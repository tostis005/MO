<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'wc_get_product' ) ) {
	throw new RuntimeException( 'WooCommerce no está disponible.' );
}

function emdo_repair_category_id( string $name, string $slug ): int {
	$term = get_term_by( 'slug', $slug, 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		$created = wp_insert_term( $name, 'product_cat', array( 'slug' => $slug ) );
		if ( is_wp_error( $created ) ) {
			throw new RuntimeException( 'No se pudo crear la categoría ' . $name . ': ' . $created->get_error_message() );
		}
		$term = get_term( (int) $created['term_id'], 'product_cat' );
	}
	if ( ! $term instanceof WP_Term ) {
		throw new RuntimeException( 'No se pudo resolver la categoría ' . $name . '.' );
	}
	return (int) $term->term_id;
}

function emdo_repair_product_categories( WC_Product $product, int $target_category_id ): void {
	$default_id = (int) get_option( 'default_product_cat', 0 );
	$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $product->get_category_ids() ) ) ) );
	if ( $default_id > 0 ) {
		$category_ids = array_values( array_diff( $category_ids, array( $default_id ) ) );
	}
	$category_ids[] = $target_category_id;
	$product->set_category_ids( array_values( array_unique( array_filter( array_map( 'intval', $category_ids ) ) ) ) );
	$product->save();
}

function emdo_repair_term_names( int $product_id, string $taxonomy ): array {
	if ( ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}
	$terms = wp_get_object_terms( $product_id, $taxonomy, array( 'fields' => 'names' ) );
	return is_wp_error( $terms ) ? array() : array_values( array_map( 'strval', $terms ) );
}

$expected_site = preg_match( '/(?:^|\.)elmercadodeorigen\.com$/', (string) wp_parse_url( get_option( 'siteurl' ), PHP_URL_HOST ) );
if ( ! $expected_site ) {
	throw new RuntimeException( 'Este script solo puede ejecutarse en el WordPress de producción de El Mercado de Origen.' );
}

$targets = array(
	5064 => array( 'name' => 'Botellita de aceite de oliva virgen sin filtrar', 'category_name' => 'Aceites', 'category_slug' => 'aceites' ),
	5065 => array( 'name' => 'Sobre de salchichón', 'category_name' => 'Embutidos y curados', 'category_slug' => 'embutidos-y-curados' ),
	5066 => array( 'name' => 'Cuña de queso', 'category_name' => 'Quesos', 'category_slug' => 'quesos' ),
	5067 => array( 'name' => 'Botella de vino', 'category_name' => 'Vinos', 'category_slug' => 'vinos' ),
);

$report = array(
	'site_url' => (string) get_option( 'siteurl' ),
	'updated' => array(),
	'created_categories' => array(),
	'verification' => array(),
);

foreach ( $targets as $product_id => $target ) {
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product || $product->is_type( 'variation' ) ) {
		throw new RuntimeException( 'No se encontró el producto esperado ' . $product_id . '.' );
	}
	if ( (string) $product->get_name() !== (string) $target['name'] ) {
		throw new RuntimeException( 'El producto ' . $product_id . ' no coincide con el nombre auditado. Abortado por seguridad.' );
	}

	$existing_category = get_term_by( 'slug', (string) $target['category_slug'], 'product_cat' );
	$category_id = emdo_repair_category_id( (string) $target['category_name'], (string) $target['category_slug'] );
	if ( ! $existing_category instanceof WP_Term ) {
		$report['created_categories'][] = array( 'id' => $category_id, 'name' => (string) $target['category_name'], 'slug' => (string) $target['category_slug'] );
	}

	emdo_repair_product_categories( $product, $category_id );

	if ( 5065 === $product_id ) {
		if ( ! class_exists( 'MDO_Cured_Catalog' ) ) {
			throw new RuntimeException( 'MDO_Cured_Catalog no está disponible en producción.' );
		}
		$result = MDO_Cured_Catalog::classify_product( $product_id );
		if ( empty( $result['target'] ) || empty( $result['individual_cured'] ) ) {
			throw new RuntimeException( 'El clasificador de embutidos no reconoció el Sobre de salchichón.' );
		}
		if ( class_exists( 'MDO_Cured_Producer' ) ) {
			MDO_Cured_Producer::sync_after_save( wc_get_product( $product_id ) );
		}
	}

	$product = wc_get_product( $product_id );
	$report['updated'][] = array(
		'id' => $product_id,
		'name' => (string) $product->get_name(),
		'categories' => emdo_repair_term_names( $product_id, 'product_cat' ),
		'tipo_producto' => emdo_repair_term_names( $product_id, 'pa_tipo-producto' ),
		'preparacion' => emdo_repair_term_names( $product_id, 'pa_preparacion' ),
		'productor' => emdo_repair_term_names( $product_id, 'pa_productor' ),
	);
}

// Verificación global: ningún producto de catálogo puede quedarse solo en la categoría por defecto.
$default_id = (int) get_option( 'default_product_cat', 0 );
$ids = wc_get_products( array(
	'limit' => -1,
	'return' => 'ids',
	'status' => array( 'publish', 'private', 'draft', 'pending' ),
	'orderby' => 'ID',
	'order' => 'ASC',
) );
$uncategorized = array();
foreach ( array_map( 'intval', $ids ) as $product_id ) {
	$product = wc_get_product( $product_id );
	if ( ! $product instanceof WC_Product || $product->is_type( 'variation' ) ) {
		continue;
	}
	$category_ids = array_values( array_unique( array_filter( array_map( 'intval', $product->get_category_ids() ) ) ) );
	if ( $default_id > 0 ) {
		$category_ids = array_values( array_diff( $category_ids, array( $default_id ) ) );
	}
	if ( ! $category_ids ) {
		$uncategorized[] = array( 'id' => $product_id, 'name' => (string) $product->get_name() );
	}
}

$salchichon_types = emdo_repair_term_names( 5065, 'pa_tipo-producto' );
$salchichon_prep = emdo_repair_term_names( 5065, 'pa_preparacion' );
$salchichon_producer = emdo_repair_term_names( 5065, 'pa_productor' );
if ( ! in_array( 'Salchichón', $salchichon_types, true ) || ! in_array( 'Loncheado', $salchichon_prep, true ) || ! in_array( 'El Mercado de Origen', $salchichon_producer, true ) ) {
	throw new RuntimeException( 'La verificación de filtros del Sobre de salchichón no coincide con lo esperado.' );
}

if ( $uncategorized ) {
	throw new RuntimeException( 'Quedan productos sin categoría válida: ' . wp_json_encode( $uncategorized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
}

$report['verification'] = array(
	'total_products_scanned' => count( $ids ),
	'uncategorized_count' => count( $uncategorized ),
	'salchichon_filters' => array(
		'tipo_producto' => $salchichon_types,
		'preparacion' => $salchichon_prep,
		'productor' => $salchichon_producer,
	),
	'finished_at' => current_time( 'mysql' ),
);

clean_term_cache( array_map( 'intval', wp_list_pluck( $report['updated'], 'id' ) ), 'product_cat' );
if ( class_exists( 'WC_Cache_Helper' ) ) {
	WC_Cache_Helper::invalidate_cache_group( 'product_' );
	WC_Cache_Helper::invalidate_cache_group( 'woocommerce-attributes' );
}
wp_cache_flush();

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . PHP_EOL;
