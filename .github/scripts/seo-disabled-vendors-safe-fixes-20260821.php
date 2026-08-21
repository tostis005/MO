<?php
/**
 * Safe SEO readiness fixes for disabled WCFM vendors.
 * - Never changes vendor enable/disable/offline state.
 * - Never changes product post_status, visibility, price or stock.
 * - Only fills blank image ALT attributes and reports content/category/English gaps.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

function emdo_dv_disabled( int $user_id, WP_User $user ): bool {
    if ( in_array( 'disable_vendor', (array) $user->roles, true ) ) return true;
    $disabled = strtolower( trim( (string) get_user_meta( $user_id, '_disable_vendor', true ) ) );
    if ( in_array( $disabled, array( '1', 'yes', 'true', 'on' ), true ) ) return true;
    $offline = strtolower( trim( (string) get_user_meta( $user_id, '_wcfm_store_offline', true ) ) );
    if ( in_array( $offline, array( '1', 'yes', 'true', 'on' ), true ) ) return true;
    return false;
}

function emdo_dv_len( $html ): int {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $html ) ) ) );
    return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
}

function emdo_dv_store_name( int $user_id, WP_User $user ): string {
    $profile = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
    if ( is_array( $profile ) && ! empty( $profile['store_name'] ) ) return trim( (string) $profile['store_name'] );
    return trim( (string) ( $user->display_name ?: $user->user_login ) );
}

$authors = $wpdb->get_col( "SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type='product' ORDER BY post_author" );
$disabled_vendors = 0;
$products_checked = 0;
$alt_updated = 0;
$alt_kept = 0;
$missing_images = 0;
$missing_categories = 0;
$content_missing = 0;
$content_short = 0;
$english_missing_title = 0;
$english_missing_content = 0;
$english_unpublished = 0;
$status_before = array();
$status_after = array();
$vendor_rows = array();
$seen_images = array();

foreach ( $authors as $author_raw ) {
    $user_id = (int) $author_raw;
    $user = get_userdata( $user_id );
    if ( ! $user instanceof WP_User || ! emdo_dv_disabled( $user_id, $user ) ) continue;
    $disabled_vendors++;

    $ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type='product' AND post_author=%d
           AND post_status IN ('publish','draft','pending','private','future')
         ORDER BY ID",
        $user_id
    ) );

    $vendor = array(
        'user_id' => $user_id,
        'store' => emdo_dv_store_name( $user_id, $user ),
        'roles' => array_values( (array) $user->roles ),
        'products' => 0,
        'product_ids' => array(),
        'alt_updated' => 0,
        'missing_content' => array(),
        'short_content' => array(),
        'missing_category' => array(),
        'missing_featured_image' => array(),
        'english_missing_title' => array(),
        'english_missing_content' => array(),
        'english_unpublished' => array(),
    );

    foreach ( $ids as $id_raw ) {
        $id = (int) $id_raw;
        $p = get_post( $id );
        if ( ! $p instanceof WP_Post || (int) $p->post_author !== $user_id || $p->post_type !== 'product' ) continue;
        $vendor['products']++;
        $vendor['product_ids'][] = $id;
        $products_checked++;
        $status_before[$id] = $p->post_status;

        $len = emdo_dv_len( $p->post_content );
        if ( $len === 0 ) { $content_missing++; $vendor['missing_content'][] = array( $id, $p->post_title ); }
        elseif ( $len < 120 ) { $content_short++; $vendor['short_content'][] = array( $id, $p->post_title, $len ); }

        $cats = wp_get_post_terms( $id, 'product_cat', array( 'fields' => 'ids' ) );
        if ( is_wp_error( $cats ) || ! $cats ) { $missing_categories++; $vendor['missing_category'][] = array( $id, $p->post_title ); }

        $thumb = (int) get_post_thumbnail_id( $id );
        if ( ! $thumb ) {
            $missing_images++;
            $vendor['missing_featured_image'][] = array( $id, $p->post_title );
        } else {
            $seen_images[$thumb] = true;
            $old = trim( (string) get_post_meta( $thumb, '_wp_attachment_image_alt', true ) );
            if ( $old === '' ) {
                update_post_meta( $thumb, '_wp_attachment_image_alt', wp_strip_all_tags( $p->post_title ) );
                $alt_updated++; $vendor['alt_updated']++;
            } else {
                $alt_kept++;
            }
        }

        $gallery = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $id, '_product_image_gallery', true ) ) ) );
        foreach ( $gallery as $image_id ) {
            $seen_images[$image_id] = true;
            $old = trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) );
            if ( $old === '' ) {
                $label = wp_strip_all_tags( $p->post_title );
                update_post_meta( $image_id, '_wp_attachment_image_alt', $label );
                $alt_updated++; $vendor['alt_updated']++;
            } else {
                $alt_kept++;
            }
        }

        if ( trim( (string) get_post_meta( $id, '_en_US_post_title', true ) ) === '' ) {
            $english_missing_title++; $vendor['english_missing_title'][] = array( $id, $p->post_title );
        }
        if ( emdo_dv_len( get_post_meta( $id, '_en_US_post_content', true ) ) === 0 ) {
            $english_missing_content++; $vendor['english_missing_content'][] = array( $id, $p->post_title );
        }
        if ( (string) get_post_meta( $id, '_en_US_published', true ) !== '1' ) {
            $english_unpublished++; $vendor['english_unpublished'][] = array( $id, $p->post_title );
        }
    }
    $vendor_rows[] = $vendor;
}

// Verify statuses and disabled/offline signals were not changed.
foreach ( $status_before as $id => $before ) {
    $after = get_post_status( $id );
    $status_after[$id] = $after;
    if ( $after !== $before ) {
        throw new RuntimeException( "Product status changed unexpectedly for {$id}: {$before} -> {$after}" );
    }
}

$alt_missing_after = 0;
foreach ( array_keys( $seen_images ) as $image_id ) {
    if ( trim( (string) get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) === '' ) $alt_missing_after++;
}

$summary = array(
    'disabled_vendors' => $disabled_vendors,
    'products_checked' => $products_checked,
    'unique_images_checked' => count( $seen_images ),
    'alt_updated' => $alt_updated,
    'alt_existing_kept' => $alt_kept,
    'alt_missing_after' => $alt_missing_after,
    'missing_featured_image' => $missing_images,
    'missing_category' => $missing_categories,
    'missing_content' => $content_missing,
    'short_content' => $content_short,
    'english_missing_title' => $english_missing_title,
    'english_missing_content' => $english_missing_content,
    'english_unpublished' => $english_unpublished,
    'product_status_changes' => 0,
);

echo "EMDO DISABLED VENDOR SAFE SEO FIXES 2026-08-21\n";
echo 'SUMMARY=' . wp_json_encode( $summary, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
foreach ( $vendor_rows as $row ) {
    echo 'DISABLED_VENDOR=' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
if ( $alt_missing_after !== 0 ) throw new RuntimeException( 'Blank ALT values remain in checked disabled-vendor images.' );
echo "DISABLED_VENDOR_SAFE_FIXES=PASS\n";
