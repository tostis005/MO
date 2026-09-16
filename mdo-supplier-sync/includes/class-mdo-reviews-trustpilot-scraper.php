<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scraper público de Trustpilot ejecutado desde el servidor de producción.
 *
 * Lee el JSON SSR __NEXT_DATA__ de las páginas públicas y entrega el payload a
 * MDO_Reviews_Scraping::import_payload(), por lo que no duplica atribución,
 * deduplicación ni protección de decisiones manuales.
 */
final class MDO_Reviews_Trustpilot_Scraper {
	private const PROFILE_ES = 'https://es.trustpilot.com/review/elmercadodeorigen.com';
	private const PROFILE_WWW = 'https://www.trustpilot.com/review/elmercadodeorigen.com';

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

		$reviews = array();
		$reported_count = 0;
		$rating = 0.0;
		$total_pages = 0;
		$profile_url = self::PROFILE_ES;
		$max_pages = 'full' === $mode ? 60 : 2;

		for ( $page_number = 1; $page_number <= $max_pages; ++$page_number ) {
			$page = self::fetch_page( $page_number );
			if ( is_wp_error( $page ) ) {
				return $page;
			}
			$profile_url = (string) ( $page['profile_url'] ?? $profile_url );
			$page_props = $page['data']['props']['pageProps'] ?? null;
			if ( ! is_array( $page_props ) ) {
				return new WP_Error( 'mdo_trustpilot_pageprops', 'Trustpilot no devolvió pageProps válidos.' );
			}

			if ( 1 === $page_number ) {
				$business_unit = isset( $page_props['businessUnit'] ) && is_array( $page_props['businessUnit'] ) ? $page_props['businessUnit'] : array();
				$filters = isset( $page_props['filters'] ) && is_array( $page_props['filters'] ) ? $page_props['filters'] : array();
				$pagination = isset( $filters['pagination'] ) && is_array( $filters['pagination'] ) ? $filters['pagination'] : array();
				$reported_count = absint( $business_unit['numberOfReviews'] ?? $pagination['totalCount'] ?? 0 );
				$rating = (float) ( $business_unit['trustScore'] ?? 0 );
				$total_pages = absint( $pagination['totalPages'] ?? 0 );
			}

			$raw_reviews = isset( $page_props['reviews'] ) && is_array( $page_props['reviews'] ) ? $page_props['reviews'] : array();
			if ( empty( $raw_reviews ) ) {
				break;
			}
			foreach ( $raw_reviews as $raw_review ) {
				if ( ! is_array( $raw_review ) ) {
					continue;
				}
				$id = sanitize_text_field( (string) ( $raw_review['id'] ?? '' ) );
				$review_rating = (int) round( (float) ( $raw_review['rating'] ?? 0 ) );
				if ( '' === $id || $review_rating < 1 || $review_rating > 5 ) {
					continue;
				}
				$consumer = isset( $raw_review['consumer'] ) && is_array( $raw_review['consumer'] ) ? $raw_review['consumer'] : array();
				$dates = isset( $raw_review['dates'] ) && is_array( $raw_review['dates'] ) ? $raw_review['dates'] : array();
				$reviews[ $id ] = array(
					'id' => $id,
					'author_name' => (string) ( $consumer['displayName'] ?? '' ),
					'author_avatar_url' => (string) ( $consumer['imageUrl'] ?? '' ),
					'rating' => $review_rating,
					'title' => (string) ( $raw_review['title'] ?? '' ),
					'text' => (string) ( $raw_review['text'] ?? '' ),
					'date' => (string) ( $dates['publishedDate'] ?? $dates['experiencedDate'] ?? '' ),
					'source_url' => 'https://www.trustpilot.com/reviews/' . rawurlencode( $id ),
					'verified' => ! empty( $raw_review['labels']['verification']['isVerified'] ),
					'transport' => 'trustpilot_next_data',
				);
			}

			if ( 'full' === $mode && $reported_count > 0 && count( $reviews ) >= $reported_count ) {
				break;
			}
			if ( $total_pages > 0 && $page_number >= $total_pages ) {
				break;
			}
			if ( 'incremental' === $mode && $page_number >= 2 ) {
				break;
			}
			usleep( 350000 );
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
			'source' => 'trustpilot',
			'provider' => 'trustpilot_public_http',
			'mode' => $mode,
			'scraped_at' => gmdate( 'c' ),
			'reported_count' => $reported_count,
			'rating' => $rating,
			'profile_url' => $profile_url,
			'sorted_newest' => true,
			'reviews' => array_values( $reviews ),
		);
	}

	/** @return array|WP_Error */
	private static function fetch_page( int $page_number ) {
		$last_error = null;
		foreach ( array( self::PROFILE_ES, self::PROFILE_WWW ) as $base_url ) {
			$url = add_query_arg(
				array(
					'page' => max( 1, $page_number ),
					'sort' => 'recency',
				),
				$base_url
			);
			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 35,
					'redirection' => 5,
					'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
					'headers' => array(
						'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
						'Accept-Language' => 'es-ES,es;q=0.9,en;q=0.8',
						'Cache-Control' => 'no-cache',
						'Pragma' => 'no-cache',
						'Referer' => 'https://www.google.com/',
					),
				)
			);
			if ( is_wp_error( $response ) ) {
				$last_error = $response;
				continue;
			}
			$code = (int) wp_remote_retrieve_response_code( $response );
			$body = (string) wp_remote_retrieve_body( $response );
			if ( $code < 200 || $code >= 400 || '' === $body ) {
				$last_error = new WP_Error( 'mdo_trustpilot_http', 'Trustpilot HTTP ' . $code . ' en página ' . $page_number . '.' );
				continue;
			}
			if ( ! preg_match( '#<script[^>]+id=["\']__NEXT_DATA__["\'][^>]*>(.*?)</script>#is', $body, $matches ) ) {
				$last_error = new WP_Error( 'mdo_trustpilot_next_data', 'Trustpilot no incluyó __NEXT_DATA__ en página ' . $page_number . '.' );
				continue;
			}
			$data = json_decode( trim( $matches[1] ), true );
			if ( ! is_array( $data ) || JSON_ERROR_NONE !== json_last_error() ) {
				$last_error = new WP_Error( 'mdo_trustpilot_json', 'Trustpilot devolvió __NEXT_DATA__ no válido en página ' . $page_number . '.' );
				continue;
			}
			return array( 'profile_url' => $base_url, 'data' => $data );
		}
		return $last_error instanceof WP_Error ? $last_error : new WP_Error( 'mdo_trustpilot_fetch', 'No se pudo descargar Trustpilot.' );
	}
}
