<?php
/**
 * Plugin Name: MDO - Footer layout and social contacts 0.10.278
 * Description: Reorganiza el pie en dos columnas funcionales, sustituye los logos de pago por accesos sociales y mantiene un bloque final de copyright limpio.
 * Version: 0.10.278
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

		$footer_markup = sprintf(
			'<div class="mdo-footer-shell-010278"><div class="mdo-footer-main-010278"><section class="mdo-footer-column-010278"><h2>%1$s</h2><ul>%2$s</ul></section><section class="mdo-footer-column-010278 mdo-footer-info-010278"><h2>%3$s</h2><ul>%4$s</ul></section><section class="mdo-footer-column-010278 mdo-footer-contact-010278"><h2>%5$s</h2><div class="mdo-footer-socials-010278">%6$s</div></section></div><div class="mdo-footer-bottom-010278">&copy; %7$s - %8$s</div></div>',
			esc_html( $copy['market_title'] ),
			$market_html,
			esc_html( $copy['info_title'] ),
			$info_html,
			esc_html( $copy['social_title'] ),
			$social_html,
			esc_html( gmdate( 'Y' ) ),
			esc_html( $copy['copyright'] )
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

			html body .site-footer .mdo-footer-bottom-010278 {
				margin-top: 38px;
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

				html body .site-footer .mdo-footer-bottom-010278 {
					margin-top: 28px;
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
				if (!map.telegram && /(t\.me|telegram\.me)/i.test(href)) map.telegram = href;
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
