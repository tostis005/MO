<?php
/**
 * Production update for first five Tolecarnes products using TranslatePress.
 * Updates WooCommerce short/long description in Spanish and stores reviewed English strings
 * in TranslatePress. It never duplicates products or changes price, stock, SKU, images or taxonomy.
 */
if (!defined('ABSPATH')) { exit("Run inside WordPress\n"); }
global $wpdb;

function mo_tp_fail($message){
    if (defined('WP_CLI') && WP_CLI) { WP_CLI::error($message); }
    throw new Exception($message);
}

// Reuse the already-reviewed ES/EN copy definitions from the batch data script.
$data_file = __DIR__ . '/tolecarnes-products-batch-01-production.php';
if (!is_file($data_file)) { mo_tp_fail('Reviewed batch data file not found.'); }
$source = file_get_contents($data_file);
$start = strpos($source, '$producer_es =');
$end   = strpos($source, '// PRE-FLIGHT');
if ($start === false || $end === false || $end <= $start) { mo_tp_fail('Could not extract reviewed copy definitions.'); }
$definitions = substr($source, $start, $end - $start);
eval($definitions);
if (empty($products) || count($products) !== 5) { mo_tp_fail('Expected exactly five reviewed product definitions.'); }

$expected = [
    'carne_picada'   => ['id'=>11058, 'slug'=>'carne-picada-extra', 'title'=>'Carne picada extra', 'en_title'=>'Extra Ground Beef'],
    'burger_mixta'   => ['id'=>11061, 'slug'=>'burger-mixtas-sin-gluten-2-uds', 'title'=>'Burger mixtas - sin gluten (2 uds)', 'en_title'=>'Gluten-Free Beef & Pork Burgers'],
    'filetes_primera'=> ['id'=>11064, 'slug'=>'filetes-primera', 'title'=>'Filetes primera', 'en_title'=>'First-Category Beef Steaks'],
    'ragu'           => ['id'=>11073, 'slug'=>'magro-o-ragu-de-ternera', 'title'=>'Magro o ragú de ternera', 'en_title'=>'Diced Beef for Ragout'],
    'entrana'        => ['id'=>11075, 'slug'=>'entrana-de-ternera', 'title'=>'Entraña de ternera', 'en_title'=>'Beef Skirt Steak – Entraña'],
];

$trp_table = $wpdb->prefix . 'trp_dictionary_es_es_en_us';
if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $trp_table)) !== $trp_table) {
    mo_tp_fail('TranslatePress ES→EN dictionary table not found.');
}
$columns = $wpdb->get_col("SHOW COLUMNS FROM `{$trp_table}`", 0);
foreach (['id','original','translated','status','block_type'] as $required) {
    if (!in_array($required, $columns, true)) { mo_tp_fail("TranslatePress dictionary missing column {$required}."); }
}

