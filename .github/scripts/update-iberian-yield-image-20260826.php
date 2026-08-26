<?php
/**
 * One-off production update: replace the featured image for the Iberian ham yield/packs article.
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "ERROR: WordPress is not loaded.\n");
    exit(10);
}

$target_url = 'https://www.elmercadodeorigen.com/cuantos-sobres-salen-jamon-iberico-rendimiento-real-pieza/';
$target_slug = 'cuantos-sobres-salen-jamon-iberico-rendimiento-real-pieza';
$image_url  = 'https://upload.wikimedia.org/wikipedia/commons/a/ac/Barcelona_Mercat_Boqueria_9_%288271967087%29.jpg';
$source_page = 'https://commons.wikimedia.org/wiki/File:Barcelona_Mercat_Boqueria_9_(8271967087).jpg';
$alt = 'Varios sobres de jamón envasados al vacío, una referencia visual del rendimiento de una pieza de jamón ibérico';
$title = 'Sobres de jamón envasados al vacío';
$credit = 'Foto: Alain Rouiller / Wikimedia Commons · CC BY-SA 2.0';

$post_id = (int) url_to_postid($target_url);
if (!$post_id) {
    $post = get_page_by_path($target_slug, OBJECT, array('post', 'page'));
    if ($post instanceof WP_Post) {
        $post_id = (int) $post->ID;
    }
}

if (!$post_id) {
    fwrite(STDERR, "ERROR: target article not found.\n");
    exit(20);
}

$post = get_post($post_id);
if (!$post instanceof WP_Post) {
    fwrite(STDERR, "ERROR: invalid target post.\n");
    exit(21);
}

// Reuse the same sourced attachment on re-runs instead of creating duplicates.
$existing = get_posts(array(
    'post_type'      => 'attachment',
    'post_status'    => 'inherit',
    'fields'         => 'ids',
    'posts_per_page' => 1,
    'meta_key'       => '_mdo_source_url',
    'meta_value'     => $image_url,
));

$attachment_id = $existing ? (int) $existing[0] : 0;

if (!$attachment_id) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($image_url, 90);
    if (is_wp_error($tmp)) {
        fwrite(STDERR, 'ERROR: image download failed: ' . $tmp->get_error_message() . "\n");
        exit(30);
    }

    $file = array(
        'name'     => 'sobres-jamon-iberico-rendimiento.jpg',
        'tmp_name' => $tmp,
    );

    $attachment_id = media_handle_sideload($file, $post_id, $title);
    if (is_wp_error($attachment_id)) {
        @unlink($tmp);
        fwrite(STDERR, 'ERROR: media sideload failed: ' . $attachment_id->get_error_message() . "\n");
        exit(31);
    }
    $attachment_id = (int) $attachment_id;
}

update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
update_post_meta($attachment_id, '_mdo_source_url', $image_url);
update_post_meta($attachment_id, '_mdo_source_page', $source_page);
update_post_meta($attachment_id, '_mdo_license', 'CC BY-SA 2.0');
update_post_meta($attachment_id, '_mdo_photographer', 'Alain Rouiller');

$updated = wp_update_post(array(
    'ID'           => $attachment_id,
    'post_title'   => $title,
    'post_excerpt' => $credit,
    'post_content' => $credit . "\nFuente: " . $source_page,
), true);
if (is_wp_error($updated)) {
    fwrite(STDERR, 'ERROR: attachment metadata update failed: ' . $updated->get_error_message() . "\n");
    exit(32);
}

if (!set_post_thumbnail($post_id, $attachment_id)) {
    // set_post_thumbnail() can return false when the requested value is already set.
    $current = (int) get_post_thumbnail_id($post_id);
    if ($current !== $attachment_id) {
        fwrite(STDERR, "ERROR: failed to set featured image.\n");
        exit(40);
    }
}

clean_post_cache($post_id);
clean_post_cache($attachment_id);

printf(
    "featured_image_updated post_id=%d attachment_id=%d slug=%s source=%s\n",
    $post_id,
    $attachment_id,
    $post->post_name,
    $image_url
);
