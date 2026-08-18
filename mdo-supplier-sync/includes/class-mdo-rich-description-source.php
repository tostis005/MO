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
 * antes de que el conector lo procese.
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

		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		$host = preg_replace( '/^www\./i', '', $host );

		$xpath = new DOMXPath( $dom );
		$rich  = self::find_rich_description( $xpath, $host );
		$rich  = self::normalize_rich_markup( $rich, $host );
		if ( '' === $rich || ! preg_match( '/<(?:strong|b|em|i|ul|ol|li|br|p|h[2-6]|table)\b/i', $rich ) ) {
			return $response;
		}

		/*
		 * Estos dos proveedores son conectores explícitamente soportados por EMDO.
		 * Su JSON-LD aplana el contenido visible y, en Puente Robles, además omite
		 * parte de los bloques auxiliares. La descripción visible es la fuente de
		 * verdad y no debe descartarse por una comparación de texto plano.
		 */
		$force_visible = in_array( $host, array( 'elcatedratico.com', 'puenterobles.com' ), true );

		$changed = false;
		$scripts = $xpath->query( "//script[contains(translate(@type,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz'),'ld+json')]" );
		foreach ( $scripts ?: array() as $script ) {
			$data = json_decode( trim( (string) $script->textContent ), true );
			if ( ! is_array( $data ) ) {
				continue;
			}
			if ( self::inject_description( $data, $rich, $force_visible ) ) {
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

	private static function find_rich_description( DOMXPath $xpath, string $host ): string {
		if ( 'elcatedratico.com' === $host ) {
			$nodes = $xpath->query( "//*[@id='descripcion'][1]" );
			if ( $nodes && $nodes->length ) {
				return wp_kses_post( trim( self::inner_html( $nodes->item( 0 ) ) ) );
			}
		}

		if ( 'puenterobles.com' === $host ) {
			/*
			 * Puente Robles divide la ficha entre una descripcion principal y los
			 * bloques de ingredientes, conservacion, envio, peso y recomendaciones.
			 * Todos cuelgan de .adicional; seleccionar solo .descripcion pierde los
			 * ultimos bloques y deja el JSON-LD plano como ganador.
			 */
			$nodes = $xpath->query( "//*[contains(concat(' ', normalize-space(@class), ' '), ' adicional ')][1]" );
			if ( $nodes && $nodes->length ) {
				return wp_kses_post( trim( self::inner_html( $nodes->item( 0 ) ) ) );
			}
		}

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
				$format_count = preg_match_all( '/<(?:strong|b|em|i|ul|ol|li|br|p|h[2-6]|table)\b/i', $html, $matches );
				$score = (int) $format_count * 1000 + mb_strlen( $text );
				if ( $score > $best_score ) {
					$best = wp_kses_post( trim( $html ) );
					$best_score = $score;
				}
			}
		}
		return $best;
	}

	/**
	 * Convierte estilos que solo existen en la web del proveedor en semantica
	 * portable para WooCommerce y corrige fronteras inline sin espacio.
	 */
	private static function normalize_rich_markup( string $html, string $host ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}

		if ( 'puenterobles.com' === $host ) {
			$fragment = new DOMDocument();
			$previous = libxml_use_internal_errors( true );
			$loaded = $fragment->loadHTML( '<?xml encoding="utf-8" ?><div id="mdo-rich-root">' . $html . '</div>', LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
			libxml_clear_errors();
			libxml_use_internal_errors( $previous );
			if ( $loaded ) {
				$fragment_xpath = new DOMXPath( $fragment );
				$titles = array();
				foreach ( $fragment_xpath->query( "//*[@id='mdo-rich-root']//*[contains(concat(' ', normalize-space(@class), ' '), ' titulo ')]" ) ?: array() as $title ) {
					$titles[] = $title;
				}
				foreach ( $titles as $title ) {
					$text = trim( preg_replace( '/\s+/u', ' ', (string) $title->textContent ) );
					if ( '' === $text ) {
						$title->parentNode->removeChild( $title );
						continue;
					}
					if ( in_array( mb_strtolower( $text, 'UTF-8' ), array( 'descripción', 'descripcion' ), true ) ) {
						$title->parentNode->removeChild( $title );
						continue;
					}
					$paragraph = $fragment->createElement( 'p' );
					$strong = $fragment->createElement( 'strong' );
					$strong->appendChild( $fragment->createTextNode( $text ) );
					$paragraph->appendChild( $strong );
					$title->parentNode->replaceChild( $paragraph, $title );
				}

				$root = $fragment_xpath->query( "//*[@id='mdo-rich-root']" );
				if ( $root && $root->length ) {
					$html = self::inner_html( $root->item( 0 ) );
				}
			}
		}

		/*
		 * El Catedratico publica, por ejemplo, <strong>Peso</strong>Ofrecemos...
		 * La negrita marca la frontera semantica, pero sin un espacio el contenido
		 * se ve pegado al reutilizarlo fuera de su CSS original.
		 */
		$html = preg_replace( '~</(strong|b)>(?=[\p{L}\p{N}])~u', '</$1> ', $html );

		return wp_kses_post( trim( $html ) );
	}

	private static function inner_html( DOMNode $node ): string {
		$html = '';
		foreach ( $node->childNodes as $child ) {
			$html .= $node->ownerDocument->saveHTML( $child );
		}
		return $html;
	}

	private static function inject_description( array &$data, string $rich, bool $force = false ): bool {
		$changed = false;
		$type = $data['@type'] ?? null;
		$types = is_array( $type ) ? $type : array( $type );
		if ( in_array( 'Product', $types, true ) ) {
			$current = isset( $data['description'] ) ? trim( wp_strip_all_tags( (string) $data['description'] ) ) : '';
			$visible = trim( self::comparison_text( $rich ) );
			if ( '' !== $visible && ( $force || '' === $current || self::same_description_text( $current, $visible ) ) ) {
				$data['description'] = $rich;
				$changed = true;
			}
		}
		foreach ( $data as &$value ) {
			if ( is_array( $value ) && self::inject_description( $value, $rich, $force ) ) {
				$changed = true;
			}
		}
		unset( $value );
		return $changed;
	}

	private static function comparison_text( string $value ): string {
		$value = preg_replace( '~</(?:strong|b|em|i|span|p|div|li|h[1-6])>|<br\s*/?>~iu', ' ', $value );
		$value = wp_strip_all_tags( $value );
		$value = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = str_replace( array( "\xC2\xA0", "\u{00A0}" ), ' ', $value );
		return trim( preg_replace( '/\s+/u', ' ', $value ) );
	}

	private static function same_description_text( string $a, string $b ): bool {
		$normalize = static function ( string $value ): string {
			$value = self::comparison_text( $value );
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
