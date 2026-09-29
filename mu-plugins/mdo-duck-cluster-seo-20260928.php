<?php
/**
 * Plugin Name: MDO Duck Cluster SEO 2026-09-28
 * Description: Discovery, SERP metadata and contextual internal linking for the editorial duck cluster.
 * Version: 2026.09.30.1
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

/** Unique, concise title links for all 60 cluster URLs. */
function emdo_duck_serp_titles_20260928(): array {
	return array(
		'carne-de-pato-guia-completa-cortes-sabor-preparacion' => 'Carne de pato: cortes, sabor y cómo elegirla',
		'foie-gras-de-pato-que-es-tipos-diferencias-como-elegirlo' => 'Foie gras de pato: tipos, formatos y cómo elegirlo',
		'magret-de-pato-que-es-como-cocinarlo-punto-perfecto' => 'Magret de pato: qué corte es y cómo cocinarlo',
		'confit-de-pato-que-es-como-se-prepara-y-con-que-acompanarlo' => 'Confit de pato: qué es, preparación y acompañamientos',
		'jamon-de-pato-que-es-como-se-elabora-y-como-se-come' => 'Jamón de pato: qué es, elaboración y cómo servirlo',
		'foie-gras-pate-mousse-parfait-bloc-micuit-diferencias' => 'Foie, paté, mousse, parfait, bloc y micuit: diferencias',
		'pato-fresco-principales-cortes-y-como-cocinar-cada-uno' => 'Pato fresco: cortes principales y cómo cocinarlos',
		'pate-de-pato-que-es-como-se-elabora-y-como-elegirlo' => 'Paté de pato: qué es, elaboración y cómo elegirlo',
		'como-cocinar-magret-de-pato-en-sarten-jugoso' => 'Magret de pato en sartén: cómo dejarlo jugoso',
		'como-preparar-confit-de-pato-al-horno-piel-crujiente' => 'Confit de pato crujiente: horno, grill y air fryer',
		'como-servir-foie-gras-temperatura-corte-pan-acompanamientos' => 'Cómo servir foie gras: temperatura, corte y presentación',
		'mousse-de-pato-que-es-diferencia-pate-foie' => 'Mousse de pato: diferencias con paté y foie gras',
		'rillettes-de-pato-que-son-como-se-elaboran-y-como-se-comen' => 'Rillettes de pato: qué son, elaboración y cómo servirlas',
		'foie-gras-entier-bloc-y-bloc-con-trozos-diferencias' => 'Foie gras entier vs bloc y bloc con trozos',
		'foie-gras-fresco-micuit-conserva-diferencias-usos' => 'Foie fresco, micuit o conserva: diferencias y usos',
		'jamon-de-pato-curado-y-ahumado-diferencias' => 'Jamón de pato curado vs ahumado: diferencias',
		'grasa-de-pato-para-que-sirve-como-utilizarla-cocina' => 'Grasa de pato: usos y cómo aprovecharla en cocina',
		'como-cocinar-foie-gras-fresco-a-la-plancha-sin-que-se-deshaga' => 'Foie gras a la plancha: cómo cocinarlo sin deshacerlo',
		'parfait-de-foie-y-mousse-de-foie-diferencias' => 'Parfait de foie vs mousse: textura, composición y sabor',
		'como-conservar-foie-gras-antes-y-despues-de-abrirlo' => 'Cómo conservar foie gras antes y después de abrirlo',
		'con-que-acompanar-foie-gras-pan-frutas-mermeladas' => 'Con qué acompañar foie gras: panes, frutas y contrastes',
		'magret-de-pato-a-la-naranja-receta-claves' => 'Magret de pato a la naranja: receta y claves',
		'que-guarnicion-poner-al-magret-de-pato' => 'Guarniciones para magret de pato: ideas que funcionan',
		'punto-coccion-magret-pato-temperaturas-tiempos' => 'Punto del magret de pato: temperaturas y tiempos',
		'como-cortar-servir-magret-de-pato' => 'Cómo cortar y servir magret de pato correctamente',
		'que-hacer-con-grasa-que-suelta-magret-de-pato' => 'Qué hacer con la grasa del magret de pato',
		'confit-de-pato-al-horno-temperatura-tiempo-trucos' => 'Confit de pato al horno: temperatura, tiempo y trucos',
		'como-calentar-confit-de-pato-conserva-envasado-vacio' => 'Cómo calentar confit de pato en conserva o al vacío',
		'con-que-acompanar-confit-de-pato-mejores-guarniciones' => 'Guarniciones para confit de pato: qué le va mejor',
		'confit-de-pato-a-la-naranja-receta-acompanamientos' => 'Confit de pato a la naranja: receta y acompañamientos',
		'que-hacer-con-confit-de-pato-desmigado-ideas' => 'Confit de pato desmigado: ideas para aprovecharlo',
		'arroz-con-pato-como-prepararlo-confit-carne-pato' => 'Arroz con pato: cómo prepararlo con confit o carne',
		'croquetas-confit-de-pato-como-aprovecharlo' => 'Croquetas de confit de pato: receta y trucos',
		'como-servir-jamon-de-pato-temperatura-corte-presentacion' => 'Cómo servir jamón de pato: temperatura, corte y presentación',
		'con-que-acompanar-jamon-de-pato' => 'Con qué acompañar jamón de pato: ideas y contrastes',
		'canapes-con-jamon-de-pato-ideas-sencillas' => 'Canapés con jamón de pato: ideas fáciles',
		'ensalada-con-jamon-de-pato-combinaciones' => 'Ensalada con jamón de pato: combinaciones que funcionan',
		'como-conservar-jamon-de-pato-entero-loncheado' => 'Cómo conservar jamón de pato entero y loncheado',
		'como-servir-pate-de-pato-temperatura-pan-acompanamientos' => 'Cómo servir paté de pato: temperatura, pan y acompañamientos',
		'canapes-con-pate-de-pato-ideas-faciles' => 'Canapés con paté de pato: ideas fáciles',
		'pate-mousse-rillettes-diferencias' => 'Paté, mousse y rillettes de pato: diferencias',
		'mousse-de-pato-pimienta-cognac-orujo-diferencias' => 'Mousse de pato: pimienta, coñac u orujo, diferencias',
		'como-servir-rillettes-de-pato-y-acompanarlas' => 'Cómo servir rillettes de pato y con qué acompañarlas',
		'solomillo-de-pato-que-corte-es-como-cocinarlo' => 'Solomillo de pato: qué corte es y cómo cocinarlo',
		'muslo-de-pato-fresco-y-confit-diferencias' => 'Muslo fresco vs confit de pato: diferencias y cocción',
		'manchon-de-pato-que-parte-es-como-cocina' => 'Manchón de pato: qué parte es y cómo cocinarlo',
		'corazones-de-pato-como-prepararlos-sabor' => 'Corazones de pato: sabor y cómo prepararlos',
		'pato-entero-al-horno-jugoso-crujiente' => 'Pato entero al horno: jugoso por dentro y crujiente',
		'carne-de-pato-a-que-sabe-textura' => 'Carne de pato: a qué sabe y qué textura tiene',
		'carne-de-pato-vs-pollo-diferencias-sabor-cocina' => 'Carne de pato vs pollo: diferencias de sabor y cocina',
		'valor-nutricional-carne-de-pato-proteinas-grasas-calorias' => 'Carne de pato: proteínas, grasas y calorías',
		'cuanta-carne-de-pato-calcular-por-persona' => 'Cuánta carne de pato calcular por persona',
		'como-conservar-congelar-descongelar-carne-de-pato' => 'Cómo conservar, congelar y descongelar carne de pato',
		'salsas-para-pato-naranja-frutos-rojos-pedro-ximenez' => 'Salsas para pato: naranja, frutos rojos y Pedro Ximénez',
		'que-vino-combina-magret-confit-foie-jamon-pato' => 'Qué vino combina con magret, confit, foie y jamón de pato',
		'pato-con-frutos-rojos-por-que-funciona-como-prepararlo' => 'Pato con frutos rojos: por qué funciona y cómo prepararlo',
		'pato-con-pedro-ximenez-cortes-preparados' => 'Pato con Pedro Ximénez: cortes y preparados que combinan',
		'aperitivos-con-foie-gras-navidad-ocasiones-especiales' => 'Aperitivos con foie gras para Navidad y ocasiones especiales',
		'menu-navidad-con-pato-foie-magret-confit-jamon' => 'Menú de Navidad con pato: foie, magret, confit y jamón',
		'productos-de-pato-para-regalar-foie-pates-jamon-lotes' => 'Productos de pato para regalar: foie, paté y jamón',
	);
}

