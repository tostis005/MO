<?php
/** Read-only production audit of La Huerta de Ana Mary imported products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! class_exists( 'MDO_Database' ) ) { fwrite( STDERR, "ERROR: MDO database layer not loaded.\n" ); exit( 2 ); }

global $wpdb;
$table = MDO_Database::table( 'source_products' );
$rows = $wpdb->get_results(
    "SELECT id,supplier_id,source_url,wc_product_id,title,status,source_price,source_stock_status,source_payload,last_error
     FROM {$table}
     WHERE source_url LIKE '%lahuertadeanamary.com%'
     ORDER BY id ASC",
    ARRAY_A
);

$source_hosts = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
$summary = array(
    'source_rows' => 0,
    'linked_products' => 0,
    'mojibake_title' => 0,
    'mojibake_description' => 0,
    'families' => array(),
    'statuses' => array(),
);

foreach ( (array) $rows as $row ) {
    $url = trim( (string) ( $row['source_url'] ?? '' ) );
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    if ( ! in_array( $host, $source_hosts, true ) ) { continue; }
    ++$summary['source_rows'];
    $status = (string) ( $row['status'] ?? 'unknown' );
    $summary['statuses'][ $status ] = ( $summary['statuses'][ $status ] ?? 0 ) + 1;

    $family = 'other';
    $path = strtolower( (string) wp_parse_url( $url, PHP_URL_PATH ) );
    if ( str_contains( $path, '/conservas-3/' ) ) { $family = 'conservas'; }
    elseif ( str_contains( $path, '/legumbres-10/' ) ) { $family = 'legumbres'; }
    elseif ( str_contains( $path, '/hortalizas-2/' ) ) { $family = 'hortalizas'; }
    $summary['families'][ $family ] = ( $summary['families'][ $family ] ?? 0 ) + 1;

    $product_id = absint( $row['wc_product_id'] ?? 0 );
    $payload = json_decode( (string) ( $row['source_payload'] ?? '' ), true );
    if ( ! is_array( $payload ) ) { $payload = array(); }

    $record = array(
        'source_id' => (int) $row['id'],
        'wc_product_id' => $product_id,
        'status' => $status,
        'family' => $family,
        'source_url' => $url,
        'source_row_title' => (string) ( $row['title'] ?? '' ),
        'source_price' => (string) ( $row['source_price'] ?? '' ),
        'source_stock_status' => (string) ( $row['source_stock_status'] ?? '' ),
        'payload_title' => (string) ( $payload['title'] ?? '' ),
        'payload_description' => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) ) ),
        'last_error' => (string) ( $row['last_error'] ?? '' ),
    );

    if ( $product_id && 'product' === get_post_type( $product_id ) ) {
        ++$summary['linked_products'];
        $title = get_the_title( $product_id );
        $description = (string) get_post_field( 'post_content', $product_id );
        $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'all' ) );
        $cats = array();
        if ( ! is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) { $cats[] = array( 'name' => $term->name, 'slug' => $term->slug ); }
        }
        $title_bad = (bool) preg_match( '/(?:Ã|Â|â|�)/u', (string) $title );
        $desc_bad = (bool) preg_match( '/(?:Ã|Â|â|�)/u', (string) $description );
        if ( $title_bad ) { ++$summary['mojibake_title']; }
        if ( $desc_bad ) { ++$summary['mojibake_description']; }
        $record += array(
            'current_title' => $title,
            'current_description' => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $description ) ) ),
            'categories' => $cats,
            'title_mojibake' => $title_bad,
            'description_mojibake' => $desc_bad,
        );
    }

    echo 'HUERTA_PRODUCT ' . wp_json_encode( $record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}

ksort( $summary['families'] );
ksort( $summary['statuses'] );
echo 'HUERTA_AUDIT_SUMMARY ' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "huerta_audit_ok\n";
