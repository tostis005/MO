<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_traffic_inc(&$a,$k,$n=1){$a[$k]=($a[$k]??0)+$n;}
function emdo_traffic_top($a,$limit=20){arsort($a);return array_slice($a,0,$limit,true);}
function emdo_traffic_agent_group($ua){
    $u=strtolower($ua);
    if(preg_match('/facebookexternalhit|meta-externalagent|meta-externalfetcher|facebot|facebookcatalog|facebookbot|instagram/',$u)) return 'Meta/Facebook';
    if(preg_match('/googlebot|google-inspectiontool|adsbot-google|mediapartners-google/',$u)) return 'Google';
    if(str_contains($u,'bingbot')||str_contains($u,'bingpreview')) return 'Bing';
    if(str_contains($u,'ahrefs')) return 'Ahrefs';
    if(str_contains($u,'semrush')) return 'Semrush';
    if(str_contains($u,'mj12bot')) return 'MJ12';
    if(str_contains($u,'dotbot')) return 'DotBot';
    if(str_contains($u,'petalbot')) return 'PetalBot';
    if(str_contains($u,'yandex')) return 'Yandex';
    if(str_contains($u,'bytespider')) return 'ByteSpider';
    if(preg_match('/gptbot|chatgpt-user|oai-searchbot/',$u)) return 'OpenAI';
    if(preg_match('/claudebot|claude-web/',$u)) return 'Anthropic';
    if(preg_match('/bot|spider|crawler|crawl|headless|scrapy|python-requests|curl\//',$u)) return 'Other bot/automation';
    return 'Human-like/unknown';
}
function emdo_traffic_endpoint($target){
    $p=parse_url($target,PHP_URL_PATH); if(!is_string($p)) $p=$target;
    $l=strtolower($target);
    if(str_contains($l,'/wp-json/')) return 'wp-json';
    if(str_contains($l,'/wp-admin/admin-ajax.php')) return 'admin-ajax';
    if(str_contains($l,'wc-ajax=')) return 'wc-ajax';
    if(str_contains($l,'/wp-login.php')) return 'wp-login';
    if(str_contains($l,'/xmlrpc.php')) return 'xmlrpc';
    if(preg_match('/[?&]s=/', $l)) return 'search';
    if(str_starts_with($p,'/producto/')) return 'product';
    if(str_starts_with($p,'/categoria-producto/')) return 'category';
    if($p==='/'||$p==='') return 'home';
    if(preg_match('/\.(?:css|js|jpg|jpeg|png|webp|gif|svg|ico|woff2?|ttf)(?:$|\?)/i',$target)) return 'static';
    return 'other';
}
function emdo_traffic_geo_tool(){
    if(!function_exists('shell_exec')) return array('type'=>'none');
    $g=trim((string)@shell_exec('command -v geoiplookup 2>/dev/null'));
    if($g!=='') return array('type'=>'geoiplookup','bin'=>$g);
    $m=trim((string)@shell_exec('command -v mmdblookup 2>/dev/null'));
    if($m!==''){
        foreach(array('/usr/share/GeoIP/GeoLite2-Country.mmdb','/usr/share/GeoIP/GeoIP2-Country.mmdb','/var/lib/GeoIP/GeoLite2-Country.mmdb') as $db){if(is_readable($db))return array('type'=>'mmdblookup','bin'=>$m,'db'=>$db);}
    }
    return array('type'=>'none');
}
function emdo_traffic_country($ip,$tool){
    if(($tool['type']??'none')==='geoiplookup'){
        $out=(string)@shell_exec(escapeshellcmd($tool['bin']).' '.escapeshellarg($ip).' 2>/dev/null');
        if(preg_match('/:\s*([A-Z]{2})\s*,/',$out,$m)) return $m[1];
    }
    if(($tool['type']??'none')==='mmdblookup'){
        $cmd=escapeshellcmd($tool['bin']).' --file '.escapeshellarg($tool['db']).' --ip '.escapeshellarg($ip).' country iso_code 2>/dev/null';
        $out=(string)@shell_exec($cmd);
        if(preg_match('/"([A-Z]{2})"/',$out,$m)) return $m[1];
    }
    return '??';
}

$vhost=dirname(rtrim(ABSPATH,'/'));
$domain=basename($vhost);
$candidates=array($vhost.'/logs','/var/www/vhosts/system/'.$domain.'/logs');
$logdir=''; foreach($candidates as $d){if(is_dir($d)){ $logdir=$d; break; }}

echo "TRAFFIC_AUDIT_START\n";
echo 'TRAFFIC_LOGDIR '.($logdir!==''?$logdir:'NOT_FOUND')."\n";
if($logdir===''){echo "TRAFFIC_AUDIT_NO_LOGDIR\n"; return;}

$proxy=array(); foreach(array('proxy_access_ssl_log','proxy_access_log') as $n){$p=$logdir.'/'.$n;if(is_readable($p))$proxy[]=$p;}
$apache=array(); foreach(array('access_ssl_log','access_log') as $n){$p=$logdir.'/'.$n;if(is_readable($p))$apache[]=$p;}
$files=$proxy?:$apache;
if(!$files){echo "TRAFFIC_AUDIT_NO_READABLE_LOGS\n"; return;}

