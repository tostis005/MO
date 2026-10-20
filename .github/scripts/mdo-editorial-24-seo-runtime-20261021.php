<?php
/** Editorial 24 SEO runtime. Read-only, scoped to the 48 exact bilingual URLs. */
if (!defined('ABSPATH')) exit;
$emdo_seo_24_routes = [
    'como-elegir-mejor-cafe-en-grano-para-casa' => true,
    'en/how-to-choose-the-best-whole-bean-coffee-for-home' => true,
    'cremas-de-cacao-como-elegir-ingredientes-diferencias' => true,
    'en/chocolate-spreads-how-to-choose-ingredients-and-differences' => true,
    'tipos-de-aguacate-hass-bacon-fuerte-diferencias' => true,
    'en/avocado-varieties-hass-bacon-fuerte-differences' => true,
    'cafe-en-grano-o-molido-diferencias-cual-comprar' => true,
    'en/whole-bean-vs-ground-coffee-which-to-buy' => true,
    'como-saber-si-aguacate-esta-maduro-y-madurarlo' => true,
    'en/how-to-tell-if-an-avocado-is-ripe-and-ripen-it' => true,
    'naranjas-de-mesa-o-de-zumo-diferencias-cuales-comprar' => true,
    'en/eating-or-juicing-oranges-differences-which-to-buy' => true,
    'cafe-de-especialidad-que-es-como-elegirlo' => true,
    'en/what-is-specialty-coffee-and-how-to-choose' => true,
    'tipos-de-mango-variedades-diferencias-cual-elegir' => true,
    'en/types-of-mango-varieties-differences-which-to-choose' => true,
    'comprar-fruta-online-como-elegir-fresca-calidad' => true,
    'en/buying-fresh-fruit-online-how-to-choose' => true,
    'que-cafe-comprar-para-cafetera-italiana' => true,
    'en/best-coffee-for-moka-pot-buying-guide' => true,
    'que-cafe-elegir-cafetera-superautomatica' => true,
    'en/how-to-choose-coffee-for-bean-to-cup-machine' => true,
    'temporada-aguacates-espana-variedades-cuando-comprar' => true,
    'en/spanish-avocado-season-when-to-buy-by-variety' => true,
    'como-conservar-aguacates-enteros-abiertos' => true,
    'en/how-to-store-whole-and-cut-avocados' => true,
    'como-saber-mango-maduro-elegir-bueno' => true,
    'en/how-to-tell-if-a-mango-is-ripe-and-choose-one' => true,
    'cuando-temporada-mango-espana-cosecha' => true,
    'en/mango-season-in-spain-harvest-calendar' => true,
    'cafe-arabica-robusta-diferencias-cual-elegir' => true,
    'en/arabica-vs-robusta-coffee-differences-which-to-buy' => true,
    'cafe-natural-torrefacto-mezcla-diferencias-elegir' => true,
    'en/natural-roast-torrefacto-coffee-blends-explained' => true,
    'que-cafe-elegir-buen-espresso-casa' => true,
    'en/how-to-choose-coffee-for-home-espresso' => true,
    'tipos-tueste-cafe-claro-medio-oscuro' => true,
    'en/light-medium-dark-coffee-roasts-how-to-choose' => true,
    'tipos-naranjas-variedades-sabor-cual-elegir' => true,
    'en/types-of-oranges-varieties-flavour-which-to-choose' => true,
    'temporada-naranjas-mandarinas-espana-calendario' => true,
    'en/orange-mandarin-season-spain-harvest-calendar' => true,
    'frutas-de-temporada-espana-calendario-mes-a-mes' => true,
    'en/seasonal-fruit-in-spain-month-by-month-calendar' => true,
    'frutas-tropicales-tipos-sabor-como-elegir' => true,
    'en/tropical-fruits-types-flavours-how-to-choose' => true,
    'cajas-fruta-temporada-domicilio-elegir-cantidad' => true,
    'en/seasonal-fruit-box-delivery-how-to-choose-size' => true,
];
$emdo_seo_24_route = trim((string)wp_parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH), '/');
if (!isset($emdo_seo_24_routes[$emdo_seo_24_route])) return;

