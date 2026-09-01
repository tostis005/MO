<?php
/**
 * Plugin Name: MDO - Dynamic review counts 0.10.281
 * Description: Mantiene en opciones de WordPress los recuentos de Google y Trustpilot, coordina su actualización tras el ciclo nocturno de proveedores y aporta un respaldo diario.
 * Version: 0.10.281
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const MDO_REVIEW_GOOGLE_OPTION_010279       = 'mdo_google_review_count';
const MDO_REVIEW_TRUSTPILOT_OPTION_010279   = 'mdo_trustpilot_review_count';
const MDO_REVIEW_GOOGLE_UPDATED_010279      = 'mdo_google_review_count_updated_at';
const MDO_REVIEW_TRUST_UPDATED_010279       = 'mdo_trustpilot_review_count_updated_at';
const MDO_REVIEW_LAST_RESULT_010279         = 'mdo_review_counts_last_result';
const MDO_REVIEW_CYCLE_READY_010281         = 'mdo_review_counts_supplier_cycle_ready_at';
const MDO_REVIEW_CYCLE_HOOK_010279          = 'mdo_review_counts_after_supplier_cycle_010279';
const MDO_REVIEW_FALLBACK_HOOK_010279       = 'mdo_review_counts_daily_fallback_010279';

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

/**
 * Los checkers propios se programan con WP-Cron para no depender del estado de
 * inicialización interna de Action Scheduler. El trabajo de proveedores sí se
 * consulta directamente en sus tablas para saber cuándo ha terminado.
 */
function mdo_review_counts_schedule_single_010279( int $timestamp ): void {
	if ( ! wp_next_scheduled( MDO_REVIEW_CYCLE_HOOK_010279 ) ) {
		wp_schedule_single_event( $timestamp, MDO_REVIEW_CYCLE_HOOK_010279 );
	}
}

/**
 * El dispatcher de proveedores arranca a las 03:00 y separa tiendas 30 min.
 * Registramos un comprobador tras el dispatcher; si siguen quedando trabajos,
 * se vuelve a comprobar 15 minutos después hasta que el ciclo quede vacío.
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

	update_option( MDO_REVIEW_CYCLE_READY_010281, time(), false );

	/* Google se puede leer directamente desde el servidor. Trustpilot tiene un
	 * refresco de navegador externo que usa la misma opción persistente. */
	if ( ! mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_010279 ) ) {
		mdo_review_counts_refresh_google_010281( false );
	}
}
add_action( MDO_REVIEW_CYCLE_HOOK_010279, 'mdo_review_counts_after_supplier_cycle_010279' );

/** Respaldo diario por si el ciclo nocturno no llega a disparar el checker. */
function mdo_review_counts_ensure_fallback_010279(): void {
	if ( ! wp_next_scheduled( MDO_REVIEW_FALLBACK_HOOK_010279 ) ) {
		wp_schedule_event( mdo_review_counts_next_local_time_010279( 9, 30 ), 'daily', MDO_REVIEW_FALLBACK_HOOK_010279 );
	}
}
add_action( 'init', 'mdo_review_counts_ensure_fallback_010279', 100 );

function mdo_review_counts_fallback_010279(): void {
	if ( mdo_review_counts_supplier_work_pending_010279() ) {
		mdo_review_counts_schedule_single_010279( time() + ( 15 * MINUTE_IN_SECONDS ) );
		return;
	}

	update_option( MDO_REVIEW_CYCLE_READY_010281, time(), false );
	if ( ! mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_010279 ) ) {
		mdo_review_counts_refresh_google_010281( false );
	}
}
add_action( MDO_REVIEW_FALLBACK_HOOK_010279, 'mdo_review_counts_fallback_010279' );

