<?php
/**
 * Conteo publico y jerarquia visual de las categorias de la Home 0.10.212.
 *
 * La portada no debe usar el count persistido del termino porque ese valor
 * sigue incluyendo productos publicados de vendedores WCFM que hemos decidido
 * tratar como inexistentes para el publico. Este modulo calcula el total con
 * las mismas exclusiones y separa nombre y numero de productos en dos lineas.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuenta productos publicados realmente disponibles para el catalogo publico
 * dentro de una categoria, incluyendo sus categorias hijas.
 *
 * El resultado es deliberadamente publico incluso para administradores: la
 * Home puede estar cacheada y su contador debe ser unico y coherente para
 * cualquier visitante.
 */
function elmercado_home_public_category_count_010212( int $term_id ): int {
	static $counts = array();

	$term_id = absint( $term_id );
	if ( $term_id <= 0 ) {
		return 0;
	}

	if ( isset( $counts[ $term_id ] ) ) {
		return $counts[ $term_id ];
	}

	$disabled_vendor_ids = function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' )
		? array_values( array_filter( array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() ) ) )
		: array();

	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'fields'                 => 'ids',
		'posts_per_page'         => 1,
		'paged'                  => 1,
		'no_found_rows'          => false,
		'ignore_sticky_posts'    => true,
		'suppress_filters'       => false,
		'cache_results'          => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'tax_query'              => array(
			array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array( $term_id ),
				'include_children' => true,
			),
		),
	);

	if ( $disabled_vendor_ids ) {
		$args['author__not_in'] = $disabled_vendor_ids;
	}

	$query              = new WP_Query( $args );
	$counts[ $term_id ] = max( 0, (int) $query->found_posts );
	wp_reset_postdata();

	return $counts[ $term_id ];
}

/**
 * Acabado final de las tarjetas: nombre arriba y contador debajo.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<style id="elmercado-home-category-visibility-010212">
			body.home.elmercado-child-theme .emo-category-card__content {
				display: flex !important;
				flex-direction: column !important;
				align-items: flex-start !important;
				justify-content: flex-end !important;
				gap: 0 !important;
				min-width: 0 !important;
			}

			body.home.elmercado-child-theme .emo-category-card__content > strong {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 0 6px !important;
				line-height: 1.14 !important;
			}

			body.home.elmercado-child-theme .emo-category-card__content > small {
				display: block !important;
				width: 100% !important;
				margin: 0 !important;
				line-height: 1.2 !important;
			}

			@media (max-width: 767px) {
				body.home.elmercado-child-theme .emo-category-card__content > strong {
					margin-bottom: 5px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
