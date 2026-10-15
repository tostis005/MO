<?php
/**
 * Remove obsolete 'Imagen provisional' / 'Temporary editorial image'
 * from image attachment display metadata in WordPress production.
 * No file renames, URL changes, image swaps or WooCommerce mutations.
 */
if (!defined('ABSPATH')) { exit(1); }
const EMDO_BRAND_LABEL='El Mercado de Origen';
$old_labels=[
 'Imagen provisional del blog de El Mercado de Origen',
 'Temporary editorial image for El Mercado de Origen blog'
];
global $wpdb;
$ids=[];
$default_id=(int)get_option('emdo_default_blog_featured_attachment_id',0);
if ($default_id>0) $ids[$default_id]=true;
foreach ($old_labels as $old) {
 foreach ((array)$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='attachment' AND post_title=%s",$old)) as $id) $ids[(int)$id]=true;
 foreach ((array)$wpdb->get_col($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_wp_attachment_image_alt','_en_US_attachment_alt','_en_US_post_title') AND meta_value=%s",$old)) as $id) {
   if (get_post_type((int)$id)==='attachment') $ids[(int)$id]=true;
 }
}
if (!$ids) throw new RuntimeException('No central/default or legacy provisional attachment found.');
$oldmap=array_fill_keys($old_labels,true);
$keys=['_wp_attachment_image_alt','_en_US_attachment_alt','_en_US_post_title','_en_US_post_excerpt','_en_US_post_content'];
$snaps=[];$updated=[];$skipped=[];
try {
 foreach (array_keys($ids) as $id) {
   $attachment=get_post($id);
   if (!$attachment instanceof WP_Post || $attachment->post_type!=='attachment') continue;
   $snapshot=['post_title'=>$attachment->post_title,'post_excerpt'=>$attachment->post_excerpt,'post_content'=>$attachment->post_content,'metas'=>[]];
   foreach ($keys as $key) $snapshot['metas'][$key]=get_post_meta($id,$key,true);
   $snaps[$id]=$snapshot;
   $edit=[];
   foreach (['post_title','post_excerpt','post_content'] as $field) {
      if (isset($oldmap[trim((string)$attachment->$field)])) $edit[$field]=EMDO_BRAND_LABEL;
   }
   if ($edit) { $edit['ID']=$id; $r=wp_update_post(wp_slash($edit),true); if (is_wp_error($r)) throw new RuntimeException('Cannot update attachment '.$id.': '.$r->get_error_message()); }
   $meta_edits=[];
   foreach ($keys as $key) {
      $value=(string)get_post_meta($id,$key,true);
      if (isset($oldmap[trim($value)])) { update_post_meta($id,$key,EMDO_BRAND_LABEL);$meta_edits[]=$key; }
   }
   $should_branded=(int)$id===$default_id || '1'===(string)get_post_meta($id,'_emdo_default_blog_image',true);
   if ($should_branded) {
     foreach (['_wp_attachment_image_alt','_en_US_attachment_alt'] as $key) {
       if ((string)get_post_meta($id,$key,true)==='') { update_post_meta($id,$key,EMDO_BRAND_LABEL);$meta_edits[]=$key; }
     }
   }
   $new=get_post($id);
   foreach (['post_title','post_excerpt','post_content'] as $field) {
     if (isset($oldmap[trim((string)$new->$field)])) throw new RuntimeException('Title/excerpt/content still provisional '.$id);
   }
   foreach ($keys as $key) {
     if (isset($oldmap[trim((string)get_post_meta($id,$key,true))])) throw new RuntimeException('Provisional meta remains '.$id.':'.$key);
   }
   clean_post_cache($id);
   if ($edit||$meta_edits) $updated[]=['id'=>(int)$id,'title'=>(string)get_post_field('post_title',$id),'alt'=>(string)get_post_meta($id,'_wp_attachment_image_alt',true),'en_alt'=>(string)get_post_meta($id,'_en_US_attachment_alt',true),'changed_meta'=>$meta_edits,'file'=>(string)get_attached_file($id)];
   else $skipped[]=(int)$id;
 }
} catch (Throwable $e) {
 foreach ($snaps as $id=>$data) {
    wp_update_post(wp_slash(['ID'=>$id,'post_title'=>$data['post_title'],'post_excerpt'=>$data['post_excerpt'],'post_content'=>$data['post_content']]),true);
    foreach ($data['metas'] as $key=>$val) update_post_meta($id,$key,$val);
 }
 wp_cache_flush(); throw $e;
}
if (!$updated && !$skipped) throw new RuntimeException('No qualifying attachments found');
wp_cache_flush();
echo 'EMDO_IMAGE_ALT_AUDIT '.wp_json_encode(['verified'=>true,'central_id'=>$default_id,'checked'=>count($ids),'updated'=>$updated,'unchanged'=>$skipped],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
