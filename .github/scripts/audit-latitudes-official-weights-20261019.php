<?php
/** Read-only check for supplier's own WooCommerce shipping weights on variable-size cuts. */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(1);
$host=wp_parse_url(site_url('/'),PHP_URL_HOST);
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) WP_CLI::error('Not production');
$ids=get_posts(['post_type'=>'product','post_status'=>'any','author'=>4949,'fields'=>'ids','numberposts'=>-1]);
$total=0;$found=0;$diff=[];$errors=[];
foreach($ids as $id) {
  if(get_post_meta($id,'_mdo_latitudes_weight_basis',true)!=='title_upper_bound') continue;
  $total++;
  $p=wc_get_product($id);
  $url=(string)get_post_meta($id,'_emdo_source_url',true);
  if(!str_starts_with($url,'https://latitudes.es/')){$errors[]=['id'=>$id,'error'=>'url'];continue;}
  $res=wp_safe_remote_get($url,['timeout'=>15,'headers'=>['Accept'=>'text/html'],'user-agent'=>'EMDO product weight audit']);
  if(is_wp_error($res)||wp_remote_retrieve_response_code($res)!==200){$errors[]=['id'=>$id,'error'=>is_wp_error($res)?$res->get_error_message():wp_remote_retrieve_response_code($res)];continue;}
  $html=(string)wp_remote_retrieve_body($res);
  $dom=new DOMDocument();$old=libxml_use_internal_errors(true);$dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NONET|LIBXML_NOERROR|LIBXML_NOWARNING);libxml_clear_errors();libxml_use_internal_errors($old);
  $xp=new DOMXPath($dom);
  $ns=$xp->query("//tr[contains(concat(' ',normalize-space(@class),' '),' woocommerce-product-attributes-item--weight ')]//td");
  $txt=$ns && $ns->length?trim($ns->item(0)->textContent):'';
  if(!preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*kg/ui',$txt,$m)){$errors[]=['id'=>$id,'error'=>'no_page_weight','sample'=>$txt];continue;}
  $source=(float)str_replace(',','.',$m[1]);
  $local=(float)$p->get_weight('edit');
  $found++;
  if(abs($source-$local)>0.0001) $diff[]=['id'=>$id,'name'=>$p->get_name(),'source_kg'=>$source,'set_kg'=>$local,'delta_kg'=>round($source-$local,3)];
}
WP_CLI::log('LWS_OFFICIAL_WEIGHTS='.wp_json_encode(['total_ranges'=>$total,'source_verified'=>$found,'mismatches'=>$diff,'failed'=>$errors],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
WP_CLI::success('Read-only official weight check complete');
