<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! taxonomy_exists( 'product_cat' ) || ! function_exists( 'wc_get_product' ) ) {
    throw new Exception( 'WooCommerce unavailable.' );
}
global $wpdb;

$supplier_id = 5;
$source_table = class_exists( 'MDO_Database' ) ? MDO_Database::table( 'source_products' ) : '';
$source_rows = array();
if ( $source_table ) {
    $source_rows = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT id,wc_product_id,title,status,source_url,source_stock_status FROM {$source_table} WHERE supplier_id=%d ORDER BY id ASC",
            $supplier_id
        ),
        ARRAY_A
    );
}

$duck_slugs = array(
    'pato','pato-fresco','magret-de-pato','confit-de-pato','jamon-de-pato',
    'foie-gras-de-pato','pate-mousse-rillettes-de-pato','foie-pates-untables'
);

$term_rows = array();
foreach ( $duck_slugs as $slug ) {
    $t = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $t instanceof WP_Term ) {
        $term_rows[$slug] = null;
        continue;
    }
    $parent = $t->parent ? get_term( (int)$t->parent, 'product_cat' ) : null;
    $url = get_term_link( $t );
    $desc = trim( wp_strip_all_tags( (string)$t->description ) );
    $term_rows[$slug] = array(
        'id'=>(int)$t->term_id,
        'name'=>$t->name,
        'parent_id'=>(int)$t->parent,
        'parent_slug'=>$parent instanceof WP_Term ? $parent->slug : '',
        'count'=>(int)$t->count,
        'url'=>is_wp_error($url)?'':(string)$url,
        'description_chars'=>function_exists('mb_strlen')?mb_strlen($desc,'UTF-8'):strlen($desc),
        'description_links'=>preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\']/iu',(string)$t->description,$m)?array_values(array_unique($m[1])):array(),
    );
}

$active_duck = array();
$excluded_duck = array();
$public_duck = array();
$taxonomy_meta_mismatch = array();
$category_counts = array();
$cluster_meta_count = 0;
$stock_counts = array();

$is_duck_tax = static function(array $slugs): bool {
    foreach ( array('pato','pato-fresco','magret-de-pato','confit-de-pato','jamon-de-pato','foie-gras-de-pato','pate-mousse-rillettes-de-pato') as $slug ) {
        if ( in_array( $slug, $slugs, true ) ) return true;
    }
    return false;
};

foreach ( $source_rows as $row ) {
    $wc = (int)($row['wc_product_id'] ?? 0);
    if ( $wc <= 0 ) continue;
    $terms = wp_get_post_terms( $wc, 'product_cat' );
    $slugs = is_wp_error($terms) ? array() : array_values(array_map(static fn(WP_Term $t):string=>$t->slug,$terms));
    $is_duck = $is_duck_tax($slugs);
    $meta = (string)get_post_meta($wc,'_emdo_duck_commercial_cluster',true);
    $group = (string)get_post_meta($wc,'_emdo_duck_product_cluster',true);
    $status = (string)get_post_status($wc);
    $product = wc_get_product($wc);
    $stock = $product ? (string)$product->get_stock_status() : '';
    if ($meta==='1') $cluster_meta_count++;
    if ($is_duck) {
        foreach ($slugs as $slug) {
            if (in_array($slug,$duck_slugs,true)) $category_counts[$slug]=($category_counts[$slug]??0)+1;
        }
        $stock_counts[$stock]=($stock_counts[$stock]??0)+1;
        $item=array(
            'source_id'=>(int)$row['id'],
            'wc_product_id'=>$wc,
            'title'=>(string)$row['title'],
            'source_status'=>(string)$row['status'],
            'post_status'=>$status,
            'stock'=>$stock,
            'categories'=>$slugs,
            'cluster_meta'=>$meta,
            'cluster_group'=>$group,
            'url'=>$status==='publish'?(string)get_permalink($wc):'',
        );
        if ((string)$row['status']==='active') $active_duck[]=$item;
        if ((string)$row['status']==='excluded') $excluded_duck[]=$item;
        if ($status==='publish') $public_duck[]=$item;
        if ($meta!=='1' || $group==='') $taxonomy_meta_mismatch[]=$item;
    } elseif ($meta==='1') {
        $taxonomy_meta_mismatch[]=array(
            'source_id'=>(int)$row['id'],'wc_product_id'=>$wc,'title'=>(string)$row['title'],
            'source_status'=>(string)$row['status'],'post_status'=>$status,'stock'=>$stock,
            'categories'=>$slugs,'cluster_meta'=>$meta,'cluster_group'=>$group,
            'reason'=>'duck_meta_without_duck_taxonomy'
        );
    }
}
ksort($category_counts); ksort($stock_counts);

