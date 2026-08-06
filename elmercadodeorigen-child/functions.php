<?php
/**
 * Bootstrap del tema hijo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELMERCADO_THEME_VERSION', '0.8.2' );
define( 'ELMERCADO_THEME_PATH', get_stylesheet_directory() );
define( 'ELMERCADO_THEME_URL', get_stylesheet_directory_uri() );

require_once ELMERCADO_THEME_PATH . '/inc/setup.php';
require_once ELMERCADO_THEME_PATH . '/inc/woocommerce.php';
require_once ELMERCADO_THEME_PATH . '/inc/polish.php';
require_once ELMERCADO_THEME_PATH . '/inc/performance.php';
require_once ELMERCADO_THEME_PATH . '/inc/home-cache.php';
require_once ELMERCADO_THEME_PATH . '/inc/output-optimization.php';
require_once ELMERCADO_THEME_PATH . '/inc/final-performance.php';
require_once ELMERCADO_THEME_PATH . '/inc/header-finish.php';
require_once ELMERCADO_THEME_PATH . '/inc/home-navigation.php';
require_once ELMERCADO_THEME_PATH . '/inc/home-refresh.php';
require_once ELMERCADO_THEME_PATH . '/inc/home-header-normalize.php';
require_once ELMERCADO_THEME_PATH . '/inc/release-one.php';
require_once ELMERCADO_THEME_PATH . '/inc/release-one-finish.php';
require_once ELMERCADO_THEME_PATH . '/inc/editorial-system.php';
require_once ELMERCADO_THEME_PATH . '/inc/editorial-performance.php';
require_once ELMERCADO_THEME_PATH . '/inc/editorial-finish.php';
require_once ELMERCADO_THEME_PATH . '/inc/commerce-experience.php';
require_once ELMERCADO_THEME_PATH . '/inc/performance-release.php';
require_once ELMERCADO_THEME_PATH . '/inc/semantic-polish.php';
require_once ELMERCADO_THEME_PATH . '/inc/global-finish.php';
require_once ELMERCADO_THEME_PATH . '/inc/professional-finish.php';
require_once ELMERCADO_THEME_PATH . '/inc/vendor-storefront.php';
require_once ELMERCADO_THEME_PATH . '/inc/premium-qa.php';

$elmercado_optional_finish = ELMERCADO_THEME_PATH . '/inc/header-search-finish.php';
if ( is_readable( $elmercado_optional_finish ) ) {
	require_once $elmercado_optional_finish;
}

/* La optimización se ejecuta una sola vez, al final del encolado normal. */
remove_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );
remove_action( 'wp_print_footer_scripts', 'elmercado_optimize_home_assets', 0 );
