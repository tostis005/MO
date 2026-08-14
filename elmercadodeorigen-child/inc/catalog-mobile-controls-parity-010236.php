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
			/* Selector de ordenación compartido también en escritorio. */
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
				box-sizing:border-box !important; position:static !important; inset:auto !important; float:none !important; clear:none !important;
				width:250px !important; min-width:250px !important; max-width:250px !important; margin:0 !important; padding:0 !important; transform:none !important;
			}
			html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
				box-sizing:border-box !important; display:block !important; width:250px !important; min-width:250px !important; max-width:250px !important;
				height:40px !important; min-height:40px !important; margin:0 !important; padding:0 30px 0 12px !important;
				border:1px solid rgba(23,63,50,.14) !important; border-radius:999px !important; background:#f7f9f6 !important;
				box-shadow:none !important; color:#173f32 !important; font-family:inherit !important; font-size:12px !important; font-weight:700 !important;
				letter-spacing:0 !important; line-height:1 !important;
			}

			@media (max-width:991px) {
				/* Ningún clearfix histórico puede añadir una fila al toolbar. */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229::before,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229::after,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229::before,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229::after {
					content:none !important; display:none !important;
				}

				/* WCFM no puede mantener columnas/float de escritorio en móvil. */
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .body_area,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store :is(.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area) {
					display:block !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
					float:none !important; clear:both !important; margin-left:0 !important; margin-right:0 !important; transform:none !important;
				}

				/*
				 * Contrato móvil único. Usamos flex en lugar del grid histórico para que
				 * el contador nunca pueda colapsar a 0 px al convivir con el selector.
				 */
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
					position:relative !important; z-index:2 !important; display:flex !important; box-sizing:border-box !important;
					width:100% !important; min-width:0 !important; max-width:100% !important; height:68px !important; min-height:68px !important; max-height:68px !important;
					align-items:center !important; justify-content:flex-start !important; gap:10px !important; clear:both !important; float:none !important;
					margin:0 0 10px !important; padding:10px 12px !important; border:1px solid rgba(23,63,50,.12) !important; border-radius:16px !important;
					background:#fff !important; box-shadow:0 8px 24px rgba(17,42,34,.055) !important; font-size:15px !important; font-weight:430 !important;
					line-height:1.65 !important; transform:none !important;
				}

				/*
				 * La tienda WCFM vive dentro de un contenedor con gutter adicional. Sólo
				 * toolbar y botón salen de ese gutter para usar exactamente los mismos
				 * 16 px laterales que la Tienda general.
				 */
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 {
					left:50% !important; width:calc(100vw - 32px) !important; min-width:calc(100vw - 32px) !important; max-width:calc(100vw - 32px) !important;
					transform:translateX(-50%) !important;
				}

				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225),
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-vendor-result-count-010225) {
					position:static !important; inset:auto !important; display:flex !important; flex:1 1 0 !important; box-sizing:border-box !important;
					width:auto !important; min-width:0 !important; max-width:none !important; height:44px !important; min-height:44px !important; max-height:44px !important;
					align-items:center !important; align-self:center !important; justify-self:stretch !important; overflow:hidden !important;
					float:none !important; clear:none !important; margin:0 !important; padding:0 !important; color:#42564e !important;
					font-family:inherit !important; font-size:9.8px !important; font-weight:700 !important; letter-spacing:0 !important; line-height:1.35 !important;
					white-space:nowrap !important; word-break:normal !important; overflow-wrap:normal !important; writing-mode:horizontal-tb !important; text-orientation:mixed !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225) *,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 :is(.woocommerce-result-count,.emo-vendor-result-count-010225) * {
					white-space:inherit !important; word-break:normal !important; overflow-wrap:normal !important; writing-mode:horizontal-tb !important; text-orientation:mixed !important;
				}

				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering {
					position:static !important; inset:auto !important; display:flex !important; flex:0 0 148px !important; box-sizing:border-box !important;
					width:148px !important; min-width:148px !important; max-width:148px !important; height:44px !important; min-height:44px !important; max-height:44px !important;
					align-items:center !important; align-self:center !important; justify-content:flex-end !important; float:none !important; clear:none !important;
					margin:0 !important; padding:0 !important; transform:none !important;
				}
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					box-sizing:border-box !important; display:block !important; width:148px !important; min-width:148px !important; max-width:148px !important;
					height:42px !important; min-height:42px !important; max-height:42px !important; margin:0 !important; padding:0 26px 0 10px !important;
					border:1px solid rgba(23,63,50,.14) !important; border-radius:999px !important; background:#f7f9f6 !important;
					box-shadow:none !important; color:#173f32 !important; font-family:inherit !important; font-size:11.5px !important; font-weight:700 !important;
					letter-spacing:0 !important; line-height:1 !important;
				}

				/* Mismo trigger visual y mismo flujo vertical. */
				html body.elmercado-child-theme :is(#emo-premium-filter-toggle,.emo-mobile-filter-toggle.emo-filter-toggle-shared-010229),
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
					position:relative !important; z-index:2 !important; display:flex !important; box-sizing:border-box !important; width:100% !important; min-width:0 !important; max-width:100% !important;
					height:48px !important; min-height:48px !important; max-height:48px !important; align-items:center !important; justify-content:flex-start !important; gap:10px !important;
					clear:both !important; float:none !important; margin:0 0 18px !important; padding:0 16px !important;
					border:1px solid rgba(23,63,50,.16) !important; border-radius:14px !important; background:rgba(255,253,248,.92) !important;
					box-shadow:0 7px 20px rgba(23,63,50,.045) !important; color:#173f32 !important; font-family:inherit !important;
					font-size:13px !important; font-weight:820 !important; letter-spacing:.01em !important; line-height:1.2 !important; text-align:left !important;
					transform:none !important; isolation:isolate !important;
				}
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229 {
					left:50% !important; width:calc(100vw - 32px) !important; min-width:calc(100vw - 32px) !important; max-width:calc(100vw - 32px) !important;
					transform:translateX(-50%) !important;
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
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering,
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .emo-catalog-toolbar-shared-010229 .woocommerce-ordering select {
					flex-basis:138px !important; width:138px !important; min-width:138px !important; max-width:138px !important;
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
		<script id="elmercado-catalog-mobile-controls-parity-script-010236">
		(() => {
			'use strict';
			const labels={menu_order:'Recomendados',popularity:'Más populares',rating:'Mejor valorados',date:'Más recientes',price:'Menor precio','price-desc':'Mayor precio'};
			const setImportant=(node,name,value)=>node?.style?.setProperty(name,value,'important');
			const remove=(node,...names)=>names.forEach(name=>node?.style?.removeProperty(name));

			const normaliseOrdering=(compact,orderWidth)=>{
				document.querySelectorAll('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering').forEach(form=>{
					setImportant(form,'box-sizing','border-box'); setImportant(form,'position','static'); setImportant(form,'inset','auto');
					setImportant(form,'float','none'); setImportant(form,'clear','none'); setImportant(form,'margin','0'); setImportant(form,'padding','0'); setImportant(form,'transform','none');
					setImportant(form,'width',orderWidth); setImportant(form,'min-width',orderWidth); setImportant(form,'max-width',orderWidth);
					if(compact){setImportant(form,'display','flex');setImportant(form,'flex',`0 0 ${orderWidth}`);setImportant(form,'height','44px');setImportant(form,'min-height','44px');setImportant(form,'max-height','44px');setImportant(form,'align-items','center');setImportant(form,'justify-content','flex-end');}
					else remove(form,'flex','height','min-height','max-height','align-items','justify-content');
				});

				document.querySelectorAll('.emo-catalog-toolbar-shared-010229 .woocommerce-ordering select').forEach(select=>{
					select.classList.add('emo-order-select-parity-010236');
					setImportant(select,'box-sizing','border-box'); setImportant(select,'display','block'); setImportant(select,'width',orderWidth); setImportant(select,'min-width',orderWidth); setImportant(select,'max-width',orderWidth);
					[...select.options].forEach(option=>{
						if(labels[option.value]) option.textContent=labels[option.value];
						else option.textContent=(option.textContent||'').replace(/^Ordenar\s+por\s+/i,'').trim();
					});
				});
			};

			const normaliseMobileToolbar=(toolbar,vendor)=>{
				if(!toolbar) return;
				[['position','relative'],['z-index','2'],['display','flex'],['box-sizing','border-box'],['height','68px'],['min-height','68px'],['max-height','68px'],['align-items','center'],['justify-content','flex-start'],['gap','10px'],['clear','both'],['float','none'],['margin','0 0 10px'],['padding','10px 12px'],['border','1px solid rgba(23,63,50,.12)'],['border-radius','16px'],['background','#fff'],['box-shadow','0 8px 24px rgba(17,42,34,.055)'],['font-size','15px'],['font-weight','430'],['line-height','1.65']].forEach(([n,v])=>setImportant(toolbar,n,v));
				if(vendor){setImportant(toolbar,'left','50%');setImportant(toolbar,'width','calc(100vw - 32px)');setImportant(toolbar,'min-width','calc(100vw - 32px)');setImportant(toolbar,'max-width','calc(100vw - 32px)');setImportant(toolbar,'transform','translateX(-50%)');}
				else {setImportant(toolbar,'left','auto');setImportant(toolbar,'width','100%');setImportant(toolbar,'min-width','0');setImportant(toolbar,'max-width','100%');setImportant(toolbar,'transform','none');}

				const count=toolbar.querySelector('.woocommerce-result-count,.emo-catalog-result-count-010220,.emo-vendor-result-count-010225');
				if(count){
					[['position','static'],['inset','auto'],['display','flex'],['flex','1 1 0'],['box-sizing','border-box'],['width','auto'],['min-width','0'],['max-width','none'],['height','44px'],['min-height','44px'],['max-height','44px'],['align-items','center'],['align-self','center'],['overflow','hidden'],['float','none'],['clear','none'],['margin','0'],['padding','0'],['color','#42564e'],['font-size','9.8px'],['font-weight','700'],['line-height','1.35'],['white-space','nowrap'],['word-break','normal'],['overflow-wrap','normal'],['writing-mode','horizontal-tb'],['text-orientation','mixed']].forEach(([n,v])=>setImportant(count,n,v));
					count.querySelectorAll('*').forEach(node=>{setImportant(node,'white-space','nowrap');setImportant(node,'word-break','normal');setImportant(node,'overflow-wrap','normal');setImportant(node,'writing-mode','horizontal-tb');});
				}
			};

			const normaliseToggle=(toggle,vendor)=>{
				if(!toggle) return;
				toggle.classList.add('emo-mobile-filter-control-parity-010236');
				[['position','relative'],['z-index','2'],['display','flex'],['box-sizing','border-box'],['height','48px'],['min-height','48px'],['max-height','48px'],['align-items','center'],['justify-content','flex-start'],['gap','10px'],['clear','both'],['float','none'],['margin','0 0 18px'],['padding','0 16px'],['border','1px solid rgba(23,63,50,.16)'],['border-radius','14px'],['background','rgba(255,253,248,.92)'],['box-shadow','0 7px 20px rgba(23,63,50,.045)'],['color','#173f32'],['font-size','13px'],['font-weight','820'],['line-height','1.2'],['text-align','left']].forEach(([n,v])=>setImportant(toggle,n,v));
				if(vendor){setImportant(toggle,'left','50%');setImportant(toggle,'width','calc(100vw - 32px)');setImportant(toggle,'min-width','calc(100vw - 32px)');setImportant(toggle,'max-width','calc(100vw - 32px)');setImportant(toggle,'transform','translateX(-50%)');}
				else {setImportant(toggle,'left','auto');setImportant(toggle,'width','100%');setImportant(toggle,'min-width','0');setImportant(toggle,'max-width','100%');setImportant(toggle,'transform','none');}
			};

			const normalise=()=>{
				const viewport=window.innerWidth;
				const compact=viewport<=991;
				const orderWidth=viewport<=360?'138px':(compact?'148px':'250px');
				normaliseOrdering(compact,orderWidth);

				const shopToolbar=document.querySelector('body:not(.wcfmmp-store-page) .emo-catalog-toolbar-shared-010229');
				const vendorToolbar=document.querySelector('#wcfmmp-store .emo-catalog-toolbar-shared-010229');
				const shopToggle=document.querySelector('#emo-premium-filter-toggle');
				const vendorToggle=document.querySelector('#wcfmmp-store .emo-mobile-filter-toggle.emo-filter-toggle-shared-010229');

				if(vendorToggle){
					const vendorLabel=vendorToggle.querySelector('.emo-filter-label');
					if(vendorLabel) vendorLabel.textContent='Filtros';
					const vendorShell=document.querySelector('.emo-mobile-filter-shell.emo-filter-shell-shared-010229');
					const vendorTitle=vendorShell?.querySelector('.emo-mobile-filter-title');
					if(vendorTitle) vendorTitle.textContent='Filtros';
				}

				if(compact){
					normaliseMobileToolbar(shopToolbar,false);
					normaliseMobileToolbar(vendorToolbar,true);
					normaliseToggle(shopToggle,false);
					normaliseToggle(vendorToggle,true);

					const store=document.querySelector('#wcfmmp-store');
					if(store){
						store.querySelectorAll('.body_area,.right_side,.right_side_full,.products-wrapper,.wcfmmp-store-product,.product_area').forEach(node=>{
							setImportant(node,'box-sizing','border-box');setImportant(node,'width','100%');setImportant(node,'min-width','0');setImportant(node,'max-width','100%');setImportant(node,'float','none');setImportant(node,'clear','both');setImportant(node,'margin-left','0');setImportant(node,'margin-right','0');setImportant(node,'transform','none');
						});
						const products=store.querySelector('ul.products');
						products?.style.removeProperty('transform'); products?.style.removeProperty('margin-bottom');
					}
				}else{
					[shopToolbar,vendorToolbar].forEach(toolbar=>remove(toolbar,'left','display','height','min-height','max-height','align-items','justify-content','gap','margin','padding','border','border-radius','background','box-shadow','font-size','font-weight','line-height','width','min-width','max-width','transform'));
					[shopToggle,vendorToggle].forEach(toggle=>remove(toggle,'left','width','min-width','max-width','height','min-height','max-height','transform'));
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
