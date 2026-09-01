<?php
/**
 * Publish cheese authority batch 06: positions 51-60, Spanish + Falang en_US.
 * Full-batch preflight before publishing any new draft.
 */
if ( ! defined('ABSPATH') ) { exit; }

const EMDO_CHEESE_BATCH06_MARKER = '2026-09-01.cheese-06.v1';

function emdo_cheese06_load_json(string $file): array {
    $raw = file_get_contents($file);
    if ($raw === false) { throw new RuntimeException('Could not read ' . basename($file)); }
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new RuntimeException('Invalid article data in ' . basename($file)); }
    return $data;
}

$files = glob(__DIR__ . '/cheese-batch-06-*.json');
if ($files === false) { $files = array(); }
$articles = array();
foreach ($files as $file) { $articles[] = emdo_cheese06_load_json($file); }
usort($articles, static function($x,$y){ return ((int)$x['pos']) <=> ((int)$y['pos']); });
if (10 !== count($articles)) { throw new RuntimeException('Expected exactly 10 articles, got ' . count($articles)); }

$required = array('pos','title','slug','en_title','en_slug','excerpt','en_excerpt','lead_es','lead_en','facts_es','facts_en','sections_es','sections_en','faq_es','faq_en','conclusion_es','conclusion_en','related','sources');
$seen = array(); $seen_en = array(); $positions = array();
foreach ($articles as $article) {
    foreach ($required as $key) {
        if (!array_key_exists($key, $article)) { throw new RuntimeException('Missing ' . $key . ' in ' . (string)($article['slug'] ?? 'unknown')); }
    }
    $slug=(string)$article['slug']; $en_slug=(string)$article['en_slug']; $pos=(int)$article['pos'];
    if (isset($seen[$slug]) || isset($seen_en[$en_slug])) { throw new RuntimeException('Duplicate slug in batch'); }
    $seen[$slug]=1; $seen_en[$en_slug]=1; $positions[]=$pos;
}
sort($positions);
if (range(51,60) !== $positions) { throw new RuntimeException('Positions must be 51 through 60'); }

function emdo_cheese06_e(string $s): string { return esc_html($s); }

function emdo_cheese06_category(): WP_Term {
    $term=get_category_by_slug('quesos');
    if (!$term instanceof WP_Term) {
        $created=wp_insert_term('Quesos','category',array(
            'slug'=>'quesos',
            'description'=>'Guías de compra, conservación, elaboración y cultura del queso.'
        ));
        if (is_wp_error($created)) { throw new RuntimeException($created->get_error_message()); }
        $term=get_term((int)$created['term_id'],'category');
    }
    if (!$term instanceof WP_Term) { throw new RuntimeException('Could not resolve Quesos category'); }
    update_term_meta($term->term_id,'_en_US_name','Cheese');
    update_term_meta($term->term_id,'_en_US_slug','cheese');
    update_term_meta($term->term_id,'_en_US_description','Buying guides, storage advice, cheesemaking and cheese culture.');
    update_term_meta($term->term_id,'_en_US_published','1');
    return $term;
}

function emdo_cheese06_image(int $category_id): int {
    $ids=get_posts(array(
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>60,
        'fields'=>'ids','category__in'=>array($category_id)
    ));
    foreach($ids as $id){
        $thumb=(int)get_post_thumbnail_id($id);
        if($thumb>0){ return $thumb; }
    }
    return 0;
}

function emdo_cheese06_related(array $slugs,bool $spanish): string {
    $items=array();
    foreach($slugs as $slug){
        $p=get_page_by_path((string)$slug,OBJECT,'post');
        if(!$p instanceof WP_Post){ continue; }
        $same_batch=EMDO_CHEESE_BATCH06_MARKER===(string)get_post_meta($p->ID,'_emdo_cheese_batch06',true);
        if('publish'!==get_post_status($p) && !$same_batch){ continue; }

        if($spanish){
            $items[]='<li><a href="'.esc_url('/'.$p->post_name.'/').'">'.emdo_cheese06_e(get_the_title($p)).'</a></li>';
        } else {
            if('1'!==(string)get_post_meta($p->ID,'_en_US_published',true)){ continue; }
            $en_slug=(string)get_post_meta($p->ID,'_en_US_post_name',true);
            $en_title=(string)get_post_meta($p->ID,'_en_US_post_title',true);
            if(''===$en_slug || ''===$en_title){ continue; }
            $items[]='<li><a href="'.esc_url('/en/'.$en_slug.'/').'">'.emdo_cheese06_e($en_title).'</a></li>';
        }
    }
    if(empty($items)){ return ''; }
    return ($spanish?'<h2>Guías relacionadas</h2>':'<h2>Related guides</h2>').'<ul>'.implode('',$items).'</ul>';
}

