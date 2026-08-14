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
 * Replace the final categories section with the visibility-aware renderer and
 * inject producer cover CSS after every other Home transformation has run.
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
	 * belong to disabled/offline WCFM stores. Re-running the renderer here makes
	 * that user-specific order the last word in the generated Home HTML.
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
