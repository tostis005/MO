<?php
/**
 * Production maintenance for Home PageSpeed 0.10.251.
 *
 * Runs through `wp eval-file` after the theme files are deployed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$begin = '# BEGIN El Mercado de Origen static cache 0.10.251';
$end   = '# END El Mercado de Origen static cache 0.10.251';
$block = <<<'HTACCESS'
# BEGIN El Mercado de Origen static cache 0.10.251
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/gif "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType image/avif "access plus 1 year"
    ExpiresByType image/svg+xml "access plus 1 year"
    ExpiresByType image/x-icon "access plus 1 year"
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType text/javascript "access plus 1 year"
    ExpiresByType font/woff "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
<IfModule mod_headers.c>
    <FilesMatch "\.(?:css|js|jpg|jpeg|png|gif|webp|avif|svg|ico|woff|woff2)$">
        Header set Cache-Control "public, max-age=31536000"
    </FilesMatch>
</IfModule>
# END El Mercado de Origen static cache 0.10.251
HTACCESS;

$htaccess = ABSPATH . '.htaccess';
$current  = is_file( $htaccess ) ? file_get_contents( $htaccess ) : '';

if ( false === $current ) {
	WP_CLI::error( 'No se pudo leer .htaccess.' );
}

$pattern = '~\n?' . preg_quote( $begin, '~' ) . '.*?' . preg_quote( $end, '~' ) . '\n?~s';
$current = preg_replace( $pattern, "\n", (string) $current );
if ( ! is_string( $current ) ) {
	WP_CLI::error( 'No se pudo normalizar .htaccess.' );
}

$updated = rtrim( $current ) . "\n\n" . $block . "\n";
if ( false === file_put_contents( $htaccess, $updated, LOCK_EX ) ) {
	WP_CLI::error( 'No se pudo escribir .htaccess.' );
}

WP_CLI::log( 'Cabeceras de caché estática instaladas en .htaccess.' );

if ( function_exists( 'elmercado_flush_home_cache' ) ) {
	elmercado_flush_home_cache();
	WP_CLI::log( 'Caché HTML/CSS de Home invalidada.' );
}

wp_cache_flush();
WP_CLI::log( 'Object cache de WordPress vaciada.' );

/* Diagnóstico de los adjuntos que PageSpeed señaló como más pesados. */
$uploads = wp_get_upload_dir();
$targets = array(
	$uploads['basedir'] . '/2026/08/Tolecarnes-fondo.jpg',
);
$jamon_candidates = glob( $uploads['basedir'] . '/2026/08/JAMON_ACT*.jpg' );
if ( is_array( $jamon_candidates ) ) {
	$targets = array_merge( $targets, $jamon_candidates );
}

foreach ( array_unique( $targets ) as $file ) {
	if ( ! is_file( $file ) ) {
		continue;
	}

	$relative = ltrim( str_replace( $uploads['basedir'], '', $file ), '/' );
	$url      = trailingslashit( $uploads['baseurl'] ) . $relative;
	$id       = attachment_url_to_postid( $url );
	$bytes    = filesize( $file );
	WP_CLI::log( sprintf( 'Imagen %s | attachment=%d | original=%s bytes', basename( $file ), (int) $id, number_format_i18n( (int) $bytes ) ) );

	if ( $id ) {
		$metadata = wp_get_attachment_metadata( $id );
		$sizes    = is_array( $metadata ) && isset( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ? array_keys( $metadata['sizes'] ) : array();
		WP_CLI::log( '  sub-tamaños: ' . ( $sizes ? implode( ', ', $sizes ) : 'ninguno' ) );
	}
}

/* Comprueba exactamente la API responsive usada por la portada. */
foreach ( array( 11052, 12667 ) as $attachment_id ) {
	$image  = wp_get_attachment_image_src( $attachment_id, 'medium_large' );
	$srcset = wp_get_attachment_image_srcset( $attachment_id, 'medium_large' );
	WP_CLI::log(
		sprintf(
			'Responsive attachment=%d | src=%s | srcset=%s',
			$attachment_id,
			is_array( $image ) && ! empty( $image[0] ) ? $image[0] : 'false',
			is_string( $srcset ) && '' !== $srcset ? substr( $srcset, 0, 500 ) : 'false'
		)
	);

	if ( function_exists( 'elmercado_home_responsive_producer_cards_010252' ) ) {
		$original = wp_get_attachment_url( $attachment_id );
		$sample   = '<img src="' . esc_url( (string) $original ) . '" alt="diagnostic">';
		WP_CLI::log( 'Rewrite sample ' . $attachment_id . ': ' . elmercado_home_responsive_producer_cards_010252( $sample ) );
	} else {
		WP_CLI::log( 'Rewrite function unavailable for attachment ' . $attachment_id );
	}
}
