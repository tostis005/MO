<?php
/**
 * Cierre final 0.10.234: contador estable, paridad real de filtros y carga
 * continua sin repeticiones al final del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Superficie objetivo: Tienda/categorías o una tienda WCFM.
 */
function elmercado_catalog_filter_scroll_target_010234(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}
	return function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225();
}

/**
 * Total exacto que debe permanecer visible durante toda la carga continua.
 */
function elmercado_catalog_filter_scroll_total_010234(): int {
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() && function_exists( 'elmercado_vendor_store_state_010225' ) ) {
		$state = elmercado_vendor_store_state_010225();
		return max( 0, (int) ( $state['total'] ?? 0 ) );
	}
	if ( function_exists( 'elmercado_catalog_exact_result_total_010220' ) ) {
		return max( 0, (int) elmercado_catalog_exact_result_total_010220() );
	}
	return 0;
}

/**
 * Retira solo el loader footer histórico. Se conservan su CSS y el guard de
 * history del head; el loader de abajo lo sustituye con paginación estricta.
 */
function elmercado_catalog_filter_scroll_remove_legacy_loader_010234(): void {
	global $wp_filter;

	if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}

	$legacy_file = wp_normalize_path( ELMERCADO_THEME_PATH . '/inc/catalog-continuous-loading-010176.php' );
	$callbacks   = $wp_filter['wp_footer']->callbacks;

	foreach ( $callbacks as $priority => $items ) {
		foreach ( $items as $item ) {
			$callback = $item['function'] ?? null;
			if ( ! $callback instanceof Closure ) {
				continue;
			}
			try {
				$reflection = new ReflectionFunction( $callback );
				$filename   = $reflection->getFileName();
			} catch ( Throwable $throwable ) {
				continue;
			}
			if ( is_string( $filename ) && wp_normalize_path( $filename ) === $legacy_file ) {
				remove_action( 'wp_footer', $callback, (int) $priority );
			}
		}
	}
}

elmercado_catalog_filter_scroll_remove_legacy_loader_010234();

