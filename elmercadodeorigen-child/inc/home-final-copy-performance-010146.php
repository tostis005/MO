<?php
/**
 * Cierre editorial y de rendimiento de la portada 0.10.146.
 *
 * Refuerza la propuesta "del productor a tu mesa" sin comparaciones explícitas
 * y vuelve a retirar recursos que plugins recientes están encolando tarde en la
 * Home, después de la pasada normal de optimización.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Última revisión del discurso de portada sobre el HTML ya compuesto.
 *
 * @param string $html Documento HTML.
 * @return string
 */
function elmercado_home_final_copy_010146( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$copy = array(
		'Productores y artesanos con nombre propio' => 'Productores seleccionados, con nombre propio',
		'Pago seguro y atención cercana' => 'Compra segura y atención cercana',
		'Productos directamente desde su origen' => 'Del productor a tu mesa',
		'Aceites, ibéricos y despensa de productores' => 'Del productor a tu mesa',
		'Productos con origen.' => 'El sabor empieza donde se hace.',
		'Sabor con nombre propio.' => 'Y llega directo a tu mesa.',
		'Una selección de productos con procedencia clara, pensada para acercar a quienes producen y a quienes eligen qué llega a su mesa.' => 'Aceites, ibéricos y despensa elegidos por quién los hace, cómo los elabora y el cuidado que hay detrás. Del productor a tu casa, con el origen siempre a la vista.',
		'Descubre aceites, ibéricos y especialidades de despensa elegidos por su procedencia, su calidad y el trabajo de quienes los elaboran.' => 'Aceites, ibéricos y despensa elegidos por quién los hace, cómo los elabora y el cuidado que hay detrás. Del productor a tu casa, con el origen siempre a la vista.',
		'Descubrir productos' => 'Descubrir la selección',
		'Conocer a quienes los hacen' => 'Conocer a los productores',
		'Menos catálogo, más criterio' => 'Sabes quién lo hace',
		'Sabes quién lo elabora' => 'Elegido por cómo se elabora',
		'Elegimos lo que merece la pena' => 'Elegimos por lo que hay detrás',
		'Aceites, ibéricos y especialidades seleccionados por su calidad y su procedencia.' => 'Materia prima, tiempo, oficio y procedencia: cada producto tiene razones para estar aquí.',
		'El productor sigue visible' => 'El productor no desaparece',
		'Sabes quién elabora cada producto y puedes conocer su proyecto antes de comprar.' => 'Conoces quién lo elabora y la forma en que trabaja antes de elegir.',
		'Comprar resulta sencillo' => 'Del origen a tu puerta',
		'Información clara, pago seguro y atención cercana durante todo el pedido.' => 'Tu pedido se prepara con cuidado para que el producto llegue como merece.',
		'Empieza por lo que te apetece' => 'Empieza por el sabor que buscas',
		'Una despensa para disfrutarla de verdad' => 'Una despensa donde el origen se nota',
		'Aceites para cada día, ibéricos para compartir y productos con los que convertir una comida o un regalo en algo especial.' => 'Aceites con carácter, ibéricos con tiempo y especialidades de productores que cuidan cada detalle desde el origen.',
		'Descubre productos de origen y encuentra nuevas formas de llevarlos directamente a tu mesa.' => 'Aceites con carácter, ibéricos con tiempo y especialidades de productores que cuidan cada detalle desde el origen.',
		'Lo que más se repite' => 'Los que hacen volver',
		'Los productos que ya se han ganado un sitio en muchas mesas' => 'Productos que encuentran un sitio fijo en la mesa',
		'Ordenados por ventas reales: una forma sencilla de empezar por lo que más eligen quienes ya compran en El Mercado de Origen.' => 'Los más elegidos por quienes ya compran aquí: una buena forma de empezar por sabores que apetece volver a pedir.',
		'Ver todos los productos' => 'Ver toda la selección',
		'No vendemos de todo. Elegimos lo que tiene algo que aportar.' => 'La calidad empieza mucho antes de abrir el paquete.',
		'Reunimos productos cuya procedencia, forma de elaboración y calidad justifican que lleguen a tu mesa. Menos ruido, más producto y más personas visibles detrás.' => 'Empieza en la materia prima, en el campo, el secadero o el obrador; en el tiempo y en una forma concreta de hacer las cosas. Por eso aquí el productor y el origen siguen formando parte del producto.',
		'La procedencia no es una nota al pie: forma parte del valor de cada producto.' => 'Conocer la procedencia ayuda a entender el producto y todo el trabajo que hay detrás.',
		'La calidad se disfruta' => 'El oficio se nota',
		'Seleccionamos productos pensados para repetir, compartir y regalar con acierto.' => 'Seleccionamos productos en los que la materia prima, el tiempo y el cuidado tienen un papel real.',
		'Quien lo hace importa' => 'Quien lo hace sigue cerca',
		'La compra es digital, pero el productor y su manera de trabajar siguen en primer plano.' => 'La compra es online, pero detrás sigue habiendo una finca, un secadero, un obrador y alguien que responde por su trabajo.',
		'Conoce el origen' => 'Conoce a quien lo hace',
		'Detrás de cada producto hay una forma de hacer las cosas.' => 'Cuando un producto tiene algo especial, merece saber de dónde viene.',
		'Cada producto empieza mucho antes de llegar a tu mesa.' => 'Cuando un producto tiene algo especial, merece saber de dónde viene.',
		'Entra en las tiendas de los productores, descubre sus proyectos y elige sabiendo a quién apoyas con cada compra.' => 'Entra en las tiendas de los productores, conoce su historia y descubre qué hace distinta su manera de trabajar.',
	);

	return strtr( $html, $copy );
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		/*
		 * home-refresh arranca en -500. Este buffer es exterior a él para recibir
		 * primero sus sustituciones y aplicar después el discurso definitivo.
		 */
		ob_start( 'elmercado_home_final_copy_010146' );
	},
	-600
);

