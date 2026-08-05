<?php
/**
 * Primera versión cerrada de la experiencia comercial.
 *
 * Refuerza el posicionamiento directo del productor al consumidor y elimina
 * elementos que distraen de la compra: reCAPTCHA, wishlist, avisos de stock y
 * el conmutador de filtros vacío. También termina de alinear la cabecera y
 * simplifica la barra lateral de la tienda.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sustituye la redacción provisional de la portada por una propuesta más fiel
 * al catálogo real y al concepto original de El Mercado de Origen.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$replacements = array(
			'Aceites, ibéricos y despensa de productores' => 'Aceites, jamones, paletas ibéricas y naranjas',
			'Productos con origen.' => 'Del productor',
			'Sabor con nombre propio.' => 'directo a tu casa.',
			'Descubre aceites, ibéricos y especialidades de despensa elegidos por su procedencia, su calidad y el trabajo de quienes los elaboran.' => 'Seleccionamos productores que cuidan cada detalle para que recibas en casa aceites de oliva virgen extra, jamones y paletas ibéricas y naranjas de temporada con todo su sabor y su origen intactos.',
			'Descubrir productos' => 'Comprar productos',
			'Conocer a quienes los hacen' => 'Conocer a los productores',
			'Selección' => 'Directo',
			'Menos catálogo, más criterio' => 'Del productor, sin rodeos',
			'Procedencia' => 'Calidad',
			'Sabes quién lo elabora' => 'Productos elegidos para repetir',
			'Confianza' => 'Cercanía',
			'Pago seguro y atención cercana' => 'Atención antes y después de tu compra',
			'Elegimos lo que merece la pena' => 'Directo desde el origen',
			'Aceites, ibéricos y especialidades seleccionados por su calidad y su procedencia.' => 'Aceites, jamones, paletas y naranjas enviados desde quienes los producen.',
			'El productor sigue visible' => 'Sabes a quién compras',
			'Sabes quién elabora cada producto y puedes conocer su proyecto antes de comprar.' => 'Conoces al productor, su forma de trabajar y el origen de lo que llega a tu casa.',
			'Comprar resulta sencillo' => 'Del origen a tu puerta',
			'Información clara, pago seguro y atención cercana durante todo el pedido.' => 'Compra sencilla, pago seguro y atención cercana hasta que recibes el pedido.',
			'Empieza por lo que te apetece' => 'Elige tu próximo favorito',
			'Una despensa para disfrutarla de verdad' => 'Aceite, ibéricos y fruta con origen',
			'Aceites para cada día, ibéricos para compartir y productos con los que convertir una comida o un regalo en algo especial.' => 'AOVE para cada día, jamones y paletas para disfrutar y regalar, y naranjas de temporada enviadas directamente desde quien las cultiva.',
			'Lo que más se repite' => 'Los favoritos de nuestros clientes',
			'Los productos que ya se han ganado un sitio en muchas mesas' => 'Los productos que más vuelven a entrar en el carrito',
			'Ordenados por ventas reales: una forma sencilla de empezar por lo que más eligen quienes ya compran en El Mercado de Origen.' => 'Una selección ordenada por ventas reales: aceites, jamones y paletas que ya han convencido a quienes compran directamente a nuestros productores.',
			'Nuestro criterio' => 'Del productor al consumidor',
			'No vendemos de todo. Elegimos lo que tiene algo que aportar.' => 'Acortamos la distancia entre quien lo hace bien y quien sabe disfrutarlo.',
			'Reunimos productos cuya procedencia, forma de elaboración y calidad justifican que lleguen a tu mesa. Menos ruido, más producto y más personas visibles detrás.' => 'El Mercado de Origen reúne productores que trabajan con cuidado y clientes que buscan algo más que una etiqueta. Tú eliges y el producto viaja desde su origen hasta tu casa.',
			'Sabes de dónde viene' => 'Origen visible',
			'La procedencia no es una nota al pie: forma parte del valor de cada producto.' => 'Sabes quién produce lo que compras y qué historia hay detrás.',
			'La calidad se disfruta' => 'Calidad que se nota',
			'Seleccionamos productos pensados para repetir, compartir y regalar con acierto.' => 'Elegimos referencias que destacan por su sabor, su elaboración y la confianza que generan.',
			'Quien lo hace importa' => 'Relación directa',
			'La compra es digital, pero el productor y su manera de trabajar siguen en primer plano.' => 'Menos distancia, más transparencia y una atención que continúa después del pago.',
			'Conoce el origen' => 'Conoce a los productores',
			'Detrás de cada producto hay una forma de hacer las cosas.' => 'Detrás de cada aceite, cada jamón y cada naranja hay alguien que se juega su nombre.',
			'Entra en las tiendas de los productores, descubre sus proyectos y elige sabiendo a quién apoyas con cada compra.' => 'Descubre sus proyectos, cómo trabajan y por qué sus productos merecen llegar directamente a tu casa.',
			'Conocer a los productores' => 'Ver productores',
			'De los más elegidos' => 'Directo del productor',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	},
	60
);

/**
 * Desactiva la integración reCAPTCHA v3 de Contact Form 7 de forma completa.
 * Se conserva una protección ligera mediante campo trampa y tiempo mínimo.
 */
