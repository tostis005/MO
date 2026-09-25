(function () {
	'use strict';

	var config = window.ElMercadoAdsterraGeo || {};
	var debugMode = /(?:^|[?&])adsterra_debug=1(?:&|$)/.test(window.location.search);
	var debug = window.ElMercadoAdsterraGeoDebug = {
		phase: 'initializing',
		country: null,
		canBuy: null,
		showAds: null,
		hydrated: 0,
		error: null
	};

	var units = {
		'responsive-desktop': {
			key: '4e02d145fdb84842713b24a4bade6244',
			width: 728,
			height: 90
		},
		'responsive-mobile': {
			key: '3de18866861c081d6eb282ad00fe2bee',
			width: 320,
			height: 50
		},
		'rectangle': {
			key: 'c1f547d5b9552a71fae2de32c69f2d66',
			width: 300,
			height: 250
		},
		'tall-rectangle': {
			key: 'bd307b1985a219b2694f34d17ceebe8e',
			width: 160,
			height: 300
		},
		'skyscraper': {
			key: '040427a877ae83cac71362a8c92eb779',
			width: 160,
			height: 600
		},
		'footer-banner': {
			key: '985360811e3cd3c3d7d50e3a9ea81484',
			width: 468,
			height: 60
		}
	};

	function renderDebug() {
		if (!debugMode || !document.body) return;
		var panel = document.getElementById('elmercado-adsterra-debug');
		if (!panel) {
			panel = document.createElement('div');
			panel.id = 'elmercado-adsterra-debug';
			panel.style.cssText = 'position:fixed;z-index:2147483647;left:12px;bottom:12px;max-width:420px;padding:12px 14px;background:#111;color:#fff;font:13px/1.45 monospace;border-radius:6px;box-shadow:0 2px 14px rgba(0,0,0,.35);white-space:pre-wrap;word-break:break-word;';
			document.body.appendChild(panel);
		}
		panel.textContent = [
			'Adsterra debug',
			'phase: ' + debug.phase,
			'country: ' + (debug.country || 'unknown'),
			'can_buy: ' + String(debug.canBuy),
			'show_ads: ' + String(debug.showAds),
			'hydrated: ' + String(debug.hydrated),
			'error: ' + (debug.error || 'none')
		].join('\n');
	}

	function setPhase(phase) {
		debug.phase = phase;
		renderDebug();
	}

	function hydrateBanner(slot, unit, unitName) {
		if (!slot || !unit || !unitName || slot.getAttribute('data-emo-adsterra-hydrated') === '1') return;
		if (!config.frameEndpoint) return;

		var mount = slot.querySelector('.emo-adsterra-mount');
		if (!mount) return;

		var frame = document.createElement('iframe');
		frame.width = String(unit.width);
		frame.height = String(unit.height);
		frame.setAttribute('title', 'Publicidad');
		frame.setAttribute('scrolling', 'no');
		frame.setAttribute('frameborder', '0');
		frame.style.cssText = 'display:block;border:0;max-width:100%;overflow:hidden;background:transparent;';

		var separator = config.frameEndpoint.indexOf('?') === -1 ? '?' : '&';
		frame.src = config.frameEndpoint + separator + 'emo_adsterra_frame=' + encodeURIComponent(unitName) + '&_=' + Date.now();

		mount.appendChild(frame);
		slot.classList.add('is-eligible');
		slot.setAttribute('aria-hidden', 'false');
		slot.setAttribute('data-emo-adsterra-hydrated', '1');
		debug.hydrated += 1;
	}

	function hydrateNative(slot) {
		if (!slot || slot.getAttribute('data-emo-adsterra-hydrated') === '1') return;

		var mount = slot.querySelector('.emo-adsterra-mount');
		if (!mount) return;

		var container = document.createElement('div');
		container.id = 'container-a83b8ce6c354e77b2ae5f266936bd60f';
		mount.appendChild(container);

		var script = document.createElement('script');
		script.async = true;
		script.setAttribute('data-cfasync', 'false');
		script.src = 'https://pl31502847.profitableratecpmnetwork.com/a83b8ce6c354e77b2ae5f266936bd60f/invoke.js';
		mount.insertBefore(script, container);

		slot.classList.add('is-eligible');
		slot.setAttribute('aria-hidden', 'false');
		slot.setAttribute('data-emo-adsterra-hydrated', '1');
		debug.hydrated += 1;
	}

	function hydrateEligibleSlots() {
		var slots = Array.prototype.slice.call(document.querySelectorAll('[data-emo-adsterra-slot]'));

		slots.forEach(function (slot) {
			var type = slot.getAttribute('data-emo-adsterra-slot');

			if (type === 'native') {
				hydrateNative(slot);
				return;
			}

			if (type === 'skyscraper' && window.matchMedia('(max-width: 1160px)').matches) return;
			if (type === 'tall-rectangle' && window.matchMedia('(max-width: 767px)').matches) return;
			if (type === 'footer-banner' && window.matchMedia('(max-width: 519px)').matches) return;

			if (type === 'responsive-top') {
				var responsiveName = window.matchMedia('(max-width: 767px)').matches
					? 'responsive-mobile'
					: 'responsive-desktop';
				hydrateBanner(slot, units[responsiveName], responsiveName);
				return;
			}

			if (units[type]) hydrateBanner(slot, units[type], type);
		});

		setPhase('eligible_adsterra_loaded');
	}

	function requestEligibility(attempt) {
		setPhase('checking_eligibility');
		var separator = config.endpoint.indexOf('?') === -1 ? '?' : '&';
		var url = config.endpoint + separator + '_blog_ad_geo=' + Date.now();

		fetch(url, {
			method: 'GET',
			credentials: 'same-origin',
			cache: 'no-store',
			headers: { 'Accept': 'application/json' }
		})
			.then(function (response) {
				if (!response.ok) throw new Error('Ad eligibility request failed with HTTP ' + response.status);
				return response.json();
			})
			.then(function (data) {
				debug.country = data && data.country ? data.country : null;
				debug.canBuy = data && typeof data.can_buy !== 'undefined' ? data.can_buy : null;
				debug.showAds = data && data.show_ads === true;

				if (debug.showAds) {
					setPhase('eligible');
					hydrateEligibleSlots();
				} else {
					setPhase('not_eligible_no_ads');
				}
			})
			.catch(function (error) {
				debug.error = error && error.message ? error.message : String(error || 'unknown_error');
				if (attempt < 2) {
					window.setTimeout(function () { requestEligibility(attempt + 1); }, 250);
					return;
				}
				setPhase('eligibility_error_no_ads');
			});
	}

	if (debugMode) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', renderDebug, { once: true });
		} else {
			renderDebug();
		}
	}

	if (!config.endpoint || !config.frameEndpoint || typeof window.fetch !== 'function') {
		debug.error = 'Missing endpoint, frame endpoint or Fetch API';
		setPhase('configuration_error');
		return;
	}

	requestEligibility(1);
}());
