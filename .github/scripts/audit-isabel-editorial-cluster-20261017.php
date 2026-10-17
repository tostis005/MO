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
$products=[];$counts=['total'=>0,'published'=>0,'instock'=>0,'outofstock'=>0,'hidden'=>0,'approved_edits'=>0,'short_empty'=>0,'long_under300'=>0,'category_root_only'=>0,'category_conflicts'=>0,'duplicate_intro'=>0,'source_match'=>0,'source_allergens_lost'=>0];
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
 if($original&&preg_match('/al[eé]rgen|trazas|puede contener/ui',$original)&&!preg_match('/al[eé]rgen|trazas|puede contener/ui',$long)){
  $counts['source_allergens_lost']++;$issues[]=['id'=>$id,'issue'=>'allergen_label_missing'];
 }
 $prefix=mb_substr($normalized($long),0,100);if($prefix)$prefixes[$prefix]=($prefixes[$prefix]??0)+1;
 $products[]=['id'=>$id,'name'=>$n,'status'=>$p->get_status(),'stock'=>$p->get_stock_status(),'category'=>$categories,'long_len'=>mb_strlen($long),'short_len'=>mb_strlen($short),'source_len'=>mb_strlen($original),'source_equal'=>($original!==''&&$normalized($original)===$normalized($long)),'approved'=>$approved,'source_url'=>$source,'weight'=>$p->get_weight()];
}
$counts['duplicate_intro']=count(array_filter($prefixes,static fn($v)=>$v>1));
usort($products,static fn($a,$b)=>strcmp($a['name'],$b['name']));
$summary=['generated_at'=>gmdate('c'),'counts'=>$counts,'categories'=>$category_counts,'posts_count'=>count($posts),'posts'=>$posts,'post_warnings'=>$post_warnings,'issues'=>$issues,'products'=>$products];
echo 'ISABEL_EDITORIAL_AUDIT='.wp_json_encode($summary,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
