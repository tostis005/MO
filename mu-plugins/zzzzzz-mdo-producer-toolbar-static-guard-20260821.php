<?php
/**
 * Plugin Name: MDO Producer Toolbar Static Guard
 * Description: Prevents legacy producer scripts/styles from rewriting the shared catalogue toolbar after mount.
 * Version: 1.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Detect a public WCFM producer store without depending on one helper. */
function mdo_producer_toolbar_static_guard_is_store_20260821(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'mdo_ps_toolbar_ux_is_store_20260821' ) && mdo_ps_toolbar_ux_is_store_20260821() ) {
		return true;
	}
	if ( function_exists( 'mdo_ps_safe_is_store_20260821' ) && mdo_ps_safe_is_store_20260821() ) {
		return true;
	}
	if ( function_exists( 'wcfm_is_store_page' ) && wcfm_is_store_page() ) {
		return true;
	}
	if ( function_exists( 'wcfmmp_is_store_page' ) && wcfmmp_is_store_page() ) {
		return true;
	}
	return (bool) get_query_var( 'store' );
}

/**
 * Remove only the two historical footer closures that continuously write inline
 * toolbar geometry. The route is fully resolved here, while the legacy callbacks
 * have not run yet because both are registered at PHP_INT_MAX.
 */
function mdo_producer_toolbar_static_guard_remove_legacy_footer_20260821(): void {
	if ( ! mdo_producer_toolbar_static_guard_is_store_20260821() ) {
		return;
	}

	global $wp_filter;
	if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}

	$targets = array(
		wp_normalize_path( get_stylesheet_directory() . '/inc/catalog-mobile-controls-parity-010236.php' ),
		wp_normalize_path( get_stylesheet_directory() . '/inc/vendor-toolbar-mobile-final.php' ),
	);

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
			if ( is_string( $filename ) && in_array( wp_normalize_path( $filename ), $targets, true ) ) {
				remove_action( 'wp_footer', $callback, (int) $priority );
			}
		}
	}
}
add_action( 'wp_footer', 'mdo_producer_toolbar_static_guard_remove_legacy_footer_20260821', -999999 );

