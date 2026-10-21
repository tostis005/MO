<?php
/** Read-only audit of editorial/shop bidirectional SEO cluster architecture. */
if (!defined('ABSPATH')) throw new RuntimeException('WordPress required');
$group_ids = [
  'cafe' => [1,2,3,4,5,6,7,8,9],
  'chocolate' => [10],
  'aguacate' => [11,12,13,14],
  'mango' => [15,16,17],
  'citricos' => [18,19,20],
  'frutas' => [21,22,23,24]
];
$rows=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>150,'meta_query'=>[['key'=>'_emdo_newprod_plan_id','compare'=>'EXISTS']],'suppress_filters'=>true]);
$articles=[];
foreach($rows as $p){
  $n=(int)get_post_meta($p->ID,'_emdo_newprod_plan_id',true);
  if(!$n || $n>24)continue;
  $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
  $extract=static fn(string $html):array=>array_map(static fn($a)=>(string)($a[1]??''),array_slice(iterator_to_array((static function($a){foreach($a as $b)yield $b;})([])),0));
  $anchors=static function(string $html):array{
    preg_match_all('~<a\b[^>]*href=["\x27]([^"\x27]+)["\x27][^>]*>(.*?)</a>~is',$html,$matches,PREG_SET_ORDER);
    return array_map(static fn($m)=>['url'=>html_entity_decode($m[1],ENT_QUOTES|ENT_HTML5),'text'=>wp_strip_all_tags($m[2])],$matches);
  };
  $terms=wp_get_post_terms($p->ID,'category',['fields'=>'slugs']);
  $articles[$n]=[
     'id'=>$p->ID,'plan'=>$n,'title'=>$p->post_title,
     'slug'=>$p->post_name,'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),
     'terms'=>is_wp_error($terms)?[]:$terms,
     'links_es'=>$anchors($p->post_content),'links_en'=>$anchors($en),
     'word_es'=>count(preg_split('/\s+/u',trim(wp_strip_all_tags($p->post_content)))),
     'word_en'=>count(preg_split('/\s+/u',trim(wp_strip_all_tags($en)))),
     'h2_es'=>preg_match_all('~<h2\b~i',$p->post_content),'h2_en'=>preg_match_all('~<h2\b~i',$en),
     'image'=>(int)get_post_thumbnail_id($p->ID),
     'shop_url'=>(string)get_post_meta($p->ID,'_emdo_newprod_product_category_url',true),
     'status'=>$p->post_status,
  ];
}
ksort($articles);
$choco=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>40,'meta_key'=>'_emdo_chocolate_release','meta_value'=>'emdo-chocolates-isabel-20261017-30-bilingual','suppress_filters'=>true]);
$choco_rows=[];
foreach($choco as $p){
 $topic=(int)get_post_meta($p->ID,'_emdo_chocolate_topic',true);
 $en=(string)get_post_meta($p->ID,'_en_US_post_content',true);
 preg_match_all('~<a\b[^>]*href=["\x27]([^"\x27]+)~i',$p->post_content,$mm);
 preg_match_all('~<a\b[^>]*href=["\x27]([^"\x27]+)~i',$en,$nn);
 $choco_rows[]=['id'=>$p->ID,'topic'=>$topic,'title'=>$p->post_title,'es_links'=>$mm[1]??[],'en_links'=>$nn[1]??[],
   'words_es'=>count(preg_split('/\s+/u',trim(wp_strip_all_tags($p->post_content)))),
   'words_en'=>count(preg_split('/\s+/u',trim(wp_strip_all_tags($en)))),
   'h2_es'=>preg_match_all('~<h2\b~i',$p->post_content),
   'en_published'=>(string)get_post_meta($p->ID,'_en_US_published',true),'featured'=>(int)get_post_thumbnail_id($p->ID)];
}
$terms=get_terms(['taxonomy'=>'product_cat','hide_empty'=>false]);
$cat_info=[];
foreach($terms as $term){
 $label=mb_strtolower($term->name.' '.$term->slug);
 if(!preg_match('/cafe|caf[eé]|mango|aguacat|chocolat|cacao|naranja|mandarina|c[ií]tric|fruta|tropic/u',$label))continue;
 $product_ids=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>100,'fields'=>'ids',
  'tax_query'=>[['taxonomy'=>'product_cat','field'=>'term_id','terms'=>[$term->term_id],'include_children'=>false]]]);
 $stock=[];$samples=[];
 foreach($product_ids as $pid){$prod=wc_get_product($pid);if(!$prod)continue;
   if($prod->is_visible() && $prod->is_purchasable() && $prod->is_in_stock()){$stock[]=$pid;if(count($samples)<5)$samples[]=['id'=>$pid,'name'=>$prod->get_name(),'url'=>get_permalink($pid)];}
 }
 $cat_info[]=['id'=>$term->term_id,'slug'=>$term->slug,'name'=>$term->name,'parent'=>$term->parent,'url'=>get_term_link($term),
   'direct_published'=>count($product_ids),'direct_instock_buyable'=>count($stock),
   'sample'=>$samples,'description_words'=>str_word_count(wp_strip_all_tags(term_description($term->term_id,'product_cat')))];
}
$selected_products=[];
foreach([18073,18065,18057,20541,20567,19048,18636,18491,17803,17875,19066,18814] as $id){
 $p=wc_get_product($id);if(!$p)continue;
 $terms=wp_get_post_terms($id,'product_cat',['fields'=>'slugs']);
 $selected_products[]=['id'=>$id,'title'=>$p->get_name(),'status'=>$p->get_status(),'in_stock'=>$p->is_in_stock(),
   'purchasable'=>$p->is_purchasable(),'terms'=>is_wp_error($terms)?[]:$terms,
   'url'=>get_permalink($id),'short_words'=>count(preg_split('/\s+/u',trim(wp_strip_all_tags($p->get_short_description()))))];
}
$mu=WPMU_PLUGIN_DIR;
$modules=[];
foreach(['mdo-chocolates-seo-cluster-20261017.php','mdo-global-seo-cluster-architecture-20261001.php',
'mdo-english-island-runtime-seo.php','0000-emdo-editorial-24-seo-20261021.php'] as $f)$modules[$f]=file_exists("$mu/$f");
echo 'EMDO_CLUSTER_AUDIT '.wp_json_encode([
'articles'=>$articles,'chocolate_count'=>count($choco_rows),'chocolate_posts'=>$choco_rows,'categories'=>$cat_info,
'products'=>$selected_products,'modules'=>$modules,'seen'=>['approved'=>count($articles),'chocolate'=>count($choco)]],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;

$termcheck=[];
foreach(['frutas','aguacates','mangos','citricos','frutas-tropicales','cafe','chocolates-y-cacao'] as $slug){
 $term=get_term_by('slug',$slug,'category');
 $termcheck[$slug]=$term instanceof WP_Term?['id'=>$term->term_id,'parent'=>$term->parent,'count'=>$term->count,'url'=>get_term_link($term)]:null;
}
$translation=[];
foreach([18073,18065,20541,20567,19048,18636,18491,19066,17803,17875] as $id){
 $translation[]=['id'=>$id,'pub'=>(string)get_post_meta($id,'_en_US_published',true),'en_slug'=>(string)get_post_meta($id,'_en_US_post_name',true)];
}
echo 'EMDO_CLUSTER_EXTRA '.wp_json_encode(['category_terms'=>$termcheck,'translations'=>$translation],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;

$productsByGroup=['cafe'=>[],'aguacate'=>[],'mango'=>[],'citricos'=>[],'chocolate'=>[]];
$all=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>650,'fields'=>'ids','suppress_filters'=>true]);
foreach($all as $pid){
 $p=wc_get_product((int)$pid);if(!$p||!$p->is_visible()||!$p->is_purchasable()||!$p->is_in_stock())continue;
 $terms=wp_get_post_terms($pid,'product_cat',['fields'=>'slugs']);if(is_wp_error($terms))continue;
 $title=remove_accents(mb_strtolower($p->get_name()));$group='';
 if(in_array('cafe-de-especialidad',$terms,true))$group='cafe';
 elseif(in_array('aguacates',$terms,true))$group='aguacate';
 elseif(in_array('chocolates-artesanos',$terms,true) || array_intersect($terms,['tabletas-de-chocolate','bombones-y-chocolatinas','cremas-de-cacao','cacao-para-beber-y-cocinar']))$group='chocolate';
 elseif(array_intersect($terms,['packs-y-lotes','frutas','frutas-tropicales','frutas-del-mundo']) && preg_match('/(?<![a-z])mangos?(?![a-z])/u',$title))$group='mango';
 elseif(array_intersect($terms,['packs-y-lotes','frutas','frutas-tropicales']) && preg_match('/(?<![a-z])(naranjas?|mandarinas?|pomelos?)(?![a-z])/u',$title))$group='citricos';
 if($group==='')continue;
 $long=(string)$p->get_description();$short=(string)$p->get_short_description();
 $wc=static fn($str)=>count(preg_split('/\s+/u',trim(wp_strip_all_tags(strip_shortcodes($str)))))-($str===''?1:0);
 $image_id=(int)$p->get_image_id();
 $alt=$image_id?(string)get_post_meta($image_id,'_wp_attachment_image_alt',true):'';
 $productsByGroup[$group][]=['name'=>$p->get_name(),'id'=>$pid,'words'=>$wc($long),'short'=>$wc($short),
   'image'=>$image_id,'alt_ok'=>trim($alt)!=='','lang_en'=>(string)get_post_meta($pid,'_en_US_published',true)==='1',
   'en_text'=>strlen(trim((string)get_post_meta($pid,'_en_US_post_content',true)))>0,
   'type'=>$p->get_type(),'cat'=>$terms];
}
foreach($productsByGroup as $name=>$items){
 $n=count($items);
 $stats=['group'=>$name,'count'=>$n,
   'empty_long'=>count(array_filter($items,static fn($p)=>$p['words']<15)),
   'short_long'=>count(array_filter($items,static fn($p)=>$p['words']<90)),
   'no_image'=>count(array_filter($items,static fn($p)=>$p['image']<1)),
   'no_alt'=>count(array_filter($items,static fn($p)=>!$p['alt_ok'])),
   'no_native_english'=>count(array_filter($items,static fn($p)=>!$p['lang_en'])),
   'without_english_content'=>count(array_filter($items,static fn($p)=>!$p['en_text'])),
   'examples_short'=>array_slice(array_values(array_filter($items,static fn($p)=>$p['words']<90)),0,5)];
 echo 'EMDO_PRODUCT_QUALITY '.wp_json_encode($stats,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
}
