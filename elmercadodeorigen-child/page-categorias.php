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

/* UI copy is read from the persisted wp_options map; nothing is translated in the browser. */
$ui_copy = static function ( string $source ) use ( $is_english ): string {
	if ( $is_english && function_exists( 'elmercado_ui_copy_010245' ) ) {
		return elmercado_ui_copy_010245( $source );
	}
	return $source;
};

/* Persisted multilingual metadata can contain encoded HTML. Decode first, then strip tags. */
$clean_persisted_text = static function ( $value ): string {
	$value   = (string) $value;
	$charset = get_bloginfo( 'charset' ) ?: 'UTF-8';
	for ( $pass = 0; $pass < 2; $pass++ ) {
		$decoded = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, $charset );
		if ( $decoded === $value ) {
			break;
		}
		$value = $decoded;
	}
	$value = wp_strip_all_tags( $value, true );
	$value = (string) preg_replace( '/\s+/u', ' ', $value );
	return trim( $value );
};

/* Spanish editorial source of truth for the category cards. */
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
	$term_id   = absint( $item['id'] ?? 0 );
	$slug      = sanitize_title( (string) ( $item['slug'] ?? '' ) );
	$parent_id = absint( $item['parent'] ?? 0 );

	/* The public hub is intentionally a top-level catalogue: never show subcategories. */
	if ( $parent_id > 0 ) {
		continue;
	}

	/* Mentta/Menta is an internal synchronization category and is never public. */
	if ( in_array( $slug, array( 'mentta', 'menta' ), true ) ) {
		continue;
	}

	if ( $is_english && $term_id > 0 ) {
		if ( '1' !== (string) get_term_meta( $term_id, '_en_US_published', true ) ) {
			continue;
		}

		$translated_name = $clean_persisted_text( get_term_meta( $term_id, '_en_US_name', true ) );
		if ( '' === $translated_name ) {
			/* Never leak the Spanish category name into the English catalogue. */
			continue;
		}
		$item['name'] = $translated_name;

		/* Exact translation of the Spanish card copy, persisted per term in the database. */
		$translated_summary = $clean_persisted_text( get_term_meta( $term_id, '_emdo_en_hub_summary', true ) );
		if ( '' !== $translated_summary ) {
			$item['summary'] = $translated_summary;
		} else {
			/* Safe fallback: do not invent an English marketing sentence. */
			$item['summary'] = '';
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

<main id="primary" class="site-main emo-categories-hub" data-emo-categories-hub="010259">
	<section class="emo-categories-hub__hero">
		<div class="emo-categories-hub__hero-inner">
			<span class="emo-kicker"><?php echo esc_html( $ui_copy( 'Categorías de producto' ) ); ?></span>
			<h1><?php echo esc_html( $ui_copy( 'Todas las categorías' ) ); ?></h1>
			<p><?php echo esc_html( $ui_copy( 'Aquí encontrarás todos los productos agrupados por categorías. Entra en la que te interese para ver la selección completa.' ) ); ?></p>
		</div>
	</section>

	<section class="emo-categories-hub__content" aria-labelledby="emo-categories-hub-title">
		<header class="emo-categories-hub__intro">
			<div>
				<span class="emo-kicker"><?php echo esc_html( $ui_copy( 'Todas las categorías' ) ); ?></span>
				<h2 id="emo-categories-hub-title"><?php echo esc_html( $ui_copy( 'Elige una categoría' ) ); ?></h2>
			</div>
			<p><?php echo esc_html( $ui_copy( 'Cada categoría reúne productos del mismo tipo para que puedas encontrarlos y compararlos más fácilmente.' ) ); ?></p>
		</header>

		<?php if ( $items ) : ?>
			<div class="emo-categories-hub__grid">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$count_value = max( 0, (int) ( $item['count'] ?? 0 ) );
					if ( $is_english ) {
						$count_format = $ui_copy( 1 === $count_value ? '%s producto' : '%s productos' );
					} else {
						$count_format = _n( '%s producto', '%s productos', $count_value, 'elmercadodeorigen' );
					}
					$count_label = sprintf( $count_format, number_format_i18n( $count_value ) );
					$style       = ! empty( $item['image'] )
						? '--emo-category-image:url(' . esc_url( (string) $item['image'] ) . ');'
						: '';
					?>
					<article class="emo-category-hub-card" data-category-slug="<?php echo esc_attr( (string) $item['slug'] ); ?>"<?php echo $style ? ' style="' . esc_attr( $style ) . '"' : ''; ?>>
						<a class="emo-category-hub-card__media" href="<?php echo esc_url( (string) $item['link'] ); ?>" aria-label="<?php echo esc_attr( (string) $item['name'] ); ?>"></a>
						<div class="emo-category-hub-card__body">
							<h2><a href="<?php echo esc_url( (string) $item['link'] ); ?>"><?php echo esc_html( (string) $item['name'] ); ?></a></h2>
							<?php if ( ! empty( $item['summary'] ) ) : ?>
								<p class="emo-category-hub-card__summary"><?php echo esc_html( (string) $item['summary'] ); ?></p>
							<?php endif; ?>
							<footer class="emo-category-hub-card__footer">
								<span class="emo-category-hub-card__count" data-category-count="<?php echo esc_attr( (string) $count_value ); ?>"><?php echo esc_html( $count_label ); ?></span>
								<a class="emo-category-hub-card__link" href="<?php echo esc_url( (string) $item['link'] ); ?>"><?php echo esc_html( $ui_copy( 'Ver categoría' ) ); ?> <span aria-hidden="true">→</span></a>
							</footer>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="emo-categories-hub__empty">
				<h2><?php echo esc_html( $ui_copy( 'No hay categorías para mostrar.' ) ); ?></h2>
			</div>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
