<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conservative category inference for products imported by EMDO.
 *
 * It only assigns existing WooCommerce product categories and only when the
 * textual evidence is strong enough. Existing/manual category assignments are
 * never overwritten.
 */
final class MDO_Auto_Categorizer {
	private const MIN_SCORE  = 8.0;
	private const MIN_MARGIN = 3.0;

	/**
	 * Infer and, when safe, set category IDs on the in-memory Woo product.
	 * The result must be passed to record_result() after the product is saved.
	 */
	public static function maybe_assign( WC_Product $product, array $payload, array $source_row = array() ): array {
		$product_id = (int) $product->get_id();
		$current    = array_values( array_unique( array_filter( array_map( 'absint', (array) $product->get_category_ids() ) ) ) );
		$default_id = absint( get_option( 'default_product_cat', 0 ) );
		$meaningful = array_values( array_filter( $current, static fn( int $id ): bool => $id > 0 && $id !== $default_id ) );

		if ( $meaningful ) {
			return array(
				'assigned'     => false,
				'reason'       => 'existing_categories',
				'category_ids' => $meaningful,
				'score'        => null,
			);
		}

		if ( $product_id > 0 && get_post_meta( $product_id, '_emdo_auto_category_attempted', true ) ) {
			return array(
				'assigned'     => false,
				'reason'       => 'already_attempted',
				'category_ids' => array(),
				'score'        => null,
			);
		}

		$result = self::infer( $payload, $source_row );
		if ( ! empty( $result['category_ids'] ) ) {
			$product->set_category_ids( array_values( array_unique( array_map( 'absint', $result['category_ids'] ) ) ) );
			$result['assigned'] = true;
		} else {
			$result['assigned'] = false;
		}
		return $result;
	}

