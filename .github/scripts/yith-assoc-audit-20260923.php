<?php
if (!defined('ABSPATH')) exit(2);
global $wpdb;
$t=$wpdb->prefix.'yith_wapo_blocks_assoc';
$out=['columns'=>$wpdb->get_results("SHOW COLUMNS FROM `$t`",ARRAY_A),'rows'=>$wpdb->get_results("SELECT * FROM `$t` ORDER BY id DESC LIMIT 30",ARRAY_A)];
echo "YITH_ASSOC_AUDIT: ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
