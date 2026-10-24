<?php
/**
 * Install the user-approved AI-generated Pajarete banner (not a documentary
 * photograph) in the WCFM producer storefront and producer listing.
 * PHP WP-CLI execution, scoped exclusively to vendor 4974.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('WP-CLI only.');
}
foreach (['siteurl','home'] as $key) {
    $host=strtolower((string)wp_parse_url((string)get_option($key),PHP_URL_HOST));
    if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Refusing nonproduction site.');
    }
}
if (!class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('Expected WooCommerce and supplier plugin.');
}
global $wpdb;
$t=MDO_Database::table('suppliers');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$t} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
$vendor_id=4974;
$vendor=get_user_by('id',$vendor_id);
if (!$s || (int)$s['vendor_user_id']!==$vendor_id || !$vendor ||
    (string)$vendor->user_login!=='quesos-pajarete') {
    throw new RuntimeException('Supplier/vendor mismatch.');
}
$profile=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
if (!is_array($profile) || (string)($profile['store_name']??'')!=='Quesos Pajarete') {
    throw new RuntimeException('Invalid WCFM store settings.');
}
$desktop=getenv('PAJARETE_BANNER_DESKTOP')?:'';
$mobile=getenv('PAJARETE_BANNER_MOBILE')?:'';
foreach ([[$desktop,2022,778],[$mobile,960,369]] as $spec) {
    [$file,$width,$height]=$spec;
    if (!$file || !is_readable($file) || !is_file($file)) {
        throw new RuntimeException('Missing prepared banner: '.$file);
    }
    $bytes=file_get_contents($file);
    $meta=@getimagesizefromstring($bytes);
    if (!$meta || (int)$meta[0]!==$width || (int)$meta[1]!==$height ||
        ($meta['mime']??'')!=='image/webp' || strlen($bytes)<10000) {
        throw new RuntimeException('Unexpected banner dimensions or encoding: '.$file);
    }
}
$files=[
    ['file'=>$desktop,'role'=>'desktop','filename'=>'emdo-producer-quesos-pajarete-banner-20261024.webp'],
    ['file'=>$mobile,'role'=>'mobile','filename'=>'emdo-producer-quesos-pajarete-banner-mobile-960-20261024.webp']
];
$mode=getenv('PAJARETE_BANNER_MODE')?:'audit';
if (!in_array($mode,['audit','apply','verify'],true)) {
    throw new RuntimeException('Unsupported mode.');
}
$asset_sha=hash_file('sha256',$desktop);
if ($mode==='audit') {
    echo 'PAJARETE_BANNER_PREVIEW='.wp_json_encode([
        'vendor_id'=>$vendor_id,'desktop_sha256'=>$asset_sha,
        'current_banner'=>(int)($profile['banner']??0),
        'current_mobile'=>(int)($profile['mobile_banner']??0),
        'current_list'=>(int)($profile['list_banner']??0)
    ],JSON_UNESCAPED_SLASHES).PHP_EOL;
    exit;
}
require_once ABSPATH.'wp-admin/includes/image.php';
require_once ABSPATH.'wp-admin/includes/file.php';
$results=[];
foreach ($files as $item) {
    $role=$item['role'];
    $hash=hash_file('sha256',$item['file']);
    $matches=get_posts([
        'post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,
        'fields'=>'ids','meta_query'=>[
            ['key'=>'_emdo_pajarete_banner_role_20261024','value'=>$role],
            ['key'=>'_emdo_pajarete_banner_sha256','value'=>$hash]
        ]
    ]);
    $id=$matches?(int)$matches[0]:0;
    if ($id) {
        $file=get_attached_file($id);
        if (!$file || !is_readable($file) ||
            hash_file('sha256',$file)!==$hash) $id=0;
    }
    if (!$id && $mode==='verify') {
        throw new RuntimeException('Expected '.$role.' attachment absent.');
    }
    if (!$id) {
        $written=wp_upload_bits($item['filename'],null,file_get_contents($item['file']));
        if (!empty($written['error']) || !is_file($written['file']??'')) {
            throw new RuntimeException('Could not upload banner '.$role);
        }
        $id=wp_insert_attachment([
            'post_title'=>'Quesos Pajarete: banner editorial '.$role,
            'post_excerpt'=>'Composición visual generada para El Mercado de Origen; no representa una fotografía documental de los productos.',
            'post_content'=>'Imagen editorial creada para presentar la identidad quesera de Quesos Pajarete.',
            'post_mime_type'=>'image/webp',
            'post_status'=>'inherit'
        ],$written['file'],0,true);
        if (is_wp_error($id) || !$id) {
            throw new RuntimeException('Could not create media item '.$role);
        }
        $id=(int)$id;
        wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$written['file']));
        update_post_meta($id,'_wp_attachment_image_alt','Quesos artesanos: composición editorial de Quesos Pajarete');
        update_post_meta($id,'_emdo_pajarete_banner_role_20261024',$role);
        update_post_meta($id,'_emdo_pajarete_banner_sha256',$hash);
    }
    $results[$role]=['id'=>$id,'url'=>wp_get_attachment_url($id),'sha256'=>$hash];
}
if ($mode==='apply') {
    $snapshot='_emdo_pajarete_banner_prelaunch_backup_20261024';
    if (false===get_option($snapshot,false)) {
        add_option($snapshot,[
            'banner'=>$profile['banner']??null,
            'mobile_banner'=>$profile['mobile_banner']??null,
            'list_banner'=>$profile['list_banner']??null,
            'banner_type'=>$profile['banner_type']??null,
            'list_banner_type'=>$profile['list_banner_type']??null
        ],'','no');
    }
    $profile['banner_type']='single_img';
    $profile['list_banner_type']='single_img';
    $profile['banner']=(string)$results['desktop']['id'];
    $profile['mobile_banner']=(string)$results['mobile']['id'];
    $profile['list_banner']=(string)$results['mobile']['id'];
    update_user_meta($vendor_id,'wcfmmp_profile_settings',$profile);
}
$updated=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
if (!is_array($updated) ||
    (int)($updated['banner']??0)!==$results['desktop']['id'] ||
    (int)($updated['mobile_banner']??0)!==$results['mobile']['id'] ||
    (int)($updated['list_banner']??0)!==$results['mobile']['id'] ||
    (string)($updated['banner_type']??'')!=='single_img' ||
    (string)($updated['list_banner_type']??'')!=='single_img') {
    throw new RuntimeException('Banner WCFM verification failed.');
}
echo 'PAJARETE_BANNER_RESULT='.wp_json_encode([
    'vendor_id'=>$vendor_id,'desktop'=>$results['desktop'],
    'mobile'=>$results['mobile'],'list_banner'=>$results['mobile'],
    'about_untouched'=>true
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
