<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$draft_id=10;
$vendor_id=4507;
function emdo_image_audit_abort($m){ fwrite(STDERR,'EMDO_IMAGE_AUDIT_ABORT: '.$m."\n"); exit(27); }

try {
    $campaign_class='\\FluentCrm\\App\\Models\\Campaign';
    if(!class_exists($campaign_class)) emdo_image_audit_abort('FluentCRM class unavailable');
    $draft=$campaign_class::find($draft_id);
    if(!$draft || (string)$draft->status!=='draft') emdo_image_audit_abort('draft guard failed');

    echo "=== EMDO_TOLECARNES_IMAGE_AUDIT_BEGIN ===\n";
    echo 'DRAFT_ID='.(int)$draft->id.'|SUBJECT='.(string)$draft->email_subject."\n";

    $settings=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
    if(!is_array($settings)) emdo_image_audit_abort('WCFM settings unavailable');
    foreach(array('gravatar','banner','mobile_banner','list_banner') as $key){
        $id=isset($settings[$key])?(int)$settings[$key]:0;
        if(!$id) continue;
        $url=wp_get_attachment_url($id);
        $meta=wp_get_attachment_metadata($id);
        $w=is_array($meta)&&isset($meta['width'])?(int)$meta['width']:0;
        $h=is_array($meta)&&isset($meta['height'])?(int)$meta['height']:0;
        echo strtoupper($key).'_ID='.$id.'|URL='.$url.'|WIDTH='.$w.'|HEIGHT='.$h."\n";
    }

    foreach(array(11148,11082,11145,11154,11136) as $pid){
        $p=get_post($pid);
        if(!$p) continue;
        $thumb=get_post_thumbnail_id($pid);
        $url=$thumb?wp_get_attachment_image_url($thumb,'large'):'';
        $meta=$thumb?wp_get_attachment_metadata($thumb):array();
        $w=is_array($meta)&&isset($meta['width'])?(int)$meta['width']:0;
        $h=is_array($meta)&&isset($meta['height'])?(int)$meta['height']:0;
        echo 'PRODUCT|ID='.$pid.'|TITLE='.str_replace(array("\n","\r"),' ',(string)$p->post_title).'|URL='.$url.'|WIDTH='.$w.'|HEIGHT='.$h."\n";
    }

    echo "=== EMDO_TOLECARNES_IMAGE_AUDIT_END ===\n";
} catch(Throwable $e){ emdo_image_audit_abort(get_class($e).': '.$e->getMessage()); }
