<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
$domain='elmercadodeorigen.com';
$sys='/var/www/vhosts/system/'.$domain;
$log=$sys.'/logs/access_ssl_log.processed';
$pool='/opt/plesk/php/8.2/etc/php-fpm.d/'.$domain.'.conf';
$custom=$sys.'/conf/php.ini';
$slow=$sys.'/logs/php-fpm_slow.log';
echo "=== SAFE POST-CHANGE VERIFICATION ===\n";
echo 'PROCESSED_LOG_BYTES '.(is_file($log)?(int)filesize($log):-1)."\n";
echo "DISK\n".cmd('df -h /')."\n";
echo "LOGROTATION\n".cmd("plesk bin site -i ".escapeshellarg($domain)." | grep -A6 -i 'Logrotation info'")."\n";
echo "STAGING_CRON\n".cmd("crontab -l 2>/dev/null | grep 'emdo-staging-wp-cron.lock'")."\n";
echo "ACTIVE_GENERATED_POOL\n".cmd("grep -E '^(pm =|pm.max_children|pm.max_requests|slowlog|request_slowlog_timeout|php_value\\[memory_limit\\])' ".escapeshellarg($pool))."\n";
echo "HEALTH\n".cmd("curl -sS -L -o /dev/null --max-time 20 -w 'HTTP=%{http_code} TTFB=%{time_starttransfer} TOTAL=%{time_total}' https://www.elmercadodeorigen.com/")."\n";
echo "=== CAPTURED FPM SLOWLOG ===\n";
if(is_readable($slow)){
  $raw=cmd('tail -n 220 '.escapeshellarg($slow));
  $raw=preg_replace('/(?i)(password|secret|token|key)(\s*[=:]\s*)\S+/','$1$2<redacted>',$raw);
  echo $raw."\n";
}else echo "NO_SLOWLOG_YET\n";
