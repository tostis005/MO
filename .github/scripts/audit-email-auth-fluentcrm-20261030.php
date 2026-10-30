<?php
/* Read-only FluentCRM, WordPress and mail sender audit for production. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$klass='\\FluentCrm\\App\\Models\\Campaign';
if(!class_exists($klass))exit(4);
$c=$klass::find(12);
if(!$c)exit(5);
$settings=$c->settings;
$ms=is_array($settings)?($settings['mailer_settings']??[]):[];
$global=function_exists('fluentcrmGetGlobalSettings')?fluentcrmGetGlobalSettings('email_settings',[]):[];
$clean=function($x){$o=[];foreach(['from_name','from_email','reply_to_name','reply_to_email','is_custom'] as $k)if(isset($x[$k]))$o[$k]=$x[$k];return $o;};
$has_mustuse=function_exists('emdo_authenticated_envelope_from') && has_action('phpmailer_init','emdo_authenticated_envelope_from');
echo 'FLUENT_SENDER_AUDIT='.wp_json_encode([
 'campaign_id'=>12,'status'=>$c->status,'subject'=>$c->email_subject,
 'morning_greeting'=>strpos((string)$c->email_body,'Buenos días,')!==false,
 'promotional_coupon'=>strpos((string)$c->email_body,'SINFILTRO')!==false,
 'footer_settings_present'=>isset($settings['footer_settings']),
 'campaign_mail_settings'=>$clean(is_array($ms)?$ms:[]),
 'global_mail_settings'=>$clean(is_array($global)?$global:[]),
 'mu_envelope_fix_active'=>(bool)$has_mustuse,
 'mail_transport_hooks'=>['phpmailer_init'=>has_action('phpmailer_init'),'pre_wp_mail'=>has_filter('pre_wp_mail')]
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if(!is_array($settings)||($ms['from_email']??'')!=='hola@elmercadodeorigen.com'||$c->status!=='draft')exit(6);
echo "FLUENT_SENDER_AUDIT_COMPLETE\n";
