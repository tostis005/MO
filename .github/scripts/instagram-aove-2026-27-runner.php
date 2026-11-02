<?php
/**
 * One-off AOVE 2026/27 approved-image publisher.
 * Executed only via wp eval-file in the production WordPress environment.
 */
if ( ! defined('ABSPATH') || ! function_exists('emdo_instagram_publish_image') ) {
    fwrite(STDERR, "Instagram publisher is not loaded in WordPress.\n");
    exit(3);
}
$queue_path = getenv('EMDO_IG_APPROVED_POST_FILE');
$image_path = getenv('EMDO_IG_APPROVED_IMAGE_FILE');
if ( ! $queue_path || ! is_readable($queue_path) || ! $image_path || ! is_readable($image_path) ) {
    fwrite(STDERR, "Approved post JSON or image is missing.\n");
    exit(4);
}
$post = json_decode(file_get_contents($queue_path), true);
if ( ! is_array($post) || empty($post['request_id']) || empty($post['caption']) ) {
    fwrite(STDERR, "Approved post data is invalid.\n");
    exit(5);
}
$request_id = sanitize_key($post['request_id']);
if ( $request_id !== 'aove-2026-27-lechin-manzanilla-20261102' ) {
    fwrite(STDERR, "Unexpected request ID.\n");
    exit(6);
}
$prior = get_option('emdo_ig_last_queue_request_id', '');
if ( hash_equals((string)$prior, $request_id) ) {
    $log = get_option('emdo_ig_publish_log', array());
    $last = is_array($log) && isset($log[0]) ? $log[0] : array();
    echo 'EMDO_IG_RESULT:' . wp_json_encode(array(
        'status' => 'skipped', 'reason' => 'duplicate_request_id',
        'request_id' => $request_id,
        'media_id' => isset($last['media_id']) ? $last['media_id'] : '',
        'image_url' => isset($last['image_url']) ? $last['image_url'] : ''
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
$existing = get_posts(array(
    'post_type' => 'attachment', 'post_status' => 'inherit',
    'posts_per_page' => 1, 'fields' => 'ids',
    'meta_key' => '_emdo_ig_request_id', 'meta_value' => $request_id
));
$attachment_id = $existing ? (int)$existing[0] : 0;
if ( ! $attachment_id ) {
    $uploads = wp_upload_dir();
    if ( ! empty($uploads['error']) || ! wp_mkdir_p($uploads['path']) ) {
        fwrite(STDERR, "WordPress upload directory is not writable.\n");
        exit(9);
    }
    $name = wp_unique_filename($uploads['path'], 'aove-sin-filtrar-nueva-cosecha-2026-27.jpg');
    $destination = trailingslashit($uploads['path']) . $name;
    if ( ! copy($image_path, $destination) ) {
        fwrite(STDERR, "Cannot copy approved image to WordPress uploads.\n");
        exit(10);
    }
    $attachment_id = wp_insert_attachment(array(
        'guid' => trailingslashit($uploads['url']) . $name,
        'post_title' => 'AOVE sin filtrar - Nueva cosecha 2026/27 - Lechin y Manzanilla',
        'post_content' => 'Imagen para la publicacion de Instagram de El Mercado de Origen.',
        'post_mime_type' => 'image/jpeg', 'post_status' => 'inherit'
    ), $destination, 0, true);
    if ( is_wp_error($attachment_id) ) {
        @unlink($destination);
        fwrite(STDERR, "Cannot create WordPress media attachment: " . $attachment_id->get_error_message() . "\n");
        exit(11);
    }
    update_post_meta($attachment_id, '_emdo_ig_request_id', $request_id);
    update_post_meta($attachment_id, '_wp_attachment_image_alt', 'AOVE sin filtrar de nueva cosecha 2026/27, Lechin y Manzanilla');
    $metadata = wp_generate_attachment_metadata($attachment_id, $destination);
    if ( is_array($metadata) ) wp_update_attachment_metadata($attachment_id, $metadata);
}
$image_url = wp_get_attachment_url($attachment_id);
if ( ! $image_url || 'https' !== wp_parse_url($image_url, PHP_URL_SCHEME) ) {
    fwrite(STDERR, "Uploaded image has no public HTTPS URL.\n");
    exit(12);
}
$account = emdo_ig_verify_connection();
if ( is_wp_error($account) || empty($account['id']) || empty($account['username']) ) {
    $reason = is_wp_error($account) ? $account->get_error_message() : 'Instagram account not identified.';
    echo 'EMDO_IG_RESULT:' . wp_json_encode(array(
        'status' => 'uploaded_pending_instagram_auth',
        'request_id' => $request_id,
        'image_url' => $image_url,
        'wordpress_attachment_id' => (int)$attachment_id,
        'reason' => $reason
    ), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
    exit(0);
}
$published = emdo_instagram_publish_image($image_url, $post['caption']);
if ( is_wp_error($published) ) {
    fwrite(STDERR, "Instagram publish failed: " . $published->get_error_message() . "\n");
    exit(13);
}
update_option('emdo_ig_last_queue_request_id', $request_id, false);
echo 'EMDO_IG_RESULT:' . wp_json_encode(array(
    'status' => 'published', 'request_id' => $request_id,
    'media_id' => $published['media_id'], 'username' => $published['username'],
    'image_url' => $image_url, 'wordpress_attachment_id' => (int)$attachment_id,
    'published_at' => $published['published_at']
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
