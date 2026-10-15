<?php
/**
 * Controlled Latitudes import. Invoke with LATITUDES_PHASE=preflight|batch|final.
 * Never activate the vendor or publish products.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$phase = getenv( 'LATITUDES_PHASE' ) ?: 'preflight';
$suppliers = MDO_Database::table( 'suppliers' );
$sources = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1", 'latitudes' ), ARRAY_A );
if ( ! $supplier || 'latitudes' !== (string) $supplier['connector'] ) {
    throw new RuntimeException( 'No está configurado el proveedor Latitudes en EMDO.' );
}
$id = (int) $supplier['id'];
$vendor = (int) $supplier['vendor_user_id'];
if ( $id <= 0 || $vendor <= 0 || (string) get_user_meta( $vendor, '_disable_vendor', true ) !== '1' ) {
    throw new RuntimeException( 'Bloqueo de seguridad: Latitudes debe tener vendedor deshabilitado.' );
}
$count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sources} WHERE supplier_id=%d", $id ) );
if ( $count !== 138 ) {
    throw new RuntimeException( 'Catálogo de origen inesperado; se esperaban 138 referencias, encontradas ' . $count );
}
$published = (int) $wpdb->get_var( $wpdb->prepare(
    "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='publish'", $vendor
) );
if ( $published !== 0 ) {
    throw new RuntimeException( 'Hay productos publicados de Latitudes. Se cancela importación.' );
}
if ( 'preflight' === $phase ) {
    $invalid = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$sources} WHERE supplier_id=%d AND (source_price IS NULL OR source_price <= 0 OR source_payload IS NULL OR source_stock_status NOT IN ('instock','outofstock'))", $id
    ) );
    if ( $invalid ) { throw new RuntimeException( 'Hay ' . $invalid . ' referencias con campos críticos incompletos.' ); }
    echo 'LATITUDES_BULK_PREFLIGHT=' . wp_json_encode( array(
        'supplier_id' => $id, 'vendor_id' => $vendor,
        'disabled' => 1, 'sources' => $count, 'invalid' => $invalid, 'published' => $published,
    ) ) . PHP_EOL;
    exit;
}
if ( 'batch' === $phase ) {
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id,title,source_price,source_stock_status FROM {$sources} WHERE supplier_id=%d AND status='pending' AND (last_error IS NULL OR last_error='') ORDER BY id ASC LIMIT 5", $id
    ), ARRAY_A );
    $ok = array();
    $errors = array();
    foreach ( $rows as $row ) {
        $src_id = (int) $row['id'];
        try {
            $wc_id = MDO_Woo_Importer::import_source_product( $src_id );
            $wc = wc_get_product( $wc_id );
            if ( ! $wc ) { throw new RuntimeException( 'No se ha podido cargar el producto importado.' ); }
            if ( $wc->get_status() !== 'draft' || $wc->get_catalog_visibility() !== 'hidden' ||
                (int) get_post_field( 'post_author', $wc_id ) !== $vendor ) {
                $wc->set_status( 'draft' );
                $wc->set_catalog_visibility( 'hidden' );
                $wc->save();
                throw new RuntimeException( 'Bloqueo de publicación aplicado tras importar: revisar el producto.' );
            }
            if ( ! $wc->get_image_id() ||
                mb_strlen( trim( wp_strip_all_tags( (string) $wc->get_description() ) ) ) < 35 ||
                ! is_numeric( $wc->get_price() ) ) {
                throw new RuntimeException( 'Faltan imágenes, precio o descripción en WooCommerce.' );
            }
            if ( abs( (float) $wc->get_price() - (float) $row['source_price'] ) > 0.02 ) {
                throw new RuntimeException( 'Precio importado distinto del origen.' );
            }
            if ( $wc->get_stock_status() !== $row['source_stock_status'] ) {
                throw new RuntimeException( 'El stock importado no coincide con el origen.' );
            }
            $ok[] = $wc_id;
        } catch ( Throwable $exception ) {
            MDO_Woo_Importer::mark_import_error( $src_id, $exception->getMessage() );
            $errors[] = array( 'source_id' => $src_id, 'message' => $exception->getMessage() );
        }
    }
    $pending = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sources} WHERE supplier_id=%d AND status='pending' AND (last_error IS NULL OR last_error='')", $id ) );
    $active = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$sources} WHERE supplier_id=%d AND status='active'", $id ) );
    echo 'LATITUDES_BULK_BATCH=' . wp_json_encode( array(
        'imported' => count( $ok ), 'ids' => $ok, 'errors' => $errors,
        'remaining' => $pending, 'active' => $active,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    if ( count( $errors ) > 3 ) { exit( 31 ); }
    exit;
}
if ( 'final' === $phase ) {
    $active_rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id,wc_product_id,title,status,last_error FROM {$sources} WHERE supplier_id=%d ORDER BY id", $id
    ), ARRAY_A );
    $bad = array();
    $ready = 0;
    $pending = 0;
    foreach ( $active_rows as $row ) {
        if ( $row['status'] !== 'active' || ! $row['wc_product_id'] ) {
            $pending++;
            if ( count( $bad ) < 15 ) { $bad[] = array( 'title' => $row['title'], 'error' => $row['last_error'] ); }
            continue;
        }
        $product = wc_get_product( (int) $row['wc_product_id'] );
        if ( ! $product || $product->get_status() !== 'draft' || $product->get_catalog_visibility() !== 'hidden' ||
            (int) get_post_field( 'post_author', (int) $row['wc_product_id'] ) !== $vendor ||
            ! $product->get_image_id() ) {
            if ( count( $bad ) < 15 ) { $bad[] = array( 'wc_id' => $row['wc_product_id'], 'title' => $row['title'], 'error' => 'Not a complete hidden draft' ); }
            continue;
        }
        $ready++;
    }
    echo 'LATITUDES_BULK_FINAL=' . wp_json_encode( array(
        'source_total' => $count, 'imported_verified' => $ready, 'pending' => $pending,
        'published' => $published, 'disabled' => 1, 'errors_sample' => $bad,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
    if ( $ready !== $count || $pending !== 0 || $bad ) { exit( 41 ); }
    echo 'LATITUDES_HIDDEN_IMPORT_OK' . PHP_EOL;
    exit;
}
throw new RuntimeException( 'LATITUDES_PHASE desconocida.' );
