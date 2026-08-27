<?php
/** Publish editorial authority batch 15 from repository data files. */
if ( ! defined( 'ABSPATH' ) ) { exit; }
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$author_ids=get_users(array('role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC','fields'=>'ID'));
$author_id=!empty($author_ids)?(int)$author_ids[0]:1;

function emdo_ab15_category(string $name,string $slug,string $en_name,string $en_slug):int {
    $term=get_term_by('slug',$slug,'category');
    if(!$term instanceof WP_Term){
        $created=wp_insert_term($name,'category',array('slug'=>$slug));
        if(is_wp_error($created)){throw new RuntimeException($created->get_error_message());}
        $term=get_term((int)$created['term_id'],'category');
    }
    if(!$term instanceof WP_Term){throw new RuntimeException('Missing category '.$slug);}
    update_term_meta($term->term_id,'_en_US_name',$en_name);
    update_term_meta($term->term_id,'_en_US_slug',sanitize_title($en_slug));
    update_term_meta($term->term_id,'_en_US_published','1');
    return (int)$term->term_id;
}

function emdo_ab15_words(string $html):int {
    $plain=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags(strip_shortcodes($html))));
    if(''===$plain){return 0;}
    preg_match_all("/[\\p{L}\\p{M}]+(?:[’'’-][\\p{L}\\p{M}]+)*/u",$plain,$m);
    return count($m[0]);
}

function emdo_ab15_post_id(string $key,string $slug):int {
    $ids=get_posts(array('post_type'=>'post','post_status'=>array('publish','draft','pending','future','private'),'posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_authority_key','meta_value'=>$key));
    if(!empty($ids)){return (int)$ids[0];}
    $post=get_page_by_path($slug,OBJECT,'post');
    return $post instanceof WP_Post?(int)$post->ID:0;
}

function emdo_ab15_download_image(string $url,int $post_id,string $title):int {
    $tmp=download_url($url,90);
    if(is_wp_error($tmp)){throw new RuntimeException('Image download failed: '.$tmp->get_error_message());}
    $path=(string)parse_url($url,PHP_URL_PATH);
    $ext=strtolower(pathinfo(rawurldecode($path),PATHINFO_EXTENSION));
    if(!in_array($ext,array('jpg','jpeg','png','webp'),true)){$ext='jpg';}
    $file=array('name'=>'emdo-batch15-'.$post_id.'-'.time().'.'.$ext,'tmp_name'=>$tmp);
    $id=media_handle_sideload($file,$post_id,$title);
    if(is_wp_error($id)){@unlink($tmp);throw new RuntimeException('Image import failed: '.$id->get_error_message());}
    return (int)$id;
}

function emdo_ab15_image(int $post_id,array $img):int {
    foreach(array('id','direct','page','photographer','license','license_url','alt_es','alt_en') as $field){
        if(empty($img[$field])){throw new RuntimeException('Image missing '.$field.' for post '.$post_id);}
    }
    $source_key=(string)$img['id'];
    $ids=get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_image_source_key','meta_value'=>$source_key));
    $attachment_id=!empty($ids)?(int)$ids[0]:0;
    if($attachment_id<=0){
        $attachment_id=emdo_ab15_download_image((string)$img['direct'],$post_id,(string)$img['alt_es']);
        wp_update_post(array('ID'=>$attachment_id,'post_title'=>$img['alt_es'],'post_excerpt'=>'Fotografía: '.$img['photographer'].'. Fuente: '.$img['page'].'. Licencia: '.$img['license'].'.'));
        update_post_meta($attachment_id,'_emdo_image_source_key',$source_key);
        update_post_meta($attachment_id,'_emdo_image_source_page',$img['page']);
        update_post_meta($attachment_id,'_emdo_image_creator',$img['photographer']);
        update_post_meta($attachment_id,'_emdo_image_license',$img['license']);
        update_post_meta($attachment_id,'_emdo_image_license_url',$img['license_url']);
    }
    update_post_meta($attachment_id,'_wp_attachment_image_alt',$img['alt_es']);
    update_post_meta($attachment_id,'_en_US_attachment_alt',$img['alt_en']);
    if(!set_post_thumbnail($post_id,$attachment_id)){throw new RuntimeException('Could not set featured image for post '.$post_id);}
    return $attachment_id;
}

function emdo_ab15_product_slug(string $topic):string {
    $map=array(
        'hams'=>array(array('jamones-paletas'),array('Jamones y paletas')),
        'cured_meats'=>array(array('embutidos','embutidos-y-curados'),array('Embutidos','Embutidos y curados','Cured meats')),
    );
    if(!isset($map[$topic])){throw new RuntimeException('Unknown product topic '.$topic);}
    foreach($map[$topic][0] as $slug){$term=get_term_by('slug',$slug,'product_cat');if($term instanceof WP_Term){return $term->slug;}}
    foreach($map[$topic][1] as $name){$term=get_term_by('name',$name,'product_cat');if($term instanceof WP_Term){return $term->slug;}}
    throw new RuntimeException('Product category not found for '.$topic);
}

function emdo_ab15_products(string $html):string {
    return strtr($html,array('__HAMS__'=>emdo_ab15_product_slug('hams'),'__CURED_MEATS__'=>emdo_ab15_product_slug('cured_meats')));
}

