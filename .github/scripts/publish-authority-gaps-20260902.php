<?php
/** Publish 21 missing authority articles, Spanish + Falang en_US. */
if (!defined('ABSPATH')) { exit; }
const EMDO_AUTH_GAPS_MARKER = '2026-09-02.authority-gaps.v1';
const EMDO_AUTH_GAPS_META   = '_emdo_authority_gaps_20260902';

$data_file = __DIR__ . '/authority-gaps-all.json';
$raw = file_get_contents($data_file);
if ($raw === false) { throw new RuntimeException('Could not read authority-gaps-all.json'); }
$articles = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
if (!is_array($articles) || count($articles) !== 21) { throw new RuntimeException('Expected exactly 21 articles'); }
$expected = array(12,14,18,20,21,25,45,67,68,70,73,74,75,82,83,84,85,86,87,88,90);
$positions = array_map(fn($a)=>(int)$a['pos'], $articles); sort($positions);
if ($positions !== $expected) { throw new RuntimeException('Editorial positions mismatch'); }

function emdo_ag_e(string $s): string { return esc_html($s); }
function emdo_ag_term(string $slug): WP_Term {
    $t = get_category_by_slug($slug);
    if (!$t instanceof WP_Term) { throw new RuntimeException('Required category missing: '.$slug); }
    return $t;
}
function emdo_ag_image(int $cat): int {
    $ids = get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids','category__in'=>array($cat),'orderby'=>'date','order'=>'DESC'));
    foreach ($ids as $id) { $x=(int)get_post_thumbnail_id((int)$id); if ($x>0) return $x; }
    return 0;
}
function emdo_ag_author(int $cat): int {
    $ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','category__in'=>array($cat),'orderby'=>'date','order'=>'DESC'));
    if ($ids) { $a=(int)get_post_field('post_author',(int)$ids[0]); if ($a>0) return $a; }
    return 1;
}
function emdo_ag_title(string $s): string {
    if (function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>62) return rtrim(mb_substr($s,0,59,'UTF-8')).'…';
    return $s;
}
function emdo_ag_desc(string $s): string {
    if (function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>158) return rtrim(mb_substr($s,0,155,'UTF-8')).'…';
    return $s;
}
function emdo_ag_words(string $html): int {
    $txt=wp_strip_all_tags($html);
    preg_match_all("/[\\p{L}\\p{N}]+(?:[’'\\-][\\p{L}\\p{N}]+)*/u",$txt,$m);
    return count($m[0]);
}
function emdo_ag_english_collision(string $en_slug, int $current=0): ?int {
    $ids=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','pending','future','private'),'posts_per_page'=>10,'fields'=>'ids','meta_key'=>'_en_US_post_name','meta_value'=>$en_slug));
    foreach($ids as $id){ if((int)$id!==$current) return (int)$id; }
    return null;
}
function emdo_ag_related(array $slugs, bool $spanish): string {
    $items=array();
    foreach($slugs as $slug){
        $p=get_page_by_path((string)$slug,OBJECT,'post');
        if(!$p instanceof WP_Post) continue;
        $same=EMDO_AUTH_GAPS_MARKER===(string)get_post_meta($p->ID,EMDO_AUTH_GAPS_META,true);
        if(get_post_status($p)!=='publish' && !$same) continue;
        if($spanish){
            $items[]='<li><a href="'.esc_url('/'.$p->post_name.'/').'">'.emdo_ag_e(get_the_title($p)).'</a></li>';
        } else {
            $pub=(string)get_post_meta($p->ID,'_en_US_published',true);
            if($pub!=='1' && !$same) continue;
            $s=(string)get_post_meta($p->ID,'_en_US_post_name',true);
            $t=(string)get_post_meta($p->ID,'_en_US_post_title',true);
            if($s==='' || $t==='') continue;
            $items[]='<li><a href="'.esc_url('/en/'.$s.'/').'">'.emdo_ag_e($t).'</a></li>';
        }
    }
    if(!$items) return '';
    return ($spanish?'<h2>Guías relacionadas</h2>':'<h2>Related guides</h2>').'<ul>'.implode('',$items).'</ul>';
}
function emdo_ag_render(array $a, bool $spanish): string {
    $lead=$spanish?(string)$a['lead_es']:(string)$a['lead_en'];
    $facts=$spanish?(array)$a['facts_es']:(array)$a['facts_en'];
    $sections=$spanish?(array)$a['sections_es']:(array)$a['sections_en'];
    $faq=$spanish?(array)$a['faq_es']:(array)$a['faq_en'];
    $conclusion=$spanish?(string)$a['conclusion_es']:(string)$a['conclusion_en'];
    $out='<p>'.emdo_ag_e($lead).'</p>';
    $out.=$spanish?'<h2>Resumen rápido</h2>':'<h2>Quick summary</h2>';
    $out.='<table><tbody>';
    foreach($facts as $row){ if(!is_array($row)||count($row)<2) continue; $out.='<tr><th>'.emdo_ag_e((string)$row[0]).'</th><td>'.emdo_ag_e((string)$row[1]).'</td></tr>'; }
    $out.='</tbody></table>';
    foreach($sections as $sec){
        if(!is_array($sec)||count($sec)<2) continue;
        $out.='<h2>'.emdo_ag_e((string)$sec[0]).'</h2>';
        foreach(preg_split('/\n\n+/',(string)$sec[1]) as $p){ if(trim($p)!=='') $out.='<p>'.emdo_ag_e(trim($p)).'</p>'; }
    }
    if($faq){
        $out.=$spanish?'<h2>Preguntas relacionadas</h2>':'<h2>Related questions</h2>';
        foreach($faq as $qa){ if(!is_array($qa)||count($qa)<2) continue; $out.='<h3>'.emdo_ag_e((string)$qa[0]).'</h3><p>'.emdo_ag_e((string)$qa[1]).'</p>'; }
    }
    $out.=emdo_ag_related((array)$a['related'],$spanish);
    $out.=$spanish?'<h2>Conclusión</h2>':'<h2>Bottom line</h2>';
    $out.='<p>'.emdo_ag_e($conclusion).'</p>';
    $out.=$spanish?'<h2>Fuentes y referencias</h2>':'<h2>Sources and references</h2>';
    $out.='<ul>';
    foreach((array)$a['sources'] as $src){
        $host=(string)wp_parse_url((string)$src,PHP_URL_HOST); if($host==='') $host=(string)$src;
        $out.='<li><a href="'.esc_url((string)$src).'" rel="noopener noreferrer">'.emdo_ag_e($host).'</a></li>';
    }
    $out.='</ul>';
    return $out;
}

