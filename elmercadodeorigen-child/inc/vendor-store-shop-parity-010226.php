<?php
/**
 * Paridad visual entre Tienda y las tiendas de productor + categorías visibles.
 *
 * - El rail del productor replica el acabado final de Tienda.
 * - La categoría activa queda por encima del precio, como en los archivos.
 * - Se elimina el texto editorial redundante bajo Productos.
 * - Home y el filtro de categorías de Tienda se alimentan de la misma verdad
 *   de visibilidad: publicados, catalogables, con stock y vendedor disponible.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Categorías raíz con productos realmente visibles para el usuario actual.
 *
 * @return array<int,array{term:WP_Term,count:int}>
 */
function elmercado_visible_root_categories_010226(): array {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return array();
	}

	$counts = function_exists( 'elmercado_catalog_visible_category_counts_010217' )
		? elmercado_catalog_visible_category_counts_010217()
		: array();
	$exclude = array_filter( array( (int) get_option( 'default_product_cat' ) ) );
	$terms = get_terms(
		array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => false,
			'parent'     => 0,
			'exclude'    => $exclude,
		)
	);

	if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
		return array();
	}

	$visible = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term ) {
			continue;
		}
		$count = function_exists( 'elmercado_catalog_visible_category_count_010217' )
			? elmercado_catalog_visible_category_count_010217( (int) $term->term_id )
			: max( 0, (int) ( $counts[ (int) $term->term_id ] ?? $term->count ) );
		if ( $count <= 0 ) {
			continue;
		}
		$visible[] = array(
			'term'  => $term,
			'count' => $count,
		);
	}

	usort(
		$visible,
		static function ( array $left, array $right ): int {
			$by_count = (int) $right['count'] <=> (int) $left['count'];
			return 0 !== $by_count ? $by_count : strnatcasecmp( (string) $left['term']->name, (string) $right['term']->name );
		}
	);

	return $visible;
}

/**
 * HTML de categorías de Home sin límite artificial de seis tarjetas.
 */
function elmercado_home_categories_visible_html_010226(): string {
	$categories = elmercado_visible_root_categories_010226();
	if ( ! $categories ) {
		return '';
	}

	$html  = '<section class="emo-section emo-categories"><div class="emo-shell">';
	$html .= '<div class="emo-section-heading"><div><span class="emo-kicker">' . esc_html__( 'Explora por categoría', 'elmercadodeorigen' ) . '</span><h2>' . esc_html__( 'Encuentra tu próximo descubrimiento', 'elmercadodeorigen' ) . '</h2></div><p>' . esc_html__( 'Una despensa diversa, seleccionada para comprar mejor y conocer quién hay detrás de cada producto.', 'elmercadodeorigen' ) . '</p></div>';
	$html .= '<div class="emo-category-grid">';

	foreach ( $categories as $data ) {
		$term  = $data['term'];
		$count = max( 0, (int) $data['count'] );
		$link  = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$thumbnail_id = (int) get_term_meta( (int) $term->term_id, 'thumbnail_id', true );
		$image         = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
		$style         = $image ? ' style="--emo-category-image:url(' . esc_url( $image ) . ')"' : '';
		$label         = sprintf(
			esc_html( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ) ),
			number_format_i18n( $count )
		);

		$html .= '<a class="emo-category-card" href="' . esc_url( $link ) . '"' . $style . '>';
		$html .= '<span class="emo-category-card__media" aria-hidden="true"></span>';
		$html .= '<div class="emo-category-card__content"><strong>' . esc_html( $term->name ) . '</strong><small>' . $label . '</small></div>';
		$html .= '</a>';
	}

	$html .= '</div></div></section>';
	return $html;
}

add_filter(
	'the_content',
	static function ( string $content ): string {
		if ( is_admin() || ! is_front_page() || ! in_the_loop() || ! is_main_query() || false === strpos( $content, 'emo-section emo-categories' ) ) {
			return $content;
		}
		$replacement = elmercado_home_categories_visible_html_010226();
		if ( '' === $replacement ) {
			return $content;
		}
		$updated = preg_replace( '~<section class="emo-section emo-categories"[^>]*>.*?</section>~s', $replacement, $content, 1 );
		return is_string( $updated ) ? $updated : $content;
	},
	2000
);

/**
 * Widget de categorías de Tienda construido con counts de catálogo reales.
 */
