<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Product_Bulk_Admin {
	private const PAGE = 'mdo-supplier-sync-products';

	public static function init(): void {
		add_action( 'admin_post_mdo_bulk_source_products', array( __CLASS__, 'handle_bulk' ) );
		add_action( 'admin_post_mdo_restore_source_product', array( __CLASS__, 'restore_single' ) );
		add_action( 'admin_footer', array( __CLASS__, 'render_controls' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
	}

	public static function render_controls(): void {
		if ( ! self::is_products_page() || ! self::can_manage() ) {
			return;
		}

		global $wpdb;
		$table       = MDO_Database::table( 'source_products' );
		$supplier_id = isset( $_GET['supplier_id'] ) ? absint( $_GET['supplier_id'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sql         = $supplier_id
			? $wpdb->prepare( "SELECT id, status, source_payload FROM {$table} WHERE supplier_id = %d ORDER BY id DESC LIMIT 500", $supplier_id )
			: "SELECT id, status, source_payload FROM {$table} ORDER BY id DESC LIMIT 500";
		$rows        = $wpdb->get_results( $sql, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		$items = array();
		foreach ( $rows as $row ) {
			$id      = (int) $row['id'];
			$payload = json_decode( (string) $row['source_payload'], true );
			$payload = is_array( $payload ) ? $payload : array();
			$images  = isset( $payload['images'] ) && is_array( $payload['images'] ) ? $payload['images'] : array();
			$thumb   = $images ? esc_url_raw( (string) reset( $images ) ) : '';
			$items[] = array(
				'id'          => $id,
				'status'      => sanitize_key( (string) $row['status'] ),
				'thumbnail'   => $thumb,
				'restore_url' => wp_nonce_url(
					admin_url( 'admin-post.php?action=mdo_restore_source_product&source_product_id=' . $id . ( $supplier_id ? '&supplier_id=' . $supplier_id : '' ) ),
					'mdo_restore_source_' . $id
				),
			);
		}

		$action_url = admin_url( 'admin-post.php' );
		$nonce      = wp_create_nonce( 'mdo_bulk_source_products' );
		?>
		<script>
		(function () {
			const items = <?php echo wp_json_encode( $items, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
			const wrap = document.querySelector('.mdo-sync-wrap');
			if (!wrap || !items.length) return;
			const table = wrap.querySelector('.mdo-panel table.widefat');
			if (!table) return;
			const headRow = table.querySelector('thead tr');
			const bodyRows = Array.from(table.querySelectorAll('tbody tr'));
			if (!headRow || !bodyRows.length || bodyRows.length !== items.length) return;

			const th = document.createElement('th');
			th.className = 'check-column';
			th.innerHTML = '<input type="checkbox" id="mdo-select-all" aria-label="Seleccionar todos los productos de esta vista">';
			headRow.prepend(th);

			const imageTh = document.createElement('th');
			imageTh.textContent = 'Imagen';
			imageTh.style.width = '72px';
			th.after(imageTh);

			bodyRows.forEach((row, index) => {
				const item = items[index];
				const td = document.createElement('th');
				td.scope = 'row';
				td.className = 'check-column';
				td.innerHTML = '<input type="checkbox" class="mdo-product-check" form="mdo-bulk-products" name="source_product_ids[]" value="' + item.id + '" aria-label="Seleccionar producto">';
				row.prepend(td);

				const imageTd = document.createElement('td');
				imageTd.style.width = '72px';
				if (item.thumbnail) {
					const img = document.createElement('img');
					img.src = item.thumbnail;
					img.alt = '';
					img.loading = 'lazy';
					img.referrerPolicy = 'no-referrer-when-downgrade';
					img.style.width = '56px';
					img.style.height = '56px';
					img.style.objectFit = 'cover';
					img.style.borderRadius = '4px';
					img.style.border = '1px solid #dcdcde';
					img.style.background = '#fff';
					imageTd.appendChild(img);
				} else {
					imageTd.textContent = '—';
				}
				td.after(imageTd);

				if (item.status === 'excluded') {
					const actionsCell = row.lastElementChild;
					if (actionsCell) {
						const restore = document.createElement('a');
						restore.className = 'button';
						restore.href = item.restore_url;
						restore.textContent = 'Volver a incluir';
						actionsCell.appendChild(restore);
					}
				}
			});

			const panel = table.closest('.mdo-panel');
			const form = document.createElement('form');
			form.id = 'mdo-bulk-products';
			form.method = 'post';
			form.action = <?php echo wp_json_encode( $action_url ); ?>;
			form.style.margin = '0 0 12px';
			form.innerHTML =
				'<input type="hidden" name="action" value="mdo_bulk_source_products">' +
				'<input type="hidden" name="_wpnonce" value="<?php echo esc_js( $nonce ); ?>">' +
				'<input type="hidden" name="supplier_id" value="<?php echo (int) $supplier_id; ?>">' +
				'<select name="bulk_action" id="mdo-bulk-action" required>' +
					'<option value="">Acciones en lote</option>' +
					'<option value="import">Importar seleccionados</option>' +
					'<option value="exclude">Excluir seleccionados</option>' +
					'<option value="restore">Volver a incluir seleccionados</option>' +
				'</select> ' +
				'<button type="submit" class="button action">Aplicar</button>' +
				'<span id="mdo-selected-count" style="margin-left:10px;color:#646970;">0 seleccionados</span>';
			panel.prepend(form);

			const checks = Array.from(table.querySelectorAll('.mdo-product-check'));
			const selectAll = document.getElementById('mdo-select-all');
			const count = document.getElementById('mdo-selected-count');
			const updateCount = () => {
				const selected = checks.filter(check => check.checked).length;
				count.textContent = selected + (selected === 1 ? ' seleccionado' : ' seleccionados');
				selectAll.checked = selected === checks.length && checks.length > 0;
				selectAll.indeterminate = selected > 0 && selected < checks.length;
			};
			selectAll.addEventListener('change', () => {
				checks.forEach(check => { check.checked = selectAll.checked; });
				updateCount();
			});
			checks.forEach(check => check.addEventListener('change', updateCount));

			form.addEventListener('submit', (event) => {
				const selected = checks.filter(check => check.checked).length;
				const action = document.getElementById('mdo-bulk-action').value;
				if (!selected) {
					event.preventDefault();
					alert('Selecciona al menos un producto.');
					return;
				}
				if (!action) {
					event.preventDefault();
					alert('Elige una acción en lote.');
					return;
				}
				if (action === 'exclude' && !confirm('Los productos seleccionados dejarán de sincronizarse y los que ya estén publicados pasarán a borrador. ¿Continuar?')) {
					event.preventDefault();
				}
			});
		})();
		</script>
		<?php
	}

	public static function handle_bulk(): void {
		self::guard();
		check_admin_referer( 'mdo_bulk_source_products' );

		$action      = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';
		$raw_ids     = isset( $_POST['source_product_ids'] ) ? (array) wp_unslash( $_POST['source_product_ids'] ) : array();
		$ids         = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $raw_ids ) ) ) ), 0, 500 );
		$supplier_id = isset( $_POST['supplier_id'] ) ? absint( $_POST['supplier_id'] ) : 0;
		$count       = 0;

		if ( ! in_array( $action, array( 'import', 'exclude', 'restore' ), true ) || ! $ids ) {
			self::redirect( $supplier_id, 'none', 0 );
		}

		global $wpdb;
		$table = MDO_Database::table( 'source_products' );
		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
		$query = $wpdb->prepare( "SELECT id, status FROM {$table} WHERE id IN ({$placeholders})", ...$ids ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows  = $wpdb->get_results( $query, ARRAY_A ) ?: array();

		foreach ( $rows as $row ) {
			$id     = (int) $row['id'];
			$status = (string) $row['status'];

			if ( 'import' === $action && 'pending' === $status ) {
				if ( MDO_Scheduler::queue_import( $id ) ) {
					$count++;
				}
				continue;
			}

			if ( 'exclude' === $action && 'excluded' !== $status ) {
				MDO_Woo_Importer::exclude_source_product( $id );
				$count++;
				continue;
			}

			if ( 'restore' === $action && 'excluded' === $status ) {
				if ( self::restore( $id ) ) {
					$count++;
				}
			}
		}

		self::redirect( $supplier_id, $action, $count );
	}

	public static function restore_single(): void {
		self::guard();
		$id          = isset( $_GET['source_product_id'] ) ? absint( $_GET['source_product_id'] ) : 0;
		$supplier_id = isset( $_GET['supplier_id'] ) ? absint( $_GET['supplier_id'] ) : 0;
		check_admin_referer( 'mdo_restore_source_' . $id );
		$count = $id && self::restore( $id ) ? 1 : 0;
		self::redirect( $supplier_id, 'restore', $count );
	}

	public static function notices(): void {
		if ( ! self::is_products_page() || ! isset( $_GET['mdo_bulk_result'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$action = sanitize_key( wp_unslash( $_GET['mdo_bulk_result'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$count  = isset( $_GET['mdo_bulk_count'] ) ? absint( $_GET['mdo_bulk_count'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$message = match ( $action ) {
			'import'  => sprintf( 'Se han puesto en cola %d productos seleccionados para importar.', $count ),
			'exclude' => sprintf( 'Se han excluido %d productos seleccionados.', $count ),
			'restore' => sprintf( 'Se han devuelto %d productos a Pendiente. Puedes importarlos cuando quieras.', $count ),
			default   => 'No se ha aplicado ninguna acción.',
		};
		$class = $count > 0 ? 'notice notice-success is-dismissible' : 'notice notice-warning is-dismissible';
		echo '<div class="' . esc_attr( $class ) . '"><p>' . esc_html( $message ) . '</p></div>';
	}

	private static function restore( int $source_product_id ): bool {
		global $wpdb;
		$result = $wpdb->update(
			MDO_Database::table( 'source_products' ),
			array(
				'status'     => 'pending',
				'last_error' => null,
			),
			array(
				'id'     => $source_product_id,
				'status' => 'excluded',
			)
		);
		return is_int( $result ) && $result > 0;
	}

	private static function redirect( int $supplier_id, string $action, int $count ): void {
		$url = add_query_arg(
			array(
				'page'            => self::PAGE,
				'mdo_bulk_result' => sanitize_key( $action ),
				'mdo_bulk_count'  => $count,
			),
			admin_url( 'admin.php' )
		);
		if ( $supplier_id ) {
			$url = add_query_arg( 'supplier_id', $supplier_id, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	private static function is_products_page(): bool {
		return is_admin() && isset( $_GET['page'] ) && self::PAGE === sanitize_key( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	private static function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	private static function guard(): void {
		if ( ! self::can_manage() ) {
			wp_die( 'No tienes permisos para gestionar EMDO.' );
		}
	}
}
