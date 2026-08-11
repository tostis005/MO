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
				'single_image_width'    => 1200,
				'product_grid'          => array(
					'default_rows'    => 4,
					'min_rows'        => 1,
					'max_rows'        => 8,
					'default_columns' => 3,
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
		return 3;
	},
	20
);

add_filter(
	'loop_shop_per_page',
	static function (): int {
		return 15;
	},
	20
);

/**
 * La rebaja queda visible en el precio; evitamos pegatinas superpuestas.
 */
add_filter(
	'woocommerce_sale_flash',
	static function (): string {
		return '';
	},
	30
);

/**
 * El formulario de consulta de WCFM no se ofrece en la ficha de producto.
 * Se corta en el filtro del plugin antes de que el botón llegue al HTML.
 *
 * @param bool $allow Estado original del módulo de consultas.
 */
add_filter(
	'wcfm_is_allow_enquiry',
	static function ( $allow ): bool {
		if ( ! is_admin() && function_exists( 'is_product' ) && is_product() ) {
			return false;
		}

		return (bool) $allow;
	},
	PHP_INT_MAX
);

/**
 * Seis productos relacionados en una rejilla responsiva compacta.
 *
 * El número de columnas visuales se resuelve en CSS para poder pasar de
 * 6 → 3 → 2 → 1 según el ancho disponible sin afectar otros listados.
 *
 * @param array<string, mixed> $args Argumentos de la consulta.
 * @return array<string, mixed>
 */
add_filter(
	'woocommerce_output_related_products_args',
	static function ( array $args ): array {
		$args['posts_per_page'] = 6;
		$args['columns']        = 6;

		return $args;
	},
	30
);

add_filter(
	'woocommerce_upsells_columns',
	static function (): int {
		return 3;
	},
	30
);

add_filter(
	'woocommerce_upsells_total',
	static function (): int {
		return 3;
	},
	30
);

add_filter(
	'woocommerce_cross_sells_columns',
	static function (): int {
		return 3;
	},
	30
);

add_filter(
	'woocommerce_cross_sells_total',
	static function (): int {
		return 3;
	},
	30
);

/**
 * En la selección comercial de la portada mostramos los seis superventas que
 * se pueden comprar ahora. La consulta se mantiene dinámica y no requiere
 * seleccionar productos manualmente ni guardar ajustes de diseño en la BBDD.
 *
 * @param array<string, mixed> $query_args Argumentos de WP_Query.
 * @param array<string, mixed> $attributes Atributos normalizados del shortcode.
 * @return array<string, mixed>
 */
add_filter(
	'woocommerce_shortcode_products_query',
	static function ( array $query_args, array $attributes ): array {
		if ( ! is_front_page() || empty( $attributes['ids'] ) || 6 !== (int) ( $attributes['limit'] ?? 0 ) ) {
			return $query_args;
		}

		$query_args['posts_per_page'] = 6;
		$query_args['meta_key']       = 'total_sales';
		$query_args['orderby']        = 'meta_value_num';
		$query_args['order']          = 'DESC';
		$query_args['post__in']       = array();

		if ( ! isset( $query_args['meta_query'] ) || ! is_array( $query_args['meta_query'] ) ) {
			$query_args['meta_query'] = array();
		}

		$query_args['meta_query'][] = array(
			'key'     => '_stock_status',
			'value'   => 'instock',
			'compare' => '=',
		);

		return $query_args;
	},
	30,
	2
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
