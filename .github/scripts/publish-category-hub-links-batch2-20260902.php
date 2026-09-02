<?php
if (!defined('ABSPATH')) exit;

$marker='<!-- emdo-category-hub-links-20260902-b2 -->';
$cat_ids=[438,439,440,441,442,443,444,445,450];
$limit=8;

function emdo_chb2_norm($url){$u=wp_parse_url(html_entity_decode((string)$url,ENT_QUOTES|ENT_HTML5,'UTF-8'));if(!$u||empty($u['path']))return null;return preg_replace('#/+#','/','/'.trim($u['path'],'/').'/');}
function emdo_chb2_links($html){preg_match_all('/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/isu',(string)$html,$m);return $m[2]??[];}
function emdo_chb2_en_url($id){$s=(string)get_post_meta($id,'_en_US_post_name',true);return $s?rtrim(home_url('/'),'/').'/en/'.trim($s,'/').'/':'';}

$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);
$esmap=[];$enmap=[];$rows=[];
foreach($posts as $p){
 $ep=emdo_chb2_norm(get_permalink($p->ID));$ens=(string)get_post_meta($p->ID,'_en_US_post_name',true);$enp=$ens?'/en/'.trim($ens,'/').'/':null;
 if($ep)$esmap[$ep]=$p->ID;if($enp)$enmap[$enp]=$p->ID;
 $rows[$p->ID]=['id'=>$p->ID,'in_es'=>0,'in_en'=>0,'cats'=>array_map('intval',wp_get_post_categories($p->ID)),'title'=>$p->post_title,'en_title'=>(string)get_post_meta($p->ID,'_en_US_post_title',true)];
}
foreach($posts as $p){
 foreach(emdo_chb2_links($p->post_content) as $href){$path=emdo_chb2_norm($href);if($path&&isset($esmap[$path])&&$esmap[$path]!==$p->ID)$rows[$esmap[$path]]['in_es']++;}
 $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
 foreach(emdo_chb2_links($en) as $href){$path=emdo_chb2_norm($href);if($path&&isset($enmap[$path])&&$enmap[$path]!==$p->ID)$rows[$enmap[$path]]['in_en']++;}
}

$backup_key='emdo_category_hub_links_backup_20260902_b2';
if(!get_option($backup_key,false)){
 $backup=[];foreach($cat_ids as $cid){$t=get_term($cid,'category');if($t&&!is_wp_error($t))$backup[$cid]=['es'=>$t->description,'en'=>(string)get_term_meta($cid,'_en_US_description',true)];}
 add_option($backup_key,$backup,'','no');
}

$changes=[];
foreach($cat_ids as $cid){
 $t=get_term($cid,'category');if(!$t||is_wp_error($t))continue;
 $es=(string)$t->description;$en=(string)get_term_meta($cid,'_en_US_description',true);
 if(strpos($es,$marker)!==false||strpos($en,$marker)!==false)continue;
 $candidates=[];
 foreach($rows as $r){
  if(!in_array($cid,$r['cats'],true))continue;
  if($cid===445 && !in_array($r['id'],[14174,14175,14176,14177,14178],true))continue;
  if($cid!==445 && $r['in_es']>0 && $r['in_en']>0)continue;
  $both=($r['in_es']===0&&$r['in_en']===0)?1:0;
  $candidates[]=$r+['both'=>$both];
 }
 usort($candidates,function($a,$b){if($a['both']!==$b['both'])return $b['both']<=>$a['both'];$amin=$a['in_es']+$a['in_en'];$bmin=$b['in_es']+$b['in_en'];if($amin!==$bmin)return $amin<=>$bmin;return $b['id']<=>$a['id'];});
 $pick=array_slice($candidates,0,$limit);if(!$pick)continue;
 $esitems=[];$enitems=[];$ids=[];
 foreach($pick as $r){
  $esurl=get_permalink($r['id']);$enurl=emdo_chb2_en_url($r['id']);
  if($esurl&&strpos($es,$esurl)===false)$esitems[]='<li><a href="'.esc_url($esurl).'">'.esc_html($r['title']).'</a></li>';
  if($enurl&&$r['en_title']&&strpos($en,$enurl)===false)$enitems[]='<li><a href="'.esc_url($enurl).'">'.esc_html($r['en_title']).'</a></li>';
  $ids[]=$r['id'];
 }
 if($esitems){$es.="\n\n".$marker."\n<div class=\"emdo-category-guides\"><h2>Guías destacadas</h2><ul>".implode('',$esitems)."</ul></div>\n<!-- /emdo-category-hub-links-20260902-b2 -->";wp_update_term($cid,'category',['description'=>$es]);}
 if($enitems){$en.="\n\n".$marker."\n<div class=\"emdo-category-guides\"><h2>Featured guides</h2><ul>".implode('',$enitems)."</ul></div>\n<!-- /emdo-category-hub-links-20260902-b2 -->";update_term_meta($cid,'_en_US_description',$en);}
 $changes[]=['category'=>$cid,'name'=>$t->name,'targets'=>$ids,'es_links'=>count($esitems),'en_links'=>count($enitems)];
}

echo "EMDO_CATEGORY_HUB_LINKS_B2_BEGIN\n";
echo wp_json_encode(['changes'=>$changes,'backup_present'=>(bool)get_option($backup_key,false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_CATEGORY_HUB_LINKS_B2_END\n";
