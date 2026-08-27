<?php
$a=require __DIR__.'/18-ham-weight-choice-base.php';
$es=<<<'HTML'
<h2>El rendimiento cambia la percepción del precio</h2>
<p>Comparar dos piezas únicamente por el precio total puede llevar a conclusiones engañosas. Un jamón más pesado cuesta más, pero también reparte el coste entre más raciones; al mismo tiempo, dos piezas del mismo peso pueden ofrecer rendimientos diferentes por su conformación, nivel de grasa o proporción de hueso. Por eso, si estás decidiendo entre varios tramos, piensa en <strong>cuánto producto vas a consumir realmente</strong> y no solo en los kilos indicados en la etiqueta.</p>
<p>En una compra para casa, el mejor valor no es necesariamente el menor precio por kilo de pieza. Una pieza algo más pequeña que se termina en buenas condiciones puede ser una compra más satisfactoria que otra enorme cuyas últimas lonchas llegan secas por un consumo demasiado lento.</p>
<h2>Para Navidad o una celebración, calcula primero las raciones</h2>
<p>Cuando el jamón se compra para una comida familiar, Navidad o un evento, el criterio cambia. En ese caso interesa estimar cuántas personas lo tomarán y si será un aperitivo más o uno de los protagonistas de la mesa. Para una degustación con otros entrantes, la cantidad por persona será menor que cuando el jamón ocupa el centro del aperitivo.</p>
<p>Si el evento es puntual y no vas a continuar cortando la pieza con frecuencia después, puede ser más eficiente combinar una pieza de tamaño razonable con sobres loncheados. Así aseguras servicio suficiente sin comprar varios kilos adicionales solo por miedo a quedarte corto.</p>
<h2>Antes de elegir peso, responde estas cuatro preguntas</h2>
<ol>
<li>¿Cuántas personas comen jamón habitualmente en casa?</li>
<li>¿Lo consumís a diario, varias veces por semana o solo en ocasiones?</li>
<li>¿Queréis cortar una pieza entera o priorizáis comodidad?</li>
<li>¿El presupuesto debe concentrarse en más cantidad o en subir de categoría?</li>
</ol>
<p>Con esas respuestas, el peso deja de ser una elección abstracta. Para muchos hogares, <strong>comprar la categoría que realmente desean en un tamaño que puedan terminar bien</strong> es más inteligente que sacrificar calidad por llevarse una pieza mayor.</p>
HTML;
$en=<<<'HTML'
<h2>Yield changes how you should think about price</h2>
<p>Comparing two hams only by their total price can be misleading. A heavier piece costs more but spreads that cost across more servings; at the same time, two hams of identical weight can produce different edible yields because of shape, fat cover and bone proportion. When choosing between weight bands, think about <strong>how much ham you will actually eat</strong>, not just the kilograms printed on the label.</p>
<p>For a household, the best value is not always the lowest price per kilogram of whole piece. A slightly smaller ham that is finished in excellent condition can be a better purchase than an oversized one whose final slices become dry because consumption is too slow.</p>
<h2>For Christmas or a celebration, calculate servings first</h2>
<p>If the ham is being bought for Christmas, a family gathering or an event, estimate the number of diners and decide whether ham will be one appetiser among many or the centrepiece. A tasting portion alongside several starters requires much less per person than a table where ham is the main aperitif.</p>
<p>If the event is occasional and you will not keep carving frequently afterwards, combining a sensibly sized whole piece with pre-sliced packs can be efficient. It provides service capacity without buying several extra kilograms simply as insurance.</p>
<h2>Four questions to answer before choosing a weight</h2>
<ol>
<li>How many people regularly eat ham at home?</li>
<li>Do you eat it daily, several times a week or only occasionally?</li>
<li>Do you want the ritual of carving a whole piece or maximum convenience?</li>
<li>Should your budget buy more quantity or a higher production category?</li>
</ol>
<p>Once those questions are answered, weight becomes a practical decision rather than an abstract number. For many households, <strong>buying the category they genuinely want in a size they can finish well</strong> makes more sense than sacrificing quality merely to take home a larger ham.</p>
HTML;
$a['content']=str_replace('<h2>La pregunta correcta</h2>',$es.'<h2>La pregunta correcta</h2>',$a['content']);
$a['en_content']=str_replace('<h2>The right question</h2>',$en.'<h2>The right question</h2>',$a['en_content']);
return $a;
