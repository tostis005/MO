<?php
/**
 * Filtros funcionales de catálogo específicos por categoría.
 *
 * Este módulo define perfiles y genera el marcado de atributos. La presentación,
 * el orden y el montaje pertenecen exclusivamente al sistema consolidado de
 * catálogo; aquí no hay CSS ni controladores JavaScript.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Perfiles de filtros por familia.
 */
function elmercado_catalog_filter_profiles(): array {
	return array(
		'ham' => array(
			'label'      => 'Jamones y paletas',
			'attributes' => array(
				'tipo-pieza'   => 'Tipo de pieza',
				'calidad'      => 'Calidad',
				'raza-iberica' => 'Raza ibérica',
				'alimentacion' => 'Alimentación',
				'con-dop'      => 'Con DOP',
				'dop'          => 'Denominación de origen',
				'origen'       => 'Origen',
				'preparacion'  => 'Preparación',
				'rango-peso'   => 'Peso',
				'curacion'     => 'Curación',
				'productor'    => 'Productor',
			),
		),
	);
}

/**
 * Devuelve el perfil aplicable a la categoría actual.
 *
 * También considera ancestros, de modo que una futura subcategoría de
 * Jamones y paletas herede automáticamente estos filtros.
 */
function elmercado_catalog_filter_profile(): ?array {
	if ( ! function_exists( 'is_product_category' ) || ! is_product_category() ) {
		return null;
	}

	$term = get_queried_object();
	if ( ! $term instanceof WP_Term || 'product_cat' !== $term->taxonomy ) {
		return null;
	}

	$term_ids = array_merge(
		array( (int) $term->term_id ),
		array_map( 'intval', get_ancestors( (int) $term->term_id, 'product_cat', 'taxonomy' ) )
	);

	foreach ( array_values( array_unique( $term_ids ) ) as $term_id ) {
		$candidate = get_term( $term_id, 'product_cat' );
		if ( ! $candidate instanceof WP_Term ) {
			continue;
		}

		$haystack = remove_accents( strtolower( $candidate->name . ' ' . $candidate->slug ) );
		$has_ham  = (bool) preg_match( '/\bjamon(?:es)?\b/u', $haystack );
		$has_pork = (bool) preg_match( '/\bpaleta(?:s)?\b/u', $haystack );

		if ( $has_ham && $has_pork ) {
			$profiles = elmercado_catalog_filter_profiles();
			return $profiles['ham'];
		}
	}

	return null;
}

/**
 * URL limpia de la categoría: elimina atributos, precio, vendedor y paginación
 * al volver al estado sin filtros.
 */
function elmercado_catalog_filter_clear_url(): string {
	$term = get_queried_object();
	if ( $term instanceof WP_Term && 'product_cat' === $term->taxonomy ) {
		$link = get_term_link( $term );
		if ( ! is_wp_error( $link ) ) {
			return (string) $link;
		}
	}

	return function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
}

/**
 * Indica si existe algún atributo/precio del perfil activo.
 */
function elmercado_catalog_profile_has_active_filter( array $profile ): bool {
	if ( ! class_exists( 'WC_Query' ) ) {
		return false;
	}

	$chosen = WC_Query::get_layered_nav_chosen_attributes();
	foreach ( array_keys( $profile['attributes'] ?? array() ) as $attribute_slug ) {
		$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
		if ( ! empty( $chosen[ $taxonomy ]['terms'] ) ) {
			return true;
		}
	}

	return isset( $_GET['min_price'] ) || isset( $_GET['max_price'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

/**
 * Genera únicamente el marcado de los widgets nativos de navegación por capas.
 * El sistema consolidado los monta una vez en el sidebar y se ocupa del estilo.
 */
add_action(
	'woocommerce_before_main_content',
	static function (): void {
		if ( is_admin() || ! class_exists( 'WooCommerce' ) || ! class_exists( 'WC_Widget_Layered_Nav' ) ) {
			return;
		}

		$profile = elmercado_catalog_filter_profile();
		if ( ! $profile ) {
			return;
		}

		$clear_url = elmercado_catalog_filter_clear_url();
		?>
		<aside
			id="emo-category-attribute-filters"
			class="emo-category-attribute-filters"
			hidden
			data-clear-url="<?php echo esc_url( $clear_url ); ?>"
			aria-label="<?php echo esc_attr( 'Filtros de ' . $profile['label'] ); ?>"
		>
			<div class="emo-category-attribute-filters__groups">
				<?php
				foreach ( $profile['attributes'] as $attribute_slug => $label ) {
					$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
					if ( ! taxonomy_exists( $taxonomy ) ) {
						continue;
					}

					the_widget(
						'WC_Widget_Layered_Nav',
						array(
							'title'        => $label,
							'attribute'    => $attribute_slug,
							'display_type' => 'list',
							'query_type'   => 'or',
						),
						array(
							'before_widget' => '<section class="widget woocommerce widget_layered_nav emo-category-filter-group emo-category-filter-' . esc_attr( $attribute_slug ) . '">',
							'after_widget'  => '</section>',
							'before_title'  => '<h3 class="widget-title emo-category-filter-title">',
							'after_title'   => '</h3>',
						)
					);
				}
				?>
			</div>
		</aside>
		<?php
	},
	40
);
