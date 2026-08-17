<?php
/**
 * Página visual de todas las categorías 0.10.257.
 *
 * Comparte con Tienda/Home la misma verdad de catálogo: productos publicados,
 * catalogables y con stock; el público no ve vendedores WCFM desactivados y
 * los administradores sí conservan esa visibilidad completa.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** URL canónica del índice de categorías. */
function elmercado_categories_hub_url_010257(): string {
	$page = get_page_by_path( 'categorias', OBJECT, 'page' );
	return $page instanceof WP_Post ? get_permalink( $page ) : home_url( '/categorias/' );
}

/** Sólo un administrador autenticado puede ver categorías internas. */
function elmercado_categories_hub_can_view_private_010257(): bool {
	return is_user_logged_in() && current_user_can( 'manage_options' );
}

/**
 * Pequeña lista natural para describir subcategorías/productos sin inventar
 * contenido editorial que no exista en WordPress.
 *
 * @param string[] $values Valores ya saneados.
 */
function elmercado_categories_hub_join_010257( array $values ): string {
	$values = array_values( array_filter( array_map( 'trim', $values ) ) );
	$count  = count( $values );
	if ( $count < 2 ) {
		return $values[0] ?? '';
	}
	$last = array_pop( $values );
	return implode( ', ', $values ) . ' y ' . $last;
}

/**
 * Ejemplos reales de producto para una categoría cuando no tiene descripción.
 * Respeta exactamente las exclusiones del catálogo actual.
 *
 * @return string[]
 */
function elmercado_categories_hub_product_examples_010257( int $term_id ): array {
	$args = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'fields'                 => 'ids',
		'posts_per_page'         => 3,
		'orderby'                => 'date',
		'order'                  => 'DESC',
		'ignore_sticky_posts'    => true,
		'suppress_filters'       => false,
		'cache_results'          => false,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'tax_query'              => array(
			array(
				'taxonomy'         => 'product_cat',
				'field'            => 'term_id',
				'terms'            => array( absint( $term_id ) ),
				'include_children' => true,
			),
		),
	);

	if ( function_exists( 'elmercado_catalog_force_in_stock_tax_query_010219' ) ) {
		$args['tax_query'] = elmercado_catalog_force_in_stock_tax_query_010219( $args['tax_query'] );
	}
	if ( function_exists( 'elmercado_catalog_counts_excluded_authors_010217' ) ) {
		$excluded = elmercado_catalog_counts_excluded_authors_010217();
		if ( $excluded ) {
			$args['author__not_in'] = $excluded;
		}
	}

	$query = new WP_Query( $args );
	$names = array();
	foreach ( array_map( 'absint', (array) $query->posts ) as $product_id ) {
		$title = trim( wp_strip_all_tags( get_the_title( $product_id ) ) );
		if ( '' !== $title ) {
			$names[] = wp_trim_words( $title, 6, '' );
		}
	}
	wp_reset_postdata();
	return array_values( array_unique( $names ) );
}

/**
 * Texto útil de tarjeta. Primero usa la descripción editorial del término;
 * después subcategorías reales y, como último recurso, productos reales.
 *
 * @param array<string,mixed> $item Categoría normalizada.
 * @param array<int,array<int,string>> $children_by_parent Hijos visibles.
 */
function elmercado_categories_hub_description_010257( array $item, array $children_by_parent ): string {
	$description = trim( wp_strip_all_tags( html_entity_decode( (string) ( $item['description'] ?? '' ), ENT_QUOTES, get_bloginfo( 'charset' ) ) ) );
	if ( '' !== $description ) {
		return wp_trim_words( $description, 34, '…' );
	}

	$term_id = absint( $item['id'] ?? 0 );
	$name    = (string) ( $item['name'] ?? '' );
	$children = array_slice( $children_by_parent[ $term_id ] ?? array(), 0, 3 );
	if ( $children ) {
		return sprintf(
			/* translators: 1: category name, 2: child category names. */
			esc_html__( 'Dentro de %1$s encontrarás %2$s, además de otras propuestas disponibles según el catálogo actual.', 'elmercadodeorigen' ),
			$name,
			elmercado_categories_hub_join_010257( $children )
		);
	}

	$examples = elmercado_categories_hub_product_examples_010257( $term_id );
	if ( $examples ) {
		return sprintf(
			/* translators: 1: product examples, 2: category name. */
			esc_html__( 'Entre los productos disponibles encontrarás %1$s y otras propuestas de %2$s, siempre con el productor identificado.', 'elmercadodeorigen' ),
			elmercado_categories_hub_join_010257( $examples ),
			$name
		);
	}

	return sprintf(
		/* translators: %s: category name. */
		esc_html__( 'Explora la selección disponible de %s, con origen claro, productor visible y disponibilidad actual.', 'elmercadodeorigen' ),
		$name
	);
}

