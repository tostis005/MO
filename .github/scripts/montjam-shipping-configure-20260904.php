<?php
/**
 * Configure Montjam WCFM Marketplace shipping by reusing only the active
 * WooCommerce shipping zones currently used by Hidalgo de la Jara.
 *
 * Rates requested:
 * - Spain mainland: €7.50 below €150; free from €150.
 * - Balearic Islands: €15 below €150; free from €150.
 * - Remaining European countries currently enabled for Hidalgo: €25 flat.
 *
 * This script never creates or edits WooCommerce zones/countries.
 */
if (!defined('ABSPATH')) { exit(1); }
if (!class_exists('WooCommerce')) { throw new RuntimeException('WooCommerce unavailable'); }

global $wpdb;

function msc_out($label, $value = null) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

function msc_flat_settings($cost) {
    return [
        'title' => 'Tarifa plana',
        'description' => '',
        'cost' => number_format((float)$cost, 2, '.', ''),
        'tax_status' => 'none',
        'class_cost_18' => '',
        'class_cost_no_class_cost' => '',
        'calculation_type' => '',
    ];
}

function msc_free_settings($minimum) {
    return [
        'title' => 'Envío gratis',
        'description' => '',
        'cost' => '0',
        'tax_status' => 'none',
        'min_amount' => number_format((float)$minimum, 0, '.', ''),
    ];
}

$montjam = get_user_by('login', 'montjam');
if (!$montjam) $montjam = get_user_by('login', 'Montjam');
if (!$montjam) throw new RuntimeException('Montjam vendor not found');
$montjam_id = (int)$montjam->ID;

$hidalgo_id = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key='store_name' AND meta_value=%s LIMIT 1",
    'Hidalgo de la Jara'
));
if (!$hidalgo_id) {
    $hidalgo_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE display_name=%s LIMIT 1",
        'Hidalgo de la Jara'
    ));
}
if (!$hidalgo_id) throw new RuntimeException('Hidalgo de la Jara vendor not found');

$wcfm_methods = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
$wcfm_locs = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_locations';
$core_zones = $wpdb->prefix . 'woocommerce_shipping_zones';
$core_locs = $wpdb->prefix . 'woocommerce_shipping_zone_locations';
foreach ([$wcfm_methods, $wcfm_locs, $core_zones, $core_locs] as $table) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        throw new RuntimeException('Required shipping table missing: ' . $table);
    }
}

// Only zones that still exist in WooCommerce and currently have an enabled Hidalgo method.
$zones = $wpdb->get_results($wpdb->prepare(
    "SELECT DISTINCT z.zone_id, z.zone_name, z.zone_order
       FROM `$core_zones` z
       INNER JOIN `$wcfm_methods` hm ON hm.zone_id=z.zone_id
      WHERE hm.vendor_id=%d AND hm.is_enabled=1
      ORDER BY z.zone_order, z.zone_id",
    $hidalgo_id
), ARRAY_A);
if (!$zones) throw new RuntimeException('No active Hidalgo shipping zones found');

$peninsula_zone = null;
$baleares_zone = null;
$europe_zones = [];
$country_codes = [];

foreach ($zones as $zone) {
    $zone_id = (int)$zone['zone_id'];
    $locations = $wpdb->get_results($wpdb->prepare(
        "SELECT location_code, location_type FROM `$core_locs` WHERE zone_id=%d ORDER BY location_type, location_code",
        $zone_id
    ), ARRAY_A);

    $location_codes = array_column($locations, 'location_code');
    $zone_name = (string)$zone['zone_name'];

    if (stripos($zone_name, 'Baleares') !== false || in_array('ES:PM', $location_codes, true)) {
        $baleares_zone = $zone_id;
        continue;
    }

    if (stripos($zone_name, 'España') !== false && stripos($zone_name, 'península') !== false) {
        $peninsula_zone = $zone_id;
        continue;
    }

    $countries = [];
    foreach ($locations as $loc) {
        if ($loc['location_type'] === 'country' && strtoupper((string)$loc['location_code']) !== 'ES') {
            $countries[] = strtoupper((string)$loc['location_code']);
        }
    }
    if ($countries) {
        $europe_zones[$zone_id] = $countries;
        foreach ($countries as $code) $country_codes[$code] = true;
    }
}

if (!$peninsula_zone) throw new RuntimeException('Active Hidalgo mainland Spain zone not found');
if (!$baleares_zone) throw new RuntimeException('Active Hidalgo Balearic Islands zone not found');
if (!$europe_zones) throw new RuntimeException('No active Hidalgo European country zones found');

