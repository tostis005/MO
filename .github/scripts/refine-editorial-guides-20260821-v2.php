<?php
/**
 * Editorial guides revision 2: deeper expert copy + curated high-resolution imagery.
 * Requires the six posts published by publish-editorial-guides-20260821.php.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

function emdo_v2_cat_url( string $slug, bool $english = false ): string {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) { return home_url( $english ? '/en/shop/' : '/tienda/' ); }
    if ( $english ) {
        $en_slug = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
        if ( '' !== $en_slug && '1' === (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) {
            return home_url( '/en/product-category/' . $en_slug . '/' );
        }
        return home_url( '/en/shop/' );
    }
    $url = get_term_link( $term );
    return is_wp_error( $url ) ? home_url( '/tienda/' ) : (string) $url;
}

function emdo_v2_tokens( string $content ): string {
    $map = array(
        '{{HAMS}}'          => esc_url( emdo_v2_cat_url( 'jamones-paletas', false ) ),
        '{{HAMS_EN}}'       => esc_url( emdo_v2_cat_url( 'jamones-paletas', true ) ),
        '{{OILS}}'          => esc_url( emdo_v2_cat_url( 'aceites', false ) ),
        '{{OILS_EN}}'       => esc_url( emdo_v2_cat_url( 'aceites', true ) ),
        '{{MEAT}}'          => esc_url( emdo_v2_cat_url( 'carnes', false ) ),
        '{{MEAT_EN}}'       => esc_url( emdo_v2_cat_url( 'carnes', true ) ),
        '{{VEGETABLES}}'    => esc_url( emdo_v2_cat_url( 'hortalizas-verduras', false ) ),
        '{{VEGETABLES_EN}}' => esc_url( emdo_v2_cat_url( 'hortalizas-verduras', true ) ),
        '{{PULSES}}'        => esc_url( emdo_v2_cat_url( 'legumbres', false ) ),
        '{{PULSES_EN}}'     => esc_url( emdo_v2_cat_url( 'legumbres', true ) ),
    );
    return str_replace( array_keys( $map ), array_values( $map ), $content );
}

function emdo_v2_featured_image( int $post_id, array $image ): int {
    $existing = get_posts( array(
        'post_type'      => 'attachment',
        'post_status'    => 'inherit',
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'meta_key'       => '_emdo_unsplash_photo_id',
        'meta_value'     => $image['id'],
    ) );
    $attachment_id = ! empty( $existing ) ? (int) $existing[0] : 0;

    if ( $attachment_id <= 0 ) {
        $attachment_id = media_sideload_image( $image['direct'], $post_id, $image['alt_es'], 'id' );
        if ( is_wp_error( $attachment_id ) ) {
            throw new RuntimeException( 'Image sideload failed for ' . $image['id'] . ': ' . $attachment_id->get_error_message() );
        }
        $attachment_id = (int) $attachment_id;
        wp_update_post( array(
            'ID'           => $attachment_id,
            'post_title'   => $image['alt_es'],
            'post_excerpt' => 'Fotografía: ' . $image['photographer'] . ' · Unsplash.',
        ) );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $image['alt_es'] );
        update_post_meta( $attachment_id, '_emdo_unsplash_photo_id', $image['id'] );
        update_post_meta( $attachment_id, '_emdo_unsplash_page', $image['page'] );
        update_post_meta( $attachment_id, '_emdo_unsplash_photographer', $image['photographer'] );
        update_post_meta( $attachment_id, '_emdo_image_license', 'Unsplash License - free commercial and non-commercial use' );
        update_post_meta( $attachment_id, '_emdo_image_license_url', 'https://unsplash.com/license' );
    }

    set_post_thumbnail( $post_id, $attachment_id );
    return $attachment_id;
}

$images = array(
    'ham_cut' => array(
        'id'           => 'kzJdFLlgXgM',
        'direct'       => 'https://images.unsplash.com/photo-1709790028365-d49ae1e20f53?auto=format&fit=crop&w=2000&q=85',
        'page'         => 'https://unsplash.com/photos/a-close-up-of-meat-being-cut-with-a-knife-kzJdFLlgXgM',
        'photographer' => 'Jean-Jacques CHARLES',
        'alt_es'       => 'Corte de una pieza de jamón curado con cuchillo jamonero',
    ),
    'ham_slice' => array(
        'id'           => 'eqpOgjRpAHA',
        'direct'       => 'https://images.unsplash.com/photo-1732565432358-a8c95bc24ea3?auto=format&fit=crop&w=2000&q=85',
        'page'         => 'https://unsplash.com/photos/a-close-up-of-sliced-meat-on-a-plate-eqpOgjRpAHA',
        'photographer' => 'Maite Paternain',
        'alt_es'       => 'Lonchas finas de jamón curado servidas en un plato',
    ),
    'oil' => array(
        'id'           => '102NToxkJFA',
        'direct'       => 'https://images.unsplash.com/photo-1765118527220-da9c7a560b13?auto=format&fit=crop&w=2000&q=85',
        'page'         => 'https://unsplash.com/photos/olive-oil-being-poured-into-a-bowl-of-herbs-102NToxkJFA',
        'photographer' => 'Asli Dokuzeylul',
        'alt_es'       => 'Aceite de oliva vertiéndose sobre un cuenco con hierbas y especias',
    ),
    'meat' => array(
        'id'           => 'ZhGH7BX9bGY',
        'direct'       => 'https://images.unsplash.com/photo-1690983323544-026a23725551?auto=format&fit=crop&w=2000&q=85',
        'page'         => 'https://unsplash.com/photos/a-cutting-board-topped-with-raw-meat-next-to-a-knife-ZhGH7BX9bGY',
        'photographer' => 'Sergey Kotenev',
        'alt_es'       => 'Cortes de carne de vacuno cruda sobre una tabla de cocina',
    ),
    'vegetables' => array(
        'id'           => '6PFqjxsHMOU',
        'direct'       => 'https://images.unsplash.com/photo-1648090229186-6188eaefcc6a?auto=format&fit=crop&w=2000&q=85',
        'page'         => 'https://unsplash.com/photos/a-basket-filled-with-lots-of-different-types-of-vegetables-6PFqjxsHMOU',
        'photographer' => 'Annie Lang',
        'alt_es'       => 'Cesta de hortalizas recién cosechadas con tomates, pimientos y raíces',
    ),
    'pulses' => array(
        'id'           => 'sV5Va80VGrY',
        'direct'       => 'https://images.unsplash.com/photo-1708436478029-732032c5b86d?auto=format&fit=crop&w=2000&q=85',
        'page'         => 'https://unsplash.com/photos/a-person-reaching-for-some-food-in-small-bowls-sV5Va80VGrY',
        'photographer' => 'Monika Borys',
        'alt_es'       => 'Distintas variedades de lentejas secas dispuestas en cuencos',
    ),
);

$articles = array(
'jamon-o-paleta' => array(
    'image' => $images['ham_cut'],
    'title' => 'Jamón o paleta ibérica: diferencias reales y cuál elegir',
    'excerpt' => 'Jamón y paleta no son la misma pieza en dos tamaños. Anatomía, proporción de hueso, curación, corte y ritmo de consumo cambian de forma apreciable. Esta guía explica cómo interpretar esas diferencias y elegir con criterio.',
    'en_title' => 'Iberian ham or shoulder: the real differences and how to choose',
    'en_excerpt' => 'Ham and shoulder are not simply the same product in two sizes. Anatomy, bone ratio, curing, slicing and consumption rate all change. This guide explains how to read those differences and choose with confidence.',
    'content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:jamon-o-paleta -->
<p>Cuando se compara un jamón ibérico con una paleta ibérica es fácil caer en una simplificación: pensar que la paleta es, básicamente, un jamón más pequeño y que la única diferencia práctica está en el precio. En realidad, son dos piezas anatómicamente distintas y esa diferencia se nota desde el momento de la curación hasta el último corte. Cambia la forma de la pieza, la proporción de hueso, el tamaño de los músculos, la distribución de la grasa, la superficie disponible para cortar y, por supuesto, la cantidad de producto que tendremos que consumir una vez abierta.</p>
<p>Por eso no existe una respuesta universal a la pregunta “¿qué es mejor, jamón o paleta?”. Existe una respuesta más útil: <strong>qué pieza encaja mejor con el tipo de producto que buscamos, el número de personas que lo van a consumir y la forma en que queremos disfrutarlo</strong>.</p>
<h2>La diferencia empieza en la anatomía, no en la etiqueta</h2>
<p>El jamón procede de las extremidades posteriores del cerdo y la paleta de las delanteras. Esa explicación parece elemental, pero tiene muchas consecuencias. La pata trasera soporta una estructura muscular mayor y da lugar a una pieza normalmente más larga y voluminosa. La delantera es más compacta y presenta una relación distinta entre huesos, músculos y grasa.</p>
<p>En el jamón encontramos zonas amplias como la maza, que permiten obtener lonchas grandes y relativamente regulares durante buena parte del corte. La paleta ofrece superficies de corte menores y cambia de plano con más rapidez. Esto exige algo más de atención con el cuchillo, pero también crea una experiencia muy interesante para quien disfruta recorriendo zonas con texturas diferentes.</p>
<p>La anatomía explica además por qué no conviene comparar dos piezas únicamente por su precio total. Una paleta suele costar menos porque pesa menos, pero también tiene proporcionalmente más hueso. El jamón exige un desembolso mayor, aunque suele ofrecer un rendimiento comestible superior. Ninguno de esos dos datos determina por sí solo cuál es la compra más conveniente: hay que relacionarlos con el consumo real.</p>
<h2>Curación: el tamaño de la pieza cambia el ritmo</h2>
<p>Durante la elaboración de un jamón o una paleta se producen procesos físicos y bioquímicos complejos: pérdida gradual de agua, difusión de sal y transformaciones de proteínas y grasas que construyen aroma, sabor y textura. Una pieza grande no evoluciona igual que una pequeña. La distancia desde el exterior hasta las zonas más profundas es mayor y el equilibrio necesita más tiempo.</p>
<p>Por esa razón, los jamones suelen admitir procesos de curación más largos. No significa que “más meses” sean siempre sinónimo de más calidad. Una curación correcta es la que se adapta al tamaño, composición y características de cada pieza. Forzar el tiempo sin considerar esas variables puede secar en exceso determinadas zonas; quedarse corto puede dejar una evolución insuficiente.</p>
<p>En la paleta, la proximidad entre músculos, grasa y hueso genera con frecuencia una sensación aromática más intensa y directa. El jamón, sobre todo en piezas bien equilibradas, puede ofrecer una progresión más gradual entre zonas. Son perfiles distintos, no una escala en la que uno ocupe necesariamente un nivel superior.</p>
<h2>Grasa infiltrada, grasa exterior y textura</h2>
<p>En un buen ibérico la grasa no es un elemento accesorio. Interviene en la jugosidad, en el transporte de compuestos aromáticos y en la sensación de persistencia en boca. Sin embargo, conviene evitar otro tópico frecuente: no toda la grasa visible es “infiltración”. La grasa infiltrada aparece dentro del tejido muscular; la grasa exterior y la que rodea determinadas zonas cumplen otras funciones.</p>
<p>La cantidad y distribución de esa grasa dependen de la genética del animal, la alimentación, el ejercicio, la edad, la pieza concreta y el proceso de elaboración. Por eso no tiene sentido afirmar que una paleta será siempre más grasa o que un jamón será siempre más fino. Sí es cierto que la anatomía de la paleta concentra músculos de menor tamaño alrededor de huesos y grasa, y eso puede hacer que el sabor se perciba más potente en determinadas zonas.</p>
<h2>Cómo leer la denominación de un ibérico</h2>
<p>Antes de decidir entre jamón y paleta conviene resolver una cuestión aún más importante: <strong>qué categoría de ibérico estamos comprando</strong>. La normativa española distingue productos en función del porcentaje racial y del sistema de alimentación y manejo. El Real Decreto 4/2014 establece además el sistema de precintos de colores que ayuda a identificar las distintas categorías.</p>
<p>La brida negra identifica el producto de bellota 100 % ibérico; la roja, el de bellota ibérico que no es 100 % ibérico; la verde corresponde al cebo de campo ibérico y la blanca al cebo ibérico. La denominación completa de venta debe aportar más información que un simple reclamo comercial. Si queremos comparar dos piezas con criterio, lo primero es asegurarnos de que estamos comparando categorías equivalentes.</p>
<p>También conviene recordar que la expresión “pata negra” tiene un uso regulado y no debe utilizarse como sinónimo genérico de cualquier producto ibérico. El dato útil es la denominación legal completa y la información de trazabilidad que acompaña a la pieza.</p>
<h2>Rendimiento y número de comensales: la parte más práctica</h2>
<p>Para una casa en la que se consume jamón varias veces por semana, el tamaño del jamón entero puede ser una ventaja. Permite avanzar por la pieza con regularidad y aprovechar mejor sus diferentes zonas. En una vivienda con uno o dos consumidores ocasionales, esa misma cantidad puede convertirse en un problema: cuanto más lentamente se avanza, más importante resulta proteger bien la superficie de corte y mantener unas condiciones adecuadas.</p>
<p>La paleta resuelve parte de ese problema porque se termina antes. Es especialmente interesante para hogares pequeños, para quien quiere una pieza entera sin comprometerse a un consumo prolongado o para quien prefiere invertir el presupuesto en una categoría superior antes que en una pieza de mayor tamaño.</p>
<p>Si el consumo es todavía más esporádico, el dilema entre jamón y paleta puede no ser el principal. En ese caso, un formato loncheado y envasado puede encajar mejor que cualquiera de las dos piezas enteras. La mejor compra no es la más tradicional, sino la que permite disfrutar el producto en condiciones óptimas.</p>
<h2>El corte también cambia</h2>
<p>El jamón ofrece zonas amplias en las que resulta más sencillo mantener una loncha larga, fina y relativamente uniforme. La paleta tiene una geometría más exigente: aparecen huesos antes, cambia la inclinación del corte y se trabaja sobre músculos más pequeños. Para un cortador experimentado esto forma parte del atractivo; para alguien que empieza puede significar un aprovechamiento algo más difícil.</p>
<p>En ambos casos, el objetivo no debería ser obtener lonchas transparentes a cualquier precio. Interesa que tengan un grosor agradable, que incorporen una proporción equilibrada de magro y grasa y que el corte siga el plano correcto para no crear escalones. Un cuchillo bien afilado y un soporte estable son más importantes que cualquier gesto espectacular.</p>
<h2>Entonces, ¿qué elegir?</h2>
<p>Si sois varias personas, el consumo es frecuente y valoráis lonchas amplias y un mayor rendimiento de la pieza, <strong>el jamón suele ser la opción más lógica</strong>. Si sois pocos, consumís ibérico de forma más espaciada o queréis una pieza completa de menor tamaño, <strong>la paleta puede ser una compra extraordinariamente sensata</strong>. Si lo prioritario es la comodidad y la conservación por raciones, conviene plantearse directamente el loncheado.</p>
<p>Después de decidir el tipo de pieza, vuelve a mirar lo realmente importante: categoría, porcentaje racial, alimentación, productor, proceso y condiciones de conservación. En nuestra selección de <a href="{{HAMS}}">jamones y paletas</a> puedes comparar formatos y productores con esa información como punto de partida.</p>
<h2>Productos relacionados</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
<p><small>Referencia normativa: Real Decreto 4/2014, de 10 de enero, norma de calidad para la carne, el jamón, la paleta y la caña de lomo ibérico (BOE).</small></p>
HTML,
    'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:jamon-o-paleta -->
<p>When Iberian ham is compared with Iberian shoulder, the discussion is often reduced to a simple idea: shoulder is a smaller ham and the main difference is price. In practice they are anatomically different cuts, and that difference can be felt from the curing room to the final slice. Shape, bone ratio, muscle size, fat distribution, cutting surface and the amount that needs to be consumed after opening are all different.</p>
<p>That is why there is no universal answer to “which is better, ham or shoulder?”. A more useful question is <strong>which cut best suits the category you want, the number of people eating it and the way you intend to serve it</strong>.</p>
<h2>The difference begins with anatomy, not marketing</h2>
<p>Ham comes from the hind leg and shoulder from the front leg. This basic distinction has important consequences. The hind leg has larger muscle structures and normally produces a longer, heavier piece. The front leg is more compact and has a different relationship between bone, muscle and fat.</p>
<p>A ham includes broad areas such as the maza, where large, fairly regular slices can be obtained for a substantial part of the cutting process. A shoulder has smaller cutting surfaces and changes plane more quickly. It therefore demands a little more attention from the slicer, while giving enthusiasts an interesting sequence of textures and flavours.</p>
<p>Anatomy also explains why whole-piece price is a poor comparison on its own. A shoulder normally costs less because it weighs less, but proportionally it also contains more bone. A ham requires a larger initial spend but usually gives a higher edible yield. Neither fact decides value by itself: the relevant measure is how the piece fits your real consumption.</p>
<h2>Curing: size changes the pace</h2>
<p>During curing, several physical and biochemical processes take place: gradual moisture loss, salt diffusion and the transformation of proteins and fats that build aroma, flavour and texture. A large piece does not evolve at the same pace as a small one. The distance between the surface and the deepest areas is greater, so equilibrium takes more time.</p>
<p>For that reason hams generally support longer curing periods. This does not mean that more months automatically equal higher quality. Correct curing is the time and environment appropriate to the size, composition and characteristics of the individual piece. Excessive time can dry some areas too far; insufficient time can leave the piece underdeveloped.</p>
<p>Shoulder often gives a more immediate aromatic intensity because smaller muscles, fat and bone are closely interwoven. A well-balanced ham can offer a more gradual progression as the cutting moves through its different zones. These are different profiles rather than a simple quality ladder.</p>
<h2>Intramuscular fat, external fat and texture</h2>
<p>Fat is not incidental in good Iberian cured meat. It contributes to juiciness, carries aromatic compounds and influences persistence on the palate. But not every visible deposit is “marbling”. Intramuscular fat sits within muscle tissue; external and intermuscular fat play different roles.</p>
<p>Amount and distribution depend on genetics, feeding, exercise, age, the specific cut and the curing process. It is therefore misleading to claim that shoulder is always fattier or ham always more delicate. What can be said is that shoulder anatomy places smaller muscles close to bone and fat, which can make some areas taste especially intense.</p>
<h2>How to read an Iberian designation</h2>
<p>Before choosing ham or shoulder, establish something even more important: <strong>which Iberian category you are buying</strong>. Spanish rules distinguish products by breed percentage and feeding/management system. Royal Decree 4/2014 also establishes the coloured seals used to identify the principal categories.</p>
<p>Black identifies acorn-fed 100% Iberian; red, acorn-fed Iberian that is not 100%; green, free-range field-fed Iberian; and white, grain-fed Iberian. The full legal sales name tells you far more than a broad marketing phrase. Meaningful comparison starts by comparing equivalent categories.</p>
<p>The expression “pata negra” is also regulated and should not be treated as a generic synonym for every Iberian product. The useful information is the complete legal designation and the traceability attached to the piece.</p>
<h2>Yield and number of diners: the practical decision</h2>
<p>In a household where ham is eaten several times a week, the size of a whole ham can be an advantage. The piece progresses steadily and its different areas can be used efficiently. In a household with one or two occasional consumers, the same volume may become inconvenient: the slower the cutting advances, the more important it is to protect the exposed surface and maintain suitable conditions.</p>
<p>Shoulder solves part of that problem because it is finished sooner. It is particularly sensible for small households, for buyers who want the experience of a whole piece without committing to a very long consumption period, or for people who would rather spend their budget on a higher category than on a larger cut.</p>
<p>If consumption is even more occasional, the ham-versus-shoulder question may not be the most important one. Professionally sliced packs can be a better fit than either whole piece. The best purchase is the format that allows the product to be enjoyed in good condition.</p>
<h2>Slicing is different too</h2>
<p>Ham offers broad areas where it is easier to maintain long, thin and fairly regular slices. Shoulder geometry is more demanding: bones appear sooner, the cutting angle changes quickly and the muscles are smaller. An experienced slicer may enjoy this challenge; a beginner may find efficient use slightly harder.</p>
<p>In both cases, the goal is not to produce transparent slices at any cost. A pleasant thickness, a balanced proportion of lean and fat and a flat cutting plane matter more. A sharp knife and a stable stand are more valuable than any theatrical cutting technique.</p>
<h2>So which should you choose?</h2>
<p>If several people will eat it, consumption is frequent and broad slices plus higher whole-piece yield matter, <strong>ham is usually the logical choice</strong>. If the household is small, consumption is more spaced out or a smaller whole piece is preferred, <strong>shoulder can be an exceptionally sensible purchase</strong>. If convenience and portion-by-portion storage are the priorities, consider sliced formats instead.</p>
<p>Once the cut is chosen, return to the information that really defines the product: category, breed percentage, feeding, producer, process and storage. You can compare formats and producers in our <a href="{{HAMS_EN}}">ham and shoulder selection</a>.</p>
<h2>Related products</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
<p><small>Regulatory reference: Spanish Royal Decree 4/2014 on the quality standard for Iberian meat, ham, shoulder and loin.</small></p>
HTML,
),
'pieza-o-loncheado' => array(
    'image' => $images['ham_slice'],
    'title' => 'Jamón ibérico: ¿pieza entera o loncheado? Cómo elegir el formato',
    'excerpt' => 'Pieza entera y loncheado cambian mucho más que la presentación. Afectan al corte, la exposición al oxígeno, la temperatura de servicio, el ritmo de consumo y el aprovechamiento. Esta guía ayuda a elegir formato sin confundir comodidad con calidad.',
    'en_title' => 'Iberian ham: whole piece or sliced? How to choose the format',
    'en_excerpt' => 'Whole and pre-sliced ham differ in much more than presentation. Slicing, oxygen exposure, serving temperature, consumption rate and yield all change. This guide helps choose the format without confusing convenience with quality.',
    'content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:pieza-o-loncheado -->
<p>Una vez elegido el tipo de ibérico aparece una segunda decisión que a menudo se toma demasiado rápido: comprar la pieza entera o recibir el producto ya loncheado. No es una cuestión menor. El formato determina cuánto manipularemos el producto, a qué ritmo se expondrá al aire, cuánto espacio necesitaremos, qué habilidad de corte hará falta y con qué facilidad podremos servir una ración en el momento adecuado.</p>
<p>La calidad de origen puede ser exactamente la misma en ambos formatos. Un buen jamón no deja de serlo por estar loncheado y una pieza entera no se vuelve automáticamente superior por conservar la pezuña. <strong>La diferencia está en cómo queremos gestionar ese producto en casa.</strong></p>
<h2>Qué ofrece una pieza entera que no puede reproducir un sobre</h2>
<p>El atractivo de una pieza entera no es solo visual. Cortar el jamón permite recorrer sus zonas y apreciar que no todas se comportan igual. La maza ofrece una superficie amplia, con lonchas que suelen combinar magro y grasa de forma muy equilibrada. La contramaza o babilla es más estrecha y suele presentar una sensación algo más curada. Cerca de la punta y alrededor de huesos aparecen aromas y texturas diferentes.</p>
<p>Esa variación forma parte de la experiencia. Quien disfruta aprendiendo a cortar puede adaptar el grosor a cada zona, decidir qué parte servir en cada momento y reservar huesos y recortes para cocina. Además, el interior de la pieza permanece protegido hasta que el corte avanza hacia él.</p>
<p>A cambio, la pieza exige constancia. Una vez iniciada, la superficie cortada queda en contacto con el aire. No hace falta obsesionarse con consumirla en pocos días, pero sí evitar abandonarla durante largos periodos y proteger bien la zona expuesta. También se necesita un jamonero estable, cuchillos apropiados y suficiente seguridad técnica.</p>
<h2>Qué resuelve el loncheado profesional</h2>
<p>El loncheado transforma una pieza grande en raciones independientes. Cada sobre puede permanecer cerrado hasta que realmente vaya a consumirse, de modo que la exposición se concentra en una cantidad pequeña. Esto tiene mucho sentido en hogares con consumo irregular, en regalos enviados a personas que no cortan jamón o cuando se quiere distribuir una pieza entre varios domicilios.</p>
<p>También aporta regularidad. Un corte profesional bien hecho busca lonchas finas y proporcionadas, colocadas de forma que se separen con facilidad. Para muchas personas esa comodidad compensa de sobra la pérdida del ritual del cuchillo.</p>
<p>El punto importante es revisar cómo está presentado el producto. Conviene fijarse en el peso por sobre, el tipo de envasado, las instrucciones de conservación y si se especifica claramente de qué pieza procede. Un buen loncheado debe conservar la identidad del producto, no esconderla.</p>
<h2>Temperatura: un detalle que cambia por completo la percepción</h2>
<p>Uno de los errores más comunes es abrir un sobre que acaba de salir de un entorno muy frío y juzgar el producto inmediatamente. A baja temperatura la grasa está más firme y libera menos aroma. Cuando el producto alcanza una temperatura de servicio adecuada, la grasa recupera plasticidad y la percepción aromática se hace más expresiva.</p>
<p>No existe un número mágico válido para todos los casos: deben seguirse las indicaciones del productor y las condiciones concretas de conservación. Lo importante es entender el principio. <strong>Servir demasiado frío puede ocultar parte de lo que hemos pagado</strong>. En una pieza entera situada en una estancia adecuada, ese problema suele ser menor porque la temperatura es más estable.</p>
<h2>Oxígeno, luz y tiempo: qué ocurre después de abrir</h2>
<p>En una pieza entera, la zona recién cortada es la principal superficie expuesta. Conviene mantenerla protegida y evitar fuentes de calor o luz directa. En un sobre, la protección funciona mientras permanece cerrado; una vez abierto, la cantidad es pequeña y lo lógico es consumirla en un plazo corto siguiendo las recomendaciones de la etiqueta.</p>
<p>Por tanto, no tiene demasiado sentido preguntar qué formato “conserva más” sin hablar del patrón de consumo. Una familia que corta jamón casi todos los días puede gestionar magníficamente una pieza. Una persona que toma cuatro lonchas cada dos semanas probablemente conservará mejor la experiencia con sobres independientes.</p>
<h2>¿Pieza entera, deshuesada o loncheada?</h2>
<p>Entre ambos extremos existe además el formato deshuesado. Puede ser interesante para quien quiere cortar en casa con cuchillo o máquina pero no desea trabajar alrededor del hueso. Ocupa menos espacio y facilita un aprovechamiento regular, aunque pierde parte de la experiencia tradicional de la pieza montada en jamonero.</p>
<p>La elección puede resumirse así:</p>
<ul>
<li><strong>Pieza entera:</strong> para consumo frecuente, aficionados al corte y hogares que valoran recorrer las distintas zonas.</li>
<li><strong>Deshuesado:</strong> para quien quiere cortar en casa con más facilidad y necesita ahorrar espacio.</li>
<li><strong>Loncheado:</strong> para consumo ocasional, regalos prácticos, raciones controladas o máxima comodidad.</li>
</ul>
<h2>El error que conviene evitar: usar el formato como indicador de calidad</h2>
<p>La calidad se evalúa antes: categoría del ibérico, porcentaje racial, alimentación, elaboración, productor, estado de la pieza y correcta conservación. Después se decide cómo queremos recibirla. Comparar un gran jamón loncheado con una pieza entera mediocre no tiene sentido; tampoco asumir que cualquier sobre será equivalente a un buen corte recién hecho.</p>
<p>En la categoría de <a href="{{HAMS}}">jamones y paletas</a> puedes revisar los formatos disponibles y elegir primero el producto y después la presentación más adecuada a tu consumo.</p>
<h2>Productos relacionados</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
    'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:pieza-o-loncheado -->
<p>Once the type of Iberian product has been chosen, a second decision is often made too quickly: buying the whole piece or receiving it professionally sliced. This is not a minor detail. Format determines how much the product is handled, how quickly it is exposed to air, how much space is required, what slicing skill is needed and how easily a good portion can be served at the right moment.</p>
<p>The underlying quality can be exactly the same in both formats. A good ham does not stop being good because it has been sliced, and a whole leg is not automatically superior because the hoof is still attached. <strong>The real difference is how you want to manage the product at home.</strong></p>
<h2>What a whole piece offers that a packet cannot reproduce</h2>
<p>The appeal of a whole ham is not merely visual. Cutting through the piece lets you experience its different zones. The maza provides a broad surface where slices often combine lean and fat very evenly. The babilla is narrower and can feel more cured. Near the tip and around the bones, aroma and texture change again.</p>
<p>That variation is part of the pleasure. A person who enjoys slicing can adapt thickness to each area, choose what to serve at a particular moment and save bones and trimmings for cooking. The uncut interior also remains naturally protected until the cutting plane reaches it.</p>
<p>The trade-off is consistency. Once started, the cut surface is exposed to air. There is no need to rush through a whole ham in a few days, but leaving it untouched for very long periods is not ideal. The exposed area needs protection, and a stable stand, suitable knives and safe technique are essential.</p>
<h2>What professional slicing solves</h2>
<p>Pre-slicing turns one large piece into independent portions. Each pack remains closed until it is actually needed, so exposure is limited to a small quantity at a time. This works particularly well for irregular consumption, gifts to people who do not slice ham, or sharing one piece between different households.</p>
<p>It also offers consistency. Good professional slicing aims for thin, proportionate slices arranged so that they separate easily. For many people that convenience more than compensates for losing the ritual of cutting from the leg.</p>
<p>Presentation still deserves attention. Check pack weight, packaging type, storage instructions and whether the origin of the sliced product is clearly identified. Good slicing should preserve the identity of the product rather than hide it.</p>
<h2>Serving temperature can transform perception</h2>
<p>A common mistake is to open a packet straight from a very cold environment and judge it immediately. At low temperature fat is firmer and releases fewer aromatic compounds. Once the product approaches an appropriate serving temperature, the fat softens and aroma becomes much more expressive.</p>
<p>There is no single magic temperature for every product; follow the producer’s instructions and the actual storage conditions. The important principle is that <strong>serving too cold can hide some of the qualities you paid for</strong>. A whole piece kept in a suitable room is often less affected because its temperature is more stable.</p>
<h2>Oxygen, light and time after opening</h2>
<p>With a whole ham, the freshly cut face is the main exposed surface. It should be protected and kept away from heat and direct light. With a sliced pack, protection lasts while the pack remains sealed; after opening, the quantity is small and should sensibly be eaten promptly according to the label instructions.</p>
<p>It therefore makes little sense to ask which format “keeps longer” without discussing consumption pattern. A family slicing almost every day can manage a whole piece beautifully. Someone eating a few slices every two weeks is more likely to preserve the experience with separate packs.</p>
<h2>Whole, boneless or sliced?</h2>
<p>Boneless ham sits between the two extremes. It can suit people who want to slice at home with a knife or machine but do not want to work around the bone. It takes less space and gives a regular cutting format, although it loses some of the traditional whole-leg experience.</p>
<ul>
<li><strong>Whole piece:</strong> frequent consumption, people who enjoy slicing and households interested in the differences between the zones.</li>
<li><strong>Boneless:</strong> easier home slicing and more compact storage.</li>
<li><strong>Pre-sliced:</strong> occasional consumption, practical gifts, controlled portions and maximum convenience.</li>
</ul>
<h2>The mistake to avoid: treating format as a quality grade</h2>
<p>Quality is assessed first: Iberian category, breed percentage, feeding, curing, producer, condition of the piece and correct storage. Only then should format be chosen. Comparing an excellent sliced ham with a mediocre whole leg is meaningless, just as assuming every packet is equivalent to a skilled fresh slice is misleading.</p>
<p>Browse the available formats in our <a href="{{HAMS_EN}}">ham and shoulder selection</a>, choosing the product first and the presentation second.</p>
<h2>Related products</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
),
'aove-5-litros' => array(
    'image' => $images['oil'],
    'title' => 'AOVE en garrafa de 5 litros: cuándo compensa y cómo conservarlo bien',
    'excerpt' => 'El formato de 5 litros puede ser una gran compra cuando existe suficiente rotación. Esta guía explica qué deteriora un AOVE, cómo influyen oxígeno, luz y temperatura y cómo organizar el uso diario para proteger mejor el aceite.',
    'en_title' => 'Extra virgin olive oil in a 5-litre container: when it makes sense and how to store it well',
    'en_excerpt' => 'A 5-litre format can be excellent value when turnover is high enough. This guide explains what degrades extra virgin olive oil, how oxygen, light and temperature matter, and how to organise daily use to protect it.',
    'content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:aove-5-litros -->
<p>Comprar aceite de oliva virgen extra en una garrafa de cinco litros puede ser una decisión excelente, pero no porque “cuanto más grande, más rentable” sea siempre cierto. El formato funciona cuando el consumo acompaña y cuando el aceite se almacena de forma sensata. Si una garrafa permanece abierta durante meses junto a una ventana o al lado de los fogones, el ahorro inicial puede terminar jugando en contra de la calidad sensorial.</p>
<p>Para entenderlo conviene empezar por una idea esencial: <strong>el AOVE es un producto vivo desde el punto de vista químico, pero no es un alimento pensado para mejorar con el envejecimiento</strong>. Desde que se obtiene, su objetivo es conservar el mayor tiempo posible los aromas, sabores y compuestos que lo caracterizan.</p>
<h2>Qué deteriora realmente un aceite de oliva virgen extra</h2>
<p>Los tres factores domésticos que más nos interesan son oxígeno, luz y temperatura. Los tres aceleran, por mecanismos diferentes, las reacciones que van modificando los lípidos y los compuestos aromáticos. Por eso la conservación no consiste únicamente en mirar una fecha: consiste en reducir exposiciones innecesarias durante toda la vida del envase.</p>
<p>El oxígeno entra en juego cada vez que abrimos el recipiente y, sobre todo, a medida que aumenta el espacio de aire dentro de un envase parcialmente vacío. La luz puede favorecer reacciones fotooxidativas, razón por la que los envases opacos o muy protectores son especialmente adecuados. El calor acelera la velocidad de muchas reacciones químicas; colocar el aceite junto al horno o en una estantería que recibe sol directo es una mala costumbre aunque resulte cómodo.</p>
<h2>Por qué una garrafa grande puede conservarse muy bien</h2>
<p>El tamaño del envase no es el enemigo. Una garrafa grande, opaca, bien cerrada y almacenada en un lugar estable puede funcionar perfectamente. El problema aparece cuando la utilizamos como aceitera de servicio, abriéndola continuamente, dejándola cerca del calor y exponiéndola a cambios de temperatura.</p>
<p>Una estrategia muy práctica es mantener el recipiente principal en la despensa y rellenar una botella o aceitera pequeña para el uso diario. De ese modo, el envase grande se abre menos y permanece en mejores condiciones. La aceitera de uso cotidiano también debería proteger el contenido de la luz y mantenerse alejada de los fuegos.</p>
<p>Conviene además evitar trasvasar todo el contenido a recipientes decorativos de origen desconocido. Si no sabemos si cierran correctamente, si dejan pasar mucha luz o si están perfectamente limpios y secos, podemos empeorar la conservación sin obtener ninguna ventaja.</p>
<h2>¿Hace falta guardar el AOVE en la nevera?</h2>
<p>En la mayoría de los hogares no es necesario. Lo importante es un lugar fresco, oscuro y con una temperatura relativamente estable. A temperaturas bajas algunos componentes del aceite pueden cristalizar y el aspecto volverse turbio; ese fenómeno físico no significa necesariamente que el producto se haya estropeado, pero tampoco aporta una ventaja práctica para un consumo normal.</p>
<p>La despensa suele ser mejor opción que la encimera. Si en verano una cocina alcanza temperaturas elevadas durante muchas horas, conviene buscar la zona más fresca de la casa. Lo que debemos evitar especialmente son los picos de calor y la exposición continua.</p>
<h2>Cuándo compensa realmente comprar cinco litros</h2>
<p>No existe una cifra universal de personas por hogar. Dos personas que cocinan a diario con AOVE pueden gastar más que una familia que apenas lo utiliza. La forma correcta de decidir es revisar el ritmo de consumo de los últimos meses. Si compramos formatos pequeños con frecuencia, una garrafa grande puede reducir coste por litro, embalaje y número de pedidos.</p>
<p>Si, por el contrario, tardamos muchísimo en terminar una botella de 500 ml, saltar a cinco litros probablemente no tenga sentido. En ese escenario puede ser más interesante comprar menos cantidad y renovarla con mayor frecuencia, especialmente si nos gusta alternar variedades o perfiles.</p>
<h2>Variedad: Picual, Arbequina y otros perfiles</h2>
<p>El formato no debería hacernos olvidar la variedad. Una Picual suele ofrecer perfiles más intensos, con amargo y picante perceptibles y una estabilidad oxidativa generalmente elevada. Arbequina tiende a expresar perfiles más suaves y dulces en la entrada, con aromas frutados delicados. Otras variedades aportan combinaciones distintas.</p>
<p>Estas descripciones son orientativas, porque fecha de cosecha, madurez de la aceituna, zona, extracción y conservación modifican el resultado final. Dos Picuales pueden ser muy diferentes entre sí. La variedad es una pista, no una ficha sensorial completa.</p>
<h2>Filtrado y sin filtrar: qué cambia de verdad</h2>
<p>En un aceite sin filtrar pueden permanecer pequeñas partículas sólidas y microgotas de agua procedentes del proceso. Eso explica su aspecto más turbio y una sensación de “aceite recién hecho” que muchos consumidores disfrutan. Sin embargo, esa presencia de agua y sólidos también puede acelerar determinados cambios si el producto se almacena durante mucho tiempo.</p>
<p>El filtrado elimina buena parte de esas partículas y suele favorecer una mayor estabilidad durante el almacenamiento. No tiene sentido presentar una opción como universalmente superior a la otra. Si compramos un sin filtrar, es especialmente importante respetar las recomendaciones del productor y consumirlo con buena rotación.</p>
<h2>Qué mirar antes de comprar un AOVE de gran formato</h2>
<ul>
<li>Que el envase proteja bien de la luz y cierre correctamente.</li>
<li>La variedad o mezcla de variedades, cuando se indique.</li>
<li>La información del productor y la campaña o fecha de consumo preferente.</li>
<li>Si está filtrado o sin filtrar y qué recomendaciones de conservación ofrece.</li>
<li>Si nuestro consumo real justifica el volumen elegido.</li>
</ul>
<p>En nuestra selección de <a href="{{OILS}}">aceites de oliva virgen extra</a> puedes comparar formatos y variedades. Si eliges cinco litros, la clave es sencilla: buen producto, buena rotación y un lugar oscuro, fresco y estable.</p>
<h2>Productos relacionados</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
    'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:aove-5-litros -->
<p>Buying extra virgin olive oil in a five-litre container can be an excellent decision, but not because “bigger is always better value”. The format works when consumption is high enough and storage is sensible. If a large container sits open for months beside a sunny window or next to the hob, the initial saving can work against sensory quality.</p>
<p>The key idea is that <strong>extra virgin olive oil is chemically dynamic, but it is not a product designed to improve through ageing</strong>. Once produced, the objective is to preserve its aromas, flavours and characteristic compounds for as long as possible.</p>
<h2>What actually degrades extra virgin olive oil?</h2>
<p>The three household factors that matter most are oxygen, light and temperature. They accelerate different reactions that gradually modify lipids and aromatic compounds. Good storage is therefore not just about checking a date; it is about reducing unnecessary exposure throughout the life of the container.</p>
<p>Oxygen becomes relevant every time the container is opened and as the headspace increases in a partially empty vessel. Light can promote photo-oxidative reactions, which is why opaque or highly protective packaging is valuable. Heat accelerates many chemical reactions, so storing oil beside an oven or in direct sunlight is a poor habit even if it is convenient.</p>
<h2>Why a large container can keep very well</h2>
<p>Container size itself is not the problem. A large opaque container, tightly closed and stored in a stable environment can perform very well. Problems arise when the bulk container is used as the everyday serving bottle, opened repeatedly and kept close to heat.</p>
<p>A practical strategy is to keep the main container in the pantry and refill a small daily-use bottle. The large container is then opened less often. The smaller bottle should also protect the oil from light and stay away from the cooking heat source.</p>
<p>Avoid decanting the entire purchase into decorative vessels of uncertain quality. If we do not know how well they seal, how much light they transmit or whether they are perfectly clean and dry, storage can become worse rather than better.</p>
<h2>Does extra virgin olive oil need refrigeration?</h2>
<p>For most households, no. A cool, dark place with reasonably stable temperature is normally more practical. At low temperatures some oil components can crystallise and the oil may look cloudy. This physical change does not necessarily indicate spoilage, but it offers little advantage for ordinary day-to-day use.</p>
<p>A pantry is usually better than the worktop. If a kitchen becomes very hot for long periods in summer, use the coolest suitable storage area available. The main objective is to avoid sustained heat and large temperature swings.</p>
<h2>When does five litres really make sense?</h2>
<p>There is no universal number of people per household. Two people who cook with extra virgin olive oil every day may use more than a larger family that rarely uses it. The right approach is to review actual consumption over recent months. If smaller bottles are replaced frequently, a large container can reduce price per litre, packaging and the number of orders.</p>
<p>If a 500 ml bottle already lasts a very long time, jumping to five litres is unlikely to be sensible. Smaller formats may preserve freshness better for that consumption pattern and make it easier to alternate varieties and styles.</p>
<h2>Variety: Picual, Arbequina and other profiles</h2>
<p>Format should not make us forget cultivar. Picual often gives more assertive profiles with noticeable bitterness and pungency and generally high oxidative stability. Arbequina often feels softer and sweeter on entry, with delicate fruity aromas. Other cultivars bring different balances.</p>
<p>These are broad tendencies rather than guarantees. Harvest timing, fruit maturity, growing area, extraction and storage all influence the final oil. Two Picual oils can be very different. Cultivar is a useful clue, not a complete tasting note.</p>
<h2>Filtered or unfiltered: what really changes?</h2>
<p>Unfiltered oil can retain fine solid particles and tiny amounts of vegetation water. This creates the cloudy appearance and the freshly produced character that many consumers enjoy. Those remaining solids and water can also accelerate some changes during longer storage.</p>
<p>Filtering removes much of that material and generally supports greater stability. Neither style should be declared universally superior. If you choose unfiltered oil, follow the producer’s instructions carefully and plan for good turnover.</p>
<h2>What to check before buying a large-format EVOO</h2>
<ul>
<li>Packaging that protects from light and closes reliably.</li>
<li>Cultivar or blend information where provided.</li>
<li>Producer, harvest and best-before information.</li>
<li>Whether it is filtered or unfiltered and the recommended storage.</li>
<li>Whether your real consumption justifies the chosen volume.</li>
</ul>
<p>Compare formats and cultivars in our <a href="{{OILS_EN}}">extra virgin olive oil selection</a>. With five litres, the principle is simple: good oil, good turnover and a cool, dark, stable place.</p>
<h2>Related products</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
),
'carne-online' => array(
    'image' => $images['meat'],
    'title' => 'Comprar carne online: qué mirar antes de elegir un corte',
    'excerpt' => 'Comprar carne online exige sustituir la conversación del mostrador por información clara: corte, peso, estado de conservación, uso culinario, productor y entrega. Esta guía explica cómo leer una ficha de carne con criterio profesional.',
    'en_title' => 'Buying meat online: what to check before choosing a cut',
    'en_excerpt' => 'Buying meat online means replacing the butcher-counter conversation with clear information: cut, weight, condition, cooking method, producer and delivery. This guide explains how to read a meat listing properly.',
    'content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:carne-online -->
<p>Comprar carne online no debería ser un acto de fe basado en una fotografía atractiva. Una buena ficha de producto tiene que sustituir buena parte de la conversación que tendríamos en una carnicería: qué corte es, cuánto recibiremos, cómo se presenta, en qué estado se entrega, cómo debemos conservarlo y para qué técnicas de cocina resulta adecuado.</p>
<p>Cuando esa información existe, internet tiene una ventaja importante: permite comparar con calma y volver sobre los datos antes de decidir. El problema aparece cuando todos los productos se presentan con los mismos adjetivos —“premium”, “selección”, “gourmet”— pero faltan los datos que realmente ayudan a cocinar.</p>
<h2>El corte importa más que el aspecto de la foto</h2>
<p>La carne de un mismo animal reúne músculos con funciones muy distintas. Un músculo que ha trabajado poco suele presentar menos tejido conjuntivo y puede resultar tierno con una cocción rápida. Otros músculos participan mucho más en el movimiento y contienen más colágeno; necesitan tiempo, humedad o temperaturas controladas para que ese tejido se transforme y la textura se vuelva agradable.</p>
<p>Por eso no existe “el mejor corte” en abstracto. Un solomillo puede ser magnífico para una cocción breve, pero no tiene sentido pagar su precio para un guiso que se beneficia de carrillera, morcillo, aguja u otras piezas con más estructura. Del mismo modo, una pieza diseñada para cocción lenta puede quedar dura si la tratamos como un filete de plancha.</p>
<p>Antes de comprar, piensa primero en el plato y después en el corte. Esa inversión del orden habitual evita buena parte de las compras decepcionantes.</p>
<h2>Peso, número de piezas y tolerancia: leer la cantidad de verdad</h2>
<p>El precio final solo sirve para comparar cuando sabemos qué cantidad estamos recibiendo. Una ficha debería indicar si el producto se vende por peso, por bandeja, por unidad o como lote. Si hay piezas cortadas manualmente, puede existir una tolerancia razonable; lo importante es que el cliente pueda anticiparla.</p>
<p>En lotes conviene ir un paso más allá. No basta con saber que el conjunto pesa tres o cinco kilos. Es mucho más útil conocer qué cortes incluye y, si es posible, cómo se reparte aproximadamente el peso. Eso permite planificar comidas y evita que el congelador se llene de piezas que no sabemos cómo utilizar.</p>
<h2>Fresco, refrigerado, envasado o congelado: palabras que no deben mezclarse</h2>
<p>“Fresco” describe el producto de forma general, pero la información práctica debe ser más concreta. El consumidor necesita saber si recibirá carne refrigerada o congelada, si viene al vacío o en otro tipo de envase y qué instrucciones debe seguir al llegar a casa.</p>
<p>La carne es un alimento perecedero y la cadena de frío es esencial. Si el paquete llega en condiciones que generan dudas razonables —temperatura inadecuada, envase dañado, pérdida de vacío o signos de deterioro— no conviene improvisar. Hay que seguir las instrucciones del vendedor y contactar con atención al cliente cuando corresponda.</p>
<p>También es importante separar seguridad alimentaria de calidad culinaria. Un producto puede ser seguro y, sin embargo, haber perdido parte de la textura o jugosidad que buscábamos por una descongelación incorrecta o un almacenamiento demasiado largo.</p>
<h2>Qué nos dice el color y qué no nos dice</h2>
<p>El color de la carne cambia por la química de la mioglobina y por la exposición al oxígeno. Una carne envasada al vacío puede presentar tonos más oscuros o violáceos y recuperar un rojo más vivo tras entrar en contacto con el aire. Por tanto, no conviene diagnosticar calidad únicamente por una fotografía o por el color en el momento de abrir.</p>
<p>Olor, integridad del envase, fecha, temperatura y aspecto general deben considerarse conjuntamente. La información del productor y las instrucciones de conservación son mucho más fiables que una regla simplista del tipo “más rojo es siempre mejor”.</p>
<h2>Grasa y marmoleo: no todo se mide en ternura</h2>
<p>La grasa intramuscular puede contribuir a la jugosidad y al sabor, especialmente en cortes cocinados a temperaturas en las que esa grasa se funde. Pero una pieza con más grasa no es automáticamente mejor para cualquier receta. En carne picada, por ejemplo, la proporción de grasa modifica textura y jugosidad; en un guiso largo, el tejido conjuntivo tiene un papel tan importante como el marmoleo.</p>
<p>Conviene desconfiar de las descripciones que utilizan “marmoleado” como única prueba de calidad sin explicar raza, corte, maduración o uso culinario. La calidad es un conjunto de variables.</p>
<h2>Maduración: útil cuando está bien explicada</h2>
<p>En determinados cortes de vacuno, la maduración controlada permite que enzimas naturales actúen sobre estructuras musculares y puede modificar ternura y perfil aromático. Pero “madurado” tampoco es un adjetivo mágico. Importan el tiempo, el método, la temperatura, la humedad y, sobre todo, que el proceso se realice de forma controlada.</p>
<p>Si una ficha destaca una maduración concreta, debería explicarla. Un número de días sin contexto aporta menos información de la que parece.</p>
<h2>Origen, productor y trazabilidad</h2>
<p>En un marketplace de productores, saber quién prepara y envía la carne aporta contexto real. Permite entender la especialización del vendedor, consultar sus condiciones de envío y valorar información como raza o sistema de producción cuando esté documentada.</p>
<p>Eso es más útil que una colección de adjetivos. Una ficha profesional debería dar datos verificables y separar claramente hechos de lenguaje comercial. En El Mercado de Origen, los productos de terceros son preparados y expedidos por el vendedor correspondiente, mientras la plataforma facilita la compra, seguimiento y asistencia.</p>
<h2>Una lista de comprobación antes de añadir al carrito</h2>
<ul>
<li>¿Sé exactamente qué corte es y para qué lo quiero cocinar?</li>
<li>¿Entiendo el peso, el número de piezas y el formato de venta?</li>
<li>¿Está claro si se entrega refrigerado o congelado y cómo conservarlo?</li>
<li>¿La información sobre raza, maduración u origen está documentada?</li>
<li>¿Conozco las condiciones de envío del productor?</li>
</ul>
<p>Con esas preguntas resueltas, comprar carne online deja de ser una apuesta y se parece mucho más a una compra bien asesorada. Puedes aplicar estos criterios al explorar nuestra selección de <a href="{{MEAT}}">carnes</a>.</p>
<h2>Productos relacionados</h2>
[products category="carnes" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
    'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:carne-online -->
<p>Buying meat online should not be an act of faith based on an attractive photograph. A strong product page needs to replace much of the conversation we would normally have at a butcher’s counter: which cut it is, how much arrives, how it is packed, its delivery condition, how it should be stored and which cooking methods suit it.</p>
<p>When that information is present, online buying has an advantage: we can compare calmly and return to the details before deciding. The problem begins when every product is described with the same adjectives — “premium”, “selection”, “gourmet” — while the practical information required for cooking is missing.</p>
<h2>The cut matters more than the photograph</h2>
<p>Different muscles from the same animal perform very different jobs. Muscles that work less often generally contain less connective tissue and can be tender with quick cooking. Other muscles contribute heavily to movement and contain more collagen; they need time, moisture or controlled temperatures for that structure to soften.</p>
<p>There is therefore no single “best cut”. Fillet may be excellent for brief cooking, but paying fillet prices makes little sense for a braise that benefits from cheek, shank, chuck or another more structured cut. Conversely, a slow-cooking cut can feel tough when treated like a quick-fry steak.</p>
<p>Think about the dish first and choose the cut second. Reversing the usual order prevents many disappointing purchases.</p>
<h2>Weight, number of pieces and tolerance: understand the quantity</h2>
<p>Final price is only comparable when quantity is clear. A listing should state whether the product is sold by weight, tray, whole piece or as part of a bundle. Hand-cut pieces can reasonably vary in weight; what matters is that the customer can anticipate that variation.</p>
<p>Bundles need even more detail. Knowing that a box weighs three or five kilograms is useful, but knowing which cuts are included and roughly how the weight is distributed makes planning far easier and prevents a freezer full of unidentified pieces.</p>
<h2>Fresh, chilled, vacuum-packed or frozen are not interchangeable terms</h2>
<p>“Fresh” may describe the product broadly, but practical information needs to be more specific. Customers should know whether the meat will arrive chilled or frozen, whether it is vacuum-packed or packed by another method, and what to do as soon as it arrives.</p>
<p>Meat is perishable and the cold chain matters. If a parcel arrives in a condition that raises reasonable doubts — inappropriate temperature, damaged packaging, lost vacuum or signs of deterioration — do not improvise. Follow the seller’s instructions and contact customer support where appropriate.</p>
<p>Food safety and culinary quality are also separate ideas. A product can remain safe yet lose some of the texture or juiciness you expected because it was thawed badly or stored for too long.</p>
<h2>What colour tells us — and what it does not</h2>
<p>Meat colour changes with myoglobin chemistry and oxygen exposure. Vacuum-packed beef may look darker or purplish and become brighter red after exposure to air. Colour alone is therefore a poor diagnosis of quality, especially from a photograph or immediately after opening.</p>
<p>Smell, package integrity, date, temperature and overall appearance should be considered together. Producer information and storage instructions are much more reliable than simplistic rules such as “redder is always better”.</p>
<h2>Fat and marbling: tenderness is not the whole story</h2>
<p>Intramuscular fat can contribute to juiciness and flavour, particularly in cuts cooked at temperatures that allow that fat to soften. But more fat is not automatically better for every recipe. In mince, fat percentage strongly influences texture and juiciness; in a long braise, connective tissue can matter just as much as marbling.</p>
<p>Be cautious when “marbling” is used as the only proof of quality without explaining breed, cut, maturation or intended use. Quality is a combination of variables.</p>
<h2>Maturation: useful when it is explained properly</h2>
<p>For certain beef cuts, controlled ageing allows natural enzymes to act on muscle structures and can change tenderness and flavour. But “aged” is not a magic word. Time, method, temperature, humidity and process control all matter.</p>
<p>If a listing highlights a specific ageing period, it should explain the context. A number of days on its own tells less than it appears to.</p>
<h2>Origin, producer and traceability</h2>
<p>In a producer marketplace, knowing who prepares and dispatches the meat adds real context. It helps customers understand the seller’s specialisation, delivery terms and documented information such as breed or production system.</p>
<p>That is more useful than a stack of adjectives. A professional listing should provide verifiable facts and distinguish them from marketing language. At El Mercado de Origen, third-party products are prepared and dispatched by the corresponding seller, while the platform facilitates purchase, tracking and customer assistance.</p>
<h2>A checklist before adding to the basket</h2>
<ul>
<li>Do I know exactly which cut it is and how I plan to cook it?</li>
<li>Do I understand the weight, number of pieces and selling format?</li>
<li>Is it clear whether the meat arrives chilled or frozen and how to store it?</li>
<li>Are claims about breed, ageing or origin documented?</li>
<li>Do I understand the producer’s delivery conditions?</li>
</ul>
<p>Once those questions are answered, buying meat online stops being a gamble and becomes a properly informed purchase. Apply these criteria while browsing our <a href="{{MEAT_EN}}">meat selection</a>.</p>
<h2>Related products</h2>
[products category="carnes" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
),
'hortalizas-temporada' => array(
    'image' => $images['vegetables'],
    'title' => 'Hortalizas de temporada: cómo elegir mejor según el momento del año',
    'excerpt' => 'La temporada no es un calendario rígido. Zona, variedad, clima y sistema de cultivo desplazan las cosechas. Esta guía explica cómo interpretar la estacionalidad, reconocer frescura y planificar una compra de hortalizas con menos desperdicio.',
    'en_title' => 'Seasonal vegetables: how to choose better throughout the year',
    'en_excerpt' => 'Seasonality is not a rigid calendar. Region, cultivar, weather and growing system shift harvest windows. This guide explains how to interpret seasonality, recognise freshness and plan vegetables with less waste.',
    'content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:hortalizas-temporada -->
<p>Los calendarios de temporada son una herramienta estupenda, pero funcionan peor cuando se interpretan como una tabla inamovible en la que cada hortaliza “empieza” el día uno de un mes y “termina” el último día de otro. España reúne climas, altitudes y zonas de producción muy diferentes. A eso se suman variedades tempranas y tardías, cultivo protegido, campañas especialmente cálidas o frías y técnicas agronómicas que desplazan las fechas.</p>
<p>La forma más útil de hablar de temporada es entenderla como una <strong>ventana de producción especialmente coherente para una zona y un cultivo</strong>, no como una frontera exacta. Esa idea nos permite comprar mejor sin convertir el calendario en un dogma.</p>
<h2>Qué significa realmente que una hortaliza esté “de temporada”</h2>
<p>Una hortaliza está en temporada cuando las condiciones de ese momento permiten que su ciclo productivo se desarrolle de manera habitual en una determinada zona. Eso suele traducirse en más disponibilidad y, en muchos casos, en una oferta procedente de cosechas recientes. Pero la temporada no garantiza por sí sola sabor, frescura ni calidad.</p>
<p>Un tomate recolectado demasiado pronto puede estar en pleno verano y resultar decepcionante. Una col cultivada correctamente puede llegar excelente en una fecha que un calendario simplificado situaría cerca del borde de su campaña. La estacionalidad es una pista importante; el estado real del producto sigue siendo decisivo.</p>
<h2>España no tiene una única temporada</h2>
<p>La diferencia entre una huerta costera, una zona de interior a mayor altitud y un área de clima más suave puede ser considerable. Una misma variedad puede adelantarse o retrasarse varias semanas. Incluso dentro de una misma comarca, orientación, tipo de suelo y condiciones concretas del año modifican la fecha de recolección.</p>
<p>Por eso la información del productor es especialmente valiosa. Si sabemos dónde cultiva, qué variedad ofrece y en qué momento de la campaña se encuentra, tenemos un contexto mucho más preciso que el que proporciona un calendario nacional.</p>
<h2>Cómo reconocer frescura sin caer en reglas demasiado simples</h2>
<p>No todas las hortalizas se evalúan igual. En hojas como lechugas, acelgas o espinacas interesa observar turgencia, consistencia y ausencia de zonas extensas marchitas o viscosas. En pimientos y berenjenas, una piel firme y bien adherida suele ser una buena señal. En calabacines y pepinos interesa una textura consistente; en raíces, que no haya reblandecimientos o daños importantes.</p>
<p>El color ayuda, pero siempre dentro de la variedad. Un tomate morado no debe juzgarse con el mismo patrón que uno rojo; un pimiento puede ser verde, amarillo o rojo según variedad y estado de maduración. “Color intenso” no es una regla universal.</p>
<p>También hay que distinguir defectos estéticos de deterioro. Una zanahoria torcida o un tomate con forma irregular pueden ser perfectamente buenos. Golpes profundos, podredumbres, mohos o tejidos acuosos son otra cuestión.</p>
<h2>Temporada, proximidad y producción al aire libre: conceptos distintos</h2>
<p>Es frecuente utilizar estas palabras como si fueran equivalentes. No lo son. Una hortaliza puede estar en su temporada natural y recorrer una distancia considerable hasta el consumidor. Otra puede producirse muy cerca bajo un invernadero. También puede haber producto local almacenado durante un tiempo y producto de otra región cosechado muy recientemente.</p>
<p>Si el origen importa, lo correcto es leer el origen. Si queremos saber el método de cultivo, hay que buscar esa información específica. La palabra “temporada” no permite deducir automáticamente ninguna de las dos cosas.</p>
<h2>Una guía práctica por estaciones</h2>
<p><strong>Invierno</strong> suele favorecer muchas hojas, coles y raíces: acelgas, espinacas, puerros, coliflores, brócolis, repollos, nabos o determinadas zanahorias, con variaciones regionales. Son productos que encajan de forma natural en caldos, cremas, asados y guisos.</p>
<p><strong>Primavera</strong> es una época de transición. Guisantes, habas y espárragos ganan protagonismo mientras algunas verduras de invierno continúan y empiezan a aparecer productos asociados a temperaturas más suaves. Es uno de los momentos más interesantes para observar cómo cambia una huerta semana a semana.</p>
<p><strong>Verano</strong> concentra gran parte de la temporada más reconocible de tomates, pimientos, berenjenas, pepinos y calabacines en muchas zonas. Son hortalizas con las que se construyen ensaladas, gazpachos, pistos, parrilladas y conservas.</p>
<p><strong>Otoño</strong> vuelve a ser una transición: conviven los últimos productos estivales con calabazas, puerros, hojas y raíces. La disponibilidad concreta depende mucho de la zona y de cómo haya evolucionado el tiempo.</p>
<h2>Cómo planificar la compra para desperdiciar menos</h2>
<p>Comprar “de temporada” pierde buena parte de su sentido si la mitad de la cesta termina deteriorándose. Conviene separar productos de consumo inmediato de otros con mayor capacidad de almacenamiento. Hojas tiernas, hierbas y algunos frutos delicados deben entrar en el menú de los primeros días. Cebollas, patatas, ajos o determinadas calabazas admiten una planificación más larga si se conservan correctamente.</p>
<p>Una buena rutina consiste en decidir primero tres o cuatro comidas y construir la cesta alrededor de ellas. Después podemos añadir productos versátiles para completar la semana. Así evitamos comprar una gran variedad por impulso y descubrir más tarde que todos necesitan consumirse al mismo tiempo.</p>
<h2>La mejor referencia es la oferta real del productor</h2>
<p>Los calendarios del Ministerio de Agricultura son útiles para orientarse, pero cuando compramos a productores concretos existe una fuente todavía más directa: su catálogo actual. Si una huerta trabaja de forma estacional, lo que está cosechando y ofreciendo en cada momento funciona como un calendario vivo.</p>
<p>Consulta nuestra selección de <a href="{{VEGETABLES}}">hortalizas y verduras</a> y presta atención al productor, la variedad y la disponibilidad. Entender la temporada no sirve para memorizar meses; sirve para aprender a mirar mejor el producto.</p>
<h2>Productos relacionados</h2>
[products category="hortalizas-verduras" limit="6" columns="3" orderby="date" order="DESC"]
<p><small>Referencia orientativa: calendarios de hortalizas de temporada del Ministerio de Agricultura, Pesca y Alimentación.</small></p>
HTML,
    'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:hortalizas-temporada -->
<p>Seasonality calendars are excellent tools, but they become less useful when treated as rigid tables in which a vegetable begins on the first day of one month and ends on the last day of another. Spain contains very different climates, altitudes and growing regions. Early and late cultivars, protected growing, unusually warm or cold years and agronomic techniques all shift harvest dates.</p>
<p>The most useful way to understand seasonality is as a <strong>production window that is particularly coherent for a crop in a given area</strong>, rather than an exact border. That approach helps us buy better without turning a calendar into a rulebook.</p>
<h2>What does “in season” actually mean?</h2>
<p>A vegetable is in season when the conditions at that time normally suit its production cycle in a particular area. This often means greater availability and, in many cases, produce from recent harvests. But seasonality on its own does not guarantee flavour, freshness or quality.</p>
<p>A tomato harvested too early can be disappointing in the middle of summer. A well-grown cabbage can be excellent near the edge of a simplified calendar window. Seasonality is an important clue; the actual condition of the produce remains decisive.</p>
<h2>Spain does not have one single season</h2>
<p>The difference between a coastal market garden, a high inland area and a mild-climate region can be considerable. The same cultivar may move several weeks earlier or later. Even within one district, orientation, soil and the weather of a particular year change harvest timing.</p>
<p>Producer information is therefore especially valuable. Knowing where a crop is grown, which cultivar is offered and where the farm is in its harvest cycle gives much more precise context than a national calendar alone.</p>
<h2>Recognising freshness without oversimplifying</h2>
<p>Different vegetables need different criteria. Leafy produce such as lettuce, chard or spinach should generally feel turgid and show no extensive wilting or sliminess. Peppers and aubergines should normally have firm, well-attached skin. Courgettes and cucumbers should feel consistent; roots should not show serious softening or decay.</p>
<p>Colour can help, but only within the correct cultivar. A purple tomato should not be judged by the same colour standard as a red one; peppers can be green, yellow or red depending on cultivar and ripeness. “Bright colour” is not a universal rule.</p>
<p>Cosmetic imperfections should also be separated from deterioration. A twisted carrot or irregular tomato can be perfectly good. Deep bruising, rot, mould or watery tissue is a different matter.</p>
<h2>Seasonal, local and open-field are different concepts</h2>
<p>These ideas are often used as though they were interchangeable. They are not. A vegetable can be in its natural season and still travel a considerable distance. Another can be produced very locally under protected growing conditions. Local produce can also have been stored, while produce from another region may have been harvested very recently.</p>
<p>If origin matters, read the origin. If the growing method matters, look for that specific information. “Seasonal” does not allow either fact to be assumed automatically.</p>
<h2>A practical guide through the year</h2>
<p><strong>Winter</strong> commonly favours many leafy vegetables, brassicas and roots: chard, spinach, leeks, cauliflower, broccoli, cabbage, turnips and some carrots, with strong regional variation. They naturally suit soups, purées, roasting and stews.</p>
<p><strong>Spring</strong> is a transition. Peas, broad beans and asparagus gain prominence while some winter vegetables continue and crops associated with milder temperatures begin to appear. It is a particularly interesting time to watch a market garden change from week to week.</p>
<p><strong>Summer</strong> concentrates the familiar season for tomatoes, peppers, aubergines, cucumbers and courgettes in many areas. These vegetables build salads, gazpacho-style soups, stews such as pisto, grills and preserves.</p>
<p><strong>Autumn</strong> is another transition: the last summer crops overlap with squashes, leeks, leafy vegetables and roots. Exact availability depends heavily on region and the year’s weather.</p>
<h2>Planning the basket to reduce waste</h2>
<p>Buying “seasonally” loses much of its value if half the basket deteriorates. Separate immediate-use produce from vegetables that store well. Tender leaves, herbs and delicate fruits belong in the first meals of the week. Onions, potatoes, garlic and some squashes allow longer planning when stored correctly.</p>
<p>A useful routine is to decide three or four meals first and build the basket around them. Add versatile ingredients afterwards. This prevents buying a large variety on impulse and discovering that everything needs to be eaten at once.</p>
<h2>The producer’s real offer is the best live calendar</h2>
<p>Spain’s Ministry of Agriculture seasonality calendars are useful general references, but when buying from a specific grower there is an even more direct source: what that grower is actually harvesting and offering. A genuinely seasonal market garden effectively creates a living calendar through its current catalogue.</p>
<p>Explore our <a href="{{VEGETABLES_EN}}">vegetable selection</a> and pay attention to producer, cultivar and current availability. The point of understanding seasonality is not to memorise months; it is to learn to read produce better.</p>
<h2>Related products</h2>
[products category="hortalizas-verduras" limit="6" columns="3" orderby="date" order="DESC"]
<p><small>General reference: seasonal vegetable calendars published by Spain’s Ministry of Agriculture, Fisheries and Food.</small></p>
HTML,
),
'legumbres' => array(
    'image' => $images['pulses'],
    'title' => 'Legumbres secas: remojo, cocción y cómo elegir garbanzos, lentejas y alubias',
    'excerpt' => 'Las legumbres secas parecen simples hasta que dos paquetes se comportan de forma distinta. Variedad, edad del grano, agua, remojo, ingredientes ácidos y técnica de cocción influyen mucho. Una guía para entender qué ocurre realmente en la olla.',
    'en_title' => 'Dried pulses: soaking, cooking and how to choose chickpeas, lentils and beans',
    'en_excerpt' => 'Dried pulses look simple until two packets behave differently. Variety, age, water, soaking, acidic ingredients and cooking technique all matter. A guide to understanding what is really happening in the pot.',
    'content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:legumbres -->
<p>Las legumbres secas son uno de los productos más agradecidos de la despensa: duran mucho, permiten planificar comidas con antelación y, bien cocinadas, ofrecen texturas extraordinariamente diferentes según la variedad. Precisamente por parecer un alimento sencillo, a veces atribuimos todos los resultados a la receta y olvidamos que el propio grano tiene mucho que decir.</p>
<p>Dos paquetes de alubias blancas pueden necesitar tiempos distintos; un garbanzo puede quedar mantecoso y otro conservar un centro firme; unas lentejas mantienen perfectamente la forma y otras se abren con rapidez. Detrás de esas diferencias hay variedad, calibre, edad, condiciones de almacenamiento, composición del agua y técnica.</p>
<h2>Qué ocurre durante el remojo</h2>
<p>Una legumbre seca contiene poca agua. Durante el remojo empieza a rehidratarse: el agua penetra a través de la cubierta y avanza hacia el interior. Este paso reduce la diferencia entre la parte exterior y el centro antes de que llegue el calor de la cocción, por eso puede ayudar a obtener un resultado más homogéneo.</p>
<p>Garbanzos y muchas alubias suelen beneficiarse de un remojo prolongado. Las lentejas pequeñas, en cambio, pueden cocinarse sin ese paso o con remojos breves, según variedad y receta. No existe una regla única porque “lenteja” o “alubia” no describen una sola estructura.</p>
<p>El remojo también permite descartar granos dañados y revisar el producto antes de cocinar. Lo importante es utilizar un recipiente suficientemente grande: muchas legumbres aumentan considerablemente de volumen al hidratarse.</p>
<h2>La edad del grano explica muchos misterios</h2>
<p>Con el almacenamiento prolongado, algunas legumbres desarrollan lo que en ciencia de alimentos se conoce como fenómeno de endurecimiento y pueden necesitar más tiempo para ablandarse. No significa que cualquier legumbre de una campaña anterior sea mala, pero sí explica por qué un paquete que ha pasado mucho tiempo almacenado puede comportarse de forma distinta.</p>
<p>La rotación del producto y unas condiciones secas y estables ayudan a conservarlo mejor. En casa conviene proteger las legumbres de humedad, calor excesivo e insectos y mantenerlas en recipientes limpios y bien cerrados.</p>
<h2>El agua también cocina</h2>
<p>La composición mineral del agua puede influir en el ablandamiento de las paredes celulares. En aguas duras, con concentraciones elevadas de determinados minerales, algunas legumbres pueden tardar más en alcanzar una textura tierna. Esta es una de las razones por las que una receta reproducida en dos ciudades no siempre tarda lo mismo.</p>
<p>Si observamos de manera repetida que una variedad tarda mucho más de lo razonable, merece la pena considerar no solo el fuego y el tiempo, sino también la antigüedad del grano y el agua utilizada.</p>
<h2>Garbanzos: firmeza exterior y centro cremoso</h2>
<p>En un garbanzo bien cocido buscamos normalmente una piel integrada y un interior tierno, sin un núcleo harinoso o duro. Un buen remojo facilita ese resultado. Durante la cocción interesa mantener un hervor controlado, no una agitación violenta que rompa la piel innecesariamente.</p>
<p>El calibre influye en el tiempo, pero no determina por sí solo la calidad. Variedades pequeñas y grandes pueden ser excelentes. Lo importante es que el tamaño sea relativamente uniforme dentro del lote, porque así los granos evolucionan a un ritmo parecido.</p>
<h2>Lentejas: por qué son las más fáciles para improvisar</h2>
<p>Muchas lentejas tienen una cubierta fina y un tamaño pequeño, de modo que el agua y el calor alcanzan el interior con rapidez. Por eso algunas variedades pueden ir directamente a la olla y resolver una comida sin planificación del día anterior.</p>
<p>Pero esa rapidez también exige atención. Si queremos una lenteja que conserve forma para una ensalada, conviene controlar el punto y elegir una variedad adecuada. Para un guiso en el que buscamos más cuerpo y espesamiento, una ligera apertura del grano puede ser perfectamente deseable.</p>
<h2>Alubias: un universo demasiado amplio para meterlo en una sola categoría</h2>
<p>Alubia blanca, pinta, roja, negra, judión, fabes y muchas variedades locales presentan tamaños, pieles y contenidos de almidón diferentes. Esa diversidad explica por qué algunas son especialmente cremosas y otras mantienen mejor la estructura.</p>
<p>Cuando la ficha del producto identifica variedad y origen, esa información es mucho más útil que limitarse al color. Nos permite repetir una receta con mayor consistencia y entender por qué una alubia concreta funciona tan bien en un determinado guiso.</p>
<h2>Sal e ingredientes ácidos: separar mitos de efectos reales</h2>
<p>Durante años se ha repetido que la sal “endurece” siempre las legumbres. La realidad culinaria es más matizada y muchas técnicas incorporan sal durante la cocción sin impedir que el grano se ablande. Lo que sí puede retrasar claramente el ablandamiento son medios muy ácidos.</p>
<p>Tomate, vinagre, vino u otros ingredientes ácidos pueden incorporarse más tarde si el objetivo es conseguir una legumbre muy tierna y estamos teniendo problemas de cocción. No es una prohibición: es una herramienta para controlar el proceso. En una receta bien conocida, el orden puede ajustarse para buscar textura y sabor a la vez.</p>
<h2>Olla convencional u olla a presión</h2>
<p>La olla a presión reduce notablemente el tiempo porque cocina a una temperatura superior a la ebullición normal. Es excelente para garbanzos y alubias cuando se domina el punto, pero deja menos margen para comprobar textura durante el proceso. En olla convencional el cocinero puede observar y ajustar con mayor facilidad.</p>
<p>Ningún método es intrínsecamente mejor. La presión aporta eficiencia; la cocción lenta aporta control visual y una evolución más fácil de seguir. En ambos casos conviene respetar las instrucciones de seguridad del equipo y adaptar el tiempo a la variedad real.</p>
<h2>Qué mirar al comprar legumbres secas</h2>
<ul>
<li>Variedad y origen, cuando estén disponibles.</li>
<li>Granos relativamente uniformes e íntegros.</li>
<li>Ausencia de humedad, polvo excesivo o señales de plagas.</li>
<li>Un envase que proteja correctamente el producto.</li>
<li>Instrucciones específicas del productor si la variedad las requiere.</li>
</ul>
<p>Una buena legumbre no necesita demasiados adjetivos: necesita identidad, conservación correcta y una cocina que entienda sus tiempos. Puedes consultar las variedades disponibles en nuestra categoría de <a href="{{PULSES}}">legumbres</a>.</p>
<h2>Productos relacionados</h2>
[products category="legumbres" limit="5" columns="3" orderby="date" order="DESC"]
HTML,
    'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_REV2:legumbres -->
<p>Dried pulses are among the most useful foods in the pantry: they keep well, make meal planning easy and, when cooked carefully, offer remarkably different textures from one variety to another. Because they look simple, we sometimes attribute every result to the recipe and forget how much the seed itself contributes.</p>
<p>Two packets of white beans can need different cooking times; one chickpea can become creamy while another keeps a firm centre; some lentils hold their shape beautifully while others open quickly. Variety, size, age, storage, water composition and technique all sit behind those differences.</p>
<h2>What happens during soaking?</h2>
<p>A dried pulse contains little water. During soaking it begins to rehydrate: water crosses the seed coat and gradually reaches the centre. This reduces the difference between the outside and inside before cooking begins and can therefore support more even softening.</p>
<p>Chickpeas and many beans generally benefit from a long soak. Small lentils can often be cooked without it or with only brief soaking, depending on variety and recipe. There is no single rule because “lentil” and “bean” each describe many different structures.</p>
<p>Soaking also provides a convenient moment to inspect the pulses and remove damaged seeds. Use a container with plenty of space because many pulses increase substantially in volume as they hydrate.</p>
<h2>The age of the seed explains many mysteries</h2>
<p>During prolonged storage some pulses develop what food science calls hard-to-cook characteristics and may need much longer to soften. This does not mean that every pulse from an earlier harvest is poor, but it does explain why a packet stored for a long time can behave differently.</p>
<p>Good stock rotation and dry, stable storage conditions help. At home, protect dried pulses from moisture, excessive heat and insects, using clean containers that close properly.</p>
<h2>Water cooks too</h2>
<p>Mineral composition can influence the softening of plant cell walls. Hard water containing higher levels of certain minerals can make some pulses take longer to become tender. This is one reason why the same recipe can produce different cooking times in two cities.</p>
<p>If a variety repeatedly takes far longer than expected, consider not only heat and timing but also the age of the pulses and the water being used.</p>
<h2>Chickpeas: a tender centre with structure outside</h2>
<p>In a well-cooked chickpea we normally want an integrated skin and a soft centre without a chalky or hard core. Adequate soaking helps reach that point. During cooking, a controlled simmer is usually more useful than violent agitation that unnecessarily damages the skins.</p>
<p>Seed size influences time but does not determine quality on its own. Small and large varieties can both be excellent. Relative uniformity within a batch is useful because the seeds then progress at a similar rate.</p>
<h2>Lentils: why they are so useful for unplanned meals</h2>
<p>Many lentils have thin coats and small size, allowing water and heat to reach the centre quickly. Some varieties can therefore go straight into the pot and solve a meal without planning the day before.</p>
<p>That speed also requires attention. If the goal is a lentil that keeps its shape for a salad, choose an appropriate variety and watch the cooking point. For a stew where body and thickening are desirable, some breakdown can be exactly what the dish needs.</p>
<h2>Beans: far too diverse for one generic category</h2>
<p>White, pinto, red and black beans, giant beans, fabes and many local varieties differ in size, skin and starch characteristics. That diversity explains why some become especially creamy while others maintain their structure.</p>
<p>When a product page identifies variety and origin, that information is far more useful than colour alone. It helps reproduce recipes consistently and explains why a specific bean can be particularly well suited to a particular stew.</p>
<h2>Salt and acidic ingredients: separating myth from useful technique</h2>
<p>For years people have repeated that salt always “hardens” beans. Cooking practice is more nuanced, and many methods include salt during cooking without preventing softening. Strongly acidic environments, however, can clearly slow softening.</p>
<p>Tomato, vinegar, wine and other acidic ingredients can be added later if very tender pulses are the objective and cooking is proving slow. This is not a prohibition; it is a tool for controlling texture. In a familiar recipe, timing can be adjusted to balance both flavour and tenderness.</p>
<h2>Conventional pot or pressure cooker?</h2>
<p>A pressure cooker greatly reduces time by cooking above the normal boiling temperature. It is excellent for chickpeas and beans once the timing is understood, although it provides less opportunity to check texture during cooking. A conventional pot makes observation and adjustment easier.</p>
<p>Neither method is inherently superior. Pressure offers efficiency; slower cooking offers visual control. In both cases follow equipment safety instructions and adapt timing to the actual variety being cooked.</p>
<h2>What to look for when buying dried pulses</h2>
<ul>
<li>Variety and origin where available.</li>
<li>Reasonably uniform, intact seeds.</li>
<li>No moisture, excessive dust or signs of pests.</li>
<li>Packaging that protects the product properly.</li>
<li>Producer-specific instructions where a variety needs them.</li>
</ul>
<p>Good pulses do not need a long list of adjectives. They need identity, correct storage and cooking that respects their individual behaviour. See the varieties currently available in our <a href="{{PULSES_EN}}">pulse selection</a>.</p>
<h2>Related products</h2>
[products category="legumbres" limit="5" columns="3" orderby="date" order="DESC"]
HTML,
),
);

$report = array( 'revision' => 2, 'posts' => array(), 'errors' => array() );

foreach ( $articles as $key => $data ) {
    $posts = get_posts( array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => 2,
        'meta_key'       => '_emdo_editorial_key',
        'meta_value'     => $key,
    ) );
    if ( 1 !== count( $posts ) || ! $posts[0] instanceof WP_Post ) {
        $report['errors'][] = array( 'key' => $key, 'error' => 'Expected exactly one published editorial post.' );
        continue;
    }

    $post_id = (int) $posts[0]->ID;
    $content = emdo_v2_tokens( $data['content'] );
    $en_content = emdo_v2_tokens( $data['en_content'] );

    $updated = wp_update_post( array(
        'ID'           => $post_id,
        'post_title'   => $data['title'],
        'post_excerpt' => $data['excerpt'],
        'post_content' => $content,
        'post_status'  => 'publish',
    ), true );
    if ( is_wp_error( $updated ) ) {
        $report['errors'][] = array( 'key' => $key, 'error' => $updated->get_error_message() );
        continue;
    }

    update_post_meta( $post_id, '_en_US_post_title', $data['en_title'] );
    update_post_meta( $post_id, '_en_US_post_excerpt', $data['en_excerpt'] );
    update_post_meta( $post_id, '_en_US_post_content', $en_content );
    update_post_meta( $post_id, '_en_US_ready', '1' );
    update_post_meta( $post_id, '_en_US_published', '1' );
    update_post_meta( $post_id, '_en_US_updated_at', gmdate( 'c' ) );
    update_post_meta( $post_id, '_emdo_editorial_revision', '2' );
    update_post_meta( $post_id, '_emdo_editorial_revision_at', gmdate( 'c' ) );

    try {
        $attachment_id = emdo_v2_featured_image( $post_id, $data['image'] );
    } catch ( Throwable $e ) {
        $report['errors'][] = array( 'key' => $key, 'error' => $e->getMessage() );
        continue;
    }

    $metadata = wp_get_attachment_metadata( $attachment_id );
    $report['posts'][] = array(
        'id'       => $post_id,
        'key'      => $key,
        'title'    => get_the_title( $post_id ),
        'words_es' => str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) ),
        'words_en' => str_word_count( wp_strip_all_tags( strip_shortcodes( $en_content ) ) ),
        'image_id' => $attachment_id,
        'image_w'  => is_array( $metadata ) ? (int) ( $metadata['width'] ?? 0 ) : 0,
        'image_h'  => is_array( $metadata ) ? (int) ( $metadata['height'] ?? 0 ) : 0,
        'image_src'=> $data['image']['page'],
    );
    clean_post_cache( $post_id );
}

wp_cache_flush();
if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
do_action( 'litespeed_purge_all' );

if ( ! empty( $report['errors'] ) || count( $report['posts'] ) !== 6 ) {
    fwrite( STDERR, wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL );
    exit( 1 );
}

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL;
