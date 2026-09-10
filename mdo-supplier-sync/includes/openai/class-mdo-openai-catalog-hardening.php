<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Resolve legacy/persisted OpenAI IDs safely before onboarding.
 *
 * Some WooCommerce SKUs are duplicated in the live catalog. A duplicated SKU
 * must never become an OpenAI item_id. Existing duplicated OpenAI IDs from
 * pre-onboarding QA are migrated deterministically to the WooCommerce fallback
 * IDs. No feed has been delivered externally yet, so this avoids publishing
 * ambiguous identifiers while preserving stable IDs from this point onward.
 */
function mdo_openai_harden_item_id_20260910( $candidate, $product ): string {
    if ( ! $product instanceof WC_Product ) {
        return mdo_openai_clean_text_20260909( $candidate, 120 );
    }

    $candidate = mdo_openai_clean_text_20260909( $candidate, 120 );
    if ( '' === $candidate ) {
        return $candidate;
    }

    global $wpdb;
    $product_id = (int) $product->get_id();

    $sku_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s",
            $candidate
        )
    );

    $openai_other_count = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_mdo_openai_item_id' AND meta_value = %s AND post_id <> %d",
            $candidate,
            $product_id
        )
    );

    if ( $sku_count <= 1 && 0 === $openai_other_count ) {
        return $candidate;
    }

    $fallback = $product instanceof WC_Product_Variation
        ? 'wc-' . (int) $product->get_parent_id() . '-v' . $product_id
        : 'wc-' . $product_id;

    $fallback = mdo_openai_clean_text_20260909( $fallback, 120 );
    if ( '' !== $fallback ) {
        update_post_meta( $product_id, '_mdo_openai_item_id', $fallback );
    }

    return $fallback;
}
add_filter( 'mdo_openai_item_id', 'mdo_openai_harden_item_id_20260910', PHP_INT_MAX, 2 );

/**
 * Convert an actual WordPress attachment URL into a strict HTTPS URL that PHP's
 * FILTER_VALIDATE_URL accepts. This only normalizes an existing URL; it never
 * invents a product image.
 */
function mdo_openai_normalize_attachment_url_20260910( $url ): string {
    $url = trim( (string) $url );
    if ( '' === $url ) {
        return '';
    }

    $parts = wp_parse_url( $url );
    if ( ! is_array( $parts ) || empty( $parts['host'] ) ) {
        return '';
    }

    $home = wp_parse_url( home_url( '/' ) );
    $home_host = is_array( $home ) ? strtolower( (string) ( $home['host'] ?? '' ) ) : '';
    $host = strtolower( (string) $parts['host'] );

    $scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
    if ( 'http' === $scheme && '' !== $home_host && $host === $home_host ) {
        $scheme = 'https';
    }
    if ( 'https' !== $scheme ) {
        return '';
    }

    $path = (string) ( $parts['path'] ?? '' );
    if ( '' !== $path ) {
        $segments = explode( '/', $path );
        foreach ( $segments as &$segment ) {
            if ( '' === $segment ) { continue; }
            $segment = rawurlencode( rawurldecode( $segment ) );
        }
        unset( $segment );
        $path = implode( '/', $segments );
    }

    $normalized = 'https://' . $parts['host'];
    if ( isset( $parts['port'] ) ) { $normalized .= ':' . (int) $parts['port']; }
    $normalized .= $path;
    if ( isset( $parts['query'] ) && '' !== (string) $parts['query'] ) { $normalized .= '?' . (string) $parts['query']; }

    $normalized = esc_url_raw( $normalized );
    return filter_var( $normalized, FILTER_VALIDATE_URL ) ? $normalized : '';
}

/**
 * Use only real images already attached to the product/variation. If the main
 * image is missing, the first gallery image is a legitimate product-image
 * fallback. Variations may inherit parent images, matching WooCommerce's own
 * product model.
 */
function mdo_openai_harden_native_record_images_20260910( array $record, $product, array $settings ): array {
    if ( ! $product instanceof WC_Product ) {
        return $record;
    }

    $parent = null;
    if ( $product instanceof WC_Product_Variation ) {
        $parent = wc_get_product( $product->get_parent_id() );
        if ( ! $parent instanceof WC_Product ) { $parent = null; }
    }

    $candidate_urls = array();
    if ( ! empty( $record['image_url'] ) ) { $candidate_urls[] = (string) $record['image_url']; }

    $attachment_ids = array();
    $attachment_ids[] = (int) $product->get_image_id();
    $attachment_ids = array_merge( $attachment_ids, array_map( 'absint', (array) $product->get_gallery_image_ids() ) );
    if ( $parent instanceof WC_Product ) {
        $attachment_ids[] = (int) $parent->get_image_id();
        $attachment_ids = array_merge( $attachment_ids, array_map( 'absint', (array) $parent->get_gallery_image_ids() ) );
    }

    foreach ( array_values( array_unique( array_filter( $attachment_ids ) ) ) as $attachment_id ) {
        $url = wp_get_attachment_image_url( $attachment_id, 'full' );
        if ( ! is_string( $url ) || '' === $url ) { $url = wp_get_attachment_url( $attachment_id ); }
        if ( is_string( $url ) && '' !== $url ) { $candidate_urls[] = $url; }
    }

    $main = '';
    $additional = array();
    foreach ( $candidate_urls as $candidate_url ) {
        $normalized = mdo_openai_normalize_attachment_url_20260910( $candidate_url );
        if ( '' === $normalized ) { continue; }
        if ( '' === $main ) { $main = $normalized; }
        elseif ( $normalized !== $main ) { $additional[] = $normalized; }
    }

    foreach ( (array) ( $record['additional_image_urls'] ?? array() ) as $candidate_url ) {
        $normalized = mdo_openai_normalize_attachment_url_20260910( $candidate_url );
        if ( '' !== $normalized && $normalized !== $main ) { $additional[] = $normalized; }
    }

    if ( '' !== $main ) {
        $record['image_url'] = $main;
    }
    $additional = array_values( array_unique( $additional ) );
    if ( $additional ) { $record['additional_image_urls'] = array_slice( $additional, 0, 10 ); }
    elseif ( isset( $record['additional_image_urls'] ) ) { unset( $record['additional_image_urls'] ); }

    return $record;
}
add_filter( 'mdo_openai_native_record', 'mdo_openai_harden_native_record_images_20260910', PHP_INT_MAX, 3 );
