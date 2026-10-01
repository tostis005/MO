<?php
if (!defined('ABSPATH')) exit(1);
global $wpdb;

function hvd_out($label,$value=null){
  if (is_array($value)||is_object($value)) $value=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  echo $label.($value===null?'':': '.(string)$value)."\n";
}

$patterns=['emo-hero__visual--vendors','emo-vendor-count-','emo-hero-card--','array_slice(','posts_per_page','numberposts'];
$roots=[get_stylesheet_directory(), WP_PLUGIN_DIR, WPMU_PLUGIN_DIR];
foreach($roots as $root){
  if(!$root || !is_dir($root)) continue;
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root,FilesystemIterator::SKIP_DOTS));
  foreach($it as $f){
    if(!$f->isFile() || $f->getSize()>1000000) continue;
    $ext=strtolower($f->getExtension());
    if(!in_array($ext,['php','css','js'],true)) continue;
    $p=$f->getPathname();
    if(strpos($p,'/vendor/')!==false || strpos($p,'/node_modules/')!==false) continue;
    $txt=@file_get_contents($p);
    if($txt===false || strpos($txt,'emo-hero__visual--vendors')===false) continue;
    $lines=preg_split('/\R/',$txt);
    $hits=[];
    foreach($lines as $i=>$line){
      foreach($patterns as $pat){
        if(stripos($line,$pat)!==false){ $hits[$i]=true; break; }
      }
    }
    hvd_out('MATCH_FILE',['path'=>$p,'size'=>$f->getSize()]);
    foreach(array_keys($hits) as $i){
      $a=max(0,$i-14); $b=min(count($lines)-1,$i+22);
      echo "CONTEXT ".($i+1)."\n";
      for($j=$a;$j<=$b;$j++) echo ($j+1).': '.$lines[$j]."\n";
      echo "---\n";
    }
  }
}

// Fetch fresh public Home.
$url=home_url('/').'?hero_vendor_diag='.time();
$r=wp_remote_get($url,['timeout'=>30,'headers'=>['Cache-Control'=>'no-cache','Pragma'=>'no-cache']]);
if(is_wp_error($r)) throw new RuntimeException($r->get_error_message());
$html=(string)wp_remote_retrieve_body($r);
hvd_out('HOME_HTTP',['code'=>wp_remote_retrieve_response_code($r),'bytes'=>strlen($html)]);

libxml_use_internal_errors(true);
$doc=new DOMDocument();
$doc->loadHTML('<?xml encoding="utf-8" ?>'.$html);
$xp=new DOMXPath($doc);
$visual=$xp->query("//*[contains(concat(' ',normalize-space(@class),' '),' emo-hero__visual--vendors ')]")->item(0);
if(!$visual) throw new RuntimeException('Vendor hero visual not found');
hvd_out('VISUAL_CLASS',$visual->getAttribute('class'));

$cards=$xp->query(".//*[contains(concat(' ',normalize-space(@class),' '),' emo-hero-card ')]",$visual);
$rows=[];
foreach($cards as $idx=>$card){
  $nameNode=$xp->query(".//figcaption//strong",$card)->item(0);
  $img=$xp->query(".//img",$card)->item(0);
  $a=$xp->query(".//a",$card)->item(0);
  $row=[
    'index'=>$idx+1,
    'class'=>$card->getAttribute('class'),
    'name'=>$nameNode?trim($nameNode->textContent):'',
    'href'=>$a?$a->getAttribute('href'):'',
  ];
  if($img){
    foreach(['src','srcset','sizes','width','height','loading','fetchpriority','decoding'] as $attr) $row[$attr]=$img->getAttribute($attr);
  }
  $rows[]=$row;
}
hvd_out('CARDS',$rows);

// Try to enumerate marketplace vendors with published products.
$vendors=[];
$user_ids=$wpdb->get_col("SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' ORDER BY post_author");
foreach($user_ids as $uid){
  $uid=(int)$uid;
  $u=get_user_by('id',$uid);
  if(!$u) continue;
  $store=get_user_meta($uid,'store_name',true);
  if(!$store) $store=$u->display_name;
  $count=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$uid));
  $profile=get_user_meta($uid,'wcfmmp_profile_settings',true);
  $banner='';
  if(is_array($profile)){
    foreach(['banner','banner_id','store_banner','mobile_banner','gravatar'] as $k){
      if(!empty($profile[$k])) $banner.=$k.'='. (is_scalar($profile[$k])?$profile[$k]:'[complex]').';';
    }
  }
  $vendors[]=['id'=>$uid,'login'=>$u->user_login,'display'=>$u->display_name,'store'=>$store,'products'=>$count,'banner_meta'=>$banner];
}
usort($vendors,static fn($a,$b)=>$b['products']<=>$a['products']);
hvd_out('PUBLISHED_PRODUCT_VENDORS',$vendors);

hvd_out('HOME_VENDOR_DIAGNOSTIC_OK');