/** Hand-written descriptions keep each search intent separate and avoid template-like snippets. */
function emdo_duck_serp_descriptions_20260930(): array {
	return array(
		'carne-de-pato-guia-completa-cortes-sabor-preparacion' => 'Conoce los principales cortes de pato, su sabor y textura, qué preparación conviene a cada pieza y qué revisar antes de elegir carne de pato.',
		'foie-gras-de-pato-que-es-tipos-diferencias-como-elegirlo' => 'Diferencias entre foie gras entero, bloc, micuit y otros formatos, con criterios de uso, conservación y textura para elegir el más adecuado.',
		'magret-de-pato-que-es-como-cocinarlo-punto-perfecto' => 'Qué es el magret de pato, de qué parte procede, cómo es su capa de grasa y qué técnicas funcionan mejor para cocinar este corte.',
		'confit-de-pato-que-es-como-se-prepara-y-con-que-acompanarlo' => 'Qué es el confit de pato, cómo se elabora, cómo calentarlo sin secarlo y qué guarniciones y salsas combinan mejor con su textura.',
		'jamon-de-pato-que-es-como-se-elabora-y-como-se-come' => 'Qué es el jamón de pato, cómo se cura, qué sabor y textura tiene y cómo cortarlo, atemperarlo y servirlo para disfrutarlo mejor.',
		'foie-gras-pate-mousse-parfait-bloc-micuit-diferencias' => 'Compara foie gras, paté, mousse, parfait, bloc y micuit por composición, textura, elaboración y uso para no confundir denominaciones.',
		'pato-fresco-principales-cortes-y-como-cocinar-cada-uno' => 'Repaso de los principales cortes de pato fresco y de las técnicas de cocción que mejor encajan con pechuga, muslo, solomillo y otras piezas.',
		'pate-de-pato-que-es-como-se-elabora-y-como-elegirlo' => 'Qué es un paté de pato, cómo se elabora, en qué se diferencia de mousse y foie gras y qué conviene mirar al comparar distintos formatos.',
		'como-cocinar-magret-de-pato-en-sarten-jugoso' => 'Método paso a paso para cocinar magret de pato en sartén, fundir bien la grasa, controlar el punto y mantener la carne jugosa.',
		'como-preparar-confit-de-pato-al-horno-piel-crujiente' => 'Cómo dejar crujiente la piel de un confit ya cocinado comparando horno, grill y air fryer, con claves para dorar sin resecar la carne.',
		'como-servir-foie-gras-temperatura-corte-pan-acompanamientos' => 'Temperatura, corte, cantidad por persona y presentación del foie gras, con pautas para servirlo sin ocultar su sabor ni su textura.',
		'mousse-de-pato-que-es-diferencia-pate-foie' => 'Qué caracteriza a una mousse de pato y cómo cambia frente a paté y foie gras en composición, textura, intensidad y forma de servirla.',
		'rillettes-de-pato-que-son-como-se-elaboran-y-como-se-comen' => 'Qué son las rillettes de pato, cómo se elaboran con carne desmigada y grasa y qué textura, sabor y formas de consumo puedes esperar.',
		'foie-gras-entier-bloc-y-bloc-con-trozos-diferencias' => 'Diferencias entre foie gras entier, bloc y bloc con trozos: composición, textura, aspecto al corte y situaciones en las que encaja cada formato.',
		'foie-gras-fresco-micuit-conserva-diferencias-usos' => 'Compara foie gras fresco, micuit y conserva por tratamiento térmico, conservación, textura, vida útil y usos habituales en cocina y servicio.',
		'jamon-de-pato-curado-y-ahumado-diferencias' => 'Qué cambia entre jamón de pato curado y ahumado en aroma, textura, intensidad, elaboración y combinaciones para aperitivos y platos.',
		'grasa-de-pato-para-que-sirve-como-utilizarla-cocina' => 'Ideas para aprovechar la grasa de pato en patatas, verduras, arroces y otras preparaciones, con pautas de conservación y uso en cocina.',
		'como-cocinar-foie-gras-fresco-a-la-plancha-sin-que-se-deshaga' => 'Cómo marcar foie gras fresco a la plancha con sartén muy caliente, tiempos breves y manipulación mínima para dorarlo sin que se funda en exceso.',
		'parfait-de-foie-y-mousse-de-foie-diferencias' => 'Diferencias entre parfait y mousse de foie en proporción de hígado, textura, untuosidad, sabor y forma de presentarlos en mesa.',
		'como-conservar-foie-gras-antes-y-despues-de-abrirlo' => 'Cómo guardar foie gras según su formato antes y después de abrirlo, con pautas sobre frío, cierre, manipulación y señales de deterioro.',
		'con-que-acompanar-foie-gras-pan-frutas-mermeladas' => 'Panes, frutas, mermeladas y otros contrastes que acompañan bien al foie gras, con ideas para equilibrar dulzor, acidez, textura y grasa.',
		'magret-de-pato-a-la-naranja-receta-claves' => 'Cómo preparar magret de pato a la naranja equilibrando cocción, grasa, acidez y dulzor para que la salsa acompañe sin tapar la carne.',
		'que-guarnicion-poner-al-magret-de-pato' => 'Guarniciones para magret de pato con patata, verduras, fruta y purés, ordenadas por tipo de contraste y por el estilo de plato que buscas.',
		'punto-coccion-magret-pato-temperaturas-tiempos' => 'Temperaturas, tiempos y reposo del magret de pato para controlar el punto interior sin descuidar el dorado de la piel y el fundido de la grasa.',
		'como-cortar-servir-magret-de-pato' => 'Cómo reposar, cortar y servir un magret de pato para conservar sus jugos, presentar lonchas uniformes y mantener una buena textura.',
		'que-hacer-con-grasa-que-suelta-magret-de-pato' => 'Cómo colar, guardar y reutilizar la grasa que suelta el magret de pato y en qué preparaciones merece la pena aprovecharla.',
		'confit-de-pato-al-horno-temperatura-tiempo-trucos' => 'Temperatura, tiempo y acabado al horno para recalentar confit de pato, fundir el exceso de grasa y conseguir una piel bien dorada.',
		'como-calentar-confit-de-pato-conserva-envasado-vacio' => 'Cómo recalentar confit de pato en conserva o al vacío sin resecarlo, con métodos para horno, sartén y acabado final crujiente.',
		'con-que-acompanar-confit-de-pato-mejores-guarniciones' => 'Guarniciones para confit de pato con patata, verduras, frutas y ensaladas, buscando equilibrio frente a su sabor intenso y textura untuosa.',
		'confit-de-pato-a-la-naranja-receta-acompanamientos' => 'Cómo preparar confit de pato a la naranja con una salsa equilibrada y qué acompañamientos funcionan mejor para completar el plato.',
		'que-hacer-con-confit-de-pato-desmigado-ideas' => 'Ideas para usar confit de pato desmigado en arroces, pasta, rellenos, croquetas y aperitivos aprovechando una carne ya cocinada y sabrosa.',
		'arroz-con-pato-como-prepararlo-confit-carne-pato' => 'Claves para preparar arroz con pato usando confit o carne fresca, ajustando grasa, caldo, sofrito, tiempos y cantidad de carne.',
		'croquetas-confit-de-pato-como-aprovecharlo' => 'Cómo aprovechar confit de pato desmigado en croquetas, con pautas para equilibrar la bechamel, la grasa, el sabor y el rebozado.',
		'como-servir-jamon-de-pato-temperatura-corte-presentacion' => 'Cómo atemperar, cortar y presentar jamón de pato para que la grasa tenga buena textura y las lonchas mantengan equilibrio y sabor.',
		'con-que-acompanar-jamon-de-pato' => 'Acompañamientos para jamón de pato con panes, frutas, frutos secos, quesos y aliños que aportan contraste sin dominar el producto.',
		'canapes-con-jamon-de-pato-ideas-sencillas' => 'Ideas sencillas de canapés con jamón de pato combinando bases crujientes, fruta, queso y toques ácidos para aperitivos fáciles de montar.',
		'ensalada-con-jamon-de-pato-combinaciones' => 'Combinaciones para ensalada con jamón de pato: hojas, frutas, frutos secos, quesos y vinagretas que equilibran su intensidad y grasa.',
		'como-conservar-jamon-de-pato-entero-loncheado' => 'Cómo conservar jamón de pato entero o loncheado, protegerlo del aire y el exceso de humedad y atemperarlo correctamente antes de servir.',
		'como-servir-pate-de-pato-temperatura-pan-acompanamientos' => 'Temperatura, cantidad, panes y acompañamientos para servir paté de pato con buena textura y sin cargar el aperitivo de sabores demasiado intensos.',
		'canapes-con-pate-de-pato-ideas-faciles' => 'Ideas de canapés con paté de pato usando pan, fruta, encurtidos, frutos secos y otros contrastes para preparar aperitivos variados.',
		'pate-mousse-rillettes-diferencias' => 'Compara paté, mousse y rillettes de pato por elaboración, textura, composición y forma de consumo para distinguir tres preparados diferentes.',
		'mousse-de-pato-pimienta-cognac-orujo-diferencias' => 'Qué aportan pimienta, coñac u orujo a una mousse de pato y cómo cambian el aroma, la intensidad y los acompañamientos que mejor le encajan.',
		'como-servir-rillettes-de-pato-y-acompanarlas' => 'Cómo atemperar y servir rillettes de pato y qué panes, encurtidos, mostazas y contrastes ayudan a equilibrar su textura rica y untuosa.',
		'solomillo-de-pato-que-corte-es-como-cocinarlo' => 'Qué es el solomillo de pato, dónde se encuentra, cómo es su textura y qué cocciones rápidas permiten cocinarlo sin secarlo.',
		'muslo-de-pato-fresco-y-confit-diferencias' => 'Diferencias entre muslo de pato fresco y confitado en textura, grasa, preparación, tiempos de cocción y usos más habituales.',
		'manchon-de-pato-que-parte-es-como-cocina' => 'Qué parte del pato es el manchón, cómo es su proporción de carne y hueso y qué cocciones lentas o doradas le sacan mejor partido.',
		'corazones-de-pato-como-prepararlos-sabor' => 'Qué sabor y textura tienen los corazones de pato, cómo limpiarlos y qué cocciones rápidas ayudan a mantenerlos tiernos.',
		'pato-entero-al-horno-jugoso-crujiente' => 'Cómo asar un pato entero buscando carne jugosa y piel crujiente, con claves sobre secado, temperatura, grasa, reposo y acabado.',
		'carne-de-pato-a-que-sabe-textura' => 'A qué sabe la carne de pato, cómo cambia su textura según el corte y por qué la piel, la grasa y el punto de cocción influyen tanto.',
		'carne-de-pato-vs-pollo-diferencias-sabor-cocina' => 'Diferencias entre carne de pato y pollo en sabor, grasa, textura, cortes y técnicas de cocción para entender cuándo sustituir una por otra.',
		'valor-nutricional-carne-de-pato-proteinas-grasas-calorias' => 'Qué aporta la carne de pato en proteínas, grasas y calorías y por qué los valores cambian según el corte, la piel y la forma de cocinarla.',
		'cuanta-carne-de-pato-calcular-por-persona' => 'Cantidades orientativas de carne de pato por persona según cortes, pato entero, magret o confit, con ajustes para menús y acompañamientos.',
		'como-conservar-congelar-descongelar-carne-de-pato' => 'Pautas para refrigerar, congelar y descongelar carne de pato manteniendo una manipulación segura y reduciendo pérdidas de textura y jugos.',
		'salsas-para-pato-naranja-frutos-rojos-pedro-ximenez' => 'Ideas de salsas para pato con naranja, frutos rojos, Pedro Ximénez y otros perfiles, explicando qué corte y preparación encaja con cada una.',
		'que-vino-combina-magret-confit-foie-jamon-pato' => 'Orientaciones para elegir vino con magret, confit, foie y jamón de pato según grasa, intensidad, dulzor, acidez y tipo de preparación.',
		'pato-con-frutos-rojos-por-que-funciona-como-prepararlo' => 'Por qué los frutos rojos combinan con el pato y cómo usar su acidez y dulzor en salsas y acompañamientos sin tapar el sabor de la carne.',
		'pato-con-pedro-ximenez-cortes-preparados' => 'Qué cortes y preparados de pato combinan mejor con Pedro Ximénez y cómo controlar el dulzor al reducirlo en salsa o glaseado.',
		'aperitivos-con-foie-gras-navidad-ocasiones-especiales' => 'Ideas de aperitivos con foie gras para Navidad y celebraciones, con formatos, bases, contrastes y cantidades para montar bocados equilibrados.',
		'menu-navidad-con-pato-foie-magret-confit-jamon' => 'Ideas para montar un menú de Navidad con pato combinando foie, magret, confit y jamón sin repetir sabores ni cargar demasiado cada pase.',
		'productos-de-pato-para-regalar-foie-pates-jamon-lotes' => 'Guía para elegir productos de pato como regalo: foie, patés, jamón y otros formatos, valorando conservación, variedad y facilidad de servicio.',
	);
}

