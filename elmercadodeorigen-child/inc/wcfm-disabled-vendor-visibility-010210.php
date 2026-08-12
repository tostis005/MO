<?php
/**
 * Oculta al público los productos de tiendas WCFM desactivadas u offline.
 *
 * Los administradores conservan visibilidad completa para poder auditar y
 * gestionar el catálogo. Para el resto de usuarios, esos productos se tratan
 * como inexistentes en consultas, loops, shortcodes y compra.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Solo los administradores pueden ver productos de tiendas desactivadas.
 */
function elmercado_wcfm_disabled_visibility_can_view_010210(): bool {
	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Interpreta de forma segura los flags usados por WCFM.
 *
 * @param mixed $value Valor de user meta.
 */
function elmercado_wcfm_status_flag_is_on_010210( $value ): bool {
	if ( is_bool( $value ) ) {
		return $value;
	}

	if ( is_int( $value ) || is_float( $value ) ) {
		return 0 !== (int) $value;
	}

	if ( is_string( $value ) ) {
		$normalized = strtolower( trim( $value ) );
		return ! in_array( $normalized, array( '', '0', 'no', 'false', 'off', 'none' ), true );
	}

	return ! empty( $value );
}

/**
 * Indica si WCFM considera al vendedor desactivado o con la tienda offline.
 */
function elmercado_wcfm_vendor_is_disabled_010210( int $user_id ): bool {
	if ( $user_id <= 0 ) {
		return false;
	}

	$user = get_userdata( $user_id );
	if ( ! $user instanceof WP_User ) {
		return false;
	}

	$roles = array_map( 'sanitize_key', (array) $user->roles );
	if ( in_array( 'disable_vendor', $roles, true ) ) {
		return true;
	}

	if ( elmercado_wcfm_status_flag_is_on_010210( get_user_meta( $user_id, '_disable_vendor', true ) ) ) {
		return true;
	}

	return elmercado_wcfm_status_flag_is_on_010210( get_user_meta( $user_id, '_wcfm_store_offline', true ) );
}

/**
 * Devuelve autores con productos publicados cuya tienda WCFM está desactivada.
 * La lista se calcula una sola vez por petición.
 *
 * @return int[]
 */
function elmercado_wcfm_disabled_vendor_ids_010210(): array {
	static $disabled_ids = null;

	if ( is_array( $disabled_ids ) ) {
		return $disabled_ids;
	}

	global $wpdb;

	$author_ids = $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT DISTINCT post_author FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s AND post_author > 0",
			'product',
			'publish'
		)
	);

	$disabled_ids = array();
	foreach ( array_map( 'intval', (array) $author_ids ) as $author_id ) {
		if ( elmercado_wcfm_vendor_is_disabled_010210( $author_id ) ) {
			$disabled_ids[] = $author_id;
		}
	}

	$disabled_ids = array_values( array_unique( array_filter( $disabled_ids ) ) );
	return $disabled_ids;
}

/**
 * Comprueba el vendedor real de un producto o variación.
 */
function elmercado_wcfm_product_is_from_disabled_vendor_010210( int $product_id ): bool {
	if ( $product_id <= 0 ) {
		return false;
	}

	$post = get_post( $product_id );
	if ( ! $post instanceof WP_Post ) {
		return false;
	}

	if ( 'product_variation' === $post->post_type && $post->post_parent > 0 ) {
		$post = get_post( (int) $post->post_parent );
		if ( ! $post instanceof WP_Post ) {
			return false;
		}
	}

	return 'product' === $post->post_type && elmercado_wcfm_vendor_is_disabled_010210( (int) $post->post_author );
}

/**
 * Determina si una WP_Query puede devolver productos en el frontend.
 */
function elmercado_wcfm_query_targets_products_010210( WP_Query $query ): bool {
	if ( $query->is_singular( 'product' ) || $query->is_post_type_archive( 'product' ) ) {
		return true;
	}

	if ( $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) {
		return true;
	}

	if ( $query->is_tax() ) {
		$taxonomy = (string) $query->get( 'taxonomy' );
		if ( 0 === strpos( $taxonomy, 'pa_' ) ) {
			return true;
		}
	}

	$post_type = $query->get( 'post_type' );
	if ( 'product' === $post_type ) {
		return true;
	}

	return is_array( $post_type ) && in_array( 'product', $post_type, true );
}

/**
 * Fusiona autores bloqueados con author__not_in sin destruir otros filtros.
 *
 * @param array<string,mixed> $args Argumentos de consulta.
 * @return array<string,mixed>
 */
