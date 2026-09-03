<?php
/**
 * Puente de entrada para posts: activa los bloques editoriales comerciales y
 * conserva intacta la plantilla single.php existente.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$inline_commerce_module = __DIR__ . '/inc/blog-inline-commerce-010268.php';
if ( is_readable( $inline_commerce_module ) ) {
	require_once $inline_commerce_module;

	if ( function_exists( 'elmercado_blog_maybe_handle_subscription' ) ) {
		elmercado_blog_maybe_handle_subscription();
	}

	if ( function_exists( 'elmercado_blog_inject_inline_commercial_blocks' ) ) {
		add_filter( 'the_content', 'elmercado_blog_inject_inline_commercial_blocks', 35 );
	}

	/*
	 * Oculta desde el primer pintado el especial que single.php deja tras el
	 * articulo. En el footer se mueve al ancla interior antes de poder mostrarse.
	 */
	add_action(
		'wp_head',
		static function (): void {
			?>
			<style id="elmercado-blog-inline-commerce-010268">
				body.single-post main#primary.emo-article-page > article.emo-article ~ *:not(.emo-related-reading) {
					display: none !important;
				}

				body.single-post .emo-inline-commerce[hidden] {
					display: none !important;
				}

				body.single-post .emo-inline-commerce {
					box-sizing: border-box;
					margin: 36px 0 !important;
				}

				body.single-post .emo-inline-special-anchor > * {
					width: 100% !important;
					max-width: 100% !important;
					margin-left: 0 !important;
					margin-right: 0 !important;
					box-sizing: border-box !important;
				}

				body.single-post .emo-inline-newsletter {
					padding: clamp(24px, 4vw, 34px) !important;
					background: #f6f1e8 !important;
					border: 1px solid rgba(13, 33, 27, 0.12) !important;
					border-radius: 20px !important;
				}

				body.single-post .emo-inline-newsletter__eyebrow {
					display: block;
					margin-bottom: 8px;
					font-size: 12px;
					font-weight: 700;
					letter-spacing: .09em;
					text-transform: uppercase;
				}

				body.single-post .emo-inline-newsletter h3 {
					margin: 0 0 10px !important;
					font-size: clamp(24px, 3.2vw, 32px) !important;
					line-height: 1.16 !important;
				}

				body.single-post .emo-inline-newsletter__body {
					margin: 0 0 20px !important;
				}

				body.single-post .emo-inline-newsletter__row {
					display: flex;
					gap: 10px;
					align-items: stretch;
				}

				body.single-post .emo-inline-newsletter__row input[type="email"] {
					min-width: 0;
					flex: 1 1 auto;
					min-height: 48px;
					margin: 0 !important;
					padding: 11px 14px !important;
					background: #fff !important;
					border: 1px solid rgba(13, 33, 27, 0.2) !important;
					border-radius: 10px !important;
				}

				body.single-post .emo-inline-newsletter__row button {
					min-height: 48px;
					margin: 0 !important;
					padding: 11px 20px !important;
					border-radius: 10px !important;
					white-space: nowrap;
				}

				body.single-post .emo-inline-newsletter__consent {
					display: flex;
					gap: 9px;
					align-items: flex-start;
					margin-top: 13px;
					font-size: 12px;
					line-height: 1.45;
				}

				body.single-post .emo-inline-newsletter__consent input {
					margin-top: 3px;
					flex: 0 0 auto;
				}

				body.single-post .emo-inline-newsletter__notice {
					margin: 0 0 16px !important;
					padding: 10px 12px !important;
					background: rgba(255, 255, 255, .72) !important;
					border-radius: 9px !important;
					font-weight: 600;
				}

				body.single-post .emo-inline-newsletter__honeypot {
					position: absolute !important;
					left: -10000px !important;
					width: 1px !important;
					height: 1px !important;
					overflow: hidden !important;
				}

				body.single-post .emo-inline-newsletter-anchor:empty {
					display: none;
				}

				@media (max-width: 640px) {
					body.single-post .emo-inline-commerce {
						margin: 28px 0 !important;
					}

					body.single-post .emo-inline-newsletter__row {
						flex-direction: column;
					}

					body.single-post .emo-inline-newsletter__row button {
						width: 100%;
					}
				}
			</style>
			<?php
		},
		1
	);

	add_action(
		'wp_footer',
		static function (): void {
			?>
			<script id="elmercado-blog-inline-commerce-runtime-010268">
			(function () {
				'use strict';

				var main = document.querySelector('main#primary.emo-article-page');
				var specialAnchor = document.querySelector('[data-emo-special-anchor]');
				var newsletterAnchor = document.querySelector('[data-emo-newsletter-anchor]');
				var newsletter = document.querySelector('[data-emo-commerce="newsletter"]');

				if (!main || !specialAnchor) {
					return;
				}

				var article = null;
				var related = null;
				Array.prototype.forEach.call(main.children, function (child) {
					if (!article && child.matches && child.matches('article.emo-article')) {
						article = child;
					}
					if (!related && child.matches && child.matches('.emo-related-reading')) {
						related = child;
					}
				});

				var moved = 0;
				if (article) {
					var sibling = article.nextElementSibling;
					while (sibling && sibling !== related) {
						var next = sibling.nextElementSibling;
						specialAnchor.appendChild(sibling);
						moved += 1;
						sibling = next;
					}
				}

				if (moved > 0 && newsletter && newsletterAnchor) {
					newsletterAnchor.appendChild(newsletter);
				}

				specialAnchor.setAttribute('data-emo-has-special', moved > 0 ? '1' : '0');

				function setVisible(element, visible) {
					if (!element) {
						return;
					}
					element.hidden = !visible;
					element.setAttribute('aria-hidden', visible ? 'false' : 'true');
				}

				function applyEligibility(canBuy) {
					setVisible(specialAnchor, canBuy && moved > 0);
					setVisible(newsletter, canBuy && !!newsletter);

					if (canBuy && newsletter && /(?:^|[?&])emo_newsletter=/.test(window.location.search)) {
						window.setTimeout(function () {
							newsletter.scrollIntoView({ block: 'center' });
						}, 0);
					}
				}

				function resolveFromGeoDebug() {
					var geo = window.ElMercadoAdsenseGeoDebug;
					if (!geo || typeof geo.canBuy !== 'boolean') {
						return false;
					}
					applyEligibility(geo.canBuy === true);
					return true;
				}

				if (resolveFromGeoDebug()) {
					return;
				}

				var attempts = 0;
				var timer = window.setInterval(function () {
					attempts += 1;
					if (resolveFromGeoDebug() || attempts >= 60) {
						window.clearInterval(timer);
					}
				}, 100);
			}());
			</script>
			<?php
		},
		PHP_INT_MAX
	);
}

require __DIR__ . '/single.php';
