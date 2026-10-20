<?php
/** Tropitop: idempotent WCFM vendor + Bacon variable product setup, draft-only. */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(1);
foreach(['home','siteurl'] as $opt) {
  $host=strtolower((string)wp_parse_url(get_option($opt),PHP_URL_HOST));
  if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong production host');
}
foreach(['MDO_Database','MDO_Connector_Tropitop','MDO_Woo_Importer','MDO_Tropitop_Shipping','WooCommerce','WCFMmp_Shipping_Zone'] as $class)
 if(!class_exists($class))throw new RuntimeException('Missing required class '.$class);
if(get_option('woocommerce_weight_unit')!=='kg')throw new RuntimeException('Expected kg weight unit');
global $wpdb;
$sup=$wpdb->prefix.'mdo_suppliers';$src=$wpdb->prefix.'mdo_source_products';
$r=$wpdb->get_row($wpdb->prepare("SELECT * FROM $sup WHERE code=%s",'tropitop'),ARRAY_A);
if(!$r || (int)$r['active']!==0)throw new RuntimeException('Expected existing inactive Tropitop supplier');
$vendor=get_user_by('login','tropitop');
if($vendor && !in_array('wcfm_vendor',(array)$vendor->roles,true))throw new RuntimeException('Tropitop login conflict');
if($vendor && (int)($r['vendor_user_id']??0) && (int)$r['vendor_user_id']!==(int)$vendor->ID)throw new RuntimeException('Supplier vendor collision');
if($vendor && !get_user_meta($vendor->ID,'_disable_vendor',true))throw new RuntimeException('Already active vendor; refusing draft reset');
if(!$vendor) {
 $emails=(array)$wpdb->get_col("SELECT user_email FROM {$wpdb->users} WHERE user_email LIKE 'tostis005+%@%'");
 $domains=[];$max=-1;
 foreach($emails as $email)if(preg_match('/^tostis005\+(\d+)@([^@]+)$/i',$email,$m)){
   $max=max($max,(int)$m[1]);$domains[strtolower($m[2])]=($domains[strtolower($m[2])]??0)+1;
 }
 if($max<0||!$domains)throw new RuntimeException('Unknown vendor mailbox convention');
 arsort($domains);$email='';
 foreach(range($max+1,$max+99) as $i) {
   $candidate='tostis005+'.$i.'@'.array_key_first($domains);
   if(!email_exists($candidate)){$email=$candidate;break;}
 }
 if(!$email)throw new RuntimeException('No available internal alias');
 $id=wp_insert_user(['user_login'=>'tropitop','user_nicename'=>'tropitop','user_email'=>$email,
  'display_name'=>'Tropitop','first_name'=>'Tropitop','role'=>'wcfm_vendor','user_pass'=>wp_generate_password(36,true,true)]);
 if(is_wp_error($id))throw new RuntimeException($id->get_error_message());
 $vendor=get_user_by('id',$id);
}
$vid=(int)$vendor->ID;
update_user_meta($vid,'_disable_vendor',1);
if((int)$r['vendor_user_id']!==$vid) {
 if(false===$wpdb->update($sup,['vendor_user_id'=>$vid],['id'=>(int)$r['id']]))throw new RuntimeException('EMDO supplier vendor relation failed');
}
$fruit=get_term_by('slug','frutas','product_cat');
if(!$fruit) {
 $x=wp_insert_term('Frutas','product_cat',['slug'=>'frutas','description'=>'Fruta fresca de temporada de productores españoles.']);
 if(is_wp_error($x))throw new RuntimeException($x->get_error_message());
 $fruit=get_term((int)$x['term_id'],'product_cat');
}
$avocado=get_term_by('slug','aguacates','product_cat');
if(!$avocado){
 $x=wp_insert_term('Aguacates','product_cat',['slug'=>'aguacates','parent'=>(int)$fruit->term_id,
 'description'=>'Aguacates de origen español, variedad Bacon y otras selecciones de temporada.']);
 if(is_wp_error($x))throw new RuntimeException($x->get_error_message());
 $avocado=get_term((int)$x['term_id'],'product_cat');
}
$bio='<p>Tropitop es un proyecto de Nature Tropical Fruit, S.L.U., dedicado a las frutas frescas de origen mediterráneo. Cultiva variedades de aguacate en Artana (Castellón), en el entorno de la Sierra de Espadán, y selecciona sus frutos por temporada.</p>';
$profile=get_user_meta($vid,'wcfmmp_profile_settings',true);$profile=is_array($profile)?$profile:[];
$profile=array_merge($profile,['user_name'=>'tropitop','user_email'=>$vendor->user_email,'store_name'=>'Tropitop',
 'store_slug'=>'tropitop','vendor_id'=>$vid,'shop_description'=>$bio,'store_hide_email'=>'yes',
 'store_hide_phone'=>'yes','store_hide_address'=>'yes']);
