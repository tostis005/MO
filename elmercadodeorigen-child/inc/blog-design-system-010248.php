<?php
/**
 * Sistema visual final del blog 0.10.248.
 *
 * Unifica el ancho del archivo y de las entradas, mejora la jerarquía visual
 * de las tarjetas y convierte los productos incrustados en un bloque editorial
 * amplio pero contenido, sin desbordar el lienzo en escritorio o móvil.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! ( is_home() || is_archive() || is_singular( 'post' ) ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-design-system-010248">
			/* ---------------------------------------------------------
			 * 0.10.248 · Un solo lienzo editorial
			 * --------------------------------------------------------- */
			body.elmercado-editorial-content {
				--emo-blog-shell: 1180px;
				--emo-blog-reading: 800px;
				--emo-blog-wide: 1040px;
				--emo-blog-card-radius: 20px;
			}

			body.elmercado-editorial-content :is(.emo-journal, .emo-article-page),
			body.single-post.elmercado-child-theme .emo-article-page {
				width: min(var(--emo-blog-shell), calc(100% - 48px)) !important;
				max-width: var(--emo-blog-shell) !important;
				margin: clamp(22px, 3vw, 38px) auto clamp(36px, 6vw, 76px) !important;
				background: transparent !important;
				box-sizing: border-box;
			}

			body.elmercado-editorial-content :is(.emo-journal, .emo-article-page) > :is(.emo-journal-hero, .emo-article),
			body.single-post.elmercado-child-theme .emo-article-page > .emo-article {
				width: 100% !important;
				max-width: none !important;
			}

			body.elmercado-editorial-content :is(.emo-journal-hero, .emo-article-hero) {
				width: 100% !important;
				max-width: none !important;
				margin-inline: auto !important;
				border: 1px solid rgba(255, 255, 255, 0.08);
				border-radius: 26px;
				box-shadow: 0 20px 55px rgba(13, 33, 27, 0.13);
			}

			body.elmercado-editorial-content :is(.emo-journal-hero__inner, .emo-article-hero__inner) {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: clamp(58px, 7vw, 92px) clamp(34px, 6vw, 78px) !important;
				box-sizing: border-box;
			}

			body.elmercado-editorial-content :is(.emo-journal-hero, .emo-article-hero) h1 {
				max-width: 900px !important;
				font-size: clamp(46px, 6.2vw, 78px) !important;
				line-height: 0.98 !important;
				letter-spacing: -0.055em !important;
				text-wrap: balance;
			}

			body.elmercado-editorial-content :is(.emo-journal-hero p, .emo-article-hero__lead) {
				max-width: 690px !important;
				font-size: clamp(16px, 1.45vw, 19px) !important;
				line-height: 1.68 !important;
			}

			/* ---------------------------------------------------------
			 * Archivo: la cabecera y las entradas comparten exactamente
			 * los mismos bordes exteriores.
			 * --------------------------------------------------------- */
			body.elmercado-editorial-content .emo-journal-listing {
				width: 100% !important;
				padding: clamp(46px, 6vw, 76px) 0 0 !important;
			}

			body.elmercado-editorial-content .emo-journal-listing > .emo-shell,
			body.single-post.elmercado-child-theme .emo-related-reading > .emo-shell {
				width: 100% !important;
				max-width: none !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
				box-sizing: border-box;
			}

			body.elmercado-editorial-content .emo-journal-toolbar {
				align-items: flex-end;
				margin-bottom: clamp(24px, 3vw, 36px) !important;
				padding: 0 2px;
			}

			body.elmercado-editorial-content .emo-journal-toolbar h2 {
				max-width: 780px;
				font-size: clamp(32px, 4vw, 52px) !important;
				line-height: 1.04;
				text-wrap: balance;
			}

			body.elmercado-editorial-content .emo-journal-grid {
				display: grid !important;
				width: 100% !important;
				max-width: none !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				gap: 26px !important;
				margin: 0 !important;
			}

			body.elmercado-editorial-content .emo-article-card {
				min-width: 0;
				border-radius: var(--emo-blog-card-radius) !important;
				border-color: rgba(13, 33, 27, 0.1) !important;
				box-shadow: 0 10px 30px rgba(13, 33, 27, 0.055) !important;
			}

			body.elmercado-editorial-content .emo-article-card:hover {
				box-shadow: 0 18px 42px rgba(13, 33, 27, 0.11) !important;
				transform: translateY(-4px) !important;
			}

			body.elmercado-editorial-content .emo-article-card:not(.emo-article-card--featured) .emo-article-card__media {
				min-height: 0 !important;
				aspect-ratio: 16 / 9;
			}

			body.elmercado-editorial-content .emo-article-card:not(.emo-article-card--featured) .emo-article-card__media :is(img, .emo-article-card__placeholder) {
				min-height: 0 !important;
				aspect-ratio: 16 / 9;
			}

			body.elmercado-editorial-content .emo-article-card__body {
				padding: clamp(22px, 2.7vw, 34px) !important;
			}

			body.elmercado-editorial-content .emo-article-card:not(.emo-article-card--featured) h2 {
				font-size: clamp(25px, 2.4vw, 34px) !important;
				line-height: 1.08 !important;
				letter-spacing: -0.035em;
				text-wrap: balance;
			}

			body.elmercado-editorial-content .emo-article-card:not(.emo-article-card--featured) p {
				font-size: 15px;
				line-height: 1.68;
			}

			body.elmercado-editorial-content .emo-article-card--featured {
				grid-template-columns: minmax(0, 1.1fr) minmax(340px, 0.9fr) !important;
				min-height: 430px !important;
			}

			body.elmercado-editorial-content .emo-article-card--featured h2 {
				font-size: clamp(34px, 4.2vw, 55px) !important;
				line-height: 1.02 !important;
				letter-spacing: -0.045em;
			}

			/* ---------------------------------------------------------
			 * Entrada: lectura estrecha y cómoda dentro del mismo shell.
			 * --------------------------------------------------------- */
			body.single-post.elmercado-child-theme .emo-article-main {
				width: 100% !important;
				padding: clamp(34px, 5vw, 64px) 0 0 !important;
			}

			body.single-post.elmercado-child-theme .emo-article-main > .emo-shell {
				width: 100% !important;
				max-width: none !important;
				margin-inline: auto !important;
				padding-inline: 0 !important;
				box-sizing: border-box;
			}

			body.single-post.elmercado-child-theme .emo-article-featured {
				width: min(100%, 1080px) !important;
				max-width: 1080px !important;
				margin: 0 auto clamp(32px, 5vw, 54px) !important;
				border-radius: 22px !important;
				box-shadow: 0 16px 42px rgba(13, 33, 27, 0.1) !important;
			}

			body.single-post.elmercado-child-theme .emo-article-featured img {
				width: 100% !important;
				max-width: 100% !important;
				max-height: 610px !important;
				aspect-ratio: 16 / 9;
				object-fit: cover;
			}

			body.single-post.elmercado-child-theme .emo-article-content {
				width: min(100%, var(--emo-blog-reading)) !important;
				max-width: var(--emo-blog-reading) !important;
				margin-inline: auto !important;
				padding: clamp(28px, 4.2vw, 52px) !important;
				border-radius: 22px !important;
				box-shadow: 0 12px 34px rgba(13, 33, 27, 0.06) !important;
				box-sizing: border-box;
			}

			body.single-post.elmercado-child-theme .emo-article-content > :is(p, ul, ol) {
				color: #38443f;
				font-size: clamp(16px, 1.1vw, 18px) !important;
				line-height: 1.82 !important;
			}

			body.single-post.elmercado-child-theme .emo-article-content > p:first-of-type {
				color: var(--emo-forest-950, #0d211b);
				font-size: clamp(19px, 1.7vw, 23px) !important;
				line-height: 1.62 !important;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(h2, h3, h4, h5, h6) {
				overflow-wrap: break-word;
				text-wrap: balance;
			}

			body.single-post.elmercado-child-theme .emo-article-content h2 {
				margin-top: clamp(42px, 6vw, 70px) !important;
				font-size: clamp(31px, 3.2vw, 44px) !important;
				line-height: 1.08 !important;
				letter-spacing: -0.035em;
			}

			body.single-post.elmercado-child-theme .emo-article-content h3 {
				margin-top: 38px !important;
				font-size: clamp(25px, 2.5vw, 34px) !important;
				line-height: 1.12 !important;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(img, video, iframe) {
				max-width: 100%;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(table, pre) {
				max-width: 100%;
				overflow-x: auto;
			}

			/* ---------------------------------------------------------
			 * Productos dentro de artículos.
			 * En escritorio se permite una apertura editorial de 1040 px,
			 * siempre centrada y 70 px por dentro del shell de 1180 px.
			 * --------------------------------------------------------- */
			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
				width: min(var(--emo-blog-wide), calc(100vw - 80px)) !important;
				max-width: var(--emo-blog-wide) !important;
				margin: clamp(34px, 5vw, 52px) 50% !important;
				padding: 0 !important;
				transform: translateX(-50%) !important;
				overflow: visible !important;
				box-sizing: border-box;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
				display: grid !important;
				width: 100% !important;
				max-width: 100% !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
				box-sizing: border-box;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template)::before,
			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template)::after {
				display: none !important;
				content: none !important;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products > li.product, ul.products > li.product, .wc-block-grid__product, .wc-block-product) {
				display: flex !important;
				float: none !important;
				clear: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				height: 100%;
				flex-direction: column;
				margin: 0 !important;
				padding: 13px 13px 16px !important;
				background: #fff !important;
				border: 1px solid rgba(13, 33, 27, 0.105) !important;
				border-radius: 18px !important;
				box-shadow: 0 9px 26px rgba(13, 33, 27, 0.065) !important;
				box-sizing: border-box;
				overflow: hidden;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products > li.product, ul.products > li.product, .wc-block-grid__product, .wc-block-product) :is(a, .woocommerce-loop-product__link) {
				max-width: 100%;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products > li.product, ul.products > li.product, .wc-block-grid__product, .wc-block-product) img {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				height: auto !important;
				aspect-ratio: 4 / 3;
				margin: 0 0 14px !important;
				background: #f5f1ea;
				border-radius: 13px !important;
				object-fit: cover;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce-loop-product__title, .wc-block-grid__product-title, .wc-block-components-product-name, .wp-block-post-title) {
				margin: 2px 0 9px !important;
				color: var(--emo-forest-950, #0d211b) !important;
				font-family: var(--emo-font-sans, Poppins, Arial, sans-serif) !important;
				font-size: 15px !important;
				font-weight: 700 !important;
				line-height: 1.38 !important;
				letter-spacing: -0.01em !important;
				overflow-wrap: anywhere;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.price, .wc-block-grid__product-price, .wc-block-components-product-price) {
				margin: auto 0 13px !important;
				color: var(--emo-clay-dark, #a84f35) !important;
				font-size: 16px !important;
				font-weight: 800 !important;
				line-height: 1.35;
			}

			body.single-post.elmercado-child-theme .emo-article-content :is(.button, .wp-block-button__link, .wc-block-grid__product-add-to-cart a) {
				display: inline-flex !important;
				width: 100% !important;
				min-height: 42px;
				align-items: center;
				justify-content: center;
				margin: auto 0 0 !important;
				padding: 10px 14px !important;
				border-radius: 999px !important;
				font-size: 12px !important;
				font-weight: 800 !important;
				line-height: 1.2 !important;
				box-sizing: border-box;
			}

			/* Pie de artículo y recomendaciones dentro del mismo sistema. */
			body.single-post.elmercado-child-theme .emo-article-footer {
				width: min(100%, var(--emo-blog-reading)) !important;
				max-width: var(--emo-blog-reading) !important;
				margin: 18px auto 0 !important;
				padding: 18px 3px !important;
			}

			body.single-post.elmercado-child-theme .emo-related-reading {
				width: 100% !important;
				max-width: none !important;
				margin: clamp(48px, 7vw, 84px) 0 0 !important;
				padding: clamp(40px, 5vw, 62px) clamp(28px, 4vw, 46px) !important;
				background: #efe8dc !important;
				border-radius: 24px;
				box-sizing: border-box;
			}

			body.single-post.elmercado-child-theme .emo-related-reading .emo-journal-grid,
			body.single-post.elmercado-child-theme .emo-related-grid {
				display: grid !important;
				width: min(100%, var(--emo-blog-wide)) !important;
				max-width: var(--emo-blog-wide) !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin-inline: auto !important;
			}

			body.single-post.elmercado-child-theme .emo-related-reading__heading {
				width: min(100%, var(--emo-blog-wide));
				margin: 0 auto 28px !important;
			}

			body.single-post.elmercado-child-theme .emo-related-reading .emo-article-card__media {
				min-height: 0 !important;
				aspect-ratio: 16 / 10;
			}

			body.single-post.elmercado-child-theme .emo-related-reading .emo-article-card__media :is(img, .emo-article-card__placeholder) {
				min-height: 0 !important;
				aspect-ratio: 16 / 10;
			}

			body.single-post.elmercado-child-theme .emo-related-reading .emo-article-card__body {
				padding: 20px !important;
			}

			body.single-post.elmercado-child-theme .emo-related-reading .emo-article-card h2 {
				font-size: clamp(22px, 2vw, 28px) !important;
				line-height: 1.12 !important;
			}

			body.single-post.elmercado-child-theme .emo-related-reading .emo-article-card p {
				font-size: 14px;
				line-height: 1.6;
			}

			/* ---------------------------------------------------------
			 * Responsive: sin saltos de ancho ni scroll horizontal.
			 * --------------------------------------------------------- */
			@media (max-width: 1100px) {
				body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
					width: 100% !important;
					max-width: 100% !important;
					margin: 34px 0 !important;
					transform: none !important;
				}

				body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template),
				body.single-post.elmercado-child-theme .emo-related-reading .emo-journal-grid,
				body.single-post.elmercado-child-theme .emo-related-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 820px) {
				body.elmercado-editorial-content :is(.emo-journal, .emo-article-page),
				body.single-post.elmercado-child-theme .emo-article-page {
					width: calc(100% - 32px) !important;
					margin-top: 16px !important;
			}

				body.elmercado-editorial-content :is(.emo-journal-hero, .emo-article-hero) {
					border-radius: 20px;
				}

				body.elmercado-editorial-content :is(.emo-journal-hero__inner, .emo-article-hero__inner) {
					padding: 44px 26px 48px !important;
				}

				body.elmercado-editorial-content :is(.emo-journal-hero, .emo-article-hero) h1 {
					font-size: clamp(40px, 11vw, 62px) !important;
				}

				body.elmercado-editorial-content .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 18px !important;
				}

				body.elmercado-editorial-content .emo-article-card--featured {
					grid-column: auto !important;
					display: flex !important;
					min-height: 0 !important;
					flex-direction: column;
				}

				body.elmercado-editorial-content .emo-article-card--featured .emo-article-card__media {
					min-height: 0 !important;
					aspect-ratio: 16 / 9;
				}

				body.single-post.elmercado-child-theme .emo-article-content {
					padding: 26px 22px !important;
					border-radius: 18px !important;
				}

				body.single-post.elmercado-child-theme .emo-related-reading {
					padding: 32px 20px !important;
					border-radius: 20px;
				}

				body.single-post.elmercado-child-theme .emo-related-reading__heading,
				body.elmercado-editorial-content .emo-journal-toolbar {
					align-items: flex-start;
					flex-direction: column;
				}
			}

			@media (max-width: 620px) {
				body.single-post.elmercado-child-theme .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template),
				body.single-post.elmercado-child-theme .emo-related-reading .emo-journal-grid,
				body.single-post.elmercado-child-theme .emo-related-grid {
					grid-template-columns: minmax(0, 1fr) !important;
				}

				body.single-post.elmercado-child-theme .emo-article-footer {
					align-items: flex-start;
					flex-direction: column;
				}

				body.single-post.elmercado-child-theme .emo-article-navigation {
					width: 100%;
					justify-content: space-between;
				}
			}

			@media (max-width: 420px) {
				body.elmercado-editorial-content :is(.emo-journal, .emo-article-page),
				body.single-post.elmercado-child-theme .emo-article-page {
					width: calc(100% - 20px) !important;
				}

				body.elmercado-editorial-content :is(.emo-journal-hero__inner, .emo-article-hero__inner) {
					padding-inline: 20px !important;
				}

				body.single-post.elmercado-child-theme .emo-article-content {
					padding: 24px 18px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
