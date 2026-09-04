(function () {
	'use strict';

	var config = window.ElMercadoAdsenseGeo || {};
	var eligible = false;
	var googleScriptPromise = null;
	var debugMode = /(?:^|[?&])adsense_debug=1(?:&|$)/.test(window.location.search);
	var debug = window.ElMercadoAdsenseGeoDebug = {
		phase: 'initializing',
		country: null,
		canBuy: null,
		showAds: null,
		attempt: 0,
		adRequestsPaused: true,
		inArticlePlaceholders: 0,
		inArticleRequested: 0,
		inArticleFilled: 0,
		inArticleError: null,
		error: null
	};

	function adsbygoogleQueue() {
		window.adsbygoogle = window.adsbygoogle || [];
		return window.adsbygoogle;
	}

	function googleScriptExists() {
		return !!document.querySelector(
			'script[src*="pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]'
		);
	}

	function loadGoogleScript() {
		if (googleScriptExists()) {
			return Promise.resolve();
		}

		if (googleScriptPromise) {
			return googleScriptPromise;
		}

		if (!config.publisher) {
			return Promise.reject(new Error('Missing AdSense publisher'));
		}

		googleScriptPromise = new Promise(function (resolve, reject) {
			var script = document.createElement('script');
			script.async = true;
			script.crossOrigin = 'anonymous';
			script.setAttribute('data-emo-adsense-dynamic', '1');
			script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(config.publisher);
			script.addEventListener('load', resolve, { once: true });
			script.addEventListener('error', function () {
				googleScriptPromise = null;
				reject(new Error('AdSense script failed to load'));
			}, { once: true });
			(document.head || document.documentElement).appendChild(script);
		});

		return googleScriptPromise;
	}

	function isPaused() {
		var queue = adsbygoogleQueue();
		// ADSENSE_PAUSE_DEBUG_010289: solo un 1 explicito significa pausado.
		// Tras inicializarse Google puede retirar la propiedad del array.
		return queue.pauseAdRequests === 1;
	}

	function updateInArticleDebug() {
		debug.inArticlePlaceholders = document.querySelectorAll('.emo-inarticle-ad-slot[data-emo-inarticle-ad]').length;
		debug.inArticleRequested = document.querySelectorAll('.emo-inarticle-ad-slot[data-emo-inarticle-hydrated="1"]').length;
		debug.inArticleFilled = document.querySelectorAll('.emo-inarticle-ad-slot.is-filled').length;
	}

	function renderDebugPanel() {
		if (!debugMode || !document.body) {
			return;
		}

		updateInArticleDebug();

		var panel = document.getElementById('elmercado-adsense-debug');
		if (!panel) {
			panel = document.createElement('div');
			panel.id = 'elmercado-adsense-debug';
			panel.setAttribute('role', 'status');
			panel.style.cssText = 'position:fixed;z-index:2147483647;left:12px;bottom:12px;max-width:420px;padding:12px 14px;background:#111;color:#fff;font:13px/1.45 monospace;border-radius:6px;box-shadow:0 2px 14px rgba(0,0,0,.35);white-space:pre-wrap;word-break:break-word;';
			document.body.appendChild(panel);
		}

		debug.adRequestsPaused = isPaused();
		panel.textContent = [
			'AdSense debug',
			'phase: ' + debug.phase,
			'country: ' + (debug.country || 'unknown'),
			'can_buy: ' + String(debug.canBuy),
			'show_ads: ' + String(debug.showAds),
			'attempt: ' + String(debug.attempt),
			'ad_requests_paused: ' + String(debug.adRequestsPaused),
			'inarticle_placeholders: ' + String(debug.inArticlePlaceholders),
			'inarticle_requested: ' + String(debug.inArticleRequested),
			'inarticle_filled: ' + String(debug.inArticleFilled),
			'inarticle_error: ' + (debug.inArticleError || 'none'),
			'google_script: ' + (googleScriptExists() ? 'present' : 'absent'),
			'adsbygoogle: ' + (typeof window.adsbygoogle !== 'undefined' ? 'present' : 'absent'),
			'error: ' + (debug.error || 'none')
		].join('\n');
	}

	function setPhase(phase) {
		debug.phase = phase;
		debug.updatedAt = new Date().toISOString();
		renderDebugPanel();
	}

	function pauseAdRequests() {
		adsbygoogleQueue().pauseAdRequests = 1;
		debug.adRequestsPaused = true;
	}

	function syncInArticleStatus(slot, ins) {
		var status = ins.getAttribute('data-ad-status');
		if (status === 'filled') {
			slot.classList.add('is-filled');
			slot.setAttribute('aria-hidden', 'false');
		} else {
			slot.classList.remove('is-filled');
			slot.setAttribute('aria-hidden', 'true');
		}
		updateInArticleDebug();
		renderDebugPanel();
	}

	function hydrateInArticleAds() {
		if (!eligible || !config.inArticleSlot || !config.publisher || !googleScriptExists()) {
			return;
		}

		var slots = Array.prototype.slice.call(
			document.querySelectorAll('.emo-inarticle-ad-slot[data-emo-inarticle-ad]:not([data-emo-inarticle-hydrated])')
		);

		debug.inArticlePlaceholders = document.querySelectorAll('.emo-inarticle-ad-slot[data-emo-inarticle-ad]').length;

		slots.forEach(function (slot) {
			var ins = document.createElement('ins');
			ins.className = 'adsbygoogle';
			ins.style.display = 'block';
			ins.style.textAlign = 'center';
			ins.setAttribute('data-ad-layout', 'in-article');
			ins.setAttribute('data-ad-format', 'fluid');
			ins.setAttribute('data-ad-client', config.publisher);
			ins.setAttribute('data-ad-slot', config.inArticleSlot);

			slot.setAttribute('data-emo-inarticle-hydrated', '1');
			slot.classList.add('is-requested');
			slot.appendChild(ins);

			var observer = new MutationObserver(function () {
				syncInArticleStatus(slot, ins);
			});
			observer.observe(ins, {
				attributes: true,
				attributeFilter: ['data-ad-status']
			});

			try {
				adsbygoogleQueue().push({});
			} catch (error) {
				debug.inArticleError = error && error.message ? error.message : String(error || 'unknown_error');
				slot.classList.remove('is-requested');
				slot.setAttribute('aria-hidden', 'true');
			}

			syncInArticleStatus(slot, ins);
		});

		updateInArticleDebug();
		renderDebugPanel();
	}

	function scheduleInArticleAds() {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', hydrateInArticleAds, { once: true });
		} else {
			hydrateInArticleAds();
		}
	}

	function activateAds() {
		adsbygoogleQueue().pauseAdRequests = 0;
		debug.adRequestsPaused = false;
		setPhase('eligible_loading_adsense');

		loadGoogleScript()
			.then(function () {
				setPhase('adsense_loaded');
				scheduleInArticleAds();
			})
			.catch(function (error) {
				eligible = false;
				pauseAdRequests();
				debug.error = error && error.message ? error.message : String(error || 'unknown_error');
				setPhase('adsense_load_error');
			});
	}

	if (debugMode) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', renderDebugPanel, { once: true });
		} else {
			renderDebugPanel();
		}
		window.setInterval(renderDebugPanel, 1000);
	}

	// Seguridad por defecto. No se descarga ningun recurso de Google hasta que
	// el endpoint confirme expresamente show_ads=true.
	pauseAdRequests();

	if (!config.endpoint || !config.publisher || typeof window.fetch !== 'function') {
		setPhase('configuration_error');
		debug.error = 'Missing endpoint, publisher or Fetch API';
		renderDebugPanel();
		return;
	}

	function requestEligibility(attempt) {
		debug.attempt = attempt;
		debug.error = null;
		setPhase('checking_eligibility');

		var separator = config.endpoint.indexOf('?') === -1 ? '?' : '&';
		var url = config.endpoint + separator + '_adsense_geo=' + Date.now();

		fetch(url, {
			method: 'GET',
			credentials: 'same-origin',
			cache: 'no-store',
			headers: {
				'Accept': 'application/json'
			}
		})
			.then(function (response) {
				if (!response.ok) {
					throw new Error('AdSense eligibility request failed with HTTP ' + response.status);
				}
				return response.json();
			})
			.then(function (data) {
				debug.country = data && data.country ? data.country : null;
				debug.canBuy = data && typeof data.can_buy !== 'undefined' ? data.can_buy : null;
				debug.showAds = data && data.show_ads === true;
				eligible = debug.showAds;

				if (eligible) {
					setPhase('eligible');
					activateAds();
				} else {
					pauseAdRequests();
					setPhase('not_eligible_no_adsense');
				}
			})
			.catch(function (error) {
				debug.error = error && error.message ? error.message : String(error || 'unknown_error');

				if (attempt < 2) {
					setPhase('eligibility_retry');
					window.setTimeout(function () {
						requestEligibility(attempt + 1);
					}, 250);
					return;
				}

				eligible = false;
				pauseAdRequests();
				setPhase('eligibility_error_no_adsense');
			});
	}

	requestEligibility(1);
}());
