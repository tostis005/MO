<?php
/**
 * Club EMDO: saldo como aportación previa (tender), no como cupón ni descuento fiscal.
 *
 * Compatible exclusivamente con el checkout clásico de WooCommerce.
 * La adaptación WCFM, la pasarela y los documentos fiscales deben comprobarse
 * en staging antes de habilitarlo en producción.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club_Checkout {
	private static bool $admin_email = false;
	public static function init(): void {
		add_filter( 'woocommerce_calculated_total', array( __CLASS__, 'total_after_wallet' ), 99, 2 );
		add_action( 'woocommerce_checkout_update_order_review', array( __CLASS__, 'update_checkout' ), 5 );
		add_action( 'woocommerce_checkout_process', array( __CLASS__, 'checkout_process' ), 5 );
		add_action( 'woocommerce_review_order_before_payment', array( __CLASS__, 'wallet_form' ), 5 );
		add_action( 'woocommerce_before_checkout_form', array( __CLASS__, 'gift_form' ), 12 );
		add_action( 'wp_footer', array( __CLASS__, 'inline_script' ), 40 );
		add_action( 'woocommerce_checkout_create_order', array( __CLASS__, 'save_order_tender' ), 10, 2 );
		add_action( 'woocommerce_checkout_order_created', array( __CLASS__, 'reserve_wallet' ), 20 );
		add_action( 'woocommerce_order_status_cancelled', array( __CLASS__, 'release_wallet' ), 20 );
		add_action( 'woocommerce_order_status_failed', array( __CLASS__, 'schedule_failed_expiry' ), 20 );
		add_action( 'mdo_club_cancel_failed_order', array( __CLASS__, 'cancel_expired_failed_order' ), 10, 1 );
		add_action( 'woocommerce_email_before_order_table', array( __CLASS__, 'before_email' ), 1, 4 );
		add_action( 'woocommerce_email_after_order_table', array( __CLASS__, 'after_email' ), PHP_INT_MAX, 4 );
		add_filter( 'woocommerce_get_order_item_totals', array( __CLASS__, 'email_totals' ), 90, 3 );
		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'admin_notice' ) );
	}
	private static function active(): bool {
		return MDO_Club::enabled( 'checkout' ) && is_user_logged_in() && function_exists( 'WC' );
	}
	private static function cart_has_prepaid( $cart ): bool {
		if ( ! $cart ) { return false; }
		foreach ( $cart->get_cart() as $item ) {
			if ( ! empty( $item['mdo_club_kind'] ) ) { return true; }
		}
		return false;
	}
	private static function wallet_limit( int $total_cents ): int {
		return max( 0, min( $total_cents, MDO_Club_Ledger::balance( get_current_user_id() ) ) );
	}
	private static function requested( array $data ): void {
		if ( ! WC()->session ) { return; }
		$enabled = isset( $data['mdo_club_use'] ) && '1' === (string) $data['mdo_club_use'];
		$requested = isset( $data['mdo_club_amount'] ) ? MDO_Club_Ledger::cents( $data['mdo_club_amount'] ) : 0;
		WC()->session->set( 'mdo_club_requested_cents', $enabled ? max( 0, $requested ) : 0 );
		WC()->session->set( 'mdo_club_use_enabled', $enabled ? 'yes' : 'no' );
	}
	public static function update_checkout( string $post_data ): void {
		if ( ! self::active() || ! WC()->session ) { return; }
		$data = array();
		parse_str( $post_data, $data );
		self::requested( $data );
	}
	public static function checkout_process(): void {
		if ( ! self::active() || ! WC()->cart ) { return; }
		self::requested( wp_unslash( $_POST ) );
		WC()->cart->calculate_totals();
	}
	public static function total_after_wallet( $total, $cart ) {
		if ( ! self::active() || ! WC()->session || self::cart_has_prepaid( $cart ) ) { return $total; }
		$full = MDO_Club_Ledger::cents( $total );
		$limit = self::wallet_limit( $full );
		$requested = (int) WC()->session->get( 'mdo_club_requested_cents', 0 );
		$enabled = 'yes' === WC()->session->get( 'mdo_club_use_enabled', 'no' );
		$applied = $enabled ? min( $requested, $limit ) : 0;
		WC()->session->set( 'mdo_club_applied_cents', $applied );
		return (float) MDO_Club_Ledger::money( $full - $applied );
	}
	public static function wallet_form(): void {
		if ( ! self::active() || ! WC()->cart || self::cart_has_prepaid( WC()->cart ) || ! WC()->session ) { return; }
		$balance = MDO_Club_Ledger::balance( get_current_user_id() );
		if ( $balance <= 0 ) { return; }
		$applied = (int) WC()->session->get( 'mdo_club_applied_cents', 0 );
		$want = (int) WC()->session->get( 'mdo_club_requested_cents', 0 );
		$checked = 'yes' === WC()->session->get( 'mdo_club_use_enabled', 'no' );
		$max = $balance;
		?>
		<div class="mdo-club-checkout" style="padding:16px;margin:12px 0;background:#f6f0e4;border-radius:13px;color:#244634">
			<label for="mdo_club_use" style="display:flex;gap:9px;align-items:center;cursor:pointer">
				<input id="mdo_club_use" type="checkbox" name="mdo_club_use" value="1" <?php checked( $checked ); ?>>
				<strong>Usar saldo Club EMDO</strong> · <?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $balance ) ) ); ?> disponibles
			</label>
			<label for="mdo_club_amount" style="display:block;margin:10px 0 3px">Importe que quieres utilizar (€)</label>
			<input id="mdo_club_amount" type="number" name="mdo_club_amount" min="0" max="<?php echo esc_attr( MDO_Club_Ledger::money( $max ) ); ?>" step="0.01"
				value="<?php echo esc_attr( MDO_Club_Ledger::money( $want > 0 ? $want : min( $balance, MDO_Club_Ledger::cents( WC()->cart->get_total( 'edit' ) ) + $applied ) ) ); ?>"
				style="width:140px;max-width:100%">
			<?php if ( $applied ) : ?><p style="margin:9px 0 0">Saldo aplicado: <?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $applied ) ) ); ?>. El resto se cobrará con el método elegido.</p><?php endif; ?>
		</div>
		<?php
	}
	public static function gift_form(): void {
		if ( ! is_checkout() || is_order_received_page() || ! MDO_Club::enabled( 'prepaid' ) ) { return; }
		if ( ! is_user_logged_in() ) {
			echo '<p class="woocommerce-info">¿Tienes una tarjeta regalo Club EMDO? Inicia sesión para canjearla desde Mi monedero.</p>';
			return;
		}
		?>
		<div class="mdo-club-gift-checkout" style="padding:14px;margin:12px 0;background:#fff9ed;border-radius:12px">
			<strong>¿Tienes una tarjeta regalo Club EMDO?</strong>
			<p>Canjea aquí una o varias tarjetas. Sus importes se sumarán a tu monedero.</p>
			<form method="post" style="display:flex;flex-wrap:wrap;gap:10px;align-items:center">
				<?php wp_nonce_field( 'mdo_club_redeem' ); ?>
				<input type="hidden" name="mdo_club_form" value="redeem">
				<input type="text" name="gift_code" maxlength="64" placeholder="Código CLUB-…" required style="flex:1;min-width:190px">
				<button type="submit" class="button">Canjear tarjeta</button>
			</form>
		</div>
		<?php
	}
	public static function inline_script(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) { return; }
		?><script>
		(function(){
			if(!window.jQuery){return;}
			jQuery(document.body).on('change','#mdo_club_use,#mdo_club_amount',function(){
				jQuery(document.body).trigger('update_checkout');
			});
		})();
		</script><?php
	}
	public static function save_order_tender( $order, $data ): void {
		if ( ! self::active() || ! WC()->cart || ! WC()->session || self::cart_has_prepaid( WC()->cart ) ) { return; }
		$applied = (int) WC()->session->get( 'mdo_club_applied_cents', 0 );
		if ( $applied <= 0 ) { return; }
		$full = MDO_Club_Ledger::cents( $order->get_total() ) + $applied;
		$order->update_meta_data( '_mdo_club_wallet_cents', $applied );
		$order->update_meta_data( '_mdo_club_full_total_cents', $full );
		$order->update_meta_data( '_mdo_club_wallet_user_id', get_current_user_id() );
	}
	public static function reserve_wallet( $order ): void {
		if ( ! $order instanceof WC_Order ) { return; }
		$amount = (int) $order->get_meta( '_mdo_club_wallet_cents' );
		$user = (int) $order->get_meta( '_mdo_club_wallet_user_id' );
		if ( $amount <= 0 ) { return; }
		if ( ! MDO_Club_Ledger::change( $user, -$amount, 'spend:' . $order->get_id(), 'spend', $order->get_id() ) ) {
			throw new Exception( 'Tu monedero no tiene saldo suficiente. Actualiza el checkout y vuelve a intentarlo.' );
		}
		$order->update_meta_data( '_mdo_club_reserved', 'yes' );
		$order->save();
	}
	/**
	 * Mantener la reserva mientras se permite reintentar un pago fallido.
	 * Tras 24 h, si el pedido sigue fallido y sin pagar, se cancela y libera.
	 * Nunca liberar y permitir a la vez el reintento del mismo pedido.
	 */
	public static function schedule_failed_expiry( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || $order->is_paid() || $order->get_date_paid() || 'yes' !== $order->get_meta( '_mdo_club_reserved' ) ) { return; }
		if ( ! wp_next_scheduled( 'mdo_club_cancel_failed_order', array( $order_id ) ) ) {
			wp_schedule_single_event( time() + DAY_IN_SECONDS, 'mdo_club_cancel_failed_order', array( $order_id ) );
		}
		$order->add_order_note( 'Club EMDO: saldo reservado durante 24 horas por fallo de pago. Si no se completa el pago, el pedido se cancelará y liberará la reserva.' );
	}
	public static function cancel_expired_failed_order( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->has_status( 'failed' ) || $order->is_paid()
			|| 'yes' !== $order->get_meta( '_mdo_club_reserved' ) ) {
			return;
		}
		$order->update_status( 'cancelled', 'Club EMDO: cancelación automática tras 24 horas de pago fallido. El saldo reservado se devuelve al monedero.' );
	}
	public static function release_wallet( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || 'yes' !== $order->get_meta( '_mdo_club_reserved' ) ) { return; }
		$amount = (int) $order->get_meta( '_mdo_club_wallet_cents' );
		$user = (int) $order->get_meta( '_mdo_club_wallet_user_id' );
		if ( $amount > 0 && MDO_Club_Ledger::change( $user, $amount, 'release:' . $order_id, 'release', $order_id ) ) {
			$order->update_meta_data( '_mdo_club_reserved', 'released' );
			$order->save();
		}
	}
	public static function before_email( $order, $sent_to_admin, $plain_text, $email ): void {
		self::$admin_email = $sent_to_admin && $email instanceof WC_Email && 'new_order' === $email->id;
	}
	public static function after_email( $order, $sent_to_admin, $plain_text, $email ): void {
		self::$admin_email = false;
	}
	public static function email_totals( array $totals, $order, $tax_display ): array {
		if ( ! $order instanceof WC_Order ) { return $totals; }
		$wallet = (int) $order->get_meta( '_mdo_club_wallet_cents' );
		$full = (int) $order->get_meta( '_mdo_club_full_total_cents' );
		if ( $wallet <= 0 || $full <= 0 ) { return $totals; }
		// En el correo que se reenvía al productor: total facturable, sin detalles de pago.
		if ( self::$admin_email ) {
			if ( isset( $totals['order_total'] ) ) {
				$totals['order_total']['label'] = 'Total a facturar:';
				$totals['order_total']['value'] = wc_price( MDO_Club_Ledger::money( $full ), array( 'currency' => $order->get_currency() ) );
			}
			return $totals;
		}
		// Cliente: transparencia sobre cada medio de pago.
		$rows = array();
		foreach ( $totals as $key => $value ) {
			if ( 'order_total' === $key ) {
				$rows['mdo_club_value'] = array(
					'label' => 'Valor del pedido:',
					'value' => wc_price( MDO_Club_Ledger::money( $full ), array( 'currency' => $order->get_currency() ) ),
				);
				$rows['mdo_club_wallet'] = array(
					'label' => 'Pagado con monedero Club EMDO:',
					'value' => '−' . wc_price( MDO_Club_Ledger::money( $wallet ), array( 'currency' => $order->get_currency() ) ),
				);
				$value['label'] = 'Importe pagado al finalizar:';
			}
			$rows[ $key ] = $value;
		}
		return $rows;
	}
	public static function admin_notice( $order ): void {
		$full = (int) $order->get_meta( '_mdo_club_full_total_cents' );
		$wallet = (int) $order->get_meta( '_mdo_club_wallet_cents' );
		if ( $full <= 0 || $wallet <= 0 ) { return; }
		echo '<p><strong>Club EMDO · conciliación:</strong> valor del pedido a facturar por el productor: '
			. wp_kses_post( wc_price( MDO_Club_Ledger::money( $full ) ) )
			. '. Monedero: ' . wp_kses_post( wc_price( MDO_Club_Ledger::money( $wallet ) ) )
			. '. Pago externo: ' . wp_kses_post( wc_price( MDO_Club_Ledger::money( MDO_Club_Ledger::cents( $order->get_total() ) ) ) )
			. '.</p>';
	}
}