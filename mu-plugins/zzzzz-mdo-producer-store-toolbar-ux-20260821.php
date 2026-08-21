<?php
/**
 * Plugin Name: MDO Producer Store Toolbar UX
 * Description: Keeps shipping destination inside the white catalogue toolbar, preserves it on empty producer stores, and prevents first-paint toolbar colour flashes.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_ps_toolbar_ux_is_store_20260821(): bool {
	return function_exists( 'mdo_ps_safe_is_store_20260821' ) && mdo_ps_safe_is_store_20260821();
}

function mdo_ps_toolbar_ux_is_surface_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( mdo_ps_toolbar_ux_is_store_20260821() ) {
		return true;
	}
	return function_exists( 'mdo_catalog_summarybar_is_surface_20260820' ) && mdo_catalog_summarybar_is_surface_20260820();
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( mdo_ps_toolbar_ux_is_store_20260821() ) {
			$classes[] = 'mdo-producer-store-toolbar-ux';
		}
		return $classes;
	},
	PHP_INT_MAX
);

/**
 * Lightweight trigger used only as a guaranteed source/fallback. The existing
 * destination modal and save logic remain owned by the already-verified safe
 * producer-store plugin.
 */
function mdo_ps_toolbar_ux_render_trigger_20260821( string $extra_attr = '' ): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821()
		|| ! function_exists( 'mdo_ps_safe_destination_20260821' )
		|| ! function_exists( 'mdo_ps_safe_countries_20260821' )
		|| ! function_exists( 'mdo_ps_safe_text_20260821' ) ) {
		return;
	}

	$destination = mdo_ps_safe_destination_20260821();
	$countries   = mdo_ps_safe_countries_20260821();
	$country     = strtoupper( (string) ( $destination['country'] ?? 'ES' ) );
	$postcode    = trim( (string) ( $destination['postcode'] ?? '' ) );
	$label       = (string) ( $countries[ $country ] ?? $country );
	if ( 'ES' === $country && '' !== $postcode ) {
		$label .= ' · ' . $postcode;
	}
	?>
	<div class="mdo-ps-destination mdo-catalog-destination mdo-catalog-destination--canonical" data-mdo-ps-destination-trigger-wrap <?php echo $extra_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> >
		<button type="button" class="mdo-ps-destination__trigger mdo-catalog-destination__trigger" data-mdo-ps-destination-open aria-haspopup="dialog" aria-controls="mdo-ps-destination-dialog">
			<span class="mdo-catalog-destination__label"><?php echo esc_html( mdo_ps_safe_text_20260821( 'Envío a', 'Shipping to' ) ); ?> <strong><?php echo esc_html( $label ); ?></strong></span>
			<span class="mdo-catalog-destination__chevron" aria-hidden="true">
				<svg viewBox="0 0 16 10" width="12" height="8" focusable="false"><path d="M2 2.2 8 8l6-5.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			</span>
		</button>
	</div>
	<?php
}

/**
 * WooCommerce omits before_shop_loop completely when a query has no products.
 * Render the same white toolbar before the no-products notice so the destination
 * can always be changed again.
 */
function mdo_ps_toolbar_ux_empty_toolbar_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<div class="woostify-sorting emo-catalog-toolbar-shared-010229 mdo-ps-toolbar-host mdo-ps-toolbar-empty" data-mdo-ps-toolbar-empty-server data-mdo-ps-toolbar-host>
		<div class="woostify-toolbar-left elmercado-vendor-filter-hidden">
			<?php mdo_ps_toolbar_ux_render_trigger_20260821( 'data-mdo-ps-empty-trigger' ); ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_no_products_found', 'mdo_ps_toolbar_ux_empty_toolbar_20260821', 1 );

/**
 * Guaranteed pre-modal source. It is printed before the verified producer
 * modal script; therefore, on a zero-product template that does not fire the
 * WooCommerce empty hook, the existing modal script can still bind this trigger.
 */
