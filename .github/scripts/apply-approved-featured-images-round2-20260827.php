<?php
/** Apply five user-approved definitive blog featured images and remove provisional metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$items = array(
    array(
        'slug' => 'tomates-rajados-por-que-se-agrietan-cuando-se-pueden-comer',
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/2_x_Cracked_tomato_2017_A.jpg',
        'filename' => 'tomates-rajados-fructibus.jpg',
        'title' => 'Dos tomates rajados',
        'alt' => 'Dos tomates rojos con grietas visibles alrededor de la zona del pedúnculo',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:2_x_Cracked_tomato_2017_A.jpg',
        'photographer' => 'Fructibus',
        'license' => 'CC0 1.0',
        'license_url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
    ),
    array(
        'slug' => 'smash-burger-vs-hamburguesa-tradicional-que-cambia-carne-coccion',
        'image_url' => 'https://images.pexels.com/photos/30406049/pexels-photo-30406049.jpeg',
        'filename' => 'smash-burger-reza-tavakoli.jpg',
        'title' => 'Smash burger prensada sobre plancha',
        'alt' => 'Cocinero presionando carne de hamburguesa con una prensa sobre una plancha caliente',
        'source_page' => 'https://www.pexels.com/photo/chef-pressing-burgers-on-griddle-with-burger-press-30406049/',
        'photographer' => 'Reza Tavakoli',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'por-que-hamburguesa-se-encoge-cocinar-causas-como-reducirlo',
        'image_url' => 'https://images.pexels.com/photos/37245456/pexels-photo-37245456.jpeg',
        'filename' => 'hamburguesas-parrilla-caio-niceas.jpg',
        'title' => 'Hamburguesas cocinándose en la parrilla',
        'alt' => 'Varias hamburguesas de carne cocinándose sobre una parrilla caliente',
        'source_page' => 'https://www.pexels.com/photo/sizzling-burger-patties-grilling-on-bbq-37245456/',
        'photographer' => 'Caio Niceas',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'color-aove-indica-calidad-aceite-verde-dorado',
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/0/01/Professional_Cobalt_Blue_Olive_Oil_Tasting_Glass.jpg',
        'filename' => 'vaso-cata-aove-azul-merchantadventurer.jpg',
        'title' => 'Vaso azul profesional para cata de aceite de oliva',
        'alt' => 'Vaso azul de cata profesional utilizado para ocultar el color del aceite de oliva',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Professional_Cobalt_Blue_Olive_Oil_Tasting_Glass.jpg',
        'photographer' => 'MerchantAdventurer',
        'license' => 'CC BY-SA 4.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/4.0/',
    ),
    array(
        'slug' => 'primera-presion-en-frio-vs-extraccion-en-frio-que-significan',
        'image_url' => 'https://upload.wikimedia.org/wikipedia/commons/b/b2/Centrifuga_verticale_g1.jpg',
        'filename' => 'centrifuga-extraccion-aceite-giancarlo-dessi.jpg',
        'title' => 'Centrífuga vertical para extracción de aceite de oliva',
        'alt' => 'Aceite de oliva saliendo de una centrífuga vertical durante el proceso de extracción',
        'source_page' => 'https://commons.wikimedia.org/wiki/File:Centrifuga_verticale_g1.jpg',
        'photographer' => 'Giancarlo Dessì',
        'license' => 'CC BY-SA 3.0',
        'license_url' => 'https://creativecommons.org/licenses/by-sa/3.0/',
    ),
);

$report = array('release' => '20260827-featured-round2', 'posts' => array());

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

if ( count($report['posts']) !== 5 ) {
    throw new RuntimeException('Expected five updated posts.');
}

wp_cache_flush();
echo wp_json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
