<?php
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
$slugs = array('aceites','jamones-paletas','embutidos-y-curados','packs-y-lotes','carnes','conservas','hortalizas-verduras','legumbres');
function emdo_cat_audit_plain($v): string {
    $v=(string)$v; $charset=get_bloginfo('charset')?:'UTF-8';
    for($i=0;$i<2;$i++){ $d=html_entity_decode($v,ENT_QUOTES|ENT_HTML5,$charset); if($d===$v)break; $v=$d; }
    return trim(preg_replace('/\s+/u',' ',wp_strip_all_tags($v)));
}
echo "EMDO CATEGORY DESCRIPTION CONTENT AUDIT 2026-08-21\n";
foreach($slugs as $slug){
    $t=get_term_by('slug',$slug,'product_cat');
    if(!$t instanceof WP_Term){ echo 'CATEGORY='.wp_json_encode(array('slug'=>$slug,'missing'=>true))."\n"; continue; }
    $es=emdo_cat_audit_plain($t->description);
    $en=emdo_cat_audit_plain(get_term_meta($t->term_id,'_en_US_description',true));
    echo 'CATEGORY='.wp_json_encode(array(
        'id'=>(int)$t->term_id,'slug'=>$slug,'name'=>$t->name,'count'=>(int)$t->count,
        'es'=>$es,'en'=>$en,'en_published'=>(string)get_term_meta($t->term_id,'_en_US_published',true),
    ),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