/**
 * Última capa visual: el panel interno del productor no añade un segundo
 * padding y ambos raíles usan literalmente la misma familia tipográfica.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_catalog_filter_scroll_target_010234() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-parity-final-010234">
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 {
				font-family:Aptos,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-filter-rail-shared-010229 #emo-vendor-filters {
				display:block !important; box-sizing:border-box !important; width:100% !important; max-width:none !important;
				margin:0 !important; padding:0 !important; border:0 !important; border-radius:0 !important;
				background:transparent !important; box-shadow:none !important; font-family:inherit !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-title-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-filter-rail-shared-010229 #emo-vendor-filters .widget-title {
				font-family:inherit !important; font-size:10.5px !important; font-weight:800 !important; line-height:1.25 !important;
				letter-spacing:.085em !important; text-transform:uppercase !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:hover,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:focus-visible,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) > .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229 > span {
				text-decoration:none !important; text-decoration-line:none !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove:hover > span:last-child,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove:focus-visible > span:last-child {
				text-decoration:none !important; text-decoration-line:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Loader definitivo: solo acepta la página contigua, conserva todos los
 * filtros de la URL, deduplica por identidad de producto y se detiene al llegar
 * al total exacto del servidor.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_catalog_filter_scroll_target_010234() ) {
			return;
		}

		$total = elmercado_catalog_filter_scroll_total_010234();
		$label = sprintf(
			esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ),
			number_format_i18n( $total )
		);
		?>
		<script id="elmercado-catalog-scroll-final-010234">
		(() => {
			'use strict';

			const exactTotal = <?php echo wp_json_encode( $total ); ?>;
			const exactLabel = <?php echo wp_json_encode( $label ); ?>;
			const gridSelector = '#wcfmmp-store ul.products,main ul.products,#primary ul.products,.content-area ul.products,ul.products';
			const grid = document.querySelector(gridSelector);
			if (!grid) return;

			const isVendor = !!grid.closest('#wcfmmp-store');
			const surface = isVendor ? grid.closest('#wcfmmp-store') : (grid.closest('main,#primary,.content-area') || document);
			const currentUrl = new URL(window.location.href);
			const paginationKeys = ['paged','product-page','product_page','page'];
			const pagerScopes = [
				'.woocommerce-pagination','.woostify-pagination','.wcfm-pagination','.wcfmmp-pagination','.wcfm_pagination',
				'.wcfmmp-store-product-pagination','.navigation.pagination','.products-pagination','.product-pagination',
				'.infinite-scroll-pagination','.woostify-load-more','.woocommerce-load-more'
			];
			const pagerScopeSelector = pagerScopes.join(',');
			const pagerLinkSelector = pagerScopes.map((selector) => `${selector} a[href]`).concat(['a.page-numbers[href]']).join(',');

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

			const signature = (value) => {
				try {
					const url = new URL(value, currentUrl.href);
					url.pathname = url.pathname.replace(/\/page\/\d+\/?$/i, '/').replace(/\/+$/, '/');
					paginationKeys.forEach((key) => url.searchParams.delete(key));
					url.hash = '';
					const params = [...url.searchParams.entries()].sort(([ak,av],[bk,bv]) => ak === bk ? av.localeCompare(bv) : ak.localeCompare(bk));
					url.search = '';
					params.forEach(([key,value]) => url.searchParams.append(key,value));
					return url.href;
				} catch (_) { return ''; }
			};

			const resolveHref = (element, baseUrl) => {
				const raw = element?.getAttribute?.('href');
				if (!raw || raw === '#' || /^javascript:/i.test(raw)) return '';
				try { return new URL(raw, baseUrl).href; } catch (_) { return ''; }
			};

			const nextUrlFromDocument = (root, baseUrl, currentPage) => {
				const desiredPage = currentPage + 1;
				const family = signature(baseUrl);
				const head = root.querySelector('link[rel~="next"][href]');
				const headHref = resolveHref(head, baseUrl);
				if (headHref && signature(headHref) === family && pageFromUrl(headHref) === desiredPage) return headHref;

				const links = [...new Set(root.querySelectorAll(pagerLinkSelector))];
				for (const link of links) {
					const href = resolveHref(link, baseUrl);
					if (!href || signature(href) !== family || pageFromUrl(href) !== desiredPage) continue;
					const rel = (link.getAttribute('rel') || '').toLowerCase().split(/\s+/);
					const words = `${link.getAttribute('aria-label') || ''} ${link.getAttribute('title') || ''} ${link.textContent || ''}`.replace(/\s+/g,' ').trim().toLowerCase();
					if (rel.includes('next') || link.classList.contains('next') || /\b(next|siguiente|más|more)\b/.test(words)) return href;
				}
				for (const link of links) {
					const href = resolveHref(link, baseUrl);
					if (href && signature(href) === family && pageFromUrl(href) === desiredPage) return href;
				}
				return '';
			};

			const canonicalProductHref = (item) => {
				const link = item.querySelector('a.woocommerce-LoopProduct-link,a[href*="/producto/"],a[href*="/product/"]');
				if (!link?.href) return '';
				try {
					const url = new URL(link.href, currentUrl.href);
					url.search = ''; url.hash = '';
					return `${url.origin}${url.pathname.replace(/\/+$/,'/')}`;
				} catch (_) { return link.href; }
			};

			const productKey = (item) => {
				const postClass = [...item.classList].find((name) => /^(?:post|product)-\d+$/.test(name));
				if (postClass) return `class:${postClass}`;
				const ownId = item.getAttribute('data-product_id') || item.getAttribute('data-product-id');
				if (ownId) return `id:${ownId}`;
				const idNode = item.querySelector('[data-product_id],[data-product-id]');
				const nestedId = idNode?.getAttribute('data-product_id') || idNode?.getAttribute('data-product-id');
				if (nestedId) return `id:${nestedId}`;
				const href = canonicalProductHref(item);
				if (href) return `url:${href}`;
				const title = (item.querySelector('.woocommerce-loop-product__title,.product-title,h2,h3')?.textContent || '').replace(/\s+/g,' ').trim().toLowerCase();
				const price = (item.querySelector('.price')?.textContent || '').replace(/\s+/g,' ').trim().toLowerCase();
				const image = item.querySelector('img')?.getAttribute('src') || item.querySelector('img')?.getAttribute('data-src') || '';
				return title ? `fallback:${title}|${price}|${image}` : '';
			};

			const productItems = () => [...grid.querySelectorAll(':scope > li.product')];
			const knownProducts = new Set(productItems().map(productKey).filter(Boolean));
			const initialPage = pageFromUrl(currentUrl.href) || 1;
			const loadedPages = new Set([initialPage]);
			let highestPage = initialPage;
			let nextUrl = exactTotal && productItems().length >= exactTotal ? '' : nextUrlFromDocument(document,currentUrl.href,initialPage);
			let loading = false;
			let retryTimer = 0;
			let continuationTimer = 0;
			const preloadDistance = Math.max(1800,Math.min(3200,Math.round(window.innerHeight*2.6)));

			document.querySelectorAll(pagerScopeSelector).forEach((node) => node.classList.add('emo-catalog-native-pagination'));

			const state = document.createElement('div');
			state.className = 'emo-catalog-load-state';
			state.setAttribute('role','status');
			state.setAttribute('aria-live','polite');
			state.innerHTML = '<span class="emo-catalog-spinner" aria-hidden="true"></span><span class="emo-catalog-load-message"></span><button type="button" class="emo-catalog-load-button" hidden>Cargar más productos</button>';
			grid.insertAdjacentElement('afterend',state);
			const message = state.querySelector('.emo-catalog-load-message');
			const button = state.querySelector('.emo-catalog-load-button');

			const exactCountNodes = () => [...surface.querySelectorAll('.woocommerce-result-count')];
			let countSyncing = false;
			const lockCounts = () => {
				if (countSyncing) return;
				countSyncing = true;
				try {
					exactCountNodes().forEach((node) => {
						if ((node.textContent || '').replace(/\s+/g,' ').trim() !== exactLabel) node.textContent = exactLabel;
					});
				} finally { countSyncing = false; }
			};
			lockCounts();
			exactCountNodes().forEach((node) => new MutationObserver(lockCounts).observe(node,{childList:true,characterData:true,subtree:true}));
			document.body.addEventListener('emo:catalog-products-appended',lockCounts);

			const setState = (mode,text='') => {
				const active = mode === 'loading' || mode === 'retrying';
				const failure = mode === 'failure';
				state.classList.toggle('is-loading',active);
				state.classList.toggle('is-failure',failure);
				message.textContent = active || failure ? text : '';
				button.hidden = !failure || !nextUrl;
			};
			const showIdle = () => setState('idle');
			const showLoading = () => setState('loading','Cargando más productos…');
			const showRetrying = () => setState('retrying','Cargando más productos…');
			const showFailure = () => setState('failure','No se ha podido continuar la carga automática.');
			const showFinished = () => setState('finished');
			const nearViewport = () => { const rect=state.getBoundingClientRect(); return rect.top <= window.innerHeight+preloadDistance && rect.bottom >= -preloadDistance; };

			const fetchPage = async (url) => {
				const controller = new AbortController();
				const timeout = window.setTimeout(() => controller.abort(),8000);
				try {
					const response = await fetch(url,{credentials:'same-origin',signal:controller.signal,headers:{Accept:'text/html'}});
					if (!response.ok) throw new Error(`HTTP ${response.status}`);
					return {doc:new DOMParser().parseFromString(await response.text(),'text/html'),responseUrl:response.url || url};
				} finally { window.clearTimeout(timeout); }
			};

			const debugState = {};
			Object.defineProperties(debugState,{
				initialUrl:{enumerable:true,get:()=>currentUrl.href},nextUrl:{enumerable:true,get:()=>nextUrl},highestPage:{enumerable:true,get:()=>highestPage},
				shown:{enumerable:true,get:()=>productItems().length},unique:{enumerable:true,get:()=>knownProducts.size},loading:{enumerable:true,get:()=>loading}
			});
			window.__emoCatalogLoaderState = debugState;

			const scheduleContinuation = () => {
				window.clearTimeout(continuationTimer);
				continuationTimer = window.setTimeout(() => { if (!loading && nextUrl && nearViewport()) loadNext(true); },60);
			};

			const loadNext = async (allowRetry=true) => {
				if (loading || !nextUrl) return;
				if (exactTotal && productItems().length >= exactTotal) { nextUrl=''; showFinished(); return; }
				const requestedUrl = nextUrl;
				const requestedPage = pageFromUrl(requestedUrl);
				if (!requestedPage || requestedPage !== highestPage + 1 || loadedPages.has(requestedPage)) { nextUrl=''; showFinished(); return; }
				loading = true; showLoading();
				try {
					const {doc,responseUrl} = await fetchPage(requestedUrl);
					const sourceGrid = doc.querySelector(gridSelector);
					if (!sourceGrid) throw new Error('Product grid not found');
					let appended = 0;
					let remaining = exactTotal ? Math.max(0,exactTotal-productItems().length) : Number.POSITIVE_INFINITY;
					for (const item of [...sourceGrid.querySelectorAll(':scope > li.product')]) {
						if (remaining <= 0) break;
						const key = productKey(item);
						if (key && knownProducts.has(key)) continue;
						if (key) knownProducts.add(key);
						grid.append(document.importNode(item,true));
						appended += 1; remaining -= 1;
					}
					loadedPages.add(requestedPage); highestPage = requestedPage;
					lockCounts();
					if (!appended) { nextUrl=''; loading=false; showFinished(); return; }
					const responsePage = pageFromUrl(responseUrl) || requestedPage;
					const reachedTotal = exactTotal && productItems().length >= exactTotal;
					const candidate = reachedTotal ? '' : nextUrlFromDocument(doc,responseUrl,responsePage);
					const candidatePage = pageFromUrl(candidate);
					nextUrl = candidate && candidatePage === highestPage + 1 && !loadedPages.has(candidatePage) ? candidate : '';
					document.body.dispatchEvent(new CustomEvent('emo:catalog-products-appended',{detail:{count:appended,page:highestPage,nextUrl}}));
					window.__emoCatalogHistoryGuard?.restore?.();
					loading=false;
					if (nextUrl) { showIdle(); scheduleContinuation(); } else { showFinished(); }
				} catch (_) {
					loading=false; window.__emoCatalogHistoryGuard?.restore?.();
					if (allowRetry) {
						showRetrying(); window.clearTimeout(retryTimer);
						retryTimer=window.setTimeout(() => { if (!loading && nextUrl === requestedUrl) loadNext(false); },800);
					} else { showFailure(); }
				}
			};

			button.addEventListener('click',() => { window.clearTimeout(retryTimer); window.clearTimeout(continuationTimer); showIdle(); loadNext(false); });
			if (!nextUrl) { showFinished(); return; }
			if (!('IntersectionObserver' in window)) { showFailure(); return; }
			showIdle();
			const observer = new IntersectionObserver((entries) => { if (entries.some((entry) => entry.isIntersecting)) loadNext(true); },{rootMargin:`${preloadDistance}px 0px ${preloadDistance}px 0px`,threshold:.01});
			observer.observe(state);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
