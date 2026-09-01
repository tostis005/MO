<?php
/** Apply six user-approved definitive blog featured images and remove provisional metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$items = array(
    array(
        'slug' => 'aceite-oliva-o-girasol-para-freir-cual-elegir',
        'image_url' => 'https://images.pexels.com/photos/9431741/pexels-photo-9431741.jpeg',
        'filename' => 'fritura-aceite-muhammad-khawar-nazir.jpg',
        'title' => 'Croquetas friéndose en aceite',
        'alt' => 'Croquetas doradas friéndose en una sartén con aceite caliente y burbujas visibles',
        'source_page' => 'https://www.pexels.com/photo/preparing-food-on-frying-pan-9431741/',
        'photographer' => 'Muhammad Khawar Nazir',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'cuanto-hierro-tiene-carne-ternera',
        'image_url' => 'https://images.pexels.com/photos/5892851/pexels-photo-5892851.jpeg',
        'filename' => 'ternera-cruda-bela-bleier.jpg',
        'title' => 'Corte de ternera cruda con romero',
        'alt' => 'Primer plano de un corte de carne de ternera cruda con romero y granos de pimienta sobre madera',
        'source_page' => 'https://www.pexels.com/photo/close-up-photo-of-fresh-meat-slice-5892851/',
        'photographer' => 'Béla Bleier',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'cuando-echar-sal-garbanzos-lentejas-alubias-endurece-legumbres',
        'image_url' => 'https://images.pexels.com/photos/7656561/pexels-photo-7656561.jpeg',
        'filename' => 'garbanzos-bol-cup-of-couple-7656561.jpg',
        'title' => 'Garbanzos en un bol sobre fondo neutro',
        'alt' => 'Garbanzos cocidos en un bol beige sobre un fondo claro y minimalista',
        'source_page' => 'https://www.pexels.com/photo/a-close-up-shot-of-chickpeas-in-a-bowl-7656561/',
        'photographer' => 'Cup of Couple',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar',
        'image_url' => 'https://images.pexels.com/photos/7656564/pexels-photo-7656564.jpeg',
        'filename' => 'garbanzos-cenital-cup-of-couple-7656564.jpg',
        'title' => 'Garbanzos vistos desde arriba',
        'alt' => 'Vista cenital de garbanzos en un bol, sin envases ni marcas visibles',
        'source_page' => 'https://www.pexels.com/photo/close-up-photo-chickpeas-in-a-bowl-7656564/',
        'photographer' => 'Cup of Couple',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'conserva-vs-semiconserva-diferencia-por-que-necesita-frio',
        'image_url' => 'https://images.pexels.com/photos/9797030/pexels-photo-9797030.jpeg',
        'filename' => 'sardinas-conserva-towfiqu-barbhuiya.jpg',
        'title' => 'Sardinas en conserva con aceite',
        'alt' => 'Lata abierta de sardinas en aceite junto a un tenedor, sin etiquetas ni marcas comerciales visibles',
        'source_page' => 'https://www.pexels.com/photo/close-up-shot-of-sardines-in-a-can-9797030/',
        'photographer' => 'Towfiqu barbhuiya',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'slug' => 'que-verduras-tienen-mas-fibra',
        'image_url' => 'https://images.pexels.com/photos/29959934/pexels-photo-29959934.jpeg',
        'filename' => 'verduras-frescas-barbara-medic.jpg',
        'title' => 'Verduras frescas sobre mármol',
        'alt' => 'Composición cenital de verduras frescas y hojas verdes sobre una superficie de mármol',
        'source_page' => 'https://www.pexels.com/photo/fresh-veggies-and-greens-flat-lay-on-marble-29959934/',
        'photographer' => 'Barbara Medic',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
);

$report = array('release' => '20260901-featured-approved', 'posts' => array());

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
    update_post_meta($post_id, '_emdo_editorial_image_approved_pexels_id', preg_replace('/\D+/', '', basename(dirname($item['image_url']))));
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

if ( count($report['posts']) !== 6 ) {
    throw new RuntimeException('Expected six updated posts.');
}

wp_cache_flush();
echo wp_json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
