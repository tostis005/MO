<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Postprocesado de reseñas públicas procedentes de los hilos oficiales de
 * Foro Coches: asignación por hilo y deduplicación contra reseñas EMDO que se
 * hubieran incorporado manualmente con anterioridad.
 */
final class MDO_Reviews_Forocoches {
	private const THREAD_IBERICOS = '9308187';
	private const THREAD_ACEITE = '8107492';

	public static function after_import(): array {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE source='forocoches' ORDER BY id ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$stats = array( 'assigned' => 0, 'merged' => 0, 'kept' => 0, 'multi_store' => 0 );
		foreach ( (array) $rows as $row ) {
			$payload = json_decode( (string) $row->source_payload, true );
			$thread_id = sanitize_text_field( (string) ( $payload['thread_id'] ?? '' ) );
			$vendor_ids = self::vendors_for_review( $thread_id, (string) $row->review_text );
			$duplicate = self::find_internal_duplicate( $row );
			if ( $duplicate ) {
				if ( self::merge_into_internal( $row, $duplicate, $vendor_ids ) ) {
					++$stats['merged'];
				}
				continue;
			}

			$is_manual = in_array( (string) $row->validation_method, array( 'manual', 'ai_suggestion' ), true );
			if ( $vendor_ids && ! $is_manual && 'validated' !== (string) $row->status ) {
				if ( ! MDO_Reviews_Vendors::replace( (int) $row->id, $vendor_ids ) ) {
					continue;
				}
				$primary = (int) $vendor_ids[0];
				$reason = 1 < count( $vendor_ids )
					? 'Comentario positivo de Foro Coches que menciona productos de varias tiendas; se sugieren todas las tiendas detectadas y se mantiene en borrador.'
					: ( self::THREAD_ACEITE === $thread_id
						? 'Comentario positivo extraído del hilo oficial de aceite de El Mercado de Origen en Foro Coches.'
						: 'Comentario positivo extraído del hilo oficial de ibéricos de El Mercado de Origen en Foro Coches.' );
				$wpdb->update(
					$table,
					array(
						'suggested_vendor_user_id' => $primary,
						'assignment_type' => 1 < count( $vendor_ids ) ? 'forocoches_multi' : 'forocoches_thread',
						'assignment_confidence' => 0.99,
						'assignment_reason' => $reason,
						'status' => 'pending',
						'validation_method' => 'forocoches_suggestion',
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => (int) $row->id )
				);
				++$stats['assigned'];
				if ( 1 < count( $vendor_ids ) ) {
					++$stats['multi_store'];
				}
			}
			++$stats['kept'];
		}
		update_option( 'mdo_forocoches_reconcile_last', array( 'at' => time(), 'stats' => $stats ), false );
		return $stats;
	}

