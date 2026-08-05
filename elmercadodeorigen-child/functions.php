<?php
/**
 * Bootstrap del tema hijo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELMERCADO_THEME_VERSION', '0.5.0' );
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

/* La optimización se ejecuta una sola vez, al final del encolado normal. */
remove_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );
remove_action( 'wp_print_footer_scripts', 'elmercado_optimize_home_assets', 0 );
