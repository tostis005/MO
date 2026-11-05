<?php
/**
 * Club de El Mercado de Origen: configuración, cuenta, recargas y tarjetas regalo.
 * Los flujos monetarios permanecen desactivados hasta su revisión y activación en EMDO.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club {
	private static bool $adding_club = false;
	public static function configured( string $feature ): bool {
		return 'yes' === get_option( 'mdo_club_' . $feature . '_enabled', 'no' );
	}
	public static function enabled( string $feature ): bool {
		// El interruptor de lanzamiento es independiente de lo configurado.
		return defined( 'MDO_CLUB_RELEASE_APPROVED' )
			&& true === MDO_CLUB_RELEASE_APPROVED
			&& self::configured( $feature );
	}
	public static function percent(): float {
		return min( 5, max( 0, (float) get_option( 'mdo_club_reward_percent', '1' ) ) );
	}
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'menu_item' ), 35 );
		add_action( 'woocommerce_account_monedero_endpoint', array( __CLASS__, 'account' ) );
		add_shortcode( 'mdo_club_gift_cards', array( __CLASS__, 'gift_page' ) );
		add_shortcode( 'mdo_club_landing', array( __CLASS__, 'landing' ) );
		add_action( 'wp_loaded', array( __CLASS__, 'handle_forms' ), 25 );
		add_filter( 'woocommerce_add_to_cart_validation', array( __CLASS__, 'validate_cart' ), 20, 2 );
		add_action( 'woocommerce_before_calculate_totals', array( __CLASS__, 'price_topups' ), 20 );
		add_action( 'woocommerce_check_cart_items', array( __CLASS__, 'check_prepaid_cart' ) );
		add_filter( 'woocommerce_coupon_is_valid', array( __CLASS__, 'no_coupons_for_prepaid' ), 20, 2 );
		add_filter( 'woocommerce_available_payment_gateways', array( __CLASS__, 'prepaid_gateways' ), 999 );
		add_action( 'woocommerce_checkout_create_order_line_item', array( __CLASS__, 'line_metadata' ), 10, 4 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'issue_prepaid' ), 20 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'issue_prepaid' ), 20 );
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'award_reward' ), 35 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'award_reward' ), 35 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'award_reward' ), 35 );
		add_filter( 'woocommerce_email_enabled_new_order', array( __CLASS__, 'skip_supplier_email' ), 20, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 25 );
		add_action( 'admin_post_mdo_club_save', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_create_content' ) );
		add_action( 'woocommerce_order_refunded', array( __CLASS__, 'refund_notice' ), 10, 2 );
	}
	public static function endpoint(): void {
		add_rewrite_endpoint( 'monedero', EP_ROOT | EP_PAGES );
	}
	public static function menu_item( array $items ): array {
		$result = array();
		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) { $result['monedero'] = 'Club EMDO'; }
			$result[ $key ] = $label;
		}
		if ( ! isset( $result['monedero'] ) ) { $result['monedero'] = 'Club EMDO'; }
		return $result;
	}
	public static function admin_menu(): void {
		add_submenu_page( 'mdo-supplier-sync', 'Club EMDO', 'Club EMDO', 'manage_woocommerce', 'mdo-club', array( __CLASS__, 'settings' ) );
	}
	public static function settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		$percent = self::percent();
		$ready = defined( 'MDO_CLUB_RELEASE_APPROVED' ) && true === MDO_CLUB_RELEASE_APPROVED;
		global $wpdb;
		$wallet_cents = (int) $wpdb->get_var( 'SELECT COALESCE(SUM(balance_cents),0) FROM ' . MDO_Club_Ledger::table( 'wallets' ) );
		$gift_cents = (int) $wpdb->get_var( 'SELECT COALESCE(SUM(amount_cents),0) FROM ' . MDO_Club_Ledger::table( 'gifts' ) . ' WHERE redeemed_user_id=0' );
		$pending_cards = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . MDO_Club_Ledger::table( 'gifts' ) . ' WHERE redeemed_user_id=0' );
		?>
		<div class="wrap"><h1>Club EMDO</h1>
			<p>Configura el porcentaje de recompensa. Los pagos anticipados y el uso de saldo no se habilitan hasta validar pasarela, impuestos, reembolsos y liquidaciones.</p>
			<?php if ( ! $ready ) : ?><div class="notice notice-warning inline"><p><strong>Club EMDO bloqueado para transacciones:</strong> falta habilitación técnica explícita <code>MDO_CLUB_RELEASE_APPROVED</code> tras auditoría y pruebas en staging. Guardar los ajustes no lo activará.</p></div><?php endif; ?>
			<p><strong>Saldo vivo de clientes:</strong> <?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $wallet_cents ) ) ); ?>
				· <strong>Tarjetas sin canjear:</strong> <?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $gift_cents ) ) ); ?> (<?php echo (int) $pending_cards; ?>).
				Son pasivos pendientes de consumo, no ventas de productos.</p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'mdo_club_settings' ); ?>
				<input type="hidden" name="action" value="mdo_club_save">
				<table class="form-table">
					<tr><th><label for="mdo-club-rate">Recompensa por compra (%)</label></th><td>
						<input id="mdo-club-rate" type="number" min="0" max="5" step="0.1" name="reward_percent" value="<?php echo esc_attr( $percent ); ?>">
						<p class="description">Valor inicial: 1 %. Solo líneas de productos; no portes, recargas ni tarjetas regalo. Se acredita al completar el pedido.</p>
					</td></tr>
					<tr><th>Activación</th><td>
						<label><input type="checkbox" name="rewards_enabled" value="yes" <?php checked( self::configured( 'rewards' ) ); ?>> Activar recompensas</label><br>
						<label><input type="checkbox" name="prepaid_enabled" value="yes" <?php checked( self::configured( 'prepaid' ) ); ?>> Activar venta de recargas y tarjetas regalo</label><br>
						<label><input type="checkbox" name="checkout_enabled" value="yes" <?php checked( self::configured( 'checkout' ) ); ?>> Activar aplicación de saldo en checkout clásico</label>
						<p class="description"><strong>No activar pagos sin pruebas de extremo a extremo.</strong> La constante de desbloqueo técnico no equivale a autorización contractual o fiscal de Edenred.</p>
					</td></tr>
				</table>
				<?php submit_button( 'Guardar configuración' ); ?>
			</form>
			<p><a class="button button-secondary" href="<?php echo esc_url( admin_url( 'admin.php?page=mdo-club-balances' ) ); ?>">Ver y ajustar saldos de usuarios</a></p>
			<p><strong>Devoluciones:</strong> no se ajusta automáticamente ningún saldo por reembolso. Revisar cada caso desde Saldos Club EMDO.</p>
			<p><strong>Publicación:</strong> se generan borradores de las páginas «Club EMDO» y «Tarjetas regalo». Publícalos al validar todo el circuito.</p>
			<?php self::render_reconciliation(); ?>
		</div>
		<?php
	}
	/**
	 * Conciliación de pagos mixtos. No sustituye los informes fiscales
	 * ni los importes de comisión/liquidación que calcule WCFM.
	 */
	private static function render_reconciliation(): void {
		if ( ! function_exists( 'wc_get_orders' ) ) { return; }
		$orders = wc_get_orders( array(
			'limit' => 20,
			'orderby' => 'date',
			'order' => 'DESC',
			'meta_key' => '_mdo_club_wallet_cents',
			'meta_value' => 0,
			'meta_compare' => '>',
		) );
		?>
		<h2>Conciliación: últimos pedidos con monedero</h2>
		<p>El productor debe facturar el valor completo del pedido; el pago bancario de WooCommerce puede ser inferior por el saldo Club aplicado. Es imprescindible cuadrarlo con las liquidaciones de WCFM.</p>
		<table class="widefat striped">
			<thead><tr><th>Pedido</th><th>Cliente</th><th>Venta facturable</th><th>Monedero</th><th>Pasarela</th><th>Cuadre</th></tr></thead>
			<tbody>
			<?php if ( ! $orders ) : ?><tr><td colspan="6">Sin pagos con monedero todavía.</td></tr><?php endif; ?>
			<?php foreach ( $orders as $order ) :
				$full = (int) $order->get_meta( '_mdo_club_full_total_cents' );
				$wallet = (int) $order->get_meta( '_mdo_club_wallet_cents' );
				$gateway = MDO_Club_Ledger::cents( $order->get_total() );
				$matched = $full > 0 && $full === $wallet + $gateway;
				?>
				<tr>
					<td><a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo (int) $order->get_id(); ?></a> · <?php echo esc_html( $order->get_status() ); ?></td>
					<td><?php echo esc_html( $order->get_billing_email() ); ?></td>
					<td><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $full ) ) ); ?></td>
					<td><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $wallet ) ) ); ?></td>
					<td><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $gateway ) ) ); ?></td>
					<td><?php echo $matched ? '<strong style="color:#17713b">Cuadrado</strong>' : '<strong style="color:#b32d2e">REVISAR</strong>'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
	public static function save_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Sin permiso.' ); }
		check_admin_referer( 'mdo_club_settings' );
		$rate = isset( $_POST['reward_percent'] ) ? (float) wc_format_decimal( wp_unslash( $_POST['reward_percent'] ) ) : 1;
		update_option( 'mdo_club_reward_percent', (string) min( 5, max( 0, $rate ) ), false );
		foreach ( array( 'rewards', 'prepaid', 'checkout' ) as $name ) {
			update_option( 'mdo_club_' . $name . '_enabled', isset( $_POST[ $name . '_enabled' ] ) ? 'yes' : 'no', false );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mdo-club&saved=1' ) );
		exit;
	}
	public static function maybe_create_content(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! class_exists( 'WooCommerce' ) ) { return; }
		MDO_Club_Ledger::maybe_install();
		foreach ( array(
			'mdo_club_page' => array( 'club', 'Club EMDO', '[mdo_club_landing]' ),
			'mdo_club_gift_page' => array( 'tarjetas-regalo', 'Tarjetas regalo', '[mdo_club_gift_cards]' ),
		) as $option => $data ) {
			if ( get_option( $option ) ) { continue; }
			$existing = get_page_by_path( $data[0] );
			$id = $existing ? $existing->ID : wp_insert_post( array(
				'post_title' => $data[1], 'post_name' => $data[0], 'post_content' => $data[2],
				'post_type' => 'page', 'post_status' => 'publish',
			) );
			if ( $id && ! is_wp_error( $id ) ) { update_option( $option, (int) $id, false ); }
		}
		foreach ( array( 'wallet' => 'Recarga de monedero Club EMDO', 'gift' => 'Tarjeta regalo Club EMDO' ) as $kind => $title ) {
			$option = 'mdo_club_product_' . $kind;
			if ( wc_get_product( (int) get_option( $option ) ) ) { continue; }
			$product = new WC_Product_Simple();
			$product->set_name( $title );
			$product->set_status( 'publish' );
			$product->set_catalog_visibility( 'hidden' );
			$product->set_virtual( true );
			$product->set_sold_individually( true );
			$product->set_tax_status( 'none' ); // Bono polivalente: pendiente de validación fiscal.
			$product->set_regular_price( '1.00' ); // Sobrescrito con la cantidad validada de la sesión.
			$product->update_meta_data( '_mdo_club_kind', $kind );
			$product_id = $product->save();
			if ( $product_id ) { update_option( $option, $product_id, false ); }
		}
		if ( '1' !== get_option( 'mdo_club_endpoint_rewrite' ) ) {
			flush_rewrite_rules( false );
			update_option( 'mdo_club_endpoint_rewrite', '1', false );
		}
	}
	private static function product_kind( int $product_id ): string {
		if ( $product_id === (int) get_option( 'mdo_club_product_wallet' ) ) { return 'wallet'; }
		if ( $product_id === (int) get_option( 'mdo_club_product_gift' ) ) { return 'gift'; }
		return '';
	}
	private static function funds_html( int $cents ): string {
		return wc_price( MDO_Club_Ledger::money( $cents ) );
	}
	public static function account(): void {
		$user = get_current_user_id();
		if ( ! $user ) { echo '<p>Inicia sesión para ver tu monedero.</p>'; return; }
		$balance = MDO_Club_Ledger::balance( $user );
		self::styles();
		?>
		<div class="mdo-club">
			<div class="mdo-club-hero"><span>EL MERCADO DE ORIGEN · CLUB EMDO</span><h2>Mi monedero</h2>
				<p>Saldo disponible para tus próximos pedidos</p><strong><?php echo wp_kses_post( self::funds_html( $balance ) ); ?></strong>
				<p>Un solo saldo que suma recompensas, recargas y tarjetas regalo.</p>
			</div>
			<div class="mdo-club-columns">
				<section class="mdo-club-card"><h3>Recargar saldo</h3><p>Elige cuánto quieres añadir a tu monedero.</p>
					<?php self::purchase_form( 'wallet' ); ?>
				</section>
				<section class="mdo-club-card"><h3>Canjear tarjeta regalo</h3><p>Convierte una o varias tarjetas regalo en saldo acumulable.</p>
					<?php self::redeem_form(); ?>
					<p><a href="<?php echo esc_url( get_permalink( (int) get_option( 'mdo_club_gift_page' ) ) ); ?>">Comprar tarjeta regalo</a></p>
				</section>
			</div>
			<section class="mdo-club-card"><h3>Mis recompensas</h3><p><?php if ( self::enabled( 'rewards' ) ) : ?>Recibe <?php echo esc_html( (string) self::percent() ); ?> % de las compras válidas, una vez completadas. Las recompensas se acumulan en el mismo monedero.<?php else : ?>Próximamente: acumula recompensas con tus compras para gastar en próximos pedidos.<?php endif; ?></p></section>
			<section class="mdo-club-card"><h3>Movimientos</h3>
				<table><thead><tr><th>Fecha</th><th>Concepto</th><th>Importe</th></tr></thead><tbody>
				<?php foreach ( MDO_Club_Ledger::movements( $user ) as $line ) : ?>
					<tr><td><?php echo esc_html( mysql2date( 'd/m/Y', $line['created_at'], false ) ); ?></td>
						<td><?php echo esc_html( self::kind_label( $line['kind'] ) ); ?><?php if ( $line['order_id'] ) : ?> · #<?php echo (int) $line['order_id']; ?><?php endif; ?></td>
						<td><?php echo esc_html( ( (int) $line['amount_cents'] > 0 ? '+' : '−' ) ); ?><?php echo wp_kses_post( self::funds_html( abs( (int) $line['amount_cents'] ) ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody></table>
			</section>
		</div>
		<?php
	}
	private static function kind_label( string $kind ): string {
		return array( 'wallet' => 'Recarga', 'gift' => 'Tarjeta regalo', 'reward' => 'Recompensa', 'spend' => 'Compra', 'release' => 'Saldo devuelto', 'adjustment' => 'Ajuste' )[ $kind ] ?? 'Movimiento';
	}
	public static function gift_page(): string {
		ob_start();
		self::styles();
		?>
		<div class="mdo-club mdo-club-gifts">
			<div class="mdo-club-hero"><span>UN DETALLE CON ORIGEN</span><h2>Tarjetas regalo Club EMDO</h2>
			<p>Regala la libertad de elegir productos auténticos de nuestros productores.</p>
			<div class="mdo-club-gift-visual"><small>EL MERCADO DE ORIGEN</small><strong>Una tarjeta, muchos sabores.</strong><span>Tarjeta regalo Club EMDO</span></div></div>
			<section class="mdo-club-card"><h3>Elige tu regalo</h3>
				<p>El destinatario recibirá un correo con su tarjeta y un código único. Podrá canjearla en Mi cuenta y acumular varias.</p>
				<?php self::purchase_form( 'gift' ); ?>
			</section>
		</div>
		<?php
		return (string) ob_get_clean();
	}
	public static function landing(): string {
		ob_start(); self::styles(); ?>
		<div class="mdo-club"><div class="mdo-club-hero"><span>EL MERCADO DE ORIGEN</span><h2>Club EMDO</h2><p>Comprar bien también tiene recompensa.</p></div>
			<section class="mdo-club-card"><h3><?php echo self::enabled( 'rewards' ) ? 'Recupera ' . esc_html( (string) self::percent() ) . ' % en tus compras' : 'Tus compras tendrán recompensa'; ?></h3><p><?php echo self::enabled( 'rewards' ) ? 'Una vez completado tu pedido, recibes saldo en tu monedero para gastar en futuras compras válidas.' : 'Estamos preparando las ventajas de Club EMDO.'; ?></p>
			<p>También puedes guardar saldo y canjear varias tarjetas regalo en tu monedero.</p>
			<a class="button" href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>">Ir a Mi cuenta</a>
			<a class="button" href="<?php echo esc_url( get_permalink( (int) get_option( 'mdo_club_gift_page' ) ) ); ?>">Tarjetas regalo</a></section></div>
		<?php return (string) ob_get_clean();
	}
	private static function amount_buttons(): void {
		echo '<div class="mdo-club-amounts">';
		foreach ( array( 25, 50, 100 ) as $amount ) {
			echo '<button type="button" class="button" data-mdo-amount="' . (int) $amount . '">' . esc_html( $amount . ' €' ) . '</button> ';
		}
		echo '</div>';
	}
	private static function purchase_form( string $kind ): void {
		if ( ! self::enabled( 'prepaid' ) ) { echo '<p class="mdo-club-note">Las recargas y tarjetas regalo estarán disponibles próximamente.</p>'; return; }
		if ( 'wallet' === $kind && ! is_user_logged_in() ) { echo '<p>Inicia sesión para recargar tu monedero.</p>'; return; }
		?>
		<form method="post" class="mdo-club-buy">
			<?php wp_nonce_field( 'mdo_club_buy' ); ?>
			<input type="hidden" name="mdo_club_form" value="buy">
			<input type="hidden" name="kind" value="<?php echo esc_attr( $kind ); ?>">
			<?php self::amount_buttons(); ?>
			<label>Importe personalizado (€)<input type="number" name="amount" min="10" max="500" step="0.01" value="25" required></label>
			<?php if ( 'gift' === $kind ) : ?>
				<label>Correo de la persona que recibirá la tarjeta<input type="email" name="recipient_email" maxlength="190" required></label>
				<label>Mensaje (opcional)<textarea name="gift_message" maxlength="350" rows="3"></textarea></label>
			<?php endif; ?>
			<button class="button alt" type="submit"><?php echo 'gift' === $kind ? 'Comprar tarjeta regalo' : 'Añadir saldo'; ?></button>
		</form>
		<?php self::amount_script(); ?>
		<?php
	}
	private static function redeem_form(): void {
		if ( ! self::enabled( 'prepaid' ) ) { echo '<p class="mdo-club-note">El canje estará disponible próximamente.</p>'; return; }
		if ( ! is_user_logged_in() ) { echo '<p>Inicia sesión para canjear tu tarjeta.</p>'; return; }
		?>
		<form method="post" class="mdo-club-redeem">
			<?php wp_nonce_field( 'mdo_club_redeem' ); ?>
			<input type="hidden" name="mdo_club_form" value="redeem">
			<label>Código de tarjeta regalo<input type="text" name="gift_code" maxlength="64" placeholder="CLUB-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX" required></label>
			<button type="submit" class="button">Canjear tarjeta</button>
		</form>
		<?php
	}
	private static function amount_script(): void {
		?><script>
		document.querySelectorAll('.mdo-club-buy').forEach(function(form){
			form.querySelectorAll('[data-mdo-amount]').forEach(function(button){
				button.addEventListener('click',function(){form.querySelector('[name="amount"]').value=button.dataset.mdoAmount;});
			});
		});
		</script><?php
	}
	private static function styles(): void {
		?><style>
		.mdo-club{max-width:920px;margin:20px auto;color:#224334;font-family:inherit}
		.mdo-club-hero{background:#193e30;color:#fff7e8;border-radius:20px;padding:34px;margin-bottom:22px}
		.mdo-club-hero>span{font-size:11px;letter-spacing:.17em;font-weight:750;opacity:.8}
		.mdo-club-hero h2{color:#fff7e8;margin:12px 0;font-size:clamp(26px,4vw,42px)}
		.mdo-club-hero p{color:#edf1e7}.mdo-club-hero>strong{font-size:32px;color:#fff}.mdo-club-balance-breakdown{display:flex;flex-wrap:wrap;gap:16px;margin-top:14px;font-size:13px;color:#e8f1e7}
		.mdo-club-columns{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px}
		.mdo-club-card{padding:22px;margin-bottom:16px;background:#fffdfa;border:1px solid #eee7dc;border-radius:16px}
		.mdo-club-card h3{color:#224334;margin:0 0 10px}.mdo-club-card p{line-height:1.6}
		.mdo-club form label{display:block;font-weight:600;margin:12px 0 8px}
		.mdo-club input,.mdo-club textarea{width:100%;max-width:100%;box-sizing:border-box;padding:11px;border:1px solid #d7ddd4;border-radius:9px;background:#fff}
		.mdo-club .button{background:#28513c;color:#fff;border:0;border-radius:10px;padding:10px 14px;display:inline-block;margin:6px 5px 0 0;cursor:pointer}
		.mdo-club-amounts .button{background:#f4eee3;color:#234b37}.mdo-club-note{font-style:italic}
		.mdo-club table{width:100%;border-collapse:collapse}.mdo-club td,.mdo-club th{padding:10px;border-bottom:1px solid #eee;text-align:left}
		.mdo-club-gift-visual{max-width:390px;margin:22px 0 0;padding:24px;border-radius:15px;background:linear-gradient(125deg,#e7b66e,#f8dfb2 50%,#d7a75f);color:#1f4735;display:grid;gap:14px;box-shadow:0 14px 30px rgba(0,0,0,.16)}
		.mdo-club-gift-visual strong{font-family:Georgia,serif;font-size:24px}.mdo-club-gift-visual small{font-size:11px;letter-spacing:.13em}
		@media(max-width:700px){.mdo-club-columns{grid-template-columns:1fr}.mdo-club-hero{padding:24px}.mdo-club-card{padding:16px}}
		</style><?php
	}
	public static function handle_forms(): void {
		if ( empty( $_POST['mdo_club_form'] ) || ! function_exists( 'WC' ) ) { return; }
		$action = sanitize_key( wp_unslash( $_POST['mdo_club_form'] ) );
		if ( 'redeem' === $action ) {
			check_admin_referer( 'mdo_club_redeem' );
			if ( ! self::enabled( 'prepaid' ) || ! is_user_logged_in() ) { wc_add_notice( 'Inicia sesión para canjear la tarjeta.', 'error' ); }
			elseif ( MDO_Club_Ledger::redeem( get_current_user_id(), sanitize_text_field( wp_unslash( $_POST['gift_code'] ?? '' ) ) ) ) {
				wc_add_notice( 'Tarjeta regalo incorporada a tu monedero.', 'success' );
			} else { wc_add_notice( 'El código no es válido o ya fue canjeado.', 'error' ); }
			wp_safe_redirect( wp_get_referer() ?: wc_get_page_permalink( 'myaccount' ) ); exit;
		}
		if ( 'buy' !== $action ) { return; }
		check_admin_referer( 'mdo_club_buy' );
		if ( ! self::enabled( 'prepaid' ) ) { wc_add_notice( 'Las recargas no están disponibles.', 'error' ); return; }
		$kind = sanitize_key( wp_unslash( $_POST['kind'] ?? '' ) );
		$amount = MDO_Club_Ledger::cents( wp_unslash( $_POST['amount'] ?? 0 ) );
		$email = sanitize_email( wp_unslash( $_POST['recipient_email'] ?? '' ) );
		$message = sanitize_textarea_field( wp_unslash( $_POST['gift_message'] ?? '' ) );
		if ( ! in_array( $kind, array( 'wallet', 'gift' ), true ) || $amount < 1000 || $amount > 50000
			|| ( 'gift' === $kind && ! is_email( $email ) )
			|| ( 'wallet' === $kind && ! is_user_logged_in() ) ) {
			wc_add_notice( 'Revisa el importe (10–500 €) y los datos.', 'error' );
			return;
		}
		if ( ! WC()->cart ) { wc_load_cart(); }
		if ( ! WC()->cart || ! WC()->cart->is_empty() ) {
			wc_add_notice( 'Por seguridad, compra las recargas y tarjetas regalo en un pedido separado.', 'error' );
			return;
		}
		$product_id = (int) get_option( 'mdo_club_product_' . $kind );
		if ( ! $product_id || ! wc_get_product( $product_id ) ) { wc_add_notice( 'El producto Club aún no está configurado.', 'error' ); return; }
		self::$adding_club = true;
		$added = WC()->cart->add_to_cart( $product_id, 1, 0, array(), array(
			'mdo_club_kind' => $kind, 'mdo_club_amount_cents' => $amount,
			'mdo_club_recipient' => $email, 'mdo_club_message' => $message,
			'mdo_club_unique' => wp_generate_uuid4(),
		) );
		self::$adding_club = false;
		if ( $added ) { wp_safe_redirect( wc_get_checkout_url() ); exit; }
		wc_add_notice( 'No se pudo añadir el saldo al pedido.', 'error' );
	}
	public static function validate_cart( bool $valid, int $product_id ): bool {
		$kind = self::product_kind( $product_id );
		if ( $kind && ( ! self::enabled( 'prepaid' ) || ! self::$adding_club ) ) { return false; }
		if ( WC()->cart && ! WC()->cart->is_empty() ) {
			foreach ( WC()->cart->get_cart() as $item ) {
				if ( ! empty( $item['mdo_club_kind'] ) ) { return false; }
			}
		}
		return $valid;
	}
	public static function no_coupons_for_prepaid( bool $valid, $coupon ): bool {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return $valid; }
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! empty( $item['mdo_club_kind'] ) ) { return false; }
		}
		return $valid;
	}
	public static function check_prepaid_cart(): void {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return; }
		$items = WC()->cart->get_cart();
		$prepaid = array_filter( $items, static function( $item ) {
			return ! empty( $item['mdo_club_kind'] );
		} );
		if ( ! $prepaid ) { return; }
		if ( ! self::enabled( 'prepaid' ) || 1 !== count( $items ) || WC()->cart->get_applied_coupons() ) {
			wc_add_notice( 'Las recargas y tarjetas regalo requieren un pedido separado.', 'error' );
		}
		foreach ( $prepaid as $item ) {
			$kind = sanitize_key( $item['mdo_club_kind'] );
			$cents = (int) ( $item['mdo_club_amount_cents'] ?? 0 );
			$email = sanitize_email( $item['mdo_club_recipient'] ?? '' );
			if (
				1 !== (int) $item['quantity']
				|| self::product_kind( (int) $item['product_id'] ) !== $kind
				|| $cents < 1000 || $cents > 50000
				|| ( 'wallet' === $kind && ! is_user_logged_in() )
				|| ( 'gift' === $kind && ! is_email( $email ) )
			) {
				wc_add_notice( 'Los datos de la tarjeta regalo o recarga no son válidos.', 'error' );
				break;
			}
		}
	}
	public static function prepaid_gateways( array $gateways ): array {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) { return $gateways; }
		$prepaid = false;
		foreach ( WC()->cart->get_cart() as $item ) {
			if ( ! empty( $item['mdo_club_kind'] ) ) { $prepaid = true; break; }
		}
		if ( ! $prepaid ) { return $gateways; }
		// Mostrar los medios disponibles en la tienda, incluyendo transferencia y Bizum.
		// Solo se excluye contra reembolso: no tiene sentido para productos virtuales.
		// La transferencia o cualquier método manual exige confirmar el cobro
		// cambiando el pedido a Completado antes de emitir saldo o tarjeta regalo.
		foreach ( array( 'cod' ) as $gateway_id ) {
			unset( $gateways[ $gateway_id ] );
		}
		return $gateways;
	}
	public static function price_topups( $cart ): void {
		if ( is_admin() && ! wp_doing_ajax() ) { return; }
		foreach ( $cart->get_cart() as $item ) {
			if ( ! empty( $item['mdo_club_kind'] ) && isset( $item['mdo_club_amount_cents'] ) ) {
				$amount = (int) $item['mdo_club_amount_cents'];
				if ( $amount >= 1000 && $amount <= 50000 ) {
					$item['data']->set_price( MDO_Club_Ledger::money( $amount ) );
				}
			}
		}
	}
	public static function line_metadata( $item, $cart_key, $values, $order ): void {
		if ( empty( $values['mdo_club_kind'] ) ) { return; }
		foreach ( array(
			'_mdo_club_kind' => sanitize_key( $values['mdo_club_kind'] ),
			'_mdo_club_amount_cents' => (int) $values['mdo_club_amount_cents'],
			'_mdo_club_recipient' => sanitize_email( $values['mdo_club_recipient'] ),
			'_mdo_club_message' => sanitize_textarea_field( $values['mdo_club_message'] ),
		) as $key => $val ) { $item->add_meta_data( $key, $val, true ); }
	}
	public static function is_prepaid( $order ): bool {
		if ( ! $order instanceof WC_Order ) { return false; }
		foreach ( $order->get_items() as $item ) {
			if ( in_array( $item->get_meta( '_mdo_club_kind' ), array( 'wallet', 'gift' ), true ) ) { return true; }
		}
		return false;
	}
	public static function issue_prepaid( int $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->is_paid() || ! self::is_prepaid( $order ) ) { return; }
		// Las pasarelas online invocan payment_complete; en transferencia/Bizum
		// manual, el administrador confirma el pago al completar el pedido.
		// Nunca se emite saldo desde un pedido pendiente o en espera.
		// No emitir dinero si el cliente pagó menos que el valor comprado.
		// Protege frente a cupones, edición de cantidades, ajustes manuales y
		// otras extensiones que cambien el total incluso tras validar el carrito.
		$lines = $order->get_items( 'line_item' );
		if ( 1 !== count( $lines ) || $order->get_items( 'coupon' ) ) {
			$order->add_order_note( 'BLOQUEO Club EMDO: el pedido de saldo contiene líneas adicionales o descuentos.' );
			return;
		}
		$only_item = reset( $lines );
		$declared_cents = (int) $only_item->get_meta( '_mdo_club_amount_cents' );
		if (
			1 !== (int) $only_item->get_quantity()
			|| $declared_cents < 1000 || $declared_cents > 50000
			|| MDO_Club_Ledger::cents( $only_item->get_total() + $only_item->get_total_tax() ) !== $declared_cents
			|| MDO_Club_Ledger::cents( $order->get_total() ) !== $declared_cents
		) {
			$order->add_order_note( 'BLOQUEO Club EMDO: el importe pagado no coincide con el saldo solicitado. Revisar y reembolsar manualmente; no se ha emitido saldo.' );
			return;
		}
		foreach ( $lines as $item_id => $item ) {
			$kind = $item->get_meta( '_mdo_club_kind' );
			$cents = (int) $item->get_meta( '_mdo_club_amount_cents' );
			if ( ! in_array( $kind, array( 'gift', 'wallet' ), true ) || $cents < 1000 ) { continue; }
			if ( 'wallet' === $kind ) {
				$user_id = (int) $order->get_customer_id();
				if ( $user_id && MDO_Club_Ledger::change( $user_id, $cents, 'topup:' . $order_id . ':' . $item_id, 'wallet', $order_id ) ) {
					$order->add_order_note( 'Club: recarga de monedero registrada.' );
				}
			} else {
				$email = sanitize_email( $item->get_meta( '_mdo_club_recipient' ) );
				if ( MDO_Club_Ledger::issue_gift( $order_id, $item_id, $cents, $email ) && ! $item->get_meta( '_mdo_club_gift_sent' ) ) {
					if ( self::send_gift( $email, $order_id, $item_id, $cents, (string) $item->get_meta( '_mdo_club_message' ) ) ) {
						$item->update_meta_data( '_mdo_club_gift_sent', 'yes' ); $item->save();
						$order->add_order_note( 'Club: tarjeta regalo enviada a ' . $email );
					} else { $order->add_order_note( 'Club: no se pudo enviar la tarjeta regalo. Requiere reenvío.' ); }
				}
			}
		}
	}
	private static function send_gift( string $email, int $order_id, int $item_id, int $cents, string $message ): bool {
		$code = MDO_Club_Ledger::gift_code( $order_id, $item_id );
		$site = home_url( '/' );
		$account = wc_get_page_permalink( 'myaccount' );
		$body = '<div style="background:#f6f2e8;padding:32px 12px;font-family:Arial,sans-serif">'
			. '<div style="max-width:600px;margin:auto;background:#fffdf7;border-radius:18px;overflow:hidden">'
			. '<div style="background:#1b4533;padding:28px;color:#fff">'
			. '<div style="font-size:12px;letter-spacing:2px">EL MERCADO DE ORIGEN</div>'
			. '<h1 style="font-family:Georgia,serif;font-size:32px;color:#fff">Un regalo con origen</h1>'
			. '<p style="color:#f2e6d1">Alguien ha pensado en ti.</p></div>'
			. '<div style="margin:25px;padding:28px;border-radius:15px;background:linear-gradient(120deg,#e5bc76,#f9e7c6);color:#234533">'
			. '<div style="letter-spacing:2px;font-size:12px">TARJETA REGALO · CLUB EMDO</div>'
			. '<div style="font-family:Georgia,serif;font-size:36px;margin:16px 0">' . esc_html( wp_strip_all_tags( self::funds_html( $cents ) ) ) . '</div>'
			. '<div style="font-size:16px;letter-spacing:1px;font-weight:bold">' . esc_html( $code ) . '</div></div>'
			. ( $message ? '<p style="margin:25px;color:#355140">' . nl2br( esc_html( $message ) ) . '</p>' : '' )
			. '<p style="padding:0 25px;color:#365242">Crea una cuenta o inicia sesión, abre Mi monedero y canjea el código. Puedes acumular varias tarjetas regalo.</p>'
			. '<p style="padding:0 25px 28px"><a href="' . esc_url( $account ) . '" style="background:#28533c;color:#fff;padding:14px 22px;text-decoration:none;border-radius:9px;display:inline-block">Canjear tarjeta</a></p>'
			. '<p style="padding:0 25px 25px;font-size:12px;color:#7d8b80">' . esc_html( $site ) . '</p></div></div>';
		return wp_mail( $email, 'Tu tarjeta regalo de Club EMDO', $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
	}
	public static function award_reward( int $order_id ): void {
		if ( ! self::enabled( 'rewards' ) ) { return; }
		$order = wc_get_order( $order_id );
		if ( ! $order || ! $order->is_paid() || ! $order->get_date_paid() || self::is_prepaid( $order ) || ! $order->get_customer_id() || ! get_user_by( 'id', (int) $order->get_customer_id() ) || (int) $order->get_meta( '_mdo_club_award_cents' ) > 0 ) { return; }
		$eligible = 0;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			if ( self::product_kind( (int) $item->get_product_id() ) ) { continue; }
			$eligible += MDO_Club_Ledger::cents( (float) $item->get_total() + (float) $item->get_total_tax() );
		}
		$amount = (int) round( $eligible * self::percent() / 100 );
		if ( $amount > 0 && MDO_Club_Ledger::change( (int) $order->get_customer_id(), $amount, 'reward:' . $order_id, 'reward', $order_id ) ) {
			$order->update_meta_data( '_mdo_club_award_cents', $amount );
			$order->save();
		}
	}
	public static function skip_supplier_email( bool $enabled, $order ): bool {
		return self::is_prepaid( $order ) ? false : $enabled;
	}
	public static function refund_notice( int $order_id, int $refund_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order ) { return; }
		if ( self::is_prepaid( $order )
			|| (int) $order->get_meta( '_mdo_club_award_cents' ) > 0
			|| (int) $order->get_meta( '_mdo_club_wallet_cents' ) > 0 ) {
			$order->add_order_note(
				'Club EMDO: hay saldo o tarjetas regalo implicados. El reembolso NO modifica automáticamente el monedero. Revisar el caso y, si procede, ajustar manualmente el saldo en EMDO > Saldos Club EMDO.'
			);
		}
	}
}