<?php
/**
 * Guarded WCFM EntreSetas producer artwork + Spanish About update, production.
 * Does not enable seller or products, and does not touch commercial/shipping data.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    throw new RuntimeException('WP-CLI required');
}
foreach (['home','siteurl'] as $key) {
    $host=strtolower((string)wp_parse_url((string)get_option($key), PHP_URL_HOST));
    if (!in_array($host,['elmercadodeorigen.com','www.elmercadodeorigen.com'],true)) {
        throw new RuntimeException('Refusing nonproduction site: '.$host);
    }
}
if (!class_exists('MDO_Database')) throw new RuntimeException('EMDO supplier plugin not active');
global $wpdb;
$vendor_id=4982;
$user=get_user_by('id',$vendor_id);
$profile=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
$table=MDO_Database::table('suppliers');
$supplier=$wpdb->get_row($wpdb->prepare("SELECT code,vendor_user_id FROM {$table} WHERE code=%s LIMIT 1",'entresetas'),ARRAY_A);
if (!$user || $user->user_login!=='entresetas' || $user->user_nicename!=='entresetas' ||
    !in_array('wcfm_vendor',(array)$user->roles,true) ||
    !is_array($profile) || (string)($profile['store_name']??'')!=='EntreSetas' ||
    (string)($profile['store_slug']??'')!=='entresetas' ||
    !$supplier || (int)$supplier['vendor_user_id']!==$vendor_id) {
    throw new RuntimeException('EntreSetas supplier/vendor identity mismatch; no changes');
}
$prior_disabled=(bool)get_user_meta($vendor_id,'_disable_vendor',true);
$prior_media_auth=(bool)get_option('mdo_entresetas_media_authorized',false);
$prior_commercial=(bool)get_option('mdo_entresetas_commercial_ready',false);
$files=[
 'desktop'=>['file'=>(string)getenv('ENTRESETAS_BANNER_DESKTOP_FILE'),'width'=>2022,'height'=>778],
 'mobile'=>['file'=>(string)getenv('ENTRESETAS_BANNER_MOBILE_FILE'),'width'=>960,'height'=>369],
];
foreach ($files as $label=>&$item) {
    if (!$item['file'] || !is_readable($item['file']) ||
        filesize($item['file'])<15000 || filesize($item['file'])>5000000) {
        throw new RuntimeException('No readable valid '.$label.' banner');
    }
    $img=@getimagesize($item['file']);
    if (!$img || $img[0]!==$item['width'] || $img[1]!==$item['height'] ||
        ($img['mime']??'')!=='image/webp') {
        throw new RuntimeException('Unexpected '.$label.' image size or format');
    }
    $item['sha256']=hash_file('sha256',$item['file']);
}
unset($item);

$description=<<<'HTML'
<p>La historia de EntreSetas comienza con Nazaret Mateos y con una pasión por las setas que la acompaña desde la infancia. Creció cerca de la Sierra de la Culebra, en Zamora, entre paseos por el monte y jornadas de recolección. Años después, ya instalada en Palencia y trabajando en una oficina, seguía imaginando un proyecto que le permitiera volver a conectar con el campo. Esa idea terminó tomando forma en 2018, cuando nació EntreSetas en Paredes de Nava.</p>
<p>Desde el principio, Nazaret quiso que la manera de cultivar fuera tan importante como el producto obtenido. EntreSetas desarrolla su actividad en un entorno rural de Tierra de Campos, con un método de cultivo ecológico que se adapta a los ciclos naturales de luz, temperatura y humedad. En lugar de buscar una producción idéntica durante todo el año, trabaja con distintas variedades según la estación y las condiciones más adecuadas para cada una de ellas.</p>
<p>El aprovechamiento de los recursos cercanos es una parte esencial de esta forma de trabajar. Para preparar los sustratos utilizan materiales de origen local, como paja, cereales y serrín procedente de talas responsables. El suelo con cubierta vegetal contribuye a mantener la humedad en las zonas de cultivo, reduciendo la necesidad de recurrir a sistemas más intensivos y manteniendo una relación estrecha con el entorno.</p>
<p>Esta manera de producir permite ofrecer una selección de setas gourmet con personalidades muy distintas. Junto a variedades como shiitake, seta de ostra, seta de cardo o enoki, el proyecto ha desarrollado productos en otros formatos para disfrutar de su sabor más allá de la temporada de fresco: deshidratados, conservas, harinas, sales y otras elaboraciones relacionadas con el mundo de las setas.</p>
<p>El compromiso ambiental también está presente después de la cosecha. EntreSetas busca aprovechar los restos del cultivo, que pueden destinarse a abono para parcelas vecinas o a alimentación animal, dentro de una filosofía de reducción de residuos y uso responsable de los materiales. Es un planteamiento que también tiene una dimensión social: colaborar con la actividad del entorno y contribuir a que el medio rural siga siendo un lugar en el que emprender y trabajar.</p>
<p>La iniciativa ha recibido reconocimientos por su innovación agraria y su apuesta por la sostenibilidad, pero su labor no termina en la producción. Nazaret y su equipo también organizan talleres y actividades divulgativas para acercar el mundo de las setas a niños y adultos, compartir lo aprendido y fomentar una mirada más atenta hacia la naturaleza.</p>
<p>Hoy, EntreSetas reúne aquella afición que nació en los montes de Zamora con un proyecto propio arraigado en Palencia. Detrás de cada variedad y cada elaboración hay una forma de entender el cultivo que pone en primer plano el origen, el respeto por los ciclos naturales y el cuidado de lo que llega a la mesa.</p>
HTML;
if (mb_strlen(wp_strip_all_tags($description))<1300) {
    throw new RuntimeException('About description too short');
}
$backup_key='_emdo_entresetas_visuals_backup_20261029';
if (!metadata_exists('user',$vendor_id,$backup_key)) {
    $backup=[
      'profile'=>$profile,
      '_store_description'=>(string)get_user_meta($vendor_id,'_store_description',true),
    ];
    if (!add_user_meta($vendor_id,$backup_key,$backup,true)) {
        throw new RuntimeException('Failed to create visual profile rollback');
    }
}
require_once ABSPATH.'wp-admin/includes/file.php';
require_once ABSPATH.'wp-admin/includes/media.php';
require_once ABSPATH.'wp-admin/includes/image.php';
$attachments=[];
foreach ($files as $kind=>$item) {
    $existing=get_posts([
      'post_type'=>'attachment','post_status'=>'inherit','fields'=>'ids','posts_per_page'=>10,
      'meta_query'=>[
        ['key'=>'_emdo_entresetas_banner_sha256','value'=>$item['sha256']],
        ['key'=>'_emdo_entresetas_banner_role','value'=>$kind],
      ],
    ]);
    $id=0;
    foreach ((array)$existing as $candidate) {
        $f=get_attached_file((int)$candidate);
        if ($f && is_readable($f) && hash_file('sha256',$f)===$item['sha256']) {
            $id=(int)$candidate;
            break;
        }
    }
    if (!$id) {
        $filename='emdo-entresetas-banner-'.$kind.'-20261029.webp';
        $data=wp_upload_bits($filename,null,file_get_contents($item['file']));
        if (!empty($data['error'])||!is_file($data['file']??'')) {
            throw new RuntimeException('Cannot upload '.$kind.' banner');
        }
        $id=wp_insert_attachment([
            'post_title'=>'EntreSetas — banner '.($kind==='desktop'?'principal':'móvil y directorio'),
            'post_content'=>'Composición editorial conceptual de setas gourmet, sin representar instalaciones reales de EntreSetas.',
            'post_mime_type'=>'image/webp',
            'post_status'=>'inherit',
        ],$data['file'],0,true);
        if (is_wp_error($id)||!$id)throw new RuntimeException('Cannot register '.$kind.' banner');
        $id=(int)$id;
        wp_update_attachment_metadata($id,wp_generate_attachment_metadata($id,$data['file']));
        update_post_meta($id,'_emdo_entresetas_banner_sha256',$item['sha256']);
        update_post_meta($id,'_emdo_entresetas_banner_role',$kind);
        update_post_meta($id,'_wp_attachment_image_alt','Selección conceptual de setas gourmet: seta de ostra, shiitake, seta de cardo y enoki');
    }
    $attachments[$kind]=$id;
}
$profile['banner_type']='single_img';
$profile['list_banner_type']='single_img';
$profile['banner']=(string)$attachments['desktop'];
$profile['mobile_banner']=(string)$attachments['mobile'];
$profile['list_banner']=(string)$attachments['mobile'];
$profile['shop_description']=$description;
update_user_meta($vendor_id,'_store_description',$description);
update_user_meta($vendor_id,'wcfmmp_profile_settings',$profile);
clean_user_cache($vendor_id);
wp_cache_delete($vendor_id,'user_meta');

$check=get_user_meta($vendor_id,'wcfmmp_profile_settings',true);
$check_direct=(string)get_user_meta($vendor_id,'_store_description',true);
if (!is_array($check) ||
    (int)($check['banner']??0)!==$attachments['desktop'] ||
    (int)($check['mobile_banner']??0)!==$attachments['mobile'] ||
    (int)($check['list_banner']??0)!==$attachments['mobile'] ||
    ($check['banner_type']??'')!=='single_img' ||
    ($check['list_banner_type']??'')!=='single_img' ||
    (string)($check['shop_description']??'')!==$description ||
    $check_direct!==$description ||
    (bool)get_user_meta($vendor_id,'_disable_vendor',true)!==$prior_disabled ||
    (bool)get_option('mdo_entresetas_media_authorized',false)!==$prior_media_auth ||
    (bool)get_option('mdo_entresetas_commercial_ready',false)!==$prior_commercial) {
    throw new RuntimeException('Verification failed after updating EntreSetas visuals');
}
echo 'ENTRESETAS_VISUALS_UPDATED='.wp_json_encode([
    'vendor_id'=>$vendor_id,'store_slug'=>'entresetas',
    'banner_desktop_id'=>$attachments['desktop'],
    'banner_desktop_url'=>wp_get_attachment_url($attachments['desktop']),
    'banner_mobile_list_id'=>$attachments['mobile'],
    'banner_mobile_list_url'=>wp_get_attachment_url($attachments['mobile']),
    'about_chars'=>mb_strlen(wp_strip_all_tags($description)),
    'about_sha256'=>hash('sha256',$description),
    'vendor_disabled'=>$prior_disabled,
    'media_rights_flag_unchanged'=>true,
    'commercial_flag_unchanged'=>true,
],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