/**
 * Todas las categorías con producto que corresponden al usuario actual.
 * Incluye subcategorías: la Home sigue mostrando las grandes familias y esta
 * página permite recorrer el catálogo completo.
 *
 * @return array<int,array<string,mixed>>
 */
function elmercado_categories_hub_items_010257(): array {
	if ( ! taxonomy_exists( 'product_cat' ) || ! function_exists( 'elmercado_catalog_visible_category_counts_010217' ) ) {
		return array();
	}

	global $wpdb;
	$counts = elmercado_catalog_visible_category_counts_010217();
	$rows   = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->prepare(
			"SELECT t.term_id, t.name, t.slug, tt.parent, tt.description
			FROM {$wpdb->terms} t
			INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_id = t.term_id
			WHERE tt.taxonomy = %s",
			'product_cat'
		)
	);

	$default_id  = (int) get_option( 'default_product_cat' );
	$can_private = elmercado_categories_hub_can_view_private_010257();
	$items       = array();
	$names       = array();

	foreach ( (array) $rows as $row ) {
		$term_id = absint( $row->term_id ?? 0 );
		$count   = max( 0, (int) ( $counts[ $term_id ] ?? 0 ) );
		$slug    = sanitize_title( (string) ( $row->slug ?? '' ) );
		if ( $term_id <= 0 || $term_id === $default_id || $count <= 0 ) {
			continue;
		}
		if ( ! $can_private && 'mentta' === $slug ) {
			continue;
		}

		$link = get_term_link( $term_id, 'product_cat' );
		if ( is_wp_error( $link ) ) {
			continue;
		}

		$thumbnail_id = (int) get_term_meta( $term_id, 'thumbnail_id', true );
		$image         = $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' ) : '';
		$parent_id     = absint( $row->parent ?? 0 );
		$name          = (string) ( $row->name ?? '' );
		$names[ $term_id ] = $name;

		$items[] = array(
			'id'          => $term_id,
			'name'        => $name,
			'slug'        => $slug,
			'parent'      => $parent_id,
			'description' => (string) ( $row->description ?? '' ),
			'count'       => $count,
			'link'        => (string) $link,
			'image'       => (string) $image,
		);
	}

	$children_by_parent = array();
	foreach ( $items as $item ) {
		$parent = absint( $item['parent'] ?? 0 );
		if ( $parent > 0 ) {
			$children_by_parent[ $parent ][] = (string) $item['name'];
		}
	}

	foreach ( $items as &$item ) {
		$parent_id          = absint( $item['parent'] ?? 0 );
		$item['parent_name'] = $parent_id > 0 ? ( $names[ $parent_id ] ?? '' ) : '';
		$item['summary']     = elmercado_categories_hub_description_010257( $item, $children_by_parent );
	}
	unset( $item );

	usort(
		$items,
		static function ( array $left, array $right ): int {
			$left_root  = 0 === absint( $left['parent'] ?? 0 );
			$right_root = 0 === absint( $right['parent'] ?? 0 );
			if ( $left_root !== $right_root ) {
				return $left_root ? -1 : 1;
			}
			if ( ! $left_root && (string) ( $left['parent_name'] ?? '' ) !== (string) ( $right['parent_name'] ?? '' ) ) {
				return strnatcasecmp( (string) ( $left['parent_name'] ?? '' ), (string) ( $right['parent_name'] ?? '' ) );
			}
			$by_count = (int) ( $right['count'] ?? 0 ) <=> (int) ( $left['count'] ?? 0 );
			return 0 !== $by_count ? $by_count : strnatcasecmp( (string) $left['name'], (string) $right['name'] );
		}
	);

	return $items;
}

