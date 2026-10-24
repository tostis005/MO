<?php
/**
 * Production read-only inventory for Quesos Pajarete.
 * List current live/visible Woo products, source status, categories and
 * source-category evidence before changing any inclusion decisions.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('WooCommerce and supplier sync required.');
}
foreach (['home', 'siteurl'] as $key) {
    $host = strtolower((string)wp_parse_url((string)get_option($key), PHP_URL_HOST));
    if (!in_array($host, ['elmercadodeorigen.com','www.elmercadodeorigen.com'], true)) {
        throw new RuntimeException('Production-only inventory.');
    }
}
global $wpdb;
$st = MDO_Database::table('suppliers');
$pt = MDO_Database::table('source_products');
$s = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$st} WHERE code=%s LIMIT 1", 'quesos-pajarete'), ARRAY_A);
if (!$s || (int)$s['vendor_user_id'] !== 4974 ||
    !get_user_by('id',4974) || get_user_by('id',4974)->user_login !== 'quesos-pajarete') {
    throw new RuntimeException('Unexpected producer identity.');
}
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id,wc_product_id,title,source_url,status,source_payload FROM {$pt} WHERE supplier_id=%d ORDER BY id",
    (int)$s['id']
), ARRAY_A);
if (count($rows) < 100 || count($rows) > 220) {
    throw new RuntimeException('Unexpected source catalogue size.');
}
$shown=[];$excluded=[];$other=[];
foreach ($rows as $r) {
    $pid=(int)$r['wc_product_id'];
    $payload=json_decode((string)$r['source_payload'],true);
    $payload=is_array($payload)?$payload:[];
    $cats=[];
    foreach ((array)($payload['source_categories']??[]) as $cat) {
        if (is_array($cat)) $cats[]=trim((string)($cat['name']??$cat['slug']??''));
        elseif (is_string($cat)) $cats[]=trim($cat);
    }
    $record=[
        'source_id'=>(int)$r['id'], 'id'=>$pid,'title'=>(string)$r['title'],
        'source_status'=>(string)$r['status'],'source_url'=>(string)$r['source_url'],
        'source_categories'=>array_values(array_unique(array_filter($cats))),
    ];
    if (!$pid) { $other[]=$record; continue; }
    $p=wc_get_product($pid);
    if (!$p || (int)get_post_field('post_author',$pid)!==4974 || $p->is_type('variation')) {
        throw new RuntimeException('Wrong product/vendor association '.$pid);
    }
    $record['woo_status']=$p->get_status();
    $record['visibility']=$p->get_catalog_visibility();
    $record['woo_categories']=wp_get_post_terms($pid,'product_cat',['fields'=>'names']);
    if ($r['status']==='excluded') {
        $excluded[]=$record;
    } elseif ($p->get_status()==='publish' && $p->get_catalog_visibility()==='visible') {
        $shown[]=$record;
    } else {
        $other[]=$record;
    }
}
echo 'PAJARETE_ONLY_CHEESE_PREVIEW='.wp_json_encode([
    'supplier_id'=>(int)$s['id'],'vendor_id'=>4974,'source_total'=>count($rows),
    'shown_count'=>count($shown),'already_excluded_count'=>count($excluded),
    'other_count'=>count($other),'shown'=>$shown,'excluded'=>$excluded,'other'=>$other
], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
