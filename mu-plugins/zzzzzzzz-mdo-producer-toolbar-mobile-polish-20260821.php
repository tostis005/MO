<?php
/**
 * Plugin Name: MDO Producer Toolbar Mobile Polish
 * Description: Mobile-only deterministic polish for producer shipping and ordering controls.
 * Version: 1.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_toolbar_mobile_polish_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_producer_toolbar_static_guard_is_store_20260821' ) ) {
		return mdo_producer_toolbar_static_guard_is_store_20260821();
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) ) {
		return mdo_ps_toolbar_ux_is_store_20260821();
	}
	return function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page();
}

function mdo_producer_toolbar_mobile_polish_css_20260821(): void {
	if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
		return;
	}
	?>
	<style class="mdo-producer-toolbar-mobile-polish-20260821">
		@media (max-width:640px) {
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized {
				position:relative !important;
				isolation:isolate !important;
				overflow:visible !important;
			}

			/* Shipping: a single clean line inside the 40px control. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination--canonical,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination {
				position:relative !important;
				z-index:1 !important;
				overflow:visible !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger {
				position:relative !important;
				z-index:1 !important;
				display:grid !important;
				grid-template-columns:minmax(0,1fr) 16px !important;
				column-gap:9px !important;
				align-items:center !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 13px !important;
				overflow:hidden !important;
				line-height:1 !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:first-child,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:first-child {
				display:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
				position:static !important;
				dis:flex !important;
				display:flex !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				height:38px !important;
				min-height:38px !important;
				max-height:38px !important;
				align-items:center !important;
				margin:0 !important;
				padding:0 !important;
				overflow:hidden !important;
				white-space:nowrap !important;
				text-overflow:ellipsis !important;
				line-height:1.2 !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:last-child,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:last-child {
				pointer-events:none !important;
			}

			/* Ordering: no wrapper border; only the native select is visible and interactive. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering {
				position:relative !important;
				z-index:200 !important;
				display:block !important;
				box-sizing:border-box !important;
				border:0 !important;
				border-radius:0 !important;
				outline:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				overflow:visible !important;
				pointer-events:auto !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering::before,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering::after {
				content:none !important;
				display:none !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering span {
				display:none !important;
				visibility:hidden !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering select {
				position:relative !important;
				z-index:201 !important;
				display:block !important;
				visibility:visible !important;
				opacity:1 !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				padding:0 36px 0 14px !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				outline:0 !important;
				background:#fff !important;
				box-shadow:none !important;
				pointer-events:auto !important;
				cursor:pointer !important;
				touch-action:manipulation !important;
				-webkit-appearance:menulist !important;
				appearance:auto !important;
			}
		}
	</style>
	<?php
}

add_action( 'wp_head', 'mdo_producer_toolbar_mobile_polish_css_20260821', PHP_INT_MAX );
add_action( 'wp_footer', 'mdo_producer_toolbar_mobile_polish_css_20260821', PHP_INT_MAX );

add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
			return;
		}
		?>
		<script id="mdo-producer-toolbar-mobile-polish-js-20260821">
		(() => {
			'use strict';
			if (!matchMedia('(max-width:640px)').matches) return;
			const apply = () => {
				const store = document.querySelector('#wcfmmp-store');
				if (!store) return;
				const trigger = store.querySelector('.mdo-catalog-destination__trigger, .mdo-ps-destination__trigger');
				const label = trigger?.querySelector(':scope > span');
				if (label) {
					const clean = label.textContent.replace(/\s+/g, ' ').trim();
					if (label.textContent !== clean || label.children.length) label.textContent = clean;
					label.style.setProperty('display','flex','important');
					label.style.setProperty('height','38px','important');
					label.style.setProperty('min-height','38px','important');
					label.style.setProperty('max-height','38px','important');
					label.style.setProperty('align-items','center','important');
					label.style.setProperty('line-height','1.2','important');
					label.style.setProperty('white-space','nowrap','important');
					label.style.setProperty('overflow','hidden','important');
					label.style.setProperty('pointer-events','none','important');
				}
				const firstSvg = trigger?.querySelector(':scope > svg:first-child');
				if (firstSvg) firstSvg.style.setProperty('display','none','important');
				const form = store.querySelector('.woocommerce-ordering');
				const select = form?.querySelector('select');
				if (form) {
					form.style.setProperty('border','0','important');
					form.style.setProperty('border-radius','0','important');
					form.style.setProperty('background','transparent','important');
					form.style.setProperty('box-shadow','none','important');
					form.style.setProperty('pointer-events','auto','important');
					form.style.setProperty('z-index','200','important');
					form.querySelectorAll('span').forEach(n => {
						n.style.setProperty('display','none','important');
						n.style.setProperty('pointer-events','none','important');
					});
				}
				if (select) {
					select.style.setProperty('pointer-events','auto','important');
					select.style.setProperty('z-index','201','important');
					select.style.setProperty('appearance','auto','important');
					select.style.setProperty('-webkit-appearance','menulist','important');
				}
			};
			apply();
			requestAnimationFrame(apply);
			setTimeout(apply,250);
			setTimeout(apply,900);
			setTimeout(apply,1800);
			const store = document.querySelector('#wcfmmp-store');
			if (store) {
				let timer = 0;
				new MutationObserver(() => {
					clearTimeout(timer);
					timer = setTimeout(apply, 20);
				}).observe(store, {subtree:true, childList:true});
			}
			window.addEventListener('pageshow', apply, {passive:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
