<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Puente de importación para reseñas externas obtenidas por navegador.
 *
 * El navegador se ejecuta fuera de WordPress (GitHub Actions) y entrega un
 * payload JSON. La normalización, atribución, deduplicación y protección de
 * decisiones manuales siguen pasando por MDO_Reviews::upsert_review().
 */
final class MDO_Reviews_Scraping {
	private const LEGACY_CRON_HOOK = 'mdo_reviews_daily_import';
	private const GROUP = 'mdo-supplier-sync';
	private const DISABLE_VERSION = '1.0.35';
	private const GOOGLE_PROFILE_URL = 'https://www.google.com/maps/place/?q=place_id:ChIJbbIJi58nQg0RgJroXR8DG_U&hl=es';
	private const TRUSTPILOT_PROFILE_URL = 'https://es.trustpilot.com/review/elmercadodeorigen.com';
	private const MAX_PAYLOAD_BYTES = 12582912;
	private const MAX_REVIEWS_PER_IMPORT = 2500;

	public static function init(): void {
		// MDO_Reviews_Integration sigue aportando la pestaña WCFM, pero ya no
		// debe ejecutar los transportes API oficiales ni su cron diario.
		remove_action( self::LEGACY_CRON_HOOK, array( 'MDO_Reviews_Integration', 'import_external' ) );
		remove_action( 'admin_post_mdo_reviews_import', array( 'MDO_Reviews_Integration', 'handle_import' ) );
		remove_action( 'action_scheduler_init', array( 'MDO_Reviews_Integration', 'ensure_schedule' ), 25 );
		remove_action( 'init', array( 'MDO_Reviews_Integration', 'ensure_schedule' ), 90 );

		add_action( 'admin_post_mdo_reviews_import', array( __CLASS__, 'handle_import' ) );
		add_action( 'init', array( __CLASS__, 'disable_legacy_schedule' ), 95 );
	}

	public static function deactivate(): void {
		self::unschedule_legacy_import();
	}

