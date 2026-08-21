<?php
/** Read-only catalog/content SEO audit for production. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }

function emdo_audit_text_len( $html ): int {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( (string) $html ) ) ) );
    return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
}
function emdo_out( string $key, $value ): void {
    if ( is_array( $value ) || is_object( $value ) ) $value = wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
    echo $key . '=' . $value . "\n";
}

echo "EMDO SEO CATALOG DATA AUDIT 2026-08-21\n";

$products = get_posts(array(
    'post_type'=>'product','post_status'=>'publish','numberposts'=>-1,
    'orderby'=>'ID','order'=>'ASC','suppress_filters'=>false,
));
$stats = array(
    'published'=>count($products),'missing_content'=>0,'short_content'=>0,'missing_excerpt'=>0,
    'missing_featured_image'=>0,'missing_category'=>0,'missing_sku'=>0,'duplicate_title_groups'=>0,
    'featured_alt_missing'=>0,'gallery_alt_missing'=>0,'gallery_images_checked'=>0,
);
$issues = array(); $title_map=array();
foreach ( $products as $p ) {
    $reasons=array();
    $content_len=emdo_audit_text_len($p->post_content);
    $excerpt_len=emdo_audit_text_len($p->post_excerpt);
    if ($content_len===0) { $stats['missing_content']++; $reasons[]='missing_content'; }
    elseif ($content_len<120) { $stats['short_content']++; $reasons[]='short_content'; }
    if ($excerpt_len===0) { $stats['missing_excerpt']++; }
    $thumb=(int)get_post_thumbnail_id($p->ID);
    if (!$thumb) { $stats['missing_featured_image']++; $reasons[]='missing_featured_image'; }
    else {
        $alt=trim((string)get_post_meta($thumb,'_wp_attachment_image_alt',true));
        if ($alt==='') { $stats['featured_alt_missing']++; $reasons[]='featured_alt_missing'; }
    }
    $cats=wp_get_post_terms($p->ID,'product_cat',array('fields'=>'ids'));
    if (is_wp_error($cats) || !$cats) { $stats['missing_category']++; $reasons[]='missing_category'; }
    if (function_exists('wc_get_product')) {
        $wc=wc_get_product($p->ID);
        if ($wc && trim((string)$wc->get_sku())==='') $stats['missing_sku']++;
        if ($wc) {
            foreach ((array)$wc->get_gallery_image_ids() as $img_id) {
                $stats['gallery_images_checked']++;
                if (trim((string)get_post_meta((int)$img_id,'_wp_attachment_image_alt',true))==='') $stats['gallery_alt_missing']++;
            }
        }
    }
    $key=function_exists('mb_strtolower')?mb_strtolower(trim($p->post_title),'UTF-8'):strtolower(trim($p->post_title));
    $title_map[$key][]=$p->ID;
    if ($reasons) $issues[]=array('id'=>$p->ID,'title'=>$p->post_title,'url'=>get_permalink($p),'reasons'=>$reasons,'content_len'=>$content_len);
}
foreach ($title_map as $ids) if(count($ids)>1) $stats['duplicate_title_groups']++;
emdo_out('PRODUCT_STATS',$stats);

$reason_counts=array();
foreach($issues as $i) foreach($i['reasons'] as $r) $reason_counts[$r]=($reason_counts[$r]??0)+1;
emdo_out('PRODUCT_ISSUE_COUNTS',$reason_counts);
$shown=0;
foreach($issues as $i) {
    if ($shown++>=100) break;
    emdo_out('PRODUCT_ISSUE',$i);
}

$cats=get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
$cat_stats=array('total'=>0,'nonempty'=>0,'empty_description'=>0,'short_description'=>0,'zero_products'=>0,'missing_thumbnail'=>0);
$cat_issues=array();
if(!is_wp_error($cats)) {
    $cat_stats['total']=count($cats);
    foreach($cats as $c) {
        if($c->count>0) $cat_stats['nonempty']++; else $cat_stats['zero_products']++;
        $len=emdo_audit_text_len($c->description);
        $r=array();
        if($len===0){$cat_stats['empty_description']++; if($c->count>0)$r[]='missing_description';}
        elseif($len<100){$cat_stats['short_description']++; if($c->count>0)$r[]='short_description';}
        $thumb=(int)get_term_meta($c->term_id,'thumbnail_id',true);
        if(!$thumb){$cat_stats['missing_thumbnail']++; if($c->count>0)$r[]='missing_thumbnail';}
        if($r)$cat_issues[]=array('id'=>$c->term_id,'name'=>$c->name,'slug'=>$c->slug,'count'=>$c->count,'description_len'=>$len,'reasons'=>$r,'url'=>get_term_link($c));
    }
}
emdo_out('CATEGORY_STATS',$cat_stats);
usort($cat_issues,fn($a,$b)=>$b['count']<=>$a['count']);
$shown=0; foreach($cat_issues as $i){if($shown++>=80)break;emdo_out('CATEGORY_ISSUE',$i);}

$pages=get_posts(array('post_type'=>'page','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC'));
$page_stats=array('published'=>count($pages),'missing_human_content'=>0,'missing_featured_image'=>0);
$page_issues=array();
foreach($pages as $p){
    $len=emdo_audit_text_len($p->post_content); $r=array();
    if($len===0){$page_stats['missing_human_content']++;$r[]='missing_content';}
    if(!get_post_thumbnail_id($p->ID)) $page_stats['missing_featured_image']++;
    if($r)$page_issues[]=array('id'=>$p->ID,'title'=>$p->post_title,'slug'=>$p->post_name,'url'=>get_permalink($p),'reasons'=>$r);
}
emdo_out('PAGE_STATS',$page_stats);
$shown=0;foreach($page_issues as $i){if($shown++>=80)break;emdo_out('PAGE_ISSUE',$i);}

echo "END_AUDIT\n";
