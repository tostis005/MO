<?php
/**
 * Plugin Name: MDO - Dynamic review counts 0.10.280
 * Description: Mantiene en opciones de WordPress los recuentos de Google y Trustpilot y los refresca después del ciclo nocturno de proveedores, con respaldo diario.
 * Version: 0.10.280
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_REVIEW_GOOGLE_OPTION_010279       = 'mdo_google_review_count';
const MDO_REVIEW_TRUSTPILOT_OPTION_010279   = 'mdo_trustpilot_review_count';
const MDO_REVIEW_GOOGLE_UPDATED_010279      = 'mdo_google_review_count_updated_at';
const MDO_REVIEW_TRUST_UPDATED_010279       = 'mdo_trustpilot_review_count_updated_at';
const MDO_REVIEW_LAST_RESULT_010279         = 'mdo_review_counts_last_result';
const MDO_REVIEW_CYCLE_HOOK_010279          = 'mdo_review_counts_after_supplier_cycle_010279';
const MDO_REVIEW_FALLBACK_HOOK_010279       = 'mdo_review_counts_daily_fallback_010279';
const MDO_REVIEW_GROUP_010279               = 'mdo-review-counts';

function mdo_review_counts_seed_options_010279(): void {
	if ( false === get_option( MDO_REVIEW_GOOGLE_OPTION_010279, false ) ) {
		add_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303, '', false );
	}
	if ( false === get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, false ) ) {
		add_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169, '', false );
	}
}
add_action( 'init', 'mdo_review_counts_seed_options_010279', 1 );

function mdo_review_counts_action_scheduler_ready_010279(): bool {
	return did_action( 'action_scheduler_init' ) > 0;
}

function mdo_review_counts_next_local_time_010279( int $hour, int $minute ): int {
	$now  = current_datetime();
	$next = $now->setTime( $hour, $minute, 0 );
	if ( $next <= $now ) {
		$next = $next->modify( '+1 day' );
	}
	return $next->getTimestamp();
}

function mdo_review_counts_schedule_single_010279( int $timestamp ): void {
	if ( mdo_review_counts_action_scheduler_ready_010279() && function_exists( 'as_schedule_single_action' ) ) {
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
 * El ciclo de proveedores arranca a las 03:00 y separa tiendas 30 minutos.
 * Registramos un comprobador tras el dispatcher y éste espera hasta que no
 * queden trabajos de tienda/producto pendientes antes de consultar reseñas.
 */
function mdo_review_counts_after_dispatch_010279(): void {
	mdo_review_counts_schedule_single_010279( time() + ( 20 * MINUTE_IN_SECONDS ) );
}
add_action( 'mdo_supplier_sync_dispatch', 'mdo_review_counts_after_dispatch_010279', 30 );

function mdo_review_counts_supplier_work_pending_010279(): bool {
	$hooks = array( 'mdo_supplier_sync_run_supplier', 'mdo_supplier_sync_scrape_product' );

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
	return mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_010279 )
		&& mdo_review_counts_updated_today_010279( MDO_REVIEW_TRUST_UPDATED_010279 );
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

/** Respaldo diario por si el ciclo nocturno no llega a completarse. */
function mdo_review_counts_ensure_fallback_010279(): void {
	$next = mdo_review_counts_next_local_time_010279( 9, 30 );

	if ( mdo_review_counts_action_scheduler_ready_010279() && function_exists( 'as_schedule_recurring_action' ) ) {
		if ( ! function_exists( 'as_has_scheduled_action' ) || ! as_has_scheduled_action( MDO_REVIEW_FALLBACK_HOOK_010279, array(), MDO_REVIEW_GROUP_010279 ) ) {
			as_schedule_recurring_action( $next, DAY_IN_SECONDS, MDO_REVIEW_FALLBACK_HOOK_010279, array(), MDO_REVIEW_GROUP_010279 );
		}
		return;
	}

	if ( ! function_exists( 'as_schedule_recurring_action' ) && ! wp_next_scheduled( MDO_REVIEW_FALLBACK_HOOK_010279 ) ) {
		wp_schedule_event( $next, 'daily', MDO_REVIEW_FALLBACK_HOOK_010279 );
	}
}
add_action( 'action_scheduler_init', 'mdo_review_counts_ensure_fallback_010279', 100 );
add_action(
	'init',
	static function (): void {
		if ( ! function_exists( 'as_schedule_recurring_action' ) ) {
			mdo_review_counts_ensure_fallback_010279();
		}
	},
	100
);

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

function mdo_review_counts_response_010279( string $url, bool $browser_user_agent = false ) {
	$user_agent = $browser_user_agent
		? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36'
		: 'Mozilla/5.0 (compatible; ElMercadoDeOrigenReviewSync/1.0; +https://www.elmercadodeorigen.com/)';

	return wp_remote_get(
		$url,
		array(
			'timeout'     => 30,
			'redirection' => 5,
			'user-agent'  => $user_agent,
			'headers'     => array(
				'Accept'          => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
				'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
				'Cache-Control'   => 'no-cache',
				'Pragma'          => 'no-cache',
			),
		)
	);
}

function mdo_review_counts_normalize_number_010279( string $raw ): int {
	$digits = preg_replace( '/[^0-9]/', '', $raw );
	return is_string( $digits ) && '' !== $digits ? (int) $digits : 0;
}

