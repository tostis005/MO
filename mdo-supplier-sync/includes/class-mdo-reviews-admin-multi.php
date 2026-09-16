<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Panel de moderación multi-tienda para las reseñas de EMDO.
 */
final class MDO_Reviews_Admin_Multi {
	private const PAGE_SIZE = 50;

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'replace_menu' ), 99 );
		remove_action( 'admin_post_mdo_reviews_save', array( 'MDO_Reviews', 'handle_save' ) );
		remove_action( 'admin_post_mdo_reviews_validate_suggestion', array( 'MDO_Reviews', 'handle_validate_suggestion' ) );
		add_action( 'admin_post_mdo_reviews_save', array( __CLASS__, 'handle_save' ) );
		add_action( 'admin_post_mdo_reviews_validate_suggestion', array( __CLASS__, 'handle_validate_suggestion' ) );
	}

	public static function replace_menu(): void {
		remove_submenu_page( 'mdo-supplier-sync', 'mdo-reviews' );
		add_submenu_page( 'mdo-supplier-sync', 'Reseñas', 'Reseñas', 'manage_woocommerce', 'mdo-reviews', array( __CLASS__, 'admin_page' ) );
	}

	public static function handle_validate_suggestion(): void {
		self::guard( 'mdo_reviews_validate_suggestion' );
		$id = absint( $_POST['review_id'] ?? 0 );
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$review = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $id ) );
		if ( $review ) {
			$ids = MDO_Reviews_Vendors::get( $id, $review );
			if ( ! $ids && (int) $review->suggested_vendor_user_id > 0 ) {
				$ids[] = (int) $review->suggested_vendor_user_id;
			}
			if ( $ids ) {
				MDO_Reviews_Vendors::replace( $id, $ids );
				$wpdb->update(
					$table,
					array(
						'vendor_user_id' => (int) $ids[0],
						'suggested_vendor_user_id' => (int) $ids[0],
						'status' => 'validated',
						'validation_method' => 'manual',
						'validated_by' => get_current_user_id(),
						'validated_at' => current_time( 'mysql' ),
						'updated_at' => current_time( 'mysql' ),
					),
					array( 'id' => $id )
				);
			}
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mdo-reviews', 'mdo_notice' => 'saved' ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function handle_save(): void {
		self::guard( 'mdo_reviews_save' );
		$id = absint( $_POST['review_id'] ?? 0 );
		$status = sanitize_key( (string) ( $_POST['status'] ?? 'pending' ) );
		if ( ! in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
			$status = 'pending';
		}
		$vendor_ids = isset( $_POST['vendor_user_ids'] ) && is_array( $_POST['vendor_user_ids'] )
			? array_values( array_unique( array_filter( array_map( 'absint', $_POST['vendor_user_ids'] ) ) ) )
			: array();
		$product_id = absint( $_POST['wc_product_id'] ?? 0 );
		$order_id = absint( $_POST['wc_order_id'] ?? 0 );
		$notice = 'saved';
		if ( 'validated' === $status && ! $vendor_ids ) {
			$status = 'pending';
			$notice = 'vendor_required';
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE id=%d", $id ) );
		if ( $exists ) {
			MDO_Reviews_Vendors::replace( $id, $vendor_ids );
			$primary = $vendor_ids ? (int) $vendor_ids[0] : 0;
			$wpdb->update(
				$table,
				array(
					'vendor_user_id' => 'validated' === $status && $primary ? $primary : null,
					'suggested_vendor_user_id' => $primary ?: null,
					'wc_product_id' => $product_id ?: null,
					'wc_order_id' => $order_id ?: null,
					'status' => $status,
					'validation_method' => 'manual',
					'validated_by' => 'validated' === $status ? get_current_user_id() : null,
					'validated_at' => 'validated' === $status ? current_time( 'mysql' ) : null,
					'assignment_type' => $vendor_ids ? 'manual_multi' : null,
					'assignment_confidence' => $vendor_ids ? 1.0 : 0,
					'assignment_reason' => $vendor_ids ? sprintf( 'Asignación manual a %d tienda(s).', count( $vendor_ids ) ) : null,
					'updated_at' => current_time( 'mysql' ),
				),
				array( 'id' => $id )
			);
		}
		wp_safe_redirect( add_query_arg( array( 'page' => 'mdo-reviews', 'review_id' => $id, 'mdo_notice' => $notice ), admin_url( 'admin.php' ) ) );
		exit;
	}

	private static function guard( string $action ): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'No tienes permisos para realizar esta acción.', 'mdo-supplier-sync' ) );
		}
		check_admin_referer( $action );
	}

	public static function admin_page(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$review_id = absint( $_GET['review_id'] ?? 0 );
		if ( $review_id ) {
			self::render_detail( $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id=%d", $review_id ) ) );
			return;
		}

		$status = sanitize_key( (string) ( $_GET['status'] ?? '' ) );
		$source = sanitize_key( (string) ( $_GET['source'] ?? '' ) );
		$vendor_raw = sanitize_key( (string) ( $_GET['vendor'] ?? '' ) );
		$vendor_unassigned = 'unassigned' === $vendor_raw;
		$vendor = $vendor_unassigned ? 0 : absint( $vendor_raw );
		$search = sanitize_text_field( (string) ( $_GET['s'] ?? '' ) );
		$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
		$where = array( '1=1' );
		$params = array();
		if ( in_array( $status, array( 'pending', 'validated', 'rejected' ), true ) ) {
			$where[] = 'r.status=%s';
			$params[] = $status;
		}
		if ( $source ) {
			$where[] = 'r.source=%s';
			$params[] = $source;
		}
		if ( $vendor_unassigned ) {
			$where[] = MDO_Reviews_Vendors::review_unassigned_sql( 'r' );
		} elseif ( $vendor ) {
			$where[] = MDO_Reviews_Vendors::review_matches_vendor_sql( 'r' );
			array_push( $params, $vendor, $vendor, $vendor );
		}
		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$where[] = '(r.author_name LIKE %s OR r.review_text LIKE %s OR r.review_title LIKE %s OR r.assignment_reason LIKE %s)';
			array_push( $params, $like, $like, $like, $like );
		}
		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} r WHERE {$where_sql}";
		$total = (int) $wpdb->get_var( $params ? $wpdb->prepare( $count_sql, ...$params ) : $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$offset = ( $page - 1 ) * self::PAGE_SIZE;
		$list_sql = "SELECT r.* FROM {$table} r WHERE {$where_sql} ORDER BY (r.review_date IS NULL) ASC,r.review_date DESC,r.id DESC LIMIT %d OFFSET %d";
		$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, ...array_merge( $params, array( self::PAGE_SIZE, $offset ) ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$sources = $wpdb->get_col( "SELECT DISTINCT source FROM {$table} ORDER BY source ASC" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$vendors = self::vendor_options();
		$counts = self::counts();
		?>
		<div class="wrap mdo-sync-wrap mdo-reviews-admin">
			<h1>Reseñas</h1>
			<p>Modera reseñas de EMDO, Google, Trustpilot y Foro Coches. Una reseña puede pertenecer a varias tiendas; solo las publicadas aparecen en sus páginas públicas.</p>
			<?php self::notice(); ?>
			<div class="mdo-review-toolbar"><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mdo_reviews_import"><?php wp_nonce_field( 'mdo_reviews_import' ); ?><button class="button button-primary">Importar fuentes locales</button></form></div>
			<div class="mdo-review-cards"><?php foreach ( array( 'all' => 'Todas', 'pending' => 'Borradores', 'validated' => 'Publicadas', 'unassigned' => 'Sin asignar', 'rejected' => 'Descartadas' ) as $key => $label ) : ?><a class="mdo-review-card" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mdo-reviews', 'status' => 'all' === $key || 'unassigned' === $key ? false : $key, 'vendor' => 'unassigned' === $key ? 'unassigned' : false ), admin_url( 'admin.php' ) ) ); ?>"><strong><?php echo esc_html( (string) ( $counts[ $key ] ?? 0 ) ); ?></strong><span><?php echo esc_html( $label ); ?></span></a><?php endforeach; ?></div>
			<form method="get" class="mdo-review-filters"><input type="hidden" name="page" value="mdo-reviews"><select name="status"><option value="">Todos los estados</option><?php foreach ( array( 'pending' => 'Borradores', 'validated' => 'Publicadas', 'rejected' => 'Descartadas' ) as $key => $label ) : ?><option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select><select name="source"><option value="">Todas las fuentes</option><?php foreach ( $sources as $source_name ) : ?><option value="<?php echo esc_attr( $source_name ); ?>" <?php selected( $source, $source_name ); ?>><?php echo esc_html( self::source_label( $source_name ) ); ?></option><?php endforeach; ?></select><select name="vendor"><option value="">Todas las tiendas</option><option value="unassigned" <?php selected( $vendor_unassigned ); ?>>Sin asignar</option><?php foreach ( $vendors as $vendor_id => $vendor_name ) : ?><option value="<?php echo esc_attr( (string) $vendor_id ); ?>" <?php selected( $vendor, $vendor_id ); ?>><?php echo esc_html( $vendor_name ); ?></option><?php endforeach; ?></select><input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Persona, reseña o motivo"><button class="button">Filtrar</button></form>
			<div class="mdo-review-table-wrap"><table class="widefat striped mdo-review-table"><thead><tr><th>Fecha</th><th>Fuente</th><th>Persona</th><th>Puntuación</th><th>Reseña</th><th>Tienda(s)</th><th>Producto / pedido</th><th>Confianza</th><th>Estado</th><th></th></tr></thead><tbody>
			<?php if ( ! $rows ) : ?><tr><td colspan="10">No hay reseñas para estos filtros.</td></tr><?php endif; ?>
			<?php foreach ( (array) $rows as $row ) : ?><tr><td><?php echo esc_html( self::format_date( $row->review_date ) ); ?></td><td><?php echo wp_kses_post( self::source_badge( $row ) ); ?></td><td><strong><?php echo esc_html( $row->author_name ?: 'Anónimo' ); ?></strong></td><td><?php echo wp_kses_post( self::stars( (int) $row->rating ) ); ?></td><td><div class="mdo-review-excerpt"><?php echo esc_html( wp_trim_words( wp_strip_all_tags( (string) $row->review_text ), 24 ) ); ?></div></td><td><?php echo esc_html( self::vendor_names( $row ) ); ?></td><td><?php echo wp_kses_post( self::relation( $row ) ); ?></td><td><?php echo esc_html( number_format_i18n( (float) $row->assignment_confidence * 100, 0 ) . '%' ); ?><br><small><?php echo esc_html( (string) $row->assignment_reason ); ?></small></td><td><span class="mdo-review-status is-<?php echo esc_attr( $row->status ); ?>"><?php echo esc_html( self::status_label( $row->status ) ); ?></span></td><td class="mdo-review-actions"><a class="button button-small" href="<?php echo esc_url( add_query_arg( array( 'page' => 'mdo-reviews', 'review_id' => $row->id ), admin_url( 'admin.php' ) ) ); ?>">Abrir</a></td></tr><?php endforeach; ?>
			</tbody></table></div><?php self::pagination( $page, $total ); ?></div>
		<?php
	}

	private static function render_detail( $review ): void {
		$vendors = self::vendor_options();
		$selected = $review ? MDO_Reviews_Vendors::get( (int) $review->id, $review ) : array();
		?>
		<div class="wrap mdo-sync-wrap mdo-reviews-admin"><p><a href="<?php echo esc_url( add_query_arg( 'page', 'mdo-reviews', admin_url( 'admin.php' ) ) ); ?>">← Volver a reseñas</a></p><?php self::notice(); ?>
		<?php if ( ! $review ) : ?><div class="notice notice-error"><p>Reseña no encontrada.</p></div></div><?php return; endif; ?>
		<h1>Reseña #<?php echo esc_html( (string) $review->id ); ?></h1><div class="mdo-review-detail-grid"><section class="mdo-review-detail-card"><h2><?php echo esc_html( $review->author_name ?: 'Anónimo' ); ?></h2><div class="mdo-review-stars-large"><?php echo wp_kses_post( self::stars( (int) $review->rating ) ); ?></div><p class="description"><?php echo esc_html( self::format_date( $review->review_date ) ); ?> · <?php echo wp_kses_post( self::source_badge( $review ) ); ?></p><?php if ( $review->review_title ) : ?><h3><?php echo esc_html( $review->review_title ); ?></h3><?php endif; ?><div class="mdo-review-fulltext"><?php echo wpautop( wp_kses_post( $review->review_text ) ); ?></div><?php if ( $review->source_url ) : ?><p><a href="<?php echo esc_url( $review->source_url ); ?>" target="_blank" rel="noopener noreferrer">Abrir reseña original ↗</a></p><?php endif; ?><dl><dt>ID fuente</dt><dd><?php echo esc_html( $review->source_review_id ?: '—' ); ?></dd><dt>Motivo de asignación</dt><dd><?php echo esc_html( $review->assignment_reason ?: '—' ); ?></dd><dt>Confianza</dt><dd><?php echo esc_html( number_format_i18n( (float) $review->assignment_confidence * 100, 0 ) . '%' ); ?></dd></dl></section>
		<section class="mdo-review-detail-card"><h2>Asignación y validación</h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><input type="hidden" name="action" value="mdo_reviews_save"><input type="hidden" name="review_id" value="<?php echo esc_attr( (string) $review->id ); ?>"><?php wp_nonce_field( 'mdo_reviews_save' ); ?><p><label><strong>Tiendas</strong><br><select name="vendor_user_ids[]" multiple size="8" style="min-width:320px"><?php foreach ( $vendors as $vendor_id => $vendor_name ) : ?><option value="<?php echo esc_attr( (string) $vendor_id ); ?>" <?php selected( in_array( (int) $vendor_id, $selected, true ) ); ?>><?php echo esc_html( $vendor_name ); ?></option><?php endforeach; ?></select><br><small>Ctrl/Cmd + clic para seleccionar varias tiendas.</small></label></p><p><label><strong>Producto WooCommerce (ID)</strong><br><input type="number" min="0" name="wc_product_id" value="<?php echo esc_attr( (string) ( $review->wc_product_id ?: '' ) ); ?>"></label></p><p><label><strong>Pedido WooCommerce (ID)</strong><br><input type="number" min="0" name="wc_order_id" value="<?php echo esc_attr( (string) ( $review->wc_order_id ?: '' ) ); ?>"></label></p><p><label><strong>Estado</strong><br><select name="status"><option value="pending" <?php selected( $review->status, 'pending' ); ?>>Borrador</option><option value="validated" <?php selected( $review->status, 'validated' ); ?>>Publicada</option><option value="rejected" <?php selected( $review->status, 'rejected' ); ?>>Descartada</option></select></label></p><p><button class="button button-primary">Guardar</button></p></form></section></div></div>
		<?php
	}

	private static function counts(): array {
		global $wpdb;
		$table = MDO_Database::table( 'reviews' );
		$rows = $wpdb->get_results( "SELECT status,COUNT(*) c FROM {$table} GROUP BY status", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$all = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$unassigned = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} r WHERE r.status='pending' AND " . MDO_Reviews_Vendors::review_unassigned_sql( 'r' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array( 'all' => $all, 'pending' => (int) ( $rows['pending']->c ?? 0 ), 'validated' => (int) ( $rows['validated']->c ?? 0 ), 'rejected' => (int) ( $rows['rejected']->c ?? 0 ), 'unassigned' => $unassigned );
	}

	private static function vendor_options(): array {
		$vendors = array();
		foreach ( get_users( array( 'role' => 'wcfm_vendor', 'fields' => array( 'ID', 'display_name' ) ) ) as $user ) {
			$name = (string) $user->display_name;
			if ( function_exists( 'wcfmmp_get_store' ) ) {
				$store = wcfmmp_get_store( (int) $user->ID );
				if ( $store && method_exists( $store, 'get_shop_name' ) ) {
					$name = (string) $store->get_shop_name();
				}
			}
			$vendors[ (int) $user->ID ] = $name ?: (string) $user->display_name;
		}
		asort( $vendors, SORT_NATURAL | SORT_FLAG_CASE );
		return $vendors;
	}

	private static function vendor_names( $review ): string {
		$vendors = self::vendor_options();
		$names = array();
		foreach ( MDO_Reviews_Vendors::get( (int) $review->id, $review ) as $id ) {
			$names[] = $vendors[ $id ] ?? ( 'Tienda #' . $id );
		}
		return $names ? implode( ', ', $names ) : 'Sin asignar';
	}

	private static function source_badge( $review ): string {
		$label = self::source_label( (string) $review->source );
		$badge = '<span class="mdo-source-badge">' . esc_html( $label ) . '</span>';
		if ( ! empty( $review->source_url ) ) {
			return '<a href="' . esc_url( $review->source_url ) . '" target="_blank" rel="noopener noreferrer" title="Abrir reseña original">' . $badge . '</a>';
		}
		return $badge;
	}

	private static function source_label( string $source ): string {
		$labels = array( 'woocommerce_product' => 'EMDO', 'wcfm' => 'EMDO', 'google' => 'Google', 'trustpilot' => 'Trustpilot', 'forocoches' => 'Foro Coches', 'external' => 'EMDO' );
		return $labels[ $source ] ?? ucfirst( str_replace( '_', ' ', $source ) );
	}

	private static function status_label( string $status ): string {
		return array( 'pending' => 'Borrador', 'validated' => 'Publicada', 'rejected' => 'Descartada' )[ $status ] ?? $status;
	}

	private static function stars( int $rating ): string {
		$rating = max( 0, min( 5, $rating ) );
		$out = '<span class="mdo-stars" aria-label="' . esc_attr( sprintf( '%d de 5 estrellas', $rating ) ) . '">';
		for ( $i = 1; $i <= 5; $i++ ) {
			$out .= '<span class="' . ( $i <= $rating ? 'is-filled' : '' ) . '" aria-hidden="true">★</span>';
		}
		return $out . '</span>';
	}

	private static function relation( $row ): string {
		$parts = array();
		if ( (int) $row->wc_product_id > 0 ) {
			$parts[] = 'Producto #' . (int) $row->wc_product_id;
		}
		if ( (int) $row->wc_order_id > 0 ) {
			$parts[] = 'Pedido #' . (int) $row->wc_order_id;
		}
		return $parts ? esc_html( implode( ' · ', $parts ) ) : '—';
	}

	private static function format_date( $date ): string {
		if ( ! $date ) {
			return 'Sin fecha';
		}
		$timestamp = strtotime( (string) $date );
		return $timestamp ? wp_date( 'd/m/Y H:i', $timestamp ) : 'Sin fecha';
	}

	private static function pagination( int $page, int $total ): void {
		$pages = (int) ceil( $total / self::PAGE_SIZE );
		if ( $pages <= 1 ) {
			return;
		}
		$base = remove_query_arg( 'paged' );
		echo '<div class="tablenav"><div class="tablenav-pages">';
		for ( $i = max( 1, $page - 3 ); $i <= min( $pages, $page + 3 ); $i++ ) {
			echo '<a class="button ' . ( $i === $page ? 'button-primary' : '' ) . '" href="' . esc_url( add_query_arg( 'paged', $i, $base ) ) . '">' . esc_html( (string) $i ) . '</a> ';
		}
		echo '</div></div>';
	}

	private static function notice(): void {
		$notice = sanitize_key( (string) ( $_GET['mdo_notice'] ?? '' ) );
		if ( 'saved' === $notice ) {
			echo '<div class="notice notice-success is-dismissible"><p>Reseña guardada.</p></div>';
		} elseif ( 'vendor_required' === $notice ) {
			echo '<div class="notice notice-warning is-dismissible"><p>Para publicar una reseña debes asignarla al menos a una tienda.</p></div>';
		}
	}
}
