<?php
/**
 * Cierre final del catálogo: contador estable, paridad visual de filtros y
 * carga continua por lotes exactos, sin depender de las páginas HTML del tema.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function elmercado_catalog_filter_scroll_target_010234(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_core_filters_is_catalog' ) && elmercado_core_filters_is_catalog() ) {
		return true;
	}
	return function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225();
}

function elmercado_catalog_filter_scroll_is_vendor_010234(): bool {
	$classes = function_exists( 'get_body_class' ) ? get_body_class() : array();
	return in_array( 'wcfmmp-store-page', $classes, true )
		|| ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() );
}

function elmercado_catalog_filter_scroll_total_010234(): int {
	if ( elmercado_catalog_filter_scroll_is_vendor_010234() && function_exists( 'elmercado_vendor_store_state_010225' ) ) {
		$state = elmercado_vendor_store_state_010225();
		return max( 0, (int) ( $state['total'] ?? 0 ) );
	}
	if ( function_exists( 'elmercado_catalog_exact_result_total_010220' ) ) {
		return max( 0, (int) elmercado_catalog_exact_result_total_010220() );
	}
	return 0;
}

/**
 * IDs exactos del contexto actual en el orden del catálogo.
 *
 * @return int[]
 */
function elmercado_catalog_filter_scroll_ordered_ids_010234(): array {
	static $cache = array();
	$key = ( elmercado_catalog_filter_scroll_is_vendor_010234() ? 'vendor:' : 'shop:' ) . (string) ( $_SERVER['REQUEST_URI'] ?? '' );
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	if ( elmercado_catalog_filter_scroll_is_vendor_010234() && function_exists( 'elmercado_vendor_store_state_010225' ) ) {
		$state   = elmercado_vendor_store_state_010225();
		$allowed = array_values( array_unique( array_filter( array_map( 'absint', (array) ( $state['filtered_ids'] ?? array() ) ) ) ) );
		if ( ! $allowed ) {
			$cache[ $key ] = array();
			return array();
		}

		$ordering = function_exists( 'woocommerce_get_catalog_ordering_args' )
			? woocommerce_get_catalog_ordering_args()
			: array( 'orderby' => 'date', 'order' => 'DESC' );
		$args = array(
			'post_type'              => 'product',
			'post_status'            => 'publish',
			'fields'                 => 'ids',
			'posts_per_page'         => -1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'post__in'               => $allowed,
			'orderby'                => $ordering['orderby'] ?? 'date',
			'order'                  => $ordering['order'] ?? 'DESC',
			'meta_key'               => $ordering['meta_key'] ?? '', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'cache_results'          => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		$query = new WP_Query( $args );
		$ids   = array_values( array_unique( array_filter( array_map( 'absint', (array) $query->posts ) ) ) );
		/* En caso de una extensión de ordenación incompatible, nunca perdemos IDs. */
		$ids = array_merge( $ids, array_values( array_diff( $allowed, $ids ) ) );
		$cache[ $key ] = array_values( array_unique( $ids ) );
		return $cache[ $key ];
	}

	global $wp_query;
	$vars = $wp_query instanceof WP_Query ? (array) $wp_query->query_vars : array();
	$vars['post_type']              = 'product';
	$vars['post_status']            = 'publish';
	$vars['fields']                 = 'ids';
	$vars['posts_per_page']         = -1;
	$vars['nopaging']               = true;
	$vars['paged']                  = 1;
	$vars['page']                   = 1;
	$vars['offset']                 = 0;
	$vars['no_found_rows']          = true;
	$vars['ignore_sticky_posts']    = true;
	$vars['suppress_filters']       = false;
	$vars['cache_results']          = false;
	$vars['update_post_meta_cache'] = false;
	$vars['update_post_term_cache'] = false;
	unset( $vars['product-page'], $vars['product_page'] );

	$query = new WP_Query( $vars );
	$ids   = array_values( array_unique( array_filter( array_map( 'absint', (array) $query->posts ) ) ) );
	$total = elmercado_catalog_filter_scroll_total_010234();
	if ( $total > 0 && count( $ids ) > $total ) {
		$ids = array_slice( $ids, 0, $total );
	}
	$cache[ $key ] = $ids;
	return $ids;
}

/** Retira únicamente el loader footer histórico; conserva su CSS/history guard. */
function elmercado_catalog_filter_scroll_remove_legacy_loader_010234(): void {
	global $wp_filter;
	if ( empty( $wp_filter['wp_footer'] ) || ! $wp_filter['wp_footer'] instanceof WP_Hook ) {
		return;
	}
	$legacy_file = wp_normalize_path( ELMERCADO_THEME_PATH . '/inc/catalog-continuous-loading-010176.php' );
	foreach ( $wp_filter['wp_footer']->callbacks as $priority => $items ) {
		foreach ( $items as $item ) {
			$callback = $item['function'] ?? null;
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
elmercado_catalog_filter_scroll_remove_legacy_loader_010234();

/**
 * Render AJAX de un lote explícito de productos publicados.
 */
function elmercado_catalog_filter_scroll_batch_010234(): void {
	check_ajax_referer( 'elmercado_catalog_batch_010234', 'nonce' );
	$raw = isset( $_POST['ids'] ) ? (array) wp_unslash( $_POST['ids'] ) : array();
	$ids = array_slice( array_values( array_unique( array_filter( array_map( 'absint', $raw ) ) ) ), 0, 24 );
	if ( ! $ids ) {
		wp_send_json_success( array( 'html' => '', 'ids' => array() ) );
	}

	$rendered = array();
	ob_start();
	foreach ( $ids as $product_id ) {
		$post = get_post( $product_id );
		if ( ! $post instanceof WP_Post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
			continue;
		}
		$product = wc_get_product( $product_id );
		if ( ! $product || ! $product->is_visible() || ! $product->is_in_stock() ) {
			continue;
		}
		$GLOBALS['post'] = $post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		setup_postdata( $post );
		wc_get_template_part( 'content', 'product' );
		$rendered[] = $product_id;
	}
	wp_reset_postdata();
	$html = (string) ob_get_clean();
	wp_send_json_success( array( 'html' => $html, 'ids' => $rendered ) );
}
add_action( 'wp_ajax_elmercado_catalog_batch_010234', 'elmercado_catalog_filter_scroll_batch_010234' );
add_action( 'wp_ajax_nopriv_elmercado_catalog_batch_010234', 'elmercado_catalog_filter_scroll_batch_010234' );

/** Paridad visual real: mismo padding, misma tipografía y sin subrayado. */
add_action(
	'wp_head',
	static function (): void {
		if ( ! elmercado_catalog_filter_scroll_target_010234() ) {
			return;
		}
		?>
		<style id="elmercado-catalog-filter-parity-final-010234">
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 {
				font-family:Aptos,-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-filter-rail-shared-010229 #emo-vendor-filters {
				display:block !important; box-sizing:border-box !important; width:100% !important; max-width:none !important;
				margin:0 !important; padding:0 !important; border:0 !important; border-radius:0 !important;
				background:transparent !important; box-shadow:none !important; font-family:inherit !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-title-shared-010229,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-filter-rail-shared-010229 #emo-vendor-filters .widget-title {
				font-family:inherit !important; font-size:10.5px !important; font-weight:800 !important; line-height:1.25 !important;
				letter-spacing:.085em !important; text-transform:uppercase !important;
			}
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:hover,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229:focus-visible,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:hover > .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-row-shared-010229:is(.current-cat,.is-active,.chosen,.woocommerce-widget-layered-nav-list__item--chosen) > .emo-filter-link-shared-010229,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-filter-link-shared-010229 > span,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove:hover > span:last-child,
			html body.elmercado-child-theme :is(#secondary#secondary,#wcfmmp-store#wcfmmp-store .left_sidebar).emo-filter-rail-shared-010229 .emo-category-context__remove:focus-visible > span:last-child {
				text-decoration:none !important; text-decoration-line:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Loader por IDs exactos: no usa /page/2, por lo que no puede repetir la
 * última página aunque WCFM/WooCommerce publiquen enlaces de paginación erróneos.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( ! elmercado_catalog_filter_scroll_target_010234() ) {
			return;
		}
		$total       = elmercado_catalog_filter_scroll_total_010234();
		$ordered_ids = elmercado_catalog_filter_scroll_ordered_ids_010234();
		$label       = sprintf( esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ), number_format_i18n( $total ) );
		?>
		<script id="elmercado-catalog-scroll-final-010234">
		(() => {
			'use strict';
			const exactTotal = <?php echo wp_json_encode( $total ); ?>;
			const exactLabel = <?php echo wp_json_encode( $label ); ?>;
			const orderedIds = <?php echo wp_json_encode( array_values( $ordered_ids ) ); ?>.map(String);
			const endpoint = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
			const nonce = <?php echo wp_json_encode( wp_create_nonce( 'elmercado_catalog_batch_010234' ) ); ?>;
			const gridSelector = '#wcfmmp-store ul.products,main ul.products,#primary ul.products,.content-area ul.products,ul.products';
			const grid = document.querySelector(gridSelector);
			if (!grid) return;
			const isVendor = !!grid.closest('#wcfmmp-store');
			const surface = isVendor ? grid.closest('#wcfmmp-store') : (grid.closest('main,#primary,.content-area') || document);

			const pagerScopes=['.woocommerce-pagination','.woostify-pagination','.wcfm-pagination','.wcfmmp-pagination','.wcfm_pagination','.wcfmmp-store-product-pagination','.navigation.pagination','.products-pagination','.product-pagination','.infinite-scroll-pagination','.woostify-load-more','.woocommerce-load-more'];
			document.querySelectorAll(pagerScopes.join(',')).forEach(node=>node.classList.add('emo-catalog-native-pagination'));

			const idFromItem=(item)=>{
				const postClass=[...item.classList].find(name=>/^post-\d+$/.test(name));
				if(postClass) return postClass.replace('post-','');
				const own=item.getAttribute('data-product_id')||item.getAttribute('data-product-id');
				if(own) return String(own);
				const nested=item.querySelector('[data-product_id],[data-product-id]');
				return String(nested?.getAttribute('data-product_id')||nested?.getAttribute('data-product-id')||'');
			};
			const productItems=()=>[...grid.querySelectorAll(':scope > li.product')];
			const known=new Set(productItems().map(idFromItem).filter(Boolean));
			let remaining=orderedIds.filter(id=>!known.has(id));
			let loading=false;
			let failures=0;

			const state=document.createElement('div');
			state.className='emo-catalog-load-state';
			state.setAttribute('role','status'); state.setAttribute('aria-live','polite');
			state.innerHTML='<span class="emo-catalog-spinner" aria-hidden="true"></span><span class="emo-catalog-load-message"></span><button type="button" class="emo-catalog-load-button" hidden>Cargar más productos</button>';
			grid.insertAdjacentElement('afterend',state);
			const message=state.querySelector('.emo-catalog-load-message');
			const button=state.querySelector('.emo-catalog-load-button');

			const exactCountNodes=()=>[...surface.querySelectorAll('.woocommerce-result-count')];
			let syncing=false;
			const lockCounts=()=>{
				if(syncing) return; syncing=true;
				try{exactCountNodes().forEach(node=>{if((node.textContent||'').replace(/\s+/g,' ').trim()!==exactLabel) node.textContent=exactLabel;});}
				finally{syncing=false;}
			};
			lockCounts();
			exactCountNodes().forEach(node=>new MutationObserver(lockCounts).observe(node,{childList:true,characterData:true,subtree:true}));

			const setState=(mode,text='')=>{
				state.classList.toggle('is-loading',mode==='loading');
				state.classList.toggle('is-failure',mode==='failure');
				message.textContent=(mode==='loading'||mode==='failure')?text:'';
				button.hidden=mode!=='failure'||remaining.length===0;
			};
			const finish=()=>{remaining=[];setState('finished');};

			const debug={};
			Object.defineProperties(debug,{
				shown:{enumerable:true,get:()=>productItems().length},unique:{enumerable:true,get:()=>known.size},loading:{enumerable:true,get:()=>loading},
				remaining:{enumerable:true,get:()=>remaining.length},nextUrl:{enumerable:true,get:()=>remaining.length?'batch':''},highestPage:{enumerable:true,get:()=>Math.max(1,Math.ceil(productItems().length/15))},ordered:{enumerable:true,get:()=>orderedIds.length}
			});
			window.__emoCatalogLoaderState=debug;

			const loadNext=async()=>{
				if(loading||!remaining.length) return;
				if(exactTotal&&productItems().length>=exactTotal){finish();return;}
				loading=true; setState('loading','Cargando más productos…');
				const batch=remaining.slice(0,12);
				const body=new URLSearchParams({action:'elmercado_catalog_batch_010234',nonce});
				batch.forEach(id=>body.append('ids[]',id));
				try{
					const controller=new AbortController(); const timer=setTimeout(()=>controller.abort(),10000);
					let response;
					try{response=await fetch(endpoint,{method:'POST',credentials:'same-origin',headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},body:body.toString(),signal:controller.signal});}
					finally{clearTimeout(timer);}
					if(!response.ok) throw new Error(`HTTP ${response.status}`);
					const payload=await response.json();
					if(!payload?.success) throw new Error('Invalid batch response');
					const holder=document.createElement('ul'); holder.innerHTML=payload.data?.html||'';
					let appended=0;
					for(const item of [...holder.querySelectorAll(':scope > li.product')]){
						const id=idFromItem(item); if(id&&known.has(id)) continue;
						if(id) known.add(id); grid.append(item); appended++;
					}
					const rendered=new Set((payload.data?.ids||[]).map(String));
					remaining=remaining.filter(id=>!batch.includes(id)||!rendered.has(id));
					/* IDs no renderizables no deben bloquear el final. */
					if(!appended) remaining=remaining.filter(id=>!batch.includes(id));
					lockCounts(); failures=0; loading=false;
					document.body.dispatchEvent(new CustomEvent('emo:catalog-products-appended',{detail:{count:appended,remaining:remaining.length}}));
					if(!remaining.length||(exactTotal&&productItems().length>=exactTotal)){finish();return;}
					setState('idle');
					if(state.getBoundingClientRect().top<=window.innerHeight+2200) setTimeout(loadNext,80);
				}catch(_){
					loading=false; failures++;
					if(failures<2){setState('loading','Cargando más productos…');setTimeout(loadNext,900);}
					else setState('failure','No se ha podido continuar la carga automática.');
				}
			};
			button.addEventListener('click',()=>{failures=0;setState('idle');loadNext();});
			if(!remaining.length){finish();return;}
			if(!('IntersectionObserver' in window)){setState('failure','Pulsa para cargar más productos.');button.hidden=false;return;}
			setState('idle');
			const observer=new IntersectionObserver(entries=>{if(entries.some(entry=>entry.isIntersecting))loadNext();},{rootMargin:'2400px 0px 2400px 0px',threshold:.01});
			observer.observe(state);
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
