<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$extras = array(
'duck-02' => <<<'HTML'
<h2>Qué pedir al vendedor cuando la ficha no es suficiente</h2>
<p>Si la información comercial no aclara el formato, pregunta si el producto es fresco, micuit o conserva; si necesita refrigeración antes de abrir; qué peso neto tiene; y cuántos días recomienda el elaborador después de abierto. Estas cuatro respuestas resuelven problemas prácticos que una descripción genérica no siempre cubre.</p>
<p>También merece la pena confirmar si un producto vendido como surtido incluye realmente categorías distintas o varias variantes de una misma preparación. Para un regalo o una cata, la variedad de texturas suele aportar más valor que acumular sabores aromatizados de una sola familia.</p>
HTML,
'duck-03' => <<<'HTML'
<h2>Cómo cambia el magret según su tamaño y forma</h2>
<p>Un magret ancho y grueso tiene más inercia térmica que una pieza plana. Esto afecta a la elección de técnica: los muy gruesos se benefician de un acabado en horno, mientras que los pequeños pueden completarse en sartén. La cantidad de grasa visible también varía y determina cuánto tiempo necesitará la piel para quedar fina y dorada.</p>

<h2>Qué preguntar si compras en carnicería</h2>
<p>Pide el peso aproximado por pieza, si está fresco o descongelado y la fecha recomendada de consumo. Si vas a cocinar para varias personas, intenta elegir magrets de tamaño parecido para que alcancen el punto al mismo tiempo. Esta homogeneidad facilita mucho trabajar por tandas.</p>

<h2>Qué aspecto tiene una pieza bien preparada para cocinar</h2>
<p>Antes de llevarla a la sartén, la piel debe estar seca y libre de plumas residuales; la grasa puede marcarse superficialmente sin llegar a la carne. Si la superficie de la carne tiene pequeñas membranas, recórtalas con cuidado. No hace falta retirar la capa grasa que define al corte.</p>

<h2>Magret frío en ensalada o bocadillo</h2>
<p>También puede cocinarse, enfriarse y utilizarse en lonchas finas para una ensalada o un bocadillo. En ese caso conviene mantener la piel crujiente aparte o recalentarla brevemente, porque el frigorífico la ablanda. Mostaza, hojas amargas, pera y encurtidos ayudan a equilibrar la grasa.</p>
HTML,
'duck-10' => <<<'HTML'
<h2>Prueba comparativa: qué método deja mejor textura</h2>
<p>Si tienes varias piezas, puedes terminar una con grill y otra en air fryer para comprobar cómo responde tu equipo. El grill produce un tostado muy rápido y concentrado; la air fryer seca de forma más uniforme alrededor de la pieza. En hornos con ventilador potente, la diferencia puede ser pequeña. El mejor método es el que te permite controlar color sin resecar el interior.</p>

<h2>Qué ocurre con la piel durante el dorado</h2>
<p>La superficie contiene grasa y humedad. Primero el calor evapora agua; después la grasa se funde y la piel empieza a tostarse. Si hay demasiada humedad, el proceso se queda en una fase de cocción al vapor. Si el calor es excesivo desde el inicio, algunas zonas se queman antes de que otras se sequen. Por eso secar y distribuir bien las piezas importa tanto como la temperatura.</p>

<h2>Cómo trabajar con dos bandejas</h2>
<p>Para ocho o más muslos, utiliza dos bandejas y deja espacio entre ellos. Durante el calentamiento puedes rotar posiciones; para el acabado, es mejor pasar una bandeja cada vez bajo el grill. Intentar dorar dos alturas simultáneamente suele producir una bandeja perfecta y otra pálida.</p>

<h2>Acabado crujiente sin horno</h2>
<p>Si no tienes horno, una sartén pesada es suficiente. Calienta a fuego medio, coloca la piel hacia abajo y presiona suavemente con una espátula. Retira grasa a medida que salga. Cuando la superficie esté crujiente, gira solo para calentar la cara inferior si es necesario.</p>

<h2>Cómo servir varias piezas sin perder el crujiente</h2>
<p>Cuando termines la primera tanda, colócala sobre una rejilla en un horno muy bajo o en una zona templada, siempre sin tapar. No amontones los muslos. El contacto entre pieles y el vapor atrapado destruyen rápidamente la textura.</p>
HTML,
'duck-11' => <<<'HTML'
<h2>Cómo preparar el servicio para varios invitados</h2>
<p>En una comida de ocho o diez personas, corta primero solo la mitad del foie. Mantén el resto refrigerado y prepara una segunda tanda cuando la primera esté casi terminada. Ten un vaso con agua caliente para el cuchillo y un paño limpio para secarlo después de cada corte. Esta pequeña mise en place permite trabajar deprisa sin manipular en exceso el producto.</p>

<h2>Qué hacer si el foie llega demasiado blando</h2>
<p>Devuélvelo al frigorífico durante unos minutos antes de intentar cortar. Manipular una pieza muy blanda produce bordes irregulares y deja grasa en el plato. No intentes solucionar el problema congelándolo rápidamente: un frío extremo puede cambiar la textura exterior y dificultar un atemperado uniforme.</p>

<h2>Cómo servirlo en formato buffet</h2>
<p>En un buffet no conviene exponer una terrina completa durante horas. Sirve porciones pequeñas sobre una bandeja fresca y repón desde el frigorífico. Coloca utensilios propios para el foie y evita que se mezclen con patés o quesos. Si hay pan, mantenlo en una cesta separada para conservar el crujiente.</p>

<h2>Presentación minimalista frente a tabla compartida</h2>
<p>En plato individual, una porción limpia, dos tostadas y un único elemento de contraste son suficientes. En una tabla compartida puedes ofrecer más pan y fruta, pero mantén el foie separado de productos muy aromáticos. La presentación debe facilitar que cada persona controle su bocado, no obligarla a comer varios sabores juntos.</p>
HTML,
);

foreach ( $extras as $key => $html ) {
	$ids = get_posts( array(
		'post_type'=>'post','post_status'=>'publish','posts_per_page'=>1,'fields'=>'ids',
		'meta_key'=>'_emdo_seo_landing_key','meta_value'=>$key,'emdo_include_hidden_blog_islands'=>true,
	) );
	if ( empty( $ids ) ) { throw new Exception( 'Missing ' . $key ); }
	$id=(int)$ids[0];
	$content=(string)get_post_field('post_content',$id);
	$marker='<!-- emdo-hub-completion-20260928:' . $key . ' -->';
	if(false===strpos($content,$marker)){
		$result=wp_update_post(wp_slash(array('ID'=>$id,'post_content'=>$content."\n".$marker."\n".$html)),true);
		if(is_wp_error($result)) throw new Exception($key.': '.$result->get_error_message());
	}
	update_post_meta($id,'_emdo_duck_intent_separation','20260928-v3-complete');
	clean_post_cache($id);
	echo $key.'|'.$id.PHP_EOL;
}
echo "HUB_COMPLETION_OK\n";
