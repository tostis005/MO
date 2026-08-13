<?php
/**
 * Catálogo y filtros contextuales de las tiendas de productor.
 *
 * Lleva a las tiendas WCFM la misma lógica de catálogo que Tienda, pero con el
 * productor fijado por la URL: total exacto, precio, categorías propias y los
 * atributos de la familia seleccionada. Nunca ofrece un filtro de vendedor.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detecta una petición de tienda WCFM incluso durante consultas secundarias.
 */
function elmercado_vendor_store_is_request_010225(): bool {
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	$parts       = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
	$endpoint    = trim( (string) get_option( 'wcfm_store_url', 'tienda' ), '/' );
	$position    = array_search( $endpoint, $parts, true );

	return false !== $position && isset( $parts[ $position + 1 ] );
}

/**
 * Slug de la tienda a partir del endpoint real configurado por WCFM.
 */
function elmercado_vendor_store_slug_010225(): string {
	static $slug = null;
	if ( null !== $slug ) {
		return $slug;
	}

	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );
	$parts       = array_values( array_filter( explode( '/', trim( $path, '/' ) ), 'strlen' ) );
	$endpoint    = trim( (string) get_option( 'wcfm_store_url', 'tienda' ), '/' );
	$position    = array_search( $endpoint, $parts, true );

	$slug = false !== $position && isset( $parts[ $position + 1 ] )
		? sanitize_title( rawurldecode( (string) $parts[ $position + 1 ] ) )
		: '';

	return $slug;
}

/**
 * ID del productor dueño de la tienda actual.
 */
function elmercado_vendor_store_vendor_id_010225(): int {
	static $vendor_id = null;
	if ( null !== $vendor_id ) {
		return $vendor_id;
	}

	$vendor_id = 0;
	$slug      = elmercado_vendor_store_slug_010225();
	if ( '' === $slug ) {
		return 0;
	}

	$user = get_user_by( 'slug', $slug );
	if ( ! $user instanceof WP_User ) {
		$user = get_user_by( 'login', $slug );
	}
	if ( $user instanceof WP_User ) {
		$vendor_id = (int) $user->ID;
	}

	return $vendor_id;
}

/**
 * URL canónica de la tienda actual.
 */
function elmercado_vendor_store_url_010225( int $vendor_id = 0 ): string {
	$vendor_id = $vendor_id ?: elmercado_vendor_store_vendor_id_010225();
	if ( $vendor_id > 0 && function_exists( 'wcfmmp_get_store_url' ) ) {
		return trailingslashit( (string) wcfmmp_get_store_url( $vendor_id ) );
	}

	$slug = elmercado_vendor_store_slug_010225();
	return home_url( '/' . trim( (string) get_option( 'wcfm_store_url', 'tienda' ), '/' ) . '/' . $slug . '/' );
}

/**
 * La única excepción de visibilidad por usuario sigue siendo la de vendedores
 * desactivados: solo un administrador puede entrar en su catálogo.
 */
function elmercado_vendor_store_vendor_is_publicly_allowed_010225( int $vendor_id ): bool {
	if ( $vendor_id <= 0 ) {
		return false;
	}

	if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) && elmercado_wcfm_vendor_is_disabled_010210( $vendor_id ) ) {
		return function_exists( 'elmercado_catalog_counts_can_view_disabled_010217' )
			? elmercado_catalog_counts_can_view_disabled_010217()
			: current_user_can( 'manage_options' );
	}

	return true;
}

/**
 * Términos product_visibility que nunca pertenecen al catálogo visible.
 *
 * @return int[]
 */
function elmercado_vendor_store_excluded_visibility_terms_010225(): array {
	if ( function_exists( 'elmercado_catalog_excluded_visibility_term_ids_010218' ) ) {
		return array_values( array_filter( array_map( 'absint', elmercado_catalog_excluded_visibility_term_ids_010218() ) ) );
	}

	if ( ! function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		return array();
	}

	$ids = wc_get_product_visibility_term_ids();
	return array_values(
		array_filter(
			array_map(
				'absint',
				array(
					$ids['exclude-from-catalog'] ?? 0,
					$ids['outofstock'] ?? 0,
				)
			)
		)
	);
}

/**
 * Convierte los límites de precio actuales en IDs permitidos usando la tabla
 * de lookup de WooCommerce, también válida para productos variables.
 *
 * @return int[]|null Null significa que no hay filtro de precio.
 */