add_filter( 'aioseo_title', static function ( $title ): string {
	if ( is_category( 'pato' ) ) {
		return 'Pato: carne, magret, foie, confit y recetas';
	}
	$slug = emdo_duck_current_slug_20260928();
	if ( '' === $slug ) { return (string) $title; }
	$map = emdo_duck_serp_titles_20260928();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : (string) $title;
}, 35 );

add_filter( 'aioseo_description', static function ( $description ): string {
	if ( is_category( 'pato' ) ) {
		return 'Guías sobre carne de pato, magret, foie gras, confit, jamón de pato, cortes, cocción, conservación, recetas, salsas y acompañamientos.';
	}
	$slug = emdo_duck_current_slug_20260928();
	if ( '' === $slug ) { return (string) $description; }
	$map = emdo_duck_serp_descriptions_20260930();
	return isset( $map[ $slug ] ) ? $map[ $slug ] : (string) $description;
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
}, 46 );

/** Primary topical groups make the 60-page cluster easier to crawl and understand. */
function emdo_duck_subtopics_20260930(): array {
	return array(
		'carne-cortes' => array(
			'label' => 'Carne de pato y cortes',
			'keys' => array( 'duck-01','duck-07','duck-17','duck-44','duck-45','duck-46','duck-47','duck-48','duck-49','duck-50','duck-51','duck-52','duck-53' ),
		),
		'foie-preparados' => array(
			'label' => 'Foie, paté, mousse y rillettes',
			'keys' => array( 'duck-02','duck-06','duck-08','duck-11','duck-12','duck-13','duck-14','duck-15','duck-18','duck-19','duck-20','duck-21','duck-39','duck-40','duck-41','duck-42','duck-43','duck-58' ),
		),
		'magret' => array(
			'label' => 'Magret de pato',
			'keys' => array( 'duck-03','duck-09','duck-22','duck-23','duck-24','duck-25','duck-26' ),
		),
		'confit' => array(
			'label' => 'Confit de pato',
			'keys' => array( 'duck-04','duck-10','duck-27','duck-28','duck-29','duck-30','duck-31','duck-32','duck-33' ),
		),
		'jamon' => array(
			'label' => 'Jamón de pato',
			'keys' => array( 'duck-05','duck-16','duck-34','duck-35','duck-36','duck-37','duck-38' ),
		),
		'salsas-menus' => array(
			'label' => 'Salsas, maridajes y menús',
			'keys' => array( 'duck-54','duck-55','duck-56','duck-57','duck-59','duck-60' ),
		),
	);
}

