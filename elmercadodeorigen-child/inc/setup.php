<?php
/**
 * Configuración general, recursos y experiencia de portada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Devuelve una versión basada en la fecha del archivo durante desarrollo.
 */
function elmercado_asset_version( string $relative_path ): string {
	$absolute_path = ELMERCADO_THEME_PATH . $relative_path;

	return file_exists( $absolute_path )
		? (string) filemtime( $absolute_path )
		: ELMERCADO_THEME_VERSION;
}

/**
 * Localiza una página por una lista de slugs y devuelve una alternativa segura.
 *
 * @param string[] $slugs Slugs candidatos.
 */
function elmercado_page_url( array $slugs, string $fallback ): string {
	foreach ( $slugs as $slug ) {
		$page = get_page_by_path( $slug );

		if ( $page instanceof WP_Post ) {
			return get_permalink( $page );
		}
	}

	return home_url( $fallback );
}

add_action(
	'after_setup_theme',
	function (): void {
		load_child_theme_textdomain( 'elmercadodeorigen', ELMERCADO_THEME_PATH . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'editor-styles' );
		add_editor_style( 'assets/css/theme.css' );
	}
);

add_action(
	'wp_enqueue_scripts',
	function (): void {
		$parent = wp_get_theme( 'woostify' );

		wp_enqueue_style(
			'woostify-parent',
			get_template_directory_uri() . '/style.css',
			array(),
			$parent->exists() ? $parent->get( 'Version' ) : null
		);

		wp_enqueue_style(
			'elmercado-theme',
			ELMERCADO_THEME_URL . '/assets/css/theme.css',
			array( 'woostify-parent' ),
			elmercado_asset_version( '/assets/css/theme.css' )
		);

		wp_enqueue_style(
			'elmercado-integrations',
			ELMERCADO_THEME_URL . '/assets/css/integrations.css',
			array( 'elmercado-theme' ),
			elmercado_asset_version( '/assets/css/integrations.css' )
		);

		wp_enqueue_script(
			'elmercado-theme',
			ELMERCADO_THEME_URL . '/assets/js/theme.js',
			array(),
			elmercado_asset_version( '/assets/js/theme.js' ),
			true
		);
	},
	20
);

add_filter(
	'body_class',
	function ( array $classes ): array {
		$classes[] = 'elmercado-child-theme';

		if ( class_exists( 'WooCommerce' ) ) {
			$classes[] = 'elmercado-has-woocommerce';
		}

		if ( is_front_page() ) {
			$classes[] = 'elmercado-premium-home';
		}

		return $classes;
	}
);

/**
 * Barra de confianza por encima de la cabecera.
 */
add_action(
	'wp_body_open',
	function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<div class="emo-announcement" role="region" aria-label="Ventajas de El Mercado de Origen">
			<div class="emo-shell emo-announcement__inner">
				<span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 7 9 18l-5-5"/></svg><?php esc_html_e( 'Selección cuidada de productores', 'elmercadodeorigen' ); ?></span>
				<span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 4 7v5c0 5 3.4 8.7 8 10 4.6-1.3 8-5 8-10V7l-8-4Z"/><path d="m9 12 2 2 4-4"/></svg><?php esc_html_e( 'Compra segura y transparente', 'elmercadodeorigen' ); ?></span>
				<span><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h13v11H3z"/><path d="M16 10h3l2 3v4h-5z"/><circle cx="7" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg><?php esc_html_e( 'Envíos preparados con cuidado', 'elmercadodeorigen' ); ?></span>
			</div>
		</div>
		<?php
	},
	5
);

/**
 * Devuelve los productos con más unidades vendidas, respetando la visibilidad
 * del catálogo y la configuración de productos sin existencias.
 *
 * @return int[]
 */
