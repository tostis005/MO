<?php
/**
 * Ajustes derivados de la verificación visual y Lighthouse de la versión 0.8.5.
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

		if ( function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page() ) {
			echo '<meta name="description" content="Descubre los productos, la historia y la forma de trabajar de este productor de El Mercado de Origen.">' . "\n";
		}
		?>
		<style id="elmercado-vendor-home-verification">
			/* WCFM limitaba internamente la altura de la fotografía y dejaba una banda oscura. */
			body.wcfmmp-store-page #wcfmmp-store .banner_area .banner_img {
				position: absolute !important;
				inset: 0 !important;
				width: 100% !important;
				height: 100% !important;
				max-height: none !important;
				margin: 0 !important;
				padding: 0 !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .banner_area .banner_img img {
				position: absolute !important;
				inset: 0 !important;
				width: 100% !important;
				height: 100% !important;
				max-height: none !important;
				object-fit: cover !important;
				object-position: center 44% !important;
			}

			/* Las tarjetas claras contiguas al manifiesto usan texto oscuro, nunca blanco. */
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card),
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) h2,
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) h3,
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) h4,
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) p,
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) a,
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) span {
				color: #173f32 !important;
				opacity: 1 !important;
			}

			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) a:hover,
			body.elmercado-child-theme .emo-origin-distance-section > *:not(.emo-origin-distance-card) a:focus-visible {
				color: #1f674b !important;
			}

			/* Separación final entre navegación, resultados y catálogo. */
			body.wcfmmp-store-page #wcfmmp-store .tab_links_area + .wcfm-clearfix {
				display: none !important;
			}

			body.wcfmmp-store-page #wcfmmp-store #products {
				margin-top: 0 !important;
			}

			body.wcfmmp-store-page #wcfmmp-store .woostify-sorting + ul.products {
				margin-top: 0 !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-origin-distance-section-class">
		(() => {
			const card = document.querySelector('.emo-origin-distance-card');
			if (!card || !card.parentElement) return;
			card.parentElement.classList.add('emo-origin-distance-section');
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

add_filter(
	'robots_txt',
	static function ( string $output, bool $public ): string {
		if ( ! $public ) {
			return "User-agent: *\nDisallow: /\n";
		}

		return "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\nSitemap: " . home_url( '/sitemap_index.xml' ) . "\n";
	},
	PHP_INT_MAX,
	2
);
