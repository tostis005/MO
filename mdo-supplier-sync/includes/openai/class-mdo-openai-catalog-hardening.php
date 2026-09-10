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
 * invents or substitutes a product image.
 */
function mdo_openai_normalize_attachment_url_20260910( $url ): string {
    $url = trim( (string) $url );
    if ( '' === $url ) { return ''; }

    $parts = wp_parse_url( $url );
    if ( ! is_array( $parts ) || empty( $parts['host'] ) ) { return ''; }

    $home = wp_parse_url( home_url( '/' ) );
    $home_host = is_array( $home ) ? strtolower( (string) ( $home['host'] ?? '' ) ) : '';
    $host = strtolower( (string) $parts['host'] );
    $scheme = strtolower( (string) ( $parts['scheme'] ?? '' ) );
    if ( 'http' === $scheme && '' !== $home_host && $host === $home_host ) { $scheme = 'https'; }
    if ( 'https' !== $scheme ) { return ''; }

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
 * The OpenAI main image must be the product/variation featured image, with the
 * normal WooCommerce variation -> parent featured-image inheritance only.
 * Gallery images are never promoted to image_url.
 */
function mdo_openai_canonical_image_url_20260910( WC_Product $product ): string {
    $image_id = (int) $product->get_image_id();
    if ( $image_id <= 0 && $product instanceof WC_Product_Variation ) {
        $parent = wc_get_product( (int) $product->get_parent_id() );
        if ( $parent instanceof WC_Product ) { $image_id = (int) $parent->get_image_id(); }
    }
    if ( $image_id <= 0 ) { return ''; }

    $url = wp_get_attachment_image_url( $image_id, 'full' );
    if ( ! is_string( $url ) || '' === $url ) { $url = wp_get_attachment_url( $image_id ); }
    return mdo_openai_normalize_attachment_url_20260910( $url );
}

/**
 * WCFM can leave a vendor's products as published WordPress posts while the
 * store itself is disabled. Such products must never enter an external feed.
 */
function mdo_openai_vendor_is_public_20260910( int $vendor_id ): bool {
    if ( $vendor_id <= 0 ) { return false; }

    $user = get_userdata( $vendor_id );
    if ( ! $user instanceof WP_User ) { return false; }
    if ( in_array( 'disable_vendor', (array) $user->roles, true ) ) { return false; }

    if ( function_exists( 'mdo_gmf_vendor_is_active_v1' ) && ! mdo_gmf_vendor_is_active_v1( $vendor_id ) ) {
        return false;
    }

    return (bool) apply_filters( 'mdo_openai_vendor_is_public', true, $vendor_id, $user );
}

/**
 * Keep only products that an anonymous storefront visitor can legitimately be
 * offered, and silently omit rows that cannot satisfy OpenAI's mandatory image.
 */
function mdo_openai_harden_product_exclusion_20260910( bool $excluded, WC_Product $product, array $settings ): bool {
    if ( $excluded ) { return true; }

    list( $parent_id, $parent, $post ) = mdo_openai_parent_context_20260909( $product );
    if ( ! $post instanceof WP_Post || ! $parent instanceof WC_Product ) { return true; }
    if ( 'publish' !== (string) $post->post_status || '' !== (string) $post->post_password ) { return true; }
    if ( 'hidden' === (string) $parent->get_catalog_visibility() || ! $parent->is_visible() ) { return true; }

    if ( $product instanceof WC_Product_Variation && 'publish' !== (string) $product->get_status() ) { return true; }
    if ( ! $product->is_purchasable() ) { return true; }

    $vendor_id = (int) $post->post_author;
    if ( ! mdo_openai_vendor_is_public_20260910( $vendor_id ) ) { return true; }

    if ( '' === mdo_openai_canonical_image_url_20260910( $product ) ) { return true; }

    return false;
}
add_filter( 'mdo_openai_product_excluded', 'mdo_openai_harden_product_exclusion_20260910', PHP_INT_MAX, 3 );

/**
 * Mirror the same anonymous/public checks in is_eligible_search so a future
 * refactor cannot accidentally mark an excluded storefront item as eligible.
 */
function mdo_openai_harden_search_eligibility_20260910( bool $eligible, WC_Product $product, array $settings ): bool {
    if ( ! $eligible ) { return false; }
    list( , $parent, $post ) = mdo_openai_parent_context_20260909( $product );
    if ( ! $post instanceof WP_Post || ! $parent instanceof WC_Product ) { return false; }
    if ( 'publish' !== (string) $post->post_status || '' !== (string) $post->post_password ) { return false; }
    if ( 'hidden' === (string) $parent->get_catalog_visibility() || ! $parent->is_visible() ) { return false; }
    if ( $product instanceof WC_Product_Variation && 'publish' !== (string) $product->get_status() ) { return false; }
    if ( ! mdo_openai_vendor_is_public_20260910( (int) $post->post_author ) ) { return false; }
    if ( '' === mdo_openai_canonical_image_url_20260910( $product ) ) { return false; }
    return true;
}
add_filter( 'mdo_openai_is_eligible_search', 'mdo_openai_harden_search_eligibility_20260910', PHP_INT_MAX, 3 );

/**
 * Normalize only the image URLs already selected by core. Never use gallery
 * images as a replacement for a missing featured image.
 */
function mdo_openai_harden_native_record_images_20260910( array $record, $product, array $settings ): array {
    if ( ! $product instanceof WC_Product ) { return $record; }

    $main = mdo_openai_normalize_attachment_url_20260910( $record['image_url'] ?? '' );
    $record['image_url'] = $main;

    $additional = array();
    foreach ( (array) ( $record['additional_image_urls'] ?? array() ) as $candidate_url ) {
        $normalized = mdo_openai_normalize_attachment_url_20260910( $candidate_url );
        if ( '' !== $normalized && $normalized !== $main ) { $additional[] = $normalized; }
    }
    $additional = array_values( array_unique( $additional ) );
    if ( $additional ) { $record['additional_image_urls'] = array_slice( $additional, 0, 10 ); }
    elseif ( isset( $record['additional_image_urls'] ) ) { unset( $record['additional_image_urls'] ); }

    return $record;
}
add_filter( 'mdo_openai_native_record', 'mdo_openai_harden_native_record_images_20260910', PHP_INT_MAX, 3 );
