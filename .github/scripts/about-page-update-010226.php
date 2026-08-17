<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained; read-only.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_TOLECARNES_QUERY_ROUTE_AUDIT_BEGIN ===\n";
try {
    $urls = array(
        'store_query'  => home_url('/?store=tolecarnes'),
        'vendor_query' => home_url('/?wcfm_vendor=tolecarnes'),
        'shop_vendor'  => home_url('/tienda/?wcfm_vendor=tolecarnes'),
    );
    foreach ($urls as $name => $url) {
        $resp = wp_remote_get($url, array('timeout'=>15,'redirection'=>3,'headers'=>array('Cache-Control'=>'no-cache','Pragma'=>'no-cache'),'user-agent'=>'Mozilla/5.0 EMDO route validator'));
        if (is_wp_error($resp)) { echo 'ROUTE='.$name.'|ERR='.$resp->get_error_message()."\n"; continue; }
        $body = wp_remote_retrieve_body($resp);
        preg_match('/<title[^>]*>(.*?)<\/title>/is',$body,$tm);
        preg_match('/<h1[^>]*>(.*?)<\/h1>/is',$body,$hm);
        preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)/i',$body,$cm);
        if (empty($cm[1])) preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']/i',$body,$cm);
        $product_links = array();
        if (preg_match_all('/href=["\'](https?:\/\/[^"\']+\/producto\/[^"\']+)["\']/i',$body,$pm)) {
            $product_links = array_values(array_unique(array_slice($pm[1],0,10)));
        }
        echo 'ROUTE='.$name
            .'|HTTP='.wp_remote_retrieve_response_code($resp)
            .'|TITLE='.trim(wp_strip_all_tags(isset($tm[1])?$tm[1]:''))
            .'|H1='.trim(wp_strip_all_tags(isset($hm[1])?$hm[1]:''))
            .'|CANONICAL='.(isset($cm[1])?html_entity_decode($cm[1],ENT_QUOTES):'')
            .'|PRODUCT_LINKS='.count($product_links)."\n";
        foreach ($product_links as $link) echo 'PRODUCT_LINK='.$name.'|'.$link."\n";
        foreach (array('Tolecarnes','Burger 100% ternera','Tapilla o picaña de ternera','Montes de Toledo') as $needle) {
            echo 'TEXT='.$name.'|'.$needle.'='.(stripos($body,$needle)!==false?'yes':'no')."\n";
        }
    }
} catch(Throwable $e){ echo 'AUDIT_ERR='.get_class($e).':'.$e->getMessage()."\n"; }
echo "=== EMDO_TOLECARNES_QUERY_ROUTE_AUDIT_END ===\n";
