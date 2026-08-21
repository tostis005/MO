<?php
/**
 * Plugin Name: MDO Producer Store Toolbar UX
 * Description: Keeps producer destination controls inside the producer toolbar and gives main shop and producer shop the same stable visual contract from first paint.
 * Version: 1.2.0
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

/** Keep the destination available even when the selected country leaves no products. */
function mdo_ps_toolbar_ux_empty_toolbar_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<div class="woostify-sorting elmercado-vendor-sorting-normalized mdo-ps-toolbar-host mdo-ps-toolbar-empty" data-mdo-ps-toolbar-empty-server data-mdo-ps-toolbar-host>
		<div class="elmercado-vendor-toolbar mdo-ps-toolbar-integrated mdo-ps-toolbar-empty-inner" role="group">
			<?php mdo_ps_toolbar_ux_render_trigger_20260821( 'data-mdo-ps-empty-trigger' ); ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_no_products_found', 'mdo_ps_toolbar_ux_empty_toolbar_20260821', 1 );

/** Hidden guaranteed source, printed before the already-verified producer modal binder. */
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
 * Final visual contract. Values intentionally mirror the main-shop toolbar from
 * mdo-catalog-summarybar-visible-20260820.php. Producer-specific legacy CSS can
 * no longer create a different grid, height, radius or mobile stacking pattern.
 */
