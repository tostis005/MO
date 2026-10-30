<?php
/**
 * One-off update for FluentCRM AOVE campaign #12.
 * Only greeting in saved draft body changes, never send or schedule.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
 fwrite(STDERR,"GREETING_ABORT_NOT_WPCLI\n"); exit(2);
}
$host=strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)) {
 fwrite(STDERR,"GREETING_ABORT_WRONG_HOST\n"); exit(3);
}
$klass='\\FluentCrm\\App\\Models\\Campaign';
if(!class_exists($klass)){fwrite(STDERR,"GREETING_ABORT_NO_FLUENTCRM\n");exit(4);}
$campaign=$klass::find(12);
$expected_subject='Aceite recién molturado: descubre el Lechín y la Manzanilla sin filtrar';
if(!$campaign||$campaign->status!=='draft'||$campaign->email_subject!==$expected_subject||$campaign->design_template!=='simple') {
 fwrite(STDERR,"GREETING_ABORT_UNEXPECTED_CAMPAIGN\n");exit(5);
}
$original=(string)$campaign->email_body;
$old='Buenas noches,';
$new='Buenos días,';
if(substr_count($original,$old)===0 && substr_count($original,$new)===1) {
 echo "AOVE_GREETING_ALREADY_UPDATED id=12 status=draft\n";
 echo "AOVE_GREETING_OPERATION_COMPLETE\n";exit(0);
}
if(substr_count($original,$old)!==1 || substr_count($original,$new)!==0
 || strpos($original,'SINFILTRO')===false
 || strpos($original,'https://www.elmercadodeorigen.com/tienda/1957/')===false
 || strpos($original,'Lechín')===false || strpos($original,'Manzanilla')===false){
 fwrite(STDERR,"GREETING_ABORT_UNEXPECTED_BODY\n");exit(6);
}
$updated=str_replace($old,$new,$original);
$campaign->email_body=$updated;
$campaign->save();
$fresh=$klass::find(12);
if(!$fresh||(string)$fresh->email_body!==$updated || $fresh->status!=='draft'
 || $fresh->email_subject!==$expected_subject
 || $fresh->design_template!=='simple'){
 fwrite(STDERR,"GREETING_ABORT_VERIFICATION_FAILED\n");exit(7);
}
echo "AOVE_GREETING_UPDATED id=12 old=Buenas_noches new=Buenos_dias status=draft\n";
echo 'AOVE_GREETING_BODY_SHA256='.hash('sha256',(string)$fresh->email_body)."\n";
echo "AOVE_GREETING_OPERATION_COMPLETE\n";