function elmercado_best_selling_product_ids( int $limit = 6 ): array {
	if ( ! post_type_exists( 'product' ) ) {
		return array();
	}

	$tax_query = array();

	if ( function_exists( 'wc_get_product_visibility_term_ids' ) ) {
		$visibility = wc_get_product_visibility_term_ids();
		$excluded   = array_filter(
			array(
				$visibility['exclude-from-catalog'] ?? 0,
				'yes' === get_option( 'woocommerce_hide_out_of_stock_items' ) ? ( $visibility['outofstock'] ?? 0 ) : 0,
			)
		);

		if ( ! empty( $excluded ) ) {
			$tax_query[] = array(
				'taxonomy' => 'product_visibility',
				'field'    => 'term_taxonomy_id',
				'terms'    => array_map( 'absint', $excluded ),
				'operator' => 'NOT IN',
			);
		}
	}

	$args = array(
		'post_type'           => 'product',
		'post_status'         => 'publish',
		'posts_per_page'      => max( 1, $limit ),
		'fields'              => 'ids',
		'no_found_rows'       => true,
		'ignore_sticky_posts' => true,
		'meta_key'            => 'total_sales',
		'orderby'             => array(
			'meta_value_num' => 'DESC',
			'date'           => 'DESC',
		),
	);

	if ( ! empty( $tax_query ) ) {
		$args['tax_query'] = $tax_query;
	}

	$query = new WP_Query( $args );
	$ids   = array_values( array_filter( array_map( 'absint', $query->posts ) ) );

	if ( ! empty( $ids ) ) {
		return $ids;
	}

	return get_posts(
		array(
			'post_type'      => 'product',
			'post_status'    => 'publish',
			'posts_per_page' => max( 1, $limit ),
			'fields'         => 'ids',
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
}

/**
 * Obtiene los superventas para la composición visual de portada.
 *
 * @return WC_Product[]
 */
function elmercado_home_visual_products(): array {
	if ( ! function_exists( 'wc_get_product' ) ) {
		return array();
	}

	$products = array_map( 'wc_get_product', elmercado_best_selling_product_ids( 4 ) );

	return array_values(
		array_filter(
			$products,
			static function ( $product ): bool {
				return $product instanceof WC_Product;
			}
		)
	);
}

/**
 * Renderiza la composición de imágenes del hero usando productos reales.
 */
function elmercado_render_home_visual(): string {
	$products = elmercado_home_visual_products();

	if ( empty( $products ) ) {
		return '<div class="emo-hero__placeholder" aria-hidden="true"><span></span><span></span><span></span></div>';
	}

	$html = '<div class="emo-hero__visual" aria-label="Productos favoritos de nuestros clientes">';

	foreach ( $products as $index => $product ) {
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$image_id = $product->get_image_id();
		$image    = $image_id
			? wp_get_attachment_image(
				$image_id,
				'woocommerce_single',
				false,
				array(
					'alt'      => $product->get_name(),
					'loading'  => 0 === $index ? 'eager' : 'lazy',
					'decoding' => 'async',
				)
			)
			: wc_placeholder_img( 'woocommerce_single' );

		$html .= sprintf(
			'<a class="emo-hero-card emo-hero-card--%1$d" href="%2$s"><figure>%3$s<figcaption><span>%4$s</span><strong>%5$s</strong></figcaption></figure></a>',
			(int) $index + 1,
			esc_url( $product->get_permalink() ),
			$image,
			esc_html__( 'Favorito del mercado', 'elmercadodeorigen' ),
			esc_html( $product->get_name() )
		);
	}

	$html .= '</div>';

	return $html;
}

/**
 * Renderiza categorías destacadas con imágenes y datos reales.
 */
function elmercado_render_home_categories(): string {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}

	$exclude = array_filter( array( (int) get_option( 'default_product_cat' ) ) );
	$terms   = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'number'     => 6,
			'orderby'    => 'count',
			'order'      => 'DESC',
			'exclude'    => $exclude,
		)
	);

	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		return '';
	}

	$html = '<section class="emo-section emo-categories"><div class="emo-shell">';
	$html .= '<div class="emo-section-heading"><div><span class="emo-kicker">' . esc_html__( 'Explora por categoría', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Encuentra tu próximo descubrimiento', 'elmercadodeorigen' ) . '</h2></div><p>' . esc_html__( 'Una despensa diversa, seleccionada para comprar mejor y conocer quién hay detrás de cada producto.', 'elmercadodeorigen' ) . '</p></div>';
	$html .= '<div class="emo-category-grid">';

	foreach ( $terms as $term ) {
		$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$image         = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
		$link          = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$style = $image ? ' style="--emo-category-image:url(' . esc_url( $image ) . ')"' : '';
		$html .= '<a class="emo-category-card" href="' . esc_url( $link ) . '"' . $style . '>';
		$html .= '<span class="emo-category-card__media" aria-hidden="true"></span>';
		$html .= '<span class="emo-category-card__content"><strong>' . esc_html( $term->name ) . '</strong><small>' . sprintf( esc_html( _n( '%s producto', '%s productos', (int) $term->count, 'elmercadodeorigen' ) ), number_format_i18n( (int) $term->count ) ) . '</small></span>';
		$html .= '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
		$html .= '</a>';
	}

	$html .= '</div></div></section>';

	return $html;
}

