<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_duck_final_norm( string $text ): string {
    $text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $text = remove_accents( mb_strtolower( $text, 'UTF-8' ) );
    $text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
    return trim( preg_replace( '/\s+/u', ' ', (string) $text ) );
}
function emdo_duck_final_words( string $html ): int {
    $text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
    if ( '' === $text ) return 0;
    preg_match_all( '/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u', $text, $m );
    return count( $m[0] );
}
function emdo_duck_final_paragraphs( string $html ): array {
    $out = array();
    if ( preg_match_all( '/<p\b[^>]*>(.*?)<\/p>/isu', $html, $m ) ) {
        foreach ( $m[1] as $raw ) {
            $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $raw ) ) );
            if ( mb_strlen( $plain, 'UTF-8' ) < 80 ) continue;
            $norm = emdo_duck_final_norm( $plain );
            if ( str_word_count( $norm ) < 12 ) continue;
            $out[] = array( 'plain'=>$plain, 'norm'=>$norm );
        }
    }
    return $out;
}
function emdo_duck_final_headings( string $html ): array {
    $out = array();
    if ( preg_match_all( '/<h([23])\b[^>]*>(.*?)<\/h\1>/isu', $html, $m, PREG_SET_ORDER ) ) {
        foreach ( $m as $row ) {
            $plain = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $row[2] ) ) );
            if ( '' === $plain ) continue;
            $out[] = array( 'level'=>(int)$row[1], 'plain'=>$plain, 'norm'=>emdo_duck_final_norm($plain) );
        }
    }
    return $out;
}
function emdo_duck_final_tokens( string $text ): array {
    $stop = array_flip(array(
        'que','con','para','por','una','uno','unos','unas','del','las','los','como','mas','este','esta','estos','estas',
        'entre','desde','sobre','pero','tambien','cuando','donde','cada','muy','sin','sus','son','ser','puede','pueden',
        'conviene','producto','productos','pato','carne','guia','como','hacer','hay','tener','tiene','tipo','tipos'
    ));
    $norm = emdo_duck_final_norm( $text );
    $parts = preg_split( '/\s+/u', $norm ) ?: array();
    return array_values( array_filter( $parts, static fn($w) => strlen($w) >= 3 && ! isset($stop[$w]) ) );
}
function emdo_duck_final_cosine( array $a, array $b ): float {
    $ca=array_count_values($a); $cb=array_count_values($b);
    $dot=0.0; $na=0.0; $nb=0.0;
    foreach($ca as $k=>$v){ $na += $v*$v; if(isset($cb[$k])) $dot += $v*$cb[$k]; }
    foreach($cb as $v){ $nb += $v*$v; }
    return ($na>0 && $nb>0) ? $dot/(sqrt($na)*sqrt($nb)) : 0.0;
}

$posts = get_posts(array(
    'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,
    'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck','orderby'=>'ID','order'=>'ASC',
));
if ( 60 !== count($posts) ) {
    throw new Exception('Expected 60 duck posts, found '.count($posts));
}

$rows=array(); $para_map=array(); $heading_map=array();
$forbidden=array(
    'detalles practicos para completar la guia',
    'antes de aplicar una regla fija conviene mirar el formato concreto que tenemos delante',
    'este punto merece atencion porque suele ser el que mas cambia el resultado final',
    'es una diferencia pequena sobre el papel pero muy visible cuando el producto llega al plato',
    'aqui conviene separar lo que pertenece al producto de lo que depende de la tecnica',
    'mas que memorizar una receta unica interesa comprender las variables que de verdad cambian el resultado',
    'en esta guia nos centramos en la intencion concreta de busqueda',
);
$forbidden_hits=array_fill_keys($forbidden,0);

