<?php
/**
 * Correcciones finales solicitadas tras la revisión en producción.
 *
 * - El filtro global por vendedor restringe el SQL real también para visitantes,
 *   incluso en consultas auxiliares que construyen la carga continua por IDs.
 * - La lista completa del scroll conserva exactamente la ordenación de WooCommerce.
 * - Las etiquetas de atributos de productos variables no llevan recuadro.
 * - Productores no muestra ninguna barra de búsqueda/filtrado de WCFM.
 * - Tienda no muestra la línea editorial secundaria solicitada.
 * - Las tiendas de productor ocultan "Vendido por" y evitan el parpadeo del contador.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Vendedor seleccionado y válido en el catálogo global.
 */
function elmercado_catalog_selected_vendor_010239(): int {
	if ( ! isset( $_GET['vendor_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 0;
	}

	$vendor_id = absint( wp_unslash( $_GET['vendor_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( $vendor_id <= 0 || ! function_exists( 'elmercado_core_filter_is_vendor' ) || ! elmercado_core_filter_is_vendor( $vendor_id ) ) {
		return 0;
	}

	return $vendor_id;
}

/**
 * Estamos en el catálogo global, no en la tienda individual de un productor.
 */
function elmercado_catalog_vendor_filter_request_010239(): bool {
	if ( is_admin() ) {
		return false;
	}
	if ( function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225() ) {
		return false;
	}

	$is_shop = function_exists( 'is_shop' ) && is_shop();
	$is_tax  = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
	return $is_shop || $is_tax;
}

/**
 * Detecta cualquier WP_Query de productos ejecutada durante el catálogo.
 * Esto incluye el loop principal y la consulta auxiliar de IDs de la carga
 * continua 0.10.234, que era la que reintroducía productos de otros vendedores.
 */
function elmercado_catalog_vendor_filter_product_query_010239( WP_Query $query ): bool {
	if ( ! elmercado_catalog_vendor_filter_request_010239() ) {
		return false;
	}

	if ( $query->is_main_query() ) {
		if ( function_exists( 'elmercado_catalog_is_main_query_010224' ) ) {
			return elmercado_catalog_is_main_query_010224( $query );
		}
		if ( $query->is_post_type_archive( 'product' ) || $query->is_tax( 'product_cat' ) || $query->is_tax( 'product_tag' ) ) {
			return true;
		}
	}

	$post_type = $query->get( 'post_type' );
	if ( 'product' === $post_type || ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
		return true;
	}

	return false;
}

/**
 * Reaplica la restricción después de las capas históricas que puedan modificar
 * author durante pre_get_posts. También cubre la consulta secundaria que genera
 * orderedIds para el scroll continuo.
 */
add_action(
	'pre_get_posts',
	static function ( WP_Query $query ): void {
		$vendor_id = elmercado_catalog_selected_vendor_010239();
		if ( $vendor_id <= 0 || ! elmercado_catalog_vendor_filter_product_query_010239( $query ) ) {
			return;
		}

		$query->set( 'author', $vendor_id );
		$query->set( 'author__in', array( $vendor_id ) );
		$query->set( 'emo_selected_vendor_010239', $vendor_id );
	},
	PHP_INT_MAX
);

/**
 * Última defensa antes de ejecutar cada SELECT de productos del catálogo. De
 * esta forma WooCommerce/WCFM o la reconstrucción del loader no pueden volver a
 * introducir productos pertenecientes a otro vendedor.
 *
 * @param array<string,string> $clauses Partes SQL de WP_Query.
 * @return array<string,string>
 */
add_filter(
	'posts_clauses',
	static function ( array $clauses, WP_Query $query ): array {
		$vendor_id = elmercado_catalog_selected_vendor_010239();
		if ( $vendor_id <= 0 || ! elmercado_catalog_vendor_filter_product_query_010239( $query ) ) {
			return $clauses;
		}

		global $wpdb;
		$clauses['where'] = (string) ( $clauses['where'] ?? '' ) . $wpdb->prepare(
			" AND {$wpdb->posts}.post_author = %d",
			$vendor_id
		);
		return $clauses;
	},
	PHP_INT_MAX,
	2
);

/**
 * Valor de ordenación solicitado por el visitante.
 */
function elmercado_catalog_scroll_ordering_value_010245(): string {
	$value = '';
	if ( isset( $_GET['orderby'] ) && ! is_array( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = sanitize_text_field( wp_unslash( (string) $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	if ( '' === $value ) {
		$query_value = get_query_var( 'orderby' );
		if ( is_string( $query_value ) ) {
			$value = sanitize_text_field( $query_value );
		}
	}
	if ( '' === $value ) {
		$value = sanitize_text_field( (string) get_option( 'woocommerce_default_catalog_orderby', 'menu_order' ) );
	}
	return strtolower( trim( $value ) );
}

/**
 * Identifica la consulta auxiliar de IDs que alimenta la carga continua 0.10.234.
 */
function elmercado_catalog_scroll_ids_query_010245( WP_Query $query ): bool {
	if ( is_admin() || 'ids' !== (string) $query->get( 'fields' ) ) {
		return false;
	}
	if ( -1 !== (int) $query->get( 'posts_per_page' ) || (bool) $query->get( 'suppress_filters' ) ) {
		return false;
	}
	$post_type = $query->get( 'post_type' );
	if ( 'product' !== $post_type && ! ( is_array( $post_type ) && in_array( 'product', $post_type, true ) ) ) {
		return false;
	}
	if ( function_exists( 'elmercado_catalog_filter_scroll_target_010234' ) ) {
		return elmercado_catalog_filter_scroll_target_010234();
	}
	$is_vendor = function_exists( 'elmercado_vendor_store_is_request_010225' ) && elmercado_vendor_store_is_request_010225();
	$is_shop   = function_exists( 'is_shop' ) && is_shop();
	$is_tax    = function_exists( 'is_product_taxonomy' ) && is_product_taxonomy();
	return $is_vendor || $is_shop || $is_tax;
}

/**
 * WooCommerce aplica precio, popularidad y valoración con filtros posts_clauses
 * temporales y los retira al terminar el loop principal. La consulta auxiliar
 * del scroll se ejecuta después, por lo que aquí reproducimos las cláusulas
 * oficiales y dejamos un desempate estable por product_id. También fijamos los
 * criterios simples para que todos los lotes compartan una única secuencia.
 *
 * @param array<string,string> $clauses Partes SQL de WP_Query.
 * @return array<string,string>
 */
add_filter(
	'posts_clauses',
	static function ( array $clauses, WP_Query $query ): array {
		if ( ! elmercado_catalog_scroll_ids_query_010245( $query ) ) {
			return $clauses;
		}

		$requested = elmercado_catalog_scroll_ordering_value_010245();
		$order     = in_array( $requested, array( 'date', 'modified' ), true ) ? 'DESC' : 'ASC';
		if ( '-desc' === substr( $requested, -5 ) ) {
			$order     = 'DESC';
			$requested = substr( $requested, 0, -5 );
		} elseif ( '-asc' === substr( $requested, -4 ) ) {
			$order     = 'ASC';
			$requested = substr( $requested, 0, -4 );
		}

		global $wpdb;
		$posts = $wpdb->posts;
		$alias = 'emo_scroll_order_lookup_010245';

		switch ( $requested ) {
			case 'price':
			case 'popularity':
			case 'rating':
				$table = $wpdb->prefix . 'wc_product_meta_lookup';
				if ( false === strpos( (string) ( $clauses['join'] ?? '' ), $alias ) ) {
					$clauses['join'] = (string) ( $clauses['join'] ?? '' ) . " LEFT JOIN {$table} {$alias} ON {$posts}.ID = {$alias}.product_id ";
				}
				if ( 'price' === $requested ) {
					$clauses['orderby'] = 'DESC' === $order
						? " {$alias}.max_price DESC, {$alias}.product_id DESC "
						: " {$alias}.min_price ASC, {$alias}.product_id ASC ";
				} elseif ( 'popularity' === $requested ) {
					$clauses['orderby'] = " {$alias}.total_sales DESC, {$alias}.product_id DESC ";
				} else {
					$clauses['orderby'] = " {$alias}.average_rating DESC, {$alias}.rating_count DESC, {$alias}.product_id DESC ";
				}
				break;

			case 'date':
				$clauses['orderby'] = " {$posts}.post_date {$order}, {$posts}.ID {$order} ";
				break;

			case 'modified':
				$clauses['orderby'] = " {$posts}.post_modified {$order}, {$posts}.ID {$order} ";
				break;

			case 'title':
				$clauses['orderby'] = " {$posts}.post_title {$order}, {$posts}.ID {$order} ";
				break;

			case 'id':
				$clauses['orderby'] = " {$posts}.ID {$order} ";
				break;

			case 'menu_order':
			case '':
				$clauses['orderby'] = " {$posts}.menu_order {$order}, {$posts}.post_title {$order}, {$posts}.ID {$order} ";
				break;
		}

		return $clauses;
	},
	PHP_INT_MAX,
	2
);

/**
 * Marcador de release y corrección visual del atributo de variación.
 */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() ) {
			return;
		}
		?>
		<style id="elmercado-user-reported-corrections-010240">
			body.single-product form.variations_form table.variations th.label {
				border:0 !important;
				border-width:0 !important;
				border-style:none !important;
				outline:0 !important;
				background:transparent !important;
				box-shadow:none !important;
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);

/**
 * Registramos esta capa en wp_loaded para que sus reglas de wp_head queden
 * detrás de todas las capas históricas que se registran en after_setup_theme.
 */
add_action(
	'wp_loaded',
	static function (): void {
		add_action(
			'wp_head',
			static function (): void {
				if ( is_admin() ) {
					return;
				}

				$vendor_label = '';
				if ( function_exists( 'elmercado_vendor_store_is_request_010225' )
					&& elmercado_vendor_store_is_request_010225()
					&& function_exists( 'elmercado_vendor_store_state_010225' ) ) {
					$state = elmercado_vendor_store_state_010225();
					$total = max( 0, (int) ( $state['total'] ?? 0 ) );
					$vendor_label = sprintf(
						esc_html( _n( '%s resultado', '%s resultados', $total, 'elmercadodeorigen' ) ),
						number_format_i18n( $total )
					);
				}
				?>
				<style id="elmercado-production-ui-polish-010240">
					/* Todas las fichas de producto: menos aire vertical sin alterar tipografía ni ancho. */
					html body.elmercado-child-theme ul.products li.product .price {
						margin-top:0.4rem !important;
						margin-bottom:0 !important;
						padding-top:0.4rem !important;
						line-height:1.25 !important;
					}
					html body.elmercado-child-theme ul.products li.product .wcfmmp_sold_by_container {
						margin-top:0.4rem !important;
						margin-bottom:0 !important;
						padding-top:0.4rem !important;
						line-height:1.35 !important;
					}
					html body.elmercado-child-theme ul.products li.product .button {
						margin-top:0.62rem !important;
					}
					@media (max-width:767px) {
						html body.elmercado-child-theme ul.products li.product .price {
							margin-top:0.32rem !important;
							padding-top:0.32rem !important;
						}
						html body.elmercado-child-theme ul.products li.product .wcfmmp_sold_by_container {
							margin-top:0.32rem !important;
							padding-top:0.32rem !important;
						}
						html body.elmercado-child-theme ul.products li.product .button {
							margin-top:0.5rem !important;
						}
					}

					/*
					 * Productores: la búsqueda de WCFM se inserta fuera de
					 * #wcfmmp-stores-wrap, por eso la regla anterior no podía alcanzarla.
					 */
					html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfm-store-list-page,.wcfmmp-store-list-page) :is(
						.wcfmmp-store-search-form,
						.wcfmmp-store-search-form-box,
						.wcfmmp-store-lists-sorting,
						[class*="store-search-form"]
					) {
						display:none !important;
						visibility:hidden !important;
						width:0 !important;
						height:0 !important;
						min-width:0 !important;
						min-height:0 !important;
						max-width:0 !important;
						max-height:0 !important;
						margin:0 !important;
						padding:0 !important;
						border:0 !important;
						opacity:0 !important;
						overflow:hidden !important;
						pointer-events:none !important;
					}
					html body.elmercado-child-theme:is(.elmercado-producers-page,.wcfm-store-list-page,.wcfmmp-store-list-page) .woocommerce-result-count {
						display:none !important;
						visibility:hidden !important;
						height:0 !important;
						min-height:0 !important;
						margin:0 !important;
						padding:0 !important;
					}

					/* Tienda: se retira por completo la línea editorial indicada. */
					html body.elmercado-child-theme.woocommerce-shop .emo-shop-lead.emo-shop-lead--final,
					html body.elmercado-child-theme.post-type-archive-product .emo-shop-lead.emo-shop-lead--final {
						display:none !important;
						visibility:hidden !important;
						height:0 !important;
						min-height:0 !important;
						max-height:0 !important;
						margin:0 !important;
						padding:0 !important;
						border:0 !important;
						overflow:hidden !important;
					}

					/* Dentro de una tienda de productor, el vendedor ya es evidente. */
					html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store ul.products li.product :is(
						.wcfmmp_sold_by_container,
						.wcfmmp_sold_by_wrapper,
						.wcfmmp_sold_by_label,
						.wcfmmp_sold_by_logo,
						[class*="sold_by"]
					) {
						display:none !important;
						visibility:hidden !important;
						height:0 !important;
						min-height:0 !important;
						margin:0 !important;
						padding:0 !important;
						border:0 !important;
						overflow:hidden !important;
					}

					<?php if ( '' !== $vendor_label ) : ?>
					/*
					 * El HTML nativo llega como "Mostrando 1–N de X". Antes de que el
					 * runtime lo normalice, ocultamos solo ese texto y pintamos el total
					 * exacto calculado en PHP. Así no existe un estado visual intermedio.
					 */
					html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woocommerce-result-count:not(.emo-vendor-result-count-010225) {
						color:transparent !important;
						font-size:0 !important;
						text-shadow:none !important;
					}
					html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woocommerce-result-count:not(.emo-vendor-result-count-010225)::after {
						content:<?php echo wp_json_encode( $vendor_label ); ?>;
						display:block !important;
						color:#42564e !important;
						font-family:inherit !important;
						font-size:12px !important;
						font-weight:700 !important;
						letter-spacing:0 !important;
						line-height:1.3 !important;
						white-space:nowrap !important;
					}
					@media (max-width:991px) {
						html body.elmercado-child-theme.wcfmmp-store-page #wcfmmp-store#wcfmmp-store .woocommerce-result-count:not(.emo-vendor-result-count-010225)::after {
							font-size:9.8px !important;
							line-height:1.35 !important;
						}
					}
					<?php endif; ?>
				</style>
				<?php
			},
			PHP_INT_MAX
		);
	},
	PHP_INT_MAX
);
