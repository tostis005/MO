<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conserva el HTML semántico seguro de la descripción original.
 *
 * Algunos proveedores publican una descripción rica en el HTML visible pero una
 * versión de texto plano en JSON-LD. Los conectores priorizan JSON-LD, por lo que
 * aquí sustituimos únicamente description del Product JSON-LD por una versión
 * semántica y limpia del contenido visible antes de que el conector lo procese.
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
				$encoded = wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
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
				return trim( self::inner_html( $nodes->item( 0 ) ) );
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
				return trim( self::inner_html( $nodes->item( 0 ) ) );
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
	 * Los proveedores contienen HTML de presentación dependiente de su CSS y, en
	 * algunos productos, etiquetas inline mal balanceadas. Reconstruimos bloques
	 * semánticos desde el DOM ya interpretado para almacenar HTML portable y válido.
	 */
	private static function normalize_rich_markup( string $html, string $host ): string {
		if ( '' === trim( $html ) ) {
			return '';
		}
		if ( 'elcatedratico.com' === $host ) {
			return self::rebuild_catedratico( $html );
		}
		if ( 'puenterobles.com' === $host ) {
			return self::rebuild_puente_robles( $html );
		}
		$html = preg_replace( '~</(strong|b)>(?=[\p{L}\p{N}])~u', '</$1> ', $html );
		return wp_kses_post( trim( $html ) );
	}

	private static function rebuild_catedratico( string $html ): string {
		$loaded = self::load_fragment( $html );
		if ( ! $loaded ) {
			return wp_kses_post( trim( $html ) );
		}
		list( $dom, $xpath ) = $loaded;
		$nodes = $xpath->query( "//*[@id='mdo-rich-root']//p | //*[@id='mdo-rich-root']//h2 | //*[@id='mdo-rich-root']//h3 | //*[@id='mdo-rich-root']//h4" );
		$labels = array( 'Conservación y caducidad', 'Recomendaciones', 'Envío', 'Peso', 'Certificación' );
		$out = array();
		foreach ( $nodes ?: array() as $node ) {
			if ( self::has_ancestor_named( $node, 'p', 'mdo-rich-root' ) ) {
				continue;
			}
			$text = self::node_text( $node );
			if ( '' === $text ) {
				continue;
			}
			$is_heading = in_array( strtolower( $node->nodeName ), array( 'h2', 'h3', 'h4' ), true );
			if ( $is_heading ) {
				$out[] = '<p><strong>' . esc_html( $text ) . '</strong></p>';
				continue;
			}
			$matched = false;
			foreach ( $labels as $label ) {
				if ( 0 === mb_stripos( $text, $label, 0, 'UTF-8' ) ) {
					$rest = trim( mb_substr( $text, mb_strlen( $label, 'UTF-8' ), null, 'UTF-8' ) );
					$rest = ltrim( $rest, ": \t\n\r\0\x0B" );
					$out[] = '<p><strong>' . esc_html( $label ) . '</strong>' . ( '' !== $rest ? ' ' . esc_html( $rest ) : '' ) . '</p>';
					$matched = true;
					break;
				}
			}
			if ( ! $matched ) {
				$out[] = '<p>' . esc_html( $text ) . '</p>';
			}
		}
		return wp_kses_post( trim( implode( "\n", $out ) ) );
	}

	private static function rebuild_puente_robles( string $html ): string {
		$loaded = self::load_fragment( $html );
		if ( ! $loaded ) {
			return wp_kses_post( trim( $html ) );
		}
		list( $dom, $xpath ) = $loaded;
		$query = "//*[@id='mdo-rich-root']//*[self::p or self::h2 or self::h3 or self::h4 or self::table or contains(concat(' ', normalize-space(@class), ' '), ' titulo ')]";
		$nodes = $xpath->query( $query );
		$out = array();
		foreach ( $nodes ?: array() as $node ) {
			$name = strtolower( $node->nodeName );
			if ( 'p' === $name && ( self::has_ancestor_named( $node, 'p', 'mdo-rich-root' ) || self::has_ancestor_named( $node, 'table', 'mdo-rich-root' ) ) ) {
				continue;
			}
			if ( 'table' === $name ) {
				$table = self::clean_table( $node );
				if ( '' !== $table ) {
					$out[] = $table;
				}
				continue;
			}

			$class = $node instanceof DOMElement ? (string) $node->getAttribute( 'class' ) : '';
			$is_title = preg_match( '/(?:^|\s)titulo(?:\s|$)/i', $class ) || in_array( $name, array( 'h2', 'h3', 'h4' ), true );
			if ( $is_title && self::has_heading_ancestor( $node, 'mdo-rich-root' ) ) {
				continue;
			}

			$text = self::node_text( $node );
			if ( '' === $text ) {
				continue;
			}
			if ( $is_title ) {
				$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text, 'UTF-8' ) : strtolower( $text );
				if ( in_array( $lower, array( 'descripción', 'descripcion' ), true ) ) {
					continue;
				}
				$out[] = '<p><strong>' . esc_html( $text ) . '</strong></p>';
			} else {
				$out[] = '<p>' . esc_html( $text ) . '</p>';
			}
		}
		return wp_kses_post( trim( implode( "\n", $out ) ) );
	}

	private static function load_fragment( string $html ): ?array {
		$dom = new DOMDocument();
		$previous = libxml_use_internal_errors( true );
		$loaded = $dom->loadHTML( '<?xml encoding="utf-8" ?><div id="mdo-rich-root">' . $html . '</div>', LIBXML_NOWARNING | LIBXML_NOERROR | LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		if ( ! $loaded ) {
			return null;
		}
		return array( $dom, new DOMXPath( $dom ) );
	}

	private static function node_text( DOMNode $node ): string {
		$value = html_entity_decode( (string) $node->textContent, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$value = str_replace( array( "\xC2\xA0", "\u{00A0}" ), ' ', $value );
		return trim( preg_replace( '/\s+/u', ' ', $value ) );
	}

	private static function has_ancestor_named( DOMNode $node, string $name, string $stop_id ): bool {
		$parent = $node->parentNode;
		while ( $parent instanceof DOMElement ) {
			if ( $stop_id === $parent->getAttribute( 'id' ) ) {
				return false;
			}
			if ( strtolower( $parent->nodeName ) === strtolower( $name ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	private static function has_heading_ancestor( DOMNode $node, string $stop_id ): bool {
		$parent = $node->parentNode;
		while ( $parent instanceof DOMElement ) {
			if ( $stop_id === $parent->getAttribute( 'id' ) ) {
				return false;
			}
			if ( in_array( strtolower( $parent->nodeName ), array( 'p', 'h2', 'h3', 'h4' ), true ) ) {
				return true;
			}
			$parent = $parent->parentNode;
		}
		return false;
	}

	private static function clean_table( DOMNode $table ): string {
		$rows = array();
		$xpath = new DOMXPath( $table->ownerDocument );
		foreach ( $xpath->query( './/tr', $table ) ?: array() as $row ) {
			$cells = array();
			foreach ( $xpath->query( './th|./td', $row ) ?: array() as $cell ) {
				$tag = 'th' === strtolower( $cell->nodeName ) ? 'th' : 'td';
				$text = self::node_text( $cell );
				$cells[] = '<' . $tag . '>' . esc_html( $text ) . '</' . $tag . '>';
			}
			if ( $cells ) {
				$rows[] = '<tr>' . implode( '', $cells ) . '</tr>';
			}
		}
		return $rows ? '<table><tbody>' . implode( '', $rows ) . '</tbody></table>' : '';
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
		$max = max( strlen( $left ), strlen( $right ) );
		if ( 0 === $max ) {
			return false;
		}
		$distance = levenshtein( substr( $left, 0, 2500 ), substr( $right, 0, 2500 ) );
		return ( $distance / max( 1, min( 2500, $max ) ) ) <= 0.08;
	}
}
