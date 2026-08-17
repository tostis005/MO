<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source_id = 9;
$draft_id = 10;
$product_id = 11148;
$recipient = 'jose.fraga@gmail.com';
$state_key = '_emdo_fluentcrm_tolecarnes_mail_20260817';

function emdo_correct_abort($m){ fwrite(STDERR,'EMDO_CRM_CORRECT_ABORT: '.$m."\n"); exit(21); }

try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    $controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $app_class='\\FluentCrm\\Framework\\Foundation\\App';

    $source=$campaign_class::find($source_id);
    $draft=$campaign_class::find($draft_id);
    if(!$source || (string)$source->status!=='archived') emdo_correct_abort('source campaign guard failed');
    if(!$draft || (string)$draft->status!=='draft' || (int)$draft->template_id!==10453 || (string)$draft->design_template!=='simple') emdo_correct_abort('draft campaign guard failed');

    $product=get_post($product_id);
    if(!$product || $product->post_type!=='product' || $product->post_status!=='publish' || (int)$product->post_author!==4507 || stripos($product->post_title,'Burger 100% ternera')===false) emdo_correct_abort('Tolecarnes burger product guard failed');
    $cta_url=get_permalink($product_id);
    $resp=wp_remote_get($cta_url,array('timeout'=>12,'redirection'=>3,'user-agent'=>'EMDO corrected CRM test'));
    if(is_wp_error($resp) || (int)wp_remote_retrieve_response_code($resp)!==200) emdo_correct_abort('CTA product URL is not live');

    $body=(string)$draft->email_body;
    $pattern='/(<a\s+class="wp-block-button__link[^>]*"\s+href=")[^"]+("[^>]*><strong>DESCUBRE TOLECARNES<\/strong>)/i';
    $replacement='$1'.esc_url($cta_url).'$2';
    $corrected=preg_replace($pattern,$replacement,$body,1,$count);
    if(!$corrected || $count!==1) emdo_correct_abort('CTA replacement did not match exactly once');
    $draft->email_body=$corrected;
    $draft->status='draft';
    $draft->save();

    $draft=$campaign_class::find($draft_id);
    if(!$draft || strpos((string)$draft->email_body,esc_url($cta_url))===false) emdo_correct_abort('CTA save verification failed');
    if(strpos((string)$draft->email_body,'jamon-de-de-cebo-de-campo')!==false) emdo_correct_abort('old incorrect CTA still present');

    $state=get_option($state_key,array());
    if(empty($state['corrected_test_sent'])) {
        $app=$app_class::getInstance();
        $request=$app['request'];
        $controller=new $controller_class();
        $request->merge(array(
            'test_campaign'=>false,
            'campaign_id'=>$draft_id,
            'email'=>$recipient,
        ));
        $test=$controller->sendTestEmail();
        if(is_array($test) && array_key_exists('result',$test) && $test['result']===false) emdo_correct_abort('FluentCRM corrected test returned false');
        $state['corrected_test_sent']=true;
        $state['corrected_test_sent_at']=current_time('mysql');
        $state['corrected_test_message']=is_array($test)&&isset($test['message'])?sanitize_text_field($test['message']):'sendTestEmail completed';
        $state['corrected_cta']=$cta_url;
        update_option($state_key,$state,false);
    }

    $source_check=$campaign_class::find($source_id);
    if(!$source_check || (string)$source_check->status!=='archived') emdo_correct_abort('source changed unexpectedly');

    echo "=== EMDO_FLUENTCRM_CORRECTED_OK ===\n";
    echo 'SOURCE_STATUS='.(string)$source_check->status."\n";
    echo 'DRAFT_CAMPAIGN_ID='.(int)$draft->id."\n";
    echo 'DRAFT_STATUS='.(string)$draft->status."\n";
    echo 'DRAFT_TEMPLATE_ID='.(int)$draft->template_id."\n";
    echo 'DRAFT_DESIGN='.(string)$draft->design_template."\n";
    echo 'CTA_PRODUCT_ID='.$product_id."\n";
    echo 'CTA_PRODUCT_TITLE='.$product->post_title."\n";
    echo 'CTA_URL='.$cta_url."\n";
    echo 'CORRECTED_TEST_RECIPIENT='.$recipient."\n";
    echo 'CORRECTED_TEST_SENT='.(!empty($state['corrected_test_sent'])?'yes':'no')."\n";
    echo 'CORRECTED_TEST_MESSAGE='.(isset($state['corrected_test_message'])?$state['corrected_test_message']:'')."\n";
} catch(Throwable $e) {
    emdo_correct_abort(get_class($e).': '.$e->getMessage());
}
