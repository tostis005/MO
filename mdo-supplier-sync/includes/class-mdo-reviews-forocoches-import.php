<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importador de payloads generados por el scraper público de Foro Coches.
 *
 * Mantiene las nuevas reseñas en borrador, conserva decisiones manuales y
 * ejecuta después la reconciliación contra reseñas EMDO ya existentes.
 */
final class MDO_Reviews_Forocoches_Import {
	private const MAX_PAYLOAD_BYTES = 20971520;
	private const MAX_REVIEWS = 5000;

	public static function import_file( string $path ) {
		$path = trim( $path );
		if ( '' === $path || ! is_readable( $path ) || ! is_file( $path ) ) {
			return new WP_Error( 'mdo_forocoches_payload_missing', 'No se encuentra el payload de Foro Coches.' );
		}
		$size = filesize( $path );
		if ( false === $size || $size <= 0 || $size > self::MAX_PAYLOAD_BYTES ) {
			return new WP_Error( 'mdo_forocoches_payload_size', 'El payload de Foro Coches tiene un tamaño no permitido.' );
		}
		$json = file_get_contents( $path );
		$payload = false === $json ? null : json_decode( $json, true );
		if ( ! is_array( $payload ) || JSON_ERROR_NONE !== json_last_error() || 'forocoches' !== sanitize_key( (string) ( $payload['source'] ?? '' ) ) ) {
			return new WP_Error( 'mdo_forocoches_payload_json', 'El payload de Foro Coches no es válido.' );
		}
		return self::import_payload( $payload );
	}

	public static function import_payload( array $payload ) {
		$mode = sanitize_key( (string) ( $payload['mode'] ?? 'incremental' ) );
		if ( ! in_array( $mode, array( 'full', 'incremental' ), true ) ) {
			$mode = 'incremental';
		}
		$reviews = isset( $payload['reviews'] ) && is_array( $payload['reviews'] ) ? array_slice( $payload['reviews'], 0, self::MAX_REVIEWS ) : array();
		$found = 0;
		$saved = 0;
		$skipped = 0;
		$seen = array();
		foreach ( $reviews as $review ) {
			if ( ! is_array( $review ) ) {
				++$skipped;
				continue;
			}
			$data = self::normalize( $review );
			if ( ! $data ) {
				++$skipped;
				continue;
			}
			$id = (string) $data['source_review_id'];
			if ( isset( $seen[ $id ] ) ) {
				continue;
			}
			$seen[ $id ] = true;
			++$found;

			self::preserve_existing_moderation( $data );
			if ( self::invoke_reviews_private( 'upsert_review', array( 'forocoches', $data ) ) ) {
				++$saved;
			}
		}

		$reconcile = MDO_Reviews_Forocoches::after_import();
		$result = array(
			'found' => $found,
			'saved' => $saved,
			'skipped' => $skipped,
			'total' => self::source_count(),
			'mode' => $mode,
			'provider' => sanitize_key( (string) ( $payload['provider'] ?? 'forocoches_public_playwright' ) ),
			'scraped_at' => sanitize_text_field( (string) ( $payload['scraped_at'] ?? '' ) ),
			'reconcile' => $reconcile,
		);
		update_option( 'mdo_forocoches_review_count', (int) $result['total'], false );
		update_option( 'mdo_forocoches_review_source', (string) $result['provider'], false );
		update_option( 'mdo_forocoches_review_count_updated_at', time(), false );
		update_option( 'mdo_forocoches_last_import', array( 'at' => time(), 'result' => $result ), false );
		return $result;
	}

	private static function normalize( array $review ): ?array {
		$id = sanitize_text_field( (string) ( $review['id'] ?? '' ) );
		$author = sanitize_text_field( (string) ( $review['author_name'] ?? '' ) );
		$text = trim( wp_strip_all_tags( (string) ( $review['text'] ?? '' ) ) );
		$rating = (int) round( (float) ( $review['rating'] ?? 0 ) );
		$source_url = esc_url_raw( (string) ( $review['source_url'] ?? '' ) );
		$date = self::normalize_date( $review['date'] ?? '' );
		if ( '' === $id || '' === $author || '' === $text || $rating < 1 || $rating > 5 || ! $date || ! $source_url ) {
			return null;
		}
		return array(
			'source_review_id' => $id,
			'author_name' => $author,
			'author_avatar_url' => '',
			'rating' => $rating,
			'review_title' => '',
			'review_text' => $text,
			'review_date' => $date,
			'review_date_gmt' => gmdate( 'Y-m-d H:i:s', strtotime( $date . ' UTC' ) ?: time() ),
			'source_url' => $source_url,
			'status' => 'pending',
			'validation_method' => null,
			'assignment_type' => null,
			'assignment_confidence' => 0,
			'assignment_reason' => null,
			'source_payload' => wp_json_encode( $review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
		);
	}

	private static function preserve_existing_moderation( array &$data ): void {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT status,validation_method FROM {$table} WHERE source='forocoches' AND source_review_id=%s LIMIT 1",
				(string) $data['source_review_id']
			)
		);
		if ( ! $existing ) {
			return;
		}
		if ( in_array( (string) $existing->status, array( 'validated', 'rejected' ), true ) || in_array( (string) $existing->validation_method, array( 'manual', 'ai_suggestion' ), true ) ) {
			$data['status'] = (string) $existing->status;
			$data['validation_method'] = (string) $existing->validation_method;
		}
	}

	private static function normalize_date( $value ): ?string {
		if ( '' === trim( (string) $value ) ) {
			return null;
		}
		$timestamp = strtotime( (string) $value );
		return $timestamp ? gmdate( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private static function source_count(): int {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE source='forocoches'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function invoke_reviews_private( string $method_name, array $args = array() ) {
		try {
			$method = new ReflectionMethod( 'MDO_Reviews', $method_name );
			if ( method_exists( $method, 'setAccessible' ) ) {
				$method->setAccessible( true );
			}
			return $method->invokeArgs( null, $args );
		} catch ( Throwable $error ) {
			error_log( '[EMDO Foro Coches import] ' . $error->getMessage() );
			return false;
		}
	}
}
