<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { throw new RuntimeException( 'WooCommerce unavailable.' ); }

$targets = array(
    14287 => array( 'bad_main'=>14447, 'bad_gallery'=>array( 14448 ) ),
    14301 => array( 'bad_main'=>14449, 'bad_gallery'=>array() ),
);

foreach ( $targets as $product_id => $spec ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product ) { throw new RuntimeException( 'Missing product ' . $product_id ); }

    $changed = false;
    if ( (int) $product->get_image_id() === (int) $spec['bad_main'] ) {
        $product->set_image_id( 0 );
        $changed = true;
    }

    $gallery = array_map( 'absint', (array) $product->get_gallery_image_ids() );
    $clean_gallery = array_values( array_diff( $gallery, array_map( 'absint', (array) $spec['bad_gallery'] ) ) );
    if ( $clean_gallery !== $gallery ) {
        $product->set_gallery_image_ids( $clean_gallery );
        $changed = true;
    }

    if ( $changed ) {
        $product->save();
        wc_delete_product_transients( $product_id );
    }

    $saved = wc_get_product( $product_id );
    echo 'MONTJAM ' . wp_json_encode(
        array(
            'id'=>$product_id,
            'image_id'=>$saved instanceof WC_Product ? (int) $saved->get_image_id() : -1,
            'gallery'=>$saved instanceof WC_Product ? array_map( 'absint', (array) $saved->get_gallery_image_ids() ) : array(),
            'changed'=>$changed,
        ),
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
    ) . "\n";
}