add_action(
	'init',
	static function (): void {
		remove_action( 'wp_enqueue_scripts', 'wpcf7_recaptcha_enqueue_scripts', 20 );
		remove_filter( 'wpcf7_form_hidden_fields', 'wpcf7_recaptcha_add_hidden_fields', 100 );
		remove_filter( 'wpcf7_spam', 'wpcf7_recaptcha_verify_response', 9 );
	},
	100
);

add_filter(
	'wpcf7_form_elements',
	static function ( string $content ): string {
		$started = time();

		return $content
			. '<div class="emo-form-honeypot" aria-hidden="true">'
			. '<label>No completar este campo<input type="text" name="_emo_website" value="" tabindex="-1" autocomplete="off"></label>'
			. '</div><input type="hidden" name="_emo_started" value="' . esc_attr( (string) $started ) . '">';
	}
);

add_filter(
	'wpcf7_spam',
	static function ( bool $spam, $submission ): bool {
		if ( $spam ) {
			return true;
		}

		$honeypot = isset( $_POST['_emo_website'] )
			? trim( sanitize_text_field( wp_unslash( $_POST['_emo_website'] ) ) )
			: '';
		$started  = isset( $_POST['_emo_started'] ) ? absint( $_POST['_emo_started'] ) : 0;

		if ( '' !== $honeypot || ( $started > 0 && ( time() - $started ) < 2 ) ) {
			if ( is_object( $submission ) && method_exists( $submission, 'add_spam_log' ) ) {
				$submission->add_spam_log(
					array(
						'agent'  => 'elmercado-honeypot',
						'reason' => 'La comprobación antispam ligera no se superó.',
					)
				);
			}

			return true;
		}

		return false;
	},
	8,
	2
);

/**
 * Indica si la página actual es la página propia de wishlist.
 */
function elmercado_is_wishlist_screen(): bool {
	return function_exists( 'yith_wcwl_is_wishlist_page' ) && yith_wcwl_is_wishlist_page();
}

/**
 * Retira recursos que ya no tienen interfaz visible.
 */
