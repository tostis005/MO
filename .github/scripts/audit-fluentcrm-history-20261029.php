<?php
/** Read-only WooCommerce buyer coverage audit against FluentCRM; never print email addresses. */
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;
$prefix = $wpdb->prefix;
$table_exists = function ($table) use ($wpdb) {
    return (int) $wpdb->get_var($wpdb->prepare(
        'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=%s', $table
    )) > 0;
};
$crm_table = $prefix . 'fc_subscribers';
$hpos_table = $prefix . 'wc_orders';
$has_crm = $table_exists($crm_table);
$has_hpos = $table_exists($hpos_table);
$hpos_setting = get_option('woocommerce_custom_orders_table_enabled', 'no');
if (!$has_crm) { echo "FLUENT_AUDIT_ERROR=fluentcrm_subscribers_table_not_found\n"; exit(2); }

$crm = [];
$crm_status_counts = [];
$crm_rows = 0;
$crm_duplicates = 0;
$last_id = 0;
$limit = 2000;
do {
    $rows = $wpdb->get_results($wpdb->prepare(
        "SELECT id,email,status FROM `{$crm_table}` WHERE id>%d ORDER BY id LIMIT %d", $last_id, $limit
    ), ARRAY_A);
    if (!is_array($rows)) { throw new RuntimeException('FluentCRM query failed'); }
    foreach ($rows as $row) {
        $last_id = (int) $row['id'];
        ++$crm_rows;
        $email = strtolower(trim((string) $row['email']));
        $status = strtolower((string) $row['status']);
        $crm_status_counts[$status] = ($crm_status_counts[$status] ?? 0) + 1;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { continue; }
        if (isset($crm[$email])) {
            ++$crm_duplicates;
            if ($status === 'subscribed') { $crm[$email] = $status; }
        } else { $crm[$email] = $status; }
    }
} while (count($rows) === $limit);

$stats = ['hpos_setting' => $hpos_setting, 'hpos_exists' => $has_hpos,
    'crm_rows' => $crm_rows, 'crm_unique_valid_emails' => count($crm),
    'crm_duplicate_email_rows' => $crm_duplicates, 'crm_status_counts' => $crm_status_counts];
if ($has_hpos) {
    $stats['hpos_shop_order_rows'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$hpos_table}` WHERE type='shop_order'");
}
$posts_table = $prefix . 'posts';
$meta_table = $prefix . 'postmeta';
$stats['legacy_shop_order_rows'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `{$posts_table}` WHERE post_type='shop_order'");
$source = ($has_hpos && $hpos_setting === 'yes') ? 'hpos' : 'legacy';
if ($source === 'legacy' && $stats['legacy_shop_order_rows'] === 0 && $has_hpos) { $source = 'hpos'; }
$stats['chosen_source'] = $source;
$buyers = [];
$order_status_counts = [];
$orders_read = 0;
$orders_qualifying = 0;
$orders_no_email = 0;
$last_id = 0;
$accepted = ['wc-completed' => true, 'wc-processing' => true, 'wc-refunded' => true];
do {
    if ($source === 'hpos') {
        $sql = $wpdb->prepare(
            "SELECT id,status,billing_email AS email,date_created_gmt AS order_date FROM `{$hpos_table}` WHERE type='shop_order' AND id>%d ORDER BY id LIMIT %d",
            $last_id, $limit
        );
    } else {
        $sql = $wpdb->prepare(
            "SELECT p.ID AS id,p.post_status AS status,pm.meta_value AS email,p.post_date_gmt AS order_date
             FROM `{$posts_table}` p LEFT JOIN `{$meta_table}` pm ON pm.post_id=p.ID AND pm.meta_key='_billing_email'
             WHERE p.post_type='shop_order' AND p.ID>%d ORDER BY p.ID LIMIT %d",
            $last_id, $limit
        );
    }
    $rows = $wpdb->get_results($sql, ARRAY_A);
    if (!is_array($rows)) { throw new RuntimeException('WooCommerce order query failed'); }
    foreach ($rows as $row) {
        $last_id = (int) $row['id'];
        ++$orders_read;
        $status = (string) $row['status'];
        $order_status_counts[$status] = ($order_status_counts[$status] ?? 0) + 1;
        if (!isset($accepted[$status])) { continue; }
        ++$orders_qualifying;
        $email = strtolower(trim((string) $row['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { ++$orders_no_email; continue; }
        $date = (string) $row['order_date'];
        if (!isset($buyers[$email])) {
            $buyers[$email] = ['first' => $date, 'last' => $date, 'orders' => 1];
        } else {
            if ($date !== '' && ($buyers[$email]['first'] === '' || strcmp($date, $buyers[$email]['first']) < 0)) { $buyers[$email]['first'] = $date; }
            if (strcmp($date, $buyers[$email]['last']) > 0) { $buyers[$email]['last'] = $date; }
            ++$buyers[$email]['orders'];
        }
    }
} while (count($rows) === $limit);

$monthly = [];
$missing = 0;
$found = 0;
$subscribed = 0;
$first_missing = null;
$last_missing = null;
$first_order = null;
$last_order = null;
$missing_repeat_buyers = 0;
foreach ($buyers as $email => $buyer) {
    $date = $buyer['first'];
    $month = preg_match('/^\d{4}-\d{2}/', $date) ? substr($date, 0, 7) : 'unknown';
    if (!isset($monthly[$month])) { $monthly[$month] = ['month' => $month,'buyers' => 0,'in_crm' => 0,'missing' => 0,'subscribed' => 0]; }
    ++$monthly[$month]['buyers'];
    $first_order = $first_order === null || strcmp($date, $first_order) < 0 ? $date : $first_order;
    if (strcmp($buyer['last'], (string) $last_order) > 0) { $last_order = $buyer['last']; }
    if (isset($crm[$email])) {
        ++$found;
        ++$monthly[$month]['in_crm'];
        if ($crm[$email] === 'subscribed') { ++$subscribed; ++$monthly[$month]['subscribed']; }
    } else {
        ++$missing;
        ++$monthly[$month]['missing'];
        if ($buyer['orders'] > 1) { ++$missing_repeat_buyers; }
        if ($first_missing === null || strcmp($date, $first_missing) < 0) { $first_missing = $date; }
        if ($last_missing === null || strcmp($buyer['last'], $last_missing) > 0) { $last_missing = $buyer['last']; }
    }
}
$stats += [
    'orders_scanned' => $orders_read, 'qualifying_orders' => $orders_qualifying,
    'qualifying_orders_invalid_or_empty_email' => $orders_no_email,
    'all_order_status_counts' => $order_status_counts,
    'unique_historical_buyers' => count($buyers),
    'buyers_present_in_fluentcrm' => $found,
    'buyers_missing_from_fluentcrm' => $missing,
    'buyers_with_subscribed_status' => $subscribed,
    'missing_buyers_with_multiple_orders' => $missing_repeat_buyers,
    'first_buyer_order_gmt' => $first_order, 'last_buyer_order_gmt' => $last_order,
    'earliest_missing_buyers_first_order_gmt' => $first_missing,
    'latest_missing_buyers_last_order_gmt' => $last_missing,
];
ksort($monthly);
echo 'FLUENT_AUDIT_SUMMARY=' . wp_json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
foreach ($monthly as $row) { echo 'FLUENT_AUDIT_MONTH=' . wp_json_encode($row) . "\n"; }
echo "FLUENT_AUDIT_READ_ONLY_COMPLETE\n";
