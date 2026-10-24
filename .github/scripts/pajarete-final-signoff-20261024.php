<?php
/**
 * Final non-mutating sign-off for published Pajarete product catalog and SEO.
 */
if(!defined('ABSPATH')||!class_exists('MDO_Database')||!class_exists('WooCommerce'))throw new RuntimeException('Environment');
$h=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if(!in_array($h,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Production only');
global $wpdb;
$sup=MDO_Database::table('suppliers');$t=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974)throw new RuntimeException('Supplier mismatch');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,status,source_url,source_payload,wc_product_id,last_error FROM {$t} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
$seo=$wpdb->prefix.'aioseo_posts';
$summary=['source_total'=>count($rows),'published_approved'=>0,'excluded_hidden'=>0,
    'photo_review_hidden'=>0,'pending_unimported'=>0,'published_without_cover'=>0,
    'published_without_alt'=>0,'published_without_long'=>0,
    'published_without_short'=>0,'published_uncategorized'=>0,
    'published_without_meta_title'=>0,'published_without_meta_description'=>0,
    'published_with_incomplete_gallery'=>0,'published_variation_problems'=>0,
    'published_image_attachments'=>0,'published_unique_image_attachments'=>0,
    'published_long_source_normalized_match'=>0,
    'published_unexpected'=>0,'draft_other'=>0];
$seo_titles=[];$seo_descs=[];$pending=[];$problems=[];$seen_media=[];
$norm=static function(string $html):string{
    $v=html_entity_decode(wp_strip_all_tags($html),ENT_QUOTES|ENT_HTML5,'UTF-8');
    $v=preg_replace('/[\x{00A0}\x{202F}\x{200B}]/u',' ',$v);
    return trim((string)preg_replace('/\s+/u',' ',$v));
};
foreach($rows as $r){
    $id=(int)$r['wc_product_id'];
    $origin=json_decode((string)$r['source_payload'],true);$origin=is_array($origin)?$origin:[];
    if(!$id){
        $summary['pending_unimported']++;
        $pending[]=['source_id'=>(int)$r['id'],'title'=>$r['title'],'state'=>$r['status'],
           'source_url'=>$r['source_url'],'last_error'=>mb_substr((string)$r['last_error'],0,380),
           'has_payload'=>(bool)$origin,'price'=>$origin['price']??null,
           'variation_count'=>count((array)($origin['variations']??[]))];
        continue;
    }
    $p=wc_get_product($id);
    if(!$p){$problems[]=['id'=>$id,'error'=>'missing_wc'];continue;}
    if((int)get_post_field('post_author',$id)!==4974){$problems[]=['id'=>$id,'error'=>'wrong_vendor'];continue;}
    if($r['status']==='excluded'){
        if($p->get_status()==='publish'||$p->get_catalog_visibility()!=='hidden')
            $problems[]=['id'=>$id,'error'=>'excluded_visible'];
        else $summary['excluded_hidden']++;
        continue;
    }
    if((int)$id===21483){
        if($p->get_status()!=='draft'||$p->get_catalog_visibility()!=='hidden'||
          get_post_meta($id,'_mdo_pajarete_photo_validation_required',true)!=='1')
            $problems[]=['id'=>$id,'error'=>'suspicious_wine_photo_exposed'];
        else $summary['photo_review_hidden']++;
        continue;
    }
    if($r['status']!=='active'||$p->get_status()!=='publish'||$p->get_catalog_visibility()!=='visible'){
        $summary['draft_other']++;$problems[]=['id'=>$id,'error'=>'unexpected_status'];continue;
    }
    $summary['published_approved']++;
    $data=$wpdb->get_row($wpdb->prepare("SELECT title,description,robots_noindex FROM {$seo} WHERE post_id=%d LIMIT 1",$id),ARRAY_A);
    if(!$data||!trim((string)$data['title'])){$summary['published_without_meta_title']++;$problems[]=['id'=>$id,'error'=>'missing_meta_title'];}
    if(!$data||!trim((string)$data['description'])){$summary['published_without_meta_description']++;$problems[]=['id'=>$id,'error'=>'missing_meta_desc'];}
    if($data){$seo_titles[$data['title']][]=$id;$seo_descs[$data['description']][]=$id;}
    if($data && (int)$data['robots_noindex']!==0)$problems[]=['id'=>$id,'error'=>'noindex'];
    if(!$norm($p->get_short_description())){$summary['published_without_short']++;$problems[]=['id'=>$id,'error'=>'short_empty'];}
    if(!$norm($p->get_description())){$summary['published_without_long']++;$problems[]=['id'=>$id,'error'=>'long_empty'];}
    if($norm($p->get_description())===$norm((string)($origin['description']??'')))$summary['published_long_source_normalized_match']++;
    else $problems[]=['id'=>$id,'error'=>'long_differs_source'];
    $terms=wp_get_post_terms($id,'product_cat',['fields'=>'slugs']);
    if(is_wp_error($terms)||!$terms||in_array('sin-categorizar',(array)$terms,true)){
        $summary['published_uncategorized']++;$problems[]=['id'=>$id,'error'=>'category'];
    }
    if($p->is_type('variable')){
        if(!$p->get_children())$summary['published_variation_problems']++;
        foreach($p->get_children() as $cid){
           $v=wc_get_product($cid);
           if(!$v||!is_numeric($v->get_regular_price())||(float)$v->get_regular_price()<=0)
             $summary['published_variation_problems']++;
        }
    }
    $image=(int)$p->get_image_id();
    if(!$image){$summary['published_without_cover']++;$problems[]=['id'=>$id,'error'=>'missing_cover'];}
    $all=array_values(array_unique(array_filter(array_map('intval',array_merge([$image],$p->get_gallery_image_ids())))));
    $map=json_decode((string)get_post_meta($id,'_emdo_image_map',true),true);
    $map=is_array($map)?$map:[];
    $urls=array_values(array_unique(array_filter((array)($origin['images']??[]),'is_string')));
    foreach($urls as $url){
        $imgid=(int)($map[$url]??0);
        if(!$imgid||!in_array($imgid,$all,true)||!get_attached_file($imgid)||!is_file(get_attached_file($imgid))){
            $summary['published_with_incomplete_gallery']++;$problems[]=['id'=>$id,'error'=>'gallery_source_mismatch'];break;
        }
    }
    foreach($all as $aid){
        $summary['published_image_attachments']++;
        $seen_media[$aid]=true;
        if(!trim((string)get_post_meta($aid,'_wp_attachment_image_alt',true))){
            $summary['published_without_alt']++;$problems[]=['id'=>$id,'error'=>'missing_alt_'.$aid];
        }
    }
}
$summary['published_unique_image_attachments']=count($seen_media);
$duplicate_title=array_filter($seo_titles,static fn($ids)=>count($ids)>1 && trim((string)array_search($ids,$seo_titles,true))!=='');
$duplicate_desc=array_filter($seo_descs,static fn($ids)=>count($ids)>1 && trim((string)array_search($ids,$seo_descs,true))!=='');
$display_dupes=static function(array $records):array{
  $out=[];foreach($records as $text=>$ids)if(count($ids)>1&&$text!=='')$out[]=['ids'=>$ids,'value'=>$text];
  return array_slice($out,0,15);
};
echo 'PAJARETE_SIGNOFF='.wp_json_encode([
  'counts'=>$summary,'duplicate_seo_titles'=>$display_dupes($seo_titles),
  'duplicate_meta_descriptions'=>$display_dupes($seo_descs),
  'pending'=>$pending,'other_problems'=>array_slice($problems,0,40)
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
