<?php
/** Build a repository-wide inventory of editorial article images. */
$root = dirname(__DIR__);
$patterns = array(
    $root . '/.github/data/editorial-authority-batch*/*.php',
    $root . '/.github/data/editorial-authority-batch*-*/*.php',
);
$files = array();
foreach ($patterns as $pattern) {
    foreach (glob($pattern) ?: array() as $file) {
        $files[$file] = true;
    }
}
$files = array_keys($files);
sort($files, SORT_NATURAL);
$rows = array();
$by_id = array();
$by_direct = array();
foreach ($files as $file) {
    $article = require $file;
    if (!is_array($article) || empty($article['key'])) {
        continue;
    }
    $image = isset($article['image']) && is_array($article['image']) ? $article['image'] : array();
    $id = trim((string)($image['id'] ?? ''));
    $direct = trim((string)($image['direct'] ?? ''));
    $row = array(
        'file' => str_replace($root . '/', '', $file),
        'key' => (string)$article['key'],
        'slug' => (string)($article['slug'] ?? ''),
        'title' => (string)($article['title'] ?? ''),
        'topic' => (string)($article['topic'] ?? ''),
        'image_id' => $id,
        'image_direct' => $direct,
        'image_page' => (string)($image['page'] ?? ''),
        'photographer' => (string)($image['photographer'] ?? ''),
        'alt_es' => (string)($image['alt_es'] ?? ''),
        'alt_en' => (string)($image['alt_en'] ?? ''),
    );
    $rows[] = $row;
    if ($id !== '') { $by_id[$id][] = $row['key']; }
    if ($direct !== '') {
        $normalized = preg_replace('/\?.*$/', '', $direct);
        $by_direct[$normalized][] = $row['key'];
    }
}
$dupes = array('ids'=>array(), 'direct'=>array());
foreach ($by_id as $key => $uses) { if (count($uses) > 1) { $dupes['ids'][$key] = $uses; } }
foreach ($by_direct as $key => $uses) { if (count($uses) > 1) { $dupes['direct'][$key] = $uses; } }
$high_risk = array();
foreach ($rows as $row) {
    $haystack = mb_strtolower($row['key'].' '.$row['slug'].' '.$row['title'].' '.$row['topic'].' '.$row['alt_es']);
    if (preg_match('/jam[oó]n|paleta|ib[eé]ric|bellota|aove|aceite|oliva/u', $haystack)) {
        $high_risk[] = $row;
    }
}
$out = array(
    'generated_at' => gmdate('c'),
    'article_count' => count($rows),
    'duplicate_image_ids' => $dupes['ids'],
    'duplicate_image_urls' => $dupes['direct'],
    'high_risk_ham_oil' => $high_risk,
    'articles' => $rows,
);
echo json_encode($out, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) . PHP_EOL;
