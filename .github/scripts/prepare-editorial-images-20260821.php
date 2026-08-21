<?php
/** Import the six curated Unsplash images used by editorial revision 2. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$images = array(
    array('id'=>'kzJdFLlgXgM','direct'=>'https://images.unsplash.com/photo-1709790028365-d49ae1e20f53?auto=format&fit=crop&w=2000&q=85','page'=>'https://unsplash.com/photos/a-close-up-of-meat-being-cut-with-a-knife-kzJdFLlgXgM','photographer'=>'Jean-Jacques CHARLES','alt'=>'Corte de una pieza de jamón curado con cuchillo jamonero'),
    array('id'=>'eqpOgjRpAHA','direct'=>'https://images.unsplash.com/photo-1732565432358-a8c95bc24ea3?auto=format&fit=crop&w=2000&q=85','page'=>'https://unsplash.com/photos/a-close-up-of-sliced-meat-on-a-plate-eqpOgjRpAHA','photographer'=>'Maite Paternain','alt'=>'Lonchas finas de jamón curado servidas en un plato'),
    array('id'=>'102NToxkJFA','direct'=>'https://images.unsplash.com/photo-1765118527220-da9c7a560b13?auto=format&fit=crop&w=2000&q=85','page'=>'https://unsplash.com/photos/olive-oil-being-poured-into-a-bowl-of-herbs-102NToxkJFA','photographer'=>'Asli Dokuzeylul','alt'=>'Aceite de oliva vertiéndose sobre un cuenco con hierbas y especias'),
    array('id'=>'ZhGH7BX9bGY','direct'=>'https://images.unsplash.com/photo-1690983323544-026a23725551?auto=format&fit=crop&w=2000&q=85','page'=>'https://unsplash.com/photos/a-cutting-board-topped-with-raw-meat-next-to-a-knife-ZhGH7BX9bGY','photographer'=>'Sergey Kotenev','alt'=>'Cortes de carne de vacuno cruda sobre una tabla de cocina'),
    array('id'=>'6PFqjxsHMOU','direct'=>'https://images.unsplash.com/photo-1648090229186-6188eaefcc6a?auto=format&fit=crop&w=2000&q=85','page'=>'https://unsplash.com/photos/a-basket-filled-with-lots-of-different-types-of-vegetables-6PFqjxsHMOU','photographer'=>'Annie Lang','alt'=>'Cesta de hortalizas recién cosechadas con tomates, pimientos y raíces'),
    array('id'=>'sV5Va80VGrY','direct'=>'https://images.unsplash.com/photo-1708436478029-732032c5b86d?auto=format&fit=crop&w=2000&q=85','page'=>'https://unsplash.com/photos/a-person-reaching-for-some-food-in-small-bowls-sV5Va80VGrY','photographer'=>'Monika Borys','alt'=>'Distintas variedades de lentejas secas dispuestas en cuencos'),
);

$report = array('images'=>array(),'errors'=>array());
foreach ( $images as $image ) {
    $ids = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_unsplash_photo_id','meta_value'=>$image['id']));
    $attachment_id = !empty($ids) ? (int)$ids[0] : 0;
    if ( $attachment_id <= 0 ) {
        $tmp = download_url($image['direct'], 60);
        if ( is_wp_error($tmp) ) { $report['errors'][]=array('id'=>$image['id'],'error'=>$tmp->get_error_message()); continue; }
        $file_array = array('name'=>'emdo-unsplash-'.$image['id'].'.jpg','tmp_name'=>$tmp);
        $attachment_id = media_handle_sideload($file_array, 0, $image['alt']);
        if ( is_wp_error($attachment_id) ) {
            @unlink($tmp);
            $report['errors'][]=array('id'=>$image['id'],'error'=>$attachment_id->get_error_message());
            continue;
        }
        $attachment_id=(int)$attachment_id;
    }
    wp_update_post(array('ID'=>$attachment_id,'post_title'=>$image['alt'],'post_excerpt'=>'Fotografía: '.$image['photographer'].' · Unsplash.'));
    update_post_meta($attachment_id,'_wp_attachment_image_alt',$image['alt']);
    update_post_meta($attachment_id,'_emdo_unsplash_photo_id',$image['id']);
    update_post_meta($attachment_id,'_emdo_unsplash_page',$image['page']);
    update_post_meta($attachment_id,'_emdo_unsplash_photographer',$image['photographer']);
    update_post_meta($attachment_id,'_emdo_image_license','Unsplash License - free commercial and non-commercial use');
    update_post_meta($attachment_id,'_emdo_image_license_url','https://unsplash.com/license');
    $meta=wp_get_attachment_metadata($attachment_id);
    $report['images'][]=array('id'=>$image['id'],'attachment_id'=>$attachment_id,'width'=>(int)($meta['width']??0),'height'=>(int)($meta['height']??0),'page'=>$image['page']);
}
if(!empty($report['errors']) || count($report['images'])!==6){fwrite(STDERR,wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL);exit(1);} 
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
