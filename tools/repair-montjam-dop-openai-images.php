<?php
/**
 * Repair the two Montjam D.O.P. Jabugo products whose authoritative image
 * sources were already declared by the original catalogue importer but failed
 * to persist in WordPress. Idempotent and limited to the two known parents.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { throw new RuntimeException( 'WooCommerce unavailable' ); }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function mdo_repair_montjam_media_20260910( string $url, int $post_id, string $alt ): int {
    $existing = get_posts( array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_montjam_source_url',
        'meta_value'     => $url,
    ) );
    if ( $existing ) {
        $id = (int) $existing[0];
        if ( wp_attachment_is_image( $id ) && wp_get_attachment_url( $id ) ) {
            update_post_meta( $id, '_wp_attachment_image_alt', $alt );
            return $id;
        }
    }

    // Also recover a prior sideload that may have succeeded before source meta
    // was written. Match only the exact original filename.
    $filename = wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );
    if ( '' !== $filename ) {
        global $wpdb;
        $like = '%' . $wpdb->esc_like( $filename );
        $candidate = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND meta_value LIKE %s ORDER BY post_id DESC LIMIT 1",
            $like
        ) );
        if ( $candidate > 0 && wp_attachment_is_image( $candidate ) && wp_get_attachment_url( $candidate ) ) {
            update_post_meta( $candidate, '_montjam_source_url', $url );
            update_post_meta( $candidate, '_wp_attachment_image_alt', $alt );
            return $candidate;
        }
    }

    $id = media_sideload_image( $url, $post_id, $alt, 'id' );
    if ( is_wp_error( $id ) ) {
        $response = wp_remote_get( $url, array(
            'timeout'     => 30,
            'redirection' => 5,
            'headers'     => array(
                'User-Agent' => 'Mozilla/5.0 (compatible; ElMercadoDeOrigen/1.0)',
                'Referer'    => 'https://www.iberuss.es/',
                'Accept'     => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            ),
        ) );
        $status = is_wp_error( $response ) ? 0 : (int) wp_remote_retrieve_response_code( $response );
        $body = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_body( $response );
        $content_type = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_header( $response, 'content-type' );
        if ( $status < 200 || $status >= 300 || '' === $body || 0 !== stripos( $content_type, 'image/' ) ) {
            $reason = is_wp_error( $response ) ? $response->get_error_message() : 'HTTP ' . $status . ' ' . $content_type;
            throw new RuntimeException( 'Could not fetch authoritative Montjam image ' . $url . ': ' . $reason );
        }

        $tmp = wp_tempnam( $filename ?: 'montjam-image.jpg' );
        if ( ! $tmp ) { throw new RuntimeException( 'Could not allocate image temp file.' ); }
        if ( false === file_put_contents( $tmp, $body ) ) { @unlink( $tmp ); throw new RuntimeException( 'Could not write image temp file.' ); }
        $id = media_handle_sideload( array( 'name' => $filename ?: 'montjam-image.jpg', 'tmp_name' => $tmp ), $post_id, $alt );
        if ( is_wp_error( $id ) ) {
            @unlink( $tmp );
            throw new RuntimeException( 'Could not persist authoritative Montjam image ' . $url . ': ' . $id->get_error_message() );
        }
    }

    $id = (int) $id;
    if ( $id <= 0 || ! wp_attachment_is_image( $id ) || ! wp_get_attachment_url( $id ) ) {
        throw new RuntimeException( 'Sideloaded attachment is not a valid image for ' . $url );
    }
    update_post_meta( $id, '_montjam_source_url', $url );
    update_post_meta( $id, '_wp_attachment_image_alt', $alt );
    return $id;
}

$targets = array(
    14287 => array(
        'expected_skus' => array( 'MONTJAM-JDOP-600-650','MONTJAM-JDOP-650-700','MONTJAM-JDOP-700-750','MONTJAM-JDOP-750-800' ),
        'images' => array(
            array( 'https://www.iberuss.es/img/products/0000000607_0.jpg', 'Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre brida negra' ),
            array( 'https://www.iberuss.es/img/products/0000000607_1.jpg', 'Jamón de bellota 100% ibérico D.O.P. Jabugo Onofre brida negra - detalle' ),
        ),
    ),
    14301 => array(
        'expected_skus' => array( 'MONTJAM-PDOP-500-550','MONTJAM-PDOP-550-600' ),
        'images' => array(
            array( 'https://www.iberuss.es/img/products/0000000587_0.jpg', 'Paleta de bellota 100% ibérica D.O.P. Jabugo Onofre brida negra' ),
        ),
    ),
);

foreach ( $targets as $parent_id => $spec ) {
    $product = wc_get_product( $parent_id );
    if ( ! $product instanceof WC_Product_Variable ) {
        throw new RuntimeException( 'Expected variable product missing: ' . $parent_id );
    }
    $actual_skus = array();
    foreach ( array_map( 'absint', (array) $product->get_children() ) as $child_id ) {
        $child = wc_get_product( $child_id );
        if ( $child instanceof WC_Product_Variation ) { $actual_skus[] = (string) $child->get_sku(); }
    }
    sort( $actual_skus );
    $expected_skus = array_values( $spec['expected_skus'] );
    sort( $expected_skus );
    if ( $actual_skus !== $expected_skus ) {
        throw new RuntimeException( 'SKU guard failed for parent ' . $parent_id . ': ' . wp_json_encode( $actual_skus ) );
    }

    $attachment_ids = array();
    foreach ( $spec['images'] as $image_spec ) {
        $attachment_ids[] = mdo_repair_montjam_media_20260910( (string) $image_spec[0], $parent_id, (string) $image_spec[1] );
    }
    if ( empty( $attachment_ids ) ) { throw new RuntimeException( 'No images repaired for parent ' . $parent_id ); }

    $product->set_image_id( (int) $attachment_ids[0] );
    $product->set_gallery_image_ids( array_map( 'intval', array_slice( $attachment_ids, 1 ) ) );
    $product->save();
    wc_delete_product_transients( $parent_id );

    $saved = wc_get_product( $parent_id );
    $main_id = $saved instanceof WC_Product ? (int) $saved->get_image_id() : 0;
    $main_url = $main_id > 0 ? wp_get_attachment_url( $main_id ) : false;
    if ( $main_id <= 0 || ! is_string( $main_url ) || 0 !== strpos( $main_url, 'https://' ) ) {
        throw new RuntimeException( 'Image verification failed for parent ' . $parent_id );
    }
    echo 'REPAIRED ' . wp_json_encode( array(
        'parent_id' => $parent_id,
        'name' => $saved->get_name(),
        'image_id' => $main_id,
        'image_url' => $main_url,
        'gallery' => array_map( 'absint', (array) $saved->get_gallery_image_ids() ),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
