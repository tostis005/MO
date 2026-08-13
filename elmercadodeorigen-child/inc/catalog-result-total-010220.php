<?php
/**
 * Total exacto del catálogo y presentación mínima de resultados.
 *
 * El main query de esta instalación no es una fuente fiable para found_posts:
 * varias capas del tema/marketplace componen el loop de producto sobre una
 * consulta más amplia. Este módulo calcula el total desde la misma definición
 * de producto visible usada por categorías y filtros.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IDs de una taxonomía a partir de slugs válidos.
 *
 * @param string[] $slugs Slugs solicitados.
 * @return int[]
 */
function elmercado_catalog_term_ids_from_slugs_010220( string $taxonomy, array $slugs ): array {
	$slugs = array_values( array_unique( array_filter( array_map( 'sanitize_title', $slugs ) ) ) );
	if ( ! $slugs || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$terms = get_terms(
		array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => false,
			'slug'       => $slugs,
			'fields'     => 'ids',
		)
	);
	if ( is_wp_error( $terms ) ) {
		return array();
	}

	return array_values( array_unique( array_filter( array_map( 'absint', (array) $terms ) ) ) );
}

/**
 * Añade una condición EXISTS de taxonomía a un WHERE SQL.
 *
 * @param int[] $term_ids Términos aceptados.
 */
function elmercado_catalog_taxonomy_exists_sql_010220( string $post_alias, string $taxonomy, array $term_ids ): string {
	$term_ids = array_values( array_unique( array_filter( array_map( 'absint', $term_ids ) ) ) );
	if ( ! $term_ids ) {
		return ' AND 1=0';
	}

	global $wpdb;
	$taxonomy = sanitize_key( $taxonomy );

	return ' AND EXISTS ('
		. 'SELECT 1 FROM ' . $wpdb->term_relationships . ' emo_rt_tr '
		. 'INNER JOIN ' . $wpdb->term_taxonomy . ' emo_rt_tt ON emo_rt_tt.term_taxonomy_id = emo_rt_tr.term_taxonomy_id '
		. 'WHERE emo_rt_tr.object_id = ' . $post_alias . '.ID '
		. "AND emo_rt_tt.taxonomy = '" . esc_sql( $taxonomy ) . "' "
		. 'AND emo_rt_tt.term_id IN (' . implode( ',', $term_ids ) . '))';
}

/**
 * Total de productos realmente disponibles en el contexto actual.
 */
