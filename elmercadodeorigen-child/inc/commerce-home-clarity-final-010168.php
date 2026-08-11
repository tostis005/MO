<?php
/**
 * Claridad de precio variable, encuadre del hero y rotación de la barra superior 0.10.168.
 *
 * - En productos variables con YITH WAPO conserva el rango inicial y, tras
 *   seleccionar una variación, deja visible un único precio total actualizado.
 * - En la Home muestra completos los jamones y paletas del mosaico del hero.
 * - Convierte los tres mensajes de la barra superior en una rotación vertical
 *   ligera, sin observers, resize sync ni requestAnimationFrame.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'yith_wapo_table_order_total_label',
	static function ( $label ) {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return $label;
		}

		return __( 'Precio total', 'elmercadodeorigen' );
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-commerce-home-clarity-final-010168">
			/* Barra superior: un único mensaje visible, rotando verticalmente. */
			body.elmercado-child-theme .emo-announcement {
				--emo-announcement-height: 38px;
				overflow: hidden !important;
			}

			body.elmercado-child-theme .emo-announcement__inner {
				position: relative !important;
				display: block !important;
				height: var(--emo-announcement-height) !important;
				min-height: var(--emo-announcement-height) !important;
				overflow: hidden !important;
			}

			body.elmercado-child-theme .emo-announcement__inner > span {
				position: absolute !important;
				inset: 0 !important;
				display: flex !important;
				width: 100% !important;
				height: var(--emo-announcement-height) !important;
				min-width: 0 !important;
				align-items: center !important;
				justify-content: center !important;
				padding: 0 1rem !important;
				border: 0 !important;
				opacity: 0;
				transform: translateY(105%);
				animation: emo-announcement-vertical-010168 12s ease-in-out infinite;
			}

			body.elmercado-child-theme .emo-announcement__inner > span:nth-child(1) {
				animation-delay: -0.45s;
			}
			body.elmercado-child-theme .emo-announcement__inner > span:nth-child(2) {
				animation-delay: 3.55s;
			}
			body.elmercado-child-theme .emo-announcement__inner > span:nth-child(3) {
				animation-delay: 7.55s;
			}

			body.elmercado-child-theme .emo-announcement:hover .emo-announcement__inner > span {
				animation-play-state: paused;
			}

			@keyframes emo-announcement-vertical-010168 {
				0% {
					opacity: 0;
					transform: translateY(105%);
				}
				4% {
					opacity: 1;
					transform: translateY(0);
				}
				28% {
					opacity: 1;
					transform: translateY(0);
				}
				33.333% {
					opacity: 0;
					transform: translateY(-105%);
				}
				100% {
					opacity: 0;
					transform: translateY(-105%);
				}
			}

			/* Hero: los productos altos se muestran completos dentro de la misma tarjeta. */
			body.home.elmercado-child-theme .emo-hero-card:has(img[alt*="jamón" i]) figure,
			body.home.elmercado-child-theme .emo-hero-card:has(img[alt*="paleta" i]) figure {
				background: #f7f3ea !important;
			}

			body.home.elmercado-child-theme .emo-hero-card img[alt*="jamón" i],
			body.home.elmercado-child-theme .emo-hero-card img[alt*="paleta" i] {
				object-fit: contain !important;
				object-position: center center !important;
				background: #f7f3ea !important;
			}

			body.home.elmercado-child-theme .emo-hero-card:hover img[alt*="jamón" i],
			body.home.elmercado-child-theme .emo-hero-card:hover img[alt*="paleta" i] {
				transform: scale(1.02) !important;
			}

			/* Producto variable + YITH WAPO: una sola referencia de precio tras elegir tamaño. */
			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified .woocommerce-variation-price {
				display: none !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified.emo-final-price-active > p.price,
			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified.emo-final-price-active > span.price {
				display: none !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-price-table {
				display: none !important;
				width: 100% !important;
				margin: 0.9rem 0 0 !important;
				padding: 0 !important;
				background: transparent !important;
				border: 0 !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified.emo-final-price-active #wapo-total-price-table {
				display: block !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-price-table table {
				width: 100% !important;
				margin: 0 !important;
				background: transparent !important;
				border: 0 !important;
				box-shadow: none !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-product-price,
			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-options {
				display: none !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order {
				display: flex !important;
				width: 100% !important;
				align-items: baseline !important;
				justify-content: space-between !important;
				gap: 1rem !important;
				padding: 0.9rem 0 0 !important;
				background: transparent !important;
				border: 0 !important;
				border-top: 1px solid var(--emo-line) !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order > th,
			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order > td {
				display: block !important;
				width: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				background: transparent !important;
				border: 0 !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order > th {
				color: var(--emo-muted) !important;
				font-size: 0.78rem !important;
				font-weight: 800 !important;
				letter-spacing: 0.06em !important;
				line-height: 1.25 !important;
				text-transform: uppercase !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order-price,
			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order-price .amount,
			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order-price .price {
				color: var(--emo-forest-800) !important;
				font-size: clamp(1.35rem, 2.2vw, 1.72rem) !important;
				font-weight: 850 !important;
				line-height: 1.15 !important;
				text-align: right !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order-price del {
				display: none !important;
			}

			body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order-price ins {
				background: transparent !important;
				color: inherit !important;
				font-size: inherit !important;
				font-weight: inherit !important;
				text-decoration: none !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme .emo-announcement {
					--emo-announcement-height: 34px;
				}

				body.elmercado-child-theme .emo-announcement__inner > span {
					font-size: 0.68rem !important;
					white-space: nowrap;
				}

				body.single-product.elmercado-child-theme .summary.emo-wapo-price-unified #wapo-total-order {
					gap: 0.75rem !important;
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

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<script id="elmercado-unified-product-price-010168">
		jQuery(function ($) {
			'use strict';

			const $form = $('form.variations_form').first();
			const $table = $('#wapo-total-price-table').first();
			const $summary = $form.closest('.summary');

			if (!$form.length || !$table.length || !$summary.length) {
				return;
			}

			$summary.addClass('emo-wapo-price-unified');

			const syncVariationState = function () {
				const variationId = parseInt($form.find('input.variation_id').val() || '0', 10);
				$summary.toggleClass('emo-final-price-active', variationId > 0);
			};

			$form.on('found_variation', function () {
				$summary.addClass('emo-final-price-active');
			});

			$form.on('reset_data hide_variation', function () {
				$summary.removeClass('emo-final-price-active');
			});

			syncVariationState();
		});
		</script>
		<?php
	},
	PHP_INT_MAX
);
