<?php
/**
 * Ajustes de acabado profesional basados en la auditoría visual desplegada.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-professional-finish">
			/* Tienda: el filtro de productor nunca debe comprimirse ni escribirse en vertical. */
			body.elmercado-child-theme.woocommerce-shop .emo-vendor-filter {
				display: grid !important;
				grid-template-columns: max-content minmax(190px, 280px) !important;
				align-items: center !important;
				justify-content: start !important;
				gap: 0.75rem 1rem !important;
				min-width: 0 !important;
			}

			body.elmercado-child-theme.woocommerce-shop .emo-vendor-filter label {
				display: inline-block !important;
				width: auto !important;
				min-width: max-content !important;
				margin: 0 !important;
				writing-mode: horizontal-tb !important;
				text-orientation: mixed !important;
				white-space: nowrap !important;
				word-break: normal !important;
				overflow-wrap: normal !important;
			}

			body.elmercado-child-theme.woocommerce-shop .emo-vendor-filter select {
				width: 100% !important;
				min-width: 0 !important;
			}

			body.elmercado-child-theme.woocommerce-shop .emo-shop-controls,
			body.elmercado-child-theme.woocommerce-shop .woocommerce-shop-header {
				align-items: center !important;
			}

			body.elmercado-child-theme.woocommerce-shop ul.products li.product {
				transition: transform 180ms ease, box-shadow 180ms ease !important;
			}

			body.elmercado-child-theme.woocommerce-shop ul.products li.product:hover {
				transform: translateY(-3px);
				box-shadow: 0 18px 42px rgba(13, 33, 27, 0.1) !important;
			}

			/* Blog: ningún medio vacío debe parecer una imagen rota o sin terminar. */
			body.elmercado-child-theme .emo-article-card__placeholder,
			body.elmercado-child-theme .emo-article-card__media:empty,
			body.elmercado-child-theme .emo-article-card__image:empty {
				position: relative !important;
				display: flex !important;
				min-height: 260px !important;
				align-items: flex-end !important;
				padding: clamp(1.35rem, 3vw, 2rem) !important;
				background:
					radial-gradient(circle at 82% 22%, transparent 0 18%, rgba(215, 168, 79, 0.16) 18.4% 19%, transparent 19.4% 28%, rgba(215, 168, 79, 0.1) 28.4% 29%, transparent 29.4%),
					linear-gradient(135deg, #173f32, #0d2b22 72%, #21483a) !important;
			}

			body.elmercado-child-theme .emo-article-card__placeholder::before,
			body.elmercado-child-theme .emo-article-card__media:empty::before,
			body.elmercado-child-theme .emo-article-card__image:empty::before {
				content: "El cuaderno de origen" !important;
				max-width: 11ch;
				color: #fffdf8;
				font-family: Georgia, "Times New Roman", serif;
				font-size: clamp(1.45rem, 3vw, 2.2rem);
				font-weight: 700;
				letter-spacing: -0.035em;
				line-height: 1.02;
			}

			body.elmercado-child-theme .emo-article-card img {
				display: block;
				width: 100%;
				height: 100%;
				object-fit: cover;
			}

			/* Productores: introducción más compacta y transición clara al buscador. */
			body.elmercado-child-theme.wcfm-store-list-page .emo-producers-intro {
				min-height: 0 !important;
				margin-bottom: clamp(1.4rem, 3vw, 2.25rem) !important;
				padding-top: clamp(1rem, 2vw, 1.75rem) !important;
				padding-bottom: clamp(1.25rem, 3vw, 2.25rem) !important;
			}

			body.elmercado-child-theme.wcfm-store-list-page #wcfmmp-stores-wrap {
				margin-top: 0 !important;
			}

			body.elmercado-child-theme #wcfmmp-stores-wrap .wcfmmp-store-search-form,
			body.elmercado-child-theme #wcfmmp-stores-wrap .store-lists-sorting {
				box-shadow: 0 8px 24px rgba(13, 33, 27, 0.055) !important;
			}

			body.elmercado-child-theme #wcfmmp-stores-wrap .wcfmmp-single-store {
				transition: transform 180ms ease, box-shadow 180ms ease !important;
			}

			body.elmercado-child-theme #wcfmmp-stores-wrap .wcfmmp-single-store:hover {
				transform: translateY(-3px);
				box-shadow: 0 18px 42px rgba(13, 33, 27, 0.09) !important;
			}

			/* Mi cuenta sin sesión: composición compacta, equilibrada y sin vacío artificial. */
			body.elmercado-child-theme.woocommerce-account:not(.logged-in) .site-content,
			body.elmercado-child-theme.woocommerce-account:not(.logged-in) #content {
				min-height: 0 !important;
				padding-bottom: clamp(2.5rem, 6vw, 5rem) !important;
			}

			body.elmercado-child-theme.woocommerce-account:not(.logged-in) .woocommerce {
				max-width: 1160px;
				margin-inline: auto;
			}

			body.elmercado-child-theme.woocommerce-account:not(.logged-in) #customer_login {
				align-items: start !important;
				gap: clamp(1.25rem, 3vw, 2.5rem) !important;
			}

			body.elmercado-child-theme.woocommerce-account:not(.logged-in) #customer_login > div {
				margin-bottom: 0 !important;
			}

			body.elmercado-child-theme.woocommerce-account:not(.logged-in) .site-footer {
				margin-top: 0 !important;
			}

			/* Páginas editoriales: lectura más refinada y menos masa blanca. */
			body.elmercado-child-theme .emo-about-story,
			body.elmercado-child-theme .emo-editorial-card,
			body.elmercado-child-theme .entry-content > .wp-block-group.has-background {
				box-shadow: 0 14px 36px rgba(13, 33, 27, 0.065) !important;
			}

			body.elmercado-child-theme .emo-about-story p,
			body.elmercado-child-theme .entry-content > .wp-block-group p {
				max-width: 74ch;
			}

			/* Pie: conservar densidad y alinear contenido/pagos de forma estable. */
			body.elmercado-child-theme .site-footer .woostify-container,
			body.elmercado-child-theme .site-footer .container {
				align-items: center;
			}

			body.elmercado-child-theme .site-footer img {
				max-height: 42px;
				width: auto;
			}

			/* Evita que avisos flotantes tapen productos o controles importantes. */
			body.elmercado-child-theme .woocommerce-store-notice,
			body.elmercado-child-theme .woocommerce-message[role="alert"] {
				max-width: min(420px, calc(100vw - 28px));
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme.woocommerce-shop .emo-vendor-filter {
					grid-template-columns: minmax(0, 1fr) !important;
					align-items: stretch !important;
				}

				body.elmercado-child-theme.woocommerce-shop .emo-vendor-filter label {
					min-width: 0 !important;
					white-space: normal !important;
				}

				body.elmercado-child-theme.wcfm-store-list-page .emo-producers-intro {
					padding-top: 0.5rem !important;
					padding-bottom: 1.25rem !important;
				}

				body.elmercado-child-theme.woocommerce-account:not(.logged-in) #customer_login {
					display: grid !important;
					grid-template-columns: minmax(0, 1fr) !important;
				}

				body.elmercado-child-theme .emo-article-card__placeholder,
				body.elmercado-child-theme .emo-article-card__media:empty,
				body.elmercado-child-theme .emo-article-card__image:empty {
					min-height: 210px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
