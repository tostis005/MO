<?php
/**
 * Plugin Name: EMDO Cookie Consent Bridge
 * Description: Bridges CookieYes consent to Google Consent Mode and Meta signals.
 * Version: 1.0.4
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

/**
 * Keep the legacy CookieYes banner tidy at every viewport. CookieYes can move
 * its inner container when switching to the mobile presentation, so these
 * selectors deliberately target the legacy banner classes themselves instead
 * of relying on them remaining descendants of #cookie-law-info-bar.
 */
function emdo_output_consent_styles() {
	?>
	<style id="emdo-cookie-consent-responsive">
	#cookie-law-info-bar,
	#cookie-law-info-bar *,
	.cli-bar-container.cli-style-v2,
	.cli-bar-container.cli-style-v2 * {
		box-sizing: border-box;
	}
	.cli-bar-container.cli-style-v2 {
		display: flex !important;
		align-items: center !important;
		justify-content: space-between !important;
		gap: 18px !important;
		width: 100% !important;
	}
	.cli-bar-container.cli-style-v2 .cli-bar-message {
		flex: 1 1 auto !important;
		width: auto !important;
		margin: 0 !important;
		padding: 0 !important;
		text-align: left !important;
	}
	.cli-bar-container.cli-style-v2 .cli-bar-btn_container {
		flex: 0 0 auto !important;
		display: flex !important;
		align-items: center !important;
		justify-content: flex-end !important;
		flex-wrap: nowrap !important;
		gap: 8px !important;
		width: auto !important;
		margin: 0 !important;
		padding: 0 !important;
	}
	.cli-bar-container.cli-style-v2 .cli-bar-btn_container .cli_action_button,
	.cli-bar-container.cli-style-v2 .cli-bar-btn_container .cli_settings_button {
		display: inline-flex !important;
		align-items: center !important;
		justify-content: center !important;
		float: none !important;
		transform: none !important;
		width: auto !important;
		min-width: 0 !important;
		max-width: none !important;
		min-height: 40px !important;
		margin: 0 !important;
		line-height: 1.2 !important;
		white-space: nowrap !important;
		text-align: center !important;
	}

	@media (max-width: 900px) {
		.cli-bar-container.cli-style-v2 {
			flex-direction: column !important;
			align-items: stretch !important;
			gap: 12px !important;
		}
		.cli-bar-container.cli-style-v2 .cli-bar-message {
			width: 100% !important;
		}
		.cli-bar-container.cli-style-v2 .cli-bar-btn_container {
			display: grid !important;
			grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
			align-items: stretch !important;
			justify-content: stretch !important;
			gap: 8px !important;
			width: 100% !important;
		}
		.cli-bar-container.cli-style-v2 .cli-bar-btn_container .cli_action_button,
		.cli-bar-container.cli-style-v2 .cli-bar-btn_container .cli_settings_button {
			width: 100% !important;
			min-height: 42px !important;
			padding: 10px 6px !important;
			font-size: 13px !important;
		}
	}

	@media (max-width: 359px) {
		.cli-bar-container.cli-style-v2 .cli-bar-btn_container {
			gap: 6px !important;
		}
		.cli-bar-container.cli-style-v2 .cli-bar-btn_container .cli_action_button,
		.cli-bar-container.cli-style-v2 .cli-bar-btn_container .cli_settings_button {
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
