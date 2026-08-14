<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}

echo "=== SERVER CAPACITY ===\n";
echo 'NPROC '.cmd('nproc')."\n";
echo "FREE\n".cmd('free -h')."\n";
echo "MEMINFO\n".cmd("grep -E 'MemTotal|MemAvailable|SwapTotal|SwapFree' /proc/meminfo")."\n";
echo "LOAD\n".cmd('uptime')."\n";
echo "DISK\n".cmd('df -h / /var /var/www 2>/dev/null')."\n";

echo "=== LARGE LOG FILES ===\n";
echo cmd("find /var/log /var/www/vhosts/system/elmercadodeorigen.com/logs -xdev -type f -size +100M -printf '%s %TY-%Tm-%TdT%TH:%TM:%TS %p\n' 2>/dev/null | sort -nr | head -n 80")."\n";

echo "=== DOMAIN LOG DETAILS ===\n";
$logs=preg_split('/\R+/',cmd("find /var/www/vhosts/system/elmercadodeorigen.com/logs -maxdepth 2 -type f -size +50M -print 2>/dev/null | head -n 30"),-1,PREG_SPLIT_NO_EMPTY);
foreach($logs as $f){$q=escapeshellarg($f);echo "-- $f --\n";echo 'SIZE '.cmd("stat -c '%s bytes' $q")."\n";echo 'FIRST '.cmd("head -n 1 $q | cut -c1-220")."\n";echo 'LAST '.cmd("tail -n 1 $q | cut -c1-220")."\n";}

echo "=== LOGROTATE DOMAIN CONFIG ===\n";
echo cmd("grep -RHi -B3 -A15 'elmercadodeorigen.com' /etc/logrotate.d /usr/local/psa/etc/logrotate.d /var/www/vhosts/system/elmercadodeorigen.com/conf 2>/dev/null | head -n 240")."\n";

echo "=== PROD PHP-FPM POOL ===\n";
echo cmd("cat /opt/plesk/php/8.2/etc/php-fpm.d/elmercadodeorigen.com.conf 2>/dev/null | grep -E '^(pm|php_value\\[memory_limit\\]|php_value\\[max_execution_time\\])' ")."\n";
echo "=== PHP-FPM PROCESSES ===\n";
echo cmd("ps -eo pid,rss,pcpu,etime,args --sort=-rss | grep '[p]hp-fpm: pool elmercadodeorigen.com' | head -n 30")."\n";
echo "=== TOP MEMORY PROCESSES ===\n";
echo cmd("ps -eo pid,rss,pcpu,etime,comm,args --sort=-rss | head -n 25")."\n";

echo "=== EMDO CRON ===\n";
echo cmd("grep -RHiE 'elmercadodeorigen|wp-cron|mdo_supplier_sync' /etc/cron.d /var/spool/cron/crontabs 2>/dev/null | head -n 160")."\n";

global $wpdb;$at=$wpdb->prefix.'actionscheduler_actions';$gt=$wpdb->prefix.'actionscheduler_groups';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$at))===$at){
 echo "=== EMDO ACTION SCHEDULER CURRENT ===\n";
 $rows=$wpdb->get_results("SELECT a.hook,a.status,a.scheduled_date_gmt,a.last_attempt_gmt,a.attempts,g.slug AS grp FROM $at a LEFT JOIN $gt g ON g.group_id=a.group_id WHERE a.hook LIKE 'mdo_supplier_sync_%' ORDER BY a.action_id DESC LIMIT 50",ARRAY_A);
 echo json_encode($rows,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
}