function mdo_ps_toolbar_ux_source_trigger_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<div data-mdo-ps-toolbar-ux-source hidden aria-hidden="true">
		<?php mdo_ps_toolbar_ux_render_trigger_20260821( 'data-mdo-ps-source-trigger' ); ?>
	</div>
	<?php
}
add_action( 'wp_footer', 'mdo_ps_toolbar_ux_source_trigger_20260821', 1 );

/**
 * Critical first-paint CSS. It is intentionally emitted at the end of wp_head:
 * after theme/enqueued CSS but before body paint. The older complete layout CSS
 * may still load later; these values already match its final white/green state.
 */
function mdo_ps_toolbar_ux_critical_css_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_surface_20260821() ) {
		return;
	}
	?>
	<style id="mdo-toolbar-critical-first-paint-20260821" data-mdo-toolbar-critical-first-paint="1">
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229,
		html body.mdo-producer-store-toolbar-ux .woostify-sorting,
		html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-host {
			box-sizing:border-box !important;
			width:100% !important;
			min-width:0 !important;
			height:auto !important;
			min-height:68px !important;
			max-height:none !important;
			align-items:center !important;
			overflow:visible !important;
			margin:0 0 16px !important;
			padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;
			border-radius:16px !important;
			background:#fff !important;
			background-color:#fff !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			color:#173f32 !important;
		}
		html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-host {
			display:flex !important;
			justify-content:space-between !important;
			gap:18px !important;
		}
		html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-host > .woostify-toolbar-left,
		html body.mdo-producer-store-toolbar-ux .woostify-sorting > .woostify-toolbar-left {
			display:flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			flex:1 1 auto !important;
			width:auto !important;
			min-width:0 !important;
			min-height:42px !important;
			align-items:center !important;
			gap:15px !important;
			overflow:visible !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
		}
		html body .mdo-ps-destination,
		html body .mdo-catalog-destination--canonical {
			display:inline-flex !important;
			visibility:visible !important;
			opacity:1 !important;
			position:static !important;
			box-sizing:border-box !important;
			width:auto !important;
			min-width:0 !important;
			max-width:100% !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 !important;
			float:none !important;
			transform:none !important;
		}
		html body .mdo-ps-destination__trigger,
		html body .mdo-catalog-destination__trigger {
			display:grid !important;
			grid-template-columns:minmax(0,1fr) 16px !important;
			column-gap:9px !important;
			box-sizing:border-box !important;
			width:auto !important;
			min-width:0 !important;
			max-width:100% !important;
			height:42px !important;
			min-height:42px !important;
			max-height:42px !important;
			align-items:center !important;
			margin:0 !important;
			padding:0 13px 0 14px !important;
			border:1px solid rgba(23,63,50,.15) !important;
			border-radius:999px !important;
			background:#f8faf8 !important;
			background-color:#f8faf8 !important;
			box-shadow:none !important;
			color:#173f32 !important;
			font-family:inherit !important;
			font-size:12.5px !important;
			font-weight:600 !important;
			line-height:1 !important;
			white-space:nowrap !important;
			cursor:pointer !important;
		}
		html body .mdo-ps-destination__trigger > svg:first-child {
			display:none !important;
		}
		html body .mdo-ps-destination__trigger > svg:last-child {
			display:block !important;
			width:12px !important;
			height:8px !important;
			margin:0 !important;
		}
		html body .mdo-catalog-destination__label {
			display:block !important;
			min-width:0 !important;
			overflow:hidden !important;
			text-overflow:ellipsis !important;
			white-space:nowrap !important;
			line-height:1.2 !important;
		}
		html body .mdo-ps-destination__trigger strong,
		html body .mdo-catalog-destination__trigger strong {
			color:inherit !important;
			font-weight:760 !important;
		}
		html body .mdo-catalog-destination__chevron {
			display:grid !important;
			place-items:center !important;
			width:16px !important;
			height:16px !important;
			min-width:16px !important;
			margin:0 !important;
			padding:0 !important;
			line-height:0 !important;
			opacity:.72 !important;
		}
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
		html body.mdo-producer-store-toolbar-ux .woostify-sorting .woocommerce-ordering select {
			background-color:#f8faf8 !important;
			color:#173f32 !important;
			border-color:rgba(23,63,50,.15) !important;
		}
		@media (max-width:640px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229,
			html body.mdo-producer-store-toolbar-ux .woostify-sorting,
			html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-host {
				min-height:0 !important;
				max-height:none !important;
				height:auto !important;
				padding:11px !important;
				border-radius:15px !important;
			}
			html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-empty {
				display:block !important;
			}
			html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-empty > .woostify-toolbar-left {
				display:block !important;
				min-height:40px !important;
			}
			html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-empty .mdo-ps-destination,
			html body.mdo-producer-store-toolbar-ux .mdo-ps-toolbar-empty .mdo-ps-destination__trigger {
				width:100% !important;
			}
			html body .mdo-ps-destination__trigger,
			html body .mdo-catalog-destination__trigger {
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 12px 0 13px !important;
				font-size:11.75px !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_ps_toolbar_ux_critical_css_20260821', PHP_INT_MAX );

/**
 * The normal producer trigger is rendered by WCFM outside the native toolbar.
 * Re-parent that already-bound node into the toolbar. If the toolbar itself is
 * omitted because there are zero products, create the same white shell first.
 */
function mdo_ps_toolbar_ux_mount_script_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<script id="mdo-ps-toolbar-ux-js-20260821">
	(() => {
		'use strict';
		const source = document.querySelector('[data-mdo-ps-toolbar-ux-source]');
		const allTriggers = Array.from(document.querySelectorAll('[data-mdo-ps-destination-open]'));
		let trigger = allTriggers.find((node) => !source || !source.contains(node));
		if (!trigger && source) trigger = source.querySelector('[data-mdo-ps-destination-open]');
		if (!trigger) return;

		let wrap = trigger.closest('[data-mdo-ps-destination-trigger-wrap]');
		if (!wrap) return;
		wrap.classList.add('mdo-catalog-destination', 'mdo-catalog-destination--canonical');
		trigger.classList.add('mdo-catalog-destination__trigger');
		const directSvgs = Array.from(trigger.children).filter((node) => node.tagName && node.tagName.toLowerCase() === 'svg');
		if (directSvgs.length > 1) {
			directSvgs[0].classList.add('mdo-ps-pin-to-hide');
			directSvgs[directSvgs.length - 1].classList.add('mdo-ps-chevron-direct');
		}

		const candidates = Array.from(document.querySelectorAll('.woostify-sorting.emo-catalog-toolbar-shared-010229, .woostify-sorting'));
		let toolbar = candidates.find((node) => !source || !source.contains(node));
		if (!toolbar) {
			toolbar = document.createElement('div');
			toolbar.className = 'woostify-sorting emo-catalog-toolbar-shared-010229 mdo-ps-toolbar-host mdo-ps-toolbar-empty';
			toolbar.setAttribute('data-mdo-ps-toolbar-host', '');
			const anchor = document.querySelector('.woocommerce-no-products-found, .woocommerce-info, ul.products, .products, .wcfmmp-store-info, main#main, .site-main');
			if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(toolbar, anchor);
			else document.body.appendChild(toolbar);
		}
		toolbar.classList.add('emo-catalog-toolbar-shared-010229', 'mdo-ps-toolbar-host');
		toolbar.setAttribute('data-mdo-ps-toolbar-host', '');

		let left = Array.from(toolbar.children).find((node) => node.classList && node.classList.contains('woostify-toolbar-left'));
		if (!left) {
			left = document.createElement('div');
			left.className = 'woostify-toolbar-left elmercado-vendor-filter-hidden';
			toolbar.insertBefore(left, toolbar.firstChild);
		}
		left.appendChild(wrap);
		wrap.setAttribute('data-mdo-ps-mounted', '1');

		allTriggers.forEach((other) => {
			if (other === trigger) return;
			const otherWrap = other.closest('[data-mdo-ps-destination-trigger-wrap]');
			if (otherWrap && otherWrap !== wrap) otherWrap.remove();
		});
		if (source) source.remove();
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_ps_toolbar_ux_mount_script_20260821', PHP_INT_MAX );
