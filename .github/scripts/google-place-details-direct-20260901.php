<?php
function mdo_google_details_find_key($value) {
    if (is_string($value) && preg_match('/AIza[0-9A-Za-z_-]{20,}/', $value, $m)) return $m[0];
    if (is_array($value)) foreach ($value as $v) { $k = mdo_google_details_find_key($v); if ($k) return $k; }
    if (is_object($value)) return mdo_google_details_find_key((array) $value);
    return '';
}
$key = mdo_google_details_find_key(get_option('grw_google_api_key', ''));
$place_id = 'ChIJbbIJi58nQg0RgJroXR8DG_U';
echo 'KEY_PRESENT=' . ($key ? 'yes' : 'no') . "\n";
echo 'PLACE_ID=' . $place_id . "\n";
if (!$key) return;
$url = add_query_arg([
    'place_id' => $place_id,
    'fields' => 'place_id,name,rating,user_ratings_total,formatted_address,formatted_phone_number,international_phone_number,website,url,business_status',
    'language' => 'es',
    'key' => $key,
], 'https://maps.googleapis.com/maps/api/place/details/json');
$r = wp_remote_get($url, ['timeout'=>25]);
if (is_wp_error($r)) { echo 'ERROR=' . $r->get_error_code() . "\n"; return; }
$j = json_decode(wp_remote_retrieve_body($r), true);
echo 'HTTP=' . wp_remote_retrieve_response_code($r) . "\n";
echo 'STATUS=' . ($j['status'] ?? '') . "\n";
if (!empty($j['error_message'])) echo 'API_ERROR=' . preg_replace('/AIza[0-9A-Za-z_-]+/', '[redacted]', $j['error_message']) . "\n";
$p = $j['result'] ?? [];
echo 'NAME=' . ($p['name'] ?? '') . "\n";
echo 'ADDRESS=' . ($p['formatted_address'] ?? '') . "\n";
echo 'PHONE=' . ($p['formatted_phone_number'] ?? '') . "\n";
echo 'INTL_PHONE=' . ($p['international_phone_number'] ?? '') . "\n";
echo 'WEBSITE=' . ($p['website'] ?? '') . "\n";
echo 'RATING=' . ($p['rating'] ?? '') . "\n";
echo 'REVIEWS=' . ($p['user_ratings_total'] ?? '') . "\n";
echo 'BUSINESS_STATUS=' . ($p['business_status'] ?? '') . "\n";
echo 'MAP_URL=' . ($p['url'] ?? '') . "\n";
