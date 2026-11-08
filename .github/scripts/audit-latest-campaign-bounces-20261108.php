<?php
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
global $wpdb;
$cls='\\FluentCrm\\App\\Models\\Campaign';
foreach($cls::orderBy('id','DESC')->limit(8)->get() as $c){
 echo 'CAMPAIGN='.wp_json_encode(['id'=>$c->id,'title'=>$c->title,'subject'=>$c->email_subject,'status'=>$c->status,'created'=>$c->created_at,'updated'=>$c->updated_at])."\n";
}
$t=$wpdb->prefix.'fc_campaign_emails';
echo 'MAIL_COLUMNS='.wp_json_encode($wpdb->get_col("SHOW COLUMNS FROM ".$t,0))."\n";
echo 'RECENT_SENDS='.wp_json_encode($wpdb->get_results("SELECT campaign_id,status,COUNT(*) n,MIN(created_at) first_date,MAX(updated_at) last_update FROM ".$t." GROUP BY campaign_id,status ORDER BY campaign_id DESC LIMIT 30",ARRAY_A))."\n";
$f=getenv('EMDO_BOUNCE_MANIFEST');
$m=json_decode(file_get_contents($f),true);
$counts=[];$dates=[];
foreach($m as $r){
 $sub=$wpdb->get_row($wpdb->prepare("SELECT id,status FROM ".$wpdb->prefix."fc_subscribers WHERE email=%s",$r['email']));
 if(!$sub)continue;
 $campaigns=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT campaign_id FROM ".$t." WHERE subscriber_id=%d",$sub->id));
 foreach($campaigns as $cid){
 $key=$cid.':'.$sub->status;
 $counts[$key]=($counts[$key]??0)+1;
 }
 $day=gmdate('Y-m-d',(int)$r['last_epoch']);$dates[$day]=($dates[$day]??0)+1;
}
ksort($dates);echo 'MATCHED_BY_CAMPAIGN_STATUS='.wp_json_encode($counts)."\n";
echo 'BOUNCE_DATES='.wp_json_encode($dates)."\n";
echo "BOUNCE_CRM_READ_ONLY_COMPLETE\n";
