<?php
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
$pid=21814;$pack=wc_get_product($pid);
if(!$pack||$pack->get_status()!=='draft'||(string)get_post_meta($pid,'_emdo_mentta_pack_key',true)!=='pasaporte-salvaje'||!get_option('emdo_musee_mentta_review_hold'))exit(3);
$items=get_post_meta($pid,'_emdo_mentta_pack_lines',true);
if(!is_array($items)||count($items)!==9)exit(4);
$dir='/tmp/musee-original-photo-export-'.getmypid();if(!wp_mkdir_p($dir))exit(5);
$out=[];
foreach($items as $i=>$item) {
 $id=(int)$item['id'];$att=(int)$item['image'];$p=wc_get_product($id);
 if(!$p||!$p->is_type('simple')||!$att||!wp_attachment_is_image($att))exit(6);
 $file=get_attached_file($att);
 if(!$file||!is_file($file))exit(7);
 $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));if(!in_array($ext,['jpg','jpeg','png','webp'],true))exit(8);
 $dest=$dir.'/'.sprintf('%02d',$i+1).'-'.sanitize_file_name($item['key']).'.'.$ext;
 if(!copy($file,$dest))exit(9);
 $out[]=['idx'=>$i+1,'id'=>$id,'name'=>$p->get_name(),'label'=>$item['label'],'image_id'=>$att,'file'=>basename($dest),'sha256'=>hash_file('sha256',$file),'source_file'=>basename($file),'source_image_url'=>wp_get_attachment_url($att)];
}
file_put_contents($dir.'/manifest.json',wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo "SOURCE_EXPORT_DIR ".$dir."\n";
echo "SOURCE_AUDIT ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
