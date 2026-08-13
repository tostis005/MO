<?php
/**
 * One-time guarded production update for the 1957 store description.
 * Locates the unique WCFM producer by its approved old copy and store name/slug,
 * then updates only the two description fields used by the public About page.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

$old_needle = 'Desde 1957 hemos mantenido una tradición en la almazara';
$new_needle = 'La historia de <strong>1957</strong> comienza precisamente ese año';

$new_description = <<<'HTML'
<p>La historia de <strong>1957</strong> comienza precisamente ese año, cuando Gregorio inició su andadura en el mundo del olivar. Desde entonces, el aceite de oliva ha ido pasando de una generación a la siguiente hasta convertirse en una forma de vida compartida hoy por <strong>tres generaciones</strong> de la misma familia.</p>
<p>Ese vínculo con el olivar es también el punto de partida de sus aceites. Para la elaboración de sus <strong>aceites de oliva virgen extra</strong> trabajan con aceitunas recogidas del árbol, en su momento óptimo de maduración, buscando que el fruto llegue a la almazara en las mejores condiciones posibles.</p>
<p>A partir de ahí comienza un proceso en el que cada detalle está pensado para conservar las cualidades naturales de la aceituna. Tras su limpieza, el fruto se moltura y el aceite se obtiene exclusivamente mediante procedimientos mecánicos, manteniéndose durante todo el recorrido en contacto con materiales inertes como el <strong>acero inoxidable</strong> para evitar alteraciones.</p>
<p>Uno de los aspectos que más caracteriza su elaboración es el control de la temperatura. Sus AOVEs se extraen <strong>en frío</strong>, manteniendo el proceso de transformación por debajo de los <strong>19 ºC</strong>, con el objetivo de preservar al máximo los aromas, sabores y propiedades presentes en el fruto.</p>
<p>La experiencia acumulada desde 1957 ha ido acompañada también de distintos reconocimientos a la calidad de sus aceites. Entre ellos se encuentran primeros premios obtenidos en los galardones a los mejores AOVEs del <strong>Valle del Guadalquivir</strong>, tanto en categorías de frutado verde intenso como de frutado maduro, además del reconocimiento a la trayectoria iniciada por Gregorio dentro del sector oleícola de la zona.</p>
<p>Hoy, 1957 mantiene ese mismo vínculo entre familia, olivar y almazara que dio origen al proyecto. Tres generaciones después, continúan elaborando sus aceites combinando la experiencia transmitida durante décadas con un especial cuidado por la materia prima y por cada una de las fases que intervienen en su transformación.</p>
HTML;

$candidates = array();
$user_ids = get_users( array( 'fields' => 'ID' ) );

foreach ( $user_ids as $user_id ) {
    $user_id = (int) $user_id;
    $settings = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
    $direct   = get_user_meta( $user_id, '_store_description', true );

    if ( ! is_array( $settings ) ) {
        continue;
    }

    $store_name = isset( $settings['store_name'] ) ? (string) $settings['store_name'] : '';
    $store_slug = isset( $settings['store_slug'] ) ? (string) $settings['store_slug'] : '';
    $shop_desc  = isset( $settings['shop_description'] ) ? $settings['shop_description'] : '';

    $identity_match = ( '1957' === trim( $store_name ) || '1957' === trim( $store_slug ) );
    $old_in_direct  = is_string( $direct ) && false !== strpos( $direct, $old_needle );
    $old_in_shop    = is_string( $shop_desc ) && false !== strpos( $shop_desc, $old_needle );
    $new_in_direct  = is_string( $direct ) && false !== strpos( $direct, $new_needle );
    $new_in_shop    = is_string( $shop_desc ) && false !== strpos( $shop_desc, $new_needle );

    if ( $identity_match && ( ( $old_in_direct && $old_in_shop ) || ( $new_in_direct && $new_in_shop ) ) ) {
        $candidates[] = array(
            'user_id'    => $user_id,
            'settings'   => $settings,
            'direct'     => $direct,
            'store_name' => $store_name,
            'store_slug' => $store_slug,
        );
    }
}

if ( 1 !== count( $candidates ) ) {
    fwrite( STDERR, '1957_UPDATE_ABORT: expected exactly one matching producer, found ' . count( $candidates ) . "\n" );
    exit( 2 );
}

$target     = $candidates[0];
$user_id    = (int) $target['user_id'];
$settings   = $target['settings'];
$direct     = $target['direct'];
$store_slug = (string) $target['store_slug'];
$shop_desc  = isset( $settings['shop_description'] ) ? $settings['shop_description'] : '';

$direct_is_new   = is_string( $direct ) && false !== strpos( $direct, $new_needle );
$settings_is_new = is_string( $shop_desc ) && false !== strpos( $shop_desc, $new_needle );

if ( $direct_is_new && $settings_is_new ) {
    echo "__1957_UPDATE__=already_applied\n";
    echo "__USER_ID__={$user_id}\n";
    echo "__STORE_SLUG__={$store_slug}\n";
    exit( 0 );
}

$direct_has_old   = is_string( $direct ) && false !== strpos( $direct, $old_needle );
$settings_has_old = is_string( $shop_desc ) && false !== strpos( $shop_desc, $old_needle );

if ( ! $direct_has_old || ! $settings_has_old ) {
    fwrite( STDERR, "1957_UPDATE_ABORT: approved old copy not found in both description fields\n" );
    exit( 3 );
}

$old_direct_hash   = hash( 'sha256', $direct );
$old_settings_hash = hash( 'sha256', $shop_desc );

update_user_meta( $user_id, '_store_description', $new_description );
$settings['shop_description'] = $new_description;
update_user_meta( $user_id, 'wcfmmp_profile_settings', $settings );

clean_user_cache( $user_id );
wp_cache_delete( $user_id, 'user_meta' );

$direct_after   = get_user_meta( $user_id, '_store_description', true );
$settings_after = get_user_meta( $user_id, 'wcfmmp_profile_settings', true );
$shop_after     = is_array( $settings_after ) && isset( $settings_after['shop_description'] ) ? $settings_after['shop_description'] : '';

if (
    ! is_string( $direct_after ) || false === strpos( $direct_after, $new_needle ) ||
    ! is_string( $shop_after ) || false === strpos( $shop_after, $new_needle ) ||
    false !== strpos( $direct_after, $old_needle ) || false !== strpos( $shop_after, $old_needle )
) {
    fwrite( STDERR, "1957_UPDATE_ABORT: post-update verification failed\n" );
    exit( 4 );
}

echo "__1957_UPDATE__=success\n";
echo "__USER_ID__={$user_id}\n";
echo "__STORE_SLUG__={$store_slug}\n";
echo "__OLD_DIRECT_SHA256__={$old_direct_hash}\n";
echo "__OLD_SETTINGS_SHA256__={$old_settings_hash}\n";
echo "__NEW_SHA256__=" . hash( 'sha256', $new_description ) . "\n";
