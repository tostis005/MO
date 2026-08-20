<?php
/**
 * Plugin Name: MDO Catalog Summary Bar Visible
 * Description: One stable server-rendered results + shipping destination row for desktop and mobile catalog views.
 * Version: 1.4.2
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

/*
 * Remove the old count at hook level after the child theme has registered it.
 * This avoids a second result node entirely instead of fighting later !important CSS.
 */
add_action(
	'wp',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
		remove_action( 'woocommerce_before_shop_loop', 'elmercado_catalog_render_exact_result_count_010220', 21 );
	},
	1
);

function mdo_catalog_summarybar_render_20260820(): void {
	static $rendered = false;
	if ( $rendered || ! mdo_catalog_summarybar_is_surface_20260820() ) {
		return;
	}
	$rendered = true;

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
		<span class="mdo-catalog-summarybar__count" data-mdo-summary-count aria-live="polite"><?php echo esc_html( $result_label ); ?></span>
		<button type="button" class="mdo-catalog-summarybar__destination" data-mdo-summary-destination-open aria-haspopup="dialog" aria-controls="mdo-catalog-destination-dialog">
			<svg class="mdo-catalog-summarybar__pin" aria-hidden="true" viewBox="0 0 24 24" width="15" height="15"><path d="M12 21s6-5.2 6-11a6 6 0 1 0-12 0c0 5.8 6 11 6 11Zm0-8.5A2.5 2.5 0 1 1 12 7a2.5 2.5 0 0 1 0 5.5Z" fill="currentColor"/></svg>
			<span><?php echo esc_html( mdo_catalog_summarybar_text_20260820( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label ); ?></strong></span>
			<svg class="mdo-catalog-summarybar__chevron" aria-hidden="true" viewBox="0 0 20 20" width="13" height="13"><path d="m5 7.5 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
		</button>
	</div>
	<?php
}

/* Render once, outside Woostify's mobile-hidden left toolbar. */
add_action( 'woocommerce_shop_loop_header', 'mdo_catalog_summarybar_render_20260820', 99 );
add_action( 'woocommerce_before_shop_loop', 'mdo_catalog_summarybar_render_20260820', 1 );

add_action(
	'wp_head',
	static function (): void {
		if ( ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-summarybar-visible-20260820">
			/* CSS fallback; the historical result callback is also removed in PHP above. */
			body .woocommerce-result-count,
			body .emo-catalog-result-count-010220,
			body .mdo-catalog-destination--canonical,
			body .mdo-catalog-toolbar__count,
			body .mdo-catalog-toolbar__destination{display:none!important}

			body .mdo-catalog-summarybar{
				display:flex!important;visibility:visible!important;opacity:1!important;
				align-items:center;justify-content:space-between;gap:12px;
				width:100%;min-height:34px;margin:0 0 14px;padding:0;
				clear:both;float:none;box-sizing:border-box;
			}
			body .mdo-catalog-summarybar__count{
				display:block!important;visibility:visible!important;opacity:1!important;
				margin:0;color:#5b6964;font-size:13px;font-weight:500;line-height:1.4;white-space:nowrap;
			}
			body .mdo-catalog-summarybar__destination{
				display:inline-flex!important;visibility:visible!important;opacity:1!important;
				align-items:center;gap:6px;min-height:34px;margin:0;padding:0 11px;
				border:1px solid rgba(23,63,50,.18);border-radius:999px;background:#fff;box-shadow:none;
				color:#173f32;font:inherit;font-size:13px;line-height:1;white-space:nowrap;cursor:pointer;
			}
			body .mdo-catalog-summarybar__destination:hover,
			body .mdo-catalog-summarybar__destination:focus-visible{border-color:rgba(23,63,50,.35);background:#fbfdfc;outline:none}
			body .mdo-catalog-summarybar__destination strong{font-weight:700}
			body .mdo-catalog-summarybar__pin,body .mdo-catalog-summarybar__chevron{flex:0 0 auto}
			body .mdo-catalog-summarybar__chevron{opacity:.65}

			@media(max-width:767px){
				body .mdo-catalog-summarybar{gap:8px;margin:0 0 12px;padding:0}
				body .mdo-catalog-summarybar__count{font-size:12px}
				body .mdo-catalog-summarybar__destination{min-height:32px;padding:0 9px;font-size:12px}
			}
			@media(max-width:360px){body .mdo-catalog-summarybar{flex-wrap:wrap;justify-content:flex-start}}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/** Simple click bridge only. No MutationObserver, DOM movement, cloning or resize listeners. */
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
			const button=document.querySelector('[data-mdo-summary-destination-open]');
			if(!button)return;
			button.addEventListener('click',()=>{
				const canonical=document.querySelector('.mdo-catalog-destination--canonical [data-mdo-destination-open]');
				if(canonical)canonical.click();
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