	private static function find_internal_duplicate( $forum_row ) {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$text = self::normalize_text( (string) $forum_row->review_text );
		if ( '' === $text || empty( $forum_row->review_date ) ) {
			return null;
		}
		$date = substr( (string) $forum_row->review_date, 0, 10 );
		$candidates = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id<>%d AND source IN ('woocommerce_product','wcfm','external') AND status<>'rejected' AND DATE(review_date) BETWEEN DATE_SUB(%s, INTERVAL 1 DAY) AND DATE_ADD(%s, INTERVAL 1 DAY)",
				(int) $forum_row->id,
				$date,
				$date
			)
		);
		$matches = array();
		foreach ( (array) $candidates as $candidate ) {
			if ( $text === self::normalize_text( (string) $candidate->review_text ) ) {
				$matches[] = $candidate;
			}
		}
		return 1 === count( $matches ) ? $matches[0] : null;
	}

	private static function merge_into_internal( $forum_row, $internal_row, array $thread_vendor_ids ): bool {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$forum_vendor_ids = MDO_Reviews_Vendors::get( (int) $forum_row->id, $forum_row );
		$internal_vendor_ids = MDO_Reviews_Vendors::get( (int) $internal_row->id, $internal_row );
		$vendor_ids = array_values( array_unique( array_filter( array_merge( $internal_vendor_ids, $forum_vendor_ids, $thread_vendor_ids ) ) ) );

		$wpdb->query( 'START TRANSACTION' );
		try {
			$forum_key = (string) $forum_row->source_key;
			if ( false === $wpdb->delete( MDO_Reviews_Vendors::table(), array( 'review_id' => (int) $forum_row->id ), array( '%d' ) ) ) {
				throw new RuntimeException( 'No se pudo retirar la asignación duplicada de Foro Coches.' );
			}
			if ( false === $wpdb->delete( $table, array( 'id' => (int) $forum_row->id ), array( '%d' ) ) ) {
				throw new RuntimeException( 'No se pudo retirar la fila duplicada de Foro Coches.' );
			}
			$update = array(
				'source' => 'forocoches',
				'source_review_id' => (string) $forum_row->source_review_id,
				'source_key' => $forum_key,
				'source_url' => (string) $forum_row->source_url,
				'source_payload' => (string) $forum_row->source_payload,
				'author_avatar_url' => $forum_row->author_avatar_url ?: $internal_row->author_avatar_url,
				'last_seen_at' => current_time( 'mysql' ),
				'updated_at' => current_time( 'mysql' ),
			);
			if ( (int) $internal_row->rating < 1 || (int) $internal_row->rating > 5 ) {
				$update['rating'] = (int) $forum_row->rating;
			}
			if ( false === $wpdb->update( $table, $update, array( 'id' => (int) $internal_row->id ) ) ) {
				throw new RuntimeException( 'No se pudo convertir la reseña EMDO en su origen Foro Coches.' );
			}
			$wpdb->query( 'COMMIT' );
			MDO_Reviews_Vendors::replace( (int) $internal_row->id, $vendor_ids );
			return true;
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[EMDO Foro Coches] ' . $error->getMessage() );
			return false;
		}
	}

	private static function vendors_for_review( string $thread_id, string $text ): array {
		$ids = array();
		$primary = self::vendor_for_thread( $thread_id );
		if ( $primary > 0 ) {
			$ids[] = $primary;
		}
		$normalized = ' ' . self::normalize_text( $text ) . ' ';
		$mentions_oil = (bool) preg_match( '/\b(?:aceite|aceites|aove|arbequina|lechin|picual|martena|almazara)\b/u', $normalized );
		$mentions_ham = (bool) preg_match( '/\b(?:jamon|jamones|paleta|paletas|paletilla|paletillas|iberic[oa]s?|bellota|lomo|chorizo|salchichon|embutido)\b/u', $normalized );
		if ( $mentions_oil ) {
			$id = self::vendor_by_name( '1957' );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		if ( $mentions_ham ) {
			$id = self::vendor_by_name( 'hidalgo de la jara' );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}
		return array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
	}

	private static function vendor_for_thread( string $thread_id ): int {
		if ( self::THREAD_ACEITE === $thread_id ) {
			return self::vendor_by_name( '1957' );
		}
		if ( self::THREAD_IBERICOS === $thread_id ) {
			return self::vendor_by_name( 'hidalgo de la jara' );
		}
		return 0;
	}

	private static function vendor_by_name( string $needle ): int {
		$needle = self::normalize_text( $needle );
		foreach ( get_users( array( 'role' => 'wcfm_vendor', 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$names = array( (string) $user->display_name );
			if ( function_exists( 'wcfmmp_get_store' ) ) {
				$store = wcfmmp_get_store( (int) $user->ID );
				if ( $store && method_exists( $store, 'get_shop_name' ) ) {
					$names[] = (string) $store->get_shop_name();
				}
			}
			foreach ( $names as $name ) {
				if ( false !== strpos( self::normalize_text( $name ), $needle ) ) {
					return (int) $user->ID;
				}
			}
		}
		return 0;
	}

	private static function normalize_text( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( $text );
		$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
		$text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
		return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	}
}
