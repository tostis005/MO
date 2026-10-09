<?php
/**
 * Read-only inspect installed Mentta product-query implementation.
 * Outputs only the two known source-code windows for product eligibility and category mapping.
 * Removes any credential-bearing lines from output.
 */
if (!defined('ABSPATH')) {exit(2);}
$f=WP_PLUGIN_DIR.'/mentta-marketplace/mentta-marketplace.php';
if (!is_readable($f)) {echo "MENTTA_PRODUCT_QUERY_MISSING\n";exit(3);}
$lines=file($f,FILE_IGNORE_NEW_LINES);
$windows=array(array(400,515),array(625,675));
foreach($windows as $window) {
 echo "MENTTA_QUERY_WINDOW ".$window[0]."-".$window[1]."\n";
 for($i=$window[0];$i<=$window[1] && $i<=count($lines);$i++){
   $line=$lines[$i-1];
   if (preg_match('/secret|token|pass|authorization|consumer|cookie|api[_-]?key|license|http:\/\/|https:\/\/|private[_-]?key|password|nonce|customer/i',$line)) {
     echo $i.": [sensitive line omitted]\n";continue;
   }
   if(strlen($line)>220) $line=substr($line,0,220).'...';
   echo $i.": ".trim($line)."\n";
 }
}
