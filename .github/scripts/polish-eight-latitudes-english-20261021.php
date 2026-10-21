<?php
/** English-only correction of two tokenisation artefacts in eight recently released burger fiches. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!class_exists('MDO_Database'))throw new RuntimeException('CLI supplier sync required');
$hostname=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($hostname,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
global $wpdb;
$source=MDO_Database::table('source_products');$su=MDO_Database::table('suppliers');
$ids=[544,545,546,547,548,552,557,621];$backup=[];$changes=[];$report=['reviewed'=>0,'updated'=>0,'already_clean'=>0,'errors'=>[]];
foreach($ids as $source_id){
  $row=$wpdb->get_row($wpdb->prepare("SELECT sp.wc_product_id,s.code,s.vendor_user_id FROM {$source} sp JOIN {$su} s ON sp.supplier_id=s.id WHERE sp.id=%d",$source_id),ARRAY_A);
  $id=(int)($row['wc_product_id']??0);$p=$id?wc_get_product($id):null;
  if(!$row||$row['code']!=='latitudes'||!$p||$p->get_status()!=='publish'||(int)get_post_field('post_author',$id)!==(int)$row['vendor_user_id']||(string)get_post_meta($id,'_en_US_published',true)!=='1') {
    $report['errors'][]=['source'=>$source_id,'reason'=>'unexpected_identity_or_translation'];continue;
  }
  $report['reviewed']++;
  $body=(string)get_post_meta($id,'_en_US_post_content',true);
  if(!str_contains($body,'<strong>Ingredients:</strong>')||!str_contains($body,'sulphites')){
    $report['errors'][]=['source'=>$source_id,'reason'=>'unexpected_english_content'];continue;
  }
  $clean=preg_replace('/\\balmid[oó]n de corn\\b/iu','corn starch',$body);
  $clean=preg_replace('/\\bgarlic y onion\\b/iu','garlic and onion',$clean);
  if($clean===$body){$report['already_clean']++;continue;}
  $backup[$id]=$body;$changes[$id]=$clean;
}
if(count($report['errors'])||$report['reviewed']!==8) {
 echo 'EMDO_MEAT_EN_QA='.wp_json_encode($report).PHP_EOL;exit(15);
}
if($changes){
 $key='emdo_meat_en_natural_copy_backup_20261021_'.gmdate('YmdHis').'_'.wp_generate_password(5,false,false);
 if(!add_option($key,$backup,'',false))throw new RuntimeException('Backup failed');
 foreach($changes as $id=>$body){
  update_post_meta($id,'_en_US_post_content',$body);
  if((string)get_post_meta($id,'_en_US_post_content',true)!==$body)$report['errors'][]=['id'=>$id,'reason'=>'verification_failed'];
  else $report['updated']++;
 }
 $report['backup']=$key;
}
$report['verified']=!$report['errors']&&$report['reviewed']===$report['updated']+$report['already_clean'];
echo 'EMDO_MEAT_EN_QA='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if(!$report['verified'])exit(16);
