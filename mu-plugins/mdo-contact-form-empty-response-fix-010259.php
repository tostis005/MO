<?php
/**
 * Plugin Name: MDO Contact Form Empty Response Fix
 * Description: Prevents hidden Contact Form 7 infrastructure from rendering as empty boxes while preserving real validation and submission messages.
 * Version: 1.1.0
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
			/*
			 * Contact Form 7 wraps its transport fields in a fieldset. Samsung
			 * Internet can apply the browser's default fieldset border even though
			 * every control inside it is type="hidden", producing an empty grey box
			 * before the first real field. Keep the hidden inputs in the form but
			 * remove their visual wrapper completely.
			 */
			.wpcf7 form > fieldset.hidden-fields-container,
			.wpcf7 form fieldset.hidden-fields-container {
				display: none !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				width: 0 !important;
				height: 0 !important;
				min-width: 0 !important;
				min-height: 0 !important;
			}

			/* Empty CF7 response boxes also stay invisible until a real message exists. */
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
