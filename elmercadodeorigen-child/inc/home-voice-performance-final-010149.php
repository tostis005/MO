<?php
/**
 * Voz final de portada y ajustes PSI.
 *
 * Recupera la idea original de El Mercado de Origen: acercar productores y
 * consumidores, elegir por el producto y mantener visible a quien lo hace.
 * También activa las prioridades responsivas reales del mosaico y saca recursos
 * de Woostify sin interfaz crítica de la ruta inicial.
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
		'El sabor empieza donde se hace.' => 'Lo que merece la pena.',
		'Y llega directo a tu mesa.' => 'Directo de quien lo hace.',
		'Aceites, ibéricos y despensa elegidos por quién los hace, cómo los elabora y el cuidado que hay detrás. Del productor a tu casa, con el origen siempre a la vista.' => 'Seleccionamos productor a productor y miramos primero el producto: ibéricos hechos con tiempo, aceites de almazara y una despensa que apetece volver a pedir. Tú eliges sabiendo quién está detrás.',
		'Descubrir la selección' => 'Entrar al mercado',
		'Conocer a los productores' => 'Conocer los productores',
		'Sabes quién lo hace' => 'Productor a productor',
		'Elegido por cómo se elabora' => 'Cada uno con su puesto',
		'Compra segura y atención cercana' => 'Compra fácil y cercana',
		'Elegimos por lo que hay detrás' => 'El producto manda',
		'Materia prima, tiempo, oficio y procedencia: cada producto tiene razones para estar aquí.' => 'Elegimos por sabor, materia prima y forma de hacer las cosas. Si no nos apetece tenerlo en casa, no tiene sentido que esté en el mercado.',
		'El productor no desaparece' => 'Sabes a quién compras',
		'Conoces quién lo elabora y la forma en que trabaja antes de elegir.' => 'Cada productor tiene su puesto, su historia y sus productos. Puedes conocerlos antes de decidir qué llevarte.',
		'Del origen a tu puerta' => 'De allí a tu casa',
		'Tu pedido se prepara con cuidado para que el producto llegue como merece.' => 'La compra es online, pero la idea sigue siendo sencilla: acercar a quien hace un buen producto a quien quiere disfrutarlo.',
		'Empieza por el sabor que buscas' => 'De puesto en puesto',
		'Una despensa donde el origen se nota' => 'Una despensa que merece la pena conocer',
		'Aceites con carácter, ibéricos con tiempo y especialidades de productores que cuidan cada detalle desde el origen.' => 'Ibéricos con tiempo, aceites de almazara y otros productos elegidos productor a productor. Entra por lo que te apetezca y descubre quién está detrás.',
		'Los que hacen volver' => 'Para empezar bien',
		'Productos que encuentran un sitio fijo en la mesa' => 'Los favoritos del mercado',
		'Los más elegidos por quienes ya compran aquí: una buena forma de empezar por sabores que apetece volver a pedir.' => 'Los productos que más eligen quienes ya compran aquí. Una forma sencilla de empezar por cosas que funcionan y apetece volver a pedir.',
		'Ver toda la selección' => 'Ver todo el mercado',
		'Nuestro criterio' => 'Cómo empezó',
		'La calidad empieza mucho antes de abrir el paquete.' => 'Primero vendimos lo nuestro. Después abrimos el mercado.',
		'Empieza en la materia prima, en el campo, el secadero o el obrador; en el tiempo y en una forma concreta de hacer las cosas. Por eso aquí el productor y el origen siguen formando parte del producto.' => 'En 2017 pusimos en marcha una tienda online para vender directamente lo que producíamos. Vimos que había clientes que querían buen producto, trazabilidad y saber de dónde venía. El siguiente paso fue abrir espacio a otros productores que también merecía la pena conocer.',
		'Conoce cómo nace el proyecto' => 'Conoce nuestra historia',
		'Sabes de dónde viene' => 'Producto antes que volumen',
		'Conocer la procedencia ayuda a entender el producto y todo el trabajo que hay detrás.' => 'Preferimos elegir bien antes que llenar el catálogo: materia prima, elaboración, sabor y una calidad que se mantenga compra tras compra.',
		'El oficio se nota' => 'Cada productor, su puesto',
		'Seleccionamos productos en los que la materia prima, el tiempo y el cuidado tienen un papel real.' => 'No escondemos a quien lo hace detrás de una ficha genérica. Puedes entrar en su tienda y conocer su trabajo.',
		'Quien lo hace sigue cerca' => 'Directo y con trazabilidad',
		'La compra es online, pero detrás sigue habiendo una finca, un secadero, un obrador y alguien que responde por su trabajo.' => 'Sabes quién produce, qué estás comprando y de dónde viene. Esa cercanía es la razón de ser del mercado.',
		'Conoce a quien lo hace' => 'De puesto en puesto',
		'Cuando un producto tiene algo especial, merece saber de dónde viene.' => 'Conoce a quien está detrás de lo que compras.',
		'Entra en las tiendas de los productores, conoce su historia y descubre qué hace distinta su manera de trabajar.' => 'Entra en los puestos, descubre cómo trabaja cada productor y encuentra esas cosas que uno acaba recomendando después por su nombre.',
	);

	$html = strtr( $html, $copy );

	/*
	 * Advanced Coupons imprime modulepreloads comunes aunque no hay interfaz de
	 * cupones en la Home. Sus scripts principales ya se retiran en 0.10.147;
	 * eliminamos también esos preloads huérfanos para evitar dos descargas inútiles.
	 */
	$clean = preg_replace( '~<link\\b[^>]*advanced-coupons-for-woocommerce-free[^>]*>\\s*~i', '', $html );

	return is_string( $clean ) ? $clean : $html;
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

/**
 * Las valoraciones de WooCommerce solo necesitan cinco estrellas visibles.
 * En Home usamos glifos del sistema para evitar que star.woff prolongue la
 * cadena crítica por un recurso de apenas 1,5 KiB.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return;
		}
		?>
		<style id="elmercado-home-native-stars">
			body.elmercado-premium-home .star-rating,
			body.elmercado-premium-home .star-rating::before,
			body.elmercado-premium-home .star-rating span::before {
				font-family: Arial, Helvetica, sans-serif !important;
				letter-spacing: .04em !important;
			}
			body.elmercado-premium-home .star-rating::before {
				content: "★★★★★" !important;
				color: #d8d7d0 !important;
			}
			body.elmercado-premium-home .star-rating span::before {
				content: "★★★★★" !important;
				color: #d7a84f !important;
			}
		</style>
		<?php
	}
);
