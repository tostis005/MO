<?php
/** Read-only global blog audit: publication, Falang EN, duplicates, overlap, redirects and sitemap. */
if (!defined('ABSPATH')) { exit; }

function emdo_g_norm($s){
    $s=wp_strip_all_tags((string)$s);
    $s=remove_accents(mb_strtolower($s,'UTF-8'));
    $s=preg_replace('/[^a-z0-9]+/u',' ',$s);
    return trim(preg_replace('/\s+/',' ',$s));
}
function emdo_g_words($s){
    preg_match_all("/[\\p{L}\\p{N}]+(?:[’'\\-][\\p{L}\\p{N}]+)*/u",wp_strip_all_tags((string)$s),$m);
    return count($m[0]);
}
function emdo_g_tokens($s){
    static $stop=null;
    if($stop===null){
        $raw='que de la el los las un una unos unas y o e en del al por para con sin sobre como como se su sus es son mas muy no si lo le les ya entre desde hasta este esta estos estas ese esa esos esas hay cuando donde cual cuales porque puede pueden tambien cada todo toda todos todas mismo misma mismos mismas otro otra otros otras hacer hace qué cómo por qué what the a an and or of in on for to from with without is are be can how why this that these those its their more most also each same other into when where which';
        $stop=array_fill_keys(preg_split('/\s+/',emdo_g_norm($raw)) ,1);
    }
    $txt=emdo_g_norm($s); if($txt==='') return array();
    $out=array();
    foreach(explode(' ',$txt) as $w){ if(strlen($w)<3 || isset($stop[$w])) continue; $out[$w]=($out[$w]??0)+1; }
    return $out;
}
function emdo_g_jaccard($a,$b){
    $aa=array_fill_keys(array_keys(emdo_g_tokens($a)),1); $bb=array_fill_keys(array_keys(emdo_g_tokens($b)),1);
    if(!$aa||!$bb) return 0.0;
    $i=count(array_intersect_key($aa,$bb)); $u=count($aa)+count($bb)-$i; return $u?($i/$u):0.0;
}
function emdo_g_cos($a,$b){
    if(!$a||!$b) return 0.0; $dot=0.0; $na=0.0; $nb=0.0;
    foreach($a as $k=>$v){ $na+=$v*$v; if(isset($b[$k])) $dot+=$v*$b[$k]; }
    foreach($b as $v) $nb+=$v*$v;
    return ($na>0&&$nb>0)?$dot/(sqrt($na)*sqrt($nb)):0.0;
}
function emdo_g_headings($html){
    preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/isu',(string)$html,$m);
    return array_values(array_filter(array_map(fn($x)=>trim(wp_strip_all_tags($x)),$m[1])));
}
function emdo_g_xml_locs($xml){
    $locs=array(); if(!is_string($xml)||$xml==='') return $locs;
    if(preg_match_all('/<loc>\s*(.*?)\s*<\/loc>/is',$xml,$m)) foreach($m[1] as $u) $locs[]=html_entity_decode(trim(strip_tags($u)),ENT_QUOTES|ENT_XML1,'UTF-8');
    return array_values(array_unique($locs));
}
function emdo_g_fetch_sitemap_tree($root){
    $seen=array(); $urls=array(); $queue=array($root); $responses=array(); $limit=100;
    while($queue && count($seen)<$limit){
        $u=array_shift($queue); if(isset($seen[$u])) continue; $seen[$u]=1;
        $r=wp_remote_get($u,array('timeout'=>20,'redirection'=>2,'user-agent'=>'EMDO-Global-Blog-Audit/1.0'));
        if(is_wp_error($r)){ $responses[]=array('url'=>$u,'error'=>$r->get_error_message()); continue; }
        $code=(int)wp_remote_retrieve_response_code($r); $body=(string)wp_remote_retrieve_body($r);
        $responses[]=array('url'=>$u,'code'=>$code,'bytes'=>strlen($body));
        if($code!==200) continue;
        $locs=emdo_g_xml_locs($body);
        if(stripos($body,'<sitemapindex')!==false){ foreach($locs as $loc) if(!isset($seen[$loc])) $queue[]=$loc; }
        elseif(stripos($body,'<urlset')!==false){ foreach($locs as $loc) $urls[$loc]=1; }
    }
    return array('root'=>$root,'responses'=>$responses,'urls'=>array_keys($urls));
}

