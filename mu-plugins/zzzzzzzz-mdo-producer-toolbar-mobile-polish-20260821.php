<?php
/**
 * Plugin Name: MDO Producer Toolbar Mobile Polish
 * Description: Mobile-only polish for producer shipping/ordering controls: no clipped label, no double ordering border, and reliable native select interaction.
 * Version: 1.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function mdo_producer_toolbar_mobile_polish_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_producer_toolbar_static_guard_is_store_20260821' ) ) {
		return mdo_producer_toolbar_static_guard_is_store_20260821();
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) ) {
		return mdo_ps_toolbar_ux_is_store_20260821();
	}
	return function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page();
}

function mdo_producer_toolbar_mobile_polish_css_20260821(): void {
	if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
		return;
	}
	?>
	<style class="mdo-producer-toolbar-mobile-polish-20260821">
		@media (max-width:640px) {
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized {
				position:relative !important;
				isolation:isolate !important;
				overflow:visible !important;
			}

			/* Destination: one line, centred, with no clipping. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination--canonical,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination {
				position:relative !important;
				z-index:1 !important;
				overflow:visible !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger {
				position:relative !important;
				z-index:1 !important;
				display:grid !important;
				grid-template-columns:minmax(0,1fr) 16px !important;
				align-items:center !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				padding:0 13px !important;
				overflow:visible !important;
				line-height:1.25 !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:first-child,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:first-child {
				display:none !important;
				visibility:hidden !important;
				width:0 !important;
				height:0 !important;
				margin:0 !important;
				padding:0 !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span {
				position:static !important;
				inset:auto !important;
				z-index:auto !important;
				display:flex !important;
				flex-flow:row nowrap !important;
				gap:3px !important;
				min-width:0 !important;
				height:100% !important;
				align-items:center !important;
				overflow:visible !important;
				white-space:nowrap !important;
				text-overflow:clip !important;
				line-height:1.25 !important;
				padding:1px 0 0 !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > span > strong,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > span > strong {
				display:inline !important;
				position:static !important;
				inset:auto !important;
				flex:0 0 auto !important;
				margin:0 !important;
				padding:0 !important;
				line-height:inherit !important;
				white-space:nowrap !important;
				pointer-events:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-catalog-destination__trigger > svg:last-child,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .mdo-ps-destination__trigger > svg:last-child {
				pointer-events:none !important;
			}

			/* Ordering: the form has no visual shell; only the select has the rounded border. */
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering {
				position:relative !important;
				z-index:50 !important;
				display:flex !important;
				box-sizing:border-box !important;
				border:0 !important;
				border-radius:0 !important;
				outline:0 !important;
				background:transparent !important;
				box-shadow:none !important;
				overflow:visible !important;
				pointer-events:auto !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering::before,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering::after,
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering span {
				pointer-events:none !important;
				box-shadow:none !important;
			}
			html body.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized .woocommerce-ordering select {
				position:relative !important;
				z-index:51 !important;
				display:block !important;
				visibility:visible !important;
				opacity:1 !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				border:1px solid rgba(23,63,50,.14) !important;
				border-radius:999px !important;
				outline:0 !important;
				box-shadow:none !important;
				pointer-events:auto !important;
				cursor:pointer !important;
				touch-action:manipulation !important;
			}
		}
	</style>
	<?php
}

/* First-paint rules. */
add_action( 'wp_head', 'mdo_producer_toolbar_mobile_polish_css_20260821', PHP_INT_MAX );
/* Repeat after theme/footer polish rules so no later legacy selector can restore the block strong/double shell. */
add_action( 'wp_footer', 'mdo_producer_toolbar_mobile_polish_css_20260821', PHP_INT_MAX );

add_action(
	'wp_footer',
	static function (): void {
		if ( ! mdo_producer_toolbar_mobile_polish_is_store_20260821() ) {
			return;
		}
		?>
		<script id="mdo-producer-toolbar-mobile-polish-js-20260821">
		(() => {
			if (!matchMedia('(max-width:640px)').matches) return;
			const apply=()=>{
				const store=document.querySelector('#wcfmmp-store'); if(!store) return;
				const trigger=store.querySelector('.mdo-catalog-destination__trigger');
				const label=trigger?.querySelector('span'); const strong=label?.querySelector('strong');
				const form=store.querySelector('.woocommerce-ordering'); const select=form?.querySelector('select');
				if(label){label.style.setProperty('display','flex','important');label.style.setProperty('flex-flow','row nowrap','important');label.style.setProperty('overflow','visible','important');label.style.setProperty('pointer-events','none','important');}
				if(strong){strong.style.setProperty('display','inline','important');strong.style.setProperty('position','static','important');strong.style.setProperty('line-height','inherit','important');}
				if(form){form.style.setProperty('border','0','important');form.style.setProperty('background','transparent','important');form.style.setProperty('box-shadow','none','important');form.style.setProperty('pointer-events','auto','important');form.style.setProperty('z-index','50','important');}
				if(select){select.style.setProperty('pointer-events','auto','important');select.style.setProperty('z-index','51','important');}
			};
			apply(); requestAnimationFrame(apply); setTimeout(apply,300); setTimeout(apply,1200);
			window.addEventListener('pageshow',apply,{passive:true});
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
