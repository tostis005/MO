<?php
/**
 * Club EMDO: consulta y corrección manual del monedero único.
 * Ajustes manuales trazables; los reembolsos no modifican automáticamente saldos.
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

final class MDO_Club_Admin {
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 65 );
		add_action( 'admin_post_mdo_club_adjust', array( __CLASS__, 'adjust' ) );
	}
	public static function menu(): void {
		add_submenu_page(
			'mdo-supplier-sync', 'Club EMDO · Saldos y tarjetas', 'Saldos y tarjetas',
			'manage_woocommerce', 'mdo-club-balances', array( __CLASS__, 'render' )
		);
	}
	private static function url( array $params = array() ): string {
		return add_query_arg( array_merge( array( 'page' => 'mdo-club-balances' ), $params ), admin_url( 'admin.php' ) );
	}
	/**
	 * Bounded signed decimal euro amount. Reject exponent notation and more
	 * than two decimals so 5,25 and -5.25 are unambiguous.
	 */
	public static function parse_amount( $raw ): int {
		if ( ! is_string( $raw ) || ! preg_match( '/^[+-]?\d{1,4}(?:[.,]\d{1,2})?$/', trim( $raw ) ) ) {
			return 0;
		}
		$cents = (int) round( (float) str_replace( ',', '.', trim( $raw ) ) * 100 );
		return abs( $cents ) <= 500000 ? $cents : 0;
	}
	public static function adjust(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Sin permisos para gestionar saldos.' ); }
		$user_id = isset( $_POST['club_user'] ) ? absint( $_POST['club_user'] ) : 0;
		check_admin_referer( 'mdo_club_adjust_' . $user_id );
		$raw = isset( $_POST['club_delta'] ) ? sanitize_text_field( wp_unslash( $_POST['club_delta'] ) ) : '';
		$note = isset( $_POST['club_note'] ) ? sanitize_text_field( wp_unslash( $_POST['club_note'] ) ) : '';
		$note = substr( trim( $note ), 0, 200 );
		$delta = self::parse_amount( $raw );
		if ( $user_id <= 0 || ! get_user_by( 'id', $user_id ) || 0 === $delta || strlen( $note ) < 5 ) {
			$status = 'invalid';
		} else {
			$event = 'adjust:' . wp_generate_uuid4();
			$status = MDO_Club_Ledger::change(
				$user_id, $delta, $event, 'adjustment', 0, false, get_current_user_id(), $note
			) ? 'done' : 'funds';
		}
		wp_safe_redirect( self::url( array( 'club_status' => $status, 'club_user' => $user_id ) ) );
		exit;
	}
	public static function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( 'Sin permisos para gestionar saldos.' ); }
		if ( 'gifts' === ( isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '' ) ) {
			self::render_gifts();
			return;
		}
		$page = isset( $_GET['pnum'] ) ? max( 1, absint( $_GET['pnum'] ) ) : 1;
		$search = isset( $_GET['s'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['s'] ) ), 0, 100 ) : '';
		$detail_id = isset( $_GET['club_user'] ) ? absint( $_GET['club_user'] ) : 0;
		$balance_filter = isset( $_GET['w_balance'] ) ? sanitize_key( wp_unslash( $_GET['w_balance'] ) ) : 'all';
		if ( ! in_array( $balance_filter, array( 'all', 'positive', 'zero' ), true ) ) { $balance_filter = 'all'; }
		$sort = isset( $_GET['w_sort'] ) ? sanitize_key( wp_unslash( $_GET['w_sort'] ) ) : 'balance';
		if ( ! in_array( $sort, array( 'balance', 'name', 'email', 'registered', 'id' ), true ) ) { $sort = 'balance'; }
		$direction = isset( $_GET['w_dir'] ) && 'asc' === sanitize_key( wp_unslash( $_GET['w_dir'] ) ) ? 'asc' : 'desc';
		$report = MDO_Club_Ledger::wallet_report( $page, 25, $search, $balance_filter, $sort, $direction );
		$users = $report['rows'];
		$filters = array( 's' => $search, 'w_balance' => $balance_filter, 'w_sort' => $sort, 'w_dir' => $direction );
		$status = isset( $_GET['club_status'] ) ? sanitize_key( wp_unslash( $_GET['club_status'] ) ) : '';
		$totals = MDO_Club_Ledger::totals();
		?>
		<div class="wrap">
			<h1>Club EMDO · Administración</h1>
			<?php self::tabs( 'wallets' ); ?>
			<h2>Saldos de usuarios</h2>
			<p>Un único monedero acumula recargas, tarjetas regalo y recompensas. Los ajustes son manuales y quedan registrados con administrador y motivo. Las devoluciones no generan movimientos automáticos.</p>
			<?php if ( $status ) : ?>
				<div class="notice <?php echo esc_attr( 'done' === $status ? 'notice-success' : 'notice-error' ); ?> is-dismissible">
					<p><?php echo esc_html( array(
						'done' => 'Saldo actualizado y movimiento registrado.',
						'invalid' => 'Selecciona un cliente, un importe válido y un motivo de al menos cinco caracteres.',
						'funds' => 'No se pudo aplicar el ajuste. Comprueba que no deje el saldo negativo.',
					)[ $status ] ?? 'Operación no completada.' ); ?></p>
				</div>
			<?php endif; ?>
			<div style="display:flex;gap:12px;margin:18px 0;flex-wrap:wrap">
				<div style="padding:18px;background:#fff;border:1px solid #dcdcde;border-radius:10px">
					<strong style="font-size:24px"><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( (int) $totals['balance_cents'] ) ) ); ?></strong><br>Saldo total de clientes
				</div>
				<div style="padding:18px;background:#fff;border:1px solid #dcdcde;border-radius:10px">
					<strong style="font-size:24px"><?php echo (int) $totals['positive_wallets']; ?></strong><br><a href="<?php echo esc_url( self::url( array( 'w_balance' => 'positive', 'w_sort' => 'balance', 'w_dir' => 'desc' ) ) ); ?>">Ver solo usuarios con saldo</a>
				</div>
			</div>
			<h2>Buscar y ordenar clientes</h2>
			<form method="get" style="margin:12px 0">
				<input type="hidden" name="page" value="mdo-club-balances">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Nombre, correo o ID" style="min-width:240px">
				<label for="mdo-club-balance-filter">Saldo:</label>
				<select id="mdo-club-balance-filter" name="w_balance">
					<option value="all" <?php selected( $balance_filter, 'all' ); ?>>Todos los usuarios</option>
					<option value="positive" <?php selected( $balance_filter, 'positive' ); ?>>Solo con saldo</option>
					<option value="zero" <?php selected( $balance_filter, 'zero' ); ?>>Sin saldo</option>
				</select>
				<label for="mdo-club-wallet-sort">Ordenar por:</label>
				<select id="mdo-club-wallet-sort" name="w_sort">
					<option value="balance" <?php selected( $sort, 'balance' ); ?>>Saldo</option>
					<option value="name" <?php selected( $sort, 'name' ); ?>>Nombre</option>
					<option value="email" <?php selected( $sort, 'email' ); ?>>Correo</option>
					<option value="registered" <?php selected( $sort, 'registered' ); ?>>Fecha de registro</option>
					<option value="id" <?php selected( $sort, 'id' ); ?>>ID de usuario</option>
				</select>
				<select name="w_dir" aria-label="Sentido de ordenación">
					<option value="desc" <?php selected( $direction, 'desc' ); ?>>Descendente</option>
					<option value="asc" <?php selected( $direction, 'asc' ); ?>>Ascendente</option>
				</select>
				<button class="button button-primary" type="submit">Aplicar filtros</button>
				<?php if ( $search || 'all' !== $balance_filter || 'balance' !== $sort || 'desc' !== $direction ) : ?>
					<a class="button" href="<?php echo esc_url( self::url() ); ?>">Limpiar</a>
				<?php endif; ?>
			</form>
			<p><strong><?php echo (int) $report['total']; ?></strong> usuarios encontrados.</p>
			<table class="widefat striped">
				<thead><tr>
					<?php self::sort_th( 'Cliente', 'name', $sort, $direction, 'w_sort', 'w_dir', $filters ); ?>
					<?php self::sort_th( 'Correo', 'email', $sort, $direction, 'w_sort', 'w_dir', $filters ); ?>
					<?php self::sort_th( 'Saldo disponible', 'balance', $sort, $direction, 'w_sort', 'w_dir', $filters ); ?>
					<th>Ajuste manual</th><th>Movimientos</th>
				</tr></thead>
				<tbody>
					<?php if ( ! $users ) : ?><tr><td colspan="5">No se encontraron usuarios con estos filtros. Prueba «Todos los usuarios».</td></tr><?php endif; ?>
					<?php foreach ( $users as $user ) :
						$user_id = (int) $user['user_id']; ?>
						<tr>
							<td><strong><?php echo esc_html( $user['display_name'] ); ?></strong><br><small>#<?php echo $user_id; ?></small></td>
							<td><?php echo esc_html( $user['user_email'] ); ?></td>
							<td><strong><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( (int) $user['balance_cents'] ) ) ); ?></strong></td>
							<td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:6px;flex-wrap:wrap">
								<?php wp_nonce_field( 'mdo_club_adjust_' . $user_id ); ?>
								<input type="hidden" name="action" value="mdo_club_adjust">
								<input type="hidden" name="club_user" value="<?php echo (int) $user_id; ?>">
								<input type="text" name="club_delta" placeholder="+5,00 / -2,00" size="12" required aria-label="Importe positivo o negativo">
								<input type="text" name="club_note" placeholder="Motivo del ajuste" size="23" minlength="5" maxlength="200" required aria-label="Motivo">
								<button class="button button-secondary" type="submit">Aplicar</button>
							</form></td>
							<td><a href="<?php echo esc_url( self::url( array_merge( $filters, array( 'club_user' => $user_id ) ) ) ); ?>">Ver historial</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php $total_pages = (int) ceil( $report['total'] / 25 ); ?>
			<?php if ( $total_pages > 1 ) : ?>
				<p><?php echo wp_kses_post( paginate_links( array(
					'base' => add_query_arg( 'pnum', '%#%', self::url( $filters ) ),
					'format' => '', 'current' => $page, 'total' => $total_pages,
				) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $detail_id ) : self::history( $detail_id ); endif; ?>
		</div>
		<?php
	}
	/**
	 * Server-side sortable column link. Whitelisted SQL columns are selected
	 * in the ledger; this helper only changes URL parameters.
	 */
	private static function sort_th( string $label, string $key, string $sort, string $direction, string $sort_param, string $direction_param, array $filters ): void {
		$active = $sort === $key;
		$next_direction = $active && 'asc' === $direction ? 'desc' : 'asc';
		$params = array_merge( $filters, array(
			$sort_param => $key, $direction_param => $next_direction,
		) );
		$attr = $active ? ' aria-sort="' . ( 'asc' === $direction ? 'ascending' : 'descending' ) . '"' : '';
		echo '<th' . $attr . '><a href="' . esc_url( self::url( $params ) ) . '">'
			. esc_html( $label ) . ( $active ? ' ' . ( 'asc' === $direction ? '▲' : '▼' ) : ' ↕' )
			. '</a></th>';
	}
	/** Two views within the existing admin URL, preserving old bookmarks. */
	private static function tabs( string $active ): void {
		$tabs = array(
			'wallets' => array( 'Saldos de usuarios', self::url() ),
			'gifts' => array( 'Tarjetas regalo', self::url( array( 'tab' => 'gifts' ) ) ),
		);
		echo '<nav class="nav-tab-wrapper" aria-label="Secciones de administración de Club EMDO" style="margin-bottom:22px">';
		foreach ( $tabs as $key => $tab ) {
			echo '<a class="nav-tab' . ( $active === $key ? ' nav-tab-active' : '' ) . '" href="' . esc_url( $tab[1] ) . '">'
				. esc_html( $tab[0] ) . '</a>';
		}
		echo '</nav>';
	}

	/** Only report issued gifts, not WooCommerce orders awaiting payment. */
	private static function render_gifts(): void {
		$page = isset( $_GET['gp' ] ) ? max( 1, absint( $_GET['gp'] ) ) : 1;
		$search = isset( $_GET['gs'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['gs'] ) ), 0, 100 ) : '';
		$status = isset( $_GET['gstatus'] ) ? sanitize_key( wp_unslash( $_GET['gstatus'] ) ) : '';
		if ( ! in_array( $status, array( '', 'unredeemed', 'redeemed' ), true ) ) { $status = ''; }
		$stats = MDO_Club_Ledger::gift_report_totals();
		$report = MDO_Club_Ledger::gift_report( $page, 25, $search, $status );
		$rows = $report['rows'];
		?>
		<div class="wrap" id="mdo-club-admin-panel">
			<h1>Club EMDO · Administración</h1>
			<?php self::tabs( 'gifts' ); ?>
			<h2>Tarjetas regalo adquiridas</h2>
			<p>Se muestran las tarjetas emitidas tras confirmarse el pago. Los pedidos pendientes de pago siguen en WooCommerce.
				Los códigos de canje no se muestran por seguridad.</p>
			<style>
				#mdo-club-admin-panel .mdo-club-gift-stats{display:flex;flex-wrap:wrap;gap:12px;margin:19px 0 22px}
				#mdo-club-admin-panel .mdo-club-gift-stat{min-width:155px;flex:1 1 160px;max-width:275px;padding:16px 19px;border:1px solid #dcdcde;border-radius:10px;background:#fff}
				#mdo-club-admin-panel .mdo-club-gift-stat strong{display:block;margin-bottom:4px;font-size:23px;line-height:1.3;white-space:nowrap}
				#mdo-club-admin-panel .mdo-club-gift-stat span{color:#50575e}
				#mdo-club-admin-panel .mdo-club-gift-table-wrap{max-width:100%;overflow-x:auto}
				#mdo-club-admin-panel .mdo-club-gift-table{min-width:1000px}
				#mdo-club-admin-panel .mdo-club-gift-table td{vertical-align:top;padding-top:14px;padding-bottom:14px}
				#mdo-club-admin-panel .mdo-club-gift-table small{display:block;color:#646970;margin-top:3px}
				#mdo-club-admin-panel .mdo-club-gift-status{display:inline-block;padding:3px 10px;border-radius:30px;background:#e8f2e8;color:#24502d;font-weight:650;white-space:nowrap}
				#mdo-club-admin-panel .mdo-club-gift-status--pending{background:#fff1d4;color:#79531c}
				#mdo-club-admin-panel .mdo-club-gift-filters{display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin:17px 0}
			</style>
			<div class="mdo-club-gift-stats">
				<div class="mdo-club-gift-stat"><strong><?php echo (int) $stats['issued_count']; ?></strong><span>Tarjetas emitidas</span></div>
				<div class="mdo-club-gift-stat"><strong><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $stats['issued_cents'] ) ) ); ?></strong><span>Importe emitido</span></div>
				<div class="mdo-club-gift-stat"><strong><?php echo (int) $stats['unredeemed_count']; ?></strong><span>Tarjetas sin canjear</span></div>
				<div class="mdo-club-gift-stat"><strong><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( $stats['unredeemed_cents'] ) ) ); ?></strong><span>Importe pendiente de canje</span></div>
				<div class="mdo-club-gift-stat"><strong><?php echo (int) $stats['redeemed_count']; ?></strong><span>Tarjetas canjeadas</span></div>
			</div>
			<form method="get" class="mdo-club-gift-filters">
				<input type="hidden" name="page" value="mdo-club-balances">
				<input type="hidden" name="tab" value="gifts">
				<label class="screen-reader-text" for="mdo-club-gift-search">Buscar tarjetas</label>
				<input id="mdo-club-gift-search" type="search" name="gs" value="<?php echo esc_attr( $search ); ?>"
					placeholder="Correo destinatario o nº pedido" style="min-width:265px">
				<label class="screen-reader-text" for="mdo-club-gift-status">Estado de canje</label>
				<select id="mdo-club-gift-status" name="gstatus">
					<option value="">Todos los estados</option>
					<option value="unredeemed" <?php selected( $status, 'unredeemed' ); ?>>Sin canjear</option>
					<option value="redeemed" <?php selected( $status, 'redeemed' ); ?>>Canjeadas</option>
				</select>
				<button class="button" type="submit">Filtrar</button>
				<?php if ( '' !== $search || '' !== $status ) : ?>
					<a class="button button-secondary" href="<?php echo esc_url( self::url( array( 'tab' => 'gifts' ) ) ); ?>">Limpiar</a>
				<?php endif; ?>
			</form>
			<p><?php echo (int) $report['total']; ?> tarjetas encontradas.</p>
			<div class="mdo-club-gift-table-wrap">
			<table class="widefat striped mdo-club-gift-table">
				<thead><tr>
					<th>Fecha de emisión</th><th>Pedido</th><th>Comprador</th><th>Destinatario</th>
					<th>Importe</th><th>Estado</th><th>Canjeada por</th><th>Correo enviado</th>
				</tr></thead>
				<tbody>
				<?php if ( ! $rows ) : ?><tr><td colspan="8">No hay tarjetas regalo para los filtros seleccionados.</td></tr><?php endif; ?>
				<?php foreach ( $rows as $gift ) :
					$order = wc_get_order( (int) $gift['order_id'] );
					$buyer_user_id = $order ? (int) $order->get_customer_id() : 0;
					$buyer_user = $buyer_user_id ? get_user_by( 'id', $buyer_user_id ) : false;
					$buyer_name = $buyer_user ? $buyer_user->display_name : '';
					if ( ! $buyer_name && $order ) {
						$buyer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
					}
					if ( ! $buyer_name ) { $buyer_name = $order ? 'Cliente invitado' : 'Pedido no disponible'; }
					$buyer_email = $order ? $order->get_billing_email() : '';
					$recipient_user = get_user_by( 'email', (string) $gift['recipient_email'] );
					$redeemed = (int) $gift['redeemed_user_id'] > 0;
					$redeemer = $redeemed ? get_user_by( 'id', (int) $gift['redeemed_user_id'] ) : false;
					$order_item = $order ? $order->get_item( (int) $gift['item_id'] ) : false;
					$sent = $order_item && 'yes' === $order_item->get_meta( '_mdo_club_gift_sent' );
				?>
				<tr>
					<td><?php echo esc_html( self::local_date( (string) $gift['created_at'] ) ); ?></td>
					<td>
						<?php if ( $order ) : ?>
							<a href="<?php echo esc_url( $order->get_edit_order_url() ); ?>">#<?php echo (int) $gift['order_id']; ?></a>
							<small><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></small>
						<?php else : ?>#<?php echo (int) $gift['order_id']; ?><?php endif; ?>
					</td>
					<td>
						<strong><?php echo esc_html( $buyer_name ); ?></strong>
						<small><?php echo esc_html( $buyer_email ); ?></small>
						<?php if ( $buyer_user ) : ?><small>Usuario #<?php echo (int) $buyer_user_id; ?></small><?php endif; ?>
					</td>
					<td>
						<?php echo esc_html( $gift['recipient_email'] ); ?>
						<?php if ( $recipient_user ) : ?>
							<small><?php echo esc_html( $recipient_user->display_name ); ?> · Usuario #<?php echo (int) $recipient_user->ID; ?></small>
						<?php endif; ?>
					</td>
					<td><strong><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( (int) $gift['amount_cents'] ) ) ); ?></strong></td>
					<td>
						<span class="mdo-club-gift-status<?php echo $redeemed ? '' : ' mdo-club-gift-status--pending'; ?>">
							<?php echo $redeemed ? 'Canjeada' : 'Sin canjear'; ?>
						</span>
						<?php if ( $redeemed && $gift['redeemed_at'] ) : ?>
							<small><?php echo esc_html( self::local_date( (string) $gift['redeemed_at'] ) ); ?></small>
						<?php endif; ?>
					</td>
					<td>
						<?php if ( $redeemed ) : ?>
							<?php echo esc_html( $redeemer ? $redeemer->display_name : 'Usuario #' . (int) $gift['redeemed_user_id'] ); ?>
							<small>#<?php echo (int) $gift['redeemed_user_id']; ?></small>
						<?php else : ?>—<?php endif; ?>
					</td>
					<td><?php echo $order_item ? ( $sent ? 'Sí' : 'Pendiente de revisar' ) : '—'; ?></td>
				</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			</div>
			<?php
			$total_pages = (int) ceil( $report['total'] / 25 );
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo wp_kses_post( paginate_links( array(
					'base' => add_query_arg( 'gp', '%#%', self::url( array(
						'tab' => 'gifts', 'gs' => $search, 'gstatus' => $status,
					) ) ),
					'format' => '', 'current' => $page, 'total' => $total_pages,
				) ) );
				echo '</div></div>';
			}
			?>
			<p class="description">Las tarjetas sin canjear todavía no se han incorporado a ningún monedero.
				Una tarjeta canjeada pasa al saldo del usuario que la canjeó. Las devoluciones se revisan manualmente.</p>
		</div>
		<?php
	}
	private static function local_date( string $utc ): string {
		$timestamp = strtotime( $utc . ' UTC' );
		return $timestamp ? wp_date( 'd/m/Y H:i', $timestamp ) : $utc;
	}
	private static function history( int $user_id ): void {
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) { return; }
		$rows = MDO_Club_Ledger::movements( $user_id, 100 );
		?>
		<h2>Historial de <?php echo esc_html( $user->display_name ); ?> (#<?php echo (int) $user_id; ?>)</h2>
		<table class="widefat striped">
			<thead><tr><th>Fecha UTC</th><th>Movimiento</th><th>Importe</th><th>Pedido</th><th>Administrador</th><th>Motivo</th></tr></thead>
			<tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="6">Sin movimientos.</td></tr><?php endif; ?>
			<?php foreach ( $rows as $row ) : ?>
				<tr>
					<td><?php echo esc_html( $row['created_at'] ); ?></td>
					<td><?php echo esc_html( array(
						'wallet' => 'Recarga', 'gift' => 'Tarjeta regalo', 'reward' => 'Recompensa',
						'spend' => 'Pago de pedido', 'release' => 'Saldo liberado',
						'adjustment' => 'Ajuste manual',
					)[ $row['kind'] ] ?? $row['kind'] ); ?></td>
					<td><?php echo esc_html( ( (int) $row['amount_cents'] < 0 ? '−' : '+' ) . MDO_Club_Ledger::money( abs( (int) $row['amount_cents'] ) ) . ' €' ); ?></td>
					<td><?php echo $row['order_id'] ? '#' . (int) $row['order_id'] : '—'; ?></td>
					<td><?php echo $row['admin_id'] ? '#' . (int) $row['admin_id'] : 'Automático'; ?></td>
					<td><?php echo esc_html( $row['note'] ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}