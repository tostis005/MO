<?php
/**
 * Banner propio de la tienda de Latitudes.
 *
 * Evita el banner genérico anterior ("pollo, ternera, cerdo").
 * No cambia el banner de ningún otro productor.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Identifica una tienda Latitudes sin apoyarse en el idioma o en IDs fijos. */
function elmercado_latitudes_banner_is_request_20261015(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_vendor_store_slug_010225' )
		&& 'latitudes' === elmercado_vendor_store_slug_010225() ) {
		return true;
	}
	if ( ! function_exists( 'elmercado_vendor_store_is_request_010225' )
		|| ! elmercado_vendor_store_is_request_010225()
		|| ! function_exists( 'elmercado_vendor_store_vendor_id_010225' )
		|| ! function_exists( 'elmercado_latitudes_vendor_id_20261015' ) ) {
		return false;
	}
	$vendor_id = elmercado_vendor_store_vendor_id_010225();
	return $vendor_id > 0 && $vendor_id === elmercado_latitudes_vendor_id_20261015();
}

add_filter(
	'body_class',
	static function ( $classes ) {
		if ( elmercado_latitudes_banner_is_request_20261015() ) {
			$classes[] = 'emo-latitudes-store-20261015';
		}
		return $classes;
	},
	PHP_INT_MAX
);

/** SVG local optimizado, sin peticiones a terceros ni alteraciones en WCFM. */
add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_latitudes_banner_is_request_20261015() ) {
			return;
		}
		$asset = get_stylesheet_directory_uri() . '/assets/images/latitudes-premium-banner.svg';
		?>
		<style id="elmercado-latitudes-new-banner-20261015">
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area {
				position: relative !important;
				isolation: isolate !important;
				min-height: clamp(280px, 30vw, 410px) !important;
				background: #15211b url('<?php echo esc_url( $asset ); ?>') center / cover no-repeat !important;
				overflow: hidden !important;
			}
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area::before {
				content: '';
				position: absolute;
				inset: 0;
				z-index: 1;
				pointer-events: none;
				background: linear-gradient(90deg, rgba(11,22,17,.63) 0%, rgba(11,22,17,.29) 50%, transparent 78%);
			}
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .banner_img,
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .banner_text {
				/* El banner anterior se sustituye; el logo independiente se conserva. */
				visibility: hidden !important;
			}
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-copy {
				position: absolute;
				z-index: 4;
				top: 50%;
				left: clamp(186px, 21vw, 280px);
				width: min(54%, 680px);
				transform: translateY(-50%);
				color: #fffdf7;
				text-shadow: 0 3px 22px rgba(0,0,0,.35);
				pointer-events: none;
			}
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-eyebrow {
				display: block;
				margin-bottom: 14px;
				font-weight: 800;
				font-size: clamp(12px, 1.25vw, 16px);
				letter-spacing: .19em;
				text-transform: uppercase;
				color: #e9ddc6;
			}
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-title {
				display: block;
				color: #fffdf7 !important;
				font-family: Georgia, 'Times New Roman', serif;
				font-weight: 600;
				font-size: clamp(27px, 3.1vw, 48px);
				line-height: 1.12;
				letter-spacing: -.025em;
			}
			body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-subtitle {
				display: block;
				margin-top: 14px;
				font-size: clamp(13px, 1.3vw, 17px);
				font-weight: 500;
				line-height: 1.5;
				color: #f8f2e7;
			}
			@media (max-width: 640px) {
				body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area {
					min-height: 340px !important;
					background-position: 61% center !important;
				}
				body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area::before {
					background: linear-gradient(180deg, rgba(11,22,17,.78) 0%, rgba(11,22,17,.45) 72%, rgba(11,22,17,.3) 100%);
				}
				body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-copy {
					top: 17px;
					left: 20px;
					right: 20px;
					width: auto;
					transform: none;
					text-align: center;
				}
				body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-eyebrow {
					margin-bottom: 6px;
					font-size: 11px;
				}
				body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-title {
					font-size: clamp(24px, 7vw, 33px);
				}
				body.emo-latitudes-store-20261015 #wcfmmp-store .banner_area .emo-latitudes-banner-subtitle {
					margin-top: 5px;
					font-size: 12px;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/** Texto real accesible/seleccionable y traducible, no imagen con letras. */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_latitudes_banner_is_request_20261015() ) {
			return;
		}
		$is_en = 0 === strpos( determine_locale(), 'en_' );
		$eyebrow = 'LATITUDES';
		$title = $is_en ? 'Exotic meats and premium cuts' : 'Carnes exóticas y cortes premium';
		$subtitle = $is_en ? 'Discover extraordinary flavours from around the world.' : 'Descubre sabores singulares de todo el mundo.';
		?>
		<script id="elmercado-latitudes-banner-content-20261015">
			(() => {
				const banner = document.querySelector('#wcfmmp-store .banner_area');
				if (!banner || banner.querySelector('.emo-latitudes-banner-copy')) return;
				const container = document.createElement('div');
				container.className = 'emo-latitudes-banner-copy';
				const eyebrow = document.createElement('span');
				eyebrow.className = 'emo-latitudes-banner-eyebrow';
				eyebrow.textContent = <?php echo wp_json_encode( $eyebrow ); ?>;
				const title = document.createElement('strong');
				title.className = 'emo-latitudes-banner-title';
				title.textContent = <?php echo wp_json_encode( $title ); ?>;
				const subtitle = document.createElement('span');
				subtitle.className = 'emo-latitudes-banner-subtitle';
				subtitle.textContent = <?php echo wp_json_encode( $subtitle ); ?>;
				container.append(eyebrow, title, subtitle);
				banner.appendChild(container);
			})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
