<?php
/**
 * Temporarily suppress public language controls while translated storefront
 * pages are tested by direct URL. Falang itself stays active so /en/... routes
 * and translated content continue to work normally.
 *
 * @package ElMercadoDeOrigen
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
		<style id="elmercado-language-switchers-hidden-010246">
			/* Keep all public language-changing controls hidden during testing. */
			.elmercado-falang-switcher,
			.falang-language-switcher,
			[class*="falang-language-switcher"],
			.trp-language-switcher-container,
			.trp-language-switcher,
			#trp-floater-ls,
			.trp-floater-ls,
			li.menu-item-language,
			li.lang-item {
				display: none !important;
				visibility: hidden !important;
				pointer-events: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
