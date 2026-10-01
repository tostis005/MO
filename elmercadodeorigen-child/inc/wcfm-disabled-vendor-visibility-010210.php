<?php
/**
 * Política de visibilidad para tiendas WCFM Offline y Disabled.
 *
 * - Offline (_wcfm_store_offline): oculto al público, visible en frontend para
 *   administradores para poder auditar el catálogo.
 * - Disabled (_disable_vendor / rol disable_vendor): excluido del frontend para
 *   todos, incluidos administradores. Sigue existiendo en wp-admin para gestión.
 *
 * Los productos se mantienen publicados; esta capa decide si forman parte o no
 * del storefront, consultas, filtros, conteos, relacionados y compra.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Solo los administradores pueden auditar en frontend una tienda Offline.
 * Este permiso nunca hace visibles tiendas marcadas como Disabled.
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
 * Estado fuerte: vendedor retirado del marketplace.
 */
function elmercado_wcfm_vendor_is_hard_disabled_010210( int $user_id ): bool {
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

	return elmercado_wcfm_status_flag_is_on_010210( get_user_meta( $user_id, '_disable_vendor', true ) );
}

/**
 * Estado temporal: tienda desconectada/offline.
 */
function elmercado_wcfm_vendor_is_offline_010210( int $user_id ): bool {
	return $user_id > 0
		&& elmercado_wcfm_status_flag_is_on_010210( get_user_meta( $user_id, '_wcfm_store_offline', true ) );
}

/**
 * Compatibilidad con llamadas históricas: "disabled" incluye ambos flags.
 */
function elmercado_wcfm_vendor_is_disabled_010210( int $user_id ): bool {
	return elmercado_wcfm_vendor_is_hard_disabled_010210( $user_id )
		|| elmercado_wcfm_vendor_is_offline_010210( $user_id );
}

/**
 * Regla efectiva del frontend para el usuario actual.
 */
function elmercado_wcfm_vendor_is_hidden_010210( int $user_id ): bool {
	if ( elmercado_wcfm_vendor_is_hard_disabled_010210( $user_id ) ) {
		return true;
	}

	return elmercado_wcfm_vendor_is_offline_010210( $user_id )
		&& ! elmercado_wcfm_disabled_visibility_can_view_010210();
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
 * Devuelve vendedores retirados de forma absoluta, tengan o no productos
 * publicados. Se usa también para directorios/listados de productores.
 *
 * @return int[]
 */
function elmercado_wcfm_hard_disabled_vendor_ids_010210(): array {
	static $ids = null;

	if ( is_array( $ids ) ) {
		return $ids;
	}

	$ids = array();

	$role_ids = get_users(
		array(
			'role'   => 'disable_vendor',
			'fields' => 'ids',
		)
	);
	$ids = array_merge( $ids, array_map( 'absint', (array) $role_ids ) );

	$meta_users = get_users(
		array(
			'meta_key'     => '_disable_vendor',
			'meta_compare' => 'EXISTS',
			'fields'       => 'ids',
		)
	);
	foreach ( array_map( 'absint', (array) $meta_users ) as $user_id ) {
		if ( elmercado_wcfm_vendor_is_hard_disabled_010210( $user_id ) ) {
			$ids[] = $user_id;
		}
	}

	$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );
	return $ids;
}

/**
 * Vendedores que deben quedar ocultos en el frontend para el usuario actual.
 * Disabled siempre; Offline solo para visitantes/no administradores.
 *
 * @return int[]
 */
function elmercado_wcfm_hidden_vendor_ids_010210(): array {
	$hidden = elmercado_wcfm_hard_disabled_vendor_ids_010210();

	if ( elmercado_wcfm_disabled_visibility_can_view_010210() ) {
		return $hidden;
	}

	foreach ( elmercado_wcfm_disabled_vendor_ids_010210() as $vendor_id ) {
		if ( elmercado_wcfm_vendor_is_offline_010210( (int) $vendor_id ) ) {
			$hidden[] = (int) $vendor_id;
		}
	}

	return array_values( array_unique( array_filter( array_map( 'absint', $hidden ) ) ) );
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

	return 'product' === $post->post_type && elmercado_wcfm_vendor_is_hidden_010210( (int) $post->post_author );
}

/**
 * Devuelve el vendedor solicitado por el filtro público, si existe.
 */
