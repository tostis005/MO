<?php
/**
 * Plugin Name: MDO - Footer layout and social contacts 0.10.284
 * Description: Organiza el pie global con redes sociales, seis métodos de pago y copyright.
 * Version: 0.10.286
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Refresh the separately cached home HTML once so the new payment strip is not
 * lost behind the previous footer snapshot.
 */
add_action(
	'init',
	static function (): void {
		$version = 'payment-methods-20261110-v4';
		$key = 'mdo_footer_payment_cache_version';
		if ( get_option( $key, '' ) === $version ) {
			return;
		}
		if ( function_exists( 'elmercado_flush_home_cache' ) ) {
			elmercado_flush_home_cache();
		} else {
			$file = WP_CONTENT_DIR . '/uploads/elmercado-home-static/index.html';
			if ( is_file( $file ) ) {
				@unlink( $file );
			}
		}
		update_option( $key, $version, false );
	},
	-9999
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}

		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$is_english   = function_exists( 'elmercado_is_english_request_010245' )
			? (bool) elmercado_is_english_request_010245()
			: ( 1 === preg_match( '#^/en(?:/|$)#i', $request_path ) );

		if ( $is_english ) {
			$copy = array(
				'market_title' => 'El Mercado',
				'info_title'   => 'Information',
				'social_title' => 'Follow us & contact',
				'copyright'    => 'El Mercado de Origen',
			);
			$market_links = array(
				array( 'label' => 'About us', 'url' => home_url( '/en/about-us/' ), 'aliases' => array( 'about us' ) ),
				array( 'label' => 'Contact', 'url' => home_url( '/en/contact/' ), 'aliases' => array( 'contact' ) ),
				array( 'label' => 'For producers', 'url' => home_url( '/en/contact-producers/' ), 'aliases' => array( 'contact producers', 'for producers', 'producer contact' ) ),
			);
			$info_links = array(
				array( 'label' => 'Shipping', 'url' => home_url( '/en/shipping/' ), 'aliases' => array( 'shipping' ) ),
				array( 'label' => 'Returns & refunds', 'url' => home_url( '/en/returns-refunds/' ), 'aliases' => array( 'returns and refunds', 'returns & refunds' ) ),
				array( 'label' => 'Legal notice', 'url' => home_url( '/en/legal-notice/' ), 'aliases' => array( 'legal notice' ) ),
				array( 'label' => 'Terms & conditions', 'url' => home_url( '/en/terms-and-conditions/' ), 'aliases' => array( 'terms and conditions', 'terms & conditions' ) ),
				array( 'label' => 'Privacy policy', 'url' => home_url( '/en/privacy-policy/' ), 'aliases' => array( 'privacy policy' ) ),
				array( 'label' => 'Cookie policy', 'url' => home_url( '/en/cookie-policy/' ), 'aliases' => array( 'cookie policy', 'cookies policy' ) ),
			);
		} else {
			$copy = array(
				'market_title' => 'El Mercado',
				'info_title'   => 'Información',
				'social_title' => 'Síguenos y contacta',
				'copyright'    => 'El Mercado de Origen',
			);
			$market_links = array(
				array( 'label' => 'Quiénes somos', 'url' => home_url( '/quienes-somos/' ), 'aliases' => array( 'quiénes somos', 'quienes somos' ) ),
				array( 'label' => 'Contacto', 'url' => home_url( '/contacto/' ), 'aliases' => array( 'contacto' ) ),
				array( 'label' => 'Contacto productores', 'url' => home_url( '/contacto-productores/' ), 'aliases' => array( 'contacto productores', 'contacto de productores' ) ),
			);
			$info_links = array(
				array( 'label' => 'Envíos', 'url' => home_url( '/envios/' ), 'aliases' => array( 'envíos', 'envios' ) ),
				array( 'label' => 'Devoluciones y reembolsos', 'url' => home_url( '/devoluciones-y-reembolsos/' ), 'aliases' => array( 'devoluciones y reembolsos', 'devoluciones', 'reembolsos' ) ),
				array( 'label' => 'Aviso legal', 'url' => home_url( '/aviso-legal/' ), 'aliases' => array( 'aviso legal' ) ),
				array( 'label' => 'Términos y condiciones', 'url' => home_url( '/politica/' ), 'aliases' => array( 'términos y condiciones', 'terminos y condiciones' ) ),
				array( 'label' => 'Política de privacidad', 'url' => home_url( '/politica-de-privacidad/' ), 'aliases' => array( 'política de privacidad', 'politica de privacidad' ) ),
				array( 'label' => 'Política de cookies', 'url' => home_url( '/politica-de-cookies/' ), 'aliases' => array( 'política de cookies', 'politica de cookies', 'cookies' ) ),
			);
		}

		$svg = array(
			'instagram' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"></rect><circle cx="12" cy="12" r="4.25"></circle><circle class="mdo-social-dot" cx="17.4" cy="6.7" r="1"></circle></svg>',
			'facebook'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14.2 8.2V6.7c0-.7.5-.9 1-.9h2.4V2.2L14.4 2C11.2 2 9.8 3.9 9.8 6.4v1.8H7v4h2.8V22h4.4v-9.8h3.1l.5-4h-3.6z"></path></svg>',
			'telegram'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21.4 3.2 18.2 20c-.2 1.2-.9 1.5-1.9.9l-4.9-3.6-2.4 2.3c-.3.3-.5.5-1 .5l.4-5 9.1-8.2c.4-.4-.1-.6-.6-.2L5.7 13.8.9 12.3c-1-.3-1.1-1 .2-1.5L20 3.5c.9-.3 1.7.2 1.4-.3z"></path></svg>',
			'whatsapp'  => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.5 3.5A11.8 11.8 0 0 0 12.1 0C5.6 0 .3 5.3.3 11.8c0 2.1.5 4.1 1.6 5.9L.2 24l6.5-1.7c1.7.9 3.6 1.4 5.5 1.4 6.5 0 11.8-5.3 11.8-11.8 0-3.2-1.2-6.1-3.5-8.4zm-8.3 18.2c-1.7 0-3.4-.5-4.9-1.3l-.4-.2-3.8 1 1-3.7-.2-.4a9.8 9.8 0 1 1 8.3 4.6zm5.4-7.4c-.3-.1-1.8-.9-2-.9-.3-.1-.5-.1-.7.2-.2.3-.8.9-1 1.1-.2.2-.4.2-.7.1-1.8-.9-3-1.7-4.2-3.8-.3-.5.3-.5.9-1.7.1-.2 0-.4 0-.6-.1-.1-.7-1.7-1-2.3-.3-.6-.5-.5-.7-.5h-.6c-.2 0-.6.1-.9.4-.3.3-1.2 1.2-1.2 2.9 0 1.7 1.2 3.3 1.4 3.5.1.2 2.4 3.7 5.9 5.2 2.2 1 3.1 1.1 4.2.9.7-.1 1.8-.7 2.1-1.5.3-.7.3-1.4.2-1.5-.1-.2-.4-.3-.7-.4z"></path></svg>',
		);

		$social_links = array(
			array( 'key' => 'instagram', 'label' => 'Instagram', 'url' => 'https://www.instagram.com/elmercadodeorigen/' ),
			array( 'key' => 'facebook', 'label' => 'Facebook', 'url' => 'https://www.facebook.com/elmercadodeorigen/' ),
			array( 'key' => 'telegram', 'label' => 'Telegram', 'url' => 'https://t.me/elmercadodeorigen' ),
			array( 'key' => 'whatsapp', 'label' => 'WhatsApp', 'url' => 'https://wa.me/34603029509' ),
		);

		$render_links = static function ( array $links ): string {
			$html = '';
			foreach ( $links as $link ) {
				$html .= sprintf(
					'<li><a href="%1$s" data-mdo-aliases="%2$s">%3$s</a></li>',
					esc_url( $link['url'] ),
					esc_attr( wp_json_encode( $link['aliases'] ) ),
					esc_html( $link['label'] )
				);
			}
			return $html;
		};

		$market_html = $render_links( $market_links );
		$info_html   = $render_links( $info_links );
		$social_html = '';
		foreach ( $social_links as $social ) {
			$social_html .= sprintf(
				'<a class="mdo-footer-social-010278 mdo-footer-social--%1$s" href="%2$s" target="_blank" rel="noopener noreferrer" aria-label="%3$s" title="%3$s">%4$s<span class="screen-reader-text">%3$s</span></a>',
				esc_attr( $social['key'] ),
				esc_url( $social['url'] ),
				esc_attr( $social['label'] ),
				$svg[ $social['key'] ]
			);
		}


		$payment_labels = array(
			'edenred'    => 'Edenred',
			'visa'       => 'Visa',
			'mastercard' => 'Mastercard',
			'bizum'      => 'Bizum',
			'paypal'     => 'PayPal',
			'transfer'   => $is_english ? 'Bank transfer' : 'Transferencia bancaria',
		);
		$payment_title = $is_english ? 'Accepted payment methods' : 'Métodos de pago';
		$payment_html = '<section class="mdo-footer-payments-010278" aria-label="' . esc_attr( $payment_title ) . '"><h2 class="mdo-footer-payments-heading-010278">' . esc_html( $payment_title ) . '</h2><div class="mdo-footer-payment-cards-010278">';
		foreach ( $payment_labels as $slug => $label ) {
			if ( 'paypal' === $slug ) {
				// Official PayPal 2024 wordmark supplied in the Jetpack PayPal Payments package.
				// Embed the exact SVG paths so the badge cannot disappear when a third-party CDN fails.
				$icon_html = '<svg class="mdo-footer-payment-paypal-logo-010278" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 135 48"><g clip-path="url(#a)"><path fill="#000" d="M133 2.02v32.83h-7.47V2.02H133zM122.78 13.1v21.81h-6.64v-1.88c-.84.82-1.8 1.44-2.86 1.88-1.07.46-2.23.7-3.47.7-1.56 0-3.01-.29-4.34-.87-1.33-.61-2.49-1.44-3.47-2.48-.99-1.05-1.77-2.27-2.35-3.66-.55-1.42-.82-2.95-.82-4.58s.27-3.14.82-4.53c.58-1.42 1.36-2.66 2.35-3.71a10.45 10.45 0 0 1 3.47-2.44c1.33-.61 2.78-.92 4.34-.92 1.24 0 2.4.23 3.47.7 1.07.44 2.03 1.06 2.86 1.88v-1.88h6.64v-.02zm-11.77 15.87c1.36 0 2.47-.46 3.34-1.4.9-.93 1.35-2.12 1.35-3.57 0-1.45-.45-2.65-1.35-3.57-.87-.93-1.99-1.4-3.34-1.4-1.35 0-2.49.46-3.39 1.4-.87.93-1.31 2.12-1.31 3.57 0 1.45.44 2.65 1.31 3.57.9.93 2.03 1.4 3.39 1.4zM88.17 2.02c1.97 0 3.65.28 5.04.83 1.39.55 2.56 1.32 3.52 2.31.98 1.02 1.75 2.18 2.3 3.49.55 1.31.82 2.72.82 4.23 0 1.51-.27 2.92-.82 4.23a11.15 11.15 0 0 1-2.3 3.49c-.95.99-2.13 1.76-3.52 2.31-1.39.55-3.07.83-5.04.83h-3.6V34.9h-7.6V2.02h11.2zm-1.09 14.96c1.02 0 1.79-.1 2.35-.31.58-.23 1.06-.53 1.43-.87.78-.73 1.17-1.7 1.17-2.92s-.39-2.19-1.17-2.92c-.38-.35-.85-.63-1.43-.83-.55-.23-1.33-.35-2.35-.35h-2.52v8.2h2.52zM48.97 13.1h8.25l5.6 10.46h.09L67.9 13.1h7.64L59.17 45.98h-7.6l7.47-15.04L48.97 13.1zm-1.47 0v21.81h-6.64v-1.88c-.84.82-1.8 1.44-2.86 1.88-1.07.46-2.23.7-3.47.7-1.56 0-3.01-.29-4.34-.87-1.33-.61-2.49-1.44-3.47-2.48-.99-1.05-1.77-2.27-2.35-3.66-.55-1.42-.82-2.95-.82-4.58s.27-3.14.82-4.53c.58-1.42 1.36-2.66 2.35-3.71a10.35 10.35 0 0 1 3.47-2.44c1.33-.61 2.78-.92 4.34-.92 1.24 0 2.4.23 3.47.7 1.07.44 2.03 1.06 2.86 1.88v-1.88h6.64v-.02zM35.73 28.97c1.36 0 2.47-.46 3.35-1.4.9-.93 1.35-2.12 1.35-3.57 0-1.45-.45-2.65-1.35-3.57-.87-.93-1.99-1.4-3.35-1.4s-2.49.46-3.39 1.4c-.87.93-1.3 2.12-1.3 3.57 0 1.45.44 2.65 1.3 3.57.9.93 2.03 1.4 3.39 1.4zM13.2 2.02c1.97 0 3.65.28 5.04.83 1.39.55 2.56 1.32 3.52 2.31.98 1.02 1.75 2.18 2.3 3.49.55 1.31.82 2.72.82 4.23 0 1.51-.27 2.92-.82 4.23a11.15 11.15 0 0 1-2.3 3.49c-.95.99-2.13 1.76-3.52 2.31-1.39.55-3.07.83-5.04.83H9.6V34.9H2V2.02h11.2zm-1.08 14.96c1.02 0 1.79-.1 2.35-.31.58-.23 1.06-.53 1.43-.87.78-.73 1.17-1.7 1.17-2.92s-.39-2.19-1.17-2.92c-.38-.35-.85-.63-1.43-.83-.55-.23-1.33-.35-2.35-.35H9.6v8.2h2.52z"/></g><defs><clipPath id="a"><path fill="#fff" d="M2 2.02h131v43.96H2z"/></clipPath></defs></svg>';
			} elseif ( 'transfer' === $slug ) {
				// Lucide "landmark" (ISC licence): balanced bank icon, no improvised strokes.
				$short_label = $is_english ? 'Bank transfer' : 'Transferencia';
				$icon_html = '<span class="mdo-footer-payment-transfer-010278" aria-hidden="true">'
					. '<svg class="mdo-footer-payment-transfer-icon-010278" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" > <path d="M10 18v-7" /> <path d="M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z" /> <path d="M14 18v-7" /> <path d="M18 18v-7" /> <path d="M3 22h18" /> <path d="M6 18v-7" /> </svg>'
					. '<span class="mdo-footer-payment-transfer-label-010278">' . esc_html( $short_label ) . '</span></span>';
			} else {
				$icon_html = '<span class="mdo-footer-payment-logo-010278 mdo-footer-payment-logo--' . esc_attr( $slug ) . '" aria-hidden="true"></span>';
			}
			$payment_html .= '<span class="mdo-footer-payment-card-010278 mdo-footer-payment-card--' . esc_attr( $slug ) . '" role="img" aria-label="' . esc_attr( $label ) . '">' . $icon_html . '</span>';
		}
		$payment_html .= '</div></section>';

		$footer_markup = sprintf(
			'<div class="mdo-footer-shell-010278"><div class="mdo-footer-main-010278"><section class="mdo-footer-column-010278"><h2>%1$s</h2><ul>%2$s</ul></section><section class="mdo-footer-column-010278 mdo-footer-info-010278"><h2>%3$s</h2><ul>%4$s</ul></section><section class="mdo-footer-column-010278 mdo-footer-contact-010278"><h2>%5$s</h2><div class="mdo-footer-socials-010278">%6$s</div></section></div>%9$s<div class="mdo-footer-bottom-010278">&copy; %7$s - %8$s</div></div>',
			esc_html( $copy['market_title'] ),
			$market_html,
			esc_html( $copy['info_title'] ),
			$info_html,
			esc_html( $copy['social_title'] ),
			$social_html,
			esc_html( gmdate( 'Y' ) ),
			esc_html( $copy['copyright'] ),
			$payment_html
		);
		?>
		<style id="mdo-footer-layout-socials-010278">
			html body .site-footer.mdo-footer-enhanced-010278 {
				margin-top: 0;
				padding: 0 !important;
				background: #0d211b !important;
				color: rgba(255,255,255,.78) !important;
			}

			html body .site-footer.mdo-footer-enhanced-010278 > :not(.mdo-footer-shell-010278) {
				display: none !important;
			}

			html body .site-footer .mdo-footer-shell-010278 {
				width: min(100% - 48px, 1240px);
				margin: 0 auto;
				padding: 46px 0 22px;
			}

			html body .site-footer .mdo-footer-main-010278 {
				display: grid;
				grid-template-columns: minmax(0,.9fr) minmax(0,1.25fr) minmax(220px,.75fr);
				gap: clamp(34px,5vw,72px);
				align-items: start;
			}

			html body .site-footer .mdo-footer-column-010278 h2 {
				margin: 0 0 17px !important;
				padding: 0 !important;
				color: #fff !important;
				font-family: inherit !important;
				font-size: 15px !important;
				font-weight: 650 !important;
				line-height: 1.3 !important;
				letter-spacing: .025em !important;
				text-transform: none !important;
			}

			html body .site-footer .mdo-footer-column-010278 ul {
				display: grid;
				gap: 9px;
				margin: 0 !important;
				padding: 0 !important;
				list-style: none !important;
			}

			html body .site-footer .mdo-footer-column-010278 li {
				margin: 0 !important;
				padding: 0 !important;
				border: 0 !important;
			}

			html body .site-footer .mdo-footer-column-010278 li::before,
			html body .site-footer .mdo-footer-column-010278 li::after {
				display: none !important;
				content: none !important;
			}

			html body .site-footer .mdo-footer-column-010278 li a {
				display: inline-block;
				padding: 2px 0 !important;
				color: rgba(255,255,255,.76) !important;
				font-size: 14px !important;
				font-weight: 400 !important;
				line-height: 1.45 !important;
				text-decoration: none !important;
				transition: color .18s ease, transform .18s ease;
			}

			html body .site-footer .mdo-footer-column-010278 li a:hover,
			html body .site-footer .mdo-footer-column-010278 li a:focus-visible {
				color: #fff !important;
				transform: translateX(2px);
			}

			html body .site-footer .mdo-footer-socials-010278 {
				display: flex;
				flex-wrap: wrap;
				gap: 10px;
			}

			html body .site-footer .mdo-footer-social-010278 {
				display: inline-flex !important;
				width: 42px;
				height: 42px;
				align-items: center;
				justify-content: center;
				padding: 0 !important;
				border: 1px solid rgba(255,255,255,.24);
				border-radius: 999px;
				color: #fff !important;
				background: rgba(255,255,255,.045);
				text-decoration: none !important;
				transition: background .18s ease, border-color .18s ease, transform .18s ease;
			}

			html body .site-footer .mdo-footer-social-010278:hover,
			html body .site-footer .mdo-footer-social-010278:focus-visible {
				border-color: rgba(255,255,255,.58);
				background: rgba(255,255,255,.1);
				transform: translateY(-2px);
			}

			html body .site-footer .mdo-footer-social-010278 svg {
				display: block;
				width: 19px;
				height: 19px;
				fill: currentColor;
				stroke: none;
			}

			html body .site-footer .mdo-footer-social--instagram svg {
				fill: none;
				stroke: currentColor;
				stroke-width: 1.8;
			}

			html body .site-footer .mdo-footer-social--instagram .mdo-social-dot {
				fill: currentColor;
				stroke: none;
			}


			/* Six equal-sized cards: three columns on desktop, two on mobile. */
			html body .site-footer .mdo-footer-payments-010278 {
				margin-top: 34px;
				padding-top: 25px;
				border-top: 1px solid rgba(255,255,255,.12);
			}
			html body .site-footer .mdo-footer-payments-heading-010278 {
				margin: 0 0 14px !important;
				padding: 0 !important;
				font: inherit !important;
				font-size: 13px !important;
				line-height: 1.5 !important;
				font-weight: 550 !important;
				letter-spacing: .01em;
				text-align: center;
				color: rgba(255,255,255,.74) !important;
			}
			html body .site-footer .mdo-footer-payment-cards-010278 {
				display: grid;
				grid-template-columns: repeat(3,minmax(0,1fr));
				gap: 11px;
				width: min(100%, 615px);
				margin: 0 auto;
			}
			html body .site-footer .mdo-footer-payment-card-010278,
			html body .site-footer .mdo-footer-payment-card-010278:first-child {
				display: flex;
				align-items: center;
				justify-content: center;
				min-width: 0;
				width: 100%;
				height: 72px;
				padding: 6px 10px !important;
				border: 1px solid rgba(255,255,255,.15);
				border-radius: 9px;
				background: #fff;
				overflow: hidden;
				box-sizing: border-box;
			}
			html body .site-footer .mdo-footer-payment-paypal-logo-010278 {
				display: block;
				flex: none;
				width: min(100%, 132px);
				max-width: 100%;
				height: 45px;
				margin: 0 auto;
				overflow: visible;
			}
			html body .site-footer .mdo-footer-payment-transfer-010278 {
				display: flex;
				flex-direction: column;
				align-items: center;
				justify-content: center;
				gap: 4px;
				width: 100%;
				max-width: 100%;
				color: #24443e;
				text-align: center;
				font-family: inherit;
				font-size: 12px;
				line-height: 1.15;
				font-weight: 650;
				letter-spacing: .01em;
				text-transform: none;
			}
			html body .site-footer .mdo-footer-payment-transfer-icon-010278 {
				display: block;
				flex: none;
				width: 29px;
				height: 29px;
				margin: 0 auto;
				color: #24443e;
			}
			html body .site-footer .mdo-footer-payment-transfer-label-010278 {
				display: block;
				white-space: nowrap;
			}
			html body .site-footer .mdo-footer-payment-logo-010278 {
				display: block;
				flex: none;
				background-image: url("data:image/webp;base64,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");
				background-size: 384px 77px;
				background-repeat: no-repeat;
			}
			html body .site-footer .mdo-footer-payment-logo--edenred {
				width: 101px; height: 63px; background-position: -5.75px -6px;
			}
			html body .site-footer .mdo-footer-payment-logo--visa {
				width: 94px; height: 34px; background-position: -109.5px -23px;
			}
			html body .site-footer .mdo-footer-payment-logo--mastercard {
				width: 66px; height: 52px; background-position: -212px -13.75px;
			}
			html body .site-footer .mdo-footer-payment-logo--bizum {
				width: 92px; height: 29px; background-position: -289.25px -23.25px;
			}

			html body .site-footer .mdo-footer-bottom-010278 {
				margin-top: 22px;
				padding-top: 18px;
				border-top: 1px solid rgba(255,255,255,.12);
				color: rgba(255,255,255,.58);
				font-size: 12.5px;
				line-height: 1.45;
			}

			@media (max-width: 860px) {
				html body .site-footer .mdo-footer-main-010278 {
					grid-template-columns: repeat(2,minmax(0,1fr));
					gap: 34px 44px;
				}

				html body .site-footer .mdo-footer-contact-010278 {
					grid-column: 1 / -1;
				}
			}

			@media (max-width: 600px) {
				html body .site-footer .mdo-footer-shell-010278 {
					width: min(100% - 32px, 1240px);
					padding: 34px 0 18px;
				}

				html body .site-footer .mdo-footer-main-010278 {
					grid-template-columns: minmax(0,1fr);
					gap: 27px;
				}

				html body .site-footer .mdo-footer-contact-010278 {
					grid-column: auto;
				}

				html body .site-footer .mdo-footer-column-010278 h2 {
					margin-bottom: 12px !important;
				}

				html body .site-footer .mdo-footer-column-010278 ul {
					gap: 7px;
				}

				html body .site-footer .mdo-footer-payments-010278 {
					margin-top: 27px;
					padding-top: 20px;
				}
				html body .site-footer .mdo-footer-payments-heading-010278 {
					margin-bottom: 12px !important;
				}
				html body .site-footer .mdo-footer-payment-cards-010278 {
					grid-template-columns: repeat(2, minmax(0, 1fr));
					width: 100%;
					gap: 9px;
				}
				html body .site-footer .mdo-footer-payment-card-010278,
				html body .site-footer .mdo-footer-payment-card-010278:first-child {
					height: 68px;
					padding: 6px 8px !important;
				}
				html body .site-footer .mdo-footer-payment-paypal-logo-010278 {
					width: min(100%, 124px);
					height: 44px;
				}
				html body .site-footer .mdo-footer-payment-transfer-010278 {
					font-size: 11.5px;
					letter-spacing: 0;
				}

				html body .site-footer .mdo-footer-bottom-010278 {
					margin-top: 21px;
					padding-top: 16px;
				}
			}
		</style>
		<script id="mdo-footer-layout-socials-runtime-010278">
		(() => {
			const footer = document.querySelector('.site-footer');
			if (!footer || footer.dataset.mdoFooter010278 === '1') return;

			const normalize = (value) => (value || '')
				.toLowerCase()
				.normalize('NFD')
				.replace(/[\u0300-\u036f]/g, '')
				.replace(/\s+/g, ' ')
				.trim();

			/* Preserve translated/current destinations already configured in WordPress. */
			const existing = Array.from(footer.querySelectorAll('a[href]')).map((anchor) => ({
				label: normalize(anchor.textContent),
				href: anchor.href
			}));

			/* Reuse any social destination already present elsewhere on the live site. */
			const socialSources = Array.from(document.querySelectorAll('a[href]')).reduce((map, anchor) => {
				const href = anchor.href || '';
				if (!map.instagram && /instagram\.com/i.test(href)) map.instagram = href;
				if (!map.facebook && /facebook\.com/i.test(href)) map.facebook = href;
				if (!map.whatsapp && /(wa\.me|whatsapp\.com)/i.test(href)) map.whatsapp = href;
				return map;
			}, {});

			const shell = document.createElement('div');
			shell.innerHTML = <?php echo wp_json_encode( $footer_markup, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>;
			const replacement = shell.firstElementChild;
			if (!replacement) return;

			replacement.querySelectorAll('a[data-mdo-aliases]').forEach((anchor) => {
				let aliases = [];
				try { aliases = JSON.parse(anchor.dataset.mdoAliases || '[]'); } catch (error) { aliases = []; }
				const wanted = aliases.map(normalize);
				const match = existing.find((item) => wanted.includes(item.label));
				if (match && match.href) anchor.href = match.href;
				anchor.removeAttribute('data-mdo-aliases');
			});

			Object.entries(socialSources).forEach(([network, href]) => {
				const anchor = replacement.querySelector('.mdo-footer-social--' + network);
				if (anchor && href) anchor.href = href;
			});

			footer.prepend(replacement);
			footer.dataset.mdoFooter010278 = '1';
			footer.classList.add('mdo-footer-enhanced-010278');
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
