<?php
/**
 * Plugin Name: EMDO Cookie Consent Bridge
 * Description: Bridges CookieYes consent to Google Consent Mode and Meta signals.
 * Version: 1.0.1
 */

defined( 'ABSPATH' ) || exit;

/**
 * Return true only after CookieYes has recorded an explicit acceptance of
 * non-necessary cookies. The category cookie alone is not sufficient because
 * legacy CookieYes previously preselected it before the visitor made a choice.
 */
function emdo_nonnecessary_consent_granted() {
	$viewed = isset( $_COOKIE['viewed_cookie_policy'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['viewed_cookie_policy'] ) ) : '';
	$choice = isset( $_COOKIE['cookielawinfo-checkbox-non-necessary'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['cookielawinfo-checkbox-non-necessary'] ) ) : '';

	return 'yes' === $viewed && 'yes' === $choice;
}

/**
 * Synchronize the first-party state consumed by Meta for WooCommerce before
 * ordinary plugins initialise their trackers.
 */
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

/** Keep Meta Pixel/CAPI held until CookieYes has explicit consent. */
add_filter(
	'facebook_signals_held',
	function () {
		return ! emdo_nonnecessary_consent_granted();
	},
	-9999
);

/** Do not render/initialise the Meta integration pixel before consent. */
add_filter(
	'facebook_for_woocommerce_integration_pixel_enabled',
	function ( $enabled ) {
		return $enabled && emdo_nonnecessary_consent_granted();
	},
	-9999
);

/**
 * Google Consent Mode must run before MonsterInsights outputs its GA4 config.
 * The browser-side cookie check also makes the behaviour safe on cached HTML.
 */
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
		function readCookie(name) {
			var prefix = name + '=';
			var parts = document.cookie ? document.cookie.split(';') : [];
			for (var i = 0; i < parts.length; i++) {
				var item = parts[i].trim();
				if (item.indexOf(prefix) === 0) return decodeURIComponent(item.substring(prefix.length));
			}
			return '';
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
		var secure = location.protocol === 'https:' ? '; Secure' : '';
		document.cookie = 'wc_facebook_signals_state=' + (granted ? 'active' : 'held') + '; path=/; max-age=31536000; SameSite=Lax' + secure;
		if (!granted && document.cookie) {
			document.cookie.split(';').forEach(function (part) {
				var name = part.split('=')[0].trim();
				if (name === '_ga' || name.indexOf('_ga_') === 0 || name === '_fbp') expireCookie(name);
			});
		}

		/*
		 * CookieYes legacy treats the banner's Accept action as "save current
		 * category selection". Since non-necessary cookies now start unchecked,
		 * make the explicit Accept button mean "accept non-necessary cookies".
		 * The capture listener runs before CookieYes handles the same click.
		 */
		document.addEventListener('click', function (event) {
			var node = event.target && event.target.closest ? event.target.closest('[data-cli_action="accept"],#cookie_action_close_header,.wt-cli-accept-btn') : null;
			if (!node) return;
			var checkbox = document.getElementById('wt-cli-checkbox-non-necessary');
			if (!checkbox || checkbox.checked) return;
			checkbox.checked = true;
			checkbox.dispatchEvent(new Event('change', {bubbles: true}));
		}, true);
	})();
	</script>
	<?php
}
add_action( 'wp_head', 'emdo_output_consent_bootstrap', -9999 );