foreach($posts as $post){
    $html=(string)$post->post_content;
    $paras=emdo_duck_final_paragraphs($html);
    $heads=emdo_duck_final_headings($html);
    foreach($paras as $p){
        $h=sha1($p['norm']);
        if(!isset($para_map[$h])) $para_map[$h]=array('text'=>$p['plain'],'norm'=>$p['norm'],'posts'=>array());
        $para_map[$h]['posts'][(int)$post->ID]=true;
    }
    foreach($heads as $hrow){
        $key=$hrow['level'].'|'.$hrow['norm'];
        if(!isset($heading_map[$key])) $heading_map[$key]=array('level'=>$hrow['level'],'text'=>$hrow['plain'],'posts'=>array());
        $heading_map[$key]['posts'][(int)$post->ID]=true;
    }
    $norm=emdo_duck_final_norm($html);
    $hits=array();
    foreach($forbidden as $phrase){
        $n=substr_count($norm,$phrase);
        if($n){$forbidden_hits[$phrase]+=$n;$hits[$phrase]=$n;}
    }
    preg_match_all('/<h2\b/i',$html,$h2);
    preg_match_all('/<h1\b/i',$html,$h1);
    preg_match_all('/<a\b[^>]+href=["\x{27}]([^"\x{27}]+)["\x{27}]/i',$html,$lm);
    $rows[] = array(
        'id'=>(int)$post->ID,
        'key'=>(string)get_post_meta($post->ID,'_emdo_seo_landing_key',true),
        'slug'=>(string)$post->post_name,
        'title'=>(string)$post->post_title,
        'words'=>emdo_duck_final_words($html),
        'h2'=>count($h2[0]),
        'h1_in_content'=>count($h1[0]),
        'long_paragraphs'=>count($paras),
        'stored_links'=>count($lm[1]??array()),
        'forbidden_hits'=>$hits,
        'tokens'=>emdo_duck_final_tokens($html),
    );
}

$exact_dupes=array();
foreach($para_map as $v){
    $ids=array_keys($v['posts']);
    if(count($ids)>=2){
        $exact_dupes[]=array('count'=>count($ids),'post_ids'=>$ids,'text'=>$v['text']);
    }
}
usort($exact_dupes,fn($a,$b)=>$b['count']<=>$a['count']);

$repeated_headings=array();
foreach($heading_map as $v){
    $ids=array_keys($v['posts']);
    if(count($ids)>=3){
        $repeated_headings[]=array('count'=>count($ids),'level'=>$v['level'],'post_ids'=>$ids,'text'=>$v['text']);
    }
}
usort($repeated_headings,fn($a,$b)=>$b['count']<=>$a['count']);

$pairs=array();
for($i=0;$i<count($rows);$i++){
    for($j=$i+1;$j<count($rows);$j++){
        $sim=emdo_duck_final_cosine($rows[$i]['tokens'],$rows[$j]['tokens']);
        if($sim>=0.72){
            $pairs[]=array(
                'similarity'=>round($sim,3),
                'a'=>$rows[$i]['slug'],'b'=>$rows[$j]['slug'],
                'title_a'=>$rows[$i]['title'],'title_b'=>$rows[$j]['title']
            );
        }
    }
}
usort($pairs,fn($a,$b)=>$b['similarity']<=>$a['similarity']);

$thin=array_values(array_filter($rows,fn($r)=>$r['words']<650));
$weak_structure=array_values(array_filter($rows,fn($r)=>$r['h2']<5));
$h1_violations=array_values(array_filter($rows,fn($r)=>$r['h1_in_content']>0));
$forbidden_post_count=count(array_filter($rows,fn($r)=>!empty($r['forbidden_hits'])));

$summary=array(
    'count'=>count($rows),
    'words'=>array(
        'min'=>min(array_column($rows,'words')),
        'avg'=>round(array_sum(array_column($rows,'words'))/count($rows),1),
        'max'=>max(array_column($rows,'words')),
    ),
    'h2'=>array(
        'min'=>min(array_column($rows,'h2')),
        'avg'=>round(array_sum(array_column($rows,'h2'))/count($rows),1),
        'max'=>max(array_column($rows,'h2')),
    ),
    'thin_under_650'=>count($thin),
    'weak_structure_under_5_h2'=>count($weak_structure),
    'h1_in_content'=>count($h1_violations),
    'forbidden_template_posts'=>$forbidden_post_count,
    'exact_long_duplicate_paragraph_groups'=>count($exact_dupes),
    'repeated_heading_groups_3plus'=>count($repeated_headings),
    'article_pairs_similarity_072plus'=>count($pairs),
);

foreach($rows as &$row){ unset($row['tokens']); } unset($row);

echo wp_json_encode(array(
    'batch'=>'20261001-duck-editorial-final-audit',
    'summary'=>$summary,
    'forbidden_hits'=>$forbidden_hits,
    'exact_duplicate_paragraphs'=>array_slice($exact_dupes,0,50),
    'repeated_headings'=>array_slice($repeated_headings,0,50),
    'high_similarity_pairs'=>array_slice($pairs,0,50),
    'thin_posts'=>$thin,
    'weak_structure_posts'=>$weak_structure,
    'h1_violations'=>$h1_violations,
    'posts'=>$rows,
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
