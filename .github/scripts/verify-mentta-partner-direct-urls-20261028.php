<?php
/**
 * Read-only preflight/verify for direct review of SIX approved Mentta packs.
 * The four pending packs + previously imported Montjam ham must remain excluded.
 */
if (!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
$expected=[
 21814=>'pack-musee-mentta-pasaporte-salvaje',
 21818=>'pack-musee-mentta-safari-africano',
 21820=>'pack-musee-mentta-vuelta-al-mundo',
 21904=>'pack-cafe-especialidad-esencia-costa-rica-ikore',
 21906=>'pack-cafe-especialidad-ruta-cuatro-origenes-ikore',
 21908=>'pack-cafe-especialidad-coleccion-completa-ikore',
];
$excluded=emdo_mentta_exclusive_ids();
foreach($expected as $id=>$slug){
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='publish'||$p->get_slug()!==$slug||
    !in_array($id,$excluded,true)||$p->get_catalog_visibility()!=='visible'){
    fwrite(STDERR,"PARTNER_VIEW_PREFLIGHT_BAD_PRODUCT $id\n");exit(3);
 }
}
foreach([21816,21822,21824,21826] as $id) {
 $p=wc_get_product($id);
 if(!$p || $p->get_status()!=='draft' || !in_array($id,$excluded,true)){
  fwrite(STDERR,"PENDING_MUSEE_PACK_PROTECTION_FAILED $id\n");exit(4);
 }
}
$montjam=wc_get_product(17129);
if(!$montjam || !in_array(17129,$excluded,true) ||
   $montjam->get_catalog_visibility()!=='hidden'){
 fwrite(STDERR,"IMPORTED_MONTJAM_STATE_MISMATCH\n");exit(5);
}
if(function_exists('emdo_mentta_partner_view_products')){
 $map=emdo_mentta_partner_view_products();
 foreach($expected as $id=>$slug)if(($map[$slug]??null)!==$id) {
  fwrite(STDERR,"PARTNER_VIEW_ALLOWLIST_MISMATCH $id\n");exit(6);
 }
 if(count($map)!==6)exit(7);
}
echo 'MENTTA_PARTNER_VIEW_PREFLIGHT_OK '.wp_json_encode([
 'approved_six'=>array_keys($expected),'still_pending_four'=>[21816,21822,21824,21826],
 'original_hidden_montjam'=>17129,
 'allowlist_active'=>function_exists('emdo_mentta_partner_view_products')
 ],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
