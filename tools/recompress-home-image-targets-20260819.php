<?php
/**
 * Home-only image target recompression.
 * Run with WP CLI (plugins/themes skipped):
 * wp eval-file tools/recompress-home-image-targets-20260819.php --skip-plugins --skip-themes
 */

$uploads = wp_get_upload_dir();
$root    = trailingslashit( (string) $uploads['basedir'] );
$quality = 68;

$patterns = array(
    '2017/12/Garrafa_5L-360x480.jpg',
    '2017/12/Garrafa_5L-600x800.jpg',
    '2023/12/Aceite-sin-filtrar.jpg',
    '2023/12/Aceite-sin-filtrar-600x593.jpg',
    '2023/12/Aceite-sin-filtrar-300x297.jpg',
    '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-300x200.jpg',
    '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-450x300.jpg',
    '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-480x320.jpg',
    '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-600x400.jpg',
    '2026/08/JAMON_ACTO_ECOLOGICO1-scaled-768x512.jpg',
    '2026/08/Tolecarnes-fondo-300x300.jpg',
    '2026/08/Tolecarnes-fondo-400x400.jpg',
    '2026/08/Tolecarnes-fondo-480x480.jpg',
    '2026/08/Tolecarnes-fondo-600x600.jpg',
    '2026/08/Tolecarnes-fondo-768x768.jpg',
    '2020/06/Cerdo-ibérico-300x400.jpg',
    '2026/08/Packs-lotes-300x400-1.jpg',
);

function mdo_recompress_webp( string $source, string $dest, int $quality ): array {
    $info = @getimagesize( $source );
    if ( ! is_array( $info ) || empty( $info[0] ) || empty( $info[1] ) ) {
        return array( false, 'invalid_source' );
    }

    $mime = $info['mime'] ?? '';
    if ( 'image/jpeg' === $mime ) {
        $image = @imagecreatefromjpeg( $source );
    } elseif ( 'image/png' === $mime ) {
        $image = @imagecreatefrompng( $source );
    } else {
        return array( false, 'unsupported_mime:' . $mime );
    }

    if ( ! is_resource( $image ) && ! ( $image instanceof GdImage ) ) {
        return array( false, 'decode_failed' );
    }

    $ok = imagewebp( $image, $dest, $quality );
    imagedestroy( $image );
    if ( ! $ok || ! is_readable( $dest ) || filesize( $dest ) <= 0 ) {
        return array( false, 'encode_failed' );
    }

    return array( true, array( (int) $info[0], (int) $info[1] ) );
}

$before = 0;
$after  = 0;
$count  = 0;
$failed = 0;

foreach ( $patterns as $relative ) {
    $source = $root . $relative;
    if ( ! is_readable( $source ) ) {
        printf( "missing\t%s\n", $relative );
        continue;
    }

    $dest = preg_replace( '~\.(?:jpe?g|png)$~i', '.webp', $source );
    if ( ! is_string( $dest ) ) {
        ++$failed;
        continue;
    }

    $source_size = (int) filesize( $source );
    list( $ok, $detail ) = mdo_recompress_webp( $source, $dest, $quality );
    if ( ! $ok ) {
        ++$failed;
        printf( "failed\t%s\t%s\n", $relative, (string) $detail );
        continue;
    }

    $dest_size = (int) filesize( $dest );
    if ( $dest_size >= $source_size ) {
        @unlink( $dest );
        printf( "discarded\t%d\t%d\t%s\n", $source_size, $dest_size, $relative );
        continue;
    }

    ++$count;
    $before += $source_size;
    $after  += $dest_size;
    printf( "webp68\t%d\t%d\t%s\n", $source_size, $dest_size, $relative );
}

/* Candidato exacto para 315 CSS px a DPR 1.75: 315 * 1.75 = 551.25. */
$garrafa_source = $root . '2017/12/Garrafa_5L-600x800.jpg';
$garrafa_dest   = $root . '2017/12/Garrafa_5L-552x736.webp';
if ( is_readable( $garrafa_source ) ) {
    $src = @imagecreatefromjpeg( $garrafa_source );
    if ( $src ) {
        $dst = imagecreatetruecolor( 552, 736 );
        if ( $dst && imagecopyresampled( $dst, $src, 0, 0, 0, 0, 552, 736, imagesx( $src ), imagesy( $src ) ) && imagewebp( $dst, $garrafa_dest, $quality ) ) {
            printf( "custom\t552x736\t%d\t2017/12/Garrafa_5L-552x736.webp\n", filesize( $garrafa_dest ) );
        } else {
            ++$failed;
            echo "failed\tcustom_garrafa_552\n";
        }
        if ( $dst ) {
            imagedestroy( $dst );
        }
        imagedestroy( $src );
    }
}

printf( "summary count=%d failed=%d before=%d after=%d saved=%d quality=%d\n", $count, $failed, $before, $after, max( 0, $before - $after ), $quality );
