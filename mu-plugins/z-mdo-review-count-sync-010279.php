<?php
/**
 * Plugin Name: MDO - Dynamic review counts 0.10.279
 * Description: Mantiene en opciones de WordPress los recuentos de reseñas de Google y Trustpilot, los actualiza tras el ciclo nocturno de proveedores y alimenta la home desde esos valores.
 * Version: 0.10.279
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_REVIEW_GOOGLE_OPTION_010279          = 'mdo_google_review_count';
const MDO_REVIEW_TRUSTPILOT_OPTION_010279      = 'mdo_trustpilot_review_count';
const MDO_REVIEW_GOOGLE_UPDATED_OPTION_010279  = 'mdo_google_review_count_updated_at';
const MDO_REVIEW_TRUST_UPDATED_OPTION_010279   = 'mdo_trustpilot_review_count_updated_at';
const MDO_REVIEW_LAST_RESULT_OPTION_010279     = 'mdo_review_counts_last_result';
const MDO_REVIEW_CYCLE_HOOK_010279             = 'mdo_review_counts_after_supplier_cycle_010279';
const MDO_REVIEW_FALLBACK_HOOK_010279          = 'mdo_review_counts_daily_fallback_010279';
const MDO_REVIEW_GROUP_010279                  = 'mdo-review-counts';

/**
 * Seeds are only a safe first-render fallback. Successful remote reads replace
 * them immediately and subsequent renders always use the stored options.
 */
function mdo_review_counts_seed_options_010279(): void {
	if ( false === get_option( MDO_REVIEW_GOOGLE_OPTION_010279, false ) ) {
		add_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303, '', false );
	}
	if ( false === get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, false ) ) {
		add_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169, '', false );
	}
}
add_action( 'init', 'mdo_review_counts_seed_options_010279', 1 );

function mdo_review_counts_next_local_time_010279( int $hour, int $minute ): int {
	$now  = current_datetime();
	$next = $now->setTime( $hour, $minute, 0 );
	if ( $next <= $now ) {
		$next = $next->modify( '+1 day' );
	}
	return $next->getTimestamp();
}

function mdo_review_counts_schedule_single_010279( int $timestamp ): void {
	if ( function_exists( 'as_schedule_single_action' ) ) {
		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( MDO_REVIEW_CYCLE_HOOK_010279, array(), MDO_REVIEW_GROUP_010279 ) ) {
			return;
		}
		as_schedule_single_action( $timestamp, MDO_REVIEW_CYCLE_HOOK_010279, array(), MDO_REVIEW_GROUP_010279 );
		return;
	}
	if ( ! wp_next_scheduled( MDO_REVIEW_CYCLE_HOOK_010279 ) ) {
		wp_schedule_single_event( $timestamp, MDO_REVIEW_CYCLE_HOOK_010279 );
	}
}

/**
 * The supplier dispatcher runs at 03:00 and spaces stores by 30 minutes. We
 * enqueue a checker after dispatch; it waits while any supplier/product action
 * is still pending or running, so the review refresh truly happens afterwards.
 */
function mdo_review_counts_after_dispatch_010279(): void {
	mdo_review_counts_schedule_single_010279( time() + ( 20 * MINUTE_IN_SECONDS ) );
}
add_action( 'mdo_supplier_sync_dispatch', 'mdo_review_counts_after_dispatch_010279', 30 );

function mdo_review_counts_supplier_work_pending_010279(): bool {
	$hooks = array(
		'mdo_supplier_sync_run_supplier',
		'mdo_supplier_sync_scrape_product',
	);

	if ( function_exists( 'as_has_scheduled_action' ) ) {
		foreach ( $hooks as $hook ) {
			if ( as_has_scheduled_action( $hook, null, 'mdo-supplier-sync' ) ) {
				return true;
			}
		}
	}

	global $wpdb;
	$actions_table = $wpdb->prefix . 'actionscheduler_actions';
	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $actions_table ) ) === $actions_table ) {
		$placeholders = implode( ',', array_fill( 0, count( $hooks ), '%s' ) );
		$sql = $wpdb->prepare(
			"SELECT COUNT(*) FROM {$actions_table} WHERE hook IN ({$placeholders}) AND status IN ('pending','in-progress')",
			...$hooks
		);
		return (int) $wpdb->get_var( $sql ) > 0;
	}

	return false;
}

