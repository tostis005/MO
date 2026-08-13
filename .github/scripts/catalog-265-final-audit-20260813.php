<?php
/**
 * Read-only final production audit for the intended 265-product catalog.
 * Scope:
 * - 1957: publish only
 * - Hidalgo de la Jara: publish only
 * - Tolecarnes: publish only
 * - Puente Robles: publish + archived
 * - El Catedrático: publish + archived
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
if ( ! function_exists( 'wc_get_product' ) ) { exit( 2 ); }

global $wpdb;

$vendors = array(
    3    => array( 'name' => '1957',              'statuses' => array( 'publish' ) ),
    6    => array( 'name' => 'Hidalgo de la Jara','statuses' => array( 'publish' ) ),
    4507 => array( 'name' => 'Tolecarnes',        'statuses' => array( 'publish' ) ),
    4508 => array( 'name' => 'Puente Robles',     'statuses' => array( 'publish', 'archived' ) ),
    4509 => array( 'name' => 'El Catedrático',    'statuses' => array( 'publish', 'archived' ) ),
);

function emdo_265_norm( $value ) {
    $value = html_entity_decode( wp_strip_all_tags( (string) $value ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = remove_accents( strtolower( $value ) );
    return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
}
function emdo_265_terms( $product_id, $taxonomy ) {
    if ( ! taxonomy_exists( $taxonomy ) ) { return array(); }
    $names = wp_get_object_terms( (int) $product_id, $taxonomy, array( 'fields' => 'names' ) );
    if ( is_wp_error( $names ) ) { return array(); }
    $names = array_values( array_unique( array_map( 'strval', (array) $names ) ) );
    sort( $names );
    return $names;
}
function emdo_265_categories( $product_id ) {
    $slugs = wp_get_post_terms( (int) $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
    if ( is_wp_error( $slugs ) ) { return array(); }
    $slugs = array_values( array_unique( array_map( 'strval', (array) $slugs ) ) );
    sort( $slugs );
    return $slugs;
}
function emdo_265_has( array $values, $needle ) {
    return in_array( (string) $needle, array_map( 'strval', $values ), true );
}
function emdo_265_expected_race( $title ) {
    foreach ( array( 100, 75, 50 ) as $pct ) {
        if ( preg_match( '/\b' . $pct . '\s*%\s*(?:raza\s+)?iberic[oa]s?\b/u', $title ) || preg_match( '/\biberic[oa]s?\s*(?:de\s+)?(?:raza\s+)?' . $pct . '\s*%\b/u', $title ) ) {
            return $pct . '% ibérico';
        }
    }
    return '';
}
function emdo_265_expected_feed( $title ) {
    if ( preg_match( '/\bcebo\s+(?:de\s+)?campo\b/u', $title ) ) { return 'Cebo de campo'; }
    if ( preg_match( '/\bbellota\b/u', $title ) ) { return 'Bellota'; }
    if ( preg_match( '/\bcebo\b/u', $title ) ) { return 'Cebo'; }
    return '';
}
function emdo_265_expected_cured_type( $title ) {
    $map = array(
        'Lomito' => '/\blomito\b/u',
        'Chorizo' => '/\bchorizos?\b/u',
        'Salchichón' => '/\bsalchichon(?:es)?\b/u',
        'Morcón' => '/\bmorcon(?:es)?\b/u',
        'Sobrasada' => '/\bsobrasadas?\b/u',
        'Cecina' => '/\bcecinas?\b/u',
        'Lomo' => '/\blomo\b/u',
    );
    foreach ( $map as $name => $pattern ) {
        if ( preg_match( $pattern, $title ) ) { return $name; }
    }
    return '';
}
function emdo_265_expected_piece( $title ) {
    if ( preg_match( '/\bpaleta(?:s)?\b/u', $title ) ) { return 'Paleta'; }
    if ( preg_match( '/\bjamon(?:es)?\b/u', $title ) ) { return 'Jamón'; }
    return '';
}

$errors = array();
$warnings = array();
$vendor_summary = array();
$total_checked = 0;
$status_summary = array();
$category_count = (int) wp_count_terms( array( 'taxonomy'=>'product_cat', 'hide_empty'=>false ) );

foreach ( $vendors as $author_id => $cfg ) {
    $settings = get_user_meta( $author_id, 'wcfmmp_profile_settings', true );
    if ( ! is_array( $settings ) || (string) ( $settings['store_name'] ?? '' ) !== $cfg['name'] ) {
        throw new RuntimeException( 'Vendor identity mismatch for ' . $author_id );
    }

    $placeholders = implode( ',', array_fill( 0, count( $cfg['statuses'] ), '%s' ) );
    $params = array_merge( array( $author_id ), $cfg['statuses'] );
    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT ID, post_status FROM {$wpdb->posts}
         WHERE post_type='product' AND post_author=%d AND post_status IN ($placeholders)
         ORDER BY ID ASC",
        $params
    ), ARRAY_A );

    $v_errors = 0;
    $v_warnings = 0;
    $category_sets = array();
    foreach ( (array) $rows as $row ) {
        $product_id = (int) $row['ID'];
        $status = (string) $row['post_status'];
        $product = wc_get_product( $product_id );
        if ( ! $product instanceof WC_Product ) {
            $errors[] = array( 'id'=>$product_id, 'vendor'=>$cfg['name'], 'error'=>'WooCommerce product unavailable' );
            ++$v_errors;
            continue;
        }
        ++$total_checked;
        $status_summary[$status] = (int) ($status_summary[$status] ?? 0) + 1;
        $title_raw = (string) $product->get_name( 'edit' );
        $title = emdo_265_norm( $title_raw );
        $categories = emdo_265_categories( $product_id );
        $set_key = $categories ? implode( '+', $categories ) : '(none)';
        $category_sets[$set_key] = (int) ($category_sets[$set_key] ?? 0) + 1;

        $attrs = array(
            'tipo-pieza' => emdo_265_terms( $product_id, 'pa_tipo-pieza' ),
            'calidad' => emdo_265_terms( $product_id, 'pa_calidad' ),
            'raza' => emdo_265_terms( $product_id, 'pa_raza-iberica' ),
            'alimentacion' => emdo_265_terms( $product_id, 'pa_alimentacion' ),
            'con-dop' => emdo_265_terms( $product_id, 'pa_con-dop' ),
            'dop' => emdo_265_terms( $product_id, 'pa_dop' ),
            'origen' => emdo_265_terms( $product_id, 'pa_origen' ),
            'preparacion' => emdo_265_terms( $product_id, 'pa_preparacion' ),
            'rango-peso' => emdo_265_terms( $product_id, 'pa_rango-peso' ),
            'curacion' => emdo_265_terms( $product_id, 'pa_curacion' ),
            'productor' => emdo_265_terms( $product_id, 'pa_productor' ),
            'tipo-producto' => emdo_265_terms( $product_id, 'pa_tipo-producto' ),
        );

        $pe = array();
        $pw = array();
        if ( ! $categories || emdo_265_has( $categories, 'sin-categorizar' ) || emdo_265_has( $categories, 'uncategorized' ) ) {
            $pe[] = 'missing/default category';
        }

        // Vendor-specific primary category rules that are intentionally simple.
        if ( '1957' === $cfg['name'] && $categories !== array( 'aceites' ) ) {
            $pe[] = '1957 product must be Aceites';
        }
        if ( 'Tolecarnes' === $cfg['name'] && $categories !== array( 'carnes' ) ) {
            $pe[] = 'Tolecarnes product must be Carnes';
        }

        $is_ham = emdo_265_has( $categories, 'jamones-paletas' ) || emdo_265_has( $categories, 'jamones-y-paletas' );
        $is_cured = emdo_265_has( $categories, 'embutidos-y-curados' );
        $is_adobado = emdo_265_has( $categories, 'adobados' );
        $is_accessory = emdo_265_has( $categories, 'accesorios' );
        $is_pack = emdo_265_has( $categories, 'packs-y-lotes' );

        if ( $is_ham ) {
            $piece = emdo_265_expected_piece( $title );
            if ( ! $attrs['tipo-pieza'] ) { $pe[] = 'ham missing tipo-pieza'; }
            elseif ( $piece && ! emdo_265_has( $attrs['tipo-pieza'], $piece ) ) { $pe[] = 'ham wrong tipo-pieza expected ' . $piece; }
            if ( ! $attrs['calidad'] ) { $pe[] = 'ham missing calidad'; }
            if ( ! $attrs['con-dop'] ) { $pe[] = 'ham missing con-dop'; }
            if ( count( $attrs['con-dop'] ) > 1 ) { $pe[] = 'ham multiple con-dop values'; }
            if ( ! $attrs['origen'] ) { $pe[] = 'ham missing origen'; }
            if ( ! $attrs['preparacion'] ) { $pe[] = 'ham missing preparacion'; }
            if ( ! $attrs['productor'] || ! emdo_265_has( $attrs['productor'], $cfg['name'] ) ) { $pe[] = 'ham missing/wrong productor'; }
            if ( ! $attrs['curacion'] ) { $pw[] = 'ham missing curacion'; }

            $race = emdo_265_expected_race( $title );
            if ( $race && ! emdo_265_has( $attrs['raza'], $race ) ) { $pe[] = 'ham race mismatch expected ' . $race; }
            $feed = emdo_265_expected_feed( $title );
            if ( $feed && ! emdo_265_has( $attrs['alimentacion'], $feed ) ) { $pe[] = 'ham feed mismatch expected ' . $feed; }
            if ( preg_match( '/\bdop\b/u', $title ) ) {
                if ( ! emdo_265_has( $attrs['con-dop'], 'Sí' ) ) { $pe[] = 'title says DOP but con-dop is not Sí'; }
                if ( ! $attrs['dop'] ) { $pe[] = 'title says DOP but dop term missing'; }
            }
        }

        if ( $is_cured ) {
            $type = emdo_265_expected_cured_type( $title );
            if ( ! $attrs['tipo-producto'] ) { $pe[] = 'cured missing tipo-producto'; }
            elseif ( $type && ! emdo_265_has( $attrs['tipo-producto'], $type ) ) { $pe[] = 'cured type mismatch expected ' . $type; }
            if ( ! $attrs['preparacion'] ) { $pe[] = 'cured missing preparacion'; }
            if ( ! $attrs['productor'] || ! emdo_265_has( $attrs['productor'], $cfg['name'] ) ) { $pe[] = 'cured missing/wrong productor'; }
            $race = emdo_265_expected_race( $title );
            if ( $race && ! emdo_265_has( $attrs['raza'], $race ) ) { $pe[] = 'cured race mismatch expected ' . $race; }
            $feed = emdo_265_expected_feed( $title );
            if ( $feed && ! emdo_265_has( $attrs['alimentacion'], $feed ) ) { $pe[] = 'cured feed mismatch expected ' . $feed; }
        }

        if ( $is_adobado && ! $attrs['tipo-producto'] ) { $pe[] = 'adobado missing tipo-producto'; }
        if ( $is_accessory && ! $attrs['tipo-producto'] ) { $pe[] = 'accessory missing tipo-producto'; }

        // A pack category does not itself require ham/cured filter metadata. If a
        // product is also assigned to a family category, that family's rules above apply.
        if ( $is_pack && count( $categories ) === 1 && $categories[0] === 'packs-y-lotes' ) {
            // Expected and valid for transversal/mixed bundles.
        }

        if ( $pe ) {
            ++$v_errors;
            $errors[] = array(
                'id'=>$product_id, 'vendor'=>$cfg['name'], 'status'=>$status,
                'title'=>$title_raw, 'categories'=>$categories,
                'errors'=>$pe, 'attributes'=>array_filter($attrs),
            );
        }
        if ( $pw ) {
            ++$v_warnings;
            $warnings[] = array(
                'id'=>$product_id, 'vendor'=>$cfg['name'], 'status'=>$status,
                'title'=>$title_raw, 'categories'=>$categories,
                'warnings'=>$pw, 'attributes'=>array_filter($attrs),
            );
        }
    }
    arsort( $category_sets );
    $vendor_summary[$cfg['name']] = array(
        'checked'=>count($rows), 'errors'=>$v_errors, 'warnings'=>$v_warnings,
        'category_sets'=>$category_sets,
    );
}

if ( 265 !== $total_checked ) {
    throw new RuntimeException( 'Expected exactly 265 intended products, checked ' . $total_checked );
}

foreach ( $errors as $row ) {
    echo 'CATALOG_265_ERROR ' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}
foreach ( $warnings as $row ) {
    echo 'CATALOG_265_WARNING ' . wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
}

echo 'CATALOG_265_SUMMARY ' . wp_json_encode( array(
    'checked'=>$total_checked,
    'status_counts'=>$status_summary,
    'error_count'=>count($errors),
    'warning_count'=>count($warnings),
    'product_category_count'=>$category_count,
    'vendors'=>$vendor_summary,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
