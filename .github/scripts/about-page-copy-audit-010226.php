<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$vhost=dirname(rtrim(ABSPATH,'/'));
$domain=basename($vhost);
$candidates=array($vhost.'/logs','/var/www/vhosts/system/'.$domain.'/logs');
$dirs=array();
foreach($candidates as $d){if(is_dir($d))$dirs[]=$d;}
echo "LOG_DISCOVERY_START\n";
foreach(array_unique($dirs) as $d){
    echo 'LOG_DIR '.$d."\n";
    $entries=@scandir($d); if(!is_array($entries))continue;
    foreach($entries as $name){
        if($name==='.'||$name==='..')continue;
        $p=$d.'/'.$name;
        if(!preg_match('/access|log|error/i',$name))continue;
        $rp=@realpath($p); $st=@stat($p); $lst=@lstat($p);
        echo 'LOG_FILE '.wp_json_encode(array(
            'name'=>$name,'is_link'=>is_link($p),'realpath'=>$rp?:'',
            'readable'=>is_readable($p),'size'=>$st?(int)$st['size']:null,
            'link_size'=>$lst?(int)$lst['size']:null,'mtime'=>$st?date('c',(int)$st['mtime']):null
        ),JSON_UNESCAPED_SLASHES)."\n";
    }
}
echo "LOG_DISCOVERY_END\n";
