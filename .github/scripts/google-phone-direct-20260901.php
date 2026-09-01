<?php
function mdo_google_phone_find_key($value) {
    if (is_string($value) && preg_match('/AIza[0-9A-Za-z_-]{20,}/', $value, $m)) return $m[0];
    if (is_array($value)) foreach ($value as $v) { $k = mdo_google_phone_find_key($v); if ($k) return $k; }
    if (is_object($value)) return mdo_google_phone_find_key((array) $value);
    return '';
}
$key = mdo_google_phone_find_key(get_option('grw_google_api_key', ''));
echo 'KEY_PRESENT=' . ($key ? 'yes' : 'no') . "\n";
if (!$key) return;
$find_url = add_query_arg([
    'input' => '+34603029509',
    'inputtype' => 'phonenumber',
    'fields' => 'place_id,name,formatted_address,rating,user_ratings_total',
    'language' => 'es',
    'key' => $key,
], 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json');
$r = wp_remote_get($find_url, ['timeout'=>25]);
if (is_wp_error($r)) { echo 'FIND_ERROR=' . $r->get_error_code() . "\n"; return; }
$j = json_decode(wp_remote_retrieve_body($r), true);
echo 'FIND_HTTP=' . wp_remote_retrieve_response_code($r) . "\n";
echo 'FIND_STATUS=' . ($j['status'] ?? '') . "\n";
if (!empty($j['error_message'])) echo 'FIND_API_ERROR=' . preg_replace('/AIza[0-9A-Za-z_-]+/', '[redacted]', $j['error_message']) . "\n";
$c = $j['candidates'][0] ?? [];
echo 'FIND_PLACE_ID=' . ($c['place_id'] ?? '') . "\n";
echo 'FIND_NAME=' . ($c['name'] ?? '') . "\n";
echo 'FIND_ADDRESS=' . ($c['formatted_address'] ?? '') . "\n";
echo 'FIND_RATING=' . ($c['rating'] ?? '') . "\n";
echo 'FIND_REVIEWS=' . ($c['user_ratings_total'] ?? '') . "\n";
$place_id = (string)($c['place_id'] ?? '');
if ($place_id === '') return;
$details_url = add_query_arg([
    'place_id' => $place_id,
    'fields' => 'place_id,name,rating,user_ratings_total,formatted_address,formatted_phone_number,international_phone_number,website,url,business_status',
    'language' => 'es',
    'key' => $key,
], 'https://maps.googleapis.com/maps/api/place/details/json');
$d = wp_remote_get($details_url, ['timeout'=>25]);
if (is_wp_error($d)) { echo 'DETAILS_ERROR=' . $d->get_error_code() . "\n"; return; }
$jd = json_decode(wp_remote_retrieve_body($d), true);
echo 'DETAILS_HTTP=' . wp_remote_retrieve_response_code($d) . "\n";
echo 'DETAILS_STATUS=' . ($jd['status'] ?? '') . "\n";
if (!empty($jd['error_message'])) echo 'DETAILS_API_ERROR=' . preg_replace('/AIza[0-9A-Za-z_-]+/', '[redacted]', $jd['error_message']) . "\n";
$p = $jd['result'] ?? [];
echo 'NAME=' . ($p['name'] ?? '') . "\n";
echo 'ADDRESS=' . ($p['formatted_address'] ?? '') . "\n";
echo 'PHONE=' . ($p['formatted_phone_number'] ?? '') . "\n";
echo 'INTL_PHONE=' . ($p['international_phone_number'] ?? '') . "\n";
echo 'WEBSITE=' . ($p['website'] ?? '') . "\n";
echo 'RATING=' . ($p['rating'] ?? '') . "\n";
echo 'REVIEWS=' . ($p['user_ratings_total'] ?? '') . "\n";
echo 'BUSINESS_STATUS=' . ($p['business_status'] ?? '') . "\n";
echo 'MAP_URL=' . ($p['url'] ?? '') . "\n";
