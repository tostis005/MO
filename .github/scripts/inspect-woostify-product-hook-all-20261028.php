<?php
/** Read-only callback and theme source inspection; never prints private keys or runtime data. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
global $wp_filter;
foreach(['woostify_content_top','woostify_after_header','woocommerce_before_single_product','woocommerce_single_product_summary'] as $hook){
 $records=[];
 if(isset($wp_filter[$hook])&&$wp_filter[$hook] instanceof WP_Hook){
  foreach($wp_filter[$hook]->callbacks as $priority=>$entries)foreach($entries as $entry) {
   $f=$entry['function']??null;
   $name=is_string($f)?$f:(is_array($f)?(is_object($f[0])?get_class($f[0]):(string)$f[0]).'::'.$f[1]:($f instanceof Closure?'closure':'other'));
   $records[]=['priority'=>(int)$priority,'name'=>$name];
  }
 }
 echo 'THEME_HOOK_CALLBACKS '.wp_json_encode(['hook'=>$hook,'callbacks'=>$records])."\n";
}
$root=get_template_directory();
$files=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
$c=0;
foreach($files as $file){
 if(++$c>2200)break;
 if(!$file->isFile()||$file->getExtension()!=='php'||$file->getSize()>250000)continue;
 $lines=file($file->getPathname(),FILE_IGNORE_NEW_LINES);if(!$lines)continue;
 foreach($lines as $i=>$line) {
  if(!str_contains($line,'woostify_product_navigation'))continue;
  echo 'NAV_REGISTRATION '.wp_json_encode(['file'=>str_replace($root.'/','',$file->getPathname()),'line'=>$i+1,'snippet'=>substr(trim($line),0,220)])."\n";
 }
}
echo "HOOK_REGISTRATION_DONE\n";
