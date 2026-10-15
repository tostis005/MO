<?php
/** Add four correctly identified and CC-attributed featured images to blog posts. */
if (!defined('ABSPATH')) exit(1);
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
const EMDO_PHOTO_RELEASE='exoticas-photos-20261015-02';
$file=(string)getenv('EMDO_PHOTO_PAYLOAD');
if (!$file || !is_file($file))throw new RuntimeException('Missing photo manifest');
$data=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);
if (($data['release']??'')!==EMDO_PHOTO_RELEASE || count($data['photos']??[])!==4)throw new RuntimeException('Invalid photo manifest');
$targets=[];$originals=[];
foreach($data['photos'] as $a) {
 foreach(['slug','key','file','url','page','author','license','license_url','alt_es','alt_en','caption_es','caption_en'] as $k)
   if(!isset($a[$k]) || !is_string($a[$k]) || !trim($a[$k]))throw new RuntimeException('Missing '.$k);
 if(!str_starts_with($a['url'],'https://upload.wikimedia.org/') && !str_starts_with($a['url'],'https://thumb.wikimedia.org/'))
    throw new RuntimeException('Unapproved image source');
 if(!str_starts_with($a['page'],'https://commons.wikimedia.org/wiki/File:'))throw new RuntimeException('Missing Wikimedia source');
 if(!str_starts_with($a['license_url'],'https://creativecommons.org/licenses/by-sa/') && !str_starts_with($a['license_url'],'https://creativecommons.org/licenses/by/'))throw new RuntimeException('Unapproved license');
 $p=get_page_by_path($a['slug'],OBJECT,'post');
 if(!$p instanceof WP_Post || $p->post_status!=='publish')throw new RuntimeException('Post missing '.$a['slug']);
 if((string)get_post_meta($p->ID,'_emdo_quality_v2_batch',true)!=='quality-v2-20261015-02')throw new RuntimeException('Quality v2 edit missing');
 $current=(int)get_post_thumbnail_id($p->ID);
 $owned=(string)get_post_meta($p->ID,'_emdo_cover_photo_release',true)===EMDO_PHOTO_RELEASE;
 if($current>0 && !$owned)throw new RuntimeException('Refusing to replace unrelated custom cover: '.$a['slug']);
 $targets[$a['slug']]=['p'=>$p,'a'=>$a];
}
$created=[];$snaps=[];$results=[];
try {
 foreach($targets as $slug=>$pair) {
   $p=$pair['p'];$a=$pair['a'];$id=(int)$p->ID;
   $snap=['es'=>$p->post_content,'en'=>get_post_meta($id,'_en_US_post_content',true),'metas'=>[]];
   foreach(['_thumbnail_id','_emdo_editorial_image_approved_id','_emdo_editorial_cover_asset_id','_emdo_cover_photo_release','_emdo_editorial_cover_brand_safe'] as $key)
      $snap['metas'][$key]=['exists'=>metadata_exists('post',$id,$key),'value'=>get_post_meta($id,$key,true)];
   $snaps[$id]=$snap;
   $key='emdo-photo:'.EMDO_PHOTO_RELEASE.':'.(string)$a['key'];
   $found=get_posts(['post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_photo_source_key','meta_value'=>$key]);
   $aid= $found ? (int)$found[0] : 0;
   if(!$aid) {
      $urls=[(string)$a['url'],'https://commons.wikimedia.org/wiki/Special:Redirect/file/'.rawurlencode((string)$a['file']).'?width=1280'];
      $tmp=null;
      foreach($urls as $url) {
        $download=download_url($url,90);
        if(is_wp_error($download))continue;
        $info=@getimagesize($download);
        if(!$info || $info[0]<900 || $info[1]<600 || $info['mime']!=='image/jpeg'){@unlink($download);continue;}
        $tmp=$download;break;
      }
      if(!$tmp)throw new RuntimeException('Cannot fetch validated Wikimedia JPEG for '.$slug);
      $filearr=['name'=>'emdo-'.sanitize_title((string)$a['key']).'-editorial-20261015.jpg','tmp_name'=>$tmp];
      $result=media_handle_sideload($filearr,$id,(string)$a['alt_es']);
      if(is_wp_error($result)){@unlink($tmp);throw new RuntimeException('Sideload failed '.$slug.': '.$result->get_error_message());}
      $aid=(int)$result;$created[]=$aid;
      update_post_meta($aid,'_emdo_photo_source_key',$key);
   }
   $caption='Fotografía: '.(string)$a['author'].' / Wikimedia Commons · '.(string)$a['license'];
   wp_update_post(['ID'=>$aid,'post_title'=>(string)$a['alt_es'],'post_excerpt'=>$caption]);
   update_post_meta($aid,'_wp_attachment_image_alt',(string)$a['alt_es']);
   update_post_meta($aid,'_en_US_attachment_alt',(string)$a['alt_en']);
   update_post_meta($aid,'_emdo_photo_original_page',(string)$a['page']);
   update_post_meta($aid,'_emdo_photo_author',(string)$a['author']);
   update_post_meta($aid,'_emdo_photo_license',(string)$a['license']);
   update_post_meta($aid,'_emdo_photo_license_url',(string)$a['license_url']);
   $credit_marker='<!-- EMDO_PHOTO_CREDIT_20261015_'.strtoupper((string)$a['key']).' -->';
   $source='<a href="'.esc_url($a['page']).'">Wikimedia Commons</a>';
   $license='<a href="'.esc_url($a['license_url']).'">'.esc_html($a['license']).'</a>';
   $es_credit=PHP_EOL.$credit_marker.'<p class="emdo-photo-credit"><small>'.esc_html($a['caption_es']).' Fotografía: '.esc_html($a['author']).' / '.$source.'. Licencia '.$license.'. Imagen redimensionada para la web.</small></p>';
   $en_credit=PHP_EOL.$credit_marker.'<p class="emdo-photo-credit"><small>'.esc_html($a['caption_en']).' Photograph: '.esc_html($a['author']).' / '.$source.'. License '.$license.'. Image resized for web display.</small></p>';
   // Legacy first-run footer used a literal backslash-n; normalise it to a real line break.
   $rawline='\\n'.$credit_marker;
   $es_current=(string)get_post_field('post_content',$id);
   if(str_contains($es_current,$rawline)) {
     $es_clean=str_replace($rawline,PHP_EOL.$credit_marker,$es_current);
     $res=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$es_clean]),true);
     if(is_wp_error($res))throw new RuntimeException('Could not normalise Spanish photo credit '.$slug);
     $p=get_post($id);
   }
   $en_current=(string)get_post_meta($id,'_en_US_post_content',true);
   if(str_contains($en_current,$rawline))update_post_meta($id,'_en_US_post_content',str_replace($rawline,PHP_EOL.$credit_marker,$en_current));
   if(!str_contains((string)$p->post_content,$credit_marker)){
      $res=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>(string)$p->post_content.$es_credit]),true);
      if(is_wp_error($res))throw new RuntimeException('ES photo credit failed '.$slug);
   }
   $en=(string)get_post_meta($id,'_en_US_post_content',true);
   if(!str_contains($en,$credit_marker))update_post_meta($id,'_en_US_post_content',$en.$en_credit);
   if((int)get_post_thumbnail_id($id)!==$aid && !set_post_thumbnail($id,$aid))throw new RuntimeException('Could not set thumbnail '.$slug);
   update_post_meta($id,'_emdo_editorial_image_approved_id',$aid);
   update_post_meta($id,'_emdo_editorial_cover_asset_id','emdo-commons-'.sanitize_key((string)$a['key']));
   update_post_meta($id,'_emdo_editorial_cover_brand_safe','1');
   update_post_meta($id,'_emdo_cover_photo_release',EMDO_PHOTO_RELEASE);
   if((int)get_post_thumbnail_id($id)!==$aid
      || !str_contains((string)get_post_field('post_content',$id),$credit_marker)
      || !str_contains((string)get_post_meta($id,'_en_US_post_content',true),$credit_marker))
      throw new RuntimeException('Post image/credit verification failed '.$slug);
   $results[]=['slug'=>$slug,'post_id'=>$id,'attachment_id'=>$aid,'url'=>get_permalink($id),'source'=>(string)$a['page'],'alt'=>(string)$a['alt_es']];
 }
} catch(Throwable $e) {
 foreach($snaps as $id=>$snap) {
    wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$snap['es']]),true);
    update_post_meta($id,'_en_US_post_content',$snap['en']);
    foreach($snap['metas'] as $k=>$v) {
       if($v['exists'])update_post_meta($id,$k,$v['value']);
       else delete_post_meta($id,$k);
    }
 }
 foreach(array_reverse($created) as $id)wp_delete_attachment($id,true);
 wp_cache_flush();
 throw $e;
}
wp_cache_flush();
echo 'EMDO_PHOTO_BATCH '.wp_json_encode(['verified'=>count($results)===4,'count'=>count($results),'posts'=>$results],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
