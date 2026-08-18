<?php
/**
 * Plugin Name: MDO Merchant Consistency
 * Description: Keeps homepage marketplace claims consistent with seller-specific shipping/returns and removes invalid stored GTIN/EAN values.
 * Version: 1.1.0
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const MDO_MC_VERSION = '2026-08-18-3';

/**
 * Validate a stored GTIN after removing spaces and hyphens.
 * Google accepts 8, 12, 13 and 14 digit GTINs, with a valid GS1 check digit.
 */
function mdo_mc_gtin_is_valid( string $raw ): bool {
    $value = preg_replace( '/[\s-]+/', '', trim( $raw ) );
    if ( ! is_string( $value ) || ! preg_match( '/^\d{8}$|^\d{12}$|^\d{13}$|^\d{14}$/', $value ) ) {
        return false;
    }

    $digits = array_map( 'intval', str_split( $value ) );
    $check  = array_pop( $digits );
    $sum    = 0;
    $weight = 3;
    for ( $i = count( $digits ) - 1; $i >= 0; $i-- ) {
        $sum += $digits[ $i ] * $weight;
        $weight = ( 3 === $weight ) ? 1 : 3;
    }
    $expected = ( 10 - ( $sum % 10 ) ) % 10;
    return $expected === $check;
}

/**
 * Remove only objectively invalid non-empty EAN/GTIN values already stored by the
 * WooCommerce EAN plugin. Never invent or auto-correct a code. Each removed value
 * is backed up in post meta so it can be restored if a manufacturer confirms it.
 *
 * @return array<int,string> Product/variation ID => removed value.
 */
function mdo_mc_clean_invalid_eans(): array {
    $cleaned = array();
    $ids = get_posts( array(
        'post_type'        => array( 'product', 'product_variation' ),
        'post_status'      => 'any',
        'posts_per_page'   => -1,
        'fields'           => 'ids',
        'suppress_filters' => true,
        'meta_query'       => array(
            array(
                'key'     => '_alg_ean',
                'value'   => '',
                'compare' => '!=',
            ),
        ),
    ) );

    foreach ( array_map( 'intval', (array) $ids ) as $id ) {
        $ean = trim( (string) get_post_meta( $id, '_alg_ean', true ) );
        if ( '' === $ean || mdo_mc_gtin_is_valid( $ean ) ) {
            continue;
        }
        if ( ! metadata_exists( 'post', $id, '_mdo_mc_invalid_ean_backup_20260818' ) ) {
            update_post_meta( $id, '_mdo_mc_invalid_ean_backup_20260818', $ean );
        }
        delete_post_meta( $id, '_alg_ean' );
        $cleaned[ $id ] = $ean;
    }

    update_option( '_mdo_mc_identifier_cleanup_20260818', array(
        'version' => MDO_MC_VERSION,
        'time'    => current_time( 'mysql' ),
        'cleaned' => $cleaned,
    ), false );

    return $cleaned;
}

/**
 * Replace legacy global promises with wording that accurately reflects the
 * multi-seller model. Patterns deliberately accept common Unicode dashes and
 * apostrophes plus JSON/HTML-escaped forms used in SEO metadata.
 */
function mdo_mc_replace_home_claims( string $text, string $lang ): string {
    if ( 'en' === $lang ) {
        $text = strtr( $text, array(
            'FREE SHIPPING FROM SELECT PRODUCERS' => 'SHIPPING TERMS BY PRODUCER',
            '24-48H DELIVERY'                     => 'DIRECT SHIPPING BY PRODUCERS',
            '24–48H DELIVERY'                     => 'DIRECT SHIPPING BY PRODUCERS',
            '24—48H DELIVERY'                     => 'DIRECT SHIPPING BY PRODUCERS',
            '24\\u201348H DELIVERY'               => 'DIRECT SHIPPING BY PRODUCERS',
            '24\\u201448H DELIVERY'               => 'DIRECT SHIPPING BY PRODUCERS',
            'EASY RETURNS'                        => 'RETURNS SUPPORT',
            'WE’RE HERE TO HELP'                  => 'CUSTOMER SUPPORT &amp; TRACKING',
            'WE‘RE HERE TO HELP'                  => 'CUSTOMER SUPPORT &amp; TRACKING',
            "WE'RE HERE TO HELP"                 => 'CUSTOMER SUPPORT &amp; TRACKING',
            'WE&amp;rsquo;RE HERE TO HELP'        => 'CUSTOMER SUPPORT &amp; TRACKING',
            'WE&rsquo;RE HERE TO HELP'            => 'CUSTOMER SUPPORT &amp; TRACKING',
            'WE\\u2019RE HERE TO HELP'            => 'CUSTOMER SUPPORT &amp; TRACKING',
            'From the producer to your home in 24 - 48 business hours (except sliced products)' => 'From the producer to your home, with delivery times shown by producer and destination',
            '/en/special-conditions/'             => '/en/shipping/',
            '/en/condiciones-especiales/'         => '/en/shipping/',
        ) );
        $patterns = array(
            '/FREE\s+SHIPPING\s+FROM\s+SELECT\s+PRODUCERS/iu' => 'SHIPPING TERMS BY PRODUCER',
            '/24[\p{Pd}\x{00A0}\x{2009}\s]*48H\s+DELIVERY/iu' => 'DIRECT SHIPPING BY PRODUCERS',
            '/EASY\s+RETURNS/iu' => 'RETURNS SUPPORT',
            '/WE(?:[\'’‘´`]|&(?:rsquo|#8217|#x2019);)RE\s+HERE\s+TO\s+HELP/iu' => 'CUSTOMER SUPPORT &amp; TRACKING',
        );
        foreach ( $patterns as $pattern => $replacement ) {
            $updated = preg_replace( $pattern, $replacement, $text );
            if ( is_string( $updated ) ) { $text = $updated; }
        }
        return $text;
    }

    $text = strtr( $text, array(
        'ENVÍO GRATIS EN VARIOS PRODUCTORES' => 'CONDICIONES DE ENVÍO POR PRODUCTOR',
        'ENVÍOS EN 24-48H'                   => 'ENVÍOS DIRECTOS DEL PRODUCTOR',
        'ENVÍOS EN 24–48H'                   => 'ENVÍOS DIRECTOS DEL PRODUCTOR',
        'ENVÍOS EN 24—48H'                   => 'ENVÍOS DIRECTOS DEL PRODUCTOR',
        'DEVOLUCIÓN FÁCIL Y SENCILLA'        => 'ASISTENCIA EN DEVOLUCIONES',
        'RESOLVEMOS TUS DUDAS'               => 'ATENCIÓN Y SEGUIMIENTO',
        'Del productor a tu casa en 24 - 48h hábiles (excepto en loncheados)' => 'Del productor a tu casa, con plazos según productor y destino',
        '/condiciones-especiales/'            => '/envios/',
        'ENV\\u00cdO GRATIS EN VARIOS PRODUCTORES' => 'CONDICIONES DE ENV\\u00cdO POR PRODUCTOR',
        'ENV\\u00cdOS EN 24-48H'                  => 'ENV\\u00cdOS DIRECTOS DEL PRODUCTOR',
        'DEVOLUCI\\u00d3N F\\u00c1CIL Y SENCILLA' => 'ASISTENCIA EN DEVOLUCIONES',
    ) );
    return $text;
}

