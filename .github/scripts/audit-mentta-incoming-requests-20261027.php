<?php
/**
 * Read-only privacy-safe summary of incoming Mentta API GET requests in local vhost logs.
 * NEVER outputs IP, user agent, URL, credentials, query string or customer data.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$siteRoot=dirname(rtrim(ABSPATH,'/'));
$paths=[];
foreach([$siteRoot.'/logs','/var/www/vhosts/system/elmercadodeorigen.com/logs'] as $dir){
 if(!is_dir($dir))continue;
 foreach(['*access*log*','*proxy*log*','*ssl*log*'] as $pattern)foreach((array)glob($dir.'/'.$pattern) as $f){
  if(is_file($f)&&is_readable($f)&&!str_ends_with($f,'.gz'))$paths[realpath($f)]=$f;
 }
}
$files=[];$summary=[];$status=[];$recent=[];
foreach(array_slice(array_values($paths),0,16) as $file){
 $size=filesize($file);if(!$size)continue;
 $fh=fopen($file,'rb');if(!$fh)continue;
 $limit=8*1024*1024;
 fseek($fh,max(0,$size-$limit));$data=stream_get_contents($fh);
 fclose($fh);if(!is_string($data))continue;
 if($size>$limit)$data=substr($data,(int)strpos($data,"\n")+1);
 $matches=0;
 foreach(explode("\n",$data) as $line){
  if(!str_contains($line,'mentta_marketplace/products'))continue;
  if(!preg_match('~"(?P<method>GET|POST|HEAD)\s+(?P<url>\S*mentta_marketplace/products\S*)\s+HTTP/[^"]+"\s+(?P<status>\d{3})~',$line,$m))continue;
  $matches++;
  $url=html_entity_decode($m['url']);
  $query=(string)parse_url($url,PHP_URL_QUERY);
  $params=[];parse_str($query,$params);
  $page=isset($params['page'])?(int)$params['page']:1;
  $pagesize=isset($params['page_size'])?(int)$params['page_size']:50;
  $kind=isset($params['product_id'])?'individual':'list';
  $k=$kind.'/page='.$page.'/size='.$pagesize;
  $summary[$k]=($summary[$k]??0)+1;
  $status[$m['status']]=($status[$m['status']]??0)+1;
  preg_match('~\[(\d{2}/[A-Za-z]{3}/\d{4}:\d{2}:\d{2})~',$line,$date);
  $recent[]=['time'=>($date[1]??'unknown'),'method'=>$m['method'],'status'=>(int)$m['status'],'mode'=>$kind,'page'=>$page,'size'=>$pagesize];
 }
 $files[]=['kind'=>basename($file),'size_bytes'=>$size,'requests_in_tail'=>$matches];
}
ksort($summary);ksort($status);
echo 'MENTTA_TRAFFIC_AUDIT '.wp_json_encode(['files_checked'=>$files,'request_counts_by_page'=>$summary,'status_counts'=>$status,'recent_requests'=>array_slice($recent,-30)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
