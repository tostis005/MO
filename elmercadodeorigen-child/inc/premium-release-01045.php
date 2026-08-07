<?php
/**
 * Cierre premium de redacción y filtros responsivos 0.10.45.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Copy final de marca. Se aplica al HTML ya compuesto para desacoplar el
 * discurso público de categorías concretas que pueden cambiar con el catálogo.
 */
add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! in_the_loop() || ! is_main_query() ) {
			return $content;
		}

		$replacements = array(
			'Productos con origen, directos de sus productores' => 'Productos directamente desde su origen',
			'Del productor, directo a tu casa.' => 'Del origen a tu mesa, de forma más directa.',
			'Descubre productos directamente desde su origen, para acercar lo que se produce a quienes quieren disfrutarlo en casa.' => 'Una selección de productos con procedencia clara, pensada para acercar a quienes producen y a quienes eligen qué llega a su mesa.',
			'Comprar productos' => 'Explorar productos',
			'Ver productores' => 'Conocer productores',
			'Una compra más directa' => 'Más cerca del origen',
			'Productos que hablan por sí solos' => 'Una selección con criterio',
			'Atención cercana en cada paso' => 'Acompañamiento cuando lo necesitas',
			'Origen que puedes conocer' => 'Procedencia clara',
			'Productos seleccionados y enviados con la cercanía de quienes conocen su origen.' => 'Productos elegidos por su procedencia y por el valor de quienes los hacen posibles.',
			'Conoces quién está detrás, cómo trabaja y qué hace diferente cada producto.' => 'Puedes conocer quién está detrás, cómo trabaja y qué aporta al producto que eliges.',
			'Explora la selección' => 'Elige desde el origen',
			'Productos con origen para elegir mejor' => 'Productos con procedencia para elegir con criterio',
			'Descubre propuestas para disfrutar, compartir o regalar, siempre con información clara sobre su procedencia y quién las hace posibles.' => 'Encuentra productos para disfrutar, compartir o regalar con información clara sobre su procedencia y sobre quién los hace posibles.',
			'Los productos que más eligen nuestros clientes' => 'Los productos que más se eligen',
			'Una selección ordenada por ventas reales para empezar por los productos que más confianza generan entre nuestros clientes.' => 'Una selección ordenada por ventas reales para descubrir qué productos vuelven una y otra vez a la cesta.',
			'Acortamos la distancia entre quienes producen y quienes quieren elegir mejor.' => 'Acortamos la distancia entre el origen y tu mesa.',
			'El Mercado de Origen conecta a productores que cuidan lo que hacen con personas que valoran el origen, la calidad y una forma de comprar más transparente.' => 'El Mercado de Origen acerca productos con procedencia a personas que quieren comprar con más información, más criterio y menos distancia.',
			'Detrás de cada producto hay una forma de hacer las cosas.' => 'Cada producto empieza mucho antes de llegar a tu mesa.',
			'Descubre sus proyectos, cómo trabajan y qué aporta cada uno a la selección del mercado.' => 'Conoce los proyectos, la forma de trabajar y las decisiones que dan identidad a cada producto.',
			'Con origen propio' => 'Con procedencia visible',
			'Conoce a quienes están detrás de cada producto.' => 'Conoce el origen a través de quienes lo hacen posible.',
			'Proyectos con identidad propia, formas de trabajar distintas y una misma voluntad: que el producto llegue con todo su valor hasta quien lo elige.' => 'Proyectos con identidad propia y formas distintas de trabajar, unidos por una idea sencilla: que el valor del origen también llegue a quien compra.',
			'Estamos para ayudarte a elegir y comprar con confianza.' => 'Estamos para ayudarte antes y después de tu compra.',
			'Cuéntanos qué necesitas. Te responderemos con información clara y una atención cercana, antes o después de tu pedido.' => 'Cuéntanos qué necesitas. Te responderemos de forma clara y cercana, tanto si estás eligiendo como si ya has hecho tu pedido.',
		);

		return str_replace( array_keys( $replacements ), array_values( $replacements ), $content );
	},
	999
);

