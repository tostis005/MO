<?php
/**
 * Tarifas pactadas de Latitudes (por paquete exclusivo del vendedor).
 *
 * Hasta 199,99 €: 13,45 €; desde 200 €: envío gratis.
 * El umbral se calcula sobre subtotal después de descuento e impuestos de
 * productos del propio vendedor, no sobre el total de vendedores del carrito.
 *
 * No cambia los envíos de ningún otro productor y no altera los ajustes
 * guardados en WCFM/WooCommerce. Si WCFM no separa paquetes, se deja intacto
 * el paquete mixto por seguridad y debe corregirse la separación en el panel.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Obtiene el autor real del producto (padre para una variación). */
function elmercado_latitudes_shipping_product_vendor_20261015( $product ): int {
	if ( ! is_object( $product ) || ! is_callable( array( $product, 'get_id' ) ) ) {
		return 0;
	}
	$id = absint( $product->get_id() );
	if ( is_callable( array( $product, 'get_parent_id' ) ) && $product->get_parent_id() ) {
		$id = absint( $product->get_parent_id() );
	}
	return function_exists( 'wcfm_get_vendor_id_by_post' )
		? absint( wcfm_get_vendor_id_by_post( $id ) )
		: absint( get_post_field( 'post_author', $id ) );
}

/** Paquete exclusivo, comprobado por sus productos, no por un nombre. */
function elmercado_latitudes_is_shipping_package_20261015( array $package ): bool {
	$latitudes = function_exists( 'elmercado_latitudes_vendor_id_20261015' )
		? elmercado_latitudes_vendor_id_20261015() : 0;
	if ( $latitudes <= 0 || empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
		return false;
	}
	$has_latitudes = false;
	foreach ( $package['contents'] as $item ) {
		$vendor = isset( $item['data'] )
			? elmercado_latitudes_shipping_product_vendor_20261015( $item['data'] )
			: 0;
		if ( $vendor <= 0 || $vendor !== $latitudes ) {
			return false;
		}
		$has_latitudes = true;
	}
	return $has_latitudes;
}

/** Solo territorio servido: península española/portuguesa y Baleares. */
function elmercado_latitudes_shipping_destination_allowed_20261015( array $package ): bool {
	$dest    = isset( $package['destination'] ) && is_array( $package['destination'] )
		? $package['destination'] : array();
	$country = strtoupper( (string) ( $dest['country'] ?? '' ) );
	$postal  = preg_replace( '/\s+/', '', strtoupper( (string) ( $dest['postcode'] ?? '' ) ) );
	if ( 'ES' === $country ) {
		/* 35/38 Canarias, 51 Ceuta, 52 Melilla. Si falta CP, WooCommerce aún no lo conoce. */
		return '' === $postal || ! preg_match( '/^(35|38|51|52)\d{3}$/', $postal );
	}
	if ( 'PT' === $country ) {
		/* Los CP 9xxx corresponden a Madeira/Azores. */
		return '' === $postal || ! preg_match( '/^9\d{3}/', $postal );
	}
	return false;
}

/** Total final visible de las líneas del vendedor, con IVA y después de cupones. */
function elmercado_latitudes_shipping_items_total_20261015( array $package ): float {
	$total = 0.0;
	foreach ( (array) ( $package['contents'] ?? array() ) as $item ) {
		$total += (float) ( $item['line_total'] ?? 0 ) + (float) ( $item['line_tax'] ?? 0 );
	}
	return round( max( 0.0, $total ), wc_get_price_decimals() );
}

/**
 * Firma de paquete diferente: WooCommerce recalcula tasas aunque conserve
 * tasas antiguas en WC_Session. Solo se aplica a paquetes puros de Latitudes.
 */
add_filter(
	'woocommerce_cart_shipping_packages',
	static function ( $packages ) {
		if ( ! is_array( $packages ) ) {
			return $packages;
		}
		foreach ( $packages as &$package ) {
			if ( is_array( $package ) && elmercado_latitudes_is_shipping_package_20261015( $package ) ) {
				$package['elmercado_latitudes_shipping_version'] = '20261015-fixed-1345-200';
			}
		}
		unset( $package );
		return $packages;
	},
	PHP_INT_MAX
);

/** Deja un único método con el coste pactado para Latitudes. */
add_filter(
	'woocommerce_package_rates',
	static function ( $rates, $package ) {
		if ( ! is_array( $package ) || ! elmercado_latitudes_is_shipping_package_20261015( $package ) ) {
			return $rates;
		}
		if ( ! elmercado_latitudes_shipping_destination_allowed_20261015( $package ) ) {
			return array();
		}
		if ( ! class_exists( 'WC_Shipping_Rate' ) ) {
			return $rates;
		}
		$total = elmercado_latitudes_shipping_items_total_20261015( $package );
		$free  = $total >= 200.0;
		$gross = $free ? 0.0 : 13.45;
		$net   = $gross;
		$taxes = array();

		/* Conserva 13,45 € como coste final, incluso con impuestos de envío activados. */
		if ( ! $free && function_exists( 'wc_tax_enabled' ) && wc_tax_enabled()
			&& class_exists( 'WC_Tax' ) ) {
			$tax_rates = WC_Tax::get_shipping_tax_rates();
			if ( $tax_rates ) {
				$taxes = WC_Tax::calc_inclusive_tax( $gross, $tax_rates );
				$net   = max( 0.0, $gross - array_sum( $taxes ) );
			}
		}

		$existing = null;
		if ( is_array( $rates ) ) {
			foreach ( $rates as $rate ) {
				if ( $rate instanceof WC_Shipping_Rate
					&& ! in_array( $rate->get_method_id(), array( 'local_pickup', 'pickup_location' ), true ) ) {
					$existing = clone $rate;
					break;
				}
			}
		}
		$rate = $existing instanceof WC_Shipping_Rate ? $existing : new WC_Shipping_Rate();
		$id   = $free ? 'emo_latitudes_free_20261015' : 'emo_latitudes_flat_20261015';
		$rate->set_id( $id );
		$rate->set_label( $free ? 'Envío gratuito · Latitudes' : 'Envío Latitudes' );
		$rate->set_cost( wc_format_decimal( $net ) );
		$rate->set_taxes( $taxes );
		$rate->set_method_id( $free ? 'free_shipping' : 'flat_rate' );
		if ( is_callable( array( $rate, 'set_tax_status' ) ) ) {
			$rate->set_tax_status( $taxes ? 'taxable' : 'none' );
		}
		return array( $id => $rate );
	},
	PHP_INT_MAX,
	2
);
