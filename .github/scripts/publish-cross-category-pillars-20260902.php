<?php
/** Publish five bilingual evergreen cross-category food guides. Idempotent by ES slug. */
if (!defined('ABSPATH')) exit;

function emdo_cp_en_url($id){
    $slug=(string)get_post_meta($id,'_en_US_post_name',true);
    return $slug ? rtrim(home_url('/'),'/').'/en/'.trim($slug,'/').'/' : get_permalink($id);
}
function emdo_cp_es_link($id,$label){ return '<a href="'.esc_url(get_permalink($id)).'">'.esc_html($label).'</a>'; }
function emdo_cp_en_link($id,$label){ return '<a href="'.esc_url(emdo_cp_en_url($id)).'">'.esc_html($label).'</a>'; }
function emdo_cp_words($s){ preg_match_all('/[\p{L}\p{N}]+/u',wp_strip_all_tags((string)$s),$m); return count($m[0]); }

$category_id=445;
$placeholder_id=13442;
$source_eu_quality='https://agriculture.ec.europa.eu/farming/geographical-indications-and-quality-schemes/geographical-indications-and-quality-schemes-explained_en';
$source_reg_1169='https://eur-lex.europa.eu/legal-content/en/ALL/?uri=CELEX%3A02011R1169-20250401';
$source_origin='https://food.ec.europa.eu/food-safety/labelling-and-nutrition/food-information-consumers-legislation/origin-labelling_en';
$source_aesan='https://www.aesan.gob.es/para-la-ciudadania/preguntas-y-respuestas-frecuentes';
$source_trace='https://eur-lex.europa.eu/eli/reg/2002/178/';

$dop_es=emdo_cp_es_link(13900,'guía de quesos españoles con DOP');
$hamdop_es=emdo_cp_es_link(13852,'denominaciones de origen del jamón ibérico');
$oildop_es=emdo_cp_es_link(13861,'denominaciones de origen del aceite de oliva');
$dop_en=emdo_cp_en_link(13900,'guide to Spanish PDO cheeses');
$hamdop_en=emdo_cp_en_link(13852,'Iberian ham PDO guide');
$oildop_en=emdo_cp_en_link(13861,'olive-oil PDO guide');

$label_cheese_es=emdo_cp_es_link(13967,'cómo leer la etiqueta de un queso');
$label_oil_es=emdo_cp_es_link(13013,'cómo leer la etiqueta de un aceite de oliva');
$label_pres_es=emdo_cp_es_link(13090,'cómo leer la etiqueta de una conserva vegetal');
$label_cheese_en=emdo_cp_en_link(13967,'how to read a cheese label');
$label_oil_en=emdo_cp_en_link(13013,'how to read an olive-oil label');
$label_pres_en=emdo_cp_en_link(13090,'how to read a vegetable preserve label');

$expiry_pres_es=emdo_cp_es_link(13325,'si las conservas caducan y cómo interpretar su duración');
$expiry_oil_es=emdo_cp_es_link(13021,'si el AOVE caduca y cómo detectar deterioro');
$meat_safe_es=emdo_cp_es_link(13291,'cómo valorar el estado de la carne fresca');
$expiry_pres_en=emdo_cp_en_link(13325,'how long preserves last');
$expiry_oil_en=emdo_cp_en_link(13021,'whether EVOO expires and how it deteriorates');
$meat_safe_en=emdo_cp_en_link(13291,'how to assess fresh meat condition');

$items=[];

