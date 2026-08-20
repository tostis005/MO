<?php
/**
 * Plugin Name: MDO EMDO Selection Badge
 * Description: Shows a discreet EMDO Selection badge on catalogue cards for products with maximum editorial priority.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_EMDO_Selection_Badge {
	private const MAX_PRIORITY = 100;

	public static function init(): void {
		add_action( 'woocommerce_before_shop_loop_item_title', array( __CLASS__, 'render_badge' ), 8 );
		add_action( 'wp_head', array( __CLASS__, 'render_styles' ), 90 );
	}

	public static function render_badge(): void {
		global $product;

		if ( ! $product instanceof WC_Product || ! class_exists( 'MDO_Catalog_Ranking' ) ) {
			return;
		}

		$product_id = absint( $product->get_id() );
		if ( ! $product_id || self::MAX_PRIORITY !== MDO_Catalog_Ranking::get_priority( $product_id ) ) {
			return;
		}

		printf(
			'<span class="mdo-emdo-selection-badge" aria-label="%1$s">%1$s</span>',
			esc_attr( self::text( 'Selección EMDO', 'EMDO Selection' ) )
		);
	}

	public static function render_styles(): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="mdo-emdo-selection-badge-css">
			.woocommerce ul.products li.product,.woocommerce-page ul.products li.product{position:relative;}
			.mdo-emdo-selection-badge{position:absolute;top:12px;left:12px;z-index:8;display:inline-flex;align-items:center;width:auto;max-width:calc(100% - 24px);box-sizing:border-box;padding:5px 9px;border:1px solid rgba(255,255,255,.34);border-radius:999px;background:rgba(23,63,50,.92);color:#fff;font-size:10.5px;font-weight:700;line-height:1.15;letter-spacing:.015em;white-space:nowrap;box-shadow:0 1px 5px rgba(13,38,30,.13);pointer-events:none;-webkit-backdrop-filter:blur(3px);backdrop-filter:blur(3px);}
			@media (max-width:600px){.mdo-emdo-selection-badge{top:9px;left:9px;max-width:calc(100% - 18px);padding:4px 7px;font-size:9.5px;}}
		</style>
		<?php
	}

	private static function text( string $es, string $en ): string {
		if ( function_exists( 'mdo_sst_is_english' ) && mdo_sst_is_english() ) {
			return $en;
		}
		if ( function_exists( 'mdo_en_is_request' ) && mdo_en_is_request() ) {
			return $en;
		}
		$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : '';
		return ( '/en' === $path || 0 === strpos( $path, '/en/' ) ) ? $en : $es;
	}
}

MDO_EMDO_Selection_Badge::init();
