<?php
if (!defined('ABSPATH')) { exit(1); }

$payload_path = getenv('EMDO_LEGUME_JSON');
$rollback_path = getenv('EMDO_LEGUME_ROLLBACK');
if (!$payload_path || !is_readable($payload_path) || !$rollback_path) {
  fwrite(STDERR, "Missing import payload or rollback path.\n");
  exit(2);
}

$payload = json_decode((string)file_get_contents($payload_path), true);
if (!is_array($payload) || empty($payload['verified']) || (int)($payload['count'] ?? 0) !== 5 || count((array)($payload['articles'] ?? [])) !== 5) {
  fwrite(STDERR, "Invalid staging export payload.\n");
  exit(3);
}
$articles = $payload['articles'];

$category = get_category_by_slug('legumbres');
if (!$category instanceof WP_Term) {
  fwrite(STDERR, "Production category Legumbres not found.\n");
  exit(4);
}
$category_id = (int)$category->term_id;

function emdo_prod_generic_image_id(): int {
  $samples = [
    'cuando-echar-sal-garbanzos-lentejas-alubias-endurece-legumbres',
    'como-hacer-que-legumbres-den-menos-gases-remojo-coccion',
    'garbanzos-cuanto-tiempo-remojo-cuanto-tardan-cocerse',
  ];
  foreach ($samples as $slug) {
    $sample = get_page_by_path($slug, OBJECT, 'post');
    if ($sample instanceof WP_Post) {
      $id = (int)get_post_thumbnail_id($sample->ID);
      if ($id > 0) { return $id; }
    }
  }
  return 0;
}

function emdo_prod_en_language() {
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
    foreach ((array)$model->get_languages_list(['hide_default' => false]) as $lang) {
      $slug = is_object($lang) && isset($lang->slug) ? strtolower((string)$lang->slug) : '';
      $locale = is_object($lang) && isset($lang->locale) ? strtolower(str_replace('-', '_', (string)$lang->locale)) : '';
      if ($slug === 'en' || strpos($locale, 'en_') === 0) { return $lang; }
    }
  }
  return null;
}

function emdo_prod_save_falang_field(int $post_id, string $field, string $value, $lang): bool {
  if (!class_exists('\\Falang\\Core\\Post') || !is_object($lang)) { return false; }
  $locale = isset($lang->locale) ? str_replace('-', '_', (string)$lang->locale) : 'en_US';
  if ($locale === '') { $locale = 'en_US'; }
  $short = strpos($field, 'post_') === 0 ? substr($field, 5) : $field;
  $candidates = array_values(array_unique([
    '_' . $locale . '_' . $field,
    '_' . $locale . '_' . $short,
    $locale . '_' . $field,
    $locale . '_' . $short,
    '_' . strtolower($locale) . '_' . $field,
    '_' . strtolower($locale) . '_' . $short,
  ]));
  if ($field === 'post_name') {
    $candidates[] = '_' . $locale . '_slug';
    $candidates[] = $locale . '_slug';
  }
  $post = get_post($post_id);
  if (!$post instanceof WP_Post) { return false; }
  foreach ($candidates as $key) {
    update_post_meta($post_id, $key, $value);
    try {
      $fp = new \\Falang\\Core\\Post($post_id);
      $translated = $fp->translate_post_field($post, $field, $lang, (string)$post->{$field});
    } catch (Throwable $e) {
      $translated = '';
    }
    if ((string)$translated === $value) { return true; }
    delete_post_meta($post_id, $key, $value);
  }
  return false;
}

$image_id = emdo_prod_generic_image_id();
if ($image_id <= 0) {
  fwrite(STDERR, "Production provisional blog image not found.\n");
  exit(5);
}
$lang = emdo_prod_en_language();
if (!is_object($lang) || !class_exists('\\Falang\\Core\\Post')) {
  fwrite(STDERR, "Production Falang English API unavailable.\n");
  exit(6);
}

foreach ($articles as $article) {
  $slug = (string)($article['slug'] ?? '');
  if ($slug === '' || get_page_by_path($slug, OBJECT, 'post') instanceof WP_Post) {
    fwrite(STDERR, "Safety stop: target production slug already exists: {$slug}\n");
    exit(7);
  }
}

$created = [];
file_put_contents($rollback_path, wp_json_encode(['created_ids' => []]));

try {
  foreach ($articles as $article) {
    foreach (['title','slug','excerpt','content','en_title','en_slug','en_excerpt','en_content'] as $required) {
      if (!isset($article[$required]) || (string)$article[$required] === '') {
        throw new RuntimeException('Missing required field ' . $required);
      }
    }

    $post_id = wp_insert_post(wp_slash([
      'post_type'      => 'post',
      'post_status'    => 'publish',
      'post_title'     => (string)$article['title'],
      'post_name'      => (string)$article['slug'],
      'post_excerpt'   => (string)$article['excerpt'],
      'post_content'   => (string)$article['content'],
      'post_category'  => [$category_id],
      'comment_status' => 'closed',
      'ping_status'    => 'closed',
    ]), true);
    if (is_wp_error($post_id) || (int)$post_id <= 0) {
      throw new RuntimeException('wp_insert_post failed for ' . $article['slug']);
    }
    $post_id = (int)$post_id;
    $created[] = $post_id;
    file_put_contents($rollback_path, wp_json_encode(['created_ids' => $created]));
    set_post_thumbnail($post_id, $image_id);

    $fields = [
      'post_title'   => (string)$article['en_title'],
      'post_content' => (string)$article['en_content'],
      'post_excerpt' => (string)$article['en_excerpt'],
      'post_name'    => (string)$article['en_slug'],
    ];
    foreach ($fields as $field => $value) {
      if (!emdo_prod_save_falang_field($post_id, $field, $value, $lang)) {
        throw new RuntimeException("Falang save failed for {$article['slug']} / {$field}");
      }
    }
    $locale = isset($lang->locale) ? str_replace('-', '_', (string)$lang->locale) : 'en_US';
    if ($locale === '') { $locale = 'en_US'; }
    update_post_meta($post_id, '_' . $locale . '_published', '1');
  }

  flush_rewrite_rules(false);
} catch (Throwable $e) {
  foreach (array_reverse($created) as $id) { wp_delete_post((int)$id, true); }
  file_put_contents($rollback_path, wp_json_encode(['created_ids' => []]));
  fwrite(STDERR, "Import rolled back: {$e->getMessage()}\n");
  exit(20);
}

$result = [];
foreach ($articles as $article) {
  $post = get_page_by_path((string)$article['slug'], OBJECT, 'post');
  if (!$post instanceof WP_Post || $post->post_status !== 'publish' || (int)get_post_thumbnail_id($post->ID) !== $image_id) {
    foreach (array_reverse($created) as $id) { wp_delete_post((int)$id, true); }
    file_put_contents($rollback_path, wp_json_encode(['created_ids' => []]));
    fwrite(STDERR, "Verification failed after insert; rolled back.\n");
    exit(21);
  }
  $result[] = [
    'id' => (int)$post->ID,
    'slug' => (string)$article['slug'],
    'en_slug' => (string)$article['en_slug'],
    'title' => (string)$article['title'],
    'en_title' => (string)$article['en_title'],
    'permalink' => get_permalink($post->ID),
    'en_permalink' => home_url('/en/' . ltrim((string)$article['en_slug'], '/') . '/'),
  ];
}

echo wp_json_encode(['verified' => true, 'count' => count($result), 'posts' => $result], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