$items[]=[
 'title'=>'DOP, IGP y ETG: qué significan, diferencias y cómo reconocer los sellos de calidad',
 'slug'=>'dop-igp-etg-diferencias-sellos-calidad-alimentos',
 'excerpt'=>'Qué significan DOP, IGP y ETG, qué protege cada figura europea, en qué se diferencian y cómo comprobar si un alimento está realmente amparado.',
 'seo_title'=>'DOP, IGP y ETG: diferencias y sellos de calidad alimentaria',
 'seo_description'=>'Guía clara sobre DOP, IGP y ETG: qué protege cada sello europeo, diferencias entre ellos y cómo reconocer una denominación auténtica.',
 'en_title'=>'PDO, PGI and TSG: differences and how to recognise EU food quality labels',
 'en_slug'=>'pdo-pgi-tsg-differences-eu-food-quality-labels',
 'en_excerpt'=>'What PDO, PGI and TSG mean, what each EU quality scheme protects, how they differ and how to verify a genuine registered name.',
 'en_seo_title'=>'PDO, PGI and TSG: EU food quality labels explained',
 'en_seo_description'=>'A clear guide to PDO, PGI and TSG: what each EU quality label protects, how they differ and how to recognise a genuine registered name.',
 'content'=>
'<p>DOP, IGP y ETG aparecen en quesos, aceites, jamones, embutidos y muchos otros alimentos europeos, pero <strong>no significan lo mismo ni son una escala de “mejor a peor”</strong>. Son figuras de calidad reguladas que protegen nombres y métodos bajo condiciones concretas. Entender la diferencia evita dos errores habituales: pensar que cualquier producto tradicional tiene una DOP y asumir que un sello garantiza por sí solo un sabor o una calidad sensorial idénticos.</p>
<h2>Resumen rápido: qué significa cada sigla</h2>
<table><thead><tr><th>Figura</th><th>Qué protege principalmente</th><th>Relación con el territorio</th></tr></thead><tbody>
<tr><td><strong>DOP</strong></td><td>Un nombre ligado a un saber hacer y unas características definidas</td><td>Las fases esenciales de producción, transformación y elaboración están vinculadas a la zona definida</td></tr>
<tr><td><strong>IGP</strong></td><td>Un nombre cuya calidad, reputación u otra característica se vincula a un origen geográfico</td><td>Al menos una de las fases relevantes está vinculada a la zona</td></tr>
<tr><td><strong>ETG</strong></td><td>Una composición o método tradicional</td><td>No protege necesariamente un origen geográfico concreto</td></tr>
</tbody></table>
<p>La formulación exacta depende de la normativa europea aplicable a cada esquema y producto. Lo importante es recordar que <strong>DOP e IGP son indicaciones geográficas; ETG protege tradición</strong>.</p>
<h2>Qué es una DOP</h2>
<p>DOP significa <strong>Denominación de Origen Protegida</strong>. El nombre identifica un producto estrechamente ligado a un lugar y a un pliego de condiciones. Ese pliego concreta aspectos como zona geográfica, materias primas admitidas, razas o variedades cuando proceda, procesos, maduración, controles y características del producto.</p>
<p>Eso no significa que dos productores de la misma DOP fabriquen alimentos idénticos. Dentro del marco común pueden existir diferencias de materia prima, cosecha, afinado, tiempo de curación o estilo de elaboración. La DOP delimita lo que el producto debe cumplir; <strong>no convierte el sabor en una fórmula única</strong>.</p>
<h2>Qué es una IGP</h2>
<p>IGP significa <strong>Indicación Geográfica Protegida</strong>. También protege un nombre relacionado con un territorio, pero el vínculo productivo exigido es distinto del de la DOP. En términos generales, basta con que una parte relevante del proceso esté conectada con la zona y que exista una calidad, reputación u otra característica atribuible al origen.</p>
<p>Por eso no conviene traducir DOP como “más calidad” e IGP como “menos calidad”. Son <strong>modelos jurídicos diferentes de relación entre producto y territorio</strong>.</p>
<h2>Qué es una ETG</h2>
<p>ETG significa <strong>Especialidad Tradicional Garantizada</strong>. Aquí la clave es la tradición: una receta, composición o método de producción reconocido. A diferencia de DOP e IGP, el concepto no exige que el producto proceda de una única región determinada. La protección se centra en que el nombre reservado se utilice para un producto que respeta el método o composición registrados.</p>
<h2>El sello no sustituye al nombre registrado</h2>
<p>Al comprar, no basta con reconocer un logotipo de colores. Conviene leer <strong>el nombre completo de la figura protegida</strong> y comprobar que corresponde a un registro real. Expresiones como “estilo”, “tipo”, “tradicional” o una referencia geográfica en una marca no equivalen automáticamente a DOP, IGP o ETG.</p>
<h2>Cómo comprobar si una denominación es auténtica</h2>
<ol>
<li>Busca el nombre completo protegido, no solo una referencia al territorio.</li>
<li>Comprueba el logotipo europeo cuando corresponda y las identificaciones del consejo regulador o sistema de control.</li>
<li>Consulta el registro oficial europeo si tienes dudas sobre un nombre.</li>
<li>Lee la etiqueta completa: el sello no sustituye a la información sobre categoría, ingredientes, productor o formato.</li>
</ol>
<h2>Cómo encaja este sistema en los alimentos de El Mercado de Origen</h2>
<p>La lógica se entiende mejor con productos concretos. Puedes ampliar con nuestra '.$dop_es.', la guía de '.$hamdop_es.' y la explicación de las '.$oildop_es.'. En esas páginas el foco ya no es la diferencia jurídica general entre DOP, IGP y ETG, sino qué protege cada denominación concreta.</p>
<h2>DOP no significa automáticamente “artesano”, “ecológico” ni “de bellota”</h2>
<p>Son conceptos distintos. Una figura geográfica protege un nombre bajo un pliego; “ecológico” responde a otra normativa; “artesano” depende del contexto y de la regulación aplicable; y categorías como bellota o cebo pertenecen al sistema específico del ibérico. Pueden coincidir en un mismo producto, pero <strong>una palabra no implica automáticamente las otras</strong>.</p>
<h2>La idea que merece la pena recordar</h2>
<p>Si ves DOP o IGP, piensa primero en <strong>vínculo entre nombre, territorio y pliego</strong>. Si ves ETG, piensa en <strong>tradición de composición o método</strong>. Después, para elegir entre dos productos concretos, sigue mirando materia prima, productor, elaboración, maduración o cosecha y el perfil que realmente buscas.</p>
<h2>Fuentes oficiales</h2>
<ul><li><a href="'.$source_eu_quality.'" rel="nofollow noopener">Comisión Europea: indicaciones geográficas y regímenes de calidad</a>.</li></ul>',
 'en_content'=>
'<p>PDO, PGI and TSG appear on cheese, olive oil, ham, cured meats and many other European foods, but <strong>they do not mean the same thing and they are not a ranking from “best” to “worst”</strong>. They are regulated quality schemes protecting registered names and defined production rules. Understanding the difference helps avoid two common mistakes: assuming every traditional product has a geographical indication, and assuming one label guarantees an identical flavour from every producer.</p>
<h2>Quick guide: what each abbreviation means</h2>
<table><thead><tr><th>Scheme</th><th>Main purpose</th><th>Link with place</th></tr></thead><tbody>
<tr><td><strong>PDO</strong></td><td>A registered name closely linked to a defined place, know-how and product characteristics</td><td>The essential production, processing and preparation stages are tied to the defined area</td></tr>
<tr><td><strong>PGI</strong></td><td>A registered name whose quality, reputation or another characteristic is linked to geographical origin</td><td>At least one relevant production stage is linked to the area</td></tr>
<tr><td><strong>TSG</strong></td><td>A traditional composition or production method</td><td>It does not necessarily protect one geographical origin</td></tr>
</tbody></table>
<p>The exact legal wording depends on the applicable EU rules and product specification. The useful distinction is that <strong>PDO and PGI are geographical indications, while TSG protects tradition</strong>.</p>
<h2>What a PDO protects</h2>
<p>PDO means <strong>Protected Designation of Origin</strong>. The registered name identifies a product with a strong connection to a defined place and specification. The specification may set out the geographical area, authorised raw materials, breeds or varieties where relevant, processing, maturation, controls and product characteristics.</p>
<p>This does not make every product within one PDO taste identical. Producers can still differ in raw material, harvest, ageing, curing time or house style within the common rules. A PDO defines what must be respected; <strong>it does not reduce flavour to one formula</strong>.</p>
<h2>What a PGI protects</h2>
<p>PGI means <strong>Protected Geographical Indication</strong>. It also protects a name connected with a place, but the required production link is different from a PDO. Broadly, at least one relevant stage must be linked to the area and a quality, reputation or other characteristic must be attributable to the geographical origin.</p>
<p>That is why PDO should not be read as “higher quality” and PGI as “lower quality”. They are <strong>different legal models for linking a product name with a place</strong>.</p>
<h2>What a TSG protects</h2>
<p>TSG means <strong>Traditional Speciality Guaranteed</strong>. The core idea is tradition: a recognised recipe, composition or method of production. Unlike PDO and PGI, it does not require the food to come from one specific region. Protection centres on the registered traditional characteristics.</p>
<h2>The logo does not replace the registered name</h2>
<p>When buying, do not rely only on recognising a coloured EU symbol. Read the <strong>full registered name</strong>. Expressions such as “style”, “type”, “traditional” or a geographical reference in a brand name do not automatically create PDO, PGI or TSG status.</p>
<h2>How to verify a protected name</h2>
<ol><li>Look for the full protected name, not merely a place reference.</li><li>Check the EU logo where applicable and any official certification identifiers.</li><li>Use the official EU register if a name is unclear.</li><li>Read the rest of the label: a quality scheme does not replace category, ingredients, producer or format information.</li></ol>
<h2>Examples across Spanish foods</h2>
<p>You can apply this framework to our '.$dop_en.', '.$hamdop_en.' and '.$oildop_en.'. Those guides focus on what the individual registered names protect rather than repeating the general legal distinction.</p>
<h2>PDO does not automatically mean artisan, organic or acorn-fed</h2>
<p>These are separate concepts. A geographical indication protects a registered name under a specification; organic production follows different rules; artisan terminology depends on context and applicable law; and acorn-fed or grain-fed categories belong to the specific Iberian system. They may coexist on one product, but <strong>one term does not automatically imply the others</strong>.</p>
<h2>The useful takeaway</h2>
<p>When you see PDO or PGI, think first about the <strong>link between a registered name, a place and a specification</strong>. When you see TSG, think about a <strong>traditional composition or method</strong>. Then compare the details that matter for the actual product: raw material, producer, processing, maturation or harvest and the style you want.</p>
<h2>Official source</h2><ul><li><a href="'.$source_eu_quality.'" rel="nofollow noopener">European Commission: geographical indications and quality schemes</a>.</li></ul>'
];

