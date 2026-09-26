<?php
if (!defined('ABSPATH')) { exit; }

function emdo_b2b07_words($html) {
    $text = trim(preg_replace('/\\s+/u', ' ', wp_strip_all_tags(strip_shortcodes($html))));
    if ($text === '') return 0;
    preg_match_all('/[\\p{L}\\p{M}]+(?:[’\\x{27}’-][\\p{L}\\p{M}]+)*/u', $text, $m);
    return count($m[0]);
}

function emdo_b2b07_existing($key, $slug) {
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
'key'=>'gift-spanish-products-foreigner',
'slug'=>'productos-espanoles-regalar-extranjero',
'title'=>'Qué productos españoles regalar a un extranjero',
'excerpt'=>'Ideas de productos españoles para regalar a una persona extranjera: AOVE, ibéricos, conservas y otras opciones con origen y productor visibles.',
'seo_title'=>'Qué productos españoles regalar a un extranjero',
'seo_description'=>'Ideas para regalar productos españoles a una persona extranjera: AOVE, ibéricos, conservas, formatos prácticos y criterios para elegir.',
'focus'=>'productos españoles para regalar a un extranjero',
'content'=><<<'HTML'
<p>Cuando quieres hacer un regalo a una persona extranjera, la gastronomía española ofrece muchas posibilidades. El reto no es encontrar algo “típico”, sino elegir un producto que represente bien su origen, sea fácil de disfrutar y encaje con la forma en que el destinatario va a recibirlo o transportarlo.</p>
<p>Un buen regalo no necesita incluir muchas referencias. A veces basta con un producto reconocible, con procedencia clara y una historia sencilla de explicar.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un regalo gastronómico con origen español?</h2><p>Cuéntanos para quién es, presupuesto y si el regalo debe viajar. Revisaremos qué productos disponibles pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Aceite de oliva virgen extra</h2>
<p>El AOVE es uno de los productos españoles más fáciles de explicar y regalar. Tiene utilidad cotidiana, permite hablar de variedad, origen y productor y no depende de que el destinatario tenga conocimientos especiales para disfrutarlo.</p>
<p>Puede funcionar como regalo individual o como parte de una pequeña selección.</p>
<h2>Jamón, paleta e ibéricos</h2>
<p>Son productos muy asociados a la gastronomía española y pueden resultar especialmente atractivos para alguien que ya los conoce o quiere descubrirlos. El formato es importante: una pieza completa tiene presencia, mientras que el loncheado es más práctico para transportar y consumir.</p>
<p>Si el regalo debe viajar fuera de España, hay que revisar las restricciones del país de destino antes de elegir productos cárnicos.</p>
<h2>Conservas</h2>
<p>Las conservas ofrecen variedad, larga duración y formatos manejables. Pueden ser una buena opción para alguien que quiere llevarse un recuerdo gastronómico sin complicaciones de consumo inmediato.</p>
<p>También permiten construir una selección más original que los regalos más previsibles.</p>
<h2>Productos de despensa</h2>
<p>Además del AOVE y las conservas, algunos productos de despensa pueden funcionar bien si tienen procedencia identificable y un formato cómodo.</p>
<p>La clave es evitar referencias que necesiten demasiada explicación o condiciones especiales de conservación.</p>
<h2>Qué regalar si el destinatario viaja en avión</h2>
<p>En este caso, el tamaño, peso, envase y restricciones del transporte son fundamentales. Los líquidos pueden tener limitaciones en equipaje de mano y algunos alimentos no pueden entrar en determinados países.</p>
<p>Antes de elegir el regalo, conviene saber si se entregará en España para consumir aquí o si viajará con la persona.</p>
<h2>Qué regalar a alguien que vive fuera de España</h2>
<p>Si el pedido debe enviarse directamente al extranjero, no hay que asumir que cualquier producto puede transportarse. Las condiciones dependen del destino, del vendedor y de la categoría alimentaria.</p>
<p>En la consulta inicial conviene indicar país y ciudad para revisar qué alternativas son realistas.</p>
<h2>Un producto con historia suele funcionar mejor</h2>
<p>Para una persona extranjera, poder explicar quién produce el alimento y de dónde procede aporta contexto. El regalo se convierte en una pequeña introducción a una parte de la gastronomía española.</p>
<p>En El Mercado de Origen el productor y la procedencia están visibles en las fichas de producto.</p>
<h2>¿Una selección de varias regiones o un solo origen?</h2>
<p>Ambas opciones pueden funcionar. Una selección de distintas procedencias ofrece variedad; un regalo centrado en una región concreta puede tener más personalidad.</p>
<p>Si el destinatario ha visitado una zona determinada de España, vincular el regalo a ese viaje puede añadir significado.</p>
<h2>Qué evitar</h2>
<p>Evita elegir únicamente por estereotipo, regalar productos que el destinatario no pueda transportar o consumir y sobrecargar la selección con demasiadas referencias.</p>
<p>También conviene revisar alergias, restricciones alimentarias y normas de importación cuando corresponda.</p>
<h2>Cómo elegir según presupuesto</h2>
<p>Con un presupuesto contenido, un AOVE o una conserva especial pueden ser suficientes. En un rango medio, se puede crear una pequeña selección. En presupuestos superiores, una pieza de mayor entidad o un conjunto más completo puede tener sentido.</p>
<p>El valor está en la elección, no necesariamente en el volumen.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, país o lugar de entrega, si el producto debe viajar, fecha y preferencias conocidas del destinatario. Con esos datos podemos revisar qué opciones disponibles pueden funcionar mejor.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Regala una parte de la gastronomía española</h2><p>Dinos el contexto del regalo y revisaremos productos con origen y productor identificables.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'gift-foodie-has-everything',
'slug'=>'regalos-gastronomicos-alguien-tiene-de-todo',
'title'=>'Regalos gastronómicos para alguien que tiene de todo',
'excerpt'=>'Ideas de regalos gastronómicos para alguien difícil de sorprender: productos consumibles, con origen, productores concretos y selecciones con criterio.',
'seo_title'=>'Regalos gastronómicos para alguien que tiene de todo',
'seo_description'=>'Ideas gastronómicas para regalar a alguien que tiene de todo: productos consumibles, con origen, útiles y fáciles de disfrutar.',
'focus'=>'regalos gastronómicos para alguien que tiene de todo',
'content'=><<<'HTML'
<p>Regalar a alguien que “tiene de todo” suele ser difícil porque los objetos acumulan poco valor cuando la persona ya dispone de lo que necesita. En esos casos, los productos gastronómicos tienen una ventaja clara: se disfrutan, se consumen y no se convierten en otro objeto permanente.</p>
<p>La clave está en elegir algo con personalidad, no simplemente algo caro. Un productor concreto, una procedencia interesante o un formato poco habitual pueden aportar más que una caja enorme.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Necesitas sorprender sin regalar otro objeto?</h2><p>Cuéntanos presupuesto, ocasión y gustos generales. Revisaremos qué productos gastronómicos pueden resultar especiales.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar ideas</a></p></div>
<h2>Productos consumibles: una ventaja real</h2>
<p>Un buen alimento no ocupa espacio para siempre. Se abre, se comparte y se disfruta. Esto lo convierte en una opción interesante para personas que ya tienen demasiadas cosas materiales.</p>
<p>Además, permite adaptar el nivel del regalo al presupuesto sin entrar en comparaciones con objetos de lujo.</p>
<h2>Elige origen, no solo categoría</h2>
<p>Decir “aceite”, “jamón” o “conserva” es solo el principio. Lo que puede hacer especial el regalo es quién lo produce, de dónde viene y qué características concretas tiene.</p>
<p>El origen ayuda a convertir un producto conocido en una experiencia nueva.</p>
<h2>Un AOVE diferente</h2>
<p>Para alguien que cocina o disfruta de la gastronomía, un AOVE con procedencia clara puede ser más interesante que otro utensilio de cocina.</p>
<p>Es práctico y se puede comparar, catar y utilizar en diferentes platos.</p>
<h2>Una selección de ibéricos</h2>
<p>Los ibéricos pueden ser una opción adecuada para alguien que disfruta de aperitivos o reuniones en casa. Los formatos loncheados facilitan el consumo y permiten probar varias referencias.</p>
<p>Si el destinatario no consume carne, hay alternativas mejores.</p>
<h2>Conservas y productos poco habituales</h2>
<p>Las conservas permiten salir de los regalos más previsibles y descubrir productores o categorías que quizá el destinatario no compraría habitualmente.</p>
<p>Una selección pequeña puede tener más interés que una cesta genérica.</p>
<h2>Regalar una experiencia de consumo</h2>
<p>Puedes pensar el regalo alrededor de un momento: un aperitivo, una cena informal, una degustación de aceites o una selección para compartir.</p>
<p>Ese enfoque da sentido a los productos y ayuda a que el conjunto resulte memorable.</p>
<h2>Menos productos, mejor elegidos</h2>
<p>Cuando alguien tiene de todo, la abundancia no suele impresionar. En cambio, una selección breve y bien explicada puede resultar más personal.</p>
<p>Dos o tres referencias de calidad pueden funcionar mejor que una caja muy llena.</p>
<h2>Evita regalar algo que requiera más “cosas”</h2>
<p>Si el producto necesita un accesorio específico, mucho espacio o un mantenimiento especial, puede perder parte de su atractivo.</p>
<p>Los formatos fáciles de abrir, consumir y compartir suelen ser una apuesta más segura.</p>
<h2>Cómo elegir según personalidad</h2>
<p>Para una persona curiosa, busca productos menos conocidos. Para alguien tradicional, una gran referencia clásica puede funcionar mejor. Para un amante de la cocina, AOVE o conservas de calidad pueden tener mucho sentido.</p>
<p>No es necesario conocer cada gusto; basta con entender cómo disfruta de la comida.</p>
<h2>Presupuesto y percepción</h2>
<p>Un presupuesto alto no garantiza sorpresa. A veces un producto pequeño pero difícil de encontrar genera más interés que una propuesta costosa y genérica.</p>
<p>El objetivo debería ser descubrir, disfrutar o compartir.</p>
<h2>Ideas para quien disfruta descubriendo sabores</h2>
<p>Si la persona suele probar cosas nuevas, puede funcionar mejor una referencia menos obvia que un producto muy conocido. Una conserva especial, un AOVE de variedad concreta o un pack de productor pueden aportar ese componente de descubrimiento.</p>
<p>La clave es que el producto tenga una historia verificable y no depender únicamente de una presentación llamativa.</p>
<h2>Regalos para compartir</h2>
<p>Cuando no sabes exactamente qué le gustará, elegir algo que pueda abrirse y compartirse reduce el riesgo. Una selección de aperitivo, ibéricos o conservas puede disfrutarse en compañía y generar una experiencia más amplia.</p>
<p>También evita que el regalo dependa de un gusto extremadamente individual.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, ocasión, gustos generales, restricciones y fecha. Con esos datos podemos revisar qué productos disponibles pueden aportar algo diferente.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Sorprende con algo que se disfruta y desaparece</h2><p>Cuéntanos el perfil de la persona y revisaremos opciones gastronómicas con personalidad.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir a contacto</a></p></div>
HTML
),
array(
'key'=>'gift-ideas-aove',
'slug'=>'ideas-regalos-aceite-oliva-virgen-extra',
'title'=>'Ideas de regalos con aceite de oliva virgen extra',
'excerpt'=>'Ideas para regalar AOVE: botella única, selección de aceites, pack gastronómico, regalo corporativo o detalle para amantes de la cocina.',
'seo_title'=>'Ideas de regalos con aceite de oliva virgen extra',
'seo_description'=>'Ideas para regalar aceite de oliva virgen extra: formatos, combinaciones, destinatarios, presupuestos y criterios para elegir un buen AOVE.',
'focus'=>'regalos con aceite de oliva virgen extra',
'content'=><<<'HTML'
<p>El aceite de oliva virgen extra puede ser un regalo mucho más versátil de lo que parece. Sirve como detalle individual, como producto protagonista de una cesta, como regalo corporativo o como opción para alguien que disfruta cocinando.</p>
<p>La diferencia está en elegir el AOVE por sus características y procedencia, no solo por el envase. Productor, variedad, origen, formato y ocasión ayudan a convertir una botella en un regalo con sentido.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres regalar AOVE y no sabes qué formato elegir?</h2><p>Cuéntanos presupuesto, destinatario y ocasión. Revisaremos qué aceites disponibles pueden encajar.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Una sola botella bien elegida</h2>
<p>Un AOVE de productor concreto puede funcionar perfectamente como regalo individual. Es una opción sobria, útil y fácil de disfrutar.</p>
<p>Este formato encaja especialmente bien cuando se busca un detalle elegante sin necesidad de crear una cesta.</p>
<h2>Selección de varios aceites</h2>
<p>Para alguien interesado en la cocina, comparar distintos AOVE puede convertirse en una pequeña experiencia de cata.</p>
<p>La selección puede centrarse en variedades, zonas o productores distintos, siempre dependiendo de la disponibilidad real.</p>
<h2>AOVE con conservas</h2>
<p>El aceite combina bien con conservas y productos de despensa. Esta mezcla permite crear un regalo compacto y coherente, pensado para aperitivos o cocina cotidiana.</p>
<p>Es una alternativa interesante a los lotes centrados únicamente en productos cárnicos.</p>
<h2>AOVE con ibéricos</h2>
<p>Una combinación de aceite e ibéricos puede funcionar como regalo gastronómico clásico con productos muy reconocibles.</p>
<p>La proporción del presupuesto dependerá de cuál de las dos categorías se quiera convertir en protagonista.</p>
<h2>AOVE como regalo corporativo</h2>
<p>Para empresas, el aceite tiene la ventaja de ser relativamente transversal y fácil de explicar. Puede encajar con clientes, empleados, colaboradores o eventos.</p>
<p>Si se necesitan muchas unidades, hay que comprobar stock y formato antes de confirmar la propuesta.</p>
<h2>AOVE para una persona extranjera</h2>
<p>Es un producto fuertemente asociado a la gastronomía española y permite contar una historia sobre origen y productor.</p>
<p>Si la persona va a viajar, conviene tener en cuenta las normas de equipaje para líquidos y el destino final.</p>
<h2>Qué mirar antes de elegir</h2>
<p>No conviene fijarse únicamente en la presentación. Revisa productor, procedencia, variedad, formato, información de la ficha y uso previsto.</p>
<p>El mejor regalo será el que tenga sentido para quien lo recibe.</p>
<h2>Formato y tamaño</h2>
<p>Una botella grande puede tener presencia; un formato más pequeño puede ser más práctico para eventos, viajes o regalos múltiples.</p>
<p>También puede ser interesante combinar varios formatos si el objetivo es descubrir diferentes aceites.</p>
<h2>Presupuesto</h2>
<p>Con un presupuesto contenido, una botella puede ser suficiente. En rangos medios se puede añadir una conserva o crear una pequeña selección. En niveles superiores, se puede trabajar con varios aceites y otros productos complementarios.</p>
<p>No es necesario llenar una cesta para aumentar el valor percibido.</p>
<h2>Presentación y mensaje</h2>
<p>Una nota breve que explique el origen del aceite o el motivo del regalo puede aportar más que un exceso de decoración.</p>
<p>Si se necesita personalización específica, debe revisarse según vendedor, cantidades y plazo.</p>
<h2>Ideas para quien cocina a diario</h2>
<p>En este perfil, el AOVE tiene una ventaja clara: no es un producto ornamental. Puede utilizarse en cocina, aliños o acabados y se integra fácilmente en el día a día.</p>
<p>Por eso, una botella bien elegida puede tener más utilidad que un regalo gastronómico pensado únicamente para una ocasión especial.</p>
<h2>Cómo hacer que el regalo parezca más especial sin complicarlo</h2>
<p>Una selección cuidada, una nota breve sobre el productor o una combinación con una conserva pueden elevar la percepción del regalo sin necesidad de añadir demasiados elementos.</p>
<p>La información sobre origen y variedad aporta más valor que llenar la caja de referencias secundarias.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, número de regalos si son varios, destinatario, fecha y lugar de entrega. Con esos datos podremos revisar los AOVE disponibles y posibles combinaciones.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Convierte el AOVE en un regalo con origen</h2><p>Dinos para quién es y qué presupuesto manejas y revisaremos opciones disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
),
array(
'key'=>'gift-ideas-iberian-ham',
'slug'=>'ideas-regalos-jamon-iberico',
'title'=>'Ideas de regalos con jamón ibérico',
'excerpt'=>'Ideas para regalar jamón ibérico: pieza, paleta, loncheados, selección de ibéricos y combinaciones según presupuesto, ocasión y destinatario.',
'seo_title'=>'Ideas de regalos con jamón ibérico',
'seo_description'=>'Ideas para regalar jamón ibérico: pieza, paleta, loncheado, packs y combinaciones según destinatario, ocasión y presupuesto.',
'focus'=>'regalos con jamón ibérico',
'content'=><<<'HTML'
<p>El jamón ibérico puede convertirse en un regalo muy distinto según el formato. No es lo mismo entregar una pieza completa que una paleta, varios sobres loncheados o una selección de ibéricos. Cada opción tiene un presupuesto, una presencia y una facilidad de consumo diferentes.</p>
<p>Por eso, antes de elegir conviene pensar en quién lo recibirá, cómo lo va a disfrutar y si el regalo debe transportarse o enviarse.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Quieres regalar jamón ibérico?</h2><p>Cuéntanos presupuesto, ocasión y formato preferido si lo tienes claro. Revisaremos qué jamones, paletas o loncheados están disponibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar opciones</a></p></div>
<h2>Una pieza de jamón</h2>
<p>La pieza completa tiene mucha presencia y suele asociarse a un regalo importante. Es adecuada para alguien que disfruta del producto y tiene espacio y utensilios para cortarlo.</p>
<p>También puede ser una opción para compartir en casa o en reuniones.</p>
<h2>Una paleta</h2>
<p>La paleta ofrece un formato más compacto y suele permitir trabajar con otro rango de presupuesto. Puede ser una alternativa interesante cuando se quiere mantener la experiencia de una pieza completa con menor tamaño.</p>
<p>La elección debe basarse en producto concreto, peso y categoría.</p>
<h2>Jamón loncheado</h2>
<p>El loncheado gana en comodidad. No requiere corte, facilita el consumo y puede ser especialmente práctico para personas que viven solas, para viajes o para quien no quiere almacenar una pieza.</p>
<p>También permite regalar varios sobres y distribuir mejor el consumo.</p>
<h2>Selección de ibéricos</h2>
<p>Si no quieres centrar todo el regalo en una pieza, una selección de jamón y otros ibéricos puede aportar variedad.</p>
<p>Este formato funciona bien para aperitivos y para compartir.</p>
<h2>Jamón con AOVE</h2>
<p>Combinar jamón ibérico y aceite de oliva virgen extra crea una propuesta muy vinculada a gastronomía española. Puede ser suficiente con dos productos protagonistas bien elegidos.</p>
<p>No hace falta añadir muchas referencias para que el regalo tenga entidad.</p>
<h2>Jamón como regalo de Navidad</h2>
<p>Es uno de los momentos más habituales para regalarlo, pero también uno de los de mayor demanda. Si se necesitan varias unidades o pesos concretos, conviene empezar con antelación.</p>
<p>La disponibilidad puede reducirse a medida que avanza diciembre.</p>
<h2>Jamón para un cliente o colaborador</h2>
<p>En contextos profesionales, una pieza puede reservarse para relaciones importantes, mientras que formatos loncheados o selecciones pueden adaptarse mejor a carteras más amplias.</p>
<p>Conviene mantener proporcionalidad y revisar políticas de aceptación de regalos cuando corresponda.</p>
<h2>Jamón para una persona extranjera</h2>
<p>Puede ser un regalo muy representativo, pero si debe salir de España hay que revisar las restricciones del país de destino. Los productos cárnicos tienen limitaciones en muchos lugares.</p>
<p>El formato loncheado no elimina necesariamente esas restricciones.</p>
<h2>Cómo elegir categoría y presupuesto</h2>
<p>No todos los jamones ibéricos son iguales. Conviene revisar porcentaje racial, alimentación, productor, peso, formato y cualquier información disponible en la ficha.</p>
<p>El presupuesto debería fijarse antes de elegir para filtrar opciones realistas.</p>
<h2>Qué evitar</h2>
<p>Evita regalar una pieza a alguien que no consume carne, no dispone de espacio o no quiere cortarla. También evita elegir únicamente por tamaño sin revisar la categoría y procedencia.</p>
<p>La comodidad del destinatario forma parte del valor del regalo.</p>
<h2>Qué formato regalar según el tipo de persona</h2>
<p>Para alguien acostumbrado a cortar jamón, una pieza puede ser una buena elección. Para quien busca comodidad, el loncheado resulta más práctico. Para una familia o grupo, una paleta puede ofrecer un equilibrio interesante entre tamaño, presencia y consumo compartido.</p>
<p>Elegir el formato adecuado mejora mucho la experiencia sin necesidad de aumentar el presupuesto.</p>
<h2>Cómo acompañar el jamón sin restarle protagonismo</h2>
<p>Si quieres añadir algo más, conviene elegir uno o dos complementos sencillos, como AOVE o alguna conserva. El objetivo es reforzar la experiencia, no convertir el regalo en una mezcla sin foco.</p>
<p>Cuando el jamón es el protagonista, el resto debería ocupar un papel secundario.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, formato preferido, fecha, destino y perfil del destinatario. Con esos datos podemos revisar qué opciones reales encajan mejor.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Elige el formato de jamón que mejor encaje</h2><p>Dinos para quién es el regalo y revisaremos piezas, paletas, loncheados y posibles complementos.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Ir al formulario de contacto</a></p></div>
HTML
),
array(
'key'=>'gift-ideas-food-lovers',
'slug'=>'regalos-gourmet-amantes-gastronomia',
'title'=>'Ideas de regalos gourmet para amantes de la gastronomía',
'excerpt'=>'Ideas de regalos gourmet para amantes de la gastronomía: AOVE, ibéricos, conservas, selecciones de productores y propuestas para descubrir nuevos sabores.',
'seo_title'=>'Regalos gourmet para amantes de la gastronomía',
'seo_description'=>'Ideas de regalos gourmet para amantes de la gastronomía: productos con origen, productores concretos, AOVE, ibéricos y conservas.',
'focus'=>'regalos gourmet para amantes de la gastronomía',
'content'=><<<'HTML'
<p>Regalar a una persona que disfruta mucho de la gastronomía puede ser más difícil que regalar a alguien que simplemente aprecia comer bien. Quien tiene afición suele conocer productos, comparar calidades y valorar detalles como el origen, el productor o el formato.</p>
<p>Por eso, en lugar de buscar “algo gourmet” de forma genérica, conviene elegir una propuesta que permita descubrir, comparar o disfrutar un producto con más profundidad.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">¿Buscas un regalo para alguien muy gastronómico?</h2><p>Cuéntanos presupuesto, gustos generales y ocasión. Revisaremos productos y productores que puedan resultar interesantes.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Consultar ideas</a></p></div>
<h2>Una selección de AOVE</h2>
<p>Para alguien que cocina o disfruta comparando sabores, una selección de aceites puede ser una experiencia interesante. Permite descubrir diferencias de variedad, origen y productor.</p>
<p>No es necesario que la selección sea grande; dos o tres referencias pueden ser suficientes.</p>
<h2>Ibéricos con información de origen</h2>
<p>Un amante de la gastronomía probablemente valorará conocer más que la categoría general. Productor, tipo de pieza, porcentaje racial y formato pueden formar parte del interés del regalo.</p>
<p>Una selección pequeña puede permitir comparar distintos productos.</p>
<h2>Conservas singulares</h2>
<p>Las conservas pueden sorprender más que categorías muy previsibles y permiten explorar sabores que quizá el destinatario no compra habitualmente.</p>
<p>También son fáciles de almacenar y compartir.</p>
<h2>Un producto protagonista</h2>
<p>Si el presupuesto es alto, no hace falta repartirlo entre muchas referencias. Una pieza destacada puede ser un regalo excelente cuando el destinatario conoce y aprecia la categoría.</p>
<p>El valor está en la calidad y en la elección concreta.</p>
<h2>Regalos para descubrir un productor</h2>
<p>Una buena idea es centrar el regalo en un productor concreto y permitir que el destinatario conozca su trabajo.</p>
<p>Esto añade una historia y evita la sensación de cesta genérica.</p>
<h2>Regalos para comparar</h2>
<p>Otra estrategia es seleccionar productos de la misma categoría con diferencias claras: dos aceites, varias conservas o distintos formatos de ibéricos.</p>
<p>La comparación convierte el regalo en una pequeña degustación.</p>
<h2>Qué regalar a quien cocina mucho</h2>
<p>AOVE, conservas, productos de despensa y referencias versátiles suelen tener más utilidad que alimentos muy específicos.</p>
<p>Conviene pensar en productos que puedan integrarse en recetas y no solo consumirse de una única forma.</p>
<h2>Qué regalar a quien disfruta más del aperitivo</h2>
<p>Ibéricos, conservas y productos pensados para compartir pueden ser más adecuados. Una selección compacta puede crear una experiencia completa sin demasiadas referencias.</p>
<p>El formato debe facilitar abrir y disfrutar el regalo.</p>
<h2>Evita lo “gourmet” solo de nombre</h2>
<p>Una presentación sofisticada no sustituye la calidad ni el origen. Para una persona aficionada a la gastronomía, la información real del producto suele tener más peso que un embalaje llamativo.</p>
<p>Revisa siempre quién produce, procedencia y características.</p>
<h2>Presupuesto</h2>
<p>Con un presupuesto contenido, apuesta por una referencia interesante. Con un rango medio, crea una pequeña selección. En presupuestos altos, mejora categoría o construye una experiencia de comparación.</p>
<p>La cantidad de productos no debería ser el criterio principal.</p>
<h2>Cómo elegir un regalo que no resulte previsible</h2>
<p>Para alguien muy aficionado a la gastronomía, las categorías conocidas pueden seguir funcionando si el enfoque es distinto. En lugar de regalar “aceite”, puedes elegir una variedad concreta; en lugar de “ibéricos”, una selección pensada para comparar formatos o productores.</p>
<p>La diferencia está en el criterio de selección, no necesariamente en buscar productos extraños.</p>
<h2>Regalar conocimiento además de producto</h2>
<p>La experiencia mejora cuando el destinatario puede saber de dónde viene lo que recibe, quién lo produce y qué características tiene. Esa información convierte el regalo en una oportunidad de descubrir algo nuevo.</p>
<p>En El Mercado de Origen procuramos que el productor y la procedencia estén visibles precisamente para que esa parte del regalo no se pierda.</p>
<h2>Qué información necesitamos</h2>
<p>Presupuesto, ocasión, categorías que le gustan o que quieres evitar, fecha y destino. Con esos datos podemos revisar qué opciones del catálogo tienen más sentido.</p>
<div class="emdo-seo-landing-cta" style="margin:32px 0;padding:28px;border:1px solid #e4dfd6;border-radius:14px;background:#faf8f3"><h2 style="margin-top:0">Regala algo que invite a descubrir</h2><p>Cuéntanos qué tipo de gastronomía disfruta la persona y revisaremos propuestas con origen y productor visibles.</p><p style="margin-bottom:0"><a class="button" href="/contacto/">Contactar</a></p></div>
HTML
)
);

$admins=get_users(array('role'=>'administrator','number'=>1,'fields'=>'ID'));
$author=$admins?(int)$admins[0]:1;
$rows=array();

foreach($pages as $p){
    $words=emdo_b2b07_words($p['content']);
    if($words<650) throw new Exception($p['key'].' too short: '.$words);

    $existing=emdo_b2b07_existing($p['key'],$p['slug']);
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
    update_post_meta($id,'_emdo_seo_landing_batch','20260926-b2b-07');
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
echo wp_json_encode(array('batch'=>'20260926-b2b-07','count'=>count($rows),'pages'=>$rows),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT).PHP_EOL;
