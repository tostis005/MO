<?php
/**
 * Sticky real y estado activo de filtros de escritorio 0.10.91.
 *
 * Woostify mantiene #view como contenedor de recorte. En escritorio, ese
 * recorte convierte el rail sticky en un elemento que se desplaza con toda la
 * página. Se libera únicamente en archivos de producto > 1100 px y se marca
 * de forma declarativa el término activo.
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

		$term_link = '';
		if ( is_product_category() || is_product_tag() ) {
			$term = get_queried_object();
			if ( $term instanceof WP_Term ) {
				$link = get_term_link( $term );
				if ( ! is_wp_error( $link ) ) {
					$term_link = $link;
				}
			}
		}
		?>
		<style id="elmercado-desktop-filter-sticky-final-01091">
			@media (min-width: 1101px) {
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #view,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content,
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) #content.site-content > .woostify-container {
					overflow: visible !important;
					overflow-x: visible !important;
					overflow-y: visible !important;
				}

				/* En archivos de taxonomía el panel y la toolbar comienzan juntos. */
				body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) {
					margin-top: 0 !important;
				}

				/* El término activo debe leerse a simple vista en escritorio. */
				body.elmercado-child-theme:is(.woocommerce-shop,.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) .widget_product_categories .current-cat > a {
					margin: 4px 0 !important;
					padding: 8px 10px !important;
					border-radius: 9px !important;
					background: #eaf2ed !important;
					color: #173f32 !important;
					font-weight: 850 !important;
				}
				<?php if ( $term_link ) : ?>
				body.elmercado-child-theme:is(.tax-product_cat,.tax-product_tag):not(.wcfmmp-store-page) :is(#secondary.widget-area,.shop-widget-area) a[href="<?php echo esc_url( $term_link ); ?>"] {
					background: #173f32 !important;
					color: #fff !important;
					border-color: #173f32 !important;
					font-weight: 850 !important;
				}
				<?php endif; ?>
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);