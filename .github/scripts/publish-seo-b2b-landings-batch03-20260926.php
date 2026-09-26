<?php
if (!defined('ABSPATH')) { exit; }

function emdo_b2b03_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_b2b03_existing($key, $slug) {
    $ids = get_posts(array(
        'post_type'=>'page',
        'post_status'=>'any',
        'posts_per_page'=>1,
        'fields'=>'ids',
        'meta_key'=>'_emdo_seo_landing_key',
        'meta_value'=>$key,
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
'key'=>'b2b-what-to-put-christmas-basket-employees',
'slug'=>'que-incluir-cesta-navidad-empleados',
'title'=>'Qué incluir en una cesta de Navidad para empleados',
'excerpt'=>'Qué incluir en una cesta de Navidad para empleados según presupuesto, tipo de equipo y formato. Ideas con productos gastronómicos y criterios para elegir mejor.',
'seo_title'=>'Qué incluir en una cesta de Navidad para empleados',
'seo_description'=>'Ideas para decidir qué incluir en una cesta de Navidad para empleados según presupuesto, perfil del equipo, logística y tipo de producto.',
'focus'=>'qué incluir en una cesta de Navidad para empleados',
'content'=><<<'HTML'
<p>Elegir <strong>qué incluir en una cesta de Navidad para empleados</strong> parece sencillo hasta que hay que hacerlo para todo un equipo. En ese momento aparecen las dudas: ¿mejor muchos productos pequeños o pocos pero de más calidad? ¿Conviene incluir alcohol? ¿Jamón o paleta? ¿Es preferible una cesta igual para todos o varias opciones?</p>
<p>La respuesta depende del presupuesto, del tamaño de la plantilla, de cómo se va a entregar el regalo y de lo homogéneo o diverso que sea el equipo. Lo importante es construir una selección coherente, no llenar una caja por llenar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres que te ayudemos a definir la cesta?</h2><p>Cuéntanos cuántos empleados tienes, presupuesto por persona y fecha. Revisaremos qué productos disponibles pueden encajar en una propuesta realista.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar una propuesta</a></p></div>
<h2>Empieza por el presupuesto y el número de personas</h2>
<p>Antes de elegir productos conviene fijar dos cifras: cuántos empleados recibirán el regalo y cuánto puede gastar la empresa por persona. Si una cesta cuesta 10 euros más de lo previsto, la diferencia puede parecer pequeña hasta que se multiplica por 50, 100 o 300 empleados.</p>
<p>Definir el presupuesto al principio evita enamorarse de una propuesta que luego no puede escalarse. También permite decidir si interesa concentrar el valor en un producto principal o repartirlo entre varias referencias.</p>
<h2>Un producto protagonista suele dar más coherencia</h2>
<p>Muchas cestas funcionan mejor cuando hay una referencia principal y el resto acompaña. Ese protagonismo puede recaer en una pieza de jamón o paleta, una selección de ibéricos, un buen aceite de oliva virgen extra o un pack de productor.</p>
<p>No hace falta que el producto protagonista sea necesariamente el más caro, pero sí debería ser fácil de reconocer y aportar sentido al conjunto.</p>
<h2>Ibéricos, jamón y paleta</h2>
<p>Son opciones muy asociadas a la Navidad y pueden funcionar especialmente bien cuando se busca un regalo pensado para compartir. La elección entre pieza entera, paleta o loncheado depende del presupuesto y del perfil del equipo.</p>
<p>El loncheado resulta más cómodo para personas que no quieren cortar una pieza. El jamón o la paleta aportan más presencia, pero también requieren más espacio y manejo.</p>
<h2>Aceite de oliva virgen extra</h2>
<p>El AOVE es un producto versátil porque no depende de un momento concreto de consumo y puede integrarse tanto en una cesta sencilla como en una propuesta de mayor valor.</p>
<p>También tiene la ventaja de que su origen, variedad o productor pueden explicarse fácilmente, algo que ayuda a dar personalidad al regalo.</p>
<h2>Conservas y productos de despensa</h2>
<p>Las conservas permiten añadir variedad y son fáciles de almacenar. Pueden complementar bien una selección de ibéricos o aceite y ayudan a construir una cesta que no dependa únicamente de productos cárnicos.</p>
<p>Lo importante es no añadir referencias solo por aumentar el número de unidades. Una conserva bien elegida aporta más que tres productos sin relación con el resto.</p>
<h2>¿Conviene incluir alcohol?</h2>
<p>No necesariamente. Algunas empresas prefieren evitar vino, cava u otras bebidas para que la cesta sea más transversal. Esto puede ser útil cuando no se conocen los hábitos de todos los empleados o cuando existe una política interna que desaconseja ese tipo de regalo.</p>
<p>Una cesta sin alcohol puede seguir teniendo mucho valor si el presupuesto se concentra en alimentos.</p>
<h2>Qué hacer con alergias y restricciones</h2>
<p>Una plantilla puede incluir personas con alergias, intolerancias, dietas específicas o restricciones religiosas. Si la empresa necesita contemplar estas situaciones, conviene identificarlas con antelación y no asumir que una cesta estándar sirve para todos.</p>
<p>Cualquier afirmación sobre alérgenos debe basarse en la información concreta de cada producto. Si existen requisitos especiales, deben comunicarse antes de cerrar la selección.</p>
<h2>¿Una sola cesta o varias alternativas?</h2>
<p>Ofrecer demasiadas opciones puede complicar la gestión, pero en algunos equipos tiene sentido preparar dos alternativas: por ejemplo, una general y otra sin alcohol o sin determinados productos.</p>
<p>La decisión depende del tamaño de la plantilla y de la capacidad de la empresa para gestionar elecciones. Si el equipo es pequeño, puede haber más margen para personalizar; si es muy grande, suele ser más práctico trabajar con pocas variantes.</p>
<h2>Ten en cuenta cómo se va a entregar</h2>
<p>No es lo mismo entregar todas las cestas en una oficina que enviarlas a domicilios particulares. El tamaño, peso y número de productores implicados pueden afectar a la logística.</p>
<p>El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide su pedido. Si necesitas muchas unidades, varias sedes o distintos domicilios, hay que revisar la composición concreta antes de confirmar la operativa.</p>
<h2>Una cesta buena no se mide por el número de productos</h2>
<p>El error más común es pensar que una cesta con doce referencias es mejor que una con cinco. No siempre es así. La calidad percibida depende de la coherencia, del valor real de los productos y de que el conjunto tenga sentido para quien lo recibe.</p>
<p>En muchos casos, menos productos pero mejor escogidos generan una impresión más cuidada.</p>
<h2>Qué información nos ayuda a preparar una propuesta</h2>
<p>Si quieres que estudiemos una cesta para tu empresa, indícanos número de empleados, presupuesto por persona, fecha deseada, lugar o lugares de entrega y cualquier preferencia importante. Con esos datos revisaremos qué productos disponibles pueden encajar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Define una cesta que tenga sentido para tu equipo</h2><p>No necesitas elegir cada producto antes de contactar. Dinos cantidades y presupuesto y estudiaremos las opciones.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-budget-christmas-basket-employees',
'slug'=>'cuanto-gastar-cesta-navidad-empleados',
'title'=>'Cuánto gastar en la cesta de Navidad de los empleados',
'excerpt'=>'Guía para definir cuánto gastar en la cesta de Navidad de los empleados según tamaño de plantilla, presupuesto total y tipo de regalo.',
'seo_title'=>'Cuánto gastar en la cesta de Navidad de los empleados',
'seo_description'=>'Cómo calcular cuánto gastar por empleado en una cesta de Navidad según plantilla, presupuesto total, logística y tipo de regalo.',
'focus'=>'cuánto gastar en la cesta de Navidad de los empleados',
'content'=><<<'HTML'
<p>Una de las primeras decisiones al preparar un regalo navideño para una plantilla es <strong>cuánto gastar por empleado</strong>. No existe una cifra universal. El importe adecuado depende del tamaño de la empresa, del presupuesto total disponible, de la política interna, del tipo de relación con el equipo y de si la cesta es un detalle simbólico o un beneficio navideño con más peso.</p>
<p>Lo importante es evitar dos errores: elegir productos antes de saber cuánto puede gastar la empresa y fijar una cifra por persona sin multiplicarla por el número real de destinatarios.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Ya tienes un presupuesto por empleado?</h2><p>Envíanos el número de personas, el rango de gasto y la fecha. Revisaremos qué propuestas gastronómicas pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Calcula primero el presupuesto total</h2>
<p>Si la empresa tiene 20 empleados, una diferencia de 15 euros por cesta supone 300 euros. Si tiene 200 empleados, la misma diferencia se convierte en 3.000 euros. Por eso conviene trabajar primero con el presupuesto global y después dividirlo entre destinatarios.</p>
<p>También es recomendable reservar margen para transporte, posibles variaciones de stock y cualquier coste de preparación o personalización que pueda existir.</p>
<h2>Presupuestos contenidos: prioriza uno o dos productos buenos</h2>
<p>Cuando el presupuesto por persona es ajustado, intentar llenar una cesta con demasiadas referencias suele empeorar el resultado. Puede tener más sentido elegir un buen AOVE, una selección sencilla de embutidos o un pack compacto con productos reconocibles.</p>
<p>La percepción de calidad no depende únicamente del volumen de la caja. Un producto con buena procedencia y presentación puede funcionar mejor que muchas referencias pequeñas.</p>
<h2>Presupuesto medio: busca equilibrio</h2>
<p>Con un rango intermedio puede plantearse una combinación de varios productos: por ejemplo, aceite, embutidos y conservas. También puede valorarse una paleta o ciertos formatos de ibéricos, dependiendo de disponibilidad y precio.</p>
<p>En este nivel suele ser especialmente importante evitar que una sola referencia consuma casi todo el presupuesto si después el conjunto queda desequilibrado.</p>
<h2>Presupuesto alto: el producto protagonista cobra importancia</h2>
<p>En presupuestos superiores puede tener sentido incorporar una pieza de jamón o paleta, una selección premium o un lote con más personalidad. Aquí la empresa puede decidir si quiere aumentar cantidad, categoría o singularidad.</p>
<p>No siempre es necesario añadir más productos. A veces es preferible mejorar el nivel de la referencia principal.</p>
<h2>¿Debe ser la misma cifra para todos?</h2>
<p>En empleados, lo habitual es trabajar con un presupuesto homogéneo para evitar diferencias difíciles de justificar. Sin embargo, algunas empresas tienen políticas específicas para antigüedad, reconocimiento o equipos con funciones distintas.</p>
<p>Si existen diferentes grupos, conviene definirlos de forma clara y preparar propuestas separadas. Eso permite mantener coherencia dentro de cada segmento.</p>
<h2>Incluye la logística en el cálculo</h2>
<p>El coste del producto no siempre es el coste final. Si la empresa necesita enviar las cestas a múltiples domicilios, trabajar con varias sedes o coordinar entregas en fechas concretas, la logística puede influir en el presupuesto real.</p>
<p>En El Mercado de Origen cada vendedor prepara y expide directamente su pedido. Cuando intervienen varios vendedores o destinos, hay que revisar la operativa antes de cerrar cifras definitivas.</p>
<h2>¿Cuánto es demasiado poco?</h2>
<p>No hay una frontera exacta. Lo importante es que el regalo no obligue a elegir productos de escaso valor únicamente para cumplir con una cantidad de referencias. Si el presupuesto es reducido, es mejor plantear un detalle pequeño pero coherente que una cesta que parezca más grande de lo que realmente aporta.</p>
<p>La transparencia interna también importa. Un regalo modesto puede funcionar bien si está alineado con la situación y cultura de la empresa.</p>
<h2>¿Cuánto es demasiado?</h2>
<p>En sentido contrario, un regalo excesivamente caro puede resultar innecesario o incoherente con otras políticas internas. El presupuesto debería responder a un criterio de empresa, no a una competición por hacer la cesta más llamativa.</p>
<p>Además, en regalos de mayor valor conviene revisar implicaciones fiscales, contables o de compliance que correspondan a la empresa.</p>
<h2>Trabaja con rangos, no con una cifra rígida</h2>
<p>Al pedir una propuesta puedes indicar un rango, por ejemplo “alrededor de 40 euros”, “entre 60 y 80 euros” o “máximo 100 euros por persona”. Eso da margen para comparar productos disponibles sin perder de vista el límite económico.</p>
<p>También permite adaptar la composición si una referencia concreta no está disponible en el volumen necesario.</p>
<h2>Qué datos necesitamos para ayudarte</h2>
<p>Número de empleados, presupuesto por persona o presupuesto total, fecha, forma de entrega y cualquier preferencia importante. Con esos datos podemos revisar productos reales y plantear una propuesta que no dependa de precios teóricos.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Convierte el presupuesto en una propuesta concreta</h2><p>Dinos cuántas personas son y cuánto quieres gastar por empleado. Revisaremos las alternativas disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Hablar con nosotros</a></p></div>
HTML
),
array(
'key'=>'b2b-when-order-christmas-baskets',
'slug'=>'cuando-encargar-cestas-navidad-empresa',
'title'=>'Cuándo encargar las cestas de Navidad de empresa',
'excerpt'=>'Cuándo conviene encargar las cestas de Navidad de empresa según número de unidades, personalización, logística y fecha de entrega.',
'seo_title'=>'Cuándo encargar las cestas de Navidad de empresa',
'seo_description'=>'Cuándo pedir las cestas de Navidad de empresa para evitar problemas de stock, personalización y entregas de última hora.',
'focus'=>'cuándo encargar las cestas de Navidad de empresa',
'content'=><<<'HTML'
<p>Las cestas de Navidad suelen parecer una tarea de diciembre, pero para muchas empresas diciembre ya es tarde. Si hay que preparar decenas de unidades, coordinar varias sedes, enviar a domicilios particulares o trabajar con productos concretos, <strong>conviene empezar bastante antes</strong>.</p>
<p>No existe una fecha universal para encargar las cestas de Navidad de empresa, pero sí una regla sencilla: cuanto mayor sea el volumen y más específica sea la propuesta, más antelación hace falta.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Ya sabes cuántas cestas necesitarás?</h2><p>Aunque todavía no tengas la composición cerrada, puedes enviarnos cantidades, presupuesto y fecha objetivo para empezar a revisar opciones.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar información</a></p></div>
<h2>Septiembre y octubre: buen momento para planificar</h2>
<p>Para empresas que necesitan muchas unidades o quieren valorar distintas propuestas, septiembre y octubre son meses cómodos para empezar. Todavía hay margen para comparar productos, definir presupuesto y resolver dudas logísticas.</p>
<p>También permite detectar pronto si una referencia concreta no puede cubrir el volumen necesario y buscar alternativas sin presión.</p>
<h2>Noviembre: todavía es viable, pero hay menos margen</h2>
<p>En noviembre la campaña navideña ya está en marcha. Muchas empresas cierran sus pedidos durante este mes y algunos productos empiezan a concentrar demanda.</p>
<p>Si el pedido es sencillo y las unidades son moderadas, puede seguir siendo una fecha razonable. Si existen varias sedes, personalización o productos muy concretos, la flexibilidad disminuye.</p>
<h2>Diciembre: depende mucho del caso</h2>
<p>En diciembre todavía pueden existir opciones, pero no conviene contar con la misma disponibilidad ni con plazos amplios. Cuanto más cerca esté la fecha de entrega, más importante es simplificar la propuesta.</p>
<p>En esta fase es mejor trabajar con productos realmente disponibles que insistir en una composición ideal que quizá no pueda prepararse a tiempo.</p>
<h2>El volumen cambia los plazos</h2>
<p>No es lo mismo pedir 10 cestas que 200. A medida que aumenta el número de unidades, hay que comprobar stock, capacidad de preparación y logística. Una referencia que está disponible para un pedido pequeño puede no estarlo en la cantidad necesaria para una empresa grande.</p>
<p>Por eso, si conoces el volumen aproximado, indícalo aunque aún no tengas cerrada la lista de destinatarios.</p>
<h2>La personalización también requiere tiempo</h2>
<p>Si quieres incluir tarjetas, mensajes, presentaciones especiales u otros elementos personalizados, hay que revisar si son posibles y cuánto plazo necesitan. No todas las personalizaciones están disponibles para todos los vendedores o productos.</p>
<p>Cuanto antes se plantee la necesidad, más fácil es confirmar alternativas.</p>
<h2>Varias sedes y domicilios particulares</h2>
<p>La logística es uno de los motivos principales para anticiparse. Una entrega centralizada es mucho más sencilla que enviar a múltiples direcciones.</p>
<p>El Mercado de Origen es un marketplace: cada vendedor prepara y expide directamente su pedido. Si la propuesta incluye varios productos o vendedores y además hay muchos destinos, necesitamos estudiar la operativa antes de confirmar fechas.</p>
<h2>¿Qué pasa si aún no sabes el presupuesto?</h2>
<p>No hace falta tener todos los datos cerrados para empezar. Puedes contactar con una estimación de unidades y un rango de presupuesto. Eso ya permite filtrar opciones y entender qué tipo de cesta es realista.</p>
<p>Después se puede ajustar la selección cuando la empresa cierre la cifra definitiva.</p>
<h2>La fecha de entrega debería definirse desde el principio</h2>
<p>Muchas empresas piensan en “tener las cestas en Navidad”, pero conviene fijar un día o una semana concreta. No es lo mismo entregar el 5 de diciembre que el 20.</p>
<p>Si hay un evento interno, una comida de empresa o un día específico de reparto, esa información debe formar parte de la consulta inicial.</p>
<h2>Planificar antes no obliga a cerrar el pedido antes</h2>
<p>Contactar con antelación no significa confirmar inmediatamente. Significa ganar información: disponibilidad, rangos de precio, posibles formatos y condicionantes logísticos.</p>
<p>Esa información ayuda a la empresa a decidir con más criterio y reduce el riesgo de llegar a diciembre sin opciones adecuadas.</p>
<h2>Qué necesitamos para empezar a revisar tu caso</h2>
<p>Número aproximado de cestas, presupuesto orientativo, fecha de entrega, tipo de destinatario, destino o destinos y cualquier requisito especial. Con esos datos podremos decirte qué opciones tiene sentido estudiar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Empieza a planificar antes de que llegue diciembre</h2><p>Envíanos cantidades y fecha objetivo. Revisaremos el catálogo y te diremos qué alternativas pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'b2b-christmas-baskets-multiple-offices',
'slug'=>'cestas-navidad-empresa-varias-sedes',
'title'=>'Cómo organizar cestas de Navidad para una empresa con varias sedes',
'excerpt'=>'Cómo organizar cestas de Navidad para empresas con varias sedes: planificación, presupuestos, entregas, destinatarios y logística.',
'seo_title'=>'Cestas de Navidad para empresas con varias sedes',
'seo_description'=>'Cómo organizar cestas de Navidad cuando la empresa tiene varias sedes: presupuesto, entregas, destinatarios y coordinación logística.',
'focus'=>'cestas de Navidad empresa varias sedes',
'content'=><<<'HTML'
<p>Organizar cestas de Navidad para una empresa con varias sedes exige algo más que elegir productos. Hay que decidir si todas las oficinas recibirán la misma propuesta, cómo se repartirán las unidades, quién coordina la recepción y qué ocurre si una sede necesita una fecha diferente.</p>
<p>Cuanto antes se defina esta estructura, más fácil es evitar errores. La parte logística puede condicionar tanto como el presupuesto o el contenido de la cesta.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Tienes empleados repartidos entre varias oficinas?</h2><p>Envíanos número de unidades por sede, presupuesto y fechas. Revisaremos qué operativa puede plantearse con los productos disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar el caso</a></p></div>
<h2>Haz un mapa de sedes antes de elegir la cesta</h2>
<p>El primer paso es saber cuántas ubicaciones existen y cuántas personas recibirán el regalo en cada una. Parece obvio, pero muchas empresas empiezan por elegir la cesta y descubren después que la distribución complica el pedido.</p>
<p>Una tabla sencilla con sede, número de empleados, dirección, persona de contacto y fecha deseada ayuda a detectar problemas antes de tiempo.</p>
<h2>Decide si todas las sedes recibirán lo mismo</h2>
<p>La homogeneidad simplifica la gestión, pero no siempre es obligatoria. Algunas empresas prefieren mantener la misma cesta en todas las oficinas; otras adaptan el contenido a tamaños de equipo o presupuestos regionales.</p>
<p>Si se quieren varias propuestas, conviene limitar el número de variantes. Cuantas más combinaciones existan, más compleja será la preparación y el control.</p>
<h2>Define un responsable por sede</h2>
<p>Cuando la entrega es centralizada en cada oficina, resulta útil contar con una persona responsable de recibir, revisar y distribuir las unidades. Esto reduce incidencias y evita entregas fallidas.</p>
<p>La persona de contacto debe saber cuántas cajas espera, en qué fechas y dónde pueden almacenarse temporalmente.</p>
<h2>Fechas diferentes por oficina</h2>
<p>Puede ocurrir que una sede celebre su comida de Navidad antes que otra o que tenga días de cierre diferentes. Si las fechas no son iguales, deben comunicarse desde el principio.</p>
<p>La logística se planifica mejor cuando cada sede tiene una ventana concreta de entrega en lugar de una indicación genérica como “antes de Navidad”.</p>
<h2>El peso y el volumen importan</h2>
<p>Una pieza de jamón o una cesta grande puede resultar atractiva, pero multiplica el volumen cuando hay muchas unidades. Esto afecta al transporte, recepción y almacenamiento.</p>
<p>En sedes pequeñas o con poco espacio, a veces resulta más práctico optar por formatos compactos o por entregas más cercanas a la fecha de reparto interno.</p>
<h2>¿Una sola compra o varias por sede?</h2>
<p>La respuesta depende de la composición y de los vendedores implicados. El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente su pedido.</p>
<p>Por eso no damos por hecho que una combinación de productos de distintos productores pueda viajar como una única caja. Antes de confirmar la operativa hay que revisar la propuesta concreta.</p>
<h2>Cómo gestionar cambios de última hora</h2>
<p>En plantillas grandes es normal que haya altas, bajas, cambios de sede o empleados que finalmente trabajen en remoto. Mantener un pequeño margen de unidades o definir una fecha límite interna para cambios puede simplificar mucho la gestión.</p>
<p>Cuanto más tarde se produzcan las modificaciones, menos opciones hay para adaptar stock o destinos.</p>
<h2>Presupuesto por sede y presupuesto global</h2>
<p>Aunque la cesta sea igual para todos, conviene controlar el presupuesto tanto por persona como por sede. Esto facilita imputaciones internas y permite detectar rápidamente si una oficina ha cambiado de tamaño.</p>
<p>Si existe un presupuesto total cerrado, repartirlo por sede antes de pedir propuestas ayuda a mantener el control.</p>
<h2>Centralizar información evita errores</h2>
<p>Una sola persona o equipo debería consolidar las cantidades y direcciones antes de enviarlas. Cuando varias sedes hacen cambios por separado, aumenta el riesgo de duplicados o inconsistencias.</p>
<p>Un documento único con la versión final de cantidades y destinos es mucho más fácil de gestionar.</p>
<h2>Qué necesitamos para estudiar un pedido multisede</h2>
<p>Número de sedes, unidades por sede, direcciones, fechas, presupuesto por persona y cualquier diferencia entre oficinas. Si algunas unidades deben ir a domicilios particulares, también hay que separarlas del resto.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Organiza el pedido por sedes desde el principio</h2><p>Envíanos la distribución aproximada y revisaremos qué solución puede plantearse con el catálogo y la logística disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir a contacto</a></p></div>
HTML
),
array(
'key'=>'b2b-christmas-baskets-remote-teams',
'slug'=>'cestas-navidad-equipos-remoto',
'title'=>'Cestas de Navidad para equipos que trabajan en remoto',
'excerpt'=>'Cómo organizar cestas de Navidad para empleados en remoto: presupuesto, domicilios, formatos, logística y alternativas para equipos distribuidos.',
'seo_title'=>'Cestas de Navidad para equipos en remoto',
'seo_description'=>'Ideas y planificación para enviar cestas de Navidad a empleados que trabajan en remoto o desde distintas ciudades.',
'focus'=>'cestas de Navidad equipos remoto',
'content'=><<<'HTML'
<p>Los equipos distribuidos han cambiado la forma de organizar muchos regalos de empresa. Cuando los empleados trabajan desde casa o desde distintas ciudades, ya no basta con recibir todas las cestas en una oficina y repartirlas. Hay que gestionar direcciones, fechas, privacidad y una logística mucho más fragmentada.</p>
<p>Eso no significa que haya que renunciar al regalo. Significa que la cesta debe pensarse desde el principio para un <strong>equipo en remoto</strong>.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Tu equipo está repartido por distintas ciudades?</h2><p>Cuéntanos cuántas personas son, presupuesto, fecha y tipo de destinos. Revisaremos la viabilidad del pedido y las opciones disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Recoge las direcciones con tiempo</h2>
<p>La primera dificultad no es el producto, sino tener una dirección válida para cada empleado. Conviene solicitarla con antelación, confirmar código postal y establecer una fecha límite para cambios.</p>
<p>La empresa debe gestionar estos datos de acuerdo con sus obligaciones de privacidad y compartir únicamente la información necesaria para la entrega.</p>
<h2>Elige formatos fáciles de recibir en casa</h2>
<p>Cuando una cesta se envía a domicilios particulares, el tamaño y el peso ganan importancia. Un formato compacto puede resultar más cómodo para el destinatario y más sencillo de gestionar.</p>
<p>Eso no obliga a reducir el valor del regalo. Puede concentrarse el presupuesto en productos de calidad que ocupen menos espacio.</p>
<h2>Una propuesta homogénea simplifica mucho</h2>
<p>Si todos los empleados reciben el mismo regalo, la gestión es más sencilla. Aun así, puede ser útil prever una segunda opción para restricciones concretas, siempre que el volumen y la operativa lo permitan.</p>
<p>Cuantas más variantes haya, más difícil será controlar qué recibe cada persona.</p>
<h2>Productos que pueden funcionar bien en remoto</h2>
<p>AOVE, ibéricos en formatos manejables, conservas y packs compactos pueden ser alternativas interesantes. Una pieza grande también puede funcionar, pero implica más volumen y una recepción más delicada.</p>
<p>La elección debe tener en cuenta no solo el presupuesto, sino también cómo recibirá y almacenará el regalo cada empleado.</p>
<h2>¿Se puede enviar a muchas direcciones diferentes?</h2>
<p>Depende de los productos elegidos, del vendedor y del número de destinos. El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente su pedido.</p>
<p>Por eso, cuando una empresa necesita muchos envíos individuales, primero revisamos la composición concreta y la viabilidad logística. No damos por confirmada una operativa masiva hasta comprobarla.</p>
<h2>Fechas de entrega: evita el último día</h2>
<p>En un equipo remoto es mejor trabajar con una ventana de entrega que con un único día exacto, especialmente si hay muchas provincias implicadas. También conviene dejar margen antes de una reunión virtual, comida o evento interno en el que se quiera abrir el regalo.</p>
<p>Si existe una fecha crítica, debe comunicarse desde el principio.</p>
<h2>Qué hacer con empleados ausentes o de viaje</h2>
<p>En equipos distribuidos es frecuente que alguien esté temporalmente fuera de su domicilio habitual. La empresa puede pedir confirmación de dirección antes de cerrar el pedido y fijar un plazo para cambios.</p>
<p>Esto reduce devoluciones y entregas fallidas.</p>
<h2>Cómo mantener el componente de equipo</h2>
<p>Un regalo enviado a casa puede seguir formando parte de una experiencia compartida. Algunas empresas lo vinculan a una reunión virtual, una comida de equipo o un mensaje común.</p>
<p>El producto se convierte así en un punto de conexión entre personas que no están físicamente en el mismo lugar.</p>
<h2>Presupuesto y logística deben calcularse juntos</h2>
<p>En remoto, no conviene pensar únicamente en el precio del contenido. Los envíos individuales pueden influir en el coste total y en la viabilidad de determinadas propuestas.</p>
<p>Por eso es mejor indicar desde el principio si el presupuesto por persona debe incluir transporte o si existe margen adicional.</p>
<h2>Qué información necesitamos</h2>
<p>Número de empleados, presupuesto por persona, provincias o destinos aproximados, fecha, preferencias de producto y si todos recibirán la misma propuesta. Con estos datos podremos revisar qué opciones reales existen.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Haz que el equipo remoto también reciba su regalo</h2><p>Envíanos cantidades, presupuesto y destinos. Revisaremos qué operativa y productos pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
)
);

$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author=$admins?(int)$admins[0]:1;
$rows=array();

foreach($pages as $p){
    $words=emdo_b2b03_words($p['content']);
    if($words<650) throw new Exception($p['key'].' too short: '.$words);

    $existing=emdo_b2b03_existing($p['key'],$p['slug']);
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
    update_post_meta($id,'_emdo_seo_landing_batch','20260926-b2b-03');
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
echo wp_json_encode(array('batch'=>'20260926-b2b-03','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
