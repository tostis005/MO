<?php
/** Apply two user-approved blog featured images with duplicate preflight and image SEO metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

global $wpdb;

$items = array(
    array(
        'selection' => '1A',
        'slug' => 'peso-neto-vs-peso-escurrido-conserva-que-significa',
        'source_key' => 'wikimedia:Canned_white_beans_opened_and_drained',
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/89/Canned_white_beans%2C_opened_and_drained.jpg',
        'filename' => 'peso-escurrido-alubias-conserva-father-jack.jpg',
        'title' => 'Alubias blancas de conserva abiertas y escurridas',
        'alt' => 'Alubias blancas de conserva en una lata abierta después de escurrir el líquido',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Canned_white_beans,_opened_and_drained.jpg',
        'photographer' => 'Father.Jack',
        'license' => 'CC BY 2.0',
        'license_url' => 'https://creativecommons.org/licenses/by/2.0/',
    ),
    array(
        'selection' => '2C',
        'slug' => 'se-pueden-congelar-legumbres-cocidas-como-hacerlo-textura',
        'source_key' => 'pexels:1640771',
        'image_url' => 'https://images.pexels.com/photos/1640771/pexels-photo-1640771.jpeg',
        'filename' => 'legumbres-cocidas-recipientes-ella-olsson.jpg',
        'title' => 'Legumbres cocidas preparadas en recipientes',
        'alt' => 'Garbanzos y otras legumbres cocidas repartidas en recipientes de comida preparada',
        'source_page' => 'https://www.pexels.com/photo/variety-of-dishes-1640771/',
        'photographer' => 'Ella Olsson',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
);

$report = array(
    'release' => '20260828-featured-round6',
    'duplicate_preflight' => array(),
    'posts' => array(),
);

// Check every source before modifying anything. Abort if the same source is already used by another post.
foreach ( $items as $idx => $item ) {
    $post = get_page_by_path( $item['slug'], OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) {
        throw new RuntimeException( 'Post not found: ' . $item['slug'] );
    }
    $post_id = (int) $post->ID;
    $items[$idx]['post_id'] = $post_id;

    $candidate_ids = array();
    $source_matches = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_mdo_source_page',
        'meta_value' => $item['source_page'],
    ));
    foreach ( $source_matches as $id ) { $candidate_ids[] = (int) $id; }

    $key_matches = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_mdo_source_key',
        'meta_value' => $item['source_key'],
    ));
    foreach ( $key_matches as $id ) { $candidate_ids[] = (int) $id; }

    $candidate_ids = array_values( array_unique( array_filter( $candidate_ids ) ) );
    $reusable_attachment = 0;
    $conflicts = array();

    foreach ( $candidate_ids as $attachment_id ) {
        $used_by_thumbnail = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_thumbnail_id' AND meta_value=%s AND post_id<>%d",
            (string) $attachment_id,
            $post_id
        ) );
        $used_by_approved = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_emdo_editorial_image_approved_id' AND meta_value=%s AND post_id<>%d",
            (string) $attachment_id,
            $post_id
        ) );
        $used_elsewhere = array_values( array_unique( array_map( 'intval', array_merge( $used_by_thumbnail, $used_by_approved ) ) ) );
        if ( ! empty( $used_elsewhere ) ) {
            $conflicts[] = array('attachment_id' => $attachment_id, 'used_by_posts' => $used_elsewhere);
        } else {
            $reusable_attachment = $attachment_id;
        }
    }

    if ( ! empty( $conflicts ) ) {
        throw new RuntimeException( 'Duplicate image source already used by another post for ' . $item['source_key'] . ': ' . wp_json_encode( $conflicts ) );
    }

    $items[$idx]['reusable_attachment'] = $reusable_attachment;
    $report['duplicate_preflight'][] = array(
        'slug' => $item['slug'],
        'source_key' => $item['source_key'],
        'existing_attachment_candidates' => $candidate_ids,
        'reusable_attachment' => $reusable_attachment,
        'conflicts' => array(),
    );
}

foreach ( $items as $item ) {
    $post_id = (int) $item['post_id'];
    $attachment_id = (int) $item['reusable_attachment'];
    $reused_existing = $attachment_id > 0;

    if ( ! $attachment_id ) {
        $tmp = download_url( $item['image_url'], 120 );
        if ( is_wp_error( $tmp ) ) {
            throw new RuntimeException( 'Download failed for ' . $item['slug'] . ': ' . $tmp->get_error_message() );
        }
        $file = array('name' => $item['filename'], 'tmp_name' => $tmp);
        $attachment_id = media_handle_sideload( $file, $post_id, $item['title'] );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            throw new RuntimeException( 'Media import failed for ' . $item['slug'] . ': ' . $attachment_id->get_error_message() );
        }
        $attachment_id = (int) $attachment_id;
    }

    wp_update_post(array('ID' => $attachment_id, 'post_title' => $item['title']));
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item['alt'] );
    update_post_meta( $attachment_id, '_mdo_source_url', $item['image_url'] );
    update_post_meta( $attachment_id, '_mdo_source_page', $item['source_page'] );
    update_post_meta( $attachment_id, '_mdo_source_key', $item['source_key'] );
    update_post_meta( $attachment_id, '_mdo_photographer', $item['photographer'] );
    update_post_meta( $attachment_id, '_mdo_license', $item['license'] );
    update_post_meta( $attachment_id, '_mdo_license_url', $item['license_url'] );

    set_post_thumbnail( $post_id, $attachment_id );
    update_post_meta( $post_id, '_emdo_editorial_image_approved_id', $attachment_id );
    delete_post_meta( $post_id, '_emdo_editorial_image_approved_pexels_id' );
    update_post_meta( $post_id, '_emdo_editorial_cover_brand_safe', '1' );
    delete_post_meta( $post_id, '_emdo_uses_default_featured' );
    delete_post_meta( $post_id, '_emdo_default_featured_hash' );
    delete_post_meta( $post_id, '_emdo_default_featured_updated_at' );
    delete_post_meta( $post_id, '_emdo_editorial_cover_asset_id' );
    clean_post_cache( $post_id );

    $thumbnail_id = (int) get_post_thumbnail_id( $post_id );
    $approved_id = (int) get_post_meta( $post_id, '_emdo_editorial_image_approved_id', true );
    $uses_default = (string) get_post_meta( $post_id, '_emdo_uses_default_featured', true );
    $cover_asset = (string) get_post_meta( $post_id, '_emdo_editorial_cover_asset_id', true );
    $attachment_url = (string) wp_get_attachment_url( $attachment_id );
    $attachment_alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
    $attachment_title = (string) get_the_title( $attachment_id );
    $source_page = (string) get_post_meta( $attachment_id, '_mdo_source_page', true );
    $photographer = (string) get_post_meta( $attachment_id, '_mdo_photographer', true );
    $license = (string) get_post_meta( $attachment_id, '_mdo_license', true );
    $expected_stem = pathinfo( $item['filename'], PATHINFO_FILENAME );
    $actual_basename = basename( (string) wp_parse_url( $attachment_url, PHP_URL_PATH ) );

    if (
        $thumbnail_id !== $attachment_id ||
        $approved_id !== $attachment_id ||
        $uses_default !== '' ||
        $cover_asset !== '' ||
        $attachment_url === '' ||
        $attachment_alt !== $item['alt'] ||
        $attachment_title !== $item['title'] ||
        $source_page !== $item['source_page'] ||
        $photographer !== $item['photographer'] ||
        $license !== $item['license'] ||
        strpos( $actual_basename, $expected_stem ) === false
    ) {
        throw new RuntimeException( 'Featured image/SEO verification failed for: ' . $item['slug'] );
    }

    $report['posts'][] = array(
        'selection' => $item['selection'],
        'slug' => $item['slug'],
        'post_id' => $post_id,
        'attachment_id' => $attachment_id,
        'thumbnail_id' => $thumbnail_id,
        'approved_id' => $approved_id,
        'uses_default' => $uses_default,
        'cover_asset' => $cover_asset,
        'attachment_url' => $attachment_url,
        'filename' => $actual_basename,
        'title' => $attachment_title,
        'alt' => $attachment_alt,
        'source_page' => $source_page,
        'photographer' => $photographer,
        'license' => $license,
        'source_key' => $item['source_key'],
        'reused_existing' => $reused_existing,
        'permalink' => get_permalink( $post_id ),
    );
}

if ( count( $report['posts'] ) !== 2 || count( $report['duplicate_preflight'] ) !== 2 ) {
    throw new RuntimeException( 'Expected two updated posts and two duplicate checks.' );
}

wp_cache_flush();
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
