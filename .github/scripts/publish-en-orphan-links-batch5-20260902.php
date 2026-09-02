<?php
if (!defined('ABSPATH')) exit;

$marker='<!-- emdo-en-orphan-links-20260902-b5 -->';
$map=[
 13039=>[13029,13409],
 12971=>[13068,13364,13453],
 13495=>[13507],
 12932=>[13707],
 13410=>[13711,13715,13717,13719],
];
function emdo_b5_en_url($id){$s=(string)get_post_meta($id,'_en_US_post_name',true);return $s?rtrim(home_url('/'),'/').'/en/'.trim($s,'/').'/':'';}
function emdo_b5_en_title($id){return trim((string)get_post_meta($id,'_en_US_post_title',true));}
function emdo_b5_insert($html,$block){
 foreach(['~<h2\b[^>]*>\s*(?:Sources|Sources and references|References|Source|Related products|Related guides|Related reading)\b~iu'] as $p){
  if(preg_match($p,$html,$m,PREG_OFFSET_CAPTURE)) return substr($html,0,$m[0][1]).$block."\n".substr($html,$m[0][1]);
 }
 return rtrim($html)."\n\n".$block;
}
$backup_key='emdo_en_orphan_links_backup_20260902_b5';
if(!get_option($backup_key,false)){
 $backup=[];foreach(array_keys($map) as $sid)$backup[$sid]=(string)get_post_meta($sid,'_en_US_post_content',true);
 add_option($backup_key,$backup,'','no');
}
$changes=[];$skipped=[];
foreach($map as $sid=>$targets){
 $p=get_post($sid);$en=(string)get_post_meta($sid,'_en_US_post_content',true);
 if(!$p||$p->post_status!=='publish'||$en===''||get_post_meta($sid,'_en_US_published',true)!=='1'){$skipped[]=['donor'=>$sid,'reason'=>'donor unavailable'];continue;}
 if(strpos($en,$marker)!==false){$skipped[]=['donor'=>$sid,'reason'=>'marker exists'];continue;}
 $items=[];$ids=[];
 foreach($targets as $tid){
  if(get_post_meta($tid,'_en_US_published',true)!=='1')continue;
  $url=emdo_b5_en_url($tid);$title=emdo_b5_en_title($tid);
  if(!$url||!$title||strpos($en,$url)!==false)continue;
  $items[]='<li><a href="'.esc_url($url).'">'.esc_html($title).'</a></li>';$ids[]=$tid;
 }
 if(!$items){$skipped[]=['donor'=>$sid,'reason'=>'no missing links'];continue;}
 $block=$marker."\n<section class=\"emdo-editorial-related-links\" aria-label=\"Related reading\"><h2>Related reading</h2><ul>".implode('',$items)."</ul></section>\n<!-- /emdo-en-orphan-links-20260902-b5 -->";
 update_post_meta($sid,'_en_US_post_content',emdo_b5_insert($en,$block));
 $changes[]=['donor'=>$sid,'title'=>$p->post_title,'targets'=>$ids,'en_added'=>count($items)];
}
echo "EMDO_EN_ORPHAN_LINKS_B5_BEGIN\n";
echo wp_json_encode(['changes'=>$changes,'skipped'=>$skipped,'backup_present'=>(bool)get_option($backup_key,false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_EN_ORPHAN_LINKS_B5_END\n";
