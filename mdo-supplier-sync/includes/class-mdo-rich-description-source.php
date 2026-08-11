<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conserva el HTML semántico seguro de la descripción original.
 *
 * Algunos proveedores publican una descripción rica en el HTML visible pero una
 * versión de texto plano en JSON-LD. Los conectores priorizan JSON-LD, por lo que
 * aquí sustituimos únicamente description del Product JSON-LD por el HTML visible
 * equivalente antes de que el conector lo procese.
 */
final class MDO_Rich_Description_Source {
	public static function init(): void {
		add_filter( 'http_response', array( __CLASS__, 'preserve' ), 20, 3 );
	}

	public static function preserve( $response, array $args, string $url ) {
		if ( is_wp_error( $response ) || empty( $response['body'] ) ) {
			return $response;
		}

		$user_agent = (string) ( $args['user-agent'] ?? '' );
		if ( false === stripos( $user_agent, 'EMDO Catalog Sync/' ) ) {
			return $response;
		}

		$body = (string) $response['body'];
		if ( false === stripos( $body, 'ld+json' ) || false === stripos( $body, 'description' ) ) {
			return $response;
		}

		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $body, LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		if ( ! $loaded ) {
			return $response;
		}

		$xpath = new DOMXPath( $dom );
		$rich  = self::find_rich_description( $xpath );
		if ( '' === $rich || ! preg_match( '/<(?:strong|b|em|i|ul|ol|li|br|p|h[2-6])\b/i', $rich ) ) {
			return $response;
		}

		$changed = false;
		$scripts = $xpath->query( "//script[contains(translate(@type,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'ld+json')]" );
		foreach ( $scripts ?: array() as $script ) {
			$data = json_decode( trim( (string) $script->textContent ), true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			if ( self::inject_description( $data, $rich ) ) {
				$encoded = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
				if ( is_string( $encoded ) && '' !== $encoded ) {
					while ( $script->firstChild ) {
						$script->removeChild( $script->firstChild );
					}
					$script->appendChild( $dom->createTextNode( $encoded ) );
					$changed = true;
				}
			}
		}

		if ( ! $changed ) {
			return $response;
		}

		$serialized = $dom->saveHTML();
		if ( is_string( $serialized ) && '' !== $serialized ) {
			$response['body'] = $serialized;
		}
		return $response;
	}

	private static function find_rich_description( DOMXPath $xpath ): string {
		$queries = array(
			"//*[@id='tab-description']",
			"//*[@id='descripcion']",
			"//*[@id='description']",
			"//*[contains(concat(' ', normalize-space(@class), ' '), ' woocommerce-product-details__short-description ')]",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'product-description')]",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'descripcion')]",
			"//*[contains(translate(@class,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'description')]",
		);

		$best = '';
		$best_score = -1;
		foreach ( $queries as $query ) {
			$nodes = $xpath->query( $query );
			foreach ( $nodes ?: array() as $node ) {
				$html = self::inner_html( $node );
				$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $html ) ) );
				if ( mb_strlen( $text ) < 30 ) {
					continue;
				}
				$format_count = preg_match_all( '/<(?:strong|b|em|i|ul|ol|li|br|p|h[2-6])\b/i', $html, $matches );
				$score = (int) $format_count * 1000 + mb_strlen( $text );
				if ( $score > $best_score ) {
					$best = wp_kses_post( trim( $html ) );
					$best_score = $score;
				}
			}
		}
		return $best;
	}

	private static function inner_html( DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return $html;
	}

	private static function inject_description( array &$data, string $rich ): bool {
		$changed = false;
		$type = $data['@type'] ?? null;
		$types = is_array( $type ) ? $type : array( $type );
		if ( in_array( 'Product', $types, true ) ) {
			$current = isset( $data['description'] ) ? trim( wp_strip_all_tags( (string) $data['description'] ) ) : '';
			$visible = trim( wp_strip_all_tags( $rich ) );
			if ( '' !== $visible && ( '' === $current || self::same_description_text( $current, $visible ) ) ) {
				$data['description'] = $rich;
				$changed = true;
			}
		}
		foreach ( $data as &$value ) {
			if ( is_array( $value ) && self::inject_description( $value, $rich ) ) {
				$changed = true;
			}
		}
		unset( $value );
		return $changed;
	}

	private static function same_description_text( string $a, string $b ): bool {
		$normalize = static function ( string $value ): string {
			$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$value = str_replace( array( "\xC2\xA0", "\u{00A0}" ), ' ', $value );
			$value = trim( preg_replace( '/\s+/u', ' ', $value ) );
			return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
		};
		$left  = $normalize( $a );
		$right = $normalize( $b );
		if ( $left === $right ) {
			return true;
		}
		// Aceptamos pequeñas diferencias de espacios/puntuación entre JSON-LD y HTML.
		$max = max( strlen( $left ), strlen( $right ) );
		if ( 0 === $max ) {
			return false;
		}
		$distance = levenshtein( substr( $left, 0, 2500 ), substr( $right, 0, 2500 ) );
		return ( $distance / max( 1, min( 2500, $max ) ) ) <= 0.08;
	}
}
