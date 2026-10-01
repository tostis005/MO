<?php
if ( ! defined( 'ABSPATH' ) ) { exit( "WordPress not loaded\n" ); }

if ( ! function_exists( 'elmercado_home_active_vendor_ids_010244' ) || ! function_exists( 'elmercado_home_vendor_banner_010244' ) ) {
    fwrite( STDERR, "Home vendor helpers unavailable\n" );
    exit( 2 );
}

echo "=== HOME HERO ACTIVE VENDORS ===\n";
$ids = elmercado_home_active_vendor_ids_010244( 50 );
$index = 0;
foreach ( $ids as $vendor_id ) {
    $name = trim( elmercado_home_vendor_name_010244( (int) $vendor_id ) );
    $url  = trim( elmercado_home_vendor_url_010244( (int) $vendor_id ) );
    if ( '' === $name || '' === $url ) { continue; }
    $index++;
    $banner = elmercado_home_vendor_banner_010244( (int) $vendor_id );
    $user = get_userdata( (int) $vendor_id );
    $disable_meta = get_user_meta( (int) $vendor_id, '_disable_vendor', true );
    $offline_meta = get_user_meta( (int) $vendor_id, '_wcfm_store_offline', true );
    $hard_disabled = function_exists( 'elmercado_wcfm_vendor_is_hard_disabled_010210' )
        ? elmercado_wcfm_vendor_is_hard_disabled_010210( (int) $vendor_id )
        : null;
    $offline = function_exists( 'elmercado_wcfm_vendor_is_offline_010210' )
        ? elmercado_wcfm_vendor_is_offline_010210( (int) $vendor_id )
        : null;
    $attachment_id = $banner ? (int) attachment_url_to_postid( $banner ) : 0;
    $meta = $attachment_id ? wp_get_attachment_metadata( $attachment_id ) : array();
    $file = $attachment_id ? get_attached_file( $attachment_id ) : '';
    echo wp_json_encode(array(
        'card' => $index,
        'vendor_id' => (int) $vendor_id,
        'name' => $name,
        'roles' => $user instanceof WP_User ? array_values( $user->roles ) : array(),
        'disable_meta' => $disable_meta,
        'offline_meta' => $offline_meta,
        'hard_disabled' => $hard_disabled,
        'offline' => $offline,
        'banner' => $banner,
        'attachment_id' => $attachment_id,
        'width' => isset($meta['width']) ? (int)$meta['width'] : 0,
        'height' => isset($meta['height']) ? (int)$meta['height'] : 0,
        'file' => $file ? basename($file) : '',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
}

if ( function_exists( 'elmercado_render_home_vendor_visual_010244' ) ) {
    echo "=== HERO VISUAL HTML ===\n";
    echo elmercado_render_home_vendor_visual_010244() . "\n";
}


echo "=== ALL WCFM VENDOR STATES ===\n";
$vendor_ids = get_users( array( 'role__in' => array( 'wcfm_vendor', 'disable_vendor' ), 'fields' => 'ids' ) );
foreach ( array_map( 'intval', (array) $vendor_ids ) as $vendor_id ) {
    $user = get_userdata( $vendor_id );
    if ( ! $user instanceof WP_User ) { continue; }
    $name = function_exists( 'elmercado_home_vendor_name_010244' )
        ? trim( elmercado_home_vendor_name_010244( $vendor_id ) )
        : trim( (string) get_user_meta( $vendor_id, 'store_name', true ) );
    $published = (int) $GLOBALS['wpdb']->get_var(
        $GLOBALS['wpdb']->prepare(
            "SELECT COUNT(*) FROM {$GLOBALS['wpdb']->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",
            $vendor_id
        )
    );
    echo wp_json_encode( array(
        'vendor_id' => $vendor_id,
        'name' => $name,
        'roles' => array_values( $user->roles ),
        'disable_meta' => get_user_meta( $vendor_id, '_disable_vendor', true ),
        'offline_meta' => get_user_meta( $vendor_id, '_wcfm_store_offline', true ),
        'hard_disabled' => function_exists( 'elmercado_wcfm_vendor_is_hard_disabled_010210' )
            ? elmercado_wcfm_vendor_is_hard_disabled_010210( $vendor_id ) : null,
        'offline' => function_exists( 'elmercado_wcfm_vendor_is_offline_010210' )
            ? elmercado_wcfm_vendor_is_offline_010210( $vendor_id ) : null,
        'published_products' => $published,
        'store_url' => function_exists( 'wcfmmp_get_store_url' ) ? wcfmmp_get_store_url( $vendor_id ) : '',
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