function elmercado_wcfm_requested_vendor_id_010210(): int {
	return isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Si se conserva una URL de filtro de un vendedor bloqueado, la consulta debe
 * ser vacía en lugar de caer de nuevo al catálogo general.
 */
function elmercado_wcfm_block_disabled_vendor_filter_010210( WP_Query $query ): void {
	$requested_vendor = elmercado_wcfm_requested_vendor_id_010210();
	if ( $requested_vendor <= 0 || ! elmercado_wcfm_vendor_is_hidden_010210( $requested_vendor ) ) {
		return;
	}

	$current = array_map( 'intval', (array) $query->get( 'post__in' ) );
	$query->set( 'post__in', $current ? array_values( array_intersect( $current, array( 0 ) ) ) : array( 0 ) );
	$query->set( 'author', 0 );
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
	$disabled = elmercado_wcfm_hidden_vendor_ids_010210();
	if ( ! $disabled ) {
		return $args;
	}

	$current                = isset( $args['author__not_in'] ) ? array_map( 'intval', (array) $args['author__not_in'] ) : array();
	$args['author__not_in'] = array_values( array_unique( array_merge( $current, $disabled ) ) );

	$requested_vendor = elmercado_wcfm_requested_vendor_id_010210();
	if ( $requested_vendor > 0 && in_array( $requested_vendor, $disabled, true ) ) {
		$args['post__in'] = array( 0 );
		unset( $args['author'] );
	}

	return $args;
}

/**
 * Defensa principal para Tienda, categorías, búsquedas de producto y singles.
 * Se ejecuta tarde para que ningún filtro de vendedor pueda reintroducir al autor.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( ! elmercado_wcfm_query_targets_products_010210( $query ) ) {
			return;
		}

		$disabled = elmercado_wcfm_hidden_vendor_ids_010210();
		if ( ! $disabled ) {
			return;
		}

		$current = array_map( 'intval', (array) $query->get( 'author__not_in' ) );
		$query->set( 'author__not_in', array_values( array_unique( array_merge( $current, $disabled ) ) ) );
		elmercado_wcfm_block_disabled_vendor_filter_010210( $query );
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
		if ( ! is_object( $query ) || ! method_exists( $query, 'get' ) || ! method_exists( $query, 'set' ) ) {
			return;
		}
		$disabled = elmercado_wcfm_hidden_vendor_ids_010210();
		if ( ! $disabled ) {
			return;
		}
		$current = array_map( 'intval', (array) $query->get( 'author__not_in' ) );
		$query->set( 'author__not_in', array_values( array_unique( array_merge( $current, $disabled ) ) ) );

		$requested_vendor = elmercado_wcfm_requested_vendor_id_010210();
		if ( $requested_vendor > 0 && in_array( $requested_vendor, $disabled, true ) ) {
			$query->set( 'post__in', array( 0 ) );
			$query->set( 'author', 0 );
		}
	},
	999
);

/**
 * Última barrera para consultas personalizadas que no hayan declarado post_type.
 */
add_filter(
	'the_posts',
	static function ( array $posts, WP_Query $query ): array {
		if ( ! $posts ) {
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
		if ( ! is_object( $product ) || ! method_exists( $product, 'get_id' ) ) {
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
		if ( ! is_object( $variation ) || ! method_exists( $variation, 'get_id' ) ) {
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
		return array_values(
			array_filter(
				array_map( 'intval', $related_posts ),
				static fn ( int $product_id ): bool => ! elmercado_wcfm_product_is_from_disabled_vendor_010210( $product_id )
			)
		);
	},
	999
);

/**
 * Los listados de productores de WCFM nunca deben incluir Disabled.
 * Offline mantiene el comportamiento nativo de WCFM.
 */
function elmercado_wcfm_append_hard_disabled_vendor_exclusions_010210( $ids ): array {
	$ids = is_array( $ids ) ? $ids : array();
	return array_values(
		array_unique(
			array_filter(
				array_map(
					'absint',
					array_merge( $ids, elmercado_wcfm_hard_disabled_vendor_ids_010210() )
				)
			)
		)
	);
}

add_filter(
	'wcfm_exclude_vendors_list',
	static function ( $ids ) {
		return elmercado_wcfm_append_hard_disabled_vendor_exclusions_010210( $ids );
	},
	999
);

add_filter(
	'wcfmmp_exclude_vendors_list',
	static function ( $ids ) {
		return elmercado_wcfm_append_hard_disabled_vendor_exclusions_010210( $ids );
	},
	999
);

add_filter(
	'wcfmmp_store_list_card_valid',
	static function ( $store_id ) {
		$vendor_id = absint( $store_id );
		return $vendor_id > 0 && elmercado_wcfm_vendor_is_hard_disabled_010210( $vendor_id )
			? false
			: $store_id;
	},
	999
);

/**
 * Una tienda Disabled tampoco debe abrirse directamente aunque el visitante sea
 * administrador. El backend /wp-admin/ no se toca.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! function_exists( 'wcfm_is_store_page' ) || ! wcfm_is_store_page() ) {
			return;
		}

		$store_key = function_exists( 'wcfm_get_option' )
			? (string) wcfm_get_option( 'wcfm_store_url', 'store' )
			: 'store';
		$slug = sanitize_title( (string) get_query_var( $store_key ) );
		if ( '' === $slug ) {
			return;
		}

		$user = get_user_by( 'slug', $slug );
		if ( ! $user instanceof WP_User || ! elmercado_wcfm_vendor_is_hard_disabled_010210( (int) $user->ID ) ) {
			return;
		}

		global $wp_query;
		if ( $wp_query instanceof WP_Query ) {
			$wp_query->set_404();
		}
		status_header( 404 );
		nocache_headers();
	},
	-999
);

/**
 * Defensa de Home: si el hero de productores fue construido por una capa
 * personalizada que no pasa por el listado nativo de WCFM, eliminamos las
 * tarjetas de vendedores Disabled por su URL de tienda y corregimos el contador
 * de tarjetas usado por el layout.
 */
function elmercado_wcfm_strip_hard_disabled_home_vendors_010210( string $html ): string {
	if ( '' === $html || false === strpos( $html, 'emo-hero__visual--vendors' ) ) {
		return $html;
	}

	foreach ( elmercado_wcfm_hard_disabled_vendor_ids_010210() as $vendor_id ) {
		$store_url = function_exists( 'wcfmmp_get_store_url' )
			? (string) wcfmmp_get_store_url( (int) $vendor_id )
			: '';
		if ( '' === $store_url ) {
			continue;
		}

		$quoted = preg_quote( untrailingslashit( $store_url ), '~' );
		$html = (string) preg_replace(
			'~<a\\b(?=[^>]*\\bclass=(["\\\'])[^"\\\']*\\bemo-hero-card\\b[^"\\\']*\\1)(?=[^>]*\\bhref=(["\\\'])' . $quoted . '/?\\2)[^>]*>.*?</a>~is',
			'',
			$html
		);
	}

	if ( preg_match( '~<div\\b[^>]*\\bclass=(["\\\'])[^"\\\']*\\bemo-hero__visual--vendors\\b[^"\\\']*\\1[^>]*>(.*?)</div>~is', $html, $match ) ) {
		/*
		 * Cuenta tarjetas reales, no ocurrencias de la cadena "emo-hero-card".
		 * Cada tarjeta también tiene una clase como emo-hero-card--1, por lo que
		 * contar la cadena duplicaba el total (6 => 12) y rompía el grid.
		 */
		$count = preg_match_all(
			'~<a\\b[^>]*\\bclass=(["\\\'])[^"\\\']*\\bemo-hero-card\\b[^"\\\']*\\1[^>]*>~is',
			(string) $match[2]
		);
		if ( is_int( $count ) && $count >= 0 ) {
			$visual = (string) preg_replace( '~\\bemo-vendor-count-\\d+\\b~', 'emo-vendor-count-' . $count, (string) $match[0], 1 );
			$visual = (string) preg_replace( '~\\bdata-emo-vendor-count=(["\\\'])\\d+\\1~', 'data-emo-vendor-count="' . $count . '"', $visual, 1 );
			$html   = substr_replace( $html, $visual, (int) strpos( $html, $match[0] ), strlen( $match[0] ) );
		}
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || ! is_front_page() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}
		ob_start( 'elmercado_wcfm_strip_hard_disabled_home_vendors_010210' );
	},
	-10000
);

/**
 * Los productos de vendedores Disabled tampoco deben formar parte de sitemaps.
 * Offline conserva su semántica temporal y no se fuerza aquí.
 */
add_filter(
	'wp_sitemaps_posts_query_args',
	static function ( array $args, string $post_type ): array {
		if ( 'product' !== $post_type ) {
			return $args;
		}
		$current = isset( $args['author__not_in'] ) ? array_map( 'absint', (array) $args['author__not_in'] ) : array();
		$args['author__not_in'] = array_values(
			array_unique(
				array_merge( $current, elmercado_wcfm_hard_disabled_vendor_ids_010210() )
			)
		);
		return $args;
	},
	999,
	2
);

add_filter(
	'wpseo_sitemap_entry',
	static function ( $url, string $type, $object ) {
		if (
			'post' === $type
			&& $object instanceof WP_Post
			&& 'product' === $object->post_type
			&& elmercado_wcfm_vendor_is_hard_disabled_010210( (int) $object->post_author )
		) {
			return false;
		}
		return $url;
	},
	999,
	3
);

