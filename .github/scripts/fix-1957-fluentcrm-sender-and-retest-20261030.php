<?php
/**
 * Re-send exactly one FluentCRM test for campaign 12 after setting authenticated
 * sender domain. No global mail changes, no bulk sends, no scheduled sends.
 */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$campaign_class='\\FluentCrm\\App\\Models\\Campaign';
$controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
$helper_class='\\FluentCrm\\App\\Services\\Helper';
if(!class_exists($campaign_class)||!class_exists($controller_class)||!class_exists($helper_class)){
 fwrite(STDERR,"AOVE_RETEST_ABORT_FLUENT_MISSING\n");exit(4);
}
$c=$campaign_class::find(12);
if(!$c||$c->status!=='draft'||$c->design_template!=='simple'
 ||$c->email_subject!=='Aceite recién molturado: descubre el Lechín y la Manzanilla sin filtrar'
 ||strpos((string)$c->email_body,'SINFILTRO')===false
 ||strpos((string)$c->email_body,'Buenos días,')===false
 ||strpos((string)$c->email_body,'Buenas noches,')!==false
 ||strpos((string)$c->email_body,'https://www.elmercadodeorigen.com/tienda/1957/')===false) {
 fwrite(STDERR,"AOVE_RETEST_ABORT_DRAFT_NOT_EXPECTED\n");exit(5);
}
$recipient='jose.fraga@gmail.com';
$sender='hola@elmercadodeorigen.com';
$settings=$c->settings;
if(!is_array($settings)){fwrite(STDERR,"AOVE_RETEST_ABORT_SETTINGS_TYPE\n");exit(6);}
$settings['mailer_settings']=[
 'is_custom'=>'yes',
 'from_name'=>'El Mercado de Origen',
 'from_email'=>$sender,
 'reply_to_name'=>'El Mercado de Origen',
 'reply_to_email'=>$sender
];
$c->settings=$settings;
$c->save();
$c=$campaign_class::find(12);
if(!$c||$c->status!=='draft'){fwrite(STDERR,"AOVE_RETEST_ABORT_NOT_DRAFT\n");exit(7);}
$actual=$c->settings['mailer_settings']??[];
if(($actual['is_custom']??'')!=='yes'||($actual['from_email']??'')!==$sender){
 fwrite(STDERR,"AOVE_RETEST_ABORT_SENDER_SAVE\n");exit(8);
}
$headers=$helper_class::getMailHeadersFromSettings($actual);
if(($headers['From']??'')!=='El Mercado de Origen <'.$sender.'>'
 ||($headers['Reply-To']??'')!=='El Mercado de Origen <'.$sender.'>'){
 fwrite(STDERR,"AOVE_RETEST_ABORT_HEADER_VERIFY\n");exit(9);
}
echo 'AOVE_RETEST_CAMPAIGN_SENDER_OK='.wp_json_encode([
 'campaign_id'=>12,'status'=>$c->status,'from'=>$headers['From'],
 'reply_to'=>$headers['Reply-To'],'subject'=>$c->email_subject
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
$state_key='_emdo_fluentcrm_aove_1957_20261030_native_test_buenos_dias';
$state=get_option($state_key,[]);
if(!is_array($state))$state=[];
if(!empty($state['sent'])){
 echo "AOVE_RETEST_ALREADY_SUBMITTED\n";
 echo "AOVE_RETEST_OPERATION_COMPLETE\n";
 exit(0);
}
$controller=new $controller_class();
$ref=new ReflectionProperty($controller,'request');
$ref->setAccessible(true);
if($ref->getType()){fwrite(STDERR,"AOVE_RETEST_ABORT_REQUEST_TYPED\n");exit(10);}
$request=new class($recipient){
 private $params;
 public function __construct($recipient){$this->params=[
 'test_campaign'=>'no','campaign_id'=>12,'email'=>$recipient
 ];}
 public function get($key,$default=null){return $this->params[$key]??$default;}
};
$ref->setValue($controller,$request);
if($request->get('email')!==$recipient || (int)$request->get('campaign_id')!==12)exit(11);

if(!function_exists('emdo_authenticated_envelope_from') || !has_action('phpmailer_init','emdo_authenticated_envelope_from')){
 fwrite(STDERR,"AOVE_RETEST_ABORT_AUTHENTICATED_ENVELOPE_NOT_INSTALLED\n");exit(15);
}
echo "AOVE_RETEST_FLUENTCRM_NATIVE_SEND_TEST\n";
try{$result=$controller->sendTestEmail();}
catch(Throwable $e){fwrite(STDERR,'AOVE_RETEST_ERROR '.get_class($e).' '.substr($e->getMessage(),0,200)."\n");exit(12);}
$accepted=is_array($result)?($result['result']??null):null;
if($accepted!==true){fwrite(STDERR,'AOVE_RETEST_NOT_ACCEPTED_TYPE '.gettype($accepted)."\n");exit(13);}
update_option($state_key,['sent'=>true,'recipient'=>$recipient,'sent_at'=>current_time('mysql'),'campaign_id'=>12],false);
$check=$campaign_class::find(12);
if(!$check||$check->status!=='draft'){fwrite(STDERR,"AOVE_RETEST_ABORT_STATUS_AFTER_SEND\n");exit(14);}
echo 'AOVE_RETEST_SUBMITTED='.wp_json_encode(['campaign_id'=>12,'to'=>$recipient,'sender'=>$sender,'wp_mail_accepted'=>true,'status'=>$check->status],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "AOVE_RETEST_OPERATION_COMPLETE\n";