function elmercado_catalog_exact_result_total_010220(): int {
	static $cache = array();

	$scope = function_exists( 'elmercado_catalog_counts_can_view_disabled_010217' ) && elmercado_catalog_counts_can_view_disabled_010217()
		? 'admin'
		: 'public';
	$key = $scope . ':' . (string) ( $_SERVER['REQUEST_URI'] ?? '' );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	global $wpdb;

	$lookup_table = $wpdb->wc_product_meta_lookup;
	$sql          = "SELECT COUNT(DISTINCT p.ID)
		FROM {$wpdb->posts} p
		LEFT JOIN {$lookup_table} emo_lookup ON emo_lookup.product_id = p.ID
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'";

	if ( function_exists( 'elmercado_catalog_visibility_sql_clause_010218' ) ) {
		$sql .= elmercado_catalog_visibility_sql_clause_010218( 'p' );
	}

	if ( function_exists( 'elmercado_catalog_counts_excluded_authors_010217' ) ) {
		$excluded = array_values( array_filter( array_map( 'absint', elmercado_catalog_counts_excluded_authors_010217() ) ) );
		if ( $excluded ) {
			$sql .= ' AND p.post_author NOT IN (' . implode( ',', $excluded ) . ')';
		}
	}

	$vendor_id = function_exists( 'elmercado_wcfm_requested_vendor_id_010210' )
		? elmercado_wcfm_requested_vendor_id_010210()
		: ( isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $vendor_id > 0 ) {
		if ( function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' )
			&& ! elmercado_catalog_counts_can_view_disabled_010217()
			&& elmercado_wcfm_vendor_is_disabled_010210( $vendor_id ) ) {
			$cache[ $key ] = 0;
			return 0;
		}
		$sql .= ' AND p.post_author = ' . absint( $vendor_id );
	}

	/* Categoría/archivo de taxonomía activo. */
	if ( function_exists( 'is_product_category' ) && is_product_category() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
			$category_ids = array( (int) $term->term_id );
			$children     = get_term_children( (int) $term->term_id, 'product_cat' );
			if ( ! is_wp_error( $children ) ) {
				$category_ids = array_merge( $category_ids, array_map( 'absint', (array) $children ) );
			}
			$sql .= elmercado_catalog_taxonomy_exists_sql_010220( 'p', 'product_cat', $category_ids );
		}
	} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$term = get_queried_object();
		if ( $term instanceof WP_Term && taxonomy_exists( $term->taxonomy ) ) {
			$sql .= elmercado_catalog_taxonomy_exists_sql_010220( 'p', $term->taxonomy, array( (int) $term->term_id ) );
		}
	}

	/* Navegación por capas de WooCommerce. */
	$chosen = class_exists( 'WC_Query' ) ? WC_Query::get_layered_nav_chosen_attributes() : array();
	foreach ( (array) $chosen as $taxonomy => $data ) {
		$taxonomy = sanitize_key( (string) $taxonomy );
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$term_ids = elmercado_catalog_term_ids_from_slugs_010220( $taxonomy, (array) ( $data['terms'] ?? array() ) );
		if ( ! $term_ids ) {
			$sql .= ' AND 1=0';
			continue;
		}

		$query_type = strtolower( (string) ( $data['query_type'] ?? 'and' ) );
		if ( 'or' === $query_type ) {
			$sql .= elmercado_catalog_taxonomy_exists_sql_010220( 'p', $taxonomy, $term_ids );
			continue;
		}

		foreach ( $term_ids as $term_id ) {
			$sql .= elmercado_catalog_taxonomy_exists_sql_010220( 'p', $taxonomy, array( $term_id ) );
		}
	}

	/* Precio: la tabla lookup es la misma fuente que usa WooCommerce para catálogo. */
	$min_price = isset( $_GET['min_price'] ) ? wc_format_decimal( wp_unslash( $_GET['min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$max_price = isset( $_GET['max_price'] ) ? wc_format_decimal( wp_unslash( $_GET['max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( '' !== $min_price && is_numeric( $min_price ) ) {
		$sql .= $wpdb->prepare( ' AND emo_lookup.max_price >= %f', (float) $min_price );
	}
	if ( '' !== $max_price && is_numeric( $max_price ) ) {
		$sql .= $wpdb->prepare( ' AND emo_lookup.min_price <= %f', (float) $max_price );
	}

	/* Búsqueda de catálogo, cuando se conserve s en la URL. */
	if ( isset( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$search = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( '' !== $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$sql .= $wpdb->prepare( ' AND (p.post_title LIKE %s OR p.post_excerpt LIKE %s OR p.post_content LIKE %s)', $like, $like, $like );
		}
	}

	$total         = max( 0, (int) $wpdb->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$cache[ $key ] = $total;
	return $total;
}

/**
 * El contador anterior se conserva en DOM por compatibilidad, pero se oculta.
 * Éste es el único contador visible y no depende del found_posts global.
 */
add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}

		$total = elmercado_catalog_exact_result_total_010220();
		$label = sprintf(
			esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ),
			number_format_i18n( $total )
		);
		echo '<p class="woocommerce-result-count emo-catalog-result-count-010220" aria-live="polite">' . $label . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	21
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-result-total-010220">
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-catalog-result-count-010218 { display:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-catalog-result-count-010220 { display:block !important; white-space:nowrap; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .woocommerce-ordering { display:none !important; }
		</style>
		<?php
	},
	PHP_INT_MAX
);