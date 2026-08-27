<?php
/** Backfill license/source metadata for batch 12 featured images from the certified repository records. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$items = array(
    'dop-los-pedroches-guide' => array(
        'source_key'=>'commons-dehesa-pigs',
        'page'=>'https://commons.wikimedia.org/wiki/File:Dehesa_Pigs.jpg',
        'creator'=>'comakut',
        'license'=>'CC BY 2.0 / Wikimedia Commons',
        'license_url'=>'https://creativecommons.org/licenses/by/2.0/',
    ),
    'iberian-ham-hand-vs-machine-slicing-guide' => array(
        'source_key'=>'commons-jamon-cortado-madrid-2009',
        'page'=>'https://commons.wikimedia.org/wiki/File:Jam%C3%B3n_cortado-Madrid-2009.jpg',
        'creator'=>'Tamorlan',
        'license'=>'CC BY 3.0 / Wikimedia Commons',
        'license_url'=>'https://creativecommons.org/licenses/by/3.0/',
    ),
    'iberian-ham-yield-packs-guide' => array(
        'source_key'=>'commons-sliced-iberico-ham-francois-nguyen',
        'page'=>'https://commons.wikimedia.org/wiki/File:Sliced_Iberico_ham_(Jam%C3%B3n_ib%C3%A9rico).jpg',
        'creator'=>'François Nguyen',
        'license'=>'CC BY 2.0 / Wikimedia Commons',
        'license_url'=>'https://creativecommons.org/licenses/by/2.0/',
    ),
    'iberian-ham-mould-guide' => array(
        'source_key'=>'commons-bodega-curado-jamon-beher',
        'page'=>'https://commons.wikimedia.org/wiki/File:Bodega_Curado_Jamon_BEHER_Bernardo_Hernandez_Guijuelo_Salamanca.JPG',
        'creator'=>'Pravdaverita',
        'license'=>'CC BY 3.0 / Wikimedia Commons',
        'license_url'=>'https://creativecommons.org/licenses/by/3.0/',
    ),
    'freeze-iberian-ham-storage-guide' => array(
        'source_key'=>'commons-barcelona-boqueria-vacuum-jamon',
        'page'=>'https://commons.wikimedia.org/wiki/File:Barcelona_Mercat_Boqueria_9_(8271967087).jpg',
        'creator'=>'Alain Rouiller',
        'license'=>'CC BY-SA 2.0 / Wikimedia Commons',
        'license_url'=>'https://creativecommons.org/licenses/by-sa/2.0/',
    ),
);

$out=array();
foreach($items as $key=>$m){
    $ids=get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_authority_key','meta_value'=>$key));
    if(empty($ids)){throw new RuntimeException('Post not found: '.$key);}
    $post_id=(int)$ids[0];
    $image_id=(int)get_post_thumbnail_id($post_id);
    if($image_id<=0){throw new RuntimeException('Featured image missing: '.$key);}
    update_post_meta($image_id,'_emdo_image_source_key',$m['source_key']);
    update_post_meta($image_id,'_emdo_image_source_page',$m['page']);
    update_post_meta($image_id,'_emdo_image_creator',$m['creator']);
    update_post_meta($image_id,'_emdo_image_license',$m['license']);
    update_post_meta($image_id,'_emdo_image_license_url',$m['license_url']);
    if(get_post_meta($image_id,'_emdo_image_changes',true)===''){
        update_post_meta($image_id,'_emdo_image_changes','Responsive display may resize or crop the image to fit the site layout.');
    }
    $out[]=array('key'=>$key,'post_id'=>$post_id,'image_id'=>$image_id,'license'=>$m['license'],'creator'=>$m['creator'],'page'=>$m['page']);
}
echo wp_json_encode(array('backfilled'=>true,'items'=>$out),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
