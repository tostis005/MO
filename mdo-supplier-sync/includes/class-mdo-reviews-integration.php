<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Integración operativa de las reseñas de tienda.
 *
 * - Sincroniza a diario únicamente las fuentes externas (Google + Trustpilot).
 * - Se ejecuta a las 02:30 en la zona horaria de WordPress, antes del dispatcher
 *   nocturno de proveedores de EMDO (03:00).
 * - Restaura la pestaña pública "Reseñas" de WCFM aunque la preferencia nativa
 *   de reseñas de proveedor esté desactivada.
 */
final class MDO_Reviews_Integration {
	private const CRON_HOOK = 'mdo_reviews_daily_import';
	private const GROUP = 'mdo-supplier-sync';
	private const SCHEDULE_VERSION = '2.0.0';

	public static function init(): void {
		// MDO_Reviews 1.0.31 programaba este hook a +1h e importaba también
		// Woo/WCFM. Sustituimos solo la planificación diaria, no el importador base.
		remove_action( self::CRON_HOOK, array( 'MDO_Reviews', 'import_all' ) );
		remove_action( 'init', array( 'MDO_Reviews', 'ensure_schedule' ), 30 );
		add_action( self::CRON_HOOK, array( __CLASS__, 'import_external' ) );

		add_filter( 'wcfmmp_store_tabs', array( __CLASS__, 'store_tabs' ), 999, 2 );

		if ( did_action( 'action_scheduler_init' ) ) {
			self::ensure_schedule();
		} elseif ( function_exists( 'as_schedule_recurring_action' ) ) {
			add_action( 'action_scheduler_init', array( __CLASS__, 'ensure_schedule' ), 25 );
		} else {
			add_action( 'init', array( __CLASS__, 'ensure_schedule' ), 90 );
		}
	}

	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CRON_HOOK, array(), self::GROUP );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Ejecuta exclusivamente la ingesta externa. Los métodos de normalización y
	 * atribución siguen centralizados en MDO_Reviews para no duplicar reglas.
	 */
	public static function import_external(): array {
		$context = self::invoke_reviews_private( 'assignment_context' );
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$stats = array(
			'google' => self::invoke_reviews_private(
				'import_cached_source',
				array(
					'google',
					array( 'trustindex-google-review-content', 'trustindex-google-reviews', 'google_reviews' ),
					$context,
				)
			),
			'trustpilot' => self::invoke_reviews_private(
				'import_cached_source',
				array(
					'trustpilot',
					array( 'trustindex-trustpilot-review-content', 'trustindex-trustpilot-reviews', 'trustpilot_reviews' ),
					$context,
				)
			),
		);

		foreach ( array( 'google', 'trustpilot' ) as $source ) {
			if ( ! is_array( $stats[ $source ] ) ) {
				$stats[ $source ] = array( 'found' => 0, 'saved' => 0 );
			}
		}

		$payload = array(
			'at' => time(),
			'stats' => $stats,
			'mode' => 'external_only',
		);
		update_option( 'mdo_reviews_last_external_import', $payload, false );
		update_option( 'mdo_reviews_last_import', $payload, false );

		return $stats;
	}

	public static function ensure_schedule(): void {
		$version = (string) get_option( 'mdo_reviews_external_schedule_version', '' );
		$existing = self::next_scheduled_timestamp();
		$wrong_slot = $existing > 0 && '02:30' !== wp_date( 'H:i', $existing );
		$needs_reset = self::SCHEDULE_VERSION !== $version || $wrong_slot;

		if ( $needs_reset ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::CRON_HOOK, array(), self::GROUP );
			}
			wp_clear_scheduled_hook( self::CRON_HOOK );
			$existing = 0;
		}

		$next = self::next_two_thirty_timestamp();
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( $existing <= 0 && ! as_has_scheduled_action( self::CRON_HOOK, array(), self::GROUP ) ) {
				as_schedule_recurring_action( $next, DAY_IN_SECONDS, self::CRON_HOOK, array(), self::GROUP );
			}
		} elseif ( $existing <= 0 && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( $next, 'daily', self::CRON_HOOK );
		}

		if ( $needs_reset || self::SCHEDULE_VERSION !== $version ) {
			update_option( 'mdo_reviews_external_schedule_version', self::SCHEDULE_VERSION, false );
		}
	}

	/**
	 * WCFM crea el endpoint de reviews incluso si la preferencia visual está
	 * desactivada. Reponemos la pestaña al final del filtro y la colocamos justo
	 * después de "Acerca de" cuando esa pestaña existe.
	 */
	public static function store_tabs( array $tabs, $store_id ): array {
		$label = __( 'Reseñas', 'mdo-supplier-sync' );
		$result = array();
		$inserted = false;

		foreach ( $tabs as $key => $value ) {
			if ( 'reviews' === $key ) {
				continue;
			}
			$result[ $key ] = $value;
			if ( 'about' === $key ) {
				$result['reviews'] = $label;
				$inserted = true;
			}
		}

		if ( ! $inserted ) {
			$result['reviews'] = $label;
		}

		return $result;
	}

	private static function next_scheduled_timestamp(): int {
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			$next = as_next_scheduled_action( self::CRON_HOOK, array(), self::GROUP );
			if ( is_numeric( $next ) && (int) $next > 0 ) {
				return (int) $next;
			}
		}
		$next = wp_next_scheduled( self::CRON_HOOK );
		return $next ? (int) $next : 0;
	}

	private static function next_two_thirty_timestamp(): int {
		$now = current_datetime();
		$next = $now->setTime( 2, 30, 0 );
		if ( $next <= $now ) {
			$next = $next->modify( '+1 day' );
		}
		return $next->getTimestamp();
	}

	/**
	 * MDO_Reviews mantiene estos helpers privados para proteger su API pública.
	 * Esta integración pertenece al mismo plugin y los reutiliza de forma
	 * controlada para mantener una única lógica de parsing/atribución.
	 */
	private static function invoke_reviews_private( string $method, array $args = array() ) {
		try {
			$reflection = new ReflectionMethod( 'MDO_Reviews', $method );
			if ( method_exists( $reflection, 'setAccessible' ) ) {
				$reflection->setAccessible( true );
			}
			return $reflection->invokeArgs( null, $args );
		} catch ( Throwable $error ) {
			error_log( '[EMDO reviews] Error en importación externa: ' . $method . ' - ' . $error->getMessage() );
			return null;
		}
	}
}
