<?php
/** Reviewed, individually authored Latitudes descriptions, first 18 products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$supplier_table = MDO_Database::table( 'suppliers' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $supplier || '1' !== (string) get_user_meta( (int) $supplier['vendor_user_id'], '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Latitudes must remain disabled.' );
}
$reviews = array(
    518 => '<p>El foie de pato en pieza permite trabajar el hígado como ingrediente principal, desde porciones marcadas en sartén hasta elaboraciones que requieren un corte limpio. El formato aproximado de 600 a 700 g resulta adecuado cuando se busca preparar varias raciones a partir de una sola pieza. No debe confundirse con un paté o un foie gras ya cocinado: son presentaciones diferentes que exigen usos distintos.</p>',
    519 => '<p>El magret es la pechuga del pato y destaca por su capa de grasa exterior, que se puede dorar de forma gradual antes de terminar la cocción de la carne. Esta pieza ronda los 400 g y se entrega envasada al vacío y congelada, según la ficha de Latitudes. El proveedor declara como ingrediente pechuga de pato y señala que no contiene alérgenos declarados.</p>',
    520 => '<p>Este surtido de medallones reúne distintas carnes para probarlas en una misma comida y comparar sus matices sin comprar piezas grandes de cada tipo. Latitudes destaca el contraste entre el avestruz y el bisonte como parte de la experiencia de degustación. El formato de unos 280 g encaja en una cata de pequeñas porciones, cocinadas por separado para respetar las particularidades de cada carne.</p>',
    521 => '<p>Las ancas de rana de calibre 16-20 se presentan aquí en un paquete de aproximadamente 250 g. Su carne tiene una textura compacta y un sabor delicado, por lo que funcionan bien en preparaciones sencillas que no oculten el producto, como un salteado con ajo o una salsa suave. El calibre permite identificar el formato comercial sin confundirlo con la cantidad incluida en el paquete.</p>',
    522 => '<p>Esta caja de aproximadamente 1 kg reúne ancas de rana de calibre 16-20, una opción para cocinar varias raciones o una preparación compartida. Frente al paquete pequeño, el interés de este formato está en la cantidad disponible, no en un corte diferente. La carne es de textura compacta y sabor suave; admite elaboraciones breves en sartén y recetas con salsas ligeras.</p>',
    523 => '<p>Filete de alce procedente de la zona de cadera o babilla, presentado como ración cercana a los 200 g. Latitudes describe esta carne como tierna, de color rojo intenso y con un sabor suave que puede recordar a la ternera. Por tratarse de una pieza para cocinado rápido, conviene ajustar el tiempo a su grosor y evitar una cocción prolongada que perjudique la textura.</p>',
    524 => '<p>La cadera de canguro se ofrece en un corte de aproximadamente 700 a 800 g, apropiado para porcionar en filetes o medallones antes de cocinar. Es una carne de sabor más marcado que la ternera y con matices que el productor relaciona con la caza. Su textura suave permite preparaciones a la plancha o sartén, ajustando el punto de cocción al tamaño de cada porción.</p>',
    525 => '<p>Estas croquetas combinan carne de avestruz con mango, una mezcla que introduce un contrapunto afrutado en una elaboración salada. Latitudes las presenta como una especialidad de Musée Gourmet y destaca su textura suave. El envase de unos 500 g resulta práctico para servir pequeñas raciones como entrante o para compartir en un aperitivo.</p>',
    526 => '<p>La versión de 1 kg de las croquetas de avestruz y mango se suministra, según Latitudes, en dos envases de 500 g. Mantiene la combinación de carne de avestruz y fruta de la referencia más pequeña, pero facilita preparar una mesa con más comensales o utilizar los paquetes por separado. El mango aporta el contraste de sabores que distingue esta elaboración de una croqueta de carne convencional.</p>',
    527 => '<p>Carne de cocodrilo, mandarina y eneldo se combinan en estas croquetas de unos 500 g, elaboradas dentro de la selección de Musée Gourmet. La mandarina introduce una nota cítrica y el eneldo un perfil aromático diferente al de los rebozados de carne tradicionales. Es una preparación pensada para ofrecer pequeñas porciones sin necesidad de trabajar un corte entero de cocodrilo.</p>',
    528 => '<p>El formato de aproximadamente 1 kg de las croquetas de cocodrilo con mandarina y eneldo incluye dos paquetes de 500 g, según la descripción del productor. Conserva el equilibrio entre la carne, la nota cítrica de la mandarina y el eneldo, con una textura suave. Está pensado para quienes necesitan más cantidad de la misma especialidad sin depender de un único envase grande.</p>',
    529 => '<p>Entrecot de bisonte presentado en una ración cercana a los 200 g. Se trata de un corte para cocinar en sartén o parrilla, donde el grosor y el punto de cocción condicionan el resultado. Es una alternativa a los entrecots de vacuno más habituales para quien desea descubrir una carne de otra especie sin comprar una pieza de gran formato.</p>',
    530 => '<p>Esta ración de entrecot de camello, de unos 200 g, permite probar una carne poco frecuente en el recetario doméstico mediante un corte reconocible. Puede prepararse a la plancha o a la parrilla, prestando atención al grosor y al punto para no resecar la pieza. Latitudes lo ofrece dentro de su selección de carnes exóticas.</p>',
    531 => '<p>Entrecot de potro en ración aproximada de 200 g, pensado para una preparación individual. El entrecot permite una cocción directa en sartén o parrilla y un corte posterior en lonchas para servirlo. La ficha de origen también utiliza la denominación «carne de caballo» para describir esta referencia; se conserva el nombre comercial del producto, potro, tal como figura en su título.</p>',
    532 => '<p>Filete de primera extra de avestruz en una ración aproximada de 200 g. Aunque procede de un ave, su carne es roja y ofrece una alternativa a los filetes habituales de vacuno. Su presentación en una porción individual facilita una cocción rápida en sartén o plancha, con especial atención al grosor de la pieza.</p>',
    533 => '<p>Este formato de aproximadamente 1 kg de filete de avestruz de primera extra permite preparar varias raciones a partir de la misma compra. La carne, de color rojo y textura tierna según Latitudes, recuerda en su aspecto al vacuno, aunque su sabor es distinto. Puede cortarse en filetes del tamaño deseado y cocinarse en preparaciones breves que respeten su textura.</p>',
    534 => '<p>Filete de bisonte obtenido de la cadera o la babilla, en una ración de unos 200 g. Latitudes describe una carne roja oscura, con grasa principalmente exterior a los músculos y una textura tierna. Su sabor comienza suave y gana intensidad al masticarlo, una característica interesante para degustarlo con acompañamientos poco dominantes.</p>',
    535 => '<p>Filete de cebra de primera, servido en una ración cercana a los 200 g. La ficha de Latitudes describe un perfil próximo a ciertas carnes de caza, con una nota algo más dulce que la del vacuno. Para apreciar el corte, una cocción breve y una guarnición sencilla permiten centrar la atención en su sabor y textura.</p>',
);
$updated = array();
foreach ( $reviews as $source_id => $description ) {
    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT id,wc_product_id,title,source_hash,source_url FROM {$source_table} WHERE id=%d AND supplier_id=%d",
        $source_id, (int) $supplier['id']
    ), ARRAY_A );
    if ( ! $row || ! str_starts_with( (string) $row['source_url'], 'https://latitudes.es/' ) ) {
        throw new RuntimeException( 'Source identity mismatch: ' . $source_id );
    }
    $product = wc_get_product( (int) $row['wc_product_id'] );
    if ( ! $product || $product->get_status() !== 'draft' || $product->get_catalog_visibility() !== 'hidden' ||
        (int) get_post_field( 'post_author', $product->get_id() ) !== (int) $supplier['vendor_user_id'] ||
        ! get_post_meta( $product->get_id(), '_emdo_latitudes_category_v1', true ) ) {
        throw new RuntimeException( 'Missing/visible/wrongly categorised product: ' . $source_id );
    }
    $existing = (string) get_post_meta( $product->get_id(), '_emdo_latitudes_editorial_description', true );
    if ( '' !== $existing && $existing !== $description ) {
        throw new RuntimeException( 'The existing approved editorial text differs: ' . $source_id );
    }
    if ( mb_strlen( trim( wp_strip_all_tags( $description ) ) ) < 170 ) {
        throw new RuntimeException( 'Editorial content too short: ' . $source_id );
    }
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_description', wp_kses_post( $description ) );
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_approved', '1' );
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_source_hash', (string) $row['source_hash'] );
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_version', '20261015-01' );
    $product->set_description( wp_kses_post( $description ) );
    $product->save();
    $reloaded = wc_get_product( $product->get_id() );
    if ( $reloaded->get_description() !== wp_kses_post( $description ) || $reloaded->get_status() !== 'draft' ||
        $reloaded->get_catalog_visibility() !== 'hidden' ) {
        throw new RuntimeException( 'Editorial persistence or privacy check failed: ' . $source_id );
    }
    $updated[] = array( 'source_id' => $source_id, 'wc_id' => $product->get_id(), 'chars' => mb_strlen( wp_strip_all_tags( $description ) ) );
}
echo 'LATITUDES_EDITORIAL_BATCH_01=' . wp_json_encode( array(
    'updated' => count( $updated ), 'items' => $updated, 'published' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
