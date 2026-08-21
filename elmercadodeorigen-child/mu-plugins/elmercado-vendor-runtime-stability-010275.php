<?php
/**
 * Runtime stability for WCFM vendor stores 0.10.275.
 *
 * This helper now owns pagination cache-busting only. Mobile ordering is owned
 * exclusively by elmercado-vendor-ordering-popover-010272.php (implementation
 * 0.10.276), avoiding multiple observers and touch handlers fighting each other.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-runtime-stability-010275">
		(() => {
			'use strict';
			if (!document.body || !document.body.classList.contains('wcfmmp-store-page')) return;

			if (!window.__mdoVendorFetchNoStore010275) {
				window.__mdoVendorFetchNoStore010275 = true;
				const nativeFetch = window.fetch.bind(window);
				window.fetch = (input, init = {}) => {
					try {
						const raw = typeof input === 'string' || input instanceof URL ? input : input?.url;
						const url = new URL(raw, window.location.href);
						const here = new URL(window.location.href);
						const currentStoreBase = here.pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
						const requestedStoreBase = url.pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
						const isVendorPageRequest = url.origin === here.origin && requestedStoreBase === currentStoreBase && (/\/page\/\d+\/?$/i.test(url.pathname) || [...url.searchParams.keys()].some(key => ['paged','product-page','product_page','page'].includes(key)));
						if (isVendorPageRequest) {
							url.searchParams.set('_mdo_scroll', `${Date.now()}-${Math.random().toString(36).slice(2,8)}`);
							const headers = new Headers(init?.headers || (input instanceof Request ? input.headers : undefined));
							headers.set('Cache-Control', 'no-cache, no-store, max-age=0');
							headers.set('Pragma', 'no-cache');
							return nativeFetch(url.href, { ...init, cache: 'no-store', headers });
						}
					} catch (_) {}
					return nativeFetch(input, init);
				};
			}
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
