<?php
/**
 * Copy final de Home 0.10.153.
 *
 * Mantiene el discurso abierto a cualquier categoría y concentra la propuesta
 * de valor en una idea sencilla: cada proyecto se selecciona de forma
 * individual y debe aportar una razón clara para estar en el mercado.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Simplifica la voz de la portada una vez aplicadas todas las capas anteriores.
 *
 * @param string $html Documento HTML.
 * @return string
 */
function elmercado_home_copy_clean_final_010152( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$copy = array(
		'Productores escogidos uno a uno' => 'Selección hecha con criterio',
		'Del origen a tu casa' => 'Envíos preparados con cuidado',
		'Del productor a tu casa' => 'Selección con criterio',

		'Lo que merece la pena.' => 'Un mercado con criterio.',
		'Directo de quien lo hace.' => 'Elegimos uno a uno.',
		'Seleccionamos productor a productor y miramos primero el producto: ibéricos hechos con tiempo, aceites de almazara y una despensa que apetece volver a pedir. Tú eliges sabiendo quién está detrás.' => 'Seleccionamos cada proyecto de forma individual. Buscamos propuestas con algo propio y una razón clara para estar aquí.',
		'Entrar al mercado' => 'Ver el mercado',
		'Productor a productor' => 'Cada proyecto se revisa',
		'Procedencia' => 'Propuesta',
		'Cada uno con su puesto' => 'Algo propio que aportar',
		'Compra fácil y cercana' => 'Compra clara y segura',

		'El producto manda' => 'Seleccionamos uno a uno',
		'Elegimos por sabor, materia prima y forma de hacer las cosas. Si no nos apetece tenerlo en casa, no tiene sentido que esté en el mercado.' => 'No buscamos llenar el catálogo. Elegimos cada proyecto de forma individual.',
		'Productos seleccionados por su calidad, su procedencia y todo lo que aporta conocer su origen.' => 'No buscamos llenar el catálogo. Elegimos cada proyecto de forma individual.',
		'Sabes a quién compras' => 'Miramos el conjunto',
		'Cada productor tiene su puesto, su historia y sus productos. Puedes conocerlos antes de decidir qué llevarte.' => 'Valoramos la propuesta completa antes de incorporarla al mercado.',
		'De allí a tu casa' => 'El criterio se mantiene',
		'La compra es online, pero la idea sigue siendo sencilla: acercar a quien hace un buen producto a quien quiere disfrutarlo.' => 'Pueden cambiar las categorías. La forma de seleccionar no cambia.',

		'De puesto en puesto' => 'Explora el mercado',
		'Una despensa que merece la pena conocer' => 'Descubre la selección',
		'Ibéricos con tiempo, aceites de almazara y otros productos elegidos productor a productor. Entra por lo que te apetezca y descubre quién está detrás.' => 'El catálogo puede crecer. El criterio de selección se mantiene.',

		'Para empezar bien' => 'Lo más elegido',
		'Los productos que más eligen quienes ya compran aquí. Una forma sencilla de empezar por cosas que funcionan y apetece volver a pedir.' => 'Una forma rápida de descubrir lo que más eligen quienes ya compran aquí.',

		'Cómo empezó' => 'Nuestra forma de seleccionar',
		'Primero vendimos lo nuestro. Después abrimos el mercado.' => 'Elegir bien antes que sumar.',
		'En 2017 pusimos en marcha una tienda online para vender directamente lo que producíamos. Vimos que había clientes que querían buen producto, trazabilidad y saber de dónde venía. El siguiente paso fue abrir espacio a otros productores que también merecía la pena conocer.' => 'El mercado puede crecer sin perder el criterio. Cada nueva propuesta se valora por separado.',
		'Conoce nuestra historia' => 'Conoce el proyecto',
		'Producto antes que volumen' => 'Selección antes que volumen',
		'Preferimos elegir bien antes que llenar el catálogo: materia prima, elaboración, sabor y una calidad que se mantenga compra tras compra.' => 'Preferimos incorporar menos propuestas y elegirlas bien.',
		'Cada productor, su puesto' => 'Algo propio que aportar',
		'No escondemos a quien lo hace detrás de una ficha genérica. Puedes entrar en su tienda y conocer su trabajo.' => 'Buscamos proyectos que destaquen por lo que hacen y por cómo lo hacen.',
		'Directo y con trazabilidad' => 'Un criterio que no cambia',
		'Sabes quién produce, qué estás comprando y de dónde viene. Esa cercanía es la razón de ser del mercado.' => 'Pueden cambiar las categorías. La forma de seleccionar se mantiene.',

		'Conoce a quien está detrás de lo que compras.' => '¿Tu proyecto encaja en este mercado?',
		'Entra en los puestos, descubre cómo trabaja cada productor y encuentra esas cosas que uno acaba recomendando después por su nombre.' => 'Buscamos propuestas con algo propio. Si crees que la tuya encaja, queremos conocerla.',
	);

	$html = strtr( $html, $copy );

	/* El segundo CTA del hero sigue llevando al directorio de vendedores. */
	$hero = preg_replace_callback(
		'~<section class="emo-hero".*?</section>~s',
		static function ( array $matches ): string {
			return str_replace( 'Conocer los productores', 'Conocer quién está detrás', $matches[0] );
		},
		$html,
		1
	);
	if ( is_string( $hero ) ) {
		$html = $hero;
	}

	/*
	 * La última franja pasa a ser captación de nuevas propuestas. El destino se
	 * ajusta al contacto para vendedores, sin depender del nombre exacto del slug.
	 */
	$contact_url = function_exists( 'elmercado_page_url' )
		? elmercado_page_url( array( 'contacto-productores', 'contacto-proveedores', 'vende-con-nosotros' ), '/contacto-productores/' )
		: home_url( '/contacto-productores/' );

	$vendor = preg_replace_callback(
		'~<section class="emo-section emo-vendor-cta".*?</section>~s',
		static function ( array $matches ) use ( $contact_url ): string {
			$section = str_replace(
				array(
					'Explora el mercado',
					'Conocer los productores',
				),
				array(
					'¿Quieres vender aquí?',
					'Cuéntanos tu propuesta',
				),
				$matches[0]
			);

			$section_with_url = preg_replace_callback(
				'~(<a class="emo-button emo-button--dark" href=")[^"]+("[^>]*>)~',
				static function ( array $link_matches ) use ( $contact_url ): string {
					return $link_matches[1] . esc_url( $contact_url ) . $link_matches[2];
				},
				$section,
				1
			);

			return is_string( $section_with_url ) ? $section_with_url : $section;
		},
		$html,
		1
	);

	return is_string( $vendor ) ? $vendor : $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		/* Capa exterior final: recibe el HTML después de todas las revisiones previas. */
		ob_start( 'elmercado_home_copy_clean_final_010152' );
	},
	-800
);
