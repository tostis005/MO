<?php
/**
 * Read-only A03/A04/B04 production inventory.
 * No wp_update_post, wp_update_term, update_* or catalog mutations.
 */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) ) { exit(2); }

$slugs = array(
    'cosecha-26-27-sin-filtrar-aceite-de-oliva-virgen-extra-5l-extraccion-en-frio',
    'sin-filtrar-nueva-campana-26-27-aceite-de-oliva-virgen-extra-15x1l',
    'aceite-de-oliva-virgen-extra-12x500ml-pet-sin-filtrar-nueva-campana-26-27',
);
$out = array( 'audit'=>'seo-harvest-20261105', 'products'=>array(), 'categories'=>array() );
foreach ( $slugs as $slug ) {
    $post = get_page_by_path( $slug, OBJECT, 'product' );
    if ( ! $post instanceof WP_Post ) { $out['products'][]=array('slug'=>$slug,'issue'=>'not_found'); continue; }
    $product = wc_get_product( $post->ID );
    if ( ! $product ) { $out['products'][]=array('slug'=>$slug,'issue'=>'wc_not_found'); continue; }
    $attributes = array();
    foreach ( $product->get_attributes() as $name=>$attribute ) {
        if ( $attribute instanceof WC_Product_Attribute ) {
            $attributes[] = array('name'=>$name,'variation'=>$attribute->get_variation(),
                'options'=>$attribute->is_taxonomy() ? wc_get_product_terms($product->get_id(), $attribute->get_name(), array('fields'=>'names')) : $attribute->get_options());
        }
    }
    $children = array();
    if ( $product->is_type('variable') ) {
        foreach ( $product->get_children() as $child_id ) {
            $v = wc_get_product( $child_id );
            if ( ! $v ) { continue; }
            $children[] = array('id'=>$child_id,'status'=>$v->get_status(),'sku'=>$v->get_sku(),
                'attributes'=>$v->get_attributes(),'regular_price'=>$v->get_regular_price(),
                'sale_price'=>$v->get_sale_price(),'price'=>$v->get_price(),
                'stock_status'=>$v->get_stock_status(),'manage_stock'=>$v->get_manage_stock(),
                'stock_quantity'=>$v->get_stock_quantity(),'purchasable'=>$v->is_purchasable());
        }
    }
    $out['products'][] = array(
        'id'=>$product->get_id(),'slug'=>$slug,'title'=>$product->get_name(),'status'=>$product->get_status(),
        'type'=>$product->get_type(),'sku'=>$product->get_sku(),'attributes'=>$attributes,
        'default_attributes'=>$product->get_default_attributes(),'variations'=>$children,
        'category_ids'=>$product->get_category_ids(),
        'short_description'=>wp_strip_all_tags($product->get_short_description()),
        'description_length'=>strlen(wp_strip_all_tags($product->get_description()))
    );
}
foreach ( array('aceites','setas-y-hongos','setas-hongos','jamones-paletas') as $slug ) {
    $term = get_term_by('slug',$slug,'product_cat');
    if ( ! $term instanceof WP_Term ) { continue; }
    $out['categories'][] = array('slug'=>$term->slug,'term_id'=>$term->term_id,
        'count'=>$term->count,'description'=>$term->description,
        'description_sha256'=>hash('sha256',$term->description),
        'children'=>array_map(static function($t){return array('slug'=>$t->slug,'name'=>$t->name,'count'=>$t->count);},
            get_terms(array('taxonomy'=>'product_cat','parent'=>$term->term_id,'hide_empty'=>false))),
        'english_description'=>get_term_meta($term->term_id,'_en_US_description',true));
}
echo 'AOVE_CATALOG_AUDIT='.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if ( count($out['products'])!==3 || count(array_filter($out['products'],static fn($p)=>!empty($p['issue'])))>0 ) { exit(3); }
