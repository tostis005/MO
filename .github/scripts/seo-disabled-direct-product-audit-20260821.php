<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$targets = array(
    'disabled_puente' => 11170,
    'disabled_catedratico' => 11975,
    'active_tolecarnes' => 11058,
);
$out = array();

foreach ( $targets as $label => $id ) {
    $post = get_post( $id );
    if ( ! $post instanceof WP_Post ) {
        $out[$label] = array( 'id' => $id, 'exists' => false );
        continue;
    }
    $url = get_permalink( $post );
    $response = wp_remote_get( add_query_arg( 'emdo_direct_visibility_audit', time() . '-' . $id, $url ), array(
        'timeout' => 30,
        'redirection' => 0,
        'headers' => array( 'User-Agent' => 'EMDO-Direct-Product-Audit/2026-08-21', 'Cache-Control' => 'no-cache' ),
    ) );
    $http = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
    $out[$label] = array(
        'id' => $id,
        'exists' => true,
        'status' => $post->post_status,
        'author' => (int) $post->post_author,
        'url' => $url,
        'vendor_disabled' => function_exists( 'elmercado_wcfm_product_is_from_disabled_vendor_010210' )
            ? (bool) elmercado_wcfm_product_is_from_disabled_vendor_010210( $id ) : null,
        'http_public' => $http,
        'error' => is_wp_error( $response ) ? $response->get_error_message() : '',
    );
}

echo "EMDO DISABLED DIRECT PRODUCT PUBLIC AUDIT 2026-08-21\n";
echo wp_json_encode( $out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . "\n";

$ok = ! empty( $out['disabled_puente']['vendor_disabled'] )
    && ! empty( $out['disabled_catedratico']['vendor_disabled'] )
    && empty( $out['active_tolecarnes']['vendor_disabled'] )
    && (int) ($out['disabled_puente']['http_public'] ?? 0) === 404
    && (int) ($out['disabled_catedratico']['http_public'] ?? 0) === 404
    && (int) ($out['active_tolecarnes']['http_public'] ?? 0) === 200;

echo 'DISABLED_DIRECT_PRODUCT_VISIBILITY=' . ( $ok ? 'PASS' : 'REVIEW' ) . "\n";
exit( $ok ? 0 : 2 );
