(() => {
	'use strict';

	const body = document.body;
	const html = document.documentElement;
	const isHome = body.classList.contains('home');
	const header = document.querySelector('.site-header');
	const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	const makeKeyboardButton = (element, label) => {
		if (!element) return;
		element.setAttribute('role', 'button');
		element.setAttribute('tabindex', '0');
		element.setAttribute('aria-label', label);
		if (element.dataset.emoKeyboardButton === '1') return;
		element.dataset.emoKeyboardButton = '1';
		element.addEventListener('keydown', (event) => {
			if (event.key !== 'Enter' && event.key !== ' ') return;
			event.preventDefault();
			element.click();
		});
	};

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


	const removeProductHoverArtifacts = (root = document) => {
		const selector = '.product-loop-hover-image,.product-loop-action,.loop-add-to-cart-on-image';
		if (root instanceof Element && root.matches(selector)) root.remove();
		root.querySelectorAll?.(selector).forEach((element) => element.remove());
	};

	const labelWishlistLinks = (root = document) => {
		const selector = 'a.add_to_wishlist:not([aria-label])';
		const links = [];
		if (root instanceof Element && root.matches(selector)) links.push(root);
		root.querySelectorAll?.(selector).forEach((link) => links.push(link));
		links.forEach((link) => {
			const product = link.closest('li.product, div.product');
			const name = product?.querySelector('.woocommerce-loop-product__title, .product_title')?.textContent?.trim();
			link.setAttribute('aria-label', name ? `Añadir ${name} a favoritos` : 'Añadir producto a favoritos');
		});
	};

	const repairHustleDialogs = (root = document) => {
		root.querySelectorAll?.('.hustle-ui[role="dialog"]').forEach((dialog, index) => {
			const title = dialog.querySelector('.hustle-title');
			if (title) {
				title.id = title.id || `emo-newsletter-title-${index + 1}`;
				dialog.setAttribute('aria-labelledby', title.id);
			} else if (!dialog.hasAttribute('aria-label')) {
				dialog.setAttribute('aria-label', 'Suscripción a la newsletter');
			}
		});
	};

	body.classList.add('emo-js-ready');
	/*
	 * On the Home, legacy sticky-header cleanup used to remove classes, inline
	 * offsets and bumper DOM after the first frame. Even when those artefacts are
	 * harmless, changing them post-paint can move the entire hero and create a
	 * large CLS. Interior pages retain the historical cleanup unchanged.
	 */
	if (!isHome) {
		cleanLegacyHeaderArtifacts();
		window.addEventListener('load', cleanLegacyHeaderArtifacts, { once: true });
	}
	removeProductHoverArtifacts();
	labelWishlistLinks();
	repairHustleDialogs();

	const brandingLink = document.querySelector('.site-branding > .site-title > a');
	brandingLink?.querySelectorAll(':scope > .site-title').forEach((duplicate) => duplicate.remove());

	const mainNavigation = document.querySelector('.site-header .main-navigation');
	if (mainNavigation && !mainNavigation.id) mainNavigation.id = 'site-navigation';

	const mobileMenu = document.querySelector('.sidebar-menu');
	const mobileMenuTrigger = document.querySelector('.toggle-sidebar-menu-btn');
	let menuOverlay = document.querySelector('.emo-mobile-menu-overlay');

	if (mobileMenu && mobileMenuTrigger) {
		mobileMenu.id = mobileMenu.id || 'mobile-navigation';
		if (!menuOverlay) {
			menuOverlay = document.createElement('div');
			menuOverlay.className = 'emo-mobile-menu-overlay';
			menuOverlay.setAttribute('aria-hidden', 'true');
			body.append(menuOverlay);
		}

		mobileMenu.querySelectorAll('.dgwt-wcas-search-wrapp').forEach((search) => {
			search.closest('li')?.classList.add('emo-duplicate-search-item');
		});

		let closeButton = mobileMenu.querySelector('.elmercado-mobile-menu-close');
		if (!closeButton) {
			closeButton = document.createElement('button');
			closeButton.type = 'button';
			closeButton.className = 'elmercado-mobile-menu-close';
			closeButton.setAttribute('aria-label', 'Cerrar menú');
			closeButton.setAttribute('title', 'Cerrar menú');
			mobileMenu.prepend(closeButton);
		}

		makeKeyboardButton(mobileMenuTrigger, 'Abrir menú de navegación');
		mobileMenuTrigger.setAttribute('aria-controls', mobileMenu.id);

		const isOpen = () => html.classList.contains('sidebar-menu-open');
		const updateMenuState = () => {
			const open = isOpen();
			mobileMenuTrigger.setAttribute('aria-expanded', String(open));
			mobileMenuTrigger.setAttribute('aria-label', open ? 'Cerrar menú de navegación' : 'Abrir menú de navegación');
			mobileMenu.setAttribute('aria-hidden', String(!open));
			menuOverlay.setAttribute('aria-hidden', String(!open));
		};
		const closeMenu = () => {
			if (!isOpen()) return;
			mobileMenuTrigger.click();
			window.setTimeout(() => {
				if (!isOpen()) return;
				html.classList.remove('sidebar-menu-open');
				body.classList.remove('sidebar-menu-open');
				updateMenuState();
			}, 80);
		};

		new MutationObserver(updateMenuState).observe(html, { attributes: true, attributeFilter: ['class'] });
		mobileMenuTrigger.addEventListener('click', () => window.setTimeout(updateMenuState, 0));
		menuOverlay.addEventListener('click', closeMenu);
		closeButton.addEventListener('click', closeMenu);
		mobileMenu.addEventListener('click', (event) => {
			const link = event.target instanceof Element ? event.target.closest('.menu-item > a') : null;
			if (link && !link.parentElement?.classList.contains('menu-item-has-children')) closeMenu();
		});
		updateMenuState();

		document.addEventListener('keydown', (event) => {
			if (!isOpen()) return;
			if (event.key === 'Escape') {
				event.preventDefault();
				closeMenu();
				mobileMenuTrigger.focus();
				return;
			}
			if (event.key !== 'Tab') return;
			const focusable = [
				mobileMenuTrigger,
				...mobileMenu.querySelectorAll('a[href],button:not([disabled]),input:not([disabled]),[tabindex]:not([tabindex="-1"])')
			].filter((element) => element.getClientRects().length > 0);
			if (focusable.length < 2) return;
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
	const cartLink = document.querySelector('.site-tools .shopping-cart, .site-tools .shopping-bag-button, a.shopping-cart, a.shopping-bag-button');
	accountLink?.setAttribute('aria-label', 'Mi cuenta');

	let cartFrame = 0;
	const syncCart = () => {
		if (!cartLink) return;
		const counters = cartLink.querySelectorAll('.shop-cart-count,.shopping-cart-count,.cart-count,.count,.mini-cart-count,.elmercado-cart-direct-count');
		let positive = 0;
		counters.forEach((node) => {
			const match = (node.textContent || '').match(/\d+/);
			const count = match ? Number.parseInt(match[0], 10) : 0;
			const empty = !Number.isFinite(count) || count < 1;
			node.classList.toggle('is-zero', empty);
			node.classList.toggle('elmercado-cart-count-empty', empty);
			node.setAttribute('aria-hidden', empty ? 'true' : 'false');
			if (!empty) positive = Math.max(positive, count);
		});
		cartLink.setAttribute('aria-label', positive > 0 ? `Ver carrito, ${positive} productos` : 'Ver carrito');
	};
	const scheduleCartSync = () => {
		if (cartFrame) return;
		cartFrame = window.requestAnimationFrame(() => {
			syncCart();
			cartFrame = 0;
		});
	};
	if (cartLink) {
		syncCart();
		new MutationObserver(scheduleCartSync).observe(cartLink, { childList: true, subtree: true, characterData: true });
	}

	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart removed_from_cart wc_fragments_refreshed wc_fragments_loaded', scheduleCartSync);
		window.jQuery(document.body).on('added_to_cart', (_event, fragments) => {
			if (!fragments || typeof fragments !== 'object') return;
			Object.entries(fragments).forEach(([selector, markup]) => {
				window.jQuery(selector).replaceWith(markup);
			});
		});
	}

	/* Solo observamos zonas de producto reales; el directorio de productores queda fuera. */
	const productRoot = document.querySelector('ul.products, .product-page-container, .single-product');
	if (productRoot) {
		const pendingRoots = new Set();
		let interfaceFrame = 0;
		const flush = () => {
			pendingRoots.forEach((root) => {
				removeProductHoverArtifacts(root);
				labelWishlistLinks(root);
			});
			pendingRoots.clear();
			interfaceFrame = 0;
		};
		new MutationObserver((mutations) => {
			mutations.forEach((mutation) => mutation.addedNodes.forEach((node) => {
				if (node.nodeType === Node.ELEMENT_NODE) pendingRoots.add(node);
			}));
			if (pendingRoots.size && !interfaceFrame) interfaceFrame = window.requestAnimationFrame(flush);
		}).observe(productRoot, { childList: true, subtree: true });
	}

	if (reducedMotion) {
		document.querySelectorAll('.emo-reveal').forEach((element) => element.classList.add('is-visible'));
		return;
	}

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

	const revealObserver = new IntersectionObserver((entries) => {
		entries.forEach((entry) => {
			if (!entry.isIntersecting) return;
			entry.target.classList.add('is-visible');
			revealObserver.unobserve(entry.target);
		});
	}, { rootMargin: '0px 0px -7% 0px', threshold: 0.08 });

	revealElements.forEach((element) => revealObserver.observe(element));
})();
