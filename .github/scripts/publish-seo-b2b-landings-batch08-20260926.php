<?php
if (!defined('ABSPATH')) { exit; }

function emdo_b2b08_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_b2b08_existing($key, $slug) {
    $ids = get_posts(array(
        'post_type'=>'page','post_status'=>'any','posts_per_page'=>1,'fields'=>'ids',
        'meta_key'=>'_emdo_seo_landing_key','meta_value'=>$key,
    ));
    if ($ids) return (int)$ids[0];
    $p = get_page_by_path($slug, OBJECT, 'page');
    if ($p) return (int)$p->ID;
    $collision = get_page_by_path($slug, OBJECT, 'post');
    if ($collision) throw new Exception('Slug collision with post: '.$slug.' ID '.$collision->ID);
    return 0;
}

$pages = array(
array(
'key'=>'gift-gastronomic-weddings',
'slug'=>'regalos-gastronomicos-bodas',
'title'=>'Regalos gastronómicos para bodas',
'excerpt'=>'Ideas de regalos gastronómicos relacionados con bodas: para novios, padres, testigos, colaboradores y otros momentos especiales de la celebración.',
'seo_title'=>'Regalos gastronómicos para bodas',
'seo_description'=>'Ideas de regalos gastronómicos para bodas: productos con origen para novios, padres, testigos, colaboradores y momentos especiales.',
'focus'=>'regalos gastronómicos para bodas',
'content'=><<<'HTML'
<p>Una boda genera muchas ocasiones distintas para regalar. No todo se reduce al detalle que reciben los invitados: también puede haber un agradecimiento para padres, testigos, personas que han ayudado a organizar la celebración, proveedores cercanos o incluso un regalo gastronómico para los propios novios.</p>
<p>Los productos de alimentación funcionan bien porque se disfrutan, se comparten y pueden tener una relación clara con el territorio o la historia de la pareja. La clave es elegir el formato según quién recibe el regalo y en qué momento se entrega.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un regalo gastronómico para una boda?</h2><p>Cuéntanos para quién es, cuántas unidades necesitas, presupuesto y fecha. Revisaremos qué productos disponibles pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Regalos para padres y familiares cercanos</h2>
<p>Padres, hermanos o familiares que han tenido un papel especial en la organización pueden recibir un detalle distinto al de los invitados. En estos casos tiene sentido elegir un producto de mayor entidad o una selección más personal.</p>
<p>Un buen AOVE, una pieza ibérica o un pequeño lote de productor pueden transmitir agradecimiento sin recurrir a objetos decorativos.</p>
<h2>Regalos para testigos</h2>
<p>Los testigos suelen ocupar un lugar destacado en la ceremonia y muchas parejas quieren agradecerlo de forma específica. Un regalo gastronómico puede funcionar especialmente bien si se conoce algo de sus gustos.</p>
<p>No hace falta que todos reciban exactamente lo mismo si son pocas personas, aunque mantener una lógica común puede dar coherencia al conjunto.</p>
<h2>Un regalo para los novios</h2>
<p>También puede plantearse a la inversa: familiares, amigos o empresas pueden regalar productos gastronómicos a la pareja. Una selección para disfrutar después de la boda puede ser una alternativa a los regalos materiales tradicionales.</p>
<p>En este caso es útil saber si los novios cocinan, disfrutan del aperitivo o valoran especialmente alguna categoría.</p>
<h2>AOVE como regalo de boda</h2>
<p>El aceite de oliva virgen extra es versátil, fácil de integrar en distintos presupuestos y permite destacar productor y origen. Puede regalarse solo o como parte de una selección.</p>
<p>Además, funciona tanto en regalos individuales como en cantidades mayores.</p>
<h2>Ibéricos y jamón</h2>
<p>Para regalos de mayor entidad, jamón, paleta o ibéricos pueden tener mucha presencia. Conviene elegir formato según presupuesto, facilidad de consumo y si el destinatario podrá almacenar una pieza.</p>
<p>El loncheado puede resultar más práctico para algunos perfiles.</p>
<h2>Productos ligados al lugar de la boda</h2>
<p>Si la celebración está vinculada a una región concreta, puede tener sentido buscar productos de esa zona o con una procedencia que conecte con la historia de la pareja.</p>
<p>Este tipo de elección añade significado sin necesidad de personalizaciones complejas.</p>
<h2>Cómo adaptar el presupuesto</h2>
<p>En regalos para personas muy cercanas se puede trabajar con un presupuesto superior al de un detalle para invitados. Lo importante es definir desde el principio cuántos destinatarios hay y qué papel tiene cada grupo.</p>
<p>Separar las categorías evita intentar utilizar una única propuesta para necesidades muy distintas.</p>
<h2>Presentación y mensaje</h2>
<p>Una nota breve puede aportar mucho en una boda, especialmente cuando el regalo reconoce una ayuda o relación concreta. Si necesitas tarjetas, presentación especial u otro elemento, debe revisarse según producto, vendedor y plazo.</p>
<p>No conviene dar por disponible una personalización hasta confirmarla.</p>
<h2>Cuándo organizar estos regalos</h2>
<p>Los últimos días antes de la boda suelen estar llenos de tareas. Resolver los regalos con antelación reduce estrés y permite más margen para revisar productos y cantidades.</p>
<p>Si la entrega debe hacerse antes o después del evento, indícalo desde el primer contacto.</p>
<h2>Logística y número de destinatarios</h2>
<p>No es lo mismo preparar tres regalos para familiares que cincuenta para colaboradores. El volumen afecta a disponibilidad, preparación y transporte.</p>
<p>El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente su pedido, por lo que la operativa depende de la composición concreta.</p>
<h2>Qué información necesitamos</h2>
<p>Número de regalos, tipo de destinatario, presupuesto, fecha y lugar de entrega. Si existe alguna categoría que quieras destacar o evitar, indícalo también.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Haz que el regalo forme parte de la celebración</h2><p>Dinos a quién quieres agradecer y revisaremos opciones gastronómicas con origen y productor visibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'gift-wedding-guests',
'slug'=>'regalos-gastronomicos-invitados-boda',
'title'=>'Regalos gastronómicos para invitados de boda',
'excerpt'=>'Ideas de detalles gastronómicos para invitados de boda: formatos pequeños, productos con origen, presupuesto por persona y logística para muchas unidades.',
'seo_title'=>'Regalos gastronómicos para invitados de boda',
'seo_description'=>'Detalles gastronómicos para invitados de boda: ideas por presupuesto, formatos prácticos, cantidades, presentación y logística.',
'focus'=>'regalos gastronómicos para invitados de boda',
'content'=><<<'HTML'
<p>El detalle para invitados de boda tiene que cumplir varias cosas a la vez: ser agradable, fácil de transportar, asumible cuando se multiplica por muchas personas y lo bastante especial como para no parecer un recuerdo genérico.</p>
<p>Los productos gastronómicos pueden encajar muy bien porque son consumibles y permiten trabajar con origen, productor y territorio. La clave es elegir formatos pequeños y realistas para el número total de asistentes.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un detalle para los invitados?</h2><p>Indícanos número aproximado de personas, presupuesto por unidad, fecha y lugar de celebración. Revisaremos qué formatos pueden funcionar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar propuestas</a></p></div>
<h2>El presupuesto por persona es el punto de partida</h2>
<p>Una diferencia de dos o tres euros parece pequeña hasta que se multiplica por cien o doscientos invitados. Por eso conviene fijar un límite unitario antes de elegir productos.</p>
<p>También hay que decidir si ese presupuesto debe incluir presentación o transporte.</p>
<h2>AOVE en formatos manejables</h2>
<p>El aceite de oliva virgen extra puede ser un detalle muy vinculado a la gastronomía española. En una boda, el tamaño importa: un formato práctico es más fácil de colocar, entregar y transportar.</p>
<p>La disponibilidad de formatos concretos depende del productor y debe confirmarse.</p>
<h2>Conservas y productos de despensa</h2>
<p>Pequeños productos de despensa pueden funcionar como recuerdo útil y fácil de disfrutar en casa. Tienen además la ventaja de una conservación sencilla.</p>
<p>Si se elige una conserva, conviene valorar el peso y el envase cuando los invitados tengan que desplazarse.</p>
<h2>Un producto de origen local</h2>
<p>Cuando la boda se celebra en una zona con fuerte identidad gastronómica, un producto del territorio puede convertir el detalle en un recuerdo del lugar.</p>
<p>Este enfoque funciona especialmente bien si la procedencia se explica de manera sencilla.</p>
<h2>Evita detalles demasiado voluminosos</h2>
<p>Los invitados pueden tener que caminar, viajar en coche, tren o avión después de la celebración. Un regalo grande o pesado puede resultar incómodo.</p>
<p>En este tipo de pedido, la practicidad forma parte del valor del detalle.</p>
<h2>Una misma opción para todos simplifica</h2>
<p>Ofrecer muchas variantes puede parecer atractivo, pero complica el montaje y la entrega. Si existen restricciones importantes, puede estudiarse una alternativa concreta, pero conviene limitar el número de opciones.</p>
<p>La simplicidad reduce errores cuando hay muchas unidades.</p>
<h2>Presentación y personalización</h2>
<p>Una etiqueta, tarjeta o mensaje puede ayudar a vincular el producto con la boda. Sin embargo, cualquier personalización depende del formato, vendedor, cantidades y plazos.</p>
<p>Debe confirmarse antes de diseñar el resto de la papelería alrededor del regalo.</p>
<h2>Cómo calcular cantidades</h2>
<p>No siempre el número de regalos coincide exactamente con el número de invitados. Puede haber parejas, familias, niños o personas que finalmente no asistan.</p>
<p>Conviene decidir si el detalle será individual o por unidad familiar antes de cerrar cantidades.</p>
<h2>Qué hacer con sobrantes</h2>
<p>Es prudente prever un pequeño margen, pero tampoco sobredimensionar mucho el pedido. Los productos gastronómicos tienen la ventaja de que los sobrantes suelen poder aprovecharse, siempre dentro de su fecha y condiciones de conservación.</p>
<p>Una previsión realista ayuda a controlar el presupuesto.</p>
<h2>Entrega en el propio evento</h2>
<p>Si el producto debe llegar al restaurante, finca u hotel, hay que coordinar fecha, persona de contacto y espacio de almacenamiento.</p>
<p>Conviene que la entrega se produzca con margen suficiente para revisar cantidades.</p>
<h2>Qué información necesitamos</h2>
<p>Número de invitados, presupuesto por detalle, fecha, lugar de celebración y si el regalo será individual o por pareja/familia. Con esos datos podemos revisar formatos y disponibilidad.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Elige un detalle que los invitados quieran llevarse</h2><p>Dinos cantidades y presupuesto y revisaremos productos gastronómicos prácticos y con origen.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'gift-communions-baptisms',
'slug'=>'regalos-gastronomicos-comuniones-bautizos',
'title'=>'Regalos gastronómicos para comuniones y bautizos',
'excerpt'=>'Ideas de detalles gastronómicos para comuniones y bautizos: productos pequeños, útiles, con origen y adaptados al presupuesto y número de invitados.',
'seo_title'=>'Regalos gastronómicos para comuniones y bautizos',
'seo_description'=>'Detalles gastronómicos para comuniones y bautizos: ideas por presupuesto, número de invitados, formatos, presentación y entrega.',
'focus'=>'regalos gastronómicos comuniones bautizos',
'content'=><<<'HTML'
<p>Comuniones y bautizos suelen reunir a familiares y amigos en celebraciones donde muchas familias quieren entregar un pequeño detalle. Los productos gastronómicos pueden ser una alternativa práctica a los recuerdos decorativos porque se consumen, se disfrutan y no terminan ocupando espacio.</p>
<p>Para que funcionen bien, deben adaptarse al número de invitados, al presupuesto y a la logística del evento. El tamaño y la facilidad de transporte son especialmente importantes.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un detalle gastronómico para una celebración familiar?</h2><p>Cuéntanos número de invitados, presupuesto por unidad, fecha y lugar. Revisaremos qué productos pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Un detalle pequeño puede ser suficiente</h2>
<p>No hace falta que el regalo tenga gran tamaño. Cuando se entrega a muchos invitados, un producto sencillo pero bien elegido puede resultar más útil y elegante.</p>
<p>La procedencia y el productor pueden aportar personalidad sin aumentar necesariamente el volumen.</p>
<h2>AOVE como detalle</h2>
<p>El aceite de oliva virgen extra puede funcionar en celebraciones familiares por su utilidad y su vínculo con la gastronomía española.</p>
<p>El formato debe ser cómodo para entregar y llevar, especialmente si los invitados se desplazan.</p>
<h2>Conservas y productos de despensa</h2>
<p>También pueden utilizarse pequeñas referencias de despensa o conservas, siempre que el formato tenga sentido para el evento.</p>
<p>Son productos fáciles de guardar y pueden disfrutarse después de la celebración.</p>
<h2>Productos ligados al territorio</h2>
<p>Si la familia tiene relación con una zona concreta, elegir un producto de ese origen puede aportar un significado especial.</p>
<p>No hace falta que el producto sea raro; basta con que exista una conexión clara y pueda explicarse.</p>
<h2>Presupuesto por unidad</h2>
<p>Como en cualquier celebración con muchos asistentes, conviene multiplicar el coste unitario por el número real de regalos antes de decidir.</p>
<p>Pequeñas diferencias de precio pueden cambiar significativamente el presupuesto total.</p>
<h2>¿Regalo por persona o por familia?</h2>
<p>Esta decisión puede modificar mucho las cantidades. En algunos eventos tiene sentido un detalle individual; en otros, un regalo por pareja o núcleo familiar resulta suficiente.</p>
<p>Definirlo al principio evita comprar de más.</p>
<h2>Presentación</h2>
<p>Una tarjeta con la fecha o un mensaje breve puede personalizar el detalle sin complicar demasiado el producto. Si se necesita una presentación específica, debe consultarse según vendedor y plazo.</p>
<p>No todas las referencias admiten las mismas opciones.</p>
<h2>Detalles para niños y adultos</h2>
<p>Si se quiere diferenciar, conviene mantener pocas variantes. Una opción para adultos y otra para niños puede ser manejable; muchas combinaciones aumentan el riesgo de errores.</p>
<p>En cualquier caso, los ingredientes y alérgenos deben revisarse individualmente.</p>
<h2>Conservación y temperatura</h2>
<p>En celebraciones de primavera o verano, hay que pensar en cuánto tiempo estará el producto fuera de condiciones controladas. Los alimentos estables a temperatura ambiente suelen ser más prácticos para este tipo de detalle.</p>
<p>La facilidad de conservación puede ser tan importante como la presentación.</p>
<h2>Entrega en restaurante o finca</h2>
<p>Si los productos deben llegar directamente al lugar de celebración, conviene coordinar recepción, fecha y persona responsable.</p>
<p>El pedido debería llegar con margen para comprobar cantidades y distribuir los detalles.</p>
<h2>Qué información necesitamos</h2>
<p>Número aproximado de regalos, presupuesto por unidad, fecha, lugar, si el detalle será por persona o familia y cualquier preferencia de producto.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Prepara un detalle útil y fácil de disfrutar</h2><p>Envíanos los datos de la celebración y revisaremos formatos gastronómicos adecuados al volumen y presupuesto.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-gourmet-event-favors',
'slug'=>'detalles-gourmet-eventos-empresa',
'title'=>'Detalles gourmet para eventos de empresa',
'excerpt'=>'Ideas de detalles gourmet para eventos de empresa: formatos pequeños, escalables y fáciles de entregar a asistentes, invitados o equipos.',
'seo_title'=>'Detalles gourmet para eventos de empresa',
'seo_description'=>'Detalles gourmet para eventos corporativos: ideas para muchos asistentes, presupuesto unitario, formato, presentación y logística.',
'focus'=>'detalles gourmet para eventos de empresa',
'content'=><<<'HTML'
<p>En un evento de empresa, no siempre hace falta entregar un gran regalo. Muchas veces funciona mejor un <strong>detalle gourmet pequeño</strong> que pueda darse a decenas o cientos de asistentes sin complicar la logística.</p>
<p>El objetivo puede ser agradecer la asistencia, acompañar una acreditación, cerrar una jornada o dejar un recuerdo útil. Para eso, el formato debe ser manejable, repetible y compatible con el presupuesto por persona.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Necesitas un detalle para muchos asistentes?</h2><p>Indícanos número de unidades, presupuesto, fecha y lugar del evento. Revisaremos formatos gastronómicos que puedan escalarse.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar propuestas</a></p></div>
<h2>Pequeño, pero con identidad</h2>
<p>Un detalle no necesita ser grande para resultar memorable. Un producto de productor identificado puede aportar más valor que un objeto promocional genérico.</p>
<p>La procedencia y el contexto pueden formar parte del recuerdo del evento.</p>
<h2>Presupuesto por asistente</h2>
<p>En eventos con mucho volumen, el presupuesto unitario debe definirse con precisión. Una diferencia mínima se multiplica rápidamente cuando hay cientos de personas.</p>
<p>También conviene aclarar si el presupuesto incluye presentación y transporte.</p>
<h2>AOVE en formatos adecuados</h2>
<p>El aceite puede funcionar bien como detalle corporativo por su utilidad y reconocimiento. Para eventos, el tamaño y el peso son especialmente importantes.</p>
<p>La existencia de formatos concretos debe comprobarse según productor y unidades.</p>
<h2>Conservas y pequeños productos de despensa</h2>
<p>Son alternativas que pueden diferenciarse del merchandising habitual y resultar fáciles de llevar.</p>
<p>Conviene revisar envase, peso y facilidad de almacenamiento antes de elegir.</p>
<h2>Detalle para asistentes frente a regalo para ponentes</h2>
<p>No tienen por qué ser iguales. El detalle general puede ser compacto y escalable, mientras que ponentes, patrocinadores o invitados especiales pueden recibir una propuesta de mayor entidad.</p>
<p>Separar ambos grupos ayuda a usar mejor el presupuesto.</p>
<h2>Entrega en bolsa de bienvenida</h2>
<p>Si el producto va dentro de una bolsa, debe encajar físicamente y no poner en riesgo otros materiales. Peso, posibilidad de derrame y resistencia del envase son aspectos relevantes.</p>
<p>Conviene conocer las dimensiones antes de cerrar la composición.</p>
<h2>Entrega al final del evento</h2>
<p>En este caso, el detalle puede funcionar como cierre y agradecimiento. Debe ser fácil de recoger y transportar, especialmente si los asistentes vuelven en tren o avión.</p>
<p>Los formatos muy voluminosos suelen ser menos adecuados.</p>
<h2>Personalización y marca</h2>
<p>Una tarjeta o mensaje puede conectar el detalle con el evento. Si se necesita etiqueta, packaging específico u otro elemento, debe validarse con antelación.</p>
<p>No conviene diseñar una acción alrededor de una personalización que todavía no esté confirmada.</p>
<h2>Stock para grandes cantidades</h2>
<p>Un producto puede estar disponible para veinte unidades y no para quinientas. En eventos grandes, la capacidad de suministro es un criterio tan importante como el diseño.</p>
<p>Por eso conviene iniciar la búsqueda con tiempo y mantener alguna alternativa.</p>
<h2>Logística del recinto</h2>
<p>Hay que definir quién recibe el pedido, dónde se almacena, cuándo se monta y quién se encarga del reparto. Estas cuestiones pueden determinar qué formato es realmente práctico.</p>
<p>Una buena idea sobre el papel debe funcionar también el día del evento.</p>
<h2>Qué información necesitamos</h2>
<p>Número de asistentes, presupuesto por unidad, fecha, recinto, momento de entrega y cualquier requisito de presentación. Con esos datos podemos revisar opciones realistas.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Haz que el detalle sea fácil de entregar y recordar</h2><p>Dinos volumen y presupuesto y revisaremos productos que puedan funcionar a escala.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'gift-gastronomic-thank-you',
'slug'=>'regalos-gastronomicos-agradecimiento',
'title'=>'Regalos gastronómicos de agradecimiento',
'excerpt'=>'Ideas de regalos gastronómicos para dar las gracias a clientes, colaboradores, familiares o personas que han ayudado en un momento importante.',
'seo_title'=>'Regalos gastronómicos de agradecimiento',
'seo_description'=>'Ideas de regalos gastronómicos para agradecer una ayuda, colaboración o gesto: productos con origen, presupuesto y formato adecuados.',
'focus'=>'regalos gastronómicos de agradecimiento',
'content'=><<<'HTML'
<p>Dar las gracias con un regalo no requiere una ocasión comercial ni una fecha concreta. Puede ser una forma de reconocer una ayuda, cerrar un proyecto, agradecer una recomendación, celebrar una colaboración o devolver un gesto importante.</p>
<p>Los productos gastronómicos funcionan bien porque son consumibles y pueden adaptarse tanto a agradecimientos personales como profesionales. Lo importante es que el valor del regalo sea proporcional y que el destinatario entienda el motivo.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres agradecer algo con un regalo gastronómico?</h2><p>Cuéntanos a quién va dirigido, presupuesto, ocasión y fecha. Revisaremos qué productos pueden transmitir mejor ese agradecimiento.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar ideas</a></p></div>
<h2>El motivo importa más que el tamaño</h2>
<p>Un agradecimiento pequeño y bien elegido puede tener más impacto que un regalo caro sin contexto. La persona debería entender por qué lo recibe.</p>
<p>Una nota breve puede ayudar a expresar el motivo sin necesidad de una presentación compleja.</p>
<h2>Agradecer a un cliente</h2>
<p>Después de una colaboración importante o una recomendación, un detalle puede reforzar la relación. Conviene mantener un tono profesional y revisar cualquier política de regalos que pueda existir.</p>
<p>Un AOVE, una pequeña selección o un producto de productor concreto pueden funcionar bien.</p>
<h2>Agradecer a un proveedor o colaborador</h2>
<p>También puede tener sentido reconocer a quien ha resuelto un problema, trabajado con especial dedicación o acompañado un proyecto.</p>
<p>En este caso, el regalo debería ser proporcional a la relación y no parecer una obligación comercial.</p>
<h2>Agradecer a una persona cercana</h2>
<p>En contextos personales hay más margen para adaptar el producto a los gustos conocidos del destinatario. Puedes elegir una categoría que ya sepas que disfruta.</p>
<p>La gastronomía permite además compartir el regalo con otras personas.</p>
<h2>Un AOVE como agradecimiento</h2>
<p>Es una opción práctica y sobria. Puede funcionar cuando no se quiere hacer un regalo demasiado íntimo o cuando se busca algo útil.</p>
<p>El productor y el origen pueden añadir personalidad.</p>
<h2>Ibéricos o jamón</h2>
<p>Si el agradecimiento tiene más entidad y el destinatario disfruta de esta categoría, una selección de ibéricos, una paleta o incluso una pieza de jamón pueden ser opciones adecuadas.</p>
<p>El formato debe elegirse según presupuesto y facilidad de consumo.</p>
<h2>Una selección pequeña</h2>
<p>Combinar dos o tres productos con una lógica clara puede dar sensación de cuidado sin sobredimensionar el regalo.</p>
<p>Por ejemplo, AOVE y conservas, o una selección de aperitivo.</p>
<h2>Cuándo entregar el regalo</h2>
<p>El agradecimiento suele tener más sentido cerca del momento que lo motiva. No hace falta esperar a Navidad o a una fecha comercial.</p>
<p>Si el regalo llega semanas después, una nota que recuerde el motivo puede ayudar a contextualizarlo.</p>
<h2>Presupuesto y proporcionalidad</h2>
<p>Un regalo excesivo puede generar incomodidad. Conviene elegir una cifra razonable según la relación y el motivo del agradecimiento.</p>
<p>En empresas, también deben tenerse en cuenta políticas internas o límites de compliance cuando correspondan.</p>
<h2>Entrega y presentación</h2>
<p>Si el regalo se envía a domicilio u oficina, hay que confirmar la dirección y quién puede recibirlo. Si se necesita una tarjeta o mensaje, debe revisarse según la operativa.</p>
<p>La presentación debe acompañar al producto, no eclipsarlo.</p>
<h2>Qué información necesitamos</h2>
<p>Motivo del agradecimiento, tipo de destinatario, presupuesto, fecha y lugar de entrega. Con esos datos podemos revisar opciones adecuadas y disponibles.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Di gracias con algo que pueda disfrutarse</h2><p>Explícanos el contexto y revisaremos productos gastronómicos proporcionados al gesto que quieres reconocer.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
)
);

