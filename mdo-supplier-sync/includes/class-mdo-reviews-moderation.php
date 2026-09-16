<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prepara una tanda de moderación de reseñas sin publicarlas.
 *
 * - Retira las 10 filas legacy del antiguo import de Trustindex que quedaron
 *   duplicadas frente al catálogo Google canónico actual.
 * - Sugiere tienda únicamente para borradores con una atribución semántica
 *   inequívoca según el contenido de la reseña.
 * - Nunca publica: vendor_user_id permanece vacío y status permanece pending.
 */
final class MDO_Reviews_Moderation {
	private const VERSION = '1.1.0';
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
			$reset = self::reset_previous_ai_suggestions( $table );
			$suggested = self::suggest_unassigned_reviews( $table );
			update_option( self::OPTION, self::VERSION, false );
			update_option(
				'mdo_reviews_moderation_last_run',
				array(
					'at'        => time(),
					'deleted'   => $deleted,
					'reset'     => $reset,
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

	private static function reset_previous_ai_suggestions( string $table ): int {
		global $wpdb;
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET suggested_vendor_user_id=NULL, assignment_type=NULL, assignment_confidence=0, assignment_reason=NULL, validation_method=NULL, updated_at=%s WHERE status='pending' AND COALESCE(vendor_user_id,0)=0 AND validation_method='ai_suggestion'",
				current_time( 'mysql' )
			)
		);
		if ( false === $updated ) {
			throw new RuntimeException( 'No se pudieron reiniciar las sugerencias IA anteriores.' );
		}
		return (int) $updated;
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
		$plain = remove_accents( wp_strip_all_tags( $text ) );
		$plain = function_exists( 'mb_strtolower' ) ? mb_strtolower( $plain, 'UTF-8' ) : strtolower( $plain );
		$text = preg_replace( '/[^a-z0-9]+/u', ' ', $plain );
		$text = ' ' . trim( preg_replace( '/\s+/', ' ', (string) $text ) ) . ' ';
		if ( '' === trim( $text ) ) {
			return array( 'vendor_id' => 0 );
		}

		$ham_weights = array(
			' jamon ' => 5, ' jamones ' => 5, ' paleta ' => 5, ' paletas ' => 5, ' paletilla ' => 5, ' paletillas ' => 5,
			' iberic' => 4, ' bellota ' => 4, ' brida ' => 4, ' pedroches ' => 4,
			' lomo ' => 3, ' lomito ' => 3, ' chorizo ' => 3, ' salchichon ' => 3, ' morcon ' => 3, ' sobrasada ' => 3, ' embutido' => 2,
			' lonchas de la maza ' => 3, ' sebo ' => 2,
		);
		$oil_weights = array(
			' aceite ' => 5, ' aceites ' => 5, ' aove ' => 5, ' oliva ' => 3,
			' arbequina ' => 4, ' martena ' => 4, ' lechin ' => 4, ' picual ' => 4, ' almazara ' => 4, ' oro liquido ' => 3,
			' sin filtrar ' => 2,
		);
		$other_product_terms = array( ' naranja', ' queso', ' patata', ' tomate', ' pimiento', ' verdura', ' hortaliza', ' garbanzo', ' lenteja', ' ternera', ' vaca', ' burger' );

		$ham_score = self::score_terms( $text, $ham_weights );
		$oil_score = self::score_terms( $text, $oil_weights );

		// Un aceite o un embutido citado únicamente como obsequio no identifica
		// la tienda de la compra principal. En esos casos retiramos esa pista.
		if ( $oil_score > 0 && self::is_incidental_gift_reference( $plain, 'oil' ) ) {
			$oil_score = 0;
		}
		if ( $ham_score > 0 && self::is_incidental_gift_reference( $plain, 'ham' ) ) {
			$ham_score = 0;
		}

		$other = false;
		foreach ( $other_product_terms as $term ) {
			if ( false !== strpos( $text, $term ) ) {
				$other = true;
				break;
			}
		}

		// Solo proponemos una tienda cuando hay una única familia comercial
		// inequívoca. Las reseñas mixtas se dejan expresamente sin asignar.
		if ( $ham_score > 0 && 0 === $oil_score && ! $other ) {
			$confidence = $ham_score <= 2 ? 0.86 : 0.94;
			return array(
				'vendor_id'  => self::HIDALGO_VENDOR_ID,
				'confidence' => $confidence,
				'reason'     => 'Sugerencia IA para revisión: la reseña se refiere de forma inequívoca a jamón, paleta o productos ibéricos asociados a Hidalgo de la Jara. Se mantiene en borrador.',
			);
		}
		if ( $oil_score > 0 && 0 === $ham_score && ! $other ) {
			$confidence = $oil_score <= 2 ? 0.88 : 0.94;
			return array(
				'vendor_id'  => self::OIL_VENDOR_ID,
				'confidence' => $confidence,
				'reason'     => 'Sugerencia IA para revisión: la reseña se refiere de forma inequívoca a aceite/AOVE o a una variedad de aceite asociada a 1957. Se mantiene en borrador.',
			);
		}
		return array( 'vendor_id' => 0 );
	}

	private static function is_incidental_gift_reference( string $text, string $family ): bool {
		if ( 'oil' === $family ) {
			$product = '(?:aceite|aove)';
		} else {
			$product = '(?:jamon|paleta|paletilla|embutido|salchichon|chorizo|lomo)';
		}
		$gift = '(?:de regalo|como regalo|obsequio|detalle|incluyer(?:on|a)|incluid[oa]s?)';
		return (bool) preg_match(
			'/(?:' . $gift . ').{0,55}' . $product . '|' . $product . '.{0,55}(?:' . $gift . ')/u',
			$text
		);
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
