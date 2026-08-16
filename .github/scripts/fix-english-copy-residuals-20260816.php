<?php
/**
 * One-shot production data cleanup for reviewed English metadata.
 * Spanish source values are never modified.
 */
if ( PHP_SAPI !== 'cli' ) { exit( 1 ); }
if ( empty( $argv[1] ) ) { fwrite( STDERR, "Usage: php script.php /path/to/wp-root\n" ); exit( 2 ); }
$root = rtrim( (string) $argv[1], '/' );
ini_set( 'memory_limit', '512M' );
require $root . '/wp-load.php';

global $wpdb;
$backup = array( 'generated_at' => gmdate( 'c' ), 'post_meta' => array(), 'term_meta' => array() );
$changes = 0;

function mdo_backup_post_meta_20260816( array &$backup, int $post_id, string $key, string $old ): void {
    $backup['post_meta'][] = array( 'post_id' => $post_id, 'key' => $key, 'value' => $old );
}
function mdo_backup_term_meta_20260816( array &$backup, int $term_id, string $key, string $old ): void {
    $backup['term_meta'][] = array( 'term_id' => $term_id, 'key' => $key, 'value' => $old );
}
function mdo_clean_en_term_value_20260816( string $text ): string {
    return '<span data-no-translation>' . esc_html( $text ) . '</span>';
}

// Reviewed English replacements for terms that were incorrectly published with Spanish values.
$term_map = array(
    'product_tag' => array(
        'almendras' => 'Almonds',
        'garbanzos' => 'Chickpeas',
        'lentejas' => 'Lentils',
        'miel' => 'Honey',
    ),
    'pa_peso' => array(
        '14kg-de-naranja-de-mesa-y-1kg-de-miel' => '14 kg table oranges + 1 kg honey',
        '14kg-de-naranja-de-zumo-y-1kg-de-miel' => '14 kg juicing oranges + 1 kg honey',
        '15kg-de-naranja-de-mesa' => '15 kg table oranges',
        '15kg-de-naranja-de-zumo' => '15 kg juicing oranges',
    ),
    'pa_tamano' => array(
        '10uds-x-100g-cortado-a-mano' => '10 × 100 g, hand-sliced',
        '10uds-x-80g-cortado-a-mano' => '10 × 80 g, hand-sliced',
        '20uds-x-100g-cortado-a-maquina' => '20 × 100 g, machine-sliced',
        '45-5kg-con-dop-los-pedroches-cortado-a-cuchillo-huesos-taquitos' => '4.5–5 kg, Los Pedroches PDO, hand-carved + bones + diced ham',
        '45-5kg-con-dop-los-pedroches-cortado-a-maquina-huesos-taquitos' => '4.5–5 kg, Los Pedroches PDO, machine-sliced + bones + diced ham',
        '45-5kg-con-dop-los-pedroches-deshuesado' => '4.5–5 kg, Los Pedroches PDO, boneless',
        '45-5kg-cortado-a-cuchillo-huesos-taquitos' => '4.5–5 kg, hand-carved + bones + diced ham',
        '45-5kg-deshuesado' => '4.5–5 kg, boneless',
        '5-55kg-con-dop-los-pedroches-cortado-a-cuchillo-huesos-taquitos' => '5–5.5 kg, Los Pedroches PDO, hand-carved + bones + diced ham',
        '5-55kg-con-dop-los-pedroches-cortado-a-maquina-huesos-taquitos' => '5–5.5 kg, Los Pedroches PDO, machine-sliced + bones + diced ham',
        '5-55kg-con-dop-los-pedroches-deshuesado' => '5–5.5 kg, Los Pedroches PDO, boneless',
        '75-8-kg-loncheado-a-maquinahuesostaquitos' => '7.5–8 kg, machine-sliced + bones + diced ham',
        'bote-de-1kg' => '1 kg jar',
        'botella-blanca-de-500-ml-aove-premium-picudo' => '500 ml white bottle (Premium Picudo EVOO)',
        'botella-negra-de-500-ml-aove-premium-picudo' => '500 ml black bottle (Premium Picudo EVOO)',
        'caja-de-1kg' => '1 kg box',
        'caja-de-200g' => '200 g box',
        'caja-de-500g' => '500 g box',
        'garbanzos-lentejas-y-almendras-ecologicas' => 'Organic chickpeas, lentils and almonds',
        'garbanzos-lentejas-almendras-y-aceite-virgen-extra-ecologico' => 'Organic chickpeas, lentils, almonds and extra virgin olive oil',
        'lata-de-3-litros-con-doble-tapon-aove-premium-arbequino' => '3 L tin with double cap (Premium Arbequino EVOO)',
        'lomo-salchichon-y-chorizo-de-bellota-100-iberico-loncheado' => 'Sliced 100% Iberian acorn-fed cured loin, salchichón and chorizo',
        'medias-piezas-05kg-pieza' => 'Half pieces (0.5 kg per piece)',
        'medias-piezas-loncheadas-envasadas-al-vacio-5-sobres-pieza' => 'Sliced vacuum-packed half pieces (5 packs per piece)',
        'medio-lomo-medio-salchichon-y-medio-chorizo-de-bellota-100-iberico-loncheado' => 'Half cured loin, half salchichón and half 100% Iberian acorn-fed chorizo, sliced',
        'pack-carne-de-cerdo-de-bellota-100-iberico-con-dop-los-pedroches-aceite-sin-filtrar' => '100% Iberian acorn-fed pork pack with Los Pedroches PDO + unfiltered olive oil',
        'pack-de-miel-de-12-botes-de-05kg' => '12 × 0.5 kg jars of honey',
        'pack-de-miel-de-6-botes-de-1kg' => '6 × 1 kg jars of honey',
        'pieza-de-1kg' => '1 kg piece',
        'pieza-de-250g' => '250 g piece',
        'pieza-de-2kg' => '2 kg piece',
        'pieza-de-3kg' => '3 kg piece',
        'pieza-de-400g' => '400 g piece',
        'pieza-de-600g' => '600 g piece',
        'pieza-de-800g' => '800 g piece',
        'saco-de-10kg' => '10 kg sack',
        'saco-de-5kg' => '5 kg sack',
        'tarro-de-05kg' => '0.5 kg jar',
        'tarro-de-1kg' => '1 kg jar',
        'tarro-de-2kg' => '2 kg jar',
        'tarro-de-5kg' => '5 kg jar',
    ),
    'pa_variedad' => array(
        'caja-de-15kg' => '15 kg box',
        'martena-6-tradicional-6-sin-filtrar' => 'Marteña (6 traditional, 6 unfiltered)',
        'sin-filtrar-3-arbequina-3-martenapicual-3-manzanilla-3-lechin' => 'Unfiltered (3 Arbequina, 3 Marteña (Picual), 3 Manzanilla, 3 Lechín)',
    ),
);

