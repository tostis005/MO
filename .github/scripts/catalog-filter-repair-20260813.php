<?php
/** Guarded production repair: restore active supplier products archived by WCFM. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$targets = array(
    4508 => array('name'=>'Puente Robles','expected_archived'=>105,'expected_publish_after'=>106),
    4509 => array('name'=>'El Catedrático','expected_archived'=>32,'expected_publish_after'=>95),
);
$source_table=$wpdb->prefix.'mdo_source_products';
$supplier_table=$wpdb->prefix.'mdo_suppliers';
$updated=0;

foreach($targets as $author_id=>$cfg){
    $settings=get_user_meta($author_id,'wcfmmp_profile_settings',true);
    if(!is_array($settings)||(string)($settings['store_name']??'')!==$cfg['name']){throw new RuntimeException('Vendor identity mismatch '.$author_id);}
    if(function_exists('elmercado_wcfm_vendor_is_disabled_010210')&&!elmercado_wcfm_vendor_is_disabled_010210($author_id)){throw new RuntimeException('Vendor state changed '.$author_id);}
    $supplier=$wpdb->get_row($wpdb->prepare("SELECT id,active FROM {$supplier_table} WHERE vendor_user_id=%d ORDER BY id DESC LIMIT 1",$author_id),ARRAY_A);
    if(!$supplier||1!==(int)$supplier['active']){throw new RuntimeException('Active supplier mapping missing '.$author_id);}
    $ids=array_map('intval',(array)$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='archived' ORDER BY ID",$author_id)));
    if(count($ids)!==(int)$cfg['expected_archived']){throw new RuntimeException('Archived count mismatch '.$author_id.' got '.count($ids));}
    foreach($ids as $product_id){
        $source=$wpdb->get_row($wpdb->prepare("SELECT id,status,wc_product_id FROM {$source_table} WHERE supplier_id=%d AND wc_product_id=%d ORDER BY id DESC LIMIT 1",(int)$supplier['id'],$product_id),ARRAY_A);
        if(!$source||'active'!==(string)$source['status']||$product_id!==(int)$source['wc_product_id']){throw new RuntimeException('Non-active source product '.$product_id);}
        $product=wc_get_product($product_id);
        if(!$product instanceof WC_Product){throw new RuntimeException('Missing Woo product '.$product_id);}
        $result=wp_update_post(array('ID'=>$product_id,'post_status'=>'publish'),true);
        if(is_wp_error($result)||(int)$result!==$product_id||'publish'!==get_post_status($product_id)){throw new RuntimeException('Republish failed '.$product_id);}
        clean_post_cache($product_id);
        wc_delete_product_transients($product_id);
        ++$updated;
    }
    $archived=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='archived'",$author_id));
    $publish=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status='publish'",$author_id));
    if(0!==$archived||(int)$cfg['expected_publish_after']!==$publish){throw new RuntimeException('Final status mismatch '.$author_id.' publish='.$publish.' archived='.$archived);}
    echo 'CATALOG_STATUS_REPAIR_VENDOR '.wp_json_encode(array('id'=>$author_id,'vendor'=>$cfg['name'],'publish'=>$publish,'archived'=>$archived,'still_disabled'=>function_exists('elmercado_wcfm_vendor_is_disabled_010210')?elmercado_wcfm_vendor_is_disabled_010210($author_id):null),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
if(137!==$updated){throw new RuntimeException('Expected 137 updates, got '.$updated);}
if(function_exists('elmercado_flush_home_cache')){elmercado_flush_home_cache();}
wp_cache_flush();
if(class_exists('WC_Cache_Helper')){WC_Cache_Helper::get_transient_version('product',true);}
echo 'FILTER_REPAIR_SUMMARY '.wp_json_encode(array('updated'=>$updated,'created_categories'=>0,'created_terms'=>0),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
