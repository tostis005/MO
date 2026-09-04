<?php
if (!defined('ABSPATH')) exit(1);

function emdo_context($path, $patterns, $radius=8) {
    $lines = @file($path, FILE_IGNORE_NEW_LINES);
    if (!$lines) return;
    $hits=[];
    foreach ($lines as $i=>$line) {
        foreach ($patterns as $pat) {
            if (stripos($line, $pat)!==false) { $hits[]=$i; break; }
        }
    }
    if (!$hits) return;
    echo "===== MATCH_FILE {$path} =====\n";
    $printed=[];
    foreach ($hits as $hit) {
        $a=max(0,$hit-$radius); $b=min(count($lines)-1,$hit+$radius);
        for($i=$a;$i<=$b;$i++) {
            if(isset($printed[$i])) continue;
            $printed[$i]=1;
            echo ($i+1).': '.$lines[$i]."\n";
        }
        echo "---\n";
    }
}

$roots=[get_stylesheet_directory(), WP_PLUGIN_DIR];
$patterns=['emdo-home__hero','Una forma distinta de elegir','emdo_special','_emdo_gallery','_emdo_price_mode','emdo-especiales'];
foreach($roots as $root){
    if(!is_dir($root)) continue;
    $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach($it as $f){
        if(!$f->isFile() || $f->getSize()>600000) continue;
        $ext=strtolower($f->getExtension());
        if(!in_array($ext,['php','css','js'],true)) continue;
        $p=$f->getPathname();
        if(strpos($p,'/vendor/')!==false || strpos($p,'/node_modules/')!==false) continue;
        $txt=@file_get_contents($p);
        if($txt===false) continue;
        $match=false;
        foreach($patterns as $pat){ if(stripos($txt,$pat)!==false){$match=true;break;} }
        if($match) emdo_context($p,$patterns,10);
    }
}

// Registered callbacks around homepage rendering / specials.
global $wp_filter;
foreach(['wp_head','wp_footer','the_content','loop_start','template_redirect'] as $hook){
    if(empty($wp_filter[$hook])) continue;
    echo "HOOK {$hook}\n";
    foreach($wp_filter[$hook]->callbacks as $prio=>$callbacks){
        foreach($callbacks as $cb){
            $fn=$cb['function']; $name='';
            if(is_string($fn)) $name=$fn;
            elseif(is_array($fn)) $name=(is_object($fn[0])?get_class($fn[0]):(string)$fn[0]).'::'.$fn[1];
            if(preg_match('/emdo|home|special|hero/i',$name)) echo "  {$prio} {$name}\n";
        }
    }
}
