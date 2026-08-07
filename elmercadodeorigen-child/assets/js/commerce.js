(() => {
	'use strict';

	const configuration = window.elMercadoCommerce || {};
	const menu = document.querySelector('#mobile-navigation, .sidebar-menu');
	const root = document.documentElement;
	const productSurface = document.body.classList.contains('woocommerce-shop')
		|| document.body.classList.contains('single-product')
		|| document.body.classList.contains('post-type-archive-product');

	const updateMenuInertState = () => {
		if (!menu) {
			return;
		}

		const open = root.classList.contains('sidebar-menu-open');
		menu.toggleAttribute('inert', !open);

		if ('inert' in menu) {
			menu.inert = !open;
		}
	};

	if (menu) {
		updateMenuInertState();
		new MutationObserver(updateMenuInertState).observe(root, {
			attributes: true,
			attributeFilter: ['class']
		});
	}

	const updateCartAccessibleName = () => {
		const cartButton = document.querySelector('.shopping-bag-button.shopping-cart, a.shopping-cart');
		if (!cartButton) {
			return;
		}

		const countNode = cartButton.querySelector('.shop-cart-count, .cart-count, .count');
		const count = countNode?.textContent?.trim() || '';
		cartButton.setAttribute('aria-label', count ? `Ver carrito, ${count}` : 'Ver carrito');
	};

	updateCartAccessibleName();
	const cartButton = document.querySelector('.shopping-bag-button.shopping-cart, a.shopping-cart');
	if (cartButton) {
		new MutationObserver(updateCartAccessibleName).observe(cartButton, {
			childList: true,
			characterData: true,
			subtree: true
		});
	}

	/**
	 * Normaliza el carrito lateral que Woostify reconstruye mediante fragmentos.
	 * Elimina la representación duplicada del cierre, muestra la cantidad completa
	 * y mantiene un único camino hacia la página de carrito.
	 */
	const polishMiniCart = (scope = document) => {
		const panel = scope.matches?.('#shop-cart-sidebar')
			? scope
			: scope.querySelector?.('#shop-cart-sidebar') || document.querySelector('#shop-cart-sidebar');

		if (!panel) {
			return;
		}

		panel.setAttribute('role', 'dialog');
		panel.setAttribute('aria-modal', 'true');
		panel.setAttribute('aria-label', 'Carrito de la compra');

		const closeButton = panel.querySelector('#close-cart-sidebar-btn');
		closeButton?.setAttribute('aria-label', 'Cerrar carrito');
		closeButton?.setAttribute('title', 'Cerrar carrito');

		panel.querySelectorAll('.woocommerce-mini-cart-item, .mini_cart_item').forEach((item) => {
			item.classList.add('emo-mini-cart-item');

			const productLink = [...item.querySelectorAll(':scope > a')].find((link) => !link.matches('.remove, .remove_from_cart_button'));
			const productName = productLink?.textContent?.trim().replace(/\s+/g, ' ') || 'producto';

			if (productLink) {
				productLink.classList.add('emo-mini-cart-product-link');

				let nameNode = productLink.querySelector('.emo-mini-cart-product-name');
				if (!nameNode) {
					const directTextNodes = [...productLink.childNodes].filter((node) => node.nodeType === Node.TEXT_NODE);
					const directName = directTextNodes.map((node) => node.textContent).join(' ').trim().replace(/\s+/g, ' ');

					if (directName) {
						nameNode = document.createElement('span');
						nameNode.className = 'emo-mini-cart-product-name';
						nameNode.textContent = directName;
						directTextNodes.forEach((node) => node.remove());
						productLink.append(nameNode);
					}
				}
			}

			const removeLinks = [...item.querySelectorAll(':scope > a.remove, :scope > a.remove_from_cart_button')];
			removeLinks.slice(1).forEach((duplicate) => duplicate.remove());

			const removeLink = removeLinks[0];
			if (removeLink) {
				[...removeLink.childNodes].forEach((node) => {
					if (node.nodeType === Node.TEXT_NODE && node.textContent.trim().match(/^[×x]$/i)) {
						node.remove();
					}
				});
				removeLink.setAttribute('aria-label', `Eliminar ${productName} del carrito`);
				removeLink.setAttribute('title', `Eliminar ${productName}`);
				removeLink.querySelectorAll('svg, .woostify-svg-icon').forEach((icon) => {
					icon.setAttribute('aria-hidden', 'true');
					icon.setAttribute('focusable', 'false');
				});
			}

			const quantity = item.querySelector('.mini-cart-quantity');
			const input = quantity?.querySelector('input.qty');
			if (quantity) {
				quantity.setAttribute('role', 'group');
				quantity.setAttribute('aria-label', `Cantidad de ${productName}`);
			}
			if (input) {
				input.setAttribute('aria-label', `Cantidad de ${productName}`);
				input.setAttribute('title', `Cantidad de ${productName}`);
				input.setAttribute('inputmode', 'numeric');
			}

			quantity?.querySelector('[data-qty="minus"]')?.setAttribute('aria-label', `Reducir cantidad de ${productName}`);
			quantity?.querySelector('[data-qty="plus"]')?.setAttribute('aria-label', `Aumentar cantidad de ${productName}`);
		});

		/* El flujo aprobado pasa primero por el carrito completo. */
		panel.querySelectorAll('.woocommerce-mini-cart__buttons a.checkout').forEach((checkout) => checkout.remove());

		const cartLink = panel.querySelector('.woocommerce-mini-cart__buttons a:not(.checkout)');
		if (cartLink) {
			cartLink.textContent = 'Ver carrito';
			cartLink.setAttribute('aria-label', 'Ir a la página del carrito');
		}
	};

	polishMiniCart();
	let miniCartFrame = 0;
	const miniCartObserver = new MutationObserver((mutations) => {
		if (!mutations.some((mutation) => [...mutation.addedNodes].some((node) => node.nodeType === Node.ELEMENT_NODE))) {
			return;
		}
		if (miniCartFrame) {
			return;
		}
		miniCartFrame = window.requestAnimationFrame(() => {
			polishMiniCart();
			updateCartAccessibleName();
			miniCartFrame = 0;
		});
	});
	miniCartObserver.observe(document.body, { childList: true, subtree: true });

	if (window.jQuery) {
		window.jQuery(document.body).on('wc_fragments_loaded wc_fragments_refreshed added_to_cart removed_from_cart', () => {
			window.requestAnimationFrame(() => polishMiniCart());
		});
	}

	let closeTimer = 0;

	const removeToast = () => {
		window.clearTimeout(closeTimer);
		const toast = document.querySelector('.emo-cart-toast');

		if (!toast) {
			return;
		}

		toast.classList.remove('is-visible');
		window.setTimeout(() => toast.remove(), 190);
	};

	const escapeHtml = (value) => String(value).replace(/[&<>'"]/g, (character) => ({
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		"'": '&#039;',
		'"': '&quot;'
	})[character]);

	const showToast = (productName = '') => {
		if (!productSurface) {
			return;
		}

		removeToast();

		const toast = document.createElement('div');
		const cartUrl = configuration.cartUrl
			|| document.querySelector('.site-header a.cart-contents, .site-header .shopping-cart a')?.href
			|| `${window.location.origin}/carrito/`;
		const safeName = escapeHtml(productName.trim());

		toast.className = 'emo-cart-toast';
		toast.setAttribute('role', 'status');
		toast.setAttribute('aria-live', 'polite');
		toast.innerHTML = `
			<span class="emo-cart-toast__icon" aria-hidden="true">✓</span>
			<span class="emo-cart-toast__copy">
				<strong>Producto añadido al carrito</strong>
				<span>${safeName || 'Tu selección se ha guardado correctamente.'}</span>
			</span>
			<span class="emo-cart-toast__actions">
				<a class="emo-cart-toast__link" href="${escapeHtml(cartUrl)}">Ver carrito</a>
				<button class="emo-cart-toast__close" type="button" aria-label="Cerrar confirmación">×</button>
			</span>
		`;

		toast.querySelector('.emo-cart-toast__close')?.addEventListener('click', removeToast);
		document.body.append(toast);
		window.requestAnimationFrame(() => toast.classList.add('is-visible'));
		closeTimer = window.setTimeout(removeToast, 8000);
	};

	/* La confirmación se crea únicamente desde el evento real de WooCommerce. */
	if (productSurface && window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', (_event, _fragments, _cartHash, button) => {
			const element = button?.get?.(0) || button?.[0] || null;
			const product = element?.closest('li.product, .product');
			const name = product?.querySelector('.woocommerce-loop-product__title, .product_title, h2, h3')?.textContent || '';
			updateCartAccessibleName();
			showToast(name);
		});
	}
})();
