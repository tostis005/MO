<?php
/**
 * Rendimiento, accesibilidad técnica y entrega condicional de recursos.
 *
 * La portada está renderizada por el child theme y no utiliza el contenido
 * Elementor original. Esto permite retirar recursos de plugins que no tienen
 * interfaz activa en esa página sin afectar tienda, producto, carrito, checkout,
 * cuenta, formularios ni paneles de vendedores.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si estamos sirviendo la portada pública optimizada.
 */
function elmercado_is_optimized_home(): bool {
	return ! is_admin() && is_front_page();
}

/**
 * Sirve archivos de descubrimiento simples sin cargar la plantilla completa.
 */
add_action(
	'init',
	static function (): void {
		if ( is_admin() || wp_doing_ajax() ) {
			return;
		}

		$path = isset( $_SERVER['REQUEST_URI'] )
			? (string) wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH )
			: '';

		if ( '/robots.txt' === $path ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'Cache-Control: public, max-age=3600' );
			echo "User-agent: *\n";
			echo "Disallow: /wp-admin/\n";
			echo "Allow: /wp-admin/admin-ajax.php\n";
			echo 'Sitemap: ' . esc_url_raw( home_url( '/wp-sitemap.xml' ) ) . "\n";
			exit;
		}

		if ( '/llms.txt' === $path ) {
			header( 'Content-Type: text/markdown; charset=utf-8' );
			header( 'Cache-Control: public, max-age=3600' );
			echo "# El Mercado de Origen\n\n";
			echo "Marketplace de alimentos y productos con origen, elaborado por productores y artesanos.\n\n";
			echo "## Secciones principales\n\n";
			echo '- [Portada](' . esc_url_raw( home_url( '/' ) ) . ")\n";
			echo '- [Tienda](' . esc_url_raw( home_url( '/tienda/' ) ) . ")\n";
			echo '- [Productores](' . esc_url_raw( home_url( '/productores/' ) ) . ")\n";
			echo '- [Blog](' . esc_url_raw( home_url( '/blog/' ) ) . ")\n";
			echo '- [Contacto](' . esc_url_raw( home_url( '/contacto/' ) ) . ")\n";
			exit;
		}
	},
	-1000
);

/**
 * Elimina emoji legacy: los navegadores modernos resuelven emoji nativamente.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_enqueue_scripts', 'wp_enqueue_emoji_styles' );

/**
 * Encola la última capa visual fuera de la portada. En la portada se inserta
 * junto al resto del CSS del child theme para evitar una cadena de peticiones.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( elmercado_is_optimized_home() ) {
			return;
		}

		wp_enqueue_style(
			'elmercado-refinement',
			ELMERCADO_THEME_URL . '/assets/css/refinement.css',
			array( 'elmercado-premium' ),
			elmercado_asset_version( '/assets/css/refinement.css' )
		);
	},
	10000
);

/**
 * Retira un conjunto de estilos por identificador.
 *
 * @param string[] $handles Identificadores registrados.
 */
function elmercado_remove_styles( array $handles ): void {
	foreach ( $handles as $handle ) {
		wp_dequeue_style( $handle );
	}
}

/**
 * Retira un conjunto de scripts por identificador.
 *
 * @param string[] $handles Identificadores registrados.
 */
function elmercado_remove_scripts( array $handles ): void {
	foreach ( $handles as $handle ) {
		wp_dequeue_script( $handle );
	}
}

/**
 * Elimina recursos registrados cuya URL pertenezca a plugins sin interfaz en
 * la portada personalizada.
 *
 * @param WP_Dependencies $registry Registro de estilos o scripts.
 * @param string[]        $needles Fragmentos de URL.
 * @param callable        $dequeue Función de retirada.
 */
function elmercado_remove_assets_by_source( WP_Dependencies $registry, array $needles, callable $dequeue ): void {
	foreach ( $registry->registered as $handle => $asset ) {
		$source = isset( $asset->src ) ? (string) $asset->src : '';

		foreach ( $needles as $needle ) {
			if ( '' !== $source && str_contains( $source, $needle ) ) {
				$dequeue( (string) $handle );
				break;
			}
		}
	}
}

/**
 * Optimiza la cola de la portada después de que plugins y tema hayan encolado.
 */
