<?php
/**
 * One-off production repair for the four La Huerta de Ana Mary products
 * incorporated on 2026-08-24, plus legacy title/slug encoding cleanup.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}
if ( ! class_exists( 'MDO_Database' ) || ! function_exists( 'wc_get_product' ) ) {
    fwrite( STDERR, "ERROR: EMDO/WooCommerce not loaded.\n" );
    exit( 2 );
}

global $wpdb;
$table = MDO_Database::table( 'source_products' );

$targets = array(
    13241 => array(
        'source_id' => 308,
        'source_fragment' => '/tomate-pera-203.html',
        'title' => 'Tomate pera',
        'slug' => 'tomate-pera',
        'description' => '<p>Tomate pera, cuya característica forma alargada le da nombre. Tiene un sabor suave y dulce, piel muy fina y mucha carne. Es estupendo para elaborar gazpacho, salmorejo, sopas frías, conservas y salsas, además de para ensaladas o para untar en pan. Son tomates de temporada cultivados al aire libre, no de invernadero.</p>',
    ),
    13238 => array(
        'source_id' => 307,
        'source_fragment' => '/loras-o-a-oras-40.html',
        'title' => 'Loras o ñoras',
        'slug' => 'loras-o-noras',
        'description' => '<p>Las ñoras, también conocidas como loras según la zona, son un tipo de pimiento de bola, de color rojo brillante, con abundante carne y fácil de pelar.</p><p>Su principal componente es el agua, seguido de los hidratos de carbono, por lo que su aporte calórico es bajo. También aportan fibra y vitamina C.</p><p>Se pueden consumir tanto crudas como cocinadas y, por su tamaño, resultan ideales para rellenar y asar.</p>',
    ),
    13235 => array(
        'source_id' => 306,
        'source_fragment' => '/tomates-115.html',
        'title' => 'Tomates',
        'slug' => 'tomates',
        'description' => '<p>Tomates de tamaño medio-grande, forma achatada, piel fina y sabor intenso. No son de invernadero.</p><p>El tomate es el fruto de la planta tomatera y un ingrediente básico en ensaladas y muchas otras preparaciones. Aporta vitamina C, carotenoides como el licopeno y minerales como potasio y magnesio.</p><p>Aunque botánicamente es una fruta, en cocina se utiliza habitualmente como hortaliza.</p>',
    ),
    13233 => array(
        'source_id' => 305,
        'source_fragment' => '/10-kg-pimientos-lamuyos-rojos-especiales-para-asar-209.html',
        'title' => '10 kg de pimientos lamuyos rojos, especiales para asar',
        'slug' => '10-kg-de-pimientos-lamuyos-rojos-especiales-para-asar',
        'description' => '<p>10 kg de pimientos lamuyos rojos, con gastos de envío incluidos.</p><p>Esta variedad es muy apreciada por su carne gruesa y turgente, su sabor dulce, baja acidez, gran tamaño y color rojo vivo. Son especialmente adecuados para asar y embotar.</p><p>Su principal componente es el agua y aportan fibra, vitamina C y carotenoides. Se pueden consumir crudos o cocinados, y resultan ideales para asar, rellenar o preparar en ensalada.</p>',
    ),
);

$legacy = array(
    12837 => array(
        'source_id' => 301,
        'source_fragment' => '/alubia-blanca-de-ri-n-200.html',
        'title' => 'Alubia blanca de riñón',
        'slug' => 'alubia-blanca-de-rinon',
    ),
);

function mdo_huerta_fix_20260824_assert_source( int $product_id, array $expected ): array {
    global $wpdb, $table;
    $source_id = absint( get_post_meta( $product_id, '_emdo_source_product_id', true ) );
    $source_url = trim( (string) get_post_meta( $product_id, '_emdo_source_url', true ) );
    if ( $source_id !== (int) $expected['source_id'] ) {
        throw new RuntimeException( sprintf( 'Product %d source_id mismatch: got %d expected %d.', $product_id, $source_id, (int) $expected['source_id'] ) );
    }
    if ( false === strpos( $source_url, (string) $expected['source_fragment'] ) ) {
        throw new RuntimeException( sprintf( 'Product %d source URL mismatch: %s.', $product_id, $source_url ) );
    }
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT id, source_url, title, source_payload FROM {$table} WHERE id = %d LIMIT 1", $source_id ), ARRAY_A );
    if ( ! $row ) {
        throw new RuntimeException( sprintf( 'Source row %d not found for product %d.', $source_id, $product_id ) );
    }
    if ( false === strpos( (string) $row['source_url'], (string) $expected['source_fragment'] ) ) {
        throw new RuntimeException( sprintf( 'Source row %d URL mismatch.', $source_id ) );
    }
    return $row;
}

function mdo_huerta_fix_20260824_update_source( int $source_id, string $title, ?string $description = null ): void {
    global $wpdb, $table;
    $row = $wpdb->get_row( $wpdb->prepare( "SELECT source_payload FROM {$table} WHERE id = %d LIMIT 1", $source_id ), ARRAY_A );
    if ( ! $row ) {
        throw new RuntimeException( sprintf( 'Source row %d disappeared.', $source_id ) );
    }
    $payload = json_decode( (string) $row['source_payload'], true );
    $payload = is_array( $payload ) ? $payload : array();
    $payload['title'] = $title;
    if ( null !== $description ) {
        $payload['description'] = $description;
    }
    $updated = $wpdb->update(
        $table,
        array(
            'title' => $title,
            'source_payload' => wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ),
        ),
        array( 'id' => $source_id ),
        array( '%s', '%s' ),
        array( '%d' )
    );
    if ( false === $updated ) {
        throw new RuntimeException( sprintf( 'Could not update source row %d.', $source_id ) );
    }
}

function mdo_huerta_fix_20260824_set_title_slug( int $product_id, string $title, string $slug ): void {
    $post = get_post( $product_id );
    if ( ! $post || 'product' !== $post->post_type ) {
        throw new RuntimeException( sprintf( 'Woo product %d not found.', $product_id ) );
    }
    $slug = wp_unique_post_slug( $slug, $product_id, (string) $post->post_status, 'product', (int) $post->post_parent );
    $result = wp_update_post(
        array(
            'ID' => $product_id,
            'post_title' => $title,
            'post_name' => $slug,
        ),
        true
    );
    if ( is_wp_error( $result ) ) {
        throw new RuntimeException( sprintf( 'Could not update product %d title/slug: %s', $product_id, $result->get_error_message() ) );
    }
    clean_post_cache( $product_id );
}

function mdo_huerta_fix_20260824_remove_unit_label( string $description ): string {
    return trim( (string) preg_replace(
        '~\s*<p\b[^>]*class=["\'][^"\']*\bemdo-source-unit-price\b[^"\']*["\'][^>]*>.*?</p>\s*~isu',
        "\n",
        $description
    ) );
}

try {
    foreach ( $targets as $product_id => $expected ) {
        $row = mdo_huerta_fix_20260824_assert_source( $product_id, $expected );
        unset( $row );

        $product = wc_get_product( $product_id );
        if ( ! $product ) {
            throw new RuntimeException( sprintf( 'Woo product %d unavailable.', $product_id ) );
        }

        $clean_description = mdo_huerta_fix_20260824_remove_unit_label( (string) $expected['description'] );
        $product->set_name( (string) $expected['title'] );
        $product->set_description( wp_kses_post( $clean_description ) );
        $product->save();
        mdo_huerta_fix_20260824_set_title_slug( $product_id, (string) $expected['title'], (string) $expected['slug'] );

        if ( class_exists( 'MDO_Huerta_Defaults' ) ) {
            MDO_Huerta_Defaults::apply_to_product( $product_id );
        }

        delete_post_meta( $product_id, '_emdo_huerta_price_basis' );
        update_post_meta( $product_id, '_emdo_huerta_description_canonical', $clean_description );
        update_post_meta( $product_id, '_emdo_huerta_description_locked', '1' );

        mdo_huerta_fix_20260824_update_source( (int) $expected['source_id'], (string) $expected['title'], $clean_description );

        echo sprintf( "FIXED_RECENT %d | %s\n", $product_id, (string) $expected['title'] );
    }

    foreach ( $legacy as $product_id => $expected ) {
        mdo_huerta_fix_20260824_assert_source( $product_id, $expected );
        mdo_huerta_fix_20260824_set_title_slug( $product_id, (string) $expected['title'], (string) $expected['slug'] );
        mdo_huerta_fix_20260824_update_source( (int) $expected['source_id'], (string) $expected['title'], null );
        echo sprintf( "FIXED_LEGACY %d | %s\n", $product_id, (string) $expected['title'] );
    }

    if ( function_exists( 'mdo_huerta_title_quality_20260824_apply' ) ) {
        foreach ( array_keys( $targets + $legacy ) as $product_id ) {
            mdo_huerta_title_quality_20260824_apply( (int) $product_id );
        }
    }

    $errors = array();
    $category_ok = 0;
    $unit_price_labels = 0;
    $encoding_issues = 0;

    foreach ( $targets as $product_id => $expected ) {
        $product = wc_get_product( $product_id );
        $post = get_post( $product_id );
        $cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'slugs' ) );
        $cats = is_wp_error( $cats ) ? array() : array_values( $cats );
        $description = $product ? (string) $product->get_description() : '';
        $canonical = (string) get_post_meta( $product_id, '_emdo_huerta_description_canonical', true );

        if ( ! $product || ! $post ) {
            $errors[] = "missing:$product_id";
            continue;
        }
        if ( $product->get_name() !== (string) $expected['title'] ) {
            $errors[] = "title:$product_id";
        }
        if ( $post->post_name !== (string) $expected['slug'] ) {
            $errors[] = "slug:$product_id:" . $post->post_name;
        }
        if ( trim( $description ) !== trim( (string) $expected['description'] ) ) {
            $errors[] = "description:$product_id";
        }
        if ( trim( $canonical ) !== trim( (string) $expected['description'] ) || '1' !== (string) get_post_meta( $product_id, '_emdo_huerta_description_locked', true ) ) {
            $errors[] = "canonical:$product_id";
        }
        if ( in_array( 'hortalizas-verduras', $cats, true ) && ! array_intersect( array( 'conservas', 'legumbres', 'sin-categorizar' ), $cats ) ) {
            $category_ok++;
        } else {
            $errors[] = "category:$product_id:" . implode( ',', $cats );
        }
        if ( false !== strpos( $description, 'emdo-source-unit-price' ) || '' !== (string) get_post_meta( $product_id, '_emdo_huerta_price_basis', true ) ) {
            $unit_price_labels++;
            $errors[] = "unit-price:$product_id";
        }
        $check = implode( ' | ', array( $product->get_name(), $post->post_name, wp_strip_all_tags( $description ) ) );
        if ( preg_match( '/(?:Ã|Â|â€|â€™|â€œ|â€|ã.|ï¿½|�|\?\?)/u', $check ) ) {
            $encoding_issues++;
            $errors[] = "encoding:$product_id";
        }
    }

    foreach ( $legacy as $product_id => $expected ) {
        $post = get_post( $product_id );
        $product = wc_get_product( $product_id );
        if ( ! $post || ! $product || $product->get_name() !== (string) $expected['title'] || $post->post_name !== (string) $expected['slug'] ) {
            $errors[] = "legacy:$product_id";
        }
    }

    $source_issues = $wpdb->get_results(
        "SELECT id, title FROM {$table} WHERE id IN (301,305,306,307,308) AND (title REGEXP 'Ã|Â|ã' OR title LIKE '%??%')",
        ARRAY_A
    ) ?: array();
    if ( $source_issues ) {
        $errors[] = 'source-encoding:' . wp_json_encode( $source_issues, JSON_UNESCAPED_UNICODE );
    }

    if ( $errors ) {
        foreach ( $errors as $error ) {
            fwrite( STDERR, "VERIFY_ERROR $error\n" );
        }
        exit( 10 );
    }

    echo sprintf(
        "huerta_new_products_fix_ok recent=%d old_encoding_cleanup=%d categories_ok=%d unit_price_labels=%d encoding_issues=%d\n",
        count( $targets ),
        count( $legacy ),
        $category_ok,
        $unit_price_labels,
        $encoding_issues
    );
} catch ( Throwable $error ) {
    fwrite( STDERR, 'ERROR: ' . $error->getMessage() . "\n" );
    exit( 20 );
}
