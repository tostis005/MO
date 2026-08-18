<?php
if(!defined('ABSPATH')) exit;
global $wpdb;
$rows=$wpdb->get_results("SELECT u.ID,u.user_login,u.display_name,um.meta_value FROM {$wpdb->users} u JOIN {$wpdb->usermeta} um ON um.user_id=u.ID AND um.meta_key='wcfmmp_profile_settings' ORDER BY u.ID",ARRAY_A);
$out=[];
foreach($rows as $r){$s=maybe_unserialize($r['meta_value']);$id=(int)$r['ID'];$published=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",$id));$all=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status NOT IN('trash','auto-draft') AND post_author=%d",$id));$out[]=['id'=>$id,'login'=>$r['user_login'],'display'=>$r['display_name'],'store_name'=>$s['store_name']??'','store_slug'=>$s['store_slug']??'','published_products'=>$published,'all_products'=>$all,'shop_description'=>$s['shop_description']??'','store_hide_policy'=>$s['store_hide_policy']??null,'store_hide_description'=>$s['store_hide_description']??null];}
echo json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
