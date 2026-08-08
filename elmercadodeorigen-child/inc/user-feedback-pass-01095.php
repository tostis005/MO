<?php
/**
 * Ajustes solicitados de filtros, navegación y portada 0.10.95.
 *
 * Mantiene el drawer compacto ya estable, elimina el trigger nativo redundante,
 * permite recorrer un rail sticky alto, sustituye subrayados de filtros por un
 * estado sombreado y afina la composición móvil de la portada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Refuerza la sección activa del menú principal en contextos donde WordPress no
 * asigna por sí solo current-menu-item al archivo de tienda o al blog.
 */
add_filter(
	'nav_menu_css_class',
	static function ( array $classes, $item, $args, int $depth ): array {
		if ( is_admin() || 0 !== $depth || ! $item instanceof WP_Post ) {
			return $classes;
		}

		$object_id = (int) $item->object_id;
		$is_product_section = ( function_exists( 'is_shop' ) && is_shop() )
			|| ( function_exists( 'is_product' ) && is_product() )
			|| ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );

		if ( $is_product_section && function_exists( 'wc_get_page_id' ) ) {
			$shop_page_id = (int) wc_get_page_id( 'shop' );
			if ( $shop_page_id > 0 && $object_id === $shop_page_id ) {
				$classes[] = 'emo-current-section';
			}
		}

		$is_blog_section = is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date();
		if ( $is_blog_section ) {
			$posts_page_id = (int) get_option( 'page_for_posts' );
			if ( $posts_page_id > 0 && $object_id === $posts_page_id ) {
				$classes[] = 'emo-current-section';
			}
		}

		return array_values( array_unique( $classes ) );
	},
	20,
	4
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		$term_link = '';
		if ( is_product_category() || is_product_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$term_link = $link;
				}
			}
		}
		?>
		<style id="elmercado-user-feedback-pass-01095">
			/* El rail ya está visible en PC y el drawer propio es el único trigger compacto. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) .woostify-sorting :is(
				button.filter,
				a.filter,
				.filter.show,
				.emo-remove-filter-toggle
			) {
				display: none !important;
				visibility: hidden !important;
				width: 0 !important;
				height: 0 !important;
				min-width: 0 !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				overflow: hidden !important;
				pointer-events: none !important;
			}

			/* Los filtros se leen como opciones seleccionables, nunca como enlaces subrayados. */
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) :is(
				.widget_product_categories,
				.widget_product_tag_cloud,
				.tagcloud,
				.woocommerce-widget-layered-nav-list
			) a {
				text-decoration: none !important;
				text-decoration-line: none !important;
				transition: background-color 160ms ease, color 160ms ease, border-color 160ms ease !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) :is(.widget_product_categories,.woocommerce-widget-layered-nav-list) li > a {
				display: flex !important;
				min-height: 38px !important;
				align-items: center !important;
				margin: 3px 0 !important;
				padding: 8px 10px !important;
				border-radius: 9px !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) :is(.widget_product_tag_cloud,.tagcloud) a {
				border-radius: 999px !important;
			}

			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) :is(
				.widget_product_categories,
				.widget_product_tag_cloud,
				.tagcloud,
				.woocommerce-widget-layered-nav-list
			) a:is(:hover,:focus-visible),
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) :is(.current-cat,.woocommerce-widget-layered-nav-list__item--chosen,.emo-filter-is-active) > a,
			body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) a.emo-filter-is-active {
				background: #eaf2ed !important;
				color: #173f32 !important;
				border-color: transparent !important;
				font-weight: 800 !important;
				text-decoration: none !important;
				text-decoration-line: none !important;
			}

			<?php if ( $term_link ) : ?>
			body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(
				#secondary.widget-area,
				.shop-widget-area,
				#emo-premium-filter-shell .emo-mobile-filter-content
			) a[href="<?php echo esc_url( $term_link ); ?>"] {
				background: #eaf2ed !important;
				color: #173f32 !important;
				border-color: transparent !important;
				font-weight: 850 !important;
				text-decoration: none !important;
			}
			<?php endif; ?>

			/* El mismo indicador del hover del menú permanece en la sección actual. */
			body.elmercado-child-theme .main-navigation .primary-navigation > :is(
				.current-menu-item,
				.current-menu-parent,
				.current-menu-ancestor,
				.current_page_item,
				.current_page_parent,
				.current_page_ancestor,
				.emo-current-section
			) > a::after,
			body.elmercado-child-theme .primary-navigation > :is(
				.current-menu-item,
				.current-menu-parent,
				.current-menu-ancestor,
				.current_page_item,
				.current_page_parent,
				.current_page_ancestor,
				.emo-current-section
			) > a::after {
				transform: scaleX(1) !important;
				transform-origin: left !important;
			}

			/* El titular de superventas conserva presencia sin fragmentarse en exceso. */
			body.home.elmercado-child-theme .emo-featured-products .emo-section-heading > div {
				max-width: 960px !important;
			}
			body.home.elmercado-child-theme .emo-featured-products .emo-section-heading h2 {
				max-width: 960px !important;
				font-size: clamp(2.05rem, 3.1vw, 3.45rem) !important;
				line-height: 1.08 !important;
				text-wrap: pretty !important;
			}

			@media (min-width: 1101px) {
				/* Sticky con viewport propio: un filtro alto se recorre sin esperar al final de la página. */
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					max-height: calc(100dvh - 112px) !important;
					overflow-x: hidden !important;
					overflow-y: auto !important;
					overscroll-behavior: contain !important;
					scrollbar-width: thin;
				}
			}

			@media (max-width: 767px) {
				body.home.elmercado-child-theme .emo-featured-products .emo-section-heading h2 {
					font-size: clamp(1.9rem, 8vw, 2.2rem) !important;
					line-height: 1.12 !important;
					text-wrap: pretty !important;
				}

				/* En móvil el bloque editorial posterior a productos se integra en el fondo claro. */
				body.home.elmercado-child-theme .emo-story {
					background: var(--emo-paper) !important;
				}
				body.home.elmercado-child-theme .emo-story__panel {
					min-height: 0 !important;
					padding: 0 0 1.5rem !important;
					background: transparent !important;
					background-image: none !important;
					border: 0 !important;
					border-radius: 0 !important;
					box-shadow: none !important;
					color: var(--emo-ink) !important;
				}
				body.home.elmercado-child-theme .emo-story__panel :is(h2,h3,strong,span) {
					color: var(--emo-forest-950) !important;
				}
				body.home.elmercado-child-theme .emo-story__panel p {
					color: var(--emo-muted) !important;
				}
				body.home.elmercado-child-theme .emo-story__panel .emo-kicker {
					color: var(--emo-clay-dark) !important;
				}
				body.home.elmercado-child-theme .emo-story__panel .emo-text-link {
					color: var(--emo-forest-900) !important;
					text-decoration-color: transparent !important;
				}

				/* La última sección blanca llega directamente al pie, sin franja residual. */
				body.home.elmercado-child-theme #content.site-content,
				body.home.elmercado-child-theme .site-content,
				body.home.elmercado-child-theme .emo-home {
					margin-bottom: 0 !important;
					padding-bottom: 0 !important;
				}
				body.home.elmercado-child-theme .site-footer {
					margin-top: 0 !important;
				}
				body.home.elmercado-child-theme .site-footer::before {
					display: none !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
