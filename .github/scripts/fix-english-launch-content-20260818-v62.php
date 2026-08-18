<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;

$backup_key = 'mdo_english_launch_backup_20260818_v62';
$backup     = get_option( $backup_key, array() );
if ( ! is_array( $backup ) ) { $backup = array(); }
$report = array( 'backup_key' => $backup_key, 'posts' => array(), 'terms' => array(), 'ui' => array() );

$find_post_by_en_meta = static function ( string $key, string $value ) use ( $wpdb ): int {
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%s ORDER BY post_id ASC LIMIT 1",
			$key,
			$value
		)
	);
};

$set_en_meta = static function ( int $post_id, string $key, string $value, string $label ) use ( &$backup, &$report ): void {
	if ( $post_id < 1 ) { $report['posts'][ $label ] = array( 'status' => 'not-found' ); return; }
	$old = (string) get_post_meta( $post_id, $key, true );
	if ( ! isset( $backup['postmeta'][ $post_id ][ $key ] ) ) {
		$backup['postmeta'][ $post_id ][ $key ] = $old;
	}
	update_post_meta( $post_id, $key, $value );
	$report['posts'][ $label ] = array(
		'post_id' => $post_id,
		'meta_key' => $key,
		'old_chars' => mb_strlen( $old ),
		'new_chars' => mb_strlen( $value ),
		'changed' => $old !== $value,
	);
};

/* Journal cards: persist natural English excerpts in Falang post metadata. */
$ham_id = $find_post_by_en_meta( '_en_US_post_title', 'Iberian ham' );
$set_en_meta(
	$ham_id,
	'_en_US_post_excerpt',
	'Iberian ham is a unique product whose history, according to documentary evidence, dates back to Roman times. Its distinctive sensory characteristics and nutritional properties give it an exceptional quality closely linked to breed, feeding, curing and origin.',
	'journal_iberian_ham_excerpt'
);

$oil_article_id = $find_post_by_en_meta( '_en_US_post_title', 'Extra virgin olive oil' );
$set_en_meta(
	$oil_article_id,
	'_en_US_post_excerpt',
	'November is harvest time in the Córdoba countryside. Although the timing differs in other parts of Spain, choosing the right moment to pick the olives is essential to the aroma, flavour and quality of the resulting extra virgin olive oil.',
	'journal_evoo_excerpt'
);

/* Cookie Policy: correct the table label and replace Spanish/obsolete browser-help URLs with current English official resources. */
$cookie_id = $find_post_by_en_meta( '_en_US_post_name', 'cookie-policy' );
if ( $cookie_id > 0 ) {
	$key = '_en_US_post_content';
	$old = (string) get_post_meta( $cookie_id, $key, true );
	if ( ! isset( $backup['postmeta'][ $cookie_id ][ $key ] ) ) {
		$backup['postmeta'][ $cookie_id ][ $key ] = $old;
	}
	$replacements = array(
		'First name' => 'Name',
		'https://support.google.com/chrome/answer/95647?hl=es' => 'https://support.google.com/chrome/answer/95647?hl=en',
		'http://windows.microsoft.com/es-es/windows-vista/cookies-frequently-asked-questions' => 'https://support.microsoft.com/en-us/edge/microsoft-edge-browsing-data-and-privacy',
		'https://windows.microsoft.com/es-es/windows-vista/cookies-frequently-asked-questions' => 'https://support.microsoft.com/en-us/edge/microsoft-edge-browsing-data-and-privacy',
		'http://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-que-los-sitios-we' => 'https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox',
		'https://support.mozilla.org/es/kb/habilitar-y-deshabilitar-cookies-que-los-sitios-we' => 'https://support.mozilla.org/en-US/kb/clear-cookies-and-site-data-firefox',
		'http://www.apple.com/es/privacy/use-of-cookies/' => 'https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac',
		'https://www.apple.com/es/privacy/use-of-cookies/' => 'https://support.apple.com/guide/safari/manage-cookies-sfri11471/mac',
		'http://help.opera.com/Windows/11.50/es-ES/cookies.html' => 'https://help.opera.com/en/latest/web-preferences/',
		'https://help.opera.com/Windows/11.50/es-ES/cookies.html' => 'https://help.opera.com/en/latest/web-preferences/',
		'http://www.youronlinechoices.com/es/' => 'https://www.youronlinechoices.com/',
		'https://www.youronlinechoices.com/es/' => 'https://www.youronlinechoices.com/',
		'https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage?hl=es#analyticsjs' => 'https://developers.google.com/analytics/devguides/collection/analyticsjs/cookie-usage?hl=en#analyticsjs',
		'http://www.google.es/policies/privacy/ads/#toc-doubleclick' => 'https://policies.google.com/technologies/ads?hl=en',
		'http://www.google.es/policies/privacy/ads/' => 'https://policies.google.com/technologies/ads?hl=en',
	);
	$new = strtr( $old, $replacements );
	update_post_meta( $cookie_id, $key, $new );
	$report['posts']['cookie_policy'] = array(
		'post_id' => $cookie_id,
		'changed' => $new !== $old,
		'old_chars' => mb_strlen( $old ),
		'new_chars' => mb_strlen( $new ),
		'spanish_help_urls_remaining' => preg_match_all( '#(?:hl=es|/es(?:-|/)|es-ES)#i', $new ),
	);
} else {
	$report['posts']['cookie_policy'] = array( 'status' => 'not-found' );
}

/* Make the legacy 23/24 attribute truthful rather than presenting it as a current “new harvest”. */
$legacy_terms = $wpdb->get_results(
	"SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key='_en_US_name' AND meta_value LIKE '%NEW HARVEST 23/24%' ORDER BY term_id",
	ARRAY_A
);
foreach ( (array) $legacy_terms as $row ) {
	$term_id = (int) $row['term_id'];
	$old     = (string) $row['meta_value'];
	$new     = str_replace( 'NEW HARVEST 23/24', '2023/24 harvest', $old );
	if ( ! isset( $backup['termmeta'][ $term_id ]['_en_US_name'] ) ) {
		$backup['termmeta'][ $term_id ]['_en_US_name'] = $old;
	}
	update_term_meta( $term_id, '_en_US_name', $new );
	$report['terms'][] = array( 'term_id' => $term_id, 'old' => $old, 'new' => $new, 'changed' => $new !== $old );
}

/* Persist missing English interface strings as well as the MU-plugin fallback. */
$ui_key = 'elmercado_en_ui_copy_010245';
$ui = get_option( $ui_key, array() );
if ( ! is_array( $ui ) ) { $ui = array(); }
if ( ! isset( $backup['options'][ $ui_key ] ) ) { $backup['options'][ $ui_key ] = $ui; }
$ui['Quitar'] = 'Remove';
$ui['Variedad'] = 'Variety';
update_option( $ui_key, $ui, false );
$report['ui'] = array( 'Quitar' => $ui['Quitar'], 'Variedad' => $ui['Variedad'] );

update_option( $backup_key, $backup, false );

/* Clear caches/transients that can retain translated HTML or taxonomy labels. */
if ( function_exists( 'wc_delete_product_transients' ) ) { wc_delete_product_transients(); }
wp_cache_flush();

echo wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
