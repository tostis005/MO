<?php
/** One-time manual catalog provisioning. No supplier connector or scheduler. */
if (!defined('ABSPATH')) exit(1);
foreach (array('home','siteurl') as $key) {
 if (!in_array(strtolower((string)wp_parse_url(get_option($key),PHP_URL_HOST)),array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true)) throw new RuntimeException('Production required');
}
if (!class_exists('WC_Product_Variable') || !get_role('wcfm_vendor')) throw new RuntimeException('WooCommerce/WCFM required');
global $wpdb;
$tax_class=$wpdb->get_var("SELECT tax_rate_class FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_country='ES' AND ABS(tax_rate-10)<0.0001 ORDER BY tax_rate_order LIMIT 1");
if($tax_class===null) throw new RuntimeException('Existing ES 10% tax class required');
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
update_user_meta($vid,'_mdo_manual_producer',array('legal_name'=>'Ikore Kofi S.L.','cif'=>'B26933242','address'=>'C/ Cofrentes 11, 46010 Valencia, España','website'=>'https://ikorekofi.com/','contact'=>'info@ikorekofi.com','registration'=>'EM-25.00008/V','emdo_commission_percent'=>15,'box_cm'=>array(43,30,12),'max_box_kg'=>3,'shipping_status'=>'pending_confirmation','sync'=>false));
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
 $p->set_description('<h2>'.$r[1].': café de especialidad tostado en Valencia</h2><p>'.$r[11].' Ikore Kofi trabaja este origen con un tueste medio y selección manual de los granos.</p><h3>Ficha del café</h3><ul><li>Origen: '.$r[6].'.</li><li>Composición: café 100 % arábica.</li><li>Altitud: '.$r[7].' metros sobre el nivel del mar.</li><li>Proceso: '.$r[8].'.</li><li>Tueste: medio.</li><li>Puntuación: '.$r[9].' SCA.</li><li>Notas de cata: '.$r[10].'.</li><li>Contenido neto: 250 g.</li></ul><p>Las notas de cata describen el perfil sensorial del café; no son ingredientes añadidos.</p><h3>Elige cómo prepararlo</h3><p>Disponible en grano o con molienda para italiana, espresso, prensa francesa, cold brew, Aeropress o filtro y V60. Elige la opción de tu cafetera antes de añadirlo al carrito. Si tienes molinillo, el formato en grano permite moler justo antes de preparar la bebida.</p><h3>Conservación</h3><p>Guarda el envase bien cerrado en un lugar fresco y seco, protegido de la luz y de olores intensos. Consulta en el envase la información del lote y consumo preferente.</p>');
 $attr=new WC_Product_Attribute();$attr->set_name('Molienda');$attr->set_options($grinds);$attr->set_visible(true);$attr->set_variation(true);
 $size=new WC_Product_Attribute();$size->set_name('Formato');$size->set_options(array('250 g'));$size->set_visible(true);$size->set_variation(false);
 $p->set_attributes(array($attr,$size));$p->set_default_attributes(array('molienda'=>'En grano'));
 $p->set_weight((string)wc_get_weight(0.25,get_option('woocommerce_weight_unit'),'kg'));
 $id=$p->save();wp_update_post(array('ID'=>$id,'post_author'=>$vid));
 update_post_meta($id,'_emdo_source_url','https://ikorekofi.com/cafe/'.$r[2].'/');
 update_post_meta($id,'_mdo_ikore_manual',1);
 update_post_meta($id,'_mdo_supplier_reference',$r[0]);
 $children=$p->get_children();
 foreach($grinds as $i=>$grind) {
  $sku=$r[0].'-250-'.($i+1);$cid=wc_get_product_id_by_sku($sku);
  $v=$cid?wc_get_product($cid):new WC_Product_Variation();
  if($cid&&(int)$v->get_parent_id()!==$id)throw new RuntimeException('Variation collision');
  $v->set_parent_id($id);$v->set_sku($sku);$v->set_attributes(array('molienda'=>$grind));$stored_price=wc_prices_include_tax()?$r[3]:wc_format_decimal((float)$r[3]/1.10,6);$v->set_tax_class('parent');$v->set_tax_status('taxable');$v->set_regular_price($stored_price);$v->set_sale_price('');$v->set_price($stored_price);$v->set_status('publish');$v->set_manage_stock(false);$v->set_stock_status('instock');$v->set_weight((string)wc_get_weight(0.25,get_option('woocommerce_weight_unit'),'kg'));$v->set_image_id($images[$r[0]]);$child=$v->save();wp_update_post(array('ID'=>$child,'post_author'=>$vid));
 }
 WC_Product_Variable::sync($id);wc_delete_product_transients($id);$ids[]=$id;
 $report[]=array('id'=>$id,'sku'=>$r[0],'name'=>$p->get_name(),'price_250g'=>$r[3],'url'=>get_permalink($id));
}
foreach($ids as $id) {$p=wc_get_product($id);$p->set_cross_sell_ids(array_values(array_diff($ids,array($id))));$p->set_status('publish');$p->save();wc_delete_product_transients($id);}
delete_user_meta($vid,'_disable_vendor');
foreach($ids as $id) { $p=wc_get_product($id);if($p->get_status()!=='publish'||count($p->get_children())!==7||!$p->get_image_id()||!$p->is_purchasable())throw new RuntimeException('Verification failed '.$id); }
echo 'IKORE_RESULT='.wp_json_encode(array('vendor_id'=>$vid,'store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($vid):'','products'=>$report,'variations'=>42,'logo'=>$logo,'banner'=>$banner,'manual_catalog'=>true,'shipping'=>'pending_confirmation'),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
