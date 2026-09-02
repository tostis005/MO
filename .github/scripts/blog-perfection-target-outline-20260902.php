<?php
if (!defined('ABSPATH')) { exit; }
$ids=[13694,13718,13720,13721,13727,13728,13730,13734,12924,12971,13086,13852,13853,13854,13855,13857,13859,13861,13862,13863,13864,13865,13866,14022,14088,14089];
function emdo_outline($html){
  preg_match_all('/<h[2-3][^>]*>(.*?)<\/h[2-3]>/isu',(string)$html,$m);
  return array_values(array_map(fn($x)=>trim(wp_strip_all_tags($x)),$m[1]));
}
function emdo_words($html){preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$html),$m);return count($m[0]);}
$out=[];
foreach($ids as $id){
 $p=get_post($id); if(!$p) continue;
 $en=(string)get_post_meta($id,'_en_US_post_content',true);
 $out[]=['id'=>$id,'status'=>$p->post_status,'title'=>$p->post_title,'es_words'=>emdo_words($p->post_content),'es_h'=>emdo_outline($p->post_content),'en_words'=>emdo_words($en),'en_h'=>emdo_outline($en)];
}
echo "EMDO_TARGET_OUTLINE_BEGIN\n".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\nEMDO_TARGET_OUTLINE_END\n";
