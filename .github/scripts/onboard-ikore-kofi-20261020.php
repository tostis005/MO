<?php
/** One-time manual catalog provisioning. No supplier connector or scheduler. */
if (!defined('ABSPATH')) exit(1);
foreach (array('home','siteurl') as $key) {
 if (!in_array(strtolower((string)wp_parse_url(get_option($key),PHP_URL_HOST)),array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)) throw new RuntimeException('Production required');
}
if (!class_exists('WC_Product_Variable') || !get_role('wcfm_vendor')) throw new RuntimeException('WooCommerce/WCFM required');
global $wpdb;
$tax_class=$wpdb->get_var("SELECT tax_rate_class FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country='ES' AND ABS(tax_rate-10)<0.0001 ORDER BY tax_rate_order LIMIT 1");
if($tax_class===null) {
 $tax_class='iva-cafe-10';
 if(!in_array($tax_class,WC_Tax::get_tax_class_slugs(),true)) {
  $created=WC_Tax::create_tax_class('IVA café 10',$tax_class);
  if(is_wp_error($created))throw new RuntimeException($created->get_error_message());
 }
 $exists=$wpdb->get_var($wpdb->prepare("SELECT tax_rate_id FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_class=%s AND tax_rate_country='ES' AND ABS(tax_rate-10)<0.0001",$tax_class));
 if(!$exists) WC_Tax::_insert_tax_rate(array('tax_rate_country'=>'ES','tax_rate_state'=>'','tax_rate'=>'10.0000','tax_rate_name'=>'IVA 10%','tax_rate_priority'=>1,'tax_rate_compound'=>0,'tax_rate_shipping'=>1,'tax_rate_order'=>0,'tax_rate_class'=>$tax_class));
}
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$catalog=array(
 array('46002/V0112','Panamá Chiriquí','chiriqui','20.00','panama-1-600x600.jpg','2026/06','Panamá','1650–1700','Natural','86','Fruta madura, chocolate, caramelo dulce y especias','Un café de perfil frutal y dulce, con matices de cacao y un final especiado.'),
 array('46002/V0116','Colombia El Indio','el-indio','18.00','colombia-el-indio-1-600x600.jpg','2026/06','Colombia','1500–2150','Natural','87','Fruta de la pasión, chocolate, vino dulce, naranja y frambuesa','Una selección de edición limitada con una combinación expresiva de fruta tropical, cítricos y frutos rojos.'),
 array('46002/V0122','Costa Rica Villa Sarchí Miel','villa-sarchi','12.90','costa-rica-villa-1-1-600x600.jpg','2026/09','Costa Rica','1500','Honey','86','Miel, nueces, mantequilla, vainilla, caramelo y manzana verde','Un perfil dulce y redondo, con recuerdos de frutos secos y un contraste fresco de manzana verde.'),
 array('46002/V0123','Costa Rica Typica Mejorado','typica-mejorado','12.90','costa-rica-typica-1-1-600x600.jpg','2026/09','Costa Rica','1500','Yellow Honey','86','Caramelo, caña de azúcar, mantequilla, chocolate negro y naranja','Un café que combina un fondo dulce y de cacao con un matiz cítrico de naranja.'),
 array('46002/V0127','Kenia Washed Adumu','adumu','18.00','kenia-adumu-ab-4-1-600x600.jpg','2026/09','Kenia','1550–2000','Lavado','84','Azúcar moreno, cereza, frambuesa y chocolate con leche','Una selección de Kenia con notas de frutos rojos sobre una base dulce de azúcar moreno y cacao.'),
 array('46002/V0124','Costa Rica Geisha','geisha','18.00','costa-rica-geisha-1-600x600.jpg','2026/09','Costa Rica','1500','Honey','87','Jazmín, fruta de hueso, pasas, vainilla, azúcar moreno y lima','Un perfil aromático y floral, con fruta de hueso y un contraste cítrico que acompaña su dulzor.')
);
// Resolve original media before making any catalog changes. Reuse on retries.
$media=function($url,$label) use ($wpdb) {
 if (wp_parse_url($url,PHP_URL_HOST)!=='ikorekofi.com') throw new RuntimeException('Unexpected asset host');
 $id=(int)$wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_mdo_ikore_original_url' AND meta_value=%s LIMIT 1",$url));
 if (!$id || !get_attached_file($id) || !file_exists(get_attached_file($id))) {
  $id=media_sideload_image($url,0,$label,'id');
  if (is_wp_error($id)) throw new RuntimeException('Official image failed: '.$label.' '.$id->get_error_message());
  update_post_meta($id,'_mdo_ikore_original_url',$url);
 }
 update_post_meta($id,'_wp_attachment_image_alt',$label);
 return (int)$id;
};
$logo=$media('https://ikorekofi.com/wp-content/uploads/2024/10/logo.png','Ikore Kofi — logotipo oficial');
$banner=$media('https://ikorekofi.com/wp-content/uploads/2025/03/taira-home-e1742810712658.jpg','Ikore Kofi — tostando café en su obrador');
$images=array();
foreach($catalog as $r) $images[$r[0]]=$media('https://ikorekofi.com/wp-content/uploads/'.$r[5].'/'.$r[4],'Café de especialidad '.$r[1].' — Ikore Kofi');
$vendor=get_user_by('login','ikore-kofi');
if (!$vendor) {
 $emails=$wpdb->get_col("SELECT user_email FROM {$wpdb->users} WHERE user_email LIKE 'tostis005+%@%'");
 $max=-1; $domains=array();
 foreach($emails as $e) if(preg_match('/^tostis005\+([0-9]+)@([^@]+)$/i',$e,$m)) {$max=max($max,(int)$m[1]);$domains[$m[2]]=($domains[$m[2]]??0)+1;}
 if($max<0||!$domains) throw new RuntimeException('Internal vendor email convention missing');
 arsort($domains);$domain=array_key_first($domains);$email='';
 for($n=$max+1;$n<$max+100;$n++) if(!email_exists('tostis005+'.$n.'@'.$domain)) {$email='tostis005+'.$n.'@'.$domain;break;}
 if(!$email) throw new RuntimeException('No internal mailbox available');
 $id=wp_insert_user(array('user_login'=>'ikore-kofi','user_nicename'=>'ikore-kofi','user_email'=>$email,'display_name'=>'Ikore Kofi','first_name'=>'Ikore Kofi','role'=>'wcfm_vendor','user_pass'=>wp_generate_password(32,true,true)));
 if(is_wp_error($id)) throw new RuntimeException($id->get_error_message());
 $vendor=get_user_by('id',$id);
}
$vid=(int)$vendor->ID;
if(!in_array('wcfm_vendor',$vendor->roles,true)) throw new RuntimeException('Existing account role conflict');
$about='<p>Ikore Kofi es un tostador de café de especialidad de Valencia. Alfredo y Taira trabajan en el tueste profesional desde 2014 y seleccionan cafés de diferentes orígenes para ofrecer perfiles distintos en cada taza.</p><p>En su obrador adaptan el tueste a cada origen, revisan los granos manualmente y catan el resultado. Esta selección reúne seis cafés 100 % arábica de Panamá, Colombia, Costa Rica y Kenia, con información sobre proceso, altura y notas de cata.</p>';
$profile=get_user_meta($vid,'wcfmmp_profile_settings',true);$profile=is_array($profile)?$profile:array();
$profile=array_merge($profile,array('user_name'=>'ikore-kofi','user_email'=>$vendor->user_email,'store_name'=>'Ikore Kofi','store_slug'=>'ikore-kofi','vendor_id'=>$vid,'shop_description'=>$about,'store_hide_email'=>'yes','store_hide_phone'=>'yes','store_hide_address'=>'yes','gravatar'=>(string)$logo,'banner_type'=>'image','banner'=>(string)$banner,'mobile_banner'=>(string)$banner,'list_banner_type'=>'image','list_banner'=>(string)$banner));
update_user_meta($vid,'store_name','Ikore Kofi');
update_user_meta($vid,'_store_description',$about);
update_user_meta($vid,'wcfmmp_profile_settings',$profile);
update_user_meta($vid,'_mdo_manual_producer',array('legal_name'=>'Ikore Kofi S.L.','cif'=>'B26933242','address'=>'C/ Cofrentes 11, 46010 Valencia, España','website'=>'https://ikorekofi.com/','contact'=>'info@ikorekofi.com','registration'=>'EM-25.00008/V','emdo_commission_percent'=>15,'box_cm'=>array(43,30,12),'max_box_kg'=>3,'shipping_status'=>'configured','sync'=>false));
// Existing WCFM configuration defines commissions for the vendor: 85% vendor / 15% marketplace.
$global=get_option('wcfm_commission_options',array());
if(empty($global['commission_for'])) $global=get_option('wcfmmp_commission_options',array());
if(!in_array($global['commission_for']??'',array('vendor','admin'),true)) throw new RuntimeException('Commission direction unknown');
$commission=array('commission_mode'=>'percent','commission_percent'=>($global['commission_for']==='vendor'?'85':'15'),'commission_fixed'=>'','tax_name'=>'','tax_percent'=>'');
update_user_meta($vid,'_wcfmmp_commission',$commission);
$profile['commission']=$commission;update_user_meta($vid,'wcfmmp_profile_settings',$profile);
$term=get_term_by('slug','cafe-de-especialidad','product_cat');
if(!$term) {$t=wp_insert_term('Café de especialidad','product_cat',array('slug'=>'cafe-de-especialidad','description'=>'Cafés de especialidad de distintos orígenes, con información de proceso, tueste y notas de cata.'));if(is_wp_error($t))throw new RuntimeException($t->get_error_message());$cat=(int)$t['term_id'];}else{$cat=(int)$term->term_id;}
$grinds=array('En grano','Italiana','Espresso','Prensa francesa','Cold brew','Aeropress','Filtro y V60');
$ids=array();$report=array();
foreach($catalog as $r) {
 $id=wc_get_product_id_by_sku($r[0]);
 if($id && (int)get_post_field('post_author',$id)!==$vid) throw new RuntimeException('SKU belongs to another producer');
 $p=$id?wc_get_product($id):new WC_Product_Variable();
 if(!$p instanceof WC_Product_Variable) throw new RuntimeException('Product type conflict');
 $p->set_name('Café de especialidad '.$r[1].' — Ikore Kofi');
 $p->set_slug('cafe-ikore-kofi-'.$r[2]);$p->set_sku($r[0]);$p->set_status('draft');
 $p->set_tax_status('taxable');$p->set_tax_class($tax_class);
 $p->set_catalog_visibility('visible');$p->set_category_ids(array($cat));$p->set_image_id($images[$r[0]]);
 $p->set_short_description('<p>Café 100 % arábica de '.$r[6].', de tueste medio y proceso '.$r[8].'. '.$r[11].' Puntuación: '.$r[9].' SCA.</p>');
 $p->set_description('<h2>'.$r[1].': café de especialidad tostado en Valencia</h2><p>'.$r[11].' Ikore Kofi trabaja este origen con un tueste medio y selección manual de los granos.</p><h3>Ficha del café</h3><ul><li>Origen: '.$r[6].'.</li><li>Composición: café 100 % arábica.</li><li>Altitud: '.$r[7].' metros sobre el nivel del mar.</li><li>Proceso: '.$r[8].'.</li><li>Tueste: medio.</li><li>Puntuación: '.$r[9].' SCA.</li><li>Notas de cata: '.$r[10].'.</li><li>Contenido neto: 250 g o 1 kg, según el formato seleccionado.</li></ul><p>Las notas de cata describen el perfil sensorial del café; no son ingredientes añadidos.</p><h3>Elige cómo prepararlo</h3><p>Disponible en grano o con molienda para italiana, espresso, prensa francesa, cold brew, Aeropress o filtro y V60. Elige la opción de tu cafetera antes de añadirlo al carrito. Si tienes molinillo, el formato en grano permite moler justo antes de preparar la bebida.</p><h3>Conservación</h3><p>Guarda el envase bien cerrado en un lugar fresco y seco, protegido de la luz y de olores intensos. Consulta en el envase la información del lote y consumo preferente.</p>');
 $attr=new WC_Product_Attribute();$attr->set_name('Molienda');$attr->set_options($grinds);$attr->set_visible(true);$attr->set_variation(true);
 $size=new WC_Product_Attribute();$size->set_name('Peso');$size->set_options(array('250 g','1 kg'));$size->set_visible(true);$size->set_variation(true);$size->set_position(0);$attr->set_position(1);
 $p->set_attributes(array($size,$attr));$p->set_default_attributes(array('peso'=>'250 g','molienda'=>'En grano'));
 $p->set_weight((string)wc_get_weight(0.25,get_option('woocommerce_weight_unit'),'kg'));
 $id=$p->save();wp_update_post(array('ID'=>$id,'post_author'=>$vid));
 update_post_meta($id,'_emdo_source_url','https://ikorekofi.com/cafe/'.$r[2].'/');
 update_post_meta($id,'_mdo_ikore_manual',1);
 update_post_meta($id,'_mdo_supplier_reference',$r[0]);
 $children=$p->get_children();
 foreach(array('250'=>'250 g','1000'=>'1 kg') as $grams=>$weight_label) {
 foreach($grinds as $i=>$grind) {
  $sku=$r[0].'-'.$grams.'-'.($i+1);$cid=wc_get_product_id_by_sku($sku);
  $v=$cid?wc_get_product($cid):new WC_Product_Variation();
  if($cid&&(int)$v->get_parent_id()!==$id)throw new RuntimeException('Variation collision');
  $v->set_parent_id($id);$v->set_sku($sku);$v->set_attributes(array('peso'=>$weight_label,'molienda'=>$grind));$gross_price=$grams==250?$r[3]:array('chiriqui'=>'76.00','el-indio'=>'68.40','villa-sarchi'=>'49.02','typica-mejorado'=>'49.02','adumu'=>'68.40','geisha'=>'68.40')[$r[2]];$stored_price=wc_prices_include_tax()?$gross_price:wc_format_decimal((float)$gross_price/1.10,6);$v->set_tax_class('parent');$v->set_tax_status('taxable');$v->set_regular_price($stored_price);$v->set_sale_price('');$v->set_price($stored_price);$v->set_status('publish');$v->set_manage_stock(false);$v->set_stock_status('instock');$v->set_weight((string)wc_get_weight((float)$grams/1000,get_option('woocommerce_weight_unit'),'kg'));$v->set_image_id($images[$r[0]]);$child=$v->save();wp_update_post(array('ID'=>$child,'post_author'=>$vid));
 }
 }
 WC_Product_Variable::sync($id);wc_delete_product_transients($id);$ids[]=$id;
 $report[]=array('id'=>$id,'sku'=>$r[0],'name'=>$p->get_name(),'price_250g'=>$r[3],'url'=>get_permalink($id));
}
foreach($ids as $id) {$p=wc_get_product($id);$p->set_cross_sell_ids(array_values(array_diff($ids,array($id))));$p->set_status('publish');$p->save();wc_delete_product_transients($id);}
delete_user_meta($vid,'_disable_vendor');
foreach($ids as $id) { $p=wc_get_product($id);if($p->get_status()!=='publish'||count($p->get_children())!==14||!$p->get_image_id()||!$p->is_purchasable())throw new RuntimeException('Verification failed '.$id); }

