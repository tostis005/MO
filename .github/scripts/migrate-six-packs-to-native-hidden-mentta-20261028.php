<?php
/**
 * Change six existing imported packs to the same native WooCommerce
 * catalog='hidden' + Mentta-only IsVisible override used by Montjam.
 * Test one first, then remaining five; all changes reversible.
 * Env: MDO_HIDE_PACKS_MODE=preflight|apply_one|verify_one|apply_rest|verify_all|rollback_one|rollback_rest
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))exit(2);
$mode=(string)(getenv('MDO_HIDE_PACKS_MODE')?:'preflight');
$states=['preflight','apply_one','verify_one','apply_rest','verify_all','rollback_one','rollback_rest'];
if(!in_array($mode,$states,true))exit(2);
$slugs=[
 21814=>'pack-degustacion-pasaporte-salvaje',
 21818=>'pack-degustacion-safari-africano',
 21820=>'pack-degustacion-vuelta-al-mundo',
 21904=>'pack-cafe-esencia-de-costa-rica',
 21906=>'pack-cafe-ruta-de-los-cuatro-origenes',
 21908=>'pack-cafe-coleccion-completa'
];
$selected=in_array($mode,['apply_one','verify_one','rollback_one'],true)?[21814]:
 (in_array($mode,['apply_rest','rollback_rest'],true)?[21818,21820,21904,21906,21908]:array_keys($slugs));
$exclusive=emdo_mentta_exclusive_ids();
foreach($slugs as $id=>$slug){
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='publish'||$p->get_slug()!==$slug
    ||!in_array((int)$id,array_keys($slugs),true)||
    !in_array(429,array_map('intval',$p->get_category_ids()),true)||
    get_post_meta($id,'_emdo_mentta_only',true)!=='yes'||
    !$p->get_image_id()||!$p->is_in_stock()){
  fwrite(STDERR,"INVALID_PRODUCT_BASELINE $id\n");exit(3);
 }
 // Only allow each pre-test state; don't accidentally re-expose other products.
 if(!in_array($p->get_catalog_visibility(),['visible','hidden'],true))exit(4);
 if(in_array($id,[21904,21906,21908],true)&&(!$p->is_type('variable')||count($p->get_children())!==7))exit(5);
}
if(in_array($mode,['apply_one','apply_rest','rollback_one'],true)){
 if(!get_option('mdo_mentta_exclusives_before_native_packs_20261028',false)){
  add_option('mdo_mentta_exclusives_before_native_packs_20261028',$exclusive,'','no');
 }
 foreach($selected as $id){
  $p=wc_get_product($id);
  $p->set_catalog_visibility(in_array($mode,['rollback_one','rollback_rest'],true)?'visible':'hidden');
  $p->save();
 }
 if(in_array($mode,['rollback_one','rollback_rest'],true))$new=array_values(array_unique(array_merge($exclusive,$selected)));
 else $new=array_values(array_diff($exclusive,$selected));
 update_option('emdo_mentta_exclusive_ids',$new,false);
 foreach($selected as $id){
  clean_post_cache($id);wc_delete_product_transients($id);
  if(function_exists('rocket_clean_post'))rocket_clean_post($id);
 }
 if(function_exists('wp_cache_flush'))wp_cache_flush();
}
foreach($selected as $id){
 $p=wc_get_product($id);$protected=in_array($id,emdo_mentta_exclusive_ids(),true);
 $expectedHidden=!in_array($mode,['preflight','rollback_one','rollback_rest'],true);
 if($mode!=='preflight'&&($p->get_catalog_visibility()!==($expectedHidden?'hidden':'visible')||$protected===$expectedHidden))exit(6);
 $report=['mode'=>$mode,'id'=>$id,'slug'=>$p->get_slug(),'type'=>$p->get_type(),
  'woo_visibility'=>$p->get_catalog_visibility(),'status'=>$p->get_status(),
  'public_exclusive_guard'=>$protected,'stock'=>$p->is_in_stock(),'pvp'=>round(wc_get_price_including_tax($p),2)];
 if(in_array($mode,['verify_one','verify_all'],true)){
  if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
  $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id='.$id;
  $req=new WP_REST_Request('GET','/mentta_marketplace/products');
  $req->set_param('product_id',$id);
  $res=WC_Mentta_Plugin::get_products($req);
  $raw=$res instanceof WP_REST_Response?$res->get_data():null;
  $data=is_object($raw)?($raw->data??[]):($raw['data']??[]);
  $entry=is_array($data)?($data[0]??[]):[];
  $report['mentta_visible']=$entry['IsVisible']??null;
  $report['mentta_stock']=$entry['IsInStock']??null;
  $report['mentta_purchasable']=$entry['IsPurchasable']??null;
  $report['mentta_variations']=count((array)($entry['Variations']??[]));
  if(in_array($id,[21904,21906,21908],true)&&count((array)($entry['Variations']??[]))!==7)exit(9);
  if(($entry['IsVisible']??null)!==true ||($entry['IsInStock']??null)!==true||
     ($entry['IsPurchasable']??null)!==true||(int)($entry['Id']??0)!==$id)exit(7);
 }
 echo 'NATIVE_MENTTA_PRODUCT '.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
echo "NATIVE_MENTTA_MODE_{$mode}_OK\n";
