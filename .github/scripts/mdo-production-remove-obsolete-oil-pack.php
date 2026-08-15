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
        if ( 'outofstock' !== $variation->get_stock_status() ) {
            $variation->set_stock_status( 'outofstock' );
            $variation->save();
        }
        $changed_variations[] = $variation_id;
    }
}
/* Taxonomy-backed Woo attributes use the post/term relationship as the source
 * of selectable options. Remove only this product's relationship; keep the term
 * itself for historical records and any other products that might use it. */
$result = wp_remove_object_terms( 1056, array( (int) $target_term->term_id ), 'pa_tamano' );
if ( is_wp_error( $result ) ) {
    fwrite( STDERR, "Could not remove obsolete size relationship: {$result->get_error_message()}\n" );
    exit( 6 );
}
wc_delete_product_transients( 1056 );
clean_object_term_cache( 1056, 'product' );
clean_post_cache( 1056 );
wp_cache_flush();
echo 'Obsolete pack variations hidden: ' . ( $changed_variations ? implode( ',', $changed_variations ) : 'already hidden' ) . PHP_EOL;
$fresh_terms = wp_get_post_terms( 1056, 'pa_tamano', array( 'fields' => 'ids' ) );
if ( is_wp_error( $fresh_terms ) ) { fwrite( STDERR, "Could not verify product size terms.\n" ); exit( 7 ); }
if ( in_array( (int) $target_term->term_id, array_map( 'intval', $fresh_terms ), true ) ) {
    fwrite( STDERR, "Obsolete pack term is still attached to product.\n" );
    exit( 8 );
}
$fresh = wc_get_product( 1056 );
foreach ( $fresh->get_children() as $variation_id ) {
    $variation = wc_get_product( $variation_id );
    $attrs = $variation ? $variation->get_attributes() : array();
    if ( $variation && isset( $attrs['pa_tamano'] ) && $target_slug === $attrs['pa_tamano'] && 'publish' === get_post_status( $variation_id ) ) {
        fwrite( STDERR, "Obsolete variation {$variation_id} still published.\n" ); exit( 9 );
    }
}
echo "Verified obsolete pack is no longer purchasable or selectable.\n";
