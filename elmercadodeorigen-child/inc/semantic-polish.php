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

			/* Los SVG repetidos del logotipo conservan referencias internas únicas. */
			const seenIds = new Set();
			let duplicateIndex = 0;
			document.querySelectorAll('[id]').forEach((element) => {
				const originalId = element.id;
				if (!seenIds.has(originalId)) {
					seenIds.add(originalId);
					return;
				}

				const svg = element.closest('svg');
				if (!svg) {
					return;
				}

				duplicateIndex += 1;
				const replacementId = `${originalId}-emo-${duplicateIndex}`;
				element.id = replacementId;

				svg.querySelectorAll('*').forEach((node) => {
					[...node.attributes].forEach((attribute) => {
						const updated = attribute.value
							.replaceAll(`#${originalId}`, `#${replacementId}`)
							.replaceAll(`url(${originalId})`, `url(${replacementId})`);
						if (updated !== attribute.value) node.setAttribute(attribute.name, updated);
					});
				});
			});

			<?php if ( ! is_front_page() && ( is_home() || is_singular( 'post' ) || is_category() || is_tag() || is_author() || is_date() ) ) : ?>
			/* Sólo el contexto editorial necesita comprobar si conviven varios H1 visibles. */
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
			<?php endif; ?>

			/* Cualquier tarjeta editorial sin imagen recibe el acabado de marca. */
			document.querySelectorAll('.emo-article-card__media').forEach((media) => {
				if (media.querySelector('img')) {
					return;
				}

				let placeholder = media.querySelector('.emo-article-card__placeholder');
				if (!placeholder) {
					placeholder = media.querySelector(':scope > span') || document.createElement('span');
					if (!placeholder.isConnected) media.append(placeholder);
				}
				placeholder.classList.add('emo-article-card__placeholder');
				placeholder.setAttribute('aria-hidden', 'true');
			});

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