function emdo_duck_post_map_20260930(): array {
	static $map = null;
	if ( is_array( $map ) ) { return $map; }
	$map = array();
	$ids = get_posts( array(
		'post_type' => 'post',
		'post_status' => 'publish',
		'posts_per_page' => 100,
		'fields' => 'ids',
		'meta_key' => EMDO_DUCK_CLUSTER_META_20260928,
		'meta_value' => 'duck',
		'orderby' => 'ID',
		'order' => 'ASC',
	) );
	foreach ( $ids as $id ) {
		$key = (string) get_post_meta( (int) $id, '_emdo_seo_landing_key', true );
		if ( '' === $key ) { continue; }
		$map[ $key ] = array(
			'id' => (int) $id,
			'title' => (string) get_the_title( (int) $id ),
			'url' => (string) get_permalink( (int) $id ),
		);
	}
	return $map;
}

/** Add four tightly related links rather than generic same-category links only. */
add_filter( 'the_content', static function ( string $content ): string {
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || ! emdo_duck_is_post_20260928() ) {
		return $content;
	}
	if ( false !== strpos( $content, 'data-emdo-duck-related-topic' ) ) { return $content; }

	$current_id = (int) get_the_ID();
	$current_key = (string) get_post_meta( $current_id, '_emdo_seo_landing_key', true );
	if ( '' === $current_key ) { return $content; }

	$chosen = null;
	foreach ( emdo_duck_subtopics_20260930() as $slug => $group ) {
		if ( in_array( $current_key, $group['keys'], true ) ) {
			$chosen = array( 'slug' => $slug, 'label' => $group['label'], 'keys' => $group['keys'] );
			break;
		}
	}
	if ( ! $chosen ) { return $content; }

	$post_map = emdo_duck_post_map_20260930();
	$candidates = array_values( array_filter( $chosen['keys'], static fn( $key ) => $key !== $current_key && isset( $post_map[ $key ] ) ) );
	if ( empty( $candidates ) ) { return $content; }

	$pos = array_search( $current_key, $chosen['keys'], true );
	$ordered = array();
	$total = count( $chosen['keys'] );
	for ( $step = 1; $step < $total && count( $ordered ) < 4; $step++ ) {
		$key = $chosen['keys'][ ( (int) $pos + $step ) % $total ];
		if ( $key !== $current_key && isset( $post_map[ $key ] ) ) { $ordered[] = $key; }
	}
	if ( empty( $ordered ) ) { return $content; }

	$html = '<aside class="emdo-duck-related-topic" data-emdo-duck-related-topic="' . esc_attr( $chosen['slug'] ) . '">';
	$html .= '<strong>Continúa con ' . esc_html( strtolower( $chosen['label'] ) ) . '</strong><ul>';
	foreach ( $ordered as $key ) {
		$html .= '<li><a href="' . esc_url( $post_map[ $key ]['url'] ) . '">' . esc_html( $post_map[ $key ]['title'] ) . '</a></li>';
	}
	$html .= '</ul></aside>';
	return $content . "\n" . $html;
}, 47 );

/** Stable path back to the complete topic hub. */
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
		. '<p>Explora el índice completo de carne de pato, magret, foie gras, confit, jamón de pato, recetas, salsas y conservación.</p>'
		. '<a href="' . esc_url( $link ) . '">Ver todas las guías de pato →</a>'
		. '</aside>';
}, 48 );

add_action( 'wp_head', static function (): void {
	if ( ! is_singular( 'post' ) || ! emdo_duck_is_post_20260928() ) { return; }
	?>
	<style id="emdo-duck-hub-link-css">
		.emdo-duck-related-topic,.emdo-duck-hub-link{margin:30px 0 8px;padding:20px 22px;border:1px solid rgba(13,33,27,.13);border-radius:14px;background:#f8f5ef}
		.emdo-duck-related-topic strong,.emdo-duck-hub-link strong{display:block;margin-bottom:8px;font-size:1.05em}
		.emdo-duck-related-topic ul{margin:0;padding-left:20px}
		.emdo-duck-related-topic li{margin:6px 0}
		.emdo-duck-hub-link p{margin:0 0 8px!important}
		.emdo-duck-related-topic a,.emdo-duck-hub-link a{font-weight:650;text-decoration:underline;text-underline-offset:3px}
	</style>
	<?php
}, 120 );
