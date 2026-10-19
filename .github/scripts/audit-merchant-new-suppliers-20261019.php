<?php
/** Read-only reconciliation of newly onboarded EMDO suppliers versus Google Merchant country feeds. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) { exit(1); }
foreach (['home','siteurl'] as $option) {
    $host = strtolower((string) wp_parse_url(get_option($option), PHP_URL_HOST));
    if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)) {
        WP_CLI::error('Refusing non-production target');
    }
}
if (!class_exists('MDO_Database') || !function_exists('mdo_gmf_parent_ids_v1') || !function_exists('mdo_gmf_offer_data_v1')) {
    WP_CLI::error('Supplier or Google Merchant modules are not loaded in production');
}
global $wpdb;
$table = MDO_Database::table('suppliers');
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT id, code, name, vendor_user_id, active, last_sync_at FROM {$table} WHERE code IN (%s,%s) ORDER BY code",
    'latitudes', 'chocolates-artesanos-isabel'
), ARRAY_A);
$included = array_fill_keys(array_map('intval', mdo_gmf_parent_ids_v1()), true);
$report = [
    'site' => home_url('/'),
    'module_version' => defined('MDO_GMF_VERSION_V1') ? MDO_GMF_VERSION_V1 : null,
    'feeds' => array_keys(mdo_gmf_actual_country_map_v1()),
    'candidate_count' => count($included),
    'vendors' => []
];
foreach ($rows as $supplier) {
    $id = (int) $supplier['vendor_user_id'];
    $ids = get_posts([
        'post_type'=>'product', 'post_status'=>'any', 'author'=>$id,
        'fields'=>'ids', 'numberposts'=>-1, 'suppress_filters'=>true
    ]);
    $src = MDO_Database::table('source_products');
    $mapped = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT wc_product_id FROM {$src} WHERE supplier_id=%d AND wc_product_id>0",
        (int) $supplier['id']
    ));
    $ids = array_values(array_unique(array_filter(array_map('intval', array_merge((array)$ids, (array)$mapped)))));
    sort($ids);
    $stats = [
        'code'=>$supplier['code'], 'supplier_id'=>(int)$supplier['id'],
        'vendor_id'=>$id, 'active'=>$supplier['active'], 'last_sync'=>$supplier['last_sync_at'],
        'vendor_is_active'=>mdo_gmf_vendor_is_active_v1($id),
        'total_products'=>count($ids), 'published'=>0, 'in_feed_parent_pool'=>0,
        'eligible_ES'=>0, 'eligible_PT'=>0, 'eligible_FR'=>0,
        'missing_from_parent_pool'=>[], 'not_eligible_ES'=>[], 'examples_in_ES'=>[],
        'stock'=>[], 'missing_english'=>0
    ];
    foreach ($ids as $pid) {
        $post = get_post($pid);
        $product = wc_get_product($pid);
        if (!$post instanceof WP_Post || !$product instanceof WC_Product) { continue; }
        $status = (string) $post->post_status;
        $state = (string) $product->get_stock_status();
        $stats['stock'][$state] = ($stats['stock'][$state] ?? 0) + 1;
        if ($status !== 'publish') { continue; }
        $stats['published']++;
        if (!mdo_gmf_english_ready_v1($pid)) { $stats['missing_english']++; }
        if (!isset($included[$pid])) {
            if (count($stats['missing_from_parent_pool'])<35) {
                $stats['missing_from_parent_pool'][] = ['id'=>$pid,'name'=>$product->get_name(),'visibility'=>$product->get_catalog_visibility(),'status'=>$status,'author'=>(int)$post->post_author];
            }
            continue;
        }
        $stats['in_feed_parent_pool']++;
        $offers = $product instanceof WC_Product_Variable ?
            array_filter(array_map('wc_get_product',array_map('absint',$product->get_children()))) : [$product];
        foreach (['ES','PT','FR'] as $country) {
            $hits=0;
            foreach ($offers as $offer_product) {
                if (!$offer_product instanceof WC_Product) { continue; }
                $off = mdo_gmf_offer_data_v1($offer_product,$post,$product instanceof WC_Product_Variable ? $product : null,$country);
                if (is_array($off)) {
                    $hits++;
                    if ($country==='ES' && count($stats['examples_in_ES'])<8) {
                        $stats['examples_in_ES'][]=['id'=>$off['id'],'name'=>$off['title'],'shipping'=>$off['shipping_cost'],'weight'=>$off['shipping_weight']];
                    }
                }
            }
            $stats['eligible_'.$country] += $hits;
        }
        if ($stats['eligible_ES'] === 0 || true) {
            $has_es=false;
            foreach ($offers as $offer_product) {
                if ($offer_product instanceof WC_Product && is_array(mdo_gmf_offer_data_v1($offer_product,$post,$product instanceof WC_Product_Variable ? $product : null,'ES'))) {
                    $has_es=true;break;
                }
            }
            if (!$has_es && count($stats['not_eligible_ES'])<40) {
                $ship=mdo_gmf_vendor_shipping_v1((int)$post->post_author,'ES');
                $stats['not_eligible_ES'][]=[
                    'id'=>$pid,'name'=>$product->get_name(),'price'=>$product->get_price(),
                    'has_image'=>(bool)$product->get_image_id(),
                    'has_description'=>(bool)trim(wp_strip_all_tags($product->get_short_description().' '.$product->get_description())),
                    'can_ship'=>!empty($ship['can_ship']),'shipping_cost'=>$ship['cost'] ?? null,
                    'visibility'=>$product->get_catalog_visibility()
                ];
            }
        }
    }
    $report['vendors'][]=$stats;
}
$codes = array_column($report['vendors'],'code');
if (count($codes)!==2) { $report['warning']='Expected both suppliers but found '.implode(',',$codes); }
foreach (['ES','PT','FR'] as $code) {
    $report['feed_offer_count'][$code]=mdo_gmf_offer_count_v1($code);
}
WP_CLI::log('EMDO_MERCHANT_RECONCILIATION='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
WP_CLI::success('Merchant catalog reconciliation read-only complete');
