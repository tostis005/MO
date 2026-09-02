<?php
if (!defined('ABSPATH')) exit;
$sample='https://www.elmercadodeorigen.com/dop-igp-etg-diferencias-sellos-calidad-alimentos/';
$r=wp_remote_get($sample,['timeout'=>20]);
if(is_wp_error($r)) throw new Exception($r->get_error_message());
$body=(string)wp_remote_retrieve_body($r);
preg_match_all('#<script[^>]+type=["\']application/ld\+json["\'][^>]*>(.*?)</script>#is',$body,$m);
$blocks=[];
$walk=function($v,$path='root') use (&$walk,&$blocks){
 if(!is_array($v)) return;
 if(isset($v['@type'])){
   $types=(array)$v['@type'];
   if(in_array('BlogPosting',$types,true)||in_array('Article',$types,true)||in_array('Person',$types,true)||in_array('Organization',$types,true)){
      $blocks[]=['path'=>$path,'type'=>$v['@type'],'id'=>$v['@id']??null,'name'=>$v['name']??null,'author'=>$v['author']??null,'publisher'=>$v['publisher']??null,'datePublished'=>$v['datePublished']??null,'dateModified'=>$v['dateModified']??null];
   }
 }
 foreach($v as $k=>$vv) if(is_array($vv)) $walk($vv,$path.'.'.$k);
};
foreach($m[1] as $i=>$raw){$d=json_decode(trim($raw),true); if(is_array($d)) $walk($d,'script'.$i);}
$active=[];
foreach((array)get_option('active_plugins',[]) as $p){ if(preg_match('/seo|rank|schema|yoast|aioseo|seopress/i',$p))$active[]=$p; }
$mu=[]; foreach(wp_get_mu_plugins() as $file=>$data){ if(preg_match('/seo|schema|rank|yoast/i',$file.' '.($data['Name']??'')))$mu[]=['file'=>$file,'name'=>$data['Name']??'']; }
$comments=[]; if(preg_match_all('/<!--\s*(.*?)\s*-->/s',$body,$cm)){foreach($cm[1] as $c){$c=trim(preg_replace('/\s+/',' ',$c));if(preg_match('/seo|schema|yoast|rank math|aioseo|seopress/i',$c))$comments[]=mb_substr($c,0,300);}}
echo "EMDO_SCHEMA_SOURCE_BEGIN\n";
echo wp_json_encode(['sample'=>$sample,'http'=>wp_remote_retrieve_response_code($r),'active_seo_plugins'=>$active,'mu_matches'=>$mu,'nodes'=>$blocks,'html_comments'=>array_values(array_unique($comments))],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_SCHEMA_SOURCE_END\n";
