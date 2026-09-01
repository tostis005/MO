<?php
/**
 * EMDO SEO Task 2 - consolidate over-fragmented nutrition content and reinforce
 * standalone vegetable queries that deserve their own URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function emdo_task2_update_post( int $id, string $expected_slug, string $title, string $excerpt, string $content ): void {
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post ) {
		throw new RuntimeException( 'Missing post ID ' . $id );
	}
	if ( $post->post_name !== $expected_slug ) {
		throw new RuntimeException( sprintf( 'Slug mismatch for %d: expected %s, got %s', $id, $expected_slug, $post->post_name ) );
	}

	$result = wp_update_post(
		array(
			'ID'           => $id,
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => $content,
		),
		true
	);
	if ( is_wp_error( $result ) ) {
		throw new RuntimeException( 'Update failed for ' . $id . ': ' . $result->get_error_message() );
	}
}

$aove = <<<'HTML'
<!-- EMDO-SEO-TASK2-AOVE-20260901 -->
<p>El <strong>aceite de oliva virgen extra (AOVE)</strong> es, desde el punto de vista nutricional, un alimento muy concentrado: prácticamente todo su peso es grasa. Pero decir solo que «es grasa» se queda corto. Lo que distingue su composición es el claro predominio de <strong>ácidos grasos monoinsaturados, sobre todo ácido oleico</strong>, junto con una fracción minoritaria en la que aparecen vitamina E y numerosos compuestos fenólicos.</p>
<p>En esta guía reunimos en una sola página las preguntas que más se solapan cuando se busca la composición del AOVE: cuántas calorías aporta, qué tipo de grasa contiene, cuánta grasa saturada tiene, si contiene omega-3 y omega-6, si aporta vitamina E, si tiene colesterol y qué papel ocupan los polifenoles. Las cifras son orientativas: un AOVE concreto puede variar según variedad, campaña, madurez de la aceituna, elaboración y conservación.</p>

<h2>Composición nutricional del AOVE: resumen por 100 g y por cucharada</h2>
<p>Como referencia homogénea, la siguiente tabla utiliza valores de composición de aceite de oliva de USDA FoodData Central y los redondea para facilitar la lectura. Una cucharada se ha estimado en <strong>13,5 g</strong>; en una cocina real el peso puede variar según la cuchara y cuánto se llene.</p>
<table>
<thead><tr><th>Nutriente</th><th>Por 100 g</th><th>Por cucharada (≈13,5 g)</th></tr></thead>
<tbody>
<tr><td>Energía</td><td>≈884 kcal</td><td>≈119 kcal</td></tr>
<tr><td>Grasa total</td><td>100 g</td><td>≈13,5 g</td></tr>
<tr><td>Grasa saturada</td><td>≈13,8 g</td><td>≈1,9 g</td></tr>
<tr><td>Grasa monoinsaturada</td><td>≈73 g</td><td>≈9,9 g</td></tr>
<tr><td>Grasa poliinsaturada</td><td>≈10,5 g</td><td>≈1,4 g</td></tr>
<tr><td>Ácido linoleico (omega-6)</td><td>≈9,8 g</td><td>≈1,3 g</td></tr>
<tr><td>Ácido alfa-linolénico (omega-3)</td><td>≈0,8 g</td><td>≈0,10 g</td></tr>
<tr><td>Vitamina E (alfa-tocoferol)</td><td>≈14,4 mg</td><td>≈1,9 mg</td></tr>
<tr><td>Proteínas</td><td>0 g</td><td>0 g</td></tr>
<tr><td>Hidratos de carbono</td><td>0 g</td><td>0 g</td></tr>
<tr><td>Azúcares</td><td>0 g</td><td>0 g</td></tr>
<tr><td>Fibra</td><td>0 g</td><td>0 g</td></tr>
<tr><td>Colesterol</td><td>0 mg</td><td>0 mg</td></tr>
</tbody>
</table>
<p><em>Valores orientativos y redondeados. No sustituyen la información nutricional de la etiqueta de un aceite concreto.</em></p>

<h2>La clave del AOVE está en el tipo de grasa, no solo en la cantidad</h2>
<p>Todos los aceites aportan mucha energía porque están formados casi por completo por lípidos. La diferencia importante aparece al mirar <strong>qué ácidos grasos forman esos lípidos</strong>. El Consejo Oleícola Internacional sitúa al ácido oleico como el ácido graso predominante del aceite de oliva y recoge un intervalo amplio, aproximadamente del 55 al 83 % de los ácidos grasos, porque la proporción natural cambia entre aceites.</p>
<p>Ese dato explica por qué una tabla genérica puede mostrar alrededor de un 73 % de grasa monoinsaturada y, al mismo tiempo, un Picual concreto, un Arbequina o un aceite de otra variedad ofrecer una composición diferente. No hay que confundir una media de base de datos con una especificación fija para cada botella.</p>
<p>Si quieres profundizar solo en este ácido graso, la guía específica <a href="/acido-oleico-aceite-oliva-que-es-cuanto-tiene/">qué es el ácido oleico y cuánto tiene el aceite de oliva</a> desarrolla la cuestión sin repetir toda la tabla nutricional.</p>

<h2>¿Cuánta grasa saturada tiene el AOVE?</h2>
<p>El AOVE <strong>sí contiene grasa saturada</strong>. Una referencia habitual ronda los 13,8 g por 100 g, es decir, cerca de 1,9 g en una cucharada de 13,5 g. Lo importante es poner esa cifra junto al resto del perfil: la fracción monoinsaturada es mucho mayor y la poliinsaturada ocupa una proporción menor.</p>
<p>Entre los saturados aparece sobre todo ácido palmítico, además de cantidades menores de ácido esteárico y otros ácidos grasos. La variedad, el clima y el grado de maduración pueden desplazar las proporciones, de modo que no existe un AOVE universal con un porcentaje idéntico de saturadas.</p>

<h2>¿El AOVE tiene omega-3 y omega-6?</h2>
<p>Sí, pero no en la misma cantidad. La fracción poliinsaturada del aceite de oliva contiene principalmente <strong>ácido linoleico (omega-6)</strong> y una cantidad bastante menor de <strong>ácido alfa-linolénico o ALA (omega-3)</strong>. En la referencia utilizada, 100 g aportan aproximadamente 9,8 g de linoleico y 0,8 g de alfa-linolénico.</p>
<p>Por eso no es correcto presentar el AOVE como un aceite especialmente concentrado en omega-3. Su rasgo lipídico distintivo es el ácido oleico, un monoinsaturado de la familia omega-9. Tampoco debe confundirse el ALA vegetal con EPA y DHA, otros omega-3 presentes principalmente en alimentos de origen marino.</p>

<h2>Vitamina E: el principal micronutriente que suele destacarse</h2>
<p>El aceite de oliva aporta <strong>vitamina E en forma de tocoferoles</strong>, especialmente alfa-tocoferol. Como orientación, una tabla genérica de aceite de oliva recoge alrededor de 14,4 mg por 100 g, lo que equivale a cerca de 1,9 mg en una cucharada de 13,5 g.</p>
<p>La cifra real no es idéntica en todos los vírgenes extra. Variedad, madurez, extracción, oxígeno, luz, temperatura y tiempo de almacenamiento influyen en la fracción antioxidante. Esa es una de las razones por las que resulta más riguroso utilizar una cifra orientativa y consultar la analítica o la etiqueta cuando se necesita describir un aceite concreto.</p>

<h2>Polifenoles y antioxidantes: importantes, pero no son una cifra fija de la tabla nutricional</h2>
<p>Los <strong>compuestos fenólicos</strong> forman parte de la fracción minoritaria del AOVE. Entre ellos aparecen derivados de hidroxitirosol y tirosol y compuestos relacionados con oleuropeína y oleocantal. No deben confundirse con la vitamina E: son familias químicas diferentes y su concentración puede variar de forma muy marcada.</p>
<p>El Consejo Oleícola Internacional dispone incluso de métodos analíticos específicos para determinar compuestos fenólicos en aceites de oliva. Esa variabilidad explica por qué una tabla nutricional estándar no puede asignar a «todos los AOVE» una cantidad universal de polifenoles.</p>
<p>Para profundizar, consulta <a href="/polifenoles-aceite-oliva-que-son/">qué son los polifenoles del aceite de oliva</a> y <a href="/antioxidantes-aceite-oliva-virgen-extra/">qué antioxidantes tiene el aceite de oliva virgen extra</a>.</p>

<h2>¿El aceite de oliva tiene colesterol?</h2>
<p><strong>No.</strong> El colesterol es un esterol propio de tejidos animales y el aceite de oliva es un alimento de origen vegetal. Por eso una tabla de composición del aceite de oliva registra 0 mg de colesterol.</p>
<p>Esto no significa que «grasa saturada» y «colesterol» sean lo mismo. Son conceptos distintos: el AOVE contiene una fracción de ácidos grasos saturados, pero no contiene colesterol.</p>

<h2>¿Tiene proteínas, hidratos, azúcar o fibra?</h2>
<p>En la práctica, el AOVE aporta <strong>0 g de proteínas, 0 g de hidratos de carbono, 0 g de azúcares y 0 g de fibra</strong> en las tablas de composición habituales. Tampoco es un alimento que destaque por minerales. Su valor nutricional se entiende fundamentalmente por el perfil de ácidos grasos y por sus componentes minoritarios.</p>
<p>Esto ayuda a interpretar correctamente la etiqueta: que un AOVE no tenga proteínas o fibra no dice nada negativo sobre su calidad. Simplemente no son los nutrientes característicos de un aceite.</p>

<h2>¿Cuántas calorías tiene una cucharada de AOVE?</h2>
<p>Una cucharada de unos 13,5 g aporta aproximadamente <strong>119 kcal</strong>. Por 100 g, la cifra ronda las 884 kcal. La energía es alta porque la grasa aporta unas 9 kcal por gramo y el aceite prácticamente no contiene agua.</p>
<p>La cantidad doméstica importa mucho: una cucharada, dos cucharadas o un chorro sin medir pueden representar cantidades bastante diferentes. Si buscas únicamente equivalencias de cucharadas y raciones, consulta <a href="/calorias-aceite-oliva-cucharada-100g/">cuántas calorías tiene el aceite de oliva por cucharada y por 100 g</a>.</p>

<h2>Qué puede cambiar de un AOVE a otro</h2>
<ul>
<li><strong>Variedad de aceituna:</strong> modifica especialmente la relación entre oleico y linoleico y también parte de la fracción minoritaria.</li>
<li><strong>Madurez y campaña:</strong> el fruto no tiene la misma composición en todas las fases ni en todos los años.</li>
<li><strong>Zona y clima:</strong> las condiciones de cultivo afectan al perfil de ácidos grasos.</li>
<li><strong>Elaboración:</strong> el proceso condiciona especialmente compuestos minoritarios y estabilidad.</li>
<li><strong>Filtrado:</strong> afecta sobre todo a agua, sólidos en suspensión y evolución durante el almacenamiento; no convierte radicalmente el perfil de triglicéridos.</li>
<li><strong>Conservación:</strong> luz, oxígeno, calor y tiempo favorecen la degradación oxidativa y la pérdida progresiva de determinados componentes minoritarios.</li>
</ul>
<p>Estas diferencias están explicadas con más detalle en <a href="/aove-filtrado-o-sin-filtrar-diferencias/">AOVE filtrado o sin filtrar</a> y en <a href="/como-conservar-aove-correctamente/">cómo conservar el AOVE correctamente</a>.</p>

<h2>¿Qué ocurre al calentarlo?</h2>
<p>Calentar un AOVE no hace que de repente deje de ser aceite de oliva ni elimina toda su fracción insaturada. Lo que sí ocurre es que <strong>temperatura, tiempo y reutilización</strong> favorecen transformaciones oxidativas y pueden reducir compuestos minoritarios sensibles, incluidos parte de los fenoles y tocoferoles.</p>
<p>No es equivalente un salteado breve a mantener un aceite durante mucho tiempo a alta temperatura o reutilizarlo repetidamente. La guía <a href="/aove-pierde-propiedades-al-calentarlo-que-cambia-al-cocinar/">qué cambia en el AOVE al calentarlo</a> aborda esa intención de búsqueda por separado.</p>

<h2>Cómo interpretar una etiqueta de AOVE sin sacar conclusiones equivocadas</h2>
<p>La declaración nutricional te permite comparar energía, grasas, saturadas y otros nutrientes declarados. Pero <strong>no resume por sí sola la calidad de un virgen extra</strong>. La categoría «virgen extra» depende de requisitos químicos y sensoriales establecidos para los aceites de oliva vírgenes; no de superar una cifra concreta de vitamina E o polifenoles.</p>
<p>Tampoco conviene atribuir a una variedad una composición exacta basándose solo en su nombre. Si necesitas conocer el contenido preciso de un lote, la única respuesta exacta es su análisis.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Cuál es el nutriente principal del AOVE?</h3>
<p>La grasa. Dentro de ella predominan los ácidos grasos monoinsaturados, especialmente el ácido oleico.</p>
<h3>¿El AOVE tiene vitamina E?</h3>
<p>Sí. Las tablas de composición recogen alfa-tocoferol, aunque la concentración real varía entre aceites y durante el almacenamiento.</p>
<h3>¿El AOVE tiene omega-3?</h3>
<p>Sí, principalmente ALA, pero en una cantidad pequeña frente al omega-6 y, sobre todo, frente al ácido oleico monoinsaturado.</p>
<h3>¿El aceite de oliva tiene colesterol?</h3>
<p>No. Es un alimento vegetal y las tablas de composición registran 0 mg de colesterol.</p>
<h3>¿El AOVE tiene azúcar?</h3>
<p>No. Su declaración nutricional habitual registra 0 g de hidratos y 0 g de azúcares.</p>
<h3>¿Un AOVE con más polifenoles es siempre de mayor calidad?</h3>
<p>No puede reducirse la calidad global del aceite a una sola cifra. Los fenoles son relevantes, pero la categoría virgen extra exige además parámetros químicos y una evaluación sensorial sin defectos.</p>

<h2>Fuentes y criterio de los datos</h2>
<ul>
<li><a href="https://fdc.nal.usda.gov/" rel="noopener">USDA FoodData Central</a>: referencia de composición para aceite de oliva utilizada para las cifras nutricionales orientativas.</li>
<li><a href="https://www.internationaloliveoil.org/what-we-do/chemistry-standardisation-unit/standards-and-methods/" rel="noopener">Consejo Oleícola Internacional</a>: estándares, métodos de análisis y composición de los aceites de oliva.</li>
<li><a href="https://www.internationaloliveoil.org/evoo-the-functional-food/" rel="noopener">Consejo Oleícola Internacional: EVOO, the functional food</a>: contexto sobre ácido oleico, tocoferoles, compuestos fenólicos y variabilidad durante producción y almacenamiento.</li>
</ul>
<p><strong>Criterio editorial:</strong> las cifras de tablas son referencias de composición, no certificados de un producto concreto. Para comparar dos AOVE comerciales debe prevalecer su etiqueta y, cuando exista, su analítica de lote.</p>
HTML;

$potasio = <<<'HTML'
<!-- EMDO-SEO-TASK2-POTASIO-20260901 -->
<p>Si comparamos hortalizas habituales por 100 gramos, <strong>espinaca, acelga, patata, alcachofa, coles de Bruselas y brócoli</strong> se encuentran entre las opciones que suelen aportar más potasio. Pero un ranking útil necesita dos matices: comparar siempre alimentos en el mismo estado —crudos con crudos o cocidos con cocidos— y mirar también la <strong>ración que realmente se come</strong>.</p>
<p>El potasio es un mineral presente en muchos alimentos vegetales. No existe una única «verdura con potasio»: prácticamente todo el grupo aporta algo, y la posición exacta cambia según variedad, base de datos, agua del alimento y forma de cocción.</p>

<h2>Verduras y hortalizas con más potasio por 100 g</h2>
<p>La tabla siguiente reúne valores orientativos y redondeados de alimentos crudos procedentes de bases de composición como USDA FoodData Central. Se han escogido hortalizas comunes en España para que la comparación sea práctica.</p>
<table>
<thead><tr><th>Alimento crudo</th><th>Potasio aprox. por 100 g</th><th>Comentario práctico</th></tr></thead>
<tbody>
<tr><td>Espinaca</td><td>≈560 mg</td><td>Muy concentrada por 100 g, aunque una ración cruda puede pesar poco.</td></tr>
<tr><td>Patata</td><td>≈420 mg</td><td>La ración habitual suele superar claramente los 100 g.</td></tr>
<tr><td>Coles de Bruselas</td><td>≈390 mg</td><td>Una de las crucíferas que más destacan.</td></tr>
<tr><td>Acelga</td><td>≈380 mg</td><td>Hoja verde con aporte elevado.</td></tr>
<tr><td>Alcachofa</td><td>≈370 mg</td><td>La porción comestible real es menor que el peso de la pieza entera.</td></tr>
<tr><td>Zanahoria</td><td>≈320 mg</td><td>Aporta una cantidad intermedia-alta.</td></tr>
<tr><td>Brócoli</td><td>≈315 mg</td><td>Combina potasio con otros micronutrientes y fibra.</td></tr>
<tr><td>Calabacín</td><td>≈260 mg</td><td>Su gran contenido en agua reduce la concentración por 100 g.</td></tr>
<tr><td>Tomate</td><td>≈240 mg</td><td>Puede aportar bastante por la cantidad que se consume en una ensalada.</td></tr>
<tr><td>Pimiento rojo</td><td>≈210 mg</td><td>No lidera en potasio, aunque destaca por otros nutrientes.</td></tr>
</tbody>
</table>
<p><em>Las cifras son aproximadas. Pueden variar por variedad, madurez, cultivo y base de datos. La patata es botánicamente un tubérculo, pero se incluye por su uso culinario y por ser una hortaliza habitual en la dieta.</em></p>

<h2>¿La espinaca es la verdura con más potasio?</h2>
<p>Entre las hortalizas comunes incluidas en esta tabla, la espinaca cruda aparece en la parte alta. Eso no significa que sea «la campeona absoluta» de todos los vegetales posibles ni que una ensalada de espinaca vaya a aportar necesariamente más que una ración grande de patata o alcachofa.</p>
<p>La concentración por 100 g y el aporte de una ración son preguntas diferentes. Cien gramos de hojas crudas ocupan mucho volumen, mientras que comer 200 g de patata preparada es relativamente habitual.</p>

<h2>Por 100 g o por ración: cuál es la comparación que sirve</h2>
<p>Los 100 g son útiles para ordenar alimentos en igualdad de condiciones. Para saber qué aporta un plato, hay que multiplicar esa concentración por la cantidad comestible.</p>
<p>Ejemplo sencillo: un alimento con 300 mg por 100 g aporta unos 450 mg si la ración comestible es de 150 g. Otro con 500 mg por 100 g puede aportar solo 250 mg si se consumen 50 g. Por eso no recomendamos escoger verduras únicamente por su puesto en una tabla.</p>

<h2>Qué cambia al cocer las verduras</h2>
<p>El potasio es un mineral: <strong>no se destruye por el calor como puede ocurrir con determinadas vitaminas</strong>. Sin embargo, es soluble en agua. Al hervir un alimento, parte puede pasar al líquido de cocción, especialmente si se corta en trozos pequeños y se utiliza mucha agua.</p>
<p>Eso provoca dos efectos que a veces parecen contradictorios:</p>
<ul>
<li>si se desecha el agua, parte del potasio que salió del alimento deja de estar en el plato;</li>
<li>si el alimento pierde mucha agua durante la cocción, algunos nutrientes pueden parecer más concentrados por 100 g.</li>
</ul>
<p>Por eso comparar «espinaca cruda» con «espinaca hervida y escurrida» sin indicar el estado puede inducir a error.</p>

<h2>¿Cocer al vapor conserva más potasio que hervir?</h2>
<p>En general, los métodos que reducen el contacto directo con mucha agua limitan la lixiviación de minerales al líquido. Cocción al vapor, microondas con poca agua o salteado pueden conservar una mayor proporción dentro del alimento que un hervido largo seguido de escurrido. Eso no convierte automáticamente un método en «mejor» para todas las recetas: textura, seguridad y preferencias también importan.</p>

<h2>Las verduras con más potasio no son necesariamente las más nutritivas</h2>
<p>El potasio es solo una parte del perfil nutricional. El pimiento rojo queda por debajo de la espinaca en esta tabla y, sin embargo, sobresale claramente en <a href="/que-verduras-tienen-mas-vitamina-c/">vitamina C</a>. Otras hortalizas destacan por <a href="/que-verduras-tienen-mas-fibra/">fibra</a>, folatos, carotenoides u otros minerales.</p>
<p>La guía <a href="/nutrientes-verduras-vitaminas-minerales-fibra/">qué nutrientes aportan las verduras</a> reúne el panorama completo y evita convertir un solo mineral en criterio de calidad.</p>

<h2>¿Qué pasa con las conservas y las verduras congeladas?</h2>
<p>El congelado no «elimina» el potasio. En conservas y productos cocidos, la distribución entre alimento y líquido sí puede cambiar. Si una conserva está en un líquido de cobertura y se escurre, parte de los minerales solubles puede quedar en ese líquido. Además, la sal añadida modifica el sodio, que es un dato distinto del potasio.</p>
<p>En <a href="/verduras-frescas-vs-congeladas-diferencias-nutrientes-sabor/">verduras frescas vs congeladas</a> explicamos por qué el formato por sí solo no permite decir que una verdura «ya no tiene nutrientes».</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Qué verdura tiene más potasio?</h3>
<p>No hay una respuesta universal sin definir qué alimentos y qué estado se comparan. Entre hortalizas comunes crudas, la espinaca aparece muy arriba, junto con patata, acelga, alcachofa y coles de Bruselas.</p>
<h3>¿La patata tiene mucho potasio?</h3>
<p>Sí, y además la ración habitual suele ser grande. El contenido final del plato cambia según variedad y preparación.</p>
<h3>¿El tomate tiene potasio?</h3>
<p>Sí. Su concentración es menor que la de las hojas verdes de la parte alta de la tabla, pero una ración generosa de tomate puede contribuir de forma apreciable.</p>
<h3>¿Se pierde todo el potasio al hervir?</h3>
<p>No. Parte puede pasar al agua de cocción; la cantidad depende del corte, volumen de agua, tiempo y si el líquido se consume o se desecha.</p>

<h2>Fuentes y criterio</h2>
<ul>
<li><a href="https://fdc.nal.usda.gov/" rel="noopener">USDA FoodData Central</a>, valores de composición de hortalizas crudas utilizados como referencia y redondeados.</li>
<li><a href="https://www.bedca.net/" rel="noopener">BEDCA, Base de Datos Española de Composición de Alimentos</a>, utilizada como referencia complementaria para alimentos consumidos en España.</li>
</ul>
<p>Las tablas de composición describen valores medios. Si una persona necesita controlar clínicamente su ingesta de potasio, debe utilizar las indicaciones y cantidades individualizadas de su profesional sanitario, no un ranking general de internet.</p>
HTML;

$calcio = <<<'HTML'
<!-- EMDO-SEO-TASK2-CALCIO-20260901 -->
<p>Las verduras pueden aportar calcio, pero comparar solo los miligramos de una tabla cuenta una historia incompleta. Entre hortalizas habituales, <strong>coles de hoja, espinaca, acelga y brócoli</strong> pueden aportar cantidades interesantes; sin embargo, el <strong>calcio total no es lo mismo que el calcio que el organismo puede absorber</strong>. Compuestos naturales como los oxalatos hacen que dos verduras con cifras parecidas se comporten de manera diferente.</p>
<p>Por eso esta comparativa responde a dos preguntas a la vez: qué verduras contienen más calcio por 100 g y por qué la biodisponibilidad importa al interpretar el ranking.</p>

<h2>Verduras con más calcio por 100 g: comparativa orientativa</h2>
<table>
<thead><tr><th>Alimento crudo</th><th>Calcio aprox. por 100 g</th><th>Cómo interpretar el dato</th></tr></thead>
<tbody>
<tr><td>Berza / kale</td><td>≈150 mg</td><td>Hoja verde con concentración alta y menor problema de oxalatos que la espinaca.</td></tr>
<tr><td>Espinaca</td><td>≈100 mg</td><td>Mucho calcio total, pero también bastante oxalato.</td></tr>
<tr><td>Acelga</td><td>≈50 mg</td><td>Aporta calcio, aunque sus oxalatos también condicionan la absorción.</td></tr>
<tr><td>Brócoli</td><td>≈45–50 mg</td><td>Menor cifra total que la espinaca, pero con una biodisponibilidad más favorable.</td></tr>
<tr><td>Alcachofa</td><td>≈40–45 mg</td><td>Aporte intermedio.</td></tr>
<tr><td>Repollo</td><td>≈40 mg</td><td>La cifra varía entre tipos de col.</td></tr>
<tr><td>Judía verde</td><td>≈35–40 mg</td><td>Aporte moderado.</td></tr>
<tr><td>Zanahoria</td><td>≈30–35 mg</td><td>No destaca frente a las hojas verdes.</td></tr>
<tr><td>Tomate</td><td>≈10 mg</td><td>Concentración baja por 100 g.</td></tr>
<tr><td>Pimiento rojo</td><td>≈7–10 mg</td><td>Su fortaleza nutricional está en otros micronutrientes, no en el calcio.</td></tr>
</tbody>
</table>
<p><em>Valores orientativos y redondeados de alimentos crudos. Pueden cambiar según variedad, estado y base de composición.</em></p>

<h2>¿La espinaca tiene mucho calcio?</h2>
<p>En términos de <strong>calcio total</strong>, sí: puede rondar 100 mg por 100 g en tablas de composición. El problema aparece si esa cifra se interpreta como «100 mg disponibles para el organismo». La espinaca contiene oxalatos, compuestos que se unen al calcio y reducen su absorción intestinal.</p>
<p>Eso explica una aparente paradoja: una hortaliza con menos calcio total, como el brócoli, puede ofrecer una fracción aprovechable proporcionalmente mayor. Por eso un artículo riguroso sobre «verduras con calcio» no debería limitarse a ordenar una columna de miligramos.</p>

<h2>Qué significa biodisponibilidad del calcio</h2>
<p>La <strong>biodisponibilidad</strong> describe qué parte de un nutriente ingerido llega a estar disponible para ser absorbido y utilizado. En el caso del calcio vegetal influyen la propia matriz del alimento y compuestos como oxalatos y fitatos.</p>
<p>No significa que una verdura con oxalatos «no aporte nada». Significa que el número de la tabla no debe convertirse directamente en cantidad absorbida. La dieta completa, las cantidades consumidas y el resto de fuentes de calcio son lo que da contexto.</p>

<h2>Brócoli frente a espinaca: un buen ejemplo</h2>
<p>La espinaca suele ganar al brócoli si miramos únicamente calcio total por 100 g. Cuando introducimos la absorción, la comparación deja de ser tan simple. Las crucíferas como brócoli y determinadas coles tienen poco oxalato en comparación con la espinaca, por lo que su calcio se considera mejor aprovechable.</p>
<p>La conclusión práctica no es «el brócoli es mejor que la espinaca», sino que <strong>cantidad total y biodisponibilidad son dos variables distintas</strong>. Además, ambos alimentos aportan otros nutrientes.</p>

<h2>Por 100 g y por ración pueden salir rankings diferentes</h2>
<p>Una tabla estandarizada permite comparar. Una comida real depende de la ración. Cien gramos de hojas de kale o espinaca crudas ocupan mucho volumen; al cocinarse pierden agua y su peso cambia. Una ración de brócoli cocinado puede ser más sencilla de cuantificar y consumir.</p>
<p>Por eso, para responder «cuánto calcio he comido», hay que partir del peso realmente consumido y de si el valor de la base de datos corresponde a alimento crudo, hervido, al vapor o escurrido.</p>

<h2>¿La cocción elimina el calcio?</h2>
<p>El calcio es un mineral y <strong>no se destruye por efecto del calor</strong>. Sí puede cambiar su concentración por 100 g al cambiar el agua del alimento y una parte puede pasar al agua de cocción. El resultado depende del método, tiempo, tamaño de los trozos y de si se consume el líquido.</p>
<p>Este mismo principio explica por qué no conviene comparar directamente una cifra de verdura cruda con otra hervida sin leer cómo está descrito el alimento en la base de datos.</p>

<h2>¿Las verduras bastan como única fuente de calcio?</h2>
<p>La pregunta no puede responderse mirando una sola hortaliza. La ingesta total procede de todo el patrón alimentario y las necesidades cambian según edad y circunstancias personales. Además de verduras, existen otras fuentes alimentarias de calcio de origen animal y vegetal.</p>
<p>Esta guía tiene un objetivo comparativo: ayudar a entender qué hortalizas aportan más y por qué la absorción cambia. No pretende diseñar una pauta clínica ni sustituir una recomendación dietética individualizada.</p>

<h2>Calcio, hierro, potasio y fibra: no hay una única «verdura más nutritiva»</h2>
<p>Una misma verdura puede ocupar posiciones muy distintas según el nutriente. Para ampliar:</p>
<ul>
<li><a href="/que-verduras-tienen-mas-hierro/">verduras con más hierro</a>;</li>
<li><a href="/verduras-mas-potasio-comparativa/">verduras con más potasio</a>;</li>
<li><a href="/que-verduras-tienen-mas-fibra/">verduras con más fibra</a>;</li>
<li><a href="/que-verduras-tienen-mas-vitamina-c/">verduras con más vitamina C</a>;</li>
<li><a href="/nutrientes-verduras-vitaminas-minerales-fibra/">guía general de nutrientes de las verduras</a>.</li>
</ul>
<p>Este enlazado permite que cada página responda una intención concreta sin repetir el mismo artículo cambiando únicamente el nombre del mineral.</p>

<h2>Preguntas frecuentes</h2>
<h3>¿Qué verdura tiene más calcio?</h3>
<p>Depende del conjunto que se compare. Entre hortalizas comunes, algunas coles de hoja y la espinaca presentan cifras altas de calcio total. La absorción, sin embargo, no es igual en todas.</p>
<h3>¿El calcio de la espinaca se absorbe bien?</h3>
<p>Se absorbe peor que el de algunas verduras con menos oxalatos porque parte queda unido a estos compuestos.</p>
<h3>¿El brócoli tiene calcio?</h3>
<p>Sí. Su cifra total es menor que la de la espinaca, pero se considera una fuente vegetal con una fracción de calcio relativamente bien disponible.</p>
<h3>¿Hervir una verdura destruye su calcio?</h3>
<p>No. El mineral no se destruye con el calor, aunque parte puede pasar al agua y la concentración por 100 g puede cambiar.</p>

<h2>Fuentes y criterio</h2>
<ul>
<li><a href="https://fdc.nal.usda.gov/" rel="noopener">USDA FoodData Central</a>, valores de composición de hortalizas empleados como referencia y redondeados.</li>
<li><a href="https://www.bedca.net/" rel="noopener">BEDCA, Base de Datos Española de Composición de Alimentos</a>, referencia complementaria para composición de alimentos.</li>
</ul>
<p>Para valorar la absorción se ha tenido en cuenta el consenso nutricional sobre el efecto de los oxalatos en verduras de hoja. Las cifras concretas de absorción dependen del alimento y del estudio; por eso no se presenta un porcentaje universal.</p>
HTML;

$lomo = <<<'HTML'
<!-- EMDO-SEO-TASK2-LOMO-20260901 -->
<p>El <strong>lomo ibérico curado</strong> es un producto con una composición muy concentrada por la pérdida de agua durante el curado. Destaca sobre todo por su <strong>proteína</strong>, y también aporta grasa, hierro, zinc, fósforo y vitaminas del grupo B. La cantidad exacta cambia entre elaboradores y piezas: la proporción de grasa de la materia prima, el adobo, la sal y el grado de secado hacen que no exista una tabla universal válida para todos los lomos ibéricos.</p>
<p>Para dar cifras comparables usamos como referencia la ficha oficial de <strong>lomo embuchado</strong> publicada por el Ministerio de Agricultura, Pesca y Alimentación. Es una referencia de composición, no la analítica de todos los productos ibéricos. Para una pieza concreta siempre debe prevalecer su etiqueta.</p>

<h2>Composición nutricional del lomo embuchado por 100 g</h2>
<table>
<thead><tr><th>Nutriente</th><th>Por 100 g, referencia</th><th>En 50 g, aprox.</th></tr></thead>
<tbody>
<tr><td>Energía</td><td>≈386 kcal</td><td>≈193 kcal</td></tr>
<tr><td>Proteínas</td><td>≈50 g</td><td>≈25 g</td></tr>
<tr><td>Grasa total</td><td>≈20,7 g</td><td>≈10,4 g</td></tr>
<tr><td>Grasa saturada</td><td>≈6,7 g</td><td>≈3,4 g</td></tr>
<tr><td>Grasa monoinsaturada</td><td>≈8,7 g</td><td>≈4,3 g</td></tr>
<tr><td>Grasa poliinsaturada</td><td>≈3,2 g</td><td>≈1,6 g</td></tr>
<tr><td>Hidratos de carbono</td><td>0 g en la referencia</td><td>0 g</td></tr>
<tr><td>Hierro</td><td>≈3,7 mg</td><td>≈1,9 mg</td></tr>
<tr><td>Zinc</td><td>≈2,6 mg</td><td>≈1,3 mg</td></tr>
<tr><td>Fósforo</td><td>≈180 mg</td><td>≈90 mg</td></tr>
<tr><td>Potasio</td><td>≈230 mg</td><td>≈115 mg</td></tr>
<tr><td>Sodio</td><td>≈1.470 mg</td><td>≈735 mg</td></tr>
<tr><td>Vitamina B1</td><td>≈0,8 mg</td><td>≈0,4 mg</td></tr>
<tr><td>Niacina</td><td>≈12 mg</td><td>≈6 mg</td></tr>
<tr><td>Vitamina B12</td><td>≈2 µg</td><td>≈1 µg</td></tr>
</tbody>
</table>
<p><em>Valores orientativos de la ficha MAPA/FEN para lomo embuchado. Una etiqueta comercial puede diferir de forma importante, especialmente en grasa y sal.</em></p>

<h2>Por qué el lomo curado tiene tanta proteína por 100 g</h2>
<p>La concentración no significa que durante el curado «aparezca» proteína nueva. La carne pierde agua y, al reducirse el peso, los sólidos quedan más concentrados. Por eso 100 g de lomo embuchado pueden mostrar una cifra de proteína mucho mayor que 100 g de carne de cerdo fresca.</p>
<p>La referencia oficial utilizada recoge alrededor de 50 g de proteína por 100 g. La cifra real depende del producto y del grado de secado. Para desarrollar únicamente esta consulta mantenemos una página específica: <a href="/cuanta-proteina-tiene-lomo-iberico/">cuánta proteína tiene el lomo ibérico</a>.</p>

<h2>Cuánto hierro aporta el lomo ibérico</h2>
<p>La referencia de lomo embuchado recoge aproximadamente <strong>3,7 mg de hierro por 100 g</strong>. En una porción de 50 g serían alrededor de 1,9 mg. Al proceder de carne, parte del hierro se encuentra en formas asociadas al tejido animal y presenta un contexto de absorción distinto al hierro no hemo de los alimentos vegetales.</p>
<p>No hace falta una URL independiente para repetir solo este dato: se entiende mejor dentro del conjunto de proteína, minerales, vitaminas, grasa y sal del producto.</p>

<h2>Qué tipo de grasa tiene el lomo ibérico</h2>
<p>El lomo curado no es un alimento «sin grasa». En la ficha de referencia aparecen aproximadamente 20,7 g de grasa total por 100 g, repartidos en:</p>
<ul>
<li>unos <strong>8,7 g de monoinsaturadas</strong>;</li>
<li>unos <strong>6,7 g de saturadas</strong>;</li>
<li>unos <strong>3,2 g de poliinsaturadas</strong>.</li>
</ul>
<p>La grasa monoinsaturada es la fracción individual más alta de esa referencia, pero el perfil de un lomo ibérico concreto puede cambiar con la materia prima y el sistema de producción. No es riguroso asignar a todo lomo «100 % ibérico», «de bellota» o de otra categoría una cifra química fija sin analizar el producto.</p>

<h2>¿Ser ibérico cambia automáticamente la tabla nutricional?</h2>
<p>La palabra <strong>ibérico</strong> informa de la materia prima y del marco de calidad aplicable; no es una tabla nutricional. Genética, alimentación, ejercicio, pieza anatómica, infiltración, formulación y curado pueden influir en la composición final.</p>
<p>Por eso es mejor separar dos preguntas:</p>
<ul>
<li><strong>qué es el producto y de qué materia prima procede</strong>;</li>
<li><strong>qué nutrientes declara esa pieza concreta</strong>.</li>
</ul>
<p>La primera se responde con denominación, trazabilidad y ficha de producto. La segunda, con la declaración nutricional del elaborador o una analítica.</p>

<h2>Vitaminas del lomo: B1, B2, niacina y B12</h2>
<p>La ficha oficial de lomo embuchado destaca varias vitaminas del grupo B. Entre las cifras de referencia aparecen aproximadamente 0,8 mg de tiamina (B1), 0,25 mg de riboflavina (B2), 12 mg de equivalentes de niacina y 2 µg de vitamina B12 por 100 g.</p>
<p>Estas vitaminas no deben analizarse de forma aislada del resto de la dieta. El objetivo de la tabla es describir la composición del alimento, no convertir cada micronutriente en una promesa sobre sus efectos.</p>

<h2>Minerales: hierro, zinc, fósforo, potasio y sodio</h2>
<p>Además del hierro, la referencia recoge zinc, fósforo, potasio, magnesio y calcio. El dato que más conviene mirar junto a ellos es el <strong>sodio</strong>, porque los productos curados utilizan sal como parte del proceso.</p>
<p>Con 1.470 mg de sodio por 100 g en la referencia, la equivalencia teórica sería de alrededor de 3,7 g de sal por 100 g usando el factor de conversión sodio × 2,5. Sin embargo, la cantidad de sal de un producto comercial puede ser distinta: la cifra válida para comprar y comparar es la de su etiqueta.</p>

<h2>¿El lomo ibérico tiene hidratos de carbono?</h2>
<p>La ficha de lomo embuchado utilizada como referencia registra 0 g de hidratos. No obstante, un producto elaborado puede incorporar ingredientes de adobo o formulación que hagan variar la declaración final. De nuevo, la etiqueta concreta manda.</p>

<h2>Lomo ibérico frente a jamón ibérico y chorizo</h2>
<p>No existe una jerarquía nutricional universal porque las recetas y piezas varían. A grandes rasgos, el lomo curado suele destacar por una <strong>concentración de proteína muy alta</strong>; el chorizo acostumbra a tener más grasa debido a su formulación, y el jamón presenta su propio equilibrio entre magro y grasa según pieza y zona.</p>
<p>Para comparar directamente dos productos consulta <a href="/jamon-iberico-vs-lomo-iberico-diferencias-nutricionales/">jamón ibérico vs lomo ibérico</a>. Y para entender qué es exactamente el corte, <a href="/cana-de-lomo-y-lomo-embuchado-son-lo-mismo-diferencias/">caña de lomo y lomo embuchado</a>.</p>

<h2>Cómo comparar dos lomos en la tienda</h2>
<ol>
<li>Comprueba que comparas la misma unidad: normalmente por 100 g.</li>
<li>Mira proteína, grasa total y grasas saturadas.</li>
<li>Revisa la sal, especialmente si las dos piezas tienen curaciones o formulaciones distintas.</li>
<li>Lee ingredientes y denominación de venta; una tabla nutricional no explica por sí sola la calidad de la materia prima.</li>
<li>No extrapoles una tabla genérica a una pieza concreta cuando el fabricante aporta valores propios.</li>
</ol>

<h2>Preguntas frecuentes</h2>
<h3>¿Cuánta proteína tiene el lomo ibérico?</h3>
<p>Como referencia para lomo embuchado, alrededor de 50 g por 100 g. Un producto concreto puede diferir.</p>
<h3>¿Cuánto hierro tiene?</h3>
<p>La referencia oficial utilizada recoge unos 3,7 mg por 100 g.</p>
<h3>¿El lomo tiene grasa saturada?</h3>
<p>Sí. En la referencia aparecen unos 6,7 g por 100 g, junto a una fracción monoinsaturada mayor.</p>
<h3>¿Tiene vitamina B12?</h3>
<p>Sí. La ficha de referencia recoge aproximadamente 2 µg por 100 g.</p>
<h3>¿El lomo curado tiene mucha sal?</h3>
<p>Es un producto curado y el sodio es un dato relevante. La cantidad varía entre elaboradores; consulta siempre la declaración de sal de la etiqueta concreta.</p>

<h2>Fuentes y criterio</h2>
<ul>
<li><a href="https://www.mapa.gob.es/es/ministerio/servicios/informacion/lomo%20embuchado_tcm30-102854.pdf" rel="noopener">Ministerio de Agricultura, Pesca y Alimentación: ficha de lomo embuchado</a>, con composición nutricional por 100 g.</li>
<li><a href="https://www.fen.org.es/" rel="noopener">Fundación Española de la Nutrición (FEN)</a>, referencia de composición de alimentos y derivados cárnicos.</li>
</ul>
<p><strong>Criterio editorial:</strong> los datos se presentan como referencia de lomo embuchado y no como una analítica universal del lomo ibérico. La composición declarada por el elaborador prevalece para cada producto.</p>
HTML;

$chorizo = <<<'HTML'
<!-- EMDO-SEO-TASK2-CHORIZO-20260901 -->
<p>El <strong>chorizo ibérico</strong> aporta proteínas, grasa, hierro, fósforo, selenio y vitaminas del grupo B, pero su composición puede variar mucho entre elaboradores. A diferencia de una pieza muscular entera como el lomo, el chorizo se formula con carne y grasa en proporciones que no son idénticas en todos los productos, además de sal, pimentón y otros ingredientes.</p>
<p>Para evitar convertir una tabla genérica en una falsa precisión, utilizamos como referencia la ficha de <strong>chorizo</strong> de la Fundación Española de la Nutrición (FEN), basada en un chorizo con alrededor del 32 % de grasa. Un chorizo ibérico comercial puede tener cifras distintas: para una compra concreta debe prevalecer su etiqueta.</p>

<h2>Composición nutricional del chorizo por 100 g</h2>
<table>
<thead><tr><th>Nutriente</th><th>Por 100 g, referencia FEN</th><th>En 50 g, aprox.</th></tr></thead>
<tbody>
<tr><td>Energía</td><td>≈385 kcal</td><td>≈193 kcal</td></tr>
<tr><td>Proteínas</td><td>≈22 g</td><td>≈11 g</td></tr>
<tr><td>Grasa total</td><td>≈32,1 g</td><td>≈16,1 g</td></tr>
<tr><td>Grasa saturada</td><td>≈12,1 g</td><td>≈6,0 g</td></tr>
<tr><td>Grasa monoinsaturada</td><td>≈13,9 g</td><td>≈7,0 g</td></tr>
<tr><td>Grasa poliinsaturada</td><td>≈4,3 g</td><td>≈2,1 g</td></tr>
<tr><td>Hidratos de carbono</td><td>≈2 g</td><td>≈1 g</td></tr>
<tr><td>Hierro</td><td>≈2,4 mg</td><td>≈1,2 mg</td></tr>
<tr><td>Fósforo</td><td>≈160 mg</td><td>≈80 mg</td></tr>
<tr><td>Selenio</td><td>≈21 µg</td><td>≈10,5 µg</td></tr>
<tr><td>Sodio</td><td>≈1.060 mg</td><td>≈530 mg</td></tr>
<tr><td>Vitamina B1</td><td>≈0,3 mg</td><td>≈0,15 mg</td></tr>
<tr><td>Niacina</td><td>≈7,1 mg</td><td>≈3,6 mg</td></tr>
<tr><td>Vitamina B12</td><td>≈1 µg</td><td>≈0,5 µg</td></tr>
</tbody>
</table>
<p><em>Tabla orientativa. La propia FEN señala que el valor energético depende especialmente de la cantidad de grasa. Un chorizo ibérico concreto puede separarse de estos valores.</em></p>

<h2>¿Cuánta proteína tiene el chorizo ibérico?</h2>
<p>La referencia utilizada aporta unos <strong>22 g de proteína por 100 g</strong>. El curado reduce el contenido de agua y concentra los sólidos, pero la formulación del chorizo hace que la cifra final dependa también de cuánto magro y grasa se hayan empleado.</p>
<p>Como esta pregunta sí tiene una intención de búsqueda independiente, mantenemos la guía <a href="/cuanta-proteina-tiene-chorizo-iberico/">cuánta proteína tiene el chorizo ibérico</a>, donde se desarrolla la comparación por ración y con otros curados.</p>

<h2>Cuánto hierro tiene el chorizo</h2>
<p>La ficha FEN recoge aproximadamente <strong>2,4 mg de hierro por 100 g</strong>. Al proceder de carne, incluye hierro hemo, cuya absorción difiere de la del hierro no hemo de los alimentos vegetales.</p>
<p>El hierro es relevante dentro del perfil, pero no necesita una página aislada si el usuario puede resolver en esta misma guía cuánto aporta, qué otros minerales hay y cómo interpretar una ración.</p>

<h2>Qué tipo de grasa tiene el chorizo</h2>
<p>En el chorizo de referencia, la grasa se reparte aproximadamente en:</p>
<ul>
<li><strong>13,9 g de monoinsaturadas</strong> por 100 g;</li>
<li><strong>12,1 g de saturadas</strong>;</li>
<li><strong>4,3 g de poliinsaturadas</strong>.</li>
</ul>
<p>Es decir, la fracción monoinsaturada es ligeramente superior a la saturada en esa composición concreta, pero ambas son relevantes. No sería correcto describir todo chorizo ibérico con exactamente estos porcentajes: la receta y la grasa de partida cambian entre fabricantes y categorías.</p>

<h2>Omega-3 y omega-6 en el chorizo</h2>
<p>La tabla FEN recoge aproximadamente 0,31 g de ácidos grasos omega-3 y 3,76 g de ácido linoleico omega-6 por 100 g en el producto de referencia. De nuevo, son cifras de composición media, no una especificación legal del «chorizo ibérico».</p>
<p>El dato útil para comparar productos comerciales suele estar en grasa total y saturadas, porque son campos que aparecen de forma sistemática en la declaración nutricional. El desglose completo de ácidos grasos no siempre figura en la etiqueta.</p>

<h2>Vitaminas y minerales que aporta</h2>
<p>Además del hierro, la ficha de composición destaca <strong>fósforo y selenio</strong> y recoge zinc, potasio y magnesio. Entre las vitaminas aparecen especialmente tiamina (B1), niacina y vitamina B12.</p>
<p>El chorizo no debe valorarse como si fuera un suplemento de un micronutriente concreto. Es un alimento curado con un conjunto nutricional que incluye al mismo tiempo proteína, grasa y una cantidad importante de sodio.</p>

<h2>La sal es un dato clave al comparar chorizos</h2>
<p>La referencia FEN contiene unos 1.060 mg de sodio por 100 g. La equivalencia teórica con sal sería de aproximadamente 2,7 g por 100 g al multiplicar el sodio por 2,5. La receta real puede dar otra cifra.</p>
<p>En una etiqueta española verás normalmente la cantidad de <strong>sal</strong>, no solo sodio. Si comparas dos chorizos, utiliza ese dato declarado y la misma base de 100 g.</p>

<h2>¿El chorizo ibérico tiene hidratos de carbono?</h2>
<p>Puede tener una pequeña cantidad. La referencia utilizada recoge unos 2 g por 100 g. Los ingredientes y azúcares empleados en la elaboración pueden modificar la cifra. Por eso no conviene afirmar que todos los chorizos tienen exactamente 0 g de hidratos.</p>

<h2>¿Qué cambia por ser ibérico?</h2>
<p>La denominación ibérica describe la materia prima y el marco de calidad que corresponda, pero <strong>no fija una tabla nutricional única</strong>. La genética y la alimentación pueden influir en la grasa de la materia prima; la formulación del embutido, el porcentaje de magro, el tocino, el secado y los ingredientes influyen después en el producto terminado.</p>
<p>Dos chorizos ibéricos pueden tener diferencias nutricionales reales sin que uno de ellos esté «mal». Para comparar, la información más precisa es la declaración nutricional de cada elaborador.</p>

<h2>Chorizo vs salchichón: por qué no son nutricionalmente intercambiables</h2>
<p>Aunque ambos son embutidos curados, cambian formulación y condimentos y pueden cambiar también la proporción de grasa. Si buscas la comparación completa de ingredientes, sabor, curación y nutrición, consulta <a href="/chorizo-vs-salchichon-diferencias-ingredientes-sabor-curacion/">chorizo vs salchichón</a>.</p>
<p>También puedes situarlo frente a otros curados en <a href="/lomo-chorizo-salchichon-iberico-diferencias-como-elegir/">lomo, chorizo y salchichón ibérico: diferencias y cómo elegir</a>.</p>

<h2>Cómo leer la tabla nutricional de un chorizo</h2>
<ol>
<li>Compara siempre por 100 g antes de mirar el tamaño de la ración.</li>
<li>Revisa proteína, grasa total y saturadas.</li>
<li>Comprueba la sal: puede variar de forma significativa.</li>
<li>Lee los hidratos y azúcares declarados si quieres comparar recetas.</li>
<li>No deduzcas composición exacta solo por palabras como «ibérico», «bellota» o por el aspecto de la grasa.</li>
</ol>

<h2>Preguntas frecuentes</h2>
<h3>¿Cuánta proteína tiene el chorizo ibérico?</h3>
<p>Como referencia general de chorizo, unos 22 g por 100 g. La cifra del producto concreto puede ser distinta.</p>
<h3>¿Cuánto hierro aporta?</h3>
<p>La ficha FEN utilizada recoge aproximadamente 2,4 mg por 100 g.</p>
<h3>¿Qué grasa predomina?</h3>
<p>En la referencia, la monoinsaturada es la fracción más alta, muy próxima a la saturada. La formulación real puede cambiar el reparto.</p>
<h3>¿Tiene vitamina B12?</h3>
<p>Sí. La referencia recoge aproximadamente 1 µg por 100 g.</p>
<h3>¿Tiene mucha sal?</h3>
<p>Es un producto curado y la sal es un dato importante. La cantidad exacta debe leerse en la etiqueta del fabricante.</p>

<h2>Fuentes y criterio</h2>
<ul>
<li><a href="https://fen.org.es/MercadoAlimentosFEN/pdfs/chorizo.pdf" rel="noopener">Fundación Española de la Nutrición: ficha de chorizo</a>, composición nutricional de referencia.</li>
<li><a href="https://www.mapa.gob.es/es/alimentacion/legislacion/recopilaciones-legislativas-monograficas/" rel="noopener">Ministerio de Agricultura, Pesca y Alimentación</a>, marco de calidad de carne y derivados cárnicos.</li>
</ul>
<p><strong>Criterio editorial:</strong> las cifras describen un chorizo de referencia y no sustituyen la tabla nutricional de un chorizo ibérico comercial concreto.</p>
HTML;

emdo_task2_update_post(
	13707,
	'nutrientes-aceite-oliva-virgen-extra',
	'Qué nutrientes tiene el aceite de oliva virgen extra: grasas, vitamina E, polifenoles y calorías',
	'Guía completa de los nutrientes del AOVE: calorías, ácido oleico, grasas saturadas, omega-3 y omega-6, vitamina E, polifenoles y colesterol.',
	$aove
);

emdo_task2_update_post(
	13715,
	'verduras-mas-potasio-comparativa',
	'¿Qué verduras tienen más potasio? Comparativa por 100 g y por ración',
	'Comparativa de verduras ricas en potasio: valores por 100 g, efecto de la cocción y por qué una ración real puede cambiar el ranking.',
	$potasio
);

emdo_task2_update_post(
	13716,
	'verduras-mas-calcio-comparativa',
	'¿Qué verduras tienen más calcio? Comparativa y qué cambia en su absorción',
	'Verduras con más calcio por 100 g, comparadas con contexto: espinaca, brócoli, coles y otras hortalizas, biodisponibilidad y efecto de la cocción.',
	$calcio
);

emdo_task2_update_post(
	13752,
	'nutrientes-lomo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
	'Qué nutrientes tiene el lomo ibérico: proteínas, grasas, hierro, vitaminas, minerales y sal',
	'Composición nutricional del lomo ibérico y lomo embuchado: proteína, tipos de grasa, hierro, vitaminas B, minerales, sodio y valores por ración.',
	$lomo
);

emdo_task2_update_post(
	13759,
	'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
	'Qué nutrientes tiene el chorizo ibérico: proteínas, grasas, hierro, vitaminas, minerales y sal',
	'Composición nutricional del chorizo ibérico: proteína, grasas saturadas y monoinsaturadas, hierro, vitaminas B, minerales, sal y valores por ración.',
	$chorizo
);

$redirects = array(
	'cuanta-vitamina-e-tiene-aove' => 'nutrientes-aceite-oliva-virgen-extra',
	'aove-omega-3-omega-6-perfil-grasas' => 'nutrientes-aceite-oliva-virgen-extra',
	'aceite-oliva-tiene-colesterol' => 'nutrientes-aceite-oliva-virgen-extra',
	'cuanta-grasa-saturada-tiene-aove' => 'nutrientes-aceite-oliva-virgen-extra',
	'cuanto-hierro-tiene-lomo-iberico' => 'nutrientes-lomo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
	'grasa-lomo-iberico-saturada-monoinsaturada-poliinsaturada' => 'nutrientes-lomo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
	'cuanto-hierro-tiene-chorizo-iberico' => 'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
	'grasa-chorizo-iberico-saturada-monoinsaturada-poliinsaturada' => 'nutrientes-chorizo-iberico-proteinas-grasas-hierro-vitaminas-minerales',
);

$retire_ids = array(
	13747 => 'cuanta-vitamina-e-tiene-aove',
	13748 => 'aove-omega-3-omega-6-perfil-grasas',
	13749 => 'aceite-oliva-tiene-colesterol',
	13751 => 'cuanta-grasa-saturada-tiene-aove',
	13755 => 'cuanto-hierro-tiene-lomo-iberico',
	13756 => 'grasa-lomo-iberico-saturada-monoinsaturada-poliinsaturada',
	13761 => 'cuanto-hierro-tiene-chorizo-iberico',
	13762 => 'grasa-chorizo-iberico-saturada-monoinsaturada-poliinsaturada',
);

foreach ( $retire_ids as $id => $expected_slug ) {
	$post = get_post( $id );
	if ( ! $post instanceof WP_Post || $post->post_name !== $expected_slug ) {
		throw new RuntimeException( 'Retire guard failed for ID ' . $id );
	}
}

// Rewrite internal links before retiring the source URLs.
global $wpdb;
$changed_posts = array();
foreach ( $redirects as $old_slug => $new_slug ) {
	$like = '%' . $wpdb->esc_like( $old_slug ) . '%';
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_content FROM {$wpdb->posts} WHERE post_content LIKE %s AND post_status NOT IN ('trash','auto-draft','inherit')",
			$like
		)
	);
	foreach ( $rows as $row ) {
		$new_content = str_replace( $old_slug, $new_slug, (string) $row->post_content );
		if ( $new_content !== $row->post_content ) {
			$result = wp_update_post( array( 'ID' => (int) $row->ID, 'post_content' => $new_content ), true );
			if ( is_wp_error( $result ) ) {
				throw new RuntimeException( 'Internal link rewrite failed for post ' . $row->ID );
			}
			$changed_posts[ (int) $row->ID ] = true;
		}
	}
}

foreach ( $retire_ids as $id => $expected_slug ) {
	if ( ! wp_trash_post( $id ) ) {
		throw new RuntimeException( 'Could not trash source post ' . $id );
	}
}

// Hard stop if a published content body still points at one of the retired slugs.
foreach ( array_keys( $redirects ) as $old_slug ) {
	$like      = '%' . $wpdb->esc_like( $old_slug ) . '%';
	$remaining = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_content LIKE %s",
			$like
		)
	);
	if ( $remaining > 0 ) {
		throw new RuntimeException( 'Published internal references remain for ' . $old_slug . ': ' . $remaining );
	}
}

clean_post_cache( 13707 );
clean_post_cache( 13715 );
clean_post_cache( 13716 );
clean_post_cache( 13752 );
clean_post_cache( 13759 );

printf(
	"EMDO_TASK2_OK survivors=5 retired=%d internal_posts_rewritten=%d\n",
	count( $retire_ids ),
	count( $changed_posts )
);
