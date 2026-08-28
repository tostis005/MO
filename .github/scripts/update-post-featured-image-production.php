<?php
/** Production-safe featured image updater driven by EMDO_FEATURED_REQUEST JSON. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

global $wpdb;

$request_path = getenv('EMDO_FEATURED_REQUEST');
if ( ! $request_path || ! is_readable( $request_path ) ) {
    throw new RuntimeException('Missing or unreadable EMDO_FEATURED_REQUEST.');
}
$request = json_decode( file_get_contents( $request_path ), true );
if ( ! is_array( $request ) ) {
    throw new RuntimeException('Invalid featured image request JSON.');
}

$required = array('post_slug','image_url','image_filename','image_title','image_alt','source_page','photographer','license');
foreach ( $required as $key ) {
    if ( empty( $request[$key] ) || ! is_string( $request[$key] ) ) {
        throw new RuntimeException('Missing required request field: ' . $key);
    }
}

$post = get_page_by_path( $request['post_slug'], OBJECT, 'post' );
if ( ! $post instanceof WP_Post ) {
    throw new RuntimeException('Post not found: ' . $request['post_slug']);
}
$post_id = (int) $post->ID;
$source_key = isset($request['source_key']) ? (string) $request['source_key'] : '';
$license_url = isset($request['license_url']) ? (string) $request['license_url'] : '';

$candidate_ids = array();
foreach ( array('_mdo_source_page' => $request['source_page'], '_mdo_source_url' => $request['image_url']) as $meta_key => $meta_value ) {
    $ids = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => $meta_key,
        'meta_value' => $meta_value,
    ));
    foreach ( $ids as $id ) { $candidate_ids[] = (int) $id; }
}
if ( $source_key !== '' ) {
    $ids = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_mdo_source_key',
        'meta_value' => $source_key,
    ));
    foreach ( $ids as $id ) { $candidate_ids[] = (int) $id; }
}
$candidate_ids = array_values(array_unique(array_filter($candidate_ids)));

$reusable_attachment = 0;
$conflicts = array();
foreach ( $candidate_ids as $attachment_id ) {
    $used_thumbnail = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_thumbnail_id' AND meta_value=%s AND post_id<>%d",
        (string) $attachment_id,
        $post_id
    ));
    $used_approved = $wpdb->get_col($wpdb->prepare(
        "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_emdo_editorial_image_approved_id' AND meta_value=%s AND post_id<>%d",
        (string) $attachment_id,
        $post_id
    ));
    $used_elsewhere = array_values(array_unique(array_map('intval', array_merge($used_thumbnail, $used_approved))));
    if ( ! empty($used_elsewhere) ) {
        $conflicts[] = array('attachment_id' => $attachment_id, 'used_by_posts' => $used_elsewhere);
    } else {
        $reusable_attachment = $attachment_id;
    }
}
if ( ! empty($conflicts) ) {
    throw new RuntimeException('Duplicate image source already used by another post: ' . wp_json_encode($conflicts));
}

$attachment_id = $reusable_attachment;
$reused_existing = $attachment_id > 0;
if ( ! $attachment_id ) {
    $tmp = download_url($request['image_url'], 120);
    if ( is_wp_error($tmp) ) {
        throw new RuntimeException('Image download failed: ' . $tmp->get_error_message());
    }
    $file = array('name' => $request['image_filename'], 'tmp_name' => $tmp);
    $attachment_id = media_handle_sideload($file, $post_id, $request['image_title']);
    if ( is_wp_error($attachment_id) ) {
        @unlink($tmp);
        throw new RuntimeException('Media import failed: ' . $attachment_id->get_error_message());
    }
    $attachment_id = (int) $attachment_id;
}

wp_update_post(array('ID' => $attachment_id, 'post_title' => $request['image_title']));
update_post_meta($attachment_id, '_wp_attachment_image_alt', $request['image_alt']);
update_post_meta($attachment_id, '_mdo_source_url', $request['image_url']);
update_post_meta($attachment_id, '_mdo_source_page', $request['source_page']);
update_post_meta($attachment_id, '_mdo_photographer', $request['photographer']);
update_post_meta($attachment_id, '_mdo_license', $request['license']);
if ( $license_url !== '' ) { update_post_meta($attachment_id, '_mdo_license_url', $license_url); }
if ( $source_key !== '' ) { update_post_meta($attachment_id, '_mdo_source_key', $source_key); }

set_post_thumbnail($post_id, $attachment_id);
update_post_meta($post_id, '_emdo_editorial_image_approved_id', $attachment_id);
delete_post_meta($post_id, '_emdo_editorial_image_approved_pexels_id');
update_post_meta($post_id, '_emdo_editorial_cover_brand_safe', '1');
delete_post_meta($post_id, '_emdo_uses_default_featured');
delete_post_meta($post_id, '_emdo_default_featured_hash');
delete_post_meta($post_id, '_emdo_default_featured_updated_at');
delete_post_meta($post_id, '_emdo_editorial_cover_asset_id');
clean_post_cache($post_id);
wp_cache_flush();

$thumbnail_id = (int) get_post_thumbnail_id($post_id);
$approved_id = (int) get_post_meta($post_id, '_emdo_editorial_image_approved_id', true);
$uses_default = (string) get_post_meta($post_id, '_emdo_uses_default_featured', true);
$cover_asset = (string) get_post_meta($post_id, '_emdo_editorial_cover_asset_id', true);
$attachment_url = (string) wp_get_attachment_url($attachment_id);
$attachment_alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
$attachment_title = (string) get_the_title($attachment_id);
$actual_basename = basename((string) wp_parse_url($attachment_url, PHP_URL_PATH));
$expected_stem = pathinfo($request['image_filename'], PATHINFO_FILENAME);

if (
    $thumbnail_id !== $attachment_id ||
    $approved_id !== $attachment_id ||
    $uses_default !== '' ||
    $cover_asset !== '' ||
    $attachment_url === '' ||
    $attachment_alt !== $request['image_alt'] ||
    $attachment_title !== $request['image_title'] ||
    strpos($actual_basename, $expected_stem) === false
) {
    throw new RuntimeException('Featured image/SEO verification failed.');
}

$report = array(
    'verified' => true,
    'duplicate_preflight' => array(
        'existing_attachment_candidates' => $candidate_ids,
        'conflicts' => array(),
        'reused_existing' => $reused_existing,
    ),
    'post' => array(
        'slug' => $request['post_slug'],
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
        'source_page' => $request['source_page'],
        'photographer' => $request['photographer'],
        'license' => $request['license'],
        'source_key' => $source_key,
        'permalink' => get_permalink($post_id),
    ),
);

echo wp_json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
