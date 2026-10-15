<?php
/** Reviewed, individually authored Latitudes descriptions, final 49 premium products. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$source_table = MDO_Database::table( 'source_products' );
$supplier_table = MDO_Database::table( 'suppliers' );
$supplier = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$supplier_table} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $supplier || '1' !== (string) get_user_meta( (int) $supplier['vendor_user_id'], '_disable_vendor', true ) ) {
    throw new RuntimeException( 'Latitudes must remain disabled.' );
}
$reviews = array(
    607 => '<p>Las delicias de wagyu japonés A5 se presentan en porciones finas de aproximadamente 100 g, elaboradas a partir de cortes que Latitudes identifica como aguja, cadera y chuck short rib. Es una selección orientada a cocinar pequeñas cantidades y apreciar la infiltración de grasa sin trabajar una pieza grande. El grado A5 corresponde a la clasificación comercial indicada por el proveedor.</p>',
    608 => '<p>Medallones de wagyu japonés anunciados como A5 en un formato próximo a 150 g. Según Latitudes proceden de cadera y picanha, dos zonas que permiten obtener porciones pequeñas para una cocción directa. La presentación facilita servir el wagyu como degustación y comparar su marmoleado con el de cortes de origen australiano.</p>',
    609 => '<p>El ribete de wagyu japonés A5, de unos 150 g, reúne filetes procedentes de zonas que Latitudes identifica como centro de cadera, tapa y aguja. Pueden prepararse enteros o dividirse en bocados más pequeños para una degustación. El interés de esta referencia está tanto en el formato ya cortado como en la infiltración grasa característica del wagyu de alta clasificación.</p>',
    610 => '<p>Este corte yakiniku de wagyu japonés A5 llega en finos escalopines de aproximadamente 100 g, preparados a partir de la parte central de la cadera. El yakiniku se basa en cocinar porciones pequeñas de carne sobre una superficie caliente, de forma rápida y en tandas. Su formato evita tener que laminar en casa una pieza grande de wagyu.</p>',
    611 => '<p>Aguja de wagyu japonés en ración cercana a 200 g, un corte con presencia de grasa infiltrada que aporta jugosidad durante el cocinado. Latitudes lo comercializa dentro de su selección japonesa, distinta de la de wagyu australiano. La descripción original mezclaba la aguja con la entraña; aquí se mantiene la identificación de aguja que aparece en el nombre del producto.</p>',
    612 => '<p>La entraña de wagyu australiano se ofrece en ración aproximada de 200 g. Es un corte de fibras marcadas que admite una preparación en sartén o parrilla, seguido de un corte transversal a la fibra para servir. Latitudes destaca el marmoleado propio de esta carne, que contribuye a su textura y aroma sin que sea necesario añadir salsas intensas.</p>',
    613 => '<p>Entrecot de Black Angus en ración individual próxima a 200 g. Se trata de un corte reconocible para cocinar directamente en plancha o parrilla, donde el tiempo de cocción debe adaptarse al grosor real de la pieza. Es una opción para comparar la carne Angus con los cortes de wagyu y con otras especialidades cárnicas de Latitudes.</p>',
    614 => '<p>Entrecot de wagyu australiano de aproximadamente 200 g, comercializado por Latitudes con un índice de marmoleado BMS 4-5. Esta medida describe la infiltración visible de grasa en el músculo, no el peso ni el tamaño del corte. La ficha del productor indica que se suministra envasado al vacío y congelado, con entrecot de wagyu como ingrediente.</p>',
    615 => '<p>La ración de entrecot de wagyu australiano BMS 6-7, de unos 200 g, se sitúa en un nivel de marmoleado superior al de la referencia BMS 4-5 del mismo catálogo. Esa grasa infiltrada influye en la textura al cocinar. Se trata de un corte individual pensado para preparación directa, conservando el origen y la graduación comercial que comunica Latitudes.</p>',
    616 => '<p>Entrecot de wagyu australiano BMS 8-9 presentado en una ración de aproximadamente 200 g. Latitudes destaca sus vetas claras de grasa entre la carne roja, rasgo asociado al marmoleado elevado. El formato permite preparar una pieza individual y comprobar su textura sin comprar un lomo entero de esta misma gama.</p>',
    617 => '<p>Entrecot de wagyu japonés clasificado por Latitudes como A3, en una porción de unos 200 g obtenida del lomo bajo. Dentro de la oferta japonesa se diferencia de las referencias A4 y A5 por su grado comercial. Puede cocinarse como un entrecot individual, ajustando el tiempo al grosor y a la cantidad de grasa de la pieza.</p>',
    618 => '<p>La referencia de entrecot de wagyu japonés A4 con BMS 5-7 se presenta en unos 200 g y procede del lomo bajo, según Latitudes. Combina la identificación del grado japonés con un rango concreto de marmoleado. Es una elección intermedia dentro de las categorías A3, A4 y A5 presentes en el catálogo, adecuada para una degustación en porciones.</p>',
    619 => '<p>Entrecot de wagyu japonés A5 y BMS 10-12 en una ración aproximada de 200 g, comercializado por Latitudes como corte de lomo bajo. Su denominación señala un nivel alto de grasa intramuscular; conviene tenerlo en cuenta al elegir el tamaño de la porción y los acompañamientos. La clasificación indicada procede de la ficha del proveedor.</p>',
    620 => '<p>La burger meat de Black Angus de aproximadamente 500 g permite moldear hamburguesas de distinto tamaño a partir de una mezcla comercial ya aliñada. Latitudes la distribuye congelada y envasada al vacío, lista para cocinar según las indicaciones del envase. No debe presentarse como carne pura: el productor declara condimentos, aditivos y sulfitos.</p>',
    621 => '<p>Pack de dos burger meat de Black Angus de 150 g cada una, para preparar dos hamburguesas de tamaño equivalente. Se trata de un elaborado condimentado, distinto del entrecot o la picanha Angus sin preparar. Latitudes declara su conservación congelada y advierte de la presencia de sulfitos, información que debe permanecer visible junto a los ingredientes.</p>',
    622 => '<p>Esta burger meat de wagyu japonés de 150 g se anuncia en Latitudes como elaborada con carne de origen japonés. Ofrece una manera de probar el wagyu en formato de hamburguesa, con una textura influida por la infiltración de grasa propia de la carne. La procedencia japonesa se refiere a la carne declarada por el proveedor, sin presuponer que todo ingrediente añadido comparta ese origen.</p>',
    623 => '<p>Burger meat de wagyu australiano en un envase cercano a 500 g. La composición declarada por Latitudes contiene un 85 % de carne de wagyu australiano, además de agua, fibras y otros ingredientes, por lo que no es una hamburguesa de carne exclusivamente. El formato permite porcionar la mezcla y cocinar varias piezas, prestando atención a los sulfitos que figuran en su ficha.</p>',
    624 => '<p>El pack de dos burger meat de wagyu de 150 g utiliza, según la lista de ingredientes publicada por Latitudes, carne de wagyu chileno en un 85 %. Esta precisión es importante porque no debe confundirse con la hamburguesa de wagyu australiano ni con la de carne japonesa. Es una preparación aliñada y congelada, con sulfitos declarados por el proveedor.</p>',
    625 => '<p>Lomo alto de Black Angus procedente de Estados Unidos en una pieza de aproximadamente 1 kg. Latitudes describe una alimentación con pastos y un acabado con grano, relacionada con el veteado graso de la carne. El formato permite preparar varios entrecots a partir del mismo corte y ajustar el grosor según la ocasión.</p>',
    626 => '<p>Este lomo de wagyu australiano BMS 6-7 se presenta en una pieza de aproximadamente 2,6 a 2,8 kg, orientada a porcionar o cocinar para varios comensales. El rango BMS identifica el grado comercial de infiltración grasa que anuncia Latitudes. Antes de cocinar conviene definir el número y grosor de los cortes para aprovechar de forma uniforme una pieza grande.</p>',
    627 => '<p>Lomo de wagyu australiano BMS 8-9 en un formato cercano a 1 kg. Su marmoleado anunciado es superior al de las referencias BMS 4-5 y BMS 6-7 de esta gama, una diferencia a considerar al elegir corte y ración. Permite trabajar una pieza de lomo completa o dividirla en porciones de tamaño uniforme para cocinar a la parrilla.</p>',
    628 => '<p>El lomo alto de wagyu australiano BMS 8-9 se ofrece en una pieza grande, de aproximadamente 3,5 a 3,7 kg. Latitudes lo identifica por un marmoleado elevado, con grasa repartida dentro del músculo. Es un formato pensado para obtener distintos entrecots o preparar un servicio compartido, siempre teniendo en cuenta el rendimiento real del corte.</p>',
    629 => '<p>Lomo bajo de wagyu australiano BMS 8-9 en una pieza de unos 2,6 kg. Se distingue del lomo alto por la zona de procedencia dentro del animal, aunque ambos pertenecen a la selección de wagyu con elevado marmoleado. Esta presentación permite cortar entrecots de grosor controlado y adaptar cada ración al menú.</p>',
    630 => '<p>El lomo alto de wagyu japonés A4 se ofrece en una pieza de aproximadamente 1,8 a 2 kg. Latitudes describe su carne como procedente de animales de raza Japanese Black y comercializa esta referencia con grado A4. Es un corte para porcionar en entrecots o preparar como pieza entera, manteniendo una diferencia clara respecto a las referencias A5.</p>',
    631 => '<p>Lomo alto de wagyu japonés A5, identificado por Latitudes con BMS 9-11 y presentado en una pieza de unos 1,9 a 2,1 kg. Su rango de marmoleado permite distinguirlo de otros lomos japoneses A5 de la misma tienda. El formato resulta adecuado para planificar varias raciones de entrecot, ajustando su grosor al servicio.</p>',
    632 => '<p>Este lomo de wagyu japonés A5/A5+ y BMS 10-12 llega en una pieza pequeña de aproximadamente 500 g. Ofrece una alternativa a los grandes lomos enteros cuando se desea preparar una degustación de carne muy marmoleada. La graduación procede de la denominación comercial de Latitudes y debe mantenerse diferenciada de los cortes A4 o de origen australiano.</p>',
    633 => '<p>El lomo alto de wagyu japonés A5+ y BMS 11-12 se comercializa como pieza grande de aproximadamente 3,9 a 4,2 kg. La denominación identifica uno de los niveles de marmoleado más elevados de la gama anunciada por Latitudes. Por su tamaño, conviene planificar el despiece en porciones o entrecots antes de servir, evitando tratarlo como una ración individual.</p>',
    634 => '<p>Lomo bajo de wagyu japonés A4 anunciado con procedencia de Kagoshima, en una pieza de aproximadamente 2,9 a 3,2 kg. El corte permite obtener varias raciones de entrecot de la zona baja del lomo. La mención a Kagoshima y al grado A4 corresponde a la información comercial del proveedor; no se sustituye por datos genéricos de otras prefecturas japonesas.</p>',
    635 => '<p>Este lomo bajo de wagyu japonés A5 se identifica en el catálogo con Kagoshima y un peso aproximado de 3,1 a 3,3 kg. Reúne el corte de lomo bajo con una graduación comercial de alta infiltración grasa. Puede dividirse en piezas para diferentes servicios, prestando atención a la conservación y al rendimiento de un formato grande.</p>',
    636 => '<p>Lomo bajo de wagyu japonés A5+ con BMS 11-12, en una pieza de aproximadamente 3,3 a 3,5 kg. Latitudes lo sitúa en su gama de gran marmoleado, pero su corte y peso son distintos de los del lomo alto A5+. Esta presentación está destinada a obtener múltiples entrecots o porciones, más que a una sola preparación individual.</p>',
    637 => '<p>La picanha de wagyu japonés A5 se presenta en una porción cercana a 150 g. Es un corte conocido por su cobertura de grasa exterior, que conviene distinguir del marmoleado interior característico del wagyu. Su tamaño resulta apropiado para una degustación individual o para comparar picanha con entrecot dentro de la misma gama.</p>',
    638 => '<p>Picanha de wagyu australiano en ración aproximada de 200 g, comercializada por Latitudes como alternativa a los cortes japoneses. La picanha procede de una zona concreta de la cadera y se reconoce por la grasa que cubre parte de la pieza. Puede cocinarse en parrilla o sartén, adaptando el tiempo al grosor y a la grasa exterior.</p>',
    639 => '<p>Esta picanha de wagyu con un rango BMS 6-9 se ofrece en una pieza de aproximadamente 1,6 a 1,8 kg. El formato permite cortarla en varias raciones conservando su capa exterior de grasa. La ficha comercial indica el marmoleado, pero no se añade un país de origen que no forme parte de la identificación del producto.</p>',
    640 => '<p>Picanha de wagyu australiano BMS 6-9 en una pieza de aproximadamente 2,1 a 2,3 kg. Frente a la ración individual, este formato puede cocinarse para compartir o porcionarse antes de llegar a la parrilla. Combina la cobertura grasa propia de la picanha con el marmoleado anunciado para esta selección australiana.</p>',
    641 => '<p>La picanha de Black Angus en ración de unos 200 g permite probar este corte de la cadera sin comprar una pieza entera. Latitudes destaca el veteado graso de su selección Angus, que influye en la textura durante el cocinado. Su presentación individual facilita una preparación sencilla en sartén o parrilla.</p>',
    642 => '<p>La pieza entera de picanha de Black Angus ronda los 1,2 a 1,3 kg y permite elegir cómo cortarla antes de cocinar. Es una alternativa al entrecot para una comida compartida, con una cobertura de grasa que requiere atención durante el asado. La denominación Black Angus identifica la carne, sin inferir una categoría USDA concreta a partir de esta ficha.</p>',
    643 => '<p>El rabillo de cadera o maminha de wagyu se ofrece en una pieza de aproximadamente 1,5 kg. Este corte de la parte posterior del animal es distinto de la picanha, aunque ambos se utilizan con frecuencia en parrilla y asados. Latitudes lo incluye dentro de su selección wagyu y destaca la influencia del marmoleado en la textura.</p>',
    644 => '<p>Sobrecostillas de wagyu australiano BMS 4-5 sin hueso, en ración de aproximadamente 200 g. La ausencia de hueso cambia el aprovechamiento y la forma de servir respecto a la versión con hueso. Latitudes destaca la infiltración grasa característica del wagyu australiano y el corte se presta a preparaciones donde se controle la cocción de la carne.</p>',
    645 => '<p>La ración de unos 200 g de sobrecostillas de wagyu australiano con hueso se comercializa como BMS 4-5. El hueso forma parte del corte y debe considerarse al calcular la cantidad de carne útil por comensal. Latitudes indica que se distribuye envasada al vacío y congelada, con costilla de wagyu australiano como ingrediente declarado.</p>',
    646 => '<p>Estas sobrecostillas de wagyu australiano con hueso se ofrecen en una presentación cercana a 1 kg. El formato está pensado para cocinar una cantidad mayor que la ración individual, teniendo en cuenta el peso del hueso en el conjunto. La documentación de Latitudes utiliza dos rangos de marmoleado distintos para esta referencia, por lo que se evita afirmar uno de ellos en esta descripción hasta su confirmación.</p>',
    647 => '<p>Churrasco de Black Angus sin hueso en ración próxima a los 200 g, una presentación distinta del entrecot y de la picanha del mismo catálogo. Latitudes describe su selección de Angus con veteado graso y una textura jugosa. El corte admite una preparación en parrilla o sartén, prestando atención al grosor de la pieza.</p>',
    648 => '<p>Solomillo de wagyu australiano BMS 6-7 en una ración cercana a 200 g. Al proceder del centro del solomillo, es un corte de presentación limpia que se diferencia de un lomo o una sobrecostilla. Latitudes lo identifica con un rango concreto de infiltración grasa y ofrece esta cantidad para una elaboración individual.</p>',
    649 => '<p>Este solomillo de wagyu japonés A4 se presenta en una ración de 150 g cortada de la parte central de la pieza, conocida en cocina como tournedó. Latitudes lo comercializa con grado A4, inferior al A5 de otras referencias japonesas de la misma tienda. Su formato permite cocinar un medallón individual sin dividir un solomillo entero.</p>',
    650 => '<p>Solomillo entero de wagyu japonés A5/A5+ y BMS 10-12 en una pieza de aproximadamente 4,8 a 5,2 kg. Es un formato grande, orientado a porcionar en medallones o tournedós antes de servir. La graduación comercial corresponde a la comunicada por Latitudes y debe mantenerse asociada a esta pieza, sin extenderla a todos los solomillos wagyu.</p>',
    651 => '<p>Solomillo de wagyu japonés A5/A5+ y BMS 10-12 en ración de unos 150 g, obtenida de la zona central del corte según Latitudes. Es una opción para degustar wagyu japonés de elevado marmoleado sin adquirir el solomillo entero. La porción individual facilita una cocción breve y una presentación sencilla que permite apreciar la textura de la carne.</p>',
    652 => '<p>T-bone de cebón irlandés en una pieza de aproximadamente 500 a 600 g, identificable por el hueso en forma de T que separa las dos zonas de carne. Latitudes describe el cebón irlandés como tierno y de tono rosado. Al cocinarlo, conviene tener en cuenta el peso del hueso y el diferente grosor de las partes del corte.</p>',
    653 => '<p>Esta referencia de T-bone de cebón irlandés se ofrece en un tamaño superior, de unos 600 a 700 g. Incluye el característico hueso central que separa dos partes de carne, por lo que el peso total no equivale al peso comestible. El formato permite preparar una pieza para compartir o una ración abundante, ajustando los tiempos al grosor real.</p>',
    654 => '<p>Vacío de wagyu australiano en una ración aproximada de 200 g, un corte de fibras largas que se diferencia del entrecot y del solomillo. Latitudes destaca en esta carne la presencia de grasa infiltrada y un sabor intenso. Para servirlo, el corte transversal a la fibra después de la cocción ayuda a obtener porciones más agradables al masticar.</p>',
    655 => '<p>La pieza de vacío de wagyu australiano de aproximadamente 800 a 900 g permite preparar varias raciones con un corte de sabor marcado. Frente al envase de 200 g, ofrece más libertad para elegir el grosor de cada porción o cocinar una pieza entera. Su estructura de fibras aconseja lonchear la carne a contrapelo al servir.</p>',
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
    update_post_meta( $product->get_id(), '_emdo_latitudes_editorial_version', '20261015-04' );
    $product->set_description( wp_kses_post( $description ) );
    $product->save();
    if ( 646 === (int) $source_id ) {
        update_post_meta( $product->get_id(), '_emdo_latitudes_manual_review',
            'CONFLICTO DE ORIGEN: título BMS 4-5 y ficha técnica BMS 8-9. Confirmar rango real con Latitudes antes de publicar.' );
    }
    $reloaded = wc_get_product( $product->get_id() );
    if ( $reloaded->get_description() !== wp_kses_post( $description ) || $reloaded->get_status() !== 'draft' ||
        $reloaded->get_catalog_visibility() !== 'hidden' ) {
        throw new RuntimeException( 'Editorial persistence or privacy check failed: ' . $source_id );
    }
    $updated[] = array( 'source_id' => $source_id, 'wc_id' => $product->get_id(), 'chars' => mb_strlen( wp_strip_all_tags( $description ) ) );
}
echo 'LATITUDES_EDITORIAL_BATCH_04=' . wp_json_encode( array(
    'updated' => count( $updated ), 'items' => $updated, 'published' => 0,
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . PHP_EOL;
