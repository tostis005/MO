<?php
/**
 * One-time normalizer for legacy/provisional Wagyu internal links.
 * It only replaces exact relative paths with the canonical slugs declared
 * by the final 65-article payload set.
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

    if ($updated !== $source) {
        if (file_put_contents($file, $updated) === false) {
            fwrite(STDERR, "Unable to write $file\n");
            exit(1);
        }
        $touched++;
        fwrite(STDOUT, basename($file) . ": $fileCount replacements\n");
    }
}

fwrite(STDOUT, "Normalized $total links across $touched payload files.\n");
