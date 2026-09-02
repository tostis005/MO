<?php
if (!defined('ABSPATH')) exit;

function emdo_il_norm_path($url){
    $u = wp_parse_url(html_entity_decode((string)$url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if (!$u || empty($u['path'])) return null;
    $path = '/'.trim($u['path'],'/').'/';
    $path = preg_replace('#/+#','/',$path);
    return $path;
}
function emdo_il_links($html){
    preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1[^>]*>(.*?)<\/a>/isu',(string)$html,$m,PREG_SET_ORDER);
    $out=[];
    foreach($m as $x){
        $href=trim(html_entity_decode($x[2],ENT_QUOTES|ENT_HTML5,'UTF-8'));
        if($href===''||str_starts_with($href,'#')||str_starts_with($href,'mailto:')||str_starts_with($href,'tel:')||str_starts_with($href,'javascript:')) continue;
        $anchor=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags($x[3])));
        $out[]=['href'=>$href,'anchor'=>$anchor];
    }
    return $out;
}
function emdo_il_tokens($s){
    $s=remove_accents(mb_strtolower(wp_strip_all_tags((string)$s),'UTF-8'));
    preg_match_all('/[a-z0-9]+/u',$s,$m);
    $stop=array_fill_keys(['que','como','para','por','con','del','las','los','una','uno','unos','unas','sus','sin','mas','muy','hay','cada','entre','sobre','esta','este','estos','estas','guia','completa','completo','diferencias','tipos','principales','espana','espanoles','espanolas'],true);
    $out=[]; foreach($m[0] as $t){ if(strlen($t)>=3 && empty($stop[$t])) $out[$t]=true; }
    return array_keys($out);
}
function emdo_il_jaccard($a,$b){
    $a=array_fill_keys($a,true);$b=array_fill_keys($b,true);$i=0;$u=$a+$b;
    foreach($a as $k=>$_) if(isset($b[$k])) $i++;
    return count($u)?$i/count($u):0;
}

$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);
$by_id=[];$es_path_to_id=[];$en_path_to_id=[];
foreach($posts as $p){
    $es=emdo_il_norm_path(get_permalink($p->ID));
    $en_slug=(string)get_post_meta($p->ID,'_en_US_post_name',true);
    $en=$en_slug?'/en/'.trim($en_slug,'/').'/':null;
    $cats=wp_get_post_categories($p->ID);
    $by_id[$p->ID]=[
        'id'=>$p->ID,'title'=>$p->post_title,'slug'=>$p->post_name,'es_path'=>$es,'en_path'=>$en,
        'en_title'=>(string)get_post_meta($p->ID,'_en_US_post_title',true),'cats'=>$cats,
        'tokens'=>emdo_il_tokens($p->post_title),
        'in_es'=>[],'out_es'=>[],'in_en'=>[],'out_en'=>[],'cat_in_es'=>0,'cat_in_en'=>0
    ];
    if($es)$es_path_to_id[$es]=$p->ID;
    if($en)$en_path_to_id[$en]=$p->ID;
}

