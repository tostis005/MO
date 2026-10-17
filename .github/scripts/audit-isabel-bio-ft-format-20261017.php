<?php
/** Read only: audit supplier BIO&FT spelling in source vs imported Woo titles. */
if(!defined('ABSPATH'))exit(1);
global $wpdb;
$host=strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST));
if(!in_array($host,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('Only production');
$sup=MDO_Database::table('suppliers');$products=MDO_Database::table('source_products');
$vendor=(int)$wpdb->get_var($wpdb->prepare("SELECT vendor_user_id FROM {$sup} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'));
if($vendor!==4964)throw new RuntimeException('Unexpected vendor');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,source_payload,wc_product_id,status,source_url FROM {$products} WHERE supplier_id=(SELECT id FROM {$sup} WHERE code=%s) ORDER BY id",'chocolates-artesanos-isabel'),ARRAY_A);
$summary=array('total'=>count($rows),'woo'=>0,'source_bio_ft'=>0,'woo_bio_ft'=>0,'woo_lowercase_bio_ft'=>0,
 'source_uppercase_bio_ft'=>0,'source_other_casing'=>0,'html_escaped_title'=>0,'other_mismatch'=>0,'samples'=>array(),'bad_entities'=>array());
foreach($rows as $r){
 $raw=json_decode((string)$r['source_payload'],true);
 $src=(string)($raw['title']??$r['title']);
 $wc=(int)$r['wc_product_id']?wc_get_product((int)$r['wc_product_id']):false;
 if(!$wc)continue;
 if((int)get_post_field('post_author',$wc->get_id())!==$vendor)throw new RuntimeException('Vendor mismatch');
 $summary['woo']++;
 $actual=$wc->get_name();
 if(preg_match('/bio\s*&\s*ft/iu',$src)){$summary['source_bio_ft']++;if(preg_match('/BIO\s*&\s*FT/u',$src))$summary['source_uppercase_bio_ft']++;else $summary['source_other_casing']++;}
 if(preg_match('/bio\s*&\s*ft/iu',$actual)){
  $summary['woo_bio_ft']++;
  if(!preg_match('/BIO\s*&\s*FT/u',$actual))$summary['woo_lowercase_bio_ft']++;
  if(count($summary['samples'])<25)$summary['samples'][]=array('id'=>$r['id'],'source'=>$src,'woo'=>$actual);
 }
 if(preg_match('/&(?:amp|(?:amp;)+|lt|gt|quot|#\d+)(?:;|\b)/iu',$actual)){
  $summary['html_escaped_title']++;if(count($summary['bad_entities'])<8)$summary['bad_entities'][]=array('id'=>$r['id'],'woo'=>$actual);
 }
 $expected=MDO_Text::normalize_title($src);
 if($actual!==$expected && !str_contains($actual,'BIO&FT')){
  $summary['other_mismatch']++;
 }
}
echo 'ISABEL_BIOFT_AUDIT='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