function elmercado_wcfm_exclude_disabled_authors_from_args_010210( array $args ): array {
	if ( elmercado_wcfm_disabled_visibility_can_view_010210() ) {
		return $args;
	}

	$disabled = elmercado_wcfm_disabled_vendor_ids_010210();
	if ( ! $disabled ) {
		return $args;
	}

	$current                = isset( $args['author__not_in'] ) ? array_map( 'intval', (array) $args['author__not_in'] ) : array();
	$args['author__not_in'] = array_values( array_unique( array_merge( $current, $disabled ) ) );
	return $args;
}

/**
 * Defensa principal para Tienda, categorías, búsquedas de producto y singles.
 * Se ejecuta tarde para que ningún filtro de vendedor pueda reintroducir al autor.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() || ! elmercado_wcfm_query_targets_products_010210( $query ) ) {
			return;
		}

		$disabled = elmercado_wcfm_disabled_vendor_ids_010210();
		if ( ! $disabled ) {
			return;
		}

		$current = array_map( 'intval', (array) $query->get( 'author__not_in' ) );
		$query->set( 'author__not_in', array_values( array_unique( array_merge( $current, $disabled ) ) ) );
	},
	999
);

/**
 * Cubre consultas de shortcodes/bloques de WooCommerce.
 */
add_filter(
	'woocommerce_shortcode_products_query',
	static function ( array $query_args ): array {
		return elmercado_wcfm_exclude_disabled_authors_from_args_010210( $query_args );
	},
	999
);

/**
 * Cubre WC_Product_Query allí donde la consulta acabe pasando por WP_Query.
 */
add_action(
	'woocommerce_product_query',
	static function ( $query ): void {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() || ! is_object( $query ) || ! method_exists( $query, 'get' ) || ! method_exists( $query, 'set' ) ) {
			return;
		}
		$disabled = elmercado_wcfm_disabled_vendor_ids_010210();
		if ( ! $disabled ) {
			return;
		}
		$current = array_map( 'intval', (array) $query->get( 'author__not_in' ) );
		$query->set( 'author__not_in', array_values( array_unique( array_merge( $current, $disabled ) ) ) );
	},
	999
);

/**
 * Última barrera para consultas personalizadas que no hayan declarado post_type.
 */
add_filter(
	'the_posts',
	static function ( array $posts, WP_Query $query ): array {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() || ! $posts ) {
			return $posts;
		}

		return array_values(
			array_filter(
				$posts,
				static function ( $post ): bool {
					if ( ! $post instanceof WP_Post || ! in_array( $post->post_type, array( 'product', 'product_variation' ), true ) ) {
						return true;
					}
					return ! elmercado_wcfm_product_is_from_disabled_vendor_010210( (int) $post->ID );
				}
			)
		);
	},
	999,
	2
);

/**
 * WooCommerce no debe considerar visible un producto de una tienda bloqueada.
 */
add_filter(
	'woocommerce_product_is_visible',
	static function ( bool $visible, int $product_id ): bool {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() ) {
			return $visible;
		}
		return elmercado_wcfm_product_is_from_disabled_vendor_010210( $product_id ) ? false : $visible;
	},
	999,
	2
);

/**
 * Evita compras por enlaces antiguos, carrito persistente o cachés externas.
 */
add_filter(
	'woocommerce_is_purchasable',
	static function ( bool $purchasable, $product ): bool {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() || ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
			return $purchasable;
		}
		return elmercado_wcfm_product_is_from_disabled_vendor_010210( (int) $product->get_id() ) ? false : $purchasable;
	},
	999,
	2
);

add_filter(
	'woocommerce_variation_is_purchasable',
	static function ( bool $purchasable, $variation ): bool {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() || ! is_object( $variation ) || ! method_exists( $variation, 'get_id' ) ) {
			return $purchasable;
		}
		return elmercado_wcfm_product_is_from_disabled_vendor_010210( (int) $variation->get_id() ) ? false : $purchasable;
	},
	999,
	2
);

/**
 * Limpia recomendaciones/relacionados precalculados.
 */
add_filter(
	'woocommerce_related_products',
	static function ( array $related_posts ): array {
		if ( elmercado_wcfm_disabled_visibility_can_view_010210() ) {
			return $related_posts;
		}
		return array_values(
			array_filter(
				array_map( 'intval', $related_posts ),
				static fn ( int $product_id ): bool => ! elmercado_wcfm_product_is_from_disabled_vendor_010210( $product_id )
			)
		);
	},
	999
);
