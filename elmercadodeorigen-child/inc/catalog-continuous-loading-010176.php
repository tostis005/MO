<?php
/**
 * Carga continua estable del catálogo, refinada en 0.10.177.
 *
 * Conserva la paginación HTML para rastreo y navegación sin JavaScript, pero
 * sustituye la experiencia visual de scroll infinito de Woostify por una carga
 * anticipada, silenciosa y con fallback manual si la red no responde.
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
	$is_vendor  = function_exists( 'is_wcfm_store_page' ) && is_wcfm_store_page();

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
		<style id="elmercado-continuous-catalog-010177">
			body.emo-continuous-catalog .emo-catalog-native-pagination,
			body.emo-continuous-catalog ul.products ~ #infscr-loading,
			body.emo-continuous-catalog ul.products ~ .infinite-scroll-status,
			body.emo-continuous-catalog ul.products ~ .infinite-scroll-request,
			body.emo-continuous-catalog ul.products ~ .infinite-scroll-loader,
			body.emo-continuous-catalog ul.products ~ .woostify-infinite-scroll-loading,
			body.emo-continuous-catalog ul.products ~ .woostify-load-more,
			body.emo-continuous-catalog ul.products ~ .woocommerce-load-more {
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

			/* Sentinel de carga: ocupa 1px y permanece invisible en el flujo normal. */
			body.emo-continuous-catalog .emo-catalog-load-state {
				display: block;
				width: 100%;
				height: 1px;
				min-height: 1px;
				margin: 0;
				padding: 0;
				clear: both;
				overflow: hidden;
				visibility: hidden;
			}

			/* Solo mostramos interfaz si la carga automática ha fallado dos veces. */
			body.emo-continuous-catalog .emo-catalog-load-state.is-failure {
				display: flex;
				height: auto;
				min-height: 58px;
				align-items: center;
				justify-content: center;
				gap: 10px;
				padding: 14px 0 4px;
				overflow: visible;
				visibility: visible;
				color: #496258;
				font-size: 12px;
				line-height: 1.4;
				text-align: center;
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
		</style>
		<script id="elmercado-continuous-catalog-history-010177">
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
		<script id="elmercado-continuous-catalog-loader-010177">
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

			const pageNumber = (value) => {
				try {
					const url = new URL(value, window.location.href);
					const pathMatch = url.pathname.match(/\/page\/(\d+)(?:\/|$)/i);
					if (pathMatch) return Math.max(1, Number.parseInt(pathMatch[1], 10) || 1);
					for (const key of ['paged', 'product-page', 'product_page', 'page']) {
						const parsed = Number.parseInt(url.searchParams.get(key) || '', 10);
						if (Number.isFinite(parsed) && parsed > 0) return parsed;
					}
				} catch (_) {}
				return 1;
			};

			const initialPage = pageNumber(currentUrl.href);
			let highestPage = initialPage;
			let loading = false;
			let retryTimer = 0;
			let nextUrl = '';

			const pagerScopeSelector = [
				'.woocommerce-pagination',
				'.woostify-pagination',
				'.wcfm-pagination',
				'.navigation.pagination',
				'.products-pagination',
				'.product-pagination',
				'.infinite-scroll-pagination',
				'.woostify-load-more',
				'.woocommerce-load-more'
			].join(',');

			const pagerLinks = (root) => [...root.querySelectorAll('a[href]')].filter((link) => {
				if (link.closest(pagerScopeSelector)) return true;
				if (link.matches('a.page-numbers, a[rel="next"], a[rel="prev"]')) return true;
				return /^(ver|cargar|load)\s+(más|more|anterior|previous|siguiente|next)/i.test((link.textContent || '').trim());
			});

			const findPageUrl = (root, direction, pivot) => {
				const links = pagerLinks(root);
				const direct = links.find((link) => {
					if (link.getAttribute('rel') === direction) return true;
					return link.closest(pagerScopeSelector) && link.matches(`a.${direction}, a.page-numbers.${direction}`);
				});
				if (direct?.href) return direct.href;

				const candidates = links
					.map((link) => ({ href: link.href, page: pageNumber(link.href) }))
					.filter((item) => direction === 'next' ? item.page > pivot : item.page < pivot)
					.sort((a, b) => direction === 'next' ? a.page - b.page : b.page - a.page);
				return candidates[0]?.href || '';
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

			nextUrl = findPageUrl(document, 'next', highestPage);
			const previousUrl = initialPage > 1 ? findPageUrl(document, 'prev', initialPage) : '';
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
			state.innerHTML = '<span class="emo-catalog-load-message"></span><button type="button" class="emo-catalog-load-button" hidden>Cargar más productos</button>';
			grid.insertAdjacentElement('afterend', state);

			const message = state.querySelector('.emo-catalog-load-message');
			const button = state.querySelector('.emo-catalog-load-button');
			const countNode = surface.querySelector('.woocommerce-result-count');
			const totalMatch = countNode?.textContent?.replace(/\./g, '').match(/(\d+)\s+resultados?/i);
			const total = totalMatch ? Number.parseInt(totalMatch[1], 10) : 0;
			let shown = grid.querySelectorAll(':scope > li.product').length;

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
				const failure = mode === 'failure';
				state.classList.toggle('is-failure', failure);
				message.textContent = failure ? text : '';
				button.hidden = !failure;
			};

			const showIdle = () => setState('idle');
			const showLoading = () => setState('loading');
			const showRetrying = () => setState('retrying');
			const showFailure = () => setState('failure', 'No se han podido cargar automáticamente.');
			const showFinished = () => setState('finished');

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
					return new DOMParser().parseFromString(await response.text(), 'text/html');
				} finally {
					window.clearTimeout(timeout);
				}
			};

			const loadNext = async (allowAutomaticRetry = true) => {
				if (loading || !nextUrl) return;
				loading = true;
				showLoading();
				const requestedUrl = nextUrl;

				try {
					const doc = await fetchPage(requestedUrl);
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

					highestPage = Math.max(highestPage + 1, pageNumber(requestedUrl));
					nextUrl = findPageUrl(doc, 'next', highestPage);
					shown += appended;
					updateCount();
					document.body.dispatchEvent(new CustomEvent('emo:catalog-products-appended', { detail: { count: appended, page: highestPage } }));
					window.__emoCatalogHistoryGuard?.restore?.();
					loading = false;
					if (nextUrl) showIdle();
					else showFinished();
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
				retryTimer = 0;
				showIdle();
				loadNext(false);
			});

			if (!nextUrl) {
				showFinished();
				return;
			}

			if (!('IntersectionObserver' in window)) {
				showFailure();
				return;
			}

			showIdle();
			const preloadDistance = Math.max(1800, Math.min(3200, Math.round(window.innerHeight * 2.6)));
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
