<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
$domain='elmercadodeorigen.com';
$sys='/var/www/vhosts/system/'.$domain;
$log=$sys.'/logs/access_ssl_log.processed';
$pool='/opt/plesk/php/8.2/etc/php-fpm.d/'.$domain.'.conf';
$custom=$sys.'/conf/php.ini';
echo "=== SAFE POST-CHANGE VERIFICATION ===\n";
echo 'PROCESSED_LOG_BYTES '.(is_file($log)?(int)filesize($log):-1)."\n";
echo 'PROCESSED_LOG_FIRST '.substr(cmd('head -n 1 '.escapeshellarg($log)),0,180)."\n";
echo 'PROCESSED_LOG_LAST '.substr(cmd('tail -n 1 '.escapeshellarg($log)),0,180)."\n";
echo "DISK\n".cmd('df -h /')."\n";
echo "LOG_DIR_DU\n".cmd('du -sh '.escapeshellarg($sys.'/logs').' 2>/dev/null')."\n";
echo "LOGROTATION\n".cmd("plesk bin site -i ".escapeshellarg($domain)." | grep -A6 -i 'Logrotation info'")."\n";
echo "STAGING_CRON\n".cmd("crontab -l 2>/dev/null | grep 'emdo-staging-wp-cron.lock'")."\n";
echo "CUSTOM_PHP_INI\n".(is_readable($custom)?cmd('cat '.escapeshellarg($custom)):'MISSING')."\n";
echo "ACTIVE_GENERATED_POOL\n".cmd("grep -E '^(pm =|pm.max_children|pm.max_requests|slowlog|request_slowlog_timeout|php_value\\[memory_limit\\])' ".escapeshellarg($pool))."\n";
echo "FPM_SERVICE\n".cmd("systemctl show plesk-php82-fpm.service -p ActiveState -p SubState -p ActiveEnterTimestamp 2>/dev/null")."\n";
echo "HEALTH\n".cmd("curl -sS -L -o /dev/null --max-time 20 -w 'HTTP=%{http_code} TTFB=%{time_starttransfer} TOTAL=%{time_total}' https://www.elmercadodeorigen.com/")."\n";
echo "SLOWLOG\n".cmd("ls -l ".escapeshellarg($sys.'/logs/php-fpm_slow.log')." 2>/dev/null || echo 'not-created-yet (normal until a request exceeds 10s)'")."\n";
