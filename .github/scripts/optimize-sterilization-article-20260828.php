<?php
/**
 * SEO/content optimization for the published article about sterilisation of vegetable preserves.
 * Keeps the existing slug, featured image, taxonomy and translation relationships untouched.
 */
if ( ! defined( 'ABSPATH' ) ) {
	return;
}

$slug = 'esterilizacion-conservas-vegetales-por-que-duran-despensa';
$post = get_page_by_path( $slug, OBJECT, 'post' );
if ( ! $post instanceof WP_Post ) {
	throw new RuntimeException( 'Target post not found: ' . $slug );
}

$post_id = (int) $post->ID;
$before = array(
	'id'             => $post_id,
	'title'          => get_the_title( $post_id ),
	'excerpt'        => (string) get_post_field( 'post_excerpt', $post_id ),
	'content_length' => strlen( (string) get_post_field( 'post_content', $post_id ) ),
	'permalink'      => get_permalink( $post_id ),
	'thumbnail_id'   => (int) get_post_thumbnail_id( $post_id ),
);

$new_title = 'Esterilización de conservas vegetales: cómo funciona y por qué duran';
$new_excerpt = 'La esterilización de conservas vegetales combina tratamiento térmico, pH, cierre hermético y control del proceso para que puedan conservarse durante meses sin refrigeración antes de abrirse.';
$seo_description = 'Qué es la esterilización de conservas vegetales, qué papel tienen el pH 4,6, el autoclave y el cierre hermético, y por qué duran meses sin frío.';

$find_related = static function ( string $search ) use ( $post_id ): string {
	$ids = get_posts( array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		's'              => $search,
		'posts_per_page' => 5,
		'fields'         => 'ids',
		'post__not_in'   => array( $post_id ),
		'orderby'        => 'relevance',
		'no_found_rows'  => true,
	) );
	foreach ( $ids as $id ) {
		$url = get_permalink( (int) $id );
		if ( is_string( $url ) && '' !== $url ) {
			return $url;
		}
	}
	return '';
};

$link_expiry = $find_related( 'caducan conservas' );
$link_label  = $find_related( 'etiqueta conserva vegetal' );
$link_choose = $find_related( 'elegir conservas vegetales' );

$related_html = '';
$related_items = array();
if ( $link_expiry ) {
	$related_items[] = '<li><a href="' . esc_url( $link_expiry ) . '">¿Caducan las conservas? Cómo interpretar su duración y el consumo preferente</a></li>';
}
if ( $link_label ) {
	$related_items[] = '<li><a href="' . esc_url( $link_label ) . '">Cómo leer la etiqueta de una conserva vegetal</a></li>';
}
if ( $link_choose ) {
	$related_items[] = '<li><a href="' . esc_url( $link_choose ) . '">Cómo elegir buenas conservas vegetales</a></li>';
}
if ( $related_items ) {
	$related_html = '<h2>Para seguir profundizando</h2><ul>' . implode( '', $related_items ) . '</ul>';
}

$content = <<<'HTML'
<p><strong>La esterilización de conservas vegetales es el tratamiento térmico que, unido a un envase herméticamente cerrado y a un proceso industrial controlado, permite que muchas conservas sean estables a temperatura ambiente durante meses o incluso años antes de abrirse.</strong> No consiste simplemente en “hervir un tarro”: la intensidad del tratamiento depende del alimento, su acidez, el tamaño del envase, la densidad del producto y la forma en la que el calor llega a su interior.</p>

<p>En España existe además una diferencia técnica importante alrededor del <strong>pH 4,6</strong>. La normativa de conservas vegetales distingue entre productos con pH final igual o inferior a 4,6 y productos con pH superior a 4,6, para los que el tratamiento térmico exige condiciones más severas y, en las conservas esterilizadas, el uso de autoclaves a presión o sistemas equivalentes validados.</p>

<h2>Qué significa esterilizar una conserva vegetal</h2>

<p>En tecnología alimentaria se habla de <strong>esterilización industrial o técnica</strong> cuando el proceso destruye o inactiva los microorganismos capaces de alterar el alimento en las condiciones normales en las que va a almacenarse. El objetivo práctico no es convertir el producto en un material absolutamente libre de cualquier forma de vida imaginable, sino alcanzar una <strong>estabilidad microbiológica segura para su vida comercial prevista</strong>.</p>

<p>Por eso, cuando hablamos de una conserva vegetal comercial, conviene pensar en un sistema formado por cuatro elementos que trabajan juntos:</p>

<ul>
<li><strong>el alimento y su formulación</strong>, incluida su acidez;</li>
<li><strong>el tratamiento térmico</strong>, diseñado para ese producto concreto;</li>
<li><strong>el cierre hermético</strong>, que evita una recontaminación posterior;</li>
<li><strong>el control del proceso</strong>, con tiempos, temperaturas y registros definidos.</li>
</ul>

