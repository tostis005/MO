<?php
/**
 * Core-only production seed for persisted English storefront UI copy.
 * Intended for WP-CLI with --skip-plugins --skip-themes.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit( 1 );
}

$option = 'elmercado_en_ui_copy_010245';
$version_option = 'elmercado_en_ui_copy_version_010245';
$version = '2026-08-18.2';
$defaults = array(
	'Buscar' => 'Search',
	'Filtros' => 'Filters',
	'Filtrar productos' => 'Filter products',
	'Cerrar filtros' => 'Close filters',
	'Filtros activos' => 'Active filters',
	'Filtros aplicados' => 'Applied filters',
	'Limpiar todo' => 'Clear all',
	'Categorías' => 'Categories',
	'Vendedor' => 'Seller',
	'Precio' => 'Price',
	'Recomendados' => 'Recommended',
	'Más populares' => 'Most popular',
	'Mejor valorados' => 'Top rated',
	'Más recientes' => 'Newest',
	'Menor precio' => 'Lowest price',
	'Mayor precio' => 'Highest price',
	'VISITAR' => 'VISIT',
	'Visitar' => 'Visit',
	'TU SELECCIÓN' => 'YOUR SELECTION',
	'Revisa tu carrito' => 'Review your cart',
	'Comprueba cantidades y productos antes de continuar. Verás el coste final y las opciones disponibles en el siguiente paso.' => 'Check quantities and products before continuing. You’ll see the final cost and available options in the next step.',
	'Pago protegido durante todo el proceso' => 'Secure payment throughout the process',
	'Información clara antes de confirmar' => 'Clear information before you confirm',
	'Atención cercana si necesitas ayuda' => 'Personal support if you need help',
	'Alimentación' => 'Feeding',
	'Calidad' => 'Quality',
	'Con DOP' => 'With PDO',
	'Curación' => 'Curing',
	'Denominación de origen' => 'Protected Designation of Origin',
	'Origen' => 'Origin',
	'Peso' => 'Weight',
	'Preparación' => 'Preparation',
	'Productor' => 'Producer',
	'Raza ibérica' => 'Iberian breed',
	'Tamaño' => 'Size',
	'Tipo de pieza' => 'Piece type',
	'Tipo de producto' => 'Product type',
	'Variedad' => 'Variety',
	'Compra por categoría' => 'Shop by category',
	'Encuentra lo que buscas por categoría' => 'Find what you are looking for by category',
	'Hemos agrupado los productos por categorías para que puedas encontrar fácilmente el tipo de producto que buscas.' => 'We have grouped products by category so you can easily find the type of product you are looking for.',
	'Ver todas las categorías' => 'View all categories',
	'Categorías de producto' => 'Product categories',
	'Todas las categorías' => 'All categories',
	'Aquí encontrarás todos los productos agrupados por categorías. Entra en la que te interese para ver la selección completa.' => 'Here you will find all products grouped by category. Open the one you are interested in to see the full selection.',
	'Elige una categoría' => 'Choose a category',
	'Cada categoría reúne productos del mismo tipo para que puedas encontrarlos y compararlos más fácilmente.' => 'Each category brings together products of the same type so you can find and compare them more easily.',
	'Ver categoría' => 'View category',
	'No hay categorías para mostrar.' => 'There are no categories to show.',
	'%s producto' => '%s product',
	'%s productos' => '%s products',
	'Explora la selección disponible de %s, con origen claro, productor visible y disponibilidad actual.' => 'Explore the available %s selection, with clear origin, visible producer and current availability.',
);

$stored = get_option( $option, array() );
if ( ! is_array( $stored ) ) {
	$stored = array();
}
update_option( $option, array_merge( $defaults, $stored ), false );
update_option( $version_option, $version, false );
echo "ENGLISH_UI_COPY_SEEDED\n";