$items[]=[
 'title'=>'Cómo leer la etiqueta de un alimento: ingredientes, nutrición, origen, lote y conservación',
 'slug'=>'como-leer-etiqueta-alimento-ingredientes-nutricion-origen-lote-conservacion',
 'excerpt'=>'Guía práctica para leer una etiqueta alimentaria sin perderse entre ingredientes, alérgenos, información nutricional, origen, fechas, lote y conservación.',
 'seo_title'=>'Cómo leer la etiqueta de un alimento: guía completa',
 'seo_description'=>'Aprende a leer una etiqueta alimentaria: ingredientes, alérgenos, nutrición, fechas, origen, operador, lote y conservación, paso a paso.',
 'en_title'=>'How to read a food label: ingredients, nutrition, origin, batch and storage',
 'en_slug'=>'how-to-read-food-label-ingredients-nutrition-origin-batch-storage',
 'en_excerpt'=>'A practical guide to reading a food label: ingredients, allergens, nutrition, dates, origin, business operator, batch and storage instructions.',
 'en_seo_title'=>'How to read a food label: a practical complete guide',
 'en_seo_description'=>'Learn how to read a food label: ingredients, allergens, nutrition, dates, origin, operator details, batch and storage, step by step.',
 'content'=>
'<p>Una etiqueta alimentaria contiene mucha información, pero <strong>no toda responde a la misma pregunta</strong>. La lista de ingredientes explica de qué está hecho el producto; la tabla nutricional cuantifica nutrientes; la fecha informa sobre duración o seguridad según el caso; y el origen solo es obligatorio bajo determinadas reglas. Leerlo todo como si fuera un único “indicador de calidad” lleva a conclusiones equivocadas.</p>
<h2>El orden más útil para leer una etiqueta</h2>
<ol><li><strong>Denominación del alimento:</strong> identifica legalmente qué producto es.</li><li><strong>Ingredientes y alérgenos:</strong> explican composición y sustancias que requieren especial atención.</li><li><strong>Cantidad neta:</strong> indica cuánto producto compras.</li><li><strong>Fecha y conservación:</strong> dicen hasta cuándo y en qué condiciones debe mantenerse.</li><li><strong>Información nutricional:</strong> permite comparar energía y nutrientes sobre una base común.</li><li><strong>Operador responsable y origen cuando proceda:</strong> ayudan a identificar quién comercializa el alimento y, en determinados casos, su procedencia.</li><li><strong>Lote:</strong> permite identificar una partida concreta en la cadena alimentaria.</li></ol>
<h2>1. Empieza por la denominación, no por el nombre de marketing</h2>
<p>La parte más grande del frontal puede ser una marca o una frase comercial. La <strong>denominación legal o descriptiva</strong> es la que te dice qué alimento estás comprando. Es especialmente útil cuando dos envases parecen similares pero pertenecen a categorías distintas, como ocurre con diferentes tipos de aceite o productos cárnicos.</p>
<h2>2. Cómo leer la lista de ingredientes</h2>
<p>Los ingredientes se presentan generalmente en <strong>orden decreciente de peso en el momento de su utilización</strong>. Eso ayuda a entender qué componentes predominan. En determinados casos, la cantidad de un ingrediente debe declararse cuando aparece en el nombre, se destaca mediante palabras o imágenes o resulta esencial para caracterizar el alimento.</p>
<p>Una lista más corta no es automáticamente mejor y una lista larga no convierte un alimento en malo. La pregunta correcta es si los ingredientes corresponden al producto que esperabas comprar.</p>
<h2>3. Alérgenos: deben distinguirse dentro de la lista</h2>
<p>La normativa europea exige destacar las sustancias o productos que causan alergias o intolerancias incluidos en la lista legal. El formato puede variar —negrita, tipografía o contraste—, pero deben ser identificables. Quien tenga una alergia diagnosticada no debería basarse en impresiones generales del envase, sino en la información específica del producto.</p>
<h2>4. Tabla nutricional: compárala sobre la misma base</h2>
<p>La declaración nutricional suele expresar valores por <strong>100 g o 100 ml</strong>, lo que permite comparar productos de forma coherente. Puede añadirse información por ración, pero una “ración” no siempre tiene el mismo tamaño entre fabricantes. Energía, grasas, saturadas, hidratos, azúcares, proteínas y sal responden a preguntas diferentes; ningún dato aislado resume todo el alimento.</p>
<h2>5. Fecha de caducidad y consumo preferente no son equivalentes</h2>
<p>La etiqueta puede llevar una fecha de consumo preferente o una fecha de caducidad según las características del alimento. No deben interpretarse del mismo modo. Tenemos una guía específica sobre <a href="'.home_url('/fecha-caducidad-consumo-preferente-diferencias/').'">caducidad y consumo preferente</a>, porque aquí basta con recordar que una se relaciona principalmente con duración de calidad y la otra con seguridad en alimentos muy perecederos.</p>
<h2>6. Conservación antes y después de abrir</h2>
<p>Lee siempre frases como “conservar refrigerado”, “una vez abierto…” o “proteger de la luz y el calor”. La fecha del envase <strong>no puede separarse de las condiciones de conservación</strong>. Abrir el producto también puede cambiar por completo su duración práctica.</p>
<h2>7. Qué significa el origen</h2>
<p>No todos los alimentos tienen la misma obligación de indicar país de origen o lugar de procedencia. Existen reglas generales y normas específicas por sector. Además, cuando se declara un origen para el alimento y su ingrediente primario procede de otro lugar, pueden activarse requisitos adicionales. Lo explicamos con detalle en nuestra guía sobre <a href="'.home_url('/origen-etiqueta-alimento-pais-procedencia-ingrediente-primario/').'">origen, procedencia e ingrediente primario</a>.</p>
<h2>8. Operador, lote y trazabilidad</h2>
<p>La etiqueta identifica al operador responsable de la información alimentaria y normalmente incluye un lote o sistema equivalente de identificación de partida. El lote no es una puntuación de calidad: es una herramienta para relacionar unidades de producción y facilitar controles, incidencias y retiradas. Para ampliar este punto, consulta nuestra guía de <a href="'.home_url('/trazabilidad-alimentaria-que-es-como-funciona-productor-consumidor/').'">trazabilidad alimentaria</a>.</p>
<h2>9. Cómo leer sellos como DOP, IGP o ETG</h2>
<p>Un sello europeo de calidad aporta información distinta de la tabla nutricional. Si aparece una figura protegida, puedes consultar nuestra guía sobre <a href="'.home_url('/dop-igp-etg-diferencias-sellos-calidad-alimentos/').'">DOP, IGP y ETG</a> para saber qué protege exactamente.</p>
<h2>Ejemplos de etiquetas específicas</h2>
<p>Una guía general no debe repetir las reglas particulares de cada producto. Para aplicar el método, puedes ver '.$label_cheese_es.', '.$label_oil_es.' y '.$label_pres_es.'.</p>
<h2>La lectura en 30 segundos</h2>
<p>Si tienes poco tiempo, identifica primero <strong>qué producto es</strong>, después <strong>de qué está hecho</strong>, luego <strong>cómo debe conservarse y hasta cuándo</strong>, y finalmente compara la <strong>tabla nutricional</strong> si esa información es relevante para tu elección. El resto —origen, sellos, lote o menciones voluntarias— añade contexto, pero no sustituye esos cuatro pasos.</p>
<h2>Fuente normativa</h2><ul><li><a href="'.$source_reg_1169.'" rel="nofollow noopener">Reglamento (UE) 1169/2011 sobre información alimentaria facilitada al consumidor, versión consolidada</a>.</li></ul>',
 'en_content'=>
'<p>A food label contains a great deal of information, but <strong>each part answers a different question</strong>. The ingredients list explains what the food is made from; the nutrition declaration quantifies nutrients; the date concerns durability or safety depending on the type; and origin is mandatory only under certain rules. Treating all of this as one single “quality score” leads to poor comparisons.</p>
<h2>A useful order for reading a label</h2>
<ol><li><strong>Name of the food:</strong> identifies what the product legally is.</li><li><strong>Ingredients and allergens:</strong> explain composition and substances requiring attention.</li><li><strong>Net quantity:</strong> tells you how much food you are buying.</li><li><strong>Date and storage conditions:</strong> explain durability and how the product must be kept.</li><li><strong>Nutrition declaration:</strong> lets you compare energy and nutrients on a common basis.</li><li><strong>Responsible food business and origin where required:</strong> identify the operator and, in certain cases, provenance.</li><li><strong>Batch or lot:</strong> identifies a production batch for traceability.</li></ol>
<h2>1. Start with the name of the food, not the marketing headline</h2>
<p>The largest words on the front may be a brand or marketing phrase. The <strong>legal or descriptive name</strong> tells you what you are actually buying. This matters when packages look similar but belong to different legal categories, as can happen with olive oils or meat products.</p>
<h2>2. How to read the ingredients list</h2>
<p>Ingredients are generally listed in <strong>descending order of weight when used</strong>. This helps show which components predominate. In certain circumstances, the percentage of an ingredient must be declared when it appears in the product name, is emphasised in words or pictures, or is essential to characterise the food.</p>
<p>A shorter ingredient list is not automatically better, and a longer one does not automatically make a food worse. The useful question is whether the ingredients match the product you intended to buy.</p>
<h2>3. Allergens must stand out</h2>
<p>EU rules require listed substances or products causing allergies or intolerances to be emphasised within the ingredients information. The exact typography can vary, but they must be distinguishable. Anyone with a diagnosed allergy should rely on the product-specific information rather than general impressions of the packaging.</p>
<h2>4. Nutrition: compare like with like</h2>
<p>The nutrition declaration is normally given per <strong>100 g or 100 ml</strong>, which creates a common comparison basis. Per-serving information may also appear, but serving sizes can differ between manufacturers. Energy, fat, saturates, carbohydrate, sugars, protein and salt answer different questions; no single figure summarises the whole food.</p>
<h2>5. Use-by and best-before are not interchangeable</h2>
<p>A label may carry a best-before or a use-by date depending on the food. They should not be interpreted in the same way. See our dedicated guide to <a href="'.home_url('/en/use-by-vs-best-before-date-differences-food-safety/').'">use-by and best-before dates</a>; for this page, the essential point is that one primarily concerns quality durability while the other is used for safety on highly perishable foods.</p>
<h2>6. Storage before and after opening</h2>
<p>Always read instructions such as “keep refrigerated”, “once opened…” or “protect from light and heat”. A printed date <strong>cannot be separated from storage conditions</strong>. Opening a package may also change its practical shelf life completely.</p>
<h2>7. What origin means</h2>
<p>Not every food has the same duty to state country of origin or place of provenance. General rules coexist with sector-specific rules. Where an origin is declared for the food and its primary ingredient comes from somewhere else, additional requirements may apply. Our guide to <a href="'.home_url('/en/origin-food-label-country-provenance-primary-ingredient/').'">origin, provenance and primary ingredients</a> covers this distinction.</p>
<h2>8. Operator, lot and traceability</h2>
<p>The label identifies the food business responsible for the information and normally carries a lot or equivalent batch identifier. A lot number is not a quality score; it is a tool for linking units from a production run and supporting controls, investigations and recalls. See our guide to <a href="'.home_url('/en/food-traceability-what-it-is-how-it-works-producer-consumer/').'">food traceability</a>.</p>
<h2>9. How to read PDO, PGI and TSG labels</h2>
<p>An EU quality scheme conveys different information from the nutrition table. If a protected name appears, our guide to <a href="'.home_url('/en/pdo-pgi-tsg-differences-eu-food-quality-labels/').'">PDO, PGI and TSG</a> explains what each scheme protects.</p>
<h2>Product-specific label guides</h2>
<p>A general guide should not repeat every sector-specific rule. Apply the framework through our guides to '.$label_cheese_en.', '.$label_oil_en.' and '.$label_pres_en.'.</p>
<h2>The 30-second label check</h2>
<p>If time is short, first identify <strong>what the product is</strong>, then <strong>what it is made from</strong>, then <strong>how it must be stored and until when</strong>, and finally compare the <strong>nutrition declaration</strong> if nutrition matters to your decision. Origin, schemes, lot numbers and voluntary claims add context but do not replace those basics.</p>
<h2>Regulatory source</h2><ul><li><a href="'.$source_reg_1169.'" rel="nofollow noopener">Regulation (EU) No 1169/2011 on food information to consumers, consolidated version</a>.</li></ul>'
];

