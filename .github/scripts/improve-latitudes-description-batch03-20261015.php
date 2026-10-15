<?php
/** Reviewed, individually authored Latitudes descriptions, next 42 products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$supplier_table = MDO_Database::table( 'suppliers' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $supplier || '1' !== (string) get_user_meta( (int) $supplier['vendor_user_id'], '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Latitudes must remain disabled.' );
}
$reviews = array(
    565 => '<p>El huevo de avestruz se vende refrigerado y constituye una presentación poco habitual dentro de la selección de Latitudes. Por su tamaño, resulta especialmente interesante para preparaciones que deban repartirse entre varias personas, como una tortilla o un revuelto bien cuajado. Es un producto estacional que conviene conservar y cocinar según las indicaciones de su envase.</p>',
    566 => '<p>El estuche contiene dos huevos de oca, una alternativa a los huevos de gallina para recetas en las que el huevo es protagonista. Se pueden utilizar en tortillas, masas y elaboraciones al horno, ajustando la proporción de ingredientes al tamaño de cada unidad. La descripción original de Latitudes contiene un texto ajeno a este producto, por lo que se ha redactado esta ficha a partir de su identificación comercial.</p>',
    567 => '<p>Lomo de alce en un corte de aproximadamente 1 kg, pensado para obtener varias porciones de una misma pieza. Es un formato adecuado para dividir en filetes o medallones y controlar individualmente el punto de cocción. A diferencia del ragú, se trata de un corte entero cuya textura depende del grosor y del tiempo que permanezca al calor.</p>',
    568 => '<p>El lomo de alce en ración próxima a los 200 g permite probar esta carne sin adquirir una pieza grande. Latitudes la describe como tierna, de color rojo intenso y con un sabor relativamente suave. Su presentación en un corte de lomo se presta a una preparación sencilla en plancha o sartén, siempre ajustada al grosor de la ración.</p>',
    569 => '<p>Lomo de bisonte comercializado como corte cercano a 1 kg, apto para porcionar antes de cocinar o preparar como pieza mayor. La ficha de Latitudes destaca su color rojo oscuro y una grasa que se encuentra principalmente fuera del músculo. Su sabor se describe como progresivo, de entrada suave y mayor intensidad al final, por lo que los acompañamientos discretos ayudan a apreciarlo.</p>',
    570 => '<p>Esta ración de lomo de búfalo de unos 200 g ofrece un corte individual de una carne distinta de la de vacuno convencional. Puede prepararse a la sartén o a la parrilla, prestando atención al grosor para conservar una buena textura. La descripción que aparecía en el origen se refería erróneamente a Black Angus; la presente ficha utiliza únicamente la identificación comercial de búfalo.</p>',
    571 => '<p>El lomo de canguro en pieza de unos 700 a 800 g permite decidir el tamaño de los cortes antes del cocinado. En comparación con una ración individual, ofrece más flexibilidad para preparar varios medallones o servirlo loncheado. Como otras piezas magras, agradece un control cuidadoso del tiempo de cocción para no perder jugosidad.</p>',
    572 => '<p>Una ración de lomo de canguro de aproximadamente 200 g para descubrir esta carne en un corte limpio y fácil de preparar. Latitudes describe una textura tierna y un contenido graso reducido. Puede cocinarse a la plancha o en sartén, con una guarnición sencilla que permita comparar su sabor con el de otros cortes de la misma especie.</p>',
    573 => '<p>Lomo de cebra en una porción próxima a 200 g, pensado para un plato individual. Según la descripción de Latitudes, su sabor recuerda a las carnes de caza y presenta matices próximos a los de la carne equina. El formato resulta adecuado para un cocinado directo, sin necesidad de trabajar un ragú o una pieza entera.</p>',
    574 => '<p>El lomo de jabalí en un corte de unos 1 kg permite planificar varias raciones de una carne de caza con personalidad propia. Frente a las piezas destinadas a estofado, el lomo admite preparaciones en una pieza o en porciones. Por tratarse de carne de jabalí, debe cocinarse suficientemente y seguir las indicaciones de seguridad alimentaria, sin servirla cruda.</p>',
    575 => '<p>Esta ración de lomo de jabalí, cercana a 200 g, facilita preparar un plato individual con carne de caza. El lomo se distingue del ragú por su presentación como corte para porcionar o cocinar entero. Conviene asegurar una cocción completa y acompañarlo con sabores que no oculten el carácter propio de esta especie.</p>',
    576 => '<p>Corte de lomo de venado de aproximadamente 1 kg, para quienes prefieren porcionar una pieza de caza en casa. Permite obtener medallones de grosor uniforme o preparar una elaboración para compartir. El venado aporta un perfil de sabor diferente al del vacuno, y este formato ayuda a elegir el tipo de cocción según la receta.</p>',
    577 => '<p>El lomo de venado en ración de unos 200 g ofrece una opción individual para introducir carne de caza en una comida. Es un corte distinto de los dados para guiso y permite una presentación sencilla, cortado en lonchas tras la cocción. El tiempo y la temperatura deben ajustarse al grosor real de la pieza.</p>',
    578 => '<p>Ragú de avestruz en un corte aproximado de 1 kg, apropiado para recetas de cazuela en las que la carne se cocina con verduras y un fondo aromático. Aunque procede de un ave, la carne de avestruz es roja; Latitudes destaca además ciertos matices dulces. Esta presentación está orientada a platos diferentes del filete a la plancha.</p>',
    579 => '<p>El ragú de búfalo llega en una presentación cercana a 1 kg para preparar guisos y estofados. Latitudes identifica expresamente esta referencia como búfalo mediterráneo criado en España, envasado al vacío y congelado. Su uso en cazuela permite trabajar la carne con verduras y líquido de cocción hasta alcanzar la textura deseada.</p>',
    580 => '<p>Ragú de canguro en un formato aproximado de 1,2 a 1,3 kg para cocinar una receta compartida. Latitudes describe una carne roja oscura, tierna y con poca grasa visible. En una preparación de cazuela conviene conservar suficiente humedad y ajustar el tiempo a los trozos obtenidos de la pieza, en lugar de tratarla como un filete rápido.</p>',
    581 => '<p>Este ragú de canguro se ofrece en un formato mayor, de unos 2,5 kg, destinado a preparar varias raciones de una misma especialidad. Su interés está en permitir un guiso de mayor cantidad sin mezclar carnes diferentes. Puede combinarse con verduras y un fondo de cocción que ayude a conservar la jugosidad de una carne magra.</p>',
    582 => '<p>Ragú de cebra en un corte de aproximadamente 1,2 a 1,3 kg, orientado a guisos de carne. Latitudes describe un color rosado y un sabor con matices próximos a la caza y a la carne equina. La preparación en cazuela, con líquido suficiente y fuego moderado, permite desarrollar una receta distinta de sus filetes o lomos.</p>',
    583 => '<p>El formato de unos 2,5 kg de ragú de cebra resulta adecuado para preparar una cazuela de mayor tamaño y repartirla en varias comidas. Se trata de carne destinada a una elaboración con tiempo, no del corte de lomo para plancha. Su perfil gustativo, según Latitudes, combina notas que recuerdan a la caza y a la carne equina.</p>',
    584 => '<p>Ragú de cocodrilo en una bolsa de aproximadamente 1 kg, comercializada congelada y envasada al vacío según Latitudes. El productor describe una carne entre rosada y blanquecina, de sabor suave y con un ligero matiz que recuerda al pescado. Debe cocinarse completamente, utilizando una preparación adecuada para una carne de reptil.</p>',
    585 => '<p>El ragú de corzo de aproximadamente 1,2 a 1,3 kg ofrece una base para estofados de carne de caza. La ficha del proveedor lo identifica como carne de corzo, envasada al vacío y congelada. Un guiso con verduras y cocción controlada permite preparar varias raciones, sin confundir esta pieza con un solomillo destinado a otro tipo de elaboración.</p>',
    586 => '<p>Formato de unos 2,5 kg de ragú de corzo para preparar una cazuela de caza destinada a varios comensales. Latitudes lo comercializa envasado al vacío y congelado, con carne de corzo como ingrediente declarado. La cantidad permite cocinar una receta grande y repartirla después en raciones, prestando atención a su correcta conservación.</p>',
    587 => '<p>El ragú de gamo en corte de aproximadamente 1,2 a 1,3 kg se presta a estofados en los que la carne gana textura y sabor durante la cocción. Latitudes destaca que es una carne magra de gusto intenso. Puede combinarse con verduras, setas o un fondo aromático, evitando que la salsa resulte más dominante que el producto.</p>',
    588 => '<p>Esta presentación de ragú de gamo ronda los 2,5 kg y está pensada para preparar guisos de mayor cantidad. La carne de gamo tiene, según Latitudes, un sabor intenso y una textura agradable; su tratamiento en cazuela permite trabajarla con un fondo de cocción. Es un formato diferente del solomillo de gamo, más apropiado para otras técnicas.</p>',
    589 => '<p>Ragú de jabalí en un corte aproximado de 1,2 a 1,3 kg, una elección para estofados de caza con tiempo de cocción suficiente. Latitudes describe una carne aromática, de color rosado oscuro y textura consistente. Debe cocinarse por completo y manipularse respetando las indicaciones de seguridad alimentaria para carne de caza.</p>',
    590 => '<p>El ragú de jabalí en formato cercano a los 2,5 kg permite preparar un guiso abundante con una sola especie. Su sabor aromático y su textura consistente encajan en una cazuela con verduras y caldo. Como cualquier preparación de jabalí, requiere cocción completa; no es un producto destinado a servirse crudo o poco hecho.</p>',
    591 => '<p>Ragú de venado en corte de aproximadamente 1,2 a 1,3 kg para estofados de carne de caza. La ficha del proveedor señala su color oscuro y una textura musculada, acompañados de un sabor elegante. Una cocción con verduras y líquido suficiente permite trabajar la carne de manera distinta de un lomo para plancha.</p>',
    592 => '<p>Este ragú de venado ronda los 2,5 kg y está pensado para recetas de cazuela de mayor volumen. El venado aporta la intensidad característica de una carne de caza, mientras que el guiso permite ajustar su textura con tiempo y humedad. El formato es especialmente útil cuando se preparan varias raciones de una misma receta.</p>',
    593 => '<p>Solomillo de alce en ración individual de unos 200 g. Latitudes describe esta carne como tierna, de color rojo intenso y con un sabor suave, próximo al de algunos cortes de ternera. El solomillo permite un cocinado directo y una presentación limpia, siempre cuidando el grosor y el punto de la pieza.</p>',
    594 => '<p>El solomillo de avestruz en corte aproximado de 700 a 800 g permite preparar varias raciones a partir de una pieza. Aunque se trata de carne de ave, su color es rojo y su uso culinario puede acercarse al de determinados cortes de vacuno. Conviene dividirla de forma uniforme y adaptar los tiempos a las porciones elegidas.</p>',
    595 => '<p>Esta ración de solomillo de avestruz de unos 200 g está pensada para probar la carne roja de esta ave en un corte individual. Su formato facilita una preparación breve en sartén o plancha y una presentación en medallones. Puede acompañarse con verduras asadas o una salsa ligera que no tape el sabor de la carne.</p>',
    596 => '<p>Solomillo de bisonte en ración próxima a los 200 g, un corte especialmente adecuado para cocinar una porción individual. Se diferencia del ragú y del lomo grande por su presentación y por el menor trabajo de preparación necesario. El punto de cocción debe adaptarse al grosor, sin dar por hecho que todas las piezas son iguales.</p>',
    597 => '<p>El solomillo de búfalo de unos 200 g permite probar esta carne en un corte de presentación limpia. Latitudes describe el búfalo como una carne roja, sabrosa y con poco marmoleado visible. Para apreciar sus características, puede servirse con una guarnición discreta, ajustando el cocinado al grosor de la ración.</p>',
    598 => '<p>Solomillo de cocodrilo en corte cercano a 500 g, un formato que permite dividir una carne poco habitual en varias porciones. El proveedor asocia su perfil gustativo a un sabor suave con matices próximos al pescado. La descripción antigua mencionaba por error el antílope; esta ficha se ha corregido para identificar el producto como cocodrilo y recomendar una cocción completa.</p>',
    599 => '<p>La ración de solomillo de cocodrilo de unos 200 g ofrece una alternativa al ragú de la misma especie. Latitudes destaca su sabor suave y un ligero recuerdo al pescado. Puede prepararse en porciones de tamaño uniforme, pero debe quedar completamente cocinada y manipularse conforme a las pautas de higiene alimentaria.</p>',
    600 => '<p>El solomillo de corzo se comercializa en una pieza aproximada de 900 g a 1 kg, apta para dividir en varias raciones. Latitudes señala que se presenta al vacío y congelado, con solomillo de corzo como ingrediente. Es un corte diferente del ragú para estofado y permite preparaciones en porciones más definidas.</p>',
    601 => '<p>Solomillo de gamo en un corte de unos 800 a 900 g, para quienes buscan una pieza de caza que puedan porcionar antes de cocinar. Latitudes describe la carne de gamo como magra y de sabor intenso. La preparación puede centrarse en el corte mismo, con verduras o una salsa breve que acompañe sin dominar.</p>',
    602 => '<p>Solomillo de jabalí en una pieza aproximada de 1 kg, adecuado para obtener varias raciones de carne de caza. Su presentación como corte entero lo diferencia de los trozos para guiso. El jabalí debe cocinarse suficientemente por motivos de seguridad alimentaria, siguiendo las indicaciones correspondientes a esta especie.</p>',
    603 => '<p>La ración individual de solomillo de jabalí de unos 200 g permite preparar un plato de carne de caza sin adquirir un corte entero. Su tamaño ayuda a controlar el servicio y acompañarlo con una guarnición sencilla. Debe cocinarse por completo; no es recomendable presentarlo crudo ni poco hecho.</p>',
    604 => '<p>Solomillo de venado en corte aproximado de 1 kg, para preparar varias raciones de esta carne de caza. Puede dividirse en piezas de grosor similar y servirse con verduras, setas o un acompañamiento que no oculte su sabor. Es una opción distinta del ragú, tanto por presentación como por el tipo de preparación que permite.</p>',
    605 => '<p>Esta ración de solomillo de venado de unos 200 g ofrece una pieza individual de carne de caza con un corte definido. Permite realizar una preparación sencilla y presentar la carne en lonchas una vez cocinada. El formato evita tener que dividir una pieza grande cuando se busca probar esta especialidad por primera vez.</p>',
    606 => '<p>Dos piezas de triángulo de avestruz de unos 200 g cada una forman esta referencia de Latitudes, identificada comercialmente como procedente de Sudáfrica. Es una carne roja de ave, de sabor característico y con matices que el proveedor describe como ligeramente dulces. El formato de dos unidades permite cocinar porciones separadas y compararlas con otros cortes de avestruz.</p>',
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
    $original_record = $wpdb->get_var( $wpdb->prepare(
        "SELECT source_payload FROM {$source_table} WHERE id=%d AND supplier_id=%d", $source_id, (int) $supplier['id']
    ) );
    $source_payload = json_decode( (string) $original_record, true ) ?: array();
    $source_note = trim( html_entity_decode( wp_strip_all_tags( (string) ( $source_payload['description'] ?? '' ) ), ENT_QUOTES | ENT_HTML5 ) );
    // The original allergen/ingredient declaration is never paraphrased:
    // retain the exact technical statement alongside unique editorial prose.
    if ( preg_match( '/Al[eé]rgenos?\s*:/iu', $source_note ) ) {
        $source_note = preg_replace( '/^\s*Descripci[oó]n\s*/iu', '', $source_note );
        $description .= '<p><strong>Información sobre preparación, ingredientes y alérgenos publicada por Latitudes:</strong> '
            . esc_html( $source_note ) . '</p>';
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
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_version', '20261015-03' );
    $product->set_description( wp_kses_post( $description ) );
    $product->save();
    $reloaded = wc_get_product( $product->get_id() );
    if ( $reloaded->get_description() !== wp_kses_post( $description ) || $reloaded->get_status() !== 'draft' ||
        $reloaded->get_catalog_visibility() !== 'hidden' ) {
        throw new RuntimeException( 'Editorial persistence or privacy check failed: ' . $source_id );
    }
    $updated[] = array( 'source_id' => $source_id, 'wc_id' => $product->get_id(), 'chars' => mb_strlen( wp_strip_all_tags( $description ) ) );
}
echo 'LATITUDES_EDITORIAL_BATCH_03=' . wp_json_encode( array(
    'updated' => count( $updated ), 'items' => $updated, 'published' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
