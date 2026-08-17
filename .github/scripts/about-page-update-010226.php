<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source_id=9;
$draft_id=10;
$recipient='josefraga@onlinetic.es';
$new_subject='Tenemos nueva imagen. Pero eso es solo el principio';
$logo_url='https://www.elmercadodeorigen.com/wp-content/uploads/2021/03/Logo-sin-fondo-150x150.jpg';
$state_key='_emdo_fluentcrm_personal_style_test_20260817_1147';
function emdo_personal_test_abort($m){ fwrite(STDERR,'EMDO_PERSONAL_TEST_ABORT: '.$m."\n"); exit(25); }

try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    $controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $app_class='\\FluentCrm\\Framework\\Foundation\\App';
    if(!class_exists($campaign_class)||!class_exists($controller_class)||!class_exists($app_class)) emdo_personal_test_abort('FluentCRM classes unavailable');

    $source=$campaign_class::find($source_id);
    $draft=$campaign_class::find($draft_id);
    if(!$source || (string)$source->status!=='archived') emdo_personal_test_abort('source campaign guard failed');
    if(!$draft || (string)$draft->status!=='draft') emdo_personal_test_abort('draft campaign guard failed');
    if((string)$draft->design_template!=='simple' || (int)$draft->template_id!==10453) emdo_personal_test_abort('design guard failed');

    $body=(string)$draft->email_body;
    if(stripos($body,'DESCUBRE TOLECARNES')===false || stripos($body,'Tenemos nueva imagen')===false) emdo_personal_test_abort('mailing body guard failed');
    if(strpos($body,$logo_url)===false) emdo_personal_test_abort('expected logo not found');

    $updated=$body;
    $removed=0;
    $updated=preg_replace_callback('#<!--\\s*wp:image\\b.*?-->.*?<!--\\s*/wp:image\\s*-->#is',function($m) use($logo_url,&$removed){
        if(strpos($m[0],$logo_url)!==false){ $removed++; return ''; }
        return $m[0];
    },$updated);
    if($removed===0){
        $q=preg_quote($logo_url,'#');
        $updated=preg_replace('#<figure\\b[^>]*>.*?<img\\b[^>]*src=["\\\']'.$q.'["\\\'][^>]*>.*?</figure>#is','',$updated,1,$removed);
    }
    if($removed===0){
        $q=preg_quote($logo_url,'#');
        $updated=preg_replace('#<img\\b[^>]*src=["\\\']'.$q.'["\\\'][^>]*?/?>#is','',$updated,1,$removed);
    }
    if($removed!==1) emdo_personal_test_abort('logo removal count='.$removed);
    if(strpos($updated,$logo_url)!==false) emdo_personal_test_abort('logo URL remains after removal');

    $draft->email_body=$updated;
    $draft->email_subject=$new_subject;
    $draft->title=$new_subject;
    $draft->status='draft';
    $draft->save();

    $draft=$campaign_class::find($draft_id);
    if(!$draft || (string)$draft->status!=='draft') emdo_personal_test_abort('draft save verification failed');
    if((string)$draft->email_subject!==$new_subject || (string)$draft->title!==$new_subject) emdo_personal_test_abort('subject/title verification failed');
    if(strpos((string)$draft->email_body,$logo_url)!==false) emdo_personal_test_abort('logo save verification failed');
    if(strpos((string)$draft->email_subject,'👀')!==false || strpos((string)$draft->title,'👀')!==false) emdo_personal_test_abort('emoji still present');

    $state=get_option($state_key,array());
    if(empty($state['sent'])){
        $app=$app_class::getInstance();
        $request=$app['request'];
        $controller=new $controller_class();
        $request->merge(array('test_campaign'=>false,'campaign_id'=>$draft_id,'email'=>$recipient));
        $test=$controller->sendTestEmail();
        if(is_array($test) && array_key_exists('result',$test) && $test['result']===false) emdo_personal_test_abort('FluentCRM test returned false');
        $state=array(
            'sent'=>true,
            'sent_at'=>current_time('mysql'),
            'recipient'=>$recipient,
            'campaign_id'=>$draft_id,
            'message'=>is_array($test)&&isset($test['message'])?sanitize_text_field($test['message']):'sendTestEmail completed'
        );
        update_option($state_key,$state,false);
    }

    $img_count=preg_match_all('/<img\\b[^>]*>/i',(string)$draft->email_body,$tmp);
    echo "=== EMDO_FLUENTCRM_PERSONAL_TEST_OK ===\n";
    echo 'SOURCE_ID='.(int)$source->id.'|STATUS='.(string)$source->status."\n";
    echo 'DRAFT_ID='.(int)$draft->id.'|STATUS='.(string)$draft->status.'|DESIGN='.(string)$draft->design_template.'|TEMPLATE_ID='.(int)$draft->template_id."\n";
    echo 'TITLE='.(string)$draft->title."\n";
    echo 'SUBJECT='.(string)$draft->email_subject."\n";
    echo 'LOGO_REMOVED=yes|IMG_COUNT='.(int)$img_count."\n";
    echo 'TEST_RECIPIENT='.$recipient."\n";
    echo 'TEST_SENT='.(!empty($state['sent'])?'yes':'no')."\n";
    echo 'TEST_MESSAGE='.(isset($state['message'])?$state['message']:'')."\n";
} catch(Throwable $e){ emdo_personal_test_abort(get_class($e).': '.$e->getMessage()); }
