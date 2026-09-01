(function () {
	'use strict';

	var config = window.ElMercadoAdsenseGeo || {};
	var eligible = false;
	var loaded = false;

	if (!config.endpoint || !config.publisher || typeof window.fetch !== 'function') {
		return;
	}

	function cookieValue(name) {
		var prefix = encodeURIComponent(name) + '=';
		var cookies = document.cookie ? document.cookie.split(';') : [];

		for (var i = 0; i < cookies.length; i += 1) {
			var item = cookies[i].trim();
			if (item.indexOf(prefix) === 0) {
				return decodeURIComponent(item.slice(prefix.length));
			}
		}
		return '';
	}

	function webToffeeConsentGranted() {
		// WebToffee 3.x.
		if (typeof window.getWccConsent === 'function') {
			try {
				var consent = window.getWccConsent();
				var categories = consent && consent.categories ? consent.categories : {};
				if (categories.advertisement === true || categories.marketing === true || categories.non_necessary === true) {
					return true;
				}
			} catch (error) {
				// Seguimos con las comprobaciones compatibles con la version legacy.
			}
		}

		// Compatibilidad con WebToffee legacy (Cookie Law Info).
		if (window.CLI && window.CLI.consent) {
			if (
				window.CLI.consent.advertisement === true ||
				window.CLI.consent.marketing === true ||
				window.CLI.consent.non_necessary === true
			) {
				return true;
			}
		}

		var consentCookies = [
			'cookielawinfo-checkbox-advertisement',
			'cookielawinfo-checkbox-marketing',
			'cookielawinfo-checkbox-non-necessary'
		];

		for (var i = 0; i < consentCookies.length; i += 1) {
			if (cookieValue(consentCookies[i]).toLowerCase() === 'yes') {
				return true;
			}
		}

		return false;
	}

	function loadAdsenseIfAllowed() {
		if (!eligible || loaded || !webToffeeConsentGranted()) {
			return;
		}

		loaded = true;
		var script = document.createElement('script');
		script.async = true;
		script.crossOrigin = 'anonymous';
		script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(config.publisher);
		document.head.appendChild(script);
	}

	// API moderna de WebToffee: reacciona al consentimiento sin recargar.
	document.addEventListener('wcc_consent_update', function () {
		window.setTimeout(loadAdsenseIfAllowed, 0);
	});
	document.addEventListener('_wccBannerVisible', function () {
		window.setTimeout(loadAdsenseIfAllowed, 0);
	});

	// Fallback para configuraciones antiguas del banner, sin sustituir callbacks
	// globales que puedan usar otras integraciones.
	document.addEventListener('click', function (event) {
		var target = event.target && event.target.closest ? event.target.closest('.cli_action_button, .wt-cli-accept-all-btn, .wt-cli-save-preferences-btn') : null;
		if (target) {
			window.setTimeout(loadAdsenseIfAllowed, 150);
			window.setTimeout(loadAdsenseIfAllowed, 600);
		}
	});

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
			loadAdsenseIfAllowed();
		})
		.catch(function () {
			// Fallo seguro: si no podemos validar geografia, no cargamos AdSense.
			eligible = false;
		});
}());
