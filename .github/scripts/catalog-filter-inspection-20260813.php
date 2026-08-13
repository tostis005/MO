<?php
/** Read-only verification of production catalog status and visible-count semantics. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$vendors = array(3=>'1957',6=>'Hidalgo de la Jara',4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático');
$vendor_ids=array_keys($vendors);
$disabled=function_exists('elmercado_wcfm_disabled_vendor_ids_010210')?array_values(array_filter(array_map('absint',elmercado_wcfm_disabled_vendor_ids_010210()))):array();
$visibility=function_exists('wc_get_product_visibility_term_ids')?wc_get_product_visibility_term_ids():array();
$visibility_ids=array_values(array_filter(array_map('absint',array($visibility['exclude-from-catalog']??0,$visibility['outofstock']??0))));
$visibility_clause=function($alias)use($wpdb,$visibility_ids){
    if(!$visibility_ids){return '';}
    return " AND NOT EXISTS (SELECT 1 FROM {$wpdb->term_relationships} vtr INNER JOIN {$wpdb->term_taxonomy} vtt ON vtt.term_taxonomy_id=vtr.term_taxonomy_id WHERE vtr.object_id={$alias}.ID AND vtt.taxonomy='product_visibility' AND vtt.term_id IN (".implode(',',$visibility_ids).'))';
};

foreach($vendors as $author_id=>$vendor){
    $status_rows=$wpdb->get_results($wpdb->prepare("SELECT post_status,COUNT(*) n FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d GROUP BY post_status ORDER BY post_status",$author_id),ARRAY_A);
    $statuses=array();foreach((array)$status_rows as $r){$statuses[(string)$r['post_status']]=(int)$r['n'];}
    $visible=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_author=%d AND p.post_status='publish'".$visibility_clause('p'),$author_id));
    echo 'CATALOG_VENDOR_COUNT '.wp_json_encode(array('id'=>$author_id,'vendor'=>$vendor,'statuses'=>$statuses,'visible_catalog'=>$visible,'disabled'=>in_array($author_id,$disabled,true)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}

$ids_sql=implode(',',array_map('absint',$vendor_ids));
$disabled_sql=$disabled?implode(',',array_map('absint',$disabled)):'0';
$all_five_publish=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author IN ({$ids_sql})");
$all_five_visible=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_status='publish' AND p.post_author IN ({$ids_sql})".$visibility_clause('p'));
$public_five_visible=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_status='publish' AND p.post_author IN ({$ids_sql}) AND p.post_author NOT IN ({$disabled_sql})".$visibility_clause('p'));
$all_site_publish=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish'");
$all_site_visible=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_status='publish'".$visibility_clause('p'));
$public_site_visible=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} p WHERE p.post_type='product' AND p.post_status='publish' AND p.post_author NOT IN ({$disabled_sql})".$visibility_clause('p'));

echo 'CATALOG_COUNT_SUMMARY '.wp_json_encode(array(
 'five_vendor_publish'=>$all_five_publish,
 'five_vendor_admin_visible'=>$all_five_visible,
 'five_vendor_public_visible'=>$public_five_visible,
 'all_site_publish'=>$all_site_publish,
 'all_site_admin_visible'=>$all_site_visible,
 'all_site_public_visible'=>$public_site_visible,
 'disabled_vendor_ids'=>$disabled,
 'woocommerce_hide_out_of_stock_items'=>get_option('woocommerce_hide_out_of_stock_items','no'),
 'forced_visibility_term_ids'=>$visibility_ids,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";

// Independent root-category counts for the five intended vendors, admin-visible semantics.
$rows=$wpdb->get_results("SELECT DISTINCT p.ID product_id,tt.term_id FROM {$wpdb->posts} p INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id=p.ID INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id=tr.term_taxonomy_id WHERE p.post_type='product' AND p.post_status='publish' AND p.post_author IN ({$ids_sql}) AND tt.taxonomy='product_cat'".$visibility_clause('p'),ARRAY_A);
$parents=array();foreach((array)$wpdb->get_results("SELECT term_id,parent FROM {$wpdb->term_taxonomy} WHERE taxonomy='product_cat'",ARRAY_A) as $r){$parents[(int)$r['term_id']]=(int)$r['parent'];}
$sets=array();foreach((array)$rows as $r){$pid=(int)$r['product_id'];$term=(int)$r['term_id'];$seen=array();while($term>0&&empty($seen[$term])){$seen[$term]=1;$sets[$term][$pid]=1;$term=$parents[$term]??0;}}
$root_counts=array();foreach($parents as $tid=>$parent){if(0!==$parent)continue;$term=get_term($tid,'product_cat');if($term instanceof WP_Term){$root_counts[$term->slug]=count($sets[$tid]??array());}}arsort($root_counts);
echo 'CATALOG_FIVE_VENDOR_ROOT_COUNTS '.wp_json_encode($root_counts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "FILTER_INSPECTION_DONE\n";
