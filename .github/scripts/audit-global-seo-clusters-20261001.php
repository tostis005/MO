<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! taxonomy_exists( 'product_cat' ) ) { throw new Exception( 'WooCommerce product categories unavailable.' ); }

$term_url = static function( WP_Term $term ): string {
    $u = get_term_link( $term );
    return is_wp_error( $u ) ? '' : (string) $u;
};

$product_terms = get_terms( array(
    'taxonomy'=>'product_cat',
    'hide_empty'=>false,
    'orderby'=>'term_id',
    'order'=>'ASC',
) );
if ( is_wp_error( $product_terms ) ) { throw new Exception( $product_terms->get_error_message() ); }

$pcats = array();
foreach ( $product_terms as $term ) {
    if ( ! $term instanceof WP_Term ) { continue; }
    $products = get_posts( array(
        'post_type'=>'product',
        'post_status'=>'publish',
        'posts_per_page'=>-1,
        'fields'=>'ids',
        'tax_query'=>array(array(
            'taxonomy'=>'product_cat',
            'field'=>'term_id',
            'terms'=>array((int)$term->term_id),
            'include_children'=>false,
        )),
    ) );
    $supplier_counts = array();
    $sample_products = array();
    foreach ( $products as $pid ) {
        $sid = (int) get_post_meta( (int)$pid, '_emdo_supplier_id', true );
        if ( $sid > 0 ) { $supplier_counts[$sid] = ($supplier_counts[$sid] ?? 0) + 1; }
        if ( count($sample_products) < 8 ) {
            $sample_products[] = array(
                'id'=>(int)$pid,
                'title'=>(string)get_the_title((int)$pid),
                'url'=>(string)get_permalink((int)$pid),
                'supplier_id'=>$sid,
                'stock'=>function_exists('wc_get_product') && wc_get_product((int)$pid) ? wc_get_product((int)$pid)->get_stock_status() : '',
            );
        }
    }
    arsort($supplier_counts);
    $children = get_terms(array(
        'taxonomy'=>'product_cat','hide_empty'=>false,'parent'=>(int)$term->term_id,'fields'=>'ids'
    ));
    $children = is_wp_error($children) ? array() : array_map('intval',$children);

    $pcats[] = array(
        'id'=>(int)$term->term_id,
        'name'=>$term->name,
        'slug'=>$term->slug,
        'parent'=>(int)$term->parent,
        'count_term'=>(int)$term->count,
        'published_direct'=>count($products),
        'children'=>$children,
        'url'=>$term_url($term),
        'description_chars'=>mb_strlen(trim(wp_strip_all_tags((string)$term->description)),'UTF-8'),
        'supplier_counts'=>$supplier_counts,
        'sample_products'=>$sample_products,
    );
}

$blog_terms = get_terms(array(
    'taxonomy'=>'category',
    'hide_empty'=>false,
    'orderby'=>'term_id',
    'order'=>'ASC',
));
if ( is_wp_error( $blog_terms ) ) { throw new Exception( $blog_terms->get_error_message() ); }

$bcats = array();
foreach($blog_terms as $term){
    if(!$term instanceof WP_Term) continue;
    $posts = get_posts(array(
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'cat'=>(int)$term->term_id,
    ));
    $direct = get_posts(array(
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
        'tax_query'=>array(array(
            'taxonomy'=>'category','field'=>'term_id','terms'=>array((int)$term->term_id),'include_children'=>false
        ))
    ));
    $clusters=array();
    $samples=array();
    foreach($direct as $pid){
        $cluster=(string)get_post_meta((int)$pid,'_emdo_blog_cluster',true);
        if(''!==$cluster) $clusters[$cluster]=($clusters[$cluster]??0)+1;
        if(count($samples)<10){
            $samples[]=array(
                'id'=>(int)$pid,
                'title'=>(string)get_the_title((int)$pid),
                'slug'=>(string)get_post_field('post_name',(int)$pid),
                'url'=>(string)get_permalink((int)$pid),
                'cluster'=>$cluster,
                'key'=>(string)get_post_meta((int)$pid,'_emdo_seo_landing_key',true),
            );
        }
    }
    arsort($clusters);
    $children=get_terms(array('taxonomy'=>'category','hide_empty'=>false,'parent'=>(int)$term->term_id,'fields'=>'ids'));
    $children=is_wp_error($children)?array():array_map('intval',$children);
    $bcats[]=array(
        'id'=>(int)$term->term_id,
        'name'=>$term->name,
        'slug'=>$term->slug,
        'parent'=>(int)$term->parent,
        'count_term'=>(int)$term->count,
        'published_with_children'=>count($posts),
        'published_direct'=>count($direct),
        'children'=>$children,
        'url'=>$term_url($term),
        'description_chars'=>mb_strlen(trim(wp_strip_all_tags((string)$term->description)),'UTF-8'),
        'cluster_counts'=>$clusters,
        'sample_posts'=>$samples,
    );
}

$all_posts = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC'
));
$cluster_counts=array();
$orphan_cluster_posts=array();
foreach($all_posts as $post){
    if(!$post instanceof WP_Post) continue;
    $cluster=(string)get_post_meta($post->ID,'_emdo_blog_cluster',true);
    if(''===$cluster) continue;
    $cluster_counts[$cluster]=($cluster_counts[$cluster]??0)+1;
    $cats=wp_get_post_terms($post->ID,'category',array('fields'=>'slugs'));
    $cats=is_wp_error($cats)?array():array_values($cats);
    if(!$cats){
        $orphan_cluster_posts[]=array('id'=>(int)$post->ID,'title'=>$post->post_title,'cluster'=>$cluster,'url'=>get_permalink($post));
    }
}
arsort($cluster_counts);

$suppliers=array();
if(class_exists('MDO_Supplier_Repository')){
    foreach(MDO_Supplier_Repository::all() as $s){
        $sid=(int)($s['id']??0);
        if($sid<=0) continue;
        $published=get_posts(array(
            'post_type'=>'product','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids',
            'meta_key'=>'_emdo_supplier_id','meta_value'=>$sid
        ));
        if(!$published) continue;
        $suppliers[$sid]=array(
            'id'=>$sid,
            'name'=>(string)($s['name']??''),
            'code'=>(string)($s['code']??''),
            'connector'=>(string)($s['connector']??''),
            'published_products'=>count($published),
            'vendor_user_id'=>(int)($s['vendor_user_id']??0),
        );
    }
}

echo wp_json_encode(array(
    'batch'=>'20261001-global-cluster-audit',
    'product_categories'=>$pcats,
    'blog_categories'=>$bcats,
    'blog_cluster_counts'=>$cluster_counts,
    'orphan_cluster_posts'=>$orphan_cluster_posts,
    'suppliers'=>$suppliers,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