$statuses=array('publish','draft','pending','future','private','trash');
$all=get_posts(array('post_type'=>'post','post_status'=>$statuses,'posts_per_page'=>-1,'orderby'=>'ID','order'=>'ASC','suppress_filters'=>true));
$pub=array(); $nonpub=array(); $status_counts=array_fill_keys($statuses,0);
foreach($all as $p){
    $status_counts[$p->post_status]=($status_counts[$p->post_status]??0)+1;
    $rec=array(
        'id'=>(int)$p->ID,'status'=>$p->post_status,'title'=>(string)$p->post_title,'slug'=>(string)$p->post_name,
        'norm_title'=>emdo_g_norm($p->post_title),'words'=>emdo_g_words($p->post_content),
        'content_tokens'=>emdo_g_tokens($p->post_content),'headings'=>emdo_g_headings($p->post_content),
        'en_title'=>(string)get_post_meta($p->ID,'_en_US_post_title',true),
        'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),
        'en_content'=>(string)get_post_meta($p->ID,'_en_US_post_content',true),
        'en_ready'=>(string)get_post_meta($p->ID,'_en_US_ready',true),
        'en_published'=>(string)get_post_meta($p->ID,'_en_US_published',true),
    );
    $rec['en_words']=emdo_g_words($rec['en_content']);
    if($p->post_status==='publish') $pub[]=$rec; else $nonpub[]=$rec;
}

$lang_issues=array(); $en_slug_map=array(); $title_map=array(); $slug_map=array();
foreach($pub as $r){
    $missing=array();
    foreach(array('en_title'=>'title','en_slug'=>'slug','en_content'=>'content') as $k=>$label) if(trim($r[$k])==='') $missing[]=$label;
    if($r['en_ready']!=='1') $missing[]='ready_flag';
    if($r['en_published']!=='1') $missing[]='published_flag';
    if($r['en_words']<120) $missing[]='en_content_under_120_words';
    if($missing) $lang_issues[]=array('id'=>$r['id'],'title'=>$r['title'],'slug'=>$r['slug'],'en_slug'=>$r['en_slug'],'en_words'=>$r['en_words'],'issues'=>$missing);
    if($r['en_slug']!=='') $en_slug_map[emdo_g_norm($r['en_slug'])][]=$r['id'];
    $title_map[$r['norm_title']][]=$r['id']; $slug_map[$r['slug']][]=$r['id'];
}
$exact=array('titles'=>array(),'slugs'=>array(),'en_slugs'=>array());
foreach($title_map as $k=>$ids) if($k!==''&&count($ids)>1) $exact['titles'][]=array('value'=>$k,'ids'=>$ids);
foreach($slug_map as $k=>$ids) if($k!==''&&count($ids)>1) $exact['slugs'][]=array('value'=>$k,'ids'=>$ids);
foreach($en_slug_map as $k=>$ids) if($k!==''&&count($ids)>1) $exact['en_slugs'][]=array('value'=>$k,'ids'=>$ids);

$overlaps=array(); $n=count($pub);
for($i=0;$i<$n;$i++) for($j=$i+1;$j<$n;$j++){
    $a=$pub[$i]; $b=$pub[$j];
    $tj=emdo_g_jaccard($a['title'],$b['title']);
    // Avoid expensive full cosine for clearly unrelated titles unless content is likely templated.
    $cc=emdo_g_cos($a['content_tokens'],$b['content_tokens']);
    if($tj>=0.42 || $cc>=0.70){
        $score=max($tj,min(1,$cc));
        $overlaps[]=array('a'=>$a['id'],'at'=>$a['title'],'as'=>$a['slug'],'b'=>$b['id'],'bt'=>$b['title'],'bs'=>$b['slug'],'title_j'=>round($tj,3),'content_cos'=>round($cc,3),'ah'=>$a['headings'],'bh'=>$b['headings'],'score'=>round($score,3));
    }
}
usort($overlaps,fn($x,$y)=>$y['score']<=>$x['score']);
if(count($overlaps)>150) $overlaps=array_slice($overlaps,0,150);

$draft_overlaps=array();
foreach($nonpub as $d){
    if(!in_array($d['status'],array('draft','pending','future','private'),true)) continue;
    $best=null;
    foreach($pub as $p){
        $tj=emdo_g_jaccard($d['title'],$p['title']);
        if($tj<0.35 && $d['norm_title']!==$p['norm_title']) continue;
        $cc=emdo_g_cos($d['content_tokens'],$p['content_tokens']);
        if($d['norm_title']===$p['norm_title'] || $tj>=0.55 || $cc>=0.72){
            $cand=array('draft'=>$d['id'],'status'=>$d['status'],'dt'=>$d['title'],'published'=>$p['id'],'pt'=>$p['title'],'title_j'=>round($tj,3),'content_cos'=>round($cc,3));
            if($best===null || max($cand['title_j'],$cand['content_cos'])>max($best['title_j'],$best['content_cos'])) $best=$cand;
        }
    }
    if($best) $draft_overlaps[]=$best;
}
usort($draft_overlaps,fn($x,$y)=>max($y['title_j'],$y['content_cos'])<=>max($x['title_j'],$x['content_cos']));

