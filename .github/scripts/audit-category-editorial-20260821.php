<?php
/* Trigger 2026-08-21: audit current production categories after workflow registration. */
if ( ! defined( 'ABSPATH' ) ) { exit(1); }
$terms = get_terms(array('taxonomy'=>'product_cat','hide_empty'=>false));
if ( is_wp_error($terms) ) { fwrite(STDERR,$terms->get_error_message()."\n"); exit(2); }
$out=array('generated_at'=>gmdate('c'),'default_product_cat'=>(int)get_option('default_product_cat'),'terms'=>array());
foreach($terms as $term){
    $id=(int)$term->term_id;
    $out['terms'][]=array(
        'id'=>$id,
        'name'=>(string)$term->name,
        'slug'=>(string)$term->slug,
        'parent'=>(int)$term->parent,
        'count'=>(int)$term->count,
        'description'=>(string)$term->description,
        'en_published'=>(string)get_term_meta($id,'_en_US_published',true),
        'en_name'=>(string)get_term_meta($id,'_en_US_name',true),
        'en_slug'=>(string)get_term_meta($id,'_en_US_slug',true),
        'en_description'=>(string)get_term_meta($id,'_en_US_description',true),
        'en_hub_summary'=>(string)get_term_meta($id,'_emdo_en_hub_summary',true),
        'thumbnail_id'=>(int)get_term_meta($id,'thumbnail_id',true),
    );
}
usort($out['terms'],static function($a,$b){return [$a['parent'],$a['name']] <=> [$b['parent'],$b['name']];});
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
