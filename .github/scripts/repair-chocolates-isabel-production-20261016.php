<?php
/** Safe re-scrape/enrichment of Isabel catalog; never enables store or publishes products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
foreach ( array( 'home', 'siteurl' ) as $key ) {
    $host = strtolower( (string) wp_parse_url( get_option( $key ), PHP_URL_HOST ) );
    if ( ! in_array( $host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
        throw new RuntimeException( 'Solo se permite el dominio de producción.' );
    }
}
$code = 'chocolates-artesanos-isabel';
$suppliers = MDO_Database::table( 'suppliers' );
$products = MDO_Database::table( 'source_products' );
$s = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1", $code), ARRAY_A );
if ( ! $s ) { throw new RuntimeException('No existe el proveedor Isabel.'); }
$sid = (int) $s['id']; $vid = (int) $s['vendor_user_id'];
if ( ! $vid || ! get_user_meta( $vid, '_disable_vendor', true ) ) {
    throw new RuntimeException( 'El proveedor no está deshabilitado; se cancela el mantenimiento de prelanzamiento.' );
}
if ( ! class_exists( 'MDO_Isabel_Quality' ) ) {
    throw new RuntimeException( 'La versión corregida de EMDO aún no está desplegada.' );
}
$mode = getenv('ISABEL_REPAIR_PHASE') ?: 'batch';
if ( $mode === 'batch' || $mode === 'all' ) {
    for ( $round = 0; $round < ( $mode === 'all' ? 40 : 1 ); $round++ ) {
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT sp.id,sp.source_url,sp.wc_product_id FROM {$products} sp
         INNER JOIN {$wpdb->posts} p ON p.ID=sp.wc_product_id
         LEFT JOIN {$wpdb->postmeta} marker ON marker.post_id=sp.wc_product_id AND marker.meta_key='_emdo_isabel_quality_v2_done'
         WHERE sp.supplier_id=%d AND sp.status='active' AND sp.wc_product_id IS NOT NULL
         AND p.post_type='product' AND marker.meta_id IS NULL
         ORDER BY sp.id LIMIT 6", $sid
    ), ARRAY_A );
    $done=0;$blocked=0;
    foreach ( (array)$rows as $row ) {
        $row_id=(int)$row['id'];$product_id=(int)$row['wc_product_id'];
        try {
            if ( (int)get_post_field('post_author',$product_id) !== $vid ) {
                throw new RuntimeException( 'El autor de la ficha no coincide con Isabel.' );
            }
            $payload = MDO_Connector_Chocolates_Isabel::scrape_product( (string)$row['source_url'] );
            MDO_Connector_Chocolates_Isabel::upsert_product( $sid, $payload );
            // Importer re-validates stock/price, saves media and applies Isabelle-specific QA.
            $imported = MDO_Woo_Importer::import_source_product( $row_id );
            if ( (int)$imported !== $product_id ) { throw new RuntimeException('Se detectó un cambio inesperado de ID.'); }
            $woo = wc_get_product($product_id);
            if ( ! $woo || $woo->get_status() !== 'draft' || $woo->get_catalog_visibility() !== 'hidden' ||
                 (int) get_post_field('post_author',$product_id) !== $vid ) {
                throw new RuntimeException('Se ha alterado el bloqueo comercial del producto.');
            }
            update_post_meta($product_id,'_emdo_isabel_quality_v2_done','1');
            delete_post_meta($product_id,'_emdo_isabel_quality_v2_error');
            $done++;
            echo 'ISABEL_REPAIRED=' . $row_id . ':woo=' . $product_id . ':image=' . (int)$woo->get_image_id() .
                ':weight=' . $woo->get_weight() . ':dims=' . implode('x', array($woo->get_length(),$woo->get_width(),$woo->get_height())) . "\n";
        } catch (Throwable $e) {
            // Mark as attempted so a bad item cannot cause infinite retry loops.
            update_post_meta($product_id,'_emdo_isabel_quality_v2_done','error');
            update_post_meta($product_id,'_emdo_isabel_quality_v2_error', sanitize_textarea_field($e->getMessage()));
            $blocked++;
            echo 'ISABEL_REPAIR_ERROR=' . $row_id . ':' . substr($e->getMessage(),0,250) . "\n";
        }
    }
    $remaining = (int)$wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM {$products} sp
         INNER JOIN {$wpdb->posts} p ON p.ID=sp.wc_product_id
         LEFT JOIN {$wpdb->postmeta} marker ON marker.post_id=sp.wc_product_id AND marker.meta_key='_emdo_isabel_quality_v2_done'
         WHERE sp.supplier_id=%d AND sp.status='active' AND p.post_type='product' AND marker.meta_id IS NULL", $sid
    ));
    echo 'ISABEL_REPAIR_BATCH=' . wp_json_encode(array('ok'=>$done,'blocked'=>$blocked,'remaining'=>$remaining)) . "\n";
    if ( $remaining === 0 ) { break; }
    }
    if ( $mode === 'batch' ) { exit; }
    $mode = 'report';
}
if ( $mode === 'backfill' ) {
    // QA confirmed rows 730–756 are ordinary physical chocolate products.
    // Use atomic pending->importing claims; a concurrent onboarding worker
    // selects only pending rows, so the same record cannot be scheduled twice.
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id,source_url FROM {$products}
         WHERE supplier_id=%d AND status='pending'
         AND (last_error IS NULL OR last_error='')
         AND id BETWEEN 712 AND 756 ORDER BY id DESC", $sid
    ), ARRAY_A );
    $ok=0;$fail=0;$skip=0;
    foreach ( (array)$rows as $r ) {
        $row_id=(int)$r['id'];
        if ( ! MDO_Woo_Importer::mark_importing( $row_id ) ) { $skip++; continue; }
        try {
            $new_payload = MDO_Connector_Chocolates_Isabel::scrape_product( (string)$r['source_url'] );
            if ( preg_match( '/visita|taller|curso|personalizad/u', mb_strtolower( (string)($new_payload['title'] ?? ''),'UTF-8' ) ) ) {
                throw new RuntimeException( 'Artículo con servicio o personalización que exige revisión de opciones.' );
            }
            MDO_Connector_Chocolates_Isabel::upsert_product( $sid, $new_payload );
            $wc_id = (int) MDO_Woo_Importer::import_source_product( $row_id );
            $p = wc_get_product( $wc_id );
            if ( ! $p || $p->get_status() !== 'draft' || $p->get_catalog_visibility() !== 'hidden' ||
                 (int)get_post_field('post_author',$wc_id) !== $vid ) {
                throw new RuntimeException( 'No se mantuvo el estado oculto del producto.' );
            }
            update_post_meta( $wc_id, '_emdo_isabel_quality_v2_done', '1' );
            $ok++;
            echo 'ISABEL_BACKFILL_OK id=' . $row_id . ' woo=' . $wc_id . ' price=' . $p->get_price() .
                 ' image=' . (int)$p->get_image_id() . "\n";
        } catch ( Throwable $e ) {
            MDO_Woo_Importer::mark_import_error( $row_id, $e->getMessage() );
            $fail++;
            echo 'ISABEL_BACKFILL_BLOCKED id=' . $row_id . ' reason=' . substr($e->getMessage(),0,230) . "\n";
        }
    }
    echo 'ISABEL_BACKFILL_REPORT=' . wp_json_encode(array('imported'=>$ok,'blocked'=>$fail,'skipped'=>$skip)) . "\n";
    exit;
}
if ( $mode === 'dimensions' ) {
    // No network access: recover explicitly stated three-dimensional values from
    // already archived manufacturer descriptions on all imported Isabel products.
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT id,wc_product_id,source_payload FROM {$products}
         WHERE supplier_id=%d AND status='active' AND wc_product_id IS NOT NULL
         ORDER BY id", $sid
    ), ARRAY_A );
    $ok=0;$dimensions_set=0;$error=0;
    foreach ( (array) $rows as $r ) {
        $product_id = (int) $r['wc_product_id'];
        if ( (int)get_post_field('post_author',$product_id) !== $vid ) { $error++; continue; }
        $p = wc_get_product( $product_id );
        if ( ! $p || $p->get_status() !== 'draft' || $p->get_catalog_visibility() !== 'hidden' ) { $error++; continue; }
        $payload = json_decode( (string)$r['source_payload'], true );
        if ( ! is_array($payload) || empty($payload['description']) ) { $error++; continue; }
        MDO_Isabel_Quality::apply( $product_id, $payload );
        $p = wc_get_product( $product_id );
        if ( $p && $p->get_length() && $p->get_width() && $p->get_height() ) { $dimensions_set++; }
        $ok++;
    }
    echo 'ISABEL_DIMENSIONS=' . wp_json_encode(array('checked'=>$ok,'dimensions_set'=>$dimensions_set,'errors'=>$error)) . "\n";
    exit;
}
if ( $mode === 'report' ) {
    $inventory = $wpdb->get_results( $wpdb->prepare(
        "SELECT status,COUNT(*) n FROM {$products} WHERE supplier_id=%d GROUP BY status ORDER BY status", $sid
    ), ARRAY_A );
    $reviewed=(int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_emdo_isabel_quality_v2_done' AND m.meta_value='1'
         WHERE p.post_type='product' AND p.post_author=%d", $vid));
    $errors=$wpdb->get_results($wpdb->prepare(
        "SELECT p.ID,LEFT(p.post_title,120) title, LEFT(e.meta_value,200) error
         FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} e ON e.post_id=p.ID AND e.meta_key='_emdo_isabel_quality_v2_error'
         WHERE p.post_type='product' AND p.post_author=%d ORDER BY p.ID LIMIT 20", $vid),ARRAY_A);
    echo 'ISABEL_REPAIR_REPORT=' . wp_json_encode(array('statuses'=>$inventory,'reviewed'=>$reviewed,'errors'=>$errors,
        'disabled'=>(bool)get_user_meta($vid,'_disable_vendor',true)),
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit;
}
throw new RuntimeException('Fase no reconocida.');
