<?php
/**
 * Compact Falang language switcher that replaces the previous TranslatePress
 * floating control. Languages are sourced from Falang, so future published
 * languages appear automatically without theme changes.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! shortcode_exists( 'falangsw' ) ) {
			return;
		}

		$switcher = do_shortcode( '[falangsw display_name="0" display_flags="1" hide_current="0" positioning="h"]' );
		if ( '' === trim( $switcher ) ) {
			return;
		}
		?>
		<div class="elmercado-falang-switcher" aria-label="Selector de idioma">
			<?php echo $switcher; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Falang generates the language links. ?>
		</div>
		<style id="elmercado-falang-switcher-010245">
			.elmercado-falang-switcher {
				position: fixed;
				right: 10vw;
				bottom: 0;
				z-index: 99999;
				padding: 9px 11px;
				background: #fff;
				border: 1px solid rgba(20, 56, 82, .12);
				border-bottom: 0;
				border-radius: 8px 8px 0 0;
				box-shadow: 0 -4px 18px rgba(20, 56, 82, .08);
				line-height: 1;
			}
			.elmercado-falang-switcher .falang-language-switcher {
				display: flex !important;
				align-items: center;
				gap: 9px;
				margin: 0 !important;
				padding: 0 !important;
				list-style: none !important;
			}
			.elmercado-falang-switcher .falang-language-switcher li {
				display: block;
				margin: 0 !important;
				padding: 0 !important;
			}
			.elmercado-falang-switcher .falang-language-switcher a,
			.elmercado-falang-switcher .falang-language-switcher span {
				display: block;
				padding: 0 !important;
				line-height: 1 !important;
			}
			.elmercado-falang-switcher .falang-language-switcher img {
				display: block;
				width: 24px;
				height: auto;
				margin: 0 !important;
				border-radius: 2px;
			}
			.elmercado-falang-switcher .falang-language-switcher .current img {
				outline: 2px solid rgba(20, 56, 82, .22);
				outline-offset: 2px;
			}
			@media (max-width: 767px) {
				.elmercado-falang-switcher {
					right: 12px;
					padding: 8px 10px;
				}
				.elmercado-falang-switcher .falang-language-switcher img {
					width: 22px;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