function elmercado_shop_category_widget_010226(): string {
	$categories = elmercado_visible_root_categories_010226();
	if ( ! $categories ) {
		return '';
	}

	ob_start();
	?>
	<aside class="widget woocommerce widget_product_categories emo-global-category-filter-010226" data-emo-category-truth="010226">
		<h2 class="widget-title">Categorías</h2>
		<ul class="product-categories">
			<?php foreach ( $categories as $data ) : ?>
				<?php
				$term  = $data['term'];
				$count = max( 0, (int) $data['count'] );
				$link  = get_term_link( $term );
				if ( is_wp_error( $link ) ) {
					continue;
				}
				?>
				<li class="cat-item cat-item-<?php echo esc_attr( (string) $term->term_id ); ?>">
					<a href="<?php echo esc_url( $link ); ?>"><?php echo esc_html( $term->name ); ?></a>
					<span class="count" aria-label="<?php echo esc_attr( sprintf( _n( '%s producto', '%s productos', $count, 'elmercadodeorigen' ), number_format_i18n( $count ) ) ); ?>"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	</aside>
	<?php
	return (string) ob_get_clean();
}

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'is_shop' ) || ! is_shop() || ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) ) {
			return;
		}
		$widget = elmercado_shop_category_widget_010226();
		if ( '' === $widget ) {
			return;
		}
		?>
		<template id="emo-global-category-filter-template-010226"><?php echo $widget; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></template>
		<script id="elmercado-global-category-filter-010226">
		(() => {
			'use strict';
			const template = document.getElementById('emo-global-category-filter-template-010226');
			if (!template) return;
			let frame = 0;

			const sync = () => {
				if (frame) cancelAnimationFrame(frame);
				frame = requestAnimationFrame(() => {
					frame = 0;
					const sidebars = [...new Set(document.querySelectorAll('#secondary.widget-area, .shop-widget-area, .emo-mobile-filter-content .widget-area'))];
					sidebars.forEach((sidebar) => {
						if (!(sidebar instanceof Element)) return;
						let current = sidebar.querySelector(':scope > .emo-global-category-filter-010226');
						const legacy = [...sidebar.querySelectorAll(':scope > .widget_product_categories:not(.emo-global-category-filter-010226), :scope > .widget_block:has(.wc-block-product-categories), :scope > .widget_block:has(.wp-block-woocommerce-product-categories)')];
						if (!current) {
							const fragment = template.content.cloneNode(true);
							current = fragment.firstElementChild;
							if (!current) return;
							if (legacy.length) {
								legacy[0].replaceWith(current);
								legacy.slice(1).forEach((node) => node.remove());
							} else {
								const anchor = sidebar.querySelector(':scope > #emo-global-vendor-filter') || sidebar.querySelector(':scope > .widget_price_filter');
								if (anchor) anchor.insertAdjacentElement('afterend', current);
								else sidebar.appendChild(current);
							}
						} else {
							legacy.forEach((node) => node.remove());
						}
					});
				});
			};

			sync();
			new MutationObserver(sync).observe(document.body, { childList:true, subtree:true });
			window.addEventListener('pageshow', sync, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Paridad visual del rail de productor con el rail definitivo de Tienda.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-vendor-shop-parity-010226">
			/* La categoría global nueva utiliza exactamente el patrón de Tienda. */
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > .emo-global-category-filter-010226 {
				display:block !important; visibility:visible !important; box-sizing:border-box !important; width:100% !important;
				margin:0 0 12px !important; padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important;
			}
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > .emo-global-category-filter-010226 > .widget-title,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget-title {
				display:grid !important; grid-template-columns:max-content minmax(24px,1fr) !important; align-items:center !important; column-gap:10px !important;
				width:100% !important; min-height:0 !important; margin:0 0 8px !important; padding:1px 1px 7px !important; border:0 !important;
				border-radius:0 !important; background:transparent !important; box-shadow:none !important; color:#173f32 !important;
				font-size:10.5px !important; font-weight:800 !important; letter-spacing:.085em !important; line-height:1.25 !important; text-align:left !important; text-transform:uppercase !important;
			}
			body.elmercado-child-theme.woocommerce-shop :is(#secondary.widget-area,.shop-widget-area,.emo-mobile-filter-content .widget-area) > .emo-global-category-filter-010226 > .widget-title::after,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget-title::after {
				content:"" !important; display:block !important; width:100% !important; height:1px !important; background:rgba(23,63,50,.16) !important;
			}
			body.elmercado-child-theme.woocommerce-shop .emo-global-category-filter-010226 ul,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters :is(.emo-vendor-category-filter,.emo-vendor-attribute-filter) ul {
				list-style:none !important; margin:0 !important; padding:0 !important;
			}
			body.elmercado-child-theme.woocommerce-shop .emo-global-category-filter-010226 li,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters :is(.emo-vendor-category-filter,.emo-vendor-attribute-filter) li {
				display:grid !important; grid-template-columns:minmax(0,1fr) auto !important; align-items:center !important; gap:8px !important;
				min-height:34px !important; margin:0 !important; padding:4px 1px !important; border:0 !important; background:transparent !important;
			}
			body.elmercado-child-theme.woocommerce-shop .emo-global-category-filter-010226 li > a,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters :is(.emo-vendor-category-filter,.emo-vendor-attribute-filter) li > a {
				min-width:0 !important; padding:0 !important; color:#35483f !important; font-size:12.5px !important; font-weight:650 !important; line-height:1.3 !important; text-decoration:none !important;
			}
			body.elmercado-child-theme.woocommerce-shop .emo-global-category-filter-010226 .count,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters :is(.emo-vendor-category-filter,.emo-vendor-attribute-filter) small {
				font-size:11px !important; font-weight:700 !important; color:#7b8b83 !important; white-space:nowrap !important;
			}

			/* Productor: misma caja exterior y misma densidad de Tienda. */
			@media (min-width:1101px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					box-sizing:border-box !important; width:250px !important; min-width:250px !important; max-width:250px !important;
					padding:18px !important; border:1px solid rgba(23,63,50,.11) !important; border-radius:18px !important;
					background:#fff !important; box-shadow:0 12px 32px rgba(17,42,34,.07) !important;
				}
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget {
				float:none !important; box-sizing:border-box !important; width:100% !important; margin:0 0 12px !important; padding:0 !important;
				border:0 !important; border-bottom:0 !important; border-radius:0 !important; background:transparent !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-filters .widget:last-child { margin-bottom:0 !important; }

			/* Categoría activa: misma tarjeta compacta que en Tienda y antes del precio. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context {
				box-sizing:border-box !important; width:100% !important; margin:0 0 14px !important; padding:0 !important; border:0 !important; background:transparent !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__eyebrow { display:none !important; }
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row {
				display:flex !important; align-items:center !important; justify-content:space-between !important; gap:10px !important; min-height:38px !important;
				padding:8px 10px !important; border:1px solid rgba(23,63,50,.10) !important; border-radius:10px !important; background:#f3f7f4 !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row strong {
				min-width:0 !important; color:#173f32 !important; font-size:13px !important; font-weight:750 !important; line-height:1.25 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-category-context__row a {
				display:inline-flex !important; align-items:center !important; gap:3px !important; padding:3px 2px !important; border:0 !important; background:transparent !important;
				color:#687b72 !important; font-size:10.5px !important; font-weight:700 !important; line-height:1 !important; text-decoration:none !important;
			}

			/* Filtro de precio: mismas dimensiones y controles que Tienda. */
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter form,
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider_wrapper { margin:0 !important; padding:0 !important; font-family:inherit !important; }
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider {
				position:relative !important; height:4px !important; min-height:4px !important; margin:12px 9px 20px !important; padding:0 !important; border:0 !important;
				border-radius:999px !important; background:#dfe9e3 !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider .ui-slider-range {
				top:0 !important; height:4px !important; border:0 !important; border-radius:999px !important; background:#2f7d5d !important; box-shadow:none !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider .ui-slider-handle {
				top:50% !important; width:18px !important; height:18px !important; min-width:18px !important; min-height:18px !important; margin-top:-9px !important; margin-left:-9px !important;
				padding:0 !important; border:3px solid #2f7d5d !important; border-radius:50% !important; background:#fff !important; box-shadow:0 1px 5px rgba(17,42,34,.12) !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider_amount {
				display:flex !important; align-items:center !important; justify-content:space-between !important; gap:10px !important; width:100% !important; min-height:40px !important;
				margin:0 !important; padding:0 !important; font-family:inherit !important; text-align:left !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_slider_amount .button {
				flex:0 0 auto !important; min-height:38px !important; margin:0 !important; padding:0 14px !important; border-radius:999px !important; font-family:inherit !important;
				font-size:12px !important; font-weight:750 !important; line-height:1 !important;
			}
			html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .emo-vendor-price-filter .price_label {
				min-width:0 !important; margin:0 0 0 auto !important; color:#42564e !important; font-family:inherit !important; font-size:11.5px !important;
				font-weight:700 !important; line-height:1.25 !important; text-align:right !important; white-space:nowrap !important;
			}

			@media (max-width:1100px) {
				html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store .left_sidebar.emo-vendor-filter-rail-010225 {
					background:#fff !important; border-left:1px solid rgba(23,63,50,.11) !important; box-shadow:-18px 0 48px rgba(17,42,34,.16) !important;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Orden, geometría inline y limpieza del texto redundante en tiendas WCFM.
 */
add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! function_exists( 'elmercado_vendor_store_is_request_010225' ) || ! elmercado_vendor_store_is_request_010225() ) {
			return;
		}
		?>
		<script id="elmercado-vendor-shop-parity-script-010226">
		(() => {
			'use strict';
			const store = document.querySelector('#wcfmmp-store');
			if (!store) return;
			const unwanted = 'Una selección de productos con procedencia clara para acercar el origen a tu mesa de una forma más directa.';
			let frame = 0;

			const normalize = (value) => (value || '').replace(/\s+/g, ' ').trim();
			const sync = () => {
				if (frame) cancelAnimationFrame(frame);
				frame = requestAnimationFrame(() => {
					frame = 0;
					store.querySelectorAll('p').forEach((node) => {
						if (normalize(node.textContent) === unwanted) node.remove();
					});

					const sidebar = store.querySelector('.left_sidebar.emo-vendor-filter-rail-010225');
					const panel = sidebar?.querySelector('#emo-vendor-filters');
					if (!sidebar || !panel) return;

					const context = panel.querySelector('#emo-vendor-category-context');
					const price = panel.querySelector('.emo-vendor-price-filter');
					const categories = panel.querySelector('#emo-vendor-category-filter');
					if (context && price && context.nextElementSibling !== price) panel.insertBefore(context, price);
					if (!context && price && categories && price.nextElementSibling !== categories) panel.insertBefore(categories, price.nextElementSibling);

					if (context) {
						context.querySelector('.emo-vendor-category-context__eyebrow')?.remove();
						const row = context.querySelector('.emo-vendor-category-context__row');
						const name = row?.querySelector('strong');
						const remove = row?.querySelector('a');
						if (name) name.classList.add('emo-category-context__name');
						if (remove) {
							remove.classList.add('emo-category-context__remove');
							if (!remove.querySelector('[aria-hidden="true"]')) remove.innerHTML = '<span aria-hidden="true">×</span><span>Quitar</span>';
						}
					}

					const desktop = window.matchMedia('(min-width:1101px)').matches;
					if (desktop) {
						sidebar.style.setProperty('box-sizing', 'border-box', 'important');
						sidebar.style.setProperty('width', '250px', 'important');
						sidebar.style.setProperty('min-width', '250px', 'important');
						sidebar.style.setProperty('max-width', '250px', 'important');
						sidebar.style.setProperty('padding', '18px', 'important');
						sidebar.style.setProperty('border', '1px solid rgba(23,63,50,.11)', 'important');
						sidebar.style.setProperty('border-radius', '18px', 'important');
						sidebar.style.setProperty('background', '#fff', 'important');
						sidebar.style.setProperty('box-shadow', '0 12px 32px rgba(17,42,34,.07)', 'important');
					} else {
						sidebar.style.setProperty('background', '#fff', 'important');
						sidebar.style.setProperty('border-left', '1px solid rgba(23,63,50,.11)', 'important');
						sidebar.style.setProperty('box-shadow', '-18px 0 48px rgba(17,42,34,.16)', 'important');
					}
				});
			};

			sync();
			new MutationObserver(sync).observe(store, { childList:true, subtree:true, characterData:true });
			window.addEventListener('resize', sync, { passive:true });
			window.addEventListener('pageshow', sync, { passive:true });
		})();
		</script>
		<?php
	},
	PHP_INT_MAX
);
