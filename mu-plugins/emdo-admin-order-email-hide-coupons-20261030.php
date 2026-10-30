<?php
/**
 * Plugin Name: EMDO — ocultar Cupones en email interno de nuevo pedido
 * Description: Elimina solo la fila «Cupones» del correo administrativo new_order.
 * Version: 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Oculta la fila informativa de cupones, sin modificar el pedido ni los importes.
 *
 * @param array<string, mixed> $totals Filas preparadas para mostrar en el email.
 * @return array<string, mixed>
 */
function emdo_new_order_email_without_coupons_row_20261030( $totals ) {
	if ( ! is_array( $totals ) ) {
		return $totals;
	}

	foreach ( $totals as $key => $row ) {
		if ( ! is_array( $row ) ) {
			continue;
		}

		$label = isset( $row['label'] )
			? sanitize_title( html_entity_decode( wp_strip_all_tags( (string) $row['label'] ), ENT_QUOTES, 'UTF-8' ) )
			: '';

		if (
			in_array( (string) $key, array( 'coupon', 'coupons', 'cupones' ), true )
			|| in_array( $label, array( 'cupones', 'coupons', 'coupon' ), true )
		) {
			unset( $totals[ $key ] );
		}
	}

	return $totals;
}

/*
 * Activar el filtro solo durante la tabla del email de «Nuevo pedido» para
 * administración. Funciona tanto en HTML como en texto plano y nunca se
 * aplica a emails de clientes ni a la pantalla de totales del pedido.
 */
add_action(
	'woocommerce_email_before_order_table',
	static function ( $order, $sent_to_admin, $plain_text, $email ): void {
		if ( $sent_to_admin && $email instanceof WC_Email && 'new_order' === $email->id ) {
			add_filter( 'woocommerce_get_order_item_totals', 'emdo_new_order_email_without_coupons_row_20261030', PHP_INT_MAX );
		}
	},
	0,
	4
);

add_action(
	'woocommerce_email_after_order_table',
	static function ( $order, $sent_to_admin, $plain_text, $email ): void {
		if ( $sent_to_admin && $email instanceof WC_Email && 'new_order' === $email->id ) {
			remove_filter( 'woocommerce_get_order_item_totals', 'emdo_new_order_email_without_coupons_row_20261030', PHP_INT_MAX );
		}
	},
	PHP_INT_MAX,
	4
);