$items[]=[
 'title'=>'Fecha de caducidad vs consumo preferente: diferencias y qué significa cada una',
 'slug'=>'fecha-caducidad-consumo-preferente-diferencias',
 'excerpt'=>'Caducidad y consumo preferente no significan lo mismo. Aprende qué indica cada fecha, cuándo afecta a la seguridad y cómo influye la conservación.',
 'seo_title'=>'Caducidad vs consumo preferente: diferencias y significado',
 'seo_description'=>'Qué diferencia hay entre fecha de caducidad y consumo preferente, cuándo afecta a la seguridad y cómo interpretar cada fecha correctamente.',
 'en_title'=>'Use-by vs best-before dates: the difference and what each means',
 'en_slug'=>'use-by-vs-best-before-date-differences-food-safety',
 'en_excerpt'=>'Use-by and best-before dates do not mean the same thing. Learn what each date indicates, when safety is involved and why storage conditions matter.',
 'en_seo_title'=>'Use-by vs best-before dates: what is the difference?',
 'en_seo_description'=>'Understand the difference between use-by and best-before dates, when food safety is involved and how storage conditions change interpretation.',
 'content'=>
'<p>“Fecha de caducidad” y “consumo preferente” se parecen visualmente en un envase, pero <strong>no transmiten el mismo mensaje</strong>. Una está destinada a alimentos muy perecederos desde el punto de vista microbiológico y marca un límite de seguridad; la otra indica el periodo durante el cual el alimento conserva sus propiedades específicas si se almacena correctamente.</p>
<h2>La diferencia en una tabla</h2>
<table><thead><tr><th></th><th>Fecha de caducidad</th><th>Consumo preferente</th></tr></thead><tbody>
<tr><td><strong>Pregunta principal</strong></td><td>Seguridad</td><td>Calidad/durabilidad</td></tr>
<tr><td><strong>Se usa en</strong></td><td>Alimentos microbiológicamente muy perecederos</td><td>Alimentos que pueden perder cualidades antes de convertirse necesariamente en inseguros</td></tr>
<tr><td><strong>Después de la fecha</strong></td><td>No debe consumirse</td><td>Puede seguir siendo apto si se ha conservado correctamente y el envase está íntegro, aunque puede perder calidad</td></tr>
</tbody></table>
<h2>Qué significa la fecha de caducidad</h2>
<p>La fecha de caducidad se utiliza cuando, tras un periodo relativamente corto, el alimento puede constituir un peligro inmediato para la salud humana desde el punto de vista microbiológico. Por eso <strong>no debe tratarse como una simple recomendación de calidad</strong>. Una vez superada, no corresponde decidir por olor o aspecto si “parece bien”.</p>
<h2>Qué significa el consumo preferente</h2>
<p>El consumo preferente señala la fecha hasta la que el alimento mantiene sus propiedades específicas cuando se conserva correctamente. Una vez superada, pueden disminuir aroma, textura, color, frescura o estabilidad, pero <strong>la fecha por sí sola no convierte automáticamente el alimento en inseguro</strong>.</p>
<p>Eso tampoco significa que todo pueda comerse indefinidamente. Hay que respetar las condiciones de almacenamiento, revisar que el envase esté íntegro y valorar el producto según su categoría. Un aceite oxidado, una conserva con el envase alterado o un alimento mal almacenado puede no ser adecuado aunque la fecha impresa todavía no haya llegado.</p>
<h2>La conservación forma parte de la fecha</h2>
<p>Una fecha solo tiene sentido junto a instrucciones como “conservar entre 0 y 5 ºC”, “mantener en lugar fresco y seco” o “una vez abierto, consumir en…”. Si se rompe la cadena de frío o el envase se mantiene en condiciones inadecuadas, <strong>la fecha deja de ser una garantía independiente</strong>.</p>
<h2>Qué cambia al abrir el envase</h2>
<p>La fecha impresa suele referirse al producto sin abrir y conservado según las instrucciones. Tras la apertura pueden entrar oxígeno, humedad o microorganismos y cambiar las condiciones del alimento. Por eso las instrucciones posteriores a la apertura tienen prioridad práctica sobre la idea de que “todavía faltan meses para la fecha”.</p>
<h2>¿Se puede decidir solo por olor, color o sabor?</h2>
<p>No. Los sentidos sirven para detectar algunos deterioros de calidad, pero <strong>no pueden confirmar la ausencia de microorganismos peligrosos</strong>. Esto es especialmente importante en productos con fecha de caducidad. En alimentos con consumo preferente, el aspecto y el olor pueden ayudar a valorar calidad junto con el estado del envase y la conservación, pero no sustituyen las reglas de seguridad de cada producto.</p>
<h2>Ejemplos que ayudan a no confundir conceptos</h2>
<p>En las '.$expiry_pres_es.' tratamos envases estables y señales de deterioro; en nuestra guía sobre '.$expiry_oil_es.' el problema central es la oxidación y pérdida de calidad; y para productos muy perecederos resulta más útil una guía de seguridad como '.$meat_safe_es.'. Son problemas diferentes, aunque todos tengan una fecha o duración asociada.</p>
<h2>¿Y si un alimento está cerca de la fecha?</h2>
<p>Planifica el consumo, congela solo cuando el producto lo permita y la congelación se haga <strong>antes</strong> del límite de seguridad, y sigue las indicaciones específicas del fabricante. No tiene sentido descartar automáticamente un producto con consumo preferente solo porque la fecha acaba de pasar; tampoco tiene sentido asumir que una fecha de caducidad vencida puede “rescatarse” cocinando.</p>
<h2>La regla sencilla</h2>
<p><strong>Caducidad: seguridad, no consumir después.</strong> <strong>Consumo preferente: calidad mínima, interpretar junto con conservación y estado del envase.</strong> Y en ambos casos, si el envase está hinchado, roto, pierde líquido o el alimento se ha conservado de forma insegura, la fecha no compensa ese problema.</p>
<h2>Fuente oficial</h2><ul><li><a href="'.$source_aesan.'" rel="nofollow noopener">AESAN: preguntas frecuentes para la ciudadanía sobre fechas y seguridad alimentaria</a>.</li><li><a href="'.$source_reg_1169.'" rel="nofollow noopener">Reglamento (UE) 1169/2011</a>.</li></ul>',
 'en_content'=>
'<p>“Use by” and “best before” can look similar on a package, but <strong>they do not communicate the same thing</strong>. A use-by date applies to foods that are highly perishable from a microbiological point of view and marks a safety limit. A best-before date indicates the period during which a food retains its specific properties when stored correctly.</p>
<h2>The difference at a glance</h2>
<table><thead><tr><th></th><th>Use by</th><th>Best before</th></tr></thead><tbody><tr><td><strong>Main question</strong></td><td>Safety</td><td>Quality and durability</td></tr><tr><td><strong>Used for</strong></td><td>Highly microbiologically perishable foods</td><td>Foods that may lose quality before necessarily becoming unsafe</td></tr><tr><td><strong>After the date</strong></td><td>Do not consume</td><td>May still be suitable if stored correctly and the package is intact, although quality can decline</td></tr></tbody></table>
<h2>What a use-by date means</h2>
<p>A use-by date is used where, after a relatively short period, a food may constitute an immediate danger to human health from a microbiological point of view. It should therefore <strong>not be treated as a mere quality suggestion</strong>. Once it has passed, smell or appearance cannot establish that the food is safe.</p>
<h2>What a best-before date means</h2>
<p>A best-before date indicates how long a food retains its specific properties when properly stored. After that date, aroma, texture, colour, freshness or stability can deteriorate, but <strong>the date alone does not automatically make the food unsafe</strong>.</p>
<p>This does not mean foods last indefinitely. Storage instructions still matter, the package should be intact and the nature of the product matters. Oxidised oil, a damaged can or badly stored food may be unsuitable even before the printed date.</p>
<h2>Storage conditions are part of the date</h2>
<p>A date only makes sense alongside instructions such as “keep refrigerated”, “store in a cool dry place” or “once opened, consume within…”. If the cold chain is broken or the food is stored incorrectly, <strong>the printed date is not an independent guarantee</strong>.</p>
<h2>What changes after opening</h2>
<p>The printed date normally relates to an unopened product stored as instructed. Opening can introduce oxygen, moisture or microorganisms and change the food environment. That is why after-opening instructions matter more in practice than the fact that the printed date may still be months away.</p>
<h2>Can smell, colour or taste decide safety?</h2>
<p>No. Sensory checks can reveal some quality deterioration, but <strong>they cannot prove the absence of dangerous microorganisms</strong>. This is especially important for use-by foods. For best-before foods, appearance and smell can help assess quality together with packaging and storage, but they do not replace product-specific safety rules.</p>
<h2>Different foods, different deterioration problems</h2>
<p>Our guide to '.$expiry_pres_en.' focuses on shelf-stable packaging and spoilage signs; '.$expiry_oil_en.' is mainly about oxidation and loss of quality; and '.$meat_safe_en.' addresses a much more perishable product. The presence of a date does not make these the same problem.</p>
<h2>If a food is close to its date</h2>
<p>Plan consumption, freeze only when appropriate and <strong>before</strong> the relevant safety limit, and follow the manufacturer’s specific instructions. A best-before food need not automatically be thrown away the day after its date; a food past its use-by date should not be “rescued” simply by cooking it.</p>
<h2>The simple rule</h2>
<p><strong>Use by: safety; do not consume after the date.</strong> <strong>Best before: minimum quality; interpret it together with storage and package condition.</strong> In either case, swollen, leaking or damaged packaging or unsafe storage conditions override any reassurance from the printed date.</p>
<h2>Official sources</h2><ul><li><a href="'.$source_aesan.'" rel="nofollow noopener">AESAN: consumer food-safety FAQs</a>.</li><li><a href="'.$source_reg_1169.'" rel="nofollow noopener">Regulation (EU) No 1169/2011</a>.</li></ul>'
];