$retired_ids=range(14061,14070); $retired=array();
foreach($retired_ids as $id) $retired[]=array('id'=>$id,'status'=>get_post_status($id),'en_published'=>(string)get_post_meta($id,'_en_US_published',true),'slug'=>(string)get_post_field('post_name',$id),'en_slug'=>(string)get_post_meta($id,'_en_US_post_name',true));
$redirects=get_option('emdo_authority_redirects_20260902',array()); if(!is_array($redirects)) $redirects=array();
$redirect_chains=array(); foreach($redirects as $from=>$to) if(isset($redirects[$to])) $redirect_chains[]=array('from'=>$from,'to'=>$to,'next'=>$redirects[$to]);

// Search public content for links still pointing at any redirect source.
$redirect_internal_refs=array();
foreach($redirects as $from=>$to){
    $needle='/'.ltrim((string)$from,'/'); $hits=array();
    foreach($pub as $p){ if(strpos((string)get_post_field('post_content',$p['id']),$needle)!==false || strpos((string)get_post_meta($p['id'],'_en_US_post_content',true),$needle)!==false) $hits[]=$p['id']; }
    if($hits) $redirect_internal_refs[]=array('from'=>$from,'to'=>$to,'posts'=>$hits);
}

// Sitemap discovery and comparison. Prefer sitemap_index.xml, then core wp-sitemap.xml.
$home=rtrim(home_url('/'),'/'); $trees=array();
foreach(array($home.'/sitemap_index.xml',$home.'/wp-sitemap.xml') as $root){
    $tree=emdo_g_fetch_sitemap_tree($root);
    $root_ok=false; foreach($tree['responses'] as $rr) if($rr['url']===$root && ($rr['code']??0)===200) $root_ok=true;
    if($root_ok && $tree['urls']) { $trees[]=$tree; break; }
}
$sitemap=array('root'=>null,'url_count'=>0,'published_es_missing'=>array(),'published_en_missing'=>array(),'retired_present'=>array(),'blog_redirect_like'=>array(),'responses'=>array());
if($trees){
    $tree=$trees[0]; $sitemap['root']=$tree['root']; $sitemap['url_count']=count($tree['urls']); $sitemap['responses']=$tree['responses'];
    $set=array_fill_keys(array_map(fn($u)=>rtrim($u,'/').'/', $tree['urls']),1);
    foreach($pub as $p){
        $es=rtrim(get_permalink($p['id']),'/').'/'; if(!isset($set[$es])) $sitemap['published_es_missing'][]=array('id'=>$p['id'],'url'=>$es);
        if($p['en_slug']!==''&&$p['en_published']==='1'){
            $en=$home.'/en/'.trim($p['en_slug'],'/').'/'; if(!isset($set[$en])) $sitemap['published_en_missing'][]=array('id'=>$p['id'],'url'=>$en);
        }
    }
    foreach($retired as $r){
        if($r['slug']!=='') foreach($tree['urls'] as $u) if(strpos($u,'/'.$r['slug'].'/')!==false) $sitemap['retired_present'][]=$u;
        if($r['en_slug']!=='') foreach($tree['urls'] as $u) if(strpos($u,'/en/'.$r['en_slug'].'/')!==false) $sitemap['retired_present'][]=$u;
    }
}

$out=array(
    'counts'=>array('all'=>count($all),'published'=>count($pub),'status'=>$status_counts,'language_issues'=>count($lang_issues),'semantic_candidates'=>count($overlaps),'draft_overlap_candidates'=>count($draft_overlaps),'redirects'=>count($redirects)),
    'language_issues'=>$lang_issues,'exact_duplicates'=>$exact,'semantic_candidates'=>$overlaps,'draft_overlap_candidates'=>$draft_overlaps,
    'retired'=>$retired,'redirect_chains'=>$redirect_chains,'redirect_internal_refs'=>$redirect_internal_refs,'sitemap'=>$sitemap
);
echo "EMDO_GLOBAL_BLOG_AUDIT_BEGIN\n";
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_GLOBAL_BLOG_AUDIT_END\n";
