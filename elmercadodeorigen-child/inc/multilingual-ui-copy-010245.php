<?php
/**
 * Persisted multilingual copy for storefront UI that is not represented by
 * normal translated posts/terms.
 *
 * English strings are seeded once into wp_options and the storefront only
 * reads those persisted values. There is deliberately no DOM scanner,
 * MutationObserver or client-side translation pass.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const ELMERCADO_EN_UI_OPTION_010245         = 'elmercado_en_ui_copy_010245';
const ELMERCADO_EN_UI_OPTION_VERSION_010245 = 'elmercado_en_ui_copy_version_010245';
const ELMERCADO_EN_UI_VERSION_010245        = '2026-08-18.2';

function elmercado_current_language_slug_010245(): string {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	if ( preg_match( '#^/(en|pt|fr|it)(?:/|$)#i', $path, $matches ) ) {
		return strtolower( $matches[1] );
	}

	$referer      = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
	$referer_path = (string) wp_parse_url( $referer, PHP_URL_PATH );
	if ( preg_match( '#^/(en|pt|fr|it)(?:/|$)#i', $referer_path, $matches ) ) {
		return strtolower( $matches[1] );
	}

	global $falang_core;
	if ( function_exists( 'falang_current_language' ) && isset( $falang_core ) && is_object( $falang_core ) ) {
		$language = falang_current_language( 'slug' );
		if ( is_string( $language ) && '' !== $language ) {
			return strtolower( $language );
		}
	}

	return 'es';
}

function elmercado_is_english_request_010245(): bool {
	return 'en' === elmercado_current_language_slug_010245();
}

/**
 * Seed values. They are copied to wp_options and are not used as a browser-side
 * translation table.
 *
 * @return array<string,string>
 */
function elmercado_manual_english_ui_defaults_010245(): array {
	return array(
		'Buscar' => 'Search',
		'Filtros' => 'Filters',
		'Filtrar productos' => 'Filter products',
		'Cerrar filtros' => 'Close filters',
		'Filtros activos' => 'Active filters',
		'Filtros aplicados' => 'Applied filters',
		'Limpiar todo' => 'Clear all',
		'Categorías' => 'Categories',
		'Vendedor' => 'Seller',
		'Precio' => 'Price',
		'Recomendados' => 'Recommended',
		'Más populares' => 'Most popular',
		'Mejor valorados' => 'Top rated',
		'Más recientes' => 'Newest',
		'Menor precio' => 'Lowest price',
		'Mayor precio' => 'Highest price',
		'VISITAR' => 'VISIT',
		'Visitar' => 'Visit',
		'TU SELECCIÓN' => 'YOUR SELECTION',
		'Revisa tu carrito' => 'Review your cart',
		'Comprueba cantidades y productos antes de continuar. Verás el coste final y las opciones disponibles en el siguiente paso.' => 'Check quantities and products before continuing. You’ll see the final cost and available options in the next step.',
		'Pago protegido durante todo el proceso' => 'Secure payment throughout the process',
		'Información clara antes de confirmar' => 'Clear information before you confirm',
		'Atención cercana si necesitas ayuda' => 'Personal support if you need help',
		'Alimentación' => 'Feeding',
		'Calidad' => 'Quality',
		'Con DOP' => 'With PDO',
		'Curación' => 'Curing',
		'Denominación de origen' => 'Protected Designation of Origin',
		'Origen' => 'Origin',
		'Peso' => 'Weight',
		'Preparación' => 'Preparation',
		'Productor' => 'Producer',
		'Raza ibérica' => 'Iberian breed',
		'Tamaño' => 'Size',
		'Tipo de pieza' => 'Piece type',
		'Tipo de producto' => 'Product type',
		'Variedad' => 'Variety',
		'Compra por categoría' => 'Shop by category',
		'Encuentra lo que buscas por categoría' => 'Find what you are looking for by category',
		'Hemos agrupado los productos por categorías para que puedas encontrar fácilmente el tipo de producto que buscas.' => 'We have grouped products by category so you can easily find the type of product you are looking for.',
		'Ver todas las categorías' => 'View all categories',
		'Categorías de producto' => 'Product categories',
		'Todas las categorías' => 'All categories',
		'Aquí encontrarás todos los productos agrupados por categorías. Entra en la que te interese para ver la selección completa.' => 'Here you will find all products grouped by category. Open the one you are interested in to see the full selection.',
		'Elige una categoría' => 'Choose a category',
		'Cada categoría reúne productos del mismo tipo para que puedas encontrarlos y compararlos más fácilmente.' => 'Each category brings together products of the same type so you can find and compare them more easily.',
		'Ver categoría' => 'View category',
		'No hay categorías para mostrar.' => 'There are no categories to show.',
		'%s producto' => '%s product',
		'%s productos' => '%s products',
		'Explora la selección disponible de %s, con origen claro, productor visible y disponibilidad actual.' => 'Explore the available %s selection, with clear origin, visible producer and current availability.',
	);
}

