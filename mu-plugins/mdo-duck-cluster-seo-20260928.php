<?php
/**
 * Plugin Name: MDO Duck Cluster SEO 2026-09-28
 * Description: Discovery, SERP metadata and contextual internal linking for the editorial duck cluster.
 * Version: 2026.09.29.2
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

const EMDO_DUCK_CLUSTER_META_20260928 = '_emdo_blog_cluster';
function emdo_duck_is_post_20260928( int $post_id = 0 ): bool {
	if ( $post_id <= 0 ) { $post_id = (int) get_queried_object_id(); }
	return $post_id > 0
		&& 'post' === get_post_type( $post_id )
		&& 'duck' === (string) get_post_meta( $post_id, EMDO_DUCK_CLUSTER_META_20260928, true );
}

function emdo_duck_current_slug_20260928(): string {
	if ( is_admin() || ! is_singular( 'post' ) || ! emdo_duck_is_post_20260928() ) { return ''; }
	return (string) get_post_field( 'post_name', get_queried_object_id() );
}

/** Concise SERP titles for visible titles likely to truncate. */
function emdo_duck_serp_titles_20260928(): array {
	return array(
		'carne-de-pato-guia-completa-cortes-sabor-preparacion' => 'Carne de pato: cortes, sabor, preparación y cómo elegirla',
		'foie-gras-de-pato-que-es-tipos-diferencias-como-elegirlo' => 'Foie gras de pato: cómo elegir formato y conservación',
		'magret-de-pato-que-es-como-cocinarlo-punto-perfecto' => 'Magret de pato: qué corte es y cómo cocinarlo',
		'foie-gras-pate-mousse-parfait-bloc-micuit-diferencias' => 'Foie, paté, mousse, parfait, bloc y micuit: diferencias',
		'pato-fresco-principales-cortes-y-como-cocinar-cada-uno' => 'Pato fresco: cortes principales y cómo cocinar cada uno',
		'como-preparar-confit-de-pato-al-horno-piel-crujiente' => 'Confit de pato crujiente: horno, grill y air fryer',
		'como-servir-foie-gras-temperatura-corte-pan-acompanamientos' => 'Cómo servir foie gras: temperatura, corte y presentación',
		'mousse-de-pato-que-es-diferencia-pate-foie' => 'Mousse de pato: diferencias con paté y foie gras',
		'foie-gras-entier-bloc-y-bloc-con-trozos-diferencias' => 'Foie gras entier vs bloc y bloc con trozos',
		'foie-gras-fresco-micuit-conserva-diferencias-usos' => 'Foie fresco, micuit o conserva: diferencias y usos',
		'parfait-de-foie-y-mousse-de-foie-diferencias' => 'Parfait de foie vs mousse: composición, textura y sabor',
		'con-que-acompanar-foie-gras-pan-frutas-mermeladas' => 'Con qué acompañar foie gras: panes, frutas y contrastes',
		'magret-de-pato-a-la-naranja-receta-claves' => 'Magret de pato a la naranja: receta y claves',
		'confit-de-pato-al-horno-temperatura-tiempo-trucos' => 'Confit de pato al horno: temperatura, tiempo y trucos',
		'como-calentar-confit-de-pato-conserva-envasado-vacio' => 'Cómo calentar confit de pato en conserva o al vacío',
		'que-hacer-con-confit-de-pato-desmigado-ideas' => 'Confit de pato desmigado: ideas para aprovecharlo',
		'croquetas-confit-de-pato-como-aprovecharlo' => 'Croquetas de confit de pato: receta y trucos',
		'mousse-de-pato-pimienta-cognac-orujo-diferencias' => 'Mousse de pato: pimienta, coñac u orujo, diferencias',
		'muslo-de-pato-fresco-y-confit-diferencias' => 'Muslo fresco vs confit de pato: diferencias y cocción',
		'pato-entero-al-horno-jugoso-crujiente' => 'Pato entero al horno: jugoso por dentro y crujiente',
		'carne-de-pato-vs-pollo-diferencias-sabor-cocina' => 'Carne de pato vs pollo: diferencias de sabor y cocina',
		'valor-nutricional-carne-de-pato-proteinas-grasas-calorias' => 'Carne de pato: proteínas, grasas, calorías y nutrición',
		'como-conservar-congelar-descongelar-carne-de-pato' => 'Cómo conservar, congelar y descongelar carne de pato',
		'salsas-para-pato-naranja-frutos-rojos-pedro-ximenez' => 'Salsas para pato: naranja, frutos rojos y Pedro Ximénez',
		'menu-navidad-con-pato-foie-magret-confit-jamon' => 'Menú de Navidad con pato: foie, magret, confit y jamón',
		'productos-de-pato-para-regalar-foie-pates-jamon-lotes' => 'Productos de pato para regalar: foie, paté y jamón',
	);
}

add_filter( 'aioseo_title', static function ( $title ): string {
	if ( is_category( 'pato' ) ) {
		return 'Pato: carne, magret, foie, confit y recetas';
	}
	$slug = emdo_duck_current_slug_20260928();
	if ( '' === $slug ) { return (string) $title; }
	$map = emdo_duck_serp_titles_20260928();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : (string) get_the_title( get_queried_object_id() );
}, 35 );

add_filter( 'aioseo_description', static function ( $description ): string {
	if ( is_category( 'pato' ) ) {
		return 'Guías sobre carne de pato, magret, foie gras, confit, jamón de pato, cortes, cocción, conservación, recetas, salsas y acompañamientos.';
	}
	if ( ! is_singular( 'post' ) || ! emdo_duck_is_post_20260928() ) { return (string) $description; }
	$excerpt = trim( wp_strip_all_tags( (string) get_post_field( 'post_excerpt', get_queried_object_id() ) ) );
	return '' !== $excerpt ? $excerpt : (string) $description;
}, 35 );