/**
 * Renderiza los productos con más ventas acumuladas en WooCommerce.
 */
function elmercado_render_home_products(): string {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return '';
	}

	$product_ids = elmercado_best_selling_product_ids( 6 );

	if ( empty( $product_ids ) ) {
		return '';
	}

	$shortcode = sprintf(
		'[products ids="%s" limit="6" columns="3" orderby="post__in"]',
		esc_attr( implode( ',', array_map( 'absint', $product_ids ) ) )
	);
	$shop_url = wc_get_page_permalink( 'shop' );

	return '<section class="emo-section emo-featured-products"><div class="emo-shell">'
		. '<div class="emo-section-heading"><div><span class="emo-kicker">' . esc_html__( 'Los más elegidos', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Los favoritos de nuestros clientes', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'Una selección viva que se ordena automáticamente según las ventas reales del mercado.', 'elmercadodeorigen' ) . '</p></div><a class="emo-text-link" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Ver todo el mercado', 'elmercadodeorigen' ) . '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>'
		. do_shortcode( $shortcode )
		. '</div></section>';
}

/**
 * Sustituye únicamente la portada por una experiencia versionada en el tema.
 */
add_filter(
	'the_content',
	function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		static $rendering = false;

		if ( $rendering ) {
			return $content;
		}

		$rendering  = true;
		$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
		$vendor_url = elmercado_page_url( array( 'store-listing', 'tiendas', 'productores' ), '/store-listing/' );
		$about_url  = elmercado_page_url( array( 'nosotros', 'quienes-somos', 'sobre-nosotros' ), '/nosotros/' );

		$hero = '<section class="emo-hero"><div class="emo-shell emo-hero__grid"><div class="emo-hero__copy">'
			. '<span class="emo-kicker emo-kicker--light">' . esc_html__( 'El mercado de los buenos orígenes', 'elmercadodeorigen' ) . '</span>'
			. '<h1>' . esc_html__( 'Sabores con origen.', 'elmercadodeorigen' ) . '<em>' . esc_html__( 'Productos con historia.', 'elmercadodeorigen' ) . '</em></h1>'
			. '<p>' . esc_html__( 'Descubre alimentos y productos elegidos por su calidad, elaborados por personas que cuidan cada detalle desde el origen.', 'elmercadodeorigen' ) . '</p>'
			. '<div class="emo-hero__actions"><a class="emo-button emo-button--accent" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Explorar el mercado', 'elmercadodeorigen' ) . '</a><a class="emo-button emo-button--ghost" href="' . esc_url( $vendor_url ) . '">' . esc_html__( 'Conocer a los productores', 'elmercadodeorigen' ) . '</a></div>'
			. '<div class="emo-hero__proof"><span><strong>' . esc_html__( 'Origen', 'elmercadodeorigen' ) . '</strong>' . esc_html__( 'Productos con identidad', 'elmercadodeorigen' ) . '</span><span><strong>' . esc_html__( 'Criterio', 'elmercadodeorigen' ) . '</strong>' . esc_html__( 'Selección cuidada', 'elmercadodeorigen' ) . '</span><span><strong>' . esc_html__( 'Cercanía', 'elmercadodeorigen' ) . '</strong>' . esc_html__( 'Compra más humana', 'elmercadodeorigen' ) . '</span></div>'
			. '</div>' . elmercado_render_home_visual() . '</div></section>';

		$trust = '<section class="emo-trust"><div class="emo-shell emo-trust__grid">'
			. '<article><span>01</span><div><strong>' . esc_html__( 'Productos seleccionados', 'elmercadodeorigen' ) . '</strong><p>' . esc_html__( 'Una oferta pensada para descubrir calidad, no para perderse entre miles de referencias.', 'elmercadodeorigen' ) . '</p></div></article>'
			. '<article><span>02</span><div><strong>' . esc_html__( 'Personas detrás del producto', 'elmercadodeorigen' ) . '</strong><p>' . esc_html__( 'Conoce a productores, artesanos y proyectos que trabajan con propósito.', 'elmercadodeorigen' ) . '</p></div></article>'
			. '<article><span>03</span><div><strong>' . esc_html__( 'Compra sencilla y segura', 'elmercadodeorigen' ) . '</strong><p>' . esc_html__( 'Una experiencia clara desde la elección del producto hasta la entrega.', 'elmercadodeorigen' ) . '</p></div></article>'
			. '</div></section>';

		$story = '<section class="emo-section emo-story"><div class="emo-shell emo-story__grid"><div class="emo-story__panel">'
			. '<span class="emo-kicker emo-kicker--light">' . esc_html__( 'Mucho más que una tienda', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Comprar bien empieza por saber de dónde viene.', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'El Mercado de Origen conecta productos memorables con personas que valoran la procedencia, el cuidado y el trabajo bien hecho.', 'elmercadodeorigen' ) . '</p><a class="emo-text-link emo-text-link--light" href="' . esc_url( $about_url ) . '">' . esc_html__( 'Conoce nuestro proyecto', 'elmercadodeorigen' ) . '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>'
			. '<div class="emo-story__values"><article><span>01</span><h3>' . esc_html__( 'Calidad con contexto', 'elmercadodeorigen' ) . '</h3><p>' . esc_html__( 'No solo importa qué compras, sino cómo se ha elaborado y quién lo hace posible.', 'elmercadodeorigen' ) . '</p></article><article><span>02</span><h3>' . esc_html__( 'Una despensa con personalidad', 'elmercadodeorigen' ) . '</h3><p>' . esc_html__( 'Productos diferentes para regalar, compartir o convertir lo cotidiano en algo especial.', 'elmercadodeorigen' ) . '</p></article><article><span>03</span><h3>' . esc_html__( 'Relaciones más cercanas', 'elmercadodeorigen' ) . '</h3><p>' . esc_html__( 'Un mercado digital que mantiene visible el valor humano de cada proyecto.', 'elmercadodeorigen' ) . '</p></article></div></div></section>';

		$vendor_cta = '<section class="emo-section emo-vendor-cta"><div class="emo-shell"><div class="emo-vendor-cta__inner"><div><span class="emo-kicker">' . esc_html__( 'Productores y artesanos', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Descubre quién hace posible cada sabor.', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'Entra en sus tiendas, conoce sus proyectos y compra directamente dentro de un mercado creado para darles protagonismo.', 'elmercadodeorigen' ) . '</p></div><a class="emo-button emo-button--dark" href="' . esc_url( $vendor_url ) . '">' . esc_html__( 'Ver productores', 'elmercadodeorigen' ) . '</a></div></div></section>';

		$rendering = false;

		return '<div class="emo-home">' . $hero . $trust . elmercado_render_home_categories() . elmercado_render_home_products() . $story . $vendor_cta . '</div>';
	},
	30
);
