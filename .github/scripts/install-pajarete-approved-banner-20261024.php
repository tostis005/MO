<?php
/**
 * Install approved, generated Quesos Pajarete panorama as native WCFM producer
 * banner (desktop/mobile/list). No other seller/profile/product is modified.
 */
if (!defined('ABSPATH') || !class_exists('WooCommerce') || !class_exists('MDO_Database')) {
    throw new RuntimeException('WooCommerce / EMDO is required');
}
foreach (['home','siteurl'] as $name) {
    $host=strtolower((string)wp_parse_url((string)get_option($name),PHP_URL_HOST));
    if(!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Only production site is permitted');
    }
}
global $wpdb;
$table=MDO_Database::table('suppliers');
$s=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE code=%s LIMIT 1",'quesos-pajarete'),ARRAY_A);
$id=4974;
$user=get_user_by('id',$id);
if(!$s||(int)$s['vendor_user_id']!==$id||!$user||
   $user->user_login!=='quesos-pajarete') {
    throw new RuntimeException('Pajarete vendor identity mismatch');
}
$p=get_user_meta($id,'wcfmmp_profile_settings',true);
if(!is_array($p)||($p['store_name']??'')!=='Quesos Pajarete') {
    throw new RuntimeException('Pajarete WCFM settings not found');
}
$files=[
    'desktop'=>['path'=>getenv('PAJARETE_BANNER_DESKTOP_FILE'),'w'=>2022,'h'=>778,'q'=>'main'],
    'mobile'=>['path'=>getenv('PAJARETE_BANNER_MOBILE_FILE'),'w'=>960,'h'=>369,'q'=>'mobile'],
];
foreach($files as $name=>$v) {
    $file=(string)$v['path'];
    if(!$file||!is_file($file)||!is_readable($file)||filesize($file)<15000||filesize($file)>5000000) {
        throw new RuntimeException('Missing validated '.$name.' image');
    }
    $img=@getimagesize($file);
    if(!$img||(int)$img[0]!==$v['w']||(int)$img[1]!==$v['h']||
      ($img['mime']??'')!=='image/webp') {
        throw new RuntimeException('Incorrect image dimensions or mime: '.$name);
    }
    $files[$name]['hash']=hash_file('sha256',$file);
}
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$attached=[];
foreach($files as $name=>$v) {
    $hash=$v['hash'];
    $found=get_posts([
       'post_type'=>'attachment','post_status'=>'inherit','fields'=>'ids','posts_per_page'=>5,
       'meta_query'=>[
          ['key'=>'_emdo_pajarete_banner_hash','value'=>$hash],
          ['key'=>'_emdo_pajarete_banner_role','value'=>$name],
       ]
    ]);
    $aid=0;
    foreach($found as $candidate) {
        $f=get_attached_file((int)$candidate);
        if($f&&is_readable($f)&&hash_file('sha256',$f)===$hash) {
            $aid=(int)$candidate;break;
        }
    }
    if(!$aid) {
        $filename='emdo-quesos-pajarete-banner-'.($name==='desktop'?'2022x778':'960x369').'-20261024.webp';
        $bytes=file_get_contents($v['path']);
        $written=wp_upload_bits($filename,null,$bytes);
        if(!empty($written['error'])||!is_file($written['file']??'')) {
            throw new RuntimeException('Could not upload native '.$name.' banner');
        }
        $aid=wp_insert_attachment([
            'post_title'=>'Quesos Pajarete — banner '.($name==='desktop'?'principal':'móvil y listado'),
            'post_content'=>'Imagen editorial ilustrativa generada para El Mercado de Origen.',
            'post_mime_type'=>'image/webp','post_status'=>'inherit',
        ],$written['file'],0,true);
        if(is_wp_error($aid)||!$aid)throw new RuntimeException('Could not create attachment for '.$name);
        $aid=(int)$aid;
        wp_update_attachment_metadata($aid,wp_generate_attachment_metadata($aid,$written['file']));
        update_post_meta($aid,'_emdo_pajarete_banner_hash',$hash);
        update_post_meta($aid,'_emdo_pajarete_banner_role',$name);
        update_post_meta($aid,'_wp_attachment_image_alt','Selección de quesos artesanales y paisaje de la Sierra de Cádiz — composición ilustrativa');
    }
    $attached[$name]=$aid;
}
$backup_key='_emdo_pajarete_banner_rollback_20261024';
if(!metadata_exists('user',$id,$backup_key)) {
    $old=[];
    foreach(['banner','mobile_banner','list_banner','banner_type','list_banner_type'] as $k) {
        $old[$k]=$p[$k]??null;
    }
    update_user_meta($id,$backup_key,$old);
}
$p['banner_type']='single_img';
$p['list_banner_type']='single_img';
$p['banner']=(string)$attached['desktop'];
$p['mobile_banner']=(string)$attached['mobile'];
$p['list_banner']=(string)$attached['mobile'];
update_user_meta($id,'wcfmmp_profile_settings',$p);
$read=get_user_meta($id,'wcfmmp_profile_settings',true);
if(!is_array($read)||
    (int)($read['banner']??0)!==$attached['desktop']||
    (int)($read['mobile_banner']??0)!==$attached['mobile']||
    (int)($read['list_banner']??0)!==$attached['mobile']||
    ($read['banner_type']??'')!=='single_img'||
    ($read['list_banner_type']??'')!=='single_img') {
    throw new RuntimeException('Failed to verify saved Pajarete profile banner');
}
echo 'PAJARETE_BANNER_INSTALLED='.wp_json_encode([
    'vendor'=>$id,'desktop_id'=>$attached['desktop'],'mobile_list_id'=>$attached['mobile'],
    'desktop_url'=>wp_get_attachment_url($attached['desktop']),
    'mobile_url'=>wp_get_attachment_url($attached['mobile']),
    'desktop_sha256'=>$files['desktop']['hash'],
    'mobile_sha256'=>$files['mobile']['hash'],
    'about_preserved'=>!empty($read['shop_description']),
    'format'=>'2022x778 desktop, 960x369 mobile/list, WebP',
],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES).PHP_EOL;