function mdo_review_counts_plain_text_010279( string $body ): string {
	$text = html_entity_decode( wp_strip_all_tags( $body ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	$text = preg_replace( '/\s+/u', ' ', $text );
	return is_string( $text ) ? trim( $text ) : '';
}

function mdo_review_counts_parse_010279( string $body, string $source ): int {
	$text = mdo_review_counts_plain_text_010279( $body );
	$patterns = 'google' === $source
		? array(
			'/[\"\']reviewCount[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
			'/([0-9][0-9.,]*)\s*(?:total|reviews?)\b/i',
			'/trusted\s+by\s+over\s+([0-9][0-9.,]*)/i',
		)
		: array(
			'/El Mercado de Origen\s+Opiniones\s+([0-9][0-9.,]*)/iu',
			'/([0-9][0-9.,]*)\s+opiniones\b/iu',
			'/[\"\']reviewCount[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
			'/[\"\']numberOfReviews[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
			'/([0-9][0-9.,]*)\s+reviews?\b/i',
		);

	foreach ( $patterns as $pattern ) {
		foreach ( array( $body, $text ) as $haystack ) {
			if ( preg_match( $pattern, $haystack, $match ) ) {
				$count = mdo_review_counts_normalize_number_010279( (string) $match[1] );
				if ( $count > 0 && $count < 1000000 ) {
					return $count;
				}
			}
		}
	}
	return 0;
}

function mdo_review_counts_fetch_google_010279() {
	$response = mdo_review_counts_response_010279( 'https://www.trustindex.io/reviews/www.elmercadodeorigen.com' );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = (string) wp_remote_retrieve_body( $response );
	if ( $status < 200 || $status >= 400 || strlen( $body ) < 500 ) {
		return new WP_Error( 'mdo_google_http', 'Google/Trustindex HTTP ' . $status );
	}
	$count = mdo_review_counts_parse_010279( $body, 'google' );
	return $count > 0 ? $count : new WP_Error( 'mdo_google_parse', 'No se pudo extraer el recuento de Google.' );
}

/**
 * Trustpilot entrega actualmente la ficha completa con estado HTTP 403 a
 * determinados clientes. Sólo aceptamos ese cuerpo si identifica de forma
 * inequívoca nuestra ficha; una página de challenge nunca puede actualizar
 * la opción almacenada.
 */
function mdo_review_counts_fetch_trustpilot_010279() {
	$response = mdo_review_counts_response_010279( 'https://es.trustpilot.com/review/elmercadodeorigen.com', true );
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = (string) wp_remote_retrieve_body( $response );
	if ( strlen( $body ) < 1000 ) {
		return new WP_Error( 'mdo_trustpilot_empty', 'Trustpilot HTTP ' . $status . ': respuesta sin ficha utilizable.' );
	}

	$text = mdo_review_counts_plain_text_010279( $body );
	$valid_profile = false !== stripos( $text, 'El Mercado de Origen' )
		&& ( false !== stripos( $text, 'elmercadodeorigen.com' ) || false !== stripos( $body, '61faf114a16cf7ffb4c3ddfa' ) );
	if ( ! $valid_profile ) {
		return new WP_Error( 'mdo_trustpilot_invalid', 'Trustpilot HTTP ' . $status . ': la respuesta no corresponde a la ficha.' );
	}

	$count = mdo_review_counts_parse_010279( $body, 'trustpilot' );
	return $count > 0 ? $count : new WP_Error( 'mdo_trustpilot_parse', 'No se pudo extraer el recuento de Trustpilot (HTTP ' . $status . ').' );
}

function mdo_review_counts_purge_home_cache_010279(): void {
	if ( function_exists( 'elmercado_flush_home_cache' ) ) {
		elmercado_flush_home_cache();
	}
	$front_id = (int) get_option( 'page_on_front', 0 );
	if ( $front_id > 0 ) {
		clean_post_cache( $front_id );
	}
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
}

/** Cada fuente se actualiza de forma independiente; un error nunca guarda 0. */
function mdo_review_counts_refresh_010279( bool $force = false ): array {
	mdo_review_counts_seed_options_010279();
	$before_google = (int) get_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303 );
	$before_trust  = (int) get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169 );
	$result = array(
		'google'     => $before_google,
		'trustpilot' => $before_trust,
		'google_ok'  => false,
		'trust_ok'   => false,
		'changed'    => false,
		'at'         => time(),
	);

	if ( $force || ! mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_010279 ) ) {
		$google = mdo_review_counts_fetch_google_010279();
		if ( is_wp_error( $google ) ) {
			$result['google_error'] = $google->get_error_message();
		} else {
			$result['google']    = (int) $google;
			$result['google_ok'] = true;
			update_option( MDO_REVIEW_GOOGLE_OPTION_010279, (int) $google, false );
			update_option( MDO_REVIEW_GOOGLE_UPDATED_010279, time(), false );
		}
	} else {
		$result['google_ok'] = true;
	}

	if ( $force || ! mdo_review_counts_updated_today_010279( MDO_REVIEW_TRUST_UPDATED_010279 ) ) {
		$trust = mdo_review_counts_fetch_trustpilot_010279();
		if ( is_wp_error( $trust ) ) {
			$result['trust_error'] = $trust->get_error_message();
		} else {
			$result['trustpilot'] = (int) $trust;
			$result['trust_ok']   = true;
			update_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, (int) $trust, false );
			update_option( MDO_REVIEW_TRUST_UPDATED_010279, time(), false );
		}
	} else {
		$result['trust_ok'] = true;
	}

	$result['changed'] = $before_google !== (int) $result['google'] || $before_trust !== (int) $result['trustpilot'];
	update_option( MDO_REVIEW_LAST_RESULT_010279, wp_json_encode( $result ), false );
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
