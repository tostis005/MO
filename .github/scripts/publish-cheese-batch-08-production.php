<?php
/** Publish cheese authority batch 08: positions 71-80, Spanish + Falang en_US. */
if (!defined('ABSPATH')) { exit; }
const EMDO_CHEESE_BATCH08_MARKER = '2026-09-01.cheese-08.v1';

function emdo_cheese08_load_article(int $position): array {
    $file=__DIR__.'/cheese-batch-08-'.$position.'.json';
    $raw=file_get_contents($file);
    if($raw===false){throw new RuntimeException('Could not read editorial data: '.basename($file));}
    $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    if(!is_array($data)){throw new RuntimeException('Invalid editorial data: '.basename($file));}
    if((int)($data['pos']??0)!==$position){throw new RuntimeException('Editorial position mismatch: '.basename($file));}
    return $data;
}
$articles=array(); foreach(range(71,80) as $p){$articles[]=emdo_cheese08_load_article($p);} if(count($articles)!==10){throw new RuntimeException('Expected exactly 10 articles');}
$required=array('pos','title','slug','en_title','en_slug','excerpt','en_excerpt','lead_es','lead_en','facts_es','facts_en','sections_es','sections_en','faq_es','faq_en','conclusion_es','conclusion_en','related','sources');
$seen=array();$seen_en=array();$positions=array();
foreach($articles as $a){foreach($required as $k){if(!array_key_exists($k,$a)){throw new RuntimeException('Missing '.$k.' in '.$a['slug']);}}$s=(string)$a['slug'];$e=(string)$a['en_slug'];if(isset($seen[$s])||isset($seen_en[$e])){throw new RuntimeException('Duplicate slug');}$seen[$s]=1;$seen_en[$e]=1;$positions[]=(int)$a['pos'];}
sort($positions);if($positions!==range(71,80)){throw new RuntimeException('Positions must be 71 through 80');}

function emdo_cheese08_e(string $s):string{return esc_html($s);} 
function emdo_cheese08_category():WP_Term{
    $t=get_category_by_slug('quesos');
    if(!$t instanceof WP_Term){$c=wp_insert_term('Quesos','category',array('slug'=>'quesos','description'=>'Guías de compra, conservación, elaboración y cultura del queso.'));if(is_wp_error($c)){throw new RuntimeException($c->get_error_message());}$t=get_term((int)$c['term_id'],'category');}
    if(!$t instanceof WP_Term){throw new RuntimeException('Could not resolve Quesos category');}
    update_term_meta($t->term_id,'_en_US_name','Cheese');update_term_meta($t->term_id,'_en_US_slug','cheese');update_term_meta($t->term_id,'_en_US_description','Buying guides, storage advice, cheesemaking and cheese culture.');update_term_meta($t->term_id,'_en_US_published','1');return $t;
}
function emdo_cheese08_image(int $cat):int{$ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>50,'fields'=>'ids','category__in'=>array($cat)));foreach($ids as $id){$x=(int)get_post_thumbnail_id($id);if($x>0){return $x;}}return 0;}
function emdo_cheese08_related(array $slugs,bool $es):string{
    $items=array();foreach($slugs as $slug){$p=get_page_by_path((string)$slug,OBJECT,'post');if(!$p instanceof WP_Post){continue;}$same=EMDO_CHEESE_BATCH08_MARKER===(string)get_post_meta($p->ID,'_emdo_cheese_batch08',true);if(get_post_status($p)!=='publish'&&!$same){continue;}
        if($es){$items[]='<li><a href="'.esc_url('/'.$p->post_name.'/').'">'.emdo_cheese08_e(get_the_title($p)).'</a></li>';}else{$pub=(string)get_post_meta($p->ID,'_en_US_published',true);if($pub!=='1'&&!$same){continue;}$s=(string)get_post_meta($p->ID,'_en_US_post_name',true);$t=(string)get_post_meta($p->ID,'_en_US_post_title',true);if($s===''||$t===''){continue;}$items[]='<li><a href="'.esc_url('/en/'.$s.'/').'">'.emdo_cheese08_e($t).'</a></li>';}}
    if(!$items){return '';}return ($es?'<h2>Guías relacionadas</h2>':'<h2>Related guides</h2>').'<ul>'.implode('',$items).'</ul>';
}
function emdo_cheese08_render(array $a,bool $es):string{
    $lead=$es?$a['lead_es']:$a['lead_en'];$facts=$es?$a['facts_es']:$a['facts_en'];$sections=$es?$a['sections_es']:$a['sections_en'];$faq=$es?$a['faq_es']:$a['faq_en'];$conclusion=$es?$a['conclusion_es']:$a['conclusion_en'];
    $out='<p>'.emdo_cheese08_e((string)$lead).'</p>'.($es?'<h2>Resumen rápido</h2>':'<h2>Quick summary</h2>').'<table><tbody>';
    foreach($facts as $r){$out.='<tr><th>'.emdo_cheese08_e((string)$r[0]).'</th><td>'.emdo_cheese08_e((string)$r[1]).'</td></tr>';}$out.='</tbody></table>';
    foreach($sections as $sec){$out.='<h2>'.emdo_cheese08_e((string)$sec[0]).'</h2>';foreach(preg_split('/\n\n+/',(string)$sec[1]) as $p){if(trim($p)!==''){$out.='<p>'.emdo_cheese08_e(trim($p)).'</p>';}}}
    if($faq){$out.=$es?'<h2>Preguntas relacionadas</h2>':'<h2>Related questions</h2>';foreach($faq as $qa){$out.='<h3>'.emdo_cheese08_e((string)$qa[0]).'</h3><p>'.emdo_cheese08_e((string)$qa[1]).'</p>';}}
    $out.=emdo_cheese08_related((array)$a['related'],$es);$out.=$es?'<h2>Conclusión</h2>':'<h2>Bottom line</h2>';$out.='<p>'.emdo_cheese08_e((string)$conclusion).'</p>';
    $out.=$es?'<h2>Fuentes y referencias</h2>':'<h2>Sources and references</h2>';$out.='<ul>';foreach((array)$a['sources'] as $src){$host=(string)wp_parse_url((string)$src,PHP_URL_HOST);$out.='<li><a href="'.esc_url((string)$src).'" rel="noopener noreferrer">'.emdo_cheese08_e($host).'</a></li>';}$out.='</ul>';return $out;
}
function emdo_cheese08_title(string $s):string{if(function_exists('mb_strlen')&&mb_strlen($s,'UTF-8')>62){return rtrim(mb_substr($s,0,59,'UTF-8')).'…';}return $s;}
function emdo_cheese08_desc(string $s):string{if(function_exists('mb_strlen')&&mb_strlen($s,'UTF-8')>158){return rtrim(mb_substr($s,0,155,'UTF-8')).'…';}return $s;}

