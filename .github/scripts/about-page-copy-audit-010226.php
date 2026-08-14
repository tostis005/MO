<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function cmd($c){$o=shell_exec($c.' 2>&1');return is_string($o)?trim($o):'';}
$sys='/var/www/vhosts/system/elmercadodeorigen.com';
echo "=== DOMAIN CUSTOM PHP.INI ===\n";
$f=$sys.'/conf/php.ini';
if(is_readable($f)){echo cmd("sed -n '1,260p' ".escapeshellarg($f))."\n";}else{echo "NO_CUSTOM_PHP_INI\n";}
echo "=== PLESK SHOW PHP SETTINGS ===\n";
echo cmd("plesk bin site --show-php-settings elmercadodeorigen.com 2>/dev/null | head -n 260")."\n";
echo "=== PROD LOGROTATE FILE ===\n";
$lr='/usr/local/psa/etc/logrotate.d/elmercadodeorigen.com';
if(is_readable($lr)){echo cmd('cat '.escapeshellarg($lr))."\n";}else{echo "NO_PROD_LOGROTATE_FILE\n";}
echo "=== SITE INFO LOG ROTATION ===\n";
echo cmd("plesk bin site -i elmercadodeorigen.com 2>/dev/null | grep -Ei 'log|rotation|traffic|stat' | head -n 100")."\n";
echo "=== CURRENT USER CRONTAB ===\n";
echo cmd("crontab -l 2>/dev/null | grep -E 'elmercadodeorigen|wp-cron|mdo' || true")."\n";
