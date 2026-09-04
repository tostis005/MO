<?php
if (!defined('ABSPATH')) exit(1);
$roots=[WP_PLUGIN_DIR, WPMU_PLUGIN_DIR, get_theme_root()];
$hits=[];
foreach($roots as $root){
 if(!$root || !is_dir($root)) continue;
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
 foreach($it as $f){
  if(!$f->isFile() || strtolower($f->getExtension())!=='php') continue;
  $p=$f->getPathname(); $txt=@file_get_contents($p); if($txt===false) continue;
  if(strpos($txt,'mdo_promotion')===false && strpos($txt,'_mdo_promo_featured_home')===false) continue;
  $lines=preg_split('/\R/',$txt); $sn=[];
  foreach($lines as $i=>$line){
   if(strpos($line,'mdo_promotion')!==false || strpos($line,'_mdo_promo_featured_home')!==false || strpos($line,'_mdo_promo_type')!==false){
    $sn[]=($i+1).':'.trim($line); if(count($sn)>=20) break;
   }
  }
  $hits[str_replace(ABSPATH,'',$p)]=$sn;
 }
}
echo 'MDO_PROMO_SOURCE:'.wp_json_encode($hits,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
