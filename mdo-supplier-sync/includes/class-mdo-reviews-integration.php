<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sincronización de reseñas externas directamente desde las APIs oficiales.
 *
 * Google: Google Business Profile API (OAuth 2.0).
 * Trustpilot: Business Units API pública (API key).
 *
 * No se usa Trustindex ni scraping como fuente de contenido de reseñas.
 */
final class MDO_Reviews_Integration {
	private const CRON_HOOK = 'mdo_reviews_daily_import';
	private const SEED_HOOK = 'mdo_reviews_seed_import';
	private const GROUP = 'mdo-supplier-sync';
	private const SCHEDULE_VERSION = '3.0.0';
	private const BUSINESS_DOMAIN = 'elmercadodeorigen.com';
	private const GOOGLE_PROFILE_URL = 'https://www.google.com/maps/search/?api=1&query=El%20Mercado%20de%20Origen';
	private const TRUSTPILOT_PROFILE_URL = 'https://es.trustpilot.com/review/elmercadodeorigen.com';

	public static function init(): void {
		remove_action( self::CRON_HOOK, array( 'MDO_Reviews', 'import_all' ) );
		remove_action( self::SEED_HOOK, array( 'MDO_Reviews', 'import_all' ) );
		remove_action( 'init', array( 'MDO_Reviews', 'ensure_schedule' ), 30 );
		remove_action( 'init', array( 'MDO_Reviews', 'maybe_seed' ), 31 );
		remove_action( 'admin_post_mdo_reviews_import', array( 'MDO_Reviews', 'handle_import' ) );

		add_action( self::CRON_HOOK, array( __CLASS__, 'import_external' ) );
		add_action( 'admin_post_mdo_reviews_import', array( __CLASS__, 'handle_import' ) );
		add_filter( 'wcfmmp_store_tabs', array( __CLASS__, 'store_tabs' ), 999, 2 );

		if ( did_action( 'action_scheduler_init' ) ) {
			self::ensure_schedule();
		} elseif ( function_exists( 'as_schedule_recurring_action' ) ) {
			add_action( 'action_scheduler_init', array( __CLASS__, 'ensure_schedule' ), 25 );
		} else {
			add_action( 'init', array( __CLASS__, 'ensure_schedule' ), 90 );
		}
	}