/**
 * Normalize the underlying homepage source used by the Spanish page and by the
 * reviewed English island metadata. This prevents SEO descriptions/schema from
 * reintroducing legacy global promises before the final HTML filter runs.
 * A one-time backup is stored before the first change.
 *
 * @return array<string,bool|int>
 */
function mdo_mc_normalize_home_sources(): array {
    $front_id = (int) get_option( 'page_on_front' );
    if ( $front_id <= 0 ) { return array( 'front_id' => 0, 'changed' => false ); }

    $post = get_post( $front_id );
    if ( ! $post instanceof WP_Post ) { return array( 'front_id' => $front_id, 'changed' => false ); }

    if ( ! get_option( '_mdo_mc_home_claim_backup_20260818', false ) ) {
        update_option( '_mdo_mc_home_claim_backup_20260818', array(
            'time'                => current_time( 'mysql' ),
            'post_content'        => (string) $post->post_content,
            'post_excerpt'        => (string) $post->post_excerpt,
            '_en_US_post_content' => (string) get_post_meta( $front_id, '_en_US_post_content', true ),
            '_en_US_post_excerpt' => (string) get_post_meta( $front_id, '_en_US_post_excerpt', true ),
        ), false );
    }

    $changed = false;
    $es_content = mdo_mc_replace_home_claims( (string) $post->post_content, 'es' );
    $es_excerpt = mdo_mc_replace_home_claims( (string) $post->post_excerpt, 'es' );
    if ( $es_content !== (string) $post->post_content || $es_excerpt !== (string) $post->post_excerpt ) {
        wp_update_post( array(
            'ID'           => $front_id,
            'post_content' => $es_content,
            'post_excerpt' => $es_excerpt,
        ) );
        $changed = true;
    }

    foreach ( array( '_en_US_post_content', '_en_US_post_excerpt' ) as $key ) {
        $old = get_post_meta( $front_id, $key, true );
        if ( ! is_string( $old ) || '' === $old ) { continue; }
        $new = mdo_mc_replace_home_claims( $old, 'en' );
        if ( $new !== $old ) {
            update_post_meta( $front_id, $key, $new );
            $changed = true;
        }
    }

    update_option( '_mdo_mc_home_claim_normalized_20260818', array(
        'version' => MDO_MC_VERSION,
        'time'    => current_time( 'mysql' ),
        'front_id'=> $front_id,
    ), false );

    return array( 'front_id' => $front_id, 'changed' => $changed );
}

add_action( 'init', static function() {
    $done = get_option( '_mdo_mc_identifier_cleanup_20260818', array() );
    if ( ! is_array( $done ) || ( $done['version'] ?? '' ) !== MDO_MC_VERSION ) {
        mdo_mc_clean_invalid_eans();
    }

    $home_done = get_option( '_mdo_mc_home_claim_normalized_20260818', array() );
    if ( ! is_array( $home_done ) || ( $home_done['version'] ?? '' ) !== MDO_MC_VERSION ) {
        mdo_mc_normalize_home_sources();
    }
}, 20 );

function mdo_mc_home_request_language(): string {
    $path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
    $path = trailingslashit( $path ?: '/' );
    if ( '/en/' === $path || 0 === strpos( $path, '/en/' ) ) { return 'en'; }
    if ( '/' === $path ) { return 'es'; }
    return '';
}

/**
 * Final HTML safety net covering rendered content and SEO/schema summaries.
 */
function mdo_mc_filter_home_html( string $html, string $lang ): string {
    return mdo_mc_replace_home_claims( $html, $lang );
}

add_action( 'template_redirect', static function() {
    if ( is_admin() || wp_doing_ajax() || is_feed() ) { return; }
    $lang = mdo_mc_home_request_language();
    if ( '' === $lang ) { return; }
    ob_start( static function( $html ) use ( $lang ) {
        return mdo_mc_filter_home_html( (string) $html, $lang );
    } );
}, -1000 );
