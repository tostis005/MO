<?php
/**
 * Publish Isabel's 96 verified ordinary chocolates and seven protected,
 * non-purchasable personalized catalog entries, no other vendor.
 * Requires official seller shipping and the purchase protection plugin.
 */
if(!defined('ABSPATH'))exit(1);
$h=strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST));
if(!in_array($h,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('Produccion requerida');
if(!class_exists('MDO_Isabel_Quality')||!method_exists('MDO_Isabel_Quality','safe_purchase_gate'))throw new RuntimeException('Falta la protección personalizada en la versión desplegada');
if(!has_filter('woocommerce_is_purchasable',array('MDO_Isabel_Quality','safe_purchase_gate')))throw new RuntimeException('La protección en el carrito no está registrada');
global $wpdb;
$sup=MDO_Database::table('suppliers');$src=MDO_Database::table('source_products');
$v=(int)$wpdb->get_var($wpdb->prepare("SELECT vendor_user_id FROM {$sup} WHERE code=%s LIMIT 1",'chocolates-artesanos-isabel'));
if($v!==4964||!get_user_by('id',$v)||get_user_meta($v,'_disable_vendor',true))throw new RuntimeException('La tienda Isabel no está activada');
// This onboarding publisher is deliberately one-shot. The live catalog is now
// managed by supplier synchronization and targeted editorial policy. Re-running
// the original 103-item release would re-publish excluded gift vouchers and
// protected, non-purchasable customised goods.
$already_published=(int)$wpdb->get_var($wpdb->prepare(
 "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='publish'",$v));
if($already_published>0){
 echo 'ISABEL_ONE_TIME_PUBLISHER_SKIPPED_LIVE_CATALOG=' . $already_published . PHP_EOL;
 exit(0);
}
$ship=get_user_meta($v,'_wcfmmp_shipping',true);
if(!is_array($ship)||($ship['_wcfmmp_user_shipping_type']??'')!=='by_zone'||($ship['_wcfmmp_user_shipping_enable']??'')!=='yes')
 throw new RuntimeException('No se permiten publicaciones sin envíos Isabel');
$methods=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$rows=$wpdb->get_results($wpdb->prepare("SELECT zone_id,method_id,is_enabled,settings FROM {$methods} WHERE vendor_id=%d",$v),ARRAY_A);
$rates=array();foreach($rows as $r){
 if((int)$r['is_enabled']!==1)continue;
 $st=maybe_unserialize($r['settings']);
 if(is_array($st))$rates[(int)$r['zone_id']][(string)$r['method_id']]=$st;
}
foreach(array(1=>array('flat_rate'=>'4.95','free_shipping'=>'59.01'),10=>array('flat_rate'=>'7.95','free_shipping'=>'100.01')) as $zone=>$wanted) {
 foreach($wanted as $method=>$expected) {
  $field=$method==='flat_rate'?'cost':'min_amount';
  if(!isset($rates[$zone][$method])||abs((float)($rates[$zone][$method][$field]??-1)-(float)$expected)>0.0001)
   throw new RuntimeException('Envíos Isabel no verificados: zona '.$zone.' '.$method);
 }
}
$review_ids=array(692,693,700,701,702,757,758);
$products=$wpdb->get_results($wpdb->prepare("SELECT sp.id,sp.status,sp.source_url,sp.wc_product_id,sp.source_payload
 FROM {$src} sp WHERE sp.supplier_id=(SELECT id FROM {$sup} WHERE code=%s LIMIT 1)
 AND (sp.status='active' OR sp.id IN (692,693,700,701,702,757,758))
 ORDER BY sp.id",'chocolates-artesanos-isabel'),ARRAY_A);
if(count($products)!==103)throw new RuntimeException('Se esperaban 103 productos Isabel; encontrados '.count($products));
$standard=0;$special=0;$to_publish=array();$missing_images=array();
foreach($products as $r){
 $id=(int)$r['wc_product_id'];$source_id=(int)$r['id'];$review=in_array($source_id,$review_ids,true);
 if(($review&&(string)$r['status']!=='pending')||(!$review&&(string)$r['status']!=='active'))throw new RuntimeException('Estado fuente inesperado '.$source_id);
 $p=$id?wc_get_product($id):false;
 if(!$p||get_post_type($id)!=='product'||(int)get_post_field('post_author',$id)!==$v)throw new RuntimeException('Producto ajeno o ausente '.$source_id);
 if((string)get_post_meta($id,'_emdo_source_url',true)!==(string)$r['source_url'])throw new RuntimeException('URL origen incompatible '.$source_id);
 if(!in_array($p->get_status(),array('draft','publish'),true))throw new RuntimeException('Estado comercial inesperado '.$source_id);
 if(!$p->get_name()||!trim(wp_strip_all_tags($p->get_description()))||$p->get_price()==='')throw new RuntimeException('Ficha incompleta '.$source_id);
 if($review){
  if((string)get_post_meta($id,'_emdo_isabel_customization_review_required',true)!=='1')throw new RuntimeException('No protegido '.$source_id);
  if($p->is_purchasable())throw new RuntimeException('Producto personalizado comprable antes de publicar '.$source_id);
  $special++;
 }else{$standard++;}
 if(!$p->get_image_id())$missing_images[]=$source_id;
 $to_publish[]=array('source_id'=>$source_id,'wc_id'=>$id,'protected'=>$review);
}
if($standard!==96||$special!==7)throw new RuntimeException('Inventario inesperado');
// No writes before full shipping + product preflight. Publication is idempotent.
$published=array();$errors=array();
foreach($to_publish as $entry){
 try{
  $p=wc_get_product($entry['wc_id']);
  $p->set_status('publish');
  $p->set_catalog_visibility('visible');
  $p->save();
  if($entry['protected']){
   $note='Producto personalizable. Las opciones del fabricante aún están pendientes de configuración. Solicita información antes de realizar el pedido.';
   if(!str_contains((string)$p->get_short_description(),'Las opciones del fabricante')){
    $p->set_short_description(wp_kses_post((string)$p->get_short_description()).'<p><strong>'.esc_html($note).'</strong></p>');
    $p->save();
   }
  }
  $check=wc_get_product($entry['wc_id']);
  if(!$check||$check->get_status()!=='publish'||$check->get_catalog_visibility()!=='visible')throw new RuntimeException('Publicación sin persistencia');
  if($entry['protected']&&$check->is_purchasable())throw new RuntimeException('Personalización quedó comprable');
  $published[]=$entry['source_id'];
 }catch(Throwable $e){$errors[]=array('id'=>$entry['source_id'],'error'=>mb_substr($e->getMessage(),0,150));}
}
$live=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='publish'",$v));
$hidden=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_author=%d AND post_type='product' AND post_status='draft'",$v));
echo 'ISABEL_PUBLICATION_RESULT='.wp_json_encode(array('standard'=>$standard,'personalized_protected'=>$special,
 'published_in_run'=>count($published),'total_vendor_published'=>$live,'vendor_drafts'=>$hidden,
 'missing_original_images'=>$missing_images,'errors'=>$errors,'shipping_verified'=>true),
 JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
if($errors||count($published)!==103||$live!==103||$hidden!==0)throw new RuntimeException('La publicación no ha pasado toda la verificación');
