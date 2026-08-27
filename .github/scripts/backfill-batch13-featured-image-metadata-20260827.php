<?php
/** Backfill license/source metadata for selected batch 13 featured images. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$items=array(
'iberian-ham-white-crystals-guide'=>array(
 'source_key'=>'commons-jamon-iberico-bellota-detalle-corte-guanbirra',
 'page'=>'https://commons.wikimedia.org/wiki/File:Jam%C3%B3n_Ib%C3%A9rico_de_bellota_detalle_corte.jpg',
 'creator'=>'Guanbirra','license'=>'CC BY-SA 3.0 / Wikimedia Commons','license_url'=>'https://creativecommons.org/licenses/by-sa/3.0/'),
'frying-with-evoo-guide'=>array(
 'source_key'=>'pexels-rdne-4910221','page'=>'https://www.pexels.com/photo/crop-unrecognizable-chef-pouring-oil-in-frying-pan-4910221/',
 'creator'=>'RDNE Stock project','license'=>'Pexels License','license_url'=>'https://www.pexels.com/license/'),
'canned-food-shelf-life-guide'=>array(
 'source_key'=>'pexels-tivasee-33984956','page'=>'https://www.pexels.com/photo/assorted-pickled-jars-on-rustic-table-33984956/',
 'creator'=>'TIVASEE .','license'=>'Pexels License','license_url'=>'https://www.pexels.com/license/'),
'iberian-ham-intramuscular-fat-guide'=>array(
 'source_key'=>'pexels-gonzalo-mendiola-17649922','page'=>'https://www.pexels.com/photo/slice-of-ham-on-roasted-bread-17649922/',
 'creator'=>'Gonzalo Mendiola','license'=>'Pexels License','license_url'=>'https://www.pexels.com/license/'),
);
$out=array();
foreach($items as $key=>$m){
 $ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_authority_key','meta_value'=>$key));
 if(empty($ids)){throw new RuntimeException('Post not found: '.$key);} $post_id=(int)$ids[0];
 $image_id=(int)get_post_thumbnail_id($post_id); if($image_id<=0){throw new RuntimeException('Image missing: '.$key);}
 update_post_meta($image_id,'_emdo_image_source_key',$m['source_key']);
 update_post_meta($image_id,'_emdo_image_source_page',$m['page']);
 update_post_meta($image_id,'_emdo_image_creator',$m['creator']);
 update_post_meta($image_id,'_emdo_image_license',$m['license']);
 update_post_meta($image_id,'_emdo_image_license_url',$m['license_url']);
 if(stripos($m['license'],'CC BY')!==false && get_post_meta($image_id,'_emdo_image_changes',true)==='') update_post_meta($image_id,'_emdo_image_changes','Responsive display may resize or crop the image to fit the site layout.');
 $out[]=array('key'=>$key,'post_id'=>$post_id,'image_id'=>$image_id,'license'=>$m['license'],'creator'=>$m['creator'],'page'=>$m['page']);
}
echo wp_json_encode(array('backfilled'=>true,'items'=>$out),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
