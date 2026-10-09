<?php
/**
 * Read-only introspection of locally installed Mentta plugin.
 * Never output credentials, tokens, URLs, option values, or private request/response bodies.
 */
if (!defined('ABSPATH')) {exit(2);}
$dir=WP_PLUGIN_DIR.'/mentta-marketplace';
if (!is_dir($dir)) {echo "MENTTA_PLUGIN_DIAG ".wp_json_encode(array('exists'=>false))."\n";exit(3);}
$files=array();
$iterator=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir,FilesystemIterator::SKIP_DOTS));
foreach($iterator as $file){
 if (!$file->isFile() || !preg_match('/\.php$/i',$file->getFilename()) || $file->getSize()>300000) continue;
 $path=$file->getPathname();
 $relative=substr($path,strlen($dir)+1);
 if (preg_match('~(^|/)(vendor|node_modules|languages|assets|tests|test)(/|$)~i',$relative))continue;
 $contents=file($path,FILE_IGNORE_NEW_LINES);
 $matches=array(); $functions=array();$hooks=array();
 $pattern='/product_cat|term_taxonomy|category|categor[ií]a|posts_per_page|per_page|limit\b|offset\b|wc_get_products|WC_Product_Query|WP_Query|wp_get_post_terms|get_the_terms|wp_get_object_terms|wp_get_object_terms|update_post_meta|wc_get_product\b|post_status|catalog_visibility|sku\b|cron\b|schedule\b|batch\b|stock_status|filter|sync|import|export/i';
 foreach($contents as $ix=>$line) {
    if (preg_match('/(?:function\s+|function\s*\(|public\s+function\s+|private\s+function\s+)([a-zA-Z0-9_]+)/',$line,$m)) {$functions[]=$m[1];}
    if (preg_match('/(?:add_action|add_filter|register_rest_route)\s*\(/',$line)) {
      $hook=preg_replace('/\s+/', ' ',trim($line));
      if (preg_match('/^(.*?)(?:\{|\)|;)/',$hook,$m)) {$hook=$m[1];}
      if (!preg_match('/secret|token|key|pass|http|auth|oauth|consumer/i',$hook)) $hooks[]=array('line'=>$ix+1,'code'=>mb_substr($hook,0,170));
    }
    if (!preg_match($pattern,$line)) continue;
    if (preg_match('/(secret|token|password|passphrase|api[_-]?key|authorization|auth|consumer|license|cookie|private_key|access_key|bearer)/i',$line))continue;
    if (preg_match('/(http:\/\/|https:\/\/|@|[a-z0-9_]+_key|\$wpdb->prepare\([^)]*password)/i',$line)) continue;
    $one=preg_replace('/\s+/',' ',trim($line));
    if (strlen($one)>450)$one=substr($one,0,450).'...';
    if ($one!=='' && count($matches)<170){$matches[]=array('line'=>$ix+1,'code'=>$one);}
 }
 $files[]=array('path'=>$relative,'lines'=>count($contents),'functions'=>array_slice($functions,0,90),'hooks'=>array_slice($hooks,0,30),'matches'=>$matches);
}
echo "MENTTA_PLUGIN_DIAG ".wp_json_encode(array('exists'=>true,'file_count'=>count($files),'files'=>$files),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
