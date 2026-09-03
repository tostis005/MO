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

	add_action(
		'wp_head',
		static function (): void {
			?>
			<style id="elmercado-blog-inline-commerce-010270">
				body.single-post main#primary.emo-article-page > article.emo-article ~ *:not(.emo-related-reading) {
					display: none !important;
				}

				body.single-post .emo-inline-commerce[hidden] {
					display: none !important;
				}

				body.single-post .emo-inline-commerce {
					box-sizing: border-box;
				}

				body.single-post .emo-inline-special-anchor > * {
					width: 100% !important;
					max-width: 100% !important;
					margin-left: 0 !important;
					margin-right: 0 !important;
					box-sizing: border-box !important;
				}

				/*
				 * Mismo ancho de lectura que los párrafos en escritorio: 900 px.
				 * Recupera el fondo suave de la versión anterior sin volver a convertir
				 * el opt-in en una tarjeta visualmente pesada.
				 */
				body.single-post .emo-inline-newsletter {
					display: block;
					width: min(100%, 900px) !important;
					max-width: 900px !important;
					min-width: 0 !important;
					margin: 28px auto !important;
					padding: 20px 22px !important;
					background: #f6f1e8 !important;
					border: 1px solid rgba(13, 33, 27, 0.11) !important;
					border-radius: 14px !important;
					box-shadow: none !important;
					box-sizing: border-box !important;
				}

				body.single-post .emo-inline-newsletter__intro,
				body.single-post .emo-inline-newsletter__form,
				body.single-post .emo-inline-newsletter__row {
					width: 100% !important;
					max-width: none !important;
					box-sizing: border-box !important;
				}

				body.single-post .emo-inline-newsletter__intro {
					display: block;
					margin: 0 0 13px !important;
				}

				body.single-post .emo-inline-newsletter__eyebrow,
				body.single-post .emo-inline-newsletter__body {
					display: none !important;
				}

				body.single-post .emo-inline-newsletter h3 {
					margin: 0 !important;
					font-size: clamp(21px, 2vw, 25px) !important;
					line-height: 1.2 !important;
					font-weight: 700 !important;
					letter-spacing: -0.02em !important;
				}

				body.single-post .emo-inline-newsletter__row {
					display: flex;
					gap: 8px;
					align-items: stretch;
				}

				body.single-post .emo-inline-newsletter__row input[type="email"] {
					width: 100% !important;
					min-width: 0;
					flex: 1 1 auto;
					min-height: 42px;
					margin: 0 !important;
					padding: 9px 12px !important;
					background: #fff !important;
					border: 1px solid rgba(13, 33, 27, 0.2) !important;
					border-radius: 8px !important;
					box-sizing: border-box !important;
					font-size: 13px !important;
				}

				body.single-post .emo-inline-newsletter__row button {
					min-height: 42px;
					margin: 0 !important;
					padding: 9px 17px !important;
					border-radius: 8px !important;
					white-space: nowrap;
					font-size: 13px !important;
				}

				body.single-post .emo-inline-newsletter__consent {
					display: flex;
					gap: 7px;
					align-items: flex-start;
					margin-top: 8px;
					font-size: 10.5px;
					line-height: 1.35;
					opacity: .76;
				}

				body.single-post .emo-inline-newsletter__consent input {
					margin-top: 2px;
					flex: 0 0 auto;
				}

				body.single-post .emo-inline-newsletter__notice {
					margin: 0 0 10px !important;
					padding: 8px 10px !important;
					background: rgba(255, 255, 255, .68) !important;
					border-radius: 7px !important;
					font-size: 12px !important;
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

				/* La raíz de relacionados ya sale como div desde PHP, nunca como section. */
				html body.single-post main#primary.emo-article-page .emo-related-reading {
					width: 100% !important;
					max-width: 100% !important;
					margin-left: 0 !important;
					margin-right: 0 !important;
					box-sizing: border-box !important;
				}

				@media (min-width: 1101px) {
					html body.single-post main#primary.emo-article-page > .emo-related-reading > .emo-shell {
						width: min(100%, 800px) !important;
						max-width: 800px !important;
						margin-left: auto !important;
						margin-right: auto !important;
					}
				}

				@media (max-width: 640px) {
					body.single-post .emo-inline-newsletter {
						width: 100% !important;
						max-width: 100% !important;
						margin: 24px auto !important;
						padding: 18px 16px !important;
					}

					body.single-post .emo-inline-newsletter h3 {
						font-size: 20px !important;
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

	/*
	 * Sólo mueve el especial existente al cuerpo del artículo. La resolución
	 * geográfica y la visibilidad del newsletter las gestiona el fallback del
	 * propio módulo, que nace visible y sólo se oculta ante un can_buy=false.
	 */
	add_action(
		'wp_footer',
		static function (): void {
			?>
			<script id="elmercado-blog-inline-commerce-runtime-010270">
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

				if (newsletter && /(?:^|[?&])emo_newsletter=/.test(window.location.search)) {
					window.setTimeout(function () {
						newsletter.scrollIntoView({ block: 'center' });
					}, 0);
				}
			}());
			</script>
			<?php
		},
		PHP_INT_MAX
	);
}

/*
 * single.php sigue siendo la plantilla base, pero su HTML se normaliza en el
 * servidor antes de enviarlo al navegador. De este modo relacionados nunca
 * llega al cliente como <section> y no puede heredar ninguna regla global de
 * .emo-article-content > :is(..., section).
 */
ob_start();
require __DIR__ . '/single.php';
$rendered_post = (string) ob_get_clean();

$rendered_post = preg_replace(
	'~<section\b([^>]*\bclass=(?:"[^"]*\bemo-related-reading\b[^"]*"|\'[^\']*\bemo-related-reading\b[^\']*\')[^>]*)>(.*?)</section>~isu',
	'<div$1 data-emo-related-root="1">$2</div>',
	$rendered_post,
	1
);

$is_english       = 0 === strpos( strtolower( (string) determine_locale() ), 'en' );
$newsletter_title = $is_english
	? 'Stay up to date with all our latest news'
	: 'Mantente informado de todas nuestras novedades';

$rendered_post = preg_replace_callback(
	'~(<aside\b[^>]*\bid="emo-newsletter"[^>]*>.*?<h3>).*?(</h3>)~isu',
	static function ( array $matches ) use ( $newsletter_title ): string {
		return $matches[1] . esc_html( $newsletter_title ) . $matches[2];
	},
	$rendered_post,
	1
);

$rendered_post = preg_replace(
	'~<span\b[^>]*class="emo-inline-newsletter__eyebrow"[^>]*>.*?</span>~isu',
	'',
	$rendered_post,
	1
);
$rendered_post = preg_replace(
	'~<p\b[^>]*class="emo-inline-newsletter__body"[^>]*>.*?</p>~isu',
	'',
	$rendered_post,
	1
);

echo $rendered_post; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
