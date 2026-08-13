<?php
/**
 * Read-only final production audit for the intended 265-product catalog.
 * Mixed packs are validated from their assigned family metadata, but shorthand
 * pack titles are not used to infer a single race/feed value.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$vendors = array(
    3    => array( 'name'=>'1957',               'statuses'=>array('publish') ),
    6    => array( 'name'=>'Hidalgo de la Jara', 'statuses'=>array('publish') ),
    4507 => array( 'name'=>'Tolecarnes',         'statuses'=>array('publish') ),
    4508 => array( 'name'=>'Puente Robles',      'statuses'=>array('publish','archived') ),
    4509 => array( 'name'=>'El Catedrático',     'statuses'=>array('publish','archived') ),
);
function emdo_265_norm($v){$v=remove_accents(strtolower(html_entity_decode(wp_strip_all_tags((string)$v),ENT_QUOTES|ENT_HTML5,'UTF-8')));return trim((string)preg_replace('/\s+/u',' ',$v));}
function emdo_265_terms($id,$tax){if(!taxonomy_exists($tax))return array();$n=wp_get_object_terms((int)$id,$tax,array('fields'=>'names'));if(is_wp_error($n))return array();$n=array_values(array_unique(array_map('strval',(array)$n)));sort($n);return $n;}
function emdo_265_cats($id){$n=wp_get_post_terms((int)$id,'product_cat',array('fields'=>'slugs'));if(is_wp_error($n))return array();$n=array_values(array_unique(array_map('strval',(array)$n)));sort($n);return $n;}
function emdo_265_has($a,$v){return in_array((string)$v,array_map('strval',(array)$a),true);}
function emdo_265_race($t){foreach(array(100,75,50) as $p){if(preg_match('/\b'.$p.'\s*%\s*(?:raza\s+)?iberic[oa]s?\b/u',$t)||preg_match('/\biberic[oa]s?\s*(?:de\s+)?(?:raza\s+)?'.$p.'\s*%\b/u',$t))return $p.'% ibérico';}return '';}
function emdo_265_feed($t){if(preg_match('/\bcebo\s+(?:de\s+)?campo\b/u',$t))return 'Cebo de campo';if(preg_match('/\bbellota\b/u',$t))return 'Bellota';if(preg_match('/\bcebo\b/u',$t))return 'Cebo';return '';}
function emdo_265_piece($t){if(preg_match('/\bpaleta(?:s)?\b/u',$t))return 'Paleta';if(preg_match('/\bjamon(?:es)?\b/u',$t))return 'Jamón';return '';}
function emdo_265_cured_type($t){foreach(array('Lomito'=>'/\blomito\b/u','Chorizo'=>'/\bchorizos?\b/u','Salchichón'=>'/\bsalchichon(?:es)?\b/u','Morcón'=>'/\bmorcon(?:es)?\b/u','Sobrasada'=>'/\bsobrasadas?\b/u','Cecina'=>'/\bcecinas?\b/u','Lomo'=>'/\blomo\b/u') as $n=>$p){if(preg_match($p,$t))return $n;}return '';}

$errors=array();$warnings=array();$summary=array();$checked=0;$status_counts=array();
foreach($vendors as $author=>$cfg){
    $settings=get_user_meta($author,'wcfmmp_profile_settings',true);
    if(!is_array($settings)||(string)($settings['store_name']??'')!==$cfg['name'])throw new RuntimeException('Vendor identity mismatch '.$author);
    $ph=implode(',',array_fill(0,count($cfg['statuses']),'%s'));$params=array_merge(array($author),$cfg['statuses']);
    $rows=$wpdb->get_results($wpdb->prepare("SELECT ID,post_status FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ($ph) ORDER BY ID",$params),ARRAY_A);
    $ve=0;$vw=0;$uncat=0;
    foreach((array)$rows as $row){
        $id=(int)$row['ID'];$status=(string)$row['post_status'];$p=wc_get_product($id);
        if(!$p instanceof WC_Product){$errors[]=array('id'=>$id,'vendor'=>$cfg['name'],'errors'=>array('WooCommerce product unavailable'));++$ve;continue;}
        ++$checked;$status_counts[$status]=(int)($status_counts[$status]??0)+1;
        $title_raw=(string)$p->get_name('edit');$title=emdo_265_norm($title_raw);$cats=emdo_265_cats($id);
        $a=array(
            'tipo-pieza'=>emdo_265_terms($id,'pa_tipo-pieza'),'calidad'=>emdo_265_terms($id,'pa_calidad'),'raza'=>emdo_265_terms($id,'pa_raza-iberica'),
            'alimentacion'=>emdo_265_terms($id,'pa_alimentacion'),'con-dop'=>emdo_265_terms($id,'pa_con-dop'),'dop'=>emdo_265_terms($id,'pa_dop'),
            'origen'=>emdo_265_terms($id,'pa_origen'),'preparacion'=>emdo_265_terms($id,'pa_preparacion'),'curacion'=>emdo_265_terms($id,'pa_curacion'),
            'productor'=>emdo_265_terms($id,'pa_productor'),'tipo-producto'=>emdo_265_terms($id,'pa_tipo-producto'),
        );
        $pe=array();$pw=array();
        if(!$cats||emdo_265_has($cats,'sin-categorizar')||emdo_265_has($cats,'uncategorized')){$pe[]='missing/default category';++$uncat;}
        if('1957'===$cfg['name']&&$cats!==array('aceites'))$pe[]='1957 product must be Aceites';
        if('Tolecarnes'===$cfg['name']&&$cats!==array('carnes'))$pe[]='Tolecarnes product must be Carnes';
        $ham=emdo_265_has($cats,'jamones-paletas')||emdo_265_has($cats,'jamones-y-paletas');
        $cured=emdo_265_has($cats,'embutidos-y-curados');$adobado=emdo_265_has($cats,'adobados');$accessory=emdo_265_has($cats,'accesorios');$pack=emdo_265_has($cats,'packs-y-lotes');
        if($ham){
            $piece=emdo_265_piece($title);if(!$a['tipo-pieza'])$pe[]='ham missing tipo-pieza';elseif($piece&&!emdo_265_has($a['tipo-pieza'],$piece))$pe[]='ham wrong tipo-pieza expected '.$piece;
            if(!$a['calidad'])$pe[]='ham missing calidad';if(!$a['con-dop'])$pe[]='ham missing con-dop';if(count($a['con-dop'])>1)$pe[]='ham multiple con-dop values';
            if(!$a['origen'])$pe[]='ham missing origen';if(!$a['preparacion'])$pe[]='ham missing preparacion';if(!$a['productor']||!emdo_265_has($a['productor'],$cfg['name']))$pe[]='ham missing/wrong productor';
            if(!$a['curacion'])$pw[]='ham missing curacion';
            if(!$pack){$r=emdo_265_race($title);if($r&&!emdo_265_has($a['raza'],$r))$pe[]='ham race mismatch expected '.$r;$f=emdo_265_feed($title);if($f&&!emdo_265_has($a['alimentacion'],$f))$pe[]='ham feed mismatch expected '.$f;}
            if(preg_match('/\bdop\b/u',$title)){if(!emdo_265_has($a['con-dop'],'Sí'))$pe[]='title says DOP but con-dop is not Sí';if(!$a['dop'])$pe[]='title says DOP but dop term missing';}
        }
        if($cured){
            $type=emdo_265_cured_type($title);if(!$a['tipo-producto'])$pe[]='cured missing tipo-producto';elseif(!$pack&&$type&&!emdo_265_has($a['tipo-producto'],$type))$pe[]='cured type mismatch expected '.$type;
            if(!$a['preparacion'])$pe[]='cured missing preparacion';if(!$a['productor']||!emdo_265_has($a['productor'],$cfg['name']))$pe[]='cured missing/wrong productor';
            if(!$pack){$r=emdo_265_race($title);if($r&&!emdo_265_has($a['raza'],$r))$pe[]='cured race mismatch expected '.$r;$f=emdo_265_feed($title);if($f&&!emdo_265_has($a['alimentacion'],$f))$pe[]='cured feed mismatch expected '.$f;}
        }
        if($adobado&&!$a['tipo-producto'])$pe[]='adobado missing tipo-producto';if($accessory&&!$a['tipo-producto'])$pe[]='accessory missing tipo-producto';
        if($pe){++$ve;$errors[]=array('id'=>$id,'vendor'=>$cfg['name'],'status'=>$status,'title'=>$title_raw,'categories'=>$cats,'errors'=>$pe,'attributes'=>array_filter($a));}
        if($pw){++$vw;$warnings[]=array('id'=>$id,'vendor'=>$cfg['name'],'status'=>$status,'title'=>$title_raw,'categories'=>$cats,'warnings'=>$pw,'attributes'=>array_filter($a));}
    }
    $summary[$cfg['name']]=array('checked'=>count($rows),'errors'=>$ve,'warnings'=>$vw,'uncategorized'=>$uncat);
}
if(265!==$checked)throw new RuntimeException('Expected exactly 265 intended products, checked '.$checked);
foreach($errors as $r)echo 'CATALOG_265_ERROR '.wp_json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
foreach($warnings as $r)echo 'CATALOG_265_WARNING '.wp_json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo 'CATALOG_265_SUMMARY '.wp_json_encode(array('checked'=>$checked,'status_counts'=>$status_counts,'error_count'=>count($errors),'warning_count'=>count($warnings),'product_category_count'=>(int)wp_count_terms(array('taxonomy'=>'product_cat','hide_empty'=>false)),'vendors'=>$summary),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
