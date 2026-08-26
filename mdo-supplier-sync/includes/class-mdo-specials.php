<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bilingual public layer for EMDO promotions.
 *
 * Commercial data is stored once on mdo_promotion. Customer-facing copy is stored
 * in ES/EN meta fields so dates, products, producer and coupon cannot drift between languages.
 */
final class MDO_Specials {
	private const POST_TYPE = 'mdo_promotion';
	private const NONCE_ACTION = 'mdo_save_specials_meta';
	private const NONCE_NAME = 'mdo_specials_nonce';
	private const REWRITE_VERSION = '1';
	private const SEED_KEY = 'tolecarnes-hamburguesas-v2';
	private static bool $syncing = false;

	public static function init(): void {
		add_filter( 'register_post_type_args', array( __CLASS__, 'filter_post_type_args' ), 20, 2 );
		add_action( 'init', array( __CLASS__, 'register_english_routes' ), 7 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_filter( 'request', array( __CLASS__, 'resolve_english_single' ), 20 );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrites' ), 21 );
		add_action( 'init', array( __CLASS__, 'migrate_tolecarnes_special' ), 31 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'replace_meta_box' ), 99 );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 20, 3 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ), 99 );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy_routes' ), 1 );
		add_filter( 'redirect_canonical', array( __CLASS__, 'disable_english_canonical_redirect' ), 20, 2 );
		add_filter( 'get_canonical_url', array( __CLASS__, 'canonical_url' ), 20, 2 );
		add_action( 'wp_head', array( __CLASS__, 'output_language_links' ), 4 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ), 30 );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column' ), 30, 2 );
	}

	public static function filter_post_type_args( array $args, string $post_type ): array {
		if ( self::POST_TYPE !== $post_type ) { return $args; }
		$args['has_archive'] = 'especiales';
		$args['rewrite'] = array( 'slug' => 'especiales', 'with_front' => false );
		$args['supports'] = array( 'thumbnail', 'page-attributes' );
		return $args;
	}

	public static function register_english_routes(): void {
		add_rewrite_rule( '^en/specials/?$', 'index.php?post_type=' . self::POST_TYPE . '&mdo_specials_lang=en', 'top' );
		add_rewrite_rule( '^en/specials/([^/]+)/?$', 'index.php?mdo_specials_lang=en&mdo_specials_slug=$matches[1]', 'top' );
	}

	public static function query_vars( array $vars ): array {
		$vars[] = 'mdo_specials_lang';
		$vars[] = 'mdo_specials_slug';
		return $vars;
	}

	public static function resolve_english_single( array $vars ): array {
		if ( empty( $vars['mdo_specials_slug'] ) ) { return $vars; }
		$slug = sanitize_title( (string) $vars['mdo_specials_slug'] );
		$ids = get_posts( array(
			'post_type' => self::POST_TYPE,
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'fields' => 'ids',
			'meta_key' => '_mdo_promo_slug_en',
			'meta_value' => $slug,
		) );
		if ( $ids ) {
			return array( 'post_type' => self::POST_TYPE, 'p' => (int) $ids[0], 'mdo_specials_lang' => 'en' );
		}
		return $vars;
	}

	public static function maybe_flush_rewrites(): void {
		if ( get_option( 'mdo_specials_rewrite_version' ) === self::REWRITE_VERSION ) { return; }
		flush_rewrite_rules( false );
		update_option( 'mdo_specials_rewrite_version', self::REWRITE_VERSION, false );
	}

	public static function replace_meta_box(): void {
		remove_meta_box( 'mdo-promotion-details', self::POST_TYPE, 'normal' );
		add_meta_box( 'mdo-specials-details', 'Promoción · contenido y reglas', array( __CLASS__, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$shared = self::shared( $post->ID );
		$suppliers = class_exists( 'MDO_Supplier_Repository' ) ? MDO_Supplier_Repository::all() : array();
		$product = $shared['image_product_id'] ? get_post( (int) $shared['image_product_id'] ) : null;
		$status = class_exists( 'MDO_Promotions' ) ? MDO_Promotions::status( $post->ID ) : 'draft';
		?>
		<style>
		.mdo-sp-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 20px}.mdo-sp-field{display:flex;flex-direction:column;gap:6px}.mdo-sp-wide{grid-column:1/-1}.mdo-sp-field label{font-weight:600}.mdo-sp-field input[type=text],.mdo-sp-field input[type=url],.mdo-sp-field input[type=date],.mdo-sp-field input[type=number],.mdo-sp-field select,.mdo-sp-field textarea{width:100%}.mdo-sp-help{font-size:12px;color:#646970}.mdo-sp-status{display:inline-block;padding:4px 9px;border-radius:999px;background:#f0f0f1;font-weight:600}.mdo-sp-tabs{grid-column:1/-1;display:flex;gap:6px;border-bottom:1px solid #dcdcde;margin-top:8px}.mdo-sp-tab{border:1px solid #dcdcde;border-bottom:0;background:#f6f7f7;padding:9px 14px;font-weight:600;cursor:pointer}.mdo-sp-tab.is-active{background:#fff;position:relative;bottom:-1px}.mdo-sp-panel{grid-column:1/-1;display:none}.mdo-sp-panel.is-active{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:16px 20px;padding-top:10px}.mdo-sp-panel .mdo-sp-wide{grid-column:1/-1}@media(max-width:782px){.mdo-sp-grid,.mdo-sp-panel.is-active{grid-template-columns:1fr}.mdo-sp-wide,.mdo-sp-panel .mdo-sp-wide{grid-column:1}}
		</style>
		<p>Una sola promoción comparte productor, fechas, cupón y productos. Solo se traducen los textos visibles para el cliente.</p>
		<p><span class="mdo-sp-status"><?php echo esc_html( ucfirst( $status ) ); ?></span></p>
		<div class="mdo-sp-grid">
			<div class="mdo-sp-field"><label for="mdo_sp_type">Tipo interno</label><select id="mdo_sp_type" name="mdo_sp_type"><?php foreach ( self::types() as $value => $label ) : ?><option value="<?php echo esc_attr( $value ); ?>" <?php selected( $shared['type'], $value ); ?>><?php echo esc_html( $label ); ?></option><?php endforeach; ?></select></div>
			<div class="mdo-sp-field"><label for="mdo_sp_supplier">Productor</label><select id="mdo_sp_supplier" name="mdo_sp_supplier"><option value="0">Sin productor concreto</option><?php foreach ( $suppliers as $supplier ) : ?><option value="<?php echo (int) $supplier['id']; ?>" <?php selected( (int) $shared['supplier_id'], (int) $supplier['id'] ); ?>><?php echo esc_html( (string) $supplier['name'] ); ?></option><?php endforeach; ?></select></div>
			<div class="mdo-sp-field"><label for="mdo_sp_start">Fecha de inicio</label><input id="mdo_sp_start" name="mdo_sp_start" type="date" value="<?php echo esc_attr( $shared['start'] ); ?>"></div>
			<div class="mdo-sp-field"><label for="mdo_sp_end">Fecha de fin</label><input id="mdo_sp_end" name="mdo_sp_end" type="date" value="<?php echo esc_attr( $shared['end'] ); ?>"><span class="mdo-sp-help">El último día está incluido.</span></div>
			<div class="mdo-sp-field"><label for="mdo_sp_coupon">Cupón</label><input id="mdo_sp_coupon" name="mdo_sp_coupon" type="text" value="<?php echo esc_attr( $shared['coupon'] ); ?>" placeholder="Opcional"></div>
			<div class="mdo-sp-field"><label for="mdo_sp_order">Orden</label><input id="mdo_sp_order" name="mdo_sp_order" type="number" min="0" step="1" value="<?php echo esc_attr( (string) $post->menu_order ); ?>"></div>
			<div class="mdo-sp-field mdo-sp-wide"><label for="mdo_sp_products">Productos relacionados</label><input id="mdo_sp_products" name="mdo_sp_products" type="text" value="<?php echo esc_attr( $shared['product_ids'] ); ?>" placeholder="123, 456"><span class="mdo-sp-help">IDs WooCommerce separados por comas.</span></div>
			<div class="mdo-sp-field mdo-sp-wide"><label for="mdo_sp_image_product">Producto cuya imagen se usa</label><input id="mdo_sp_image_product" name="mdo_sp_image_product" type="number" min="0" value="<?php echo esc_attr( (string) $shared['image_product_id'] ); ?>"><?php if ( $product ) : ?><span class="mdo-sp-help">Actual: <?php echo esc_html( $product->post_title ); ?>. La imagen se hereda automáticamente salvo que se elija una imagen destacada específica.</span><?php endif; ?></div>
			<div class="mdo-sp-field mdo-sp-wide"><label for="mdo_sp_cta_url">Destino del botón</label><input id="mdo_sp_cta_url" name="mdo_sp_cta_url" type="url" value="<?php echo esc_attr( $shared['cta_url'] ); ?>" placeholder="Opcional; vacío = tienda del productor"></div>
			<div class="mdo-sp-field"><label><input name="mdo_sp_featured" type="checkbox" value="1" <?php checked( $shared['featured_home'], '1' ); ?>> Destacada para la home</label><span class="mdo-sp-help">Solo prepara el dato; no modifica la home.</span></div>
			<div class="mdo-sp-tabs"><button type="button" class="mdo-sp-tab is-active" data-lang="es">Español</button><button type="button" class="mdo-sp-tab" data-lang="en">English</button></div>
			<?php foreach ( array( 'es' => 'Español', 'en' => 'English' ) as $lang => $label ) : $copy = self::copy( $post->ID, $lang ); ?>
				<div class="mdo-sp-panel <?php echo 'es' === $lang ? 'is-active' : ''; ?>" data-panel="<?php echo esc_attr( $lang ); ?>">
					<div class="mdo-sp-field"><label>Título · <?php echo esc_html( $label ); ?></label><input name="mdo_sp_title_<?php echo esc_attr( $lang ); ?>" type="text" value="<?php echo esc_attr( $copy['title'] ); ?>"></div>
					<div class="mdo-sp-field"><label>Slug · <?php echo esc_html( $label ); ?></label><input name="mdo_sp_slug_<?php echo esc_attr( $lang ); ?>" type="text" value="<?php echo esc_attr( $copy['slug'] ); ?>"></div>
					<div class="mdo-sp-field mdo-sp-wide"><label>Antetítulo · <?php echo esc_html( $label ); ?></label><input name="mdo_sp_eyebrow_<?php echo esc_attr( $lang ); ?>" type="text" value="<?php echo esc_attr( $copy['eyebrow'] ); ?>"></div>
					<div class="mdo-sp-field mdo-sp-wide"><label>Resumen · <?php echo esc_html( $label ); ?></label><textarea name="mdo_sp_summary_<?php echo esc_attr( $lang ); ?>" rows="3"><?php echo esc_textarea( $copy['summary'] ); ?></textarea></div>
					<div class="mdo-sp-field mdo-sp-wide"><label>Cómo funciona · <?php echo esc_html( $label ); ?></label><textarea name="mdo_sp_benefit_<?php echo esc_attr( $lang ); ?>" rows="4"><?php echo esc_textarea( $copy['benefit'] ); ?></textarea></div>
					<div class="mdo-sp-field mdo-sp-wide"><label>Contenido ampliado · <?php echo esc_html( $label ); ?></label><textarea name="mdo_sp_content_<?php echo esc_attr( $lang ); ?>" rows="6"><?php echo esc_textarea( $copy['content'] ); ?></textarea></div>
					<div class="mdo-sp-field"><label>Texto del botón · <?php echo esc_html( $label ); ?></label><input name="mdo_sp_cta_label_<?php echo esc_attr( $lang ); ?>" type="text" value="<?php echo esc_attr( $copy['cta_label'] ); ?>"></div>
					<div class="mdo-sp-field mdo-sp-wide"><label>Condiciones · <?php echo esc_html( $label ); ?></label><textarea name="mdo_sp_conditions_<?php echo esc_attr( $lang ); ?>" rows="4"><?php echo esc_textarea( $copy['conditions'] ); ?></textarea></div>
				</div>
			<?php endforeach; ?>
		</div>
		<script>(function(){var t=document.querySelectorAll('.mdo-sp-tab'),p=document.querySelectorAll('.mdo-sp-panel');t.forEach(function(b){b.addEventListener('click',function(){var l=b.dataset.lang;t.forEach(function(x){x.classList.toggle('is-active',x===b)});p.forEach(function(x){x.classList.toggle('is-active',x.dataset.panel===l)});});});})();</script>
		<?php
	}

	public static function save_meta( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );
		if ( self::$syncing || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) { return; }
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

		$type = sanitize_key( wp_unslash( $_POST['mdo_sp_type'] ?? 'custom' ) );
		if ( ! isset( self::types()[ $type ] ) ) { $type = 'custom'; }
		$shared = array(
			'type' => $type,
			'start' => self::sanitize_date( $_POST['mdo_sp_start'] ?? '' ),
			'end' => self::sanitize_date( $_POST['mdo_sp_end'] ?? '' ),
			'supplier_id' => absint( $_POST['mdo_sp_supplier'] ?? 0 ),
			'coupon' => sanitize_text_field( wp_unslash( $_POST['mdo_sp_coupon'] ?? '' ) ),
			'cta_url' => esc_url_raw( wp_unslash( $_POST['mdo_sp_cta_url'] ?? '' ) ),
			'product_ids' => self::sanitize_ids( $_POST['mdo_sp_products'] ?? '' ),
			'image_product_id' => absint( $_POST['mdo_sp_image_product'] ?? 0 ),
			'featured_home' => isset( $_POST['mdo_sp_featured'] ) ? '1' : '0',
		);
		foreach ( $shared as $key => $value ) { update_post_meta( $post_id, '_mdo_promo_' . $key, $value ); }

		foreach ( array( 'es', 'en' ) as $lang ) {
			$data = array(
				'title' => sanitize_text_field( wp_unslash( $_POST[ 'mdo_sp_title_' . $lang ] ?? '' ) ),
				'slug' => sanitize_title( wp_unslash( $_POST[ 'mdo_sp_slug_' . $lang ] ?? '' ) ),
				'eyebrow' => sanitize_text_field( wp_unslash( $_POST[ 'mdo_sp_eyebrow_' . $lang ] ?? '' ) ),
				'summary' => sanitize_textarea_field( wp_unslash( $_POST[ 'mdo_sp_summary_' . $lang ] ?? '' ) ),
				'benefit' => sanitize_textarea_field( wp_unslash( $_POST[ 'mdo_sp_benefit_' . $lang ] ?? '' ) ),
				'content' => wp_kses_post( wp_unslash( $_POST[ 'mdo_sp_content_' . $lang ] ?? '' ) ),
				'cta_label' => sanitize_text_field( wp_unslash( $_POST[ 'mdo_sp_cta_label_' . $lang ] ?? '' ) ),
				'conditions' => sanitize_textarea_field( wp_unslash( $_POST[ 'mdo_sp_conditions_' . $lang ] ?? '' ) ),
			);
			if ( ! $data['slug'] && $data['title'] ) { $data['slug'] = sanitize_title( $data['title'] ); }
			foreach ( $data as $field => $value ) { update_post_meta( $post_id, '_mdo_promo_' . $field . '_' . $lang, $value ); }
		}

		$es = self::copy( $post_id, 'es' );
		self::$syncing = true;
		wp_update_post( array(
			'ID' => $post_id,
			'post_title' => $es['title'] ?: 'Promoción sin título',
			'post_name' => $es['slug'] ?: sanitize_title( $es['title'] ),
			'post_excerpt' => $es['summary'],
			'post_content' => $es['content'],
			'menu_order' => max( 0, absint( $_POST['mdo_sp_order'] ?? 0 ) ),
		) );
		self::$syncing = false;
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

	public static function shared( int $post_id ): array {
		$defaults = array( 'type' => 'custom', 'start' => '', 'end' => '', 'supplier_id' => 0, 'coupon' => '', 'cta_url' => '', 'product_ids' => '', 'image_product_id' => 0, 'featured_home' => '0' );
		foreach ( $defaults as $key => $default ) {
			$value = get_post_meta( $post_id, '_mdo_promo_' . $key, true );
			$defaults[ $key ] = '' === $value ? $default : $value;
		}
		return $defaults;
	}

	public static function copy( int $post_id, string $lang ): array {
		$lang = 'en' === $lang ? 'en' : 'es';
		$post = get_post( $post_id );
		$legacy = array(
			'title' => $post ? $post->post_title : '', 'slug' => $post ? $post->post_name : '',
			'eyebrow' => (string) get_post_meta( $post_id, '_mdo_promo_eyebrow', true ),
			'summary' => (string) get_post_meta( $post_id, '_mdo_promo_summary', true ),
			'benefit' => (string) get_post_meta( $post_id, '_mdo_promo_benefit', true ),
			'content' => $post ? $post->post_content : '',
			'cta_label' => (string) get_post_meta( $post_id, '_mdo_promo_cta_label', true ),
			'conditions' => (string) get_post_meta( $post_id, '_mdo_promo_conditions', true ),
		);
		$out = array();
		foreach ( $legacy as $field => $fallback ) {
			$value = (string) get_post_meta( $post_id, '_mdo_promo_' . $field . '_' . $lang, true );
			if ( '' === $value && 'es' === $lang ) { $value = $fallback; }
			$out[ $field ] = $value;
		}
		return $out;
	}

	public static function text( int $post_id, string $field, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$data = self::copy( $post_id, $lang );
		if ( ! empty( $data[ $field ] ) ) { return (string) $data[ $field ]; }
		if ( 'en' === $lang ) { $es = self::copy( $post_id, 'es' ); return (string) ( $es[ $field ] ?? '' ); }
		return '';
	}

	public static function language(): string {
		if ( 'en' === get_query_var( 'mdo_specials_lang' ) ) { return 'en'; }
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		return 0 === strpos( $path, 'en/specials' ) ? 'en' : 'es';
	}

	public static function strings( string $key, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$s = array(
			'es' => array(
				'kicker' => 'Ahora en El Mercado', 'title' => 'Especiales del Mercado', 'intro' => 'Regalos, packs, ventajas y propuestas especiales que preparamos junto a nuestros productores. Aquí encontrarás únicamente las que están disponibles ahora.', 'view' => 'Ver especial', 'until' => 'Disponible hasta el', 'prev' => 'Anterior', 'next' => 'Siguiente', 'empty_title' => 'No hay especiales activos ahora mismo', 'empty' => 'Cuando tengamos una propuesta especial disponible, aparecerá aquí.', 'shop' => 'Ver la tienda', 'with' => 'Con', 'expired_title' => 'Este especial ha finalizado.', 'expired' => 'Puedes consultar los especiales que están disponibles actualmente.', 'current' => 'Ver especiales actuales', 'scheduled' => 'Este especial estará disponible a partir del', 'how' => 'Cómo funciona', 'related' => 'Productos relacionados', 'conditions' => 'Condiciones', 'code' => 'Código de la promoción', 'copy' => 'Copiar código', 'copied' => 'Copiado', 'all' => 'Todos los especiales', 'buy' => 'Comprar ahora',
			),
			'en' => array(
				'kicker' => 'Now at El Mercado', 'title' => 'Market Specials', 'intro' => 'Gifts, packs, benefits and special proposals created together with our producers. Here you will only find the ones available right now.', 'view' => 'View special', 'until' => 'Available until', 'prev' => 'Previous', 'next' => 'Next', 'empty_title' => 'There are no active specials right now', 'empty' => 'When a special proposal is available, it will appear here.', 'shop' => 'Visit the shop', 'with' => 'With', 'expired_title' => 'This special has ended.', 'expired' => 'You can browse the specials that are currently available.', 'current' => 'View current specials', 'scheduled' => 'This special will be available from', 'how' => 'How it works', 'related' => 'Related products', 'conditions' => 'Conditions', 'code' => 'Promotion code', 'copy' => 'Copy code', 'copied' => 'Copied', 'all' => 'All specials', 'buy' => 'Shop now',
			),
		);
		return (string) ( $s[ $lang ][ $key ] ?? $s['es'][ $key ] ?? $key );
	}

	public static function archive_url( ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		return 'en' === $lang ? home_url( '/en/specials/' ) : home_url( '/especiales/' );
	}

	public static function permalink( int $post_id, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$slug = self::text( $post_id, 'slug', $lang );
		if ( ! $slug ) { $slug = (string) get_post_field( 'post_name', $post_id ); }
		return 'en' === $lang ? home_url( '/en/specials/' . $slug . '/' ) : home_url( '/especiales/' . $slug . '/' );
	}

	public static function supplier( int $post_id ): ?array {
		return class_exists( 'MDO_Promotions' ) ? MDO_Promotions::supplier( $post_id ) : null;
	}

	public static function status( int $post_id ): string {
		return class_exists( 'MDO_Promotions' ) ? MDO_Promotions::status( $post_id ) : 'draft';
	}

	public static function product_ids( int $post_id ): array {
		$value = (string) self::shared( $post_id )['product_ids'];
		return $value ? array_values( array_filter( array_map( 'absint', explode( ',', $value ) ) ) ) : array();
	}

	public static function image_id( int $post_id ): int {
		$featured = get_post_thumbnail_id( $post_id );
		if ( $featured ) { return (int) $featured; }
		$shared = self::shared( $post_id );
		$product_id = (int) $shared['image_product_id'];
		if ( ! $product_id ) { $ids = self::product_ids( $post_id ); $product_id = $ids ? (int) $ids[0] : 0; }
		return $product_id ? (int) get_post_thumbnail_id( $product_id ) : 0;
	}

	public static function image_html( int $post_id, string $size = 'large', array $attrs = array() ): string {
		$id = self::image_id( $post_id );
		return $id ? (string) wp_get_attachment_image( $id, $size, false, $attrs ) : '';
	}

	public static function cta_url( int $post_id, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$url = (string) self::shared( $post_id )['cta_url'];
		if ( ! $url ) {
			$supplier = self::supplier( $post_id );
			if ( $supplier && ! empty( $supplier['vendor_user_id'] ) && function_exists( 'wcfmmp_get_store_url' ) ) { $url = (string) wcfmmp_get_store_url( (int) $supplier['vendor_user_id'] ); }
		}
		if ( 'en' === $lang && $url ) {
			$home = trailingslashit( home_url( '/' ) );
			if ( 0 === strpos( $url, $home ) ) {
				$relative = ltrim( substr( $url, strlen( $home ) ), '/' );
				if ( 0 !== strpos( $relative, 'en/' ) ) { $url = $home . 'en/' . $relative; }
			}
		}
		return $url;
	}

	public static function format_date( string $date, ?string $lang = null ): string {
		$parts = array_map( 'intval', explode( '-', $date ) );
		if ( 3 !== count( $parts ) || ! checkdate( $parts[1], $parts[2], $parts[0] ) ) { return $date; }
		$lang = $lang ?: self::language();
		if ( 'en' === $lang ) {
			$m = array( 1 => 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );
			return $parts[2] . ' ' . $m[ $parts[1] ] . ' ' . $parts[0];
		}
		$m = array( 1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre' );
		return $parts[2] . ' de ' . $m[ $parts[1] ] . ' de ' . $parts[0];
	}

	public static function redirect_legacy_routes(): void {
		if ( is_admin() ) { return; }
		$path = trim( (string) wp_parse_url( $_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH ), '/' );
		if ( 'ofertas' === $path ) { wp_safe_redirect( self::archive_url( 'es' ), 301 ); exit; }
		if ( 'ofertas/hamburguesas-regalo-tole-carnes' === $path ) { wp_safe_redirect( home_url( '/especiales/hamburguesas-ternera-regalo-tolecarnes/' ), 301 ); exit; }
		if ( 0 === strpos( $path, 'ofertas/' ) ) { wp_safe_redirect( home_url( '/especiales/' . substr( $path, 8 ) . '/' ), 301 ); exit; }
		if ( 'en/especiales' === $path ) { wp_safe_redirect( self::archive_url( 'en' ), 301 ); exit; }
	}

	public static function disable_english_canonical_redirect( $redirect, $requested ) {
		unset( $requested );
		return 'en' === self::language() && is_singular( self::POST_TYPE ) ? false : $redirect;
	}

	public static function canonical_url( string $canonical, WP_Post $post ): string {
		return self::POST_TYPE === $post->post_type ? self::permalink( $post->ID, self::language() ) : $canonical;
	}

	public static function output_language_links(): void {
		if ( is_singular( self::POST_TYPE ) ) {
			$id = get_queried_object_id();
			printf( '<link rel="alternate" hreflang="es" href="%s">' . "\n", esc_url( self::permalink( $id, 'es' ) ) );
			printf( '<link rel="alternate" hreflang="en" href="%s">' . "\n", esc_url( self::permalink( $id, 'en' ) ) );
			printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( self::permalink( $id, 'es' ) ) );
		} elseif ( is_post_type_archive( self::POST_TYPE ) ) {
			printf( '<link rel="alternate" hreflang="es" href="%s">' . "\n", esc_url( self::archive_url( 'es' ) ) );
			printf( '<link rel="alternate" hreflang="en" href="%s">' . "\n", esc_url( self::archive_url( 'en' ) ) );
			printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( self::archive_url( 'es' ) ) );
		}
	}

	public static function admin_columns( array $columns ): array {
		$columns['mdo_specials_en'] = 'English';
		return $columns;
	}

	public static function admin_column( string $column, int $post_id ): void {
		if ( 'mdo_specials_en' === $column ) { echo self::text( $post_id, 'title', 'en' ) ? '✓' : '—'; }
	}

	public static function migrate_tolecarnes_special(): void {
		if ( get_option( 'mdo_specials_tolecarnes_v2' ) ) { return; }
		$ids = get_posts( array(
			'post_type' => self::POST_TYPE, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids',
			'meta_query' => array( 'relation' => 'OR', array( 'key' => '_mdo_promo_seed_key', 'value' => self::SEED_KEY ), array( 'key' => '_mdo_promo_seed_key', 'value' => 'tolecarnes-hamburguesas-v1' ) ),
		) );
		if ( ! $ids ) {
			$legacy = get_page_by_path( 'hamburguesas-regalo-tole-carnes', OBJECT, self::POST_TYPE );
			if ( $legacy ) { $ids = array( $legacy->ID ); }
		}
		$post_id = $ids ? (int) $ids[0] : 0;
		if ( ! $post_id ) {
			$post_id = wp_insert_post( array( 'post_type' => self::POST_TYPE, 'post_status' => 'publish', 'post_title' => 'Dos hamburguesas de ternera de regalo', 'post_name' => 'hamburguesas-ternera-regalo-tolecarnes', 'menu_order' => 0 ), true );
			if ( is_wp_error( $post_id ) ) { error_log( '[EMDO especiales] ' . $post_id->get_error_message() ); return; }
		}

		$supplier_id = 0; $vendor_id = 0;
		if ( class_exists( 'MDO_Supplier_Repository' ) ) {
			foreach ( MDO_Supplier_Repository::all() as $supplier ) {
				$name = preg_replace( '/[^a-z0-9]+/', '', remove_accents( strtolower( (string) ( $supplier['name'] ?? '' ) ) ) );
				if ( false !== strpos( $name, 'tolecarnes' ) ) { $supplier_id = (int) $supplier['id']; $vendor_id = (int) ( $supplier['vendor_user_id'] ?? 0 ); break; }
			}
		}
		$product_id = self::find_tolecarnes_burger( $vendor_id );
		$image_id = $product_id ? (int) get_post_thumbnail_id( $product_id ) : 0;

		$shared = array(
			'_mdo_promo_seed_key' => self::SEED_KEY, '_mdo_promo_type' => 'gift', '_mdo_promo_start' => '2026-08-26', '_mdo_promo_end' => '2026-08-31', '_mdo_promo_supplier_id' => $supplier_id, '_mdo_promo_coupon' => '', '_mdo_promo_cta_url' => '', '_mdo_promo_product_ids' => $product_id ? (string) $product_id : '', '_mdo_promo_image_product_id' => $product_id, '_mdo_promo_featured_home' => '1',
		);
		foreach ( $shared as $key => $value ) { update_post_meta( $post_id, $key, $value ); }

		$es = array(
			'title' => 'Dos hamburguesas de ternera de regalo', 'slug' => 'hamburguesas-ternera-regalo-tolecarnes', 'eyebrow' => 'Regalo con tu pedido', 'summary' => 'Haz tu pedido a Tolecarnes y recibe dos hamburguesas 100% ternera de regalo.', 'benefit' => 'No tienes que introducir ningún código ni hacer nada especial. Haz tu pedido a Tolecarnes y recibirás dos hamburguesas de ternera de regalo junto a tu compra.', 'content' => '<p>Una ventaja especial para los pedidos de Tolecarnes: dos hamburguesas 100% ternera de regalo con tu compra.</p>', 'cta_label' => 'Ver productos de Tolecarnes', 'conditions' => 'Promoción válida para pedidos realizados a Tolecarnes hasta el 31 de agosto de 2026 incluido. No requiere código promocional.',
		);
		$en = array(
			'title' => 'Two free beef burgers', 'slug' => 'free-beef-burgers-tolecarnes', 'eyebrow' => 'A gift with your order', 'summary' => 'Place an order with Tolecarnes and receive two 100% beef burgers as a gift.', 'benefit' => 'No code or special action is required. Place your order with Tolecarnes and two beef burgers will be included as a gift with your purchase.', 'content' => '<p>A special benefit for Tolecarnes orders: receive two 100% beef burgers as a gift with your purchase.</p>', 'cta_label' => 'Shop Tolecarnes', 'conditions' => 'Valid for orders placed with Tolecarnes through 31 August 2026 inclusive. No promotional code is required.',
		);
		foreach ( array( 'es' => $es, 'en' => $en ) as $lang => $data ) { foreach ( $data as $field => $value ) { update_post_meta( $post_id, '_mdo_promo_' . $field . '_' . $lang, $value ); } }
		if ( $image_id ) { set_post_thumbnail( $post_id, $image_id ); }
		self::$syncing = true;
		wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish', 'post_title' => $es['title'], 'post_name' => $es['slug'], 'post_excerpt' => $es['summary'], 'post_content' => $es['content'], 'menu_order' => 0 ) );
		self::$syncing = false;

		if ( $product_id && $image_id ) { update_option( 'mdo_specials_tolecarnes_v2', 1, false ); }
		else { error_log( '[EMDO especiales] No se encontró con imagen el producto BURGER 100% TERNERA (2 UNIDADES) de Tolecarnes; se reintentará.' ); }
	}

	private static function find_tolecarnes_burger( int $vendor_id ): int {
		$args = array( 'post_type' => 'product', 'post_status' => array( 'publish', 'private', 'draft' ), 'posts_per_page' => -1, 'orderby' => 'ID', 'order' => 'DESC' );
		if ( $vendor_id ) { $args['author'] = $vendor_id; }
		$posts = get_posts( $args );
		if ( ! $posts && $vendor_id ) { unset( $args['author'] ); $posts = get_posts( $args ); }
		$best = 0; $best_score = 0;
		foreach ( $posts as $product ) {
			$title = remove_accents( strtolower( wp_strip_all_tags( $product->post_title ) ) );
			$compact = preg_replace( '/[^a-z0-9]+/', '', $title );
			if ( false !== strpos( $compact, 'burger100ternera2unidades' ) || false !== strpos( $compact, 'burguer100ternera2unidades' ) ) { return (int) $product->ID; }
			$score = 0;
			if ( false !== strpos( $title, 'ternera' ) ) { $score += 6; }
			if ( false !== strpos( $title, 'burger' ) || false !== strpos( $title, 'burguer' ) || false !== strpos( $title, 'hamburgues' ) ) { $score += 6; }
			if ( false !== strpos( $title, '2 unidades' ) || false !== strpos( $title, '2 unidad' ) ) { $score += 3; }
			if ( false !== strpos( $title, 'vaca' ) ) { $score -= 10; }
			if ( $score > $best_score ) { $best_score = $score; $best = (int) $product->ID; }
		}
		return $best_score >= 12 ? $best : 0;
	}

	private static function types(): array { return array( 'gift' => 'Regalo', 'coupon' => 'Cupón / descuento', 'pack' => 'Pack especial', 'custom' => 'Personalizada' ); }
	private static function sanitize_date( $value ): string {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ) { return ''; }
		$p = array_map( 'intval', explode( '-', $value ) );
		return checkdate( $p[1], $p[2], $p[0] ) ? $value : '';
	}
	private static function sanitize_ids( $value ): string {
		$value = sanitize_text_field( wp_unslash( (string) $value ) );
		$ids = array_values( array_unique( array_filter( array_map( 'absint', preg_split( '/\s*,\s*/', $value ) ) ) ) );
		return implode( ',', $ids );
	}
}
