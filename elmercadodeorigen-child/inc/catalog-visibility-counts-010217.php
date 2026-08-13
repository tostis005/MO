<?php
/**
 * Coherencia de contadores del catálogo con la visibilidad real de productos.
 *
 * Regla única:
 * - solo productos publicados y visibles en catálogo;
 * - productos sin existencias quedan fuera cuando WooCommerce los oculta;
 * - el público excluye vendedores WCFM desactivados/offline;
 * - los administradores conservan la visibilidad de esos vendedores, pero no
 *   cuentan productos que el catálogo oculta por stock/visibilidad.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si el usuario actual puede ver productos de vendedores desactivados.
 */
function elmercado_catalog_counts_can_view_disabled_010217(): bool {
	return function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' )
		&& elmercado_wcfm_disabled_visibility_can_view_010210();
}

/**
 * Vendedores que deben desaparecer de los contadores para el usuario actual.
 *
 * @return int[]
 */
function elmercado_catalog_counts_excluded_authors_010217(): array {
	if ( elmercado_catalog_counts_can_view_disabled_010217() || ! function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
		return array();
	}

	return array_values(
		array_unique(
			array_filter(
				array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() )
			)
		)
	);
}

/**
 * Indica si WooCommerce está configurado para ocultar productos sin existencias.
 */
function elmercado_catalog_hides_out_of_stock_010218(): bool {
	return 'yes' === get_option( 'woocommerce_hide_out_of_stock_items', 'no' );
}

/**
 * Términos de product_visibility que no deben entrar en los conteos de catálogo.
 *
 * @return int[]
 */
function elmercado_catalog_excluded_visibility_term_ids_010218(): array {
	static $cache = null;
	if ( is_array( $cache ) ) {
		return $cache;
	}

	$cache = array();
	if ( ! function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		return $cache;
	}

	$visibility = wc_get_product_visibility_term_ids();
	$catalog_id = isset( $visibility['exclude-from-catalog'] ) ? absint( $visibility['exclude-from-catalog'] ) : 0;
	$stock_id   = isset( $visibility['outofstock'] ) ? absint( $visibility['outofstock'] ) : 0;

	if ( $catalog_id > 0 ) {
		$cache[] = $catalog_id;
	}
	if ( elmercado_catalog_hides_out_of_stock_010218() && $stock_id > 0 ) {
		$cache[] = $stock_id;
	}

	$cache = array_values( array_unique( array_filter( $cache ) ) );
	return $cache;
}

/**
 * SQL que elimina productos ocultos por product_visibility.
 *
 * @param string $post_reference Tabla/alias que contiene la columna ID.
 */
function elmercado_catalog_visibility_sql_clause_010218( string $post_reference ): string {
	$term_ids = elmercado_catalog_excluded_visibility_term_ids_010218();
	if ( ! $term_ids ) {
		return '';
	}

	global $wpdb;
	return ' AND NOT EXISTS ('
		. 'SELECT 1 FROM ' . $wpdb->term_relationships . ' emo_vis_tr '
		. 'INNER JOIN ' . $wpdb->term_taxonomy . ' emo_vis_tt ON emo_vis_tt.term_taxonomy_id = emo_vis_tr.term_taxonomy_id '
		. 'WHERE emo_vis_tr.object_id = ' . $post_reference . '.ID '
		. "AND emo_vis_tt.taxonomy = 'product_visibility' "
		. 'AND emo_vis_tt.term_id IN (' . implode( ',', array_map( 'absint', $term_ids ) ) . '))';
}

/**
 * Conteos de categorías equivalentes al catálogo realmente visible.
 *
 * Cada producto se propaga a los ancestros de sus categorías y se deduplica por
 * ID, igual que una consulta product_cat con include_children=true.
 *
 * @return array<int,int>
 */
function elmercado_catalog_visible_category_counts_010217(): array {
	static $cache = array();

	$excluded  = elmercado_catalog_counts_excluded_authors_010217();
	$scope_key = ( elmercado_catalog_counts_can_view_disabled_010217() ? 'admin' : 'public:' . implode( ',', $excluded ) )
		. ':stock:' . ( elmercado_catalog_hides_out_of_stock_010218() ? 'hidden' : 'shown' );

	if ( isset( $cache[ $scope_key ] ) ) {
		return $cache[ $scope_key ];
	}

	global $wpdb;

	$parent_rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT term_id, parent FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
			'product_cat'
		)
	);

	$parents = array();
	foreach ( (array) $parent_rows as $row ) {
		$term_id = isset( $row->term_id ) ? absint( $row->term_id ) : 0;
		if ( $term_id > 0 ) {
			$parents[ $term_id ] = isset( $row->parent ) ? absint( $row->parent ) : 0;
		}
	}

	$author_clause = $excluded
		? ' AND p.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded ) ) . ')'
		: '';
	$visibility_clause = elmercado_catalog_visibility_sql_clause_010218( 'p' );

	$sql = "SELECT DISTINCT p.ID AS product_id, tt.term_id
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND tt.taxonomy = 'product_cat'{$author_clause}{$visibility_clause}";

	$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	$sets = array();

	foreach ( (array) $rows as $row ) {
		$product_id = isset( $row->product_id ) ? absint( $row->product_id ) : 0;
		$term_id    = isset( $row->term_id ) ? absint( $row->term_id ) : 0;
		if ( $product_id <= 0 || $term_id <= 0 ) {
			continue;
		}

		$visited = array();
		$current = $term_id;
		while ( $current > 0 && empty( $visited[ $current ] ) ) {
			$visited[ $current ] = true;
			if ( ! isset( $sets[ $current ] ) ) {
				$sets[ $current ] = array();
			}
			$sets[ $current ][ $product_id ] = true;
			$current = isset( $parents[ $current ] ) ? absint( $parents[ $current ] ) : 0;
		}
	}

	$counts = array();
	foreach ( $parents as $term_id => $parent_id ) {
		unset( $parent_id );
		$counts[ $term_id ] = isset( $sets[ $term_id ] ) ? count( $sets[ $term_id ] ) : 0;
	}

	$cache[ $scope_key ] = $counts;
	return $counts;
}

