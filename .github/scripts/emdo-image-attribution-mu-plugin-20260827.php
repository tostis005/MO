<?php
/**
 * Plugin Name: EMDO Featured Image Attribution
 * Description: Automatically shows featured-image credit on single blog posts when the stored image license requires attribution.
 * Version: 2026.08.27
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function emdo_image_attribution_license_requires_credit(string $license): bool {
    $license = trim($license);
    if ($license === '') { return false; }
    if (preg_match('/\b(nc|noncommercial|non-commercial)\b/i', $license)) { return false; }
    return (bool)preg_match('/\b(CC\s*BY(?:-SA)?(?:\s+[0-9.]+)?|Creative Commons Attribution(?:-ShareAlike)?)\b/i', $license);
}

function emdo_image_attribution_is_english(): bool {
    $uri = isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '';
    return (bool)preg_match('~^/en(?:/|$)~', $uri);
}

function emdo_image_attribution_meta(int $image_id, string $key): string {
    return trim((string)get_post_meta($image_id, $key, true));
}

function emdo_image_attribution_fallback_caption(int $image_id, string &$creator, string &$source, string &$license): void {
    $caption = trim((string)get_post_field('post_excerpt', $image_id));
    if ($caption === '') { return; }
    if ($creator === '' && preg_match('/Fotograf(?:í|i)a:\s*([^\.]+)\./iu', $caption, $m)) { $creator = trim($m[1]); }
    if ($source === '' && preg_match('~Fuente:\s*(https?://\S+)~iu', $caption, $m)) { $source = rtrim($m[1], '.,)'); }
    if ($license === '' && preg_match('/Licencia:\s*([^\.]+(?:\.[0-9]+)?)/iu', $caption, $m)) { $license = trim($m[1]); }
}

function emdo_image_attribution_credit_html(int $image_id): string {
    $creator = emdo_image_attribution_meta($image_id, '_emdo_image_creator');
    $source = emdo_image_attribution_meta($image_id, '_emdo_image_source_page');
    $license = emdo_image_attribution_meta($image_id, '_emdo_image_license');
    $license_url = emdo_image_attribution_meta($image_id, '_emdo_image_license_url');
    $changes = emdo_image_attribution_meta($image_id, '_emdo_image_changes');
    emdo_image_attribution_fallback_caption($image_id, $creator, $source, $license);

    if (!emdo_image_attribution_license_requires_credit($license)) { return ''; }
    if ($creator === '') { $creator = emdo_image_attribution_is_english() ? 'Image author' : 'Autor de la imagen'; }

    $source_label = '';
    if ($source !== '') {
        $host = (string)wp_parse_url($source, PHP_URL_HOST);
        if (stripos($host, 'wikimedia.org') !== false) { $source_label = 'Wikimedia Commons'; }
        elseif (stripos($host, 'flickr.com') !== false) { $source_label = 'Flickr'; }
        else { $source_label = preg_replace('/^www\./i', '', $host); }
    }

    $creator_html = esc_html($creator);
    if ($source !== '') {
        $label = $creator . ($source_label !== '' ? ' / '.$source_label : '');
        $creator_html = '<a class="emdo-image-credit-source" href="'.esc_url($source).'" rel="noopener noreferrer">'.esc_html($label).'</a>';
    }
    $license_html = esc_html($license);
    if ($license_url !== '') {
        $license_html = '<a class="emdo-image-credit-license" href="'.esc_url($license_url).'" rel="license noopener noreferrer">'.esc_html($license).'</a>';
    }

    $en = emdo_image_attribution_is_english();
    $prefix = $en ? 'Featured image: ' : 'Imagen destacada: ';
    $join = $en ? ', licensed under ' : ', licencia ';
    $change_text = '';
    if ($changes !== '') {
        $change_text = $en
            ? ' The site may resize or crop the image to fit the layout.'
            : ' La web puede redimensionar o recortar la imagen para adaptarla al diseño.';
    }
    return '<p class="emdo-image-credit"><small>'.$prefix.$creator_html.$join.$license_html.'.'.$change_text.'</small></p>';
}

add_filter('the_content', function($content) {
    if (is_admin() || is_feed() || !is_singular('post') || !in_the_loop() || !is_main_query()) { return $content; }
    $post_id = (int)get_queried_object_id();
    if ($post_id <= 0) { return $content; }
    $image_id = (int)get_post_thumbnail_id($post_id);
    if ($image_id <= 0) { return $content; }
    $credit = emdo_image_attribution_credit_html($image_id);
    if ($credit === '') { return $content; }
    if (strpos($content, 'class="emdo-image-credit"') !== false || strpos($content, "class='emdo-image-credit'") !== false) { return $content; }
    return $credit . "\n" . $content;
}, 8);

add_action('wp_head', function() {
    if (!is_singular('post')) { return; }
    echo '<style id="emdo-image-attribution-css">.emdo-image-credit{margin:.25rem 0 1.35rem;font-size:.78rem;line-height:1.45;color:#6b6b6b}.emdo-image-credit small{font-size:inherit}.emdo-image-credit a{color:inherit;text-decoration:underline;text-underline-offset:2px}.emdo-image-credit a:hover{color:#222}</style>';
}, 40);
