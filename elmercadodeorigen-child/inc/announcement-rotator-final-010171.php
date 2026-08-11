<?php
/**
 * Rotación estable de la barra superior 0.10.173.
 *
 * Usa un único nodo visible para mostrar, en orden, el contenido de los tres
 * mensajes originales. De este modo ninguna regla heredada que oculte el
 * segundo o tercer span puede dejar la barra vacía.
 *
 * No usa requestAnimationFrame, sincronización de resize ni observers.
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
		<style id="elmercado-announcement-rotator-final-010173">
			body.elmercado-child-theme .emo-announcement__inner > span {
				display: none !important;
				opacity: 0 !important;
				visibility: hidden !important;
				transform: none !important;
				animation: none !important;
				transition: none !important;
			}

			body.elmercado-child-theme .emo-announcement__inner > span:first-child {
				display: flex !important;
				opacity: 1 !important;
				visibility: visible !important;
				transform: none !important;
				animation: none !important;
				transition: none !important;
				z-index: 3 !important;
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
		<script id="elmercado-announcement-rotator-final-010173">
		(() => {
			const initAnnouncementRotator = () => {
				const inner = document.querySelector('.emo-announcement__inner');
				if (!inner || inner.dataset.emoRotator === 'single-node-010173') return;

				const items = Array.from(inner.children)
					.filter((node) => node.tagName === 'SPAN')
					.slice(0, 3);

				if (items.length < 2) return;

				const slides = items.map((item) => item.innerHTML);
				const host = items[0];
				let index = 0;

				inner.dataset.emoRotator = 'single-node-010173';
				inner.classList.add('emo-announcement-js');
				host.setAttribute('aria-live', 'polite');
				host.setAttribute('aria-atomic', 'true');

				items.slice(1).forEach((item) => {
					item.setAttribute('aria-hidden', 'true');
				});

				const render = () => {
					host.innerHTML = slides[index];
					host.dataset.emoAnnouncementIndex = String(index);
					host.setAttribute('aria-hidden', 'false');
				};

				render();
				window.setInterval(() => {
					index = (index + 1) % slides.length;
					render();
				}, 3600);
			};

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', initAnnouncementRotator, { once: true });
			} else {
				initAnnouncementRotator();
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