	public static function deactivate(): void {
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( self::CRON_HOOK, array(), self::GROUP );
		}
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	public static function handle_import(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'mdo-supplier-sync' ) );
		}
		check_admin_referer( 'mdo_reviews_import' );

		$stats = array(
			'wcfm' => self::invoke_reviews_private( 'import_wcfm_reviews' ),
			'woocommerce' => self::invoke_reviews_private( 'import_woocommerce_reviews' ),
		);
		$stats = array_merge( $stats, self::import_external() );
		$found = 0;
		$saved = 0;
		foreach ( $stats as $source_stats ) {
			if ( ! is_array( $source_stats ) ) {
				continue;
			}
			$found += (int) ( $source_stats['found'] ?? 0 );
			$saved += (int) ( $source_stats['saved'] ?? 0 );
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page' => 'mdo-reviews',
					'mdo_notice' => 'imported',
					'found' => $found,
					'saved' => $saved,
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	public static function import_external(): array {
		$context = self::invoke_reviews_private( 'assignment_context' );
		if ( ! is_array( $context ) ) {
			$context = array();
		}

		$stats = array(
			'google' => self::import_google_official( $context ),
			'trustpilot' => self::import_trustpilot_official( $context ),
		);

		$payload = array(
			'at' => time(),
			'stats' => $stats,
			'mode' => 'official_external_only',
		);
		update_option( 'mdo_reviews_last_external_import', $payload, false );
		update_option( 'mdo_reviews_last_import', $payload, false );

		return $stats;
	}

	private static function import_google_official( array $context ): array {
		$token = self::google_access_token();
		if ( is_wp_error( $token ) ) {
			return self::error_stats( 'google_business_profile_api', $token );
		}

		$identity = self::google_business_identity( $token );
		if ( is_wp_error( $identity ) ) {
			return self::error_stats( 'google_business_profile_api', $identity );
		}

		$account_id = (string) $identity['account_id'];
		$location_id = (string) $identity['location_id'];
		$page_token = '';
		$found = 0;
		$saved = 0;
		$expected = 0;
		$guard = 0;

		do {
			$url = sprintf(
				'https://mybusiness.googleapis.com/v4/accounts/%s/locations/%s/reviews?pageSize=50',
				rawurlencode( $account_id ),
				rawurlencode( $location_id )
			);
			if ( '' !== $page_token ) {
				$url .= '&pageToken=' . rawurlencode( $page_token );
			}
			$response = self::api_json( $url, array( 'Authorization' => 'Bearer ' . $token ) );
			if ( is_wp_error( $response ) ) {
				return self::error_stats( 'google_business_profile_api', $response, $found, $saved, $expected );
			}

			$reviews = isset( $response['reviews'] ) && is_array( $response['reviews'] ) ? $response['reviews'] : array();
			$expected = max( $expected, absint( $response['totalReviewCount'] ?? 0 ) );
			foreach ( $reviews as $review ) {
				if ( ! is_array( $review ) ) {
					continue;
				}
				++$found;
				$reviewer = isset( $review['reviewer'] ) && is_array( $review['reviewer'] ) ? $review['reviewer'] : array();
				$data = array(
					'source_review_id' => (string) ( $review['reviewId'] ?? '' ),
					'author_name' => (string) ( $reviewer['displayName'] ?? '' ),
					'author_avatar_url' => (string) ( $reviewer['profilePhotoUrl'] ?? '' ),
					'rating' => self::google_star_rating( $review['starRating'] ?? 0 ),
					'review_text' => wp_strip_all_tags( (string) ( $review['comment'] ?? '' ) ),
					'review_date' => self::normalize_source_date( $review['createTime'] ?? $review['updateTime'] ?? '' ),
					'source_url' => self::GOOGLE_PROFILE_URL,
					'source_payload' => wp_json_encode( $review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
					'status' => 'pending',
				);
				if ( $data['rating'] > 0 && self::save_external_row( 'google', $data, $context ) ) {
					++$saved;
				}
			}
			$page_token = (string) ( $response['nextPageToken'] ?? '' );
			++$guard;
		} while ( '' !== $page_token && $guard < 100 );

		if ( $expected > 0 ) {
			update_option( 'mdo_google_review_count', $expected, false );
			update_option( 'mdo_google_review_count_updated_at', time(), false );
		}

		return array(
			'found' => $found,
			'saved' => $saved,
			'expected' => $expected,
			'complete' => $expected > 0 ? $found >= $expected : true,
			'provider' => 'google_business_profile_api',
			'configured' => true,
		);
	}

	private static function google_access_token() {
		$direct = self::config_value( 'MDO_GOOGLE_BUSINESS_ACCESS_TOKEN', 'mdo_google_business_access_token' );
		if ( '' !== $direct ) {
			return $direct;
		}
		$client_id = self::config_value( 'MDO_GOOGLE_BUSINESS_CLIENT_ID', 'mdo_google_business_client_id' );
		$client_secret = self::config_value( 'MDO_GOOGLE_BUSINESS_CLIENT_SECRET', 'mdo_google_business_client_secret' );
		$refresh_token = self::config_value( 'MDO_GOOGLE_BUSINESS_REFRESH_TOKEN', 'mdo_google_business_refresh_token' );
		if ( '' === $client_id || '' === $client_secret || '' === $refresh_token ) {
			return new WP_Error( 'missing_google_oauth', 'Faltan credenciales OAuth de Google Business Profile.' );
		}

		$response = wp_remote_post(
			'https://oauth2.googleapis.com/token',
			array(
				'timeout' => 30,
				'body' => array(
					'client_id' => $client_id,
					'client_secret' => $client_secret,
					'refresh_token' => $refresh_token,
					'grant_type' => 'refresh_token',
				),
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'google_oauth_transport', $response->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 || ! is_array( $body ) || empty( $body['access_token'] ) ) {
			return new WP_Error( 'google_oauth_failed', 'Google OAuth no ha devuelto un access token válido.', array( 'status' => $status ) );
		}
		return (string) $body['access_token'];
	}

	private static function google_business_identity( string $token ) {
		$account_id = self::clean_resource_id( self::config_value( 'MDO_GOOGLE_BUSINESS_ACCOUNT_ID', 'mdo_google_business_account_id' ), 'accounts/' );
		$location_id = self::clean_resource_id( self::config_value( 'MDO_GOOGLE_BUSINESS_LOCATION_ID', 'mdo_google_business_location_id' ), 'locations/' );
		if ( '' !== $account_id && '' !== $location_id ) {
			return array( 'account_id' => $account_id, 'location_id' => $location_id );
		}

		$headers = array( 'Authorization' => 'Bearer ' . $token );
		$accounts_response = self::api_json( 'https://mybusinessaccountmanagement.googleapis.com/v1/accounts', $headers );
		if ( is_wp_error( $accounts_response ) ) {
			return $accounts_response;
		}
		$accounts = isset( $accounts_response['accounts'] ) && is_array( $accounts_response['accounts'] ) ? $accounts_response['accounts'] : array();
		$candidates = array();
		foreach ( $accounts as $account ) {
			if ( ! is_array( $account ) || empty( $account['name'] ) ) {
				continue;
			}
			$current_account = self::clean_resource_id( (string) $account['name'], 'accounts/' );
			if ( '' === $current_account || ( '' !== $account_id && $account_id !== $current_account ) ) {
				continue;
			}
			$page_token = '';
			$guard = 0;
			do {
				$url = sprintf(
					'https://mybusinessbusinessinformation.googleapis.com/v1/accounts/%s/locations?readMask=name,title,websiteUri&pageSize=100',
					rawurlencode( $current_account )
				);
				if ( '' !== $page_token ) {
					$url .= '&pageToken=' . rawurlencode( $page_token );
				}
				$locations_response = self::api_json( $url, $headers );
				if ( is_wp_error( $locations_response ) ) {
					break;
				}
				$locations = isset( $locations_response['locations'] ) && is_array( $locations_response['locations'] ) ? $locations_response['locations'] : array();
				foreach ( $locations as $location ) {
					if ( ! is_array( $location ) || empty( $location['name'] ) ) {
						continue;
					}
					$current_location = self::clean_resource_id( (string) $location['name'], 'locations/' );
					$website = strtolower( (string) ( $location['websiteUri'] ?? '' ) );
					$candidates[] = array(
						'account_id' => $current_account,
						'location_id' => $current_location,
						'matches_domain' => false !== strpos( $website, self::BUSINESS_DOMAIN ),
					);
				}
				$page_token = (string) ( $locations_response['nextPageToken'] ?? '' );
				++$guard;
			} while ( '' !== $page_token && $guard < 50 );
		}

		$chosen = null;
		foreach ( $candidates as $candidate ) {
			if ( ! empty( $candidate['matches_domain'] ) ) {
				$chosen = $candidate;
				break;
			}
		}
		if ( ! $chosen && 1 === count( $candidates ) ) {
			$chosen = $candidates[0];
		}
		if ( ! $chosen ) {
			return new WP_Error( 'google_location_not_resolved', 'No se ha podido identificar de forma inequívoca la ubicación de Google Business Profile.' );
		}
		update_option( 'mdo_google_business_account_id', (string) $chosen['account_id'], false );
		update_option( 'mdo_google_business_location_id', (string) $chosen['location_id'], false );
		return array( 'account_id' => (string) $chosen['account_id'], 'location_id' => (string) $chosen['location_id'] );
	}

	private static function import_trustpilot_official( array $context ): array {
		$api_key = self::config_value( 'MDO_TRUSTPILOT_API_KEY', 'mdo_trustpilot_api_key' );
		if ( '' === $api_key ) {
			return array(
				'found' => 0,
				'saved' => 0,
				'provider' => 'trustpilot_business_units_api',
				'configured' => false,
				'error' => 'missing_trustpilot_api_key',
			);
		}

		$headers = array( 'apikey' => $api_key );
		$business_unit_id = self::config_value( 'MDO_TRUSTPILOT_BUSINESS_UNIT_ID', 'mdo_trustpilot_business_unit_id' );
		if ( '' === $business_unit_id ) {
			$find = self::api_json( 'https://api.trustpilot.com/v1/business-units/find?name=' . rawurlencode( self::BUSINESS_DOMAIN ), $headers );
			if ( is_wp_error( $find ) || empty( $find['id'] ) ) {
				return self::error_stats( 'trustpilot_business_units_api', is_wp_error( $find ) ? $find : new WP_Error( 'trustpilot_business_unit_not_found', 'No se ha encontrado el Business Unit de Trustpilot.' ) );
			}
			$business_unit_id = (string) $find['id'];
			update_option( 'mdo_trustpilot_business_unit_id', $business_unit_id, false );
		}

		$expected = 0;
		$summary = self::api_json( 'https://api.trustpilot.com/v1/business-units/' . rawurlencode( $business_unit_id ), $headers );
		if ( ! is_wp_error( $summary ) ) {
			$expected = absint( $summary['numberOfReviews']['total'] ?? 0 );
			$rating = (float) ( $summary['score']['trustScore'] ?? 0 );
			if ( $expected > 0 ) {
				update_option( 'mdo_trustpilot_review_count', $expected, false );
				update_option( 'mdo_trustpilot_review_count_updated_at', time(), false );
			}
			if ( $rating > 0 && $rating <= 5 ) {
				update_option( 'mdo_trustpilot_rating', round( $rating, 1 ), false );
			}
		}

		$page_token = '';
		$found = 0;
		$saved = 0;
		$guard = 0;
		do {
			$url = 'https://api.trustpilot.com/v1/business-units/' . rawurlencode( $business_unit_id ) . '/all-reviews';
			if ( '' !== $page_token ) {
				$url .= '?pageToken=' . rawurlencode( $page_token );
			}
			$response = self::api_json( $url, $headers );
			if ( is_wp_error( $response ) ) {
				return self::error_stats( 'trustpilot_business_units_api', $response, $found, $saved, $expected );
			}
			$reviews = isset( $response['reviews'] ) && is_array( $response['reviews'] ) ? $response['reviews'] : array();
			foreach ( $reviews as $review ) {
				if ( ! is_array( $review ) ) {
					continue;
				}
				++$found;
				$consumer = isset( $review['consumer'] ) && is_array( $review['consumer'] ) ? $review['consumer'] : array();
				$data = array(
					'source_review_id' => (string) ( $review['id'] ?? '' ),
					'author_name' => (string) ( $consumer['displayName'] ?? '' ),
					'rating' => max( 1, min( 5, (int) ( $review['stars'] ?? 0 ) ) ),
					'review_title' => (string) ( $review['title'] ?? '' ),
					'review_text' => wp_strip_all_tags( (string) ( $review['text'] ?? '' ) ),
					'review_date' => self::normalize_source_date( $review['createdAt'] ?? $review['updatedAt'] ?? '' ),
					'source_url' => self::TRUSTPILOT_PROFILE_URL,
					'source_payload' => wp_json_encode( $review, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
					'status' => 'pending',
				);
				if ( self::save_external_row( 'trustpilot', $data, $context ) ) {
					++$saved;
				}
			}
			$page_token = (string) ( $response['nextPageToken'] ?? '' );
			++$guard;
		} while ( '' !== $page_token && $guard < 500 );

		update_option( 'mdo_trustpilot_review_source', 'trustpilot_official_api', false );
		update_option( 'mdo_trustpilot_review_source_url', self::TRUSTPILOT_PROFILE_URL, false );

		return array(
			'found' => $found,
			'saved' => $saved,
			'expected' => $expected,
			'complete' => $expected > 0 ? $found >= $expected : true,
			'provider' => 'trustpilot_business_units_api',
			'configured' => true,
		);
	}

	private static function api_json( string $url, array $headers = array() ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 30,
				'redirection' => 3,
				'headers' => $headers,
			)
		);
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'api_transport_error', $response->get_error_message() );
		}
		$status = (int) wp_remote_retrieve_response_code( $response );
		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( $status < 200 || $status >= 300 ) {
			return new WP_Error( 'api_http_' . $status, 'La API oficial ha respondido con HTTP ' . $status . '.', array( 'status' => $status ) );
		}
		if ( ! is_array( $body ) ) {
			return new WP_Error( 'api_invalid_json', 'La API oficial no ha devuelto JSON válido.' );
		}
		return $body;
	}

	private static function config_value( string $constant, string $option ): string {
		if ( defined( $constant ) ) {
			$value = constant( $constant );
			if ( is_scalar( $value ) && '' !== trim( (string) $value ) ) {
				return trim( (string) $value );
			}
		}
		$env = getenv( $constant );
		if ( false !== $env && '' !== trim( (string) $env ) ) {
			return trim( (string) $env );
		}
		$value = get_option( $option, '' );
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}

	private static function clean_resource_id( string $value, string $prefix ): string {
		$value = trim( $value );
		if ( 0 === strpos( $value, $prefix ) ) {
			$value = substr( $value, strlen( $prefix ) );
		}
		return trim( $value, '/' );
	}

	private static function google_star_rating( $value ): int {
		if ( is_numeric( $value ) ) {
			return max( 1, min( 5, (int) round( (float) $value ) ) );
		}
		$map = array( 'ONE' => 1, 'TWO' => 2, 'THREE' => 3, 'FOUR' => 4, 'FIVE' => 5 );
		return (int) ( $map[ strtoupper( (string) $value ) ] ?? 0 );
	}

	private static function normalize_source_date( $value ): ?string {
		if ( empty( $value ) ) {
			return null;
		}
		if ( is_numeric( $value ) ) {
			$timestamp = (int) $value;
			if ( $timestamp > 20000000000 ) {
				$timestamp = (int) floor( $timestamp / 1000 );
			}
			return wp_date( 'Y-m-d H:i:s', $timestamp );
		}
		$timestamp = strtotime( (string) $value );
		return $timestamp ? wp_date( 'Y-m-d H:i:s', $timestamp ) : null;
	}

	private static function save_external_row( string $source, array $data, array $context ): bool {
		$assigned = self::invoke_reviews_private( 'apply_assignment', array( $data, $context ) );
		if ( ! is_array( $assigned ) ) {
			$assigned = $data;
		}
		if ( empty( $assigned['status'] ) ) {
			$assigned['status'] = 'pending';
		}
		return (bool) self::invoke_reviews_private( 'upsert_review', array( $source, $assigned ) );
	}

	private static function error_stats( string $provider, WP_Error $error, int $found = 0, int $saved = 0, int $expected = 0 ): array {
		$result = array(
			'found' => $found,
			'saved' => $saved,
			'provider' => $provider,
			'configured' => ! in_array( $error->get_error_code(), array( 'missing_google_oauth', 'missing_trustpilot_api_key' ), true ),
			'error' => $error->get_error_code(),
		);
		if ( $expected > 0 ) {
			$result['expected'] = $expected;
			$result['complete'] = false;
		}
		return $result;
	}

	public static function ensure_schedule(): void {
		$version = (string) get_option( 'mdo_reviews_external_schedule_version', '' );
		$existing = self::next_scheduled_timestamp();
		$wrong_slot = $existing > 0 && '02:30' !== wp_date( 'H:i', $existing );
		$needs_reset = self::SCHEDULE_VERSION !== $version || $wrong_slot;
		if ( $needs_reset ) {
			if ( function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::CRON_HOOK, array(), self::GROUP );
			}
			wp_clear_scheduled_hook( self::CRON_HOOK );
			$existing = 0;
		}
		$next = self::next_two_thirty_timestamp();
		if ( function_exists( 'as_has_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			if ( $existing <= 0 && ! as_has_scheduled_action( self::CRON_HOOK, array(), self::GROUP ) ) {
				as_schedule_recurring_action( $next, DAY_IN_SECONDS, self::CRON_HOOK, array(), self::GROUP );
			}
		} elseif ( $existing <= 0 && ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( $next, 'daily', self::CRON_HOOK );
		}
		if ( $needs_reset || self::SCHEDULE_VERSION !== $version ) {
			update_option( 'mdo_reviews_external_schedule_version', self::SCHEDULE_VERSION, false );
		}
	}

	public static function store_tabs( array $tabs, $store_id ): array {
		$label = __( 'Reseñas', 'mdo-supplier-sync' );
		$result = array();
		$inserted = false;
		foreach ( $tabs as $key => $value ) {
			if ( 'reviews' === $key ) {
				continue;
			}
			$result[ $key ] = $value;
			if ( 'about' === $key ) {
				$result['reviews'] = $label;
				$inserted = true;
			}
		}
		if ( ! $inserted ) {
			$result['reviews'] = $label;
		}
		return $result;
	}

	private static function next_scheduled_timestamp(): int {
		if ( function_exists( 'as_next_scheduled_action' ) ) {
			$next = as_next_scheduled_action( self::CRON_HOOK, array(), self::GROUP );
			if ( is_numeric( $next ) && (int) $next > 0 ) {
				return (int) $next;
			}
		}
		$next = wp_next_scheduled( self::CRON_HOOK );
		return $next ? (int) $next : 0;
	}

	private static function next_two_thirty_timestamp(): int {
		$now = current_datetime();
		$next = $now->setTime( 2, 30, 0 );
		if ( $next <= $now ) {
			$next = $next->modify( '+1 day' );
		}
		return $next->getTimestamp();
	}

	private static function invoke_reviews_private( string $method, array $args = array() ) {
		try {
			$reflection = new ReflectionMethod( 'MDO_Reviews', $method );
			if ( method_exists( $reflection, 'setAccessible' ) ) {
				$reflection->setAccessible( true );
			}
			return $reflection->invokeArgs( null, $args );
		} catch ( Throwable $error ) {
			error_log( '[EMDO reviews] Error en integración: ' . $method . ' - ' . $error->getMessage() );
			return null;
		}
	}
}
