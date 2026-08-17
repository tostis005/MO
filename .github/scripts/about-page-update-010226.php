<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained; read-only audit.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_TOLECARNES_LINK_AUDIT_BEGIN ===\n";
try {
    $uid = 4507;
    $u = get_userdata($uid);
    echo 'USER=' . ($u ? $u->user_login . '|' . $u->display_name : 'missing') . "\n";

    $products = get_posts(array(
        'post_type'=>'product','post_status'=>array('publish','private','draft','pending'),
        'author'=>$uid,'posts_per_page'=>100,'orderby'=>'date','order'=>'DESC'
    ));
    foreach($products as $p) {
        echo 'AUTHOR_PRODUCT=' . $p->ID . '|' . $p->post_status . '|' . $p->post_title . '|' . get_permalink($p->ID) . "\n";
    }

    global $wpdb;
    $needles = array('%ternera%','%hamburguesa%','%entrecot%','%solomillo%','%tolecarnes%');
    foreach($needles as $needle) {
        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT ID,post_status,post_title,post_name,post_author FROM {$wpdb->posts} WHERE post_type='product' AND (post_title LIKE %s OR post_name LIKE %s) ORDER BY ID DESC LIMIT 60",
            $needle,$needle
        ));
        foreach($rows as $r) {
            echo 'MATCH_PRODUCT=' . $r->ID . '|' . $r->post_status . '|' . $r->post_author . '|' . $r->post_title . '|' . get_permalink($r->ID) . "\n";
        }
    }

    foreach(array(home_url('/'),home_url('/productores/'),home_url('/tienda/')) as $page_url) {
        $resp=wp_remote_get($page_url,array('timeout'=>12,'redirection'=>3,'user-agent'=>'EMDO link audit'));
        if(is_wp_error($resp)) { echo 'PAGE_ERR=' . $page_url . '|' . $resp->get_error_message() . "\n"; continue; }
        $html=wp_remote_retrieve_body($resp);
        echo 'PAGE=' . $page_url . '|HTTP=' . wp_remote_retrieve_response_code($resp) . '|HAS_TOLE=' . (stripos($html,'Tolecarnes')!==false?'yes':'no') . "\n";
        if(preg_match_all('/href=["\']([^"\']*tolecarnes[^"\']*)["\']/i',$html,$m)) {
            foreach(array_unique($m[1]) as $href) echo 'TOLE_HREF=' . html_entity_decode($href,ENT_QUOTES) . "\n";
        }
    }

    // Inspect WCFM marketplace settings relevant to vendor/store permalinks.
    foreach(array('wcfmmp_settings','wcfm_options','wcfm_store_url') as $key) {
        $value=get_option($key,null);
        if($value!==null) {
            if(is_array($value)) {
                $filtered=array();
                array_walk_recursive($value,function($v,$k)use(&$filtered){ if(stripos((string)$k,'store')!==false || stripos((string)$k,'url')!==false || stripos((string)$k,'vendor')!==false) $filtered[$k]=$v; });
                echo 'OPTION=' . $key . '|' . wp_json_encode($filtered,JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) . "\n";
            } else echo 'OPTION=' . $key . '|' . (string)$value . "\n";
        }
    }
} catch(Throwable $e) {
    echo 'AUDIT_ERR=' . get_class($e) . ':' . $e->getMessage() . "\n";
}
echo "=== EMDO_TOLECARNES_LINK_AUDIT_END ===\n";
