<?php
/**
 * Readable and accessible PhotoSwipe controls for WooCommerce galleries.
 * Scoped to product detail pages only.
 *
 * @package ElMercadoDeOrigen
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		$relative = '/assets/css/product-gallery-controls-010318.css';
		if ( ! is_readable( ELMERCADO_THEME_PATH . $relative ) ) {
			return;
		}
		wp_enqueue_style(
			'elmercado-product-gallery-controls',
			ELMERCADO_THEME_URL . $relative,
			array(),
			function_exists( 'elmercado_asset_version' )
				? elmercado_asset_version( $relative )
				: ELMERCADO_THEME_VERSION
		);
	},
	9999
);

add_action(
	'wp_footer',
	static function (): void {
		if ( ! function_exists( 'is_product' ) || ! is_product() ) {
			return;
		}
		?>
		<script id="elmercado-product-gallery-labels-010318">
		(function () {
			'use strict';
			var names = {
				'pswp__button--close': 'Cerrar imagen',
				'pswp__button--share': 'Compartir imagen',
				'pswp__button--fs': 'Pantalla completa',
				'pswp__button--zoom': 'Ampliar o reducir imagen',
				'pswp__button--arrow--left': 'Imagen anterior',
				'pswp__button--arrow--right': 'Imagen siguiente'
			};
			function applyLabels() {
				var pswp = document.querySelector('.pswp');
				if (!pswp) { return; }
				Object.keys(names).forEach(function (klass) {
					pswp.querySelectorAll('.' + klass).forEach(function (button) {
						button.setAttribute('aria-label', names[klass]);
						button.setAttribute('title', names[klass]);
						if (!button.hasAttribute('type')) { button.setAttribute('type', 'button'); }
					});
				});
			}
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', applyLabels, { once: true });
			} else {
				applyLabels();
			}
		}());
		</script>
		<script id="elmercado-product-gallery-resize-20261027">
		(function () {
			'use strict';
			var frame = 0;
			function resizeCarousels() {
				frame = 0;
				var galleries = document.querySelectorAll(
					'.product-images-container.flickity-enabled, .product-thumbnail-images-container.flickity-enabled'
				);
				galleries.forEach(function (gallery) {
					var instance = window.Flickity && typeof window.Flickity.data === 'function'
						? window.Flickity.data(gallery) : null;
					if (!instance && window.jQuery) {
						instance = window.jQuery(gallery).data('flickity');
					}
					if (instance && typeof instance.resize === 'function') {
						instance.resize();
					}
				});
			}
			function scheduleResize() {
				if (frame || typeof window.requestAnimationFrame !== 'function') { return; }
				frame = window.requestAnimationFrame(resizeCarousels);
			}
			// Images may arrive after Flickity measures the initial viewport.
			// A load listener in capture phase also covers native lazy loading.
			document.addEventListener('load', function (event) {
				var target = event.target;
				if (target && target.tagName === 'IMG' && target.closest &&
					target.closest('.product-gallery')) {
					scheduleResize();
				}
			}, true);
			window.addEventListener('load', scheduleResize, { once: true });
			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', scheduleResize, { once: true });
			} else {
				scheduleResize();
			}
		}());
		</script>
		<?php
	},
	99
);
