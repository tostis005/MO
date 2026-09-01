<?php
$options = [
    'trustindex-google-page-details',
    'trustindex-google-review-content',
    'widget_trustindex_google_widget',
];
$found = [];
foreach ($options as $name) {
    $value = get_option($name, '');
    $text = is_string($value) ? $value : wp_json_encode($value);
    if (!is_string($text) || $text === '') continue;
    if (preg_match_all('/ChIJ[A-Za-z0-9_-]{10,}/', $text, $m)) {
        foreach ($m[0] as $id) $found[$id] = true;
    }
}
echo 'PLACE_IDS=' . implode(',', array_keys($found)) . "\n";
