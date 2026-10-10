<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Muestra las reseñas externas EMDO en la ficha del producto al que
 * fueron asignadas y validadas. No convierte reseñas de tienda en reseñas
 * de cualquier artículo de ese productor.
 */
final class MDO_Reviews_Product {
	private const LIMIT = 30;

	public static function init(): void {
		add_filter( 'woocommerce_product_tabs', array( __CLASS__, 'tabs' ), 90 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ), 31 );
	}

	public static function assets(): void {
		if ( function_exists( 'is_product' ) && is_product() ) {
			wp_enqueue_style( 'mdo-reviews', MDO_SUPPLIER_SYNC_URL . 'assets/reviews.css', array(), MDO_SUPPLIER_SYNC_VERSION );
		}
	}

	public static function tabs( array $tabs ): array {
		if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) {
			return $tabs;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return $tabs;
		}

		$count = self::count( (int) $product->get_id() );
		$has_native_tab = isset( $tabs['reviews'] );
		// Conservar la pestaña nativa. Sin opiniones externas, no duplicarla.
		if ( $has_native_tab && 0 === $count ) {
			return $tabs;
		}
		$english = self::is_english();
		$title = $has_native_tab
			? ( $english ? 'External reviews' : 'Reseñas externas' )
			: ( $english ? 'Reviews' : 'Reseñas' );
		if ( $count > 0 ) {
			$title .= ' (' . number_format_i18n( $count ) . ')';
		}
		$tabs['mdo_product_reviews'] = array(
			'title'    => $title,
			'priority' => 55,
			'callback' => array( __CLASS__, 'render' ),
		);
		return $tabs;
	}

	private static function count( int $product_id ): int {
		if ( $product_id < 1 || ! class_exists( 'MDO_Database' ) ) {
			return 0;
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE wc_product_id=%d AND status='validated' AND source IN ('google','trustpilot') AND rating BETWEEN 1 AND 5",
				$product_id
			)
		);
	}

	public static function render(): void {
		if ( ! function_exists( 'wc_get_product' ) ) {
			return;
		}
		$product = wc_get_product( get_queried_object_id() );
		if ( ! $product ) {
			return;
		}
		$product_id = (int) $product->get_id();
		$english = self::is_english();
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$reviews = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT author_name,review_text,review_date,rating,source,source_url FROM {$table} WHERE wc_product_id=%d AND status='validated' AND source IN ('google','trustpilot') AND rating BETWEEN 1 AND 5 ORDER BY (review_date IS NULL),review_date DESC,id DESC LIMIT %d",
				$product_id,
				self::LIMIT
			)
		);

		echo '<section class="mdo-product-external-reviews mdo-reviews-page">';
		echo '<h2>' . esc_html( $english ? 'Reviews about this product' : 'Reseñas sobre este producto' ) . '</h2>';
		if ( empty( $reviews ) ) {
			echo '<p class="mdo-reviews-empty">' . esc_html( $english ? 'There are no published reviews specifically attributed to this product yet.' : 'Todavía no hay reseñas publicadas atribuidas específicamente a este producto.' ) . '</p>';
		} else {
			echo '<div class="mdo-store-reviews">';
			foreach ( $reviews as $review ) {
				$source = 'google' === $review->source ? 'Google' : 'Trustpilot';
				$name = trim( (string) $review->author_name );
				$name = '' !== $name ? $name : ( $english ? 'Customer' : 'Cliente' );
				$rating = max( 1, min( 5, (int) $review->rating ) );
				$date = $review->review_date ? strtotime( (string) $review->review_date ) : false;
				echo '<article class="mdo-store-review">';
				echo '<div class="mdo-store-review-body">';
				echo '<div class="mdo-store-review-head"><strong>' . esc_html( $name ) . '</strong>';
				if ( $review->source_url ) {
					echo '<a class="mdo-source-link" href="' . esc_url( $review->source_url ) . '" target="_blank" rel="noopener noreferrer"><span class="mdo-source-badge">' . esc_html( $source ) . '</span></a>';
				} else {
					echo '<span class="mdo-source-badge">' . esc_html( $source ) . '</span>';
				}
				echo '</div><div class="mdo-store-review-meta"><span class="mdo-stars" aria-label="' . esc_attr( $rating . ' / 5' ) . '">' . esc_html( str_repeat( '★', $rating ) . str_repeat( '☆', 5 - $rating ) ) . '</span>';
				if ( $date ) {
					echo '<span>' . esc_html( wp_date( 'd/m/Y', $date ) ) . '</span>';
				}
				echo '</div><div class="mdo-store-review-text">' . wpautop( wp_kses_post( (string) $review->review_text ) ) . '</div></div></article>';
			}
			echo '</div>';
		}
		echo '</section>';
	}

	private static function is_english(): bool {
		if ( function_exists( 'mdo_island_en_request' ) ) {
			return (bool) mdo_island_en_request();
		}
		$path = (string) wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		return (bool) preg_match( '~^/en(?:/|$)~i', $path );
	}
}