function mdo_review_counts_response_010279( string $url ) {
	return wp_remote_get(
		$url,
		array(
			'timeout'     => 30,
			'redirection' => 5,
			'user-agent'  => 'Mozilla/5.0 (compatible; ElMercadoDeOrigenReviewSync/1.0; +https://www.elmercadodeorigen.com/)',
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

function mdo_review_counts_parse_google_010281( string $body ): int {
	$patterns = array(
		'/[\"\']reviewCount[\"\']\s*:\s*[\"\']?([0-9][0-9.,]*)/i',
		'/([0-9][0-9.,]*)\s*(?:total|reviews?)\b/i',
		'/trusted\s+by\s+over\s+([0-9][0-9.,]*)/i',
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
	$response = mdo_review_counts_response_010279( 'https://www.trustindex.io/reviews/www.elmercadodeorigen.com' );
	if ( is_wp_error( $response ) ) {
		return $response;
	}
	$status = (int) wp_remote_retrieve_response_code( $response );
	$body   = (string) wp_remote_retrieve_body( $response );
	if ( $status < 200 || $status >= 400 || strlen( $body ) < 500 ) {
		return new WP_Error( 'mdo_google_http', 'Google/Trustindex HTTP ' . $status );
	}
	$count = mdo_review_counts_parse_google_010281( $body );
	return $count > 0 ? $count : new WP_Error( 'mdo_google_parse', 'No se pudo extraer el recuento de Google.' );
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

function mdo_review_counts_store_google_010281( int $count ): bool {
	if ( $count < 1 || $count >= 1000000 ) {
		return false;
	}
	$before = (int) get_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303 );
	update_option( MDO_REVIEW_GOOGLE_OPTION_010279, $count, false );
	update_option( MDO_REVIEW_GOOGLE_UPDATED_010279, time(), false );
	if ( $before !== $count ) {
		mdo_review_counts_purge_home_cache_010279();
	}
	return true;
}

function mdo_review_counts_store_trustpilot_010281( int $count ): bool {
	if ( $count < 1 || $count >= 1000000 ) {
		return false;
	}
	$before = (int) get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169 );
	update_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, $count, false );
	update_option( MDO_REVIEW_TRUST_UPDATED_010279, time(), false );
	update_option( MDO_REVIEW_LAST_RESULT_010279, wp_json_encode( array(
		'google'     => (int) get_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303 ),
		'trustpilot' => $count,
		'trust_ok'   => true,
		'at'         => time(),
	) ), false );
	if ( $before !== $count ) {
		mdo_review_counts_purge_home_cache_010279();
	}
	return true;
}

function mdo_review_counts_refresh_google_010281( bool $force = false ): array {
	mdo_review_counts_seed_options_010279();
	$current = (int) get_option( MDO_REVIEW_GOOGLE_OPTION_010279, 303 );
	$result  = array( 'google' => $current, 'google_ok' => false, 'changed' => false, 'at' => time() );

	if ( ! $force && mdo_review_counts_updated_today_010279( MDO_REVIEW_GOOGLE_UPDATED_010279 ) ) {
		$result['google_ok'] = true;
		return $result;
	}

	$fetched = mdo_review_counts_fetch_google_010279();
	if ( is_wp_error( $fetched ) ) {
		$result['google_error'] = $fetched->get_error_message();
		return $result;
	}

	$result['google']    = (int) $fetched;
	$result['google_ok'] = mdo_review_counts_store_google_010281( (int) $fetched );
	$result['changed']   = $current !== (int) $fetched;
	return $result;
}

/** Compatibilidad con verificadores/despliegues anteriores: ya no fuerza TP. */
function mdo_review_counts_refresh_010279( bool $force = false ): array {
	$google = mdo_review_counts_refresh_google_010281( $force );
	return array_merge(
		$google,
		array(
			'trustpilot' => (int) get_option( MDO_REVIEW_TRUSTPILOT_OPTION_010279, 169 ),
			'trust_ok'   => mdo_review_counts_updated_today_010279( MDO_REVIEW_TRUST_UPDATED_010279 ),
		)
	);
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
