<?php
/**
 * Correcciones de feedback en ficha y Home 0.10.169.
 *
 * - Compacta la separación vertical entre los selectores de variación y el
 *   primer bloque de formato de YITH WAPO.
 * - Corrige el ciclo vertical de la barra superior para que los tres mensajes
 *   roten de forma continua, sin quedarse en un fotograma vacío.
 * - Fuerza el encuadre completo de jamones y paletas dentro de las tarjetas del
 *   hero sin aumentar el tamaño del mosaico.
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
		<style id="elmercado-user-feedback-home-product-010169">
			/* Barra superior: ciclo 1 → 2 → 3 continuo, sin pausa en hover. */
			body.elmercado-child-theme .emo-announcement__inner > span {
				opacity: 0 !important;
				transform: translateY(105%);
				animation-name: emo-announcement-vertical-010169 !important;
				animation-duration: 12s !important;
				animation-timing-function: ease-in-out !important;
				animation-iteration-count: infinite !important;
				animation-fill-mode: both !important;
				animation-play-state: running !important;
			}

			body.elmercado-child-theme .emo-announcement__inner > span:nth-child(1) {
				animation-delay: 0s !important;
			}

			body.elmercado-child-theme .emo-announcement__inner > span:nth-child(2) {
				animation-delay: -8s !important;
			}

			body.elmercado-child-theme .emo-announcement__inner > span:nth-child(3) {
				animation-delay: -4s !important;
			}

			body.elmercado-child-theme .emo-announcement:hover .emo-announcement__inner > span {
				animation-play-state: running !important;
			}

			@keyframes emo-announcement-vertical-010169 {
				0%, 27% {
					opacity: 1;
					transform: translateY(0);
				}
				31% {
					opacity: 0;
					transform: translateY(-105%);
				}
				31.01%, 96% {
					opacity: 0;
					transform: translateY(105%);
				}
				100% {
					opacity: 1;
					transform: translateY(0);
				}
			}

			/* Hero: el producto alto ocupa toda la caja disponible sin recorte. */
			body.home.elmercado-child-theme .emo-hero-card[href*="/producto/jamon-"] figure,
			body.home.elmercado-child-theme .emo-hero-card[href*="/producto/paleta-"] figure {
				display: block !important;
				background: #f7f3ea !important;
			}

			body.home.elmercado-child-theme .emo-hero-card[href*="/producto/jamon-"] img,
			body.home.elmercado-child-theme .emo-hero-card[href*="/producto/paleta-"] img {
				display: block !important;
				width: 100% !important;
				height: 100% !important;
				min-width: 0 !important;
				min-height: 0 !important;
				max-width: 100% !important;
				max-height: 100% !important;
				padding: 0.2rem !important;
				background: #f7f3ea !important;
				box-sizing: border-box !important;
				object-fit: contain !important;
				object-position: center center !important;
				transform: none !important;
			}

			body.home.elmercado-child-theme .emo-hero-card[href*="/producto/jamon-"]:hover img,
			body.home.elmercado-child-theme .emo-hero-card[href*="/producto/paleta-"]:hover img {
				transform: scale(1.015) !important;
			}

			/* Ficha variable: peso y formato quedan visualmente relacionados. */
			body.single-product.elmercado-child-theme form.variations_form table.variations {
				margin-top: 0 !important;
				margin-bottom: 0.35rem !important;
				border-collapse: separate !important;
				border-spacing: 0 !important;
			}

			body.single-product.elmercado-child-theme form.variations_form table.variations tr {
				margin-top: 0 !important;
				margin-bottom: 0.35rem !important;
			}

			body.single-product.elmercado-child-theme form.variations_form table.variations tr:last-child {
				margin-bottom: 0 !important;
			}

			body.single-product.elmercado-child-theme form.variations_form table.variations th,
			body.single-product.elmercado-child-theme form.variations_form table.variations td {
				padding-top: 0.2rem !important;
				padding-bottom: 0.2rem !important;
				vertical-align: middle !important;
			}

			body.single-product.elmercado-child-theme form.variations_form table.variations select {
				margin-top: 0 !important;
				margin-bottom: 0 !important;
			}

			body.single-product.elmercado-child-theme form.variations_form .reset_variations {
				display: inline-block;
				margin-top: 0.15rem !important;
				margin-bottom: 0 !important;
			}

			body.single-product.elmercado-child-theme form.variations_form #yith-wapo-container,
			body.single-product.elmercado-child-theme form.variations_form .yith-wapo-container {
				margin-top: 0.25rem !important;
				padding-top: 0 !important;
			}

			body.single-product.elmercado-child-theme form.variations_form #yith-wapo-container .yith-wapo-block:first-child,
			body.single-product.elmercado-child-theme form.variations_form .yith-wapo-container .yith-wapo-block:first-child,
			body.single-product.elmercado-child-theme form.variations_form #yith-wapo-container .yith-wapo-addon:first-child,
			body.single-product.elmercado-child-theme form.variations_form .yith-wapo-container .yith-wapo-addon:first-child {
				margin-top: 0 !important;
				padding-top: 0 !important;
			}

			body.single-product.elmercado-child-theme form.variations_form #yith-wapo-container .yith-wapo-addon:first-child .wapo-addon-title,
			body.single-product.elmercado-child-theme form.variations_form .yith-wapo-container .yith-wapo-addon:first-child .wapo-addon-title,
			body.single-product.elmercado-child-theme form.variations_form #yith-wapo-container .yith-wapo-addon:first-child h3,
			body.single-product.elmercado-child-theme form.variations_form .yith-wapo-container .yith-wapo-addon:first-child h3 {
				margin-top: 0.15rem !important;
				margin-bottom: 0.35rem !important;
			}

			@media (max-width: 767px) {
				body.single-product.elmercado-child-theme form.variations_form table.variations {
					margin-bottom: 0.25rem !important;
				}

				body.single-product.elmercado-child-theme form.variations_form table.variations tr {
					margin-bottom: 0.25rem !important;
				}

				body.single-product.elmercado-child-theme form.variations_form #yith-wapo-container,
				body.single-product.elmercado-child-theme form.variations_form .yith-wapo-container {
					margin-top: 0.15rem !important;
				}
			}

			@media (prefers-reduced-motion: reduce) {
				body.elmercado-child-theme .emo-announcement__inner > span {
					display: none !important;
					animation: none !important;
					opacity: 1 !important;
					transform: none !important;
				}

				body.elmercado-child-theme .emo-announcement__inner > span:first-child {
					display: flex !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