update_user_meta($vid,'store_name','Tropitop');update_user_meta($vid,'_store_description',$bio);
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$logo_url='https://tropitop.com/img/logo-1742202050.jpg';
$logo=(int)get_user_meta($vid,'_mdo_tropitop_logo_attachment_id',true);
if(!$logo || get_post_type($logo)!=='attachment') {
 $logo=media_sideload_image($logo_url,0,'Logotipo de Tropitop — productor de fruta fresca','id');
 if(is_wp_error($logo))throw new RuntimeException('Logo not imported '.$logo->get_error_message());
 update_user_meta($vid,'_mdo_tropitop_logo_attachment_id',(int)$logo);
}
$profile['gravatar']=(string)$logo;
update_user_meta($vid,'wcfmmp_profile_settings',$profile);
// First refresh remote data. Do not turn an incomplete variant selector into a
// purchasable simple product.
$url='https://tropitop.com/es/aguacates/33-comprar-aguacate-bacon.html';
$payload=MDO_Connector_Tropitop::scrape_product($url);
if((string)$payload['source_product_id']!=='33' || (int)$payload['variation_count']!==3
 || (int)$payload['image_count']<8 || $payload['stock_status']!=='instock')
 throw new RuntimeException('Tropitop live product does not pass stock/image/variation audit');
$matrix=[];foreach($payload['variations'] as $v) {
 $matrix[(int)$v['weight_kg']]=(float)$v['display_price'];
 if(!$v['is_in_stock'])throw new RuntimeException('An avocado variation is out of stock');
}
ksort($matrix);
if(array_keys($matrix)!==[3,4,9])throw new RuntimeException('Unexpected sizes '.wp_json_encode($matrix));
MDO_Connector_Tropitop::upsert_product((int)$r['id'],$payload);
$source_id=(int)$wpdb->get_var($wpdb->prepare("SELECT id FROM $src WHERE supplier_id=%d AND source_url=%s",(int)$r['id'],$url));
if(!$source_id)throw new RuntimeException('Missing product after synchronization');
$product_id=MDO_Woo_Importer::import_source_product($source_id);
$p=wc_get_product($product_id);
if(!$p instanceof WC_Product_Variable)throw new RuntimeException('Expected variable product');
$ids=$p->get_children();$weights=[];foreach($ids as $child_id){
 $child=wc_get_product($child_id);$weights[]=(float)$child->get_weight();
}
sort($weights);
if($p->get_status()!=='draft'||$p->get_catalog_visibility()!=='hidden'||$weights!==[3.0,4.0,9.0]
  || !has_term((int)$avocado->term_id,'product_cat',$product_id)
  || count($p->get_gallery_image_ids())<7 || !$p->get_image_id())
 throw new RuntimeException('Draft product validation failed: status, weights, taxonomy or image gallery');
$settings=get_user_meta($vid,'wcfmmp_profile_settings',true);$settings=is_array($settings)?$settings:[];
if(empty($settings['banner'])) {
 $settings['banner']=(string)$p->get_image_id();
 $settings['banner_type']='image';
 $settings['mobile_banner']=(string)$p->get_image_id();
 $settings['list_banner_type']='image';
 $settings['list_banner']=(string)$p->get_image_id();
 update_user_meta($vid,'wcfmmp_profile_settings',$settings);
}
// Scope a WCFM flat-rate shipping method to this vendor / mainland zone.
// The EMDO filter replaces the amount using the verified weight calculator;
// never touch a zone/method belonging to another vendor.
$zone=new WC_Shipping_Zone(1);
if($zone->get_zone_name()!=='España (península)')throw new RuntimeException('Unexpected mainland WC zone');
$methods=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$old=(array)$wpdb->get_results($wpdb->prepare("SELECT * FROM $methods WHERE vendor_id=%d",$vid),ARRAY_A);
foreach($old as $row) if((int)$row['zone_id']!==1 || $row['method_id']!=='flat_rate')
 throw new RuntimeException('Conflicting shipping configuration for this vendor');
