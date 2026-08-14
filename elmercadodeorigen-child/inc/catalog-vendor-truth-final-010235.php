<?php
/**
 * Verdad final de pertenencia y paginación de las tiendas WCFM.
 *
 * El catálogo del productor debe basarse en el vendedor que WCFM asigna a cada
 * producto. Este cierre también impide que los totales/páginas globales de
 * Tienda sustituyan los del productor.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IDs publicados que WCFM atribuye realmente a un vendedor.
 *
 * @return int[]
 */
function elmercado_vendor_owned_product_ids_010235( int $vendor_id ): array {
	static $cache = array();

	$vendor_id = absint( $vendor_id );
	if ( $vendor_id <= 0 ) {
		return array();
	}
	if ( isset( $cache[ $vendor_id ] ) ) {
		return $cache[ $vendor_id ];
	}

	global $wpdb;
	$candidates = array_values(
		array_filter(
			array_map(
				'absint',
				(array) $wpdb->get_col(
					$wpdb->prepare(
						"SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status = %s",
						'product',
						'publish'
					)
				)
			)
		)
	);

	$owned = array();
	foreach ( $candidates as $product_id ) {
		$owner_id = function_exists( 'wcfm_get_vendor_id_by_post' )
			? absint( wcfm_get_vendor_id_by_post( $product_id ) )
			: absint( get_post_field( 'post_author', $product_id ) );
		if ( $owner_id === $vendor_id ) {
			$owned[] = $product_id;
		}
	}

	$cache[ $vendor_id ] = array_values( array_unique( $owned ) );
	return $cache[ $vendor_id ];
}

/**
 * Hace que las consultas de verdad del panel y cualquier loop de la tienda
 * queden intersectados con los productos que WCFM asigna al vendedor actual.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$is_product_query = 'product' === $post_type
			|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
			|| $query->is_post_type_archive( 'product' )
			|| $query->is_tax( 'dc_vendor_shop' );
		if ( ! $is_product_query && ! $query->get( 'emo_vendor_store_truth_010225' ) ) {
			return;
		}

		$vendor_id = function_exists( 'elmercado_vendor_store_vendor_id_010225' )
			? absint( elmercado_vendor_store_vendor_id_010225() )
			: 0;
		if ( $vendor_id <= 0 ) {
			return;
		}

		$owned   = elmercado_vendor_owned_product_ids_010235( $vendor_id );
		$current = array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) );
		$allowed = $current ? array_values( array_intersect( $current, $owned ) ) : $owned;
		$query->set( 'post__in', $allowed ?: array( 0 ) );
	},
	PHP_INT_MAX
);

/**
 * Total exacto del productor ya con categoría, atributos y precio aplicados.
 */
function elmercado_vendor_exact_total_010235(): int {
	if ( ! function_exists( 'elmercado_vendor_store_state_010225' ) ) {
		return 0;
	}
	$state = elmercado_vendor_store_state_010225();
	return max( 0, (int) ( $state['total'] ?? 0 ) );
}

/**
 * Última palabra sobre found_posts en una tienda: nunca el total global.
 */
add_filter(
	'found_posts',
	static function ( $found_posts, WP_Query $query ) {
		if ( is_admin()
			|| $query->get( 'emo_vendor_store_truth_010225' )
			|| ! function_exists( 'elmercado_vendor_store_is_request_010225' )
			|| ! elmercado_vendor_store_is_request_010225() ) {
			return $found_posts;
		}

		$post_type = $query->get( 'post_type' );
		$is_product_query = 'product' === $post_type
			|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
			|| $query->is_post_type_archive( 'product' )
			|| $query->is_tax( 'dc_vendor_shop' );
		return $is_product_query ? elmercado_vendor_exact_total_010235() : $found_posts;
	},
	PHP_INT_MAX,
	2
);

/**
 * Sincroniza también max_num_pages después de cualquier capa global previa.
 */
add_filter(
	'the_posts',
	static function ( array $posts, WP_Query $query ): array {
		if ( is_admin()
			|| $query->get( 'emo_vendor_store_truth_010225' )
			|| ! function_exists( 'elmercado_vendor_store_is_request_010225' )
			|| ! elmercado_vendor_store_is_request_010225() ) {
			return $posts;
		}

		$post_type = $query->get( 'post_type' );
		$is_product_query = 'product' === $post_type
			|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
			|| $query->is_post_type_archive( 'product' )
			|| $query->is_tax( 'dc_vendor_shop' );
		if ( ! $is_product_query ) {
			return $posts;
		}

		$total = elmercado_vendor_exact_total_010235();
		$query->found_posts = $total;
		$per_page = (int) $query->get( 'posts_per_page' );
		if ( $per_page <= 0 ) {
			$per_page = max( 1, (int) get_option( 'posts_per_page', 12 ) );
		}
		$query->max_num_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;
		return $posts;
	},
	PHP_INT_MAX,
	2
);
