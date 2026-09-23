<?php
/**
 * Append adapted preparation/product information to the six Tolecarnes ready-meal products
 * created on 2026-09-23.
 *
 * Safety:
 * - targets six exact product IDs and identities only;
 * - preserves the existing description verbatim at the beginning;
 * - changes post_content only;
 * - creates a persistent backup before the first write;
 * - is idempotent and verifies every product after writing.
 */
if ( ! defined( 'ABSPATH' ) ) {
    fwrite( STDERR, "ABORT: WordPress not loaded\n" );
    exit( 2 );
}
if ( ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ABORT: WooCommerce unavailable\n" );
    exit( 3 );
}

function emdo_tc_ready_fail( string $message, int $code = 10 ): void {
    fwrite( STDERR, "ABORT: {$message}\n" );
    exit( $code );
}

$specs = [
    14664 => [
        'title'      => 'Callos a la madrileña',
        'slug'       => 'callos-a-la-madrilena',
        'sku'        => '/callos-a-la-madrilena',
        'source_url' => 'https://tolecarnes.com/producto/callos-madrilena/',
        'old'        => 'Precio por cazuelita. Tratamiento culinario de callos, morros y patas de vacuno. SIN ADITIVOS NI CONSERVANTES',
        'addition'   => <<<'HTML'
<!-- emdo-tolecarnes-ready-info-20260923 -->
<h2>Información del producto</h2>
<p>Un plato preparado de inspiración tradicional, elaborado con callos, pata y morro de vacuno, acompañado de chorizo y morcilla y cocinado con cebolla, ajo, vino, aceite de oliva, especias y plantas aromáticas.</p>
<p><strong>Ingredientes:</strong> callos de vacuno, patas de vacuno, morros de vacuno, chorizo, morcilla, cebolla, ajo, especias, plantas aromáticas, vino, sal, aceite de oliva y harina de trigo.</p>
<p><strong>Formato:</strong> envase CPET tipo skin de 400 g, equivalente a 1 ración. La cazuelita es apta para microondas y horno.</p>
<p><strong>Conservación:</strong> mantener refrigerado entre 0 y 5 °C. Una vez abierto el envase, consumir en un máximo de 48 horas.</p>
<p><strong>Preparación:</strong> retirar el plástico protector y calentar aproximadamente 2 minutos en el microondas a máxima potencia o unos 10 minutos en el horno a 190 °C.</p>
<p><strong>Alérgenos:</strong> contiene harina de trigo.</p>
HTML,
    ],
    14666 => [
        'title'      => 'Albondigas de la abuela',
        'slug'       => 'albondigas-de-la-abuela',
        'sku'        => '/albondigasdelaabuela',
        'source_url' => 'https://tolecarnes.com/producto/albondigas-abuela/',
        'old'        => 'Precio por cazuelita. Tratamiento culinario de carne picada y condimentada de vacuno y porcino. SIN CONSERVANTES NI ADITIVOS',
        'addition'   => <<<'HTML'
<!-- emdo-tolecarnes-ready-info-20260923 -->
<h2>Información del producto</h2>
<p>Albóndigas preparadas con carne de vacuno y cerdo y una salsa cocinada con verduras, vino, especias y plantas aromáticas. Un plato listo para calentar y servir.</p>
<p><strong>Ingredientes:</strong> carne de vacuno y cerdo, pan, leche, huevo, sal, aceite, ajo, vino, especias, plantas aromáticas, cebolla, zanahoria y tomate concentrado.</p>
<p><strong>Formato:</strong> envase CPET tipo skin de 300 g, equivalente a 1 ración. La cazuelita es apta para microondas y horno.</p>
<p><strong>Conservación:</strong> mantener refrigerado entre 0 y 5 °C. Una vez abierto el envase, consumir en un máximo de 48 horas.</p>
<p><strong>Preparación:</strong> retirar el plástico protector y calentar aproximadamente 2 minutos en el microondas a máxima potencia o unos 10 minutos en el horno a 190 °C.</p>
<p><strong>Alérgenos:</strong> contiene harina de trigo, leche y huevo.</p>
HTML,
    ],
    14668 => [
        'title'      => 'Carrillada de vacuno estofada',
        'slug'       => 'carrillada-de-vacuno-estofada',
        'sku'        => '/carrillada-de-vacuno-estofada',
        'source_url' => 'https://tolecarnes.com/producto/carrillada-vacuno/',
        'old'        => 'Precio por cazuelita. Tratamiento culinario de la carrillada de vacuno estofada. SIN CONSERVANTES NI ADITIVOS.',
        'addition'   => <<<'HTML'
<!-- emdo-tolecarnes-ready-info-20260923 -->
<h2>Información del producto</h2>
<p>Carrillada de vacuno estofada lentamente con una base de cebolla, zanahoria, ajo y vino, acompañada de aceite de oliva, especias y plantas aromáticas. Está pensada para calentar y servir directamente.</p>
<p><strong>Ingredientes:</strong> carrillada de vacuno, aceite de oliva, cebolla, vino, zanahoria, especias, ajo, plantas aromáticas y sal.</p>
<p><strong>Formato:</strong> envase CPET tipo skin de 500 g, para aproximadamente 1-2 raciones. La cazuelita es apta para microondas y horno.</p>
<p><strong>Preparación:</strong> retirar el plástico protector y calentar aproximadamente 2 minutos en el microondas a máxima potencia o unos 10 minutos en el horno a 190 °C.</p>
<p><strong>Alérgenos:</strong> puede contener trazas de gluten.</p>
HTML,
    ],
    14670 => [
        'title'      => 'Rabo estofado',
        'slug'       => 'rabo-estofado',
        'sku'        => '/rabo-estofado',
        'source_url' => 'https://tolecarnes.com/producto/rabo-estofado/',
        'old'        => 'Precio por cazuelita. Tratamiento culinario del rabo de vacuno, SIN CONSERVANTES NI ADITIVOS.',
        'addition'   => <<<'HTML'
<!-- emdo-tolecarnes-ready-info-20260923 -->
<h2>Información del producto</h2>
<p>Rabo de vacuno estofado con una salsa elaborada a base de vino, cebolla, zanahoria, ajo, tomate concentrado, especias y plantas aromáticas. Un plato preparado para calentar y servir.</p>
<p><strong>Ingredientes:</strong> rabo de vacuno, aceite de oliva, ajo, vino, especias, plantas aromáticas, tomate concentrado, cebolla, zanahoria, harina de trigo y sal.</p>
<p><strong>Formato:</strong> envase CPET tipo skin de 500 g, para aproximadamente 1-2 raciones. La cazuelita es apta para microondas y horno.</p>
<p><strong>Conservación:</strong> mantener refrigerado entre 0 y 5 °C. Una vez abierto el envase, consumir en un máximo de 48 horas.</p>
<p><strong>Preparación:</strong> retirar el plástico protector y calentar aproximadamente 2 minutos en el microondas a máxima potencia o unos 10 minutos en el horno a 190 °C.</p>
<p><strong>Alérgenos:</strong> contiene harina de trigo.</p>
HTML,
    ],
    14672 => [
        'title'      => 'Estofado de la abuela',
        'slug'       => 'estofado-de-la-abuela',
        'sku'        => '/estofado-de-la-abuela',
        'source_url' => 'https://tolecarnes.com/producto/carne-estofada/',
        'old'        => 'Precio por cazuelita. Tratamiento culinario de carne troceada de vacuno, SIN CONSERVANTES NI ADITIVOS.',
        'addition'   => <<<'HTML'
<!-- emdo-tolecarnes-ready-info-20260923 -->
<h2>Información del producto</h2>
<p>Estofado preparado con carne de vacuno troceada y cocinada con vino, cebolla, ajo, zanahoria, tomate y especias. Una opción lista para calentar y servir sin necesidad de cocinar el guiso desde cero.</p>
<p><strong>Ingredientes:</strong> carne de vacuno, aceite de oliva, vino, cebolla, ajo, zanahoria, agua, especias, tomate concentrado y sal.</p>
<p><strong>Formato:</strong> envase CPET tipo skin de 300 g, equivalente a 1 ración. La cazuelita es apta para microondas y horno.</p>
<p><strong>Preparación:</strong> retirar el plástico protector y calentar aproximadamente 2 minutos en el microondas a máxima potencia o unos 10 minutos en el horno a 190 °C.</p>
<p><strong>Alérgenos:</strong> puede contener trazas de gluten.</p>
HTML,
    ],
    14674 => [
        'title'      => 'Costilla de ternera',
        'slug'       => 'costilla-de-ternera',
        'sku'        => '259186',
        'source_url' => 'https://tolecarnes.com/producto/costilla-de-ternera/',
        'old'        => 'Precio por cazuela. Tratamiento culinario de carne de vacuno, SIN CONSERVANTES NI ADITIVOS.',
        'addition'   => <<<'HTML'
<!-- emdo-tolecarnes-ready-info-20260923 -->
<h2>Información del producto</h2>
<p>Costilla de ternera asada con salsa BBQ, preparada de forma artesanal y pensada para calentar y servir. Se presenta como una ración individual de 450 g.</p>
<p><strong>Ingredientes:</strong> costilla de ternera, pimentón de la Vera, aceite de girasol, especias BBQ, sal, azúcares, dextrosa, aroma y salsa BBQ elaborada con tomate, tomate concentrado, vinagre, azúcar, azúcar caramelizado, especias, sal, aroma de humo, aromas, jarabe de glucosa, almidón modificado, xantana y E202.</p>
<p><strong>Formato:</strong> envase de 450 g, equivalente a 1 ración.</p>
<p><strong>Preparación:</strong> plato cocinado y listo para calentar y servir.</p>
HTML,
    ],
];

