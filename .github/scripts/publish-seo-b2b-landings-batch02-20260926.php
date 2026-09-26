<?php
if (!defined('ABSPATH')) { exit; }

function emdo_b2b02_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_b2b02_existing($key, $slug) {
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
    if ($collision) throw new Exception('Slug collision with post: '.$slug.' ID '.$collision->ID);
    return 0;
}

$pages = array(
array(
'key'=>'b2b-christmas-no-alcohol',
'slug'=>'cestas-navidad-sin-alcohol',
'title'=>'Cestas de Navidad sin alcohol',
'excerpt'=>'Cestas de Navidad sin alcohol para empresas, empleados y clientes con productos gastronómicos de productores españoles. Cuéntanos presupuesto, unidades y fecha.',
'seo_title'=>'Cestas de Navidad sin alcohol para empresas | El Mercado de Origen',
'seo_description'=>'Cestas de Navidad sin alcohol con ibéricos, AOVE, conservas y otros productos españoles. Cuéntanos unidades, presupuesto y fecha.',
'focus'=>'cestas de Navidad sin alcohol',
'content'=><<<'HTML'
<p>Una cesta de Navidad no necesita vino, cava ni ninguna otra bebida alcohólica para resultar completa. De hecho, cada vez hay más empresas que buscan <strong>cestas de Navidad sin alcohol</strong> porque quieren una propuesta válida para equipos diversos, destinatarios con distintas preferencias o políticas internas que aconsejan evitar este tipo de productos.</p>
<p>En El Mercado de Origen podemos estudiar propuestas centradas exclusivamente en alimentos: aceite de oliva virgen extra, jamón y paleta, ibéricos, conservas, productos de huerta y otras referencias disponibles en nuestro marketplace. Si nos dices cuántas unidades necesitas, presupuesto aproximado y fecha, revisaremos qué combinación puede tener sentido.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas una cesta navideña sin bebidas alcohólicas?</h2><p>Cuéntanos número de destinatarios, presupuesto y fecha. Estudiaremos una propuesta basada en productos gastronómicos de productores españoles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
<h2>Por qué una cesta sin alcohol puede ser más sencilla de compartir</h2>
<p>Cuando una empresa regala a muchas personas, no conoce necesariamente los hábitos de consumo de cada destinatario. Puede haber personas que no beban alcohol por elección, salud, religión, embarazo, tratamiento médico o simplemente porque no les interesa. Prescindir de las bebidas evita tener que acertar con esa preferencia y permite concentrar todo el presupuesto en alimentos.</p>
<p>Eso no significa renunciar a una cesta especial. Al contrario: el espacio y el presupuesto que normalmente ocuparían vino o cava pueden dedicarse a productos con más peso gastronómico o a una selección más amplia de referencias.</p>
<h2>Qué productos pueden formar una cesta de Navidad sin alcohol</h2>
<p>La composición depende de la disponibilidad y del presupuesto, pero existen muchas familias que pueden combinarse sin recurrir a bebidas alcohólicas.</p>
<ul>
<li><strong>Aceite de oliva virgen extra:</strong> práctico, reconocible y adecuado para perfiles muy distintos.</li>
<li><strong>Jamón o paleta:</strong> una opción protagonista cuando el presupuesto permite una pieza o un formato de mayor valor.</li>
<li><strong>Embutidos y curados:</strong> ideales para crear un lote pensado para aperitivos y comidas compartidas.</li>
<li><strong>Conservas:</strong> aportan variedad y permiten jugar con formatos y presupuestos.</li>
<li><strong>Productos de huerta y despensa:</strong> útiles para construir una propuesta menos centrada en ibéricos.</li>
<li><strong>Packs ya disponibles:</strong> cuando su composición se ajusta al objetivo del regalo.</li>
</ul>
<p>La idea no es incluir de todo, sino crear una selección que resulte coherente y fácil de disfrutar.</p>
<h2>Cestas sin alcohol para empleados</h2>
<p>En plantillas amplias, evitar el alcohol puede simplificar la decisión. También puede encajar mejor con políticas internas de recursos humanos o con empresas que prefieren un regalo más universal.</p>
<p>Si el equipo es numeroso, conviene fijar primero el presupuesto por persona y la fecha de entrega. Si además existen varias sedes o trabajadores en remoto, necesitamos saberlo antes de confirmar la operativa.</p>
<h2>Cestas sin alcohol para clientes</h2>
<p>Para clientes, una propuesta sin alcohol puede seguir teniendo una presentación premium. Una buena pieza ibérica, un AOVE seleccionado o una combinación de productos con procedencia clara pueden transmitir cuidado sin depender de una botella para elevar el valor percibido.</p>
<p>También puede ser útil cuando no conoces bien los hábitos del cliente o cuando el regalo se dirige a una empresa y no a una persona concreta.</p>
<h2>Cómo distribuir el presupuesto cuando no hay vino ni cava</h2>
<p>Eliminar las bebidas permite reasignar presupuesto. Algunas empresas prefieren mejorar la categoría de un producto principal; otras incorporan más variedad. La decisión depende de qué sensación se quiera transmitir.</p>
<p>Por ejemplo, con un presupuesto determinado puede ser preferible elegir un buen AOVE, una selección de ibéricos y una conserva de calidad antes que ampliar el número de referencias con productos menos relevantes. En presupuestos superiores, una pieza de jamón o paleta puede convertirse en el centro del regalo.</p>
<h2>¿Es una cesta sin alcohol adecuada para cualquier destinatario?</h2>
<p>Eliminar el alcohol resuelve una preferencia, pero no convierte automáticamente la cesta en apta para cualquier dieta. Puede haber alérgenos, productos de origen animal, gluten u otros ingredientes que deban revisarse individualmente.</p>
<p>Si la empresa necesita tener en cuenta alergias o restricciones concretas, debe indicarlo en la consulta. Revisaremos los productos disponibles y confirmaremos únicamente lo que pueda comprobarse en el etiquetado o información del vendedor.</p>
<h2>Pedidos para varias personas o direcciones</h2>
<p>El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente los productos correspondientes a su pedido. Por eso, en solicitudes con muchas unidades o destinos diferentes, primero debemos revisar la composición elegida y confirmar qué logística es viable.</p>
<p>Si la entrega es para una única sede, varias oficinas o domicilios particulares, inclúyelo en el primer mensaje. Esa información puede influir en la propuesta.</p>
<h2>Qué necesitamos para empezar</h2>
<p>Indícanos número aproximado de cestas, presupuesto por unidad, fecha deseada, tipo de destinatario y lugar o lugares de entrega. Si tienes alguna preferencia —por ejemplo, que el AOVE sea protagonista o que no haya ningún producto cárnico— también puedes mencionarla.</p>
<p>No hace falta que diseñes la cesta tú mismo. Nuestro objetivo es partir de esos requisitos y revisar qué productos reales del marketplace pueden encajar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Prepara una cesta de Navidad sin alcohol</h2><p>Envíanos cantidades, presupuesto y fecha. Revisaremos las opciones disponibles y te diremos qué propuesta podemos plantear.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-christmas-ham',
'slug'=>'cestas-navidad-jamon-iberico',
'title'=>'Cestas de Navidad con jamón ibérico',
'excerpt'=>'Cestas de Navidad con jamón ibérico, paleta, loncheados y productos complementarios para empresas, empleados o clientes.',
'seo_title'=>'Cestas de Navidad con jamón ibérico | El Mercado de Origen',
'seo_description'=>'Cestas de Navidad con jamón ibérico para empresas, empleados o clientes. Cuéntanos unidades, presupuesto, formato y fecha.',
'focus'=>'cestas de Navidad con jamón ibérico',
'content'=><<<'HTML'
<p>Para muchas empresas, el jamón sigue siendo el producto que convierte una cesta de Navidad en un regalo con verdadera presencia. Pero hablar de <strong>cestas de Navidad con jamón ibérico</strong> puede significar cosas muy distintas: una pieza completa, una paleta, sobres loncheados o una selección de ibéricos acompañada de otros productos.</p>
<p>En El Mercado de Origen podemos estudiar propuestas a partir de los jamones, paletas e ibéricos disponibles en nuestros productores, ajustando el planteamiento al presupuesto, número de destinatarios, formato y fecha.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres que el jamón sea el protagonista de la cesta?</h2><p>Dinos cuántas unidades necesitas, presupuesto aproximado y si prefieres pieza, paleta o formatos más cómodos. Revisaremos qué opciones están disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Pedir una propuesta</a></p></div>
<h2>Jamón, paleta o loncheado: tres planteamientos diferentes</h2>
<p>Una pieza de jamón ofrece presencia, rendimiento y un regalo que puede disfrutarse durante más tiempo. La paleta ocupa menos espacio y puede permitir trabajar en un rango de presupuesto diferente. El loncheado, por su parte, facilita el consumo y evita que el destinatario necesite soporte, cuchillo o experiencia de corte.</p>
<p>No hay un formato correcto para todas las empresas. La elección depende del presupuesto, del tipo de destinatario y de la importancia que quieras dar al producto dentro del conjunto.</p>
<h2>Una cesta con jamón no necesita estar llena de productos</h2>
<p>Cuando existe una pieza protagonista, conviene que el resto acompañe y no compita. Un AOVE, algunos embutidos, conservas o referencias de despensa pueden completar la propuesta sin convertirla en una acumulación de productos.</p>
<p>En otros casos, la cesta puede basarse en varios formatos de ibéricos en lugar de una pieza entera. Esto facilita el reparto y puede adaptarse mejor a equipos o clientes con poco espacio.</p>
<h2>Cestas con jamón para empleados</h2>
<p>Cuando el regalo se dirige a una plantilla, el presupuesto suele ser homogéneo y el número de unidades tiene mucha importancia. Una diferencia pequeña en el precio de cada pieza puede tener un impacto grande al multiplicarse por decenas o cientos de destinatarios.</p>
<p>Por eso necesitamos saber cuántas cestas hay que preparar y qué límite económico existe antes de recomendar un formato. También conviene aclarar si la empresa pretende entregar todos los regalos en una única ubicación o si existen distintas sedes y domicilios.</p>
<h2>Cestas con jamón para clientes</h2>
<p>Para clientes, el jamón puede reservarse a relaciones comerciales estratégicas o utilizarse como elemento central de un regalo de mayor valor. Algunas empresas trabajan con diferentes niveles de regalo según el tipo de cliente, lo que permite ajustar categoría, formato o acompañamientos.</p>
<p>Si necesitas varios escalones de presupuesto, indícalo. Podemos revisar opciones separadas sin obligar a utilizar el mismo lote para todos los destinatarios.</p>
<h2>Cómo influye la categoría del ibérico</h2>
<p>Dentro del jamón ibérico existen distintas designaciones y porcentajes raciales que afectan al posicionamiento y al precio. Para un pedido corporativo conviene fijarse en la información concreta de cada producto y no basarse únicamente en la palabra “ibérico”.</p>
<p>En El Mercado de Origen cada ficha mantiene visible al productor y la información disponible sobre el producto. Eso permite tomar una decisión más informada y explicar mejor qué se está regalando.</p>
<h2>Qué presupuesto tiene sentido</h2>
<p>No existe una cifra universal. El presupuesto dependerá de si se busca una paleta, una pieza de jamón, un formato loncheado o una combinación. También hay que tener en cuenta que el coste logístico puede variar según el vendedor, destino y composición del pedido.</p>
<p>Si nos indicas un rango por cesta, podremos descartar opciones que no encajen y concentrarnos en alternativas realistas.</p>
<h2>¿Se puede acompañar el jamón con otros productos españoles?</h2>
<p>Sí, siempre que los productos disponibles y la logística lo permitan. AOVE, embutidos, conservas y otras referencias pueden complementar el regalo. Sin embargo, al tratarse de un marketplace, si intervienen distintos vendedores hay que revisar cómo se prepara y expide cada pedido.</p>
<p>No prometemos una única caja o una operativa determinada hasta comprobar la composición concreta. Nuestro objetivo es plantear una solución que pueda ejecutarse de verdad.</p>
<h2>Navidad y disponibilidad</h2>
<p>Durante las semanas previas a Navidad aumenta la demanda de jamones, paletas y loncheados. Si necesitas varias unidades, empezar con antelación permite disponer de más opciones de peso, categoría y formato.</p>
<p>Indica la fecha deseada desde el primer contacto. La entrega no se considera confirmada hasta que se revisen stock y condiciones, pero tener una fecha objetivo ayuda a decidir qué alternativas son viables.</p>
<h2>Qué información debes enviarnos</h2>
<p>Para estudiar una propuesta necesitamos cantidad aproximada, presupuesto por cesta, tipo de destinatario, formato preferido si lo tienes claro, fecha y destinos. Si no sabes si elegir jamón o paleta, puedes dejar esa decisión abierta y te explicaremos qué encaja mejor con el presupuesto disponible.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Cuéntanos cuántas cestas necesitas</h2><p>Revisaremos jamones, paletas, loncheados y posibles complementos para plantear una propuesta ajustada a tu empresa.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'b2b-christmas-spanish-products',
'slug'=>'cestas-navidad-productos-espanoles',
'title'=>'Cestas de Navidad con productos españoles',
'excerpt'=>'Cestas de Navidad con productos españoles de productores identificables: ibéricos, AOVE, conservas y otras referencias para empresas y clientes.',
'seo_title'=>'Cestas de Navidad con productos españoles | El Mercado de Origen',
'seo_description'=>'Cestas de Navidad con productos españoles para empresas, empleados y clientes. Propuestas con productores y procedencia visibles.',
'focus'=>'cestas de Navidad con productos españoles',
'content'=><<<'HTML'
<p>Una cesta de Navidad puede ser mucho más interesante cuando los productos comparten una idea clara. Elegir <strong>productos españoles con productor y procedencia identificables</strong> permite construir un regalo que no dependa únicamente de la presentación: también tiene una historia detrás.</p>
<p>El Mercado de Origen reúne productores especializados en distintas categorías gastronómicas. Si tu empresa busca una cesta navideña basada en productos españoles, podemos revisar las referencias disponibles y estudiar una propuesta según presupuesto, unidades, destinatarios y fecha.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres regalar una selección de productos españoles?</h2><p>Indícanos número de cestas, presupuesto y fecha. Revisaremos el catálogo disponible y estudiaremos una propuesta coherente con tu objetivo.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar información</a></p></div>
<h2>Qué significa realmente “productos españoles” en una cesta</h2>
<p>Para nosotros no basta con utilizar una bandera en el embalaje o una descripción genérica. El valor está en poder identificar quién vende el producto, de dónde procede y qué características tiene. Esa trazabilidad comercial ayuda a que el regalo resulte más auténtico y menos anónimo.</p>
<p>En el marketplace puedes encontrar productores y vendedores de jamón, aceite, carne, huerta, conservas y otras categorías. La selección final depende de las referencias disponibles en el momento de preparar la propuesta.</p>
<h2>Productos que pueden representar bien la gastronomía española</h2>
<p>No existe una única cesta “española”. Puede centrarse en una región, una categoría o una combinación de productos reconocibles. Algunas familias que pueden valorarse son:</p>
<ul>
<li><strong>Jamón, paleta e ibéricos</strong>, vinculados a una de las categorías gastronómicas más reconocibles.</li>
<li><strong>Aceite de oliva virgen extra</strong>, útil tanto como regalo único como dentro de una selección.</li>
<li><strong>Conservas</strong>, que aportan variedad y facilidad de almacenamiento.</li>
<li><strong>Productos de huerta y despensa</strong>, para equilibrar el conjunto y ampliar perfiles.</li>
<li><strong>Packs de productores</strong>, cuando ya existe una combinación preparada que encaja con la ocasión.</li>
</ul>
<p>La combinación concreta debe responder al presupuesto y al destinatario, no a una lista fija de productos “típicos”.</p>
<h2>Cestas españolas para empleados</h2>
<p>En equipos amplios, una selección gastronómica puede funcionar bien porque es consumible y se puede compartir en casa. Si la empresa quiere transmitir vínculo con producto nacional o apoyar a productores, una cesta con procedencias visibles refuerza ese mensaje.</p>
<p>Para poder estudiar el pedido necesitamos saber número de empleados, presupuesto por persona y forma de entrega prevista.</p>
<h2>Cestas españolas para clientes o colaboradores</h2>
<p>Para un cliente, la procedencia puede aportar una capa adicional de valor. El regalo deja de ser simplemente un lote y se convierte en una selección que permite descubrir productores concretos.</p>
<p>Esto puede resultar especialmente interesante para clientes internacionales, visitantes o empresas que quieran ofrecer un detalle ligado a la gastronomía española. Si el destinatario está fuera de España o hay condiciones especiales de envío, deben revisarse antes de confirmar cualquier propuesta.</p>
<h2>Cómo evitar una cesta demasiado genérica</h2>
<p>Una cesta puede contener muchos productos y aun así no tener personalidad. Para evitarlo conviene elegir un hilo conductor: productores españoles, una categoría principal, una región o una combinación pensada para un momento de consumo concreto.</p>
<p>También ayuda limitar el número de referencias. Tres o cuatro productos con una historia clara pueden resultar más memorables que una selección mucho más grande en la que nada destaque.</p>
<h2>Presupuesto y niveles de regalo</h2>
<p>Si existen distintos grupos de destinatarios, se pueden estudiar varios niveles de presupuesto. Por ejemplo, una selección compacta para un grupo amplio y una propuesta con una pieza principal para determinados clientes.</p>
<p>Cuéntanos los rangos y cantidades de cada grupo. Revisaremos qué productos reales pueden sostener esos presupuestos sin forzar combinaciones poco coherentes.</p>
<h2>Logística en un marketplace de productores</h2>
<p>El Mercado de Origen no funciona como un almacén único. Cada vendedor identificado en el producto prepara y envía su parte del pedido. Esto es importante cuando se plantean cestas con referencias de diferentes productores.</p>
<p>Antes de prometer una presentación conjunta o una entrega concreta, necesitamos revisar la composición. Si buscas una cesta unificada, indícalo expresamente y te diremos qué podemos ofrecer con la operativa disponible.</p>
<h2>Cuándo empezar a preparar el pedido navideño</h2>
<p>La disponibilidad de productos cambia a medida que se acerca diciembre. Los pedidos corporativos con muchas unidades, formatos concretos o varios destinos se benefician de una planificación temprana.</p>
<p>Si ya tienes una previsión de cantidades y presupuesto, puedes iniciar la consulta aunque todavía no hayas cerrado la lista de destinatarios.</p>
<h2>Qué datos incluir en tu mensaje</h2>
<p>Número aproximado de cestas, presupuesto por unidad, tipo de destinatario, fecha, destinos y cualquier preferencia de producto. También puedes indicar si buscas una cesta especialmente representativa de España o si hay alguna categoría que quieras evitar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Construyamos una propuesta con origen</h2><p>Envíanos los datos básicos del pedido y revisaremos qué productores y productos disponibles pueden formar parte de la selección.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir a contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-spanish-corporate-gifts',
'slug'=>'regalos-empresa-productos-espanoles',
'title'=>'Regalos de empresa con productos españoles',
'excerpt'=>'Regalos de empresa con productos españoles para clientes, empleados, colaboradores y eventos. Propuestas gastronómicas adaptadas a presupuesto y ocasión.',
'seo_title'=>'Regalos de empresa con productos españoles | El Mercado de Origen',
'seo_description'=>'Regalos corporativos con productos españoles y productores identificables. Cuéntanos ocasión, unidades, presupuesto y fecha.',
'focus'=>'regalos de empresa con productos españoles',
'content'=><<<'HTML'
<p>Cuando una empresa quiere hacer un regalo que tenga relación con España, la gastronomía ofrece una opción muy natural. Un <strong>regalo corporativo con productos españoles</strong> puede servir para clientes, empleados, colaboradores, visitantes internacionales, ponentes o equipos que celebran un hito.</p>
<p>En El Mercado de Origen trabajamos con productores y vendedores especializados en distintas categorías de alimentación. Podemos estudiar una propuesta a partir de los productos realmente disponibles, ajustándola al presupuesto, número de unidades, tipo de destinatario y fecha.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un regalo corporativo con producto español?</h2><p>Cuéntanos la ocasión, cantidades y presupuesto. Revisaremos qué referencias y productores pueden encajar en la propuesta.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
<h2>Un regalo de empresa no tiene por qué ser merchandising</h2>
<p>Bolígrafos, libretas, botellas reutilizables o gadgets pueden ser útiles, pero muchas empresas buscan alternativas consumibles y con más carga emocional. La gastronomía tiene esa ventaja: se disfruta, se comparte y permite hablar del origen del producto.</p>
<p>Cuando además el productor está identificado, el regalo deja de ser un artículo genérico y se conecta con una historia concreta.</p>
<h2>Qué productos españoles pueden funcionar bien</h2>
<p>La selección depende del destinatario y del presupuesto. Algunas categorías que suelen resultar fáciles de entender y regalar son el aceite de oliva virgen extra, los ibéricos, el jamón y la paleta, las conservas y determinados productos de huerta o despensa.</p>
<p>En ocasiones funciona mejor un único producto protagonista. En otras, tiene sentido una pequeña selección. La propuesta no debe medirse por cantidad de referencias, sino por coherencia y valor percibido.</p>
<h2>Regalos para clientes nacionales</h2>
<p>Aunque el cliente viva en España, la procedencia sigue importando. Un AOVE de un productor concreto, una pieza ibérica o una conserva seleccionada pueden tener más personalidad que un lote anónimo.</p>
<p>Si tienes distintos niveles de cliente, se pueden estudiar varios presupuestos. Lo importante es mantener una lógica clara y que cada grupo reciba una propuesta proporcionada a la relación comercial.</p>
<h2>Regalos para clientes o visitantes internacionales</h2>
<p>Los productos españoles pueden funcionar especialmente bien como detalle para personas que visitan la empresa desde otros países o para colaboradores internacionales. Sin embargo, antes de plantear envíos fuera de España hay que revisar destinos, restricciones y condiciones del vendedor.</p>
<p>Si el regalo debe viajar, indícalo en la consulta. No todos los productos ni todos los destinos tienen la misma operativa.</p>
<h2>Regalos para empleados y equipos</h2>
<p>Una selección de productos españoles también puede utilizarse como regalo interno: Navidad, reconocimiento, celebración de objetivos, aniversarios o reuniones de equipo.</p>
<p>En este caso el número de unidades y el presupuesto por persona suelen ser los factores principales. Si el equipo trabaja en remoto o está repartido entre varias sedes, necesitamos saberlo desde el principio.</p>
<h2>Regalos vinculados a la identidad de la empresa</h2>
<p>Una compañía no necesita imprimir su logotipo en el regalo para transmitir valores. Si la empresa quiere reforzar cercanía con el territorio, producto nacional, gastronomía o apoyo al pequeño productor, la selección en sí puede comunicar ese mensaje.</p>
<p>La clave es poder explicar por qué se han elegido esos productos. En El Mercado de Origen el productor y la procedencia forman parte de la experiencia de compra.</p>
<h2>Presupuesto: cómo hacerlo manejable</h2>
<p>Antes de seleccionar referencias conviene fijar un rango por destinatario. Eso permite evitar propuestas poco realistas y decidir si interesa un producto único, un pack o una combinación.</p>
<p>Si la empresa maneja varios grupos, indícanos cantidad y presupuesto de cada uno. Podemos estudiar alternativas independientes y comprobar disponibilidad real.</p>
<h2>Presentación, mensajes y personalización</h2>
<p>Si necesitas una tarjeta, mensaje, presentación especial u otro elemento, debes indicarlo en la solicitud. La posibilidad de personalizar depende de cantidades, plazos y del vendedor concreto, por lo que se confirma caso a caso.</p>
<p>Lo mismo sucede con agrupaciones de productos de diferentes vendedores. El Mercado de Origen es un marketplace: cada vendedor prepara y envía directamente su pedido. Antes de confirmar una caja conjunta o una determinada logística, tenemos que revisar la composición.</p>
<h2>Qué información necesitamos</h2>
<p>Ocasión, número de regalos, presupuesto por unidad, fecha objetivo, destinos y perfil del destinatario. Si quieres que el regalo represente una región, categoría o tipo de productor, también puedes explicarlo.</p>
<p>Con esos datos revisaremos la oferta disponible y podremos responder con alternativas que realmente puedan ejecutarse.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Dinos qué quieres transmitir con el regalo</h2><p>Nosotros revisaremos los productos disponibles y estudiaremos una propuesta ajustada a tu presupuesto y destinatarios.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
HTML
),
array(
'key'=>'b2b-event-gastronomic-gifts',
'slug'=>'regalos-gastronomicos-eventos-congresos',
'title'=>'Regalos gastronómicos para eventos, congresos y reuniones de empresa',
'excerpt'=>'Regalos gastronómicos para eventos, congresos, ponentes, invitados y reuniones de empresa con productos de productores españoles.',
'seo_title'=>'Regalos gastronómicos para eventos y congresos | El Mercado de Origen',
'seo_description'=>'Detalles gastronómicos para eventos, congresos, ponentes e invitados. Estudiamos propuestas según unidades, presupuesto, fecha y lugar.',
'focus'=>'regalos gastronómicos para eventos',
'content'=><<<'HTML'
<p>Un evento corporativo no termina cuando acaba la última ponencia. El detalle que recibe un invitado, un ponente o un colaborador puede convertirse en parte del recuerdo de la jornada. Por eso muchas empresas buscan <strong>regalos gastronómicos para eventos, congresos y reuniones</strong> que sean útiles, fáciles de entender y distintos del merchandising habitual.</p>
<p>En El Mercado de Origen podemos estudiar propuestas basadas en productos de productores españoles. Para hacerlo necesitamos conocer número de unidades, presupuesto, tipo de asistente, fecha, lugar de entrega y cualquier condición especial del evento.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Estás organizando un evento o congreso?</h2><p>Cuéntanos cuántos detalles necesitas, presupuesto por unidad, fecha y lugar. Revisaremos las opciones disponibles y la viabilidad logística.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Regalos para asistentes, ponentes o invitados especiales</h2>
<p>No todos los participantes de un evento tienen por qué recibir el mismo detalle. Puede existir una propuesta sencilla para asistentes, otra para ponentes y una tercera para invitados o clientes estratégicos.</p>
<p>Separar los grupos permite ajustar el presupuesto y el nivel del regalo. También evita gastar de más en algunos casos o quedarse corto en otros.</p>
<h2>Qué características debería tener un regalo de evento</h2>
<p>En un congreso o jornada profesional, la logística importa tanto como el producto. Un regalo demasiado voluminoso puede ser incómodo para quien debe viajar. Uno muy delicado puede complicar el transporte. Si se entrega al final del evento, el formato debe ser manejable.</p>
<p>Por eso conviene pensar en tamaño, peso, conservación y modo de entrega además del valor gastronómico.</p>
<h2>Productos gastronómicos que pueden encajar</h2>
<p>Dependiendo del contexto, se pueden valorar pequeños formatos de AOVE, productos ibéricos, conservas, packs de productor u otras referencias disponibles. En regalos de mayor nivel puede tener sentido una selección más amplia o una pieza protagonista.</p>
<p>Si muchos asistentes van a viajar en avión o vienen de otros países, indícalo. Algunas opciones pueden resultar más prácticas que otras y las restricciones de transporte deben revisarse por separado.</p>
<h2>Detalles para ponentes y colaboradores</h2>
<p>Un ponente que ha dedicado tiempo a preparar una intervención suele merecer un reconocimiento diferente del obsequio general del evento. Aquí puede tener sentido trabajar con un presupuesto superior o una propuesta más personal.</p>
<p>Lo mismo ocurre con patrocinadores, colaboradores o personas que han ayudado a organizar la jornada. Si existen diferentes grupos, podemos estudiar alternativas por separado.</p>
<h2>Regalos para congresos con asistentes internacionales</h2>
<p>Cuando hay visitantes extranjeros, un producto gastronómico español puede funcionar como recuerdo del viaje y como forma de mostrar una parte del territorio. AOVE, ibéricos o conservas pueden ser reconocibles, pero el formato debe adaptarse a la movilidad del destinatario.</p>
<p>Si el producto se va a transportar fuera de España, conviene advertirlo desde el principio. Las condiciones de entrada de alimentos dependen del destino y no deben darse por supuestas.</p>
<h2>Eventos internos de empresa</h2>
<p>No todos los eventos son congresos externos. Kick-offs, reuniones anuales, encuentros comerciales, jornadas de formación o celebraciones internas también pueden incorporar un detalle gastronómico para el equipo.</p>
<p>En estos casos el regalo puede vincularse a un mensaje de agradecimiento, cierre de ejercicio o celebración de objetivos. El presupuesto y formato pueden ser distintos de los de un evento dirigido a clientes.</p>
<h2>Entrega en sede, hotel o recinto</h2>
<p>Para eventos, el lugar de entrega es un dato crítico. Necesitamos saber si los productos deben llegar a una oficina, hotel, recinto ferial u otra ubicación y con qué margen antes del evento.</p>
<p>El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide su pedido. Si intervienen varios productores o se necesitan muchas unidades, revisaremos la operativa antes de confirmar que una entrega concreta es posible.</p>
<h2>Personalización y materiales del evento</h2>
<p>Si quieres incluir una tarjeta, mensaje, acreditación, elemento de marca o presentación específica, explícalo en el primer contacto. No todas las personalizaciones son posibles para todos los productos ni con cualquier plazo, por lo que deben validarse caso a caso.</p>
<p>También puedes indicarnos si el regalo debe entregarse preparado individualmente o si buscas producto para montar los detalles por tu cuenta.</p>
<h2>Cómo calcular el presupuesto</h2>
<p>Multiplica el presupuesto unitario por el número realista de destinatarios y reserva margen para posibles costes de transporte o preparación. Si existen varios grupos, crea un presupuesto por grupo.</p>
<p>Con esa información podemos revisar qué tipo de producto encaja mejor y evitar propuestas imposibles de escalar al volumen del evento.</p>
<h2>Qué necesitamos para estudiar tu evento</h2>
<p>Fecha, ciudad o lugar de entrega, número de unidades, presupuesto por unidad, tipo de asistentes y cualquier requisito logístico o alimentario. Si el evento tiene varios días o diferentes momentos de entrega, también debe indicarse.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Prepara el detalle gastronómico de tu evento</h2><p>Envíanos los datos básicos y revisaremos qué opciones del marketplace pueden funcionar por formato, presupuesto y logística.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
)
);

