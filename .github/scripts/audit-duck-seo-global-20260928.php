<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

function emdo_duck_audit_words_20260928( string $html ): int {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
    if ( '' === $text ) return 0;
    preg_match_all( '/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u', $text, $m );
    return count( $m[0] );
}
function emdo_duck_link_count_20260928( string $html ): int {
    if ( ! preg_match_all( '/<a\b[^>]*href=["\x{27}]([^"\x{27}]+)["\x{27}]/iu', $html, $m ) ) return 0;
    return count( $m[1] );
}
function emdo_duck_product_link_count_20260928( string $html ): int {
    if ( ! preg_match_all( '/<a\b[^>]*href=["\x{27}]([^"\x{27}]+)["\x{27}]/iu', $html, $m ) ) return 0;
    $n=0;
    foreach($m[1] as $u){
        if ( preg_match( '#/(?:producto|product)/#i', $u ) || preg_match( '#/tienda/[^/]+/(?!reviews|acercade|policies)#i', $u ) ) $n++;
    }
    return $n;
}

$ids = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
    'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','orderby'=>'ID','order'=>'ASC',
    'emdo_include_hidden_blog_islands'=>true,
));
if(count($ids)!==60) throw new Exception('Expected 60 duck posts, found '.count($ids));

$aioseo_table = $wpdb->prefix . 'aioseo_posts';
$aioseo_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $aioseo_table ) ) === $aioseo_table;

$rows=[]; $thumbs=[]; $hidden=0; $categoryless=0; $incoming_external_total=0; $incoming_cluster_total=0;
foreach($ids as $id){
    $id=(int)$id;
    $slug=(string)get_post_field('post_name',$id);
    $url=get_permalink($id);
    $content=(string)get_post_field('post_content',$id);
    $thumb=(int)get_post_thumbnail_id($id);
    $thumbs[$thumb]=($thumbs[$thumb]??0)+1;
    $is_hidden='1'===(string)get_post_meta($id,'_emdo_blog_hidden_listing',true);
    if($is_hidden)$hidden++;
    $cats=wp_get_post_categories($id,array('fields'=>'all'));
    if(empty($cats))$categoryless++;
    $tags=wp_get_post_tags($id,array('fields'=>'names'));
    $image_url=$thumb?wp_get_attachment_image_url($thumb,'full'):'';
    $meta=wp_get_attachment_metadata($thumb);
    $aio=null;
    if($aioseo_exists){
        $aio=$wpdb->get_row($wpdb->prepare(
            "SELECT title,description,canonical_url,og_title,og_description,og_image_url,twitter_image_url FROM {$aioseo_table} WHERE post_id=%d LIMIT 1",
            $id
        ),ARRAY_A);
    }
    $needle1='%/'.$wpdb->esc_like($slug).'/%';
    $incoming_cluster=(int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         INNER JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_emdo_blog_cluster' AND pm.meta_value='duck'
         WHERE p.post_type='post' AND p.post_status='publish' AND p.ID<>%d AND p.post_content LIKE %s",
        $id,$needle1
    ));
    $incoming_external=(int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
         LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_emdo_blog_cluster'
         WHERE p.post_type='post' AND p.post_status='publish' AND p.ID<>%d
         AND (pm.meta_value IS NULL OR pm.meta_value<>'duck') AND p.post_content LIKE %s",
        $id,$needle1
    ));
    $incoming_cluster_total += $incoming_cluster;
    $incoming_external_total += $incoming_external;

    $rows[]=array(
        'id'=>$id,
        'key'=>(string)get_post_meta($id,'_emdo_seo_landing_key',true),
        'title'=>get_the_title($id),
        'slug'=>$slug,
        'words'=>emdo_duck_audit_words_20260928($content),
        'excerpt_chars'=>mb_strlen((string)get_post_field('post_excerpt',$id),'UTF-8'),
        'published'=>get_post_time('c',true,$id),
        'modified'=>get_post_modified_time('c',true,$id),
        'hidden_listing'=>$is_hidden,
        'categories'=>array_map(fn($c)=>array('id'=>$c->term_id,'slug'=>$c->slug,'name'=>$c->name),$cats),
        'tags'=>$tags,
        'thumbnail'=>array(
            'id'=>$thumb,'url'=>$image_url,
            'alt'=>(string)get_post_meta($thumb,'_wp_attachment_image_alt',true),
            'title'=>$thumb?get_the_title($thumb):'',
            'width'=>(int)($meta['width']??0),'height'=>(int)($meta['height']??0),
        ),
        'links'=>array(
            'total'=>emdo_duck_link_count_20260928($content),
            'product'=>emdo_duck_product_link_count_20260928($content),
            'incoming_cluster'=>$incoming_cluster,
            'incoming_non_duck_blog'=>$incoming_external,
        ),
        'seo_meta'=>array(
            'yoast_title'=>(string)get_post_meta($id,'_yoast_wpseo_title',true),
            'yoast_desc'=>(string)get_post_meta($id,'_yoast_wpseo_metadesc',true),
            'rankmath_title'=>(string)get_post_meta($id,'rank_math_title',true),
            'rankmath_desc'=>(string)get_post_meta($id,'rank_math_description',true),
            'aioseo'=>$aio,
        ),
    );
}

$normal = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
    'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck',
));
$with_bypass = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
    'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','emdo_include_hidden_blog_islands'=>true,
));

$cats = get_categories(array('hide_empty'=>false,'orderby'=>'count','order'=>'DESC','number'=>30));
$active = (array)get_option('active_plugins',array());

$out=array(
    'generated_at'=>gmdate('c'),
    'site'=>home_url('/'),
    'theme'=>array('template'=>get_template(),'stylesheet'=>get_stylesheet()),
    'active_plugins'=>$active,
    'aioseo_table'=>$aioseo_exists,
    'summary'=>array(
        'count'=>count($ids),
        'hidden_listing'=>$hidden,
        'categoryless'=>$categoryless,
        'unique_featured_images'=>count(array_filter(array_keys($thumbs))),
        'featured_image_distribution'=>$thumbs,
        'incoming_cluster_total'=>$incoming_cluster_total,
        'incoming_non_duck_blog_total'=>$incoming_external_total,
        'query_without_bypass_count'=>count($normal),
        'query_with_bypass_count'=>count($with_bypass),
    ),
    'top_categories'=>array_map(fn($c)=>array('id'=>$c->term_id,'name'=>$c->name,'slug'=>$c->slug,'count'=>$c->count),$cats),
    'posts'=>$rows,
);
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
