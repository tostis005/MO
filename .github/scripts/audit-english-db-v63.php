<?php
if ( ! defined( 'ABSPATH' ) ) { fwrite(STDERR,"WordPress required\n"); exit(2); }

function mdoqa_meta_state( int $id, string $type ): array {
    $prefix='_en_US_'; $post=get_post($id); $issues=[];
    $published=(string)get_post_meta($id,$prefix.'published',true)==='1';
    $name=trim(wp_strip_all_tags((string)get_post_meta($id,$prefix.'post_name',true)));
    $title=trim(wp_strip_all_tags((string)get_post_meta($id,$prefix.'post_title',true)));
    $content=(string)get_post_meta($id,$prefix.'post_content',true);
    if(!$published)$issues[]='not_en_published'; if($name==='')$issues[]='missing_en_slug'; if($title==='')$issues[]='missing_en_title';
    if($post instanceof WP_Post && trim(wp_strip_all_tags((string)$post->post_content))!=='' && trim(wp_strip_all_tags($content))==='')$issues[]='missing_en_content';
    return ['id'=>$id,'type'=>$type,'es_slug'=>$post?$post->post_name:'','es_title'=>$post?$post->post_title:'','en_slug'=>$name,'en_title'=>$title,'issues'=>$issues];
}

$out=['summary'=>[],'objects'=>[],'terms'=>[],'attribute_labels'=>[],'attribute_issues'=>[],'options'=>[]];
foreach(['page','post','product'] as $type){
    $ids=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','suppress_filters'=>true]); $eligible=[];
    foreach(array_map('intval',$ids) as $id){
        if($type==='product'){
            if(function_exists('elmercado_wcfm_product_is_from_disabled_vendor_010210')&&elmercado_wcfm_product_is_from_disabled_vendor_010210($id))continue;
            if(function_exists('wc_get_product')){$p=wc_get_product($id);if(!$p||$p->get_catalog_visibility()==='hidden')continue;}
        }
        $eligible[]=$id;$row=mdoqa_meta_state($id,$type);if($row['issues'])$out['objects'][]=$row;
    }
    $out['summary'][$type.'_published']=count($ids);$out['summary'][$type.'_eligible']=count($eligible);$out['summary'][$type.'_with_issues']=count(array_filter($out['objects'],fn($x)=>$x['type']===$type));
}

$mentta_ids=[];$mentta=get_term_by('slug','mentta','product_cat');if($mentta instanceof WP_Term){$mentta_ids[]=$mentta->term_id;$children=get_term_children($mentta->term_id,'product_cat');if(!is_wp_error($children))$mentta_ids=array_merge($mentta_ids,array_map('intval',$children));}
foreach(['product_cat','product_tag','category'] as $tax){
    if(!taxonomy_exists($tax))continue;$terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>true]);if(is_wp_error($terms))continue;$count=0;$bad=0;
    foreach($terms as $term){
        if($tax==='product_cat'&&in_array((int)$term->term_id,$mentta_ids,true))continue;$count++;
        $pub=(string)get_term_meta($term->term_id,'_en_US_published',true)==='1';$slug=trim(wp_strip_all_tags((string)get_term_meta($term->term_id,'_en_US_slug',true)));$name=trim(wp_strip_all_tags((string)get_term_meta($term->term_id,'_en_US_name',true)));$desc=(string)get_term_meta($term->term_id,'_en_US_description',true);$issues=[];
        if(!$pub)$issues[]='not_en_published';if($slug==='')$issues[]='missing_en_slug';if($name==='')$issues[]='missing_en_name';if(trim(wp_strip_all_tags((string)$term->description))!==''&&trim(wp_strip_all_tags($desc))==='')$issues[]='missing_en_description';
        if($issues){$bad++;$out['terms'][]=['taxonomy'=>$tax,'id'=>$term->term_id,'es_slug'=>$term->slug,'es_name'=>$term->name,'en_slug'=>$slug,'en_name'=>$name,'issues'=>$issues];}
    }
    $out['summary'][$tax.'_eligible']=$count;$out['summary'][$tax.'_with_issues']=$bad;
}

/* Exercise the actual English label filter and scan English term data for Spanish residues. */
$_SERVER['REQUEST_URI']='/en/qa-attribute-audit/';
$spanish_name_re='/\b(?:sí|meses?|menos|cortado|cortada|deshuesado|deshuesada|virutas|codillo|taco|carne|cerdo|ib[eé]ric[oa]|bellota|campo|pieza|piezas|bote|botella|caja|saco|tarro|medias|envasadas?|vac[ií]o|sin filtrar|tradicional|paleta)\b/iu';
$spanish_slug_re='/(?:^|-)(?:si|meses?|menos|cortado|cortada|deshuesado|virutas|codillo|carne|cerdo|iberico|iberica|bellota|campo|pieza|piezas|bote|botella|caja|saco|tarro|medias|envasadas|vacio|sin-filtrar|tradicional|paleta)(?:-|$)/i';
if(function_exists('wc_get_attribute_taxonomies')){
    foreach((array)wc_get_attribute_taxonomies() as $a){
        $tax='pa_'.$a->attribute_name;
        $rendered=function_exists('wc_attribute_label')?wc_attribute_label($tax):$a->attribute_label;
        $out['attribute_labels'][]=['taxonomy'=>$tax,'stored'=>$a->attribute_label,'english_rendered'=>$rendered,'public'=>(int)$a->attribute_public];
        if(!taxonomy_exists($tax))continue;$terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>true]);if(is_wp_error($terms))continue;
        foreach($terms as $term){
            $name=trim(wp_strip_all_tags((string)get_term_meta($term->term_id,'_en_US_name',true)));$slug=trim((string)get_term_meta($term->term_id,'_en_US_slug',true));$issues=[];
            if((string)get_term_meta($term->term_id,'_en_US_published',true)!=='1')$issues[]='not_en_published';
            if($name==='')$issues[]='missing_en_name';elseif(preg_match($spanish_name_re,$name))$issues[]='spanish_word_in_en_name';
            if($slug==='')$issues[]='missing_en_slug';elseif(preg_match($spanish_slug_re,$slug))$issues[]='spanish_word_in_en_slug';
            if(preg_match('/(?<=\d),(?=\d)/u',$name))$issues[]='decimal_comma_in_en_name';
            if(preg_match('/\bDOP\b/u',$name))$issues[]='dop_in_en_name';
            if($issues)$out['attribute_issues'][]=['taxonomy'=>$tax,'id'=>$term->term_id,'native_slug'=>$term->slug,'native_name'=>$term->name,'en_slug'=>$slug,'en_name'=>$name,'issues'=>array_values(array_unique($issues))];
        }
    }
}
foreach(['woocommerce_shop_page_id','woocommerce_cart_page_id','woocommerce_checkout_page_id','woocommerce_myaccount_page_id'] as $key){$id=(int)get_option($key);$out['options'][$key]=['id'=>$id,'es_slug'=>$id?get_post_field('post_name',$id):'','en_slug'=>$id?(string)get_post_meta($id,'_en_US_post_name',true):''];}
$out['summary']['object_issue_total']=count($out['objects']);$out['summary']['term_issue_total']=count($out['terms']);$out['summary']['attribute_issue_total']=count($out['attribute_issues']);
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
