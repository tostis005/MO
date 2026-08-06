<?php
/**
 * Pequeñas correcciones semánticas que no alteran la composición visual.
 *
 * @package ElMercadoDeOrigen
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
		?>
		<script id="elmercado-semantic-polish">
		(() => {
			/* Woostify duplica los mismos ID del menú de escritorio en el panel móvil. */
			const mobileNavigation = document.querySelector('#mobile-navigation, .sidebar-menu');
			if (mobileNavigation) {
				const counts = new Map();
				document.querySelectorAll('[id]').forEach((element) => counts.set(element.id, (counts.get(element.id) || 0) + 1));
				mobileNavigation.querySelectorAll('[id]').forEach((element) => {
					if (element !== mobileNavigation && (counts.get(element.id) || 0) > 1) {
						element.removeAttribute('id');
					}
				});
			}

			/* En el archivo del blog el nombre del sitio no debe competir con el H1 editorial. */
			const visibleHeadingOne = [...document.querySelectorAll('h1')].filter((heading) => {
				const style = getComputedStyle(heading);
				return style.display !== 'none' && style.visibility !== 'hidden' && heading.getClientRects().length > 0;
			});
			const brandingHeading = document.querySelector('.site-branding h1.site-title');

			if (brandingHeading && visibleHeadingOne.length > 1) {
				const replacement = document.createElement('div');
				[...brandingHeading.attributes].forEach((attribute) => replacement.setAttribute(attribute.name, attribute.value));
				replacement.removeAttribute('id');
				replacement.classList.add('site-title');
				replacement.append(...brandingHeading.childNodes);
				brandingHeading.replaceWith(replacement);
			}

			/* Los SVG de los controles son decorativos; el enlace aporta el nombre. */
			document.querySelectorAll('.site-tools svg, .emo-announcement svg, #shop-cart-sidebar a.remove svg').forEach((icon) => {
				icon.setAttribute('aria-hidden', 'true');
				icon.setAttribute('focusable', 'false');
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