$settings=['title'=>'Envío Tropitop — península','cost'=>'2.95','tax_status'=>'none',
 'description'=>'','class_cost_no_class_cost'=>'','calculation_type'=>''];
if(!$old){
 $ok=$wpdb->insert($methods,['method_id'=>'flat_rate','zone_id'=>1,'vendor_id'=>$vid,
  'is_enabled'=>1,'settings'=>maybe_serialize($settings)]);
 if(false===$ok)throw new RuntimeException('Could not create vendor shipping method: '.$wpdb->last_error);
}else{
 $ok=$wpdb->update($methods,['is_enabled'=>1,'settings'=>maybe_serialize($settings)],
  ['instance_id'=>(int)$old[0]['instance_id'],'vendor_id'=>$vid]);
 if(false===$ok)throw new RuntimeException('Could not update WCFM rate');
}
$ship=get_user_meta($vid,'_wcfmmp_shipping',true);$ship=is_array($ship)?$ship:[];
$ship['_wcfmmp_user_shipping_enable']='yes';$ship['_wcfmmp_user_shipping_type']='by_zone';
update_user_meta($vid,'_wcfmmp_shipping',$ship);
update_option('mdo_tropitop_shipping_rates_verified','0',false);
// Exercise the full WooCommerce shipping hook using a temporary, request-only
// filter: it simulates verified readiness without opening live checkout.
$fake_ready=static fn()=>'1';
add_filter('pre_option_mdo_tropitop_shipping_rates_verified',$fake_ready,10,0);
$shipping_samples=[3=>2.95,4=>2.95,6=>5.95,8=>5.95,9=>5.95,
 12=>7.95,18=>10.95,20=>10.95,24=>12.95,27=>15.95,36=>20.95];
try {
 foreach($shipping_samples as $kg=>$expected) {
   $fake=new WC_Product_Simple();
   $fake->set_weight((string)$kg);
   $package=['contents'=>[['product_id'=>$product_id,'data'=>$fake,'quantity'=>1]],
     'vendor_id'=>$vid,'destination'=>['country'=>'ES','postcode'=>'28001','state'=>'M']];
   $rate=new WC_Shipping_Rate('wcfmmp_product_shipping_by_zone:test','Tropitop',2.95,[],'wcfmmp_product_shipping_by_zone',1);
   $result=MDO_Tropitop_Shipping::filter_rates(['native'=>$rate],$package);
   $actual=$result?(float)reset($result)->get_cost():null;
   if($actual===null || abs($actual-$expected)>0.001)throw new RuntimeException('Bad shipping QA at '.$kg.'kg: '.var_export($actual,true));
 }
 $banned=['ES'=>'07001','PT'=>'1000-001','FR'=>'75001'];
 foreach($banned as $country=>$postcode) {
   $package['destination']=['country'=>$country,'postcode'=>$postcode];
   if(MDO_Tropitop_Shipping::filter_rates(['native'=>$rate],$package)!==[])
     throw new RuntimeException('Unverified destination was not blocked '.$country);
 }
} finally {
 remove_filter('pre_option_mdo_tropitop_shipping_rates_verified',$fake_ready,10);
}
if(MDO_Tropitop_Shipping::is_ready())throw new RuntimeException('Shipping unexpectedly enabled');
echo 'TROPITOP_SHIPPING_SMOKE_OK weight_samples='.count($shipping_samples)."\\n";
echo 'TROPITOP_DRAFT_QA='.wp_json_encode([
 'vendor_id'=>$vid,'supplier_id'=>(int)$r['id'],'product_id'=>$product_id,
 'status'=>$p->get_status(),'category_id'=>(int)$avocado->term_id,
 'variations'=>$matrix,'images'=>1+count($p->get_gallery_image_ids()),
 'logo_id'=>(int)$logo,'shipping_zone'=>1,'supplier_active'=>0,'checkout_ready'=>false
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
