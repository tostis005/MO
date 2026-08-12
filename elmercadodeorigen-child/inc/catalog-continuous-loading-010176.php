<?php
/**
 * Carga continua estable del catálogo, refinada en 0.10.181.
 *
 * Conserva la paginación HTML para rastreo y navegación sin JavaScript, pero
 * sustituye la experiencia visual de scroll infinito de Woostify por una carga
 * anticipada, con un único indicador visible y fallback manual si la red falla.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Indica si estamos en un archivo de producto o en una tienda WCFM.
 */
function elmercado_is_continuous_catalog_surface_010176(): bool {
	$is_archive = function_exists( 'is_shop' ) && ( is_shop() || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) );
	$is_vendor  = ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) ||
		( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) ||
		( function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page() );

	if ( ! $is_vendor ) {
		$store_query_var = function_exists( 'wcfm_get_option' ) ? (string) wcfm_get_option( 'wcfm_store_url', 'store' ) : 'store';
		$is_vendor       = '' !== $store_query_var && '' !== (string) get_query_var( $store_query_var );
	}

	return $is_archive || $is_vendor;
}

/*
 * El scroll nativo de Woostify usa esta clase para activar su experiencia.
 * La retiramos antes de que llegue al navegador y mantenemos la paginación real
 * como fuente de URLs para una mejora progresiva controlada por el tema hijo.
 */