function elmercado_vendor_store_price_product_ids_010225( ?float $min_price, ?float $max_price ): ?array {
	if ( null === $min_price && null === $max_price ) {
		return null;
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wc_product_meta_lookup';
	$where = array();
	$args  = array();

	if ( null !== $min_price ) {
		$where[] = 'max_price >= %f';
		$args[]  = max( 0, $min_price );
	}
	if ( null !== $max_price ) {
		$where[] = 'min_price <= %f';
		$args[]  = max( 0, $max_price );
	}

	$sql = "SELECT product_id FROM {$table} WHERE " . implode( ' AND ', $where ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	if ( $args ) {
		$sql = $wpdb->prepare( $sql, $args ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	return array_values( array_filter( array_map( 'absint', (array) $wpdb->get_col( $sql ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

/**
 * Perfil de atributos para un término de categoría concreto.
 */
function elmercado_vendor_store_profile_for_term_010225( ?WP_Term $term ): ?array {
	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy || ! function_exists( 'elmercado_catalog_filter_profiles' ) ) {
		return null;
	}

	$profiles = elmercado_catalog_filter_profiles();
	$term_ids = array_merge(
		array( (int) $term->term_id ),
		array_map( 'intval', get_ancestors( (int) $term->term_id, 'product_cat', 'taxonomy' ) )
	);

	foreach ( array_values( array_unique( $term_ids ) ) as $term_id ) {
		$candidate = get_term( $term_id, 'product_cat' );
		if ( ! $candidate instanceof WP_Term ) {
			continue;
		}

		if ( 'adobados' === $candidate->slug ) {
			return $profiles['adobados'] ?? null;
		}
		if ( 'embutidos-y-curados' === $candidate->slug ) {
			return $profiles['cured'] ?? null;
		}

		$haystack = remove_accents( strtolower( $candidate->name . ' ' . $candidate->slug ) );
		$has_ham  = (bool) preg_match( '/\bjamon(?:es)?\b/u', $haystack );
		$has_pork = (bool) preg_match( '/\bpaleta(?:s)?\b/u', $haystack );
		if ( $has_ham && $has_pork ) {
			return $profiles['ham'] ?? null;
		}
	}

	return null;
}

/**
 * Filtros de atributos elegidos, limitados al perfil activo.
 *
 * @return array<string,string[]>
 */
function elmercado_vendor_store_selected_attributes_010225( ?array $profile ): array {
	if ( ! $profile ) {
		return array();
	}

	$selected = array();
	foreach ( array_keys( (array) ( $profile['attributes'] ?? array() ) ) as $attribute_slug ) {
		/* Productor es redundante: ya estamos dentro de un productor concreto. */
		if ( 'productor' === $attribute_slug ) {
			continue;
		}

		$key = 'filter_' . sanitize_title( $attribute_slug );
		if ( ! isset( $_GET[ $key ] ) || is_array( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			continue;
		}

		$raw   = sanitize_text_field( wp_unslash( (string) $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$terms = array_values( array_unique( array_filter( array_map( 'sanitize_title', explode( ',', $raw ) ) ) ) );
		if ( $terms ) {
			$selected[ $attribute_slug ] = $terms;
		}
	}

	return $selected;
}

/**
 * Precio elegido en la URL.
 *
 * @return array{0:?float,1:?float}
 */
function elmercado_vendor_store_selected_price_010225(): array {
	$min = null;
	$max = null;
	if ( isset( $_GET['min_price'] ) && ! is_array( $_GET['min_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = wc_format_decimal( wp_unslash( (string) $_GET['min_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$min   = '' !== $value ? max( 0, (float) $value ) : null;
	}
	if ( isset( $_GET['max_price'] ) && ! is_array( $_GET['max_price'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = wc_format_decimal( wp_unslash( (string) $_GET['max_price'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max   = '' !== $value ? max( 0, (float) $value ) : null;
	}
	if ( null !== $min && null !== $max && $min > $max ) {
		$temporary = $min;
		$min       = $max;
		$max       = $temporary;
	}
	return array( $min, $max );
}

/**
 * Conjunto exacto de productos del productor para un estado de filtros.
 *
 * @param array<string,string[]> $attribute_filters Filtros de atributos.
 * @return int[]
 */
function elmercado_vendor_store_product_ids_010225(
	int $vendor_id,
	?WP_Term $category = null,
	array $attribute_filters = array(),
	?float $min_price = null,
	?float $max_price = null
): array {
	if ( ! elmercado_vendor_store_vendor_is_publicly_allowed_010225( $vendor_id ) ) {
		return array();
	}

	$tax_query = array( 'relation' => 'AND' );
	$excluded  = elmercado_vendor_store_excluded_visibility_terms_010225();
	if ( $excluded ) {
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_id',
			'terms'    => $excluded,
			'operator' => 'NOT IN',
		);
	}

	if ( $category instanceof WP_Term ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => array( (int) $category->term_id ),
			'include_children' => true,
			'operator'         => 'IN',
		);
	}

	foreach ( $attribute_filters as $attribute_slug => $term_slugs ) {
		$taxonomy = wc_attribute_taxonomy_name( sanitize_title( $attribute_slug ) );
		if ( ! taxonomy_exists( $taxonomy ) || ! $term_slugs ) {
			continue;
		}
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => array_values( array_unique( array_map( 'sanitize_title', $term_slugs ) ) ),
			'operator' => 'IN',
		);
	}

	$args = array(
		'post_type'                     => 'product',
		'post_status'                   => 'publish',
		'author'                        => $vendor_id,
		'fields'                        => 'ids',
		'posts_per_page'                => -1,
		'no_found_rows'                 => true,
		'ignore_sticky_posts'           => true,
		'suppress_filters'              => true,
		'cache_results'                 => false,
		'update_post_meta_cache'        => false,
		'update_post_term_cache'        => false,
		'emo_vendor_store_truth_010225' => 1,
		'tax_query'                     => $tax_query,
	);

	$price_ids = elmercado_vendor_store_price_product_ids_010225( $min_price, $max_price );
	if ( null !== $price_ids ) {
		$args['post__in'] = $price_ids ?: array( 0 );
	}

	$query = new WP_Query( $args );
	return array_values( array_unique( array_filter( array_map( 'absint', (array) $query->posts ) ) ) );
}

/**
 * Categorías raíz realmente usadas por los productos visibles del productor.
 *
 * @param int[] $product_ids IDs visibles sin filtros de categoría.
 * @return array<int,array{term:WP_Term,ids:int[],count:int}>
 */
function elmercado_vendor_store_categories_010225( array $product_ids ): array {
	$categories = array();
	$default_id = (int) get_option( 'default_product_cat' );

	foreach ( array_values( array_unique( array_map( 'absint', $product_ids ) ) ) as $product_id ) {
		if ( $product_id <= 0 ) {
			continue;
		}
		$terms = wp_get_post_terms( $product_id, 'product_cat' );
		if ( is_wp_error( $terms ) ) {
			continue;
		}

		$roots_for_product = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term || (int) $term->term_id === $default_id ) {
				continue;
			}
			$ancestors = array_reverse( array_map( 'intval', get_ancestors( (int) $term->term_id, 'product_cat', 'taxonomy' ) ) );
			$root_id   = $ancestors ? (int) reset( $ancestors ) : (int) $term->term_id;
			if ( $root_id === $default_id || $root_id <= 0 ) {
				continue;
			}
			$roots_for_product[ $root_id ] = true;
		}

		foreach ( array_keys( $roots_for_product ) as $root_id ) {
			if ( ! isset( $categories[ $root_id ] ) ) {
				$root = get_term( $root_id, 'product_cat' );
				if ( ! $root instanceof WP_Term ) {
					continue;
				}
				$categories[ $root_id ] = array(
					'term'  => $root,
					'ids'   => array(),
					'count' => 0,
				);
			}
			$categories[ $root_id ]['ids'][ $product_id ] = $product_id;
		}
	}

	foreach ( $categories as $root_id => $data ) {
		$ids                               = array_values( array_unique( array_map( 'absint', (array) $data['ids'] ) ) );
		$categories[ $root_id ]['ids']     = $ids;
		$categories[ $root_id ]['count']   = count( $ids );
	}

	uasort(
		$categories,
		static function ( array $left, array $right ): int {
			return strnatcasecmp( (string) $left['term']->name, (string) $right['term']->name );
		}
	);

	return $categories;
}

/**
 * Categoría explícita o implícita (si el productor solo trabaja una familia).
 */
function elmercado_vendor_store_active_category_010225( array $categories ): ?WP_Term {
	if ( isset( $_GET['emo_vendor_cat'] ) && ! is_array( $_GET['emo_vendor_cat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$slug = sanitize_title( wp_unslash( (string) $_GET['emo_vendor_cat'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( $term instanceof WP_Term ) {
			foreach ( $categories as $data ) {
				$root = $data['term'] ?? null;
				if ( ! $root instanceof WP_Term ) {
					continue;
				}
				if ( (int) $root->term_id === (int) $term->term_id || term_is_ancestor_of( (int) $root->term_id, (int) $term->term_id, 'product_cat' ) ) {
					return $term;
				}
			}
		}
	}

	if ( 1 === count( $categories ) ) {
		$only = reset( $categories );
		return isset( $only['term'] ) && $only['term'] instanceof WP_Term ? $only['term'] : null;
	}

	return null;
}

/**
 * Estado actual completo, calculado una sola vez por petición.
 */
function elmercado_vendor_store_state_010225(): array {
	static $state = null;
	if ( null !== $state ) {
		return $state;
	}

	$vendor_id       = elmercado_vendor_store_vendor_id_010225();
	$base_ids        = $vendor_id > 0 ? elmercado_vendor_store_product_ids_010225( $vendor_id ) : array();
	$categories      = elmercado_vendor_store_categories_010225( $base_ids );
	$active_category = elmercado_vendor_store_active_category_010225( $categories );
	$profile         = elmercado_vendor_store_profile_for_term_010225( $active_category );
	$attributes      = elmercado_vendor_store_selected_attributes_010225( $profile );
	list( $min_price, $max_price ) = elmercado_vendor_store_selected_price_010225();
	$filtered_ids = $vendor_id > 0
		? elmercado_vendor_store_product_ids_010225( $vendor_id, $active_category, $attributes, $min_price, $max_price )
		: array();

	$state = array(
		'vendor_id'       => $vendor_id,
		'base_ids'        => $base_ids,
		'categories'      => $categories,
		'active_category' => $active_category,
		'profile'         => $profile,
		'attributes'      => $attributes,
		'min_price'       => $min_price,
		'max_price'       => $max_price,
		'filtered_ids'    => $filtered_ids,
		'total'           => count( $filtered_ids ),
		'implicit_cat'    => 1 === count( $categories ) && ! isset( $_GET['emo_vendor_cat'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	);

	return $state;
}

/**
 * Construye la tax_query que debe recibir cualquier loop de productos WCFM.
 *
 * @param array<int|string,mixed> $tax_query Consulta existente.
 * @return array<int|string,mixed>
 */
function elmercado_vendor_store_merge_tax_query_010225( array $tax_query, array $state ): array {
	if ( ! isset( $tax_query['relation'] ) ) {
		$tax_query['relation'] = 'AND';
	}

	$excluded = elmercado_vendor_store_excluded_visibility_terms_010225();
	if ( $excluded ) {
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_id',
			'terms'    => $excluded,
			'operator' => 'NOT IN',
		);
	}

	if ( $state['active_category'] instanceof WP_Term ) {
		$tax_query[] = array(
			'taxonomy'         => 'product_cat',
			'field'            => 'term_id',
			'terms'            => array( (int) $state['active_category']->term_id ),
			'include_children' => true,
			'operator'         => 'IN',
		);
	}

	foreach ( (array) $state['attributes'] as $attribute_slug => $term_slugs ) {
		$taxonomy = wc_attribute_taxonomy_name( sanitize_title( $attribute_slug ) );
		if ( ! taxonomy_exists( $taxonomy ) || ! $term_slugs ) {
			continue;
		}
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => $term_slugs,
			'operator' => 'IN',
		);
	}

	return $tax_query;
}

/**
 * Hace que tanto el query principal de WooCommerce como el query interno de
 * WCFM consuman el mismo conjunto de reglas. pre_get_posts se ejecuta también
 * sobre WP_Query secundarios, así que no dependemos de una API privada de WCFM.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! elmercado_vendor_store_is_request_010225() || $query->get( 'emo_vendor_store_truth_010225' ) ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$is_product_query = 'product' === $post_type
			|| ( is_array( $post_type ) && in_array( 'product', $post_type, true ) )
			|| $query->is_post_type_archive( 'product' )
			|| $query->is_tax( 'dc_vendor_shop' );

		if ( ! $is_product_query ) {
			return;
		}

		$state     = elmercado_vendor_store_state_010225();
		$vendor_id = (int) $state['vendor_id'];
		if ( $vendor_id <= 0 ) {
			return;
		}

		$query->set( 'post_type', 'product' );
		$query->set( 'post_status', 'publish' );
		$query->set( 'author', $vendor_id );
		$query->set( 'tax_query', elmercado_vendor_store_merge_tax_query_010225( (array) $query->get( 'tax_query' ), $state ) );

		if ( ! elmercado_vendor_store_vendor_is_publicly_allowed_010225( $vendor_id ) ) {
			$query->set( 'post__in', array( 0 ) );
			return;
		}

		$price_ids = elmercado_vendor_store_price_product_ids_010225( $state['min_price'], $state['max_price'] );
		if ( null !== $price_ids ) {
			$current = array_values( array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) ) );
			$allowed = $price_ids ?: array( 0 );
			$query->set( 'post__in', $current ? array_values( array_intersect( $current, $allowed ) ) ?: array( 0 ) : $allowed );
		}
	},
	PHP_INT_MAX
);

/**
 * Conserva solo parámetros de catálogo seguros al construir enlaces/formularios.
 *
 * @return array<string,string>
 */
function elmercado_vendor_store_preserved_args_010225( array $exclude = array() ): array {
	$args = array();
	foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$key = sanitize_key( (string) $key );
		if ( in_array( $key, $exclude, true ) || is_array( $value ) ) {
			continue;
		}
		if ( 'emo_vendor_cat' !== $key && 'min_price' !== $key && 'max_price' !== $key && 'orderby' !== $key && 0 !== strpos( $key, 'filter_' ) && 0 !== strpos( $key, 'query_type_' ) ) {
			continue;
		}
		$args[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
	}
	return $args;
}

/**
 * URL de un cambio de filtro dentro de la misma tienda.
 */
function elmercado_vendor_store_filter_url_010225( array $changes, array $remove = array() ): string {
	$args = elmercado_vendor_store_preserved_args_010225( $remove );
	foreach ( $remove as $key ) {
		unset( $args[ $key ] );
	}
	foreach ( $changes as $key => $value ) {
		$key = sanitize_key( (string) $key );
		if ( null === $value || '' === $value ) {
			unset( $args[ $key ] );
		} else {
			$args[ $key ] = (string) $value;
		}
	}

	return $args ? add_query_arg( $args, elmercado_vendor_store_url_010225() ) : elmercado_vendor_store_url_010225();
}

/**
 * URL para salir de una categoría: conserva precio/orden y elimina atributos.
 */
function elmercado_vendor_store_clear_category_url_010225(): string {
	$args = elmercado_vendor_store_preserved_args_010225();
	foreach ( array_keys( $args ) as $key ) {
		if ( 'emo_vendor_cat' === $key || 0 === strpos( $key, 'filter_' ) || 0 === strpos( $key, 'query_type_' ) ) {
			unset( $args[ $key ] );
		}
	}
	return $args ? add_query_arg( $args, elmercado_vendor_store_url_010225() ) : elmercado_vendor_store_url_010225();
}

/**
 * Rango real de precios para el contexto de categoría/atributos, sin recortar
 * por el propio precio actualmente seleccionado.
 *
 * @return array{0:float,1:float}
 */
function elmercado_vendor_store_price_bounds_010225( array $state ): array {
	$ids = elmercado_vendor_store_product_ids_010225(
		(int) $state['vendor_id'],
		$state['active_category'] instanceof WP_Term ? $state['active_category'] : null,
		(array) $state['attributes'],
		null,
		null
	);
	if ( ! $ids ) {
		return array( 0.0, 0.0 );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'wc_product_meta_lookup';
	$list  = implode( ',', array_map( 'absint', $ids ) );
	$row   = $wpdb->get_row( "SELECT MIN(min_price) AS minimum, MAX(max_price) AS maximum FROM {$table} WHERE product_id IN ({$list})" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
	$min   = $row ? (float) $row->minimum : 0.0;
	$max   = $row ? (float) $row->maximum : 0.0;
	return array( floor( max( 0, $min ) ), ceil( max( 0, $max ) ) );
}

/**
 * Cuenta términos de un atributo dentro de un conjunto exacto de productos.
 *
 * @return array<string,array{term:WP_Term,count:int}>
 */
function elmercado_vendor_store_attribute_counts_010225( array $product_ids, string $taxonomy ): array {
	$counts = array();
	foreach ( array_values( array_unique( array_map( 'absint', $product_ids ) ) ) as $product_id ) {
		if ( $product_id <= 0 ) {
			continue;
		}
		$terms = wp_get_post_terms( $product_id, $taxonomy );
		if ( is_wp_error( $terms ) ) {
			continue;
		}
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			if ( ! isset( $counts[ $term->slug ] ) ) {
				$counts[ $term->slug ] = array( 'term' => $term, 'count' => 0 );
			}
			$counts[ $term->slug ]['count']++;
		}
	}

	uasort(
		$counts,
		static function ( array $left, array $right ): int {
			return strnatcasecmp( (string) $left['term']->name, (string) $right['term']->name );
		}
	);
	return $counts;
}

/**
 * Panel de filtros propio de la tienda de productor.
 */
function elmercado_vendor_store_filter_panel_010225( array $state ): string {
	if ( (int) $state['vendor_id'] <= 0 ) {
		return '';
	}

	list( $bound_min, $bound_max ) = elmercado_vendor_store_price_bounds_010225( $state );
	$current_min = null !== $state['min_price'] ? max( $bound_min, (float) $state['min_price'] ) : $bound_min;
	$current_max = null !== $state['max_price'] ? min( $bound_max, (float) $state['max_price'] ) : $bound_max;
	if ( $current_max < $current_min ) {
		$current_max = $current_min;
	}

	ob_start();
	?>
	<div class="emo-vendor-filters" id="emo-vendor-filters" data-vendor-id="<?php echo esc_attr( (string) $state['vendor_id'] ); ?>">
		<div class="emo-vendor-filters__mobile-head">
			<strong>Filtros</strong>
			<button type="button" class="emo-vendor-filters__close" aria-label="Cerrar filtros">×</button>
		</div>

		<section class="widget woocommerce widget_price_filter emo-vendor-price-filter">
			<h2 class="widget-title">Filtrar por precio</h2>
			<form method="get" action="<?php echo esc_url( elmercado_vendor_store_url_010225() ); ?>">
				<div class="price_slider_wrapper">
					<div class="price_slider" style="display:none"></div>
					<div class="price_slider_amount" data-step="1">
						<input type="text" id="min_price" name="min_price" value="<?php echo esc_attr( wc_format_localized_price( $current_min ) ); ?>" data-min="<?php echo esc_attr( (string) $bound_min ); ?>" placeholder="Precio mínimo" />
						<input type="text" id="max_price" name="max_price" value="<?php echo esc_attr( wc_format_localized_price( $current_max ) ); ?>" data-max="<?php echo esc_attr( (string) $bound_max ); ?>" placeholder="Precio máximo" />
						<button type="submit" class="button">Filtrar</button>
						<div class="price_label" style="display:none">Precio: <span class="from"></span> — <span class="to"></span></div>
						<div class="clear"></div>
					</div>
				</div>
				<?php foreach ( elmercado_vendor_store_preserved_args_010225( array( 'min_price', 'max_price' ) ) as $key => $value ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
				<?php endforeach; ?>
			</form>
		</section>

		<?php if ( $state['active_category'] instanceof WP_Term ) : ?>
			<section class="widget emo-vendor-category-context" id="emo-vendor-category-context">
				<div class="emo-vendor-category-context__eyebrow">Categoría</div>
				<div class="emo-vendor-category-context__row">
					<strong><?php echo esc_html( $state['active_category']->name ); ?></strong>
					<?php if ( count( $state['categories'] ) > 1 && ! $state['implicit_cat'] ) : ?>
						<a href="<?php echo esc_url( elmercado_vendor_store_clear_category_url_010225() ); ?>" rel="nofollow">Quitar</a>
					<?php endif; ?>
				</div>
			</section>
		<?php elseif ( count( $state['categories'] ) > 1 ) : ?>
			<?php
			list( $category_min, $category_max ) = elmercado_vendor_store_selected_price_010225();
			$price_context_ids = elmercado_vendor_store_product_ids_010225( (int) $state['vendor_id'], null, array(), $category_min, $category_max );
			$price_context_set = array_fill_keys( $price_context_ids, true );
			?>
			<section class="widget widget_product_categories emo-vendor-category-filter" id="emo-vendor-category-filter">
				<h2 class="widget-title">Categorías</h2>
				<ul>
					<?php foreach ( $state['categories'] as $data ) : ?>
						<?php
						$term = $data['term'] ?? null;
						if ( ! $term instanceof WP_Term ) {
							continue;
						}
						$count = 0;
						foreach ( (array) $data['ids'] as $product_id ) {
							if ( isset( $price_context_set[ (int) $product_id ] ) ) {
								$count++;
							}
						}
						if ( $count <= 0 ) {
							continue;
						}
						$url = elmercado_vendor_store_filter_url_010225( array( 'emo_vendor_cat' => $term->slug ), array( 'emo_vendor_cat' ) );
						?>
						<li><a href="<?php echo esc_url( $url ); ?>" rel="nofollow"><span><?php echo esc_html( $term->name ); ?></span><small><?php echo esc_html( number_format_i18n( $count ) ); ?></small></a></li>
					<?php endforeach; ?>
				</ul>
			</section>
		<?php endif; ?>

		<?php if ( $state['active_category'] instanceof WP_Term && is_array( $state['profile'] ) ) : ?>
			<?php foreach ( (array) ( $state['profile']['attributes'] ?? array() ) as $attribute_slug => $label ) : ?>
				<?php
				if ( 'productor' === $attribute_slug ) {
					continue;
				}
				$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
				if ( ! taxonomy_exists( $taxonomy ) ) {
					continue;
				}
				$other_filters = (array) $state['attributes'];
				unset( $other_filters[ $attribute_slug ] );
				$context_ids = elmercado_vendor_store_product_ids_010225(
					(int) $state['vendor_id'],
					$state['active_category'],
					$other_filters,
					$state['min_price'],
					$state['max_price']
				);
				$counts   = elmercado_vendor_store_attribute_counts_010225( $context_ids, $taxonomy );
				$selected = (array) ( $state['attributes'][ $attribute_slug ] ?? array() );
				if ( ! $counts && ! $selected ) {
					continue;
				}
				?>
				<section class="widget woocommerce widget_layered_nav emo-vendor-attribute-filter" data-attribute="<?php echo esc_attr( $attribute_slug ); ?>">
					<h2 class="widget-title"><?php echo esc_html( $label ); ?></h2>
					<ul class="woocommerce-widget-layered-nav-list">
						<?php foreach ( $counts as $term_slug => $data ) : ?>
							<?php
							$term = $data['term'] ?? null;
							if ( ! $term instanceof WP_Term ) {
								continue;
							}
							$count       = (int) ( $data['count'] ?? 0 );
							$is_selected = in_array( $term_slug, $selected, true );
							if ( $count <= 0 && ! $is_selected ) {
								continue;
							}
							$next = $selected;
							if ( $is_selected ) {
								$next = array_values( array_diff( $next, array( $term_slug ) ) );
							} else {
								$next[] = $term_slug;
							}
							$filter_key = 'filter_' . sanitize_title( $attribute_slug );
							$url        = elmercado_vendor_store_filter_url_010225(
								array( $filter_key => $next ? implode( ',', array_values( array_unique( $next ) ) ) : null ),
								array()
							);
							?>
							<li class="wc-layered-nav-term<?php echo $is_selected ? ' chosen' : ''; ?>">
								<a href="<?php echo esc_url( $url ); ?>" rel="nofollow"><span><?php echo esc_html( $term->name ); ?></span><small><?php echo esc_html( number_format_i18n( $count ) ); ?></small></a>
							</li>
						<?php endforeach; ?>
					</ul>
				</section>
			<?php endforeach; ?>
		<?php endif; ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * El slider nativo de WooCommerce se reutiliza para conservar la experiencia de
 * Tienda; solo cambian su rango y su consulta, que quedan acotados al productor.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( is_admin() || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		if ( wp_script_is( 'wc-price-slider', 'registered' ) ) {
			wp_enqueue_script( 'wc-price-slider' );
		}
	},
	PHP_INT_MAX
);

/**
 * Monta el rail a la derecha, normaliza el total y crea el drawer móvil.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		$state = elmercado_vendor_store_state_010225();
		if ( (int) $state['vendor_id'] <= 0 ) {
			return;
		}
		$panel = elmercado_vendor_store_filter_panel_010225( $state );
		$total = max( 0, (int) $state['total'] );
		$label = sprintf( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ), number_format_i18n( $total ) );
		?>
		<template id="emo-vendor-filter-template-010225"><?php echo $panel; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
		<div class="emo-vendor-filter-overlay-010225" hidden></div>
		<script id="elmercado-vendor-store-catalog-010225">
		(() => {
			const store = document.querySelector('#wcfmmp-store');
			const template = document.querySelector('#emo-vendor-filter-template-010225');
			if (!store || !template) return;
			const exactLabel = <?php echo wp_json_encode( $label ); ?>;
			let syncing = false;

			const productHost = () => store.querySelector('.right_side, .right_side_full, .products-wrapper, .wcfmmp-store-product, .product_area');
			const sidebarHost = () => store.querySelector('.left_sidebar');
			const toolbarHost = () => store.querySelector('.elmercado-vendor-toolbar') || productHost();

			function syncCount() {
				let nodes = [...store.querySelectorAll('.woocommerce-result-count')];
				if (!nodes.length) {
					const host = toolbarHost();
					if (host) {
						const node = document.createElement('p');
						node.className = 'woocommerce-result-count emo-vendor-result-count-010225';
						host.prepend(node);
						nodes = [node];
					}
				}
				nodes.forEach((node) => {
					if (node.textContent.replace(/\s+/g, ' ').trim() !== exactLabel) node.textContent = exactLabel;
					node.classList.add('emo-vendor-result-count-010225');
					node.removeAttribute('aria-hidden');
					node.setAttribute('role', 'status');
					node.setAttribute('aria-live', 'polite');
				});
			}

			function initPriceSlider() {
				if (window.jQuery) {
					window.jQuery(document.body).trigger('init_price_filter');
				}
			}

			function ensureToggle() {
				const host = toolbarHost();
				if (!host || host.querySelector('.emo-vendor-filter-toggle-010225')) return;
				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'emo-vendor-filter-toggle-010225';
				button.setAttribute('aria-controls', 'emo-vendor-filters');
				button.setAttribute('aria-expanded', 'false');
				button.innerHTML = '<span aria-hidden="true">☰</span> Filtrar';
				host.appendChild(button);
			}

			function mountPanel() {
				const body = store.querySelector('.body_area');
				const sidebar = sidebarHost();
				const products = productHost();
				if (!body || !sidebar || !products) return;
				if (!sidebar.querySelector('#emo-vendor-filters')) {
					sidebar.innerHTML = template.innerHTML;
					sidebar.classList.add('emo-vendor-filter-rail-010225');
				}
				/* Mismo orden semántico que Tienda: catálogo primero, filtros después. */
				if (products.parentElement === body && sidebar.parentElement === body) {
					body.insertBefore(products, sidebar);
				}
				ensureToggle();
				initPriceSlider();
			}

			function setOpen(open) {
				document.documentElement.classList.toggle('emo-vendor-filters-open-010225', open);
				const overlay = document.querySelector('.emo-vendor-filter-overlay-010225');
				if (overlay) overlay.hidden = !open;
				const toggle = store.querySelector('.emo-vendor-filter-toggle-010225');
				if (toggle) toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
			}

			document.addEventListener('click', (event) => {
				if (event.target.closest('.emo-vendor-filter-toggle-010225')) setOpen(true);
				if (event.target.closest('.emo-vendor-filters__close, .emo-vendor-filter-overlay-010225')) setOpen(false);
			});
			document.addEventListener('keydown', (event) => {
				if (event.key === 'Escape') setOpen(false);
			});

			function sync() {
				if (syncing) return;
				syncing = true;
				try {
					syncCount();
					mountPanel();
				} finally {
					syncing = false;
				}
			}

			sync();
			const observer = new MutationObserver(() => requestAnimationFrame(sync));
			observer.observe(store, { childList: true, subtree: true, characterData: true });
			window.addEventListener('pageshow', sync);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Presentación: catálogo a la izquierda y rail de 250px a la derecha, alineado
 * con el patrón de Tienda. En móvil se convierte en drawer mediante un único
 * botón Filtrar dentro de la toolbar del productor.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-store-catalog-010225">
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
				overflow: visible !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-result-count-010225 {
				visibility: visible !important;
				display: block !important;
				opacity: 1 !important;
				margin: 0 !important;
				font-size: 14px !important;
				line-height: 1.35 !important;
				white-space: nowrap !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget {
				float: none !important;
				width: 100% !important;
				margin: 0 !important;
				padding: 20px 0 !important;
				border: 0 !important;
				border-bottom: 1px solid rgba(24,35,28,.11) !important;
				background: transparent !important;
				box-shadow: none !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget:first-of-type {
				padding-top: 0 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget:last-child {
				border-bottom: 0 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget-title {
				margin: 0 0 13px !important;
				padding: 0 !important;
				font-size: 15px !important;
				font-weight: 700 !important;
				line-height: 1.25 !important;
				letter-spacing: -.01em !important;
				text-transform: none !important;
				color: var(--emo-ink, #26342b) !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-filter ul,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter ul {
				list-style: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-filter li,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter li {
				margin: 0 !important;
				padding: 0 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-filter a,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter a {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 12px !important;
				min-height: 36px !important;
				padding: 6px 0 !important;
				color: var(--emo-ink, #26342b) !important;
				text-decoration: none !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-filter a:hover span,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter a:hover span {
				text-decoration: underline !important;
				text-underline-offset: 3px !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-filter small,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter small {
				flex: 0 0 auto !important;
				font-size: 12px !important;
				color: rgba(38,52,43,.62) !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter li.chosen > a {
				font-weight: 700 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-attribute-filter li.chosen > a span::before {
				content: '✓' !important;
				display: inline-block !important;
				margin-right: 7px !important;
				font-weight: 700 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__eyebrow {
				margin-bottom: 7px !important;
				font-size: 11px !important;
				font-weight: 700 !important;
				letter-spacing: .08em !important;
				text-transform: uppercase !important;
				color: rgba(38,52,43,.56) !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 12px !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row strong {
				font-size: 15px !important;
				line-height: 1.3 !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row a {
				font-size: 12px !important;
				font-weight: 700 !important;
				text-decoration: underline !important;
				text-underline-offset: 3px !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider_wrapper {
				padding-top: 4px !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider_amount {
				display: grid !important;
				grid-template-columns: auto 1fr !important;
				align-items: center !important;
				gap: 10px !important;
				font-size: 12px !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider_amount .button {
				float: none !important;
				grid-column: 1 !important;
				min-height: 34px !important;
				padding: 7px 12px !important;
				font-size: 12px !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_label {
				grid-column: 2 !important;
				text-align: right !important;
				white-space: nowrap !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter #min_price,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter #max_price {
				display: none !important;
			}
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters__mobile-head,
			body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filter-toggle-010225 {
				display: none !important;
			}

			@media (min-width: 1101px) {
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) 250px !important;
					column-gap: 34px !important;
					row-gap: 0 !important;
					align-items: start !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .right_side,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .right_side_full,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .products-wrapper,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .wcfmmp-store-product,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .product_area {
					grid-column: 1 !important;
					grid-row: 1 !important;
					width: 100% !important;
					max-width: none !important;
					min-width: 0 !important;
					float: none !important;
					margin: 0 !important;
					position: static !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					grid-column: 2 !important;
					grid-row: 1 !important;
					width: 250px !important;
					max-width: 250px !important;
					min-width: 0 !important;
					float: none !important;
					position: sticky !important;
					top: 94px !important;
					align-self: start !important;
					margin: 0 !important;
					padding: 0 !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area > .spacer {
					display: none !important;
				}
			}

			@media (max-width: 1100px) {
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area {
					display: block !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .right_side,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .right_side_full,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .products-wrapper,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .wcfmmp-store-product,
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .product_area {
					width: 100% !important;
					max-width: none !important;
					float: none !important;
					margin: 0 !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					display: block !important;
					position: fixed !important;
					z-index: 1000001 !important;
					top: 0 !important;
					right: 0 !important;
					bottom: 0 !important;
					left: auto !important;
					width: min(360px, 92vw) !important;
					max-width: 92vw !important;
					height: 100dvh !important;
					overflow: auto !important;
					padding: 20px 22px 32px !important;
					margin: 0 !important;
					background: #fff !important;
					box-shadow: -18px 0 44px rgba(13,25,17,.18) !important;
					transform: translateX(105%) !important;
					transition: transform .22s ease !important;
				}
				html.emo-vendor-filters-open-010225 body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					transform: translateX(0) !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters__mobile-head {
					display: flex !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 16px !important;
					padding: 0 0 16px !important;
					border-bottom: 1px solid rgba(24,35,28,.11) !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters__close {
					display: inline-grid !important;
					place-items: center !important;
					width: 36px !important;
					height: 36px !important;
					padding: 0 !important;
					border: 1px solid rgba(24,35,28,.15) !important;
					border-radius: 50% !important;
					background: #fff !important;
					font-size: 24px !important;
					line-height: 1 !important;
				}
				body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filter-toggle-010225 {
					display: inline-flex !important;
					align-items: center !important;
					justify-content: center !important;
					gap: 7px !important;
					min-height: 38px !important;
					padding: 7px 13px !important;
					border: 1px solid rgba(24,35,28,.16) !important;
					border-radius: 999px !important;
					background: #fff !important;
					color: var(--emo-ink, #26342b) !important;
					font-size: 13px !important;
					font-weight: 700 !important;
				}
				.emo-vendor-filter-overlay-010225:not([hidden]) {
					display: block !important;
					position: fixed !important;
					z-index: 1000000 !important;
					inset: 0 !important;
					background: rgba(13,25,17,.38) !important;
				}
				html.emo-vendor-filters-open-010225 {
					overflow: hidden !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
