<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Admin {
	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_post_mdo_save_supplier', array( __CLASS__, 'save_supplier' ) );
		add_action( 'admin_post_mdo_run_supplier', array( __CLASS__, 'run_supplier' ) );
		add_action( 'admin_notices', array( __CLASS__, 'dependency_notice' ) );
	}

	public static function menu(): void {
		$capability = 'manage_woocommerce';
		add_menu_page( 'EMDO', 'EMDO', $capability, 'mdo-supplier-sync', array( __CLASS__, 'dashboard' ), 'dashicons-update', 56 );
		add_submenu_page( 'mdo-supplier-sync', 'Resumen', 'Resumen', $capability, 'mdo-supplier-sync', array( __CLASS__, 'dashboard' ) );
		add_submenu_page( 'mdo-supplier-sync', 'Proveedores', 'Proveedores', $capability, 'mdo-supplier-sync-suppliers', array( __CLASS__, 'suppliers' ) );
		add_submenu_page( 'mdo-supplier-sync', 'Productos origen', 'Productos origen', $capability, 'mdo-supplier-sync-products', array( __CLASS__, 'products' ) );
		add_submenu_page( 'mdo-supplier-sync', 'Historial', 'Historial', $capability, 'mdo-supplier-sync-history', array( __CLASS__, 'history' ) );
	}

	public static function assets( string $hook ): void {
		if ( false === strpos( $hook, 'mdo-supplier-sync' ) ) {
			return;
		}
		wp_enqueue_style( 'mdo-supplier-sync-admin', MDO_SUPPLIER_SYNC_URL . 'assets/admin.css', array(), MDO_SUPPLIER_SYNC_VERSION );
	}

	public static function dependency_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		if ( ! class_exists( 'WooCommerce' ) ) {
			echo '<div class="notice notice-warning"><p><strong>EMDO:</strong> WooCommerce no está activo. La gestión de proveedores funcionará, pero no se podrán crear ni actualizar productos.</p></div>';
		}
	}

	public static function dashboard(): void {
		self::guard();
		global $wpdb;
		$suppliers = MDO_Database::table( 'suppliers' );
		$products  = MDO_Database::table( 'source_products' );
		$runs      = MDO_Database::table( 'sync_runs' );
		$stats = array(
			'suppliers' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$suppliers}" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'active' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$suppliers} WHERE active = 1" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'pending' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$products} WHERE status = 'pending'" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			'errors' => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$runs} WHERE status = 'error' AND started_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ), // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		);
		$recent = $wpdb->get_results( "SELECT r.*, s.name supplier_name FROM {$runs} r LEFT JOIN {$suppliers} s ON s.id = r.supplier_id ORDER BY r.id DESC LIMIT 10", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		?>
		<div class="wrap mdo-sync-wrap">
			<h1>EMDO</h1>
			<p class="description">Centro de control de proveedores y sincronizaciones de El Mercado de Origen.</p>
			<div class="mdo-cards">
				<?php self::card( 'Proveedores', $stats['suppliers'] ); ?>
				<?php self::card( 'Activos', $stats['active'] ); ?>
				<?php self::card( 'Pendientes', $stats['pending'] ); ?>
				<?php self::card( 'Errores 30 días', $stats['errors'] ); ?>
			</div>
			<div class="mdo-panel">
				<h2>Últimas ejecuciones</h2>
				<?php self::runs_table( $recent ); ?>
			</div>
		</div>
		<?php
	}

	public static function suppliers(): void {
		self::guard();
		$id        = isset( $_GET['supplier_id'] ) ? absint( $_GET['supplier_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$editing   = $id ? MDO_Supplier_Repository::find( $id ) : null;
		$suppliers = MDO_Supplier_Repository::all();
		?>
		<div class="wrap mdo-sync-wrap">
			<h1 class="wp-heading-inline">Proveedores</h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=mdo-supplier-sync-suppliers&new=1' ) ); ?>" class="page-title-action">Añadir proveedor</a>
			<hr class="wp-header-end">
			<?php if ( $editing || isset( $_GET['new'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
				<?php self::supplier_form( $editing ); ?>
			<?php else : ?>
				<div class="mdo-panel">
					<table class="widefat striped">
						<thead><tr><th>Proveedor</th><th>Código</th><th>Web origen</th><th>Vendedor</th><th>Regla comercial</th><th>Frecuencia</th><th>Estado</th><th></th></tr></thead>
						<tbody>
						<?php if ( ! $suppliers ) : ?>
							<tr><td colspan="8">Todavía no hay proveedores configurados.</td></tr>
						<?php endif; ?>
						<?php foreach ( $suppliers as $supplier ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $supplier['name'] ); ?></strong></td>
								<td><code><?php echo esc_html( $supplier['code'] ); ?></code></td>
								<td><a href="<?php echo esc_url( $supplier['source_url'] ); ?>" target="_blank" rel="noopener">Abrir web</a></td>
								<td><?php echo esc_html( self::vendor_label( (int) $supplier['vendor_user_id'] ) ); ?></td>
								<td><?php echo esc_html( self::commercial_label( $supplier ) ); ?></td>
								<td><?php echo esc_html( ucfirst( $supplier['sync_frequency'] ) ); ?></td>
								<td><?php echo $supplier['active'] ? '<span class="mdo-status is-ok">Activo</span>' : '<span class="mdo-status">Inactivo</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
								<td><a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=mdo-supplier-sync-suppliers&supplier_id=' . (int) $supplier['id'] ) ); ?>">Gestionar</a></td>
							</tr>
						<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public static function products(): void {
		self::guard();
		global $wpdb;
		$products  = MDO_Database::table( 'source_products' );
		$suppliers = MDO_Database::table( 'suppliers' );
		$rows = $wpdb->get_results( "SELECT p.*, s.name supplier_name FROM {$products} p LEFT JOIN {$suppliers} s ON s.id = p.supplier_id ORDER BY p.id DESC LIMIT 250", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		?>
		<div class="wrap mdo-sync-wrap"><h1>Productos origen</h1><div class="mdo-panel">
		<table class="widefat striped"><thead><tr><th>Proveedor</th><th>Producto</th><th>Estado</th><th>WooCommerce</th><th>Última detección</th><th>Origen</th></tr></thead><tbody>
		<?php if ( ! $rows ) : ?><tr><td colspan="6">Aún no se ha analizado ningún catálogo.</td></tr><?php endif; ?>
		<?php foreach ( $rows as $row ) : ?><tr>
			<td><?php echo esc_html( $row['supplier_name'] ?: '#' . $row['supplier_id'] ); ?></td>
			<td><?php echo esc_html( $row['title'] ?: '(sin título)' ); ?></td>
			<td><span class="mdo-status status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( ucfirst( $row['status'] ) ); ?></span></td>
			<td><?php echo $row['wc_product_id'] ? '<a href="' . esc_url( get_edit_post_link( (int) $row['wc_product_id'] ) ) . '">#' . (int) $row['wc_product_id'] . '</a>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
			<td><?php echo esc_html( $row['last_seen_at'] ); ?></td>
			<td><a href="<?php echo esc_url( $row['source_url'] ); ?>" target="_blank" rel="noopener">Ver</a></td>
		</tr><?php endforeach; ?>
		</tbody></table></div></div>
		<?php
	}

	public static function history(): void {
		self::guard();
		global $wpdb;
		$runs      = MDO_Database::table( 'sync_runs' );
		$suppliers = MDO_Database::table( 'suppliers' );
		$rows = $wpdb->get_results( "SELECT r.*, s.name supplier_name FROM {$runs} r LEFT JOIN {$suppliers} s ON s.id = r.supplier_id ORDER BY r.id DESC LIMIT 200", ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		?>
		<div class="wrap mdo-sync-wrap"><h1>Historial de sincronizaciones</h1><div class="mdo-panel"><?php self::runs_table( $rows ); ?></div></div>
		<?php
	}

	public static function save_supplier(): void {
		self::guard();
		check_admin_referer( 'mdo_save_supplier' );
		$id = isset( $_POST['supplier_id'] ) ? absint( $_POST['supplier_id'] ) : 0;
		$data = wp_unslash( $_POST );
		if ( empty( $data['code'] ) || empty( $data['name'] ) || empty( $data['source_url'] ) ) {
			wp_die( 'Código, nombre y URL origen son obligatorios.' );
		}
		$vendor_user_id = isset( $data['vendor_user_id'] ) ? absint( $data['vendor_user_id'] ) : 0;
		if ( $vendor_user_id ) {
			$vendor_user = get_user_by( 'id', $vendor_user_id );
			if ( ! $vendor_user || ! in_array( 'wcfm_vendor', (array) $vendor_user->roles, true ) ) {
				wp_die( 'El usuario seleccionado debe tener el rol wcfm_vendor.' );
			}
		}
		$saved_id = MDO_Supplier_Repository::save( $data, $id );
		wp_safe_redirect( admin_url( 'admin.php?page=mdo-supplier-sync-suppliers&supplier_id=' . $saved_id . '&saved=1' ) );
		exit;
	}

	public static function run_supplier(): void {
		self::guard();
		$id = isset( $_GET['supplier_id'] ) ? absint( $_GET['supplier_id'] ) : 0;
		check_admin_referer( 'mdo_run_supplier_' . $id );
		if ( $id ) {
			MDO_Scheduler::queue_manual( $id );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=mdo-supplier-sync-history&queued=1' ) );
		exit;
	}

	private static function supplier_form( ?array $supplier ): void {
		$supplier = $supplier ?: array(
			'id' => 0, 'code' => '', 'name' => '', 'source_url' => '', 'vendor_user_id' => '', 'connector' => 'none',
			'commercial_rule' => 'percentage', 'commission_percent' => '', 'fixed_fee' => '', 'fixed_fee_scope' => 'order',
			'currency' => 'EUR', 'sync_frequency' => 'weekly', 'notification_email' => get_option( 'admin_email' ),
			'exclusion_url_fragments' => '[]', 'notes' => '', 'active' => 1,
		);
		$users = get_users( array( 'role' => 'wcfm_vendor', 'orderby' => 'display_name', 'order' => 'ASC' ) );
		?>
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="mdo-panel mdo-form">
			<input type="hidden" name="action" value="mdo_save_supplier">
			<input type="hidden" name="supplier_id" value="<?php echo (int) $supplier['id']; ?>">
			<?php wp_nonce_field( 'mdo_save_supplier' ); ?>
			<h2><?php echo $supplier['id'] ? 'Gestionar proveedor' : 'Nuevo proveedor'; ?></h2>
			<div class="mdo-grid">
				<label><span>Nombre</span><input class="regular-text" name="name" required value="<?php echo esc_attr( $supplier['name'] ); ?>"></label>
				<label><span>Código interno</span><input class="regular-text" name="code" required placeholder="tolecarnes" value="<?php echo esc_attr( $supplier['code'] ); ?>"><small>Identificador estable para BI e integraciones.</small></label>
				<label class="mdo-span-2"><span>URL de la tienda / catálogo</span><input class="large-text" type="url" name="source_url" required value="<?php echo esc_attr( $supplier['source_url'] ); ?>"></label>
				<label><span>Vendedor WordPress / WCFM</span><select name="vendor_user_id"><option value="">— Sin asignar —</option><?php foreach ( $users as $user ) : ?><option value="<?php echo (int) $user->ID; ?>" <?php selected( (int) $supplier['vendor_user_id'], (int) $user->ID ); ?>><?php echo esc_html( $user->display_name . ' (#' . $user->ID . ')' ); ?></option><?php endforeach; ?></select><small>Solo se muestran usuarios con rol wcfm_vendor.</small></label>
				<label><span>Conector</span><select name="connector"><option value="none" <?php selected( $supplier['connector'], 'none' ); ?>>Sin conector</option><option value="tolecarnes" <?php selected( $supplier['connector'], 'tolecarnes' ); ?>>Tolecarnes</option><option value="el-catedratico" <?php selected( $supplier['connector'], 'el-catedratico' ); ?>>El Catedrático</option><option value="puente-robles" <?php selected( $supplier['connector'], 'puente-robles' ); ?>>Puente Robles</option></select><small>El scraper concreto se conecta aquí.</small></label>
			</div>
			<h3>Condiciones comerciales</h3>
			<div class="mdo-grid mdo-grid-4">
				<label><span>Regla</span><select name="commercial_rule"><option value="percentage" <?php selected( $supplier['commercial_rule'], 'percentage' ); ?>>Porcentaje</option><option value="percentage_plus_fixed" <?php selected( $supplier['commercial_rule'], 'percentage_plus_fixed' ); ?>>Porcentaje + fijo</option><option value="fixed" <?php selected( $supplier['commercial_rule'], 'fixed' ); ?>>Fijo</option><option value="custom" <?php selected( $supplier['commercial_rule'], 'custom' ); ?>>Personalizada</option></select></label>
				<label><span>% para EMDO</span><input type="number" step="0.0001" min="0" name="commission_percent" value="<?php echo esc_attr( $supplier['commission_percent'] ); ?>"></label>
				<label><span>Fijo</span><input type="number" step="0.01" min="0" name="fixed_fee" value="<?php echo esc_attr( $supplier['fixed_fee'] ); ?>"></label>
				<label><span>Fijo por</span><select name="fixed_fee_scope"><option value="order" <?php selected( $supplier['fixed_fee_scope'], 'order' ); ?>>Pedido</option><option value="line" <?php selected( $supplier['fixed_fee_scope'], 'line' ); ?>>Línea/producto</option></select></label>
			</div>
			<h3>Sincronización</h3>
			<div class="mdo-grid">
				<label><span>Frecuencia</span><select name="sync_frequency"><option value="manual" <?php selected( $supplier['sync_frequency'], 'manual' ); ?>>Solo manual</option><option value="daily" <?php selected( $supplier['sync_frequency'], 'daily' ); ?>>Diaria</option><option value="weekly" <?php selected( $supplier['sync_frequency'], 'weekly' ); ?>>Semanal</option></select></label>
				<label><span>Email de avisos</span><input type="email" class="regular-text" name="notification_email" value="<?php echo esc_attr( $supplier['notification_email'] ); ?>"></label>
				<label class="mdo-span-2"><span>Excluir URLs que contengan (una regla por línea)</span><textarea name="exclusion_url_fragments" rows="7" placeholder="/platos-preparados/&#10;/vinos/"><?php echo esc_textarea( MDO_Supplier_Repository::fragments_as_text( $supplier['exclusion_url_fragments'] ) ); ?></textarea><small>Antes de importar se descartará cualquier producto cuya URL contenga uno de estos fragmentos.</small></label>
				<label class="mdo-span-2"><span>Notas internas</span><textarea name="notes" rows="5"><?php echo esc_textarea( $supplier['notes'] ); ?></textarea></label>
				<label class="mdo-check"><input type="checkbox" name="active" value="1" <?php checked( (int) $supplier['active'], 1 ); ?>> Proveedor activo</label>
				<input type="hidden" name="currency" value="EUR">
			</div>
			<p class="submit"><button class="button button-primary" type="submit">Guardar proveedor</button>
			<?php if ( $supplier['id'] ) : ?>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=mdo_run_supplier&supplier_id=' . (int) $supplier['id'] ), 'mdo_run_supplier_' . (int) $supplier['id'] ) ); ?>">Probar sincronización</a>
			<?php endif; ?></p>
			<?php if ( $supplier['id'] ) : ?><p class="description">En esta versión la prueba solo registra la ejecución. No modifica productos hasta que el conector del proveedor esté implementado y validado.</p><?php endif; ?>
		</form>
		<?php
	}

	private static function runs_table( array $rows ): void {
		?>
		<table class="widefat striped"><thead><tr><th>Proveedor</th><th>Inicio</th><th>Tipo</th><th>Resultado</th><th>Encontrados</th><th>Nuevos</th><th>Actualizados</th><th>Errores</th><th>Detalle</th></tr></thead><tbody>
		<?php if ( ! $rows ) : ?><tr><td colspan="9">Todavía no hay ejecuciones.</td></tr><?php endif; ?>
		<?php foreach ( $rows as $row ) : ?><tr>
			<td><?php echo esc_html( $row['supplier_name'] ?: '#' . $row['supplier_id'] ); ?></td><td><?php echo esc_html( $row['started_at'] ); ?></td><td><?php echo esc_html( $row['trigger_type'] ); ?></td>
			<td><span class="mdo-status status-<?php echo esc_attr( $row['status'] ); ?>"><?php echo esc_html( ucfirst( $row['status'] ) ); ?></span></td>
			<td><?php echo (int) $row['products_found']; ?></td><td><?php echo (int) $row['products_new']; ?></td><td><?php echo (int) $row['products_updated']; ?></td><td><?php echo (int) $row['errors_count']; ?></td><td><?php echo esc_html( wp_trim_words( (string) $row['message'], 18 ) ); ?></td>
		</tr><?php endforeach; ?>
		</tbody></table>
		<?php
	}

	private static function card( string $label, int $value ): void {
		echo '<div class="mdo-card"><strong>' . esc_html( number_format_i18n( $value ) ) . '</strong><span>' . esc_html( $label ) . '</span></div>';
	}

	private static function vendor_label( int $user_id ): string {
		if ( ! $user_id ) {
			return 'Sin asignar';
		}
		$user = get_user_by( 'id', $user_id );
		return $user ? $user->display_name . ' (#' . $user_id . ')' : '#' . $user_id;
	}

	private static function commercial_label( array $supplier ): string {
		$percent = null !== $supplier['commission_percent'] ? rtrim( rtrim( (string) $supplier['commission_percent'], '0' ), '.' ) . '%' : '';
		$fixed   = null !== $supplier['fixed_fee'] ? number_format_i18n( (float) $supplier['fixed_fee'], 2 ) . ' €' : '';
		return match ( $supplier['commercial_rule'] ) {
			'percentage_plus_fixed' => trim( $percent . ' + ' . $fixed, ' +' ),
			'fixed' => $fixed ?: 'Fijo',
			'custom' => 'Personalizada',
			default => $percent ?: 'Porcentaje',
		};
	}

	private static function guard(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( 'No tienes permisos para gestionar EMDO.' );
		}
	}
}
