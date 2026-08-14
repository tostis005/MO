<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_cmd($cmd) {
    $out = shell_exec($cmd . ' 2>&1');
    return is_string($out) ? trim($out) : '';
}

$prod = rtrim(ABSPATH, '/');
$base = dirname($prod);
$host = basename($base);

echo "SLOWDOWN_DIAG_START\n";
echo 'SERVER_TIME ' . emdo_cmd('date -Is') . "\n";
echo 'SERVER_TZ ' . emdo_cmd('date +%Z') . "\n";
echo 'UPTIME ' . emdo_cmd('uptime') . "\n";
echo 'LOADAVG ' . emdo_cmd('cat /proc/loadavg') . "\n";
echo 'CPU_COUNT ' . emdo_cmd('getconf _NPROCESSORS_ONLN') . "\n";
echo 'MEMINFO ' . preg_replace('/\s+/', ' ', emdo_cmd("grep -E 'MemTotal|MemAvailable|SwapTotal|SwapFree' /proc/meminfo")) . "\n";

echo "=== PROD DOMAIN CONFIG MATCHES ===\n";
$matches = emdo_cmd("grep -RIl --include='*.conf' --include='*.inc' 'elmercadodeorigen\\.com' /var/www/vhosts/system /etc/nginx/plesk.conf.d 2>/dev/null | head -n 60");
echo $matches . "\n";

echo "=== SYSTEM VHOST DIRS CONTAINING ELMERCADO ===\n";
echo emdo_cmd("find /var/www/vhosts/system -maxdepth 2 -type d -iname '*elmercado*' -print 2>/dev/null | head -n 60") . "\n";

echo "=== LOG DIR INVENTORY, RECENT/NONEMPTY ===\n";
$dirs = array();
foreach (glob('/var/www/vhosts/system/*/logs', GLOB_ONLYDIR) ?: array() as $d) {
    $parent = basename(dirname($d));
    if (stripos($parent, 'elmercado') !== false || $parent === $host) $dirs[] = $d;
}
$dirs[] = $base . '/logs';
$dirs = array_values(array_unique($dirs));
foreach ($dirs as $d) {
    if (!is_dir($d)) continue;
    echo "LOGDIR $d\n";
    foreach (glob($d . '/*') ?: array() as $f) {
        if (!is_file($f) || !is_readable($f)) continue;
        $sz = filesize($f); $mt = filemtime($f);
        if ($sz > 0 || $mt >= time()-86400) {
            echo 'LOGFILE ' . basename($f) . ' size=' . $sz . ' mtime=' . gmdate('c',$mt) . "\n";
        }
    }
}

echo "=== SAR AVAILABILITY ===\n";
echo 'SAR ' . emdo_cmd('command -v sar || true') . "\n";
if (emdo_cmd('command -v sar || true') !== '') {
    echo "--- SAR CPU 08:00-08:40 UTC-ish server time ---\n";
    echo emdo_cmd("LC_ALL=C sar -u -s 08:00:00 -e 08:40:00") . "\n";
    echo "--- SAR QUEUE 08:00-08:40 ---\n";
    echo emdo_cmd("LC_ALL=C sar -q -s 08:00:00 -e 08:40:00") . "\n";
    echo "--- SAR MEMORY 08:00-08:40 ---\n";
    echo emdo_cmd("LC_ALL=C sar -r -s 08:00:00 -e 08:40:00") . "\n";
}

echo "=== ATOP AVAILABILITY ===\n";
echo 'ATOP ' . emdo_cmd('command -v atop || true') . "\n";
echo 'ATOP_LOGS ' . emdo_cmd("ls -1 /var/log/atop 2>/dev/null | tail -n 10") . "\n";

echo "=== JOURNAL RESOURCE/WEB ERRORS 08:10-08:35 ===\n";
$journal = emdo_cmd("journalctl --since '2026-08-14 08:10:00' --until '2026-08-14 08:35:00' --no-pager 2>/dev/null | grep -Ei 'oom|out of memory|killed process|php|fpm|mysql|mariadb|nginx|apache|httpd|cpu|load' | tail -n 160");
echo $journal . "\n";

echo "=== CURRENT TOP PROCESSES ===\n";
echo emdo_cmd("ps -eo pid,user,comm,%cpu,%mem,etime --sort=-%cpu | head -n 25") . "\n";

echo "=== AGENT360 FILE LOCATIONS ===\n";
echo emdo_cmd("find /etc /var/lib /var/log /opt -maxdepth 3 \( -iname '*agent360*' -o -iname '*360monitor*' \) -print 2>/dev/null | head -n 80") . "\n";

echo "SLOWDOWN_DIAG_END\n";