$groupCounts=$uaCounts=$pathCounts=$statusCounts=$endpointCounts=$minuteCounts=$methodCounts=$ipCounts=array();
$metaPathCounts=$metaUaCounts=$metaMinuteCounts=$metaStatusCounts=array();
$metaIps=array(); $firstTs=''; $lastTs=''; $lines=0; $parsed=0; $bytesScanned=0; $fileInfo=array();
$maxBytes=50*1024*1024; $maxLinesPerFile=500000;
foreach($files as $file){
    $size=(int)@filesize($file); $start=max(0,$size-$maxBytes); $fh=@fopen($file,'rb'); if(!$fh)continue;
    if($start>0){fseek($fh,$start);fgets($fh);} $local=0; $begin=ftell($fh);
    while(!feof($fh)&&$local<$maxLinesPerFile){
        $line=fgets($fh); if($line===false)break; $lines++;$local++;
        if(!preg_match('/^(\S+) \S+ \S+ \[([^\]]+)\] "(\S+) ([^\"]*) HTTP\/[0-9.]+" (\d{3}) (\S+) "([^\"]*)" "([^\"]*)"/',$line,$m)) continue;
        $parsed++; $ip=$m[1];$ts=$m[2];$method=$m[3];$target=$m[4];$status=$m[5];$ua=$m[8];
        if($firstTs==='')$firstTs=$ts; $lastTs=$ts;
        $minute=substr($ts,0,17); $group=emdo_traffic_agent_group($ua); $endpoint=emdo_traffic_endpoint($target);
        $path=parse_url($target,PHP_URL_PATH); if(!is_string($path)||$path==='')$path=$target; if(strlen($path)>180)$path=substr($path,0,180);
        emdo_traffic_inc($groupCounts,$group);emdo_traffic_inc($uaCounts,substr($ua,0,220));emdo_traffic_inc($pathCounts,$path);emdo_traffic_inc($statusCounts,$status);emdo_traffic_inc($endpointCounts,$endpoint);emdo_traffic_inc($minuteCounts,$minute);emdo_traffic_inc($methodCounts,$method);emdo_traffic_inc($ipCounts,$ip);
        if($group==='Meta/Facebook'){
            emdo_traffic_inc($metaPathCounts,$path);emdo_traffic_inc($metaUaCounts,substr($ua,0,220));emdo_traffic_inc($metaMinuteCounts,$minute);emdo_traffic_inc($metaStatusCounts,$status);$metaIps[$ip]=true;
        }
    }
    $end=ftell($fh);fclose($fh);$scanned=max(0,$end-$begin);$bytesScanned+=$scanned;
    $fileInfo[]=array('name'=>basename($file),'size_bytes'=>$size,'start_offset'=>$start,'scanned_bytes'=>$scanned,'lines'=>$local);
}

$topIpCounts=array_values(emdo_traffic_top($ipCounts,20));
$tool=emdo_traffic_geo_tool(); $countryReq=$countryIps=array();
$topIps=emdo_traffic_top($ipCounts,100);
if(($tool['type']??'none')!=='none'){
    foreach($topIps as $ip=>$n){$cc=emdo_traffic_country($ip,$tool);emdo_traffic_inc($countryReq,$cc,$n);emdo_traffic_inc($countryIps,$cc,1);}
}
$metaCountryReq=$metaCountryIps=array();
if(($tool['type']??'none')!=='none' && $metaIps){
    $metaRank=array(); foreach(array_keys($metaIps) as $ip)$metaRank[$ip]=$ipCounts[$ip]??0; arsort($metaRank);$metaRank=array_slice($metaRank,0,100,true);
    foreach($metaRank as $ip=>$n){$cc=emdo_traffic_country($ip,$tool);emdo_traffic_inc($metaCountryReq,$cc,$n);emdo_traffic_inc($metaCountryIps,$cc,1);}
}
$minuteVals=array_values($minuteCounts);rsort($minuteVals);$metaMinuteVals=array_values($metaMinuteCounts);rsort($metaMinuteVals);
$summary=array(
    'files'=>$fileInfo,'bytes_scanned'=>$bytesScanned,'lines'=>$lines,'parsed_requests'=>$parsed,'first_log_time'=>$firstTs,'last_log_time'=>$lastTs,
    'unique_ips'=>count($ipCounts),'top_ip_request_counts'=>$topIpCounts,'agent_groups'=>emdo_traffic_top($groupCounts,30),'top_user_agents'=>emdo_traffic_top($uaCounts,25),
    'endpoints'=>emdo_traffic_top($endpointCounts,30),'top_paths'=>emdo_traffic_top($pathCounts,30),'statuses'=>emdo_traffic_top($statusCounts,20),'methods'=>emdo_traffic_top($methodCounts,20),
    'peak_requests_per_minute'=>$minuteVals[0]??0,'geoip_tool'=>$tool['type']??'none','top100_ip_country_request_counts'=>emdo_traffic_top($countryReq,30),'top100_ip_country_ip_counts'=>emdo_traffic_top($countryIps,30),
    'meta'=>array('requests'=>$groupCounts['Meta/Facebook']??0,'unique_ips'=>count($metaIps),'peak_requests_per_minute'=>$metaMinuteVals[0]??0,'statuses'=>emdo_traffic_top($metaStatusCounts,20),'top_user_agents'=>emdo_traffic_top($metaUaCounts,20),'top_paths'=>emdo_traffic_top($metaPathCounts,30),'top100_ip_country_request_counts'=>emdo_traffic_top($metaCountryReq,30),'top100_ip_country_ip_counts'=>emdo_traffic_top($metaCountryIps,30))
);
echo 'TRAFFIC_AUDIT_JSON '.wp_json_encode($summary,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
echo "TRAFFIC_AUDIT_END\n";
