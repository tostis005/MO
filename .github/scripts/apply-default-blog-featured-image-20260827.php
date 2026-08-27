<?php
/** Apply or replace the central provisional featured image for editorial posts. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$image_file = (string) getenv( 'EMDO_DEFAULT_BLOG_IMAGE_FILE' );
if ( '' === $image_file || ! is_file( $image_file ) ) {
    throw new RuntimeException( 'EMDO_DEFAULT_BLOG_IMAGE_FILE missing.' );
}

$info = @getimagesize( $image_file );
if ( ! is_array( $info ) ) { throw new RuntimeException( 'Default image is not readable.' ); }
$width = (int) $info[0];
$height = (int) $info[1];
if ( $width < 1200 || $height < 700 ) {
    throw new RuntimeException( 'Default image too small: ' . $width . 'x' . $height );
}
$hash = hash_file( 'sha256', $image_file );
if ( ! is_string( $hash ) || '' === $hash ) { throw new RuntimeException( 'Could not hash default image.' ); }

$existing = get_posts(array(
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_default_blog_image_hash',
    'meta_value'     => $hash,
));
$attachment_id = ! empty( $existing ) ? (int) $existing[0] : 0;

if ( $attachment_id <= 0 ) {
    $tmp = wp_tempnam( 'el-mercado-de-origen-blog-provisional.webp' );
    if ( ! $tmp || ! copy( $image_file, $tmp ) ) { throw new RuntimeException( 'Could not stage default image.' ); }
    $file = array(
        'name'     => 'el-mercado-de-origen-blog-provisional-' . substr( $hash, 0, 12 ) . '.webp',
        'tmp_name' => $tmp,
    );
    $result = media_handle_sideload( $file, 0, 'Imagen provisional del blog de El Mercado de Origen' );
    if ( is_wp_error( $result ) ) {
        @unlink( $tmp );
        throw new RuntimeException( 'Default image import failed: ' . $result->get_error_message() );
    }
    $attachment_id = (int) $result;
    wp_update_post(array(
        'ID'           => $attachment_id,
        'post_title'   => 'Imagen provisional del blog de El Mercado de Origen',
        'post_excerpt' => '',
        'post_content' => '',
    ));
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Imagen provisional del blog de El Mercado de Origen' );
    update_post_meta( $attachment_id, '_en_US_attachment_alt', 'Temporary editorial image for El Mercado de Origen blog' );
    update_post_meta( $attachment_id, '_emdo_default_blog_image', '1' );
    update_post_meta( $attachment_id, '_emdo_default_blog_image_hash', $hash );
    update_post_meta( $attachment_id, '_emdo_default_blog_image_created_at', gmdate( 'c' ) );
}

update_option( 'emdo_default_blog_featured_attachment_id', $attachment_id, false );
update_option( 'emdo_default_blog_featured_hash', $hash, false );

$seed_keys = array(
    'ham-vs-shoulder-buying-guide',
    'iberian-vs-serrano-ham-guide',
    'iberian-lomo-vs-lomito-guide',
    'chorizo-vs-salchichon-guide',
    'whole-vs-boneless-iberian-ham-guide',
);
$seed_ids = array();
foreach ( $seed_keys as $key ) {
    $ids = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 2,
        'fields'         => 'ids',
        'meta_key'       => '_emdo_authority_key',
        'meta_value'     => $key,
    ));
    if ( 1 !== count( $ids ) ) { throw new RuntimeException( 'Seed post not unique: ' . $key ); }
    $post_id = (int) $ids[0];
    $seed_ids[] = $post_id;
    update_post_meta( $post_id, '_emdo_uses_default_featured', '1' );
}

$marked_ids = get_posts(array(
    'post_type'      => 'post',
    'post_status'    => array('publish','draft','pending','future','private'),
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_uses_default_featured',
    'meta_value'     => '1',
));
$target_ids = array_values( array_unique( array_map( 'intval', array_merge( $seed_ids, $marked_ids ) ) ) );
if ( count( $target_ids ) < 5 ) { throw new RuntimeException( 'Expected at least five provisional-image posts.' ); }

$posts = array();
foreach ( $target_ids as $post_id ) {
    update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
    update_post_meta( $post_id, '_emdo_editorial_image_approved_id', $attachment_id );
    delete_post_meta( $post_id, '_emdo_editorial_image_approved_pexels_id' );
    update_post_meta( $post_id, '_emdo_editorial_cover_asset_id', 'emdo-blog-default-featured' );
    update_post_meta( $post_id, '_emdo_editorial_cover_brand_safe', '1' );
    update_post_meta( $post_id, '_emdo_default_featured_hash', $hash );
    update_post_meta( $post_id, '_emdo_default_featured_updated_at', gmdate( 'c' ) );
    clean_post_cache( $post_id );
    if ( (int) get_post_thumbnail_id( $post_id ) !== $attachment_id ) {
        throw new RuntimeException( 'Featured image did not persist for post ' . $post_id );
    }
    if ( '1' !== (string) get_post_meta( $post_id, '_emdo_uses_default_featured', true ) ) {
        throw new RuntimeException( 'Default-image marker disappeared for post ' . $post_id );
    }
    $posts[] = array(
        'id'      => $post_id,
        'slug'    => (string) get_post_field( 'post_name', $post_id ),
        'status'  => (string) get_post_status( $post_id ),
        'image_id'=> $attachment_id,
    );
}

$meta = wp_get_attachment_metadata( $attachment_id );
$report = array(
    'default_attachment_id' => $attachment_id,
    'default_hash'          => $hash,
    'image_width'           => (int) ( $meta['width'] ?? $width ),
    'image_height'          => (int) ( $meta['height'] ?? $height ),
    'image_url'             => (string) wp_get_attachment_url( $attachment_id ),
    'posts'                 => $posts,
);
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
