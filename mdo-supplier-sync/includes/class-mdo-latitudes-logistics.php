<?php
/** Latitudes product weights and SEUR Frio vendor-only shipping policy. */
if (!defined('ABSPATH')) exit;

final class MDO_Latitudes_Logistics {
    private const VENDOR_ID = 4949;
    private const FREE_FROM = 200.00;

    public static function init(): void {
        add_filter('woocommerce_package_rates', [__CLASS__, 'calculate_vendor_shipping_rates'], 9998, 2);
    }

    /** Upper bound of net weight advertised by the producer, expressed in kg. */
    public static function weight_from_title(string $title): ?array {
        $s = html_entity_decode($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $s = preg_replace('/\s+/u', ' ', trim($s));
        // 2x150 g / 2 unidades de 200 g: shipping weight is the entire pack.
        if (preg_match('/([1-9][0-9]?)\s*(?:[x×]|unidades?\s+de)\s*([0-9]+(?:[.,][0-9]+)?)\s*(g|grs?|gramos?)\.?(?=\W|$)/iu', $s, $m)) {
            $kg = (int)$m[1] * self::parse_number($m[2]) / 1000;
            return ['kg'=>round($kg,3), 'basis'=>'pack'];
        }
        // Ranges: use the upper bound for shipping, preventing undercharge.
        if (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*[-–—]\s*([0-9]+(?:[.,][0-9]+)?)\s*(kg|kilos?|g|grs?|gramos?)\.?(?=\W|$)/iu', $s, $m)) {
            $kg=max(self::parse_number($m[1]),self::parse_number($m[2])) * (preg_match('/^(g|gr|grs|gramos?)$/i',$m[3]) ? .001 : 1);
            return ['kg'=>round($kg,3),'basis'=>'title_upper_bound'];
        }
        if (preg_match('/([0-9]+(?:[.,][0-9]+)?)\s*(kg|kilos?|g|grs?|gramos?)\.?(?=\W|$)/iu', $s, $m)) {
            $kg=self::parse_number($m[1]) * (preg_match('/^(g|gr|grs|gramos?)$/i',$m[2]) ? .001 : 1);
            return ['kg'=>round($kg,3),'basis'=>'title_weight'];
        }
        // Supplier product pages publish additional WooCommerce weights:
        // ostrich egg 2.000 kg and goose egg box of two 0.300 kg.
        if (preg_match('/\bhuevo de avestruz\b/iu', $s)) return ['kg'=>2.0,'basis'=>'source_product_weight'];
        if (preg_match('/\bhuevo de oca\b/iu', $s) && preg_match('/\b2\s+unidades\b/iu',$s)) return ['kg'=>0.3,'basis'=>'source_product_weight'];
        return null;
    }

    private static function parse_number(string $raw): float {
        // Decimal commas and dots are used interchangeably in supplier names.
        return (float)str_replace(',', '.', $raw);
    }

    private static function vendor_package(array $package): bool {
        if (isset($package['vendor_id']) && (int)$package['vendor_id'] !== self::VENDOR_ID) return false;
        if (empty($package['contents']) || !is_array($package['contents'])) return false;
        foreach ($package['contents'] as $item) {
            $pid = absint($item['product_id'] ?? 0);
            if (!$pid || (int)get_post_field('post_author', $pid) !== self::VENDOR_ID) return false;
        }
        return true;
    }

    public static function free_shipping_eligible(array $package): bool {
        if (get_option('mdo_latitudes_weight_shipping_enabled') !== '1' || !self::vendor_package($package)) return false;
        return self::package_subtotal_gross($package) >= self::FREE_FROM;
    }

    private static function package_subtotal_gross(array $package): float {
        $total=0.0;
        foreach ($package['contents'] as $item) {
            // Discounted cart amount, including VAT when WooCommerce collects it.
            $total += (float)($item['line_total'] ?? 0) + (float)($item['line_tax'] ?? 0);
        }
        return round($total,2);
    }

    private static function region(array $package): string {
        $d=(array)($package['destination'] ?? []);
        $country=strtoupper((string)($d['country'] ?? ''));
        $postcode=preg_replace('/\D+/','',(string)($d['postcode'] ?? ''));
        $state=strtoupper((string)($d['state'] ?? ''));
        if ($country !== 'ES') return '';
        if (str_starts_with($postcode,'07') || $state === 'PM') return 'baleares';
        if (in_array(substr($postcode,0,2),['35','38','51','52'],true)) return '';
        return 'peninsula';
    }

    public static function calculate_vendor_shipping_rates(array $rates, array $package): array {
        if (get_option('mdo_latitudes_weight_shipping_enabled') !== '1' || !self::vendor_package($package)) return $rates;
        $region=self::region($package);
        if ($region === '') return $rates; // Portugal and excluded places keep their separate native handling.
        if (!$rates) return $rates;
        $wcfm = get_user_meta(self::VENDOR_ID,'_wcfmmp_shipping',true);
        if (!is_array($wcfm) || ($wcfm['_wcfmmp_user_shipping_type'] ?? '') !== 'by_zone') return $rates;
        $kg=0.0;
        foreach ($package['contents'] as $item) {
            $p=$item['data'] ?? null;
            if (!$p instanceof WC_Product) $p=wc_get_product((int)($item['variation_id'] ?? $item['product_id'] ?? 0));
            if (!$p || !is_numeric($p->get_weight()) || (float)$p->get_weight() <= 0) {
                return []; // A missing weight must never create an artificially cheap shipping rate.
            }
            $kg += (float)wc_get_weight((float)$p->get_weight(), 'kg') * max(1,(int)($item['quantity'] ?? 1));
        }
        $free=self::package_subtotal_gross($package) >= self::FREE_FROM;
        $cost=$free ? 0.00 : ($region === 'baleares' ? 29.00 : ($kg <= 4.00001 ? 13.45 : ($kg <= 7.00001 ? 16.45 : 21.45)));

        // Preserve the native WCFM/WooCommerce rate id to keep the vendor's order attribution.
        $picked=null;
        foreach ($rates as $key=>$rate) {
            if (!is_object($rate) || !method_exists($rate,'get_method_id') || !method_exists($rate,'set_cost')) continue;
            $method=(string)$rate->get_method_id();
            if ($method === 'wcfmmp_product_shipping_by_zone') { $picked=$key; break; }
            if ($picked === null && in_array($method,['flat_rate','free_shipping'],true)) $picked=$key;
        }
        if ($picked === null) return []; // Do not fall back to a non-WCFM courier without approval.
        $selected=$rates[$picked];
        $selected->set_label($free ? 'Seur Frío — envío gratuito' : 'Seur Frío');
        $selected->set_cost(wc_format_decimal($cost,2));
        if (method_exists($selected,'set_taxes')) $selected->set_taxes([]);
        return [$picked=>$selected];
    }
}
