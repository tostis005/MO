<?php
/**
 * Endpoint geográfico ultraligero para publicidad.
 *
 * Evita arrancar WordPress. Primero usa cabeceras de país del proxy/CDN y,
 * si no existen, resuelve la IP del visitante contra la misma base MaxMind
 * que mantiene WooCommerce.
 */

header( 'Content-Type: application/json; charset=UTF-8' );
header( 'Cache-Control: no-store, no-cache, must-revalidate, max-age=0, private' );
header( 'Pragma: no-cache' );
header( 'Expires: 0' );
header( 'X-Robots-Tag: noindex, nofollow, noarchive', true );

function emo_ad_geo_country_code_010298( $value ): string {
	$value = strtoupper( trim( (string) $value ) );
	return preg_match( '/^[A-Z]{2}$/', $value ) && ! in_array( $value, array( 'XX', 'T1' ), true ) ? $value : '';
}

function emo_ad_geo_ip_010298( $value ): string {
	$value = trim( explode( ',', (string) $value )[0] ?? '' );

	if ( preg_match( '/^\[([^\]]+)\](?::\d+)?$/', $value, $matches ) ) {
		$value = $matches[1];
	} elseif ( preg_match( '/^(\d{1,3}(?:\.\d{1,3}){3}):\d+$/', $value, $matches ) ) {
		$value = $matches[1];
	}

	return filter_var( $value, FILTER_VALIDATE_IP ) ? $value : '';
}

$candidates = array(
	'cf'          => $_SERVER['HTTP_CF_IPCOUNTRY'] ?? '',
	'x_country'   => $_SERVER['HTTP_X_COUNTRY_CODE'] ?? '',
	'cloudfront'  => $_SERVER['HTTP_CLOUDFRONT_VIEWER_COUNTRY'] ?? '',
	'geoip'       => $_SERVER['GEOIP_COUNTRY_CODE'] ?? '',
	'geoip_redir' => $_SERVER['REDIRECT_GEOIP_COUNTRY_CODE'] ?? '',
	'mm'          => $_SERVER['MM_COUNTRY_CODE'] ?? '',
);

$country = '';
$source  = 'none';

foreach ( $candidates as $candidate_source => $candidate ) {
	$country = emo_ad_geo_country_code_010298( $candidate );
	if ( '' !== $country ) {
		$source = $candidate_source;
		break;
	}
}

if ( '' === $country ) {
	$ip_candidates = array(
		$_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',
		$_SERVER['HTTP_X_REAL_IP'] ?? '',
		$_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',
		$_SERVER['REMOTE_ADDR'] ?? '',
	);

	$ip = '';
	foreach ( $ip_candidates as $candidate ) {
		$ip = emo_ad_geo_ip_010298( $candidate );
		if ( '' !== $ip ) {
			break;
		}
	}

	if ( '' !== $ip ) {
		$wp_content = dirname( __DIR__, 3 );
		$autoload   = $wp_content . '/plugins/woocommerce/vendor/autoload.php';
		$databases  = array(
			$wp_content . '/plugins/wordfence/lib/geoip.mmdb',
			$wp_content . '/wflogs/GeoLite2-Country.mmdb',
		);
		$woocommerce_databases = glob( $wp_content . '/uploads/woocommerce_uploads/*GeoLite2-Country.mmdb' );
		if ( is_array( $woocommerce_databases ) ) {
			$databases = array_merge( $woocommerce_databases, $databases );
		}
		$databases = array_values( array_filter( array_unique( $databases ), 'is_file' ) );

		if ( is_file( $autoload ) && ! empty( $databases ) ) {
			try {
				require_once $autoload;

				if ( class_exists( 'MaxMind\\Db\\Reader' ) ) {
					$reader = new MaxMind\Db\Reader( $databases[0] );
					$data   = $reader->get( $ip );
					$reader->close();

					if ( isset( $data['country']['iso_code'] ) ) {
						$country = emo_ad_geo_country_code_010298( $data['country']['iso_code'] );
						if ( '' !== $country ) {
							$source = 'maxmind';
						}
					}
				}
			} catch ( Throwable $error ) {
				// Fallo seguro: el navegador recurrirá al endpoint REST completo.
			}
		}
	}
}

echo json_encode(
	array(
		'country' => $country,
		'source'  => $source,
	),
	JSON_UNESCAPED_SLASHES
);
