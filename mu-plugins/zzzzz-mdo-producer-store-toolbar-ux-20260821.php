<?php
/**
 * Plugin Name: MDO Producer Store Toolbar UX
 * Description: Makes the producer-store catalogue toolbar use the same DOM, geometry and first-paint colours as the main shop toolbar.
 * Version: 1.3.0
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
 * The main catalogue already removes this legacy footer geometry script. Do the
 * same on producer stores; its PHP labels and wp_head CSS remain untouched.
 */
add_action(
	'wp',
	static function (): void {
		if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
			return;
		}
		global $wp_filter;
		if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
			return;
		}
		$target = wp_normalize_path( get_stylesheet_directory() . '/inc/catalog-mobile-controls-parity-010236.php' );
		foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback_data ) {
				$callback = $callback_data['function'] ?? null;
				if ( ! $callback instanceof Closure ) {
					continue;
				}
				try {
					$reflection = new ReflectionFunction( $callback );
					$filename   = $reflection->getFileName();
				} catch ( Throwable $throwable ) {
					continue;
				}
				if ( is_string( $filename ) && wp_normalize_path( $filename ) === $target ) {
					remove_action( 'wp_footer', $callback, (int) $priority );
				}
			}
		}
	},
	1300
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
			<span class="mdo-catalog-destination__chevron" aria-hidden="true"><svg viewBox="0 0 16 10" width="12" height="8" focusable="false"><path d="M2 2.2 8 8l6-5.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
		</button>
	</div>
	<?php
}

/** Same server-side shell when a selected destination leaves zero products. */
function mdo_ps_toolbar_ux_empty_toolbar_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<div class="woostify-sorting emo-catalog-toolbar-shared-010229 elmercado-vendor-sorting-normalized mdo-ps-toolbar-host mdo-ps-toolbar-empty mdo-ps-toolbar-ready" data-mdo-ps-toolbar-empty-server data-mdo-ps-toolbar-host>
		<div class="woostify-toolbar-left elmercado-vendor-filter-hidden">
			<?php mdo_ps_toolbar_ux_render_trigger_20260821( 'data-mdo-ps-empty-trigger' ); ?>
		</div>
	</div>
	<?php
}
add_action( 'woocommerce_no_products_found', 'mdo_ps_toolbar_ux_empty_toolbar_20260821', 1 );

/** Guaranteed trigger source for templates that skip before_shop_loop. */
function mdo_ps_toolbar_ux_source_trigger_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<div data-mdo-ps-toolbar-ux-source hidden aria-hidden="true"><?php mdo_ps_toolbar_ux_render_trigger_20260821( 'data-mdo-ps-source-trigger' ); ?></div>
	<?php
}
add_action( 'wp_footer', 'mdo_ps_toolbar_ux_source_trigger_20260821', 1 );

/**
 * Critical CSS is emitted before body paint. Main-shop values are repeated here
 * only to prevent its old green/transient paint; producer selectors use the same
 * final values with extra specificity so historical WCFM rules cannot diverge.
 */
