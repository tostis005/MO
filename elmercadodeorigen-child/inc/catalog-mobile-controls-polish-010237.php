<?php
/**
 * Pulido final de los controles móviles del catálogo.
 *
 * Elimina el marco interior heredado de WCFM para que Tienda y productor usen
 * una única tarjeta visual, y dibuja una flecha de ordenación propia con aire
 * suficiente respecto al borde derecho.
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
		<style id="elmercado-catalog-mobile-controls-polish-010237">
			@media (max-width:991px) {
				/*
				 * WCFM envuelve contador + ordenación en .elmercado-vendor-toolbar,
				 * que conserva su propia tarjeta. La neutralizamos con una especificidad
				 * superior a las capas históricas para que sólo exista la tarjeta exterior.
				 */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .emo-toolbar-inner-frame-010237,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .elmercado-vendor-toolbar,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .elmercado-vendor-toolbar.emo-toolbar-inner-frame-010237 {
					display:contents !important;
					width:auto !important;
					min-width:0 !important;
					max-width:none !important;
					height:auto !important;
					min-height:0 !important;
					max-height:none !important;
					border:0 !important;
					border-radius:0 !important;
					background:transparent !important;
					background-color:transparent !important;
					box-shadow:none !important;
					outline:0 !important;
					margin:0 !important;
					padding:0 !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .elmercado-vendor-toolbar::before,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .elmercado-vendor-toolbar::after {
					content:none !important;
					display:none !important;
				}

				/* La única caja visible es la tarjeta exterior compartida. */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
					border:1px solid rgba(23,63,50,.12) !important;
					border-radius:16px !important;
					background:#fff !important;
					box-shadow:0 8px 24px rgba(17,42,34,.055) !important;
					overflow:visible !important;
				}

				/* Evita marcos de clearfix/wrappers vacíos que WCFM pueda reinsertar. */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 > :is(.clear,.clearfix):not(.woocommerce-ordering) {
					display:none !important;
				}

				/* Flecha propia, idéntica en Tienda y productor y separada del borde. */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					position:relative !important;
					overflow:visible !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					-webkit-appearance:none !important;
					appearance:none !important;
					background-image:none !important;
					padding-right:42px !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::after,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering::after {
					content:"" !important;
					position:absolute !important;
					top:50% !important;
					right:16px !important;
					width:8px !important;
					height:8px !important;
					box-sizing:border-box !important;
					border:0 !important;
					border-right:1.5px solid #173f32 !important;
					border-bottom:1.5px solid #173f32 !important;
					background:transparent !important;
					transform:translateY(-65%) rotate(45deg) !important;
					transform-origin:center !important;
					pointer-events:none !important;
					z-index:3 !important;
				}
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
		<script id="elmercado-catalog-mobile-controls-polish-script-010237">
		(() => {
			'use strict';
			const mobile = window.matchMedia('(max-width:991px)');
			const force = (node, name, value) => node?.style?.setProperty(name, value, 'important');

			const commonAncestorInside = (toolbar, a, b) => {
				if (!toolbar || !a || !b) return null;
				let node = a.parentElement;
				while (node && node !== toolbar) {
					if (node.contains(b)) return node;
					node = node.parentElement;
				}
				return null;
			};

			const flattenInnerFrame = (inner) => {
				if (!inner) return;
				inner.classList.add('emo-toolbar-inner-frame-010237');
				[
					['display','contents'],
					['width','auto'],
					['min-width','0'],
					['max-width','none'],
					['height','auto'],
					['min-height','0'],
					['max-height','none'],
					['border','0'],
					['border-radius','0'],
					['background','transparent'],
					['background-color','transparent'],
					['box-shadow','none'],
					['outline','0'],
					['margin','0'],
					['padding','0']
				].forEach(([name,value]) => force(inner,name,value));
			};

			const polish = () => {
				if (!mobile.matches) return;
				document.querySelectorAll('.emo-catalog-toolbar-shared-010229').forEach((toolbar) => {
					const count = toolbar.querySelector('.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225');
					const ordering = toolbar.querySelector('.woocommerce-ordering');
					if (!count || !ordering) return;

					/*
					 * En WCFM ambos controles llegan dentro de .elmercado-vendor-toolbar.
					 * Además de CSS, aplicamos inline !important para vencer cualquier regla
					 * histórica que se inyecte después de esta capa.
					 */
					flattenInnerFrame(commonAncestorInside(toolbar, count, ordering));

					force(count,'writing-mode','horizontal-tb');
					force(count,'white-space','nowrap');
					force(ordering,'position','relative');
					const select = ordering.querySelector('select');
					if (select) {
						force(select,'appearance','none');
						force(select,'-webkit-appearance','none');
						force(select,'background-image','none');
						force(select,'padding-right','42px');
					}
				});
			};

			polish();
			requestAnimationFrame(polish);
			setTimeout(polish,250);
			setTimeout(polish,900);
			setTimeout(polish,1800);
			window.addEventListener('pageshow',polish,{passive:true});
			window.addEventListener('resize',polish,{passive:true});
			new MutationObserver(() => requestAnimationFrame(polish)).observe(document.body,{childList:true,subtree:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
