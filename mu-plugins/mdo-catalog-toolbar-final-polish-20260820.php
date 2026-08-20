<?php
/**
 * Plugin Name: MDO Catalog Toolbar Final Polish
 * Description: Final geometry and layering overrides for the native catalog toolbar and shipping destination modal.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( ! function_exists( 'mdo_catalog_summarybar_is_surface_20260820' ) || ! mdo_catalog_summarybar_is_surface_20260820() ) {
			return;
		}
		?>
		<style id="mdo-catalog-toolbar-final-polish-20260820">
			/* Desktop: every control shares the same 42 px optical baseline. */
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
				min-height:68px !important;
				align-items:center !important;
				padding:12px 14px !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
				display:flex !important;
				align-items:center !important;
				min-height:42px !important;
				height:42px !important;
				max-height:42px !important;
				gap:15px !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left .emo-catalog-result-count-010220.woocommerce-result-count {
				dis:flex !important;
				display:flex !important;
				align-items:center !important;
				height:42px !important;
				min-height:42px !important;
				max-height:42px !important;
				margin:0 !important;
				padding:0 2px !important;
				line-height:1.25 !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select {
				height:42px !important;
				min-height:42px !important;
				max-height:42px !important;
				align-self:center !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering {
				margin-left:auto !important;
			}

			/* No leading locator icon; the chevron sits exactly on the vertical centre. */
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__pin {
				display:none !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron {
				display:grid !important;
				place-items:center !important;
				align-self:center !important;
				width:16px !important;
				height:16px !important;
				min-width:16px !important;
				min-height:16px !important;
				margin:0 !important;
				padding:0 !important;
				line-height:0 !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__chevron svg {
				display:block !important;
				width:12px !important;
				height:8px !important;
				margin:0 !important;
				transform:none !important;
			}
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:hover *,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus *,
			html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger:focus-visible * {
				color:#173f32 !important;
			}

			/* Phones: one calm column. Count, destination and ordering never compete for width. */
			@media (max-width:640px) {
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 {
					display:grid !important;
					grid-template-columns:minmax(0,1fr) !important;
					grid-template-rows:auto auto !important;
					align-items:stretch !important;
					justify-items:stretch !important;
					gap:10px !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:auto !important;
					min-height:0 !important;
					max-height:none !important;
					overflow:visible !important;
					margin:0 0 12px !important;
					padding:12px !important;
				}
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left,
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left.elmercado-vendor-filter-hidden {
					display:grid !important;
					grid-column:1 !important;
					grid-row:1 !important;
					grid-template-columns:minmax(0,1fr) !important;
					grid-template-rows:auto 40px !important;
					align-items:stretch !important;
					gap:7px !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:auto !important;
					min-height:0 !important;
					max-height:none !important;
					overflow:visible !important;
					margin:0 !important;
					padding:0 !important;
				}
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woostify-toolbar-left .emo-catalog-result-count-010220.woocommerce-result-count {
					grid-column:1 !important;
					grid-row:1 !important;
					display:flex !important;
					align-items:center !important;
					width:100% !important;
					height:17px !important;
					min-height:17px !important;
					max-height:17px !important;
					margin:0 !important;
					padding:0 2px !important;
					font-size:11px !important;
					line-height:17px !important;
				}
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination--canonical {
					grid-column:1 !important;
					grid-row:2 !important;
					display:block !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					margin:0 !important;
				}
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 .mdo-catalog-destination__trigger {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					padding:0 12px 0 13px !important;
					font-size:11.75px !important;
				}
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering {
					grid-column:1 !important;
					grid-row:2 !important;
					display:flex !important;
					box-sizing:border-box !important;
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					margin:0 !important;
					padding:0 !important;
				}
				html body.elmercado-child-theme .woostify-sorting.emo-catalog-toolbar-shared-010229 > .woocommerce-ordering select {
					width:100% !important;
					min-width:0 !important;
					max-width:100% !important;
					height:40px !important;
					min-height:40px !important;
					max-height:40px !important;
					padding:0 32px 0 12px !important;
					font-size:11.75px !important;
				}
			}

			/* Modal is a root layer and its close control matches drawer sizing. */
			html body > .mdo-destination-modal--root {
				z-index:2147483646 !important;
			}
			html body > .mdo-destination-modal--root .mdo-destination-modal__close {
				width:42px !important;
				height:42px !important;
				min-width:42px !important;
				min-height:42px !important;
				top:10px !important;
				right:10px !important;
			}
			html body > .mdo-destination-modal--root .mdo-destination-modal__panel h2 {
				margin-right:48px !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