<h2>El pH 4,6: por qué la acidez cambia el tratamiento</h2>

<p>Uno de los factores más importantes en una conserva es su acidez. Un alimento ácido no ofrece las mismas condiciones para el crecimiento microbiano que uno poco ácido. En particular, el valor de <strong>pH 4,6</strong> se utiliza como referencia técnica porque por debajo de ese nivel se limita el crecimiento de <em>Clostridium botulinum</em>, una bacteria especialmente relevante en la seguridad de las conservas.</p>

<div class="wp-block-table"><table><thead><tr><th>Tipo de producto</th><th>Referencia de pH</th><th>Qué implica para el proceso</th></tr></thead><tbody><tr><td>Ácido o suficientemente acidificado</td><td>pH final ≤ 4,6</td><td>La acidez reduce determinados riesgos microbiológicos y permite tratamientos térmicos menos exigentes que en alimentos poco ácidos, siempre según un proceso validado.</td></tr><tr><td>Poco ácido</td><td>pH final &gt; 4,6</td><td>Requiere un control térmico más severo. La normativa española contempla autoclaves a presión o sistemas equivalentes para las conservas esterilizadas de este grupo.</td></tr></tbody></table></div>

<p>Esto explica por qué no existe una única temperatura ni un único tiempo de esterilización válido para todas las verduras. Un pimiento acidificado, un tomate, una legumbre cocida y una conserva de alcachofa pueden necesitar procesos diferentes aunque todos terminen en un tarro aparentemente parecido.</p>

<h2>Cómo se esterilizan las conservas vegetales en la industria</h2>

<p>El proceso exacto cambia según el producto y la fábrica, pero la lógica general suele seguir una secuencia parecida:</p>

<ol>
<li><strong>Selección y preparación.</strong> La materia prima se limpia, clasifica y prepara con el corte o tratamiento previo que corresponda.</li>
<li><strong>Llenado del envase.</strong> Se introduce el vegetal y, cuando procede, su líquido de cobertura o formulación.</li>
<li><strong>Cierre hermético.</strong> El recipiente se cierra de forma que pueda resistir el tratamiento y mantener la barrera frente a una contaminación posterior.</li>
<li><strong>Tratamiento térmico.</strong> El producto ya envasado se somete al ciclo definido para alcanzar la letalidad microbiológica necesaria en el punto más desfavorable del alimento.</li>
<li><strong>Enfriamiento.</strong> Una vez alcanzado el tratamiento previsto, se enfría el envase para detener la sobrecocción y proteger mejor textura, color y calidad.</li>
<li><strong>Verificación y trazabilidad.</strong> La industria controla el cierre, el ciclo térmico, los lotes y otros parámetros del proceso.</li>
</ol>

<p>La parte decisiva es que el tratamiento se diseña para el <strong>interior real del producto</strong>, no únicamente para la temperatura del agua o del vapor que rodea el envase. La penetración del calor cambia con el tamaño del tarro, la viscosidad, el tipo de alimento y la proporción de líquido.</p>

<h2>Qué es un autoclave y cuándo se utiliza</h2>

<p>Un <strong>autoclave para conservas</strong> es un equipo capaz de realizar tratamientos térmicos controlados bajo presión. Esa presión permite trabajar en condiciones que no se consiguen con una simple olla abierta y controlar de forma reproducible el ciclo que recibe el alimento envasado.</p>

<p>En la reglamentación técnico-sanitaria española de conservas vegetales se establece que, para las conservas esterilizadas con <strong>pH final superior a 4,6</strong>, la instalación de esterilización debe consistir en autoclaves a presión o en otro sistema capaz de conseguir una esterilización industrial o técnica equivalente. Para pH final igual o inferior a 4,6, la norma contempla instalaciones a presión atmosférica.</p>

<p><strong>Esto no debe interpretarse como una receta doméstica.</strong> El pH, el producto, el envase y la curva de penetración del calor deben evaluarse conjuntamente. Copiar un tiempo o una temperatura de otra conserva no permite concluir que un alimento sea seguro.</p>

<h2>Esterilización y pasteurización no son lo mismo</h2>

<p>Ambas técnicas utilizan calor, pero persiguen niveles de control distintos. La <strong>pasteurización</strong> reduce de forma importante los microorganismos sensibles al calor, mientras que la <strong>esterilización industrial</strong> busca una estabilidad microbiológica que permita, en los productos diseñados para ello, conservar el envase cerrado a temperatura ambiente.</p>

<p>Por eso hay productos pasteurizados que necesitan refrigeración y conservas esterilizadas que pueden permanecer en la despensa. Tampoco todo producto vegetal en tarro es necesariamente una “conserva esterilizada”: existen encurtidos, semiconservas, productos refrigerados y formulaciones acidificadas con procesos diferentes.</p>

