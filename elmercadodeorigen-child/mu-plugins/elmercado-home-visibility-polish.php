<?php
/**
 * Final Home visibility and producer-image polish.
 *
 * This MU layer runs as the outermost Home output buffer so the category block
 * that reaches the visitor is always rebuilt with the catalogue counts for the
 * current user scope. Public/non-admin visitors therefore sort by products they
 * can actually see, while administrators retain their broader management view.
 *
 * It also forces producer banners in the Home hero to fill each card using a
 * non-distorting cover crop.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether this request is the public front page.
 */
function elmercado_home_visibility_polish_is_front_010245(): bool {
	return ! is_admin() && is_front_page() && ! is_feed() && ! is_trackback() && ! wp_doing_ajax();
}

/**
 * Sort the category cards that are actually going to the browser by the product
 * count printed for the current visitor. This is deliberately a final-output
 * operation: an anonymous customer is sorted by anonymous/public counts, a
 * logged non-admin by that same public visibility policy, and an administrator
 * by the broader counts that are printed for administrators.
 */
function elmercado_home_sort_category_cards_010245( string $html ): string {
	$start = strpos( $html, '<section class="emo-section emo-categories"' );
	$end   = false !== $start ? strpos( $html, '</section>', $start ) : false;
	if ( false === $start || false === $end ) {
		return $html;
	}

	$end    += strlen( '</section>' );
	$section = substr( $html, $start, $end - $start );
	if ( ! is_string( $section ) || '' === $section ) {
		return $html;
	}

	$matched = preg_match_all(
		'~<a\s+class="[^"]*\bemo-category-card\b[^"]*"[^>]*>.*?</a>~si',
		$section,
		$cards,
		PREG_OFFSET_CAPTURE
	);
	if ( ! is_int( $matched ) || $matched < 2 || empty( $cards[0] ) ) {
		return $html;
	}

	$items = array();
	foreach ( $cards[0] as $index => $match ) {
		$card   = isset( $match[0] ) ? (string) $match[0] : '';
		$offset = isset( $match[1] ) ? (int) $match[1] : 0;
		$text   = html_entity_decode( wp_strip_all_tags( $card ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$count  = -1;

		if ( preg_match( '/([0-9][0-9.,]*)\s+productos?/iu', $text, $number ) ) {
			$digits = preg_replace( '/[^0-9]/', '', (string) $number[1] );
			$count  = is_string( $digits ) && '' !== $digits ? (int) $digits : 0;
		}

		$items[] = array(
			'html'   => $card,
			'count'  => $count,
			'index'  => (int) $index,
			'offset' => $offset,
		);
	}

	usort(
		$items,
		static function ( array $left, array $right ): int {
			$by_count = (int) $right['count'] <=> (int) $left['count'];
			return 0 !== $by_count ? $by_count : (int) $left['index'] <=> (int) $right['index'];
		}
	);

	$first      = $cards[0][0];
	$last       = $cards[0][ count( $cards[0] ) - 1 ];
	$first_pos  = (int) $first[1];
	$last_end   = (int) $last[1] + strlen( (string) $last[0] );
	$sorted_html = implode( '', array_column( $items, 'html' ) );
	$section     = substr_replace( $section, $sorted_html, $first_pos, $last_end - $first_pos );

	if ( ! str_contains( $section, 'data-emo-category-order-final="010245"' ) ) {
		$section = preg_replace(
			'~<section class="emo-section emo-categories"~',
			'<section class="emo-section emo-categories" data-emo-category-order-final="010245"',
			$section,
			1
		) ?: $section;
	}

	return substr_replace( $html, $section, $start, $end - $start );
}

/**
 * Replace the final categories section with the visibility-aware renderer,
 * enforce the card order visible in the final HTML and inject producer cover
 * CSS after every other Home transformation has run.
 */
function elmercado_home_visibility_polish_output_010245( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	/*
	 * 0.10.226 obtains category counts from
	 * elmercado_catalog_visible_category_counts_010217(). That function already
	 * distinguishes administrators from every other visitor and excludes, for
	 * the public scope, products that are not catalog-visible, out of stock or
	 * belong to disabled/offline WCFM stores.
	 */
	if ( function_exists( 'elmercado_home_category_output_html_010226' ) ) {
		$categories = (string) elmercado_home_category_output_html_010226();
		if ( '' !== $categories ) {
			$start = strpos( $html, '<section class="emo-section emo-categories"' );
			$end   = false !== $start ? strpos( $html, '</section>', $start ) : false;
			if ( false !== $start && false !== $end ) {
				$end  += strlen( '</section>' );
				$html = substr_replace( $html, $categories, $start, $end - $start );
			}
		}
	}

	$html = elmercado_home_sort_category_cards_010245( $html );

	if ( ! str_contains( $html, 'id="elmercado-home-visibility-polish-010245"' ) ) {
		$style = <<<'HTML'
<style id="elmercado-home-visibility-polish-010245">
body.home .emo-hero__visual--vendors .emo-hero-card,
body.home .emo-hero__visual--vendors .emo-hero-card figure {
	overflow: hidden !important;
}
body.home .emo-hero__visual--vendors .emo-hero-card figure {
	position: relative !important;
	width: 100% !important;
	height: 100% !important;
	margin: 0 !important;
}
body.home .emo-hero__visual--vendors .emo-hero-card figure > img {
	position: absolute !important;
	inset: 0 !important;
	display: block !important;
	width: 100% !important;
	height: 100% !important;
	max-width: none !important;
	max-height: none !important;
	object-fit: cover !important;
	object-position: center center !important;
}
</style>
HTML;
		$head_end = strpos( $html, '</head>' );
		if ( false !== $head_end ) {
			$html = substr_replace( $html, $style, $head_end, 0 );
		}
	}

	return $html;
}

/*
 * Start before the existing Home buffers so this one is outermost and its
 * callback is applied last when PHP flushes nested output buffers.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( elmercado_home_visibility_polish_is_front_010245() ) {
			ob_start( 'elmercado_home_visibility_polish_output_010245' );
		}
	},
	-12000
);
