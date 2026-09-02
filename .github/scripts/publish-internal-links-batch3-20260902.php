<?php
if (!defined('ABSPATH')) exit;

$marker='<!-- emdo-internal-links-20260902-b3 -->';
$map=[
 12945=>[13372,13373,13374,13402],
 13039=>[13400,13401,13403],
 14003=>[13927,13947],
 14040=>[13968,13983,13985,14042],
 14081=>[13969,14028,14046],
 13923=>[14005],
 13907=>[14041],
 13878=>[13876],
 14041=>[14045]
];
function emdo_ilb3_url($id,$lang){if($lang==='es')return get_permalink($id);$s=(string)get_post_meta($id,'_en_US_post_name',true);return $s?rtrim(home_url('/'),'/').'/en/'.trim($s,'/').'/':'';}
function emdo_ilb3_title($id,$lang){return $lang==='es'?get_the_title($id):(string)get_post_meta($id,'_en_US_post_title',true);}
function emdo_ilb3_insert($html,$block,$lang){
 $patterns=$lang==='es'?['~<h2\b[^>]*>\s*(?:Fuentes|Fuentes consultadas|Fuentes y referencias|Fuentes y criterio|Fuente|Productos relacionados|Guías relacionadas)\b~iu']:['~<h2\b[^>]*>\s*(?:Sources|Sources and references|References|Source|Related products|Related guides)\b~iu'];
 foreach($patterns as $pattern){if(preg_match($pattern,$html,$m,PREG_OFFSET_CAPTURE)){return substr($html,0,$m[0][1]).$block."\n".substr($html,$m[0][1]);}}
 return rtrim($html)."\n\n".$block;
}
function emdo_ilb3_block($targets,$lang,$html,$marker){
 $items=[];foreach($targets as $tid){$p=get_post($tid);if(!$p||$p->post_status!=='publish'||$p->post_type!=='post')continue;if($lang==='en'&&get_post_meta($tid,'_en_US_published',true)!=='1')continue;$url=emdo_ilb3_url($tid,$lang);$title=trim(emdo_ilb3_title($tid,$lang));if(!$url||!$title||strpos($html,$url)!==false)continue;$items[]='<li><a href="'.esc_url($url).'">'.esc_html($title).'</a></li>';}
 if(!$items)return '';$h=$lang==='es'?'Lecturas relacionadas':'Related reading';return $marker."\n<section class=\"emdo-editorial-related-links\" aria-label=\"".esc_attr($h)."\"><h2>".esc_html($h)."</h2><ul>".implode('',$items)."</ul></section>\n<!-- /emdo-internal-links-20260902-b3 -->";
}
$backup_key='emdo_internal_links_backup_20260902_b3';
if(!get_option($backup_key,false)){$backup=[];foreach(array_keys($map) as $sid){$p=get_post($sid);if($p)$backup[$sid]=['es'=>$p->post_content,'en'=>(string)get_post_meta($sid,'_en_US_post_content',true),'modified'=>$p->post_modified,'modified_gmt'=>$p->post_modified_gmt];}add_option($backup_key,$backup,'','no');}
$changes=[];$skipped=[];
foreach($map as $sid=>$targets){$p=get_post($sid);if(!$p||$p->post_status!=='publish'||$p->post_type!=='post'){$skipped[]=['id'=>$sid,'reason'=>'missing source'];continue;}$es=$p->post_content;$en=(string)get_post_meta($sid,'_en_US_post_content',true);$eb=strpos($es,$marker)===false?emdo_ilb3_block($targets,'es',$es,$marker):'';$nb=strpos($en,$marker)===false?emdo_ilb3_block($targets,'en',$en,$marker):'';$esn=$eb?emdo_ilb3_insert($es,$eb,'es'):$es;$enn=$nb?emdo_ilb3_insert($en,$nb,'en'):$en;if($esn===$es&&$enn===$en){$skipped[]=['id'=>$sid,'reason'=>'no missing links or marker exists'];continue;}$ok=true;if($esn!==$es){$r=wp_update_post(['ID'=>$sid,'post_content'=>$esn],true);if(is_wp_error($r)){$ok=false;$skipped[]=['id'=>$sid,'reason'=>$r->get_error_message()];}}if($ok&&$enn!==$en)update_post_meta($sid,'_en_US_post_content',$enn);if($ok)$changes[]=['id'=>$sid,'title'=>$p->post_title,'targets'=>$targets,'es_added'=>$eb?substr_count($eb,'<li><a href='):0,'en_added'=>$nb?substr_count($nb,'<li><a href='):0];}
echo "EMDO_INTERNAL_LINKS_B3_BEGIN\n";echo wp_json_encode(['changed'=>$changes,'skipped'=>$skipped,'backup_present'=>(bool)get_option($backup_key,false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";echo "EMDO_INTERNAL_LINKS_B3_END\n";