	/**
	 * El botón del administrador sigue reimportando fuentes locales.
	 * Google y Trustpilot se sincronizan por navegador en el workflow dedicado.
	 */
	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'mdo-supplier-sync' ) );
		}
		check_admin_referer( 'mdo_reviews_import' );

		$stats = array(
			'wcfm' => self::invoke_reviews_private( 'import_wcfm_reviews' ),
			'woocommerce' => self::invoke_reviews_private( 'import_woocommerce_reviews' ),
		);
		$found = 0;
		$saved = 0;
		foreach ( $stats as $source_stats ) {
			if ( ! is_array( $source_stats ) ) {
				continue;
			}
			$found += (int) ( $source_stats['found'] ?? 0 );
			$saved += (int) ( $source_stats['saved'] ?? 0 );
		}

		update_option(
			'mdo_reviews_last_import',
			array(
				'at' => time(),
				'stats' => $stats,
				'mode' => 'local_sources',
			),
			false
		);

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'mdo-reviews',
					'mdo_notice' => 'imported',
					'found' => $found,
					'saved' => $saved,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function disable_legacy_schedule(): void {
		if ( self::DISABLE_VERSION === (string) get_option( 'mdo_reviews_scraping_schedule_version', '' ) ) {
			return;
		}
		self::unschedule_legacy_import();
		update_option( 'mdo_reviews_scraping_schedule_version', self::DISABLE_VERSION, false );
	}

	private static function unschedule_legacy_import(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::LEGACY_CRON_HOOK, array(), self::GROUP );
		}
		wp_clear_scheduled_hook( self::LEGACY_CRON_HOOK );
	}

	/**
	 * Importa un JSON generado por el scraper del workflow.
	 *
	 * @return array|WP_Error
	 */
	public static function import_file( string $path ) {
		$path = trim( $path );
		if ( '' === $path || ! is_readable( $path ) || ! is_file( $path ) ) {
			return new WP_Error( 'mdo_scrape_payload_missing', 'No se encuentra el payload de reseñas.' );
		}
		$size = filesize( $path );
		if ( false === $size || $size <= 0 || $size > self::MAX_PAYLOAD_BYTES ) {
			return new WP_Error( 'mdo_scrape_payload_size', 'El payload de reseñas tiene un tamaño no permitido.' );
		}
		$json = file_get_contents( $path );
		if ( false === $json ) {
			return new WP_Error( 'mdo_scrape_payload_read', 'No se ha podido leer el payload de reseñas.' );
		}
		$payload = json_decode( $json, true );
		if ( ! is_array( $payload ) || JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'mdo_scrape_payload_json', 'El payload de reseñas no contiene JSON válido.' );
		}
		return self::import_payload( $payload );
	}

	/**
	 * @return array|WP_Error
	 */
	public static function import_payload( array $payload ) {
		$source = sanitize_key( (string) ( $payload['source'] ?? '' ) );
		if ( ! in_array( $source, array( 'google', 'trustpilot' ), true ) ) {
			return new WP_Error( 'mdo_scrape_source', 'Fuente de reseñas no permitida.' );
		}
		$mode = sanitize_key( (string) ( $payload['mode'] ?? 'incremental' ) );
		if ( ! in_array( $mode, array( 'full', 'incremental' ), true ) ) {
			$mode = 'incremental';
		}
		$reviews = isset( $payload['reviews'] ) && is_array( $payload['reviews'] ) ? $payload['reviews'] : array();
		$reviews = array_slice( $reviews, 0, self::MAX_REVIEWS_PER_IMPORT );
		$context = self::invoke_reviews_private( 'assignment_context' );
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$before = self::source_count( $source );
		$seen = array();
		$found = 0;
		$saved = 0;
		$skipped = 0;

		foreach ( $reviews as $review ) {
			if ( ! is_array( $review ) ) {
				++$skipped;
				continue;
			}
			$data = self::normalize_review( $source, $review );
			if ( ! is_array( $data ) ) {
				++$skipped;
				continue;
			}
			$dedupe = (string) $data['source_review_id'];
			if ( isset( $seen[ $dedupe ] ) ) {
				continue;
			}
			$seen[ $dedupe ] = true;
			++$found;

			$assigned = self::invoke_reviews_private( 'apply_assignment', array( $data, $context ) );
			if ( ! is_array( $assigned ) ) {
				$assigned = $data;
			}
			if ( empty( $assigned['status'] ) ) {
				$assigned['status'] = 'pending';
			}
			if ( self::invoke_reviews_private( 'upsert_review', array( $source, $assigned ) ) ) {
				++$saved;
			}
		}

		$after = self::source_count( $source );
		$reported_count = absint( $payload['reported_count'] ?? 0 );
		$rating = isset( $payload['rating'] ) ? (float) $payload['rating'] : 0.0;
		self::persist_source_metadata( $source, $reported_count, $rating );

		$result = array(
			'found' => $found,
			'saved' => $saved,
			'inserted' => max( 0, $after - $before ),
			'skipped' => $skipped,
			'total' => $after,
			'expected' => $reported_count,
			'complete' => 'full' !== $mode || 0 === $reported_count ? null : $found >= $reported_count,
			'provider' => 'playwright_scraper',
			'mode' => $mode,
			'scraped_at' => sanitize_text_field( (string) ( $payload['scraped_at'] ?? '' ) ),
		);
		self::persist_last_import( $source, $result );
		return $result;
	}

	private static function normalize_review( string $source, array $review ): ?array {
		$rating = (int) round( (float) ( $review['rating'] ?? 0 ) );
		if ( $rating < 1 || $rating > 5 ) {
			return null;
		}
		$author = sanitize_text_field( (string) ( $review['author_name'] ?? '' ) );
		$text = trim( wp_strip_all_tags( (string) ( $review['text'] ?? $review['review_text'] ?? '' ) ) );
		$title = sanitize_text_field( (string) ( $review['title'] ?? $review['review_title'] ?? '' ) );
		$id = sanitize_text_field( (string) ( $review['id'] ?? $review['source_review_id'] ?? '' ) );
		$date = self::normalize_source_date( $review['date'] ?? $review['review_date'] ?? '' );
		if ( '' === $id ) {
			$id = 'fallback-' . hash( 'sha256', implode( '|', array( $source, $author, $rating, $date ?: '', $title, $text ) ) );
		}
		if ( '' === $author && '' === $text && '' === $title ) {
			return null;
		}

		$profile_url = 'google' === $source ? self::GOOGLE_PROFILE_URL : self::TRUSTPILOT_PROFILE_URL;
		$source_url = esc_url_raw( (string) ( $review['source_url'] ?? $profile_url ) );
		$data = array(
			'source_review_id' => $id,
			'author_name' => $author,
			'author_avatar_url' => esc_url_raw( (string) ( $review['author_avatar_url'] ?? '' ) ),
			'rating' => $rating,
			'review_title' => $title,
			'review_text' => $text,
			'review_date' => $date,
			'source_url' => $source_url ?: $profile_url,
			'status' => 'pending',
			'source_payload' => wp_json_encode( $review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		);
		if ( $date ) {
			$timestamp = strtotime( $date . ' UTC' );
			if ( $timestamp ) {
				$data['review_date_gmt'] = gmdate( 'Y-m-d H:i:s', $timestamp );
			}
		}
		return $data;
	}

	private static function normalize_source_date( $value ): ?string {
		if ( null === $value || '' === trim( (string) $value ) ) {
			return null;
		}
		if ( is_numeric( $value ) ) {
			$timestamp = (int) $value;
			if ( $timestamp > 20000000000 ) {
				$timestamp = (int) floor( $timestamp / 1000 );
			}
		} else {
			$timestamp = strtotime( (string) $value );
		}
		return $timestamp ? wp_date( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private static function source_count( string $source ): int {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE source=%s", $source ) );
	}

	private static function persist_source_metadata( string $source, int $count, float $rating ): void {
		if ( $count > 0 ) {
			update_option( 'mdo_' . $source . '_review_count', $count, false );
			update_option( 'mdo_' . $source . '_review_count_updated_at', time(), false );
		}
		if ( $rating > 0 && $rating <= 5 ) {
			update_option( 'mdo_' . $source . '_rating', round( $rating, 1 ), false );
		}
		update_option( 'mdo_' . $source . '_review_source', $source . '_playwright_scraper', false );
		update_option(
			'mdo_' . $source . '_review_source_url',
			'google' === $source ? self::GOOGLE_PROFILE_URL : self::TRUSTPILOT_PROFILE_URL,
			false
		);
	}

	private static function persist_last_import( string $source, array $result ): void {
		$last = get_option( 'mdo_reviews_last_external_import', array() );
		$stats = is_array( $last ) && isset( $last['stats'] ) && is_array( $last['stats'] ) ? $last['stats'] : array();
		$stats[ $source ] = $result;
		$payload = array(
			'at' => time(),
			'stats' => $stats,
			'mode' => 'playwright_scraper',
		);
		update_option( 'mdo_reviews_last_external_import', $payload, false );
		update_option( 'mdo_reviews_last_import', $payload, false );
	}

	private static function invoke_reviews_private( string $method, array $args = array() ) {
		try {
			$reflection = new ReflectionMethod( 'MDO_Reviews', $method );
			if ( method_exists( $reflection, 'setAccessible' ) ) {
				$reflection->setAccessible( true );
			}
			return $reflection->invokeArgs( null, $args );
		} catch ( Throwable $error ) {
			error_log( '[EMDO reviews scraping] ' . $method . ': ' . $error->getMessage() );
			return null;
		}
	}
}