if ( 6 !== count( $specs ) ) {
    emdo_tc_ready_fail( 'expected exactly six target specs', 4 );
}

$before = [];
$plan = [];

foreach ( $specs as $id => $spec ) {
    $post = get_post( $id );
    if ( ! $post || 'product' !== $post->post_type || 'publish' !== $post->post_status ) {
        emdo_tc_ready_fail( "product {$id} missing or not published", 5 );
    }

    $product = wc_get_product( $id );
    if ( ! $product || ! $product->is_type( 'simple' ) ) {
        emdo_tc_ready_fail( "product {$id} is not a simple WooCommerce product", 6 );
    }

    $user = get_userdata( (int) $post->post_author );
    $vendor = $user ? (string) $user->display_name : '';
    $source_url = (string) get_post_meta( $id, '_emdo_source_url', true );
    $supplier_id = absint( get_post_meta( $id, '_emdo_supplier_id', true ) );
    $sku = (string) $product->get_sku();

    if (
        $post->post_title !== $spec['title']
        || $post->post_name !== $spec['slug']
        || $sku !== $spec['sku']
        || $source_url !== $spec['source_url']
        || 1 !== $supplier_id
        || false === stripos( $vendor, 'tolecarnes' )
    ) {
        emdo_tc_ready_fail(
            "identity mismatch for product {$id}: title={$post->post_title}; slug={$post->post_name}; sku={$sku}; supplier={$supplier_id}; vendor={$vendor}; source={$source_url}",
            7
        );
    }

    $new_content = $spec['old'] . "\n\n" . trim( $spec['addition'] );
    $current = (string) $post->post_content;
    if ( $current !== $spec['old'] && $current !== $new_content ) {
        emdo_tc_ready_fail( "content for product {$id} changed since audit; refusing to overwrite", 8 );
    }

    $before[ $id ] = [
        'id'         => $id,
        'title'      => (string) $post->post_title,
        'slug'       => (string) $post->post_name,
        'status'     => (string) $post->post_status,
        'author'     => (int) $post->post_author,
        'excerpt'    => (string) $post->post_excerpt,
        'content'    => $current,
        'sku'        => $sku,
        'source_url' => $source_url,
        'supplier_id'=> $supplier_id,
    ];

    $plan[ $id ] = [
        'old' => $spec['old'],
        'new' => $new_content,
        'already_applied' => ( $current === $new_content ),
    ];
}

