<?php
/**
 * Enlaces comerciales contextuales desde tres guías de regalos del blog.
 *
 * No altera el contenido guardado, títulos, metadatos SEO, canónicas ni URLs.
 * Si una landing deja de estar publicada, su enlace desaparece automáticamente.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! is_singular( 'post' ) || is_admin() || is_feed() || ! is_string( $content ) ) {
			return $content;
		}

		/* Las landings publicadas son españolas. No mezclar su CTA en la versión inglesa. */
		if ( function_exists( 'elmercado_blog_products_is_english_010277' ) && elmercado_blog_products_is_english_010277() ) {
			return $content;
		}

		$post_id = (int) get_queried_object_id();
		if ( $post_id < 1 || (int) get_the_ID() !== $post_id || false !== strpos( $content, 'data-emdo-b2b-context' ) ) {
			return $content;
		}

		$links = array(
			'que-jamon-iberico-regalar-pieza-paleta-loncheado-presupuesto' => array(
				'page'  => 'jamon-iberico-empresas-regalos-corporativos',
				'label' => 'Consulta opciones de jamón ibérico para empresas',
				'intro' => '¿Necesitas regalos de jamón ibérico para un equipo o varios clientes?',
			),
			'como-elegir-lote-productos-gourmet-presupuesto-destinatario-conservacion' => array(
				'page'  => 'cestas-lotes-navidad-empresas',
				'label' => 'Consulta cestas y lotes de Navidad para empresas',
				'intro' => '¿Buscas varias cestas para empleados o clientes?',
			),
			'productos-de-pato-para-regalar-foie-pates-jamon-lotes' => array(
				'page'  => 'regalos-gourmet-empresas',
				'label' => 'Consulta opciones de regalos gourmet para empresas',
				'intro' => '¿Estás buscando regalos gastronómicos para una empresa?',
			),
		);

		$slug = (string) get_post_field( 'post_name', $post_id );
		if ( ! isset( $links[ $slug ] ) ) {
			return $content;
		}

		$target = get_page_by_path( $links[ $slug ]['page'], OBJECT, 'page' );
		if ( ! $target instanceof WP_Post || 'publish' !== $target->post_status ) {
			return $content;
		}

		$url = get_permalink( $target );
		if ( ! $url ) {
			return $content;
		}

		$cta = '<aside class="emdo-blog-b2b-context" data-emdo-b2b-context="1" style="margin:24px 0;padding:16px 20px;border-left:3px solid #34634d;background:#f6f8f5;border-radius:5px">'
			. '<p style="margin:0 0 6px"><strong>' . esc_html( $links[ $slug ]['intro'] ) . '</strong></p>'
			. '<p style="margin:0"><a href="' . esc_url( $url ) . '">' . esc_html( $links[ $slug ]['label'] ) . ' →</a></p>'
			. '</aside>';

		/* Colocar antes del bloque de compra relacionado, sin alterar el texto original. */
		if ( preg_match( '/<(?:div|section)\\b[^>]*\\bemo-related-products-dynamic\\b/iu', $content, $match, PREG_OFFSET_CAPTURE ) ) {
			$offset = (int) $match[0][1];
		} elseif ( preg_match( '/<h[2-4]\\b[^>]*>[^<]*(?:Jamones y paletas|Productos de pato relacionados|Productos relacionados)[^<]*<\\/h[2-4]>/iu', $content, $match, PREG_OFFSET_CAPTURE ) ) {
			$offset = (int) $match[0][1];
		} else {
			$offset = strlen( $content );
		}

		return substr( $content, 0, $offset ) . $cta . substr( $content, $offset );
	},
	32
);
