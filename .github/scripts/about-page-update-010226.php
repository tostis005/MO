<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained; read-only.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_VENDOR_COMPARE_BEGIN ===\n";
try {
    $users=get_users(array('role'=>'wcfm_vendor','number'=>200));
    foreach($users as $u){
        $settings=get_user_meta($u->ID,'wcfmmp_profile_settings',true);
        $store_name=is_array($settings)&&isset($settings['store_name'])?$settings['store_name']:get_user_meta($u->ID,'wcfmmp_store_name',true);
        if(!$store_name) continue;
        if(stripos($store_name,'Tolecarnes')===false && stripos($store_name,'1957')===false && stripos($store_name,'Hidalgo')===false) continue;
        $slug=is_array($settings)&&isset($settings['store_slug'])?$settings['store_slug']:$u->user_nicename;
        $vendor_id=is_array($settings)&&isset($settings['vendor_id'])?$settings['vendor_id']:'';
        $url=function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($u->ID):home_url('/tienda/'.$slug.'/');
        $resp=wp_remote_get($url,array('timeout'=>12,'redirection'=>3,'headers'=>array('Cache-Control'=>'no-cache'),'user-agent'=>'Mozilla/5.0 EMDO vendor compare'));
        $code=is_wp_error($resp)?'ERR':wp_remote_retrieve_response_code($resp);
        $body=is_wp_error($resp)?'':wp_remote_retrieve_body($resp);
        $pub=(int)count_user_posts($u->ID,'product',true);
        echo 'VENDOR='.wp_json_encode(array(
            'user_id'=>$u->ID,
            'login'=>$u->user_login,
            'roles'=>$u->roles,
            'store_name'=>$store_name,
            'store_slug'=>$slug,
            'profile_vendor_id'=>$vendor_id,
            'store_url'=>$url,
            'http'=>$code,
            'public_product_count'=>$pub,
            'has_store_name'=>stripos($body,$store_name)!==false,
        ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
        foreach(array('wcfm_vendor_status','wcfmmp_vendor_status','_wcfm_vendor_status','wcfm_vendor_disable','wcfm_vendor_enable','wcfmmp_store_offline') as $key){
            $v=get_user_meta($u->ID,$key,true);
            if($v!==''&&$v!==null) echo 'META='.$u->ID.'|'.$key.'|'.(is_scalar($v)?$v:wp_json_encode($v))."\n";
        }
    }
} catch(Throwable $e){ echo 'AUDIT_ERR='.get_class($e).':'.$e->getMessage()."\n"; }
echo "=== EMDO_VENDOR_COMPARE_END ===\n";