$unresolved=[];
foreach($posts as $p){
    foreach([['lang'=>'es','html'=>$p->post_content,'map'=>$es_path_to_id],['lang'=>'en','html'=>(string)get_post_meta($p->ID,'_en_US_post_content',true),'map'=>$en_path_to_id]] as $cfg){
        foreach(emdo_il_links($cfg['html']) as $ln){
            $href=$ln['href'];
            if(str_starts_with($href,'//')) $href='https:'.$href;
            $parts=wp_parse_url($href);
            $host=$parts['host']??'';
            if($host && !in_array(strtolower($host),['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)) continue;
            $path=emdo_il_norm_path($href);
            if(!$path) continue;
            if(isset($cfg['map'][$path])){
                $to=$cfg['map'][$path]; if($to===$p->ID) continue;
                $out_key='out_'.$cfg['lang'];$in_key='in_'.$cfg['lang'];
                $by_id[$p->ID][$out_key][$to]=($by_id[$p->ID][$out_key][$to]??0)+1;
                $by_id[$to][$in_key][$p->ID]=($by_id[$to][$in_key][$p->ID]??0)+1;
            } elseif(preg_match('#^/(?:en/)?[^/]+/$#',$path)) {
                $unresolved[$cfg['lang'].'|'.$path]=['lang'=>$cfg['lang'],'path'=>$path,'from'=>$p->ID,'anchor'=>$ln['anchor']];
            }
        }
    }
}

$cat_ids=[438,439,440,441,442,443,444,445,450];
foreach($cat_ids as $cid){
    $t=get_term($cid,'category'); if(!$t||is_wp_error($t)) continue;
    foreach([['lang'=>'es','html'=>$t->description,'map'=>$es_path_to_id],['lang'=>'en','html'=>(string)get_term_meta($cid,'_en_US_description',true),'map'=>$en_path_to_id]] as $cfg){
        foreach(emdo_il_links($cfg['html']) as $ln){
            $path=emdo_il_norm_path($ln['href']); if(!$path||!isset($cfg['map'][$path])) continue;
            $id=$cfg['map'][$path]; $k='cat_in_'.$cfg['lang']; $by_id[$id][$k]++;
        }
    }
}

$orphans_es=[];$orphans_en=[];$weak=[];$asym=[];
foreach($by_id as $id=>$r){
    $ies=count($r['in_es'])+$r['cat_in_es']; $ien=count($r['in_en'])+$r['cat_in_en'];
    $oes=count($r['out_es']); $oen=count($r['out_en']);
    if($ies===0)$orphans_es[]=$id;
    if($ien===0)$orphans_en[]=$id;
    if($ies<=1||$ien<=1)$weak[]=['id'=>$id,'title'=>$r['title'],'in_es'=>$ies,'in_en'=>$ien,'out_es'=>$oes,'out_en'=>$oen,'cats'=>$r['cats']];
    if(abs($oes-$oen)>=3||abs($ies-$ien)>=3)$asym[]=['id'=>$id,'title'=>$r['title'],'in_es'=>$ies,'in_en'=>$ien,'out_es'=>$oes,'out_en'=>$oen];
}

$suggestions=[];
$weak_ids=array_slice(array_column($weak,'id'),0,120);
foreach($weak_ids as $id){
    $src=$by_id[$id]; $cand=[];
    foreach($by_id as $tid=>$tr){
        if($tid===$id||isset($src['out_es'][$tid])) continue;
        $cat_overlap=count(array_intersect($src['cats'],$tr['cats'])); if(!$cat_overlap) continue;
        $j=emdo_il_jaccard($src['tokens'],$tr['tokens']);
        $score=$j + min(0.25,0.08*$cat_overlap) + (count($tr['in_es'])>=5?0.05:0);
        if($score<0.12) continue;
        $cand[]=['id'=>$tid,'title'=>$tr['title'],'score'=>round($score,3),'path'=>$tr['es_path']];
    }
    usort($cand,fn($a,$b)=>$b['score']<=>$a['score']);
    if($cand)$suggestions[$id]=array_slice($cand,0,4);
}

usort($weak,fn($a,$b)=>($a['in_es']+$a['in_en'])<=>($b['in_es']+$b['in_en']));
usort($asym,fn($a,$b)=>max(abs($b['out_es']-$b['out_en']),abs($b['in_es']-$b['in_en']))<=>max(abs($a['out_es']-$a['out_en']),abs($a['in_es']-$a['in_en'])));

$summary=[];
foreach($by_id as $id=>$r){
    $summary[]=['id'=>$id,'title'=>$r['title'],'slug'=>$r['slug'],'in_es'=>count($r['in_es'])+$r['cat_in_es'],'in_en'=>count($r['in_en'])+$r['cat_in_en'],'out_es'=>count($r['out_es']),'out_en'=>count($r['out_en']),'cat_in_es'=>$r['cat_in_es'],'cat_in_en'=>$r['cat_in_en']];
}
usort($summary,fn($a,$b)=>($a['in_es']+$a['in_en'])<=>($b['in_es']+$b['in_en']));

echo "EMDO_INTERNAL_LINK_AUDIT_BEGIN\n";
echo wp_json_encode([
 'counts'=>['published'=>count($posts),'orphans_es'=>count($orphans_es),'orphans_en'=>count($orphans_en),'weak'=>count($weak),'asymmetry'=>count($asym),'unresolved_single_segment'=>count($unresolved)],
 'orphans_es'=>$orphans_es,'orphans_en'=>$orphans_en,
 'weakest'=>array_slice($weak,0,80),'asymmetry'=>array_slice($asym,0,50),
 'suggestions'=>$suggestions,'lowest_inbound'=>array_slice($summary,0,100),
 'unresolved'=>array_slice(array_values($unresolved),0,100)
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_INTERNAL_LINK_AUDIT_END\n";
