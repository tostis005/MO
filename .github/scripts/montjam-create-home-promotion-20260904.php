<?php
if (!defined('ABSPATH')) exit(1);

function mjp_out($label,$value=null){if(is_array($value)||is_object($value))$value=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);echo $label.($value===null?'':': '.(string)$value)."\n";}

if (!post_type_exists('mdo_promotion') || !class_exists('MDO_Promotions')) throw new RuntimeException('MDO promotions unavailable');
if (!function_exists('wc_get_product')) throw new RuntimeException('WooCommerce unavailable');

$parent_id=14264;
$variation_id=14280;
$parent=wc_get_product($parent_id);
$variation=wc_get_product($variation_id);
if(!$parent || !$parent->is_type('variable')) throw new RuntimeException('Montjam parent product missing');
if(!$variation || !$variation->is_type('variation') || (int)$variation->get_parent_id()!==$parent_id) throw new RuntimeException('Target variation missing');
$price=(float)$variation->get_price();
if(abs($price-225.00)>0.001) throw new RuntimeException('Expected target price 225.00, got '.$price);
$size=(string)$variation->get_attribute('pa_tamano');
if($size!=='6-65-kg') throw new RuntimeException('Expected 6-6.5 kg variation, got '.$size);

$product_url=get_permalink($parent_id);
$product_image=(int)get_post_thumbnail_id($parent_id);
if(!$product_image) throw new RuntimeException('Montjam product has no featured image');

$supplier_id=0;
if(class_exists('MDO_Supplier_Repository')){
 foreach((array)MDO_Supplier_Repository::all() as $supplier){
  $name=isset($supplier['name'])?remove_accents(strtolower((string)$supplier['name'])):'';
  $vendor_user_id=isset($supplier['vendor_user_id'])?(int)$supplier['vendor_user_id']:0;
  if($vendor_user_id===4723 || $name==='montjam' || strpos($name,'montjam')!==false){$supplier_id=(int)($supplier['id']??0);break;}
 }
}

$seed='montjam-jamon-bellota-225-v1';
$existing=get_posts(['post_type'=>'mdo_promotion','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mdo_promo_seed_key','meta_value'=>$seed]);
$postarr=[
 'post_type'=>'mdo_promotion','post_status'=>'publish','post_title'=>'Jamón de bellota 100% ibérico Montjam por 225 €',
 'post_name'=>'jamon-bellota-100-iberico-montjam-225','post_excerpt'=>'Jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, por 225 €.',
 'post_content'=>'<p>Una pieza 100% ibérica de bellota elaborada por Montjam en Huelva, con curación lenta y un perfil intenso, jugoso y aromático. La pieza de 6 a 6,5 kg está disponible por <strong>225 €</strong>.</p>',
 'menu_order'=>0,
];
if($existing){$postarr['ID']=(int)$existing[0];$post_id=wp_update_post($postarr,true);}else{$post_id=wp_insert_post($postarr,true);}
if(is_wp_error($post_id)) throw new RuntimeException($post_id->get_error_message());
$post_id=(int)$post_id;

