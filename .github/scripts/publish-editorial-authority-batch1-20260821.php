<?php
/**
 * Publish editorial authority batch 1: five long-form bilingual articles.
 * Idempotent by _emdo_authority_key / native slug.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$author_ids = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ID' ) );
$author_id  = ! empty( $author_ids ) ? (int) $author_ids[0] : 1;

function emdo_ab1_category( string $name, string $slug, string $en_name, string $en_slug ): int {
    $term = get_term_by( 'slug', $slug, 'category' );
    if ( ! $term instanceof WP_Term ) {
        $created = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
        if ( is_wp_error( $created ) ) { throw new RuntimeException( $created->get_error_message() ); }
        $term = get_term( (int) $created['term_id'], 'category' );
    }
    if ( ! $term instanceof WP_Term ) { throw new RuntimeException( 'Missing category: ' . $slug ); }
    update_term_meta( $term->term_id, '_en_US_name', $en_name );
    update_term_meta( $term->term_id, '_en_US_slug', sanitize_title( $en_slug ) );
    update_term_meta( $term->term_id, '_en_US_published', '1' );
    return (int) $term->term_id;
}

function emdo_ab1_product_cat_url( string $slug, bool $en = false ): string {
    $term = get_term_by( 'slug', $slug, 'product_cat' );
    if ( ! $term instanceof WP_Term ) { return home_url( $en ? '/en/shop/' : '/tienda/' ); }
    if ( $en ) {
        $en_slug = sanitize_title( (string) get_term_meta( $term->term_id, '_en_US_slug', true ) );
        return $en_slug && '1' === (string) get_term_meta( $term->term_id, '_en_US_published', true )
            ? home_url( '/en/product-category/' . $en_slug . '/' )
            : home_url( '/en/shop/' );
    }
    $url = get_term_link( $term );
    return is_wp_error( $url ) ? home_url( '/tienda/' ) : (string) $url;
}

function emdo_ab1_image( int $post_id, array $img ): int {
    $ids = get_posts( array(
        'post_type' => 'attachment', 'post_status' => 'inherit', 'posts_per_page' => 1, 'fields' => 'ids',
        'meta_key' => '_emdo_pexels_photo_id', 'meta_value' => $img['id'],
    ) );
    $attachment_id = ! empty( $ids ) ? (int) $ids[0] : 0;
    if ( $attachment_id <= 0 ) {
        $attachment_id = media_sideload_image( $img['direct'], $post_id, $img['alt_es'], 'id' );
        if ( is_wp_error( $attachment_id ) ) {
            throw new RuntimeException( 'Image ' . $img['id'] . ': ' . $attachment_id->get_error_message() );
        }
        $attachment_id = (int) $attachment_id;
        wp_update_post( array(
            'ID' => $attachment_id,
            'post_title' => $img['alt_es'],
            'post_excerpt' => 'Fotografía: ' . $img['photographer'] . ' · Pexels.',
        ) );
        update_post_meta( $attachment_id, '_wp_attachment_image_alt', $img['alt_es'] );
        update_post_meta( $attachment_id, '_emdo_pexels_photo_id', $img['id'] );
        update_post_meta( $attachment_id, '_emdo_pexels_page', $img['page'] );
        update_post_meta( $attachment_id, '_emdo_pexels_photographer', $img['photographer'] );
        update_post_meta( $attachment_id, '_emdo_image_license', 'Pexels License - free personal and commercial use' );
        update_post_meta( $attachment_id, '_emdo_image_license_url', 'https://help.pexels.com/hc/en-us/articles/360042295174-What-is-the-license-of-the-photos-and-videos-on-Pexels' );
    }
    set_post_thumbnail( $post_id, $attachment_id );
    return $attachment_id;
}

function emdo_ab1_words( string $html ): int {
    $plain = wp_strip_all_tags( strip_shortcodes( $html ) );
    preg_match_all( "/[\\p{L}\\p{M}]+(?:[’'’-][\\p{L}\\p{M}]+)*/u", $plain, $m );
    return count( $m[0] );
}

function emdo_ab1_post_id( string $key, string $slug ): int {
    $ids = get_posts( array(
        'post_type' => 'post', 'post_status' => array( 'publish','draft','pending','future','private' ),
        'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_emdo_authority_key', 'meta_value' => $key,
    ) );
    if ( ! empty( $ids ) ) { return (int) $ids[0]; }
    $post = get_page_by_path( $slug, OBJECT, 'post' );
    return $post instanceof WP_Post ? (int) $post->ID : 0;
}

$guide_cat = emdo_ab1_category( 'Guías de compra', 'guias-de-compra', 'Buying guides', 'buying-guides' );
$cats = array(
    'hams' => emdo_ab1_category( 'Jamones y paletas', 'jamones-y-paletas', 'Hams and shoulders', 'hams-and-shoulders' ),
    'cured' => emdo_ab1_category( 'Embutidos y curados', 'embutidos-y-curados', 'Cured meats', 'cured-meats' ),
    'oil' => emdo_ab1_category( 'Aceites', 'aceites', 'Olive oil', 'olive-oil' ),
);

$urls = array(
    'hams' => esc_url( emdo_ab1_product_cat_url( 'jamones-paletas', false ) ),
    'hams_en' => esc_url( emdo_ab1_product_cat_url( 'jamones-paletas', true ) ),
    'cured' => esc_url( emdo_ab1_product_cat_url( 'embutidos-y-curados', false ) ),
    'cured_en' => esc_url( emdo_ab1_product_cat_url( 'embutidos-y-curados', true ) ),
    'oil' => esc_url( emdo_ab1_product_cat_url( 'aceites', false ) ),
    'oil_en' => esc_url( emdo_ab1_product_cat_url( 'aceites', true ) ),
);

$images = array(
    'seals' => array(
        'id' => '4871277',
        'direct' => 'https://images.pexels.com/photos/4871277/pexels-photo-4871277.jpeg?auto=compress&cs=tinysrgb&w=2400',
        'page' => 'https://www.pexels.com/photo/photo-of-a-glass-of-wine-beside-jamon-4871277/',
        'photographer' => 'ROMAN ODINTSOV',
        'alt_es' => 'Pieza de jamón curado en jamonero junto a una copa de vino',
    ),
    'storage' => array(
        'id' => '4871265',
        'direct' => 'https://images.pexels.com/photos/4871265/pexels-photo-4871265.jpeg?auto=compress&cs=tinysrgb&w=2400',
        'page' => 'https://www.pexels.com/photo/close-up-of-ham-on-a-cutting-board-4871265/',
        'photographer' => 'ROMAN ODINTSOV',
        'alt_es' => 'Lonchas de jamón curado recién cortadas sobre una tabla de madera',
    ),
    'cured' => array(
        'id' => '4639364',
        'direct' => 'https://images.pexels.com/photos/4639364/pexels-photo-4639364.jpeg?auto=compress&cs=tinysrgb&w=2400',
        'page' => 'https://www.pexels.com/photo/delicious-smoked-sausages-sliced-on-cutting-board-4639364/',
        'photographer' => 'Milan',
        'alt_es' => 'Embutidos curados cortados sobre una tabla de madera',
    ),
    'varieties' => array(
        'id' => '4109913',
        'direct' => 'https://images.pexels.com/photos/4109913/pexels-photo-4109913.jpeg?auto=compress&cs=tinysrgb&w=2400',
        'page' => 'https://www.pexels.com/photo/photo-of-ceramic-bowl-on-top-of-wooden-chopping-board-4109913/',
        'photographer' => 'Polina Tankilevitch',
        'alt_es' => 'Aceitunas, pan y aceite de oliva en una composición gastronómica',
    ),
    'evoo' => array(
        'id' => '36183155',
        'direct' => 'https://images.pexels.com/photos/36183155/pexels-photo-36183155.jpeg?auto=compress&cs=tinysrgb&w=2400',
        'page' => 'https://www.pexels.com/photo/olive-oil-bottle-on-rustic-wooden-table-36183155/',
        'photographer' => 'Valeria Boltneva',
        'alt_es' => 'Botella de aceite de oliva sobre una mesa rústica de madera',
    ),
);

$boe_iberico = 'https://www.boe.es/eli/es/rd/2014/01/10/4/con';
$mapa_update = 'https://www.mapa.gob.es/es/prensa/ultimas-noticias/detalle_noticias/el-ministerio-de-agricultura--pesca-y-alimentaci-n-y-el-sector-del-ib-rico-avanzan-en-los-trabajos-para-actualizar-la-norma-de-calidad/ec10609e-f182-4ef6-bbe0-e1a8dba6e8f8';
$boe_derivados = 'https://www.boe.es/eli/es/rd/2014/06/13/474/con';
$olive_varieties = 'https://www.aceitesdeolivadeespana.com/la-importancia-de-las-variedades/';
$eu_olive = 'https://agriculture.ec.europa.eu/document/download/cb848d45-397b-4266-ac32-3e2e4394f9cd_en?filename=factsheet-olive-oil_en.pdf';

