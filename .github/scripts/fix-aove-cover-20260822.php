<?php
/** Replace the one retired AOVE cover that blocks the global editorial-image certificate. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$slug = 'que-significa-aceite-oliva-virgen-extra';
$img = array(
    'id' => '18465070',
    'direct' => 'https://images.pexels.com/photos/18465070/pexels-photo-18465070.jpeg?auto=compress&cs=tinysrgb&w=2400',
    'page' => 'https://www.pexels.com/photo/olives-growing-on-tree-18465070/',
    'photographer' => 'Ant Armada',
    'alt_es' => 'Aceitunas creciendo en el olivo, contexto natural del aceite de oliva virgen extra sin envases ni marcas',
    'alt_en' => 'Olives growing on the tree as natural EVOO context with no packaging or branding',
);

$post = get_page_by_path( $slug, OBJECT, 'post' );
if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
    throw new RuntimeException( 'Published AOVE guide not found: ' . $slug );
}
$post_id = (int) $post->ID;
$ids = get_posts( array(
    'post_type'=>'attachment', 'post_status'=>'inherit', 'posts_per_page'=>1, 'fields'=>'ids',
    'meta_key'=>'_emdo_pexels_photo_id', 'meta_value'=>$img['id'],
) );
$attachment_id = ! empty( $ids ) ? (int) $ids[0] : 0;
if ( $attachment_id <= 0 ) {
    $attachment_id = media_sideload_image( $img['direct'], $post_id, $img['alt_es'], 'id' );
    if ( is_wp_error( $attachment_id ) ) { throw new RuntimeException( $attachment_id->get_error_message() ); }
    $attachment_id = (int) $attachment_id;
}

wp_update_post( array(
    'ID'=>$attachment_id,
    'post_title'=>wp_strip_all_tags( $img['alt_es'] ),
    'post_excerpt'=>'Fotografía: ' . $img['photographer'] . ' · Pexels.',
) );
update_post_meta( $attachment_id, '_wp_attachment_image_alt', $img['alt_es'] );
update_post_meta( $attachment_id, '_en_US_attachment_alt', $img['alt_en'] );
update_post_meta( $attachment_id, '_emdo_pexels_photo_id', $img['id'] );
update_post_meta( $attachment_id, '_emdo_pexels_page', $img['page'] );
update_post_meta( $attachment_id, '_emdo_pexels_photographer', $img['photographer'] );
update_post_meta( $attachment_id, '_emdo_image_license', 'Pexels License - free personal and commercial use' );
update_post_meta( $attachment_id, '_emdo_image_license_url', 'https://www.pexels.com/license/' );

$meta = wp_get_attachment_metadata( $attachment_id );
if ( (int)($meta['width'] ?? 0) < 900 || (int)($meta['height'] ?? 0) < 550 ) {
    throw new RuntimeException( 'AOVE replacement image too small.' );
}
set_post_thumbnail( $post_id, $attachment_id );
update_post_meta( $post_id, '_emdo_editorial_image_approved_id', $attachment_id );
update_post_meta( $post_id, '_emdo_editorial_image_approved_pexels_id', $img['id'] );
update_post_meta( $post_id, '_emdo_editorial_image_approved_at', gmdate('c') );
update_post_meta( $post_id, '_emdo_editorial_image_override', '0.10.264' );

$actual = (string) get_post_meta( (int)get_post_thumbnail_id( $post_id ), '_emdo_pexels_photo_id', true );
if ( $actual !== $img['id'] ) { throw new RuntimeException( 'AOVE replacement verification failed.' ); }

echo wp_json_encode( array(
    'status'=>'ok', 'post_id'=>$post_id, 'attachment_id'=>$attachment_id,
    'pexels_id'=>$img['id'], 'url'=>(string)get_permalink($post_id),
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
