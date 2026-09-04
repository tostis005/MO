<?php
if (!defined('ABSPATH')) { exit(1); }
$root=WP_PLUGIN_DIR.'/yith-woocommerce-product-add-ons/includes';
$files=[
  $root.'/class-yith-wapo-db.php'=>[175,390],
  $root.'/class-yith-wapo-front.php'=>[480,545],
  $root.'/class-yith-wapo.php'=>[440,535],
];
foreach($files as $path=>$range){
  echo "FILE: ".str_replace(ABSPATH,'',$path)."\n";
  $lines=@file($path); if(!$lines){echo "MISSING\n";continue;}
  for($i=$range[0];$i<=$range[1] && $i<=count($lines);$i++) echo $i.':'.$lines[$i-1];
}
