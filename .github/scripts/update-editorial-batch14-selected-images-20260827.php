<?php
/** Update selected featured images for editorial authority batch 14. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$items = array(
    array(
        'key' => 'what-is-cured-sausage-guide',
        'source_key' => 'pexels-eduard-perez-37256489',
        'direct' => 'https://images.pexels.com/photos/37256489/pexels-photo-37256489.jpeg?cs=srgb&dl=pexels-eduard-perez-2158828645-37256489.jpg&fm=jpg',
        'page' => 'https://www.pexels.com/photo/rustic-hanging-sausages-in-sunlit-workshop-37256489/',
        'creator' => 'Eduard Perez',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
        'alt_es' => 'Embutidos curados colgados en un obrador rústico iluminado por el sol',
        'alt_en' => 'Cured sausages hanging in a sunlit rustic workshop',
    ),
    array(
        'key' => 'pepper-types-guide',
        'source_key' => 'pexels-bell-peppers-38541828',
        'direct' => 'https://images.pexels.com/photos/38541828/pexels-photo-38541828.jpeg',
        'page' => 'https://www.pexels.com/photo/colorful-assortment-of-fresh-bell-peppers-displayed-38541828/',
        'creator' => 'Pexels contributor',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
        'alt_es' => 'Surtido de pimientos frescos de distintos colores',
        'alt_en' => 'Colorful assortment of fresh bell peppers',
    ),
    array(
        'key' => 'onion-types-guide',
        'source_key' => 'pexels-kindel-media-7456550',
        'direct' => 'https://images.pexels.com/photos/7456550/pexels-photo-7456550.jpeg?cs=srgb&dl=pexels-kindelmedia-7456550.jpg&fm=jpg',
        'page' => 'https://www.pexels.com/photo/a-close-up-shot-of-red-onions-7456550/',
        'creator' => 'Kindel Media',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
        'alt_es' => 'Primer plano de cebollas rojas y amarillas frescas',
        'alt_en' => 'Close-up of fresh red and yellow onions',
    ),
);

$report = array();
foreach ( $items as $item ) {
    $ids = get_posts(array(
        'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_key' => '_emdo_authority_key', 'meta_value' => $item['key'],
    ));
    if ( empty($ids) ) { throw new RuntimeException('Post not found: '.$item['key']); }
    $post_id = (int) $ids[0];

    $existing = get_posts(array(
        'post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids',
        'meta_key'=>'_emdo_image_source_key','meta_value'=>$item['source_key'],
    ));
    $attachment_id = !empty($existing) ? (int)$existing[0] : 0;
    if ( $attachment_id <= 0 ) {
        $tmp = download_url($item['direct'], 90);
        if ( is_wp_error($tmp) ) { throw new RuntimeException($item['key'].' download failed: '.$tmp->get_error_message()); }
        $file = array('name'=>'emdo-batch14-'.$item['source_key'].'.jpg','tmp_name'=>$tmp);
        $attachment_id = media_handle_sideload($file, $post_id, $item['alt_es']);
        if ( is_wp_error($attachment_id) ) { @unlink($tmp); throw new RuntimeException($item['key'].' import failed: '.$attachment_id->get_error_message()); }
        $attachment_id = (int)$attachment_id;
    }

    wp_update_post(array(
        'ID'=>$attachment_id,
        'post_title'=>$item['alt_es'],
        'post_excerpt'=>'Fotografía: '.$item['creator'].'. Fuente: '.$item['page'].'. Licencia: '.$item['license'].'.',
    ));
    update_post_meta($attachment_id,'_emdo_image_source_key',$item['source_key']);
    update_post_meta($attachment_id,'_emdo_image_source_page',$item['page']);
    update_post_meta($attachment_id,'_emdo_image_creator',$item['creator']);
    update_post_meta($attachment_id,'_emdo_image_license',$item['license']);
    update_post_meta($attachment_id,'_emdo_image_license_url',$item['license_url']);
    update_post_meta($attachment_id,'_wp_attachment_image_alt',$item['alt_es']);
    update_post_meta($attachment_id,'_en_US_attachment_alt',$item['alt_en']);
    if ( ! set_post_thumbnail($post_id,$attachment_id) ) { throw new RuntimeException($item['key'].' could not set featured image'); }

    $meta = wp_get_attachment_metadata($attachment_id);
    $w = (int)($meta['width'] ?? 0); $h = (int)($meta['height'] ?? 0);
    if ( $w < 1200 || $h < 700 ) { throw new RuntimeException($item['key'].' image too small '.$w.'x'.$h); }
    if ( (int)get_post_thumbnail_id($post_id) !== $attachment_id ) { throw new RuntimeException($item['key'].' thumbnail mismatch'); }

    $report[] = array('key'=>$item['key'],'post_id'=>$post_id,'image_id'=>$attachment_id,'width'=>$w,'height'=>$h,'page'=>$item['page']);
}

echo wp_json_encode(array('updated'=>true,'items'=>$report),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
