<?php
/**
 * Cierre definitivo del breakpoint de filtros de tienda 0.10.44.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-shop-filter-breakpoint-final-01044">
			@media (max-width: 1100px) {
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) #content .woostify-container,
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .site-content > .woostify-container {
					display: block !important;
					grid-template-columns: minmax(0, 1fr) !important;
					width: 100% !important;
					max-width: none !important;
				}
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) #primary.content-area,
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .content-area,
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) main.site-main {
					display: block !important;
					width: 100% !important;
					max-width: 100% !important;
					min-width: 0 !important;
					flex: 1 1 100% !important;
					flex-basis: 100% !important;
					float: none !important;
					margin-inline: 0 !important;
					padding-right: 0 !important;
				}

				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle {
					display: inline-flex !important;
					width: 100% !important;
					height: 44px !important;
					min-height: 44px !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 10px !important;
					margin: 0 0 18px !important;
					padding: 0 14px !important;
					border: 1px solid rgba(23,63,50,.13) !important;
					border-radius: 12px !important;
					background: #f7f9f6 !important;
					color: #173f32 !important;
					font-size: 12px !important;
					font-weight: 750 !important;
					box-shadow: none !important;
					cursor: pointer !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-toggle::before {
					content: "" !important;
					width: 16px !important;
					height: 16px !important;
					flex: 0 0 16px !important;
					background:
						linear-gradient(#2f7d5d,#2f7d5d) 0 3px/16px 1px no-repeat,
						linear-gradient(#2f7d5d,#2f7d5d) 0 8px/16px 1px no-repeat,
						linear-gradient(#2f7d5d,#2f7d5d) 0 13px/16px 1px no-repeat !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-label {
					margin-right: auto !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-toggle .emo-filter-chevron {
					font-size: 17px !important;
					line-height: 1 !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-toggle[aria-expanded="true"] .emo-filter-chevron {
					transform: rotate(180deg) !important;
				}

				html body.elmercado-child-theme .emo-mobile-filter-shell {
					position: fixed !important;
					inset: 0 !important;
					display: block !important;
					background: rgba(8,27,22,.42) !important;
					z-index: 10020 !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-shell[hidden] {
					display: none !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-panel {
					position: absolute !important;
					inset: 0 auto 0 0 !important;
					width: min(86vw, 330px) !important;
					max-width: 330px !important;
					height: 100% !important;
					padding: 14px 14px calc(22px + env(safe-area-inset-bottom,0px)) !important;
					overflow-y: auto !important;
					background: #fff !important;
					box-shadow: 16px 0 46px rgba(8,27,22,.18) !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-head {
					display: flex !important;
					min-height: 44px !important;
					align-items: center !important;
					justify-content: space-between !important;
					gap: 12px !important;
					margin: 0 0 14px !important;
					padding-bottom: 10px !important;
					border-bottom: 1px solid rgba(23,63,50,.12) !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-title {
					margin: 0 !important;
					color: #173f32 !important;
					font-size: 14px !important;
					font-weight: 800 !important;
					letter-spacing: .01em !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-close {
					display: grid !important;
					width: 40px !important;
					height: 40px !important;
					min-width: 40px !important;
					padding: 0 !important;
					place-items: center !important;
					border: 0 !important;
					border-radius: 50% !important;
					background: #173f32 !important;
					color: #fff !important;
					font-size: 22px !important;
					line-height: 1 !important;
				}
				html body.elmercado-child-theme .emo-mobile-filter-panel .widget-area {
					display: block !important;
					position: static !important;
					width: 100% !important;
					max-width: none !important;
					height: auto !important;
					margin: 0 !important;
					padding: 0 !important;
					visibility: visible !important;
					opacity: 1 !important;
					transform: none !important;
				}
				html.emo-shop-filter-open,
				body.emo-shop-filter-open {
					overflow: hidden !important;
				}
			}

			@media (min-width: 1101px) {
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle,
				html body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-shell {
					display: none !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
