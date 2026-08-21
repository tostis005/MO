<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

function emdo_sm_fetch_locs( string $url ): array {
    $res = wp_remote_get( add_query_arg( 'emdo_sm_audit', (string) microtime(true), $url ), array( 'timeout' => 45, 'redirection' => 3, 'headers' => array( 'Cache-Control' => 'no-cache', 'User-Agent' => 'EMDO-Sitemap-Membership-Audit/2026-08-21' ) ) );
    if ( is_wp_error( $res ) ) throw new RuntimeException( $url . ': ' . $res->get_error_message() );
    $code = (int) wp_remote_retrieve_response_code( $res );
    $body = (string) wp_remote_retrieve_body( $res );
    if ( $code !== 200 || $body === '' ) throw new RuntimeException( $url . ': HTTP ' . $code );
    preg_match_all( '#<loc>\s*(.*?)\s*</loc>#is', $body, $m );
    return array_values( array_unique( array_map( static fn($x) => html_entity_decode( trim( (string)$x ), ENT_QUOTES | ENT_XML1, 'UTF-8' ), $m[1] ?? array() ) ) );
}
function emdo_sm_norm( string $url ): string { return untrailingslashit( strtok( $url, '?' ) ?: $url ); }
function emdo_sm_disabled( int $uid ): bool {
    $u = get_userdata($uid); if (!$u) return false;
    if (in_array('disable_vendor',(array)$u->roles,true)) return true;
    foreach (array('_disable_vendor','_wcfm_store_offline') as $k) {
        $v=strtolower(trim((string)get_user_meta($uid,$k,true)));
        if(in_array($v,array('1','yes','true','on'),true)) return true;
    }
    return false;
}

$root = untrailingslashit( home_url('/') );
$product_locs = emdo_sm_fetch_locs( $root . '/mdo-sitemap-products.xml' );
$category_locs = emdo_sm_fetch_locs( $root . '/mdo-sitemap-categories.xml' );
$page_locs = emdo_sm_fetch_locs( $root . '/mdo-sitemap-pages.xml' );
$product_set = array_fill_keys( array_map('emdo_sm_norm',$product_locs), true );
$category_set = array_fill_keys( array_map('emdo_sm_norm',$category_locs), true );
$page_set = array_fill_keys( array_map('emdo_sm_norm',$page_locs), true );

$out=array(
 'product_locs'=>count($product_locs),'category_locs'=>count($category_locs),'page_locs'=>count($page_locs),
 'disabled_published_products'=>0,'disabled_products_in_sitemap'=>array(),'active_published_products'=>0,'active_missing_from_sitemap'=>array(),
 'nonpublish_products_in_sitemap'=>array(),'mentta_category_urls_in_sitemap'=>array(),'inicio_bf_in_pages'=>false,
 'by_vendor'=>array(),
);

$rows=$wpdb->get_results("SELECT ID,post_author,post_status,post_title FROM {$wpdb->posts} WHERE post_type='product' AND post_status IN ('publish','draft','private','pending','future') ORDER BY post_author,ID");
foreach($rows as $r){
    $id=(int)$r->ID;$uid=(int)$r->post_author;$status=(string)$r->post_status;
    $disabled=emdo_sm_disabled($uid);
    $url=emdo_sm_norm(get_permalink($id));
    $in=isset($product_set[$url]);
    $profile=get_user_meta($uid,'wcfmmp_profile_settings',true);
    $name=is_array($profile)&&!empty($profile['store_name'])?(string)$profile['store_name']:(get_userdata($uid)->display_name??('user-'.$uid));
    if(!isset($out['by_vendor'][$name]))$out['by_vendor'][$name]=array('disabled'=>$disabled,'publish'=>0,'sitemap'=>0,'draft_private_pending_future'=>0);
    if($status==='publish'){
        $out['by_vendor'][$name]['publish']++;
        if($in)$out['by_vendor'][$name]['sitemap']++;
        if($disabled){
            $out['disabled_published_products']++;
            if($in && count($out['disabled_products_in_sitemap'])<40)$out['disabled_products_in_sitemap'][]=array('id'=>$id,'vendor'=>$name,'title'=>$r->post_title,'url'=>$url);
        } else {
            $out['active_published_products']++;
            if(!$in && count($out['active_missing_from_sitemap'])<40)$out['active_missing_from_sitemap'][]=array('id'=>$id,'vendor'=>$name,'title'=>$r->post_title,'url'=>$url);
        }
    } else {
        $out['by_vendor'][$name]['draft_private_pending_future']++;
        if($in && count($out['nonpublish_products_in_sitemap'])<40)$out['nonpublish_products_in_sitemap'][]=array('id'=>$id,'status'=>$status,'vendor'=>$name,'title'=>$r->post_title,'url'=>$url);
    }
}

$mentta=get_term_by('slug','mentta','product_cat');
if($mentta instanceof WP_Term){
    $ids=array_merge(array($mentta->term_id),get_term_children($mentta->term_id,'product_cat'));
    foreach($ids as $tid){$term=get_term((int)$tid,'product_cat');if(!$term instanceof WP_Term)continue;$u=get_term_link($term);if(!is_wp_error($u)&&isset($category_set[emdo_sm_norm($u)]))$out['mentta_category_urls_in_sitemap'][]=$u;}
}
$out['inicio_bf_in_pages']=isset($page_set[emdo_sm_norm($root.'/inicio-bf/')]);
$out['product_sitemap_sample']=array_slice($product_locs,0,12);
$out['category_sitemap_sample']=array_slice($category_locs,0,20);
$out['page_sitemap']=$page_locs;

echo "EMDO SITEMAP MEMBERSHIP AUDIT 2026-08-21\n";
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