$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author=$admins?(int)$admins[0]:1;
$rows=array();

foreach($pages as $p){
    $words=emdo_b2b08_words($p['content']);
    if($words<650) throw new Exception($p['key'].' too short: '.$words);

    $existing=emdo_b2b08_existing($p['key'],$p['slug']);
    $post=array(
        'post_type'=>'page','post_status'=>'publish','post_author'=>$author,
        'post_title'=>wp_strip_all_tags($p['title']),
        'post_name'=>sanitize_title($p['slug']),
        'post_excerpt'=>wp_strip_all_tags($p['excerpt']),
        'post_content'=>$p['content'],
        'comment_status'=>'closed','ping_status'=>'closed','post_parent'=>0,'menu_order'=>0,
    );
    if($existing) $post['ID']=$existing;
    $res=$existing?wp_update_post($post,true):wp_insert_post($post,true);
    if(is_wp_error($res)) throw new Exception($p['key'].': '.$res->get_error_message());

    $id=(int)$res;
    update_post_meta($id,'_emdo_seo_landing_key',$p['key']);
    update_post_meta($id,'_emdo_seo_landing_batch','20260926-b2b-08');
    update_post_meta($id,'_emdo_seo_landing_hidden_navigation','1');
    update_post_meta($id,'_emdo_seo_landing_updated_at',gmdate('c'));
    update_post_meta($id,'_yoast_wpseo_title',$p['seo_title']);
    update_post_meta($id,'_yoast_wpseo_metadesc',$p['seo_description']);
    update_post_meta($id,'_yoast_wpseo_focuskw',$p['focus']);
    update_post_meta($id,'rank_math_title',$p['seo_title']);
    update_post_meta($id,'rank_math_description',$p['seo_description']);
    update_post_meta($id,'rank_math_focus_keyword',$p['focus']);
    delete_post_meta($id,'_yoast_wpseo_meta-robots-noindex');
    delete_post_meta($id,'rank_math_robots');
    clean_post_cache($id);

    $rows[]=array('key'=>$p['key'],'id'=>$id,'title'=>get_the_title($id),'slug'=>get_post_field('post_name',$id),'type'=>get_post_type($id),'status'=>get_post_status($id),'words'=>$words,'url'=>get_permalink($id));
}

if(count($rows)!==5) throw new Exception('Expected 5 landing pages');
echo wp_json_encode(array('batch'=>'20260926-b2b-08','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
