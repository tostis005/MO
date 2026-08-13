<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
add_action( 'wp_head', static function (): void {
	if ( is_admin() ) { return; }
	?>
	<style id="elmercado-vendor-filter-spacing-010226">
	@media (min-width:1101px) {
		html body.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 > #emo-vendor-filters {
			box-sizing:border-box !important;
			width:100% !important;
			margin:0 !important;
			padding:18px !important;
		}
	}
	</style>
	<?php
}, PHP_INT_MAX );