add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( ! elmercado_is_continuous_catalog_surface_010176() ) {
			return $classes;
		}

		$classes = array_values( array_diff( $classes, array( 'infinite-scroll-active' ) ) );
		$classes[] = 'emo-continuous-catalog';

		return array_values( array_unique( $classes ) );
	},
	PHP_INT_MAX
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! elmercado_is_continuous_catalog_surface_010176() ) {
			return;
		}
		?>
		<style id="elmercado-continuous-catalog-010181">
			/* El cargador nativo residual es el segundo spinner que no debe verse. */
			body.emo-continuous-catalog .emo-catalog-native-pagination,
			body.emo-continuous-catalog #infscr-loading,
			body.emo-continuous-catalog .infinite-scroll-status,
			body.emo-continuous-catalog .infinite-scroll-request,
			body.emo-continuous-catalog .infinite-scroll-loader,
			body.emo-continuous-catalog .woostify-infinite-scroll-loading,
			body.emo-continuous-catalog .woostify-load-more,
			body.emo-continuous-catalog .woocommerce-load-more {
				display: none !important;
			}

			body.emo-continuous-catalog .emo-catalog-previous {
				display: inline-flex;
				align-items: center;
				min-height: 38px;
				margin: 0 0 16px;
				padding: 0 14px;
				border: 1px solid rgba(23,63,50,.16);
				border-radius: 999px;
				background: #fff;
				color: #173f32;
				font-size: 12px;
				font-weight: 750;
				text-decoration: none;
			}

			/* En reposo solo queda el sentinel de 1px; durante la petición sí damos feedback. */
			body.emo-continuous-catalog .emo-catalog-load-state {
				display: flex;
				box-sizing: border-box;
				width: 100%;
				height: 1px;
				min-height: 1px;
				align-items: center;
				justify-content: center;
				gap: 10px;
				margin: 0;
				padding: 0;
				clear: both;
				overflow: hidden;
				visibility: hidden;
				color: #496258;
				font-size: 12px;
				line-height: 1.4;
				text-align: center;
			}

			body.emo-continuous-catalog .emo-catalog-load-state.is-loading,
			body.emo-continuous-catalog .emo-catalog-load-state.is-failure {
				height: auto;
				min-height: 58px;
				padding: 14px 0 4px;
				overflow: visible;
				visibility: visible;
			}

			body.emo-continuous-catalog .emo-catalog-spinner {
				display: none;
				width: 18px;
				height: 18px;
				flex: 0 0 18px;
				border: 2px solid rgba(23,63,50,.18);
				border-top-color: #173f32;
				border-radius: 50%;
				animation: emo-catalog-spin-010181 .7s linear infinite;
			}

			body.emo-continuous-catalog .emo-catalog-load-state.is-loading .emo-catalog-spinner {
				display: inline-block;
			}

			body.emo-continuous-catalog .emo-catalog-load-button {
				display: inline-flex;
				min-height: 40px;
				align-items: center;
				justify-content: center;
				padding: 0 18px;
				border: 0;
				border-radius: 999px;
				background: #173f32;
				color: #fff;
				font-size: 12px;
				font-weight: 800;
				cursor: pointer;
			}

			body.emo-continuous-catalog .emo-catalog-load-button[hidden] {
				display: none !important;
			}

			@keyframes emo-catalog-spin-010181 {
				to { transform: rotate(360deg); }
			}

			@media (prefers-reduced-motion: reduce) {
				body.emo-continuous-catalog .emo-catalog-spinner {
					animation: none;
				}
			}
		</style>
		<script id="elmercado-continuous-catalog-history-010181">
		(() => {
			'use strict';

			const initialUrl = new URL(window.location.href);
			const pageNumber = (value) => {
				try {
					const url = new URL(value, initialUrl.href);
					const pathMatch = url.pathname.match(/\/page\/(\d+)(?:\/|$)/i);
					if (pathMatch) return Math.max(1, Number.parseInt(pathMatch[1], 10) || 1);
					for (const key of ['paged', 'product-page', 'product_page', 'page']) {
						const parsed = Number.parseInt(url.searchParams.get(key) || '', 10);
						if (Number.isFinite(parsed) && parsed > 0) return parsed;
					}
				} catch (_) {}
				return 1;
			};

			const initialPage = pageNumber(initialUrl.href);
			const basePath = (pathname) => pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
			const originalPushState = window.history.pushState.bind(window.history);
			const originalReplaceState = window.history.replaceState.bind(window.history);

			const isScrollPaginationUrl = (candidate) => {
				if (!candidate || initialPage !== 1) return false;
				try {
					const target = new URL(candidate, initialUrl.href);
					return target.origin === initialUrl.origin &&
						basePath(target.pathname) === basePath(initialUrl.pathname) &&
						pageNumber(target.href) > 1;
				} catch (_) {
					return false;
				}
			};

			window.history.pushState = function (state, title, url) {
				if (isScrollPaginationUrl(url)) return;
				return originalPushState(state, title, url);
			};

			window.history.replaceState = function (state, title, url) {
				if (isScrollPaginationUrl(url)) return;
				return originalReplaceState(state, title, url);
			};

			window.__emoCatalogHistoryGuard = {
				initialHref: initialUrl.href,
				initialPage,
				restore() {
					if (initialPage === 1 && pageNumber(window.location.href) > 1) {
						originalReplaceState(window.history.state, '', initialUrl.href);
					}
				}
			};
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! elmercado_is_continuous_catalog_surface_010176() ) {
			return;
		}
		?>
		<script id="elmercado-continuous-catalog-loader-010181">
		(() => {
			'use strict';

			const body = document.body;
			if (!body.classList.contains('emo-continuous-catalog')) return;
			body.classList.remove('infinite-scroll-active');
			window.__emoCatalogHistoryGuard?.restore?.();

			const gridSelector = '#wcfmmp-store ul.products, main ul.products, #primary ul.products, .content-area ul.products, ul.products';
			const grid = document.querySelector(gridSelector);
			if (!grid) return;

			const surface = grid.closest('#wcfmmp-store, main, #primary, .content-area') || document;
			const currentUrl = new URL(window.location.href);
			const paginationKeys = ['paged', 'product-page', 'product_page', 'page'];

			const pageFromUrl = (value) => {
				try {
					const url = new URL(value, currentUrl.href);
					const pathMatch = url.pathname.match(/\/page\/(\d+)(?:\/|$)/i);
					if (pathMatch) return Math.max(1, Number.parseInt(pathMatch[1], 10) || 1);
					for (const key of paginationKeys) {
						const parsed = Number.parseInt(url.searchParams.get(key) || '', 10);
						if (Number.isFinite(parsed) && parsed > 0) return parsed;
					}
				} catch (_) {}
				return 0;
			};

			const resolveHref = (element, baseUrl) => {
				const raw = element?.getAttribute?.('href');
				if (!raw || raw === '#' || /^javascript:/i.test(raw)) return '';
				try {
					return new URL(raw, baseUrl).href;
				} catch (_) {
					return '';
				}
			};

			const catalogFamily = (value) => {
				try {
					const url = new URL(value, currentUrl.href);
					url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
					paginationKeys.forEach((key) => url.searchParams.delete(key));
					url.hash = '';
					return `${url.origin}${url.pathname}`;
				} catch (_) {
					return '';
				}
			};

			const pageFromElement = (element, baseUrl) => {
				if (!element) return 0;
				for (const key of ['page', 'paged', 'pageNumber', 'page-number']) {
					const attrName = `data-${key.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}`;
					const parsed = Number.parseInt(element.getAttribute?.(attrName) || '', 10);
					if (Number.isFinite(parsed) && parsed > 0) return parsed;
				}
				const text = (element.textContent || '').trim();
				if (/^\d+$/.test(text)) return Number.parseInt(text, 10) || 0;
				const href = resolveHref(element, baseUrl);
				return href ? pageFromUrl(href) : 0;
			};

			const pagerScopes = [
				'.woocommerce-pagination',
				'.woostify-pagination',
				'.wcfm-pagination',
				'.wcfmmp-pagination',
				'.wcfm_pagination',
				'.wcfmmp-store-product-pagination',
				'.navigation.pagination',
				'.products-pagination',
				'.product-pagination',
				'.infinite-scroll-pagination',
				'.woostify-load-more',
				'.woocommerce-load-more'
			];
			const pagerScopeSelector = pagerScopes.join(',');
			const pagerLinkSelector = [
				...pagerScopes.map((selector) => `${selector} a[href]`),
				'a.page-numbers[href]',
				'a[rel~="next"][href]',
				'a[rel~="prev"][href]'
			].join(',');

			const pagerLinks = (root) => [...new Set(root.querySelectorAll(pagerLinkSelector))];

			const currentPageFromDocument = (root, baseUrl) => {
				const selectors = [
					'[aria-current="page"]',
					'.page-numbers.current',
					'.woocommerce-pagination .current',
					'.wcfmmp-pagination .current',
					'.wcfm-pagination .current',
					'.pagination .current',
					'.pagination .active'
				];
				for (const selector of selectors) {
					const marker = root.querySelector(selector);
					const page = pageFromElement(marker, baseUrl) || pageFromElement(marker?.querySelector?.('a[href]'), baseUrl);
					if (page) return page;
				}
				return pageFromUrl(baseUrl) || 1;
			};

			const directionMatches = (link, direction) => {
				const rel = (link.getAttribute('rel') || '').toLowerCase().split(/\s+/);
				if (rel.includes(direction)) return true;
				if (link.classList.contains(direction)) return true;
				const words = `${link.getAttribute('aria-label') || ''} ${link.getAttribute('title') || ''} ${link.textContent || ''}`.replace(/\s+/g, ' ').trim().toLowerCase();
				return direction === 'next'
					? /\b(next|siguiente|más|more)\b/.test(words)
					: /\b(prev|previous|anterior)\b/.test(words);
			};

			const findPageUrl = (root, direction, baseUrl, pivot) => {
				const family = catalogFamily(baseUrl);
				const headLink = root.querySelector(`link[rel~="${direction}"][href]`);
				const headHref = resolveHref(headLink, baseUrl);
				if (headHref && catalogFamily(headHref) === family) return headHref;

				const links = pagerLinks(root);
				const direct = links.find((link) => {
					if (!directionMatches(link, direction)) return false;
					const href = resolveHref(link, baseUrl);
					return href && catalogFamily(href) === family;
				});
				if (direct) return resolveHref(direct, baseUrl);

				const desiredPage = direction === 'next' ? pivot + 1 : Math.max(1, pivot - 1);
				const candidates = links
					.map((link) => ({ href: resolveHref(link, baseUrl), page: pageFromElement(link, baseUrl) }))
					.filter((item) => item.href && catalogFamily(item.href) === family && item.page > 0)
					.filter((item) => direction === 'next' ? item.page > pivot : item.page < pivot)
					.sort((a, b) => {
						if (a.page === desiredPage && b.page !== desiredPage) return -1;
						if (b.page === desiredPage && a.page !== desiredPage) return 1;
						return direction === 'next' ? a.page - b.page : b.page - a.page;
					});
				if (candidates[0]?.href) return candidates[0].href;

				/*
				 * Última lectura de compatibilidad: si WCFM/tema cambia las clases del
				 * paginador, seguimos exigiendo un href REAL del HTML recibido que sea
				 * exactamente la página contigua de esta misma superficie.
				 */
				const exact = [...root.querySelectorAll('a[href]')].find((link) => {
					const href = resolveHref(link, baseUrl);
					return href && catalogFamily(href) === family && pageFromUrl(href) === desiredPage;
				});
				return exact ? resolveHref(exact, baseUrl) : '';
			};

			const paginationSnapshot = (root, baseUrl) => {
				const current = currentPageFromDocument(root, baseUrl);
				return {
					current,
					next: findPageUrl(root, 'next', baseUrl, current),
					prev: current > 1 ? findPageUrl(root, 'prev', baseUrl, current) : ''
				};
			};

			const hideNativePagination = (root = document) => {
				root.querySelectorAll(pagerScopeSelector).forEach((node) => node.classList.add('emo-catalog-native-pagination'));
				root.querySelectorAll('a[href],button').forEach((control) => {
					const text = (control.textContent || '').replace(/\s+/g, ' ').trim();
					if (!/^(ver|cargar|load)\s+(anterior|previous|más|more|siguiente|next)$/i.test(text)) return;
					control.classList.add('emo-catalog-native-pagination');
					const parent = control.parentElement;
					if (!parent || parent.children.length !== 1) return;
					if (parent.matches('.woostify-sorting,.elmercado-vendor-toolbar') || parent.querySelector('.woocommerce-result-count,.woocommerce-ordering')) return;
					parent.classList.add('emo-catalog-native-pagination');
				});
			};

			const initialPagination = paginationSnapshot(document, currentUrl.href);
			const initialPage = initialPagination.current || 1;
			let highestPage = initialPage;
			let loading = false;
			let retryTimer = 0;
			let continuationTimer = 0;
			let nextUrl = initialPagination.next;
			const previousUrl = initialPagination.prev;
			hideNativePagination();

			if (previousUrl) {
				const previous = document.createElement('a');
				previous.className = 'emo-catalog-previous';
				previous.href = previousUrl;
				previous.textContent = 'Ver productos anteriores';
				grid.insertAdjacentElement('beforebegin', previous);
			}

			const state = document.createElement('div');
			state.className = 'emo-catalog-load-state';
			state.setAttribute('role', 'status');
			state.setAttribute('aria-live', 'polite');
			state.innerHTML = '<span class="emo-catalog-spinner" aria-hidden="true"></span><span class="emo-catalog-load-message"></span><button type="button" class="emo-catalog-load-button" hidden>Cargar más productos</button>';
			grid.insertAdjacentElement('afterend', state);

			const message = state.querySelector('.emo-catalog-load-message');
			const button = state.querySelector('.emo-catalog-load-button');
			const countNode = surface.querySelector('.woocommerce-result-count');
			const countText = countNode?.textContent?.replace(/\s+/g, ' ').trim() || '';
			const totalMatch = countText.match(/(?:de|of)\s+([\d.,]+)\s+(?:resultados?|results?)/i) || countText.match(/([\d.,]+)\s+(?:resultados?|results?)/i);
			const total = totalMatch ? Number.parseInt(totalMatch[1].replace(/[.,]/g, ''), 10) || 0 : 0;
			let shown = grid.querySelectorAll(':scope > li.product').length;
			const preloadDistance = Math.max(1800, Math.min(3200, Math.round(window.innerHeight * 2.6)));

			const updateCount = () => {
				if (!countNode || initialPage !== 1 || !total) return;
				countNode.textContent = `Mostrando ${Math.min(shown, total)} de ${total} resultados`;
			};

			const productKey = (item) => {
				const postClass = [...item.classList].find((name) => /^post-\d+$/.test(name));
				if (postClass) return postClass;
				const link = item.querySelector('a.woocommerce-LoopProduct-link, a[href*="/producto/"]');
				return link?.href || '';
			};

			const setState = (mode, text = '') => {
				const active = mode === 'loading' || mode === 'retrying';
				const failure = mode === 'failure';
				state.classList.toggle('is-loading', active);
				state.classList.toggle('is-failure', failure);
				message.textContent = active || failure ? text : '';
				button.hidden = !failure || !nextUrl;
			};

			const showIdle = () => setState('idle');
			const showLoading = () => setState('loading', 'Cargando más productos…');
			const showRetrying = () => setState('retrying', 'Cargando más productos…');
			const showFailure = (text = 'No se ha podido continuar la carga automática.') => setState('failure', text);
			const showFinished = () => setState('finished');

			const stateIsNearViewport = () => {
				const rect = state.getBoundingClientRect();
				return rect.top <= window.innerHeight + preloadDistance && rect.bottom >= -preloadDistance;
			};

			const fetchPage = async (url) => {
				const controller = new AbortController();
				const timeout = window.setTimeout(() => controller.abort(), 8000);
				try {
					const response = await fetch(url, {
						credentials: 'same-origin',
						signal: controller.signal,
						headers: { 'Accept': 'text/html' }
					});
					if (!response.ok) throw new Error(`HTTP ${response.status}`);
					const html = await response.text();
					return {
						doc: new DOMParser().parseFromString(html, 'text/html'),
						responseUrl: response.url || url
					};
				} finally {
					window.clearTimeout(timeout);
				}
			};

			const scheduleContinuation = () => {
				window.clearTimeout(continuationTimer);
				continuationTimer = window.setTimeout(() => {
					if (!loading && nextUrl && stateIsNearViewport()) loadNext(true);
				}, 60);
			};

			const debugState = {};
			Object.defineProperties(debugState, {
				initialUrl: { enumerable: true, get: () => currentUrl.href },
				nextUrl: { enumerable: true, get: () => nextUrl },
				highestPage: { enumerable: true, get: () => highestPage },
				shown: { enumerable: true, get: () => shown },
				loading: { enumerable: true, get: () => loading }
			});
			window.__emoCatalogLoaderState = debugState;

			const loadNext = async (allowAutomaticRetry = true) => {
				if (loading || !nextUrl) return;
				loading = true;
				showLoading();
				const requestedUrl = nextUrl;

				try {
					const { doc, responseUrl } = await fetchPage(requestedUrl);
					const sourceGrid = doc.querySelector(gridSelector);
					if (!sourceGrid) throw new Error('Product grid not found');

					const existing = new Set([...grid.querySelectorAll(':scope > li.product')].map(productKey).filter(Boolean));
					let appended = 0;
					[...sourceGrid.querySelectorAll(':scope > li.product')].forEach((item) => {
						const key = productKey(item);
						if (key && existing.has(key)) return;
						if (key) existing.add(key);
						grid.append(document.importNode(item, true));
						appended += 1;
					});
					if (!appended) throw new Error('No new products returned');

					const responsePagination = paginationSnapshot(doc, responseUrl);
					highestPage = Math.max(highestPage, responsePagination.current || pageFromUrl(requestedUrl) || highestPage + 1);
					shown += appended;
					nextUrl = responsePagination.next;
					updateCount();
					document.body.dispatchEvent(new CustomEvent('emo:catalog-products-appended', { detail: { count: appended, page: highestPage, nextUrl } }));
					window.__emoCatalogHistoryGuard?.restore?.();
					loading = false;

					if (nextUrl) {
						showIdle();
						scheduleContinuation();
						return;
					}

					if (total && shown < total) {
						showFailure('No se ha podido localizar la siguiente página del catálogo.');
						return;
					}

					showFinished();
				} catch (_) {
					loading = false;
					window.__emoCatalogHistoryGuard?.restore?.();
					if (allowAutomaticRetry) {
						showRetrying();
						window.clearTimeout(retryTimer);
						retryTimer = window.setTimeout(() => {
							if (!loading && nextUrl === requestedUrl) loadNext(false);
						}, 800);
						return;
					}
					showFailure();
				}
			};

			button.addEventListener('click', () => {
				window.clearTimeout(retryTimer);
				window.clearTimeout(continuationTimer);
				retryTimer = 0;
				continuationTimer = 0;
				showIdle();
				loadNext(false);
			});

			if (!nextUrl) {
				if (total && shown < total) {
					showFailure('No se ha podido localizar la siguiente página del catálogo.');
				} else {
					showFinished();
				}
				return;
			}

			if (!('IntersectionObserver' in window)) {
				showFailure();
				return;
			}

			showIdle();
			const observer = new IntersectionObserver((entries) => {
				if (entries.some((entry) => entry.isIntersecting)) loadNext(true);
			}, { rootMargin: `${preloadDistance}px 0px ${preloadDistance}px 0px`, threshold: 0.01 });
			observer.observe(state);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
