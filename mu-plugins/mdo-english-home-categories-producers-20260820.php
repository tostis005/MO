<?php
/**
 * Plugin Name: MDO English Home Categories and Producer CTA
 * Description: Keeps English home categories in parity with Spanish and normalizes producer-card CTA copy.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_EN_CATEGORY_SEED_VERSION_010262 = '2026-08-20.1';
const MDO_EN_CATEGORY_SEED_OPTION_010262  = 'mdo_en_category_seed_version_010262';

/**
 * English names for storefront product categories that may be created/imported
 * before Falang has received a manual translation.
 *
 * Existing non-empty Falang names always win: this table only fills gaps.
 *
 * @return array<string,string>
 */
function elmercado_english_category_fallbacks_010262(): array {
	return array(
		'aceites'               => 'Oils',
		'carnes'                => 'Meat',
		'jamones-y-paletas'      => 'Hams and shoulders',
		'embutidos-y-curados'    => 'Cured meats',
		'embutidos'              => 'Cured meats',
		'packs-y-lotes'          => 'Packs and bundles',
		'pack-gourmet'           => 'Gourmet packs',
		'packs-gourmet'          => 'Gourmet packs',
		'quesos'                 => 'Cheese',
		'naranjas'               => 'Oranges',
		'hortalizas-verduras'    => 'Vegetables',
		'hortalizas-y-verduras'  => 'Vegetables',
		'verduras-y-hortalizas'  => 'Vegetables',
		'hortalizas'             => 'Vegetables',
		'verduras'               => 'Vegetables',
		'conservas'              => 'Preserves',
		'legumbres'              => 'Pulses',
		'accesorios'             => 'Accessories',
		'adobados'               => 'Marinated meats',
		'frutas'                 => 'Fruit',
		'frutas-y-verduras'      => 'Fruit and vegetables',
		'frutas-y-hortalizas'    => 'Fruit and vegetables',
		'miel'                   => 'Honey',
		'dulces'                 => 'Sweets',
	);
}

/** Detect /en/ even before the child-theme language helper is loaded. */
function elmercado_is_english_request_010262(): bool {
	if ( function_exists( 'elmercado_is_english_request_010245' ) ) {
		return elmercado_is_english_request_010245();
	}

	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	return 1 === preg_match( '#^/en(?:/|$)#i', $path );
}

/**
 * Seed missing English product-category metadata used by the final Home renderer.
 * Do not overwrite an existing English name entered manually in Falang.
 */
function elmercado_seed_english_categories_010262(): void {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$map   = elmercado_english_category_fallbacks_010262();
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
		)
	);
	if ( is_wp_error( $terms ) ) {
		return;
	}

	$changed = false;
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}

		$slug = sanitize_title( (string) $term->slug );
		if ( ! isset( $map[ $slug ] ) ) {
			/* Importers occasionally add a numeric suffix to a known category slug. */
			$base_slug = preg_replace( '/-\d+$/', '', $slug );
			$slug      = is_string( $base_slug ) ? $base_slug : $slug;
		}
		if ( ! isset( $map[ $slug ] ) ) {
			continue;
		}

		$term_id       = (int) $term->term_id;
		$existing_name = trim( (string) get_term_meta( $term_id, '_en_US_name', true ) );
		if ( '' === $existing_name ) {
			update_term_meta( $term_id, '_en_US_name', $map[ $slug ] );
			$changed = true;
		}
		if ( '1' !== (string) get_term_meta( $term_id, '_en_US_published', true ) ) {
			update_term_meta( $term_id, '_en_US_published', '1' );
			$changed = true;
		}
	}

	$version_changed = MDO_EN_CATEGORY_SEED_VERSION_010262 !== (string) get_option( MDO_EN_CATEGORY_SEED_OPTION_010262, '' );
	if ( $version_changed ) {
		update_option( MDO_EN_CATEGORY_SEED_OPTION_010262, MDO_EN_CATEGORY_SEED_VERSION_010262, false );
	}

	/* One-time cache invalidation so the English Home does not keep an older category block. */
	if ( $changed || $version_changed ) {
		wp_cache_flush();
		if ( function_exists( 'rocket_clean_domain' ) ) {
			rocket_clean_domain();
		}
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
		}
		if ( has_action( 'litespeed_purge_all' ) ) {
			do_action( 'litespeed_purge_all' );
		}
	}
}
add_action( 'init', 'elmercado_seed_english_categories_010262', 40 );

/**
 * Normalize the producer-directory CTA on English requests regardless of the
 * WCFM text domain or whether a Spanish translation has already been applied.
 */
function elmercado_english_producer_visit_label_010262( string $translated, string $text, string $domain ): string {
	unset( $domain );
	if ( is_admin() || ! elmercado_is_english_request_010262() ) {
		return $translated;
	}

	$candidates = array( trim( $text ), trim( $translated ) );
	$labels     = array( 'Visit Store', 'Visitar Tienda', 'Visitar tienda', 'VISITAR TIENDA', 'Visitar', 'VISITAR' );
	return array_intersect( $candidates, $labels ) ? 'Visit' : $translated;
}
add_filter( 'gettext', 'elmercado_english_producer_visit_label_010262', PHP_INT_MAX, 3 );

/**
 * Some producer-card templates contain the Spanish CTA as literal HTML instead
 * of gettext. Limit the fallback replacement strictly to the English producer
 * directory so no editorial copy is touched elsewhere.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! elmercado_is_english_request_010262() ) {
			return;
		}

		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$path = trim( (string) wp_parse_url( $uri, PHP_URL_PATH ), '/' );
		if ( ! preg_match( '#^en/(?:producers|productores)/?$#i', $path ) ) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				return str_replace(
					array( '>Visitar Tienda<', '>Visitar tienda<', '>VISITAR TIENDA<', '>Visit Store<' ),
					array( '>Visit<', '>Visit<', '>Visit<', '>Visit<' ),
					$html
				);
			}
		);
	},
	PHP_INT_MAX
);
