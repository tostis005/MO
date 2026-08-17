<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source_id = 9;
$draft_id = 10;
$recipient = 'jose.fraga@gmail.com';
$future_cta_url = home_url('/tienda/tolecarnes');
$state_key = '_emdo_fluentcrm_tolecarnes_final_test_20260817';

function emdo_crm_final_abort($m){ fwrite(STDERR, 'EMDO_CRM_FINAL_ABORT: '.$m."\n"); exit(23); }

try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    $controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $app_class='\\FluentCrm\\Framework\\Foundation\\App';

    if (!class_exists($campaign_class) || !class_exists($controller_class) || !class_exists($app_class)) {
        emdo_crm_final_abort('FluentCRM classes unavailable');
    }

    $source=$campaign_class::find($source_id);
    $draft=$campaign_class::find($draft_id);

    if(!$source || (string)$source->status!=='archived') emdo_crm_final_abort('source campaign guard failed');
    if(!$draft || (string)$draft->status!=='draft') emdo_crm_final_abort('draft campaign guard failed');
    if((string)$draft->design_template!=='simple') emdo_crm_final_abort('draft design guard failed');

    $body=(string)$draft->email_body;
    if(stripos($body,'DESCUBRE TOLECARNES')===false) emdo_crm_final_abort('CTA label not found');
    if(stripos($body,'Tenemos nueva imagen')===false) emdo_crm_final_abort('final mailing body guard failed');

    // Only update the CTA destination. Tolecarnes is intentionally not enabled yet,
    // so no public URL availability check is performed here.
    $pattern='/(<a\s+class="wp-block-button__link[^>]*"\s+href=")[^"]+("[^>]*><strong>DESCUBRE TOLECARNES<\/strong>)/i';
    $replacement='$1'.esc_url($future_cta_url).'$2';
    $updated=preg_replace($pattern,$replacement,$body,1,$count);
    if(!$updated || $count!==1) emdo_crm_final_abort('CTA replacement did not match exactly once');

    $draft->email_body=$updated;
    $draft->status='draft';
    $draft->save();

    $draft=$campaign_class::find($draft_id);
    if(!$draft || (string)$draft->status!=='draft') emdo_crm_final_abort('draft save verification failed');
    if(strpos((string)$draft->email_body,esc_url($future_cta_url))===false) emdo_crm_final_abort('future Tolecarnes CTA not saved');

    $state=get_option($state_key,array());
    if(empty($state['sent'])) {
        $app=$app_class::getInstance();
        $request=$app['request'];
        $controller=new $controller_class();
        $request->merge(array(
            'test_campaign'=>false,
            'campaign_id'=>$draft_id,
            'email'=>$recipient,
        ));
        $test=$controller->sendTestEmail();
        if(is_array($test) && array_key_exists('result',$test) && $test['result']===false) {
            emdo_crm_final_abort('FluentCRM test returned false');
        }
        $state=array(
            'sent'=>true,
            'sent_at'=>current_time('mysql'),
            'recipient'=>$recipient,
            'campaign_id'=>$draft_id,
            'cta'=>$future_cta_url,
            'message'=>is_array($test)&&isset($test['message'])?sanitize_text_field($test['message']):'sendTestEmail completed'
        );
        update_option($state_key,$state,false);
    }

    $source_check=$campaign_class::find($source_id);
    if(!$source_check || (string)$source_check->status!=='archived') emdo_crm_final_abort('source campaign changed unexpectedly');

    echo "=== EMDO_FLUENTCRM_FINAL_TEST_OK ===\n";
    echo 'SOURCE_ID='.(int)$source_check->id.'|STATUS='.(string)$source_check->status."\n";
    echo 'DRAFT_ID='.(int)$draft->id.'|STATUS='.(string)$draft->status.'|DESIGN='.(string)$draft->design_template.'|TEMPLATE_ID='.(int)$draft->template_id."\n";
    echo 'DRAFT_TITLE='.(string)$draft->title."\n";
    echo 'DRAFT_SUBJECT='.(string)$draft->email_subject."\n";
    echo 'CTA_URL='.$future_cta_url."\n";
    echo 'TEST_RECIPIENT='.$recipient."\n";
    echo 'TEST_SENT='.(!empty($state['sent'])?'yes':'no')."\n";
    echo 'TEST_MESSAGE='.(isset($state['message'])?$state['message']:'')."\n";
} catch(Throwable $e) {
    emdo_crm_final_abort(get_class($e).': '.$e->getMessage());
}