/**
 * El modo compacto de tienda empieza a 1100px. Esta capa crea el control si la
 * versión móvil antigua no lo hizo y conserva un único sidebar real, que vuelve
 * a su lugar al regresar a escritorio.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-premium-shop-filter-01045">
			@media (max-width:1100px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag) .emo-mobile-filter-toggle {
					display:flex !important;
					width:100% !important;
					min-height:44px !important;
					align-items:center !important;
					justify-content:space-between !important;
					gap:10px !important;
					margin:0 0 18px !important;
					padding:0 14px !important;
					border:1px solid rgba(23,63,50,.12) !important;
					border-radius:12px !important;
					background:#f7f9f6 !important;
					color:#173f32 !important;
					font-size:12px !important;
					font-weight:800 !important;
					box-shadow:none !important;
				}
				body.elmercado-child-theme .emo-mobile-filter-shell:not([hidden]) { display:block !important; }
				body.elmercado-child-theme .emo-mobile-filter-shell[hidden] { display:none !important; }
			}
			@media (min-width:1101px) {
				body.elmercado-child-theme .emo-mobile-filter-toggle,
				body.elmercado-child-theme .emo-mobile-filter-shell { display:none !important; }
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
		<script id="elmercado-premium-shop-filter-controller-01045">
		(() => {
			'use strict';
			const body = document.body;
			if (!body.matches('.woocommerce-shop,.tax-product_cat,.tax-product_tag') || body.classList.contains('wcfmmp-store-page')) return;

			const compact = () => matchMedia('(max-width:1100px)').matches;
			const sorting = document.querySelector('.woostify-sorting,.woocommerce-ordering')?.closest('.woostify-sorting') || document.querySelector('.woostify-sorting');
			let sidebar = document.querySelector('.emo-mobile-filter-content #secondary.widget-area,.emo-mobile-filter-content .widget-area,#secondary.widget-area,.shop-widget-area,.content-area + .widget-area');
			if (!sidebar) return;

			let homeMarker = document.querySelector('#emo-filter-home-01045');
			if (!homeMarker) {
				homeMarker = document.createElement('span');
				homeMarker.id = 'emo-filter-home-01045';
				homeMarker.hidden = true;
				const oldContent = sidebar.closest('.emo-mobile-filter-content');
				const fallbackParent = document.querySelector('.site-content > .woostify-container,#content > .woostify-container');
				if (!oldContent && sidebar.parentNode) sidebar.parentNode.insertBefore(homeMarker, sidebar);
				else if (fallbackParent) fallbackParent.append(homeMarker);
			}

			let toggle = document.querySelector('.emo-mobile-filter-toggle');
			if (!toggle) {
				toggle = document.createElement('button');
				toggle.type = 'button';
				toggle.className = 'emo-mobile-filter-toggle';
				toggle.setAttribute('aria-expanded','false');
				toggle.setAttribute('aria-controls','emo-mobile-filter-panel');
				toggle.innerHTML = '<span class="emo-filter-label">Filtros</span><span class="emo-filter-chevron" aria-hidden="true">⌄</span>';
				if (sorting) sorting.insertAdjacentElement('afterend', toggle);
				else document.querySelector('#primary,.content-area')?.prepend(toggle);
			} else {
				toggle.querySelector('.emo-filter-label')?.replaceChildren(document.createTextNode('Filtros'));
			}

			let shell = document.querySelector('.emo-mobile-filter-shell');
			if (!shell) {
				shell = document.createElement('div');
				shell.className = 'emo-mobile-filter-shell';
				shell.hidden = true;
				shell.innerHTML = '<aside class="emo-mobile-filter-panel" id="emo-mobile-filter-panel" aria-label="Filtros de productos"><div class="emo-mobile-filter-head"><h2 class="emo-mobile-filter-title">Filtros</h2><button type="button" class="emo-mobile-filter-close" aria-label="Cerrar filtros">×</button></div><div class="emo-mobile-filter-content"></div></aside>';
				body.append(shell);
			}
			const content = shell.querySelector('.emo-mobile-filter-content');
			const close = shell.querySelector('.emo-mobile-filter-close');
			const title = shell.querySelector('.emo-mobile-filter-title');
			if (title) title.textContent = 'Filtros';
			if (!content || !toggle) return;

			const moveIn = () => { if (sidebar.parentElement !== content) content.append(sidebar); };
			const moveOut = () => {
				if (homeMarker?.parentNode && sidebar.parentElement === content) homeMarker.parentNode.insertBefore(sidebar, homeMarker.nextSibling);
			};
			const shut = (focus = false) => {
				shell.hidden = true;
				toggle.setAttribute('aria-expanded','false');
				document.documentElement.classList.remove('emo-shop-filter-open');
				body.classList.remove('emo-shop-filter-open');
				if (focus && compact()) toggle.focus();
			};
			const open = () => {
				if (!compact()) return;
				moveIn();
				shell.hidden = false;
				toggle.setAttribute('aria-expanded','true');
				document.documentElement.classList.add('emo-shop-filter-open');
				body.classList.add('emo-shop-filter-open');
				requestAnimationFrame(() => close?.focus());
			};
			const sync = () => {
				if (compact()) moveIn();
				else { shut(false); moveOut(); }
			};

			/* Sustituimos nodos para descartar listeners heredados con breakpoint 991. */
			const freshToggle = toggle.cloneNode(true);
			toggle.replaceWith(freshToggle);
			toggle = freshToggle;
			const freshClose = close ? close.cloneNode(true) : null;
			if (close && freshClose) close.replaceWith(freshClose);
			toggle.addEventListener('click', () => toggle.getAttribute('aria-expanded') === 'true' ? shut(true) : open());
			freshClose?.addEventListener('click', () => shut(true));
			shell.addEventListener('click', (event) => { if (event.target === shell) shut(true); });
			document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && !shell.hidden) shut(true); });
			window.addEventListener('resize', () => requestAnimationFrame(sync), {passive:true});
			sync();
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
