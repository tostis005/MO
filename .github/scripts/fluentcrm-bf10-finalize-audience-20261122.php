<?php
/**
 * Copy the exact send audience settings from the latest archived Black Friday
 * campaign (#14) and the verified corporate from/reply-to of campaign #13.
 * Draft only. NEVER sends or schedules.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    fwrite(STDERR, "BF10_READY_WP_CLI_REQUIRED\n"); exit(2);
}
$host = strtolower((string)wp_parse_url(home_url('/'),PHP_URL_HOST));
if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
    fwrite(STDERR,"BF10_READY_WRONG_SITE\n");exit(3);
}
$cls='\FluentCrm\App\Models\Campaign';
if(!class_exists($cls)){fwrite(STDERR,"BF10_READY_MODEL_MISSING\n");exit(4);}
$target=$cls::find(15);
$latest=$cls::find(14);
$sender_ref=$cls::find(13);
if(!$target || !$latest || !$sender_ref ||
    (string)$target->status !== 'draft' ||
    (string)$latest->status !== 'archived' ||
    (string)$sender_ref->status !== 'archived' ||
    (string)$target->email_subject !== '🖤 Black Friday: 10 % en 6 productores + envío gratis con Tolecarnes' ||
    !str_contains((string)$target->email_body,'tolecarnes-grill.jpg') ||
    !str_contains((string)$target->email_body,'Ver todas las ofertas del Black Friday') ||
    !str_contains((string)$latest->title,'Montjam') ||
    !str_contains((string)$sender_ref->title,'Hidalgo de la Jara')) {
    fwrite(STDERR,"BF10_READY_SOURCE_OR_TARGET_MISMATCH\n");exit(5);
}
$source=is_array($latest->settings)?$latest->settings:[];
$sender=is_array($sender_ref->settings)?$sender_ref->settings:[];
if(!isset($source['subscribers']) || !is_array($source['subscribers']) ||
   !array_key_exists('sending_type',$source) ||
   !array_key_exists('sending_filter',$source) ||
   !isset($sender['mailer_settings']) ||
   ($sender['mailer_settings']['from_email']??'')!=='hola@elmercadodeorigen.com' ||
   ($sender['mailer_settings']['from_name']??'')!=='El Mercado de Origen' ||
   ($sender['mailer_settings']['reply_to_email']??'')!=='hola@elmercadodeorigen.com') {
    fwrite(STDERR,"BF10_READY_MISSING_REFERENCE_SEND_SETTINGS\n");exit(6);
}
$fields=['subscribers','excludedSubscribers','sending_filter','dynamic_segment',
    'advanced_filters','sending_type','click_tracker','open_tracker'];
$settings=is_array($target->settings)?$target->settings:[];
foreach($fields as $key) {
    if(!array_key_exists($key,$source)){
        fwrite(STDERR,"BF10_READY_MISSING_SOURCE_KEY ".$key."\n");exit(7);
    }
    $settings[$key]=$source[$key];
}
$settings['mailer_settings']=array_merge(
    is_array($settings['mailer_settings']??null)?$settings['mailer_settings']:[],
    $sender['mailer_settings']
);
$target->settings=$settings;
$target->save();
$verified=$cls::find(15);
$actual=is_array($verified->settings)?$verified->settings:[];
if(!$verified || (string)$verified->status!=='draft' ||
   (string)$verified->email_subject!=='🖤 Black Friday: 10 % en 6 productores + envío gratis con Tolecarnes' ||
   !str_contains((string)$verified->email_body,'tolecarnes-grill.jpg') ||
   !str_contains((string)$verified->email_body,'Ver todas las ofertas del Black Friday') ||
   ($actual['mailer_settings']['from_email']??'')!=='hola@elmercadodeorigen.com' ||
   ($actual['mailer_settings']['from_name']??'')!=='El Mercado de Origen') {
    fwrite(STDERR,"BF10_READY_SAVE_VERIFICATION_FAILED\n");exit(8);
}
foreach($fields as $key){
    if(!array_key_exists($key,$actual) || $actual[$key]!==$source[$key]){
        fwrite(STDERR,"BF10_READY_RECIPIENT_FIELD_MISMATCH ".$key."\n");exit(9);
    }
}
echo "BF10_READY_VERIFIED id=15 status=draft sender=hola@elmercadodeorigen.com audience_source_campaign=14 sender_source_campaign=13 setting_keys=".implode(',',array_keys($actual))."\n";
