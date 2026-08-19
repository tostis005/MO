<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$out = array(
	'ok'             => false,
	'plugin_version' => defined( 'MDO_SUPPLIER_SYNC_VERSION' ) ? MDO_SUPPLIER_SYNC_VERSION : 'unknown',
	'checks'         => array(),
	'catalog'        => array(),
	'smoke_import'   => array(),
);

try {
	if ( '1.0.17' !== (string) $out['plugin_version'] ) {
		throw new RuntimeException( 'Producción no está ejecutando EMDO 1.0.17.' );
	}

	$supplier = null;
	foreach ( MDO_Supplier_Repository::all() as $candidate ) {
		if ( 'la-huerta-ana-mary' === (string) ( $candidate['connector'] ?? '' ) ) {
			$supplier = $candidate;
			break;
		}
	}
	if ( ! $supplier ) {
		throw new RuntimeException( 'No se encontró La Huerta de Ana Mary en EMDO.' );
	}

	$term = get_term_by( 'slug', 'hortalizas-verduras', 'product_cat' );
	if ( ! $term || is_wp_error( $term ) ) {
		throw new RuntimeException( 'No existe la categoría Hortalizas/Verduras.' );
	}
	$out['category'] = array(
		'id'   => (int) $term->term_id,
		'name' => (string) $term->name,
		'slug' => (string) $term->slug,
	);

	$discovery = MDO_Connector_Huerta_Ana_Mary::discover( $supplier );
	$urls      = array_values( (array) ( $discovery['products'] ?? array() ) );
	$out['catalog']['products_found'] = count( $urls );
	$out['catalog']['pages_found']    = count( (array) ( $discovery['pages'] ?? array() ) );
	$out['catalog']['errors']         = array();
	$out['catalog']['empty_images']   = array();
	$out['catalog']['bad_images']     = array();
	$out['catalog']['empty_descriptions'] = array();
	$out['catalog']['noisy_descriptions'] = array();
	$out['catalog']['encoding_issues']     = array();
	$out['catalog']['products']            = array();

	$flowers_ok = false;
	foreach ( $urls as $url ) {
		try {
			$product = MDO_Connector_Huerta_Ana_Mary::scrape_product( (string) $url );
			$id      = (string) ( $product['source_product_id'] ?? '' );
			$title   = (string) ( $product['title'] ?? '' );
			$images  = array_values( (array) ( $product['images'] ?? array() ) );
			$desc    = wp_strip_all_tags( (string) ( $product['description'] ?? '' ) );

			if ( '113' === $id ) {
				$flowers_ok = 'Flores de calabacín 8 unidades' === $title;
			}
			if ( ! $images ) {
				$out['catalog']['empty_images'][] = array( 'id' => $id, 'title' => $title, 'url' => $url );
			} else {
				$first = (string) $images[0];
				$path  = (string) wp_parse_url( $first, PHP_URL_PATH );
				if ( ! str_starts_with( $path, '/data/productos/imagenes/' ) ) {
					$out['catalog']['bad_images'][] = array( 'id' => $id, 'title' => $title, 'image' => $first, 'reason' => 'wrong_path' );
				} else {
					$response = wp_remote_get( $first, array( 'timeout' => 20, 'redirection' => 5 ) );
					if ( is_wp_error( $response ) ) {
						$out['catalog']['bad_images'][] = array( 'id' => $id, 'title' => $title, 'image' => $first, 'reason' => $response->get_error_message() );
					} else {
						$status = (int) wp_remote_retrieve_response_code( $response );
						$type   = (string) wp_remote_retrieve_header( $response, 'content-type' );
						if ( 200 !== $status || ! str_starts_with( strtolower( $type ), 'image/' ) ) {
							$out['catalog']['bad_images'][] = array( 'id' => $id, 'title' => $title, 'image' => $first, 'status' => $status, 'type' => $type );
						}
					}
				}
			}

			if ( '' === trim( $desc ) ) {
				$out['catalog']['empty_descriptions'][] = array( 'id' => $id, 'title' => $title );
			}
			if ( preg_match( '/(?:Te interesa|Carrito de la Compra|Seguir Comprando|Subtotal:)/iu', $desc ) ) {
				$out['catalog']['noisy_descriptions'][] = array( 'id' => $id, 'title' => $title );
			}
			if ( preg_match( '/(?:Ã|Â|â€|ã)/u', $title . ' ' . $desc ) ) {
				$out['catalog']['encoding_issues'][] = array( 'id' => $id, 'title' => $title );
			}

			$out['catalog']['products'][] = array(
				'id'                 => $id,
				'title'              => $title,
				'price'              => $product['price'] ?? null,
				'image_count'        => count( $images ),
				'first_image'        => $images[0] ?? '',
				'description_length' => mb_strlen( $desc ),
			);
		} catch ( Throwable $error ) {
			$out['catalog']['errors'][] = array( 'url' => $url, 'error' => $error->getMessage() );
		}
	}
	$out['checks']['39_products'] = 39 === count( $urls );
	$out['checks']['flowers_title'] = $flowers_ok;
	$out['checks']['all_have_images'] = empty( $out['catalog']['empty_images'] );
	$out['checks']['all_image_urls_valid'] = empty( $out['catalog']['bad_images'] );
	$out['checks']['all_have_descriptions'] = empty( $out['catalog']['empty_descriptions'] );
	$out['checks']['descriptions_clean'] = empty( $out['catalog']['noisy_descriptions'] );
	$out['checks']['encoding_clean'] = empty( $out['catalog']['encoding_issues'] );
	$out['checks']['catalog_scrape_errors'] = empty( $out['catalog']['errors'] );

	global $wpdb;
	$table = MDO_Database::table( 'source_products' );
	$source_row = $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$table} WHERE supplier_id = %d AND source_product_id = %s ORDER BY id DESC LIMIT 1",
			(int) $supplier['id'],
			'32'
		),
		ARRAY_A
	);
	if ( ! $source_row || empty( $source_row['wc_product_id'] ) ) {
		throw new RuntimeException( 'No se encontró el producto de prueba Patatas blancas ya importado.' );
	}

	$fresh = MDO_Connector_Huerta_Ana_Mary::scrape_product( (string) $source_row['source_url'] );
	MDO_Connector_Huerta_Ana_Mary::upsert_product( (int) $supplier['id'], $fresh );
	$product_id = MDO_Woo_Importer::import_source_product( (int) $source_row['id'] );
	$product    = wc_get_product( $product_id );
	$categories = wp_get_object_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
	$categories = is_wp_error( $categories ) ? array() : array_map( 'intval', $categories );

	$out['smoke_import'] = array(
		'product_id'          => $product_id,
		'name'                => $product ? $product->get_name() : '',
		'image_id'            => $product ? (int) $product->get_image_id() : 0,
		'gallery_count'       => $product ? count( $product->get_gallery_image_ids() ) : 0,
		'category_ids'        => $categories,
		'has_target_category' => in_array( (int) $term->term_id, $categories, true ),
		'description'         => $product ? wp_strip_all_tags( $product->get_description() ) : '',
	);
	$out['checks']['smoke_product_same_id'] = 12699 === (int) $product_id;
	$out['checks']['smoke_has_image']       = $product && (int) $product->get_image_id() > 0;
	$out['checks']['smoke_category']        = in_array( (int) $term->term_id, $categories, true );
	$out['checks']['smoke_description_clean'] = $product && ! preg_match( '/(?:Te interesa|Carrito de la Compra|Seguir Comprando|Subtotal:)/iu', wp_strip_all_tags( $product->get_description() ) );

	$out['ok'] = ! in_array( false, $out['checks'], true );
} catch ( Throwable $error ) {
	$out['fatal_error'] = $error->getMessage();
	$out['ok'] = false;
}

echo wp_json_encode( $out, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
exit( $out['ok'] ? 0 : 5 );
