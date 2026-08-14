<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
function top_assoc($rows,$key){$out=[];foreach($rows as $r){$k=(string)($r[$key]??'');$out[$k]=($out[$k]??0)+1;}arsort($out);return array_slice($out,0,30,true);}

echo "=== PHP82 FPM LOG CANDIDATES ===\n";
echo cmd("find /var/log /opt/plesk/php/8.2 -maxdepth 4 -type f \( -iname '*fpm*log*' -o -iname '*php*error*' -o -iname 'error.log' \) -printf '%TY-%Tm-%TdT%TH:%TM:%TS %s %p\n' 2>/dev/null | tail -n 120")."\n";
echo "=== FPM SATURATION WINDOW ===\n";
$files=preg_split('/\R+/',cmd("find /var/log /var/www/vhosts/system/elmercadodeorigen.com -maxdepth 5 -type f \( -iname '*fpm*log*' -o -iname '*php*error*' -o -iname 'error_log' \) -size +0c -print 2>/dev/null | head -n 80"),-1,PREG_SPLIT_NO_EMPTY);
foreach($files as $f){if(!is_readable($f))continue;$q=escapeshellarg($f);$out=cmd("grep -aEi '14-Aug-2026 07:(2[5-9]|[3-5][0-9])|14-Aug-2026 08:0[0-3]|Aug 14 07:(2[5-9]|[3-5][0-9])|Aug 14 08:0[0-3]|2026/08/14 07:(2[5-9]|[3-5][0-9])|2026/08/14 08:0[0-3]|2026-08-14 07:(2[5-9]|[3-5][0-9])|2026-08-14 08:0[0-3]' $q 2>/dev/null | grep -Ei 'max_children|seems busy|pool|fpm|child|slow|timeout|fatal|memory|terminated|segfault' | tail -n 100");if($out!=='')echo "-- $f --\n$out\n";}

echo "=== CURRENT PROD FPM POOL ===\n";
echo cmd("cat /opt/plesk/php/8.2/etc/php-fpm.d/elmercadodeorigen.com.conf 2>/dev/null | grep -Ev '^[;#]|^$' | sed -E 's/(listen = ).*/\\1<socket>/' | head -n 120")."\n";

global $wpdb;$prefix=$wpdb->prefix;
echo "=== PRODUCTION ACTION SCHEDULER ===\n";
$at=$prefix.'actionscheduler_actions';$lt=$prefix.'actionscheduler_logs';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$at))===$at){
  $rows=$wpdb->get_results("SELECT action_id,hook,status,scheduled_date_gmt,scheduled_date_local,last_attempt_gmt,last_attempt_local,attempts,group_id,args FROM $at WHERE (scheduled_date_gmt BETWEEN '2026-08-14 07:20:00' AND '2026-08-14 08:10:00') OR (last_attempt_gmt BETWEEN '2026-08-14 07:20:00' AND '2026-08-14 08:10:00') ORDER BY COALESCE(last_attempt_gmt,scheduled_date_gmt),action_id LIMIT 1000",ARRAY_A);
  echo 'PROD_AS_COUNT '.count($rows)."\n";echo 'PROD_AS_HOOKS '.json_encode(top_assoc($rows,'hook'),JSON_UNESCAPED_SLASHES)."\n";echo 'PROD_AS_STATUS '.json_encode(top_assoc($rows,'status'),JSON_UNESCAPED_SLASHES)."\n";
  $safe=[];foreach($rows as $r){$safe[]=['id'=>$r['action_id'],'hook'=>$r['hook'],'status'=>$r['status'],'scheduled'=>$r['scheduled_date_gmt'],'last_attempt'=>$r['last_attempt_gmt'],'attempts'=>$r['attempts'],'group'=>$r['group_id']];}echo 'PROD_AS_ROWS '.json_encode(array_slice($safe,0,250),JSON_UNESCAPED_SLASHES)."\n";
  if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$lt))===$lt){$logs=$wpdb->get_results("SELECT l.action_id,l.message,l.log_date_gmt,a.hook FROM $lt l LEFT JOIN $at a ON a.action_id=l.action_id WHERE l.log_date_gmt BETWEEN '2026-08-14 07:20:00' AND '2026-08-14 08:10:00' ORDER BY l.log_date_gmt,l.log_id LIMIT 1200",ARRAY_A);echo 'PROD_AS_LOG_COUNT '.count($logs)."\n";$hooks=[];foreach($logs as $r){$hooks[]=['hook'=>$r['hook'],'time'=>$r['log_date_gmt'],'message'=>preg_replace('/\s+/',' ',(string)$r['message'])];}echo 'PROD_AS_LOGS '.json_encode(array_slice($hooks,0,400),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";}
}

echo "=== STAGING ACTION SCHEDULER VIA WPCLI ===\n";
$dev=cmd("find /var/www/vhosts -maxdepth 6 -type f -path '*/dev.elmercadodeorigen.com/httpdocs/wp-config.php' -printf '%h\n' 2>/dev/null | head -n1");echo 'DEV_PATH '.$dev."\n";
if($dev!==''){
  $php='/opt/plesk/php/8.3/bin/php';if(!is_executable($php))$php='/opt/plesk/php/8.2/bin/php';$wp=cmd('command -v wp');
  $sql="SELECT hook,status,COUNT(*) c,MIN(COALESCE(last_attempt_gmt,scheduled_date_gmt)) first_t,MAX(COALESCE(last_attempt_gmt,scheduled_date_gmt)) last_t FROM wp_actionscheduler_actions WHERE (scheduled_date_gmt BETWEEN '2026-08-14 07:20:00' AND '2026-08-14 08:10:00') OR (last_attempt_gmt BETWEEN '2026-08-14 07:20:00' AND '2026-08-14 08:10:00') GROUP BY hook,status ORDER BY c DESC LIMIT 100";
  echo cmd(escapeshellarg($php).' '.escapeshellarg($wp).' db query '.escapeshellarg($sql).' --skip-column-names --path='.escapeshellarg($dev).' --allow-root 2>/dev/null')."\n";
}

echo "=== CRON CONFIG FOR EMDO ===\n";
echo cmd("grep -RHiE 'elmercadodeorigen|wp-cron|action.?scheduler' /etc/cron.d /var/spool/cron/crontabs 2>/dev/null | head -n 160")."\n";
