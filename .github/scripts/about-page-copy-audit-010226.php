<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_cmd($cmd) {
    $out = shell_exec($cmd . ' 2>&1');
    return is_string($out) ? trim($out) : '';
}
function inc(&$a,$k,$n=1){$a[$k]=($a[$k]??0)+$n;}
function topn($a,$n=20){arsort($a);return array_slice($a,0,$n,true);}
function group_ua($ua){
    $u=strtolower($ua);
    if(str_contains($u,'monitoring360bot')) return '360 Monitoring';
    if(str_contains($u,'facebookexternalhit')||str_contains($u,'meta-externalagent')||str_contains($u,'facebookcatalog')) return 'Meta/Facebook';
    if(str_contains($u,'googlebot')) return 'Googlebot';
    if(str_contains($u,'bingbot')) return 'Bingbot';
    if(str_contains($u,'gptbot')||str_contains($u,'oai-searchbot')) return 'OpenAI';
    if(str_contains($u,'semrush')) return 'Semrush';
    if(str_contains($u,'ahrefs')) return 'Ahrefs';
    if(str_contains($u,'bytespider')||str_contains($u,'petalbot')||str_contains($u,'mj12bot')||str_contains($u,'crawler')||str_contains($u,'spider')||str_contains($u,'bot')||str_contains($u,'scan')||str_contains($u,'curl/')||str_contains($u,'python')||str_contains($u,'go-http-client')||$ua==='-') return 'Other bot/automation';
    return 'Browser-like/unknown';
}
function endpoint($path){
    $p=parse_url($path,PHP_URL_PATH) ?: $path;
    if($p==='/'||$p==='') return 'home';
    if(str_contains($p,'wp-login.php')) return 'wp-login';
    if(str_contains($p,'xmlrpc.php')) return 'xmlrpc';
    if(str_contains($p,'wp-admin/admin-ajax.php')) return 'admin-ajax';
    if(str_starts_with($p,'/wp-json/')) return 'wp-json';
    if(str_contains($p,'wc-ajax')||str_contains($path,'wc-ajax')) return 'wc-ajax';
    if(str_contains($path,'?s=')||str_contains($path,'&s=')) return 'search';
    if(preg_match('~/producto/|/product/~',$p)) return 'product';
    if(preg_match('~/categoria-producto/|/product-category/~',$p)) return 'category';
    if(preg_match('~\.(css|js|jpg|jpeg|png|webp|gif|svg|woff2?|ico)(\?|$)~i',$path)) return 'static';
    return 'other';
}

