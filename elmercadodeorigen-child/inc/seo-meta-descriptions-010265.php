<?php
/**
 * Meta descriptions SEO para productos y artículos.
 *
 * Genera descripciones útiles cuando la actual falta o es demasiado corta,
 * sin sustituir descripciones manuales suficientemente completas.
 * Compatible con Yoast SEO, Rank Math, AIOSEO y SEOPress; si no hay plugin
 * SEO activo, imprime una única meta description como fallback.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normaliza texto para usarlo en una meta description.
 *
 * @param string $text Texto de origen.
 * @return string
 */
function elmercado_seo_plain_text( $text ) {
	$text = (string) $text;
	$text = strip_shortcodes( $text );
	$text = preg_replace( '/<!--.*?-->/s', ' ', $text );
	$text = wp_strip_all_tags( $text, true );
	$text = html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );

	return trim( (string) $text );
}

/**
 * Devuelve la longitud de texto de forma segura en UTF-8.
 *
 * @param string $text Texto.
 * @return int
 */
function elmercado_seo_strlen( $text ) {
	return function_exists( 'mb_strlen' ) ? mb_strlen( $text, 'UTF-8' ) : strlen( $text );
}

/**
 * Recorta una descripción cerca de un límite, evitando cortar palabras.
 *
 * @param string $text Texto.
 * @param int    $max  Máximo de caracteres.
 * @return string
 */
function elmercado_seo_trim_description( $text, $max = 158 ) {
	$text = elmercado_seo_plain_text( $text );
	$max  = max( 120, (int) $max );

	if ( elmercado_seo_strlen( $text ) <= $max ) {
		return rtrim( $text, " \t\n\r\0\x0B,;:-" );
	}

	if ( function_exists( 'mb_substr' ) ) {
		$cut       = mb_substr( $text, 0, $max, 'UTF-8' );
		$last_space = mb_strrpos( $cut, ' ', 0, 'UTF-8' );

		if ( false !== $last_space && $last_space >= ( $max - 28 ) ) {
			$cut = mb_substr( $cut, 0, $last_space, 'UTF-8' );
		}
	} else {
		$cut        = substr( $text, 0, $max );
		$last_space = strrpos( $cut, ' ' );

		if ( false !== $last_space && $last_space >= ( $max - 28 ) ) {
			$cut = substr( $cut, 0, $last_space );
		}
	}

	return rtrim( $cut, " \t\n\r\0\x0B,;:-" ) . '…';
}

/**
 * Detecta si la URL actual corresponde a la versión inglesa.
 *
 * Falang mantiene el locale principal en algunos contextos, por eso también
 * comprobamos el prefijo /en/ de la URL pública.
 *
 * @return bool
 */
function elmercado_seo_is_english() {
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( 0 === strpos( $uri, '/en/' ) || '/en' === $uri ) {
		return true;
	}

	return 0 === strpos( (string) get_locale(), 'en_' );
}

/**
 * Construye una descripción SEO específica para la URL actual.
 *
 * @return string
 */
function elmercado_seo_generated_description() {
	if ( ! is_singular( array( 'post', 'product' ) ) ) {
		return '';
	}

	$post_id = get_queried_object_id();
	$post    = $post_id ? get_post( $post_id ) : null;

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$title   = elmercado_seo_plain_text( get_the_title( $post ) );
	$english = elmercado_seo_is_english();

	if ( 'product' === $post->post_type ) {
		$short = elmercado_seo_plain_text( $post->post_excerpt );

		if ( elmercado_seo_strlen( $short ) >= 70 ) {
			$description = $title . ': ' . $short;
		} elseif ( $english ) {
			$description = 'Buy ' . $title . ' online directly from its source. See characteristics, formats, price and delivery information at El Mercado de Origen.';
		} else {
			$description = 'Compra ' . $title . ' online directamente desde su origen. Consulta características, formatos, precio y condiciones de envío en El Mercado de Origen.';
		}

		return elmercado_seo_trim_description( $description );
	}

	$source = elmercado_seo_plain_text( $post->post_excerpt );

	if ( elmercado_seo_strlen( $source ) < 90 ) {
		$source = elmercado_seo_plain_text( $post->post_content );
	}

	if ( '' !== $source ) {
		$description = $title . ': ' . $source;
	} elseif ( $english ) {
		$description = $title . '. A practical guide from El Mercado de Origen to understand the product, its origin and the key points to choose with confidence.';
	} else {
		$description = $title . '. Una guía de El Mercado de Origen para entender mejor el producto, su origen y las claves que ayudan a elegir con criterio.';
	}

	return elmercado_seo_trim_description( $description );
}

/**
 * Mejora una meta description únicamente cuando falta o es demasiado corta.
 *
 * Así respetamos descripciones manuales que ya tienen contenido suficiente.
 *
 * @param string $description Descripción generada por el plugin SEO.
 * @return string
 */
function elmercado_seo_filter_meta_description( $description ) {
	if ( is_admin() || ! is_singular( array( 'post', 'product' ) ) ) {
		return $description;
	}

	$current = elmercado_seo_plain_text( $description );

	if ( elmercado_seo_strlen( $current ) >= 120 ) {
		return $description;
	}

	$generated = elmercado_seo_generated_description();

	return '' !== $generated ? $generated : $description;
}

add_filter( 'wpseo_metadesc', 'elmercado_seo_filter_meta_description', 99 );
add_filter( 'rank_math/frontend/description', 'elmercado_seo_filter_meta_description', 99 );
add_filter( 'aioseo_description', 'elmercado_seo_filter_meta_description', 99 );
add_filter( 'seopress_titles_desc', 'elmercado_seo_filter_meta_description', 99 );

/**
 * Comprueba si un plugin SEO conocido se encarga ya de imprimir la etiqueta.
 *
 * @return bool
 */
function elmercado_seo_plugin_outputs_description() {
	return defined( 'WPSEO_VERSION' )
		|| defined( 'RANK_MATH_VERSION' )
		|| defined( 'AIOSEO_VERSION' )
		|| defined( 'SEOPRESS_VERSION' )
		|| function_exists( 'aioseo' );
}

/**
 * Fallback para instalaciones sin plugin SEO que emita meta description.
 *
 * @return void
 */
function elmercado_seo_fallback_meta_description() {
	if ( is_admin() || elmercado_seo_plugin_outputs_description() ) {
		return;
	}

	$description = elmercado_seo_generated_description();

	if ( '' === $description ) {
		return;
	}

	echo '<meta name="description" content="' . esc_attr( $description ) . '">' . "\n";
}
add_action( 'wp_head', 'elmercado_seo_fallback_meta_description', 2 );
