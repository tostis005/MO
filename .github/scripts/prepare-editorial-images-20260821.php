<?php
/** Production-safe deployment for approved featured image 1A. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
global $wpdb;

$item = array(
    'selection' => '1A',
    'slug' => 'peso-neto-vs-peso-escurrido-conserva-que-significa',
    'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/8/89/Canned_white_beans%2C_opened_and_drained.jpg',
    'filename' => 'peso-escurrido-alubias-conserva-father-jack.jpg',
    'title' => 'Alubias blancas de conserva abiertas y escurridas',
    'alt' => 'Alubias blancas de conserva en una lata abierta después de escurrir el líquido',
    'source_page' => 'https://commons.wikimedia.org/wiki/File:Canned_white_beans,_opened_and_drained.jpg',
    'source_key' => 'wikimedia:Canned_white_beans_opened_and_drained',
    'photographer' => 'Father.Jack',
    'license' => 'CC BY 2.0',
    'license_url' => 'https://creativecommons.org/licenses/by/2.0/',
);

$post = get_page_by_path( $item['slug'], OBJECT, 'post' );
if ( ! $post instanceof WP_Post ) { throw new RuntimeException('Post not found: '.$item['slug']); }
$post_id = (int) $post->ID;

$candidate_ids = array();
foreach ( array('_mdo_source_page'=>$item['source_page'], '_mdo_source_key'=>$item['source_key']) as $key=>$value ) {
    $ids = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>-1,'fields'=>'ids','meta_key'=>$key,'meta_value'=>$value));
    foreach($ids as $id){ $candidate_ids[]=(int)$id; }
}
$candidate_ids = array_values(array_unique(array_filter($candidate_ids)));
$attachment_id = 0;
$conflicts = array();
foreach($candidate_ids as $aid){
    $other = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_thumbnail_id','_emdo_editorial_image_approved_id') AND meta_value=%s AND post_id<>%d",
        (string)$aid, $post_id
    ));
    $other = array_values(array_unique(array_map('intval',$other)));
    if($other){ $conflicts[]=array('attachment_id'=>$aid,'used_by_posts'=>$other); }
    else { $attachment_id=$aid; }
}
if($conflicts){ throw new RuntimeException('Duplicate image already used elsewhere: '.wp_json_encode($conflicts)); }
$reused_existing = $attachment_id > 0;

if(!$attachment_id){
    $tmp = download_url($item['image_url'],120);
    if(is_wp_error($tmp)){ throw new RuntimeException('Download failed: '.$tmp->get_error_message()); }
    $attachment_id = media_handle_sideload(array('name'=>$item['filename'],'tmp_name'=>$tmp),$post_id,$item['title']);
    if(is_wp_error($attachment_id)){ @unlink($tmp); throw new RuntimeException('Import failed: '.$attachment_id->get_error_message()); }
    $attachment_id=(int)$attachment_id;
}

wp_update_post(array('ID'=>$attachment_id,'post_title'=>$item['title'],'post_excerpt'=>'Fotografía: '.$item['photographer'].' · '.$item['license'].'.'));
update_post_meta($attachment_id,'_wp_attachment_image_alt',$item['alt']);
update_post_meta($attachment_id,'_mdo_source_url',$item['image_url']);
update_post_meta($attachment_id,'_mdo_source_page',$item['source_page']);
update_post_meta($attachment_id,'_mdo_source_key',$item['source_key']);
update_post_meta($attachment_id,'_mdo_photographer',$item['photographer']);
update_post_meta($attachment_id,'_mdo_license',$item['license']);
update_post_meta($attachment_id,'_mdo_license_url',$item['license_url']);
set_post_thumbnail($post_id,$attachment_id);
update_post_meta($post_id,'_emdo_editorial_image_approved_id',$attachment_id);
delete_post_meta($post_id,'_emdo_editorial_image_approved_pexels_id');
update_post_meta($post_id,'_emdo_editorial_cover_brand_safe','1');
delete_post_meta($post_id,'_emdo_uses_default_featured');
delete_post_meta($post_id,'_emdo_default_featured_hash');
delete_post_meta($post_id,'_emdo_default_featured_updated_at');
delete_post_meta($post_id,'_emdo_editorial_cover_asset_id');
clean_post_cache($post_id); wp_cache_flush();

$thumb=(int)get_post_thumbnail_id($post_id);
$approved=(int)get_post_meta($post_id,'_emdo_editorial_image_approved_id',true);
$uses_default=(string)get_post_meta($post_id,'_emdo_uses_default_featured',true);
$cover_asset=(string)get_post_meta($post_id,'_emdo_editorial_cover_asset_id',true);
$url=(string)wp_get_attachment_url($attachment_id);
$alt=(string)get_post_meta($attachment_id,'_wp_attachment_image_alt',true);
$title=(string)get_the_title($attachment_id);
$basename=basename((string)wp_parse_url($url,PHP_URL_PATH));
$stem=pathinfo($item['filename'],PATHINFO_FILENAME);
if($thumb!==$attachment_id||$approved!==$attachment_id||$uses_default!==''||$cover_asset!==''||$url===''||$alt!==$item['alt']||$title!==$item['title']||strpos($basename,$stem)===false){
    throw new RuntimeException('Database/image SEO verification failed.');
}

$permalink=get_permalink($post_id);
$verify_url=add_query_arg('emdo_featured_verify',(string)time(),$permalink);
$response=wp_remote_get($verify_url,array('timeout'=>40,'redirection'=>5,'headers'=>array('Cache-Control'=>'no-cache','User-Agent'=>'Mozilla/5.0 EMDO image verifier')));
if(is_wp_error($response)){ throw new RuntimeException('Frontend request failed: '.$response->get_error_message()); }
$http=(int)wp_remote_retrieve_response_code($response);
$body=(string)wp_remote_retrieve_body($response);
$image_present=strpos($body,$basename)!==false;
$alt_present=strpos($body,$item['alt'])!==false || strpos($body,esc_attr($item['alt']))!==false;
if($http!==200||!$image_present||!$alt_present){ throw new RuntimeException('Frontend verification failed: '.wp_json_encode(array('http'=>$http,'image_present'=>$image_present,'alt_present'=>$alt_present))); }

$actual=array(
    'selection'=>$item['selection'],'slug'=>$item['slug'],'post_id'=>$post_id,'attachment_id'=>$attachment_id,
    'thumbnail_id'=>$thumb,'approved_id'=>$approved,'uses_default'=>$uses_default,'cover_asset'=>$cover_asset,
    'attachment_url'=>$url,'filename'=>$basename,'title'=>$title,'alt'=>$alt,'source_page'=>$item['source_page'],
    'photographer'=>$item['photographer'],'license'=>$item['license'],'source_key'=>$item['source_key'],
    'reused_existing'=>$reused_existing,'duplicate_candidates'=>$candidate_ids,'duplicate_conflicts'=>array(),
    'permalink'=>$permalink,'public_http'=>$http,'image_present'=>$image_present,'alt_present'=>$alt_present,'verified'=>true
);
$images=array($actual);
for($i=2;$i<=6;$i++){ $images[]=array('slot'=>$i,'status'=>'no-op'); }
echo wp_json_encode(array('images'=>$images,'errors'=>array(),'featured_update'=>$actual),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
