<?php
/**
 * Targeted Home WebP optimizer for the three current Lighthouse image findings.
 *
 * Run with:
 *   wp eval-file tools/generate-home-target-webp-20260820.php
 *
 * For every target it tries quality 72, 68 and 64 and keeps the highest-quality
 * candidate that saves at least 20% versus the source JPEG. Existing WebPs are
 * backed up next to the file before replacement.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$uploads = wp_get_upload_dir();
$base    = trailingslashit( (string) $uploads['basedir'] );
$targets = array(
    '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-450x300.jpg',
    '2026/08/Tolecarnes-fondo-400x400.jpg',
    '2026/08/Packs-lotes-300x400-1.jpg',
);
$qualities = array( 72, 68, 64 );

foreach ( $targets as $relative ) {
    $source = $base . $relative;
    if ( ! is_readable( $source ) ) {
        printf( "missing\t%s\n", $relative );
        continue;
    }

    $source_size = (int) filesize( $source );
    $dest        = preg_replace( '~\.jpe?g$~i', '.webp', $source );
    if ( ! is_string( $dest ) ) {
        printf( "invalid_dest\t%s\n", $relative );
        continue;
    }

    $existing_size = is_readable( $dest ) ? (int) filesize( $dest ) : 0;
    $best = null;

    foreach ( $qualities as $quality ) {
        $editor = wp_get_image_editor( $source );
        if ( is_wp_error( $editor ) ) {
            printf( "editor_error\t%s\t%s\n", $relative, $editor->get_error_message() );
            break;
        }

        $editor->set_quality( $quality );
        $candidate = preg_replace( '~\.webp$~i', '.mdo-q' . $quality . '.webp', $dest );
        $saved     = $editor->save( $candidate, 'image/webp' );
        if ( is_wp_error( $saved ) || ! is_readable( $candidate ) ) {
            printf( "candidate_error\tq%d\t%s\n", $quality, $relative );
            @unlink( $candidate );
            continue;
        }

        $candidate_size = (int) filesize( $candidate );
        $ratio          = $source_size > 0 ? $candidate_size / $source_size : 1;
        printf(
            "candidate\tq%d\t%d\t%d\t%.4f\t%s\n",
            $quality,
            $source_size,
            $candidate_size,
            $ratio,
            $relative
        );

        if ( $candidate_size > 0 && $candidate_size <= (int) floor( $source_size * 0.80 ) ) {
            $best = array(
                'file'    => $candidate,
                'quality' => $quality,
                'size'    => $candidate_size,
            );
            break;
        }

        @unlink( $candidate );
    }

    /* Clean lower-quality candidates that were never selected. */
    foreach ( $qualities as $quality ) {
        $candidate = preg_replace( '~\.webp$~i', '.mdo-q' . $quality . '.webp', $dest );
        if ( is_string( $candidate ) && ( null === $best || $candidate !== $best['file'] ) ) {
            @unlink( $candidate );
        }
    }

    if ( null === $best ) {
        printf( "skip_no_20pct_saving\t%d\t%d\t%s\n", $source_size, $existing_size, $relative );
        continue;
    }

    $backup = $dest . '.pre-20260820';
    if ( is_readable( $dest ) && ! is_readable( $backup ) ) {
        @copy( $dest, $backup );
    }

    if ( ! @rename( $best['file'], $dest ) ) {
        @unlink( $best['file'] );
        printf( "replace_failed\t%s\n", $relative );
        continue;
    }
    @chmod( $dest, 0644 );

    $saved_bytes = max( 0, $source_size - (int) $best['size'] );
    printf(
        "selected\tq%d\t%d\t%d\t%d\told_webp=%d\t%s\n",
        (int) $best['quality'],
        $source_size,
        (int) $best['size'],
        $saved_bytes,
        $existing_size,
        $relative
    );
}
