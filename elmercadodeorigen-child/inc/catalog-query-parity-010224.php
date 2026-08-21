<?php
/**
 * Paridad definitiva entre consulta, conteos y paginación del catálogo.
 *
 * La regla de negocio es única para todo el frontend:
 * - solo productos publicados;
 * - fuera los excluidos del catálogo;
 * - fuera los agotados para cualquier usuario;
 * - el público excluye vendedores WCFM desactivados/offline;
 * - el administrador puede ver esos vendedores desactivados.
 *
 * Este módulo sincroniza el SQL real, found_posts, max_num_pages, Home y carga
 * continua para que todas las superficies representen exactamente el mismo set.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si una consulta es el loop principal de Tienda/categoría/atributo.
 */
function elmercado_catalog_is_main_query_010224( WP_Query $query ): bool {
	if ( ! $query->is_main_query() || $query->is_singular() ) {
		return false;
	}

	if ( function_exists( 'elmercado_wcfm_query_targets_products_010210' ) ) {
		return elmercado_wcfm_query_targets_products_010210( $query );
	}

	if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) {
		return true;
	}

	$post_type = $query->get( 'post_type' );
	return 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) );
}

/**
 * Fuerza en el propio loop las exclusiones de visibilidad usadas por los counts.
 *
 * @param array<int|string,mixed> $tax_query Consulta existente.
 * @return array<int|string,mixed>
 */
function elmercado_catalog_force_visibility_tax_query_010224( array $tax_query ): array {
	$excluded = function_exists( 'elmercado_catalog_excluded_visibility_term_ids_010218' )
		? array_values( array_filter( array_map( 'absint', elmercado_catalog_excluded_visibility_term_ids_010218() ) ) )
		: array();

	if ( ! $excluded ) {
		return $tax_query;
	}

	$already = array();
	foreach ( $tax_query as $clause ) {
		if ( ! is_array( $clause ) || 'product_visibility' !== ( $clause['taxonomy'] ?? '' ) ) {
			continue;
		}
		if ( 'NOT IN' !== strtoupper( (string) ( $clause['operator'] ?? '' ) ) ) {
			continue;
		}
		$already = array_merge( $already, array_map( 'absint', (array) ( $clause['terms'] ?? array() ) ) );
	}

	$missing = array_values( array_diff( $excluded, array_unique( $already ) ) );
	if ( $missing ) {
		$tax_query[] = array(
			'taxonomy' => 'product_visibility',
			'field'    => 'term_id',
			'terms'    => $missing,
			'operator' => 'NOT IN',
		);
	}

	return $tax_query;
}

add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		if ( is_admin() || ! elmercado_catalog_is_main_query_010224( $query ) ) {
			return;
		}

		/* El frontend nunca es una vista de borradores/privados, ni siquiera para admin. */
		$query->set( 'post_status', 'publish' );
		$query->set(
			'tax_query',
			elmercado_catalog_force_visibility_tax_query_010224( (array) $query->get( 'tax_query' ) )
		);

		if ( function_exists( 'elmercado_catalog_counts_can_view_disabled_010217' )
			&& ! elmercado_catalog_counts_can_view_disabled_010217()
			&& function_exists( 'elmercado_catalog_counts_excluded_authors_010217' ) ) {
			$excluded_authors = array_values( array_filter( array_map( 'absint', elmercado_catalog_counts_excluded_authors_010217() ) ) );
			if ( $excluded_authors ) {
				$current = array_map( 'absint', (array) $query->get( 'author__not_in' ) );
				$query->set( 'author__not_in', array_values( array_unique( array_merge( $current, $excluded_authors ) ) ) );
			}
		}
	},
	1200
);

/**
 * Última defensa SQL. Plugins de marketplace pueden reconstruir tax_query o el
 * estado de posts después de pre_get_posts. Esta cláusula se aplica justo antes
 * de ejecutar el SELECT y replica literalmente la regla usada por los counts.
 *
 * @param array<string,string> $clauses Partes SQL de WP_Query.
 * @return array<string,string>
 */
add_filter(
	'posts_clauses',
	static function ( array $clauses, WP_Query $query ): array {
		if ( is_admin() || ! elmercado_catalog_is_main_query_010224( $query ) ) {
			return $clauses;
		}

		global $wpdb;
		$where = (string) ( $clauses['where'] ?? '' );

		/* Solo publicados, también para administradores con read_private_products. */
		$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_status = %s", 'publish' );

		/* Exclude-from-catalog y outofstock son obligatorios para todos. */
		if ( function_exists( 'elmercado_catalog_visibility_sql_clause_010218' ) ) {
			$where .= elmercado_catalog_visibility_sql_clause_010218( $wpdb->posts );
		}

		/* Única excepción admin: vendedores desactivados. */
		if ( function_exists( 'elmercado_catalog_counts_can_view_disabled_010217' )
			&& ! elmercado_catalog_counts_can_view_disabled_010217()
			&& function_exists( 'elmercado_catalog_counts_excluded_authors_010217' ) ) {
			$excluded_authors = array_values( array_filter( array_map( 'absint', elmercado_catalog_counts_excluded_authors_010217() ) ) );
			if ( $excluded_authors ) {
				$where .= ' AND ' . $wpdb->posts . '.post_author NOT IN (' . implode( ',', $excluded_authors ) . ')';
			}
		}

		$clauses['where'] = $where;
		return $clauses;
	},
	PHP_INT_MAX,
	2
);