function mdo_ps_toolbar_ux_critical_css_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_surface_20260821() ) {
		return;
	}
	?>
	<style id="mdo-toolbar-critical-first-paint-20260821" data-mdo-toolbar-critical-first-paint="1">
		/* White final surface from first paint, in both catalogues. */
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229,
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized {
			display:flex !important; box-sizing:border-box !important; position:relative !important; inset:auto !important;
			width:100% !important; min-width:0 !important; max-width:100% !important; height:auto !important; min-height:68px !important; max-height:none !important;
			align-items:center !important; justify-content:space-between !important; gap:18px !important; overflow:visible !important;
			margin:0 0 16px !important; padding:12px 14px !important; border:1px solid rgba(23,63,50,.11) !important; border-radius:16px !important;
			background:#fff !important; background-color:#fff !important; box-shadow:0 10px 28px rgba(17,42,34,.055) !important;
			color:#173f32 !important; transform:none !important; transition:none !important; animation:none !important; float:none !important; clear:both !important;
		}

		/* WCFM's historical inner grid is structural only. JS removes it after normalisation. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .elmercado-vendor-toolbar {
			display:contents !important; margin:0 !important; padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important;
		}

		/* Exact main-shop left group. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woostify-toolbar-left,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
			display:flex !important; visibility:visible !important; opacity:1 !important; position:static !important; box-sizing:border-box !important;
			flex:1 1 auto !important; width:auto !important; min-width:0 !important; max-width:none !important; height:42px !important; min-height:42px !important; max-height:42px !important;
			align-items:center !important; gap:15px !important; overflow:visible !important; margin:0 !important; padding:0 !important; float:none !important; clear:none !important;
		}
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-result-count,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220.woocommerce-result-count {
			display:flex !important; visibility:visible !important; opacity:1 !important; position:static !important; box-sizing:border-box !important;
			flex:0 0 auto !important; width:auto !important; min-width:0 !important; max-width:none !important; height:42px !important; min-height:42px !important; max-height:42px !important;
			align-items:center !important; overflow:visible !important; margin:0 !important; padding:0 2px !important; float:none !important; clear:none !important;
			color:#53665f !important; font-family:inherit !important; font-size:12.5px !important; font-weight:700 !important; line-height:1.25 !important; white-space:nowrap !important;
		}

		/* Destination: identical pale pill, never the historical green button. */
		html body .mdo-ps-destination,
		html body .mdo-catalog-destination--canonical {
			display:inline-flex !important; visibility:visible !important; opacity:1 !important; position:static !important; box-sizing:border-box !important;
			flex:0 1 auto !important; width:auto !important; min-width:0 !important; max-width:100% !important; height:42px !important; min-height:42px !important; max-height:42px !important;
			align-items:center !important; margin:0 !important; padding:0 !important; float:none !important; transform:none !important;
		}
		html body .mdo-ps-destination__trigger,
		html body .mdo-catalog-destination__trigger {
			display:grid !important; grid-template-columns:minmax(0,1fr) 16px !important; column-gap:9px !important; box-sizing:border-box !important;
			width:auto !important; min-width:0 !important; max-width:100% !important; height:42px !important; min-height:42px !important; max-height:42px !important;
			align-items:center !important; margin:0 !important; padding:0 13px 0 14px !important; border:1px solid rgba(23,63,50,.15) !important; border-radius:999px !important;
			background:#f8faf8 !important; background-color:#f8faf8 !important; box-shadow:none !important; color:#173f32 !important;
			font-family:inherit !important; font-size:12.5px !important; font-weight:600 !important; line-height:1 !important; white-space:nowrap !important; cursor:pointer !important;
		}
		html body .mdo-catalog-destination__label {display:block !important; min-width:0 !important; overflow:hidden !important; text-overflow:ellipsis !important; white-space:nowrap !important; line-height:1.2 !important;}
		html body .mdo-catalog-destination__trigger strong {color:inherit !important; font-weight:760 !important;}
		html body .mdo-catalog-destination__chevron {display:grid !important; place-items:center !important; width:16px !important; height:16px !important; min-width:16px !important; margin:0 !important; padding:0 !important; line-height:0 !important; opacity:.72 !important;}
		html body .mdo-catalog-destination__chevron svg {display:block !important; width:12px !important; height:8px !important; margin:0 !important;}

		/* Ordering: direct child of the white surface, exactly like the shop. */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering {
			display:flex !important; position:static !important; box-sizing:border-box !important; flex:0 1 248px !important;
			width:clamp(190px,22vw,248px) !important; min-width:170px !important; max-width:248px !important; height:42px !important; min-height:42px !important; max-height:42px !important;
			align-items:center !important; margin:0 0 0 auto !important; padding:0 !important; float:none !important; clear:none !important; transform:none !important;
		}
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering select,
		html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select {
			display:block !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
			height:42px !important; min-height:42px !important; max-height:42px !important; margin:0 !important; padding:0 36px 0 13px !important;
			border:1px solid rgba(23,63,50,.15) !important; border-radius:999px !important; background-color:#f8faf8 !important;
			background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1.5 6 6.5 11 1.5' fill='none' stroke='%23173f32' stroke-width='1.5' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
			background-repeat:no-repeat !important; background-position:right 13px center !important; background-size:12px 8px !important;
			box-shadow:none !important; color:#173f32 !important; font-family:inherit !important; font-size:12.5px !important; font-weight:700 !important; line-height:1 !important;
			-webkit-appearance:none !important; appearance:none !important;
		}

		/* Tablet: same 68px single-row composition measured on the main shop. */
		@media (min-width:641px) and (max-width:991px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized {
				left:50% !important; width:calc(100vw - 45px) !important; min-width:calc(100vw - 45px) !important; max-width:calc(100vw - 45px) !important; transform:translateX(-50%) !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering {
				flex:0 1 190px !important; width:190px !important; min-width:150px !important; max-width:190px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-result-count {font-size:11.5px !important;}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger {font-size:11.75px !important; padding:0 11px 0 12px !important;}
		}

		/* Phone: exact measured main-shop composition: 140px card, count + destination, then full-width ordering. */
		@media (max-width:640px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				display:grid !important; grid-template-columns:minmax(0,1fr) !important; grid-template-rows:auto auto !important;
				align-items:stretch !important; justify-items:stretch !important; gap:10px !important; box-sizing:border-box !important;
				height:auto !important; min-height:0 !important; max-height:none !important; overflow:visible !important; margin:0 0 12px !important; padding:12px !important; border-radius:15px !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized {
				left:50% !important; width:calc(100vw - 32px) !important; min-width:calc(100vw - 32px) !important; max-width:calc(100vw - 32px) !important; transform:translateX(-50%) !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woostify-toolbar-left,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left {
				display:grid !important; grid-column:1 !important; grid-row:1 !important; grid-template-columns:minmax(0,1fr) !important; grid-template-rows:auto 40px !important;
				align-items:stretch !important; gap:7px !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
				height:auto !important; min-height:0 !important; max-height:none !important; overflow:visible !important; margin:0 !important; padding:0 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-result-count,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .emo-catalog-result-count-010220.woocommerce-result-count {
				grid-column:1 !important; grid-row:1 !important; width:100% !important; height:17px !important; min-height:17px !important; max-height:17px !important;
				margin:0 !important; padding:0 2px !important; font-size:11px !important; line-height:17px !important;
			}
			html body .mdo-catalog-destination--canonical {grid-column:1 !important; grid-row:2 !important; display:block !important; width:100% !important; min-width:0 !important; max-width:100% !important; height:40px !important; min-height:40px !important; max-height:40px !important;}
			html body .mdo-catalog-destination__trigger {width:100% !important; min-width:0 !important; max-width:100% !important; height:40px !important; min-height:40px !important; max-height:40px !important; padding:0 12px 0 13px !important; font-size:11.75px !important;}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering {
				grid-column:1 !important; grid-row:2 !important; display:flex !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
				height:40px !important; min-height:40px !important; max-height:40px !important; margin:0 !important; padding:0 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .woocommerce-ordering select,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select {
				width:100% !important; min-width:100% !important; max-width:100% !important; height:40px !important; min-height:40px !important; max-height:40px !important;
				padding:0 36px 0 12px !important; font-size:11.75px !important; background-position:right 12px center !important;
			}
		}
	</style>
	<?php
}

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
 * Convert the WCFM toolbar to the exact main-shop DOM:
 *   .woostify-sorting
 *     > .woostify-toolbar-left > result-count + destination
 *     > .woocommerce-ordering
 */
function mdo_ps_toolbar_ux_mount_script_20260821(): void {
	if ( ! mdo_ps_toolbar_ux_is_store_20260821() ) {
		return;
	}
	?>
	<script id="mdo-ps-toolbar-ux-js-20260821">
	(() => {
		'use strict';
		const store=document.querySelector('#wcfmmp-store');
		if(!store) return;
		const source=document.querySelector('[data-mdo-ps-toolbar-ux-source]');

		const pickTrigger=()=>{
			const all=Array.from(document.querySelectorAll('[data-mdo-ps-destination-open]'));
			let trigger=all.find(n=>!source||!source.contains(n));
			if(!trigger&&source) trigger=source.querySelector('[data-mdo-ps-destination-open]');
			const wrap=trigger?.closest('[data-mdo-ps-destination-trigger-wrap]')||null;
			return {all,trigger,wrap};
		};

		const createEmptyHost=()=>{
			const host=document.createElement('div');
			host.className='woostify-sorting emo-catalog-toolbar-shared-010229 elmercado-vendor-sorting-normalized mdo-ps-toolbar-host mdo-ps-toolbar-empty';
			host.setAttribute('data-mdo-ps-toolbar-host','');
			const anchor=store.querySelector('.woocommerce-no-products-found,.woocommerce-info,ul.products,.products,.wcfmmp-store-product');
			if(anchor?.parentNode) anchor.parentNode.insertBefore(host,anchor); else store.appendChild(host);
			return host;
		};

		const normalise=()=>{
			const {all,trigger,wrap}=pickTrigger();
			if(!wrap||!trigger) return false;
			let host=store.querySelector('.woostify-sorting.elmercado-vendor-sorting-normalized')||store.querySelector('.woostify-sorting');
			if(!host) host=createEmptyHost();
			host.classList.add('emo-catalog-toolbar-shared-010229','elmercado-vendor-sorting-normalized','mdo-ps-toolbar-host');
			host.setAttribute('data-mdo-ps-toolbar-host','');

			const inner=host.querySelector(':scope > .elmercado-vendor-toolbar');
			const result=host.querySelector('.woocommerce-result-count');
			const ordering=host.querySelector('.woocommerce-ordering');
			if(result) result.classList.add('emo-catalog-result-count-010220','emo-vendor-result-count-010225');
			wrap.classList.add('mdo-catalog-destination','mdo-catalog-destination--canonical');
			trigger.classList.add('mdo-catalog-destination__trigger');

			let left=Array.from(host.children).find(node=>node.classList?.contains('woostify-toolbar-left'))||null;
			if(!left){left=document.createElement('div');left.className='woostify-toolbar-left';host.prepend(left);}
			left.classList.remove('elmercado-vendor-filter-hidden');
			if(result&&result.parentElement!==left) left.appendChild(result);
			if(wrap.parentElement!==left) left.appendChild(wrap);
			if(ordering&&ordering.parentElement!==host) host.appendChild(ordering);

			if(inner&&inner!==left){
				const meaningful=Array.from(inner.children).filter(node=>node!==result&&node!==ordering&&node!==wrap&&getComputedStyle(node).display!=='none');
				if(!meaningful.length) inner.remove(); else inner.classList.add('mdo-ps-toolbar-unused-inner');
			}
			all.forEach(other=>{if(other===trigger)return;const otherWrap=other.closest('[data-mdo-ps-destination-trigger-wrap]');if(otherWrap&&otherWrap!==wrap&&(!source||!source.contains(otherWrap)))otherWrap.remove();});
			if(source&&!source.contains(wrap)) source.remove();
			host.classList.add('mdo-ps-toolbar-ready');
			return true;
		};

		normalise();
		document.addEventListener('DOMContentLoaded',normalise,{once:true});
		window.addEventListener('pageshow',normalise,{passive:true});
		requestAnimationFrame(normalise);
		setTimeout(normalise,250);
		setTimeout(normalise,900);
	})();
	</script>
	<?php
}

add_action(
	'wp',
	static function (): void {
		if ( mdo_ps_toolbar_ux_is_store_20260821() ) {
			add_action( 'wp_footer', 'mdo_ps_toolbar_ux_mount_script_20260821', PHP_INT_MAX );
		}
	},
	PHP_INT_MAX
);
