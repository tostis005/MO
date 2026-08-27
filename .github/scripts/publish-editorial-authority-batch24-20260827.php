<?php
/** Publish editorial authority batch 24 with the central provisional featured image. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
$author_ids=get_users(array('role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC','fields'=>'ID'));
$author_id=!empty($author_ids)?(int)$author_ids[0]:1;
function emdo_ab24_category(string $name,string $slug,string $en_name,string $en_slug):int{
 $term=get_term_by('slug',$slug,'category');
 if(!$term instanceof WP_Term){$created=wp_insert_term($name,'category',array('slug'=>$slug));if(is_wp_error($created)){throw new RuntimeException($created->get_error_message());}$term=get_term((int)$created['term_id'],'category');}
 if(!$term instanceof WP_Term){throw new RuntimeException('Missing category '.$slug);}
 update_term_meta($term->term_id,'_en_US_name',$en_name);update_term_meta($term->term_id,'_en_US_slug',sanitize_title($en_slug));update_term_meta($term->term_id,'_en_US_published','1');return (int)$term->term_id;
}
function emdo_ab24_words(string $html):int{$plain=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));if(''===$plain){return 0;}preg_match_all("/[\\p{L}\\p{M}]+(?:[’'’-][\\p{L}\\p{M}]+)*/u",$plain,$m);return count($m[0]);}
function emdo_ab24_post_id(string $key,string $slug):int{$ids=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','pending','future','private'),'posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_authority_key','meta_value'=>$key));if(!empty($ids)){return (int)$ids[0];}$post=get_page_by_path($slug,OBJECT,'post');return $post instanceof WP_Post?(int)$post->ID:0;}
function emdo_ab24_product_slug(string $topic):string{
 $map=array(
  'vegetables'=>array(array('hortalizas-verduras','hortalizas','verduras'),array('Hortalizas/Verduras','Hortalizas y verduras','Hortalizas','Vegetables')),
  'cured_meats'=>array(array('embutidos'),array('Embutidos','Cured meats','Charcuterie')),
 );
 if(!isset($map[$topic])){throw new RuntimeException('Unknown product topic '.$topic);}
 foreach($map[$topic][0] as $slug){$term=get_term_by('slug',$slug,'product_cat');if($term instanceof WP_Term){return $term->slug;}}
 foreach($map[$topic][1] as $name){$term=get_term_by('name',$name,'product_cat');if($term instanceof WP_Term){return $term->slug;}}
 throw new RuntimeException('Product category not found for '.$topic);
}
function emdo_ab24_products(string $html):string{return strtr($html,array('__VEGETABLES__'=>emdo_ab24_product_slug('vegetables'),'__CURED_MEATS__'=>emdo_ab24_product_slug('cured_meats')));}
function emdo_ab24_default_image(int $post_id,bool $is_new):array{
 $default_id=(int)get_option('emdo_default_blog_featured_attachment_id',0);$default_hash=(string)get_option('emdo_default_blog_featured_hash','');
 if($default_id<=0||'attachment'!==get_post_type($default_id)){throw new RuntimeException('Central provisional image is not configured.');}
 if('1'!==(string)get_post_meta($default_id,'_emdo_default_blog_image',true)){throw new RuntimeException('Configured provisional image is not marked as central default.');}
 $current_id=(int)get_post_thumbnail_id($post_id);$currently_default='1'===(string)get_post_meta($post_id,'_emdo_uses_default_featured',true);
 if($is_new||$currently_default||$current_id<=0){update_post_meta($post_id,'_emdo_uses_default_featured','1');update_post_meta($post_id,'_thumbnail_id',$default_id);update_post_meta($post_id,'_emdo_editorial_image_approved_id',$default_id);delete_post_meta($post_id,'_emdo_editorial_image_approved_pexels_id');update_post_meta($post_id,'_emdo_editorial_cover_asset_id','emdo-blog-default-featured');update_post_meta($post_id,'_emdo_editorial_cover_brand_safe','1');update_post_meta($post_id,'_emdo_default_featured_hash',$default_hash);update_post_meta($post_id,'_emdo_default_featured_updated_at',gmdate('c'));clean_post_cache($post_id);}
 $final_id=(int)get_post_thumbnail_id($post_id);$uses_default='1'===(string)get_post_meta($post_id,'_emdo_uses_default_featured',true);if($final_id<=0){throw new RuntimeException('Post '.$post_id.' has no featured image.');}if($uses_default&&$final_id!==$default_id){throw new RuntimeException('Post '.$post_id.' provisional marker/image mismatch.');}return array('image_id'=>$final_id,'uses_default'=>$uses_default,'default_id'=>$default_id,'default_hash'=>$default_hash);
}
$data_dir=(string)getenv('EMDO_BATCH24_DIR');if(''===$data_dir||!is_dir($data_dir)){throw new RuntimeException('EMDO_BATCH24_DIR missing');}
$only=(string)getenv('EMDO_BATCH24_ONLY_FILE');if(''!==$only){$only=basename($only);$target=trailingslashit($data_dir).$only;if(!is_file($target)){throw new RuntimeException('Batch 24 article file not found: '.$only);}$files=array($target);}else{$files=glob(trailingslashit($data_dir).'*.php');sort($files,SORT_NATURAL);if(count($files)!==5){throw new RuntimeException('Expected 5 article files, found '.count($files));}}
$articles=array();foreach($files as $file){$a=require $file;if(!is_array($a)||empty($a['key'])){throw new RuntimeException('Invalid article data '.basename($file));}$articles[]=$a;}
$guide_cat=emdo_ab24_category('Guías y consejos','guias-y-consejos','Guides and advice','guides-and-advice');
$topic_cats=array(
 'vegetables'=>emdo_ab24_category('Hortalizas y verduras','hortalizas-y-verduras','Vegetables','vegetables'),
 'cured_meats'=>emdo_ab24_category('Embutidos','embutidos','Cured meats','cured-meats'),
);
$report=array('batch'=>24,'release'=>'20260827','posts'=>array());
foreach($articles as $a){
 foreach(array('key','slug','en_slug','topic','title','en_title','excerpt','en_excerpt','content','en_content') as $field){if(!isset($a[$field])||''===$a[$field]){throw new RuntimeException(($a['key']??'article').': missing '.$field);}}
 if(!isset($topic_cats[$a['topic']])){throw new RuntimeException($a['key'].': unexpected topic '.$a['topic']);}
 $content=emdo_ab24_products($a['content']);$en_content=emdo_ab24_products($a['en_content']);
 $words_es=emdo_ab24_words($content);$words_en=emdo_ab24_words($en_content);if($words_es<850||$words_en<750){throw new RuntimeException($a['key'].': article too short ES='.$words_es.' EN='.$words_en);}
 $existing_id=emdo_ab24_post_id($a['key'],$a['slug']);$is_new=$existing_id<=0;$data=array('post_type'=>'post','post_status'=>'publish','post_author'=>$author_id,'post_title'=>wp_strip_all_tags($a['title']),'post_name'=>sanitize_title($a['slug']),'post_excerpt'=>wp_strip_all_tags($a['excerpt']),'post_content'=>$content);if(!$is_new){$data['ID']=$existing_id;$result=wp_update_post($data,true);}else{$result=wp_insert_post($data,true);}if(is_wp_error($result)){throw new RuntimeException($a['key'].': '.$result->get_error_message());}
 $post_id=(int)$result;wp_set_post_categories($post_id,array($guide_cat,$topic_cats[$a['topic']]),false);update_post_meta($post_id,'_emdo_authority_key',$a['key']);update_post_meta($post_id,'_emdo_authority_batch','24');update_post_meta($post_id,'_en_US_post_title',wp_strip_all_tags($a['en_title']));update_post_meta($post_id,'_en_US_post_name',sanitize_title($a['en_slug']));update_post_meta($post_id,'_en_US_post_excerpt',wp_strip_all_tags($a['en_excerpt']));update_post_meta($post_id,'_en_US_post_content',$en_content);update_post_meta($post_id,'_en_US_ready','1');update_post_meta($post_id,'_en_US_published','1');update_post_meta($post_id,'_emdo_editorial_updated',gmdate('c'));
 $img=emdo_ab24_default_image($post_id,$is_new);if('publish'!==get_post_status($post_id)){throw new RuntimeException($a['key'].': post is not published');}if('1'!==get_post_meta($post_id,'_en_US_published',true)){throw new RuntimeException($a['key'].': English version not marked published');}
 $report['posts'][]=array('key'=>$a['key'],'id'=>$post_id,'status'=>get_post_status($post_id),'slug'=>(string)get_post_field('post_name',$post_id),'en_slug'=>sanitize_title($a['en_slug']),'words_es'=>$words_es,'words_en'=>$words_en,'image_id'=>$img['image_id'],'uses_default'=>$img['uses_default'],'default_image_id'=>$img['default_id'],'topic'=>$a['topic']);
}
if(count($report['posts'])!==count($articles)){throw new RuntimeException('Publication report count mismatch');}
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
