<?php
/** Final independent QA: original-pixel retention and embedded HTML original URL references. */
if(!defined('ABSPATH')||!defined('WP_CLI')||!WP_CLI)exit(2);
global $wpdb;
$old=[17177,17179,17183,17185,17199,17201,17205,17271,17275,17279,17283,17287,17289,17291,17293,17295,17297,17307,17313,17327,17333,17335,17337,17344,17348,17357,17359,17361,17363,17383,18168];
$results=[];$embedded=[];$perPhoto=[];
foreach($old as $id){
 $new=(int)get_post_meta($id,'_emdo_latitudes_clean_replacement_id',true);
 $a=(string)get_attached_file($id);$b=(string)get_attached_file($new);
 if(!is_readable($a)||!is_readable($b))throw new RuntimeException("MISSING_MEDIA $id");
 $ia=@imagecreatefromstring(file_get_contents($a));$ib=@imagecreatefromstring(file_get_contents($b));
 if(!$ia||!$ib)throw new RuntimeException("IMAGE_DECODE_FAILURE $id");
 $mae=0;$max=0;$sample=0;
 for($yy=10;$yy<900;$yy+=37)for($xx=5;$xx<1023;$xx+=37){
  $ca=imagecolorat($ia,$xx,$yy);$cb=imagecolorat($ib,$xx,$yy);
  for($shift=0;$shift<=16;$shift+=8){
   $diff=abs((($ca>>$shift)&255)-(($cb>>$shift)&255));
   $mae+=$diff;$max=max($max,$diff);$sample++;
  }
 }
 imagedestroy($ia);imagedestroy($ib);
 $mae=$mae/max(1,$sample);
 $mime=get_post_mime_type($new);
 if(($mime==='image/png' && $mae>1) || ($mime==='image/jpeg' && $mae>7))
  throw new RuntimeException("IMAGE_PIXELS_ALTERED_OUTSIDE_FOOTER $id MAE=$mae MAX=$max");
 $perPhoto[]=['original'=>$id,'clean'=>$new,'mime'=>$mime,'pixel_mae_top90'=>round($mae,4),'max_sample_diff'=>$max];
 $origURL=(string)wp_get_attachment_url($id);
 if($origURL){
  $needle=wp_parse_url($origURL,PHP_URL_PATH);
  $like='%'.$wpdb->esc_like(basename($needle)).'%';
  $hits=$wpdb->get_results($wpdb->prepare(
    "SELECT ID,post_type FROM {$wpdb->posts} WHERE post_type IN ('product','product_variation')
     AND post_status NOT IN ('trash','auto-draft') AND post_content LIKE %s",$like),ARRAY_A);
  if($hits)$embedded[]=['photo'=>$id,'source_filename'=>basename($needle),'used_in_content'=>$hits];
 }
}
$stats=['images_compared'=>count($perPhoto),'png'=>count(array_filter($perPhoto,fn($x)=>$x['mime']==='image/png')),
 'jpeg'=>count(array_filter($perPhoto,fn($x)=>$x['mime']==='image/jpeg')),
 'largest_pixel_mae'=>max(array_column($perPhoto,'pixel_mae_top90')),
 'html_embedded_original_file_refs'=>count($embedded),'embedded'=>$embedded];
echo 'LATITUDES_FOOTER_PIXEL_PRESERVATION_QA '.wp_json_encode($stats,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
