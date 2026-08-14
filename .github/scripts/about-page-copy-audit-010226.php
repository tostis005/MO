<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function run_cmd(string $cmd): string {
    $out=[]; $rc=0; exec($cmd.' 2>&1',$out,$rc); $text=trim(implode("\n",$out));
    if($text!=='') echo $text."\n";
    if($rc!==0) throw new RuntimeException("Command failed ($rc): $cmd\n$text");
    return $text;
}
$domain='elmercadodeorigen.com';
$sys='/var/www/vhosts/system/'.$domain;
$log=$sys.'/logs/access_ssl_log.processed';

echo "=== 1 TRIM HISTORICAL PROCESSED LOG ===\n";
if(is_file($log)){
    clearstatcache(true,$log); $before=(int)filesize($log); echo "LOG_BEFORE_BYTES $before\n";
    $first=trim((string)shell_exec('head -n 1 '.escapeshellarg($log).' 2>/dev/null'));
    if($before>0 && !preg_match('/\/[A-Z][a-z]{2}\/2026:/',$first)){
        $probe=run_cmd("LC_ALL=C grep -abm1 -E '\\[[0-9]{2}/[A-Z][a-z]{2}/2026:' ".escapeshellarg($log)." | head -n1");
        if(!preg_match('/^(\d+):/',$probe,$m)) throw new RuntimeException('Could not find first 2026 log line');
        $offset=(int)$m[1]; $tmp=$log.'.keep2026.'.getmypid().'.tmp';
        run_cmd('nice -n 19 ionice -c3 tail -c +'.($offset+1).' '.escapeshellarg($log).' > '.escapeshellarg($tmp));
        $tf=trim((string)shell_exec('head -n 1 '.escapeshellarg($tmp).' 2>/dev/null'));
        $tl=trim((string)shell_exec('tail -n 1 '.escapeshellarg($tmp).' 2>/dev/null'));
        if(!preg_match('/\/[A-Z][a-z]{2}\/2026:/',$tf) || !preg_match('/\/2026:/',$tl)){
            @unlink($tmp); throw new RuntimeException('2026 trimmed log verification failed');
        }
        run_cmd('nice -n 19 ionice -c3 cat '.escapeshellarg($tmp).' > '.escapeshellarg($log));
        @unlink($tmp); clearstatcache(true,$log); echo 'LOG_AFTER_BYTES '.(int)filesize($log)."\n"; echo 'LOG_FIRST_2026 '.substr($tf,0,180)."\n"; echo 'LOG_LAST '.substr($tl,0,180)."\n";
    } else { echo "LOG_ALREADY_2026_ONLY_OR_EMPTY\n"; }
}

echo "=== 2 ENABLE PLESK LOG ROTATION ===\n";
run_cmd('plesk bin site -u '.escapeshellarg($domain).' -log-rotate true -log-bytime daily -log-max-num-files 90 -log-compress true');
echo run_cmd("plesk bin site -i ".escapeshellarg($domain)." | grep -A6 -i 'Logrotation info'")."\n";

echo "=== 3 REDUCE STAGING WP-CRON FREQUENCY ===\n";
$cron=trim((string)shell_exec('crontab -l 2>/dev/null'));
$needle='* * * * * flock -n /tmp/emdo-staging-wp-cron.lock';
$replacement='*/15 * * * * flock -n /tmp/emdo-staging-wp-cron.lock';
$count=0; $new=str_replace($needle,$replacement,$cron,$count);
if($count===1){
    $ct='/tmp/emdo-crontab-'.getmypid(); file_put_contents($ct,$new."\n"); run_cmd('crontab '.escapeshellarg($ct)); @unlink($ct);
} elseif($count===0 && str_contains($cron,$replacement)) { echo "STAGING_CRON_ALREADY_15M\n"; }
else { throw new RuntimeException('Unexpected staging cron match count: '.$count); }
echo run_cmd("crontab -l | grep 'emdo-staging-wp-cron.lock'")."\n";

echo "=== 4 TUNE PRODUCTION PHP-FPM AND ENABLE SLOWLOG ===\n";
$conf=$sys.'/conf/php.ini'; $backup=''; $hadConf=is_file($conf);
if($hadConf){$backup=$conf.'.emdo-backup-'.date('YmdHis'); if(!copy($conf,$backup)) throw new RuntimeException('Could not back up custom php.ini');}
try{
    $general='/tmp/emdo-php-general-'.getmypid().'.ini'; file_put_contents($general,"memory_limit=768M\n");
    run_cmd('plesk bin site --update-php-settings '.escapeshellarg($domain).' -settings '.escapeshellarg($general)); @unlink($general);
    $content=is_file($conf)?(string)file_get_contents($conf):'';
    if(str_contains($content,'[php-fpm-pool-settings]')) throw new RuntimeException('Unexpected existing php-fpm-pool-settings section; refusing duplicate block');
    $block="\n[php-fpm-pool-settings]\npm.max_children = 20\npm.max_requests = 500\nslowlog = {$sys}/logs/php-fpm_slow.log\nrequest_slowlog_timeout = 10s\n";
    if(file_put_contents($conf,$block,FILE_APPEND)===false) throw new RuntimeException('Could not write custom FPM settings');
    run_cmd('/usr/local/psa/bin/php_settings -u');
    sleep(2);
    $pool='/opt/plesk/php/8.2/etc/php-fpm.d/'.$domain.'.conf';
    $verify=run_cmd("grep -E '^(pm.max_children|pm.max_requests|slowlog|request_slowlog_timeout|php_value\\[memory_limit\\])' ".escapeshellarg($pool));
    if(!str_contains($verify,'pm.max_children = 20') || !str_contains($verify,'pm.max_requests = 500') || !str_contains($verify,'request_slowlog_timeout = 10s') || !str_contains($verify,'php_value[memory_limit] = 768M')) throw new RuntimeException('Generated PHP-FPM pool did not contain expected settings');
    $health=run_cmd("curl -sS -L -o /dev/null --max-time 15 -w 'HTTP=%{http_code} TTFB=%{time_starttransfer} TOTAL=%{time_total}' https://www.elmercadodeorigen.com/");
    if(!preg_match('/HTTP=(200|301|302)/',$health)) throw new RuntimeException('Production health check failed after PHP-FPM update');
    if($backup!=='') @unlink($backup);
    echo "PHP_FPM_TUNING_OK\n";
}catch(Throwable $e){
    echo 'PHP_FPM_TUNING_ERROR '.$e->getMessage()."\nROLLBACK_START\n";
    $general='/tmp/emdo-php-general-rollback-'.getmypid().'.ini'; file_put_contents($general,"memory_limit=4096M\n"); @shell_exec('plesk bin site --update-php-settings '.escapeshellarg($domain).' -settings '.escapeshellarg($general).' 2>&1'); @unlink($general);
    if($hadConf && $backup!=='' && is_file($backup)){@copy($backup,$conf); @unlink($backup);} elseif(!$hadConf){@unlink($conf);}
    @shell_exec('/usr/local/psa/bin/php_settings -u 2>&1');
    echo "ROLLBACK_DONE\n"; throw $e;
}

echo "=== FINAL ===\n";
echo run_cmd("plesk bin site -i ".escapeshellarg($domain)." | grep -Ei 'Disk space used by Log|Log rotation status|Maximum number of log|Compress log'")."\n";
echo run_cmd("plesk bin site --show-php-settings ".escapeshellarg($domain)." | grep -E 'memory_limit|pm.max_children|pm.max_requests' ")."\n";
