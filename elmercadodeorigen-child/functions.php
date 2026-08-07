<?php
/**
 * Bootstrap del tema hijo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELMERCADO_THEME_VERSION', '0.10.41' );
define( 'ELMERCADO_THEME_PATH', get_stylesheet_directory() );
define( 'ELMERCADO_THEME_URL', get_stylesheet_directory_uri() );

$elmercado_modules = array(
	'inc/setup.php',
	'inc/woocommerce.php',
	'inc/polish.php',
	'inc/performance.php',
	'inc/home-cache.php',
	'inc/output-optimization.php',
	'inc/final-performance.php',
	'inc/header-finish.php',
	'inc/home-navigation.php',
	'inc/home-refresh.php',
	'inc/home-header-normalize.php',
	'inc/release-one.php',
	'inc/release-one-finish.php',
	'inc/editorial-system.php',
	'inc/editorial-performance.php',
	'inc/editorial-finish.php',
	'inc/commerce-experience.php',
	'inc/performance-release.php',
	'inc/semantic-polish.php',
	'inc/global-finish.php',
	'inc/professional-finish.php',
	'inc/header-search-finish.php',
	'inc/vendor-store-finish.php',
	'inc/premium-qa.php',
	'inc/premium-visual-finish.php',
	'inc/vendor-home-final.php',
	'inc/vendor-home-verification.php',
	'inc/vendor-home-verification-two.php',
	'inc/vendor-home-verification-three.php',
	'inc/accessibility-contrast-final.php',
	'inc/storefront-final-pass.php',
	'inc/premium-storefront-polish.php',
	'inc/visual-correction-093.php',
	'inc/storefront-edge-fix.php',
	'inc/minicart-final-control.php',
	'inc/layout-consistency-096.php',
	'inc/layout-consistency-098.php',
	'inc/mobile-commerce-finish.php',
	'inc/product-card-carousel-finish.php',
	'inc/shop-producer-filter-final.php',
	'inc/home-mobile-header-final.php',
	'inc/runtime-stability-final.php',
	'inc/vendor-toolbar-mobile-final.php',
	'inc/storefront-second-review-final.php',
	'inc/cart-counter-visibility-final.php',
	'inc/mobile-catalog-interactions-final.php',
	'inc/header-unified-final.php',
	'inc/sitewide-visual-harmony-final.php',
	'inc/cart-toast-guard-final.php',
	'inc/mobile-menu-visual-final.php',
	'inc/mobile-header-hitareas-final.php',
	'inc/mobile-visual-corrections-01023.php',
	'inc/mobile-visual-corrections-01024.php',
	'inc/comprehensive-review-01033.php',
	'inc/checkout-contrast-final-01035.php',
	'inc/checkout-stock-final-01036.php',
	'inc/store-vendor-layout-final-01037.php',
	'inc/layout-density-final-01039.php',
	'inc/vendor-flow-gap-final-01041.php',
);

foreach ( $elmercado_modules as $elmercado_module ) {
	$elmercado_module_path = ELMERCADO_THEME_PATH . '/' . $elmercado_module;
	if ( is_readable( $elmercado_module_path ) ) {
		require_once $elmercado_module_path;
	}
}

/* La optimización se ejecuta una sola vez, al final del encolado normal. */
remove_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );
remove_action( 'wp_print_footer_scripts', 'elmercado_optimize_home_assets', 0 );
