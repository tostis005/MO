<?php
/**
 * Plugin Name: MDO Catalogue Visual Parity 2026-08-28
 * Description: CSS-only responsive visual parity between the global shop and producer catalogues.
 * Version: 1.0.0
 * Author: El Mercado de Origen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Presentation only. No catalogue queries, filters, product hooks or DOM scripts.
 */
function mdo_catalog_visual_parity_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-visual-parity-20260828">
		/* --------------------------------------------------------------
		 * Producer catalogue geometry
		 * --------------------------------------------------------------
		 * WCFM's right column already sits on the same 1180px / sidebar grid
		 * used by the global shop. A second horizontal padding narrows only the
		 * producer catalogue (896px -> 806px on desktop). Removing that second
		 * padding makes toolbar, filter rail and product grid use the same column.
		 * Top padding is deliberately left untouched.
		 */
		html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .body_area > .right_side,
		html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store .body_area > .right_side {
			padding-left:0 !important;
			padding-right:0 !important;
		}

		/* Product grid uses the same responsive column rhythm as /tienda/.
		 * This changes layout only; it cannot add, remove or filter products. */
		@media (max-width:640px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store ul.products {
				grid-template-columns:minmax(0,1fr) !important;
				column-gap:14px !important;
				row-gap:14px !important;
			}
		}

		@media (min-width:641px) and (max-width:900px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store ul.products {
				grid-template-columns:repeat(2,minmax(0,1fr)) !important;
				column-gap:18.9px !important;
				row-gap:18.9px !important;
			}
		}

		@media (min-width:901px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store ul.products,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store ul.products {
				grid-template-columns:repeat(4,minmax(0,1fr)) !important;
				column-gap:15.4px !important;
				row-gap:15.4px !important;
			}
		}

		/* --------------------------------------------------------------
		 * Filter control parity while the desktop sidebar is collapsed
		 * -------------------------------------------------------------- */
		@media (min-width:641px) and (max-width:1100px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store #toggle-sidebar-mobile-button,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store #toggle-sidebar-mobile-button {
				display:flex !important;
				position:relative !important;
				left:auto !important;
				right:auto !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				transform:none !important;
				margin-left:0 !important;
				margin-right:0 !important;
				margin-bottom:18px !important;
				justify-content:center !important;
				align-items:center !important;
		}
		}

		@media (min-width:641px) and (max-width:900px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store #toggle-sidebar-mobile-button,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store #toggle-sidebar-mobile-button {
				height:48px !important;
				min-height:48px !important;
				max-height:48px !important;
				padding:0 16px !important;
				border-radius:14px !important;
				font-size:13px !important;
				font-weight:820 !important;
				line-height:15.6px !important;
				gap:10px !important;
			}
		}

		@media (min-width:901px) and (max-width:1100px) {
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store #toggle-sidebar-mobile-button,
			html body.elmercado-child-theme.wcfm-store-page #wcfmmp-store #toggle-sidebar-mobile-button {
				height:44px !important;
				min-height:44px !important;
				max-height:44px !important;
				padding:0 14px !important;
				border-radius:12px !important;
				font-size:12px !important;
				font-weight:800 !important;
				line-height:12px !important;
				gap:10px !important;
			}
		}

		/* --------------------------------------------------------------
		 * One decorative chevron system for destination + ordering
		 * --------------------------------------------------------------
		 * Existing destination SVGs stay in the DOM (behaviour untouched) but
		 * their last/chevron icon is visually hidden. Both controls then draw
		 * the same 7px CSS chevron, at the same vertical centre and weight.
		 */
		#mdo-catalog-parity-final-20260824 [data-mdo-destination-open],
		#mdo-catalog-parity-final-20260824 [data-mdo-ps-destination-open] {
			position:relative !important;
		}

		#mdo-catalog-parity-final-20260824 [data-mdo-destination-open] > svg:last-of-type,
		#mdo-catalog-parity-final-20260824 [data-mdo-ps-destination-open] > svg:last-of-type,
		#mdo-catalog-parity-final-20260824 [data-mdo-destination-open] .mdo-catalog-destination__chevron,
		#mdo-catalog-parity-final-20260824 [data-mdo-ps-destination-open] .mdo-catalog-destination__chevron {
			visibility:hidden !important;
		}

		#mdo-catalog-parity-final-20260824 [data-mdo-destination-open]::after,
		#mdo-catalog-parity-final-20260824 [data-mdo-ps-destination-open]::after,
		#mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
			content:"" !important;
			display:block !important;
			position:absolute !important;
			top:50% !important;
			right:15px !important;
			left:auto !important;
			box-sizing:border-box !important;
			width:7px !important;
			height:7px !important;
			margin:-5px 0 0 !important;
			padding:0 !important;
			border:0 !important;
			border-right:1.5px solid #173f32 !important;
			border-bottom:1.5px solid #173f32 !important;
			background:transparent !important;
			box-shadow:none !important;
			opacity:.72 !important;
			transform:rotate(45deg) !important;
			transform-origin:center !important;
			pointer-events:none !important;
			z-index:3 !important;
		}

		@media (max-width:640px) {
			#mdo-catalog-parity-final-20260824 [data-mdo-destination-open]::after,
			#mdo-catalog-parity-final-20260824 [data-mdo-ps-destination-open]::after,
			#mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
				right:14px !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_footer', 'mdo_catalog_visual_parity_20260828', PHP_INT_MAX );
