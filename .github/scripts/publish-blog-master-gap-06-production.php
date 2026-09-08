<?php
/** Publish EMDO master-list final gap batch 06: positions 47-50. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
const EMDO_MASTER_GAP_06_MARKER = '2026-09-08.master-gap-06';
const EMDO_GAP06_IMAGE = 13442;
const EMDO_GAP06_COMP_SHA = 'e057e1e087154844a440ba504ba5c8cf0fc0db5525c2a131740a53d8fc9ee83a';
const EMDO_GAP06_RAW_SHA = '9792f7f263b29ad45d162df57403b64e5b45c7b519ba8f17a0da50702c9c0f02';

function emdo_gap06_load(): array {
    $enc='';
    for($i=1;$i<=2;$i++){
        $p=__DIR__.'/blog-master-gap-06-'.$i.'.b64';
        if(!is_readable($p)) throw new RuntimeException('Missing payload part '.$i);
        $enc.=trim((string)file_get_contents($p));
    }
    $comp=base64_decode($enc,true);
    if(false===$comp || hash('sha256',$comp)!==EMDO_GAP06_COMP_SHA) throw new RuntimeException('Compressed payload validation failed.');
    $json=gzdecode($comp);
    if(false===$json || hash('sha256',$json)!==EMDO_GAP06_RAW_SHA) throw new RuntimeException('Decoded payload validation failed.');
    $a=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    if(!is_array($a) || count($a)!==4) throw new RuntimeException('Expected exactly 4 articles.');
    $required=array('pos','title','slug','excerpt','content','seo_title','seo_description','focus_keyword','en_title','en_slug','en_excerpt','en_content','en_seo_title','en_seo_description','en_focus_keyword','category_slug','category_name','product_cat_slug','product_cat_name','related_heading','en_related_heading');
    $pos=[];$es=[];$en=[];
    foreach($a as $x){
        foreach($required as $k){ if(!array_key_exists($k,$x) || trim((string)$x[$k])==='') throw new RuntimeException('Missing field '.$k); }
        $pos[]=(int)$x['pos'];
        if(isset($es[$x['slug']]) || isset($en[$x['en_slug']])) throw new RuntimeException('Duplicate slug.');
        $es[$x['slug']]=1;$en[$x['en_slug']]=1;
        if($x['category_slug']!=='guias-y-consejos' || $x['product_cat_slug']!=='packs-y-lotes') throw new RuntimeException('Unexpected category mapping.');
    }
    sort($pos); if($pos!==array(47,48,49,50)) throw new RuntimeException('Position set mismatch.');
    return $a;
}
function emdo_gap06_words(string $html): int {
    $plain=wp_strip_all_tags(strip_shortcodes($html));
    preg_match_all('/\p{L}[\p{L}\p{M}\'’\-]*/u',$plain,$m); return count($m[0]);
}
function emdo_gap06_trim(string $s,int $max): string {
    if(function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>$max) return rtrim(mb_substr($s,0,$max-1,'UTF-8')).'…';
    return $s;
}
function emdo_gap06_render(array $a, bool $en): string {
    $content=$en?(string)$a['en_content']:(string)$a['content'];
    $heading=$en?(string)$a['en_related_heading']:(string)$a['related_heading'];
    $block='<h2>'.esc_html($heading).'</h2>'."\n".'[products category="'.esc_attr((string)$a['product_cat_slug']).'" limit="4" columns="4" orderby="date" order="DESC"]';
    $out=str_replace('<!-- EMDO_RELATED_PRODUCTS -->',$block,$content);
    if(strpos($out,'EMDO_RELATED_PRODUCTS')!==false) throw new RuntimeException('Unrendered products placeholder.');
    return $out;
}

$articles=emdo_gap06_load();
$category=get_category_by_slug('guias-y-consejos');
$product_cat=get_term_by('slug','packs-y-lotes','product_cat');
if(!$category instanceof WP_Term) throw new RuntimeException('Blog category guias-y-consejos not found.');
if(!$product_cat instanceof WP_Term) throw new RuntimeException('Product category packs-y-lotes not found.');
if(get_post_type(EMDO_GAP06_IMAGE)!=='attachment' || !wp_attachment_is_image(EMDO_GAP06_IMAGE)) throw new RuntimeException('Featured image 13442 unavailable.');
$sample=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','category__in'=>array((int)$category->term_id)));
$author=$sample?(int)get_post_field('post_author',(int)$sample[0]):1; if($author<=0)$author=1;

