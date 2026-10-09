<?php
// Read-only review of Mentta serialized product fields and response shape; omits all access/security information.
if(!defined('ABSPATH'))exit(2);
$file=WP_PLUGIN_DIR.'/mentta-marketplace/mentta-marketplace.php';
if(!is_readable($file))exit(3);
$lines=file($file,FILE_IGNORE_NEW_LINES);
foreach(array(array(675,770),array(770,820)) as $win) {
 echo "MENTTA_SERIALIZATION_WINDOW ".$win[0]."-".$win[1]."\n";
 for($i=$win[0];$i<=$win[1] && $i<=count($lines);$i++) {
  $line=trim($lines[$i-1]);
  if(preg_match('/secret|token|pass|authorization|consumer|cookie|api[_-]?key|license|https?:\/\/|private[_-]?key|password|nonce|customer/i',$line))continue;
  echo $i.": ".substr($line,0,185)."\n";
 }
}
