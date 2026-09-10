<?php
/**
 * Automated QA for the 65 bilingual Wagyu editorial payloads.
 * Run from repository root: php tools/wagyu-editorial-preflight.php
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$files = glob($root . '/.github/data/editorial-wagyu-batch*-20260910/*.php') ?: [];
sort($files, SORT_NATURAL);

$errors = [];
$warnings = [];
$articles = [];
$required = [
    'key', 'slug', 'en_slug', 'topic', 'title', 'en_title',
    'excerpt', 'en_excerpt', 'focus_keyword', 'en_focus_keyword',
    'seo_title', 'en_seo_title', 'meta_description', 'en_meta_description',
    'content', 'en_content',
];

if (count($files) !== 65) {
    $errors[] = 'Expected 65 Wagyu payload files; found ' . count($files) . '.';
}

foreach ($files as $file) {
    $relative = ltrim(str_replace($root, '', $file), '/');
    $data = include $file;

    if (!is_array($data)) {
        $errors[] = "$relative does not return an array.";
        continue;
    }

    foreach ($required as $field) {
        if (!array_key_exists($field, $data) || !is_string($data[$field]) || trim($data[$field]) === '') {
            $errors[] = "$relative: missing or empty required field '$field'.";
        }
    }

    if (($data['topic'] ?? null) !== 'wagyu') {
        $errors[] = "$relative: topic must be 'wagyu'.";
    }

    $articles[] = ['file' => $relative, 'data' => $data];
}

function duplicates(array $values): array
{
    $counts = array_count_values(array_filter($values, static fn($v) => is_string($v) && $v !== ''));
    return array_keys(array_filter($counts, static fn($count) => $count > 1));
}

foreach (['key', 'slug', 'en_slug', 'title', 'en_title'] as $field) {
    $dupes = duplicates(array_map(static fn($a) => $a['data'][$field] ?? '', $articles));
    foreach ($dupes as $value) {
        $errors[] = "Duplicate $field: $value";
    }
}

foreach (['focus_keyword', 'en_focus_keyword', 'seo_title', 'en_seo_title'] as $field) {
    $dupes = duplicates(array_map(static fn($a) => $a['data'][$field] ?? '', $articles));
    foreach ($dupes as $value) {
        $warnings[] = "Duplicate $field: $value";
    }
}

function word_count_html(string $html): int
{
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match_all('/[\p{L}\p{N}]+(?:[’\'\-][\p{L}\p{N}]+)*/u', $text, $matches);
    return count($matches[0]);
}

function internal_links(string $html): array
{
    preg_match_all('/href\s*=\s*([\'\"])(.*?)\1/i', $html, $matches);
    return array_values(array_unique($matches[2] ?? []));
}

function normalize_path(string $href): ?string
{
    if ($href === '' || $href[0] === '#' || str_starts_with($href, 'mailto:') || str_starts_with($href, 'tel:')) {
        return null;
    }
    if (preg_match('~^https?://~i', $href)) {
        return null;
    }
    if (!str_starts_with($href, '/')) {
        return null;
    }
    $path = parse_url($href, PHP_URL_PATH);
    if (!is_string($path) || $path === '') {
        return null;
    }
    return rtrim($path, '/') . '/';
}

function looks_like_wagyu_cluster_link(string $path): bool
{
    return (bool) preg_match(
        '~(?:wagyu|kobe|miyazaki|kagoshima|bms|kuroge|washu|angus|yakiniku|teppanyaki|shabu|sukiyaki|rubia-gallega|carne-madurada)~i',
        $path
    );
}

$esUrls = [];
$enUrls = [];
foreach ($articles as $article) {
    $d = $article['data'];
    if (!empty($d['slug'])) {
        $esUrls['/' . trim($d['slug'], '/') . '/'] = true;
    }
    if (!empty($d['en_slug'])) {
        $enUrls['/en/' . trim($d['en_slug'], '/') . '/'] = true;
    }
}

$esCounts = [];
$enCounts = [];
$linkCount = 0;
foreach ($articles as $article) {
    $file = $article['file'];
    $d = $article['data'];
    $es = (string) ($d['content'] ?? '');
    $en = (string) ($d['en_content'] ?? '');

    $esWords = word_count_html($es);
    $enWords = word_count_html($en);
    $esCounts[$file] = $esWords;
    $enCounts[$file] = $enWords;

    if ($esWords < 700) {
        $errors[] = "$file: Spanish content has $esWords words (<700).";
    }
    if ($enWords < 650) {
        $errors[] = "$file: English content has $enWords words (<650).";
    }

    if (mb_strlen((string) ($d['seo_title'] ?? '')) > 65) {
        $warnings[] = "$file: Spanish SEO title is over 65 characters.";
    }
    if (mb_strlen((string) ($d['en_seo_title'] ?? '')) > 65) {
        $warnings[] = "$file: English SEO title is over 65 characters.";
    }
    if (mb_strlen((string) ($d['meta_description'] ?? '')) > 170) {
        $warnings[] = "$file: Spanish meta description is over 170 characters.";
    }
    if (mb_strlen((string) ($d['en_meta_description'] ?? '')) > 170) {
        $warnings[] = "$file: English meta description is over 170 characters.";
    }

    foreach (internal_links($es) as $href) {
        $path = normalize_path($href);
        if ($path === null) {
            continue;
        }
        $linkCount++;
        if (str_starts_with($path, '/en/')) {
            $errors[] = "$file: Spanish content links to English path $path";
            continue;
        }
        if (looks_like_wagyu_cluster_link($path) && !isset($esUrls[$path])) {
            $errors[] = "$file: unresolved Spanish Wagyu-cluster link $path";
        }
    }

    foreach (internal_links($en) as $href) {
        $path = normalize_path($href);
        if ($path === null) {
            continue;
        }
        $linkCount++;
        if (looks_like_wagyu_cluster_link($path) && !str_starts_with($path, '/en/')) {
            $errors[] = "$file: English content links to non-English Wagyu path $path";
            continue;
        }
        if (str_starts_with($path, '/en/') && looks_like_wagyu_cluster_link($path) && !isset($enUrls[$path])) {
            $errors[] = "$file: unresolved English Wagyu-cluster link $path";
        }
    }
}

$esMin = $esCounts ? min($esCounts) : 0;
$esMax = $esCounts ? max($esCounts) : 0;
$esAvg = $esCounts ? (int) round(array_sum($esCounts) / count($esCounts)) : 0;
$enMin = $enCounts ? min($enCounts) : 0;
$enMax = $enCounts ? max($enCounts) : 0;
$enAvg = $enCounts ? (int) round(array_sum($enCounts) / count($enCounts)) : 0;

fwrite(STDOUT, "Wagyu editorial preflight\n");
fwrite(STDOUT, "========================\n");
fwrite(STDOUT, 'Payloads: ' . count($articles) . "\n");
fwrite(STDOUT, "Spanish words: min=$esMin max=$esMax avg=$esAvg\n");
fwrite(STDOUT, "English words: min=$enMin max=$enMax avg=$enAvg\n");
fwrite(STDOUT, "Internal links checked: $linkCount\n");
fwrite(STDOUT, 'Warnings: ' . count($warnings) . "\n");
foreach ($warnings as $warning) {
    fwrite(STDOUT, "WARNING: $warning\n");
}
fwrite(STDOUT, 'Errors: ' . count($errors) . "\n");
foreach ($errors as $error) {
    fwrite(STDERR, "ERROR: $error\n");
}

if ($errors) {
    exit(1);
}

fwrite(STDOUT, "PASS: all 65 Wagyu editorial payloads passed blocking QA.\n");
exit(0);
