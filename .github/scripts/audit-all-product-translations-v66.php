<?php
if ( ! defined('ABSPATH') ) { fwrite(STDERR,"WordPress required\n"); exit(2); }
global $wpdb;

function mdo_v66_plain($s): string {
    return trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(html_entity_decode((string)$s,ENT_QUOTES|ENT_HTML5,'UTF-8'))));
}
function mdo_v66_norm($s): string {
    $s=strtolower(remove_accents(mdo_v66_plain($s)));
    $s=preg_replace('/[^a-z0-9%]+/u',' ',$s);
    return trim(preg_replace('/\s+/',' ',$s));
}
function mdo_v66_store_name(int $uid): string {
    foreach(['wcfmmp_profile_settings','wcfm_profile_settings'] as $key){
        $v=get_user_meta($uid,$key,true);
        if(is_array($v)&&!empty($v['store_name'])) return mdo_v66_plain($v['store_name']);
    }
    $u=get_userdata($uid); return $u?mdo_v66_plain($u->display_name?:$u->user_login):'author-'.$uid;
}
function mdo_v66_spanish_hits(string $s): array {
    $plain=mdo_v66_plain($s); if($plain==='')return [];
    $patterns=[
        'descripción'=>'/\bdescripci[oó]n\b/iu','ingredientes'=>'/\bingredientes?\b/iu','conservación'=>'/\bconservaci[oó]n\b/iu',
        'modo de empleo'=>'/\bmodo\s+de\s+(?:empleo|uso|preparaci[oó]n)\b/iu','elaborado'=>'/\belaborad[oa]s?\b/iu',
        'procedencia'=>'/\bprocedencia\b/iu','recomendamos'=>'/\brecomendamos\b/iu','aproximadamente'=>'/\baproximadamente\b/iu',
        'envasado'=>'/\benvasad[oa]s?\b/iu','sin gluten'=>'/\bsin\s+gluten\b/iu','unidades'=>'/\bunidades\b/iu',
        'caja de'=>'/\bcaja\s+de\b/iu','pieza de'=>'/\bpieza\s+de\b/iu','peso aprox'=>'/\bpeso\s+(?:aprox|aproximado)\b/iu',
        'producto refrigerado'=>'/\bproducto\s+refrigerado\b/iu','mantener refrigerado'=>'/\bmantener\s+refrigerad[oa]\b/iu',
        'fecha de caducidad'=>'/\bfecha\s+de\s+caducidad\b/iu','consumo preferente'=>'/\bconsumo\s+preferente\b/iu',
        'una vez abierto'=>'/\buna\s+vez\s+abierto\b/iu','aceite de oliva virgen extra'=>'/\baceite\s+de\s+oliva\s+virgen\s+extra\b/iu',
        'carne de'=>'/\bcarne\s+de\b/iu','meses de curación'=>'/\bmeses?\s+de\s+curaci[oó]n\b/iu','cortado a máquina'=>'/\bcortad[oa]\s+a\s+m[aá]quina\b/iu',
        'envasado al vacío'=>'/\benvasad[oa]\s+al\s+vac[ií]o\b/iu','conservar entre'=>'/\bconservar\s+entre\b/iu'
    ];
    $hits=[];foreach($patterns as $label=>$re){if(preg_match($re,$plain))$hits[]=$label;}return $hits;
}
function mdo_v66_slug_hits(string $slug): array {
    $parts=preg_split('/-+/',strtolower(remove_accents($slug)));
    $bad=['producto','carne','carnes','pieza','piezas','caja','cajas','cortado','cortada','maquina','deshuesado','deshuesada','envasado','envasada','vacio','virutas','codillo','paleta','jamon','aceite','oliva','tradicional','filtrado','meses','lote','ternera','vaca','cerdo','hamburguesas'];
    return array_values(array_unique(array_intersect($parts,$bad)));
}
function mdo_v66_nums($s): array {
    $s=mdo_v66_plain($s);preg_match_all('/\d+(?:[.,]\d+)?/u',$s,$m);$o=[];
    foreach($m[0] as $x){$x=str_replace(',','.',$x);$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;
}
function mdo_v66_ecodes($s): array {
    $s=mdo_v66_plain($s);preg_match_all('/\bE\s*-?\s*\d+[A-Z]*\b/ui',$s,$m);$o=[];
    foreach($m[0] as $x){$x=preg_replace('/[^A-Z0-9]/','',strtoupper($x));$o[$x]=($o[$x]??0)+1;}ksort($o);return $o;
}

$target_ids=[4507=>'Tolecarnes',4508=>'Puente Robles',4509=>'El Catedrático'];
$out=[
 'generated_at'=>gmdate('c'),
 'summary'=>['catalog_products'=>0,'products_with_any_issue'=>0,'missing_translation_products'=>0,'spanish_residue_products'=>0,'attribute_issue_products'=>0,'integrity_issue_products'=>0,'routing_issue_products'=>0,'exact_copy_review_products'=>0,'by_status'=>[]],
 'targets'=>[], 'vendors'=>[], 'issues'=>[], 'exact_copy_review'=>[], 'products'=>[]
];
foreach($target_ids as $name)$out['targets'][$name]=['products'=>0,'issues'=>0,'missing'=>0,'spanish_residue'=>0,'attribute_issues'=>0,'integrity_issues'=>0,'routing_issues'=>0,'exact_copy_review'=>0,'by_status'=>[],'english_fields_ready'=>0,'staged_ready'=>0,'english_routing_on'=>0];

$rows=$wpdb->get_results("SELECT ID,post_author,post_status FROM {$wpdb->posts} WHERE post_type='product' AND post_status IN ('publish','draft','pending','private','future','archived') ORDER BY ID");
$out['summary']['catalog_products']=count($rows);

foreach($rows as $row){
    $id=(int)$row->ID;$p=get_post($id);if(!$p)continue;
    $uid=(int)$row->post_author;$vendor=$target_ids[$uid]??mdo_v66_store_name($uid);$target=$target_ids[$uid]??'';
    $product=function_exists('wc_get_product')?wc_get_product($id):null;
    $visibility=$product?(string)$product->get_catalog_visibility():'';
    $status=(string)$p->post_status;
    $out['summary']['by_status'][$status]=($out['summary']['by_status'][$status]??0)+1;

    $src=['title'=>(string)$p->post_title,'slug'=>(string)$p->post_name,'excerpt'=>(string)$p->post_excerpt,'content'=>(string)$p->post_content];
    $en=[
      'published'=>(string)get_post_meta($id,'_en_US_published',true),'ready'=>(string)get_post_meta($id,'_en_US_ready',true),
      'title'=>(string)get_post_meta($id,'_en_US_post_title',true),'slug'=>(string)get_post_meta($id,'_en_US_post_name',true),
      'excerpt'=>(string)get_post_meta($id,'_en_US_post_excerpt',true),'content'=>(string)get_post_meta($id,'_en_US_post_content',true)
    ];

    $missing=[];
    if(mdo_v66_plain($en['title'])==='')$missing[]='title';
    if(trim($en['slug'])==='')$missing[]='slug';
    if(mdo_v66_plain($src['excerpt'])!==''&&mdo_v66_plain($en['excerpt'])==='')$missing[]='excerpt';
    if(mdo_v66_plain($src['content'])!==''&&mdo_v66_plain($en['content'])==='')$missing[]='content';
    if(($uid===4508||$uid===4509)&&$en['ready']!=='1')$missing[]='prelaunch_ready_flag';

    $routing=[];
    if(($uid===4508||$uid===4509)&&$en['published']==='1')$routing[]='premature_english_routing';
    if($uid===4507&&$status==='publish'&&$en['published']!=='1')$routing[]='live_product_english_routing_off';

    $spanish=[];foreach(['title','excerpt','content'] as $f){$h=mdo_v66_spanish_hits($en[$f]);if($h)$spanish[$f]=$h;}
    $sh=mdo_v66_slug_hits($en['slug']);if($sh)$spanish['slug']=$sh;

    $src_all=$src['title'].' '.$src['excerpt'].' '.$src['content'];$en_all=$en['title'].' '.$en['excerpt'].' '.$en['content'];
    $integrity=[];
    if(mdo_v66_nums($src_all)!==mdo_v66_nums($en_all))$integrity[]='number_mismatch';
    if(mdo_v66_ecodes($src_all)!==mdo_v66_ecodes($en_all))$integrity[]='ecode_mismatch';

    $exact=[];foreach(['title','excerpt','content'] as $f){$a=mdo_v66_norm($src[$f]);$b=mdo_v66_norm($en[$f]);if($a!==''&&$a===$b){$words=count(array_filter(explode(' ',$a)));if(($f==='title'&&$words>=3)||($f!=='title'&&$words>=8))$exact[]=$f;}}

    $attribute_issues=[];
    if($product){foreach($product->get_attributes() as $attr){
        $an=(string)$attr->get_name();
        if($attr->is_taxonomy()&&taxonomy_exists($an)){
            foreach((array)$attr->get_options() as $tid){$term=get_term((int)$tid,$an);if(!$term||is_wp_error($term))continue;
                $tn=mdo_v66_plain((string)get_term_meta($term->term_id,'_en_US_name',true));$ts=trim((string)get_term_meta($term->term_id,'_en_US_slug',true));$tp=(string)get_term_meta($term->term_id,'_en_US_published',true);$ai=[];
                if($tn==='')$ai[]='missing_en_name';if($ts==='')$ai[]='missing_en_slug';if($tp!=='1')$ai[]='en_not_published';
                $h=mdo_v66_spanish_hits($tn);if($h)$ai[]='spanish_in_en_name:'.implode(',',$h);$h=mdo_v66_slug_hits($ts);if($h)$ai[]='spanish_in_en_slug:'.implode(',',$h);
                if($ai)$attribute_issues[]=['attribute'=>$an,'term_id'=>(int)$term->term_id,'native'=>$term->name,'english'=>$tn,'english_slug'=>$ts,'issues'=>$ai];
            }
        }else{
            $nh=mdo_v66_spanish_hits($an);$oh=[];foreach((array)$attr->get_options() as $opt){$h=mdo_v66_spanish_hits((string)$opt);if($h)$oh[]=['value'=>(string)$opt,'hits'=>$h];}
            if($nh||$oh)$attribute_issues[]=['attribute'=>$an,'custom'=>true,'name_hits'=>$nh,'option_hits'=>$oh];
        }
    }}

    $fields_ready=!$missing;$has_issue=(bool)($missing||$routing||$spanish||$integrity||$attribute_issues);
    $vk=$vendor?:'(unknown)';if(!isset($out['vendors'][$vk]))$out['vendors'][$vk]=['products'=>0,'issues'=>0,'missing'=>0,'spanish_residue'=>0,'attribute_issues'=>0,'integrity_issues'=>0,'routing_issues'=>0,'exact_copy_review'=>0,'by_status'=>[],'hidden'=>0];
    $v=&$out['vendors'][$vk];$v['products']++;$v['by_status'][$status]=($v['by_status'][$status]??0)+1;if($visibility==='hidden')$v['hidden']++;if($has_issue)$v['issues']++;if($missing)$v['missing']++;if($spanish)$v['spanish_residue']++;if($attribute_issues)$v['attribute_issues']++;if($integrity)$v['integrity_issues']++;if($routing)$v['routing_issues']++;if($exact)$v['exact_copy_review']++;unset($v);

    if($target){$t=&$out['targets'][$target];$t['products']++;$t['by_status'][$status]=($t['by_status'][$status]??0)+1;if($fields_ready)$t['english_fields_ready']++;if($en['ready']==='1')$t['staged_ready']++;if($en['published']==='1')$t['english_routing_on']++;if($has_issue)$t['issues']++;if($missing)$t['missing']++;if($spanish)$t['spanish_residue']++;if($attribute_issues)$t['attribute_issues']++;if($integrity)$t['integrity_issues']++;if($routing)$t['routing_issues']++;if($exact)$t['exact_copy_review']++;unset($t);}

    if($has_issue){$out['summary']['products_with_any_issue']++;if($missing)$out['summary']['missing_translation_products']++;if($spanish)$out['summary']['spanish_residue_products']++;if($attribute_issues)$out['summary']['attribute_issue_products']++;if($integrity)$out['summary']['integrity_issue_products']++;if($routing)$out['summary']['routing_issue_products']++;
        $out['issues'][]=['id'=>$id,'author_id'=>$uid,'vendor'=>$vendor,'target'=>$target,'status'=>$status,'visibility'=>$visibility,'native_title'=>mdo_v66_plain($src['title']),'english_title'=>mdo_v66_plain($en['title']),'native_slug'=>$src['slug'],'english_slug'=>$en['slug'],'en_US_published'=>$en['published'],'en_US_ready'=>$en['ready'],'missing'=>$missing,'routing'=>$routing,'spanish'=>$spanish,'integrity'=>$integrity,'attribute_issues'=>$attribute_issues];
    }
    if($exact){$out['summary']['exact_copy_review_products']++;$out['exact_copy_review'][]=['id'=>$id,'author_id'=>$uid,'vendor'=>$vendor,'target'=>$target,'status'=>$status,'visibility'=>$visibility,'title'=>mdo_v66_plain($src['title']),'fields'=>$exact,'source_sample'=>mb_substr(mdo_v66_plain($src['content']),0,500),'english_sample'=>mb_substr(mdo_v66_plain($en['content']),0,500)];}
    $out['products'][]=['id'=>$id,'author_id'=>$uid,'vendor'=>$vendor,'target'=>$target,'status'=>$status,'visibility'=>$visibility,'native_title'=>mdo_v66_plain($src['title']),'english_title'=>mdo_v66_plain($en['title']),'english_slug'=>$en['slug'],'en_US_published'=>$en['published'],'en_US_ready'=>$en['ready'],'issues'=>$has_issue?1:0,'exact_review'=>$exact];
}
foreach($out['vendors'] as &$v){ksort($v['by_status']);}unset($v);foreach($out['targets'] as &$t){ksort($t['by_status']);}unset($t);ksort($out['summary']['by_status']);ksort($out['vendors'],SORT_NATURAL|SORT_FLAG_CASE);
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
