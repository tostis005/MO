<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_duck_patch_words_20260928( string $html ): int {
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $html ) ) ) );
	if ( '' === $text ) { return 0; }
	preg_match_all( '/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u', $text, $matches );
	return count( $matches[0] );
}

$extras = array(
'duck-02' => <<<'HTML'
<h2>Cómo decidir entre formatos cuando compras foie gras</h2>
<p>Una forma útil de elegir es empezar por la escena de consumo y recorrerla al revés. Si quieres cortar porciones limpias en un plato, busca un formato que mantenga estructura. Si necesitas veinte canapés iguales, un bloc facilita el trabajo. Si vas a cocinar escalopes, la compra debe ser foie fresco. Y si el producto viajará o permanecerá varios días en despensa antes de regalarse, la estabilidad de una conserva puede pesar más que la textura de un micuit.</p>
<p>También conviene calcular el tamaño antes de comprar. Un envase pequeño evita sobrantes cuando sois dos; un formato grande tiene sentido en una comida numerosa. Compara precio por kilo solo entre productos de la misma categoría: enfrentar un paté con un foie gras entero únicamente por precio confunde composiciones y usos diferentes.</p>
HTML,
'duck-16' => <<<'HTML'
<h2>Cómo hacer una cata de curado y ahumado</h2>
<p>Para apreciar de verdad la diferencia, sirve ambos jamones a la misma temperatura, con el mismo grosor de loncha y sobre platos separados. Empieza por el curado sin humo y deja el ahumado para después, porque sus aromas permanecen más tiempo en boca. Entre uno y otro, agua y un trozo de pan neutro son suficientes para limpiar el paladar.</p>
<p>No añadas queso, mermelada o mostaza durante la primera comparación. Prueba primero el producto solo y después incorpora acompañamientos. Así puedes distinguir qué notas proceden del secado, la pimienta y la propia carne, y cuáles aparecen por el proceso de ahumado. Esta pequeña cata también ayuda a decidir qué referencia encaja mejor en una ensalada, una tabla o un canapé.</p>
HTML,
'duck-19' => <<<'HTML'
<h2>Cuándo merece la pena elegir parfait en vez de mousse</h2>
<p>El parfait tiene sentido cuando quieres que el hígado graso domine claramente el sabor pero prefieres una textura uniforme y fácil de servir. En un entrante de plato puede colocarse en una porción definida con pan y un único contraste ácido. La mousse encaja mejor cuando la ligereza de textura y la facilidad para untar son prioritarias, por ejemplo en una bandeja numerosa de canapés.</p>
<p>Si vas a comparar precios, revisa el peso neto y la proporción declarada de foie. Un tarro pequeño de parfait puede cundir mucho porque se sirve en capas finas. Una mousse puede cubrir más tostadas gracias a su textura aireada, pero su intensidad dependerá de la receta. Elegir por función evita convertir dos productos distintos en sustitutos automáticos.</p>
HTML,
'duck-20' => <<<'HTML'
<h2>Organizar el frigorífico cuando hay varios productos de foie</h2>
<p>Si compras varias referencias para una celebración, etiqueta mentalmente tres grupos: crudos, listos para comer refrigerados y conservas cerradas. Mantén el foie fresco separado y en la zona más fría; micuit y terrinas deben permanecer protegidos de alimentos crudos; las conservas estables pueden quedarse fuera del frigorífico hasta su apertura si la etiqueta lo permite.</p>
<p>El día de la comida, saca únicamente la cantidad que vayas a servir en cada tanda. Así evitas que una terrina completa pase repetidamente del frío a la mesa. Después, anota la fecha de apertura en el envase o en un recipiente cerrado. Esta práctica sencilla resulta especialmente útil cuando hay varios tarros abiertos y cada uno tiene un plazo de consumo diferente.</p>
HTML,
'duck-29' => <<<'HTML'
<h2>Cómo construir el plato según la época del año</h2>
<p>En otoño e invierno, el confit admite guarniciones profundas como lentejas, puré de apionabo, setas o col salteada. Para que el conjunto no resulte pesado, incorpora siempre un elemento ácido: mostaza, vinagre, manzana o una ensalada pequeña. En primavera y verano, reduce la fécula y aumenta hojas amargas, judías verdes, cítricos y verduras crujientes.</p>
<p>También cambia la cantidad de salsa. Con una guarnición cremosa basta un jugo ligero; con una ensalada fresca puedes permitirte una reducción de naranja o vino en una porción pequeña. Pensar primero en temperatura, estación y textura evita terminar siempre con el mismo plato de confit y patatas.</p>
HTML,
'duck-35' => <<<'HTML'
<h2>Tres tablas de jamón de pato según la ocasión</h2>
<p><strong>Aperitivo rápido:</strong> jamón de pato, baguette tostada, manzana verde y almendras sin sal. <strong>Cena informal:</strong> añade endivias, queso de cabra suave y pepinillos para que la tabla pueda funcionar como entrante. <strong>Cata:</strong> sirve curado y ahumado por separado, con pan neutro, pera y agua, evitando otros sabores intensos.</p>
<p>La lógica es distinta en cada caso. En el aperitivo buscas comodidad; en la cena necesitas algo más de volumen vegetal; en la cata interesa reducir interferencias. Esta forma de plantear la tabla ayuda a comprar únicamente los complementos que cumplen una función y evita llenar la mesa de productos que compiten entre sí.</p>
HTML,
'duck-37' => <<<'HTML'
<h2>Cómo preparar la ensalada para que llegue crujiente a la mesa</h2>
<p>Lava y seca muy bien las hojas; el agua residual diluye la vinagreta. Guarda por separado fruta, frutos secos, jamón y aliño hasta el último momento. Las nueces pueden tostarse con antelación y la vinagreta aguanta varias horas emulsionada en un frasco. Corta manzana o pera cerca del servicio o protégelas con unas gotas de cítrico.</p>
<p>Aliña primero las hojas con poca cantidad, reparte en platos y coloca después el jamón atemperado. De esta forma las lonchas no quedan empapadas de aceite y mantienen mejor textura. Termina con fruta y fruto seco. El orden de montaje es pequeño, pero marca mucha diferencia en una ensalada que puede estar esperando mientras se termina el resto del menú.</p>
HTML,
'duck-39' => <<<'HTML'
<h2>Cómo plantear una degustación de patés de pato</h2>
<p>Si tienes dos o tres patés diferentes, sírvelos en porciones pequeñas y ordénalos de menor a mayor intensidad. Utiliza el mismo pan para todos y deja los acompañamientos para una segunda vuelta. Primero prueba cada paté solo; después añade pepinillo, manzana o mostaza y observa qué combinación mejora realmente el producto.</p>
<p>Una degustación resulta más útil cuando las recetas son distintas: un paté sencillo, otro especiado y uno con foie ofrecen más información que tres versiones casi idénticas. Identificar ingredientes y porcentaje de pato antes de probar ayuda además a relacionar la etiqueta con el sabor y a elegir mejor una próxima compra.</p>
HTML,
'duck-40' => <<<'HTML'
<h2>Cómo calcular y organizar una bandeja para invitados</h2>
<p>Para una reunión de seis personas con otros aperitivos, prepara entre doce y dieciocho canapés de paté repartidos en dos o tres sabores. Mantén el tamaño pequeño y utiliza bases iguales para trabajar más rápido. Si la bandeja será el aperitivo principal, aumenta unidades, no el grosor de cada canapé.</p>
<p>Organiza el montaje como una cadena: bases tostadas, paté, topping y bandeja. Trabajar de esta forma permite mantener porciones regulares y reduce el tiempo que el producto permanece fuera del frío. Reserva una parte sin montar en el frigorífico y repón cuando se vacíe la primera bandeja; la segunda tanda llegará más fresca y el pan conservará mejor su textura.</p>
HTML,
'duck-42' => <<<'HTML'
<h2>Cómo escoger una mousse aromatizada para cocinar o regalar</h2>
<p>Para una tabla con quesos y embutidos, la versión a la pimienta aporta contraste sin sumar dulzor. En un regalo, coñac suele transmitir un perfil clásico y reconocible. El orujo encaja mejor con alguien que disfruta sabores vínicos y destilados. Estas son orientaciones de estilo: la composición base sigue siendo el dato decisivo.</p>
<p>Si la mousse va a utilizarse en una receta caliente, recuerda que el aroma puede cambiar con el calor y la emulsión puede separarse. Es preferible incorporarla al final de una salsa o utilizarla en un relleno que no pase mucho tiempo en horno. Para canapés fríos, en cambio, la aromatización se percibe de manera más nítida.</p>
HTML,
'duck-43' => <<<'HTML'
<h2>Un entrante completo con rillettes</h2>
<p>Para convertir un tarro en un plato y no solo en un untable, calcula una pequeña porción de rillettes, una ensalada de endivia y manzana, dos rebanadas de pan tostado y unos pepinillos. Aliña las hojas con mostaza y vinagre, pero muy poco aceite: la carne ya aporta grasa suficiente.</p>
<p>Sirve las rillettes en una pequeña quenelle o en su propio recipiente, dejando que cada comensal se sirva. La ensalada mantiene frescor entre bocados y el encurtido introduce acidez. Este formato resulta más equilibrado que una gran tostada cubierta por una capa gruesa de carne y permite apreciar mejor la textura deshilachada.</p>
HTML,
'duck-44' => <<<'HTML'
<h2>Una receta rápida de solomillos con setas</h2>
<p>Para dos personas, saltea unos 250-300 gramos de solomillos secos y sazonados en una sartén caliente con una cucharadita de grasa. Retira cuando estén cocinados y reserva. En la misma sartén dora 200 gramos de setas sin amontonarlas, añade una chalota picada y desglasa con un poco de caldo.</p>
<p>Devuelve los solomillos solo durante el tiempo necesario para calentarlos y termina con perejil y unas gotas de limón. Esta secuencia mantiene la carne tierna porque no hierve durante toda la cocción de las setas. Sirve con puré, arroz o pan crujiente. Si necesitas seguir una recomendación conservadora de seguridad, comprueba 74&nbsp;°C en el centro.</p>
HTML,
'duck-45' => <<<'HTML'
<h2>Cómo cambia la compra según el tiempo que tienes</h2>
<p>Si empiezas a cocinar con varias horas de margen, el muslo fresco permite braseado, asado lento o un confitado casero. Si llegas a casa con treinta minutos antes de cenar, el confit preparado es claramente más práctico: puede calentarse y dorarse mientras haces la guarnición. Para una comida grande, esa diferencia logística se multiplica.</p>
<p>También cambia el almacenamiento. El fresco obliga a decidir pronto entre cocinar o congelar; una conserva de confit puede permanecer en despensa según su etiqueta. Por tanto, la comparación no es solo culinaria: tiempo disponible, espacio de frío y planificación de la compra son criterios tan útiles como el sabor.</p>
HTML,
'duck-46' => <<<'HTML'
<h2>Manchones crujientes con glaseado ácido</h2>
<p>Si partes de manchones confitados, retira grasa y hornéalos con la piel expuesta hasta que estén bien calientes. Termina con grill o air fryer para dorar. Mientras tanto mezcla mostaza, una pequeña cucharadita de miel y vinagre de Jerez. El resultado debe saber más ácido que dulce.</p>
<p>Pinta los manchones al salir del horno y vuelve a darles solo uno o dos minutos de calor. Así el glaseado se fija sin reblandecer por completo la superficie. Sírvelos con col rallada o encurtidos. Esta preparación funciona como aperitivo informal y aprovecha el formato pequeño mejor que intentar presentarlo como un muslo principal.</p>
HTML,
'duck-47' => <<<'HTML'
<h2>Corazones salteados con cebolla y Jerez</h2>
<p>Para una tapa, limpia y abre los corazones por la mitad. Dora primero cebolla en tiras hasta que tome color y retira. Sube el fuego, añade los corazones bien secos y cocínalos en una sola capa. Cuando estén hechos, devuelve la cebolla y desglasa con una pequeña cantidad de Jerez seco.</p>
<p>Deja que el alcohol se evapore y termina con perejil. La salsa debe ser corta, apenas suficiente para cubrir la carne. Pan crujiente o unas patatas cocidas completan el plato. Si buscas el criterio más conservador de seguridad para menudencias de ave, comprueba una temperatura interna de 74&nbsp;°C.</p>
HTML,
'duck-50' => <<<'HTML'
<h2>Cómo adaptar una receta de pollo cuando utilizas pato</h2>
<p>Empieza reduciendo la grasa añadida. Si la receta pide dorar pechuga de pollo en aceite, un magret puede empezar sin aceite y liberar suficiente grasa para todo el plato. Después, ajusta la salsa: el pato soporta más acidez y suele necesitar menos nata o mantequilla para resultar jugoso.</p>
<p>Con muslos sucede lo contrario: el tiempo de cocción lenta puede mantenerse o incluso alargarse según tamaño, pero conviene retirar grasa del fondo antes de servir. Las especias que funcionan con pollo también pueden funcionar con pato, aunque naranja, cereza, vino, mostaza y setas encuentran un compañero especialmente natural en su sabor más marcado.</p>
HTML,
'duck-52' => <<<'HTML'
<h2>Ejemplo de cálculo para una comida de ocho personas</h2>
<p>Si el plato principal será magret con dos aperitivos previos, ocho raciones de 150-170 gramos de carne útil equivalen aproximadamente a 1,2-1,4 kilos. Comprueba el peso real de cada magret para decidir cuántas piezas necesitas. Si eliges confit, una pieza por adulto simplifica el cálculo y puedes comprar una o dos unidades extra si el tamaño es pequeño.</p>
<p>Para el aperitivo, un bloc de foie de 250 gramos puede dar una porción pequeña a ocho personas y aún dejar margen según el corte. Un estuche de 250-300 gramos de jamón de pato también puede funcionar dentro de una tabla compartida. Planificar el conjunto evita comprar cada producto como si fuera el único plato de la comida.</p>
HTML,
'duck-53' => <<<'HTML'
<h2>Cómo organizar un congelador de cortes de pato</h2>
<p>Divide la compra por futuros usos antes de congelar. Guarda los magrets individualmente, agrupa solomillos en raciones y separa muslos de la carcasa si compras un pato entero. Los paquetes planos se congelan y descongelan de forma más uniforme y permiten aprovechar mejor el espacio.</p>
<p>Anota fecha, peso y corte en cada bolsa. También puedes escribir el uso previsto —“arroz”, “asado”, “2 personas”— para evitar descongelar más de lo necesario. Coloca los paquetes nuevos detrás de los antiguos y revisa una vez al mes. Este sistema sencillo reduce pérdidas de calidad y ayuda a respetar mejor los periodos recomendados de congelación.</p>
HTML,
'duck-54' => <<<'HTML'
<h2>Cómo elegir la salsa antes de empezar a cocinar</h2>
<p>Piensa primero en la guarnición. Si habrá puré y setas, una salsa de vino o fondo mantiene el plato salado. Si servirás hojas amargas, una salsa de naranja o frutos rojos aporta contraste. Si la guarnición ya lleva fruta o boniato, evita Pedro Ximénez y otras reducciones muy dulces.</p>
<p>Después valora el corte. Un magret tolera salsas reducidas porque su piel mantiene textura si se sirven alrededor. Un confit necesita menos cantidad y más acidez. Solomillos pequeños agradecen salsas rápidas que no los obliguen a seguir cocinándose. Elegir la salsa con estos dos criterios evita recetas donde todos los componentes aportan el mismo dulzor o la misma grasa.</p>
HTML,
'duck-55' => <<<'HTML'
<h2>Un recorrido de vinos para una comida completa con pato</h2>
<p>Si el aperitivo incluye foie o jamón de pato, un espumoso brut puede acompañar ambos y continuar con una ensalada. Para un principal de magret o confit, cambia después a un tinto de cuerpo medio con buena acidez. De esta forma solo necesitas dos vinos y cada uno cumple una función clara.</p>
<p>Con un magret a la naranja, prueba un tinto ligero o un blanco con cuerpo antes de asumir que necesitas un vino potente. Con confit y lentejas, un tinto más terroso puede funcionar. Para profundizar en el plato principal, consulta <a href="/magret-de-pato-a-la-naranja-receta-claves/">la receta de magret a la naranja</a> o <a href="/confit-de-pato-que-es-como-se-prepara-y-con-que-acompanarlo/">la guía del confit</a>.</p>
HTML,
'duck-56' => <<<'HTML'
<h2>Cómo ajustar la salsa según la fruta disponible</h2>
<p>Con frambuesas muy ácidas puedes prescindir de vinagre y añadir una pequeña cantidad de miel. Con moras maduras suele ocurrir lo contrario: conviene reforzar la acidez. Las cerezas admiten vino tinto y especias; la grosella funciona bien con caldo porque ya aporta mucha tensión.</p>
<p>La fruta congelada puede contener más agua superficial, así que empieza con menos líquido y deja reducir antes de decidir la textura. Si la salsa queda demasiado espesa, añade fondo poco a poco. Este ajuste por sabor real es más fiable que una receta cerrada, porque madurez, variedad y temporada cambian enormemente el contenido de azúcar de la fruta.</p>
HTML,
'duck-57' => <<<'HTML'
<h2>Una salsa de Pedro Ximénez pensada para no resultar empalagosa</h2>
<p>Para dos magrets, sofríe una chalota muy picada después de retirar la grasa de la sartén. Añade 50 ml de Pedro Ximénez y deja reducir aproximadamente a la mitad. Incorpora 150 ml de fondo de ave o carne desgrasado y cuece hasta que la salsa cubra ligeramente una cuchara.</p>
<p>Apaga el fuego, prueba y añade entre media y una cucharadita de vinagre de Jerez según el dulzor. Ajusta sal y pimienta. No hace falta incorporar miel, pasas ni azúcar. Sirve una pequeña cantidad alrededor del magret para conservar la piel. Esta fórmula mantiene las notas de fruta seca del vino pero utiliza fondo y acidez para devolver la salsa al terreno salado.</p>
HTML,
'duck-58' => <<<'HTML'
<h2>Cómo montar una bandeja de foie sin que todos los bocados sepan igual</h2>
<p>Utiliza una misma base de foie y cambia únicamente el contraste: un canapé con manzana ácida, otro con avellana tostada y un tercero con naranja amarga. Mantén pan y tamaño iguales. Así los invitados perciben tres perfiles distintos sin que tengas que comprar tres tipos de foie ni preparar muchas salsas.</p>
<p>Coloca etiquetas pequeñas si hay ingredientes que puedan causar alergias o si una versión contiene alcohol. Monta primero una bandeja pequeña y conserva el resto de componentes en frío. Cuando se termine, prepara una segunda. En una celebración larga esta estrategia ofrece mejor textura que dejar cuarenta canapés montados desde el principio.</p>
HTML,
'duck-60' => <<<'HTML'
<h2>Cómo montar un lote de tres niveles de precio</h2>
<p><strong>Pequeño:</strong> jamón de pato loncheado, rillettes y un paté. <strong>Medio:</strong> añade un bloc de foie gras y un complemento como pan o confitura. <strong>Especial:</strong> incorpora foie gras entero, confit y una selección de elaboraciones con texturas distintas. La progresión debe aumentar calidad y variedad, no solo número de tarros.</p>
<p>Antes de cerrar la caja, revisa las condiciones de conservación de cada artículo. Si mezclas refrigerados y conservas, toda la logística queda condicionada por el producto que necesita más frío. Para envíos o regalos corporativos suele ser más sencillo construir el lote exclusivamente con referencias estables hasta su apertura.</p>
HTML,
);

