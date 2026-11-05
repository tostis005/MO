<?php
/**
 * Club EMDO · primera fase: recompensa para clientes registrados y gestión EMDO.
 * No permite recargas, tarjetas regalo ni aplicar saldo en checkout.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club_Rewards {
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'account_endpoint' ), 9 );
		add_action( 'init', array( __CLASS__, 'maybe_refresh_rewrite' ), 35 );
		add_filter( 'woocommerce_account_menu_items', array( __CLASS__, 'account_menu' ), 35 );
		add_action( 'woocommerce_account_monedero_endpoint', array( __CLASS__, 'account_page' ) );

		// Diferentes pasarelas confirman pagos en momentos diferentes.
		// El event_key de la tabla de movimientos evita créditos duplicados.
		add_action( 'woocommerce_payment_complete', array( __CLASS__, 'credit_purchase' ), 30 );
		add_action( 'woocommerce_order_status_processing', array( __CLASS__, 'credit_purchase' ), 30 );
		add_action( 'woocommerce_order_status_completed', array( __CLASS__, 'credit_purchase' ), 30 );

		if ( is_admin() ) {
			add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ), 60 );
			add_action( 'admin_post_mdo_club_rewards_settings', array( __CLASS__, 'save_settings' ) );
			add_action( 'admin_post_mdo_club_reward_adjust', array( __CLASS__, 'adjust_balance' ) );
		}
	}

	public static function enabled(): bool {
		return 'yes' === get_option( 'mdo_club_rewards_enabled', 'yes' );
	}
	public static function rate(): float {
		return min( 10, max( 0, (float) get_option( 'mdo_club_rewards_percent', '1' ) ) );
	}
	public static function account_endpoint(): void {
		add_rewrite_endpoint( 'monedero', EP_ROOT | EP_PAGES );
	}
	public static function maybe_refresh_rewrite(): void {
		if ( '1' !== get_option( 'mdo_club_rewards_endpoint_registered', '0' ) ) {
			flush_rewrite_rules( false );
			update_option( 'mdo_club_rewards_endpoint_registered', '1', false );
		}
	}
	public static function account_menu( array $items ): array {
		$result = array();
		foreach ( $items as $key => $label ) {
			if ( 'customer-logout' === $key ) { $result['monedero'] = 'Club EMDO'; }
			$result[ $key ] = $label;
		}
		if ( ! isset( $result['monedero'] ) ) { $result['monedero'] = 'Club EMDO'; }
		return $result;
	}
	/**
	 * Uses paid product-line totals including VAT, after WooCommerce discounts.
	 * Excludes shipping/fees. Paid order status and date paid are mandatory.
	 */
	public static function eligible_cents( $order ): int {
		if ( ! $order instanceof WC_Order ) { return 0; }
		$eligible = 0;
		foreach ( $order->get_items( 'line_item' ) as $item ) {
			$eligible += (int) round( ( (float) $item->get_total() + (float) $item->get_total_tax() ) * 100 );
		}
		return max( 0, $eligible );
	}
	public static function credit_purchase( int $order_id ): void {
		if ( ! self::enabled() || self::rate() <= 0 ) { return; }
		$order = wc_get_order( $order_id );
		if (
			! $order instanceof WC_Order
			|| ! $order->is_paid()
			|| ! $order->get_date_paid()
			|| (int) $order->get_customer_id() <= 0
		) { return; }
		$user_id = (int) $order->get_customer_id();
		if ( ! get_user_by( 'id', $user_id ) ) { return; }
		$eligible = self::eligible_cents( $order );
		$reward = (int) round( $eligible * self::rate() / 100 );
		if ( $reward <= 0 ) { return; }
		// Stable key across payment_complete, processing, completed and retries.
		MDO_Club_Rewards_Ledger::change(
			$user_id, $reward, 'purchase:' . $order_id, 'reward', $order_id, 0,
			'Club EMDO · ' . self::rate() . '% sobre productos pagados'
		);
	}
	public static function account_page(): void {
		$user_id = get_current_user_id();
		if ( ! $user_id ) { echo '<p>Inicia sesión para consultar Club EMDO.</p>'; return; }
		$balance = MDO_Club_Rewards_Ledger::balance( $user_id );
		$movements = MDO_Club_Rewards_Ledger::movements( $user_id, 25 );
		?>
		<style>
			.mdo-club-rewards{max-width:940px;margin:0 auto;color:#244735}
			.mdo-club-rewards .mdo-club-hero{padding:28px;border-radius:19px;background:linear-gradient(125deg,#193e30,#2d5840);color:#fffdf5;margin-bottom:20px}
			.mdo-club-rewards .mdo-club-hero small{font-weight:700;letter-spacing:.13em}
			.mdo-club-rewards .mdo-club-hero h2{margin:12px 0;color:#fff;font-size:29px}
			.mdo-club-rewards .mdo-club-hero strong{display:block;margin:12px 0;font-size:34px;color:#fff}
			.mdo-club-rewards .mdo-club-card{padding:22px;margin-bottom:16px;background:#fffdf9;border:1px solid #e8e7df;border-radius:15px}
			.mdo-club-rewards h3{color:#204532;margin:0 0 10px}
			.mdo-club-rewards .mdo-club-table{border-collapse:collapse;width:100%}
			.mdo-club-rewards .mdo-club-table th,.mdo-club-rewards .mdo-club-table td{padding:10px 8px;text-align:left;border-bottom:1px solid #ece9df}
			@media(max-width:600px){.mdo-club-rewards .mdo-club-hero{padding:20px}.mdo-club-rewards .mdo-club-card{padding:15px}.mdo-club-rewards .mdo-club-table{font-size:12px}}
		</style>
		<div class="mdo-club-rewards">
			<div class="mdo-club-hero">
				<small>EL MERCADO DE ORIGEN · CLUB EMDO</small>
				<h2>Mi monedero</h2>
				<p>Recompensas acumuladas con tus compras</p>
				<strong><?php echo wp_kses_post( wc_price( MDO_Club_Rewards_Ledger::format_amount( $balance ) ) ); ?></strong>
			</div>
			<div class="mdo-club-card">
				<h3>Comprar tiene recompensa</h3>
				<?php if ( self::enabled() ) : ?>
					<p>Acumulas el <?php echo esc_html( number_format_i18n( self::rate(), 1 ) ); ?> % del importe de los productos de tus pedidos pagados, sin incluir gastos de envío. Las recompensas se añaden cuando se confirma el pago.</p>
				<?php else : ?><p>Las recompensas están temporalmente pausadas.</p><?php endif; ?>
				<p><small>Las opciones para gastar el saldo, recargar y utilizar tarjetas regalo se habilitarán en una fase posterior.</small></p>
			</div>
			<div class="mdo-club-card">
				<h3>Historial de movimientos</h3>
				<?php if ( ! $movements ) : ?><p>Todavía no tienes recompensas registradas.</p><?php else : ?>
					<table class="mdo-club-table"><thead><tr><th>Fecha</th><th>Concepto</th><th>Importe</th></tr></thead><tbody>
					<?php foreach ( $movements as $movement ) : ?>
						<tr>
							<td><?php echo esc_html( mysql2date( 'd/m/Y', $movement['created_at'], false ) ); ?></td>
							<td><?php echo esc_html( 'reward' === $movement['type'] ? 'Recompensa' : 'Ajuste de saldo' ); ?>
								<?php if ( $movement['order_id'] ) : ?> · pedido #<?php echo (int) $movement['order_id']; ?><?php endif; ?></td>
							<td><?php echo esc_html( ( (int) $movement['delta_cents'] >= 0 ? '+' : '−' ) ); ?><?php echo wp_kses_post( wc_price( MDO_Club_Rewards_Ledger::format_amount( abs( (int) $movement['delta_cents'] ) ) ) ); ?></td>
						</tr>
					<?php endforeach; ?>
					</tbody></table>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
	public static function admin_menu(): void {
		add_submenu_page( 'mdo-supplier-sync', 'Club EMDO', 'Club EMDO', 'manage_woocommerce', 'mdo-club-rewards', array( __CLASS__, 'admin_page' ) );
	}
	private static function admin_url( array $extra = array() ): string {
		return add_query_arg( array_merge( array( 'page' => 'mdo-club-rewards' ), $extra ), admin_url( 'admin.php' ) );
	}
	public static function admin_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Sin permisos.' ); }
		$rate = self::rate();
		$enabled = self::enabled();
		$totals = MDO_Club_Rewards_Ledger::totals();
		$search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$search = substr( $search, 0, 100 );
		$page = isset( $_GET['pnum'] ) ? max( 1, absint( $_GET['pnum'] ) ) : 1;
		$detail_id = isset( $_GET['club_user'] ) ? absint( $_GET['club_user'] ) : 0;
		$args = array( 'number' => 25, 'paged' => $page, 'orderby' => 'registered', 'order' => 'DESC', 'count_total' => true );
		if ( '' !== $search ) {
			if ( ctype_digit( ltrim( $search, '#' ) ) ) {
				$args['include'] = array( absint( ltrim( $search, '#' ) ) );
			} else {
				$args['search'] = '*' . $search . '*';
				$args['search_columns'] = array( 'user_email', 'user_login', 'display_name' );
			}
		}
		$query = new WP_User_Query( $args );
		$users = $query->get_results();
		$balances = MDO_Club_Rewards_Ledger::balances_for_users( wp_list_pluck( $users, 'ID' ) );
		$status = isset( $_GET['club_status'] ) ? sanitize_key( wp_unslash( $_GET['club_status'] ) ) : '';
		?>
		<div class="wrap">
			<h1>Club EMDO · Recompensas y saldos</h1>
			<p>Control del saldo promocional de clientes registrados. Las recargas, tarjetas regalo y pagos con monedero permanecen fuera de esta fase.</p>
			<?php if ( $status ) : ?>
				<div class="notice <?php echo esc_attr( in_array( $status, array( 'saved', 'adjusted' ), true ) ? 'notice-success' : 'notice-error' ); ?> is-dismissible"><p>
					<?php echo esc_html( array(
						'saved' => 'Configuración guardada.',
						'adjusted' => 'Ajuste de saldo aplicado y registrado.',
						'funds' => 'Saldo insuficiente para realizar el ajuste.',
						'invalid' => 'Revisa el importe, el cliente y el motivo (mínimo 5 caracteres).',
					)[ $status ] ?? 'No se pudo completar la operación.' ); ?>
				</p></div>
			<?php endif; ?>
			<div style="display:flex;gap:12px;flex-wrap:wrap;margin:20px 0">
				<div style="background:#fff;border:1px solid #dcdcde;padding:18px;border-radius:10px">
					<strong style="font-size:24px"><?php echo wp_kses_post( wc_price( MDO_Club_Rewards_Ledger::format_amount( (int) $totals['total_cents'] ) ) ); ?></strong><br>Saldo total acumulado
				</div>
				<div style="background:#fff;border:1px solid #dcdcde;padding:18px;border-radius:10px">
					<strong style="font-size:24px"><?php echo (int) $totals['positive_wallets']; ?></strong><br>Clientes con saldo
				</div>
			</div>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="padding:20px;background:#fff;border:1px solid #dcdcde;border-radius:10px;max-width:740px">
				<h2 style="margin-top:0">Configuración de recompensas</h2>
				<?php wp_nonce_field( 'mdo_club_rewards_settings' ); ?>
				<input type="hidden" name="action" value="mdo_club_rewards_settings">
				<p><label><input type="checkbox" name="rewards_enabled" value="yes" <?php checked( $enabled ); ?>> <strong>Abonar automáticamente recompensas por pedidos pagados</strong></label></p>
				<p><label for="mdo-club-rate">Porcentaje de recompensa: </label>
					<input id="mdo-club-rate" type="number" name="rewards_percent" min="0" max="10" step="0.1" value="<?php echo esc_attr( $rate ); ?>"> %</p>
				<p class="description">Por defecto 1 %. Solo importe de productos (IVA incluido, después de descuentos), sin gastos de envío. No se vuelve a acreditar un pedido ya recompensado.</p>
				<?php submit_button( 'Guardar configuración', 'primary', 'submit', false ); ?>
			</form>

			<h2 style="margin-top:30px">Saldos de usuarios</h2>
			<form method="get" style="margin-bottom:12px">
				<input type="hidden" name="page" value="mdo-club-rewards">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Buscar por nombre, email o ID" style="width:310px;max-width:70%">
				<button type="submit" class="button">Buscar</button>
				<?php if ( $search ) : ?><a class="button" href="<?php echo esc_url( self::admin_url() ); ?>">Limpiar</a><?php endif; ?>
			</form>
			<table class="widefat striped">
				<thead><tr><th>Cliente</th><th>Correo</th><th>Saldo</th><th>Modificar saldo (+ / −)</th><th>Historial</th></tr></thead>
				<tbody>
				<?php if ( ! $users ) : ?><tr><td colspan="5">No se han encontrado usuarios.</td></tr><?php endif; ?>
				<?php foreach ( $users as $user ) : ?>
					<tr>
						<td><strong><?php echo esc_html( $user->display_name ); ?></strong><br><small>#<?php echo (int) $user->ID; ?></small></td>
						<td><?php echo esc_html( $user->user_email ); ?></td>
						<td><strong><?php echo wp_kses_post( wc_price( MDO_Club_Rewards_Ledger::format_amount( (int) ( $balances[ $user->ID ] ?? 0 ) ) ) ); ?></strong></td>
						<td>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:7px;flex-wrap:wrap;align-items:center">
								<?php wp_nonce_field( 'mdo_club_reward_adjust_' . $user->ID ); ?>
								<input type="hidden" name="action" value="mdo_club_reward_adjust">
								<input type="hidden" name="club_user_id" value="<?php echo (int) $user->ID; ?>">
								<input type="text" name="club_amount" required placeholder="+5,00 / -2,00" size="11" aria-label="Cantidad positiva o negativa">
								<input type="text" name="club_reason" required minlength="5" maxlength="200" placeholder="Motivo del ajuste" size="24" aria-label="Motivo del ajuste">
								<button type="submit" class="button button-secondary">Aplicar</button>
							</form>
						</td>
						<td><a href="<?php echo esc_url( self::admin_url( array( 'club_user' => $user->ID, 's' => $search ) ) ); ?>">Ver movimientos</a></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php $total_pages = (int) ceil( $query->get_total() / 25 ); ?>
			<?php if ( $total_pages > 1 ) : ?>
				<p><?php echo wp_kses_post( paginate_links( array(
					'base' => add_query_arg( 'pnum', '%#%', self::admin_url( array( 's' => $search ) ) ),
					'format' => '', 'current' => $page, 'total' => $total_pages,
				) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $detail_id > 0 ) : self::admin_movements( $detail_id ); endif; ?>
		</div>
		<?php
	}
	private static function admin_movements( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) { return; }
		$entries = MDO_Club_Rewards_Ledger::movements( $user_id, 100 );
		?>
		<h2>Movimientos de <?php echo esc_html( $user->display_name ); ?> (#<?php echo (int) $user_id; ?>)</h2>
		<p>Saldo actual: <strong><?php echo wp_kses_post( wc_price( MDO_Club_Rewards_Ledger::format_amount( MDO_Club_Rewards_Ledger::balance( $user_id ) ) ) ); ?></strong></p>
		<table class="widefat striped"><thead><tr><th>Fecha (UTC)</th><th>Tipo</th><th>Importe</th><th>Pedido</th><th>Administrador</th><th>Motivo</th></tr></thead><tbody>
			<?php if ( ! $entries ) : ?><tr><td colspan="6">Sin movimientos.</td></tr><?php endif; ?>
			<?php foreach ( $entries as $entry ) : ?>
				<tr><td><?php echo esc_html( $entry['created_at'] ); ?></td>
				<td><?php echo esc_html( 'reward' === $entry['type'] ? 'Recompensa' : 'Ajuste manual' ); ?></td>
				<td><?php echo esc_html( ( (int) $entry['delta_cents'] >= 0 ? '+' : '−' ) . MDO_Club_Rewards_Ledger::format_amount( abs( (int) $entry['delta_cents'] ) ) . ' €' ); ?></td>
				<td><?php echo $entry['order_id'] ? '#' . (int) $entry['order_id'] : '—'; ?></td>
				<td><?php echo $entry['admin_id'] ? '#' . (int) $entry['admin_id'] : 'Automático'; ?></td>
				<td><?php echo esc_html( $entry['note'] ); ?></td></tr>
			<?php endforeach; ?>
		</tbody></table>
		<?php
	}
	public static function save_settings(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Sin permisos.' ); }
		check_admin_referer( 'mdo_club_rewards_settings' );
		$raw = isset( $_POST['rewards_percent'] ) ? sanitize_text_field( wp_unslash( $_POST['rewards_percent'] ) ) : '1';
		$rate = is_numeric( str_replace( ',', '.', $raw ) ) ? (float) str_replace( ',', '.', $raw ) : 1;
		update_option( 'mdo_club_rewards_percent', (string) min( 10, max( 0, round( $rate, 1 ) ) ), false );
		update_option( 'mdo_club_rewards_enabled', isset( $_POST['rewards_enabled'] ) ? 'yes' : 'no', false );
		wp_safe_redirect( self::admin_url( array( 'club_status' => 'saved' ) ) ); exit;
	}
	public static function adjust_balance(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Sin permisos.' ); }
		$user_id = isset( $_POST['club_user_id'] ) ? absint( $_POST['club_user_id'] ) : 0;
		check_admin_referer( 'mdo_club_reward_adjust_' . $user_id );
		$raw = isset( $_POST['club_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['club_amount'] ) ) : '';
		$delta = MDO_Club_Rewards_Ledger::parse_adjustment( $raw );
		$reason = isset( $_POST['club_reason'] ) ? sanitize_text_field( wp_unslash( $_POST['club_reason'] ) ) : '';
		$reason = substr( $reason, 0, 200 );
		if ( ! get_user_by( 'id', $user_id ) || 0 === $delta || strlen( $reason ) < 5 ) {
			$status = 'invalid';
		} else {
			$event_key = 'adjust:' . wp_generate_uuid4();
			$status = MDO_Club_Rewards_Ledger::change(
				$user_id, $delta, $event_key, 'adjustment', 0, get_current_user_id(), $reason
			) ? 'adjusted' : 'funds';
		}
		wp_safe_redirect( self::admin_url( array( 'club_status' => $status, 'club_user' => $user_id ) ) );
		exit;
	}
}