$terms=array('quesos'=>emdo_ag_term('quesos'),'aceites'=>emdo_ag_term('aceites'));
$images=array(); $authors=array();
foreach($terms as $slug=>$term){ $images[$slug]=emdo_ag_image((int)$term->term_id); $authors[$slug]=emdo_ag_author((int)$term->term_id); }
$ids=array(); $seen_es=array(); $seen_en=array();

// Phase 1: reserve all posts as drafts and establish bilingual identities.
foreach($articles as $a){
    $cat=(string)$a['category']; if(!isset($terms[$cat])) throw new RuntimeException('Unexpected category '.$cat);
    $slug=(string)$a['slug']; $en_slug=(string)$a['en_slug'];
    if(isset($seen_es[$slug])||isset($seen_en[$en_slug])) throw new RuntimeException('Duplicate slug in payload');
    $seen_es[$slug]=1; $seen_en[$en_slug]=1;
    $existing=get_page_by_path($slug,OBJECT,'post');
    if($existing instanceof WP_Post){
        if(EMDO_AUTH_GAPS_MARKER!==(string)get_post_meta($existing->ID,EMDO_AUTH_GAPS_META,true)) throw new RuntimeException('Refusing to overwrite existing non-batch post: '.$slug);
        $id=(int)$existing->ID;
    } else {
        $collision=emdo_ag_english_collision($en_slug,0); if($collision) throw new RuntimeException('English slug collision '.$en_slug.' post '.$collision);
        $r=wp_insert_post(wp_slash(array('post_type'=>'post','post_status'=>'draft','post_title'=>(string)$a['title'],'post_name'=>$slug,'post_excerpt'=>(string)$a['excerpt'],'post_author'=>$authors[$cat],'post_category'=>array((int)$terms[$cat]->term_id),'comment_status'=>'closed','ping_status'=>'closed')),true);
        if(is_wp_error($r)||(int)$r<=0) throw new RuntimeException('Could not reserve '.$slug.': '.(is_wp_error($r)?$r->get_error_message():'unknown'));
        $id=(int)$r;
    }
    $collision=emdo_ag_english_collision($en_slug,$id); if($collision) throw new RuntimeException('English slug collision '.$en_slug.' post '.$collision);
    update_post_meta($id,EMDO_AUTH_GAPS_META,EMDO_AUTH_GAPS_MARKER);
    update_post_meta($id,'_emdo_editorial_position',(string)(int)$a['pos']);
    update_post_meta($id,'_en_US_post_title',(string)$a['en_title']);
    update_post_meta($id,'_en_US_post_name',$en_slug);
    update_post_meta($id,'_en_US_post_excerpt',(string)$a['en_excerpt']);
    update_post_meta($id,'_en_US_ready','1');
    update_post_meta($id,'_en_US_published','1');
    $ids[$slug]=$id;
}

