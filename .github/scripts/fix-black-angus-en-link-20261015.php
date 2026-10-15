<?php
/** Correct one verified broken internal English blog link without touching other content. */
if(!defined('ABSPATH'))exit(1);
$slug='entrecot-black-angus-plancha-receta';
$old='https://www.elmercadodeorigen.com/en/wagyu-vs-angus-marbling-flavour-differences/';
$new='https://www.elmercadodeorigen.com/en/wagyu-vs-angus-differences-marbling-flavour/';
$p=get_page_by_path($slug,OBJECT,'post');
if(!$p instanceof WP_Post || $p->post_status!=='publish')throw new RuntimeException('Missing Black Angus blog article');
$id=(int)$p->ID;
$dest=$GLOBALS['wpdb']->get_var($GLOBALS['wpdb']->prepare(
 "SELECT p.ID FROM {$GLOBALS['wpdb']->posts} p INNER JOIN {$GLOBALS['wpdb']->postmeta} m ON m.post_id=p.ID
 WHERE p.post_type='post' AND p.post_status='publish' AND m.meta_key='_en_US_post_name' AND m.meta_value=%s LIMIT 1",
 'wagyu-vs-angus-differences-marbling-flavour'
));
if(!$dest)throw new RuntimeException('Canonical destination does not exist');
$en=(string)get_post_meta($id,'_en_US_post_content',true);
if(!str_contains($en,$old)&&!str_contains($en,$new))throw new RuntimeException('Neither known link is present; refusing unexpected edit');
$changed=str_contains($en,$old);
if($changed){
 $updated=str_replace($old,$new,$en);
 update_post_meta($id,'_en_US_post_content',$updated);
}
$check=(string)get_post_meta($id,'_en_US_post_content',true);
if(str_contains($check,$old)||!str_contains($check,$new)||!str_contains($check,'EMDO_PHOTO_CREDIT_20261015_')) {
 if($changed)update_post_meta($id,'_en_US_post_content',$en);
 throw new RuntimeException('Failed to preserve English body/photo credits or correct the link');
}
wp_cache_flush();
echo 'EMDO_ANGUS_LINK_FIX '.wp_json_encode(['verified'=>true,'changed'=>$changed,'post_id'=>$id,'destination_id'=>(int)$dest,'old'=>$old,'new'=>$new],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
