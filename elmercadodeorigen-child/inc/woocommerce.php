<?php
/**
 * Integración visual con WooCommerce.
 *
 * Evitamos sobrescribir plantillas mientras los hooks y CSS sean suficientes,
 * reduciendo así el mantenimiento al actualizar WooCommerce.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'after_setup_theme',
	function (): void {
		add_theme_support(
			'woocommerce',
			array(
				'thumbnail_image_width' => 720,
				'single_image_width'    => 1100,
				'product_grid'          => array(
					'default_rows'    => 4,
					'min_rows'        => 1,
					'max_rows'        => 8,
					'default_columns' => 4,
					'min_columns'     => 1,
					'max_columns'     => 4,
				),
			)
		);
		add_theme_support( 'wc-product-gallery-zoom' );
		add_theme_support( 'wc-product-gallery-lightbox' );
		add_theme_support( 'wc-product-gallery-slider' );
	}
);

add_filter(
	'loop_shop_columns',
	static function (): int {
		return 4;
	},
	20
);

add_filter(
	'loop_shop_per_page',
	static function (): int {
		return 16;
	},
	20
);

add_filter(
	'woocommerce_sale_flash',
	static function (): string {
		return '<span class="onsale">' . esc_html__( 'Oferta', 'elmercadodeorigen' ) . '</span>';
	}
);

/**
 * Texto de botón más claro para productos simples.
 */
add_filter(
	'woocommerce_product_add_to_cart_text',
	static function ( string $text, WC_Product $product ): string {
		if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
			return esc_html__( 'Añadir al carrito', 'elmercadodeorigen' );
		}

		return $text;
	},
	20,
	2
);
