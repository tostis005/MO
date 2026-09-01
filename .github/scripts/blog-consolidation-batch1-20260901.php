<?php
/**
 * SEO consolidation batch 1: vegetables, olive oil and legumes.
 * Updates survivor posts, drafts redundant posts and rewrites internal links.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

function emdo_consolidation_post_by_slug( string $slug ): ?WP_Post {
	$post = get_page_by_path( $slug, OBJECT, 'post' );
	return $post instanceof WP_Post ? $post : null;
}

function emdo_consolidation_update( string $slug, string $title, string $excerpt, string $content, string $meta_description ): int {
	$post = emdo_consolidation_post_by_slug( $slug );
	if ( ! $post ) {
		throw new RuntimeException( 'Missing survivor: ' . $slug );
	}
	$result = wp_update_post(
		array(
			'ID'           => $post->ID,
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}
	update_post_meta( $post->ID, '_yoast_wpseo_metadesc', $meta_description );
	update_post_meta( $post->ID, 'rank_math_description', $meta_description );
	update_post_meta( $post->ID, '_emdo_consolidated_20260901', '1' );
	clean_post_cache( $post->ID );
	echo "UPDATED {$post->ID} {$slug}\n";
	return (int) $post->ID;
}

function emdo_consolidation_archive( string $source_slug, string $destination_slug ): void {
	$post = emdo_consolidation_post_by_slug( $source_slug );
	if ( ! $post ) {
		echo "SOURCE_ALREADY_ABSENT {$source_slug}\n";
		return;
	}
	update_post_meta( $post->ID, '_emdo_consolidated_into', $destination_slug );
	$result = wp_update_post( array( 'ID' => $post->ID, 'post_status' => 'draft' ), true );
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( $result->get_error_message() );
	}
	clean_post_cache( $post->ID );
	echo "DRAFTED {$post->ID} {$source_slug} => {$destination_slug}\n";
}

function emdo_consolidation_rewrite_links( array $redirects ): void {
	$posts = get_posts(
		array(
			'post_type'        => array( 'post', 'page' ),
			'post_status'      => array( 'publish', 'draft' ),
			'posts_per_page'   => -1,
			'suppress_filters' => false,
		)
	);
	foreach ( $posts as $post ) {
		$original = $post->post_content;
		$content  = $original;
		foreach ( $redirects as $old => $new ) {
			$old_abs = home_url( '/' . $old . '/' );
			$new_abs = home_url( '/' . $new . '/' );
			$content = str_replace( array( $old_abs, '/' . $old . '/' ), array( $new_abs, '/' . $new . '/' ), $content );
		}
		if ( $content !== $original ) {
			$result = wp_update_post( array( 'ID' => $post->ID, 'post_content' => $content ), true );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( $result->get_error_message() );
			}
			echo "LINKS_UPDATED {$post->ID}\n";
		}
	}
}

$vitamin_c = <<<'HTML'
<p><strong>Respuesta rápida:</strong> entre las hortalizas habituales en España, el <strong>pimiento rojo crudo</strong> suele ser uno de los alimentos que más vitamina C aporta por 100 gramos. También destacan el pimiento verde, el brócoli y las coles de Bruselas. Pero el ranking cambia si comparamos alimentos cocinados, porque la vitamina C es sensible al calor y puede pasar al agua de cocción.</p>

<p>Buscar “qué verduras tienen más vitamina C” parece pedir una tabla sencilla, pero una comparación útil necesita tres reglas: comparar el mismo estado del alimento, indicar que los valores son aproximados y tener en cuenta la ración real. En esta guía reunimos esas tres cosas para que la clasificación no resulte engañosa.</p>

<h2>Ranking de verduras con más vitamina C por 100 gramos</h2>
<table>
<thead><tr><th>Verdura u hortaliza, cruda</th><th>Vitamina C aproximada</th><th>Qué conviene saber</th></tr></thead>
<tbody>
<tr><td>Pimiento rojo</td><td>120–150 mg/100 g</td><td>Suele liderar claramente entre las hortalizas comunes.</td></tr>
<tr><td>Coles de Bruselas</td><td>80–100 mg/100 g</td><td>El cocinado puede reducir la cifra.</td></tr>
<tr><td>Pimiento verde</td><td>80–100 mg/100 g</td><td>La cantidad cambia con variedad y madurez.</td></tr>
<tr><td>Brócoli</td><td>80–90 mg/100 g</td><td>Destaca también por folatos y fibra.</td></tr>
<tr><td>Coliflor</td><td>45–60 mg/100 g</td><td>Una ración habitual puede aportar una cantidad relevante.</td></tr>
<tr><td>Repollo</td><td>30–40 mg/100 g</td><td>Hay diferencias entre variedades de col.</td></tr>
<tr><td>Espinaca</td><td>25–30 mg/100 g</td><td>Su fama se asocia más al folato y al hierro que a la vitamina C.</td></tr>
<tr><td>Tomate</td><td>15–25 mg/100 g</td><td>La ración suele ser mayor que la de otras hortalizas.</td></tr>
<tr><td>Calabacín</td><td>15–20 mg/100 g</td><td>Aporta menos por peso, pero encaja fácilmente en raciones grandes.</td></tr>
</tbody>
</table>
<p><small>Valores orientativos por 100 g de parte comestible cruda. Las tablas de composición pueden diferir según variedad, madurez, cultivo, conservación y método analítico.</small></p>

<h2>¿El pimiento tiene más vitamina C que la naranja?</h2>
<p>Por 100 gramos, <strong>un pimiento rojo crudo puede contener bastante más vitamina C que una naranja</strong>. Eso no convierte al pimiento en un alimento “mejor”: la comparación por peso mide densidad nutricional, no lo que una persona termina comiendo. Es fácil consumir una naranja completa, mientras que el pimiento puede aparecer como una fracción de una ensalada o un sofrito.</p>
<p>La pregunta práctica es doble: cuánto aporta el alimento por 100 g y cuánto solemos incluir realmente en una comida.</p>

<h2>Por qué aparecen cifras distintas según la tabla nutricional</h2>
<p>Es normal encontrar números diferentes para el mismo alimento. La vitamina C cambia con la variedad, el grado de maduración, el tiempo desde la cosecha, la temperatura de almacenamiento y el procesado. Además, algunas bases de datos muestran el alimento crudo y otras el alimento hervido o cocinado.</p>
<p>Por eso esta comparativa utiliza rangos y no pretende presentar una cifra universal con falsa precisión. Para comparar, lo importante es que todas las filas se refieran al mismo estado del alimento.</p>

<h2>Qué ocurre con la vitamina C al cocinar las verduras</h2>
<p>La vitamina C es hidrosoluble y relativamente sensible al calor. Dos procesos pueden reducir la cantidad que llega al plato: la degradación por temperatura y el paso al agua de cocción. La pérdida no es idéntica en todas las técnicas.</p>
<ul>
<li><strong>Hervir en mucha agua:</strong> favorece el paso de vitamina C al líquido, especialmente si después se desecha.</li>
<li><strong>Vapor:</strong> suele limitar el contacto con agua y puede ayudar a conservar mejor vitaminas hidrosolubles.</li>
<li><strong>Microondas:</strong> con poco agua y tiempos breves puede resultar eficiente desde el punto de vista de conservación.</li>
<li><strong>Salteado breve:</strong> reduce el tiempo de exposición, aunque la temperatura sea alta.</li>
<li><strong>Cocciones largas:</strong> tienden a producir mayores pérdidas.</li>
</ul>
<p>Esto no significa que las verduras cocinadas “se queden sin vitaminas”. Cocinar puede mejorar la palatabilidad, permitir comer más cantidad y modificar la disponibilidad de otros compuestos. La elección no tiene por qué ser crudo o cocinado: una dieta variada puede incluir ambas formas.</p>

<h2>Vitamina C y hierro vegetal: por qué interesa combinarlos</h2>
<p>La vitamina C puede favorecer la absorción del hierro no hemo presente en alimentos vegetales. Por eso combinaciones corrientes —lentejas con pimiento, garbanzos con tomate, espinacas con una guarnición rica en vitamina C— tienen sentido nutricional.</p>
<p>Si quieres profundizar, consulta también nuestra guía sobre <a href="/que-verduras-tienen-mas-hierro/">qué verduras tienen más hierro</a> y la comparativa de <a href="/que-verduras-tienen-mas-fibra/">verduras con más fibra</a>.</p>

<h2>¿Qué cuenta más: el dato por 100 g o la ración?</h2>
<p>Los 100 gramos son útiles para comparar en igualdad de condiciones, pero la ración define el aporte real. Un alimento con una cifra moderada puede contribuir mucho si solemos comer 200 gramos; otro con una cifra espectacular puede aportar menos si se utiliza en pequeñas cantidades.</p>
<p>Por eso, para planificar comidas, usa el ranking como orientación y no como una competición. Variar pimientos, crucíferas, tomates, verduras de hoja y productos de temporada aporta perfiles distintos.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Cuál es la verdura con más vitamina C?</h3>
<p>Entre las hortalizas de consumo habitual, el pimiento rojo crudo suele situarse en las primeras posiciones.</p>
<h3>¿El brócoli tiene vitamina C?</h3>
<p>Sí. Crudo puede aportar una cantidad elevada por 100 g; la cifra disminuye en mayor o menor medida según el cocinado.</p>
<h3>¿La vitamina C desaparece al hervir?</h3>
<p>No desaparece por completo, pero puede reducirse por calor y porque una parte pasa al agua.</p>
<h3>¿Las verduras congeladas conservan vitamina C?</h3>
<p>Pueden conservar cantidades relevantes. La congelación industrial suele realizarse poco después de la cosecha, aunque el escaldado previo y el almacenamiento producen cambios. Más información en <a href="/verduras-frescas-vs-congeladas-diferencias-nutrientes-sabor/">verduras frescas vs congeladas</a>.</p>

<h2>Fuentes y criterio</h2>
<p>Para esta guía contrastamos tablas españolas y publicaciones de la <a href="https://www.fen.org.es/" rel="nofollow">Fundación Española de la Nutrición (FEN)</a>, bases de composición de alimentos y documentación científica sobre pérdidas de vitaminas durante el cocinado. Los valores se presentan como rangos porque la composición de una hortaliza fresca no es idéntica en todas las variedades y condiciones.</p>
HTML;

$iron = <<<'HTML'
<p><strong>Respuesta rápida:</strong> entre las verduras y hortalizas habituales, <strong>espinacas y acelgas</strong> suelen aparecer entre las que contienen más hierro por 100 gramos. Sin embargo, el dato necesita contexto: el hierro de los vegetales es principalmente <strong>hierro no hemo</strong> y su absorción es más variable que la del hierro hemo presente en carne y pescado.</p>

<h2>Verduras con más hierro por 100 gramos</h2>
<table>
<thead><tr><th>Verdura u hortaliza</th><th>Hierro aproximado</th><th>Observación</th></tr></thead>
<tbody>
<tr><td>Espinaca</td><td>aprox. 2,5–4 mg/100 g</td><td>Las bases de datos difieren; contiene además compuestos que condicionan la absorción.</td></tr>
<tr><td>Acelga</td><td>aprox. 2–3 mg/100 g</td><td>Verdura de hoja con una cantidad relativamente alta.</td></tr>
<tr><td>Guisantes frescos</td><td>aprox. 1,5 mg/100 g</td><td>Botánicamente son leguminosas; frescos se usan como verdura.</td></tr>
<tr><td>Alcachofa</td><td>aprox. 1–1,5 mg/100 g</td><td>Destaca más por fibra que por hierro.</td></tr>
<tr><td>Brócoli</td><td>aprox. 1 mg/100 g</td><td>Aporta además vitamina C, interesante en la misma comida.</td></tr>
<tr><td>Judía verde</td><td>aprox. 1 mg/100 g</td><td>La cifra cambia entre crudo y cocinado.</td></tr>
<tr><td>Tomate</td><td>aprox. 0,3–0,6 mg/100 g</td><td>No destaca por hierro, pero puede acompañar fuentes más concentradas.</td></tr>
</tbody>
</table>
<p><small>Valores orientativos. No mezcles cifras de alimento crudo con hervido o escurrido: el agua cambia la concentración por 100 g.</small></p>

<h2>¿Tienen realmente mucho hierro las espinacas?</h2>
<p>Las espinacas sí contienen más hierro que muchas hortalizas, pero su fama histórica simplificó demasiado la cuestión. La propia Fundación Española de la Nutrición recuerda que <strong>la cantidad presente no equivale a la cantidad absorbida</strong>. El hierro vegetal es no hemo y su aprovechamiento depende del conjunto de la comida.</p>
<p>Además, las espinacas contienen oxalatos y otros componentes capaces de reducir la biodisponibilidad de determinados minerales. Eso no las convierte en un alimento poco interesante; simplemente impide comparar miligramos de hierro vegetal y animal como si fueran equivalentes.</p>

<h2>Hierro vegetal vs hierro de la carne</h2>
<p>El hierro hemo, presente en alimentos de origen animal, suele absorberse con mayor eficiencia y está menos condicionado por otros componentes de la dieta. El hierro no hemo de verduras, legumbres, frutos secos y cereales tiene una absorción más variable.</p>
<p>Por eso una tabla de “alimentos con más hierro” debe separar dos conceptos: <strong>cantidad total</strong> y <strong>biodisponibilidad</strong>. Puedes ampliar la comparación en <a href="/hierro-carne-vs-legumbres-diferencias/">hierro de la carne vs hierro de las legumbres</a>.</p>

<h2>Cómo mejorar la absorción del hierro de las verduras</h2>
<p>Una estrategia sencilla es combinar en la misma comida una fuente de hierro vegetal con alimentos ricos en vitamina C. La vitamina C puede favorecer la reducción y solubilidad del hierro no hemo, facilitando su aprovechamiento.</p>
<ul>
<li>Espinacas con pimiento o tomate.</li>
<li>Legumbres con pimiento rojo, tomate o una fruta rica en vitamina C de postre.</li>
<li>Brócoli junto a otras fuentes vegetales de hierro.</li>
</ul>
<p>También conviene recordar que té y café contienen polifenoles que, consumidos junto a la comida, pueden disminuir la absorción de hierro no hemo en determinadas circunstancias.</p>

<h2>¿Qué pasa con el hierro al cocinar?</h2>
<p>El hierro es un mineral y no se destruye por el calor como una vitamina termolábil. Sin embargo, parte puede pasar al agua de cocción y, sobre todo, el cambio de agua del alimento modifica la cifra expresada por 100 gramos. Una espinaca cocida puede parecer más concentrada simplemente porque ha perdido volumen y agua.</p>
<p>Para comparar bien, usa siempre el mismo estado: crudo con crudo o cocido con cocido.</p>

<h2>¿Son las verduras una fuente relevante de hierro en España?</h2>
<p>Sí contribuyen. Los datos del estudio ANIBES de la FEN sitúan al grupo de verduras y hortalizas como una de las fuentes que participan en la ingesta total de hierro de la población española, aunque cereales y carnes aportan una proporción mayor.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Qué verdura tiene más hierro?</h3>
<p>Entre las más habituales, espinaca y acelga suelen destacar, aunque la cifra exacta depende de la base de datos y el estado del alimento.</p>
<h3>¿El hierro de las espinacas se absorbe igual que el de la carne?</h3>
<p>No. Es principalmente hierro no hemo y su absorción es más variable.</p>
<h3>¿El brócoli aporta hierro?</h3>
<p>Sí, aunque no está entre las hortalizas más concentradas. Su contenido en vitamina C puede ser interesante en una comida con fuentes vegetales de hierro.</p>
<h3>¿Cocer destruye el hierro?</h3>
<p>No lo destruye, pero parte puede pasar al agua y el cambio de peso altera la concentración por 100 g.</p>

<h2>Fuentes</h2>
<ul>
<li><a href="https://www.fen.org.es/anibes/index.php/es/ingesta_alimentarias_hierro" rel="nofollow">FEN – Ingesta y fuentes alimentarias de hierro en la población española (ANIBES)</a>.</li>
<li><a href="https://www.fen.org.es/" rel="nofollow">Fundación Española de la Nutrición</a> y sus tablas/publicaciones sobre frutas y hortalizas.</li>
</ul>
HTML;

$fiber = <<<'HTML'
<p><strong>Respuesta rápida:</strong> entre las hortalizas de consumo habitual, <strong>alcachofa y guisantes frescos</strong> suelen situarse entre las que más fibra aportan por 100 gramos. También destacan las coles de Bruselas, el brócoli, las judías verdes y la zanahoria.</p>

<h2>Ranking de verduras con más fibra</h2>
<table>
<thead><tr><th>Verdura u hortaliza</th><th>Fibra aproximada</th><th>Ración orientativa</th></tr></thead>
<tbody>
<tr><td>Alcachofa</td><td>5–6 g/100 g</td><td>Una ración puede superar claramente los 100 g de parte comestible.</td></tr>
<tr><td>Guisantes frescos</td><td>aprox. 5 g/100 g</td><td>Botánicamente son leguminosas y por eso su perfil se acerca al de las legumbres.</td></tr>
<tr><td>Coles de Bruselas</td><td>3,5–4 g/100 g</td><td>Una ración habitual puede aportar varios gramos.</td></tr>
<tr><td>Brócoli</td><td>aprox. 3 g/100 g</td><td>Combina fibra con vitamina C y folatos.</td></tr>
<tr><td>Judías verdes</td><td>2,5–3 g/100 g</td><td>El valor varía según cocinado y escurrido.</td></tr>
<tr><td>Zanahoria</td><td>2,5–3 g/100 g</td><td>Aporta fibra junto con carotenoides.</td></tr>
<tr><td>Espinaca</td><td>2–2,5 g/100 g</td><td>La ración y el enorme cambio de volumen al cocinar importan mucho.</td></tr>
<tr><td>Coliflor</td><td>aprox. 2 g/100 g</td><td>Puede sumar una cantidad apreciable en raciones grandes.</td></tr>
</tbody>
</table>

<h2>Por qué la alcachofa destaca</h2>
<p>La alcachofa contiene una proporción relevante de componentes no digeribles. Entre ellos aparecen distintos tipos de fibra y fructanos como la inulina. Esto ayuda a explicar que, por 100 gramos, pueda superar a hortalizas mucho más acuosas.</p>

<h2>Guisantes: ¿verdura o legumbre?</h2>
<p>Botánicamente el guisante pertenece a las leguminosas. En cocina, el guisante fresco suele agruparse con las verduras, mientras que el guisante seco se comporta más como una legumbre seca. Esta peculiaridad explica su contenido relativamente alto de fibra y proteína frente a muchas hortalizas.</p>

<h2>¿La cocción destruye la fibra?</h2>
<p>La fibra es mucho más estable frente al calor que la vitamina C. Cocinar ablanda las paredes celulares y modifica la textura, pero no hace desaparecer la fibra. Lo que sí puede reducirla es retirar físicamente partes ricas en fibra, pelar en exceso o colar una preparación.</p>
<p>Una crema de verduras triturada <strong>con toda su pulpa</strong> conserva gran parte de la fibra. Un zumo o caldo filtrado no, porque se elimina buena parte de los sólidos.</p>

<h2>Fibra soluble e insoluble: no toda la fibra es igual</h2>
<p>El término “fibra” engloba pectinas, celulosas, hemicelulosas, fructanos y otros componentes con propiedades distintas. Por eso no tiene sentido perseguir una sola verdura como “la mejor”. Variar entre alcachofa, crucíferas, zanahoria, verduras de hoja, tomate y otras hortalizas ofrece perfiles complementarios.</p>

<h2>Por 100 gramos o por ración: qué comparación es más útil</h2>
<p>La tabla por 100 g sirve para comparar densidad. La ración sirve para entender el aporte real. Una ensalada grande, una guarnición de brócoli o un plato de alcachofas pueden aportar cantidades diferentes aunque la clasificación por 100 gramos diga otra cosa.</p>
<p>Además, las <a href="/que-legumbre-tiene-mas-fibra-comparativa/">legumbres secas suelen aportar mucha más fibra</a> que la mayoría de las verduras cuando se comparan en condiciones equivalentes.</p>

<h2>¿Pelar las verduras reduce la fibra?</h2>
<p>Puede reducirla. En muchos vegetales, la piel y las capas externas contienen una proporción apreciable de fibra. No siempre es posible o deseable consumirlas —depende del alimento, el estado y la receta—, pero retirar sistemáticamente todas las partes externas reduce el aprovechamiento.</p>

<h2>¿Las verduras congeladas mantienen la fibra?</h2>
<p>En general, la congelación afecta poco a la cantidad total de fibra. Puede cambiar la textura por la formación de cristales de hielo y existe un escaldado previo en muchos productos congelados, pero la fibra estructural permanece en gran medida. Consulta la guía de <a href="/verduras-frescas-vs-congeladas-diferencias-nutrientes-sabor/">verduras frescas vs congeladas</a>.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Qué verdura tiene más fibra?</h3>
<p>Entre las de consumo habitual, la alcachofa suele destacar, junto con guisantes frescos, coles de Bruselas y brócoli.</p>
<h3>¿La zanahoria tiene fibra?</h3>
<p>Sí. Su aporte es moderado y puede sumar cantidades relevantes cuando forma parte habitual de la dieta.</p>
<h3>¿Triturar elimina la fibra?</h3>
<p>No si conservas toda la pulpa. Triturar cambia la estructura física, pero no equivale a colar.</p>
<h3>¿La piel aporta fibra?</h3>
<p>A menudo sí, aunque la proporción depende de cada alimento.</p>

<h2>Fuentes y criterio</h2>
<p>Valores orientativos contrastados con publicaciones de la <a href="https://www.fen.org.es/" rel="nofollow">Fundación Española de la Nutrición</a> y tablas de composición de alimentos. Las cifras pueden variar por variedad, estado de madurez y tratamiento culinario.</p>
HTML;

$tomato = <<<'HTML'
<p>Elegir un buen tomate no depende solo del color. <strong>Variedad, grado de maduración, textura y uso culinario</strong> importan tanto como el aspecto exterior. Un tomate excelente para ensalada puede ser mediocre para salsa, y uno muy maduro que ya no apetece cortar en rodajas puede ser perfecto para cocinar.</p>

<p>Esta guía reúne en una sola página cómo elegir tomates, cómo reconocer su madurez, dónde conservarlos, qué tipos encajan mejor con cada receta y qué señales indican deterioro real.</p>

<h2>Cómo elegir un buen tomate</h2>
<ul>
<li><strong>Peso:</strong> debería sentirse lleno y relativamente pesado para su tamaño.</li>
<li><strong>Piel:</strong> busca una superficie íntegra, sin golpes húmedos, moho ni zonas hundidas.</li>
<li><strong>Firmeza:</strong> depende del uso. Para ensalada conviene firme pero no duro; para salsa puede estar más maduro.</li>
<li><strong>Aroma:</strong> en variedades aromáticas, la zona del pedúnculo puede ofrecer pistas de madurez.</li>
<li><strong>Color:</strong> no es una regla universal. Hay tomates verdes, amarillos, negros, rosados y bicolores que están maduros con colores muy distintos.</li>
</ul>

<h2>Cómo saber si un tomate está maduro</h2>
<p>La madurez se reconoce combinando color propio de la variedad, ligera cesión a la presión y aroma. Un tomate totalmente duro suele necesitar más tiempo; uno muy blando puede estar pasado o simplemente ser una variedad de pulpa delicada.</p>
<p>No aprietes repetidamente la fruta: las pequeñas lesiones mecánicas aceleran su deterioro. Es mejor sostenerla y ejercer una presión mínima.</p>

<h2>Tipos de tomate y para qué sirve cada uno</h2>
<table>
<thead><tr><th>Tipo o perfil</th><th>Características habituales</th><th>Usos donde suele funcionar bien</th></tr></thead>
<tbody>
<tr><td>Tomate de ensalada</td><td>Firme, jugoso, fácil de cortar</td><td>Ensaladas, bocadillos, guarniciones</td></tr>
<tr><td>Tomate rosa</td><td>Carnoso, piel fina, sabor suave e intenso según variedad</td><td>Tomar crudo, ensaladas sencillas</td></tr>
<tr><td>Tomate tipo pera</td><td>Más pulpa y menos agua relativa</td><td>Salsas, sofritos, conservas</td></tr>
<tr><td>Cherry</td><td>Pequeño, normalmente dulce y concentrado</td><td>Ensaladas, horno, guarniciones</td></tr>
<tr><td>Tomate de colgar</td><td>Seleccionado para buena conservación</td><td>Pan con tomate, uso prolongado en despensa según variedad</td></tr>
<tr><td>Variedades oscuras o moradas</td><td>Perfil aromático particular y color propio</td><td>Consumo en crudo y platos donde el tomate sea protagonista</td></tr>
</tbody>
</table>
<p>Los nombres comerciales no siempre identifican una única variedad botánica. Lo útil al comprar es entender qué textura, cantidad de agua y nivel de madurez necesita la receta.</p>

<h2>¿Nevera o fuera de la nevera?</h2>
<p>Un tomate que todavía necesita madurar suele evolucionar mejor a temperatura ambiente, protegido del sol directo y del calor excesivo. Cuando ya está muy maduro y no se va a consumir pronto, <strong>la nevera puede frenar el deterioro</strong>.</p>
<p>La refrigeración prolongada puede modificar aroma y textura, especialmente en tomates sensibles. Si has refrigerado un tomate maduro, sacarlo con antelación antes de comerlo puede mejorar la percepción aromática.</p>

<h2>Cómo guardar tomates para que duren más</h2>
<ol>
<li>Separa los que estén golpeados o muy maduros de los más firmes.</li>
<li>No los laves hasta que vayas a utilizarlos; la humedad superficial sostenida favorece deterioro.</li>
<li>Evita apilarlos bajo productos pesados.</li>
<li>Usa primero los que tengan pequeñas grietas superficiales o estén más maduros.</li>
<li>Si aparece moho, podredumbre húmeda u olor claramente fermentado, descarta el tomate afectado.</li>
</ol>

<h2>Tomates rajados: ¿se pueden comer?</h2>
<p>Una grieta puede aparecer por cambios rápidos en la disponibilidad de agua durante el crecimiento. Una fisura seca y reciente no equivale automáticamente a podredumbre, pero la zona rota facilita la entrada de microorganismos y acelera el deterioro. Consulta nuestra guía específica sobre <a href="/tomates-rajados-por-que-se-agrietan-cuando-se-pueden-comer/">tomates rajados</a>.</p>

<h2>Por qué un tomate puede quedar harinoso</h2>
<p>La textura harinosa se relaciona con la estructura de la pulpa, el grado de maduración, la variedad y las condiciones de almacenamiento. No suele ser por sí sola una señal de riesgo, pero sí de peor calidad sensorial. Lo explicamos con detalle en <a href="/tomate-harinoso-por-que-ocurre-textura-como-evitarlo/">por qué un tomate se vuelve harinoso</a>.</p>

<h2>Qué hacer con tomates demasiado maduros</h2>
<p>Si siguen en buen estado pero han perdido firmeza, aprovéchalos en salsa, sofrito, gazpacho, salmorejo, tomate rallado o asado. Un producto puede dejar de ser ideal para ensalada sin estar estropeado.</p>

<h2>Cómo cortar el desperdicio al comprar tomate</h2>
<p>Compra distintos grados de madurez cuando no vayas a consumir todos el mismo día: algunos listos para comer y otros algo más firmes. Es más eficaz que comprar una bandeja completa en el mismo punto de maduración.</p>
<p>También ayuda elegir el tipo según el uso. Para una salsa, pagar por un tomate extremadamente firme y bonito puede no aportar ninguna ventaja; para una ensalada donde será protagonista, la textura sí es decisiva.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Se deben guardar los tomates en la nevera?</h3>
<p>Los que aún necesitan madurar, mejor fuera en condiciones moderadas. Los muy maduros pueden refrigerarse para ganar tiempo antes de consumirlos.</p>
<h3>¿El tomate verde siempre está inmaduro?</h3>
<p>No. Existen variedades cuyo color maduro incluye tonos verdes. Hay que conocer la variedad y valorar firmeza y aroma.</p>
<h3>¿Un tomate blando está malo?</h3>
<p>No necesariamente. Puede estar simplemente muy maduro. Moho, olor desagradable, líquido anormal o podredumbre húmeda son señales más claras de deterioro.</p>
<h3>¿Qué tomate es mejor para salsa?</h3>
<p>Los tomates carnosos, con buena proporción de pulpa y sabor maduro, suelen funcionar especialmente bien; los tipo pera son una opción clásica.</p>

<h2>Una regla sencilla para elegir mejor</h2>
<p>No busques “el mejor tomate” en abstracto. Busca <strong>el tomate adecuado para lo que vas a cocinar y para cuándo vas a comerlo</strong>. Esa decisión mejora sabor, textura y aprovechamiento mucho más que fijarse en un único rasgo exterior.</p>
HTML;

$seasonal = <<<'HTML'
<p>Comprar verduras de temporada puede ayudar a encontrar productos en un buen momento de disponibilidad, variedad y precio, pero hablar de “temporada” en España necesita matices. <strong>El clima, la zona productora, el cultivo protegido, la variedad y la conservación amplían muchas campañas</strong>. Por eso un calendario debe leerse como guía, no como una frontera exacta.</p>

<p>Esta página reúne un calendario práctico por meses y explica cómo interpretarlo sin confundir “se vende todo el año” con “está en su campaña más característica”.</p>

<h2>Calendario de verduras y hortalizas de temporada en España</h2>
<table>
<thead><tr><th>Mes</th><th>Hortalizas especialmente asociadas a la época</th></tr></thead>
<tbody>
<tr><td>Enero</td><td>Acelga, alcachofa, apio, brócoli, coliflor, escarola, espinaca, puerro, repollo, cardo</td></tr>
<tr><td>Febrero</td><td>Alcachofa, brócoli, coliflor, espinaca, puerro, guisante, haba, escarola</td></tr>
<tr><td>Marzo</td><td>Alcachofa, espárrago verde, guisante, haba, espinaca, brócoli, acelga</td></tr>
<tr><td>Abril</td><td>Espárrago, guisante, haba, alcachofa, acelga, lechuga, espinaca</td></tr>
<tr><td>Mayo</td><td>Espárrago, judía verde, lechuga, pepino, calabacín, cebolla tierna</td></tr>
<tr><td>Junio</td><td>Tomate, pepino, calabacín, judía verde, pimiento, berenjena, lechuga</td></tr>
<tr><td>Julio</td><td>Tomate, pimiento, berenjena, pepino, calabacín, judía verde, cebolla</td></tr>
<tr><td>Agosto</td><td>Tomate, pimiento, berenjena, pepino, calabacín, judía verde, calabaza temprana</td></tr>
<tr><td>Septiembre</td><td>Tomate, pimiento, berenjena, calabaza, judía verde, puerro, primeras crucíferas según zona</td></tr>
<tr><td>Octubre</td><td>Calabaza, alcachofa, brócoli, coliflor, acelga, espinaca, puerro, escarola</td></tr>
<tr><td>Noviembre</td><td>Alcachofa, brócoli, coliflor, repollo, espinaca, acelga, puerro, cardo</td></tr>
<tr><td>Diciembre</td><td>Alcachofa, cardo, brócoli, coliflor, repollo, escarola, espinaca, puerro</td></tr>
</tbody>
</table>
<p><small>Calendario orientativo para España. La disponibilidad real cambia por región, variedad, campaña y sistemas de cultivo.</small></p>

<h2>Invierno: hojas, crucíferas, alcachofa y puerro</h2>
<p>Los meses fríos están especialmente asociados a verduras de hoja, coles, brócoli, coliflor, alcachofa, puerro, cardo y escarola. Son productos que encajan en sopas, cremas, asados y guisos, aunque muchos se comercializan durante periodos más largos gracias a distintas zonas productoras.</p>

<h2>Primavera: transición y productos de campaña corta</h2>
<p>La primavera es especialmente interesante para espárragos, guisantes y habas, además de la continuidad de algunas verduras de invierno. Conforme suben las temperaturas aparecen con más fuerza pepinos, calabacines, judías verdes y otras hortalizas de verano.</p>

<h2>Verano: tomate, pimiento, berenjena y calabacín</h2>
<p>El verano concentra algunas de las hortalizas más reconocibles de la cocina mediterránea: tomate, pimiento, berenjena, pepino y calabacín. Es una buena época para preparaciones crudas, plancha, parrilla, gazpachos y asados.</p>

<h2>Otoño: calabaza y regreso de las verduras de frío</h2>
<p>A medida que avanza el otoño ganan protagonismo calabaza, puerro, alcachofa y crucíferas. Es una etapa de solapamiento: todavía pueden encontrarse buenas hortalizas estivales de determinadas zonas mientras comienzan campañas más propias de los meses fríos.</p>

<h2>Por qué encontramos casi todas las verduras durante todo el año</h2>
<p>España reúne climas y zonas productoras muy diferentes. A ello se suman invernaderos, variedades con calendarios distintos, conservación y comercio entre regiones. Por eso “temporada” no significa necesariamente “único momento del año en que existe ese producto”.</p>
<p>El calendario oficial del Ministerio de Agricultura muestra precisamente campañas amplias para numerosas hortalizas. Conviene utilizarlo como orientación y mirar también el origen concreto del producto.</p>

<h2>Cómo comprar por temporada sin complicarse</h2>
<ul>
<li>Mira <strong>origen y variedad</strong>, no solo el nombre del producto.</li>
<li>Prioriza las verduras que presentan buen aspecto y precio en ese momento.</li>
<li>No descartes un producto por estar fuera de su “mes típico”: puede proceder de una zona con otra campaña.</li>
<li>Planifica menús flexibles: sustituir una hortaliza por otra de temporada suele ser más fácil que forzar una receta.</li>
<li>Aprovecha productos muy maduros en cremas, salsas o asados para reducir desperdicio.</li>
</ul>

<h2>¿Temporada significa siempre producto local?</h2>
<p>No. Un alimento puede estar en temporada en otra región o país y haber viajado hasta el punto de venta. Si el origen importa en tu decisión, compruébalo en el etiquetado o en la ficha del producto.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Qué verduras son de temporada en invierno?</h3>
<p>Alcachofa, brócoli, coliflor, repollo, espinaca, acelga, puerro, escarola y cardo son ejemplos habituales.</p>
<h3>¿Qué verduras destacan en verano?</h3>
<p>Tomate, pimiento, berenjena, pepino, calabacín y judía verde tienen una fuerte asociación con los meses cálidos.</p>
<h3>¿Por qué hay tomate todo el año?</h3>
<p>Por la combinación de distintas zonas productoras, cultivo protegido, variedades y cadenas de suministro. Eso no impide que algunas variedades tengan momentos de especial calidad o abundancia.</p>

<h2>Fuente principal</h2>
<p>Como referencia utilizamos el <a href="https://www.mapa.gob.es/es/alimentacion/temas/desperdicio/11calendario_verduras_completo_tcm30-623767.pdf" rel="nofollow">Calendario de hortalizas de temporada del Ministerio de Agricultura, Pesca y Alimentación</a>, interpretado junto con la realidad de campañas regionales y cultivo protegido.</p>
HTML;

$filtered = <<<'HTML'
<p><strong>Respuesta rápida:</strong> un AOVE sin filtrar conserva pequeñas gotas de agua y partículas sólidas en suspensión que le dan un aspecto turbio. El filtrado elimina buena parte de esos restos. <strong>No convierte un aceite mediocre en uno bueno ni un aceite bueno en uno peor</strong>; cambia sobre todo su aspecto y su estabilidad durante el almacenamiento.</p>

<h2>AOVE filtrado vs sin filtrar: diferencias principales</h2>
<table>
<thead><tr><th>Aspecto</th><th>Filtrado</th><th>Sin filtrar</th></tr></thead>
<tbody>
<tr><td>Aspecto</td><td>Más limpio y brillante</td><td>Turbio por agua y partículas en suspensión</td></tr>
<tr><td>Sedimentos</td><td>Mucho menores</td><td>Pueden depositarse con el tiempo</td></tr>
<tr><td>Estabilidad</td><td>Generalmente más favorable para almacenamiento</td><td>Exige más cuidado y suele interesar consumirlo antes</td></tr>
<tr><td>Sabor inicial</td><td>Perfil limpio; depende de aceituna y elaboración</td><td>Puede percibirse más “recién elaborado”, pero no siempre más intenso</td></tr>
<tr><td>Calidad legal</td><td>Puede ser virgen extra</td><td>También puede ser virgen extra</td></tr>
<tr><td>Uso</td><td>Muy versátil y adecuado para guardar</td><td>Interesante para consumo relativamente próximo si gusta ese estilo</td></tr>
</tbody>
</table>

<h2>Qué significa exactamente “sin filtrar”</h2>
<p>Después de extraer el aceite de la aceituna quedan cantidades pequeñas de agua de vegetación y sólidos finos. Un aceite recién elaborado puede presentar turbidez. El filtrado utiliza sistemas físicos para retirar buena parte de esa humedad y partículas; no es un refinado químico.</p>
<p>Un AOVE filtrado sigue siendo AOVE si cumple los requisitos químicos y sensoriales de la categoría. Y un aceite sin filtrar no es automáticamente virgen extra por el hecho de estar turbio.</p>

<h2>¿El AOVE sin filtrar es más natural?</h2>
<p>“Más natural” no es una categoría técnica útil para comparar calidad. Ambos proceden de extracción mecánica. El filtrado es una operación física destinada a mejorar limpieza y estabilidad. Lo importante es la calidad de la aceituna, la rapidez de elaboración, el control del proceso, el almacenamiento y el estado sensorial y químico final.</p>

<h2>¿Tiene más polifenoles un aceite sin filtrar?</h2>
<p>No se puede responder con un sí universal. El contenido fenólico depende mucho más de variedad, madurez, cultivo, extracción y almacenamiento. El filtrado puede modificar parte de los compuestos minoritarios, pero dos aceites de lotes diferentes no pueden compararse únicamente por estar filtrados o no.</p>
<p>La turbidez tampoco es una medida de polifenoles. Un aceite muy turbio no tiene por qué ser más amargo, más picante ni más rico en antioxidantes.</p>

<h2>Por qué el aceite sin filtrar suele tener una vida comercial más delicada</h2>
<p>El agua y los sólidos residuales pueden favorecer reacciones y fermentaciones en el sedimento durante el almacenamiento. Por eso, para guardar aceite durante meses, retirar esos restos suele mejorar la estabilidad.</p>
<p>El Consejo Oleícola Internacional incluye entre sus buenas prácticas de almacenamiento la eliminación de sedimentos en aceites vírgenes comestibles. La idea es sencilla: proteger el aceite de aquello que acelera su deterioro —oxígeno, luz, calor, humedad y residuos—.</p>

<h2>¿Hay que decantar un AOVE sin filtrar?</h2>
<p>Si se forma un sedimento apreciable, mantener el aceite mucho tiempo en contacto con él no es lo ideal. En bodega, el trasiego y el filtrado se utilizan precisamente para controlar estos residuos. En casa, lo más práctico es comprar un formato que puedas consumir en un plazo razonable y mantenerlo bien cerrado, fresco y oscuro.</p>

<h2>Cómo conservar cada tipo</h2>
<ul>
<li>Guarda la botella lejos de luz directa y fuentes de calor.</li>
<li>Cierra bien el envase después de cada uso para limitar el oxígeno.</li>
<li>No almacenes grandes formatos abiertos durante periodos excesivos si consumes poco.</li>
<li>En un AOVE sin filtrar, presta especial atención a sedimentos, olores anómalos y evolución del sabor.</li>
</ul>
<p>Consulta también nuestra guía completa sobre <a href="/como-conservar-aove-correctamente/">cómo conservar AOVE</a>.</p>

<h2>¿Cuál elegir?</h2>
<p><strong>Elige filtrado</strong> si quieres un aceite estable, limpio, fácil de guardar y de uso general. <strong>Elige sin filtrar</strong> si buscas probar un aceite muy reciente con su aspecto característico y vas a consumirlo relativamente pronto. En ambos casos, la prioridad debe ser que el aceite sea de buena calidad y esté bien conservado.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Un AOVE turbio es de mejor calidad?</h3>
<p>No. La turbidez indica partículas y agua en suspensión, no una categoría superior.</p>
<h3>¿Filtrar elimina el ácido oleico?</h3>
<p>No de forma relevante. El ácido oleico forma parte de la fracción grasa mayoritaria del aceite; el filtrado actúa principalmente sobre agua y partículas.</p>
<h3>¿El sin filtrar sabe más fuerte?</h3>
<p>Puede percibirse distinto cuando está recién elaborado, pero intensidad, amargor y picor dependen de muchos factores. No es una regla.</p>
<h3>¿Cuál dura más?</h3>
<p>En condiciones comparables, el filtrado suele ofrecer una estabilidad de almacenamiento más predecible.</p>

<h2>Fuentes</h2>
<ul>
<li><a href="https://www.internationaloliveoil.org/que-hacemos/unidad-de-quimica-y-normalizacion/normas-coi-metodos-y-guias/?lang=es" rel="nofollow">Consejo Oleícola Internacional – normas y guías de almacenamiento</a>.</li>
<li>Literatura científica sobre humedad, sólidos en suspensión, compuestos fenólicos y estabilidad de aceites vírgenes.</li>
</ul>
HTML;

$heating = <<<'HTML'
<p><strong>Respuesta corta:</strong> sí, calentar AOVE cambia parte de su composición, pero <strong>no “pierde todas sus propiedades” ni deja de ser automáticamente adecuado para cocinar</strong>. Los aromas y algunos compuestos fenólicos son sensibles al calor; el ácido oleico, que constituye gran parte de su fracción grasa, es bastante más estable. Temperatura, tiempo y reutilización determinan cuánto cambia.</p>

<h2>Qué componentes del AOVE cambian al calentarlo</h2>
<table>
<thead><tr><th>Componente</th><th>Qué suele ocurrir con el calor</th></tr></thead>
<tbody>
<tr><td>Aromas volátiles</td><td>Disminuyen; por eso un AOVE pierde parte de su frutado al cocinar.</td></tr>
<tr><td>Polifenoles</td><td>Muchos se reducen o transforman, con diferencias entre compuestos.</td></tr>
<tr><td>Vitamina E / tocoferoles</td><td>Puede disminuir con calentamientos intensos o repetidos.</td></tr>
<tr><td>Ácido oleico</td><td>Es relativamente estable y sigue siendo la grasa predominante.</td></tr>
<tr><td>Productos de oxidación</td><td>Aumentan a medida que crecen tiempo, temperatura, aire y reutilizaciones.</td></tr>
</tbody>
</table>

<h2>Temperatura y tiempo: la combinación que importa</h2>
<p>Un salteado de pocos minutos no es comparable con mantener aceite a alta temperatura durante horas. Los estudios de calentamiento muestran una relación clara entre intensidad térmica y pérdida de determinados compuestos fenólicos.</p>
<p>En un estudio que simuló salteado doméstico con AOVE, el contenido fenólico disminuyó más a 170 ºC que a 120 ºC. Otros trabajos con calentamientos prolongados a 180 ºC también muestran degradación de tocoferoles y polifenoles. Eso respalda una conclusión práctica: <strong>cuanto menos tiempo innecesario pase el aceite a alta temperatura, mejor conservará su perfil inicial</strong>.</p>

<h2>¿Se puede freír con AOVE?</h2>
<p>Sí. El aceite de oliva presenta una fracción elevada de ácido oleico monoinsaturado y puede utilizarse para fritura. El problema no es que sea “virgen extra”, sino someter cualquier aceite a temperaturas excesivas, mantenerlo demasiado tiempo, acumular restos quemados o reutilizarlo indefinidamente.</p>
<p>En una fritura doméstica suele interesar trabajar aproximadamente en el entorno de 160–180 ºC según el alimento y la técnica, evitando que el aceite humee de forma sostenida. Más información en <a href="/se-puede-freir-aceite-oliva-virgen-extra-temperatura-reutilizacion-cual-elegir/">nuestra guía para freír con AOVE</a>.</p>

<h2>¿El punto de humo decide si un aceite es bueno para cocinar?</h2>
<p>No por sí solo. El punto de humo es una propiedad útil, pero la estabilidad real depende también del perfil de ácidos grasos, antioxidantes, calidad inicial, temperatura, tiempo y presencia de restos de alimento. Reducir toda la decisión a un único número de punto de humo es demasiado simplista.</p>

<h2>Sofrito, horno, plancha y air fryer: no son lo mismo</h2>
<h3>Sofrito</h3>
<p>La presencia de agua de cebolla, tomate u otras verduras condiciona la temperatura durante parte del proceso. No equivale a calentar una sartén vacía con aceite durante mucho tiempo.</p>
<h3>Plancha o sartén</h3>
<p>Usa solo el aceite necesario y añade el alimento cuando la superficie esté lista. Evita dejar el AOVE solo al fuego máximo.</p>
<h3>Horno</h3>
<p>La temperatura del aire puede ser alta, pero el aceite distribuido sobre un alimento no necesariamente alcanza inmediatamente esa misma temperatura.</p>
<h3>Air fryer</h3>
<p>Una capa fina de AOVE puede utilizarse para dorar y aportar sabor. Consulta <a href="/se-puede-usar-aove-air-fryer-temperatura-cantidad-como-aplicarlo/">cómo usar AOVE en air fryer</a>.</p>

<h2>¿Se pierden los polifenoles?</h2>
<p>Parte sí. No todos los polifenoles reaccionan igual y no desaparecen de golpe. Investigaciones sobre calentamiento doméstico y simulado han observado reducciones significativas en determinados compuestos, especialmente a temperaturas elevadas. También se ha visto que puede permanecer una cantidad relevante de actividad antioxidante y de ciertos fenoles después del tratamiento.</p>
<p>Por eso las dos frases extremas —“calentarlo no cambia nada” y “al calentarlo pierde todo”— son incorrectas.</p>

<h2>¿Merece la pena usar un buen AOVE para cocinar?</h2>
<p>Sí, aunque puede tener sentido reservar los aceites más aromáticos para terminaciones en crudo si quieres apreciar toda su complejidad. Un AOVE equilibrado funciona en sofritos, guisos, horno y fritura; un aceite de perfil muy intenso puede lucir especialmente bien sobre el plato terminado.</p>
<p>La decisión también es económica: no necesitas utilizar tu botella más especial para una cocción donde sus matices volátiles van a perderse.</p>

<h2>Reutilizar aceite: una vez no es lo mismo que muchas</h2>
<p>Cada ciclo de calentamiento, enfriamiento y contacto con aire favorece la degradación. Restos de pan rallado, harina o alimento aceleran el deterioro. Si reutilizas aceite de fritura:</p>
<ul>
<li>filtra los restos cuando se haya enfriado;</li>
<li>guárdalo protegido de luz y aire;</li>
<li>no mezcles aceite muy degradado con aceite nuevo para “recuperarlo”;</li>
<li>descártalo si presenta olor rancio o extraño, espuma persistente, viscosidad anormal, oscurecimiento acusado o humo prematuro.</li>
</ul>

<h2>Cómo minimizar las pérdidas al cocinar</h2>
<ol>
<li>No calientes el aceite vacío más tiempo del necesario.</li>
<li>Evita el humo sostenido.</li>
<li>Utiliza la temperatura adecuada para la técnica, no siempre el fuego máximo.</li>
<li>En platos largos, puedes reservar una parte del AOVE para añadir al final.</li>
<li>Conserva la botella lejos de luz, calor y aire entre usos.</li>
</ol>

<h2>Preguntas frecuentes</h2>
<h3>¿El AOVE se vuelve “malo” al calentarlo?</h3>
<p>No. Se degrada progresivamente como cualquier grasa sometida a calor, pero no existe un cambio instantáneo que lo convierta en un aceite inadecuado.</p>
<h3>¿Pierde vitamina E?</h3>
<p>Puede perder parte, especialmente con tratamientos intensos y repetidos.</p>
<h3>¿Es mejor tomarlo siempre crudo?</h3>
<p>En crudo conserva mejor aromas y compuestos sensibles, pero también puede emplearse correctamente para cocinar.</p>
<h3>¿Se puede cocinar a 180 ºC con AOVE?</h3>
<p>Puede utilizarse a temperaturas de fritura, controlando tiempo, estado del aceite y evitando sobrecalentamientos innecesarios.</p>

<h2>Fuentes científicas y técnicas</h2>
<ul>
<li><a href="https://pubmed.ncbi.nlm.nih.gov/31963124/" rel="nofollow">Domestic Sautéing with EVOO: Change in the Phenolic Profile</a>.</li>
<li><a href="https://pubmed.ncbi.nlm.nih.gov/12358466/" rel="nofollow">Influence of thermal treatments simulating cooking processes on the polyphenol content in virgin olive oil</a>.</li>
<li><a href="https://pubmed.ncbi.nlm.nih.gov/17935291/" rel="nofollow">How heating affects extra virgin olive oil quality indexes and chemical composition</a>.</li>
<li><a href="https://www.internationaloliveoil.org/olive-world/olive-oil/" rel="nofollow">Consejo Oleícola Internacional – aceite de oliva y gastronomía</a>.</li>
</ul>
HTML;

$legumes = <<<'HTML'
<p>Cocinar bien legumbres secas no exige trucos misteriosos, pero sí entender cuatro variables: <strong>tipo y edad de la legumbre, remojo, calidad del agua y temperatura de cocción</strong>. Cuando alguna falla, aparecen los problemas clásicos: garbanzos duros, alubias que se rompen, pieles desprendidas o tiempos interminables.</p>

<p>Esta guía reúne en una sola página el proceso completo para garbanzos, lentejas y alubias: cuánto remojar, tiempos de cocción, cuándo salar, qué hacer con el bicarbonato y cómo corregir errores.</p>

<h2>Tabla rápida: remojo y cocción de las principales legumbres</h2>
<table>
<thead><tr><th>Legumbre</th><th>Remojo orientativo</th><th>Olla tradicional</th><th>Olla rápida</th></tr></thead>
<tbody>
<tr><td>Garbanzos</td><td>8–12 horas</td><td>aprox. 1,5–2,5 h</td><td>aprox. 20–40 min</td></tr>
<tr><td>Alubias</td><td>8–12 horas</td><td>aprox. 1–2 h</td><td>aprox. 15–30 min</td></tr>
<tr><td>Lenteja pardina</td><td>Opcional</td><td>aprox. 25–45 min</td><td>aprox. 8–15 min</td></tr>
<tr><td>Lenteja castellana</td><td>0–2 horas, opcional</td><td>aprox. 35–60 min</td><td>aprox. 10–18 min</td></tr>
</tbody>
</table>
<p><small>Son tiempos orientativos: variedad, edad, agua, cantidad y olla cambian mucho el resultado. En olla rápida cuenta el tiempo desde que alcanza presión y respeta siempre las instrucciones del fabricante.</small></p>

<h2>Qué legumbres necesitan remojo</h2>
<h3>Garbanzos</h3>
<p>El remojo de unas 8–12 horas es la referencia doméstica más útil. Hidrata el interior, acorta la cocción y ayuda a que la textura sea más uniforme. Consulta también <a href="/garbanzos-tiempo-remojo-cuanto-tardan-cocerse/">la guía específica de remojo y cocción de garbanzos</a>.</p>
<h3>Alubias</h3>
<p>La mayoría de las alubias secas agradecen un remojo similar. Algunas variedades pequeñas pueden cocinarse sin él, pero necesitarán más tiempo y el resultado puede ser menos uniforme.</p>
<h3>Lentejas</h3>
<p>Muchas lentejas, especialmente las pequeñas, <strong>no necesitan remojo obligatorio</strong>. Puede utilizarse para acortar tiempos o conseguir una hidratación más uniforme. Más detalle en <a href="/hay-que-poner-lentejas-en-remojo-cuanto-tiempo/">si hay que remojar las lentejas</a>.</p>

<h2>¿Agua fría o caliente para el remojo?</h2>
<p>Para la mayoría de legumbres basta agua a temperatura ambiente o fresca. En garbanzos existe una tradición culinaria de utilizar agua templada, pero lo determinante es que haya suficiente agua y tiempo para que la semilla se hidrate por completo.</p>
<p>Durante el remojo las legumbres aumentan considerablemente de volumen. Utiliza un recipiente amplio y al menos tres veces su volumen de agua para evitar que queden expuestas.</p>

<h2>¿Hay que tirar el agua de remojo?</h2>
<p>Para una guía general, la opción más sencilla y consistente es <strong>escurrir y cocinar con agua limpia</strong>. Durante el remojo pasan al agua parte de oligosacáridos y otros compuestos solubles. Si quieres profundizar en las ventajas e inconvenientes, consulta <a href="/hay-que-tirar-agua-remojo-legumbres-se-puede-aprovechar/">si conviene aprovechar el agua de remojo</a>.</p>

<h2>Cómo cocinar garbanzos sin que queden duros</h2>
<ol>
<li>Remójalos hasta que estén hidratados.</li>
<li>Escúrrelos.</li>
<li>Empieza la cocción con suficiente agua y evita grandes interrupciones de temperatura.</li>
<li>Mantén un hervor controlado, no violentamente turbulento.</li>
<li>Prueba varios granos antes de dar por terminada la cocción.</li>
</ol>
<p>Los garbanzos viejos pueden tardar mucho más. Si tras un tiempo razonable siguen duros, no siempre es culpa de la receta.</p>

<h2>Cómo cocinar alubias sin que se rompan</h2>
<p>Las alubias son sensibles al hervor excesivamente fuerte. Una cocción suave ayuda a conservar la piel. No remuevas constantemente con cuchara: mover la olla con suavidad reduce roturas.</p>
<p>Si pierden la piel o quedan duras, revisa <a href="/por-que-legumbres-quedan-duras-se-rompen-pierden-piel/">las causas más habituales de legumbres duras, rotas o con piel desprendida</a>.</p>

<h2>Cómo cocinar lentejas</h2>
<p>Las lentejas son más rápidas porque su tamaño y estructura permiten una hidratación más veloz. Empieza a comprobar la textura antes de lo que harías con garbanzos o alubias. La lenteja roja pelada necesita todavía menos tiempo y tiende a deshacerse, algo útil para cremas y curris.</p>

<h2>Cuándo echar la sal</h2>
<p>La idea de que la sal “siempre endurece” las legumbres es demasiado simple. La sal puede utilizarse durante la cocción; el efecto depende de concentración, variedad y otros componentes del agua. Lo que sí puede retrasar claramente el ablandamiento son medios ácidos.</p>
<p>Si quieres una explicación específica, consulta <a href="/cuando-echar-sal-garbanzos-lentejas-alubias-endurece-legumbres/">cuándo echar la sal a las legumbres</a>.</p>

<h2>Tomate, vinagre y otros ingredientes ácidos: mejor cuando la legumbre ya está tierna</h2>
<p>Los ácidos pueden ralentizar el ablandamiento de las paredes celulares. Si una receta lleva tomate, vino, vinagre o limón y estás teniendo problemas de textura, una estrategia segura es incorporarlos cuando la legumbre esté ya casi tierna.</p>

<h2>¿Sirve añadir bicarbonato?</h2>
<p>Una pequeña cantidad de bicarbonato aumenta el pH y puede acelerar el ablandamiento, especialmente con aguas duras o legumbres viejas. Excederse puede romper pieles, dar textura pastosa y aportar sabor desagradable. No es imprescindible para una legumbre fresca y bien cocinada.</p>
<p>Consulta cantidades y usos en <a href="/bicarbonato-en-remojo-legumbres-para-que-sirve-cuanto-usar/">bicarbonato en el remojo de legumbres</a>.</p>

<h2>El agua dura puede cambiar la cocción</h2>
<p>El calcio y el magnesio del agua pueden interactuar con componentes de las paredes vegetales y dificultar el ablandamiento. Si siempre tienes problemas aunque controles tiempo y remojo, probar con un agua de menor mineralización puede ser una prueba útil.</p>

<h2>Por qué las legumbres viejas tardan más</h2>
<p>Con el almacenamiento prolongado se producen cambios estructurales conocidos en tecnología de alimentos como endurecimiento de difícil cocción. Una legumbre seca puede seguir siendo segura durante mucho tiempo y, sin embargo, perder calidad culinaria y necesitar muchas más horas.</p>
<p>Por eso conviene comprar cantidades que realmente vayas a consumir y conservarlas en lugar seco, fresco y protegido de plagas. Más información en <a href="/como-conservar-legumbres-secas-despensa/">cómo guardar legumbres secas</a>.</p>

<h2>Olla rápida vs olla tradicional</h2>
<p>La olla rápida reduce mucho el tiempo porque cocina a mayor temperatura bajo presión. No empeora automáticamente el valor nutricional y puede ahorrar energía. La olla tradicional ofrece más control visual sobre la textura y facilita incorporar ingredientes por fases.</p>
<p>La elección depende del plato. Para una legumbre que quieras mantener muy entera, quizá prefieras control manual; para cocina diaria, la olla rápida es una herramienta excelente.</p>

<h2>Errores que más estropean las legumbres</h2>
<ul>
<li>Usar legumbres muy antiguas y pensar que todo se arregla con más fuego.</li>
<li>No utilizar suficiente agua en el remojo.</li>
<li>Añadir ingredientes ácidos desde el principio cuando la legumbre ya suele quedar dura.</li>
<li>Hervir alubias con demasiada violencia.</li>
<li>Dar por hecho que todas las variedades tienen el mismo tiempo.</li>
<li>Abrir una olla a presión sin respetar las instrucciones de seguridad.</li>
</ul>

<h2>Preguntas frecuentes</h2>
<h3>¿Qué legumbres no necesitan remojo?</h3>
<p>Muchas lentejas pueden cocinarse directamente. Garbanzos y la mayoría de alubias se benefician claramente de un remojo previo.</p>
<h3>¿Cuánto tiempo se dejan los garbanzos en remojo?</h3>
<p>Una referencia práctica es 8–12 horas.</p>
<h3>¿Puedo dejar las legumbres 24 horas en remojo?</h3>
<p>En climas cálidos, un remojo tan largo a temperatura ambiente aumenta el riesgo de fermentación. Si necesitas prolongarlo, utiliza refrigeración y agua limpia.</p>
<h3>¿Por qué siguen duras después de dos horas?</h3>
<p>Edad de la legumbre, agua dura, acidez, variedad y temperatura de cocción son causas posibles.</p>
<h3>¿Se pierden nutrientes al remojar?</h3>
<p>Una parte de compuestos solubles puede pasar al agua, pero el remojo también mejora la cocción y modifica antinutrientes. Consulta <a href="/remojo-legumbres-pierden-nutrientes/">qué nutrientes cambian con el remojo</a>.</p>

<h2>Conclusión</h2>
<p>Para cocinar legumbres secas con consistencia, piensa menos en trucos y más en proceso: producto no demasiado viejo, hidratación adecuada, agua suficiente, cocción estable y tiempo adaptado a la variedad. Con esas bases, garbanzos, lentejas y alubias dejan de ser imprevisibles.</p>
HTML;

emdo_consolidation_update(
	'que-verduras-tienen-mas-vitamina-c',
	'¿Qué verduras tienen más vitamina C? Ranking por 100 g, raciones y efecto de la cocción',
	'Ranking de verduras con más vitamina C, con valores por 100 g, comparación con la naranja, efecto de la cocción y consejos para conservarla mejor.',
	$vitamin_c,
	'Qué verduras tienen más vitamina C: ranking por 100 g, comparación con la naranja y cómo cambian los valores al cocinar, congelar o servir una ración.'
);

emdo_consolidation_update(
	'que-verduras-tienen-mas-hierro',
	'¿Qué verduras tienen más hierro? Ranking, absorción y cómo aprovecharlo mejor',
	'Comparativa de verduras con más hierro por 100 g, diferencias entre hierro vegetal y animal y cómo la vitamina C puede mejorar su absorción.',
	$iron,
	'Qué verduras tienen más hierro: ranking por 100 g, por qué el hierro vegetal se absorbe distinto y cómo combinarlo con vitamina C para aprovecharlo mejor.'
);

emdo_consolidation_update(
	'que-verduras-tienen-mas-fibra',
	'¿Qué verduras tienen más fibra? Ranking por 100 g y por ración',
	'Comparativa de las verduras con más fibra, con valores por 100 g, efecto del cocinado, importancia de la ración y diferencias entre triturar y colar.',
	$fiber,
	'Qué verduras tienen más fibra: ranking de alcachofa, guisantes, brócoli y otras hortalizas, con valores por 100 g, raciones y efecto del cocinado.'
);

emdo_consolidation_update(
	'tomate-como-elegir-conservar-usar-cada-tipo',
	'Tomate: tipos, cómo elegirlo, saber si está maduro, conservarlo y usarlo en cocina',
	'Guía completa del tomate: cómo elegirlo, reconocer su madurez, conservarlo dentro o fuera de la nevera y escoger el tipo adecuado para cada receta.',
	$tomato,
	'Tomate: cómo elegirlo, reconocer cuándo está maduro, conservarlo correctamente y escoger entre tomate de ensalada, pera, rosa, cherry y otros tipos.'
);

emdo_consolidation_update(
	'verduras-temporada-espana-calendario-meses-que-comprar',
	'Verduras de temporada en España: calendario por meses y guía para comprar mejor',
	'Calendario práctico de verduras y hortalizas de temporada en España por meses, con claves para interpretar campañas, origen, cultivo protegido y disponibilidad.',
	$seasonal,
	'Calendario de verduras de temporada en España mes a mes: qué hortalizas destacan en invierno, primavera, verano y otoño y cómo interpretar su campaña.'
);

emdo_consolidation_update(
	'aove-filtrado-o-sin-filtrar-diferencias',
	'AOVE filtrado vs sin filtrar: diferencias de sabor, aspecto, conservación y calidad',
	'Qué cambia entre un AOVE filtrado y uno sin filtrar: turbidez, sedimentos, polifenoles, estabilidad, conservación y cuál conviene elegir según el uso.',
	$filtered,
	'AOVE filtrado vs sin filtrar: qué cambia en aspecto, sabor, sedimentos, polifenoles y conservación, y cuál conviene elegir según cuánto tardarás en consumirlo.'
);

emdo_consolidation_update(
	'aove-pierde-propiedades-al-calentarlo-que-cambia-al-cocinar',
	'¿El AOVE pierde propiedades al calentarlo? Qué cambia al cocinar y a qué temperatura',
	'Qué ocurre al calentar aceite de oliva virgen extra: polifenoles, vitamina E, ácido oleico, fritura, reutilización y cómo minimizar la degradación.',
	$heating,
	'¿El AOVE pierde propiedades al calentarlo? Explicamos qué pasa con polifenoles, vitamina E y ácido oleico al freír, saltear, hornear o reutilizar el aceite.'
);

emdo_consolidation_update(
	'guia-cocinar-legumbres-secas-remojo-coccion-errores',
	'Cómo cocinar legumbres secas: remojo, tiempos, olla rápida y errores que las dejan duras',
	'Guía completa para cocinar garbanzos, lentejas y alubias: tiempos de remojo y cocción, sal, bicarbonato, agua dura, olla rápida y errores frecuentes.',
	$legumes,
	'Cómo cocinar legumbres secas: remojo y tiempos de garbanzos, lentejas y alubias, olla rápida, sal, bicarbonato, agua dura y soluciones si quedan duras.'
);

$redirects = array(
	'verduras-mas-vitamina-c-comparativa' => 'que-verduras-tienen-mas-vitamina-c',
	'verduras-mas-hierro-comparativa' => 'que-verduras-tienen-mas-hierro',
	'verduras-mas-fibra-comparativa' => 'que-verduras-tienen-mas-fibra',
	'tomates-como-elegir-madurez-conservar-usar-segun-receta' => 'tomate-como-elegir-conservar-usar-cada-tipo',
	'hortalizas-de-temporada-como-elegir-mejor' => 'verduras-temporada-espana-calendario-meses-que-comprar',
	'aove-filtrado-vs-sin-filtrar-diferencias' => 'aove-filtrado-o-sin-filtrar-diferencias',
	'aove-pierde-propiedades-al-calentarlo-que-cambia-temperatura' => 'aove-pierde-propiedades-al-calentarlo-que-cambia-al-cocinar',
	'legumbres-secas-remojo-coccion-como-elegir' => 'guia-cocinar-legumbres-secas-remojo-coccion-errores',
);

foreach ( $redirects as $source => $destination ) {
	emdo_consolidation_archive( $source, $destination );
}

emdo_consolidation_rewrite_links( $redirects );
update_option( 'emdo_blog_consolidation_batch1_20260901', gmdate( 'c' ), false );
echo "BATCH1_OK\n";