foreach ( $term_map as $taxonomy => $items ) {
    foreach ( $items as $slug => $english ) {
        $term = get_term_by( 'slug', $slug, $taxonomy );
        if ( ! $term instanceof WP_Term ) { echo "TERM_MISSING|{$taxonomy}|{$slug}\n"; continue; }
        $key = '_en_US_name';
        $old = (string) get_term_meta( $term->term_id, $key, true );
        $new = mdo_clean_en_term_value_20260816( $english );
        if ( $old === $new ) { continue; }
        mdo_backup_term_meta_20260816( $backup, (int) $term->term_id, $key, $old );
        update_term_meta( $term->term_id, $key, $new );
        $changes++;
        echo "TERM_UPDATED|{$taxonomy}|{$term->term_id}|{$slug}|{$english}\n";
    }
}

// Exact fragments found in reviewed English content and excerpts.
$exact = array(
    'ENVÍO GRATIS EN VARIOS PRODUCTORES' => 'FREE SHIPPING FROM SELECT PRODUCERS',
    'ENVÍOS EN 24-48H' => '24–48H DELIVERY',
    'DEVOLUCIÓN FÁCIL Y SENCILLA' => 'EASY RETURNS',
    'RESOLVEMOS TUS DUDAS' => "WE’RE HERE TO HELP",
    'El plazo de preparación y envío es de varios días dependiendo de la demanda del momento' => 'Preparation and dispatch take several days, depending on current demand.',
    'El resultado es una pieza de carne limpia, que se puede presentar en trozos, ya sea para lonchear fácilmente en casa o para ser envasada al vacío y dividida en porciones.' => 'The result is a clean, boneless piece of meat that can be cut into portions, either for easy slicing at home or for vacuum-packing and portioning.',
    'Ocupa menos espacio y se puede envasar al vacío de manera más eficaz, lo que ayuda a conservar su sabor y textura por más tiempo.' => 'It takes up less space and can be vacuum-packed more efficiently, helping preserve its flavour and texture for longer.',
    'En este sentido, El Mercado de Origen garantiza el cumplimiento de la normativa vigente en materia de protección de datos personales, reflejada en la Ley Orgánica 3/2018, de 5 de diciembre, de Protección de Datos Personales y de Garantía de Derechos Digitales (LOPD GDD). Cumple también con el Reglamento (UE) 2016/679 del Parlamento Europeo y del Consejo de 27 de abril de 2016 relativo a la protección de las personas físicas (RGPD).' => 'In this regard, El Mercado de Origen complies with applicable personal-data protection legislation, including Spanish Organic Law 3/2018 of 5 December on Personal Data Protection and Guarantee of Digital Rights (LOPDGDD). It also complies with Regulation (EU) 2016/679 of the European Parliament and of the Council of 27 April 2016 on the protection of natural persons with regard to the processing of personal data (GDPR).',
    'El uso de sitio Web implica la aceptación de esta Política de Privacidad así como las condiciones incluidas en los' => 'Use of the website implies acceptance of this Privacy Policy and the conditions set out in the',
);

