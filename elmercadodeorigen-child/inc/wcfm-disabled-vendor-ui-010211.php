<?php
/**
 * Limpieza visual de vendedores WCFM desactivados en el catálogo público.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$elmercado_wcfm_publish_policy_010216 = __DIR__ . '/wcfm-disabled-vendor-publish-policy-010216.php';
if ( is_readable( $elmercado_wcfm_publish_policy_010216 ) ) {
	require_once $elmercado_wcfm_publish_policy_010216;
}
unset( $elmercado_wcfm_publish_policy_010216 );

/**
 * Devuelve los vendedores WCFM bloqueados que deben desaparecer del filtro.
 *
 * @return int[]
 */
function elmercado_wcfm_disabled_vendor_ui_ids_010211(): array {
	if ( ! function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) || elmercado_wcfm_disabled_visibility_can_view_010210() ) {
		return array();
	}
	if ( ! function_exists( 'elmercado_wcfm_disabled_vendor_ids_010210' ) ) {
		return array();
	}

	return array_values( array_filter( array_map( 'absint', elmercado_wcfm_disabled_vendor_ids_010210() ) ) );
}

/**
 * Una URL antigua con vendor_id bloqueado no debe generar chip/selección activa.
 * La consulta principal ya ha sido restringida por el guard de visibilidad.
 */
add_action(
	'template_redirect',
	static function (): void {
		if ( ! function_exists( 'elmercado_wcfm_disabled_visibility_can_view_010210' ) || elmercado_wcfm_disabled_visibility_can_view_010210() ) {
			return;
		}

		$vendor_id = function_exists( 'elmercado_wcfm_requested_vendor_id_010210' ) ? elmercado_wcfm_requested_vendor_id_010210() : 0;
		if ( $vendor_id <= 0 || ! function_exists( 'elmercado_wcfm_vendor_is_disabled_010210' ) || ! elmercado_wcfm_vendor_is_disabled_010210( $vendor_id ) ) {
			return;
		}

		unset( $_GET['vendor_id'], $_REQUEST['vendor_id'] );
	},
	1
);

/**
 * El CSS principal del catálogo usa selectores muy específicos con !important.
 * Repetimos el ID del bloque para superar esa especificidad y evitar cualquier
 * parpadeo antes de que el DOM termine de montarse.
 */
add_action(
	'wp_head',
	static function (): void {
		$ids = elmercado_wcfm_disabled_vendor_ui_ids_010211();
		if ( ! $ids ) {
			return;
		}

		$selectors = array();
		foreach ( $ids as $vendor_id ) {
			$selectors[] = 'html body.elmercado-child-theme #emo-global-vendor-filter#emo-global-vendor-filter .emo-global-vendor-filter__item[data-vendor-id="' . $vendor_id . '"]';
			$selectors[] = 'html body.elmercado-child-theme #emo-global-vendor-filter#emo-global-vendor-filter [data-vendor-id="' . $vendor_id . '"]';
		}
		?>
		<style id="elmercado-wcfm-disabled-vendor-ui-010211">
			<?php echo esc_html( implode( ',', $selectors ) ); ?> { display:none !important; visibility:hidden !important; pointer-events:none !important; }
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Defensa de DOM: además del CSS, elimina físicamente las filas bloqueadas.
 * Es un script inline pequeño y no depende de jQuery ni de otros assets.
 */
add_action(
	'wp_footer',
	static function (): void {
		$ids = elmercado_wcfm_disabled_vendor_ui_ids_010211();
		if ( ! $ids ) {
			return;
		}
		?>
		<script id="elmercado-wcfm-disabled-vendor-dom-010211">
		(function(){
			var ids=<?php echo wp_json_encode( array_map( 'strval', $ids ) ); ?>;
			function clean(){
				ids.forEach(function(id){
					document.querySelectorAll('#emo-global-vendor-filter [data-vendor-id="'+id+'"]')
						.forEach(function(node){
							var row=node.closest('.emo-global-vendor-filter__item') || node;
							if(row && row.parentNode){ row.parentNode.removeChild(row); }
						});
				});
			}
			clean();
			if(document.readyState==='loading'){
				document.addEventListener('DOMContentLoaded',clean,{once:true});
			}
			window.setTimeout(clean,0);
			window.setTimeout(clean,250);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