// Phase 2: render complete Spanish and English bodies while posts remain drafts.
$metrics=array();
foreach($articles as $a){
    $cat=(string)$a['category']; $slug=(string)$a['slug']; $id=$ids[$slug];
    $es=emdo_ag_render($a,true); $en=emdo_ag_render($a,false);
    $r=wp_update_post(wp_slash(array('ID'=>$id,'post_status'=>'draft','post_title'=>(string)$a['title'],'post_name'=>$slug,'post_excerpt'=>(string)$a['excerpt'],'post_content'=>$es,'post_author'=>$authors[$cat],'post_category'=>array((int)$terms[$cat]->term_id),'comment_status'=>'closed','ping_status'=>'closed')),true);
    if(is_wp_error($r)) throw new RuntimeException('Spanish render failed '.$slug.': '.$r->get_error_message());
    update_post_meta($id,'_en_US_post_content',$en);
    update_post_meta($id,'_emdo_seo_title',emdo_ag_title((string)$a['title']));
    update_post_meta($id,'_emdo_seo_description',emdo_ag_desc((string)$a['excerpt']));
    update_post_meta($id,'_en_US_seo_title',emdo_ag_title((string)$a['en_title']));
    update_post_meta($id,'_en_US_seo_description',emdo_ag_desc((string)$a['en_excerpt']));
    if($images[$cat]>0) set_post_thumbnail($id,$images[$cat]);
    clean_post_cache($id);
    $ew=emdo_ag_words($es); $nw=emdo_ag_words($en);
    if($ew<620||$nw<520) throw new RuntimeException('Rendered article too short '.$slug.' ES='.$ew.' EN='.$nw);
    if(count((array)$a['sections_es'])<6||count((array)$a['sections_en'])<6) throw new RuntimeException('Too few sections '.$slug);
    if(count((array)$a['sources'])<2) throw new RuntimeException('Too few sources '.$slug);
    if(strpos($es,'<h2>Preguntas relacionadas</h2>')===false||strpos($en,'<h2>Related questions</h2>')===false) throw new RuntimeException('FAQ missing '.$slug);
    if(strpos($es,'<h2>Fuentes y referencias</h2>')===false||strpos($en,'<h2>Sources and references</h2>')===false) throw new RuntimeException('Sources block missing '.$slug);
    $metrics[$slug]=array('es_words'=>$ew,'en_words'=>$nw,'image_id'=>(int)get_post_thumbnail_id($id),'related_es'=>strpos($es,'<h2>Guías relacionadas</h2>')!==false,'related_en'=>strpos($en,'<h2>Related guides</h2>')!==false);
}

// Phase 3: final identity/status checks before any post is made public.
foreach($articles as $a){
    $slug=(string)$a['slug']; $id=$ids[$slug];
    if(get_post_status($id)!=='draft') throw new RuntimeException('Unexpected prepublish status '.$slug);
    if((string)get_post_meta($id,'_en_US_post_name',true)!==(string)$a['en_slug']) throw new RuntimeException('English slug verification failed '.$slug);
    if((string)get_post_meta($id,'_en_US_published',true)!=='1') throw new RuntimeException('English publish flag missing '.$slug);
    if((string)get_post_meta($id,EMDO_AUTH_GAPS_META,true)!==EMDO_AUTH_GAPS_MARKER) throw new RuntimeException('Marker verification failed '.$slug);
}

// Phase 4: publish only after all 42 language versions have passed validation.
foreach($articles as $a){
    $slug=(string)$a['slug']; $id=$ids[$slug];
    $r=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
    if(is_wp_error($r)) throw new RuntimeException('Publish failed '.$slug.': '.$r->get_error_message());
    clean_post_cache($id);
}

$out=array('marker'=>EMDO_AUTH_GAPS_MARKER,'count'=>21,'cheese'=>0,'oils'=>0,'posts'=>array());
foreach($articles as $a){
    $slug=(string)$a['slug']; $id=$ids[$slug]; $m=$metrics[$slug];
    if($a['category']==='quesos') $out['cheese']++; else $out['oils']++;
    $out['posts'][]=array('position'=>(int)$a['pos'],'id'=>$id,'category'=>(string)$a['category'],'slug'=>$slug,'en_slug'=>(string)$a['en_slug'],'status'=>get_post_status($id),'es_words'=>$m['es_words'],'en_words'=>$m['en_words'],'related_es'=>$m['related_es'],'related_en'=>$m['related_en'],'image_id'=>$m['image_id']);
}
echo "EMDO_AUTHORITY_GAPS_BEGIN\n";
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_AUTHORITY_GAPS_END\n";
