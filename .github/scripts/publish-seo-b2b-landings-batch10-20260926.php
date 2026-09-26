<?php
if (!defined('ABSPATH')) { exit; }

function emdo_b2b10_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_b2b10_existing($key, $slug) {
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
'key'=>'b2b-choose-corporate-gift-not-typical',
'slug'=>'como-elegir-regalo-corporativo-sin-caer-en-lo-tipico',
'title'=>'Cómo elegir un regalo corporativo sin caer en lo típico',
'excerpt'=>'Cómo elegir un regalo corporativo diferente sin recurrir a merchandising genérico: criterios de utilidad, origen, destinatario, presupuesto y ocasión.',
'seo_title'=>'Cómo elegir un regalo corporativo sin caer en lo típico',
'seo_description'=>'Cómo elegir un regalo corporativo diferente: utilidad, origen, destinatario, presupuesto y ocasión para evitar opciones genéricas.',
'focus'=>'regalo corporativo original sin caer en lo típico',
'content'=><<<'HTML'
<p>Elegir un regalo corporativo diferente no significa buscar algo extravagante. Muchas empresas caen en opciones muy repetidas porque resultan fáciles de comprar a gran escala, pero eso también hace que el destinatario apenas recuerde quién se lo envió.</p>
<p>La alternativa no tiene por qué ser más cara. Un producto útil, consumible, con origen claro y elegido para una ocasión concreta puede resultar mucho más memorable que un objeto promocional genérico.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres evitar el regalo corporativo de siempre?</h2><p>Cuéntanos destinatarios, presupuesto, ocasión y fecha. Revisaremos propuestas gastronómicas con una lógica clara y productos reales disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Empieza por el motivo, no por el catálogo</h2>
<p>Un regalo de agradecimiento, una bienvenida, un aniversario o un cierre de proyecto no deberían resolverse exactamente igual. Definir el motivo permite elegir un formato coherente y evita comprar “algo corporativo” sin contexto.</p>
<p>Cuando el destinatario entiende por qué recibe el regalo, la percepción cambia.</p>
<h2>Evita regalar solo por visibilidad de marca</h2>
<p>Un objeto con un gran logotipo puede funcionar como merchandising, pero no siempre como regalo. Si el objetivo es agradecer o reconocer una relación, conviene que el destinatario perciba primero el valor del producto y después la marca.</p>
<p>Una tarjeta discreta puede ser suficiente para contextualizar el gesto.</p>
<h2>Prioriza utilidad y disfrute</h2>
<p>Los productos gastronómicos tienen la ventaja de que se consumen, se comparten y no ocupan espacio indefinidamente. Esto reduce el riesgo de regalar algo que termine guardado en un cajón.</p>
<p>AOVE, ibéricos, conservas o una selección pequeña pueden adaptarse a perfiles muy distintos.</p>
<h2>El origen puede ser el elemento diferencial</h2>
<p>Una referencia conocida puede resultar especial si se conoce quién la produce, de dónde procede y qué características tiene. Esa información convierte un producto en una historia y evita la sensación de regalo anónimo.</p>
<p>En El Mercado de Origen el productor y la procedencia forman parte de las fichas.</p>
<h2>Menos referencias, mejor elegidas</h2>
<p>Una caja con muchos productos no es necesariamente más original. A menudo una selección corta, coherente y con un producto protagonista transmite más criterio.</p>
<p>El objetivo es que cada referencia tenga una razón para estar ahí.</p>
<h2>Adapta el regalo al destinatario</h2>
<p>No hace falta personalizar uno a uno, pero sí distinguir entre empleados, clientes, proveedores o invitados de evento. Cada grupo tiene expectativas y necesidades diferentes.</p>
<p>Un mismo regalo universal puede terminar siendo demasiado genérico para todos.</p>
<h2>Piensa en cómo se entregará</h2>
<p>Un regalo espectacular pero incómodo de transportar puede ser una mala elección para un evento o un equipo remoto. Tamaño, peso, conservación y facilidad de recepción son parte del diseño.</p>
<p>La logística debe evaluarse antes de cerrar la selección.</p>
<h2>No confundas originalidad con personalización compleja</h2>
<p>La diferenciación puede lograrse mediante el producto, el productor, la combinación o el momento de entrega. No siempre hace falta un packaging especial o una personalización física.</p>
<p>Si se necesita, debe confirmarse según cantidades, vendedor y plazo.</p>
<h2>Define un presupuesto realista</h2>
<p>Con un rango cerrado es más fácil decidir dónde concentrar valor. En presupuestos pequeños, una buena referencia puede ser suficiente; en rangos altos, conviene mejorar categoría y selección antes que añadir volumen.</p>
<p>También debe aclararse si el transporte está incluido.</p>
<h2>Busca coherencia entre producto y empresa</h2>
<p>Una compañía que habla de sostenibilidad, territorio o cercanía puede encontrar más sentido en productos con procedencia visible que en regalos impersonales. El detalle puede reforzar valores ya existentes sin necesidad de convertirlos en un discurso comercial.</p>
<p>La coherencia hace que el regalo parezca natural.</p>
<h2>Qué información necesitamos</h2>
<p>Tipo de destinatario, número de unidades, presupuesto, ocasión, fecha y forma de entrega. Con esos datos podemos filtrar propuestas y evitar opciones genéricas que no aporten valor.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Haz que el regalo tenga una razón detrás</h2><p>Dinos qué quieres transmitir y revisaremos productos que encajen con la ocasión y el destinatario.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'gift-spanish-gourmet-products',
'slug'=>'productos-gourmet-espanoles-para-regalar',
'title'=>'Productos gourmet españoles para regalar',
'excerpt'=>'Selección de categorías de productos gourmet españoles para regalar: AOVE, ibéricos, jamón, conservas y productos de despensa con origen visible.',
'seo_title'=>'Productos gourmet españoles para regalar',
'seo_description'=>'Productos gourmet españoles para regalar: AOVE, ibéricos, jamón, conservas, despensa y criterios para elegir según destinatario.',
'focus'=>'productos gourmet españoles para regalar',
'content'=><<<'HTML'
<p>España ofrece una enorme variedad de productos gastronómicos que pueden convertirse en regalos con mucha más personalidad que una cesta genérica. La clave es elegir categorías que tengan sentido para el destinatario y priorizar referencias con productor y procedencia identificables.</p>
<p>No existe un único “producto gourmet” ideal. AOVE, jamón, ibéricos, conservas y productos de despensa responden a ocasiones, presupuestos y formas de consumo diferentes.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas productos gourmet españoles para regalar?</h2><p>Cuéntanos para quién es, presupuesto y fecha. Revisaremos categorías y productos disponibles que puedan encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar propuestas</a></p></div>
<h2>Aceite de oliva virgen extra</h2>
<p>El AOVE es una de las opciones más versátiles. Puede regalarse solo, combinarse con otros productos o formar parte de una selección de varios productores o variedades.</p>
<p>Es práctico, reconocible y permite hablar de territorio y elaboración.</p>
<h2>Jamón</h2>
<p>Una pieza de jamón aporta presencia y suele reservarse para regalos de mayor entidad. El presupuesto, el peso, la categoría y la facilidad de consumo deben valorarse antes de elegir.</p>
<p>Para determinados destinatarios, el formato loncheado puede resultar más cómodo.</p>
<h2>Paleta</h2>
<p>La paleta permite trabajar con otro tamaño y rango de precio manteniendo la experiencia de una pieza completa. Puede ser una opción interesante para regalos familiares o corporativos.</p>
<p>Como siempre, conviene revisar productor, categoría y peso.</p>
<h2>Ibéricos</h2>
<p>Los embutidos y loncheados ibéricos funcionan bien para compartir y ofrecen formatos más compactos que una pieza. También permiten crear selecciones variadas.</p>
<p>Son especialmente adecuados para aperitivos y celebraciones.</p>
<h2>Conservas</h2>
<p>Las conservas pueden aportar originalidad y variedad. Son fáciles de almacenar, tienen formatos manejables y permiten descubrir categorías menos previsibles.</p>
<p>También combinan bien con AOVE y otros productos de despensa.</p>
<h2>Productos de despensa</h2>
<p>Una buena despensa ofrece alternativas para quien busca regalos prácticos y fáciles de integrar en la cocina cotidiana.</p>
<p>La procedencia y el productor ayudan a diferenciar unas referencias de otras.</p>
<h2>Cómo combinar varios productos</h2>
<p>Una selección equilibrada puede partir de un producto protagonista y añadir dos o tres complementos. Por ejemplo, AOVE con conservas o ibéricos con aceite.</p>
<p>La combinación debería responder a una lógica de consumo, no a llenar espacio.</p>
<h2>Qué productos regalar a clientes</h2>
<p>Para clientes conviene valorar relación, presupuesto y posible política de aceptación de regalos. Una propuesta sobria y bien elegida suele funcionar mejor que una cesta excesiva.</p>
<p>En cuentas estratégicas puede elevarse la categoría o elegir una pieza protagonista.</p>
<h2>Qué productos regalar a empleados</h2>
<p>En plantillas grandes, la disponibilidad y repetibilidad son claves. En equipos pequeños hay más margen para referencias concretas o propuestas más singulares.</p>
<p>La logística de entrega también influye en el formato.</p>
<h2>Qué productos regalar a una persona extranjera</h2>
<p>El AOVE, los ibéricos o las conservas pueden representar bien la gastronomía española, pero si el producto debe salir de España hay que revisar restricciones del destino.</p>
<p>No todas las categorías pueden enviarse a todos los países.</p>
<h2>Cómo elegir según presupuesto</h2>
<p>En presupuestos pequeños, una referencia bien seleccionada puede ser suficiente. En rangos medios, se puede crear una pequeña selección. En presupuestos altos, conviene mejorar categoría y formato antes que aumentar el número de productos.</p>
<p>La percepción de calidad depende más de la elección que de la cantidad.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, destinatario, fecha, destino y categorías preferidas o excluidas. Con esos datos podemos revisar productos disponibles y propuestas realistas.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Elige productos españoles con identidad</h2><p>Cuéntanos el contexto del regalo y revisaremos qué referencias del catálogo pueden aportar más valor.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'gift-spanish-artisan-products',
'slug'=>'productos-artesanos-espanoles-para-regalar',
'title'=>'Productos artesanos españoles para regalar',
'excerpt'=>'Ideas de productos artesanos españoles para regalar con productor, procedencia y elaboración visibles, desde AOVE e ibéricos hasta conservas y despensa.',
'seo_title'=>'Productos artesanos españoles para regalar',
'seo_description'=>'Productos artesanos españoles para regalar: cómo elegir referencias con productor, procedencia, elaboración y formatos adecuados.',
'focus'=>'productos artesanos españoles para regalar',
'content'=><<<'HTML'
<p>Cuando alguien busca productos artesanos españoles para regalar, normalmente busca algo más que una categoría gastronómica. Busca una referencia con origen, un productor identificable y una elaboración que tenga sentido contar.</p>
<p>El término “artesano” puede utilizarse de formas muy distintas, por lo que conviene no quedarse solo con una etiqueta comercial. La mejor selección parte de la información real disponible sobre quién produce, dónde lo hace y qué características tiene el producto.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas productos de productores españoles para regalar?</h2><p>Dinos presupuesto, destinatario y ocasión. Revisaremos referencias con procedencia y productor visibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Más allá de la etiqueta “artesano”</h2>
<p>Antes de elegir, conviene revisar la información concreta del producto. Procedencia, productor, ingredientes, formato y método de elaboración aportan más contexto que una palabra genérica.</p>
<p>El objetivo es poder explicar de forma sencilla qué hace especial a la referencia.</p>
<h2>AOVE de productor</h2>
<p>El aceite de oliva virgen extra permite regalar una referencia ligada a una finca, cooperativa o productor concreto. Variedad, zona y perfil pueden formar parte de la historia.</p>
<p>Es una opción práctica y adecuada para muchos tipos de destinatario.</p>
<h2>Ibéricos y productos curados</h2>
<p>Los productos curados también pueden aportar identidad cuando se conoce quién los elabora y de dónde proceden. El formato puede ir desde piezas completas hasta loncheados.</p>
<p>Conviene adaptar la elección al consumo previsto.</p>
<h2>Conservas de productor</h2>
<p>Las conservas son una categoría interesante para descubrir pequeñas producciones y propuestas diferentes. Además, su facilidad de almacenamiento las hace muy aptas para regalo.</p>
<p>Pueden combinarse con AOVE u otros productos de despensa.</p>
<h2>Productos de despensa con procedencia clara</h2>
<p>Muchas referencias de despensa pueden tener un gran valor cuando existe información sobre su origen. La clave es evitar convertir la selección en una suma de productos sin relación.</p>
<p>Un hilo conductor por territorio o productor puede ayudar.</p>
<h2>Regalar a alguien que valora la gastronomía</h2>
<p>Una persona aficionada suele apreciar detalles como productor, variedad, formato o procedencia. En este caso puede ser mejor elegir menos referencias y aportar más contexto.</p>
<p>La posibilidad de descubrir algo nuevo añade valor al regalo.</p>
<h2>Regalos corporativos con productos artesanos</h2>
<p>En empresa, estas referencias pueden ayudar a diferenciar un regalo sin necesidad de personalización compleja. Un producto con identidad ya aporta una historia propia.</p>
<p>Si se necesitan muchas unidades, debe comprobarse que exista stock suficiente.</p>
<h2>Cómo evitar una selección incoherente</h2>
<p>No todos los productos “artesanos” tienen por qué formar parte de la misma cesta. Conviene elegir un criterio común: territorio, tipo de consumo, productor o categoría.</p>
<p>La coherencia mejora la percepción del conjunto.</p>
<h2>Presentación frente a producto</h2>
<p>En este tipo de regalo, la presentación debería acompañar la historia del productor, no sustituirla. Una caja sencilla puede ser suficiente si las referencias tienen valor por sí mismas.</p>
<p>La personalización, si se necesita, debe revisarse caso por caso.</p>
<h2>Presupuesto</h2>
<p>Los productos de productor pueden encontrarse en rangos distintos. En presupuestos contenidos, una sola referencia puede funcionar; en niveles superiores, se puede crear una selección con varias procedencias.</p>
<p>Lo importante es mantener calidad y contexto.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, número de unidades, destinatario, fecha, destino y si existe una zona o categoría que quieras priorizar. Con esos datos podemos revisar opciones disponibles.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Pon al productor en el centro del regalo</h2><p>Cuéntanos qué buscas y revisaremos referencias españolas con procedencia e identidad claras.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'gift-local-spanish-producers',
'slug'=>'regalos-productores-locales-espanoles',
'title'=>'Regalos de productores locales y españoles',
'excerpt'=>'Ideas para regalar productos de productores locales y españoles, priorizando origen, cercanía, identidad y una selección coherente con el destinatario.',
'seo_title'=>'Regalos de productores locales y españoles',
'seo_description'=>'Ideas para regalar productos de productores locales y españoles con origen visible, categorías gastronómicas y criterios para elegir mejor.',
'focus'=>'regalos de productores locales españoles',
'content'=><<<'HTML'
<p>Regalar productos de productores locales o españoles puede ser una forma de dar más contexto al regalo y alejarse de propuestas anónimas. El valor no está únicamente en que el producto sea de una zona concreta, sino en poder identificar quién está detrás y qué relación tiene con el territorio.</p>
<p>Este enfoque puede funcionar tanto en regalos personales como corporativos, siempre que la selección se adapte al destinatario, presupuesto y logística.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres regalar productos de productores españoles?</h2><p>Cuéntanos presupuesto, ocasión y destinatarios. Revisaremos qué referencias con origen visible pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar propuestas</a></p></div>
<h2>Qué significa “local” en un regalo</h2>
<p>Puede significar producto de la misma provincia, de una comunidad autónoma, de una región vinculada al destinatario o simplemente de productores españoles frente a propuestas más impersonales.</p>
<p>Conviene definir qué cercanía tiene sentido en cada caso.</p>
<h2>Regalos ligados a una región</h2>
<p>Si la empresa o la persona tiene una conexión con un territorio concreto, seleccionar productos de esa zona puede aportar una historia clara al regalo.</p>
<p>Esto funciona especialmente bien en aniversarios, visitas, eventos o relaciones con componente territorial.</p>
<h2>Regalos con varios productores</h2>
<p>Otra opción es construir una selección de distintas procedencias españolas. Esto permite mostrar variedad y crear una pequeña ruta gastronómica a través del regalo.</p>
<p>La selección debería mantener coherencia y no convertirse en un catálogo aleatorio.</p>
<h2>AOVE y productores locales</h2>
<p>El aceite permite trabajar muy bien la relación con territorio y productor. Variedad, zona y elaboración pueden diferenciar una referencia de otra.</p>
<p>Es además un producto útil y fácil de explicar.</p>
<h2>Ibéricos y curados</h2>
<p>Los productos curados también pueden expresar procedencia y tradición. El formato debe elegirse según destinatario y facilidad de consumo.</p>
<p>Para regalos múltiples, los loncheados pueden simplificar la logística.</p>
<h2>Conservas y despensa</h2>
<p>Son categorías ideales para descubrir pequeños productores y ampliar la selección más allá de los productos más conocidos.</p>
<p>También permiten trabajar con formatos compactos y fáciles de almacenar.</p>
<h2>Regalos para empresas</h2>
<p>Una empresa puede utilizar estos productos para clientes, empleados, colaboradores o eventos. El enfoque permite diferenciar el regalo sin depender únicamente de branding o packaging personalizado.</p>
<p>El productor y el origen ya aportan identidad.</p>
<h2>Regalos personales</h2>
<p>Para familiares o amigos, conocer gustos y relación con una zona permite afinar mucho más. Un producto del lugar de origen de la persona o de un viaje compartido puede tener un significado adicional.</p>
<p>El contexto convierte una referencia sencilla en algo más personal.</p>
<h2>Cómo seleccionar varios productores</h2>
<p>Conviene limitar el número de referencias y buscar un hilo conductor: aperitivo, despensa, aceites o una región concreta.</p>
<p>La variedad debe sumar, no dispersar.</p>
<h2>Disponibilidad y escala</h2>
<p>Pequeños productores pueden tener stock limitado. Si se necesitan muchas unidades, la capacidad de suministro debe comprobarse antes de cerrar el regalo.</p>
<p>En pedidos grandes puede ser necesario trabajar con alternativas equivalentes.</p>
<h2>Qué información necesitamos</h2>
<p>Número de regalos, presupuesto, destinatario, fecha, destino y si buscas una región concreta o una selección nacional. Con esos datos podemos revisar propuestas viables.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Regala productos con un lugar y una persona detrás</h2><p>Dinos qué relación quieres destacar y revisaremos productores y referencias disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'b2b-gourmet-baskets-by-budget',
'slug'=>'cestas-gourmet-personalizadas-segun-presupuesto',
'title'=>'Cestas gourmet personalizadas según presupuesto',
'excerpt'=>'Cómo plantear cestas gourmet personalizadas según presupuesto, desde selecciones compactas hasta propuestas premium, sin perder coherencia ni viabilidad.',
'seo_title'=>'Cestas gourmet personalizadas según presupuesto',
'seo_description'=>'Cestas gourmet personalizadas según presupuesto: cómo adaptar productos, categorías, cantidades y nivel de regalo a distintos rangos.',
'focus'=>'cestas gourmet personalizadas según presupuesto',
'content'=><<<'HTML'
<p>Personalizar una cesta gourmet por presupuesto no consiste en añadir o quitar productos al azar hasta alcanzar una cifra. Lo más eficaz es definir un rango económico y construir una selección coherente dentro de él, priorizando las categorías que más valor aportan al destinatario.</p>
<p>Este enfoque resulta útil para empresas con distintos niveles de cliente, plantillas con presupuesto cerrado o pedidos en los que el coste por persona debe mantenerse bajo control.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Tienes un presupuesto por cesta?</h2><p>Dinos número de unidades, rango por persona, fecha y destinatarios. Revisaremos composiciones posibles con productos disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Solicitar propuesta</a></p></div>
<h2>Empieza por una cifra real</h2>
<p>Antes de pensar en productos, define cuánto puede gastar la empresa por cesta y si ese importe incluye transporte. Esto evita trabajar sobre propuestas que luego no son viables.</p>
<p>Un rango también puede ser útil si todavía no existe una cifra cerrada.</p>
<h2>Presupuestos contenidos</h2>
<p>Cuando el presupuesto es limitado, suele funcionar mejor elegir uno o dos productos con identidad que intentar construir una cesta muy grande.</p>
<p>AOVE, una conserva especial o un pequeño pack pueden ser suficientes si están bien seleccionados.</p>
<h2>Presupuestos medios</h2>
<p>En rangos intermedios se puede introducir variedad y combinar dos o tres categorías: aceite, ibéricos, conservas o productos de despensa.</p>
<p>Conviene mantener un producto protagonista para que el conjunto no pierda foco.</p>
<h2>Presupuestos altos</h2>
<p>En niveles superiores puede tener sentido incorporar jamón, paleta, ibéricos de mayor categoría o una selección premium.</p>
<p>El aumento de presupuesto debería notarse en la calidad y la categoría, no solo en el número de referencias.</p>
<h2>Personalizar por tipo de destinatario</h2>
<p>Una empresa puede trabajar con una cesta para empleados, otra para clientes y una tercera para cuentas estratégicas. Cada nivel puede tener su propio presupuesto y composición.</p>
<p>Definir grupos claros facilita el control de costes y stock.</p>
<h2>Personalizar por preferencias</h2>
<p>También pueden estudiarse variantes sin alcohol o alternativas que eviten determinadas categorías, siempre que la operativa lo permita.</p>
<p>Cuantas más versiones existan, más compleja será la preparación.</p>
<h2>Cómo mantener coherencia entre varios presupuestos</h2>
<p>Si existen tres niveles, conviene que compartan una lógica común. Por ejemplo, todos pueden incluir AOVE y diferenciarse en la categoría del producto principal o en el número de complementos.</p>
<p>Esto ayuda a que las propuestas formen parte de una misma campaña.</p>
<h2>Evita “rellenar” para llegar al presupuesto</h2>
<p>No es obligatorio gastar hasta el último euro si hacerlo implica añadir productos que no aportan valor. Una cesta más limpia puede resultar mejor que una propuesta llena de referencias secundarias.</p>
<p>La selección debe responder al destinatario, no a ocupar espacio.</p>
<h2>Presentación y personalización física</h2>
<p>La personalización puede incluir mensaje, tarjeta o presentación, pero estas opciones dependen de vendedor, producto y cantidades.</p>
<p>Conviene resolver primero composición y logística y después valorar elementos adicionales.</p>
<h2>Logística y transporte</h2>
<p>En envíos individuales o múltiples domicilios, el coste de transporte puede alterar significativamente el presupuesto por persona. El Mercado de Origen funciona como marketplace y cada vendedor prepara y expide directamente su pedido.</p>
<p>La composición debe revisarse junto con la operativa real.</p>
<h2>Cómo comparar propuestas dentro del mismo rango</h2>
<p>No compares únicamente el número de productos. Revisa categoría, peso, productor, procedencia, facilidad de consumo y formato.</p>
<p>Dos cestas con el mismo coste pueden ofrecer niveles de calidad muy distintos.</p>
<h2>Qué información necesitamos</h2>
<p>Número de unidades, presupuesto por cesta, grupos de destinatarios, fecha, destinos y cualquier requisito especial. Con esos datos podemos revisar varias composiciones ajustadas al rango.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Construye la cesta desde el presupuesto</h2><p>Envíanos el rango y el volumen y revisaremos qué selección puede ofrecer más valor sin añadir complejidad innecesaria.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
)
);

$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author=$admins?(int)$admins[0]:1;
$rows=array();

foreach($pages as $p){
    $words=emdo_b2b10_words($p['content']);
    if($words<650) throw new Exception($p['key'].' too short: '.$words);

    $existing=emdo_b2b10_existing($p['key'],$p['slug']);
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
    update_post_meta($id,'_emdo_seo_landing_batch','20260926-b2b-10');
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
echo wp_json_encode(array('batch'=>'20260926-b2b-10','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