// Official checkout checked 2026-10-20: ES mainland 3.99, Balearics 6.99,
// Portugal 5.99; all three free at EUR40 including VAT, after discounts.
$methods=$wpdb->prefix.'wcfm_marketplace_shipping_zone_methods';
$locations=$wpdb->prefix.'wcfm_marketplace_shipping_zone_locations';
$core_zones=$wpdb->prefix.'woocommerce_shipping_zones';
$core_methods=$wpdb->prefix.'woocommerce_shipping_zone_methods';
$zones=array(1=>array('name'=>'España (península)','flat'=>'3.99'),10=>array('name'=>'Baleares','flat'=>'6.99'),20=>array('name'=>'Portugal','flat'=>'5.99'));
foreach($zones as $zid=>$cfg) {
 $name=$wpdb->get_var($wpdb->prepare("SELECT zone_name FROM {$core_zones} WHERE zone_id=%d",$zid));
 $gateway=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$core_methods} WHERE zone_id=%d AND method_id='wcfmmp_product_shipping_by_zone' AND is_enabled=1",$zid));
 if($name!==$cfg['name']||!$gateway)throw new RuntimeException('Unexpected global shipping zone '.$zid);
}
$other_before=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id<>%d ORDER BY instance_id",$vid),ARRAY_A);
$backup=array('methods'=>$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id=%d",$vid),ARRAY_A),'locations'=>$wpdb->get_results($wpdb->prepare("SELECT * FROM {$locations} WHERE vendor_id=%d",$vid),ARRAY_A),'meta'=>get_user_meta($vid,'_wcfmmp_shipping',true));
if(!get_user_meta($vid,'_mdo_ikore_shipping_before_20261020',true))update_user_meta($vid,'_mdo_ikore_shipping_before_20261020',$backup);
$wpdb->query('START TRANSACTION');
try {
 if(false===$wpdb->delete($methods,array('vendor_id'=>$vid),array('%d')))throw new RuntimeException('Cannot update own shipping rates');
 if(false===$wpdb->delete($locations,array('vendor_id'=>$vid),array('%d')))throw new RuntimeException('Cannot update own shipping locations');
 foreach($zones as $zid=>$cfg) {
  $flat=array('title'=>'Envío a domicilio (CTT Express)','description'=>'Entrega estimada en 48–72 horas laborables','cost'=>$cfg['flat'],'tax_status'=>'none','class_cost_no_class_cost'=>'','calculation_type'=>'');
  $free=array('title'=>'Envío gratuito (CTT Express)','description'=>'Desde 40 € en productos de Ikore Kofi','cost'=>'0','tax_status'=>'none','min_amount'=>'40');
  foreach(array('flat_rate'=>$flat,'free_shipping'=>$free) as $method=>$settings) {
   if(false===$wpdb->insert($methods,array('method_id'=>$method,'zone_id'=>$zid,'vendor_id'=>$vid,'is_enabled'=>1,'settings'=>maybe_serialize($settings)),array('%s','%d','%d','%d','%s')))throw new RuntimeException('Cannot save shipping rate');
  }
 }
 $meta=get_user_meta($vid,'_wcfmmp_shipping',true);$meta=is_array($meta)?$meta:array();
 $meta['_wcfmmp_user_shipping_enable']='yes';$meta['_wcfmmp_user_shipping_type']='by_zone';update_user_meta($vid,'_wcfmmp_shipping',$meta);
 $wpdb->query('COMMIT');
}catch(Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
$after=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id=%d ORDER BY zone_id,method_id",$vid),ARRAY_A);
if(count($after)!==6)throw new RuntimeException('Shipping rate count incorrect');
foreach($after as $row) {
 $settings=maybe_unserialize($row['settings']);$zid=(int)$row['zone_id'];$method=$row['method_id'];$field=$method==='free_shipping'?'min_amount':'cost';
 $expected=$method==='free_shipping'?'40':$zones[$zid]['flat'];
 if(abs((float)$settings[$field]-(float)$expected)>0.001)throw new RuntimeException('Shipping rate mismatch');
}
$other_after=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$methods} WHERE vendor_id<>%d ORDER BY instance_id",$vid),ARRAY_A);
if(wp_json_encode($other_after)!==wp_json_encode($other_before))throw new RuntimeException('Other vendor shipping changed');
WC_Cache_Helper::get_transient_version('shipping',true);do_action('mdo_shipping_destinations_invalidate');
echo 'IKORE_SHIPPING='.wp_json_encode(array('zones'=>$zones,'free_from'=>40,'other_vendors_unchanged'=>true),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;

echo 'IKORE_RESULT='.wp_json_encode(array('vendor_id'=>$vid,'store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($vid):'','products'=>$report,'variations'=>84,'logo'=>$logo,'banner'=>$banner,'manual_catalog'=>true,'shipping'=>'configured'),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