function mdo_review_counts_updated_today_010279( string $option ): bool {
	$timestamp = (int) get_option( $option, 0 );
	return $timestamp > 0 && wp_date( 'Y-m-d', $timestamp ) === wp_date( 'Y-m-d' );
}

function mdo_review_counts_day_complete_010279(): bool {
	return mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_OPTION_010279 )
		&& mdo_review_counts_updated_today_010279( MDO_REVIEW_TRUST_UPDATED_OPTION_010279 );
}

function mdo_review_counts_after_supplier_cycle_010279(): void {
	if ( mdo_review_counts_supplier_work_pending_010279() ) {
		mdo_review_counts_schedule_single_010279( time() + ( 15 * MINUTE_IN_SECONDS ) );
		return;
	}
	if ( ! mdo_review_counts_day_complete_010279() ) {
		mdo_review_counts_refresh_010279( false );
	}
}
add_action( MDO_REVIEW_CYCLE_HOOK_010279, 'mdo_review_counts_after_supplier_cycle_010279' );

/** Daily safety net in case the supplier dispatcher or its final checker fails. */
function mdo_review_counts_ensure_fallback_010279(): void {
	$next = mdo_review_counts_next_local_time_010279( 9, 30 );
	if ( function_exists( 'as_schedule_recurring_action' ) ) {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! as_has_scheduled_action( MDO_REVIEW_FALLBACK_HOOK_010279, array(), MDO_REVIEW_GROUP_010279 ) ) {
			as_schedule_recurring_action( $next, DAY_IN_SECONDS, MDO_REVIEW_FALLBACK_HOOK_010279, array(), MDO_REVIEW_GROUP_010279 );
		}
		return;
	}
	if ( ! wp_next_scheduled( MDO_REVIEW_FALLBACK_HOOK_010279 ) ) {
		wp_schedule_event( $next, 'daily', MDO_REVIEW_FALLBACK_HOOK_010279 );
	}
}
add_action( 'init', 'mdo_review_counts_ensure_fallback_010279', 100 );
add_action( 'action_scheduler_init', 'mdo_review_counts_ensure_fallback_010279', 100 );

function mdo_review_counts_fallback_010279(): void {
	if ( mdo_review_counts_day_complete_010279() ) {
		return;
	}
	if ( mdo_review_counts_supplier_work_pending_010279() ) {
		mdo_review_counts_schedule_single_010279( time() + ( 15 * MINUTE_IN_SECONDS ) );
		return;
	}
	mdo_review_counts_refresh_010279( false );
}
add_action( MDO_REVIEW_FALLBACK_HOOK_010279, 'mdo_review_counts_fallback_010279' );

function mdo_review_counts_remote_body_010279( string $url ) {
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 25,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (compatible; ElMercadoDeOrigenReviewSync/1.0; +https://www.elmercadodeorigen.com/)',
			'headers'     => array(
				'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
				'Cache-Control'   => 'no-cache',
			),
		)
	);
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	if ( $status < 200 || $status >= 400 ) {
		return new WP_Error( 'mdo_review_http', 'HTTP ' . $status );
	}
	$body = (string) wp_remote_retrieve_body( $response );
	if ( strlen( $body ) < 500 ) {
		return new WP_Error( 'mdo_review_empty', 'Respuesta demasiado corta.' );
	}
	return $body;
}

function mdo_review_counts_normalize_number_010279( string $raw ): int {
	$digits = preg_replace( '/[^0-9]/', '', $raw );
	return is_string( $digits ) && '' !== $digits ? (int) $digits : 0;
}