<h2>Por qué una conserva esterilizada puede durar tanto sin frío</h2>

<p>La duración no se debe a un solo factor. Durante el tratamiento térmico se controla la carga microbiana hasta el nivel establecido para el producto y, después, el <strong>envase hermético impide que nuevos microorganismos entren desde el exterior</strong>. Mientras el cierre permanezca íntegro y el producto se almacene según las indicaciones del fabricante, ese sistema puede mantenerse estable durante mucho tiempo.</p>

<p>Eso tampoco significa que una conserva sea eterna. La seguridad y la calidad son conceptos relacionados pero diferentes: con el paso del tiempo pueden cambiar el color, el aroma o la textura incluso cuando el envase continúa cerrado. La fecha indicada por el productor y las condiciones de almacenamiento siguen siendo la referencia práctica.</p>

<h2>Qué ocurre cuando se abre el tarro</h2>

<p>Al abrir una conserva se rompe la barrera que la mantenía aislada del entorno. Entran aire y microorganismos y pueden producirse contaminaciones a través de cubiertos, manos o superficies. Por eso una conserva que era estable en la despensa puede necesitar <strong>refrigeración después de abrirse</strong>.</p>

<p>La recomendación correcta es seguir siempre la etiqueta del producto: allí debe indicarse cómo conservarlo una vez abierto y, cuando corresponda, en qué plazo conviene consumirlo.</p>

<h2>¿La esterilización destruye todos los nutrientes?</h2>

<p>No. El calor puede reducir algunos compuestos sensibles, especialmente determinadas vitaminas, y también modifica textura, pigmentos y aromas. Sin embargo, minerales, fibra y muchos otros componentes permanecen en gran medida, y algunos compuestos pueden incluso hacerse más disponibles después del tratamiento térmico.</p>

<p>En una conserva de calidad, el objetivo tecnológico es encontrar el equilibrio entre <strong>seguridad, estabilidad y calidad sensorial</strong>. Aplicar más calor del necesario no es una ventaja: puede deteriorar el producto sin aportar un beneficio adicional si el proceso ya está correctamente validado.</p>

<h2>Qué señales indican que una conserva no debe consumirse</h2>

<p>Un tratamiento correcto no compensa un envase que ha perdido su integridad. Conviene descartar conservas con <strong>tapas abombadas, fugas, pérdida evidente de vacío, grietas, corrosión importante o deformaciones severas en la zona de cierre</strong>. No se debe probar una conserva sospechosa “para comprobar” si está bien.</p>

<p>Las autoridades de seguridad alimentaria recomiendan almacenar las conservas industriales en un lugar fresco y seco, protegidas de la luz intensa y de cambios bruscos de temperatura.</p>

<h2>Conserva industrial y conserva casera: una diferencia importante</h2>

<p>La explicación anterior describe la lógica de las <strong>conservas comerciales</strong>. En industria se trabaja con equipos controlados, procesos definidos para cada producto, registros de temperatura y tiempo, verificación del cierre y sistemas de trazabilidad.</p>

<p>Una conserva casera no debe intentar reproducir un tratamiento industrial copiando una cifra de temperatura o unos minutos encontrados en internet. Esto es especialmente importante en alimentos poco ácidos, donde una elaboración incorrecta puede implicar riesgos graves. Para conservas domésticas deben utilizarse procedimientos específicamente validados por organismos competentes.</p>

<h2>Preguntas frecuentes sobre la esterilización de conservas vegetales</h2>

<h3>¿A qué temperatura se esterilizan las conservas vegetales?</h3>
<p>No existe una temperatura única. Depende del pH, el alimento, el envase, su tamaño, la densidad y el proceso diseñado. En alimentos poco ácidos se emplean tratamientos a presión capaces de superar las condiciones de ebullición a presión atmosférica, pero la seguridad depende del ciclo completo validado, no de una cifra aislada.</p>

<h3>¿Todas las conservas vegetales se esterilizan?</h3>
<p>No necesariamente. Algunas formulaciones ácidas o acidificadas pueden emplear otros tratamientos térmicos, y existen semiconservas o productos refrigerados. La tecnología utilizada depende de cómo esté diseñado el alimento y de las condiciones de conservación previstas.</p>

<h3>¿Por qué el pH 4,6 es tan importante?</h3>
<p>Porque marca una referencia microbiológica fundamental en el control de <em>Clostridium botulinum</em>. Por encima de ese nivel, los alimentos poco ácidos requieren un proceso térmico especialmente controlado para garantizar su estabilidad y seguridad.</p>

<h3>¿Una conserva esterilizada necesita conservantes?</h3>
<p>No necesariamente. El tratamiento térmico y el cierre hermético pueden ser suficientes para conseguir estabilidad en muchos productos. Otras recetas incorporan sal, ácidos u otros ingredientes por razones tecnológicas o sensoriales. Hay que leer la lista de ingredientes de cada producto.</p>

