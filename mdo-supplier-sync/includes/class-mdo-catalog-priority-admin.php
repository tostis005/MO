<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Central EMDO screen for catalogue editorial priority. */
final class MDO_Catalog_Priority_Admin {
	private const PAGE     = 'mdo-supplier-sync-priority';
	private const PER_PAGE = 50;

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_action( 'wp_ajax_mdo_set_product_priority', array( __CLASS__, 'ajax_set_priority' ) );
	}

	public static function menu(): void {
		add_submenu_page(
			'mdo-supplier-sync',
			'Orden y prioridad del catálogo',
			'Prioridad catálogo',
			'manage_woocommerce',
			self::PAGE,
			array( __CLASS__, 'render' )
		);
	}

	public static function render(): void {
		self::guard();

		$tab          = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'all'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab          = 'prioritized' === $tab ? 'prioritized' : 'all';
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page_num     = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$prioritized  = 'prioritized' === $tab;
		$total        = self::count_products( $prioritized, $search );
		$rows         = self::query_products( $prioritized, $search, $page_num );
		$all_count    = self::count_products( false, '' );
		$prio_count   = self::count_products( true, '' );
		$pages        = max( 1, (int) ceil( $total / self::PER_PAGE ) );
		$nonce        = wp_create_nonce( 'mdo_catalog_priority' );
		?>
		<div class="wrap mdo-sync-wrap mdo-catalog-priority">
			<h1>Orden y prioridad del catálogo</h1>
			<p class="description">Gestiona desde EMDO qué productos reciben un impulso editorial. El valor 0 equivale a no tener prioridad y elimina la marca explícita del producto.</p>

			<nav class="nav-tab-wrapper" style="margin-top:20px;">
				<a class="nav-tab <?php echo 'all' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&view=all' ) ); ?>">Todos los productos <span class="count">(<?php echo (int) $all_count; ?>)</span></a>
				<a class="nav-tab <?php echo 'prioritized' === $tab ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&view=prioritized' ) ); ?>">Priorizados <span class="count">(<?php echo (int) $prio_count; ?>)</span></a>
			</nav>

			<div class="mdo-panel" style="margin-top:16px;">
				<form method="get" style="display:flex;gap:8px;align-items:center;margin:0 0 14px;">
					<input type="hidden" name="page" value="<?php echo esc_attr( self::PAGE ); ?>">
					<input type="hidden" name="view" value="<?php echo esc_attr( $tab ); ?>">
					<label class="screen-reader-text" for="mdo-priority-search">Buscar producto</label>
					<input id="mdo-priority-search" type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Buscar por nombre o ID" style="min-width:280px;">
					<button class="button">Buscar</button>
					<?php if ( '' !== $search ) : ?>
						<a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::PAGE . '&view=' . $tab ) ); ?>">Limpiar</a>
					<?php endif; ?>
				</form>

				<p style="margin:0 0 12px;color:#646970;">
					<?php if ( $prioritized ) : ?>Los productos con prioridad aparecen de mayor a menor. Al elegir “Sin prioridad”, desaparecen de esta pestaña.<?php else : ?>Los productos priorizados aparecen primero para que puedas revisarlos de un vistazo.<?php endif; ?>
				</p>

				<table class="widefat striped mdo-priority-table">
					<thead>
						<tr>
							<th style="width:64px;">Imagen</th>
							<th>Producto</th>
							<th>Productor</th>
							<th style="width:110px;">Estado</th>
							<th style="width:120px;">Precio</th>
							<th style="width:220px;">Prioridad EMDO</th>
						</tr>
					</thead>
					<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="6"><?php echo esc_html( $prioritized ? 'No hay productos con prioridad en esta vista.' : 'No se han encontrado productos.' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php
						$product_id = absint( $row['ID'] );
						$priority   = absint( $row['priority'] );
						$product    = function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;
						$status_obj = get_post_status_object( (string) $row['post_status'] );
						$status      = $status_obj ? $status_obj->label : (string) $row['post_status'];
						$edit_url   = get_edit_post_link( $product_id );
						?>
						<tr data-product-id="<?php echo (int) $product_id; ?>">
							<td><?php echo get_the_post_thumbnail( $product_id, array( 48, 48 ), array( 'style' => 'width:48px;height:48px;object-fit:cover;border-radius:4px;' ) ) ?: '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></td>
							<td><strong><?php echo esc_html( (string) $row['post_title'] ); ?></strong><br><small>#<?php echo (int) $product_id; ?><?php if ( $edit_url ) : ?> · <a href="<?php echo esc_url( $edit_url ); ?>">Editar producto</a><?php endif; ?></small></td>
							<td><?php echo esc_html( self::vendor_label( absint( $row['post_author'] ) ) ); ?></td>
							<td><?php echo esc_html( $status ); ?></td>
							<td><?php echo $product ? wp_kses_post( $product->get_price_html() ?: '—' ) : '—'; ?></td>
							<td>
								<label class="screen-reader-text" for="mdo-priority-<?php echo (int) $product_id; ?>">Prioridad del producto</label>
								<select id="mdo-priority-<?php echo (int) $product_id; ?>" class="mdo-priority-select" data-product-id="<?php echo (int) $product_id; ?>" style="min-width:145px;">
									<?php foreach ( MDO_Catalog_Ranking::priority_levels() as $value => $label ) : ?>
										<option value="<?php echo (int) $value; ?>" <?php selected( $priority, (int) $value ); ?>><?php echo esc_html( $label . ' (' . $value . ')' ); ?></option>
									<?php endforeach; ?>
								</select>
								<span class="mdo-priority-save-state" aria-live="polite" style="display:inline-block;min-width:62px;margin-left:6px;color:#646970;"></span>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>

				<?php if ( $pages > 1 ) : ?>
					<div class="tablenav"><div class="tablenav-pages" style="float:none;margin-top:14px;">
						<?php
						echo wp_kses_post(
							paginate_links(
								array(
									'base'      => add_query_arg( 'paged', '%#%', admin_url( 'admin.php?page=' . self::PAGE . '&view=' . $tab . ( '' !== $search ? '&s=' . rawurlencode( $search ) : '' ) ) ),
									'format'    => '',
									'current'   => $page_num,
									'total'     => $pages,
									'prev_text' => '‹',
									'next_text' => '›',
								)
							)
						);
						?>
					</div></div>
				<?php endif; ?>
			</div>
		</div>

		<script>
		(function () {
			const nonce = <?php echo wp_json_encode( $nonce ); ?>;
			const prioritizedView = <?php echo $prioritized ? 'true' : 'false'; ?>;
			document.querySelectorAll('.mdo-priority-select').forEach((select) => {
				select.dataset.previous = select.value;
				select.addEventListener('change', async () => {
					const state = select.parentElement.querySelector('.mdo-priority-save-state');
					const previous = select.dataset.previous || '0';
					select.disabled = true;
					state.textContent = 'Guardando…';
					try {
						const body = new URLSearchParams({
							action: 'mdo_set_product_priority',
							nonce,
							product_id: select.dataset.productId,
							priority: select.value
						});
						const response = await fetch(ajaxurl, {
							method: 'POST',
							headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
							credentials: 'same-origin',
							body: body.toString()
						});
						const payload = await response.json();
						if (!payload.success) throw new Error(payload?.data?.message || 'No se pudo guardar');
						select.dataset.previous = String(payload.data.priority);
						state.textContent = 'Guardado';
						if (prioritizedView && Number(payload.data.priority) === 0) {
							const row = select.closest('tr');
							if (row) setTimeout(() => row.remove(), 300);
						}
					} catch (error) {
						select.value = previous;
						state.textContent = 'Error';
						window.alert(error.message || 'No se pudo guardar la prioridad.');
					} finally {
						select.disabled = false;
						setTimeout(() => { if (state.textContent === 'Guardado') state.textContent = ''; }, 1800);
					}
				});
			});
		})();
		</script>
		<?php
	}

	public static function ajax_set_priority(): void {
		self::guard_ajax();
		check_ajax_referer( 'mdo_catalog_priority', 'nonce' );

		$product_id = isset( $_POST['product_id'] ) ? absint( $_POST['product_id'] ) : 0;
		$priority   = isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 0;
		if ( ! $product_id || 'product' !== get_post_type( $product_id ) ) {
			wp_send_json_error( array( 'message' => 'Producto no válido.' ), 400 );
		}
		if ( ! array_key_exists( $priority, MDO_Catalog_Ranking::priority_levels() ) ) {
			wp_send_json_error( array( 'message' => 'Prioridad no válida.' ), 400 );
		}
		if ( ! MDO_Catalog_Ranking::set_priority( $product_id, $priority ) ) {
			wp_send_json_error( array( 'message' => 'No se pudo guardar la prioridad.' ), 500 );
		}

		wp_send_json_success(
			array(
				'product_id' => $product_id,
				'priority'   => $priority,
				'label'      => MDO_Catalog_Ranking::priority_levels()[ $priority ],
			)
		);
	}

	private static function query_products( bool $prioritized, string $search, int $page_num ): array {
		global $wpdb;

		$meta_key = MDO_Catalog_Ranking::PRIORITY_META;
		$where    = "p.post_type = 'product' AND p.post_status NOT IN ('trash','auto-draft')";
		$params   = array( $meta_key );

		if ( $prioritized ) {
			$where .= " AND CAST(COALESCE(pm.meta_value, '0') AS UNSIGNED) > 0";
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (p.post_title LIKE %s';
			$params[] = $like;
			if ( ctype_digit( $search ) ) {
				$where   .= ' OR p.ID = %d';
				$params[] = absint( $search );
			}
			$where .= ')';
		}

		$offset = ( max( 1, $page_num ) - 1 ) * self::PER_PAGE;
		$sql = "SELECT p.ID, p.post_title, p.post_status, p.post_author,
			CAST(COALESCE(pm.meta_value, '0') AS UNSIGNED) priority
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE {$where}
			ORDER BY CAST(COALESCE(pm.meta_value, '0') AS UNSIGNED) DESC, p.post_title ASC, p.ID DESC
			LIMIT %d OFFSET %d";
		$params[] = self::PER_PAGE;
		$params[] = $offset;

		$query = $wpdb->prepare( $sql, ...$params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results( $query, ARRAY_A ) ?: array(); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function count_products( bool $prioritized, string $search ): int {
		global $wpdb;

		$meta_key = MDO_Catalog_Ranking::PRIORITY_META;
		$where    = "p.post_type = 'product' AND p.post_status NOT IN ('trash','auto-draft')";
		$params   = array( $meta_key );

		if ( $prioritized ) {
			$where .= " AND CAST(COALESCE(pm.meta_value, '0') AS UNSIGNED) > 0";
		}
		if ( '' !== $search ) {
			$like     = '%' . $wpdb->esc_like( $search ) . '%';
			$where   .= ' AND (p.post_title LIKE %s';
			$params[] = $like;
			if ( ctype_digit( $search ) ) {
				$where   .= ' OR p.ID = %d';
				$params[] = absint( $search );
			}
			$where .= ')';
		}

		$sql = "SELECT COUNT(DISTINCT p.ID)
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
			WHERE {$where}";
		$query = $wpdb->prepare( $sql, ...$params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return (int) $wpdb->get_var( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private static function vendor_label( int $vendor_id ): string {
		if ( ! $vendor_id ) {
			return '—';
		}

		$settings = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
			return (string) $settings['store_name'];
		}

		$user = get_userdata( $vendor_id );
		return $user ? (string) $user->display_name : '#' . $vendor_id;
	}

	private static function can_manage(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	private static function guard(): void {
		if ( ! self::can_manage() ) {
			wp_die( 'No tienes permisos para gestionar EMDO.' );
		}
	}

	private static function guard_ajax(): void {
		if ( ! self::can_manage() ) {
			wp_send_json_error( array( 'message' => 'No tienes permisos para gestionar EMDO.' ), 403 );
		}
	}
}
