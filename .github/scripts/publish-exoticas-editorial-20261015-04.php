<?php
/**
 * EMDO editorial batch 2026-10-15. BLOG ONLY.
 * Idempotent creation of three bilingual posts and four additive edits.
 * Does not touch WooCommerce, importer, product_cat or template files.
 */
if (!defined('ABSPATH')) { exit(1); }
const EMDO_EXO_RELEASE = 'blog-carnes-exoticas-20261015-04';
$payload_path = getenv('EMDO_EXO_PAYLOAD');
if (!$payload_path || !is_file($payload_path)) { throw new RuntimeException('EMDO_EXO_PAYLOAD missing'); }
$data = json_decode(file_get_contents($payload_path), true, 512, JSON_THROW_ON_ERROR);
if (($data['release'] ?? '') !== EMDO_EXO_RELEASE || count($data['posts'] ?? []) !== 5 || count($data['updates'] ?? []) !== 0) {
    throw new RuntimeException('Unexpected editorial manifest');
}
$slugs=[];
foreach ($data['posts'] as $a) {
    foreach (['slug','en_slug','title','en_title','content','en_content','seo_title','seo_desc','keyword','excerpt','en_excerpt','en_seo_title','en_seo_desc','en_keyword'] as $k) {
        if (!is_string($a[$k] ?? null) || trim($a[$k]) === '') { throw new RuntimeException('Missing post field '.$k); }
    }
    if (isset($slugs[$a['slug']])) { throw new RuntimeException('Duplicate new slug'); }
    $slugs[$a['slug']]=true;
    $existing = get_page_by_path($a['slug'], OBJECT, 'post');
    if ($existing instanceof WP_Post && get_post_meta($existing->ID, '_emdo_exo_batch', true) !== EMDO_EXO_RELEASE) {
        throw new RuntimeException('Existing unrelated post; refusing overwrite: '.$a['slug']);
    }
}
foreach ($data['updates'] as $a) {
    if (!isset($a['slug'],$a['es'],$a['en'],$a['key'])) { throw new RuntimeException('Incomplete update'); }
    $existing = get_page_by_path($a['slug'], OBJECT, 'post');
    if (!$existing instanceof WP_Post || $existing->post_status !== 'publish') {
        throw new RuntimeException('Existing Wagyu post missing/not published: '.$a['slug']);
    }
    if (isset($slugs[$a['slug']])) { throw new RuntimeException('New/existing slug conflict'); }
}
$parent = get_category_by_slug('carnes');
if (!$parent instanceof WP_Term) { throw new RuntimeException('Parent blog category carnes not found'); }
$cat = get_category_by_slug('carnes-exoticas');
$created_term_id=0;
if (!$cat instanceof WP_Term) {
    $inserted = wp_insert_term('Carnes exóticas','category', ['slug'=>'carnes-exoticas','parent'=>(int)$parent->term_id]);
    if (is_wp_error($inserted)) { throw new RuntimeException('Could not add blog category: '.$inserted->get_error_message()); }
    $created_term_id=(int)$inserted['term_id']; $cat=get_term($created_term_id,'category');
}
if (!$cat instanceof WP_Term) { throw new RuntimeException('Blog category unavailable'); }
update_term_meta($cat->term_id,'_en_US_name','Exotic meats');
update_term_meta($cat->term_id,'_en_US_slug','exotic-meats');
update_term_meta($cat->term_id,'_en_US_published','1');
$admins=get_users(['role'=>'administrator','number'=>1,'orderby'=>'ID','order'=>'ASC','fields'=>'ID']);
$author_id= !empty($admins) ? (int)$admins[0] : 1;
$created_ids=[]; $snapshots=[]; $done_new=[]; $done_updated=[];
try {
    foreach ($data['posts'] as $a) {
        $old=get_page_by_path($a['slug'], OBJECT, 'post');
        if ($old instanceof WP_Post) {
            $id=(int)$old->ID;
            $snapshots[$id]=['content'=>$old->post_content,'en_content'=>get_post_meta($id,'_en_US_post_content',true)];
        } else {
            $id=0;
        }
        $arr=['post_type'=>'post','post_status'=>'publish','post_title'=>$a['title'],
              'post_name'=>$a['slug'],'post_excerpt'=>$a['excerpt'],'post_content'=>"<!-- EMDO_EXO_NEW_20261015:{$a['slug']} -->\n".$a['content'],
              'post_author'=>$author_id,'comment_status'=>'closed','ping_status'=>'closed'];
        if ($id) { $arr['ID']=$id; }
        $result=$id ? wp_update_post(wp_slash($arr),true) : wp_insert_post(wp_slash($arr),true);
        if (is_wp_error($result) || (int)$result<1) { throw new RuntimeException('Cannot publish '.$a['slug']); }
        $id=(int)$result;
        if (!$old instanceof WP_Post) { $created_ids[]=$id; }
        wp_set_post_categories($id,[(int)$parent->term_id,(int)$cat->term_id],false);
        update_post_meta($id,'_emdo_exo_batch',EMDO_EXO_RELEASE);
        update_post_meta($id,'_emdo_related_product_category_slug','carnes-exoticas'); // editorial matching only; no product edits
        update_post_meta($id,'_emdo_seo_title',$a['seo_title']);
        update_post_meta($id,'_emdo_seo_description',$a['seo_desc']);
        update_post_meta($id,'_emdo_focus_keyword',$a['keyword']);
        update_post_meta($id,'_en_US_post_title',$a['en_title']);
        update_post_meta($id,'_en_US_post_name',$a['en_slug']);
        update_post_meta($id,'_en_US_post_excerpt',$a['en_excerpt']);
        update_post_meta($id,'_en_US_post_content',"<!-- EMDO_EXO_NEW_20261015:{$a['slug']} -->\n".$a['en_content']);
        update_post_meta($id,'_en_US_seo_title',$a['en_seo_title']);
        update_post_meta($id,'_en_US_seo_description',$a['en_seo_desc']);
        update_post_meta($id,'_en_US_focus_keyword',$a['en_keyword']);
        update_post_meta($id,'_en_US_ready','1');
        update_post_meta($id,'_en_US_published','1');
        if (get_post_status($id)!=='publish' || !has_category((int)$cat->term_id,$id)) {
            throw new RuntimeException('Published post/category verification failed: '.$a['slug']);
        }
        $done_new[]=['id'=>$id,'slug'=>$a['slug'],'en_slug'=>$a['en_slug'],'url'=>get_permalink($id)];
    }
    foreach ($data['updates'] as $a) {
        $post=get_page_by_path($a['slug'],OBJECT,'post');
        $id=(int)$post->ID;
        $marker='<!-- EMDO_EXO_20261015:'.$a['key'].' -->';
        $old_en=(string)get_post_meta($id,'_en_US_post_content',true);
        $snapshots[$id]=['content'=>$post->post_content,'en_content'=>$old_en,
            'seo_title'=>get_post_meta($id,'_emdo_seo_title',true),
            'seo_desc'=>get_post_meta($id,'_emdo_seo_description',true)];
        $es=(string)$post->post_content;
        if (strpos($es,$marker)===false) {
            $block="\n".$marker."\n".$a['es']."\n";
            if (preg_match('/<h2[^>]*>\\s*(Preguntas frecuentes|La conclusión|Conclusión)/iu',$es,$m,PREG_OFFSET_CAPTURE)) {
                $pos=$m[0][1]; $es=substr($es,0,$pos).$block.substr($es,$pos);
            } else { $es.= $block; }
            $result=wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$es]),true);
            if (is_wp_error($result)) { throw new RuntimeException('Update failed '.$a['slug']); }
        }
        if ($old_en!=='') {
            $en=$old_en;
            if (strpos($en,$marker)===false) {
                $block="\n".$marker."\n".$a['en']."\n";
                if (preg_match('/<h2[^>]*>\\s*(Frequently asked questions|Conclusion|The conclusion)/iu',$en,$m,PREG_OFFSET_CAPTURE)) {
                    $pos=$m[0][1]; $en=substr($en,0,$pos).$block.substr($en,$pos);
                } else { $en.= $block; }
                update_post_meta($id,'_en_US_post_content',$en);
            }
        }
        update_post_meta($id,'_emdo_seo_title',$a['seo_title']);
        update_post_meta($id,'_emdo_seo_description',$a['seo_desc']);
        update_post_meta($id,'_emdo_exo_updated',EMDO_EXO_RELEASE);
        if (strpos((string)get_post_field('post_content',$id),$marker)===false) {
            throw new RuntimeException('Edit marker not found '.$a['slug']);
        }
        $done_updated[]=['id'=>$id,'slug'=>$a['slug'],'url'=>get_permalink($id)];
    }
} catch (Throwable $e) {
    foreach (array_reverse($created_ids) as $id) { wp_delete_post($id,true); }
    foreach ($snapshots as $id=>$s) {
        if (in_array($id,$created_ids,true)) { continue; }
        wp_update_post(wp_slash(['ID'=>$id,'post_content'=>$s['content']]),true);
        update_post_meta($id,'_en_US_post_content',$s['en_content']);
        if (array_key_exists('seo_title',$s)) { update_post_meta($id,'_emdo_seo_title',$s['seo_title']); }
        if (array_key_exists('seo_desc',$s)) { update_post_meta($id,'_emdo_seo_description',$s['seo_desc']); }
    }
    if ($created_term_id && !get_objects_in_term($created_term_id,'category')) { wp_delete_term($created_term_id,'category'); }
    wp_cache_flush();
    throw $e;
}
wp_cache_flush();
echo "EMDO_EXO_RELEASE ".wp_json_encode(['verified'=>count($done_new)===5 && count($done_updated)===0,
  'created_or_upserted'=>count($done_new),'existing_updated'=>count($done_updated),
  'blog_category_id'=>$cat->term_id,'blog_category_slug'=>$cat->slug,
  'new_posts'=>$done_new,'updated_posts'=>$done_updated],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
