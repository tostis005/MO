<?php
if ( ! defined( 'ABSPATH' ) ) { fwrite(STDERR,"WordPress required\n"); exit(2); }

function mdoqa_meta_state( int $id, string $type ): array {
    $prefix = '_en_US_';
    $published = (string) get_post_meta($id,$prefix.'published',true) === '1';
    $name  = trim(wp_strip_all_tags((string)get_post_meta($id,$prefix.'post_name',true)));
    $title = trim(wp_strip_all_tags((string)get_post_meta($id,$prefix.'post_title',true)));
    $content = (string)get_post_meta($id,$prefix.'post_content',true);
    $excerpt = (string)get_post_meta($id,$prefix.'post_excerpt',true);
    $post = get_post($id);
    $issues=[];
    if (!$published) $issues[]='not_en_published';
    if ($name==='') $issues[]='missing_en_slug';
    if ($title==='') $issues[]='missing_en_title';
    if ($post instanceof WP_Post) {
        if (trim(wp_strip_all_tags((string)$post->post_content))!=='' && trim(wp_strip_all_tags($content))==='') $issues[]='missing_en_content';
        if ($type==='post' && trim(wp_strip_all_tags((string)$post->post_excerpt))!=='' && trim(wp_strip_all_tags($excerpt))==='') $issues[]='missing_en_excerpt';
    }
    return ['id'=>$id,'type'=>$type,'es_slug'=>$post?$post->post_name:'','es_title'=>$post?$post->post_title:'','en_slug'=>$name,'en_title'=>$title,'issues'=>$issues];
}

$out=['summary'=>[],'objects'=>[],'terms'=>[],'attributes'=>[],'options'=>[]];
foreach (['page','post','product'] as $type) {
    $ids=get_posts(['post_type'=>$type,'post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','suppress_filters'=>true]);
    $eligible=[];
    foreach(array_map('intval',$ids) as $id){
        if ($type==='product') {
            if (function_exists('elmercado_wcfm_product_is_from_disabled_vendor_010210') && elmercado_wcfm_product_is_from_disabled_vendor_010210($id)) continue;
            if (function_exists('wc_get_product')) { $p=wc_get_product($id); if(!$p || $p->get_catalog_visibility()==='hidden') continue; }
        }
        $eligible[]=$id;
        $row=mdoqa_meta_state($id,$type);
        if ($row['issues']) $out['objects'][]=$row;
    }
    $out['summary'][$type.'_published']=count($ids);
    $out['summary'][$type.'_eligible']=count($eligible);
    $out['summary'][$type.'_with_issues']=count(array_filter($out['objects'],fn($x)=>$x['type']===$type));
}

$mentta_ids=[];
$mentta=get_term_by('slug','mentta','product_cat');
if($mentta instanceof WP_Term){$mentta_ids[]=$mentta->term_id;$children=get_term_children($mentta->term_id,'product_cat');if(!is_wp_error($children))$mentta_ids=array_merge($mentta_ids,array_map('intval',$children));}
foreach(['product_cat','product_tag','category'] as $tax){
    if(!taxonomy_exists($tax))continue;
    $terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>true]); if(is_wp_error($terms))continue;
    $count=0;$bad=0;
    foreach($terms as $term){
        if($tax==='product_cat' && in_array((int)$term->term_id,$mentta_ids,true))continue;
        $count++;
        $pub=(string)get_term_meta($term->term_id,'_en_US_published',true)==='1';
        $slug=trim(wp_strip_all_tags((string)get_term_meta($term->term_id,'_en_US_slug',true)));
        $name=trim(wp_strip_all_tags((string)get_term_meta($term->term_id,'_en_US_name',true)));
        $desc=(string)get_term_meta($term->term_id,'_en_US_description',true);
        $issues=[]; if(!$pub)$issues[]='not_en_published'; if($slug==='')$issues[]='missing_en_slug'; if($name==='')$issues[]='missing_en_name';
        if(trim(wp_strip_all_tags((string)$term->description))!=='' && trim(wp_strip_all_tags($desc))==='')$issues[]='missing_en_description';
        if($issues){$bad++;$out['terms'][]=['taxonomy'=>$tax,'id'=>$term->term_id,'es_slug'=>$term->slug,'es_name'=>$term->name,'en_slug'=>$slug,'en_name'=>$name,'issues'=>$issues];}
    }
    $out['summary'][$tax.'_eligible']=$count;$out['summary'][$tax.'_with_issues']=$bad;
}

if(function_exists('wc_get_attribute_taxonomies')){
    foreach((array)wc_get_attribute_taxonomies() as $a){
        $tax='pa_'.$a->attribute_name; $terms=get_terms(['taxonomy'=>$tax,'hide_empty'=>true]); if(is_wp_error($terms))$terms=[];
        $rows=[];
        foreach($terms as $term){
            $rows[]=['id'=>$term->term_id,'slug'=>$term->slug,'name'=>$term->name,'en_name'=>(string)get_term_meta($term->term_id,'_en_US_name',true),'en_slug'=>(string)get_term_meta($term->term_id,'_en_US_slug',true),'en_published'=>(string)get_term_meta($term->term_id,'_en_US_published',true)];
        }
        $out['attributes'][]=['taxonomy'=>$tax,'attribute_name'=>$a->attribute_name,'attribute_label'=>$a->attribute_label,'rewrite_slug'=>isset($a->attribute_public)?$a->attribute_public:null,'terms'=>$rows];
    }
}

foreach(['woocommerce_shop_page_id','woocommerce_cart_page_id','woocommerce_checkout_page_id','woocommerce_myaccount_page_id'] as $key){
    $id=(int)get_option($key);$out['options'][$key]=['id'=>$id,'es_slug'=>$id?get_post_field('post_name',$id):'','en_slug'=>$id?(string)get_post_meta($id,'_en_US_post_name',true):''];
}

$out['summary']['object_issue_total']=count($out['objects']);$out['summary']['term_issue_total']=count($out['terms']);
echo wp_json_encode($out,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),"\n";
