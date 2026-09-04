<?php
if (!defined('ABSPATH')) exit(1);

function dump_lines($label,$path,$start=1,$end=9999){
  echo "===== {$label} {$path} =====\n";
  $lines=@file($path,FILE_IGNORE_NEW_LINES);
  if(!$lines){echo "MISSING\n";return;}
  $end=min($end,count($lines));
  for($i=max(1,$start);$i<=$end;$i++) echo $i.': '.$lines[$i-1]."\n";
}
function grep_context($label,$path,$patterns,$radius=10){
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

$inc=get_stylesheet_directory().'/inc/';
grep_context('HOME_REFRESH',$inc.'home-refresh.php',['emo-hero','hero__grid','hero__visual','hero__proof','padding','min-height'],16);
grep_context('HOME_RHYTHM',$inc.'home-rhythm-final-01099.php',['emo-hero','padding','min-height'],12);
grep_context('HERO_BALANCE',$inc.'home-hero-cart-balance-010119.php',['emo-hero','padding','min-height'],10);

// Search all small live child-theme and MU-plugin style-producing files for desktop hero sizing rules.
$roots=[get_stylesheet_directory().'/inc',WP_CONTENT_DIR.'/mu-plugins'];
foreach($roots as $root){
  if(!is_dir($root)) continue;
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
  foreach($it as $f){
    if(!$f->isFile() || $f->getSize()>500000 || strtolower($f->getExtension())!=='php') continue;
    $txt=@file_get_contents($f->getPathname()); if($txt===false || stripos($txt,'emo-hero')===false) continue;
    grep_context('HERO_SOURCE',$f->getPathname(),['.emo-hero {','.emo-hero{','emo-hero__grid','emo-hero__visual','emo-hero__proof','min-height','padding-bottom'],8);
  }
}

$r=wp_remote_get(home_url('/'),['timeout'=>30,'redirection'=>3,'headers'=>['Cache-Control'=>'no-cache','Pragma'=>'no-cache']]);
if(!is_wp_error($r)){
  $html=wp_remote_retrieve_body($r);
  if(preg_match_all('~<style\\b[^>]*>(.*?)</style>~is',$html,$m)){
    $n=0;
    foreach($m[1] as $style){
      if(stripos($style,'.emo-hero')===false) continue;
      $n++;
      echo "===== RENDERED_HERO_STYLE_BLOCK {$n} =====\n".$style."\n";
    }
  }
  $marker='data-mdo-home-featured-special=';
  $pos=strpos($html,$marker);
  if($pos!==false) echo "===== FEATURED_SPECIAL_RENDER =====\n".substr($html,max(0,$pos-1000),3500)."\n";
}
