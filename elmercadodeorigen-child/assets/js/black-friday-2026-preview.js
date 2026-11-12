/* Black Friday 2026: cortinilla de campaña compartida y control diario. */
(function () {
	'use strict';

	function init() {
		var overlay = document.getElementById('bf26-overlay');
		if (!overlay) { return; }

		var panel = overlay.querySelector('.bf26-overlay__panel');
		var enterButton = overlay.querySelector('[data-bf26-enter]');
		var previousFocus = null;
		var seenKey = 'emdo_bf2026_curtain_last_seen';
		var fallbackSessionKey = 'emdo_bf2026_curtain_session_seen';

		// Use the visitor's calendar date, not a rolling 24-hour timer.
		// This makes the curtain eligible again on the following local day.
		function todayLocal() {
			var now = new Date();
			return String(now.getFullYear()) + '-' +
				String(now.getMonth() + 1).padStart(2, '0') + '-' +
				String(now.getDate()).padStart(2, '0');
		}

		function curtainSeen() {
			var today = todayLocal();
			try { if (window.localStorage.getItem(seenKey) === today) { return true; } }
			catch (error) { /* Fall through to cookie and session storage. */ }
			try {
				if (document.cookie.split(';').some(function (part) {
					return part.trim() === seenKey + '=' + today;
				})) { return true; }
			} catch (error) { /* Cookie access may be disabled. */ }
			try { return window.sessionStorage.getItem(fallbackSessionKey) === today; }
			catch (error) { return false; }
		}

		function rememberDismissal() {
			var today = todayLocal();
			try { window.localStorage.setItem(seenKey, today); }
			catch (error) { /* Cookie/session fallback below. */ }
			try {
				var tomorrow = new Date();
				tomorrow.setHours(24, 0, 0, 0);
				document.cookie = seenKey + '=' + today +
					';expires=' + tomorrow.toUTCString() + ';path=/;SameSite=Lax';
			} catch (error) { /* Cookie access may be disabled. */ }
			try { window.sessionStorage.setItem(fallbackSessionKey, today); }
			catch (error) { /* The close action remains functional. */ }
		}

		function openCurtain() {
			// Record the impression as soon as it is shown. Navigation before
			// dismissal must not cause a second popup on another page.
			rememberDismissal();
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

		// Both the landing and the rest of the shop use the same daily key.
		// A user can reopen the presentation explicitly from the landing.
		if (!curtainSeen()) { openCurtain(); }
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