/**
 * Corrige la fuente que usa WordPress para construir la paginación.
 */
add_filter(
	'found_posts',
	static function ( $found_posts, WP_Query $query ) {
		if ( is_admin() || ! elmercado_catalog_is_main_query_010224( $query ) || ! function_exists( 'elmercado_catalog_exact_result_total_010220' ) ) {
			return $found_posts;
		}

		return elmercado_catalog_exact_result_total_010220();
	},
	PHP_INT_MAX,
	2
);

/**
 * Antes de pintar el paginador dejamos ambas propiedades en el valor exacto.
 */
add_filter(
	'the_posts',
	static function ( array $posts, WP_Query $query ): array {
		if ( is_admin() || ! elmercado_catalog_is_main_query_010224( $query ) || ! function_exists( 'elmercado_catalog_exact_result_total_010220' ) ) {
			return $posts;
		}

		$total = max( 0, (int) elmercado_catalog_exact_result_total_010220() );
		$query->found_posts = $total;

		$per_page = (int) $query->get( 'posts_per_page' );
		if ( $per_page <= 0 ) {
			$per_page = (int) get_option( 'posts_per_page', 12 );
		}
		$query->max_num_pages = $per_page > 0 ? (int) ceil( $total / $per_page ) : 0;

		return $posts;
	},
	PHP_INT_MAX,
	2
);

/**
 * Reescribe los counts de tarjetas de Home con la misma fuente central.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() || false === strpos( $content, 'emo-category-card' ) ) {
			return $content;
		}
		if ( ! function_exists( 'elmercado_catalog_visible_category_count_010217' ) ) {
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

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term ) {
				continue;
			}
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}

			$count        = elmercado_catalog_visible_category_count_010217( (int) $term->term_id );
			$escaped_link = esc_url( $link );
			$card_pattern = '~<a class="emo-category-card" href="' . preg_quote( $escaped_link, '~' ) . '"[^>]*>.*?</a>~s';

			if ( $count <= 0 ) {
				$content = (string) preg_replace( $card_pattern, '', $content, 1 );
				continue;
			}

			$label = sprintf(
				esc_html( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ) ),
				number_format_i18n( $count )
			);
			$content = (string) preg_replace_callback(
				$card_pattern,
				static function ( array $matches ) use ( $label ): string {
					return (string) preg_replace( '~<small[^>]*>.*?</small>~s', '<small>' . $label . '</small>', (string) $matches[0], 1 );
				},
				$content,
				1
			);
		}

		return $content;
	},
	2000
);

/**
 * Mantiene el copy exacto y blinda el DOM contra dos cargadores históricos que
 * puedan solicitar la misma página simultáneamente. La deduplicación usa el ID
 * de producto, no el texto ni la posición visual.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_core_filters_is_catalog' ) || ! elmercado_core_filters_is_catalog() ) {
			return;
		}
		$total = function_exists( 'elmercado_catalog_exact_result_total_010220' )
			? max( 0, (int) elmercado_catalog_exact_result_total_010220() )
			: 0;
		?>
		<script id="elmercado-catalog-query-parity-010224">
		(() => {
			'use strict';
			const node = document.querySelector('.emo-catalog-result-count-010220');
			const total = <?php echo wp_json_encode( $total ); ?>;
			const label = `${total.toLocaleString('es-ES')} ${total === 1 ? 'resultado' : 'resultados'}`;

			if (node) {
				let normalizing = false;
				const normalize = () => {
					if (normalizing) return;
					const current = (node.textContent || '').replace(/\s+/g, ' ').trim();
					if (current === label) return;
					normalizing = true;
					node.textContent = label;
					normalizing = false;
				};
				normalize();
				new MutationObserver(normalize).observe(node, { childList: true, characterData: true, subtree: true });
			}

			const grid = document.querySelector('#wcfmmp-store ul.products, main ul.products, #primary ul.products, .content-area ul.products, ul.products');
			if (!grid) return;

			/* Si por legado se han inicializado dos estados de carga, conservamos uno. */
			const loaderStates = [...document.querySelectorAll('.emo-catalog-load-state')];
			loaderStates.slice(1).forEach((state) => state.remove());

			const productKey = (item) => {
				const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
				if (postClass) return postClass;
				const id = item.getAttribute('data-product_id') || item.querySelector('[data-product_id]')?.getAttribute('data-product_id');
				if (id) return `product-${id}`;
				const href = item.querySelector('a.woocommerce-LoopProduct-link, a[href*="/producto/"]')?.href || '';
				return href;
			};

			const dedupe = () => {
				const seen = new Set();
				[...grid.querySelectorAll(':scope > li.product')].forEach((item) => {
					const key = productKey(item);
					if (!key) return;
					if (seen.has(key)) {
						item.remove();
						return;
					}
					seen.add(key);
				});
			};
			dedupe();
			let queued = false;
			new MutationObserver(() => {
				if (queued) return;
				queued = true;
				queueMicrotask(() => {
					queued = false;
					dedupe();
				});
			}).observe(grid, { childList: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
