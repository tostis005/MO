<?php
/**
 * Verification-only WP CLI script for the narrow Mentta API visibility override.
 * No products, categories, attributes, prices or visibility settings are modified.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI ||
    !class_exists('WC_Mentta_Plugin') || !function_exists('emdo_montjam_mentta_product_export_request')) {
    fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: required plugins unavailable\n");exit(2);
}
$prod=wc_get_product(17129);
$source=wc_get_product(14264);
$sample=wc_get_product(14630);
if (!$prod || !$source || !$sample ||
    $prod->get_catalog_visibility()!=='hidden' ||
    $prod->get_status()!=='publish' ||
    $prod->get_slug()!=='jamon-de-bellota-100-iberico-montjam-mentta' ||
    !in_array(17129,emdo_mentta_exclusive_ids(),true)) {
    fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: unexpected WooCommerce baseline\n");exit(3);
}
$categories=wp_get_object_terms(17129,'product_cat',array('fields'=>'slugs'));
if (is_wp_error($categories) || !in_array('mentta',$categories,true)
    || !in_array('mentta-jamones-paletas',$categories,true)) {
    fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: Mentta category missing\n");exit(4);
}
$old_uri=$_SERVER['REQUEST_URI']??null;
$_SERVER['REQUEST_URI']='/producto/jamon-de-bellota-100-iberico-montjam-mentta/';
if ($prod->is_visible()!==false) {
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: frontend visibility leaked\n");exit(5);
}
if (!defined('REST_REQUEST')) define('REST_REQUEST',true);
$_SERVER['REQUEST_URI']='/wp-json/wc/store/v1/products/17129';
if ($prod->is_visible()!==false) {
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: public Store API visibility leaked\n");exit(6);
}
$_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/products?product_id=17129';
if ($prod->is_visible()!==true || $source->get_catalog_visibility()!=='visible') {
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: Mentta override failed\n");exit(7);
}
$req=new WP_REST_Request('GET','/mentta_marketplace/products');
$req->set_param('product_id',17129);
$response=WC_Mentta_Plugin::get_products($req);
if (!($response instanceof WP_REST_Response) || $response->get_status()!==200) {
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: unexpected Mentta response\n");exit(8);
}
$body=$response->get_data();
$data=is_object($body)?($body->data??array()):(is_array($body)?($body['data']??array()):array());
$entry=is_array($data) && count($data)===1?$data[0]:null;
if(!is_array($entry) || (int)($entry['Id']??0)!==17129 ||
    ($entry['IsVisible']??null)!==true ||
    !in_array(429,array_map('intval',$entry['Categories']??array()),true) ||
    !in_array(432,array_map('intval',$entry['Categories']??array()),true) ||
    count($entry['Variations']??array())!==2) {
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: Mentta endpoint didn't report the product visible\n");exit(9);
}
$_SERVER['REQUEST_URI']='/wp-json/mentta_marketplace/categories';
if($prod->is_visible()!==false){
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: override leaked into another REST route\n");exit(10);
}
if($old_uri===null)unset($_SERVER['REQUEST_URI']);else $_SERVER['REQUEST_URI']=$old_uri;
if($prod->get_catalog_visibility()!=='hidden'){
 fwrite(STDERR,"MENTTA_ONLY_API_OVERRIDE_ABORT: product catalog_visibility changed\n");exit(11);
}
echo 'MENTTA_ONLY_API_OVERRIDE_VERIFIED '.wp_json_encode(array(
 'product_id'=>17129,'woo_catalog_visibility'=>$prod->get_catalog_visibility(),
 'public_product_visible'=>false,'public_store_api_visible'=>false,
 'mentta_product_endpoint_is_visible'=>$entry['IsVisible'],
 'mentta_variations'=>count($entry['Variations']),
 'mentta_category_ids'=>array_values(array_intersect(array(429,432),array_map('intval',$entry['Categories']))),
 'woocommerce_metadata_unchanged'=>true
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
