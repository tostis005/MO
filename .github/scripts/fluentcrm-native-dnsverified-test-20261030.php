<?php
/**
 * Native FluentCRM test email to one authorized address only.
 * Does not send or schedule the campaign.
 */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) exit(2);
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))exit(3);
$campaign_class='\\FluentCrm\\App\\Models\\Campaign';
$controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
if(!class_exists($campaign_class)||!class_exists($controller_class)) exit(4);
$campaign=$campaign_class::find(12);
$address='jose.fraga@gmail.com';
$sender='hola@elmercadodeorigen.com';
if(!$campaign || $campaign->status!=='draft'
 ||$campaign->email_subject!=='Aceite recién molturado: descubre el Lechín y la Manzanilla sin filtrar'
 || strpos((string)$campaign->email_body,'Buenos días,')===false
 || strpos((string)$campaign->email_body,'Buenas noches,')!==false
 || strpos((string)$campaign->email_body,'SINFILTRO')===false
 || strpos((string)$campaign->email_body,'https://www.elmercadodeorigen.com/tienda/1957/')===false
 || ($campaign->settings['mailer_settings']['from_email']??'')!==$sender
 || !function_exists('emdo_authenticated_envelope_from')
 || !has_action('phpmailer_init','emdo_authenticated_envelope_from')) {
 fwrite(STDERR,"FLUENT_NATIVE_TEST_ABORT_SAFETY_CHECK\n");exit(5);
}
$statekey='_emdo_fluentcrm_native_dnsverified_test_20261030';
if(get_option($statekey,false)){
 echo "FLUENT_NATIVE_TEST_ALREADY_RECORDED\n";
 echo "FLUENT_NATIVE_TEST_OPERATION_COMPLETE\n";exit(0);
}
$controller=new $controller_class();
$prop=new ReflectionProperty($controller,'request');
$prop->setAccessible(true);
if($prop->getType()){fwrite(STDERR,"FLUENT_NATIVE_TEST_ABORT_TYPED_REQUEST\n");exit(6);}
$request=new class($address) {
 private $params;
 public function __construct($addr){$this->params=['test_campaign'=>'no','campaign_id'=>12,'email'=>$addr];}
 public function get($name,$default=null){return $this->params[$name]??$default;}
};
$prop->setValue($controller,$request);
try{$result=$controller->sendTestEmail();}
catch(Throwable $e){fwrite(STDERR,"FLUENT_NATIVE_TEST_ABORT ".get_class($e)." ".substr($e->getMessage(),0,140)."\n");exit(7);}
if(!is_array($result)||($result['result']??null)!==true){
 fwrite(STDERR,"FLUENT_NATIVE_TEST_FAILED ".gettype($result)."\n");exit(8);
}
update_option($statekey,['recipient'=>$address,'campaign_id'=>12,'timestamp'=>current_time('mysql'),'transport_accepted'=>true],false);
$verify=$campaign_class::find(12);
if(!$verify||$verify->status!=='draft')exit(9);
echo 'FLUENT_NATIVE_TEST_SUBMITTED='.wp_json_encode(['recipient'=>$address,'campaign_id'=>12,'sender'=>$sender,'method'=>'FluentCRM CampaignController::sendTestEmail','wp_mail_accepted'=>true,'campaign_status'=>$verify->status],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "FLUENT_NATIVE_TEST_OPERATION_COMPLETE\n";
