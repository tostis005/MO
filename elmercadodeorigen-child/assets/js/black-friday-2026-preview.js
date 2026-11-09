/* Solo vista previa: no añade cortinillas al resto del sitio. */
(function () {
	'use strict';

	function init() {
		var overlay = document.getElementById('bf26-overlay');
		if (!overlay) { return; }

		var panel = overlay.querySelector('.bf26-overlay__panel');
		var enterButton = overlay.querySelector('[data-bf26-enter]');
		var previousFocus = null;
		var dismissedKey = 'emdo_bf2026_curtain_dismissed';

		function curtainSeen() {
			try { return window.sessionStorage.getItem(dismissedKey) === '1'; }
			catch (error) { return false; }
		}

		function rememberDismissal() {
			try { window.sessionStorage.setItem(dismissedKey, '1'); }
			catch (error) { /* Storage may be unavailable; navigation still works. */ }
		}

		function openCurtain() {
			previousFocus = document.activeElement;
			overlay.hidden = false;
			document.body.classList.add('bf26-modal-open');
			if (panel) { panel.focus({ preventScroll: true }); }
		}

		function closeCurtain(goToOffers) {
			overlay.hidden = true;
			document.body.classList.remove('bf26-modal-open');
			rememberDismissal();
			if (goToOffers) {
				var section = document.getElementById('ofertas');
				if (section) {
					section.scrollIntoView({ behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth', block: 'start' });
				}
			} else if (previousFocus && typeof previousFocus.focus === 'function') {
				previousFocus.focus({ preventScroll: true });
			}
		}

		Array.prototype.forEach.call(document.querySelectorAll('[data-bf26-close]'), function (button) {
			button.addEventListener('click', function () { closeCurtain(false); });
		});
		Array.prototype.forEach.call(document.querySelectorAll('[data-bf26-open]'), function (button) {
			button.addEventListener('click', openCurtain);
		});
		if (enterButton) {
			enterButton.addEventListener('click', function () { closeCurtain(true); });
		}
		var backdrop = overlay.querySelector('.bf26-overlay__backdrop');
		if (backdrop) {
			backdrop.addEventListener('click', function () { closeCurtain(false); });
		}

		document.addEventListener('keydown', function (event) {
			if (overlay.hidden) { return; }
			if (event.key === 'Escape') {
				event.preventDefault();
				closeCurtain(false);
				return;
			}
			if (event.key !== 'Tab') { return; }
			var focusables = Array.prototype.slice.call(overlay.querySelectorAll('button:not([disabled]),a[href],input:not([disabled])'));
			if (!focusables.length) { event.preventDefault(); return; }
			var first = focusables[0], last = focusables[focusables.length - 1];
			if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
			else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
		});

		function copyLegacy(value) {
			var input = document.createElement('textarea');
			input.value = value;
			input.style.position = 'fixed';
			input.style.opacity = '0';
			document.body.appendChild(input);
			input.select();
			var copied = document.execCommand && document.execCommand('copy');
			document.body.removeChild(input);
			return !!copied;
		}

		Array.prototype.forEach.call(document.querySelectorAll('[data-bf26-copy]'), function (button) {
			button.addEventListener('click', function () {
				var value = button.getAttribute('data-bf26-copy');
				if (!value) { return; }
				var original = button.innerHTML;
				var report = function (success) {
					button.textContent = success ? '¡Copiado!' : 'Selecciona: ' + value;
					setTimeout(function () { button.innerHTML = original; }, 1800);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(value).then(function () { report(true); }).catch(function () { report(copyLegacy(value)); });
				} else {
					report(copyLegacy(value));
				}
			});
		});

		// La presentación se muestra una vez por sesión. Al volver de la tienda
		// el menú, el carrito y la navegación siguen disponibles sin interrupción.
		// Se puede abrir de nuevo con el botón al final de la landing.
		if (!curtainSeen()) { openCurtain(); }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
