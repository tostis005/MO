<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source_id=9;
$draft_id=10;
$recipient='josefraga@onlinetic.es';
$expected_subject='Tenemos nueva imagen. Pero eso es solo el principio';
$image_url='https://www.elmercadodeorigen.com/wp-content/uploads/2026/08/Tolecarnes-fondo.jpg';
$state_key='_emdo_fluentcrm_tolecarnes_typography_image_test_20260817_1207';
function emdo_typo_image_abort($m){ fwrite(STDERR,'EMDO_TYPO_IMAGE_ABORT: '.$m."\n"); exit(28); }

try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    $controller_class='\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $app_class='\\FluentCrm\\Framework\\Foundation\\App';
    if(!class_exists($campaign_class)||!class_exists($controller_class)||!class_exists($app_class)) emdo_typo_image_abort('FluentCRM classes unavailable');

    $source=$campaign_class::find($source_id);
    $draft=$campaign_class::find($draft_id);
    if(!$source || (string)$source->status!=='archived') emdo_typo_image_abort('source guard failed');
    if(!$draft || (string)$draft->status!=='draft') emdo_typo_image_abort('draft guard failed');
    if((string)$draft->design_template!=='simple' || (int)$draft->template_id!==10453) emdo_typo_image_abort('design guard failed');
    if((string)$draft->email_subject!==$expected_subject) emdo_typo_image_abort('subject guard failed');

    $body=(string)$draft->email_body;
    if(stripos($body,'DESCUBRE TOLECARNES')===false || stripos($body,'Una empresa familiar')===false) emdo_typo_image_abort('mailing body guard failed');
    if(stripos($body,'Logo-sin-fondo')!==false) emdo_typo_image_abort('logo unexpectedly present');

    // Start from the current approved copy. Remove a prior Tolecarnes image if this script is ever retried after save.
    $body=preg_replace('#<!--\s*EMDO_TOLECARNES_IMAGE_START\s*-->.*?<!--\s*EMDO_TOLECARNES_IMAGE_END\s*-->#is','',$body);

    // Replace headings with normal-size bold paragraphs so emphasis comes from weight, not size.
    $body=preg_replace_callback('#<h([1-6])\b[^>]*>(.*?)</h\1>#is',function($m){
        $inner=trim($m[2]);
        $plain=preg_replace('#^\s*<strong>(.*)</strong>\s*$#is','$1',$inner);
        return '<p style="font-size:16px;line-height:1.6;margin:0 0 16px;"><strong>'.$plain.'</strong></p>';
    },$body);

    // Give every normal paragraph exactly the same typography.
    $body=preg_replace('#<p\b[^>]*>#i','<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">',$body);

    // Normalize list text as well, if present.
    $body=preg_replace('#<li\b[^>]*>#i','<li style="font-size:16px;line-height:1.6;">',$body);

    // Add the producer's store/banner image after its introductory description paragraph.
    $image_block='<!-- EMDO_TOLECARNES_IMAGE_START -->'
        .'<div style="margin:22px 0;text-align:center;">'
        .'<img src="'.esc_url($image_url).'" alt="Tolecarnes" width="660" style="display:block;width:100%;max-width:660px;height:auto;margin:0 auto;border:0;" />'
        .'</div>'
        .'<!-- EMDO_TOLECARNES_IMAGE_END -->';

    $count=0;
    $body=preg_replace_callback('#(<p\b[^>]*>.*?Una empresa familiar.*?</p>)#is',function($m) use($image_block,&$count){
        $count++;
        return $m[1].$image_block;
    },$body,1);
    if($count!==1) emdo_typo_image_abort('intro paragraph image insertion count='.$count);

    // Remove font-size declarations from non-button wrapper tags that could visually override paragraphs.
    $body=preg_replace_callback('#<(div|span)\b([^>]*)>#i',function($m){
        $attrs=$m[2];
        if(stripos($attrs,'wp-block-button')!==false) return $m[0];
        $attrs=preg_replace('/font-size\s*:\s*[^;"\']+;?/i','',$attrs);
        return '<'.$m[1].$attrs.'>';
    },$body);

    $draft->email_body=$body;
    $draft->status='draft';
    $draft->save();

    $draft=$campaign_class::find($draft_id);
    if(!$draft || (string)$draft->status!=='draft') emdo_typo_image_abort('draft save verification failed');
    $saved=(string)$draft->email_body;
    if(substr_count($saved,$image_url)!==1) emdo_typo_image_abort('image save verification failed');
    if(preg_match('#<h[1-6]\b#i',$saved)) emdo_typo_image_abort('heading remains after normalization');
    if(stripos($saved,'Logo-sin-fondo')!==false) emdo_typo_image_abort('logo returned unexpectedly');
    if(substr_count($saved,'font-size:16px')<5) emdo_typo_image_abort('paragraph typography verification failed');

    $state=get_option($state_key,array());
    if(empty($state['sent'])){
        $app=$app_class::getInstance();
        $request=$app['request'];
        $controller=new $controller_class();
        $request->merge(array('test_campaign'=>false,'campaign_id'=>$draft_id,'email'=>$recipient));
        $test=$controller->sendTestEmail();
        if(is_array($test) && array_key_exists('result',$test) && $test['result']===false) emdo_typo_image_abort('FluentCRM test returned false');
        $state=array(
            'sent'=>true,
            'sent_at'=>current_time('mysql'),
            'recipient'=>$recipient,
            'campaign_id'=>$draft_id,
            'message'=>is_array($test)&&isset($test['message'])?sanitize_text_field($test['message']):'sendTestEmail completed'
        );
        update_option($state_key,$state,false);
    }

    echo "=== EMDO_FLUENTCRM_TYPO_IMAGE_TEST_OK ===\n";
    echo 'SOURCE_ID='.(int)$source->id.'|STATUS='.(string)$source->status."\n";
    echo 'DRAFT_ID='.(int)$draft->id.'|STATUS='.(string)$draft->status.'|DESIGN='.(string)$draft->design_template.'|TEMPLATE_ID='.(int)$draft->template_id."\n";
    echo 'SUBJECT='.(string)$draft->email_subject."\n";
    echo 'TYPOGRAPHY=16px_uniform|HEADINGS=0|EMPHASIS=bold'."\n";
    echo 'IMAGE_URL='.$image_url."\n";
    echo 'IMAGE_COUNT='.substr_count($saved,$image_url)."\n";
    echo 'TEST_RECIPIENT='.$recipient."\n";
    echo 'TEST_SENT='.(!empty($state['sent'])?'yes':'no')."\n";
    echo 'TEST_MESSAGE='.(isset($state['message'])?$state['message']:'')."\n";
} catch(Throwable $e){ emdo_typo_image_abort(get_class($e).': '.$e->getMessage()); }
