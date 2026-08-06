<?php
/**
 * Corrección final del alcance de contraste y posición del logo del productor.
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

		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH ) : '';
		if ( is_string( $request_path ) && preg_match( '#^/tienda/[^/]+/#', $request_path ) ) {
			echo '<meta name="description" content="Compra directamente los productos de este productor y conoce su historia en El Mercado de Origen.">' . "\n";
		}
		?>
		<style id="elmercado-vendor-home-verification-three">
			body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-vendor-logo {
				top: auto !important;
				right: auto !important;
				bottom: clamp(1.2rem, 3vw, 2.4rem) !important;
				left: clamp(1.2rem, 4vw, 3rem) !important;
				transform: none !important;
			}

			/* Desactiva el alcance excesivo de la comprobación anterior. */
			body.elmercado-child-theme .emo-origin-distance-section,
			body.elmercado-child-theme .emo-origin-distance-section * {
				color: revert !important;
			}

			/* Solo el grupo de manifiesto y sus tres tarjetas recibe corrección de contraste. */
			body.elmercado-child-theme .emo-origin-distance-grid,
			body.elmercado-child-theme .emo-origin-distance-grid * {
				color: #173f32 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-grid .emo-origin-distance-card,
			body.elmercado-child-theme .emo-origin-distance-grid .emo-origin-distance-card * {
				color: #fffdf8 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-grid .emo-origin-distance-card p {
				opacity: 0.9 !important;
			}

			/* Restablece explícitamente el contraste del hero principal. */
			body.home.elmercado-child-theme .emo-home-hero,
			body.home.elmercado-child-theme .emo-home-hero h1,
			body.home.elmercado-child-theme .emo-home-hero h2,
			body.home.elmercado-child-theme .emo-home-hero h3,
			body.home.elmercado-child-theme .emo-home-hero p,
			body.home.elmercado-child-theme .emo-home-hero a,
			body.home.elmercado-child-theme .emo-home-hero span {
				color: #fffdf8 !important;
			}

			@media (max-width: 640px) {
				body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-vendor-logo {
					top: auto !important;
					right: auto !important;
					bottom: 1rem !important;
					left: 50% !important;
					transform: translateX(-50%) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-origin-distance-grid-scope">
		(() => {
			document.querySelectorAll('.emo-origin-distance-section').forEach((node) => node.classList.remove('emo-origin-distance-section'));
			const card = document.querySelector('.emo-origin-distance-card');
			if (!card) return;
			const grid = card.parentElement && card.parentElement.parentElement ? card.parentElement.parentElement : card.parentElement;
			if (grid) grid.classList.add('emo-origin-distance-grid');
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
