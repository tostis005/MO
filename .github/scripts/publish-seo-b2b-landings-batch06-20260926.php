<?php
if (!defined('ABSPATH')) { exit; }

function emdo_b2b06_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_b2b06_existing($key, $slug) {
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
'key'=>'b2b-original-corporate-gifts-producers',
'slug'=>'regalos-corporativos-originales-productores-espanoles',
'title'=>'Regalos corporativos originales con productos de productores españoles',
'excerpt'=>'Ideas para crear regalos corporativos originales con productos de productores españoles, evitando propuestas genéricas y adaptando el regalo al destinatario.',
'seo_title'=>'Regalos corporativos originales con productores españoles',
'seo_description'=>'Regalos corporativos originales con productos de productores españoles: ideas con origen, criterio, presupuesto y destinatario.',
'focus'=>'regalos corporativos originales',
'content'=><<<'HTML'
<p>Conseguir que un regalo corporativo sea original no consiste en buscar algo extraño. Muchas veces basta con escapar de las soluciones genéricas y elegir productos con una historia clara, un productor reconocible y una razón concreta para formar parte del regalo.</p>
<p>Los productos gastronómicos permiten hacer precisamente eso. Un buen AOVE, una selección de ibéricos, conservas o una combinación de varias referencias pueden convertirse en un regalo diferente si existe un criterio detrás y el destinatario entiende qué está recibiendo.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres un regalo corporativo distinto?</h2><p>Cuéntanos quién lo recibirá, cuántas unidades necesitas, presupuesto y fecha. Revisaremos productos de productores españoles que puedan encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
<h2>Original no significa extravagante</h2>
<p>Un regalo excesivamente raro puede sorprender, pero también generar rechazo. En un contexto profesional suele funcionar mejor una propuesta fácil de entender, pero menos previsible que el merchandising habitual.</p>
<p>La originalidad puede estar en el origen, en la combinación o en la forma de contar el producto.</p>
<h2>El productor puede ser parte del regalo</h2>
<p>Cuando el destinatario puede saber quién produce el aceite, el jamón o la conserva, el regalo deja de ser anónimo. Esa información aporta contexto y permite valorar mejor lo que se recibe.</p>
<p>En El Mercado de Origen el vendedor y la procedencia forman parte de las fichas de producto, lo que facilita construir propuestas con identidad.</p>
<h2>Un producto único puede ser más original que una cesta llena</h2>
<p>Una botella de AOVE especialmente seleccionada, una pieza de jamón, una paleta o un pack pequeño de productor pueden resultar más memorables que una caja con muchas referencias sin conexión.</p>
<p>Concentrar el presupuesto permite elevar la categoría del producto principal y simplificar la presentación.</p>
<h2>Combinar categorías con una lógica</h2>
<p>Si se crea una selección, conviene definir un hilo conductor: aperitivo, despensa española, ibéricos, aceite y conservas, o una combinación pensada para compartir.</p>
<p>La coherencia hace que el regalo parezca elegido, no montado al azar.</p>
<h2>Regalos originales para clientes</h2>
<p>En clientes, una propuesta distinta puede ayudar a escapar de la saturación navideña. Si muchas empresas envían cajas similares, seleccionar productos con productor visible puede aportar diferenciación sin aumentar necesariamente el presupuesto.</p>
<p>También puede reservarse una opción más singular para cuentas estratégicas.</p>
<h2>Regalos originales para empleados</h2>
<p>Para empleados, la originalidad debe ser compatible con la repetibilidad. Una referencia que funciona para diez personas quizá no esté disponible para doscientas.</p>
<p>Por eso conviene equilibrar singularidad, stock y facilidad de entrega.</p>
<h2>Regalos para eventos y visitantes</h2>
<p>Los productos gastronómicos españoles también pueden funcionar como detalle para ponentes, invitados o visitantes internacionales. En estos casos interesa especialmente elegir formatos manejables y fáciles de transportar.</p>
<p>Si el producto va a salir de España, deben revisarse condiciones de transporte y restricciones del destino.</p>
<h2>Cómo evitar que el regalo parezca promocional</h2>
<p>No es necesario colocar el logotipo en todos los elementos. A veces una tarjeta breve y una selección coherente transmiten mejor la intención.</p>
<p>Si se necesita personalización, debe confirmarse según producto, productor, cantidades y plazo.</p>
<h2>La originalidad también depende del momento</h2>
<p>Un detalle enviado fuera de Navidad puede resultar más inesperado. Cierre de proyecto, aniversario de colaboración, visita importante o reconocimiento a un equipo son ocasiones que permiten escapar del calendario habitual.</p>
<p>La ocasión puede aportar tanto valor como el propio producto.</p>
<h2>Presupuesto y originalidad</h2>
<p>No hace falta un presupuesto alto para diferenciarse. En rangos contenidos, elegir una sola referencia con buena procedencia puede ser suficiente. En presupuestos mayores, la diferencia puede estar en la categoría, el formato o una selección más cuidada.</p>
<p>Lo importante es evitar gastar en elementos que no añaden valor al destinatario.</p>
<h2>Qué información necesitamos</h2>
<p>Número de regalos, presupuesto, destinatarios, ocasión, fecha y destinos. Si quieres evitar alguna categoría o buscas un enfoque concreto, indícalo también.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Busca originalidad en el producto y en su origen</h2><p>Envíanos el contexto y revisaremos qué opciones disponibles pueden construir un regalo distinto sin complicarlo innecesariamente.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-company-gifts-price-bands',
'slug'=>'regalos-empresa-menos-30-50-100-euros',
'title'=>'Regalos de empresa por menos de 30, 50 y 100 euros',
'excerpt'=>'Ideas de regalos de empresa por distintos rangos de presupuesto: menos de 30, 50 y 100 euros, con productos gastronómicos y criterios para elegir mejor.',
'seo_title'=>'Regalos de empresa por menos de 30, 50 y 100 euros',
'seo_description'=>'Ideas de regalos corporativos por menos de 30, 50 y 100 euros, con productos gastronómicos y criterios para aprovechar mejor el presupuesto.',
'focus'=>'regalos de empresa por presupuesto',
'content'=><<<'HTML'
<p>Cuando una empresa tiene que preparar varios regalos, el presupuesto por persona condiciona casi todas las decisiones. Trabajar con rangos como <strong>menos de 30, 50 o 100 euros</strong> ayuda a filtrar opciones y a evitar propuestas que no pueden escalarse al número real de destinatarios.</p>
<p>Estas cifras deben entenderse como referencias de planificación, no como un catálogo cerrado ni como precios garantizados. La disponibilidad y el precio real dependen de los productos y vendedores presentes en cada momento.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Tienes un presupuesto máximo por regalo?</h2><p>Dinos cuántas unidades necesitas, el rango por persona y la fecha. Revisaremos qué propuestas reales pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Regalos de empresa por menos de 30 euros</h2>
<p>En presupuestos contenidos conviene concentrar el valor. Un AOVE, una conserva especial, un pequeño pack o una selección muy compacta pueden funcionar mejor que una cesta grande.</p>
<p>El objetivo no debería ser aparentar volumen, sino elegir una referencia que tenga sentido y procedencia clara.</p>
<h2>Qué evitar por debajo de 30 euros</h2>
<p>Intentar incluir demasiados productos suele diluir el presupuesto. También puede aumentar el tamaño del envío sin mejorar la experiencia.</p>
<p>En este rango es especialmente importante priorizar calidad percibida y facilidad de entrega.</p>
<h2>Regalos de empresa alrededor de 50 euros</h2>
<p>Con un presupuesto medio ya puede plantearse una combinación de varias categorías: por ejemplo, aceite, embutidos y conservas, o un pack de productor con varios elementos.</p>
<p>También pueden existir formatos ibéricos que encajen, siempre dependiendo de la oferta disponible.</p>
<h2>Cómo repartir un presupuesto de 50 euros</h2>
<p>Una estrategia es dedicar la mayor parte a un producto protagonista y utilizar el resto para uno o dos complementos. Otra es repartir el valor entre tres referencias de peso similar.</p>
<p>La elección dependerá de si se busca presencia, variedad o facilidad de envío.</p>
<h2>Regalos de empresa alrededor de 100 euros</h2>
<p>En este nivel puede tener sentido valorar una pieza de mayor entidad, una selección premium o una combinación con categorías superiores.</p>
<p>No es obligatorio llenar una caja. Aumentar la calidad del producto principal puede generar una percepción más alta que añadir muchas referencias secundarias.</p>
<h2>Clientes, empleados y presupuesto</h2>
<p>Para empleados suele ser importante mantener homogeneidad. En clientes, puede trabajarse con varios niveles según la relación comercial.</p>
<p>Si existen distintos grupos, conviene definir cantidades y presupuesto de cada uno antes de seleccionar productos.</p>
<h2>¿El presupuesto incluye envío?</h2>
<p>Esta pregunta debe resolverse desde el principio. En entregas a múltiples domicilios, el transporte puede representar una parte relevante del coste total.</p>
<p>El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente su pedido, por lo que la logística depende de la composición concreta.</p>
<h2>Regalos por presupuesto en Navidad</h2>
<p>Durante la campaña navideña la disponibilidad puede cambiar con rapidez. Si existe un límite económico muy cerrado, conviene empezar con antelación para disponer de más alternativas dentro del rango.</p>
<p>Esperar a los últimos días puede obligar a cambiar formato o categoría.</p>
<h2>Cómo comparar propuestas</h2>
<p>No compares únicamente el número de productos. Revisa quién los produce, qué categoría tienen, formato, peso, procedencia y utilidad para el destinatario.</p>
<p>Dos regalos con el mismo precio pueden transmitir sensaciones muy diferentes.</p>
<h2>Qué hacer si el presupuesto no está cerrado</h2>
<p>Puedes trabajar con un intervalo. Decir “entre 40 y 60 euros” ofrece margen para valorar distintas composiciones sin perder el control económico.</p>
<p>También permite adaptarse si una referencia concreta no está disponible.</p>
<h2>Qué información necesitamos</h2>
<p>Número de unidades, rango por destinatario, fecha, tipo de público y forma de entrega. Con esos datos podemos revisar opciones reales y evitar propuestas teóricas que no encajen.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Empieza por el presupuesto, no por el catálogo</h2><p>Dinos el rango por persona y revisaremos qué productos disponibles pueden ofrecer más valor dentro de ese límite.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir a contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-what-gift-employees-christmas',
'slug'=>'que-regalar-empleados-navidad',
'title'=>'Qué regalar a los empleados por Navidad',
'excerpt'=>'Qué regalar a los empleados por Navidad: criterios para elegir entre cesta, lote, jamón, AOVE, ibéricos, packs y otras opciones gastronómicas.',
'seo_title'=>'Qué regalar a los empleados por Navidad',
'seo_description'=>'Qué regalar a los empleados por Navidad según presupuesto, tamaño del equipo, preferencias, logística y tipo de producto gastronómico.',
'focus'=>'qué regalar a empleados por Navidad',
'content'=><<<'HTML'
<p>Decidir <strong>qué regalar a los empleados por Navidad</strong> no consiste únicamente en elegir una cesta. Antes conviene definir qué papel tendrá el regalo, cuánto puede gastar la empresa por persona, cómo se entregará y si existen necesidades especiales dentro del equipo.</p>
<p>La gastronomía ofrece muchas posibilidades: jamón, paleta, ibéricos, aceite, conservas, packs o combinaciones. La mejor opción depende de la plantilla, no de una lista universal.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿No sabes qué regalar a tu equipo?</h2><p>Cuéntanos cuántas personas son, presupuesto, fecha y cómo se entregará. Revisaremos qué alternativas pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar propuesta</a></p></div>
<h2>Una cesta de Navidad clásica</h2>
<p>La cesta sigue funcionando porque permite combinar varios productos y se asocia claramente con la campaña navideña.</p>
<p>Puede adaptarse a presupuestos distintos, pero conviene evitar llenarla de referencias sin una lógica clara.</p>
<h2>Un lote compacto</h2>
<p>Para empresas que prefieren un regalo más sencillo, un lote con pocos productos puede ser más práctico. También facilita el almacenamiento y la entrega.</p>
<p>Un AOVE, ibéricos y una conserva pueden formar una propuesta equilibrada sin necesidad de una cesta grande.</p>
<h2>Jamón o paleta</h2>
<p>Una pieza completa tiene presencia y suele disfrutarse en casa durante varios días. La paleta permite trabajar con otro tamaño y presupuesto.</p>
<p>Si el equipo es muy diverso, el formato loncheado puede resultar más cómodo.</p>
<h2>Selección de ibéricos</h2>
<p>Los ibéricos funcionan bien como regalo para compartir y pueden plantearse en formatos relativamente compactos.</p>
<p>Son una opción clásica, pero pueden ganar personalidad si se conoce el productor y la procedencia.</p>
<h2>Aceite de oliva virgen extra</h2>
<p>El AOVE es una alternativa útil cuando se busca un producto práctico y fácil de integrar en distintos hogares.</p>
<p>Puede regalarse solo o como parte de un pequeño lote.</p>
<h2>Una propuesta sin alcohol</h2>
<p>Si la empresa no conoce los hábitos de todos los empleados, eliminar vino o cava puede simplificar la decisión.</p>
<p>El presupuesto que normalmente ocuparían las bebidas puede destinarse a mejorar los alimentos.</p>
<h2>¿Una misma opción para todos?</h2>
<p>En plantillas grandes, la homogeneidad facilita la gestión. Aun así, puede tener sentido prever una alternativa para determinadas restricciones o preferencias.</p>
<p>Cuantas más variantes haya, más compleja será la operativa.</p>
<h2>Qué regalar a un equipo pequeño</h2>
<p>Las empresas con pocas personas pueden permitirse mayor flexibilidad. Conocer gustos generales del equipo ayuda a elegir una propuesta más específica.</p>
<p>También puede ser más fácil trabajar con dos opciones o con productos de stock más limitado.</p>
<h2>Qué regalar a un equipo en remoto</h2>
<p>En remoto, el tamaño y la facilidad de envío ganan importancia. Formatos compactos pueden resultar más adecuados que piezas voluminosas.</p>
<p>Además, hay que recoger direcciones y calcular el coste logístico desde el principio.</p>
<h2>Presupuesto y percepción</h2>
<p>Un presupuesto modesto no obliga a hacer un mal regalo. Concentrarlo en una o dos referencias puede ser mejor que intentar simular una cesta grande.</p>
<p>En presupuestos altos, mejorar la categoría del producto principal suele aportar más que añadir muchos complementos.</p>
<h2>Qué información necesitamos para ayudar</h2>
<p>Número de empleados, presupuesto por persona, fecha, destinos y cualquier preferencia importante. Con esos datos podemos revisar alternativas disponibles y plantear una propuesta realista.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Elige el regalo en función de tu equipo</h2><p>Envíanos cantidades y presupuesto y revisaremos qué opciones gastronómicas pueden funcionar mejor.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'b2b-what-gift-clients-christmas',
'slug'=>'que-regalar-clientes-navidad',
'title'=>'Qué regalar a los clientes por Navidad',
'excerpt'=>'Qué regalar a los clientes por Navidad según relación, presupuesto y perfil: jamón, AOVE, ibéricos, lotes, selecciones y regalos gastronómicos.',
'seo_title'=>'Qué regalar a los clientes por Navidad',
'seo_description'=>'Qué regalar a clientes por Navidad según presupuesto, nivel de relación y tipo de destinatario. Ideas gastronómicas con criterio y origen.',
'focus'=>'qué regalar a clientes por Navidad',
'content'=><<<'HTML'
<p>Elegir <strong>qué regalar a los clientes por Navidad</strong> puede parecer más difícil que regalar a empleados porque no todos los clientes tienen la misma relación con la empresa. Algunos son recientes, otros estratégicos y otros llevan años colaborando.</p>
<p>Por eso, antes de elegir el producto conviene decidir si todos recibirán lo mismo o si habrá distintos niveles de regalo.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Tienes que preparar regalos para tus clientes?</h2><p>Cuéntanos cuántos son, presupuesto, fecha y si existen distintos niveles. Revisaremos qué propuestas pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Un AOVE para un detalle sobrio</h2>
<p>El aceite de oliva virgen extra puede funcionar bien cuando se busca un regalo útil, fácil de entender y relacionado con gastronomía española.</p>
<p>Es una opción adecuada para carteras amplias o para clientes con los que se quiere mantener un tono profesional y sencillo.</p>
<h2>Ibéricos para compartir</h2>
<p>Una selección de ibéricos tiene un carácter más festivo y encaja bien con Navidad. Puede adaptarse a varios niveles de presupuesto y formatos.</p>
<p>Si no conoces las preferencias alimentarias del cliente, conviene evitar asumir que esta categoría será adecuada para todos.</p>
<h2>Jamón o paleta para clientes importantes</h2>
<p>Una pieza puede reservarse para relaciones estratégicas o clientes de especial relevancia. Aporta presencia y suele percibirse como un regalo de mayor entidad.</p>
<p>La paleta permite trabajar con otro rango de precio y tamaño.</p>
<h2>Conservas y despensa</h2>
<p>Una selección de conservas o productos de despensa puede ser una alternativa menos previsible y fácil de compartir.</p>
<p>También funciona bien combinada con AOVE.</p>
<h2>Un lote sin alcohol</h2>
<p>Si no conoces los hábitos del destinatario, una propuesta sin vino o cava puede ser más neutral. El presupuesto se concentra en alimentos y se reduce el riesgo de regalar algo que no se consume.</p>
<p>Esto puede resultar útil cuando el regalo se dirige a una empresa y no a una persona concreta.</p>
<h2>¿Mismo regalo para todos los clientes?</h2>
<p>No siempre. La segmentación por importancia, duración de la relación o tipo de cuenta puede ayudar a utilizar mejor el presupuesto.</p>
<p>Conviene limitar los niveles para no complicar demasiado la gestión.</p>
<h2>Clientes estratégicos</h2>
<p>En relaciones especialmente importantes puede tener sentido una propuesta premium con una pieza protagonista o una selección más cuidada.</p>
<p>Antes de elevar mucho el valor, conviene revisar políticas internas o límites de aceptación de regalos en la empresa receptora.</p>
<h2>Clientes internacionales</h2>
<p>Un producto español puede ser muy atractivo para un cliente extranjero, pero los envíos internacionales están sujetos a condiciones y restricciones que deben revisarse antes de cerrar la propuesta.</p>
<p>No todos los productos ni vendedores pueden operar en todos los destinos.</p>
<h2>La importancia del mensaje</h2>
<p>Un regalo gana valor cuando el cliente entiende por qué lo recibe. Una nota de agradecimiento breve puede ser suficiente para contextualizarlo.</p>
<p>Si se necesita personalización, debe confirmarse según productos, cantidades y plazo.</p>
<h2>Cuándo enviarlo</h2>
<p>Evitar los últimos días antes de Navidad puede ayudar a reducir saturación logística y a conseguir que el regalo llegue cuando todavía puede disfrutarse durante la campaña.</p>
<p>Si existe una fecha concreta, debe comunicarse desde el principio.</p>
<h2>Qué información necesitamos</h2>
<p>Número de clientes, niveles de presupuesto, fecha, destinos y cualquier preferencia conocida. Con esos datos podemos revisar productos disponibles y plantear propuestas diferenciadas si tiene sentido.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Elige el regalo según la relación con el cliente</h2><p>Cuéntanos cómo es tu cartera y revisaremos qué alternativas pueden encajar en cada nivel.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-balanced-gourmet-basket',
'slug'=>'como-preparar-cesta-gourmet-equilibrada',
'title'=>'Cómo preparar una cesta gourmet equilibrada',
'excerpt'=>'Cómo preparar una cesta gourmet equilibrada combinando producto protagonista, variedad, presupuesto, formatos y facilidad de consumo sin llenar por llenar.',
'seo_title'=>'Cómo preparar una cesta gourmet equilibrada',
'seo_description'=>'Cómo construir una cesta gourmet equilibrada: producto principal, variedad, presupuesto, formatos, destinatario y coherencia entre referencias.',
'focus'=>'cómo preparar una cesta gourmet equilibrada',
'content'=><<<'HTML'
<p>Una cesta gourmet equilibrada no es la que más productos contiene. Es la que combina variedad, calidad, utilidad y presupuesto de forma que cada referencia tenga una razón para estar ahí.</p>
<p>Este criterio es especialmente importante en regalos corporativos, donde la selección debe poder explicarse, repetirse y adaptarse al destinatario sin convertirse en una mezcla aleatoria de productos.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres preparar una cesta con una selección coherente?</h2><p>Cuéntanos destinatarios, presupuesto, unidades y fecha. Revisaremos qué productos pueden combinarse de forma realista.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
<h2>Empieza por el destinatario</h2>
<p>Antes de pensar en productos, define quién recibirá la cesta. Un empleado, un cliente estratégico, un proveedor o un invitado de evento pueden requerir enfoques distintos.</p>
<p>También importa si el regalo se disfrutará en casa, en una oficina o durante un evento.</p>
<h2>Elige un producto protagonista</h2>
<p>Una cesta suele ganar claridad cuando existe una referencia principal. Puede ser jamón, paleta, una selección de ibéricos, AOVE o un pack destacado.</p>
<p>El resto de productos deberían acompañar y completar, no competir entre sí.</p>
<h2>Combina categorías, no repitas demasiado</h2>
<p>Si la cesta contiene varios productos, conviene aportar variedad. Por ejemplo, un producto cárnico, un AOVE y una conserva pueden crear una experiencia más completa que tres referencias muy similares.</p>
<p>La variedad debe ser manejable y no convertir el regalo en un surtido sin identidad.</p>
<h2>Equilibra cantidad y calidad</h2>
<p>Añadir productos baratos solo para aumentar el número de referencias puede empeorar el resultado. En muchos casos es preferible reducir cantidad y mejorar la categoría de los elementos principales.</p>
<p>La cesta debe transmitir selección, no relleno.</p>
<h2>Piensa en el momento de consumo</h2>
<p>Una cesta para aperitivo puede centrarse en ibéricos y conservas. Una propuesta de despensa puede incluir AOVE y productos de larga conservación.</p>
<p>Definir el momento de uso ayuda a elegir referencias que tengan relación entre sí.</p>
<h2>Controla el presupuesto desde el principio</h2>
<p>Es fácil superar el límite cuando se añaden productos uno a uno. Conviene reservar la mayor parte del presupuesto para el producto protagonista y distribuir el resto entre complementos.</p>
<p>También debe aclararse si el presupuesto incluye transporte.</p>
<h2>Evita formatos difíciles de gestionar</h2>
<p>Una pieza grande puede ser excelente para determinados destinatarios, pero poco práctica en un evento o para un equipo remoto.</p>
<p>El formato debe adaptarse a cómo se entregará y recibirá la cesta.</p>
<h2>Alcohol: decidirlo de forma consciente</h2>
<p>No es obligatorio incluir vino o cava. Si no se conocen las preferencias del destinatario, una cesta sin alcohol puede ser más transversal.</p>
<p>En ese caso, el presupuesto puede reforzar el resto de productos.</p>
<h2>Restricciones alimentarias</h2>
<p>Si existen alergias, intolerancias o dietas específicas, los ingredientes de cada producto deben revisarse individualmente. No conviene asumir que una cesta estándar es apta para todos.</p>
<p>Cuanto antes se conozcan estas necesidades, más fácil es plantear alternativas.</p>
<h2>Presentación frente a contenido</h2>
<p>La presentación importa, pero no debería consumir una parte desproporcionada del presupuesto. Una caja sencilla con buena selección puede transmitir más valor que un embalaje espectacular con productos mediocres.</p>
<p>Si se necesita personalización, debe confirmarse según vendedor y cantidades.</p>
<h2>Logística en un marketplace</h2>
<p>El Mercado de Origen reúne distintos vendedores y cada uno prepara y expide directamente su pedido. Si una cesta combina referencias de varios productores, hay que revisar la operativa antes de prometer una presentación conjunta.</p>
<p>La composición ideal debe ser también viable logísticamente.</p>
<h2>La prueba final: ¿puedes explicar la cesta en una frase?</h2>
<p>Si puedes resumirla como “una selección de ibéricos con AOVE para compartir” o “una cesta de despensa española sin alcohol”, probablemente existe una lógica clara.</p>
<p>Si necesitas enumerar diez productos para explicar qué es, quizá la selección esté demasiado dispersa.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Construye la cesta alrededor de una idea</h2><p>Dinos presupuesto, destinatarios y fecha y revisaremos una composición que tenga sentido con los productos disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
)
);

$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author=$admins?(int)$admins[0]:1;
$rows=array();

foreach($pages as $p){
    $words=emdo_b2b06_words($p['content']);
    if($words<650) throw new Exception($p['key'].' too short: '.$words);

    $existing=emdo_b2b06_existing($p['key'],$p['slug']);
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
    update_post_meta($id,'_emdo_seo_landing_batch','20260926-b2b-06');
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
echo wp_json_encode(array('batch'=>'20260926-b2b-06','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
