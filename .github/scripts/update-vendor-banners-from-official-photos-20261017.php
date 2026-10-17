<?php
/**
 * Replace only WCFM seller cover photos with authentic, existing media from
 * each supplier's own official website. NO AI, NO composites, NO cropping.
 * Shipping, pricing, logos and other profile settings are not modified.
 */
if(!defined('ABSPATH'))exit(1);
$h=strtolower((string)wp_parse_url(home_url(),PHP_URL_HOST));
if(!in_array($h,array('elmercadodeorigen.com','www.elmercadodeorigen.com'),true))throw new RuntimeException('Produccion requerida');
global $wpdb;
$t=MDO_Database::table('suppliers');
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$jobs=array(
 array('code'=>'chocolates-artesanos-isabel','image'=>'https://chocolatesartesanosisabel.com/wp-content/uploads/AtemperadoDeChocolate.jpg','host'=>'chocolatesartesanosisabel.com','description'=>'Chocolates Artesanos Isabel - obrador real atemperando chocolate'),
 array('code'=>'latitudes','image'=>'https://latitudes.es/wp-content/uploads/2021/03/Yakiniku-sin-envasar--600x600.jpg','host'=>'latitudes.es','description'=>'Latitudes - corte real de Wagyu del catalogo oficial')
);
foreach($jobs as $job){
 $vendor=$wpdb->get_row($wpdb->prepare("SELECT code,vendor_user_id FROM {$t} WHERE code=%s LIMIT 1",$job['code']),ARRAY_A);
 if(!$vendor||!(int)$vendor['vendor_user_id'])throw new RuntimeException('Vendedor ausente: '.$job['code']);
 $id=(int)$vendor['vendor_user_id'];
 if(!get_user_by('id',$id))throw new RuntimeException('Usuario vendedor inexistente: '.$id);
 $p=get_user_meta($id,'wcfmmp_profile_settings',true);
 if(!is_array($p))throw new RuntimeException('Perfil sin datos: '.$job['code']);
 if(strtolower((string)wp_parse_url($job['image'],PHP_URL_HOST))!==$job['host'])
  throw new RuntimeException('Origen no permitido: '.$job['code']);
 // Avoid repeated attachments when Actions are retried.
 $prev=(int)get_user_meta($id,'_emdo_authentic_vendor_banner_20261017',true);
 $prior_url=(string)get_post_meta($prev,'_emdo_authentic_photo_source_url',true);
 $attachment=($prev&&$prior_url===$job['image']&&get_post_type($prev)==='attachment')?$prev:0;
 if(!$attachment){
  $attachment=media_sideload_image($job['image'],0,$job['description'],'id');
  if(is_wp_error($attachment))throw new RuntimeException('Fallo imagen '.$job['code'].': '.$attachment->get_error_message());
  $attachment=(int)$attachment;
  $meta=wp_get_attachment_metadata($attachment);
  if(!is_array($meta)|| (int)($meta['width']??0)<590 || (int)($meta['height']??0)<350){
   wp_delete_attachment($attachment,true);throw new RuntimeException('Fotografia demasiado pequena para banner: '.$job['code']);
  }
  update_post_meta($attachment,'_emdo_authentic_photo_source_url',$job['image']);
  update_post_meta($attachment,'_emdo_authentic_photo_origin','official_supplier_site');
  wp_update_post(array('ID'=>$attachment,'post_title'=>$job['description']));
 }
 $old=(int)($p['banner']??0);
 $p['banner_type']='image';
 $p['banner']=(string)$attachment;
 $p['mobile_banner']=(string)$attachment;
 $p['list_banner_type']='image';
 $p['list_banner']=(string)$attachment;
 update_user_meta($id,'wcfmmp_profile_settings',$p);
 update_user_meta($id,'_emdo_authentic_vendor_banner_20261017',$attachment);
 $saved=get_user_meta($id,'wcfmmp_profile_settings',true);
 if((int)($saved['banner']??0)!==$attachment)throw new RuntimeException('Banner no persistido: '.$job['code']);
 echo 'AUTHENTIC_VENDOR_BANNER='.wp_json_encode(array('code'=>$job['code'],'vendor_id'=>$id,'old_id'=>$old,
 'new_id'=>$attachment,'url'=>wp_get_attachment_url($attachment),'original_url'=>$job['image'],
 'dimensions'=>array_intersect_key((array)wp_get_attachment_metadata($attachment),array_flip(array('width','height')))),
 JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
}
