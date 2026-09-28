<?php
if(!defined('ABSPATH')) exit;
$terms=array('pato','duck','foie','magret','confit','jamón de pato','jamon de pato','rillettes');
$out=['attachments'=>[],'products'=>[]];
foreach($terms as $term){
  $atts=get_posts(['post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>50,'s'=>$term]);
  foreach($atts as $a){
    $id=(int)$a->ID;
    $file=(string)get_attached_file($id);
    $out['attachments'][$id]=[
      'id'=>$id,'title'=>$a->post_title,'caption'=>$a->post_excerpt,'alt'=>(string)get_post_meta($id,'_wp_attachment_image_alt',true),
      'url'=>(string)wp_get_attachment_url($id),'file'=>$file?basename($file):'','mime'=>(string)get_post_mime_type($id)
    ];
  }
  $prods=get_posts(['post_type'=>'product','post_status'=>['publish','draft','private','pending'],'posts_per_page'=>50,'s'=>$term]);
  foreach($prods as $p){
    $id=(int)$p->ID;
    $out['products'][$id]=[
      'id'=>$id,'status'=>$p->post_status,'title'=>$p->post_title,'slug'=>$p->post_name,'thumb'=>(int)get_post_thumbnail_id($id),
      'url'=>get_permalink($id)
    ];
  }
}
$out['attachments']=array_values($out['attachments']);
$out['products']=array_values($out['products']);
echo wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
