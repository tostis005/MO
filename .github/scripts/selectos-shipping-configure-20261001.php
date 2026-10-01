<?php
/**
 * Configure Selectos de Castilla marketplace shipping.
 *
 * Enabled destinations only:
 * - Spain mainland: €12 below €120; free from €120.
 * - Portugal: €12 below €120; free from €120.
 * - Germany, Belgium, France, Greece, Hungary, Italy, Luxembourg, Netherlands,
 *   Poland, Czech Republic, Sweden and Switzerland: €25 flat rate.
 *
 * Baleares, Canarias and every unlisted country remain unavailable for this vendor.
 * Global WooCommerce zones are never modified.
 */
if (!defined('ABSPATH')) { exit(1); }
if (!class_exists('WooCommerce')) { throw new RuntimeException('WooCommerce unavailable'); }

global $wpdb;

function ssc_out($label, $value = null) {
    if (is_array($value) || is_object($value)) {
        $value = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
    echo $label . ($value === null ? '' : ': ' . (string)$value) . "\n";
}

function ssc_flat_settings($cost) {
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

function ssc_free_settings($minimum) {
    return [
        'title' => 'Envío gratis',
        'description' => '',
        'cost' => '0',
        'tax_status' => 'none',
        'min_amount' => number_format((float)$minimum, 0, '.', ''),
    ];
}

$selectos_id = 0;

// Prefer the EMDO supplier/vendor relationship, because that is the canonical
// assignment used by the Selectos catalogue importer.
if (class_exists('MDO_Database')) {
    $supplier_table = MDO_Database::table('suppliers');
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $supplier_table)) === $supplier_table) {
        $selectos_id = (int)$wpdb->get_var($wpdb->prepare(
            "SELECT vendor_user_id
               FROM `$supplier_table`
              WHERE LOWER(name)=LOWER(%s)
                 OR code=%s
                 OR source_url LIKE %s
              ORDER BY id DESC
              LIMIT 1",
            'Selectos de Castilla',
            'selectos-de-castilla',
            '%selectosdecastilla.com%'
        ));
    }
}

if (!$selectos_id) {
    $selectos_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT user_id
           FROM {$wpdb->usermeta}
          WHERE meta_key='store_name' AND LOWER(meta_value)=LOWER(%s)
          LIMIT 1",
        'Selectos de Castilla'
    ));
}
if (!$selectos_id) {
    $selectos_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE LOWER(display_name)=LOWER(%s) LIMIT 1",
        'Selectos de Castilla'
    ));
}
if (!$selectos_id) throw new RuntimeException('Selectos de Castilla vendor not found');

$selectos_user = get_user_by('id', $selectos_id);
if (!$selectos_user) throw new RuntimeException('Selectos vendor user does not exist');

// Hidalgo is used only as a reference to ensure we select existing, actively
// used marketplace zones. Its shipping rows are never changed.
$hidalgo_id = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT user_id
       FROM {$wpdb->usermeta}
      WHERE meta_key='store_name' AND LOWER(meta_value)=LOWER(%s)
      LIMIT 1",
    'Hidalgo de la Jara'
));
if (!$hidalgo_id) {
    $hidalgo_id = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT ID FROM {$wpdb->users} WHERE LOWER(display_name)=LOWER(%s) LIMIT 1",
        'Hidalgo de la Jara'
    ));
}
if (!$hidalgo_id) throw new RuntimeException('Hidalgo de la Jara vendor not found');

$wcfm_methods = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_methods';
$wcfm_locs    = $wpdb->prefix . 'wcfm_marketplace_shipping_zone_locations';
$core_zones   = $wpdb->prefix . 'woocommerce_shipping_zones';
$core_locs    = $wpdb->prefix . 'woocommerce_shipping_zone_locations';
$core_methods = $wpdb->prefix . 'woocommerce_shipping_zone_methods';

foreach ([$wcfm_methods, $wcfm_locs, $core_zones, $core_locs, $core_methods] as $table) {
    if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) {
        throw new RuntimeException('Required shipping table missing: ' . $table);
    }
}

// Find the same active mainland-Spain zone used by Hidalgo.
$peninsula_zone = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT z.zone_id
       FROM `$core_zones` z
       JOIN `$wcfm_methods` hm ON hm.zone_id=z.zone_id
      WHERE hm.vendor_id=%d
        AND hm.is_enabled=1
        AND LOWER(z.zone_name) LIKE %s
        AND LOWER(z.zone_name) LIKE %s
      ORDER BY z.zone_order, z.zone_id
      LIMIT 1",
    $hidalgo_id,
    '%espa%',
    '%pen%nsula%'
));

