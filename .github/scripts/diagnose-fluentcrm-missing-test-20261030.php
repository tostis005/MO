<?php
/** Read-only diagnosis of a single FluentCRM test message delivery. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
global $wpdb;
$target='jose.fraga@gmail.com';
$cls='\\FluentCrm\\App\\Models\\Campaign';
$c=class_exists($cls)?$cls::find(12):null;
if(!$c){echo "MAIL_DIAG_ABORT_CAMPAIGN_NOT_FOUND\n";exit(4);}
echo 'MAIL_CAMPAIGN='.wp_json_encode(['id'=>12,'status'=>$c->status,'template'=>$c->design_template,
 'subject'=>$c->email_subject,'preheader'=>$c->email_pre_header,
 'settings_keys'=>is_array($c->settings)?array_keys($c->settings):gettype($c->settings)],
 JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$plugins=get_option('active_plugins',[]);
echo 'ACTIVE_MAIL_PLUGINS='.wp_json_encode(array_values(array_filter((array)$plugins,
 static fn($p)=>preg_match('/fluent|smtp|mail|post|send|ses|spark|brevo|mailgun|wp-mail|mailjet|elastic/i',$p))),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo 'WORDPRESS_MAIL_SETTINGS='.wp_json_encode([
 'admin_email'=>get_option('admin_email'),'phpmailer_init_hooks'=>has_action('phpmailer_init'),
 'pre_wp_mail_hooks'=>has_filter('pre_wp_mail')
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$matches=$wpdb->get_col($wpdb->prepare("SHOW TABLES LIKE %s",$wpdb->esc_like($wpdb->prefix).'%mail%'));
foreach((array)$matches as $table){
 if(!preg_match('/^[a-zA-Z0-9_]+$/',$table))continue;
 $columns=$wpdb->get_col("SHOW COLUMNS FROM ".$table,0);
 echo 'MAIL_LOG_TABLE='.wp_json_encode(['table'=>$table,'columns'=>$columns],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 if(!in_array('id',$columns,true))continue;
 $recipient_fields=array_values(array_filter($columns,static fn($v)=>in_array(strtolower($v),['to','to_email','recipient','recipients','email','email_to','email_address','to_address'],true)));
 $safe_fields=array_values(array_filter($columns,static fn($v)=>in_array(strtolower($v),
 ['id','to','to_email','recipient','recipients','email','email_to','email_address','to_address','subject','email_subject','status','status_code','result','provider','mailer','created_at','updated_at','sent_at','date','response_code','message_id','error_message','error'],true)));
 if(!$recipient_fields||!$safe_fields)continue;
 $conditions=[];
 foreach($recipient_fields as $field)$conditions[]=$field." LIKE %s";
 $where=implode(' OR ',$conditions);
 $sql=$wpdb->prepare("SELECT ".implode(',',$safe_fields)
 ." FROM ".$table." WHERE (".$where.") ORDER BY id DESC LIMIT 12",array_fill(0,count($recipient_fields),'%'.$wpdb->esc_like($target).'%'));
 $rows=$wpdb->get_results($sql,ARRAY_A);
 foreach((array)$rows as $row){
   foreach($row as $k=>$v){if(!is_scalar($v)&&$v!==null)$row[$k]=gettype($v);
    elseif(is_string($v)&&strlen($v)>450)$row[$k]=substr($v,0,450);}
   echo 'MAIL_LOG_MATCH='.wp_json_encode(['table'=>$table,'row'=>$row],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
 }
}
$state=get_option('_emdo_fluentcrm_aove_1957_20261030',[]);
echo 'CAMPAIGN_TEST_STATE='.wp_json_encode(['test_sent_flag'=>(bool)($state['test_sent']??false),
 'test_recipient_match'=>($state['test_recipient']??'')===$target,
 'test_sent_at'=>$state['test_sent_at']??null],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "MAIL_DELIVERY_DIAG_COMPLETE\n";