add_filter(
	'body_class',
	static function ( array $classes ): array {
		if ( is_page( 'categorias' ) ) {
			$classes[] = 'emo-categories-page';
		}
		return array_values( array_unique( $classes ) );
	},
	PHP_INT_MAX
);

/** Diseño final de la página de categorías y pequeño ajuste del CTA de Home. */
add_action(
	'wp_head',
	static function (): void {
		if ( is_admin() || ( ! is_page( 'categorias' ) && ! is_front_page() ) ) {
			return;
		}
		?>
		<style id="elmercado-categories-hub-010257">
			body.home.elmercado-child-theme .emo-categories .emo-section-heading > div > p {
				max-width: 650px;
				margin: 18px 0 0;
				color: var(--emo-muted, #68736d);
				font-size: 1.02rem;
				line-height: 1.65;
			}
			body.home.elmercado-child-theme .emo-categories .emo-section-heading > .emo-text-link {
				flex: 0 0 auto;
				margin-bottom: 8px;
				white-space: nowrap;
			}

			body.emo-categories-page.elmercado-child-theme {
				background: #f7f3ea;
			}
			body.emo-categories-page .site-content,
			body.emo-categories-page #content {
				background: #f7f3ea;
			}
			.emo-categories-hub {
				width: 100%;
				min-width: 0;
				padding: clamp(22px, 3vw, 38px) 0 clamp(58px, 8vw, 100px);
				overflow: hidden;
			}
			.emo-categories-hub__hero {
				position: relative;
				width: min(calc(100% - 40px), 1180px);
				margin: 0 auto clamp(42px, 6vw, 72px);
				padding: clamp(52px, 7vw, 88px) clamp(28px, 7vw, 82px);
				background: linear-gradient(135deg, #10271f 0%, #1d4637 58%, #315e48 100%);
				border: 1px solid rgba(255,255,255,.08);
				border-radius: 30px;
				box-shadow: 0 24px 64px rgba(13,33,27,.16);
				color: #fff;
				overflow: hidden;
			}
			.emo-categories-hub__hero::after {
				position: absolute;
				top: -180px;
				right: -130px;
				width: 430px;
				height: 430px;
				background: radial-gradient(circle, rgba(215,168,79,.24), rgba(215,168,79,0) 68%);
				content: "";
				pointer-events: none;
			}
			.emo-categories-hub__hero-inner {
				position: relative;
				z-index: 1;
				max-width: 850px;
			}
			.emo-categories-hub__hero .emo-kicker {
				color: #efc9b4;
			}
			.emo-categories-hub__hero h1 {
				max-width: 820px;
				margin: 0 0 22px;
				color: #fff;
				font-size: clamp(44px, 6vw, 76px);
				line-height: .99;
				letter-spacing: -.052em;
			}
			.emo-categories-hub__hero p {
				max-width: 720px;
				margin: 0;
				color: rgba(255,255,255,.82);
				font-size: clamp(16px, 1.45vw, 19px);
				line-height: 1.7;
			}
			.emo-categories-hub__stat {
				display: inline-flex;
				align-items: center;
				gap: 9px;
				margin-top: 28px;
				padding: 9px 14px;
				background: rgba(255,255,255,.09);
				border: 1px solid rgba(255,255,255,.12);
				border-radius: 999px;
				color: rgba(255,255,255,.92);
				font-size: 13px;
				font-weight: 750;
			}
			.emo-categories-hub__stat::before {
				width: 7px;
				height: 7px;
				background: #d7a84f;
				border-radius: 50%;
				content: "";
			}
			.emo-categories-hub__content {
				width: min(calc(100% - 40px), 1180px);
				margin: 0 auto;
			}
			.emo-categories-hub__intro {
				display: flex;
				align-items: end;
				justify-content: space-between;
				gap: 32px;
				margin-bottom: 28px;
			}
			.emo-categories-hub__intro h2 {
				margin: 0;
				font-size: clamp(31px, 3.7vw, 48px);
			}
			.emo-categories-hub__intro p {
				max-width: 460px;
				margin: 0 0 4px;
				color: #68736d;
			}
			.emo-categories-hub__grid {
				display: grid;
				grid-template-columns: repeat(3, minmax(0,1fr));
				gap: 22px;
			}
			.emo-category-hub-card {
				position: relative;
				display: flex;
				min-width: 0;
				flex-direction: column;
				background: #fff;
				border: 1px solid rgba(13,33,27,.085);
				border-radius: 22px;
				box-shadow: 0 10px 30px rgba(13,33,27,.055);
				overflow: hidden;
				transition: transform 220ms ease, box-shadow 220ms ease, border-color 220ms ease;
			}
			.emo-category-hub-card:hover {
				transform: translateY(-5px);
				border-color: rgba(47,102,80,.2);
				box-shadow: 0 20px 45px rgba(13,33,27,.105);
			}
			.emo-category-hub-card__media {
				position: relative;
				display: block;
				width: 100%;
				aspect-ratio: 16 / 10;
				background-color: #e8e5dc;
				background-image: var(--emo-category-image, linear-gradient(135deg,#dfe8df,#eee6d8));
				background-position: center;
				background-repeat: no-repeat;
				background-size: cover;
				overflow: hidden;
			}
			.emo-category-hub-card__media::after {
				position: absolute;
				inset: 0;
				background: linear-gradient(to top, rgba(13,33,27,.12), transparent 46%);
				content: "";
			}
			.emo-category-hub-card__body {
				display: flex;
				min-height: 235px;
				flex: 1 1 auto;
				flex-direction: column;
				padding: 24px;
			}
			.emo-category-hub-card__parent {
				display: inline-flex;
				align-self: flex-start;
				margin-bottom: 11px;
				padding: 5px 9px;
				background: #e9f0eb;
				border-radius: 999px;
				color: #315e48;
				font-size: 10px;
				font-weight: 800;
				letter-spacing: .08em;
				line-height: 1.2;
				text-transform: uppercase;
			}
			.emo-category-hub-card h2 {
				margin: 0 0 12px;
				font-size: clamp(25px, 2.2vw, 31px);
				line-height: 1.06;
			}
			.emo-category-hub-card h2 a {
				color: #122a22;
				text-decoration: none;
			}
			.emo-category-hub-card__summary {
				margin: 0 0 22px;
				color: #5e6a64;
				font-size: 14px;
				line-height: 1.67;
			}
			.emo-category-hub-card__footer {
				display: flex;
				align-items: center;
				justify-content: space-between;
				gap: 16px;
				margin-top: auto;
				padding-top: 16px;
				border-top: 1px solid rgba(13,33,27,.075);
			}
			.emo-category-hub-card__count {
				color: #68736d;
				font-size: 12px;
				font-weight: 750;
			}
			.emo-category-hub-card__link {
				display: inline-flex;
				align-items: center;
				gap: 7px;
				color: #21483a;
				font-size: 12px;
				font-weight: 850;
				text-decoration: none;
			}
			.emo-category-hub-card__link span {
				font-size: 17px;
				line-height: 1;
				transition: transform 180ms ease;
			}
			.emo-category-hub-card:hover .emo-category-hub-card__link span {
				transform: translateX(3px);
			}
			.emo-categories-hub__empty {
				padding: 48px;
				background: #fff;
				border: 1px solid rgba(13,33,27,.08);
				border-radius: 22px;
				text-align: center;
			}

			@media (max-width: 980px) {
				.emo-categories-hub__grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
				.emo-categories-hub__intro { align-items: flex-start; flex-direction: column; gap: 12px; }
				body.home.elmercado-child-theme .emo-categories .emo-section-heading { align-items: flex-start; flex-direction: column; gap: 20px; }
			}
			@media (max-width: 640px) {
				.emo-categories-hub { padding-top: 14px; }
				.emo-categories-hub__hero,
				.emo-categories-hub__content { width: calc(100% - 20px); }
				.emo-categories-hub__hero { margin-bottom: 36px; padding: 42px 22px 44px; border-radius: 20px; }
				.emo-categories-hub__hero h1 { font-size: clamp(39px, 12vw, 54px); }
				.emo-categories-hub__grid { grid-template-columns: minmax(0,1fr); gap: 16px; }
				.emo-category-hub-card__body { min-height: 0; padding: 21px; }
				.emo-category-hub-card__media { aspect-ratio: 16 / 9; }
				.emo-category-hub-card__footer { align-items: flex-start; }
				body.home.elmercado-child-theme .emo-categories .emo-section-heading > .emo-text-link { margin: 0; }
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
