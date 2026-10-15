<?php
/** Reviewed, individually authored Latitudes descriptions, next 29 products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$supplier_table = MDO_Database::table( 'suppliers' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $supplier || '1' !== (string) get_user_meta( (int) $supplier['vendor_user_id'], '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Latitudes must remain disabled.' );
}
$reviews = array(
    536 => '<p>Los fingers de avestruz ofrecen la carne de esta ave en una presentación pensada para servir porciones pequeñas. El paquete de unos 500 g puede utilizarse en una comida informal o como entrante compartido. Antes de prepararlos es importante atender a la información de alérgenos publicada por Latitudes, distinta de la que corresponde a un corte de avestruz sin elaborar.</p>',
    537 => '<p>Este formato de aproximadamente 1 kg de fingers de avestruz está pensado para quienes necesitan más cantidad de un elaborado de carne roja de ave. Permite organizar varias raciones y servirlo como entrante o dentro de un surtido de aperitivos calientes. A diferencia del filete de avestruz, se trata de un producto preparado que requiere revisar su lista de ingredientes y alérgenos.</p>',
    538 => '<p>Los fingers de canguro de 500 g presentan una carne exótica en pequeñas porciones, más sencillas de repartir que un corte entero de cadera o lomo. Funcionan como aperitivo o segundo plato acompañado de una guarnición ligera. La ficha original indica que se venden congelados y contiene advertencias de alérgenos que deben consultarse antes de cocinarlos.</p>',
    539 => '<p>La presentación de 1 kg de fingers de canguro permite preparar varias raciones de esta especialidad sin tener que partir una pieza grande de carne. Es una opción para mesas compartidas y degustaciones de elaborados exóticos. Latitudes señala condiciones de conservación en congelación y advertencias de alérgenos propias del producto preparado.</p>',
    540 => '<p>Los fingers de cocodrilo se ofrecen en un paquete cercano a los 500 g dentro de la selección de Musée Gourmet comercializada por Latitudes. Esta presentación transforma una carne poco habitual en pequeñas porciones fáciles de servir, una alternativa a probarla en filetes o solomillos. Se recomienda consultar las instrucciones de preparación y conservación del envase.</p>',
    541 => '<p>El formato de 1 kg de fingers de cocodrilo llega, según la ficha de Latitudes, repartido en dos paquetes de 500 g. Esto facilita dividir el consumo en diferentes ocasiones sin cambiar de especialidad. La propuesta de Musée Gourmet está orientada a degustar una carne exótica en forma de aperitivo, distinta de los cortes frescos para plancha.</p>',
    542 => '<p>Esta burger meat de alce de 150 g permite preparar una hamburguesa con carne de una especie diferente del vacuno habitual. Latitudes la comercializa aliñada, envasada al vacío y congelada, lista para cocinar una vez manipulada conforme a las instrucciones del envase. No es carne picada sin ingredientes añadidos: el productor declara condimentos y sulfitos.</p>',
    543 => '<p>El pack de burger meat de alce reúne dos porciones de 150 g, adecuado para preparar dos hamburguesas de la misma especialidad. La mezcla de carne y condimentos se vende ya aliñada y congelada, por lo que conviene seguir las indicaciones del fabricante antes de llevarla a la parrilla o a la plancha. La ficha declara presencia de sulfitos.</p>',
    544 => '<p>Dos unidades de burger meat de avestruz de 150 g permiten comparar una hamburguesa elaborada con carne de ave roja frente a las de vacuno tradicionales. El producto se presenta ya aliñado y congelado, preparado para cocinarse a la parrilla o a la plancha. Al tratarse de una mezcla condimentada, debe consultarse la información específica de ingredientes y sulfitos.</p>',
    545 => '<p>Burger meat de bisonte en porción comercial de 150 g, pensada para elaborar una hamburguesa con una especie distinta del vacuno convencional. Latitudes describe una mezcla de carne, condimentos y otros ingredientes, envasada al vacío y congelada. Su composición incluye sulfitos, por lo que no debe confundirse con un filete de bisonte sin elaborar.</p>',
    546 => '<p>El pack de dos burger meat de bisonte de 150 g ofrece un formato pensado para cocinar dos raciones de la misma carne. Son elaborados aliñados que pueden prepararse a la plancha o parrilla, según el productor. La lista de ingredientes de Latitudes señala aditivos y sulfitos; esta información debe acompañar siempre a la descripción comercial.</p>',
    547 => '<p>Burger meat de búfalo de 150 g, presentada como una alternativa a la hamburguesa elaborada con vacuno más común. Esta referencia se comercializa condimentada y congelada, lista para cocinar conforme a las indicaciones del envase. El producto contiene otros ingredientes además de carne de búfalo, incluidos sulfitos declarados por Latitudes.</p>',
    548 => '<p>Este pack de dos burger meat de búfalo de 150 g permite preparar dos hamburguesas con la misma mezcla aliñada. La preparación resulta práctica para cocinar ambas piezas a la vez en sartén, plancha o parrilla. No debe presentarse como carne de búfalo pura: la descripción del productor incluye ingredientes adicionales y advertencia de sulfitos.</p>',
    549 => '<p>La burger meat de canguro de 150 g lleva una carne que también se vende en cortes enteros, pero aquí aparece en una preparación aliñada. El formato individual sirve para preparar una hamburguesa sin necesidad de picar el lomo o la cadera. Latitudes la comercializa envasada al vacío y congelada, con ingredientes y sulfitos declarados.</p>',
    550 => '<p>Dos burger meat de canguro de 150 g forman este pack para cocinar dos raciones. Son piezas elaboradas y condimentadas, pensadas para pasar directamente a una preparación en plancha o parrilla tras seguir las instrucciones de manipulación y conservación. La información de Latitudes incluye una advertencia de sulfitos que no debe omitirse.</p>',
    551 => '<p>El pack de burger meat de cebra contiene dos porciones de 150 g de una mezcla elaborada con esta carne exótica. Es una forma distinta de probar la especie respecto a sus filetes y ragús, y resulta cómoda para cocinar dos hamburguesas de tamaño equivalente. Antes de servir, es importante tener en cuenta los ingredientes añadidos y los sulfitos indicados por Latitudes.</p>',
    552 => '<p>Burger meat de cocodrilo de 150 g, presentada como una preparación condimentada para cocinar a la plancha o a la parrilla. Permite probar esta carne en formato de hamburguesa, sin necesidad de cortar o trabajar un solomillo entero. Como ocurre con los demás elaborados de la gama, la composición del productor incluye ingredientes adicionales y requiere revisar los alérgenos declarados.</p>',
    553 => '<p>El pack de dos burger meat de cocodrilo de 150 g es una propuesta para compartir o preparar dos raciones de una carne poco habitual. Latitudes comercializa estas hamburguesas elaboradas y congeladas, con instrucciones para cocinarlas en plancha o parrilla. La lista original de ingredientes y alérgenos debe consultarse al tratarse de una mezcla, no de un corte fresco.</p>',
    554 => '<p>La burger meat de alce en formato de unos 500 g facilita preparar varias porciones de una mezcla cárnica ya aliñada. Frente a la unidad de 150 g, esta presentación permite ajustar el tamaño de las hamburguesas a cada comida. Latitudes declara condimentos y aditivos, además de sulfitos, y especifica su conservación congelada.</p>',
    555 => '<p>El paquete de aproximadamente 500 g de burger meat de avestruz permite trabajar una preparación de carne roja de ave sin comprar filetes enteros. Es útil para formar hamburguesas de distintos tamaños o repartir la mezcla entre varias raciones. Al estar aliñada, su composición no equivale a carne de avestruz sola: Latitudes especifica ingredientes y sulfitos.</p>',
    556 => '<p>Esta burger meat de bisonte de 500 g ofrece más flexibilidad para dividir una mezcla de carne condimentada en varias hamburguesas. La selección permite probar bisonte en una elaboración distinta del entrecot o el filete. La ficha del proveedor indica envasado al vacío, congelación y presencia de sulfitos en la lista de alérgenos.</p>',
    557 => '<p>Burger meat de búfalo en formato de 500 g, pensada para porcionar antes de cocinar y adaptar el tamaño de las hamburguesas. Se trata de carne condimentada, una preparación diferente del lomo o el solomillo de búfalo de la misma selección. Latitudes publica una lista de ingredientes con aditivos y declara sulfitos.</p>',
    558 => '<p>Elaborado de carne de camello de aproximadamente 500 g para preparar hamburguesas y explorar esta especie en una receta conocida. La mezcla se presenta aliñada, por lo que no equivale a un corte de camello entero. Antes de cocinarla es importante respetar las indicaciones de conservación y revisar los ingredientes y alérgenos publicados por Latitudes.</p>',
    559 => '<p>La burger meat de canguro de 500 g permite moldear o repartir una mezcla aliñada en varias raciones, en lugar de trabajar con un corte de cadera o lomo. Es una alternativa para quien desea probar esta carne en una preparación familiar. Como elaborado comercial, incorpora ingredientes adicionales y requiere consultar la advertencia de sulfitos.</p>',
    560 => '<p>Burger meat de cebra en un paquete cercano a los 500 g, pensada para formar hamburguesas del tamaño deseado. Frente al filete de primera de la misma especie, esta referencia es una mezcla de carne y condimentos. Latitudes indica que el producto se distribuye congelado y detalla ingredientes y alérgenos específicos.</p>',
    561 => '<p>La burger meat de cocodrilo de 500 g permite preparar varias hamburguesas con una especie que también se comercializa en filetes y solomillos. Su formato elaborado simplifica el reparto en porciones y ofrece otra manera de servir esta carne. La información del proveedor debe consultarse para conocer la composición, conservación y presencia de alérgenos.</p>',
    562 => '<p>La burger meat de gamo de unos 500 g acerca una carne de caza a un formato familiar, que puede dividirse en varias hamburguesas antes de cocinar. Permite disfrutar de gamo sin trabajar una pieza para guisar o asar. Es importante distinguir la carne original de esta preparación comercial, que contiene condimentos y otros ingredientes especificados por Latitudes.</p>',
    563 => '<p>Este paquete de unos 500 g de burger meat de jabalí facilita elaborar hamburguesas con una carne habitual en guisos de caza, pero presentada aquí como mezcla condimentada. Puede porcionarse según las necesidades de la comida y cocinarse a la plancha o parrilla. La composición publicada por el proveedor debe acompañar la ficha, especialmente sus alérgenos.</p>',
    564 => '<p>Burger meat de venado en formato de aproximadamente 500 g, una manera de preparar esta carne de caza en hamburguesas sin recurrir a un ragú o un asado. La mezcla se comercializa aliñada y permite decidir el tamaño de cada porción. Conviene consultar los ingredientes añadidos, las condiciones de conservación y los alérgenos que declara Latitudes.</p>',
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
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_version', '20261015-02' );
    $product->set_description( wp_kses_post( $description ) );
    $product->save();
    $reloaded = wc_get_product( $product->get_id() );
    if ( $reloaded->get_description() !== wp_kses_post( $description ) || $reloaded->get_status() !== 'draft' ||
        $reloaded->get_catalog_visibility() !== 'hidden' ) {
        throw new RuntimeException( 'Editorial persistence or privacy check failed: ' . $source_id );
    }
    $updated[] = array( 'source_id' => $source_id, 'wc_id' => $product->get_id(), 'chars' => mb_strlen( wp_strip_all_tags( $description ) ) );
}
echo 'LATITUDES_EDITORIAL_BATCH_02=' . wp_json_encode( array(
    'updated' => count( $updated ), 'items' => $updated, 'published' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
