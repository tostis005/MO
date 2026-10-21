<?php
/**
 * Revert ONLY 18 unapproved machine-generated English product metas from
 * a specific persisted WordPress backup. Keep Spanish, order options and
 * producer source import untouched. Preflight defaults to read-only.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('MDO_Database')) throw new RuntimeException('WP CLI expected');
$host=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong production host');
$backup_key='mdo_english_products_backup_20261020_20261021_100413_EQXdIpEl';
$mode=(string)(getenv('EMDO_REVERT_TROPITOP_MODE')?:'audit');
if(!in_array($mode,['audit','apply'],true))throw new RuntimeException('Invalid mode');
$back=get_option($backup_key);
if(!is_array($back)||!isset($back['before'])||!is_array($back['before'])||count($back['before'])!==18)
 throw new RuntimeException('Expected exactly 18 metadata backups');
global $wpdb;
$supplier=MDO_Database::table('suppliers');$sp=MDO_Database::table('source_products');
$target=$wpdb->get_row($wpdb->prepare("SELECT id,vendor_user_id FROM {$supplier} WHERE code=%s",'tropitop'),ARRAY_A);
if(!$target)throw new RuntimeException('Tropitop source missing');
$valid=['_en_US_post_title','_en_US_post_name','_en_US_post_excerpt','_en_US_post_content','_en_US_published','_en_US_ready'];
$report=['mode'=>$mode,'backup_items'=>18,'reviewed'=>0,'restored'=>0,'already_original'=>0,'skipped'=>[],'verified'=>false];
$ready=[];
foreach($back['before'] as $id=>$before){
 $id=(int)$id;
 $p=$id?get_post($id):null;
 $linked=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$sp} WHERE supplier_id=%d AND wc_product_id=%d",(int)$target['id'],$id));
 if(!$p||$p->post_type!=='product'||$p->post_status!=='publish'||
  (int)$p->post_author!==(int)$target['vendor_user_id']||
  (int)$linked<1||!is_array($before)||count($before)!==6||
  array_diff(array_keys($before),$valid)||
  count(array_intersect(array_keys($before),$valid))!==6){
   $report['skipped'][]=['id'=>$id,'reason'=>'identity_or_backup_mismatch'];continue;
 }
 $report['reviewed']++;
 $same=true;
 foreach($before as $key=>$snapshot){
  if(!is_array($snapshot)||!array_key_exists('exists',$snapshot)||!array_key_exists('value',$snapshot)){
   $report['skipped'][]=['id'=>$id,'reason'=>'invalid_snapshot'];continue 2;
  }
  $exists=metadata_exists('post',$id,$key);
  if($exists!==(bool)$snapshot['exists']||
     (string)get_post_meta($id,$key,true)!==(string)$snapshot['value'])$same=false;
 }
 if($same){$report['already_original']++;continue;}
 if((string)get_post_meta($id,'_en_US_published',true)!=='1'){
  $report['skipped'][]=['id'=>$id,'reason'=>'translation_was_changed_elsewhere'];continue;
 }
 $ready[$id]=$before;
}
if($report['reviewed']!==18||$report['skipped']){
 echo 'EMDO_TROPITOP_EN_ROLLBACK='.wp_json_encode($report).PHP_EOL;exit(15);
}
if($mode==='apply')foreach($ready as $id=>$before){
 foreach($before as $key=>$snapshot){
   if($snapshot['exists'])update_post_meta($id,$key,$snapshot['value']);
   else delete_post_meta($id,$key);
 }
 clean_post_cache($id);
 $report['restored']++;
}
$good=true;
if($mode==='apply')foreach($back['before'] as $id=>$before)
 foreach($before as $key=>$snapshot)
  if(metadata_exists('post',(int)$id,$key)!==(bool)$snapshot['exists']||
     (string)get_post_meta((int)$id,$key,true)!==(string)$snapshot['value'])$good=false;
$report['verified']=$good;
$report['completed']=$good&&($mode==='audit'||$report['restored']+$report['already_original']===18);
echo 'EMDO_TROPITOP_EN_ROLLBACK='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$report['completed'])exit(16);
