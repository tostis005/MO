<?php
/**
 * Productos del blog: rejilla 4 → 2 → 1 y enlace dinámico de categoría 0.10.267.
 *
 * Mantiene la apariencia global de las tarjetas de WooCommerce, limita el
 * ajuste a los productos insertados dentro de entradas y añade un CTA debajo
 * de los shortcodes de producto que declaran una categoría concreta.
 *
 * @package ElMercadoDeOrigen
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Añade un enlace a la categoría real usada por un shortcode de productos.
 *
 * @param string       $output Salida HTML del shortcode.
 * @param string       $tag    Nombre del shortcode.
 * @param array|string $attr   Atributos resueltos.
 * @param array        $match  Coincidencia original del shortcode.
 * @return string
 */
function elmercado_blog_product_category_more_link( $output, $tag, $attr, $match ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
	if ( is_admin() || ! is_singular( 'post' ) || ! in_array( $tag, array( 'products', 'product_category' ), true ) ) {
		return $output;
	}

	if ( ! is_array( $attr ) || empty( $attr['category'] ) ) {
		return $output;
	}

	$category_slugs = array_values(
		array_filter(
			array_map(
				'sanitize_title',
				array_map( 'trim', explode( ',', (string) $attr['category'] ) )
			)
		)
	);

	/* Un CTA de categoría sólo es inequívoco cuando el bloque usa una categoría. */
	if ( 1 !== count( $category_slugs ) ) {
		return $output;
	}

	$term = get_term_by( 'slug', $category_slugs[0], 'product_cat' );
	if ( ! $term instanceof WP_Term ) {
		return $output;
	}

	$term_link = get_term_link( $term );
	if ( is_wp_error( $term_link ) ) {
		return $output;
	}

	$locale = function_exists( 'determine_locale' ) ? determine_locale() : get_locale();
	$label  = 0 === strpos( strtolower( (string) $locale ), 'en' )
		? sprintf( 'View all products in %s', $term->name )
		: sprintf( 'Ver todos los productos de %s', $term->name );

	$cta = sprintf(
		'<div class="emo-product-category-more"><a class="emo-product-category-more__link" href="%1$s">%2$s <span aria-hidden="true">→</span></a></div>',
		esc_url( $term_link ),
		esc_html( $label )
	);

	return $output . $cta;
}
add_filter( 'do_shortcode_tag', 'elmercado_blog_product_category_more_link', 20, 4 );

add_action(
	'wp_footer',
	static function (): void {
		if ( is_admin() || ! is_singular( 'post' ) ) {
			return;
		}
		?>
		<style id="elmercado-blog-product-category-more-010267">
			/* Escritorio: cuatro productos en una única fila cuando el bloque contiene cuatro. */
			html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
				grid-template-columns: repeat(4, minmax(0, 1fr)) !important;
			}

			html body.single-post main#primary.emo-article-page .emo-product-category-more {
				display: flex;
				justify-content: flex-end;
				width: min(1040px, calc(100vw - 80px));
				max-width: 1040px;
				margin: -26px 50% 42px;
				padding: 0;
				transform: translateX(-50%);
				box-sizing: border-box;
			}

			html body.single-post main#primary.emo-article-page .emo-product-category-more__link {
				display: inline-flex;
				align-items: center;
				gap: 0.42rem;
				font-weight: 700;
				line-height: 1.35;
				text-decoration: none;
			}

			html body.single-post main#primary.emo-article-page .emo-product-category-more__link:hover,
			html body.single-post main#primary.emo-article-page .emo-product-category-more__link:focus-visible {
				text-decoration: underline;
				text-underline-offset: 0.18em;
			}

			/* Tablet y portátil estrecho: dos columnas. */
			@media (max-width: 1100px) {
				html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
					grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
				}

				html body.single-post main#primary.emo-article-page .emo-product-category-more {
					width: 100%;
					max-width: 100%;
					margin: -18px 0 34px;
					transform: none;
				}
			}

			/* Móvil: una columna. */
			@media (max-width: 620px) {
				html body.single-post main#primary.emo-article-page .emo-article-content :is(.woocommerce ul.products, ul.products, .wc-block-grid__products, .wc-block-product-template) {
					grid-template-columns: minmax(0, 1fr) !important;
				}

				html body.single-post main#primary.emo-article-page .emo-product-category-more {
					justify-content: center;
					margin-top: -14px;
				}
			}
		</style>
		<?php
	},
	PHP_INT_MAX
);
