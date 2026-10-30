<?php
/**
 * Audit / optionally unsubscribe verified permanent hard bounces in shop Maildir.
 * The manifest never leaves the production server; no addresses logged.
 *
 * Apply requires EMDO_BOUNCE_APPLY=1 and a fresh, validated manifest.
 * Retains contact/order data: only changes FluentCRM status to unsubscribed.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$file=(string)getenv('EMDO_BOUNCE_MANIFEST');
$apply=(string)getenv('EMDO_BOUNCE_APPLY')==='1';
if(!preg_match('~^/tmp/emdo-bounces-[a-zA-Z0-9_-]+\.json$~',$file)||!is_readable($file)){
 fwrite(STDERR,"BOUNCE_SYNC_ABORT_MANIFEST_PATH\n");exit(4);
}
$manifest=json_decode(file_get_contents($file),true);
if(!is_array($manifest)||count($manifest)>30000){fwrite(STDERR,"BOUNCE_SYNC_ABORT_MANIFEST_INVALID\n");exit(5);}
global $wpdb;
$table=$wpdb->prefix.'fc_subscribers';
$mail_table=$wpdb->prefix.'fc_campaign_emails';
if(!preg_match('/^[a-zA-Z0-9_]+$/',$table.$mail_table))exit(6);
$subscriber_cls='\\FluentCrm\\App\\Models\\Subscriber';
if(!class_exists($subscriber_cls)){fwrite(STDERR,"BOUNCE_SYNC_ABORT_MODEL_UNAVAILABLE\n");exit(7);}
$now=time();
$counts=[];
$code_counts=[];
$age_buckets=[];
$eligible=[];
$skipped=[];
$add=function($name)use(&$counts){$counts[$name]=($counts[$name]??0)+1;};
foreach($manifest as $line){
 $add('manifest_addresses');
 if(!is_array($line)){$add('bad_row');continue;}
 $email=strtolower(trim((string)($line['email']??'')));
 $codes=(array)($line['statuses']??[]);
 $when=(int)($line['last_epoch']??0);
 if(!is_email($email)||!$codes){$add('bad_address_or_codes');continue;}
 $sub=$wpdb->get_row($wpdb->prepare("SELECT id,email,status,created_at,last_activity,updated_at FROM ".$table." WHERE email=%s ORDER BY id DESC LIMIT 1",$email),ARRAY_A);
 if(!$sub){$add('not_in_fluentcrm');continue;}
 $add('in_fluentcrm');
 if((string)$sub['status']!=='subscribed'){$add('already_not_subscribed');continue;}
 $add('active_in_fluentcrm');
 foreach($codes as $code)$code_counts[(string)$code]=($code_counts[(string)$code]??0)+1;
 if(!array_intersect($codes,['5.1.1','5.1.10','5.2.1'])){$add('ambiguous_hard_code_manual_review');continue;}
 if($when<1262304000 || $when>$now+86400){$add('missing_or_bad_bounce_date_manual_review');continue;}
 $days=(int)floor(($now-$when)/86400);
 $bucket=($days<=30?'0_30':($days<=180?'31_180':($days<=365?'181_365':'over365')));
 $age_buckets[$bucket]=($age_buckets[$bucket]??0)+1;
 $created=strtotime((string)($sub['created_at']??''))?:0;
 if($created>$when+86400){$add('contact_created_after_bounce');continue;}
 $activity=strtotime((string)($sub['last_activity']??''))?:0;
 if($activity>$when+86400){$add('contact_activity_after_bounce');continue;}
 $fresh_click=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM ".$mail_table." WHERE subscriber_id=%d AND (is_open>0 OR click_counter>0) AND updated_at>%s",(int)$sub['id'],gmdate('Y-m-d H:i:s',$when+86400)));
 if((int)$fresh_click>0){$add('campaign_engagement_after_bounce');continue;}
 // A customer's successful order AFTER the bounce is contradictory evidence:
 // do not remove their marketing consent without a newer fresh hard bounce.
 $order_last=0;
 $hpos_exists=$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=%s',$wpdb->prefix.'wc_orders'));
 if($hpos_exists){
   $orders_table=$wpdb->prefix.'wc_orders';
   $orderdate=$wpdb->get_var($wpdb->prepare(
     "SELECT MAX(date_created_gmt) FROM ".$orders_table." WHERE billing_email=%s AND type='shop_order' AND status IN ('wc-processing','wc-completed')",$email));
   $order_last=strtotime((string)$orderdate)?:0;
 }
 if($order_last>$when+86400){$add('customer_order_after_bounce');continue;}
 $eligible[]=['id'=>(int)$sub['id'],'email'=>$email,'status'=>(string)$sub['status'],'bounce_epoch'=>$when,'codes'=>array_values($codes)];
 $add('eligible_confirmed_hard_bounce');
}
ksort($counts);ksort($code_counts);ksort($age_buckets);
echo 'BOUNCE_CRM_RECON_COUNTS='.wp_json_encode($counts)."\n";
echo 'BOUNCE_CRM_RECON_ACTIVE_CODES='.wp_json_encode($code_counts)."\n";
echo 'BOUNCE_CRM_RECON_AGE_BUCKETS='.wp_json_encode($age_buckets)."\n";
echo 'BOUNCE_CRM_ELIGIBLE_COUNT='.count($eligible)."\n";
if(!$apply){echo "BOUNCE_CRM_READ_ONLY_COMPLETE\n";exit(0);}
if(count($eligible)>500){fwrite(STDERR,"BOUNCE_SYNC_ABORT_TOO_MANY_CONTACTS\n");exit(8);}
$option='_emdo_hard_bounce_unsubscribed_20261030';
$previous=get_option($option,[]);
if(!is_array($previous))$previous=[];
$changed=0;$already=0;
foreach($eligible as $entry){
 $id=(int)$entry['id'];
 $sub=$subscriber_cls::find($id);
 if(!$sub||strtolower((string)$sub->email)!==$entry['email']){
   fwrite(STDERR,"BOUNCE_SYNC_ABORT_SUBSCRIBER_CHANGED\n");exit(9);
 }
 if($sub->status==='unsubscribed'){$already++;continue;}
 if($sub->status!=='subscribed'){fwrite(STDERR,"BOUNCE_SYNC_ABORT_STATUS_CHANGED\n");exit(10);}
 $sub->status='unsubscribed';
 $sub->save();
 $check=$subscriber_cls::find($id);
 if(!$check||$check->status!=='unsubscribed'){fwrite(STDERR,"BOUNCE_SYNC_ABORT_VERIFICATION_FAILED\n");exit(11);}
 $previous[]=['subscriber_id'=>$id,'old_status'=>'subscribed','new_status'=>'unsubscribed','bounce_epoch'=>$entry['bounce_epoch'],'codes'=>$entry['codes'],'when'=>current_time('mysql')];
 update_option($option,$previous,false);
 $changed++;
}
echo 'BOUNCE_CRM_UNSUBSCRIBED='.wp_json_encode(['changed'=>$changed,'already_unsubscribed_during_run'=>$already,'audit_log_records'=>count($previous)])."\n";
echo "BOUNCE_CRM_APPLY_COMPLETE\n";
