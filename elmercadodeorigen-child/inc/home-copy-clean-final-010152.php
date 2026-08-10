<?php
/**
 * Copy final de Home 0.10.153.
 *
 * Mantiene el discurso abierto a cualquier categoría y concentra la propuesta
 * de valor en una idea sencilla: seleccionar proveedores con criterio, sin
 * recurrir a metáforas de mercado, despensa o puestos.
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
		'Productores seleccionados, con nombre propio' => 'Proveedores seleccionados con criterio',
		'Productores escogidos uno a uno' => 'Proveedores seleccionados con criterio',
		'Selección hecha con criterio' => 'Proveedores seleccionados con criterio',
		'Del origen a tu casa' => 'Compra sencilla y segura',
		'Del productor a tu casa' => 'Compra sencilla y segura',
		'Envíos preparados con cuidado' => 'Compra sencilla y segura',

		'Lo que merece la pena.' => 'Propuestas que destacan.',
		'Un mercado con criterio.' => 'Propuestas que destacan.',
		'Directo de quien lo hace.' => 'Seleccionadas con criterio.',
		'Cada incorporación cuenta.' => 'Seleccionadas con criterio.',
		'Elegimos uno a uno.' => 'Seleccionadas con criterio.',
		'Seleccionamos productor a productor y miramos primero el producto: ibéricos hechos con tiempo, aceites de almazara y una despensa que apetece volver a pedir. Tú eliges sabiendo quién está detrás.' => 'Buscamos proveedores que aporten algo distinto. Valoramos lo que ofrecen, cómo trabajan y qué hace especial su propuesta.',
		'Seleccionamos cada proyecto de forma individual. Buscamos propuestas con algo propio y una razón clara para estar aquí.' => 'Buscamos proveedores que aporten algo distinto. Valoramos lo que ofrecen, cómo trabajan y qué hace especial su propuesta.',
		'Entrar al mercado' => 'Ver productos',
		'Ver el mercado' => 'Ver productos',
		'Conocer los productores' => 'Conocer proveedores',
		'Conocer quién está detrás' => 'Conocer proveedores',
		'Productor a productor' => 'Selección',
		'Cada proyecto se revisa' => 'Cada propuesta se revisa',
		'Procedencia' => 'Diferencia',
		'Propuesta' => 'Diferencia',
		'Cada uno con su puesto' => 'Algo propio que aportar',
		'Una razón para estar aquí' => 'Algo propio que aportar',
		'Compra fácil y cercana' => 'Confianza',
		'Compra clara y segura' => 'Compra clara y segura',

		'El producto manda' => 'Buscamos algo distinto',
		'Seleccionamos uno a uno' => 'Buscamos algo distinto',
		'Elegimos por sabor, materia prima y forma de hacer las cosas. Si no nos apetece tenerlo en casa, no tiene sentido que esté en el mercado.' => 'Seleccionamos proveedores por lo que aportan, no por sumar opciones.',
		'Productos seleccionados por su calidad, su procedencia y todo lo que aporta conocer su origen.' => 'Seleccionamos proveedores por lo que aportan, no por sumar opciones.',
		'No buscamos llenar el catálogo. Elegimos cada proyecto de forma individual.' => 'Seleccionamos proveedores por lo que aportan, no por sumar opciones.',
		'Sabes a quién compras' => 'Valoramos cómo trabajan',
		'Miramos el conjunto' => 'Valoramos cómo trabajan',
		'Cada productor tiene su puesto, su historia y sus productos. Puedes conocerlos antes de decidir qué llevarte.' => 'Nos importa tanto lo que ofrecen como la forma en que lo hacen.',
		'Valoramos la propuesta completa antes de incorporarla al mercado.' => 'Nos importa tanto lo que ofrecen como la forma en que lo hacen.',
		'De allí a tu casa' => 'El criterio se mantiene',
		'La compra es online, pero la idea sigue siendo sencilla: acercar a quien hace un buen producto a quien quiere disfrutarlo.' => 'Pueden cambiar las categorías. La forma de seleccionar no cambia.',
		'Pueden cambiar las categorías. La forma de seleccionar no cambia.' => 'Pueden cambiar las categorías. La forma de seleccionar no cambia.',

		'De puesto en puesto' => 'Explora por categorías',
		'Explora el mercado' => 'Explora por categorías',
		'Una despensa que merece la pena conocer' => 'Encuentra lo que buscas',
		'Descubre la selección' => 'Encuentra lo que buscas',
		'Ibéricos con tiempo, aceites de almazara y otros productos elegidos productor a productor. Entra por lo que te apetezca y descubre quién está detrás.' => 'La selección seguirá creciendo con nuevas propuestas elegidas con el mismo criterio.',
		'El catálogo puede crecer. El criterio de selección se mantiene.' => 'La selección seguirá creciendo con nuevas propuestas elegidas con el mismo criterio.',
		'Nuevas categorías, nuevas propuestas y el mismo criterio en cada incorporación.' => 'La selección seguirá creciendo con nuevas propuestas elegidas con el mismo criterio.',

		'Para empezar bien' => 'Lo más elegido',
		'Los favoritos del mercado' => 'Los más elegidos',
		'Los productos que más eligen quienes ya compran aquí. Una forma sencilla de empezar por cosas que funcionan y apetece volver a pedir.' => 'Una forma rápida de descubrir lo que más eligen otros clientes.',
		'Una forma rápida de descubrir lo que más eligen quienes ya compran aquí.' => 'Una forma rápida de descubrir lo que más eligen otros clientes.',
		'Ver todo el mercado' => 'Ver todos los productos',

		'Cómo empezó' => 'Nuestro criterio',
		'Nuestra forma de seleccionar' => 'Nuestro criterio',
		'Primero vendimos lo nuestro. Después abrimos el mercado.' => 'No se trata de tener más. Se trata de elegir bien.',
		'Elegir bien antes que sumar.' => 'No se trata de tener más. Se trata de elegir bien.',
		'Seleccionar antes que acumular.' => 'No se trata de tener más. Se trata de elegir bien.',
		'En 2017 pusimos en marcha una tienda online para vender directamente lo que producíamos. Vimos que había clientes que querían buen producto, trazabilidad y saber de dónde venía. El siguiente paso fue abrir espacio a otros productores que también merecía la pena conocer.' => 'Buscamos proveedores que destaquen por lo que ofrecen o por cómo trabajan. Cada propuesta se valora antes de formar parte de la selección.',
		'El mercado puede crecer sin perder el criterio. Cada nueva propuesta se valora por separado.' => 'Buscamos proveedores que destaquen por lo que ofrecen o por cómo trabajan. Cada propuesta se valora antes de formar parte de la selección.',
		'El mercado está pensado para crecer sin convertirse en un catálogo sin filtro. Cada nueva incorporación se revisa de forma individual y tiene que aportar una razón clara para estar aquí.' => 'Buscamos proveedores que destaquen por lo que ofrecen o por cómo trabajan. Cada propuesta se valora antes de formar parte de la selección.',
		'Conoce nuestra historia' => 'Conoce el proyecto',
		'Producto antes que volumen' => 'Selección antes que volumen',
		'Preferimos elegir bien antes que llenar el catálogo: materia prima, elaboración, sabor y una calidad que se mantenga compra tras compra.' => 'Preferimos tener menos opciones y elegirlas bien.',
		'Preferimos incorporar menos propuestas y elegirlas bien.' => 'Preferimos tener menos opciones y elegirlas bien.',
		'Cada productor, su puesto' => 'Algo propio que aportar',
		'No escondemos a quien lo hace detrás de una ficha genérica. Puedes entrar en su tienda y conocer su trabajo.' => 'Buscamos proveedores que destaquen por lo que hacen y por cómo lo hacen.',
		'Buscamos proyectos que destaquen por lo que hacen y por cómo lo hacen.' => 'Buscamos proveedores que destaquen por lo que hacen y por cómo lo hacen.',
		'Directo y con trazabilidad' => 'Un criterio que no cambia',
		'Sabes quién produce, qué estás comprando y de dónde viene. Esa cercanía es la razón de ser del mercado.' => 'Pueden cambiar las categorías. La forma de seleccionar se mantiene.',
		'Pueden cambiar las categorías. La forma de seleccionar se mantiene.' => 'Pueden cambiar las categorías. La forma de seleccionar se mantiene.',

		'Conoce a quien está detrás de lo que compras.' => '¿Crees que tu propuesta encaja?',
		'¿Tu proyecto encaja en este mercado?' => '¿Crees que tu propuesta encaja?',
		'Entra en los puestos, descubre cómo trabaja cada productor y encuentra esas cosas que uno acaba recomendando después por su nombre.' => 'Buscamos proveedores con algo propio. Si crees que tu propuesta puede aportar, queremos conocerla.',
		'Buscamos propuestas con algo propio y una forma de trabajar que marque la diferencia. Si crees que encaja, queremos conocerla.' => 'Buscamos proveedores con algo propio. Si crees que tu propuesta puede aportar, queremos conocerla.',
		'Buscamos propuestas con algo propio. Si crees que la tuya encaja, queremos conocerla.' => 'Buscamos proveedores con algo propio. Si crees que tu propuesta puede aportar, queremos conocerla.',
	);

	$html = strtr( $html, $copy );

	/* El segundo CTA del hero sigue llevando al directorio de vendedores. */
	$hero = preg_replace_callback(
		'~<section class="emo-hero".*?</section>~s',
		static function ( array $matches ): string {
			return str_replace(
				array( 'Conocer los productores', 'Conocer quién está detrás' ),
				'Conocer proveedores',
				$matches[0]
			);
		},
		$html,
		1
	);
	if ( is_string( $hero ) ) {
		$html = $hero;
	}

	/*
	 * La última franja pasa a captar nuevas propuestas. El destino se ajusta al
	 * contacto para vendedores sin depender del nombre exacto del slug.
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
					'De puesto en puesto',
					'Conocer los productores',
				),
				array(
					'¿Quieres vender con nosotros?',
					'¿Quieres vender con nosotros?',
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