$logdir='/var/www/vhosts/system/elmercadodeorigen.com/logs';
$files=array($logdir.'/access_ssl_log',$logdir.'/proxy_access_ssl_log');
$cutoff=strtotime('2026-08-14 08:00:00 UTC');
$end=strtotime('2026-08-14 08:40:00 UTC');
$summary=array('files'=>array(),'requests'=>0,'first'=>'','last'=>'','unique_ips'=>0,'per_minute'=>array(),'status'=>array(),'methods'=>array(),'agents'=>array(),'uas'=>array(),'paths'=>array(),'endpoints'=>array(),'ip_counts'=>array(),'ip_agents'=>array(),'ip_paths'=>array(),'dynamic_per_minute'=>array(),'errors_per_minute'=>array());
$ips=array();$seen=array();
foreach($files as $file){
    if(!is_readable($file)) continue;
    $fh=fopen($file,'rb');$lines=0;$parsed=0;
    while(($line=fgets($fh))!==false){
        $lines++;
        if(!preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) ([^ ]+) HTTP\/[^"]+" (\d{3}) \S+ "[^"]*" "([^"]*)"/',$line,$m)) continue;
        $ts=strtotime($m[2]); if($ts===false||$ts<$cutoff||$ts>$end) continue;
        $parsed++;$ip=$m[1];$method=$m[3];$path=$m[4];$status=$m[5];$ua=$m[6];$minute=gmdate('H:i',$ts);
        $key=sha1($file.'|'.$line); if(isset($seen[$key])) continue; $seen[$key]=1;
        $summary['requests']++;$ips[$ip]=1;
        if($summary['first']===''||$ts<strtotime($summary['first']))$summary['first']=gmdate('c',$ts);
        if($summary['last']===''||$ts>strtotime($summary['last']))$summary['last']=gmdate('c',$ts);
        inc($summary['per_minute'],$minute);inc($summary['status'],$status);inc($summary['methods'],$method);inc($summary['agents'],group_ua($ua));inc($summary['uas'],$ua);inc($summary['paths'],$path);inc($summary['endpoints'],endpoint($path));inc($summary['ip_counts'],$ip);
        if((int)$status>=500) inc($summary['errors_per_minute'],$minute);
        if(endpoint($path)!=='static') inc($summary['dynamic_per_minute'],$minute);
        if(!isset($summary['ip_agents'][$ip]))$summary['ip_agents'][$ip]=array();inc($summary['ip_agents'][$ip],group_ua($ua));
        if(!isset($summary['ip_paths'][$ip]))$summary['ip_paths'][$ip]=array();inc($summary['ip_paths'][$ip],$path);
    }
    fclose($fh);$summary['files'][]=array('name'=>basename($file),'lines'=>$lines,'matched'=>$parsed,'size'=>filesize($file));
}
$summary['unique_ips']=count($ips);
ksort($summary['per_minute']);ksort($summary['dynamic_per_minute']);ksort($summary['errors_per_minute']);
$summary['status']=topn($summary['status'],20);$summary['methods']=topn($summary['methods'],10);$summary['agents']=topn($summary['agents'],20);$summary['uas']=topn($summary['uas'],25);$summary['paths']=topn($summary['paths'],40);$summary['endpoints']=topn($summary['endpoints'],20);
$topips=topn($summary['ip_counts'],20);$safeips=array();
foreach($topips as $ip=>$count){
    $ptr=@gethostbyaddr($ip); if(!$ptr||$ptr===$ip)$ptr='no-ptr';
    $ag=topn($summary['ip_agents'][$ip]??array(),5);$pa=topn($summary['ip_paths'][$ip]??array(),8);
    $safeips[]=array('id'=>substr(hash('sha256',$ip),0,10),'requests'=>$count,'ptr'=>$ptr,'agent_groups'=>$ag,'top_paths'=>$pa);
}
unset($summary['ip_counts'],$summary['ip_agents'],$summary['ip_paths']);$summary['top_clients']=$safeips;
echo "POST_REBOOT_TRAFFIC_JSON " . json_encode($summary,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";

echo "=== PREVIOUS BOOT END ===\n";
echo emdo_cmd("journalctl -b -1 -n 220 --no-pager | tail -n 220") . "\n";
echo "=== LAST REBOOTS ===\n";
echo emdo_cmd("last -x | head -n 20") . "\n";

echo "=== PROD ERROR SIGNATURES TODAY ===\n";
$err=$logdir.'/error_log';
if(is_readable($err)){
    $patterns=array('Allowed memory size'=>'memory_limit','upstream timed out'=>'upstream_timeout','Maximum execution time'=>'max_execution','PHP Fatal'=>'php_fatal','server reached max_children'=>'max_children','Out of memory'=>'oom','MySQL server has gone away'=>'mysql_gone','Too many connections'=>'too_many_connections');
    $counts=array();$recent=array();$fh=fopen($err,'rb');
    while(($line=fgets($fh))!==false){
        foreach($patterns as $needle=>$name) if(stripos($line,$needle)!==false) inc($counts,$name);
        if(str_contains($line,'2026')||str_contains($line,'Aug 14')||str_contains($line,'14-Aug')) $recent[]=$line;
        if(count($recent)>120) array_shift($recent);
    } fclose($fh);
    echo 'ERROR_COUNTS '.json_encode($counts)."\n";
    echo "ERROR_TAIL\n".implode('',array_slice($recent,-80))."\n";
}
$perr=$logdir.'/proxy_error_log';
if(is_readable($perr)){
    echo "PROXY_ERROR_TAIL\n".emdo_cmd("tail -n 120 ".escapeshellarg($perr))."\n";
}
echo "POST_REBOOT_TRAFFIC_END\n";