$articles = array(
array(
'key'=>'iberico-seals','slug'=>'brida-negra-roja-verde-blanca-jamon-iberico','en_slug'=>'black-red-green-white-seal-iberian-ham','topic'=>'hams','image'=>$images['seals'],
'title'=>'Brida negra, roja, verde o blanca: qué significa cada etiqueta del jamón ibérico',
'en_title'=>'Black, red, green or white seal: what each Iberian ham label really means',
'excerpt'=>'Los colores del precinto del ibérico no son un recurso de marketing: resumen una denominación legal que combina alimentación, manejo y, en determinados casos, porcentaje racial. Esta guía explica cómo leerlos sin confundir conceptos.',
'en_excerpt'=>'The colour seal on Iberian ham is not a marketing device: it condenses a legal designation covering feeding, management and, in some cases, breed percentage. This guide explains how to read it correctly.',
'content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:iberico-seals -->
<p>En una pieza de jamón o paleta ibérica, la brida de color es una de las pistas más útiles para entender qué estamos comprando. Pero también es una de las más malinterpretadas. Se suele hablar de “etiqueta negra”, “roja”, “verde” o “blanca” como si los colores fueran una clasificación subjetiva de mejor a peor. No lo son. Forman parte del sistema de identificación establecido por la Norma de Calidad del Ibérico y cada color corresponde a una denominación de venta concreta.</p>
<p>La clave para leer bien un ibérico es separar tres ideas que a menudo se mezclan: <strong>la raza del animal, su alimentación y manejo, y la pieza que tenemos delante</strong>. El precinto resume parte de esa información, pero no sustituye a la denominación completa. Dos jamones pueden llevar colores distintos por el sistema de alimentación; dos piezas del mismo color pueden tener porcentajes raciales diferentes. Leer el conjunto evita comprar guiándonos por palabras aisladas.</p>
<h2>Qué regula realmente el sistema de precintos</h2>
<p>El Real Decreto 4/2014 establece la norma de calidad para la carne, el jamón, la paleta y la caña de lomo ibérico. Para jamones y paletas exige un precinto inviolable, con numeración individual, asignado de acuerdo con la categoría a la que pertenece la pieza. Ese número se integra en el sistema de trazabilidad y permite relacionar la pieza con el lote del que procede.</p>
<p>El color, por tanto, no se coloca porque el productor considere que un jamón es “premium”. Responde a una categoría definida. Es una diferencia importante: la calidad sensorial final depende además de materia prima, manejo, salazón, secado, maduración y criterio del elaborador; el precinto identifica las condiciones reglamentarias bajo las que se comercializa el producto.</p>
<h2>Brida negra: de bellota 100 % ibérico</h2>
<p>El precinto negro corresponde exclusivamente a la denominación <strong>“de bellota 100 % ibérico”</strong>. Aquí coinciden dos datos: el producto pertenece a la categoría de bellota y el animal es 100 % ibérico conforme a los requisitos de la norma. Es también la única categoría para la que la expresión “pata negra” está legalmente reservada.</p>
<p>La palabra bellota no significa simplemente que el animal haya comido alguna bellota en algún momento. La norma vincula esta designación a unas condiciones concretas de alimentación y manejo durante la fase final de engorde en montanera. Por eso también términos como “dehesa” o “montanera” están reservados a productos que cumplen las condiciones previstas para la designación de bellota.</p>
<h2>Brida roja: de bellota ibérico</h2>
<p>La brida roja también identifica un producto <strong>de bellota</strong>, pero no 100 % ibérico. En la práctica encontraremos porcentajes raciales que deben aparecer en la denominación cuando corresponda. Esta es una de las confusiones más habituales: rojo no significa “cebo” ni “una categoría sin bellota”. Comparte con el negro el tipo de alimentación de bellota; lo que cambia es la condición racial que permite utilizar el 100 % ibérico.</p>
<p>Esto explica por qué una compra razonada no debería reducirse a “negro siempre y rojo nunca”. Si queremos comparar productos, conviene mirar el porcentaje racial, la procedencia, el elaborador, el formato y el uso que vamos a darle. El precinto nos da una base objetiva, no una cata anticipada.</p>
<h2>Brida verde: de cebo de campo ibérico</h2>
<p>El verde corresponde a <strong>“de cebo de campo ibérico”</strong>. La expresión hace referencia a un sistema de manejo y alimentación distinto del de bellota. Un dato especialmente útil es que el color verde no fija por sí solo un único porcentaje racial. Puede existir, por ejemplo, un producto de cebo de campo 100 % ibérico: el porcentaje de raza debe leerse aparte.</p>
<p>Este punto desmonta otra idea frecuente: los cuatro colores no son una escala que vaya codificando simultáneamente raza y alimentación de manera lineal. Negro sí combina expresamente bellota y 100 % ibérico; en los demás casos necesitamos completar la lectura con la denominación de venta.</p>
<h2>Brida blanca: de cebo ibérico</h2>
<p>El precinto blanco identifica la categoría <strong>“de cebo ibérico”</strong>. Son animales cuya fase de engorde responde al sistema de cebo contemplado por la norma. Como ocurre con el verde, para conocer el porcentaje racial no basta con mirar el color: hay que leer la información completa de la pieza.</p>
<p>Que el blanco sea una categoría diferente de la bellota no convierte automáticamente el producto en una mala pieza. La norma describe un sistema productivo; después intervienen muchos factores de elaboración. Lo correcto es no atribuir al color más información de la que realmente contiene.</p>
<h2>El porcentaje racial: el dato que hay que leer junto al color</h2>
<p>Cuando el producto no es 100 % ibérico, la indicación del porcentaje racial es fundamental. Es frecuente encontrar menciones como 50 % o 75 % ibérico. Esa cifra no indica cuánto jamón “es ibérico” ni mide la grasa de la pieza: informa de la proporción racial según los criterios genealógicos establecidos.</p>
<p>Por eso, ante dos productos, un buen orden de lectura sería: primero la denominación completa; después el porcentaje racial; a continuación el precinto y la información de origen, productor o denominación de origen cuando exista. El precio tiene mucho más sentido cuando sabemos exactamente qué categorías estamos comparando.</p>
<h2>¿Y si lleva DOP?</h2>
<p>Las piezas amparadas por una Denominación de Origen Protegida pueden utilizar sus propios precintos, siempre respetando los colores, menciones y requisitos de la norma cuando emplean estas denominaciones de venta. La DOP añade un pliego de condiciones territorial y productivo propio; no elimina el significado reglamentario de negro, rojo, verde o blanco.</p>
<h2>Cinco errores comunes al comprar ibérico</h2>
<ul>
<li><strong>Guiarse por el color de la pezuña.</strong> La pezuña no sustituye al precinto ni a la denominación legal.</li>
<li><strong>Usar “pata negra” para cualquier ibérico.</strong> La norma reserva esa expresión al de bellota 100 % ibérico.</li>
<li><strong>Pensar que rojo significa cebo.</strong> La brida roja es de bellota ibérico.</li>
<li><strong>Creer que verde nunca puede ser 100 % ibérico.</strong> Alimentación/manejo y porcentaje racial son variables distintas.</li>
<li><strong>Comparar precios sin comparar categorías.</strong> Una diferencia de precio puede responder a raza, alimentación, peso, curación, DOP, formato y productor.</li>
</ul>
<h2>Cómo utilizar el precinto para elegir mejor</h2>
<p>El precinto resulta especialmente útil cuando lo usamos como punto de partida y no como único criterio. Primero decide qué experiencia buscas: una pieza entera para consumo habitual, una paleta más manejable, sobres loncheados o un regalo. Después compara productos dentro de una categoría equivalente. Si aún dudas entre las dos piezas, nuestra guía sobre <a href="https://www.elmercadodeorigen.com/jamon-o-paleta-diferencias-cual-elegir/">jamón o paleta ibérica</a> explica cómo cambian rendimiento, anatomía y ritmo de consumo.</p>
<p>En <a href="{$urls['hams']}">nuestra selección de jamones y paletas</a> puedes ver ejemplos de distintas denominaciones y formatos y leer la información de cada producto antes de decidir.</p>
<h2>Fuentes y normativa</h2>
<p>La referencia principal de este artículo es el <a href="{$boe_iberico}">Real Decreto 4/2014, Norma de Calidad del Ibérico</a>. En julio de 2026 el Ministerio de Agricultura informó de que el sector y la Administración están trabajando en una actualización de la norma; ese proceso de revisión no debe confundirse con una norma nueva ya aprobada. Puede consultarse la <a href="{$mapa_update}">nota del Ministerio sobre los trabajos de actualización</a>.</p>
<h2>Productos relacionados</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
'en_content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:iberico-seals -->
<p>On a whole Iberian ham or shoulder, the coloured seal is one of the most useful clues for understanding what you are buying. It is also one of the most misunderstood. Black, red, green and white are sometimes presented as a simple subjective ranking from best to worst. That is not what the system means. The colours form part of Spain's Iberian Quality Standard and each one corresponds to a defined sales designation.</p>
<p>The most useful way to read an Iberian product is to separate three ideas that are often mixed together: <strong>breed, feeding and management, and the actual cut</strong>. The seal condenses part of that information, but it does not replace the full legal designation. Two hams may have different colours because of feeding and management; two pieces with the same colour may have different breed percentages. Reading the whole designation prevents a purchase from being driven by one isolated word.</p>
<h2>What the colour-seal system actually regulates</h2>
<p>Royal Decree 4/2014 establishes the quality standard for Iberian meat, ham, shoulder and cured loin in Spain. For hams and shoulders it requires an inviolable, individually numbered seal corresponding to the product's category. The number forms part of the traceability system and links the piece back to the relevant slaughter batch.</p>
<p>The colour is therefore not attached because a producer decides that a particular ham is “premium”. It represents a regulated category. That distinction matters because final eating quality also depends on raw material, handling, salting, drying, maturation and the producer's skill. The seal tells you the regulatory conditions under which the product is sold; it is not a sensory score.</p>
<h2>Black seal: acorn-fed 100% Iberian</h2>
<p>The black seal is reserved for <strong>“de bellota 100% ibérico”</strong>: acorn-fed and 100% Iberian under the conditions of the standard. It is also the only designation for which the expression “pata negra” is legally reserved.</p>
<p>“Acorn-fed” does not simply mean that the animal ate a few acorns at some point. The designation is linked to specific feeding and management conditions during the final montanera fattening period. Terms that evoke the dehesa or montanera are likewise reserved by the standard for products that meet the relevant bellota conditions.</p>
<h2>Red seal: acorn-fed Iberian</h2>
<p>The red seal also identifies an <strong>acorn-fed Iberian</strong> product, but not one that qualifies as 100% Iberian. The applicable breed percentage must be read in the full designation. This corrects one of the most common misconceptions: red does not mean grain-fed. Red and black share the bellota feeding category; the difference relevant to the colour is the 100% Iberian breed condition required for black.</p>
<p>This is why an informed comparison should not be reduced to “black good, red bad”. Breed percentage, producer, origin, format and intended use all matter. The seal provides an objective starting point; it does not tell you in advance how much you will enjoy the finished ham.</p>
<h2>Green seal: field-fed Iberian</h2>
<p>Green corresponds to <strong>“de cebo de campo ibérico”</strong>, a feeding and management system different from bellota. One particularly useful detail is that green does not encode a single breed percentage. A cebo de campo product can, for example, be 100% Iberian; the breed percentage has to be read separately.</p>
<p>That illustrates why the four colours should not be imagined as a single ladder simultaneously encoding both breed and feeding. Black explicitly combines bellota and 100% Iberian. For the other colours, the full sales designation supplies information the colour alone cannot.</p>
<h2>White seal: grain-fed Iberian</h2>
<p>The white seal identifies <strong>“de cebo ibérico”</strong>. The final fattening system is the cebo system defined by the standard. As with green, the colour itself is not enough to determine breed percentage, so the wording on the product remains essential.</p>
<p>Being a different production category from bellota does not automatically make a white-seal product a badly made ham. The regulation describes production conditions; curing and craftsmanship still matter. The sensible approach is to use the seal for the information it actually provides and not to turn it into claims it was never designed to make.</p>
<h2>Breed percentage: the figure to read alongside the colour</h2>
<p>When a product is not 100% Iberian, the breed percentage is an important part of the designation. Figures such as 50% or 75% Iberian refer to breed ancestry according to the rules of the standard. They do not mean that only that percentage of the ham itself is “Iberian”, nor do they measure fat content.</p>
<p>A practical reading order is: full sales designation first; breed percentage second; seal colour and then origin, producer and protected designation information where applicable. Price comparisons become far more meaningful once you know that you are comparing equivalent categories.</p>
<h2>What changes when a PDO is involved?</h2>
<p>Products protected by a PDO may use their own seals provided they respect the colours, mentions and other requirements of the Iberian standard when using these sales designations. A PDO adds its own territorial and production specification; it does not erase the regulatory meaning of black, red, green or white.</p>
<h2>Five common buying mistakes</h2>
<ul>
<li><strong>Judging by hoof colour.</strong> The hoof is not a substitute for the official seal and sales designation.</li>
<li><strong>Calling every Iberian ham “pata negra”.</strong> The expression is reserved for acorn-fed 100% Iberian.</li>
<li><strong>Assuming red means grain-fed.</strong> Red is an acorn-fed Iberian category.</li>
<li><strong>Assuming green can never be 100% Iberian.</strong> Feeding/management and breed percentage are separate variables.</li>
<li><strong>Comparing price without comparing categories.</strong> Breed, feeding, weight, curing, PDO status, format and producer can all affect price.</li>
</ul>
<h2>Using the seal to make a better choice</h2>
<p>Use the seal as the beginning of the decision rather than the whole decision. First decide how you intend to consume the product: a whole piece for regular use, a smaller shoulder, pre-sliced packs or a gift. Then compare within equivalent categories. If you are still deciding between the two cuts, our guide to <a href="https://www.elmercadodeorigen.com/en/ham-or-shoulder-differences-how-to-choose/">Iberian ham versus shoulder</a> explains yield, anatomy and consumption rate.</p>
<p>You can compare formats and designations in our <a href="{$urls['hams_en']}">ham and shoulder selection</a>, with the producer and product information visible before purchase.</p>
<h2>Sources and regulation</h2>
<p>The main reference is Spain's <a href="{$boe_iberico}">Royal Decree 4/2014 on the Iberian Quality Standard</a>. In July 2026 the Spanish Ministry of Agriculture announced ongoing work with the sector to update the standard; this is a review process, not a replacement standard already in force. The Ministry's <a href="{$mapa_update}">July 2026 update</a> explains that work.</p>
<h2>Related products</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
),
array(
'key'=>'ham-storage','slug'=>'como-conservar-jamon-iberico-empezado','en_slug'=>'how-to-store-iberian-ham-once-started','topic'=>'hams','image'=>$images['storage'],
'title'=>'Cómo conservar un jamón ibérico en casa una vez empezado',
'en_title'=>'How to store an Iberian ham at home once it has been started',
'excerpt'=>'Una pieza empezada cambia desde el primer corte: la superficie se oxida, pierde humedad y queda más expuesta al ambiente. Esta guía explica cómo protegerla, dónde colocarla y cómo adaptar la conservación al ritmo de consumo.',
'en_excerpt'=>'Once a ham is started, the cut surface oxidises, loses moisture and becomes more exposed to its surroundings. This guide explains how to protect it, where to keep it and how storage should follow your consumption rate.',
'content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:ham-storage -->
<p>Una pieza de jamón ibérico está pensada para conservarse durante mucho tiempo antes de abrirla, pero esa estabilidad cambia en cuanto hacemos el primer corte. A partir de ese momento aparece una superficie nueva expuesta al oxígeno, la luz, la temperatura y la pérdida de humedad. Conservar bien un jamón empezado no consiste en “cerrarlo” herméticamente: consiste en reducir esos factores sin crear otros problemas.</p>
<p>La regla más útil es sencilla: <strong>un jamón abierto se conserva mejor cuando se consume con regularidad, se corta con limpieza y se mantiene en un ambiente estable</strong>. Muchos problemas domésticos vienen de lo contrario: una pieza al lado de una fuente de calor, semanas sin cortar, grasa vieja pegada sobre la superficie o cambios continuos de temperatura.</p>
<h2>El lugar importa más de lo que parece</h2>
<p>Una pieza entera con hueso necesita un lugar fresco, seco, ventilado y protegido de la luz solar directa. “Fresco” no significa necesariamente frigorífico. En una vivienda, lo importante es alejar el jamonero de radiadores, horno, vitrocerámica, ventanas con sol, terrazas muy calientes y zonas con grandes oscilaciones térmicas.</p>
<p>La cocina puede parecer el lugar natural, pero no siempre es el mejor: al cocinar se acumulan calor, vapor y cambios de humedad. Si disponemos de una despensa o una zona interior estable, suele ser preferible. Ante todo, deben respetarse las indicaciones concretas del productor, porque el grado de curación, tamaño y formato de la pieza pueden modificar la recomendación.</p>
<h2>Qué le ocurre a la superficie de corte</h2>
<p>La parte recién cortada tiene humedad y grasa expuestas. Con el paso de las horas, el oxígeno modifica el color de la carne y la superficie empieza a secarse. Una ligera capa más oscura o seca después de un periodo sin cortar no significa necesariamente que el jamón esté estropeado; normalmente basta con retirar una loncha fina hasta recuperar una superficie agradable.</p>
<p>Otra cosa distinta son olores claramente rancios, sabores anómalos, superficies húmedas de forma extraña o signos que se apartan de la evolución normal de un curado. En caso de duda, no conviene “arreglar” la pieza simplemente retirando más producto: debe consultarse al productor o al vendedor.</p>
<h2>¿Conviene cubrir el corte con la propia grasa?</h2>
<p>Al empezar una pieza se retiran corteza y grasa exterior hasta llegar a la zona que vamos a consumir. Puede reservarse una lámina limpia de grasa blanca para colocarla temporalmente sobre la superficie de corte. Su función es limitar el contacto directo con el aire y ralentizar el secado.</p>
<p>Pero esa grasa tampoco dura indefinidamente. Si se oxida, amarillea en exceso o adquiere olor rancio, no tiene sentido seguir utilizándola. Es mejor reemplazarla por una porción limpia obtenida al avanzar el corte. Encima puede colocarse un paño limpio y transpirable o la protección aconsejada por el productor. Lo que debemos evitar es envolver una pieza abierta de cualquier manera con materiales que atrapen humedad y condensación.</p>
<h2>La orientación de la pieza depende del ritmo de consumo</h2>
<p>La recomendación clásica es empezar por la maza —la zona más ancha y jugosa— cuando se prevé un consumo frecuente. Si el consumo va a ser lento, puede interesar comenzar por la babilla, una zona más estrecha y habitualmente más curada, para evitar que termine secándose demasiado mientras avanzamos por la otra cara.</p>
<p>No es una norma absoluta. El tamaño de la pieza, su curación y el número de comensales importan. Lo útil es pensar en semanas de consumo real: una familia que corta jamón varias veces por semana avanza de manera muy distinta a una persona que abre la pieza únicamente los fines de semana.</p>
<h2>Cortar poco y cortar a menudo</h2>
<p>Para conservar mejor la pieza, es preferible cortar lo que se va a servir en cada ocasión. Cortar una gran cantidad para dejarla varios días en un plato o recipiente elimina una de las ventajas de tener la pieza entera: poder obtener la loncha en el momento de consumo.</p>
<p>Mantener el plano de corte razonablemente limpio y regular también ayuda. Los escalones profundos dejan recovecos que se secan de forma desigual y dificultan aprovechar la carne alrededor del hueso. Un cuchillo adecuado y una superficie de corte estable son, por tanto, herramientas de conservación además de herramientas de presentación.</p>
<h2>¿Hay que guardar el jamón entero en la nevera?</h2>
<p>Una pieza curada entera y con hueso no se trata igual que un sobre loncheado. Salvo que el productor indique lo contrario, introducir y sacar continuamente un jamón entero del frigorífico puede generar cambios de temperatura y condensación poco favorables. La recomendación para la pieza suele orientarse a un ambiente fresco y estable.</p>
<p>En cambio, el <strong>jamón loncheado y envasado</strong> debe conservarse exactamente como indique su etiqueta. Muchos formatos al vacío requieren refrigeración. Una vez abierto el envase, la situación cambia de nuevo: hay más superficie expuesta y el producto debe consumirse siguiendo las instrucciones del fabricante.</p>
<h2>Temperatura de servicio: conservar y degustar son dos cosas distintas</h2>
<p>Un sobre puede estar bien conservado en frío y, aun así, resultar poco expresivo si se sirve demasiado frío. El jamón desarrolla mejor su textura cuando la grasa pierde rigidez y aparece brillante y flexible. Por eso suele ser útil sacar el envase refrigerado con antelación suficiente para que se atempere, siempre sin ignorar las instrucciones de seguridad y conservación del producto.</p>
<p>No hace falta perseguir una cifra exacta como si todos los jamones fueran iguales. La señal sensorial es más práctica: lonchas flexibles, grasa menos opaca y aromas que aparecen con más facilidad.</p>
<h2>Qué hacer con recortes, taquitos y huesos</h2>
<p>Aprovechar bien una pieza también forma parte de conservarla. Las zonas difíciles de lonchear pueden convertirse en taquitos para cocina, croquetas, huevos, verduras o legumbres. Al llegar al hueso, puede serrarse en porciones adecuadas para caldos si se dispone de las herramientas y condiciones apropiadas. Si no se va a utilizar pronto, conviene porcionar y congelar estos aprovechamientos de manera higiénica.</p>
<h2>Una rutina sencilla después de cada corte</h2>
<ol>
<li>Retira solo la cantidad que vas a consumir.</li>
<li>Deja el plano de corte limpio y lo más regular posible.</li>
<li>Protege la superficie con grasa limpia de la propia pieza si está en buen estado.</li>
<li>Cubre de forma transpirable y mantén el jamonero lejos de calor y sol.</li>
<li>Si pasan varios días, retira una lámina superficial antes de volver a servir si se ha secado.</li>
</ol>
<p>Si tu consumo es muy esporádico, quizá una pieza entera no sea el formato más práctico. Nuestra guía sobre <a href="https://www.elmercadodeorigen.com/jamon-pieza-entera-o-loncheado-como-elegir/">pieza entera o loncheado</a> compara precisamente conservación, comodidad y ritmo de consumo.</p>
<p>Puedes consultar los formatos disponibles en nuestra <a href="{$urls['hams']}">selección de jamones y paletas</a> y seguir siempre las indicaciones específicas del productor de la pieza que elijas.</p>
<h2>Productos relacionados</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
'en_content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:ham-storage -->
<p>A cured Iberian ham is designed to remain stable for a long time before it is opened, but that stability changes the moment the first cut is made. A fresh surface is suddenly exposed to oxygen, light, temperature and moisture loss. Storing a started ham well is not about sealing it as tightly as possible; it is about limiting those factors without creating new problems.</p>
<p>The most useful principle is simple: <strong>an opened ham keeps best when it is eaten regularly, sliced cleanly and kept in a stable environment</strong>. Many household problems come from the opposite: leaving the piece next to a heat source, not cutting it for weeks, keeping old oxidised fat pressed against the surface or moving it repeatedly between very different temperatures.</p>
<h2>Where you keep it matters</h2>
<p>A whole bone-in cured ham needs a cool, dry, ventilated place away from direct sunlight. “Cool” does not automatically mean refrigerator. At home, keep the ham stand away from radiators, ovens, hobs, sunny windows, hot terraces and places with large temperature swings.</p>
<p>The kitchen feels like the obvious location, but it is not always ideal because cooking produces heat, steam and rapid changes in humidity. A stable interior pantry or similar space may be better. The producer's own instructions should always take priority because curing level, size and format can change the recommendation.</p>
<h2>What happens to the cut surface</h2>
<p>The freshly cut face contains exposed moisture and fat. As time passes, oxygen changes the colour and the surface gradually dries. A slightly darker or drier first layer after a period without slicing does not automatically mean that the ham is spoiled; often a very thin trimming slice is enough to return to an attractive surface.</p>
<p>Clearly rancid smells, abnormal flavours, unusual dampness or other signs outside the normal development of a dry-cured product are different. If there is genuine doubt, the solution is not simply to cut deeper and hope for the best. Ask the producer or seller for advice.</p>
<h2>Should you cover the cut with the ham's own fat?</h2>
<p>When a whole ham is opened, rind and external fat are trimmed away until the area to be sliced is exposed. A clean piece of the white fat can be reserved and placed over the cut face. It acts as a temporary barrier, reducing direct exposure to air and slowing dehydration.</p>
<p>That piece of fat is not permanent. If it becomes heavily yellowed, oxidised or rancid-smelling, replace it with a fresh clean piece as the cut progresses. A clean breathable cloth or the producer's recommended cover can then go over the top. What matters is avoiding improvised wrapping that traps moisture and condensation around the exposed ham.</p>
<h2>Which side to start depends on how quickly you will eat it</h2>
<p>The traditional recommendation is to start with the maza —the broader, usually juicier side— when the ham will be eaten frequently. If consumption will be slow, starting with the narrower, generally more cured babilla can make sense so that this side is not left to dry further while the other side is being eaten.</p>
<p>This is a practical rule rather than an absolute law. Size, curing and number of diners all matter. Think in terms of your real weekly consumption: a family slicing ham several times a week advances through the piece very differently from one person who serves it only at weekends.</p>
<h2>Slice little, slice often</h2>
<p>For storage quality, it is usually better to cut the amount you plan to serve each time. Cutting a large quantity and leaving it for days in a dish or container removes one of the main advantages of owning a whole piece: being able to produce the slice at the moment you want to eat it.</p>
<p>A reasonably flat, clean cutting plane also helps. Deep steps and hollows dry unevenly and make it harder to use the meat around the bone efficiently. A suitable knife and a secure ham stand are therefore storage tools as well as slicing tools.</p>
<h2>Should a whole ham go in the refrigerator?</h2>
<p>A whole cured bone-in ham is not handled like a packet of sliced ham. Unless the producer specifically instructs otherwise, repeatedly moving a whole ham in and out of the refrigerator can create temperature changes and condensation that are not helpful. Recommendations for whole pieces normally focus on a cool, stable environment.</p>
<p><strong>Vacuum-packed sliced ham</strong>, on the other hand, must be stored exactly as its label states. Many packs require refrigeration. Once a packet is opened, the situation changes again because far more surface area is exposed, and the manufacturer's instructions for opened product become particularly important.</p>
<h2>Serving temperature and storage temperature are not the same thing</h2>
<p>A packet can be correctly refrigerated and still taste muted if served very cold. Ham becomes more expressive as its fat loses rigidity and appears glossy and flexible. It is therefore often useful to allow a refrigerated packet to temper before serving, while still respecting its food-safety and storage instructions.</p>
<p>There is no need to chase one magic temperature for every product. Practical sensory signs are useful: flexible slices, less opaque fat and aromas that become easier to perceive.</p>
<h2>Using trimmings, cubes and bones</h2>
<p>Good storage also means good use. Areas that are difficult to slice can become cubes for cooking, croquettes, eggs, vegetables or pulses. When you reach the bone it can be cut into manageable sections for stocks if you have the right equipment and hygienic conditions. Portions that will not be used soon can be packed and frozen appropriately.</p>
<h2>A simple routine after each slicing session</h2>
<ol>
<li>Cut only what you plan to eat.</li>
<li>Leave the cutting face clean and reasonably even.</li>
<li>Protect it with clean fat from the piece if that fat remains in good condition.</li>
<li>Cover it appropriately and keep the stand away from heat and sunlight.</li>
<li>After several idle days, trim a thin dry surface layer if necessary before serving again.</li>
</ol>
<p>If your consumption is genuinely occasional, a whole ham may not be the most practical format. Our guide to <a href="https://www.elmercadodeorigen.com/en/whole-ham-or-sliced-how-to-choose-format/">whole versus sliced Iberian ham</a> compares storage, convenience and consumption rate.</p>
<p>See the formats available in our <a href="{$urls['hams_en']}">ham and shoulder selection</a>, and always follow the specific instructions supplied by the producer of the piece you buy.</p>
<h2>Related products</h2>
[products category="jamones-paletas" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
),
array(
'key'=>'cured-meats-guide','slug'=>'lomo-chorizo-salchichon-iberico-diferencias-como-elegir','en_slug'=>'iberian-loin-chorizo-salchichon-differences-how-to-choose','topic'=>'cured','image'=>$images['cured'],
'title'=>'Lomo, chorizo y salchichón ibérico: diferencias y cómo elegir',
'en_title'=>'Iberian loin, chorizo and salchichón: differences and how to choose',
'excerpt'=>'Lomo, chorizo y salchichón comparten una despensa, pero no la misma elaboración. Cambian la materia prima, el picado, la condimentación, la textura y el orden en que conviene degustarlos. Esta guía ayuda a distinguirlos.',
'en_excerpt'=>'Loin, chorizo and salchichón share the same charcuterie board but not the same production method. Raw material, mincing, seasoning, texture and tasting order all differ. This guide explains how to tell them apart.',
'content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:cured-meats-guide -->
<p>En una tabla de ibéricos, lomo, chorizo y salchichón pueden parecer tres variantes de una misma idea: carne curada, cortada en lonchas y servida como aperitivo. Desde el punto de vista de la elaboración, sin embargo, son productos muy distintos. Entender esa diferencia ayuda a leer mejor la etiqueta, a elegir según el gusto de cada persona y a servirlos de una manera que respete su textura y su perfil aromático.</p>
<p>La primera gran separación está entre <strong>una pieza muscular curada</strong> y <strong>un embutido elaborado a partir de carne y grasa troceadas o picadas</strong>. Esa decisión tecnológica determina prácticamente todo lo que percibimos después en boca.</p>
<h2>El lomo: una pieza muscular, no una masa de carne picada</h2>
<p>La caña de lomo se elabora a partir de una pieza muscular que se mantiene entera, se adoba, se embute y se somete a curación. Al cortar una loncha seguimos viendo la estructura continua del músculo: sus fibras, su grasa infiltrada y la dirección natural de la pieza. Esa estructura explica que la textura del lomo sea diferente de la de un chorizo o un salchichón.</p>
<p>En productos ibéricos, la caña de lomo está incluida específicamente en la Norma de Calidad del Ibérico. Conviene no utilizar “lomo” y “lomito” como si fueran términos universalmente intercambiables: diferentes elaboradores pueden emplear cortes y denominaciones comerciales distintas. La ficha del producto debe indicar qué pieza se ha elaborado y cuál es su denominación.</p>
<p>Sensorialmente, el lomo suele dejar más protagonismo al sabor de la carne, a la grasa infiltrada y al adobo, sin el mosaico de carne y tocino característico de un embutido picado. Cortarlo demasiado grueso puede volver la mordida innecesariamente firme; una loncha fina permite percibir mejor su textura.</p>
<h2>Chorizo: el pimentón cambia el centro de gravedad</h2>
<p>El chorizo parte de carnes y grasa troceadas o picadas, condimentadas y embutidas antes de su curación. Entre sus elementos característicos está el pimentón, que influye de forma decisiva en color, aroma y sabor. El ajo y otras especias pueden completar el perfil según la receta del elaborador.</p>
<p>Esto no significa que todos los chorizos sepan igual. Cambian el tamaño del picado, la proporción de grasa, el tipo y cantidad de pimentón, el calibre, la humedad final y el tiempo de maduración. Un chorizo de picado más grueso ofrece una percepción distinta de carne y grasa que otro de estructura fina; uno más curado presenta una mordida más firme y aromas más concentrados.</p>
<h2>Salchichón: especias y carne con un perfil menos dominado por el pimentón</h2>
<p>El salchichón también es un embutido curado elaborado con carne y grasa, pero su personalidad aromática es diferente. En lugar de estar definido por el protagonismo del pimentón propio del chorizo, suele apoyarse en pimienta y otras especias. Por eso, cuando se prueban productos comparables del mismo elaborador, el salchichón suele permitir apreciar de otra manera el sabor de la carne y la grasa.</p>
<p>La textura vuelve a depender del picado y la curación. En una buena loncha se distingue el mosaico de magro y grasa y la masa mantiene cohesión sin convertirse en una pasta homogénea. Como en cualquier derivado cárnico, la receta concreta importa más que una descripción genérica: la lista de ingredientes nos dice qué ha añadido realmente el productor.</p>
<h2>Qué significa “ibérico” en un embutido</h2>
<p>La palabra “ibérico” aplicada a derivados cárnicos no debería interpretarse como una licencia para imaginar cualquier característica. España cuenta con normas de calidad específicas tanto para el ibérico como para los derivados cárnicos. La trazabilidad, la materia prima y el uso de determinadas menciones deben poder sostener la denominación empleada.</p>
<p>Para el comprador, la mejor práctica es sencilla: leer la denominación, la lista de ingredientes, el productor y, cuando se informe, el porcentaje racial o las condiciones relevantes. “Ibérico” es información importante, pero no sustituye al resto de la ficha.</p>
<h2>Textura: la diferencia más fácil de reconocer a ciegas</h2>
<p>Si cerráramos los ojos, la estructura distinguiría muy pronto los tres productos. El lomo conserva fibras musculares continuas. Chorizo y salchichón muestran un mosaico de partículas de magro y grasa unidas durante el proceso de curación. Esa arquitectura modifica la forma en que la grasa se funde en boca y la velocidad a la que aparecen las especias.</p>
<p>También cambia la loncha ideal. El lomo agradece un corte fino que atraviese la fibra de forma limpia. En chorizo y salchichón, una loncha excesivamente fina puede perder parte de la sensación de textura; demasiado gruesa, por el contrario, puede resultar pesada. El punto adecuado depende del calibre y curación de cada pieza.</p>
<h2>En qué orden servirlos</h2>
<p>No existe un protocolo obligatorio, pero en una cata comparativa suele funcionar avanzar desde perfiles menos dominantes hacia los más condimentados. Una secuencia razonable puede ser lomo, salchichón y chorizo. Así el pimentón y las especias más intensas del chorizo no condicionan desde el principio la percepción del resto.</p>
<p>Entre productos muy diferentes la lógica puede cambiar. Un salchichón intensamente especiado podría resultar más potente que un chorizo suave. Lo importante es ordenar por intensidad real y ofrecer pan y agua para limpiar el paladar, sin llenar la tabla de acompañamientos que oculten el producto.</p>
<h2>Cómo elegir según el momento</h2>
<ul>
<li><strong>Para una degustación centrada en la carne:</strong> el lomo es una referencia especialmente interesante por su estructura de músculo entero.</li>
<li><strong>Para quien disfruta del pimentón y un perfil más marcado:</strong> el chorizo suele ser la elección natural.</li>
<li><strong>Para una tabla equilibrada:</strong> el salchichón aporta un registro especiado distinto y crea contraste con el chorizo.</li>
<li><strong>Para regalar:</strong> combinar los tres permite mostrar técnicas y perfiles diferentes sin depender de una única pieza grande.</li>
</ul>
<h2>Conservación y servicio</h2>
<p>Una pieza entera de embutido y un sobre loncheado no se conservan necesariamente de la misma forma. Hay que seguir la etiqueta de cada producto, especialmente después de abrirlo. Los sobres refrigerados suelen expresarse mejor si se atemperan antes de servir, siempre dentro de las instrucciones del fabricante. En piezas enteras, un corte limpio y una protección adecuada del extremo ayudan a limitar el secado.</p>
<p>En <a href="{$urls['cured']}">nuestra selección de embutidos y curados</a> puedes comparar distintas elaboraciones de Hidalgo de la Jara y revisar ingredientes, formato y características antes de elegir.</p>
<h2>Fuentes y normativa</h2>
<p>Para la terminología y los criterios generales de derivados cárnicos hemos utilizado como referencia el <a href="{$boe_derivados}">Real Decreto 474/2014, norma de calidad de derivados cárnicos</a>, cuyo texto consolidado fue actualizado en febrero de 2026, y la <a href="{$boe_iberico}">Norma de Calidad del Ibérico</a> para las categorías específicamente reguladas en ella.</p>
<h2>Productos relacionados</h2>
[products category="embutidos-y-curados" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
'en_content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:cured-meats-guide -->
<p>On an Iberian charcuterie board, cured loin, chorizo and salchichón can look like three versions of the same idea: cured pork, sliced and served as an appetiser. From a production point of view they are fundamentally different. Understanding those differences makes labels easier to read, helps you choose according to taste and allows each product to be served in a way that respects its texture and aromatic profile.</p>
<p>The first major distinction is between <strong>a cured whole muscle</strong> and <strong>a sausage made from chopped or minced meat and fat</strong>. That technological choice determines much of what we later perceive in the mouth.</p>
<h2>Cured loin: a whole muscle rather than a minced mixture</h2>
<p>Caña de lomo is produced from a muscle that remains whole, is seasoned, placed in a casing and cured. When it is sliced, the continuous structure of the muscle remains visible: fibres, intramuscular fat and the natural direction of the cut. That is why its texture is unlike chorizo or salchichón.</p>
<p>For Iberian products, caña de lomo is specifically covered by Spain's Iberian Quality Standard. It is worth avoiding the assumption that “lomo” and “lomito” always describe the same cut. Producers may work with different muscles and commercial names, so the product sheet should identify what has actually been cured.</p>
<p>From a sensory perspective, loin often gives the meat, intramuscular fat and seasoning more direct prominence, without the mosaic structure of a minced sausage. Cutting it too thick can make the bite unnecessarily firm; a fine slice usually shows its texture more elegantly.</p>
<h2>Chorizo: paprika shifts the centre of flavour</h2>
<p>Chorizo is made from chopped or minced meat and fat, seasoned and stuffed before curing. Paprika is one of its defining elements and has a major influence on colour, aroma and taste. Garlic and other seasonings can complete the profile according to the producer's recipe.</p>
<p>That does not mean all chorizos taste alike. Particle size, fat proportion, paprika type and quantity, casing diameter, final moisture and maturation all change the result. A coarse-cut chorizo reveals meat and fat differently from a finely structured one; a drier, more mature piece gives a firmer bite and more concentrated aromas.</p>
<h2>Salchichón: spice and meat without paprika taking centre stage</h2>
<p>Salchichón is also a cured sausage based on meat and fat, but its aromatic identity is different. Instead of being defined by paprika in the way chorizo is, it commonly relies on pepper and other spices. When comparable products from the same maker are tasted side by side, salchichón can therefore reveal the character of the meat and fat from a different angle.</p>
<p>Texture again depends on particle size and curing. A good slice shows a recognisable mosaic of lean and fat while remaining cohesive rather than becoming a uniform paste. As with any cured meat, the specific recipe matters more than a generic description, so the ingredient list is valuable information.</p>
<h2>What does “Iberian” mean on a cured sausage?</h2>
<p>The term “Iberian” on a meat product should not be treated as permission to assume every other characteristic. Spain has quality rules for both Iberian products and meat derivatives. Traceability, raw material and the use of protected or regulated terms must support the designation being used.</p>
<p>For the buyer, the practical approach is to read the full name, ingredients, producer and any stated breed or production information. “Iberian” matters, but it does not replace the rest of the product specification.</p>
<h2>Texture is the easiest difference to identify blind</h2>
<p>With your eyes closed, structure would distinguish the products quickly. Loin retains continuous muscle fibres. Chorizo and salchichón show a mosaic of lean and fat particles bound together during curing. This architecture changes the way fat melts in the mouth and how quickly seasoning is released.</p>
<p>The ideal slice changes too. Loin benefits from a clean, fine slice across the fibres. With chorizo and salchichón, an excessively thin slice can lose some textural interest, while a very thick one may feel heavy. The right thickness depends on casing diameter and curing level.</p>
<h2>What order should they be served in?</h2>
<p>There is no compulsory tasting protocol, but a comparative tasting often works best by moving from less dominant profiles to more heavily seasoned ones. Loin, salchichón and then chorizo is a useful sequence because paprika and stronger chorizo seasoning do not condition the palate before the other products are assessed.</p>
<p>Real products can of course break that rule. A strongly peppered salchichón may be more intense than a mild chorizo. The principle is to order by actual intensity and provide bread and water to reset the palate rather than covering the cured meats with too many accompaniments.</p>
<h2>Choosing for the occasion</h2>
<ul>
<li><strong>For a tasting focused on the meat itself:</strong> cured loin is particularly informative because of its whole-muscle structure.</li>
<li><strong>For someone who enjoys paprika and a bolder seasoning profile:</strong> chorizo is the natural choice.</li>
<li><strong>For a balanced board:</strong> salchichón supplies a different spicy register and useful contrast with chorizo.</li>
<li><strong>For a gift:</strong> combining all three shows different techniques and flavour profiles without requiring one large whole ham.</li>
</ul>
<h2>Storage and serving</h2>
<p>A whole cured sausage and a pre-sliced packet do not necessarily have the same storage instructions. Follow each label, particularly after opening. Refrigerated sliced packs are often more expressive after tempering before service, while still remaining within the manufacturer's safety instructions. With whole pieces, a clean cut and appropriate protection of the exposed end help limit drying.</p>
<p>Our <a href="{$urls['cured_en']}">cured meats selection</a> lets you compare different Hidalgo de la Jara products and check ingredients, format and product details before choosing.</p>
<h2>Sources and regulation</h2>
<p>For terminology and general meat-derivative criteria we used Spain's <a href="{$boe_derivados}">Royal Decree 474/2014 on the quality standard for meat derivatives</a>, whose consolidated text was updated in February 2026, together with the <a href="{$boe_iberico}">Iberian Quality Standard</a> for categories specifically covered by it.</p>
<h2>Related products</h2>
[products category="embutidos-y-curados" limit="6" columns="3" orderby="popularity" order="DESC"]
HTML,
),
array(
'key'=>'oil-varieties','slug'=>'picual-arbequina-lechin-que-aove-elegir','en_slug'=>'picual-arbequina-lechin-which-evoo-to-choose','topic'=>'oil','image'=>$images['varieties'],
'title'=>'Picual, Arbequina o Lechín: qué AOVE elegir según el plato',
'en_title'=>'Picual, Arbequina or Lechín: which extra virgin olive oil to choose for each dish',
'excerpt'=>'La variedad de aceituna cambia el perfil de un AOVE: intensidad, frutado, amargor, picor y persistencia no se expresan igual en Arbequina, Picual o Lechín. Elegir variedad es elegir cuánto queremos que el aceite participe en el plato.',
'en_excerpt'=>'Olive variety changes the sensory profile of an extra virgin olive oil: fruitiness, bitterness, pungency, intensity and persistence differ between Arbequina, Picual and Lechín. Choosing a variety means choosing how strongly the oil participates in the dish.',
'content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:oil-varieties -->
<p>Elegir un aceite de oliva virgen extra únicamente por la palabra “AOVE” es parecido a elegir vino limitándonos a saber que es vino: la categoría nos dice mucho sobre cómo se ha obtenido y clasificado el producto, pero dentro de ella existe una enorme diversidad. La variedad de aceituna es una de las variables que más fácilmente puede reconocer el consumidor porque modifica el aroma, el equilibrio entre amargo y picante, la sensación de dulzor y la persistencia.</p>
<p>Arbequina, Picual y Lechín no representan tres niveles de calidad. <strong>Son tres perfiles posibles</strong>. Un AOVE delicado puede ser magnífico para una preparación y quedarse corto en otra; un aceite intenso puede elevar un tomate maduro o una carne y dominar un plato que pide sutileza. El criterio útil no es preguntar cuál es “el mejor”, sino qué papel queremos que desempeñe el aceite.</p>
<h2>Antes de comparar variedades: la cosecha y la elaboración también cuentan</h2>
<p>Una variedad no sabe siempre exactamente igual. El grado de madurez de la aceituna, el momento de cosecha, el clima, el suelo, el tiempo hasta la almazara, la extracción y la conservación cambian el perfil final. Por eso las descripciones varietales deben entenderse como tendencias sensoriales, no como recetas matemáticas.</p>
<p>También puede haber diferencias entre una campaña y otra. Un productor que trabaja bien ajusta su proceso a la materia prima que recibe. La variedad marca una dirección; la cosecha y la elaboración escriben la versión concreta de ese año.</p>
<h2>Arbequina: suavidad, fluidez y un frutado amable</h2>
<p>La Arbequina se asocia habitualmente con aceites fluidos, de entrada dulce o suave y con amargor y picor contenidos. Las descripciones varietales del sector citan notas que pueden recordar a manzana, plátano, almendra y fruta fresca. Esa combinación la convierte en una buena puerta de entrada para personas poco acostumbradas a AOVE intensos.</p>
<p>En cocina funciona especialmente bien cuando queremos que el aceite acompañe sin imponerse: mayonesas y emulsiones suaves, ensaladas delicadas, pescado blanco, verduras de sabor sutil, repostería en la que sustituimos otras grasas o un desayuno con pan donde buscamos un perfil redondo. “Suave” no significa neutro ni de menor calidad; significa que su balance sensorial suele ocupar menos espacio en el plato.</p>
<h2>Picual: estructura, carácter y persistencia</h2>
<p>Picual es una de las variedades más reconocibles de España. Suele ofrecer un perfil más intenso, con frutado marcado y una presencia más evidente de amargor y picor, especialmente en aceites de cosecha temprana o de perfil verde. Es además una variedad apreciada por su estabilidad oxidativa.</p>
<p>Esa personalidad funciona muy bien cuando queremos que el aceite sea un ingrediente visible: pan con tomate, tomate natural, verduras asadas, legumbres, sopas, carnes, marinados, platos de cuchara o ensaladas con ingredientes potentes. En una tostada sencilla, un Picual expresivo puede convertirse prácticamente en el sabor principal.</p>
<p>En el catálogo de 1957 encontrarás la denominación <strong>Marteña (Picual)</strong>. Utilizamos aquí “Picual” como referencia varietal general y respetamos “Marteña (Picual)” tal como aparece identificada por el productor en su producto.</p>
<h2>Lechín: un equilibrio con personalidad propia</h2>
<p>Lechín —y en particular Lechín de Sevilla en las descripciones varietales más habituales— ofrece un perfil frutado con tonos verdes y un equilibrio reconocible entre amargor y picor. Para quien encuentra una Arbequina demasiado delicada pero no quiere que toda la cocina tenga la intensidad de un Picual marcado, puede ocupar un espacio muy interesante.</p>
<p>Es especialmente versátil con verduras, legumbres, platos de cuchara, carnes blancas, masas saladas, salsas, panes y elaboraciones en las que queremos notar el AOVE sin convertirlo en el único protagonista. Esa posición intermedia no lo hace “menos definido”: su personalidad se expresa precisamente en el equilibrio.</p>
<h2>Un mapa práctico según el plato</h2>
<table>
<thead><tr><th>Preparación</th><th>Perfil que puede funcionar</th><th>Por qué</th></tr></thead>
<tbody>
<tr><td>Mayonesa o emulsión suave</td><td>Arbequina</td><td>Aporta fruta y grasa sin dominar tanto la mezcla.</td></tr>
<tr><td>Pan con tomate</td><td>Picual</td><td>Su intensidad soporta muy bien la acidez y el sabor del tomate.</td></tr>
<tr><td>Verduras asadas</td><td>Picual o Lechín</td><td>Ambos pueden acompañar sabores tostados con suficiente presencia.</td></tr>
<tr><td>Pescado blanco</td><td>Arbequina o Lechín suave</td><td>Permiten conservar protagonismo del pescado.</td></tr>
<tr><td>Legumbres</td><td>Picual o Lechín</td><td>Un toque final en crudo añade aroma y persistencia al guiso.</td></tr>
<tr><td>Carne a la plancha</td><td>Picual</td><td>Su carácter resiste mejor sabores intensos y tostados.</td></tr>
<tr><td>Repostería</td><td>Arbequina</td><td>Su perfil más amable suele integrarse con mayor facilidad.</td></tr>
</tbody>
</table>
<p>La tabla no pretende convertir la cocina en un reglamento. Una de las mejores formas de aprender AOVE es probar dos variedades sobre el mismo alimento. Un tomate, una rebanada de pan o una patata cocida ofrecen una base neutra suficiente para apreciar cómo cambia el plato con cada aceite.</p>
<h2>¿Y para cocinar? Un buen AOVE no es solo para tomar en crudo</h2>
<p>Existe la idea de que un AOVE de calidad debe reservarse exclusivamente para aliñar. Es una simplificación. El aceite participa también en sofritos, horno, salteados, guisos y frituras. Al calentarlo cambia parte de su expresión aromática, pero siguen importando la estabilidad, el sabor de partida y la forma en que se integra con el resto de ingredientes.</p>
<p>La decisión puede ser económica además de sensorial. Podemos reservar un AOVE muy aromático para terminar un plato y utilizar otro perfil para la cocción, o cocinar y terminar con la misma variedad cuando queremos continuidad de sabor.</p>
<h2>Tener dos perfiles en casa suele ser más útil que buscar un aceite universal</h2>
<p>Si se consume AOVE a diario, una combinación muy práctica es disponer de un aceite de perfil amable y otro más intenso. La Arbequina cubre usos delicados; Picual o Lechín amplían las posibilidades cuando el plato pide más presencia. Así no obligamos al mismo aceite a funcionar igual en una vinagreta suave y en un plato de carne.</p>
<p>1957 ofrece actualmente distintas opciones varietales dentro de su gama, lo que permite comparar perfiles sin cambiar de productor. En nuestra <a href="{$urls['oil']}">selección de aceites</a> puedes consultar los formatos disponibles. Si compras formatos grandes, también te interesa nuestra guía sobre <a href="https://www.elmercadodeorigen.com/aove-garrafa-5-litros-cuando-compensa-como-conservar/">AOVE en garrafa de 5 litros y conservación</a>.</p>
<h2>Fuente varietal</h2>
<p>Para las características sensoriales generales hemos utilizado como referencia las fichas y contenidos de <a href="{$olive_varieties}">Aceites de Oliva de España sobre la importancia de las variedades</a>. El perfil exacto de cada botella debe valorarse siempre según la información del productor y la campaña concreta.</p>
<h2>Productos relacionados</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
'en_content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:oil-varieties -->
<p>Choosing extra virgin olive oil only by looking for the words “extra virgin” is rather like choosing wine knowing only that it is wine. The category tells you a great deal about how the oil was produced and classified, but there is still enormous diversity within it. Olive variety is one of the easiest variables for a consumer to explore because it changes aroma, bitterness, pungency, perceived sweetness and persistence.</p>
<p>Arbequina, Picual and Lechín are not three quality levels. <strong>They are three possible sensory profiles</strong>. A delicate EVOO can be superb in one preparation and too quiet in another; an intense oil can transform ripe tomato or grilled meat yet dominate a dish that needs subtlety. The useful question is not which variety is “best”, but what role you want the oil to play.</p>
<h2>Before comparing varieties: harvest and production matter too</h2>
<p>A variety does not taste exactly the same every time. Olive maturity, harvest date, climate, soil, time before milling, extraction and storage all influence the final profile. Varietal descriptions should therefore be read as sensory tendencies rather than mathematical formulas.</p>
<p>There can also be differences from one harvest to the next. A skilled producer adjusts processing to the fruit received. Variety sets a direction; harvest and production write that season's particular version.</p>
<h2>Arbequina: gentle, fluid and approachable fruitiness</h2>
<p>Arbequina is commonly associated with fluid oils, a sweet or gentle entry and relatively restrained bitterness and pungency. Spanish sector descriptions mention aromas that can recall apple, banana, almond and fresh fruit. This makes it an approachable starting point for people who are not used to strongly bitter or pungent EVOOs.</p>
<p>In the kitchen it is particularly useful when the oil should support rather than dominate: mild mayonnaise and emulsions, delicate salads, white fish, subtly flavoured vegetables, baking where olive oil replaces another fat, or breakfast toast where a rounded profile is wanted. “Mild” does not mean neutral or lower quality; it means the sensory balance usually takes up less space in the dish.</p>
<h2>Picual: structure, character and persistence</h2>
<p>Picual is one of Spain's most recognisable olive varieties. It commonly produces a more intense profile, with pronounced fruitiness and a clearer bitter and pungent presence, particularly in early-harvest or greener styles. It is also valued for its oxidative stability.</p>
<p>That personality works well when the oil is meant to be a visible ingredient: tomato on toast, fresh tomato, roasted vegetables, pulses, soups, meat, marinades, stews or robust salads. On a simple piece of bread, an expressive Picual can almost become the principal flavour.</p>
<p>In 1957's range you will see the wording <strong>Marteña (Picual)</strong>. Here we use Picual as the general varietal reference while preserving the producer's own “Marteña (Picual)” description when referring to its product.</p>
<h2>Lechín: balance with a personality of its own</h2>
<p>Lechín —particularly Lechín de Sevilla in commonly used varietal descriptions— tends to offer a fruity profile with green tones and a recognisable balance between bitterness and pungency. For someone who finds Arbequina too delicate but does not want every dish to carry the force of a powerful Picual, it can occupy a very useful middle ground.</p>
<p>It is versatile with vegetables, pulses, stews, white meats, savoury pastry, sauces and breads, especially when you want the EVOO to be noticeable without becoming the only protagonist. That middle position does not make it vague; balance is part of its identity.</p>
<h2>A practical map by dish</h2>
<table>
<thead><tr><th>Preparation</th><th>Useful profile</th><th>Reason</th></tr></thead>
<tbody>
<tr><td>Mild mayonnaise or emulsion</td><td>Arbequina</td><td>Fruit and richness without overwhelming the mixture.</td></tr>
<tr><td>Tomato on toast</td><td>Picual</td><td>Its intensity stands up well to tomato acidity and flavour.</td></tr>
<tr><td>Roasted vegetables</td><td>Picual or Lechín</td><td>Both can accompany browned, roasted flavours with enough presence.</td></tr>
<tr><td>White fish</td><td>Arbequina or a gentle Lechín</td><td>They can leave the fish itself in the foreground.</td></tr>
<tr><td>Pulses</td><td>Picual or Lechín</td><td>A raw finishing drizzle can add aroma and persistence to the stew.</td></tr>
<tr><td>Grilled beef</td><td>Picual</td><td>Its character copes well with strong, browned flavours.</td></tr>
<tr><td>Baking</td><td>Arbequina</td><td>Its gentler profile often integrates more easily.</td></tr>
</tbody>
</table>
<p>This is not intended to turn cooking into a rule book. One of the best ways to learn about EVOO is to taste two varieties on the same simple food. Tomato, bread or a boiled potato provides a sufficiently neutral base to notice how dramatically the oil can change the dish.</p>
<h2>What about cooking? Good EVOO is not only for raw use</h2>
<p>The idea that high-quality extra virgin olive oil should be reserved exclusively for dressing is too simplistic. Olive oil also works in sautéing, roasting, stewing and frying. Heat changes part of its aromatic expression, but starting flavour, stability and interaction with the food still matter.</p>
<p>The choice can be economic as well as sensory. You might reserve a particularly aromatic EVOO for finishing and use another profile during cooking, or cook and finish with the same variety when you want continuity of flavour.</p>
<h2>Two profiles at home can be more useful than one “universal” oil</h2>
<p>For a household that uses EVOO daily, keeping one gentle oil and one more assertive oil is often practical. Arbequina covers delicate uses; Picual or Lechín expands the range when a dish needs more presence. That avoids asking one bottle to behave identically in a mild vinaigrette and with grilled meat.</p>
<p>1957 currently offers different varietal options, allowing profiles to be compared within one producer's range. See the available formats in our <a href="{$urls['oil_en']}">olive oil selection</a>. If you buy larger containers, our guide to <a href="https://www.elmercadodeorigen.com/en/extra-virgin-olive-oil-5-litre-container-storage-guide/">5-litre EVOO and storage</a> is also useful.</p>
<h2>Varietal source</h2>
<p>For general sensory characteristics we used the varietal material published by <a href="{$olive_varieties}">Aceites de Oliva de España</a>. The exact profile of a bottle should always be judged according to the producer's information and the specific harvest.</p>
<h2>Related products</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
),
array(
'key'=>'what-evoo-means','slug'=>'que-significa-aceite-oliva-virgen-extra','en_slug'=>'what-extra-virgin-olive-oil-really-means','topic'=>'oil','image'=>$images['evoo'],
'title'=>'Qué significa realmente que un aceite sea virgen extra',
'en_title'=>'What “extra virgin olive oil” really means',
'excerpt'=>'“Virgen extra” no es un adjetivo publicitario: es una categoría regulada que exige un método de obtención y parámetros químicos y sensoriales concretos. Explicamos qué implica, qué no dice y cómo leer mejor una botella.',
'en_excerpt'=>'“Extra virgin” is not a promotional adjective: it is a regulated category requiring a defined production method plus chemical and sensory criteria. This guide explains what the term means, what it does not tell you and how to read a bottle more intelligently.',
'content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:what-evoo-means -->
<p>“Aceite de oliva virgen extra” se ha convertido en una expresión tan habitual que es fácil leerla como si significara simplemente “aceite de oliva bueno”. Técnicamente es mucho más precisa. <strong>Virgen extra es una categoría legal y comercial</strong>, no un superlativo que el fabricante pueda colocar libremente en una botella. Para pertenecer a ella, el aceite debe proceder de aceitunas mediante procedimientos admitidos para los aceites vírgenes y cumplir criterios analíticos y sensoriales.</p>
<p>Entenderlo cambia la forma de comprar. La categoría AOVE nos da una garantía básica sobre el tipo de aceite que tenemos delante, pero no nos dice por sí sola la variedad, la intensidad, el momento de cosecha, el productor, la frescura o si ese perfil encaja con nuestro gusto. Dos AOVE pueden ser muy diferentes y seguir cumpliendo ambos la misma categoría.</p>
<h2>“Virgen” describe primero cómo se obtiene</h2>
<p>Los aceites de oliva vírgenes se obtienen directamente del fruto mediante procedimientos mecánicos o físicos, bajo condiciones que no provoquen alteraciones del aceite, y sin recurrir a los procesos de refinado empleados para corregir aceites de otras categorías. El punto de partida es, por tanto, la aceituna y una extracción física.</p>
<p>En una almazara moderna, de forma simplificada, el proceso incluye recepción y limpieza del fruto, molienda, batido de la pasta, separación por centrifugación y posterior manejo del aceite. Filtrar o dejar decantar son decisiones posteriores que pueden modificar aspecto y evolución, pero <strong>un aceite filtrado y uno sin filtrar pueden ser ambos virgen extra</strong> si cumplen los requisitos de la categoría.</p>
<h2>“Extra” exige algo más que haber salido de una almazara</h2>
<p>No todo aceite obtenido mecánicamente es automáticamente virgen extra. La clasificación requiere cumplir parámetros físico-químicos y organolépticos. La Comisión Europea resume el AOVE como la categoría de mayor calidad organoléptica entre los aceites vírgenes destinados al consumidor: debe carecer de defectos sensoriales y presentar frutado, además de respetar los límites analíticos aplicables.</p>
<p>Uno de los datos más conocidos es la <strong>acidez libre, que para un virgen extra no puede superar 0,8 %</strong> expresada en ácido oleico. Sin embargo, reducir toda la calidad del aceite a ese número sería un error. Existen otros parámetros analíticos y, además, la evaluación sensorial es esencial para la clasificación.</p>
<h2>La acidez no es el sabor ácido del aceite</h2>
<p>Este es probablemente el malentendido más frecuente. La acidez libre es un parámetro químico que se determina en laboratorio. No significa que un aceite de 0,2 % “sepa menos ácido” que otro de 0,7 %, ni se mide probando el aceite con la lengua. Un consumidor no puede calcular la acidez libre mediante una cata doméstica.</p>
<p>Por eso frases como “es muy suave, así que tiene poca acidez” mezclan dos planos distintos. Suavidad, amargor, picor y frutado son sensaciones; la acidez libre es un dato analítico.</p>
<h2>El análisis sensorial también decide la categoría</h2>
<p>Para ser virgen extra, un aceite no debe presentar defectos sensoriales en la evaluación reglamentaria y debe tener atributo frutado. En la cata profesional pueden identificarse defectos relacionados, por ejemplo, con fermentaciones o una conservación inadecuada de la materia prima o del aceite. La ausencia de esos defectos diferencia al virgen extra de categorías vírgenes inferiores.</p>
<p>Esto explica por qué la calidad empieza mucho antes de la botella. Aceitunas dañadas o almacenadas demasiado tiempo antes de molturar pueden iniciar procesos indeseados. Una extracción cuidada no puede convertir mágicamente una materia prima muy deteriorada en un AOVE excelente.</p>
<h2>Virgen extra, virgen y “aceite de oliva”: no son nombres intercambiables</h2>
<p>El aceite de oliva virgen es también un aceite obtenido por procedimientos propios de los aceites vírgenes, pero admite determinados defectos sensoriales y tiene un límite de acidez libre superior al del virgen extra. Por otra parte, la categoría que en el comercio aparece simplemente como “aceite de oliva” suele estar formada por aceite de oliva refinado mezclado con aceites de oliva vírgenes.</p>
<p>El refinado permite hacer aptos para el consumo aceites que, por sus características, no se comercializarían directamente como vírgenes. El resultado es una categoría diferente, con otra lógica de producción. No es correcto llamar “virgen extra” a cualquier aceite que procede del olivo.</p>
<h2>El color no determina si un aceite es virgen extra</h2>
<p>Un aceite intensamente verde puede parecer visualmente más “premium”, pero el color no constituye por sí mismo un criterio de clasificación de virgen extra. Depende de pigmentos y de múltiples factores ligados a variedad y madurez. De hecho, en cata profesional se utilizan recipientes que evitan que el color condicione al catador.</p>
<p>Por eso conviene desconfiar de conclusiones como “cuanto más verde, mejor”. Un AOVE dorado puede ser excelente y uno verde puede no gustarnos o incluso presentar problemas que el color no revela.</p>
<h2>Amargor y picor tampoco son defectos automáticos</h2>
<p>En aceites de determinadas variedades y cosechas, amargor y picor pueden ser atributos naturales y positivos dentro de un perfil equilibrado. Quien empieza a consumir AOVE intenso a veces interpreta el picor como una señal de que “está fuerte” o “se ha pasado”. Hay que distinguir una sensación varietal limpia de un defecto sensorial.</p>
<p>Esta es una de las razones por las que conocer variedades ayuda. Una Arbequina suele expresarse de forma más amable que un Picual verde; ambos pueden ser AOVE impecables. Nuestra guía de <a href="https://www.elmercadodeorigen.com/picual-arbequina-lechin-que-aove-elegir/">Picual, Arbequina y Lechín</a> desarrolla esas diferencias.</p>
<h2>Qué mirar en una botella además de “virgen extra”</h2>
<ul>
<li><strong>La denominación exacta:</strong> confirma que realmente es aceite de oliva virgen extra.</li>
<li><strong>Origen y productor:</strong> ayudan a entender quién responde del producto y de dónde procede.</li>
<li><strong>Variedad o coupage:</strong> cuando se indica, anticipa parte del perfil sensorial.</li>
<li><strong>Formato y envase:</strong> la protección frente a luz, calor y aire importa durante el uso.</li>
<li><strong>Fecha de consumo preferente y campaña cuando se facilite:</strong> el aceite evoluciona; no mejora indefinidamente en la botella.</li>
<li><strong>Condiciones de conservación:</strong> deben respetarse también después de abrirlo.</li>
</ul>
<h2>Ser AOVE no lo hace inmune al tiempo</h2>
<p>Un virgen extra puede deteriorarse después de envasado. Oxígeno, luz, calor y tiempo favorecen la oxidación y la pérdida de aromas. Por eso tiene sentido comprar un formato acorde con el consumo real y almacenarlo bien. Una familia que cocina a diario puede terminar una garrafa grande con rapidez; un hogar que utiliza muy poco aceite quizá conserve mejor el producto en formatos menores.</p>
<p>En nuestra guía sobre <a href="https://www.elmercadodeorigen.com/aove-garrafa-5-litros-cuando-compensa-como-conservar/">AOVE de 5 litros</a> explicamos precisamente cuándo compensa el formato y cómo reducir su exposición durante el uso.</p>
<h2>La categoría es el principio, no el final de la elección</h2>
<p>Buscar “virgen extra” es una buena primera criba porque establece una categoría concreta. A partir de ahí empieza la parte gastronómica: variedad, intensidad, cosecha, formato y productor. Esa es la diferencia entre comprar una etiqueta y elegir un aceite que realmente encaja con nuestra cocina.</p>
<p>Puedes comparar los formatos de 1957 en nuestra <a href="{$urls['oil']}">selección de aceites de oliva virgen extra</a>.</p>
<h2>Fuente técnica</h2>
<p>Para la explicación de categorías hemos utilizado la información de la <a href="{$eu_olive}">Comisión Europea sobre las categorías de aceite de oliva</a>, que resume para el virgen extra la ausencia de defectos sensoriales, la presencia de frutado y el límite de acidez libre de 0,8 %, entre otros requisitos regulatorios.</p>
<h2>Productos relacionados</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
'en_content'=><<<HTML
<!-- EMDO_AUTHORITY_BATCH1:what-evoo-means -->
<p>“Extra virgin olive oil” is now such a familiar phrase that it is easy to read it as little more than “good olive oil”. Technically it is far more precise. <strong>Extra virgin is a regulated commercial category</strong>, not a superlative that a producer can simply add to a label. To qualify, the oil must be produced from olives using the processes allowed for virgin oils and must meet defined analytical and sensory criteria.</p>
<p>Understanding this changes the way you shop. EVOO gives you an important baseline about the type of oil in the bottle, but it does not by itself tell you variety, intensity, harvest timing, producer, freshness or whether the flavour suits you. Two EVOOs can be dramatically different while both legitimately belonging to the same category.</p>
<h2>“Virgin” first describes how the oil is obtained</h2>
<p>Virgin olive oils are obtained directly from olives by mechanical or physical means under conditions that do not alter the oil, without using the refining processes applied to correct oils in other categories. The starting point is therefore the olive fruit and a physical extraction process.</p>
<p>In a modern mill, a simplified sequence includes receiving and cleaning the fruit, crushing, malaxing the paste, separating the oil by centrifugation and then managing the fresh oil. Filtration or settling can alter appearance and development, but <strong>both filtered and unfiltered oils can qualify as extra virgin</strong> if they meet the category requirements.</p>
<h2>“Extra” requires more than simply coming out of a mill</h2>
<p>Not every mechanically extracted oil automatically becomes extra virgin. Classification requires both physico-chemical and organoleptic criteria. The European Commission describes EVOO as the highest organoleptic quality category among virgin olive oils sold to consumers: it must have no sensory defects and must be fruity, in addition to complying with the relevant analytical limits.</p>
<p>The best-known figure is <strong>free acidity, which for extra virgin olive oil may not exceed 0.8%</strong>, expressed as oleic acid. Reducing quality to that single number would nevertheless be a mistake. Other analytical parameters apply, and sensory assessment is also essential to classification.</p>
<h2>Free acidity is not an acidic taste</h2>
<p>This is probably the most common misunderstanding. Free acidity is a chemical parameter measured in a laboratory. An oil at 0.2% does not necessarily “taste less acidic” than one at 0.7%, and the figure cannot be determined by tasting the oil at home.</p>
<p>Statements such as “it is very mild, so it must have very low acidity” mix two different things. Mildness, bitterness, pungency and fruitiness are sensory perceptions; free acidity is an analytical measurement.</p>
<h2>Sensory assessment also determines the category</h2>
<p>To qualify as extra virgin, an oil must show no sensory defects in the regulatory assessment and must have fruitiness. Professional tasting can identify defects associated, for example, with fermentation or poor handling of olives or oil. The absence of such defects is part of what separates extra virgin from lower virgin categories.</p>
<p>This is why quality begins long before bottling. Damaged olives or fruit stored too long before milling can undergo undesirable changes. Careful extraction cannot magically turn severely compromised raw material into excellent EVOO.</p>
<h2>Extra virgin, virgin and “olive oil” are not interchangeable names</h2>
<p>Virgin olive oil is also obtained using the processes characteristic of virgin oils, but it may contain certain sensory defects and has a higher permitted free-acidity limit than extra virgin. The category sold simply as “olive oil”, meanwhile, is generally a blend of refined olive oil and virgin olive oils.</p>
<p>Refining makes oils that are not suitable for direct sale as virgin oil usable in a different commercial category. The resulting product follows a different production logic. Not every oil that comes from an olive can correctly be called extra virgin.</p>
<h2>Colour does not determine whether an oil is extra virgin</h2>
<p>A deeply green oil can look visually more “premium”, but colour is not by itself a criterion for extra-virgin classification. It depends on pigments and many factors related to olive variety and maturity. Professional sensory practice deliberately avoids allowing colour to bias the taster.</p>
<p>So “the greener the better” is not a reliable buying rule. A golden-toned EVOO can be excellent, while a green oil can still have characteristics that colour alone cannot reveal.</p>
<h2>Bitterness and pungency are not automatically defects either</h2>
<p>In oils from certain varieties and harvest styles, bitterness and pungency can be natural, positive attributes when they are clean and balanced. Someone new to assertive EVOO may interpret pungency as evidence that the oil is “too strong” or has deteriorated. It is important to distinguish a clean varietal sensation from a sensory defect.</p>
<p>This is one reason varietal knowledge is useful. Arbequina is commonly gentler than a green-style Picual, yet both can be impeccable EVOOs. Our guide to <a href="https://www.elmercadodeorigen.com/en/picual-arbequina-lechin-which-evoo-to-choose/">Picual, Arbequina and Lechín</a> explores those differences.</p>
<h2>What to look for on the bottle beyond “extra virgin”</h2>
<ul>
<li><strong>Exact category:</strong> confirm that it really is extra virgin olive oil.</li>
<li><strong>Origin and producer:</strong> they help explain who is responsible for the product and where it comes from.</li>
<li><strong>Variety or blend:</strong> when provided, this gives useful clues to sensory style.</li>
<li><strong>Format and packaging:</strong> protection from light, heat and air matters during use.</li>
<li><strong>Best-before information and harvest when supplied:</strong> olive oil evolves; it does not improve indefinitely in the bottle.</li>
<li><strong>Storage instructions:</strong> these remain important after opening.</li>
</ul>
<h2>EVOO is not immune to time</h2>
<p>An extra virgin oil can deteriorate after bottling. Oxygen, light, heat and time promote oxidation and loss of aroma. It therefore makes sense to buy a format that matches real household consumption and to store it properly. A family cooking daily may use a large container quickly; a household using little oil may preserve freshness more easily with smaller packs.</p>
<p>Our guide to <a href="https://www.elmercadodeorigen.com/en/extra-virgin-olive-oil-5-litre-container-storage-guide/">5-litre EVOO</a> explains when the large format makes sense and how to reduce exposure during use.</p>
<h2>The category is the beginning of the choice, not the end</h2>
<p>Looking for “extra virgin” is an excellent first filter because it identifies a specific category. From there the gastronomic decision begins: variety, intensity, harvest, format and producer. That is the difference between buying a label and choosing an oil that genuinely fits your kitchen.</p>
<p>You can compare 1957's available formats in our <a href="{$urls['oil_en']}">extra virgin olive oil selection</a>.</p>
<h2>Technical source</h2>
<p>For the explanation of categories we used the <a href="{$eu_olive}">European Commission's information on olive-oil categories</a>, which summarises for extra virgin olive oil the absence of sensory defects, the presence of fruitiness and the maximum free-acidity level of 0.8%, among other regulatory requirements.</p>
<h2>Related products</h2>
[products category="aceites" limit="4" columns="4" orderby="popularity" order="DESC"]
HTML,
),
);

$report = array( 'batch' => 1, 'release' => '20260821', 'posts' => array() );
foreach ( $articles as $a ) {
    $post_id = emdo_ab1_post_id( $a['key'], $a['slug'] );
    $data = array(
        'post_type' => 'post', 'post_status' => 'publish', 'post_author' => $author_id,
        'post_title' => $a['title'], 'post_name' => $a['slug'], 'post_excerpt' => $a['excerpt'], 'post_content' => $a['content'],
    );
    if ( $post_id > 0 ) { $data['ID'] = $post_id; $result = wp_update_post( $data, true ); }
    else { $result = wp_insert_post( $data, true ); }
    if ( is_wp_error( $result ) ) { throw new RuntimeException( $a['key'] . ': ' . $result->get_error_message() ); }
    $post_id = (int) $result;
    wp_set_post_categories( $post_id, array( $guide_cat, $cats[ $a['topic'] ] ), false );
    update_post_meta( $post_id, '_emdo_authority_key', $a['key'] );
    update_post_meta( $post_id, '_emdo_authority_batch', '1' );
    update_post_meta( $post_id, '_en_US_post_title', $a['en_title'] );
    update_post_meta( $post_id, '_en_US_post_name', sanitize_title( $a['en_slug'] ) );
    update_post_meta( $post_id, '_en_US_post_excerpt', $a['en_excerpt'] );
    update_post_meta( $post_id, '_en_US_post_content', $a['en_content'] );
    update_post_meta( $post_id, '_en_US_ready', '1' );
    update_post_meta( $post_id, '_en_US_published', '1' );
    $image_id = emdo_ab1_image( $post_id, $a['image'] );
    $meta = wp_get_attachment_metadata( $image_id );
    $report['posts'][] = array(
        'key' => $a['key'], 'id' => $post_id, 'status' => get_post_status( $post_id ),
        'slug' => get_post_field( 'post_name', $post_id ), 'en_slug' => get_post_meta( $post_id, '_en_US_post_name', true ),
        'words_es' => emdo_ab1_words( $a['content'] ), 'words_en' => emdo_ab1_words( $a['en_content'] ),
        'image_id' => $image_id, 'image_w' => (int) ( $meta['width'] ?? 0 ), 'image_h' => (int) ( $meta['height'] ?? 0 ),
        'image_source' => $a['image']['page'],
    );
}
wp_cache_flush();
echo wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) . PHP_EOL;
