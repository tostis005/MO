<?php
/**
 * Plugin Name: MDO Catalogue Visual Parity Refinements 2026-08-28
 * Description: Final CSS-only specificity refinements for producer tablet controls.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_catalog_visual_parity_refinements_20260828(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<style id="mdo-catalog-visual-parity-refinements-20260828">
		/* Keep the producer filter inside exactly the same catalogue column from
		 * tablet through compact desktop. This only overrides historical geometry. */
		@media (min-width:641px) and (max-width:1100px) {
			html body.elmercado-child-theme.wcfmmp-store-page.wcfm-store-page #wcfmmp-store#wcfmmp-store .body_area > .right_side #toggle-sidebar-mobile-button#toggle-sidebar-mobile-button,
			html body.elmercado-child-theme.wcfmmp-store-page.wcfm-store-page #wcfmmp-store#wcfmmp-store .body_area > .right_side .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
				display:flex !important;
				position:relative !important;
				left:0 !important;
				right:auto !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				margin-left:0 !important;
				margin-right:0 !important;
				transform:none !important;
			}
		}

		/* A historical producer rule makes the ordering chevron 8px on smaller
		 * widths. Restate the shared 7px decorative arrow with higher specificity. */
		html body.elmercado-child-theme.wcfmmp-store-page.wcfm-store-page #wcfmmp-store#wcfmmp-store #mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
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
			html body.elmercado-child-theme.wcfmmp-store-page.wcfm-store-page #wcfmmp-store#wcfmmp-store #mdo-catalog-parity-final-20260824 .woocommerce-ordering::after {
				right:14px !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_footer', 'mdo_catalog_visual_parity_refinements_20260828', PHP_INT_MAX );
