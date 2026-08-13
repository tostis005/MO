<?php
/**
 * Final read-only validation of published, non-hidden catalog products for the
 * five approved producers.
 * Workflow validation markers:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$vendors = array(
    3    => '1957',
    6    => 'Hidalgo de la Jara',
    4507 => 'Tolecarnes',
    4508 => 'Puente Robles',
    4509 => 'El Catedrático',
);

function emdo_final_terms( $product_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields' => 'names' ) );
    if ( is_wp_error( $names ) ) { return array(); }
    $names = array_values( array_unique( array_map( 'strval', (array) $names ) ) );
    sort( $names, SORT_NATURAL | SORT_FLAG_CASE );
    return $names;
}
function emdo_final_norm( $value ) {
    $value = remove_accents( strtolower( html_entity_decode( (string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
    return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
}
function emdo_final_has( array $values, $needle ) {
    return in_array( (string) $needle, $values, true );
}

$errors = array();
$vendor_counts = array();
$hidden_counts = array();
$checked = 0;

foreach ( $vendors as $author_id => $vendor_name ) {
    $settings = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
    if ( ! is_array( $settings ) || (string) ( $settings['store_name'] ?? '' ) !== $vendor_name ) {
        $errors[] = array( 'vendor' => $vendor_name, 'message' => 'Vendor identity mismatch' );
        continue;
    }
    $ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT ID FROM {$wpdb->posts}
         WHERE post_type = 'product' AND post_status = 'publish' AND post_author = %d
         ORDER BY ID ASC",
        $author_id
    ) );
    $vendor_counts[ $vendor_name ] = 0;
    $hidden_counts[ $vendor_name ] = 0;

    foreach ( array_map( 'intval', (array) $ids ) as $product_id ) {
        $product = wc_get_product( $product_id );
        if ( ! $product instanceof WC_Product || 'publish' !== $product->get_status() ) { continue; }
        if ( 'hidden' === $product->get_catalog_visibility() ) {
            ++$hidden_counts[ $vendor_name ];
            continue;
        }
        ++$vendor_counts[ $vendor_name ];
        ++$checked;

        $title = emdo_final_norm( $product->get_name( 'edit' ) );
        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
        $categories = is_wp_error( $categories ) ? array() : array_values( array_map( 'strval', $categories ) );
        $attrs = array(
            'tipo-pieza'    => emdo_final_terms( $product_id, 'pa_tipo-pieza' ),
            'calidad'       => emdo_final_terms( $product_id, 'pa_calidad' ),
            'raza'          => emdo_final_terms( $product_id, 'pa_raza-iberica' ),
            'alimentacion'  => emdo_final_terms( $product_id, 'pa_alimentacion' ),
            'con-dop'       => emdo_final_terms( $product_id, 'pa_con-dop' ),
            'preparacion'   => emdo_final_terms( $product_id, 'pa_preparacion' ),
            'productor'     => emdo_final_terms( $product_id, 'pa_productor' ),
            'tipo-producto' => emdo_final_terms( $product_id, 'pa_tipo-producto' ),
        );
        $product_errors = array();

        if ( ! $categories || in_array( 'sin-categorizar', $categories, true ) || in_array( 'uncategorized', $categories, true ) ) {
            $product_errors[] = 'invalid/default category';
        }
        if ( '1957' === $vendor_name && array( 'aceites' ) !== $categories ) {
            $product_errors[] = '1957 product is not exactly Aceites';
        }
        if ( 'Tolecarnes' === $vendor_name && array( 'carnes' ) !== $categories ) {
            $product_errors[] = 'Tolecarnes product is not exactly Carnes';
        }

        $is_ham = in_array( 'jamones-paletas', $categories, true );
        $is_cured = in_array( 'embutidos-y-curados', $categories, true );
        $is_adobado = in_array( 'adobados', $categories, true );
        $is_accessory = in_array( 'accesorios', $categories, true );

        if ( $is_ham ) {
            if ( empty( $attrs['tipo-pieza'] ) ) { $product_errors[] = 'ham missing tipo-pieza'; }
            if ( empty( $attrs['con-dop'] ) ) { $product_errors[] = 'ham missing con-dop'; }
            if ( empty( $attrs['productor'] ) || ! emdo_final_has( $attrs['productor'], $vendor_name ) ) { $product_errors[] = 'ham missing/wrong productor'; }

            $expected_race = '';
            if ( preg_match( '/\b100\s*%.*iberic|iberic.*\b100\s*%/u', $title ) ) { $expected_race = '100% ibérico'; }
            elseif ( preg_match( '/\b75\s*%.*iberic|iberic.*\b75\s*%/u', $title ) ) { $expected_race = '75% ibérico'; }
            elseif ( preg_match( '/\b50\s*%.*iberic|iberic.*\b50\s*%/u', $title ) ) { $expected_race = '50% ibérico'; }
            if ( $expected_race && ! emdo_final_has( $attrs['raza'], $expected_race ) ) { $product_errors[] = 'ham race contradicts title: ' . $expected_race; }

            $expected_feed = '';
            if ( str_contains( $title, 'cebo de campo' ) ) { $expected_feed = 'Cebo de campo'; }
            elseif ( str_contains( $title, 'bellota' ) ) { $expected_feed = 'Bellota'; }
            elseif ( preg_match( '/\bcebo\b/u', $title ) ) { $expected_feed = 'Cebo'; }
            if ( $expected_feed && ! emdo_final_has( $attrs['alimentacion'], $expected_feed ) ) { $product_errors[] = 'ham feed contradicts title: ' . $expected_feed; }
        }

        if ( $is_cured ) {
            if ( empty( $attrs['tipo-producto'] ) ) { $product_errors[] = 'cured missing tipo-producto'; }
            if ( empty( $attrs['preparacion'] ) ) { $product_errors[] = 'cured missing preparacion'; }
            if ( empty( $attrs['productor'] ) || ! emdo_final_has( $attrs['productor'], $vendor_name ) ) { $product_errors[] = 'cured missing/wrong productor'; }

            $expected_race = '';
            if ( preg_match( '/\b100\s*%.*iberic|iberic.*\b100\s*%/u', $title ) ) { $expected_race = '100% ibérico'; }
            elseif ( preg_match( '/\b75\s*%.*iberic|iberic.*\b75\s*%/u', $title ) ) { $expected_race = '75% ibérico'; }
            elseif ( preg_match( '/\b50\s*%.*iberic|iberic.*\b50\s*%/u', $title ) ) { $expected_race = '50% ibérico'; }
            if ( $expected_race && ! emdo_final_has( $attrs['raza'], $expected_race ) ) { $product_errors[] = 'cured race contradicts title: ' . $expected_race; }

            $expected_feed = '';
            if ( str_contains( $title, 'cebo de campo' ) ) { $expected_feed = 'Cebo de campo'; }
            elseif ( str_contains( $title, 'bellota' ) ) { $expected_feed = 'Bellota'; }
            elseif ( preg_match( '/\bcebo\b/u', $title ) ) { $expected_feed = 'Cebo'; }
            if ( $expected_feed && ! emdo_final_has( $attrs['alimentacion'], $expected_feed ) ) { $product_errors[] = 'cured feed contradicts title: ' . $expected_feed; }
        }

        if ( $is_adobado && empty( $attrs['tipo-producto'] ) ) {
            $product_errors[] = 'adobado missing tipo-producto';
        }
        if ( $is_accessory && empty( $attrs['tipo-producto'] ) ) {
            $product_errors[] = 'accessory missing tipo-producto';
        }

        if ( $product_errors ) {
            $errors[] = array(
                'id' => $product_id,
                'vendor' => $vendor_name,
                'title' => $product->get_name( 'edit' ),
                'categories' => $categories,
                'errors' => $product_errors,
                'attributes' => array_filter( $attrs ),
            );
        }
    }
}

foreach ( $errors as $error ) {
    echo 'FINAL_CATALOG_ERROR ' . wp_json_encode( $error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
echo 'FINAL_CATALOG_SUMMARY ' . wp_json_encode( array(
    'checked_published_non_hidden' => $checked,
    'vendor_counts' => $vendor_counts,
    'ignored_hidden_counts' => $hidden_counts,
    'error_count' => count( $errors ),
    'created_categories' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "__1957_UPDATE__=already_applied\n";
