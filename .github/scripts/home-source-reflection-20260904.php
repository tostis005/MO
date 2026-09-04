<?php
if (!defined('ABSPATH')) exit(1);

function show_callable($hook,$prio,$fn){
    $name=''; $ref=null;
    try{
        if(is_string($fn)){ $name=$fn; if(function_exists($fn)) $ref=new ReflectionFunction($fn); }
        elseif(is_array($fn)){ $name=(is_object($fn[0])?get_class($fn[0]):(string)$fn[0]).'::'.$fn[1]; $ref=new ReflectionMethod($fn[0],$fn[1]); }
        elseif($fn instanceof Closure){ $name='Closure'; $ref=new ReflectionFunction($fn); }
        elseif(is_object($fn) && method_exists($fn,'__invoke')){ $name=get_class($fn).'::__invoke'; $ref=new ReflectionMethod($fn,'__invoke'); }
    }catch(Throwable $e){}
    echo "CALLBACK {$hook} {$prio} {$name}";
    if($ref) echo ' FILE='.$ref->getFileName().' LINES='.$ref->getStartLine().'-'.$ref->getEndLine();
    echo "\n";
}

foreach(['MDO_Home_Featured_Special','MDO_Specials'] as $cls){
  if(class_exists($cls)){
    $r=new ReflectionClass($cls);
    echo "CLASS {$cls} FILE=".$r->getFileName().' LINES='.$r->getStartLine().'-'.$r->getEndLine()."\n";
  }
}

global $wp_filter;
foreach(['the_content','wp_enqueue_scripts','wp_head','wp_footer','template_include','template_redirect'] as $hook){
 if(empty($wp_filter[$hook])) continue;
 foreach($wp_filter[$hook]->callbacks as $prio=>$callbacks){ foreach($callbacks as $cb){ show_callable($hook,$prio,$cb['function']); }}
}

$roots=[WP_CONTENT_DIR.'/mu-plugins',get_stylesheet_directory(),WP_PLUGIN_DIR];
$patterns=['emdo-home__hero','Una forma distinta de elegir','NUESTRA SELECCIÓN','MDO_Home_Featured_Special','class MDO_Specials','emdo_special'];
foreach($roots as $root){
 if(!is_dir($root)) continue;
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
 foreach($it as $f){
  if(!$f->isFile()||$f->getSize()>1500000) continue; $ext=strtolower($f->getExtension()); if(!in_array($ext,['php','css','js'],true)) continue;
  $p=$f->getPathname(); if(strpos($p,'/vendor/')!==false||strpos($p,'/node_modules/')!==false||strpos($p,'/cache/')!==false) continue;
  $txt=@file_get_contents($p); if($txt===false) continue;
  foreach($patterns as $pat){ if(stripos($txt,$pat)!==false){ echo "SOURCE_MATCH {$pat} FILE={$p}\n"; break; }}
 }
}
