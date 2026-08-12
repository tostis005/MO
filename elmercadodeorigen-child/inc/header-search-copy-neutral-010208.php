<?php
/**
 * Copy neutro del buscador de cabecera 0.10.208.
 *
 * Mantiene el diseño y el comportamiento del diálogo existente, pero elimina
 * la capa editorial/comercial añadida al texto de búsqueda.
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
		<style id="elmercado-header-search-copy-neutral-010208">
			body.elmercado-child-theme .site-dialog-search .dialog-search-title::before,
			body.elmercado-child-theme .site-dialog-search .dialog-search-title::after {
				content: none !important;
				display: none !important;
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
		<script id="elmercado-header-search-copy-neutral-controller-010208">
		(() => {
			'use strict';

			const root = document.querySelector('.site-dialog-search');
			if (!root) return;

			const title = root.querySelector('.dialog-search-title');
			if (title) title.textContent = 'Buscar productos';

			root.querySelectorAll('input[type="search"], .search-field').forEach((input) => {
				input.setAttribute('placeholder', 'Buscar productos');
				input.setAttribute('aria-label', 'Buscar productos');
			});

			root.querySelectorAll('button[type="submit"], .search-submit').forEach((button) => {
				button.textContent = 'Buscar';
				button.setAttribute('aria-label', 'Buscar');
			});

			root.querySelectorAll('input[type="submit"]').forEach((button) => {
				button.value = 'Buscar';
				button.setAttribute('aria-label', 'Buscar');
			});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
