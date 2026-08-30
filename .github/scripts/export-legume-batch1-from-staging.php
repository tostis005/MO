<?php
if (!defined('ABSPATH')) { exit(1); }

$slugs = [
  'que-nutrientes-aportan-legumbres-proteinas-fibra-hierro-vitaminas-minerales',
  'que-legumbre-tiene-mas-proteina-comparativa-legumbres-espana',
  'que-legumbre-tiene-mas-hierro-comparativa-legumbres-espana',
  'que-legumbre-tiene-mas-fibra-comparativa-nutricional',
  'garbanzos-lentejas-alubias-cual-es-mas-nutritiva',
];

function emdo_export_en_language() {
  if (!function_exists('Falang')) { return null; }
  $instance = Falang();
  if (!is_object($instance) || !method_exists($instance, 'get_model')) { return null; }
  $model = $instance->get_model();
  if (!is_object($model)) { return null; }
  if (method_exists($model, 'get_language_by_locale')) {
    $lang = $model->get_language_by_locale('en_US');
    if (is_object($lang)) { return $lang; }
  }
  if (method_exists($model, 'get_languages_list')) {
    $langs = $model->get_languages_list(['hide_default' => false]);
    foreach ((array)$langs as $lang) {
      $slug = is_object($lang) && isset($lang->slug) ? strtolower((string)$lang->slug) : '';
      $locale = is_object($lang) && isset($lang->locale) ? strtolower(str_replace('-', '_', (string)$lang->locale)) : '';
      if ($slug === 'en' || strpos($locale, 'en_') === 0) { return $lang; }
    }
  }
  return null;
}

$lang = emdo_export_en_language();
if (!is_object($lang) || !class_exists('\\Falang\\Core\\Post')) {
  fwrite(STDERR, "English Falang language/API unavailable on staging.\n");
  exit(10);
}

$out = [];
foreach ($slugs as $slug) {
  $post = get_page_by_path($slug, OBJECT, 'post');
  if (!$post instanceof WP_Post || $post->post_status !== 'publish') {
    fwrite(STDERR, "Missing published staging post: {$slug}\n");
    exit(11);
  }

  try {
    $fp = new \\Falang\\Core\\Post($post->ID);
    $en_title   = (string)$fp->translate_post_field($post, 'post_title', $lang, (string)$post->post_title);
    $en_content = (string)$fp->translate_post_field($post, 'post_content', $lang, (string)$post->post_content);
    $en_excerpt = (string)$fp->translate_post_field($post, 'post_excerpt', $lang, (string)$post->post_excerpt);
    $en_slug    = (string)$fp->translate_post_field($post, 'post_name', $lang, (string)$post->post_name);
  } catch (Throwable $e) {
    fwrite(STDERR, "Falang export failed for {$slug}: {$e->getMessage()}\n");
    exit(12);
  }

  if ($en_title === '' || $en_content === '' || $en_excerpt === '' || $en_slug === '' || $en_slug === $slug) {
    fwrite(STDERR, "Incomplete English staging translation for {$slug}.\n");
    exit(13);
  }

  $out[] = [
    'title'      => (string)$post->post_title,
    'slug'       => (string)$post->post_name,
    'excerpt'    => (string)$post->post_excerpt,
    'content'    => (string)$post->post_content,
    'en_title'   => $en_title,
    'en_slug'    => $en_slug,
    'en_excerpt' => $en_excerpt,
    'en_content' => $en_content,
  ];
}

if (count($out) !== 5) { exit(14); }
echo wp_json_encode(['verified' => true, 'count' => count($out), 'articles' => $out], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
