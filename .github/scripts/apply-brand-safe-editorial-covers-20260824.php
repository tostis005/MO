<?php
/**
 * Apply the 2026-08-24 brand-safe Journal cover selection.
 *
 * Ham imagery is derived only from product photography already present in this
 * WordPress installation and cropped so no producer mark, label or uniform is
 * visible. Other replacements use explicitly unbranded Pexels photography.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$mode       = (string) ( getenv( 'MDO_COVER_MODE' ) ?: 'apply' );
$state_file = (string) getenv( 'MDO_COVER_STATE_FILE' );

$local = array(
    array('kind'=>'authority','value'=>'ham-cutting-guide','asset'=>'emdo-jamon-corte-20260824','source'=>2306,'crop'=>array(0,115,460,259),'alt'=>'Detalle del corte de un jamón ibérico, sin marcas ni etiquetas visibles'),
    array('kind'=>'authority','value'=>'ham-quantity-per-person-guide','asset'=>'emdo-jamon-raciones-20260824','source'=>5111,'crop'=>array(250,300,1450,816),'alt'=>'Lonchas de jamón ibérico listas para servir, sin marca visible'),
    array('kind'=>'authority','value'=>'sliced-ham-storage-guide','asset'=>'emdo-jamon-loncheado-conservacion-20260824','source'=>10460,'crop'=>array(1500,350,900,506),'alt'=>'Jamón ibérico loncheado envasado al vacío, sin etiqueta ni marca visible'),
    array('kind'=>'authority','value'=>'bellota-100-iberico-guide','asset'=>'emdo-jamon-bellota-100-20260824','source'=>2306,'crop'=>array(0,170,460,259),'alt'=>'Detalle de una pieza de jamón de bellota 100 % ibérico durante el corte'),
    array('kind'=>'authority','value'=>'iberian-ham-bones-trimmings-guide','asset'=>'emdo-jamon-huesos-recortes-20260824','source'=>2306,'crop'=>array(0,80,400,225),'alt'=>'Detalle de hueso, grasa y magro durante el aprovechamiento de una pieza de jamón ibérico'),
    array('kind'=>'authority','value'=>'ham-starting-orientation-guide','asset'=>'emdo-jamon-empezar-pieza-20260824','source'=>2305,'crop'=>array(0,60,440,248),'alt'=>'Pieza de jamón ibérico abierta en jamonero, sin etiquetas ni marcas visibles'),
    array('kind'=>'authority','value'=>'iberian-ham-parts-guide','asset'=>'emdo-jamon-partes-20260824','source'=>2305,'crop'=>array(0,105,420,236),'alt'=>'Detalle del magro, la grasa y la corteza de una pieza de jamón ibérico'),
    array('kind'=>'authority','value'=>'whole-ham-before-opening-storage-guide','asset'=>'emdo-jamon-conservacion-pieza-20260824','source'=>2310,'crop'=>array(0,0,500,281),'alt'=>'Piezas de jamón ibérico enteras conservándose en bodega, sin marcas visibles'),
    array('kind'=>'authority','value'=>'serve-iberian-ham-guide','asset'=>'emdo-jamon-servir-20260824','source'=>5111,'crop'=>array(120,120,1650,928),'alt'=>'Lonchas finas de jamón ibérico preparadas para el servicio, sin marca visible'),
    array('kind'=>'slug','value'=>'jamon-iberico','asset'=>'emdo-jamon-iberico-general-20260824','source'=>2305,'crop'=>array(0,0,430,242),'alt'=>'Primer plano de una pieza de jamón ibérico mostrando su infiltración y textura'),
    array('kind'=>'slug','value'=>'jamon-pieza-entera-o-loncheado-como-elegir','asset'=>'emdo-jamon-entero-o-loncheado-20260824','source'=>5111,'crop'=>array(200,100,1500,844),'alt'=>'Jamón ibérico loncheado al vacío como uno de los formatos de compra habituales'),
    array('kind'=>'slug','value'=>'jamon-o-paleta-diferencias-cual-elegir','asset'=>'emdo-jamon-o-paleta-20260824','source'=>2310,'crop'=>array(0,70,500,281),'alt'=>'Piezas ibéricas enteras en bodega para ilustrar la elección entre jamón y paleta'),
);

$pexels = array(
    array('kind'=>'authority','value'=>'iberian-board-guide','id'=>'5865336','direct'=>'https://images.pexels.com/photos/5865336/pexels-photo-5865336.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/meat-platter-with-bread-and-olives-5865336/','photographer'=>'Rachel Claire','alt'=>'Tabla de embutidos curados con pan y aceitunas, sin envases ni marcas visibles'),
    array('kind'=>'authority','value'=>'spanish-aperitivo-planning-guide','id'=>'11406465','direct'=>'https://images.pexels.com/photos/11406465/pexels-photo-11406465.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/charcuterie-board-on-the-table-11406465/','photographer'=>'Nadin Sh','alt'=>'Aperitivo con jamón, aceitunas, pan y otros acompañamientos servido para compartir'),
    array('kind'=>'authority','value'=>'commercial-preserve-sterilisation-guide','id'=>'33984949','direct'=>'https://images.pexels.com/photos/33984949/pexels-photo-33984949.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/assorted-homemade-preserved-vegetables-in-jars-33984949/','photographer'=>'TIVASEE .','alt'=>'Tarros cerrados con distintas conservas vegetales, sin etiquetas comerciales visibles'),
    array('kind'=>'authority','value'=>'evoo-expiry-quality-guide','id'=>'7296399','direct'=>'https://images.pexels.com/photos/7296399/pexels-photo-7296399.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/olive-oil-in-a-glass-jar-7296399/','photographer'=>'Damir Mijailovic','alt'=>'Aceite de oliva en un recipiente de vidrio junto a aceitunas y hojas de olivo, sin marca'),
    array('kind'=>'authority','value'=>'evoo-packaging-format-guide','id'=>'3737651','direct'=>'https://images.pexels.com/photos/3737651/pexels-photo-3737651.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/clear-glass-bottle-with-liquid-3737651/','photographer'=>'cottonbro studio','alt'=>'Botella de vidrio transparente con aceite de oliva, sin etiqueta ni marca visible'),
    array('kind'=>'title_contains','value'=>'Cómo conservar legumbres secas correctamente','id'=>'8287244','direct'=>'https://images.pexels.com/photos/8287244/pexels-photo-8287244.jpeg?auto=compress&cs=tinysrgb&w=2400','page'=>'https://www.pexels.com/photo/clear-glass-jars-with-raw-beans-seeds-and-rice-on-brown-wooden-table-8287244/','photographer'=>'Ron Lach','alt'=>'Tarros de vidrio con alubias, lentejas y otros alimentos secos guardados en despensa'),
);

function mdo_cover_find_post_20260824( string $kind, string $value ): int {
    if ( 'authority' === $kind ) {
        $ids = get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>2,'fields'=>'ids','meta_key'=>'_emdo_authority_key','meta_value'=>$value));
        if ( count($ids) !== 1 ) { throw new RuntimeException('Authority target not unique: ' . $value); }
        return (int) $ids[0];
    }
    if ( 'slug' === $kind ) {
        $post = get_page_by_path($value, OBJECT, 'post');
        if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) { throw new RuntimeException('Slug target missing: ' . $value); }
        return (int) $post->ID;
    }
    if ( 'title_contains' === $kind ) {
        $ids = get_posts(array('post_type'=>'post','post_status'=>'publish','posts_per_page'=>-1,'fields'=>'ids'));
        $matches = array();
        foreach ($ids as $id) {
            if ( false !== stripos((string)get_the_title($id), $value) ) { $matches[] = (int)$id; }
        }
        if ( count($matches) !== 1 ) { throw new RuntimeException('Title target not unique: ' . $value . ' matches=' . count($matches)); }
        return $matches[0];
    }
    throw new RuntimeException('Unknown target kind: ' . $kind);
}

function mdo_cover_all_target_ids_20260824( array $local, array $pexels ): array {
    $ids = array();
    foreach (array_merge($local,$pexels) as $spec) { $ids[] = mdo_cover_find_post_20260824((string)$spec['kind'], (string)$spec['value']); }
    return array_values(array_unique(array_map('intval',$ids)));
}

function mdo_cover_force_thumbnail_20260824( int $post_id, int $attachment_id ): void {
    if ( $post_id <= 0 || $attachment_id <= 0 || 'attachment' !== get_post_type($attachment_id) ) {
        throw new RuntimeException('Invalid featured image assignment.');
    }
    update_post_meta($post_id,'_thumbnail_id',$attachment_id);
    clean_post_cache($post_id);
    if ( (int)get_post_thumbnail_id($post_id) !== $attachment_id ) {
        global $wpdb;
        $meta_id = (int)$wpdb->get_var($wpdb->prepare("SELECT meta_id FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_thumbnail_id' ORDER BY meta_id ASC LIMIT 1",$post_id));
        if ( $meta_id > 0 ) {
            $wpdb->update($wpdb->postmeta,array('meta_value'=>(string)$attachment_id),array('meta_id'=>$meta_id),array('%s'),array('%d'));
            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id=%d AND meta_key='_thumbnail_id' AND meta_id<>%d",$post_id,$meta_id));
        } else {
            $wpdb->insert($wpdb->postmeta,array('post_id'=>$post_id,'meta_key'=>'_thumbnail_id','meta_value'=>(string)$attachment_id),array('%d','%s','%s'));
        }
        clean_post_cache($post_id);
    }
    if ( (int)get_post_thumbnail_id($post_id) !== $attachment_id ) {
        throw new RuntimeException('Featured image assignment did not persist for post ' . $post_id . '.');
    }
}

function mdo_cover_clear_thumbnail_20260824( int $post_id ): void {
    delete_post_meta($post_id,'_thumbnail_id');
    clean_post_cache($post_id);
    if ( (int)get_post_thumbnail_id($post_id) > 0 ) {
        global $wpdb;
        $wpdb->delete($wpdb->postmeta,array('post_id'=>$post_id,'meta_key'=>'_thumbnail_id'),array('%d','%s'));
        clean_post_cache($post_id);
    }
}

function mdo_cover_crop_attachment_20260824( int $post_id, array $spec ): int {
    $asset = (string) $spec['asset'];
    $existing = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_editorial_asset_id','meta_value'=>$asset));
    if ( ! empty($existing) ) {
        $attachment_id = (int)$existing[0];
    } else {
        $source_id = (int)$spec['source'];
        $source = get_attached_file($source_id);
        if ( ! is_string($source) || ! is_file($source) ) { throw new RuntimeException('Source attachment missing for ' . $asset . ': ' . $source_id); }
        [$x,$y,$w,$h] = array_map('intval',(array)$spec['crop']);
        $uploads = wp_upload_dir();
        if ( ! empty($uploads['error']) ) { throw new RuntimeException('Upload dir error: ' . $uploads['error']); }
        $dir = trailingslashit($uploads['basedir']) . 'emdo-editorial-covers';
        if ( ! wp_mkdir_p($dir) ) { throw new RuntimeException('Could not create editorial cover dir'); }
        $dest = trailingslashit($dir) . sanitize_file_name($asset) . '.jpg';

        if ( class_exists('Imagick') ) {
            $im = new Imagick($source);
            if ( method_exists($im,'autoOrient') ) { $im->autoOrient(); }
            $iw = (int)$im->getImageWidth(); $ih = (int)$im->getImageHeight();
            if ($x < 0 || $y < 0 || $w <= 0 || $h <= 0 || $x+$w > $iw || $y+$h > $ih) { throw new RuntimeException('Invalid crop for ' . $asset . ' source=' . $iw . 'x' . $ih); }
            $im->cropImage($w,$h,$x,$y);
            $im->setImagePage(0,0,0,0);
            $im->resizeImage(1600,900,Imagick::FILTER_LANCZOS,1,false);
            $im->setImageFormat('jpeg');
            $im->setImageCompression(Imagick::COMPRESSION_JPEG);
            $im->setImageCompressionQuality(88);
            $im->stripImage();
            if ( ! $im->writeImage($dest) ) { throw new RuntimeException('Could not save crop ' . $asset); }
            $im->clear(); $im->destroy();
        } else {
            $editor = wp_get_image_editor($source);
            if ( is_wp_error($editor) ) { throw new RuntimeException('Image editor failed for ' . $asset . ': ' . $editor->get_error_message()); }
            $result = $editor->crop($x,$y,$w,$h,1600,900);
            if ( is_wp_error($result) ) { throw new RuntimeException('Crop failed for ' . $asset . ': ' . $result->get_error_message()); }
            $saved = $editor->save($dest,'image/jpeg');
            if ( is_wp_error($saved) ) { throw new RuntimeException('Save failed for ' . $asset . ': ' . $saved->get_error_message()); }
        }

        $filetype = wp_check_filetype(basename($dest),null);
        $attachment_id = wp_insert_attachment(array(
            'post_mime_type'=>$filetype['type'] ?: 'image/jpeg',
            'post_title'=>(string)$spec['alt'],
            'post_excerpt'=>'Fotografía de producto de El Mercado de Origen · recorte editorial sin marcas visibles.',
            'post_status'=>'inherit',
        ),$dest,$post_id,true);
        if ( is_wp_error($attachment_id) ) { throw new RuntimeException('Attachment insert failed for ' . $asset . ': ' . $attachment_id->get_error_message()); }
        $attachment_id = (int)$attachment_id;
        $meta = wp_generate_attachment_metadata($attachment_id,$dest);
        wp_update_attachment_metadata($attachment_id,$meta);
        update_post_meta($attachment_id,'_emdo_editorial_asset_id',$asset);
        update_post_meta($attachment_id,'_emdo_editorial_source_attachment',(int)$spec['source']);
        update_post_meta($attachment_id,'_emdo_editorial_brand_safe','1');
        update_post_meta($attachment_id,'_emdo_image_license','El Mercado de Origen owned product photography - editorial crop');
    }
    update_post_meta($attachment_id,'_wp_attachment_image_alt',(string)$spec['alt']);
    mdo_cover_force_thumbnail_20260824($post_id,$attachment_id);
    update_post_meta($post_id,'_emdo_editorial_cover_asset_id',$asset);
    update_post_meta($post_id,'_emdo_editorial_cover_brand_safe','1');
    update_post_meta($post_id,'_emdo_editorial_cover_updated_at',gmdate('c'));
    return $attachment_id;
}

function mdo_cover_pexels_attachment_20260824( int $post_id, array $spec ): int {
    $id = (string)$spec['id'];
    $existing = get_posts(array('post_type'=>'attachment','post_status'=>'inherit','posts_per_page'=>1,'fields'=>'ids','meta_key'=>'_emdo_pexels_photo_id','meta_value'=>$id));
    if ( ! empty($existing) ) {
        $attachment_id = (int)$existing[0];
    } else {
        $attachment_id = media_sideload_image((string)$spec['direct'],$post_id,(string)$spec['alt'],'id');
        if ( is_wp_error($attachment_id) ) { throw new RuntimeException('Pexels ' . $id . ': ' . $attachment_id->get_error_message()); }
        $attachment_id = (int)$attachment_id;
    }
    wp_update_post(array('ID'=>$attachment_id,'post_title'=>(string)$spec['alt'],'post_excerpt'=>'Fotografía: ' . (string)$spec['photographer'] . ' · Pexels.'));
    update_post_meta($attachment_id,'_wp_attachment_image_alt',(string)$spec['alt']);
    update_post_meta($attachment_id,'_emdo_pexels_photo_id',$id);
    update_post_meta($attachment_id,'_emdo_pexels_page',(string)$spec['page']);
    update_post_meta($attachment_id,'_emdo_pexels_photographer',(string)$spec['photographer']);
    update_post_meta($attachment_id,'_emdo_image_license','Pexels License - free personal and commercial use');
    update_post_meta($attachment_id,'_emdo_image_license_url','https://www.pexels.com/license/');
    mdo_cover_force_thumbnail_20260824($post_id,$attachment_id);
    update_post_meta($post_id,'_emdo_editorial_cover_asset_id','pexels-' . $id);
    update_post_meta($post_id,'_emdo_editorial_cover_brand_safe','1');
    update_post_meta($post_id,'_emdo_editorial_cover_updated_at',gmdate('c'));
    return $attachment_id;
}

$target_ids = mdo_cover_all_target_ids_20260824($local,$pexels);

if ( 'backup' === $mode ) {
    if ( '' === $state_file ) { throw new RuntimeException('State file required for backup'); }
    $state = array();
    foreach ($target_ids as $post_id) { $state[(string)$post_id] = (int)get_post_thumbnail_id($post_id); }
    if ( false === file_put_contents($state_file, wp_json_encode($state,JSON_PRETTY_PRINT)) ) { throw new RuntimeException('Could not write cover state'); }
    echo wp_json_encode(array('status'=>'ok','mode'=>'backup','posts'=>count($state),'state_file'=>$state_file),JSON_PRETTY_PRINT) . PHP_EOL;
    return;
}

if ( 'restore' === $mode ) {
    if ( '' === $state_file || ! is_file($state_file) ) { throw new RuntimeException('State file missing for restore'); }
    $state = json_decode((string)file_get_contents($state_file),true);
    if ( ! is_array($state) ) { throw new RuntimeException('Invalid restore state'); }
    foreach ($state as $post_id=>$thumb) {
        $post_id=(int)$post_id; $thumb=(int)$thumb;
        if ($thumb>0) { mdo_cover_force_thumbnail_20260824($post_id,$thumb); } else { mdo_cover_clear_thumbnail_20260824($post_id); }
    }
    echo wp_json_encode(array('status'=>'ok','mode'=>'restore','posts'=>count($state)),JSON_PRETTY_PRINT) . PHP_EOL;
    return;
}

if ( 'apply' !== $mode ) { throw new RuntimeException('Unknown cover mode: ' . $mode); }

$report = array('status'=>'working','local'=>array(),'pexels'=>array());
foreach ($local as $spec) {
    $post_id = mdo_cover_find_post_20260824((string)$spec['kind'],(string)$spec['value']);
    $attachment_id = mdo_cover_crop_attachment_20260824($post_id,$spec);
    $actual = (string)get_post_meta($attachment_id,'_emdo_editorial_asset_id',true);
    if ($actual !== (string)$spec['asset'] || (int)get_post_thumbnail_id($post_id) !== $attachment_id) { throw new RuntimeException('Local cover validation failed: ' . $spec['value']); }
    $report['local'][] = array('target'=>$spec['value'],'post_id'=>$post_id,'attachment_id'=>$attachment_id,'asset'=>$actual,'url'=>(string)get_permalink($post_id),'image_url'=>(string)wp_get_attachment_url($attachment_id));
}
foreach ($pexels as $spec) {
    $post_id = mdo_cover_find_post_20260824((string)$spec['kind'],(string)$spec['value']);
    $attachment_id = mdo_cover_pexels_attachment_20260824($post_id,$spec);
    $actual = (string)get_post_meta($attachment_id,'_emdo_pexels_photo_id',true);
    if ($actual !== (string)$spec['id'] || (int)get_post_thumbnail_id($post_id) !== $attachment_id) { throw new RuntimeException('Pexels cover validation failed: ' . $spec['value']); }
    $report['pexels'][] = array('target'=>$spec['value'],'post_id'=>$post_id,'attachment_id'=>$attachment_id,'pexels_id'=>$actual,'url'=>(string)get_permalink($post_id),'image_url'=>(string)wp_get_attachment_url($attachment_id));
}

$hashes = array();
foreach ($report['local'] as $row) {
    $file = get_attached_file((int)$row['attachment_id']);
    $hash = is_string($file) && is_file($file) ? (string)md5_file($file) : '';
    if ( '' === $hash ) { throw new RuntimeException('Missing local cover file for ' . $row['target']); }
    if ( isset($hashes[$hash]) ) { throw new RuntimeException('Duplicate local cover bytes: ' . $row['target'] . ' / ' . $hashes[$hash]); }
    $hashes[$hash] = $row['target'];
}
$report['status']='ok';
$report['custom_count']=count($report['local']);
$report['pexels_count']=count($report['pexels']);
echo wp_json_encode($report,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT) . PHP_EOL;