function elmercado_optimize_home_assets(): void {
	if ( ! elmercado_is_optimized_home() ) {
		return;
	}

	global $wp_styles, $wp_scripts;

	/* Recursos sin una interfaz activa en la portada construida por el tema. */
	elmercado_remove_styles(
		array(
			'font-awesome',
			'berocket_products_label_style',
			'acfwf-wc-cart-block-integration',
			'acfwf-wc-checkout-block-integration',
			'acfw-blocks-frontend',
			'woostify-fonts',
			'xoo-aff-style',
			'xoo-aff-font-awesome5',
			'mediaelement',
			'wp-mediaelement',
			'jquery-selectBox',
			'woocommerce_prettyPhoto_css',
			'yith-wcwl-main',
			'xoo-wl-style',
			'xoo-wl-fonts',
			'yith_wcpb_bundle_frontend_style',
			'brands-styles',
			'wpos-slick-style',
			'pcdfwoo-public-style',
			'photoswipe-video',
			'woostify-slick',
			'woostify-slick-theme',
			'yith_wapo_front',
			'yith-plugin-fw-icon-font',
			'jquery-ui-style',
			'wcfm_fa_icon_css',
			'wcfm_core_css',
			'elementor-icons',
			'elementor-frontend',
			'google-fonts-1',
			'elementor-icons-shared-0',
			'elementor-icons-fa-solid',
			'elementor-icons-fa-regular',
			'jetpack_css',
			'wc-blocks-style',
			'fluentform-elementor-widget',
			'owl_carousel_css',
			'owl_theme_css',
			'owl_animate_css',
			'lightgallery_css',
			'lightgallery_bundle_css',
			'hustle-fonts',
		)
	);

	if ( ! is_user_logged_in() ) {
		wp_dequeue_style( 'dashicons' );
	}

	elmercado_remove_scripts(
		array(
			'xoo-aff-js',
			'wc-cart-fragments',
			'jquery-blockui',
			'underscore',
			'wp-util',
			'photoswipe',
			'xoo-wl-js',
			'jquery-selectBox',
			'prettyPhoto',
			'jquery-yith-wcwl',
			'wp-hooks',
			'wp-i18n',
			'swv',
			'contact-form-7',
			'alg-wc-ean-variations',
			'wc-add-to-cart-variation',
			'yith_wcpb_bundle_frontend_add_to_cart',
			'jquery-ui-core',
			'jquery-ui-datepicker',
			'jquery-ui-progressbar',
			'wc-single-product',
			'selectWoo',
			'yith_wapo_front',
			'jquery-blockui_js',
			'wcfm_core_js',
			'google-recaptcha',
			'wpcf7-recaptcha',
			'wp-polyfill',
			'woostify-arrive',
			'woostify-quantity-button',
			'woostify-product-variation',
			'lity',
			'tiny-slider',
			'woostify-flickity',
			'woostify-product-images',
			'easyzoom',
			'easyzoom-handle',
			'photoswipe-ui-default',
			'photoswipe-init',
			'woostify-woocommerce-sidebar',
			'owl_carousel_js',
			'mousewheel_js',
			'owl_thumbs_js',
			'lightgallery_js',
			'lightgallery_video_js',
			'lightgallery_zoom_js',
			'lightgallery_autoplay_js',
			'vimeo_player_js',
			'trustindex-js',
			'elementor-webpack-runtime',
			'elementor-frontend-modules',
			'elementor-frontend',
		)
	);

	if ( $wp_styles instanceof WP_Styles ) {
		elmercado_remove_assets_by_source(
			$wp_styles,
			array(
				'/plugins/elementor/',
				'/uploads/elementor/css/',
				'/plugins/contact-form-7/',
				'/plugins/advanced-product-labels-for-woocommerce/',
				'/plugins/advanced-coupons-for-woocommerce-free/',
				'/plugins/waitlist-woocommerce/',
				'/plugins/yith-woocommerce-wishlist/',
				'/plugins/yith-woocommerce-product-bundles',
				'/plugins/yith-woocommerce-product-add-ons/',
				'/plugins/product-categories-designs-for-woocommerce/',
				'/plugins/slide-anything/',
				'/plugins/wc-frontend-manager/',
			),
			'wp_dequeue_style'
		);
	}

	if ( $wp_scripts instanceof WP_Scripts ) {
		elmercado_remove_assets_by_source(
			$wp_scripts,
			array(
				'/plugins/elementor/',
				'/plugins/contact-form-7/',
				'/plugins/advanced-product-labels-for-woocommerce/',
				'/plugins/advanced-coupons-for-woocommerce-free/',
				'/plugins/waitlist-woocommerce/',
				'/plugins/yith-woocommerce-wishlist/',
				'/plugins/yith-woocommerce-product-bundles',
				'/plugins/yith-woocommerce-product-add-ons/',
				'/plugins/product-categories-designs-for-woocommerce/',
				'/plugins/slide-anything/',
				'/plugins/wc-frontend-manager/',
			),
			'wp_dequeue_script'
		);
	}

	/*
	 * Woostify ya carga el CSS padre. El enqueue propio anterior producía una
	 * segunda descarga idéntica y el style.css del child solo contiene cabecera.
	 */
	if ( $wp_styles instanceof WP_Styles ) {
		foreach ( $wp_styles->registered as $handle => $style ) {
			$source = isset( $style->src ) ? (string) $style->src : '';

			if ( 'woostify-parent' === $handle && str_contains( $source, '/themes/woostify/style.css' ) ) {
				wp_dequeue_style( (string) $handle );
			}

			if ( str_contains( $source, '/themes/elmercadodeorigen-child/style.css' ) ) {
				wp_dequeue_style( (string) $handle );
			}
		}
	}

	/*
	 * El CSS del child theme se inserta en una única capa sobre el CSS padre.
	 * Se eliminan cinco peticiones dependientes y su latencia en cascada.
	 */
	$parent_handle = wp_style_is( 'woostify-parent-style', 'registered' )
		? 'woostify-parent-style'
		: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : '' );

	if ( '' !== $parent_handle ) {
		$inline_css = '';
		$css_files  = array(
			'/assets/css/theme.css',
			'/assets/css/integrations.css',
			'/assets/css/polish.css',
			'/assets/css/final.css',
			'/assets/css/premium.css',
			'/assets/css/refinement.css',
		);

		foreach ( $css_files as $relative_path ) {
			$absolute_path = ELMERCADO_THEME_PATH . $relative_path;

			if ( is_readable( $absolute_path ) ) {
				$content = file_get_contents( $absolute_path );

				if ( false !== $content ) {
					$inline_css .= "\n" . preg_replace( '!/\*.*?\*/!s', '', $content );
				}
			}
		}

		if ( '' !== trim( $inline_css ) ) {
			wp_add_inline_style( $parent_handle, $inline_css );
		}

		elmercado_remove_styles(
			array(
				'elmercado-theme',
				'elmercado-integrations',
				'elmercado-polish',
				'elmercado-final',
				'elmercado-premium',
				'elmercado-refinement',
			)
		);
	}
}

