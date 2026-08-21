<?php
/**
 * Read-only SEO audit for El Mercado de Origen production.
 * Intended to run via WP-CLI eval-file.
 */

if (!defined('ABSPATH')) {
    fwrite(STDERR, "WordPress not loaded\n");
    exit(1);
}

function emdo_audit_line($key, $value) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $key . ': ' . (string) $value . "\n";
}

function emdo_audit_html($url) {
    $res = wp_remote_get($url, array(
        'timeout' => 20,
        'redirection' => 5,
        'user-agent' => 'EMDO-SEO-Audit/2026-08-21',
        'sslverify' => true,
    ));
    if (is_wp_error($res)) {
        return array('url' => $url, 'error' => $res->get_error_message());
    }

    $code = wp_remote_retrieve_response_code($res);
    $headers = wp_remote_retrieve_headers($res);
    $body = wp_remote_retrieve_body($res);
    $out = array(
        'url' => $url,
        'status' => $code,
        'x_robots_tag' => isset($headers['x-robots-tag']) ? (string) $headers['x-robots-tag'] : '',
        'content_type' => isset($headers['content-type']) ? (string) $headers['content-type'] : '',
    );

    if (stripos($out['content_type'], 'text/html') === false) {
        $out['body_sample'] = substr(preg_replace('/\s+/', ' ', trim($body)), 0, 500);
        return $out;
    }

    if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $body, $m)) {
        $out['title'] = trim(wp_strip_all_tags(html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')));
    }
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']*)["\']/i', $body, $m) ||
        preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']description["\']/i', $body, $m)) {
        $out['description'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
    if (preg_match('/<meta[^>]+name=["\']robots["\'][^>]+content=["\']([^"\']*)["\']/i', $body, $m) ||
        preg_match('/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']robots["\']/i', $body, $m)) {
        $out['robots'] = $m[1];
    }
    if (preg_match('/<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']/i', $body, $m) ||
        preg_match('/<link[^>]+href=["\']([^"\']+)["\'][^>]+rel=["\']canonical["\']/i', $body, $m)) {
        $out['canonical'] = html_entity_decode($m[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    preg_match_all('/<h1\b[^>]*>(.*?)<\/h1>/is', $body, $hm);
    $h1s = array();
    foreach (($hm[1] ?? array()) as $raw) {
        $txt = trim(preg_replace('/\s+/', ' ', wp_strip_all_tags($raw)));
        if ($txt !== '') $h1s[] = $txt;
    }
    $out['h1'] = array_slice($h1s, 0, 5);

    preg_match_all('/<link\b[^>]+rel=["\'][^"\']*alternate[^"\']*["\'][^>]*>/i', $body, $alts);
    $hreflang = array();
    foreach (($alts[0] ?? array()) as $tag) {
        if (preg_match('/hreflang=["\']([^"\']+)["\']/i', $tag, $lm) && preg_match('/href=["\']([^"\']+)["\']/i', $tag, $um)) {
            $hreflang[$lm[1]] = html_entity_decode($um[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
    }
    $out['hreflang'] = $hreflang;

    $out['has_yoast_marker'] = stripos($body, 'yoast') !== false;
    $out['has_rankmath_marker'] = stripos($body, 'rank-math') !== false || stripos($body, 'rank_math') !== false;
    $out['has_aioseo_marker'] = stripos($body, 'aioseo') !== false;
    $out['body_bytes'] = strlen($body);
    return $out;
}

echo "=== EMDO SEO READ-ONLY AUDIT 2026-08-21 ===\n";
emdo_audit_line('home', home_url('/'));
emdo_audit_line('siteurl', site_url('/'));
emdo_audit_line('blogname', get_option('blogname'));
emdo_audit_line('blogdescription', get_option('blogdescription'));
emdo_audit_line('show_on_front', get_option('show_on_front'));
emdo_audit_line('page_on_front', get_option('page_on_front'));
emdo_audit_line('page_for_posts', get_option('page_for_posts'));
emdo_audit_line('permalink_structure', get_option('permalink_structure'));
emdo_audit_line('stylesheet', get_option('stylesheet'));
emdo_audit_line('template', get_option('template'));
emdo_audit_line('wp_version', get_bloginfo('version'));
emdo_audit_line('locale', get_locale());

$plugins = (array) get_option('active_plugins', array());
emdo_audit_line('active_plugins', $plugins);
if (is_multisite()) {
    emdo_audit_line('network_plugins', array_keys((array) get_site_option('active_sitewide_plugins', array())));
}

$front_id = (int) get_option('page_on_front');
if ($front_id) {
    $front = get_post($front_id);
    emdo_audit_line('front_page', array(
        'id' => $front_id,
        'title' => $front ? $front->post_title : '',
        'slug' => $front ? $front->post_name : '',
        'status' => $front ? $front->post_status : '',
        'content_bytes' => $front ? strlen((string) $front->post_content) : 0,
        'yoast_title' => get_post_meta($front_id, '_yoast_wpseo_title', true),
        'yoast_desc' => get_post_meta($front_id, '_yoast_wpseo_metadesc', true),
        'rankmath_title' => get_post_meta($front_id, 'rank_math_title', true),
        'rankmath_desc' => get_post_meta($front_id, 'rank_math_description', true),
    ));
}

foreach (array('inicio-bf', 'journal', 'blog', 'tienda', 'productores') as $slug) {
    $p = get_page_by_path($slug, OBJECT, 'page');
    if ($p) {
        emdo_audit_line('page_' . $slug, array(
            'id' => $p->ID,
            'title' => $p->post_title,
            'status' => $p->post_status,
            'modified_gmt' => $p->post_modified_gmt,
            'url' => get_permalink($p),
            'yoast_title' => get_post_meta($p->ID, '_yoast_wpseo_title', true),
            'yoast_desc' => get_post_meta($p->ID, '_yoast_wpseo_metadesc', true),
            'rankmath_title' => get_post_meta($p->ID, 'rank_math_title', true),
            'rankmath_desc' => get_post_meta($p->ID, 'rank_math_description', true),
        ));
    }
}

foreach (array(
    'wpseo_titles', 'wpseo', 'rank-math-options-titles', 'rank-math-options-general', 'aioseo_options',
    'rewrite_rules'
) as $opt_name) {
    $val = get_option($opt_name, null);
    if ($val === null) continue;
    if ($opt_name === 'rewrite_rules') {
        emdo_audit_line('rewrite_rule_count', is_array($val) ? count($val) : 0);
    } else {
        emdo_audit_line('option_present_' . $opt_name, true);
    }
}

$check_urls = array(
    home_url('/'),
    home_url('/tienda/'),
    home_url('/productores/'),
    home_url('/inicio-bf/'),
    home_url('/en/'),
    home_url('/en/journal/'),
    home_url('/robots.txt'),
    home_url('/sitemap_index.xml'),
    home_url('/wp-sitemap.xml'),
);
foreach ($check_urls as $url) {
    emdo_audit_line('http', emdo_audit_html($url));
}

// Sample published products and categories for template-level SEO inspection.
$products = get_posts(array('post_type' => 'product', 'post_status' => 'publish', 'numberposts' => 3, 'orderby' => 'date', 'order' => 'DESC'));
foreach ($products as $p) {
    emdo_audit_line('sample_product', emdo_audit_html(get_permalink($p)));
}

if (taxonomy_exists('product_cat')) {
    $cats = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => true, 'number' => 3, 'orderby' => 'count', 'order' => 'DESC'));
    if (!is_wp_error($cats)) {
        foreach ($cats as $cat) {
            $url = get_term_link($cat);
            if (!is_wp_error($url)) {
                emdo_audit_line('sample_category', array('term' => $cat->name, 'count' => $cat->count, 'http' => emdo_audit_html($url)));
            }
        }
    }
}

echo "=== END AUDIT ===\n";