/** Final static geometry after the old child-theme head CSS. */
function mdo_producer_toolbar_static_guard_css_20260821(): void {
	if ( ! mdo_producer_toolbar_static_guard_is_store_20260821() ) {
		return;
	}
	?>
	<style id="mdo-producer-toolbar-static-guard-20260821">
		@media (max-width:640px) {
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host {
				position:relative !important;
				left:auto !important;
				display:grid !important;
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:64px 40px !important;
				align-items:stretch !important;
				justify-items:stretch !important;
				gap:10px !important;
				box-sizing:border-box !important;
				width:calc(100% + 34px) !important;
				min-width:calc(100% + 34px) !important;
				max-width:calc(100% + 34px) !important;
				height:140px !important;
				min-height:140px !important;
				max-height:140px !important;
				margin:0 -17px 12px !important;
				padding:12px !important;
				overflow:visible !important;
				transform:none !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left {
				position:static !important;
				display:grid !important;
				grid-column:1 !important;
				grid-row:1 !important;
				grid-template-columns:minmax(0,1fr) !important;
				grid-template-rows:17px 40px !important;
				align-items:stretch !important;
				gap:7px !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:64px !important;
				min-height:64px !important;
				max-height:64px !important;
				margin:0 !important;
				padding:0 !important;
				overflow:visible !important;
				float:none !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .woocommerce-result-count {
				position:static !important;
				display:flex !important;
				grid-column:1 !important;
				grid-row:1 !important;
				flex:none !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:17px !important;
				min-height:17px !important;
				max-height:17px !important;
				align-items:center !important;
				margin:0 !important;
				padding:0 2px !important;
				overflow:hidden !important;
				font-size:11px !important;
				line-height:17px !important;
				white-space:nowrap !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .mdo-catalog-destination--canonical {
				position:static !important;
				display:block !important;
				grid-column:1 !important;
				grid-row:2 !important;
				flex:none !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
				padding:0 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woostify-toolbar-left > .mdo-catalog-destination--canonical > .mdo-catalog-destination__trigger {
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woocommerce-ordering {
				position:static !important;
				display:flex !important;
				grid-column:1 !important;
				grid-row:2 !important;
				flex:none !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:0 !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				align-items:center !important;
				margin:0 !important;
				padding:0 !important;
				transform:none !important;
				float:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized.mdo-ps-toolbar-host > .woocommerce-ordering > select {
				display:block !important;
				box-sizing:border-box !important;
				width:100% !important;
				min-width:100% !important;
				max-width:100% !important;
				height:40px !important;
				min-height:40px !important;
				max-height:40px !important;
				margin:0 !important;
			}

			html body.elmercado-child-theme.wcfmmp-store-page.mdo-producer-store-toolbar-ux #wcfmmp-store#wcfmmp-store .woostify-sorting.elmercado-vendor-sorting-normalized > .elmercado-vendor-toolbar {
				display:contents !important;
				margin:0 !important;
				padding:0 !important;
				transform:none !important;
			}
		}
	</style>
	<?php
}
add_action( 'wp_head', 'mdo_producer_toolbar_static_guard_css_20260821', PHP_INT_MAX );

/**
 * Keep the DOM contract stable if WCFM or another legacy module re-wraps the
 * count/order controls after the UX mount. Only child placement and stale inline
 * geometry are normalised; product queries and filter state are untouched.
 */
function mdo_producer_toolbar_static_guard_structure_script_20260821(): void {
	if ( ! mdo_producer_toolbar_static_guard_is_store_20260821() ) {
		return;
	}
	?>
	<script id="mdo-producer-toolbar-static-guard-js-20260821">
	(() => {
		'use strict';
		const store=document.querySelector('#wcfmmp-store');
		if(!store) return;
		let frame=0;
		let applying=false;
		const clear=(node,names)=>{if(!node)return;names.forEach(name=>node.style.removeProperty(name));};
		const geometry=['left','right','top','bottom','inset','display','width','min-width','max-width','height','min-height','max-height','flex','flex-basis','grid-column','grid-row','transform','margin','margin-left','margin-right','padding','float','clear','align-self','justify-self'];

		const normalise=()=>{
			if(applying) return;
			applying=true;
			try{
				const host=store.querySelector('.woostify-sorting.elmercado-vendor-sorting-normalized')||store.querySelector('.woostify-sorting');
				if(!host) return;
				host.classList.add('emo-catalog-toolbar-shared-010229','elmercado-vendor-sorting-normalized','mdo-ps-toolbar-host','mdo-ps-toolbar-ready');

				let left=Array.from(host.children).find(node=>node.classList?.contains('woostify-toolbar-left'))||null;
				if(!left){left=document.createElement('div');left.className='woostify-toolbar-left';host.prepend(left);}
				left.classList.remove('elmercado-vendor-filter-hidden');

				const result=host.querySelector('.woocommerce-result-count')||store.querySelector('.elmercado-vendor-toolbar .woocommerce-result-count');
				const destination=host.querySelector('[data-mdo-ps-destination-trigger-wrap]')||store.querySelector('[data-mdo-ps-destination-trigger-wrap]');
				const ordering=host.querySelector('.woocommerce-ordering')||store.querySelector('.elmercado-vendor-toolbar .woocommerce-ordering');
				if(result&&result.parentElement!==left) left.appendChild(result);
				if(destination&&destination.parentElement!==left) left.appendChild(destination);
				if(ordering&&ordering.parentElement!==host) host.appendChild(ordering);

				clear(host,geometry); clear(left,geometry); clear(result,geometry); clear(destination,geometry); clear(ordering,geometry);
				clear(ordering?.querySelector('select'),geometry);

				host.querySelectorAll(':scope > .elmercado-vendor-toolbar').forEach(inner=>{
					const meaningful=Array.from(inner.children).filter(node=>getComputedStyle(node).display!=='none');
					if(!meaningful.length) inner.remove(); else inner.classList.add('mdo-ps-toolbar-unused-inner');
				});
			}finally{applying=false;}
		};
		const schedule=()=>{if(frame)return;frame=requestAnimationFrame(()=>{frame=0;normalise();});};
		normalise();
		requestAnimationFrame(normalise);
		setTimeout(normalise,250);
		setTimeout(normalise,900);
		setTimeout(normalise,1800);
		setTimeout(normalise,3000);
		window.addEventListener('pageshow',normalise,{passive:true});
		window.addEventListener('resize',schedule,{passive:true});
		new MutationObserver(schedule).observe(store,{childList:true,subtree:true});
	})();
	</script>
	<?php
}
add_action(
	'wp',
	static function (): void {
		if ( mdo_producer_toolbar_static_guard_is_store_20260821() ) {
			add_action( 'wp_footer', 'mdo_producer_toolbar_static_guard_structure_script_20260821', PHP_INT_MAX );
		}
	},
	PHP_INT_MAX
);
