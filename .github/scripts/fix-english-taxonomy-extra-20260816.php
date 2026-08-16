<?php
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( 2 ); }
$root = rtrim( (string) $argv[1], '/' );
ini_set( 'memory_limit', '512M' );
require $root . '/wp-load.php';
$map = array(
    'cabra' => 'Goat',
    'codillo' => 'Pork knuckle',
    'curado' => 'Cured',
    'dop-los-pedroches' => 'Los Pedroches PDO',
    'ecologico' => 'Organic',
    'fresco' => 'Fresh',
    'oveja' => 'Sheep',
    'queso' => 'Cheese',
    'reserva' => 'Reserve',
    'semicurado' => 'Semi-cured',
    'sin-lactosa' => 'Lactose-free',
    'taco' => 'Chunk',
    'tierno' => 'Young',
    'vaca' => 'Cow',
    'virutas' => 'Shavings',
);
$backup = array();
foreach ( $map as $slug => $english ) {
    $term = get_term_by( 'slug', $slug, 'product_tag' );
    if ( ! $term instanceof WP_Term ) { echo "MISSING|{$slug}\n"; continue; }
    $old = (string) get_term_meta( $term->term_id, '_en_US_name', true );
    $new = '<span data-no-translation>' . esc_html( $english ) . '</span>';
    if ( $old === $new ) { continue; }
    $backup[] = array( 'term_id' => (int) $term->term_id, 'old' => $old );
    update_term_meta( $term->term_id, '_en_US_name', $new );
    echo "UPDATED|{$term->term_id}|{$slug}|{$english}\n";
}
$dir = $root . '/wp-content/uploads/mdo-backups';
if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
$path = $dir . '/english-taxonomy-extra-' . gmdate( 'YmdHis' ) . '.json';
file_put_contents( $path, wp_json_encode( $backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
echo "BACKUP={$path}\n";
