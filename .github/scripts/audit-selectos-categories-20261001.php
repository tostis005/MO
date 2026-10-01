<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$supplier_id = 0;
if ( class_exists( 'MDO_Supplier_Repository' ) ) {
    foreach ( MDO_Supplier_Repository::all() as $supplier ) {
        if ( 'selectos-de-castilla' === (string)($supplier['connector'] ?? '')
            || 'selectos-de-castilla' === (string)($supplier['code'] ?? '')
            || 'Selectos de Castilla' === (string)($supplier['name'] ?? '') ) {
            $supplier_id = (int)$supplier['id'];
            break;
        }
    }
}
if ( $supplier_id <= 0 ) { $supplier_id = 5; }

$ids = get_posts(array(
    'post_type'=>'product',
    'post_status'=>array('publish','draft','private'),
    'posts_per_page'=>-1,
    'fields'=>'ids',
    'meta_key'=>'_emdo_supplier_id',
    'meta_value'=>$supplier_id,
    'orderby'=>'ID',
    'order'=>'ASC',
));

$products=array();
foreach($ids as $id){
    $id=(int)$id;
    $terms=wp_get_post_terms($id,'product_cat');
    $cats=array();
    if(!is_wp_error($terms)){
        foreach($terms as $t){
            if(!$t instanceof WP_Term) continue;
            $parent='';
            if($t->parent){
                $p=get_term((int)$t->parent,'product_cat');
                if($p instanceof WP_Term) $parent=$p->slug;
            }
            $cats[]=array('id'=>(int)$t->term_id,'name'=>$t->name,'slug'=>$t->slug,'parent'=>$parent);
        }
    }
    $p=wc_get_product($id);
    $products[]=array(
        'id'=>$id,
        'status'=>get_post_status($id),
        'title'=>get_the_title($id),
        'slug'=>(string)get_post_field('post_name',$id),
        'source_url'=>(string)get_post_meta($id,'_emdo_source_url',true),
        'stock'=>$p ? $p->get_stock_status() : null,
        'categories'=>$cats,
        'auto_reason'=>(string)get_post_meta($id,'_emdo_auto_category_reason',true),
        'auto_ids'=>(string)get_post_meta($id,'_emdo_auto_category_ids',true),
    );
}

$terms=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false,'orderby'=>'name','order'=>'ASC'));
$categories=array();
if(!is_wp_error($terms)){
    foreach($terms as $t){
        if(!$t instanceof WP_Term) continue;
        $parent='';
        if($t->parent){
            $p=get_term((int)$t->parent,'product_cat');
            if($p instanceof WP_Term) $parent=$p->slug;
        }
        $categories[]=array(
            'id'=>(int)$t->term_id,
            'name'=>$t->name,
            'slug'=>$t->slug,
            'parent'=>$parent,
            'count'=>(int)$t->count
        );
    }
}

echo wp_json_encode(array(
    'generated_at'=>gmdate('c'),
    'supplier_id'=>$supplier_id,
    'product_count'=>count($products),
    'products'=>$products,
    'categories'=>$categories,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
