<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
global $wpdb;

$st = MDO_Database::table('suppliers');
$pt = MDO_Database::table('source_products');

$s = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$st} WHERE LOWER(name)=LOWER(%s) OR code=%s OR source_url LIKE %s ORDER BY id DESC LIMIT 1",
        'Selectos de Castilla',
        'selectos-de-castilla',
        '%selectosdecastilla.com%'
    ),
    ARRAY_A
);
if (!$s) { echo "NO_SUPPLIER\n"; exit(2); }

$sid = (int)$s['id'];
$rows = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT id,status,title,source_url,wc_product_id,source_stock_status FROM {$pt} WHERE supplier_id=%d ORDER BY id",
        $sid
    ),
    ARRAY_A
) ?: array();

$groups = array();
$product_tax_classes = array();
foreach ($rows as $r) {
    $path = trim((string)wp_parse_url((string)$r['source_url'], PHP_URL_PATH), '/');
    $seg = $path ? explode('/', $path)[0] : '';
    if (!isset($groups[$seg])) $groups[$seg] = array('count'=>0,'active'=>0,'excluded'=>0,'samples'=>array());
    $groups[$seg]['count']++;
    if (isset($groups[$seg][(string)$r['status']])) $groups[$seg][(string)$r['status']]++;
    if (count($groups[$seg]['samples']) < 8) {
        $groups[$seg]['samples'][] = array(
            'id'=>(int)$r['id'],
            'title'=>$r['title'],
            'url'=>$r['source_url'],
            'status'=>$r['status'],
            'wc'=>(int)$r['wc_product_id']
        );
    }
    $pid = (int)$r['wc_product_id'];
    if ($pid > 0) {
        $p = wc_get_product($pid);
        if ($p) {
            $key = ($p->get_tax_status() ?: '(empty)') . '|' . ($p->get_tax_class() === '' ? '(standard)' : $p->get_tax_class());
            $product_tax_classes[$key] = ($product_tax_classes[$key] ?? 0) + 1;
        }
    }
}
ksort($groups);
ksort($product_tax_classes);

$rates = $wpdb->get_results(
    "SELECT tax_rate_id,tax_rate_country,tax_rate_state,tax_rate,tax_rate_name,tax_rate_priority,tax_rate_compound,tax_rate_shipping,tax_rate_order,tax_rate_class
     FROM {$wpdb->prefix}woocommerce_tax_rates
     ORDER BY tax_rate_class,tax_rate_country,tax_rate_state,tax_rate_priority,tax_rate_order,tax_rate_id",
    ARRAY_A
) ?: array();

$classes = class_exists('WC_Tax') ? WC_Tax::get_tax_classes() : array();
$class_slugs = class_exists('WC_Tax') && method_exists('WC_Tax','get_tax_class_slugs') ? WC_Tax::get_tax_class_slugs() : array();
$tax_classes_option = get_option('woocommerce_tax_classes', '');

$global_tax_class_counts = $wpdb->get_results(
    "SELECT COALESCE(pm.meta_value,'') tax_class, COUNT(*) n
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_tax_class'
     WHERE p.post_type='product'
     GROUP BY COALESCE(pm.meta_value,'')
     ORDER BY n DESC",
    ARRAY_A
) ?: array();

$iva10_products = $wpdb->get_results(
    $wpdb->prepare(
        "SELECT p.ID,p.post_title,p.post_status,p.post_author,pm.meta_value tax_class
         FROM {$wpdb->posts} p
         JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_tax_class'
         WHERE p.post_type='product' AND pm.meta_value=%s
         ORDER BY p.ID LIMIT 100",
        'iva-10'
    ),
    ARRAY_A
) ?: array();

$calc = array();
foreach (array('', 'iva-10', 'iva-21', 'iva-4', 'iva-5', 'iva-2') as $cls) {
    $found = WC_Tax::find_rates(array(
        'country'=>'ES',
        'state'=>'',
        'postcode'=>'28001',
        'city'=>'Madrid',
        'tax_class'=>$cls,
    ));
    $taxes = WC_Tax::calc_tax(100.0, $found, false);
    $calc[$cls === '' ? '(standard)' : $cls] = array(
        'rates'=>$found,
        'tax_on_100'=>array_sum(array_map('floatval',$taxes)),
    );
}

echo 'TAX_PROBE=' . wp_json_encode(array(
    'supplier'=>array('id'=>$sid,'name'=>$s['name'],'vendor_user_id'=>(int)$s['vendor_user_id']),
    'source_total'=>count($rows),
    'source_groups'=>$groups,
    'woo_product_tax_classes'=>$product_tax_classes,
    'tax_classes'=>$classes,
    'tax_class_slugs'=>$class_slugs,
    'tax_classes_option'=>$tax_classes_option,
    'global_tax_class_counts'=>$global_tax_class_counts,
    'iva10_products'=>$iva10_products,
    'calculation_es'=>$calc,
    'tax_rates'=>$rates,
), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . PHP_EOL;
