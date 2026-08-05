(() => {
	'use strict';

	const body = document.body;
	const html = document.documentElement;
	const header = document.querySelector('.site-header');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	let scrollFrame = 0;

	const makeKeyboardButton = (element, label) => {
		if (!element) {
			return;
		}

		element.setAttribute('role', 'button');
		element.setAttribute('tabindex', '0');
		element.setAttribute('aria-label', label);
		element.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter' && event.key !== ' ') {
				return;
			}

			event.preventDefault();
			element.click();
		});
	};

	/**
	 * Retira las capas de hover heredadas de Woostify. El botón inferior de la
	 * tarjeta mantiene toda la funcionalidad de compra sin duplicar controles.
	 */
	const removeProductHoverArtifacts = (root = document) => {
		root.querySelectorAll?.([
			'.product-loop-hover-image',
			'.product-loop-action',
			'.loop-add-to-cart-on-image'
		].join(',')).forEach((element) => element.remove());
	};

	/**
	 * El JavaScript personalizado antiguo fijaba la cabecera, añadía un bumper
	 * y escribía márgenes inline. Ese archivo ya no se carga; esta limpieza se
	 * conserva al iniciar por si algún caché entrega temporalmente su marcado.
	 */
	const cleanLegacyHeaderArtifacts = () => {
		const headerInner = document.querySelector('.site-header-inner');
		const oldTopbar = document.querySelector('.topbar');
		const content = document.querySelector('#content');

		headerInner?.classList.remove('fija');
		headerInner?.style.removeProperty('top');
		oldTopbar?.classList.remove('fija');
		content?.style.removeProperty('margin-top');
		document.querySelectorAll('.site-header-inner + .bumper').forEach((bumper) => bumper.remove());
	};

	const updateScrollState = () => {
		const scrolled = window.scrollY > 12;
		body.classList.toggle('is-scrolled', scrolled);
		header?.classList.toggle('is-scrolled', scrolled);
		scrollFrame = 0;
	};

	const requestScrollUpdate = () => {
		if (scrollFrame) {
			return;
		}

		scrollFrame = window.requestAnimationFrame(updateScrollState);
	};

	body.classList.add('emo-js-ready');
	cleanLegacyHeaderArtifacts();
	removeProductHoverArtifacts();
	updateScrollState();
	window.addEventListener('scroll', requestScrollUpdate, { passive: true });
	window.addEventListener('load', cleanLegacyHeaderArtifacts, { once: true });
	window.setTimeout(cleanLegacyHeaderArtifacts, 500);

	/* Evita el título duplicado que podía insertar una versión cacheada del JS. */
	const brandingLink = document.querySelector('.site-branding > .site-title > a');
	const cleanDuplicatedBrand = () => {
		brandingLink?.querySelectorAll(':scope > .site-title').forEach((duplicate) => duplicate.remove());
	};

	if (brandingLink) {
		cleanDuplicatedBrand();
		const brandingObserver = new MutationObserver(cleanDuplicatedBrand);
		brandingObserver.observe(brandingLink, { childList: true });
		window.setTimeout(() => brandingObserver.disconnect(), 4000);
	}

	/* Navegación y controles de cabecera. */
	const mainNavigation = document.querySelector('.site-header .main-navigation');
	if (mainNavigation && !mainNavigation.id) {
		mainNavigation.id = 'site-navigation';
	}

	const mobileMenu = document.querySelector('.sidebar-menu');
	const mobileMenuTrigger = document.querySelector('.toggle-sidebar-menu-btn');
	const menuOverlay = document.createElement('div');

	if (mobileMenu && mobileMenuTrigger) {
		mobileMenu.id = mobileMenu.id || 'mobile-navigation';
		menuOverlay.className = 'emo-mobile-menu-overlay';
		menuOverlay.setAttribute('aria-hidden', 'true');
		body.append(menuOverlay);

		mobileMenu.querySelectorAll('.dgwt-wcas-search-wrapp').forEach((search) => {
			search.closest('li')?.classList.add('emo-duplicate-search-item');
		});

		makeKeyboardButton(mobileMenuTrigger, 'Abrir menú de navegación');
		mobileMenuTrigger.setAttribute('aria-controls', mobileMenu.id);

		const updateMenuState = () => {
			const open = html.classList.contains('sidebar-menu-open');
			mobileMenuTrigger.setAttribute('aria-expanded', String(open));
			mobileMenuTrigger.setAttribute('aria-label', open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
			mobileMenu.setAttribute('aria-hidden', String(!open));
			menuOverlay.setAttribute('aria-hidden', String(!open));

			if (open) {
				window.setTimeout(() => mobileMenu.querySelector('input[type="search"]')?.focus(), 220);
			}
		};

		const menuObserver = new MutationObserver(updateMenuState);
		menuObserver.observe(html, { attributes: true, attributeFilter: ['class'] });
		mobileMenuTrigger.addEventListener('click', () => window.setTimeout(updateMenuState, 0));
		menuOverlay.addEventListener('click', () => mobileMenuTrigger.click());
		updateMenuState();

		document.addEventListener('keydown', (event) => {
			if (!html.classList.contains('sidebar-menu-open')) {
				return;
			}

			if (event.key === 'Escape') {
				event.preventDefault();
				mobileMenuTrigger.click();
				mobileMenuTrigger.focus();
				return;
			}

			if (event.key !== 'Tab') {
				return;
			}

			const focusable = [
				mobileMenuTrigger,
				...mobileMenu.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), [tabindex]:not([tabindex="-1"])')
			].filter((element) => element.getClientRects().length > 0);

			if (focusable.length < 2) {
				return;
			}

			const first = focusable[0];
			const last = focusable[focusable.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		});
	}

	makeKeyboardButton(document.querySelector('.site-header .dgwt-wcas-search-icon'), 'Buscar productos');
	makeKeyboardButton(document.querySelector('.site-tools > .header-search-icon'), 'Buscar productos');

	const accountLink = document.querySelector('.site-tools .my-account-icon');
	const cartLink = document.querySelector('.site-tools .shopping-cart');
	accountLink?.setAttribute('aria-label', 'Mi cuenta');
	cartLink?.setAttribute('aria-label', 'Ver carrito');

	/* Etiquetas accesibles para enlaces de favoritos generados por AJAX. */
	const labelWishlistLinks = (root = document) => {
		root.querySelectorAll?.('a.add_to_wishlist:not([aria-label])').forEach((link) => {
			const product = link.closest('li.product, div.product');
			const name = product?.querySelector('.woocommerce-loop-product__title, .product_title')?.textContent?.trim();
			link.setAttribute('aria-label', name ? `Añadir ${name} a favoritos` : 'Añadir producto a favoritos');
		});
	};

	/* Nombre accesible y contraste estable para el popup de newsletter. */
	const repairHustleDialogs = (root = document) => {
		root.querySelectorAll?.('.hustle-ui[role="dialog"]').forEach((dialog, index) => {
			const title = dialog.querySelector('.hustle-title');
			if (title) {
				title.id = title.id || `emo-newsletter-title-${index + 1}`;
				dialog.setAttribute('aria-labelledby', title.id);
			} else {
				dialog.setAttribute('aria-label', 'Suscripción a la newsletter');
			}
		});
	};

	labelWishlistLinks();
	repairHustleDialogs();

	let mutationFrame = 0;
	const interfaceObserver = new MutationObserver(() => {
		if (mutationFrame) {
			return;
		}

		mutationFrame = window.requestAnimationFrame(() => {
			removeProductHoverArtifacts();
			labelWishlistLinks();
			repairHustleDialogs();
			mutationFrame = 0;
		});
	});
	interfaceObserver.observe(body, { childList: true, subtree: true });

	/*
	 * La portada no inicia wc-cart-fragments. WooCommerce ya devuelve fragmentos
	 * tras una compra AJAX; aplicamos únicamente esos fragmentos cuando existen.
	 */
	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', (_event, fragments) => {
			if (!fragments || typeof fragments !== 'object') {
				return;
			}

			Object.entries(fragments).forEach(([selector, markup]) => {
				window.jQuery(selector).replaceWith(markup);
			});
		});
	}

	if (reducedMotion) {
		document.querySelectorAll('.emo-reveal').forEach((element) => element.classList.add('is-visible'));
		return;
	}

	/* Movimiento solo en bloques editoriales, nunca en controles transaccionales. */
	const revealElements = document.querySelectorAll([
		'.emo-section-heading',
		'.emo-category-card',
		'.emo-trust article',
		'.emo-story__values article',
		'.emo-vendor-cta__inner',
		'.entry-content > .wp-block-group',
		'.emo-reveal'
	].join(','));

	if (!('IntersectionObserver' in window) || revealElements.length === 0) {
		revealElements.forEach((element) => element.classList.add('is-visible'));
		return;
	}

	revealElements.forEach((element, index) => {
		element.classList.add('emo-reveal');
		element.style.transitionDelay = `${Math.min(index % 6, 5) * 45}ms`;
	});

	const observer = new IntersectionObserver(
		(entries) => {
			entries.forEach((entry) => {
				if (!entry.isIntersecting) {
					return;
				}

				entry.target.classList.add('is-visible');
				observer.unobserve(entry.target);
			});
		},
		{ rootMargin: '0px 0px -7% 0px', threshold: 0.08 }
	);

	revealElements.forEach((element) => observer.observe(element));
})();
