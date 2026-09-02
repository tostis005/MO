<?php
if (!defined('ABSPATH')) exit;
$category_ids=[439,440,441,442,443,444,438,450,445];
$slugs=[
'dop-igp-etg-diferencias-sellos-calidad-alimentos',
'como-leer-etiqueta-alimento-ingredientes-nutricion-origen-lote-conservacion',
'fecha-caducidad-consumo-preferente-diferencias',
'origen-etiqueta-alimento-pais-procedencia-ingrediente-primario',
'trazabilidad-alimentaria-que-es-como-funciona-productor-consumidor'
];
$backup=['created_at'=>current_time('mysql'),'categories'=>[],'attachment'=>null,'posts'=>[]];
foreach($category_ids as $id){
 $t=get_term($id,'category'); if(!$t||is_wp_error($t)) continue;
 $backup['categories'][$id]=['name'=>$t->name,'slug'=>$t->slug,'description'=>$t->description,'en_description'=>(string)get_term_meta($id,'_en_US_description',true),'en_published'=>(string)get_term_meta($id,'_en_US_published',true)];
}
$aid=13442;
if(get_post($aid)) $backup['attachment']=['id'=>$aid,'title'=>get_the_title($aid),'alt'=>(string)get_post_meta($aid,'_wp_attachment_image_alt',true)];
foreach($slugs as $slug){
 $p=get_page_by_path($slug,OBJECT,'post');
 if(!$p){ $backup['posts'][$slug]=null; continue; }
 $meta=[]; foreach(['_en_US_post_title','_en_US_post_name','_en_US_post_excerpt','_en_US_post_content','_en_US_ready','_en_US_published','_emdo_seo_title','_emdo_seo_description','_en_US_seo_title','_en_US_seo_description'] as $k) $meta[$k]=get_post_meta($p->ID,$k,true);
 $backup['posts'][$slug]=['ID'=>$p->ID,'post_title'=>$p->post_title,'post_name'=>$p->post_name,'post_excerpt'=>$p->post_excerpt,'post_content'=>$p->post_content,'post_status'=>$p->post_status,'post_author'=>$p->post_author,'categories'=>wp_get_post_categories($p->ID),'thumbnail'=>get_post_thumbnail_id($p->ID),'meta'=>$meta];
}
update_option('emdo_seo_next_pass_backup_20260902',$backup,false);
echo "EMDO_SEO_NEXT_BACKUP_BEGIN\n".wp_json_encode(['saved'=>true,'categories'=>count($backup['categories']),'existing_posts'=>count(array_filter($backup['posts'])),'attachment'=>(bool)$backup['attachment']],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\nEMDO_SEO_NEXT_BACKUP_END\n";
