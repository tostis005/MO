<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public vendor reviews UI owned by EMDO.
 */
final class MDO_Reviews_Public {
	private const QUERY_VAR = 'mdo_reviews';
	private const TAB_KEY = 'mdo_reviews';
	private const ENDPOINT = 'reviews';
	private const ROUTE_VERSION = '2.1.0';
	private const ROUTE_OPTION = 'mdo_reviews_public_route_version';
	private const PAGE_SIZE = 10;

	public static function init(): void {
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
		add_filter( 'wcfmp_store_default_query_vars', array( __CLASS__, 'default_query_var' ), 60, 3 );
		add_filter( 'wcfmmp_store_default_query_vars', array( __CLASS__, 'default_query_var' ), 60, 3 );
		add_filter( 'wcfmp_store_default_template', array( __CLASS__, 'default_template' ), 60, 2 );
		add_filter( 'wcfmmp_store_default_template', array( __CLASS__, 'default_template' ), 60, 2 );
		add_filter( 'wcfmp_store_default_template_path', array( __CLASS__, 'default_template_path' ), 60, 2 );
		add_action( 'wp_loaded', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 100 );
	}

	public static function rewrite_rules( string $wcfm_store_url ): void {
		$base = trim( $wcfm_store_url, '/' );
		if ( '' === $base ) {
			return;
		}
		add_rewrite_rule( $base . '/([^/]+)/' . self::ENDPOINT . '/?$', 'index.php?' . $base . '=$matches[1]&' . self::QUERY_VAR . '=1', 'top' );
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
		$store_tab_url = preg_replace( '~/' . preg_quote( self::ENDPOINT, '~' ) . '/?$~', '/', $store_tab_url );
		return trailingslashit( (string) $store_tab_url ) . self::ENDPOINT . '/';
	}

	public static function default_query_var( $query_var, ...$unused ) {
		return get_query_var( self::QUERY_VAR ) ? self::TAB_KEY : $query_var;
	}

	public static function default_template( $template, $tab ) {
		return self::TAB_KEY === $tab ? 'store/wcfmmp-view-store-mdo-reviews.php' : $template;
	}

	public static function default_template_path( $template_path, $tab ) {
		return self::TAB_KEY === $tab ? trailingslashit( MDO_SUPPLIER_SYNC_PATH . 'templates' ) : $template_path;
	}

	public static function render_current_store(): void {
		$vendor_id = self::current_vendor_id();
		if ( $vendor_id < 1 ) {
			return;
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$vendor_sql = MDO_Reviews_Vendors::review_matches_vendor_sql( 'r' );
		$summary = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total, AVG(r.rating) AS average_rating FROM {$table} r WHERE r.status='validated' AND r.rating>0 AND {$vendor_sql}",
				$vendor_id,
				$vendor_id,
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
			self::render_reviews( $vendor_id, $total );
		} else {
			echo '<p class="mdo-reviews-empty">' . esc_html__( 'Todavía no hay reseñas publicadas para esta tienda.', 'mdo-supplier-sync' ) . '</p>';
		}
		echo '</div>';
	}

