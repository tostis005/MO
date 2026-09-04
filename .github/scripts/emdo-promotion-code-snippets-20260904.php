<?php
if (!defined('ABSPATH')) exit(1);
$files=[
 'home'=>WP_PLUGIN_DIR.'/mdo-supplier-sync/includes/class-mdo-home-featured-special.php',
 'promos'=>WP_PLUGIN_DIR.'/mdo-supplier-sync/includes/class-mdo-promotions.php',
];
function slice_lines($path,$a,$b){$l=@file($path,FILE_IGNORE_NEW_LINES); if(!$l)return ['missing'=>$path]; $o=[]; for($i=$a;$i<=$b && $i<=count($l);$i++)$o[$i]=$l[$i-1]; return $o;}
$out=['home_100_190'=>slice_lines($files['home'],100,190),'promos_1_170'=>slice_lines($files['promos'],1,170),'promos_300_400'=>slice_lines($files['promos'],300,400)];
echo 'MDO_PROMO_CODE:'.wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
