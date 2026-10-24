<?php
/**
 * Unifica el título de todas las cabeceras de tiendas de productores WCFM.
 * No depende de la preferencia individual de WCFM sobre la posición del nombre.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_producer_banner_is_store_20261024(): bool {
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return true;
	}

	return ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() )
		|| ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() );
}

function elmercado_producer_banner_vendor_id_20261024(): int {
	if ( function_exists( 'elmercado_public_store_fix_vendor_id_010261' ) ) {
		$id = absint( elmercado_public_store_fix_vendor_id_010261() );
		if ( $id ) {
			return $id;
		}
	}
	if ( function_exists( 'elmercado_vendor_store_vendor_id_010225' ) ) {
		return absint( elmercado_vendor_store_vendor_id_010225() );
	}
	return 0;
}

function elmercado_producer_banner_name_20261024( int $vendor_id = 0 ): string {
	$vendor_id = $vendor_id ?: elmercado_producer_banner_vendor_id_20261024();
	if ( $vendor_id <= 0 ) {
		return '';
	}

	$name = '';
	if ( function_exists( 'wcfmmp_get_store' ) ) {
		$store = wcfmmp_get_store( $vendor_id );
		if ( is_object( $store ) && method_exists( $store, 'get_shop_name' ) ) {
			$name = (string) $store->get_shop_name();
		}
		if ( '' === trim( $name ) && is_object( $store ) && method_exists( $store, 'get_shop_info' ) ) {
			$info = $store->get_shop_info();
			if ( is_array( $info ) ) {
				$name = (string) ( $info['store_name'] ?? '' );
			}
		}
	}
	if ( '' === trim( $name ) ) {
		$settings = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
		if ( is_array( $settings ) ) {
			$name = (string) ( $settings['store_name'] ?? '' );
		}
	}
	if ( '' === trim( $name ) ) {
		$vendor = get_userdata( $vendor_id );
		$name   = $vendor instanceof WP_User ? (string) $vendor->display_name : '';
	}
	return trim( wp_strip_all_tags( $name ) );
}

/*
 * Cuando WCFM ejecuta su hook del banner, el H1 se sirve directamente en HTML,
 * también para SEO y sin esperar a JavaScript. El ajuste de pie cubre las
 * variantes de plantilla que no ejecuten ese hook.
 */
add_action(
	'wcfmmp_store_after_bannar_image',
	static function ( $vendor_id = 0 ): void {
		if ( ! elmercado_producer_banner_is_store_20261024() ) {
			return;
		}
		$name = elmercado_producer_banner_name_20261024( is_numeric( $vendor_id ) ? absint( $vendor_id ) : 0 );
		if ( '' === $name ) {
			return;
		}
		echo '<div class="emo-producer-banner-title-wrap"><h1 class="emo-producer-banner-title">' . esc_html( $name ) . '</h1></div>';
	},
	10,
	1
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! elmercado_producer_banner_is_store_20261024() ) {
			return;
		}
		?>
		<style id="elmercado-producer-banner-unified-20261024">
			/* Una sola posición visual, independientemente del ajuste de WCFM. */
			body.wcfmmp-store-page #wcfmmp-store .banner_area .banner_text h1 {
				display: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-producer-banner-title-wrap {
				position: absolute !important;
				z-index: 6 !important;
				top: 50% !important;
				right: clamp(1.5rem, 5vw, 4rem) !important;
				left: clamp(11rem, 20vw, 17rem) !important;
				width: auto !important;
				margin: 0 !important;
				padding: 0 !important;
				transform: translateY(-50%) !important;
				text-align: left !important;
				pointer-events: none !important;
			}
			body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-producer-banner-title-wrap h1 {
				display: block !important;
				max-width: 15ch !important;
				margin: 0 !important;
				padding: 0 !important;
				color: #fffdf8 !important;
				font-family: Georgia, "Times New Roman", serif !important;
				font-size: clamp(2.1rem, 5vw, 4.4rem) !important;
				font-weight: 700 !important;
				letter-spacing: -0.045em !important;
				line-height: 1.04 !important;
				text-transform: none !important;
				overflow-wrap: break-word !important;
				text-shadow: 0 2px 16px rgba(10, 35, 27, 0.35) !important;
			}
			@media (max-width: 640px) {
				body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-producer-banner-title-wrap {
					top: 34% !important;
					right: 1.25rem !important;
					left: 1.25rem !important;
					text-align: center !important;
				}
				body.wcfmmp-store-page #wcfmmp-store .banner_area > .emo-producer-banner-title-wrap h1 {
					max-width: none !important;
					font-size: clamp(1.65rem, 7vw, 2.65rem) !important;
					line-height: 1.06 !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! elmercado_producer_banner_is_store_20261024() ) {
			return;
		}
		$name = elmercado_producer_banner_name_20261024();
		?>
		<script id="elmercado-producer-banner-title-unified-20261024">
		(() => {
			'use strict';
			const officialName = <?php echo wp_json_encode( $name, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ) ?: '""'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>;
			const normalize = () => {
				const banner = document.querySelector('body.wcfmmp-store-page #wcfmmp-store .banner_area');
				if (!banner) return;
				const nativeTitle = banner.querySelector('.banner_text h1');
				const existingWrap = document.querySelector('body.wcfmmp-store-page #wcfmmp-store .emo-producer-banner-title-wrap');
				const existingTitle = existingWrap?.querySelector('h1');
				const headerName = document.querySelector('#wcfm_store_header .store_name, #wcfm_store_header h1')?.textContent?.trim() || '';
				const pageName = (document.title || '').split(/\s+[|–—]\s+/)[0].trim();
				const storeName = officialName || nativeTitle?.textContent?.trim() || headerName || pageName;
				if (!storeName) return;

				const wrap = existingWrap || document.createElement('div');
				wrap.className = 'emo-producer-banner-title-wrap';
				const title = existingTitle || nativeTitle || document.createElement('h1');
				title.classList.add('emo-producer-banner-title');
				title.textContent = storeName;
				wrap.appendChild(title);
				if (wrap.parentElement !== banner) banner.appendChild(wrap);
				if (nativeTitle && nativeTitle !== title) nativeTitle.remove();
				// WCFM puede configurar el nombre en la cabecera, pero esa cabecera
				// está oculta en nuestro diseño: el título visible siempre vive aquí.
			};
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', normalize, {once: true});
			} else {
				normalize();
			}
			window.addEventListener('load', normalize, {once: true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
