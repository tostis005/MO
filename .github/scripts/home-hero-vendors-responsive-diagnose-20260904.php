<?php
if ( ! defined( 'ABSPATH' ) ) { exit( "WordPress not loaded\n" ); }

if ( ! function_exists( 'elmercado_home_active_vendor_ids_010244' ) || ! function_exists( 'elmercado_home_vendor_banner_010244' ) ) {
    fwrite( STDERR, "Home vendor helpers unavailable\n" );
    exit( 2 );
}

echo "=== HOME HERO ACTIVE VENDORS ===\n";
$ids = elmercado_home_active_vendor_ids_010244( 5 );
$index = 0;
foreach ( $ids as $vendor_id ) {
    if ( $index >= 5 ) { break; }
    $name = trim( elmercado_home_vendor_name_010244( (int) $vendor_id ) );
    $url  = trim( elmercado_home_vendor_url_010244( (int) $vendor_id ) );
    if ( '' === $name || '' === $url ) { continue; }
    $index++;
    $banner = elmercado_home_vendor_banner_010244( (int) $vendor_id );
    $attachment_id = $banner ? (int) attachment_url_to_postid( $banner ) : 0;
    $meta = $attachment_id ? wp_get_attachment_metadata( $attachment_id ) : array();
    $file = $attachment_id ? get_attached_file( $attachment_id ) : '';
    echo wp_json_encode(array(
        'card' => $index,
        'vendor_id' => (int) $vendor_id,
        'name' => $name,
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
