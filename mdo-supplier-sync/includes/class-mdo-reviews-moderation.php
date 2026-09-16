<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prepara una tanda de moderación de reseñas sin publicarlas.
 *
 * - Retira las 10 filas legacy del antiguo import de Trustindex que quedaron
 *   duplicadas frente al catálogo Google canónico actual.
 * - Sugiere tienda para borradores inequívocos según el contenido de la reseña.
 * - Nunca publica: vendor_user_id permanece vacío y status permanece pending.
 */
final class MDO_Reviews_Moderation {
	private const VERSION = '1.0.0';
	private const OPTION = 'mdo_reviews_moderation_version';
	private const HIDALGO_VENDOR_ID = 6;
	private const OIL_VENDOR_ID = 3;

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'run_once' ), 36 );
	}

	public static function run_once(): void {
		if ( self::VERSION === (string) get_option( self::OPTION, '' ) ) {
			return;
		}
		if ( ! class_exists( 'MDO_Database' ) ) {
			return;
		}

		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) !== $table ) {
			return;
		}

		$wpdb->query( 'START TRANSACTION' );
		try {
			$deleted = self::remove_legacy_trustindex_duplicates( $table );
			$suggested = self::suggest_unassigned_reviews( $table );
			update_option(
				self::OPTION,
				self::VERSION,
				false
			);
			update_option(
				'mdo_reviews_moderation_last_run',
				array(
					'at'        => time(),
					'deleted'   => $deleted,
					'suggested' => $suggested,
				),
				false
			);
			$wpdb->query( 'COMMIT' );
		} catch ( Throwable $error ) {
			$wpdb->query( 'ROLLBACK' );
			error_log( '[EMDO reviews moderation] ' . $error->getMessage() );
		}
	}

	private static function remove_legacy_trustindex_duplicates( string $table ): int {
		global $wpdb;
		$legacy_ids = range( 242, 251 );
		$placeholders = implode( ',', array_fill( 0, count( $legacy_ids ), '%d' ) );
		$sql = "SELECT id,source,source_review_id FROM {$table} WHERE id IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...$legacy_ids ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$deleted = 0;
		foreach ( (array) $rows as $row ) {
			if ( 'google' !== (string) $row->source ) {
				continue;
			}
			if ( 0 === strpos( (string) $row->source_review_id, 'trustindex-' ) ) {
				continue;
			}
			$result = $wpdb->delete( $table, array( 'id' => (int) $row->id ), array( '%d' ) );
			if ( false === $result ) {
				throw new RuntimeException( 'No se pudo retirar la reseña legacy #' . (int) $row->id . '.' );
			}
			$deleted += (int) $result;
		}
		return $deleted;
	}

	private static function suggest_unassigned_reviews( string $table ): array {
		global $wpdb;
		$rows = $wpdb->get_results(
			"SELECT id,review_title,review_text FROM {$table} WHERE status='pending' AND COALESCE(vendor_user_id,0)=0 AND COALESCE(suggested_vendor_user_id,0)=0 ORDER BY id ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		);
		$stats = array( 'hidalgo' => 0, 'oil_1957' => 0, 'ambiguous' => 0 );
		foreach ( (array) $rows as $row ) {
			$classification = self::classify( (string) $row->review_title . ' ' . (string) $row->review_text );
			if ( empty( $classification['vendor_id'] ) ) {
				++$stats['ambiguous'];
				continue;
			}
			$vendor_id = (int) $classification['vendor_id'];
			$update = array(
				'suggested_vendor_user_id' => $vendor_id,
				'assignment_type'          => 'ai_suggestion',
				'assignment_confidence'    => (float) $classification['confidence'],
				'assignment_reason'        => (string) $classification['reason'],
				'validation_method'         => 'ai_suggestion',
				'updated_at'                => current_time( 'mysql' ),
			);
			$result = $wpdb->update( $table, $update, array( 'id' => (int) $row->id ) );
			if ( false === $result ) {
				throw new RuntimeException( 'No se pudo guardar la sugerencia IA para la reseña #' . (int) $row->id . '.' );
			}
			if ( self::HIDALGO_VENDOR_ID === $vendor_id ) {
				++$stats['hidalgo'];
			} else {
				++$stats['oil_1957'];
			}
		}
		return $stats;
	}

	private static function classify( string $text ): array {
		$text = remove_accents( wp_strip_all_tags( $text ) );
		$text = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
		$text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
		$text = ' ' . trim( preg_replace( '/\s+/', ' ', (string) $text ) ) . ' ';
		if ( '  ' === $text || '' === trim( $text ) ) {
			return array( 'vendor_id' => 0 );
		}

		$ham_weights = array(
			' jamon ' => 4, ' jamones ' => 4, ' paleta ' => 4, ' paletas ' => 4, ' paletilla ' => 4, ' paletillas ' => 4,
			' iberic' => 3, ' bellota ' => 3, ' brida ' => 3, ' pedroches ' => 3,
			' lomo ' => 2, ' lomito ' => 2, ' chorizo ' => 2, ' salchichon ' => 2, ' morcon ' => 2, ' sobrasada ' => 2, ' embutido' => 1,
		);
		$oil_weights = array(
			' aceite ' => 4, ' aceites ' => 4, ' aove ' => 4, ' oliva ' => 2,
			' arbequina ' => 3, ' martena ' => 3, ' lechin ' => 3, ' picual ' => 3, ' almazara ' => 3, ' oro liquido ' => 2,
		);
		$other_product_terms = array( ' naranja', ' queso', ' patata', ' tomate', ' pimiento', ' verdura', ' hortaliza', ' garbanzo', ' lenteja', ' ternera', ' vaca', ' burger', ' carne ' );

		$ham_score = self::score_terms( $text, $ham_weights );
		$oil_score = self::score_terms( $text, $oil_weights );
		$other = false;
		foreach ( $other_product_terms as $term ) {
			if ( false !== strpos( $text, $term ) ) {
				$other = true;
				break;
			}
		}

		if ( $ham_score > 0 && 0 === $oil_score && ! $other ) {
			return array(
				'vendor_id'  => self::HIDALGO_VENDOR_ID,
				'confidence' => 0.94,
				'reason'     => 'Sugerencia IA para revisión: la reseña menciona explícitamente jamón, paleta o productos ibéricos asociados a Hidalgo de la Jara. Se mantiene en borrador.',
			);
		}
		if ( $oil_score > 0 && 0 === $ham_score && ! $other ) {
			return array(
				'vendor_id'  => self::OIL_VENDOR_ID,
				'confidence' => 0.94,
				'reason'     => 'Sugerencia IA para revisión: la reseña menciona explícitamente aceite/AOVE o variedades de aceite asociadas a 1957. Se mantiene en borrador.',
			);
		}
		if ( ! $other && $ham_score >= $oil_score + 3 ) {
			return array(
				'vendor_id'  => self::HIDALGO_VENDOR_ID,
				'confidence' => 0.86,
				'reason'     => 'Sugerencia IA para revisión: reseña mixta, pero las referencias principales corresponden a jamón/ibéricos de Hidalgo de la Jara. Se mantiene en borrador.',
			);
		}
		if ( ! $other && $oil_score >= $ham_score + 3 ) {
			return array(
				'vendor_id'  => self::OIL_VENDOR_ID,
				'confidence' => 0.86,
				'reason'     => 'Sugerencia IA para revisión: reseña mixta, pero las referencias principales corresponden a aceite/AOVE de 1957. Se mantiene en borrador.',
			);
		}
		return array( 'vendor_id' => 0 );
	}

	private static function score_terms( string $text, array $weights ): int {
		$score = 0;
		foreach ( $weights as $term => $weight ) {
			if ( false !== strpos( $text, $term ) ) {
				$score += (int) $weight;
			}
		}
		return $score;
	}
}
