<?php
/**
 * Narrow category image and editorial update, 2026-10-21.
 * Runs via WP-CLI on the real WooCommerce site. No global taxonomy rewrite.
 */
if (!defined('ABSPATH') || !defined('WP_CLI') || !WP_CLI) {
    exit(1);
}
$host = strtolower((string) wp_parse_url((string) get_option('siteurl'), PHP_URL_HOST));
if (!in_array($host, ['www.elmercadodeorigen.com','elmercadodeorigen.com'], true)
    || !taxonomy_exists('product_cat')) {
    throw new RuntimeException('Unexpected WordPress environment.');
}
$phase = (string) (getenv('EMDO_CATEGORY_DESIGN_MODE') ?: 'audit');
if (!in_array($phase, ['audit', 'apply', 'verify'], true)) {
    throw new RuntimeException('Unknown phase');
}
$asset_dir = (string) (getenv('EMDO_CATEGORY_ASSET_DIR') ?: '');
$images = [
    'bebidas'          => 'categoria-bebidas-20261021.webp',
    'mieles'           => 'categoria-mieles-20261021.webp',
    'frutos-secos'     => 'categoria-frutos-secos-20261021.webp',
    'cosmetica-natural'=> 'categoria-cosmetica-natural-20261021.webp',
];
$descriptions = [
    'mermeladas-y-confituras' => 'Mermeladas y confituras elaboradas con frutas y otras combinaciones de ingredientes. Descubre distintas variedades, desde sabores clásicos hasta propuestas para acompañar quesos, tostadas o postres.',
    'bebidas' => 'Explora zumos, néctares, tés, infusiones y otras bebidas de distintos productores. Compara ingredientes, sabores y formatos para elegir lo que más encaje con cada ocasión.',
    'zumos-nectares-y-limonadas' => 'Zumos, néctares y limonadas con distintas frutas y mezclas de ingredientes. Descubre sus sabores, formatos y propuestas de cada elaborador.',
    'tes-e-infusiones' => 'Tés e infusiones con diferentes plantas, frutas y mezclas aromáticas. Una selección para quienes buscan sabores y matices distintos en cada taza.',
    'vinos-y-licores' => 'Vinos y licores de diferentes procedencias y estilos de elaboración. Encuentra propuestas para descubrir nuevos sabores y elegir según la ocasión.',
    'frutos-secos' => 'Almendras, avellanas, castañas y otros frutos secos, solos o en surtidos. Compara variedades, preparaciones y formatos para picar, cocinar o regalar.',
    'mieles' => 'Mieles de distintos orígenes florales, tonalidades y perfiles de sabor. Descubre las particularidades de cada variedad y el trabajo de los productores que hay detrás.',
    'cosmetica-natural' => 'Jabones, cremas y otros productos de cuidado personal de productores especializados. Explora sus ingredientes, formulaciones y formatos para elegir con información clara.',
    'condimentos-y-especias' => 'Sales, pimientas, especias y otros condimentos para aportar aromas y matices a la cocina. Encuentra distintas combinaciones y formatos para recetas dulces y saladas.',
    'frutas' => 'Frutas de temporada y variedades tropicales para disfrutar al natural, en macedonias o en recetas. Elige entre diferentes orígenes, tipos y formatos disponibles.',
    'verduras' => 'Verduras de la huerta para preparar ensaladas, guarniciones y platos de cada día. Descubre las variedades disponibles según la temporada.',
];
$english_summaries = [
    'mermeladas-y-confituras' => 'Artisan jams and preserves, from familiar fruit flavours to combinations for toast, desserts and cheese.',
    'bebidas' => 'Juices, nectars, teas, infusions and other drinks with varied ingredients and flavours.',
    'zumos-nectares-y-limonadas' => 'Juices, nectars and lemonades made with different fruits and ingredient combinations.',
    'tes-e-infusiones' => 'Teas and herbal infusions with a range of botanical blends and aromatic profiles.',
    'vinos-y-licores' => 'Wines and liqueurs from different producers, regions and styles.',
    'frutos-secos' => 'Almonds, hazelnuts, chestnuts and other nuts, on their own or in assorted selections.',
    'mieles' => 'Honeys with different floral origins and flavour profiles, from delicate to more intense varieties.',
    'cosmetica-natural' => 'Soaps, creams and personal-care products from specialist producers, with different ingredients and formulations.',
    'condimentos-y-especias' => 'Salts, spices and seasonings to bring new aromas and flavours to everyday cooking.',
    'frutas' => 'Seasonal fruit and tropical varieties for enjoying fresh or using in recipes.',
    'verduras' => 'Fresh vegetables for salads, side dishes and everyday meals, depending on the season.',
    'chocolates-artesanos' => 'Craft chocolates with different cocoa percentages, flavour profiles and formats.',
    'cafe-de-especialidad' => 'Specialty coffee from different origins and roasts, in beans or ground for your brewing method.',
    'huevos' => 'Eggs from identified producers, with clear details of their origin and presentation.',
];
$changed = [];
$missing = [];
$updated_images = [];
$backup = [];
foreach ($descriptions as $slug => $description) {
    $term = get_term_by('slug', $slug, 'product_cat');
    if (!$term instanceof WP_Term) {
        $missing[] = $slug;
        continue;
    }
    $backup[$slug] = [
        'id' => (int) $term->term_id,
        'description' => (string) $term->description,
        'thumbnail_id' => (int) get_term_meta($term->term_id, 'thumbnail_id', true),
        'en_summary' => (string) get_term_meta($term->term_id, '_emdo_en_hub_summary', true),
        'en_description' => (string) get_term_meta($term->term_id, '_en_US_description', true),
    ];
}
foreach (array_keys($images) as $slug) {
    if (!isset($backup[$slug])) {
        throw new RuntimeException('Required image category does not exist: ' . $slug);
    }
}
if ($phase === 'apply') {
    $backup_root = rtrim((string) getenv('HOME'), '/') . '/.elmercado-rollbacks/category-images-editorial';
    if (!is_dir($backup_root) && !wp_mkdir_p($backup_root)) {
        throw new RuntimeException('Cannot create backup directory.');
    }
    $backup_file = $backup_root . '/before-' . gmdate('Ymd-His') . '-' . getmypid() . '.json';
    $backup_data = wp_json_encode(['siteurl' => site_url(), 'date_utc' => gmdate('c'), 'terms' => $backup], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (false === file_put_contents($backup_file, $backup_data)) {
        throw new RuntimeException('Cannot persist rollback snapshot.');
    }
    foreach ($descriptions as $slug => $description) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if (!$term instanceof WP_Term) {
            continue;
        }
        if ((string) $term->description !== $description) {
            $result = wp_update_term((int) $term->term_id, 'product_cat', ['description' => $description]);
            if (is_wp_error($result)) {
                throw new RuntimeException('Failed updating description: ' . $slug . ': ' . $result->get_error_message());
            }
            $changed[] = $slug;
        }
    }
    foreach ($english_summaries as $slug => $english) {
        $term = get_term_by('slug', $slug, 'product_cat');
        if (!$term instanceof WP_Term || '1' !== (string) get_term_meta($term->term_id, '_en_US_published', true)) {
            continue;
        }
        update_term_meta($term->term_id, '_emdo_en_hub_summary', $english);
        // Keep existing fuller term translations; fix only blank or obvious generic text.
        $old = trim((string) get_term_meta($term->term_id, '_en_US_description', true));
        if ($old === '' || preg_match('/^(?:products? of |explore (?:the |our )?selection|discover products?)/i', $old)) {
            update_term_meta($term->term_id, '_en_US_description', $english);
        }
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    foreach ($images as $slug => $filename) {
        $term = get_term_by('slug', $slug, 'product_cat');
        $existing_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
        $valid_existing = $existing_id > 0 && wp_get_attachment_image_url($existing_id, 'full');
        // This release only fills blank/broken thumbnails; never replaces a working photo.
        if ($valid_existing) {
            $updated_images[$slug] = ['status' => 'kept-existing', 'attachment' => $existing_id];
            continue;
        }
        $path = rtrim($asset_dir, '/') . '/' . $filename;
        if (!is_file($path)) {
            throw new RuntimeException('Missing image binary: ' . $filename);
        }
        $bytes = file_get_contents($path);
        $size = is_string($bytes) ? @getimagesizefromstring($bytes) : false;
        if (!$size || (int) $size[0] < 300 || (int) $size[1] < 400 || ($size['mime'] ?? '') !== 'image/webp') {
            throw new RuntimeException('Invalid category image: ' . $filename);
        }
        $upload = wp_upload_bits($filename, null, $bytes);
        if (!empty($upload['error']) || empty($upload['file'])) {
            throw new RuntimeException('Upload failed: ' . $filename . ' ' . ($upload['error'] ?? ''));
        }
        $attachment_id = wp_insert_attachment([
            'post_mime_type' => 'image/webp',
            'post_title'     => 'Categoría ' . $term->name,
            'post_status'    => 'inherit',
            'post_content'   => '',
            'post_excerpt'   => '',
        ], $upload['file']);
        if (!$attachment_id || is_wp_error($attachment_id)) {
            throw new RuntimeException('Could not register media attachment for ' . $slug);
        }
        $metadata = wp_generate_attachment_metadata($attachment_id, $upload['file']);
        if (is_array($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }
        update_post_meta($attachment_id, '_wp_attachment_image_alt', 'Fotografía de la categoría ' . $term->name);
        update_post_meta($attachment_id, '_emdo_category_asset_release', '20261021');
        update_term_meta($term->term_id, 'thumbnail_id', $attachment_id);
        $updated_images[$slug] = ['status' => 'attached', 'attachment' => $attachment_id, 'file' => wp_get_attachment_url($attachment_id)];
    }
    clean_term_cache(array_values(array_map(static fn($v) => $v['id'], $backup)), 'product_cat');
    if (function_exists('wc_delete_product_transients')) {
        wc_delete_product_transients();
    }
    wp_cache_flush();
    echo 'CATEGORY_DESIGN_BACKUP=' . $backup_file . PHP_EOL;
}
$state = [];
foreach ($images as $slug => $filename) {
    $term = get_term_by('slug', $slug, 'product_cat');
    $id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
    $url = $id ? wp_get_attachment_image_url($id, 'woocommerce_thumbnail') : '';
    $state[$slug] = ['term_id' => (int) $term->term_id, 'thumbnail_id' => $id, 'image_url' => (string) $url, 'description' => (string) $term->description, 'image_action' => $updated_images[$slug] ?? null];
    if ($phase !== 'audit' && (!$id || !$url || !$term->description)) {
        throw new RuntimeException('Post-write validation failed: ' . $slug);
    }
}
echo 'CATEGORY_DESIGN_RESULT=' . wp_json_encode(['phase' => $phase, 'images' => $state, 'changed_descriptions' => $changed, 'missing_optional_terms' => $missing], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
