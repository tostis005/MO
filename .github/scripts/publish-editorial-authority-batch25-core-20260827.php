<?php
/** Core-only publisher for editorial authority batch 25. Run with --skip-plugins --skip-themes. */
if (!defined('ABSPATH')) { exit; }
function emdo_ab25c_words(string $html): int {
    $plain=trim(preg_replace('/\s+/u',' ',wp_strip_all_tags($html)));
    if ($plain==='') return 0;
    preg_match_all("/[\\p{L}\\p{M}]+(?:[’'’-][\\p{L}\\p{M}]+)*/u",$plain,$m);
    return count($m[0]);
}
function emdo_ab25c_category(string $name,string $slug,string $en_name,string $en_slug): int {
    $term=get_term_by('slug',$slug,'category');
    if (!$term instanceof WP_Term) {
        $created=wp_insert_term($name,'category',array('slug'=>$slug));
        if (is_wp_error($created)) throw new RuntimeException($created->get_error_message());
        $term=get_term((int)$created['term_id'],'category');
    }
    if (!$term instanceof WP_Term) throw new RuntimeException('Missing category '.$slug);
    update_term_meta($term->term_id,'_en_US_name',$en_name);
    update_term_meta($term->term_id,'_en_US_slug',sanitize_title($en_slug));
    update_term_meta($term->term_id,'_en_US_published','1');
    return (int)$term->term_id;
}
function emdo_ab25c_existing(string $key,string $slug): int {
    global $wpdb;
    $id=(int)$wpdb->get_var($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_emdo_authority_key' AND m.meta_value=%s WHERE p.post_type='post' ORDER BY p.ID DESC LIMIT 1",$key));
    if ($id>0) return $id;
    return (int)$wpdb->get_var($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='post' AND post_name=%s ORDER BY ID DESC LIMIT 1",sanitize_title($slug)));
}
$data_dir=(string)getenv('EMDO_BATCH25_CORE_DIR');
if ($data_dir==='' || !is_dir($data_dir)) throw new RuntimeException('EMDO_BATCH25_CORE_DIR missing');
$files=glob(trailingslashit($data_dir).'*.php'); sort($files,SORT_NATURAL);
if (count($files)!==5) throw new RuntimeException('Expected 5 article files, found '.count($files));
$guide=emdo_ab25c_category('Guías y consejos','guias-y-consejos','Guides and advice','guides-and-advice');
$cured=emdo_ab25c_category('Embutidos','embutidos','Cured meats','cured-meats');
$meat=emdo_ab25c_category('Carnes','carnes','Meat','meat');
$cats=array('cured_meats'=>$cured,'meat'=>$meat);
$default=(int)get_option('emdo_default_blog_featured_attachment_id',0);
$default_hash=(string)get_option('emdo_default_blog_featured_hash','');
if ($default<=0 || 'attachment'!==get_post_type($default)) throw new RuntimeException('Central provisional image missing');
$author_ids=get_users(array('role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC','fields'=>'ID'));
$author=!empty($author_ids)?(int)$author_ids[0]:1;
$rows=array();
foreach ($files as $file) {
    $a=require $file;
    foreach(array('key','slug','en_slug','topic','title','en_title','excerpt','en_excerpt','content','en_content') as $field){
        if(!isset($a[$field]) || $a[$field]==='') throw new RuntimeException(basename($file).' missing '.$field);
    }
    if(!isset($cats[$a['topic']])) throw new RuntimeException('Unexpected topic '.$a['topic']);
    $content=strtr($a['content'],array('__CURED_MEATS__'=>'embutidos','__MEAT__'=>'carnes'));
    $en_content=strtr($a['en_content'],array('__CURED_MEATS__'=>'embutidos','__MEAT__'=>'carnes'));
    $wes=emdo_ab25c_words($content); $wen=emdo_ab25c_words($en_content);
    if($wes<850 || $wen<750) throw new RuntimeException($a['key'].' too short ES='.$wes.' EN='.$wen);
    $existing=emdo_ab25c_existing($a['key'],$a['slug']);
    $is_new=$existing<=0;
    $post=array(
      'post_type'=>'post','post_status'=>'publish','post_author'=>$author,
      'post_title'=>wp_strip_all_tags($a['title']),'post_name'=>sanitize_title($a['slug']),
      'post_excerpt'=>wp_strip_all_tags($a['excerpt']),'post_content'=>$content,
    );
    if($existing>0){$post['ID']=$existing;$res=wp_update_post($post,true);}else{$res=wp_insert_post($post,true);}
    if(is_wp_error($res)) throw new RuntimeException($a['key'].': '.$res->get_error_message());
    $id=(int)$res;
    wp_set_post_categories($id,array($guide,$cats[$a['topic']]),false);
    update_post_meta($id,'_emdo_authority_key',$a['key']);
    update_post_meta($id,'_emdo_authority_batch','25');
    update_post_meta($id,'_en_US_post_title',wp_strip_all_tags($a['en_title']));
    update_post_meta($id,'_en_US_post_name',sanitize_title($a['en_slug']));
    update_post_meta($id,'_en_US_post_excerpt',wp_strip_all_tags($a['en_excerpt']));
    update_post_meta($id,'_en_US_post_content',$en_content);
    update_post_meta($id,'_en_US_ready','1');
    update_post_meta($id,'_en_US_published','1');
    update_post_meta($id,'_emdo_editorial_updated',gmdate('c'));
    $current=(int)get_post_thumbnail_id($id);
    $currently_default='1'===(string)get_post_meta($id,'_emdo_uses_default_featured',true);
    if($is_new || $currently_default || $current<=0){
      update_post_meta($id,'_thumbnail_id',$default);
      update_post_meta($id,'_emdo_uses_default_featured','1');
      update_post_meta($id,'_emdo_editorial_image_approved_id',$default);
      delete_post_meta($id,'_emdo_editorial_image_approved_pexels_id');
      update_post_meta($id,'_emdo_editorial_cover_asset_id','emdo-blog-default-featured');
      update_post_meta($id,'_emdo_editorial_cover_brand_safe','1');
      update_post_meta($id,'_emdo_default_featured_hash',$default_hash);
      update_post_meta($id,'_emdo_default_featured_updated_at',gmdate('c'));
    }
    clean_post_cache($id);
    $rows[]=array('key'=>$a['key'],'id'=>$id,'slug'=>get_post_field('post_name',$id),'en_slug'=>sanitize_title($a['en_slug']),'status'=>get_post_status($id),'thumbnail_id'=>(int)get_post_thumbnail_id($id),'provisional'=>(string)get_post_meta($id,'_emdo_uses_default_featured',true),'words_es'=>$wes,'words_en'=>$wen,'topic'=>$a['topic']);
}
if(count($rows)!==5) throw new RuntimeException('Publish count mismatch');
echo wp_json_encode(array('batch'=>25,'default'=>$default,'posts'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
