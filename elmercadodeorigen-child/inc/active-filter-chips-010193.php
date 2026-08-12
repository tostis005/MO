<?php
/**
 * Filtros aplicados globales 0.10.193.
 *
 * Agrupa precio, vendedor y atributos activos en un único bloque superior del
 * sidebar, tanto en la tienda como dentro de categorías.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL actual del catálogo, sin paginación.
 */
function elmercado_active_filters_current_url(): string {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	$url         = home_url( $request_uri );

	return remove_query_arg( array( 'product-page', 'paged' ), $url );
}

/**
 * Devuelve todos los filtros activos con su URL de eliminación individual.
 */
function elmercado_active_filter_chips(): array {
	$is_catalog = function_exists( 'is_shop' ) && function_exists( 'is_product_category' ) && ( is_shop() || is_product_category() );
	if ( ! $is_catalog || ! class_exists( 'WC_Query' ) ) {
		return array();
	}

	$chips   = array();
	$current = elmercado_active_filters_current_url();

	$has_min = isset( $_GET['min_price'] ) && '' !== (string) $_GET['min_price']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$has_max = isset( $_GET['max_price'] ) && '' !== (string) $_GET['max_price']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $has_min || $has_max ) {
		$min = $has_min ? (float) wc_clean( wp_unslash( $_GET['min_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$max = $has_max ? (float) wc_clean( wp_unslash( $_GET['max_price'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( null !== $min && null !== $max ) {
			$name = wc_format_localized_price( $min ) . '–' . wc_format_localized_price( $max ) . ' €';
		} elseif ( null !== $min ) {
			$name = 'Desde ' . wc_format_localized_price( $min ) . ' €';
		} else {
			$name = 'Hasta ' . wc_format_localized_price( (float) $max ) . ' €';
		}

		$chips[] = array(
			'group' => 'Precio',
			'name'  => $name,
			'url'   => remove_query_arg( array( 'min_price', 'max_price' ), $current ),
		);
	}

	$vendor_id = isset( $_GET['vendor_id'] ) ? absint( wp_unslash( $_GET['vendor_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if (
		$vendor_id > 0 &&
		function_exists( 'elmercado_core_filter_is_vendor' ) &&
		elmercado_core_filter_is_vendor( $vendor_id ) &&
		function_exists( 'elmercado_core_filter_vendor_name' )
	) {
		$vendor_name = elmercado_core_filter_vendor_name( $vendor_id );
		if ( '' !== $vendor_name ) {
			$chips[] = array(
				'group' => 'Vendedor',
				'name'  => $vendor_name,
				'url'   => remove_query_arg( 'vendor_id', $current ),
			);
		}
	}

	$profile = function_exists( 'elmercado_catalog_filter_profile' ) ? elmercado_catalog_filter_profile() : null;
	$chosen  = WC_Query::get_layered_nav_chosen_attributes();
	if ( $profile ) {
		foreach ( (array) ( $profile['attributes'] ?? array() ) as $attribute_slug => $attribute_label ) {
			$taxonomy = wc_attribute_taxonomy_name( $attribute_slug );
			$terms    = isset( $chosen[ $taxonomy ]['terms'] ) ? (array) $chosen[ $taxonomy ]['terms'] : array();
			if ( ! $terms ) {
				continue;
			}

			$filter_param = 'filter_' . $attribute_slug;
			$query_param  = 'query_type_' . $attribute_slug;
			$raw_value    = isset( $_GET[ $filter_param ] ) ? wc_clean( wp_unslash( $_GET[ $filter_param ] ) ) : implode( ',', $terms ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$selected     = array_values( array_filter( array_map( 'sanitize_title', explode( ',', (string) $raw_value ) ) ) );

			foreach ( $terms as $term_slug ) {
				$term_slug = sanitize_title( $term_slug );
				$term      = get_term_by( 'slug', $term_slug, $taxonomy );
				$name      = $term instanceof WP_Term ? $term->name : $term_slug;
				$remaining = array_values( array_diff( $selected, array( $term_slug ) ) );
				$remove    = $current;

				if ( $remaining ) {
					$remove = add_query_arg( $filter_param, implode( ',', $remaining ), $remove );
				} else {
					$remove = remove_query_arg( array( $filter_param, $query_param ), $remove );
				}

				$chips[] = array(
					'group' => (string) $attribute_label,
					'name'  => (string) $name,
					'url'   => $remove,
				);
			}
		}
	}

	return $chips;
}

/**
 * URL para limpiar todos los filtros manteniendo el contexto de categoría.
 */
function elmercado_active_filters_clear_url(): string {
	if ( function_exists( 'is_product_category' ) && is_product_category() && function_exists( 'elmercado_catalog_filter_clear_url' ) ) {
		return elmercado_catalog_filter_clear_url();
	}

	if ( function_exists( 'wc_get_page_permalink' ) ) {
		return wc_get_page_permalink( 'shop' );
	}

	return home_url( '/tienda/' );
}

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		$chips = elmercado_active_filter_chips();
		if ( is_admin() || ! $chips ) {
			return;
		}
		?>
		<template id="emo-active-filter-chips-template">
			<section class="emo-active-filter-chips" data-emo-global-active-filters="true" aria-label="Filtros aplicados">
				<div class="emo-active-filter-chips__head">
					<strong>Filtros aplicados</strong>
					<a class="emo-active-filter-chips__clear" href="<?php echo esc_url( elmercado_active_filters_clear_url() ); ?>">Limpiar todo</a>
				</div>
				<div class="emo-active-filter-chips__list">
					<?php foreach ( $chips as $chip ) : ?>
						<a
							class="emo-active-filter-chip"
							href="<?php echo esc_url( $chip['url'] ); ?>"
							aria-label="<?php echo esc_attr( 'Quitar ' . $chip['group'] . ': ' . $chip['name'] ); ?>"
						>
							<span class="emo-active-filter-chip__group"><?php echo esc_html( $chip['group'] ); ?></span>
							<span class="emo-active-filter-chip__name"><?php echo esc_html( $chip['name'] ); ?></span>
							<span class="emo-active-filter-chip__remove" aria-hidden="true">×</span>
						</a>
					<?php endforeach; ?>
				</div>
			</section>
		</template>
		<?php
	},
	39
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) || ( ! is_shop() && ! is_product_category() ) ) {
			return;
		}
		?>
		<style id="elmercado-active-filter-chips-010193">
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] {
				box-sizing:border-box !important;
				width:100% !important;
				margin:0 0 10px !important;
				padding:0 0 12px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.10) !important;
				background:transparent !important;
				box-shadow:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chips__head {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:8px !important;
				margin:0 0 8px !important;
				padding:0 2px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chips__head strong {
				color:#173f32 !important;
				font-family:inherit !important;
				font-size:11.5px !important;
				font-weight:800 !important;
				line-height:1.2 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chips__clear {
				display:inline-flex !important;
				align-items:center !important;
				justify-content:center !important;
				min-height:28px !important;
				margin:0 !important;
				padding:6px 10px !important;
				border:1px solid #173f32 !important;
				border-radius:999px !important;
				background:#173f32 !important;
				background-image:none !important;
				box-shadow:none !important;
				color:#fff !important;
				font-size:10.5px !important;
				font-weight:800 !important;
				line-height:1 !important;
				text-decoration:none !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chips__clear:hover,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chips__clear:focus-visible {
				border-color:#2f7d5d !important;
				background:#2f7d5d !important;
				color:#fff !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chips__list {
				display:flex !important;
				flex-wrap:wrap !important;
				gap:6px !important;
				margin:0 !important;
				padding:0 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip {
				display:inline-flex !important;
				max-width:100% !important;
				align-items:center !important;
				gap:4px !important;
				min-height:27px !important;
				margin:0 !important;
				padding:5px 8px !important;
				border:1px solid rgba(47,125,93,.16) !important;
				border-radius:999px !important;
				background:#edf4ef !important;
				color:#294c3f !important;
				font-size:10.5px !important;
				font-weight:650 !important;
				line-height:1.15 !important;
				text-decoration:none !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip__group {
				color:#71827a !important;
				font-size:9.5px !important;
				font-weight:700 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip__group::after {
				content:"·" !important;
				margin-left:4px !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip__name {
				min-width:0 !important;
				overflow:hidden !important;
				text-overflow:ellipsis !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip__remove {
				flex:0 0 auto !important;
				margin-left:1px !important;
				color:#496158 !important;
				font-size:14px !important;
				font-weight:500 !important;
				line-height:.8 !important;
			}
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip:hover,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat) .emo-active-filter-chips[data-emo-global-active-filters="true"] .emo-active-filter-chip:focus-visible {
				border-color:rgba(47,125,93,.36) !important;
				background:#e4efe8 !important;
				color:#173f32 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_category' ) || ( ! is_shop() && ! is_product_category() ) ) {
			return;
		}
		?>
		<script id="elmercado-active-filter-chips-controller-010193">
		(() => {
			'use strict';

			const findSidebar = () => document.querySelector(
				'.emo-mobile-filter-content #secondary.widget-area,' +
				'.emo-mobile-filter-content .shop-widget-area,' +
				'.emo-mobile-filter-content .widget-area,' +
				'#secondary.widget-area,' +
				'.shop-widget-area,' +
				'.content-area + .widget-area'
			);

			const mount = () => {
				const sidebar = findSidebar();
				const template = document.getElementById('emo-active-filter-chips-template');
				if (!sidebar || !template) return;

				let node = sidebar.querySelector(':scope > .emo-active-filter-chips[data-emo-global-active-filters="true"]');
				if (!node) {
					node = template.content.firstElementChild?.cloneNode(true);
					if (!node) return;
					sidebar.prepend(node);
				}

				sidebar.querySelectorAll('#emo-category-attribute-filters .emo-active-filter-chips').forEach((legacy) => legacy.remove());
				if (sidebar.firstElementChild !== node) sidebar.prepend(node);
			};

			mount();
			requestAnimationFrame(mount);
			setTimeout(mount, 180);
			setTimeout(mount, 720);
			setTimeout(mount, 1300);
			window.addEventListener('pageshow', mount, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
