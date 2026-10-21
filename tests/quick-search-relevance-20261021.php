<?php
/**
 * Contrato de relevancia del buscador: título > descripción corta.
 *
 * Se ejecuta sin WordPress con: php tests/quick-search-relevance-20261021.php
 */
declare(strict_types=1);
define( 'ABSPATH', __DIR__ );
function add_action( ...$args ): void {}
function add_filter( ...$args ): void {}

class WP_Query {
	public function __construct( private array $vars ) {}
	public function get( string $name ): mixed { return $this->vars[$name] ?? null; }
}
class FakeQuickSearchDb {
	public string $posts = 'wp_posts';
	public function esc_like( string $text ): string {
		return addcslashes( $text, '_%\\' );
	}
	public function prepare( string $sql, ...$arguments ): string {
		$index = 0;
		return (string) preg_replace_callback(
			'/%s/',
			static function () use ( &$index, $arguments ): string {
				return "'" . str_replace( "'", "''", (string) $arguments[$index++] ) . "'";
			},
			$sql
		);
	}
}
$wpdb = new FakeQuickSearchDb();

require dirname( __DIR__ ) . '/mu-plugins/mdo-quick-search-20261021.php';

function check( bool $condition, string $message ): void {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL $message\n" );
		exit( 1 );
	}
}

$args = elmercado_quick_search_query_args_20261021(
	'mango',
	array( array( 'taxonomy' => 'product_visibility', 'operator' => 'NOT IN' ) )
);
check( $args['search_columns'] === array( 'post_title', 'post_excerpt' ), 'solo nombre y descripción corta' );
check( ! in_array( 'post_content', $args['search_columns'], true ), 'sin descripción larga' );
check( $args['emo_quick_search_20261021'] === true, 'ordenación limitada al autocomplete' );
check( $args['posts_per_page'] === 12 && $args['no_found_rows'] === true, 'consulta acotada' );
check( count( $args['tax_query'] ) === 1, 'conserva exclusiones del catálogo' );

$orderby = elmercado_quick_search_orderby_20261021( 'original', new WP_Query( $args ) );
$exact = strpos( $orderby, 'post_title = ' );
$starts = strpos( $orderby, 'post_title LIKE ' );
$contains = strpos( $orderby, 'post_title LIKE ', (int) $starts + 1 );
$excerpt = strpos( $orderby, 'post_excerpt LIKE ' );
check( false !== $exact && false !== $starts && false !== $contains && false !== $excerpt, 'todas las prioridades' );
check( $exact < $starts && $starts < $contains && $contains < $excerpt, 'título prioritario antes de descripción corta' );
check( strpos( $orderby, 'post_content' ) === false, 'descripción larga ausente de ordenación' );
check( str_contains( $orderby, "mango%" ), 'coincidencia por prefijo' );

$other = elmercado_quick_search_orderby_20261021(
	'original',
	new WP_Query( array( 's' => 'mango' ) )
);
check( $other === 'original', 'Enter y otras búsquedas mantienen su ordenación' );

$special = $args;
$special['s'] = "mango_%'test";
$escaped = elmercado_quick_search_orderby_20261021( 'original', new WP_Query( $special ) );
check( str_contains( $escaped, "mango\\_\\%" ), 'LIKE escapa comodines del usuario' );
check( str_contains( $escaped, "''test" ), 'consulta prepara comillas de forma segura' );

echo "QUICK_SEARCH_RELEVANCE_OK 9 assertions\n";
