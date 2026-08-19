<?php
/**
 * Ejecutar con: wp eval-file tools/generate-home-webp-20260819.php
 * Genera WebP únicamente para JPEG/PNG locales realmente referenciados por la Home.
 */

$home = wp_remote_get( home_url( '/' ), array( 'timeout' => 40, 'redirection' => 3 ) );
if ( is_wp_error( $home ) ) {
    fwrite( STDERR, 'home_fetch_error=' . $home->get_error_message() . "\n" );
    exit( 2 );
}

$body    = (string) wp_remote_retrieve_body( $home );
$uploads = wp_get_upload_dir();
$base    = rtrim( (string) wp_parse_url( (string) $uploads['baseurl'], PHP_URL_PATH ), '/' );

foreach ( glob( trailingslashit( (string) $uploads['basedir'] ) . 'elmercado-home-static/home-deferred-*.css' ) ?: array() as $css ) {
    if ( is_readable( $css ) ) {
        $body .= "\n" . (string) file_get_contents( $css );
    }
}

preg_match_all(
    '~(?:https?:)?(?:\\/\\/[^\\s"\'()<>]+)?(' . preg_quote( $base, '~' ) . '/[^\\s"\'()<>?,]+\\.(?:jpe?g|png))~iu',
    html_entity_decode( $body, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
    $matches
);

$paths = array_values( array_unique( $matches[1] ?? array() ) );
$made = $reused = $skipped = $failed = 0;
$total_before = $total_after = 0;

foreach ( array_slice( $paths, 0, 120 ) as $url_path ) {
    $relative = ltrim( rawurldecode( substr( $url_path, strlen( $base ) ) ), '/' );
    if ( '' === $relative || str_contains( $relative, '..' ) ) {
        continue;
    }

    $source = trailingslashit( (string) $uploads['basedir'] ) . $relative;
    if ( ! is_readable( $source ) ) {
        continue;
    }

    $source_size = (int) filesize( $source );
    if ( $source_size < 12000 ) {
        ++$skipped;
        continue;
    }

    $dest = preg_replace( '~\\.(?:jpe?g|png)$~i', '.webp', $source );
    if ( ! is_string( $dest ) ) {
        continue;
    }

    if ( is_readable( $dest ) && filemtime( $dest ) >= filemtime( $source ) && filesize( $dest ) < $source_size ) {
        ++$reused;
        $total_before += $source_size;
        $total_after  += (int) filesize( $dest );
        printf( "reuse\t%d\t%d\t%s\n", $source_size, filesize( $dest ), $relative );
        continue;
    }

    $editor = wp_get_image_editor( $source );
    if ( is_wp_error( $editor ) ) {
        ++$failed;
        printf( "editor_error\t%s\t%s\n", $relative, $editor->get_error_message() );
        continue;
    }

    $editor->set_quality( 76 );
    $saved = $editor->save( $dest, 'image/webp' );
    if ( is_wp_error( $saved ) || ! is_readable( $dest ) ) {
        ++$failed;
        printf( "save_error\t%s\t%s\n", $relative, is_wp_error( $saved ) ? $saved->get_error_message() : 'not_created' );
        continue;
    }

    $dest_size = (int) filesize( $dest );
    if ( $dest_size <= 0 || $dest_size >= (int) round( $source_size * 0.96 ) ) {
        @unlink( $dest );
        ++$skipped;
        printf( "not_worth_it\t%d\t%d\t%s\n", $source_size, $dest_size, $relative );
        continue;
    }

    ++$made;
    $total_before += $source_size;
    $total_after  += $dest_size;
    printf( "created\t%d\t%d\t%s\n", $source_size, $dest_size, $relative );
}

printf(
    "summary candidates=%d made=%d reused=%d skipped=%d failed=%d before=%d after=%d saved=%d\n",
    count( $paths ),
    $made,
    $reused,
    $skipped,
    $failed,
    $total_before,
    $total_after,
    max( 0, $total_before - $total_after )
);
