<?php
/**
 * Cierre visual y estructural de la versión editorial.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Añade los últimos ajustes sin crear peticiones CSS adicionales. En la portada
 * se insertan junto a la hoja base; en el resto del sitio, junto a editorial.css.
 */
add_action(
	'wp_enqueue_scripts',
	static function (): void {
		$stylesheets = array(
			ELMERCADO_THEME_PATH . '/assets/css/editorial-finish.css',
			ELMERCADO_THEME_PATH . '/assets/css/ux-polish.css',
		);
		$contents = array();

		foreach ( $stylesheets as $stylesheet ) {
			if ( ! is_readable( $stylesheet ) ) {
				continue;
			}

			$content = file_get_contents( $stylesheet );
			if ( false !== $content && '' !== trim( $content ) ) {
				$contents[] = $content;
			}
		}

		if ( empty( $contents ) ) {
			return;
		}

		$handle = 'elmercado-editorial';

		if ( function_exists( 'elmercado_is_optimized_home' ) && elmercado_is_optimized_home() ) {
			$handle = wp_style_is( 'woostify-parent-style', 'registered' )
				? 'woostify-parent-style'
				: ( wp_style_is( 'woostify-parent', 'registered' ) ? 'woostify-parent' : $handle );
		}

		wp_add_inline_style( $handle, (string) preg_replace( '!/\*.*?\*/!s', '', implode( "\n", $contents ) ) );
	},
	10100
);

/**
 * Woostify no imprime siempre el título de la tienda. Se construye un encabezado
 * propio para que la página tenga jerarquía visual y un H1 semántico estable.
 */
add_action(
	'woocommerce_before_shop_loop',
	static function (): void {
		if ( ! is_shop() ) {
			return;
		}
		?>
		<header class="emo-shop-title-block">
			<span class="emo-kicker"><?php esc_html_e( 'El mercado', 'elmercadodeorigen' ); ?></span>
			<h1><?php esc_html_e( 'Productos', 'elmercadodeorigen' ); ?></h1>
		</header>
		<?php
	},
	2
);

/**
 * WCFM recalcula las tarjetas después del renderizado y añade desplazamientos en
 * zigzag. Esta normalización se ejecuta únicamente en el directorio público de
 * productores y vuelve a aplicar la composición editorial tras esos cálculos.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! is_page( 'productores' ) ) {
			return;
		}
		?>
		<script id="elmercado-producer-layout-normalizer">
		(() => {
			const important = (element, property, value) => {
				if (element) element.style.setProperty(property, value, 'important');
			};

			const normalize = () => {
				const list = document.querySelector('#wcfmmp-stores-wrap ul.wcfmmp-store-wrap');
				if (!list) return;

				important(list, 'display', 'grid');
				important(list, 'width', '100%');
				important(list, 'grid-template-columns', matchMedia('(max-width: 767px)').matches ? '1fr' : 'repeat(2, minmax(0, 1fr))');
				important(list, 'gap', '1.25rem');

				list.querySelectorAll(':scope > li.wcfmmp-single-store').forEach((card) => {
					['top', 'right', 'bottom', 'left'].forEach((property) => important(card, property, 'auto'));
					important(card, 'position', 'relative');
					important(card, 'display', 'block');
					important(card, 'width', 'auto');
					important(card, 'min-width', '0');
					important(card, 'max-width', 'none');
					important(card, 'margin', '0');
					important(card, 'float', 'none');
					important(card, 'grid-column', 'auto');
					important(card, 'grid-row', 'auto');
					important(card, 'transform', 'none');

					const wrapper = card.querySelector('.store-wrapper');
					if (wrapper) {
						['top', 'right', 'bottom', 'left'].forEach((property) => important(wrapper, property, 'auto'));
						important(wrapper, 'position', 'relative');
						important(wrapper, 'width', '100%');
						important(wrapper, 'min-width', '0');
						important(wrapper, 'max-width', 'none');
						important(wrapper, 'height', '100%');
						important(wrapper, 'margin', '0');
						important(wrapper, 'float', 'none');
						important(wrapper, 'transform', 'none');
					}

					/* La consulta pertenece al plugin de vendedor y no forma parte del directorio público. */
					card.querySelectorAll('.store-enquiry, .wcfm_catalog_enquiry').forEach((control) => {
						const container = control.closest('.store-enquiry');
						(container || control).remove();
					});

					const producerName = card.querySelector('.store-data h2, .store-data h2 a')?.textContent?.trim() || 'el productor';
					const visitButton = card.querySelector('a.wcfmmp-visit-store');

					if (visitButton) {
						visitButton.textContent = 'Visitar';
						visitButton.setAttribute('aria-label', `Visitar la tienda de ${producerName}`);
						visitButton.setAttribute('title', `Visitar la tienda de ${producerName}`);
						important(visitButton, 'display', 'inline-flex');
						important(visitButton, 'align-items', 'center');
						important(visitButton, 'justify-content', 'center');
						important(visitButton, 'white-space', 'nowrap');
						important(visitButton, 'line-height', '1');
						important(visitButton, 'min-height', '44px');
					}
				});
			};

			const observer = new MutationObserver(() => window.requestAnimationFrame(normalize));
			const root = document.querySelector('#wcfmmp-stores-wrap');
			if (root) observer.observe(root, { childList: true, subtree: true });

			[0, 300, 900, 1800, 3000].forEach((delay) => window.setTimeout(normalize, delay));
			window.addEventListener('resize', normalize, { passive: true });
			window.setTimeout(() => observer.disconnect(), 6000);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
