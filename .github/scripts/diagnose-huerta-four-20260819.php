<?php

if ( ! defined( 'ABSPATH' ) ) { exit(1); }

$urls = array(
 '134' => 'https://www.lahuertadeanamary.com/hortalizas-y-conservas/hortalizas-2/patatas-3/20-kg-de-patatas-blancas-variedad-kennebec-134.html',
 '26'  => 'https://www.lahuertadeanamary.com/hortalizas-y-conservas/hortalizas-2/pimientos-8/bolsa-300-gr-aprox-pimientos-de-padron-26.html',
 '133' => 'https://www.lahuertadeanamary.com/hortalizas-y-conservas/hortalizas-2/patatas-3/20-kg-de-patatas-rojas-red-pontiac-133.html',
 '113' => 'https://www.lahuertadeanamary.com/hortalizas-y-conservas/hortalizas-2/flores-de-calabacin-8-unidades-113.html',
);

$out = array();
foreach ( $urls as $id => $url ) {
    $r = wp_remote_get($url, array('timeout'=>25,'redirection'=>5,'user-agent'=>'Mozilla/5.0'));
    if (is_wp_error($r)) { $out[$id] = array('error'=>$r->get_error_message()); continue; }
    $html = (string) wp_remote_retrieve_body($r);
    if (function_exists('mb_check_encoding') && !mb_check_encoding($html,'UTF-8')) {
        $html = mb_convert_encoding($html,'UTF-8','Windows-1252');
    }
    $dom = new DOMDocument(); $prev = libxml_use_internal_errors(true); $dom->loadHTML('<?xml encoding="utf-8" ?>'.$html, LIBXML_NOWARNING|LIBXML_NOERROR|LIBXML_NONET); libxml_clear_errors(); libxml_use_internal_errors($prev);
    $xp = new DOMXPath($dom);
    $body = preg_replace('/\s+/u',' ', trim((string)$xp->query('//body')->item(0)->textContent));
    preg_match_all('/.{0,140}(?:€|precio|price|carrito|comprar).{0,180}/iu', $body, $m);
    $heads = array();
    foreach($xp->query('//h1|//h2|//h3|//h4|//h5|//p|//span|//div') ?: array() as $node) {
        $t = trim(preg_replace('/\s+/u',' ', (string)$node->textContent));
        if ($t !== '' && mb_strlen($t) < 500 && preg_match('/€|precio|price|kg|unidades|Te interesa|Consejos|Recetas/iu',$t)) $heads[]=$t;
        if(count($heads)>=60) break;
    }
    $out[$id] = array(
      'status'=>(int)wp_remote_retrieve_response_code($r),
      'content_type'=>(string)wp_remote_retrieve_header($r,'content-type'),
      'matches'=>array_values(array_unique($m[0] ?? array())),
      'nodes'=>array_values(array_unique($heads)),
      'body_first'=>mb_substr($body,0,5000),
    );
}

echo wp_json_encode($out, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT);
