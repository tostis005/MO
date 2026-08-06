<?php
/**
 * Segunda verificación visual de la cabecera WCFM y del manifiesto de portada.
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

		if ( is_tax( 'dc_vendor_shop' ) ) {
			echo '<meta name="description" content="Compra directamente los productos de este productor y conoce su historia en El Mercado de Origen.">' . "\n";
		}
		?>
		<style id="elmercado-vendor-home-verification-two">
			/* El logo se integra dentro del banner; la antigua cabecera WCFM desaparece por completo. */
			body.wcfmmp-store-page #wcfm_store_header {
				display: none !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-vendor-logo {
				position: absolute !important;
				z-index: 5 !important;
				bottom: clamp(1.2rem, 3vw, 2.4rem) !important;
				left: clamp(1.2rem, 4vw, 3rem) !important;
				display: block !important;
				width: clamp(108px, 11vw, 150px) !important;
				height: clamp(108px, 11vw, 150px) !important;
				margin: 0 !important;
				padding: 8px !important;
				border-radius: 50% !important;
				background: #fffdf8 !important;
				box-shadow: 0 16px 42px rgba(10, 35, 27, 0.3) !important;
				overflow: hidden !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-vendor-logo a,
			body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-vendor-logo img {
				display: block !important;
				width: 100% !important;
				height: 100% !important;
				margin: 0 !important;
				border-radius: 50% !important;
				object-fit: contain !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_text {
				left: clamp(11rem, 20vw, 17rem) !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .body_area {
				margin-top: clamp(2rem, 4vw, 3.5rem) !important;
			}

			/* Contraste explícito en todas las tarjetas claras del bloque de propuesta de valor. */
			body.elmercado-child-theme .emo-origin-distance-section,
			body.elmercado-child-theme .emo-origin-distance-section * {
				color: #173f32 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-section .emo-origin-distance-card,
			body.elmercado-child-theme .emo-origin-distance-section .emo-origin-distance-card * {
				color: #fffdf8 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-section .emo-origin-distance-card p {
				opacity: 0.9 !important;
			}

			@media (max-width: 640px) {
				body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-vendor-logo {
					bottom: 1rem !important;
					left: 50% !important;
					width: 104px !important;
					height: 104px !important;
					transform: translateX(-50%) !important;
				}

				body.wcfmmp-store-page #wcfmmp-store .banner_text {
					top: 34% !important;
					left: 1.25rem !important;
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
		<script id="elmercado-vendor-home-dom-verification">
		(() => {
			const store = document.querySelector('#wcfmmp-store');
			if (store) {
				const banner = store.querySelector('.banner_area');
				const logo = store.querySelector('#wcfm_store_header .logo_area');
				if (banner && logo && !banner.contains(logo)) {
					logo.classList.add('emo-vendor-logo');
					banner.appendChild(logo);
				}
			}

			const card = document.querySelector('.emo-origin-distance-card');
			if (!card) return;
			let section = card.closest('section');
			if (!section) {
				section = card.parentElement;
				while (section && section.parentElement && section.getBoundingClientRect().width < 700) {
					section = section.parentElement;
				}
			}
			if (section) section.classList.add('emo-origin-distance-section');
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
