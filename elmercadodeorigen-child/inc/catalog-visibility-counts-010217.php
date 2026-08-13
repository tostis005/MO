<?php
/**
 * Coherencia de contadores del catálogo con la visibilidad de vendedores WCFM.
 *
 * Mantiene una única regla de negocio para Home, categorías, vendedores y
 * atributos: los administradores ven todo el catálogo publicado; el público
 * excluye los productos de vendedores desactivados u offline.
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
 * Conteos de categorías equivalentes al resultado real de sus archivos.
 *
 * Cada producto se propaga a los ancestros de sus categorías y se deduplica por
 * ID, igual que hace una consulta de product_cat con include_children=true.
 *
 * @return array<int,int>
 */
function elmercado_catalog_visible_category_counts_010217(): array {
	static $cache = array();

	$excluded  = elmercado_catalog_counts_excluded_authors_010217();
	$scope_key = elmercado_catalog_counts_can_view_disabled_010217()
		? 'admin'
		: 'public:' . implode( ',', $excluded );

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

	$author_clause = '';
	if ( $excluded ) {
		$author_clause = ' AND p.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded ) ) . ')';
	}

	$sql = "SELECT DISTINCT p.ID AS product_id, tt.term_id
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
		INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
		WHERE p.post_type = 'product'
		AND p.post_status = 'publish'
		AND tt.taxonomy = 'product_cat'{$author_clause}";

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
 * directamente de la colección antes de renderizarse.
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
 * Inserta la exclusión de vendedores desactivados en el SQL con el que
 * WooCommerce calcula los counts de navegación por capas. Al quedar count=0,
 * el propio widget omite las opciones no seleccionadas sin JS adicional.
 *
 * @param array<string,string> $query Partes de la consulta SQL de WooCommerce.
 * @return array<string,string>
 */
add_filter(
	'woocommerce_get_filtered_term_product_counts_query',
	static function ( array $query ): array {
		if ( is_admin() || elmercado_catalog_counts_can_view_disabled_010217() ) {
			return $query;
		}

		$excluded = elmercado_catalog_counts_excluded_authors_010217();
		if ( ! $excluded || empty( $query['where'] ) ) {
			return $query;
		}

		global $wpdb;
		$query['where'] .= ' AND ' . $wpdb->posts . '.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded ) ) . ')';
		return $query;
	},
	999
);

/**
 * Conteos de vendedor en el contexto actual (Tienda o categoría), con la misma
 * política de autores que el listado principal.
 *
 * @return array<int,int>
 */
function elmercado_catalog_visible_vendor_counts_010217(): array {
	global $wpdb;

	$term_ids = function_exists( 'elmercado_catalog_context_term_ids_010207' )
		? array_values( array_filter( array_map( 'absint', elmercado_catalog_context_term_ids_010207() ) ) )
		: array();
	$excluded      = elmercado_catalog_counts_excluded_authors_010217();
	$author_clause = $excluded
		? ' AND p.post_author NOT IN (' . implode( ',', array_map( 'absint', $excluded ) ) . ')'
		: '';

	if ( $term_ids ) {
		$placeholders = implode( ',', array_fill( 0, count( $term_ids ), '%d' ) );
		$sql = "SELECT p.post_author, COUNT(DISTINCT p.ID) AS product_count
			FROM {$wpdb->posts} p
			INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'
			AND tt.taxonomy = 'product_cat'
			AND tt.term_id IN ({$placeholders}){$author_clause}
			GROUP BY p.post_author";
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$term_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
	} else {
		$sql = "SELECT p.post_author, COUNT(p.ID) AS product_count
			FROM {$wpdb->posts} p
			WHERE p.post_type = 'product'
			AND p.post_status = 'publish'{$author_clause}
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
 * Se hace en servidor, antes de enviarlo al navegador, para no mostrar ni un
 * instante vendedores/counts que el usuario no puede consultar.
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
 * Evita que una portada autenticada pueda reutilizar HTML anónimo en capas de
 * caché que respeten las constantes/cabeceras estándar de WordPress.
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
 * Última normalización de las tarjetas de categoría de Home. No depende del
 * count persistido ni de que la categoría hubiera quedado en una lista auxiliar:
 * toma exactamente las tarjetas presentes y les aplica el count del usuario.
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
