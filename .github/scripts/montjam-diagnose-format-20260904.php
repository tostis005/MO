<?php
if (!defined('ABSPATH')) { exit(1); }
$roots=[];
foreach(glob(WP_PLUGIN_DIR.'/*yith*product*add*') as $d){ if(is_dir($d)) $roots[]=$d; }
foreach(glob(WP_PLUGIN_DIR.'/*yith*wapo*') as $d){ if(is_dir($d) && !in_array($d,$roots,true)) $roots[]=$d; }
echo 'YITH_ROOTS: '.wp_json_encode($roots,JSON_UNESCAPED_SLASHES)."\n";
$needles=['transient','cache','blocks_assoc','get_blocks','show_in_products'];
foreach($roots as $root){
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
  foreach($it as $f){
    if(!$f->isFile() || strtolower($f->getExtension())!=='php') continue;
    $path=$f->getPathname();
    $lines=@file($path); if(!$lines) continue;
    foreach($lines as $i=>$line){
      $low=strtolower($line);
      foreach($needles as $n){
        if(strpos($low,$n)!==false){
          echo 'MATCH: '.str_replace(ABSPATH,'',$path).':'.($i+1).':'.trim($line)."\n";
          break;
        }
      }
    }
  }
}
