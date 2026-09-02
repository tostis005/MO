<?php
if (!defined('ABSPATH')) exit;

$marker='<!-- emdo-internal-links-20260902-b1 -->';
$map=[
 12941=>[12865,12990,12995,13283],
 12954=>[13087,13309,13329,13351],
 12939=>[13076,13868,14101,14094],
 12958=>[13023,13869,14101],
 12945=>[13039,13327,13410,13518],
 12947=>[13399,13497,13733],
 12928=>[13337,13418,13419,13499],
 14174=>[13856,14094,14090,13924],
 14175=>[12924,13086,13351,13023],
 14176=>[13363,13485,13291],
 14177=>[13856,14094,14090,14092],
 14178=>[14174,14175,14177]
];

function emdo_ilb1_url($id,$lang){
 if($lang==='es') return get_permalink($id);
 $slug=(string)get_post_meta($id,'_en_US_post_name',true);
 return $slug? rtrim(home_url('/'),'/').'/en/'.trim($slug,'/').'/' : '';
}
function emdo_ilb1_title($id,$lang){
 return $lang==='es' ? get_the_title($id) : (string)get_post_meta($id,'_en_US_post_title',true);
}
function emdo_ilb1_insert($html,$block,$lang){
 $patterns=$lang==='es'
  ? ['~<h2\b[^>]*>\s*(?:Fuentes|Fuentes consultadas|Fuentes y referencias|Fuentes y criterio|Fuente|Productos relacionados)\b~iu']
  : ['~<h2\b[^>]*>\s*(?:Sources|Sources and references|References|Source|Related products)\b~iu'];
 foreach($patterns as $pattern){
  if(preg_match($pattern,$html,$m,PREG_OFFSET_CAPTURE)){
   $pos=$m[0][1]; return substr($html,0,$pos).$block."\n".substr($html,$pos);
  }
 }
 return rtrim($html)."\n\n".$block;
}
function emdo_ilb1_block($source_id,$targets,$lang,$html,$marker){
 $items=[];
 foreach($targets as $tid){
  $p=get_post($tid); if(!$p||$p->post_status!=='publish'||$p->post_type!=='post') continue;
  if($lang==='en' && get_post_meta($tid,'_en_US_published',true)!=='1') continue;
  $url=emdo_ilb1_url($tid,$lang); $title=trim(emdo_ilb1_title($tid,$lang));
  if(!$url||!$title||strpos($html,$url)!==false) continue;
  $items[]='<li><a href="'.esc_url($url).'">'.esc_html($title).'</a></li>';
 }
 if(!$items) return '';
 $heading=$lang==='es'?'Lecturas relacionadas':'Related reading';
 return $marker."\n<section class=\"emdo-editorial-related-links\" aria-label=\"".esc_attr($heading)."\"><h2>".esc_html($heading)."</h2><ul>".implode('', $items)."</ul></section>\n<!-- /emdo-internal-links-20260902-b1 -->";
}

$backup_key='emdo_internal_links_backup_20260902_b1';
if(!get_option($backup_key,false)){
 $backup=[];
 foreach(array_keys($map) as $sid){$p=get_post($sid);if($p)$backup[$sid]=['es'=>$p->post_content,'en'=>(string)get_post_meta($sid,'_en_US_post_content',true),'modified'=>$p->post_modified,'modified_gmt'=>$p->post_modified_gmt];}
 add_option($backup_key,$backup,'','no');
}

$changes=[];$skipped=[];
foreach($map as $sid=>$targets){
 $p=get_post($sid);
 if(!$p||$p->post_status!=='publish'||$p->post_type!=='post'){$skipped[]=['id'=>$sid,'reason'=>'missing source'];continue;}
 $es=$p->post_content; $en=(string)get_post_meta($sid,'_en_US_post_content',true);
 $es_block=strpos($es,$marker)===false?emdo_ilb1_block($sid,$targets,'es',$es,$marker):'';
 $en_block=strpos($en,$marker)===false?emdo_ilb1_block($sid,$targets,'en',$en,$marker):'';
 $es_new=$es_block?emdo_ilb1_insert($es,$es_block,'es'):$es;
 $en_new=$en_block?emdo_ilb1_insert($en,$en_block,'en'):$en;
 if($es_new===$es && $en_new===$en){$skipped[]=['id'=>$sid,'reason'=>'no missing links or marker exists'];continue;}
 $ok=true;
 if($es_new!==$es){$r=wp_update_post(['ID'=>$sid,'post_content'=>$es_new],true);if(is_wp_error($r)){$ok=false;$skipped[]=['id'=>$sid,'reason'=>$r->get_error_message()];}}
 if($ok&&$en_new!==$en) update_post_meta($sid,'_en_US_post_content',$en_new);
 if($ok){
  $es_added=substr_count($es_new,'<li><a href=')-substr_count($es,'<li><a href=');
  $en_added=substr_count($en_new,'<li><a href=')-substr_count($en,'<li><a href=');
  $changes[]=['id'=>$sid,'title'=>$p->post_title,'es_added'=>$es_added,'en_added'=>$en_added];
 }
}

echo "EMDO_INTERNAL_LINKS_B1_BEGIN\n";
echo wp_json_encode(['marker'=>$marker,'changed'=>$changes,'skipped'=>$skipped,'backup_present'=>(bool)get_option($backup_key,false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_INTERNAL_LINKS_B1_END\n";