function mdo_review_counts_parse_010279( string $body, string $source ): int {
	$patterns = 'google' === $source
		? array(
			'/[\"\']reviewCount[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
			'/([0-9][0-9.,]*)\s*(?:total|reviews?)\b/i',
			'/trusted\s+by\s+over\s+([0-9][0-9.,]*)/i',
		)
		: array(
			'/[\"\']reviewCount[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
			'/[\"\']numberOfReviews[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
			'/([0-9][0-9.,]*)\s*(?:opiniones|reviews?)\b/i',
		);

	foreach ( $patterns as $pattern ) {
		if ( preg_match( $pattern, $body, $match ) ) {
			$count = mdo_review_counts_normalize_number_010279( (string) $match[1] );
			if ( $count > 0 && $count < 1000000 ) {
				return $count;
			}
		}
	}
	return 0;
}

function mdo_review_counts_fetch_google_010279() {
	$body = mdo_review_counts_remote_body_010279( 'https://www.trustindex.io/reviews/www.elmercadodeorigen.com' );
	if ( is_wp_error( $body ) ) {
		return $body;
	}
	$count = mdo_review_counts_parse_010279( $body, 'google' );
	return $count > 0 ? $count : new WP_Error( 'mdo_google_parse', 'No se pudo extraer el recuento de Google.' );
}

function mdo_review_counts_fetch_trustpilot_010279() {
	$body = mdo_review_counts_remote_body_010279( 'https://es.trustpilot.com/review/elmercadodeorigen.com' );
	if ( is_wp_error( $body ) ) {
		return $body;
	}
	$count = mdo_review_counts_parse_010279( $body, 'trustpilot' );
	return $count > 0 ? $count : new WP_Error( 'mdo_trustpilot_parse', 'No se pudo extraer el recuento de Trustpilot.' );
}

function mdo_review_counts_purge_home_cache_010279(): void {
	$front_id = (int) get_option( 'page_on_front', 0 );
	if ( $front_id > 0 ) {
		clean_post_cache( $front_id );
	}
	wp_cache_delete( 'mdo_review_counts_home', 'mdo' );
	if ( function_exists( 'rocket_clean_home' ) ) {
		rocket_clean_home();
	}
	if ( function_exists( 'rocket_clean_domain' ) ) {
		rocket_clean_domain();
	}
	if ( function_exists( 'wp_cache_clear_cache' ) ) {
		wp_cache_clear_cache();
	}
	do_action( 'litespeed_purge_url', home_url( '/' ) );
	do_action( 'litespeed_purge_all' );
}

/**
 * Refreshes both sources independently. A failed source keeps its last valid
 * count, so a transient external error can never replace a real value with 0.
 */
function mdo_review_counts_refresh_010279( bool $force = false ): array {
	mdo_review_counts_seed_options_010279();
	$before_google = (int) get_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303 );
	$before_trust  = (int) get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169 );
	$result        = array(
		'google'     => $before_google,
		'trustpilot' => $before_trust,
		'google_ok'  => false,
		'trust_ok'   => false,
		'changed'    => false,
		'at'         => time(),
	);

	if ( $force || ! mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_OPTION_010279 ) ) {
		$google = mdo_review_counts_fetch_google_010279();
		if ( is_wp_error( $google ) ) {
			$result['google_error'] = $google->get_error_message();
		} else {
			$result['google']    = (int) $google;
			$result['google_ok'] = true;
			update_option( MDO_REVIEW_GOOGLE_OPTION_010279, (int) $google, false );
			update_option( MDO_REVIEW_GOOGLE_UPDATED_OPTION_010279, time(), false );
		}
	} else {
		$result['google_ok'] = true;
	}

	if ( $force || ! mdo_review_counts_updated_today_010279( MDO_REVIEW_TRUST_UPDATED_OPTION_010279 ) ) {
		$trust = mdo_review_counts_fetch_trustpilot_010279();
		if ( is_wp_error( $trust ) ) {
			$result['trust_error'] = $trust->get_error_message();
		} else {
			$result['trustpilot'] = (int) $trust;
			$result['trust_ok']    = true;
			update_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, (int) $trust, false );
			update_option( MDO_REVIEW_TRUST_UPDATED_OPTION_010279, time(), false );
		}
	} else {
		$result['trust_ok'] = true;
	}

	$result['changed'] = $before_google !== (int) $result['google'] || $before_trust !== (int) $result['trustpilot'];
	update_option( MDO_REVIEW_LAST_RESULT_OPTION_010279, wp_json_encode( $result ), false );
	if ( $force || $result['changed'] ) {
		mdo_review_counts_purge_home_cache_010279();
	}
	return $result;
}

