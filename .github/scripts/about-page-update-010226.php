<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained; read-only.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_TOLECARNES_VENDOR_FILTER_AUDIT_BEGIN ===\n";
try {
    $uid=4507;
    $u=get_userdata($uid);
    echo 'USER_ROLES='.wp_json_encode($u?$u->roles:array())."\n";
    foreach(array('wcfmmp_profile_settings','wcfm_vendor_status','wcfmmp_vendor_status','wcfm_vendor_store','wcfmmp_store_name') as $key){
        $v=get_user_meta($uid,$key,true);
        if($v!=='' && $v!==null) echo 'USER_META='.$key.'|'.(is_scalar($v)?$v:wp_json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES))."\n";
    }

    $urls=array(
        'author_home'=>home_url('/?author=4507'),
        'author_products'=>home_url('/?post_type=product&author=4507'),
        'shop_author'=>home_url('/tienda/?author=4507'),
        'shop_vendor_id'=>home_url('/tienda/?vendor_id=4507'),
        'shop_wcfm_vendor_id'=>home_url('/tienda/?wcfm_vendor=4507'),
        'shop_store_name'=>home_url('/tienda/?store=tolecarnes'),
        'shop_search_burger'=>home_url('/tienda/?s=burger+100%25+ternera&post_type=product'),
    );
    foreach($urls as $name=>$url){
        $resp=wp_remote_get($url,array('timeout'=>12,'redirection'=>3,'headers'=>array('Cache-Control'=>'no-cache'),'user-agent'=>'Mozilla/5.0 EMDO vendor filter validator'));
        if(is_wp_error($resp)){ echo 'ROUTE='.$name.'|ERR='.$resp->get_error_message()."\n"; continue; }
        $body=wp_remote_retrieve_body($resp);
        preg_match('/<title[^>]*>(.*?)<\/title>/is',$body,$tm);
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is',$body,$hm);
        $matches=array();
        if(preg_match_all('/href=["\'](https?:\/\/[^"\']+\/producto\/[^"\']+)["\']/i',$body,$pm)) $matches=array_values(array_unique($pm[1]));
        echo 'ROUTE='.$name.'|HTTP='.wp_remote_retrieve_response_code($resp).'|TITLE='.trim(wp_strip_all_tags($tm[1]??'')).'|H1='.trim(wp_strip_all_tags($hm[1]??'')).'|HAS_TOLE='.(stripos($body,'Tolecarnes')!==false?'yes':'no').'|HAS_BURGER='.(stripos($body,'Burger 100% ternera')!==false?'yes':'no').'|HAS_TAPILLA='.(stripos($body,'Tapilla o picaña')!==false?'yes':'no').'|LINKS='.count($matches)."\n";
        foreach(array_slice($matches,0,12) as $link) echo 'LINK='.$name.'|'.$link."\n";
    }
} catch(Throwable $e){ echo 'AUDIT_ERR='.get_class($e).':'.$e->getMessage()."\n"; }
echo "=== EMDO_TOLECARNES_VENDOR_FILTER_AUDIT_END ===\n";