$meta=[
 '_mdo_promo_seed_key'=>$seed,
 '_mdo_promo_type'=>'custom',
 '_mdo_promo_eyebrow'=>'Especial Montjam',
 '_mdo_promo_summary'=>'Jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, por 225 €.',
 '_mdo_promo_start'=>'2026-09-04',
 '_mdo_promo_end'=>'',
 '_mdo_promo_supplier_id'=>$supplier_id,
 '_mdo_promo_coupon'=>'',
 '_mdo_promo_benefit'=>'Una oportunidad para disfrutar de un jamón de bellota 100% ibérico Montjam a un precio especialmente competitivo: la pieza de 6 a 6,5 kg por 225 €.',
 '_mdo_promo_cta_label'=>'Ver jamón Montjam',
 '_mdo_promo_cta_url'=>$product_url,
 '_mdo_promo_product_ids'=>(string)$parent_id,
 '_mdo_promo_conditions'=>'Precio correspondiente a la variante de 6 a 6,5 kg. Sujeto a disponibilidad.',
 '_mdo_promo_featured_home'=>'1',
 '_mdo_promo_image_product_id'=>(string)$parent_id,
 '_mdo_promo_title_es'=>'Jamón de bellota 100% ibérico Montjam por 225 €',
 '_mdo_promo_slug_es'=>'jamon-bellota-100-iberico-montjam-225',
 '_mdo_promo_eyebrow_es'=>'Especial Montjam',
 '_mdo_promo_summary_es'=>'Jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, por 225 €.',
 '_mdo_promo_benefit_es'=>'Una oportunidad para disfrutar de un jamón de bellota 100% ibérico Montjam a un precio especialmente competitivo: la pieza de 6 a 6,5 kg por 225 €.',
 '_mdo_promo_content_es'=>'<p>Una pieza 100% ibérica de bellota elaborada por Montjam en Huelva, con curación lenta y un perfil intenso, jugoso y aromático. La pieza de 6 a 6,5 kg está disponible por <strong>225 €</strong>.</p>',
 '_mdo_promo_cta_label_es'=>'Ver jamón Montjam',
 '_mdo_promo_conditions_es'=>'Precio correspondiente a la variante de 6 a 6,5 kg. Sujeto a disponibilidad.',
 '_mdo_promo_title_en'=>'Montjam 100% Iberian acorn-fed ham for €225',
 '_mdo_promo_slug_en'=>'montjam-100-iberian-acorn-fed-ham-225',
 '_mdo_promo_eyebrow_en'=>'Montjam special',
 '_mdo_promo_summary_en'=>'Montjam 100% Iberian acorn-fed ham, 6 to 6.5 kg, for €225.',
 '_mdo_promo_benefit_en'=>'A special opportunity to enjoy Montjam 100% Iberian acorn-fed ham at a highly competitive price: the 6 to 6.5 kg piece for €225.',
 '_mdo_promo_content_en'=>'<p>A 100% Iberian acorn-fed ham made by Montjam in Huelva, slowly cured for a deep, juicy and aromatic profile. The 6 to 6.5 kg piece is available for <strong>€225</strong>.</p>',
 '_mdo_promo_cta_label_en'=>'View Montjam ham',
 '_mdo_promo_conditions_en'=>'Price applies to the 6 to 6.5 kg variation. Subject to availability.',
];
foreach($meta as $k=>$v) update_post_meta($post_id,$k,$v);
set_post_thumbnail($post_id,$product_image);

clean_post_cache($post_id);
wp_cache_flush();

if(!MDO_Promotions::is_active($post_id)) throw new RuntimeException('Promotion is not active after save');
$ids=get_posts(['post_type'=>'mdo_promotion','post_status'=>'publish','posts_per_page'=>20,'fields'=>'ids','orderby'=>['menu_order'=>'ASC','date'=>'DESC'],'meta_key'=>'_mdo_promo_featured_home','meta_value'=>'1']);
$active_home=0;
foreach($ids as $id){if(MDO_Promotions::is_active((int)$id)){$active_home=(int)$id;break;}}
if($active_home!==$post_id) throw new RuntimeException('Montjam promotion is not the active home feature; active='.$active_home);

mjp_out('MONTJAM_PROMOTION_SUCCESS',[
 'promotion_id'=>$post_id,'title'=>get_the_title($post_id),'status'=>get_post_status($post_id),'active'=>MDO_Promotions::is_active($post_id),
 'featured_home'=>(string)get_post_meta($post_id,'_mdo_promo_featured_home',true),'home_selected'=>$active_home,
 'supplier_id'=>$supplier_id,'product_id'=>$parent_id,'variation_id'=>$variation_id,'price'=>$variation->get_price(),'size'=>$size,
 'thumbnail'=>(int)get_post_thumbnail_id($post_id),'product_thumbnail'=>$product_image,'cta_url'=>get_post_meta($post_id,'_mdo_promo_cta_url',true),
 'promo_url'=>get_permalink($post_id)
]);
