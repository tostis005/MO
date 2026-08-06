(() => {
	'use strict';

	const configuration = window.elMercadoCommerce || {};
	const menu = document.querySelector('#mobile-navigation, .sidebar-menu');
	const root = document.documentElement;

	/**
	 * Los controles de un panel cerrado no deben permanecer en el árbol de foco.
	 */
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

	const showToast = (productName = '') => {
		removeToast();

		const toast = document.createElement('div');
		const cartUrl = configuration.cartUrl
			|| document.querySelector('.site-header a.cart-contents, .site-header .shopping-cart a')?.href
			|| `${window.location.origin}/carrito/`;
		const safeName = productName.trim();

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
				<a class="emo-cart-toast__link" href="${cartUrl}">Ver carrito</a>
				<button class="emo-cart-toast__close" type="button" aria-label="Cerrar confirmación">×</button>
			</span>
		`;

		toast.querySelector('.emo-cart-toast__close')?.addEventListener('click', removeToast);
		document.body.append(toast);
		window.requestAnimationFrame(() => toast.classList.add('is-visible'));
		closeTimer = window.setTimeout(removeToast, 8000);
	};

	if (window.jQuery) {
		window.jQuery(document.body).on('added_to_cart', (_event, _fragments, _cartHash, button) => {
			const element = button?.get?.(0) || button?.[0] || null;
			const product = element?.closest('li.product, .product');
			const name = product?.querySelector('.woocommerce-loop-product__title, .product_title, h2, h3')?.textContent || '';
			showToast(name);
		});
	}

	/* Confirmación para adiciones con recarga completa. */
	if (document.querySelector('.woocommerce-message')?.textContent?.match(/añadid|carrito/i)) {
		const message = document.querySelector('.woocommerce-message')?.textContent || '';
		showToast(message.replace(/ver carrito/gi, '').trim().slice(0, 120));
	}
})();