// Locale/collation variations can make accented matching unreliable.
// Fall back to the exact production zone name observed in the diagnostic.
if (!$peninsula_zone) {
    $peninsula_zone = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT z.zone_id
           FROM `$core_zones` z
           JOIN `$wcfm_methods` hm ON hm.zone_id=z.zone_id
          WHERE hm.vendor_id=%d
            AND hm.is_enabled=1
            AND z.zone_name=%s
          ORDER BY z.zone_order, z.zone_id
          LIMIT 1",
        $hidalgo_id,
        'España (península)'
    ));
}

// Find the dedicated Portugal country zone (PT), also actively used by Hidalgo.
$portugal_zone = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT DISTINCT z.zone_id
       FROM `$core_zones` z
       JOIN `$core_locs` l ON l.zone_id=z.zone_id
       JOIN `$wcfm_methods` hm ON hm.zone_id=z.zone_id
      WHERE hm.vendor_id=%d
        AND hm.is_enabled=1
        AND l.location_type='country'
        AND UPPER(l.location_code)='PT'
      ORDER BY z.zone_order, z.zone_id
      LIMIT 1",
    $hidalgo_id
));

if (!$peninsula_zone) throw new RuntimeException('Active Hidalgo mainland Spain zone not found');
if (!$portugal_zone) throw new RuntimeException('Active Hidalgo Portugal zone not found');
if ($peninsula_zone === $portugal_zone) throw new RuntimeException('Mainland and Portugal unexpectedly resolve to the same zone');

$international_codes = ['DE','BE','FR','GR','HU','IT','LU','NL','PL','CZ','SE','CH'];
$international_zones = [];

foreach ($international_codes as $country_code) {
    $candidate_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT z.zone_id
           FROM `$core_zones` z
           JOIN `$core_locs` l ON l.zone_id=z.zone_id
           JOIN `$core_methods` gm ON gm.zone_id=z.zone_id
          WHERE l.location_type='country'
            AND UPPER(l.location_code)=%s
            AND gm.method_id='wcfmmp_product_shipping_by_zone'
            AND gm.is_enabled=1
          ORDER BY z.zone_order,z.zone_id",
        $country_code
    ));

    $matched_zone = 0;
    foreach ($candidate_ids as $candidate_id) {
        $candidate_id = (int)$candidate_id;
        $country_codes = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT UPPER(location_code)
               FROM `$core_locs`
              WHERE zone_id=%d AND location_type='country'
              ORDER BY location_code",
            $candidate_id
        ));
        if ($country_codes === [$country_code]) {
            $matched_zone = $candidate_id;
            break;
        }
    }

    if (!$matched_zone) {
        throw new RuntimeException('Dedicated active WCFM zone not found for country ' . $country_code);
    }
    if (in_array($matched_zone, array_merge([$peninsula_zone, $portugal_zone], array_values($international_zones)), true)) {
        throw new RuntimeException('Shipping zone collision detected for country ' . $country_code);
    }

    $international_zones[$country_code] = $matched_zone;
}

// Safety checks: mainland must not contain Baleares (PM), Las Palmas (GC),
// or Santa Cruz de Tenerife (TF); Portugal must be PT-only at country level.
$peninsula_locs = $wpdb->get_results($wpdb->prepare(
    "SELECT location_code,location_type FROM `$core_locs` WHERE zone_id=%d",
    $peninsula_zone
), ARRAY_A);
$peninsula_codes = array_map(
    static fn($r) => strtoupper((string)$r['location_code']),
    $peninsula_locs
);
foreach (['ES:PM','ES:GC','ES:TF'] as $forbidden) {
    if (in_array($forbidden, $peninsula_codes, true)) {
        throw new RuntimeException('Mainland zone unexpectedly contains excluded region ' . $forbidden);
    }
}

$pt_country_codes = $wpdb->get_col($wpdb->prepare(
    "SELECT UPPER(location_code)
       FROM `$core_locs`
      WHERE zone_id=%d AND location_type='country'
      ORDER BY location_code",
    $portugal_zone
));
if ($pt_country_codes !== ['PT']) {
    throw new RuntimeException('Portugal zone is not dedicated exclusively to PT: ' . wp_json_encode($pt_country_codes));
}

