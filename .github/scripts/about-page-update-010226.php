<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Workflow guard markers retained; this script is read-only.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_FLUENTCRM_PREWRITE_AUDIT_BEGIN ===\n";
try {
    $controller_class = '\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $campaign_class   = '\\FluentCrm\\App\\Models\\Campaign';
    $ref = new ReflectionClass( $controller_class );
    echo 'CONTROLLER_PARENT=' . ( $ref->getParentClass() ? $ref->getParentClass()->getName() : 'none' ) . "\n";
    $ctor = $ref->getConstructor();
    if ( $ctor ) {
        echo 'CONTROLLER_CTOR=' . $ctor->getDeclaringClass()->getName() . ':' . $ctor->getStartLine() . '-' . $ctor->getEndLine() . "\n";
        foreach ( $ctor->getParameters() as $p ) {
            echo 'CTOR_PARAM=' . $p->getName() . ':' . ( $p->hasType() ? (string) $p->getType() : 'untyped' ) . "\n";
        }
        $cfile = $ctor->getFileName();
        $clines = file( $cfile );
        for ( $i = max(1,$ctor->getStartLine()-5); $i <= min(count($clines),$ctor->getEndLine()+5); $i++ ) {
            echo 'CTOR_SOURCE_' . $i . '=' . rtrim($clines[$i-1]) . "\n";
        }
    }
    foreach ( array('duplicateCampaign','sendTestEmail','update') as $name ) {
        $m = $ref->getMethod($name);
        foreach ( $m->getParameters() as $p ) {
            echo 'METHOD_PARAM=' . $name . ':' . $p->getName() . ':' . ( $p->hasType() ? (string) $p->getType() : 'untyped' ) . "\n";
        }
    }

    $campaign = $campaign_class::findOrFail(9);
    echo 'SOURCE_BODY_BEGIN\n';
    echo (string) $campaign->email_body . "\n";
    echo "SOURCE_BODY_END\n";

    $users = get_users(array(
        'search' => '*Tolecarnes*',
        'search_columns' => array('user_login','user_nicename','display_name'),
        'number' => 20
    ));
    foreach ($users as $u) {
        echo 'TOLE_USER=' . wp_json_encode(array('ID'=>$u->ID,'login'=>$u->user_login,'nicename'=>$u->user_nicename,'display_name'=>$u->display_name), JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";
        foreach (array('wcfmmp_store_name','dokan_store_name','pv_shop_name','store_name') as $key) {
            $v = get_user_meta($u->ID,$key,true);
            if ($v !== '') echo 'TOLE_META=' . $key . ':' . (is_scalar($v)?$v:wp_json_encode($v)) . "\n";
        }
    }

    global $wpdb;
    $matches = $wpdb->get_results($wpdb->prepare(
        "SELECT ID, post_type, post_status, post_title, post_name, post_author FROM {$wpdb->posts} WHERE (post_title LIKE %s OR post_name LIKE %s OR post_content LIKE %s) ORDER BY ID DESC LIMIT 50",
        '%Tolecarnes%', '%tolecarnes%', '%Tolecarnes%'
    ), ARRAY_A);
    foreach ($matches as $row) echo 'TOLE_POST=' . wp_json_encode($row, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) . "\n";

    $candidates = array(
        home_url('/store/tolecarnes/'),
        home_url('/product-vendor/tolecarnes/'),
        home_url('/vendedor/tolecarnes/'),
        home_url('/?s=Tolecarnes')
    );
    foreach ($candidates as $url) {
        $resp = wp_remote_get($url, array('timeout'=>15,'redirection'=>3,'user-agent'=>'EMDO CRM audit'));
        if (is_wp_error($resp)) {
            echo 'URL_CHECK=' . $url . '|ERROR|' . $resp->get_error_message() . "\n";
        } else {
            $body = wp_remote_retrieve_body($resp);
            echo 'URL_CHECK=' . $url . '|HTTP=' . wp_remote_retrieve_response_code($resp) . '|FINAL=' . (isset($resp['http_response']) && method_exists($resp['http_response'],'get_response_object') ? '' : '') . '|HAS_TOLE=' . (stripos($body,'Tolecarnes')!==false?'yes':'no') . '|BYTES=' . strlen($body) . "\n";
        }
    }
} catch ( Throwable $e ) {
    echo 'PREWRITE_AUDIT_ERROR=' . get_class($e) . ':' . $e->getMessage() . "\n";
}
echo "=== EMDO_FLUENTCRM_PREWRITE_AUDIT_END ===\n";
