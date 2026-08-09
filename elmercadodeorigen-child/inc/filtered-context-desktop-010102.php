<?php
/**
 * Mantiene la alineación del rail de filtros en escritorio.
 *
 * El contexto adicional tras filtrar es necesario en móvil/tablet, donde el
 * filtro vive en un panel. En escritorio el rail lateral ya aporta continuidad
 * visual y debe seguir alineado con la barra del catálogo.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-filtered-context-desktop-010102">
			@media (min-width: 1101px) {
				body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag) .emo-shop-lead--filtered {
					display: none !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
