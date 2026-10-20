<?php
/**
 * Authorized Tropitop catalog ingestion into hidden Woo drafts, in bounded
 * production WP-CLI batches. No cart, purchase, or vendor activation.
 * Modes: TROPITOP_BULK_PHASE=init|step|status; TROPITOP_BULK_BATCH=1..5.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( 1 ); }
foreach ( array( 'home', 'siteurl' ) as $o ) {
    if ( ! in_array( strtolower( (string) wp_parse_url( get_option( $o ), PHP_URL_HOST ) ),
        array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'Only EMDO production host.' );
    }
}
foreach ( array( 'MDO_Database', 'MDO_Connector_Tropitop', 'MDO_Woo_Importer',
    'MDO_Tropitop_Shipping', 'WooCommerce' ) as $class ) {
    if ( ! class_exists( $class ) ) { throw new RuntimeException( 'Missing required class ' . $class ); }
}
global $wpdb;
$sup_table = MDO_Database::table( 'suppliers' );
$src_table = MDO_Database::table( 'source_products' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $sup_table WHERE code=%s", 'tropitop' ), ARRAY_A );
if ( ! $supplier || (int) $supplier['id'] !== 8 ||
    (int) $supplier['vendor_user_id'] !== 4971 ||
    abs( (float) $supplier['commission_percent'] - 10.0 ) > 0.001 ) {
    throw new RuntimeException( 'Tropitop identity or agreed 10% commission not verified.' );
}
if ( ! get_user_meta( 4971, '_disable_vendor', true ) || MDO_Tropitop_Shipping::is_ready() ) {
    throw new RuntimeException( 'Refusing bulk onboarding unless seller and checkout remain disabled.' );
}
if ( get_option( 'woocommerce_weight_unit' ) !== 'kg' ) {
    throw new RuntimeException( 'Woo shop weight unit is not kg.' );
}
$phase = (string) ( getenv( 'TROPITOP_BULK_PHASE' ) ?: 'status' );
$key = 'mdo_tropitop_full_catalog_20261020';
$state = get_option( $key, array() );
$state = is_array( $state ) ? $state : array();
if ( 'init' === $phase ) {
    if ( ! empty( $state['urls'] ) ) {
        echo 'TROPITOP_BULK_ALREADY_INITIALIZED=' . count( $state['urls'] ) . "\n";
        return;
    }
    // Root paths are verified against the producer homepage menu; the
    // 84-disponible list is included to catch items filed under unusual paths.
    $categories = array(
        '84-disponible',
        '109-bio', '63-frutas-tropicales', '106-frutas-del-mundo',
        '12-aguacates', '62-frutas', '80-verduras', '79-gourmet',
        '87-packs', '89-cajas-de-temporada', '77-apadrina-un-arbol',
        '62-citricos', '10-mangos', '52-cestas-de-fruta',
    );
    $urls = array(); $roots = array(); $warnings = array();
    foreach ( $categories as $category ) {
        $url = 'https://tropitop.com/es/' . $category;
        try {
            $items = MDO_Connector_Tropitop::discover( array(
                'source_url' => $url, 'exclusion_url_fragments' => '[]',
            ) );
            $found = (array) ( $items['products'] ?? array() );
            if ( ! $found ) { continue; }
            $roots[] = $url;
            foreach ( $found as $purl ) {
                if ( preg_match( '~/([0-9]+)(?:-[0-9]+)?-[a-z0-9-]+\.html$~i',
                    (string) wp_parse_url( $purl, PHP_URL_PATH ), $m ) ) {
                    // Key by *product* ID, never PrestaShop combination or URL.
                    $id = (int) $m[1];
                    if ( ! isset( $urls[ $id ] ) ) { $urls[ $id ] = $purl; }
                }
            }
            echo 'TROPITOP_CATEGORY_DISCOVER=' . $category . ' count=' . count( $found ) . "\n";
        } catch ( Throwable $e ) {
            $warnings[$category] = $e->getMessage();
            echo 'TROPITOP_CATEGORY_WARNING=' . $category . ' ' . $e->getMessage() . "\n";
        }
    }
    $count = count( $urls );
    if ( $count < 130 || $count > 600 ||
        ! in_array( 'https://tropitop.com/es/84-disponible', $roots, true ) ) {
        throw new RuntimeException( 'Catalog discovery unexpectedly incomplete/large: ' . $count );
    }
    ksort( $urls, SORT_NUMERIC );
    $state = array(
        'urls' => array_values( $urls ), 'roots' => $roots,
        'cursor' => 0, 'imported' => array(), 'failed' => array(),
        'warnings' => $warnings, 'started_at' => gmdate( 'c' ),
    );
    update_option( $key, $state, false );
    // This activates catalogue discovery only after bootstrap completes,
    // not public WCFM sales. Register the full set of roots for daily updates.
    $wpdb->update( $sup_table, array(
        'source_url' => implode( "\n", $roots ),
        'sync_frequency' => 'manual', 'active' => 0, 'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => 8 ) );
    echo 'TROPITOP_BULK_INIT=' . wp_json_encode( array(
        'products' => $count, 'root_categories' => count( $roots ),
        'root_warnings' => $warnings, 'seller_disabled' => true,
    ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
    return;
}
if ( ! in_array( $phase, array( 'step', 'status' ), true ) ) {
    throw new RuntimeException( 'Unknown phase ' . $phase );
}
if ( empty( $state['urls'] ) || ! is_array( $state['urls'] ) ) {
    throw new RuntimeException( 'Tropitop bulk catalog has not been initialized.' );
}
$total = count( $state['urls'] );
if ( 'status' === $phase ) {
    echo 'TROPITOP_BULK_STATUS=' . wp_json_encode( array(
        'total' => $total, 'cursor' => (int) $state['cursor'],
        'imported' => count( (array) $state['imported'] ),
        'failed' => count( (array) $state['failed'] ),
        'failed_samples' => array_slice( (array) $state['failed'], 0, 12, true ),
        'producer_active' => (int) $supplier['active'],
        'seller_disabled' => true,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    return;
}
$limit = max( 1, min( 5, (int) ( getenv( 'TROPITOP_BULK_BATCH' ) ?: 2 ) ) );
$start = (int) $state['cursor'];
$end = min( $total, $start + $limit );
for ( $i = $start; $i < $end; $i++ ) {
    $url = (string) $state['urls'][$i];
    $m = array();
    preg_match( '~/([0-9]+)(?:-[0-9]+)?-[a-z0-9-]+\.html$~i',
        (string) wp_parse_url( $url, PHP_URL_PATH ), $m );
    $pid = (string) ( $m[1] ?? $i );
    try {
        $payload = MDO_Connector_Tropitop::scrape_product( $url );
        $stock = (string) ( $payload['stock_status'] ?? 'unknown' );
        if ( ! in_array( $stock, array( 'instock', 'outofstock' ), true ) ) {
            throw new RuntimeException( 'Stock sin verificación suficiente.' );
        }
        if ( (int) ( $payload['image_count'] ?? 0 ) < 1 ||
            mb_strlen( wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) ) < 80 ) {
            throw new RuntimeException( 'Faltan fotos o descripción de producto verificable.' );
        }
        $variations = (array) ( $payload['variations'] ?? array() );
        if ( ! $variations || count( $variations ) !== (int) ( $payload['variation_count'] ?? 0 ) ) {
            throw new RuntimeException( 'Variaciones incompletas.' );
        }
        foreach ( $variations as $variation ) {
            if ( empty( $variation['attributes'] ) ||
                ! isset( $variation['display_price'], $variation['weight_kg'] ) ||
                ! is_numeric( $variation['display_price'] ) ||
                (float) $variation['display_price'] <= 0.0 ||
                ! is_numeric( $variation['weight_kg'] ) ||
                (float) $variation['weight_kg'] <= 0.0 ||
                (float) $variation['weight_kg'] > 36.0 ) {
                throw new RuntimeException( 'Precio/peso/variante inválida: no se calcula un porte inventado.' );
            }
        }
        MDO_Connector_Tropitop::upsert_product( 8, $payload );
        $source_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM $src_table WHERE supplier_id=%d AND source_url=%s ORDER BY id DESC LIMIT 1",
            8, $payload['source_url']
        ) );
        if ( ! $source_id ) { throw new RuntimeException( 'Origen sin registrar tras upsert.' ); }
        $woo_id = MDO_Woo_Importer::import_source_product( $source_id );
        $woo = wc_get_product( $woo_id );
        if ( ! $woo || ! $woo->is_type( 'variable' ) ||
            (int) get_post_field( 'post_author', $woo_id ) !== 4971 ||
            $woo->get_status() !== 'draft' || $woo->get_catalog_visibility() !== 'hidden' ||
            ! $woo->get_image_id() ||
            1 + count( $woo->get_gallery_image_ids() ) < min( 20, (int) $payload['image_count'] ) ||
            ! $woo->get_category_ids() ) {
            throw new RuntimeException( 'QA Woo: estado, autor, taxonomía o galería incompletos.' );
        }
        $weight_values = array();
        foreach ( $woo->get_children() as $child_id ) {
            $child = wc_get_product( $child_id );
            if ( ! $child || (float) $child->get_weight() <= 0 ) {
                throw new RuntimeException( 'Variación Woo sin peso de envío.' );
            }
            $weight_values[] = (float) $child->get_weight();
        }
        if ( count( $weight_values ) !== count( $variations ) ) {
            throw new RuntimeException( 'Número de variaciones guardadas diferente a origen.' );
        }
        update_post_meta( $woo_id, '_mdo_tropitop_prelaunch_validated', '1' );
        $state['imported'][$pid] = array(
            'woo_id' => (int) $woo_id, 'stock' => $stock,
            'options' => count( $variations ), 'images' => 1 + count( $woo->get_gallery_image_ids() ),
        );
        unset( $state['failed'][$pid] );
        echo 'TROPITOP_IMPORT_OK=' . wp_json_encode( array(
            'source_id' => $pid, 'woo_id' => (int) $woo_id,
            'stock' => $stock, 'options' => count( $variations ),
        ), JSON_UNESCAPED_SLASHES ) . "\n";
    } catch ( Throwable $e ) {
        $state['failed'][$pid] = array( 'url' => $url, 'reason' => substr( $e->getMessage(), 0, 250 ) );
        // If a partial Woo record was created before an exception, always
        // leave it invisible: the vendor and checkout are disabled as well.
        echo 'TROPITOP_IMPORT_REVIEW=' . wp_json_encode( array(
            'source_id' => $pid, 'error' => $e->getMessage(),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    }
    $state['cursor'] = $i + 1;
    update_option( $key, $state, false );
    unset( $payload, $woo );
    gc_collect_cycles();
}
if ( (int) $state['cursor'] >= $total ) {
    $state['finished_at'] = gmdate( 'c' );
    update_option( $key, $state, false );
    // Enable *source synchronisation*, not publication. The Woo seller stays
    // blocked and shipping verification remains false.
    $wpdb->update( $sup_table, array(
        'active' => 1, 'sync_frequency' => 'daily',
        'last_sync_at' => current_time( 'mysql' ),
        'updated_at' => current_time( 'mysql' ),
    ), array( 'id' => 8 ) );
    echo 'TROPITOP_BULK_DONE=' . wp_json_encode( array(
        'discovered' => $total,
        'imported' => count( $state['imported'] ),
        'review_required' => count( $state['failed'] ),
        'seller_disabled' => true, 'checkout_enabled' => false,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
} else {
    echo 'TROPITOP_BULK_PROGRESS=' . (int) $state['cursor'] . '/' . $total .
        ' imported=' . count( $state['imported'] ) .
        ' review=' . count( $state['failed'] ) . "\n";
}
