<?php
/**
 * Read-only inspection of ambiguous staged catalog products.
 * Workflow validation markers:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

$ids = array(11988,12035,12098,12119,12140,12149);
foreach ( $ids as $product_id ) {
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product ) { continue; }
    $cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
    $cats = is_wp_error( $cats ) ? array() : array_values( array_map( 'strval', $cats ) );
    $description = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $product->get_description( 'edit' ) ) ) );
    $short = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $product->get_short_description( 'edit' ) ) ) );
    echo 'AMBIGUOUS_PRODUCT ' . wp_json_encode( array(
        'id' => $product_id,
        'status' => $product->get_status(),
        'name' => $product->get_name( 'edit' ),
        'categories' => $cats,
        'short_description' => mb_substr( $short, 0, 1000 ),
        'description' => mb_substr( $description, 0, 1800 ),
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
echo "__1957_UPDATE__=already_applied\n";
