<?php
/**
 * Stripe checkout visual integration for El Mercado de Origen.
 *
 * The native Stripe Payment Element lives in a cross-origin iframe: host CSS
 * can style its container, but fields must use the Stripe Appearance API.
 * Keep every override scoped to Stripe to avoid changing other gateways.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The same module can be deployed as an independent production MU plugin.
// If the child theme is later deployed as well, do not register hooks twice.
if ( defined( 'EMDO_STRIPE_CHECKOUT_VISUAL_LOADED' ) ) {
	return;
}
define( 'EMDO_STRIPE_CHECKOUT_VISUAL_LOADED', true );

/**
 * Style the official WooCommerce Stripe gateway fields, including the iframe.
 * Explicit colors and a stable 16px base avoid inheriting the dark review
 * panel's legacy input/label styles. Applies to checkout shortcode and blocks.
 */
add_filter(
	'wc_stripe_upe_params',
	static function ( $stripe_params ) {
		if ( ! is_array( $stripe_params ) ) {
			return $stripe_params;
		}

		$variables = array(
			'colorPrimary'         => '#173f32',
			'colorBackground'      => '#ffffff',
			'colorText'            => '#173f32',
			'colorTextSecondary'   => '#52665a',
			'colorTextPlaceholder' => '#69796e',
			'colorDanger'          => '#a33838',
			'fontFamily'           => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
			'fontSizeBase'         => '16px',
			'borderRadius'         => '10px',
			'spacingUnit'          => '4px',
		);
		$rules = array(
			'.Label'       => (object) array(
				'color'      => '#173f32',
				'fontSize'   => '14px',
				'fontWeight' => '600',
			),
			'.Input'       => (object) array(
				'color'           => '#173f32',
				'backgroundColor' => '#ffffff',
				'border'          => '1px solid #b9c7bf',
				'borderRadius'    => '10px',
				'boxShadow'       => 'none',
				'padding'         => '12px 13px',
			),
			'.Input:focus' => (object) array(
				'border'    => '1px solid #173f32',
				'boxShadow' => '0 0 0 2px rgba(23, 63, 50, .14)',
			),
		);

		foreach ( array( 'appearance', 'blocksAppearance' ) as $key ) {
			$existing = $stripe_params[ $key ] ?? array();
			$settings = is_object( $existing ) ? get_object_vars( $existing ) : ( is_array( $existing ) ? $existing : array() );
			$prior_variables = $settings['variables'] ?? array();
			$prior_variables = is_object( $prior_variables ) ? get_object_vars( $prior_variables ) : ( is_array( $prior_variables ) ? $prior_variables : array() );
			$prior_rules = $settings['rules'] ?? array();
			$prior_rules = is_object( $prior_rules ) ? get_object_vars( $prior_rules ) : ( is_array( $prior_rules ) ? $prior_rules : array() );

			$settings['theme']     = 'stripe';
			$settings['labels']    = 'above';
			$settings['variables'] = (object) array_merge( $prior_variables, $variables );
			$settings['rules']     = (object) array_merge( $prior_rules, $rules );
			$stripe_params[ $key ] = (object) $settings;
		}

		return $stripe_params;
	},
	40
);

/**
 * WooCommerce Stripe caches its iframe appearance. Clear the old style once
 * on deployment, without flushing payment data or removing plugin settings.
 */
add_action(
	'init',
	static function (): void {
		$version = '20261116-2';
		if ( $version === get_option( 'elmercado_stripe_checkout_appearance_version' ) ) {
			return;
		}
		delete_transient( 'wc_stripe_appearance' );
		delete_transient( 'wc_stripe_blocks_appearance' );
		update_option( 'elmercado_stripe_checkout_appearance_version', $version, false );
	},
	20
);

/** Stripe-only host styling, last in wp_head after the historical checkout CSS. */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_checkout' ) || ! is_checkout() || ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) ) {
			return;
		}
		?>
		<style id="elmercado-stripe-checkout-visual-20261116">
			/* Older checkout rules color every .payment_box child white;
			   Stripe's wrapper needs a light, independent reading surface. */
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe {
				box-sizing: border-box !important;
				min-width: 0 !important;
				width: 100% !important;
				max-width: 100% !important;
				margin: 12px 0 0 !important;
				padding: clamp(12px, 1.6vw, 18px) !important;
				border: 1px solid rgba(23, 63, 50, .15) !important;
				border-radius: 14px !important;
				background: #fffdf8 !important;
				background-color: #fffdf8 !important;
				color: #173f32 !important;
				box-shadow: 0 8px 24px rgba(13, 33, 27, .08) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe :is(p, label, legend, span, small, strong) {
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe :is(.wc-upe-form, #wc-stripe-upe-form, .wc-stripe-upe-element) {
				box-sizing: border-box !important;
				display: block;
				min-width: 0 !important;
				width: 100% !important;
				max-width: 100% !important;
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
				background: transparent !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe .wc-stripe-upe-element iframe {
				max-width: 100% !important;
				border: 0 !important;
				background: transparent !important;
			}

			/* Respect native card selection and saved-card choices; never clip Stripe's labels. */
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe :is(
				#wc-stripe-cc-form,
				.wc-stripe-save-source,
				.wc-stripe-new-payment-method,
				.wc-stripe-payment-request-wrapper,
				.wc-stripe-upe-element,
				fieldset,
				.form-row
			) {
				min-width: 0 !important;
				max-width: 100% !important;
				box-sizing: border-box !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe :is(
				.wc-stripe-save-source,
				.wc-stripe-new-payment-method,
				.wc-payment-form,
				.wc-saved-payment-methods
			) :is(label, span, small, p) {
				color: #173f32 !important;
				line-height: 1.5 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe .form-row :is(input:not([type="radio"]):not([type="checkbox"]), select) {
				max-width: 100% !important;
				min-height: 44px !important;
				font-size: 16px !important;
				line-height: 1.3 !important;
				border-color: #b9c7bf !important;
				background-color: #fff !important;
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment li.payment_method_stripe > label img {
				max-width: 42px !important;
				height: auto !important;
				vertical-align: middle !important;
				margin: 0 0 0 6px !important;
			}

			/* Fallback for older Stripe card-field implementations. */
			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe .wc-stripe-elements-field {
				box-sizing: border-box !important;
				width: 100% !important;
				max-width: 100% !important;
				min-height: 46px !important;
				padding: 12px 13px !important;
				border: 1px solid #b9c7bf !important;
				border-radius: 10px !important;
				background: #fff !important;
				background-color: #fff !important;
				color: #173f32 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe .wc-stripe-elements-field:focus-within {
				border-color: #173f32 !important;
				box-shadow: 0 0 0 2px rgba(23, 63, 50, .14) !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe :is(#wc-stripe-upe-errors, .woocommerce-error, .stripe-source-errors) {
				color: #9b3030 !important;
				font-size: 13px !important;
				line-height: 1.5 !important;
			}

			html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe .form-row {
				box-sizing: border-box !important;
				min-width: 0 !important;
				max-width: 100% !important;
			}

			@media (max-width: 600px) {
				html body.elmercado-child-theme.woocommerce-checkout #payment .payment_box.payment_method_stripe {
					padding: 12px 10px !important;
					border-radius: 12px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
