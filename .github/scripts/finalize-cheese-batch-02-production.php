<?php
/** Finalise cheese batch 02 after quality-gate stop. */
if ( ! defined('ABSPATH') ) { exit; }
const EMDO_CHEESE02 = '2026-09-01.cheese-02.v1';

$posts = get_posts(array(
    'post_type'=>'post',
    'post_status'=>array('draft','publish','pending','private'),
    'posts_per_page'=>20,
    'meta_key'=>'_emdo_cheese_batch02',
    'meta_value'=>EMDO_CHEESE02,
    'orderby'=>'ID',
    'order'=>'ASC',
));
if ( 10 !== count($posts) ) { throw new RuntimeException('Expected 10 batch posts, found '.count($posts)); }

// The only failed quality gate was Tetilla EN at 645 words. Expand it substantively,
// rather than lowering the editorial threshold.
$tetilla = null;
foreach ( $posts as $p ) {
    if ( 'queso-tetilla-dop-que-es-como-se-elabora-perfil' === $p->post_name ) { $tetilla = $p; break; }
}
if ( ! $tetilla instanceof WP_Post ) { throw new RuntimeException('Tetilla batch post not found'); }
$en = (string) get_post_meta($tetilla->ID,'_en_US_post_content',true);
$heading = '<h2>How moisture changes the way Tetilla should be handled</h2>';
if ( false === strpos($en,$heading) ) {
    $addition = $heading
        . '<p>Tetilla retains substantially more moisture than a long-aged hard cheese, and that difference is practical as well as sensory. Its soft paste feels supple and creamy because water remains an important part of the structure. Once the cheese is cut, the exposed face can therefore lose quality more quickly through drying, condensation or unwanted surface growth. Keep the opened cheese refrigerated according to the producer’s instructions, protect the cut surface and avoid leaving the whole piece at room temperature repeatedly.</p>'
        . '<p>For service, cut only the portion you plan to eat and allow that portion to lose its deepest refrigerator chill. This gives the fat and paste time to soften and makes the gentle dairy aroma easier to perceive. Compared with a mature pressed cheese, Tetilla is less about concentration and persistence and more about moisture, elasticity and a clean milk-led profile. That contrast is useful on a cheese board because it changes texture and rhythm without relying on greater intensity.</p>';
    $marker = '<h2>How to identify authentic Tetilla PDO</h2>';
    if ( false === strpos($en,$marker) ) { throw new RuntimeException('Tetilla insertion marker missing'); }
    $en = str_replace($marker,$addition.$marker,$en);
    update_post_meta($tetilla->ID,'_en_US_post_content',$en);
    clean_post_cache($tetilla->ID);
}

$results=array();
foreach ( $posts as $p ) {
    $es=(string)get_post_field('post_content',$p->ID);
    $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
    $es_words=str_word_count(wp_strip_all_tags($es));
    $en_words=str_word_count(wp_strip_all_tags($en));
    if ( $es_words < 780 || $en_words < 650 ) {
        throw new RuntimeException('Quality gate failed '.$p->post_name.' ES='.$es_words.' EN='.$en_words);
    }
    foreach ( array(
        array($es,'Guías relacionadas'),
        array($en,'Related guides'),
    ) as $check ) {
        $content=$check[0]; $label=$check[1];
        if ( false !== strpos($content,'<h2>'.$label.'</h2><ul></ul>') ) {
            throw new RuntimeException('Empty related block in '.$p->post_name.' '.$label);
        }
    }
    if ( '' === (string)get_post_meta($p->ID,'_en_US_post_title',true) || '' === (string)get_post_meta($p->ID,'_en_US_post_name',true) ) {
        throw new RuntimeException('Missing EN metadata for '.$p->post_name);
    }
    update_post_meta($p->ID,'_en_US_ready','1');
    update_post_meta($p->ID,'_en_US_published','1');
    $pub=wp_update_post(array('ID'=>$p->ID,'post_status'=>'publish'),true);
    if ( is_wp_error($pub) ) { throw new RuntimeException('Publish failed '.$p->post_name.': '.$pub->get_error_message()); }
    clean_post_cache($p->ID);
    $results[]=array(
        'id'=>(int)$p->ID,
        'slug'=>$p->post_name,
        'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),
        'status'=>get_post_status($p->ID),
        'es_words'=>$es_words,
        'en_words'=>$en_words,
        'related_es'=>substr_count($es,'<h2>Guías relacionadas</h2>'),
        'related_en'=>substr_count($en,'<h2>Related guides</h2>'),
    );
}

echo "EMDO_CHEESE02_FINAL_BEGIN\n";
echo wp_json_encode(array('count'=>count($results),'posts'=>$results),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_CHEESE02_FINAL_END\n";
