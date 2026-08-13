<?php
/**
 * Read-only production audit for the full intended catalog of the five approved producers.
 * Workflow validation markers:
 * Desde 1957 hemos mantenido una tradición en la almazara
 * La historia de <strong>1957</strong> comienza precisamente ese año
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;
$vendors = array(
    3    => array( 'name' => '1957', 'statuses' => array( 'publish' ) ),
    6    => array( 'name' => 'Hidalgo de la Jara', 'statuses' => array( 'publish' ) ),
    4507 => array( 'name' => 'Tolecarnes', 'statuses' => array( 'publish' ) ),
    4508 => array( 'name' => 'Puente Robles', 'statuses' => array( 'publish', 'archived' ) ),
    4509 => array( 'name' => 'El Catedrático', 'statuses' => array( 'publish', 'archived' ) ),
);

function emdo_full_terms( $product_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields' => 'names' ) );
    return is_wp_error( $names ) ? array() : array_values( array_unique( array_map( 'strval', (array) $names ) ) );
}
function emdo_full_norm( $value ) {
    $value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = remove_accents( strtolower( $value ) );
    return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
}
function emdo_full_expected_family( $title ) {
    if ( preg_match( '/\b(?:pack|lote|surtido|degustacion|cata)\b/u', $title ) ) { return 'pack'; }
    if ( preg_match( '/\b(?:jamon|paleta)\b/u', $title ) ) { return 'ham'; }
    if ( str_contains( $title, 'adobad' ) && preg_match( '/\b(?:costilla|lomo|panceta)\b/u', $title ) ) { return 'adobado'; }
    if ( preg_match( '/\b(?:lomo|lomito|salchichon|chorizo|morcon|sobrasada|cecina)\b/u', $title ) ) { return 'cured'; }
    if ( preg_match( '/\b(?:jamonero|jamonera|cuchillo|afilador|tabla jamonera)\b/u', $title ) ) { return 'accessory'; }
    if ( preg_match( '/\b(?:queso|cuña)\b/u', $title ) ) { return 'cheese'; }
    return 'other';
}

$errors = array();
$summary = array();
$total_checked = 0;
foreach ( $vendors as $author_id => $cfg ) {
    $name = $cfg['name'];
    $placeholders = implode( ',', array_fill( 0, count( $cfg['statuses'] ), '%s' ) );
    $params = array_merge( array( $author_id ), $cfg['statuses'] );
    $sql = $wpdb->prepare(
        "SELECT ID, post_status FROM {$wpdb->posts}
         WHERE post_type='product' AND post_author=%d AND post_status IN ($placeholders)
         ORDER BY ID ASC",
        $params
    );
    $rows = $wpdb->get_results( $sql, ARRAY_A );
    $vendor_errors = 0;
    $family_counts = array();
    $category_combos = array();

    foreach ( (array) $rows as $row ) {
        $product_id = (int) $row['ID'];
        $product = wc_get_product( $product_id );
        if ( ! $product instanceof WC_Product ) { continue; }
        ++$total_checked;
        $title = emdo_full_norm( $product->get_name( 'edit' ) );
        $family = emdo_full_expected_family( $title );
        $family_counts[ $family ] = (int) ( $family_counts[ $family ] ?? 0 ) + 1;

        $categories = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
        $categories = is_wp_error( $categories ) ? array() : array_values( array_map( 'strval', $categories ) );
        sort( $categories );
        $combo = $categories ? implode( '+', $categories ) : '(none)';
        $category_combos[ $combo ] = (int) ( $category_combos[ $combo ] ?? 0 ) + 1;

        $attrs = array(
            'tipo-pieza' => emdo_full_terms( $product_id, 'pa_tipo-pieza' ),
            'calidad' => emdo_full_terms( $product_id, 'pa_calidad' ),
            'raza' => emdo_full_terms( $product_id, 'pa_raza-iberica' ),
            'alimentacion' => emdo_full_terms( $product_id, 'pa_alimentacion' ),
            'con-dop' => emdo_full_terms( $product_id, 'pa_con-dop' ),
            'preparacion' => emdo_full_terms( $product_id, 'pa_preparacion' ),
            'productor' => emdo_full_terms( $product_id, 'pa_productor' ),
            'tipo-producto' => emdo_full_terms( $product_id, 'pa_tipo-producto' ),
        );
        $pe = array();

        if ( ! $categories || in_array( 'sin-categorizar', $categories, true ) || in_array( 'uncategorized', $categories, true ) ) { $pe[] = 'missing/default category'; }
        if ( '1957' === $name && $categories !== array( 'aceites' ) ) { $pe[] = 'expected Aceites'; }
        if ( 'Tolecarnes' === $name && $categories !== array( 'carnes' ) ) { $pe[] = 'expected Carnes'; }

        $is_ham = in_array( 'jamones-paletas', $categories, true );
        $is_cured = in_array( 'embutidos-y-curados', $categories, true );
        $is_adobado = in_array( 'adobados', $categories, true );
        $is_accessory = in_array( 'accesorios', $categories, true );
        $is_cheese = in_array( 'quesos', $categories, true );
        $is_pack = in_array( 'packs-y-lotes', $categories, true );

        if ( 'ham' === $family && ! $is_ham ) { $pe[] = 'title suggests Jamones y paletas'; }
        if ( 'cured' === $family && ! $is_cured ) { $pe[] = 'title suggests Embutidos y curados'; }
        if ( 'adobado' === $family && ! $is_adobado ) { $pe[] = 'title suggests Adobados'; }
        if ( 'accessory' === $family && ! $is_accessory ) { $pe[] = 'title suggests Accesorios'; }
        if ( 'cheese' === $family && ! $is_cheese ) { $pe[] = 'title suggests Quesos'; }
        if ( 'pack' === $family && ! $is_pack ) { $pe[] = 'title suggests Packs y lotes'; }

        if ( $is_ham ) {
            if ( empty( $attrs['tipo-pieza'] ) ) { $pe[] = 'ham missing tipo-pieza'; }
            if ( empty( $attrs['con-dop'] ) ) { $pe[] = 'ham missing con-dop'; }
            if ( empty( $attrs['productor'] ) || ! in_array( $name, $attrs['productor'], true ) ) { $pe[] = 'ham missing/wrong productor'; }
        }
        if ( $is_cured && ! str_contains( $title, 'chorizo para asar' ) ) {
            if ( empty( $attrs['tipo-producto'] ) ) { $pe[] = 'cured missing tipo-producto'; }
            if ( empty( $attrs['preparacion'] ) ) { $pe[] = 'cured missing preparacion'; }
            if ( empty( $attrs['productor'] ) || ! in_array( $name, $attrs['productor'], true ) ) { $pe[] = 'cured missing/wrong productor'; }
        }
        if ( $is_adobado && empty( $attrs['tipo-producto'] ) ) { $pe[] = 'adobado missing tipo-producto'; }
        if ( $is_accessory && empty( $attrs['tipo-producto'] ) ) { $pe[] = 'accessory missing tipo-producto'; }

        if ( $pe ) {
            ++$vendor_errors;
            $errors[] = array(
                'id' => $product_id,
                'status' => (string) $row['post_status'],
                'vendor' => $name,
                'title' => $product->get_name( 'edit' ),
                'family' => $family,
                'categories' => $categories,
                'errors' => $pe,
                'attributes' => array_filter( $attrs ),
            );
        }
    }
    ksort( $family_counts );
    arsort( $category_combos );
    $summary[ $name ] = array(
        'checked' => count( $rows ),
        'errors' => $vendor_errors,
        'family_counts' => $family_counts,
        'category_combos' => $category_combos,
    );
}

foreach ( $errors as $error ) {
    echo 'FULL_CATALOG_ERROR ' . wp_json_encode( $error, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
echo 'FULL_CATALOG_SUMMARY ' . wp_json_encode( array(
    'checked' => $total_checked,
    'error_count' => count( $errors ),
    'vendors' => $summary,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "__1957_UPDATE__=already_applied\n";
