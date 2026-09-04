<?php
/** Normalize the producer brand to the exact spelling “Montjam” (one word). */
if (!defined('ABSPATH')) { exit(1); }
global $wpdb;
function mjb_out($label,$value=null){
    if (is_array($value)||is_object($value)) $value=wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    echo $label . ($value===null?'':': '.$value) . "\n";
}

// Vendor / WCFM store.
$user = get_user_by('login','montjam');
if (!$user) throw new RuntimeException('Montjam vendor not found');
wp_update_user([
    'ID'=>$user->ID,
    'display_name'=>'Montjam',
    'nickname'=>'Montjam',
    'user_nicename'=>'montjam',
]);
update_user_meta($user->ID,'store_name','Montjam');
$profile = get_user_meta($user->ID,'wcfmmp_profile_settings',true);
if (!is_array($profile)) $profile=[];
$profile['store_name']='Montjam';
$profile['store_slug']='montjam';
update_user_meta($user->ID,'wcfmmp_profile_settings',$profile);

// Producer global attribute term, retaining the same term ID/relationships.
$producer = get_term_by('slug','mont-jam','pa_productor');
if (!$producer) $producer = get_term_by('slug','montjam','pa_productor');
if ($producer && !is_wp_error($producer)) {
    $updated = wp_update_term($producer->term_id,'pa_productor',['name'=>'Montjam','slug'=>'montjam']);
    if (is_wp_error($updated)) throw new RuntimeException($updated->get_error_message());
}

// Product tag, retaining relationships.
$tag = get_term_by('slug','mont-jam','product_tag');
if (!$tag) $tag = get_term_by('name','Mont Jam','product_tag');
if (!$tag) $tag = get_term_by('slug','montjam','product_tag');
if ($tag && !is_wp_error($tag)) {
    $updated = wp_update_term($tag->term_id,'product_tag',['name'=>'Montjam','slug'=>'montjam']);
    if (is_wp_error($updated)) throw new RuntimeException($updated->get_error_message());
}

$products = [
    ['old'=>'jamon-de-bellota-100-iberico-mont-jam','new'=>'jamon-de-bellota-100-iberico-montjam'],
    ['old'=>'jamon-de-bellota-iberico-50-mont-jam','new'=>'jamon-de-bellota-iberico-50-montjam'],
    ['old'=>'paleta-de-bellota-100-iberica-mont-jam','new'=>'paleta-de-bellota-100-iberica-montjam'],
];

$out=[];
foreach($products as $p){
    $post=get_page_by_path($p['new'],OBJECT,'product');
    if(!$post) $post=get_page_by_path($p['old'],OBJECT,'product');
    if(!$post) throw new RuntimeException('Product not found: '.$p['old']);
    $id=(int)$post->ID;

    $title=str_replace('Mont Jam','Montjam',$post->post_title);
    $content=str_replace('Mont Jam','Montjam',$post->post_content);
    $excerpt=str_replace('Mont Jam','Montjam',$post->post_excerpt);
    $r=wp_update_post([
        'ID'=>$id,
        'post_title'=>$title,
        'post_name'=>$p['new'],
        'post_content'=>$content,
        'post_excerpt'=>$excerpt,
    ],true);
    if(is_wp_error($r)) throw new RuntimeException($r->get_error_message());

    foreach(['_yoast_wpseo_focuskw','_yoast_wpseo_metadesc','_yoast_wpseo_title'] as $key){
        $v=get_post_meta($id,$key,true);
        if(is_string($v)&&$v!=='') update_post_meta($id,$key,str_replace('Mont Jam','Montjam',$v));
    }
    foreach(array_filter([(int)get_post_thumbnail_id($id)]) as $aid){
        $alt=get_post_meta($aid,'_wp_attachment_image_alt',true);
        if(is_string($alt)) update_post_meta($aid,'_wp_attachment_image_alt',str_replace('Mont Jam','Montjam',$alt));
    }
    $gallery=array_filter(array_map('intval',explode(',',(string)get_post_meta($id,'_product_image_gallery',true))));
    foreach($gallery as $aid){
        $alt=get_post_meta($aid,'_wp_attachment_image_alt',true);
        if(is_string($alt)) update_post_meta($aid,'_wp_attachment_image_alt',str_replace('Mont Jam','Montjam',$alt));
    }
    clean_post_cache($id);
    $out[]=['id'=>$id,'title'=>get_the_title($id),'slug'=>get_post_field('post_name',$id),'url'=>get_permalink($id)];
}

// YITH WAPO block names/settings created for these product IDs.
$blocks=$wpdb->prefix.'yith_wapo_blocks';
if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$blocks))===$blocks){
    foreach($out as $row){
        $block=$wpdb->get_row($wpdb->prepare("SELECT * FROM `$blocks` WHERE name LIKE %s LIMIT 1",'%Formato · '.$row['id']),ARRAY_A);
        if(!$block) continue;
        $new_name='Montjam · Formato · '.$row['id'];
        $settings=maybe_unserialize($block['settings']);
        if(is_array($settings)) $settings['name']=$new_name;
        $wpdb->update($blocks,['name'=>$new_name,'settings'=>maybe_serialize($settings),'last_update'=>current_time('mysql',true)],['id'=>(int)$block['id']]);
    }
}

wp_cache_flush();
mjb_out('MONTJAM_BRAND_NORMALIZED',['vendor_id'=>(int)$user->ID,'vendor_display'=>get_userdata($user->ID)->display_name,'vendor_nicename'=>get_userdata($user->ID)->user_nicename,'products'=>$out]);
