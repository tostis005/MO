<?php
/** Apply four user-approved definitive blog featured images and remove provisional metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$items = array(
    array(
        'slug' => 'atemperar-carne-antes-cocinar-hace-falta-cuanto-tiempo',
        'image_url' => 'https://images.pexels.com/photos/17481109/pexels-photo-17481109.jpeg?cs=srgb&dl=pexels-wicho-17481109.jpg&fm=jpg',
        'filename' => 'carne-cruda-reposando-luis-merlos-vega.jpg',
        'title' => 'Carne cruda reposando sobre tabla de madera',
        'alt' => 'Chuleta de carne cruda sobre una tabla de madera antes de cocinar',
        'source_page' => 'https://www.pexels.com/photo/raw-steak-on-cutting-board-17481109/',
        'photographer' => 'Luis Merlos Vega',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'sellar-carne-encierra-jugos-mito-que-ocurre',
        'image_url' => 'https://images.pexels.com/photos/6430690/pexels-photo-6430690.jpeg?cs=srgb&dl=pexels-skylar-kang-6430690.jpg&fm=jpg',
        'filename' => 'carne-sellandose-sarten-skylar-kang.jpg',
        'title' => 'Carne dorándose en una sartén caliente',
        'alt' => 'Filetes de carne dorándose intensamente en una sartén caliente',
        'source_page' => 'https://www.pexels.com/photo/close-up-photo-of-meat-on-frying-pan-6430690/',
        'photographer' => 'Skylar Kang',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'liquido-cobertura-conservas-vegetales-que-es-para-que-sirve',
        'image_url' => 'https://images.pexels.com/photos/9005947/pexels-photo-9005947.jpeg?cs=srgb&dl=pexels-maria-verkhoturtseva-21177529-9005947.jpg&fm=jpg',
        'filename' => 'liquido-cobertura-conservas-maria-verkhoturtseva.jpg',
        'title' => 'Vertido de líquido en un tarro de verduras',
        'alt' => 'Persona vertiendo líquido de cobertura en un tarro de vidrio lleno de verduras',
        'source_page' => 'https://www.pexels.com/photo/person-pouring-water-in-glass-jar-of-vegetables-9005947/',
        'photographer' => 'Maria Verkhoturtseva',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'por-que-carne-se-pega-sarten-cuando-darle-vuelta',
        'image_url' => 'https://images.pexels.com/photos/4253311/pexels-photo-4253311.jpeg?cs=srgb&dl=pexels-cottonbro-4253311.jpg&fm=jpg',
        'filename' => 'carne-sarten-pinzas-cottonbro.jpg',
        'title' => 'Cocinero manipulando carne en una sartén',
        'alt' => 'Cocinero sujetando con pinzas un filete que se cocina en una sartén caliente',
        'source_page' => 'https://www.pexels.com/photo/person-cooking-on-black-pan-4253311/',
        'photographer' => 'cottonbro studio',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
);

$report = array('release' => '20260828-featured-round4', 'posts' => array());

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
        $file = array('name' => $item['filename'], 'tmp_name' => $tmp);
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
