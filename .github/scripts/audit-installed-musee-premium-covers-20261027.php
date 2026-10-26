<?php
/** Read-only export of installed three premium pack cover JPEGs for visual QA. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI||!function_exists('wc_get_product'))exit(2);
if(!get_option('emdo_musee_mentta_review_hold')||!function_exists('emdo_mentta_exclusive_ids'))exit(3);
$items=[
 21814=>['key'=>'pasaporte-salvaje','count'=>9,'price'=>153.49],
 21818=>['key'=>'safari-africano','count'=>8,'price'=>154.99],
 21820=>['key'=>'vuelta-al-mundo','count'=>10,'price'=>158.99]];
$dir='/tmp/musee-premium-cover-QA-'.getmypid();if(!wp_mkdir_p($dir))exit(4);
$data=[];
foreach($items as $id=>$check){
 $p=wc_get_product($id);
 if(!$p||$p->get_status()!=='draft' ||
  !in_array($id,emdo_mentta_exclusive_ids(),true) ||
  get_post_meta($id,'_emdo_mentta_pack_key',true)!==$check['key'] ||
  get_post_meta($id,'_emdo_musee_featured_premium_real_source_verified',true)!=='yes' ||
  abs((float)$p->get_regular_price('edit')-$check['price'])>.001)exit(5);
 $rows=get_post_meta($id,'_emdo_mentta_pack_lines',true);
 if(!is_array($rows)||count($rows)!==$check['count'])exit(6);
 $image=(int)$p->get_image_id();$src=get_attached_file($image);
 $img=$src&&is_file($src)?@getimagesize($src):false;
 if(!$src||!$img ||$img[0]!==1254||$img[1]!==1254)exit(7);
 $outfile="$dir/{$check['key']}.jpg";
 if(!copy($src,$outfile))exit(8);
 $data[]=['id'=>$id,'image_attachment_id'=>$image,'cover_file'=>basename($src),
   'original_pieces'=>count($rows),'price'=>$p->get_regular_price('edit'),
   'image_sha256'=>hash_file('sha256',$outfile),'url'=>wp_get_attachment_url($image)];
}
file_put_contents("$dir/audit.json",wp_json_encode($data,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
echo 'COVER_AUDIT_DIR '.$dir."\n";
echo 'ALL_THREE_PREMIUM_ARTWORK_FILES_CORRECT '.wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
