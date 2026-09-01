<?php
/**
 * Publish cheese authority batch 09: positions 81-90, Spanish + Falang en_US.
 * Editorial payload is gzip+base64, verified at three levels before WordPress writes.
 */
if ( ! defined('ABSPATH') ) { exit; }

const EMDO_CHEESE_BATCH09_MARKER = '2026-09-01.cheese-09.v1';
const EMDO_CHEESE_BATCH09_B64_LEN = 56992;
const EMDO_CHEESE_BATCH09_B64_SHA = 'a00a670a84d252e895aa6db7e7cf8d00811188b7f6a56b2fb901d5b42a78b464';
const EMDO_CHEESE_BATCH09_GZ_SHA  = '2f8dd86be400bcd4c47879b6b6912c8ca068c87aeebd114e9a4780620d2b0dca';
const EMDO_CHEESE_BATCH09_JSON_SHA = 'b6818260e040f09f8b42118a69f9822211b8d27a5c0e87e132958180013ec46d';

function emdo_cheese09_load_articles(): array {
    $b64='';
    foreach (array('00','01','02') as $part) {
        $file=__DIR__.'/cheese-batch-09-data-'.$part.'.b64';
        $raw=file_get_contents($file);
        if ($raw===false) { throw new RuntimeException('Could not read data chunk: '.basename($file)); }
        $b64.=preg_replace('/\s+/', '', $raw);
    }
    if (strlen($b64)!==EMDO_CHEESE_BATCH09_B64_LEN) {
        throw new RuntimeException('Base64 payload length mismatch: '.strlen($b64));
    }
    if (!hash_equals(EMDO_CHEESE_BATCH09_B64_SHA,hash('sha256',$b64))) {
        throw new RuntimeException('Base64 payload checksum mismatch');
    }
    $gz=base64_decode($b64,true);
    if ($gz===false) { throw new RuntimeException('Strict base64 decode failed'); }
    if (!hash_equals(EMDO_CHEESE_BATCH09_GZ_SHA,hash('sha256',$gz))) {
        throw new RuntimeException('Gzip payload checksum mismatch');
    }
    $json=gzdecode($gz);
    if ($json===false) { throw new RuntimeException('Gzip decode failed'); }
    if (!hash_equals(EMDO_CHEESE_BATCH09_JSON_SHA,hash('sha256',$json))) {
        throw new RuntimeException('JSON payload checksum mismatch');
    }
    $articles=json_decode($json,true,512,JSON_THROW_ON_ERROR);
    if (!is_array($articles) || count($articles)!==10) {
        throw new RuntimeException('Expected exactly 10 articles');
    }
    return $articles;
}

$articles=emdo_cheese09_load_articles();
$required=array('pos','title','slug','en_title','en_slug','excerpt','en_excerpt','lead_es','lead_en','facts_es','facts_en','sections_es','sections_en','faq_es','faq_en','conclusion_es','conclusion_en','related','sources');
$seen=array(); $seen_en=array(); $positions=array();
foreach ($articles as $article) {
    foreach ($required as $key) {
        if (!array_key_exists($key,$article)) { throw new RuntimeException('Missing '.$key.' in article data'); }
    }
    $slug=(string)$article['slug']; $en_slug=(string)$article['en_slug']; $pos=(int)$article['pos'];
    if ($slug==='' || $en_slug==='') { throw new RuntimeException('Empty slug in batch'); }
    if (isset($seen[$slug]) || isset($seen_en[$en_slug])) { throw new RuntimeException('Duplicate slug in batch'); }
    $seen[$slug]=1; $seen_en[$en_slug]=1; $positions[]=$pos;
}
sort($positions);
if (range(81,90)!==$positions) { throw new RuntimeException('Positions must be 81 through 90'); }

function emdo_cheese09_e(string $s): string { return esc_html($s); }

