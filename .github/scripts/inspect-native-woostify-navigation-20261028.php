<?php
/** Read-only: discover exact Woostify previous/next navigation callbacks in live hooks. */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(2);
global $wp_filter;
$matches=[];
foreach ((array)$wp_filter as $hook=>$chain) {
 if (!($chain instanceof WP_Hook)) continue;
 foreach ((array)$chain->callbacks as $priority=>$entries) {
  foreach ((array)$entries as $value) {
   $cb=$value['function']??null;
   if(is_string($cb))$name=$cb;
   elseif(is_array($cb))$name=(is_object($cb[0]??null)?get_class($cb[0]):(string)($cb[0]??'')) .'::'.(string)($cb[1]??'');
   elseif($cb instanceof Closure)$name='closure';
   else $name=gettype($cb);
   if(preg_match('/woostify_product_navigation|woostify_content_top|adjacent_products/i',$name))
      $matches[]=['hook'=>(string)$hook,'priority'=>(int)$priority,'callable'=>$name];
  }
 }
}
echo "NAV_CALLBACKS ".wp_json_encode($matches,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "FUNCTION_NAV_EXISTS ".(function_exists('woostify_product_navigation')?'yes':'no')."\n";
echo "NAV_INSPECTION_DONE\n";
