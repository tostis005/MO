<?php
/**
 * Production-only conversion of the URLs and short copy for six existing products.
 * Does not change IDs, prices, SKUs, categories, sales status, gallery or importer.
 * Run: MDO_PACK_PUBLIC_MODE=preflight|apply|verify wp eval-file FILE
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(2);
$mode=(string)(getenv('MDO_PACK_PUBLIC_MODE')?:'preflight');
if(!in_array($mode,['preflight','apply','verify'],true))exit(2);
$cfg=[
 21814=>[
  'old'=>'pack-musee-mentta-pasaporte-salvaje','slug'=>'pack-degustacion-pasaporte-salvaje',
  'short'=>'Descubre una selección de nueve referencias de carnes exóticas para disfrutar de una degustación diferente en casa. Una propuesta para explorar nuevos sabores, cortes y texturas.'
 ],
 21818=>[
  'old'=>'pack-musee-mentta-safari-africano','slug'=>'pack-degustacion-safari-africano',
  'short'=>'Una selección de cebra, avestruz y cocodrilo para disfrutar de sabores sorprendentes. Ideal para descubrir y compartir nuevas experiencias gastronómicas.'
 ],
 21820=>[
  'old'=>'pack-musee-mentta-vuelta-al-mundo','slug'=>'pack-degustacion-vuelta-al-mundo',
  'short'=>'Diez referencias de carnes del mundo reunidas en un solo pack de degustación. Explora distintas especies y preparaciones sin salir de casa.'
 ],
 21904=>[
  'old'=>'pack-cafe-especialidad-esencia-costa-rica-ikore','slug'=>'pack-cafe-esencia-de-costa-rica',
  'short'=>'Tres cafés de especialidad 100 % arábica de Costa Rica en bolsas de 250 g. Descubre distintos perfiles de una misma tierra y elige la molienda adecuada para tu cafetera.'
 ],
 21906=>[
  'old'=>'pack-cafe-especialidad-ruta-cuatro-origenes-ikore','slug'=>'pack-cafe-ruta-de-los-cuatro-origenes',
  'short'=>'Cuatro cafés de especialidad 100 % arábica de Costa Rica, Colombia, Kenia y Panamá. Cuatro bolsas de 250 g y siete opciones de molienda, sin variación de precio.'
 ],
 21908=>[
  'old'=>'pack-cafe-especialidad-coleccion-completa-ikore','slug'=>'pack-cafe-coleccion-completa',
  'short'=>'La colección completa de seis cafés de especialidad Ikore Kofi. Seis bolsas de 250 g de cuatro países, para una degustación de aromas y perfiles únicos. Elige tu molienda.'
 ],
];
$exclusive=function_exists('emdo_mentta_exclusive_ids')?emdo_mentta_exclusive_ids():[];
foreach($cfg as $id=>$item) {
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible'
 ||!in_array($id,$exclusive,true)||!$p->get_image_id()
 ||!in_array($p->get_slug(),[$item['old'],$item['slug']],true)){
  fwrite(STDERR,"PRODUCT_BASELINE_FAILURE $id\n");exit(3);
 }
 $post=get_post($id);
 if(!$post||$post->post_type!=='product'||!$post->post_author)exit(3);
 $matches=get_posts(['post_type'=>'product','post_status'=>'any','name'=>$item['slug'],'fields'=>'ids','numberposts'=>5,'suppress_filters'=>true]);
 if(array_diff(array_map('intval',$matches),[$id])){
  fwrite(STDERR,"SLUG_ALREADY_IN_USE $id\n");exit(4);
 }
 $description=$p->get_description('edit');
 if(preg_match('/\b(?:mentta|menta|importador|exportador)\b/iu',wp_strip_all_tags($description))) {
  fwrite(STDERR,"DESCRIPTION_HAS_TECHNICAL_TEXT $id\n");exit(5);
 }
 if($mode==='apply'){
  $p->set_slug($item['slug']);
  $p->set_short_description('<p>'.esc_html($item['short']).'</p>');
  $p->save();
  wc_delete_product_transients($id);clean_post_cache($id);
 }
 $fresh=wc_get_product($id);
 if($mode!=='preflight'&&
 ($fresh->get_slug()!==$item['slug'] || preg_match('/mentta|importador|exportador/iu',
  wp_strip_all_tags($fresh->get_short_description('edit')))))exit(6);
 echo 'CUSTOMER_PACK_PRODUCT '.wp_json_encode(['mode'=>$mode,'id'=>$id,
 'slug'=>$fresh->get_slug(),'type'=>$fresh->get_type(),
 'price_incl_tax'=>round((float)wc_get_price_including_tax($fresh),2),
 'category_ids'=>$fresh->get_category_ids(),
 'status'=>$fresh->get_status(),'stock'=>$fresh->is_in_stock(),
 'exclusive_in_shop'=>in_array($id,$exclusive,true),
 'description_nonempty'=>trim(wp_strip_all_tags($fresh->get_description('edit')))!=='',
 'public_copy_clean'=>$mode==='preflight'?null:!preg_match('/mentta|importador|exportador/iu',
   wp_strip_all_tags($fresh->get_short_description('edit').' '.$fresh->get_description('edit'))),
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
if($mode==='verify'&&class_exists('WC_Mentta_Plugin')) {
 if(!defined('REST_REQUEST'))define('REST_REQUEST',true);
 $_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products';
 foreach(array_keys($cfg) as $id) {
  $req=new WP_REST_Request('GET','/mentta_marketplace/products');
  $req->set_param('product_id',$id);
  $result=WC_Mentta_Plugin::get_products($req);
  if(!($result instanceof WP_REST_Response)||$result->get_status()!==200)exit(7);
  $body=$result->get_data();
  $items=is_object($body)?($body->data??[]):($body['data']??[]);
  $entry=is_array($items)?($items[0]??[]):[];
  if(!is_array($entry)||(int)($entry['Id']??0)!==$id||
     ($entry['IsVisible']??null)!==true||($entry['IsInStock']??null)!==true||
     ($entry['IsPurchasable']??null)!==true||
     !in_array(429,array_map('intval',$entry['Categories']??[]),true))exit(8);
  echo 'CUSTOMER_PACK_FEED_UNCHANGED '.wp_json_encode(['id'=>$id,'status'=>200,'visible'=>true,'stock'=>true])."\n";
 }
}
if($mode==='apply'){
 foreach(array_keys($cfg) as $id)if(function_exists('rocket_clean_post'))rocket_clean_post($id);
 if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
 wp_cache_flush();
}
echo "SIX_CUSTOMER_FACING_PRODUCTS_$mode_OK\n";
