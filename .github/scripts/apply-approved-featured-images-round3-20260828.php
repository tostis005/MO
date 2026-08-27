<?php
/** Apply four user-approved definitive blog featured images and remove provisional metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$items = array(
    array(
        'slug' => 'que-parte-puerro-se-come-como-limpiarlo-quitar-tierra',
        'image_url' => 'https://images.pexels.com/photos/4965038/pexels-photo-4965038.jpeg',
        'filename' => 'puerro-partes-larisa-p.jpg',
        'title' => 'Puerro entero, recortado y cortado',
        'alt' => 'Tres presentaciones de un puerro: entero, recortado y cortado en rodajas sobre fondo blanco',
        'source_page' => 'https://www.pexels.com/photo/fresh-organic-leeks-on-white-surface-4965038/',
        'photographer' => 'Larisa P.',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'hay-que-salar-berenjena-quitar-amargor-cuando-sirve',
        'image_url' => 'https://images.pexels.com/photos/8862214/pexels-photo-8862214.jpeg',
        'filename' => 'berenjena-cortada-ron-lach.jpg',
        'title' => 'Berenjena cortada sobre una tabla',
        'alt' => 'Manos cortando una berenjena fresca en rodajas sobre una tabla de cocina',
        'source_page' => 'https://www.pexels.com/photo/a-person-cutting-an-eggplant-8862214/',
        'photographer' => 'Ron Lach',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'como-lavar-verduras-correctamente-agua-vinagre-bicarbonato',
        'image_url' => 'https://images.pexels.com/photos/3872435/pexels-photo-3872435.jpeg',
        'filename' => 'verduras-lavandose-polina-tankilevitch.jpg',
        'title' => 'Verduras frescas lavándose bajo agua corriente',
        'alt' => 'Brócoli, pimiento, apio, pepino y zanahoria lavándose bajo agua corriente en un bol metálico',
        'source_page' => 'https://www.pexels.com/photo/fresh-vegetables-under-running-water-in-metal-bowl-3872435/',
        'photographer' => 'Polina Tankilevitch',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'tomate-harinoso-por-que-ocurre-textura-como-evitarlo',
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/6/62/Close-up_of_sliced_tomatoes.jpg',
        'filename' => 'tomates-cortados-wiseman42.jpg',
        'title' => 'Primer plano de tomates cortados',
        'alt' => 'Primer plano de varios tomates rojos cortados mostrando la pulpa interior y las semillas',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Close-up_of_sliced_tomatoes.jpg',
        'photographer' => 'WiseMan42',
        'license' => 'CC0 1.0',
        'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
    ),
);

$report = array('release' => '20260828-featured-round3', 'posts' => array());

foreach ( $items as $item ) {
    $post = get_page_by_path( $item['slug'], OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) {
        throw new RuntimeException( 'Post not found: ' . $item['slug'] );
    }
    $post_id = (int) $post->ID;

    $existing = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_mdo_source_page',
        'meta_value' => $item['source_page'],
    ));

    if ( ! empty( $existing ) ) {
        $attachment_id = (int) $existing[0];
    } else {
        $tmp = download_url( $item['image_url'], 120 );
        if ( is_wp_error( $tmp ) ) {
            throw new RuntimeException( 'Download failed for ' . $item['slug'] . ': ' . $tmp->get_error_message() );
        }
        $file = array(
            'name' => $item['filename'],
            'tmp_name' => $tmp,
        );
        $attachment_id = media_handle_sideload( $file, $post_id, $item['title'] );
        if ( is_wp_error( $attachment_id ) ) {
            @unlink( $tmp );
            throw new RuntimeException( 'Media import failed for ' . $item['slug'] . ': ' . $attachment_id->get_error_message() );
        }
        $attachment_id = (int) $attachment_id;
    }

    wp_update_post(array('ID' => $attachment_id, 'post_title' => $item['title']));
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $item['alt']);
    update_post_meta($attachment_id, '_mdo_source_url', $item['image_url']);
    update_post_meta($attachment_id, '_mdo_source_page', $item['source_page']);
    update_post_meta($attachment_id, '_mdo_photographer', $item['photographer']);
    update_post_meta($attachment_id, '_mdo_license', $item['license']);
    update_post_meta($attachment_id, '_mdo_license_url', $item['license_url']);

    set_post_thumbnail($post_id, $attachment_id);
    update_post_meta($post_id, '_emdo_editorial_image_approved_id', $attachment_id);
    delete_post_meta($post_id, '_emdo_editorial_image_approved_pexels_id');
    update_post_meta($post_id, '_emdo_editorial_cover_brand_safe', '1');

    delete_post_meta($post_id, '_emdo_uses_default_featured');
    delete_post_meta($post_id, '_emdo_default_featured_hash');
    delete_post_meta($post_id, '_emdo_default_featured_updated_at');
    delete_post_meta($post_id, '_emdo_editorial_cover_asset_id');
    clean_post_cache($post_id);

    $thumbnail_id = (int) get_post_thumbnail_id($post_id);
    $approved_id = (int) get_post_meta($post_id, '_emdo_editorial_image_approved_id', true);
    $uses_default = (string) get_post_meta($post_id, '_emdo_uses_default_featured', true);
    $cover_asset = (string) get_post_meta($post_id, '_emdo_editorial_cover_asset_id', true);
    $attachment_url = (string) wp_get_attachment_url($attachment_id);

    if ($thumbnail_id !== $attachment_id || $approved_id !== $attachment_id || $uses_default !== '' || $cover_asset !== '' || $attachment_url === '') {
        throw new RuntimeException('Featured image verification failed for: ' . $item['slug']);
    }

    $report['posts'][] = array(
        'slug' => $item['slug'],
        'post_id' => $post_id,
        'attachment_id' => $attachment_id,
        'thumbnail_id' => $thumbnail_id,
        'approved_id' => $approved_id,
        'uses_default' => $uses_default,
        'cover_asset' => $cover_asset,
        'attachment_url' => $attachment_url,
        'source_page' => $item['source_page'],
        'photographer' => $item['photographer'],
        'license' => $item['license'],
        'permalink' => get_permalink($post_id),
    );
}

if ( count($report['posts']) !== 4 ) {
    throw new RuntimeException('Expected four updated posts.');
}

wp_cache_flush();
echo wp_json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
