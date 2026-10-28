<?php
/** Read-only PHP reflection for WooCommerce theme navigation, no configuration/secret access. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
foreach(['woostify_content_top_open','woostify_content_top_close','woostify_get_prev_product'] as $fn){
 if(!function_exists($fn))continue;
 $ref=new ReflectionFunction($fn);$file=(string)$ref->getFileName();
 if(!str_contains($file,'/themes/woostify/')){echo "NAV_FUNCTION_OUTSIDE_THEME $fn\n";continue;}
 $lines=file($file,FILE_IGNORE_NEW_LINES);
 $lo=max(1,$ref->getStartLine());
 $hi=min($ref->getEndLine(),$lo+115);
 echo "NAV_FUNCTION_CODE ".wp_json_encode(['function'=>$fn,'theme_relative'=>substr($file,strpos($file,'/themes/')+8),'start'=>$lo,'end'=>$ref->getEndLine()],JSON_UNESCAPED_SLASHES)."\n";
 for($i=$lo;$i<=$hi;$i++){echo $i.": ".substr(trim((string)$lines[$i-1]),0,230)."\n";}
}
