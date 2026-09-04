<?php
if (!defined('ABSPATH')) exit(1);
if (!post_type_exists('mdo_promotion') || !class_exists('MDO_Promotions')) throw new RuntimeException('Promotion system unavailable');
$seed='montjam-jamon-bellota-225-v1';
$found=get_posts(['post_type'=>'mdo_promotion','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_mdo_promo_seed_key','meta_value'=>$seed]);
if(!$found) throw new RuntimeException('Montjam promotion not found after creation');
$id=(int)$found[0];
if(get_post_status($id)!=='publish') throw new RuntimeException('Montjam promotion not published');
if(get_post_meta($id,'_mdo_promo_featured_home',true)!=='1') throw new RuntimeException('Home flag missing');
if(!MDO_Promotions::is_active($id)) throw new RuntimeException('Montjam promotion is not active');
$ids=get_posts(['post_type'=>'mdo_promotion','post_status'=>'publish','posts_per_page'=>20,'fields'=>'ids','orderby'=>['menu_order'=>'ASC','date'=>'DESC'],'meta_key'=>'_mdo_promo_featured_home','meta_value'=>'1']);
$selected=0; foreach($ids as $pid){if(MDO_Promotions::is_active((int)$pid)){$selected=(int)$pid;break;}}
if($selected!==$id) throw new RuntimeException('Home would select '.$selected.' instead of '.$id);
wp_cache_flush();
if(function_exists('rocket_clean_domain')) rocket_clean_domain();
if(function_exists('w3tc_flush_all')) w3tc_flush_all();
do_action('litespeed_purge_all');
do_action('wpfc_clear_all_cache');
clean_post_cache($id);
clean_post_cache((int)get_option('page_on_front'));
echo 'MONTJAM_PROMOTION_VERIFIED:'.wp_json_encode(['id'=>$id,'title'=>get_the_title($id),'active'=>true,'home_selected'=>$selected,'featured_home'=>get_post_meta($id,'_mdo_promo_featured_home',true),'thumbnail'=>(int)get_post_thumbnail_id($id),'summary'=>get_post_meta($id,'_mdo_promo_summary',true),'cta'=>get_post_meta($id,'_mdo_promo_cta_url',true),'promo_url'=>get_permalink($id)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
