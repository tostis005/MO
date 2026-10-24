<?php
/**
 * One-off conservative category tidy-up for 14 already-published Pajarete
 * products. Never modifies price, stock, vendor, visibility or product status.
 * The excluded salpimentero remains hidden and untouched.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI || !class_exists('MDO_Database') || !class_exists('WooCommerce')) {
    throw new RuntimeException('WordPress/WooCommerce WP-CLI required.');
}
foreach (['siteurl','home'] as $option) {
    $h = strtolower((string)wp_parse_url((string)get_option($option),PHP_URL_HOST));
    if (!in_array($h,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) throw new RuntimeException('Production-only.');
}
global $wpdb;
$vendors=MDO_Database::table('suppliers');
$sources=MDO_Database::table('source_products');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$vendors} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
if (!$s || (int)$s['vendor_user_id']!==4974) throw new RuntimeException('Wrong vendor/supplier.');
$vendor=get_user_by('id',4974);
if (!$vendor || $vendor->user_login!=='quesos-pajarete') throw new RuntimeException('Vendor identity mismatch.');

/* Verified product ID, title indicator, and existing correct marketplace category. */
$spec=[
    21300=>['regañ','aperitivos-y-snacks'],
    21301=>['patatas fritas','aperitivos-y-snacks'],
    21302=>['gilda anchoa','conservas'],
    21305=>['gilda matrimonio','conservas'],
    21472=>['gilda doble','conservas'],
    21473=>['patatas fritas','aperitivos-y-snacks'],
    21475=>['gilda pulpo','conservas'],
    21476=>['regañ','aperitivos-y-snacks'],
    21478=>['patatas fritas','aperitivos-y-snacks'],
    21479=>['membrillo','mermeladas-y-confituras'],
    21503=>['salón gourmet','packs-y-lotes'],
    21504=>['quesierra','packs-y-lotes'],
    21507=>['regañ','aperitivos-y-snacks'],
    21524=>['patatas fritas','aperitivos-y-snacks']
];
if (count($spec)!==14)throw new RuntimeException('Unexpected mapping count.');
$uncategorized=get_term_by('slug','sin-categorizar','product_cat');
if (!$uncategorized instanceof WP_Term)throw new RuntimeException('Default category not found.');
$phase=getenv('PAJARETE_CATEGORIES_MODE')?:'audit';
if (!in_array($phase,['audit','apply','verify'],true))throw new RuntimeException('Invalid phase.');
if ($phase!=='audit' && !get_term_by('slug','aperitivos-y-snacks','product_cat')) {
    if ($phase==='verify')throw new RuntimeException('Snack category missing.');
    $new=wp_insert_term('Aperitivos y snacks','product_cat',[
        'slug'=>'aperitivos-y-snacks',
        'description'=>'Una selección de patatas fritas, regañás, crackers y otros aperitivos para disfrutar y compartir.'
    ]);
    if (is_wp_error($new))throw new RuntimeException('Could not create snacks category: '.$new->get_error_message());
}
$verified=[];$before=[];$errors=[];
foreach ($spec as $id=>$info) {
    [$phrase,$slug]=$info;
    $p=wc_get_product($id);
    if (!$p || $p->is_type('variation'))throw new RuntimeException('Product absent or variation: '.$id);
    if ((int)get_post_field('post_author',$id)!==4974)throw new RuntimeException('Unexpected product owner: '.$id);
    $name=remove_accents(mb_strtolower($p->get_name()));
    $phrase=remove_accents(mb_strtolower($phrase));
    if (!str_contains($name,$phrase))throw new RuntimeException('Wrong product/title for '.$id.': '.$p->get_name());
    $source=$wpdb->get_row($wpdb->prepare("SELECT status,title,source_url FROM {$sources} WHERE supplier_id=%d AND wc_product_id=%d LIMIT 1",(int)$s['id'],$id),ARRAY_A);
    if (!$source || $source['status']!=='active' || !in_array(strtolower((string)wp_parse_url($source['source_url'],PHP_URL_HOST)),['quesospajarete.com','www.quesospajarete.com'],true)) {
        throw new RuntimeException('Product not verified active source: '.$id);
    }
    if (class_exists('MDO_Connector_Quesos_Pajarete')&&MDO_Connector_Quesos_Pajarete::excluded($source['source_url'],$p->get_name())){
        throw new RuntimeException('Excluded product encountered: '.$id);
    }
    if ($p->get_status()!=='publish' || $p->get_catalog_visibility()!=='visible') {
        throw new RuntimeException('Unexpected publication state: '.$id);
    }
    $term=get_term_by('slug',$slug,'product_cat');
    if ($phase!=='audit' && !$term)throw new RuntimeException('Missing target category '.$slug);
    $existing=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);
    if (is_wp_error($existing))throw new RuntimeException('Could not load categories '.$id);
    $target_id=$term instanceof WP_Term?(int)$term->term_id:0;
    $old_ids=array_map('intval',(array)$existing);
    if ($phase==='apply' && !in_array($target_id,$old_ids,true)) {
        if ($old_ids && array_diff($old_ids,[(int)$uncategorized->term_id])) {
            throw new RuntimeException('Refusing overwrite other curated categories on '.$id);
        }
        $result=wp_set_object_terms($id,[$target_id],'product_cat',false);
        if (is_wp_error($result))throw new RuntimeException('Term assignment failed for '.$id);
        clean_post_cache($id);
        wc_delete_product_transients($id);
    }
    $current=wp_get_post_terms($id,'product_cat',['fields'=>'ids']);
    if (is_wp_error($current))throw new RuntimeException('Cannot re-read category '.$id);
    $current=array_map('intval',$current);
    $correct=$target_id>0 && in_array($target_id,$current,true) &&
       !in_array((int)$uncategorized->term_id,$current,true);
    if ($phase==='verify' && !$correct)throw new RuntimeException('Category verification failed '.$id);
    $verified[]=['id'=>$id,'name'=>$p->get_name(),'expected'=>$slug,'status'=>$p->get_status(),
        'current_ids'=>$current,'ok'=>$correct];
}
$excluded_id=21505;
$excluded=wc_get_product($excluded_id);
if (!$excluded || $excluded->get_status()==='publish' || $excluded->get_catalog_visibility()!=='hidden') {
    throw new RuntimeException('Excluded salpimentero unexpectedly public.');
}
$profile=get_user_meta(4974,'wcfmmp_profile_settings',true);
if (!is_array($profile)) throw new RuntimeException('WCFM profile absent.');
$main=(int)($profile['banner']??0);$mobile=(int)($profile['mobile_banner']??0);$list=(int)($profile['list_banner']??0);
$about=(string)get_user_meta(4974,'_store_description',true);
$about_profile=(string)($profile['shop_description']??'');
$banner_report=[];
foreach (['desktop'=>$main,'mobile'=>$mobile] as $label=>$attachment){
    $path=$attachment?get_attached_file($attachment):false;
    $size=$path&&is_file($path)?@getimagesize($path):false;
    $banner_report[$label]=['id'=>$attachment,
        'dimensions'=>$size?[$size[0],$size[1]]:null,
        'mime'=>$size['mime']??null,'url'=>$attachment?wp_get_attachment_url($attachment):''];
}
if ($phase==='verify' &&
    (!$about || $about!==$about_profile || preg_match('/turn\\d+||【|】/u',$about) ||
    $list!==$mobile || ($banner_report['desktop']['dimensions']??null)!==[2022,778] ||
    ($banner_report['mobile']['dimensions']??null)!==[960,369])) {
    throw new RuntimeException('About or banner integrity failure.');
}
echo 'PAJARETE_CATEGORY_POLISH='.wp_json_encode([
  'phase'=>$phase,'targeted'=>count($spec),
  'properly_classified'=>count(array_filter($verified,static fn($x)=>$x['ok'])),
  'items'=>$verified,'excluded_sal_pimentero_still_hidden'=>true,
  'banner'=>$banner_report,'about_exact_and_clean'=>(bool)($about&&$about===$about_profile&&!preg_match('/turn\\d+||【|】/u',$about))
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
