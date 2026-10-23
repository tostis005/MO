<?php
/**
 * Read-only audit of imported Quesos Pajarete WooCommerce products.
 * Scoped to supplier code and its vendor. No writes.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('Required WP environment missing');
}
foreach (array('siteurl','home') as $option) {
    $host = strtolower((string)wp_parse_url((string)get_option($option), PHP_URL_HOST));
    if (!in_array($host, array('elmercadodeorigen.com','www.elmercadodeorigen.com'), true)) {
        throw new RuntimeException('Refusing nonproduction environment.');
    }
}
global $wpdb;
$suppliers = MDO_Database::table('suppliers');
$src = MDO_Database::table('source_products');
$s = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$suppliers} WHERE code=%s LIMIT 1",'quesos-pajarete'), ARRAY_A);
if (!$s) { throw new RuntimeException('Supplier missing'); }
$vendor_id = (int)$s['vendor_user_id'];
$rows = $wpdb->get_results($wpdb->prepare("SELECT id,title,status,source_url,source_payload,wc_product_id,last_error FROM {$src} WHERE supplier_id=%d ORDER BY id",$s['id']), ARRAY_A);
$counts = array('rows'=>count($rows),'woo'=>0,'no_cover'=>0,'no_cover_file'=>0,'no_gallery'=>0,'short_empty'=>0,'long_empty'=>0,'long_under_150'=>0,'src_no_images'=>0,'src_has_images_no_cover'=>0,'source_short_exists'=>0,'source_long_exists'=>0,'simple'=>0,'variable'=>0,'no_price'=>0,'published'=>0,'hidden'=>0,'mismatched_vendor'=>0,'suspect_excluded'=>0,'unlinked'=>0);
$flags = array();
$line = 0;
foreach ($rows as $r) {
    $id = (int)$r['wc_product_id'];
    if (!$id) { $counts['unlinked']++; continue; }
    $p = wc_get_product($id);
    if (!$p) { $counts['unlinked']++; continue; }
    $counts['woo']++;
    $payload = json_decode((string)$r['source_payload'],true);
    $payload = is_array($payload) ? $payload : array();
    $src_images = array_values(array_filter((array)($payload['images'] ?? array()),'is_string'));
    $img_id = (int)$p->get_image_id();
    $img_file = $img_id ? get_attached_file($img_id) : false;
    $file_ok = $img_file && is_file($img_file) && filesize($img_file)>0;
    $gallery = $p->get_gallery_image_ids();
    $long = trim(wp_strip_all_tags($p->get_description()));
    $short = trim(wp_strip_all_tags($p->get_short_description()));
    $src_long = trim(wp_strip_all_tags((string)($payload['description']??'')));
    $src_short = trim(wp_strip_all_tags((string)($payload['short_description']??'')));
    $warnings = array();
    if (!$img_id) { $counts['no_cover']++; $warnings[]='no_cover'; }
    elseif (!$file_ok) { $counts['no_cover_file']++; $warnings[]='cover_file_missing'; }
    if (!$gallery) { $counts['no_gallery']++; }
    if (!$src_images) { $counts['src_no_images']++; $warnings[]='source_no_images'; }
    elseif (!$img_id) { $counts['src_has_images_no_cover']++; }
    if (!$short) { $counts['short_empty']++; $warnings[]='short_empty'; }
    if (!$long) { $counts['long_empty']++; $warnings[]='long_empty'; }
    if (mb_strlen($long)<150) { $counts['long_under_150']++; }
    if ($src_short) { $counts['source_short_exists']++; }
    if ($src_long) { $counts['source_long_exists']++; }
    $var_count=$p->is_type('variable')?count($p->get_children()):0;
    $counts[$p->is_type('variable')?'variable':'simple']++;
    if ($p->get_price()==='') { $counts['no_price']++; $warnings[]='no_price'; }
    if ($p->get_status()==='publish') { $counts['published']++; $warnings[]='published'; }
    if ($p->get_catalog_visibility()==='hidden') { $counts['hidden']++; }
    if ((int)get_post_field('post_author',$id)!==$vendor_id) { $counts['mismatched_vendor']++;$warnings[]='wrong_vendor'; }
    if (preg_match('/\\b(?:queso(?:s)?\\s+)?(?:frescos?|tiernos?|untables?)\\b|\\bcrema(?:s)?\\s+de\\s+queso\\b/iu',(string)$p->get_name())) {
        $counts['suspect_excluded']++; $warnings[]='potentially_excluded';
    }
    $terms = wp_get_post_terms($id,'product_cat',array('fields'=>'names'));
    $item=array(
        'id'=>$id,'title'=>$p->get_name(),'status'=>$p->get_status(),'source_status'=>$r['status'],
        'source_url'=>$r['source_url'],'price'=>$p->get_price(),'variants'=>$var_count,
        'cover_id'=>$img_id,'cover_file_ok'=>(bool)$file_ok,'gallery'=>count($gallery),
        'source_images'=>count($src_images),'source_image_first'=>$src_images[0]??'',
        'short_chars'=>mb_strlen($short),'long_chars'=>mb_strlen($long),
        'src_short_chars'=>mb_strlen($src_short),'src_long_chars'=>mb_strlen($src_long),
        'desc_preview'=>mb_substr(preg_replace('/\\s+/u',' ',$long),0,120),
        'src_desc_preview'=>mb_substr(preg_replace('/\\s+/u',' ',$src_long),0,120),
        'categories'=>is_wp_error($terms)?array():(array)$terms,
        'warnings'=>$warnings
    );
    echo 'PAJARETE_AUDIT_ITEM='.wp_json_encode($item,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
    if ($warnings) { $flags[$id]=$warnings; }
    $line++;
}
echo 'PAJARETE_AUDIT_SUMMARY='.wp_json_encode(array(
    'supplier_id'=>(int)$s['id'],'vendor_id'=>$vendor_id,
    'vendor_disabled'=>(bool)get_user_meta($vendor_id,'_disable_vendor',true),
    'counts'=>$counts,'warned_items'=>count($flags)
),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
