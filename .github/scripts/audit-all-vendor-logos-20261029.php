<?php
/**
 * 2026-10-29 read-only audit of ALL WCFM producers and desktop circle logos.
 * Scope includes vendors with any published Woo product, and suppliers
 * registered in EMDO even when their store is temporarily disabled.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) throw new RuntimeException('Production WordPress and EMDO required.');
foreach (['home','siteurl'] as $k) {
 $host=strtolower((string)wp_parse_url((string)get_option($k),PHP_URL_HOST));
 if(!in_array($host,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
}
global $wpdb;
$sup=MDO_Database::table('suppliers');
$map=$wpdb->get_results("SELECT code,name,source_url,vendor_user_id,active FROM {$sup} WHERE vendor_user_id IS NOT NULL AND vendor_user_id>0 ORDER BY vendor_user_id",ARRAY_A);
$sup_by_vendor=[];
foreach($map as $row) $sup_by_vendor[(int)$row['vendor_user_id']][]=['code'=>$row['code'],'name'=>$row['name'],'source_url'=>$row['source_url'],'active'=>$row['active']];
$users=get_users(['role'=>'wcfm_vendor','fields'=>['ID','user_login','display_name'],'number'=>200]);
$counts=['vendor_accounts'=>count($users),'public_sellers'=>0,'missing_public_logos'=>0,'invalid_public_logos'=>0,'logos_valid'=>0,'unpublished'=>0];
foreach($users as $u){
 $id=(int)$u->ID;
 $profile=get_user_meta($id,'wcfmmp_profile_settings',true);
 $profile=is_array($profile)?$profile:[];
 $published=(int)$wpdb->get_var($wpdb->prepare(
 "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$id));
 $all=(int)$wpdb->get_var($wpdb->prepare(
 "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status NOT IN('trash','auto-draft') AND post_author=%d",$id));
 $avatar=$profile['gravatar']??null;
 $logo=$profile['store_logo']??null;
 $gravatar_id=is_numeric($avatar)?(int)$avatar:0;
 $img_url= $gravatar_id>0 ? wp_get_attachment_url($gravatar_id) : null;
 $isvalid= $gravatar_id>0 && get_post_type($gravatar_id)==='attachment' && is_string($img_url) && $img_url!=='';
 $picture_url= '';
 if(function_exists('wcfmmp_get_store_info')) {
  $info=wcfmmp_get_store_info($id);
  if(is_array($info)) $picture_url=(string)($info['gravatar']??'');
 }
 $banner=(int)($profile['banner']??0);
 $item=[
  'vendor_id'=>$id,'store'=>$profile['store_name']??$u->display_name,
  'slug'=>$profile['store_slug']??$u->user_login,
  'published'=>$published,'total'=>$all,'disabled'=>(bool)get_user_meta($id,'_disable_vendor',true),
  'avatar_raw'=>$avatar,'avatar_valid'=>$isvalid,
  'avatar_url'=>$img_url?:null, 'banner_id'=>$banner,
  'banner_url'=>$banner?wp_get_attachment_url($banner):null,
  'suppliers'=>$sup_by_vendor[$id]??[],
  'store_url'=>function_exists('wcfmmp_get_store_url')?wcfmmp_get_store_url($id):'',
  'wcfm_info_avatar'=>$picture_url,
  'profile_logo_fallback'=>$logo,
 ];
 if($published>0) {
  $counts['public_sellers']++;
  if(!$gravatar_id)$counts['missing_public_logos']++;
  elseif(!$isvalid)$counts['invalid_public_logos']++;
  else $counts['logos_valid']++;
 } else $counts['unpublished']++;
 echo 'MDO_VENDOR_LOGO_ENTRY='.wp_json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
}
echo 'MDO_VENDOR_LOGO_SUMMARY='.wp_json_encode($counts,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
