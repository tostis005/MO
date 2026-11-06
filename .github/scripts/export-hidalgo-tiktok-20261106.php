<?php
/**
 * Read-only TikTok Shop catalog export for Hidalgo de la Jara.
 * No database writes. Intended for one-time GitHub Actions use on production.
 */
if (!defined('ABSPATH') || !function_exists('wc_get_product')) { fwrite(STDERR, "WooCommerce unavailable\n"); exit(2); }
function emdo_tt_clean($x) {
    $x = html_entity_decode(wp_strip_all_tags((string)$x, true), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return trim((string)preg_replace('/\s+/u', ' ', $x));
}
function emdo_tt_media($id) {
    $id=(int)$id; if(!$id) return null;
    $u=wp_get_attachment_image_url($id,'full');
    $meta=wp_get_attachment_metadata($id);
    return $u ? array('url'=>$u,'width'=>(int)($meta['width']??0),'height'=>(int)($meta['height']??0)) : null;
}
function emdo_tt_props($p, $parent=null) {
    $main=emdo_tt_media($p->get_image_id());
    if(!$main && $parent) $main=emdo_tt_media($parent->get_image_id());
    $g=$p->get_gallery_image_ids();
    if(!$g && $parent) $g=$parent->get_gallery_image_ids();
    $gallery=array();
    foreach(array_slice(array_unique(array_map('intval',$g)),0,12) as $id) {
        $im=emdo_tt_media($id); if($im) $gallery[]=$im;
    }
    return array(
        'id'=>(int)$p->get_id(), 'name'=>$p->get_name(),
        'sku'=>(string)$p->get_sku(), 'status'=>(string)$p->get_status(),
        'type'=>(string)$p->get_type(), 'permalink'=>(string)$p->get_permalink(),
        'description'=>emdo_tt_clean($p->get_description()),
        'short_description'=>emdo_tt_clean($p->get_short_description()),
        'price'=>(string)$p->get_price('edit'),
        'regular_price'=>(string)$p->get_regular_price('edit'),
        'sale_price'=>(string)$p->get_sale_price('edit'),
        'stock_status'=>(string)$p->get_stock_status(),
        'manage_stock'=>(bool)$p->get_manage_stock(),
        'stock_quantity'=>$p->get_stock_quantity(),
        'backorders'=>(string)$p->get_backorders(),
        'weight_kg'=>(string)$p->get_weight('edit'),
        'length_cm'=>(string)$p->get_length('edit'),
        'width_cm'=>(string)$p->get_width('edit'),
        'height_cm'=>(string)$p->get_height('edit'),
        'image'=>$main, 'gallery'=>$gallery
    );
}
$vendor_ids=array(); $vendor_names=array();
foreach(get_users(array('fields'=>array('ID','display_name'))) as $u) {
    $s=get_user_meta($u->ID,'wcfmmp_profile_settings',true);
    $store=is_array($s)?(string)($s['store_name']??''):'';
    $name=$store!==''?$store:(string)$u->display_name;
    if(stripos(remove_accents($name),'Hidalgo de la Jara')!==false) {
        $vendor_ids[]=(int)$u->ID; $vendor_names[(int)$u->ID]=$name;
    }
}
if(!$vendor_ids) { fwrite(STDERR,"Hidalgo vendor not found\n"); exit(3); }
$ids=get_posts(array('post_type'=>'product','post_status'=>'publish',
    'posts_per_page'=>-1,'fields'=>'ids','author__in'=>$vendor_ids,'orderby'=>'ID','order'=>'ASC'));
$rows=array(); $n_variations=0;
foreach($ids as $id) {
    $p=wc_get_product($id); if(!$p || $p->is_type('variation')) continue;
    // Product authorship is shared in production; export only the 23 verified Hidalgo parents.
    $hidalgo_ids=array(4199,1356,1350,4160,2322,1363,1375,5045,1695,5080,1380,1693,14614,1370,5336,5343,1382,1586,5348,3948,3979,4188,8624);
    if(!in_array((int)$id,$hidalgo_ids,true)) continue;
    $row=emdo_tt_props($p);
    $row['vendor']=$vendor_names[(int)get_post_field('post_author',$id)]??'Hidalgo de la Jara';
    $row['attributes']=array(); $row['categories']=array();
    $cats=wp_get_post_terms($id,'product_cat',array('fields'=>'names'));
    if(!is_wp_error($cats)) $row['categories']=array_values($cats);
    foreach($p->get_attributes() as $attr) {
       if(!($attr instanceof WC_Product_Attribute)) continue;
       $values=$attr->is_taxonomy()?wc_get_product_terms($id,$attr->get_name(),array('fields'=>'names')):$attr->get_options();
       $row['attributes'][]=array('name'=>wc_attribute_label($attr->get_name(),$p),'values'=>array_values((array)$values),'variation'=>(bool)$attr->get_variation());
    }
    $row['variations']=array();
    if($p->is_type('variable')) foreach($p->get_children() as $child_id) {
        $v=wc_get_product($child_id); if(!($v instanceof WC_Product_Variation)) continue;
        $x=emdo_tt_props($v,$p);
        $x['visible']=(bool)$v->variation_is_visible();
        $x['attributes']=array();
        foreach($v->get_attributes() as $an=>$av) {
           $label=wc_attribute_label($an,$p);
           if(taxonomy_exists($an)) {
               $term=get_term_by('slug',$av,$an);
               if($term instanceof WP_Term) $av=$term->name;
           }
           $x['attributes'][$label]=(string)$av;
        }
        $row['variations'][]=$x; $n_variations++;
    }
    $rows[]=$row;
}
echo wp_json_encode(array(
  'export_date'=>gmdate('c'),'site'=>get_option('siteurl'),
  'vendor_ids'=>$vendor_ids,'parent_count'=>count($rows),
  'variation_count'=>$n_variations,'products'=>$rows
), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT), "\n";