function emdo_cheese06_render(array $article,bool $spanish): string {
    $lead=$spanish?$article['lead_es']:$article['lead_en'];
    $facts=$spanish?$article['facts_es']:$article['facts_en'];
    $sections=$spanish?$article['sections_es']:$article['sections_en'];
    $faq=$spanish?$article['faq_es']:$article['faq_en'];
    $conclusion=$spanish?$article['conclusion_es']:$article['conclusion_en'];

    $out='<p>'.emdo_cheese06_e((string)$lead).'</p>';
    $out.=$spanish?'<h2>Resumen rápido</h2>':'<h2>Quick summary</h2>';
    $out.='<table><tbody>';
    foreach($facts as $row){
        $out.='<tr><th>'.emdo_cheese06_e((string)$row[0]).'</th><td>'.emdo_cheese06_e((string)$row[1]).'</td></tr>';
    }
    $out.='</tbody></table>';

    foreach($sections as $section){
        $out.='<h2>'.emdo_cheese06_e((string)$section[0]).'</h2>';
        foreach(preg_split('/\n\n+/',(string)$section[1]) as $paragraph){
            if(trim($paragraph)!==''){ $out.='<p>'.emdo_cheese06_e(trim($paragraph)).'</p>'; }
        }
    }

    if(!empty($faq)){
        $out.=$spanish?'<h2>Preguntas relacionadas</h2>':'<h2>Related questions</h2>';
        foreach($faq as $qa){
            $out.='<h3>'.emdo_cheese06_e((string)$qa[0]).'</h3><p>'.emdo_cheese06_e((string)$qa[1]).'</p>';
        }
    }

    $out.=emdo_cheese06_related((array)$article['related'],$spanish);
    $out.=$spanish?'<h2>Conclusión</h2>':'<h2>Bottom line</h2>';
    $out.='<p>'.emdo_cheese06_e((string)$conclusion).'</p>';
    $out.=$spanish?'<h2>Fuentes y referencias</h2>':'<h2>Sources and references</h2>';
    $out.='<ul>';
    foreach((array)$article['sources'] as $src){
        $host=(string)wp_parse_url((string)$src,PHP_URL_HOST);
        $out.='<li><a href="'.esc_url((string)$src).'" rel="noopener noreferrer">'.emdo_cheese06_e($host).'</a></li>';
    }
    $out.='</ul>';
    return $out;
}

function emdo_cheese06_meta_title(string $s): string {
    if(function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>62){ return rtrim(mb_substr($s,0,59,'UTF-8')).'…'; }
    return $s;
}
function emdo_cheese06_meta_description(string $s): string {
    if(function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>158){ return rtrim(mb_substr($s,0,155,'UTF-8')).'…'; }
    return $s;
}

$term=emdo_cheese06_category();
$image_id=emdo_cheese06_image((int)$term->term_id);
$sample=get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,
    'fields'=>'ids','category__in'=>array((int)$term->term_id)
));
$author=!empty($sample)?(int)get_post_field('post_author',(int)$sample[0]):1;
if($author<=0){ $author=1; }

