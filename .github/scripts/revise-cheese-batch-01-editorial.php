<?php
/**
 * Editorial revision for cheese batch 01.
 * Removes the generic FAQ/buying/error/conclusion template and replaces it
 * with article-specific material. Some articles intentionally have no FAQ.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

const EMDO_CHEESE01_REVISION = '2026-09-01.cheese-01.editorial-v2';

$revisions = array(
'queso-manchego-dop-que-es-como-se-elabora-reconocer-autentico' => array(
'es' => array(
'action_title' => 'Cómo comprobar un Manchego DOP antes de comprarlo',
'action_html' => '<p>Empieza por la certificación, no por la estética de la etiqueta. Comprueba que la ficha mencione expresamente la DOP Queso Manchego y, cuando compres online, busca imágenes legibles de los elementos de identificación. Después compara productor, tipo de leche declarado, maduración, peso y formato. Un queso de oveja puede ser excelente sin ser Manchego DOP; lo importante es que cada nombre se utilice con precisión.</p>',
'mistakes_html' => '<h2>Errores frecuentes al identificar Queso Manchego</h2><ul><li>Dar por hecho que cualquier queso de oveja elaborado en Castilla-La Mancha pertenece a la DOP.</li><li>Confundir una presentación con molinos, ovejas o referencias manchegas con una certificación oficial.</li><li>Elegir solo por meses de maduración sin valorar productor, formato y perfil buscado.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Puede un queso de oveja elaborado en Castilla-La Mancha no ser Manchego DOP?</h3><p>Sí. Para utilizar la denominación protegida no basta con la zona: deben cumplirse el origen de la leche, la elaboración, la maduración y el resto de requisitos del pliego, además del sistema de control de la DOP.</p><h3>¿Todos los Manchegos DOP tienen la misma maduración?</h3><p>No. El pliego establece condiciones y mínimos, pero dentro de la denominación existen piezas con distintas maduraciones y perfiles. La DOP garantiza el marco de origen y elaboración, no un sabor idéntico entre productores.</p><h3>¿Cómo puedo verificar una cuña si no veo la pieza entera?</h3><p>Revisa la etiqueta de la propia cuña y la información de certificación que acompaña al formato comercial. En una tienda online, una ficha clara y fotografías legibles son especialmente importantes.</p>',
'conclusion_html' => '<p>Queso Manchego es una denominación concreta, no un sinónimo genérico de queso de oveja. La mejor compra parte de verificar la DOP y después elegir productor y maduración según el perfil que realmente quieras comer.</p>'
),
'en' => array(
'action_title' => 'How to check Manchego PDO before buying',
'action_html' => '<p>Start with certification rather than packaging style. Check that the product page explicitly states Manchego PDO and, when shopping online, look for readable images of the official identification. Then compare producer, declared milk treatment, age, weight and format. A sheep’s-milk cheese can be excellent without being Manchego PDO; the important point is that each name is used accurately.</p>',
'mistakes_html' => '<h2>Common mistakes when identifying Manchego</h2><ul><li>Assuming every sheep’s-milk cheese made in Castilla-La Mancha belongs to the PDO.</li><li>Treating windmills, sheep imagery or regional wording as proof of certification.</li><li>Choosing only by months of ageing without considering producer, format and the flavour profile you want.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Can a sheep’s-milk cheese made in Castilla-La Mancha be outside the Manchego PDO?</h3><p>Yes. The protected name requires much more than location: milk origin, production, maturation and the other specification requirements must be met and controlled.</p><h3>Do all Manchego PDO cheeses have the same age?</h3><p>No. The specification sets the framework and minimum requirements, but certified cheeses can be sold at different ages and with different profiles. PDO status does not make every producer taste identical.</p><h3>How can I verify a wedge when I cannot see the whole wheel?</h3><p>Check the wedge label and the certification information supplied with that commercial format. Clear product-page information and readable photographs matter especially when buying online.</p>',
'conclusion_html' => '<p>Manchego is a specific protected designation, not a generic synonym for sheep’s-milk cheese. Verify the PDO first, then choose producer and age according to the style you actually want to eat.</p>'
)
),
'tipos-de-queso-guia-leche-maduracion-pasta-corteza-elaboracion' => array(
'es' => array(
'action_title' => 'Cómo describir un queso sin quedarse en una sola categoría',
'action_html' => '<p>Para entender una pieza concreta, intenta describirla con cuatro o cinco datos: especie de leche, tratamiento de la leche cuando sea relevante, grado de maduración, textura o tecnología de la pasta y tipo de corteza. Ese pequeño “mapa” explica mucho más que etiquetas amplias como “fuerte”, “artesano” o “premium”.</p>',
'mistakes_html' => '<h2>Errores al clasificar quesos</h2><ul><li>Buscar una única clasificación universal cuando las categorías se superponen.</li><li>Confundir pasta blanda con queso fresco: un queso blando puede estar madurado.</li><li>Interpretar vaca, cabra, oveja o mezcla como niveles de calidad en lugar de materias primas distintas.</li><li>Suponer que una corteza vistosa permite deducir por sí sola cómo se ha elaborado el interior.</li></ul>',
'faq_html' => '',
'conclusion_html' => '<p>No existe una lista única capaz de ordenar todos los quesos. La clasificación más útil combina leche, humedad, maduración, tecnología de la pasta y corteza; así puedes comparar estilos sin convertir una sola característica en una jerarquía de calidad.</p>'
),
'en' => array(
'action_title' => 'How to describe a cheese without relying on one category',
'action_html' => '<p>For any specific cheese, try to describe four or five things: milk species, milk treatment where relevant, maturity, body or curd technology, and rind type. That small map tells you far more than broad labels such as “strong”, “artisan” or “premium”.</p>',
'mistakes_html' => '<h2>Common cheese-classification mistakes</h2><ul><li>Looking for one universal classification when categories overlap.</li><li>Confusing soft cheese with fresh cheese: a soft cheese can be fully ripened.</li><li>Treating cow, goat, sheep or mixed milk as quality levels rather than different raw materials.</li><li>Assuming an eye-catching rind tells you everything about how the interior was made.</li></ul>',
'faq_html' => '',
'conclusion_html' => '<p>No single list can organise every cheese. The most useful classification combines milk, moisture, ageing, curd technology and rind so that styles can be compared without turning one characteristic into a quality hierarchy.</p>'
)
),
'queso-tierno-semicurado-curado-viejo-anejo-diferencias' => array(
'es' => array(
'action_title' => 'Cómo elegir maduración según el uso',
'action_html' => '<p>Si buscas un queso para bocadillos o aperitivos suaves, una pasta más joven puede resultar más flexible y láctica. Para una tabla, un curado o viejo aporta contraste y persistencia. Para rallar o usar en pequeñas cantidades, una maduración larga puede ser especialmente útil. La categoría legal orienta, pero el estilo del productor y el tamaño de la pieza también modifican la sensación final.</p>',
'mistakes_html' => '<h2>Errores al comparar maduraciones</h2><ul><li>Leer “añejo” como sinónimo automático de mayor calidad.</li><li>Comparar meses de dos quesos muy distintos como si evolucionaran a la misma velocidad.</li><li>Esperar que todos los quesos de una misma categoría legal tengan idéntica textura e intensidad.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Se pueden comparar directamente los meses de curación de dos quesos distintos?</h3><p>Solo con cautela. Tamaño de la pieza, humedad, tecnología de elaboración y condiciones de maduración hacen que dos quesos con la misma edad cronológica evolucionen de manera diferente.</p><h3>¿Un semicurado puede resultar más intenso que otro queso clasificado como curado?</h3><p>Sí. La categoría aporta información sobre maduración, pero la intensidad sensorial también depende de leche, fermentos, sal, corteza y tecnología. Los nombres legales no son una escala universal de potencia aromática.</p>',
'conclusion_html' => '<p>Tierno, semicurado, curado, viejo y añejo sirven para orientarse, pero no sustituyen la descripción real del queso. Utiliza la maduración para anticipar textura e intensidad y termina la elección pensando en el uso y el productor.</p>'
),
'en' => array(
'action_title' => 'Choosing an age for the way you will use the cheese',
'action_html' => '<p>For sandwiches or gentle snacks, a younger cheese may offer a more flexible, lactic texture. On a board, aged or long-aged cheese adds contrast and persistence. For grating or using in small amounts, longer ageing can be particularly useful. The legal category helps, but producer style and wheel size also shape the final experience.</p>',
'mistakes_html' => '<h2>Mistakes when comparing cheese ages</h2><ul><li>Reading “long-aged” as an automatic synonym for higher quality.</li><li>Comparing the number of months on very different cheeses as though they matured at the same rate.</li><li>Expecting every cheese in one ageing category to have identical texture and intensity.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Can the ageing months of two different cheeses be compared directly?</h3><p>Only with caution. Wheel size, moisture, cheesemaking technology and maturation conditions mean that two cheeses of the same chronological age can develop very differently.</p><h3>Can a semi-aged cheese taste stronger than another cheese labelled aged?</h3><p>Yes. Ageing category provides useful information, but sensory intensity also depends on milk, cultures, salt, rind and technology. The legal terms are not a universal aroma scale.</p>',
'conclusion_html' => '<p>Young, semi-aged, aged and long-aged terms are useful orientation, not a full description of the cheese. Use age to anticipate texture and concentration, then choose for the intended use and producer style.</p>'
)
),
'queso-oveja-cabra-vaca-diferencias-como-elegir' => array(
'es' => array(
'action_title' => 'Cómo comparar quesos de distintas leches de forma justa',
'action_html' => '<p>Compara primero quesos de maduración y tecnología similares. Probar un fresco de cabra frente a un viejo de oveja dice mucho más sobre esos estilos concretos que sobre las especies en general. Si quieres aprender de verdad qué aporta cada leche, mantén constantes tantas variables como sea posible.</p>',
'mistakes_html' => '<h2>Ideas engañosas sobre la especie de leche</h2><ul><li>Asumir que todo queso de cabra es fuerte o ácido.</li><li>Dar por hecho que el color permite identificar con seguridad la especie de leche.</li><li>Tratar los quesos de mezcla como una categoría inferior o superior por definición.</li><li>Atribuir a la leche diferencias que en realidad proceden de maduración, corteza o elaboración.</li></ul>',
'faq_html' => '',
'conclusion_html' => '<p>Vaca, oveja y cabra aportan materias primas diferentes, pero ninguna especie determina por sí sola el carácter del queso. La comparación útil combina leche con maduración, tecnología y productor.</p>'
),
'en' => array(
'action_title' => 'How to compare cheeses from different milks fairly',
'action_html' => '<p>Start with cheeses of similar age and technology. Comparing a fresh goat cheese with a long-aged sheep’s cheese tells you far more about those two styles than about the species in general. To learn what each milk contributes, keep as many other variables constant as possible.</p>',
'mistakes_html' => '<h2>Misleading assumptions about milk species</h2><ul><li>Assuming every goat cheese is strong or sharply acidic.</li><li>Believing colour can reliably identify the animal species.</li><li>Treating mixed-milk cheese as automatically better or worse.</li><li>Attributing differences to milk that actually come from ageing, rind or production method.</li></ul>',
'faq_html' => '',
'conclusion_html' => '<p>Cow, sheep and goat provide different raw materials, but no species determines cheese character on its own. Meaningful comparison combines milk with age, technology and producer.</p>'
)
),
'queso-leche-cruda-o-pasteurizada-diferencias-sabor-seguridad' => array(
'es' => array(
'action_title' => 'Cómo interpretar “leche cruda” y “pasteurizada” en una etiqueta',
'action_html' => '<p>Úsalo como un dato de proceso. Después mira especie de leche, tipo de queso, maduración, productor, conservación y, cuando exista, certificación de origen. Para comparar sabor, lo más instructivo es enfrentar productos del mismo estilo; de lo contrario se mezclan demasiadas variables para atribuir el resultado al tratamiento térmico.</p>',
'mistakes_html' => '<h2>Errores habituales con leche cruda y pasteurizada</h2><ul><li>Usar el tratamiento de la leche como una puntuación automática de calidad.</li><li>Suponer que “leche cruda” significa ausencia de controles sanitarios.</li><li>Intentar identificar con certeza el tratamiento solo por el sabor.</li><li>Ignorar las instrucciones de conservación porque el queso sea muy madurado.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Un queso de leche cruda está elaborado “sin controles”?</h3><p>No. La ausencia de pasteurización no elimina los requisitos de higiene, trazabilidad y control microbiológico que debe cumplir un operador alimentario. Es una técnica de elaboración distinta, no una excepción a la seguridad alimentaria.</p><h3>¿Se puede saber por el sabor si un queso es de leche cruda?</h3><p>No de forma fiable. La leche cruda puede contribuir a determinados perfiles, pero fermentos, corteza, maduración y tecnología también cambian mucho el aroma. La etiqueta es la referencia para conocer el tratamiento de la leche.</p><h3>¿Pasteurizado significa necesariamente sabor más suave?</h3><p>No. Hay quesos pasteurizados muy intensos y complejos. La intensidad final depende de todo el proceso y, de forma muy importante, de la maduración.</p>',
'conclusion_html' => '<p>Leche cruda y pasteurizada son dos caminos tecnológicos, no dos notas de calidad. Para entender un queso hay que leer ese dato dentro del conjunto de elaboración, maduración, conservación y origen.</p>'
),
'en' => array(
'action_title' => 'How to read “raw milk” and “pasteurised” on a label',
'action_html' => '<p>Treat it as a processing fact. Then consider milk species, cheese style, age, producer, storage and geographical certification where relevant. For flavour comparison, similar styles are most informative; otherwise too many variables change at once to credit the result to heat treatment.</p>',
'mistakes_html' => '<h2>Common mistakes about raw and pasteurised milk</h2><ul><li>Using milk treatment as an automatic quality score.</li><li>Assuming “raw milk” means an absence of food-safety controls.</li><li>Trying to identify milk treatment with certainty from flavour alone.</li><li>Ignoring storage instructions because a cheese is heavily aged.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Is raw-milk cheese made “without controls”?</h3><p>No. The absence of pasteurisation does not remove hygiene, traceability and microbiological obligations for food businesses. It is a different production method, not an exemption from food safety.</p><h3>Can you tell from flavour whether a cheese is raw-milk?</h3><p>Not reliably. Raw milk can contribute to certain profiles, but cultures, rind, age and technology also change aroma substantially. The label is the reference for milk treatment.</p><h3>Does pasteurised automatically mean milder flavour?</h3><p>No. Pasteurised cheeses can be highly intense and complex. Final intensity depends on the complete process and especially on maturation.</p>',
'conclusion_html' => '<p>Raw and pasteurised milk are two technological routes, not two quality grades. Read milk treatment as part of the wider picture of production, ageing, storage and origin.</p>'
)
),
'como-conservar-queso-correctamente-nevera-envoltorio-temperatura' => array(
'es' => array(
'action_title' => 'Un sistema sencillo para guardar una cuña abierta',
'action_html' => '<p>Protege bien la cara de corte, colócala en una zona estable de la nevera y revisa el envoltorio cada vez que la uses. Si aparece condensación persistente, cambia el material y seca el recipiente; si la pasta se cuartea y endurece con rapidez, está perdiendo demasiada humedad. Guarda el grueso de la pieza en frío y atempera solo la porción que vas a comer.</p>',
'mistakes_html' => '<h2>Errores que acortan la vida del queso</h2><ul><li>Dejar la cara de corte expuesta al aire de la nevera.</li><li>Mantener un envoltorio húmedo durante días sin cambiarlo.</li><li>Guardar el queso junto a alimentos con olores intensos o posibles contaminantes.</li><li>Sacar y volver a enfriar toda la pieza cada vez que se sirve una pequeña porción.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Es mejor un recipiente hermético o papel para queso?</h3><p>Depende del queso y de la humedad real de tu nevera. Lo importante es protegerlo del aire sin mantener condensación constante. Un recipiente puede funcionar bien si el queso está correctamente envuelto y se vigila la humedad interior.</p><h3>¿Dónde conviene colocar el queso dentro del frigorífico?</h3><p>En una zona de temperatura relativamente estable y protegida de olores fuertes. Una puerta muy utilizada suele sufrir más cambios térmicos que un cajón o estante interior.</p><h3>¿Debo guardar igual una pieza entera y una cuña?</h3><p>No exactamente. Una cuña tiene mucha más superficie interior expuesta y necesita especial protección en la zona cortada; una pieza intacta suele evolucionar más lentamente.</p>',
'conclusion_html' => '<p>Conservar bien queso no consiste en sellarlo al máximo, sino en equilibrar frío, humedad y protección. Observa la cuña: sequedad excesiva y condensación persistente son señales opuestas de que el sistema necesita ajustarse.</p>'
),
'en' => array(
'action_title' => 'A simple system for storing an opened wedge',
'action_html' => '<p>Protect the cut face, keep the wedge in a stable part of the refrigerator and inspect the wrapping whenever you use it. Persistent condensation means the wrapping or container needs attention; rapid cracking and hardening means too much moisture is being lost. Keep the main piece cold and warm only the serving portion.</p>',
'mistakes_html' => '<h2>Storage mistakes that shorten cheese quality</h2><ul><li>Leaving the cut face exposed to refrigerator air.</li><li>Keeping damp wrapping in place for days.</li><li>Storing cheese next to very strong odours or possible sources of cross-contamination.</li><li>Warming and chilling the whole piece every time a small portion is served.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Is an airtight container better than cheese paper?</h3><p>It depends on the cheese and on the humidity in your refrigerator. The aim is to protect from drying without maintaining constant condensation. A container can work well when the cheese is properly wrapped and internal moisture is monitored.</p><h3>Where should cheese sit in the refrigerator?</h3><p>Choose a relatively stable area away from powerful odours. A frequently opened door usually experiences larger temperature swings than an interior shelf or drawer.</p><h3>Should a whole cheese and a cut wedge be stored the same way?</h3><p>Not exactly. A wedge exposes much more interior surface and the cut face needs particular protection, while an intact wheel generally changes more slowly.</p>',
'conclusion_html' => '<p>Good cheese storage is not about sealing as tightly as possible; it is about balancing refrigeration, moisture and protection. Excessive drying and persistent condensation are opposite signs that the system needs adjustment.</p>'
)
),
'se-puede-congelar-queso-tipos-como-hacerlo' => array(
'es' => array(
'action_title' => 'Cómo congelar queso paso a paso sin complicarlo',
'action_html' => '<ol><li>Decide primero para qué vas a utilizarlo al descongelar.</li><li>Divide la pieza en porciones que puedas consumir de una vez.</li><li>Protege bien cada porción frente al aire y los olores del congelador.</li><li>Etiqueta con contenido y fecha.</li><li>Descongela en refrigeración y adapta el uso a la textura resultante.</li></ol><p>Si un queso delicado se compró para comerlo solo y disfrutar de una textura cremosa concreta, congelarlo suele tener poco sentido. Si va a terminar rallado, fundido o dentro de una receta, el cambio de textura importa mucho menos.</p>',
'mistakes_html' => '<h2>Errores frecuentes al congelar queso</h2><ul><li>Congelar una pieza enorme y obligarse después a descongelarla entera.</li><li>Congelar un queso blando esperando recuperar exactamente la misma textura.</li><li>No etiquetar las porciones y terminar olvidándolas en el congelador.</li><li>Descongelar y volver a congelar repetidamente como método normal de almacenamiento.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Es mejor congelar queso en cuña o rallado?</h3><p>Depende del uso posterior. Para cocinar, congelarlo ya rallado o dividido puede ser muy práctico; si quieres conservar una porción reconocible para cortar después, una cuña pequeña y bien protegida tiene más sentido.</p><h3>¿Por qué el queso queda quebradizo después de congelarlo?</h3><p>Los cristales de hielo alteran la estructura de agua, grasa y proteínas. Al descongelar, esa matriz no siempre recupera su organización original y la pasta puede volverse más seca, granulosa o quebradiza.</p><h3>¿Cómo conviene descongelar el queso?</h3><p>La descongelación en nevera permite un cambio gradual y mantiene el alimento refrigerado. Después, úsalo según su nueva textura: un queso algo quebradizo puede seguir funcionando perfectamente en gratinados, salsas o rallado.</p><h3>¿Tiene sentido congelar un queso caro que está en su punto?</h3><p>Solo si la alternativa es desperdiciarlo. Si su principal valor está en una textura y aroma óptimos para comerlo solo, la congelación puede sacrificar parte de esa experiencia.</p>',
'conclusion_html' => '<p>Congelar queso es una herramienta contra el desperdicio, no un método para mejorar ni conservar intacta una pieza. Funciona mejor cuando se porciona con un uso futuro claro y se acepta que la textura puede cambiar.</p>'
),
'en' => array(
'action_title' => 'How to freeze cheese step by step',
'action_html' => '<ol><li>Decide how you will use the cheese after thawing.</li><li>Divide it into portions you can finish at one time.</li><li>Protect each portion carefully from air and freezer odours.</li><li>Label the contents and date.</li><li>Thaw under refrigeration and adapt the final use to the resulting texture.</li></ol><p>If a delicate cheese was bought to enjoy a specific creamy texture on its own, freezing rarely improves the experience. If it will be grated, melted or cooked, texture changes matter far less.</p>',
'mistakes_html' => '<h2>Common mistakes when freezing cheese</h2><ul><li>Freezing one huge piece and later having to thaw all of it.</li><li>Freezing a soft cheese while expecting exactly the same texture afterwards.</li><li>Failing to label portions and forgetting them in the freezer.</li><li>Repeatedly thawing and refreezing as a normal storage method.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Is it better to freeze cheese as a wedge or grated?</h3><p>It depends on the planned use. Pre-grated or divided cheese is very practical for cooking; a small protected wedge makes more sense if you want a recognisable piece to cut later.</p><h3>Why can cheese become crumbly after freezing?</h3><p>Ice crystals alter the water-fat-protein structure. After thawing, that matrix does not always reorganise exactly as before, so the paste may become drier, grainier or more crumbly.</p><h3>What is the best way to thaw cheese?</h3><p>Thawing in the refrigerator provides a gradual temperature change while keeping the food chilled. Then use it according to its new texture; a slightly crumbly cheese may still be excellent for gratins, sauces or grating.</p><h3>Does it make sense to freeze an expensive cheese at its peak?</h3><p>Mainly when the alternative is waste. If much of its value lies in an ideal texture and aroma for eating on its own, freezing may sacrifice part of that experience.</p>',
'conclusion_html' => '<p>Freezing cheese is a waste-reduction tool, not a way to improve or preserve a cheese unchanged. It works best when portions are prepared for a clear future use and some texture change is accepted.</p>'
)
),
'como-cortar-servir-queso-temperatura-cuchillos-presentacion' => array(
'es' => array(
'action_title' => 'Una secuencia sencilla para servir mejor',
'action_html' => '<p>Corta solo la cantidad prevista, deja que esa porción pierda el frío intenso, utiliza un patrón de corte que reparta de forma razonable centro y corteza y coloca cuchillos separados para quesos muy aromáticos o cremosos. El resto de la pieza debe permanecer protegido y refrigerado. Una presentación limpia y con espacio facilita más la degustación que una tabla saturada.</p>',
'mistakes_html' => '<h2>Errores que empeoran el servicio</h2><ul><li>Servir todas las piezas directamente desde la zona más fría de la nevera.</li><li>Cortar una cuña de forma que unos comensales reciban solo centro y otros casi toda la corteza.</li><li>Usar el mismo cuchillo lleno de queso azul para piezas suaves.</li><li>Cortar toda la tabla con mucha antelación y dejar que las superficies se resequen.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Hace falta un cuchillo diferente para cada queso?</h3><p>No. Es más importante que la hoja sea adecuada y esté limpia. Sí conviene separar utensilios cuando un queso puede transferir mucho aroma, moho o pasta cremosa a los demás, como ocurre con muchos azules.</p><h3>¿Hay que quitar siempre la corteza antes de servir?</h3><p>No. Algunas cortezas forman parte del estilo y pueden ser comestibles; otros recubrimientos no están destinados al consumo. La información del productor o la etiqueta debe resolver la duda.</p>',
'conclusion_html' => '<p>Servir queso bien consiste en respetar temperatura, geometría de la pieza y limpieza entre sabores. Con pequeños ajustes de corte y servicio se percibe mejor el trabajo que ya está dentro del queso.</p>'
),
'en' => array(
'action_title' => 'A simple sequence for better cheese service',
'action_html' => '<p>Cut only the amount you expect to serve, let that portion lose its deepest chill, use a cutting pattern that shares centre and rind fairly, and separate knives for very aromatic or creamy cheeses. Keep the remainder protected and refrigerated. A clean board with space usually supports tasting better than an overcrowded display.</p>',
'mistakes_html' => '<h2>Serving mistakes that reduce quality</h2><ul><li>Serving every cheese directly from the coldest part of the refrigerator.</li><li>Cutting a wedge so some guests receive only centre and others almost all rind.</li><li>Using a blue-cheese-covered knife on mild cheeses.</li><li>Cutting the entire board far in advance and allowing every surface to dry.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Do I need a different knife for every cheese?</h3><p>No. A suitable, clean blade matters more. Separate utensils are useful when one cheese can transfer a lot of aroma, mould or creamy paste to the others, as with many blue cheeses.</p><h3>Should the rind always be removed before serving?</h3><p>No. Some rinds belong to the style and may be edible, while other coatings are not intended for consumption. Producer or label information should settle any doubt.</p>',
'conclusion_html' => '<p>Good cheese service respects temperature, the geometry of the piece and clean separation between flavours. Small changes in cutting and serving help reveal the work already present in the cheese.</p>'
)
),
'como-preparar-tabla-quesos-cantidades-orden-acompanamientos' => array(
'es' => array(
'action_title' => 'Cómo comprar para una tabla sin acabar con demasiado queso',
'action_html' => '<p>Decide primero cuántas personas comerán y qué papel tendrá la tabla: aperitivo, postre o comida principal. Después elige tres a cinco perfiles distintos y compra formatos pequeños cuando quieras variedad. Es preferible poder reponer una cuña que colocar desde el principio toda la cantidad disponible, especialmente si la mesa está caliente.</p>',
'mistakes_html' => '<h2>Errores al montar una tabla de quesos</h2><ul><li>Comprar variedad por número y terminar con varios quesos casi idénticos.</li><li>Calcular la cantidad sin tener en cuenta el resto de la comida.</li><li>Cubrir todos los quesos con miel, mermeladas o salsas antes de que cada persona pueda probarlos solos.</li><li>Empezar por el queso más potente y saturar el paladar para los siguientes.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Se puede dejar la tabla completamente cortada antes de que lleguen los invitados?</h3><p>Se puede adelantar parte del trabajo, pero cortar todo demasiado pronto aumenta superficie expuesta y favorece el resecado. Mantén las piezas protegidas y termina parte del corte cerca del servicio.</p><h3>¿Hay que poner un queso de cada tipo de leche?</h3><p>No. Es una forma posible de crear contraste, pero no una regla. También puedes variar textura, corteza, maduración o intensidad aunque varias piezas sean de la misma leche.</p>',
'conclusion_html' => '<p>Una buena tabla se diseña por contraste, no por acumulación. Pocas piezas bien diferenciadas, cantidades acordes al contexto y acompañamientos que no tapen el queso suelen producir una experiencia mejor y menos desperdicio.</p>'
),
'en' => array(
'action_title' => 'How to shop for a cheese board without buying too much',
'action_html' => '<p>First decide how many people will eat and whether cheese is an appetiser, a course or the main meal. Then choose three to five genuinely different profiles and favour smaller formats when you want variety. It is better to replenish a wedge than to put every gram on the table at once, especially in a warm room.</p>',
'mistakes_html' => '<h2>Common cheese-board mistakes</h2><ul><li>Buying variety by number and ending up with several nearly identical cheeses.</li><li>Calculating quantity without considering the rest of the meal.</li><li>Covering every cheese with honey, preserves or sauces before guests can taste it alone.</li><li>Starting with the strongest cheese and overwhelming the palate for everything that follows.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Can I cut the whole board before guests arrive?</h3><p>You can prepare some of it in advance, but cutting everything too early exposes more surface and encourages drying. Keep cheeses protected and finish some cutting closer to service.</p><h3>Do I need one cheese from each milk species?</h3><p>No. That is one way to create contrast, not a rule. Texture, rind, age and intensity can also create variety even when several cheeses use the same milk.</p>',
'conclusion_html' => '<p>A good board is designed through contrast, not accumulation. A few clearly different cheeses, quantities that fit the occasion and accompaniments that do not hide the cheese usually create a better experience with less waste.</p>'
)
),
'como-saber-queso-mal-estado-moho-olor-textura' => array(
'es' => array(
'action_title' => 'Qué hacer cuando aparece una señal que no reconoces',
'action_html' => '<p>Identifica primero la familia del queso y comprueba si el aspecto forma parte de su elaboración normal. Después valora conjuntamente moho, olor, textura, envase y cómo se ha conservado. En queso blando o fresco, un moho inesperado merece mucha más cautela que un punto superficial en una pieza dura. Si no puedes distinguir si el cambio es normal y existen dudas razonables sobre seguridad, desechar es la decisión prudente.</p>',
'mistakes_html' => '<h2>Errores al valorar si un queso está estropeado</h2><ul><li>Aplicar la misma regla de moho a un queso azul, un fresco y un curado duro.</li><li>Decidir solo por un olor fuerte sin conocer el perfil normal del queso.</li><li>Recortar moho de queso rallado, desmenuzado o blando como si fuera una pieza dura.</li><li>Ignorar cómo se ha conservado el producto y fijarse únicamente en su aspecto actual.</li></ul>',
'faq_html' => '<h2>Preguntas relacionadas</h2><h3>¿Puedo quitar el moho de un queso rallado y comer el resto?</h3><p>No debe aplicarse la regla de recorte de los quesos duros a queso rallado, loncheado o desmenuzado. En esos formatos la superficie expuesta es mucho mayor y la contaminación puede estar distribuida.</p><h3>¿Un queso azul puede desarrollar mohos de otros colores?</h3><p>El aspecto normal depende del queso concreto. Si aparece un crecimiento claramente diferente de su patrón habitual, especialmente junto con cambios anormales de olor o textura, no conviene asumir que todo moho es “parte del azul”.</p><h3>¿Un olor muy fuerte significa que el queso está malo?</h3><p>No necesariamente. Algunos quesos maduros tienen aromas muy intensos de forma natural. Lo relevante es un cambio inesperado respecto al olor normal del producto y su combinación con otras señales.</p><h3>¿La corteza con moho significa siempre que hay que tirar la pieza?</h3><p>No. Existen cortezas enmohecidas deliberadamente. Hay que distinguir una flora prevista por el estilo de un crecimiento nuevo y no deseado.</p>',
'conclusion_html' => '<p>La señal decisiva no es “hay moho” o “huele fuerte”, sino si el cambio corresponde al estilo del queso y a una conservación correcta. Tipo de queso, humedad y formato determinan qué respuesta es razonable.</p>'
),
'en' => array(
'action_title' => 'What to do when you notice a sign you do not recognise',
'action_html' => '<p>First identify the cheese family and decide whether the appearance belongs to its normal production. Then assess mould, smell, texture, packaging and storage history together. Unexpected mould on soft or fresh cheese deserves much more caution than one surface spot on a hard cheese. If you cannot tell whether a change is normal and there is genuine food-safety uncertainty, discarding is the prudent choice.</p>',
'mistakes_html' => '<h2>Mistakes when judging spoiled cheese</h2><ul><li>Applying the same mould rule to blue cheese, fresh cheese and hard aged cheese.</li><li>Deciding from strong smell alone without knowing the cheese’s normal profile.</li><li>Trimming mould from grated, crumbled or soft cheese as though it were a hard wheel.</li><li>Ignoring storage history and looking only at the current appearance.</li></ul>',
'faq_html' => '<h2>Related questions</h2><h3>Can I remove mould from grated cheese and eat the rest?</h3><p>The trimming guidance for hard cheese should not be applied to grated, sliced or crumbled cheese. These formats expose far more surface area and contamination may be distributed.</p><h3>Can blue cheese develop other mould colours?</h3><p>Normal appearance depends on the specific cheese. A growth clearly different from its usual pattern, especially together with abnormal smell or texture, should not automatically be dismissed as “part of the blue”.</p><h3>Does a very strong smell mean cheese has gone bad?</h3><p>Not necessarily. Some mature cheeses are naturally very pungent. What matters is an unexpected change from the product’s normal smell together with other signs.</p><h3>Does mould on the rind always mean the cheese must be discarded?</h3><p>No. Some rinds are deliberately mould-ripened. The important distinction is between intended flora and new unwanted growth.</p>',
'conclusion_html' => '<p>The decisive question is not simply “is there mould?” or “does it smell strong?” but whether the change fits the cheese style and its storage history. Cheese type, moisture and format determine the appropriate response.</p>'
)
)
);

function emdo_cheese01_replace_between( string $content, string $start_heading, string $end_heading, string $replacement ): string {
    $pattern = '~<h2>' . preg_quote( $start_heading, '~' ) . '</h2>.*?(?=<h2>' . preg_quote( $end_heading, '~' ) . '</h2>)~s';
    $count = 0;
    $new = preg_replace( $pattern, $replacement, $content, 1, $count );
    if ( 1 !== $count || ! is_string( $new ) ) {
        throw new RuntimeException( 'Could not replace section: ' . $start_heading );
    }
    return $new;
}

function emdo_cheese01_replace_conclusion( string $content, string $heading, string $sources_heading, string $html ): string {
    return emdo_cheese01_replace_between( $content, $heading, $sources_heading, '<h2>' . esc_html( $heading ) . '</h2>' . $html );
}

function emdo_cheese01_apply_revision( string $content, array $r, bool $spanish ): string {
    if ( $spanish ) {
        $content = emdo_cheese01_replace_between( $content, 'Cómo aplicar esta información al comprar', 'Errores frecuentes', '<h2>' . esc_html( $r['action_title'] ) . '</h2>' . $r['action_html'] );
        $content = emdo_cheese01_replace_between( $content, 'Errores frecuentes', 'Preguntas rápidas', $r['mistakes_html'] );
        $content = emdo_cheese01_replace_between( $content, 'Preguntas rápidas', 'Guías relacionadas', $r['faq_html'] );
        $content = emdo_cheese01_replace_conclusion( $content, 'Conclusión', 'Fuentes y referencias', $r['conclusion_html'] );
    } else {
        $content = emdo_cheese01_replace_between( $content, 'How to use this when buying cheese', 'Common mistakes', '<h2>' . esc_html( $r['action_title'] ) . '</h2>' . $r['action_html'] );
        $content = emdo_cheese01_replace_between( $content, 'Common mistakes', 'Quick questions', $r['mistakes_html'] );
        $content = emdo_cheese01_replace_between( $content, 'Quick questions', 'Related guides', $r['faq_html'] );
        $content = emdo_cheese01_replace_conclusion( $content, 'Bottom line', 'Sources and references', $r['conclusion_html'] );
    }
    return $content;
}

$results = array();
foreach ( $revisions as $slug => $languages ) {
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    if ( ! $post instanceof WP_Post ) {
        throw new RuntimeException( 'Missing cheese article: ' . $slug );
    }
    if ( '2026-09-01.cheese-01.v1' !== (string) get_post_meta( $post->ID, '_emdo_cheese_batch01', true ) ) {
        throw new RuntimeException( 'Unexpected batch marker for: ' . $slug );
    }

    $es = emdo_cheese01_apply_revision( (string) $post->post_content, $languages['es'], true );
    $en_original = (string) get_post_meta( $post->ID, '_en_US_post_content', true );
    $en = emdo_cheese01_apply_revision( $en_original, $languages['en'], false );

    if ( false !== strpos( $es, '¿Más curación significa siempre mejor queso?' ) || false !== strpos( $en, 'Does longer ageing always mean better cheese?' ) ) {
        throw new RuntimeException( 'Generic FAQ survived in: ' . $slug );
    }
    if ( false !== strpos( $es, '<h2>Preguntas rápidas</h2>' ) || false !== strpos( $en, '<h2>Quick questions</h2>' ) ) {
        throw new RuntimeException( 'Generic FAQ heading survived in: ' . $slug );
    }

    $updated = wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $es ) ), true );
    if ( is_wp_error( $updated ) ) {
        throw new RuntimeException( 'Spanish update failed for ' . $slug . ': ' . $updated->get_error_message() );
    }
    update_post_meta( $post->ID, '_en_US_post_content', $en );
    update_post_meta( $post->ID, '_emdo_cheese_batch01_revision', EMDO_CHEESE01_REVISION );
    clean_post_cache( $post->ID );

    $es_saved = (string) get_post_field( 'post_content', $post->ID );
    $en_saved = (string) get_post_meta( $post->ID, '_en_US_post_content', true );
    if ( false !== strpos( $es_saved, '¿Más curación significa siempre mejor queso?' ) || false !== strpos( $en_saved, 'Does longer ageing always mean better cheese?' ) ) {
        throw new RuntimeException( 'Saved content still contains generic FAQ: ' . $slug );
    }
    $results[] = array(
        'id' => (int) $post->ID,
        'slug' => $slug,
        'es_words' => str_word_count( wp_strip_all_tags( $es_saved ) ),
        'en_words' => str_word_count( wp_strip_all_tags( $en_saved ) ),
        'has_es_faq' => false !== strpos( $es_saved, '<h2>Preguntas relacionadas</h2>' ),
        'has_en_faq' => false !== strpos( $en_saved, '<h2>Related questions</h2>' ),
    );
}

echo "EMDO_CHEESE01_REVISION_BEGIN\n";
echo wp_json_encode( array( 'revision' => EMDO_CHEESE01_REVISION, 'count' => count( $results ), 'posts' => $results ), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . "\n";
echo "EMDO_CHEESE01_REVISION_END\n";
