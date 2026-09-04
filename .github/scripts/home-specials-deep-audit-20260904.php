<?php
if (!defined('ABSPATH')) exit(1);

function out_file($label, $path, $start = 1, $end = 99999) {
    echo "===== {$label}: {$path} =====\n";
    if (!is_file($path)) { echo "MISSING\n"; return; }
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if (!$lines) { echo "EMPTY\n"; return; }
    $max = min(count($lines), $end);
    for ($i=max(1,$start); $i <= $max; $i++) echo $i . ': ' . $lines[$i-1] . "\n";
}

$child = get_stylesheet_directory();
out_file('HOME_CSS', $child . '/assets/css/home-emdo.css', 1, 520);
out_file('HOME_TEMPLATE', $child . '/home-emdo.php', 1, 650);
out_file('CHILD_FUNCTIONS', $child . '/functions.php', 1, 650);

$plugin = WP_PLUGIN_DIR . '/emdo-especiales';
if (is_dir($plugin)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!$f->isFile() || strtolower($f->getExtension()) !== 'php' || $f->getSize() > 400000) continue;
        $txt = @file_get_contents($f->getPathname());
        if ($txt === false) continue;
        if (!preg_match('/emdo_special|_emdo_|special|especial|home|shortcode|query/i', $txt)) continue;
        out_file('SPECIALS_PLUGIN', $f->getPathname(), 1, 1200);
    }
}

// dump ordered specials with dates/menu order and all non-sensitive _emdo meta
$posts = get_posts([
  'post_type'=>'emdo_special','post_status'=>'any','numberposts'=>100,
  'orderby'=>['menu_order'=>'ASC','date'=>'DESC'], 'suppress_filters'=>false,
]);
foreach ($posts as $p) {
    $meta=[];
    foreach (get_post_meta($p->ID) as $k=>$vals) {
        if (strpos($k,'_emdo_')!==0) continue;
        if (preg_match('/pass|token|secret|key|auth|salt/i',$k)) continue;
        $meta[$k]=array_map('maybe_unserialize',$vals);
    }
    echo 'SPECIAL_ROW: '.wp_json_encode([
      'id'=>$p->ID,'title'=>$p->post_title,'status'=>$p->post_status,
      'date'=>$p->post_date,'menu_order'=>$p->menu_order,'meta'=>$meta
    ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
