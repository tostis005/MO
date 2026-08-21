<?php
/** Publish editorial authority batch 7 from repository data files. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$author_ids = get_users( array('role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC','fields'=>'ID') );
$author_id = ! empty($author_ids) ? (int)$author_ids[0] : 1;

function emdo_ab7_category(string $name,string $slug,string $en_name,string $en_slug):int{
    $term=get_term_by('slug',$slug,'category');
    if(!$term instanceof WP_Term){$created=wp_insert_term($name,'category',array('slug'=>$slug));if(is_wp_error($created)){throw new RuntimeException($created->get_error_message());}$term=get_term((int)$created['term_id'],'category');}
    if(!$term instanceof WP_Term){throw new RuntimeException('Missing category '.$slug);} update_term_meta($term->term_id,'_en_US_name',$en_name);update_term_meta($term->term_id,'_en_US_slug',sanitize_title($en_slug));update_term_meta($term->term_id,'_en_US_published','1');return (int)$term->term_id;
}
function emdo_ab7_words(string $html):int{$plain=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));if(''===$plain){return 0;}preg_match_all("/[\\p{L}\\p{M}]+(?:[’'’-][\\p{L}\\p{M}]+)*/u",$plain,$m);return count($m[0]);}
function emdo_ab7_post_id(string $key,string $slug):int{$ids=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','pending','future','private'),'posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_authority_key','meta_value'=>$key));if(!empty($ids)){return (int)$ids[0];}$post=get_page_by_path($slug,OBJECT,'post');return $post instanceof WP_Post?(int)$post->ID:0;}
function emdo_ab7_image(int $post_id,array $img):int{
    $ids=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_pexels_photo_id','meta_value'=>(string)$img['id']));$attachment_id=!empty($ids)?(int)$ids[0]:0;
    if($attachment_id<=0){$attachment_id=media_sideload_image($img['direct'],$post_id,$img['alt_es'],'id');if(is_wp_error($attachment_id)){throw new RuntimeException('Image '.$img['id'].': '.$attachment_id->get_error_message());}$attachment_id=(int)$attachment_id;wp_update_post(array('ID'=>$attachment_id,'post_title'=>$img['alt_es'],'post_excerpt'=>'Fotografía: '.$img['photographer'].' · Pexels.'));update_post_meta($attachment_id,'_emdo_pexels_photo_id',(string)$img['id']);update_post_meta($attachment_id,'_emdo_pexels_page',$img['page']);update_post_meta($attachment_id,'_emdo_pexels_photographer',$img['photographer']);update_post_meta($attachment_id,'_emdo_image_license','Pexels License - free personal and commercial use');update_post_meta($attachment_id,'_emdo_image_license_url','https://help.pexels.com/hc/en-us/articles/360042295174-What-is-the-license-of-the-photos-and-videos-on-Pexels');}
    update_post_meta($attachment_id,'_wp_attachment_image_alt',$img['alt_es']);update_post_meta($attachment_id,'_en_US_attachment_alt',$img['alt_en']??$img['alt_es']);set_post_thumbnail($post_id,$attachment_id);return $attachment_id;
}
function emdo_ab7_product_slug(string $type):string{
    $candidates=array('oil'=>array('aceites'),'meat'=>array('carnes','carne'),'vegetables'=>array('hortalizas-verduras','hortalizas','verduras'),'pulses'=>array('legumbres'));
    foreach($candidates[$type]??array() as $slug){$term=get_term_by('slug',$slug,'product_cat');if($term instanceof WP_Term){return $term->slug;}}
    $names=array('oil'=>array('Aceites'),'meat'=>array('Carnes'),'vegetables'=>array('Hortalizas/Verduras','Hortalizas y verduras','Hortalizas','Verduras'),'pulses'=>array('Legumbres'));
    foreach($names[$type]??array() as $name){$term=get_term_by('name',$name,'product_cat');if($term instanceof WP_Term){return $term->slug;}}
    throw new RuntimeException('Product category not found for '.$type);
}
function emdo_ab7_products(string $html):string{$map=array('__OIL__'=>emdo_ab7_product_slug('oil'),'__MEAT__'=>emdo_ab7_product_slug('meat'),'__VEGETABLES__'=>emdo_ab7_product_slug('vegetables'),'__PULSES__'=>emdo_ab7_product_slug('pulses'));return str_replace(array_keys($map),array_values($map),$html);}

$data_dir=(string)getenv('EMDO_BATCH7_DIR');if(''===$data_dir||!is_dir($data_dir)){throw new RuntimeException('EMDO_BATCH7_DIR missing');}$files=glob(trailingslashit($data_dir).'*.php');sort($files,SORT_NATURAL);if(count($files)!==5){throw new RuntimeException('Expected 5 article files, found '.count($files));}
$articles=array();foreach($files as $file){$a=require $file;if(!is_array($a)||empty($a['key'])){throw new RuntimeException('Invalid article data '.basename($file));}$articles[]=$a;}
$guide_cat=emdo_ab7_category('Guías de compra','guias-de-compra','Buying guides','buying-guides');
$cats=array('oil'=>emdo_ab7_category('Aceites','aceites','Olive oil','olive-oil'),'meat'=>emdo_ab7_category('Carnes','carnes','Beef and meat','beef-and-meat'),'vegetables'=>emdo_ab7_category('Hortalizas y verduras','hortalizas-y-verduras','Vegetables','vegetables'),'pulses'=>emdo_ab7_category('Legumbres','legumbres','Pulses','pulses'));
$seen_images=array();$report=array('batch'=>7,'release'=>'20260821','posts'=>array());
foreach($articles as $a){
    foreach(array('key','slug','en_slug','topic','title','en_title','excerpt','en_excerpt','content','en_content','image') as $field){if(!isset($a[$field])||''===$a[$field]){throw new RuntimeException($a['key'].': missing '.$field);}}
    if(!isset($cats[$a['topic']])){throw new RuntimeException($a['key'].': unknown topic');}$image_key=(string)$a['image']['id'];if(isset($seen_images[$image_key])){throw new RuntimeException('Repeated image id '.$image_key);}$seen_images[$image_key]=true;
    $content=emdo_ab7_products($a['content']);$en_content=emdo_ab7_products($a['en_content']);$post_id=emdo_ab7_post_id($a['key'],$a['slug']);
    $data=array('post_type'=>'post','post_status'=>'publish','post_author'=>$author_id,'post_title'=>wp_strip_all_tags($a['title']),'post_name'=>sanitize_title($a['slug']),'post_excerpt'=>wp_strip_all_tags($a['excerpt']),'post_content'=>$content);
    if($post_id>0){$data['ID']=$post_id;$result=wp_update_post($data,true);}else{$result=wp_insert_post($data,true);}if(is_wp_error($result)){throw new RuntimeException($a['key'].': '.$result->get_error_message());}$post_id=(int)$result;
    wp_set_post_categories($post_id,array($guide_cat,$cats[$a['topic']]),false);update_post_meta($post_id,'_emdo_authority_key',$a['key']);update_post_meta($post_id,'_emdo_authority_batch','7');update_post_meta($post_id,'_en_US_post_title',wp_strip_all_tags($a['en_title']));update_post_meta($post_id,'_en_US_post_name',sanitize_title($a['en_slug']));update_post_meta($post_id,'_en_US_post_excerpt',wp_strip_all_tags($a['en_excerpt']));update_post_meta($post_id,'_en_US_post_content',$en_content);update_post_meta($post_id,'_en_US_ready','1');update_post_meta($post_id,'_en_US_published','1');update_post_meta($post_id,'_emdo_editorial_updated',gmdate('c'));
    $image_id=emdo_ab7_image($post_id,$a['image']);$meta=wp_get_attachment_metadata($image_id);$w=(int)($meta['width']??0);$h=(int)($meta['height']??0);if($w<1200||$h<700){throw new RuntimeException($a['key'].': image too small '.$w.'x'.$h);}$words_es=emdo_ab7_words($content);$words_en=emdo_ab7_words($en_content);if($words_es<850||$words_en<750){throw new RuntimeException($a['key'].': article too short ES='.$words_es.' EN='.$words_en);}
    $report['posts'][]=array('key'=>$a['key'],'id'=>$post_id,'status'=>get_post_status($post_id),'slug'=>(string)get_post_field('post_name',$post_id),'en_slug'=>sanitize_title($a['en_slug']),'words_es'=>$words_es,'words_en'=>$words_en,'image_id'=>$image_id,'image_w'=>$w,'image_h'=>$h,'image_source'=>$a['image']['page'],'topic'=>$a['topic'],'product_cat'=>emdo_ab7_product_slug($a['topic']));
}
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
// Batch 7 workflow trigger marker.
