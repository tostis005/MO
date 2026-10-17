<?php
/** Read-only audit: Chocolates Artesanos Isabel + bilingual chocolate posts. No image modifications. */
if (!defined('ABSPATH')) throw new RuntimeException('WordPress required');
if (!class_exists('WooCommerce')) throw new RuntimeException('WooCommerce required');
global $wpdb;
$supplier_id=4964;
$articles=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>60,'meta_key'=>'_emdo_chocolate_release','meta_value'=>'emdo-chocolates-isabel-20261017-30-bilingual','suppress_filters'=>true]);
$posts=[];$post_warnings=[];
foreach($articles as $post) {
 $id=(int)$post->ID;$n=(int)get_post_meta($id,'_emdo_chocolate_topic',true);
 $es=(string)$post->post_content;$en=(string)get_post_meta($id,'_en_US_post_content',true);
 $blog_category=wp_get_post_terms($id,'category',['fields'=>'slugs']);
 $line=['n'=>$n,'id'=>$id,'slug'=>$post->post_name,'es_chars'=>mb_strlen(wp_strip_all_tags($es)),'en_chars'=>mb_strlen(wp_strip_all_tags($en)),'category'=>$blog_category,
 'es_links'=>preg_match_all('/<a\\b/iu',$es),'en_links'=>preg_match_all('/<a\\b/iu',$en),
 'es_h2'=>preg_match_all('/<h2\\b/iu',$es),'en_h2'=>preg_match_all('/<h2\\b/iu',$en),
 'en_published'=>(string)get_post_meta($id,'_en_US_published',true),
 'seo_es'=>(string)get_post_meta($id,'_emdo_seo_description',true)!='',
 'seo_en'=>(string)get_post_meta($id,'_en_US_seo_description',true)!=''];
 $posts[]=$line;
 if($line['es_chars']<3000||$line['en_chars']<3000||!$line['seo_es']||!$line['seo_en']||!$line['en_published'])$post_warnings[]=$line['n'];
}
usort($posts,static fn($a,$b)=>$a['n']<=>$b['n']);
$products=[];$counts=['total'=>0,'published'=>0,'instock'=>0,'outofstock'=>0,'hidden'=>0,'approved_edits'=>0,'short_empty'=>0,'long_under300'=>0,'category_root_only'=>0,'category_conflicts'=>0,'duplicate_intro'=>0,'source_match'=>0,'source_preserved'=>0,'source_allergens_lost'=>0];
$prefixes=[];$category_counts=[];$issues=[];$source_table=class_exists('MDO_Database')?MDO_Database::table('source_products'):'';
$supplier_table=class_exists('MDO_Database')?MDO_Database::table('suppliers'):'';
$ids=[];
if(!$source_table||!$supplier_table)throw new RuntimeException('Supplier sync tables unavailable');
$ids=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT sp.wc_product_id FROM {$source_table} sp INNER JOIN {$supplier_table} s ON sp.supplier_id=s.id WHERE s.code=%s AND sp.wc_product_id > 0",'chocolates-artesanos-isabel'));
foreach($ids as $id){
 $p=wc_get_product($id);if(!$p || (int)get_post_field('post_author',$id)!==$supplier_id)continue;
 $n=$p->get_name();$long=trim(wp_strip_all_tags($p->get_description()));$short=trim(wp_strip_all_tags($p->get_short_description()));
 $categories=wp_get_post_terms($id,'product_cat',['fields'=>'slugs']);if(!is_array($categories))$categories=[];
 foreach($categories as $cat)$category_counts[$cat]=($category_counts[$cat]??0)+1;
 $counts['total']++;$counts['published']+=(int)($p->get_status()==='publish');$counts['instock']+=(int)$p->is_in_stock();$counts['outofstock']+=(int)!$p->is_in_stock();$counts['hidden']+=(int)!$p->is_visible();$counts['short_empty']+=(int)($short==='');$counts['long_under300']+=(int)(mb_strlen($long)<300);
 $counts['category_root_only']+=(int)(count($categories)===1&&in_array('chocolates-artesanos',$categories,true));
 $conflict=(bool)array_intersect($categories,['naranjas','frutas','carne','carnes','sin-categorizar','uncategorized']);
 if($conflict){$counts['category_conflicts']++;$issues[]=['id'=>$id,'issue'=>'foreign_category'];}
 $approved=get_post_meta($id,'_emdo_isabel_editorial_approved',true)==='1';$counts['approved_edits']+=(int)$approved;
 $source=(string)get_post_meta($id,'_emdo_source_url',true);$row=null;$original='';
 if($source_table&&$source){$raw=$wpdb->get_var($wpdb->prepare("SELECT source_payload FROM {$source_table} WHERE wc_product_id=%d LIMIT 1",$id));$a=json_decode((string)$raw,true);$original=trim(wp_strip_all_tags((string)($a['description']??'')));}
 $normalized=static fn($s)=>mb_strtolower(preg_replace('/\\s+/u',' ',html_entity_decode($s,ENT_QUOTES|ENT_HTML5,'UTF-8')));
 $counts['source_match']+=(int)($original!==''&&$normalized($original)===$normalized($long));
 $source_contained=$original!=='' && str_contains($normalized($long),$normalized($original));
 $counts['source_preserved']+=(int)$source_contained;
 if(!$source_contained)$issues[]=['id'=>$id,'issue'=>'source_description_not_preserved'];
 if($original&&preg_match('/al[eé]rgen|trazas|puede contener/ui',$original)&&!preg_match('/al[eé]rgen|trazas|puede contener/ui',$long)){
  $counts['source_allergens_lost']++;$issues[]=['id'=>$id,'issue'=>'allergen_label_missing'];
 }
 $prefix=mb_substr($normalized($long),0,100);if($prefix)$prefixes[$prefix]=($prefixes[$prefix]??0)+1;
 $products[]=['id'=>$id,'name'=>$n,'status'=>$p->get_status(),'stock'=>$p->get_stock_status(),'category'=>$categories,'long_len'=>mb_strlen($long),'short_len'=>mb_strlen($short),'source_len'=>mb_strlen($original),'source_equal'=>($original!==''&&$normalized($original)===$normalized($long)),'approved'=>$approved,'source_url'=>$source,'weight'=>$p->get_weight()];
}
$counts['duplicate_intro']=count(array_filter($prefixes,static fn($v)=>$v>1));
usort($products,static fn($a,$b)=>strcmp($a['name'],$b['name']));
$term_details=[];
foreach(['chocolates-artesanos','tabletas-de-chocolate','bombones-y-chocolatinas','cremas-de-cacao','cacao-para-beber-y-cocinar','packs-y-regalos','chocolates-de-temporada','kits-y-experiencias'] as $slug){
 $term=get_term_by('slug',$slug,'product_cat');
 if(!$term instanceof WP_Term){$term_details[$slug]=['missing'=>true];continue;}
 $term_details[$slug]=['id'=>(int)$term->term_id,'name'=>$term->name,'parent'=>(int)$term->parent,
 'description'=>wp_strip_all_tags((string)$term->description),
 'en_description'=>wp_strip_all_tags((string)get_term_meta($term->term_id,'_en_US_description',true)),
 'en_name'=>(string)get_term_meta($term->term_id,'_en_US_name',true)];
}
$blog_term=get_category_by_slug('chocolates-y-cacao');
if($blog_term instanceof WP_Term)$term_details['blog/chocolates-y-cacao']=['id'=>$blog_term->term_id,
 'description'=>wp_strip_all_tags((string)$blog_term->description),
 'en_description'=>wp_strip_all_tags((string)get_term_meta($blog_term->term_id,'_en_US_description',true))];

