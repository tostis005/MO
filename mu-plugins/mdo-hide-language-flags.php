<?php
/**
 * Hide visual flag-only language controls while keeping the multilingual
 * engine, translated URLs and translated content untouched.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		?>
		<style id="mdo-hide-language-flags-20260816">
			/* TranslatePress floating control is currently flag-only: hide it. */
			#trp-floater-ls {
				display: none !important;
			}

			/* If another TranslatePress switcher is rendered with text, keep the
			 * selector itself but remove only the flag artwork. */
			.trp-language-switcher img,
			.trp-ls-shortcode-current-language img,
			.trp-ls-shortcode-language img,
			.menu-item-object-language_switcher img {
				display: none !important;
			}

			/* The Falang footer control prepared in the theme is flag-only too. */
			.elmercado-falang-switcher {
				display: none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