/**
 * Count de una categoría para el usuario actual.
 */
function elmercado_catalog_visible_category_count_010217( int $term_id ): int {
	$counts = elmercado_catalog_visible_category_counts_010217();
	return max( 0, (int) ( $counts[ absint( $term_id ) ] ?? 0 ) );
}

/**
 * Hace que el widget de categorías de Tienda use los mismos productos visibles
 * que el loop principal. Si hide_empty está activo, los términos a cero salen
 * de la colección antes de renderizarse.
 */
add_filter(
	'get_terms',
	static function ( $terms, array $taxonomies, array $args ) {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return $terms;
		}
		if ( is_wp_error( $terms ) || ! in_array( 'product_cat', $taxonomies, true ) || ! is_array( $terms ) ) {
			return $terms;
		}

		$counts     = elmercado_catalog_visible_category_counts_010217();
		$hide_empty = ! empty( $args['hide_empty'] );
		$adjusted   = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
				$adjusted[] = $term;
				continue;
			}

			$count = max( 0, (int) ( $counts[ (int) $term->term_id ] ?? 0 ) );
			if ( $hide_empty && 0 === $count ) {
				continue;
			}

			$copy        = clone $term;
			$copy->count = $count;
			$adjusted[]  = $copy;
		}

		return $adjusted;
	},
	999,
	3
);

/**
 * Inserta las mismas exclusiones en el SQL de counts de navegación por capas.
 * Al quedar count=0, WooCommerce omite por sí mismo las opciones no elegidas.
 *
 * @param array<string,string> $query Partes de la consulta SQL de WooCommerce.
 * @return array<string,string>
 */
add_filter(
	'woocommerce_get_filtered_term_product_counts_query',
	static function ( array $query ): array {
		if ( is_admin() || empty( $query['where'] ) ) {
			return $query;
		}

		global $wpdb;

		$query['where'] .= elmercado_catalog_visibility_sql_clause_010218( $wpdb->posts );

		if ( ! elmercado_catalog_counts_can_view_disabled_010217() ) {
			$excluded = elmercado_catalog_counts_excluded_authors_010217();
			if ( $excluded ) {
				$query['where'] .= ' AND ' . $wpdb->posts . '.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded ) ) . ')';
			}
		}

		return $query;
	},
	999
);

/**
 * Conteos de vendedor en el contexto actual (Tienda o categoría), con la misma
 * política de vendedor, stock y visibilidad que el catálogo.
 *
 * @return array<int,int>
 */
