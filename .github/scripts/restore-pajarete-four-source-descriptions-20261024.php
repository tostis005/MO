<?php
/**
 * Restore exact verified manufacturer product descriptions on four new
 * Pajarete Woo products where third-party save hooks expanded the text.
 * Scope only source IDs 1080-1083 and their linked Woo IDs.
 */
if(!defined('ABSPATH')||!class_exists('WooCommerce')||!class_exists('MDO_Database'))throw new RuntimeException('WP required');
$host=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Prod only');
global $wpdb;
$sp=MDO_Database::table('suppliers');$table=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sp} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974)throw new RuntimeException('Supplier mismatch');
$ids=[1080,1081,1082,1083];
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,source_url,status,source_payload,wc_product_id FROM {$table} WHERE supplier_id=%d AND id IN (1080,1081,1082,1083) ORDER BY id",(int)$s['id']),ARRAY_A);
if(count($rows)!==4)throw new RuntimeException('Unexpected rows');
$norm=static function($html):string{
  $v=html_entity_decode(wp_strip_all_tags((string)$html),ENT_QUOTES|ENT_HTML5,'UTF-8');
  $v=preg_replace('/[\x{00A0}\x{200B}\x{202F}]/u',' ',$v);
  return trim((string)preg_replace('/\s+/u',' ',$v));
};
$phase=getenv('PAJARETE_COPY_PHASE')?:'audit';
if(!in_array($phase,['audit','apply','verify'],true))throw new RuntimeException('Unknown phase');
$before=[];$changed=0;$bad=[];$out=[];
foreach($rows as $r){
  $id=(int)$r['id'];$pid=(int)$r['wc_product_id'];$p=$pid?wc_get_product($pid):null;
  $src=json_decode((string)$r['source_payload'],true);
  if($r['status']!=='active'||!$p||(int)get_post_field('post_author',$pid)!==4974||
    $p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible'||
    !is_array($src)||empty($src['source_api_product_id'])||
    !$src['description'])throw new RuntimeException('Invalid source/product '.$id);
  $canonical=wp_kses_post((string)$src['description']);
  if(mb_strlen($norm($canonical))<100||mb_strlen($norm($canonical))>2200)throw new RuntimeException('Official copy not plausible '.$id);
  $local=(string)$p->get_description();
  $before[$id]=['source_id'=>$id,'woo_id'=>$pid,'old_chars'=>mb_strlen($norm($local)),
    'official_chars'=>mb_strlen($norm($canonical)),
    'same'=>$norm($local)===$norm($canonical)];
  if($phase==='apply'&&$norm($local)!==$norm($canonical)){
    $key='_mdo_pajarete_source_copy_backup_20261024_'.$id;
    if(!get_option($key,false))add_option($key,['wc_id'=>$pid,'old_html'=>$local],'','no');
    $updated=$wpdb->update($wpdb->posts,['post_content'=>$canonical],['ID'=>$pid],['%s'],['%d']);
    if($updated===false)throw new RuntimeException('Cannot save source copy '.$id);
    clean_post_cache($pid);
    if(function_exists('wc_delete_product_transients'))wc_delete_product_transients($pid);
    $changed++;
  }
  $fresh=wc_get_product($pid);
  if($phase!=='audit'&&$norm($fresh->get_description())!==$norm($canonical))$bad[]=$id;
  $out[]=['id'=>$id,'wc'=>$pid,'long_chars'=>mb_strlen($norm($fresh->get_description())),
       'matches_source'=>$norm($fresh->get_description())===$norm($canonical)];
}
echo 'PAJARETE_NEW_COPY='.wp_json_encode(['phase'=>$phase,'before'=>$before,
   'changed'=>$changed,'after'=>$out,'mismatches'=>$bad],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
if($bad)throw new RuntimeException('Original content restoration not verified');