	/**
	 * Persist diagnostic metadata after WooCommerce has produced a product ID.
	 */
	public static function record_result( int $product_id, array $result ): void {
		if ( $product_id <= 0 || ! $result ) {
			return;
		}

		update_post_meta( $product_id, '_emdo_auto_category_attempted', '1' );
		update_post_meta( $product_id, '_emdo_auto_category_reason', sanitize_key( (string) ( $result['reason'] ?? '' ) ) );

		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $result['category_ids'] ?? array() ) ) ) ) );
		if ( $ids ) {
			update_post_meta( $product_id, '_emdo_auto_category_ids', implode( ',', $ids ) );
		} else {
			delete_post_meta( $product_id, '_emdo_auto_category_ids' );
		}

		if ( isset( $result['score'] ) && null !== $result['score'] ) {
			update_post_meta( $product_id, '_emdo_auto_category_score', number_format( (float) $result['score'], 2, '.', '' ) );
		} else {
			delete_post_meta( $product_id, '_emdo_auto_category_score' );
		}
	}

	private static function infer( array $payload, array $source_row ): array {
		$terms = get_terms(
			array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			)
		);
		if ( is_wp_error( $terms ) || ! $terms ) {
			return array( 'category_ids' => array(), 'score' => null, 'reason' => 'no_categories' );
		}

		$title       = self::normalize( (string) ( $payload['title'] ?? '' ) );
		$description = self::normalize( wp_strip_all_tags( (string) ( $payload['description'] ?? '' ) ) );
		$url         = self::normalize( rawurldecode( (string) ( $source_row['source_url'] ?? ( $payload['source_url'] ?? '' ) ) ) );
		$default_id  = absint( get_option( 'default_product_cat', 0 ) );
		$scores      = array();

		foreach ( $terms as $term ) {
			if ( ! $term instanceof WP_Term || (int) $term->term_id === $default_id || self::ignored_term( $term ) ) {
				continue;
			}

			$score = self::score_term( $term, $title, $description, $url );
			if ( $score > 0 ) {
				$scores[] = array(
					'term'  => $term,
					'score' => $score,
				);
			}
		}

		if ( ! $scores ) {
			return array( 'category_ids' => array(), 'score' => 0.0, 'reason' => 'no_match' );
		}

		usort(
			$scores,
			static function ( array $a, array $b ): int {
				if ( abs( (float) $a['score'] - (float) $b['score'] ) < 0.001 ) {
					// At equal evidence prefer the more specific child category.
					return (int) $b['term']->parent <=> (int) $a['term']->parent;
				}
				return (float) $b['score'] <=> (float) $a['score'];
			}
		);

		$best        = $scores[0];
		$best_score  = (float) $best['score'];
		$second      = $scores[1] ?? null;
		$second_score = $second ? (float) $second['score'] : 0.0;

		if ( $best_score < self::MIN_SCORE ) {
			return array( 'category_ids' => array(), 'score' => $best_score, 'reason' => 'low_confidence' );
		}

		if ( $second && $best_score < 14.0 && ( $best_score - $second_score ) < self::MIN_MARGIN ) {
			return array( 'category_ids' => array(), 'score' => $best_score, 'reason' => 'ambiguous' );
		}

		return array(
			'category_ids' => array( (int) $best['term']->term_id ),
			'score'        => $best_score,
			'reason'       => 'matched',
			'category'     => (string) $best['term']->name,
		);
	}

	private static function score_term( WP_Term $term, string $title, string $description, string $url ): float {
		$name   = self::normalize( (string) $term->name );
		$slug   = self::normalize( str_replace( '-', ' ', (string) $term->slug ) );
		$score  = 0.0;
		$direct = array_values( array_unique( array_filter( array( $name, $slug ) ) ) );

		foreach ( $direct as $phrase ) {
			if ( self::contains_phrase( $title, $phrase ) ) {
				$score += 12.0;
			}
			if ( self::contains_phrase( $url, $phrase ) ) {
				$score += 10.0;
			}
			if ( self::contains_phrase( $description, $phrase ) ) {
				$score += 5.0;
			}
		}

		foreach ( self::significant_tokens( $name . ' ' . $slug ) as $token ) {
			if ( self::contains_token( $title, $token ) ) {
				$score += 4.0;
			}
			if ( self::contains_token( $url, $token ) ) {
				$score += 3.0;
			}
			if ( self::contains_token( $description, $token ) ) {
				$score += 1.5;
			}
		}

		foreach ( self::keywords_for_term( $term ) as $keyword ) {
			$keyword = self::normalize( $keyword );
			if ( '' === $keyword ) {
				continue;
			}
			if ( self::contains_phrase( $title, $keyword ) ) {
				$score += 7.0;
			}
			if ( self::contains_phrase( $url, $keyword ) ) {
				$score += 5.0;
			}
			if ( self::contains_phrase( $description, $keyword ) ) {
				$score += 2.0;
			}
		}

		if ( (int) $term->parent > 0 && $score > 0 ) {
			$score += 0.5;
		}
		return $score;
	}

	private static function keywords_for_term( WP_Term $term ): array {
		$name = self::normalize( (string) $term->name . ' ' . (string) $term->slug );
		$sets = array();

		if ( str_contains( $name, 'ques' ) ) {
			$sets[] = array( 'queso', 'manchego', 'semicurado', 'curado', 'oveja', 'cabra' );
		}
		if ( str_contains( $name, 'conserv' ) ) {
			$sets[] = array( 'conserva', 'conservas', 'tarro', 'frasco', 'mermelada', 'pisto', 'tomate frito', 'sofrito', 'encurtido', 'escabeche' );
		}
		if ( str_contains( $name, 'legumbr' ) ) {
			$sets[] = array( 'garbanzo', 'garbanzos', 'lenteja', 'lentejas', 'alubia', 'alubias', 'judia seca', 'judias secas', 'fabes', 'frijol', 'frijoles' );
		}
		if ( str_contains( $name, 'hortal' ) || str_contains( $name, 'verdur' ) ) {
			$sets[] = array( 'tomate', 'pimiento', 'berenjena', 'calabacin', 'cebolla', 'ajo', 'patata', 'lechuga', 'acelga', 'espinaca', 'pepino', 'zanahoria', 'puerro', 'coliflor', 'brocoli', 'alcachofa', 'apio', 'nabo', 'remolacha', 'calabaza', 'judia verde' );
		}
		if ( str_contains( $name, 'naranja' ) || str_contains( $name, 'citric' ) ) {
			$sets[] = array( 'naranja', 'naranjas', 'mandarina', 'mandarinas', 'limon', 'limones', 'citricos' );
		}
		if ( str_contains( $name, 'fruta' ) ) {
			$sets[] = array( 'naranja', 'mandarina', 'limon', 'manzana', 'pera', 'melocoton', 'ciruela', 'uva', 'fresa', 'melon', 'sandia' );
		}
		if ( str_contains( $name, 'embut' ) || str_contains( $name, 'curad' ) ) {
			$sets[] = array( 'chorizo', 'salchichon', 'fuet', 'sobrasada', 'longaniza', 'cecina', 'lomo embuchado', 'cana de lomo', 'lomito' );
		}
		if ( str_contains( $name, 'jamon' ) ) {
			$sets[] = array( 'jamon', 'jamones', 'pata de jamon' );
		}
		if ( str_contains( $name, 'paleta' ) ) {
			$sets[] = array( 'paleta', 'paletas' );
		}
		if ( str_contains( $name, 'adob' ) ) {
			$sets[] = array( 'adobado', 'adobada', 'adobo', 'pincho moruno' );
		}
		if ( str_contains( $name, 'accesor' ) ) {
			$sets[] = array( 'jamonero', 'cuchillo jamonero', 'cuchillo', 'chaira', 'pinza', 'afilador', 'tabla jamonera', 'soporte jamon' );
		}
		if ( preg_match( '/\blomo\b/', $name ) ) {
			$sets[] = array( 'lomo', 'lomito', 'cana de lomo' );
		}
		if ( str_contains( $name, 'lote' ) || str_contains( $name, 'pack' ) ) {
			$sets[] = array( 'lote', 'pack', 'cesta', 'estuche', 'seleccion' );
		}

		$keywords = array();
		foreach ( $sets as $set ) {
			$keywords = array_merge( $keywords, $set );
		}
		return array_values( array_unique( $keywords ) );
	}

	private static function significant_tokens( string $text ): array {
		$generic = array(
			'producto', 'productos', 'tienda', 'catalogo', 'alimentacion', 'alimentacion',
			'otros', 'otras', 'oferta', 'ofertas', 'seleccion', 'especial', 'especiales',
			'para', 'con', 'sin', 'del', 'las', 'los', 'una', 'uno', 'por', 'y', 'de',
		);
		$tokens = preg_split( '/\s+/', self::normalize( $text ) ) ?: array();
		return array_values(
			array_unique(
				array_filter(
					$tokens,
					static fn( string $token ): bool => strlen( $token ) >= 4 && ! in_array( $token, $generic, true )
				)
			)
		);
	}

	private static function ignored_term( WP_Term $term ): bool {
		$name = self::normalize( (string) $term->name . ' ' . (string) $term->slug );
		return in_array( $name, array( 'sin categorizar uncategorized', 'uncategorized uncategorized', 'sin categorizar sin categorizar' ), true )
			|| preg_match( '/^(todos|todas|tienda|catalogo|productos?)\b/', $name );
	}

	private static function contains_phrase( string $haystack, string $needle ): bool {
		if ( '' === $haystack || '' === $needle ) {
			return false;
		}
		return str_contains( ' ' . $haystack . ' ', ' ' . $needle . ' ' );
	}

	private static function contains_token( string $haystack, string $token ): bool {
		return self::contains_phrase( $haystack, $token );
	}

	private static function normalize( string $text ): string {
		$text = html_entity_decode( wp_strip_all_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = remove_accents( $text );
		$text = strtolower( $text );
		$text = preg_replace( '/[^a-z0-9]+/u', ' ', $text );
		return trim( preg_replace( '/\s+/', ' ', (string) $text ) );
	}
}