/**
 * Recursos que no tienen interfaz activa en la portada personalizada.
 * Se filtran también al imprimir porque varios plugins actuales encolan tarde.
 *
 * @return string[]
 */
function elmercado_home_unused_style_sources_010146(): array {
	return array(
		'/plugins/elementor/',
		'/uploads/elementor/css/',
		'/plugins/contact-form-7/',
		'/plugins/contact-form-7-honeypot/',
		'/plugins/slide-anything/',
		'/plugins/fluentform/',
		'/plugins/woo-discount-rules/',
		'/plugins/woo-discount-rules-pro/',
		'/plugins/wc-frontend-manager/',
		'/plugins/all-in-one-seo-pack/dist/Lite/assets/css/table-of-contents/',
		'/p/jetpack/',
		'/assets/client/blocks/wc-blocks.css',
		'fonts.googleapis.com/css?family=Roboto:',
		'fonts.googleapis.com/css?family=Roboto+',
		'fonts.googleapis.com/css?family=Roboto%20Slab',
	);
}

/**
 * @return string[]
 */
function elmercado_home_unused_script_sources_010146(): array {
	return array(
		'/plugins/elementor/',
		'/plugins/contact-form-7/',
		'/plugins/contact-form-7-honeypot/',
		'/plugins/slide-anything/',
		'/plugins/fluentform/',
		'/plugins/woo-discount-rules/',
		'/plugins/woo-discount-rules-pro/',
		'/plugins/wc-frontend-manager/',
		'cdn.trustindex.io/loader.js',
		'/wp-includes/js/jquery/ui/core.min.js',
		'/wp-includes/js/dist/hooks.min.js',
		'/wp-includes/js/dist/i18n.min.js',
		'/themes/woostify/assets/js/arrive.min.js',
		'/themes/woostify/assets/js/woocommerce/quantity-button.min.js',
		'/themes/woostify/assets/js/woocommerce/woocommerce.min.js',
	);
}

/**
 * Vuelve a limpiar las colas justo antes de imprimirlas.
 */
function elmercado_home_late_asset_cleanup_010146(): void {
	if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
		return;
	}

	global $wp_styles, $wp_scripts;

	if ( $wp_styles instanceof WP_Styles ) {
		foreach ( $wp_styles->registered as $handle => $asset ) {
			$src = isset( $asset->src ) ? (string) $asset->src : '';
			foreach ( elmercado_home_unused_style_sources_010146() as $needle ) {
				if ( '' !== $src && str_contains( $src, $needle ) ) {
					wp_dequeue_style( (string) $handle );
					break;
				}
			}
		}
	}

	if ( $wp_scripts instanceof WP_Scripts ) {
		foreach ( $wp_scripts->registered as $handle => $asset ) {
			$src = isset( $asset->src ) ? (string) $asset->src : '';
			foreach ( elmercado_home_unused_script_sources_010146() as $needle ) {
				if ( '' !== $src && str_contains( $src, $needle ) ) {
					wp_dequeue_script( (string) $handle );
					break;
				}
			}
		}
	}
}

add_action( 'wp_print_styles', 'elmercado_home_late_asset_cleanup_010146', PHP_INT_MAX );
add_action( 'wp_print_footer_scripts', 'elmercado_home_late_asset_cleanup_010146', 0 );

add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $html;
		}

		foreach ( elmercado_home_unused_style_sources_010146() as $needle ) {
			if ( str_contains( $href, $needle ) ) {
				return '';
			}
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $html;
		}

		foreach ( elmercado_home_unused_script_sources_010146() as $needle ) {
			if ( str_contains( $src, $needle ) ) {
				return '';
			}
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * performance-release marcaba todas las imágenes del hero como eager/high.
 * Restauramos la intención original: solo la primera compite por prioridad.
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attributes ): array {
		if ( ! function_exists( 'elmercado_is_optimized_home' ) || ! elmercado_is_optimized_home() ) {
			return $attributes;
		}

		$class = isset( $attributes['class'] ) ? (string) $attributes['class'] : '';
		if ( ! str_contains( $class, 'emo-hero-product-image' ) ) {
			return $attributes;
		}

		static $hero_image_index = 0;
		++$hero_image_index;

		$attributes['decoding'] = 'async';
		if ( 1 === $hero_image_index ) {
			$attributes['loading']       = 'eager';
			$attributes['fetchpriority'] = 'high';
		} else {
			$attributes['loading']       = 'lazy';
			$attributes['fetchpriority'] = 'low';
		}

		return $attributes;
	},
	PHP_INT_MAX,
	3
);
