<?php
/**
 * SEO consolidation batch 2: Iberian ham clusters.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

function emdo_jamon_post( string $slug ): ?WP_Post {
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	return $post instanceof WP_Post ? $post : null;
}
function emdo_jamon_update( string $slug, string $title, string $excerpt, string $content, string $meta ): void {
	$post = emdo_jamon_post( $slug );
	if ( ! $post ) { throw new RuntimeException( 'Missing survivor: ' . $slug ); }
	$result = wp_update_post( array( 'ID' => $post->ID, 'post_title' => $title, 'post_excerpt' => $excerpt, 'post_content' => $content ), true );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	update_post_meta( $post->ID, '_yoast_wpseo_metadesc', $meta );
	update_post_meta( $post->ID, 'rank_math_description', $meta );
	update_post_meta( $post->ID, '_emdo_consolidated_20260901', '2' );
	clean_post_cache( $post->ID );
	echo "UPDATED {$post->ID} {$slug}\n";
}
function emdo_jamon_draft( string $source, string $destination ): void {
	$post = emdo_jamon_post( $source );
	if ( ! $post ) { echo "SOURCE_ALREADY_ABSENT {$source}\n"; return; }
	update_post_meta( $post->ID, '_emdo_consolidated_into', $destination );
	$result = wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ), true );
	if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
	clean_post_cache( $post->ID );
	echo "DRAFTED {$post->ID} {$source} => {$destination}\n";
}
function emdo_jamon_links( array $redirects ): void {
	$posts = get_posts( array( 'post_type' => array( 'post', 'page' ), 'post_status' => array( 'publish', 'draft' ), 'posts_per_page' => -1, 'suppress_filters' => false ) );
	foreach ( $posts as $post ) {
		$old = $post->post_content; $new = $old;
		foreach ( $redirects as $from => $to ) {
			$new = str_replace( array( home_url( '/' . $from . '/' ), '/' . $from . '/' ), array( home_url( '/' . $to . '/' ), '/' . $to . '/' ), $new );
		}
		if ( $new !== $old ) {
			$r = wp_update_post( array( 'ID' => $post->ID, 'post_content' => $new ), true );
			if ( is_wp_error( $r ) ) { throw new RuntimeException( $r->get_error_message() ); }
			echo "LINKS_UPDATED {$post->ID}\n";
		}
	}
}

$race = <<<'HTML'
<p><strong>50 %, 75 % y 100 % ibérico indican el porcentaje racial del animal, no su alimentación.</strong> Esa es la idea que evita casi todas las confusiones. Un jamón puede ser 50 % ibérico y de bellota, o 100 % ibérico y de cebo. Raza y alimentación son dos ejes diferentes y la denominación completa debe leerse combinándolos.</p>

<h2>50 %, 75 % y 100 % ibérico: qué significa cada porcentaje</h2>
<table><thead><tr><th>Indicación</th><th>Qué exige la norma</th><th>Cruce de referencia</th></tr></thead><tbody>
<tr><td><strong>100 % ibérico</strong></td><td>Animal con 100 % de pureza genética ibérica; ambos progenitores 100 % ibéricos e inscritos en el libro genealógico.</td><td>Ibérico × ibérico.</td></tr>
<tr><td><strong>75 % raza ibérica</strong></td><td>Animal con 75 % de genética ibérica.</td><td>Hembra 100 % ibérica × macho resultante de ibérica 100 % y Duroc 100 %.</td></tr>
<tr><td><strong>50 % raza ibérica</strong></td><td>Animal con 50 % de genética ibérica.</td><td>Hembra 100 % ibérica × macho 100 % Duroc.</td></tr>
</tbody></table>
<p>El <a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">Real Decreto 4/2014</a> establece estas designaciones y obliga a indicar “% raza ibérica” cuando el producto no es 100 % ibérico.</p>

<h2>El porcentaje racial no dice si el cerdo comió bellota</h2>
<p>La denominación de venta se construye combinando <strong>tipo de producto + alimentación y manejo + tipo racial</strong>. Por ejemplo:</p>
<ul>
<li>Jamón <strong>de bellota 100 % ibérico</strong>.</li>
<li>Jamón <strong>de bellota ibérico</strong>, indicando 50 % o 75 % raza ibérica.</li>
<li>Jamón <strong>de cebo de campo ibérico</strong>, también posible en distintos porcentajes raciales.</li>
<li>Jamón <strong>de cebo ibérico</strong>.</li>
</ul>
<p>Por eso “100 % ibérico” no es sinónimo de “bellota”, y “50 % ibérico” no es sinónimo de “cebo”.</p>

<h2>Cómo encajan las bridas negra, roja, verde y blanca</h2>
<table><thead><tr><th>Precinto</th><th>Denominación</th><th>Qué informa</th></tr></thead><tbody>
<tr><td>Negro</td><td>Bellota 100 % ibérico</td><td>Bellota + 100 % raza ibérica.</td></tr>
<tr><td>Rojo</td><td>Bellota ibérico</td><td>Bellota + 50 % o 75 % raza ibérica; mira la etiqueta para saber el porcentaje.</td></tr>
<tr><td>Verde</td><td>Cebo de campo ibérico</td><td>Alimentación con pienso y manejo al aire libre según la norma; puede haber distintos porcentajes raciales.</td></tr>
<tr><td>Blanco</td><td>Cebo ibérico</td><td>Alimentación con pienso y manejo intensivo regulado; puede haber distintos porcentajes raciales.</td></tr>
</tbody></table>
<p>La norma reserva además la expresión <strong>“pata negra”</strong> a la denominación de bellota 100 % ibérico. Para entender los colores en detalle, consulta <a href="/brida-negra-roja-verde-blanca-jamon-iberico/">qué significa cada brida del jamón ibérico</a>.</p>

<h2>¿Qué cambia sensorialmente entre 100 %, 75 % y 50 %?</h2>
<p>La genética puede influir en conformación, capacidad de infiltración de grasa, crecimiento y características sensoriales, pero <strong>el porcentaje racial por sí solo no permite predecir cómo sabrá una pieza concreta</strong>. También influyen alimentación, ejercicio, peso, edad, salado, tiempo de curación, bodega, parte de la pieza y servicio.</p>
<p>Dos jamones con el mismo porcentaje pueden ser muy distintos. Y un 50 % de excelente materia prima y curación puede ofrecer una experiencia superior a un producto 100 % ibérico mal elaborado o mal conservado.</p>

<h2>¿Un 100 % ibérico es siempre mejor?</h2>
<p>“Mejor” depende de qué se valore. El 100 % ibérico representa máxima pureza racial y, cuando es de bellota, corresponde a la brida negra. Pero para comprar con criterio conviene leer la denominación completa, valorar productor, curación, peso, formato y precio. El porcentaje no sustituye al resto de información.</p>

<h2>¿Hay diferencias nutricionales fijas entre un jamón 100 %, 75 % y 50 % ibérico?</h2>
<p><strong>No existe una tabla nutricional universal que permita decir que un porcentaje racial concreto siempre tiene X gramos más de proteína, grasa o ácido oleico.</strong> La composición final depende de múltiples factores: alimentación, depósito graso, curación, deshidratación y zona analizada.</p>
<p>El porcentaje racial describe genética. Si quieres comparar nutrición, hay que analizar piezas o productos concretos en condiciones equivalentes. Para una visión general consulta <a href="/nutrientes-jamon-iberico-proteinas-grasas-hierro-vitaminas-minerales/">qué nutrientes tiene el jamón ibérico</a>.</p>

<h2>Cómo leer una etiqueta de jamón ibérico en 20 segundos</h2>
<ol>
<li><strong>Busca la denominación completa:</strong> bellota, cebo de campo o cebo.</li>
<li><strong>Mira el tipo racial:</strong> 100 %, 75 % o 50 % raza ibérica.</li>
<li><strong>Comprueba el precinto:</strong> negro, rojo, verde o blanco.</li>
<li><strong>Identifica el productor y certificador.</strong></li>
<li><strong>Después compara formato, peso, curación y precio</strong> según cómo vayas a consumirlo.</li>
</ol>

<h2>Ejemplos para no confundirse</h2>
<p><strong>“Jamón de bellota ibérico 50 % raza ibérica”:</strong> el animal cumple la categoría de bellota y tiene 50 % de genética ibérica. Lleva precinto rojo, no negro.</p>
<p><strong>“Jamón de cebo 100 % ibérico”:</strong> tiene pureza racial 100 %, pero su categoría alimentaria es cebo. La pureza racial no lo convierte en bellota.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿75 % ibérico significa que ha comido 75 % de bellotas?</h3><p>No. El 75 % se refiere exclusivamente al porcentaje racial.</p>
<h3>¿Puede existir jamón de bellota 50 % ibérico?</h3><p>Sí. La bellota describe alimentación y manejo; el 50 % describe genética.</p>
<h3>¿La brida roja puede ser 50 % o 75 %?</h3><p>Sí. El precinto rojo identifica bellota ibérico que no es 100 %; el porcentaje debe figurar en la etiqueta.</p>
<h3>¿Cuál es la única categoría que puede llamarse “pata negra”?</h3><p>La norma reserva esa mención a “de bellota 100 % ibérico”.</p>

<h2>Fuente normativa</h2>
<p>La referencia principal de esta guía es el <a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">Real Decreto 4/2014, norma de calidad del ibérico</a>, junto con la información de la <a href="https://www.mapa.gob.es/es/alimentacion/temas/control-calidad/mesa-iberico" rel="nofollow">Mesa de Coordinación de la Norma de Calidad del Ibérico del Ministerio de Agricultura</a>.</p>
HTML;

$ham_shoulder = <<<'HTML'
<p><strong>Jamón y paleta no son lo mismo:</strong> el jamón procede de las extremidades posteriores del cerdo y la paleta de las anteriores. Esa diferencia anatómica cambia tamaño, proporción de hueso, rendimiento, forma de corte, tiempo mínimo de elaboración y, en parte, la experiencia sensorial. No significa que uno sea automáticamente “mejor” que el otro.</p>

<h2>Jamón vs paleta ibérica: diferencias de un vistazo</h2>
<table><thead><tr><th>Aspecto</th><th>Jamón ibérico</th><th>Paleta ibérica</th></tr></thead><tbody>
<tr><td>Parte del animal</td><td>Pata trasera</td><td>Pata delantera</td></tr>
<tr><td>Tamaño</td><td>Mayor</td><td>Menor</td></tr>
<tr><td>Tiempo mínimo normativo de elaboración</td><td>600 días si la pieza elaborada pesa menos de 7 kg; 730 días si pesa 7 kg o más</td><td>365 días, independientemente del peso</td></tr>
<tr><td>Proporción de hueso</td><td>Menor en relación con el peso total</td><td>Mayor en relación con el peso total</td></tr>
<tr><td>Rendimiento en lonchas</td><td>Generalmente mayor</td><td>Generalmente menor</td></tr>
<tr><td>Consumo doméstico</td><td>Conviene si sois varios o consumís con frecuencia</td><td>Muy práctica para hogares con menor ritmo de consumo</td></tr>
</tbody></table>
<p>Los tiempos y pesos mínimos están recogidos en el <a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">Real Decreto 4/2014</a>. Son mínimos legales, no una promesa de que todas las piezas se comercialicen justo al cumplirlos.</p>

<h2>Por qué la paleta sabe diferente</h2>
<p>La paleta tiene una anatomía más compacta y una relación distinta entre músculos, hueso y grasa. Esto puede hacer que el sabor se perciba más intenso en algunas zonas. El jamón ofrece una pieza mayor, con zonas musculares más extensas y más recorrido de corte.</p>
<p>Pero no hay un “sabor de paleta” único: raza, alimentación, curación y parte concreta —maza, babilla, punta en el jamón, y sus equivalentes en la paleta— cambian mucho la loncha.</p>

<h2>¿Cuál tiene más grasa?</h2>
<p>No conviene responder con una cifra universal. La cantidad de grasa varía dentro de una misma pieza y entre productores. Una loncha de una zona muy infiltrada no tiene la misma composición que otra más magra. Por eso una comparación nutricional seria debe utilizar datos de producto concreto o muestras equivalentes, no convertir una diferencia anatómica en una regla absoluta.</p>

<h2>Rendimiento: por qué el precio por kilo puede engañar</h2>
<p>La paleta suele costar menos como pieza completa, pero contiene proporcionalmente más hueso y ofrece menos lonchas por kilo comprado. El jamón tiene un desembolso inicial mayor, pero normalmente un rendimiento superior.</p>
<p>Para comparar de verdad hay que pensar en <strong>precio por cantidad aprovechable</strong>, no solo en precio por kilo de pieza. Si prefieres que todo el producto sea comestible desde el primer momento, el loncheado al vacío elimina además la incertidumbre del rendimiento doméstico.</p>

<h2>¿Cuánto dura una pieza una vez empezada?</h2>
<p>La cuestión decisiva no es cuánto puede “aguantar”, sino cuánto tarda tu hogar en consumirla manteniendo buena calidad sensorial. Una pieza abierta va perdiendo humedad y aroma en la superficie. Si el consumo es lento, una paleta o sobres loncheados pueden ser más sensatos que un jamón grande.</p>
<p>Consulta <a href="/como-conservar-jamon-iberico-empezado/">cómo conservar un jamón empezado</a> y <a href="/como-conservar-sobres-jamon-iberico/">cómo conservar sobres de jamón ibérico</a>.</p>

<h2>¿Jamón o paleta para regalar?</h2>
<p>Para un regalo, la elección depende del destinatario. Una paleta puede ser ideal para una casa pequeña y ofrecer una experiencia de pieza completa con menor presupuesto. Un jamón encaja mejor cuando se busca mayor rendimiento, presencia y duración de consumo. Si no sabes si la persona corta jamón, el loncheado puede ser mucho más útil.</p>

<h2>¿Qué pesa normalmente cada pieza?</h2>
<p>La norma fija pesos mínimos de producto elaborado: para jamones, al menos 5,75 kg si son 100 % ibéricos y 7 kg para los ibéricos no 100 %; para paletas, 3,7 kg y 4 kg respectivamente. En el mercado son habituales pesos superiores.</p>
<p>Si estás eligiendo tamaño, consulta <a href="/que-peso-jamon-iberico-elegir-segun-personas-consumo/">qué peso de jamón elegir según personas y consumo</a>.</p>

<h2>Cómo elegir entre jamón y paleta</h2>
<ul>
<li><strong>Elige jamón</strong> si hay consumo frecuente, varias personas, buscas más rendimiento y disfrutas del corte de una pieza grande.</li>
<li><strong>Elige paleta</strong> si prefieres menor desembolso, consumo más rápido y una pieza más manejable.</li>
<li><strong>Elige loncheado</strong> si priorizas comodidad, control de raciones y conservación sin tener una pieza abierta.</li>
</ul>
<p>Después de decidir el formato, compara la <strong>categoría completa</strong>: bellota/cebo de campo/cebo y porcentaje racial. No confundas “jamón vs paleta” con “calidad de alimentación”.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿La paleta es un jamón pequeño?</h3><p>No. Procede de una extremidad distinta y tiene anatomía, rendimiento y tiempos de elaboración propios.</p>
<h3>¿Qué tiene más carne aprovechable?</h3><p>En términos generales, el jamón ofrece mayor rendimiento proporcional que la paleta.</p>
<h3>¿La paleta cura menos tiempo?</h3><p>La norma fija para la paleta un mínimo de 365 días; para el jamón, 600 o 730 días según peso.</p>
<h3>¿Nutricionalmente son muy distintos?</h3><p>Comparten el perfil general de productos curados del cerdo ibérico, pero la composición real varía por zona, grasa, alimentación y elaboración; no existe una diferencia fija aplicable a todas las piezas.</p>

<h2>Conclusión</h2>
<p>El jamón gana en rendimiento y recorrido de corte; la paleta en tamaño, rapidez de consumo y normalmente desembolso inicial. La mejor elección es la que encaja con vuestro ritmo de consumo y presupuesto, siempre comparando después la categoría y el productor.</p>
HTML;

$serrano = <<<'HTML'
<p><strong>Jamón ibérico y jamón serrano son jamones curados, pero no son la misma categoría de producto.</strong> “Ibérico” está ligado a animales con al menos un 50 % de raza ibérica y a una norma que regula raza, alimentación/manejo, trazabilidad y denominaciones. “Serrano” pertenece al universo del jamón curado de cerdo no ibérico y responde a otra tradición productiva y normativa.</p>

<h2>Jamón ibérico vs serrano: diferencias principales</h2>
<table><thead><tr><th>Aspecto</th><th>Jamón ibérico</th><th>Jamón serrano / curado no ibérico</th></tr></thead><tbody>
<tr><td>Base racial</td><td>Al menos 50 % raza ibérica; puede ser 50, 75 o 100 %</td><td>Procede de cerdos de razas no ibéricas o cruces fuera de la norma del ibérico</td></tr>
<tr><td>Clasificación</td><td>Combina alimentación/manejo y porcentaje racial</td><td>No utiliza el sistema de bridas del ibérico</td></tr>
<tr><td>Precintos negro/rojo/verde/blanco</td><td>Sí, en jamones y paletas acogidos a la norma</td><td>No</td></tr>
<tr><td>Perfil de grasa</td><td>Puede presentar elevada infiltración; depende de genética, alimentación y pieza</td><td>Variable según raza, alimentación y elaboración</td></tr>
<tr><td>Precio</td><td>Generalmente mayor</td><td>Generalmente menor, con amplio rango de calidades</td></tr>
</tbody></table>

<h2>Qué significa realmente “ibérico”</h2>
<p>El <a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">Real Decreto 4/2014</a> exige que un producto denominado ibérico proceda de animales con al menos 50 % de genética ibérica. Si no es 100 %, el porcentaje debe indicarse en la etiqueta. Además, para jamones y paletas, la brida informa de la categoría de alimentación/manejo.</p>
<p>Por eso “ibérico” no es una descripción informal de sabor o apariencia: es una denominación regulada.</p>

<h2>¿Qué es entonces un jamón serrano?</h2>
<p>En el lenguaje cotidiano, jamón serrano se utiliza para un jamón curado procedente de cerdo de capa blanca. No lleva los porcentajes raciales ni las bridas de la norma ibérica. Dentro de este grupo existen diferencias enormes de raza, materia prima, salado, tiempo de curación y productor, así que tampoco existe un único “serrano” sensorialmente idéntico.</p>

<h2>Grasa e infiltración: una diferencia visible, pero no absoluta</h2>
<p>El cerdo ibérico tiene una reconocida capacidad de depósito e infiltración de grasa. En determinadas producciones, especialmente con largas curaciones y acabados de bellota, aparecen vetas y una grasa de textura muy característica. Sin embargo, la infiltración no se puede diagnosticar solo por el nombre comercial y tampoco todo jamón serrano es necesariamente magro.</p>
<p>La zona de la pieza también importa: maza, babilla y punta tienen proporciones de grasa diferentes. Consulta <a href="/partes-jamon-iberico-maza-babilla-contramaza-punta-jarrete/">las partes del jamón ibérico</a>.</p>

<h2>¿Cuál tiene más proteína, grasa o calorías?</h2>
<p>No hay una cifra universal capaz de representar todos los jamones ibéricos y todos los serranos. Al curarse, ambos pierden agua y concentran nutrientes por 100 g. La cantidad final de proteína, grasa y sal depende de materia prima, zona de la pieza y proceso.</p>
<p>La diferencia nutricional más interesante suele estar en el <strong>perfil de ácidos grasos</strong>, no en afirmar que uno siempre tiene más o menos grasa total. En el ibérico, genética y alimentación pueden favorecer una elevada proporción de ácido oleico, especialmente en determinados sistemas de producción.</p>

<h2>¿Por qué el ibérico suele costar más?</h2>
<p>El precio puede reflejar genética, sistema de cría, alimentación, edad y peso de sacrificio, tiempos de elaboración, merma, certificación y disponibilidad. En bellota, la montanera requiere superficie de dehesa y una carga ganadera regulada. Además, un jamón es un producto inmovilizado durante años antes de llegar al consumidor.</p>
<p>Eso no significa que el jamón más caro sea automáticamente el que más te guste, pero sí explica por qué comparar solo €/kg entre productos de sistemas distintos puede ser engañoso.</p>

<h2>Cómo distinguirlos en la tienda</h2>
<ol>
<li>Lee la <strong>denominación de venta</strong>, no solo la marca.</li>
<li>Si es ibérico, busca porcentaje racial cuando corresponda.</li>
<li>En jamón ibérico entero, comprueba el precinto negro, rojo, verde o blanco.</li>
<li>No uses el color de la pezuña como prueba: consulta <a href="/pezuna-negra-jamon-significa-iberico-pata-negra/">por qué una pezuña negra no demuestra que un jamón sea ibérico</a>.</li>
<li>Valora productor, curación, formato y conservación.</li>
</ol>

<h2>¿Cuál elegir?</h2>
<p>Elige por el perfil que buscas y por presupuesto. Un buen serrano puede ser excelente para consumo frecuente, bocadillos, cocina y tablas. Un ibérico aporta una identidad racial y productiva específica, y puede ofrecer mayor complejidad aromática e infiltración. Dentro del ibérico, la diferencia entre bellota, cebo de campo y cebo es tan importante como la palabra “ibérico”.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Todo jamón de pezuña negra es ibérico?</h3><p>No. La pezuña no certifica raza ni alimentación.</p>
<h3>¿Todo jamón ibérico es de bellota?</h3><p>No. Existen ibéricos de bellota, cebo de campo y cebo.</p>
<h3>¿El serrano tiene menos sal?</h3><p>No puede afirmarse como regla. La sal depende del producto y proceso concretos.</p>
<h3>¿Cuál tiene más ácido oleico?</h3><p>El perfil de grasa del ibérico, especialmente en ciertos sistemas de alimentación, puede ser muy rico en ácido oleico, pero no debe asignarse una cifra fija a toda una categoría sin analizar el producto.</p>

<h2>Fuente principal</h2>
<p>Para la parte ibérica utilizamos como referencia la <a href="https://www.mapa.gob.es/es/alimentacion/temas/control-calidad/mesa-iberico" rel="nofollow">información oficial del Ministerio de Agricultura</a> y el Real Decreto 4/2014.</p>
HTML;

$feeding = <<<'HTML'
<p><strong>Bellota, cebo de campo y cebo describen alimentación y manejo, no el porcentaje racial.</strong> La norma del ibérico separa ambos ejes. Por eso puede haber un jamón de bellota 50 % ibérico y un jamón de cebo 100 % ibérico.</p>

<h2>Bellota vs cebo de campo vs cebo: tabla rápida</h2>
<table><thead><tr><th>Categoría</th><th>Alimentación y manejo</th><th>Precinto</th></tr></thead><tbody>
<tr><td><strong>Bellota</strong></td><td>Fase final en montanera aprovechando recursos naturales de la dehesa, fundamentalmente bellota y pastos, cumpliendo condiciones de peso, edad, fechas y carga ganadera.</td><td>Negro si es 100 % ibérico; rojo si es ibérico 50/75 %.</td></tr>
<tr><td><strong>Cebo de campo</strong></td><td>Alimentado con piensos, fundamentalmente cereales y leguminosas, con manejo al aire libre según las condiciones de la norma.</td><td>Verde.</td></tr>
<tr><td><strong>Cebo</strong></td><td>Alimentado con piensos, fundamentalmente cereales y leguminosas, en sistema intensivo regulado.</td><td>Blanco.</td></tr>
</tbody></table>

<h2>Qué exige la categoría de bellota</h2>
<p>La montanera no significa simplemente “haber comido alguna bellota”. El <a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">Real Decreto 4/2014</a> fija, entre otras condiciones, entrada en montanera entre el 1 de octubre y el 15 de diciembre, sacrificio entre el 15 de diciembre y el 31 de marzo, una reposición mínima de 46 kg durante más de 60 días y una edad mínima al sacrificio de 14 meses. La carga ganadera depende de la superficie arbolada y disponibilidad de bellota.</p>
<p>La denominación “dehesa” o “montanera” está reservada a productos de bellota dentro de esta norma.</p>

<h2>Qué significa cebo de campo</h2>
<p>El cebo de campo no equivale a bellota. La alimentación se basa en piensos —principalmente cereales y leguminosas—, aunque el animal se maneja en explotaciones extensivas o instalaciones al aire libre con requisitos específicos. La norma establece para animales de más de 110 kg una superficie mínima libre de 100 m² por animal durante la fase de cebo y una estancia mínima de 60 días antes del sacrificio.</p>

<h2>Qué significa cebo</h2>
<p>En la categoría de cebo, los animales se alimentan con piensos y el manejo se realiza en sistemas intensivos regulados. La norma fija una edad mínima al sacrificio de 10 meses y requisitos de superficie y peso.</p>

<h2>La raza es otro eje: no la deduzcas por el color salvo la brida negra</h2>
<p>El precinto negro sí identifica específicamente <strong>bellota 100 % ibérico</strong>. El rojo identifica bellota ibérico, pero debes mirar la etiqueta para saber si es 50 % o 75 %. Verde y blanco informan del sistema de alimentación/manejo y pueden corresponder a distintos porcentajes raciales.</p>
<p>Consulta <a href="/50-75-100-iberico-que-significa-porcentaje-raza-etiqueta/">qué significan 50, 75 y 100 % ibérico</a>.</p>

<h2>¿La bellota cambia la grasa?</h2>
<p>La alimentación de la fase final puede influir en el perfil lipídico del cerdo. Las bellotas son ricas en ácido oleico: trabajos publicados en <em>Journal of Agricultural and Food Chemistry</em> han encontrado proporciones superiores al 63 % de los ácidos grasos en distintas especies de bellota. Estudios en cerdo ibérico también muestran que dieta y sistema de producción influyen en la acumulación de ácidos grasos y tocoferoles.</p>
<p>Pero sería demasiado simplista decir que “bellota = una cifra fija de ácido oleico”. Intervienen genética, dieta completa, pasto, metabolismo del animal, tejido analizado y proceso de curación. Más detalle en <a href="/acido-oleico-jamon-iberico-que-es-de-donde-procede/">ácido oleico en el jamón ibérico</a>.</p>

<h2>¿Hay diferencias nutricionales entre bellota, cebo de campo y cebo?</h2>
<p>Puede haber diferencias en el perfil de la grasa, pero <strong>la denominación comercial no proporciona por sí sola una tabla nutricional exacta</strong>. La proteína y la grasa total por 100 g también dependen de deshidratación, zona del jamón y cantidad de grasa visible/infiltrada.</p>
<p>Si comparas dos productos concretos, usa sus etiquetas nutricionales o análisis equivalentes. No conviertas un promedio experimental en una promesa para todas las piezas del mercado.</p>

<h2>¿La bellota siempre sabe mejor?</h2>
<p>El sistema de bellota es el de mayor exigencia dentro de la norma y puede producir perfiles muy complejos, pero el gusto es personal y la elaboración sigue siendo decisiva. Curación, salado, bodega, punto de maduración y servicio pueden hacer que dos jamones de la misma categoría sean muy diferentes.</p>

<h2>Cómo elegir según presupuesto y uso</h2>
<ul>
<li><strong>Bellota:</strong> para quien busca el sistema de producción más ligado a montanera/dehesa y está dispuesto a pagar su mayor coste.</li>
<li><strong>Cebo de campo:</strong> alternativa intermedia con manejo al aire libre regulado y alimentación basada en pienso.</li>
<li><strong>Cebo:</strong> opción más accesible dentro del ibérico, adecuada para consumo frecuente si el productor y la curación son buenos.</li>
</ul>
<p>Dentro de cualquiera de ellas sigue importando porcentaje racial, productor, peso, curación y formato.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Brida roja es mejor que negra?</h3><p>La negra corresponde a bellota 100 % ibérico; la roja a bellota ibérico 50/75 %. “Mejor” depende de criterio, pero son categorías diferentes.</p>
<h3>¿La verde significa que comió bellotas?</h3><p>No. Verde significa cebo de campo ibérico.</p>
<h3>¿Puede haber cebo 100 % ibérico?</h3><p>Sí. Raza y alimentación son variables independientes.</p>
<h3>¿Bellota significa que el animal solo come bellotas?</h3><p>No. Durante la montanera aprovecha recursos naturales de la dehesa, entre ellos bellotas y pastos.</p>

<h2>Fuentes</h2>
<ul>
<li><a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">BOE – Real Decreto 4/2014</a>.</li>
<li><a href="https://pubmed.ncbi.nlm.nih.gov/22062055/" rel="nofollow">Rey et al., Meat Science: alimentación, ácidos grasos y tocoferoles en cerdo ibérico</a>.</li>
<li><a href="https://pubs.acs.org/doi/10.1021/jf030216v" rel="nofollow">Vinha et al./estudio de composición de bellotas en Journal of Agricultural and Food Chemistry</a>.</li>
</ul>
HTML;

$format = <<<'HTML'
<p><strong>No existe un formato universalmente mejor.</strong> La pieza entera ofrece ritual de corte, evolución entre zonas y buen rendimiento si se consume con frecuencia. El loncheado al vacío ofrece comodidad, raciones controladas y menor riesgo de que una pieza abierta se reseque por consumo lento.</p>

<h2>Jamón en pieza vs loncheado: diferencias principales</h2>
<table><thead><tr><th>Aspecto</th><th>Pieza entera</th><th>Loncheado al vacío</th></tr></thead><tbody>
<tr><td>Comodidad</td><td>Requiere jamonero, cuchillo y cierta técnica</td><td>Abrir, atemperar y servir</td></tr>
<tr><td>Rendimiento</td><td>Hay hueso, corteza y recortes; puede aprovecharse todo de distintas formas</td><td>El peso comprado es prácticamente producto listo para comer</td></tr>
<tr><td>Conservación una vez iniciado</td><td>La superficie queda expuesta y evoluciona</td><td>Cada sobre permanece cerrado hasta su consumo</td></tr>
<tr><td>Experiencia</td><td>Permite recorrer maza, babilla, punta y otras zonas</td><td>Uniformidad y facilidad para servir</td></tr>
<tr><td>Ritmo ideal de consumo</td><td>Medio/alto</td><td>Muy flexible</td></tr>
</tbody></table>

<h2>La pregunta clave: ¿cuánto tardarás en consumirlo?</h2>
<p>Una pieza abierta no mejora indefinidamente en la cocina de casa. A medida que pasan los días, la zona de corte pierde humedad y puede oxidarse. Si el consumo es frecuente, el corte diario funciona muy bien. Si solo comes jamón ocasionalmente, los sobres reducen el tiempo que el producto pasa expuesto.</p>
<p>Antes de comprar una pieza grande, calcula vuestro consumo con <a href="/cuanto-jamon-iberico-calcular-por-persona/">cuánto jamón servir por persona</a> y <a href="/que-peso-jamon-iberico-elegir-segun-personas-consumo/">qué peso de pieza elegir</a>.</p>

<h2>¿Sale más barato comprar una pieza?</h2>
<p>El precio por kilo de pieza no es comparable directamente con el precio por kilo loncheado. En una pieza pagas también hueso, corteza y grasa de cobertura. A cambio, huesos y recortes pueden aprovecharse en cocina y un buen cortador puede obtener gran rendimiento.</p>
<p>Para comparar de forma justa, piensa en <strong>euros por kilo de producto listo para comer</strong> y añade el valor que das al servicio de deshuesado, corte y envasado.</p>

<h2>Cuánto jamón aprovechable sale de una pieza</h2>
<p>El rendimiento varía por peso, morfología, cantidad de grasa, habilidad de corte y criterio sobre qué se considera aprovechable. No existe un porcentaje exacto para todas las piezas. Si quieres cifras y ejemplos prácticos, consulta <a href="/cuantos-sobres-salen-jamon-iberico-rendimiento-real-pieza/">cuántos sobres pueden salir de un jamón ibérico</a>.</p>

<h2>Ventajas de la pieza entera</h2>
<ul>
<li>Experiencia de corte y servicio en casa.</li>
<li>Posibilidad de adaptar grosor y cantidad en cada momento.</li>
<li>Descubrir diferencias entre zonas de la pieza.</li>
<li>Aprovechar huesos, taquitos y recortes.</li>
<li>Muy práctica en hogares o reuniones con consumo alto.</li>
</ul>

<h2>Ventajas del loncheado al vacío</h2>
<ul>
<li>Raciones previsibles y sin merma doméstica.</li>
<li>No exige cuchillos específicos ni jamonero.</li>
<li>Cada sobre permanece protegido hasta abrirlo.</li>
<li>Facilita repartir el consumo durante semanas o meses siguiendo la conservación indicada.</li>
<li>Es fácil comparar coste por cantidad comestible.</li>
</ul>

<h2>¿Cortado a cuchillo o a máquina?</h2>
<p>Es otra decisión distinta del formato. El corte a cuchillo puede adaptar cada loncha a la anatomía y producir lonchas finas e irregulares características. Una buena máquina profesional también puede lograr espesores muy finos y consistentes. El resultado depende mucho del operario, temperatura del producto y equipo.</p>
<p>Consulta <a href="/jamon-iberico-cortado-cuchillo-o-maquina-diferencias-cual-elegir/">jamón cortado a cuchillo vs a máquina</a>.</p>

<h2>Cómo servir un sobre para que no parezca “frío de nevera”</h2>
<p>El error más habitual es abrir y comer directamente a baja temperatura. Sigue las indicaciones del productor y deja que el producto alcance una temperatura de servicio adecuada antes de consumirlo. La grasa se vuelve más flexible y los aromas se perciben mejor.</p>
<p>No calientes el jamón de forma agresiva para acelerar el proceso. Una atemperación gradual es preferible. Más detalles en <a href="/como-servir-jamon-iberico-temperatura-corte-emplatado/">cómo servir jamón ibérico</a>.</p>

<h2>Cómo conservar una pieza empezada</h2>
<p>Debe mantenerse en lugar fresco, seco, ventilado y alejado de fuentes de calor. Protege la superficie de corte siguiendo buenas prácticas y recorta solo lo necesario antes de cada servicio. Si aparecen cambios de olor, moho o aspecto, distingue fenómenos superficiales normales de señales de deterioro con nuestras guías específicas.</p>

<h2>¿Y una pieza deshuesada?</h2>
<p>Es un tercer formato: conserva la idea de cortar según necesidad pero elimina el hueso y facilita almacenamiento. Requiere un sistema de corte diferente, normalmente cuchillo largo o máquina. Consulta <a href="/jamon-entero-o-deshuesado-diferencias-cual-elegir/">jamón entero vs deshuesado</a>.</p>

<h2>Qué formato elegir según tu caso</h2>
<table><thead><tr><th>Situación</th><th>Formato que suele encajar mejor</th></tr></thead><tbody>
<tr><td>Familia numerosa / consumo frecuente</td><td>Pieza entera</td></tr>
<tr><td>Una o dos personas / consumo ocasional</td><td>Loncheado</td></tr>
<tr><td>Regalo sin saber si tienen jamonero</td><td>Loncheado o estuche</td></tr>
<tr><td>Celebración y gusto por cortar</td><td>Pieza entera</td></tr>
<tr><td>Hostelería con máquina de corte</td><td>Deshuesado puede ser eficiente</td></tr>
</tbody></table>

<h2>Preguntas frecuentes</h2>
<h3>¿El loncheado es de peor calidad?</h3><p>No. El formato no determina la categoría de la materia prima. Puede lonchearse un excelente jamón de bellota 100 % ibérico o uno de cualquier otra categoría.</p>
<h3>¿Una pieza siempre sale más barata?</h3><p>No necesariamente cuando calculas el producto realmente comestible y el servicio de corte.</p>
<h3>¿Cuánto dura un sobre?</h3><p>Depende del tratamiento y fecha indicados por el productor. Respeta siempre la fecha y condiciones de conservación del envase.</p>
<h3>¿Qué formato conserva mejor el jamón una vez empezado?</h3><p>Para consumos espaciados, sobres individuales cerrados reducen la exposición de todo el producto. Para consumo rápido, una pieza bien conservada funciona perfectamente.</p>
HTML;

$oleic = <<<'HTML'
<p><strong>El ácido oleico es un ácido graso monoinsaturado (omega-9)</strong> y puede representar una proporción elevada de la grasa del cerdo ibérico. La bellota es rica en ácido oleico, pero el perfil final del jamón no depende solo de “cuántas bellotas comió”: influyen genética, dieta completa, pastos, metabolismo, tejido y elaboración.</p>

<h2>Qué es el ácido oleico</h2>
<p>Es el ácido graso cis-9-octadecenoico, conocido como C18:1 n-9. Es también el ácido graso predominante del aceite de oliva. En el jamón ibérico forma parte de los triglicéridos de la grasa subcutánea e intramuscular junto con ácidos grasos saturados y poliinsaturados.</p>
<p>Que una grasa animal contenga mucho ácido oleico no significa que sea “aceite de oliva sólido”: la matriz alimentaria, las proporciones de otros ácidos grasos y el resto de componentes son diferentes.</p>

<h2>De dónde procede el ácido oleico del jamón ibérico</h2>
<p>Hay dos vías principales. Una parte procede de los ácidos grasos de la dieta y otra se relaciona con el propio metabolismo lipídico del animal. En el cerdo, la composición de la grasa corporal responde en mayor medida a la dieta que en los rumiantes, porque no existe la misma biohidrogenación ruminal.</p>
<p>Por eso la fase de acabado puede modificar el perfil de grasa, aunque nunca sea el único factor.</p>

<h2>Por qué se relaciona la bellota con el ácido oleico</h2>
<p>Las bellotas contienen una fracción grasa muy rica en ácido oleico. Un estudio de varias especies de <em>Quercus</em> publicado en <em>Journal of Agricultural and Food Chemistry</em> encontró más de un 63 % de ácido oleico dentro de sus ácidos grasos. Estudios de alimentación en cerdo ibérico han observado cambios en la composición de ácidos grasos y tocoferoles según dieta y sistema de producción.</p>
<p>Esto explica la base científica de la relación bellota–perfil oleico, pero no autoriza a atribuir un porcentaje único a todos los jamones de bellota.</p>

<h2>¿La bellota “pasa” directamente al jamón?</h2>
<p>No de forma literal. El alimento se digiere, absorbe y metaboliza. Los ácidos grasos dietarios pueden contribuir al depósito adiposo, mientras que el organismo también sintetiza y transforma ácidos grasos. Además, durante la curación ocurren lipólisis y reacciones oxidativas que participan en el aroma.</p>
<p>El resultado final es una interacción entre animal, dieta y proceso, no una simple transferencia 1:1.</p>

<h2>¿El jamón de bellota siempre tiene más ácido oleico que el de cebo?</h2>
<p><strong>No puede garantizarse para cualquier pareja de productos solo leyendo la categoría.</strong> Históricamente la montanera y la bellota se asocian a perfiles muy ricos en monoinsaturados, pero hoy también existen piensos formulados con materias primas ricas en ácido oleico. La clasificación legal de bellota/cebo de campo/cebo se basa en alimentación y manejo definidos por la norma, no en superar un porcentaje químico fijo de ácido oleico en el jamón terminado.</p>

<h2>¿Cuánto ácido oleico tiene el jamón ibérico?</h2>
<p>La cifra depende de si se mide grasa subcutánea, intramuscular o la porción comestible completa, además de dieta y genética. Estudios clásicos han descrito valores muy elevados en la grasa de cerdo ibérico alimentado con bellota; por ejemplo, un trabajo publicado en <em>Nutrición Hospitalaria</em> encontró alrededor del 59 % de ácido oleico en grasa subcutánea de animales alimentados con bellota.</p>
<p>No conviertas ese valor experimental en la etiqueta nutricional de cualquier jamón: el porcentaje de ácido oleico <strong>sobre los ácidos grasos</strong> no es lo mismo que gramos de ácido oleico <strong>por 100 g de jamón</strong>.</p>

<h2>Ácido oleico, grasa total y porcentaje ibérico: tres cosas distintas</h2>
<table><thead><tr><th>Dato</th><th>Qué significa</th></tr></thead><tbody>
<tr><td>50/75/100 % ibérico</td><td>Porcentaje racial del animal.</td></tr>
<tr><td>Gramos de grasa por 100 g</td><td>Cantidad total de grasa en la porción analizada.</td></tr>
<tr><td>% de ácido oleico en los ácidos grasos</td><td>Composición relativa de esa grasa.</td></tr>
</tbody></table>
<p>Mezclar estos tres niveles produce titulares nutricionales incorrectos.</p>

<h2>Qué otros ácidos grasos hay en el jamón ibérico</h2>
<p>Además de oleico aparecen ácido palmítico y esteárico entre los saturados, y linoleico y otros ácidos grasos poliinsaturados en menor proporción. La mezcla concreta cambia según la pieza y el sistema de producción. Consulta <a href="/grasa-jamon-iberico-saturada-monoinsaturada-poliinsaturada/">qué tipos de grasa tiene el jamón ibérico</a>.</p>

<h2>¿El ácido oleico explica que la grasa se funda fácilmente?</h2>
<p>El perfil de ácidos grasos influye en propiedades físicas como el punto de fusión. Una mayor proporción de insaturados tiende a producir grasas más blandas que una grasa con mayor proporción de saturados. Pero la textura real también depende de temperatura, estructura del tejido y mezcla completa de lípidos.</p>

<h2>Cómo se relaciona esto con la calidad</h2>
<p>El ácido oleico es una pieza del rompecabezas, no un certificado de calidad. Para valorar un jamón hay que sumar raza, alimentación y manejo, curación, salado, trazabilidad, conservación y evaluación sensorial. La norma del ibérico no clasifica los jamones comerciales mediante un “ranking de ácido oleico”.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿La bellota tiene ácido oleico?</h3><p>Sí. En distintas especies estudiadas, el oleico es el ácido graso mayoritario de la fracción grasa de la bellota.</p>
<h3>¿El jamón ibérico tiene el mismo ácido oleico que el AOVE?</h3><p>Comparten el mismo tipo de ácido graso, pero son alimentos con composiciones y matrices muy diferentes.</p>
<h3>¿100 % ibérico significa más ácido oleico?</h3><p>No necesariamente. El 100 % describe raza; el perfil de grasa depende también de alimentación y otros factores.</p>
<h3>¿La brida negra garantiza un porcentaje de ácido oleico?</h3><p>No fija un porcentaje químico concreto. Garantiza la categoría “bellota 100 % ibérico” dentro de la norma.</p>

<h2>Fuentes científicas y normativas</h2>
<ul>
<li><a href="https://www.boe.es/eli/es/rd/2014/01/10/4/con" rel="nofollow">Real Decreto 4/2014 – norma de calidad del ibérico</a>.</li>
<li><a href="https://pubs.acs.org/doi/10.1021/jf030216v" rel="nofollow">Phenolic Compounds and Fatty Acids from Acorns, Journal of Agricultural and Food Chemistry</a>.</li>
<li><a href="https://pubmed.ncbi.nlm.nih.gov/22062055/" rel="nofollow">Rey et al. – alimentación con bellota/pasto y composición lipídica del cerdo ibérico</a>.</li>
<li><a href="https://pubmed.ncbi.nlm.nih.gov/1420485/" rel="nofollow">Martín Peña et al. – grasa de cerdo ibérico y ácidos grasos monoinsaturados</a>.</li>
</ul>
HTML;

emdo_jamon_update(
	'50-75-100-iberico-que-significa-porcentaje-raza-etiqueta',
	'50 %, 75 % y 100 % ibérico: qué significa el porcentaje, qué cambia y cómo leer la etiqueta',
	'Guía completa para entender 50 %, 75 % y 100 % ibérico: genética, cruces, alimentación, bridas, etiqueta y por qué el porcentaje racial no determina por sí solo la nutrición.',
	$race,
	'50 %, 75 % y 100 % ibérico: qué significa el porcentaje racial, cómo se cruza con bellota/cebo, qué brida corresponde y qué cambia realmente en el jamón.'
);
emdo_jamon_update(
	'jamon-o-paleta-diferencias-cual-elegir',
	'Jamón vs paleta ibérica: diferencias, rendimiento, sabor, nutrición y cuál elegir',
	'Jamón o paleta ibérica: diferencias de anatomía, peso, curación, rendimiento, sabor, nutrición, precio y consumo para elegir la pieza que más compensa.',
	$ham_shoulder,
	'Jamón vs paleta ibérica: diferencias de parte del cerdo, curación, rendimiento, sabor, nutrición, precio y tamaño para saber cuál compensa más en cada casa.'
);
emdo_jamon_update(
	'jamon-iberico-vs-jamon-serrano-diferencias-como-elegir',
	'Jamón ibérico vs jamón serrano: diferencias de raza, grasa, curación, sabor y precio',
	'Comparativa completa de jamón ibérico y serrano: qué significa cada denominación, raza, grasa, curación, nutrición, etiquetado, sabor, precio y cómo elegir.',
	$serrano,
	'Jamón ibérico vs serrano: diferencias de raza, norma, grasa, nutrición, sabor, precio y etiquetado, con claves para distinguirlos y elegir con criterio.'
);
emdo_jamon_update(
	'jamon-bellota-cebo-campo-cebo-diferencias-tipos-jamon-iberico',
	'Jamón de bellota vs cebo de campo vs cebo: diferencias, bridas, alimentación y nutrición',
	'Diferencias entre jamón de bellota, cebo de campo y cebo: alimentación, manejo, montanera, bridas, raza, grasa, nutrición, sabor, precio y cómo elegir.',
	$feeding,
	'Bellota vs cebo de campo vs cebo: qué cambia en alimentación y manejo, qué significan las bridas, cómo influye la bellota en la grasa y cómo elegir.'
);
emdo_jamon_update(
	'jamon-en-pieza-o-loncheado-al-vacio-ventajas-que-compensa',
	'Jamón en pieza vs loncheado al vacío: rendimiento, conservación, precio y qué compensa más',
	'Comparativa de jamón en pieza y loncheado: rendimiento real, conservación, comodidad, precio por producto comestible, ritmo de consumo y cuál elegir.',
	$format,
	'Jamón en pieza vs loncheado al vacío: compara rendimiento, conservación, comodidad, coste real y ritmo de consumo para elegir el formato que más compensa.'
);
emdo_jamon_update(
	'acido-oleico-jamon-iberico-que-es-de-donde-procede',
	'Ácido oleico en el jamón ibérico: qué es, cuánto puede tener y cómo influye la bellota',
	'Guía del ácido oleico en jamón ibérico: qué es, de dónde procede, por qué se relaciona con la bellota, cuánto puede contener la grasa y qué no dice la etiqueta.',
	$oleic,
	'Ácido oleico en jamón ibérico: qué es, por qué la bellota se relaciona con él, cuánto puede aparecer en la grasa y por qué raza o brida no fijan una cifra exacta.'
);

$redirects = array(
	'100-75-50-iberico-que-significa-porcentaje' => '50-75-100-iberico-que-significa-porcentaje-raza-etiqueta',
	'diferencias-nutricionales-jamon-100-75-50-iberico' => '50-75-100-iberico-que-significa-porcentaje-raza-etiqueta',
	'jamon-iberico-o-paleta-iberica-diferencias-cual-elegir' => 'jamon-o-paleta-diferencias-cual-elegir',
	'jamon-vs-paleta-iberica-diferencias-nutricionales' => 'jamon-o-paleta-diferencias-cual-elegir',
	'jamon-iberico-vs-jamon-serrano-diferencias-nutricionales' => 'jamon-iberico-vs-jamon-serrano-diferencias-como-elegir',
	'jamon-iberico-bellota-vs-cebo-campo-vs-cebo-diferencias-nutricionales' => 'jamon-bellota-cebo-campo-cebo-diferencias-tipos-jamon-iberico',
	'jamon-pieza-entera-o-loncheado-como-elegir' => 'jamon-en-pieza-o-loncheado-al-vacio-ventajas-que-compensa',
	'bellota-cambia-grasa-jamon-iberico' => 'acido-oleico-jamon-iberico-que-es-de-donde-procede',
	'por-que-jamon-bellota-tiene-mas-acido-oleico' => 'acido-oleico-jamon-iberico-que-es-de-donde-procede',
);
foreach ( $redirects as $source => $destination ) { emdo_jamon_draft( $source, $destination ); }
emdo_jamon_links( $redirects );
update_option( 'emdo_blog_consolidation_batch2_20260901', gmdate( 'c' ), false );
echo "BATCH2_OK\n";
