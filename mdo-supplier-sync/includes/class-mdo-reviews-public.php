<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public vendor reviews UI owned by EMDO.
 *
 * WCFM keeps providing the vendor-store shell, but EMDO owns the reviews
 * endpoint and content so the native WCFM "no reviews" state cannot conflict
 * with the unified review table.
 */
final class MDO_Reviews_Public {
	private const QUERY_VAR = 'mdo_reviews';
	private const TAB_KEY = 'mdo_reviews';
	private const ENDPOINT = 'reviews';
	private const ROUTE_VERSION = '2.0.0';
	private const ROUTE_OPTION = 'mdo_reviews_public_route_version';

	public static function init(): void {
		// Retire the previous bridge to the native WCFM reviews tab/template.
		remove_filter( 'wcfmmp_store_tabs', array( 'MDO_Reviews_Integration', 'store_tabs' ), 999 );
		remove_action( 'wcfmmp_rewrite_rules_loaded', array( 'MDO_Reviews_Route', 'rewrite_rules' ), 50 );
		remove_filter( 'query_vars', array( 'MDO_Reviews_Route', 'query_vars' ), 50 );
		remove_filter( 'wcfmp_store_tabs_url', array( 'MDO_Reviews_Route', 'store_tab_url' ), 50 );
		remove_filter( 'wcfmp_store_default_query_vars', array( 'MDO_Reviews_Route', 'default_query_var' ), 50 );
		remove_filter( 'wcfmmp_store_default_template', array( 'MDO_Reviews_Route', 'default_template' ), 50 );
		remove_action( 'wp_loaded', array( 'MDO_Reviews_Route', 'maybe_flush_rewrite_rules' ), 99 );
		remove_action( 'wcfmmp_store_before_latest_reviews', array( 'MDO_Reviews', 'render_store_reviews' ), 5 );

		add_action( 'wcfmmp_rewrite_rules_loaded', array( __CLASS__, 'rewrite_rules' ), 60 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ), 60 );
		add_filter( 'wcfmmp_store_tabs', array( __CLASS__, 'store_tabs' ), 1000, 2 );
		add_filter( 'wcfmp_store_tabs_url', array( __CLASS__, 'store_tab_url' ), 1000, 2 );
		add_filter( 'wcfmp_store_default_query_vars', array( __CLASS__, 'default_query_var' ), 60 );
		add_filter( 'wcfmmp_store_default_template', array( __CLASS__, 'default_template' ), 60, 2 );
		add_filter( 'wcfmmp_store_default_template_path', array( __CLASS__, 'default_template_path' ), 60, 2 );
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 100 );
	}

	public static function rewrite_rules( string $wcfm_store_url ): void {
		$base = trim( $wcfm_store_url, '/' );
		if ( '' === $base ) {
			return;
		}

		add_rewrite_rule(
			$base . '/([^/]+)/' . self::ENDPOINT . '/?$',
			'index.php?' . $base . '=$matches[1]&' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	public static function query_vars( array $vars ): array {
		if ( ! in_array( self::QUERY_VAR, $vars, true ) ) {
			$vars[] = self::QUERY_VAR;
		}
		return $vars;
	}

	public static function store_tabs( array $tabs, $store_id ): array {
		$result = array();
		$inserted = false;
		foreach ( $tabs as $key => $value ) {
			if ( 'reviews' === $key || self::TAB_KEY === $key ) {
				continue;
			}
			$result[ $key ] = $value;
			if ( 'about' === $key ) {
				$result[ self::TAB_KEY ] = __( 'Reseñas', 'mdo-supplier-sync' );
				$inserted = true;
			}
		}
		if ( ! $inserted ) {
			$result[ self::TAB_KEY ] = __( 'Reseñas', 'mdo-supplier-sync' );
		}
		return $result;
	}

	public static function store_tab_url( string $store_tab_url, string $tab ): string {
		if ( self::TAB_KEY !== $tab ) {
			return $store_tab_url;
		}

		// Be defensive if a previous filter has already appended /reviews.
		$store_tab_url = preg_replace( '~/' . preg_quote( self::ENDPOINT, '~' ) . '/?$~', '/', $store_tab_url );
		return trailingslashit( (string) $store_tab_url ) . self::ENDPOINT . '/';
	}

	public static function default_query_var( $query_var ) {
		if ( get_query_var( self::QUERY_VAR ) ) {
			return self::TAB_KEY;
		}
		return $query_var;
	}

	public static function default_template( string $template, string $tab ): string {
		if ( self::TAB_KEY === $tab ) {
			return 'store/wcfmmp-view-store-mdo-reviews.php';
		}
		return $template;
	}

	public static function default_template_path( $template_path, $tab ) {
		if ( self::TAB_KEY === $tab ) {
			return trailingslashit( MDO_SUPPLIER_SYNC_PATH . 'templates' );
		}
		return $template_path;
	}

	public static function render_current_store(): void {
		$vendor_id = self::current_vendor_id();
		if ( $vendor_id < 1 ) {
			return;
		}

		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, AVG(rating) AS average_rating FROM {$table} WHERE status='validated' AND vendor_user_id=%d AND rating>0",
				$vendor_id
			),
			ARRAY_A
		);
		$total = (int) ( $summary['total'] ?? 0 );
		$average = (float) ( $summary['average_rating'] ?? 0 );

		echo '<div class="mdo-reviews-page">';
		echo '<h2 class="mdo-reviews-page-title">' . esc_html__( 'Reseñas', 'mdo-supplier-sync' ) . '</h2>';

		if ( $total > 0 ) {
			self::render_summary( $average, $total );
			MDO_Reviews::render_store_reviews( $vendor_id );
		} else {
			echo '<p class="mdo-reviews-empty">' . esc_html__( 'Todavía no hay reseñas publicadas para esta tienda.', 'mdo-supplier-sync' ) . '</p>';
		}
		echo '</div>';
	}

	private static function render_summary( float $average, int $total ): void {
		$average = max( 0.0, min( 5.0, $average ) );
		$filled = ( $average / 5 ) * 100;
		$average_label = number_format_i18n( $average, 1 );
		$rating_label = sprintf( __( '%s sobre 5', 'mdo-supplier-sync' ), $average_label );
		$count_label = sprintf(
			_n( '%s reseña', '%s reseñas', $total, 'mdo-supplier-sync' ),
			number_format_i18n( $total )
		);

		echo '<section class="mdo-review-summary" aria-label="' . esc_attr( $rating_label . ', ' . $count_label ) . '">';
		echo '<div class="mdo-review-summary-score">' . esc_html( $average_label ) . '</div>';
		echo '<div class="mdo-review-summary-body">';
		echo '<div class="mdo-review-summary-stars" aria-hidden="true">';
		echo '<span class="mdo-review-summary-stars-base">★★★★★</span>';
		echo '<span class="mdo-review-summary-stars-fill" style="width:' . esc_attr( number_format( $filled, 2, '.', '' ) ) . '%">★★★★★</span>';
		echo '</div>';
		echo '<div class="mdo-review-summary-rating"><strong>' . esc_html( $rating_label ) . '</strong></div>';
		echo '<div class="mdo-review-summary-count">' . esc_html( $count_label ) . '</div>';
		echo '</div>';
		echo '</section>';
	}

	private static function current_vendor_id(): int {
		$store_url = (string) get_option( 'wcfm_store_url', 'store' );
		$store_name = apply_filters( 'wcfmmp_store_query_var', get_query_var( $store_url ) );
		if ( ! is_string( $store_name ) || '' === $store_name ) {
			return 0;
		}
		$user = get_user_by( 'slug', $store_name );
		return $user ? (int) $user->ID : 0;
	}

	public static function maybe_flush_rewrite_rules(): void {
		if ( self::ROUTE_VERSION === (string) get_option( self::ROUTE_OPTION, '' ) ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( self::ROUTE_OPTION, self::ROUTE_VERSION, false );
	}
}
