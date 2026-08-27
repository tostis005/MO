<?php
/** Replace the current central provisional blog featured image without touching custom covers. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$image_file = (string) getenv( 'EMDO_DEFAULT_BLOG_IMAGE_FILE' );
$expected_old_id = (int) getenv( 'EMDO_EXPECTED_OLD_DEFAULT_ATTACHMENT_ID' );
if ( '' === $image_file || ! is_file( $image_file ) ) {
    throw new RuntimeException( 'EMDO_DEFAULT_BLOG_IMAGE_FILE missing.' );
}

$info = @getimagesize( $image_file );
if ( ! is_array( $info ) ) { throw new RuntimeException( 'New default image is not readable.' ); }
$width  = (int) $info[0];
$height = (int) $info[1];
if ( $width < 1200 || $height < 675 ) {
    throw new RuntimeException( 'New default image too small: ' . $width . 'x' . $height );
}

$new_hash = hash_file( 'sha256', $image_file );
if ( ! is_string( $new_hash ) || '' === $new_hash ) { throw new RuntimeException( 'Could not hash new default image.' ); }

$current_default_id   = (int) get_option( 'emdo_default_blog_featured_attachment_id', 0 );
$current_default_hash = (string) get_option( 'emdo_default_blog_featured_hash', '' );

$existing_new = get_posts(array(
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'posts_per_page' => 1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_default_blog_image_hash',
    'meta_value'     => $new_hash,
));
$new_attachment_id = ! empty( $existing_new ) ? (int) $existing_new[0] : 0;

/* Idempotent rerun: if the central option already points at this image, only verify the marked pool. */
$already_current = $new_attachment_id > 0 && $current_default_id === $new_attachment_id && hash_equals( $new_hash, $current_default_hash );

