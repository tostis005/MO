<?php
/**
 * Mantiene la categoría de exportación Mentta exclusivamente en jamones,
 * paletas y sus loncheados. No borra ni despublica productos.
 *
 * WP-CLI: MENTTA_MODE=audit|apply wp eval-file <archivo> --path=<sitio>
 */
if ( ! defined( 'ABSPATH' ) || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "WooCommerce no está disponible.\n" );
    exit( 2 );
}

global $wpdb;
$mode = getenv( 'MENTTA_MODE' ) ?: 'audit';
if ( ! in_array( $mode, array( 'audit', 'apply' ), true ) ) {
    fwrite( STDERR, "MENTTA_MODE inválido.\n" );
    exit( 2 );
}

$root = get_term_by( 'slug', 'mentta', 'product_cat' );
if ( ! $root instanceof WP_Term ) {
    fwrite( STDERR, "No existe la categoría product_cat/mentta.\n" );
    exit( 3 );
}
$children = get_term_children( (int) $root->term_id, 'product_cat' );
if ( is_wp_error( $children ) ) {
    fwrite( STDERR, "No se pueden consultar las subcategorías Mentta.\n" );
    exit( 3 );
}
$mentta_ids = array_values( array_unique( array_merge(
    array( (int) $root->term_id ),
    array_map( 'intval', $children )
) ) );
$placeholders = implode( ',', array_fill( 0, count( $mentta_ids ), '%d' ) );
$query = "SELECT DISTINCT p.ID
    FROM {$wpdb->posts} p
    JOIN {$wpdb->term_relationships} rel ON rel.object_id = p.ID
    JOIN {$wpdb->term_taxonomy} tax ON tax.term_taxonomy_id = rel.term_taxonomy_id
    WHERE p.post_type = 'product' AND tax.taxonomy = 'product_cat'
      AND tax.term_id IN ({$placeholders}) ORDER BY p.ID";
$product_ids = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( $query, $mentta_ids ) ) );
if ( count( $product_ids ) > 500 ) {
    fwrite( STDERR, "Se supera el límite de seguridad de 500 productos. Revisión necesaria.\n" );
    exit( 4 );
}

// Only Hidalgo de la Jara IDs identified in the original audit may be removed.
// Do not change Mentta membership for oils, coffee, meats, Montjam, or other vendors.
$hidalgo_nonham_ids = array( 1375, 1380, 1382, 1586, 1693, 1695, 4188, 5080, 5091, 5336, 5343, 5348 );
$eligible = array();
$exclude = array();
foreach ( $product_ids as $id ) {
    $product = wc_get_product( $id );
    if ( ! $product ) {
        fwrite( STDERR, "Producto Mentta inexistente o no legible: {$id}.\n" );
        exit( 5 );
    }
    $name = (string) $product->get_name();
    $normalized = strtolower( remove_accents( wp_strip_all_tags( $name ) ) );
    $normalized = preg_replace( '/[^a-z0-9]+/', ' ', $normalized );
    // No basta pertenecer a la categoría general "Jamones y paletas":
    // hay surtidos mixtos y otras referencias incorrectamente categorizadas.
    $has_ham = (bool) preg_match( '/\\b(?:jamon(?:es)?|paleta(?:s)?)\\b/', $normalized );
    $contains_other = (bool) preg_match(
        '/\\b(?:chorizo(?:s)?|salchichon(?:es)?|lomo(?:s)?|lomito(?:s)?|morcon(?:es)?|sobrasada(?:s)?|fuet(?:s)?|morcilla(?:s)?|embutido(?:s)?|queso(?:s)?|pato(?:s)?)\\b/',
        $normalized
    );
    $term_ids = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $term_ids ) ) {
        fwrite( STDERR, "Error consultando categorías de {$id}.\n" );
        exit( 5 );
    }
    $assigned_mentta = array_values( array_intersect( array_map( 'intval', $term_ids ), $mentta_ids ) );
    $row = array(
        'id' => $id,
        'title' => $name,
        'mentta_terms' => $assigned_mentta,
        'status' => $product->get_status(),
    );
    if ( ! in_array( $id, $hidalgo_nonham_ids, true ) || ( $has_ham && ! $contains_other ) ) {
        $eligible[] = $row;
    } else {
        $row['reason'] = $contains_other ? 'includes_non_ham_product' : 'not_ham_or_paleta';
        $exclude[] = $row;
    }
}

echo 'MENTTA_AUDIT ' . wp_json_encode( array(
    'mode' => $mode,
    'term_ids' => $mentta_ids,
    'total' => count( $product_ids ),
    'keep' => count( $eligible ),
    'remove' => count( $exclude ),
    'kept_products' => $eligible,
    'excluded_products' => $exclude,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";

if ( 'apply' !== $mode ) {
    return;
}

$removed = array();
foreach ( $exclude as $row ) {
    $id = (int) $row['id'];
    $before = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $before ) ) {
        throw new RuntimeException( "No se pudieron leer categorías de {$id}" );
    }
    $before = array_map( 'intval', $before );
    $other_before = array_values( array_diff( $before, $mentta_ids ) );
    sort( $other_before );

    $result = wp_remove_object_terms( $id, $row['mentta_terms'], 'product_cat' );
    if ( is_wp_error( $result ) || true !== $result ) {
        throw new RuntimeException( "No se pudo retirar Mentta del producto {$id}" );
    }
    clean_object_term_cache( $id, 'product' );
    $after = wp_get_object_terms( $id, 'product_cat', array( 'fields' => 'ids' ) );
    if ( is_wp_error( $after ) ) {
        throw new RuntimeException( "No se pudo verificar el producto {$id}" );
    }
    $after = array_map( 'intval', $after );
    $other_after = array_values( array_diff( $after, $mentta_ids ) );
    sort( $other_after );
    if ( array_intersect( $after, $mentta_ids ) || $other_before !== $other_after ) {
        throw new RuntimeException( "Falló la comprobación de categorías del producto {$id}" );
    }
    $removed[] = $id;
}

echo 'MENTTA_APPLIED ' . wp_json_encode( array(
    'removed_count' => count( $removed ),
    'removed_ids' => $removed,
    'kept_count' => count( $eligible ),
    'verified_no_other_categories_changed' => true,
    'products_deleted' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
