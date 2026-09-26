<?php
if (!defined('ABSPATH')) { exit; }

function emdo_seo_landing_words($html) {
    $text = trim(preg_replace('/\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\p{L}\p{M}]+(?:[’\x{27}’-][\p{L}\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_seo_landing_existing($key, $slug) {
    $ids = get_posts(array(
        'post_type' => 'page',
        'post_status' => 'any',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_key' => '_emdo_seo_landing_key',
        'meta_value' => $key,
    ));
    if ($ids) return (int) $ids[0];

    $p = get_page_by_path($slug, OBJECT, 'page');
    if ($p) return (int) $p->ID;

    $collision = get_page_by_path($slug, OBJECT, 'post');
    if ($collision) {
        throw new Exception('Slug collision with existing post: ' . $slug . ' (ID ' . $collision->ID . ')');
    }
    return 0;
}

function emdo_seo_landing_cta($heading, $copy) {
    return '<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3">'
        . '<h2 style="margin-top:0">' . esc_html($heading) . '</h2>'
        . '<p>' . esc_html($copy) . '</p>'
        . '<p style="margin-bottom:0"><a class="button" href="/contacto/">Cuéntanos qué necesitas</a></p>'
        . '</div>';
}

$pages = array(
array(
'key' => 'b2b-christmas-baskets',
'slug' => 'cestas-lotes-navidad-empresas',
'title' => 'Cestas y lotes de Navidad para empresas',
'excerpt' => 'Cestas y lotes de Navidad para empresas con productos de productores españoles. Cuéntanos número de destinatarios, presupuesto y fecha para estudiar una propuesta.',
'seo_title' => 'Cestas y lotes de Navidad para empresas | El Mercado de Origen',
'seo_description' => '¿Buscas cestas o lotes de Navidad para empleados o clientes? Cuéntanos cantidad, presupuesto y fecha y estudiaremos una propuesta con productos de origen.',
'focus' => 'cestas de Navidad para empresas',
'content' => <<<'HTML'
<p>Cuando una empresa busca <strong>cestas o lotes de Navidad</strong>, normalmente no necesita simplemente “una caja con productos”. Necesita que el regalo encaje con el presupuesto, con el tipo de destinatario, con la imagen que quiere transmitir y con una fecha concreta. Y, si hay varios empleados, clientes o colaboradores, también necesita que la propuesta sea manejable desde el punto de vista de cantidades y entregas.</p>
<p>En El Mercado de Origen trabajamos con productos de productores españoles con procedencia visible: jamones y paletas, ibéricos, aceite de oliva virgen extra, conservas, productos de huerta, carnes y otros alimentos seleccionados. Si estás preparando un regalo de Navidad para tu empresa, <strong>podemos estudiar contigo qué opciones del catálogo encajan mejor</strong> y plantear una propuesta según las necesidades reales del pedido.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Estás preparando las cestas de Navidad de tu empresa?</h2><p>Indícanos cuántos destinatarios tienes, presupuesto aproximado por regalo, fecha deseada y cualquier requisito importante. Revisaremos las opciones disponibles y te diremos qué podemos preparar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
<h2>Una cesta de Navidad debería empezar por el destinatario, no por el catálogo</h2>
<p>No es lo mismo preparar un detalle para un equipo de diez personas que organizar un pedido para una plantilla numerosa. Tampoco es igual regalar a empleados que agradecer la confianza de clientes o colaboradores. Por eso preferimos empezar por cuatro datos: <strong>quién va a recibir el regalo, cuántas unidades necesitas, qué presupuesto manejas y cuándo debe estar resuelto</strong>.</p>
<p>A partir de ahí se puede valorar si interesa un lote centrado en ibéricos, una selección de productos para compartir, un regalo más compacto o una opción con una pieza especialmente protagonista. La disponibilidad de productos, formatos y condiciones de envío se confirma en cada caso antes de cerrar la propuesta.</p>
<h2>Qué productos pueden tener sentido en un lote de Navidad</h2>
<p>Una cesta no mejora por llevar más referencias. Muchas veces funciona mejor una selección corta y coherente que una acumulación de productos sin relación entre sí. Dependiendo del presupuesto y del perfil del destinatario, se pueden valorar alternativas como:</p>
<ul>
<li><strong>Jamón o paleta ibérica</strong>, cuando se busca un regalo con una pieza principal clara.</li>
<li><strong>Embutidos y curados</strong>, útiles para crear una selección de aperitivo y compartir.</li>
<li><strong>Aceite de oliva virgen extra</strong>, un producto español reconocible, práctico y fácil de integrar en distintos tipos de regalo.</li>
<li><strong>Conservas y productos de huerta</strong>, que aportan variedad y permiten construir propuestas menos centradas únicamente en productos cárnicos.</li>
<li><strong>Packs ya disponibles</strong> en el catálogo, cuando encajan por contenido, formato y presupuesto.</li>
</ul>
<p>El objetivo es que el regalo tenga una lógica: que quien lo reciba entienda qué contiene, de dónde viene y por qué esos productos se han elegido.</p>
<h2>Cestas para empleados y lotes para clientes: no tienen por qué ser iguales</h2>
<p>Para empleados suele ser importante trabajar con un presupuesto homogéneo y una solución que pueda repetirse en todas las unidades. En clientes puede tener más sentido diferenciar por nivel de relación, crear dos o tres escalones de presupuesto o reservar determinadas opciones para cuentas estratégicas.</p>
<p>Si necesitas ambas cosas, podemos estudiar la solicitud como dos grupos diferentes. De ese modo no se fuerza una única cesta para perfiles que quizá requieren un enfoque distinto.</p>
<h2>Cómo plantear el presupuesto por cesta</h2>
<p>Antes de elegir productos conviene definir un <strong>presupuesto máximo por destinatario</strong>. Ese dato permite descartar rápidamente opciones que no encajan y concentrarse en propuestas realistas. También conviene aclarar si ese presupuesto debe incluir transporte u otros costes asociados al pedido.</p>
<p>No es necesario llegar con una cifra cerrada al céntimo. Puedes indicarnos un intervalo —por ejemplo, “alrededor de 40 euros”, “entre 60 y 80” o “queremos una propuesta especial para una parte de los clientes”— y a partir de ahí revisaremos qué alternativas existen en el catálogo.</p>
<h2>¿Se pueden preparar pedidos para varias direcciones?</h2>
<p>La logística depende de los productos elegidos, de los productores implicados, del número de destinatarios y de las direcciones de entrega. El Mercado de Origen es un marketplace: <strong>cada vendedor identificado en el producto prepara y expide directamente su pedido</strong>. Por eso, cuando una empresa necesita varias unidades o múltiples destinos, primero tenemos que revisar la composición del pedido y confirmar qué organización es viable.</p>
<p>Si tienes una relación de direcciones, varias sedes o empleados en remoto, indícalo desde el principio. Así podremos valorar la solicitud teniendo en cuenta la parte logística y no únicamente la selección de productos.</p>
<h2>Cuándo conviene encargar las cestas de Navidad de empresa</h2>
<p>Cuanto mayor sea el número de unidades o más específica sea la selección, más sentido tiene empezar con antelación. Noviembre y diciembre concentran una parte importante de la demanda de productos gastronómicos y algunas referencias pueden tener disponibilidad limitada.</p>
<p>Si el pedido es para una fecha concreta, dinos esa fecha en el primer contacto. No daremos por confirmada una entrega hasta revisar disponibilidad y condiciones, pero contar con ese dato desde el principio ayuda a plantear opciones realistas.</p>
<h2>Un regalo con productores y procedencia visibles</h2>
<p>Una de las diferencias de El Mercado de Origen es que los productos no aparecen desligados de quien los hace. En la tienda puedes conocer al productor, su procedencia y las características de cada propuesta. Para una empresa, esto permite que el regalo tenga algo más que un contenido gastronómico: <strong>hay una historia y un origen detrás de lo que se entrega</strong>.</p>
<p>Eso puede ser especialmente interesante cuando el objetivo no es regalar por cumplir, sino ofrecer un detalle que represente cuidado en la elección.</p>
<h2>Qué información necesitamos para estudiar tu solicitud</h2>
<p>Para poder orientarte con rapidez, incluye en el mensaje el número aproximado de regalos, presupuesto por unidad o presupuesto total, tipo de destinatario, fecha deseada, provincia o provincias de entrega y si existe algún requisito especial. Si todavía estás comparando opciones, también puedes explicarnos simplemente qué idea tienes y te diremos qué información falta para poder concretarla.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Cuéntanos tu idea de cesta o lote de Navidad</h2><p>No necesitas tener la composición decidida. Envíanos cantidades, presupuesto y fecha objetivo y estudiaremos qué opciones de El Mercado de Origen pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key' => 'b2b-gourmet-gifts',
'slug' => 'regalos-gourmet-empresas',
'title' => 'Regalos gourmet para empresas',
'excerpt' => 'Regalos gourmet para empresas con productos de productores españoles. Propuestas para clientes, empleados, colaboradores, eventos y otras ocasiones corporativas.',
'seo_title' => 'Regalos gourmet para empresas | El Mercado de Origen',
'seo_description' => 'Regalos gourmet para empresas con productos de origen: clientes, empleados, eventos o agradecimientos. Cuéntanos presupuesto, unidades y fecha.',
'focus' => 'regalos gourmet para empresas',
'content' => <<<'HTML'
<p>Un <strong>regalo gourmet de empresa</strong> puede utilizarse en Navidad, pero también para agradecer una colaboración, reconocer a un equipo, acompañar un evento, celebrar un hito o cuidar una relación con un cliente. La dificultad está en encontrar algo que resulte especial sin convertirse en un objeto promocional más que termina olvidado.</p>
<p>En El Mercado de Origen trabajamos con alimentos de productores españoles seleccionados y con información clara sobre su procedencia. Si buscas un regalo corporativo gastronómico, podemos revisar contigo el catálogo y <strong>estudiar una propuesta en función del presupuesto, número de unidades, destinatarios y fecha</strong>.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Necesitas un regalo gourmet para tu empresa?</h2><p>Explícanos la ocasión, cuántas unidades necesitas y qué presupuesto manejas. Revisaremos las opciones disponibles y te indicaremos qué podemos plantear.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Hablar con El Mercado de Origen</a></p></div>
<h2>Qué hace que un regalo gastronómico funcione en un contexto profesional</h2>
<p>En un regalo corporativo importa el producto, pero también el mensaje. Un detalle demasiado genérico puede parecer impersonal; uno excesivamente específico puede no encajar con todos los destinatarios. La selección debería buscar un equilibrio entre <strong>calidad percibida, utilidad, facilidad de consumo y coherencia con la ocasión</strong>.</p>
<p>Los alimentos tienen una ventaja: se disfrutan, se comparten y no ocupan espacio de forma permanente. Además, cuando la procedencia está clara, el regalo puede contar algo sobre quién lo produce y cómo ha llegado hasta la persona que lo recibe.</p>
<h2>Regalos para clientes, empleados, colaboradores o ponentes</h2>
<p>No todos los regalos de empresa cumplen la misma función. Para un cliente puede ser una forma de agradecer confianza. Para un empleado, un reconocimiento. En un congreso puede convertirse en un detalle de bienvenida o despedida. Para un colaborador externo, puede cerrar un proyecto con un gesto personal.</p>
<p>Definir la relación con el destinatario ayuda a elegir mejor. También permite decidir si conviene una propuesta idéntica para todos o diferentes niveles de regalo según el grupo.</p>
<h2>Ideas de producto según el tipo de regalo</h2>
<p>Dependiendo de disponibilidad y presupuesto, se pueden valorar productos como AOVE, jamón o paleta, ibéricos, conservas, productos de huerta o packs ya existentes. Algunas ocasiones piden una pieza protagonista; otras funcionan mejor con una selección de varios productos pequeños.</p>
<p>Por ejemplo, un aceite de oliva virgen extra puede ser adecuado cuando se busca un regalo práctico y reconocible. Un surtido de ibéricos puede funcionar mejor para compartir. Una pieza de jamón o paleta aumenta el peso simbólico del regalo, pero exige valorar formato, presupuesto y perfil del destinatario.</p>
<h2>El presupuesto: mejor definirlo antes de enamorarse de una propuesta</h2>
<p>En regalos corporativos es habitual empezar por el producto y descubrir después que multiplicarlo por todas las unidades se sale del presupuesto. Nosotros preferimos hacerlo al revés: <strong>define primero el rango económico</strong> y construyamos a partir de ahí.</p>
<p>Si hay distintos grupos de destinatarios, puedes indicarnos varios escalones. Por ejemplo, una opción general para equipo, otra para clientes y una tercera para relaciones especialmente estratégicas. Lo importante es que cada grupo tenga una lógica clara.</p>
<h2>Regalos gourmet de empresa durante todo el año</h2>
<p>La Navidad concentra mucha demanda, pero no es la única ocasión. Un regalo gastronómico puede utilizarse en aniversarios de empresa, incorporaciones, consecución de objetivos, eventos, reuniones anuales, visitas de clientes, inauguraciones o acciones de fidelización.</p>
<p>Al no depender exclusivamente de una campaña navideña, es posible trabajar con un enfoque más flexible y elegir el producto en función del momento concreto.</p>
<h2>¿Se puede personalizar un regalo corporativo?</h2>
<p>Si necesitas tarjetas, mensajes, presentaciones especiales, agrupaciones concretas u otros elementos de personalización, indícalo en la consulta. <strong>No damos por disponible una personalización hasta revisar cada caso</strong>, porque depende del producto, del vendedor, de las cantidades y del plazo.</p>
<p>Lo mismo ocurre con los envíos a distintas direcciones. El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente su pedido. Antes de confirmar una operativa corporativa hay que comprobar qué productos intervienen y cómo puede organizarse.</p>
<h2>Por qué puede tener sentido regalar productos de origen español</h2>
<p>Un regalo empresarial no necesita llevar el logotipo de la compañía para representar sus valores. Elegir productos con procedencia identificable puede transmitir atención al detalle, interés por el producto y reconocimiento al trabajo de quien lo elabora.</p>
<p>En El Mercado de Origen procuramos que el productor sea visible. Eso permite que el destinatario no reciba solamente “aceite”, “jamón” o “una conserva”, sino que pueda saber de dónde viene y quién está detrás.</p>
<h2>Cómo pedir una propuesta</h2>
<p>Cuéntanos la ocasión, número de regalos, presupuesto orientativo, fecha deseada, destino o destinos y cualquier condición que debamos considerar. Si no sabes qué producto encaja, no pasa nada: precisamente esos datos nos sirven para reducir opciones y plantear alternativas más concretas.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Convierte la idea en una propuesta concreta</h2><p>Dinos para quién es el regalo y qué presupuesto tienes. Revisaremos la selección disponible y te responderemos con las opciones que podamos ofrecer.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key' => 'b2b-client-gifts',
'slug' => 'regalos-clientes-empresa',
'title' => 'Regalos para clientes de empresa',
'excerpt' => 'Ideas y propuestas de regalos para clientes de empresa con productos gastronómicos de productores españoles, adaptadas a presupuesto, volumen y ocasión.',
'seo_title' => 'Regalos para clientes de empresa | El Mercado de Origen',
'seo_description' => '¿Buscas regalos para clientes? Estudiamos propuestas gastronómicas según presupuesto, número de destinatarios, ocasión y fecha de entrega.',
'focus' => 'regalos para clientes de empresa',
'content' => <<<'HTML'
<p>Regalar a un cliente es distinto de regalar a un amigo o familiar. Existe una relación profesional detrás y el detalle tiene que transmitir agradecimiento sin resultar incómodo, excesivo o impersonal. Por eso, antes de elegir un producto, conviene pensar <strong>qué quieres reconocer, qué nivel de relación existe y qué presupuesto es razonable</strong>.</p>
<p>En El Mercado de Origen podemos estudiar propuestas gastronómicas para clientes utilizando productos de productores españoles presentes en nuestro catálogo. Si nos indicas número de destinatarios, presupuesto, fecha y tipo de cliente, revisaremos qué alternativas pueden encajar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres preparar un detalle para tus clientes?</h2><p>Cuéntanos cuántos regalos necesitas, tu presupuesto aproximado y para qué ocasión. Te diremos qué opciones podemos estudiar con los productos disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar información</a></p></div>
<h2>El mejor regalo para un cliente no siempre es el más caro</h2>
<p>Un regalo puede fallar por exceso igual que por defecto. Si la relación es reciente, un detalle desproporcionado puede resultar extraño. Si se trata de una colaboración importante y prolongada, una propuesta demasiado genérica puede parecer poco cuidada. La clave es que el regalo sea <strong>coherente con la relación profesional</strong>.</p>
<p>La gastronomía permite trabajar con distintos niveles: desde un producto único bien elegido hasta un lote, un surtido o una pieza de mayor valor.</p>
<h2>Segmentar clientes ayuda a elegir mejor</h2>
<p>Si tienes una cartera amplia, no es obligatorio entregar exactamente el mismo regalo a todos. Muchas empresas diferencian entre clientes habituales, cuentas estratégicas, colaboradores o nuevos clientes. Esa segmentación permite definir dos o tres presupuestos y evitar una solución única que no encaja igual de bien en todos los casos.</p>
<p>Lo importante es establecer criterios internos claros y mantener coherencia dentro de cada grupo.</p>
<h2>Qué productos pueden funcionar como regalo para clientes</h2>
<p>Los productos gastronómicos tienen la ventaja de ser consumibles y fáciles de compartir. Dependiendo del perfil y del presupuesto, se pueden valorar:</p>
<ul>
<li><strong>Aceite de oliva virgen extra</strong>, práctico y ligado a un producto español muy reconocible.</li>
<li><strong>Ibéricos y curados</strong>, especialmente adecuados para aperitivos, celebraciones o regalos destinados a compartir.</li>
<li><strong>Jamón o paleta</strong>, cuando se busca una pieza principal con mayor presencia.</li>
<li><strong>Conservas y productos de huerta</strong>, útiles para ampliar la selección y crear regalos gastronómicos menos previsibles.</li>
<li><strong>Packs disponibles en el catálogo</strong>, cuando su composición encaja con el destinatario y el presupuesto.</li>
</ul>
<p>No se trata de incluirlo todo. Una selección breve y comprensible suele transmitir más criterio que una cesta cargada de referencias sin relación.</p>
<h2>Regalos para clientes en Navidad</h2>
<p>La Navidad es el momento más habitual, pero también el de mayor saturación. Si quieres que un regalo navideño tenga sentido, conviene evitar decidirlo a última hora. El volumen de pedidos aumenta y algunas referencias pueden tener disponibilidad limitada.</p>
<p>Si ya sabes que vas a enviar regalos a clientes en diciembre, puedes contactar antes indicando una cifra aproximada de unidades. No necesitas tener cerrada la lista de destinatarios para empezar a estudiar opciones.</p>
<h2>Más allá de Navidad: cuándo puede tener sentido un detalle</h2>
<p>También puede utilizarse un regalo gastronómico para celebrar un aniversario de colaboración, agradecer un proyecto, recibir a un cliente que visita la empresa, cerrar una operación importante o acompañar un evento corporativo.</p>
<p>En estos casos el contexto es muy útil. Un regalo vinculado a un momento concreto se percibe de otra manera que un envío sin explicación.</p>
<h2>Mensaje, presentación y personalización</h2>
<p>Si quieres incorporar una nota, un mensaje o algún elemento específico de presentación, explícalo en la solicitud. La posibilidad de personalizar depende de cada producto, productor, cantidad y plazo, por lo que <strong>debe confirmarse antes de cerrar el pedido</strong>.</p>
<p>También podemos valorar solicitudes en las que existan varios destinos. Como marketplace, cada vendedor de El Mercado de Origen prepara y expide directamente su pedido, de modo que necesitamos revisar la composición concreta para saber qué operativa es posible.</p>
<h2>Qué información facilita una propuesta útil</h2>
<p>Cuanto más claro sea el punto de partida, menos tiempo se pierde comparando alternativas que no sirven. Incluye: número aproximado de clientes, presupuesto por regalo, fecha deseada, provincias de entrega, si todos recibirán lo mismo y si existe alguna condición especial.</p>
<p>Si todavía no conoces el presupuesto exacto, puedes darnos un rango. También puedes contarnos qué tipo de relación tienes con los destinatarios y qué sensación quieres transmitir: un agradecimiento sencillo, un detalle premium o una propuesta para compartir con su equipo.</p>
<h2>Productos con procedencia y productor visibles</h2>
<p>En un regalo para clientes, la historia puede sumar. El Mercado de Origen muestra quién está detrás de los productos y de dónde proceden. Eso permite que el destinatario entienda que no se ha elegido un artículo anónimo, sino una propuesta vinculada a un productor concreto.</p>
<p>Para nosotros ese origen forma parte del valor del regalo.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Cuéntanos a quién quieres regalar</h2><p>Con el número de clientes, el presupuesto y la fecha podemos empezar a estudiar una propuesta realista.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key' => 'b2b-employee-gifts',
'slug' => 'regalos-empleados-empresa',
'title' => 'Regalos para empleados: ideas gastronómicas para empresas',
'excerpt' => 'Regalos para empleados con productos gastronómicos de productores españoles. Ideas para Navidad, reconocimiento, equipos en remoto y otras ocasiones.',
'seo_title' => 'Regalos para empleados de empresa | El Mercado de Origen',
'seo_description' => 'Regalos gastronómicos para empleados según presupuesto, número de personas y ocasión. Cuéntanos qué necesitas y estudiaremos opciones.',
'focus' => 'regalos para empleados',
'content' => <<<'HTML'
<p>Un regalo para empleados puede ser una cesta de Navidad, pero también una forma de celebrar un objetivo, reconocer una trayectoria, agradecer un esfuerzo especial o acompañar un encuentro de equipo. En todos los casos hay una pregunta básica: <strong>¿cómo elegir algo que resulte agradable para muchas personas sin que parezca un detalle genérico?</strong></p>
<p>En El Mercado de Origen podemos estudiar propuestas gastronómicas basadas en productos de productores españoles. Si nos indicas cuántas personas forman el equipo, presupuesto, fecha y cualquier requisito relevante, revisaremos qué opciones pueden encajar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Estás preparando un regalo para tu equipo?</h2><p>Dinos número de empleados, presupuesto por persona, ocasión y fecha. Estudiaremos qué propuestas podemos plantear con el catálogo disponible.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Cuéntanos tu caso</a></p></div>
<h2>Primero: define qué quieres conseguir con el regalo</h2>
<p>No todos los detalles para empleados tienen el mismo significado. Una cesta de Navidad forma parte de una tradición. Un regalo por objetivos reconoce un resultado. Un detalle de bienvenida marca una incorporación. Un regalo tras un proyecto intenso puede ser simplemente una forma de decir gracias.</p>
<p>Definir la ocasión ayuda a decidir cuánto protagonismo debe tener el regalo, qué presupuesto resulta razonable y si conviene algo individual o pensado para compartir.</p>
<h2>Regalos de Navidad para empleados</h2>
<p>En Navidad suele ser importante que la propuesta pueda repetirse de manera homogénea para toda la plantilla. También conviene anticipar la logística, especialmente cuando hay varias sedes o personas que trabajan en remoto.</p>
<p>Si estás preparando lotes navideños, indícanos el número aproximado de unidades lo antes posible. Las referencias gastronómicas pueden tener disponibilidad variable y las semanas previas a Navidad concentran más demanda.</p>
<h2>Ideas gastronómicas según presupuesto</h2>
<p>Un presupuesto menor no obliga a elegir un regalo sin personalidad. Puede tener sentido concentrarlo en uno o dos productos con procedencia clara. En rangos superiores se puede valorar una selección más amplia, un pack o una pieza protagonista.</p>
<p>Entre las familias disponibles en El Mercado de Origen se encuentran aceite de oliva virgen extra, ibéricos, jamón y paleta, conservas, productos de huerta, carne y distintos packs. La elección concreta depende de la disponibilidad y de la operativa del pedido.</p>
<h2>Equipos presenciales, varias sedes y trabajo en remoto</h2>
<p>La forma de entregar el regalo cambia mucho la propuesta. Si todo el equipo trabaja en una sede, la operativa puede ser distinta de un equipo distribuido por varias provincias. Por eso es importante indicar desde el principio <strong>si necesitas una entrega centralizada o varios destinos</strong>.</p>
<p>El Mercado de Origen es un marketplace y cada vendedor prepara y envía directamente los productos de su pedido. Cuando hay muchas direcciones o intervienen varios productores, necesitamos revisar el caso antes de confirmar qué organización es viable.</p>
<h2>¿Tiene sentido ofrecer varias opciones al empleado?</h2>
<p>En algunos equipos puede ser útil trabajar con dos alternativas en lugar de una única propuesta, especialmente si hay preferencias alimentarias muy diferentes. Sin embargo, ofrecer demasiadas opciones también complica la gestión. Si estás valorando un sistema de elección, cuéntanos cómo te gustaría organizarlo y comprobaremos qué puede hacerse con el catálogo y la logística disponibles.</p>
<p>Si existen alergias, restricciones dietéticas u otras condiciones relevantes, deben comunicarse con claridad. No debe darse por supuesto que un lote concreto es apto para una necesidad alimentaria sin revisar los ingredientes y etiquetados de cada producto.</p>
<h2>Regalos para reconocer objetivos o antigüedad</h2>
<p>Cuando el regalo está vinculado a un reconocimiento, puede tener sentido aumentar la singularidad: una pieza de jamón, una selección de ibéricos, un lote de mayor valor o un producto que el empleado pueda compartir en casa. La clave es que el nivel del regalo sea coherente con el motivo.</p>
<p>También puede trabajarse con diferentes escalones cuando existen hitos de antigüedad o reconocimientos internos definidos por la empresa.</p>
<h2>El valor de saber quién produce lo que regalas</h2>
<p>Los productos de El Mercado de Origen mantienen visible al productor. Para un empleado, recibir un regalo acompañado de información sobre su procedencia puede hacer que el detalle resulte más interesante y menos anónimo.</p>
<p>La idea no es llenar el regalo de mensajes corporativos, sino permitir que el propio producto explique parte de su valor: origen, elaboración y persona o proyecto que hay detrás.</p>
<h2>Cómo solicitar una propuesta para empleados</h2>
<p>Para empezar necesitamos pocos datos: número de personas, presupuesto por empleado, fecha objetivo, lugar o lugares de entrega y ocasión. Si estás en una fase inicial, también puedes explicarnos simplemente qué estás buscando y qué límites tienes.</p>
<p>Antes de confirmar cualquier pedido revisaremos disponibilidad, vendedores implicados y condiciones de entrega. De esa forma la propuesta parte de lo que realmente se puede preparar, no de un catálogo teórico.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Prepara el regalo de tu equipo con tiempo</h2><p>Envíanos cantidades, presupuesto y fecha. Revisaremos las alternativas y te responderemos con una propuesta posible.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar con nosotros</a></p></div>
HTML
),
array(
'key' => 'b2b-iberian-ham-corporate',
'slug' => 'jamon-iberico-empresas-regalos-corporativos',
'title' => 'Jamón ibérico para empresas y regalos corporativos',
'excerpt' => 'Jamón ibérico para empresas: piezas, paletas y formatos para regalos corporativos, clientes o empleados. Estudiamos cantidades, presupuesto y fecha.',
'seo_title' => 'Jamón ibérico para empresas y regalos corporativos',
'seo_description' => '¿Buscas jamón ibérico para regalar desde tu empresa? Revisamos piezas, paletas y formatos según presupuesto, unidades, destinatarios y fecha.',
'focus' => 'jamón ibérico para empresas',
'content' => <<<'HTML'
<p>El jamón ibérico es uno de los regalos gastronómicos españoles con mayor reconocimiento, pero en un pedido de empresa conviene elegirlo con más criterio que simplemente “una pieza de jamón”. Hay diferencias de categoría, formato, peso, presupuesto y perfil de destinatario que pueden cambiar por completo la propuesta.</p>
<p>En El Mercado de Origen trabajamos con jamones y paletas de productores presentes en el marketplace. Si buscas <strong>jamón ibérico para clientes, empleados, colaboradores o un regalo corporativo</strong>, podemos revisar las opciones disponibles y estudiar el pedido según número de unidades, presupuesto y fecha.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Necesitas varias piezas o un regalo corporativo con ibéricos?</h2><p>Indícanos cuántas unidades necesitas, presupuesto aproximado, destinatarios y fecha. Revisaremos los formatos disponibles y la viabilidad del pedido.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
<h2>Jamón o paleta: una de las primeras decisiones</h2>
<p>Para un regalo empresarial, la paleta puede ofrecer una entrada de presupuesto diferente y un tamaño más manejable, mientras que el jamón suele tener mayor presencia y rendimiento. No existe una opción universalmente mejor: depende del destinatario, del presupuesto y del tipo de regalo que la empresa quiera hacer.</p>
<p>Si el objetivo es una pieza completa para una familia o un cliente importante, puede tener sentido priorizar presencia y categoría. Si se necesitan más unidades con un presupuesto ajustado, una paleta puede abrir alternativas interesantes.</p>
<h2>Pieza entera o producto loncheado</h2>
<p>Una pieza entera tiene impacto visual y ritual: requiere soporte, cuchillo, cierta experiencia de corte y tiempo de consumo. El formato loncheado es más cómodo, permite abrir pequeñas cantidades y reduce la barrera para quien no está acostumbrado a cortar jamón.</p>
<p>En un regalo corporativo conviene pensar en el receptor. Para un cliente aficionado al jamón, la pieza puede ser parte del atractivo. Para destinatarios muy diversos, el formato loncheado puede resultar más práctico. La disponibilidad de cada formato debe confirmarse en el producto y en el momento del pedido.</p>
<h2>Cómo influye la categoría del ibérico en el presupuesto</h2>
<p>Dentro del ibérico existen distintas designaciones relacionadas con alimentación y porcentaje racial. Esa información aparece en el etiquetado y se refleja en precio y posicionamiento del producto. Cuando una empresa compra varias unidades, pequeñas diferencias por pieza se multiplican rápidamente.</p>
<p>Por eso recomendamos definir primero el presupuesto por destinatario. Con esa cifra podemos revisar si encaja mejor una paleta, un jamón, un formato loncheado o una selección de otros ibéricos.</p>
<h2>Regalar jamón a clientes</h2>
<p>En clientes, el jamón funciona especialmente bien cuando se busca un regalo con presencia y vinculado a gastronomía española. Puede reservarse para cuentas estratégicas o integrarse en una política de regalos por niveles.</p>
<p>Si hay distintos grupos de clientes, indícanos cuántos destinatarios hay en cada uno y el presupuesto correspondiente. Así podemos estudiar alternativas sin forzar la misma pieza para todas las relaciones comerciales.</p>
<h2>Regalar jamón a empleados</h2>
<p>Para empleados, el factor principal suele ser la homogeneidad: mismas condiciones y un presupuesto controlable para toda la plantilla. Aquí el formato y el peso importan mucho porque afectan tanto al coste como a la facilidad de entrega y uso en casa.</p>
<p>Si el equipo está distribuido en varias localizaciones, también necesitamos conocer la logística prevista. Como marketplace, cada vendedor prepara y expide directamente su pedido, por lo que un envío corporativo debe revisarse según el producto y los destinos.</p>
<h2>¿Jamón solo o dentro de un lote?</h2>
<p>Una pieza puede ser suficiente por sí misma. En otros casos tiene sentido acompañarla con embutidos, aceite de oliva u otros productos. No recomendamos añadir referencias únicamente para “llenar” un lote: si se crea una combinación, debería haber una lógica de consumo y presupuesto.</p>
<p>También hay empresas que prefieren evitar la pieza completa y construir un regalo a partir de loncheados y curados. Esa opción puede facilitar el reparto y ofrecer variedad.</p>
<h2>Qué conviene revisar antes de cerrar un pedido corporativo</h2>
<ul>
<li><strong>Número de unidades.</strong> Permite comprobar disponibilidad real.</li>
<li><strong>Presupuesto por destinatario.</strong> Ayuda a filtrar categoría, formato y peso.</li>
<li><strong>Fecha de entrega.</strong> Especialmente importante en campañas navideñas.</li>
<li><strong>Destinos.</strong> Una única sede no se gestiona igual que muchas direcciones particulares.</li>
<li><strong>Formato.</strong> Pieza entera, paleta, loncheado u otras combinaciones.</li>
<li><strong>Preferencias o restricciones.</strong> Deben comunicarse antes de definir la propuesta.</li>
</ul>
<h2>Pedidos de Navidad: mejor anticiparse</h2>
<p>Jamones y paletas son productos especialmente demandados en las semanas previas a Navidad. Si necesitas varias unidades, conviene iniciar la consulta con antelación. La disponibilidad de pesos, categorías o formatos concretos puede cambiar a medida que avanza la campaña.</p>
<p>No reservaremos ni prometeremos una referencia hasta confirmar el pedido, pero una consulta temprana permite trabajar con más opciones.</p>
<h2>Jamón con productor y procedencia identificables</h2>
<p>En El Mercado de Origen queremos que el producto conserve el vínculo con quien lo elabora. En cada ficha puedes conocer el vendedor, características del producto y procedencia disponible. Para un regalo corporativo, esa transparencia aporta contexto y evita que el jamón se perciba como una pieza anónima.</p>
<p>Si quieres que estudiemos un pedido de empresa, no necesitas decidir previamente qué jamón comprar. Con cantidades, presupuesto, destinatarios y fecha podemos revisar las alternativas y explicarte cuáles son viables.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Cuéntanos qué tipo de regalo quieres preparar</h2><p>Revisaremos las opciones de jamón, paleta y otros ibéricos disponibles para construir una propuesta acorde al pedido.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir a contacto</a></p></div>
HTML
)
);

$admins = get_users(array('role' => 'administrator', 'number' => 1, 'fields' => 'ID'));
$author = $admins ? (int) $admins[0] : 1;
$rows = array();

foreach ($pages as $p) {
    $words = emdo_seo_landing_words($p['content']);
    if ($words < 650) {
        throw new Exception($p['key'] . ' too short: ' . $words . ' words');
    }

    $existing = emdo_seo_landing_existing($p['key'], $p['slug']);
    $postarr = array(
        'post_type' => 'page',
        'post_status' => 'publish',
        'post_author' => $author,
        'post_title' => wp_strip_all_tags($p['title']),
        'post_name' => sanitize_title($p['slug']),
        'post_excerpt' => wp_strip_all_tags($p['excerpt']),
        'post_content' => $p['content'],
        'comment_status' => 'closed',
        'ping_status' => 'closed',
        'post_parent' => 0,
        'menu_order' => 0,
    );
    if ($existing) $postarr['ID'] = $existing;

    $result = $existing ? wp_update_post($postarr, true) : wp_insert_post($postarr, true);
    if (is_wp_error($result)) {
        throw new Exception($p['key'] . ': ' . $result->get_error_message());
    }

    $id = (int) $result;
    update_post_meta($id, '_emdo_seo_landing_key', $p['key']);
    update_post_meta($id, '_emdo_seo_landing_batch', '20260926-b2b-01');
    update_post_meta($id, '_emdo_seo_landing_hidden_navigation', '1');
    update_post_meta($id, '_emdo_seo_landing_updated_at', gmdate('c'));

    update_post_meta($id, '_yoast_wpseo_title', $p['seo_title']);
    update_post_meta($id, '_yoast_wpseo_metadesc', $p['seo_description']);
    update_post_meta($id, '_yoast_wpseo_focuskw', $p['focus']);
    update_post_meta($id, 'rank_math_title', $p['seo_title']);
    update_post_meta($id, 'rank_math_description', $p['seo_description']);
    update_post_meta($id, 'rank_math_focus_keyword', $p['focus']);

    delete_post_meta($id, '_yoast_wpseo_meta-robots-noindex');
    delete_post_meta($id, 'rank_math_robots');
    clean_post_cache($id);

    $rows[] = array(
        'key' => $p['key'],
        'id' => $id,
        'title' => get_the_title($id),
        'slug' => get_post_field('post_name', $id),
        'type' => get_post_type($id),
        'status' => get_post_status($id),
        'words' => $words,
        'url' => get_permalink($id),
    );
}

if (count($rows) !== 5) throw new Exception('Expected 5 landing pages');

echo wp_json_encode(array(
    'batch' => '20260926-b2b-01',
    'count' => count($rows),
    'pages' => $rows,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . PHP_EOL;
