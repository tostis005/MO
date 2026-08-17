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

$editorial_summaries = array(
	'jamones-paletas'      => 'Una categoría dedicada a jamones y paletas, con distintas curaciones, cortes y formatos que reflejan el carácter de cada origen y el saber hacer de sus productores.',
	'embutidos-y-curados'  => 'Tradición, tiempo y materia prima se encuentran en una selección de embutidos y curados con diferentes estilos, curaciones y formas de elaboración.',
	'aceites'              => 'Aceites de oliva con distintos perfiles, variedades y matices, una forma de descubrir cómo cambian el aroma y el sabor según su origen y elaboración.',
	'carnes'               => 'Cortes y elaboraciones cárnicas con diferentes formatos y preparaciones, seleccionados para poner en valor la calidad de la materia prima y su procedencia.',
	'packs-y-lotes'        => 'Selecciones que reúnen diferentes sabores y elaboraciones en una misma propuesta, pensadas para descubrir, compartir o regalar.',
	'accesorios'           => 'Complementos pensados para servir, conservar y disfrutar mejor los productos, cuidando también los pequeños detalles alrededor de cada experiencia.',
	'adobados'             => 'Preparaciones en las que especias, marinados y recetas tradicionales aportan carácter y una forma distinta de disfrutar cada corte.',
	'mentta'               => 'Una selección organizada por familias y elaboraciones, con el origen y el productor como referencia en cada propuesta.',
);

foreach ( $items as &$item ) {
	$slug = sanitize_title( (string) ( $item['slug'] ?? '' ) );
	$name = trim( (string) ( $item['name'] ?? '' ) );
	$item['summary'] = $editorial_summaries[ $slug ] ?? sprintf(
		'Una categoría para descubrir distintas propuestas de %s, poniendo el foco en su origen, su elaboración y los productores que hay detrás.',
		$name
	);
}
unset( $item );

$count = count( $items );
?>

<main id="primary" class="site-main emo-categories-hub" data-emo-categories-hub="010257">
	<section class="emo-categories-hub__hero">
		<div class="emo-categories-hub__hero-inner">
			<span class="emo-kicker"><?php esc_html_e( 'Explora el mercado', 'elmercadodeorigen' ); ?></span>
			<h1><?php esc_html_e( 'Todas las categorías', 'elmercadodeorigen' ); ?></h1>
			<p><?php esc_html_e( 'Una forma sencilla y visual de recorrer El Mercado de Origen por familias, descubrir distintas elaboraciones y acercarte a cada categoría a través de su procedencia, su tradición y sus productores.', 'elmercadodeorigen' ); ?></p>
			<span class="emo-categories-hub__stat">
				<?php
				echo esc_html(
					sprintf(
						_n( '%s categoría con productos', '%s categorías con productos', $count, 'elmercadodeorigen' ),
						number_format_i18n( $count )
					)
				);
				?>
			</span>
		</div>
	</section>

	<section class="emo-categories-hub__content" aria-labelledby="emo-categories-hub-title">
		<header class="emo-categories-hub__intro">
			<div>
				<span class="emo-kicker"><?php esc_html_e( 'Elige por familia', 'elmercadodeorigen' ); ?></span>
				<h2 id="emo-categories-hub-title"><?php esc_html_e( 'Encuentra lo que buscas de un vistazo', 'elmercadodeorigen' ); ?></h2>
			</div>
			<p><?php esc_html_e( 'Cada familia reúne sabores, elaboraciones y estilos propios para que puedas explorar el mercado de una forma clara, visual y sencilla.', 'elmercadodeorigen' ); ?></p>
		</header>

		<?php if ( $items ) : ?>
			<div class="emo-categories-hub__grid">
				<?php foreach ( $items as $item ) : ?>
					<?php
					$count_value = max( 0, (int) ( $item['count'] ?? 0 ) );
					$count_label = sprintf(
						_n( '%s producto', '%s productos', $count_value, 'elmercadodeorigen' ),
						number_format_i18n( $count_value )
					);
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
								<a class="emo-category-hub-card__link" href="<?php echo esc_url( (string) $item['link'] ); ?>"><?php esc_html_e( 'Explorar', 'elmercadodeorigen' ); ?> <span aria-hidden="true">→</span></a>
							</footer>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php else : ?>
			<div class="emo-categories-hub__empty">
				<h2><?php esc_html_e( 'Estamos preparando nuevas categorías.', 'elmercadodeorigen' ); ?></h2>
				<p><?php esc_html_e( 'Vuelve pronto para descubrir nuevas selecciones y productores.', 'elmercadodeorigen' ); ?></p>
			</div>
		<?php endif; ?>
	</section>
</main>

<?php
get_footer();
