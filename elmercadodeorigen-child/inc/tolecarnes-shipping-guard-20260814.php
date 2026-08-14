<?php
/**
 * Aísla los envíos de Tolecarnes (vendor 4507) de los métodos globales de
 * WooCommerce. Tolecarnes usa WCFM Shipping by Zone, por lo que únicamente
 * deben mostrarse las tarifas calculadas por ese método para su paquete.
 *
 * Esto evita que métodos globales antiguos (por ejemplo free_shipping de las
 * zonas Baleares o España peninsular) se apliquen accidentalmente al vendedor.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Determina si el paquete pertenece a Tolecarnes.
 *
 * @param array $package Paquete de envío de WooCommerce.
 * @return bool
 */
function elmercado_is_tolecarnes_shipping_package( array $package ): bool {
    $vendor_id = isset( $package['vendor_id'] ) ? absint( $package['vendor_id'] ) : 0;

    if ( 4507 === $vendor_id ) {
        return true;
    }

    if ( empty( $package['contents'] ) || ! is_array( $package['contents'] ) ) {
        return false;
    }

    $authors = array();

    foreach ( $package['contents'] as $item ) {
        $product_id = 0;

        if ( ! empty( $item['product_id'] ) ) {
            $product_id = absint( $item['product_id'] );
        } elseif ( ! empty( $item['data'] ) && is_object( $item['data'] ) && method_exists( $item['data'], 'get_id' ) ) {
            $product_id = absint( $item['data']->get_id() );
        }

        if ( ! $product_id ) {
            continue;
        }

        $authors[] = absint( get_post_field( 'post_author', $product_id ) );
    }

    $authors = array_values( array_unique( array_filter( $authors ) ) );

    return 1 === count( $authors ) && 4507 === (int) $authors[0];
}

add_filter(
    'woocommerce_package_rates',
    static function ( array $rates, array $package ): array {
        if ( ! elmercado_is_tolecarnes_shipping_package( $package ) ) {
            return $rates;
        }

        foreach ( $rates as $rate_id => $rate ) {
            $method_id = is_object( $rate ) && method_exists( $rate, 'get_method_id' )
                ? (string) $rate->get_method_id()
                : '';

            if ( 'wcfmmp_product_shipping_by_zone' !== $method_id ) {
                unset( $rates[ $rate_id ] );
            }
        }

        return $rates;
    },
    PHP_INT_MAX,
    2
);
