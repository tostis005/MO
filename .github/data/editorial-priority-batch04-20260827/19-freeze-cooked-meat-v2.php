<?php
$a=require __DIR__.'/19-freeze-cooked-meat-base.php';
$es=<<<'HTML'
<h2>Qué cambia en la textura después de congelar</h2>
<p>La congelación forma cristales de hielo a partir del agua del alimento. Cuando la carne se descongela, parte de esa humedad puede salir del tejido y aparecer como jugo en el recipiente. Cuanto más lento sea el proceso de congelación, más aire quede en el envase y más ciclos de congelación y descongelación acumule el plato, mayor suele ser la pérdida de calidad.</p>
<p>Por eso funcionan especialmente bien los platos con salsa, caldo o grasa suficiente para proteger la sensación de jugosidad. En un filete a la plancha, en cambio, conviene evitar un recalentamiento largo: descongela de forma segura y calienta solo lo necesario para no seguir secando la carne.</p>
<h2>¿Mejor congelar la carne sola o el plato completo?</h2>
<p>Depende de la receta. Un estofado, ragú o guiso suele congelar bien como plato terminado. En preparaciones con patata, pasta o determinadas verduras, la carne puede mantener mejor calidad que la guarnición, cuya textura puede cambiar más. Si sabes que vas a congelar desde el principio, a veces interesa guardar la carne y la salsa juntas y preparar la guarnición fresca el día de consumo.</p>
<p>También es útil separar raciones individuales. Descongelar una bandeja grande para volver a guardar la mitad genera más manipulación y empeora la calidad. Congelar en porciones permite sacar únicamente lo que realmente vas a comer.</p>
<h2>Cómo evitar la quemadura por congelación</h2>
<p>La quemadura por congelación aparece cuando la superficie pierde humedad por contacto prolongado con aire frío y seco. No suele convertir automáticamente el alimento en inseguro, pero sí empeora color, sabor y textura. Utiliza envases que cierren bien, elimina el exceso de aire y no dejes grandes espacios vacíos alrededor de una ración pequeña.</p>
<p>Si utilizas bolsas, extiéndelas en una capa relativamente fina: además de ocupar menos espacio, la carne se congela y descongela de manera más uniforme.</p>
HTML;
$en=<<<'HTML'
<h2>How texture changes after freezing</h2>
<p>Freezing turns part of the food's water into ice crystals. During thawing, some of that moisture can leave the muscle structure and appear as liquid in the container. Quality loss tends to increase when freezing is slow, packaging contains a lot of air or the dish goes through repeated freeze-thaw cycles.</p>
<p>This is why meat in sauce, gravy or broth often performs particularly well. A grilled steak is less forgiving: thaw it safely and reheat only as much as necessary so that a second long cooking stage does not dry it further.</p>
<h2>Freeze the meat alone or the whole dish?</h2>
<p>It depends on the recipe. Stews, braises and ragù usually freeze well as complete dishes. Meals containing potatoes, pasta or certain vegetables can be different: the meat may keep its quality better than the side dish. If you know in advance that a meal will be frozen, it can make sense to freeze meat and sauce together and prepare the more delicate accompaniment fresh on the day.</p>
<p>Individual portions are also useful. Thawing one large tray only to store half of it again creates extra handling and can reduce quality. Portioning before freezing lets you remove exactly what you plan to eat.</p>
<h2>How to reduce freezer burn</h2>
<p>Freezer burn occurs when exposed surfaces lose moisture in the dry freezer environment. It does not automatically make food unsafe, but it can seriously damage colour, flavour and texture. Use close-fitting freezer containers or bags, remove unnecessary air and avoid leaving a tiny serving in a very large container.</p>
<p>When using bags, freezing them in a relatively flat layer also helps the food freeze and thaw more evenly while saving space.</p>
HTML;
$a['content']=str_replace('<h2>Resumen práctico</h2>',$es.'<h2>Resumen práctico</h2>',$a['content']);
$a['en_content']=str_replace('<h2>Quick summary</h2>',$en.'<h2>Quick summary</h2>',$a['en_content']);
return $a;
