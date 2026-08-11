<?php
/**
 * Rotación determinista de la barra superior 0.10.171.
 *
 * Sustituye la rotación CSS por un único estado activo controlado con un
 * temporizador sencillo. Evita intervalos vacíos y garantiza el ciclo
 * 1 → 2 → 3 → 1 sin requestAnimationFrame, resize sync ni observers.
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
		<style id="elmercado-announcement-rotator-final-010171">
			body.elmercado-child-theme .emo-announcement__inner > span {
				opacity: 0 !important;
				visibility: hidden !important;
				transform: translateY(105%) !important;
				animation: none !important;
				transition:
					opacity 280ms ease,
					transform 340ms ease,
					visibility 0s linear 340ms !important;
				z-index: 1;
			}

			body.elmercado-child-theme .emo-announcement__inner:not(.emo-announcement-js) > span:first-child,
			body.elmercado-child-theme .emo-announcement__inner.emo-announcement-js > span.emo-announcement-current {
				opacity: 1 !important;
				visibility: visible !important;
				transform: translateY(0) !important;
				transition-delay: 0s !important;
				z-index: 3;
			}

			body.elmercado-child-theme .emo-announcement__inner.emo-announcement-js > span.emo-announcement-leaving {
				opacity: 0 !important;
				visibility: visible !important;
				transform: translateY(-105%) !important;
				transition-delay: 0s !important;
				z-index: 2;
			}

			body.elmercado-child-theme .emo-announcement__inner.emo-announcement-reduced > span {
				transform: none !important;
				transition: none !important;
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
		<script id="elmercado-announcement-rotator-final-010171">
		(() => {
			const initAnnouncementRotator = () => {
				const inner = document.querySelector('.emo-announcement__inner');
				if (!inner || inner.dataset.emoRotator === '1') return;

				const items = Array.from(inner.children).filter((node) => node.tagName === 'SPAN');
				if (items.length < 2) return;

				inner.dataset.emoRotator = '1';
				inner.classList.add('emo-announcement-js');

				const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
				if (reducedMotion) inner.classList.add('emo-announcement-reduced');

				let index = 0;

				items.forEach((item, itemIndex) => {
					item.classList.toggle('emo-announcement-current', itemIndex === 0);
					item.classList.remove('emo-announcement-leaving');
					item.setAttribute('aria-hidden', itemIndex === 0 ? 'false' : 'true');
				});

				const showNext = () => {
					const current = items[index];
					const nextIndex = (index + 1) % items.length;
					const next = items[nextIndex];

					current.classList.remove('emo-announcement-current');
					current.classList.add('emo-announcement-leaving');
					current.setAttribute('aria-hidden', 'true');

					next.classList.remove('emo-announcement-leaving');
					next.classList.add('emo-announcement-current');
					next.setAttribute('aria-hidden', 'false');

					window.setTimeout(() => {
						current.classList.remove('emo-announcement-leaving');
					}, reducedMotion ? 0 : 380);

					index = nextIndex;
				};

				window.setInterval(showNext, 3600);
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
