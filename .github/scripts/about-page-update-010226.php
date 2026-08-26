<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source_id=9;
$draft_id=10;
$recipient='josefraga@onlinetic.es';
$expected_subject='Tenemos nueva imagen. Pero eso es solo el principio';
$image_id=11052;
$state_key='_emdo_fluentcrm_tolecarnes_inline_image_resend_20260817_1218';
function emdo_inline_image_abort($m){ fwrite(STDERR,'EMDO_INLINE_IMAGE_ABORT: '.$m."\n"); exit(29); }

try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    $controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $app_class='\\FluentCrm\\Framework\\Foundation\\App';
    if(!class_exists($campaign_class)||!class_exists($controller_class)||!class_exists($app_class)) emdo_inline_image_abort('FluentCRM classes unavailable');

    $source=$campaign_class::find($source_id);
    $draft=$campaign_class::find($draft_id);
    if(!$source || (string)$source->status!=='archived') emdo_inline_image_abort('source guard failed');
    if(!$draft || (string)$draft->status!=='draft') emdo_inline_image_abort('draft guard failed');
    if((string)$draft->design_template!=='simple' || (int)$draft->template_id!==10453) emdo_inline_image_abort('design guard failed');
    if((string)$draft->email_subject!==$expected_subject) emdo_inline_image_abort('subject guard failed');

    $image_url=wp_get_attachment_url($image_id);
    if(!$image_url) emdo_inline_image_abort('Tolecarnes image attachment unavailable');
    $image_url=add_query_arg('emdo_mail','202608171218',$image_url);

    // Verify that the image is actually reachable before putting it into the email.
    $head=wp_remote_head($image_url,array('timeout'=>15,'redirection'=>5,'user-agent'=>'Mozilla/5.0 EMDO Mail Image Check'));
    if(is_wp_error($head)) emdo_inline_image_abort('image HEAD failed: '.$head->get_error_message());
    $status=(int)wp_remote_retrieve_response_code($head);
    $ctype=(string)wp_remote_retrieve_header($head,'content-type');
    if($status<200 || $status>=400) emdo_inline_image_abort('image HTTP status='.$status);

    $body=(string)$draft->email_body;
    if(stripos($body,'DESCUBRE TOLECARNES')===false || stripos($body,'Una empresa familiar')===false) emdo_inline_image_abort('mailing body guard failed');
    if(stripos($body,'Logo-sin-fondo')!==false) emdo_inline_image_abort('logo unexpectedly present');

    // Remove any previous attempt at the Tolecarnes image, including the marker block.
    $body=preg_replace('#<!--\s*EMDO_TOLECARNES_IMAGE_START\s*-->.*?<!--\s*EMDO_TOLECARNES_IMAGE_END\s*-->#is','',$body);
    $body=preg_replace('#<img\b[^>]*Tolecarnes-fondo\.jpg[^>]*>#is','',$body);

    // Email-safe image: table layout + explicit dimensions + inline styles.
    $image_block='<!-- EMDO_TOLECARNES_IMAGE_START -->'
        .'<table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="width:100%;margin:20px 0 22px 0;border-collapse:collapse;">'
        .'<tr><td align="center" style="padding:0;text-align:center;">'
        .'<img src="'.esc_url($image_url).'" alt="Tolecarnes" width="600" border="0" '
        .'style="display:block !important;width:100% !important;max-width:600px !important;height:auto !important;margin:0 auto !important;border:0 !important;outline:none !important;text-decoration:none !important;" />'
        .'</td></tr></table>'
        .'<!-- EMDO_TOLECARNES_IMAGE_END -->';

    $inserted=0;
    $body=preg_replace_callback('#(<p\b[^>]*>.*?Una empresa familiar.*?</p>)#is',function($m) use($image_block,&$inserted){
        $inserted++;
        return $m[1].$image_block;
    },$body,1);
    if($inserted!==1) emdo_inline_image_abort('image insertion count='.$inserted);

    $draft->email_body=$body;
    $draft->status='draft';
    $draft->save();

    $draft=$campaign_class::find($draft_id);
    if(!$draft || (string)$draft->status!=='draft') emdo_inline_image_abort('draft save verification failed');
    $saved=(string)$draft->email_body;
    if(substr_count($saved,'EMDO_TOLECARNES_IMAGE_START')!==1) emdo_inline_image_abort('marker save verification failed');
    if(substr_count($saved,'<img')!==1) emdo_inline_image_abort('expected exactly one image, found '.substr_count($saved,'<img'));
    if(stripos($saved,'display:block !important')===false) emdo_inline_image_abort('email-safe image style missing');

    $state=get_option($state_key,array());
    if(empty($state['sent'])){
        $app=$app_class::getInstance();
        $request=$app['request'];
        $controller=new $controller_class();
        $request->merge(array('test_campaign'=>false,'campaign_id'=>$draft_id,'email'=>$recipient));
        $test=$controller->sendTestEmail();
        if(is_array($test) && array_key_exists('result',$test) && $test['result']===false) emdo_inline_image_abort('FluentCRM test returned false');
        $state=array(
            'sent'=>true,
            'sent_at'=>current_time('mysql'),
            'recipient'=>$recipient,
            'campaign_id'=>$draft_id,
            'message'=>is_array($test)&&isset($test['message'])?sanitize_text_field($test['message']):'sendTestEmail completed'
        );
        update_option($state_key,$state,false);
    }

    echo "=== EMDO_FLUENTCRM_INLINE_IMAGE_RESEND_OK ===\n";
    echo 'DRAFT_ID='.(int)$draft->id.'|STATUS='.(string)$draft->status."\n";
    echo 'SUBJECT='.(string)$draft->email_subject."\n";
    echo 'IMAGE_ID='.(int)$image_id."\n";
    echo 'IMAGE_URL='.$image_url."\n";
    echo 'IMAGE_HTTP_STATUS='.$status.'|CONTENT_TYPE='.$ctype."\n";
    echo 'IMAGE_HTML_COUNT='.substr_count($saved,'<img')."\n";
    echo 'IMAGE_EMAIL_SAFE_TABLE=yes'."\n";
    echo 'TEST_RECIPIENT='.$recipient."\n";
    echo 'TEST_SENT='.(!empty($state['sent'])?'yes':'no')."\n";
    echo 'TEST_MESSAGE='.(isset($state['message'])?$state['message']:'')."\n";
} catch(Throwable $e){ emdo_inline_image_abort(get_class($e).': '.$e->getMessage()); }
