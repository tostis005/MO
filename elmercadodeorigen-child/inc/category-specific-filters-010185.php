<?php
/**
 * Filtros de catálogo específicos por categoría 0.10.185.
 *
 * Cada familia de producto puede declarar su propio conjunto de atributos sin
 * contaminar el resto de archivos de tienda. La primera familia configurada es
 * Jamones y Paletas, cuyos atributos los genera y mantiene EMDO.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Perfiles de filtros por familia.
 *
 * Para añadir otra categoría basta con incorporar otro perfil y ampliar la
 * resolución en elmercado_catalog_filter_profile().
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
 * "Jamones y Paletas" herede automáticamente estos filtros.
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
 * URL limpia de la categoría que conserva la navegación actual pero elimina
 * filtros de atributos, precio y paginación.
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
 * Indica si hay algún filtro del perfil activo en la URL actual.
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
 * Renderiza los widgets nativos de navegación por capas de WooCommerce en un
 * contenedor inicialmente oculto. El controlador del footer lo coloca dentro
 * del sidebar canónico que ya usa la tienda en escritorio y móvil.
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
			<div class="emo-category-attribute-filters__head">
				<strong class="emo-category-attribute-filters__eyebrow"><?php echo esc_html( $profile['label'] ); ?></strong>
				<?php if ( elmercado_catalog_profile_has_active_filter( $profile ) ) : ?>
					<a class="emo-category-attribute-filters__clear" href="<?php echo esc_url( $clear_url ); ?>">Limpiar filtros</a>
				<?php endif; ?>
			</div>
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

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! elmercado_catalog_filter_profile() ) {
			return;
		}
		?>
		<style id="elmercado-category-specific-filters-010185">
			#emo-category-attribute-filters[hidden] { display:none !important; }
			#emo-category-attribute-filters {
				margin: 0 0 22px !important;
				padding: 0 !important;
				color: #173f32 !important;
			}
			#emo-category-attribute-filters .emo-category-attribute-filters__head {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:12px !important;
				margin:0 0 14px !important;
				padding:0 0 14px !important;
				border-bottom:1px solid rgba(23,63,50,.12) !important;
			}
			#emo-category-attribute-filters .emo-category-attribute-filters__eyebrow {
				color:#173f32 !important;
				font-size:12px !important;
				font-weight:850 !important;
				letter-spacing:.09em !important;
				line-height:1.25 !important;
				text-transform:uppercase !important;
			}
			#emo-category-attribute-filters .emo-category-attribute-filters__clear {
				color:#496158 !important;
				font-size:12px !important;
				font-weight:700 !important;
				text-decoration:underline !important;
				text-underline-offset:3px !important;
			}
			#emo-category-attribute-filters .emo-category-filter-group {
				margin:0 !important;
				padding:15px 0 !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.1) !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			#emo-category-attribute-filters .emo-category-filter-group:last-child {
				border-bottom:0 !important;
			}
			#emo-category-attribute-filters .emo-category-filter-title {
				margin:0 0 10px !important;
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:14px !important;
				font-weight:800 !important;
				letter-spacing:0 !important;
				line-height:1.35 !important;
				text-transform:none !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list {
				margin:0 !important;
				padding:0 !important;
				list-style:none !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item {
				margin:0 !important;
				padding:0 !important;
				list-style:none !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item a {
				position:relative !important;
				display:inline-flex !important;
				min-height:34px !important;
				align-items:center !important;
				padding:5px 0 5px 26px !important;
				color:#334c43 !important;
				font-size:13px !important;
				font-weight:600 !important;
				line-height:1.35 !important;
				text-decoration:none !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item a::before {
				content:"" !important;
				position:absolute !important;
				left:0 !important;
				top:50% !important;
				width:16px !important;
				height:16px !important;
				border:1px solid #9eaaa4 !important;
				border-radius:4px !important;
				background:#fff !important;
				transform:translateY(-50%) !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen a {
				color:#173f32 !important;
				font-weight:800 !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list__item--chosen a::before {
				border-color:#2f7d5d !important;
				background:#2f7d5d !important;
				box-shadow:inset 0 0 0 3px #fff !important;
			}
			#emo-category-attribute-filters .woocommerce-widget-layered-nav-list .count {
				margin-left:5px !important;
				color:#75857e !important;
				font-size:11px !important;
				font-weight:600 !important;
			}
			#emo-category-attribute-filters .emo-category-filter-productor .woocommerce-widget-layered-nav-list {
				max-height:260px !important;
				overflow:auto !important;
				padding-right:5px !important;
			}
			@media (max-width:1100px) {
				#emo-category-attribute-filters {
					margin-bottom:18px !important;
				}
				#emo-category-attribute-filters .emo-category-filter-group {
					padding:14px 0 !important;
				}
			}
		</style>
		<?php
	},
	1
);

add_action(
	'wp_footer',
	static function (): void {
		$profile = elmercado_catalog_filter_profile();
		if ( is_admin() || ! $profile ) {
			return;
		}
		?>
		<script id="elmercado-category-specific-filters-controller-010185">
		(() => {
			'use strict';
			const panel = document.getElementById('emo-category-attribute-filters');
			if (!panel) return;

			const findSidebar = () => document.querySelector(
				'.emo-mobile-filter-content #secondary.widget-area,' +
				'.emo-mobile-filter-content .shop-widget-area,' +
				'.emo-mobile-filter-content .widget-area,' +
				'#secondary.widget-area,' +
				'.shop-widget-area,' +
				'.content-area + .widget-area'
			);

			const syncClearLinks = () => {
				const clearUrl = panel.dataset.clearUrl || location.pathname;
				document.querySelectorAll('.emo-active-filters__clear').forEach((link) => {
					link.href = clearUrl;
				});
			};

			const place = () => {
				const sidebar = findSidebar();
				if (sidebar) {
					if (panel.parentElement !== sidebar) sidebar.prepend(panel);
					panel.hidden = false;
					panel.removeAttribute('aria-hidden');
				} else {
					const fallback = document.querySelector('.woostify-sorting,#primary,.content-area');
					if (fallback && panel.parentElement !== fallback.parentElement) fallback.insertAdjacentElement('afterend', panel);
					panel.hidden = false;
				}
				syncClearLinks();
			};

			place();
			requestAnimationFrame(place);
			setTimeout(place, 120);
			setTimeout(place, 650);
			setTimeout(syncClearLinks, 900);
			window.addEventListener('pageshow', place, { passive:true });
			window.addEventListener('resize', () => requestAnimationFrame(place), { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
