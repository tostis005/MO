<?php
/**
 * Control mínimo del menú móvil en la portada optimizada.
 *
 * En el resto del sitio Woostify conserva su navegación. En portada retiramos
 * ese script para ahorrar trabajo, por lo que este fragmento se limita a
 * alternar la clase que ya observa y gestiona theme.js.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! elmercado_is_optimized_home() ) {
			return;
		}

		$script = <<<'JS'
(() => {
	const trigger = document.querySelector('.toggle-sidebar-menu-btn');
	if (!trigger) return;

	trigger.addEventListener('click', (event) => {
		event.preventDefault();
		document.documentElement.classList.toggle('sidebar-menu-open');
	});
})();
JS;

		wp_add_inline_script( 'elmercado-theme', $script, 'after' );
	},
	10001
);
