<?php
/** Read-only live WooCommerce Gourmet category and available taxonomy inventory. */
if(!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI)exit(1);
$site=strtolower((string)wp_parse_url(get_option('siteurl'),PHP_URL_HOST));
if(!in_array($site,['www.elmercadodeorigen.com','elmercadodeorigen.com'],true)){throw new RuntimeException('Invalid host');}
if(!class_exists('WooCommerce'))throw new RuntimeException('No WooCommerce');
$term=get_term_by('slug','gourmet','product_cat');
if(!$term instanceof WP_Term)throw new RuntimeException('Gourmet product category not found');
$all_terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
if(is_wp_error($all_terms))throw new RuntimeException('Taxonomy unavailable');
$term_index=[];
foreach($all_terms as $cat){$term_index[(int)$cat->term_id]=['id'=>(int)$cat->term_id,'name'=>$cat->name,'slug'=>$cat->slug,'parent_id'=>(int)$cat->parent,'count'=>(int)$cat->count];}
usort($all_terms,static fn($a,$b)=>strcasecmp($a->name,$b->name));
foreach($all_terms as $cat){
  if((int)$cat->count===0 && !preg_match('/^(frut|aceit|conserv|mermel|fruto|semill|miel|bebi|zumo|infusi|t[eé]|gourmet|sal|pack|lote|cosm[eé]t|hortal|verdura|aceite)/iu',$cat->name))continue;
  echo 'EMDO_EXISTING_CATEGORY='.wp_json_encode($term_index[$cat->term_id],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
$ids=get_posts(['post_type'=>'product','post_status'=>['publish','private','draft','pending','future'],'posts_per_page'=>-1,'fields'=>'ids','orderby'=>'ID','order'=>'ASC','tax_query'=>[['taxonomy'=>'product_cat','field'=>'term_id','terms'=>(int)$term->term_id,'include_children'=>false]]]);
if(!is_array($ids))throw new RuntimeException('Could not query products');
$status_counts=[];$vendor_counts=[];$source_counts=[];$all=[];
foreach($ids as $post_id){
 $p=wc_get_product((int)$post_id);
 if(!$p)continue;
 $status=$p->get_status();$status_counts[$status]=($status_counts[$status]??0)+1;
 $author=(int)get_post_field('post_author',$post_id);
 $source=(string)get_post_meta($post_id,'_emdo_source_url',true);
 $origin=(string)wp_parse_url($source,PHP_URL_HOST);
 $vendor_counts[(string)$author]=($vendor_counts[(string)$author]??0)+1;
 $source_counts[$origin?:'other']=($source_counts[$origin?:'other']??0)+1;
 $cats=array_map(static function($cat)use($term_index){return $term_index[$cat]??['id'=>$cat,'name'=>'unknown'];},$p->get_category_ids());
 $data=['id'=>(int)$post_id,'title'=>$p->get_name(),'sku'=>$p->get_sku(),'status'=>$status,'type'=>$p->get_type(),'author'=>$author,'permalink'=>get_permalink((int)$post_id),'source_host'=>$origin,'categories'=>$cats];
 $all[]=$data;
 echo 'EMDO_GOURMET_PRODUCT='.wp_json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
echo 'EMDO_GOURMET_SUMMARY='.wp_json_encode(['term'=>$term_index[$term->term_id], 'total'=>count($all),'status_counts'=>$status_counts,'vendor_counts'=>$vendor_counts,'source_counts'=>$source_counts,'catalogue_category_count'=>count($term_index)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
