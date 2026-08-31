<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds one active featured Special to the refreshed homepage.
 *
 * This deliberately works on the main-query `the_content` value only. It does
 * not start an output buffer and never inspects or rewrites the full response.
 */
final class MDO_Home_Featured_Special {
	private const CATEGORY_MARKER = '<section class="emo-section emo-categories">';

	public static function init(): void {
		add_filter( 'the_content', array( __CLASS__, 'inject' ), 50 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ), 30 );
	}

	public static function enqueue_assets(): void {
		if ( is_admin() || ( ! is_front_page() && ! is_singular( 'post' ) ) ) {
			return;
		}

		wp_enqueue_style(
			'mdo-home-featured-special',
			MDO_SUPPLIER_SYNC_URL . 'assets/css/home-featured-special.css',
			array(),
			MDO_SUPPLIER_SYNC_VERSION
		);
	}

	public static function inject( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		/* Idempotency: never render the module twice. */
		if ( false !== strpos( $content, 'data-mdo-home-featured-special=' ) ) {
			return $content;
		}

		$marker_position = strpos( $content, self::CATEGORY_MARKER );
		if ( false === $marker_position ) {
			return $content;
		}

		$block = self::render();
		if ( '' === $block ) {
			return $content;
		}

		return substr( $content, 0, $marker_position ) . $block . substr( $content, $marker_position );
	}

	/**
	 * Render the exact featured Special block used on the homepage.
	 *
	 * Kept public so other editorial surfaces can reuse the same active-Special
	 * selection, bilingual copy and markup without duplicating business rules.
	 */
	public static function render(): string {
		if ( ! post_type_exists( 'mdo_promotion' ) || ! class_exists( 'MDO_Specials' ) || ! class_exists( 'MDO_Promotions' ) ) {
			return '';
		}

		$post_id = self::active_featured_special_id();
		if ( ! $post_id ) {
			return '';
		}

		$lang    = self::language();
		$copy    = MDO_Specials::copy( $post_id, $lang );
		$shared  = MDO_Specials::shared( $post_id );
		$title   = trim( (string) ( $copy['title'] ?? '' ) );
		$summary = trim( (string) ( $copy['summary'] ?? '' ) );
		$url     = MDO_Specials::permalink( $post_id, $lang );
		$image   = MDO_Specials::image_html(
			$post_id,
			'large',
			array(
				'alt'      => $title,
				'loading'  => 'lazy',
				'decoding' => 'async',
			)
		);

		if ( '' === $title || '' === $url ) {
			return '';
		}

		$is_en          = 'en' === $lang;
		$section_title  = $is_en ? 'NOW AT EL MERCADO DE ORIGEN' : 'AHORA EN EL MERCADO DE ORIGEN';
		$button_label   = $is_en ? 'View special' : 'Ver especial';
		$deadline_label = $is_en ? 'Available until' : 'Disponible hasta el';
		$end            = trim( (string) ( $shared['end'] ?? '' ) );
		$deadline       = $end ? MDO_Specials::format_date( $end, $lang ) : '';
		$media          = '' !== $image
			? '<a class="mdo-home-featured-special__media" href="' . esc_url( $url ) . '" aria-label="' . esc_attr( $title ) . '">' . $image . '</a>'
			: '';

		$html  = '<section class="emo-section mdo-home-featured-special" data-mdo-home-featured-special="' . (int) $post_id . '" aria-labelledby="mdo-home-featured-special-heading">';
		$html .= '<div class="emo-shell">';
		$html .= '<div class="mdo-home-featured-special__heading"><span id="mdo-home-featured-special-heading" class="emo-kicker">' . esc_html( $section_title ) . '</span></div>';
		$html .= '<article class="mdo-home-featured-special__card' . ( $media ? ' has-media' : '' ) . '">';
		$html .= $media;
		$html .= '<div class="mdo-home-featured-special__content">';
		$html .= '<h2><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h2>';
		if ( '' !== $summary ) {
			$html .= '<p class="mdo-home-featured-special__summary">' . esc_html( $summary ) . '</p>';
		}
		if ( '' !== $deadline ) {
			$html .= '<p class="mdo-home-featured-special__deadline"><span>' . esc_html( $deadline_label ) . '</span> <strong>' . esc_html( $deadline ) . '</strong></p>';
		}
		$html .= '<a class="emo-button emo-button--dark mdo-home-featured-special__button" href="' . esc_url( $url ) . '">' . esc_html( $button_label ) . '</a>';
		$html .= '</div></article></div></section>';

		return $html;
	}

	private static function active_featured_special_id(): int {
		$ids = get_posts(
			array(
				'post_type'      => 'mdo_promotion',
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'fields'         => 'ids',
				'orderby'        => array(
					'menu_order' => 'ASC',
					'date'       => 'DESC',
				),
				'meta_key'       => '_mdo_promo_featured_home',
				'meta_value'     => '1',
			)
		);

		foreach ( $ids as $id ) {
			$id = (int) $id;
			if ( $id && MDO_Promotions::is_active( $id ) ) {
				return $id;
			}
		}

		return 0;
	}

	private static function language(): string {
		if ( function_exists( 'elmercado_is_english_request_010245' ) ) {
			return elmercado_is_english_request_010245() ? 'en' : 'es';
		}

		$path = (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		return preg_match( '#^/en(?:/|$)#i', $path ) ? 'en' : 'es';
	}
}
