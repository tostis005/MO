<?php
/** Inventario de imágenes de Latitudes en producción. SOLO LECTURA. */
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;
$site = strtolower((string) wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($site, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)) {
    throw new RuntimeException('Not production: ' . $site);
}
if (!class_exists('MDO_Database') || !function_exists('wc_get_product')) {
    throw new RuntimeException('Supplier sync or WooCommerce unavailable');
}
$st = MDO_Database::table('suppliers');
$pt = MDO_Database::table('source_products');
$supplier = $wpdb->get_row($wpdb->prepare("SELECT id, vendor_user_id, code FROM {$st} WHERE code=%s", 'latitudes'), ARRAY_A);
if (!$supplier || empty($supplier['vendor_user_id'])) { throw new RuntimeException('Latitudes supplier not found'); }
$vendor = (int) $supplier['vendor_user_id'];
$rows = $wpdb->get_results($wpdb->prepare("SELECT wc_product_id,title,source_url,source_payload,status FROM {$pt} WHERE supplier_id=%d ORDER BY id", (int)$supplier['id']), ARRAY_A);
$allIds = [];
foreach ($rows as $r) { if ((int)$r['wc_product_id'] > 0) { $allIds[(int)$r['wc_product_id']] = true; } }
// El autor 4949 contiene fichas ajenas a Latitudes: limitar al registro proveedor.
$vendorIds = array_keys($allIds);
$assets = [];
$sourceOnly = [];
$placementCount = 0;
$add = static function($val, $role, $pid=0) use (&$assets, &$placementCount) {
    $id = 0; $url = '';
    if (is_numeric($val) && (int)$val > 0) { $id = (int)$val; $url = (string)wp_get_attachment_url($id); }
    elseif (is_string($val) && preg_match('~^https?://~i', $val)) { $url = trim($val); $id = (int)attachment_url_to_postid($url); }
    if (!$url || !preg_match('~^https?://~i', $url)) { return; }
    $key = $id ? 'attachment:' . $id : 'url:' . $url;
    if (!isset($assets[$key])) {
        $assets[$key] = [
            'attachment_id'=>$id, 'url'=>$url, 'product_ids'=>[], 'roles'=>[],
            'filename'=>(string)basename((string)wp_parse_url($url, PHP_URL_PATH))
        ];
    }
    $assets[$key]['roles'][$role] = true;
    if ($pid > 0) { $assets[$key]['product_ids'][(string)$pid] = true; }
    $placementCount++;
};
$productRecords = [];
foreach (array_keys($allIds) as $pid) {
    $p = wc_get_product($pid);
    if (!$p) { continue; }
    $rec = [
        'id'=>$pid, 'name'=>$p->get_name(), 'status'=>$p->get_status(),
        'catalog_visibility'=>$p->get_catalog_visibility(),
        'featured_id'=>(int)$p->get_image_id(),
        'gallery_ids'=>array_values(array_map('intval', $p->get_gallery_image_ids()))
    ];
    $productRecords[] = $rec;
    $add($rec['featured_id'], 'featured', $pid);
    foreach ($rec['gallery_ids'] as $imgId) { $add($imgId, 'gallery', $pid); }
    $map = json_decode((string)get_post_meta($pid, '_emdo_image_map', true), true);
    if (is_array($map)) {
        foreach ($map as $orig=>$mapped) {
            if ((int)$mapped > 0 && get_post_type((int)$mapped)==='attachment') {
                $add((int)$mapped,'supplier_image_map',$pid);
            }
        }
    }
    foreach ([$p->get_description(),$p->get_short_description()] as $html) {
        if (preg_match_all('~<img\b[^>]*\bsrc\s*=\s*["\x27]([^"\x27]+)~i', (string)$html, $m)) {
            foreach ($m[1] as $u) { $add(html_entity_decode($u, ENT_QUOTES), 'inline_description',$pid); }
        }
    }
}
foreach ($rows as $r) {
    $payload=json_decode((string)$r['source_payload'],true);
    if (!is_array($payload)) { continue; }
    foreach ((array)($payload['images'] ?? []) as $src) {
        if (is_string($src) && preg_match('~^https?://~i',$src)) {
            $sourceOnly[$src] = true;
        }
    }
}
$profile = get_user_meta($vendor, 'wcfmmp_profile_settings', true);
if (is_array($profile)) {
    foreach (['banner','mobile_banner','store_logo','logo','gravatar','list_banner'] as $key) {
        if (!empty($profile[$key])) { $add($profile[$key],'vendor_profile_'.$key); }
    }
}
foreach (['wcfm_store_banner','wcfm_store_logo','_wcfm_store_banner','_wcfm_store_logo'] as $key) {
    $v=get_user_meta($vendor,$key,true);
    if ($v) { $add($v,'vendor_meta_'.$key); }
}
usort($productRecords, static fn($a,$b)=>$a['id'] <=> $b['id']);
$records=[];
foreach ($assets as $x) {
    $x['roles']=array_keys($x['roles']);
    $x['product_ids']=array_map('intval',array_keys($x['product_ids']));
    $records[]=$x;
}
usort($records,static fn($a,$b)=>strcmp($a['url'],$b['url']));
$out=[
    'audit'=>'latitudes-source-only-media-readonly-20261024','production_host'=>$site,
    'supplier_id'=>(int)$supplier['id'],'vendor_id'=>$vendor,
    'source_product_count'=>count($rows),'associated_product_count'=>count($productRecords),
    'source_linked_product_count'=>count($vendorIds),'unique_media_count'=>count($records),
    'media_placements'=>$placementCount,'source_original_urls_count'=>count($sourceOnly),
    'products'=>$productRecords, 'media'=>$records, 'source_original_urls'=>array_keys($sourceOnly)
];
echo wp_json_encode($out, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE), "\n";
