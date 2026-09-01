(function () {
	'use strict';

	var config = window.ElMercadoAdsenseGeo || {};
	var eligible = false;
	var debugMode = /(?:^|[?&])adsense_debug=1(?:&|$)/.test(window.location.search);
	var debug = window.ElMercadoAdsenseGeoDebug = {
		phase: 'initializing',
		country: null,
		canBuy: null,
		showAds: null,
		attempt: 0,
		adRequestsPaused: true,
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

	function isPaused() {
		var queue = adsbygoogleQueue();
		return queue.pauseAdRequests !== 0;
	}

	function renderDebugPanel() {
		if (!debugMode || !document.body) {
			return;
		}

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

	function resumeAdRequests() {
		adsbygoogleQueue().pauseAdRequests = 0;
		debug.adRequestsPaused = false;
		setPhase('ads_requests_resumed');
	}

	if (debugMode) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', renderDebugPanel, { once: true });
		} else {
			renderDebugPanel();
		}
		window.setInterval(renderDebugPanel, 1000);
	}

	// Seguridad por defecto: aunque otro script alterase el estado antes de
	// resolver la geografia, mantenemos las solicitudes pausadas.
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
					resumeAdRequests();
				} else {
					pauseAdRequests();
					setPhase('not_eligible_ads_paused');
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
				setPhase('eligibility_error_ads_paused');
			});
	}

	requestEligibility(1);
}());