$backup_key = 'emdo_tolecarnes_ready_descriptions_backup_20260923';
if ( null === get_option( $backup_key, null ) ) {
    $backup = [
        'created_at' => current_time( 'mysql' ),
        'products'   => $before,
    ];
    if ( ! add_option( $backup_key, $backup, '', false ) ) {
        emdo_tc_ready_fail( 'could not create persistent backup', 9 );
    }
    echo "BACKUP_CREATED={$backup_key}\n";
} else {
    echo "BACKUP_PRESERVED={$backup_key}\n";
}

echo "=== PRECHANGE PLAN ===\n";
foreach ( $plan as $id => $row ) {
    echo sprintf(
        "ID=%d TITLE=%s ACTION=%s OLD_BYTES=%d NEW_BYTES=%d\n",
        $id,
        $specs[$id]['title'],
        $row['already_applied'] ? 'already_applied' : 'append_information',
        strlen( $row['old'] ),
        strlen( $row['new'] )
    );
}

$changed = 0;
$already = 0;

foreach ( $plan as $id => $row ) {
    if ( $row['already_applied'] ) {
        $already++;
        continue;
    }

    $result = wp_update_post(
        [
            'ID'           => $id,
            'post_content' => $row['new'],
        ],
        true
    );

    if ( is_wp_error( $result ) || (int) $result !== (int) $id ) {
        $message = is_wp_error( $result ) ? $result->get_error_message() : 'unexpected update result';
        emdo_tc_ready_fail( "failed updating product {$id}: {$message}", 11 );
    }

    clean_post_cache( $id );
    wc_delete_product_transients( $id );
    $changed++;
}

