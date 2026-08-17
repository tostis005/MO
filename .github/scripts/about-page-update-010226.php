<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$draft_id=10;
function emdo_logo_audit_abort($m){ fwrite(STDERR,'EMDO_LOGO_AUDIT_ABORT: '.$m."\n"); exit(24); }
try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    if(!class_exists($campaign_class)) emdo_logo_audit_abort('FluentCRM campaign class unavailable');
    $draft=$campaign_class::find($draft_id);
    if(!$draft || (string)$draft->status!=='draft') emdo_logo_audit_abort('draft guard failed');
    echo "=== EMDO_FLUENTCRM_LOGO_AUDIT_BEGIN ===\n";
    echo 'ID='.(int)$draft->id.'|STATUS='.(string)$draft->status.'|DESIGN='.(string)$draft->design_template.'|TEMPLATE_ID='.(int)$draft->template_id."\n";
    echo 'TITLE='.(string)$draft->title."\n";
    echo 'SUBJECT='.(string)$draft->email_subject."\n";
    $body=(string)$draft->email_body;
    $imgs=array();
    if(preg_match_all('/<img\\b[^>]*>/i',$body,$m)) $imgs=$m[0];
    echo 'IMG_COUNT='.count($imgs)."\n";
    foreach($imgs as $i=>$tag){
        echo 'IMG_'.($i+1).'='.substr(preg_replace('/\\s+/',' ',$tag),0,1200)."\n";
    }
    foreach(array('settings','email_body','design_template','template_id') as $field){
        if($field==='email_body') continue;
        $val=$draft->{$field}??null;
        if(is_array($val)||is_object($val)) $val=wp_json_encode($val,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        echo 'FIELD_'.$field.'='.substr((string)$val,0,3000)."\n";
    }
    echo "=== EMDO_FLUENTCRM_LOGO_AUDIT_END ===\n";
} catch(Throwable $e){ emdo_logo_audit_abort(get_class($e).': '.$e->getMessage()); }
