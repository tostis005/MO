<?php
if (!defined('ABSPATH')) exit(1);

function dump_lines($label,$path,$start=1,$end=9999){
  echo "===== {$label} {$path} =====\n";
  $lines=@file($path,FILE_IGNORE_NEW_LINES);
  if(!$lines){echo "MISSING\n";return;}
  $end=min($end,count($lines));
  for($i=max(1,$start);$i<=$end;$i++) echo $i.': '.$lines[$i-1]."\n";
}
function grep_context($label,$path,$patterns,$radius=8){
  echo "===== {$label} {$path} =====\n";
  $lines=@file($path,FILE_IGNORE_NEW_LINES); if(!$lines){echo "MISSING\n";return;}
  $printed=[];
  foreach($lines as $i=>$line){
    $hit=false; foreach($patterns as $p){if(stripos($line,$p)!==false){$hit=true;break;}}
    if(!$hit)continue;
    for($j=max(0,$i-$radius);$j<=min(count($lines)-1,$i+$radius);$j++){
      if(isset($printed[$j]))continue; $printed[$j]=1; echo ($j+1).': '.$lines[$j]."\n";
    }
    echo "---\n";
  }
}

$plugin=WP_PLUGIN_DIR.'/mdo-supplier-sync/includes/';
dump_lines('FEATURED_SPECIAL_CLASS',$plugin.'class-mdo-home-featured-special.php',1,220);
grep_context('SPECIALS_CLASS',$plugin.'class-mdo-specials.php',['register_post_type','_emdo_','meta_box','save_post','emdo_special','orderby','menu_order','date_query'],12);

$theme=get_stylesheet_directory().'/inc/';
dump_lines('HERO_BALANCE',$theme.'home-hero-cart-balance-010119.php',1,120);
dump_lines('HOME_RHYTHM',$theme.'home-rhythm-final-01099.php',1,100);
grep_context('HOME_COPY_DEFINITIVE',$theme.'home-copy-definitive-010165.php',['NUESTRA SELECCIÓN','hero','Origen','emdo-home','productores','producer','vendor'],16);

grep_context('HOME_CRITICAL',WP_CONTENT_DIR.'/mu-plugins/elmercado-home-critical-path-010254.php',['hero','min-height','padding','margin','producer','vendor'],10);
grep_context('HOME_RESPONSIVE_VENDORS',WP_CONTENT_DIR.'/mu-plugins/elmercado-home-responsive-vendors-010253.php',['hero','min-height','padding','margin','producer','vendor'],10);
grep_context('HOME_VENDORS_RESPONSIVE',WP_CONTENT_DIR.'/mu-plugins/elmercado-home-vendors-responsive-010252.php',['hero','min-height','padding','margin','producer','vendor'],10);

$r=wp_remote_get(home_url('/'),['timeout'=>30,'redirection'=>3,'headers'=>['Cache-Control'=>'no-cache']]);
if(!is_wp_error($r)){
  $html=wp_remote_retrieve_body($r);
  foreach(['NUESTRA SELECCIÓN','Una forma distinta de elegir','Origen','mdo-home-special','emdo-special','Especial'] as $needle){
    $pos=stripos($html,$needle);
    if($pos!==false){
      $a=max(0,$pos-1600); $frag=substr($html,$a,3200);
      echo "===== HOME_HTML_CONTEXT {$needle} =====\n".$frag."\n";
    }
  }
}
