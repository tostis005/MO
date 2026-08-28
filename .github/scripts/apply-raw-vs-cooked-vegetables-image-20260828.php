<?php
/** Apply the user-approved Pexels 30700510 image to the raw-vs-cooked vegetables article. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

global $wpdb;

$source_page = 'https://www.pexels.com/photo/fresh-mixed-vegetables-cooking-in-pot-30700510/';
$image_url   = 'https://images.pexels.com/photos/30700510/pexels-photo-30700510.jpeg';
$source_key  = 'pexels:30700510';
$filename    = 'verduras-crudas-cocinadas-olla-pexels-30700510.jpg';
$title       = 'Verduras frescas cocinándose en una olla';
$alt         = 'Verduras frescas variadas cocinándose en una olla';
$license     = 'Pexels License';
$license_url = 'https://www.pexels.com/license/';

/* Resolve the article without guessing its slug. */
$like_verduras = '%' . $wpdb->esc_like('verduras') . '%';
$like_hortalizas = '%' . $wpdb->esc_like('hortalizas') . '%';
$like_crudas = '%' . $wpdb->esc_like('crudas') . '%';
$candidates = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT ID, post_title, post_name FROM {$wpdb->posts}
         WHERE post_type='post' AND post_status='publish'
           AND (post_title LIKE %s OR post_name LIKE %s OR post_title LIKE %s OR post_name LIKE %s)
           AND (post_title LIKE %s OR post_name LIKE %s)
         ORDER BY ID DESC",
        $like_verduras, $like_verduras, $like_hortalizas, $like_hortalizas, $like_crudas, $like_crudas
    ),
    ARRAY_A
);
if ( count($candidates) !== 1 ) {
    fwrite(STDERR, wp_json_encode(array('error'=>'Expected exactly one raw-vs-cooked vegetables post','candidates'=>$candidates), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . PHP_EOL);
    exit(10);
}
$post_id = (int) $candidates[0]['ID'];
$post_slug = (string) $candidates[0]['post_name'];

/* Try to preserve the creator name when Pexels exposes it in page metadata. */
$photographer = 'Pexels';
$response = wp_remote_get($source_page, array('timeout'=>25, 'user-agent'=>'Mozilla/5.0 EMDO image metadata verifier'));
if ( ! is_wp_error($response) ) {
    $body = (string) wp_remote_retrieve_body($response);
    $patterns = array(
        '/"photographer"\s*:\s*\{[^}]*"name"\s*:\s*"([^"]+)"/u',
        '/"photographer"\s*:\s*"([^"]+)"/u',
        '/"photographerName"\s*:\s*"([^"]+)"/u'
    );
    foreach ($patterns as $pattern) {
        if ( preg_match($pattern, $body, $m) && ! empty($m[1]) ) {
            $photographer = html_entity_decode(stripslashes($m[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
            break;
        }
    }
}

/* Check same source in the media library and reject use by another article. */
$candidate_ids = array();
foreach ( array('_mdo_source_page'=>$source_page, '_mdo_source_url'=>$image_url, '_mdo_source_key'=>$source_key) as $key=>$value ) {
    $ids = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>$key,'meta_value'=>$value));
    foreach ($ids as $id) { $candidate_ids[] = (int) $id; }
}
$candidate_ids = array_values(array_unique(array_filter($candidate_ids)));
$conflicts = array();
$reusable = 0;
foreach ($candidate_ids as $attachment_id) {
    $refs = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_thumbnail_id','_emdo_editorial_image_approved_id') AND meta_value=%s AND post_id<>%d",
        (string)$attachment_id, $post_id
    ));
    if ($refs) {
        $conflicts[] = array('attachment_id'=>$attachment_id,'used_by_posts'=>array_map('intval',$refs));
    } else {
        $reusable = (int) $attachment_id;
    }
}
if ($conflicts) {
    fwrite(STDERR, wp_json_encode(array('error'=>'Pexels 30700510 is already used by another post','conflicts'=>$conflicts), JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . PHP_EOL);
    exit(11);
}

$attachment_id = $reusable;
$reused_existing = $attachment_id > 0;
if ( ! $attachment_id ) {
    $tmp = download_url($image_url, 120);
    if ( is_wp_error($tmp) ) {
        fwrite(STDERR, 'Image download failed: ' . $tmp->get_error_message() . PHP_EOL);
        exit(12);
    }
    $file = array('name'=>$filename,'tmp_name'=>$tmp);
    $attachment_id = media_handle_sideload($file, $post_id, $title);
    if ( is_wp_error($attachment_id) ) {
        @unlink($tmp);
        fwrite(STDERR, 'Media import failed: ' . $attachment_id->get_error_message() . PHP_EOL);
        exit(13);
    }
    $attachment_id = (int) $attachment_id;
}

wp_update_post(array('ID'=>$attachment_id,'post_title'=>$title,'post_excerpt'=>'Fotografía de Pexels. Fuente y licencia registradas en los metadatos del archivo.'));
update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
update_post_meta($attachment_id, '_mdo_source_url', $image_url);
update_post_meta($attachment_id, '_mdo_source_page', $source_page);
update_post_meta($attachment_id, '_mdo_source_key', $source_key);
update_post_meta($attachment_id, '_mdo_photographer', $photographer);
update_post_meta($attachment_id, '_mdo_license', $license);
update_post_meta($attachment_id, '_mdo_license_url', $license_url);
update_post_meta($attachment_id, '_emdo_pexels_photo_id', '30700510');

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

$thumb = (int) get_post_thumbnail_id($post_id);
$approved = (int) get_post_meta($post_id, '_emdo_editorial_image_approved_id', true);
$uses_default = (string) get_post_meta($post_id, '_emdo_uses_default_featured', true);
$cover_asset = (string) get_post_meta($post_id, '_emdo_editorial_cover_asset_id', true);
$attachment_url = (string) wp_get_attachment_url($attachment_id);
$actual_alt = (string) get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
$actual_title = (string) get_the_title($attachment_id);
$basename = basename((string) wp_parse_url($attachment_url, PHP_URL_PATH));

$verified = $thumb === $attachment_id && $approved === $attachment_id && $uses_default === '' && $cover_asset === '' && $actual_alt === $alt && $actual_title === $title && strpos($basename, 'verduras-crudas-cocinadas-olla-pexels-30700510') !== false;
if ( ! $verified ) {
    fwrite(STDERR, 'Featured image verification failed.' . PHP_EOL);
    exit(14);
}

$report = array(
    'verified'=>true,
    'selection'=>'user-url-pexels-30700510',
    'resolved_post'=>array('id'=>$post_id,'title'=>$candidates[0]['post_title'],'slug'=>$post_slug,'permalink'=>get_permalink($post_id)),
    'duplicate_preflight'=>array('existing_attachment_candidates'=>$candidate_ids,'conflicts'=>array(),'reused_existing'=>$reused_existing),
    'image'=>array('attachment_id'=>$attachment_id,'thumbnail_id'=>$thumb,'approved_id'=>$approved,'url'=>$attachment_url,'filename'=>$basename,'title'=>$actual_title,'alt'=>$actual_alt,'source_page'=>$source_page,'source_key'=>$source_key,'photographer'=>$photographer,'license'=>$license,'license_url'=>$license_url,'uses_default'=>$uses_default,'cover_asset'=>$cover_asset)
);
echo wp_json_encode($report, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . PHP_EOL;
