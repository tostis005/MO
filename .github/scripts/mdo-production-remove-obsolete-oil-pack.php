<?php
/** Remove the obsolete 4 x 5L oil-pack option from product 1056 without deleting historical data. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
$siteurl = rtrim( (string) get_option( 'siteurl' ), '/' );
if ( ! preg_match( '~^https?://(www\.)?elmercadodeorigen\.com$~i', $siteurl ) ) {
    fwrite( STDERR, "Refusing non-production site: {$siteurl}\n" );
    exit( 2 );
}
if ( ! function_exists( 'wc_get_product' ) ) { fwrite( STDERR, "WooCommerce unavailable.\n" ); exit( 3 ); }
$product = wc_get_product( 1056 );
if ( ! $product instanceof WC_Product_Variable ) { fwrite( STDERR, "Product 1056 is not variable.\n" ); exit( 4 ); }
$target_slug = 'pack-de-4-unidades-envase-de-5l-de-pet-regalo-frasca-egipcia-250ml';
$target_term = get_term_by( 'slug', $target_slug, 'pa_tamano' );
if ( ! $target_term || is_wp_error( $target_term ) ) { fwrite( STDERR, "Target size term not found.\n" ); exit( 5 ); }
$changed_variations = array();
foreach ( $product->get_children() as $variation_id ) {
    $variation = wc_get_product( $variation_id );
    if ( ! $variation ) { continue; }
    $attrs = $variation->get_attributes();
    if ( isset( $attrs['pa_tamano'] ) && $target_slug === $attrs['pa_tamano'] ) {
        if ( 'private' !== get_post_status( $variation_id ) ) {
            wp_update_post( array( 'ID' => $variation_id, 'post_status' => 'private' ) );
        }
        $variation->set_stock_status( 'outofstock' );
        $variation->save();
        $changed_variations[] = $variation_id;
    }
}
$attributes = $product->get_attributes();
if ( isset( $attributes['pa_tamano'] ) && $attributes['pa_tamano'] instanceof WC_Product_Attribute ) {
    $size_attr = $attributes['pa_tamano'];
    $options = array_map( 'intval', $size_attr->get_options() );
    $new_options = array_values( array_diff( $options, array( (int) $target_term->term_id ) ) );
    if ( $new_options !== $options ) {
        $size_attr->set_options( $new_options );
        $attributes['pa_tamano'] = $size_attr;
        $product->set_attributes( $attributes );
        $product->save();
    }
}
wc_delete_product_transients( 1056 );
clean_post_cache( 1056 );
wp_cache_flush();
echo 'Obsolete pack hidden. Variation IDs: ' . ( $changed_variations ? implode( ',', $changed_variations ) : 'already hidden' ) . PHP_EOL;
$fresh = wc_get_product( 1056 );
$attached = false;
foreach ( $fresh->get_attributes() as $attr ) {
    if ( 'pa_tamano' === $attr->get_name() && in_array( (int) $target_term->term_id, array_map( 'intval', $attr->get_options() ), true ) ) { $attached = true; }
}
if ( $attached ) { fwrite( STDERR, "Obsolete pack term is still attached to product.\n" ); exit( 6 ); }
foreach ( $fresh->get_children() as $variation_id ) {
    $variation = wc_get_product( $variation_id );
    if ( $variation && isset( $variation->get_attributes()['pa_tamano'] ) && $target_slug === $variation->get_attributes()['pa_tamano'] && 'publish' === get_post_status( $variation_id ) ) {
        fwrite( STDERR, "Obsolete variation {$variation_id} still published.\n" ); exit( 7 );
    }
}
echo "Verified obsolete pack is no longer purchasable or selectable.\n";
