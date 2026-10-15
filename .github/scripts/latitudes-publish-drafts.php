<?php
/**
 * Publicación puntual de borradores de Latitudes desde GitHub Actions / WP-CLI.
 *
 * Ejecución: LATITUDES_MODE=dry-run|publish wp eval-file <file> --skip-themes
 *
 * No requiere modificar el tema ni los ajustes de otros vendedores.
 * Es idempotente: nunca publica artículos que ya no estén en borrador.
 */
if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( 1 );
}

$mode = (string) getenv( 'LATITUDES_MODE' );
if ( ! in_array( $mode, array( 'dry-run', 'publish' ), true ) ) {
	WP_CLI::error( 'Modo inválido; usa dry-run o publish.' );
}
$home_host = strtolower( (string) wp_parse_url( home_url( '/' ), PHP_URL_HOST ) );
if ( ! in_array( $home_host, array( 'elmercadodeorigen.com', 'www.elmercadodeorigen.com' ), true ) ) {
	WP_CLI::error( 'El servidor no corresponde a producción de El Mercado de Origen.' );
}
if ( ! function_exists( 'wc_get_product' ) || ! post_type_exists( 'product' ) ) {
	WP_CLI::error( 'WooCommerce no está activo.' );
}
$vendors = get_users(
	array(
		'role__in' => array( 'wcfm_vendor', 'vendor', 'dc_vendor', 'seller' ),
		'number' => 1000,
	)
);
$candidates = array();
foreach ( $vendors as $vendor ) {
	$profile = get_user_meta( (int) $vendor->ID, 'wcfmmp_profile_settings', true );
	$store_name = is_array( $profile ) ? (string) ( $profile['store_name'] ?? '' ) : '';
	$store_url = function_exists( 'wcfmmp_get_store_url' ) ? (string) wcfmmp_get_store_url( $vendor->ID ) : '';
	$path = (string) wp_parse_url( $store_url, PHP_URL_PATH );
	$parts = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
	$store_slug = sanitize_title( (string) end( $parts ) );
	if ( 'latitudes' === sanitize_title( (string) $vendor->user_nicename )
		|| 'latitudes' === sanitize_title( (string) $vendor->user_login )
		|| 'latitudes' === sanitize_title( $store_name )
		|| 'latitudes' === $store_slug ) {
		$candidates[ (int) $vendor->ID ] = $vendor;
	}
}
if ( 1 !== count( $candidates ) ) {
	WP_CLI::error( 'Se esperaba exactamente un vendedor WCFM Latitudes; encontrados: ' . count( $candidates ) );
}
$vendor_id = (int) key( $candidates );
WP_CLI::log( 'Proveedor Latitudes: #' . $vendor_id . ' | modo: ' . $mode );

if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' )
	&& elmercado_wcfm_vendor_is_disabled_010210( $vendor_id ) ) {
	WP_CLI::error( 'El productor está desactivado. No se publica ningún artículo.' );
}

$draft_ids = get_posts(
	array(
		'post_type' => 'product',
		'post_status' => 'draft',
		'author' => $vendor_id,
		'posts_per_page' => -1,
		'fields' => 'ids',
		'no_found_rows' => true,
		'suppress_filters' => true,
	)
);
$counts = array( 'drafts' => count( $draft_ids ), 'eligible' => 0, 'published' => 0, 'skipped' => 0, 'failed' => 0 );
$errors = array();
foreach ( $draft_ids as $product_id ) {
	$product_id = (int) $product_id;
	$product = wc_get_product( $product_id );
	$reason = '';
	if ( ! $product ) {
		$reason = 'producto inválido';
	} elseif ( '' === trim( (string) $product->get_name() ) ) {
		$reason = 'sin nombre';
	} elseif ( '' === (string) $product->get_price() ) {
		$reason = 'sin precio';
	} elseif ( ! $product->get_image_id() ) {
		$reason = 'sin imagen principal';
	} elseif ( ! $product->is_in_stock() ) {
		$reason = 'sin stock';
	} elseif ( in_array( $product->get_catalog_visibility(), array( 'hidden', 'search' ), true ) ) {
		$reason = 'oculto del catálogo';
	} elseif ( function_exists( 'wcfm_get_vendor_id_by_post' ) ) {
		$owner = (int) wcfm_get_vendor_id_by_post( $product_id );
		if ( $owner > 0 && $owner !== $vendor_id ) {
			$reason = 'otro propietario WCFM #' . $owner;
		}
	}
	if ( '' !== $reason ) {
		++$counts['skipped'];
		WP_CLI::log( 'OMITIR #' . $product_id . ': ' . $reason );
		continue;
	}
	++$counts['eligible'];
	if ( 'dry-run' === $mode ) {
		WP_CLI::log( 'APTO #' . $product_id . ': ' . $product->get_name() );
		continue;
	}
	// Evitar que otra importación cambie estado/autor antes de publicar.
	if ( 'draft' !== get_post_status( $product_id )
		|| $vendor_id !== (int) get_post_field( 'post_author', $product_id ) ) {
		++$counts['failed'];
		$errors[] = $product_id;
		continue;
	}
	$updated = wp_update_post(
		array( 'ID' => $product_id, 'post_status' => 'publish' ),
		true
	);
	clean_post_cache( $product_id );
	if ( is_wp_error( $updated ) || 'publish' !== get_post_status( $product_id ) ) {
		++$counts['failed'];
		$errors[] = $product_id;
		WP_CLI::warning( 'No se pudo publicar #' . $product_id );
		continue;
	}
	++$counts['published'];
	WP_CLI::log( 'PUBLICADO #' . $product_id );
}

if ( 'publish' === $mode && $counts['published'] > 0 ) {
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	}
	if ( class_exists( 'WC_Cache_Helper' ) ) {
		WC_Cache_Helper::incr_cache_prefix( 'product' );
	}
	// El tema usa un drop-in de caché que sirve /tienda/ antes de WordPress.
	foreach ( array(
		WP_CONTENT_DIR . '/uploads/elmercado-hot-static-v1/es-shop-root.html',
		WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html',
		WP_CONTENT_DIR . '/cache/elmercado-home-static/index.html',
	) as $file ) {
		if ( is_file( $file ) ) {
			if ( @unlink( $file ) ) {
				WP_CLI::log( 'Caché HTML purgada: ' . basename( $file ) );
			} else {
				WP_CLI::warning( 'No se pudo borrar un archivo de caché: ' . basename( $file ) );
			}
		}
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'w3tc_flush_all' ) ) {
		w3tc_flush_all();
	}
	if ( has_action( 'litespeed_purge_all' ) ) {
		do_action( 'litespeed_purge_all' );
	}
	wp_cache_flush();
	WP_CLI::log( 'Transitorios y caché de catálogo invalidados.' );
}

WP_CLI::log( 'RESUMEN=' . wp_json_encode( $counts ) );
if ( $errors ) {
	WP_CLI::error( 'Algunos borradores aptos fallaron al publicar. IDs: ' . implode( ', ', $errors ) );
}
