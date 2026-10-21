<?php
/**
 * Pruebas aisladas del formateador de precios del buscador incremental.
 * Ejecutar: php tests/quick-search-price-20261021.php
 */
declare(strict_types=1);

define( 'ABSPATH', __DIR__ );
function add_action( ...$args ): void {}
function wp_strip_all_tags( string $html ): string { return strip_tags( $html ); }
function wc_price( float $value ): string {
	return '<span class="amount">' . number_format( $value, 2, ',', '.' ) . '&nbsp;€</span>';
}
function wc_get_price_to_display( WC_Product $product, array $args ): float {
	return (float) $args['price'];
}

class WC_Product {
	public function __construct(
		private string $type,
		private $low = '',
		private $high = '',
		private $price = '',
		private string $html = ''
	) {}
	public function is_type( string $type ): bool { return $type === $this->type; }
	public function get_variation_price( string $mode, bool $display ): mixed {
		return 'min' === $mode ? $this->low : $this->high;
	}
	public function get_price(): mixed { return $this->price; }
	public function get_price_html(): string { return $this->html; }
}

require dirname( __DIR__ ) . '/mu-plugins/mdo-quick-search-20261021.php';

$cases = array(
	array( new WC_Product( 'variable', 12.5, 24.9 ), '12,50 € – 24,90 €' ),
	array( new WC_Product( 'variable', 19.5, 19.5 ), '19,50 €' ),
	array( new WC_Product( 'simple', '', '', '9.50' ), '9,50 €' ),
	array( new WC_Product( 'simple', '', '', '0' ), '0,00 €' ),
	array(
		new WC_Product(
			'grouped',
			'',
			'',
			'',
			'<span class="screen-reader-text">Rango de precios: desde 4 hasta 8</span><span>4 € – 8 €</span>'
		),
		'4 € – 8 €',
	),
);
foreach ( $cases as $index => $case ) {
	$actual = elmercado_quick_search_price_20261021( $case[0] );
	if ( $case[1] !== $actual ) {
		fwrite( STDERR, 'CASE ' . $index . ' expected ' . json_encode( $case[1] ) . ', got ' . json_encode( $actual ) . PHP_EOL );
		exit( 1 );
	}
}
echo "QUICK_SEARCH_PRICE_FORMAT_OK 5 cases\n";
