<?php
/** Read-only dump of all seven draft Musée pack cover image bytes and source refs. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
if(!function_exists('emdo_musee_review_ids')||!get_option('emdo_musee_mentta_review_hold'))exit(3);
$dir='/tmp/musee-current-7-pack-covers-20261027';if(!is_dir($dir)&&!wp_mkdir_p($dir))exit(4);
$files=[];foreach(emdo_musee_review_ids() as $pid){
 $p=wc_get_product($pid);
 if(!$p||$p->get_status()!=='draft')exit(5);
 $aid=(int)$p->get_image_id();$file=(string)get_attached_file($aid);
 if(!is_readable($file))exit(6);
 $ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
 if(!in_array($ext,['jpg','jpeg','webp','png'],true))exit(7);
 $out=$pid.'-'.$aid.'.'.$ext;copy($file,$dir.'/'.$out);
 $lines=get_post_meta($pid,'_emdo_mentta_pack_lines',true);
 $files[]=['pack_id'=>$pid,'title'=>$p->get_name(),'featured_attachment_id'=>$aid,'file'=>$out,
  'gallery'=>$p->get_gallery_image_ids(),'source_component_image_ids'=>array_map(static fn($line)=>$line['image']??0,is_array($lines)?$lines:[]),
  'dimensions'=>getimagesize($file)];
}
file_put_contents($dir.'/packs.json',wp_json_encode($files,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
echo 'SEVEN_MENTTA_HELD_PACK_COVERS_READONLY '.wp_json_encode($files,JSON_UNESCAPED_UNICODE)."\n";
