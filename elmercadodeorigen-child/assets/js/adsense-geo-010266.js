(function () {
	'use strict';

	var config = window.ElMercadoAdsenseGeo || {};
	var eligible = false;
	var loaded = false;
	var debug = window.ElMercadoAdsenseGeoDebug = {
		phase: 'initializing',
		country: null,
		canBuy: null,
		showAds: null,
		attempt: 0,
		error: null
	};

	function setPhase(phase) {
		debug.phase = phase;
		debug.updatedAt = new Date().toISOString();
	}

	if (!config.endpoint || !config.publisher || typeof window.fetch !== 'function') {
		setPhase('configuration_error');
		debug.error = 'Missing endpoint, publisher or Fetch API';
		return;
	}

	/**
	 * Carga AdSense en cuanto la visita es geograficamente elegible.
	 *
	 * No hacemos una segunda puerta basada en la categoria "Advertisement" de
	 * WebToffee: la CMP/TCF comunica a Google la eleccion del visitante y Google
	 * selecciona el modo permitido. data-wcc="necessary" evita que el bloqueador
	 * automatico de WebToffee retenga el propio tag antes de leer esas senales.
	 */
	function loadAdsenseIfEligible() {
		if (!eligible || loaded) {
			return;
		}

		var existing = document.querySelector('script[src*="pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]');
		if (existing) {
			loaded = true;
			setPhase('adsense_already_present');
			return;
		}

		loaded = true;
		setPhase('adsense_loading');

		var script = document.createElement('script');
		script.async = true;
		script.crossOrigin = 'anonymous';
		script.setAttribute('data-wcc', 'necessary');
		script.setAttribute('data-ad-client', config.publisher);
		script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(config.publisher);
		script.onload = function () {
			loaded = true;
			setPhase('adsense_loaded');
		};
		script.onerror = function () {
			loaded = false;
			setPhase('adsense_script_error');
			debug.error = 'Google AdSense script failed to load';
		};
		document.head.appendChild(script);
	}

	function requestEligibility(attempt) {
		debug.attempt = attempt;
		debug.error = null;
		setPhase('checking_eligibility');

		fetch(config.endpoint, {
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
					loadAdsenseIfEligible();
				} else {
					setPhase('not_eligible');
				}
			})
			.catch(function (error) {
				debug.error = error && error.message ? error.message : String(error || 'unknown_error');

				// Un segundo intento evita que un fallo REST transitorio bloquee los ads.
				if (attempt < 2) {
					setPhase('eligibility_retry');
					window.setTimeout(function () {
						requestEligibility(attempt + 1);
					}, 250);
					return;
				}

				// Si tampoco podemos validar la geografia al reintentar, mantenemos
				// la regla comercial de no mostrar publicidad en un pais desconocido.
				eligible = false;
				setPhase('eligibility_error');
			});
	}

	requestEligibility(1);
}());
