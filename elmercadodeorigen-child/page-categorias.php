<?php
/**
 * Template de la página /categorias/.
 *
 * @package ElMercadoDeOrigen
 */

get_header();

$items = function_exists( 'elmercado_categories_hub_items_010257' )
	? elmercado_categories_hub_items_010257()
	: array();

$is_english = function_exists( 'elmercado_is_english_request_010245' ) && elmercado_is_english_request_010245();

$category_summaries = array(
	'jamones-paletas'     => 'Jamones y paletas de distintas calidades, alimentaciones, curaciones, formatos y presentaciones.',
	'embutidos-y-curados' => 'Chorizos, salchichones, lomos, lomitos, morcones, sobrasadas y otros embutidos y curados.',
	'aceites'             => 'Aceites de oliva de distintas variedades, procedencias, formatos y perfiles de sabor.',
	'carnes'              => 'Carnes en distintos cortes y formatos, desde piezas y hamburguesas hasta preparaciones para cocinar.',
	'packs-y-lotes'       => 'Packs y lotes que combinan distintos productos, tanto para regalar como para probar varias especialidades.',
	'accesorios'          => 'Accesorios y complementos para cortar, servir, conservar o presentar los productos.',
	'adobados'            => 'Carnes y otros productos adobados o marinados, con distintas preparaciones y formatos.',
);

$visible_items = array();
foreach ( $items as $item ) {
	$term_id = absint( $item['id'] ?? 0 );
	$slug    = sanitize_title( (string) ( $item['slug'] ?? '' ) );

	/* Mentta is an internal synchronization category and is never public. */
	if ( in_array( $slug, array( 'mentta', 'menta' ), true ) ) {
		continue;
	}

	if ( $is_english && $term_id > 0 ) {
		if ( '1' !== (string) get_term_meta( $term_id, '_en_US_published', true ) ) {
			continue;
		}

		$translated_name = trim( (string) get_term_meta( $term_id, '_en_US_name', true ) );
		if ( '' === $translated_name ) {
			/* Never leak the Spanish category name into the English catalogue. */
			continue;
		}

		$item['name'] = $translated_name;

		$translated_description = trim( wp_strip_all_tags( (string) get_term_meta( $term_id, '_en_US_description', true ) ) );
		if ( '' !== $translated_description ) {
			$item['summary'] = wp_trim_words( $translated_description, 34, '…' );
		} else {
			$format = function_exists( 'elmercado_ui_copy_010245' )
				? elmercado_ui_copy_010245( 'Explora la selección disponible de %s, con origen claro, productor visible y disponibilidad actual.' )
				: 'Explore the available %s selection, with clear origin, visible producer and current availability.';
			$item['summary'] = sprintf( $format, $translated_name );
		}

		$parent_id = absint( $item['parent'] ?? 0 );
		if ( $parent_id > 0 ) {
			$item['parent_name'] = trim( (string) get_term_meta( $parent_id, '_en_US_name', true ) );
		}
	} else {
		$name            = trim( (string) ( $item['name'] ?? '' ) );
		$item['summary'] = $category_summaries[ $slug ] ?? sprintf(
			'%s agrupados en una misma categoría para consultar toda la selección de forma sencilla.',
			$name
		);
	}

	$visible_items[] = $item;
}
$items = $visible_items;
?>

<main id="primary" class="site-main emo-categories-hub" data-emo-categories-hub="010257">
	<section class="emo-categories-hub__hero">
		<div class="emo-categories-hub__hero-inner">
			<span class="emo-kicker"><?php esc_html_e( 'Categorías de producto', 'elmercadodeorigen' ); ?></span>
			<h1><?php esc_html_e( 'Todas las categorías', 'elmercadodeorigen' ); ?></h1>
			<p><?php esc_html_e( 'Aquí encontrarás todos los productos agrupados por categorías. Entra en la que te interese para ver la selección completa.', 'elmercadodeorigen' ); ?></p>
		</div>
	</section>

	<section class="emo-categories-hub__content" aria-labelledby="emo-categories-hub-title">
		<header class="emo-categories-hub__intro">
			<div>
				<span class="emo-kicker"><?php esc_html_e( 'Todas las categorías', 'elmercadodeorigen' ); ?></span>
				<h2 id="emo-categories-hub-title"><?php esc_html_e( 'Elige una categoría', 'elmercadodeorigen' ); ?></h2>
			</div>
			<p><?php esc_html_e( 'Cada categoría reúne productos del mismo tipo para que puedas encontrarlos y compararlos más fácilmente.', 'elmercadodeorigen' ); ?></p>
		</header>

		<?php if ( $items ) : ?>
			<div class="emo-categories-hub__grid">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$count_value = max( 0, (int) ( $item['count'] ?? 0 ) );
					if ( $is_english && function_exists( 'elmercado_ui_copy_010245' ) ) {
						$count_format = elmercado_ui_copy_010245( 1 === $count_value ? '%s producto' : '%s productos' );
					} else {
						$count_format = _n( '%s producto', '%s productos', $count_value, 'elmercadodeorigen' );
					}
					$count_label = sprintf( $count_format, number_format_i18n( $count_value ) );
					$style = ! empty( $item['image'] )
						? '--emo-category-image:url(' . esc_url( (string) $item['image'] ) . ');'
						: '';
					?>
					<article class="emo-category-hub-card" data-category-slug="<?php echo esc_attr( (string) $item['slug'] ); ?>"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
						<a class="emo-category-hub-card__media" href="<?php echo esc_url( (string) $item['link'] ); ?>" aria-label="<?php echo esc_attr( (string) $item['name'] ); ?>"></a>
						<div class="emo-category-hub-card__body">
							<?php if ( ! empty( $item['parent_name'] ) ) : ?>
								<span class="emo-category-hub-card__parent"><?php echo esc_html( (string) $item['parent_name'] ); ?></span>
							<?php endif; ?>
							<h2><a href="<?php echo esc_url( (string) $item['link'] ); ?>"><?php echo esc_html( (string) $item['name'] ); ?></a></h2>
							<p class="emo-category-hub-card__summary"><?php echo esc_html( (string) $item['summary'] ); ?></p>
							<footer class="emo-category-hub-card__footer">
								<span class="emo-category-hub-card__count" data-category-count="<?php echo esc_attr( (string) $count_value ); ?>"><?php echo esc_html( $count_label ); ?></span>
								<a class="emo-category-hub-card__link" href="<?php echo esc_url( (string) $item['link'] ); ?>"><?php esc_html_e( 'Ver categoría', 'elmercadodeorigen' ); ?> <span aria-hidden="true">→</span></a>
							</footer>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="emo-categories-hub__empty">
				<h2><?php esc_html_e( 'No hay categorías para mostrar.', 'elmercadodeorigen' ); ?></h2>
			</div>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