add_action( 'wp_enqueue_scripts', 'elmercado_optimize_home_assets', PHP_INT_MAX );
add_action( 'wp_print_styles', 'elmercado_optimize_home_assets', 0 );
add_action( 'wp_print_footer_scripts', 'elmercado_optimize_home_assets', 0 );

/**
 * Ajusta la composición de imágenes del hero para que el navegador elija una
 * variante proporcionada al tamaño real, en vez de descargar la imagen single.
 *
 * @param array<string, string>              $attr Atributos de imagen.
 * @param WP_Post                            $attachment Adjunto.
 * @param string|array{0:int,1:int}|int[]    $size Tamaño solicitado.
 * @return array<string, string>
 */
add_filter(
	'wp_get_attachment_image_attributes',
	static function ( array $attr, WP_Post $attachment, $size ): array {
		if ( ! elmercado_is_optimized_home() || 'woocommerce_single' !== $size ) {
			return $attr;
		}

		$image = wp_get_attachment_image_src( $attachment->ID, 'woocommerce_thumbnail' );

		if ( is_array( $image ) ) {
			$attr['src']    = (string) $image[0];
			$attr['width']  = (string) $image[1];
			$attr['height'] = (string) $image[2];
		}

		$srcset = wp_get_attachment_image_srcset( $attachment->ID, 'woocommerce_thumbnail' );

		if ( is_string( $srcset ) ) {
			$attr['srcset'] = $srcset;
		}

		$attr['sizes'] = '(max-width: 520px) 72vw, (max-width: 991px) 48vw, 360px';
		$attr['class'] = trim( ( $attr['class'] ?? '' ) . ' emo-hero-product-image' );

		static $hero_index = 0;
		++$hero_index;

		if ( 1 === $hero_index ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
		} else {
			$attr['loading']       = 'lazy';
			$attr['fetchpriority'] = 'low';
		}

		return $attr;
	},
	30,
	3
);

/**
 * Elimina definitivamente los recursos legacy migrados al child theme y añade
 * defer a integraciones no críticas que se mantienen por funcionalidad.
 */
add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		if ( str_contains( $href, '/custom-css-js/6585.css' ) ) {
			return '';
		}

		if ( elmercado_is_optimized_home() && in_array( $handle, array( 'hustle_icons', 'hustle_global', 'hustle_optin', 'hustle_popup', 'ht_ctc_main_css' ), true ) ) {
			$async = str_replace( " rel='stylesheet'", " rel='stylesheet' media='print' onload=\"this.media='all'\"", $html );

			if ( $async === $html ) {
				$async = str_replace( ' rel="stylesheet"', ' rel="stylesheet" media="print" onload="this.media=\'all\'"', $html );
			}

			return $async;
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'script_loader_tag',
	static function ( string $tag, string $handle, string $src ): string {
		if ( str_contains( $src, '/custom-css-js/1341.js' ) ) {
			return '';
		}

		$defer_handles = array(
			'elmercado-theme',
			'monsterinsights-frontend-script',
			'woocommerce-analytics',
			'jp-tracks',
			'jp-tracks-functions',
			'ht_ctc_app_js',
			'hui_scripts',
			'hustle_front',
			'smush-lazy-load',
		);

		if ( in_array( $handle, $defer_handles, true ) && ! str_contains( $tag, ' defer' ) ) {
			$tag = str_replace( '<script ', '<script defer ', $tag );
		}

		return $tag;
	},
	PHP_INT_MAX,
	3
);
