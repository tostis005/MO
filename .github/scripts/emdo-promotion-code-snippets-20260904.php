<?php
if (!defined('ABSPATH')) exit(1);
if (!post_type_exists('mdo_promotion') || !class_exists('MDO_Promotions') || !function_exists('wc_get_product')) throw new RuntimeException('Promotion system unavailable');

$parent_id=14264; $variation_id=14280;
$parent=wc_get_product($parent_id); $variation=wc_get_product($variation_id);
if(!$parent || !$variation || (int)$variation->get_parent_id()!==$parent_id) throw new RuntimeException('Montjam product/variation missing');
if(abs((float)$variation->get_price()-225.00)>0.001) throw new RuntimeException('Variation price is not 225');
if((string)$variation->get_attribute('pa_tamano')!=='6-65-kg') throw new RuntimeException('Wrong target size');
$url=get_permalink($parent_id); $image=(int)get_post_thumbnail_id($parent_id);
if(!$image) throw new RuntimeException('Product image missing');

$supplier_id=0;
if(class_exists('MDO_Supplier_Repository')) foreach((array)MDO_Supplier_Repository::all() as $s){
 $name=isset($s['name'])?remove_accents(strtolower((string)$s['name'])):'';
 if((int)($s['vendor_user_id']??0)===4723 || strpos($name,'montjam')!==false){$supplier_id=(int)($s['id']??0);break;}
}
$seed='montjam-jamon-bellota-225-v1';
$found=get_posts(['post_type'=>'mdo_promotion','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mdo_promo_seed_key','meta_value'=>$seed]);
$post=[
 'post_type'=>'mdo_promotion','post_status'=>'publish','post_title'=>'Jamón de bellota 100% ibérico Montjam por 225 €',
 'post_name'=>'jamon-bellota-100-iberico-montjam-225','post_excerpt'=>'Jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, por 225 €.',
 'post_content'=>'<p>Una pieza 100% ibérica de bellota elaborada por Montjam en Huelva, con curación lenta y un perfil intenso, jugoso y aromático. La pieza de 6 a 6,5 kg está disponible por <strong>225 €</strong>.</p>','menu_order'=>0
];
if($found)$post['ID']=(int)$found[0];
$id=$found?wp_update_post($post,true):wp_insert_post($post,true);
if(is_wp_error($id)) throw new RuntimeException($id->get_error_message()); $id=(int)$id;
$meta=[
 '_mdo_promo_seed_key'=>$seed,'_mdo_promo_type'=>'custom','_mdo_promo_eyebrow'=>'Especial Montjam',
 '_mdo_promo_summary'=>'Jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, por 225 €.','_mdo_promo_start'=>'2026-09-04','_mdo_promo_end'=>'',
 '_mdo_promo_supplier_id'=>$supplier_id,'_mdo_promo_coupon'=>'','_mdo_promo_benefit'=>'Una oportunidad para disfrutar de un jamón de bellota 100% ibérico Montjam a un precio especialmente competitivo: la pieza de 6 a 6,5 kg por 225 €.',
 '_mdo_promo_cta_label'=>'Ver jamón Montjam','_mdo_promo_cta_url'=>$url,'_mdo_promo_product_ids'=>(string)$parent_id,
 '_mdo_promo_conditions'=>'Precio correspondiente a la variante de 6 a 6,5 kg. Sujeto a disponibilidad.','_mdo_promo_featured_home'=>'1','_mdo_promo_image_product_id'=>(string)$parent_id,
 '_mdo_promo_title_es'=>'Jamón de bellota 100% ibérico Montjam por 225 €','_mdo_promo_slug_es'=>'jamon-bellota-100-iberico-montjam-225','_mdo_promo_eyebrow_es'=>'Especial Montjam',
 '_mdo_promo_summary_es'=>'Jamón de bellota 100% ibérico Montjam, de 6 a 6,5 kg, por 225 €.','_mdo_promo_benefit_es'=>'Una oportunidad para disfrutar de un jamón de bellota 100% ibérico Montjam a un precio especialmente competitivo: la pieza de 6 a 6,5 kg por 225 €.',
 '_mdo_promo_content_es'=>'<p>Una pieza 100% ibérica de bellota elaborada por Montjam en Huelva. La pieza de 6 a 6,5 kg está disponible por <strong>225 €</strong>.</p>','_mdo_promo_cta_label_es'=>'Ver jamón Montjam','_mdo_promo_conditions_es'=>'Precio correspondiente a la variante de 6 a 6,5 kg. Sujeto a disponibilidad.',
 '_mdo_promo_title_en'=>'Montjam 100% Iberian acorn-fed ham for €225','_mdo_promo_slug_en'=>'montjam-100-iberian-acorn-fed-ham-225','_mdo_promo_eyebrow_en'=>'Montjam special','_mdo_promo_summary_en'=>'Montjam 100% Iberian acorn-fed ham, 6 to 6.5 kg, for €225.','_mdo_promo_benefit_en'=>'A special opportunity to enjoy Montjam 100% Iberian acorn-fed ham: the 6 to 6.5 kg piece for €225.','_mdo_promo_content_en'=>'<p>Montjam 100% Iberian acorn-fed ham from Huelva. The 6 to 6.5 kg piece is available for <strong>€225</strong>.</p>','_mdo_promo_cta_label_en'=>'View Montjam ham','_mdo_promo_conditions_en'=>'Price applies to the 6 to 6.5 kg variation. Subject to availability.'
];
foreach($meta as $k=>$v) update_post_meta($id,$k,$v);
set_post_thumbnail($id,$image); clean_post_cache($id); wp_cache_flush();
if(!MDO_Promotions::is_active($id)) throw new RuntimeException('Promotion inactive');
$ids=get_posts(['post_type'=>'mdo_promotion','post_status'=>'publish','posts_per_page'=>20,'fields'=>'ids','orderby'=>['menu_order'=>'ASC','date'=>'DESC'],'meta_key'=>'_mdo_promo_featured_home','meta_value'=>'1']);
$selected=0; foreach($ids as $pid){if(MDO_Promotions::is_active((int)$pid)){$selected=(int)$pid;break;}}
if($selected!==$id) throw new RuntimeException('Promotion is not selected for home; selected='.$selected);
echo 'MONTJAM_PROMOTION_SUCCESS:'.wp_json_encode(['promotion_id'=>$id,'home_selected'=>$selected,'title'=>get_the_title($id),'price'=>$variation->get_price(),'thumbnail'=>(int)get_post_thumbnail_id($id),'supplier_id'=>$supplier_id,'cta_url'=>$url,'promo_url'=>get_permalink($id)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
