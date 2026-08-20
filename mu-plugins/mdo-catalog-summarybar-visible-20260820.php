<?php
/**
 * Plugin Name: MDO Catalog Summary Bar Visible
 * Description: Stable results + shipping destination row for desktop and mobile catalog views.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_summarybar_is_surface_20260820(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		return true;
	}
	return is_search() && 'product' === get_query_var( 'post_type' );
}

function mdo_catalog_summarybar_text_20260820( string $es, string $en ): string {
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
	return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? $en : $es;
}

function mdo_catalog_summarybar_render_20260820(): void {
	if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
		return;
	}

	$total = function_exists( 'elmercado_catalog_exact_result_total_010220' )
		? max( 0, (int) elmercado_catalog_exact_result_total_010220() )
		: max( 0, (int) ( $GLOBALS['wp_query']->found_posts ?? 0 ) );

	$destination = class_exists( 'MDO_Catalog_Destination_Frontend' )
		? MDO_Catalog_Destination_Frontend::current_destination()
		: array( 'country' => 'ES', 'postcode' => '' );
	$countries = class_exists( 'MDO_Catalog_Destination_Frontend' )
		? (array) MDO_Catalog_Destination_Frontend::supported_countries()
		: array( 'ES' => mdo_catalog_summarybar_text_20260820( 'España', 'Spain' ) );

	$country  = (string) ( $destination['country'] ?? 'ES' );
	$postcode = trim( (string) ( $destination['postcode'] ?? '' ) );
	$label    = (string) ( $countries[ $country ] ?? $country );
	if ( 'ES' === $country && '' !== $postcode ) {
		$label .= ' · ' . $postcode;
	}

	$result_label = sprintf(
		esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ),
		number_format_i18n( $total )
	);
	?>
	<div class="mdo-catalog-summarybar" data-mdo-catalog-summarybar>
		<span class="mdo-catalog-summarybar__count" aria-live="polite"><?php echo esc_html( $result_label ); ?></span>
		<button type="button" class="mdo-catalog-summarybar__destination" data-mdo-summary-destination-open>
			<span class="mdo-catalog-summarybar__pin" aria-hidden="true">⌖</span>
			<span><?php echo esc_html( mdo_catalog_summarybar_text_20260820( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label ); ?></strong></span>
			<span class="mdo-catalog-summarybar__chevron" aria-hidden="true">⌄</span>
		</button>
	</div>
	<?php
}
add_action( 'woocommerce_before_shop_loop', 'mdo_catalog_summarybar_render_20260820', 19 );

add_action(
	'wp_head',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-summarybar-visible-20260820">
			/* Old count/control stay available for logic/modal but never compete visually. */
			.emo-catalog-result-count-010220,
			.mdo-catalog-destination--canonical{display:none!important}
			.mdo-catalog-summarybar{
				box-sizing:border-box!important;
				display:flex!important;
				visibility:visible!important;
				opacity:1!important;
				align-items:center!important;
				justify-content:space-between!important;
				gap:10px!important;
				width:100%!important;
				max-width:none!important;
				min-height:40px!important;
				height:auto!important;
				margin:0 0 14px!important;
				padding:0!important;
				float:none!important;
				clear:both!important;
				position:relative!important;
				z-index:2!important;
				font-size:13px!important;
				line-height:1.35!important;
			}
			.mdo-catalog-summarybar__count{
				display:block!important;
				visibility:visible!important;
				opacity:1!important;
				margin:0!important;
				padding:0!important;
				white-space:nowrap!important;
				color:#53635d!important;
				font-size:13px!important;
				font-weight:500!important;
			}
			.mdo-catalog-summarybar__destination{
				box-sizing:border-box!important;
				display:inline-flex!important;
				visibility:visible!important;
				opacity:1!important;
				align-items:center!important;
				justify-content:center!important;
				gap:6px!important;
				min-height:36px!important;
				margin:0!important;
				padding:0 12px!important;
				border:1px solid rgba(23,63,50,.16)!important;
				border-radius:999px!important;
				background:#fff!important;
				box-shadow:none!important;
				color:#173f32!important;
				font:inherit!important;
				font-size:13px!important;
				line-height:1!important;
				white-space:nowrap!important;
				cursor:pointer!important;
			}
			.mdo-catalog-summarybar__destination strong{font-weight:750!important}
			.mdo-catalog-summarybar__pin{font-size:15px!important;line-height:1!important}
			.mdo-catalog-summarybar__chevron{font-size:16px!important;opacity:.65!important}
			@media(max-width:767px){
				.mdo-catalog-summarybar{
					display:flex!important;
					flex-wrap:nowrap!important;
					align-items:center!important;
					justify-content:space-between!important;
					gap:8px!important;
					margin:0 0 12px!important;
					min-height:36px!important;
				}
				.mdo-catalog-summarybar__count{font-size:12px!important;min-width:0!important;overflow:hidden!important;text-overflow:ellipsis!important}
				.mdo-catalog-summarybar__destination{min-height:34px!important;padding:0 10px!important;font-size:12px!important;flex:0 0 auto!important}
			}
			@media(max-width:360px){
				.mdo-catalog-summarybar{flex-wrap:wrap!important;justify-content:flex-start!important}
				.mdo-catalog-summarybar__destination{margin-left:0!important}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<script id="mdo-catalog-summarybar-visible-js-20260820">
		(() => {
			'use strict';
			const placeBar = () => {
				const bar = document.querySelector('[data-mdo-catalog-summarybar]');
				if (!bar) return;
				const ordering = document.querySelector('.woocommerce-ordering');
				if (ordering && ordering.parentElement && bar.parentElement !== ordering.parentElement) {
					const parent = ordering.parentElement;
					const style = getComputedStyle(parent);
					if (style.display.includes('flex')) parent.style.flexWrap = 'wrap';
					parent.insertBefore(bar, ordering);
					return;
				}
				const products = document.querySelector('ul.products');
				if (products && products.parentElement && bar.nextElementSibling !== products) {
					products.parentElement.insertBefore(bar, products);
				}
			};

			const summaryOpen = document.querySelector('[data-mdo-summary-destination-open]');
			if (summaryOpen) {
				summaryOpen.addEventListener('click', () => {
					const canonical = document.querySelector('.mdo-catalog-destination--canonical [data-mdo-destination-open]');
					if (canonical) {
						canonical.click();
						return;
					}
					const modal = document.querySelector('[data-mdo-destination-modal]');
					if (modal) {
						modal.hidden = false;
						modal.setAttribute('aria-hidden', 'false');
						document.body.classList.add('mdo-destination-modal-open');
					}
				});
			}

			placeBar();
			window.addEventListener('load', placeBar, {once:true});
			window.addEventListener('resize', placeBar, {passive:true});
			new MutationObserver(placeBar).observe(document.body, {childList:true, subtree:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
