<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class MDO_Promotions {
	private const POST_TYPE       = 'mdo_promotion';
	private const NONCE_ACTION    = 'mdo_save_promotion_meta';
	private const NONCE_NAME      = 'mdo_promotion_nonce';
	private const REWRITE_VERSION = '2';
	private const MIGRATION_KEY   = 'mdo_promotions_specials_v2';
	private const SEED_KEY        = 'tolecarnes-hamburguesas-v1';

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_post_type' ), 8 );
		add_action( 'init', array( __CLASS__, 'register_rewrites' ), 9 );
		add_filter( 'query_vars', array( __CLASS__, 'query_vars' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'filter_front_query' ) );
		add_action( 'init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 20 );
		add_action( 'init', array( __CLASS__, 'maybe_migrate_specials_v2' ), 30 );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_meta_boxes' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_meta' ), 10, 3 );
		add_filter( 'template_include', array( __CLASS__, 'template_include' ) );
		add_action( 'template_redirect', array( __CLASS__, 'redirect_legacy_urls' ), 1 );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'wp_head', array( __CLASS__, 'output_hreflang' ), 4 );
		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'admin_column' ), 10, 2 );
		add_filter( 'post_updated_messages', array( __CLASS__, 'updated_messages' ) );
	}

	public static function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels' => array(
					'name' => 'Promociones', 'singular_name' => 'Promoción', 'menu_name' => 'Promociones',
					'add_new' => 'Añadir promoción', 'add_new_item' => 'Añadir promoción', 'edit_item' => 'Editar promoción',
					'new_item' => 'Nueva promoción', 'view_item' => 'Ver promoción', 'all_items' => 'Promociones',
					'search_items' => 'Buscar promociones', 'not_found' => 'No se han encontrado promociones.',
					'featured_image' => 'Imagen alternativa de la promoción', 'set_featured_image' => 'Elegir imagen alternativa',
				),
				'public' => true,
				'publicly_queryable' => true,
				'show_ui' => true,
				'show_in_menu' => 'mdo-supplier-sync',
				'show_in_rest' => true,
				'has_archive' => false,
				'rewrite' => false,
				'supports' => array( 'thumbnail', 'page-attributes' ),
				'query_var' => true,
				'capabilities' => array(
					'edit_post' => 'manage_woocommerce', 'read_post' => 'read', 'delete_post' => 'manage_woocommerce',
					'edit_posts' => 'manage_woocommerce', 'edit_others_posts' => 'manage_woocommerce', 'publish_posts' => 'manage_woocommerce',
					'read_private_posts' => 'manage_woocommerce', 'delete_posts' => 'manage_woocommerce', 'delete_private_posts' => 'manage_woocommerce',
					'delete_published_posts' => 'manage_woocommerce', 'delete_others_posts' => 'manage_woocommerce',
					'edit_private_posts' => 'manage_woocommerce', 'edit_published_posts' => 'manage_woocommerce', 'create_posts' => 'manage_woocommerce',
				),
				'map_meta_cap' => false,
			)
		);
	}

	public static function register_rewrites(): void {
		add_rewrite_rule( '^especiales/?$', 'index.php?post_type=' . self::POST_TYPE . '&mdo_promo_archive=1', 'top' );
		add_rewrite_rule( '^especiales/([^/]+)/?$', 'index.php?post_type=' . self::POST_TYPE . '&mdo_promo_es_slug=$matches[1]&mdo_promo_lang=es', 'top' );
		add_rewrite_rule( '^en/specials/?$', 'index.php?post_type=' . self::POST_TYPE . '&mdo_promo_archive=1&mdo_promo_lang=en', 'top' );
		add_rewrite_rule( '^en/specials/([^/]+)/?$', 'index.php?post_type=' . self::POST_TYPE . '&mdo_promo_en_slug=$matches[1]&mdo_promo_lang=en', 'top' );
	}

	public static function query_vars( array $vars ): array {
		$vars[] = 'mdo_promo_archive';
		$vars[] = 'mdo_promo_lang';
		$vars[] = 'mdo_promo_es_slug';
		$vars[] = 'mdo_promo_en_slug';
		return $vars;
	}

	public static function maybe_flush_rewrite_rules(): void {
		if ( get_option( 'mdo_promotions_rewrite_version' ) === self::REWRITE_VERSION ) { return; }
		flush_rewrite_rules( false );
		update_option( 'mdo_promotions_rewrite_version', self::REWRITE_VERSION, false );
	}

	public static function filter_front_query( WP_Query $query ): void {
		if ( is_admin() || ! $query->is_main_query() ) { return; }

		$es_slug = (string) $query->get( 'mdo_promo_es_slug' );
		$en_slug = (string) $query->get( 'mdo_promo_en_slug' );
		if ( $es_slug || $en_slug ) {
			$query->set( 'post_type', self::POST_TYPE );
			$query->set( 'post_status', 'publish' );
			$query->set( 'posts_per_page', 1 );
			$query->set( 'meta_key', $en_slug ? '_mdo_promo_en_slug' : '_mdo_promo_es_slug' );
			$query->set( 'meta_value', sanitize_title( $en_slug ?: $es_slug ) );
			$query->is_singular = true;
			$query->is_single   = true;
			$query->is_archive  = false;
			return;
		}

		if ( ! $query->get( 'mdo_promo_archive' ) ) { return; }
		$today = wp_date( 'Y-m-d', null, wp_timezone() );
		$query->set( 'post_type', self::POST_TYPE );
		$query->set( 'post_status', 'publish' );
		$query->set( 'posts_per_page', 24 );
		$query->set( 'orderby', array( 'menu_order' => 'ASC', 'date' => 'DESC' ) );
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
		$query->is_archive = true;
		$query->is_post_type_archive = true;
		$query->is_home = false;
	}

	public static function add_meta_boxes(): void {
		add_meta_box( 'mdo-promotion-editor', 'Promoción bilingüe', array( __CLASS__, 'render_meta_box' ), self::POST_TYPE, 'normal', 'high' );
	}

	public static function render_meta_box( WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME );
		$m = self::meta( $post->ID );
		$suppliers = class_exists( 'MDO_Supplier_Repository' ) ? MDO_Supplier_Repository::all() : array();
		?>
		<style>
		.mdo-promo-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px 18px}.mdo-promo-field{display:flex;flex-direction:column;gap:5px}.mdo-promo-field.wide{grid-column:1/-1}.mdo-promo-field label{font-weight:600}.mdo-promo-field input,.mdo-promo-field select,.mdo-promo-field textarea{width:100%}.mdo-promo-tabs{display:flex;gap:8px;margin:22px 0 14px}.mdo-promo-tab{padding:8px 12px;border:1px solid #c3c4c7;background:#f6f7f7;cursor:pointer}.mdo-promo-tab.is-active{background:#2271b1;color:#fff;border-color:#2271b1}.mdo-promo-lang{display:none}.mdo-promo-lang.is-active{display:block}.mdo-promo-note{color:#646970;font-size:12px}.mdo-promo-shared{padding:16px;background:#f6f7f7;border:1px solid #dcdcde}.mdo-promo-shared h3{margin-top:0}@media(max-width:782px){.mdo-promo-grid{grid-template-columns:1fr}}
		</style>
		<p><strong>Estado:</strong> <?php echo esc_html( self::status_label( self::status( $post->ID ) ) ); ?>. Los datos comerciales se comparten entre idiomas; solo se traducen los textos.</p>
		<div class="mdo-promo-shared"><h3>Datos compartidos</h3><div class="mdo-promo-grid">
			<div class="mdo-promo-field"><label>Tipo</label><select name="mdo_promo_type"><?php foreach ( self::types() as $v => $l ) : ?><option value="<?php echo esc_attr( $v ); ?>" <?php selected( $m['type'], $v ); ?>><?php echo esc_html( $l ); ?></option><?php endforeach; ?></select></div>
			<div class="mdo-promo-field"><label>Productor</label><select name="mdo_promo_supplier_id"><option value="0">Sin productor concreto</option><?php foreach ( $suppliers as $s ) : ?><option value="<?php echo (int) $s['id']; ?>" <?php selected( (int) $m['supplier_id'], (int) $s['id'] ); ?>><?php echo esc_html( $s['name'] ); ?></option><?php endforeach; ?></select></div>
			<div class="mdo-promo-field"><label>Inicio</label><input type="date" name="mdo_promo_start" value="<?php echo esc_attr( $m['start'] ); ?>"></div>
			<div class="mdo-promo-field"><label>Fin</label><input type="date" name="mdo_promo_end" value="<?php echo esc_attr( $m['end'] ); ?>"><span class="mdo-promo-note">La fecha final se incluye completa.</span></div>
			<div class="mdo-promo-field"><label>Cupón</label><input type="text" name="mdo_promo_coupon" value="<?php echo esc_attr( $m['coupon'] ); ?>"></div>
			<div class="mdo-promo-field"><label>Producto que aporta la imagen</label><input type="number" min="0" name="mdo_promo_image_product_id" value="<?php echo esc_attr( (string) $m['image_product_id'] ); ?>"><span class="mdo-promo-note">Si no hay imagen destacada propia, se usa la imagen de este producto.</span></div>
			<div class="mdo-promo-field wide"><label>Productos relacionados (IDs)</label><input type="text" name="mdo_promo_product_ids" value="<?php echo esc_attr( $m['product_ids'] ); ?>"></div>
			<div class="mdo-promo-field"><label>URL del botón (opcional)</label><input type="url" name="mdo_promo_cta_url" value="<?php echo esc_attr( $m['cta_url'] ); ?>"><span class="mdo-promo-note">Vacío = tienda del productor; en inglés se adapta a /en/.</span></div>
			<div class="mdo-promo-field"><label>Orden</label><input type="number" min="0" name="mdo_promo_order" value="<?php echo esc_attr( (string) $post->menu_order ); ?>"></div>
			<div class="mdo-promo-field wide"><label><input type="checkbox" name="mdo_promo_featured_home" value="1" <?php checked( $m['featured_home'], '1' ); ?>> Destacada para la home (preparado; no modifica la home ahora)</label></div>
		</div></div>

		<div class="mdo-promo-tabs"><button type="button" class="mdo-promo-tab is-active" data-lang="es">Español</button><button type="button" class="mdo-promo-tab" data-lang="en">English</button></div>
		<?php foreach ( array( 'es' => 'Español', 'en' => 'English' ) as $lang => $label ) : ?>
		<div class="mdo-promo-lang <?php echo 'es' === $lang ? 'is-active' : ''; ?>" data-lang-panel="<?php echo esc_attr( $lang ); ?>"><div class="mdo-promo-grid">
			<div class="mdo-promo-field wide"><label>Título</label><input type="text" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_title" value="<?php echo esc_attr( $m[ $lang . '_title' ] ); ?>"></div>
			<div class="mdo-promo-field"><label>Slug</label><input type="text" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_slug" value="<?php echo esc_attr( $m[ $lang . '_slug' ] ); ?>"></div>
			<div class="mdo-promo-field"><label>Antetítulo</label><input type="text" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_eyebrow" value="<?php echo esc_attr( $m[ $lang . '_eyebrow' ] ); ?>"></div>
			<div class="mdo-promo-field wide"><label>Resumen</label><textarea rows="3" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_summary"><?php echo esc_textarea( $m[ $lang . '_summary' ] ); ?></textarea></div>
			<div class="mdo-promo-field wide"><label>Cómo funciona / beneficio</label><textarea rows="4" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_benefit"><?php echo esc_textarea( $m[ $lang . '_benefit' ] ); ?></textarea></div>
			<div class="mdo-promo-field wide"><label>Contenido adicional</label><textarea rows="5" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_content"><?php echo esc_textarea( $m[ $lang . '_content' ] ); ?></textarea></div>
			<div class="mdo-promo-field"><label>Texto del botón</label><input type="text" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_cta_label" value="<?php echo esc_attr( $m[ $lang . '_cta_label' ] ); ?>"></div>
			<div class="mdo-promo-field wide"><label>Condiciones</label><textarea rows="4" name="mdo_promo_<?php echo esc_attr( $lang ); ?>_conditions"><?php echo esc_textarea( $m[ $lang . '_conditions' ] ); ?></textarea></div>
		</div></div>
		<?php endforeach; ?>
		<script>(function(){document.querySelectorAll('.mdo-promo-tab').forEach(function(b){b.addEventListener('click',function(){document.querySelectorAll('.mdo-promo-tab').forEach(function(x){x.classList.toggle('is-active',x===b);});document.querySelectorAll('.mdo-promo-lang').forEach(function(p){p.classList.toggle('is-active',p.getAttribute('data-lang-panel')===b.getAttribute('data-lang'));});});});})();</script>
		<?php
	}

	public static function save_meta( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) { return; }
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) { return; }
		if ( ! current_user_can( 'manage_woocommerce' ) ) { return; }

		$type = sanitize_key( wp_unslash( $_POST['mdo_promo_type'] ?? 'custom' ) );
		if ( ! isset( self::types()[ $type ] ) ) { $type = 'custom'; }
		$shared = array(
			'type' => $type,
			'supplier_id' => absint( $_POST['mdo_promo_supplier_id'] ?? 0 ),
			'start' => self::sanitize_date( $_POST['mdo_promo_start'] ?? '' ),
			'end' => self::sanitize_date( $_POST['mdo_promo_end'] ?? '' ),
			'coupon' => sanitize_text_field( wp_unslash( $_POST['mdo_promo_coupon'] ?? '' ) ),
			'image_product_id' => absint( $_POST['mdo_promo_image_product_id'] ?? 0 ),
			'product_ids' => self::sanitize_product_ids( $_POST['mdo_promo_product_ids'] ?? '' ),
			'cta_url' => esc_url_raw( wp_unslash( $_POST['mdo_promo_cta_url'] ?? '' ) ),
			'featured_home' => isset( $_POST['mdo_promo_featured_home'] ) ? '1' : '0',
		);
		foreach ( $shared as $k => $v ) { update_post_meta( $post_id, '_mdo_promo_' . $k, $v ); }

		foreach ( array( 'es', 'en' ) as $lang ) {
			$fields = array(
				'title' => sanitize_text_field( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_title' ] ?? '' ) ),
				'slug' => sanitize_title( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_slug' ] ?? '' ) ),
				'eyebrow' => sanitize_text_field( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_eyebrow' ] ?? '' ) ),
				'summary' => sanitize_textarea_field( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_summary' ] ?? '' ) ),
				'benefit' => sanitize_textarea_field( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_benefit' ] ?? '' ) ),
				'content' => wp_kses_post( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_content' ] ?? '' ) ),
				'cta_label' => sanitize_text_field( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_cta_label' ] ?? '' ) ),
				'conditions' => sanitize_textarea_field( wp_unslash( $_POST[ 'mdo_promo_' . $lang . '_conditions' ] ?? '' ) ),
			);
			foreach ( $fields as $k => $v ) { update_post_meta( $post_id, '_mdo_promo_' . $lang . '_' . $k, $v ); }
		}

		$es_title = sanitize_text_field( wp_unslash( $_POST['mdo_promo_es_title'] ?? '' ) );
		$order    = max( 0, absint( $_POST['mdo_promo_order'] ?? 0 ) );
		global $wpdb;
		$wpdb->update( $wpdb->posts, array( 'post_title' => $es_title ?: $post->post_title, 'menu_order' => $order ), array( 'ID' => $post_id ), array( '%s', '%d' ), array( '%d' ) );
		clean_post_cache( $post_id );
	}

	public static function meta( int $post_id ): array {
		$keys = array( 'type','supplier_id','start','end','coupon','image_product_id','product_ids','cta_url','featured_home' );
		foreach ( array( 'es','en' ) as $lang ) { foreach ( array( 'title','slug','eyebrow','summary','benefit','content','cta_label','conditions' ) as $f ) { $keys[] = $lang . '_' . $f; } }
		$out = array();
		foreach ( $keys as $k ) { $out[ $k ] = get_post_meta( $post_id, '_mdo_promo_' . $k, true ); }
		$out['type'] = $out['type'] ?: 'custom';
		$out['supplier_id'] = (int) $out['supplier_id'];
		$out['image_product_id'] = (int) $out['image_product_id'];
		$out['featured_home'] = $out['featured_home'] ?: '0';
		$out['es_title'] = $out['es_title'] ?: get_the_title( $post_id );
		$out['es_slug'] = $out['es_slug'] ?: get_post_field( 'post_name', $post_id );
		return $out;
	}

	public static function localized( int $post_id, ?string $lang = null ): array {
		$lang = $lang ?: self::language();
		$m = self::meta( $post_id );
		$prefix = 'en' === $lang ? 'en_' : 'es_';
		$fallback = 'en' === $lang ? 'es_' : 'en_';
		$out = $m;
		foreach ( array( 'title','slug','eyebrow','summary','benefit','content','cta_label','conditions' ) as $f ) {
			$out[ $f ] = $m[ $prefix . $f ] ?: $m[ $fallback . $f ];
		}
		return $out;
	}

	public static function language(): string {
		if ( 'en' === get_query_var( 'mdo_promo_lang' ) ) { return 'en'; }
		$uri = (string) ( $_SERVER['REQUEST_URI'] ?? '' );
		return str_starts_with( $uri, '/en/' ) ? 'en' : 'es';
	}

	public static function archive_url( string $lang = 'es' ): string { return home_url( 'en' === $lang ? '/en/specials/' : '/especiales/' ); }

	public static function permalink( int $post_id, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$m = self::meta( $post_id );
		$slug = 'en' === $lang ? ( $m['en_slug'] ?: $m['es_slug'] ) : $m['es_slug'];
		return home_url( ( 'en' === $lang ? '/en/specials/' : '/especiales/' ) . trailingslashit( $slug ) );
	}

	public static function status( int $post_id ): string {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) { return 'draft'; }
		$m = self::meta( $post_id );
		$today = wp_date( 'Y-m-d', null, wp_timezone() );
		if ( $m['start'] && $m['start'] > $today ) { return 'scheduled'; }
		if ( $m['end'] && $m['end'] < $today ) { return 'expired'; }
		return 'active';
	}

	public static function supplier( int $post_id ): ?array {
		$m = self::meta( $post_id );
		if ( ! $m['supplier_id'] || ! class_exists( 'MDO_Supplier_Repository' ) ) { return null; }
		$s = MDO_Supplier_Repository::find( (int) $m['supplier_id'] );
		return is_array( $s ) ? $s : null;
	}

	public static function cta_url( int $post_id, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$m = self::meta( $post_id );
		$url = (string) $m['cta_url'];
		if ( ! $url ) {
			$s = self::supplier( $post_id );
			if ( $s && ! empty( $s['vendor_user_id'] ) && function_exists( 'wcfmmp_get_store_url' ) ) { $url = (string) wcfmmp_get_store_url( (int) $s['vendor_user_id'] ); }
		}
		if ( 'en' === $lang && $url && str_starts_with( $url, home_url( '/' ) ) ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( $path && ! str_starts_with( $path, '/en/' ) ) { $url = home_url( '/en' . $path ); }
		}
		return $url;
	}

	public static function product_ids( int $post_id ): array {
		$m = self::meta( $post_id );
		return $m['product_ids'] ? array_values( array_filter( array_map( 'absint', explode( ',', $m['product_ids'] ) ) ) ) : array();
	}

	public static function image_html( int $post_id, string $size = 'large', array $attr = array() ): string {
		if ( has_post_thumbnail( $post_id ) ) { return get_the_post_thumbnail( $post_id, $size, $attr ); }
		$m = self::meta( $post_id );
		$product_id = (int) $m['image_product_id'];
		if ( ! $product_id ) { $ids = self::product_ids( $post_id ); $product_id = $ids[0] ?? 0; }
		if ( $product_id && function_exists( 'wc_get_product' ) ) {
			$p = wc_get_product( $product_id );
			if ( $p && $p->get_image_id() ) { return wp_get_attachment_image( $p->get_image_id(), $size, false, $attr ); }
		}
		return '';
	}

	public static function format_date( string $date, ?string $lang = null ): string {
		if ( ! $date ) { return ''; }
		$lang = $lang ?: self::language();
		$ts = strtotime( $date . ' 12:00:00' );
		if ( ! $ts ) { return $date; }
		if ( 'en' === $lang ) { return wp_date( 'F j, Y', $ts, wp_timezone() ); }
		$months = array( 1=>'enero',2=>'febrero',3=>'marzo',4=>'abril',5=>'mayo',6=>'junio',7=>'julio',8=>'agosto',9=>'septiembre',10=>'octubre',11=>'noviembre',12=>'diciembre' );
		return wp_date( 'j', $ts, wp_timezone() ) . ' de ' . $months[ (int) wp_date( 'n', $ts, wp_timezone() ) ] . ' de ' . wp_date( 'Y', $ts, wp_timezone() );
	}

	public static function text( string $key, ?string $lang = null ): string {
		$lang = $lang ?: self::language();
		$t = array(
			'es' => array(
				'kicker'=>'Ahora en El Mercado','archive_title'=>'Especiales del Mercado','archive_intro'=>'Regalos, packs, ventajas y propuestas especiales que preparamos junto a nuestros productores. Aquí encontrarás las que están disponibles ahora.','view'=>'Ver especial','empty_title'=>'No hay especiales activos ahora mismo','empty_text'=>'Cuando tengamos una propuesta especial disponible, aparecerá aquí.','shop'=>'Ver la tienda','until'=>'Disponible hasta el','with'=>'Con','expired'=>'Esta promoción ha finalizado.','expired_more'=>'Puedes consultar los especiales que están disponibles actualmente.','current'=>'Ver especiales actuales','scheduled'=>'Esta promoción estará disponible a partir del','how'=>'Cómo funciona','related'=>'Productos relacionados','conditions'=>'Condiciones','code'=>'Código de la promoción','copy'=>'Copiar código','copied'=>'Copiado','back'=>'← Todos los especiales','buy'=>'Comprar ahora','prev'=>'Anterior','next'=>'Siguiente'
			),
			'en' => array(
				'kicker'=>'Now at El Mercado','archive_title'=>'Market Specials','archive_intro'=>'Gifts, bundles, benefits and special proposals we prepare together with our producers. Here you will find the ones available now.','view'=>'View special','empty_title'=>'There are no active specials right now','empty_text'=>'When a new special proposal is available, it will appear here.','shop'=>'Visit the shop','until'=>'Available until','with'=>'With','expired'=>'This promotion has ended.','expired_more'=>'You can browse the specials that are currently available.','current'=>'View current specials','scheduled'=>'This promotion will be available from','how'=>'How it works','related'=>'Related products','conditions'=>'Terms','code'=>'Promotion code','copy'=>'Copy code','copied'=>'Copied','back'=>'← All specials','buy'=>'Shop now','prev'=>'Previous','next'=>'Next'
			),
		);
		return $t[ $lang ][ $key ] ?? $key;
	}

	public static function template_include( string $template ): string {
		if ( get_query_var( 'mdo_promo_archive' ) ) {
			$c = MDO_SUPPLIER_SYNC_PATH . 'templates/archive-mdo-promotion.php';
			return file_exists( $c ) ? $c : $template;
		}
		if ( is_singular( self::POST_TYPE ) || get_query_var( 'mdo_promo_es_slug' ) || get_query_var( 'mdo_promo_en_slug' ) ) {
			$c = MDO_SUPPLIER_SYNC_PATH . 'templates/single-mdo-promotion.php';
			return file_exists( $c ) ? $c : $template;
		}
		return $template;
	}

	public static function enqueue_assets(): void {
		if ( ! get_query_var( 'mdo_promo_archive' ) && ! is_singular( self::POST_TYPE ) && ! get_query_var( 'mdo_promo_es_slug' ) && ! get_query_var( 'mdo_promo_en_slug' ) ) { return; }
		wp_enqueue_style( 'mdo-promotions', MDO_SUPPLIER_SYNC_URL . 'assets/css/promotions.css', array(), MDO_SUPPLIER_SYNC_VERSION );
	}

	public static function redirect_legacy_urls(): void {
		$path = wp_parse_url( (string) ( $_SERVER['REQUEST_URI'] ?? '' ), PHP_URL_PATH );
		if ( '/ofertas/' === $path || '/ofertas' === $path ) { wp_safe_redirect( self::archive_url( 'es' ), 301 ); exit; }
		if ( in_array( $path, array( '/ofertas/hamburguesas-regalo-tole-carnes/', '/ofertas/hamburguesas-regalo-tole-carnes' ), true ) ) {
			$ids = get_posts( array( 'post_type'=>self::POST_TYPE, 'post_status'=>'publish', 'posts_per_page'=>1, 'meta_key'=>'_mdo_promo_seed_key', 'meta_value'=>self::SEED_KEY, 'fields'=>'ids' ) );
			wp_safe_redirect( $ids ? self::permalink( (int) $ids[0], 'es' ) : self::archive_url( 'es' ), 301 ); exit;
		}
	}

	public static function output_hreflang(): void {
		if ( get_query_var( 'mdo_promo_archive' ) ) {
			echo '<link rel="alternate" hreflang="es" href="' . esc_url( self::archive_url( 'es' ) ) . '">' . "\n";
			echo '<link rel="alternate" hreflang="en" href="' . esc_url( self::archive_url( 'en' ) ) . '">' . "\n";
		} elseif ( is_singular( self::POST_TYPE ) || get_query_var( 'mdo_promo_es_slug' ) || get_query_var( 'mdo_promo_en_slug' ) ) {
			$id = get_queried_object_id(); if ( ! $id && have_posts() ) { the_post(); $id = get_the_ID(); rewind_posts(); }
			if ( $id ) {
				echo '<link rel="alternate" hreflang="es" href="' . esc_url( self::permalink( $id, 'es' ) ) . '">' . "\n";
				echo '<link rel="alternate" hreflang="en" href="' . esc_url( self::permalink( $id, 'en' ) ) . '">' . "\n";
			}
		}
	}

	public static function admin_columns( array $columns ): array {
		$columns['mdo_promo_window'] = 'Vigencia'; $columns['mdo_promo_status'] = 'Estado'; return $columns;
	}
	public static function admin_column( string $column, int $post_id ): void {
		$m = self::meta( $post_id );
		if ( 'mdo_promo_window' === $column ) { echo esc_html( ( $m['start'] ?: 'Ahora' ) . ' → ' . ( $m['end'] ?: 'Sin fin' ) ); }
		if ( 'mdo_promo_status' === $column ) { echo esc_html( self::status_label( self::status( $post_id ) ) ); }
	}
	public static function updated_messages( array $messages ): array { $messages[ self::POST_TYPE ] = array( 0=>'',1=>'Promoción actualizada.',6=>'Promoción publicada.',7=>'Promoción guardada.',10=>'Borrador de promoción actualizado.' ); return $messages; }
	public static function types(): array { return array( 'gift'=>'Regalo','coupon'=>'Cupón / descuento','pack'=>'Pack especial','custom'=>'Personalizada' ); }
	private static function status_label( string $status ): string { return array( 'active'=>'Activa','expired'=>'Finalizada','scheduled'=>'Programada','draft'=>'Borrador' )[ $status ] ?? ucfirst( $status ); }
	private static function sanitize_date( $value ): string { $v=sanitize_text_field( wp_unslash( (string) $value ) ); if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/',$v) ) return ''; $p=array_map('intval',explode('-',$v)); return checkdate($p[1],$p[2],$p[0])?$v:''; }
	private static function sanitize_product_ids( $value ): string { $ids=array_values(array_unique(array_filter(array_map('absint',preg_split('/\s*,\s*/',sanitize_text_field(wp_unslash((string)$value))))))); return implode(',',$ids); }

	public static function maybe_migrate_specials_v2(): void {
		if ( get_option( self::MIGRATION_KEY ) ) { return; }
		$ids = get_posts( array( 'post_type'=>self::POST_TYPE, 'post_status'=>'any', 'posts_per_page'=>1, 'meta_key'=>'_mdo_promo_seed_key', 'meta_value'=>self::SEED_KEY, 'fields'=>'ids' ) );
		if ( ! $ids ) { $ids = get_posts( array( 'post_type'=>self::POST_TYPE, 'post_status'=>'any', 'posts_per_page'=>1, 'name'=>'hamburguesas-regalo-tole-carnes', 'fields'=>'ids' ) ); }
		if ( ! $ids ) {
			$post_id = wp_insert_post( array( 'post_type'=>self::POST_TYPE, 'post_status'=>'publish', 'post_title'=>'Dos hamburguesas de ternera de regalo', 'post_name'=>'hamburguesas-regalo-tole-carnes', 'menu_order'=>0 ), true );
			if ( is_wp_error( $post_id ) ) { return; }
		} else { $post_id = (int) $ids[0]; }

		$supplier_id = 0; $vendor_user_id = 0;
		if ( class_exists( 'MDO_Supplier_Repository' ) ) {
			foreach ( MDO_Supplier_Repository::all() as $s ) {
				$n = remove_accents( strtolower( (string) ( $s['name'] ?? '' ) ) );
				if ( false !== strpos( $n, 'tolecarnes' ) || ( false !== strpos( $n, 'tole' ) && false !== strpos( $n, 'carne' ) ) ) { $supplier_id=(int)$s['id']; $vendor_user_id=(int)($s['vendor_user_id']??0); break; }
			}
		}

		$product_id = self::find_tolecarnes_veal_burger_product( $vendor_user_id );
		wp_update_post( array( 'ID'=>$post_id, 'post_title'=>'Dos hamburguesas de ternera de regalo', 'post_name'=>'hamburguesas-regalo-tole-carnes', 'post_status'=>'publish' ) );
		$data = array(
			'_mdo_promo_seed_key'=>self::SEED_KEY, '_mdo_promo_type'=>'gift', '_mdo_promo_supplier_id'=>$supplier_id,
			'_mdo_promo_start'=>'2026-08-26', '_mdo_promo_end'=>'2026-08-31', '_mdo_promo_coupon'=>'', '_mdo_promo_cta_url'=>'',
			'_mdo_promo_image_product_id'=>$product_id, '_mdo_promo_product_ids'=>$product_id ? (string)$product_id : '', '_mdo_promo_featured_home'=>'1',
			'_mdo_promo_es_title'=>'Dos hamburguesas de ternera de regalo', '_mdo_promo_es_slug'=>'hamburguesas-ternera-regalo-tolecarnes',
			'_mdo_promo_es_eyebrow'=>'Regalo con tu pedido', '_mdo_promo_es_summary'=>'Haz tu pedido a Tolecarnes y recibe dos hamburguesas de ternera de regalo.',
			'_mdo_promo_es_benefit'=>'No tienes que introducir ningún código ni hacer nada especial. Realiza tu pedido a Tolecarnes y recibirás dos hamburguesas de ternera de regalo junto a tu compra.',
			'_mdo_promo_es_content'=>'Una ventaja especial para disfrutar de la carne de Tolecarnes: dos hamburguesas de ternera incluidas como regalo con tu pedido.',
			'_mdo_promo_es_cta_label'=>'Ver productos de Tolecarnes', '_mdo_promo_es_conditions'=>'Promoción válida para pedidos realizados a Tolecarnes hasta el 31 de agosto de 2026 incluido. No es necesario introducir ningún código.',
			'_mdo_promo_en_title'=>'Two free beef burgers', '_mdo_promo_en_slug'=>'free-beef-burgers-tolecarnes', '_mdo_promo_en_eyebrow'=>'A gift with your order',
			'_mdo_promo_en_summary'=>'Place an order with Tolecarnes and receive two beef burgers as a gift.',
			'_mdo_promo_en_benefit'=>'No code or special action is required. Place your order with Tolecarnes and you will receive two beef burgers as a gift with your purchase.',
			'_mdo_promo_en_content'=>'A special extra to enjoy Tolecarnes beef: two beef burgers included as a gift with your order.',
			'_mdo_promo_en_cta_label'=>'Shop Tolecarnes', '_mdo_promo_en_conditions'=>'Valid for orders placed with Tolecarnes through August 31, 2026 inclusive. No promotion code is required.',
		);
		foreach ( $data as $k=>$v ) { update_post_meta( $post_id, $k, $v ); }
		update_option( self::MIGRATION_KEY, 1, false );
	}

	private static function find_tolecarnes_veal_burger_product( int $vendor_user_id ): int {
		global $wpdb;
		$author_sql = $vendor_user_id ? $wpdb->prepare( ' AND post_author = %d ', $vendor_user_id ) : '';
		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_type='product' AND post_status IN ('publish','private','draft') {$author_sql} AND (LOWER(post_title) LIKE '%ternera%') AND (LOWER(post_title) LIKE '%burger%' OR LOWER(post_title) LIKE '%hamburg%') ORDER BY CASE WHEN LOWER(post_title) LIKE '%100% ternera%' THEN 0 ELSE 1 END, ID DESC LIMIT 1";
		return (int) $wpdb->get_var( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}
}
