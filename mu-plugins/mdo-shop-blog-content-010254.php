<?php
/**
 * Plugin Name: MDO - Shop and blog content 0.10.256
 * Description: Removes redundant shop/blog introduction labels and guarantees the Jamón Ibérico embutidos product grid.
 * Version: 0.10.256
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repair the historic Jamón Ibérico shortcode before WooCommerce expands it.
 * Production uses the canonical category slug `embutidos-y-curados`.
 *
 * This is kept as an early optimisation. A late rendered-HTML safeguard below
 * is authoritative because some page-builder/content filters can restore the
 * original post content before `do_shortcode` runs.
 */
function mdo_fix_jamon_embutidos_shortcode_010254( string $content ): string {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return $content;
	}

	if ( 'jamon-iberico' !== (string) get_post_field( 'post_name', get_queried_object_id() ) ) {
		return $content;
	}

	$replacements = array(
		'category="embutidos"' => 'category="embutidos-y-curados"',
		"category='embutidos'" => "category='embutidos-y-curados'",
	);

	return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
}
add_filter( 'the_content', 'mdo_fix_jamon_embutidos_shortcode_010254', 8 );

/**
 * Remove the redundant legacy heading INTRODUCCIÓN while keeping the actual
 * introductory copy. This applies consistently to old blog entries.
 */
function mdo_remove_legacy_introduction_heading_010254( string $content ): string {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return $content;
	}

	$cleaned = preg_replace(
		'/<h([1-6])\b[^>]*>\s*(?:<[^>]+>\s*)*INTRODUCCI(?:Ó|O)N\s*(?:<\/[^>]+>\s*)*<\/h\1>/iu',
		'',
		$content
	);

	return is_string( $cleaned ) ? $cleaned : $content;
}
add_filter( 'the_content', 'mdo_remove_legacy_introduction_heading_010254', PHP_INT_MAX );

/**
 * The blog cards use get_the_excerpt(), which is generated before the singular
 * `the_content` cleanup above. If a post really contains the old standalone
 * INTRODUCCIÓN heading, remove only that leading label from its card excerpt.
 * The actual introductory paragraph is preserved unchanged.
 *
 * @param string  $excerpt Generated or manual excerpt.
 * @param WP_Post $post    Current post object.
 */
function mdo_clean_legacy_introduction_excerpt_010256( string $excerpt, $post ): string {
	if ( is_admin() || ! $post instanceof WP_Post || 'post' !== $post->post_type ) {
		return $excerpt;
	}

	$has_legacy_heading = 1 === preg_match(
		'/<h([1-6])\b[^>]*>\s*(?:<[^>]+>\s*)*INTRODUCCI(?:Ó|O)N\s*(?:<\/[^>]+>\s*)*<\/h\1>/iu',
		(string) $post->post_content
	);

	if ( ! $has_legacy_heading ) {
		return $excerpt;
	}

	$cleaned = preg_replace(
		'/^\s*INTRODUCCI(?:Ó|O)N(?:\s|&nbsp;|&#160;|&#x0*A0;|:|\-|–|—)+/iu',
		'',
		$excerpt,
		1
	);

	return is_string( $cleaned ) ? trim( $cleaned ) : $excerpt;
}
add_filter( 'get_the_excerpt', 'mdo_clean_legacy_introduction_excerpt_010256', PHP_INT_MAX, 2 );

/**
 * Guarantee a real WooCommerce product grid immediately after the Embutidos
 * heading in the Jamón Ibérico article.
 *
 * Production diagnostics confirmed that the canonical category contains
 * published products and that this exact shortcode renders the normal shop
 * cards. We therefore inspect only the section between the Embutidos heading
 * and the next heading. If that section has no real product <li>, we render a
 * fresh canonical loop and inject it directly after the heading.
 */
