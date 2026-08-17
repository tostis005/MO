<?php
if ( ! defined('ABSPATH') ) { exit(1); }
global $wpdb;
$targets=[4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático'];
$summary=[];$bad=[];$slugs=[];
$strong_spanish='/\b(para|desde|hasta|que|los|las|una|unos|unas|producto|almacenamiento|consumo|ingredientes|conservaci[oó]n|env[ií]o|peso|cerdo|curaci[oó]n|deshuesad[oa]|cortad[oa]|piezas|sobres|recomendamos|alimentados|lugar|meses)\b/ui';
function mdo_num_key($x){
    $x=str_replace(',','.',(string)$x);
    if(strpos($x,'.')!==false){$x=rtrim(rtrim($x,'0'),'.');}
    if($x==='')$x='0';
    if($x[0]==='.' )$x='0'.$x;
    return $x;
}
function mdo_nums_semantic($s){
    $s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');
    preg_match_all('/\d+(?:[.,]\d+)?/u',$s,$m);$out=[];
    foreach($m[0] as $x){$x=mdo_num_key($x);$out[$x]=($out[$x]??0)+1;}ksort($out);return $out;
}
function mdo_ecodes_semantic($s){
    $s=html_entity_decode(wp_strip_all_tags((string)$s),ENT_QUOTES|ENT_HTML5,'UTF-8');
    preg_match_all('/\bE\s*-?\s*\d+[A-Z]*\b/ui',$s,$m);$out=[];
    foreach($m[0] as $x){$x=preg_replace('/[^A-Z0-9]/','',strtoupper($x));$out[$x]=($out[$x]??0)+1;}ksort($out);return $out;
}
foreach($targets as $aid=>$vendor){
    $ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$aid));
    $s=['total'=>0,'english_fields_ready'=>0,'english_routing_on'=>0,'staged_ready'=>0,'by_status'=>[],'spanish_remnant_products'=>0];
    foreach($ids as $id){
        $p=get_post((int)$id);$s['total']++;$s['by_status'][$p->post_status]=($s['by_status'][$p->post_status]??0)+1;
        $title=(string)get_post_meta($id,'_en_US_post_title',true);
        $content=(string)get_post_meta($id,'_en_US_post_content',true);
        $excerpt=(string)get_post_meta($id,'_en_US_post_excerpt',true);
        $slug=sanitize_title((string)get_post_meta($id,'_en_US_post_name',true));
        $pub=(string)get_post_meta($id,'_en_US_published',true)==='1';$ready=(string)get_post_meta($id,'_en_US_ready',true)==='1';
        $fields=trim(wp_strip_all_tags($title))!==''&&$slug!==''&&(trim(wp_strip_all_tags($p->post_content))===''||trim(wp_strip_all_tags($content))!=='');
        if($fields)$s['english_fields_ready']++;else $bad[]=['id'=>(int)$id,'vendor'=>$vendor,'reason'=>'missing_field'];
        if($pub)$s['english_routing_on']++;if($ready)$s['staged_ready']++;
        if($slug!==''){$slugs[$slug][]=(int)$id;}
        if($aid!==4507 && $pub)$bad[]=['id'=>(int)$id,'vendor'=>$vendor,'reason'=>'premature_routing'];
        $src=$p->post_title.' '.$p->post_content.' '.$p->post_excerpt;
        $dst=$title.' '.$content.' '.$excerpt;
        if(mdo_nums_semantic($src)!==mdo_nums_semantic($dst))$bad[]=['id'=>(int)$id,'vendor'=>$vendor,'reason'=>'number_mismatch','src'=>mdo_nums_semantic($src),'dst'=>mdo_nums_semantic($dst)];
        if(mdo_ecodes_semantic($src)!==mdo_ecodes_semantic($dst))$bad[]=['id'=>(int)$id,'vendor'=>$vendor,'reason'=>'ecode_mismatch'];
        $visible=html_entity_decode(wp_strip_all_tags($dst),ENT_QUOTES|ENT_HTML5,'UTF-8');
        preg_match_all($strong_spanish,$visible,$m);
        if(count($m[0])>12){$s['spanish_remnant_products']++;$bad[]=['id'=>(int)$id,'vendor'=>$vendor,'reason'=>'spanish_remnants','count'=>count($m[0])];}
    }
    ksort($s['by_status']);$summary[$vendor]=$s;
}
foreach($slugs as $slug=>$ids){if(count($ids)>1)$bad[]=['slug'=>$slug,'ids'=>$ids,'reason'=>'duplicate_english_slug'];}
echo 'VERIFY '.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";
if($bad){echo 'BAD '.wp_json_encode($bad,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT)."\n";exit(40);}
if(($summary['Tolecarnes']['total']??0)!==39 || ($summary['Tolecarnes']['english_fields_ready']??0)!==39 || ($summary['Tolecarnes']['english_routing_on']??0)!==39){exit(41);}
if(($summary['Puente Robles']['total']??0)!==106 || ($summary['Puente Robles']['english_fields_ready']??0)!==106 || ($summary['Puente Robles']['english_routing_on']??0)!==0 || ($summary['Puente Robles']['staged_ready']??0)!==106){exit(42);}
if(($summary['El Catedrático']['total']??0)!==95 || ($summary['El Catedrático']['english_fields_ready']??0)!==95 || ($summary['El Catedrático']['english_routing_on']??0)!==0 || ($summary['El Catedrático']['staged_ready']??0)!==95){exit(43);}
if(($summary['Tolecarnes']['by_status']['draft']??0)!==1 || ($summary['Tolecarnes']['by_status']['publish']??0)!==38 || ($summary['Puente Robles']['by_status']['publish']??0)!==106 || ($summary['El Catedrático']['by_status']['publish']??0)!==95){exit(44);}
echo "VERIFY_OK\n";