$items[]=[
 'title'=>'Qué significa el origen en la etiqueta de un alimento: país, procedencia e ingrediente primario',
 'slug'=>'origen-etiqueta-alimento-pais-procedencia-ingrediente-primario',
 'excerpt'=>'Qué significa el origen de un alimento en la etiqueta, cuándo es obligatorio, qué es el lugar de procedencia y cómo funciona el ingrediente primario.',
 'seo_title'=>'Origen en la etiqueta: país, procedencia e ingrediente primario',
 'seo_description'=>'Guía sobre el origen en las etiquetas de alimentos: cuándo es obligatorio, diferencia entre origen y procedencia y qué es el ingrediente primario.',
 'en_title'=>'What origin means on a food label: country, provenance and primary ingredient',
 'en_slug'=>'origin-food-label-country-provenance-primary-ingredient',
 'en_excerpt'=>'What country of origin and place of provenance mean on food labels, when origin is mandatory and how primary-ingredient origin rules work.',
 'en_seo_title'=>'Food-label origin: country, provenance and primary ingredient',
 'en_seo_description'=>'Understand origin on food labels: when it is mandatory, country of origin versus provenance and how primary-ingredient origin rules work.',
 'content'=>
'<p>“Origen”, “procedencia”, “elaborado en”, “envasado en” y el domicilio de una empresa pueden aparecer juntos en un alimento, pero <strong>no significan necesariamente que la materia prima proceda del mismo lugar</strong>. La normativa europea obliga a indicar el país de origen o lugar de procedencia en determinados casos y sectores, y también establece reglas para evitar que una presentación induzca a error.</p>
<h2>País de origen y lugar de procedencia: conceptos relacionados, no idénticos</h2>
<p>En la legislación alimentaria europea, el <strong>país de origen</strong> se relaciona con las reglas de origen aplicables, mientras que el <strong>lugar de procedencia</strong> es el lugar del que se dice que procede el alimento cuando no coincide con el país de origen definido jurídicamente. Para el consumidor, la idea útil es que una referencia geográfica puede describir cosas diferentes: origen legal, procedencia declarada, lugar de elaboración o sede del operador.</p>
<h2>¿Todos los alimentos tienen que indicar origen?</h2>
<p>No de la misma manera. Existen productos y sectores con obligaciones específicas, y además la normativa general exige indicar origen o procedencia <strong>cuando omitirlo pudiera inducir a error</strong>, especialmente si otros elementos de la etiqueta sugieren un origen distinto.</p>
<p>Por eso no conviene esperar una frase idéntica en un aceite, una carne, un queso protegido y una conserva. Cada producto puede estar sujeto a reglas sectoriales adicionales.</p>
<h2>El domicilio de la empresa no es automáticamente el origen del alimento</h2>
<p>Que una empresa tenga dirección en Madrid, Córdoba o Asturias identifica al operador responsable, pero <strong>no demuestra por sí solo dónde se produjo la materia prima</strong>. Tampoco “envasado en España” equivale necesariamente a “materia prima española”. Hay que leer qué afirma exactamente la etiqueta.</p>
<h2>Qué es el ingrediente primario</h2>
<p>La normativa utiliza el concepto de <strong>ingrediente primario</strong> para el ingrediente o ingredientes que representan más del 50 % del alimento o que el consumidor asocia normalmente con su denominación y para los que, en determinados casos, se requiere una indicación cuantitativa.</p>
<p>Cuando se declara el origen o procedencia del alimento y el ingrediente primario tiene un origen diferente, la legislación europea puede exigir que se indique también ese origen diferente o que se informe de que el ingrediente primario no procede del origen declarado para el alimento.</p>
<h2>Un ejemplo conceptual</h2>
<p>Imagina un alimento que destaca de forma clara un país o región en su presentación, pero cuya materia prima principal procede de otra zona. Dependiendo del caso y de las reglas aplicables, la etiqueta debe evitar que el consumidor interprete que ambos orígenes son necesariamente el mismo. La solución legal puede consistir en declarar el origen del ingrediente primario o señalar que es distinto.</p>
<h2>DOP e IGP añaden otra capa de significado</h2>
<p>Una DOP o IGP no es simplemente una frase de procedencia. Es un nombre registrado con un pliego y controles. Si quieres separar ambos conceptos, consulta nuestra guía sobre <a href="'.home_url('/dop-igp-etg-diferencias-sellos-calidad-alimentos/').'">DOP, IGP y ETG</a>. Un producto puede tener un origen declarado sin pertenecer a una figura protegida, y una figura protegida impone condiciones que van más allá de poner un lugar en la etiqueta.</p>
<h2>Cómo leer el origen sin sacar conclusiones demasiado rápidas</h2>
<ol><li>Identifica si la frase habla del <strong>producto</strong>, del <strong>ingrediente</strong>, del <strong>lugar de elaboración</strong> o del <strong>operador</strong>.</li><li>Busca si existe una obligación sectorial específica para ese alimento.</li><li>Si el envase destaca un territorio, revisa si también aclara el origen del ingrediente primario.</li><li>No conviertas el origen en una puntuación automática de calidad: procedencia y calidad sensorial no son sinónimos.</li></ol>
<h2>Ejemplos en categorías concretas</h2>
<p>El modo de aplicar estas reglas cambia según el producto. Por eso disponemos de guías específicas como '.$label_oil_es.' y '.$label_pres_es.'. Para quesos con figuras protegidas, también resulta útil '.$label_cheese_es.'.</p>
<h2>Origen y trazabilidad tampoco son lo mismo</h2>
<p>El origen es información que puede aparecer en la etiqueta bajo determinadas reglas; la trazabilidad es la capacidad del sistema para seguir el alimento, pienso, animal productor o sustancia relevante a lo largo de las etapas de producción, transformación y distribución. Puedes ampliar esta diferencia en nuestra guía de <a href="'.home_url('/trazabilidad-alimentaria-que-es-como-funciona-productor-consumidor/').'">trazabilidad alimentaria</a>.</p>
<h2>Fuente oficial</h2><ul><li><a href="'.$source_origin.'" rel="nofollow noopener">Comisión Europea: origin labelling</a>.</li><li><a href="'.$source_reg_1169.'" rel="nofollow noopener">Reglamento (UE) 1169/2011</a>.</li></ul>',
 'en_content'=>
'<p>“Origin”, “provenance”, “made in”, “packed in” and a company address may all appear on the same food, but <strong>they do not necessarily mean the raw material comes from the same place</strong>. EU rules require country of origin or place of provenance in certain cases and sectors, and they also aim to prevent presentation that could mislead consumers.</p>
<h2>Country of origin and place of provenance</h2>
<p>Under EU food-information rules, <strong>country of origin</strong> relates to the applicable legal origin rules, while <strong>place of provenance</strong> is the place from which a food is stated to come when that is not the legally defined country of origin. The practical point is that a geographical reference can describe different things: legal origin, stated provenance, place of processing or the business operator’s address.</p>
<h2>Must every food state origin?</h2>
<p>Not in the same way. Some products and sectors have specific origin requirements, while the general rules also require origin or provenance <strong>where omission could mislead</strong>, particularly where other information on the label suggests a different origin.</p>
<p>That is why you should not expect the same wording on olive oil, meat, protected cheese and preserved vegetables. Sector-specific rules may add further requirements.</p>
<h2>A company address is not automatically the food’s origin</h2>
<p>A business address in Madrid, Córdoba or Asturias identifies the responsible operator but <strong>does not by itself prove where the main raw material was produced</strong>. Likewise, “packed in Spain” does not necessarily mean “Spanish raw material”. Read exactly what the statement refers to.</p>
<h2>What is a primary ingredient?</h2>
<p>EU rules use the concept of a <strong>primary ingredient</strong> for an ingredient or ingredients representing more than 50% of a food or normally associated by consumers with the name of the food and for which a quantitative indication is required in certain cases.</p>
<p>Where the origin or provenance of the food is stated and the primary ingredient has a different origin, EU law may require the different origin to be indicated or a statement that the primary ingredient does not come from the origin presented for the food.</p>
<h2>A conceptual example</h2>
<p>Imagine a food that prominently presents a country or region while its main raw material comes from elsewhere. Depending on the case and applicable rules, the label must avoid implying that both origins are necessarily the same. This may mean stating the primary ingredient’s origin or clearly saying that it differs.</p>
<h2>PDO and PGI add another layer</h2>
<p>A PDO or PGI is not merely a provenance phrase. It is a registered name governed by a specification and controls. Our guide to <a href="'.home_url('/en/pdo-pgi-tsg-differences-eu-food-quality-labels/').'">PDO, PGI and TSG</a> explains the difference. A food can state an origin without belonging to a protected scheme, while a protected scheme imposes requirements beyond placing a location on the pack.</p>
<h2>How to read origin claims carefully</h2>
<ol><li>Identify whether the statement refers to the <strong>food</strong>, an <strong>ingredient</strong>, the <strong>place of processing</strong> or the <strong>operator</strong>.</li><li>Check whether sector-specific origin rules apply.</li><li>If the pack strongly highlights a place, look for clarification about the primary ingredient.</li><li>Do not turn origin into an automatic quality score: provenance and sensory quality are not synonyms.</li></ol>
<h2>Product-specific examples</h2>
<p>Application differs by category, which is why we also have '.$label_oil_en.', '.$label_pres_en.' and '.$label_cheese_en.'.</p>
<h2>Origin and traceability are not the same</h2>
<p>Origin is label information governed by specific rules; traceability is the system’s ability to follow food, feed, food-producing animals or relevant substances through production, processing and distribution. See our guide to <a href="'.home_url('/en/food-traceability-what-it-is-how-it-works-producer-consumer/').'">food traceability</a>.</p>
<h2>Official sources</h2><ul><li><a href="'.$source_origin.'" rel="nofollow noopener">European Commission: origin labelling</a>.</li><li><a href="'.$source_reg_1169.'" rel="nofollow noopener">Regulation (EU) No 1169/2011</a>.</li></ul>'
];

