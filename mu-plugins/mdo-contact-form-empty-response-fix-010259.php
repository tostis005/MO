<?php
/**
 * Plugin Name: MDO Contact Form Empty Response Fix
 * Description: Hides the empty Contact Form 7 response container until it contains a real validation or submission message.
 * Version: 1.0.0
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
		<style id="mdo-contact-form-empty-response-fix-010259">
			.wpcf7 form .wpcf7-response-output:empty {
				display: none !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				min-height: 0 !important;
				height: 0 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
