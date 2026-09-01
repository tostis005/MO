(function () {
	'use strict';

	var config = window.ElMercadoAdsenseGeo || {};
	var eligible = false;
	var loaded = false;

	if (!config.endpoint || !config.publisher || typeof window.fetch !== 'function') {
		return;
	}

	/**
	 * Carga AdSense cuando la visita es geograficamente elegible.
	 *
	 * No hacemos una segunda puerta basada en la categoria "Advertisement" de
	 * WebToffee: la CMP/TFC debe comunicar a Google la eleccion del visitante y
	 * Google selecciona el modo permitido (personalizado, no personalizado o
	 * limited ads). data-wcc="necessary" evita que el bloqueador automatico de
	 * WebToffee retenga el propio tag de AdSense antes de que Google pueda leer
	 * esas senales de privacidad.
	 */
	function loadAdsenseIfEligible() {
		if (!eligible || loaded) {
			return;
		}

		loaded = true;

		var script = document.createElement('script');
		script.async = true;
		script.crossOrigin = 'anonymous';
		script.setAttribute('data-wcc', 'necessary');
		script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(config.publisher);
		script.onerror = function () {
			loaded = false;
		};
		document.head.appendChild(script);
	}

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
				throw new Error('AdSense eligibility request failed');
			}
			return response.json();
		})
		.then(function (data) {
			eligible = data && data.show_ads === true;
			loadAdsenseIfEligible();
		})
		.catch(function () {
			// Fallo seguro: si no podemos validar geografia, no cargamos AdSense.
			eligible = false;
		});
}());