// Install outermost buffer BEFORE other regular-plugin buffers. Outside the 48 routes no extra buffer.
add_action('muplugins_loaded', static function (): void {
    ob_start(static function (string $html): string {
        if (!is_singular('post')) return $html;
        $id = (int)get_queried_object_id();
        $plan_id = (int)get_post_meta($id, '_emdo_newprod_plan_id', true);
        if ($id < 1 || $plan_id < 1 || $plan_id > 24) return $html;
        $post = get_post($id);
        if (!$post instanceof WP_Post || $post->post_status !== 'publish') return $html;
        $en_slug = (string)get_post_meta($id, '_en_US_post_name', true);
        if ($en_slug === '') return $html;
        $es_url = (string)get_permalink($id);
        $en_url = (string)home_url('/en/' . $en_slug . '/');
        $english = (bool)preg_match('~^/en/~i', (string)wp_parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH));
        $meta_desc = (string)get_post_meta($id, $english ? '_en_US_seo_description' : '_emdo_seo_description', true);
        $post_title = $english ? (string)get_post_meta($id, '_en_US_post_title', true) : $post->post_title;
        $image_id = (int)get_post_thumbnail_id($id);
        $image_url = $image_id ? (string)wp_get_attachment_image_url($image_id, 'large') : '';
        $image_meta = $image_id ? wp_get_attachment_metadata($image_id) : [];
        $html = (string)preg_replace_callback('~<head\b[^>]*>.*?</head>~is',
            static function (array $m) use ($es_url, $en_url, $meta_desc, $post_title, $image_url, $image_meta): string {
                $head = $m[0];
                // Remove all conflicting language alternates, replacing with 3 exact native-language URLs.
                $head = (string)preg_replace('~<link\b(?=[^>]*\bhreflang\s*=)[^>]*>\s*~i', '', $head);
                $addon = "\n<link rel=\"alternate\" hreflang=\"es-ES\" href=\"" . esc_url($es_url) . "\" data-emdo-editorial=\"1\" />"
                       . "\n<link rel=\"alternate\" hreflang=\"en\" href=\"" . esc_url($en_url) . "\" data-emdo-editorial=\"1\" />"
                       . "\n<link rel=\"alternate\" hreflang=\"x-default\" href=\"" . esc_url($es_url) . "\" data-emdo-editorial=\"1\" />\n";
                if ($meta_desc !== '') {
                    // Use the carefully authored bilingual SEO text rather than a generic excerpt.
                    $head = (string)preg_replace('~<meta\b(?=[^>]*\bname\s*=\s*["\x27]description["\x27])[^>]*>\s*~i', '', $head);
                    $addon .= '<meta name="description" content="' . esc_attr($meta_desc) . '" data-emdo-editorial="1" />' . "\n";
                }
                if ($image_url !== '' && !preg_match('~<meta\b(?=[^>]*\bproperty\s*=\s*["\x27]og:image["\x27])[^>]*>~i', $head)) {
                    $addon .= '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
                    if (is_array($image_meta) && !empty($image_meta['width']) && !empty($image_meta['height'])) {
                        $addon .= '<meta property="og:image:width" content="' . (int)$image_meta['width'] . '" />' . "\n";
                        $addon .= '<meta property="og:image:height" content="' . (int)$image_meta['height'] . '" />' . "\n";
                    }
                }
                if ($image_url !== '' && !preg_match('~<meta\b(?=[^>]*\bname\s*=\s*["\x27]twitter:image["\x27])[^>]*>~i', $head)) {
                    if (!preg_match('~<meta\b(?=[^>]*\bname\s*=\s*["\x27]twitter:card["\x27])[^>]*>~i', $head)) {
                        $addon .= '<meta name="twitter:card" content="summary_large_image" />' . "\n";
                    }
                    $addon .= '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
                }
                if (!preg_match('~<meta\b(?=[^>]*\bproperty\s*=\s*["\x27]og:title["\x27])[^>]*>~i', $head)) {
                    $addon .= '<meta property="og:title" content="' . esc_attr($post_title) . '" />' . "\n";
                }
                if ($meta_desc !== '' && !preg_match('~<meta\b(?=[^>]*\bproperty\s*=\s*["\x27]og:description["\x27])[^>]*>~i', $head)) {
                    $addon .= '<meta property="og:description" content="' . esc_attr($meta_desc) . '" />' . "\n";
                }
                return (string)preg_replace('~</head>~i', $addon . '</head>', $head, 1);
            }, $html, 1);

        // The theme prints a title H1; another hook prints the same title as a plain H1.
        // Remove only that exact duplicate. This does not touch unrelated or different headings.
        if (preg_match_all('~<h1\b[^>]*>.*?</h1>~is', $html, $tags, PREG_OFFSET_CAPTURE) >= 2) {
            $first = $tags[0][0][0];
            $second = $tags[0][1][0];
            $normalize = static fn(string $s): string => trim(mb_strtolower(html_entity_decode(wp_strip_all_tags($s), ENT_QUOTES | ENT_HTML5, 'UTF-8')));
            if (str_contains($first, 'entry-title') && $normalize($first) === $normalize($second)
                && (bool)preg_match('~^<h1\s*>~i', $second)) {
                $html = substr_replace($html, '', $tags[0][1][1], strlen($second));
            }
        }
        return $html;
    });
}, -PHP_INT_MAX);
