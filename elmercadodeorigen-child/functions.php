<?php
/**
 * Bootstrap del tema hijo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELMERCADO_THEME_VERSION', '0.10.3' );
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
	'inc/header-exact-consistency.php',
	'inc/mobile-commerce-finish.php',
	'inc/product-card-carousel-finish.php',
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
