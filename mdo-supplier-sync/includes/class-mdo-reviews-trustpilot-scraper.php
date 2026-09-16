<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scraper público de Trustpilot ejecutado desde producción.
 *
 * Prioriza el HTML público directo. Si Trustpilot bloquea la IP del servidor,
 * usa Jina Reader únicamente como transporte/renderizador gratuito del
 * contenido público. Toda la normalización, atribución, deduplicación y
 * protección de decisiones manuales continúa dentro de EMDO.
 */
final class MDO_Reviews_Trustpilot_Scraper {
	private const PROFILE_ES  = 'https://es.trustpilot.com/review/elmercadodeorigen.com';
	private const PROFILE_WWW = 'https://www.trustpilot.com/review/elmercadodeorigen.com';
	private const JINA_READER = 'https://r.jina.ai/';

	/** @return array|WP_Error */
	public static function sync( string $mode = 'incremental' ) {
		$payload = self::scrape( $mode );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}
		return MDO_Reviews_Scraping::import_payload( $payload );
	}

	/** @return array|WP_Error */
	public static function scrape( string $mode = 'incremental' ) {
		$mode = sanitize_key( $mode );
		if ( ! in_array( $mode, array( 'full', 'incremental' ), true ) ) {
			$mode = 'incremental';
		}

		$reviews        = array();
		$reported_count = 0;
		$rating         = 0.0;
		$total_pages    = 0;
		$profile_url    = self::PROFILE_ES;
		$transport      = '';
		$max_pages      = 'full' === $mode ? 12 : 2;

		for ( $page_number = 1; $page_number <= $max_pages; ++$page_number ) {
			$page = self::fetch_page( $page_number );
			if ( is_wp_error( $page ) ) {
				return $page;
			}

			$profile_url = (string) ( $page['profile_url'] ?? $profile_url );
			$transport   = (string) ( $page['transport'] ?? $transport );
			$page_props  = $page['data']['props']['pageProps'] ?? null;
			if ( ! is_array( $page_props ) ) {
				return new WP_Error( 'mdo_trustpilot_pageprops', 'Trustpilot no devolvió pageProps válidos.' );
			}

			if ( 1 === $page_number ) {
				$business_unit  = isset( $page_props['businessUnit'] ) && is_array( $page_props['businessUnit'] ) ? $page_props['businessUnit'] : array();
				$filters        = isset( $page_props['filters'] ) && is_array( $page_props['filters'] ) ? $page_props['filters'] : array();
				$pagination     = isset( $filters['pagination'] ) && is_array( $filters['pagination'] ) ? $filters['pagination'] : array();
				$reported_count = absint( $business_unit['numberOfReviews'] ?? $pagination['totalCount'] ?? 0 );
				$rating         = (float) ( $business_unit['trustScore'] ?? 0 );
				$total_pages    = absint( $pagination['totalPages'] ?? 0 );
			}

			$raw_reviews = isset( $page_props['reviews'] ) && is_array( $page_props['reviews'] ) ? $page_props['reviews'] : array();
			if ( empty( $raw_reviews ) ) {
				break;
			}

			$before = count( $reviews );
			foreach ( $raw_reviews as $raw_review ) {
				if ( ! is_array( $raw_review ) ) {
					continue;
				}
				$id            = sanitize_text_field( (string) ( $raw_review['id'] ?? '' ) );
				$review_rating = (int) round( (float) ( $raw_review['rating'] ?? 0 ) );
				if ( '' === $id || $review_rating < 1 || $review_rating > 5 ) {
					continue;
				}

				$consumer = isset( $raw_review['consumer'] ) && is_array( $raw_review['consumer'] ) ? $raw_review['consumer'] : array();
				$dates    = isset( $raw_review['dates'] ) && is_array( $raw_review['dates'] ) ? $raw_review['dates'] : array();
				$reviews[ $id ] = array(
					'id'                => $id,
					'author_name'       => (string) ( $consumer['displayName'] ?? '' ),
					'author_avatar_url' => (string) ( $consumer['imageUrl'] ?? '' ),
					'rating'            => $review_rating,
					'title'             => (string) ( $raw_review['title'] ?? '' ),
					'text'              => (string) ( $raw_review['text'] ?? '' ),
					'date'              => (string) ( $dates['publishedDate'] ?? $dates['experiencedDate'] ?? '' ),
					'source_url'        => 0 === strpos( $id, 'tp-' ) ? $profile_url : 'https://www.trustpilot.com/reviews/' . rawurlencode( $id ),
					'verified'          => ! empty( $raw_review['labels']['verification']['isVerified'] ),
					'transport'         => $transport,
				);
			}

			if ( 'full' === $mode && $reported_count > 0 && count( $reviews ) >= $reported_count ) {
				break;
			}
			if ( $total_pages > 0 && $page_number >= $total_pages ) {
				break;
			}
			if ( $page_number > 1 && count( $reviews ) === $before ) {
				break;
			}
			if ( 'incremental' === $mode && $page_number >= 2 ) {
				break;
			}
			usleep( 750000 );
		}

		$found = count( $reviews );
		if ( 0 === $found ) {
			return new WP_Error( 'mdo_trustpilot_empty', 'Trustpilot no devolvió reseñas públicas.' );
		}
		if ( 'full' === $mode ) {
			if ( $found < 100 ) {
				return new WP_Error( 'mdo_trustpilot_too_small', 'La carga completa de Trustpilot es demasiado pequeña: ' . $found . '.' );
			}
			if ( $reported_count > 0 && $found < (int) floor( $reported_count * 0.90 ) ) {
				return new WP_Error( 'mdo_trustpilot_incomplete', 'La carga completa de Trustpilot está incompleta: ' . $found . '/' . $reported_count . '.' );
			}
		}

		return array(
			'source'         => 'trustpilot',
			'provider'       => 'jina_reader' === $transport ? 'trustpilot_public_jina_reader' : 'trustpilot_public_http',
			'mode'           => $mode,
			'scraped_at'     => gmdate( 'c' ),
			'reported_count' => $reported_count,
			'rating'         => $rating,
			'profile_url'    => $profile_url,
			'sorted_newest'  => true,
			'reviews'        => array_values( $reviews ),
		);
	}

	/** @return array|WP_Error */
	private static function fetch_page( int $page_number ) {
		$last_error = null;
		foreach ( array( self::PROFILE_ES, self::PROFILE_WWW ) as $base_url ) {
			$url = 1 === $page_number ? $base_url : add_query_arg( array( 'page' => $page_number, 'sort' => 'recency' ), $base_url );
			$response = wp_remote_get(
				$url,
				array(
					'timeout'     => 35,
					'redirection' => 5,
					'user-agent'  => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/537.36',
					'headers'     => array(
						'Accept'                    => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
						'Accept-Language'           => 'es-ES,es;q=0.9,en;q=0.8',
						'Cache-Control'             => 'no-cache',
						'Pragma'                    => 'no-cache',
						'Referer'                   => 'https://www.google.com/',
						'Sec-Fetch-Dest'            => 'document',
						'Sec-Fetch-Mode'            => 'navigate',
						'Sec-Fetch-Site'            => 'cross-site',
						'Upgrade-Insecure-Requests' => '1',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				$last_error = $response;
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );
			if ( $code >= 200 && $code < 400 && '' !== $body ) {
				$data = self::extract_next_data( $body );
				if ( ! is_wp_error( $data ) ) {
					return array( 'profile_url' => $base_url, 'transport' => 'direct_http', 'data' => $data );
				}
				$dom_data = self::rendered_html_to_next_data( $body );
				if ( ! is_wp_error( $dom_data ) ) {
					return array( 'profile_url' => $base_url, 'transport' => 'direct_http', 'data' => $dom_data );
				}
				$last_error = $dom_data;
			} else {
				$last_error = new WP_Error( 'mdo_trustpilot_http', 'Trustpilot HTTP ' . $code . ' en página ' . $page_number . '.' );
			}
		}

		$jina = self::fetch_page_via_jina( $page_number );
		if ( ! is_wp_error( $jina ) ) {
			return $jina;
		}
		return $jina ?: ( $last_error instanceof WP_Error ? $last_error : new WP_Error( 'mdo_trustpilot_fetch', 'No se pudo descargar Trustpilot.' ) );
	}

	/** @return array|WP_Error */
	private static function fetch_page_via_jina( int $page_number ) {
		$target_url = 1 === $page_number ? self::PROFILE_ES : add_query_arg( array( 'page' => $page_number, 'sort' => 'recency' ), self::PROFILE_ES );
		$response = wp_remote_get(
			self::JINA_READER . $target_url,
			array(
				'timeout'     => 55,
				'redirection' => 3,
				'headers'     => array(
					'Accept'     => 'application/json',
					'X-No-Cache' => 'true',
					'X-Timeout'  => '35',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'mdo_trustpilot_jina_transport', $response->get_error_message() );
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$body = (string) wp_remote_retrieve_body( $response );
		if ( $code < 200 || $code >= 300 || '' === $body ) {
			return new WP_Error( 'mdo_trustpilot_jina_http', 'Jina Reader HTTP ' . $code . ' para Trustpilot página ' . $page_number . '.' );
		}

		$decoded = json_decode( $body, true );
		$content = is_array( $decoded ) ? (string) ( $decoded['data']['content'] ?? $decoded['content'] ?? '' ) : $body;
		if ( '' === trim( $content ) ) {
			return new WP_Error( 'mdo_trustpilot_jina_empty', 'Jina Reader no devolvió contenido para Trustpilot página ' . $page_number . '.' );
		}

		$data = self::extract_next_data( $content );
		if ( is_wp_error( $data ) ) {
			$data = self::rendered_html_to_next_data( $content );
		}
		if ( is_wp_error( $data ) ) {
			$data = self::reader_content_to_next_data( $content );
		}
		if ( is_wp_error( $data ) ) {
			return new WP_Error( 'mdo_trustpilot_jina_parse', 'Jina Reader no devolvió reseñas interpretables en Trustpilot página ' . $page_number . '.' );
		}

		return array( 'profile_url' => self::PROFILE_ES, 'transport' => 'jina_reader', 'data' => $data );
	}

	/** @return array|WP_Error */
	private static function reader_content_to_next_data( string $content ) {
		$content = html_entity_decode( str_replace( array( "\r\n", "\r" ), "\n", $content ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$marker  = self::last_case_insensitive_position( $content, 'Todas las opiniones' );
		if ( false !== $marker ) {
			$content = substr( $content, $marker + strlen( 'Todas las opiniones' ) );
		}

		$raw_lines = preg_split( '/\n/u', $content );
		$lines     = array();
		foreach ( $raw_lines as $line ) {
			$line = trim( (string) $line );
			$line = preg_replace( '/^#{1,6}\s*/u', '', $line );
			$line = preg_replace( '/^[*+-]\s+/u', '', $line );
			$lines[] = trim( (string) $line );
		}

		$reviews = array();
		$count   = count( $lines );
		for ( $i = 0; $i < $count; ++$i ) {
			if ( ! preg_match( '/(?:Valorada|Valorado|Rated)\s+con?\s*([1-5])\s+estrellas?|Rated\s+([1-5])\s+(?:out of|of)\s+5/iu', $lines[ $i ], $rating_match ) ) {
				continue;
			}
			$review_rating = (int) ( $rating_match[1] ?: ( $rating_match[2] ?? 0 ) );
			$date_index    = self::previous_non_empty_line( $lines, $i - 1 );
			if ( $date_index < 0 || ! self::is_short_review_date( $lines[ $date_index ] ) ) {
				continue;
			}
			$author_index = self::previous_non_empty_line( $lines, $date_index - 1 );
			if ( $author_index < 0 ) {
				continue;
			}

			$author = self::clean_author_line( $lines[ $author_index ] );
			if ( '' === $author || preg_match( '/^(Image|Imagen|Respuesta de|TrustScore)/iu', $author ) ) {
				continue;
			}
			$published_iso = self::spanish_date_to_iso( $lines[ $date_index ] );
			if ( '' === $published_iso ) {
				continue;
			}

			$j        = self::next_non_empty_line( $lines, $i + 1 );
			$verified = false;
			if ( $j >= 0 && preg_match( '/^(Verificada|Verificado|Verified)$/iu', self::plain_markdown_text( $lines[ $j ] ) ) ) {
				$verified = true;
				$j = self::next_non_empty_line( $lines, $j + 1 );
			}
			if ( $j < 0 ) {
				continue;
			}

			$title_raw = $lines[ $j ];
			$title     = self::plain_markdown_text( $title_raw );
			if ( '' === $title || preg_match( '/^(Opinión espontánea|Invitada|Respuesta de)/iu', $title ) ) {
				continue;
			}
			++$j;

			$body_lines       = array();
			$experienced_iso  = '';
			$block_end        = min( $count, $j + 40 );
			$review_link_blob = implode( "\n", array_slice( $lines, max( 0, $author_index - 1 ), min( 45, $count - max( 0, $author_index - 1 ) ) ) );
			for ( ; $j < $block_end; ++$j ) {
				$current = trim( $lines[ $j ] );
				if ( '' === $current ) {
					continue;
				}
				if ( self::is_long_review_date( $current ) ) {
					$experienced_iso = self::spanish_date_to_iso( $current );
					break;
				}
				if ( preg_match( '/^(Opinión espontánea|Invitada|Respuesta de El Mercado de Origen)$/iu', self::plain_markdown_text( $current ) ) ) {
					break;
				}
				if ( preg_match( '/(?:Valorada|Rated).*([1-5])/iu', $current ) ) {
					break;
				}
				$plain = self::plain_markdown_text( $current );
				if ( '' !== $plain && ! preg_match( '/^Ver \d+ reseñas? más de /iu', $plain ) ) {
					$body_lines[] = $plain;
				}
			}

			$text = trim( implode( "\n", $body_lines ) );
			if ( '' === $text && '' === $title ) {
				continue;
			}

			$id = '';
			if ( preg_match( '#/reviews/([A-Za-z0-9_-]{8,})#', $review_link_blob, $id_match ) ) {
				$id = sanitize_text_field( $id_match[1] );
			}
			if ( '' === $id ) {
				$stable_date = $experienced_iso ?: substr( $published_iso, 0, 10 );
				$id = 'tp-md-' . substr( hash( 'sha256', self::normalize_identity_text( $author ) . '|' . $stable_date . '|' . self::normalize_identity_text( $title ) ), 0, 32 );
			}

			$reviews[ $id ] = array(
				'id'       => $id,
				'rating'   => $review_rating,
				'title'    => $title,
				'text'     => $text,
				'consumer' => array( 'displayName' => $author ),
				'dates'    => array(
					'publishedDate'  => $published_iso,
					'experiencedDate' => $experienced_iso,
				),
				'labels'   => array( 'verification' => array( 'isVerified' => $verified ) ),
			);
		}

		if ( empty( $reviews ) ) {
			return new WP_Error( 'mdo_trustpilot_reader_empty', 'No se pudieron reconocer reseñas en la salida de Reader.' );
		}
		return self::reviews_to_next_data( array_values( $reviews ) );
	}

	/** @return array|WP_Error */
	private static function rendered_html_to_next_data( string $html ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return new WP_Error( 'mdo_trustpilot_dom_missing', 'DOMDocument no está disponible.' );
		}

		$previous = libxml_use_internal_errors( true );
		$dom      = new DOMDocument();
		$loaded   = $dom->loadHTML( '<?xml encoding="utf-8" ?>' . $html, LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );
		if ( ! $loaded ) {
			return new WP_Error( 'mdo_trustpilot_dom_invalid', 'No se pudo interpretar el HTML renderizado de Trustpilot.' );
		}

		$xpath = new DOMXPath( $dom );
		$cards = $xpath->query( '//article[@data-service-review-card-paper]' );
		if ( ! $cards || 0 === $cards->length ) {
			return new WP_Error( 'mdo_trustpilot_cards_empty', 'No se encontraron tarjetas Trustpilot renderizadas.' );
		}

		$reviews = array();
		foreach ( $cards as $card ) {
			$author = self::xpath_text( $xpath, './/*[@data-consumer-name-typography]', $card );
			$title  = self::xpath_text( $xpath, './/*[@data-service-review-title-typography or @data-review-title-typography]', $card );
			$text   = self::xpath_text( $xpath, './/*[@data-service-review-text-typography or @data-relevant-review-text-typography]', $card );
			$date   = self::xpath_attr( $xpath, './/time[@datetime]', 'datetime', $card );
			$rating = self::extract_dom_rating( $xpath, $card );
			$id     = self::extract_dom_review_id( $xpath, $card );
			if ( '' === $id ) {
				$id = 'tp-dom-' . substr( hash( 'sha256', self::normalize_identity_text( $author ) . '|' . substr( $date, 0, 10 ) . '|' . self::normalize_identity_text( $title ) ), 0, 32 );
			}
			if ( $rating < 1 || $rating > 5 || ( '' === $text && '' === $title ) ) {
				continue;
			}

			$verified_text = self::xpath_text( $xpath, './/*[@data-review-label]', $card );
			$reviews[] = array(
				'id'       => $id,
				'rating'   => $rating,
				'title'    => $title,
				'text'     => $text,
				'consumer' => array( 'displayName' => $author ),
				'dates'    => array( 'publishedDate' => $date ),
				'labels'   => array( 'verification' => array( 'isVerified' => (bool) preg_match( '/verificad|verified/i', $verified_text ) ) ),
			);
		}

		if ( empty( $reviews ) ) {
			return new WP_Error( 'mdo_trustpilot_cards_invalid', 'Las tarjetas Trustpilot no contenían reseñas válidas.' );
		}
		return self::reviews_to_next_data( $reviews );
	}

	private static function reviews_to_next_data( array $reviews ): array {
		$reported_count = absint( get_option( 'mdo_trustpilot_review_count', 0 ) );
		$trust_score    = (float) get_option( 'mdo_trustpilot_rating', 0 );
		return array(
			'props' => array(
				'pageProps' => array(
					'businessUnit' => array(
						'numberOfReviews' => $reported_count,
						'trustScore'      => $trust_score,
					),
					'filters' => array(
						'pagination' => array(
							'totalCount' => $reported_count,
							'totalPages' => 0,
						),
					),
					'reviews' => $reviews,
				),
			),
		);
	}

	private static function plain_markdown_text( string $value ): string {
		$value = preg_replace( '/!\[([^\]]*)\]\([^)]*\)/u', '$1', $value );
		$value = preg_replace( '/\[([^\]]+)\]\([^)]*\)/u', '$1', $value );
		$value = str_replace( array( '**', '__', '`' ), '', (string) $value );
		return trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $value ) ) );
	}

	private static function clean_author_line( string $value ): string {
		$value = self::plain_markdown_text( $value );
		$value = preg_replace( '/\s?[A-Z]{2}•\s*\d+\s*(?:opinión|opiniones|reseña|reseñas)\s*$/u', '', $value );
		return trim( (string) $value );
	}

	private static function normalize_identity_text( string $value ): string {
		$value = remove_accents( self::plain_markdown_text( $value ) );
		$value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $value, 'UTF-8' ) : strtolower( $value );
		return trim( preg_replace( '/[^a-z0-9]+/u', ' ', $value ) );
	}

	private static function previous_non_empty_line( array $lines, int $index ): int {
		for ( ; $index >= 0; --$index ) {
			if ( '' !== trim( (string) $lines[ $index ] ) ) {
				return $index;
			}
		return -1;
	}

	private static function next_non_empty_line( array $lines, int $index ): int {
		$count = count( $lines );
		for ( ; $index < $count; ++$index ) {
			if ( '' !== trim( (string) $lines[ $index ] ) ) {
				return $index;
			}
		return -1;
	}

	private static function is_short_review_date( string $value ): bool {
		$value = self::plain_markdown_text( $value );
		return (bool) preg_match( '/^(?:Actualizada?\s+el\s+)?\d{1,2}\s+(?:ene|feb|mar|abr|may|jun|jul|ago|sep|sept|oct|nov|dic)\s+\d{4}$/iu', $value );
	}

	private static function is_long_review_date( string $value ): bool {
		$value = self::plain_markdown_text( $value );
		return (bool) preg_match( '/^\d{1,2}\s+de\s+(?:enero|febrero|marzo|abril|mayo|junio|julio|agosto|septiembre|octubre|noviembre|diciembre)\s+de\s+\d{4}$/iu', $value );
	}

	private static function spanish_date_to_iso( string $value ): string {
		$value = trim( self::plain_markdown_text( $value ) );
		$value = preg_replace( '/^Actualizada?\s+el\s+/iu', '', $value );
		$months = array(
			'ene' => 1, 'enero' => 1, 'feb' => 2, 'febrero' => 2, 'mar' => 3, 'marzo' => 3,
			'abr' => 4, 'abril' => 4, 'may' => 5, 'mayo' => 5, 'jun' => 6, 'junio' => 6,
			'jul' => 7, 'julio' => 7, 'ago' => 8, 'agosto' => 8, 'sep' => 9, 'sept' => 9,
			'septiembre' => 9, 'oct' => 10, 'octubre' => 10, 'nov' => 11, 'noviembre' => 11,
			'dic' => 12, 'diciembre' => 12,
		);
		if ( preg_match( '/^(\d{1,2})\s+(?:de\s+)?([\p{L}.]+)(?:\s+de)?\s+(\d{4})$/u', $value, $match ) ) {
			$key = function_exists( 'mb_strtolower' ) ? mb_strtolower( rtrim( $match[2], '.' ), 'UTF-8' ) : strtolower( rtrim( $match[2], '.' ) );
			if ( isset( $months[ $key ] ) ) {
				return sprintf( '%04d-%02d-%02d 12:00:00', (int) $match[3], $months[ $key ], (int) $match[1] );
			}
		}
		return '';
	}

	private static function last_case_insensitive_position( string $haystack, string $needle ) {
		if ( function_exists( 'mb_strripos' ) ) {
			return mb_strripos( $haystack, $needle, 0, 'UTF-8' );
		}
		return strripos( $haystack, $needle );
	}

	private static function xpath_text( DOMXPath $xpath, string $query, DOMNode $context ): string {
		$nodes = $xpath->query( $query, $context );
		if ( ! $nodes || 0 === $nodes->length ) {
			return '';
		}
		return trim( preg_replace( '/\s+/u', ' ', (string) $nodes->item( 0 )->textContent ) );
	}

	private static function xpath_attr( DOMXPath $xpath, string $query, string $attribute, DOMNode $context ): string {
		$nodes = $xpath->query( $query, $context );
		if ( ! $nodes || 0 === $nodes->length || ! $nodes->item( 0 ) instanceof DOMElement ) {
			return '';
		}
		return trim( (string) $nodes->item( 0 )->getAttribute( $attribute ) );
	}

	private static function extract_dom_rating( DOMXPath $xpath, DOMNode $card ): int {
		$nodes = $xpath->query( './/*[@data-service-review-rating]', $card );
		if ( $nodes && $nodes->length > 0 && $nodes->item( 0 ) instanceof DOMElement ) {
			$value = (string) $nodes->item( 0 )->getAttribute( 'data-service-review-rating' );
			if ( preg_match( '/([1-5])/', $value, $match ) ) {
				return (int) $match[1];
			}
		}
		$images = $xpath->query( './/img[@alt]', $card );
		if ( $images ) {
			foreach ( $images as $image ) {
				if ( $image instanceof DOMElement && preg_match( '/(?:rated|valorad[^0-9]*)\s*([1-5])|([1-5])\s*(?:out of|de)\s*5/i', $image->getAttribute( 'alt' ), $match ) ) {
					return (int) ( $match[1] ?: $match[2] );
				}
			}
		}
		return 0;
	}

	private static function extract_dom_review_id( DOMXPath $xpath, DOMNode $card ): string {
		$links = $xpath->query( './/a[contains(@href, "/reviews/")]', $card );
		if ( $links ) {
			foreach ( $links as $link ) {
				if ( $link instanceof DOMElement && preg_match( '#/reviews/([A-Za-z0-9_-]{8,})#', (string) $link->getAttribute( 'href' ), $match ) ) {
					return sanitize_text_field( $match[1] );
				}
		}
		return '';
	}

	/** @return array|WP_Error */
	private static function extract_next_data( string $html ) {
		if ( ! preg_match( '#<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>#is', $html, $matches ) ) {
			return new WP_Error( 'mdo_trustpilot_next_data', 'Trustpilot no incluyó __NEXT_DATA__.' );
		}
		$data = json_decode( html_entity_decode( trim( $matches[1] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), true );
		if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
			return new WP_Error( 'mdo_trustpilot_json', 'Trustpilot devolvió __NEXT_DATA__ no válido.' );
		}
		return $data;
	}
}
