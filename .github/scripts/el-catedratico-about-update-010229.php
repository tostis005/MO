<?php
/**
 * Guarded production metadata completion for El Catedrático.
 * No product categories or attribute terms are created here.
 * Historical workflow marker: Frankfurt International Trophy 2025.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;
$user_id = 4509;
$settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
if ( ! is_array( $settings ) || 'El Catedrático' !== (string) ( $settings['store_name'] ?? '' ) || 'el-catedratico' !== (string) ( $settings['store_slug'] ?? '' ) ) {
    fwrite( STDERR, "CATEDRATICO_CATALOG_ABORT: vendor identity mismatch\n" );
    exit( 3 );
}

function emdo_catedratico_existing_term_id( $taxonomy, $name ) {
    if ( ! taxonomy_exists( $taxonomy ) ) {
        throw new RuntimeException( 'Missing taxonomy ' . $taxonomy );
    }
    $term = get_term_by( 'name', (string) $name, $taxonomy );
    if ( ! $term instanceof WP_Term ) {
        throw new RuntimeException( 'Missing existing term ' . $taxonomy . ' / ' . $name );
    }
    return (int) $term->term_id;
}

function emdo_catedratico_set_attribute( $product_id, $taxonomy, $term_name ) {
    $term_id = emdo_catedratico_existing_term_id( $taxonomy, $term_name );
    $set = wp_set_object_terms( (int) $product_id, array( $term_id ), $taxonomy, false );
    if ( is_wp_error( $set ) ) {
        throw new RuntimeException( $set->get_error_message() );
    }

    $attrs = get_post_meta( (int) $product_id, '_product_attributes', true );
    if ( ! is_array( $attrs ) ) { $attrs = array(); }
    $position = count( $attrs );
    if ( isset( $attrs[ $taxonomy ] ) && is_array( $attrs[ $taxonomy ] ) ) {
        $position = isset( $attrs[ $taxonomy ]['position'] ) ? (int) $attrs[ $taxonomy ]['position'] : $position;
    }
    $attrs[ $taxonomy ] = array(
        'name'         => $taxonomy,
        'value'        => '',
        'position'     => $position,
        'is_visible'   => 0,
        'is_variation' => 0,
        'is_taxonomy'  => 1,
    );
    update_post_meta( (int) $product_id, '_product_attributes', $attrs );
    clean_post_cache( (int) $product_id );
    wc_delete_product_transients( (int) $product_id );
}

$patches = array(
    12512 => array(
        'expected_title' => 'Chorizo bellota ibérico 100% (En caja de madera)',
        'attributes' => array( 'pa_raza-iberica' => '100% ibérico' ),
    ),
    12521 => array(
        'expected_title' => 'Salchichón de bellota ibérico 100% (En caja de madera)',
        'attributes' => array( 'pa_raza-iberica' => '100% ibérico' ),
    ),
    12602 => array(
        'expected_title' => 'Chorizo para asar',
        'attributes' => array(
            'pa_tipo-producto' => 'Chorizo',
            'pa_preparacion'   => 'Pieza entera',
        ),
    ),
);

$updated = array();
foreach ( $patches as $product_id => $patch ) {
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT ID, post_author, post_status, post_title FROM {$wpdb->posts} WHERE ID = %d AND post_type = 'product'",
        $product_id
    ), ARRAY_A );
    if ( ! is_array( $row ) || (int) $row['post_author'] !== $user_id || 'publish' !== (string) $row['post_status'] || (string) $row['post_title'] !== (string) $patch['expected_title'] ) {
        fwrite( STDERR, 'CATEDRATICO_CATALOG_ABORT: product identity mismatch ' . $product_id . "\n" );
        exit( 4 );
    }
    $product = wc_get_product( $product_id );
    if ( ! $product instanceof WC_Product || 'hidden' === $product->get_catalog_visibility() ) {
        fwrite( STDERR, 'CATEDRATICO_CATALOG_ABORT: product hidden/unavailable ' . $product_id . "\n" );
        exit( 5 );
    }

    foreach ( $patch['attributes'] as $taxonomy => $term_name ) {
        try {
            emdo_catedratico_set_attribute( $product_id, $taxonomy, $term_name );
        } catch ( Throwable $error ) {
            fwrite( STDERR, 'CATEDRATICO_CATALOG_ABORT: ' . $product_id . ' ' . $taxonomy . ': ' . $error->getMessage() . "\n" );
            exit( 6 );
        }
    }

    $verified = array();
    foreach ( $patch['attributes'] as $taxonomy => $term_name ) {
        $names = wp_get_object_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
        if ( is_wp_error( $names ) || array( $term_name ) !== array_values( array_map( 'strval', (array) $names ) ) ) {
            fwrite( STDERR, 'CATEDRATICO_CATALOG_ABORT: verification failed ' . $product_id . ' ' . $taxonomy . "\n" );
            exit( 7 );
        }
        $verified[ $taxonomy ] = array_values( array_map( 'strval', (array) $names ) );
    }
    $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $categories ) || ! $categories ) {
        fwrite( STDERR, 'CATEDRATICO_CATALOG_ABORT: category verification failed ' . $product_id . "\n" );
        exit( 8 );
    }
    $updated[] = array(
        'id' => $product_id,
        'title' => (string) $row['post_title'],
        'categories' => array_values( array_map( 'strval', (array) $categories ) ),
        'attributes' => $verified,
    );
}

foreach ( $updated as $row ) {
    echo 'CATEDRATICO_CATALOG_PRODUCT ' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
echo 'CATEDRATICO_CATALOG_SUMMARY ' . wp_json_encode( array(
    'owner_id' => $user_id,
    'updated' => count( $updated ),
    'created_categories' => 0,
    'created_terms' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "CATEDRATICO_UPDATE=already_applied\n";
echo "CATEDRATICO_USER_ID={$user_id}\n";
echo "CATEDRATICO_STORE_NAME=El Catedrático\n";
echo "CATEDRATICO_STORE_SLUG=el-catedratico\n";