/** Persist seed translations in the database without overwriting later edits. */
function elmercado_seed_manual_english_ui_010245(): void {
	if ( ELMERCADO_EN_UI_VERSION_010245 === (string) get_option( ELMERCADO_EN_UI_OPTION_VERSION_010245, '' ) ) {
		return;
	}

	$stored = get_option( ELMERCADO_EN_UI_OPTION_010245, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	$next = array_merge( elmercado_manual_english_ui_defaults_010245(), $stored );
	update_option( ELMERCADO_EN_UI_OPTION_010245, $next, false );
	update_option( ELMERCADO_EN_UI_OPTION_VERSION_010245, ELMERCADO_EN_UI_VERSION_010245, false );
}
add_action( 'init', 'elmercado_seed_manual_english_ui_010245', -100 );

/** @return array<string,string> */
function elmercado_manual_english_ui_map_010245(): array {
	$map = get_option( ELMERCADO_EN_UI_OPTION_010245, array() );
	return is_array( $map ) ? $map : array();
}

/** Read one persisted English UI string, falling back to the source text. */
function elmercado_ui_copy_010245( string $source ): string {
	if ( ! elmercado_is_english_request_010245() ) {
		return $source;
	}
	$map = elmercado_manual_english_ui_map_010245();
	return isset( $map[ $source ] ) && is_string( $map[ $source ] ) && '' !== trim( $map[ $source ] )
		? $map[ $source ]
		: $source;
}

/* Server-side lookup only: values come from wp_options; no live translation. */
add_filter(
	'gettext',
	static function ( string $translated, string $text, string $domain ): string {
		if ( ! elmercado_is_english_request_010245() ) {
			return $translated;
		}
		$map = elmercado_manual_english_ui_map_010245();
		return $map[ $text ] ?? $translated;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'woocommerce_attribute_label',
	static function ( string $label, string $name, $product ): string {
		if ( ! elmercado_is_english_request_010245() ) {
			return $label;
		}
		$map = elmercado_manual_english_ui_map_010245();
		return $map[ $label ] ?? $label;
	},
	PHP_INT_MAX,
	3
);

/**
 * WooCommerce can build a product-category archive title from the raw taxonomy
 * object. On /en/ force the already-persisted Falang term name from termmeta.
 */
function elmercado_english_product_category_title_010245( string $title ): string {
	if ( ! elmercado_is_english_request_010245() || ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return $title;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
		return $title;
	}
	if ( '1' !== (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) {
		return $title;
	}

	$name = trim( (string) get_term_meta( $term->term_id, '_en_US_name', true ) );
	return '' !== $name ? $name : $title;
}
add_filter( 'woocommerce_page_title', 'elmercado_english_product_category_title_010245', PHP_INT_MAX );
add_filter( 'single_term_title', 'elmercado_english_product_category_title_010245', PHP_INT_MAX );

/* The storefront intentionally does not expose the WCFM Policies product tab. */
add_filter(
	'woocommerce_product_tabs',
	static function ( array $tabs ): array {
		unset( $tabs['wcfm_policies_tab'], $tabs['policies'] );
		return $tabs;
	},
	PHP_INT_MAX
);
