<?php
/** Apply four user-approved blog featured images with duplicate preflight and image SEO metadata. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

global $wpdb;

$items = array(
    array(
        'selection' => '1B',
        'slug' => 'ajo-germinado-se-puede-comer-diente-brote-verde',
        'pexels_id' => '35037737',
        'image_url' => 'https://images.pexels.com/photos/35037737/pexels-photo-35037737.jpeg?cs=srgb&dl=pexels-jahratreza-35037737.jpg&fm=jpg',
        'filename' => 'ajo-germinado-brote-verde-jahra-tasfia-reza.jpg',
        'title' => 'Ajo germinado con brotes verdes',
        'alt' => 'Ajo germinado con brotes verdes creciendo desde el bulbo',
        'source_page' => 'https://www.pexels.com/photo/close-up-of-garlic-plant-growing-indoors-35037737/',
        'photographer' => 'Jahra Tasfia Reza',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'selection' => '2A',
        'slug' => 'hay-que-lavar-carne-antes-cocinar-por-que-no',
        'pexels_id' => '7790020',
        'image_url' => 'https://images.pexels.com/photos/7790020/pexels-photo-7790020.jpeg?cs=srgb&dl=pexels-babydov-7790020.jpg&fm=jpg',
        'filename' => 'lavar-carne-pollo-crudo-agua-ivan-babydov.jpg',
        'title' => 'Lavado de pollo crudo con agua',
        'alt' => 'Persona lavando pollo crudo con agua en un recipiente de acero inoxidable',
        'source_page' => 'https://www.pexels.com/photo/a-person-cleaning-chicken-meat-with-water-from-the-stainless-steel-basin-7790020/',
        'photographer' => 'Ivan Babydov',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'selection' => '4A',
        'slug' => 'conservas-tarro-vidrio-vs-lata-diferencias-conservacion-uso',
        'pexels_id' => '9898352',
        'image_url' => 'https://images.pexels.com/photos/9898352/pexels-photo-9898352.jpeg?cs=srgb&dl=pexels-ron-lach-9898352.jpg&fm=jpg',
        'filename' => 'conservas-tarro-vidrio-vs-lata-ron-lach.jpg',
        'title' => 'Conservas en tarros de vidrio y latas',
        'alt' => 'Latas de conserva y tarros de vidrio colocados juntos en una caja',
        'source_page' => 'https://www.pexels.com/photo/a-canned-food-in-the-box-9898352/',
        'photographer' => 'Ron Lach',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
    array(
        'selection' => '5A',
        'slug' => 'verduras-conserva-pierden-nutrientes-frente-frescas',
        'pexels_id' => '33984951',
        'image_url' => 'https://images.pexels.com/photos/33984951/pexels-photo-33984951.jpeg?cs=srgb&dl=pexels-tivasee-17374727-33984951.jpg&fm=jpg',
        'filename' => 'verduras-frescas-vs-conserva-tivasee.jpg',
        'title' => 'Verduras frescas y verduras en conserva',
        'alt' => 'Tomates frescos junto a verduras en conserva sobre una mesa',
        'source_page' => 'https://www.pexels.com/photo/fresh-tomatoes-and-canned-vegetables-on-rustic-table-33984951/',
        'photographer' => 'TIVASEE .',
        'license' => 'Pexels License',
        'license_url' => 'https://www.pexels.com/license/',
    ),
);

$report = array(
    'release' => '20260828-featured-round5',
    'duplicate_preflight' => array(),
    'posts' => array(),
);

// Preflight all four before making any changes. This prevents partial deployment if a selected photo is already used elsewhere.
foreach ( $items as $idx => $item ) {
    $post = get_page_by_path( $item['slug'], OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) {
        throw new RuntimeException( 'Post not found: ' . $item['slug'] );
    }
    $post_id = (int) $post->ID;
    $items[$idx]['post_id'] = $post_id;

    $candidate_ids = array();

    $exact_source = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'meta_key' => '_mdo_source_page',
        'meta_value' => $item['source_page'],
    ));
    foreach ( $exact_source as $id ) { $candidate_ids[] = (int) $id; }

    $like = '%' . $wpdb->esc_like( $item['pexels_id'] ) . '%';
    $meta_matches = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT pm.post_id
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE p.post_type = 'attachment'
           AND pm.meta_key IN ('_mdo_source_page','_mdo_source_url')
           AND pm.meta_value LIKE %s",
        $like
    ) );
    foreach ( $meta_matches as $id ) { $candidate_ids[] = (int) $id; }
    $candidate_ids = array_values( array_unique( array_filter( $candidate_ids ) ) );

    $reusable_attachment = 0;
    $conflicts = array();
    foreach ( $candidate_ids as $attachment_id ) {
        $used_by_thumbnail = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_thumbnail_id' AND meta_value = %s AND post_id <> %d",
            (string) $attachment_id,
            $post_id
        ) );
        $used_by_approved = $wpdb->get_col( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta}
             WHERE meta_key = '_emdo_editorial_image_approved_id' AND meta_value = %s AND post_id <> %d",
            (string) $attachment_id,
            $post_id
        ) );
        $used_elsewhere = array_values( array_unique( array_map( 'intval', array_merge( $used_by_thumbnail, $used_by_approved ) ) ) );

        if ( ! empty( $used_elsewhere ) ) {
            $conflicts[] = array('attachment_id' => $attachment_id, 'used_by_posts' => $used_elsewhere);
        } else {
            $reusable_attachment = $attachment_id;
        }
    }

    if ( ! empty( $conflicts ) ) {
        throw new RuntimeException( 'Duplicate image already used by another post for Pexels ' . $item['pexels_id'] . ': ' . wp_json_encode( $conflicts ) );
    }

    $items[$idx]['reusable_attachment'] = $reusable_attachment;
    $report['duplicate_preflight'][] = array(
        'slug' => $item['slug'],
        'pexels_id' => $item['pexels_id'],
        'existing_attachment_candidates' => $candidate_ids,
        'reusable_attachment' => $reusable_attachment,
        'conflicts' => array(),
    );
}

foreach ( $items as $item ) {
    $post_id = (int) $item['post_id'];
    $attachment_id = (int) $item['reusable_attachment'];
    $reused_existing = $attachment_id > 0;

    if ( ! $attachment_id ) {
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

    wp_update_post(array(
        'ID' => $attachment_id,
        'post_title' => $item['title'],
    ));
    update_post_meta( $attachment_id, '_wp_attachment_image_alt', $item['alt'] );
    update_post_meta( $attachment_id, '_mdo_source_url', $item['image_url'] );
    update_post_meta( $attachment_id, '_mdo_source_page', $item['source_page'] );
    update_post_meta( $attachment_id, '_mdo_photographer', $item['photographer'] );
    update_post_meta( $attachment_id, '_mdo_license', $item['license'] );
    update_post_meta( $attachment_id, '_mdo_license_url', $item['license_url'] );
    update_post_meta( $attachment_id, '_mdo_pexels_id', $item['pexels_id'] );

    set_post_thumbnail( $post_id, $attachment_id );
    update_post_meta( $post_id, '_emdo_editorial_image_approved_id', $attachment_id );
    delete_post_meta( $post_id, '_emdo_editorial_image_approved_pexels_id' );
    update_post_meta( $post_id, '_emdo_editorial_cover_brand_safe', '1' );
    delete_post_meta( $post_id, '_emdo_uses_default_featured' );
    delete_post_meta( $post_id, '_emdo_default_featured_hash' );
    delete_post_meta( $post_id, '_emdo_default_featured_updated_at' );
    delete_post_meta( $post_id, '_emdo_editorial_cover_asset_id' );
    clean_post_cache( $post_id );

    $thumbnail_id = (int) get_post_thumbnail_id( $post_id );
    $approved_id = (int) get_post_meta( $post_id, '_emdo_editorial_image_approved_id', true );
    $uses_default = (string) get_post_meta( $post_id, '_emdo_uses_default_featured', true );
    $cover_asset = (string) get_post_meta( $post_id, '_emdo_editorial_cover_asset_id', true );
    $attachment_url = (string) wp_get_attachment_url( $attachment_id );
    $attachment_alt = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
    $attachment_title = (string) get_the_title( $attachment_id );
    $source_page = (string) get_post_meta( $attachment_id, '_mdo_source_page', true );
    $photographer = (string) get_post_meta( $attachment_id, '_mdo_photographer', true );
    $license = (string) get_post_meta( $attachment_id, '_mdo_license', true );
    $expected_stem = pathinfo( $item['filename'], PATHINFO_FILENAME );
    $actual_basename = basename( (string) wp_parse_url( $attachment_url, PHP_URL_PATH ) );

    if (
        $thumbnail_id !== $attachment_id ||
        $approved_id !== $attachment_id ||
        $uses_default !== '' ||
        $cover_asset !== '' ||
        $attachment_url === '' ||
        $attachment_alt !== $item['alt'] ||
        $attachment_title !== $item['title'] ||
        $source_page !== $item['source_page'] ||
        $photographer !== $item['photographer'] ||
        $license !== $item['license'] ||
        strpos( $actual_basename, $expected_stem ) === false
    ) {
        throw new RuntimeException( 'Featured image/SEO verification failed for: ' . $item['slug'] );
    }

    $report['posts'][] = array(
        'selection' => $item['selection'],
        'slug' => $item['slug'],
        'post_id' => $post_id,
        'attachment_id' => $attachment_id,
        'thumbnail_id' => $thumbnail_id,
        'approved_id' => $approved_id,
        'uses_default' => $uses_default,
        'cover_asset' => $cover_asset,
        'attachment_url' => $attachment_url,
        'filename' => $actual_basename,
        'title' => $attachment_title,
        'alt' => $attachment_alt,
        'source_page' => $source_page,
        'photographer' => $photographer,
        'license' => $license,
        'pexels_id' => $item['pexels_id'],
        'reused_existing' => $reused_existing,
        'permalink' => get_permalink( $post_id ),
    );
}

if ( count( $report['posts'] ) !== 4 || count( $report['duplicate_preflight'] ) !== 4 ) {
    throw new RuntimeException( 'Expected four updated posts and four duplicate checks.' );
}

wp_cache_flush();
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
