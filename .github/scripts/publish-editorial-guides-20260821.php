<?php
/**
 * Publish the 2026-08-21 EMDO editorial buying guides in production.
 * Idempotent: existing posts are updated by native slug / editorial key.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

$marker_prefix = 'EMDO_EDITORIAL_20260821';

$author_ids = get_users( array(
    'role'    => 'administrator',
    'number'  => 1,
    'orderby' => 'ID',
    'order'   => 'ASC',
    'fields'  => 'ID',
) );
$author_id = ! empty( $author_ids ) ? (int) $author_ids[0] : 1;

function emdo_editorial_ensure_category_20260821( string $name, string $slug, string $en_name, string $en_slug, string $en_description = '' ): int {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( ! $term instanceof WP_Term ) {
        $created = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
        if ( is_wp_error( $created ) ) {
            throw new RuntimeException( 'Could not create category ' . $slug . ': ' . $created->get_error_message() );
        }
        $term = get_term( (int) $created['term_id'], 'category' );
    }
    if ( ! $term instanceof WP_Term ) {
        throw new RuntimeException( 'Category missing after create: ' . $slug );
    }
    if ( $term->name !== $name ) {
        wp_update_term( $term->term_id, 'category', array( 'name' => $name ) );
    }
    update_term_meta( $term->term_id, '_en_US_name', $en_name );
    update_term_meta( $term->term_id, '_en_US_slug', sanitize_title( $en_slug ) );
    update_term_meta( $term->term_id, '_en_US_description', $en_description );
    update_term_meta( $term->term_id, '_en_US_published', '1' );
    return (int) $term->term_id;
}

function emdo_editorial_product_category_url_20260821( string $slug, bool $english = false ): string {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) {
        return home_url( $english ? '/en/shop/' : '/tienda/' );
    }
    if ( $english ) {
        $en_slug = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
        if ( $en_slug !== '' && '1' === (string) get_term_meta( $term->term_id, '_en_US_published', true ) ) {
            return home_url( '/en/product-category/' . $en_slug . '/' );
        }
        return home_url( '/en/shop/' );
    }
    $url = get_term_link( $term );
    return is_wp_error( $url ) ? home_url( '/tienda/' ) : (string) $url;
}

function emdo_editorial_featured_image_20260821( string $product_cat_slug, int $offset = 0 ): int {
    $ids = get_posts( array(
        'post_type'      => 'product',
        'post_status'    => 'publish',
        'posts_per_page' => 12,
        'fields'         => 'ids',
        'orderby'        => 'menu_order date',
        'order'          => 'DESC',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'slug',
                'terms'    => array( $product_cat_slug ),
            ),
        ),
        'meta_query'     => array(
            array( 'key' => '_thumbnail_id', 'compare' => 'EXISTS' ),
        ),
    ) );
    if ( empty( $ids ) ) { return 0; }
    $index = min( max( 0, $offset ), count( $ids ) - 1 );
    return (int) get_post_thumbnail_id( (int) $ids[ $index ] );
}

function emdo_editorial_replace_tokens_20260821( string $html, array $tokens ): string {
    return str_replace( array_keys( $tokens ), array_values( $tokens ), $html );
}

$guide_cat = emdo_editorial_ensure_category_20260821(
    'Guías de compra',
    'guias-de-compra',
    'Buying guides',
    'buying-guides',
    'Practical guides for choosing, storing and understanding food products with greater confidence.'
);

$topic_categories = array(
    'hams'       => emdo_editorial_ensure_category_20260821( 'Jamones y paletas', 'jamones-y-paletas', 'Hams and shoulders', 'hams-and-shoulders' ),
    'oils'       => emdo_editorial_ensure_category_20260821( 'Aceites', 'aceites', 'Olive oil', 'olive-oil' ),
    'meat'       => emdo_editorial_ensure_category_20260821( 'Carnes', 'carnes', 'Meat', 'meat' ),
    'vegetables' => emdo_editorial_ensure_category_20260821( 'Hortalizas y verduras', 'hortalizas-y-verduras', 'Vegetables', 'vegetables' ),
    'pulses'     => emdo_editorial_ensure_category_20260821( 'Legumbres', 'legumbres', 'Pulses', 'pulses' ),
);

$tokens = array(
    '{{HAMS}}'           => esc_url( emdo_editorial_product_category_url_20260821( 'jamones-paletas', false ) ),
    '{{HAMS_EN}}'        => esc_url( emdo_editorial_product_category_url_20260821( 'jamones-paletas', true ) ),
    '{{OILS}}'           => esc_url( emdo_editorial_product_category_url_20260821( 'aceites', false ) ),
    '{{OILS_EN}}'        => esc_url( emdo_editorial_product_category_url_20260821( 'aceites', true ) ),
    '{{MEAT}}'           => esc_url( emdo_editorial_product_category_url_20260821( 'carnes', false ) ),
    '{{MEAT_EN}}'        => esc_url( emdo_editorial_product_category_url_20260821( 'carnes', true ) ),
    '{{VEGETABLES}}'     => esc_url( emdo_editorial_product_category_url_20260821( 'hortalizas-verduras', false ) ),
    '{{VEGETABLES_EN}}'  => esc_url( emdo_editorial_product_category_url_20260821( 'hortalizas-verduras', true ) ),
    '{{PULSES}}'         => esc_url( emdo_editorial_product_category_url_20260821( 'legumbres', false ) ),
    '{{PULSES_EN}}'      => esc_url( emdo_editorial_product_category_url_20260821( 'legumbres', true ) ),
    '{{SHOP}}'           => esc_url( home_url( '/tienda/' ) ),
    '{{SHOP_EN}}'        => esc_url( home_url( '/en/shop/' ) ),
);

$articles = array(
    array(
        'key'         => 'jamon-o-paleta',
        'slug'        => 'jamon-o-paleta-diferencias-cual-elegir',
        'en_slug'     => 'ham-or-shoulder-differences-how-to-choose',
        'title'       => 'Jamón o paleta ibérica: diferencias reales y cuál elegir',
        'en_title'    => 'Iberian ham or shoulder: the real differences and how to choose',
        'excerpt'     => 'Jamón y paleta proceden del mismo animal, pero su anatomía, proporción de hueso, infiltración y curación hacen que se comporten de forma distinta en la mesa. Esta guía explica qué cambia de verdad y cómo elegir.',
        'en_excerpt'  => 'Ham and shoulder come from the same animal, but anatomy, bone ratio, fat distribution and curing make them genuinely different on the plate. This guide explains what changes and how to choose.',
        'topic'       => 'hams',
        'product_cat' => 'jamones-paletas',
        'image_offset'=> 0,
        'content'     => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:jamon-o-paleta -->
<p>Elegir entre un jamón y una paleta ibérica no debería reducirse a pensar que uno es “mejor” que el otro. Son piezas anatómicamente distintas y esa diferencia condiciona el rendimiento, la textura, la intensidad aromática y hasta la forma en que evolucionan una vez abiertas. Entenderlo permite comprar con bastante más criterio.</p>
<h2>La diferencia empieza en la anatomía</h2>
<p>El jamón procede de las extremidades posteriores del cerdo; la paleta, de las delanteras. El jamón suele ser una pieza mayor, más alargada y con una relación entre carne y hueso más favorable. La paleta es más compacta, tiene proporcionalmente más hueso y zonas musculares más pequeñas.</p>
<p>Eso explica dos cosas importantes. Primero, que el precio por pieza no se puede comparar sin tener en cuenta el rendimiento. Segundo, que la paleta presenta más cambios de textura entre unas zonas y otras, algo que muchos aficionados valoran precisamente por su carácter.</p>
<h2>Curación, grasa e intensidad</h2>
<p>Una pieza grande necesita más tiempo para que sal, humedad y transformaciones enzimáticas avancen de forma equilibrada hasta el interior. Por eso, en términos generales, el jamón admite curaciones más largas. La paleta, al ser más pequeña y tener una anatomía distinta, suele ofrecer un perfil más intenso y directo.</p>
<p>La infiltración de grasa depende de muchos factores —raza, alimentación, ejercicio, pieza concreta y proceso—, no solo de que sea jamón o paleta. Lo correcto es fijarse en la categoría comercial completa y, en ibéricos, en la identificación reglamentaria. La Norma de Calidad del Ibérico, establecida por el Real Decreto 4/2014, regula denominaciones y precintos para evitar que conceptos distintos se mezclen.</p>
<h2>¿Cuál cunde más?</h2>
<p>Si buscas rendimiento por kilo de pieza, el jamón suele partir con ventaja porque tiene menor proporción de hueso. En una paleta, la cantidad de hueso respecto al peso total es mayor. Esto no significa que la paleta sea peor compra: significa que hay que comparar formatos equivalentes y tener claro cuánto vas a consumir.</p>
<h2>Qué elegir según el uso</h2>
<ul>
<li><strong>Para una casa con consumo frecuente:</strong> un jamón puede resultar más cómodo por duración y rendimiento.</li>
<li><strong>Para menos comensales o consumo más ocasional:</strong> una paleta reduce el tamaño de la inversión y permite terminar la pieza antes.</li>
<li><strong>Si buscas sabores más intensos:</strong> la paleta suele ofrecer sensaciones más marcadas, especialmente cerca del hueso.</li>
<li><strong>Si priorizas lonchas amplias y regulares:</strong> el jamón facilita un corte más uniforme en zonas como la maza.</li>
</ul>
<h2>La categoría importa más que el nombre de la pieza</h2>
<p>Antes de decidir, revisa siempre la denominación completa: porcentaje racial, tipo de alimentación y, cuando corresponda, precinto identificativo. “Jamón” o “paleta” describen la pieza; no explican por sí solos su categoría. Un producto bien identificado permite comparar de manera mucho más justa.</p>
<p>En nuestra selección de <a href="{{HAMS}}">jamones y paletas</a> puedes comparar ambas piezas dentro de una misma lógica de origen y productor.</p>
<h2>Productos relacionados</h2>
[products category="jamones-paletas" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
        'en_content'  => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:jamon-o-paleta -->
<p>Choosing between Iberian ham and shoulder should not be reduced to the idea that one is simply “better” than the other. They are anatomically different cuts, and that affects yield, texture, flavour intensity and even how the piece develops once opened. Understanding those differences makes buying much easier.</p>
<h2>The difference starts with anatomy</h2>
<p>Ham comes from the hind leg; shoulder comes from the front leg. A ham is normally larger and more elongated, with a more favourable meat-to-bone ratio. A shoulder is more compact, contains proportionally more bone and has smaller muscle groups.</p>
<p>This matters for two reasons. First, price per whole piece cannot be compared without considering edible yield. Second, shoulder naturally offers greater variation in texture from one area to another — something many enthusiasts appreciate because it gives the piece a more pronounced personality.</p>
<h2>Curing, fat and flavour intensity</h2>
<p>A larger cut needs more time for salt, moisture loss and enzymatic changes to progress evenly towards the centre. Ham therefore generally supports longer curing periods. Shoulder, being smaller and structurally different, often gives a more immediate and intense impression.</p>
<p>Intramuscular fat depends on many variables — breed, feeding, exercise, the individual cut and the curing process — rather than simply whether the product is ham or shoulder. For Iberian products, the full commercial designation matters. Spain’s Iberian Quality Standard, established by Royal Decree 4/2014, regulates the terms and colour seals used to identify the different categories.</p>
<h2>Which gives the better yield?</h2>
<p>If edible yield per kilogram of whole piece is the priority, ham generally has the advantage because the proportion of bone is lower. Shoulder contains more bone in relation to total weight. That does not make it worse value; it simply means the comparison has to be made on equivalent formats and expected consumption.</p>
<h2>What to choose for different situations</h2>
<ul>
<li><strong>Frequent household consumption:</strong> ham can be more practical because of its size and yield.</li>
<li><strong>Fewer diners or occasional consumption:</strong> shoulder reduces the size of the purchase and is easier to finish sooner.</li>
<li><strong>More intense flavour:</strong> shoulder often feels more concentrated, especially close to the bone.</li>
<li><strong>Wide, regular slices:</strong> ham offers larger cutting surfaces in areas such as the maza.</li>
</ul>
<h2>The category matters more than the name of the cut</h2>
<p>Always read the full designation: breed percentage, feeding system and, where applicable, the official colour seal. “Ham” and “shoulder” identify the anatomical cut; they do not describe the quality category by themselves.</p>
<p>You can compare both formats in our <a href="{{HAMS_EN}}">ham and shoulder selection</a>, where the producer and origin remain visible.</p>
<h2>Related products</h2>
[products category="jamones-paletas" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
    ),
    array(
        'key'         => 'pieza-o-loncheado',
        'slug'        => 'jamon-pieza-entera-o-loncheado-como-elegir',
        'en_slug'     => 'whole-ham-or-sliced-how-to-choose-format',
        'title'       => 'Jamón ibérico: ¿pieza entera o loncheado? Cómo elegir el formato',
        'en_title'    => 'Iberian ham: whole piece or sliced? How to choose the format',
        'excerpt'     => 'Pieza entera y loncheado no son solo dos presentaciones: cambian la conservación, el ritmo de consumo, el corte y la experiencia. Estas son las variables que conviene mirar antes de decidir.',
        'en_excerpt'  => 'Whole piece and pre-sliced ham are not merely two presentations: storage, consumption rate, slicing and serving all change. These are the variables worth checking before deciding.',
        'topic'       => 'hams',
        'product_cat' => 'jamones-paletas',
        'image_offset'=> 1,
        'content'     => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:pieza-o-loncheado -->
<p>Comprar un jamón entero o elegirlo loncheado es una decisión más práctica de lo que parece. El producto puede ser exactamente el mismo, pero el formato cambia radicalmente el ritmo de consumo, la conservación y la facilidad de servicio.</p>
<h2>La pieza entera: más control sobre el corte</h2>
<p>Una pieza permite decidir el grosor de cada loncha, aprovechar diferentes zonas y disfrutar de la evolución del jamón a medida que avanza el corte. A cambio, exige cierta técnica y un consumo suficientemente regular para evitar que la superficie permanezca demasiado tiempo expuesta.</p>
<p>La herramienta también importa: jamonero estable, cuchillo largo y bien afilado y una mínima práctica reducen desperdicio y hacen el corte mucho más seguro.</p>
<h2>Loncheado: regularidad y menos manipulación</h2>
<p>El loncheado profesional simplifica el servicio y ayuda a repartir el producto en porciones. Cuando se envasa al vacío, cada sobre permanece protegido hasta su apertura. Es una opción especialmente práctica si el consumo es esporádico, si el jamón se comparte entre varias casas o si se quiere controlar la cantidad servida en cada ocasión.</p>
<h2>La temperatura de servicio cambia la experiencia</h2>
<p>Un sobre recién sacado del frío no muestra el mismo aroma ni la misma textura que cuando la grasa ha recuperado cierta plasticidad. Lo razonable es seguir las indicaciones concretas del productor y dejar que el producto se atempere antes de servir cuando así se recomiende.</p>
<h2>¿Qué formato conserva mejor?</h2>
<p>No hay una respuesta universal. Una pieza bien mantenida conserva muy bien su interior, pero la zona de corte necesita protección y atención. El loncheado reduce la exposición hasta que se abre cada envase, aunque una vez abierto conviene consumirlo sin alargar innecesariamente el tiempo.</p>
<h2>Una regla sencilla para elegir</h2>
<ul>
<li><strong>Pieza entera:</strong> si disfrutas del corte, consumes jamón con frecuencia y quieres recorrer sus distintas zonas.</li>
<li><strong>Loncheado:</strong> si priorizas comodidad, porciones regulares y consumo espaciado.</li>
<li><strong>Deshuesado:</strong> puede ser un punto intermedio interesante para quien quiere cortar en casa sin gestionar huesos y pezuña.</li>
</ul>
<p>Lo importante es no considerar el formato como un indicador automático de calidad. Primero se elige el producto; después, la presentación que mejor encaja con la forma en que realmente se va a consumir.</p>
<p>Consulta los distintos formatos disponibles en <a href="{{HAMS}}">jamones y paletas</a>.</p>
<h2>Productos relacionados</h2>
[products category="jamones-paletas" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
        'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:pieza-o-loncheado -->
<p>Buying a whole Iberian ham or choosing it pre-sliced is a practical decision rather than a quality hierarchy. The product may be exactly the same, but the format changes consumption rate, storage, serving and the amount of skill required.</p>
<h2>Whole piece: greater control over slicing</h2>
<p>A whole piece lets you choose slice thickness, explore its different anatomical areas and experience how the ham changes as the cut progresses. In return, it requires some technique and sufficiently regular consumption so that the exposed surface is not left unattended for too long.</p>
<p>Equipment matters too: a stable ham stand, a long sharp knife and basic cutting technique reduce waste and make slicing safer.</p>
<h2>Pre-sliced: consistency and less handling</h2>
<p>Professional slicing makes serving straightforward and divides the product into manageable portions. When vacuum packed, each packet remains protected until it is opened. This format is particularly useful for occasional consumption, sharing a piece between households or controlling portions.</p>
<h2>Serving temperature matters</h2>
<p>Ham straight from a cold environment does not show the same aroma or texture as it does once the fat has regained some softness. Follow the producer’s instructions and allow the product to come towards serving temperature when recommended.</p>
<h2>Which format keeps better?</h2>
<p>There is no universal winner. A correctly kept whole ham protects its interior very effectively, but the cut surface needs care. Pre-sliced packs minimise exposure until each pack is opened; once opened, however, they should not be left unnecessarily long.</p>
<h2>A simple decision rule</h2>
<ul>
<li><strong>Whole piece:</strong> if you enjoy slicing, eat ham regularly and want to experience its different areas.</li>
<li><strong>Pre-sliced:</strong> if convenience, regular portions and spaced-out consumption matter most.</li>
<li><strong>Boneless:</strong> a useful middle ground for people who want to slice at home without managing bone and hoof.</li>
</ul>
<p>Format should not be treated as an automatic sign of quality. Choose the product first, then the presentation that fits how you will actually consume it.</p>
<p>Explore the available formats in our <a href="{{HAMS_EN}}">ham and shoulder selection</a>.</p>
<h2>Related products</h2>
[products category="jamones-paletas" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
    ),
    array(
        'key'         => 'aove-5-litros',
        'slug'        => 'aove-garrafa-5-litros-cuando-compensa-como-conservar',
        'en_slug'     => 'extra-virgin-olive-oil-5-litre-container-storage-guide',
        'title'       => 'AOVE en garrafa de 5 litros: cuándo compensa y cómo conservarlo',
        'en_title'    => 'Extra virgin olive oil in a 5-litre container: when it makes sense and how to store it',
        'excerpt'     => 'Comprar AOVE en 5 litros puede ser una decisión muy lógica si el consumo acompaña. La clave está en entender oxidación, luz, temperatura y ritmo de uso para que el ahorro de formato no perjudique al aceite.',
        'en_excerpt'  => 'Buying extra virgin olive oil in a 5-litre format can make excellent sense if consumption is high enough. The key is understanding oxygen, light, temperature and turnover so format savings do not compromise the oil.',
        'topic'       => 'oils',
        'product_cat' => 'aceites',
        'image_offset'=> 0,
        'content'     => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:aove-5-litros -->
<p>El formato de cinco litros es habitual en hogares que utilizan aceite de oliva virgen extra a diario. Su ventaja evidente es el coste por litro y la reducción de envases, pero solo tiene sentido si el aceite se consume a un ritmo que permita mantener sus características en buenas condiciones.</p>
<h2>El AOVE no mejora con el tiempo</h2>
<p>A diferencia de productos diseñados para envejecer, el aceite de oliva es un alimento cuya calidad sensorial va disminuyendo con el paso del tiempo. Oxígeno, luz y temperatura aceleran las reacciones de oxidación. Por eso la conservación debe entenderse como una carrera por proteger el aceite, no por “madurarlo”.</p>
<h2>Qué debe tener un buen envase grande</h2>
<p>La primera función del envase es limitar la luz y el contacto innecesario con el aire. Los recipientes opacos son especialmente útiles. También importa el cierre: cuanto menos tiempo permanezca abierto y cuanto menor sea la exposición repetida, mejor.</p>
<p>Si utilizas una garrafa grande, una práctica razonable es trasvasar pequeñas cantidades a una aceitera opaca y mantener el recipiente principal bien cerrado. Así se reduce el número de aperturas del envase grande.</p>
<h2>Temperatura: estabilidad antes que frío extremo</h2>
<p>El aceite debe mantenerse alejado de fuentes de calor como hornos, radiadores o zonas con sol directo. Una despensa fresca, seca y oscura suele ser un entorno mucho más sensato que una encimera junto a los fogones. No hace falta perseguir temperaturas muy bajas; interesa evitar calor y oscilaciones constantes.</p>
<h2>¿Cuándo compensa comprar 5 litros?</h2>
<p>La pregunta correcta no es cuántas personas viven en casa, sino cuánto aceite se consume. Si una garrafa va a permanecer abierta durante muchos meses con un uso mínimo, quizá un formato menor sea más coherente. Si el AOVE se emplea a diario para cocinar, aliñar y terminar platos, el formato grande puede tener mucho sentido.</p>
<h2>Variedad y fecha también cuentan</h2>
<p>Picual, arbequina, hojiblanca y otras variedades presentan perfiles distintos, pero la variedad no sustituye a una buena conservación. Revisa siempre la información del productor, la campaña cuando esté indicada y las recomendaciones del envase.</p>
<p>Puedes comparar formatos y variedades en nuestra selección de <a href="{{OILS}}">aceites</a>.</p>
<h2>Productos relacionados</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
        'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:aove-5-litros -->
<p>Five-litre formats are common in households that use extra virgin olive oil every day. The obvious advantages are price per litre and fewer containers, but the format only makes sense when the oil is consumed quickly enough to keep it in good condition.</p>
<h2>Extra virgin olive oil does not improve with age</h2>
<p>Unlike foods intentionally matured over time, olive oil gradually loses sensory freshness. Oxygen, light and heat accelerate oxidation, so storage is about protecting the oil rather than ageing it.</p>
<h2>What a good large container should do</h2>
<p>The container’s first job is to limit light and unnecessary contact with air. Opaque packaging is particularly useful. The closure matters as well: the less time the main container remains open and the fewer repeated openings it receives, the better.</p>
<p>For a large container, a sensible routine is to decant a small amount into an opaque everyday bottle while keeping the main container tightly closed. This reduces repeated exposure of the bulk oil.</p>
<h2>Temperature: stability matters more than extreme cold</h2>
<p>Keep olive oil away from ovens, radiators and direct sunlight. A cool, dry, dark cupboard is usually far more suitable than a worktop beside the hob. There is no need to chase very low temperatures; avoiding heat and repeated temperature swings is more important.</p>
<h2>When does a 5-litre format make sense?</h2>
<p>The useful question is not how many people live in the household but how much oil is actually used. If a container will stay open for many months with little turnover, a smaller format may be more appropriate. If extra virgin olive oil is used daily for cooking, dressing and finishing dishes, a larger format can be very practical.</p>
<h2>Variety and harvest information still matter</h2>
<p>Picual, Arbequina, Hojiblanca and other cultivars offer different sensory profiles, but variety never replaces good storage. Check the producer’s information, harvest details when provided and the storage advice on the container.</p>
<p>Compare formats and cultivars in our <a href="{{OILS_EN}}">olive oil selection</a>.</p>
<h2>Related products</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
    ),
    array(
        'key'         => 'carne-online',
        'slug'        => 'comprar-carne-online-que-mirar-antes-elegir-corte',
        'en_slug'     => 'buying-meat-online-what-to-check-before-choosing-a-cut',
        'title'       => 'Comprar carne online: qué mirar antes de elegir un corte',
        'en_title'    => 'Buying meat online: what to check before choosing a cut',
        'excerpt'     => 'Una buena ficha de carne debe permitir entender especie, corte, cantidad, conservación y uso culinario sin adivinar. Esta guía resume las señales que conviene revisar antes de añadir un producto al carrito.',
        'en_excerpt'  => 'A good meat listing should make species, cut, quantity, storage and culinary use clear without guesswork. This guide summarises what is worth checking before adding a product to the basket.',
        'topic'       => 'meat',
        'product_cat' => 'carnes',
        'image_offset'=> 0,
        'content'     => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:carne-online -->
<p>Comprar carne por internet funciona bien cuando la ficha del producto sustituye correctamente lo que haríamos delante de un mostrador: identificar el corte, entender la cantidad, saber cómo se entrega y decidir si encaja con la receta prevista.</p>
<h2>1. El nombre del corte debe ser preciso</h2>
<p>“Carne de vacuno” aporta muy poca información. Solomillo, entrecot, aguja, carrillera, falda, osobuco o picada tienen estructuras, contenido de tejido conjuntivo y usos muy diferentes. Un comercio serio debe llamar al corte por su nombre y explicar, cuando sea necesario, para qué preparaciones resulta adecuado.</p>
<h2>2. Peso, unidades y tolerancias</h2>
<p>Antes de comparar precios, identifica si se vende por kilo, por bandeja, por pieza o por número de unidades. En productos cortados manualmente puede existir una tolerancia razonable de peso; lo importante es que esté explicada. Comparar solo el precio final sin conocer la cantidad lleva a conclusiones equivocadas.</p>
<h2>3. Fresco, refrigerado o congelado no son sinónimos</h2>
<p>La ficha debe indicar claramente el estado en que se comercializa el producto y las instrucciones de conservación. La cadena de frío es esencial para alimentos perecederos, y el consumidor debe poder entender qué hacer al recibir el pedido. Si el productor facilita indicaciones específicas, esas instrucciones prevalecen.</p>
<h2>4. El corte correcto depende de la técnica</h2>
<p>Los cortes tiernos y con poca estructura conjuntiva admiten cocciones rápidas; otros necesitan tiempo y humedad para que el colágeno se transforme y la textura se vuelva agradable. No tiene sentido pagar más por un corte noble para una receta que se beneficia de una pieza de cocción lenta.</p>
<h2>5. Origen y productor aportan contexto</h2>
<p>Raza, sistema de producción, alimentación o maduración pueden ser datos relevantes, pero solo cuando están documentados. Conviene desconfiar de adjetivos genéricos que no vienen acompañados de información concreta. En una compra informada, la trazabilidad pesa más que una etiqueta grandilocuente.</p>
<h2>Una lista rápida antes de comprar</h2>
<ul>
<li>¿Sé exactamente qué corte estoy comprando?</li>
<li>¿Entiendo cuánto producto recibiré?</li>
<li>¿La ficha explica conservación y estado del producto?</li>
<li>¿El corte encaja con la receta?</li>
<li>¿Está claro quién lo vende y de dónde procede?</li>
</ul>
<p>Explora la categoría de <a href="{{MEAT}}">carnes</a> aplicando estos criterios a cada ficha.</p>
<h2>Productos relacionados</h2>
[products category="carnes" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
        'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:carne-online -->
<p>Buying meat online works well when the product page replaces the information you would normally gather at a butcher’s counter: identify the cut, understand the quantity, know how it is delivered and decide whether it suits the intended recipe.</p>
<h2>1. The cut should be named precisely</h2>
<p>“Beef” alone tells you very little. Fillet, ribeye, chuck, cheek, flank, shank and mince have different structures, amounts of connective tissue and culinary uses. A good listing should name the cut accurately and explain its most suitable uses when clarification is helpful.</p>
<h2>2. Weight, units and tolerances</h2>
<p>Before comparing prices, check whether the product is sold by kilogram, tray, whole piece or number of units. Hand-cut products may have a reasonable weight tolerance; what matters is that it is explained. Comparing final prices without understanding quantity is misleading.</p>
<h2>3. Fresh, chilled and frozen are not interchangeable terms</h2>
<p>The listing should clearly state the condition in which the product is sold and give storage instructions. Maintaining the cold chain is essential for perishable foods, and the customer should know what to do when the parcel arrives. Where the producer provides specific instructions, those directions should be followed.</p>
<h2>4. The right cut depends on the cooking method</h2>
<p>Tender cuts with little connective tissue suit quick cooking. Other cuts benefit from time and moisture so collagen can soften and the texture develops. Paying more for a premium quick-cooking cut does not improve a recipe that actually needs a slow-braising cut.</p>
<h2>5. Origin and producer provide useful context</h2>
<p>Breed, production system, feeding or maturation can be meaningful when documented. Generic adjectives without supporting information are less useful. For an informed purchase, traceability matters more than grand wording.</p>
<h2>A quick pre-purchase checklist</h2>
<ul>
<li>Do I know exactly which cut I am buying?</li>
<li>Do I understand how much product I will receive?</li>
<li>Are storage conditions and product state explained?</li>
<li>Does the cut suit the recipe?</li>
<li>Is the producer and origin clear?</li>
</ul>
<p>Explore our <a href="{{MEAT_EN}}">meat category</a> and apply those criteria to each product page.</p>
<h2>Related products</h2>
[products category="carnes" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
    ),
    array(
        'key'         => 'hortalizas-temporada',
        'slug'        => 'hortalizas-de-temporada-como-elegir-mejor',
        'en_slug'     => 'seasonal-vegetables-how-to-choose-better',
        'title'       => 'Hortalizas de temporada: cómo elegir mejor según el momento del año',
        'en_title'    => 'Seasonal vegetables: how to choose better throughout the year',
        'excerpt'     => 'La temporada no es un calendario rígido: depende de zona, variedad, cultivo y clima. Entender esa variabilidad ayuda a comprar hortalizas con expectativas más realistas y a aprovechar mejor cada momento.',
        'en_excerpt'  => 'Seasonality is not a rigid calendar: region, cultivar, growing system and weather all matter. Understanding that variability helps set better expectations and make more of each part of the year.',
        'topic'       => 'vegetables',
        'product_cat' => 'hortalizas-verduras',
        'image_offset'=> 0,
        'content'     => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:hortalizas-temporada -->
<p>Hablar de “producto de temporada” es útil, pero conviene evitar simplificaciones. España tiene climas muy distintos y una misma hortaliza puede tener ventanas de recolección diferentes según provincia, altitud, variedad, invernadero o cultivo al aire libre. La temporada es una guía, no una fecha exacta.</p>
<h2>Qué significa realmente que una hortaliza esté de temporada</h2>
<p>En términos prácticos, significa que su ciclo productivo encaja de forma natural con las condiciones de una zona en ese momento. Eso suele favorecer disponibilidad y permite encontrar producto recolectado en un punto adecuado de desarrollo, aunque no garantiza por sí solo sabor, frescura o calidad.</p>
<h2>La frescura se reconoce con varios indicadores</h2>
<p>No existe una regla válida para todas las hortalizas. En hojas, interesa observar turgencia y ausencia de zonas marchitas; en pimientos y berenjenas, piel firme y sin daños importantes; en raíces y tubérculos, integridad y ausencia de deterioro. El color puede orientar, pero siempre depende de la variedad.</p>
<h2>Temporada y proximidad no son lo mismo</h2>
<p>Un producto puede estar en temporada y proceder de una zona lejana; otro puede ser local y cultivarse bajo condiciones protegidas. Son conceptos distintos. Si te interesa el origen, revisa la información del productor en lugar de asumirlo a partir de la época del año.</p>
<h2>Planificar la compra reduce desperdicio</h2>
<p>Las hortalizas más delicadas deberían comprarse pensando en un consumo próximo. Otras —patatas, cebollas o determinadas calabazas, por ejemplo— toleran periodos de conservación más largos si se almacenan correctamente. La mejor compra no es la cesta más grande, sino la que se ajusta al ritmo real de la cocina.</p>
<h2>Cómo usar un calendario de temporada</h2>
<p>El calendario del Ministerio de Agricultura puede servir como referencia general, pero debe interpretarse junto con la procedencia concreta. Si un productor indica variedad, zona y momento de cosecha, esa información es más específica que cualquier tabla nacional.</p>
<p>En <a href="{{VEGETABLES}}">hortalizas y verduras</a> puedes consultar la oferta disponible en cada momento y quién está detrás de cada producto.</p>
<h2>Productos relacionados</h2>
[products category="hortalizas-verduras" limit="4" columns="4" orderby="date" order="DESC"]
HTML,
        'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:hortalizas-temporada -->
<p>“Seasonal produce” is a useful idea, but it should not be turned into a rigid calendar. Spain contains very different climates, and the same vegetable can have different harvest windows depending on province, altitude, cultivar, greenhouse production or open-field growing. Seasonality is a guide rather than an exact date.</p>
<h2>What does seasonal really mean?</h2>
<p>In practical terms, a crop is in season when its production cycle naturally fits the conditions of a given area at that time. This often supports availability and allows produce to be harvested at an appropriate stage of development, although seasonality alone does not guarantee flavour, freshness or quality.</p>
<h2>Freshness needs more than one indicator</h2>
<p>There is no single rule for every vegetable. Leafy produce should look turgid rather than wilted; peppers and aubergines should generally feel firm and show no major damage; roots and tubers should be intact and free from deterioration. Colour can help, but it always depends on the cultivar.</p>
<h2>Seasonal and local are not the same thing</h2>
<p>A product can be in season and still come from a distant growing area; another can be local but grown under protected conditions. These are different concepts. If origin matters to you, check the producer information instead of inferring it from the month.</p>
<h2>Planning purchases reduces waste</h2>
<p>Delicate vegetables are best bought with near-term consumption in mind. Others — potatoes, onions or certain squashes, for example — tolerate longer storage when kept correctly. The best purchase is not necessarily the largest basket; it is the one that matches the actual rhythm of the kitchen.</p>
<h2>How to use a seasonality calendar</h2>
<p>Spain’s Ministry of Agriculture calendar is a useful general reference, but it should be read alongside the actual place of production. If a producer gives the cultivar, area and harvest context, that information is more specific than a national chart.</p>
<p>Our <a href="{{VEGETABLES_EN}}">vegetable selection</a> shows what is currently available and the producer behind each product.</p>
<h2>Related products</h2>
[products category="hortalizas-verduras" limit="4" columns="4" orderby="date" order="DESC"]
HTML,
    ),
    array(
        'key'         => 'legumbres',
        'slug'        => 'legumbres-secas-remojo-coccion-como-elegir',
        'en_slug'     => 'dried-pulses-soaking-cooking-how-to-choose',
        'title'       => 'Legumbres secas: remojo, cocción y cómo elegir garbanzos, lentejas y alubias',
        'en_title'    => 'Dried pulses: soaking, cooking and how to choose chickpeas, lentils and beans',
        'excerpt'     => 'Garbanzos, lentejas y alubias no se cocinan todos igual. Tamaño, variedad, edad del grano, agua y técnica modifican el resultado. Una guía práctica para entender qué ocurre en la olla.',
        'en_excerpt'  => 'Chickpeas, lentils and beans do not all cook in the same way. Size, variety, age, water and technique change the result. A practical guide to what is happening in the pot.',
        'topic'       => 'pulses',
        'product_cat' => 'legumbres',
        'image_offset'=> 0,
        'content'     => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:legumbres -->
<p>Las legumbres secas parecen un producto sencillo hasta que dos paquetes aparentemente similares se comportan de forma distinta en la olla. La explicación está en la variedad, el calibre, la edad del grano, las condiciones de almacenamiento, el agua y la técnica de cocción.</p>
<h2>¿Por qué se remojan algunas legumbres?</h2>
<p>El remojo rehidrata el grano antes de la cocción y ayuda a que el agua alcance el interior de forma más homogénea. Garbanzos y muchas alubias se benefician claramente de este paso. Las lentejas pequeñas, en cambio, suelen poder cocinarse sin un remojo prolongado, aunque la recomendación concreta depende de la variedad.</p>
<h2>El tiempo de cocción nunca es una cifra absoluta</h2>
<p>Una legumbre más antigua puede tardar más en ablandarse. El agua también influye: su composición mineral modifica la velocidad con la que se suavizan las paredes celulares. Por eso dos cocinas que siguen exactamente la misma receta pueden obtener tiempos diferentes.</p>
<h2>Garbanzos, lentejas y alubias: tres comportamientos distintos</h2>
<ul>
<li><strong>Garbanzos:</strong> agradecen un remojo suficiente y una cocción estable. Conviene evitar cambios bruscos de temperatura.</li>
<li><strong>Lentejas:</strong> muchas variedades pequeñas son rápidas y no necesitan una preparación previa larga.</li>
<li><strong>Alubias:</strong> existe una enorme diversidad de tamaños y texturas; la variedad condiciona mucho el resultado final.</li>
</ul>
<h2>La variedad importa más que el color genérico</h2>
<p>Decir “alubia blanca” o “lenteja” agrupa productos muy diferentes. Si la ficha indica variedad y origen, tendremos pistas mejores sobre calibre, piel, textura y uso culinario. Esa información es especialmente útil para repetir una receta con resultados consistentes.</p>
<h2>Sal, ingredientes ácidos y punto final</h2>
<p>La cocina de legumbres admite muchas escuelas. Ingredientes ácidos como tomate o vinagre pueden afectar al ablandamiento si se incorporan demasiado pronto. En recetas donde se busca una textura muy tierna, suele ser prudente añadirlos cuando la legumbre ya está avanzada.</p>
<h2>Qué mirar al comprar</h2>
<p>Busca granos íntegros, información de variedad cuando esté disponible y un almacenamiento seco y protegido. Una legumbre no necesita una lista de adjetivos: necesita identificación clara y una buena conservación.</p>
<p>Consulta las variedades disponibles en nuestra categoría de <a href="{{PULSES}}">legumbres</a>.</p>
<h2>Productos relacionados</h2>
[products category="legumbres" limit="4" columns="4" orderby="date" order="DESC"]
HTML,
        'en_content' => <<<'HTML'
<!-- EMDO_EDITORIAL_20260821:legumbres -->
<p>Dried pulses look simple until two apparently similar packets behave differently in the pot. Variety, seed size, age, storage, water and cooking technique all affect the result.</p>
<h2>Why are some pulses soaked?</h2>
<p>Soaking rehydrates the seed before cooking and helps water reach the centre more evenly. Chickpeas and many beans benefit clearly from this step. Small lentils, by contrast, can often be cooked without a long soak, although the best method depends on the variety.</p>
<h2>Cooking time is never an absolute number</h2>
<p>Older dried pulses may take longer to soften. Water matters too: mineral composition can affect how quickly cell walls soften. Two kitchens following the same recipe can therefore obtain different cooking times.</p>
<h2>Chickpeas, lentils and beans behave differently</h2>
<ul>
<li><strong>Chickpeas:</strong> generally benefit from adequate soaking and steady cooking, without abrupt temperature changes.</li>
<li><strong>Lentils:</strong> many small varieties cook quickly and need little or no long pre-soak.</li>
<li><strong>Beans:</strong> the range of sizes and textures is enormous, so variety strongly influences the final result.</li>
</ul>
<h2>Variety tells you more than a generic colour</h2>
<p>“White bean” or “lentil” can describe many very different products. When the listing gives variety and origin, it provides better clues about size, skin, texture and culinary use. That information is especially valuable when you want consistent results from a recipe.</p>
<h2>Salt, acidic ingredients and final texture</h2>
<p>There are many legitimate schools of pulse cookery. Acidic ingredients such as tomato or vinegar can slow softening when introduced very early. In dishes where a tender texture is the objective, adding them later in the cooking process is often a sensible approach.</p>
<h2>What to look for when buying</h2>
<p>Look for intact seeds, clear variety information when available and dry, protected storage. Pulses do not need a long list of adjectives; they need clear identification and good handling.</p>
<p>See the varieties currently available in our <a href="{{PULSES_EN}}">pulse selection</a>.</p>
<h2>Related products</h2>
[products category="legumbres" limit="4" columns="4" orderby="date" order="DESC"]
HTML,
    ),
);

$report = array( 'posts' => array(), 'errors' => array() );

foreach ( $articles as $article ) {
    $existing = get_page_by_path( $article['slug'], OBJECT, 'post' );
    if ( ! $existing instanceof WP_Post ) {
        $ids = get_posts( array(
            'post_type'      => 'post',
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => '_emdo_editorial_key',
            'meta_value'     => $article['key'],
        ) );
        $existing = ! empty( $ids ) && $ids[0] instanceof WP_Post ? $ids[0] : null;
    }

    $native_content = emdo_editorial_replace_tokens_20260821( $article['content'], $tokens );
    $english_content = emdo_editorial_replace_tokens_20260821( $article['en_content'], $tokens );

    $postarr = array(
        'ID'           => $existing instanceof WP_Post ? (int) $existing->ID : 0,
        'post_type'    => 'post',
        'post_status'  => 'publish',
        'post_author'  => $author_id,
        'post_title'   => $article['title'],
        'post_name'    => $article['slug'],
        'post_excerpt' => $article['excerpt'],
        'post_content' => $native_content,
        'comment_status' => 'closed',
        'ping_status'    => 'closed',
    );

    $post_id = $existing instanceof WP_Post ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );
    if ( is_wp_error( $post_id ) ) {
        $report['errors'][] = array( 'key' => $article['key'], 'error' => $post_id->get_error_message() );
        continue;
    }
    $post_id = (int) $post_id;

    wp_set_post_categories( $post_id, array( $guide_cat, $topic_categories[ $article['topic'] ] ), false );

    update_post_meta( $post_id, '_emdo_editorial_key', $article['key'] );
    update_post_meta( $post_id, '_emdo_editorial_release', '20260821' );
    update_post_meta( $post_id, '_emdo_related_product_cat', $article['product_cat'] );
    update_post_meta( $post_id, '_en_US_post_title', $article['en_title'] );
    update_post_meta( $post_id, '_en_US_post_name', $article['en_slug'] );
    update_post_meta( $post_id, '_en_US_post_excerpt', $article['en_excerpt'] );
    update_post_meta( $post_id, '_en_US_post_content', $english_content );
    update_post_meta( $post_id, '_en_US_ready', '1' );
    update_post_meta( $post_id, '_en_US_published', '1' );
    update_post_meta( $post_id, '_en_US_updated_at', gmdate( 'c' ) );

    $image_id = emdo_editorial_featured_image_20260821( $article['product_cat'], (int) $article['image_offset'] );
    if ( $image_id > 0 ) {
        set_post_thumbnail( $post_id, $image_id );
    }

    clean_post_cache( $post_id );
    $report['posts'][] = array(
        'id'       => $post_id,
        'slug'     => $article['slug'],
        'en_slug'  => $article['en_slug'],
        'title'    => $article['title'],
        'image_id' => (int) get_post_thumbnail_id( $post_id ),
        'status'   => get_post_status( $post_id ),
    );
}

if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }
wp_cache_flush();
if ( function_exists( 'rocket_clean_domain' ) ) { rocket_clean_domain(); }
do_action( 'litespeed_purge_all' );

if ( ! empty( $report['errors'] ) || count( $report['posts'] ) !== count( $articles ) ) {
    fwrite( STDERR, wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL );
    exit( 1 );
}

echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL;
