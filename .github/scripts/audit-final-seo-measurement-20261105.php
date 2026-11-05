<?php
/**
 * Read-only audit: analytics, attribution, Google Merchant connection surface,
 * and the historic English Padron URL. Never write customer/order/product data.
 */
if (!defined('ABSPATH')) { exit(2); }
$report = [
  'audit' => 'emdo-final-seo-ga4-merchant-padron-20261105',
  'woocommerce_active' => function_exists('WC'),
  'known_analytics_options' => [],
  'order_attribution_last_30d_sample' => [],
  'padron_candidates' => [],
];
foreach ([
 'woocommerce_google_analytics_settings',
 'woocommerce_google_analytics_pro_settings',
 'googlesitekit_analytics-4_settings',
 'googlesitekit_analytics_settings',
 'googlesitekit_options',
 'gla_settings',
 'gla_merchant_id',
 'woocommerce_gla_settings',
 'woocommerce_google_listings_and_ads_settings',
 'google_tag_manager',
 'gtm4wp-options'
] as $key) {
  $value = get_option($key, null);
  $report['known_analytics_options'][$key] = [
    'exists' => null !== $value,
    'keys' => is_array($value) ? array_slice(array_map('strval',array_keys($value)),0,35) : [],
  ];
}
if ( function_exists('wc_get_orders') ) {
  $ids=wc_get_orders([
    'limit'=>150, 'return'=>'ids', 'status'=>['wc-processing','wc-completed'],
    'date_created'=>'>'.(time()-30*DAY_IN_SECONDS),
    'orderby'=>'date', 'order'=>'DESC'
  ]);
  $tally=['total'=>0,'source_type'=>[],'utm_source_present'=>0,'referrer_present'=>0];
  foreach($ids as $id){
    $order=wc_get_order($id);if(!$order)continue;
    ++$tally['total'];
    $source=(string)$order->get_meta('_wc_order_attribution_source_type');
    if($source==='')$source='(not recorded)';
    $tally['source_type'][$source]=($tally['source_type'][$source]??0)+1;
    if((string)$order->get_meta('_wc_order_attribution_utm_source')!=='') ++$tally['utm_source_present'];
    if((string)$order->get_meta('_wc_order_attribution_referrer')!=='') ++$tally['referrer_present'];
  }
  $report['order_attribution_last_30d_sample']=$tally;
}
$posts=get_posts([
  'post_type'=>'product', 'post_status'=>['publish','private','draft','trash'],
  'posts_per_page'=>160,'s'=>'padr','suppress_filters'=>true
]);
foreach($posts as $p){
  if (!$p instanceof WP_Post)continue;
  $name=(string)$p->post_title;
  $slug=(string)$p->post_name;
  if(!preg_match('/padro|padr[oó]n/i',remove_accents($name.' '.$slug))) continue;
  $wc=function_exists('wc_get_product')?wc_get_product($p->ID):null;
  $report['padron_candidates'][]=[
    'id'=>(int)$p->ID,'title'=>$name,'slug'=>$slug,'status'=>$p->post_status,
    'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),
    'en_published'=>(string)get_post_meta($p->ID,'_en_US_published',true),
    'visible'=>$wc ? $wc->is_visible():false,
    'in_stock'=>$wc ? $wc->is_in_stock():false,
    'canonical'=>$p->post_status==='publish'?get_permalink($p->ID):''
  ];
}
echo 'EMDO_FINAL_SEO_WP_AUDIT='.wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
