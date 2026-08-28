<?php
/**
 * Plugin Name: MDO Catalogue Control Widths 2026-08-28
 * Description: Presentation-only refinement for destination hierarchy, stable first paint and full-width compact catalogue controls.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_control_widths_is_surface_20260828(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_catalog_top_controls_parity_final_is_surface_20260824' ) ) {
		return mdo_catalog_top_controls_parity_final_is_surface_20260824();
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page();
}

/**
 * Critical first-paint styling for the catalogue controls.
 *
 * The historical catalogue layers finish styling in wp_footer. Keep the final
 * destination appearance and compact mobile geometry in wp_head so the first
 * painted frame already matches the settled state.
 */
function mdo_catalog_control_widths_critical_style_20260828(): void {
	if ( ! mdo_catalog_control_widths_is_surface_20260828() ) {
		return;
	}
	?>
	<style id="mdo-catalog-control-widths-critical-20260828">
		html body [data-mdo-destination-open],
		html body [data-mdo-ps-destination-open] {
			display:grid !important;
			grid-template-columns:13px minmax(0,1fr) 16px !important;
			column-gap:8px !important;
			align-items:center !important;
			background:#f1f6f2 !important;
			background-color:#f1f6f2 !important;
			color:#173f32 !important;
			border:1px solid rgba(23,63,50,.22) !important;
			border-radius:999px !important;
			box-shadow:0 1px 2px rgba(17,42,34,.025) !important;
			text-align:left !important;
			-webkit-appearance:none !important;
			appearance:none !important;
		}
		html body [data-mdo-destination-open]:hover,
		html body [data-mdo-destination-open]:focus-visible,
		html body [data-mdo-ps-destination-open]:hover,
		html body [data-mdo-ps-destination-open]:focus-visible {
			background:#eaf2ed !important;
			background-color:#eaf2ed !important;
			border-color:rgba(23,63,50,.34) !important;
			color:#173f32 !important;
		}

		/* The server-rendered control already contains a pin. Hide it before the
		 * body can paint, then use one CSS location cue consistently everywhere. */
		html body [data-mdo-destination-open] > svg:first-child,
		html body [data-mdo-ps-destination-open] > svg:first-child,
		html body [data-mdo-destination-open] > .mdo-catalog-destination__pin,
		html body [data-mdo-ps-destination-open] > .mdo-catalog-destination__pin,
		html body [data-mdo-ps-destination-open] > .mdo-ps-destination__pin {
			display:none !important;
		}

		html body [data-mdo-destination-open] > span,
		html body [data-mdo-ps-destination-open] > span {
			font-weight:500 !important;
			text-align:left !important;
		}
		html body [data-mdo-destination-open] > span > strong,
		html body [data-mdo-ps-destination-open] > span > strong {
			font-weight:760 !important;
			color:inherit !important;
		}

		/* A quiet location cue makes destination read as catalogue state rather
		 * than as another sorting control. Purely decorative; behaviour unchanged. */
		html body [data-mdo-destination-open]::before,
		html body [data-mdo-ps-destination-open]::before {
			content:"" !important;
			display:block !important;
			box-sizing:border-box !important;
			width:13px !important;
			height:13px !important;
			min-width:13px !important;
			margin:0 !important;
			padding:0 !important;
			border:0 !important;
			background-color:currentColor !important;
			-webkit-mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			mask:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath fill='black' d='M12 22s7-6.15 7-13a7 7 0 1 0-14 0c0 6.85 7 13 7 13Zm0-9.5A3.5 3.5 0 1 1 12 5a3.5 3.5 0 0 1 0 7.5Z'/%3E%3C/svg%3E") center/contain no-repeat !important;
			opacity:.72 !important;
			pointer-events:none !important;
		}

		/* The catalogue already considers widths below 768px mobile in its runtime
		 * guard. Match that breakpoint from first paint so ordering never falls back
		 * to the historical 248px desktop width between 641px and 767px. */
		@media (max-width:767px) {
			html body .emo-catalog-toolbar-shared-010229 {
				display:grid !important;
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:auto 40px 40px !important;
				align-items:stretch !important;
				justify-items:stretch !important;
				gap:8px !important;
				min-height:0 !important;
				padding:11px !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woostify-toolbar-left {
				display:contents !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-result-count {
				grid-column:1 !important;
				grid-row:1 !important;
				width:100% !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
			html body .emo-catalog-toolbar-shared-010229 .mdo-catalog-destination,
			html body .emo-catalog-toolbar-shared-010229 .mdo-ps-destination {
				grid-column:1 !important;
				grid-row:2 !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
			}
			html body .emo-catalog-toolbar-shared-010229 [data-mdo-destination-open],
			html body .emo-catalog-toolbar-shared-010229 [data-mdo-ps-destination-open] {
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				grid-column:1 !important;
				grid-row:3 !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				margin:0 !important;
			}
			html body .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select[name="orderby"] {
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_catalog_control_widths_critical_style_20260828', PHP_INT_MAX );

function mdo_catalog_control_widths_output_20260828(): void {
	if ( ! mdo_catalog_control_widths_is_surface_20260828() ) {
		return;
	}
	?>
	<script id="mdo-catalog-control-widths-20260828">
	(() => {
		'use strict';

		let applying = false;
		let observer = null;
		let observedToolbar = null;
		let queued = false;

		const set = (el, props) => {
			if (!el) return;
			Object.entries(props).forEach(([name, value]) => {
				const wanted = String(value);
				if (el.style.getPropertyValue(name) === wanted && el.style.getPropertyPriority(name) === 'important') return;
				el.style.setProperty(name, wanted, 'important');
			});
		};
		const directFlexItem = (root, node) => {
			if (!root || !node || !root.contains(node)) return null;
			let item = node;
			while (item && item.parentElement && item.parentElement !== root) item = item.parentElement;
			return item && item.parentElement === root ? item : null;
		};

		const apply = () => {
			if (applying) return false;
			applying = true;
			try {
				const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
				if (!toolbar) return false;

				const left = toolbar.querySelector('.woostify-toolbar-left');
				const count = toolbar.querySelector('.woocommerce-result-count');
				const destTrigger = toolbar.querySelector('[data-mdo-destination-open],[data-mdo-ps-destination-open]');
				const destWrap = destTrigger?.closest('.mdo-catalog-destination--canonical,.mdo-catalog-destination,.mdo-ps-destination') || destTrigger?.parentElement || null;
				const destItem = directFlexItem(left, destTrigger);
				const countItem = directFlexItem(left, count) || count;
				const orderForm = toolbar.querySelector('.woocommerce-ordering');
				const order = orderForm?.querySelector('select[name="orderby"]') || null;
				if (!left || !count || !destTrigger || !destWrap || !destItem || !orderForm || !order) return false;

				/* Keep the historical 640px query as the phone branch, while extending
				 * the same stacked contract through the existing runtime guard's 767px. */
				const narrowPhone = window.matchMedia('(max-width:640px)').matches;
				const compactMobile = window.matchMedia('(min-width:641px) and (max-width:767px)').matches;
				const mobile = narrowPhone || compactMobile;
				const desktop = window.matchMedia('(min-width:901px)').matches;
				const text = destTrigger.querySelector(':scope > span') || destTrigger.querySelector('span');
				const strong = text?.querySelector('strong') || null;
				const directSvgs = [...destTrigger.querySelectorAll(':scope > svg')];
				const legacyPin = directSvgs[0] || destTrigger.querySelector('.mdo-catalog-destination__pin,.mdo-ps-destination__pin');
				const chevron = destTrigger.querySelector('.mdo-catalog-destination__chevron,.mdo-ps-destination__chevron') || directSvgs[directSvgs.length - 1] || null;

				set(destTrigger, {
					'display':'grid',
					'grid-template-columns':'13px minmax(0,1fr) 16px',
					'column-gap':'8px',
					'align-items':'center',
					'text-align':'left',
					'background':'#f1f6f2',
					'background-color':'#f1f6f2',
					'border-color':'rgba(23,63,50,.22)',
					'box-shadow':'0 1px 2px rgba(17,42,34,.025)'
				});
				set(legacyPin, {'display':'none'});
				set(text, {
					'display':'block',
					'min-width':'0',
					'overflow':'hidden',
					'text-overflow':'ellipsis',
					'white-space':'nowrap',
					'line-height':'1.2',
					'text-align':'left',
					'font-weight':'500'
				});
				set(strong, {'color':'inherit','font-weight':'760'});
				set(chevron, {
					'display':'block',
					'position':'static',
					'top':'auto',
					'right':'auto',
					'bottom':'auto',
					'left':'auto',
					'align-self':'center',
					'justify-self':'center',
					'width':'12px',
					'height':'8px',
					'min-width':'12px',
					'max-width':'12px',
					'margin':'0',
					'padding':'0',
					'opacity':'.72',
					'transform':'none',
					'pointer-events':'none'
				});

				if (mobile) {
					set(toolbar, {
						'display':'grid',
						'grid-template-columns':'minmax(0,1fr)',
						'grid-template-rows':'auto 40px 40px',
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'auto',
						'min-height':'0',
						'max-height':'none',
						'align-items':'stretch',
						'justify-content':'space-between',
						'justify-items':'stretch',
						'gap':'8px',
						'overflow':'visible',
						'margin':'0 0 12px',
						'padding':'11px'
					});
					set(left, {
						'display':'contents',
						'visibility':'visible',
						'opacity':'1'
					});
					set(countItem, {'order':'initial'});
					set(count, {
						'grid-column':'1',
						'grid-row':'1',
						'width':'100%',
						'min-width':'0',
						'height':'18px',
						'min-height':'18px',
						'max-height':'18px',
						'margin':'0'
					});
					set(destItem, {
						'order':'initial',
						'grid-column':'1',
						'grid-row':'2',
						'flex':'0 0 100%',
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'40px',
						'min-height':'40px',
						'max-height':'40px',
						'margin':'0'
					});
					set(destWrap, {
						'grid-column':'1',
						'grid-row':'2',
						'flex':'0 0 100%',
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'40px',
						'min-height':'40px',
						'max-height':'40px',
						'margin':'0',
						'justify-self':'stretch',
						'align-self':'stretch'
					});
					set(destTrigger, {
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'40px',
						'min-height':'40px',
						'max-height':'40px',
						'justify-self':'stretch'
					});
					set(orderForm, {
						'display':'flex',
						'grid-column':'1',
						'grid-row':'3',
						'flex':'0 0 100%',
						'position':'relative',
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'40px',
						'min-height':'40px',
						'max-height':'40px',
						'margin':'0',
						'left':'0',
						'transform':'none',
						'justify-self':'stretch',
						'align-self':'stretch'
					});
					set(order, {
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'40px',
						'min-height':'40px',
						'max-height':'40px'
					});
				} else {
					set(toolbar, {
						'display':'flex',
						'grid-template-columns':'none',
						'grid-template-rows':'none',
						'width':'100%',
						'min-width':'0',
						'max-width':'100%',
						'height':'auto',
						'min-height':'68px',
						'max-height':'none',
						'align-items':'center',
						'justify-content':'space-between',
						'justify-items':'stretch',
						'gap':'18px',
						'overflow':'visible',
						'margin':'0 0 16px',
						'padding':'12px 14px'
					});
					set(left, {
						'display':'flex',
						'visibility':'visible',
						'opacity':'1',
						'position':'static',
						'flex':'1 1 auto',
						'width':'auto',
						'min-width':'0',
						'max-width':'none',
						'height':'42px',
						'min-height':'42px',
						'max-height':'42px',
						'align-items':'center',
						'gap':'15px',
						'margin':'0',
						'padding':'0',
						'transform':'none'
					});
					set(countItem, {'order':'-1'});
					set(count, {
						'grid-column':'auto',
						'grid-row':'auto',
						'width':'auto',
						'height':'42px',
						'min-height':'42px',
						'max-height':'42px',
						'margin':'0'
					});
					set(destItem, {'order':'0','grid-column':'auto','grid-row':'auto','height':'42px','min-height':'42px','max-height':'42px','margin':'0'});
					if (desktop) {
						set(destItem, {'flex':'0 1 auto','width':'auto','min-width':'0','max-width':'320px'});
						set(destWrap, {
							'flex':'0 1 auto','width':'auto','min-width':'0','max-width':'320px','height':'42px','min-height':'42px','max-height':'42px','margin':'0','justify-self':'start','align-self':'center'
						});
						set(destTrigger, {'width':'auto','min-width':'0','max-width':'320px','height':'42px','min-height':'42px','max-height':'42px','justify-self':'start'});
					} else {
						set(destItem, {'flex':'0 0 248px','width':'248px','min-width':'248px','max-width':'248px'});
						set(destWrap, {
							'flex':'0 0 248px','width':'248px','min-width':'248px','max-width':'248px','height':'42px','min-height':'42px','max-height':'42px','margin':'0','justify-self':'start','align-self':'center'
						});
						set(destTrigger, {'width':'100%','min-width':'0','max-width':'100%','height':'42px','min-height':'42px','max-height':'42px','justify-self':'stretch'});
					}
					set(orderForm, {
						'display':'flex',
						'grid-column':'auto',
						'grid-row':'auto',
						'position':'relative',
						'flex':'0 0 248px',
						'width':'248px',
						'min-width':'248px',
						'max-width':'248px',
						'height':'42px',
						'min-height':'42px',
						'max-height':'42px',
						'margin':'0 0 0 auto',
						'left':'0',
						'transform':'none'
					});
					set(order, {'width':'100%','min-width':'0','max-width':'100%','height':'42px','min-height':'42px','max-height':'42px'});
				}

				toolbar.dataset.mdoCatalogControlWidths = '20260828-v4';
				return true;
			} finally {
				applying = false;
			}
		};

		const runSoon = () => {
			if (queued) return;
			queued = true;
			queueMicrotask(() => {
				queued = false;
				apply();
			});
		};
		const watch = () => {
			const toolbar = document.querySelector('.emo-catalog-toolbar-shared-010229');
			if (!toolbar) return;
			if (toolbar === observedToolbar && observer) return;
			observer?.disconnect();
			observedToolbar = toolbar;
			observer = new MutationObserver(runSoon);
			observer.observe(toolbar, {
				attributes:true,
				attributeFilter:['style','class'],
				childList:true,
				subtree:true
			});
		};

		/* This script is deliberately registered after the historical parity and
		 * arrow owners. Apply synchronously, then correct their delayed inline
		 * mutations in the same microtask checkpoint before the browser paints. */
		apply();
		watch();
		requestAnimationFrame(() => { apply(); watch(); });
		window.addEventListener('load', () => { apply(); watch(); }, {passive:true});
		window.addEventListener('pageshow', () => { apply(); watch(); }, {passive:true});
		window.addEventListener('resize', () => { apply(); watch(); }, {passive:true});
		window.addEventListener('orientationchange', () => { apply(); watch(); }, {passive:true});
	})();
	</script>
	<?php
}

/* Parity and arrow owners register their final output dynamically at this same
 * late stage. Register ours afterwards so its synchronous first pass is the
 * final inline-important owner instead of relying on visible setTimeout fixes. */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_catalog_control_widths_is_surface_20260828() ) {
			return;
		}
		add_action( 'wp_footer', 'mdo_catalog_control_widths_output_20260828', PHP_INT_MAX );
	},
	PHP_INT_MAX - 1
);
