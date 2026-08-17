<?php
/**
 * Capa geométrica final del blog 0.10.250.
 *
 * No depende de clases históricas del body: el propio PHP limita su salida a
 * vistas editoriales y los selectores se anclan a los IDs/clases reales de
 * home.php, archive.php y single.php.
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
		<style id="elmercado-blog-design-force-010250">
			html body main#primary.emo-journal,
			html body main#primary.emo-article-page {
				--emo-blog-shell-final: 1180px;
				--emo-blog-reading-final: 800px;
				--emo-blog-wide-final: 1040px;
				display: block !important;
				width: min(var(--emo-blog-shell-final), calc(100% - 48px)) !important;
				max-width: var(--emo-blog-shell-final) !important;
				margin: clamp(22px, 3vw, 38px) auto clamp(38px, 6vw, 76px) !important;
				padding: 0 !important;
				box-sizing: border-box !important;
				overflow: visible !important;
			}

			html body main#primary.emo-journal > .emo-journal-hero,
			html body main#primary.emo-article-page .emo-article-hero {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 auto !important;
				border: 1px solid rgba(255, 255, 255, 0.08) !important;
				border-radius: 26px !important;
				box-shadow: 0 20px 55px rgba(13, 33, 27, 0.13) !important;
				box-sizing: border-box !important;
				overflow: hidden !important;
			}

			html body main#primary.emo-journal .emo-journal-hero__inner,
			html body main#primary.emo-article-page .emo-article-hero__inner {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
				padding: clamp(58px, 7vw, 92px) clamp(34px, 6vw, 78px) !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal .emo-journal-hero h1,
			html body main#primary.emo-article-page .emo-article-hero h1 {
				max-width: 900px !important;
				font-size: clamp(46px, 6.2vw, 78px) !important;
				line-height: 0.98 !important;
				letter-spacing: -0.055em !important;
				text-wrap: balance;
			}

			html body main#primary.emo-journal .emo-journal-hero p,
			html body main#primary.emo-article-page .emo-article-hero__lead {
				max-width: 690px !important;
				font-size: clamp(16px, 1.45vw, 19px) !important;
				line-height: 1.68 !important;
			}

			/* Índice: la lista ocupa exactamente el mismo lienzo que la cabecera. */
			html body main#primary.emo-journal > .emo-journal-listing {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: clamp(46px, 6vw, 76px) 0 0 !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal .emo-journal-listing > .emo-shell {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal .emo-journal-toolbar {
				align-items: flex-end !important;
				margin-bottom: clamp(24px, 3vw, 36px) !important;
				padding: 0 2px !important;
			}

			html body main#primary.emo-journal .emo-journal-toolbar h2 {
				max-width: 780px !important;
				font-size: clamp(32px, 4vw, 52px) !important;
				line-height: 1.04 !important;
				text-wrap: balance;
			}

			html body main#primary.emo-journal .emo-journal-grid {
				display: grid !important;
				width: 100% !important;
				max-width: 100% !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				gap: 26px !important;
				margin: 0 !important;
				padding: 0 !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-journal .emo-article-card {
				min-width: 0 !important;
				border-radius: 20px !important;
				border-color: rgba(13, 33, 27, 0.1) !important;
				box-shadow: 0 10px 30px rgba(13, 33, 27, 0.055) !important;
				overflow: hidden !important;
			}

			html body main#primary.emo-journal .emo-article-card:hover {
				transform: translateY(-4px) !important;
				box-shadow: 0 18px 42px rgba(13, 33, 27, 0.11) !important;
			}

			html body main#primary.emo-journal .emo-article-card:not(.emo-article-card--featured) .emo-article-card__media,
			html body main#primary.emo-journal .emo-article-card:not(.emo-article-card--featured) .emo-article-card__media :is(img, .emo-article-card__placeholder) {
				min-height: 0 !important;
				aspect-ratio: 16 / 9 !important;
			}

			html body main#primary.emo-journal .emo-article-card__body {
				padding: clamp(22px, 2.7vw, 34px) !important;
			}

			html body main#primary.emo-journal .emo-article-card:not(.emo-article-card--featured) h2 {
				font-size: clamp(25px, 2.4vw, 34px) !important;
				line-height: 1.08 !important;
				letter-spacing: -0.035em !important;
				text-wrap: balance;
			}

			html body main#primary.emo-journal .emo-article-card--featured {
				grid-column: 1 / -1 !important;
				display: grid !important;
				grid-template-columns: minmax(0, 1.1fr) minmax(340px, 0.9fr) !important;
				min-height: 430px !important;
			}

			html body main#primary.emo-journal .emo-article-card--featured h2 {
				font-size: clamp(34px, 4.2vw, 55px) !important;
				line-height: 1.02 !important;
				letter-spacing: -0.045em !important;
				text-wrap: balance;
			}

			/* Entrada: lectura cómoda en 800 px dentro del mismo shell exterior. */
			html body main#primary.emo-article-page .emo-article-main {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: clamp(34px, 5vw, 64px) 0 0 !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-article-page .emo-article-main > .emo-shell {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-article-page .emo-article-featured {
				width: min(100%, 1080px) !important;
				max-width: 1080px !important;
				margin: 0 auto clamp(32px, 5vw, 54px) !important;
				border-radius: 22px !important;
				box-shadow: 0 16px 42px rgba(13, 33, 27, 0.1) !important;
				overflow: hidden !important;
			}

			html body main#primary.emo-article-page .emo-article-featured img {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				height: auto !important;
				max-height: 610px !important;
				aspect-ratio: 16 / 9 !important;
				object-fit: cover !important;
			}

			html body main#primary.emo-article-page .emo-article-content {
				width: min(100%, var(--emo-blog-reading-final)) !important;
				max-width: var(--emo-blog-reading-final) !important;
				margin: 0 auto !important;
				padding: clamp(28px, 4.2vw, 52px) !important;
				background: #fff !important;
				border-radius: 22px !important;
				box-shadow: 0 12px 34px rgba(13, 33, 27, 0.06) !important;
				box-sizing: border-box !important;
				overflow: visible !important;
			}

			html body main#primary.emo-article-page .emo-article-content > :is(p, ul, ol) {
				color: #38443f !important;
				font-size: clamp(16px, 1.1vw, 18px) !important;
				line-height: 1.82 !important;
			}

			html body main#primary.emo-article-page .emo-article-content > p:first-of-type {
				color: #0d211b !important;
				font-size: clamp(19px, 1.7vw, 23px) !important;
				line-height: 1.62 !important;
			}

			html body main#primary.emo-article-page .emo-article-content h2 {
				margin-top: clamp(42px, 6vw, 70px) !important;
				font-size: clamp(31px, 3.2vw, 44px) !important;
				line-height: 1.08 !important;
				letter-spacing: -0.035em !important;
				text-wrap: balance;
			}

			html body main#primary.emo-article-page .emo-article-content h3 {
				margin-top: 38px !important;
				font-size: clamp(25px, 2.5vw, 34px) !important;
				line-height: 1.12 !important;
				text-wrap: balance;
			}

			html body main#primary.emo-article-page .emo-article-content :is(img, video, iframe, table, pre) {
				max-width: 100% !important;
			}

			/* Productos: apertura editorial controlada, nunca a borde de viewport. */
			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
				width: min(var(--emo-blog-wide-final), calc(100vw - 80px)) !important;
				max-width: var(--emo-blog-wide-final) !important;
				margin: clamp(34px, 5vw, 52px) 50% !important;
				padding: 0 !important;
				transform: translateX(-50%) !important;
				box-sizing: border-box !important;
				overflow: visible !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
				display: grid !important;
				float: none !important;
				width: 100% !important;
				max-width: 100% !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template)::before,
			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template)::after {
				display: none !important;
				content: none !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products > li.product, ul.products > li.product, .wc-block-grid__product, .wc-block-product) {
				display: flex !important;
				float: none !important;
				clear: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				height: 100% !important;
				flex-direction: column !important;
				margin: 0 !important;
				padding: 13px 13px 16px !important;
				background: #fff !important;
				border: 1px solid rgba(13, 33, 27, 0.105) !important;
				border-radius: 18px !important;
				box-shadow: 0 9px 26px rgba(13, 33, 27, 0.065) !important;
				box-sizing: border-box !important;
				overflow: hidden !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products > li.product, ul.products > li.product, .wc-block-grid__product, .wc-block-product) img {
				display: block !important;
				width: 100% !important;
				max-width: 100% !important;
				height: auto !important;
				aspect-ratio: 4 / 3 !important;
				margin: 0 0 14px !important;
				background: #f5f1ea !important;
				border-radius: 13px !important;
				object-fit: cover !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.woocommerce-loop-product__title, .wc-block-grid__product-title, .wc-block-components-product-name, .wp-block-post-title) {
				margin: 2px 0 9px !important;
				color: #0d211b !important;
				font-size: 15px !important;
				font-weight: 700 !important;
				line-height: 1.38 !important;
				letter-spacing: -0.01em !important;
				overflow-wrap: anywhere !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.price, .wc-block-grid__product-price, .wc-block-components-product-price) {
				margin: auto 0 13px !important;
				color: #a84f35 !important;
				font-size: 16px !important;
				font-weight: 800 !important;
			}

			html body main#primary.emo-article-page .emo-article-content :is(.button, .wp-block-button__link, .wc-block-grid__product-add-to-cart a) {
				display: inline-flex !important;
				width: 100% !important;
				min-height: 42px !important;
				align-items: center !important;
				justify-content: center !important;
				margin: auto 0 0 !important;
				padding: 10px 14px !important;
				border-radius: 999px !important;
				font-size: 12px !important;
				font-weight: 800 !important;
				line-height: 1.2 !important;
				box-sizing: border-box !important;
			}

			/* Lecturas relacionadas: mismo borde exterior, tarjetas más compactas. */
			html body main#primary.emo-article-page > .emo-related-reading {
				width: 100% !important;
				max-width: 100% !important;
				margin: clamp(48px, 7vw, 84px) 0 0 !important;
				padding: clamp(40px, 5vw, 62px) clamp(28px, 4vw, 46px) !important;
				background: #efe8dc !important;
				border-radius: 24px !important;
				box-sizing: border-box !important;
			}

			html body main#primary.emo-article-page .emo-related-reading > .emo-shell {
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			html body main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
				display: grid !important;
				width: min(100%, var(--emo-blog-wide-final)) !important;
				max-width: var(--emo-blog-wide-final) !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: 20px !important;
				margin: 0 auto !important;
			}

			@media (max-width: 1100px) {
				html body main#primary.emo-article-page .emo-article-content :is(.woocommerce, .wp-block-woocommerce-product-collection, .wc-block-grid) {
					width: 100% !important;
					max-width: 100% !important;
					margin: 34px 0 !important;
					transform: none !important;
				}

				html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template),
				html body main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}

			@media (max-width: 820px) {
				html body main#primary.emo-journal,
				html body main#primary.emo-article-page {
					width: calc(100% - 32px) !important;
					margin-top: 16px !important;
				}

				html body main#primary.emo-journal > .emo-journal-hero,
				html body main#primary.emo-article-page .emo-article-hero {
					border-radius: 20px !important;
				}

				html body main#primary.emo-journal .emo-journal-hero__inner,
				html body main#primary.emo-article-page .emo-article-hero__inner {
					padding: 44px 26px 48px !important;
				}

				html body main#primary.emo-journal .emo-journal-hero h1,
				html body main#primary.emo-article-page .emo-article-hero h1 {
					font-size: clamp(40px, 11vw, 62px) !important;
				}

				html body main#primary.emo-journal .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 18px !important;
				}

				html body main#primary.emo-journal .emo-article-card--featured {
					grid-column: auto !important;
					display: flex !important;
					min-height: 0 !important;
					flex-direction: column !important;
				}

				html body main#primary.emo-journal .emo-article-card--featured .emo-article-card__media {
					min-height: 0 !important;
					aspect-ratio: 16 / 9 !important;
				}

				html body main#primary.emo-article-page .emo-article-content {
					padding: 26px 22px !important;
					border-radius: 18px !important;
				}
			}

			@media (max-width: 620px) {
				html body main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template),
				html body main#primary.emo-article-page .emo-related-reading .emo-journal-grid {
					grid-template-columns: minmax(0, 1fr) !important;
				}
			}

			@media (max-width: 420px) {
				html body main#primary.emo-journal,
				html body main#primary.emo-article-page {
					width: calc(100% - 20px) !important;
				}

				html body main#primary.emo-journal .emo-journal-hero__inner,
				html body main#primary.emo-article-page .emo-article-hero__inner {
					padding-inline: 20px !important;
				}

				html body main#primary.emo-article-page .emo-article-content {
					padding: 24px 18px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
