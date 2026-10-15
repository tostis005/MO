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

/*
 * AdSense no debe cargarse en el primer pintado. El controlador geografico
 * insertara su script solo si la visita pertenece a un pais no vendible.
 * Esto evita inicializar el CMP publicitario de Google donde no hay anuncios.
 */
remove_action( 'wp_head', 'elmercado_adsense_output_paused_head_code', 2 );

$inline_commerce_module = __DIR__ . '/inc/blog-inline-commerce-010268.php';
if ( is_readable( $inline_commerce_module ) ) {
	require_once $inline_commerce_module;

	if ( function_exists( 'elmercado_blog_maybe_handle_subscription' ) ) {
		elmercado_blog_maybe_handle_subscription();
	}

	/*
	 * Los articulos historicos incluyen a veces <section> como hijo directo del
	 * cuerpo de lectura. Eso impide que sus h2, p y listas hereden las mismas
	 * reglas que el resto de hijos directos. Desempaquetamos solo esas secciones
	 * editoriales de primer nivel y preservamos wrappers funcionales de producto.
	 */
	if ( ! function_exists( 'elmercado_blog_flatten_top_level_sections_010280' ) ) {
		function elmercado_blog_flatten_top_level_sections_010280( string $content_html ): string {
			if ( ! is_singular( 'post' ) || false === stripos( $content_html, '<section' ) ) {
				return $content_html;
			}

			if ( ! class_exists( 'DOMDocument' ) || ! class_exists( 'DOMXPath' ) ) {
				return $content_html;
			}

			$dom      = new DOMDocument( '1.0', 'UTF-8' );
			$previous = libxml_use_internal_errors( true );
			$loaded   = $dom->loadHTML(
				'<?xml encoding="utf-8" ?><div id="emo-editorial-root-010280">' . $content_html . '</div>',
				LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
			);

			if ( ! $loaded ) {
				libxml_clear_errors();
				libxml_use_internal_errors( $previous );
				return $content_html;
			}

			$root = $dom->getElementById( 'emo-editorial-root-010280' );
			if ( ! $root instanceof DOMElement ) {
				libxml_clear_errors();
				libxml_use_internal_errors( $previous );
				return $content_html;
			}

			$children = array();
			foreach ( $root->childNodes as $child ) {
				$children[] = $child;
			}

			$xpath   = new DOMXPath( $dom );
			$changed = false;

			foreach ( $children as $child ) {
				if ( ! $child instanceof DOMElement || 'section' !== strtolower( $child->tagName ) ) {
					continue;
				}

				$classes = trim( (string) $child->getAttribute( 'class' ) );
				if (
					'' !== $classes
					&& preg_match(
						'/(?:^|\s)(?:woocommerce|products|wp-block-[^\s]+|wc-block-[^\s]+|emo-related-products[^\s]*|emo-inline-[^\s]+)(?:\s|$)/i',
						$classes
					)
				) {
					continue;
				}

				/* Conserva anclas existentes trasladando el id al primer encabezado. */
				$section_id = trim( (string) $child->getAttribute( 'id' ) );
				if ( '' !== $section_id ) {
					$heading = $xpath->query( './/*[self::h2 or self::h3 or self::h4 or self::h5 or self::h6]', $child );
					if ( $heading instanceof DOMNodeList && $heading->length > 0 ) {
						$target = $heading->item( 0 );
						if ( $target instanceof DOMElement && ! $target->hasAttribute( 'id' ) ) {
							$target->setAttribute( 'id', $section_id );
						}
					}
				}

				while ( $child->firstChild ) {
					$root->insertBefore( $child->firstChild, $child );
				}
				$root->removeChild( $child );
				$changed = true;
			}

			if ( ! $changed ) {
				libxml_clear_errors();
				libxml_use_internal_errors( $previous );
				return $content_html;
			}

			$flattened = '';
			foreach ( $root->childNodes as $child ) {
				$flattened .= (string) $dom->saveHTML( $child );
			}

			libxml_clear_errors();
			libxml_use_internal_errors( $previous );

			return '' !== $flattened ? $flattened : $content_html;
		}
	}

	add_filter( 'the_content', 'elmercado_blog_flatten_top_level_sections_010280', 34 );

	if ( function_exists( 'elmercado_blog_inject_inline_commercial_blocks' ) ) {
		add_filter( 'the_content', 'elmercado_blog_inject_inline_commercial_blocks', 35 );
	}

	add_action(
		'wp_head',
		static function (): void {
			?>
			<style id="elmercado-blog-inline-commerce-010281">
				body.single-post main#primary.emo-article-page > article.emo-article ~ *:not(.emo-related-reading) {
					display: none !important;
				}

				body.single-post .emo-inline-commerce[hidden] {
					display: none !important;
				}

				body.single-post .emo-inline-commerce {
					box-sizing: border-box;
				}

				/* Reduce tambien el margen del parrafo inmediatamente anterior. */
				body.single-post .emo-article-content > p:has(+ .emo-inline-special-anchor),
				body.single-post .emo-article-content > p:has(+ .emo-inline-newsletter-anchor) {
					margin-bottom: 8px !important;
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
				 * El margen exterior es simetrico y algo menor para integrarlo mejor.
				 */
				body.single-post .emo-inline-newsletter {
					display: block;
					width: min(100%, 900px) !important;
					max-width: 900px !important;
					min-width: 0 !important;
					margin: 8px auto 18px !important;
					padding: 14px 22px 18px !important;
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
						margin: 8px auto 16px !important;
						padding: 14px 16px 16px !important;
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
			<script id="elmercado-blog-inline-commerce-runtime-010280">
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