function elmercado_release_remove_assets(): void {
	if ( is_admin() ) {
		return;
	}

	global $wp_styles, $wp_scripts;

	$style_handles = array(
		'xoo-wl-style',
		'xoo-wl-fonts',
		'yith-wcwl-main',
		'yith-wcwl-font-awesome',
	);
	$script_handles = array(
		'google-recaptcha',
		'wpcf7-recaptcha',
		'xoo-wl-js',
		'jquery-yith-wcwl',
		'yith-wcwl',
	);

	foreach ( $style_handles as $handle ) {
		if ( ! elmercado_is_wishlist_screen() || ! str_contains( $handle, 'yith' ) ) {
			wp_dequeue_style( $handle );
		}
	}

	foreach ( $script_handles as $handle ) {
		if ( ! elmercado_is_wishlist_screen() || ! str_contains( $handle, 'yith' ) ) {
			wp_dequeue_script( $handle );
		}
	}

	$remove_sources = array(
		'/plugins/waitlist-woocommerce/',
		'/plugins/contact-form-7/modules/recaptcha/',
		'google.com/recaptcha/api.js',
		'recaptcha.net/recaptcha/api.js',
	);

	if ( ! elmercado_is_wishlist_screen() ) {
		$remove_sources[] = '/plugins/yith-woocommerce-wishlist/';
	}

	if ( $wp_styles instanceof WP_Styles ) {
		foreach ( $wp_styles->registered as $handle => $style ) {
			$source = isset( $style->src ) ? (string) $style->src : '';

			foreach ( $remove_sources as $fragment ) {
				if ( '' !== $source && str_contains( $source, $fragment ) ) {
					wp_dequeue_style( (string) $handle );
					break;
				}
			}
		}
	}

	if ( $wp_scripts instanceof WP_Scripts ) {
		foreach ( $wp_scripts->registered as $handle => $script ) {
			$source = isset( $script->src ) ? (string) $script->src : '';

			foreach ( $remove_sources as $fragment ) {
				if ( '' !== $source && str_contains( $source, $fragment ) ) {
					wp_dequeue_script( (string) $handle );
					break;
				}
			}
		}
	}
}

add_action( 'wp_enqueue_scripts', 'elmercado_release_remove_assets', PHP_INT_MAX );

add_filter(
	'do_shortcode_tag',
	static function ( string $output, string $tag ): string {
		if ( in_array( $tag, array( 'xoo_wl_form', 'yith_wcwl_add_to_wishlist' ), true ) ) {
			return '';
		}

		return $output;
	},
	20,
	2
);

/**
 * Evita que recursos impresos tarde por plugins vuelvan a entrar en la página.
 */