function mdo_guarantee_jamon_embutidos_products_010255( string $content ): string {
	if ( is_admin() || ! is_singular( 'post' ) ) {
		return $content;
	}

	if ( 'jamon-iberico' !== (string) get_post_field( 'post_name', get_queried_object_id() ) ) {
		return $content;
	}

	$heading_match = array();
	$has_heading   = 1 === preg_match(
		'/<h([1-6])\b[^>]*>(?:(?!<\/h\1>)[\s\S])*?embutidos(?:(?!<\/h\1>)[\s\S])*?<\/h\1>/iu',
		$content,
		$heading_match,
		PREG_OFFSET_CAPTURE
	);

	if ( ! $has_heading ) {
		return $content;
	}

	$heading_html  = (string) $heading_match[0][0];
	$heading_start = (int) $heading_match[0][1];
	$heading_end   = $heading_start + strlen( $heading_html );
	$tail          = substr( $content, $heading_end );

	if ( ! is_string( $tail ) ) {
		return $content;
	}

	$next_heading_offset = false;
	if ( preg_match( '/<h[1-6]\b[^>]*>/iu', $tail, $next_heading_match, PREG_OFFSET_CAPTURE ) ) {
		$next_heading_offset = (int) $next_heading_match[0][1];
	}

	$section_html = false === $next_heading_offset ? $tail : substr( $tail, 0, $next_heading_offset );

	$has_real_products = is_string( $section_html ) && 1 === preg_match(
		'/<li\b[^>]*class=(?:"[^"]*\bproduct\b[^"]*"|\'[^\']*\bproduct\b[^\']*\')[^>]*>/iu',
		$section_html
	);

	if ( $has_real_products || ! shortcode_exists( 'products' ) ) {
		return $content;
	}

	$products_html = do_shortcode(
		'[products category="embutidos-y-curados" limit="8" columns="4" orderby="popularity" order="DESC"]'
	);

	$rendered_cards = preg_match_all(
		'/<li\b[^>]*class=(?:"[^"]*\bproduct\b[^"]*"|\'[^\']*\bproduct\b[^\']*\')[^>]*>/iu',
		$products_html
	);

	if ( false === $rendered_cards || $rendered_cards < 1 ) {
		return $content;
	}

	/* Remove a possible legacy “no products” notice from the obsolete shortcode. */
	$tail = preg_replace(
		'/<p\b[^>]*class=(?:"[^"]*\bwoocommerce-info\b[^"]*"|\'[^\']*\bwoocommerce-info\b[^\']*\')[^>]*>.*?<\/p>/isu',
		'',
		$tail,
		1
	);
	if ( ! is_string( $tail ) ) {
		$tail = substr( $content, $heading_end );
	}

	$block = '<div class="emo-entry-product-section emo-jamon-embutidos-runtime" data-emo-embutidos="010255">' . $products_html . '</div>';

	return substr( $content, 0, $heading_end ) . $block . $tail;
}
add_filter( 'the_content', 'mdo_guarantee_jamon_embutidos_products_010255', PHP_INT_MAX );

/**
 * The store already has title, filters and catalogue context. The historical
 * `.emo-shop-lead` duplicates that information, so it is removed on the main
 * shop and product-category views (including filtered shop requests).
 */
function mdo_is_catalog_context_010254(): bool {
	return ( function_exists( 'is_shop' ) && is_shop() )
		|| ( function_exists( 'is_product_category' ) && is_product_category() );
}

function mdo_hide_catalog_lead_010254(): void {
	if ( is_admin() || ! mdo_is_catalog_context_010254() ) {
		return;
	}
	?>
	<style id="mdo-hide-catalog-lead-010254">
		body.woocommerce-shop .emo-shop-lead,
		body.tax-product_cat .emo-shop-lead {
			display: none !important;
		}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_hide_catalog_lead_010254', PHP_INT_MAX );

/**
 * Final DOM safety net: older theme layers can rewrite the lead late in the
 * page lifecycle. Removing the node prevents that copy from resurfacing.
 */
function mdo_remove_catalog_lead_runtime_010254(): void {
	if ( is_admin() || ! mdo_is_catalog_context_010254() ) {
		return;
	}
	?>
	<script id="mdo-remove-catalog-lead-010254">
	(() => {
		'use strict';
		const removeLead = () => {
			document.querySelectorAll('.emo-shop-lead').forEach((lead) => lead.remove());
		};
		removeLead();
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', removeLead, {once: true});
		}
		window.setTimeout(removeLead, 250);
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'mdo_remove_catalog_lead_runtime_010254', PHP_INT_MAX );