$term=emdo_cheese08_category();$image=emdo_cheese08_image((int)$term->term_id);$sample=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','category__in'=>array((int)$term->term_id)));$author=$sample?(int)get_post_field('post_author',(int)$sample[0]):1;if($author<=0){$author=1;}
$ids=array();
foreach($articles as $a){$existing=get_page_by_path((string)$a['slug'],OBJECT,'post');if($existing instanceof WP_Post){if(EMDO_CHEESE_BATCH08_MARKER!==(string)get_post_meta($existing->ID,'_emdo_cheese_batch08',true)){throw new RuntimeException('Refusing to overwrite existing non-batch post: '.$a['slug']);}$id=(int)$existing->ID;}else{$r=wp_insert_post(wp_slash(array('post_type'=>'post','post_status'=>'draft','post_title'=>(string)$a['title'],'post_name'=>(string)$a['slug'],'post_excerpt'=>(string)$a['excerpt'],'post_author'=>$author,'post_category'=>array((int)$term->term_id),'comment_status'=>'closed','ping_status'=>'closed')),true);if(is_wp_error($r)||(int)$r<=0){throw new RuntimeException('Could not reserve '.$a['slug']);}$id=(int)$r;}
    update_post_meta($id,'_emdo_cheese_batch08',EMDO_CHEESE_BATCH08_MARKER);update_post_meta($id,'_emdo_editorial_position',(string)(int)$a['pos']);update_post_meta($id,'_en_US_post_title',(string)$a['en_title']);update_post_meta($id,'_en_US_post_name',(string)$a['en_slug']);update_post_meta($id,'_en_US_post_excerpt',(string)$a['en_excerpt']);update_post_meta($id,'_en_US_ready','1');update_post_meta($id,'_en_US_published','1');$ids[(string)$a['slug']]=$id;
}
foreach($articles as $a){$id=$ids[(string)$a['slug']];$es=emdo_cheese08_render($a,true);$en=emdo_cheese08_render($a,false);$r=wp_update_post(wp_slash(array('ID'=>$id,'post_title'=>(string)$a['title'],'post_name'=>(string)$a['slug'],'post_excerpt'=>(string)$a['excerpt'],'post_content'=>$es,'post_author'=>$author,'post_category'=>array((int)$term->term_id),'comment_status'=>'closed','ping_status'=>'closed')),true);if(is_wp_error($r)){throw new RuntimeException('Spanish render failed '.$a['slug']);}update_post_meta($id,'_en_US_post_content',$en);update_post_meta($id,'_emdo_seo_title',emdo_cheese08_title((string)$a['title']));update_post_meta($id,'_emdo_seo_description',emdo_cheese08_desc((string)$a['excerpt']));update_post_meta($id,'_en_US_seo_title',emdo_cheese08_title((string)$a['en_title']));update_post_meta($id,'_en_US_seo_description',emdo_cheese08_desc((string)$a['en_excerpt']));if($image>0){set_post_thumbnail($id,$image);}clean_post_cache($id);}

