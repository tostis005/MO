<?php
if ( ! defined('ABSPATH') ) { fwrite(STDERR,"WordPress required\n"); exit(2); }
global $wpdb;
$targets=[4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático'];
$expected=[4507=>39,4508=>106,4509=>95];
$bad=[];$summary=[];$slugs=[];

function mdov67_plain($s){return trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(html_entity_decode((string)$s,ENT_QUOTES|ENT_HTML5,'UTF-8'))));}
function mdov67_norm($s){$s=strtolower(remove_accents(mdov67_plain($s)));$s=preg_replace('/[^a-z0-9%]+/',' ',$s);return trim(preg_replace('/\s+/',' ',$s));}
function mdov67_num_key($x){$x=str_replace(',','.',(string)$x);if(strpos($x,'.')!==false)$x=rtrim(rtrim($x,'0'),'.');return $x===''?'0':$x;}
function mdov67_nums($s){preg_match_all('/\d+(?:[.,]\d+)?/u',mdov67_plain($s),$m);$o=[];foreach($m[0] as $x){$x=mdov67_num_key($x);$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
function mdov67_ecodes($s){preg_match_all('/\bE\s*-?\s*\d+[A-Z]*\b/ui',mdov67_plain($s),$m);$o=[];foreach($m[0] as $x){$x=preg_replace('/[^A-Z0-9]/','',strtoupper($x));$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;}
function mdov67_spanish($s){
    $p=mdov67_plain($s);if($p==='')return [];
    $rx=[
      'descripcion'=>'/\bdescripci[oó]n\b/iu','ingredientes'=>'/\bingredientes?\b/iu','conservacion'=>'/\bconservaci[oó]n\b/iu',
      'modo'=>'/\bmodo\s+de\s+(?:empleo|uso|preparaci[oó]n)\b/iu','recomendamos'=>'/\brecomendamos\b/iu','aproximadamente'=>'/\baproximadamente\b/iu',
      'envasado'=>'/\benvasad[oa]s?\b/iu','unidades'=>'/\bunidades\b/iu','pieza de'=>'/\bpieza\s+de\b/iu','peso aprox'=>'/\bpeso\s+(?:aprox|aproximado)\b/iu',
      'producto refrigerado'=>'/\bproducto\s+refrigerado\b/iu','mantener refrigerado'=>'/\bmantener\s+refrigerad[oa]\b/iu','fecha caducidad'=>'/\bfecha\s+de\s+caducidad\b/iu',
      'consumo preferente'=>'/\bconsumo\s+preferente\b/iu','una vez abierto'=>'/\buna\s+vez\s+abierto\b/iu','aceite oliva'=>'/\baceite\s+de\s+oliva\s+virgen\s+extra\b/iu',
      'carne de'=>'/\bcarne\s+de\b/iu','meses curacion'=>'/\bmeses?\s+de\s+curaci[oó]n\b/iu','cortado maquina'=>'/\bcortad[oa]\s+a\s+m[aá]quina\b/iu',
      'envasado vacio'=>'/\benvasad[oa]\s+al\s+vac[ií]o\b/iu','conservar entre'=>'/\bconservar\s+entre\b/iu','listos para'=>'/\blistos?\s+para\b/iu','precio por'=>'/\bprecio\s+por\b/iu',
      'corte pegado'=>'/\bcorte\s+pegado\b/iu','guisar'=>'/\bguisar\b/iu','plancha'=>'/\bplancha\b/iu','codillo'=>'/\bcodillo\b/iu'
    ];$h=[];foreach($rx as $k=>$r)if(preg_match($r,$p))$h[]=$k;return $h;
}

foreach($targets as $aid=>$vendor){
    $ids=array_map('intval',$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_author=%d AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID",$aid)));
    $s=['total'=>count($ids),'fields_ready'=>0,'routing_on'=>0,'ready'=>0,'by_status'=>[],'issues'=>0];
    if(count($ids)!==$expected[$aid])$bad[]=['vendor'=>$vendor,'reason'=>'unexpected_count','got'=>count($ids),'expected'=>$expected[$aid]];
    foreach($ids as $id){
        $p=get_post($id);$s['by_status'][$p->post_status]=($s['by_status'][$p->post_status]??0)+1;
        $en=['title'=>(string)get_post_meta($id,'_en_US_post_title',true),'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),'content'=>(string)get_post_meta($id,'_en_US_post_content',true)];
        $gb=['title'=>(string)get_post_meta($id,'_en_GB_post_title',true),'slug'=>(string)get_post_meta($id,'_en_GB_post_name',true),'excerpt'=>(string)get_post_meta($id,'_en_GB_post_excerpt',true),'content'=>(string)get_post_meta($id,'_en_GB_post_content',true)];
        $pub=(string)get_post_meta($id,'_en_US_published',true);$ready=(string)get_post_meta($id,'_en_US_ready',true);
        if($pub==='1')$s['routing_on']++;if($ready==='1')$s['ready']++;
        $issues=[];
        if(mdov67_plain($en['title'])===''||sanitize_title($en['slug'])==='')$issues[]='missing_title_or_slug';
        if(mdov67_plain($p->post_excerpt)!==''&&mdov67_plain($en['excerpt'])==='')$issues[]='missing_excerpt';
        if(mdov67_plain($p->post_content)!==''&&mdov67_plain($en['content'])==='')$issues[]='missing_content';
        if(mdov67_plain($en['title'])!==mdov67_plain($gb['title'])||sanitize_title($en['slug'])!==sanitize_title($gb['slug'])||mdov67_plain($en['excerpt'])!==mdov67_plain($gb['excerpt'])||mdov67_plain($en['content'])!==mdov67_plain($gb['content']))$issues[]='en_US_en_GB_mismatch';
        if($aid===4507&&$pub!=='1')$issues[]='tolecarnes_routing_off';
        if($aid!==4507&&($pub==='1'||$ready!=='1'))$issues[]='prelaunch_state_wrong';
        $src=$p->post_title.' '.$p->post_excerpt.' '.$p->post_content;$dst=$en['title'].' '.$en['excerpt'].' '.$en['content'];
        if(mdov67_nums($src)!==mdov67_nums($dst))$issues[]='number_mismatch';
        if(mdov67_ecodes($src)!==mdov67_ecodes($dst))$issues[]='ecode_mismatch';
        $sp=mdov67_spanish($dst);if($sp)$issues[]='spanish:'.implode(',',$sp);
        foreach(['title','excerpt','content'] as $f){$a=mdov67_norm($p->{'post_'.$f}??'');$b=mdov67_norm($en[$f]);$words=count(array_filter(explode(' ',$a)));if($a!==''&&$a===$b&&(($f==='title'&&$words>=4)||($f!=='title'&&$words>=7)))$issues[]='exact_spanish_copy_'.$f;}
        $slug=sanitize_title($en['slug']);if($slug!=='')$slugs[$slug][]=$id;

        $product=function_exists('wc_get_product')?wc_get_product($id):null;
        if($product){foreach($product->get_attributes() as $attr){if($attr->is_taxonomy())continue;$label=(string)$attr->get_name();if(function_exists('mdoea_stored_label_en_010263'))$label=mdoea_stored_label_en_010263($label);$lh=mdov67_spanish($label);if($lh)$issues[]='custom_attribute_label_spanish:'.implode(',',$lh);foreach((array)$attr->get_options() as $opt){$render=(string)$opt;if(function_exists('mdoea_translate_custom_attribute_value_010263'))$render=mdoea_translate_custom_attribute_value_010263($render);$vh=mdov67_spanish($render);if($vh)$issues[]='custom_attribute_value_spanish:'.implode(',',$vh);}}}
        if(!$issues)$s['fields_ready']++;else{$s['issues']++;$bad[]=['id'=>$id,'vendor'=>$vendor,'title'=>mdov67_plain($p->post_title),'issues'=>array_values(array_unique($issues))];}
    }
    ksort($s['by_status']);$summary[$vendor]=$s;
}
foreach($slugs as $slug=>$ids)if(count($ids)>1)$bad[]=['reason'=>'duplicate_english_slug','slug'=>$slug,'ids'=>$ids];

/* Specific regression assertions for repaired edge cases. */
$expect=[
  11095=>['_en_US_post_content'=>'A cut adjacent to the skirt steak, sliced into small steaks and ready for the griddle. Price per kg.'],
  11129=>['_en_US_post_content'=>'A cut adjacent to the skirt steak, diced into ragout pieces and ready for stewing. Price per kg.'],
  11865=>['_en_US_post_title'=>'Cured ham hock','_en_US_post_name'=>'cured-ham-hock-puente-robles'],
  11964=>['_en_US_post_title'=>'Acorn-fed shoulder ham bundle','_en_US_post_name'=>'acorn-fed-shoulder-ham-bundle-puente-robles'],
  12631=>['_en_US_post_title'=>'Cured ham hock','_en_US_post_name'=>'cured-ham-hock-el-catedratico'],
];
foreach($expect as $id=>$pairs)foreach($pairs as $key=>$value)if((string)get_post_meta($id,$key,true)!==$value)$bad[]=['id'=>$id,'reason'=>'regression_assertion','key'=>$key];
if(!function_exists('mdoea_translate_custom_attribute_value_010263')||mdoea_translate_custom_attribute_value_010263('pieza de 0.600 kg')!=='0.600 kg piece'||mdoea_translate_custom_attribute_value_010263('pieza de 0.500 kg')!=='0.500 kg piece')$bad[]=['id'=>11964,'reason'=>'custom_attribute_runtime_translation_missing'];

echo 'SUMMARY '.wp_json_encode($summary,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
if($bad){echo 'BAD '.wp_json_encode($bad,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";exit(40);}
if(($summary['Tolecarnes']['routing_on']??0)!==39||($summary['Puente Robles']['routing_on']??0)!==0||($summary['El Catedrático']['routing_on']??0)!==0)exit(41);
if(($summary['Puente Robles']['ready']??0)!==106||($summary['El Catedrático']['ready']??0)!==95)exit(42);
echo "PRIORITY_PRODUCT_TRANSLATIONS_V67_OK\n";
