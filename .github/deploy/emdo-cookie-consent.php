<?php
/**
 * Plugin Name: EMDO Cookie Consent Bridge
 * Description: Bridges CookieYes consent to Google Consent Mode and Meta signals.
 * Version: 1.0.5
 */

defined( 'ABSPATH' ) || exit;

function emdo_nonnecessary_consent_granted() {
	$viewed = isset( $_COOKIE['viewed_cookie_policy'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['viewed_cookie_policy'] ) ) : '';
	$choice = isset( $_COOKIE['cookielawinfo-checkbox-non-necessary'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['cookielawinfo-checkbox-non-necessary'] ) ) : '';

	return 'yes' === $viewed && 'yes' === $choice;
}

function emdo_sync_meta_signal_cookie() {
	$state = emdo_nonnecessary_consent_granted() ? 'active' : 'held';
	$_COOKIE['wc_facebook_signals_state'] = $state;

	if ( headers_sent() ) {
		return;
	}

	setcookie(
		'wc_facebook_signals_state',
		$state,
		time() + YEAR_IN_SECONDS,
		defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
		defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
		is_ssl(),
		false
	);
}
emdo_sync_meta_signal_cookie();

add_filter(
	'facebook_signals_held',
	function () {
		return ! emdo_nonnecessary_consent_granted();
	},
	-9999
);

add_filter(
	'facebook_for_woocommerce_integration_pixel_enabled',
	function ( $enabled ) {
		return $enabled && emdo_nonnecessary_consent_granted();
	},
	-9999
);

function emdo_output_consent_styles() {
	?>
	<style id="emdo-cookie-consent-responsive">
	#cookie-law-info-bar,
	#cookie-law-info-bar * {
		box-sizing: border-box !important;
	}
	#cookie-law-info-bar {
		width: min(980px, calc(100% - 24px)) !important;
		max-width: 980px !important;
		left: 50% !important;
		right: auto !important;
		transform: translateX(-50%) !important;
		padding: 14px 16px !important;
		margin: 0 !important;
	}
	#cookie-law-info-bar > .emdo-cookie-layout,
	#cookie-law-info-bar > span.emdo-cookie-layout {
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		gap: 18px !important;
		width: 100% !important;
		margin: 0 !important;
		padding: 0 !important;
		text-align: left !important;
	}
	#cookie-law-info-bar .emdo-cookie-message {
		flex: 1 1 auto !important;
		min-width: 0 !important;
		margin: 0 !important;
		padding: 0 !important;
		line-height: 1.45 !important;
	}
	#cookie-law-info-bar .emdo-cookie-message .cli-plugin-main-link {
		display: inline !important;
		margin: 0 0 0 4px !important;
		padding: 0 !important;
		white-space: nowrap !important;
	}
	#cookie-law-info-bar .emdo-cookie-actions {
		flex: 0 0 auto !important;
		display: flex !important;
		align-items: stretch !important;
		justify-content: flex-end !important;
		flex-wrap: nowrap !important;
		gap: 8px !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	#cookie-law-info-bar .emdo-cookie-actions .cli_action_button,
	#cookie-law-info-bar .emdo-cookie-actions .cli_settings_button {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		float: none !important;
		position: static !important;
		transform: none !important;
		width: auto !important;
		min-width: 78px !important;
		max-width: none !important;
		min-height: 40px !important;
		height: auto !important;
		margin: 0 !important;
		padding: 9px 12px !important;
		line-height: 1.2 !important;
		white-space: nowrap !important;
		text-align: center !important;
		vertical-align: middle !important;
	}

	@media (max-width: 760px) {
		#cookie-law-info-bar {
			width: calc(100% - 20px) !important;
			padding: 13px 14px !important;
		}
		#cookie-law-info-bar > .emdo-cookie-layout,
		#cookie-law-info-bar > span.emdo-cookie-layout {
			flex-direction: column !important;
			align-items: stretch !important;
			gap: 12px !important;
		}
		#cookie-law-info-bar .emdo-cookie-message {
			width: 100% !important;
		}
		#cookie-law-info-bar .emdo-cookie-actions {
			display: grid !important;
			grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
			align-items: stretch !important;
			justify-content: stretch !important;
			gap: 8px !important;
			width: 100% !important;
		}
		#cookie-law-info-bar .emdo-cookie-actions .cli_action_button,
		#cookie-law-info-bar .emdo-cookie-actions .cli_settings_button {
			width: 100% !important;
			min-width: 0 !important;
			min-height: 42px !important;
			padding: 10px 5px !important;
			font-size: 13px !important;
		}
	}

	@media (max-width: 359px) {
		#cookie-law-info-bar {
			width: calc(100% - 16px) !important;
			padding: 12px !important;
		}
		#cookie-law-info-bar .emdo-cookie-actions {
			gap: 6px !important;
		}
		#cookie-law-info-bar .emdo-cookie-actions .cli_action_button,
		#cookie-law-info-bar .emdo-cookie-actions .cli_settings_button {
			padding-left: 3px !important;
			padding-right: 3px !important;
			font-size: 12px !important;
		}
	}
	</style>
	<?php
}
add_action( 'wp_head', 'emdo_output_consent_styles', 99999 );