$wpdb->query('START TRANSACTION');
try {
    // Replace Montjam's vendor-level methods only. Global WooCommerce zones are untouched.
    if ($wpdb->delete($wcfm_methods, ['vendor_id' => $montjam_id], ['%d']) === false) {
        throw new RuntimeException('Could not clear previous Montjam shipping methods');
    }
    // WCFM does not currently use vendor-specific location overrides for Hidalgo; clear any
    // accidental Montjam overrides so it uses the exact same global zones/countries.
    if ($wpdb->delete($wcfm_locs, ['vendor_id' => $montjam_id], ['%d']) === false) {
        throw new RuntimeException('Could not clear Montjam shipping location overrides');
    }

    $insert_method = function($zone_id, $method_id, array $settings) use ($wpdb, $wcfm_methods, $montjam_id) {
        $ok = $wpdb->insert($wcfm_methods, [
            'method_id' => $method_id,
            'zone_id' => (int)$zone_id,
            'vendor_id' => $montjam_id,
            'is_enabled' => 1,
            'settings' => maybe_serialize($settings),
        ], ['%s','%d','%d','%d','%s']);
        if ($ok === false) {
            throw new RuntimeException('Could not insert ' . $method_id . ' for zone ' . $zone_id . ': ' . $wpdb->last_error);
        }
    };

    // Spain mainland: €7.50, free from €150.
    $insert_method($peninsula_zone, 'flat_rate', msc_flat_settings(7.50));
    $insert_method($peninsula_zone, 'free_shipping', msc_free_settings(150));

    // Balearic Islands: €15, free from €150.
    $insert_method($baleares_zone, 'flat_rate', msc_flat_settings(15.00));
    $insert_method($baleares_zone, 'free_shipping', msc_free_settings(150));

    // Same active European country zones as Hidalgo, no extra countries: €25 flat.
    foreach (array_keys($europe_zones) as $zone_id) {
        $insert_method((int)$zone_id, 'flat_rate', msc_flat_settings(25.00));
    }

    $shipmeta = get_user_meta($montjam_id, '_wcfmmp_shipping', true);
    if (!is_array($shipmeta)) $shipmeta = [];
    $shipmeta['_wcfmmp_user_shipping_enable'] = 'yes';
    $shipmeta['_wcfmmp_user_shipping_type'] = 'by_zone';
    update_user_meta($montjam_id, '_wcfmmp_shipping', $shipmeta);

    $wpdb->query('COMMIT');
} catch (Throwable $e) {
    $wpdb->query('ROLLBACK');
    throw $e;
}

if (class_exists('WC_Cache_Helper')) {
    WC_Cache_Helper::get_transient_version('shipping', true);
}

// Verify exact persisted configuration.
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT instance_id, method_id, zone_id, vendor_id, is_enabled, settings
       FROM `$wcfm_methods`
      WHERE vendor_id=%d
      ORDER BY zone_id, method_id, instance_id",
    $montjam_id
), ARRAY_A);
foreach ($rows as &$row) $row['settings_decoded'] = maybe_unserialize($row['settings']);
unset($row);

$expected_count = 4 + count($europe_zones);
if (count($rows) !== $expected_count) {
    throw new RuntimeException('Unexpected Montjam shipping method count: ' . count($rows) . ' expected ' . $expected_count);
}

// Validate no vendor-specific countries/locations were created.
$location_override_count = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM `$wcfm_locs` WHERE vendor_id=%d",
    $montjam_id
));
if ($location_override_count !== 0) {
    throw new RuntimeException('Montjam has unexpected vendor-specific zone locations');
}

$countries_map = function_exists('WC') && WC()->countries ? WC()->countries->get_countries() : [];
$country_names = [];
foreach (array_keys($country_codes) as $code) {
    $country_names[$code] = $countries_map[$code] ?? $code;
}
ksort($country_names);

msc_out('MONTJAM_SHIPPING_SUCCESS', [
    'vendor_id' => $montjam_id,
    'hidalgo_reference_vendor_id' => $hidalgo_id,
    'peninsula' => ['zone_id' => $peninsula_zone, 'flat_rate' => '7.50', 'free_from' => '150'],
    'baleares' => ['zone_id' => $baleares_zone, 'flat_rate' => '15.00', 'free_from' => '150'],
    'europe_flat_rate' => '25.00',
    'europe_countries' => $country_names,
    'global_zones_modified' => false,
    'vendor_location_overrides' => $location_override_count,
    'methods' => $rows,
]);
