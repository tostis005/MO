<?php
/**
 * Garantiza que la Home termine con las mismas categorías visibles que Tienda.
 *
 * Este buffer es el más exterior y construye las categorías raíz directamente
 * desde la base de datos, usando los counts exactos del catálogo. Así no depende
 * del estado global de la query de Home ni de filtros históricos de get_terms().
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render final de categorías visibles para Home, sin límites artificiales.
 */
function elmercado_home_category_output_html_010226(): string {
	if ( ! function_exists( 'elmercado_catalog_visible_category_counts_010217' ) ) {
		return '';
	}

	global $wpdb;
	$counts = elmercado_catalog_visible_category_counts_010217();
	$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT t.term_id, t.name, t.slug
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			WHERE tt.taxonomy = %s AND tt.parent = 0",
			'product_cat'
		)
	);

	$default_id = (int) get_option( 'default_product_cat' );
	$items      = array();
	foreach ( (array) $rows as $row ) {
		$term_id = isset( $row->term_id ) ? absint( $row->term_id ) : 0;
		$count   = max( 0, (int) ( $counts[ $term_id ] ?? 0 ) );
		if ( $term_id <= 0 || $term_id === $default_id || $count <= 0 ) {
			continue;
		}
		$items[] = array(
			'id'    => $term_id,
			'name'  => (string) ( $row->name ?? '' ),
			'slug'  => (string) ( $row->slug ?? '' ),
			'count' => $count,
		);
	}

	usort(
		$items,
		static function ( array $left, array $right ): int {
			$by_count = (int) $right['count'] <=> (int) $left['count'];
			return 0 !== $by_count ? $by_count : strnatcasecmp( (string) $left['name'], (string) $right['name'] );
		}
	);

	if ( empty( $items ) ) {
		return '';
	}

	$categories_url = function_exists( 'elmercado_categories_hub_url_010257' )
		? elmercado_categories_hub_url_010257()
		: home_url( '/categorias/' );

	$html  = '<section class="emo-section emo-categories" data-emo-category-truth="010226"><div class="emo-shell">';
	$html .= '<div class="emo-section-heading"><div><span class="emo-kicker">' . esc_html__( 'Explora por categoría', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Encuentra tu próximo descubrimiento', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'Una despensa diversa, seleccionada para comprar mejor y conocer quién hay detrás de cada producto.', 'elmercadodeorigen' ) . '</p></div><a class="emo-text-link" data-emo-categories-link="010257" href="' . esc_url( $categories_url ) . '">' . esc_html__( 'Ver todas las categorías', 'elmercadodeorigen' ) . '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>';
	$html .= '<div class="emo-category-grid">';

	foreach ( $items as $item ) {
		$link = get_term_link( (int) $item['id'], 'product_cat' );
		if ( is_wp_error( $link ) ) {
			continue;
		}

		$thumbnail_id = (int) get_term_meta( (int) $item['id'], 'thumbnail_id', true );
		$image         = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
		$style         = $image ? ' style="--emo-category-image:url(' . esc_url( $image ) . ')"' : '';
		$count         = (int) $item['count'];
		$label         = sprintf(
			esc_html( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ) ),
			number_format_i18n( $count )
		);

		$html .= '<a class="emo-category-card" href="' . esc_url( $link ) . '"' . $style . '>';
		$html .= '<span class="emo-category-card__media" aria-hidden="true"></span>';
		$html .= '<div class="emo-category-card__content"><strong>' . esc_html( (string) $item['name'] ) . '</strong><small>' . $label . '</small></div>';
		$html .= '</a>';
	}

	$html .= '</div></div></section>';
	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		ob_start(
			static function ( string $html ): string {
				$start = strpos( $html, '<section class="emo-section emo-categories"' );
				if ( false === $start ) {
					return $html;
				}

				$replacement = elmercado_home_category_output_html_010226();
				$end         = strpos( $html, '</section>', $start );
				if ( '' === $replacement || false === $end ) {
					return $html;
				}
				$end += strlen( '</section>' );

				return substr_replace( $html, $replacement, $start, $end - $start );
			}
		);
	},
	-3000
);

/* 0.10.257: página visual de todas las categorías, cargada después del contrato de Home. */
$elmercado_categories_hub_010257 = __DIR__ . '/categories-hub-010257.php';
if ( is_readable( $elmercado_categories_hub_010257 ) ) {
	require_once $elmercado_categories_hub_010257;
}