foreach ( $extras as $key => $html ) {
	$ids = get_posts(
		array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_emdo_seo_landing_key',
			'meta_value'     => $key,
			'emdo_include_hidden_blog_islands' => true,
		)
	);
	if ( empty( $ids ) ) { throw new Exception( 'Missing ' . $key ); }
	$id = (int) $ids[0];
	$content = (string) get_post_field( 'post_content', $id );
	$marker = '<!-- emdo-duck-depth-patch-20260928:' . $key . ' -->';
	if ( false === strpos( $content, $marker ) ) {
		$content .= "\n" . $marker . "\n" . $html;
		$result = wp_update_post( wp_slash( array( 'ID' => $id, 'post_content' => $content ) ), true );
		if ( is_wp_error( $result ) ) { throw new Exception( $key . ': ' . $result->get_error_message() ); }
	}
	update_post_meta( $id, '_emdo_duck_content_rewrite', '20260928-v2-depth' );
	clean_post_cache( $id );
	echo $key . '|' . $id . '|' . emdo_duck_patch_words_20260928( (string) get_post_field( 'post_content', $id ) ) . PHP_EOL;
}

/* Retarget the near-duplicate oven article toward texture/finishing methods. */
$ids = get_posts(
	array(
		'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => 1, 'fields' => 'ids',
		'meta_key' => '_emdo_seo_landing_key', 'meta_value' => 'duck-10',
		'emdo_include_hidden_blog_islands' => true,
	)
);
if ( ! empty( $ids ) ) {
	$id = (int) $ids[0];
	wp_update_post( wp_slash( array(
		'ID' => $id,
		'post_title' => 'Cómo conseguir una piel crujiente en el confit de pato: horno, grill y air fryer',
		'post_excerpt' => 'Métodos de acabado para que un confit ya cocinado quede crujiente por fuera sin resecar la carne: horno, grill y air fryer.',
	) ) );
	update_post_meta( $id, '_yoast_wpseo_title', 'Cómo conseguir piel crujiente en confit de pato | El Mercado de Origen' );
	update_post_meta( $id, 'rank_math_title', 'Cómo conseguir piel crujiente en confit de pato | El Mercado de Origen' );
}

echo "DEPTH_PATCH_OK\n";
