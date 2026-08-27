<?php
/**
 * Descubrimiento editorial del blog: búsqueda, categorías, destacados y SEO.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Copy helper that stays deterministic even when translation catalogues lag behind. */
function elmercado_blog_copy_010263( string $es, string $en ): string {
	$is_english = function_exists( 'elmercado_is_english_request_010245' ) && elmercado_is_english_request_010245();
	return $is_english ? $en : $es;
}

/** Language-aware public blog URL. */
function elmercado_blog_public_url_010263(): string {
	$is_english = function_exists( 'elmercado_is_english_request_010245' ) && elmercado_is_english_request_010245();
	return $is_english ? home_url( '/en/journal/' ) : elmercado_blog_url();
}

/** @return array{query:string,categories:array<int,string>} */
function elmercado_blog_filter_state_010263(): array {
	$query = '';
	if ( isset( $_GET['blog_q'] ) && is_scalar( $_GET['blog_q'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$query = sanitize_text_field( wp_unslash( (string) $_GET['blog_q'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$category_value = '';
	if ( isset( $_GET['blog_cats'] ) && is_scalar( $_GET['blog_cats'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$category_value = sanitize_text_field( wp_unslash( (string) $_GET['blog_cats'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}
	$categories = array();
	foreach ( explode( ',', $category_value ) as $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' !== $slug ) {
			$categories[] = $slug;
		}
	}
	return array( 'query' => trim( $query ), 'categories' => array_values( array_unique( $categories ) ) );
}

/** Whether the current request is a dynamic blog search/filter URL. */
function elmercado_blog_has_dynamic_filters_010263(): bool {
	$state = elmercado_blog_filter_state_010263();
	return '' !== $state['query'] || ! empty( $state['categories'] );
}

/** @return array<int,WP_Term> */
function elmercado_blog_categories_010263(): array {
	$terms = get_terms( array( 'taxonomy' => 'category', 'hide_empty' => true, 'orderby' => 'count', 'order' => 'DESC' ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	$default_id = (int) get_option( 'default_category' );
	$output = array();
	foreach ( $terms as $term ) {
		if ( ! $term instanceof WP_Term || (int) $term->term_id === $default_id ) {
			continue;
		}
		if ( in_array( sanitize_title( $term->slug ), array( 'sin-categoria', 'uncategorized', 'guias-y-consejos' ), true ) ) {
			continue;
		}
		$output[] = $term;
	}
	return $output;
}

/** @return array<int,string> */
function elmercado_blog_product_category_candidates_010263( WP_Term $category ): array {
	$slug = sanitize_title( $category->slug );
	$name = sanitize_title( $category->name );
	$aliases = array(
		'jamones-y-paletas'      => array( 'jamones-paletas', 'jamones-y-paletas', 'jamones-paletas-ibericas' ),
		'jamones-paletas'        => array( 'jamones-paletas', 'jamones-y-paletas', 'jamones-paletas-ibericas' ),
		'embutidos'              => array( 'embutidos-y-curados', 'embutidos', 'embutidos-curados' ),
		'embutidos-y-curados'    => array( 'embutidos-y-curados', 'embutidos', 'embutidos-curados' ),
		'carnes'                 => array( 'carnes', 'carne' ),
		'aceites'                => array( 'aceites', 'aceite-de-oliva', 'aceites-de-oliva' ),
		'aceite-de-oliva'        => array( 'aceite-de-oliva', 'aceites', 'aceites-de-oliva' ),
		'conservas'              => array( 'conservas' ),
		'hortalizas'             => array( 'hortalizas-verduras', 'hortalizas', 'frutas-y-hortalizas', 'frutas-hortalizas' ),
		'hortalizas-y-verduras'  => array( 'hortalizas-verduras', 'hortalizas', 'verduras' ),
		'legumbres'              => array( 'legumbres' ),
		'lotes'                  => array( 'lotes', 'lotes-y-regalos', 'packs-y-lotes' ),
		'packs-y-lotes'          => array( 'packs-y-lotes', 'lotes', 'lotes-y-regalos' ),
	);
	$candidates = array( $slug, $name );
	if ( isset( $aliases[ $slug ] ) ) {
		$candidates = array_merge( $candidates, $aliases[ $slug ] );
	}
	$candidates[] = str_replace( '-y-', '-', $slug );
	return array_values( array_unique( array_filter( array_map( 'sanitize_title', $candidates ) ) ) );
}

/** Resolve a blog category to the matching product-category image. */
function elmercado_blog_category_image_010263( WP_Term $category ): string {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return '';
	}
	foreach ( elmercado_blog_product_category_candidates_010263( $category ) as $candidate ) {
		$product_term = get_term_by( 'slug', $candidate, 'product_cat' );
		if ( ! $product_term instanceof WP_Term ) {
			continue;
		}
		$thumbnail_id = (int) get_term_meta( $product_term->term_id, 'thumbnail_id', true );
		if ( $thumbnail_id > 0 ) {
			$image = wp_get_attachment_image_url( $thumbnail_id, 'woocommerce_thumbnail' );
			if ( is_string( $image ) && '' !== $image ) {
				return $image;
			}
		}
	}
	return '';
}

/** @return array<int,int> */
function elmercado_blog_featured_ids_010263( int $limit = 3, bool $fill = true ): array {
	$limit = max( 1, $limit );
	$ids = get_posts(
		array(
			'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit, 'fields' => 'ids',
			'meta_key' => '_emdo_blog_featured_priority',
			'orderby' => array( 'meta_value_num' => 'ASC', 'date' => 'DESC' ),
			'meta_query' => array( array( 'key' => '_emdo_blog_featured', 'value' => '1' ) ),
			'no_found_rows' => true, 'suppress_filters' => false, 'ignore_sticky_posts' => true,
			'update_post_meta_cache' => false, 'update_post_term_cache' => false,
		)
	);
	$ids = array_values( array_unique( array_map( 'intval', (array) $ids ) ) );
	if ( $fill && count( $ids ) < $limit ) {
		$fillers = get_posts(
			array(
				'post_type' => 'post', 'post_status' => 'publish', 'posts_per_page' => $limit - count( $ids ), 'fields' => 'ids',
				'post__not_in' => $ids, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true,
				'suppress_filters' => false, 'ignore_sticky_posts' => true, 'update_post_meta_cache' => false, 'update_post_term_cache' => false,
			)
		);
		$ids = array_merge( $ids, array_map( 'intval', (array) $fillers ) );
	}
	return array_slice( array_values( array_unique( $ids ) ), 0, $limit );
}

/** @param array<int,string> $active_categories Active category slugs. */
function elmercado_render_blog_discovery_controls_010263( string $query = '', array $active_categories = array() ): void {
	$categories = elmercado_blog_categories_010263();
	$active_categories = array_values( array_unique( array_map( 'sanitize_title', $active_categories ) ) );
	?>
	<section class="emo-blog-discovery" aria-label="<?php echo esc_attr( elmercado_blog_copy_010263( 'Buscar y filtrar artículos', 'Search and filter articles' ) ); ?>">
		<div class="emo-blog-discovery__heading"><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Buscar artículos', 'Search articles' ) ); ?></span><h2><?php echo esc_html( elmercado_blog_copy_010263( 'Encuentra el artículo que necesitas', 'Find the article you need' ) ); ?></h2></div>
		<form class="emo-blog-filter-form" action="<?php echo esc_url( elmercado_blog_public_url_010263() ); ?>" method="get" data-blog-filter-form="010263">
			<div class="emo-blog-search-row">
				<label class="screen-reader-text" for="emo-blog-search-010263"><?php echo esc_html( elmercado_blog_copy_010263( 'Buscar artículos', 'Search articles' ) ); ?></label>
				<input id="emo-blog-search-010263" name="blog_q" type="search" value="<?php echo esc_attr( $query ); ?>" placeholder="<?php echo esc_attr( elmercado_blog_copy_010263( 'Buscar: conservar jamón, aceite, morcillo…', 'Search: storing ham, olive oil, beef cuts…' ) ); ?>" autocomplete="off" />
				<button type="submit"><?php echo esc_html( elmercado_blog_copy_010263( 'Buscar', 'Search' ) ); ?></button>
			</div>
			<input type="hidden" name="blog_cats" value="<?php echo esc_attr( implode( ',', $active_categories ) ); ?>" data-blog-cats-value />
			<div class="emo-blog-chips" aria-label="<?php echo esc_attr( elmercado_blog_copy_010263( 'Categorías del blog', 'Blog categories' ) ); ?>">
				<button type="button" class="emo-blog-chip<?php echo empty( $active_categories ) ? ' is-active' : ''; ?>" data-blog-all aria-pressed="<?php echo empty( $active_categories ) ? 'true' : 'false'; ?>"><?php echo esc_html( elmercado_blog_copy_010263( 'Todos', 'All' ) ); ?></button>
				<?php foreach ( $categories as $category ) : $selected = in_array( sanitize_title( $category->slug ), $active_categories, true ); ?>
					<button type="button" class="emo-blog-chip<?php echo $selected ? ' is-active' : ''; ?>" data-blog-chip="<?php echo esc_attr( $category->slug ); ?>" aria-pressed="<?php echo $selected ? 'true' : 'false'; ?>"><?php echo esc_html( $category->name ); ?></button>
				<?php endforeach; ?>
			</div>
			<div class="emo-blog-filter-actions">
				<button type="submit" class="emo-blog-apply"><?php echo esc_html( elmercado_blog_copy_010263( 'Aplicar filtros', 'Apply filters' ) ); ?></button>
				<?php if ( '' !== $query || ! empty( $active_categories ) ) : ?><a href="<?php echo esc_url( elmercado_blog_public_url_010263() ); ?>"><?php echo esc_html( elmercado_blog_copy_010263( 'Limpiar', 'Clear' ) ); ?></a><?php endif; ?>
			</div>
		</form>
	</section>
	<?php
}

/** Render visual category navigation using the same category images as the shop. */
function elmercado_render_blog_category_navigation_010263(): void {
	$categories = elmercado_blog_categories_010263();
	if ( empty( $categories ) ) {
		return;
	}
	?>
	<section class="emo-blog-category-navigation">
		<header class="emo-blog-section-heading"><div><span class="emo-kicker"><?php echo esc_html( elmercado_blog_copy_010263( 'Explorar por categoría', 'Explore by category' ) ); ?></span><h2><?php echo esc_html( elmercado_blog_copy_010263( 'Artículos agrupados por categoría', 'Articles grouped by category' ) ); ?></h2></div></header>
		<div class="emo-blog-category-grid">
			<?php foreach ( $categories as $category ) :
				$link = get_category_link( $category );
				if ( is_wp_error( $link ) ) { continue; }
				$image = elmercado_blog_category_image_010263( $category );
				$style = '' !== $image ? ' style="--emo-blog-category-image:url(' . esc_url( $image ) . ')"' : '';
				$count_label = sprintf( elmercado_blog_copy_010263( '%s artículos', '%s articles' ), number_format_i18n( (int) $category->count ) );
				?>
				<a class="emo-blog-category-card" href="<?php echo esc_url( $link ); ?>"<?php echo $style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>><span class="emo-blog-category-card__media" aria-hidden="true"></span><span class="emo-blog-category-card__content"><strong><?php echo esc_html( $category->name ); ?></strong><small><?php echo esc_html( $count_label ); ?></small></span></a>
			<?php endforeach; ?>
		</div>
	</section>
	<?php
}

/** Render pagination for a custom blog query while preserving active filters. */
function elmercado_render_blog_pagination_010263( WP_Query $query ): void {
	if ( $query->max_num_pages <= 1 ) { return; }
	$current = max( 1, (int) get_query_var( 'paged' ) );
	$state = elmercado_blog_filter_state_010263();
	$args = array();
	if ( '' !== $state['query'] ) { $args['blog_q'] = $state['query']; }
	if ( ! empty( $state['categories'] ) ) { $args['blog_cats'] = implode( ',', $state['categories'] ); }
	?>
	<nav class="emo-journal-pagination" aria-label="<?php echo esc_attr( elmercado_blog_copy_010263( 'Paginación del blog', 'Blog pagination' ) ); ?>">
		<?php echo wp_kses_post( paginate_links( array( 'current' => $current, 'total' => (int) $query->max_num_pages, 'mid_size' => 1, 'prev_text' => '←', 'next_text' => '→', 'add_args' => $args ) ) ); ?>
	</nav>
	<?php
}

add_action( 'pre_get_posts', static function ( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_category() ) { return; }
	$query->set( 'posts_per_page', 12 );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}, 20 );

add_action( 'template_redirect', static function (): void {
	if ( is_admin() || ! ( is_home() || is_category() ) || ! elmercado_blog_has_dynamic_filters_010263() ) { return; }
	if ( ! headers_sent() ) { header( 'X-Robots-Tag: noindex, follow', true ); }
}, -1900 );

add_filter( 'wp_robots', static function ( array $robots ): array {
	if ( ( is_home() || is_category() ) && elmercado_blog_has_dynamic_filters_010263() ) {
		$robots['noindex'] = true; unset( $robots['nofollow'] ); return $robots;
	}
	if ( is_category() ) { unset( $robots['noindex'] ); }
	return $robots;
}, PHP_INT_MAX );

add_filter( 'aioseo_robots_meta', static function ( $attributes ) {
	if ( ! is_array( $attributes ) || ! ( is_home() || is_category() ) ) { return $attributes; }
	if ( elmercado_blog_has_dynamic_filters_010263() ) {
		$attributes['noindex'] = 'noindex'; $attributes['nofollow'] = ''; return $attributes;
	}
	if ( is_category() ) { unset( $attributes['noindex'] ); $attributes['index'] = 'index'; }
	return $attributes;
}, PHP_INT_MAX );

add_filter( 'aioseo_canonical_url', static function ( $url ) {
	if ( ! is_string( $url ) || ! ( is_home() || is_category() ) || ! elmercado_blog_has_dynamic_filters_010263() ) { return $url; }
	return elmercado_blog_public_url_010263();
}, PHP_INT_MAX );

add_action( 'wp_enqueue_scripts', static function (): void {
	if ( ! ( is_home() || is_category() ) ) { return; }
	wp_enqueue_style( 'elmercado-blog-discovery-010263', ELMERCADO_THEME_URL . '/assets/css/blog-discovery-010263.css', array( 'elmercado-editorial' ), elmercado_asset_version( '/assets/css/blog-discovery-010263.css' ) );
	wp_enqueue_script( 'elmercado-blog-discovery-010263', ELMERCADO_THEME_URL . '/assets/js/blog-discovery-010263.js', array(), elmercado_asset_version( '/assets/js/blog-discovery-010263.js' ), true );
}, 10120 );

add_action( 'admin_menu', static function (): void {
	add_submenu_page( 'edit.php', __( 'Destacados del blog', 'elmercadodeorigen' ), __( 'Destacados', 'elmercadodeorigen' ), 'edit_others_posts', 'emdo-blog-featured', 'elmercado_blog_featured_admin_page_010263' );
} );

function elmercado_blog_featured_admin_page_010263(): void {
	if ( ! current_user_can( 'edit_others_posts' ) ) { wp_die( esc_html__( 'No tienes permisos para gestionar los destacados.', 'elmercadodeorigen' ) ); }
	if ( isset( $_POST['emdo_blog_featured_save'] ) ) {
		check_admin_referer( 'emdo_blog_featured_save_010263' );
		$selected = isset( $_POST['featured_ids'] ) && is_array( $_POST['featured_ids'] ) ? array_values( array_unique( array_map( 'absint', wp_unslash( $_POST['featured_ids'] ) ) ) ) : array();
		$priorities = isset( $_POST['featured_priority'] ) && is_array( $_POST['featured_priority'] ) ? wp_unslash( $_POST['featured_priority'] ) : array();
		$old_ids = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_key' => '_emdo_blog_featured', 'meta_value' => '1' ) );
		foreach ( array_map( 'intval', (array) $old_ids ) as $post_id ) {
			if ( ! in_array( $post_id, $selected, true ) ) { delete_post_meta( $post_id, '_emdo_blog_featured' ); delete_post_meta( $post_id, '_emdo_blog_featured_priority' ); }
		}
		foreach ( $selected as $index => $post_id ) {
			if ( 'post' !== get_post_type( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { continue; }
			$priority = isset( $priorities[ $post_id ] ) ? absint( $priorities[ $post_id ] ) : ( $index + 1 );
			update_post_meta( $post_id, '_emdo_blog_featured', '1' );
			update_post_meta( $post_id, '_emdo_blog_featured_priority', max( 1, $priority ) );
		}
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Destacados actualizados.', 'elmercadodeorigen' ) . '</p></div>';
	}
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => array( 'publish', 'draft', 'future' ), 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Destacados del blog', 'elmercadodeorigen' ); ?></h1>
		<p><?php esc_html_e( 'Marca los artículos que quieras priorizar. En la portada del blog se muestran los tres con menor número de prioridad; si hay menos de tres, se completa con los artículos más recientes.', 'elmercadodeorigen' ); ?></p>
		<form method="post">
			<?php wp_nonce_field( 'emdo_blog_featured_save_010263' ); ?><input type="hidden" name="emdo_blog_featured_save" value="1" />
			<table class="widefat striped"><thead><tr><th style="width:90px"><?php esc_html_e( 'Destacado', 'elmercadodeorigen' ); ?></th><th><?php esc_html_e( 'Artículo', 'elmercadodeorigen' ); ?></th><th style="width:120px"><?php esc_html_e( 'Prioridad', 'elmercadodeorigen' ); ?></th><th style="width:180px"><?php esc_html_e( 'Categoría', 'elmercadodeorigen' ); ?></th></tr></thead><tbody>
			<?php foreach ( $posts as $post ) :
				$is_featured = '1' === (string) get_post_meta( $post->ID, '_emdo_blog_featured', true );
				$stored_priority = (int) get_post_meta( $post->ID, '_emdo_blog_featured_priority', true );
				$priority = $stored_priority > 0 ? $stored_priority : 99;
				$categories = get_the_category( $post->ID );
				$category = ! empty( $categories ) ? $categories[0]->name : '—'; ?>
				<tr><td><label><input type="checkbox" name="featured_ids[]" value="<?php echo esc_attr( (string) $post->ID ); ?>" <?php checked( $is_featured ); ?> /> <?php esc_html_e( 'Sí', 'elmercadodeorigen' ); ?></label></td><td><strong><a href="<?php echo esc_url( get_edit_post_link( $post->ID ) ); ?>"><?php echo esc_html( get_the_title( $post->ID ) ); ?></a></strong><br><small><?php echo esc_html( get_the_date( 'j M Y', $post->ID ) ); ?></small></td><td><input type="number" min="1" max="999" name="featured_priority[<?php echo esc_attr( (string) $post->ID ); ?>]" value="<?php echo esc_attr( (string) $priority ); ?>" style="width:84px" /></td><td><?php echo esc_html( $category ); ?></td></tr>
			<?php endforeach; ?>
			</tbody></table><?php submit_button( __( 'Guardar destacados', 'elmercadodeorigen' ) ); ?>
		</form>
	</div>
	<?php
}

add_filter( 'manage_post_posts_columns', static function ( array $columns ): array { $columns['emdo_blog_featured'] = __( 'Destacado', 'elmercadodeorigen' ); return $columns; } );
add_action( 'manage_post_posts_custom_column', static function ( string $column, int $post_id ): void {
	if ( 'emdo_blog_featured' !== $column ) { return; }
	if ( '1' !== (string) get_post_meta( $post_id, '_emdo_blog_featured', true ) ) { echo '—'; return; }
	$priority = max( 1, (int) get_post_meta( $post_id, '_emdo_blog_featured_priority', true ) );
	echo esc_html( '★ ' . $priority );
}, 10, 2 );
