<?php
/**
 * SEO orientado a oportunidades reales de Google Search Console.
 *
 * 0.10.313:
 * - elimina un H1 duplicado dentro del contenido cuando repite el título;
 * - mejora title y meta description de URLs con muchas impresiones y CTR bajo;
 * - no cambia slugs, canonicals ni contenido almacenado.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve la ruta pública actual normalizada con barra inicial y final.
 *
 * @return string
 */
function elmercado_gsc_current_path_010313() {
	$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path        = (string) wp_parse_url( $request_uri, PHP_URL_PATH );

	if ( '' === $path ) {
		return '/';
	}

	$path = '/' . ltrim( $path, '/' );

	return '/' === $path ? '/' : trailingslashit( untrailingslashit( $path ) );
}

/**
 * Normaliza un texto para comparar títulos sin depender de mayúsculas o HTML.
 *
 * @param string $text Texto a normalizar.
 * @return string
 */
function elmercado_gsc_normalize_heading_010313( $text ) {
	$text = html_entity_decode( wp_strip_all_tags( (string) $text, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', trim( $text ) );
	$text = remove_accents( (string) $text );

	return function_exists( 'mb_strtolower' )
		? mb_strtolower( $text, 'UTF-8' )
		: strtolower( $text );
}

/**
 * Quita únicamente el primer H1 del cuerpo si es una copia exacta del título.
 *
 * La plantilla single.php ya imprime el H1 principal. No se modifica el
 * post_content guardado en WordPress y un H1 distinto se conserva.
 *
 * @param string $content Contenido procesado de la entrada.
 * @return string
 */
function elmercado_gsc_remove_duplicate_h1_010313( $content ) {
	if ( is_admin() || ! is_singular( 'post' ) || false === stripos( (string) $content, '<h1' ) ) {
		return $content;
	}

	$post_id = get_queried_object_id();
	$title   = $post_id ? get_the_title( $post_id ) : '';

	if ( '' === trim( (string) $title ) ) {
		return $content;
	}

	$normalized_title = elmercado_gsc_normalize_heading_010313( $title );
	$removed          = false;

	$content = preg_replace_callback(
		'~<h1\b[^>]*>(.*?)</h1>~isu',
		static function ( $matches ) use ( $normalized_title, &$removed ) {
			if ( $removed ) {
				return $matches[0];
			}

			$heading = elmercado_gsc_normalize_heading_010313( $matches[1] );

			if ( '' !== $heading && $heading === $normalized_title ) {
				$removed = true;
				return '';
			}

			return $matches[0];
		},
		(string) $content,
		1
	);

	return is_string( $content ) ? $content : '';
}
add_filter( 'the_content', 'elmercado_gsc_remove_duplicate_h1_010313', 8 );

/**
 * Titles SEO priorizados a partir del informe GSC de 29/09/2026.
 *
 * Sólo se actúa sobre URLs con señal suficiente; el resto conserva su title.
 *
 * @return string
 */
function elmercado_gsc_targeted_title_010313() {
	$titles = array(
		'/en/how-much-protein-in-beef/' => 'Beef Protein per 100 g: Amounts by Serving | El Mercado de Origen',
		'/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/' => '¿Hay que remojar las lentejas? Tiempo de remojo | El Mercado de Origen',
		'/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/' => 'Remojo de garbanzos: tiempo y cocción | El Mercado de Origen',
		'/cuanta-proteina-tiene-carne-ternera/' => 'Proteína de la ternera por 100 g y por ración | El Mercado de Origen',
		'/en/eggplant-brown-inside-when-normal-and-when-overripe/' => 'Brown Eggplant Inside: Normal or Overripe? | El Mercado de Origen',
		'/cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas/' => 'Conservas abiertas: cuánto duran en la nevera | El Mercado de Origen',
		'/cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias/' => 'Legumbre seca por persona: gramos y raciones | El Mercado de Origen',
		'/cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla/' => 'Carne fuera de la nevera: cuánto tiempo es seguro | El Mercado de Origen',
		'/en/how-long-can-meat-stay-out-of-the-fridge-before-cooking/' => 'How Long Can Meat Stay Out of the Fridge? | El Mercado de Origen',
		'/en/do-lentils-need-soaking-how-long/' => 'Do Lentils Need Soaking? Soaking Time Guide | El Mercado de Origen',
		'/garbanzos-agua-caliente-o-fria-remojo-coccion/' => 'Garbanzos: agua caliente o fría para remojo y cocción | El Mercado de Origen',
		'/en/olive-oil-calories-tablespoon-100g/' => 'Olive Oil Calories: Tablespoon and 100 g | El Mercado de Origen',
		'/verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo/' => 'Verdura y hortaliza: diferencia y ejemplos | El Mercado de Origen',
		'/en/how-long-can-you-freeze-meat-beef-ground-beef-burgers/' => 'How Long Can You Freeze Meat? Beef and Burgers | El Mercado de Origen',
		'/calorias-aceite-oliva-cucharada-100g/' => 'Calorías del aceite de oliva: cucharada y 100 g | El Mercado de Origen',
	);

	$path = elmercado_gsc_current_path_010313();

	return isset( $titles[ $path ] ) ? $titles[ $path ] : '';
}

/**
 * Sustituye el title sólo cuando la URL está en la lista prioritaria.
 *
 * @param string $title Title actual.
 * @return string
 */
function elmercado_gsc_filter_title_010313( $title ) {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return $title;
	}

	$targeted = elmercado_gsc_targeted_title_010313();

	return '' !== $targeted ? $targeted : $title;
}
add_filter( 'pre_get_document_title', 'elmercado_gsc_filter_title_010313', 99 );
add_filter( 'wpseo_title', 'elmercado_gsc_filter_title_010313', 110 );
add_filter( 'rank_math/frontend/title', 'elmercado_gsc_filter_title_010313', 110 );
add_filter( 'aioseo_title', 'elmercado_gsc_filter_title_010313', 110 );
add_filter( 'seopress_titles_title', 'elmercado_gsc_filter_title_010313', 110 );

/**
 * Meta descriptions enfocadas a responder la intención desde el snippet.
 *
 * @return string
 */
function elmercado_gsc_targeted_description_010313() {
	$descriptions = array(
		'/en/how-much-protein-in-beef/' => 'Beef provides about 20–21 g of protein per 100 g raw. See protein amounts by serving and how values change between raw and cooked beef.',
		'/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/' => '¿Hay que poner las lentejas en remojo? Descubre cuándo hace falta, cuánto tiempo dejarlas según el tipo y cómo conseguir una buena cocción.',
		'/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/' => 'Guía práctica para remojar garbanzos y calcular su cocción: tiempos orientativos, olla convencional o rápida y claves para que queden tiernos.',
		'/cuanta-proteina-tiene-carne-ternera/' => 'La ternera magra aporta alrededor de 20–21 g de proteína por 100 g en crudo. Consulta cantidades por ración y diferencias al cocinarla.',
		'/en/eggplant-brown-inside-when-normal-and-when-overripe/' => 'Brown flesh inside an eggplant is not always a reason to discard it. Learn why it browns, signs of overripeness and when it is better not to eat it.',
		'/cuanto-duran-conservas-una-vez-abiertas-nevera-como-guardarlas/' => 'Consulta cuánto dura una conserva una vez abierta, cómo guardarla correctamente en la nevera y qué señales indican que conviene desecharla.',
		'/cuanta-legumbre-seca-por-persona-garbanzos-lentejas-alubias/' => 'Calcula cuánta legumbre seca necesitas por persona para garbanzos, lentejas y alubias, con equivalencias útiles para ajustar raciones sin pasarte.',
		'/cuanto-tiempo-puede-estar-carne-fuera-nevera-antes-cocinarla/' => 'Cuánto tiempo puede estar la carne fuera de la nevera antes de cocinarla, qué cambia con el calor ambiente y cuándo es más seguro descartarla.',
		'/en/how-long-can-meat-stay-out-of-the-fridge-before-cooking/' => 'How long can raw meat stay out before cooking? See the key food-safety time limits, what changes in warm conditions and when to discard it.',
		'/en/do-lentils-need-soaking-how-long/' => 'Do lentils need soaking? Learn which lentils benefit from it, how long to soak them and how soaking can affect cooking time and texture.',
		'/garbanzos-agua-caliente-o-fria-remojo-coccion/' => '¿Agua caliente o fría para los garbanzos? Aprende qué temperatura usar en el remojo y la cocción y cómo evitar que queden duros.',
		'/en/olive-oil-calories-tablespoon-100g/' => 'Check olive oil calories per tablespoon and per 100 g, with practical serving conversions to understand how much energy your usual portion provides.',
		'/se-pueden-congelar-legumbres-cocidas-como-hacerlo/' => 'Sí, las legumbres cocidas se pueden congelar. Aprende cómo enfriarlas, envasarlas y descongelarlas para conservar mejor su textura y sabor.',
		'/verdura-vs-hortaliza-diferencia-que-alimentos-pertenecen-cada-grupo/' => 'Verdura y hortaliza no significan exactamente lo mismo. Descubre la diferencia, qué alimentos incluye cada concepto y ejemplos fáciles de recordar.',
		'/cuanto-dura-carne-cocinada-nevera-conservacion-segura/' => 'Consulta cuánto dura la carne cocinada en la nevera, cómo enfriarla y guardarla correctamente y qué señales indican que ya no conviene consumirla.',
		'/en/how-long-opened-canned-food-keeps-in-fridge-how-to-store-it/' => 'How long does opened canned food keep in the fridge? Learn how to store leftovers safely, choose a container and spot signs that it should be discarded.',
		'/en/how-long-can-you-freeze-meat-beef-ground-beef-burgers/' => 'How long can beef, ground beef and burgers stay frozen? Compare storage times and learn how packaging and thawing affect quality and safety.',
		'/por-que-aceite-hace-espuma-al-freir-causas-cuando-preocuparse/' => '¿Por qué hace espuma el aceite al freír? Repasamos las causas más habituales, cuándo es normal y qué señales indican que conviene cambiar el aceite.',
		'/carne-magra-ternera-que-es-como-cocinar-tierna/' => 'Qué es la carne magra de ternera, qué cortes encajan mejor y cómo cocinarlos para mantenerlos tiernos, jugosos y sabrosos.',
		'/calorias-aceite-oliva-cucharada-100g/' => 'Consulta las calorías del aceite de oliva por cucharada y por 100 g, con equivalencias prácticas para entender cuánto aporta una ración habitual.',
	);

	$path = elmercado_gsc_current_path_010313();

	return isset( $descriptions[ $path ] ) ? $descriptions[ $path ] : '';
}

/**
 * Prioriza las descriptions GSC sobre generadores genéricos del plugin SEO.
 *
 * @param string $description Description actual.
 * @return string
 */
function elmercado_gsc_filter_description_010313( $description ) {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return $description;
	}

	$targeted = elmercado_gsc_targeted_description_010313();

	return '' !== $targeted ? $targeted : $description;
}
add_filter( 'wpseo_metadesc', 'elmercado_gsc_filter_description_010313', 110 );
add_filter( 'rank_math/frontend/description', 'elmercado_gsc_filter_description_010313', 110 );
add_filter( 'aioseo_description', 'elmercado_gsc_filter_description_010313', 110 );
add_filter( 'seopress_titles_desc', 'elmercado_gsc_filter_description_010313', 110 );

/**
 * Integra las descriptions GSC también en el fallback sin plugin SEO.
 *
 * El módulo seo-meta-descriptions-010265.php consulta esta función cuando
 * existe, evitando dos etiquetas meta.
 *
 * @return string
 */
function elmercado_gsc_fallback_description_010313() {
	return elmercado_gsc_targeted_description_010313();
}
