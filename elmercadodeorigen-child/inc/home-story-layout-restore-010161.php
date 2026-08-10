<?php
/**
 * Restaura la geometría original de la sección "Nuestro criterio".
 *
 * El copy 0.10.155 concatenó referencias de captura de preg_replace con
 * etiquetas que empiezan por 01/02/03. Eso produjo referencias ambiguas
 * ($101, $102, $103) y eliminó las aperturas <article><span> de las tarjetas.
 * Esta capa recompone únicamente el bloque de valores con el HTML original,
 * manteniendo el copy aprobado y sin añadir estilos ni comportamiento.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repone las tres tarjetas de criterio antes de la capa exterior de copy.
 *
 * @param string $content Contenido de la Home.
 * @return string
 */
function elmercado_home_story_layout_restore_010161( string $content ): string {
	if ( '' === $content || is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$values = '<div class="emo-story__values">'
		. '<article><span aria-label="01 — CALIDAD">01<span hidden> — CALIDAD</span></span><h3>Lo primero es el producto.</h3><p>Partimos de su calidad y de todo aquello que interviene en ella. Solo desde ahí valoramos si tiene sentido incorporarlo a nuestra selección.</p></article>'
		. '<article><span aria-label="02 — DIFERENCIACIÓN">02<span hidden> — DIFERENCIACIÓN</span></span><h3>Tiene que haber algo más.</h3><p>Buscamos productos con atributos propios, capaces de aportar un valor diferencial.<br><br>No tiene que ser siempre el mismo. Pero tiene que existir.</p></article>'
		. '<article><span aria-label="03 — PRODUCTOR">03<span hidden> — PRODUCTOR</span></span><h3>Importa quién está detrás.</h3><p>Conocer al productor nos permite entender mejor el producto: su forma de trabajar, el cuidado que dedica a su elaboración y aquello que define su manera de hacer las cosas.<br><br>Producto y productor forman parte de una misma elección.</p></article>'
		. '</div>';

	$result = preg_replace(
		'~<div class="emo-story__values">.*?</div>~s',
		$values,
		$content,
		1
	);

	return is_string( $result ) ? $result : $content;
}

add_filter( 'the_content', 'elmercado_home_story_layout_restore_010161', 999 );
