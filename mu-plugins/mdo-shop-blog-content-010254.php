<?php
/**
 * Plugin Name: MDO - Shop and blog content 0.10.254
 * Description: Removes the redundant shop lead, cleans legacy blog intro headings and repairs the Jamón Ibérico embutidos shortcode.
 * Version: 0.10.254
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repair the historic Jamón Ibérico shortcode before WooCommerce expands it.
 * Production uses the canonical category slug `embutidos-y-curados`.
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