$items[]=[
 'title'=>'Trazabilidad alimentaria: qué es y cómo se sigue un alimento del productor al consumidor',
 'slug'=>'trazabilidad-alimentaria-que-es-como-funciona-productor-consumidor',
 'excerpt'=>'Qué es la trazabilidad alimentaria, qué información conecta cada etapa de la cadena y para qué sirven el lote, proveedores, registros y retiradas.',
 'seo_title'=>'Trazabilidad alimentaria: qué es y cómo funciona',
 'seo_description'=>'Qué es la trazabilidad alimentaria, cómo conecta productor, transformador y distribuidor, y para qué sirven lotes, registros y retiradas.',
 'en_title'=>'Food traceability: what it is and how food is tracked from producer to consumer',
 'en_slug'=>'food-traceability-what-it-is-how-it-works-producer-consumer',
 'en_excerpt'=>'What food traceability is, how information connects each stage of the supply chain and how lots, supplier records and recalls fit together.',
 'en_seo_title'=>'Food traceability: what it is and how it works',
 'en_seo_description'=>'What food traceability is, how producers, processors and distributors connect records, and how lots, supplier information and recalls work.',
 'content'=>
'<p>La trazabilidad alimentaria es la capacidad de <strong>seguir el rastro de un alimento, pienso, animal destinado a producir alimentos o sustancia relevante</strong> a través de las etapas de producción, transformación y distribución. Para el consumidor suele hacerse visible mediante un lote, una etiqueta o una retirada de producto, pero por detrás existe una cadena de registros entre operadores.</p>
<h2>La idea básica: saber de quién viene y a quién se entrega</h2>
<p>La legislación alimentaria general de la Unión Europea exige que los operadores puedan identificar a las empresas que les han suministrado alimentos, piensos, animales productores de alimentos o sustancias destinadas a incorporarse a ellos. También deben disponer de sistemas para identificar a las empresas a las que han suministrado sus productos.</p>
<p>Esta lógica suele resumirse como <strong>“un paso atrás y un paso adelante”</strong>. No significa que cada consumidor tenga acceso a toda la base de datos de la cadena; significa que los operadores deben poder reconstruirla cuando sea necesario.</p>
<h2>Qué papel tiene el lote</h2>
<p>El lote agrupa unidades vinculadas a una determinada producción o partida y permite acotar una incidencia. Si un problema afecta a una partida concreta, identificar el lote puede evitar retirar indiscriminadamente productos no relacionados.</p>
<p>Un lote <strong>no indica que un alimento sea mejor o peor</strong>. Su función es operativa: conectar unidades físicas con registros de producción y distribución.</p>
<h2>Un ejemplo sencillo de cadena trazable</h2>
<ol><li>Un productor registra la materia prima o cosecha.</li><li>Un transformador registra qué materia prima recibe y qué partidas produce con ella.</li><li>Un almacén o distribuidor registra entradas y salidas.</li><li>Un comercio recibe lotes identificados.</li><li>Si aparece una incidencia, los registros permiten localizar qué unidades pueden estar afectadas y qué clientes profesionales las recibieron.</li></ol>
<p>La complejidad real puede ser mucho mayor, sobre todo en productos con mezclas, transformaciones o varios ingredientes, pero el principio es el mismo: <strong>conservar conexiones verificables entre etapas</strong>.</p>
<h2>Trazabilidad no es lo mismo que origen</h2>
<p>Un alimento puede ser trazable aunque la etiqueta no muestre al consumidor todos los lugares por los que pasó. El <strong>origen</strong> responde a reglas de información y procedencia; la <strong>trazabilidad</strong> responde a la capacidad de reconstruir la cadena. Nuestra guía sobre <a href="'.home_url('/origen-etiqueta-alimento-pais-procedencia-ingrediente-primario/').'">origen en la etiqueta</a> explica la diferencia desde el punto de vista del envase.</p>
<h2>Trazabilidad tampoco es una certificación de calidad</h2>
<p>Que un producto sea trazable es una exigencia del sistema alimentario, no un premio comercial. Una DOP o IGP añade un pliego y controles sobre un nombre protegido; la trazabilidad ayuda a sostener y verificar el recorrido del producto, pero no convierte por sí sola un alimento en DOP, ecológico, artesano o de una determinada categoría. Para esas figuras, consulta la guía de <a href="'.home_url('/dop-igp-etg-diferencias-sellos-calidad-alimentos/').'">DOP, IGP y ETG</a>.</p>
<h2>Qué ocurre cuando se detecta un problema</h2>
<p>Si un operador considera o tiene motivos para pensar que un alimento no cumple los requisitos de seguridad, debe actuar conforme a las obligaciones aplicables de retirada, información y cooperación con las autoridades. La trazabilidad permite <strong>acotar, localizar y comunicar</strong> con mayor precisión.</p>
<h2>Por qué la etiqueta importa</h2>
<p>Los alimentos comercializados deben estar adecuadamente etiquetados o identificados para facilitar su trazabilidad. Elementos como denominación, operador, lote, fechas y otra información obligatoria cumplen funciones diferentes, pero juntos ayudan a vincular el producto físico con la información de la cadena. Puedes ampliar cómo se lee cada elemento en nuestra guía de <a href="'.home_url('/como-leer-etiqueta-alimento-ingredientes-nutricion-origen-lote-conservacion/').'">etiquetado alimentario</a>.</p>
<h2>¿Puede el consumidor “trazar” por completo un producto?</h2>
<p>No necesariamente. Algunas empresas ofrecen códigos QR, datos de finca, cosecha, productor o elaboración, pero eso es información adicional. La obligación legal de trazabilidad se articula principalmente entre operadores y autoridades. La ausencia de una aplicación pública que muestre toda la cadena <strong>no significa por sí sola que el producto carezca de trazabilidad</strong>.</p>
<h2>La idea que merece la pena recordar</h2>
<p>La trazabilidad es una <strong>red de registros conectados</strong>: identifica suministradores, partidas y destinatarios profesionales para poder reconstruir qué ocurrió y actuar con precisión cuando hace falta. El lote es una de las piezas visibles de ese sistema, no todo el sistema.</p>
<h2>Fuente normativa</h2><ul><li><a href="'.$source_trace.'" rel="nofollow noopener">Reglamento (CE) 178/2002, especialmente el artículo 18 sobre trazabilidad</a>.</li></ul>',
 'en_content'=>
'<p>Food traceability is the ability to <strong>trace and follow food, feed, food-producing animals or relevant substances</strong> through the stages of production, processing and distribution. Consumers often see only a lot number, label or product recall, but behind those visible elements sits a chain of records held by food businesses.</p>
<h2>The basic idea: know who supplied you and whom you supplied</h2>
<p>EU general food law requires food and feed business operators to be able to identify businesses that supplied them with food, feed, food-producing animals or substances intended to be incorporated into food or feed. They must also have systems to identify businesses to which their products have been supplied.</p>
<p>This is often summarised as <strong>“one step back, one step forward”</strong>. It does not mean every consumer has access to the full supply-chain database; it means operators must be able to reconstruct the relevant links when needed.</p>
<h2>What a lot number does</h2>
<p>A lot groups units associated with a particular production batch or run and helps narrow an investigation. If a problem affects one batch, identifying it can prevent unrelated products from being withdrawn unnecessarily.</p>
<p>A lot number <strong>does not indicate higher or lower quality</strong>. Its function is operational: connect physical units with production and distribution records.</p>
<h2>A simple traceable-chain example</h2>
<ol><li>A producer records the raw material or harvest.</li><li>A processor records incoming raw material and the batches produced from it.</li><li>A warehouse or distributor records incoming and outgoing lots.</li><li>A retailer receives identified lots.</li><li>If a problem arises, records can identify potentially affected units and the professional customers that received them.</li></ol>
<p>Real chains may be much more complex, particularly where foods are blended or contain multiple ingredients, but the principle remains: <strong>maintain verifiable links between stages</strong>.</p>
<h2>Traceability is not the same as origin</h2>
<p>A food can be fully traceable even when its label does not show consumers every place it passed through. <strong>Origin</strong> concerns rules on provenance information; <strong>traceability</strong> concerns the ability to reconstruct the chain. Our guide to <a href="'.home_url('/en/origin-food-label-country-provenance-primary-ingredient/').'">origin on food labels</a> explains the packaging side of the distinction.</p>
<h2>Traceability is not a quality certification either</h2>
<p>Traceability is a food-system requirement, not a commercial award. A PDO or PGI adds a specification and controls for a protected name. Traceability helps support verification, but it does not by itself make a food PDO, organic, artisan or part of any specific commercial category. See our <a href="'.home_url('/en/pdo-pgi-tsg-differences-eu-food-quality-labels/').'">PDO, PGI and TSG guide</a> for those schemes.</p>
<h2>What happens when a problem is detected</h2>
<p>Where an operator considers or has reason to believe that a food does not comply with food-safety requirements, the applicable withdrawal, information and cooperation duties come into play. Traceability makes it possible to <strong>narrow, locate and communicate</strong> the affected scope more precisely.</p>
<h2>Why labels matter</h2>
<p>Food placed on the market must be adequately labelled or identified to facilitate traceability. The product name, operator, lot, dates and other mandatory details serve different purposes, but together they help connect the physical product with supply-chain information. Our <a href="'.home_url('/en/how-to-read-food-label-ingredients-nutrition-origin-batch-storage/').'">food-label guide</a> explains how to read them.</p>
<h2>Can a consumer trace the complete chain?</h2>
<p>Not necessarily. Some businesses voluntarily provide QR codes, farm data, harvest details or producer information. Legal traceability, however, mainly operates through records available to businesses and authorities. The absence of a public app showing the whole chain <strong>does not by itself mean a food is untraceable</strong>.</p>
<h2>The useful takeaway</h2>
<p>Traceability is a <strong>network of connected records</strong>: suppliers, batches and professional recipients can be identified so that the chain can be reconstructed and action targeted when necessary. A lot number is one visible piece of the system, not the whole system.</p>
<h2>Regulatory source</h2><ul><li><a href="'.$source_trace.'" rel="nofollow noopener">Regulation (EC) No 178/2002, particularly Article 18 on traceability</a>.</li></ul>'
];

