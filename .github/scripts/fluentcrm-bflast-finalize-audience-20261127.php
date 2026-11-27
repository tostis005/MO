<?php
/**
 * Copy the exact send audience settings from the latest archived Black Friday
 * campaign (#14) and the verified corporate from/reply-to of campaign #13.
 * Draft only. NEVER sends or schedules.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "BFLAST_READY_WP_CLI_REQUIRED\n"); exit(2);
}
$host = strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
    fwrite(STDERR,"BFLAST_READY_WRONG_SITE\n");exit(3);
}
$cls='\FluentCrm\App\Models\Campaign';
if(!class_exists($cls)){fwrite(STDERR,"BFLAST_READY_MODEL_MISSING\n");exit(4);}
$target=$cls::find(16);
$latest=$cls::find(14);
$sender_ref=$cls::find(13);
$checks = [
  'target_exists' => (bool)$target,
  'latest_exists' => (bool)$latest,
  'sender_ref_exists' => (bool)$sender_ref,
  'target_draft' => $target && (string)$target->status === 'draft',
  'source_archived' => $latest && (string)$latest->status === 'archived',
  'sender_archived' => $sender_ref && (string)$sender_ref->status === 'archived',
  'subject_match' => $target && (string)$target->email_subject === 'Últimos días de Black Friday: hasta el domingo 29 de noviembre',
  'grill_image_present' => $target && str_contains((string)$target->email_body,'tolecarnes-grill.jpg'),
  'final_reminder_present' => $target && str_contains((string)$target->email_body,'Ver todas las ofertas del Black Friday'),
  'telegram_present' => $target && str_contains((string)$target->email_body,'https://t.me/elmercadodeorigen'),
  'whatsapp_present' => $target && str_contains((string)$target->email_body,'https://whatsapp.com/channel/0029VbE9YBkIN9iuvrocPb3x'),
  'headline_present' => $target && str_contains((string)$target->email_body,'Este domingo se acaban<br>las ofertas.'),
  'unsubscribe_present' => $target && str_contains((string)$target->email_body,'##crm.unsubscribe_url##'),
  'reference_montjam' => $latest && str_contains((string)$latest->title,'Montjam'),
  'reference_hidalgo' => $sender_ref && str_contains((string)$sender_ref->title,'Hidalgo de la Jara'),
];
$failed = array_keys(array_filter($checks,static function($ok){return !$ok;}));
if($failed){
    fwrite(STDERR,"BFLAST_READY_SOURCE_OR_TARGET_MISMATCH checks=".implode(',',$failed)."\n");exit(5);
}
$body_hash=hash('sha256',(string)$target->email_body);
$source=is_array($latest->settings)?$latest->settings:[];
$sender=is_array($sender_ref->settings)?$sender_ref->settings:[];
if(!isset($source['subscribers']) || !is_array($source['subscribers']) ||
   !array_key_exists('sending_type',$source) ||
   !array_key_exists('sending_filter',$source) ||
   !isset($sender['mailer_settings']) ||
   ($sender['mailer_settings']['from_email']??'')!=='hola@elmercadodeorigen.com' ||
   ($sender['mailer_settings']['from_name']??'')!=='El Mercado de Origen' ||
   ($sender['mailer_settings']['reply_to_email']??'')!=='hola@elmercadodeorigen.com') {
    fwrite(STDERR,"BFLAST_READY_MISSING_REFERENCE_SEND_SETTINGS\n");exit(6);
}
$fields=['subscribers','excludedSubscribers','sending_filter','dynamic_segment',
    'advanced_filters','sending_type','click_tracker','open_tracker'];
$settings=is_array($target->settings)?$target->settings:[];
foreach($fields as $key) {
    if(!array_key_exists($key,$source)){
        fwrite(STDERR,"BFLAST_READY_MISSING_SOURCE_KEY ".$key."\n");exit(7);
    }
    $settings[$key]=$source[$key];
}
$settings['mailer_settings']=array_merge(
    is_array($settings['mailer_settings']??null)?$settings['mailer_settings']:[],
    $sender['mailer_settings']
);
$target->settings=$settings;
$target->save();
$verified=$cls::find(16);
$actual=is_array($verified->settings)?$verified->settings:[];
if(!$verified || (string)$verified->status!=='draft' ||
   hash('sha256',(string)$verified->email_body)!==$body_hash ||
   (string)$verified->email_subject!=='Últimos días de Black Friday: hasta el domingo 29 de noviembre' ||
   !str_contains((string)$verified->email_body,'tolecarnes-grill.jpg') ||
   !str_contains((string)$verified->email_body,'Ver todas las ofertas del Black Friday') ||
   ($actual['mailer_settings']['from_email']??'')!=='hola@elmercadodeorigen.com' ||
   ($actual['mailer_settings']['from_name']??'')!=='El Mercado de Origen') {
    fwrite(STDERR,"BFLAST_READY_SAVE_VERIFICATION_FAILED\n");exit(8);
}
foreach($fields as $key){
    if(!array_key_exists($key,$actual) || $actual[$key]!==$source[$key]){
        fwrite(STDERR,"BFLAST_READY_RECIPIENT_FIELD_MISMATCH ".$key."\n");exit(9);
    }
}
if (($actual['mailer_settings']['reply_to_email']??'')!=='hola@elmercadodeorigen.com') { fwrite(STDERR,"BFLAST_READY_REPLY_MISMATCH\n"); exit(10); }
echo "BFLAST_READY_VERIFIED id=16 status=draft sender=hola@elmercadodeorigen.com audience_source_campaign=14 sender_source_campaign=13 setting_keys=".implode(',',array_keys($actual))."\n";