function mdo_google_review_count_010279(): int {
	mdo_review_counts_seed_options_010279();
	return max( 0, (int) get_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303 ) );
}

function mdo_trustpilot_review_count_010279(): int {
	mdo_review_counts_seed_options_010279();
	return max( 0, (int) get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169 ) );
}

add_shortcode( 'mdo_google_review_count', static fn(): string => (string) mdo_google_review_count_010279() );
add_shortcode( 'mdo_trustpilot_review_count', static fn(): string => (string) mdo_trustpilot_review_count_010279() );

/**
 * Rewrites only a numeric text node that is followed by the review unit and is
 * inside a bounded segment beginning at the relevant provider name. This keeps
 * product prices and all unrelated numbers untouched.
 */
function mdo_review_counts_rewrite_provider_010279( string $html, string $provider, int $count, array $units ): string {
	$provider_q = preg_quote( $provider, '/' );
	$units_q    = implode( '|', array_map( static fn( string $unit ): string => preg_quote( $unit, '/' ), $units ) );
	$pattern    = '/(' . $provider_q . '(?:(?!Google|Trustpilot)[\s\S]){0,1600}?)([0-9]{1,6})(?=(?:\s|&nbsp;|&#160;|<[^>]*>){0,16}(?:' . $units_q . ')\b)/iu';
	$rewritten  = preg_replace_callback(
		$pattern,
		static fn( array $m ): string => (string) $m[1] . (string) $count,
		$html
	);
	return is_string( $rewritten ) ? $rewritten : $html;
}

function mdo_review_counts_rewrite_home_010279( string $html ): string {
	$google = mdo_google_review_count_010279();
	$trust  = mdo_trustpilot_review_count_010279();
	$html   = mdo_review_counts_rewrite_provider_010279( $html, 'Google', $google, array( 'reseñas', 'reviews' ) );
	$html   = mdo_review_counts_rewrite_provider_010279( $html, 'Trustpilot', $trust, array( 'opiniones', 'reseñas', 'reviews' ) );

	$updated = max(
		(int) get_option( MDO_REVIEW_GOOGLE_UPDATED_OPTION_010279, 0 ),
		(int) get_option( MDO_REVIEW_TRUST_UPDATED_OPTION_010279, 0 )
	);
	$marker = sprintf(
		'<!-- mdo-review-counts-010279 google=%d trustpilot=%d updated=%s -->',
		$google,
		$trust,
		$updated > 0 ? esc_html( wp_date( 'c', $updated ) ) : 'pending'
	);
	if ( false !== stripos( $html, '</body>' ) ) {
		$html = preg_replace( '/<\/body>/i', $marker . '</body>', $html, 1 ) ?: $html;
	} else {
		$html .= $marker;
	}
	return $html;
}

function mdo_review_counts_start_home_buffer_010279(): void {
	if ( is_admin() || wp_doing_ajax() || ! is_front_page() || is_feed() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
		return;
	}
	ob_start( 'mdo_review_counts_rewrite_home_010279' );
}
add_action( 'template_redirect', 'mdo_review_counts_start_home_buffer_010279', 0 );