function elmercado_catalog_visible_vendor_counts_010217(): array {
	global $wpdb;

	$term_ids = function_exists( 'elmercado_catalog_context_term_ids_010207' )
		? array_values( array_filter( array_map( 'absint', elmercado_catalog_context_term_ids_010207() ) ) )
		: array();
	$excluded          = elmercado_catalog_counts_excluded_authors_010217();
	$author_clause     = $excluded
		? ' AND p.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded ) ) . ')'
		: '';
	$visibility_clause = elmercado_catalog_visibility_sql_clause_010218( 'p' );

	if ( $term_ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		$sql = "SELECT p.post_author, COUNT(DISTINCT p.ID) AS product_count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND tt.taxonomy = 'product_cat'
			AND tt.term_id IN ({$placeholders}){$author_clause}{$visibility_clause}
			GROUP BY p.post_author";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$term_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	} else {
		$sql = "SELECT p.post_author, COUNT(p.ID) AS product_count
			FROM {$wpdb->posts} p
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'{$author_clause}{$visibility_clause}
			GROUP BY p.post_author";
		$rows = $wpdb->get_results( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	$counts = array();
	foreach ( (array) $rows as $row ) {
		$author_id = isset( $row->post_author ) ? absint( $row->post_author ) : 0;
		$count     = isset( $row->product_count ) ? absint( $row->product_count ) : 0;
		if ( $author_id > 0 && $count > 0 && ( ! function_exists( 'elmercado_core_filter_is_vendor' ) || elmercado_core_filter_is_vendor( $author_id ) ) ) {
			$counts[ $author_id ] = $count;
		}
	}

	return $counts;
}

/**
 * Corrige el marcado que genera el sistema 0.10.207 para el filtro de vendedor.
 */
function elmercado_catalog_rewrite_vendor_filter_counts_010217( string $html ): string {
	if ( false === strpos( $html, 'emo-global-vendor-filter' ) ) {
		return $html;
	}

	$counts = elmercado_catalog_visible_vendor_counts_010217();
	$html   = (string) preg_replace_callback(
		'~<li class="emo-global-vendor-filter__item([^"]*)" data-vendor-id="(\d+)">(.*?)</li>~s',
		static function ( array $matches ) use ( $counts ): string {
			$vendor_id = absint( $matches[2] ?? 0 );
			$count     = max( 0, (int) ( $counts[ $vendor_id ] ?? 0 ) );
			if ( $vendor_id <= 0 || 0 === $count ) {
				return '';
			}

			$item = (string) $matches[0];
			return (string) preg_replace(
				'~<span class="count" aria-label="[^"]*">.*?</span>~s',
				'<span class="count" aria-label="' . esc_attr( (string) $count . ' productos' ) . '">' . esc_html( (string) $count ) . '</span>',
				$item,
				1
			);
		},
		$html
	);

	if ( ! preg_match( '~<li class="emo-global-vendor-filter__item~', $html ) ) {
		$html = (string) preg_replace( '~<aside id="emo-global-vendor-filter".*?</aside>~s', '', $html, 1 );
	}

	return $html;
}

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		$GLOBALS['elmercado_vendor_count_buffer_level_010217'] = ob_get_level();
		ob_start();
	},
	37
);

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		if ( ! isset( $GLOBALS['elmercado_vendor_count_buffer_level_010217'] ) ) {
			return;
		}
		$start_level = (int) $GLOBALS['elmercado_vendor_count_buffer_level_010217'];
		unset( $GLOBALS['elmercado_vendor_count_buffer_level_010217'] );
		if ( ob_get_level() <= $start_level ) {
			return;
		}

		$html = (string) ob_get_clean();
		echo elmercado_catalog_rewrite_vendor_filter_counts_010217( $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	},
	39
);

/**
 * Evita que una portada autenticada pueda reutilizar HTML anónimo.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_front_page() && is_user_logged_in() ) {
			if ( ! defined( 'DONOTCACHEPAGE' ) ) {
				define( 'DONOTCACHEPAGE', true );
			}
			nocache_headers();
		}
	},
	0
);

/**
 * Última normalización de las tarjetas de categoría de Home.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() || false === strpos( $content, 'emo-category-card' ) ) {
			return $content;
		}

		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			return $content;
		}

		$url_to_term = array();
		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$url_to_term[ untrailingslashit( (string) $link ) ] = (int) $term->term_id;
			}
		}

		return (string) preg_replace_callback(
			'~<a class="emo-category-card" href="([^"]+)"[^>]*>.*?</a>~s',
			static function ( array $matches ) use ( $url_to_term ): string {
				$href    = html_entity_decode( (string) ( $matches[1] ?? '' ), ENT_QUOTES, get_bloginfo( 'charset' ) ?: 'UTF-8' );
				$term_id = (int) ( $url_to_term[ untrailingslashit( $href ) ] ?? 0 );
				if ( $term_id <= 0 ) {
					return (string) $matches[0];
				}

				$count = elmercado_catalog_visible_category_count_010217( $term_id );
				if ( $count <= 0 ) {
					return '';
				}

				$label = sprintf(
					esc_html( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ) ),
					number_format_i18n( $count )
				);
				$card = (string) $matches[0];
				$card = (string) preg_replace(
					'~<small(?:\s[^>]*)?>.*?</small>~s',
					'<small style="display:block!important;width:100%!important;max-width:100%!important;margin:0!important;line-height:1.2!important;text-align:right!important;align-self:stretch!important;">' . $label . '</small>',
					$card,
					1
				);

				return $card;
			},
			$content
		);
	},
	2000
);

/**
 * Total real del catálogo actual después de aplicar categoría, atributos, precio,
 * vendedor, stock y exclusiones de autor que ya forman parte de la consulta.
 */
function elmercado_catalog_current_result_total_010218(): int {
	global $wp_query;
	if ( ! $wp_query instanceof WP_Query ) {
		return 0;
	}

	return max( 0, (int) $wp_query->found_posts );
}

/**
 * Barra superior del catálogo: solo el total, sin rangos ni selector de orden.
 */
add_action(
	'wp',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}

		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

		add_action(
			'woocommerce_before_shop_loop',
			static function (): void {
				$total = elmercado_catalog_current_result_total_010218();
				$label = sprintf(
					esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ),
					number_format_i18n( $total )
				);
				echo '<p class="woocommerce-result-count emo-catalog-result-count-010218" aria-live="polite">' . $label . '</p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			},
			20
		);
	},
	999
);

/**
 * Fallback visual por si el tema padre inyecta su propio control de ordenación.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-result-count-010218">
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .woocommerce-ordering { display:none !important; }
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-catalog-result-count-010218 { white-space:nowrap; }
		</style>
		<?php
	},
	PHP_INT_MAX
);