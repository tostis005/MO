<?php
/**
 * Altura estable de títulos de producto y controles del carrusel de portada.
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
		<style id="elmercado-product-card-carousel-finish">
			/* Todas las tarjetas reservan exactamente dos líneas para el nombre. */
			body.elmercado-child-theme ul.products li.product .woocommerce-loop-product__title,
			body.elmercado-child-theme ul.products li.product .product-title,
			body.elmercado-child-theme ul.products li.product h2,
			body.elmercado-child-theme ul.products li.product h3 {
				display: -webkit-box !important;
				min-height: 2.8em !important;
				max-height: 2.8em !important;
				margin-block: 0 !important;
				overflow: hidden !important;
				-webkit-box-orient: vertical !important;
				-webkit-line-clamp: 2 !important;
				line-clamp: 2 !important;
				line-height: 1.4 !important;
				text-overflow: ellipsis !important;
			}

			body.elmercado-premium-home .emo-featured-products .emo-carousel-controls {
				display: none;
			}

			@media (max-width: 991px) {
				body.elmercado-premium-home .emo-featured-products .emo-shell {
					position: relative;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-controls {
					display: flex;
					align-items: center;
					justify-content: flex-end;
					gap: 8px;
					margin: -4px 0 10px;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control {
					display: grid;
					width: 42px;
					height: 42px;
					min-width: 42px;
					padding: 0;
					place-items: center;
					border: 1px solid #cfdcd4;
					border-radius: 50%;
					background: #fffdf8;
					color: #173f32;
					box-shadow: 0 8px 22px rgba(23, 63, 50, 0.10);
					cursor: pointer;
					transition: transform 160ms ease, background-color 160ms ease, opacity 160ms ease;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control:hover,
				body.elmercado-premium-home .emo-featured-products .emo-carousel-control:focus-visible {
					background: #e8f2ec;
					transform: translateY(-1px);
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control:focus-visible {
					outline: 2px solid #2f7d5d;
					outline-offset: 2px;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control[disabled] {
					opacity: 0.35;
					cursor: default;
					transform: none;
				}

				body.elmercado-premium-home .emo-featured-products .emo-carousel-control svg {
					width: 20px;
					height: 20px;
					fill: none;
					stroke: currentColor;
					stroke-linecap: round;
					stroke-linejoin: round;
					stroke-width: 2;
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
		if ( is_admin() || ! is_front_page() ) {
			return;
		}
		?>
		<script id="elmercado-home-carousel-controls">
		(() => {
			'use strict';

			const section = document.querySelector('.emo-featured-products');
			const track = section?.querySelector('ul.products');
			const heading = section?.querySelector('.emo-section-heading');
			if (!section || !track || !heading || section.querySelector('.emo-carousel-controls')) return;

			const controls = document.createElement('div');
			controls.className = 'emo-carousel-controls';
			controls.setAttribute('aria-label', 'Navegación de productos destacados');

			const makeButton = (direction, label, path) => {
				const button = document.createElement('button');
				button.type = 'button';
				button.className = `emo-carousel-control emo-carousel-control--${direction}`;
				button.setAttribute('aria-label', label);
				button.innerHTML = `<svg aria-hidden="true" viewBox="0 0 24 24"><path d="${path}"/></svg>`;
				return button;
			};

			const previous = makeButton('previous', 'Ver productos anteriores', 'M15 18l-6-6 6-6');
			const next = makeButton('next', 'Ver productos siguientes', 'M9 6l6 6-6 6');
			controls.append(previous, next);
			heading.insertAdjacentElement('afterend', controls);

			const cardStep = () => {
				const card = track.querySelector('li.product');
				if (!card) return Math.max(track.clientWidth * 0.82, 280);
				const gap = parseFloat(getComputedStyle(track).columnGap || getComputedStyle(track).gap || '0');
				return card.getBoundingClientRect().width + gap;
			};

			const update = () => {
				const max = Math.max(0, track.scrollWidth - track.clientWidth);
				const active = max > 4 && matchMedia('(max-width: 991px)').matches;
				controls.hidden = !active;
				previous.disabled = !active || track.scrollLeft <= 4;
				next.disabled = !active || track.scrollLeft >= max - 4;
			};

			previous.addEventListener('click', () => track.scrollBy({ left: -cardStep(), behavior: 'smooth' }));
			next.addEventListener('click', () => track.scrollBy({ left: cardStep(), behavior: 'smooth' }));
			track.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });
			window.addEventListener('resize', update, { passive: true });
			new ResizeObserver(update).observe(track);
			update();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
