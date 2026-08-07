<?php
/**
 * Armonía visual final: navegación móvil, catálogo y superficies editoriales.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_page( 'productores' ) ) {
			$classes[] = 'elmercado-producers-page';
		}
		return $classes;
	}
);

add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( ! is_shop() ) {
			return;
		}
		?>
		<div class="emo-shop-lead emo-shop-lead--final">
			<p><?php esc_html_e( 'Descubre aceites, ibéricos, fruta y otros productos con origen, seleccionados por su calidad y elaborados por productores que conocemos.', 'elmercadodeorigen' ); ?></p>
		</div>
		<?php
	},
	2
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-sitewide-visual-harmony-final">
			body.woocommerce-shop .emo-shop-lead:not(.emo-shop-lead--final) { display:none !important; }
			body.woocommerce-shop .emo-shop-lead--final {
				width:100% !important; max-width:none !important; margin:0 0 18px !important;
				padding:0 !important; border:0 !important; text-align:left !important;
			}
			body.woocommerce-shop .emo-shop-lead--final p {
				width:100% !important; max-width:none !important; margin:0 !important; padding:0 !important;
				color:#4b5d55 !important; font-size:clamp(14px,1.2vw,16px) !important;
				line-height:1.6 !important; text-align:left !important;
			}

			body.elmercado-editorial-content #content > .woostify-container,
			body.blog #content > .woostify-container { width:100% !important; max-width:none !important; padding-inline:0 !important; }
			body.elmercado-editorial-content #primary.emo-journal,
			body.blog #primary { float:none !important; width:100% !important; max-width:none !important; margin:0 !important; }
			body.blog #secondary,
			body.blog .site-content > .woostify-container > .widget-area { display:none !important; }
			body.elmercado-editorial-content .emo-journal-hero {
				margin:0 !important; padding:clamp(18px,3vw,38px) clamp(16px,3vw,40px) 0 !important; background:transparent !important;
			}
			body.elmercado-editorial-content .emo-journal-hero__inner {
				box-sizing:border-box !important; width:100% !important; max-width:1440px !important; margin-inline:auto !important;
				padding:clamp(28px,5vw,64px) !important; border-radius:28px !important;
				background:#173f32 !important; color:#fff !important; overflow:hidden !important;
			}
			body.elmercado-editorial-content .emo-journal-hero__inner :is(h1,p,.emo-kicker) { color:#fff !important; }
			body.elmercado-editorial-content .emo-journal-listing {
				padding:clamp(18px,3vw,36px) clamp(16px,3vw,40px) clamp(42px,6vw,80px) !important;
			}
			body.elmercado-editorial-content .emo-journal-listing > .emo-shell {
				box-sizing:border-box !important; width:100% !important; max-width:1440px !important; margin-inline:auto !important; padding:0 !important;
			}
			body.elmercado-editorial-content .emo-journal-grid { width:100% !important; max-width:none !important; }
			body.elmercado-editorial-content .emo-article-card {
				border:1px solid rgba(23,63,50,.11) !important; border-radius:22px !important;
				background:#fffdf8 !important; box-shadow:0 12px 34px rgba(23,63,50,.06) !important; overflow:hidden !important;
			}
			body.elmercado-editorial-content .emo-article-card__media { overflow:hidden !important; border-radius:0 !important; }

			body.elmercado-producers-page #content > .woostify-container,
			body.wcfm-store-list-page #content > .woostify-container { width:100% !important; max-width:1440px !important; }
			body.elmercado-producers-page .emo-producers-intro,
			body.wcfm-store-list-page .emo-producers-intro {
				display:grid !important; grid-template-columns:1fr !important; gap:10px !important;
				width:100% !important; max-width:none !important; margin:0 0 clamp(20px,3vw,34px) !important;
				padding:clamp(26px,4vw,50px) !important; border:0 !important; border-radius:26px !important;
				background:#173f32 !important; color:#fff !important; box-shadow:none !important;
			}
			body.elmercado-producers-page .emo-producers-intro :is(h2,p,.emo-kicker),
			body.wcfm-store-list-page .emo-producers-intro :is(h2,p,.emo-kicker) { max-width:900px !important; margin-top:0 !important; color:#fff !important; }
			body.elmercado-producers-page #wcfmmp-stores-wrap,
			body.wcfm-store-list-page #wcfmmp-stores-wrap { width:100% !important; max-width:none !important; }
			body.elmercado-producers-page #wcfmmp-stores-wrap :is(.wcfmmp-store-lists-sorting,.wcfmmp-store-list-sorting,.wcfmmp-store-list-sorting-wrapper,.wcfmmp-store-sorting,.emo-producers-sorting-hidden),
			body.wcfm-store-list-page #wcfmmp-stores-wrap :is(.wcfmmp-store-lists-sorting,.wcfmmp-store-list-sorting,.wcfmmp-store-list-sorting-wrapper,.wcfmmp-store-sorting,.emo-producers-sorting-hidden) {
				display:none !important; visibility:hidden !important;
			}
			body.elmercado-producers-page #wcfmmp-stores-wrap :is(.wcfmmp-store-search-form,.wcfmmp-store-search-form-wrapper,.wcfmmp-store-lists-search),
			body.wcfm-store-list-page #wcfmmp-stores-wrap :is(.wcfmmp-store-search-form,.wcfmmp-store-search-form-wrapper,.wcfmmp-store-lists-search) {
				border:1px solid rgba(23,63,50,.11) !important; border-radius:20px !important; background:#f7f4ec !important; box-shadow:none !important;
			}
			body.elmercado-producers-page #wcfmmp-stores-wrap ul.wcfmmp-store-wrap > li,
			body.wcfm-store-list-page #wcfmmp-stores-wrap ul.wcfmmp-store-wrap > li { border-radius:22px !important; overflow:hidden !important; }

			@media (max-width:991px) {
				html body.elmercado-child-theme .site-header-inner > .woostify-container {
					grid-template-columns:28px minmax(0,1fr) 100px !important; column-gap:4px !important;
				}
				html body.elmercado-child-theme .site-header .site-tools {
					display:grid !important; grid-template-columns:repeat(3,32px) !important; grid-auto-columns:32px !important;
					grid-auto-flow:column !important; gap:2px !important; width:100px !important; min-width:100px !important; height:40px !important;
					align-items:center !important; justify-items:center !important; justify-content:end !important; overflow:visible !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > * {
					display:grid !important; width:32px !important; min-width:32px !important; max-width:32px !important;
					height:40px !important; min-height:40px !important; margin:0 !important; padding:0 !important;
					place-items:center !important; align-self:center !important; justify-self:center !important;
					background:transparent !important; box-shadow:none !important; transform:none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools > *:hover { background:transparent !important; box-shadow:none !important; }
				html body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account-icon,.my-account > a,a.shopping-cart,a.shopping-bag-button,.shopping-cart > a,.shopping-bag-button > a,.cart-contents) {
					display:grid !important; width:30px !important; min-width:30px !important; max-width:30px !important;
					height:30px !important; min-height:30px !important; margin:0 !important; padding:0 !important;
					place-items:center !important; border:0 !important; border-radius:999px !important;
					background:transparent !important; box-shadow:none !important; line-height:1 !important; transform:none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools :is(.header-search-icon,.search-icon,.site-search-toggle,a.tools-icon,.my-account-icon,.my-account > a,a.shopping-cart,a.shopping-bag-button,.shopping-cart > a,.shopping-bag-button > a,.cart-contents):hover {
					background:rgba(23,63,50,.075) !important; box-shadow:none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools :is(svg,.woostify-svg-icon) {
					width:18px !important; height:18px !important; max-width:18px !important; max-height:18px !important; margin:0 !important; transform:none !important;
				}
				html body.elmercado-child-theme .site-header .site-tools i { font-size:18px !important; line-height:1 !important; margin:0 !important; transform:none !important; }

				html.sidebar-menu-open body.elmercado-child-theme .site-header .toggle-sidebar-menu-btn,
				html.sidebar-menu-open body.elmercado-child-theme .site-header .site-tools {
					visibility:hidden !important; opacity:0 !important; pointer-events:none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme :is(.close-sidebar-menu-btn,.close-sidebar-menu,[class*="close-sidebar"]) {
					display:none !important; visibility:hidden !important; opacity:0 !important; pointer-events:none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu { box-sizing:border-box !important; overflow-x:hidden !important; }
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .elmercado-mobile-menu-close {
					position:absolute !important; top:12px !important; right:12px !important; left:auto !important;
					display:grid !important; width:42px !important; height:42px !important; min-width:42px !important;
					margin:0 !important; padding:0 !important; place-items:center !important; border-radius:50% !important;
					background:#173f32 !important; visibility:visible !important; opacity:1 !important; pointer-events:auto !important; z-index:30 !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu :is(.dgwt-wcas-search-wrapp,.aws-container,.search-form) {
					display:block !important; visibility:visible !important; opacity:1 !important;
					box-sizing:border-box !important; width:calc(100% - 32px) !important; max-width:calc(100% - 32px) !important;
					margin:62px 16px 14px !important; inset:auto !important; transform:none !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu .emo-duplicate-search-item {
					display:block !important; visibility:visible !important; opacity:1 !important;
					width:100% !important; max-width:100% !important; margin:0 !important; padding:0 !important;
				}
				html.sidebar-menu-open body.elmercado-child-theme .sidebar-menu :is(.dgwt-wcas-search-form,.dgwt-wcas-sf-wrapp,.aws-search-form) {
					box-sizing:border-box !important; width:100% !important; max-width:100% !important; margin:0 !important;
				}

				body.elmercado-child-theme .emo-mobile-filter-toggle { justify-content:flex-start !important; }
				body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-chevron { display:none !important; }
				body.elmercado-editorial-content .emo-journal-hero__inner,
				body.elmercado-producers-page .emo-producers-intro,
				body.wcfm-store-list-page .emo-producers-intro { border-radius:20px !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-sitewide-visual-harmony-final-js">
		(() => {
			'use strict';

			let cartActionHappened = false;
			if (window.jQuery) {
				window.jQuery(document.body).on('added_to_cart', () => { cartActionHappened = true; });
			}
			const removeStaleToast = () => {
				if (!cartActionHappened) document.querySelectorAll('.emo-cart-toast').forEach((toast) => toast.remove());
			};
			removeStaleToast();
			requestAnimationFrame(removeStaleToast);
			setTimeout(removeStaleToast, 250);

			const hideProducerSorting = () => {
				const root = document.querySelector('#wcfmmp-stores-wrap');
				if (!root || !document.body.matches('.elmercado-producers-page,.wcfm-store-list-page')) return;
				root.querySelectorAll('select').forEach((select) => {
					const signature = `${select.name || ''} ${select.id || ''} ${select.textContent || ''}`.toLowerCase();
					if (!/(orderby|order|antig|newest|oldest)/.test(signature)) return;
					let candidate = select.closest('.wcfmmp-store-lists-sorting,.wcfmmp-store-list-sorting,.wcfmmp-store-list-sorting-wrapper,.wcfmmp-store-sorting');
					if (!candidate) {
						let node = select.parentElement;
						for (let depth = 0; node && node !== root && depth < 4; depth += 1, node = node.parentElement) {
							if (/(mostrando|resultado|ordenar|antigüedad|antiguedad)/.test((node.textContent || '').toLowerCase())) candidate = node;
						}
					}
					if (candidate && candidate !== root) candidate.classList.add('emo-producers-sorting-hidden');
				});
			};
			hideProducerSorting();
			setTimeout(hideProducerSorting, 350);
			setTimeout(hideProducerSorting, 1100);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
