<?php
/**
 * Hide a clearly expired single editorial special banner.
 *
 * The promotional card comes from MDO_Home_Featured_Special, which is supplied
 * by a separate plugin. Keep the plugin and the active cards unchanged; a
 * clearly past, explicit public expiry date must not be advertised as current.
 * A missing, ambiguous or invalid date leaves the original HTML untouched.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @param string $html HTML returned by the promotion plugin.
 * @param string $today Optional YYYY-MM-DD date for deterministic tests.
 * @return string Original HTML, or an empty string when definitely expired.
 */
function elmercado_blog_hide_expired_special_20261012( string $html, string $today = '' ): string {
	if ( '' === trim( $html ) ) {
		return $html;
	}

	$text = html_entity_decode( strip_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = (string) preg_replace( '/\\s+/u', ' ', $text );

	$matches = array();
	$count = preg_match_all(
		'~(?:available\\s+until|disponible\\s+hasta|v[aá]lid[oa]\\s+hasta)\\s*:?\\s*(\\d{1,2})\\s+(?:de\\s+)?([a-záéíóúüñ]+)\\s+(?:de\\s+)?(\\d{4})~iu',
		$text,
		$matches,
		PREG_SET_ORDER
	);

	// A block with multiple expiry dates cannot be classified safely.
	if ( 1 !== $count ) {
		return $html;
	}

	$months = array(
		'january' => 1, 'february' => 2, 'march' => 3, 'april' => 4,
		'may' => 5, 'june' => 6, 'july' => 7, 'august' => 8,
		'september' => 9, 'october' => 10, 'november' => 11, 'december' => 12,
		'enero' => 1, 'febrero' => 2, 'marzo' => 3, 'abril' => 4,
		'mayo' => 5, 'junio' => 6, 'julio' => 7, 'agosto' => 8,
		'septiembre' => 9, 'setiembre' => 9, 'octubre' => 10,
		'noviembre' => 11, 'diciembre' => 12,
	);
	$month_name = strtolower( (string) $matches[0][2] );
	$month = isset( $months[ $month_name ] ) ? $months[ $month_name ] : 0;
	$day = (int) $matches[0][1];
	$year = (int) $matches[0][3];

	if ( ! checkdate( $month, $day, $year ) ) {
		return $html;
	}

	$deadline = sprintf( '%04d-%02d-%02d', $year, $month, $day );
	$today = '' !== $today ? $today : wp_date( 'Y-m-d' );

	if ( 1 !== preg_match( '/^\\d{4}-\\d{2}-\\d{2}$/', $today ) ) {
		return $html;
	}

	return $deadline < $today ? '' : $html;
}
