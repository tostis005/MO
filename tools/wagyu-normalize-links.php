<?php
/**
 * One-time normalizer for legacy/provisional Wagyu internal links and the
 * final small editorial QA fixes found by the automated preflight.
 */

declare(strict_types=1);

$root = dirname(__DIR__);
$files = glob($root . '/.github/data/editorial-wagyu-batch*-20260910/*.php') ?: [];
sort($files, SORT_NATURAL);

$aliases = [
    // Spanish.
    '/cuanto-wagyu-por-persona-racion/' => '/cuanto-wagyu-comprar-por-persona/',
    '/punto-coccion-wagyu-temperatura/' => '/punto-coccion-ideal-wagyu/',
    '/punto-coccion-wagyu/' => '/punto-coccion-ideal-wagyu/',
    '/como-elegir-wagyu-japones-guia-compra/' => '/como-elegir-wagyu-japones/',
    '/wagyu-vs-carne-madurada/' => '/wagyu-vs-carne-madurada-diferencias/',
    '/como-cocinar-wagyu-a5/' => '/como-cocinar-wagyu-japones-a5-casa/',
    '/wagyu-japones-a5/' => '/wagyu-japones-a5-que-es-que-significa/',
    '/wagyu-vs-kobe/' => '/wagyu-kobe-diferencias/',
    '/kobe-miyazaki-kagoshima/' => '/kobe-miyazaki-kagoshima-diferencias-wagyu-japones/',
    '/wagyu-japones-autentico/' => '/como-saber-wagyu-japones-autentico-certificado-trazabilidad/',
    '/wagyu-sarten-plancha-parrilla/' => '/wagyu-sarten-plancha-parrilla-que-metodo-elegir/',
    '/yakiniku-teppanyaki-shabu-sukiyaki/' => '/wagyu-yakiniku-teppanyaki-shabu-shabu-sukiyaki/',
    '/wagyu-crudo-tataki-carpaccio/' => '/wagyu-crudo-tataki-carpaccio-seguridad/',
    '/como-sazonar-wagyu/' => '/como-sazonar-wagyu-sal-pimienta-salsas/',

    // English.
    '/en/how-much-wagyu-per-person-serving-size/' => '/en/how-much-wagyu-per-person/',
    '/en/how-much-wagyu-per-person-portion/' => '/en/how-much-wagyu-per-person/',
    '/en/how-to-thaw-wagyu-correctly/' => '/en/how-to-thaw-wagyu-properly/',
    '/en/ideal-wagyu-doneness-temperature/' => '/en/ideal-wagyu-doneness/',
    '/en/how-to-choose-japanese-wagyu-buying-guide/' => '/en/how-to-choose-japanese-wagyu/',
    '/en/wagyu-vs-aged-beef/' => '/en/wagyu-vs-aged-beef-differences/',
    '/en/how-to-cook-a5-wagyu/' => '/en/how-to-cook-japanese-wagyu-a5-at-home/',
    '/en/japanese-wagyu-a5-explained/' => '/en/japanese-wagyu-a5-what-it-is-what-it-means/',
    '/en/kobe-miyazaki-kagoshima-wagyu/' => '/en/kobe-miyazaki-kagoshima-japanese-wagyu-differences/',
    '/en/how-to-identify-authentic-japanese-wagyu/' => '/en/how-to-tell-authentic-japanese-wagyu-certification-traceability/',
    '/en/how-to-choose-wagyu-cut/' => '/en/which-wagyu-cut-to-choose/',
    '/en/wagyu-pan-plancha-or-grill/' => '/en/wagyu-pan-griddle-grill-which-method/',
    '/en/yakiniku-teppanyaki-shabu-shabu-sukiyaki/' => '/en/wagyu-yakiniku-teppanyaki-shabu-shabu-sukiyaki/',
    '/en/wagyu-raw-tataki-carpaccio-safety/' => '/en/raw-wagyu-tataki-carpaccio-safety/',
    '/en/how-to-season-wagyu/' => '/en/how-to-season-wagyu-salt-pepper-sauces/',
    '/en/wagyu-doneness-guide/' => '/en/ideal-wagyu-doneness/',
    '/en/wagyu-vs-kobe/' => '/en/wagyu-vs-kobe-differences/',
    '/en/wagyu-kobe-diferencias/' => '/en/wagyu-vs-kobe-differences/',
];

$expansions = [
    '55-todo-wagyu-es-a5.php' => [
        '<h2>Which grade is best for a first tasting?</h2>' => '<h2>Why lower grades are not failed A5</h2>\n<p>A3 and A4 should not be treated as carcasses that simply missed the only grade worth buying. The Japanese system deliberately describes several levels of quality, and each can produce a different balance of marbling, muscle character and richness. For a shopper, that means the grade is useful descriptive information rather than a pass-or-fail authenticity test.</p>\n<p>This is also why comparing price requires context. A lower grade can still come from well-documented Japanese Wagyu, while cut, origin, portion size and intended cooking method may matter more to the eating experience than chasing A5 alone.</p>\n<h2>Which grade is best for a first tasting?</h2>',
    ],
    '56-numero-identificacion-wagyu.php' => [
        '<h2>How should buyers use the number?</h2>' => '<h2>How to distinguish the cattle ID from other numbers</h2>\n<p>A premium beef label may contain several codes at the same time: the individual cattle identification number, a carcass or slaughter reference, an importer or distributor lot and a retailer SKU. They are not interchangeable. The cattle ID is the reference tied to the animal within Japan’s traceability framework, while commercial lot numbers help operators manage portions after processing and distribution.</p>\n<p>For a retail buyer, the goal is not to memorise every code format. It is to be able to ask how the portion in the pack connects back to the underlying animal or carcass record. A transparent seller should be able to explain that chain, especially when the listing makes specific claims about Japanese origin, grade or regional provenance.</p>\n<h2>How should buyers use the number?</h2>',
    ],
    '57-universal-wagyu-mark.php' => [
        '<h2>Using the mark intelligently</h2>' => '<h2>What the mark can and cannot simplify for a shopper</h2>\n<p>The mark is most valuable as a fast first signal when several products use the word Wagyu. It can help separate Japanese-produced Wagyu from overseas production, but it cannot choose the right product for you. Grade, BMS, cut, portion size and regional programme still need to be read separately.</p>\n<p>That distinction matters in online shopping, where a logo may be visually prominent while the technical specification sits lower on the page. Treat the mark as the beginning of verification, not the end of it.</p>\n<h2>Using the mark intelligently</h2>',
    ],
];

$total = 0;
$touched = 0;
foreach ($files as $file) {
    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "Unable to read $file\n");
        exit(1);
    }

    $updated = $source;
    $fileCount = 0;
    foreach ($aliases as $old => $new) {
        $count = 0;
        $updated = str_replace($old, $new, $updated, $count);
        $fileCount += $count;
        $total += $count;
    }

    $base = basename($file);
    if (isset($expansions[$base])) {
        foreach ($expansions[$base] as $needle => $replacement) {
            if (str_contains($updated, $needle) && !str_contains($updated, strip_tags(strtok($replacement, "\n")))) {
                $count = 0;
                $updated = str_replace($needle, $replacement, $updated, $count);
                $fileCount += $count;
                $total += $count;
            }
        }
    }

    if ($updated !== $source) {
        if (file_put_contents($file, $updated) === false) {
            fwrite(STDERR, "Unable to write $file\n");
            exit(1);
        }
        $touched++;
        fwrite(STDOUT, basename($file) . ": $fileCount replacements\n");
    }
}

fwrite(STDOUT, "Applied $total final QA replacements across $touched payload files.\n");