$created=[];$updated=[];
foreach($items as $it){
    $existing=get_page_by_path($it['slug'],OBJECT,'post');
    $postarr=[
      'post_title'=>$it['title'],'post_name'=>$it['slug'],'post_excerpt'=>$it['excerpt'],'post_content'=>$it['content'],
      'post_status'=>'publish','post_type'=>'post','post_author'=>1,'post_category'=>[$category_id]
    ];
    if($existing){ $postarr['ID']=$existing->ID; $id=wp_update_post($postarr,true); $updated[]=$id; }
    else { $id=wp_insert_post($postarr,true); $created[]=$id; }
    if(is_wp_error($id)) throw new Exception('Post write failed '.$it['slug'].': '.$id->get_error_message());
    set_post_thumbnail($id,$placeholder_id);
    update_post_meta($id,'_en_US_post_title',$it['en_title']);
    update_post_meta($id,'_en_US_post_name',$it['en_slug']);
    update_post_meta($id,'_en_US_post_excerpt',$it['en_excerpt']);
    update_post_meta($id,'_en_US_post_content',$it['en_content']);
    update_post_meta($id,'_en_US_ready','1');
    update_post_meta($id,'_en_US_published','1');
    update_post_meta($id,'_emdo_seo_title',$it['seo_title']);
    update_post_meta($id,'_emdo_seo_description',$it['seo_description']);
    update_post_meta($id,'_en_US_seo_title',$it['en_seo_title']);
    update_post_meta($id,'_en_US_seo_description',$it['en_seo_description']);
    update_post_meta($id,'_emdo_cross_category_pillars_20260902','1');
    clean_post_cache($id);
    if(emdo_cp_words(get_post_field('post_content',$id))<500 || emdo_cp_words(get_post_meta($id,'_en_US_post_content',true))<450) throw new Exception('Content too short '.$id);
}

$out=[];
foreach($items as $it){
  $p=get_page_by_path($it['slug'],OBJECT,'post'); if(!$p) continue;
  $out[]=['id'=>$p->ID,'status'=>$p->post_status,'slug'=>$p->post_name,'es_words'=>emdo_cp_words($p->post_content),'en_slug'=>(string)get_post_meta($p->ID,'_en_US_post_name',true),'en_words'=>emdo_cp_words(get_post_meta($p->ID,'_en_US_post_content',true)),'ready'=>(string)get_post_meta($p->ID,'_en_US_ready',true),'published'=>(string)get_post_meta($p->ID,'_en_US_published',true),'categories'=>wp_get_post_categories($p->ID)];
}
echo "EMDO_CROSS_CATEGORY_PILLARS_BEGIN\n";
echo wp_json_encode(['created'=>$created,'updated'=>$updated,'posts'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";
echo "EMDO_CROSS_CATEGORY_PILLARS_END\n";