$related_counts=[];
if(function_exists('emdo_choco_related_products')){
 for($topic=1;$topic<=30;$topic++){
  $related=emdo_choco_related_products($topic);
  if(count($related)>4)throw new RuntimeException('More than four related products '.$topic);
  foreach($related as $rp)if(!$rp->is_visible()||!$rp->is_purchasable()||!$rp->is_in_stock()||$rp->get_stock_status()!=='instock')throw new RuntimeException('Unpurchasable or out-of-stock product suggested: '.$rp->get_id());
  $related_counts[$topic]=count($related);
 }
}
$duplicate_probe=[];
foreach([17526,17647] as $kit_id){
 $kit=wc_get_product($kit_id);
 if(!$kit)continue;
 $source_row=$wpdb->get_row($wpdb->prepare("SELECT id,status,source_url,source_payload FROM {$source_table} WHERE wc_product_id=%d LIMIT 1",$kit_id),ARRAY_A);
 $origin=is_array($source_row)?json_decode((string)$source_row['source_payload'],true):[];
 $duplicate_probe[$kit_id]=[
  'id'=>$kit_id,'title'=>$kit->get_name(),'slug'=>get_post_field('post_name',$kit_id),
  'url'=>get_permalink($kit_id),'status'=>$kit->get_status(),'visible'=>$kit->is_visible(),
  'purchasable'=>$kit->is_purchasable(),'stock'=>$kit->get_stock_status(),
  'price'=>$kit->get_price('edit'),'regular_price'=>$kit->get_regular_price('edit'),
  'sku'=>$kit->get_sku(),'weight'=>$kit->get_weight(),'image_id'=>$kit->get_image_id(),
  'source_row'=>$source_row['id']??null,'source_status'=>$source_row['status']??'',
  'source_url'=>$source_row['source_url']??'','source_price'=>$origin['price']??null,
  'source_sku'=>$origin['sku']??null,'description_sha'=>hash('sha256',(string)$kit->get_description()),
 ];
}
$summary=['generated_at'=>gmdate('c'),'counts'=>$counts,'categories'=>$category_counts,'posts_count'=>count($posts),'posts'=>$posts,'post_warnings'=>$post_warnings,'issues'=>$issues,'products'=>$products,'term_details'=>$term_details,'related_counts'=>$related_counts,'duplicate_kit_probe'=>$duplicate_probe];
echo 'ISABEL_EDITORIAL_AUDIT='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
