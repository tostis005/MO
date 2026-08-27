<?php
$a=require __DIR__.'/20-fresh-vs-frozen-vegetables-base.php';
$es=<<<'HTML'
<h2>Temporada, distancia y tiempo: tres variables que importan</h2>
<p>Cuando una verdura fresca está en temporada, procede de una cadena corta y se consume pocos días después de cosecharse, suele ofrecer una combinación excelente de sabor y textura. Pero “fresco” no garantiza automáticamente cercanía ni rapidez. Un producto puede llegar impecable visualmente después de varios días de transporte y almacenamiento.</p>
<p>La congelación cambia esa lógica: permite capturar el producto en un momento concreto y conservarlo durante meses. Por eso es especialmente útil para verduras cuya temporada es corta o cuando la compra fresca acabaría olvidada en el cajón de la nevera.</p>
<h2>El desperdicio también forma parte de la comparación</h2>
<p>Una verdura fresca que termina en la basura no aporta ningún beneficio nutricional. Para hogares pequeños, personas que cocinan de forma irregular o recetas que requieren solo una pequeña cantidad, poder sacar 150 o 200 gramos de una bolsa congelada y guardar el resto puede ser una ventaja real.</p>
<p>En cambio, si una familia consume rápidamente una caja de verduras frescas de temporada, la ventaja del congelado en este punto prácticamente desaparece. La elección debe adaptarse al patrón real de consumo.</p>
<h2>¿Las verduras congeladas llevan conservantes?</h2>
<p>No los necesitan necesariamente para conservarse: la propia congelación es el método de conservación. En una bolsa de brócoli, espinacas, guisantes o judías verdes simples, la lista de ingredientes puede contener únicamente la verdura. Otra cosa son los preparados congelados con salsas, mantequilla, queso, sal u otros ingredientes añadidos.</p>
<p>Por eso, si buscas comparar “fresco frente a congelado” desde el punto de vista nutricional, compara una verdura fresca con <strong>la misma verdura congelada sin preparar</strong>, no con un plato listo para calentar.</p>
<h2>Una estrategia práctica para casa</h2>
<p>Prioriza fresco para ensaladas, verduras crujientes, producto de temporada y recetas donde el sabor y la textura sean protagonistas. Mantén algunas verduras congeladas para cremas, guisos, salteados rápidos y días en los que no tienes producto fresco disponible. Esta combinación suele ser más útil que obligarse a elegir un único formato para todo.</p>
HTML;
$en=<<<'HTML'
<h2>Season, distance and time all matter</h2>
<p>Fresh vegetables that are in season, come through a short supply chain and are eaten soon after harvest can offer outstanding flavour and texture. But the word “fresh” does not automatically mean local or recently picked. Produce may still look excellent after spending several days in transport, distribution and refrigeration.</p>
<p>Freezing works differently: it captures the vegetable at a particular point and allows it to be stored for months. This can be especially useful for short-season produce or in households where a fresh purchase might otherwise be forgotten in the refrigerator.</p>
<h2>Food waste belongs in the comparison too</h2>
<p>A fresh vegetable that ends up in the bin provides no nutritional advantage. For small households, irregular cooks or recipes requiring only a small amount, taking 150 or 200 grams from a frozen bag and returning the rest to the freezer can be genuinely useful.</p>
<p>If a family rapidly eats a box of seasonal fresh vegetables, however, this particular frozen advantage almost disappears. The best format depends on real consumption patterns.</p>
<h2>Do frozen vegetables contain preservatives?</h2>
<p>They do not necessarily need them: freezing itself is the preservation method. A bag of plain broccoli, spinach, peas or green beans may contain only the vegetable. Prepared frozen dishes are different and can include sauces, butter, cheese, salt or other ingredients.</p>
<p>For a fair nutritional comparison, compare fresh vegetables with <strong>the same plain frozen vegetable</strong>, not with a ready-made frozen meal.</p>
<h2>A practical household strategy</h2>
<p>Prioritise fresh produce for salads, crisp textures, seasonal vegetables and recipes where flavour is central. Keep some frozen vegetables for soups, purées, stews and quick weekday cooking. Using both formats intelligently is usually more useful than insisting that one category must win every comparison.</p>
HTML;
$a['content']=str_replace('<h2>Conclusión</h2>',$es.'<h2>Conclusión</h2>',$a['content']);
$a['en_content']=str_replace('<h2>Conclusion</h2>',$en.'<h2>Conclusion</h2>',$a['en_content']);
return $a;
