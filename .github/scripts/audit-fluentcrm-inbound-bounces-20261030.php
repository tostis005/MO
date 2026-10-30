<?php
/** Read only: FluentCRM campaign bounce stats and contact statuses. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
global $wpdb;
$subs=$wpdb->prefix.'fc_subscribers';
$mail=$wpdb->prefix.'fc_campaign_emails';
function safe_table($t){return preg_match('/^[a-zA-Z0-9_]+$/',$t);}
if(!safe_table($subs)||!safe_table($mail))exit(4);
foreach([$subs,$mail] as $table){
 $col=$wpdb->get_col("SHOW COLUMNS FROM ".$table,0);
 echo 'AUDIT_COLUMNS='.wp_json_encode(['table'=>$table,'columns'=>$col],JSON_UNESCAPED_UNICODE)."\n";
}
$counts=$wpdb->get_results("SELECT status,COUNT(*) AS n FROM ".$subs." GROUP BY status",ARRAY_A);
echo 'FLUENT_CONTACT_COUNTS='.wp_json_encode($counts)."\n";
$statuses=$wpdb->get_results("SELECT status,COUNT(*) AS n FROM ".$mail." GROUP BY status",ARRAY_A);
echo 'FLUENT_CAMPAIGN_EMAIL_COUNTS='.wp_json_encode($statuses)."\n";
$bounce=$wpdb->get_results("SELECT campaign_id,email_type,status,COUNT(*) AS n FROM ".$mail." WHERE status IN ('bounced','failed','rejected','soft_bounced','hard_bounced') GROUP BY campaign_id,email_type,status ORDER BY n DESC LIMIT 40",ARRAY_A);
echo 'FLUENT_CAMPAIGN_BOUNCE_OVERVIEW='.wp_json_encode($bounce)."\n";
echo "MAILBOX_AND_FLUENT_AUDIT_COMPLETE\n";
