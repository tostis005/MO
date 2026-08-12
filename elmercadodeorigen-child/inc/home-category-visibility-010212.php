<?php
/**
 * Conteo contextual y jerarquia visual de las categorias de la Home.
 *
 * La portada no debe usar el count persistido del termino para visitantes
 * publicos, porque ese valor incluye productos publicados de vendedores WCFM
 * desactivados. Los administradores, en cambio, deben ver exactamente el mismo
 * catalogo completo que ya ven en Tienda y en los archivos de categoria.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cuenta productos publicados visibles para el usuario actual dentro de una
 * categoria, incluyendo sus categorias hijas.
 *
 * Reutiliza deliberadamente la misma capacidad que gobierna Tienda: los
 * administradores con manage_options no excluyen vendedores desactivados; el
 * resto de usuarios si.
 */
function elmercado_home_public_category_count_010212( int $term_id ): int {
	static $counts = array();

	$term_id = absint( $term_id );
	if ( $term_id <= 0 ) {
		return 0;
	}

	$can_view_disabled = function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' )
		&& elmercado_wcfm_disabled_visibility_can_view_010210();
	$scope_key         = $can_view_disabled ? 'admin' : 'public';
	$cache_key         = $scope_key . ':' . $term_id;

	if ( isset( $counts[ $cache_key ] ) ) {
		return $counts[ $cache_key ];
	}

	$disabled_vendor_ids = ! $can_view_disabled && function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' )
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

	$query                = new WP_Query( $args );
	$counts[ $cache_key ] = max( 0, (int) $query->found_posts );
	wp_reset_postdata();

	return $counts[ $cache_key ];
}

/**
 * Sustituye en el HTML ya compuesto de la Home el count persistido de cada
 * tarjeta por el total que corresponde al usuario actual. Una categoria sin
 * ningun producto visible para ese usuario deja de ocupar una tarjeta.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() || false === strpos( $content, 'emo-category-card' ) ) {
			return $content;
		}

		$exclude = array_filter( array( (int) get_option( 'default_product_cat' ) ) );
		$terms   = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 6,
				'orderby'    => 'count',
				'order'      => 'DESC',
				'exclude'    => $exclude,
			)
		);

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
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

			$count        = elmercado_home_public_category_count_010212( (int) $term->term_id );
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
					$card = (string) $matches[0];
					$card = (string) preg_replace(
						'~<div class="emo-category-card__content">~',
						'<div class="emo-category-card__content" style="display:flex!important;flex-direction:column!important;align-items:stretch!important;justify-content:flex-end!important;gap:0!important;min-width:0!important;width:100%!important;">',
						$card,
						1
					);
					$card = (string) preg_replace(
						'~<strong>~',
						'<strong style="display:block!important;width:100%!important;max-width:100%!important;margin:0 0 6px!important;line-height:1.14!important;text-align:left!important;">',
						$card,
						1
					);
					$card = (string) preg_replace(
						'~<small>.*?</small>~s',
						'<small style="display:block!important;width:100%!important;margin:0!important;line-height:1.2!important;text-align:right!important;align-self:stretch!important;">' . $label . '</small>',
						$card,
						1
					);
					return $card;
				},
				$content,
				1
			);
		}

		return $content;
	},
	1000
);

/**
 * La Home dispone de cache HTML/estatica. Si WCFM cambia el estado de una
 * tienda hay que invalidarla en ese mismo momento para que el nuevo count sea
 * visible sin esperar a que expire la cache anonima.
 *
 * El primer parametro es int al crear/actualizar metadata y array al borrarla,
 * por eso se mantiene deliberadamente sin type hint.
 *
 * @param mixed $meta_id ID o IDs de metadata.
 */
function elmercado_flush_home_for_wcfm_vendor_state_010212( $meta_id, int $user_id, string $meta_key ): void {
	unset( $meta_id, $user_id );
	if ( in_array( $meta_key, array( '_disable_vendor', '_wcfm_store_offline' ), true ) && function_exists( 'elmercado_flush_home_cache' ) ) {
		elmercado_flush_home_cache();
	}
}
add_action( 'added_user_meta', 'elmercado_flush_home_for_wcfm_vendor_state_010212', 10, 3 );
add_action( 'updated_user_meta', 'elmercado_flush_home_for_wcfm_vendor_state_010212', 10, 3 );
add_action( 'deleted_user_meta', 'elmercado_flush_home_for_wcfm_vendor_state_010212', 10, 3 );

add_action(
	'set_user_role',
	static function ( int $user_id, string $role, array $old_roles ): void {
		unset( $user_id );
		$watched_roles = array( 'wcfm_vendor', 'vendor', 'disable_vendor' );
		if ( in_array( $role, $watched_roles, true ) || array_intersect( $watched_roles, $old_roles ) ) {
			if ( function_exists( 'elmercado_flush_home_cache' ) ) {
				elmercado_flush_home_cache();
			}
		}
	},
	10,
	3
);

/**
 * Fallback de cascada. El layout decisivo tambien se fija inline en el HTML
 * para que el optimizador de CSS de Home no pueda devolverlo al antiguo grid.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<style id="elmercado-home-category-visibility-010213">
			html body.home.elmercado-child-theme .emo-home .emo-category-card .emo-category-card__content {
				display: flex !important;
				flex-direction: column !important;
				align-items: stretch !important;
				justify-content: flex-end !important;
				gap: 0 !important;
				min-width: 0 !important;
				width: 100% !important;
			}

			html body.home.elmercado-child-theme .emo-home .emo-category-card .emo-category-card__content > strong {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 0 6px !important;
				line-height: 1.14 !important;
				text-align: left !important;
			}

			html body.home.elmercado-child-theme .emo-home .emo-category-card .emo-category-card__content > small {
				display: block !important;
				width: 100% !important;
				margin: 0 !important;
				line-height: 1.2 !important;
				text-align: right !important;
				align-self: stretch !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);