<?php
/**
 * Voz final de portada y ajustes PSI 0.10.149.
 *
 * Recupera la idea original de mercado: una selección corta de productores,
 * cada uno con su puesto, y el producto por delante del volumen. También hace
 * efectivas las prioridades responsivas del mosaico y saca dos scripts de
 * Woostify de la cadena crítica sin cambiar su orden relativo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajusta el discurso definitivo sobre el HTML ya compuesto por las capas previas.
 *
 * @param string $html Documento HTML.
 * @return string
 */
function elmercado_home_voice_final_010149( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$copy = array(
		'Productores seleccionados, con nombre propio' => 'Productores escogidos uno a uno',
		'Envíos preparados con cuidado' => 'Del origen a tu casa',
		'Del productor a tu mesa' => 'Del productor a tu casa',
		'El sabor empieza donde se hace.' => 'Lo bueno empieza en origen.',
		'Y llega directo a tu mesa.' => 'Y tiene nombre propio.',
		'Aceites, ibéricos y despensa elegidos por quién los hace, cómo los elabora y el cuidado que hay detrás. Del productor a tu casa, con el origen siempre a la vista.' => 'Reunimos una selección corta de productores que ponen el producto por delante del volumen: ibéricos hechos con tiempo, aceites de almazara y una despensa escogida por cómo sabe y por quién la hace. Compras sabiendo de dónde viene y quién está detrás.',
		'Descubrir la selección' => 'Entrar al mercado',
		'Conocer a los productores' => 'Conocer los productores',
		'Sabes quién lo hace' => 'Pocos y bien elegidos',
		'Elegido por cómo se elabora' => 'Cada uno con su puesto',
		'Compra segura y atención cercana' => 'Compra fácil y cercana',
		'Elegimos por lo que hay detrás' => 'Primero, el producto',
		'Materia prima, tiempo, oficio y procedencia: cada producto tiene razones para estar aquí.' => 'Nos fijamos en la materia prima, el sabor, la elaboración y la regularidad. Si no nos apetece tenerlo en casa, no tiene sentido que esté en el mercado.',
		'El productor no desaparece' => 'Después, quién lo hace',
		'Conoces quién lo elabora y la forma en que trabaja antes de elegir.' => 'Cada productor tiene su puesto, su historia y su manera de trabajar. Puedes conocerlos antes de decidir qué llevarte.',
		'Del origen a tu puerta' => 'Y de ahí, a tu casa',
		'Tu pedido se prepara con cuidado para que el producto llegue como merece.' => 'La compra es online, pero la idea es muy sencilla: acercar a quien hace un buen producto a quien quiere disfrutarlo.',
		'Empieza por el sabor que buscas' => 'De puesto en puesto',
		'Una despensa donde el origen se nota' => 'Una despensa hecha de buenos productores',
		'Aceites con carácter, ibéricos con tiempo y especialidades de productores que cuidan cada detalle desde el origen.' => 'Entra por lo que te apetezca. Detrás de cada categoría hay productores distintos, pero el criterio es el mismo: cosas que merece la pena llevar a casa y recordar por su nombre.',
		'Los que hacen volver' => 'Para empezar bien',
		'Productos que encuentran un sitio fijo en la mesa' => 'Los que más se llevan a casa',
		'Los más elegidos por quienes ya compran aquí: una buena forma de empezar por sabores que apetece volver a pedir.' => 'Una selección de los productos que más repiten nuestros clientes. Una forma sencilla de entrar al mercado por lo que ya funciona en muchas mesas.',
		'Ver toda la selección' => 'Ver todo el mercado',
		'Nuestro criterio' => 'Por qué existe este mercado',
		'La calidad empieza mucho antes de abrir el paquete.' => 'Queríamos volver a comprar sabiendo a quién.',
		'Empieza en la materia prima, en el campo, el secadero o el obrador; en el tiempo y en una forma concreta de hacer las cosas. Por eso aquí el productor y el origen siguen formando parte del producto.' => 'El Mercado de Origen nace para acortar la distancia entre quien hace un buen producto y quien acaba disfrutándolo. Buscamos proyectos que merece la pena conocer, les damos su propio puesto y hacemos más fácil comprarles desde casa.',
		'Conoce cómo nace el proyecto' => 'Conoce nuestra historia',
		'Sabes de dónde viene' => 'Producto antes que volumen',
		'Conocer la procedencia ayuda a entender el producto y todo el trabajo que hay detrás.' => 'Preferimos un catálogo más corto si eso significa poder elegir por materia prima, elaboración y sabor.',
		'El oficio se nota' => 'Cada productor, su puesto',
		'Seleccionamos productos en los que la materia prima, el tiempo y el cuidado tienen un papel real.' => 'No escondemos a quien lo hace detrás de una ficha genérica. Puedes entrar en su tienda y conocer su trabajo.',
		'Quien lo hace sigue cerca' => 'Más cerca del origen',
		'La compra es online, pero detrás sigue habiendo una finca, un secadero, un obrador y alguien que responde por su trabajo.' => 'La compra es digital; la relación vuelve a ser sencilla: sabes quién produce, qué estás comprando y de dónde viene.',
		'Conoce a quien lo hace' => 'De puesto en puesto',
		'Cuando un producto tiene algo especial, merece saber de dónde viene.' => 'Un buen producto se entiende mejor cuando conoces a quien lo hace.',
		'Entra en las tiendas de los productores, conoce su historia y descubre qué hace distinta su manera de trabajar.' => 'Entra en los puestos, conoce a los productores y encuentra esas cosas que uno acaba recomendando después por su nombre.',
	);

	return strtr( $html, $copy );
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		/* Capa exterior: recibe primero todas las sustituciones anteriores. */
		ob_start( 'elmercado_home_voice_final_010149' );
	},
	-700
);

/**
 * home-refresh solicita el mosaico con woocommerce_single, pero no añadía la
 * clase que usan las optimizaciones 0.10.146/147 para tamaños y prioridades.
 * La añadimos antes de que se ejecuten esos filtros.
 *
 * @param array<string,string>            $attributes Atributos de imagen.
 * @param WP_Post                         $attachment Adjunto.
 * @param string|array{0:int,1:int}|int[] $size Tamaño solicitado.
 * @return array<string,string>
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attributes, WP_Post $attachment, $size ): array {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || 'woocommerce_single' !== $size ) {
			return $attributes;
		}

		$class = isset( $attributes['class'] ) ? trim( (string) $attributes['class'] ) : '';
		if ( ! str_contains( $class, 'emo-hero-product-image' ) ) {
			$attributes['class'] = trim( $class . ' emo-hero-product-image' );
		}

		return $attributes;
	},
	5,
	3
);

/**
 * Woostify imprime estos dos scripts en la ruta crítica aunque su trabajo se
 * realiza sobre un DOM ya disponible. `defer` mantiene el orden de ejecución y
 * evita bloquear el parseo inicial.
 */
add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || '' === $src ) {
			return $html;
		}

		$defer = str_contains( $src, '/themes/woostify/assets/js/general.min.js' )
			|| str_contains( $src, '/themes/woostify/assets/js/navigation.min.js' );

		if ( ! $defer || str_contains( $html, ' defer' ) || str_contains( $html, ' async' ) ) {
			return $html;
		}

		return str_replace( '<script ', '<script defer ', $html );
	},
	PHP_INT_MAX,
	3
);
