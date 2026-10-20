<?php
/**
 * Diseño editorial del directorio de productores (revisión panorámica 2026-10-20).
 *
 * Reutiliza las fotos de fondo de WCFM como banners cover, sin introducir logos.
 * La retícula, la reserva de tamaño y la ubicación del CTA son CSS de primera
 * pintura: no hay Observer, JS, reordenaciones ni saltos al cargar imágenes.
 * El nombre del archivo se mantiene por compatibilidad con functions.php.
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
			/* Editorial sobria: sin panel gigante ni decoración que compita con las fotos. */
			html body:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) .emo-producers-intro {
				display: flex !important;
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: 1320px !important;
				min-height: 0 !important;
				align-items: flex-start !important;
				flex-direction: column !important;
				justify-content: center !important;
				gap: 14px !important;
				margin: 0 auto clamp(24px, 3.2vw, 44px) !important;
				padding: clamp(16px, 2vw, 28px) 0 clamp(18px, 2.5vw, 32px) !important;
				border: 0 !important;
				border-bottom: 1px solid rgba(23, 63, 50, .14) !important;
				border-radius: 0 !important;
				background: transparent !important;
				box-shadow: none !important;
			}
			html body:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) .emo-producers-intro .emo-kicker {
				color: #69816a !important;
				font-size: .72rem !important;
				font-weight: 750 !important;
				letter-spacing: .16em !important;
				line-height: 1.5 !important;
			}
			html body:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) .emo-producers-intro h2 {
				max-width: 900px !important;
				margin: 0 !important;
				color: #173f32 !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(2rem, 3.5vw, 3.35rem) !important;
				font-weight: 600 !important;
				letter-spacing: -.036em !important;
				line-height: 1.12 !important;
				text-wrap: balance;
			}
			html body:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) .emo-producers-intro p {
				width: 100% !important;
				max-width: 760px !important;
				margin: 0 !important;
				color: #5a665e !important;
				font-size: clamp(.92rem, 1.15vw, 1.04rem) !important;
				line-height: 1.66 !important;
			}
			/* Dos columnas de banners amplios. WCFM no decide posiciones con masonry. */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap ul.wcfmmp-store-wrap {
				display: grid !important;
				box-sizing: border-box !important;
				grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				align-items: stretch !important;
				gap: clamp(22px, 2.8vw, 36px) !important;
				width: 100% !important;
				max-width: 1320px !important;
				margin: 0 auto !important;
				padding: 0 !important;
				list-style: none !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap ul.wcfmmp-store-wrap > li.wcfmmp-single-store {
				position: relative !important;
				inset: auto !important;
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
				align-items: stretch !important;
				flex-direction: column !important;
				float: none !important;
				width: 100% !important;
				min-width: 0 !important;
				max-width: none !important;
				height: 100% !important;
				min-height: 0 !important;
				margin: 0 !important;
				border: 1px solid rgba(29, 65, 47, .105) !important;
				border-radius: 18px !important;
				background: #fff !important;
				box-shadow: 0 5px 18px rgba(24, 47, 31, .045) !important;
				overflow: hidden !important;
				transform: none !important;
				transition: box-shadow .25s ease, border-color .25s ease !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper:hover {
				border-color: rgba(29, 65, 47, .22) !important;
				box-shadow: 0 12px 30px rgba(24, 47, 31, .08) !important;
				transform: none !important;
			}
			/* Banners fotográficos panorámicos ~2.6:1, sin efecto letterbox (contain). */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-content {
				position: relative !important;
				display: block !important;
				box-sizing: border-box !important;
				flex: 0 0 auto !important;
				width: 100% !important;
				height: auto !important;
				min-height: 0 !important;
				max-height: none !important;
				aspect-ratio: 2.6 / 1 !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: #e8e7df !important;
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
				border: 0 !important;
				background-color: #e8e7df !important;
				background-size: cover !important;
				background-position: center center !important;
				background-repeat: no-repeat !important;
				transform: none !important;
				transition: transform .6s cubic-bezier(.22,.61,.36,1) !important;
			}
			@media (hover: hover) and (prefers-reduced-motion: no-preference) {
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper:hover .store-info {
					transform: scale(1.025) !important;
				}
			}
			/* Identidad debajo de la foto: nunca botones compartiendo fila con el nombre. */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-footer {
				position: static !important;
				display: flex !important;
				box-sizing: border-box !important;
				flex: 1 1 auto !important;
				flex-direction: column !important;
				align-items: stretch !important;
				justify-content: center !important;
				gap: 0 !important;
				width: 100% !important;
				height: auto !important;
				min-height: 102px !important;
				margin: 0 !important;
				padding: clamp(21px, 2vw, 27px) clamp(22px, 2.4vw, 32px) !important;
				border: 0 !important;
				border-top: 1px solid rgba(25, 58, 43, .05) !important;
				background: #fff !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store :is(.store-data-container,.store-data) {
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
				color: #213d30 !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(1.44rem, 1.9vw, 1.82rem) !important;
				font-weight: 600 !important;
				letter-spacing: -.025em !important;
				line-height: 1.2 !important;
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
			/* El único enlace semántico de la ficha es el nombre del productor.
			 * Su pseudo-elemento amplía el área interactiva a toda la tarjeta.
			 * No se anidan enlaces ni se necesita JavaScript para navegar. */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper {
				cursor: pointer !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store a.wcfmmp-visit-store {
				display: none !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data h2 a::after {
				content: "" !important;
				position: absolute !important;
				inset: 0 !important;
				z-index: 4 !important;
				cursor: pointer !important;
			}
			/* Pequeña flecha de interfaz fuera de la fotografía.
			 * Informa de que la ficha abre al productor, sin añadir otro botón. */
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-footer {
				position: static !important; /* El área expandida del enlace usa .store-wrapper. */
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data h2 {
				padding-right: 38px !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-footer::after {
				content: "↗";
				position: absolute;
				right: clamp(22px, 2.4vw, 32px);
				bottom: 34px;
				top: auto;
				transform: none;
				font-size: 1.45rem;
				line-height: 1;
				color: #557260;
				pointer-events: none;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper:hover .store-data h2 a {
				color: #315f49 !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-wrapper:has(.store-data h2 a:focus-visible) {
				outline: 3px solid #558367 !important;
				outline-offset: 3px !important;
				border-color: #315f49 !important;
			}
			html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-data h2 a:focus-visible {
				outline-offset: 5px !important;
			}
			@media (max-width: 760px) {
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap ul.wcfmmp-store-wrap {
					grid-template-columns: minmax(0, 1fr) !important;
					gap: 20px !important;
				}
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-content {
					aspect-ratio: 2.25 / 1 !important;
				}
				html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) #wcfmmp-stores-wrap .wcfmmp-single-store .store-footer {
					min-height: 94px !important;
					padding: 19px 22px !important;
				}
				html body:is(.elmercado-producers-page,.wcfmmp-store-list-page,.wcfm-store-list-page) .emo-producers-intro {
					padding: 8px 0 22px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
