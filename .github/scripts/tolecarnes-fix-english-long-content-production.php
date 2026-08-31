<?php
/**
 * Fix the English long description source used by the product template.
 * Scope: _en_US_post_content only for the ten reviewed Tolecarnes products.
 * Does not modify prices, stock, SKU, images, taxonomy, variations, Spanish copy or excerpts.
 */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }

global $wpdb;

function mo_enlong_fail($message) {
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}
function mo_enlong_vendor($post) {
    $u = $post ? get_userdata((int)$post->post_author) : false;
    return $u ? (string)$u->display_name : '';
}
function mo_enlong_extract_defs($path, $start_candidates, $end_marker) {
    if (!is_file($path)) mo_enlong_fail("Reviewed source file missing: {$path}");
    $src = file_get_contents($path);
    $start = false;
    foreach ($start_candidates as $candidate) {
        $pos = strpos($src, $candidate);
        if ($pos !== false) { $start = $pos; break; }
    }
    $end = strpos($src, $end_marker, $start === false ? 0 : $start);
    if ($start === false || $end === false || $end <= $start) {
        mo_enlong_fail("Could not extract reviewed copy definitions from {$path}");
    }
    return substr($src, $start, $end - $start);
}

$dir = __DIR__;
$b1_file = $dir . '/tolecarnes-products-batch-01-production.php';
$b2_file = $dir . '/tolecarnes-products-batch-02-production.php';

// Load only the reviewed copy-definition blocks from the two already-approved batch scripts.
$b1_defs = mo_enlong_extract_defs($b1_file, ['$producer_es =', '$producer_es='], '// PRE-FLIGHT');
eval($b1_defs);
$b1_products = $products;
$b1_producer_en = $producer_en;
unset($products, $producer_es, $producer_en);

$b2_defs = mo_enlong_extract_defs($b2_file, ['$producer_es=', '$producer_es ='], '$trp=');
eval($b2_defs);
$b2_products = $products;
$b2_producer_en = $producer_en;
unset($products, $producer_es, $producer_en);

if (trim($b1_producer_en) !== trim($b2_producer_en)) {
    mo_enlong_fail('Producer English copy differs between reviewed batches.');
}
$producer_en = trim($b1_producer_en);

$b1_ids = [
    'carne_picada'    => 11058,
    'burger_mixta'    => 11061,
    'filetes_primera' => 11064,
    'ragu'            => 11073,
    'entrana'         => 11075,
];

$expected_titles = [
    11058 => 'Carne picada extra',
    11061 => 'Burger mixtas - sin gluten (2 uds)',
    11064 => 'Filetes primera',
    11073 => 'Magro o ragú de ternera',
    11075 => 'Entraña de ternera',
    11077 => 'Entrecot de lomo bajo',
    11079 => 'Filetes aguja de ternera',
    11082 => 'Chuletón de vaca vieja madurado',
    11087 => 'Solomillo de ternera',
    11090 => 'Morcillo de ternera',
];

$payload = [];
foreach ($b1_ids as $key => $id) {
    if (empty($b1_products[$key]['en_content']) || empty($b1_products[$key]['en_faq'])) {
        mo_enlong_fail("Missing reviewed Batch01 English copy for {$key}");
    }
    $payload[$id] = trim($b1_products[$key]['en_content']) . "\n" . $producer_en . "\n" . trim($b1_products[$key]['en_faq']);
}
foreach ($b2_products as $key => $spec) {
    $id = (int)($spec['id'] ?? 0);
    if (!$id || !isset($expected_titles[$id]) || empty($spec['en_content']) || empty($spec['en_faq'])) {
        mo_enlong_fail("Invalid reviewed Batch02 definition for {$key}");
    }
    $payload[$id] = trim($spec['en_content']) . "\n" . $producer_en . "\n" . trim($spec['en_faq']);
}
if (count($payload) !== 10) mo_enlong_fail('Expected exactly ten English long-content payloads.');

// Strict preflight before any write.
foreach ($expected_titles as $id => $title) {
    $p = get_post($id);
    if (!$p || $p->post_type !== 'product' || $p->post_status === 'trash') mo_enlong_fail("Missing product ID {$id}");
    if ($p->post_title !== $title) mo_enlong_fail("Title mismatch ID {$id}: {$p->post_title}");
    if (stripos(mo_enlong_vendor($p), 'tolecarnes') === false) mo_enlong_fail("Vendor mismatch ID {$id}");
    if (strpos((string)$p->post_content, '<h2>Sobre Tolecarnes</h2>') === false) mo_enlong_fail("Reviewed Spanish long content missing ID {$id}");
    if (strpos($payload[$id], '<h2>About Tolecarnes</h2>') === false || strpos($payload[$id], '<h2>Frequently asked questions</h2>') === false) {
        mo_enlong_fail("English payload incomplete ID {$id}");
    }
    echo "PRECHECK ID={$id} title={$title} vendor=" . mo_enlong_vendor($p) . "\n";
}

$backup_key = 'mo_tolecarnes_en_longcontent_backup_20260831_v1';
$backup = get_option($backup_key, null);
if ($backup === null) {
    $backup = ['created_at' => current_time('mysql'), 'meta' => []];
    foreach (array_keys($payload) as $id) {
        $exists = metadata_exists('post', $id, '_en_US_post_content');
        $backup['meta'][$id] = [
            'exists' => $exists,
            'value' => $exists ? get_post_meta($id, '_en_US_post_content', true) : null,
        ];
    }
    if (!add_option($backup_key, $backup, '', false)) mo_enlong_fail('Could not create English long-content backup.');
    echo "BACKUP created {$backup_key}\n";
} else {
    echo "BACKUP preserved {$backup_key}\n";
}

function mo_enlong_restore($backup) {
    if (!is_array($backup) || empty($backup['meta'])) return;
    foreach ($backup['meta'] as $id => $row) {
        $id = (int)$id;
        if (!empty($row['exists'])) update_post_meta($id, '_en_US_post_content', wp_slash((string)$row['value']));
        else delete_post_meta($id, '_en_US_post_content');
        clean_post_cache($id);
    }
}

try {
    foreach ($payload as $id => $html) {
        update_post_meta($id, '_en_US_post_content', wp_slash($html));
        clean_post_cache($id);
        $stored = get_post_meta($id, '_en_US_post_content', true);
        if (trim((string)$stored) !== trim($html)) throw new Exception("Meta write verification failed ID {$id}");
        echo "UPDATED_AND_VERIFIED_META ID={$id} bytes=" . strlen($stored) . "\n";
    }
} catch (Throwable $e) {
    echo "FAILURE {$e->getMessage()}\nROLLBACK_START\n";
    mo_enlong_restore(get_option($backup_key));
    mo_enlong_fail('English long-content fix rolled back: ' . $e->getMessage());
}

echo "SUCCESS: _en_US_post_content updated and verified for all ten Tolecarnes products.\n";
