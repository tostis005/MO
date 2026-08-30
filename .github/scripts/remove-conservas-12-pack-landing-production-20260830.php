<?php
/** Remove overly specific 12-jar preserves landing and keep permanent redirects. */
if (!defined('ABSPATH')) { fwrite(STDERR, "WordPress is not loaded.\n"); exit(1); }

$key = 'conservas-12-tarros';
$es_slug = 'conservas-vegetales-packs-12-tarros';
$en_slug = 'vegetable-preserves-12-jar-cases';
$term = get_term_by('slug', 'conservas', 'product_cat');
if (!$term || is_wp_error($term)) throw new Exception('Conservas category not found');
$target = get_term_link($term);
if (is_wp_error($target)) throw new Exception($target->get_error_message());

$ids = get_posts(array(
  'post_type' => 'page',
  'post_status' => array('publish','draft','private','trash'),
  'posts_per_page' => 5,
  'fields' => 'ids',
  'meta_key' => '_emdo_ec_landing_key',
  'meta_value' => $key,
));
if (!$ids) {
  $p = get_page_by_path($es_slug, OBJECT, 'page');
  if ($p instanceof WP_Post) $ids = array((int)$p->ID);
}
foreach ($ids as $id) {
  if (get_post_status($id) !== 'trash') wp_trash_post((int)$id);
}

// Remove the old landing link from the commercial block in both languages.
$strip_link = function($html, $needle) {
  $html = preg_replace('#<a[^>]+href=["\'][^"\']*'.preg_quote($needle, '#').'/?["\'][^>]*>.*?</a>#iu', '', (string)$html);
  $html = preg_replace('/\s*·\s*(?=·|<\/p>)/u', ' ', $html);
  $html = preg_replace('/(<strong>[^<]+<\/strong>)\s*·\s*/u', '$1 ', $html);
  $html = preg_replace('/\s+·\s+·\s+/u', ' · ', $html);
  return $html;
};
$es_desc = $strip_link((string)$term->description, $es_slug);
$r = wp_update_term((int)$term->term_id, 'product_cat', array('description' => $es_desc));
if (is_wp_error($r)) throw new Exception($r->get_error_message());
$en_desc = (string)get_term_meta((int)$term->term_id, '_en_US_description', true);
if ($en_desc !== '') {
  $en_desc = $strip_link($en_desc, $en_slug);
  update_term_meta((int)$term->term_id, '_en_US_description', $en_desc);
}

// Persistent redirect in an MU plugin so it survives theme/plugin changes.
$mu_dir = WP_CONTENT_DIR . '/mu-plugins';
if (!is_dir($mu_dir) && !wp_mkdir_p($mu_dir)) throw new Exception('Cannot create mu-plugins directory');
$mu_file = $mu_dir . '/emdo-retired-seo-landings.php';
$plugin = <<<'PHP'
<?php
/** Redirect retired SEO landings to the most relevant live category. */
add_action('template_redirect', function () {
    if (is_admin()) return;
    $path = trim((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
    $redirects = array(
        'conservas-vegetales-packs-12-tarros' => home_url('/categoria-producto/conservas/'),
        'en/vegetable-preserves-12-jar-cases' => home_url('/categoria-producto/conservas/'),
    );
    if (isset($redirects[$path])) {
        wp_safe_redirect($redirects[$path], 301, 'El Mercado de Origen');
        exit;
    }
}, 0);
PHP;
if (file_put_contents($mu_file, $plugin) === false) throw new Exception('Cannot write redirect MU plugin');

flush_rewrite_rules(false);

echo wp_json_encode(array(
  'verified' => true,
  'trashed_ids' => array_values(array_map('intval', $ids)),
  'es_old' => home_url('/'.$es_slug.'/'),
  'en_old' => home_url('/en/'.$en_slug.'/'),
  'target' => $target,
  'mu_plugin' => $mu_file,
), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE).PHP_EOL;
