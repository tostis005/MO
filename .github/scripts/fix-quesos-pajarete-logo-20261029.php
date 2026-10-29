<?php
/**
 * Fix only Quesos Pajarete's missing WCFM circular store avatar.
 * Official image: from quesospajarete.com page, image alt="Quesos Pajarete"
 * File: https://quesospajarete.com/wp-content/uploads/2024/04/logo__1_-removebg-preview.png
 * Published product, banner, shipping and other seller profiles untouched.
 * Scope: production vendor ID 4974.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('MDO_Database')) throw new RuntimeException('Production WP CLI EMDO required');
foreach(['home','siteurl'] as $k) {
 $host=strtolower((string)wp_parse_url((string)get_option($k),PHP_URL_HOST));
 if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Refuse non-production host');
}
global $wpdb;
$vid=4974;
$sup=MDO_Database::table('suppliers');
$row=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
$user=get_user_by('id',$vid);
if (!$row || (int)$row['vendor_user_id']!==$vid || !$user || $user->user_login!=='quesos-pajarete' ||
  !in_array('wcfm_vendor',(array)$user->roles,true)) throw new RuntimeException('Wrong vendor identity; no changes');
$profile=get_user_meta($vid,'wcfmmp_profile_settings',true);
if(!is_array($profile)||($profile['store_name']??'')!=='Quesos Pajarete')throw new RuntimeException('Wrong WCFM producer');
$original_banner=$profile['banner']??null;
$original_mobile_banner=$profile['mobile_banner']??null;
$original_list_banner=$profile['list_banner']??null;
$current_avatar=(int)($profile['gravatar']??0);
if($current_avatar && get_post_type($current_avatar)==='attachment' && wp_get_attachment_url($current_avatar)) {
 echo 'PAJARETE_LOGO_ALREADY_PRESENT='.wp_json_encode(['vendor_id'=>$vid,'avatar'=>$current_avatar,'url'=>wp_get_attachment_url($current_avatar)],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)."\n";
 exit;
}
$public=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$vid));
if((int)$public<1)throw new RuntimeException('Store has no public products');
$other_vendors=$wpdb->get_results($wpdb->prepare(
 "SELECT user_id,meta_value FROM {$wpdb->usermeta} WHERE meta_key=%s AND user_id<>%d ORDER BY user_id,umeta_id",'wcfmmp_profile_settings',$vid),ARRAY_A);
$other_hash=hash('sha256',serialize($other_vendors));
$origin='https://quesospajarete.com/wp-content/uploads/2024/04/logo__1_-removebg-preview.png';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$id=(int)get_user_meta($vid,'_mdo_quesos_pajarete_official_logo_20261029',true);
if(!$id||get_post_type($id)!=='attachment'||(string)get_post_meta($id,'_mdo_quesos_pajarete_official_logo_source',true)!==$origin){
 $id=media_sideload_image($origin,0,'Logotipo oficial de Quesos Pajarete','id');
 if(is_wp_error($id))throw new RuntimeException('Could not import official logo: '.$id->get_error_message());
 $id=(int)$id;
 $meta=wp_get_attachment_metadata($id);
 if(!is_array($meta)||(int)($meta['width']??0)<600||(int)($meta['height']??0)<200){
  wp_delete_attachment($id,true);
  throw new RuntimeException('Official Pajarete logo image too small/wrong image dimensions');
 }
 update_post_meta($id,'_mdo_quesos_pajarete_official_logo_source',$origin);
 wp_update_post(['ID'=>$id,'post_title'=>'Quesos Pajarete — logotipo oficial']);
 update_user_meta($vid,'_mdo_quesos_pajarete_official_logo_20261029',$id);
}
$profile['gravatar']=(string)$id;
update_user_meta($vid,'wcfmmp_profile_settings',$profile);
$after=get_user_meta($vid,'wcfmmp_profile_settings',true);
$others_after=$wpdb->get_results($wpdb->prepare(
 "SELECT user_id,meta_value FROM {$wpdb->usermeta} WHERE meta_key=%s AND user_id<>%d ORDER BY user_id,umeta_id",'wcfmmp_profile_settings',$vid),ARRAY_A);
if(hash('sha256',serialize($others_after))!==$other_hash)throw new RuntimeException('Unexpected changes in another vendor profile');
if((int)($after['gravatar']??0)!==$id||
   ($after['banner']??null)!==$original_banner ||
   ($after['mobile_banner']??null)!==$original_mobile_banner ||
   ($after['list_banner']??null)!==$original_list_banner) throw new RuntimeException('Unexpected logo/banner mutation');
if(function_exists('rocket_clean_domain'))rocket_clean_domain();
if(class_exists('WC_Cache_Helper'))WC_Cache_Helper::get_transient_version('product',true);
if(function_exists('litespeed_purge_all'))litespeed_purge_all();
do_action('litespeed_purge_all');
wp_cache_flush();
echo 'PAJARETE_LOGO_CONFIGURED='.wp_json_encode([
 'vendor_id'=>$vid,'gravatar_id'=>$id,
 'local_url'=>wp_get_attachment_url($id),
 'official_source'=>$origin,
 'picture_dimensions'=>array_intersect_key((array)wp_get_attachment_metadata($id),array_flip(['width','height'])),
 'published_products_before'=>(int)$public,'banner_unchanged'=>true,'other_vendor_profiles_unchanged'=>true,
 'public_store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($vid):''
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
