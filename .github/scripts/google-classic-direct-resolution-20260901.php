<?php
function mdo_test_find_key($value) {
    if (is_string($value) && preg_match('/AIza[0-9A-Za-z_-]{20,}/', $value, $m)) return $m[0];
    if (is_array($value)) foreach ($value as $v) { $k = mdo_test_find_key($v); if ($k) return $k; }
    if (is_object($value)) return mdo_test_find_key((array) $value);
    return '';
}
$key = mdo_test_find_key(get_option('grw_google_api_key', ''));
echo 'KEY_PRESENT=' . ($key ? 'yes' : 'no') . "\n";
if (!$key) return;
$queries = [
    'El Mercado de Origen Getafe',
    'El Mercado de Origen 603029509',
    'elmercadodeorigen.com Getafe',
    'El Mercado de Origen Madrid',
];
foreach ($queries as $query) {
    echo 'QUERY=' . $query . "\n";
    $url = add_query_arg(['query'=>$query,'language'=>'es','region'=>'es','key'=>$key], 'https://maps.googleapis.com/maps/api/place/textsearch/json');
    $r = wp_remote_get($url, ['timeout'=>20]);
    if (is_wp_error($r)) { echo 'ERROR=' . $r->get_error_code() . "\n"; continue; }
    $j = json_decode(wp_remote_retrieve_body($r), true);
    echo 'HTTP=' . wp_remote_retrieve_response_code($r) . "\n";
    echo 'STATUS=' . ($j['status'] ?? '') . "\n";
    if (!empty($j['error_message'])) echo 'API_ERROR=' . preg_replace('/AIza[0-9A-Za-z_-]+/', '[redacted]', $j['error_message']) . "\n";
    $results = array_slice($j['results'] ?? [], 0, 5);
    echo 'RESULTS=' . count($results) . "\n";
    foreach ($results as $i => $p) {
        echo 'PLACE_' . ($i+1) . '_ID=' . ($p['place_id'] ?? '') . "\n";
        echo 'PLACE_' . ($i+1) . '_NAME=' . ($p['name'] ?? '') . "\n";
        echo 'PLACE_' . ($i+1) . '_ADDRESS=' . ($p['formatted_address'] ?? '') . "\n";
        echo 'PLACE_' . ($i+1) . '_RATING=' . ($p['rating'] ?? '') . "\n";
        echo 'PLACE_' . ($i+1) . '_REVIEWS=' . ($p['user_ratings_total'] ?? '') . "\n";
    }
    if ($results) break;
}
