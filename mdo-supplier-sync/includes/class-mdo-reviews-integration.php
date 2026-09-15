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
	private const SCHEDULE_VERSION = '2.1.0';

	public static function init(): void {
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
	 * Ejecuta exclusivamente la ingesta externa. Primero usa las fuentes reales
	 * que los plugins de Trustindex ya mantienen/parsean en WordPress y conserva
	 * el parser de opciones como fallback de compatibilidad.
	 */
	public static function import_external(): array {
		$context = self::invoke_reviews_private( 'assignment_context' );
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$google = self::import_google_table( $context );
		if ( empty( $google['found'] ) ) {
			$google = self::fallback_cached_source(
				'google',
				array( 'trustindex-google-review-content', 'trustindex-google-reviews', 'google_reviews' ),
				$context
			);
		}

		$trustpilot = self::import_trustpilot_plugin( $context );
		if ( empty( $trustpilot['found'] ) ) {
			$trustpilot = self::fallback_cached_source(
				'trustpilot',
				array( 'trustindex-trustpilot-review-content', 'trustindex-trustpilot-reviews', 'trustpilot_reviews' ),
				$context
			);
		}

		$stats = array(
			'google' => $google,
			'trustpilot' => $trustpilot,
		);

		$payload = array(
			'at' => time(),
			'stats' => $stats,
			'mode' => 'external_only',
		);
		update_option( 'mdo_reviews_last_external_import', $payload, false );
		update_option( 'mdo_reviews_last_import', $payload, false );

		return $stats;
	}

	/**
	 * Widgets for Google Reviews almacena las reseñas descargadas en una tabla
	 * propia. Leer esa tabla evita confundir la plantilla HTML de la opción
	 * trustindex-google-review-content con datos de reseñas.
	 */
	private static function import_google_table( array $context ): array {
		global $wpdb;
		$table = $wpdb->prefix . 'trustindex_google_reviews';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return array( 'found' => 0, 'saved' => 0, 'provider' => 'trustindex_table' );
		}

		$rows = $wpdb->get_results( "SELECT * FROM `{$table}` WHERE hidden = 0 ORDER BY date ASC, id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$saved = 0;
		foreach ( (array) $rows as $row ) {
			$data = array(
				'source_review_id' => (string) ( $row['reviewId'] ?? $row['id'] ?? '' ),
				'author_name' => (string) ( $row['user'] ?? '' ),
				'author_avatar_url' => (string) ( $row['user_photo'] ?? '' ),
				'rating' => max( 1, min( 5, (int) round( (float) ( $row['rating'] ?? 0 ) ) ) ),
				'review_text' => wp_strip_all_tags( (string) ( $row['text'] ?? '' ) ),
				'review_date' => self::normalize_source_date( $row['date'] ?? '' ),
				'source_url' => 'https://www.trustindex.io/reviews/www.elmercadodeorigen.com',
				'source_payload' => wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
			);
			if ( self::save_external_row( 'google', $data, $context ) ) {
				++$saved;
			}
		}

		return array( 'found' => count( (array) $rows ), 'saved' => $saved, 'provider' => 'trustindex_table' );
	}

	/**
	 * El plugin antiguo de Trustpilot no crea tabla: parsea su fuente mediante
	 * TrustindexPlugin::get_noreg_list_reviews(). Recuperamos la misma instancia
	 * registrada en los callbacks de WordPress para respetar toda su configuración.
	 */
	private static function import_trustpilot_plugin( array $context ): array {
		$plugin = self::find_trustpilot_plugin_instance();
		if ( ! $plugin || ! method_exists( $plugin, 'get_noreg_list_reviews' ) ) {
			return array( 'found' => 0, 'saved' => 0, 'provider' => 'trustindex_plugin' );
		}

		try {
			$raw = $plugin->get_noreg_list_reviews();
		} catch ( Throwable $error ) {
			error_log( '[EMDO reviews] Trustpilot plugin: ' . $error->getMessage() );
			return array( 'found' => 0, 'saved' => 0, 'provider' => 'trustindex_plugin', 'error' => 'provider_error' );
		}

		$candidates = array();
		self::collect_review_candidates( $raw, $candidates );
		$saved = 0;
		$seen = array();
		$source_url = (string) get_option( 'mdo_trustpilot_review_source_url', 'https://es.trustpilot.com/review/elmercadodeorigen.com' );

		foreach ( $candidates as $row ) {
			$data = self::normalize_trustpilot_row( $row, $source_url );
			if ( empty( $data['rating'] ) || ( '' === (string) $data['review_text'] && '' === (string) $data['author_name'] ) ) {
				continue;
			}
			$key = hash( 'sha256', strtolower( (string) $data['source_review_id'] ) . '|' . strtolower( (string) $data['author_name'] ) . '|' . (string) $data['review_date'] . '|' . (string) $data['rating'] . '|' . (string) $data['review_text'] );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			if ( self::save_external_row( 'trustpilot', $data, $context ) ) {
				++$saved;
			}
		}

		return array( 'found' => count( $seen ), 'saved' => $saved, 'provider' => 'trustindex_plugin' );
	}

	private static function find_trustpilot_plugin_instance() {
		global $wp_filter;
		foreach ( (array) $wp_filter as $hook ) {
			if ( ! is_object( $hook ) || ! isset( $hook->callbacks ) || ! is_array( $hook->callbacks ) ) {
				continue;
			}
			foreach ( $hook->callbacks as $callbacks ) {
				foreach ( (array) $callbacks as $callback ) {
					$function = $callback['function'] ?? null;
					if ( is_array( $function ) && isset( $function[0] ) && is_object( $function[0] ) && is_a( $function[0], 'TrustindexPlugin' ) ) {
						return $function[0];
					}
				}
			}
		}
		return null;
	}

	private static function collect_review_candidates( $node, array &$out ): void {
		if ( is_object( $node ) ) {
			$node = (array) $node;
		}
		if ( ! is_array( $node ) ) {
			return;
		}
		$keys = array_change_key_case( array_fill_keys( array_keys( $node ), true ), CASE_LOWER );
		$has_rating = isset( $keys['rating'] ) || isset( $keys['stars'] ) || isset( $keys['score'] ) || isset( $keys['review_rating'] );
		$has_content = isset( $keys['text'] ) || isset( $keys['review'] ) || isset( $keys['content'] ) || isset( $keys['user'] ) || isset( $keys['name'] ) || isset( $keys['reviewer'] );
		if ( $has_rating && $has_content ) {
			$out[] = $node;
		}
		foreach ( $node as $child ) {
			if ( is_array( $child ) || is_object( $child ) ) {
				self::collect_review_candidates( $child, $out );
			}
		}
	}

	private static function normalize_trustpilot_row( array $row, string $source_url ): array {
		$reviewer = $row['reviewer'] ?? array();
		if ( is_object( $reviewer ) ) {
			$reviewer = (array) $reviewer;
		}
		$reviewer = is_array( $reviewer ) ? $reviewer : array();

		$author = self::first_row_value( $row, array( 'user', 'author_name', 'reviewer_name', 'name' ) );
		if ( '' === $author ) {
			$author = (string) ( $reviewer['name'] ?? '' );
		}
		$avatar = self::first_row_value( $row, array( 'user_photo', 'avatar_url', 'photo' ) );
		if ( '' === $avatar ) {
			$avatar = (string) ( $reviewer['avatar_url'] ?? '' );
		}

		return array(
			'source_review_id' => self::first_row_value( $row, array( 'reviewId', 'review_id', 'id' ) ),
			'author_name' => $author,
			'author_avatar_url' => $avatar,
			'rating' => (int) round( (float) self::first_row_value( $row, array( 'rating', 'stars', 'score', 'review_rating' ) ) ),
			'review_title' => self::first_row_value( $row, array( 'title', 'headline', 'review_title' ) ),
			'review_text' => wp_strip_all_tags( self::first_row_value( $row, array( 'text', 'review', 'content', 'review_text' ) ) ),
			'review_date' => self::normalize_source_date( self::first_row_value( $row, array( 'date', 'created_at', 'published_at', 'time' ) ) ),
			'source_url' => $source_url,
			'source_payload' => wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		);
	}

	private static function first_row_value( array $row, array $keys ): string {
		foreach ( $keys as $key ) {
			if ( isset( $row[ $key ] ) && ! is_array( $row[ $key ] ) && ! is_object( $row[ $key ] ) && '' !== (string) $row[ $key ] ) {
				return (string) $row[ $key ];
			}
		}
		return '';
	}

	private static function normalize_source_date( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}
		if ( is_numeric( $value ) ) {
			$timestamp = (int) $value;
			if ( $timestamp > 20000000000 ) {
				$timestamp = (int) floor( $timestamp / 1000 );
			}
			return wp_date( 'Y-m-d H:i:s', $timestamp );
		}
		$timestamp = strtotime( (string) $value );
		return $timestamp ? wp_date( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private static function save_external_row( string $source, array $data, array $context ): bool {
		$assigned = self::invoke_reviews_private( 'apply_assignment', array( $data, $context ) );
		if ( ! is_array( $assigned ) ) {
			$assigned = $data;
		}
		if ( empty( $assigned['status'] ) ) {
			$assigned['status'] = 'pending';
		}
		return (bool) self::invoke_reviews_private( 'upsert_review', array( $source, $assigned ) );
	}

	private static function fallback_cached_source( string $source, array $options, array $context ): array {
		$result = self::invoke_reviews_private( 'import_cached_source', array( $source, $options, $context ) );
		return is_array( $result ) ? $result : array( 'found' => 0, 'saved' => 0, 'provider' => 'cached_option' );
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