// Snapshot before mutation for audit/rollback visibility.
$before_methods = $wpdb->get_results($wpdb->prepare(
    "SELECT instance_id,method_id,zone_id,vendor_id,is_enabled,settings
       FROM `$wcfm_methods`
      WHERE vendor_id=%d
      ORDER BY zone_id,method_id,instance_id",
    $selectos_id
), ARRAY_A);
foreach ($before_methods as &$row) $row['settings_decoded'] = maybe_unserialize($row['settings']);
unset($row);

$wpdb->query('START TRANSACTION');
try {
    // Remove every prior vendor-level shipping method so Selectos cannot
    // inherit Baleares, Canarias or other European destinations by mistake.
    if ($wpdb->delete($wcfm_methods, ['vendor_id' => $selectos_id], ['%d']) === false) {
        throw new RuntimeException('Could not clear previous Selectos shipping methods');
    }

    // Do not use vendor-specific location overrides: rely on the known global
    // zones exactly as Hidalgo does.
    if ($wpdb->delete($wcfm_locs, ['vendor_id' => $selectos_id], ['%d']) === false) {
        throw new RuntimeException('Could not clear Selectos shipping location overrides');
    }

    $insert_method = function($zone_id, $method_id, array $settings) use ($wpdb, $wcfm_methods, $selectos_id) {
        $ok = $wpdb->insert($wcfm_methods, [
            'method_id' => $method_id,
            'zone_id' => (int)$zone_id,
            'vendor_id' => $selectos_id,
            'is_enabled' => 1,
            'settings' => maybe_serialize($settings),
        ], ['%s','%d','%d','%d','%s']);
        if ($ok === false) {
            throw new RuntimeException('Could not insert ' . $method_id . ' for zone ' . $zone_id . ': ' . $wpdb->last_error);
        }
    };

    foreach ([$peninsula_zone, $portugal_zone] as $zone_id) {
        $insert_method($zone_id, 'flat_rate', ssc_flat_settings(12.00));
        $insert_method($zone_id, 'free_shipping', ssc_free_settings(120));
    }
    foreach ($international_zones as $country_code => $zone_id) {
        $insert_method($zone_id, 'flat_rate', ssc_flat_settings(25.00));
    }

    $shipmeta = get_user_meta($selectos_id, '_wcfmmp_shipping', true);
    if (!is_array($shipmeta)) $shipmeta = [];
    $shipmeta['_wcfmmp_user_shipping_enable'] = 'yes';
    $shipmeta['_wcfmmp_user_shipping_type'] = 'by_zone';
    if (update_user_meta($selectos_id, '_wcfmmp_shipping', $shipmeta) === false) {
        // update_user_meta may return false when unchanged, so confirm value.
        $check = get_user_meta($selectos_id, '_wcfmmp_shipping', true);
        if (!is_array($check)
            || ($check['_wcfmmp_user_shipping_enable'] ?? '') !== 'yes'
            || ($check['_wcfmmp_user_shipping_type'] ?? '') !== 'by_zone') {
            throw new RuntimeException('Could not enable WCFM by-zone shipping for Selectos');
        }
    }

    $wpdb->query('COMMIT');
} catch (Throwable $e) {
    $wpdb->query('ROLLBACK');
    throw $e;
}

if (class_exists('WC_Cache_Helper')) {
    WC_Cache_Helper::get_transient_version('shipping', true);
}
do_action('mdo_shipping_destinations_invalidate');

// Persisted-state verification.
$rows = $wpdb->get_results($wpdb->prepare(
    "SELECT instance_id,method_id,zone_id,vendor_id,is_enabled,settings
       FROM `$wcfm_methods`
      WHERE vendor_id=%d
      ORDER BY zone_id,method_id,instance_id",
    $selectos_id
), ARRAY_A);
foreach ($rows as &$row) $row['settings_decoded'] = maybe_unserialize($row['settings']);
unset($row);

$expected_method_count = 4 + count($international_zones);
if (count($rows) !== $expected_method_count) {
    throw new RuntimeException('Unexpected Selectos shipping method count: ' . count($rows) . ' expected ' . $expected_method_count);
}

$domestic_zones = [$peninsula_zone, $portugal_zone];
$allowed_zones = array_merge($domestic_zones, array_values($international_zones));
sort($allowed_zones);
$actual_zones = array_values(array_unique(array_map(static fn($r) => (int)$r['zone_id'], $rows)));
sort($actual_zones);
if ($actual_zones !== $allowed_zones) {
    throw new RuntimeException('Selectos has shipping methods outside the explicitly allowed destinations');
}

