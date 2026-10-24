<?php
/**
 * Read-only scoped Pajarete QA: compare live Store API to all source-linked
 * Woo products, including image galleries, texts, attributes and SEO metadata.
 * No mutations and no source records outside supplier 9.
 */
if (!defined('ABSPATH') || !class_exists('MDO_Database') || !class_exists('WooCommerce')) throw new RuntimeException('WooCommerce/EMDO needed');
$hostname=strtolower((string)wp_parse_url((string)get_option('siteurl'),PHP_URL_HOST));
if (!in_array($hostname,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true))throw new RuntimeException('Wrong production site');
global $wpdb;
$sup_table=MDO_Database::table('suppliers');$src_table=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$sup_table} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if(!$s||(int)$s['vendor_user_id']!==4974)throw new RuntimeException('Supplier identity mismatch');
$rows=$wpdb->get_results($wpdb->prepare("SELECT id,title,source_url,source_payload,wc_product_id,status,last_error FROM {$src_table} WHERE supplier_id=%d ORDER BY id",(int)$s['id']),ARRAY_A);
$api=[];$api_counts=[];$api_errors=[];
for($page=1;$page<=5;$page++){
  $res=wp_remote_get('https://quesospajarete.com/wp-json/wc/store/v1/products?per_page=100&page='.$page,
    ['timeout'=>40,'redirection'=>2,'headers'=>['Accept'=>'application/json'],'user-agent'=>'Mozilla/5.0 (compatible; EMDO-Source-QA/1.0)']);
  if(is_wp_error($res)||200!==(int)wp_remote_retrieve_response_code($res)){
    $api_errors[]=['page'=>$page,'error'=>is_wp_error($res)?$res->get_error_message():wp_remote_retrieve_response_code($res)];
    break;
  }
  $arr=json_decode((string)wp_remote_retrieve_body($res),true);
  if(!is_array($arr)){ $api_errors[]=['page'=>$page,'error'=>'invalid_json'];break; }
  $api_counts[]=['page'=>$page,'count'=>count($arr),'total'=>wp_remote_retrieve_header($res,'x-wp-total'),
    'pages'=>wp_remote_retrieve_header($res,'x-wp-totalpages')];
  foreach($arr as $item){
    if(!is_array($item)||empty($item['permalink']))continue;
    $h=strtolower((string)wp_parse_url((string)$item['permalink'],PHP_URL_HOST));
    $path=trim((string)wp_parse_url((string)$item['permalink'],PHP_URL_PATH),'/');
    if(in_array($h,['quesospajarete.com','www.quesospajarete.com'],true))$api[$path]=$item;
  }
  if(count($arr)<100)break;
}
$aioseo_table=$wpdb->prefix.'aioseo_posts';
$aioseo_exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$aioseo_table))===$aioseo_table;
$aioseo_fields=$aioseo_exists?$wpdb->get_col("SHOW COLUMNS FROM {$aioseo_table}",0):[];
$aioseo_rows=$aioseo_exists?$wpdb->get_results("SELECT post_id,title,description,canonical_url,robots_noindex,robots_nofollow FROM {$aioseo_table} WHERE post_id IN (21309,21319,21300) LIMIT 3",ARRAY_A):[];
$aioseo_count=$aioseo_exists?(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$aioseo_table} WHERE post_id IN (SELECT wc_product_id FROM {$src_table} WHERE supplier_id=%d AND wc_product_id>0)",(int)$s['id'])):0;
$imgs_unique=0;$imgs_duplicated=0;
foreach($api as $entry){$images=array_filter(array_map(static fn($img)=>is_array($img)?(string)($img['src']??''):'',(array)($entry['images']??[])));$imgs_unique+=count(array_unique($images));$imgs_duplicated+=count($images)-count(array_unique($images));}
$active_plugins=(array)get_option('active_plugins',[]);
$seo_plugin=array_values(array_filter($active_plugins,static fn($p)=>str_contains($p,'wordpress-seo')||str_contains($p,'rank-math')||str_contains($p,'aioseo')||str_contains($p,'seo')));
$summary=['source_rows'=>count($rows),'linked'=>0,'published'=>0,'draft'=>0,'excluded'=>0,'pending'=>0,
 'api_matched'=>0,'api_missing'=>0,'api_expected_images'=>0,'local_images'=>0,
 'missing_cover'=>0,'invalid_cover'=>0,'missing_gallery_images'=>0,'gallery_incomplete'=>0,
 'image_meta_alt_blank'=>0,'image_meta_alt_generic'=>0,'gallery_verified_source'=>0,
 'long_empty'=>0,'short_empty'=>0,'long_differs_api'=>0,'short_differs_api'=>0,
 'no_source_image_map'=>0,'category_uncategorized'=>0,'category_missing'=>0,
 'title_length_over_70'=>0,'short_under_50'=>0,'duplicate_short_count'=>0,
 'bad_price'=>0,'variable_missing_children'=>0,'bad_variation_price'=>0,
 'seo_title_blank'=>0,'seo_desc_blank'=>0,'seo_canonical_blank'=>0,
 'excluded_published'=>0,'invalid_status'=>0,
 'source_images_over12'=>0,'hero_image_origin_mismatch'=>0];
