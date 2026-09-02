<?php
if (!defined('ABSPATH')) exit;
$map=[
 'es'=>['/como-conservar-legumbres-secas-casa-humedad-insectos-caducidad/'=>13031],
 'en'=>[
  '/en/how-to-store-dried-pulses-at-home-moisture-insects-shelf-life/'=>13031,
  '/en/can-you-store-open-can-in-fridge-why-transfer-container/'=>13460,
 ],
];
function emdo_f3_norm($href){$u=wp_parse_url(html_entity_decode((string)$href,ENT_QUOTES|ENT_HTML5,'UTF-8'));if(!$u||empty($u['path']))return null;return '/'.trim($u['path'],'/').'/';}
function emdo_f3_url($id,$lang){if($lang==='es')return get_permalink($id);$s=(string)get_post_meta($id,'_en_US_post_name',true);return $s?rtrim(home_url('/'),'/').'/en/'.trim($s,'/').'/':null;}
function emdo_f3_rewrite($html,$lang,$map,&$count){return preg_replace_callback('/<a\b([^>]*?)\bhref\s*=\s*(["\'])(.*?)\2([^>]*)>(.*?)<\/a>/isu',function($m)use($lang,$map,&$count){$href=html_entity_decode($m[3],ENT_QUOTES|ENT_HTML5,'UTF-8');$path=emdo_f3_norm($href);if(!$path||!isset($map[$lang][$path]))return $m[0];$host=strtolower((string)(wp_parse_url($href,PHP_URL_HOST)??''));if($host&&!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))return $m[0];$new=emdo_f3_url((int)$map[$lang][$path],$lang);if(!$new)return $m[0];$q=(string)(wp_parse_url($href,PHP_URL_QUERY)??'');$f=(string)(wp_parse_url($href,PHP_URL_FRAGMENT)??'');if($q!=='')$new.='?'.$q;if($f!=='')$new.='#'.$f;$count++;return '<a'.$m[1].'href='.$m[2].esc_url($new).$m[2].$m[4].'>'.$m[5].'</a>';},$html);}
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','numberposts'=>-1]);$changed=[];$count=0;
foreach($posts as $p){$es=$p->post_content;$nes=emdo_f3_rewrite($es,'es',$map,$count);if($nes!==$es){wp_update_post(['ID'=>$p->ID,'post_content'=>$nes]);$changed[]=['id'=>$p->ID,'lang'=>'es'];}$en=(string)get_post_meta($p->ID,'_en_US_post_content',true);if($en!==''){$nen=emdo_f3_rewrite($en,'en',$map,$count);if($nen!==$en){update_post_meta($p->ID,'_en_US_post_content',$nen);$changed[]=['id'=>$p->ID,'lang'=>'en'];}}}
echo "EMDO_FINAL_3_STALE_BEGIN\n";echo wp_json_encode(['replacements'=>$count,'changed'=>$changed],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";echo "EMDO_FINAL_3_STALE_END\n";