foreach ($domestic_zones as $zone_id) {
    $zone_rows = array_values(array_filter($rows, static fn($r) => (int)$r['zone_id'] === (int)$zone_id));
    if (count($zone_rows) !== 2) {
        throw new RuntimeException('Domestic zone ' . $zone_id . ' does not have exactly two Selectos methods');
    }

    $flat = null;
    $free = null;
    foreach ($zone_rows as $row) {
        if ((int)$row['is_enabled'] !== 1) {
            throw new RuntimeException('Disabled Selectos method found in domestic zone ' . $zone_id);
        }
        if ($row['method_id'] === 'flat_rate') $flat = $row['settings_decoded'];
        if ($row['method_id'] === 'free_shipping') $free = $row['settings_decoded'];
    }

    if (!is_array($flat) || (float)($flat['cost'] ?? -1) !== 12.0) {
        throw new RuntimeException('Flat rate is not €12 in domestic zone ' . $zone_id);
    }
    if (!is_array($free) || (float)($free['min_amount'] ?? -1) !== 120.0) {
        throw new RuntimeException('Free-shipping threshold is not €120 in domestic zone ' . $zone_id);
    }
}

foreach ($international_zones as $country_code => $zone_id) {
    $zone_rows = array_values(array_filter($rows, static fn($r) => (int)$r['zone_id'] === (int)$zone_id));
    if (count($zone_rows) !== 1) {
        throw new RuntimeException('International zone ' . $country_code . ' does not have exactly one Selectos method');
    }
    $row = $zone_rows[0];
    if ((int)$row['is_enabled'] !== 1 || $row['method_id'] !== 'flat_rate') {
        throw new RuntimeException('International zone ' . $country_code . ' is not enabled as flat rate only');
    }
    $flat = $row['settings_decoded'];
    if (!is_array($flat) || (float)($flat['cost'] ?? -1) !== 25.0) {
        throw new RuntimeException('Flat rate is not €25 for country ' . $country_code);
    }
}

$location_override_count = (int)$wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM `$wcfm_locs` WHERE vendor_id=%d",
    $selectos_id
));
if ($location_override_count !== 0) {
    throw new RuntimeException('Selectos has unexpected vendor-specific shipping locations');
}

$shipmeta_check = get_user_meta($selectos_id, '_wcfmmp_shipping', true);
if (!is_array($shipmeta_check)
    || ($shipmeta_check['_wcfmmp_user_shipping_enable'] ?? '') !== 'yes'
    || ($shipmeta_check['_wcfmmp_user_shipping_type'] ?? '') !== 'by_zone') {
    throw new RuntimeException('Selectos WCFM shipping meta verification failed');
}

$zone_names = [];
foreach ($allowed_zones as $zone_id) {
    $zone_names[$zone_id] = (string)$wpdb->get_var($wpdb->prepare(
        "SELECT zone_name FROM `$core_zones` WHERE zone_id=%d",
        $zone_id
    ));
}

$international_output = [];
foreach ($international_zones as $country_code => $zone_id) {
    $international_output[$country_code] = [
        'zone_id' => $zone_id,
        'zone_name' => $zone_names[$zone_id],
        'flat_rate' => '25.00',
    ];
}

ssc_out('SELECTOS_SHIPPING_SUCCESS', [
    'vendor_id' => $selectos_id,
    'vendor_login' => $selectos_user->user_login,
    'hidalgo_reference_vendor_id' => $hidalgo_id,
    'destinations' => [
        'spain_mainland' => [
            'zone_id' => $peninsula_zone,
            'zone_name' => $zone_names[$peninsula_zone],
            'flat_rate' => '12.00',
            'free_from' => '120',
        ],
        'portugal' => [
            'zone_id' => $portugal_zone,
            'zone_name' => $zone_names[$portugal_zone],
            'flat_rate' => '12.00',
            'free_from' => '120',
        ],
        'international' => $international_output,
    ],
    'baleares_enabled' => false,
    'canarias_enabled' => false,
    'unlisted_countries_enabled' => false,
    'global_zones_modified' => false,
    'vendor_location_overrides' => $location_override_count,
    'previous_methods' => $before_methods,
    'persisted_methods' => $rows,
]);
