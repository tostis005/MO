<?php
/**
 * Convert the seven already-published Black Friday Latitudes pack copies
 * from WooCommerce "regular + sale" to a single honest everyday price.
 * Does not edit their Mentta originals, composition, photos, category or stock.
 *
 * MDO_BF_PACK_PRICE_MODE=preflight|apply|verify
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !function_exists('wc_get_product')) exit(2);
$host = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)) exit(2);
$mode = getenv('MDO_BF_PACK_PRICE_MODE') ?: 'preflight';
if (!in_array($mode, ['preflight','apply','verify'], true)) exit(2);
if (!function_exists('emdo_mentta_exclusive_ids')) { fwrite(STDERR,"MENTTA_GUARD_NOT_LOADED\n");exit(3); }
$keys = [
    21814 => 'pasaporte-salvaje',
    21816 => 'reserva-de-caza',
    21818 => 'safari-africano',
    21820 => 'vuelta-al-mundo',
    21822 => 'parrilla-sin-fronteras-xl',
    21824 => 'universo-wagyu',
    21826 => 'leyendas-del-vacuno',
];
$expectedNew = [
    'pasaporte-salvaje' => '127.40',
    'reserva-de-caza' => '107.82',
    'safari-africano' => '128.64',
    'vuelta-al-mundo' => '131.96',
    'parrilla-sin-fronteras-xl' => '145.17',
    'universo-wagyu' => '194.97',
    'leyendas-del-vacuno' => '165.92',
];
$records = [];
foreach ($keys as $sourceId => $key) {
    $sku = 'MDO-WEB-BF17-' . strtoupper(str_replace('-', '_', $key));
    $id = (int)wc_get_product_id_by_sku($sku);
    $p = $id ? wc_get_product($id) : false;
    $source = wc_get_product($sourceId);
    if (!$p || !$p->is_type('simple') || $p->get_status() !== 'publish'
        || (int)get_post_meta($id, '_emdo_web_source_mentta_pack_id', true) !== $sourceId
        || (string)get_post_meta($id, '_emdo_web_pack_key', true) !== $key
        || (int)get_post_field('post_author',$id) !== 4949
        || get_post_meta($id, '_emdo_mentta_only', true) === 'yes'
        || in_array($id, emdo_mentta_exclusive_ids(), true)
        || !$source || get_post_meta($sourceId,'_emdo_mentta_only',true) !== 'yes'
        || $p->get_catalog_visibility('edit') !== 'visible') {
        fwrite(STDERR,"SOURCE_OR_PUBLIC_PACK_GUARD_FAILED $key\n");exit(4);
    }
    $regular = (float)$p->get_regular_price('edit');
    $sale = $p->get_sale_price('edit');
    $expected = (float)$expectedNew[$key];
    $alreadySingle = ('' === (string)$sale) && abs($regular-$expected) < 0.001;
    $legacySale = is_numeric($sale) && abs((float)$sale-$expected)<0.001 && $regular>$expected;
    if (!$alreadySingle && !$legacySale) {
        fwrite(STDERR,"PRICE_GUARD_FAILED $key regular=$regular sale=$sale\n");exit(5);
    }
    $records[] = compact('id','sourceId','key','p','expected','alreadySingle','legacySale');
}
if (count($records) !== 7) exit(5);
if ($mode === 'preflight') {
    foreach ($records as $r) {
        echo 'BF_SINGLE_PRICE_PRECHECK '.wp_json_encode([
            'key'=>$r['key'], 'web_id'=>$r['id'],
            'current_visible_price'=>$r['p']->get_price('edit'),
            'single_target'=>number_format($r['expected'],2,'.',''),
            'already_converted'=>$r['alreadySingle']
        ],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
    }
    echo "SEVEN_BF_SINGLE_PRICES_PREFLIGHT_OK\n"; exit(0);
}
if ($mode === 'apply') {
    foreach ($records as $r) {
        if ($r['alreadySingle']) continue;
        $p = wc_get_product($r['id']);
        $p->set_regular_price(wc_format_decimal($r['expected'], 2));
        $p->set_sale_price('');
        $p->set_date_on_sale_from(null);
        $p->set_date_on_sale_to(null);
        $p->save();
        wc_delete_product_transients($r['id']);
        clean_post_cache($r['id']);
        if (function_exists('rocket_clean_post')) rocket_clean_post($r['id']);
    }
    if (class_exists('WC_Cache_Helper')) WC_Cache_Helper::get_transient_version('product',true);
    wp_cache_flush();
}
foreach ($records as $r) {
    $p = wc_get_product($r['id']);
    $actual = (float)$p->get_price('edit');
    $html = (string)$p->get_price_html();
    if (!$p || $p->get_status() !== 'publish'
        || abs($actual-$r['expected']) > 0.001
        || abs((float)$p->get_regular_price('edit')-$r['expected']) > 0.001
        || (string)$p->get_sale_price('edit') !== ''
        || $p->is_on_sale('edit')
        || stripos($html,'<del') !== false
        || stripos($html,'<ins') !== false) {
        fwrite(STDERR,"BF_SINGLE_PRICE_VERIFY_FAILED {$r['key']} web_id={$r['id']}\n");exit(7);
    }
    echo 'BF_SINGLE_PRICE_VERIFIED '.wp_json_encode([
        'key'=>$r['key'],'web_id'=>$r['id'],'regular_price'=>$p->get_regular_price('edit'),
        'sale_price'=>$p->get_sale_price('edit'),'on_sale'=>$p->is_on_sale('edit')
    ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
echo "SEVEN_BF_PACKS_SINGLE_PRICE_VERIFIED\n";
