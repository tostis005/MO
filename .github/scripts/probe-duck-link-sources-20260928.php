<?php
if(!defined('ABSPATH')) exit;
global $wpdb;
$patterns=['%carne%','%congel%','%conserv%','%prote%','%navidad%','%regal%','%vino%','%salsa%','%aperitiv%','%persona%','%cantidad%','%gourmet%'];
$ors=[];$args=[];
foreach($patterns as $p){$ors[]='(p.post_title LIKE %s OR p.post_content LIKE %s)';$args[]=$p;$args[]=$p;}
$sql="SELECT DISTINCT p.ID,p.post_title,p.post_name,p.post_date
FROM {$wpdb->posts} p
LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key='_emdo_blog_cluster'
WHERE p.post_type='post' AND p.post_status='publish' AND (pm.meta_value IS NULL OR pm.meta_value<>'duck')
AND (".implode(' OR ',$ors).")
ORDER BY p.post_date DESC LIMIT 120";
$rows=$wpdb->get_results($wpdb->prepare($sql,$args),ARRAY_A);
$out=[];
foreach($rows as $r){
 $id=(int)$r['ID']; $txt=wp_strip_all_tags((string)get_post_field('post_content',$id));
 $score=0;$matched=[];
 foreach(['carne','congel','conserv','prote','navidad','regal','vino','salsa','aperitiv','persona','cantidad','gourmet'] as $k){
  if(stripos(remove_accents($r['post_title']),remove_accents($k))!==false){$score+=3;$matched[]=$k.':title';}
  elseif(stripos(remove_accents($txt),remove_accents($k))!==false){$score+=1;$matched[]=$k.':body';}
 }
 $out[]=['id'=>$id,'title'=>$r['post_title'],'slug'=>$r['post_name'],'date'=>$r['post_date'],'score'=>$score,'matched'=>$matched];
}
usort($out,fn($a,$b)=>$b['score']<=>$a['score']);
echo wp_json_encode(array_slice($out,0,80),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