	private static function render_reviews( int $vendor_id, int $total ): void {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$page = max( 1, absint( $_GET['mdo_review_page'] ?? 1 ) );
		$offset = ( $page - 1 ) * self::PAGE_SIZE;
		$vendor_sql = MDO_Reviews_Vendors::review_matches_vendor_sql( 'r' );
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT r.* FROM {$table} r WHERE r.status='validated' AND {$vendor_sql} ORDER BY (r.review_date IS NULL) ASC,r.review_date DESC,r.id DESC LIMIT %d OFFSET %d",
				$vendor_id,
				$vendor_id,
				$vendor_id,
				self::PAGE_SIZE,
				$offset
			)
		);
		if ( ! $rows ) {
			return;
		}
		echo '<style>.bd_review_section>.review_section,.bd_review_section>.pagination{display:none!important}</style><div class="mdo-store-reviews" aria-label="Reseñas de la tienda">';
		foreach ( $rows as $row ) {
			self::render_review( $row );
		}
		$pages = (int) ceil( $total / self::PAGE_SIZE );
		if ( $pages > 1 ) {
			$base_url = remove_query_arg( 'mdo_review_page' );
			echo '<nav class="mdo-review-pagination" aria-label="Paginación de reseñas">';
			for ( $i = 1; $i <= $pages; $i++ ) {
				$url = 1 === $i ? $base_url : add_query_arg( 'mdo_review_page', $i, $base_url );
				echo '<a class="' . ( $i === $page ? 'is-current' : '' ) . '" href="' . esc_url( $url ) . '#reviews">' . esc_html( (string) $i ) . '</a>';
			}
			echo '</nav>';
		}
		echo '</div>';
	}

	private static function render_review( $row ): void {
		$name = $row->author_name ?: 'Cliente';
		$initial = function_exists( 'mb_substr' ) ? mb_substr( $name, 0, 1, 'UTF-8' ) : substr( $name, 0, 1 );
		echo '<article class="mdo-store-review"><div class="mdo-store-review-avatar">';
		if ( $row->author_avatar_url ) {
			echo '<img src="' . esc_url( $row->author_avatar_url ) . '" alt="">';
		} else {
			echo '<span>' . esc_html( strtoupper( $initial ) ) . '</span>';
		}
		echo '</div><div class="mdo-store-review-body"><div class="mdo-store-review-head"><strong>' . esc_html( $name ) . '</strong>' . self::source_badge( $row ) . '</div><div class="mdo-store-review-meta">' . wp_kses_post( self::stars( (int) $row->rating ) ) . '<span>' . esc_html( self::format_date( $row->review_date ) ) . '</span></div>';
		if ( $row->review_title ) {
			echo '<h4>' . esc_html( $row->review_title ) . '</h4>';
		}
		echo '<div class="mdo-store-review-text">' . wpautop( wp_kses_post( $row->review_text ) ) . '</div>';
		echo '</div></article>';
	}

	private static function source_badge( $row ): string {
		$label = self::source_label( (string) $row->source );
		$badge = '<span class="mdo-source-badge">' . esc_html( $label ) . '</span>';
		if ( in_array( (string) $row->source, array( 'google', 'trustpilot' ), true ) && ! empty( $row->source_url ) ) {
			return '<a class="mdo-source-link" href="' . esc_url( $row->source_url ) . '" target="_blank" rel="noopener noreferrer" aria-label="Ver reseña original en ' . esc_attr( $label ) . '">' . $badge . '</a>';
		}
		return $badge;
	}

	private static function source_label( string $source ): string {
		$labels = array(
			'emdo' => 'EMDO',
			'google' => 'Google',
			'trustpilot' => 'Trustpilot',
		);
		return $labels[ $source ] ?? ucfirst( str_replace( '_', ' ', $source ) );
	}

	private static function stars( int $rating ): string {
		$rating = max( 0, min( 5, $rating ) );
		$out = '<span class="mdo-stars" aria-label="' . esc_attr( sprintf( '%d de 5 estrellas', $rating ) ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$out .= '<span class="' . ( $i <= $rating ? 'is-filled' : '' ) . '" aria-hidden="true">★</span>';
		}
		return $out . '</span>';
	}

	private static function format_date( $date ): string {
		if ( ! $date ) {
			return 'Sin fecha';
		}
		$timestamp = strtotime( (string) $date );
		return $timestamp ? wp_date( 'd/m/Y', $timestamp ) : 'Sin fecha';
	}

	private static function render_summary( float $average, int $total ): void {
		$average = max( 0.0, min( 5.0, $average ) );
		$filled = ( $average / 5 ) * 100;
		$average_label = number_format_i18n( $average, 1 );
		$rating_label = sprintf( __( '%s sobre 5', 'mdo-supplier-sync' ), $average_label );
		$count_label = sprintf( _n( '%s reseña', '%s reseñas', $total, 'mdo-supplier-sync' ), number_format_i18n( $total ) );
		echo '<section class="mdo-review-summary" aria-label="' . esc_attr( $rating_label . ', ' . $count_label ) . '">';
		echo '<div class="mdo-review-summary-score">' . esc_html( $average_label ) . '</div><div class="mdo-review-summary-body"><div class="mdo-review-summary-stars" aria-hidden="true"><span class="mdo-review-summary-stars-base">★★★★★</span><span class="mdo-review-summary-stars-fill" style="width:' . esc_attr( number_format( $filled, 2, '.', '' ) ) . '%">★★★★★</span></div><div class="mdo-review-summary-rating"><strong>' . esc_html( $rating_label ) . '</strong></div><div class="mdo-review-summary-count">' . esc_html( $count_label ) . '</div></div></section>';
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
