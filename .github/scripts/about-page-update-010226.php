<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
// Workflow guard markers retained; read-only.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_FINAL_PREWRITE_AUDIT_BEGIN ===\n";
try {
    $uid = 4507;
    foreach (array('wcfmmp_get_store_url','wcfm_get_vendor_store','wcfm_get_vendor_store_url','get_wcfm_vendor_store_url') as $fn) {
        echo 'WCFM_FN=' . $fn . ':' . (function_exists($fn)?'yes':'no') . "\n";
        if (function_exists($fn)) {
            try { $v = $fn($uid); echo 'WCFM_FN_RESULT=' . $fn . ':' . (is_scalar($v)?$v:wp_json_encode($v)) . "\n"; } catch (Throwable $e) { echo 'WCFM_FN_ERR=' . $fn . ':' . $e->getMessage() . "\n"; }
        }
    }
    if (isset($GLOBALS['WCFMmp']) && is_object($GLOBALS['WCFMmp'])) {
        $obj = $GLOBALS['WCFMmp'];
        echo 'WCFMMP_CLASS=' . get_class($obj) . "\n";
        if (isset($obj->wcfmmp_vendor) && is_object($obj->wcfmmp_vendor)) {
            echo 'WCFMMP_VENDOR_CLASS=' . get_class($obj->wcfmmp_vendor) . "\n";
            foreach (get_class_methods($obj->wcfmmp_vendor) as $m) {
                if (stripos($m,'store')!==false || stripos($m,'url')!==false) echo 'WCFMMP_VENDOR_METHOD=' . $m . "\n";
            }
        }
    }
    $candidate = home_url('/tienda/tolecarnes/');
    $resp = wp_remote_get($candidate,array('timeout'=>8,'redirection'=>2,'user-agent'=>'EMDO CRM audit'));
    echo 'TIENDA_CHECK=' . $candidate . '|HTTP=' . (is_wp_error($resp)?'ERR':wp_remote_retrieve_response_code($resp)) . '|HAS_TOLE=' . (!is_wp_error($resp)&&stripos(wp_remote_retrieve_body($resp),'Tolecarnes')!==false?'yes':'no') . "\n";

    $appClass='\\FluentCrm\\Framework\\Foundation\\App';
    $app=$appClass::getInstance();
    $request=$app['request'];
    echo 'REQUEST_CLASS=' . get_class($request) . "\n";
    foreach (get_class_methods($request) as $m) {
        if (preg_match('/^(merge|replace|set|all|except|only|input|put|add)$/i',$m)) echo 'REQUEST_METHOD=' . $m . "\n";
    }
    $rr=new ReflectionClass($request);
    $ctor=$rr->getConstructor();
    if($ctor){ echo 'REQUEST_CTOR=' . $ctor->getStartLine() . '-' . $ctor->getEndLine() . "\n"; foreach($ctor->getParameters() as $p) echo 'REQUEST_CTOR_PARAM=' . $p->getName() . ':' . ($p->hasType()?(string)$p->getType():'untyped') . "\n"; }
} catch(Throwable $e){ echo 'FINAL_PREWRITE_ERR=' . get_class($e) . ':' . $e->getMessage() . "\n"; }
echo "=== EMDO_FINAL_PREWRITE_AUDIT_END ===\n";