$pdo_replacement = '<p>Protected Designations of Origin are used in Spain to recognise differentiated quality resulting from specific characteristics linked to the geographical environment in which the raw materials are produced and the products are made, as well as the influence of the people involved in the process. Producers that use a PDO undertake to follow defined standards to maintain consistently high quality. A public body also regulates the designation and monitors compliance with those standards. Spanish PDOs are protected under European Union regulations.</p><h3>What is a PDO for?</h3><p>For consumers, above all, it guarantees specific characteristics and a high, consistent level of quality. For producers, it protects them against similar products made in other areas and helps them reach new markets backed by the quality reputation of the PDO.</p>';

$oil_replacement = '<p>Extra virgin olive oil is a 100% natural juice that preserves the aroma, flavour, vitamins and all the properties of the olive. It is the only vegetable oil that can be consumed exactly as it is obtained. It comes directly from olives by extracting their juice, although the process is a little more complex than squeezing an orange.</p><p>When the olives arrive at the mill, they are first cleaned to remove impurities such as leaves and small twigs that may have been collected with the fruit. Once clean, the olives are ready to be processed. Milling consists essentially of four stages. Throughout the process, both the olives and the oil remain in contact only with inert materials (stainless steel) to prevent any alteration.</p><h3>Milling</h3><p>The olives are crushed in a mill to break the cell structures that contain the oil. In simple terms, it is like putting the olives in a large food blender. The result is an olive paste: crushed olives containing pieces of stone and skin, oil and vegetation water.</p><h3>Malaxation</h3><p>The olive paste is slowly mixed to make it uniform and encourage the small droplets of oil to join together into larger drops. It is important that mixing does not exceed 90 minutes and that the paste temperature remains below 27–28 °C.</p><h3>Centrifugation</h3><p>After malaxation, the paste is spun at high speed in a decanter to separate its components according to density. Three concentric layers form inside the cylindrical drum. Pomace — the dry olive matter, stones and skin, with some moisture and a density of around 1.2 g/ml — forms the outer layer. Vegetation water, with a density slightly above 1 g/ml because of its dissolved components, forms the middle layer. The oil, with a density of around 0.92 g/ml, forms the innermost layer.</p><h3>Filtration</h3><p>Different mills use different systems to remove the last traces of impurities. Vertical centrifuges, which spin the oil again at high speed, and settling tanks are among the most common systems. Some mills also use stainless-steel mesh filters. Once free of excess moisture and impurities, the oil is ready to go into storage before bottling.</p>';

$post_ids = $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_status='publish' AND post_type IN ('page','post','product')" );
foreach ( array_map( 'intval', $post_ids ) as $post_id ) {
    foreach ( array( '_en_US_post_content', '_en_US_post_excerpt' ) as $key ) {
        $old = (string) get_post_meta( $post_id, $key, true );
        if ( $old === '' ) { continue; }
        $new = strtr( $old, $exact );

        // Repeated Iberian-product section that was only partly translated.
        $new = (string) preg_replace(
            '/Constituyen el sistema utilizado en nuestro país.*?amparado por la calidad de la DOP\./su',
            $pdo_replacement,
            $new
        );

        // Product 1220 had an English heading followed by the full oil-making explanation in Spanish.
        if ( $post_id === 1220 && $key === '_en_US_post_content' ) {
            $new = (string) preg_replace(
                '/El aceite de oliva virgen extra es un zumo 100% natural.*?para ser envasado\./su',
                $oil_replacement,
                $new
            );
        }

        if ( $new === $old ) { continue; }
        mdo_backup_post_meta_20260816( $backup, $post_id, $key, $old );
        update_post_meta( $post_id, $key, $new );
        $changes++;
        echo "POST_UPDATED|{$post_id}|{$key}\n";
    }
}

$dir = $root . '/wp-content/uploads/mdo-backups';
if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
$path = $dir . '/english-copy-residuals-' . gmdate( 'YmdHis' ) . '.json';
file_put_contents( $path, wp_json_encode( $backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
echo "BACKUP={$path}\n";
echo "CHANGES={$changes}\n";
