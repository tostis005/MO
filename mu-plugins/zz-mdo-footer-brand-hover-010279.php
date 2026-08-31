<?php
/**
 * Plugin Name: MDO - Footer brand and social hover refinements 0.10.282
 * Description: Ajusta el título principal del footer global, aplica los colores de marca y mantiene coherente la caché HTML de Home.
 * Version: 0.10.282
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * La Home tiene una caché HTML propia en el tema hijo. Como un MU-plugin no
 * dispara los hooks editoriales que vacían esa caché, invalidamos una sola vez
 * por versión del refinamiento antes de template_redirect, donde la Home hace HIT.
 */
add_action(
	'init',
	static function (): void {
		$version = '0.10.282';
		$option  = 'mdo_footer_home_cache_coherence_version';

		if ( get_option( $option, '' ) === $version ) {
			return;
		}

		if ( function_exists( 'elmercado_flush_home_cache' ) ) {
			elmercado_flush_home_cache();
		} else {
			// Fallback defensivo si el tema cambia su orden de carga.
			$static_dir = WP_CONTENT_DIR . '/uploads/elmercado-home-static';
			foreach ( glob( $static_dir . '/index.html' ) ?: array() as $file ) {
				if ( is_file( $file ) ) {
					@unlink( $file );
				}
			}
		}

		update_option( $option, $version, false );
	},
	-9999
);

// Global refinement layered after the base footer module.
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="mdo-footer-brand-hover-010279">
			html body .site-footer .mdo-footer-social--instagram:hover,
			html body .site-footer .mdo-footer-social--instagram:focus-visible {
				background: #e4405f !important;
				border-color: #e4405f !important;
				color: #fff !important;
			}

			html body .site-footer .mdo-footer-social--facebook:hover,
			html body .site-footer .mdo-footer-social--facebook:focus-visible {
				background: #1877f2 !important;
				border-color: #1877f2 !important;
				color: #fff !important;
			}

			html body .site-footer .mdo-footer-social--telegram:hover,
			html body .site-footer .mdo-footer-social--telegram:focus-visible {
				background: #229ed9 !important;
				border-color: #229ed9 !important;
				color: #fff !important;
			}

			html body .site-footer .mdo-footer-social--whatsapp:hover,
			html body .site-footer .mdo-footer-social--whatsapp:focus-visible {
				background: #25d366 !important;
				border-color: #25d366 !important;
				color: #fff !important;
			}
		</style>
		<script id="mdo-footer-brand-hover-runtime-010279">
		(() => {
			const apply = () => {
				const footer = document.querySelector('.site-footer.mdo-footer-enhanced-010278');
				if (!footer) return false;

				const heading = footer.querySelector('.mdo-footer-main-010278 > .mdo-footer-column-010278:first-child h2');
				if (heading) heading.textContent = 'El Mercado de Origen';

				return !!heading;
			};

			if (apply()) return;

			const observer = new MutationObserver(() => {
				if (apply()) observer.disconnect();
			});

			observer.observe(document.documentElement, { childList: true, subtree: true });
			window.setTimeout(() => observer.disconnect(), 10000);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
