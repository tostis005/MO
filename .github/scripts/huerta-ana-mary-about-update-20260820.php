<?php
/**
 * One-off guarded production update for La Huerta de Ana Mary store About copy.
 */
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

global $wpdb;

$new_description = <<<'HTML'
<p>La Huerta de Ana Mary nace en 2011 con la idea de acercar directamente al consumidor las verduras y hortalizas cultivadas en Fresno de la Vega, en León. Detrás del proyecto se encuentran Arsenio y Antonio, agricultor de tercera generación y heredero de una forma de trabajar la tierra transmitida durante décadas dentro de su familia.</p>

<p>Fresno de la Vega, situado junto al río Esla, es conocido tradicionalmente como la Huerta del Norte por la fertilidad de sus tierras y por su larga vinculación con el cultivo de verduras y hortalizas. En este entorno se producen las distintas variedades que forman parte de La Huerta de Ana Mary, siguiendo el ritmo de las temporadas y aprovechando las condiciones de una vega especialmente ligada a la agricultura.</p>

<p>Una de las particularidades de su forma de trabajar es que las hortalizas frescas permanecen en el campo hasta que se recibe el pedido. Es entonces cuando se recolectan y se preparan para su envío, sin pasar previamente por periodos de almacenamiento en cámaras frigoríficas. En condiciones normales, incluso los pedidos formalizados antes de las 13:00 pueden salir ese mismo día. Así se reduce al máximo el tiempo entre la recolección y la llegada a casa y se mantiene esa relación directa entre la huerta y quien disfruta del producto.</p>

<p>Patatas, puerros, coliflores, repollos, cebollas, lechugas o pimientos forman parte de una producción que va cambiando a lo largo del año según la temporada. Entre ellas ocupa un lugar especial el pimiento morrón de Fresno de la Vega, uno de los cultivos más representativos del municipio y parte de una tradición agrícola profundamente ligada a la localidad.</p>

<p>Junto a las hortalizas frescas, La Huerta de Ana Mary ofrece también una selección de conservas vegetales elaboradas artesanalmente en Fresno de la Vega por Conservas Vega Esla, una empresa familiar con una larga experiencia en la conservación de los productos de la huerta. Se elaboran sin conservantes ni colorantes, manteniendo procesos tradicionales como el asado de los pimientos al fuego de leña y realizando de forma manual tareas como el pelado y el envasado. Sus conservas vegetales cuentan además con los sellos de calidad Alimentos Artesanos de Castilla y León y Tierra de Sabor.</p>

<p>Hoy, La Huerta de Ana Mary continúa uniendo la experiencia de varias generaciones de agricultores con una forma especialmente directa de llevar sus productos hasta el consumidor: cultivar en Fresno de la Vega, esperar a que llegue el pedido para recolectar las hortalizas frescas y enviarlas desde la propia zona de producción. Una manera de trabajar en la que el campo, la temporada y la frescura del producto siguen siendo los verdaderos protagonistas.</p>
HTML;

$rows = $wpdb->get_results(
    "SELECT um.user_id, u.user_login, u.user_nicename, u.display_name, um.meta_value
     FROM {$wpdb->usermeta} um
     INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
     WHERE um.meta_key = 'wcfmmp_profile_settings'",
    ARRAY_A
);

$candidates = array();
foreach ( (array) $rows as $row ) {
    $settings = maybe_unserialize( $row['meta_value'] ?? '' );
    if ( ! is_array( $settings ) ) { continue; }

    $store_name = (string) ( $settings['store_name'] ?? '' );
    $store_slug = (string) ( $settings['store_slug'] ?? '' );
    $haystack = strtolower( remove_accents( implode( ' ', array(
        $store_name,
        $store_slug,
        (string) $row['user_login'],
        (string) $row['user_nicename'],
        (string) $row['display_name'],
    ) ) ) );

    $has_huerta = false !== strpos( $haystack, 'huerta' );
    $has_ana   = false !== strpos( $haystack, 'ana' );
    $has_mary  = false !== strpos( $haystack, 'mary' ) || false !== strpos( $haystack, 'anamary' );

    if ( $has_huerta && $has_ana && $has_mary ) {
        $row['_settings'] = $settings;
        $candidates[] = $row;
    }
}

if ( 1 !== count( $candidates ) ) {
    fwrite( STDERR, 'HUERTA_ABOUT_ABORT: expected exactly one producer, found ' . count( $candidates ) . "\n" );
    foreach ( $candidates as $candidate ) {
        fwrite( STDERR, wp_json_encode( array(
            'user_id' => (int) $candidate['user_id'],
            'nicename' => (string) $candidate['user_nicename'],
            'display_name' => (string) $candidate['display_name'],
            'store_name' => (string) ( $candidate['_settings']['store_name'] ?? '' ),
            'store_slug' => (string) ( $candidate['_settings']['store_slug'] ?? '' ),
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
    }
    exit( 2 );
}

$producer = $candidates[0];
$user_id = (int) $producer['user_id'];
$settings = $producer['_settings'];
$store_slug = sanitize_title( (string) ( $settings['store_slug'] ?? '' ) );
if ( '' === $store_slug ) {
    $store_slug = sanitize_title( (string) $producer['user_nicename'] );
}
if ( '' === $store_slug ) {
    fwrite( STDERR, "HUERTA_ABOUT_ABORT: empty store slug\n" );
    exit( 3 );
}

$old_primary = (string) get_user_meta( $user_id, '_store_description', true );
$old_profile = (string) ( $settings['shop_description'] ?? '' );

echo 'HUERTA_USER_ID=' . $user_id . "\n";
echo 'HUERTA_STORE_NAME=' . (string) ( $settings['store_name'] ?? '' ) . "\n";
echo 'HUERTA_STORE_SLUG=' . $store_slug . "\n";
echo 'HUERTA_OLD_PRIMARY_SHA256=' . hash( 'sha256', $old_primary ) . "\n";
echo 'HUERTA_OLD_PROFILE_SHA256=' . hash( 'sha256', $old_profile ) . "\n";

update_user_meta( $user_id, '_store_description', $new_description );
$settings['shop_description'] = $new_description;
update_user_meta( $user_id, 'wcfmmp_profile_settings', $settings );
clean_user_cache( $user_id );

$check_primary = (string) get_user_meta( $user_id, '_store_description', true );
$check_settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
$check_profile = is_array( $check_settings ) ? (string) ( $check_settings['shop_description'] ?? '' ) : '';

if ( $new_description !== $check_primary || $new_description !== $check_profile ) {
    fwrite( STDERR, "HUERTA_ABOUT_ABORT: persisted copy does not match requested copy\n" );
    exit( 4 );
}

if ( false !== strpos( $new_description, 'Arsenio Pérez' ) || false !== strpos( $new_description, 'Antonio Luis Morán' ) ) {
    fwrite( STDERR, "HUERTA_ABOUT_ABORT: surnames unexpectedly present\n" );
    exit( 5 );
}

if ( false === strpos( $new_description, 'Detrás del proyecto se encuentran Arsenio y Antonio' ) ) {
    fwrite( STDERR, "HUERTA_ABOUT_ABORT: requested names-only sentence missing\n" );
    exit( 6 );
}

echo 'HUERTA_NEW_SHA256=' . hash( 'sha256', $new_description ) . "\n";
echo "HUERTA_ABOUT_UPDATE_OK\n";
