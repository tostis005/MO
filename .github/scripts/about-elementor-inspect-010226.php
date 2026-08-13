<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$page = get_page_by_path( 'quienes-somos', OBJECT, 'page' );
if ( ! $page ) { fwrite( STDERR, "PAGE_NOT_FOUND\n" ); exit(2); }

$keys = array( '_elementor_data', '_elementor_element_cache', '_elementor_edit_mode', '_elementor_template_type' );
$widget_id = '4807dca2';
$old = 'Todo comenzó en 2014 cuando empezamos a especializarnos';
$new = 'Nuestra historia comienza en 2014, cuando empezamos a especializarnos';

foreach ( $keys as $key ) {
    $value = get_post_meta( $page->ID, $key, true );
    $text  = is_string( $value ) ? $value : maybe_serialize( $value );
    printf("KEY=%s len=%d widget=%d old=%d new=%d\n", $key, strlen( $text ), substr_count( $text, $widget_id ), substr_count( $text, $old ), substr_count( $text, $new ) );
    $pos = strpos( $text, $widget_id );
    if ( false !== $pos ) {
        echo "WIDGET_CONTEXT_{$key}=\n";
        echo substr( $text, max(0, $pos - 400), 2600 ) . "\n";
    }
    $pos_old = strpos( $text, $old );
    if ( false !== $pos_old ) {
        echo "OLD_CONTEXT_{$key}=\n";
        echo substr( $text, max(0, $pos_old - 500), 3000 ) . "\n";
    }
}
