<?php
// Read-only semantic overlap audit for authority blog posts.
function emdo_norm_words($text) {
    $text = html_entity_decode(wp_strip_all_tags((string)$text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = remove_accents(mb_strtolower($text, 'UTF-8'));
    $text = preg_replace('/[^a-z0-9áéíóúüñ]+/u', ' ', $text);
    $parts = preg_split('/\s+/u', trim($text));
    $stop = array_flip(explode(' ', 'a al algo algunos ante antes como con contra cual cuando de del desde donde dos el ella ellas ellos en entre era es esa ese eso esta estas este estos fue ha hay la las le lo los mas me mi muy no o para pero por porque que se segun si sin sobre son su sus te un una unas uno unos y ya que qué cómo cual cuáles cuando dónde por qué')); 
    $out=[];
    foreach ($parts as $w) {
        if (strlen($w) < 3 || isset($stop[$w])) continue;
        $out[]=$w;
    }
    return $out;
}
function emdo_freq($words) { $f=[]; foreach($words as $w) $f[$w]=($f[$w]??0)+1; return $f; }
function emdo_cosine($a,$b) {
    if(!$a||!$b) return 0.0;
    $dot=0.0; $na=0.0; $nb=0.0;
    foreach($a as $k=>$v){$na+=$v*$v;if(isset($b[$k]))$dot+=$v*$b[$k];}
    foreach($b as $v)$nb+=$v*$v;
    return ($na>0&&$nb>0)?$dot/(sqrt($na)*sqrt($nb)):0.0;
}
function emdo_jaccard_set($wa,$wb) {
    $a=array_fill_keys($wa,true);$b=array_fill_keys($wb,true);
    if(!$a||!$b)return 0.0;$inter=count(array_intersect_key($a,$b));$union=count($a)+count($b)-$inter;return $union?$inter/$union:0.0;
}
function emdo_headings($html) {
    preg_match_all('/<h[23][^>]*>(.*?)<\/h[23]>/is',(string)$html,$m);
    return array_values(array_filter(array_map(fn($x)=>trim(wp_strip_all_tags($x)),$m[1]??[])));
}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);
$data=[];
foreach($posts as $p){
    $tw=emdo_norm_words($p->post_title);
    $cw=emdo_norm_words($p->post_content);
    $data[$p->ID]=[
      'id'=>$p->ID,'title'=>$p->post_title,'slug'=>$p->post_name,
      'tf'=>emdo_freq($tw),'cf'=>emdo_freq($cw), 'tw'=>$tw,
      'headings'=>emdo_headings($p->post_content), 'words'=>count($cw)
    ];
}
$new=array_values(array_filter(array_keys($data),fn($id)=>$id>=14081 && $id<=14101));
$authority=array_values(array_filter(array_keys($data),fn($id)=>$id>=13852 && $id<=14101));

echo "===NEW_BATCH_EXACT_DUPLICATE_CHECK===\n";
foreach($new as $id){
    $x=$data[$id];$same=[];
    foreach($data as $oid=>$o){if($oid==$id)continue;if($o['slug']===$x['slug']||remove_accents(mb_strtolower($o['title']))===remove_accents(mb_strtolower($x['title'])))$same[]=$oid;}
    echo json_encode(['id'=>$id,'title'=>$x['title'],'slug'=>$x['slug'],'duplicates'=>$same],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}

echo "===NEW_BATCH_OVERLAP_CANDIDATES===\n";
foreach($new as $id){
    $x=$data[$id];$cand=[];
    foreach($data as $oid=>$o){
        if($oid==$id)continue;
        $tj=emdo_jaccard_set($x['tw'],$o['tw']);
        $cc=emdo_cosine($x['cf'],$o['cf']);
        if($tj>=0.28 || $cc>=0.58){$cand[]=['id'=>$oid,'title'=>$o['title'],'title_j'=>round($tj,3),'content_cos'=>round($cc,3),'headings'=>$o['headings']];}
    }
    usort($cand,fn($a,$b)=>max($b['title_j'],$b['content_cos'])<=>max($a['title_j'],$a['content_cos']));
    $cand=array_slice($cand,0,8);
    echo json_encode(['id'=>$id,'title'=>$x['title'],'headings'=>$x['headings'],'candidates'=>$cand],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}

echo "===AUTHORITY_BLOCK_HIGH_OVERLAPS===\n";
$pairs=[];$n=count($authority);
for($i=0;$i<$n;$i++)for($j=$i+1;$j<$n;$j++){
    $a=$data[$authority[$i]];$b=$data[$authority[$j]];
    $tj=emdo_jaccard_set($a['tw'],$b['tw']);$cc=emdo_cosine($a['cf'],$b['cf']);
    if($tj>=0.42 || $cc>=0.68){$pairs[]=['a'=>$a['id'],'at'=>$a['title'],'b'=>$b['id'],'bt'=>$b['title'],'title_j'=>round($tj,3),'content_cos'=>round($cc,3)];}
}
usort($pairs,fn($a,$b)=>max($b['title_j'],$b['content_cos'])<=>max($a['title_j'],$a['content_cos']));
foreach(array_slice($pairs,0,100) as $p)echo json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";

echo "===KNOWN_TECHNICAL_PAIRS===\n";
foreach([[14040,14061],[14041,14062],[14042,14063],[14043,14064],[14044,14065],[14045,14066],[14046,14067],[14047,14068],[14048,14069],[14049,14070]] as [$a,$b]){
 if(isset($data[$a],$data[$b])) echo json_encode(['a'=>$a,'at'=>$data[$a]['title'],'b'=>$b,'bt'=>$data[$b]['title'],'title_j'=>round(emdo_jaccard_set($data[$a]['tw'],$data[$b]['tw']),3),'content_cos'=>round(emdo_cosine($data[$a]['cf'],$data[$b]['cf']),3),'a_headings'=>$data[$a]['headings'],'b_headings'=>$data[$b]['headings']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
