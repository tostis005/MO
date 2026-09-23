<?php
if(!defined('ABSPATH')) exit(2);
global $wpdb;
$t=$wpdb->prefix.'yith_wapo_blocks_assoc';
$rows=$wpdb->get_results("SELECT rule_id, object, type FROM `$t` WHERE rule_id IN (104,105,106,107,108,109,110) ORDER BY rule_id, object",ARRAY_A);
echo "MONTJAM_ASSOC_ROWS: ".wp_json_encode($rows,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