// Phase 1: reserve every ES slug and Falang EN route.
$ids=array();
foreach($articles as $article){
    $existing=get_page_by_path((string)$article['slug'],OBJECT,'post');
    if($existing instanceof WP_Post){
        if(EMDO_CHEESE_BATCH06_MARKER!==(string)get_post_meta($existing->ID,'_emdo_cheese_batch06',true)){
            throw new RuntimeException('Refusing to overwrite existing non-batch post: '.$article['slug']);
        }
        $id=(int)$existing->ID;
    } else {
        $created=wp_insert_post(wp_slash(array(
            'post_type'=>'post','post_status'=>'draft',
            'post_title'=>(string)$article['title'],
            'post_name'=>(string)$article['slug'],
            'post_excerpt'=>(string)$article['excerpt'],
            'post_author'=>$author,
            'post_category'=>array((int)$term->term_id),
            'comment_status'=>'closed','ping_status'=>'closed'
        )),true);
        if(is_wp_error($created)||(int)$created<=0){ throw new RuntimeException('Could not reserve slug: '.$article['slug']); }
        $id=(int)$created;
    }

    update_post_meta($id,'_emdo_cheese_batch06',EMDO_CHEESE_BATCH06_MARKER);
    update_post_meta($id,'_emdo_editorial_position',(string)(int)$article['pos']);
    update_post_meta($id,'_en_US_post_title',(string)$article['en_title']);
    update_post_meta($id,'_en_US_post_name',(string)$article['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$article['en_excerpt']);
    update_post_meta($id,'_en_US_ready','1');
    update_post_meta($id,'_en_US_published','1');
    $ids[(string)$article['slug']]=$id;
}

// Phase 2: render full ES + EN while preserving current status on idempotent reruns.
foreach($articles as $article){
    $id=(int)$ids[(string)$article['slug']];
    $es=emdo_cheese06_render($article,true);
    $en=emdo_cheese06_render($article,false);
    $updated=wp_update_post(wp_slash(array(
        'ID'=>$id,
        'post_title'=>(string)$article['title'],
        'post_name'=>(string)$article['slug'],
        'post_excerpt'=>(string)$article['excerpt'],
        'post_content'=>$es,
        'post_author'=>$author,
        'post_category'=>array((int)$term->term_id),
        'comment_status'=>'closed','ping_status'=>'closed'
    )),true);
    if(is_wp_error($updated)){ throw new RuntimeException('Spanish render failed: '.$article['slug']); }

    update_post_meta($id,'_en_US_post_content',$en);
    update_post_meta($id,'_emdo_seo_title',emdo_cheese06_meta_title((string)$article['title']));
    update_post_meta($id,'_emdo_seo_description',emdo_cheese06_meta_description((string)$article['excerpt']));
    update_post_meta($id,'_en_US_seo_title',emdo_cheese06_meta_title((string)$article['en_title']));
    update_post_meta($id,'_en_US_seo_description',emdo_cheese06_meta_description((string)$article['en_excerpt']));
    if($image_id>0){ set_post_thumbnail($id,$image_id); }
    clean_post_cache($id);
}

// Phase 3a: preflight ALL ten before publishing any new draft.
$metrics=array();
$forbidden_es=array('¿Más curación significa siempre mejor queso?','¿Es mejor comprar una pieza grande?');
$forbidden_en=array('Does longer ageing always mean better cheese?','Is it better to buy a large wheel?');
$spotchecks=array(
    'queso-burgos-igp-que-protege-tipos-como-reconocer-autentico'=>array('5 y un 10%','5–10%'),
    'por-que-quesos-tienen-agujeros-ojos-fermentacion-defectos'=>array('Aberturas mecánicas','Mechanical openings'),
    'fermentos-lacticos-queso-que-son-cultivos-iniciadores-funcion'=>array('Cultivos iniciadores y cultivos de maduración','Starter and ripening cultures'),
    'como-se-sala-queso-salmuera-salado-seco-sabor-maduracion'=>array('Sal y seguridad microbiológica','Salt and microbial safety'),
    'desuerado-sineresis-queso-cuajada-pierde-suero-humedad'=>array('sinéresis','syneresis'),
    'que-quesos-funden-mejor-humedad-grasa-acidez-proteina'=>array('Maduración: más fusión, menos estiramiento','Ageing: more melt, less stretch'),
    'quesos-corteza-lavada-que-son-olor-microbiota-como-degustar'=>array('Brevibacterium linens','Brevibacterium linens'),
    'como-comprar-queso-online-formato-envio-conservacion-recibir'=>array('Vacío: útil para transporte','Vacuum packing is useful for transport')
);

foreach($articles as $article){
    $id=(int)$ids[(string)$article['slug']];
    $es=(string)get_post_field('post_content',$id);
    $en=(string)get_post_meta($id,'_en_US_post_content',true);
    $es_words=str_word_count(wp_strip_all_tags($es));
    $en_words=str_word_count(wp_strip_all_tags($en));

    if($es_words<780 || $en_words<650){
        throw new RuntimeException('Article too short '.$article['slug'].' ES='.$es_words.' EN='.$en_words);
    }
    if(count((array)$article['sections_es'])<6 || count((array)$article['sections_en'])<6){
        throw new RuntimeException('Not enough substantive sections: '.$article['slug']);
    }
    if(count((array)$article['sources'])<2){
        throw new RuntimeException('Not enough sources: '.$article['slug']);
    }
    if(false!==strpos($es,'<h2>Guías relacionadas</h2><ul></ul>') || false!==strpos($en,'<h2>Related guides</h2><ul></ul>')){
        throw new RuntimeException('Empty related block: '.$article['slug']);
    }
    if(empty($article['faq_es']) && false!==strpos($es,'<h2>Preguntas relacionadas</h2>')){
        throw new RuntimeException('Unexpected ES FAQ: '.$article['slug']);
    }
    if(empty($article['faq_en']) && false!==strpos($en,'<h2>Related questions</h2>')){
        throw new RuntimeException('Unexpected EN FAQ: '.$article['slug']);
    }
    foreach($forbidden_es as $bad){
        if(false!==strpos($es,$bad)){ throw new RuntimeException('Generic ES FAQ survived: '.$article['slug']); }
    }
    foreach($forbidden_en as $bad){
        if(false!==strpos($en,$bad)){ throw new RuntimeException('Generic EN FAQ survived: '.$article['slug']); }
    }
    if(isset($spotchecks[(string)$article['slug']])){
        $check=$spotchecks[(string)$article['slug']];
        if(false===strpos($es,$check[0]) || false===strpos($en,$check[1])){
            throw new RuntimeException('Spot check missing: '.$article['slug']);
        }
    }

    $metrics[(string)$article['slug']]=array(
        'es_words'=>$es_words,'en_words'=>$en_words,
        'related_es'=>false!==strpos($es,'<h2>Guías relacionadas</h2>'),
        'related_en'=>false!==strpos($en,'<h2>Related guides</h2>'),
        'faq_es'=>false!==strpos($es,'<h2>Preguntas relacionadas</h2>'),
        'faq_en'=>false!==strpos($en,'<h2>Related questions</h2>')
    );
}

// Phase 3b: all ten passed. Publish now.
$published=array();
foreach($articles as $article){
    $id=(int)$ids[(string)$article['slug']];
    if('publish'!==get_post_status($id)){
        $r=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
        if(is_wp_error($r)){ throw new RuntimeException('Publish failed: '.$article['slug']); }
    }
    clean_post_cache($id);
    $m=$metrics[(string)$article['slug']];
    $published[]=array(
        'position'=>(int)$article['pos'],
        'id'=>$id,
        'slug'=>(string)$article['slug'],
        'en_slug'=>(string)$article['en_slug'],
        'status'=>get_post_status($id),
        'es_words'=>$m['es_words'],
        'en_words'=>$m['en_words'],
        'related_es'=>$m['related_es'],
        'related_en'=>$m['related_en'],
        'faq_es'=>$m['faq_es'],
        'faq_en'=>$m['faq_en'],
        'image_id'=>(int)get_post_thumbnail_id($id)
    );
}

echo "EMDO_CHEESE_BATCH06_BEGIN\n";
echo wp_json_encode(array(
    'marker'=>EMDO_CHEESE_BATCH06_MARKER,
    'count'=>count($published),
    'category_id'=>(int)$term->term_id,
    'posts'=>$published
),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
echo "EMDO_CHEESE_BATCH06_END\n";