$data_dir=(string)getenv('EMDO_BATCH15_DIR');
if(''===$data_dir||!is_dir($data_dir)){throw new RuntimeException('EMDO_BATCH15_DIR missing');}
$only=(string)getenv('EMDO_BATCH15_ONLY_FILE');
if(''!==$only){
    $only=basename($only);$target=trailingslashit($data_dir).$only;
    if(!is_file($target)){throw new RuntimeException('Batch 15 article file not found: '.$only);}
    $files=array($target);
}else{
    $files=glob(trailingslashit($data_dir).'*.php');sort($files,SORT_NATURAL);
    if(count($files)!==5){throw new RuntimeException('Expected 5 article files, found '.count($files));}
}

$articles=array();
foreach($files as $file){$a=require $file;if(!is_array($a)||empty($a['key'])){throw new RuntimeException('Invalid article data '.basename($file));}$articles[]=$a;}

$guide_cat=emdo_ab15_category('Guías y consejos','guias-y-consejos','Guides and advice','guides-and-advice');
$topic_cats=array(
    'hams'=>emdo_ab15_category('Jamones y paletas','jamones-y-paletas','Hams and shoulders','hams-and-shoulders'),
    'cured_meats'=>emdo_ab15_category('Embutidos y curados','embutidos-y-curados','Cured meats','cured-meats'),
);
$seen_images=array();$report=array('batch'=>15,'release'=>'20260827','posts'=>array());

foreach($articles as $a){
    foreach(array('key','slug','en_slug','topic','title','en_title','excerpt','en_excerpt','content','en_content','image') as $field){if(!isset($a[$field])||''===$a[$field]){throw new RuntimeException(($a['key']??'article').': missing '.$field);}}
    if(!isset($topic_cats[$a['topic']])){throw new RuntimeException($a['key'].': unexpected topic '.$a['topic']);}
    $image_key=(string)$a['image']['id'];if(isset($seen_images[$image_key])){throw new RuntimeException('Repeated image id '.$image_key);}$seen_images[$image_key]=true;
    $content=emdo_ab15_products($a['content']);$en_content=emdo_ab15_products($a['en_content']);
    $words_es=emdo_ab15_words($content);$words_en=emdo_ab15_words($en_content);
    if($words_es<850||$words_en<750){throw new RuntimeException($a['key'].': article too short ES='.$words_es.' EN='.$words_en);}

    $post_id=emdo_ab15_post_id($a['key'],$a['slug']);
    $data=array('post_type'=>'post','post_status'=>'publish','post_author'=>$author_id,'post_title'=>wp_strip_all_tags($a['title']),'post_name'=>sanitize_title($a['slug']),'post_excerpt'=>wp_strip_all_tags($a['excerpt']),'post_content'=>$content);
    if($post_id>0){$data['ID']=$post_id;$result=wp_update_post($data,true);}else{$result=wp_insert_post($data,true);}
    if(is_wp_error($result)){throw new RuntimeException($a['key'].': '.$result->get_error_message());}
    $post_id=(int)$result;wp_set_post_categories($post_id,array($guide_cat,$topic_cats[$a['topic']]),false);
    update_post_meta($post_id,'_emdo_authority_key',$a['key']);update_post_meta($post_id,'_emdo_authority_batch','15');
    update_post_meta($post_id,'_en_US_post_title',wp_strip_all_tags($a['en_title']));update_post_meta($post_id,'_en_US_post_name',sanitize_title($a['en_slug']));
    update_post_meta($post_id,'_en_US_post_excerpt',wp_strip_all_tags($a['en_excerpt']));update_post_meta($post_id,'_en_US_post_content',$en_content);
    update_post_meta($post_id,'_en_US_ready','1');update_post_meta($post_id,'_en_US_published','1');update_post_meta($post_id,'_emdo_editorial_updated',gmdate('c'));

    $image_id=emdo_ab15_image($post_id,$a['image']);$meta=wp_get_attachment_metadata($image_id);$w=(int)($meta['width']??0);$h=(int)($meta['height']??0);
    if($w<1200||$h<700){throw new RuntimeException($a['key'].': image too small '.$w.'x'.$h);}
    if('publish'!==get_post_status($post_id)){throw new RuntimeException($a['key'].': post is not published');}
    if('1'!==get_post_meta($post_id,'_en_US_published',true)){throw new RuntimeException($a['key'].': English version not marked published');}
    if((int)get_post_thumbnail_id($post_id)!==$image_id){throw new RuntimeException($a['key'].': featured image mismatch');}
    $report['posts'][]=array('key'=>$a['key'],'id'=>$post_id,'status'=>get_post_status($post_id),'slug'=>(string)get_post_field('post_name',$post_id),'en_slug'=>sanitize_title($a['en_slug']),'words_es'=>$words_es,'words_en'=>$words_en,'image_id'=>$image_id,'image_w'=>$w,'image_h'=>$h,'image_source'=>$a['image']['page'],'image_license'=>$a['image']['license'],'topic'=>$a['topic']);
}
if(count($report['posts'])!==count($articles)){throw new RuntimeException('Publication report count mismatch');}
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
