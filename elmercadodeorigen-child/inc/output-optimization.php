<?php
/**
 * Optimización final de la respuesta HTML de la portada.
 *
 * Algunos plugins imprimen estilos y scripts directamente en wp_footer, una
 * vez terminada la cola normal de WordPress. Como la portada está construida
 * completamente por este child theme, filtramos únicamente allí esos recursos
 * tardíos que no tienen ninguna interfaz activa.
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
 * Elimina recursos tardíos y añade los overrides que deben ganar a cualquier
 * regla impresa por un plugin en el pie de página.
 */
function elmercado_optimize_home_html( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$style_fragments = array(
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
		'/custom-css-js/6585.css',
		'/assets/client/blocks/wc-blocks.css',
		'/css/dashicons.min.css',
		'/css/jetpack.css',
	);

	$script_fragments = array(
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
		'/custom-css-js/1341.js',
		'/recaptcha/api.js',
		'/recaptcha__',
	);

	$html = (string) preg_replace_callback(
		'/<link\b[^>]*>/i',
		static function ( array $matches ) use ( $style_fragments ): string {
			return elmercado_tag_contains_fragment( $matches[0], $style_fragments ) ? '' : $matches[0];
		},
		$html
	);

	$html = (string) preg_replace_callback(
		'/<script\b[^>]*\bsrc=(?:"[^"]*"|\'[^\']*\')[^>]*>\s*<\/script>/is',
		static function ( array $matches ) use ( $script_fragments ): string {
			return elmercado_tag_contains_fragment( $matches[0], $script_fragments ) ? '' : $matches[0];
		},
		$html
	);

	/* La imagen secundaria es decorativa y además iniciaba otra descarga. */
	$html = (string) preg_replace(
		'/<span\b[^>]*class=(?:"[^"]*product-loop-hover-image[^"]*"|\'[^\']*product-loop-hover-image[^\']*\')[^>]*>\s*<\/span>/is',
		'',
		$html
	);

	$late_overrides = <<<'HTML'
<style id="elmercado-late-overrides">
body.elmercado-child-theme ul.products li.product .product-loop-hover-image,
body.elmercado-child-theme ul.products li.product .product-loop-action,
body.elmercado-child-theme ul.products li.product .loop-add-to-cart-on-image,
body.elmercado-child-theme ul.products li.product:hover .product-loop-action,
body.elmercado-child-theme ul.products li.product:hover .loop-add-to-cart-on-image {
	display: none !important;
	opacity: 0 !important;
	visibility: hidden !important;
	pointer-events: none !important;
	transform: none !important;
}
body.elmercado-child-theme ul.products li.product .product-loop-image,
body.elmercado-child-theme ul.products li.product:hover .product-loop-image {
	display: block !important;
	opacity: 1 !important;
	visibility: visible !important;
}
</style>
<script id="elmercado-remove-hover-artifacts">
(()=>{const clean=(root=document)=>root.querySelectorAll?.('.product-loop-hover-image,.product-loop-action,.loop-add-to-cart-on-image').forEach((node)=>node.remove());clean();new MutationObserver((records)=>records.forEach((record)=>record.addedNodes.forEach((node)=>{if(node.nodeType===1){if(node.matches?.('.product-loop-hover-image,.product-loop-action,.loop-add-to-cart-on-image'))node.remove();else clean(node);}}))).observe(document.body,{childList:true,subtree:true});})();
</script>
HTML;

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
