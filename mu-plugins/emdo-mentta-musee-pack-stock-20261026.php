<?php
/**
 * Plugin Name: EMDO - Stock de packs exclusivos Mentta Musée Gourmet
 * Description: El pack solo está disponible en el exportador si están disponibles todas sus referencias reales y unidades.
 * Version: 1.0.0
 */
if (!defined('ABSPATH'))exit;
function emdo_musee_pack_stock_ok($id) {
 static $inProgress=[];
 $id=(int)$id;
 if(!$id || isset($inProgress[$id]))return true;
 if(get_post_meta($id,'_emdo_mentta_only',true)!=='yes' ||
    !get_post_meta($id,'_emdo_mentta_pack_key',true))return true;
 $rows=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 if(!is_array($rows) || !$rows)return false;
 $inProgress[$id]=true;
 try {
  foreach($rows as $line) {
   $component=wc_get_product((int)($line['id']??0));
   $qty=(int)($line['qty']??0);
   if(!$component || $qty<1 || $component->get_status()!=='publish' || !$component->is_in_stock() ||
     ($component->managing_stock() && $component->get_stock_quantity()!==null &&
        (int)$component->get_stock_quantity()<$qty))return false;
  }
  return true;
 } finally { unset($inProgress[$id]); }
}
add_filter('woocommerce_product_get_stock_status',static function($status,$product){
 if(!($product instanceof WC_Product))return $status;
 return emdo_musee_pack_stock_ok($product->get_id())?'instock':'outofstock';
},100,2);
add_filter('woocommerce_product_is_in_stock',static function($inStock,$product){
 if(!($product instanceof WC_Product))return $inStock;
 return $inStock && emdo_musee_pack_stock_ok($product->get_id());
},100,2);
