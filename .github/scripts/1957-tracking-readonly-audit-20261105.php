<?php
/** Read-only anonymous WooCommerce tracking note audit, 5 Nov 2026. */
if (!defined('ABSPATH') || !function_exists('wc_get_orders') || !function_exists('wc_get_order_notes')) {
    throw new RuntimeException('WooCommerce unavailable');
}
$site = strtolower((string)wp_parse_url(get_option('siteurl'), PHP_URL_HOST));
if (!in_array($site, ['elmercadodeorigen.com', 'www.elmercadodeorigen.com'], true)) {
    throw new RuntimeException('Unexpected production site');
}
$orders = wc_get_orders([
    'limit'=>350, 'orderby'=>'date', 'order'=>'DESC',
    'date_created'=>'>=2026-10-25', 'type'=>'shop_order',
]);
$total=0; $oils=0; $noted=0; $samples=[];
foreach($orders as $o) {
    $total++;
    $has_1957=false;
    foreach ($o->get_items('line_item') as $item) {
        $product=$item->get_product();
        if (!$product) continue;
        $parent=$product->is_type('variation') ? $product->get_parent_id() : $product->get_id();
        if ((int)get_post_field('post_author', $parent) === 3) { $has_1957=true; break; }
    }
    if (!$has_1957) continue;
    $oils++;
    $notes=wc_get_order_notes(['order_id'=>$o->get_id(), 'limit'=>20, 'type'=>'customer']);
    if (!$notes) continue;
    $noted++;
    foreach($notes as $n) {
        $body=(string)$n->content;
        if(!preg_match('/seguimiento|localizador|transportista|env[ií]o|recogido|01303A/i', $body)) continue;
        $body=str_ireplace(array_filter([
          $o->get_billing_first_name().' '.$o->get_billing_last_name(),
          $o->get_shipping_first_name().' '.$o->get_shipping_last_name(),
          $o->get_billing_first_name(),$o->get_billing_last_name(),
          $o->get_shipping_first_name(),$o->get_shipping_last_name()
        ]),'[CLIENTE]',$body);
        $body=preg_replace('/(Hola|Estimad[oa])\s+[^,:\r\n]{1,80}([,:])/iu','$1 [CLIENTE]$2',$body);
        $body=preg_replace('/01303A[0-9A-Z]+/i','[CODIGO]',$body);
        $body=preg_replace('/[0-9]{8,}/','[NUMERO]',$body);
        $body=preg_replace('/[\w.+-]+@[\w.-]+\.[A-Za-z]{2,}/','[EMAIL]',$body);
        $body=preg_replace('/https?:\/\/[^\s<>]+/i','[ENLACE]', $body);
        $body=mb_substr($body,0,1300);
        $samples[]=['order_id'=>(int)$o->get_id(), 'note_date'=>($n->date_created ? (string)$n->date_created : ''), 'example_redacted'=>$body];
        break;
    }
    if(count($samples)>=5)break;
}
echo 'MDO_TRACKING_AUDIT='.wp_json_encode(['scanned'=>$total, 'vendor_1957'=>$oils, 'customer_notes'=>$noted, 'tracking_examples'=>$samples],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
