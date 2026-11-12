<?php
/**
 * Pedido minimo de cinco sobres Montjam para lomo, chorizo y salchichon.
 *
 * No afecta a piezas enteras, jamon/paleta loncheados ni otros productores.
 * Las cantidades validas son 5, 6, 7... (pasos de una unidad).
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The same module may be loaded as an MU plugin in production and by the child theme.
// Whichever copy loads first registers the hooks exactly once.
if ( function_exists( 'elmercado_is_montjam_sliced_minimum_20261111' ) ) {
	return;
}

/**
 * Identify only the sliced Montjam embutidos, never the whole pieces.
 *
 * Chorizo and lomo use their verified public slugs. The salchichon
 * loncheado is not yet listed publicly, so match only its sliced/sachet
 * presentation when the product exists (including a 90 g format).
 *
 * @param WC_Product|int $product Product object or ID.
 */
function elmercado_is_montjam_sliced_minimum_20261111( $product ): bool {
	if ( is_numeric( $product ) ) {
		$product = wc_get_product( (int) $product );
	}
	if ( ! $product instanceof WC_Product ) {
		return false;
	}
	if ( $product->is_type( 'variation' ) ) {
		$product = wc_get_product( $product->get_parent_id() );
	}
	if ( ! $product instanceof WC_Product || ! $product->is_type( 'simple' ) ) {
		return false;
	}

	$slug = $product->get_slug();
	if ( in_array(
		$slug,
		array(
			'lomo-bellota-100-iberico-loncheado-montjam-90g',
			'chorizo-cular-iberico-extra-loncheado-montjam-90g',
		),
		true
	) ) {
		return true;
	}

	// Future or unpublished salchichon: require maker, meat and sliced format.
	return str_contains( $slug, 'montjam' )
		&& str_contains( $slug, 'salchichon' )
		&& (
			str_contains( $slug, 'lonchead' )
			|| str_contains( $slug, 'sobre' )
			|| str_contains( $slug, '90g' )
			|| str_contains( $slug, '90-g' )
		);
}

/**
 * Total quantity of the same sliced product already in the cart.
 */
function elmercado_montjam_sliced_cart_quantity_20261111( int $product_id ): int {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0;
	}
	$quantity = 0;
	foreach ( WC()->cart->get_cart() as $item ) {
		if ( (int) ( $item['product_id'] ?? 0 ) === $product_id ) {
			$quantity += (int) ( $item['quantity'] ?? 0 );
		}
	}
	return $quantity;
}

/** Start at five on product pages and keep the step at one everywhere. */
add_filter(
	'woocommerce_quantity_input_args',
	static function ( array $args, $product ): array {
		if ( ! elmercado_is_montjam_sliced_minimum_20261111( $product ) ) {
			return $args;
		}
		$args['min_value'] = 5;
		$args['step']      = 1;
		if ( function_exists( 'is_product' ) && is_product() ) {
			$args['input_value'] = max( 5, (int) ( $args['input_value'] ?? 5 ) );
		}
		return $args;
	},
	100,
	2
);

/** WooCommerce templates and third-party quantity controls. */
add_filter(
	'woocommerce_quantity_input_min',
	static function ( $minimum, $product ) {
		return elmercado_is_montjam_sliced_minimum_20261111( $product ) ? 5 : $minimum;
	},
	100,
	2
);
add_filter(
	'woocommerce_quantity_input_step',
	static function ( $step, $product ) {
		return elmercado_is_montjam_sliced_minimum_20261111( $product ) ? 1 : $step;
	},
	100,
	2
);

/** Native catalogue add-to-cart links must initially add five, not one. */
add_filter(
	'woocommerce_loop_add_to_cart_args',
	static function ( array $args, $product ): array {
		if ( elmercado_is_montjam_sliced_minimum_20261111( $product ) ) {
			$args['quantity'] = 5;
		}
		return $args;
	},
	100,
	2
);

/**
 * Server-side protection for direct product, catalogue and AJAX purchases.
 * One more unit may be added when five or more are already in the cart.
 */
add_filter(
	'woocommerce_add_to_cart_validation',
	static function ( $valid, $product_id, $quantity, $variation_id = 0 ) {
		if ( ! $valid || ! elmercado_is_montjam_sliced_minimum_20261111( (int) $product_id ) ) {
			return $valid;
		}
		$after_add = elmercado_montjam_sliced_cart_quantity_20261111( (int) $product_id ) + (int) $quantity;
		if ( $after_add >= 5 ) {
			return $valid;
		}
		wc_add_notice( 'Para este producto de Montjam el pedido mínimo es de 5 sobres. Puedes pedir 5, 6, 7 o más.', 'error' );
		return false;
	},
	100,
	4
);

/** Prevent a shopper from editing an existing cart line down to 1–4. */
add_filter(
	'woocommerce_update_cart_validation',
	static function ( $valid, $cart_item_key, $values, $quantity ) {
		if ( ! $valid || 0 === (int) $quantity || ! isset( $values['data'] )
			|| ! elmercado_is_montjam_sliced_minimum_20261111( $values['data'] ) ) {
			return $valid;
		}
		if ( (int) $quantity >= 5 ) {
			return $valid;
		}
		wc_add_notice( 'El mínimo para este sobre Montjam es de 5 unidades. Para quitarlo, elimina el producto del carrito.', 'error' );
		return false;
	},
	100,
	4
);

/** Check older or restored sessions as well, before checkout. */
add_action(
	'woocommerce_check_cart_items',
	static function (): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( isset( $item['data'] ) && elmercado_is_montjam_sliced_minimum_20261111( $item['data'] )
				&& (int) ( $item['quantity'] ?? 0 ) < 5 ) {
				wc_add_notice( 'Para comprar los sobres Montjam de salchichón, chorizo o lomo debes seleccionar al menos 5 unidades de cada producto.', 'error' );
				break;
			}
		}
	},
	100
);

/** WooCommerce Cart/Checkout blocks and Store API (minimum 5, step 1). */
add_filter(
	'woocommerce_store_api_product_quantity_minimum',
	static function ( $minimum, $product, $cart_item = null ) {
		return elmercado_is_montjam_sliced_minimum_20261111( $product ) ? 5 : $minimum;
	},
	100,
	3
);
add_filter(
	'woocommerce_store_api_product_quantity_multiple_of',
	static function ( $multiple, $product, $cart_item = null ) {
		return elmercado_is_montjam_sliced_minimum_20261111( $product ) ? 1 : $multiple;
	},
	100,
	3
);

/** WooCommerce 11.2+ validation for Store API cart updates. */
add_filter(
	'woocommerce_store_api_cart_item_quantity_validation',
	static function ( $valid, $quantity, $product, $cart_item ) {
		if ( elmercado_is_montjam_sliced_minimum_20261111( $product ) && (int) $quantity < 5 ) {
			return new WP_Error( 'elmercado_montjam_minimum', 'El pedido mínimo es de 5 sobres Montjam por producto.' );
		}
		return $valid;
	},
	100,
	4
);

/** Explain the rule next to the selector on the three product pages. */
add_action(
	'woocommerce_before_add_to_cart_quantity',
	static function (): void {
		global $product;
		if ( $product instanceof WC_Product && elmercado_is_montjam_sliced_minimum_20261111( $product ) ) {
			echo '<p class="elmercado-montjam-minimum" style="margin:0 0 10px;font-size:14px;">Pedido mínimo: <strong>5 sobres</strong>. Después puedes elegir 6, 7, 8 o más.</p>';
		}
	},
	10
);
