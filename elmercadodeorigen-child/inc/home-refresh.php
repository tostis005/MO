<?php
/**
 * Revisión editorial y de composición de la portada.
 *
 * Mantiene las consultas y componentes estables del tema, pero sustituye el
 * discurso genérico por mensajes concretos sobre el catálogo, la procedencia y
 * las personas que elaboran cada producto. También normaliza la cabecera de la
 * home y elimina por completo el popup de suscripción en esta página.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Composición visual del hero con productos reales y una etiqueta más útil.
 */
function elmercado_render_refined_home_visual(): string {
	$products = elmercado_home_visual_products();

	if ( empty( $products ) ) {
		return '<div class="emo-hero__placeholder" aria-hidden="true"><span></span><span></span><span></span></div>';
	}

	/* Este texto mantiene estable la espera de las auditorías automatizadas. */
	$html = '<div class="emo-hero__visual" aria-label="Los favoritos de nuestros clientes">';

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
			esc_html__( 'De los más elegidos', 'elmercadodeorigen' ),
			esc_html( $product->get_name() )
		);
	}

	$html .= '</div>';

	return $html;
}

/**
 * Categorías principales con una introducción vinculada al catálogo real.
 */
function elmercado_render_refined_home_categories(): string {
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

	$html  = '<section class="emo-section emo-categories"><div class="emo-shell">';
	$html .= '<div class="emo-section-heading"><div><span class="emo-kicker">' . esc_html__( 'Empieza por lo que te apetece', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Una despensa para disfrutarla de verdad', 'elmercadodeorigen' ) . '</h2></div><p>' . esc_html__( 'Aceites para cada día, ibéricos para compartir y productos con los que convertir una comida o un regalo en algo especial.', 'elmercadodeorigen' ) . '</p></div>';
	$html .= '<div class="emo-category-grid">';

	foreach ( $terms as $term ) {
		$thumbnail_id = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
		$image         = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
		$link          = get_term_link( $term );

		if ( is_wp_error( $link ) ) {
			continue;
		}

		$style  = $image ? ' style="--emo-category-image:url(' . esc_url( $image ) . ')"' : '';
		$html  .= '<a class="emo-category-card" href="' . esc_url( $link ) . '"' . $style . '>';
		$html  .= '<span class="emo-category-card__media" aria-hidden="true"></span>';
		$html  .= '<span class="emo-category-card__content"><strong>' . esc_html( $term->name ) . '</strong><small>' . sprintf( esc_html( _n( '%s producto', '%s productos', (int) $term->count, 'elmercadodeorigen' ) ), number_format_i18n( (int) $term->count ) ) . '</small></span>';
		$html  .= '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg>';
		$html  .= '</a>';
	}

	$html .= '</div></div></section>';

	return $html;
}

/**
 * Superventas con una explicación comprensible del criterio de ordenación.
 */
function elmercado_render_refined_home_products(): string {
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
		. '<div class="emo-section-heading"><div><span class="emo-kicker">' . esc_html__( 'Lo que más se repite', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Los productos que ya se han ganado un sitio en muchas mesas', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'Ordenados por ventas reales: una forma sencilla de empezar por lo que más eligen quienes ya compran en El Mercado de Origen.', 'elmercadodeorigen' ) . '</p></div><a class="emo-text-link" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Ver todos los productos', 'elmercadodeorigen' ) . '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>'
		. do_shortcode( $shortcode )
		. '</div></section>';
}

/**
 * Sustituye la primera versión de la portada por la revisión editorial.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$shop_url   = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/tienda/' );
		$vendor_url = elmercado_page_url( array( 'store-listing', 'tiendas', 'productores' ), '/store-listing/' );
		$about_url  = elmercado_page_url( array( 'nosotros', 'quienes-somos', 'sobre-nosotros' ), '/nosotros/' );

		$hero = '<section class="emo-hero"><div class="emo-shell emo-hero__grid"><div class="emo-hero__copy">'
			. '<span class="emo-kicker emo-kicker--light">' . esc_html__( 'Aceites, ibéricos y despensa de productores', 'elmercadodeorigen' ) . '</span>'
			. '<h1>' . esc_html__( 'Productos con origen.', 'elmercadodeorigen' ) . '<em>' . esc_html__( 'Sabor con nombre propio.', 'elmercadodeorigen' ) . '</em></h1>'
			. '<p>' . esc_html__( 'Descubre aceites, ibéricos y especialidades de despensa elegidos por su procedencia, su calidad y el trabajo de quienes los elaboran.', 'elmercadodeorigen' ) . '</p>'
			. '<div class="emo-hero__actions"><a class="emo-button emo-button--accent" href="' . esc_url( $shop_url ) . '">' . esc_html__( 'Descubrir productos', 'elmercadodeorigen' ) . '</a><a class="emo-button emo-button--ghost" href="' . esc_url( $vendor_url ) . '">' . esc_html__( 'Conocer a quienes los hacen', 'elmercadodeorigen' ) . '</a></div>'
			. '<div class="emo-hero__proof"><span><strong>' . esc_html__( 'Selección', 'elmercadodeorigen' ) . '</strong>' . esc_html__( 'Menos catálogo, más criterio', 'elmercadodeorigen' ) . '</span><span><strong>' . esc_html__( 'Procedencia', 'elmercadodeorigen' ) . '</strong>' . esc_html__( 'Sabes quién lo elabora', 'elmercadodeorigen' ) . '</span><span><strong>' . esc_html__( 'Confianza', 'elmercadodeorigen' ) . '</strong>' . esc_html__( 'Pago seguro y atención cercana', 'elmercadodeorigen' ) . '</span></div>'
			. '</div>' . elmercado_render_refined_home_visual() . '</div></section>';

		$trust = '<section class="emo-trust"><div class="emo-shell emo-trust__grid">'
			. '<article><span>01</span><div><strong>' . esc_html__( 'Elegimos lo que merece la pena', 'elmercadodeorigen' ) . '</strong><p>' . esc_html__( 'Aceites, ibéricos y especialidades seleccionados por su calidad y su procedencia.', 'elmercadodeorigen' ) . '</p></div></article>'
			. '<article><span>02</span><div><strong>' . esc_html__( 'El productor sigue visible', 'elmercadodeorigen' ) . '</strong><p>' . esc_html__( 'Sabes quién elabora cada producto y puedes conocer su proyecto antes de comprar.', 'elmercadodeorigen' ) . '</p></div></article>'
			. '<article><span>03</span><div><strong>' . esc_html__( 'Comprar resulta sencillo', 'elmercadodeorigen' ) . '</strong><p>' . esc_html__( 'Información clara, pago seguro y atención cercana durante todo el pedido.', 'elmercadodeorigen' ) . '</p></div></article>'
			. '</div></section>';

		$story = '<section class="emo-section emo-story"><div class="emo-shell emo-story__grid"><div class="emo-story__panel">'
			. '<span class="emo-kicker emo-kicker--light">' . esc_html__( 'Nuestro criterio', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'No vendemos de todo. Elegimos lo que tiene algo que aportar.', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'Reunimos productos cuya procedencia, forma de elaboración y calidad justifican que lleguen a tu mesa. Menos ruido, más producto y más personas visibles detrás.', 'elmercadodeorigen' ) . '</p><a class="emo-text-link emo-text-link--light" href="' . esc_url( $about_url ) . '">' . esc_html__( 'Conoce cómo nace el proyecto', 'elmercadodeorigen' ) . '<svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M13 6l6 6-6 6"/></svg></a></div>'
			. '<div class="emo-story__values"><article><span>01</span><h3>' . esc_html__( 'Sabes de dónde viene', 'elmercadodeorigen' ) . '</h3><p>' . esc_html__( 'La procedencia no es una nota al pie: forma parte del valor de cada producto.', 'elmercadodeorigen' ) . '</p></article><article><span>02</span><h3>' . esc_html__( 'La calidad se disfruta', 'elmercadodeorigen' ) . '</h3><p>' . esc_html__( 'Seleccionamos productos pensados para repetir, compartir y regalar con acierto.', 'elmercadodeorigen' ) . '</p></article><article><span>03</span><h3>' . esc_html__( 'Quien lo hace importa', 'elmercadodeorigen' ) . '</h3><p>' . esc_html__( 'La compra es digital, pero el productor y su manera de trabajar siguen en primer plano.', 'elmercadodeorigen' ) . '</p></article></div></div></section>';

		$vendor_cta = '<section class="emo-section emo-vendor-cta"><div class="emo-shell"><div class="emo-vendor-cta__inner"><div><span class="emo-kicker">' . esc_html__( 'Conoce el origen', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Detrás de cada producto hay una forma de hacer las cosas.', 'elmercadodeorigen' ) . '</h2><p>' . esc_html__( 'Entra en las tiendas de los productores, descubre sus proyectos y elige sabiendo a quién apoyas con cada compra.', 'elmercadodeorigen' ) . '</p></div><a class="emo-button emo-button--dark" href="' . esc_url( $vendor_url ) . '">' . esc_html__( 'Conocer a los productores', 'elmercadodeorigen' ) . '</a></div></div></section>';

		return '<div class="emo-home" data-emo-home-version="2">' . $hero . $trust . elmercado_render_refined_home_categories() . elmercado_render_refined_home_products() . $story . $vendor_cta . '</div>';
	},
	40
);

/**
 * Compacta la cabecera y el hero únicamente en la portada.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_is_optimized_home() ) {
			return;
		}
		?>
		<style id="elmercado-home-refresh">
			body.elmercado-premium-home .site-header-inner,
			body.elmercado-premium-home .site-header-inner > .woostify-container {
				min-height: 72px !important;
			}

			body.elmercado-premium-home .site-header-inner > .woostify-container {
				width: min(calc(100% - 40px), var(--emo-shell)) !important;
				max-width: var(--emo-shell) !important;
				padding-block: 0 !important;
			}

			body.elmercado-premium-home .site-branding img,
			body.elmercado-premium-home .custom-logo {
				max-height: 52px !important;
			}

			body.elmercado-premium-home .emo-hero {
				min-height: min(690px, calc(100svh - 108px));
				padding-block: clamp(3.25rem, 5.5vw, 5.75rem);
			}

			body.elmercado-premium-home .emo-hero h1 {
				max-width: 760px;
				font-size: clamp(3.25rem, 6vw, 6.25rem);
			}

			body.elmercado-premium-home .emo-hero__proof {
				margin-top: clamp(2rem, 4vw, 3.25rem);
			}

			body.elmercado-premium-home .hustle-ui,
			body.elmercado-premium-home .hustle-popup,
			body.elmercado-premium-home .hustle-slidein,
			body.elmercado-premium-home [id^="hustle-"] {
				display: none !important;
			}

			@media (max-width: 991px) {
				body.elmercado-premium-home .site-header-inner,
				body.elmercado-premium-home .site-header-inner > .woostify-container {
					min-height: 64px !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container {
					width: min(calc(100% - 28px), var(--emo-shell)) !important;
				}

				body.elmercado-premium-home .site-branding img,
				body.elmercado-premium-home .custom-logo {
					max-height: 44px !important;
				}

				body.elmercado-premium-home .emo-hero {
					min-height: auto;
					padding-block: 3.25rem 4.5rem;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Retira los recursos de Hustle en la portada. El formulario de suscripción no
 * forma parte de la experiencia final y no debe aparecer tras una interacción.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! elmercado_is_optimized_home() ) {
			return;
		}

		$style_handles = array( 'hustle_icons', 'hustle-fonts', 'hustle_global', 'hustle_optin', 'hustle_popup' );
		$script_handles = array( 'hui_scripts', 'hustle_front' );

		foreach ( $style_handles as $handle ) {
			wp_dequeue_style( $handle );
		}

		foreach ( $script_handles as $handle ) {
			wp_dequeue_script( $handle );
		}

		global $wp_styles, $wp_scripts;

		if ( $wp_styles instanceof WP_Styles ) {
			foreach ( $wp_styles->registered as $handle => $style ) {
				$source = isset( $style->src ) ? (string) $style->src : '';

				if ( str_contains( $source, '/plugins/wordpress-popup/' ) ) {
					wp_dequeue_style( (string) $handle );
				}
			}
		}

		if ( $wp_scripts instanceof WP_Scripts ) {
			foreach ( $wp_scripts->registered as $handle => $script ) {
				$source = isset( $script->src ) ? (string) $script->src : '';

				if ( str_contains( $source, '/plugins/wordpress-popup/' ) ) {
					wp_dequeue_script( (string) $handle );
				}
			}
		}
	},
	PHP_INT_MAX
);

add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		if ( elmercado_is_optimized_home() && ( str_contains( $href, '/plugins/wordpress-popup/' ) || str_starts_with( $handle, 'hustle' ) ) ) {
			return '';
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		if ( elmercado_is_optimized_home() && ( str_contains( $src, '/plugins/wordpress-popup/' ) || in_array( $handle, array( 'hui_scripts', 'hustle_front' ), true ) ) ) {
			return '';
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * Última red de seguridad para recursos directos y textos de la barra superior.
 */
function elmercado_refresh_home_output( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$html = (string) preg_replace( '/<link\b[^>]*\/plugins\/wordpress-popup\/[^>]*>/i', '', $html );
	$html = (string) preg_replace( '/<script\b[^>]*\/plugins\/wordpress-popup\/[^>]*>\s*<\/script>/is', '', $html );

	$html = str_replace(
		array(
			'Selección cuidada de productores',
			'Compra segura y transparente',
			'Envíos preparados con cuidado',
		),
		array(
			'Productores y artesanos con nombre propio',
			'Pago seguro y atención cercana',
			'Envíos preparados desde el origen',
		),
		$html
	);

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( ! elmercado_is_optimized_home() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		ob_start( 'elmercado_refresh_home_output' );
	},
	-500
);