$reports=[];$short_hashes=[];$duplicate_titles=[];
foreach($rows as $r){
    $pid=(int)$r['wc_product_id'];$status=(string)$r['status'];$path=trim((string)wp_parse_url((string)$r['source_url'],PHP_URL_PATH),'/');
    $origin=$api[$path]??null;
    if($status==='excluded')$summary['excluded']++;
    if(!$pid){$summary['pending']++;continue;}
    $p=wc_get_product($pid);
    if(!$p){$reports[]=['id'=>$pid,'error'=>'missing_product'];continue;}
    $summary['linked']++;$summary[$p->get_status()==='publish'?'published':($p->get_status()==='draft'?'draft':'invalid_status')]++;
    $title=$p->get_name();$warnings=[];$src=json_decode((string)$r['source_payload'],true);$src=is_array($src)?$src:[];
    if($status==='excluded' && $p->get_status()==='publish'){$summary['excluded_published']++;$warnings[]='excluded_published';}
    $cover=(int)$p->get_image_id();
    if(!$cover){$summary['missing_cover']++;$warnings[]='no_cover';}
    elseif(!($cf=get_attached_file($cover))||!is_file($cf)||filesize($cf)<=0){$summary['invalid_cover']++;$warnings[]='broken_cover';}
    $gallery=array_map('intval',$p->get_gallery_image_ids());
    $used=array_values(array_unique(array_filter(array_merge([$cover],$gallery))));
    $summary['local_images']+=count($used);
    $map=json_decode((string)get_post_meta($pid,'_emdo_image_map',true),true);
    $map=is_array($map)?$map:[];
    if(!$map)$summary['no_source_image_map']++;
    $payload_imgs=array_values(array_filter((array)($src['images']??[]),'is_string'));
    $api_imgs=is_array($origin)?array_values(array_filter(array_map(static fn($i)=>is_array($i)?(string)($i['src']??''):'',(array)($origin['images']??[])))):[];
    if(!$origin){$summary['api_missing']++;$warnings[]='api_missing';}
    else $summary['api_matched']++;
    $compare_images=$api_imgs?:$payload_imgs;
    $summary['api_expected_images']+=count($compare_images);
    if(count($compare_images)>12)$summary['source_images_over12']++;
    $missing_img=[];
    foreach(array_slice($compare_images,0,12) as $url){
        $aid=(int)($map[$url]??0);
        if(!$aid||!in_array($aid,$used,true)||!get_attached_file($aid)||!is_readable(get_attached_file($aid)))$missing_img[]=$url;
        else $summary['gallery_verified_source']++;
    }
    if($missing_img){$summary['missing_gallery_images']+=count($missing_img);$summary['gallery_incomplete']++;$warnings[]='missing_gallery';}
    if($api_imgs&&isset($map[$api_imgs[0]])&&(int)$map[$api_imgs[0]]!==$cover){
        $summary['hero_image_origin_mismatch']++;$warnings[]='different_cover';
    }
    $blankalt=0;$generic=0;$broken_gallery=[];
    foreach($used as $aid){
        $alt=trim((string)get_post_meta($aid,'_wp_attachment_image_alt',true));
        if(!$alt)$blankalt++;
        if($alt===trim($title)||$alt==='Quesos Pajarete')$generic++;
        $f=get_attached_file($aid);
        if(!$f||!is_file($f))$broken_gallery[]=$aid;
    }
    if($blankalt){$summary['image_meta_alt_blank']+=$blankalt;$warnings[]='image_alt_blank';}
    if($generic)$summary['image_meta_alt_generic']+=$generic;
    if($broken_gallery)$warnings[]='broken_gallery';
    $long=trim(wp_strip_all_tags($p->get_description()));
    $short=trim(wp_strip_all_tags($p->get_short_description()));
    $origlong=is_array($origin)?trim(wp_strip_all_tags((string)($origin['description']??''))):'';
    $origshort=is_array($origin)?trim(wp_strip_all_tags((string)($origin['short_description']??''))):'';
    if(!$long){$summary['long_empty']++;$warnings[]='long_empty';}
    if(!$short){$summary['short_empty']++;$warnings[]='short_empty';}
    if($short&&mb_strlen($short)<50)$summary['short_under_50']++;
    if($origlong && md5(preg_replace('/\s+/u',' ',$long))!==md5(preg_replace('/\s+/u',' ',$origlong))){
        $summary['long_differs_api']++;$warnings[]='description_differs_source';
    }
    if($origshort && md5(preg_replace('/\s+/u',' ',$short))!==md5(preg_replace('/\s+/u',' ',$origshort))){
        $summary['short_differs_api']++;$warnings[]='short_differs_source';
    }
    if($short){$hash=md5($short);$short_hashes[$hash][]=$pid;}
    $tl=mb_strlen($title);
    if($tl>70)$summary['title_length_over_70']++;
    $cats=wp_get_post_terms($pid,'product_cat',['fields'=>'names']);
    if(is_wp_error($cats)||!$cats){$summary['category_missing']++;$warnings[]='category_missing';$cats=[];}
    if(in_array('Sin categorizar',(array)$cats,true)){$summary['category_uncategorized']++;$warnings[]='uncategorized';}
    if($p->is_type('variable')){
        $children=$p->get_children();if(!$children){$summary['variable_missing_children']++;$warnings[]='variants_missing';}
        foreach($children as $cid){
            $v=wc_get_product($cid);
            if(!$v||!is_numeric($v->get_regular_price())||(float)$v->get_regular_price()<=0){
                $summary['bad_variation_price']++;$warnings[]='invalid_variation_price';
            }
        }
    }elseif(!is_numeric($p->get_regular_price())||(float)$p->get_regular_price()<=0){$summary['bad_price']++;$warnings[]='no_price';}
    $yoast_title=(string)get_post_meta($pid,'_yoast_wpseo_title',true);
    $yoast_desc=(string)get_post_meta($pid,'_yoast_wpseo_metadesc',true);
    $rank_title=(string)get_post_meta($pid,'rank_math_title',true);
    $rank_desc=(string)get_post_meta($pid,'rank_math_description',true);
    $seo_title=trim($yoast_title?:$rank_title);
    $seo_desc=trim($yoast_desc?:$rank_desc);
    if(!$seo_title){$summary['seo_title_blank']++;$warnings[]='seo_title_blank';}
    if(!$seo_desc){$summary['seo_desc_blank']++;$warnings[]='seo_desc_blank';}
    if(!get_post_meta($pid,'_yoast_wpseo_canonical',true)&&!get_post_meta($pid,'rank_math_canonical_url',true))$summary['seo_canonical_blank']++;
    $reports[]=[
        'id'=>$pid,'source_id'=>(int)$r['id'],'name'=>$title,'src_status'=>$status,'status'=>$p->get_status(),
        'origin_url'=>$r['source_url'],'local_images'=>count($used),'api_images'=>count($api_imgs),
        'payload_images'=>count($payload_imgs),'missing_images'=>count($missing_img),
        'img_urls_missing'=>array_slice($missing_img,0,4),'blank_alts'=>$blankalt,'generic_alts'=>$generic,
        'short_chars'=>mb_strlen($short),'long_chars'=>mb_strlen($long),
        'api_short_chars'=>mb_strlen($origshort),'api_long_chars'=>mb_strlen($origlong),
        'source_intro'=>mb_substr($origshort?:$origlong,0,180),
        'local_short'=>mb_substr($short,0,180),
        'categories'=>(array)$cats,
        'source_categories'=>is_array($origin)?array_map(static fn($c)=>is_array($c)?($c['name']??''):'',(array)($origin['categories']??[])):[],
        'yoast_meta_title'=>$yoast_title,'yoast_meta_description'=>$yoast_desc,
        'warnings'=>array_values(array_unique($warnings))
    ];
}
foreach($short_hashes as $ids){if(count($ids)>1)$summary['duplicate_short_count']+=count($ids);}
$issues=array_values(array_filter($reports,static fn($p)=>!empty($p['warnings'])));
echo 'PAJARETE_QA_OVERVIEW='.wp_json_encode([
  'summary'=>$summary,'api_pages'=>$api_counts,'api_errors'=>$api_errors,
  'seo_plugins'=>$seo_plugin,'aioseo'=>['table_exists'=>$aioseo_exists,'columns'=>$aioseo_fields,'sample_rows'=>$aioseo_rows,'record_count'=>$aioseo_count],
  'api_image_url_unique_all_products'=>$imgs_unique,'api_image_url_duplicates_all_products'=>$imgs_duplicated,'site_permalink_structure'=>get_option('permalink_structure'),
  'issues'=>count($issues),'warning_counts'=>array_count_values(array_merge(...array_map(static fn($i)=>$i['warnings']??[],$reports))),
  'issue_examples'=>array_slice($issues,0,28),
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
foreach($reports as $p)echo 'PAJARETE_QA_PRODUCT='.wp_json_encode($p,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
