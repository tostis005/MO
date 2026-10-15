<?php
/** Read-only final SEO editorial QA for 18 live bilingual articles and their photographs. */
if(!defined('ABSPATH')) exit(1);
global $wpdb;
$ids=array_map('intval',(array)$wpdb->get_col($wpdb->prepare(
  "SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID
   WHERE p.post_type='post' AND p.post_status='publish'
   AND pm.meta_key=%s AND pm.meta_value LIKE %s",
 '_emdo_quality_v2_batch','quality-v2-20261015-%'
)));
$found=[];$issues=[];$allimages=[];$linkcount=0;$bilingual=0;
$wc=function($html) {
 $text=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$html)));
 preg_match_all('/[\p{L}\p{M}\p{N}]+/u',$text,$m);
 return count($m[0]);
};
$valid_link=function($url,$language) use($wpdb){
 if(!str_starts_with($url,'https://www.elmercadodeorigen.com/'))return true;
 $path=substr($url,strlen('https://www.elmercadodeorigen.com/'));
 $path=explode('?',$path,2)[0];
 $path=trim($path,'/');
 if($path==='')return true;
 if(str_starts_with($path,'en/')) {
   $slug=substr($path,3);
   if(!$slug)return false;
   $count=(int)$wpdb->get_var($wpdb->prepare(
     "SELECT COUNT(*) FROM {$wpdb->postmeta} m INNER JOIN {$wpdb->posts} p ON p.ID=m.post_id
      WHERE m.meta_key='_en_US_post_name' AND m.meta_value=%s
      AND p.post_type='post' AND p.post_status='publish'",$slug));
   return $count>0;
 }
 $p=get_page_by_path($path,OBJECT,'post');
 return $p instanceof WP_Post && $p->post_status==='publish';
};
foreach($ids as $id) {
 $p=get_post($id);if(!$p)continue;
 $es=(string)$p->post_content;$en=(string)get_post_meta($id,'_en_US_post_content',true);
 $sw=$wc($es);$ew=$wc($en);
 $qa=(string)get_post_meta($id,'_emdo_quality_v2_batch',true);
 $aid=(int)get_post_thumbnail_id($id);$allimages[$aid]=true;
 $problems=[];
 if($sw<620)$problems[]='spanish_body_short:'.$sw;
 if($ew<550)$problems[]='english_body_short:'.$ew;
 if(!str_contains($es,'EMDO_QUALITY_V2_20261015_'))$problems[]='missing_es_editorial_marker';
 if(!str_contains($en,'EMDO_QUALITY_V2_20261015_'))$problems[]='missing_en_editorial_marker';
 if(str_contains($es,'Preguntas frecuentes') || str_contains($en,'Frequently asked questions'))$problems[]='generic_faq_section';
 if((string)get_post_meta($id,'_en_US_post_name',true)==='')$problems[]='missing_en_slug';
 if($aid<=0 || get_post_type($aid)!=='attachment')$problems[]='no_valid_featured_image';
 if($aid>0) {
   if(!str_contains($es,'EMDO_PHOTO_CREDIT_20261015_') || !str_contains($en,'EMDO_PHOTO_CREDIT_20261015_'))$problems[]='missing_photo_credit';
   foreach(['_emdo_photo_source_key','_emdo_photo_original_page','_emdo_photo_author','_emdo_photo_license_url','_wp_attachment_image_alt','_en_US_attachment_alt'] as $k)
     if(trim((string)get_post_meta($aid,$k,true))==='')$problems[]='missing_attachment_meta:'.$k;
   if(str_contains(strtolower((string)get_post_meta($aid,'_wp_attachment_image_alt',true)),'provisional'))$problems[]='image_provisional_alt';
 }
 foreach([['es',$es],['en',$en]] as $pair) {
   preg_match_all('~href=[\'"](https?://www\.elmercadodeorigen\.com/[^\'"<>]+)[\'"]~i',$pair[1],$matches);
   foreach($matches[1] as $url) {
      $linkcount++;
      if(!$valid_link($url,$pair[0]))$problems[]='broken_internal_'.$pair[0].':'.$url;
   }
 }
 if($problems)$issues[$p->post_name]=$problems;
 if($sw>=620&&$ew>=550)$bilingual++;
 $found[]=['slug'=>$p->post_name,'id'=>$id,'quality_batch'=>$qa,'es_words'=>$sw,'en_words'=>$ew,
   'image_id'=>$aid,'image_credit'=>($aid>0 && str_contains($es,'EMDO_PHOTO_CREDIT_20261015_')),
   'problems'=>$problems];
}
sort($ids);
$verified=(count($found)===18 && count($allimages)===18 && !isset($allimages[0]) && !$issues);
echo 'EMDO_QUALITY_FINAL_AUDIT '.wp_json_encode([
 'verified'=>$verified,'count'=>count($found),'unique_images'=>count($allimages),
 'internal_links_checked'=>$linkcount,'articles'=>$found,'issues'=>$issues
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$verified)throw new RuntimeException('Final SEO editorial QA found outstanding issues; review JSON above');
