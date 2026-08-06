<?php
/**
 * Sistema editorial global y utilidades del blog.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * URL pública del archivo de entradas.
 */
function elmercado_blog_url(): string {
	$page_for_posts = (int) get_option( 'page_for_posts' );

	return $page_for_posts > 0 ? get_permalink( $page_for_posts ) : home_url( '/blog/' );
}

/**
 * Extracto limpio y estable para tarjetas editoriales.
 */
function elmercado_editorial_excerpt( int $post_id, int $words = 28 ): string {
	$excerpt = get_the_excerpt( $post_id );

	if ( '' === trim( $excerpt ) ) {
		$excerpt = get_post_field( 'post_content', $post_id );
	}

	$excerpt = strip_shortcodes( (string) $excerpt );
	$excerpt = wp_strip_all_tags( $excerpt, true );

	return wp_trim_words( trim( $excerpt ), max( 12, $words ), '…' );
}

/**
 * Tiempo de lectura aproximado de una entrada.
 */
function elmercado_reading_time( int $post_id ): int {
	$content = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post_id ) ) );
	$words   = preg_split( '/\s+/u', trim( $content ) );
	$count   = is_array( $words ) ? count( array_filter( $words ) ) : 0;

	return max( 1, (int) ceil( $count / 210 ) );
}

/**
 * Tarjeta reutilizable para archivo, categorías y relacionados.
 */
function elmercado_render_post_card( int $post_id, bool $featured = false ): string {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$categories = get_the_category( $post_id );
	$category   = ! empty( $categories ) ? $categories[0] : null;
	$classes    = $featured ? 'emo-article-card emo-article-card--featured' : 'emo-article-card';
	$image      = has_post_thumbnail( $post_id )
		? get_the_post_thumbnail(
			$post_id,
			$featured ? 'large' : 'medium_large',
			array(
				'loading'  => $featured ? 'eager' : 'lazy',
				'decoding' => 'async',
				'alt'      => get_the_title( $post_id ),
			)
		)
		: '<span class="emo-article-card__placeholder" aria-hidden="true"></span>';

	$category_html = '';
	if ( $category instanceof WP_Term ) {
		$category_html = sprintf(
			'<a class="emo-article-card__category" href="%1$s">%2$s</a>',
			esc_url( get_category_link( $category ) ),
			esc_html( $category->name )
		);
	}

	return sprintf(
		'<article class="%1$s"><a class="emo-article-card__media" href="%2$s">%3$s</a><div class="emo-article-card__body"><div class="emo-article-card__meta">%4$s<span>%5$s</span><span>%6$s min de lectura</span></div><h2><a href="%2$s">%7$s</a></h2><p>%8$s</p><a class="emo-article-card__link" href="%2$s">Leer el artículo <span aria-hidden="true">→</span></a></div></article>',
		esc_attr( $classes ),
		esc_url( get_permalink( $post_id ) ),
		$image,
		$category_html,
		esc_html( get_the_date( 'j M Y', $post_id ) ),
		esc_html( (string) elmercado_reading_time( $post_id ) ),
		esc_html( get_the_title( $post_id ) ),
		esc_html( elmercado_editorial_excerpt( $post_id, $featured ? 38 : 25 ) )
	);
}