if ( ! $already_current ) {
    if ( $current_default_id <= 0 ) { throw new RuntimeException( 'No current central default attachment is configured.' ); }
    if ( $expected_old_id > 0 && $current_default_id !== $expected_old_id ) {
        throw new RuntimeException( 'Current central default attachment changed unexpectedly: ' . $current_default_id );
    }

    $marked_ids = get_posts(array(
        'post_type'      => 'post',
        'post_status'    => array('publish','draft','pending','future','private'),
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'meta_key'       => '_emdo_uses_default_featured',
        'meta_value'     => '1',
        'orderby'        => 'ID',
        'order'          => 'ASC',
    ));
    $marked_ids = array_values( array_unique( array_map( 'intval', $marked_ids ) ) );
    if ( empty( $marked_ids ) ) { throw new RuntimeException( 'No posts are marked as using the central provisional image.' ); }

    $target_ids = array();
    $protected_ids = array();
    foreach ( $marked_ids as $post_id ) {
        if ( (int) get_post_thumbnail_id( $post_id ) === $current_default_id ) {
            $target_ids[] = $post_id;
        } else {
            /* A mismatched thumbnail is treated as custom and never overwritten. */
            $protected_ids[] = $post_id;
        }
    }
    if ( empty( $target_ids ) ) { throw new RuntimeException( 'No marked posts still use the old central attachment.' ); }

    if ( $new_attachment_id <= 0 ) {
        $tmp = wp_tempnam( 'el-mercado-de-origen-blog-provisional.webp' );
        if ( ! $tmp || ! copy( $image_file, $tmp ) ) { throw new RuntimeException( 'Could not stage new default image.' ); }
        $file = array(
            'name'     => 'el-mercado-de-origen-blog-provisional-' . substr( $new_hash, 0, 12 ) . '.webp',
            'tmp_name' => $tmp,
        );
        $result = media_handle_sideload( $file, 0, 'Imagen provisional del blog de El Mercado de Origen' );
        if ( is_wp_error( $result ) ) {
            @unlink( $tmp );
            throw new RuntimeException( 'New default image import failed: ' . $result->get_error_message() );
        }
        $new_attachment_id = (int) $result;
        wp_update_post(array(
            'ID'           => $new_attachment_id,
            'post_title'   => 'Imagen provisional del blog de El Mercado de Origen',
            'post_excerpt' => '',
            'post_content' => '',
        ));
        update_post_meta( $new_attachment_id, '_wp_attachment_image_alt', 'Imagen provisional del blog de El Mercado de Origen' );
        update_post_meta( $new_attachment_id, '_en_US_attachment_alt', 'Temporary editorial image for El Mercado de Origen blog' );
        update_post_meta( $new_attachment_id, '_emdo_default_blog_image', '1' );
        update_post_meta( $new_attachment_id, '_emdo_default_blog_image_hash', $new_hash );
        update_post_meta( $new_attachment_id, '_emdo_default_blog_image_created_at', gmdate( 'c' ) );
    }

    /* Update the central option first so the MU-plugin keeps the provisional marker during thumbnail replacement. */
    update_option( 'emdo_default_blog_featured_attachment_id', $new_attachment_id, false );
    update_option( 'emdo_default_blog_featured_hash', $new_hash, false );

    $updated_ids = array();
    foreach ( $target_ids as $post_id ) {
        update_post_meta( $post_id, '_thumbnail_id', $new_attachment_id );
        update_post_meta( $post_id, '_emdo_editorial_image_approved_id', $new_attachment_id );
        delete_post_meta( $post_id, '_emdo_editorial_image_approved_pexels_id' );
        update_post_meta( $post_id, '_emdo_editorial_cover_asset_id', 'emdo-blog-default-featured' );
        update_post_meta( $post_id, '_emdo_editorial_cover_brand_safe', '1' );
        update_post_meta( $post_id, '_emdo_default_featured_hash', $new_hash );
        update_post_meta( $post_id, '_emdo_default_featured_updated_at', gmdate( 'c' ) );
        clean_post_cache( $post_id );

        if ( (int) get_post_thumbnail_id( $post_id ) !== $new_attachment_id ) {
            throw new RuntimeException( 'Featured image did not persist for post ' . $post_id );
        }
        if ( '1' !== (string) get_post_meta( $post_id, '_emdo_uses_default_featured', true ) ) {
            throw new RuntimeException( 'Default-image marker disappeared for post ' . $post_id );
        }
        $updated_ids[] = $post_id;
    }
} else {
    $updated_ids = array();
    $protected_ids = array();
}

$marked_now = get_posts(array(
    'post_type'      => 'post',
    'post_status'    => array('publish','draft','pending','future','private'),
    'posts_per_page' => -1,
    'fields'         => 'ids',
    'meta_key'       => '_emdo_uses_default_featured',
    'meta_value'     => '1',
    'orderby'        => 'ID',
    'order'          => 'ASC',
));
$marked_now = array_values( array_unique( array_map( 'intval', $marked_now ) ) );

$posts = array();
foreach ( $marked_now as $post_id ) {
    $posts[] = array(
        'id'           => $post_id,
        'slug'         => (string) get_post_field( 'post_name', $post_id ),
        'status'       => (string) get_post_status( $post_id ),
        'thumbnail_id' => (int) get_post_thumbnail_id( $post_id ),
        'permalink'    => (string) get_permalink( $post_id ),
    );
}

foreach ( $posts as $row ) {
    if ( $row['thumbnail_id'] !== $new_attachment_id ) {
        throw new RuntimeException( 'A marked provisional post does not use the new central image: ' . $row['id'] );
    }
}

$meta = wp_get_attachment_metadata( $new_attachment_id );
$report = array(
    'old_attachment_id'     => $already_current ? null : $current_default_id,
    'new_attachment_id'     => $new_attachment_id,
    'new_hash'              => $new_hash,
    'image_width'           => (int) ( $meta['width'] ?? $width ),
    'image_height'          => (int) ( $meta['height'] ?? $height ),
    'image_url'             => (string) wp_get_attachment_url( $new_attachment_id ),
    'already_current'       => $already_current,
    'updated_ids'           => $updated_ids,
    'protected_ids'         => $protected_ids,
    'marked_posts'          => $posts,
);
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