function mdo_ps_toolbar_ux_critical_css_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_surface_20260821() ) {
		return;
	}
	?>
	<style id="mdo-toolbar-critical-first-paint-20260821" data-mdo-toolbar-critical-first-paint="1">
		/* Main shop: final surface exists before body paint, removing the old colour flash. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
			display:flex !important;box-sizing:border-box !important;width:100% !important;min-width:0 !important;max-width:100% !important;
			height:auto !important;min-height:68px !important;max-height:none !important;align-items:center !important;justify-content:space-between !important;
			gap:18px !important;overflow:visible !important;margin:0 0 16px !important;padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;border-radius:16px !important;background:#fff !important;background-color:#fff !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;color:#173f32 !important;
		}

		/* Producer outer host is only a transparent structural shell after normalization. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-sorting-normalized {
			display:block !important;box-sizing:border-box !important;width:100% !important;min-width:0 !important;max-width:100% !important;height:auto !important;
			min-height:0 !important;margin:0 !important;padding:0 !important;border:0 !important;border-radius:0 !important;background:transparent !important;
			background-color:transparent !important;box-shadow:none !important;overflow:visible !important;float:none !important;clear:both !important;
		}

		/* Before JS normalizes WCFM, its native sorting host already looks like the final white toolbar. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting:not(.elmercado-vendor-sorting-normalized) {
			display:flex !important;box-sizing:border-box !important;width:100% !important;min-width:0 !important;max-width:100% !important;
			height:auto !important;min-height:68px !important;max-height:none !important;align-items:center !important;justify-content:space-between !important;
			gap:18px !important;overflow:visible !important;margin:0 0 16px !important;padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;border-radius:16px !important;background:#fff !important;background-color:#fff !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;color:#173f32 !important;
		}

		/* Producer final toolbar: exactly the same surface and one-row desktop rhythm as the shop. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
			display:flex !important;box-sizing:border-box !important;width:100% !important;min-width:0 !important;max-width:100% !important;
			height:auto !important;min-height:68px !important;max-height:none !important;align-items:center !important;justify-content:flex-start !important;
			gap:15px !important;overflow:visible !important;margin:0 0 16px !important;padding:12px 14px !important;
			border:1px solid rgba(23,63,50,.11) !important;border-radius:16px !important;background:#fff !important;background-color:#fff !important;
			box-shadow:0 10px 28px rgba(17,42,34,.055) !important;color:#173f32 !important;position:relative !important;inset:auto !important;
			transform:none !important;transition:none !important;float:none !important;clear:both !important;
		}

		/* Same result-count typography and vertical alignment as main shop. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-result-count {
			display:inline-flex !important;visibility:visible !important;opacity:1 !important;position:static !important;inset:auto !important;
			box-sizing:border-box !important;flex:0 0 auto !important;width:auto !important;min-width:0 !important;max-width:none !important;
			height:auto !important;min-height:42px !important;max-height:none !important;align-items:center !important;overflow:visible !important;
			margin:0 !important;padding:0 2px !important;float:none !important;clear:none !important;color:#53665f !important;
			font-family:inherit !important;font-size:12.5px !important;font-weight:700 !important;line-height:1.35 !important;white-space:nowrap !important;
		}

		/* Destination pill: same size, weight, radius and spacing as main shop. */
		html body .mdo-ps-destination,
		html body .mdo-catalog-destination--canonical {
			display:inline-flex !important;visibility:visible !important;opacity:1 !important;position:static !important;box-sizing:border-box !important;
			flex:0 1 auto !important;width:auto !important;min-width:0 !important;max-width:100% !important;align-items:center !important;
			margin:0 !important;padding:0 !important;float:none !important;transform:none !important;
		}
		html body .mdo-ps-destination__trigger,
		html body .mdo-catalog-destination__trigger {
			display:grid !important;grid-template-columns:minmax(0,1fr) 16px !important;column-gap:9px !important;visibility:visible !important;opacity:1 !important;
			box-sizing:border-box !important;width:auto !important;min-width:0 !important;max-width:100% !important;height:42px !important;min-height:42px !important;max-height:42px !important;
			align-items:center !important;margin:0 !important;padding:0 13px 0 14px !important;border:1px solid rgba(23,63,50,.15) !important;
			border-radius:999px !important;background:#f8faf8 !important;background-color:#f8faf8 !important;box-shadow:none !important;color:#173f32 !important;
			font-family:inherit !important;font-size:12.5px !important;font-weight:600 !important;line-height:1 !important;white-space:nowrap !important;cursor:pointer !important;
			transition:background-color .15s ease,border-color .15s ease,box-shadow .15s ease !important;
		}
		html body .mdo-ps-destination__trigger:hover,
		html body .mdo-ps-destination__trigger:focus-visible,
		html body .mdo-catalog-destination__trigger:hover,
		html body .mdo-catalog-destination__trigger:focus-visible {
			background:#f2f6f3 !important;border-color:rgba(23,63,50,.30) !important;box-shadow:0 0 0 3px rgba(23,63,50,.055) !important;color:#173f32 !important;outline:none !important;
		}
		html body .mdo-catalog-destination__label {
			display:block !important;min-width:0 !important;overflow:hidden !important;text-overflow:ellipsis !important;white-space:nowrap !important;line-height:1.2 !important;
		}
		html body .mdo-ps-destination__trigger strong,
		html body .mdo-catalog-destination__trigger strong {color:inherit !important;font-weight:760 !important;}
		html body .mdo-catalog-destination__chevron {
			display:grid !important;place-items:center !important;width:16px !important;height:16px !important;min-width:16px !important;
			margin:0 !important;padding:0 !important;line-height:0 !important;opacity:.72 !important;
		}
		html body .mdo-catalog-destination__chevron svg {display:block !important;width:12px !important;height:8px !important;margin:0 !important;}

		/* Ordering: same right-aligned pill geometry as main shop. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-ordering {
			display:flex !important;visibility:visible !important;opacity:1 !important;position:static !important;inset:auto !important;
			box-sizing:border-box !important;flex:0 1 248px !important;width:clamp(190px,22vw,248px) !important;min-width:170px !important;max-width:248px !important;
			height:42px !important;min-height:42px !important;max-height:42px !important;align-items:center !important;margin:0 0 0 auto !important;padding:0 !important;
			float:none !important;clear:none !important;transform:none !important;
		}
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-ordering select {
			display:block !important;box-sizing:border-box !important;width:100% !important;min-width:0 !important;max-width:100% !important;
			height:42px !important;min-height:42px !important;max-height:42px !important;margin:0 !important;padding:0 34px 0 13px !important;
			border:1px solid rgba(23,63,50,.15) !important;border-radius:999px !important;background:#f8faf8 !important;background-color:#f8faf8 !important;
			box-shadow:none !important;color:#173f32 !important;font-family:inherit !important;font-size:12.5px !important;font-weight:700 !important;letter-spacing:0 !important;line-height:1 !important;
		}

		/* Empty producer state uses the same shell, with the destination calmly aligned left. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-empty-inner {
			display:flex !important;justify-content:flex-start !important;
		}

		/* Tablet: same one-row compression as main shop. */
		@media (min-width:641px) and (max-width:991px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting:not(.elmercado-vendor-sorting-normalized),
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
				min-height:64px !important;gap:12px !important;padding:10px 12px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-result-count {
				font-size:11.5px !important;
			}
			html body .mdo-ps-destination__trigger,
			html body .mdo-catalog-destination__trigger {padding:0 11px 0 12px !important;font-size:11.75px !important;}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-ordering {
				flex:0 1 190px !important;width:190px !important;min-width:150px !important;max-width:190px !important;
			}
		}

		/* Phone: exactly the same two-row composition used by the main shop. */
		@media (max-width:640px) {
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting:not(.elmercado-vendor-sorting-normalized),
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
				display:grid !important;grid-template-columns:minmax(0,1fr) minmax(0,1fr) !important;grid-template-rows:auto auto !important;
				align-items:center !important;justify-items:stretch !important;column-gap:8px !important;row-gap:9px !important;
				width:100% !important;min-width:0 !important;max-width:100% !important;height:auto !important;min-height:0 !important;max-height:none !important;
				overflow:visible !important;margin:0 0 12px !important;padding:11px !important;border-radius:15px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-result-count {
				grid-column:1 / -1 !important;grid-row:1 !important;width:100% !important;min-height:18px !important;height:auto !important;max-height:none !important;
				padding:0 2px !important;font-size:11px !important;line-height:1.25 !important;white-space:nowrap !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .mdo-ps-destination {
				grid-column:1 !important;grid-row:2 !important;width:100% !important;min-width:0 !important;max-width:100% !important;
			}
			html body .mdo-ps-destination__trigger,
			html body .mdo-catalog-destination__trigger {
				width:100% !important;min-width:0 !important;max-width:100% !important;height:40px !important;min-height:40px !important;max-height:40px !important;
				padding:0 10px 0 11px !important;font-size:11.5px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-ordering {
				grid-column:2 !important;grid-row:2 !important;justify-self:stretch !important;flex:none !important;box-sizing:border-box !important;
				width:100% !important;min-width:0 !important;max-width:100% !important;height:40px !important;min-height:40px !important;max-height:40px !important;margin:0 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-ordering select {
				height:40px !important;min-height:40px !important;max-height:40px !important;padding-left:10px !important;padding-right:28px !important;font-size:11.25px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-empty-inner {
				display:block !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-empty-inner .mdo-ps-destination,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-empty-inner .mdo-ps-destination__trigger {
				width:100% !important;
			}
	}

		/* Same narrow-phone fallback as main shop: controls stack only when truly necessary. */
		@media (max-width:350px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated {
				grid-template-columns:minmax(0,1fr) !important;grid-template-rows:auto auto auto !important;row-gap:8px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .mdo-ps-destination {
				grid-column:1 !important;grid-row:2 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .elmercado-vendor-toolbar.mdo-ps-toolbar-integrated > .woocommerce-ordering {
				grid-column:1 !important;grid-row:3 !important;
			}
		}
	</style>
	<?php
}

/* Register after the theme has registered its historical wp_head callbacks. */
add_action(
	'wp',
	static function (): void {
		if ( mdo_ps_toolbar_ux_is_surface_20260821() ) {
			add_action( 'wp_head', 'mdo_ps_toolbar_ux_critical_css_20260821', PHP_INT_MAX );
		}
	},
	PHP_INT_MAX
);

/**
 * Move the already-bound destination trigger into WCFM's final white toolbar.
 * We intentionally keep WCFM's direct-child result/order structure and solve
 * parity in CSS, avoiding a DOM tug-of-war with the theme's normalizer.
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
		const store = document.querySelector('#wcfmmp-store') || document;

		const getWrap = () => {
			const nodes = Array.from(document.querySelectorAll('[data-mdo-ps-destination-open]'));
			let trigger = nodes.find((node) => !source || !source.contains(node));
			if (!trigger && source) trigger = source.querySelector('[data-mdo-ps-destination-open]');
			if (!trigger) return null;
			const wrap = trigger.closest('[data-mdo-ps-destination-trigger-wrap]');
			if (!wrap) return null;
			wrap.classList.add('mdo-catalog-destination','mdo-catalog-destination--canonical');
			trigger.classList.add('mdo-catalog-destination__trigger');
			return {wrap,nodes};
		};

		const ensureEmptyToolbar = (wrap) => {
			let host = store.querySelector('.woostify-sorting');
			if (!host) {
				host = document.createElement('div');
				host.className = 'woostify-sorting elmercado-vendor-sorting-normalized mdo-ps-toolbar-host mdo-ps-toolbar-empty';
				host.setAttribute('data-mdo-ps-toolbar-host','');
				const anchor = store.querySelector('.woocommerce-no-products-found,.woocommerce-info,ul.products,.products,.wcfmmp-store-product') || document.querySelector('.woocommerce-no-products-found,.woocommerce-info,main#main,.site-main');
				if (anchor && anchor.parentNode) anchor.parentNode.insertBefore(host, anchor);
				else if (store !== document) store.appendChild(host);
				else document.body.appendChild(host);
			}
			let inner = host.querySelector(':scope > .elmercado-vendor-toolbar');
			if (!inner) {
				inner = document.createElement('div');
				inner.className = 'elmercado-vendor-toolbar mdo-ps-toolbar-integrated mdo-ps-toolbar-empty-inner';
				inner.setAttribute('role','group');
				host.prepend(inner);
			}
			inner.appendChild(wrap);
			return inner;
		};

		const mount = () => {
			const data = getWrap();
			if (!data) return false;
			const {wrap,nodes} = data;
			let inner = store.querySelector('.elmercado-vendor-toolbar');
			const hasProducts = Boolean(store.querySelector('ul.products li.product,.products .product'));
			if (!inner && !hasProducts) inner = ensureEmptyToolbar(wrap);
			if (!inner) return false;

			inner.classList.add('mdo-ps-toolbar-integrated');
			const result = inner.querySelector(':scope > .woocommerce-result-count');
			if (result) result.classList.add('emo-vendor-result-count-010225');
			const ordering = inner.querySelector(':scope > .woocommerce-ordering');
			if (ordering) inner.insertBefore(wrap, ordering);
			else if (wrap.parentElement !== inner) inner.appendChild(wrap);
			wrap.setAttribute('data-mdo-ps-mounted','1');

			nodes.forEach((other) => {
				if (wrap.contains(other)) return;
				const otherWrap = other.closest('[data-mdo-ps-destination-trigger-wrap]');
				if (otherWrap && otherWrap !== wrap && (!source || !source.contains(otherWrap))) otherWrap.remove();
			});
			if (source && !source.contains(wrap)) source.remove();
			return true;
		};

		mount();
		document.addEventListener('DOMContentLoaded', mount, {once:true});
		window.addEventListener('pageshow', mount, {passive:true});
		window.setTimeout(mount, 0);
		window.setTimeout(mount, 250);
		window.setTimeout(mount, 1000);
	})();
	</script>
	<?php
}

/* Register after theme footer normalizers so destination placement is the final DOM operation. */
add_action(
	'wp',
	static function (): void {
		if ( mdo_ps_toolbar_ux_is_store_20260821() ) {
			add_action( 'wp_footer', 'mdo_ps_toolbar_ux_mount_script_20260821', PHP_INT_MAX );
		}
	},
	PHP_INT_MAX
);