function emdo_output_consent_bootstrap() {
	?>
	<script id="emdo-consent-mode-bootstrap">
	window.dataLayer = window.dataLayer || [];
	window.gtag = window.gtag || function(){window.dataLayer.push(arguments);};
	gtag('consent', 'default', {
		'analytics_storage': 'denied',
		'ad_storage': 'denied',
		'ad_user_data': 'denied',
		'ad_personalization': 'denied',
		'wait_for_update': 500
	});
	(function () {
		'use strict';
		var secure = location.protocol === 'https:' ? '; Secure' : '';

		function readCookie(name) {
			var prefix = name + '=';
			var parts = document.cookie ? document.cookie.split(';') : [];
			for (var i = 0; i < parts.length; i++) {
				var item = parts[i].trim();
				if (item.indexOf(prefix) === 0) return decodeURIComponent(item.substring(prefix.length));
			}
			return '';
		}

		function writeCookie(name, value) {
			document.cookie = name + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; SameSite=Lax' + secure;
		}

		function expireCookie(name) {
			var expires = 'Thu, 01 Jan 1970 00:00:00 GMT';
			document.cookie = name + '=; expires=' + expires + '; path=/; SameSite=Lax';
			document.cookie = name + '=; expires=' + expires + '; path=/; domain=.elmercadodeorigen.com; SameSite=Lax';
			document.cookie = name + '=; expires=' + expires + '; path=/; domain=www.elmercadodeorigen.com; SameSite=Lax';
		}

		function normalizeCookieBanner() {
			var banner = document.getElementById('cookie-law-info-bar');
			if (!banner) return;
			var root = banner.querySelector(':scope > span');
			if (!root || root.classList.contains('emdo-cookie-layout')) return;

			var settings = root.querySelector('.cli_settings_button');
			var reject = root.querySelector('[data-cli_action="reject"],#cookie_action_close_header_reject,.wt-cli-reject-btn');
			var accept = root.querySelector('[data-cli_action="accept"],#cookie_action_close_header,.wt-cli-accept-btn');
			if (!settings || !reject || !accept) return;

			var actionNodes = [settings, reject, accept];
			var message = document.createElement('div');
			message.className = 'emdo-cookie-message';
			Array.prototype.slice.call(root.childNodes).forEach(function (node) {
				if (node.nodeType === 1 && actionNodes.indexOf(node) !== -1) return;
				message.appendChild(node);
			});

			var actions = document.createElement('div');
			actions.className = 'emdo-cookie-actions';
			actions.appendChild(settings);
			actions.appendChild(reject);
			actions.appendChild(accept);

			root.classList.add('emdo-cookie-layout');
			root.appendChild(message);
			root.appendChild(actions);
		}

		var granted = readCookie('viewed_cookie_policy') === 'yes' && readCookie('cookielawinfo-checkbox-non-necessary') === 'yes';
		var state = granted ? 'granted' : 'denied';
		gtag('consent', 'update', {
			'analytics_storage': state,
			'ad_storage': state,
			'ad_user_data': state,
			'ad_personalization': state
		});
		writeCookie('wc_facebook_signals_state', granted ? 'active' : 'held');
		if (!granted && document.cookie) {
			document.cookie.split(';').forEach(function (part) {
				var name = part.split('=')[0].trim();
				if (name === '_ga' || name.indexOf('_ga_') === 0 || name === '_fbp') expireCookie(name);
			});
		}

		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', normalizeCookieBanner);
		} else {
			normalizeCookieBanner();
		}
		new MutationObserver(normalizeCookieBanner).observe(document.documentElement, { childList: true, subtree: true });

		document.addEventListener('click', function (event) {
			var node = event.target && event.target.closest ? event.target.closest('[data-cli_action="accept"],#cookie_action_close_header,.wt-cli-accept-btn') : null;
			if (!node || !node.closest('#cookie-law-info-bar')) return;

			event.preventDefault();
			event.stopImmediatePropagation();

			writeCookie('cookielawinfo-checkbox-necessary', 'yes');
			writeCookie('cookielawinfo-checkbox-non-necessary', 'yes');
			writeCookie('viewed_cookie_policy', 'yes');
			writeCookie('CookieLawInfoConsent', 'eyJuZWNlc3NhcnkiOnRydWUsIm5vbi1uZWNlc3NhcnkiOnRydWV9');
			writeCookie('wc_facebook_signals_state', 'active');
			gtag('consent', 'update', {
				'analytics_storage': 'granted',
				'ad_storage': 'granted',
				'ad_user_data': 'granted',
				'ad_personalization': 'granted'
			});

			window.setTimeout(function () { window.location.reload(); }, 50);
		}, true);
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'emdo_output_consent_bootstrap', -9999 );
