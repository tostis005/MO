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
    echo 'WCFM_SETTINGS_TYPE='.(is_array($settings)?'array':gettype($settings))."\n";
    if(is_array($settings)){
        foreach($settings as $k=>$v){
            $lk=strtolower((string)$k);
            if(strpos($lk,'banner')!==false || strpos($lk,'logo')!==false || strpos($lk,'avatar')!==false || strpos($lk,'store')!==false){
                if(is_scalar($v)) echo 'SETTING_'.$k.'='.(string)$v."\n";
                elseif(is_array($v)) echo 'SETTING_'.$k.'='.wp_json_encode($v,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
            }
        }
    }

    $all_meta=get_user_meta($vendor_id);
    foreach($all_meta as $k=>$vals){
        $lk=strtolower((string)$k);
        if(strpos($lk,'banner')!==false || strpos($lk,'logo')!==false || strpos($lk,'avatar')!==false || strpos($lk,'store')!==false || strpos($lk,'wcfm')!==false){
            foreach((array)$vals as $v){
                if(is_scalar($v)){
                    $sv=(string)$v;
                    if(strpos($sv,'http')!==false || preg_match('/^\d+$/',$sv)) echo 'META_'.$k.'='.$sv."\n";
                }
            }
        }
    }

    $products=get_posts(array(
        'post_type'=>'product','post_status'=>array('publish','draft','pending','private'),'author'=>$vendor_id,
        'numberposts'=>20,'orderby'=>'modified','order'=>'DESC'
    ));
    $n=0;
    foreach($products as $p){
        $thumb=get_post_thumbnail_id($p->ID);
        $url=$thumb?wp_get_attachment_image_url($thumb,'large'):'';
        if(!$url) continue;
        $n++;
        echo 'PRODUCT_IMAGE_'.$n.'|ID='.(int)$p->ID.'|STATUS='.(string)$p->post_status.'|TITLE='.str_replace(array("\n","\r"),' ',(string)$p->post_title).'|URL='.$url."\n";
        if($n>=10) break;
    }

    echo "=== EMDO_TOLECARNES_IMAGE_AUDIT_END ===\n";
} catch(Throwable $e){ emdo_image_audit_abort(get_class($e).': '.$e->getMessage()); }
