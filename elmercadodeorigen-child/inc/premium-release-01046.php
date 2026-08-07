<?php
/**
 * Control canónico de filtros y cierre editorial 0.10.47.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Las fechas editoriales no forman parte de la experiencia pública del blog. */
add_filter(
	'get_the_date',
	static function ( string $date, string $format = '', $post = null ): string {
		if ( is_admin() ) {
			return $date;
		}
		$post_object = get_post( $post );
		return $post_object instanceof WP_Post && 'post' === $post_object->post_type ? '' : $date;
	},
	999,
	3
);

/* Última normalización de copy sobre contenidos construidos por capas previas. */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}
		return str_replace(
			array(
				'Del productor directo a tu casa.',
				'Del productor, directo a tu casa.',
				'Conoce a quienes están detrás de cada producto.',
				'Estamos para ayudarte a elegir y comprar con confianza.',
			),
			array(
				'Del origen a tu mesa, de forma más directa.',
				'Del origen a tu mesa, de forma más directa.',
				'Conoce el origen a través de quienes lo hacen posible.',
				'Estaremos cerca antes y después de tu compra.',
			),
			$content
		);
	},
	1200
);

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-canonical-shop-filter-01047">
			#emo-premium-filter-toggle { visibility: visible !important; opacity: 1 !important; transform: none !important; }
			#emo-premium-filter-shell[hidden] { display: none !important; }
			#emo-premium-filter-shell:not([hidden]) { display: block !important; }
			@media (max-width:1100px) {
				#emo-premium-filter-toggle {
					display:flex !important;
					position:relative !important;
					width:100% !important;
					min-height:44px !important;
					align-items:center !important;
					justify-content:space-between !important;
					gap:10px !important;
					margin:0 0 18px !important;
					padding:0 14px !important;
					border:1px solid rgba(23,63,50,.13) !important;
					border-radius:12px !important;
					background:#f7f9f6 !important;
					color:#173f32 !important;
					font-size:12px !important;
					font-weight:800 !important;
					line-height:1 !important;
					box-shadow:none !important;
					cursor:pointer !important;
				}
				#emo-premium-filter-toggle .emo-filter-label { margin-right:auto !important; }
				#emo-premium-filter-toggle .emo-filter-chevron { font-size:16px !important; line-height:1 !important; }
				#emo-premium-filter-shell .emo-mobile-filter-panel,
				#emo-premium-filter-shell .emo-mobile-filter-content,
				#emo-premium-filter-shell .widget-area {
					visibility:visible !important;
					opacity:1 !important;
					transform:none !important;
				}
			}
			@media (min-width:1101px) {
				#emo-premium-filter-toggle,
				#emo-premium-filter-shell { display:none !important; }
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) #secondary.widget-area,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .shop-widget-area {
					display:block !important;
					visibility:visible !important;
					opacity:1 !important;
					transform:none !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<script id="elmercado-canonical-shop-filter-controller-01047">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;
			const compact = () => matchMedia('(max-width:1100px)').matches;
			const primary = document.querySelector('#primary,.content-area');
			const root = document.querySelector('.site-content > .woostify-container,#content > .woostify-container') || primary?.parentElement;
			let sidebar = document.querySelector('.emo-mobile-filter-content #secondary.widget-area,.emo-mobile-filter-content .shop-widget-area,.emo-mobile-filter-content .widget-area,#secondary.widget-area,.shop-widget-area,.content-area + .widget-area');
			if (!sidebar || !root || !primary) return;
			if (sidebar.id !== 'secondary' && document.querySelector('#secondary.widget-area')) sidebar = document.querySelector('#secondary.widget-area');

			/* Conservamos un punto de retorno real junto al catálogo antes de retirar drawers heredados. */
			const marker = document.createComment('emo-premium-filter-home-01047');
			primary.parentNode?.insertBefore(marker, primary.nextSibling);
			const parking = document.createDocumentFragment();
			parking.append(sidebar);
			document.querySelectorAll('.emo-mobile-filter-toggle').forEach((node) => node.remove());
			document.querySelectorAll('.emo-mobile-filter-shell').forEach((node) => node.remove());

			const anchor = document.querySelector('.woostify-sorting') || document.querySelector('.woocommerce-ordering')?.parentElement || primary;
			const toggle = document.createElement('button');
			toggle.type = 'button';
			toggle.id = 'emo-premium-filter-toggle';
			toggle.className = 'emo-mobile-filter-toggle';
			toggle.setAttribute('aria-expanded','false');
			toggle.setAttribute('aria-controls','emo-mobile-filter-panel');
			toggle.innerHTML = '<span class="emo-filter-label">Filtros</span><span class="emo-filter-chevron" aria-hidden="true">⌄</span>';
			if (anchor && anchor !== primary) anchor.insertAdjacentElement('afterend', toggle);
			else primary.prepend(toggle);

			const shell = document.createElement('div');
			shell.id = 'emo-premium-filter-shell';
			shell.className = 'emo-mobile-filter-shell';
			shell.hidden = true;
			shell.innerHTML = '<aside class="emo-mobile-filter-panel" id="emo-mobile-filter-panel" aria-label="Filtros de productos"><div class="emo-mobile-filter-head"><h2 class="emo-mobile-filter-title">Filtros</h2><button type="button" class="emo-mobile-filter-close" aria-label="Cerrar filtros">×</button></div><div class="emo-mobile-filter-content"></div></aside>';
			body.append(shell);
			const content = shell.querySelector('.emo-mobile-filter-content');
			const close = shell.querySelector('.emo-mobile-filter-close');
			if (!content) return;

			const forceToggleVisibility = () => {
				if (compact()) {
					toggle.style.setProperty('display','flex','important');
					toggle.style.setProperty('visibility','visible','important');
					toggle.style.setProperty('opacity','1','important');
					toggle.style.setProperty('position','relative','important');
					toggle.style.setProperty('width','100%','important');
					toggle.style.setProperty('height','44px','important');
					toggle.style.setProperty('min-height','44px','important');
				} else {
					toggle.style.setProperty('display','none','important');
				}
			};
			const normalizeSidebar = () => {
				sidebar.style.setProperty('display','block','important');
				sidebar.style.setProperty('visibility','visible','important');
				sidebar.style.setProperty('opacity','1','important');
				sidebar.style.setProperty('transform','none','important');
			};
			const moveIn = () => {
				if (sidebar.parentElement !== content) content.append(sidebar);
				normalizeSidebar();
			};
			const moveOut = () => {
				if (marker.parentNode) marker.parentNode.insertBefore(sidebar, marker.nextSibling);
				else root.append(sidebar);
				normalizeSidebar();
			};
			const shut = (restoreFocus = false) => {
				shell.hidden = true;
				shell.style.setProperty('display','none','important');
				toggle.setAttribute('aria-expanded','false');
				document.documentElement.classList.remove('emo-shop-filter-open');
				body.classList.remove('emo-shop-filter-open');
				if (restoreFocus && compact()) toggle.focus();
			};
			const open = () => {
				if (!compact()) return;
				moveIn();
				shell.hidden = false;
				shell.style.setProperty('display','block','important');
				shell.style.setProperty('visibility','visible','important');
				shell.style.setProperty('opacity','1','important');
				toggle.setAttribute('aria-expanded','true');
				document.documentElement.classList.add('emo-shop-filter-open');
				body.classList.add('emo-shop-filter-open');
				requestAnimationFrame(() => close?.focus());
			};
			const sync = () => {
				forceToggleVisibility();
				if (compact()) moveIn();
				else { shut(false); moveOut(); }
			};

			toggle.addEventListener('click', (event) => {
				event.preventDefault();
				event.stopImmediatePropagation();
				toggle.getAttribute('aria-expanded') === 'true' ? shut(true) : open();
			});
			close?.addEventListener('click', (event) => { event.preventDefault(); event.stopImmediatePropagation(); shut(true); });
			shell.addEventListener('click', (event) => { if (event.target === shell) { event.stopImmediatePropagation(); shut(true); } });
			document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !shell.hidden) { event.preventDefault(); shut(true); } });
			window.addEventListener('resize', () => requestAnimationFrame(sync), {passive:true});
			sync();
			setTimeout(sync, 100);
			setTimeout(sync, 500);
		})();
		</script>
		<script id="elmercado-final-public-copy-01047">
		(() => {
			const shopLead = document.querySelector('body.woocommerce-shop .emo-shop-lead p');
			if (shopLead) shopLead.textContent = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa.';
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
