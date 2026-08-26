<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Promotions {
	private const POST_TYPE       = 'mdo_promotion';
	private const NONCE_ACTION    = 'mdo_save_promotion_meta';
	private const NONCE_NAME      = 'mdo_promotion_nonce';
	private const REWRITE_VERSION = '1';
	private const SEED_KEY        = 'tolecarnes-hamburguesas-v1';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 8 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 20 );
		add_action( 'init', array( __CLASS__, 'maybe_seed_tolecarnes_promotion' ), 30 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 3 );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_archive_query' ) );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column' ), 10, 2 );
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );
	}

	public static function register_post_type(): void {
		$labels = array(
			'name'                  => 'Promociones',
			'singular_name'         => 'Promoción',
			'menu_name'             => 'Promociones',
			'name_admin_bar'        => 'Promoción',
			'add_new'               => 'Añadir promoción',
			'add_new_item'          => 'Añadir promoción',
			'new_item'              => 'Nueva promoción',
			'edit_item'             => 'Editar promoción',
			'view_item'             => 'Ver promoción',
			'all_items'             => 'Promociones',
			'search_items'          => 'Buscar promociones',
			'not_found'             => 'No se han encontrado promociones.',
			'not_found_in_trash'    => 'No hay promociones en la papelera.',
			'featured_image'        => 'Imagen de la promoción',
			'set_featured_image'    => 'Elegir imagen',
			'remove_featured_image' => 'Quitar imagen',
		);

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'show_in_menu'       => 'mdo-supplier-sync',
				'show_in_rest'       => true,
				'has_archive'        => 'ofertas',
				'rewrite'            => array(
					'slug'       => 'ofertas',
					'with_front' => false,
				),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'page-attributes' ),
				'menu_position'      => 30,
				'query_var'          => true,
				'capabilities'       => array(
					'edit_post'              => 'manage_woocommerce',
					'read_post'              => 'read',
					'delete_post'            => 'manage_woocommerce',
					'edit_posts'             => 'manage_woocommerce',
					'edit_others_posts'      => 'manage_woocommerce',
					'publish_posts'          => 'manage_woocommerce',
					'read_private_posts'     => 'manage_woocommerce',
					'delete_posts'           => 'manage_woocommerce',
					'delete_private_posts'   => 'manage_woocommerce',
					'delete_published_posts' => 'manage_woocommerce',
					'delete_others_posts'    => 'manage_woocommerce',
					'edit_private_posts'     => 'manage_woocommerce',
					'edit_published_posts'   => 'manage_woocommerce',
					'create_posts'           => 'manage_woocommerce',
				),
				'map_meta_cap'       => false,
			)
		);
	}

	public static function maybe_flush_rewrite_rules(): void {
		if ( get_option( 'mdo_promotions_rewrite_version' ) === self::REWRITE_VERSION ) {
			return;
		}
		flush_rewrite_rules( false );
		update_option( 'mdo_promotions_rewrite_version', self::REWRITE_VERSION, false );
	}

	public static function add_meta_boxes(): void {
		add_meta_box( 'mdo-promotion-details', 'Detalles de la promoción', array( __CLASS__, 'render_details_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_details_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$meta      = self::meta( $post->ID );
		$suppliers = class_exists( 'MDO_Supplier_Repository' ) ? MDO_Supplier_Repository::all() : array();
		$status    = self::status( $post->ID );
		?>
		<style>
			.mdo-promo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 20px}.mdo-promo-field{display:flex;flex-direction:column;gap:6px}.mdo-promo-field.is-wide{grid-column:1/-1}.mdo-promo-field label{font-weight:600}.mdo-promo-field input[type=text],.mdo-promo-field input[type=url],.mdo-promo-field input[type=date],.mdo-promo-field input[type=number],.mdo-promo-field select,.mdo-promo-field textarea{width:100%}.mdo-promo-help{color:#646970;font-size:12px}.mdo-promo-status{display:inline-block;padding:4px 9px;border-radius:999px;background:#f0f0f1;font-weight:600}.mdo-promo-status.is-active{background:#edfaef;color:#116329}.mdo-promo-status.is-expired{background:#fcf0f1;color:#8a2424}.mdo-promo-status.is-scheduled{background:#fff8e5;color:#6e5500}@media(max-width:782px){.mdo-promo-grid{grid-template-columns:1fr}}
		</style>
		<p>La promoción es la capa editorial. El descuento, regalo, pack o regla comercial real debe seguir ejecutándose en WooCommerce o en la lógica correspondiente.</p>
		<p><span class="mdo-promo-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( self::status_label( $status ) ); ?></span></p>
		<div class="mdo-promo-grid">
			<div class="mdo-promo-field">
				<label for="mdo_promo_type">Tipo interno</label>
				<select id="mdo_promo_type" name="mdo_promo_type">
					<?php foreach ( self::types() as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $meta['type'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?>
				</select>
				<span class="mdo-promo-help">Solo organiza el backoffice; no limita el diseño público.</span>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_eyebrow">Antetítulo</label>
				<input id="mdo_promo_eyebrow" name="mdo_promo_eyebrow" type="text" value="<?php echo esc_attr( $meta['eyebrow'] ); ?>" placeholder="Regalo con tu pedido">
			</div>
			<div class="mdo-promo-field is-wide">
				<label for="mdo_promo_summary">Resumen corto</label>
				<textarea id="mdo_promo_summary" name="mdo_promo_summary" rows="3" placeholder="Texto breve para el índice de ofertas."><?php echo esc_textarea( $meta['summary'] ); ?></textarea>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_start">Fecha de inicio</label>
				<input id="mdo_promo_start" name="mdo_promo_start" type="date" value="<?php echo esc_attr( $meta['start'] ); ?>">
				<span class="mdo-promo-help">Vacío = activa desde el momento de publicación.</span>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_end">Fecha de fin</label>
				<input id="mdo_promo_end" name="mdo_promo_end" type="date" value="<?php echo esc_attr( $meta['end'] ); ?>">
				<span class="mdo-promo-help">Vacío = sin caducidad automática.</span>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_supplier_id">Productor</label>
				<select id="mdo_promo_supplier_id" name="mdo_promo_supplier_id">
					<option value="0">Sin productor concreto</option>
					<?php foreach ( $suppliers as $supplier ) : ?><option value="<?php echo (int) $supplier['id']; ?>" <?php selected( (int) $meta['supplier_id'], (int) $supplier['id'] ); ?>><?php echo esc_html( $supplier['name'] ); ?></option><?php endforeach; ?>
				</select>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_coupon">Cupón</label>
				<input id="mdo_promo_coupon" name="mdo_promo_coupon" type="text" value="<?php echo esc_attr( $meta['coupon'] ); ?>" placeholder="Opcional">
			</div>
			<div class="mdo-promo-field is-wide">
				<label for="mdo_promo_benefit">Cómo funciona / beneficio</label>
				<textarea id="mdo_promo_benefit" name="mdo_promo_benefit" rows="4"><?php echo esc_textarea( $meta['benefit'] ); ?></textarea>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_cta_label">Texto del botón</label>
				<input id="mdo_promo_cta_label" name="mdo_promo_cta_label" type="text" value="<?php echo esc_attr( $meta['cta_label'] ); ?>" placeholder="Comprar ahora">
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_cta_url">Destino del botón</label>
				<input id="mdo_promo_cta_url" name="mdo_promo_cta_url" type="url" value="<?php echo esc_attr( $meta['cta_url'] ); ?>" placeholder="https://…">
				<span class="mdo-promo-help">Si queda vacío, intentaremos enlazar a la tienda del productor.</span>
			</div>
			<div class="mdo-promo-field is-wide">
				<label for="mdo_promo_product_ids">Productos relacionados</label>
				<input id="mdo_promo_product_ids" name="mdo_promo_product_ids" type="text" value="<?php echo esc_attr( $meta['product_ids'] ); ?>" placeholder="123, 456, 789">
				<span class="mdo-promo-help">IDs de producto WooCommerce separados por comas. Opcional.</span>
			</div>
			<div class="mdo-promo-field is-wide">
				<label for="mdo_promo_conditions">Condiciones</label>
				<textarea id="mdo_promo_conditions" name="mdo_promo_conditions" rows="4" placeholder="Condiciones visibles para el cliente."><?php echo esc_textarea( $meta['conditions'] ); ?></textarea>
			</div>
			<div class="mdo-promo-field">
				<label for="mdo_promo_order">Orden</label>
				<input id="mdo_promo_order" name="mdo_promo_order" type="number" min="0" step="1" value="<?php echo esc_attr( (string) $post->menu_order ); ?>">
				<span class="mdo-promo-help">0 aparece antes que 10. A igualdad, primero la más reciente.</span>
			</div>
			<div class="mdo-promo-field">
				<label><input name="mdo_promo_featured_home" type="checkbox" value="1" <?php checked( $meta['featured_home'], '1' ); ?>> Destacada para la home</label>
				<span class="mdo-promo-help">Queda preparado para la futura sección de portada; por ahora no modifica la home.</span>
			</div>
		</div>
		<?php
	}

	public static function save_meta( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) { return; }
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }
		$type = isset( $_POST['mdo_promo_type'] ) ? sanitize_key( wp_unslash( $_POST['mdo_promo_type'] ) ) : 'custom';
		if ( ! array_key_exists( $type, self::types() ) ) { $type = 'custom'; }
		$values = array(
			'type'          => $type,
			'eyebrow'       => isset( $_POST['mdo_promo_eyebrow'] ) ? sanitize_text_field( wp_unslash( $_POST['mdo_promo_eyebrow'] ) ) : '',
			'summary'       => isset( $_POST['mdo_promo_summary'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mdo_promo_summary'] ) ) : '',
			'start'         => self::sanitize_date( $_POST['mdo_promo_start'] ?? '' ),
			'end'           => self::sanitize_date( $_POST['mdo_promo_end'] ?? '' ),
			'supplier_id'   => isset( $_POST['mdo_promo_supplier_id'] ) ? absint( $_POST['mdo_promo_supplier_id'] ) : 0,
			'coupon'        => isset( $_POST['mdo_promo_coupon'] ) ? sanitize_text_field( wp_unslash( $_POST['mdo_promo_coupon'] ) ) : '',
			'benefit'       => isset( $_POST['mdo_promo_benefit'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mdo_promo_benefit'] ) ) : '',
			'cta_label'     => isset( $_POST['mdo_promo_cta_label'] ) ? sanitize_text_field( wp_unslash( $_POST['mdo_promo_cta_label'] ) ) : '',
			'cta_url'       => isset( $_POST['mdo_promo_cta_url'] ) ? esc_url_raw( wp_unslash( $_POST['mdo_promo_cta_url'] ) ) : '',
			'product_ids'   => self::sanitize_product_ids( $_POST['mdo_promo_product_ids'] ?? '' ),
			'conditions'    => isset( $_POST['mdo_promo_conditions'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mdo_promo_conditions'] ) ) : '',
			'featured_home' => isset( $_POST['mdo_promo_featured_home'] ) ? '1' : '0',
		);
		foreach ( $values as $key => $value ) { update_post_meta( $post_id, '_mdo_promo_' . $key, $value ); }
		$order = isset( $_POST['mdo_promo_order'] ) ? max( 0, absint( $_POST['mdo_promo_order'] ) ) : 0;
		if ( (int) $post->menu_order !== $order ) {
			global $wpdb;
			$wpdb->update( $wpdb->posts, array( 'menu_order' => $order ), array( 'ID' => $post_id ), array( '%d' ), array( '%d' ) );
			clean_post_cache( $post_id );
		}
	}

	public static function filter_archive_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( self::POST_TYPE ) ) { return; }
		$today = wp_date( 'Y-m-d', null, wp_timezone() );
		$query->set( 'posts_per_page', 24 );
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
		$query->set( 'order', 'ASC' );
		$query->set( 'meta_query', array(
			'relation' => 'AND',
			array( 'relation' => 'OR',
				array( 'key' => '_mdo_promo_start', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_mdo_promo_start', 'value' => '', 'compare' => '=' ),
				array( 'key' => '_mdo_promo_start', 'value' => $today, 'compare' => '<=', 'type' => 'DATE' ),
			),
			array( 'relation' => 'OR',
				array( 'key' => '_mdo_promo_end', 'compare' => 'NOT EXISTS' ),
				array( 'key' => '_mdo_promo_end', 'value' => '', 'compare' => '=' ),
				array( 'key' => '_mdo_promo_end', 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ),
			),
		) );
	}

	public static function template_include( string $template ): string {
		if ( is_post_type_archive( self::POST_TYPE ) ) {
			$candidate = MDO_SUPPLIER_SYNC_PATH . 'templates/archive-mdo-promotion.php';
			return file_exists( $candidate ) ? $candidate : $template;
		}
		if ( is_singular( self::POST_TYPE ) ) {
			$candidate = MDO_SUPPLIER_SYNC_PATH . 'templates/single-mdo-promotion.php';
			return file_exists( $candidate ) ? $candidate : $template;
		}
		return $template;
	}

	public static function enqueue_assets(): void {
		if ( ! is_post_type_archive( self::POST_TYPE ) && ! is_singular( self::POST_TYPE ) ) { return; }
		wp_enqueue_style( 'mdo-promotions', MDO_SUPPLIER_SYNC_URL . 'assets/css/promotions.css', array(), MDO_SUPPLIER_SYNC_VERSION );
	}

	public static function admin_columns( array $columns ): array {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['mdo_promo_type']   = 'Tipo';
				$new['mdo_promo_window'] = 'Vigencia';
				$new['mdo_promo_status'] = 'Estado';
			}
		}
		return $new;
	}

	public static function admin_column( string $column, int $post_id ): void {
		$meta = self::meta( $post_id );
		if ( 'mdo_promo_type' === $column ) { echo esc_html( self::types()[ $meta['type'] ] ?? 'Personalizada' ); return; }
		if ( 'mdo_promo_window' === $column ) {
			$start = $meta['start'] ?: 'Ahora';
			$end   = $meta['end'] ?: 'Sin fin';
			echo esc_html( $start . ' → ' . $end );
			return;
		}
		if ( 'mdo_promo_status' === $column ) { echo esc_html( self::status_label( self::status( $post_id ) ) ); }
	}

	public static function updated_messages( array $messages ): array {
		$messages[ self::POST_TYPE ] = array( 0 => '', 1 => 'Promoción actualizada.', 2 => 'Campo actualizado.', 3 => 'Campo eliminado.', 4 => 'Promoción actualizada.', 5 => false, 6 => 'Promoción publicada.', 7 => 'Promoción guardada.', 8 => 'Promoción enviada.', 9 => 'Promoción programada.', 10 => 'Borrador de promoción actualizado.' );
		return $messages;
	}

	public static function meta( int $post_id ): array {
		$defaults = array( 'type' => 'custom', 'eyebrow' => '', 'summary' => '', 'start' => '', 'end' => '', 'supplier_id' => 0, 'coupon' => '', 'benefit' => '', 'cta_label' => '', 'cta_url' => '', 'product_ids' => '', 'conditions' => '', 'featured_home' => '0' );
		foreach ( $defaults as $key => $default ) {
			$value = get_post_meta( $post_id, '_mdo_promo_' . $key, true );
			$defaults[ $key ] = '' === $value ? $default : $value;
		}
		return $defaults;
	}

	public static function status( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) { return 'draft'; }
		$meta  = self::meta( $post_id );
		$today = wp_date( 'Y-m-d', null, wp_timezone() );
		if ( $meta['start'] && $meta['start'] > $today ) { return 'scheduled'; }
		if ( $meta['end'] && $meta['end'] < $today ) { return 'expired'; }
		return 'active';
	}

	public static function is_active( int $post_id ): bool { return 'active' === self::status( $post_id ); }

	public static function supplier( int $post_id ): ?array {
		$meta = self::meta( $post_id );
		$id   = (int) $meta['supplier_id'];
		if ( ! $id || ! class_exists( 'MDO_Supplier_Repository' ) ) { return null; }
		$supplier = MDO_Supplier_Repository::find( $id );
		return is_array( $supplier ) ? $supplier : null;
	}

	public static function cta_url( int $post_id ): string {
		$meta = self::meta( $post_id );
		if ( $meta['cta_url'] ) { return (string) $meta['cta_url']; }
		$supplier = self::supplier( $post_id );
		if ( $supplier && ! empty( $supplier['vendor_user_id'] ) && function_exists( 'wcfmmp_get_store_url' ) ) { return (string) wcfmmp_get_store_url( (int) $supplier['vendor_user_id'] ); }
		return '';
	}

	public static function product_ids( int $post_id ): array {
		$meta = self::meta( $post_id );
		if ( ! $meta['product_ids'] ) { return array(); }
		return array_values( array_filter( array_map( 'absint', explode( ',', (string) $meta['product_ids'] ) ) ) );
	}

	public static function format_date( string $date ): string {
		if ( ! $date ) { return ''; }
		$timestamp = strtotime( $date . ' 12:00:00' );
		return $timestamp ? wp_date( 'j \d\e F \d\e Y', $timestamp, wp_timezone() ) : $date;
	}

	public static function types(): array {
		return array( 'gift' => 'Regalo', 'coupon' => 'Cupón / descuento', 'pack' => 'Pack especial', 'custom' => 'Personalizada' );
	}

	private static function status_label( string $status ): string {
		$labels = array( 'active' => 'Activa', 'expired' => 'Finalizada', 'scheduled' => 'Programada', 'draft' => 'Borrador' );
		return $labels[ $status ] ?? ucfirst( $status );
	}

	private static function sanitize_date( $value ): string {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) { return ''; }
		$parts = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $parts[1], $parts[2], $parts[0] ) ? $value : '';
	}

	private static function sanitize_product_ids( $value ): string {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		$ids = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', $value ) ) ) ) );
		return implode( ',', $ids );
	}

	public static function maybe_seed_tolecarnes_promotion(): void {
		if ( get_option( 'mdo_promotions_seeded_v1' ) ) { return; }
		$existing = get_posts( array( 'post_type' => self::POST_TYPE, 'post_status' => 'any', 'posts_per_page' => 1, 'meta_key' => '_mdo_promo_seed_key', 'meta_value' => self::SEED_KEY, 'fields' => 'ids' ) );
		if ( $existing ) { update_option( 'mdo_promotions_seeded_v1', 1, false ); return; }
		$supplier_id = 0;
		if ( class_exists( 'MDO_Supplier_Repository' ) ) {
			foreach ( MDO_Supplier_Repository::all() as $supplier ) {
				$name = isset( $supplier['name'] ) ? remove_accents( strtolower( (string) $supplier['name'] ) ) : '';
				if ( false !== strpos( $name, 'tole' ) && false !== strpos( $name, 'carne' ) ) { $supplier_id = (int) $supplier['id']; break; }
			}
		}
		$post_id = wp_insert_post( array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
			'post_title' => 'Dos hamburguesas de vaca madurada de regalo',
			'post_name' => 'hamburguesas-regalo-tole-carnes',
			'post_excerpt' => 'Dos hamburguesas de vaca madurada de regalo con tu pedido de Tole Carnes.',
			'post_content' => '<p>Una ventaja especial para los pedidos de Tole Carnes: recibe dos hamburguesas de vaca madurada de regalo junto a tu compra.</p>',
			'menu_order' => 0,
		), true );
		if ( is_wp_error( $post_id ) ) { error_log( '[EMDO promociones] No se pudo crear la promoción inicial: ' . $post_id->get_error_message() ); return; }
		$seed_meta = array(
			'_mdo_promo_seed_key' => self::SEED_KEY,
			'_mdo_promo_type' => 'gift',
			'_mdo_promo_eyebrow' => 'Regalo con tu pedido',
			'_mdo_promo_summary' => 'Recibe dos hamburguesas de vaca madurada de regalo con tu pedido de Tole Carnes.',
			'_mdo_promo_start' => '', '_mdo_promo_end' => '', '_mdo_promo_supplier_id' => $supplier_id, '_mdo_promo_coupon' => '',
			'_mdo_promo_benefit' => 'Haz tu pedido a Tole Carnes y recibirás dos hamburguesas de vaca madurada de regalo junto a tu compra.',
			'_mdo_promo_cta_label' => 'Comprar en Tole Carnes', '_mdo_promo_cta_url' => '', '_mdo_promo_product_ids' => '', '_mdo_promo_conditions' => '', '_mdo_promo_featured_home' => '1',
		);
		foreach ( $seed_meta as $key => $value ) { update_post_meta( (int) $post_id, $key, $value ); }
		update_option( 'mdo_promotions_seeded_v1', 1, false );
	}
}