add_filter(
	'style_loader_tag',
	static function ( string $html, string $handle, string $href ): string {
		$fragments = array(
			'/plugins/waitlist-woocommerce/',
			'/plugins/contact-form-7/modules/recaptcha/',
		);

		if ( ! elmercado_is_wishlist_screen() ) {
			$fragments[] = '/plugins/yith-woocommerce-wishlist/';
		}

		foreach ( $fragments as $fragment ) {
			if ( str_contains( $href, $fragment ) ) {
				return '';
			}
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

add_filter(
	'script_loader_tag',
	static function ( string $html, string $handle, string $src ): string {
		$fragments = array(
			'/plugins/waitlist-woocommerce/',
			'/plugins/contact-form-7/modules/recaptcha/',
			'google.com/recaptcha/api.js',
			'recaptcha.net/recaptcha/api.js',
		);

		if ( ! elmercado_is_wishlist_screen() ) {
			$fragments[] = '/plugins/yith-woocommerce-wishlist/';
		}

		foreach ( $fragments as $fragment ) {
			if ( str_contains( $src, $fragment ) ) {
				return '';
			}
		}

		return $html;
	},
	PHP_INT_MAX,
	3
);

/**
 * Composición final: cabecera alineada, tienda limpia y plugins invisibles.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-release-one">
			.emo-form-honeypot,
			.grecaptcha-badge,
			.xoo-wl-form-wrapper,
			.xoo-wl-modal,
			.xoo-wl-inmodal,
			.xoo-wl-btn-container,
			.xoo-wl-waitlist-button,
			.xoo-wl-form,
			.xoo-wl-notice,
			.xoo-wl-added-to-waitlist,
			.yith-wcwl-add-to-wishlist,
			.yith-wcwl-add-button,
			a.add_to_wishlist {
				display: none !important;
			}

			body.woocommerce-shop .woostify-sorting .emo-remove-filter-toggle,
			body.woocommerce-shop .woostify-sorting .filter-button,
			body.woocommerce-shop .woostify-sorting .woostify-filter-button,
			body.woocommerce-shop .woostify-sorting .toggle-sidebar,
			body.woocommerce-shop .woostify-sorting .toggle-sidebar-shop,
			body.woocommerce-shop .woostify-sorting .sidebar-toggle,
			body.woocommerce-shop .woostify-sorting .open-filter {
				display: none !important;
			}

			@media (min-width: 992px) {
				body.elmercado-premium-home .site-header-inner {
					position: relative !important;
					height: 62px !important;
					min-height: 62px !important;
					padding: 0 !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container {
					position: relative !important;
					display: grid !important;
					grid-template-columns: minmax(220px, auto) minmax(0, 1fr) auto !important;
					height: 62px !important;
					min-height: 62px !important;
					align-items: center !important;
					padding: 0 !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-branding,
				body.elmercado-premium-home .site-header-inner > .woostify-container > .main-navigation,
				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-tools {
					position: static !important;
					top: auto !important;
					right: auto !important;
					bottom: auto !important;
					left: auto !important;
					inset: auto !important;
					height: auto !important;
					min-height: 0 !important;
					margin: 0 !important;
					align-self: center !important;
					transform: none !important;
					translate: none !important;
				}

				body.elmercado-premium-home .site-header .site-branding,
				body.elmercado-premium-home .site-header .site-branding > a,
				body.elmercado-premium-home .site-header .main-navigation,
				body.elmercado-premium-home .site-header .site-tools {
					display: flex !important;
					align-items: center !important;
				}

				body.elmercado-premium-home .site-header .site-branding,
				body.elmercado-premium-home .site-header .site-branding > a,
				body.elmercado-premium-home .site-header .main-navigation,
				body.elmercado-premium-home .site-header .site-tools {
					height: 62px !important;
				}
			}

			@media (max-width: 991px) {
				body.elmercado-premium-home .site-header-inner,
				body.elmercado-premium-home .site-header-inner > .woostify-container {
					position: relative !important;
					height: 60px !important;
					min-height: 60px !important;
					padding-block: 0 !important;
					align-items: center !important;
				}

				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-branding,
				body.elmercado-premium-home .site-header-inner > .woostify-container > .site-tools,
				body.elmercado-premium-home .site-header-inner > .woostify-container > .toggle-sidebar-menu-btn {
					position: static !important;
					top: auto !important;
					inset: auto !important;
					margin-block: 0 !important;
					align-self: center !important;
					transform: none !important;
					translate: none !important;
				}
			}

			body.elmercado-child-theme.woocommerce-shop #secondary {
				padding: 0 1.25rem !important;
				background: #fff !important;
				border: 1px solid rgba(13, 33, 27, 0.09) !important;
				border-radius: 24px !important;
				box-shadow: 0 18px 50px rgba(13, 33, 27, 0.07) !important;
				overflow: hidden;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .widget {
				margin: 0 !important;
				padding: 1.45rem 0 !important;
				background: transparent !important;
				border: 0 !important;
				border-radius: 0 !important;
				box-shadow: none !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .widget + .widget {
				border-top: 1px solid rgba(13, 33, 27, 0.09) !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .widget_product_tag_cloud {
				display: none !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .widget-title {
				margin: 0 0 1rem !important;
				padding: 0 0 0.75rem !important;
				border-bottom: 1px solid rgba(13, 33, 27, 0.09) !important;
				font-size: 0.76rem !important;
				font-weight: 800 !important;
				letter-spacing: 0.08em !important;
				text-transform: uppercase !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .price_slider_wrapper .ui-widget-content {
				height: 4px !important;
				background: #e6e1d7 !important;
				border: 0 !important;
				border-radius: 999px !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .price_slider_wrapper .ui-slider-range {
				background: var(--emo-forest-700) !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .price_slider_wrapper .ui-slider-handle {
				top: 50% !important;
				width: 16px !important;
				height: 16px !important;
				margin-top: -8px !important;
				background: #fff !important;
				border: 4px solid var(--emo-forest-700) !important;
				border-radius: 50% !important;
				box-shadow: 0 3px 10px rgba(13, 33, 27, 0.15) !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .price_slider_amount {
				display: flex !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 0.75rem !important;
				flex-wrap: wrap !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .price_slider_amount .button {
				width: auto !important;
				min-height: 42px !important;
				margin: 0 !important;
				padding: 0.65rem 1rem !important;
				border-radius: 999px !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .price_label {
				font-size: 0.82rem !important;
				font-weight: 650 !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .product-categories,
			body.elmercado-child-theme.woocommerce-shop #secondary .product-categories li {
				margin: 0 !important;
				padding: 0 !important;
				list-style: none !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .product-categories li + li {
				border-top: 1px solid rgba(13, 33, 27, 0.07) !important;
			}

			body.elmercado-child-theme.woocommerce-shop #secondary .product-categories a {
				display: flex !important;
				min-height: 42px !important;
				align-items: center !important;
				padding: 0.55rem 0 !important;
				color: var(--emo-forest-900) !important;
				font-weight: 650 !important;
				text-decoration: none !important;
			}

			@media (max-width: 1099px) {
				body.elmercado-child-theme.woocommerce-shop #secondary {
					width: 100% !important;
					margin-top: 2rem !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Limpia nodos que plugins insertan por AJAX y localiza el botón de filtro por
 * su etiqueta, independientemente del nombre de clase de la versión de Woostify.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-release-cleanup">
		(() => {
			const nuisanceSelector = [
				'.grecaptcha-badge',
				'.xoo-wl-form-wrapper', '.xoo-wl-modal', '.xoo-wl-inmodal',
				'.xoo-wl-btn-container', '.xoo-wl-waitlist-button', '.xoo-wl-form',
				'.xoo-wl-notice', '.xoo-wl-added-to-waitlist',
				'.yith-wcwl-add-to-wishlist'
			].join(',');

			const clean = (root = document) => {
				root.querySelectorAll?.(nuisanceSelector).forEach((node) => node.remove());

				root.querySelectorAll?.('.woostify-sorting button, .woostify-sorting a, .woostify-sorting [role="button"]').forEach((control) => {
					const label = `${control.textContent || ''} ${control.getAttribute('aria-label') || ''}`.trim().toLocaleLowerCase('es');

					if (/^(filtro|filter|filtrar productos)(\s|$)/i.test(label)) {
						control.classList.add('emo-remove-filter-toggle');
						control.hidden = true;
						control.setAttribute('aria-hidden', 'true');
					}
				});
			};

			clean();
			new MutationObserver((records) => {
				records.forEach((record) => record.addedNodes.forEach((node) => {
					if (node.nodeType === 1) clean(node);
				}));
			}).observe(document.body, { childList: true, subtree: true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Última barrera para etiquetas externas y textos de la franja superior.
 */
function elmercado_release_output( string $html ): string {
	if ( '' === $html ) {
		return $html;
	}

	$fragments = array(
		'/plugins/waitlist-woocommerce/',
		'/plugins/contact-form-7/modules/recaptcha/',
		'google.com/recaptcha/api.js',
		'recaptcha.net/recaptcha/api.js',
	);

	if ( ! elmercado_is_wishlist_screen() ) {
		$fragments[] = '/plugins/yith-woocommerce-wishlist/';
	}

	foreach ( $fragments as $fragment ) {
		$quoted = preg_quote( $fragment, '/' );
		$html   = (string) preg_replace( '/<link\b[^>]*' . $quoted . '[^>]*>/i', '', $html );
		$html   = (string) preg_replace( '/<script\b[^>]*' . $quoted . '[^>]*>\s*<\/script>/is', '', $html );
	}

	if ( is_front_page() ) {
		$html = str_replace(
			array(
				'Productores y artesanos con nombre propio',
				'Pago seguro y atención cercana',
				'Envíos preparados desde el origen',
			),
			array(
				'Directamente de productores seleccionados',
				'Compra segura y atención cercana',
				'Envíos directos desde el productor',
			),
			$html
		);
	}

	return $html;
}

add_action(
	'template_redirect',
	static function (): void {
		if ( is_admin() || is_feed() || is_trackback() || wp_doing_ajax() ) {
			return;
		}

		ob_start( 'elmercado_release_output' );
	},
	-100
);
