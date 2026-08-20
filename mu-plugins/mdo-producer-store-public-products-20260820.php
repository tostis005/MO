<?php
/**
 * Plugin Name: MDO Producer Store Public Products
 * Description: Keeps public WCFM producer stores scoped to their own products and normalizes the producer CTA.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detecta de forma tolerante el contexto de una tienda WCFM.
 */
function elmercado_public_store_fix_is_store_010261(): bool {
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) ) {
		return elmercado_vendor_store_is_request_010225();
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

/**
 * Obtiene el vendedor de la tienda actual usando primero la resolución WCFM.
 */
function elmercado_public_store_fix_vendor_id_010261(): int {
	if ( function_exists( 'elmercado_vendor_store_vendor_id_010225' ) ) {
		$vendor_id = absint( elmercado_vendor_store_vendor_id_010225() );
		if ( $vendor_id > 0 ) {
			return $vendor_id;
		}
	}

	$store_url = function_exists( 'wcfm_get_option' )
		? (string) wcfm_get_option( 'wcfm_store_url', 'store' )
		: (string) get_option( 'wcfm_store_url', 'store' );
	$store_url = trim( $store_url, '/' );
	if ( '' === $store_url ) {
		return 0;
	}

	$store_name = (string) get_query_var( $store_url );
	$store_name = (string) apply_filters( 'wcfmmp_store_query_var', $store_name );
	if ( '' === trim( $store_name ) ) {
		return 0;
	}

	$user = get_user_by( 'slug', sanitize_title( $store_name ) );
	return $user instanceof WP_User ? absint( $user->ID ) : 0;
}

/**
 * Solo corrige tiendas que ya son públicas según la política EMDO/WCFM.
 */
function elmercado_public_store_fix_vendor_allowed_010261( int $vendor_id ): bool {
	if ( $vendor_id <= 0 ) {
		return false;
	}
	if ( function_exists( 'elmercado_vendor_store_vendor_is_publicly_allowed_010225' ) ) {
		return elmercado_vendor_store_vendor_is_publicly_allowed_010225( $vendor_id );
	}
	if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) ) {
		return ! elmercado_wcfm_vendor_is_disabled_010210( $vendor_id );
	}
	return true;
}

/**
 * Determina si una consulta pertenece al loop de productos de la tienda actual.
 */
function elmercado_public_store_fix_is_product_query_010261( WP_Query $query ): bool {
	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type
		|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
		|| $query->is_post_type_archive( 'product' )
		|| $query->is_tax( 'dc_vendor_shop' )
		|| ( $query->is_main_query() && elmercado_public_store_fix_is_store_010261() );
}

/**
 * Registra el cierre en wp_loaded: el tema ya ha declarado sus callbacks, pero
 * WordPress todavía no ha ejecutado la consulta principal. De este modo, a la
 * misma prioridad PHP_INT_MAX, esta corrección queda detrás de las capas
 * históricas del catálogo y puede retirar solo la contradicción del vendedor.
 */
add_action(
	'wp_loaded',
	static function (): void {
		add_action(
			'pre_get_posts',
			static function ( WP_Query $query ): void {
				if ( is_admin() || ! elmercado_public_store_fix_is_store_010261() || ! elmercado_public_store_fix_is_product_query_010261( $query ) ) {
					return;
				}

				$vendor_id = elmercado_public_store_fix_vendor_id_010261();
				if ( ! elmercado_public_store_fix_vendor_allowed_010261( $vendor_id ) ) {
					return;
				}

				$query->set( 'post_type', 'product' );
				$query->set( 'post_status', 'publish' );
				$query->set( 'author', $vendor_id );

				/*
				 * La página Tienda puede haber añadido el productor actual a
				 * author__not_in por destino de envío. Dentro de SU tienda esa
				 * exclusión es contradictoria y se elimina, sin tocar otros autores.
				 */
				$excluded = array_values(
					array_filter(
						array_map( 'absint', (array) $query->get( 'author__not_in' ) ),
						static fn ( int $id ): bool => $id > 0 && $id !== $vendor_id
					)
				);
				$query->set( 'author__not_in', $excluded );
			},
			PHP_INT_MAX
		);

		add_filter(
			'posts_clauses',
			static function ( array $clauses, WP_Query $query ): array {
				if ( is_admin() || ! elmercado_public_store_fix_is_store_010261() || ! elmercado_public_store_fix_is_product_query_010261( $query ) || empty( $clauses['where'] ) ) {
					return $clauses;
				}

				$vendor_id = elmercado_public_store_fix_vendor_id_010261();
				if ( ! elmercado_public_store_fix_vendor_allowed_010261( $vendor_id ) ) {
					return $clauses;
				}

				/*
				 * catalog-query-parity-010224 añade también el NOT IN directamente
				 * al SQL. Se elimina solo el predicado que contiene al vendedor de
				 * esta tienda; stock, visibilidad, categorías y precio permanecen.
				 */
				global $wpdb;
				$table   = preg_quote( (string) $wpdb->posts, '~' );
				$pattern = '~\\s+AND\\s+' . $table . '\\.post_author\\s+NOT\\s+IN\\s*\\(\\s*([0-9\\s,]+)\\s*\\)~i';
				$clauses['where'] = (string) preg_replace_callback(
					$pattern,
					static function ( array $matches ) use ( $vendor_id ): string {
						$ids = array_values(
							array_filter(
								array_map( 'absint', preg_split( '/\\s*,\\s*/', trim( (string) $matches[1] ) ) ?: array() )
							)
						);
						return in_array( $vendor_id, $ids, true ) ? '' : (string) $matches[0];
					},
					(string) $clauses['where']
				);

				return $clauses;
			},
			PHP_INT_MAX,
			2
		);
	},
	PHP_INT_MAX
);

/**
 * Copy del CTA de WCFM: "Visit Store" -> "Visit".
 * Se limita a los dominios del marketplace para no tocar textos editoriales.
 */
function elmercado_public_store_fix_visit_label_010261( string $translated, string $text, string $domain ): string {
	if ( is_admin() || ! in_array( $domain, array( 'wc-multivendor-marketplace', 'wc-frontend-manager' ), true ) ) {
		return $translated;
	}

	if ( 'Visit Store' !== trim( $text ) && 'Visit Store' !== trim( $translated ) ) {
		return $translated;
	}

	return 'Visit';
}
add_filter( 'gettext', 'elmercado_public_store_fix_visit_label_010261', PHP_INT_MAX, 3 );
