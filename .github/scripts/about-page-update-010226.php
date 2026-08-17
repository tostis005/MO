<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained; read-only.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_CACHE_BYPASS_AUDIT_BEGIN ===\n";
try {
    $urls=array(
        'product'=>get_permalink(11148),
        'store'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url(4507):home_url('/tienda/tolecarnes/'),
        'product_query'=>home_url('/?post_type=product&p=11148'),
        'store_query'=>home_url('/?store=tolecarnes'),
        'vendor_query'=>home_url('/?wcfm_vendor=tolecarnes'),
        'shop_vendor'=>home_url('/tienda/?wcfm_vendor=tolecarnes'),
        'shop_search'=>home_url('/tienda/?s=ternera&post_type=product'),
    );
    foreach($urls as $name=>$url){
        foreach(array('plain'=>$url,'bypass'=>add_query_arg('emdo_mail_probe',time(),$url)) as $mode=>$probe){
            $resp=wp_remote_get($probe,array(
                'timeout'=>15,'redirection'=>3,
                'headers'=>array('Cache-Control'=>'no-cache','Pragma'=>'no-cache'),
                'user-agent'=>'Mozilla/5.0 EMDO mail link probe'
            ));
            if(is_wp_error($resp)){ echo 'CHECK='.$name.'|'.$mode.'|ERR='.$resp->get_error_message().'|URL='.$probe."\n"; continue; }
            $body=wp_remote_retrieve_body($resp);
            echo 'CHECK='.$name.'|'.$mode.'|HTTP='.wp_remote_retrieve_response_code($resp)
                .'|HAS_TOLE='.(stripos($body,'Tolecarnes')!==false?'yes':'no')
                .'|HAS_BURGER='.(stripos($body,'Burger 100% ternera')!==false?'yes':'no')
                .'|HAS_404='.(stripos($body,'404')!==false?'yes':'no')
                .'|URL='.$probe."\n";
        }
    }

    if(defined('ICL_SITEPRESS_VERSION')){
        $details=apply_filters('wpml_element_language_details',null,array('element_id'=>11148,'element_type'=>'post_product'));
        echo 'WPML_DETAILS='.wp_json_encode($details,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
    }
    if(function_exists('wc_get_product')){
        $p=wc_get_product(11148);
        if($p) echo 'WC_PRODUCT=STATUS:'.$p->get_status().'|CATALOG:'.$p->get_catalog_visibility().'|PURCHASABLE:'.($p->is_purchasable()?'yes':'no').'|STOCK:'.$p->get_stock_status()."\n";
    }
} catch(Throwable $e){ echo 'AUDIT_ERR='.get_class($e).':'.$e->getMessage()."\n"; }
echo "=== EMDO_CACHE_BYPASS_AUDIT_END ===\n";