<h3>¿Por qué las conservas duran meses o años?</h3>
<p>Porque el proceso térmico controla los microorganismos relevantes y el cierre hermético evita la recontaminación mientras el envase permanece intacto. La duración concreta la determina el fabricante para cada producto y se refleja en su etiquetado.</p>

<h2>Fuentes técnicas y normativa</h2>

<ul>
<li><a href="https://www.boe.es/buscar/act.php?id=BOE-A-1978-25634" rel="noopener">Real Decreto 2420/1978: Reglamentación Técnico-Sanitaria para la elaboración y venta de conservas vegetales (BOE)</a>.</li>
<li><a href="https://www.boe.es/buscar/act.php?id=BOE-A-1967-16485" rel="noopener">Código Alimentario Español: conservación por el calor y esterilización industrial o técnica (BOE)</a>.</li>
<li><a href="https://prtr-es.miteco.gob.es/Data/images/Gu%C3%ADa%20MTD%20en%20Espa%C3%B1a%20Transformados%20Vegetales-1F078444C914B509.pdf" rel="noopener">Guía de Mejores Técnicas Disponibles del sector de transformados vegetales (MITECO)</a>.</li>
<li><a href="https://acsa.gencat.cat/es/seguretat_alimentaria/consells_sobre_seguretat_alimentaria/consells-generals/bon-us-de-les-conserves/conserves-industrials/" rel="noopener">Almacenamiento y buen uso de las conservas industriales (Agencia Catalana de Seguridad Alimentaria)</a>.</li>
</ul>
HTML;

$content .= $related_html;

$update = wp_update_post( wp_slash( array(
	'ID'           => $post_id,
	'post_title'   => $new_title,
	'post_excerpt' => $new_excerpt,
	'post_content' => $content,
) ), true );
if ( is_wp_error( $update ) ) {
	throw new RuntimeException( $update->get_error_message() );
}

// Set common SEO metadata only when the corresponding SEO plugin/key is present.
$seo_updates = array();
if ( defined( 'WPSEO_VERSION' ) || metadata_exists( 'post', $post_id, '_yoast_wpseo_title' ) || metadata_exists( 'post', $post_id, '_yoast_wpseo_metadesc' ) ) {
	update_post_meta( $post_id, '_yoast_wpseo_title', $new_title );
	update_post_meta( $post_id, '_yoast_wpseo_metadesc', $seo_description );
	update_post_meta( $post_id, '_yoast_wpseo_focuskw', 'esterilización de conservas vegetales' );
	$seo_updates[] = 'yoast';
}
if ( defined( 'RANK_MATH_VERSION' ) || metadata_exists( 'post', $post_id, 'rank_math_title' ) || metadata_exists( 'post', $post_id, 'rank_math_description' ) ) {
	update_post_meta( $post_id, 'rank_math_title', $new_title );
	update_post_meta( $post_id, 'rank_math_description', $seo_description );
	update_post_meta( $post_id, 'rank_math_focus_keyword', 'esterilización de conservas vegetales,esterilización conservas,autoclave conservas,pH 4,6 conservas' );
	$seo_updates[] = 'rank-math';
}

clean_post_cache( $post_id );

after:
$after = array(
	'id'             => $post_id,
	'title'          => get_the_title( $post_id ),
	'excerpt'        => (string) get_post_field( 'post_excerpt', $post_id ),
	'content_length' => strlen( (string) get_post_field( 'post_content', $post_id ) ),
	'permalink'      => get_permalink( $post_id ),
	'thumbnail_id'   => (int) get_post_thumbnail_id( $post_id ),
	'modified_gmt'   => get_post_field( 'post_modified_gmt', $post_id ),
);

$result = array(
	'ok'              => true,
	'slug'            => $slug,
	'before'          => $before,
	'after'           => $after,
	'seo_updates'     => $seo_updates,
	'internal_links'  => array_values( array_filter( array( $link_expiry, $link_label, $link_choose ) ) ),
	'checks'           => array(
		'slug_unchanged'     => $slug === get_post_field( 'post_name', $post_id ),
		'thumbnail_unchanged'=> $before['thumbnail_id'] === $after['thumbnail_id'],
		'keyword_in_title'   => false !== stripos( $after['title'], 'Esterilización de conservas vegetales' ),
		'has_ph46'           => false !== strpos( (string) get_post_field( 'post_content', $post_id ), 'pH 4,6' ),
		'has_boe'            => false !== strpos( (string) get_post_field( 'post_content', $post_id ), 'boe.es' ),
		'has_faq'            => false !== strpos( (string) get_post_field( 'post_content', $post_id ), 'Preguntas frecuentes sobre la esterilización' ),
	),
);

echo wp_json_encode( $result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
