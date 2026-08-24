<?php
/**
 * Read-only compact production audit for La Huerta de Ana Mary.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! class_exists( 'MDO_Database' ) ) { fwrite( STDERR, "ERROR: MDO database layer is not loaded.\n" ); exit( 2 ); }

global $wpdb;
$table = MDO_Database::table( 'source_products' );
$rows = $wpdb->get_results(
    "SELECT id, supplier_id, source_url, wc_product_id, title, status, source_payload FROM {$table} WHERE source_url LIKE '%lahuertadeanamary.com%' ORDER BY id ASC",
    ARRAY_A
) ?: array();

$hosts = array( 'lahuertadeanamary.com', 'www.lahuertadeanamary.com' );
$recent = array();
$all_issues = array();
$wrong_category = array();
$linked_count = 0;

$expected_slug = static function( string $url ): string {
    $u = strtolower( $url );
    if ( str_contains( $u, '/conservas-3/' ) ) { return 'conservas'; }
    if ( str_contains( $u, '/legumbres-10/' ) ) { return 'legumbres'; }
    return 'hortalizas-verduras';
};

$is_suspect = static function( string $value ): bool {
    if ( '' === $value ) { return false; }
    return (bool) preg_match( '/(?:Ã|Â|â€|â€™|â€œ|â€|ã.|ï¿½|�|\?\?)/u', $value );
};

foreach ( $rows as $row ) {
    $url = trim( (string) ( $row['source_url'] ?? '' ) );
    $host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
    if ( ! in_array( $host, $hosts, true ) ) { continue; }
    $pid = absint( $row['wc_product_id'] ?? 0 );
    if ( ! $pid || 'product' !== get_post_type( $pid ) ) { continue; }
    $linked_count++;
    $product = wc_get_product( $pid );
    if ( ! $product ) { continue; }
    $post = get_post( $pid );
    $cats = wp_get_post_terms( $pid, 'product_cat', array( 'fields' => 'slugs' ) );
    $cats = is_wp_error( $cats ) ? array() : array_values( $cats );
    $expected = $expected_slug( $url );
    if ( ! in_array( $expected, $cats, true ) || array_intersect( array( 'hortalizas-verduras', 'conservas', 'legumbres', 'sin-categorizar' ), array_diff( $cats, array( $expected ) ) ) ) {
        $wrong_category[] = array( 'id' => $pid, 'name' => $product->get_name(), 'expected' => $expected, 'actual' => $cats );
    }

    $desc = (string) $product->get_description();
    $short = (string) $product->get_short_description();
    $combined = implode( ' | ', array( (string) $row['title'], $product->get_name(), $post ? $post->post_name : '', wp_strip_all_tags( $desc ), wp_strip_all_tags( $short ) ) );
    if ( $is_suspect( $combined ) ) {
        $all_issues[] = array(
            'id' => $pid,
            'source_id' => (int) $row['id'],
            'source_title' => (string) $row['title'],
            'name' => $product->get_name(),
            'slug' => $post ? $post->post_name : '',
        );
    }

    if ( ! $post || $post->post_date < '2026-08-24 00:00:00' ) { continue; }
    $payload = json_decode( (string) ( $row['source_payload'] ?? '' ), true );
    $payload = is_array( $payload ) ? $payload : array();
    $payload_kg = 'kg' === strtolower( trim( (string) ( $payload['price_basis'] ?? '' ) ) );
    foreach ( array( 'description', 'title', 'price_text', 'unit_price', 'price_label' ) as $field ) {
        if ( isset( $payload[ $field ] ) && class_exists( 'MDO_Huerta_Unit_Price' ) && MDO_Huerta_Unit_Price::source_text_is_per_kg( (string) $payload[ $field ] ) ) {
            $payload_kg = true;
        }
    }

    $live_kg = null;
    $live_http = null;
    $response = wp_remote_get( $url, array(
        'timeout' => 25,
        'redirection' => 5,
        'user-agent' => 'Mozilla/5.0 (compatible; EMDO audit; +https://www.elmercadodeorigen.com/)',
        'headers' => array( 'Accept-Language' => 'es-ES,es;q=0.9' ),
    ) );
    if ( is_wp_error( $response ) ) {
        $live_http = $response->get_error_message();
    } else {
        $live_http = (int) wp_remote_retrieve_response_code( $response );
        $body = (string) wp_remote_retrieve_body( $response );
        $live_kg = class_exists( 'MDO_Huerta_Unit_Price' ) ? MDO_Huerta_Unit_Price::source_text_is_per_kg( $body ) : null;
    }

    $recent[] = array(
        'source_id' => (int) $row['id'],
        'wc_product_id' => $pid,
        'post_date' => $post->post_date,
        'source_title' => (string) $row['title'],
        'name' => $product->get_name(),
        'slug' => $post->post_name,
        'price' => $product->get_price(),
        'categories' => $cats,
        'expected_category' => $expected,
        'description_html' => $desc,
        'price_basis_meta' => (string) get_post_meta( $pid, '_emdo_huerta_price_basis', true ),
        'payload_price_basis' => (string) ( $payload['price_basis'] ?? '' ),
        'payload_price_text' => (string) ( $payload['price_text'] ?? '' ),
        'payload_unit_price' => (string) ( $payload['unit_price'] ?? '' ),
        'payload_price_label' => (string) ( $payload['price_label'] ?? '' ),
        'payload_kg_detected' => $payload_kg,
        'live_http' => $live_http,
        'live_kg_detected' => $live_kg,
        'source_url' => $url,
    );
}

usort( $recent, static fn( $a, $b ) => strcmp( $b['post_date'], $a['post_date'] ) );

echo "HUERTA_COMPACT_AUDIT_BEGIN\n";
echo wp_json_encode( array(
    'generated_at_gmt' => gmdate( 'c' ),
    'source_rows' => count( $rows ),
    'linked_products' => $linked_count,
    'wrong_category' => $wrong_category,
    'encoding_issues' => $all_issues,
    'recent_products' => $recent,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT );
echo "\nHUERTA_COMPACT_AUDIT_END\n";
