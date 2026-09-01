<?php
/** Publish cheese authority batch 07: positions 61-70, Spanish + Falang en_US. */
if ( ! defined('ABSPATH') ) { exit; }
const EMDO_CHEESE_BATCH07_MARKER = '2026-09-01.cheese-07.v1';

function emdo_cheese07_load_article(int $position): array {
    $file = __DIR__ . '/cheese-batch-07-' . $position . '.json';
    $raw = file_get_contents($file);
    if ($raw === false) { throw new RuntimeException('Could not read editorial data: ' . basename($file)); }
    $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data)) { throw new RuntimeException('Invalid editorial data: ' . basename($file)); }
    if ((int)($data['pos'] ?? 0) !== $position) { throw new RuntimeException('Editorial position mismatch: ' . basename($file)); }
    return $data;
}
$articles = array();
foreach (range(61,70) as $position) { $articles[] = emdo_cheese07_load_article($position); }
if (10 !== count($articles)) { throw new RuntimeException('Expected exactly 10 articles, got ' . count($articles)); }

$required = array('pos','title','slug','en_title','en_slug','excerpt','en_excerpt','lead_es','lead_en','facts_es','facts_en','sections_es','sections_en','faq_es','faq_en','conclusion_es','conclusion_en','related','sources');
$seen=array(); $seen_en=array(); $positions=array();
foreach ($articles as $article) {
    foreach ($required as $key) { if (!array_key_exists($key,$article)) { throw new RuntimeException('Missing '.$key); } }
    $slug=(string)$article['slug']; $en_slug=(string)$article['en_slug']; $pos=(int)$article['pos'];
    if (isset($seen[$slug]) || isset($seen_en[$en_slug])) { throw new RuntimeException('Duplicate slug in batch'); }
    $seen[$slug]=1; $seen_en[$en_slug]=1; $positions[]=$pos;
}
sort($positions);
if (range(61,70) !== $positions) { throw new RuntimeException('Positions must be 61 through 70'); }

function emdo_cheese07_e(string $s): string { return esc_html($s); }
function emdo_cheese07_category(): WP_Term {
    $term=get_category_by_slug('quesos');
    if (!$term instanceof WP_Term) {
        $created=wp_insert_term('Quesos','category',array('slug'=>'quesos','description'=>'Guías de compra, conservación, elaboración y cultura del queso.'));
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
function emdo_cheese07_image(int $category_id): int {
    $ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>50,'fields'=>'ids','category__in'=>array($category_id)));
    foreach ($ids as $id) { $thumb=(int)get_post_thumbnail_id($id); if ($thumb>0) { return $thumb; } }
    return 0;
}
function emdo_cheese07_related(array $slugs,bool $spanish): string {
    $items=array();
    foreach ($slugs as $slug) {
        $p=get_page_by_path((string)$slug,OBJECT,'post');
        if (!$p instanceof WP_Post) { continue; }
        $same_batch=EMDO_CHEESE_BATCH07_MARKER === (string)get_post_meta($p->ID,'_emdo_cheese_batch07',true);
        if ('publish' !== get_post_status($p) && !$same_batch) { continue; }
        if ($spanish) {
            $items[]='<li><a href="'.esc_url('/'.$p->post_name.'/').'">'.emdo_cheese07_e(get_the_title($p)).'</a></li>';
        } else {
            if ('1' !== (string)get_post_meta($p->ID,'_en_US_published',true) && !$same_batch) { continue; }
            $s=(string)get_post_meta($p->ID,'_en_US_post_name',true);
            $t=(string)get_post_meta($p->ID,'_en_US_post_title',true);
            if ($s==='' || $t==='') { continue; }
            $items[]='<li><a href="'.esc_url('/en/'.$s.'/').'">'.emdo_cheese07_e($t).'</a></li>';
        }
    }
    if (empty($items)) { return ''; }
    return ($spanish?'<h2>Guías relacionadas</h2>':'<h2>Related guides</h2>').'<ul>'.implode('',$items).'</ul>';
}
function emdo_cheese07_render(array $article,bool $spanish): string {
    $lead=$spanish?$article['lead_es']:$article['lead_en'];
    $facts=$spanish?$article['facts_es']:$article['facts_en'];
    $sections=$spanish?$article['sections_es']:$article['sections_en'];
    $faq=$spanish?$article['faq_es']:$article['faq_en'];
    $conclusion=$spanish?$article['conclusion_es']:$article['conclusion_en'];
    $out='<p>'.emdo_cheese07_e((string)$lead).'</p>';
    $out.=$spanish?'<h2>Resumen rápido</h2>':'<h2>Quick summary</h2>';
    $out.='<table><tbody>';
    foreach ($facts as $row) { $out.='<tr><th>'.emdo_cheese07_e((string)$row[0]).'</th><td>'.emdo_cheese07_e((string)$row[1]).'</td></tr>'; }
    $out.='</tbody></table>';
    foreach ($sections as $section) {
        $out.='<h2>'.emdo_cheese07_e((string)$section[0]).'</h2>';
        foreach (preg_split('/\n\n+/',(string)$section[1]) as $paragraph) {
            if (trim($paragraph)!=='') { $out.='<p>'.emdo_cheese07_e(trim($paragraph)).'</p>'; }
        }
    }
    if (!empty($faq)) {
        $out.=$spanish?'<h2>Preguntas relacionadas</h2>':'<h2>Related questions</h2>';
        foreach ($faq as $qa) { $out.='<h3>'.emdo_cheese07_e((string)$qa[0]).'</h3><p>'.emdo_cheese07_e((string)$qa[1]).'</p>'; }
    }
    $out.=emdo_cheese07_related((array)$article['related'],$spanish);
    $out.=$spanish?'<h2>Conclusión</h2>':'<h2>Bottom line</h2>';
    $out.='<p>'.emdo_cheese07_e((string)$conclusion).'</p>';
    $out.=$spanish?'<h2>Fuentes y referencias</h2>':'<h2>Sources and references</h2>';
    $out.='<ul>';
    foreach ((array)$article['sources'] as $src) {
        $host=(string)wp_parse_url((string)$src,PHP_URL_HOST);
        $out.='<li><a href="'.esc_url((string)$src).'" rel="noopener noreferrer">'.emdo_cheese07_e($host).'</a></li>';
    }
    $out.='</ul>';
    return $out;
}
function emdo_cheese07_meta_title(string $s): string {
    if (function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>62) { return rtrim(mb_substr($s,0,59,'UTF-8')).'…'; }
    return $s;
}
function emdo_cheese07_meta_description(string $s): string {
    if (function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>158) { return rtrim(mb_substr($s,0,155,'UTF-8')).'…'; }
    return $s;
}

