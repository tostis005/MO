<?php
if ( ! defined( 'ABSPATH' ) ) { exit( 1 ); }

function emdo_puente_robles_key( $value ) {
    $value = remove_accents( strtolower( (string) $value ) );
    return preg_replace( '/[^a-z0-9]+/', '', $value );
}

$candidates = array();
global $wpdb;
$user_ids = $wpdb->get_col( $wpdb->prepare(
    "SELECT DISTINCT user_id FROM {$wpdb->usermeta} WHERE meta_key = %s",
    'wcfmmp_profile_settings'
) );

foreach ( $user_ids as $user_id ) {
    $settings = get_user_meta( (int) $user_id, 'wcfmmp_profile_settings', true );
    if ( ! is_array( $settings ) ) { continue; }
    $store_name = (string) ( $settings['store_name'] ?? '' );
    $store_slug = (string) ( $settings['store_slug'] ?? '' );
    if ( emdo_puente_robles_key( $store_name ) === 'puenterobles' || emdo_puente_robles_key( $store_slug ) === 'puenterobles' ) {
        $candidates[] = array( 'id' => (int) $user_id, 'settings' => $settings );
    }
}

if ( count( $candidates ) !== 1 ) {
    fwrite( STDERR, 'PUENTE_ROBLES_UPDATE_ABORT: expected one Puente Robles profile, found ' . count( $candidates ) . "\n" );
    exit( 2 );
}

$user_id = $candidates[0]['id'];
$settings = $candidates[0]['settings'];
$direct = get_user_meta( $user_id, '_store_description', true );
$shop = (string) ( $settings['shop_description'] ?? '' );

if ( ! is_string( $direct ) || ! isset( $settings['shop_description'] ) ) {
    fwrite( STDERR, "PUENTE_ROBLES_UPDATE_ABORT: unexpected description fields\n" );
    exit( 3 );
}

$new_needle = 'La historia de <strong>Puente Robles</strong> empieza mucho antes de que existiera la marca.';
$new_description = <<<'HTML'
<p>La historia de <strong>Puente Robles</strong> empieza mucho antes de que existiera la marca. De niño, Cesáreo ya acompañaba a su padre al mercado para vender los cerdos que la familia había criado y cuidado durante el año. Aquella relación con la ganadería y con los productos de la tierra acabaría convirtiéndose, en los años 80, en el proyecto familiar que puso en marcha junto a Carmen y que hoy acumula <strong>más de 30 años de trayectoria</strong>.</p>
<p>Desde <strong>Fermoselle, en pleno entorno de los Arribes del Duero</strong>, esa historia continúa estrechamente vinculada al territorio. Trabajan con pequeños ganaderos de los Arribes y de la Dehesa Salmantina, manteniendo una relación cercana con una materia prima cuyo origen forma parte del carácter de sus jamones, embutidos y otros productos tradicionales.</p>
<p>La elaboración conserva muchas de las formas de trabajo propias de la zona. En Fermoselle cuentan con sus propios espacios de despiece y secaderos, donde jamones y embutidos evolucionan lentamente aprovechando las condiciones del entorno de los Arribes del Duero. La tradición se mantiene como parte fundamental del proceso, incorporando al mismo tiempo instalaciones adaptadas a las exigencias actuales.</p>
<p>Ese vínculo con Fermoselle está presente también en las personas que forman parte del proyecto. Junto a distintos miembros de la propia familia trabajan profesionales de la zona, dando continuidad a una actividad que durante décadas ha estado ligada a la elaboración de productos ibéricos y a los sabores tradicionales de esta parte de Zamora.</p>
<p>Entre sus elaboraciones, el <strong>chorizo cular de bellota</strong> se ha convertido en uno de sus productos más reconocidos. Fue distinguido como mejor chorizo ibérico en la Feria del Chorizo Artesanal de Covaleda en 2023 y posteriormente obtuvo la <strong>Medalla de Oro en el Concours International de Lyon 2025</strong> y la Medalla de Plata en la edición de 2026.</p>
<p>Hoy, Puente Robles mantiene esa misma conexión entre familia, territorio y elaboración con la que comenzó su recorrido. Desde Fermoselle, continúa trabajando junto a ganaderos de su entorno y conservando una manera de elaborar en la que el origen de la materia prima, las recetas tradicionales y la curación pausada siguen dando forma al carácter de sus productos.</p>
HTML;

if ( strpos( $direct, $new_needle ) !== false && strpos( $shop, $new_needle ) !== false ) {
    echo "PUENTE_ROBLES_UPDATE=already_applied\n";
    echo "PUENTE_ROBLES_USER_ID={$user_id}\n";
    echo "PUENTE_ROBLES_STORE_NAME=" . (string) ( $settings['store_name'] ?? '' ) . "\n";
    echo "PUENTE_ROBLES_STORE_SLUG=" . (string) ( $settings['store_slug'] ?? '' ) . "\n";
    exit( 0 );
}

if ( $direct !== $shop ) {
    fwrite( STDERR, "PUENTE_ROBLES_UPDATE_ABORT: WCFM description fields are not synchronized before update\n" );
    exit( 4 );
}

$backup_key = '_emdo_puente_robles_about_backup_20260813';
if ( '' === (string) get_user_meta( $user_id, $backup_key, true ) ) {
    update_user_meta( $user_id, $backup_key, $direct );
}

update_user_meta( $user_id, '_store_description', $new_description );
$settings['shop_description'] = $new_description;
update_user_meta( $user_id, 'wcfmmp_profile_settings', $settings );
clean_user_cache( $user_id );
wp_cache_delete( $user_id, 'user_meta' );

$after_direct = get_user_meta( $user_id, '_store_description', true );
$after_settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
$after_shop = is_array( $after_settings ) ? (string) ( $after_settings['shop_description'] ?? '' ) : '';

if ( strpos( (string) $after_direct, $new_needle ) === false || strpos( $after_shop, $new_needle ) === false ) {
    fwrite( STDERR, "PUENTE_ROBLES_UPDATE_ABORT: post-update verification failed\n" );
    exit( 5 );
}

if ( strpos( (string) $after_direct, 'utm_source' ) !== false || strpos( $after_shop, 'utm_source' ) !== false ) {
    fwrite( STDERR, "PUENTE_ROBLES_UPDATE_ABORT: source reference detected in saved copy\n" );
    exit( 6 );
}

echo "PUENTE_ROBLES_UPDATE=success\n";
echo "PUENTE_ROBLES_USER_ID={$user_id}\n";
echo "PUENTE_ROBLES_STORE_NAME=" . (string) ( $after_settings['store_name'] ?? '' ) . "\n";
echo "PUENTE_ROBLES_STORE_SLUG=" . (string) ( $after_settings['store_slug'] ?? '' ) . "\n";
