<?php
/** Replace the batch 14 sobrasada featured image with a CC BY 2.0 Wikimedia photo and add visible attribution. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$key = 'iberian-sobrasada-guide';
$source_key = 'wikimedia-jonathan-pincas-sobrasada-y-pan-6211126857';
$direct = 'https://upload.wikimedia.org/wikipedia/commons/4/42/Sobrasada_y_pan.jpg';
$page = 'https://commons.wikimedia.org/wiki/File:Sobrasada_y_pan.jpg';
$creator = 'Jonathan Pincas';
$license = 'CC BY 2.0';
$license_url = 'https://creativecommons.org/licenses/by/2.0/';
$alt_es = 'Sobrasada tradicional servida con pan';
$alt_en = 'Traditional sobrasada served with bread';

$ids = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids',
    'meta_key'=>'_emdo_authority_key','meta_value'=>$key,
));
if ( empty($ids) ) { throw new RuntimeException('Sobrasada article not found'); }
$post_id = (int)$ids[0];

$existing = get_posts(array(
    'post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids',
    'meta_key'=>'_emdo_image_source_key','meta_value'=>$source_key,
));
$attachment_id = !empty($existing) ? (int)$existing[0] : 0;
if ( $attachment_id <= 0 ) {
    $tmp = download_url($direct, 90);
    if ( is_wp_error($tmp) ) { throw new RuntimeException('Image download failed: '.$tmp->get_error_message()); }
    $file = array('name'=>'sobrasada-y-pan-jonathan-pincas.jpg','tmp_name'=>$tmp);
    $attachment_id = media_handle_sideload($file,$post_id,$alt_es);
    if ( is_wp_error($attachment_id) ) { @unlink($tmp); throw new RuntimeException('Image import failed: '.$attachment_id->get_error_message()); }
    $attachment_id = (int)$attachment_id;
}

wp_update_post(array(
    'ID'=>$attachment_id,
    'post_title'=>$alt_es,
    'post_excerpt'=>'Fotografía: Jonathan Pincas. Fuente: Wikimedia Commons. Licencia: CC BY 2.0. La web puede mostrar una versión redimensionada o recortada para adaptarla al diseño.',
));
update_post_meta($attachment_id,'_emdo_image_source_key',$source_key);
update_post_meta($attachment_id,'_emdo_image_source_page',$page);
update_post_meta($attachment_id,'_emdo_image_creator',$creator);
update_post_meta($attachment_id,'_emdo_image_license',$license);
update_post_meta($attachment_id,'_emdo_image_license_url',$license_url);
update_post_meta($attachment_id,'_emdo_image_changes','Responsive display may resize or crop the image to fit the site layout.');
update_post_meta($attachment_id,'_wp_attachment_image_alt',$alt_es);
update_post_meta($attachment_id,'_en_US_attachment_alt',$alt_en);
if ( ! set_post_thumbnail($post_id,$attachment_id) ) { throw new RuntimeException('Could not set sobrasada featured image'); }

$credit_es = '<p class="emdo-image-credit"><small>Imagen destacada: <a href="'.$page.'" rel="noopener noreferrer">Jonathan Pincas / Wikimedia Commons</a>, licencia <a href="'.$license_url.'" rel="license noopener noreferrer">CC BY 2.0</a>. La web puede mostrar una versión redimensionada o recortada para adaptarla al diseño.</small></p>';
$credit_en = '<p class="emdo-image-credit"><small>Featured image: <a href="'.$page.'" rel="noopener noreferrer">Jonathan Pincas / Wikimedia Commons</a>, licensed under <a href="'.$license_url.'" rel="license noopener noreferrer">CC BY 2.0</a>. The site may display a resized or cropped version to fit the layout.</small></p>';

function emdo_sobrasada_upsert_credit(string $html,string $credit):string {
    $html = preg_replace('~\s*<p class=["\']emdo-image-credit["\'][^>]*>.*?</p>\s*~is','',$html);
    if ( preg_match('/\[products\b[^\]]*\]/i',$html) ) {
        return preg_replace('/(\[products\b[^\]]*\])/i',$credit."\n$1",$html,1);
    }
    return rtrim($html)."\n".$credit;
}

$content = (string)get_post_field('post_content',$post_id);
$new_content = emdo_sobrasada_upsert_credit($content,$credit_es);
$result = wp_update_post(array('ID'=>$post_id,'post_content'=>$new_content),true);
if ( is_wp_error($result) ) { throw new RuntimeException('Could not update Spanish attribution: '.$result->get_error_message()); }

$en_content = (string)get_post_meta($post_id,'_en_US_post_content',true);
if ( '' === $en_content ) { throw new RuntimeException('English article content missing'); }
update_post_meta($post_id,'_en_US_post_content',emdo_sobrasada_upsert_credit($en_content,$credit_en));
update_post_meta($post_id,'_emdo_editorial_updated',gmdate('c'));

$meta = wp_get_attachment_metadata($attachment_id);
$w = (int)($meta['width'] ?? 0); $h = (int)($meta['height'] ?? 0);
if ( $w < 1000 || $h < 700 ) { throw new RuntimeException('Image dimensions unexpectedly small '.$w.'x'.$h); }
if ( (int)get_post_thumbnail_id($post_id) !== $attachment_id ) { throw new RuntimeException('Featured image mismatch'); }
if ( false === strpos((string)get_post_field('post_content',$post_id),'Jonathan Pincas') ) { throw new RuntimeException('Spanish visible attribution missing'); }
if ( false === strpos((string)get_post_meta($post_id,'_en_US_post_content',true),'Jonathan Pincas') ) { throw new RuntimeException('English visible attribution missing'); }

echo wp_json_encode(array(
    'updated'=>true,'post_id'=>$post_id,'image_id'=>$attachment_id,'width'=>$w,'height'=>$h,
    'source_key'=>$source_key,'source_page'=>$page,'creator'=>$creator,'license'=>$license,'license_url'=>$license_url,
),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