function mo_tp_segments($html){
    $segments = [];
    if (preg_match_all('~<(h2|h3|p)\b[^>]*>(.*?)</\1>~isu', $html, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $text = trim(html_entity_decode(wp_strip_all_tags($row[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            if ($text !== '') { $segments[] = ['tag'=>strtolower($row[1]), 'text'=>$text]; }
        }
    }
    return $segments;
}

function mo_tp_pair_html($es_html, $en_html, $label){
    $es = mo_tp_segments($es_html); $en = mo_tp_segments($en_html);
    if (count($es) !== count($en)) { mo_tp_fail("Segment mismatch in {$label}: ES=".count($es)." EN=".count($en)); }
    $pairs=[];
    foreach ($es as $i=>$seg) {
        if ($seg['tag'] !== $en[$i]['tag']) { mo_tp_fail("Tag mismatch in {$label} at {$i}."); }
        $pairs[$seg['text']] = $en[$i]['text'];
    }
    return $pairs;
}

function mo_tp_get_vendor_name($post){
    $u = get_userdata((int)$post->post_author);
    return $u ? (string)$u->display_name : '';
}

// Preflight exact products before any write.
$resolved=[];
foreach ($expected as $key=>$exp) {
    $p = get_post((int)$exp['id']);
    if (!$p || $p->post_type !== 'product' || $p->post_status === 'trash') { mo_tp_fail("Missing product {$key} ID {$exp['id']}."); }
    if ($p->post_name !== $exp['slug'] || $p->post_title !== $exp['title']) { mo_tp_fail("Identity mismatch for {$key}: {$p->ID} {$p->post_title} / {$p->post_name}"); }
    if (stripos(mo_tp_get_vendor_name($p), 'tolecarnes') === false) { mo_tp_fail("Vendor mismatch for {$key}."); }
    $types = wp_get_post_terms($p->ID, 'product_type', ['fields'=>'names']);
    if (is_wp_error($types) || !in_array('simple', $types, true)) { mo_tp_fail("Unexpected WooCommerce product type for {$key}."); }
    $resolved[$key]=$p;
    echo "PRECHECK {$key}: ID {$p->ID} {$p->post_title}\n";
}

// Build complete copy and TranslatePress string map.
$payload=[]; $translations=[];
foreach ($products as $key=>$spec) {
    if (!isset($resolved[$key], $expected[$key])) { mo_tp_fail("Unexpected definition key {$key}."); }
    $es_content = trim($spec['es_content'])."\n".trim($producer_es)."\n".trim($spec['es_faq']);
    $en_content = trim($spec['en_content'])."\n".trim($producer_en)."\n".trim($spec['en_faq']);
    $payload[$key] = [
        'es_excerpt'=>$spec['es_excerpt'], 'es_content'=>$es_content,
        'en_excerpt'=>$spec['en_excerpt'], 'en_content'=>$en_content,
    ];
    $translations[$expected[$key]['title']] = $expected[$key]['en_title'];
    foreach (mo_tp_pair_html($spec['es_excerpt'], $spec['en_excerpt'], "{$key} excerpt") as $o=>$t) { $translations[$o]=$t; }
    foreach (mo_tp_pair_html($es_content, $en_content, "{$key} content") as $o=>$t) { $translations[$o]=$t; }
}
if (count($translations) < 40) { mo_tp_fail('Translation map unexpectedly small: '.count($translations)); }
echo "PRECHECK translation strings: ".count($translations)."\n";

// Backup original product copy and any existing TranslatePress rows once.
$backup_key = 'mo_tolecarnes_batch01_translatepress_backup_20260831';
$backup = get_option($backup_key, null);
if ($backup === null) {
    $backup = ['created_at'=>current_time('mysql'), 'posts'=>[], 'trp'=>[]];
    foreach ($resolved as $key=>$p) {
        $backup['posts'][$key] = ['ID'=>(int)$p->ID, 'post_excerpt'=>$p->post_excerpt, 'post_content'=>$p->post_content];
    }
    foreach (array_keys($translations) as $original) {
        $rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM `{$trp_table}` WHERE original=%s", $original), ARRAY_A);
        $backup['trp'][$original] = $rows ?: [];
    }
    if (!add_option($backup_key, $backup, '', false)) { mo_tp_fail('Could not create production backup option.'); }
    echo "BACKUP created {$backup_key}\n";
} else {
    echo "BACKUP already exists and will be preserved {$backup_key}\n";
}

function mo_tp_restore($backup, $trp_table){
    global $wpdb;
    if (!is_array($backup)) return;
    foreach (($backup['posts'] ?? []) as $row) {
        wp_update_post(wp_slash(['ID'=>(int)$row['ID'], 'post_excerpt'=>$row['post_excerpt'], 'post_content'=>$row['post_content']]));
        clean_post_cache((int)$row['ID']);
    }
    foreach (($backup['trp'] ?? []) as $original=>$rows) {
        $wpdb->delete($trp_table, ['original'=>$original], ['%s']);
        foreach ($rows as $row) {
            $data=[]; $format=[];
            foreach ($row as $col=>$value) {
                if ($col === 'id') { $data[$col]=(int)$value; $format[]='%d'; }
                elseif (in_array($col,['status','block_type','original_id'],true) && $value !== null) { $data[$col]=(int)$value; $format[]='%d'; }
                else { $data[$col]=$value; $format[]='%s'; }
            }
            $wpdb->insert($trp_table, $data, $format);
        }
    }
}

try {
    // Update only descriptions. Product identity, commerce data and taxonomy remain untouched.
    foreach ($resolved as $key=>$p) {
        $r = wp_update_post(wp_slash([
            'ID'=>(int)$p->ID,
            'post_excerpt'=>$payload[$key]['es_excerpt'],
            'post_content'=>$payload[$key]['es_content'],
        ]), true);
        if (is_wp_error($r)) { throw new Exception("Product update failed {$key}: ".$r->get_error_message()); }
        clean_post_cache((int)$p->ID);
        echo "UPDATED ES {$key}: {$p->ID}\n";
    }

    // Store English strings as human-reviewed TranslatePress translations (status 2).
    foreach ($translations as $original=>$translated) {
        $ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM `{$trp_table}` WHERE original=%s AND block_type=0", $original));
        if ($ids) {
            foreach ($ids as $id) {
                $ok = $wpdb->update($trp_table, ['translated'=>$translated,'status'=>2,'block_type'=>0], ['id'=>(int)$id], ['%s','%d','%d'], ['%d']);
                if ($ok === false) { throw new Exception("TranslatePress update failed for string: {$original}"); }
            }
        } else {
            $data=['original'=>$original,'translated'=>$translated,'status'=>2,'block_type'=>0];
            $formats=['%s','%s','%d','%d'];
            if (in_array('original_id', $GLOBALS['columns'] ?? [], true)) { $data['original_id']=null; $formats[]='%s'; }
            $ok=$wpdb->insert($trp_table,$data,$formats);
            if ($ok === false) { throw new Exception("TranslatePress insert failed for string: {$original} DB={$wpdb->last_error}"); }
        }
    }
    echo "UPDATED EN TranslatePress strings: ".count($translations)."\n";

    // Database verification.
    foreach ($resolved as $key=>$p) {
        $fresh=get_post((int)$p->ID);
        if (!$fresh || trim($fresh->post_excerpt)!==trim(wp_unslash($payload[$key]['es_excerpt']))) { throw new Exception("ES excerpt verification failed {$key}"); }
        if (strpos($fresh->post_content,'<h2>Sobre Tolecarnes</h2>')===false || strpos($fresh->post_content,'<h2>Preguntas frecuentes</h2>')===false) { throw new Exception("ES content verification failed {$key}"); }
        $en_title=$expected[$key]['en_title'];
        $found=$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM `{$trp_table}` WHERE original=%s AND translated=%s AND status=2", $expected[$key]['title'], $en_title));
        if ((int)$found < 1) { throw new Exception("EN title verification failed {$key}"); }
        echo "VERIFIED DB {$key}\n";
    }
} catch (Throwable $e) {
    echo "FAILURE: {$e->getMessage()}\nROLLBACK START\n";
    mo_tp_restore(get_option($backup_key), $trp_table);
    mo_tp_fail('Batch rolled back: '.$e->getMessage());
}

echo "SUCCESS: five Tolecarnes product fichas updated ES + TranslatePress EN.\n";
