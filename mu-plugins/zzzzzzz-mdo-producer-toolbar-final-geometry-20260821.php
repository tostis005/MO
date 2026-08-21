<?php
/**
 * Plugin Name: MDO Producer Toolbar Final Geometry
 * Description: Fixes the producer destination pill intrinsic sizing and keeps the catalogue toolbar visually separated from product cards.
 * Version: 1.1.0
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
		<style id="mdo-producer-toolbar-final-geometry-20260821">
			/*
			 * WCFM's trigger contains three real children: location SVG + text + chevron.
			 * The shared EMDO control needs only text + chevron. Removing the first SVG
			 * also makes the two-column grid's intrinsic width represent the real label.
			 */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:first-child,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:first-child {
				display:none !important;
				visibility:hidden !important;
				width:0 !important;
				height:0 !important;
				margin:0 !important;
				padding:0 !important;
			}

			/* Producer catalogue only. Desktop/tablet: size the shipping pill from its text. */
			@media (min-width:641px) {
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left {
					display:flex !important;
					align-items:center !important;
					gap:14px !important;
					overflow:visible !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .woocommerce-result-count {
					order:1 !important;
					flex:0 0 auto !important;
					width:max-content !important;
					min-width:max-content !important;
					max-width:none !important;
					overflow:visible !important;
					white-space:nowrap !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .mdo-catalog-destination--canonical {
					order:2 !important;
					display:inline-flex !important;
					flex:0 0 auto !important;
					width:max-content !important;
					min-width:0 !important;
					max-width:none !important;
					overflow:visible !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .mdo-catalog-destination--canonical > .mdo-catalog-destination__trigger {
					display:inline-grid !important;
					grid-template-columns:max-content 16px !important;
					column-gap:9px !important;
					width:max-content !important;
					min-width:142px !important;
					max-width:none !important;
					overflow:visible !important;
					white-space:nowrap !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
					display:block !important;
					min-width:max-content !important;
					overflow:visible !important;
					text-overflow:clip !important;
					white-space:nowrap !important;
				}
			}

			/* No historical pseudo pin either. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger::before,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger::before {
				content:none !important;
				display:none !important;
			}

			/* Keep a calm visual gap before the cards; no negative transforms are allowed here. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host {
				margin-bottom:22px !important;
				overflow:visible !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store ul.products {
				transform:none !important;
				margin-top:0 !important;
				padding-top:0 !important;
			}

			/* Phone: full-width stacked control, matching the main shop. */
			@media (max-width:640px) {
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .mdo-catalog-destination--canonical {
					display:block !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .mdo-catalog-destination--canonical > .mdo-catalog-destination__trigger {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) 16px !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					overflow:hidden !important;
				}
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
				html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
					min-width:0 !important;
					overflow:hidden !important;
					text-overflow:ellipsis !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