$spots=array(
'quesos-castilla-la-mancha-manchego-dop-guia-quesos-manchegos'=>array('Manchego es una DOP, no un sinónimo','Manchego is a PDO, not a synonym'),
'quesos-pais-vasco-navarra-idiazabal-roncal-guia'=>array('Dos denominaciones cercanas que no se solapan','Two neighbouring designations that do not overlap'),
'quesos-murcia-dop-queso-murcia-murcia-al-vino-diferencias-guia'=>array('Murcia al Vino: qué hacen realmente los baños de vino','Murcia al Vino: what the wine baths actually do'),
'quesos-baleares-mahon-menorca-dop-guia-islas'=>array('Mahón-Menorca: el nombre protegido que ordena el mapa','Mahón-Menorca: the protected name that anchors the map'),
'quesos-oveja-espanoles-dop-zonas-estilos-como-elegir'=>array('Extremadura: Torta del Casar y La Serena','Extremadura: Torta del Casar and La Serena'),
'quesos-cabra-espanoles-dop-zonas-estilos-como-elegir'=>array('Canarias: Majorero y Palmero','Canary Islands: Majorero and Palmero'),
'quesos-vaca-espanoles-dop-zonas-estilos-como-elegir'=>array('Galicia: cuatro DOP y un abanico enorme','Galicia: four PDOs and a broad range'),
'coagulacion-lactica-enzimatica-mixta-queso-diferencias-textura'=>array('Coagulación láctica: tiempo y acidificación','Lactic coagulation: time and acidification'),
'queso-pasta-prensada-que-significa-pasta-cocida-no-cocida'=>array('Pasta no cocida no significa cuajada fría','Uncooked curd does not mean cold curd'),
'por-que-queso-suda-suelta-grasa-condensacion-temperatura'=>array('Primero: agua y grasa no son lo mismo','First: water and fat are not the same thing')
);
$metrics=array();$bad_es=array('¿Más curación significa siempre mejor queso?','¿Es mejor comprar una pieza grande?');$bad_en=array('Does longer ageing always mean better cheese?','Is it better to buy a large wheel?');
foreach($articles as $a){$id=$ids[(string)$a['slug']];$es=(string)get_post_field('post_content',$id);$en=(string)get_post_meta($id,'_en_US_post_content',true);$ew=str_word_count(wp_strip_all_tags($es));$nw=str_word_count(wp_strip_all_tags($en));if($ew<780||$nw<650){throw new RuntimeException('Article too short '.$a['slug'].' ES='.$ew.' EN='.$nw);}if(count((array)$a['sections_es'])<6||count((array)$a['sections_en'])<6){throw new RuntimeException('Not enough sections '.$a['slug']);}if(count((array)$a['sources'])<2){throw new RuntimeException('Not enough sources '.$a['slug']);}if(strpos($es,'<h2>Guías relacionadas</h2><ul></ul>')!==false||strpos($en,'<h2>Related guides</h2><ul></ul>')!==false){throw new RuntimeException('Empty related guides '.$a['slug']);}foreach($bad_es as $x){if(strpos($es,$x)!==false){throw new RuntimeException('Generic ES FAQ '.$a['slug']);}}foreach($bad_en as $x){if(strpos($en,$x)!==false){throw new RuntimeException('Generic EN FAQ '.$a['slug']);}}$sp=$spots[(string)$a['slug']];if(strpos($es,$sp[0])===false||strpos($en,$sp[1])===false){throw new RuntimeException('Spotcheck missing '.$a['slug']);}$metrics[(string)$a['slug']]=array('es_words'=>$ew,'en_words'=>$nw,'related_es'=>strpos($es,'<h2>Guías relacionadas</h2>')!==false,'related_en'=>strpos($en,'<h2>Related guides</h2>')!==false,'faq_es'=>strpos($es,'<h2>Preguntas relacionadas</h2>')!==false,'faq_en'=>strpos($en,'<h2>Related questions</h2>')!==false);}
foreach($articles as $a){$id=$ids[(string)$a['slug']];$r=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);if(is_wp_error($r)){throw new RuntimeException('Publish failed '.$a['slug']);}clean_post_cache($id);}
$out=array('marker'=>EMDO_CHEESE_BATCH08_MARKER,'count'=>10,'category_id'=>(int)$term->term_id,'posts'=>array());foreach($articles as $a){$id=$ids[(string)$a['slug']];$m=$metrics[(string)$a['slug']];$out['posts'][]=array('position'=>(int)$a['pos'],'id'=>$id,'slug'=>(string)$a['slug'],'en_slug'=>(string)$a['en_slug'],'status'=>get_post_status($id),'es_words'=>$m['es_words'],'en_words'=>$m['en_words'],'related_es'=>$m['related_es'],'related_en'=>$m['related_en'],'faq_es'=>$m['faq_es'],'faq_en'=>$m['faq_en'],'image_id'=>(int)get_post_thumbnail_id($id));}
echo "EMDO_CHEESE_BATCH08_BEGIN\n";echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";echo "EMDO_CHEESE_BATCH08_END\n";