$term=emdo_cheese07_category();
$image_id=emdo_cheese07_image((int)$term->term_id);
$sample=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','category__in'=>array((int)$term->term_id)));
$author=!empty($sample)?(int)get_post_field('post_author',(int)$sample[0]):1;
if ($author<=0) { $author=1; }

// Phase 1: reserve all slugs and bilingual metadata.
$ids=array();
foreach ($articles as $article) {
    $existing=get_page_by_path((string)$article['slug'],OBJECT,'post');
    if ($existing instanceof WP_Post) {
        if (EMDO_CHEESE_BATCH07_MARKER !== (string)get_post_meta($existing->ID,'_emdo_cheese_batch07',true)) {
            throw new RuntimeException('Refusing to overwrite existing non-batch post: '.$article['slug']);
        }
        $id=(int)$existing->ID;
    } else {
        $created=wp_insert_post(wp_slash(array(
            'post_type'=>'post','post_status'=>'draft','post_title'=>(string)$article['title'],'post_name'=>(string)$article['slug'],
            'post_excerpt'=>(string)$article['excerpt'],'post_author'=>$author,'post_category'=>array((int)$term->term_id),
            'comment_status'=>'closed','ping_status'=>'closed'
        )),true);
        if (is_wp_error($created) || (int)$created<=0) { throw new RuntimeException('Could not reserve slug: '.$article['slug']); }
        $id=(int)$created;
    }
    update_post_meta($id,'_emdo_cheese_batch07',EMDO_CHEESE_BATCH07_MARKER);
    update_post_meta($id,'_emdo_editorial_position',(string)(int)$article['pos']);
    update_post_meta($id,'_en_US_post_title',(string)$article['en_title']);
    update_post_meta($id,'_en_US_post_name',(string)$article['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$article['en_excerpt']);
    update_post_meta($id,'_en_US_ready','1');
    update_post_meta($id,'_en_US_published','1');
    $ids[(string)$article['slug']]=$id;
}

// Phase 2: render all content while still draft/preserved status.
foreach ($articles as $article) {
    $id=(int)$ids[(string)$article['slug']];
    $es=emdo_cheese07_render($article,true);
    $en=emdo_cheese07_render($article,false);
    $updated=wp_update_post(wp_slash(array(
        'ID'=>$id,'post_title'=>(string)$article['title'],'post_name'=>(string)$article['slug'],
        'post_excerpt'=>(string)$article['excerpt'],'post_content'=>$es,'post_author'=>$author,
        'post_category'=>array((int)$term->term_id),'comment_status'=>'closed','ping_status'=>'closed'
    )),true);
    if (is_wp_error($updated)) { throw new RuntimeException('Spanish render failed: '.$article['slug']); }
    update_post_meta($id,'_en_US_post_content',$en);
    update_post_meta($id,'_emdo_seo_title',emdo_cheese07_meta_title((string)$article['title']));
    update_post_meta($id,'_emdo_seo_description',emdo_cheese07_meta_description((string)$article['excerpt']));
    update_post_meta($id,'_en_US_seo_title',emdo_cheese07_meta_title((string)$article['en_title']));
    update_post_meta($id,'_en_US_seo_description',emdo_cheese07_meta_description((string)$article['en_excerpt']));
    if ($image_id>0) { set_post_thumbnail($id,$image_id); }
    clean_post_cache($id);
}

// Phase 3a: whole-batch editorial preflight.
$metrics=array();
$forbidden_es=array('¿Más curación significa siempre mejor queso?','¿Es mejor comprar una pieza grande?');
$forbidden_en=array('Does longer ageing always mean better cheese?','Is it better to buy a large wheel?');
$spotchecks=array(
    'quesos-asturias-cabrales-gamoneu-afuega-pitu-casin-beyos-guia'=>array('Un territorio relativamente pequeño','relatively small territory'),
    'quesos-canarias-majorero-palmero-flor-guia-guia'=>array('tres nombres que no son equivalentes','three protected names'),
    'queso-fresco-que-es-elaboracion-conservacion-diferencias-madurado'=>array('Por qué dura menos','Why it has a shorter shelf life'),
    'quesos-corteza-florida-moho-blanco-como-se-forma-comer-corteza'=>array('Diferencia entre flora intencionada','Intentional flora versus accidental mould'),
    'quesos-pasta-hilada-que-son-estirar-cuajada-fundido'=>array('El papel del pH y del calcio','The role of pH and calcium'),
    'queso-envasado-vacio-conservar-abrir-olor-textura'=>array('Por qué puede oler fuerte al abrir','Why cheese may smell stronger just after opening')
);
foreach ($articles as $article) {
    $id=(int)$ids[(string)$article['slug']];
    $es=(string)get_post_field('post_content',$id);
    $en=(string)get_post_meta($id,'_en_US_post_content',true);
    $es_words=str_word_count(wp_strip_all_tags($es));
    $en_words=str_word_count(wp_strip_all_tags($en));
    if ($es_words<780 || $en_words<650) { throw new RuntimeException('Article too short '.$article['slug'].' ES='.$es_words.' EN='.$en_words); }
    if (count((array)$article['sections_es'])<6 || count((array)$article['sections_en'])<6) { throw new RuntimeException('Not enough sections: '.$article['slug']); }
    if (count((array)$article['sources'])<2) { throw new RuntimeException('Not enough sources: '.$article['slug']); }
    if (false!==strpos($es,'<h2>Guías relacionadas</h2><ul></ul>') || false!==strpos($en,'<h2>Related guides</h2><ul></ul>')) {
        throw new RuntimeException('Empty related guides: '.$article['slug']);
    }
    foreach ($forbidden_es as $bad) { if (false!==strpos($es,$bad)) { throw new RuntimeException('Generic ES FAQ survived: '.$article['slug']); } }
    foreach ($forbidden_en as $bad) { if (false!==strpos($en,$bad)) { throw new RuntimeException('Generic EN FAQ survived: '.$article['slug']); } }
    if (isset($spotchecks[(string)$article['slug']])) {
        if (false===strpos($es,$spotchecks[(string)$article['slug']][0]) || false===strpos($en,$spotchecks[(string)$article['slug']][1])) {
            throw new RuntimeException('Spotcheck missing: '.$article['slug']);
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

// Phase 3b: only after all ten pass, publish all ten.
foreach ($articles as $article) {
    $id=(int)$ids[(string)$article['slug']];
    $published=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
    if (is_wp_error($published)) { throw new RuntimeException('Publish failed: '.$article['slug']); }
    clean_post_cache($id);
}

$out=array('marker'=>EMDO_CHEESE_BATCH07_MARKER,'count'=>10,'category_id'=>(int)$term->term_id,'posts'=>array());
foreach ($articles as $article) {
    $id=(int)$ids[(string)$article['slug']];
    $m=$metrics[(string)$article['slug']];
    $out['posts'][]=array(
        'position'=>(int)$article['pos'],'id'=>$id,'slug'=>(string)$article['slug'],'en_slug'=>(string)$article['en_slug'],
        'status'=>get_post_status($id),'es_words'=>$m['es_words'],'en_words'=>$m['en_words'],
        'related_es'=>$m['related_es'],'related_en'=>$m['related_en'],'faq_es'=>$m['faq_es'],'faq_en'=>$m['faq_en'],
        'image_id'=>(int)get_post_thumbnail_id($id)
    );
}
echo "EMDO_CHEESE_BATCH07_BEGIN\n";
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_CHEESE_BATCH07_END\n";
