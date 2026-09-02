<?php
if (!defined('ABSPATH')) exit;

$targets=[13029,13068,13360,13364,13409,13453,13507,13707,13711,13715,13717,13719];
$marker='<!-- emdo-en-inbound-mirror-20260902-b4 -->';
function emdo_b4_en_url($id){$s=(string)get_post_meta($id,'_en_US_post_name',true);return $s?rtrim(home_url('/'),'/').'/en/'.trim($s,'/').'/':'';}
function emdo_b4_insert($html,$block){foreach(['~<h2\b[^>]*>\s*(?:Sources|Sources and references|References|Source|Related products|Related guides)\b~iu'] as $p){if(preg_match($p,$html,$m,PREG_OFFSET_CAPTURE))return substr($html,0,$m[0][1]).$block."\n".substr($html,$m[0][1]);}return rtrim($html)."\n\n".$block;}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1,'orderby'=>'ID','order'=>'ASC']);
$donor_targets=[];$unmatched=[];
foreach($targets as $tid){$tp=get_post($tid);if(!$tp||$tp->post_status!=='publish'||get_post_meta($tid,'_en_US_published',true)!=='1'){$unmatched[]=['target'=>$tid,'reason'=>'target unavailable'];continue;}$esurl=get_permalink($tid);$enurl=emdo_b4_en_url($tid);$entitle=(string)get_post_meta($tid,'_en_US_post_title',true);$chosen=null;
 foreach($posts as $p){if($p->ID===$tid||strpos($p->post_content,$esurl)===false)continue;$en=(string)get_post_meta($p->ID,'_en_US_post_content',true);if($en===''||get_post_meta($p->ID,'_en_US_published',true)!=='1'||strpos($en,$enurl)!==false)continue;$chosen=$p->ID;break;}
 if(!$chosen){$unmatched[]=['target'=>$tid,'reason'=>'no bilingual donor found'];continue;}
 $donor_targets[$chosen][]=[$tid,$enurl,$entitle];
}
$backup_key='emdo_en_inbound_mirror_backup_20260902_b4';
if(!get_option($backup_key,false)){$backup=[];foreach(array_keys($donor_targets) as $sid)$backup[$sid]=(string)get_post_meta($sid,'_en_US_post_content',true);add_option($backup_key,$backup,'','no');}
$changes=[];
foreach($donor_targets as $sid=>$items){$en=(string)get_post_meta($sid,'_en_US_post_content',true);$lis=[];$ids=[];foreach($items as [$tid,$url,$title]){if(!$url||!$title||strpos($en,$url)!==false)continue;$lis[]='<li><a href="'.esc_url($url).'">'.esc_html($title).'</a></li>';$ids[]=$tid;}if(!$lis)continue;$block=$marker."\n<section class=\"emdo-editorial-related-links\" aria-label=\"Related reading\"><h2>Related reading</h2><ul>".implode('',$lis)."</ul></section>\n<!-- /emdo-en-inbound-mirror-20260902-b4 -->";$new=emdo_b4_insert($en,$block);update_post_meta($sid,'_en_US_post_content',$new);$changes[]=['donor'=>$sid,'title'=>get_the_title($sid),'targets'=>$ids,'en_added'=>count($lis)];}
echo "EMDO_EN_INBOUND_MIRROR_B4_BEGIN\n";echo wp_json_encode(['changes'=>$changes,'unmatched'=>$unmatched,'backup_present'=>(bool)get_option($backup_key,false)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";echo "EMDO_EN_INBOUND_MIRROR_B4_END\n";
