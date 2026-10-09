<?php
/**
 * One-off: Montjam Mentta-only duplicate #17129 => Woo catalog visibility "visible".
 * Public shop exclusion is enforced by two existing MU plugins, not by WC visibility.
 * Must only run AFTER the reinforcement MU plugin is deployed and verified.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ABORT: WP-CLI and WooCommerce required\n" ); exit( 2 );
}
$id = 17129;
$guard_path = WP_CONTENT_DIR . '/mu-plugins/emdo-mentta-storefront-reinforcement.php';
if ( ! is_readable( $guard_path )
    || ! function_exists( 'emdo_mentta_exclusive_ids' )
    || ! function_exists( 'emdo_mentta_exclusive_public_request' )
    || ! function_exists( 'emdo_mj_exclusive_or_variation' )
    || ! function_exists( 'emdo_mj_is_store_api_request' )
    || ! in_array( $id, emdo_mentta_exclusive_ids(), true ) ) {
    fwrite( STDERR, "ABORT: storefront / Store API protection not active. No changes made.\n" ); exit( 3 );
}
$p = wc_get_product( $id );
if ( ! $p || ! $p->is_type( 'variable' )
    || $p->get_status() !== 'publish'
    || $p->get_slug() !== 'jamon-de-bellota-100-iberico-montjam-mentta'
    || ! in_array( $p->get_catalog_visibility(), array( 'hidden', 'visible' ), true )
    || (int) get_post_meta( $id, '_emdo_mentta_source_id', true ) !== 14264 ) {
    fwrite( STDERR, "ABORT: Mentta-only product identity/status mismatch.\n" ); exit( 4 );
}
$owner = get_user_by( 'login', 'montjam' );
$producer = wp_get_object_terms( $id, 'pa_productor', array( 'fields' => 'slugs' ) );
$cats = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'slugs' ) );
if ( ! $owner || (int) get_post_field( 'post_author', $id ) !== (int) $owner->ID
    || is_wp_error( $producer ) || ! in_array( 'montjam', $producer, true )
    || is_wp_error( $cats )
    || ! in_array( 'mentta', $cats, true )
    || ! in_array( 'mentta-jamones-paletas', $cats, true )
    || ! in_array( 'jamones-paletas', $cats, true ) ) {
    fwrite( STDERR, "ABORT: vendor/producer/category mismatch.\n" ); exit( 5 );
}
$expected = array( '6-65-kg' => '279.297', '65-7-kg' => '301.641' );
$prices = array();
foreach ( $p->get_children() as $vid ) {
    $v = wc_get_product( $vid );
    if ( ! $v || ! $v->is_type( 'variation' ) || $v->get_status() !== 'publish' ) {
        fwrite( STDERR, "ABORT: unexpected variation.\n" ); exit( 6 );
    }
    $size = $v->get_attributes()['pa_tamano'] ?? '';
    if ( ! isset( $expected[$size] )
        || abs( (float) $expected[$size] - (float) $v->get_regular_price('edit') ) > 0.00001
        || isset( $prices[$size] ) ) {
        fwrite( STDERR, "ABORT: variation size/price mismatch.\n" ); exit( 7 );
    }
    $prices[$size] = (string) $v->get_regular_price('edit');
}
if ( count( $prices ) !== 2 ) { fwrite( STDERR, "ABORT: expected exactly 2 sizes.\n" ); exit( 8 ); }
$before_visibility = $p->get_catalog_visibility();
$before_categories = $p->get_category_ids();
$before_price = $p->get_price('edit');
try {
    if ( 'visible' !== $before_visibility ) {
        $p->set_catalog_visibility( 'visible' );
        $p->save();
    }
    wc_delete_product_transients( $id );
    clean_post_cache( $id );
    if ( function_exists( 'rocket_clean_post' ) ) { rocket_clean_post( $id ); }
    if ( class_exists( 'WC_Cache_Helper' ) ) { WC_Cache_Helper::get_transient_version( 'product', true ); }
    if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
    $fresh = wc_get_product( $id );
    $category_after = $fresh ? $fresh->get_category_ids() : array();
    sort( $before_categories ); sort( $category_after );
    if ( ! $fresh || $fresh->get_catalog_visibility() !== 'visible'
        || $fresh->get_status() !== 'publish'
        || $fresh->get_price('edit') !== $before_price
        || $before_categories !== $category_after
        || ! in_array( $id, emdo_mentta_exclusive_ids(), true ) ) {
        throw new RuntimeException( 'Verification failed' );
    }
    echo 'MONTJAM_MENTTA_VISIBILITY_READY ' . wp_json_encode( array(
        'id' => $id,
        'catalog_visibility' => $fresh->get_catalog_visibility(),
        'public_storefront_protection' => 'enabled',
        'categories' => $category_after,
        'prices' => $prices,
        'must_verify_public_url_http_404' => true,
        'must_verify_no_public_store_api_exposure' => true,
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
} catch ( Throwable $error ) {
    $restore = wc_get_product( $id );
    if ( $restore ) {
        $restore->set_catalog_visibility( $before_visibility );
        $restore->save();
        wc_delete_product_transients( $id );
    }
    fwrite( STDERR, 'MONTJAM_MENTTA_VISIBILITY_ABORT ' . $error->getMessage() . "\n" );
    exit( 20 );
}
