<?php
/**
 * Directorio de productores: tarjetas editoriales estables y banners panorámicos.
 *
 * El nombre histórico del módulo se conserva para no alterar la carga del tema.
 * No modifica la tienda individual de cada vendedor ni las fichas de producto.
 * Los estilos se imprimen en HEAD, antes del primer renderizado, y la geometría
 * no depende de JavaScript, temporizadores ni imágenes que carguen después.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! ( is_page( array( 'productores', 'producers' ) ) || ( function_exists( 'wcfmmp_is_store_list_page' ) && wcfmmp_is_store_list_page() ) ) ) {
			return;
		}
		?>
		<style id="elmercado-producer-directory-editorial-20261020">
			/* Encabezado editorial integrado con la identidad del marketplace. */
			html body.elmercado-producers-page .emo-producers-intro,
			html body.wcfmmp-store-list-page .emo-producers-intro,
			html body.wcfm-store-list-page .emo-producers-intro {
				position: relative !important;
				display: flex !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: 1440px !important;
				min-height: 0 !important;
				flex-direction: column !important;
				align-items: flex-start !important;
				justify-content: center !important;
				gap: clamp(12px, 1.6vw, 20px) !important;
				margin: 0 auto clamp(28px, 4vw, 52px) !important;
				padding: clamp(30px, 5vw, 70px) clamp(24px, 5vw, 68px) !important;
				border: 1px solid rgba(23, 63, 50, .10) !important;
				border-radius: 28px !important;
				background: radial-gradient(ellipse at 95% 10%, rgba(211, 202, 168, .42), transparent 52%), linear-gradient(125deg, #faf8f2 0%, #f5f1e7 100%) !important;
				overflow: hidden !important;
			}
			html body.elmercado-producers-page .emo-producers-intro .emo-kicker,
			html body.wcfmmp-store-list-page .emo-producers-intro .emo-kicker,
			html body.wcfm-store-list-page .emo-producers-intro .emo-kicker {
				position: relative !important;
				z-index: 1 !important;
				color: #56745c !important;
				letter-spacing: .16em !important;
				}
			html body.elmercado-producers-page .emo-producers-intro h2,
			html body.wcfmmp-store-list-page .emo-producers-intro h2,
			html body.wcfm-store-list-page .emo-producers-intro h2 {
				position: relative !important;
				z-index: 1 !important;
				max-width: 850px !important;
				margin: 0 !important;
				color: #173f32 !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(2rem, 3.8vw, 3.6rem) !important;
				font-weight: 600 !important;
				letter-spacing: -.035em !important;
				line-height: 1.13 !important;
				text-wrap: balance;
			}
			html body.elmercado-producers-page .emo-producers-intro p,
			html body.wcfmmp-store-list-page .emo-producers-intro p,
			html body.wcfm-store-list-page .emo-producers-intro p {
				position: relative !important;
				z-index: 1 !important;
				width: 100% !important;
				max-width: 720px !important;
				margin: 0 !important;
				color: #56635b !important;
				font-size: clamp(.94rem, 1.25vw, 1.075rem) !important;
				line-height: 1.7 !important;
			}
			/* WCFM aplica a veces offsets de masonry: Grid y CSS los anulan desde el inicio. */
			html body.elmercado-child-theme.elmercado-producers-page #wcfmmp-stores-wrap ul.wcfmmp-store-wrap,
			html body.elmercado-child-theme.wcfmmp-store-list-page #wcfmmp-stores-wrap ul.wcfmmp-store-wrap,
			html body.elmercado-child-theme.wcfm-store-list-page #wcfmmp-stores-wrap ul.wcfmmp-store-wrap {
				display: grid !important;
				grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
				gap: clamp(18px, 2.4vw, 30px) !important;
				width: 100% !important;
				max-width: 1440px !important;
				align-items: stretch !important;
				margin: 0 auto !important;
				padding: 0 !important;
				list-style: none !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap ul.wcfmmp-store-wrap > li.wcfmmp-single-store {
				position: relative !important;
				top: auto !important;
				right: auto !important;
				bottom: auto !important;
				left: auto !important;
				display: block !important;
				box-sizing: border-box !important;
				grid-column: auto !important;
				grid-row: auto !important;
				float: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				height: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: none !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper {
				position: relative !important;
				display: flex !important;
				box-sizing: border-box !important;
				grid-template-columns: none !important;
				flex-direction: column !important;
				align-items: stretch !important;
				float: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				height: 100% !important;
				min-height: 0 !important;
				margin: 0 !important;
				border: 1px solid rgba(23,63,50,.10) !important;
				border-radius: 20px !important;
				background: #fffdf9 !important;
				box-shadow: 0 10px 30px rgba(23,63,50,.065) !important;
				overflow: hidden !important;
				transform: none !important;
				transition: box-shadow .2s ease, border-color .2s ease !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper:hover {
				border-color: rgba(23,63,50,.23) !important;
				box-shadow: 0 17px 38px rgba(23,63,50,.105) !important;
				transform: none !important;
			}
			/* Aspect ratio reserva el espacio exacto incluso mientras el banner carga. */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-content {
				position: relative !important;
				display: block !important;
				box-sizing: border-box !important;
				flex: 0 0 auto !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				aspect-ratio: 16 / 9 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: #efede6 !important;
				overflow: hidden !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-content .store-info {
				position: absolute !important;
				inset: 0 !important;
				display: block !important;
				box-sizing: border-box !important;
				width: 100% !important;
				height: 100% !important;
				min-height: 0 !important;
				max-height: none !important;
				margin: 0 !important;
				padding: 0 !important;
				background-color: #efede6 !important;
				background-size: contain !important; /* Logos existentes sin recortar; los nuevos banners 16:9 llenarán el marco. */
				background-position: center center !important;
				background-repeat: no-repeat !important;
				transform: none !important;
			}
			/* Nombre y llamada a la acción nunca compiten por el mismo ancho. */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-footer {
				position: relative !important;
				display: flex !important;
				box-sizing: border-box !important;
				flex: 1 1 auto !important;
				flex-direction: column !important;
				align-items: stretch !important;
				justify-content: flex-start !important;
				gap: 14px !important;
				width: 100% !important;
				height: auto !important;
				min-height: 146px !important;
				margin: 0 !important;
				padding: clamp(19px, 2.3vw, 26px) !important;
				border: 0 !important;
				border-top: 1px solid rgba(23,63,50,.07) !important;
				background: #fffdf9 !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data-container,
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data {
				position: static !important;
				display: block !important;
				box-sizing: border-box !important;
				flex: 0 0 auto !important;
				float: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				margin: 0 !important;
				padding: 0 !important;
				text-align: left !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data h2,
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data h2 a {
				display: block !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #1c352b !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(1.4rem, 2vw, 1.85rem) !important;
				font-weight: 600 !important;
				letter-spacing: -.025em !important;
				line-height: 1.23 !important;
				text-align: left !important;
				white-space: normal !important;
				word-break: normal !important;
				overflow-wrap: anywhere !important;
				text-overflow: clip !important;
				overflow: visible !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store :is(.store-avatar,.bd_rating,.store-enquiry,.wcfm_catalog_enquiry) {
				display: none !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store a.wcfmmp-visit-store {
				position: relative !important;
				display: inline-flex !important;
				box-sizing: border-box !important;
				flex: 0 0 auto !important;
				align-items: center !important;
				justify-content: space-between !important;
				gap: 10px !important;
				align-self: stretch !important;
				float: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				height: auto !important;
				min-height: 48px !important;
				margin: auto 0 0 !important;
				padding: 12px 17px !important;
				border: 1px solid #28513d !important;
				border-radius: 11px !important;
				background: #28513d !important;
				color: #fff !important;
				font-size: .9rem !important;
				font-weight: 600 !important;
				line-height: 1.35 !important;
				letter-spacing: 0 !important;
				text-align: left !important;
				text-decoration: none !important;
				white-space: normal !important;
				}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store a.wcfmmp-visit-store::after {
				content: "→";
				display: inline-block;
				flex: 0 0 auto;
				font-size: 1.2rem;
				line-height: 1;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store a.wcfmmp-visit-store:hover,
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store a.wcfmmp-visit-store:focus-visible {
				background: #173f32 !important;
				border-color: #173f32 !important;
				color: #fff !important;
				outline-offset: 3px !important;
			}
			@media (max-width: 1120px) {
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap ul.wcfmmp-store-wrap {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}
			}
			@media (max-width: 680px) {
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap ul.wcfmmp-store-wrap {
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 18px !important;
				}
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-footer {
					min-height: 0 !important;
					padding: 20px !important;
				}
				html body:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) .emo-producers-intro {
					padding: 28px 22px !important;
					border-radius: 20px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
