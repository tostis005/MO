<?php
/** Read-only production inventory of commercial product categories and live products. */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

$terms = get_terms(array(
    'taxonomy' => 'product_cat',
    'hide_empty' => false,
));
if (is_wp_error($terms)) { throw new Exception($terms->get_error_message()); }

$out = array();
foreach ($terms as $term) {
    if ($term->slug === 'uncategorized' || $term->slug === 'sin-categorizar') continue;
    $q = new WP_Query(array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'no_found_rows' => true,
        'tax_query' => array(array(
            'taxonomy' => 'product_cat',
            'field' => 'term_id',
            'terms' => array((int)$term->term_id),
            'include_children' => false,
        )),
        'orderby' => 'title',
        'order' => 'ASC',
    ));
    $products = array();
    foreach ($q->posts as $id) {
        $p = wc_get_product($id);
        if (!$p || !$p->is_in_stock()) continue;
        $products[] = array(
            'id' => (int)$id,
            'title' => get_the_title($id),
            'slug' => get_post_field('post_name', $id),
            'price' => $p->get_price(),
            'type' => $p->get_type(),
        );
    }
    if (!$products) continue;
    $parent = $term->parent ? get_term((int)$term->parent, 'product_cat') : null;
    $out[] = array(
        'term_id' => (int)$term->term_id,
        'name' => $term->name,
        'slug' => $term->slug,
        'parent' => ($parent && !is_wp_error($parent)) ? $parent->name : null,
        'count_live' => count($products),
        'products' => $products,
    );
}

usort($out, function($a,$b){ return $b['count_live'] <=> $a['count_live']; });

$landings = get_posts(array(
    'post_type'=>'page',
    'post_status'=>'publish',
    'posts_per_page'=>-1,
    'fields'=>'ids',
    'no_found_rows'=>true,
    'meta_query'=>array('relation'=>'OR',
        array('key'=>'_emdo_ac_landing_key','compare'=>'EXISTS'),
        array('key'=>'_emdo_vl_landing_key','compare'=>'EXISTS'),
        array('key'=>'_emdo_jp_landing_key','compare'=>'EXISTS'),
    ),
));
$landing_rows=array();
foreach($landings as $id){
    $key=get_post_meta($id,'_emdo_ac_landing_key',true);
    if($key==='') $key=get_post_meta($id,'_emdo_vl_landing_key',true);
    if($key==='') $key=get_post_meta($id,'_emdo_jp_landing_key',true);
    $landing_rows[]=array('id'=>(int)$id,'key'=>$key,'title'=>get_the_title($id),'slug'=>get_post_field('post_name',$id));
}

echo wp_json_encode(array('verified'=>true,'categories'=>$out,'landings'=>$landing_rows), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
