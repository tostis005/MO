<?php
/**
 * Ajuste final de los CTA del hero y equilibrio del carrito móvil 0.10.119.
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
		<style id="elmercado-home-hero-cart-balance-010119">
			/* Home: recuperar exactamente la pareja de CTA anterior al cambio de paleta 0.10.117. */
			body.home.elmercado-child-theme .emo-hero .emo-button--accent {
				background: var(--emo-clay) !important;
				border-color: var(--emo-clay) !important;
				color: #fff !important;
			}
			body.home.elmercado-child-theme .emo-hero .emo-button--accent:hover {
				background: #e07c50 !important;
				border-color: #e07c50 !important;
				color: #fff !important;
			}
			body.home.elmercado-child-theme .emo-hero .emo-button--ghost {
				background: transparent !important;
				border-color: rgba(255, 255, 255, 0.35) !important;
				color: #fff !important;
			}
			body.home.elmercado-child-theme .emo-hero .emo-button--ghost:hover {
				background: rgba(255, 255, 255, 0.1) !important;
				border-color: rgba(255, 255, 255, 0.65) !important;
				color: #fff !important;
			}

			@media (max-width: 767px) {
				/* Carrito: más aire junto a la imagen y más ancho útil para el contenido. */
				body.elmercado-child-theme.woocommerce-cart .woocommerce-cart-form {
					padding: 0.55rem !important;
				}
				body.elmercado-child-theme.woocommerce-cart table.shop_table_responsive tr.woocommerce-cart-form__cart-item {
					grid-template-columns: 70px minmax(0, 1fr) !important;
					gap: 0.1rem 0.7rem !important;
					padding: 0.85rem 0.7rem 0.85rem 1.1rem !important;
				}
				body.elmercado-child-theme.woocommerce-cart .product-thumbnail {
					width: 70px !important;
				}
				body.elmercado-child-theme.woocommerce-cart .product-thumbnail img {
					width: 66px !important;
					height: 84px !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
