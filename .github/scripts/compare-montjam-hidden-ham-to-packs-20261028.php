<?php
// Read-only production comparison of a previously imported Mentta-only Montjam ham and six new packs.
if (!defined('ABSPATH') || !class_exists('WC_Mentta_Plugin')) exit(2);
if (!defined('REST_REQUEST')) define('REST_REQUEST', true);
$ids = [17129, 21814, 21818, 21820, 21904, 21906, 21908];
foreach ($ids as $id) {
  $p = wc_get_product($id);
  if (!$p) { echo "NOT_FOUND $id\n"; continue; }
  $pa = wp_get_object_terms($id, 'pa_productor', ['fields'=>'slugs']);
  $req = new WP_REST_Request('GET','/mentta_marketplace/products');
  $req->set_param('product_id', $id);
  $_SERVER['REQUEST_URI'] = '/wp-json/mentta_marketplace/products?product_id='.$id;
  $r = WC_Mentta_Plugin::get_products($req);
  $b = $r instanceof WP_REST_Response ? $r->get_data() : [];
  $d = is_object($b) ? ($b->data ?? []) : ($b['data'] ?? []);
  $e = is_array($d) ? ($d[0] ?? []) : [];
  $vars = [];
  foreach (($e['Variations'] ?? []) as $v) {
    $vars[] = ['visible'=>$v['IsVisible']??null, 'available'=>$v['IsInStock']??null,
      'purchasable'=>$v['IsPurchasable']??null,'image_present'=>!empty($v['Image']),
      'price'=>$v['PriceIncludingTax']??null];
  }
  echo 'MENTTA_OLD_NEW '.wp_json_encode([
    'id'=>$id, 'name'=>$p->get_name(), 'slug'=>$p->get_slug(),
    'wc_visibility'=>$p->get_catalog_visibility(), 'published'=>$p->get_status()==='publish',
    'stock'=>$p->is_in_stock(), 'purchasable'=>$p->is_purchasable(),
    'producer'=>is_wp_error($pa)?[]:$pa,
    'categories'=>wp_get_post_terms($id,'product_cat',['fields'=>'slugs']),
    'is_in_exclusive_list'=>in_array($id, emdo_mentta_exclusive_ids(), true),
    'type'=>$p->get_type(), 'image_path'=>parse_url((string)wp_get_attachment_url($p->get_image_id()),PHP_URL_PATH),
    'feed_status'=>$r instanceof WP_REST_Response?$r->get_status():null,
    'feed_visible'=>$e['IsVisible']??null,
    'feed_stock'=>$e['IsInStock']??null,
    'feed_purchasable'=>$e['IsPurchasable']??null,
    'feed_image_present'=>!empty($e['Image']),
    'feed_variations'=>$vars,
    'special_mentta_source_id'=>(int)get_post_meta($id,'_emdo_mentta_source_id',true),
  ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
