<?php
if (!defined('ABSPATH')) exit(1);
$p=WP_PLUGIN_DIR.'/mdo-supplier-sync/includes/class-mdo-home-featured-special.php';
$l=@file($p,FILE_IGNORE_NEW_LINES); if(!$l) throw new RuntimeException('file missing');
$o=[]; for($i=1;$i<=105 && $i<=count($l);$i++) $o[$i]=$l[$i-1];
echo 'HOME_SPECIAL_TOP:'.wp_json_encode($o,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
