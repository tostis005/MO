<?php
/**
 * Repair four approved blog featured images that still carry provisional-cover metadata.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

$items = array(
    array(
        'slug' => 'cebolla-brotada-se-puede-comer-bulbo-brote',
        'image_url' => 'https://images.pexels.com/photos/11712982/pexels-photo-11712982.jpeg?cs=srgb&dl=pexels-mickhaupt-11712982.jpg&fm=jpg',
        'title' => 'Cebolla brotada con brotes verdes',
        'alt' => 'Cebolla con brotes verdes sobre una superficie de tela clara',
        'source_page' => 'https://www.pexels.com/photo/sprouts-on-onion-11712982/',
        'photographer' => 'Mick Haupt',
        'license' => 'Pexels License',
    ),
    array(
        'slug' => 'por-que-cebolla-hace-llorar-metodos-reducir-irritacion',
        'image_url' => 'https://images.pexels.com/photos/10432426/pexels-photo-10432426.jpeg?cs=srgb&dl=pexels-rdne-10432426.jpg&fm=jpg',
        'title' => 'Corte de cebolla morada con cuchillo',
        'alt' => 'Manos cortando una cebolla morada sobre una tabla de madera',
        'source_page' => 'https://www.pexels.com/photo/woman-cutting-onion-with-knife-10432426/',
        'photographer' => 'RDNE Stock project',
        'license' => 'Pexels License',
    ),
    array(
        'slug' => 'patata-cortada-se-pone-negra-por-que-oxidacion-como-evitarla',
        'image_url' => 'https://images.pexels.com/photos/5377332/pexels-photo-5377332.jpeg?cs=srgb&dl=pexels-n-voitkevich-5377332.jpg&fm=jpg',
        'title' => 'Patata cruda cortada en rodajas',
        'alt' => 'Patata cruda parcialmente cortada en rodajas sobre una tabla de madera',
        'source_page' => 'https://www.pexels.com/photo/close-up-shot-of-a-potato-5377332/',
        'photographer' => 'Nataliya Vaitkevich',
        'license' => 'Pexels License',
    ),
    array(
        'slug' => 'cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias',
        'image_url' => 'https://images.pexels.com/photos/8108012/pexels-photo-8108012.jpeg?cs=srgb&dl=pexels-mart-production-8108012.jpg&fm=jpg',
        'title' => 'Legumbres secas con recipientes de medida',
        'alt' => 'Garbanzos, lentejas y otras legumbres secas en boles y recipientes de medida',
        'source_page' => 'https://www.pexels.com/photo/assorted-ingredients-in-bowls-jars-and-measuring-cups-8108012/',
        'photographer' => 'MART PRODUCTION',
        'license' => 'Pexels License',
    ),
);

$report = array('release' => '20260827-approved-featured-repair', 'posts' => array());

foreach ( $items as $item ) {
    $post = get_page_by_path( $item['slug'], OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) {
        throw new RuntimeException( 'Post not found: ' . $item['slug'] );
    }
    $post_id = (int) $post->ID;

    $attachments = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_mdo_source_url',
        'meta_value' => $item['image_url'],
    ));
    if ( empty( $attachments ) ) {
        throw new RuntimeException( 'Approved attachment not found for: ' . $item['slug'] );
    }
    $attachment_id = (int) $attachments[0];

    wp_update_post(array('ID' => $attachment_id, 'post_title' => $item['title']));
    update_post_meta($attachment_id, '_wp_attachment_image_alt', $item['alt']);
    update_post_meta($attachment_id, '_mdo_source_page', $item['source_page']);
    update_post_meta($attachment_id, '_mdo_photographer', $item['photographer']);
    update_post_meta($attachment_id, '_mdo_license', $item['license']);

    // Assign both the native WordPress featured image and the editorial-approved image.
    set_post_thumbnail($post_id, $attachment_id);
    update_post_meta($post_id, '_emdo_editorial_image_approved_id', $attachment_id);
    delete_post_meta($post_id, '_emdo_editorial_image_approved_pexels_id');
    update_post_meta($post_id, '_emdo_editorial_cover_brand_safe', '1');

    // A definitive approved image must leave the provisional-cover pool completely.
    delete_post_meta($post_id, '_emdo_uses_default_featured');
    delete_post_meta($post_id, '_emdo_default_featured_hash');
    delete_post_meta($post_id, '_emdo_default_featured_updated_at');
    delete_post_meta($post_id, '_emdo_editorial_cover_asset_id');

    clean_post_cache($post_id);

    $final_thumbnail = (int) get_post_thumbnail_id($post_id);
    $final_approved = (int) get_post_meta($post_id, '_emdo_editorial_image_approved_id', true);
    $uses_default = (string) get_post_meta($post_id, '_emdo_uses_default_featured', true);
    $cover_asset = (string) get_post_meta($post_id, '_emdo_editorial_cover_asset_id', true);
    $attachment_url = (string) wp_get_attachment_url($attachment_id);

    if ($final_thumbnail !== $attachment_id || $final_approved !== $attachment_id || $uses_default !== '' || $cover_asset !== '') {
        throw new RuntimeException('Featured-image repair verification failed for: ' . $item['slug']);
    }
    if ($attachment_url === '') {
        throw new RuntimeException('Attachment URL missing for: ' . $item['slug']);
    }

    $report['posts'][] = array(
        'slug' => $item['slug'],
        'post_id' => $post_id,
        'attachment_id' => $attachment_id,
        'attachment_url' => $attachment_url,
        'alt' => $item['alt'],
        'permalink' => get_permalink($post_id),
        'thumbnail_id' => $final_thumbnail,
        'approved_id' => $final_approved,
        'uses_default' => $uses_default,
        'cover_asset' => $cover_asset,
    );
}

wp_cache_flush();
echo wp_json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
