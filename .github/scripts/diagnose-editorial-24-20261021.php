<?php
if (!defined('ABSPATH')) { exit(2); }
$target=get_page_by_path('cafe-arabica-robusta-diferencias-cual-elegir', OBJECT,'post');
$ids=[1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24];
$posts=get_posts(['post_type'=>'post','post_status'=>'publish','posts_per_page'=>100,'meta_query'=>[['key'=>'_emdo_newprod_plan_id','compare'=>'EXISTS']],'suppress_filters'=>true]);
$brief=[];
foreach($posts as $p) {
 $id=(int)get_post_meta($p->ID,'_emdo_newprod_plan_id',true);
 if(!in_array($id,$ids,true)) continue;
 $brief[]=['plan'=>$id,'post_id'=>$p->ID,'slug'=>$p->post_name,'image'=>(int)get_post_thumbnail_id($p->ID),'default'=>(string)get_post_meta($p->ID,'_emdo_uses_default_featured',true),'shop'=>(string)get_post_meta($p->ID,'_emdo_newprod_product_category_url',true),'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),'seo_description'=>(string)get_post_meta($p->ID,'_emdo_seo_description',true)];
}
usort($brief,static fn($a,$b)=>$a['plan']<=>$b['plan']);
$root=WP_CONTENT_DIR.'/mu-plugins';
$runtime=$root.'/mdo-english-island-runtime-seo.php';
$bridges=[];
foreach(glob($root.'/*.php') ?: [] as $p){
 $name=basename($p);if(str_contains($name,'english')||str_contains($name,'seo')||str_contains($name,'lang'))$bridges[]=$name;
}
$terms=[];
foreach(['cafe','cafes','cafe-en-grano','cafe-de-especialidad','mango','mangos','naranja','naranjas','citricos','frutas','aguacates'] as $slug){
 $t=get_term_by('slug',$slug,'product_cat');if(!($t instanceof WP_Term))continue;
 $products=wc_get_products(['status'=>'publish','category'=>[$slug],'limit'=>50,'stock_status'=>'instock']);
 $sample=[];
 foreach($products as $p)if($p->is_visible()&&$p->is_purchasable()&&$p->is_in_stock())$sample[]=['id'=>$p->get_id(),'name'=>$p->get_name(),'img'=>$p->get_image_id()];
 $terms[$slug]=['id'=>$t->term_id,'name'=>$t->name,'url'=>get_term_link($t),'count'=>$t->count,'in_stock_visible'=>count($sample),'sample'=>array_slice($sample,0,4)];
}
$plugins=get_option('active_plugins',[]);
echo 'EMDO_REPAIR_DIAG '.wp_json_encode(['wp'=>home_url(),'runtime_exists'=>is_file($runtime),'runtime_size'=>is_file($runtime)?filesize($runtime):0,'runtime_has_fix'=>is_file($runtime)&&str_contains((string)file_get_contents($runtime),'function mdo_island_native_spanish_url'),'mu_bridge_files'=>$bridges,'active_seo_plugins'=>array_values(array_filter($plugins,static fn($x)=>preg_match('/seo|press|rank|translate/i',$x))),'target_post_id'=>$target instanceof WP_Post?$target->ID:0,'posts'=>$brief,'categories'=>$terms],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;


$media=[];
foreach(['cafe','coffee','Ikore','mango','naranja','mandarina','citric'] as $needle){
 $img=get_posts(['post_type'=>'attachment','post_status'=>'inherit','post_mime_type'=>'image','posts_per_page'=>10,'s'=>$needle,'suppress_filters'=>true]);
 $media[$needle]=array_map(static fn($q)=>['id'=>$q->ID,'title'=>$q->post_title,'url'=>wp_get_attachment_url($q->ID),'width'=>(int)(wp_get_attachment_metadata($q->ID)['width']??0)],$img);
}
$matches=[];
foreach(['mango','naranja','mandarina','cafe'] as $needle){
 $prods=get_posts(['post_type'=>'product','post_status'=>'publish','posts_per_page'=>25,'s'=>$needle,'suppress_filters'=>true]);
 $matches[$needle]=array_map(static function($q){$p=wc_get_product($q->ID);return ['id'=>$q->ID,'name'=>$q->post_title,'stock'=>$p&&$p->is_in_stock(),'buyable'=>$p&&$p->is_purchasable(),'image'=>$p?$p->get_image_id():0];},$prods);
}
echo 'EMDO_MEDIA_DIAG '.wp_json_encode(['media'=>$media,'matches'=>$matches],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
