(function () {
	'use strict';

	var config = window.ElMercadoAdsterraGeo || {};
	var frameCounter = 0;
	var adBlockDetected = false;
	var adBlockCheckDone = false;
	var adBlockCheckStarted = false;
	var finalHydrationScheduled = false;
	var hydrationPending = false;
	var slotObserver = null;
	var googleAnchorPromise = null;
	var lazySlotObserver = null;
	var attemptedSlots = 0;
	var resolvedSlots = 0;
	var renderedSlots = 0;
	var geoCacheKey = 'emo-blog-ad-eligibility-v3';
	var geoCacheMaxAge = 30 * 60 * 1000;
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
		invokeLoaded: 0,
		creativeInserted: 0,
		googleAnchor: false,
		geoSource: null,
		eligibilityMs: null,
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

	function googleScriptExists() {
		return !!document.querySelector('script[src*="pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"]');
	}

	function preconnect(origin) {
		if (!origin || !document.head) return;
		if (document.querySelector('link[data-emo-ad-preconnect="' + origin + '"]')) return;
		var link = document.createElement('link');
		link.rel = 'preconnect';
		link.href = origin;
		link.crossOrigin = 'anonymous';
		link.setAttribute('data-emo-ad-preconnect', origin);
		document.head.appendChild(link);
	}

	function preconnectAdsterra() {
		preconnect('https://www.highrevenueformat.com');
		preconnect('https://pl31502847.profitableratecpmnetwork.com');
	}

	function preconnectGoogle() {
		preconnect('https://pagead2.googlesyndication.com');
		preconnect('https://googleads.g.doubleclick.net');
	}

	function loadGoogleAnchorScript() {
		if (googleScriptExists()) {
			debug.googleAnchor = true;
			renderDebug();
			return Promise.resolve();
		}

		if (googleAnchorPromise) return googleAnchorPromise;
		if (!config.adsensePublisher) {
			return Promise.reject(new Error('Missing AdSense publisher for anchor ad'));
		}

		googleAnchorPromise = new Promise(function (resolve, reject) {
			var script = document.createElement('script');
			script.async = true;
			script.crossOrigin = 'anonymous';
			script.setAttribute('data-emo-google-anchor', '1');
			script.src = 'https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=' + encodeURIComponent(config.adsensePublisher);
			script.addEventListener('load', function () {
				debug.googleAnchor = true;
				renderDebug();
				resolve();
			}, { once: true });
			script.addEventListener('error', function () {
				googleAnchorPromise = null;
				reject(new Error('Google anchor script failed to load'));
			}, { once: true });
			(document.head || document.documentElement).appendChild(script);
		});

		return googleAnchorPromise;
	}


	function registerSlotAttempt(slot) {
		if (!slot || slot.getAttribute('data-emo-adsterra-state')) return;
		slot.setAttribute('data-emo-adsterra-state', 'pending');
		attemptedSlots += 1;
		debug.attempted = attemptedSlots;
		renderDebug();

		var type = slot.getAttribute('data-emo-adsterra-slot') || '';
		var configured = parseInt(config.slotTimeout, 10);
		var timeout = configured && configured >= 4500 ? configured : 7000;
		if (type === 'responsive-top') timeout = Math.min(timeout, 7000);
		if (type === 'native') timeout = Math.max(timeout, 8000);

		window.setTimeout(function () {
			if (slot.getAttribute('data-emo-adsterra-state') !== 'pending') return;
			var mount = slot.querySelector('.emo-adsterra-mount');
			if (mount) mount.innerHTML = '';
			collapseSlot(slot);
			markSlotResolved(slot, 'failed');
		}, timeout);
	}

	function markSlotResolved(slot, state) {
		if (!slot || slot.getAttribute('data-emo-adsterra-state') !== 'pending') return;
		slot.setAttribute('data-emo-adsterra-state', state);
		resolvedSlots += 1;
		if (state === 'rendered') renderedSlots += 1;
		debug.resolved = resolvedSlots;
		debug.rendered = renderedSlots;
		renderDebug();

		if (state === 'failed') {
			collapseSlot(slot);
			setPhase(renderedSlots > 0 ? 'eligible_adsterra_partial_fill' : 'eligible_adsterra_no_fill');
		}
	}

	function finishAdBlockCheck(blocked) {
		adBlockDetected = blocked === true;
		adBlockCheckDone = true;

		if (adBlockDetected) {
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
		if (adBlockCheckStarted || !document.body) return;
		adBlockCheckStarted = true;

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

	function scheduleCosmeticAdBlockCheck() {
		if (document.body) {
			detectCosmeticAdBlock();
			return;
		}

		if (!document.documentElement) {
			window.setTimeout(scheduleCosmeticAdBlockCheck, 0);
			return;
		}

		var observer = new MutationObserver(function () {
			if (!document.body) return;
			observer.disconnect();
			detectCosmeticAdBlock();
		});
		observer.observe(document.documentElement, { childList: true });
	}

	function watchForAdSlots() {
		if (slotObserver || !document.documentElement) return;
		slotObserver = new MutationObserver(function () {
			if (debug.showAds === true && adBlockCheckDone && !adBlockDetected) {
				hydrateEligibleSlots();
			}
		});
		slotObserver.observe(document.documentElement, {
			childList: true,
			subtree: true
		});
	}

	function scheduleFinalHydration() {
		if (finalHydrationScheduled || document.readyState !== 'loading') return;
		finalHydrationScheduled = true;
		document.addEventListener('DOMContentLoaded', function () {
			finalHydrationScheduled = false;
			if (slotObserver) {
				slotObserver.disconnect();
				slotObserver = null;
			}
			if (debug.showAds === true && adBlockCheckDone && !adBlockDetected) {
				hydrateEligibleSlots();
			}
		}, { once: true });
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
			'invoke_loaded: ' + String(debug.invokeLoaded),
			'creative_inserted: ' + String(debug.creativeInserted),
			'rendered_visible: ' + String(debug.rendered),
			'google_anchor: ' + String(debug.googleAnchor),
			'geo_source: ' + (debug.geoSource || 'none'),
			'eligibility_ms: ' + String(debug.eligibilityMs),
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
		slot.classList.remove('is-loading');
		slot.classList.add('is-eligible');
		slot.setAttribute('aria-hidden', 'false');
		var nativeShell = slot.closest('.emo-adsterra-native-shell');
		if (nativeShell) {
			nativeShell.classList.remove('is-loading');
			nativeShell.classList.add('is-eligible');
		}
		debug.hydrated += 1;
		markSlotResolved(slot, 'rendered');
		renderDebug();
	}

	function collapseSlot(slot) {
		if (!slot) return;
		slot.classList.remove('is-eligible', 'is-loading');
		slot.setAttribute('aria-hidden', 'true');
		var nativeShell = slot.closest('.emo-adsterra-native-shell');
		if (nativeShell) nativeShell.classList.remove('is-eligible', 'is-loading');
	}

	window.addEventListener('message', function (event) {
		if (event.origin !== window.location.origin || !event.data) return;

		var allowed = {
			'emo-adsterra-script-loaded': true,
			'emo-adsterra-creative-inserted': true,
			'emo-adsterra-rendered': true,
			'emo-adsterra-blocked': true
		};
		if (!allowed[event.data.type]) return;

		var token = typeof event.data.token === 'string' ? event.data.token : '';
		if (!token) return;

		var frames = document.querySelectorAll('iframe[data-emo-adsterra-token]');
		for (var i = 0; i < frames.length; i += 1) {
			if (frames[i].getAttribute('data-emo-adsterra-token') !== token) continue;
			var frame = frames[i];
			var slot = frame.closest('[data-emo-adsterra-slot]');

			if (event.data.type === 'emo-adsterra-script-loaded') {
				if (frame.getAttribute('data-emo-adsterra-script-loaded') !== '1') {
					frame.setAttribute('data-emo-adsterra-script-loaded', '1');
					debug.invokeLoaded += 1;
					renderDebug();
				}
				break;
			}

			if (event.data.type === 'emo-adsterra-creative-inserted') {
				if (frame.getAttribute('data-emo-adsterra-creative-inserted') !== '1') {
					frame.setAttribute('data-emo-adsterra-creative-inserted', '1');
					debug.creativeInserted += 1;
					renderDebug();
				}
				break;
			}

			if (event.data.type === 'emo-adsterra-rendered') {
				frame.setAttribute('data-emo-adsterra-visible', '1');
				revealSlot(slot);
			} else {
				markSlotResolved(slot, 'failed');
				collapseSlot(slot);
			}
			break;
		}
	});

	function bannerDocument(unit, token) {
		var options = {
			key: unit.key,
			format: 'iframe',
			height: unit.height,
			width: unit.width,
			params: {}
		};

		var detector = [
			'(function(){',
			'"use strict";',
			'var token=' + JSON.stringify(token) + ';',
			'var settled=false,inserted=false;',
			'function post(type){try{window.parent.postMessage({type:type,token:token},window.location.origin);}catch(e){}}',
			'function markInserted(){if(inserted)return;inserted=true;post("emo-adsterra-creative-inserted");}',
			'function rendered(){if(settled)return;settled=true;markInserted();post("emo-adsterra-rendered");}',
			'function watch(node){if(!node||node.nodeType!==1||node.getAttribute("data-emo-adsterra-watch")==="1")return;var tag=node.tagName;node.setAttribute("data-emo-adsterra-watch","1");markInserted();',
			'if(tag==="IMG"){if(node.complete&&node.naturalWidth>0){rendered();return;}node.addEventListener("load",function(){if(node.naturalWidth>0)rendered();},{once:true});return;}',
			'if(tag==="VIDEO"){if(node.readyState>=2){rendered();return;}node.addEventListener("loadeddata",rendered,{once:true});return;}',
			'if(tag==="IFRAME"||tag==="OBJECT"||tag==="EMBED"){node.addEventListener("load",function(){setTimeout(rendered,60);},{once:true});setTimeout(function(){if(!settled&&node.isConnected){var src=node.getAttribute("src")||node.getAttribute("data")||"";if(src&&src!=="about:blank")rendered();}},900);return;}',
			'setTimeout(function(){if(!settled&&node.isConnected)rendered();},120);}',
			'function inspect(){if(settled)return;var nodes=document.body.querySelectorAll("iframe,img,video,object,embed,canvas,svg,a[href]");for(var i=0;i<nodes.length;i+=1)watch(nodes[i]);}',
			'window.__emoAdsterraInvokeLoaded=function(){post("emo-adsterra-script-loaded");inspect();};',
			'window.__emoAdsterraInvokeFailed=function(){if(settled)return;settled=true;post("emo-adsterra-blocked");};',
			'new MutationObserver(inspect).observe(document.body,{childList:true,subtree:true,attributes:true,attributeFilter:["src","href","data"]});',
			'setTimeout(function(){if(!settled){settled=true;post("emo-adsterra-blocked");}},6200);',
			'inspect();',
			'}());'
		].join('');

		return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">' +
			'<style>html,body{margin:0;padding:0;overflow:hidden;background:transparent}body{display:flex;justify-content:center;align-items:flex-start}</style>' +
			'</head><body>' +
			'<script>window.atOptions=' + JSON.stringify(options) + ';' + detector + '<\\/script>' +
			'<script src="https://www.highrevenueformat.com/' + encodeURIComponent(unit.key) + '/invoke.js" onload="window.__emoAdsterraInvokeLoaded()" onerror="window.__emoAdsterraInvokeFailed()"><\\/script>' +
			'</body></html>';
	}

	function hydrateBanner(slot, unit, unitName) {
		if (!slot || !unit || !unitName || slot.getAttribute('data-emo-adsterra-hydrated') === '1') return;

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
		frame.setAttribute('loading', 'eager');
		if (unitName.indexOf('responsive-') === 0) frame.setAttribute('fetchpriority', 'high');
		frame.style.cssText = 'display:block;border:0;max-width:100%;overflow:hidden;background:transparent;';
		frame.srcdoc = bannerDocument(unit, token);

		registerSlotAttempt(slot);
		slot.setAttribute('data-emo-adsterra-unit', unitName);
		slot.setAttribute('data-emo-adsterra-size', String(unit.width) + 'x' + String(unit.height));
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
			if (container.querySelector('iframe,img,video,object,embed,canvas,svg,a[href]')) return true;
			return Array.prototype.slice.call(container.children).some(function (node) {
				return node.tagName !== 'SCRIPT' && node.tagName !== 'STYLE' && (node.textContent || '').trim() !== '';
			});
		}

		function revealNativeWhenReady() {
			if (!nativeHasCreative()) return;
			if (slot.getAttribute('data-emo-adsterra-creative-inserted') !== '1') {
				slot.setAttribute('data-emo-adsterra-creative-inserted', '1');
				debug.creativeInserted += 1;
			}
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
		script.addEventListener('load', function () {
			if (slot.getAttribute('data-emo-adsterra-script-loaded') !== '1') {
				slot.setAttribute('data-emo-adsterra-script-loaded', '1');
				debug.invokeLoaded += 1;
				renderDebug();
			}
		}, { once: true });
		script.addEventListener('error', function () {
			markSlotResolved(slot, 'failed');
			collapseSlot(slot);
		}, { once: true });
		mount.insertBefore(script, container);
	}

	function slotIsSupported(type) {
		if (type === 'skyscraper' && window.matchMedia('(max-width: 1160px)').matches) return false;
		if (type === 'footer-banner' && window.matchMedia('(max-width: 519px)').matches) return false;
		return true;
	}

	function hydrateSlotNow(slot) {
		if (!slot || adBlockDetected || slot.getAttribute('data-emo-adsterra-hydrated') === '1') return;
		var type = slot.getAttribute('data-emo-adsterra-slot') || '';
		if (!slotIsSupported(type)) return;

		slot.removeAttribute('data-emo-adsterra-scheduled');

		if (type === 'native') {
			hydrateNative(slot);
		} else if (type === 'responsive-top') {
			var topName = window.matchMedia('(max-width: 767px)').matches
				? 'responsive-mobile'
				: 'responsive-desktop';
			hydrateBanner(slot, units[topName], topName);
		} else if (units[type]) {
			hydrateBanner(slot, units[type], type);
		}

		if (attemptedSlots > 0) setPhase('eligible_adsterra_loaded');
	}

	function lazyObserveSlot(slot) {
		if (!slot || slot.getAttribute('data-emo-adsterra-scheduled') === '1') return;
		slot.setAttribute('data-emo-adsterra-scheduled', '1');

		if (typeof window.IntersectionObserver !== 'function') {
			window.setTimeout(function () { hydrateSlotNow(slot); }, 500);
			return;
		}

		if (!lazySlotObserver) {
			lazySlotObserver = new IntersectionObserver(function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) return;
					var sentinel = entry.target;
					var target = sentinel.__emoAdsterraSlot;
					lazySlotObserver.unobserve(sentinel);
					sentinel.remove();
					if (target) hydrateSlotNow(target);
				});
			}, {
				rootMargin: '1000px 0px 1000px 0px',
				threshold: 0
			});
		}

		var anchor = slot.closest('.emo-adsterra-native-shell') || slot;
		if (!anchor.parentNode) {
			window.setTimeout(function () { hydrateSlotNow(slot); }, 500);
			return;
		}

		var sentinel = document.createElement('span');
		sentinel.setAttribute('aria-hidden', 'true');
		sentinel.style.cssText = 'display:block;width:1px;height:1px;margin:0;padding:0;visibility:hidden;pointer-events:none;';
		sentinel.__emoAdsterraSlot = slot;
		anchor.parentNode.insertBefore(sentinel, anchor);
		lazySlotObserver.observe(sentinel);
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

		var priority = {
			'responsive-top': 0,
			'skyscraper': 1,
			'rectangle': 2,
			'tall-rectangle': 3,
			'native': 4,
			'footer-banner': 5
		};

		var slots = Array.prototype.slice.call(document.querySelectorAll('[data-emo-adsterra-slot]'))
			.filter(function (slot) {
				return slot.getAttribute('data-emo-adsterra-hydrated') !== '1'
					&& slot.getAttribute('data-emo-adsterra-scheduled') !== '1';
			})
			.sort(function (a, b) {
				var aType = a.getAttribute('data-emo-adsterra-slot') || '';
				var bType = b.getAttribute('data-emo-adsterra-slot') || '';
				return (typeof priority[aType] === 'number' ? priority[aType] : 99)
					- (typeof priority[bType] === 'number' ? priority[bType] : 99);
			});

		slots.forEach(function (slot) {
			var type = slot.getAttribute('data-emo-adsterra-slot') || '';
			if (!slotIsSupported(type)) return;

			if (type === 'responsive-top') {
				slot.setAttribute('data-emo-adsterra-scheduled', '1');
				hydrateSlotNow(slot);
				return;
			}

			if (type === 'skyscraper') {
				slot.setAttribute('data-emo-adsterra-scheduled', '1');
				window.setTimeout(function () { hydrateSlotNow(slot); }, 180);
				return;
			}

			if (type === 'native') {
				slot.setAttribute('data-emo-adsterra-scheduled', '1');
				window.setTimeout(function () { hydrateSlotNow(slot); }, 900);
				return;
			}

			lazyObserveSlot(slot);
		});
	}

	function normalizeCountry(value) {
		var country = String(value || '').trim().toUpperCase();
		return /^[A-Z]{2}$/.test(country) && country !== 'XX' && country !== 'T1' ? country : '';
	}

	function countryIsShippable(country) {
		var countries = Array.isArray(config.shippableCountries) ? config.shippableCountries : [];
		country = normalizeCountry(country);
		if (!country) return true;
		return countries.indexOf('*') !== -1 || countries.indexOf(country) !== -1;
	}

	function readEligibilityCache() {
		try {
			var raw = window.sessionStorage.getItem(geoCacheKey);
			if (!raw) return null;
			var cached = JSON.parse(raw);
			if (!cached || !cached.ts || Date.now() - cached.ts > geoCacheMaxAge) {
				window.sessionStorage.removeItem(geoCacheKey);
				return null;
			}
			var country = normalizeCountry(cached.country);
			if (!country) return null;
			var canBuy = countryIsShippable(country);
			return {
				country: country,
				canBuy: canBuy,
				showAds: !canBuy
			};
		} catch (error) {
			return null;
		}
	}

	function writeEligibilityCache(data) {
		try {
			var country = normalizeCountry(data && data.country);
			if (!country) return;
			window.sessionStorage.setItem(geoCacheKey, JSON.stringify({
				country: country,
				ts: Date.now()
			}));
		} catch (error) {
			// sessionStorage puede estar bloqueado; la publicidad sigue funcionando.
		}
	}

	function applyEligibility(data, source, startedAt) {
		debug.country = normalizeCountry(data.country) || null;
		debug.canBuy = typeof data.canBuy === 'boolean' ? data.canBuy : null;
		debug.showAds = data.showAds === true;
		debug.geoSource = source || 'unknown';
		debug.eligibilityMs = Math.max(0, Math.round(performance.now() - startedAt));
		debug.error = null;

		if (debug.showAds) {
			preconnectAdsterra();
			preconnectGoogle();
			setPhase('eligible');
			loadGoogleAnchorScript().catch(function (error) {
				debug.error = error && error.message ? error.message : String(error || 'google_anchor_load_error');
				renderDebug();
			});
			watchForAdSlots();
			scheduleFinalHydration();
			hydrateEligibleSlots();
		} else {
			setPhase('not_eligible_no_ads');
		}
	}

	function requestRestEligibility(attempt, startedAt) {
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
				var country = normalizeCountry(data && data.country);
				var result = {
					country: country,
					canBuy: data && typeof data.can_buy === 'boolean' ? data.can_buy : null,
					showAds: data && data.show_ads === true
				};
				if (country) writeEligibilityCache(result);
				applyEligibility(result, 'wordpress_rest', startedAt);
			})
			.catch(function (error) {
				debug.error = error && error.message ? error.message : String(error || 'unknown_error');
				if (attempt < 2) {
					window.setTimeout(function () { requestRestEligibility(attempt + 1, startedAt); }, 150);
					return;
				}
				debug.eligibilityMs = Math.max(0, Math.round(performance.now() - startedAt));
				setPhase('eligibility_error_no_ads');
			});
	}

	function requestFastEligibility(startedAt) {
		if (!config.fastGeoEndpoint || !Array.isArray(config.shippableCountries)) {
			requestRestEligibility(1, startedAt);
			return;
		}

		var controller = typeof window.AbortController === 'function' ? new AbortController() : null;
		var timer = window.setTimeout(function () {
			if (controller) controller.abort();
		}, 900);

		fetch(config.fastGeoEndpoint + (config.fastGeoEndpoint.indexOf('?') === -1 ? '?' : '&') + '_=' + Date.now(), {
			method: 'GET',
			credentials: 'same-origin',
			cache: 'no-store',
			headers: { 'Accept': 'application/json' },
			signal: controller ? controller.signal : undefined
		})
			.then(function (response) {
				if (!response.ok) throw new Error('Fast geo failed with HTTP ' + response.status);
				return response.json();
			})
			.then(function (data) {
				window.clearTimeout(timer);
				var country = normalizeCountry(data && data.country);
				if (!country) throw new Error('Fast geo returned no country');
				var canBuy = countryIsShippable(country);
				var result = { country: country, canBuy: canBuy, showAds: !canBuy };
				writeEligibilityCache(result);
				applyEligibility(result, 'fast_' + String((data && data.source) || 'header'), startedAt);
			})
			.catch(function () {
				window.clearTimeout(timer);
				requestRestEligibility(1, startedAt);
			});
	}

	function requestEligibility() {
		var startedAt = performance.now();
		setPhase('checking_eligibility');

		var cached = readEligibilityCache();
		if (cached) {
			applyEligibility(cached, 'session_cache', startedAt);
			return;
		}

		if (window.ElMercadoAdGeoBootstrap && typeof window.ElMercadoAdGeoBootstrap.then === 'function') {
			var bootstrapTimeout = new Promise(function (resolve, reject) {
				window.setTimeout(function () {
					reject(new Error('early_geo_timeout'));
				}, 650);
			});

			Promise.race([window.ElMercadoAdGeoBootstrap, bootstrapTimeout])
				.then(function (data) {
					var country = normalizeCountry(data && data.country);
					if (!country) {
						requestFastEligibility(startedAt);
						return;
					}
					var canBuy = countryIsShippable(country);
					var result = { country: country, canBuy: canBuy, showAds: !canBuy };
					writeEligibilityCache(result);
					applyEligibility(result, 'early_' + String((data && data.source) || 'header'), startedAt);
				})
				.catch(function () {
					requestFastEligibility(startedAt);
				});
			return;
		}

		requestFastEligibility(startedAt);
	}

	if (debugMode) {
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', renderDebug, { once: true });
		} else {
			renderDebug();
		}
	}

	scheduleCosmeticAdBlockCheck();

	if (!config.endpoint || !config.frameEndpoint || typeof window.fetch !== 'function') {
		debug.error = 'Missing endpoint, frame endpoint or Fetch API';
		setPhase('configuration_error');
		return;
	}

	requestEligibility();
}());