foreach($articles as $a){ if(get_page_by_path((string)$a['slug'],OBJECT,'post') instanceof WP_Post) throw new RuntimeException('Safety stop: existing slug '.$a['slug']); }
$ids=[];$rows=[];
foreach($articles as $a){
    $r=wp_insert_post(wp_slash(array('post_type'=>'post','post_status'=>'draft','post_title'=>(string)$a['title'],'post_name'=>(string)$a['slug'],'post_excerpt'=>(string)$a['excerpt'],'post_author'=>$author,'post_category'=>array((int)$category->term_id),'comment_status'=>'closed','ping_status'=>'closed')),true);
    if(is_wp_error($r) || (int)$r<=0) throw new RuntimeException('Could not reserve '.$a['slug']);
    $id=(int)$r; $ids[(string)$a['slug']]=$id;
    update_post_meta($id,'_emdo_master_gap_06',EMDO_MASTER_GAP_06_MARKER);
    update_post_meta($id,'_emdo_editorial_position',(string)(int)$a['pos']);
}
foreach($articles as $a){
    $slug=(string)$a['slug']; $id=$ids[$slug];
    $es=emdo_gap06_render($a,false); $en=emdo_gap06_render($a,true);
    $esw=emdo_gap06_words($es); $enw=emdo_gap06_words($en);
    if($esw<850 || $enw<750) throw new RuntimeException('Article too short '.$slug.' ES='.$esw.' EN='.$enw);
    $u=wp_update_post(wp_slash(array('ID'=>$id,'post_title'=>(string)$a['title'],'post_name'=>$slug,'post_excerpt'=>(string)$a['excerpt'],'post_content'=>$es,'post_category'=>array((int)$category->term_id),'comment_status'=>'closed','ping_status'=>'closed')),true);
    if(is_wp_error($u)) throw new RuntimeException('Spanish update failed '.$slug);
    update_post_meta($id,'_en_US_post_title',(string)$a['en_title']);
    update_post_meta($id,'_en_US_post_name',(string)$a['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$a['en_excerpt']);
    update_post_meta($id,'_en_US_post_content',$en);
    update_post_meta($id,'_en_US_ready','1'); update_post_meta($id,'_en_US_published','1');
    update_post_meta($id,'_emdo_seo_title',emdo_gap06_trim((string)$a['seo_title'],62));
    update_post_meta($id,'_emdo_seo_description',emdo_gap06_trim((string)$a['seo_description'],158));
    update_post_meta($id,'_en_US_seo_title',emdo_gap06_trim((string)$a['en_seo_title'],62));
    update_post_meta($id,'_en_US_seo_description',emdo_gap06_trim((string)$a['en_seo_description'],158));
    update_post_meta($id,'rank_math_title',emdo_gap06_trim((string)$a['seo_title'],62));
    update_post_meta($id,'rank_math_description',emdo_gap06_trim((string)$a['seo_description'],158));
    update_post_meta($id,'rank_math_focus_keyword',(string)$a['focus_keyword']);
    if(!set_post_thumbnail($id,EMDO_GAP06_IMAGE)) throw new RuntimeException('Thumbnail assignment failed '.$slug);
    update_post_meta($id,'_emdo_uses_default_featured','1');
    delete_post_meta($id,'_emdo_featured_image_source'); delete_post_meta($id,'_emdo_featured_image_query');
    if((int)get_post_thumbnail_id($id)!==EMDO_GAP06_IMAGE) throw new RuntimeException('Thumbnail mismatch '.$slug);
    if((string)get_post_meta($id,'_en_US_post_name',true)!==(string)$a['en_slug']) throw new RuntimeException('English slug mismatch '.$slug);
    if((string)get_post_meta($id,'_en_US_published',true)!=='1') throw new RuntimeException('English flag missing '.$slug);
    $rows[$slug]=array('position'=>(int)$a['pos'],'id'=>$id,'slug'=>$slug,'en_slug'=>(string)$a['en_slug'],'title'=>(string)$a['title'],'en_title'=>(string)$a['en_title'],'category_slug'=>'guias-y-consejos','product_cat'=>'packs-y-lotes','es_words'=>$esw,'en_words'=>$enw,'thumbnail_id'=>EMDO_GAP06_IMAGE);
}
foreach($articles as $a){
    $slug=(string)$a['slug'];$id=$ids[$slug];
    $r=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
    if(is_wp_error($r) || get_post_status($id)!=='publish') throw new RuntimeException('Publish failed '.$slug);
    clean_post_cache($id); $rows[$slug]['status']='publish'; $rows[$slug]['permalink']=(string)get_permalink($id); $rows[$slug]['en_permalink']=(string)home_url('/en/'.trim((string)$a['en_slug'],'/').'/');
}
flush_rewrite_rules(false); wp_cache_flush();
$out=array('marker'=>EMDO_MASTER_GAP_06_MARKER,'verified'=>true,'count'=>4,'default_featured_image_id'=>EMDO_GAP06_IMAGE,'posts'=>array_values($rows),'errors'=>array());
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