/**
 * Carga la capa editorial después del resto del sistema visual.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'elmercado-editorial',
			ELMERCADO_THEME_URL . '/assets/css/editorial.css',
			array( 'elmercado-premium' ),
			elmercado_asset_version( '/assets/css/editorial.css' )
		);
	},
	10050
);

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_home() || is_archive() || is_single() ) {
			$classes[] = 'elmercado-editorial-content';
		}

		if ( is_page( array( 'quienes-somos', 'el-mercado-de-origen' ) ) ) {
			$classes[] = 'elmercado-about-page';
		}

		if ( is_page( array( 'contacto', 'contacto-productores' ) ) ) {
			$classes[] = 'elmercado-contact-page';
		}

		return $classes;
	}
);

/**
 * Revisión final de la portada: lenguaje de marca general, sin depender de las
 * categorías disponibles en un momento concreto.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$replacements = array(
			'Aceites, jamones, paletas ibéricas y naranjas' => 'Productos con origen, directos de sus productores',
			'Seleccionamos productores que cuidan cada detalle para que recibas en casa aceites de oliva virgen extra, jamones y paletas ibéricas y naranjas de temporada con todo su sabor y su origen intactos.' => 'Reunimos productos elegidos por su origen, la forma en que se elaboran y las personas que hay detrás. Compra de manera más directa, con información clara y atención cercana.',
			'Del productor, sin rodeos' => 'Una compra más directa',
			'Productos elegidos para repetir' => 'Productos que hablan por sí solos',
			'Atención antes y después de tu compra' => 'Atención cercana en cada paso',
			'Directo desde el origen' => 'Origen que puedes conocer',
			'Aceites, jamones, paletas y naranjas enviados desde quienes los producen.' => 'Productos seleccionados y enviados con la cercanía de quienes conocen su origen.',
			'Conoces al productor, su forma de trabajar y el origen de lo que llega a tu casa.' => 'Conoces quién está detrás, cómo trabaja y qué hace diferente cada producto.',
			'Elige tu próximo favorito' => 'Explora la selección',
			'Aceite, ibéricos y fruta con origen' => 'Productos con origen para elegir mejor',
			'AOVE para cada día, jamones y paletas para disfrutar y regalar, y naranjas de temporada enviadas directamente desde quien las cultiva.' => 'Descubre propuestas para disfrutar, compartir o regalar, siempre con información clara sobre su procedencia y quién las hace posibles.',
			'Los productos que más vuelven a entrar en el carrito' => 'Los productos que más eligen nuestros clientes',
			'Una selección ordenada por ventas reales: aceites, jamones y paletas que ya han convencido a quienes compran directamente a nuestros productores.' => 'Una selección ordenada por ventas reales para empezar por los productos que más confianza generan entre nuestros clientes.',
			'Acortamos la distancia entre quien lo hace bien y quien sabe disfrutarlo.' => 'Acortamos la distancia entre quienes producen y quienes quieren elegir mejor.',
			'El Mercado de Origen reúne productores que trabajan con cuidado y clientes que buscan algo más que una etiqueta. Tú eliges y el producto viaja desde su origen hasta tu casa.' => 'El Mercado de Origen conecta a productores que cuidan lo que hacen con personas que valoran el origen, la calidad y una forma de comprar más transparente.',
			'Detrás de cada aceite, cada jamón y cada naranja hay alguien que se juega su nombre.' => 'Detrás de cada producto hay una forma de hacer las cosas.',
			'Descubre sus proyectos, cómo trabajan y por qué sus productos merecen llegar directamente a tu casa.' => 'Descubre sus proyectos, cómo trabajan y qué aporta cada uno a la selección del mercado.',
			'Directo del productor' => 'Con origen propio',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	},
	100
);

/**
 * Mejora de las páginas informativas sin modificar su contenido almacenado.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_singular( 'page' ) || ! in_the_loop() || ! is_main_query() || is_front_page() ) {
			return $content;
		}

		$slug = (string) get_post_field( 'post_name', get_queried_object_id() );

		if ( in_array( $slug, array( 'quienes-somos', 'el-mercado-de-origen' ), true ) ) {
			return '<div class="emo-about-layout">'
				. '<section class="emo-about-intro"><div><span class="emo-kicker">El proyecto</span><h2>Una forma más directa de acercar productos y personas.</h2></div><p>El Mercado de Origen nace para dar visibilidad a quienes producen, reducir distancias innecesarias y facilitar una compra en la que el origen vuelve a importar.</p></section>'
				. '<div class="emo-about-values"><article><span>01</span><h3>Origen visible</h3><p>Información clara sobre quién está detrás y cómo trabaja.</p></article><article><span>02</span><h3>Relación más directa</h3><p>Menos distancia entre el productor y quien elige el producto.</p></article><article><span>03</span><h3>Valor compartido</h3><p>Una compra que reconoce el trabajo bien hecho y genera confianza.</p></article></div>'
				. '<section class="emo-about-copy"><span class="emo-kicker">Nuestra historia</span>' . $content . '</section>'
				. '</div>';
		}

		if ( 'contacto' === $slug ) {
			return '<div class="emo-contact-layout"><aside class="emo-contact-aside"><span class="emo-kicker emo-kicker--light">Hablemos</span><h2>Estamos para ayudarte a elegir y comprar con confianza.</h2><p>Cuéntanos qué necesitas. Te responderemos con información clara y una atención cercana, antes o después de tu pedido.</p><div class="emo-contact-points"><span>Consultas sobre productos</span><span>Ayuda con pedidos</span><span>Información para productores</span></div></aside><section class="emo-contact-form">' . $content . '</section></div>';
		}

		if ( 'contacto-productores' === $slug ) {
			return '<div class="emo-contact-layout"><aside class="emo-contact-aside"><span class="emo-kicker emo-kicker--light">Para productores</span><h2>Un mercado en el que tu trabajo sigue teniendo nombre propio.</h2><p>Háblanos de tu proyecto, de tus productos y de la forma en que trabajas. Queremos conocer qué te hace diferente.</p></aside><section class="emo-contact-form">' . $content . '</section></div>';
		}

		if ( 'productores' === $slug ) {
			return '<section class="emo-producers-intro"><span class="emo-kicker">El origen del mercado</span><h2>Conoce a quienes están detrás de cada producto.</h2><p>Proyectos con identidad propia, formas de trabajar distintas y una misma voluntad: que el producto llegue con todo su valor hasta quien lo elige.</p></section>' . $content;
		}

		if ( 'affiliates' === $slug && str_contains( $content, '[couponaffiliates]' ) ) {
			return '<section class="emo-empty-state"><span class="emo-kicker">Área privada</span><h2>El panel de colaboradores no está disponible en este momento.</h2><p>Contacta con nuestro equipo para recibir ayuda con tu acceso.</p><a class="emo-button" href="' . esc_url( elmercado_page_url( array( 'contacto' ), '/contacto/' ) ) . '">Contactar</a></section>';
		}

		return $content;
	},
	90
);

/**
 * Título e introducción comercial de la tienda.
 */
add_filter(
	'woocommerce_page_title',
	static function ( string $title ): string {
		return is_shop() ? 'Productos' : $title;
	}
);

add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( ! is_shop() ) {
			return;
		}
		?>
		<div class="emo-shop-lead">
			<span class="emo-kicker">Selección del mercado</span>
			<p>Explora productos con origen, productores visibles e información clara para elegir con confianza.</p>
		</div>
		<?php
	},
	3
);

/**
 * La wishlist se retiró de la experiencia pública; su URL antigua vuelve a la
 * tienda para no dejar una pantalla huérfana.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( is_page( 'lista-de-deseos' ) && ! is_admin() ) {
			$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
			wp_safe_redirect( $shop_url, 302 );
			exit;
		}
	}
);

/**
 * El blog se plantea como contenido editorial, sin comentarios públicos.
 */
add_filter(
	'comments_open',
	static function ( bool $open, int $post_id ): bool {
		return 'post' === get_post_type( $post_id ) ? false : $open;
	},
	20,
	2
);

add_filter(
	'pings_open',
	static function ( bool $open, int $post_id ): bool {
		return 'post' === get_post_type( $post_id ) ? false : $open;
	},
	20,
	2
);
