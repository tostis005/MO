<?php
/**
 * Bootstrap del tema hijo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ELMERCADO_THEME_VERSION', '0.10.266' );
/* 0.10.266 añade AdSense geolocalizado y condicionado al consentimiento en entradas del blog. */
define( 'ELMERCADO_THEME_PATH', get_stylesheet_directory() );
define( 'ELMERCADO_THEME_URL', get_stylesheet_directory_uri() );

$elmercado_modules = array(
	'inc/setup.php',
	'inc/woocommerce.php',
	'inc/polish.php',
	'inc/performance.php',
	'inc/home-cache.php',
	'inc/home-gla-defer-010260.php',
	'inc/final-performance.php',
	'inc/header-finish.php',
	'inc/home-navigation.php',
	'inc/home-refresh.php',
	'inc/home-header-normalize.php',
	'inc/legacy-runtime-cleanup-01093.php',
	'inc/editorial-system.php',
	'inc/editorial-performance.php',
	'inc/editorial-finish.php',
	'inc/commerce-experience.php',
	'inc/performance-release.php',
	'inc/semantic-polish.php',
	'inc/global-finish.php',
	'inc/professional-finish.php',
	'inc/header-search-finish.php',
	'inc/header-search-copy-neutral-010208.php',
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
	'inc/minicart-final-control.php',
	'inc/layout-consistency-096.php',
	'inc/layout-consistency-098.php',
	'inc/minicart-quantity-events-01093.php',
	'inc/product-card-carousel-finish.php',
	'inc/shop-producer-filter-final.php',
	'inc/home-mobile-header-final.php',
	'inc/runtime-stability-final.php',
	'inc/vendor-toolbar-mobile-final.php',
	'inc/storefront-second-review-final.php',
	'inc/cart-counter-visibility-final.php',
	'inc/mobile-catalog-interactions-final.php',
	'inc/catalog-continuous-loading-010176.php',
	'inc/header-unified-final.php',
	'inc/sitewide-visual-harmony-final.php',
	'inc/cart-toast-event-guard-01093.php',
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
	'inc/content-header-unification-01042.php',
	'inc/shop-filter-breakpoint-final-01044.php',
	'inc/premium-release-01045.php',
	'inc/premium-release-01046.php',
	'inc/premium-visual-system-01048.php',
	'inc/interaction-layer-01050.php',
	'inc/home-product-card-finish-01052.php',
	'inc/content-alignment-final-01053.php',
	'inc/home-carousel-inert-controls-01054.php',
	'inc/experience-polish-final-01055.php',
	'inc/transaction-focus-final-01056.php',
	'inc/transaction-tail-cleanup-01058.php',
	'inc/checkout-summary-column-01059.php',
	'inc/mobile-reading-checkout-01060.php',
	'inc/visual-coherence-01063.php',
	'inc/redundant-page-header-removal-01068.php',
	'inc/content-start-mobile-filter-01069.php',
	'inc/content-start-stability-01070.php',
	'inc/commerce-top-rhythm-01071.php',
	'inc/content-start-final-01072.php',
	'inc/page-start-filter-final-01074.php',
	'inc/visible-start-line-01080.php',
	'inc/blog-meta-visibility-final-01088.php',
	'inc/desktop-filter-layout-final-01090.php',
	'inc/filter-price-init-final-01090.php',
	'inc/desktop-filter-sticky-final-01091.php',
	'inc/native-filter-trigger-guard-01094.php',
	'inc/user-feedback-pass-01095.php',
	'inc/native-filter-remove-01096.php',
	'inc/mobile-home-contrast-01097.php',
	'inc/home-rhythm-final-01099.php',
	'inc/user-request-polish-010100.php',
	'inc/user-request-review-010102.php',
	'inc/filtered-context-desktop-010102.php',
	'inc/visual-continuity-final-010110.php',
	'inc/home-palette-site-final-010117.php',
	'inc/home-hero-cart-balance-010119.php',
	'inc/cart-checkout-filter-refinement-010121.php',
	'inc/cart-checkout-state-cleanup-010123.php',
	'inc/checkout-stability-visual-final-010124.php',
	'inc/transaction-cascade-final-010126.php',
	'inc/checkout-legibility-final-010128.php',
	'inc/cart-checkout-shipping-final-010132.php',
	'inc/home-cart-visual-cleanup-010135.php',
	'inc/home-footer-gap-final-010136.php',
	'inc/checkout-clean-coupon-010137.php',
	'inc/mobile-shipping-calculator-final-010144.php',
	'inc/desktop-shipping-calculator-final-010145.php',
	'inc/home-final-copy-performance-010146.php',
	'inc/home-performance-second-pass-010147.php',
	'inc/home-inline-jquery-010148.php',
	'inc/home-voice-performance-final-010149.php',
	'inc/product-card-density-final-010162.php',
	'inc/product-card-footer-density-010163.php',
	'inc/product-card-footer-flow-010164.php',
	'inc/related-products-responsive-grid-010175.php',
	'inc/home-copy-definitive-010165.php',
	'inc/home-story-card-rhythm-010166.php',
	'inc/commerce-home-clarity-final-010168.php',
	'inc/user-feedback-home-product-010169.php',
	'inc/announcement-rotator-final-010171.php',
	'inc/cart-checkout-contact-cleanup-010185.php',
	'inc/category-specific-filters-010185.php',
	'inc/cart-shipping-copy-final-010192.php',
	'inc/wcfm-disabled-vendor-visibility-010210.php',
	'inc/product-navigation-performance-010237.php',
	'inc/catalog-filter-system-final-010207.php',
	'inc/wcfm-disabled-vendor-ui-010211.php',
	'inc/mobile-price-slider-alignment-010209.php',
	'inc/home-category-visibility-010212.php',
	'inc/home-category-edge-align-010215.php',
	'inc/catalog-visibility-counts-010217.php',
	'inc/catalog-result-total-010220.php',
	'inc/catalog-result-total-cleanup-010221.php',
	'inc/catalog-toolbar-mobile-price-fix-010222.php',
	'inc/catalog-query-parity-010224.php',
	'inc/vendor-store-catalog-filters-010225.php',
	'inc/home-category-truth-before-legacy-010226.php',
	'inc/vendor-filter-spacing-010226.php',
	'inc/home-category-output-final-010226.php',
	'inc/catalog-filter-mobile-hitarea-010233.php',
	'inc/catalog-filter-scroll-final-010234.php',
	'inc/catalog-vendor-truth-final-010235.php',
	'inc/producer-list-image-square-010242.php',
	'inc/multilingual-ui-copy-010245.php',
	'inc/falang-switcher-010245.php',
	'inc/blog-editorial-body-class-010249.php',
	'inc/blog-footer-layout-polish-010246.php',
	'inc/blog-design-force-010250.php',
	'inc/blog-default-image-010264.php',
	'inc/seo-meta-descriptions-010265.php',
	'inc/adsense-geo-010266.php',
);

foreach ( $elmercado_modules as $elmercado_module ) {
	$elmercado_module_path = ELMERCADO_THEME_PATH . '/' . $elmercado_module;
	if ( is_readable( $elmercado_module_path ) ) {
		require_once $elmercado_module_path;
	}
}

/*
 * El contrato 0.10.229 se registra después de los cargadores históricos del
 * catálogo y es la única capa de paridad visual/geométrica que queda activa.
 */
add_action(
	'after_setup_theme',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/catalog-filter-unified-010229.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);

/*
 * La paridad móvil 0.10.236 se registra después del contrato 0.10.229 para que
 * sea la última palabra sobre toolbar, ordenación y posición del trigger.
 */
add_action(
	'after_setup_theme',
	static function (): void {
		$module = ELMERCADO_THEME_PATH . '/inc/catalog-mobile-controls-parity-010236.php';
		if ( is_readable( $module ) ) {
			require_once $module;
		}
	},
	PHP_INT_MAX
);

remove_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );
remove_action( 'wp_print_footer_scripts', 'elmercado_optimize_home_assets', 0 );