wp_cache_flush();

$verified = [];
foreach ( $specs as $id => $spec ) {
    clean_post_cache( $id );
    $post = get_post( $id );
    $product = wc_get_product( $id );
    $expected = $plan[$id]['new'];

    if ( ! $post || ! $product ) {
        emdo_tc_ready_fail( "product {$id} unavailable during verification", 12 );
    }

    $check = [
        'id'          => $id,
        'title'       => (string) $post->post_title,
        'slug'        => (string) $post->post_name,
        'status'      => (string) $post->post_status,
        'author'      => (int) $post->post_author,
        'excerpt'     => (string) $post->post_excerpt,
        'content'     => (string) $post->post_content,
        'sku'         => (string) $product->get_sku(),
        'source_url'  => (string) get_post_meta( $id, '_emdo_source_url', true ),
        'supplier_id' => absint( get_post_meta( $id, '_emdo_supplier_id', true ) ),
    ];

    if (
        $check['content'] !== $expected
        || $check['title'] !== $before[$id]['title']
        || $check['slug'] !== $before[$id]['slug']
        || $check['status'] !== $before[$id]['status']
        || $check['author'] !== $before[$id]['author']
        || $check['excerpt'] !== $before[$id]['excerpt']
        || $check['sku'] !== $before[$id]['sku']
        || $check['source_url'] !== $before[$id]['source_url']
        || $check['supplier_id'] !== $before[$id]['supplier_id']
        || 0 !== strpos( $check['content'], $spec['old'] )
        || false === strpos( $check['content'], '<!-- emdo-tolecarnes-ready-info-20260923 -->' )
    ) {
        emdo_tc_ready_fail( "post-write verification failed for product {$id}", 13 );
    }

    $verified[] = [
        'id'          => $id,
        'title'       => $check['title'],
        'content_len' => strlen( $check['content'] ),
        'preserved_original_prefix' => true,
    ];
}

echo "=== RESULT ===\n";
echo "target_count=6\n";
echo "changed={$changed}\n";
echo "already_applied={$already}\n";
echo "verified=" . count( $verified ) . "\n";
echo wp_json_encode( $verified, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n";
echo "TOLECARNES_READY_DESCRIPTIONS_UPDATE_OK\n";
