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
			'mdo-supplier-sync', 'Saldos Club EMDO', 'Saldos Club EMDO',
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
		$page = isset( $_GET['pnum'] ) ? max( 1, absint( $_GET['pnum'] ) ) : 1;
		$search = isset( $_GET['s'] ) ? substr( sanitize_text_field( wp_unslash( $_GET['s'] ) ), 0, 100 ) : '';
		$detail_id = isset( $_GET['club_user'] ) ? absint( $_GET['club_user'] ) : 0;
		$args = array(
			'number' => 25, 'paged' => $page, 'orderby' => 'registered',
			'order' => 'DESC', 'count_total' => true,
		);
		if ( '' !== $search ) {
			$normalized = ltrim( $search, '#' );
			if ( ctype_digit( $normalized ) ) {
				$args['include'] = array( absint( $normalized ) );
			} else {
				$args['search'] = '*' . $search . '*';
				$args['search_columns'] = array( 'user_email', 'user_login', 'display_name' );
			}
		}
		$query = new WP_User_Query( $args );
		$users = $query->get_results();
		$ids = array_map( 'intval', wp_list_pluck( $users, 'ID' ) );
		$balances = MDO_Club_Ledger::balances_for_users( $ids );
		$status = isset( $_GET['club_status'] ) ? sanitize_key( wp_unslash( $_GET['club_status'] ) ) : '';
		$totals = MDO_Club_Ledger::totals();
		?>
		<div class="wrap">
			<h1>Club EMDO · Saldos de usuarios</h1>
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
					<strong style="font-size:24px"><?php echo (int) $totals['positive_wallets']; ?></strong><br>Usuarios con saldo
				</div>
			</div>
			<h2>Buscar clientes</h2>
			<form method="get" style="margin:12px 0">
				<input type="hidden" name="page" value="mdo-club-balances">
				<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Nombre, correo o ID" style="min-width:250px">
				<button class="button" type="submit">Buscar</button>
				<?php if ( $search ) : ?><a class="button" href="<?php echo esc_url( self::url() ); ?>">Limpiar</a><?php endif; ?>
			</form>
			<table class="widefat striped">
				<thead><tr><th>Cliente</th><th>Correo</th><th>Saldo disponible</th><th>Ajuste manual</th><th>Movimientos</th></tr></thead>
				<tbody>
					<?php if ( ! $users ) : ?><tr><td colspan="5">No se encontraron usuarios.</td></tr><?php endif; ?>
					<?php foreach ( $users as $user ) : ?>
						<tr>
							<td><strong><?php echo esc_html( $user->display_name ); ?></strong><br><small>#<?php echo (int) $user->ID; ?></small></td>
							<td><?php echo esc_html( $user->user_email ); ?></td>
							<td><strong><?php echo wp_kses_post( wc_price( MDO_Club_Ledger::money( (int) ( $balances[ $user->ID ] ?? 0 ) ) ) ); ?></strong></td>
							<td><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:flex;gap:6px;flex-wrap:wrap">
								<?php wp_nonce_field( 'mdo_club_adjust_' . $user->ID ); ?>
								<input type="hidden" name="action" value="mdo_club_adjust">
								<input type="hidden" name="club_user" value="<?php echo (int) $user->ID; ?>">
								<input type="text" name="club_delta" placeholder="+5,00 / -2,00" size="12" required aria-label="Importe positivo o negativo">
								<input type="text" name="club_note" placeholder="Motivo del ajuste" size="23" minlength="5" maxlength="200" required aria-label="Motivo">
								<button class="button button-secondary" type="submit">Aplicar</button>
							</form></td>
							<td><a href="<?php echo esc_url( self::url( array( 'club_user' => $user->ID, 's' => $search ) ) ); ?>">Ver historial</a></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php $total_pages = (int) ceil( $query->get_total() / 25 ); ?>
			<?php if ( $total_pages > 1 ) : ?>
				<p><?php echo wp_kses_post( paginate_links( array(
					'base' => add_query_arg( 'pnum', '%#%', self::url( array( 's' => $search ) ) ),
					'format' => '', 'current' => $page, 'total' => $total_pages,
				) ) ); ?></p>
			<?php endif; ?>
			<?php if ( $detail_id ) : self::history( $detail_id ); endif; ?>
		</div>
		<?php
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