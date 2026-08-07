<?php
/**
 * Unificación de cabeceras interiores, gutters y metadatos editoriales 0.10.43.
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
		<style id="elmercado-content-header-unification-01043">
			body.elmercado-child-theme {
				--emo-page-gutter: clamp(16px, 2.5vw, 32px);
				--emo-content-max: 1180px;
				--emo-content-card-radius: 20px;
			}

			/* Un único gutter común en páginas interiores. */
			body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) .site-content > .woostify-container,
			body.elmercado-child-theme:not(.home):not(.elmercado-editorial-content) #content > .woostify-container {
				width: 100% !important;
				max-width: calc(var(--emo-content-max) + (2 * var(--emo-page-gutter))) !important;
				margin-inline: auto !important;
				padding-inline: var(--emo-page-gutter) !important;
			}

			/* El blog necesita un lienzo completo; el gutter vive en sus shells interiores. */
			body.elmercado-child-theme.elmercado-editorial-content .site-content > .woostify-container,
			body.elmercado-child-theme.elmercado-editorial-content #content > .woostify-container {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
				padding-inline: 0 !important;
			}
			body.elmercado-child-theme.elmercado-editorial-content :is(#primary,.content-area,.site-main) {
				width: 100% !important;
				max-width: none !important;
				margin-inline: 0 !important;
				float: none !important;
			}

			/* Blog y artículos: mismo ancho útil y mismo margen lateral. */
			body.elmercado-child-theme :is(
				.emo-journal-hero__inner,
				.emo-journal-listing > .emo-shell,
				.emo-article-hero__inner,
				.emo-article-main > .emo-shell,
				.emo-related-reading > .emo-shell
			) {
				width: min(var(--emo-content-max), calc(100% - (2 * var(--emo-page-gutter)))) !important;
				max-width: var(--emo-content-max) !important;
				margin-inline: auto !important;
			}

			/* Productores y Contacto ya viven dentro del gutter exterior: no sumar otro. */
			body.elmercado-child-theme .emo-producers-intro,
			body.elmercado-child-theme .emo-contact-layout {
				width: 100% !important;
				max-width: var(--emo-content-max) !important;
				margin-inline: auto !important;
			}

			/* Un único lenguaje de cabecera: tarjeta verde, redondeada y sin fondos exteriores. */
			body.elmercado-child-theme .emo-journal-hero {
				padding: 22px 0 0 !important;
				overflow: visible !important;
				background: transparent !important;
				color: #fff !important;
			}
			body.elmercado-child-theme .emo-journal-hero::before,
			body.elmercado-child-theme .emo-journal-hero::after {
				display: none !important;
			}
			body.elmercado-child-theme .emo-journal-hero__inner,
			body.elmercado-child-theme .emo-producers-intro,
			body.elmercado-child-theme .emo-contact-aside {
				box-sizing: border-box !important;
				padding: clamp(28px, 4.5vw, 54px) !important;
				border: 0 !important;
				border-radius: var(--emo-content-card-radius) !important;
				background: #173f32 !important;
				box-shadow: none !important;
				color: rgba(255,255,255,.82) !important;
			}

			body.elmercado-child-theme .emo-journal-hero__inner {
				min-height: 0 !important;
			}
			body.elmercado-child-theme .emo-producers-intro {
				display: block !important;
				margin-top: 22px !important;
				margin-bottom: 20px !important;
			}
			body.elmercado-child-theme .emo-contact-layout {
				display: grid !important;
				grid-template-columns: minmax(0, 1fr) !important;
				gap: 20px !important;
				margin-top: 22px !important;
			}
			body.elmercado-child-theme .emo-contact-aside {
				width: 100% !important;
				max-width: none !important;
				margin: 0 !important;
			}
			body.elmercado-child-theme .emo-contact-form {
				width: min(100%, 900px) !important;
				margin-inline: auto !important;
			}

			/* Kicker y tipografía de las tres cabeceras con la misma escala. */
			body.elmercado-child-theme :is(.emo-journal-hero__inner,.emo-producers-intro,.emo-contact-aside) .emo-kicker {
				margin: 0 0 18px !important;
				color: #fff !important;
				font-size: 11px !important;
				font-weight: 800 !important;
				letter-spacing: .14em !important;
				line-height: 1.2 !important;
			}
			body.elmercado-child-theme :is(.emo-journal-hero__inner,.emo-producers-intro,.emo-contact-aside) h1,
			body.elmercado-child-theme :is(.emo-journal-hero__inner,.emo-producers-intro,.emo-contact-aside) h2 {
				max-width: 820px !important;
				margin: 0 0 18px !important;
				color: #fff !important;
				font-size: clamp(34px, 5vw, 58px) !important;
				line-height: 1.03 !important;
				letter-spacing: -.035em !important;
			}
			body.elmercado-child-theme :is(.emo-journal-hero__inner,.emo-producers-intro,.emo-contact-aside) > p,
			body.elmercado-child-theme .emo-journal-hero__inner > p {
				max-width: 720px !important;
				margin: 0 !important;
				color: rgba(255,255,255,.78) !important;
				font-size: clamp(15px, 1.5vw, 17px) !important;
				line-height: 1.7 !important;
			}

			/* Sin fechas de publicación en tarjetas ni en fichas de artículo. */
			body.elmercado-child-theme .emo-article-card__meta > span:first-of-type,
			body.elmercado-child-theme .emo-article-hero__meta > span:first-of-type,
			body.elmercado-child-theme .posted-on,
			body.elmercado-child-theme .entry-date,
			body.elmercado-child-theme time.entry-date,
			body.elmercado-child-theme time.published {
				display: none !important;
			}

			/* Menos aire antes del contenido posterior a las cabeceras. */
			body.elmercado-child-theme .emo-journal-listing {
				padding-top: clamp(26px, 4vw, 44px) !important;
			}
			body.elmercado-child-theme.elmercado-compact-producers .site-content,
			body.elmercado-child-theme.elmercado-contact-page .site-content {
				padding-top: 0 !important;
			}

			@media (max-width: 767px) {
				body.elmercado-child-theme {
					--emo-page-gutter: 16px;
				}
				body.elmercado-child-theme .emo-journal-hero,
				body.elmercado-child-theme .emo-producers-intro,
				body.elmercado-child-theme .emo-contact-layout {
					margin-top: 0 !important;
				}
				body.elmercado-child-theme .emo-journal-hero {
					padding-top: 20px !important;
				}
				body.elmercado-child-theme .emo-journal-hero__inner,
				body.elmercado-child-theme .emo-producers-intro,
				body.elmercado-child-theme .emo-contact-aside {
					padding: 28px 26px !important;
				}
				body.elmercado-child-theme :is(.emo-journal-hero__inner,.emo-producers-intro,.emo-contact-aside) h1,
				body.elmercado-child-theme :is(.emo-journal-hero__inner,.emo-producers-intro,.emo-contact-aside) h2 {
					font-size: clamp(34px, 10vw, 44px) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