$posts = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'orderby'=>'ID','order'=>'ASC',
    'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck',
));
$blog = array();
$filler_count=0;
$filler_phrase='Detalles prácticos para completar la guía';
$repeated_patterns=array(
    'Antes de aplicar una regla fija, conviene mirar el formato concreto que tenemos delante.',
    'Este punto merece atención porque suele ser el que más cambia el resultado final.',
    'Es una diferencia pequeña sobre el papel, pero muy visible cuando el producto llega al plato.',
    'Aquí conviene separar lo que pertenece al producto de lo que depende de la técnica.',
    'Más que memorizar una receta única, interesa comprender las variables que de verdad cambian el resultado',
    'En esta guía nos centramos en la intención concreta de búsqueda'
);
$pattern_hits=array_fill_keys($repeated_patterns,0);
$min_words=PHP_INT_MAX;$max_words=0;$sum_words=0;
foreach($posts as $post){
    if(!$post instanceof WP_Post) continue;
    $plain=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags((string)$post->post_content)));
    $words=preg_split('/\s+/u',$plain,-1,PREG_SPLIT_NO_EMPTY) ?: array();
    $wc=count($words);
    $min_words=min($min_words,$wc);$max_words=max($max_words,$wc);$sum_words+=$wc;
    $has_filler=str_contains((string)$post->post_content,$filler_phrase);
    if($has_filler) $filler_count++;
    $hits=array();
    foreach($repeated_patterns as $p){
        $n=substr_count((string)$post->post_content,$p);
        if($n){$pattern_hits[$p]+=$n;$hits[$p]=$n;}
    }
    $blog[]=array(
        'id'=>(int)$post->ID,
        'key'=>(string)get_post_meta($post->ID,'_emdo_seo_landing_key',true),
        'slug'=>$post->post_name,
        'title'=>$post->post_title,
        'words'=>$wc,
        'has_filler'=>$has_filler,
        'pattern_hits'=>$hits,
        'subtopic'=>(string)get_post_meta($post->ID,'_emdo_duck_primary_subtopic',true),
        'url'=>(string)get_permalink($post),
    );
}
$blog_term=get_term_by('slug','pato','category');
$blog_term_data=null;
if($blog_term instanceof WP_Term){
    $u=get_term_link($blog_term);
    $blog_term_data=array(
        'id'=>(int)$blog_term->term_id,
        'count'=>(int)$blog_term->count,
        'url'=>is_wp_error($u)?'':(string)$u,
        'description'=>(string)$blog_term->description,
    );
}

$supplier = class_exists('MDO_Supplier_Repository') ? MDO_Supplier_Repository::find($supplier_id) : null;
$vendor=array();
if(is_array($supplier)){
    $uid=(int)($supplier['vendor_user_id']??0);
    $user=$uid?get_userdata($uid):false;
    $store_url='';
    if($uid && function_exists('wcfmmp_get_store_url')) $store_url=(string)wcfmmp_get_store_url($uid);
    if(!$store_url && $user instanceof WP_User) $store_url=(string)get_author_posts_url($uid);
    $vendor=array(
        'user_id'=>$uid,
        'display_name'=>$user instanceof WP_User?$user->display_name:'',
        'store_name'=>$uid?(string)get_user_meta($uid,'wcfmmp_store_name',true):'',
        'store_slug'=>$user instanceof WP_User?$user->user_nicename:'',
        'store_url'=>$store_url,
        'description_chars'=>$uid?strlen(wp_strip_all_tags((string)get_user_meta($uid,'description',true))):0,
    );
}

echo wp_json_encode(array(
    'batch'=>'20261001-duck-cluster-post-taxonomy-audit',
    'source_rows'=>count($source_rows),
    'term_rows'=>$term_rows,
    'active_duck_count'=>count($active_duck),
    'excluded_duck_count'=>count($excluded_duck),
    'public_duck_count'=>count($public_duck),
    'cluster_meta_count'=>$cluster_meta_count,
    'taxonomy_meta_mismatch_count'=>count($taxonomy_meta_mismatch),
    'taxonomy_meta_mismatch'=>$taxonomy_meta_mismatch,
    'category_counts'=>$category_counts,
    'stock_counts'=>$stock_counts,
    'public_duck'=>$public_duck,
    'blog'=>array(
        'count'=>count($blog),
        'filler_count'=>$filler_count,
        'pattern_hits'=>$pattern_hits,
        'min_words'=>$min_words===PHP_INT_MAX?0:$min_words,
        'max_words'=>$max_words,
        'avg_words'=>count($blog)?round($sum_words/count($blog),1):0,
        'term'=>$blog_term_data,
        'rows'=>$blog,
    ),
    'vendor'=>$vendor,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
