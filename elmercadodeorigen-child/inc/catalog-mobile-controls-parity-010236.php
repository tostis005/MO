<?php
/**
 * Paridad final de controles móviles entre Tienda y tiendas de productor.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Etiquetas cortas que conservan exactamente los valores de ordenación de Woo.
 *
 * @param array<string,string> $options Opciones nativas.
 * @return array<string,string>
 */
function elmercado_catalog_mobile_order_labels_010236( array $options ): array {
	$labels = array(
		'menu_order' => 'Recomendados',
		'popularity' => 'Más populares',
		'rating'     => 'Mejor valorados',
		'date'       => 'Más recientes',
		'price'      => 'Menor precio',
		'price-desc' => 'Mayor precio',
	);
	foreach ( $labels as $value => $label ) {
		if ( array_key_exists( $value, $options ) ) {
			$options[ $value ] = $label;
		}
	}
	return $options;
}
add_filter( 'woocommerce_catalog_orderby', 'elmercado_catalog_mobile_order_labels_010236', PHP_INT_MAX );
add_filter( 'woocommerce_default_catalog_orderby_options', 'elmercado_catalog_mobile_order_labels_010236', PHP_INT_MAX );

/**
 * El script histórico acercaba el grid del productor al toolbar con translate.
 * Desde que existe un botón de filtros entre ambos, ese cálculo invade el botón.
 */
function elmercado_catalog_mobile_remove_vendor_rhythm_010236(): void {
	global $wp_filter;
	if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}
	$legacy_file = wp_normalize_path( ELMERCADO_THEME_PATH . '/inc/vendor-toolbar-mobile-final.php' );
	foreach ( $wp_filter['wp_footer']->callbacks as $priority => $callbacks ) {
		foreach ( $callbacks as $callback_data ) {
			$callback = $callback_data['function'] ?? null;
			if ( ! $callback instanceof Closure ) {
				continue;
			}
			try {
				$reflection = new ReflectionFunction( $callback );
				$filename   = $reflection->getFileName();
			} catch ( Throwable $throwable ) {
				continue;
			}
			if ( is_string( $filename ) && wp_normalize_path( $filename ) === $legacy_file ) {
				remove_action( 'wp_footer', $callback, (int) $priority );
			}
		}
	}
}
elmercado_catalog_mobile_remove_vendor_rhythm_010236();

