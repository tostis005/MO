<?php
/** Final copy polish: eliminate source-data housekeeping in five customer descriptions. */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }
global $wpdb;
$ts = MDO_Database::table( 'source_products' );
$sp = MDO_Database::table( 'suppliers' );
$s = $wpdb->get_row( $wpdb->prepare( "SELECT id,vendor_user_id FROM {$sp} WHERE code=%s", 'latitudes' ), ARRAY_A );
if ( ! $s || '1' !== (string) get_user_meta( (int) $s['vendor_user_id'], '_disable_vendor', true ) ) {
    throw new RuntimeException( 'El vendedor debe seguir deshabilitado.' );
}
$copy = array(
    566 => '<p>El estuche contiene dos huevos de oca, una opción para quienes buscan un ingrediente diferente al huevo de gallina. Pueden utilizarse en tortillas, masas y otras preparaciones, adaptando las cantidades al tamaño de cada unidad. El formato de dos huevos facilita planificar una receta concreta sin comprar una cantidad mayor de la necesaria.</p>',
    570 => '<p>El lomo de búfalo se ofrece en una ración de unos 200 g, un corte individual para descubrir una carne diferente del vacuno habitual. Puede prepararse a la plancha o a la parrilla, ajustando el tiempo de cocción al grosor real de la pieza. Es una alternativa al ragú de búfalo cuando se prefiere una presentación en filete.</p>',
    598 => '<p>Solomillo de cocodrilo en una pieza de aproximadamente 500 g, un formato que permite preparar varias porciones de esta carne poco habitual. Latitudes describe un perfil suave, con matices que pueden recordar al pescado. Conviene dividir el corte en piezas uniformes y cocinarlo completamente, respetando las indicaciones de conservación y manipulación del producto.</p>',
    611 => '<p>Aguja de wagyu japonés en una ración cercana a los 200 g. La aguja es un corte distinto de la entraña y puede presentar la grasa infiltrada característica de la carne de wagyu, que influye en su jugosidad y textura. Esta referencia pertenece a la selección japonesa de Latitudes y se diferencia de sus cortes de origen australiano.</p>',
    646 => '<p>Estas sobrecostillas de wagyu australiano con hueso se presentan en una pieza de aproximadamente 1 kg, pensada para cocinar varias porciones. El peso incluye el hueso, por lo que no coincide con la cantidad de carne aprovechable. Latitudes indica que el producto se distribuye envasado al vacío y congelado. Ingredientes declarados: costilla de wagyu australiano. Alérgenos: el proveedor indica que no contiene.</p>',
);
$out = array();
foreach ( $copy as $id => $html ) {
    $r = $wpdb->get_row( $wpdb->prepare( "SELECT wc_product_id,source_url FROM {$ts} WHERE id=%d AND supplier_id=%d", $id, $s['id'] ), ARRAY_A );
    $p = $r ? wc_get_product( (int) $r['wc_product_id'] ) : false;
    if ( ! $p || 'draft' !== $p->get_status() || 'hidden' !== $p->get_catalog_visibility() ||
        (int) get_post_field( 'post_author', $p->get_id() ) !== (int) $s['vendor_user_id'] ||
        ! str_starts_with( (string) $r['source_url'], 'https://latitudes.es/' ) ||
        '1' !== (string) get_post_meta( $p->get_id(), '_emdo_latitudes_editorial_approved', true ) ) {
        throw new RuntimeException( 'Inconsistent editorial product ' . $id );
    }
    if ( mb_strlen( wp_strip_all_tags( $html ) ) < 200 ) { throw new RuntimeException( 'Copy too short ' . $id ); }
    update_post_meta( $p->get_id(), '_emdo_latitudes_editorial_description', wp_kses_post( $html ) );
    update_post_meta( $p->get_id(), '_emdo_latitudes_editorial_version', '20261015-polish' );
    $p->set_description( wp_kses_post( $html ) );
    $p->save();
    $loaded = wc_get_product( $p->get_id() );
    if ( $loaded->get_status() !== 'draft' || $loaded->get_catalog_visibility() !== 'hidden' ||
        $loaded->get_description() !== wp_kses_post( $html ) ) {
        throw new RuntimeException( 'Final verification failed ' . $id );
    }
    $out[] = (int) $id;
}
echo 'LATITUDES_POLISH=' . wp_json_encode( array( 'count' => count( $out ), 'source_ids' => $out, 'published' => 0 ) ) . PHP_EOL;
