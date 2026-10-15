<?php
/**
 * Publish three distinctly authored bilingual evergreen buying guides.
 * Stop short of publication if a suitable existing, non-placeholder image cannot
 * be found for every article. Never create a generic SEO article or category.
 */
if (!defined('ABSPATH') || !function_exists('wp_insert_post')) throw new RuntimeException('WordPress unavailable');
$folder = getenv('MDO_NEW_GUIDES_DIR');
if (!is_string($folder)||!is_dir($folder)) throw new RuntimeException('Guides payload directory unavailable');
$files = glob(rtrim($folder,'/').'/[0-9][0-9]-*.php');
sort($files);
if(count($files)!==3) throw new RuntimeException('Expected exactly three hand-authored article payloads');
$required=array('key','slug','en_slug','title','en_title','excerpt','en_excerpt','seo_title','en_seo_title','meta_description','en_meta_description','category_hint','image_terms','content','en_content');
$articles=array();
foreach($files as $file){
  $a=include $file;
  if(!is_array($a))throw new RuntimeException('Invalid payload '.basename($file));
  foreach($required as $field) if(!isset($a[$field]) || (is_string($a[$field]) && trim($a[$field])===''))throw new RuntimeException('Missing '.$field);
  foreach(array('content','en_content') as $field){
    $plain=trim(wp_strip_all_tags($a[$field]));preg_match_all('/[\p{L}\p{M}]+/u',$plain,$words);
    if(count($words[0])<470)throw new RuntimeException('Editorial payload unexpectedly short '.$a['key'].' '.$field.' '.count($words[0]));
    if(substr_count($a[$field],'<h2>')<4)throw new RuntimeException('Unstructured payload '.$a['key'].' '.$field);
  }
  $existing=get_page_by_path($a['slug'],OBJECT,'post');
  if($existing instanceof WP_Post && (string)get_post_meta($existing->ID,'_mdo_commercial_guide_20261015',true)!==$a['key']){
    throw new RuntimeException('Conflicting established article at slug '.$a['slug']);
  }
  $articles[]=$a;
}
if(count(array_unique(array_column($articles,'slug')))!==3 || count(array_unique(array_column($articles,'en_slug')))!==3) throw new RuntimeException('Duplicate slugs in batch');

// Use existing, relevant editorial categories; do NOT add new taxonomies.
$veg=get_term_by('slug','hortalizas-y-verduras','category');
$meat=get_term_by('slug','carnes','category');
$oil=get_term_by('slug','aceites','category');
if(!$veg instanceof WP_Term||!$meat instanceof WP_Term||!$oil instanceof WP_Term)throw new RuntimeException('Required existing editorial categories missing');
$general=null;
foreach(array('guias-y-consejos','guias-de-compra','consejos','alimentacion','compra-online') as $slug){
  $t=get_term_by('slug',$slug,'category');
  if($t instanceof WP_Term){$general=$t;break;}
}
$categories=array(
  'hortalizas-y-verduras'=>array((int)$veg->term_id),
  'general'=>$general instanceof WP_Term
    ? array((int)$general->term_id)
    : array((int)$veg->term_id,(int)$meat->term_id,(int)$oil->term_id)
);

// Do not choose an unrelated placeholder; use site-owned existing library media.
$attachments=get_posts(array(
 'post_type'=>'attachment','post_status'=>'inherit','post_mime_type'=>'image',
 'posts_per_page'=>1100,'orderby'=>'ID','order'=>'DESC'
));
$used=array();$choices=array();
foreach($articles as $a){
  $best_id=0;$best_score=-1;
  foreach($attachments as $media){
    $id=(int)$media->ID;
    if(in_array($id,$used,true))continue;
    $alt=trim((string)get_post_meta($id,'_wp_attachment_image_alt',true));
    $hay=mb_strtolower($alt.' '.$media->post_title.' '.$media->post_name,'UTF-8');
    if($alt===''||preg_match('/provisional|placeholder|el mercado de origen|default|sample|logo|sin imagen/i',$hay))continue;
    $dims=wp_get_attachment_metadata($id);
    if(!is_array($dims)||($dims['width']??0)<900||($dims['height']??0)<450)continue;
    $score=0;
    foreach($a['image_terms'] as $term)if(mb_stripos($hay,$term,0,'UTF-8')!==false)$score+=2;
    if($score>$best_score&&$score>=2){$best_id=$id;$best_score=$score;}
  }
  $choices[$a['key']]=$best_id;
  if($best_id>0)$used[]=$best_id;
}
$all_have_images=count(array_filter($choices))===3;
$publication=$all_have_images?'publish':'draft';
$out=array('batch'=>'20261015-commercial-evergreen-new-3','status'=>$publication,'images'=>$choices,'general_category'=>$general instanceof WP_Term?$general->slug:'multiple-existing-clusters','results'=>array());
foreach($articles as $a){
  $p=get_page_by_path($a['slug'],OBJECT,'post');
  $is_ours=$p instanceof WP_Post && (string)get_post_meta($p->ID,'_mdo_commercial_guide_20261015',true)===$a['key'];
  if($p instanceof WP_Post && $p->post_status==='publish' && $is_ours) {
    $out['results'][]=array('slug'=>$a['slug'],'id'=>$p->ID,'existing'=>true,'url'=>get_permalink($p->ID),'status'=>$p->post_status);
    continue;
  }
  $params=array(
    'post_type'=>'post','post_status'=>$publication,
    'post_title'=>$a['title'],'post_name'=>$a['slug'],
    'post_excerpt'=>$a['excerpt'],'post_content'=>$a['content'],
    'post_category'=>$categories[$a['category_hint']],
    'comment_status'=>'closed','ping_status'=>'closed',
  );
  if($p instanceof WP_Post && $is_ours) $params['ID']=$p->ID;
  $id=wp_insert_post(wp_slash($params),true);
  if(is_wp_error($id)||!$id)throw new RuntimeException('Could not save '.$a['key'].': '.(is_wp_error($id)?$id->get_error_message():'unknown'));
  update_post_meta($id,'_mdo_commercial_guide_20261015',$a['key']);
  update_post_meta($id,'_en_US_post_title',$a['en_title']);
  update_post_meta($id,'_en_US_post_name',$a['en_slug']);
  update_post_meta($id,'_en_US_post_excerpt',$a['en_excerpt']);
  update_post_meta($id,'_en_US_post_content',$a['en_content']);
  update_post_meta($id,'_en_US_seo_title',$a['en_seo_title']);
  update_post_meta($id,'_en_US_meta_description',$a['en_meta_description']);
  update_post_meta($id,'_en_US_ready','1');
  update_post_meta($id,'_en_US_published',$publication==='publish'?'1':'0');
  $image_id=(int)$choices[$a['key']];
  if($image_id>0)set_post_thumbnail($id,$image_id);
  clean_post_cache($id);
  $out['results'][]=array('id'=>(int)$id,'slug'=>$a['slug'],'en_slug'=>$a['en_slug'],'status'=>get_post_status($id),'image_id'=>$image_id,'url'=>get_permalink($id));
}
if(count($out['results'])!==3)throw new RuntimeException('Unexpected output count');
echo "MDO_NEW_EVERGREEN_RESULT ".wp_json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