add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-mobile-controls-parity-010236">
			/* Contrato del selector, idéntico en Tienda y WCFM. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				box-sizing:border-box !important; position:static !important; inset:auto !important; float:none !important; clear:none !important;
				width:250px !important; min-width:250px !important; max-width:250px !important;
				margin:0 !important; padding:0 !important; transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
				box-sizing:border-box !important; display:block !important; width:250px !important; min-width:250px !important; max-width:250px !important; height:40px !important; min-height:40px !important;
				margin:0 !important; padding:0 30px 0 12px !important; border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important; background:#f7f9f6 !important; box-shadow:none !important; color:#173f32 !important;
				font-family:inherit !important; font-size:12px !important; font-weight:700 !important; letter-spacing:0 !important; line-height:1 !important;
			}

			@media (max-width:991px) {
				/* Pseudo-clears antiguos no pueden crear una segunda fila en el grid. */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229::before,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229::after,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229::before,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229::after {
					content:none !important; display:none !important;
				}

				/* WCFM no puede estrechar el área que contiene toolbar, filtro y grid. */
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .body_area,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store :is(.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area) {
					display:block !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
					float:none !important; clear:both !important; margin-left:0 !important; margin-right:0 !important; transform:none !important;
				}

				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
					position:relative !important; z-index:2 !important; display:grid !important; grid-template-columns:minmax(0,1fr) 148px !important;
					grid-template-rows:42px !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important; height:auto !important; min-height:60px !important; max-height:none !important;
					align-items:center !important; justify-content:stretch !important; gap:8px !important; clear:both !important; float:none !important;
					margin:0 0 10px !important; padding:9px 10px !important; transform:none !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225),
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-vendor-result-count-010225) {
					grid-column:1 !important; grid-row:1 !important; display:flex !important; width:auto !important; min-width:0 !important; max-width:100% !important; min-height:42px !important;
					align-items:center !important; overflow:hidden !important; margin:0 !important; padding:0 !important; font-size:11px !important; line-height:1.25 !important;
					white-space:nowrap !important; word-break:normal !important; overflow-wrap:normal !important; writing-mode:horizontal-tb !important; text-orientation:mixed !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225) *,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-vendor-result-count-010225) * {
					white-space:inherit !important; word-break:normal !important; overflow-wrap:normal !important; writing-mode:horizontal-tb !important; text-orientation:mixed !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					grid-column:2 !important; grid-row:1 !important; display:flex !important; flex:none !important; box-sizing:border-box !important; width:148px !important; min-width:148px !important; max-width:148px !important;
					height:42px !important; min-height:42px !important; max-height:42px !important; align-items:center !important; margin:0 !important; padding:0 !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					box-sizing:border-box !important; width:148px !important; min-width:148px !important; max-width:148px !important; height:42px !important; min-height:42px !important; max-height:42px !important;
					padding:0 26px 0 10px !important; border:1px solid rgba(23,63,50,.14) !important; border-radius:999px !important;
					background:#f7f9f6 !important; box-shadow:none !important; color:#173f32 !important; font-size:11.5px !important; font-weight:700 !important; line-height:1 !important;
				}

				/* Mismo trigger visual y mismo flujo vertical. */
				html body.elmercado-child-theme :is(#emo-premium-filter-toggle,.emo-mobile-filter-toggle.emo-filter-toggle-shared-010229),
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
					position:relative !important; z-index:2 !important; display:flex !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
					height:48px !important; min-height:48px !important; align-items:center !important; justify-content:flex-start !important; gap:10px !important;
					clear:both !important; float:none !important; margin:0 0 18px !important; padding:0 16px !important;
					border:1px solid rgba(23,63,50,.16) !important; border-radius:14px !important; background:rgba(255,253,248,.92) !important;
					box-shadow:0 7px 20px rgba(23,63,50,.045) !important; color:#173f32 !important; font-family:inherit !important;
					font-size:13px !important; font-weight:820 !important; letter-spacing:.01em !important; line-height:1.2 !important; text-align:left !important;
					transform:none !important; isolation:isolate !important;
				}
				html body.elmercado-child-theme :is(#emo-premium-filter-toggle,.emo-mobile-filter-toggle.emo-filter-toggle-shared-010229)::before {
					content:"" !important; width:18px !important; height:18px !important; flex:0 0 18px !important;
					background:linear-gradient(#2f6650,#2f6650) 0 3px/18px 1px no-repeat,linear-gradient(#2f6650,#2f6650) 0 9px/18px 1px no-repeat,linear-gradient(#2f6650,#2f6650) 0 15px/18px 1px no-repeat !important;
				}
				html body.elmercado-child-theme :is(#emo-premium-filter-toggle,.emo-mobile-filter-toggle.emo-filter-toggle-shared-010229) .emo-filter-label { margin-right:auto !important; }
				html body.elmercado-child-theme :is(#emo-premium-filter-toggle,.emo-mobile-filter-toggle.emo-filter-toggle-shared-010229) .emo-filter-chevron { display:none !important; }

				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products {
					position:relative !important; z-index:1 !important; clear:both !important; float:none !important; width:100% !important; min-width:0 !important; max-width:100% !important;
					margin-top:0 !important; padding-top:0 !important; transform:none !important;
				}
			}

			@media (max-width:360px) {
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 { grid-template-columns:minmax(0,1fr) 138px !important; }
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select { width:138px !important; min-width:138px !important; max-width:138px !important; }
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
		<script id="elmercado-catalog-mobile-controls-parity-script-010236">
		(() => {
			'use strict';
			const labels={menu_order:'Recomendados',popularity:'Más populares',rating:'Mejor valorados',date:'Más recientes',price:'Menor precio','price-desc':'Mayor precio'};
			const setImportant=(node,name,value)=>node?.style?.setProperty(name,value,'important');
			const normalise=()=>{
				const viewport=window.innerWidth;
				const compact=viewport<=991;
				const width=viewport<=360?'138px':(compact?'148px':'250px');
				document.querySelectorAll('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering').forEach(form=>{
					setImportant(form,'box-sizing','border-box'); setImportant(form,'width',width); setImportant(form,'min-width',width); setImportant(form,'max-width',width);
				});
				document.querySelectorAll('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select').forEach(select=>{
					select.classList.add('emo-order-select-parity-010236');
					setImportant(select,'box-sizing','border-box'); setImportant(select,'width',width); setImportant(select,'min-width',width); setImportant(select,'max-width',width);
					[...select.options].forEach(option=>{
						if(labels[option.value]) option.textContent=labels[option.value];
						else option.textContent=(option.textContent||'').replace(/^Ordenar\s+por\s+/i,'').trim();
					});
				});
				const shopToggle=document.querySelector('#emo-premium-filter-toggle');
				const vendorToggle=document.querySelector('#wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229');
				shopToggle?.classList.add('emo-mobile-filter-control-parity-010236');
				vendorToggle?.classList.add('emo-mobile-filter-control-parity-010236');
				if(vendorToggle){
					const vendorLabel=vendorToggle.querySelector('.emo-filter-label');
					if(vendorLabel) vendorLabel.textContent='Filtros';
					const vendorShell=document.querySelector('.emo-mobile-filter-shell.emo-filter-shell-shared-010229');
					const vendorTitle=vendorShell?.querySelector('.emo-mobile-filter-title');
					if(vendorTitle) vendorTitle.textContent='Filtros';
				}
				if(compact){
					const store=document.querySelector('#wcfmmp-store');
					if(store){
						store.querySelectorAll('.body_area,.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area').forEach(node=>{
							setImportant(node,'box-sizing','border-box'); setImportant(node,'width','100%'); setImportant(node,'min-width','0'); setImportant(node,'max-width','100%');
							setImportant(node,'float','none'); setImportant(node,'clear','both'); setImportant(node,'margin-left','0'); setImportant(node,'margin-right','0'); setImportant(node,'transform','none');
						});
						const toolbar=store.querySelector('.emo-catalog-toolbar-shared-010229');
						if(toolbar){
							setImportant(toolbar,'box-sizing','border-box'); setImportant(toolbar,'width','100%'); setImportant(toolbar,'min-width','0'); setImportant(toolbar,'max-width','100%'); setImportant(toolbar,'transform','none');
							const count=toolbar.querySelector('.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225');
							if(count){
								setImportant(count,'min-width','0'); setImportant(count,'max-width','100%'); setImportant(count,'white-space','nowrap'); setImportant(count,'word-break','normal'); setImportant(count,'overflow-wrap','normal'); setImportant(count,'writing-mode','horizontal-tb'); setImportant(count,'text-orientation','mixed'); setImportant(count,'overflow','hidden');
							}
						}
						if(vendorToggle){setImportant(vendorToggle,'box-sizing','border-box'); setImportant(vendorToggle,'width','100%'); setImportant(vendorToggle,'min-width','0'); setImportant(vendorToggle,'max-width','100%');}
						const products=store.querySelector('ul.products');
						products?.style.removeProperty('transform'); products?.style.removeProperty('margin-bottom');
					}
				}
			};
			normalise(); requestAnimationFrame(normalise); setTimeout(normalise,250); setTimeout(normalise,900); setTimeout(normalise,1800);
			window.addEventListener('pageshow',normalise,{passive:true});
			window.addEventListener('resize',normalise,{passive:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