$admins = get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author = $admins ? (int)$admins[0] : 1;
$rows = array();

foreach ($pages as $p) {
    $words = emdo_b2b02_words($p['content']);
    if ($words < 650) throw new Exception($p['key'].' too short: '.$words);

    $existing = emdo_b2b02_existing($p['key'], $p['slug']);
    $post = array(
        'post_type'=>'page',
        'post_status'=>'publish',
        'post_author'=>$author,
        'post_title'=>wp_strip_all_tags($p['title']),
        'post_name'=>sanitize_title($p['slug']),
        'post_excerpt'=>wp_strip_all_tags($p['excerpt']),
        'post_content'=>$p['content'],
        'comment_status'=>'closed',
        'ping_status'=>'closed',
        'post_parent'=>0,
        'menu_order'=>0,
    );
    if ($existing) $post['ID']=$existing;
    $res = $existing ? wp_update_post($post,true) : wp_insert_post($post,true);
    if (is_wp_error($res)) throw new Exception($p['key'].': '.$res->get_error_message());

    $id=(int)$res;
    update_post_meta($id,'_emdo_seo_landing_key',$p['key']);
    update_post_meta($id,'_emdo_seo_landing_batch','20260926-b2b-02');
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

    $rows[]=array(
        'key'=>$p['key'],
        'id'=>$id,
        'title'=>get_the_title($id),
        'slug'=>get_post_field('post_name',$id),
        'type'=>get_post_type($id),
        'status'=>get_post_status($id),
        'words'=>$words,
        'url'=>get_permalink($id),
    );
}

if (count($rows)!==5) throw new Exception('Expected 5 landing pages');
echo wp_json_encode(array('batch'=>'20260926-b2b-02','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
