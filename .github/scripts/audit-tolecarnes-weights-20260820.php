<?php
/** Read-only production audit of Tolecarnes imported products and source weight data. */
// Triggered after workflow registration on 2026-08-20.
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! class_exists( 'MDO_Database' ) ) { fwrite( STDERR, "ERROR: MDO database layer not loaded.\n" ); exit( 2 ); }

global $wpdb;
$suppliers_table = MDO_Database::table( 'suppliers' );
$products_table  = MDO_Database::table( 'source_products' );

$suppliers = $wpdb->get_results(
    "SELECT id,code,name,source_url,vendor_user_id,active FROM {$suppliers_table}
     WHERE LOWER(name) LIKE '%tole%' OR LOWER(code) LIKE '%tole%' OR LOWER(source_url) LIKE '%tolecarnes%'
     ORDER BY id ASC",
    ARRAY_A
);

if ( empty( $suppliers ) ) {
    echo "TOLE_AUDIT_SUMMARY " . wp_json_encode( array( 'suppliers' => 0, 'products' => 0, 'linked_products' => 0 ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    echo "tolecarnes_audit_ok\n";
    exit( 0 );
}

$summary = array(
    'suppliers' => count( $suppliers ),
    'products' => 0,
    'linked_products' => 0,
    'source_fetch_ok' => 0,
    'source_fetch_failed' => 0,
    'source_weight_signal' => 0,
    'current_wc_weight_set' => 0,
    'current_description_weight_signal' => 0,
);

foreach ( $suppliers as $supplier ) {
    echo 'TOLE_SUPPLIER ' . wp_json_encode( $supplier, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    $rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id,supplier_id,source_url,wc_product_id,title,status,source_price,source_stock_status,source_payload,last_error
             FROM {$products_table}
             WHERE supplier_id=%d
             ORDER BY id ASC",
            (int) $supplier['id']
        ),
        ARRAY_A
    );

    foreach ( (array) $rows as $row ) {
        ++$summary['products'];
        $url = trim( (string) ( $row['source_url'] ?? '' ) );
        $payload = json_decode( (string) ( $row['source_payload'] ?? '' ), true );
        if ( ! is_array( $payload ) ) { $payload = array(); }

        $record = array(
            'source_id' => (int) $row['id'],
            'supplier_id' => (int) $row['supplier_id'],
            'wc_product_id' => absint( $row['wc_product_id'] ?? 0 ),
            'status' => (string) ( $row['status'] ?? '' ),
            'source_url' => $url,
            'source_title' => (string) ( $row['title'] ?? '' ),
            'source_price' => (string) ( $row['source_price'] ?? '' ),
            'source_stock_status' => (string) ( $row['source_stock_status'] ?? '' ),
            'payload_title' => (string) ( $payload['title'] ?? '' ),
            'payload_description' => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) ) ),
            'payload_keys' => array_keys( $payload ),
            'last_error' => (string) ( $row['last_error'] ?? '' ),
        );

        $product_id = $record['wc_product_id'];
        if ( $product_id && 'product' === get_post_type( $product_id ) ) {
            ++$summary['linked_products'];
            $current_desc = (string) get_post_field( 'post_content', $product_id );
            $current_short = (string) get_post_field( 'post_excerpt', $product_id );
            $wc_weight = (string) get_post_meta( $product_id, '_weight', true );
            if ( '' !== trim( $wc_weight ) ) { ++$summary['current_wc_weight_set']; }
            $plain_current = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $current_desc . ' ' . $current_short ) ) );
            $current_has_weight = (bool) preg_match( '/\b(?:peso|aprox(?:\.|imadamente)?|kg|kilogramos?|gramos?|\d+\s*g\b|\d+\s*kg\b)/iu', $plain_current );
            if ( $current_has_weight ) { ++$summary['current_description_weight_signal']; }
            $record += array(
                'current_title' => get_the_title( $product_id ),
                'current_description' => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $current_desc ) ) ),
                'current_short_description' => trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $current_short ) ) ),
                'current_wc_weight' => $wc_weight,
                'current_has_weight_signal' => $current_has_weight,
            );
        }

        if ( $url ) {
            $response = wp_remote_get( $url, array(
                'timeout' => 20,
                'redirection' => 5,
                'user-agent' => 'Mozilla/5.0 (compatible; EMDO catalog audit/1.0)',
            ) );
            if ( is_wp_error( $response ) ) {
                ++$summary['source_fetch_failed'];
                $record['source_fetch_error'] = $response->get_error_message();
            } else {
                $code = (int) wp_remote_retrieve_response_code( $response );
                $body = (string) wp_remote_retrieve_body( $response );
                $plain = html_entity_decode( wp_strip_all_tags( preg_replace( '#<script\b[^>]*>.*?</script>#is', ' ', $body ) ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
                $plain = trim( preg_replace( '/\s+/u', ' ', $plain ) );
                $record['source_http_code'] = $code;
                if ( $code >= 200 && $code < 400 && $plain ) {
                    ++$summary['source_fetch_ok'];
                    $signals = array();
                    if ( preg_match_all( '/.{0,110}\b(?:Peso|PRECIO POR LOTE|precio por lote|aprox(?:\.|imadamente)?|paquetes?[^.]{0,40}|\d+(?:[\.,]\d+)?\s*(?:kg|kilogramos?|g|gramos?)\b).{0,150}/iu', $plain, $matches ) ) {
                        foreach ( array_slice( $matches[0], 0, 8 ) as $m ) {
                            $m = trim( preg_replace( '/\s+/u', ' ', $m ) );
                            if ( '' !== $m && ! in_array( $m, $signals, true ) ) { $signals[] = $m; }
                        }
                    }
                    if ( ! empty( $signals ) ) { ++$summary['source_weight_signal']; }
                    $record['source_weight_signals'] = $signals;
                } else {
                    ++$summary['source_fetch_failed'];
                }
            }
        }

        echo 'TOLE_PRODUCT ' . wp_json_encode( $record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
    }
}

echo 'TOLE_AUDIT_SUMMARY ' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "tolecarnes_audit_ok\n";