function emdo_cheese09_category(): WP_Term {
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

function emdo_cheese09_image(int $category_id): int {
    $ids=get_posts(array(
        'post_type'=>'post','post_status'=>'publish','posts_per_page'=>50,
        'fields'=>'ids','category__in'=>array($category_id)
    ));
    foreach ($ids as $id) {
        $thumb=(int)get_post_thumbnail_id($id);
        if ($thumb>0) { return $thumb; }
    }
    return 0;
}

function emdo_cheese09_related(array $slugs,bool $spanish): string {
    $items=array();
    foreach ($slugs as $slug) {
        $p=get_page_by_path((string)$slug,OBJECT,'post');
        if (!$p instanceof WP_Post) { continue; }
        $same_batch=EMDO_CHEESE_BATCH09_MARKER===(string)get_post_meta($p->ID,'_emdo_cheese_batch09',true);
        if ('publish'!==get_post_status($p) && !$same_batch) { continue; }
        if ($spanish) {
            $items[]='<li><a href="'.esc_url('/'.$p->post_name.'/').'">'.emdo_cheese09_e(get_the_title($p)).'</a></li>';
        } else {
            if ('1'!==(string)get_post_meta($p->ID,'_en_US_published',true) && !$same_batch) { continue; }
            $s=(string)get_post_meta($p->ID,'_en_US_post_name',true);
            $t=(string)get_post_meta($p->ID,'_en_US_post_title',true);
            if ($s==='' || $t==='') { continue; }
            $items[]='<li><a href="'.esc_url('/en/'.$s.'/').'">'.emdo_cheese09_e($t).'</a></li>';
        }
    }
    if (empty($items)) { return ''; }
    return ($spanish?'<h2>Guías relacionadas</h2>':'<h2>Related guides</h2>').'<ul>'.implode('',$items).'</ul>';
}

function emdo_cheese09_render(array $article,bool $spanish): string {
    $lead=$spanish?$article['lead_es']:$article['lead_en'];
    $facts=$spanish?$article['facts_es']:$article['facts_en'];
    $sections=$spanish?$article['sections_es']:$article['sections_en'];
    $faq=$spanish?$article['faq_es']:$article['faq_en'];
    $conclusion=$spanish?$article['conclusion_es']:$article['conclusion_en'];

    $out='<p>'.emdo_cheese09_e((string)$lead).'</p>';
    $out.=$spanish?'<h2>Resumen rápido</h2>':'<h2>Quick summary</h2>';
    $out.='<table><tbody>';
    foreach ($facts as $row) {
        if (!is_array($row) || count($row)<2) { continue; }
        $out.='<tr><th>'.emdo_cheese09_e((string)$row[0]).'</th><td>'.emdo_cheese09_e((string)$row[1]).'</td></tr>';
    }
    $out.='</tbody></table>';

    foreach ($sections as $section) {
        if (!is_array($section) || count($section)<2) { continue; }
        $out.='<h2>'.emdo_cheese09_e((string)$section[0]).'</h2>';
        foreach (preg_split('/\n\n+/',(string)$section[1]) as $paragraph) {
            if (trim($paragraph)!=='') { $out.='<p>'.emdo_cheese09_e(trim($paragraph)).'</p>'; }
        }
    }

    if (!empty($faq)) {
        $out.=$spanish?'<h2>Preguntas relacionadas</h2>':'<h2>Related questions</h2>';
        foreach ($faq as $qa) {
            if (!is_array($qa) || count($qa)<2) { continue; }
            $out.='<h3>'.emdo_cheese09_e((string)$qa[0]).'</h3><p>'.emdo_cheese09_e((string)$qa[1]).'</p>';
        }
    }

    $out.=emdo_cheese09_related((array)$article['related'],$spanish);
    $out.=$spanish?'<h2>Conclusión</h2>':'<h2>Bottom line</h2>';
    $out.='<p>'.emdo_cheese09_e((string)$conclusion).'</p>';
    $out.=$spanish?'<h2>Fuentes y referencias</h2>':'<h2>Sources and references</h2>';
    $out.='<ul>';
    foreach ((array)$article['sources'] as $src) {
        $host=(string)wp_parse_url((string)$src,PHP_URL_HOST);
        $out.='<li><a href="'.esc_url((string)$src).'" rel="noopener noreferrer">'.emdo_cheese09_e($host).'</a></li>';
    }
    $out.='</ul>';
    return $out;
}

function emdo_cheese09_meta_title(string $s): string {
    if (function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>62) {
        return rtrim(mb_substr($s,0,59,'UTF-8')).'…';
    }
    return $s;
}
function emdo_cheese09_meta_description(string $s): string {
    if (function_exists('mb_strlen') && mb_strlen($s,'UTF-8')>158) {
        return rtrim(mb_substr($s,0,155,'UTF-8')).'…';
    }
    return $s;
}

$term=emdo_cheese09_category();
$image_id=emdo_cheese09_image((int)$term->term_id);
$sample=get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,
    'fields'=>'ids','category__in'=>array((int)$term->term_id)
));
$author=!empty($sample)?(int)get_post_field('post_author',(int)$sample[0]):1;
if ($author<=0) { $author=1; }

