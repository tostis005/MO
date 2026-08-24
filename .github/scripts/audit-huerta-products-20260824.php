<?php
/**
 * Read-only production audit for La Huerta de Ana Mary products.
 * Triggered after workflow creation on 2026-08-24.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}
if ( ! class_exists( 'MDO_Database' ) ) {
    fwrite( STDERR, "ERROR: MDO database layer is not loaded.\n" );
    exit( 2 );
}

global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$rows = $wpdb->get_results(
    "SELECT id, supplier_id, source_url, wc_product_id, title, status FROM {$source_table} WHERE source_url LIKE '%lahuertadeanamary.com%' ORDER BY id ASC",
    ARRAY_A
);

$source_hosts = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
$items = array();

foreach ( (array) $rows as $row ) {
    $source_url = trim( (string) ( $row['source_url'] ?? '' ) );
    $host = strtolower( (string) wp_parse_url( $source_url, PHP_URL_HOST ) );
    if ( ! in_array( $host, $source_hosts, true ) ) {
        continue;
    }

    $pid = absint( $row['wc_product_id'] ?? 0 );
    $base = array(
        'source_id' => (int) $row['id'],
        'supplier_id' => (int) $row['supplier_id'],
        'source_title' => (string) $row['title'],
        'source_status' => (string) $row['status'],
        'source_url' => $source_url,
        'wc_product_id' => $pid,
    );

    if ( ! $pid || 'product' !== get_post_type( $pid ) ) {
        $base['linked'] = false;
        $items[] = $base;
        continue;
    }

    $product = wc_get_product( $pid );
    if ( ! $product ) {
        $base['linked'] = false;
        $items[] = $base;
        continue;
    }

    $post = get_post( $pid );
    $cats = wp_get_post_terms( $pid, 'product_cat' );
    $cat_data = array();
    if ( ! is_wp_error( $cats ) ) {
        foreach ( $cats as $cat ) {
            $cat_data[] = array(
                'id' => (int) $cat->term_id,
                'name' => (string) $cat->name,
                'slug' => (string) $cat->slug,
                'parent' => (int) $cat->parent,
            );
        }
    }

    $desc_html = (string) $product->get_description();
    $short_html = (string) $product->get_short_description();
    $desc_text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( $desc_html, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) );
    $short_text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( html_entity_decode( $short_html, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) ) );
    $all_text = implode( ' | ', array( $product->get_name(), $desc_text, $short_text ) );
    $has_encoding_suspect = (bool) preg_match( '/(?:Ã.|Â.|â€|â€™|â€œ|â€|ï¿½|�)/u', $all_text );
    preg_match_all( '/(?:\d+(?:[\.,]\d+)?\s*(?:€\s*\/\s*kg|€\s*por\s*kilo|€\s*kg|euros?\s*(?:\/|por)\s*kilo)|(?:precio\s*(?:por|\/)?\s*kilo|precio\s*kg)[^.;|]{0,40})/iu', $all_text, $kg_matches );

    $base['linked'] = true;
    $base['post_status'] = $post ? $post->post_status : '';
    $base['post_date'] = $post ? $post->post_date : '';
    $base['post_modified'] = $post ? $post->post_modified : '';
    $base['name'] = $product->get_name();
    $base['slug'] = $post ? $post->post_name : '';
    $base['sku'] = $product->get_sku();
    $base['type'] = $product->get_type();
    $base['price'] = $product->get_price();
    $base['regular_price'] = $product->get_regular_price();
    $base['sale_price'] = $product->get_sale_price();
    $base['weight'] = $product->get_weight();
    $base['categories'] = $cat_data;
    $base['description_html'] = $desc_html;
    $base['description_text'] = $desc_text;
    $base['short_description_html'] = $short_html;
    $base['short_description_text'] = $short_text;
    $base['encoding_suspect'] = $has_encoding_suspect;
    $base['kg_mentions'] = array_values( array_unique( array_map( 'trim', $kg_matches[0] ?? array() ) ) );
    $base['emdo_source_product_id'] = (int) get_post_meta( $pid, '_emdo_source_product_id', true );
    $base['emdo_supplier_id'] = (int) get_post_meta( $pid, '_emdo_supplier_id', true );
    $base['emdo_source_url'] = (string) get_post_meta( $pid, '_emdo_source_url', true );
    $items[] = $base;
}

usort( $items, static function( $a, $b ) {
    $ad = (string) ( $a['post_date'] ?? '' );
    $bd = (string) ( $b['post_date'] ?? '' );
    if ( $ad === $bd ) {
        return (int) ( $b['wc_product_id'] ?? 0 ) <=> (int) ( $a['wc_product_id'] ?? 0 );
    }
    return strcmp( $bd, $ad );
} );

echo "HUERTA_PRODUCT_AUDIT_BEGIN\n";
echo wp_json_encode( array(
    'generated_at_gmt' => gmdate( 'c' ),
    'source_rows' => count( $rows ),
    'items' => $items,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
echo "\nHUERTA_PRODUCT_AUDIT_END\n";