/**
 * Add a small number of editorially relevant backlinks from existing authority
 * posts outside the duck cluster. These are unique prompts, not keyword stuffing.
 */
add_filter( 'the_content', static function ( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || emdo_duck_is_post_20260928() ) {
		return $content;
	}
	$slug = (string) get_post_field( 'post_name', get_the_ID() );
	$rules = array(
		'como-elegir-lote-productos-gourmet-presupuesto-destinatario-conservacion' => array(
			'target' => '/productos-de-pato-para-regalar-foie-pates-jamon-lotes/',
			'text'   => 'Si el destinatario disfruta de productos cárnicos gourmet, puedes comparar formatos en nuestra guía de <a href="%s">productos de pato para regalar</a>.',
		),
		'como-comprar-alimentos-gourmet-online-que-mirar-ademas-precio' => array(
			'target' => '/foie-gras-de-pato-que-es-tipos-diferencias-como-elegirlo/',
			'text'   => 'En categorías donde la denominación cambia mucho el producto, como el foie, conviene revisar antes nuestra guía para <a href="%s">elegir foie gras según formato, uso y conservación</a>.',
		),
		'como-guardar-pedido-alimentacion-nevera-despensa-congelador' => array(
			'target' => '/como-conservar-congelar-descongelar-carne-de-pato/',
			'text'   => 'Para una aplicación concreta de estas reglas a las aves, consulta cómo <a href="%s">conservar, congelar y descongelar carne de pato</a>.',
		),
		'carne-con-hueso-o-deshuesada-rendimiento-coccion-precio-real' => array(
			'target' => '/cuanta-carne-de-pato-calcular-por-persona/',
			'text'   => 'El hueso también cambia el cálculo en otras carnes: aquí tienes una referencia específica para <a href="%s">calcular carne de pato por persona</a>.',
		),
		'cuanto-wagyu-comprar-por-persona' => array(
			'target' => '/cuanta-carne-de-pato-calcular-por-persona/',
			'text'   => 'Si estás organizando un menú con otra carne premium, consulta también <a href="%s">cuánto pato calcular por persona según el corte</a>.',
		),
		'calorias-wagyu-valor-nutricional' => array(
			'target' => '/valor-nutricional-carne-de-pato-proteinas-grasas-calorias/',
			'text'   => 'Para comparar cómo cambia la composición en otra carne con mucha variación por piel y corte, consulta el <a href="%s">valor nutricional de la carne de pato</a>.',
		),
		'como-conservar-wagyu-nevera-congelador' => array(
			'target' => '/como-conservar-congelar-descongelar-carne-de-pato/',
			'text'   => 'Las pautas de frío cambian con cada producto; para otra carne fresca puedes revisar la guía de <a href="%s">conservación y congelación del pato</a>.',
		),
		'acompanamientos-para-wagyu-guarniciones-salsas' => array(
			'target' => '/salsas-para-pato-naranja-frutos-rojos-pedro-ximenez/',
			'text'   => 'Si buscas una lógica de contraste aplicada a otra carne intensa, compara estas ideas con nuestras <a href="%s">salsas para pato</a>.',
		),
		'wagyu-para-regalar-que-elegir' => array(
			'target' => '/productos-de-pato-para-regalar-foie-pates-jamon-lotes/',
			'text'   => 'Otra opción para un regalo gastronómico es explorar <a href="%s">foie, patés, jamón y otros productos de pato</a>.',
		),
	);
	if ( empty( $rules[ $slug ] ) ) { return $content; }
	$target = home_url( $rules[ $slug ]['target'] );
	if ( false !== strpos( $content, $target ) || false !== strpos( $content, $rules[ $slug ]['target'] ) ) { return $content; }
	$paragraph = sprintf( $rules[ $slug ]['text'], esc_url( $target ) );
	return $content . "\n<p class=\"emdo-duck-context-link\">" . $paragraph . '</p>';
}, 48 );

/**
 * Give duck posts a stable topic path at the end of the article body. The
 * existing related-reading component will then add further same-category links.
 */
add_filter( 'the_content', static function ( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! emdo_duck_is_post_20260928() ) {
		return $content;
	}
	$term = get_term_by( 'slug', 'pato', 'category' );
	if ( ! $term instanceof WP_Term ) { return $content; }
	$link = get_category_link( $term );
	if ( is_wp_error( $link ) ) { return $content; }
	if ( false !== strpos( $content, 'data-emdo-duck-hub-link' ) ) { return $content; }
	return $content
		. "\n<aside class=\"emdo-duck-hub-link\" data-emdo-duck-hub-link=\"1\">"
		. '<strong>Más guías sobre pato</strong>'
		. '<p>Explora el índice completo de carne de pato, magret, foie gras, confit, jamón de pato, recetas y conservación.</p>'
		. '<a href="' . esc_url( $link ) . '">Ver todas las guías de pato →</a>'
		. '</aside>';
}, 47 );

add_action( 'wp_head', static function (): void {
	if ( ! is_singular( 'post' ) || ! emdo_duck_is_post_20260928() ) { return; }
	?>
	<style id="emdo-duck-hub-link-css">
		.emdo-duck-hub-link{margin:34px 0 8px;padding:20px 22px;border:1px solid rgba(13,33,27,.13);border-radius:14px;background:#f8f5ef}
		.emdo-duck-hub-link strong{display:block;margin-bottom:5px;font-size:1.05em}
		.emdo-duck-hub-link p{margin:0 0 8px!important}
		.emdo-duck-hub-link a{font-weight:700;text-decoration:underline;text-underline-offset:3px}
	</style>
	<?php
}, 120 );
