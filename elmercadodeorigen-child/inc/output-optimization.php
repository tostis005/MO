<?php
/**
 * Optimización final de la respuesta HTML de la portada.
 *
 * Algunos plugins imprimen recursos directamente en wp_head o wp_footer una
 * vez terminada la cola normal. La portada está construida por este child theme,
 * por lo que podemos retirar allí recursos sin interfaz y posponer marketing
 * hasta la primera interacción sin afectar tienda, producto ni checkout.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determina si una etiqueta contiene alguno de los fragmentos indicados.
 *
 * @param string   $tag Etiqueta HTML completa.
 * @param string[] $fragments Fragmentos de URL.
 */
function elmercado_tag_contains_fragment( string $tag, array $fragments ): bool {
	foreach ( $fragments as $fragment ) {
		if ( str_contains( $tag, $fragment ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Extrae y normaliza un atributo URL de una etiqueta HTML.
 */
function elmercado_asset_url_from_tag( string $tag, string $attribute ): string {
	$pattern = '/\b' . preg_quote( $attribute, '/' ) . '=(?:"([^"]*)"|\'([^\']*)\')/i';

	if ( ! preg_match( $pattern, $tag, $matches ) ) {
		return '';
	}

	$value = isset( $matches[1] ) && '' !== $matches[1]
		? (string) $matches[1]
		: (string) ( $matches[2] ?? '' );
	$url   = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

	if ( str_starts_with( $url, '//' ) ) {
		$url = 'https:' . $url;
	}

	return esc_url_raw( $url );
}

/**
 * Convierte las imágenes lazy de Smush en imágenes nativas pequeñas. La URL de
 * data-src ya apunta a la miniatura de 300 px; retiramos srcset para que el
 * navegador no ascienda automáticamente a las variantes de 600–1500 px.
 */
function elmercado_normalize_lazy_image( array $matches ): string {
	$tag = $matches[0];

	if ( ! str_contains( $tag, 'lazyload' ) || ! preg_match( '/\bdata-src=(?:"([^"]+)"|\'([^\']+)\')/i', $tag, $src_match ) ) {
		return $tag;
	}

	$value  = isset( $src_match[1] ) && '' !== $src_match[1]
		? (string) $src_match[1]
		: (string) ( $src_match[2] ?? '' );
	$source = esc_url_raw( html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );

	if ( '' === $source ) {
		return $tag;
	}

	$tag = (string) preg_replace( '/\s(?:data-)?srcset=(?:"[^"]*"|\'[^\']*\')/i', '', $tag );
	$tag = (string) preg_replace( '/\s(?:data-)?sizes=(?:"[^"]*"|\'[^\']*\')/i', '', $tag );
	$tag = (string) preg_replace( '/\sdata-src=(?:"[^"]*"|\'[^\']*\')/i', '', $tag );
	$tag = (string) preg_replace( '/\ssrc=(?:"[^"]*"|\'[^\']*\')/i', '', $tag );
	$tag = str_replace( array( ' lazyloaded', ' lazyload' ), '', $tag );

	return str_replace( '<img', '<img src="' . esc_url( $source ) . '"', $tag );
}

/**
 * Elimina recursos tardíos, normaliza imágenes y añade un cargador diferido
 * para analítica, newsletter y atribución comercial.
 */
function elmercado_optimize_home_html( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$remove_style_fragments = array(
		'/plugins/elementor/',
		'/uploads/elementor/css/',
		'/plugins/contact-form-7/',
		'/plugins/advanced-product-labels-for-woocommerce/',
		'/plugins/advanced-coupons-for-woocommerce-free/',
		'/plugins/waitlist-woocommerce/',
		'/plugins/yith-woocommerce-wishlist/',
		'/plugins/yith-woocommerce-product-bundles',
		'/plugins/yith-woocommerce-product-add-ons/',
		'/plugins/product-categories-designs-for-woocommerce/',
		'/plugins/slide-anything/',
		'/plugins/wc-frontend-manager/',
		'/plugins/woocommerce/assets/css/prettyPhoto.css',
		'/plugins/fluentform/assets/css/fluent-forms-elementor-widget.css',
		'/plugins/woo-discount-rules',
		'/custom-css-js/6585.css',
		'/assets/client/blocks/wc-blocks.css',
		'/css/dashicons.min.css',
		'/css/jetpack.css',
		'fonts.googleapis.com/',
		'fonts.bunny.net/',
	);

	$delay_style_fragments = array(
		'/plugins/wordpress-popup/assets/hustle-ui/css/',
		'/plugins/click-to-chat-for-whatsapp/',
	);

	$remove_script_fragments = array(
		'/plugins/elementor/',
		'/plugins/contact-form-7/',
		'/plugins/advanced-product-labels-for-woocommerce/',
		'/plugins/advanced-coupons-for-woocommerce-free/',
		'/plugins/waitlist-woocommerce/',
		'/plugins/yith-woocommerce-wishlist/',
		'/plugins/yith-woocommerce-product-bundles',
		'/plugins/yith-woocommerce-product-add-ons/',
		'/plugins/product-categories-designs-for-woocommerce/',
		'/plugins/slide-anything/',
		'/plugins/wc-frontend-manager/',
		'/plugins/woocommerce/assets/js/prettyPhoto/',
		'/plugins/wp-smush-pro/app/assets/js/smush-lazy-load-native',
		'/plugins/woo-discount-rules/',
		'/plugins/woo-discount-rules-pro/',
		'/themes/woostify/assets/js/arrive.min.js',
		'/themes/woostify/assets/js/general.min.js',
		'/themes/woostify/assets/js/navigation.min.js',
		'/themes/woostify/assets/js/woocommerce/quantity-button.min.js',
		'/themes/woostify/assets/js/woocommerce/woocommerce.min.js',
		'/wp-includes/js/underscore.min.js',
		'/wp-includes/js/jquery/ui/core.min.js',
		'/woocommerce/assets/js/jquery-blockui/',
		'/jetpack-connection/dist/tracks-callables.js',
		'cdn.trustindex.io/loader.js',
		'/custom-css-js/1341.js',
		'/recaptcha/api.js',
		'/recaptcha__',
	);

	$delay_script_fragments = array(
		'googletagmanager.com/gtag/js',
		'/plugins/google-analytics-for-wordpress/assets/js/frontend-gtag.js',
		'stats.wp.com/w.js',
		'stats.wp.com/s-',
		'/plugins/wordpress-popup/assets/hustle-ui/js/',
		'/plugins/wordpress-popup/assets/js/front.min.js',
		'/plugins/click-to-chat-for-whatsapp/',
		'/woocommerce/assets/js/sourcebuster/',
		'/woocommerce/assets/js/frontend/order-attribution.min.js',
	);

	$late_styles  = array();
	$late_scripts = array();

	$html = (string) preg_replace_callback(
		'/<link\b[^>]*>/i',
		static function ( array $matches ) use ( $remove_style_fragments, $delay_style_fragments, &$late_styles ): string {
			$tag = $matches[0];

			if ( elmercado_tag_contains_fragment( $tag, $remove_style_fragments ) ) {
				return '';
			}

			if ( elmercado_tag_contains_fragment( $tag, $delay_style_fragments ) ) {
				$url = elmercado_asset_url_from_tag( $tag, 'href' );

				if ( '' !== $url ) {
					$late_styles[] = $url;
				}

				return '';
			}

			return $tag;
		},
		$html
	);

	$html = (string) preg_replace_callback(
		'/<script\b[^>]*\bsrc=(?:"[^"]*"|\'[^\']*\')[^>]*>\s*<\/script>/is',
		static function ( array $matches ) use ( $remove_script_fragments, $delay_script_fragments, &$late_scripts ): string {
			$tag = $matches[0];

			if ( elmercado_tag_contains_fragment( $tag, $remove_script_fragments ) ) {
				return '';
			}

			if ( elmercado_tag_contains_fragment( $tag, $delay_script_fragments ) ) {
				$url = elmercado_asset_url_from_tag( $tag, 'src' );

				if ( '' !== $url ) {
					$late_scripts[] = $url;
				}

				return '';
			}

			return $tag;
		},
		$html
	);

	/* Preloads de fuentes retiradas y orígenes que ya no forman parte del render. */
	$html = (string) preg_replace( '/<link\b[^>]*(?:hustle-icons-font|fonts\.gstatic\.com|fonts\.googleapis\.com|fonts\.bunny\.net)[^>]*>/i', '', $html );

	/* Imágenes nativas y sin el duplicado noscript generado por Smush. */
	$html = (string) preg_replace_callback( '/<img\b[^>]*>/i', 'elmercado_normalize_lazy_image', $html );
	$html = (string) preg_replace( '/<noscript>\s*<img\b[^>]*>\s*<\/noscript>/is', '', $html );

	/* La imagen secundaria es decorativa y además iniciaba otra descarga. */
	$html = (string) preg_replace(
		'/<span\b[^>]*class=(?:"[^"]*product-loop-hover-image[^"]*"|\'[^\']*product-loop-hover-image[^\']*\')[^>]*>\s*<\/span>/is',
		'',
		$html
	);

	$late_styles  = array_values( array_unique( array_filter( $late_styles ) ) );
	$late_scripts = array_values( array_unique( array_filter( $late_scripts ) ) );
	$styles_json  = wp_json_encode( $late_styles, JSON_UNESCAPED_SLASHES );
	$scripts_json = wp_json_encode( $late_scripts, JSON_UNESCAPED_SLASHES );

	$gate = <<<'HTML'
<style id="elmercado-marketing-gate">
.hustle-ui { display: none !important; }
</style>
HTML;

	if ( str_contains( $html, '</head>' ) ) {
		$html = str_replace( '</head>', $gate . "\n</head>", $html );
	}

	$late_overrides = '<style id="elmercado-late-overrides">'
		. 'body.elmercado-child-theme ul.products li.product .product-loop-hover-image,'
		. 'body.elmercado-child-theme ul.products li.product .product-loop-action,'
		. 'body.elmercado-child-theme ul.products li.product .loop-add-to-cart-on-image{display:none!important;opacity:0!important;visibility:hidden!important;pointer-events:none!important;transform:none!important}'
		. 'body.elmercado-child-theme ul.products li.product .product-loop-image{display:block!important;opacity:1!important;visibility:visible!important}'
		. '</style>';

	$late_overrides .= '<script id="elmercado-late-assets">(()=>{'
		. 'const styles=' . $styles_json . ',scripts=' . $scripts_json . ';let started=false;'
		. 'const loadStyle=(href)=>new Promise((resolve)=>{const link=document.createElement("link");link.rel="stylesheet";link.href=href;link.onload=link.onerror=resolve;document.head.append(link)});'
		. 'const loadScript=(src)=>new Promise((resolve)=>{const script=document.createElement("script");script.src=src;script.async=false;script.onload=script.onerror=resolve;document.body.append(script)});'
		. 'const start=()=>{if(started)return;started=true;events.forEach((name)=>window.removeEventListener(name,start,options));const run=async()=>{await Promise.all(styles.map(loadStyle));for(const src of scripts)await loadScript(src);document.getElementById("elmercado-marketing-gate")?.remove()};("requestIdleCallback"in window?requestIdleCallback(run,{timeout:2500}):setTimeout(run,0))};'
		. 'const events=["pointerdown","touchstart","keydown"];const options={passive:true,once:true};events.forEach((name)=>window.addEventListener(name,start,options));setTimeout(start,12000);'
		. 'const clean=(root=document)=>root.querySelectorAll?.(".product-loop-hover-image,.product-loop-action,.loop-add-to-cart-on-image").forEach((node)=>node.remove());clean();new MutationObserver((records)=>records.forEach((record)=>record.addedNodes.forEach((node)=>{if(node.nodeType===1){if(node.matches?.(".product-loop-hover-image,.product-loop-action,.loop-add-to-cart-on-image"))node.remove();else clean(node)}}))).observe(document.body,{childList:true,subtree:true});'
		. '})();</script>';

	if ( str_contains( $html, '</body>' ) ) {
		$html = str_replace( '</body>', $late_overrides . "\n</body>", $html );
	} else {
		$html .= $late_overrides;
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		ob_start( 'elmercado_optimize_home_html' );
	},
	-1000
);
