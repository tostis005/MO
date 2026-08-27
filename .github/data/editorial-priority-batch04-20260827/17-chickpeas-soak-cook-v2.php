<?php
$a=require __DIR__.'/17-chickpeas-soak-cook-base.php';
$extra_es=<<<'HTML'
<h2>¿Existe un remojo rápido si se te ha olvidado hacerlo la noche anterior?</h2>
<p>Sí. Como alternativa puntual, puedes cubrir los garbanzos con abundante agua, llevarlos a ebullición durante unos minutos, apagar el fuego y dejarlos reposar tapados alrededor de una hora antes de escurrirlos y empezar la cocción definitiva. No reproduce exactamente un remojo nocturno largo, pero acelera mucho la hidratación cuando no has podido planificar con antelación.</p>
<p>Si tienes tiempo, el remojo tradicional sigue siendo más cómodo porque hidrata el grano de forma gradual y permite organizar mejor los tiempos. El método rápido debe entenderse como un recurso práctico, no como una obligación culinaria.</p>
<h2>Cómo adaptar el tiempo si los garbanzos son viejos o el agua es dura</h2>
<p>Dos paquetes de garbanzos aparentemente iguales pueden necesitar tiempos distintos. Las legumbres almacenadas durante mucho tiempo suelen perder capacidad de hidratación y pueden permanecer firmes incluso después del tiempo indicado en una receta. El agua con una concentración elevada de calcio y magnesio también puede retrasar el ablandamiento.</p>
<p>Si después del tiempo orientativo siguen duros, no subas el fuego de forma agresiva: mantén una cocción estable y añade tiempo. En olla rápida, vuelve a cerrar y programa tandas cortas de unos minutos. En cazuela, continúa a hervor suave y comprueba periódicamente. Si el problema se repite con frecuencia, una cantidad muy pequeña de bicarbonato puede ayudar, pero no debe convertirse en la solución para compensar garbanzos de mala calidad o excesivamente antiguos.</p>
<h2>Cocer según el uso final: ensalada, guiso o hummus</h2>
<p>El punto perfecto tampoco es el mismo para todas las recetas. Para una ensalada interesa que el garbanzo esté completamente tierno pero conserve bien la forma. En un cocido o un guiso puede admitir algo más de cocción porque seguirá unos minutos dentro del caldo. Para hummus conviene llevarlo a un punto claramente más blando y cremoso: un garbanzo que parece perfecto para ensalada puede producir un puré menos fino.</p>
<p>Por eso el mejor “temporizador” sigue siendo probar varios granos. Si unos están tiernos y otros duros, el lote aún necesita tiempo para igualarse.</p>
<h2>Cómo cocinar una tanda grande y ahorrar tiempo</h2>
<p>Una estrategia muy útil es cocer más cantidad de la necesaria. Una vez fríos, reparte los garbanzos en porciones con un poco de su líquido de cocción y congélalos. Así puedes disponer de legumbre cocida para ensaladas, cremas o guisos sin repetir cada semana el remojo y la cocción larga.</p>
<p>También puedes congelar garbanzos ya remojados y escurridos antes de cocerlos. Congélalos extendidos o en porciones para que no formen un bloque enorme y utilízalos directamente en una futura cocción, ajustando unos minutos si fuera necesario.</p>
HTML;
$extra_en=<<<'HTML'
<h2>Is there a quick-soak method if you forgot the night before?</h2>
<p>Yes. As an occasional shortcut, cover the chickpeas generously with water, bring them to the boil for a few minutes, switch off the heat and leave them covered for about an hour. Drain them and then begin the normal cooking process. This does not reproduce a long overnight soak exactly, but it accelerates hydration when planning ahead was not possible.</p>
<p>If you have time, a conventional overnight soak remains the easiest method because hydration happens gradually and meal preparation is more predictable. Think of quick soaking as a useful fallback rather than a superior rule.</p>
<h2>How to adjust for old chickpeas or hard water</h2>
<p>Two packets that look identical can behave very differently. Pulses stored for a long time often hydrate less readily and may remain firm after a recipe's stated cooking time. Hard water, particularly water rich in calcium and magnesium, can also slow softening.</p>
<p>If chickpeas are still hard after the expected time, do not simply turn the heat up aggressively. Keep a steady simmer and give them more time. With a pressure cooker, close it again and add short extra intervals. In a conventional pot, continue gently and test regularly. A very small amount of bicarbonate of soda can sometimes help with persistent hardness, but it should not be used to disguise very old or poor-quality pulses.</p>
<h2>Cook to the final use: salad, stew or hummus</h2>
<p>The ideal texture is not the same for every recipe. For salad, chickpeas should be fully tender while holding their shape. In a stew they can be a little softer because they will continue cooking in the broth. For hummus, taking them to a distinctly soft, creamy stage usually produces a smoother purée.</p>
<p>This is why tasting several chickpeas is more useful than relying on the clock alone. If some are tender while others still have a firm centre, the batch needs more time to become even.</p>
<h2>Batch cooking and freezing to save time</h2>
<p>A practical routine is to cook more chickpeas than you need. Once cool, divide them into portions with a little cooking liquid and freeze them. You then have ready-cooked pulses for salads, soups, hummus or stews without repeating the full soaking and cooking process every week.</p>
<p>You can also freeze soaked, drained chickpeas before cooking. Freeze them in manageable portions rather than one large block, then use them in a future recipe and allow a little extra cooking time if necessary.</p>
HTML;
$a['content']=str_replace('<h2>Resumen rápido</h2>',$extra_es.'<h2>Resumen rápido</h2>',$a['content']);
$a['en_content']=str_replace('<h2>Quick summary</h2>',$extra_en.'<h2>Quick summary</h2>',$a['en_content']);
return $a;
