<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained for production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$uid=4507;
$profile_key='wcfmmp_profile_settings';
$backup_key='_emdo_wcfm_profile_backup_before_vendor_id_fix_20260817';
$product_id=11148;

function emdo_vendor_fix_abort($m){ fwrite(STDERR,'EMDO_VENDOR_FIX_ABORT: '.$m."\n"); exit(22); }

try {
    $user=get_userdata($uid);
    if(!$user || !in_array('wcfm_vendor',(array)$user->roles,true)) emdo_vendor_fix_abort('Tolecarnes vendor user guard failed');
    $settings=get_user_meta($uid,$profile_key,true);
    if(!is_array($settings) || ($settings['store_name']??'')!=='Tolecarnes' || ($settings['store_slug']??'')!=='tolecarnes') emdo_vendor_fix_abort('profile settings guard failed');

    $current=(string)($settings['vendor_id']??'');
    if($current!=='99999' && $current!==(string)$uid) emdo_vendor_fix_abort('unexpected existing vendor_id='.$current);

    if($current==='99999'){
        if(get_user_meta($uid,$backup_key,true)==='') update_user_meta($uid,$backup_key,$settings);
        $settings['vendor_id']=(string)$uid;
        update_user_meta($uid,$profile_key,$settings);
        clean_user_cache($uid);
        if(function_exists('wp_cache_flush')) wp_cache_flush();
    }

    $verify=get_user_meta($uid,$profile_key,true);
    if(!is_array($verify) || (string)($verify['vendor_id']??'')!==(string)$uid) emdo_vendor_fix_abort('vendor_id save verification failed');

    $store=function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($uid):home_url('/tienda/tolecarnes/');
    $product=get_permalink($product_id);
    $checks=array('store'=>$store,'product'=>$product);
    $results=array();
    foreach($checks as $name=>$url){
        $probe=add_query_arg('emdo_vendor_fix_probe',time().rand(100,999),$url);
        $resp=wp_remote_get($probe,array('timeout'=>15,'redirection'=>3,'headers'=>array('Cache-Control'=>'no-cache','Pragma'=>'no-cache'),'user-agent'=>'Mozilla/5.0 EMDO vendor fix verifier'));
        $code=is_wp_error($resp)?0:(int)wp_remote_retrieve_response_code($resp);
        $body=is_wp_error($resp)?'':wp_remote_retrieve_body($resp);
        $results[$name]=array('url'=>$url,'code'=>$code,'has_tole'=>stripos($body,'Tolecarnes')!==false,'has_burger'=>stripos($body,'Burger 100% ternera')!==false);
        echo 'CHECK='.$name.'|HTTP='.$code.'|HAS_TOLE='.($results[$name]['has_tole']?'yes':'no').'|HAS_BURGER='.($results[$name]['has_burger']?'yes':'no').'|URL='.$url."\n";
    }

    if($results['store']['code']!==200 || !$results['store']['has_tole'] || $results['product']['code']!==200 || !$results['product']['has_burger']){
        $backup=get_user_meta($uid,$backup_key,true);
        if(is_array($backup)){
            update_user_meta($uid,$profile_key,$backup);
            clean_user_cache($uid);
            if(function_exists('wp_cache_flush')) wp_cache_flush();
        }
        emdo_vendor_fix_abort('public verification failed; original profile restored');
    }

    echo "=== EMDO_VENDOR_FIX_OK ===\n";
    echo 'USER_ID='.$uid."\n";
    echo 'VENDOR_ID_BEFORE='.$current."\n";
    echo 'VENDOR_ID_AFTER='.(string)$verify['vendor_id']."\n";
    echo 'STORE_URL='.$store."\n";
    echo 'PRODUCT_URL='.$product."\n";
} catch(Throwable $e){ emdo_vendor_fix_abort(get_class($e).': '.$e->getMessage()); }
