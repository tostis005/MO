<?php
/**
 * Endpoint geográfico ultraligero para publicidad.
 *
 * No carga WordPress ni WooCommerce. Solo aprovecha cabeceras de país ya
 * resueltas por CDN/proxy/servidor. Si no hay una cabecera fiable, devuelve
 * país vacío y el navegador recurre al endpoint REST completo.
 */

header( 'Content-Type: application/json; charset=UTF-8' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );
header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );

$candidates = array(
	'cf'         => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '',
	'x_country'  => $_SERVER['HTTP_X_COUNTRY_CODE'] ?? '',
	'cloudfront' => $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] ?? '',
	'geoip'      => $_SERVER['GEOIP_COUNTRY_CODE'] ?? '',
	'geoip_redir'=> $_SERVER['REDIRECT_GEOIP_COUNTRY_CODE'] ?? '',
);

$country = '';
$source  = 'none';

foreach ( $candidates as $candidate_source => $candidate ) {
	$candidate = strtoupper( trim( (string) $candidate ) );
	if ( preg_match( '/^[A-Z]{2}$/', $candidate ) && ! in_array( $candidate, array( 'XX', 'T1' ), true ) ) {
		$country = $candidate;
		$source  = $candidate_source;
		break;
	}
}

echo json_encode(
	array(
		'country' => $country,
		'source'  => $source,
	),
	JSON_UNESCAPED_SLASHES
);
