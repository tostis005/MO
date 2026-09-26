(function () {
	'use strict';

	var config = window.ElMercadoAdsterraGeo || {};
	var frameCounter = 0;
	var adBlockDetected = false;
	var adBlockCheckDone = false;
	var hydrationPending = false;
	var fallbackTimer = null;
	var fallbackStarted = false;
	var attemptedSlots = 0;
	var resolvedSlots = 0;
	var renderedSlots = 0;
	var debugMode = /(?:^|[?&])adsterra_debug=1(?:&|$)/.test(window.location.search);
	var debug = window.ElMercadoAdsterraGeoDebug = {
		phase: 'initializing',
		country: null,
		canBuy: null,
		showAds: null,
		hydrated: 0,
		attempted: 0,
		resolved: 0,
		rendered: 0,
		fallback: false,
		fallbackReason: null,
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

	function adsbygoogleQueue() {
		window.adsbygoogle = window.adsbygoogle || [];
		return window.adsbygoogle;
	}

	function googleScriptExists() {
		return !!document.querySelector('script[src*="pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]');
	}

	function loadAdsenseFallbackScript() {
		adsbygoogleQueue().pauseAdRequests = 0;

		if (googleScriptExists()) {
			return Promise.resolve();
		}

		if (!config.adsensePublisher) {
			return Promise.reject(new Error('Missing AdSense fallback publisher'));
		}

		return new Promise(function (resolve, reject) {
			var script = document.createElement('script');
			script.async = true;
			script.crossOrigin = 'anonymous';
			script.setAttribute('data-emo-adsense-fallback', '1');
			script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(config.adsensePublisher);
			script.addEventListener('load', resolve, { once: true });
			script.addEventListener('error', function () {
				reject(new Error('AdSense fallback script failed to load'));
			}, { once: true });
			(document.head || document.documentElement).appendChild(script);
		});
	}

	function cleanupAdsenseFallbackSlot(slot) {
		if (!slot) return;
		slot.classList.remove('is-adsense-fallback', 'is-adsense-filled');
		slot.setAttribute('aria-hidden', 'true');
		var nativeShell = slot.closest('.emo-adsterra-native-shell');
		if (nativeShell) nativeShell.classList.remove('is-adsense-fallback');
	}

	function prepareAdsenseFallbackSlots() {
		var candidates = Array.prototype.slice.call(document.querySelectorAll(
			'.emo-adsterra-slot--rectangle, .emo-adsterra-slot--tall-rectangle'
		));

		if (!candidates.length) {
			var nativeSlot = document.querySelector('.emo-adsterra-slot--native');
			if (nativeSlot) candidates.push(nativeSlot);
		}

		if (!candidates.length) {
			var topSlot = document.querySelector('.emo-adsterra-slot--responsive-top');
			if (topSlot) candidates.push(topSlot);
		}

		return candidates.slice(0, 3).map(function (slot) {
			var mount = slot.querySelector('.emo-adsterra-mount');
			if (!mount) return null;

			mount.innerHTML = '';
			var ins = document.createElement('ins');
			ins.className = 'adsbygoogle';
			ins.style.display = 'block';
			ins.style.textAlign = 'center';
			ins.setAttribute('data-ad-layout', 'in-article');
			ins.setAttribute('data-ad-format', 'fluid');
			ins.setAttribute('data-ad-client', config.adsensePublisher);
			ins.setAttribute('data-ad-slot', config.adsenseInArticleSlot);
			mount.appendChild(ins);

			slot.classList.remove('is-eligible');
			slot.classList.add('is-adsense-fallback');
			slot.setAttribute('aria-hidden', 'false');

			var nativeShell = slot.closest('.emo-adsterra-native-shell');
			if (nativeShell) nativeShell.classList.add('is-adsense-fallback');

			return { slot: slot, ins: ins };
		}).filter(Boolean);
	}

	function requestAdsenseFallbackUnits(units) {
		units.forEach(function (unit) {
			var settled = false;
			var observer = new MutationObserver(function () {
				var status = unit.ins.getAttribute('data-ad-status');
				if (status === 'filled' || status === 'unfill-optimized') {
					settled = true;
					unit.slot.classList.add('is-adsense-filled');
					unit.slot.setAttribute('aria-hidden', 'false');
					return;
				}
				if (status === 'unfilled') {
					settled = true;
					cleanupAdsenseFallbackSlot(unit.slot);
				}
			});
			observer.observe(unit.ins, {
				attributes: true,
				attributeFilter: ['data-ad-status']
			});

			try {
				adsbygoogleQueue().push({});
			} catch (error) {
				settled = true;
				cleanupAdsenseFallbackSlot(unit.slot);
				debug.error = error && error.message ? error.message : String(error || 'adsense_fallback_push_error');
			}

			window.setTimeout(function () {
				if (!settled && !unit.ins.getAttribute('data-ad-status')) {
					cleanupAdsenseFallbackSlot(unit.slot);
				}
				observer.disconnect();
			}, 10000);
		});
	}

	function startAdsenseFallback(reason) {
		if (fallbackStarted || adBlockDetected || renderedSlots > 0) return;
		if (!config.adsensePublisher || !config.adsenseInArticleSlot) {
			debug.error = 'Missing AdSense fallback configuration';
			setPhase('adsterra_failed_no_fallback_config');
			return;
		}

		fallbackStarted = true;
		if (fallbackTimer) {
			window.clearTimeout(fallbackTimer);
			fallbackTimer = null;
		}

		debug.fallback = true;
		debug.fallbackReason = reason || 'adsterra_no_render';
		document.documentElement.classList.add('emo-adsterra-adsense-fallback');
		setPhase('adsterra_failed_loading_adsense');

		document.querySelectorAll('[data-emo-adsterra-slot]').forEach(function (slot) {
			collapseSlot(slot);
			var mount = slot.querySelector('.emo-adsterra-mount');
			if (mount) mount.innerHTML = '';
		});

		var units = prepareAdsenseFallbackSlots();

		loadAdsenseFallbackScript()
			.then(function () {
				setPhase('adsense_fallback_loaded');
				requestAdsenseFallbackUnits(units);
			})
			.catch(function (error) {
				debug.error = error && error.message ? error.message : String(error || 'adsense_fallback_load_error');
				units.forEach(function (unit) { cleanupAdsenseFallbackSlot(unit.slot); });
				setPhase('adsense_fallback_error');
			});
	}

	function registerSlotAttempt(slot) {
		if (!slot || slot.getAttribute('data-emo-adsterra-state')) return;
		slot.setAttribute('data-emo-adsterra-state', 'pending');
		attemptedSlots += 1;
		debug.attempted = attemptedSlots;
		renderDebug();
	}

	function markSlotResolved(slot, state) {
		if (!slot || slot.getAttribute('data-emo-adsterra-state') !== 'pending') return;
		slot.setAttribute('data-emo-adsterra-state', state);
		resolvedSlots += 1;
		if (state === 'rendered') renderedSlots += 1;
		debug.resolved = resolvedSlots;
		debug.rendered = renderedSlots;
		renderDebug();

		if (attemptedSlots > 0 && resolvedSlots >= attemptedSlots && renderedSlots === 0) {
			startAdsenseFallback('all_adsterra_slots_failed');
		}
	}

	function scheduleAdsenseFallback() {
		if (fallbackStarted || fallbackTimer || attemptedSlots < 1) return;
		var timeout = parseInt(config.fallbackTimeout, 10);
		if (!timeout || timeout < 2500) timeout = 6000;
		fallbackTimer = window.setTimeout(function () {
			fallbackTimer = null;
			if (renderedSlots === 0) {
				startAdsenseFallback('adsterra_render_timeout');
			}
		}, timeout);
	}

	function finishAdBlockCheck(blocked) {
		adBlockDetected = blocked === true;
		adBlockCheckDone = true;

		if (adBlockDetected) {
			if (fallbackTimer) window.clearTimeout(fallbackTimer);
			fallbackTimer = null;
			document.documentElement.classList.add('emo-adblock-detected');
			debug.phase = 'adblock_detected_no_ads';
			document.querySelectorAll('[data-emo-adsterra-slot]').forEach(function (slot) {
				collapseSlot(slot);
			});
			hydrationPending = false;
			renderDebug();
			return;
		}

		document.documentElement.classList.remove('emo-adblock-detected');
		if (hydrationPending) {
			hydrationPending = false;
			hydrateEligibleSlots();
		}
	}

	function detectCosmeticAdBlock() {
		if (!document.body) return;

		var bait = document.createElement('div');
		bait.className = 'adsbox ad-banner ad-placement ad-unit ad-zone';
		bait.setAttribute('aria-hidden', 'true');
		bait.style.cssText = 'position:absolute!important;left:-10000px!important;top:-10000px!important;width:10px!important;height:10px!important;pointer-events:none!important;';
		document.body.appendChild(bait);

		window.requestAnimationFrame(function () {
			window.requestAnimationFrame(function () {
				var style = window.getComputedStyle(bait);
				var blocked = style.display === 'none'
					|| style.visibility === 'hidden'
					|| bait.offsetWidth === 0
					|| bait.offsetHeight === 0;
				bait.remove();
				finishAdBlockCheck(blocked);
			});
		});
	}

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
			'attempted: ' + String(debug.attempted),
			'resolved: ' + String(debug.resolved),
			'rendered: ' + String(debug.rendered),
			'fallback: ' + String(debug.fallback),
			'fallback_reason: ' + (debug.fallbackReason || 'none'),
			'error: ' + (debug.error || 'none')
		].join('\n');
	}

	function setPhase(phase) {
		debug.phase = phase;
		renderDebug();
	}

	function revealSlot(slot) {
		if (adBlockDetected) {
			collapseSlot(slot);
			return;
		}
		if (!slot || slot.classList.contains('is-eligible')) return;
		slot.classList.add('is-eligible');
		slot.setAttribute('aria-hidden', 'false');
		var nativeShell = slot.closest('.emo-adsterra-native-shell');
		if (nativeShell) nativeShell.classList.add('is-eligible');
		debug.hydrated += 1;
		markSlotResolved(slot, 'rendered');
		renderDebug();
	}

	function collapseSlot(slot) {
		if (!slot) return;
		slot.classList.remove('is-eligible');
		slot.setAttribute('aria-hidden', 'true');
		var nativeShell = slot.closest('.emo-adsterra-native-shell');
		if (nativeShell) nativeShell.classList.remove('is-eligible');
	}

	window.addEventListener('message', function (event) {
		if (event.origin !== window.location.origin || !event.data) return;
		if (event.data.type !== 'emo-adsterra-rendered' && event.data.type !== 'emo-adsterra-blocked') return;

		var token = typeof event.data.token === 'string' ? event.data.token : '';
		if (!token) return;

		var frames = document.querySelectorAll('iframe[data-emo-adsterra-token]');
		for (var i = 0; i < frames.length; i += 1) {
			if (frames[i].getAttribute('data-emo-adsterra-token') !== token) continue;
			var slot = frames[i].closest('[data-emo-adsterra-slot]');
			if (event.data.type === 'emo-adsterra-rendered') {
				revealSlot(slot);
			} else {
				markSlotResolved(slot, 'failed');
				collapseSlot(slot);
			}
			break;
		}
	});

	function hydrateBanner(slot, unit, unitName) {
		if (!slot || !unit || !unitName || slot.getAttribute('data-emo-adsterra-hydrated') === '1') return;
		if (!config.frameEndpoint) return;

		var mount = slot.querySelector('.emo-adsterra-mount');
		if (!mount) return;

		frameCounter += 1;
		var token = unitName + '-' + Date.now().toString(36) + '-' + frameCounter.toString(36);
		var frame = document.createElement('iframe');
		frame.width = String(unit.width);
		frame.height = String(unit.height);
		frame.setAttribute('title', 'Publicidad');
		frame.setAttribute('scrolling', 'no');
		frame.setAttribute('frameborder', '0');
		frame.setAttribute('data-emo-adsterra-token', token);
		frame.style.cssText = 'display:block;border:0;max-width:100%;overflow:hidden;background:transparent;';

		var separator = config.frameEndpoint.indexOf('?') === -1 ? '?' : '&';
		frame.src = config.frameEndpoint + separator
			+ 'emo_adsterra_frame=' + encodeURIComponent(unitName)
			+ '&emo_adsterra_token=' + encodeURIComponent(token)
			+ '&_=' + Date.now();

		registerSlotAttempt(slot);
		slot.setAttribute('data-emo-adsterra-hydrated', '1');
		mount.appendChild(frame);
	}

	function hydrateNative(slot) {
		if (!slot || slot.getAttribute('data-emo-adsterra-hydrated') === '1') return;

		var mount = slot.querySelector('.emo-adsterra-mount');
		if (!mount) return;

		var container = document.createElement('div');
		container.id = 'container-a83b8ce6c354e77b2ae5f266936bd60f';
		mount.appendChild(container);
		registerSlotAttempt(slot);
		slot.setAttribute('data-emo-adsterra-hydrated', '1');

		function nativeHasCreative() {
			return container.children.length > 0 || (container.textContent || '').trim() !== '';
		}

		function revealNativeWhenReady() {
			if (!nativeHasCreative()) return;
			revealSlot(slot);
			observer.disconnect();
		}

		var observer = new MutationObserver(revealNativeWhenReady);
		observer.observe(container, {
			childList: true,
			subtree: true,
			attributes: true
		});

		var script = document.createElement('script');
		script.async = true;
		script.setAttribute('data-cfasync', 'false');
		script.src = 'https://pl31502847.profitableratecpmnetwork.com/a83b8ce6c354e77b2ae5f266936bd60f/invoke.js';
		script.addEventListener('error', function () {
			markSlotResolved(slot, 'failed');
			collapseSlot(slot);
		}, { once: true });
		mount.insertBefore(script, container);
	}

	function hydrateEligibleSlots() {
		if (!adBlockCheckDone) {
			hydrationPending = true;
			return;
		}
		if (adBlockDetected) {
			setPhase('adblock_detected_no_ads');
			return;
		}

		var slots = Array.prototype.slice.call(document.querySelectorAll('[data-emo-adsterra-slot]'));

		slots.forEach(function (slot) {
			var type = slot.getAttribute('data-emo-adsterra-slot');

			if (type === 'native') {
				hydrateNative(slot);
				return;
			}

			if (type === 'skyscraper' && window.matchMedia('(max-width: 1160px)').matches) return;
			if ((type === 'rectangle' || type === 'tall-rectangle') && window.matchMedia('(max-width: 767px)').matches) return;
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

		scheduleAdsenseFallback();
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

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', detectCosmeticAdBlock, { once: true });
	} else {
		detectCosmeticAdBlock();
	}

	if (!config.endpoint || !config.frameEndpoint || typeof window.fetch !== 'function') {
		debug.error = 'Missing endpoint, frame endpoint or Fetch API';
		setPhase('configuration_error');
		return;
	}

	requestEligibility(1);
}());