// Phase 1: reserve every slug and bilingual route before rendering.
$ids=array();
foreach ($articles as $article) {
    $existing=get_page_by_path((string)$article['slug'],OBJECT,'post');
    if ($existing instanceof WP_Post) {
        if (EMDO_CHEESE_BATCH09_MARKER!==(string)get_post_meta($existing->ID,'_emdo_cheese_batch09',true)) {
            throw new RuntimeException('Refusing to overwrite existing non-batch post: '.$article['slug']);
        }
        $id=(int)$existing->ID;
    } else {
        $created=wp_insert_post(wp_slash(array(
            'post_type'=>'post',
            'post_status'=>'draft',
            'post_title'=>(string)$article['title'],
            'post_name'=>(string)$article['slug'],
            'post_excerpt'=>(string)$article['excerpt'],
            'post_author'=>$author,
            'post_category'=>array((int)$term->term_id),
            'comment_status'=>'closed',
            'ping_status'=>'closed'
        )),true);
        if (is_wp_error($created) || (int)$created<=0) {
            throw new RuntimeException('Could not reserve slug: '.$article['slug']);
        }
        $id=(int)$created;
    }
    update_post_meta($id,'_emdo_cheese_batch09',EMDO_CHEESE_BATCH09_MARKER);
    update_post_meta($id,'_emdo_editorial_position',(string)(int)$article['pos']);
    update_post_meta($id,'_en_US_post_title',(string)$article['en_title']);
    update_post_meta($id,'_en_US_post_name',(string)$article['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',(string)$article['en_excerpt']);
    update_post_meta($id,'_en_US_ready','1');
    update_post_meta($id,'_en_US_published','1');
    $ids[(string)$article['slug']]=$id;
}

// Phase 2: render Spanish and English while drafts stay unpublished on first run.
foreach ($articles as $article) {
    $id=(int)$ids[(string)$article['slug']];
    $es=emdo_cheese09_render($article,true);
    $en=emdo_cheese09_render($article,false);
    $updated=wp_update_post(wp_slash(array(
        'ID'=>$id,
        'post_title'=>(string)$article['title'],
        'post_name'=>(string)$article['slug'],
        'post_excerpt'=>(string)$article['excerpt'],
        'post_content'=>$es,
        'post_author'=>$author,
        'post_category'=>array((int)$term->term_id),
        'comment_status'=>'closed',
        'ping_status'=>'closed'
    )),true);
    if (is_wp_error($updated)) { throw new RuntimeException('Spanish render failed: '.$article['slug']); }
    update_post_meta($id,'_en_US_post_content',$en);
    update_post_meta($id,'_emdo_seo_title',emdo_cheese09_meta_title((string)$article['title']));
    update_post_meta($id,'_emdo_seo_description',emdo_cheese09_meta_description((string)$article['excerpt']));
    update_post_meta($id,'_en_US_seo_title',emdo_cheese09_meta_title((string)$article['en_title']));
    update_post_meta($id,'_en_US_seo_description',emdo_cheese09_meta_description((string)$article['en_excerpt']));
    if ($image_id>0) { set_post_thumbnail($id,$image_id); }
    clean_post_cache($id);
}

// Phase 3a: preflight the entire batch before publishing any new draft.
$metrics=array();
$forbidden_es=array(
    '¿Más curación significa siempre mejor queso?',
    '¿Es mejor comprar una pieza grande?'
);
$forbidden_en=array(
    'Does longer ageing always mean better cheese?',
    'Is it better to buy a large wheel?'
);
$spotchecks=array(
    'como-madura-queso-temperatura-humedad-microbiota-tiempo'=>array('Temperatura: acelerar demasiado','Temperature: faster is not always better'),
    'lavado-cuajada-queso-que-es-como-cambia-sabor-textura'=>array('Lavado de cuajada y corteza lavada','Washed curd versus washed rind'),
    'cuantos-litros-leche-hacen-falta-1-kg-queso-rendimiento'=>array('Pastas prensadas y cocidas','Pressed and cooked cheeses'),
    'por-que-queso-blanco-amarillo-naranja-leche-maduracion-colorantes'=>array('Colorantes autorizados','Permitted colours'),
    'como-transportar-queso-viaje-coche-avion-cadena-frio'=>array('Transporte en coche','Car travel')
);
foreach ($articles as $article) {
    $slug=(string)$article['slug'];
    $id=(int)$ids[$slug];
    $es=(string)get_post_field('post_content',$id);
    $en=(string)get_post_meta($id,'_en_US_post_content',true);
    $es_words=str_word_count(wp_strip_all_tags($es));
    $en_words=str_word_count(wp_strip_all_tags($en));

    if ($es_words<780 || $en_words<650) {
        throw new RuntimeException('Article too short '.$slug.' ES='.$es_words.' EN='.$en_words);
    }
    if (count((array)$article['sections_es'])<6 || count((array)$article['sections_en'])<6) {
        throw new RuntimeException('Not enough substantive sections: '.$slug);
    }
    if (count((array)$article['sources'])<2) {
        throw new RuntimeException('Not enough sources: '.$slug);
    }
    if (false!==strpos($es,'<h2>Guías relacionadas</h2><ul></ul>') ||
        false!==strpos($en,'<h2>Related guides</h2><ul></ul>')) {
        throw new RuntimeException('Empty related guides: '.$slug);
    }
    foreach ($forbidden_es as $bad) {
        if (false!==strpos($es,$bad)) { throw new RuntimeException('Generic ES FAQ survived: '.$slug); }
    }
    foreach ($forbidden_en as $bad) {
        if (false!==strpos($en,$bad)) { throw new RuntimeException('Generic EN FAQ survived: '.$slug); }
    }
    if (isset($spotchecks[$slug])) {
        if (false===strpos($es,$spotchecks[$slug][0]) || false===strpos($en,$spotchecks[$slug][1])) {
            throw new RuntimeException('Spotcheck missing: '.$slug);
        }
    }

    $metrics[$slug]=array(
        'es_words'=>$es_words,
        'en_words'=>$en_words,
        'related_es'=>false!==strpos($es,'<h2>Guías relacionadas</h2>'),
        'related_en'=>false!==strpos($en,'<h2>Related guides</h2>'),
        'faq_es'=>false!==strpos($es,'<h2>Preguntas relacionadas</h2>'),
        'faq_en'=>false!==strpos($en,'<h2>Related questions</h2>')
    );
}

// Phase 3b: all ten passed; publish the entire batch.
foreach ($articles as $article) {
    $id=(int)$ids[(string)$article['slug']];
    if ('publish'!==get_post_status($id)) {
        $published=wp_update_post(array('ID'=>$id,'post_status'=>'publish'),true);
        if (is_wp_error($published)) { throw new RuntimeException('Publish failed: '.$article['slug']); }
    }
    clean_post_cache($id);
}

$posts=array();
foreach ($articles as $article) {
    $slug=(string)$article['slug'];
    $id=(int)$ids[$slug];
    $m=$metrics[$slug];
    $posts[]=array(
        'position'=>(int)$article['pos'],
        'id'=>$id,
        'slug'=>$slug,
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

echo "EMDO_CHEESE_BATCH09_BEGIN\n";
echo wp_json_encode(array(
    'marker'=>EMDO_CHEESE_BATCH09_MARKER,
    'count'=>count($posts),
    'category_id'=>(int)$term->term_id,
    'posts'=>$posts
),JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
echo "EMDO_CHEESE_BATCH09_END\n";
