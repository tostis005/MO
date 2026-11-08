<?php
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
if(!in_array(strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST)),['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$cls='\\FluentCrm\\App\\Models\\Campaign';$subcls='\\FluentCrm\\App\\Models\\Subscriber';
$c=$cls::orderBy('id','DESC')->first();
if(!$c||(int)$c->id!==12||(string)$c->email_subject!=='Aceite recién molturado: descubre el Lechín y la Manzanilla sin filtrar')exit(4);
$f=getenv('EMDO_BOUNCE_MANIFEST');
if(!preg_match('~^/tmp/emdo-bounces-[a-zA-Z0-9_-]+\\.json$~',$f)||!is_readable($f)||time()-filemtime($f)>900)exit(5);
$m=json_decode(file_get_contents($f),true);if(!is_array($m)||count($m)>10000)exit(6);
global $wpdb;$t=$wpdb->prefix.'fc_campaign_emails';$all=[];$counts=[];$eligible=[];
foreach($m as $r){
 if(!is_email($r['email'])||$r['action']!=='failed')continue;
 $key=strtolower($r['email']);
 if(!isset($all[$key])||$r['epoch']>$all[$key]['epoch'])$all[$key]=$r;
}
$add=function($key)use(&$counts){$counts[$key]=($counts[$key]??0)+1;};
foreach($all as $email=>$r){
 $add('unique_failed_recipients');
 $row=$wpdb->get_row($wpdb->prepare("SELECT subscriber_id,created_at FROM ".$t." WHERE campaign_id=12 AND email_address=%s LIMIT 1",$email));
 if(!$row){$add('not_a_campaign_recipient');continue;}
 $sub=$subcls::find((int)$row->subscriber_id);
 if(!$sub||strtolower((string)$sub->email)!==$email){$add('contact_mismatch');continue;}
 if($r['epoch']<strtotime($row->created_at)-7200){$add('bounce_before_campaign');continue;}
 if($sub->status!=='subscribed'){$add('already_excluded');continue;}
 $eligible[]=['id'=>(int)$sub->id,'email'=>$email,'code'=>$r['status'],'epoch'=>$r['epoch']];
 $add('eligible');
}
echo 'LATEST_CRM_COUNTS='.wp_json_encode($counts)."\n";
echo 'LATEST_CRM_ELIGIBLE_TOTAL='.count($eligible)."\n";
if(getenv('EMDO_BOUNCE_APPLY')!=='1'){echo "BOUNCE_CRM_READ_ONLY_COMPLETE\n";exit(0);}
if(count($eligible)>500)exit(7);
$opt='_emdo_campaign12_bounce_unsubscribes_20261108';$audit=get_option($opt,[]);if(!is_array($audit))exit(8);
$changed=0;
foreach($eligible as $e){
 $sub=$subcls::find($e['id']);
 if(!$sub||strtolower((string)$sub->email)!==$e['email']||$sub->status!=='subscribed')exit(9);
 $entry=['subscriber_id'=>$e['id'],'campaign_id'=>12,'old_status'=>'subscribed','new_status'=>'unsubscribed','dsn_code'=>$e['code'],'bounce_epoch'=>$e['epoch'],'when'=>current_time('mysql')];
 $sub->status='unsubscribed';$sub->save();
 if($subcls::find($e['id'])->status!=='unsubscribed')exit(10);
 $audit[]=$entry;update_option($opt,$audit,false);$changed++;
}
$remaining=0;
foreach($all as $email=>$r){
 $id=$wpdb->get_var($wpdb->prepare("SELECT subscriber_id FROM ".$t." WHERE campaign_id=12 AND email_address=%s LIMIT 1",$email));
 if($id){$s=$subcls::find((int)$id);if($s&&$s->status==='subscribed')$remaining++;}
}
echo 'LATEST_CRM_RESULT='.wp_json_encode(['changed'=>$changed,'remaining_subscribed'=>$remaining,'audit_records'=>count($audit)])."\n";
echo "BOUNCE_CRM_APPLY_COMPLETE\n";
