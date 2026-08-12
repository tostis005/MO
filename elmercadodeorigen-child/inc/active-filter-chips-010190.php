<?php
/**
 * Filtros aplicados eliminables 0.10.190.
 *
 * Muestra los refinamientos activos como chips independientes y ofrece una
 * acción clara de "Limpiar todo" que conserva la categoría actual.
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
 * Devuelve los filtros activos del perfil con su URL de eliminación individual.
 */
function elmercado_active_filter_chips(): array {
	$profile = function_exists( 'elmercado_catalog_filter_profile' ) ? elmercado_catalog_filter_profile() : null;
	if ( ! $profile || ! class_exists( 'WC_Query' ) ) {
		return array();
	}

	$chips   = array();
	$current = elmercado_active_filters_current_url();
	$chosen  = WC_Query::get_layered_nav_chosen_attributes();

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

	return $chips;
}

add_action(
	'woocommerce_before_main_content',
	static function (): void {
		$chips = elmercado_active_filter_chips();
		if ( is_admin() || ! $chips ) {
			return;
		}

		$clear_url = function_exists( 'elmercado_catalog_filter_clear_url' ) ? elmercado_catalog_filter_clear_url() : remove_query_arg( array_keys( $_GET ), elmercado_active_filters_current_url() ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<template id="emo-active-filter-chips-template">
			<section class="emo-active-filter-chips" aria-label="Filtros aplicados">
				<div class="emo-active-filter-chips__head">
					<strong>Filtros aplicados</strong>
					<a class="emo-active-filter-chips__clear" href="<?php echo esc_url( $clear_url ); ?>">Limpiar todo</a>
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
		if ( is_admin() || ! function_exists( 'elmercado_catalog_filter_profile' ) || ! elmercado_catalog_filter_profile() ) {
			return;
		}
		?>
		<style id="elmercado-active-filter-chips-010190">
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-category-filter-actions {
				display:none !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips {
				margin:5px 0 3px !important;
				padding:9px 0 12px !important;
				border:0 !important;
				border-bottom:1px solid rgba(23,63,50,.10) !important;
				background:transparent !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips__head {
				display:flex !important;
				align-items:center !important;
				justify-content:space-between !important;
				gap:8px !important;
				margin:0 0 8px !important;
				padding:0 2px !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips__head strong {
				color:#173f32 !important;
				font-size:11.5px !important;
				font-weight:800 !important;
				line-height:1.2 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips__clear {
				display:inline-flex !important;
				align-items:center !important;
				justify-content:center !important;
				min-height:26px !important;
				margin:0 !important;
				padding:5px 9px !important;
				border:1px solid #173f32 !important;
				border-radius:999px !important;
				background:#173f32 !important;
				color:#fff !important;
				font-size:10.5px !important;
				font-weight:750 !important;
				line-height:1 !important;
				text-decoration:none !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips__clear:hover,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips__clear:focus-visible {
				background:#2f7d5d !important;
				border-color:#2f7d5d !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chips__list {
				display:flex !important;
				flex-wrap:wrap !important;
				gap:6px !important;
				margin:0 !important;
				padding:0 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip {
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
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip__group {
				color:#71827a !important;
				font-size:9.5px !important;
				font-weight:700 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip__group::after {
				content:"·" !important;
				margin-left:4px !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip__name {
				min-width:0 !important;
				overflow:hidden !important;
				text-overflow:ellipsis !important;
				white-space:nowrap !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip__remove {
				flex:0 0 auto !important;
				margin-left:1px !important;
				color:#496158 !important;
				font-size:14px !important;
				font-weight:500 !important;
				line-height:.8 !important;
			}
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip:hover,
			body.elmercado-child-theme.tax-product_cat #emo-category-attribute-filters .emo-active-filter-chip:focus-visible {
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
		if ( is_admin() || ! function_exists( 'elmercado_catalog_filter_profile' ) || ! elmercado_catalog_filter_profile() ) {
			return;
		}
		?>
		<script id="elmercado-active-filter-chips-controller-010190">
		(() => {
			'use strict';
			const mount = () => {
				const panel = document.getElementById('emo-category-attribute-filters');
				const template = document.getElementById('emo-active-filter-chips-template');
				if (!panel || !template || panel.querySelector('.emo-active-filter-chips')) return;
				const node = template.content.firstElementChild?.cloneNode(true);
				if (!node) return;
				const groups = panel.querySelector('.emo-category-attribute-filters__groups');
				if (groups) groups.insertAdjacentElement('beforebegin', node);
				else panel.prepend(node);
				panel.querySelectorAll('.emo-category-filter-actions').forEach((actions) => actions.remove());
			};

			mount();
			requestAnimationFrame(mount);
			setTimeout(mount, 180);
			setTimeout(mount, 720);
			window.addEventListener('pageshow', mount, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
