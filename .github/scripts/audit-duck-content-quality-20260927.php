<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_audit_words( string $html ): array {
    $text = mb_strtolower( wp_strip_all_tags( strip_shortcodes( $html ) ), 'UTF-8' );
    preg_match_all('/[\\p{L}\\p{M}]{3,}/u', $text, $m);
    $stop = array_flip(array('que','con','para','por','una','uno','unos','unas','del','las','los','como','más','mas','este','esta','estos','estas','entre','desde','sobre','pero','también','tambien','cuando','donde','cada','muy','sin','sus','son','ser','puede','pueden','conviene','producto','productos','pato','carne'));
    $out=[];
    foreach($m[0] as $w){ if(!isset($stop[$w])) $out[]=$w; }
    return $out;
}
function emdo_audit_paragraphs( string $html ): array {
    $out=[];
    if ( preg_match_all('/<p\\b[^>]*>(.*?)<\\/p>/isu', $html, $m) ) {
        foreach($m[1] as $p){
            $t=mb_strtolower(trim(preg_replace('/\\s+/u',' ',wp_strip_all_tags($p))),'UTF-8');
            if(mb_strlen($t,'UTF-8')>=90) $out[]=$t;
        }
    }
    return $out;
}
function emdo_audit_similarity(array $a,array $b): float {
    $ca=array_count_values($a); $cb=array_count_values($b);
    $dot=0.0; $na=0.0; $nb=0.0;
    foreach($ca as $k=>$v){ $na += $v*$v; if(isset($cb[$k])) $dot += $v*$cb[$k]; }
    foreach($cb as $v){ $nb += $v*$v; }
    if($na<=0||$nb<=0) return 0.0;
    return $dot/(sqrt($na)*sqrt($nb));
}
function emdo_count_words( string $html ): int {
    $t=trim(preg_replace('/\\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));
    if($t==='') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u',$t,$m);
    return count($m[0]);
}

$ids=get_posts(array(
 'post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
 'meta_key'=>'_emdo_blog_cluster','meta_value'=>'duck',
 'orderby'=>'ID','order'=>'ASC',
 'emdo_include_hidden_blog_islands'=>true,
));
if(count($ids)!==60) throw new Exception('Expected 60 duck posts, found '.count($ids));

$posts=[]; $paraFreq=[];
foreach($ids as $id){
  $html=(string)get_post_field('post_content',$id);
  $paras=emdo_audit_paragraphs($html);
  foreach(array_unique($paras) as $p){ $h=sha1($p); $paraFreq[$h]=($paraFreq[$h]??0)+1; }
  preg_match_all('/<h2\\b[^>]*>/i',$html,$h2m);
  preg_match_all('/<h3\\b[^>]*>/i',$html,$h3m);
  preg_match_all('/<a\\b[^>]+href=["\\x{27}]([^"\\x{27}]+)["\\x{27}]/i',$html,$lm);
  $posts[$id]=array(
    'id'=>(int)$id,'title'=>get_the_title($id),'slug'=>(string)get_post_field('post_name',$id),
    'words'=>emdo_count_words($html),'h2'=>count($h2m[0]),'h3'=>count($h3m[0]),
    'links'=>$lm[1]??[],'paras'=>$paras,'tokens'=>emdo_audit_words($html)
  );
}

$clusterUrls=[];
foreach($posts as $p){ $clusterUrls['/'.trim($p['slug'],'/').'/']=true; }

$results=[];
foreach($posts as $id=>$p){
  $shared=0; $all=count($p['paras']); $sharedAll=0; $sharedMany=0;
  foreach($p['paras'] as $para){ $f=$paraFreq[sha1($para)]??1; if($f>=2)$sharedAll++; if($f>=10)$sharedMany++; }
  $internal=0;
  foreach($p['links'] as $u){
     $path=parse_url($u,PHP_URL_PATH);
     if(is_string($path) && isset($clusterUrls['/'.trim($path,'/').'/'])) $internal++;
  }
  $results[$id]=array(
    'id'=>$p['id'],'title'=>$p['title'],'slug'=>$p['slug'],'words'=>$p['words'],'h2'=>$p['h2'],'h3'=>$p['h3'],
    'paragraphs'=>$all,'shared_paragraphs_2plus'=>$sharedAll,'shared_paragraphs_10plus'=>$sharedMany,
    'shared_ratio_2plus'=>$all?round($sharedAll/$all,3):0,
    'shared_ratio_10plus'=>$all?round($sharedMany/$all,3):0,
    'cluster_internal_links'=>$internal
  );
}

$pairs=[];
$keys=array_keys($posts);
for($i=0;$i<count($keys);$i++){
  for($j=$i+1;$j<count($keys);$j++){
    $a=$posts[$keys[$i]]; $b=$posts[$keys[$j]];
    $sim=emdo_audit_similarity($a['tokens'],$b['tokens']);
    if($sim>=0.62){
      $pairs[]=array('a'=>$a['slug'],'b'=>$b['slug'],'similarity'=>round($sim,3),'title_a'=>$a['title'],'title_b'=>$b['title']);
    }
  }
}
usort($pairs,fn($x,$y)=>$y['similarity']<=>$x['similarity']);

$dupeFreq=array_values($paraFreq);
$repeated2=count(array_filter($dupeFreq,fn($n)=>$n>=2));
$repeated10=count(array_filter($dupeFreq,fn($n)=>$n>=10));
$repeated50=count(array_filter($dupeFreq,fn($n)=>$n>=50));

$avg=function($field)use($results){$v=array_column($results,$field); return round(array_sum($v)/count($v),3);};
$min=function($field)use($results){return min(array_column($results,$field));};
$max=function($field)use($results){return max(array_column($results,$field));};

$summary=array(
  'count'=>count($results),
  'words'=>array('min'=>$min('words'),'avg'=>$avg('words'),'max'=>$max('words')),
  'h2'=>array('min'=>$min('h2'),'avg'=>$avg('h2'),'max'=>$max('h2')),
  'h3'=>array('min'=>$min('h3'),'avg'=>$avg('h3'),'max'=>$max('h3')),
  'cluster_internal_links'=>array('min'=>$min('cluster_internal_links'),'avg'=>$avg('cluster_internal_links'),'max'=>$max('cluster_internal_links')),
  'shared_ratio_2plus'=>array('min'=>$min('shared_ratio_2plus'),'avg'=>$avg('shared_ratio_2plus'),'max'=>$max('shared_ratio_2plus')),
  'shared_ratio_10plus'=>array('min'=>$min('shared_ratio_10plus'),'avg'=>$avg('shared_ratio_10plus'),'max'=>$max('shared_ratio_10plus')),
  'unique_long_paragraph_hashes'=>count($paraFreq),
  'repeated_long_paragraphs_2plus'=>$repeated2,
  'repeated_long_paragraphs_10plus'=>$repeated10,
  'repeated_long_paragraphs_50plus'=>$repeated50,
  'high_similarity_pair_count'=>count($pairs),
  'top_similarity_pairs'=>array_slice($pairs,0,20),
);

echo wp_json_encode(array('summary'=>$summary,'posts'=>array_values($results)),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
