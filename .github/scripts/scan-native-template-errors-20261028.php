<?php
// Read-only, privacy-filtered scan for PHP errors causing full product-template stalls.
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(2);
$root=dirname(rtrim(ABSPATH,'/'));
$dirs=[WP_CONTENT_DIR, $root.'/logs','/var/www/vhosts/system/elmercadodeorigen.com/logs'];
$files=[];
foreach($dirs as $dir){
 if(!is_dir($dir))continue;
 foreach(['*error*','*debug*','*php*log*'] as $pattern)
  foreach((array)glob($dir.'/'.$pattern) as $f) if(is_file($f)&&is_readable($f)&&filesize($f)>0) $files[$f]=true;
}
foreach(array_slice(array_keys($files),0,12) as $path){
 $size=(int)filesize($path);
 $fh=fopen($path,'rb');
 if(!$fh)continue;
 $n=min($size,180000);fseek($fh,$size-$n);$raw=stream_get_contents($fh);fclose($fh);
 $counts=['fatal'=>0,'timeout'=>0,'upstream'=>0,'warning'=>0];
 $samples=[];
 foreach(explode("\n",(string)$raw) as $line){
  $kind=null;
  if(preg_match('/PHP Fatal error|Uncaught |Allowed memory size|Maximum execution time/i',$line))$kind='fatal';
  elseif(preg_match('/timeout|timed out/i',$line))$kind='timeout';
  elseif(preg_match('/upstream|proxy_fcgi|AH01071/i',$line))$kind='upstream';
  elseif(preg_match('/PHP Warning/i',$line))$kind='warning';
  if(!$kind)continue;
  $counts[$kind]++;
  if(count($samples)>=12)continue;
  // Only error category and structural tokens: never output original log lines/IP/URIs.
  $msg='OTHER';
  foreach(['Maximum execution time','Allowed memory size','Call to undefined function','Call to a member function','Uncaught Error','Uncaught TypeError','Uncaught Exception','upstream timed out','upstream prematurely closed','PHP Warning'] as $phrase)
   if(stripos($line,$phrase)!==false){$msg=$phrase;break;}
  $samples[]=['kind'=>$kind,'type'=>$msg];
 }
 $fatalDetails=[];
 foreach(explode("\\n",(string)$raw) as $line) {
  if(!preg_match('/(?:PHP Fatal error|Uncaught (?:Error|TypeError|RuntimeException|Exception)|Allowed memory size|Maximum execution time)/i',$line))continue;
  $kind='OTHER';
  foreach(['Call to undefined function','Call to a member function','Cannot redeclare','Uncaught TypeError','Uncaught Error','Allowed memory size','Maximum execution time','Uncaught Exception','Uncaught RuntimeException','Unknown named parameter'] as $phrase)
   if(stripos($line,$phrase)!==false){$kind=$phrase;break;}
  $place='unknown';
  if(preg_match('~(?:/wp-content/|/wp-includes/)([a-zA-Z0-9_./-]+?\\.php)(?::|\\b)~',$line,$m))$place=substr($m[1],0,140);
  if(count($fatalDetails)<12)$fatalDetails[]=['kind'=>$kind,'file'=>$place];
 }
 echo 'NATIVE_TEMPLATE_SERVER_LOG_SUMMARY '.wp_json_encode(['file'=>basename($path),'scanned'=>$n,'counts'=>$counts,'error_samples'=>$samples,'fatal_details'=>$fatalDetails])."\n";
}
echo "NATIVE_TEMPLATE_ERROR_SCAN_COMPLETE\n";
