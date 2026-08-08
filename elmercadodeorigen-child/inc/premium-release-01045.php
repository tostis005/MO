<?php
/**
 * Cierre premium de redacción y filtros responsivos 0.10.45.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Copy final de marca. Se aplica al HTML ya compuesto para desacoplar el
 * discurso público de categorías concretas que pueden cambiar con el catálogo.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$replacements = array(
			'Productos con origen, directos de sus productores' => 'Productos directamente desde su origen',
			'Del productor, directo a tu casa.' => 'Del origen a tu mesa, de forma más directa.',
			'Descubre productos directamente desde su origen, para acercar lo que se produce a quienes quieren disfrutarlo en casa.' => 'Una selección de productos con procedencia clara, pensada para acercar a quienes producen y a quienes eligen qué llega a su mesa.',
			'Comprar productos' => 'Explorar productos',
			'Ver productores' => 'Conocer productores',
			'Una compra más directa' => 'Más cerca del origen',
			'Productos que hablan por sí solos' => 'Una selección con criterio',
			'Atención cercana en cada paso' => 'Acompañamiento cuando lo necesitas',
			'Origen que puedes conocer' => 'Procedencia clara',
			'Productos seleccionados y enviados con la cercanía de quienes conocen su origen.' => 'Productos elegidos por su procedencia y por el valor de quienes los hacen posibles.',
			'Conoces quién está detrás, cómo trabaja y qué hace diferente cada producto.' => 'Puedes conocer quién está detrás, cómo trabaja y qué aporta al producto que eliges.',
			'Explora la selección' => 'Elige desde el origen',
			'Productos con origen para elegir mejor' => 'Productos con procedencia para elegir con criterio',
			'Descubre propuestas para disfrutar, compartir o regalar, siempre con información clara sobre su procedencia y quién las hace posibles.' => 'Encuentra productos para disfrutar, compartir o regalar con información clara sobre su procedencia y sobre quién los hace posibles.',
			'Los productos que más eligen nuestros clientes' => 'Los productos que más se eligen',
			'Una selección ordenada por ventas reales para empezar por los productos que más confianza generan entre nuestros clientes.' => 'Una selección ordenada por ventas reales para descubrir qué productos vuelven una y otra vez a la cesta.',
			'Acortamos la distancia entre quienes producen y quienes quieren elegir mejor.' => 'Acortamos la distancia entre el origen y tu mesa.',
			'El Mercado de Origen conecta a productores que cuidan lo que hacen con personas que valoran el origen, la calidad y una forma de comprar más transparente.' => 'El Mercado de Origen acerca productos con procedencia a personas que quieren comprar con más información, más criterio y menos distancia.',
			'Detrás de cada producto hay una forma de hacer las cosas.' => 'Cada producto empieza mucho antes de llegar a tu mesa.',
			'Descubre sus proyectos, cómo trabajan y qué aporta cada uno a la selección del mercado.' => 'Conoce los proyectos, la forma de trabajar y las decisiones que dan identidad a cada producto.',
			'Con origen propio' => 'Con procedencia visible',
			'Conoce a quienes están detrás de cada producto.' => 'Conoce el origen a través de quienes lo hacen posible.',
			'Proyectos con identidad propia, formas de trabajar distintas y una misma voluntad: que el producto llegue con todo su valor hasta quien lo elige.' => 'Proyectos con identidad propia y formas distintas de trabajar, unidos por una idea sencilla: que el valor del origen también llegue a quien compra.',
			'Estamos para ayudarte a elegir y comprar con confianza.' => 'Estamos para ayudarte antes y después de tu compra.',
			'Cuéntanos qué necesitas. Te responderemos con información clara y una atención cercana, antes o después de tu pedido.' => 'Cuéntanos qué necesitas. Te responderemos de forma clara y cercana, tanto si estás eligiendo como si ya has hecho tu pedido.',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	},
	999
);

/**
 * Conservamos únicamente la capa visual heredada. La estructura y los eventos
 * del drawer los gestiona el controlador canónico posterior.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-premium-shop-filter-01045">
			@media (max-width:1100px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle {
					display:flex !important;
					width:100% !important;
					min-height:44px !important;
					align-items:center !important;
					justify-content:space-between !important;
					gap:10px !important;
					margin:0 0 18px !important;
					padding:0 14px !important;
					border:1px solid rgba(23,63,50,.12) !important;
					border-radius:12px !important;
					background:#f7f9f6 !important;
					color:#173f32 !important;
					font-size:12px !important;
					font-weight:800 !important;
					box-shadow:none !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-shell:not([hidden]) { display:block !important; }
				body.elmercado-child-theme .emo-mobile-filter-shell[hidden] { display:none !important; }
			}
			@media (min-width:1101px) {
				body.elmercado-child-theme .emo-mobile-filter-toggle,
				body.elmercado-child-theme .emo-mobile-filter-shell { display:none !important; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
