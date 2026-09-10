<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

const MDO_OPENAI_COMMERCE_VERSION = '2026.09.10.2';
const MDO_OPENAI_COMMERCE_OPTION = 'mdo_openai_commerce_settings';
const MDO_OPENAI_COMMERCE_REPORT_OPTION = 'mdo_openai_commerce_last_report';
const MDO_OPENAI_COMMERCE_LOG_OPTION = 'mdo_openai_commerce_logs';
const MDO_OPENAI_COMMERCE_MANIFEST = 'mdo-openai-manifest.jsonl';
const MDO_OPENAI_COMMERCE_TOMBSTONE_DAYS = 14;

interface MDO_Merchant_Feed_Provider_Interface_20260909 {
    public function key(): string;
    public function label(): string;
    public function filename(): string;
    public function generate(): array;
    public function preview( int $limit = 20 ): array;
}

function mdo_openai_default_settings_20260909(): array {
    return array(
        'merchant_status'                  => 'not_applied',
        'format'                           => 'native_jsonl_gz',
        'schedule'                         => 'six_hours',
        'delivery'                         => 'none',
        'auto_upload'                      => '0',
        'direct_feed_access_confirmed'     => '0',
        'market_confirmed'                 => '0',
        'google_compatible_confirmed'      => '0',
        'seller_model'                     => 'marketplace_vendor',
        'marketplace_enabled'              => '0',
        'shipping_capability_confirmed'    => '0',
        'shipping_representation'          => 'disabled',
        'shipping_country'                 => 'ES',
        'shipping_region'                  => '',
        'shipping_service_class'           => 'standard',
        'returns_enabled'                  => '0',
        'returns_accepts'                  => '1',
        'returns_deadline_days'            => '',
        'returns_policy_url'               => home_url( '/devoluciones-y-reembolsos/' ),
        'returns_excluded_product_ids'     => '',
        'returns_excluded_category_ids'    => '',
        'returns_excluded_vendor_ids'      => '',
        'excluded_product_ids'             => '',
        'excluded_category_ids'            => '',
        'excluded_vendor_ids'              => '',
        'sftp_enabled'                     => '0',
        'sftp_host'                        => '',
        'sftp_port'                        => '22',
        'sftp_username'                    => '',
        'sftp_auth_method'                 => 'private_key',
        'sftp_password_enc'                => '',
        'sftp_private_key_enc'             => '',
        'sftp_public_key_enc'              => '',
        'sftp_passphrase_enc'              => '',
        'sftp_remote_directory'            => '',
        'sftp_remote_filename'             => '',
        'api_enabled'                      => '0',
        'api_base_endpoint'                => '',
        'api_feed_id'                      => '',
        'api_key_enc'                      => '',
        'api_version'                      => '',
        'api_accept_language'              => 'es-ES',
    );
}

function mdo_openai_settings_20260909(): array {
    $saved = get_option( MDO_OPENAI_COMMERCE_OPTION, array() );
    return wp_parse_args( is_array( $saved ) ? $saved : array(), mdo_openai_default_settings_20260909() );
}

function mdo_openai_clean_text_20260909( $value, int $max = 5000 ): string {
    $value = strip_shortcodes( (string) $value );
    $value = html_entity_decode( wp_strip_all_tags( $value, true ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
    $value = trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
    return function_exists( 'mb_substr' ) ? mb_substr( $value, 0, $max, 'UTF-8' ) : substr( $value, 0, $max );
}

function mdo_openai_parse_id_list_20260909( $value ): array {
    $parts = is_array( $value ) ? $value : preg_split( '/[\s,;]+/', (string) $value );
    return array_values( array_unique( array_filter( array_map( 'absint', (array) $parts ) ) ) );
}

function mdo_openai_money_20260909( $amount, string $currency = '' ): string {
    if ( ! is_numeric( $amount ) ) { return ''; }
    $currency = strtoupper( '' !== $currency ? $currency : ( function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'EUR' ) );
    if ( ! preg_match( '/^[A-Z]{3}$/', $currency ) ) { return ''; }
    return number_format( max( 0, (float) $amount ), 2, '.', '' ) . ' ' . $currency;
}

function mdo_openai_upload_dir_20260909(): array {
    $uploads = wp_upload_dir();
    $dir = trailingslashit( (string) $uploads['basedir'] ) . 'mdo-openai-commerce';
    $url = trailingslashit( (string) $uploads['baseurl'] ) . 'mdo-openai-commerce';
    if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
    return array( 'dir' => $dir, 'url' => $url );
}

function mdo_openai_format_meta_20260909( ?string $format = null ): array {
    $format = $format ?: (string) mdo_openai_settings_20260909()['format'];
    $map = array(
        'native_jsonl_gz' => array( 'provider'=>'native', 'kind'=>'jsonl', 'delimiter'=>null, 'filename'=>'mercado-de-origen-products.jsonl.gz' ),
        'native_csv_gz'   => array( 'provider'=>'native', 'kind'=>'csv', 'delimiter'=>',', 'filename'=>'mercado-de-origen-products.csv.gz' ),
        'native_tsv_gz'   => array( 'provider'=>'native', 'kind'=>'tsv', 'delimiter'=>"\t", 'filename'=>'mercado-de-origen-products.tsv.gz' ),
        'google_csv_gz'   => array( 'provider'=>'google_compatible', 'kind'=>'csv', 'delimiter'=>',', 'filename'=>'mercado-de-origen-openai-google.csv.gz' ),
        'google_tsv_gz'   => array( 'provider'=>'google_compatible', 'kind'=>'tsv', 'delimiter'=>"\t", 'filename'=>'mercado-de-origen-openai-google.tsv.gz' ),
    );
    return $map[ $format ] ?? $map['native_jsonl_gz'];
}

function mdo_openai_feed_path_20260909( ?string $format = null ): string {
    $storage = mdo_openai_upload_dir_20260909();
    return trailingslashit( $storage['dir'] ) . mdo_openai_format_meta_20260909( $format )['filename'];
}

function mdo_openai_log_20260909( string $event, string $message, array $context = array() ): void {
    $sensitive = array( 'password', 'private_key', 'public_key', 'passphrase', 'api_key', 'authorization', 'secret' );
    foreach ( array_keys( $context ) as $key ) {
        foreach ( $sensitive as $needle ) {
            if ( false !== stripos( (string) $key, $needle ) ) { $context[ $key ] = '[redacted]'; break; }
        }
    }
    $logs = get_option( MDO_OPENAI_COMMERCE_LOG_OPTION, array() );
    $logs = is_array( $logs ) ? $logs : array();
    array_unshift( $logs, array( 'time'=>time(), 'event'=>sanitize_key( $event ), 'message'=>mdo_openai_clean_text_20260909( $message, 500 ), 'context'=>$context ) );
    update_option( MDO_OPENAI_COMMERCE_LOG_OPTION, array_slice( $logs, 0, 100 ), false );
}

function mdo_openai_vendor_name_20260909( int $vendor_id ): string {
    if ( $vendor_id <= 0 ) { return ''; }
    $profile = get_user_meta( $vendor_id, 'wcfmmp_profile_settings', true );
    if ( is_array( $profile ) && ! empty( $profile['store_name'] ) ) {
        $name = mdo_openai_clean_text_20260909( $profile['store_name'], 150 );
        if ( '' !== $name ) { return $name; }
    }
    $user = get_userdata( $vendor_id );
    return $user instanceof WP_User ? mdo_openai_clean_text_20260909( $user->display_name, 150 ) : '';
}

function mdo_openai_parent_context_20260909( WC_Product $product ): array {
    $is_variation = $product instanceof WC_Product_Variation;
    $parent_id = $is_variation ? (int) $product->get_parent_id() : (int) $product->get_id();
    $parent = $is_variation ? wc_get_product( $parent_id ) : $product;
    $post = get_post( $parent_id );
    return array( $parent_id, $parent instanceof WC_Product ? $parent : null, $post instanceof WP_Post ? $post : null );
}

function mdo_openai_explicit_brand_20260909( WC_Product $product ): string {
    list( $parent_id ) = mdo_openai_parent_context_20260909( $product );
    $ids = array_values( array_unique( array_filter( array( (int) $product->get_id(), $parent_id ) ) ) );
    foreach ( array( 'product_brand', 'pwb-brand', 'yith_product_brand', 'pa_marca', 'pa_productor' ) as $taxonomy ) {
        if ( ! taxonomy_exists( $taxonomy ) ) { continue; }
        foreach ( $ids as $id ) {
            $terms = wp_get_post_terms( $id, $taxonomy, array( 'fields'=>'names' ) );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                $name = mdo_openai_clean_text_20260909( reset( $terms ), 150 );
                if ( '' !== $name ) { return $name; }
            }
        }
    }
    foreach ( $ids as $id ) {
        foreach ( array( '_brand','brand','_product_brand','product_brand','_producer','producer','_productor','productor' ) as $key ) {
            $value = mdo_openai_clean_text_20260909( get_post_meta( $id, $key, true ), 150 );
            if ( '' !== $value ) { return $value; }
        }
    }
    return '';
}

function mdo_get_openai_brand( WC_Product $product ): string {
    list( , , $post ) = mdo_openai_parent_context_20260909( $product );
    $brand = mdo_openai_explicit_brand_20260909( $product );
    if ( '' === $brand && $post instanceof WP_Post ) { $brand = mdo_openai_vendor_name_20260909( (int) $post->post_author ); }
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_brand', $brand, $product ), 150 );
}

function mdo_openai_seller_name_20260909( WC_Product $product, array $settings ): string {
    list( , , $post ) = mdo_openai_parent_context_20260909( $product );
    $model = (string) ( $settings['seller_model'] ?? 'marketplace_vendor' );
    $seller = '';
    if ( 'mercado' === $model ) { $seller = 'El Mercado de Origen'; }
    elseif ( 'marketplace_vendor' === $model && $post instanceof WP_Post ) { $seller = mdo_openai_vendor_name_20260909( (int) $post->post_author ); }
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_seller_name', $seller, $product, $settings ), 150 );
}

function mdo_openai_marketplace_seller_20260909( WC_Product $product, array $settings ): string {
    $enabled = '1' === (string) ( $settings['marketplace_enabled'] ?? '0' );
    $marketplace = $enabled && 'marketplace_vendor' === (string) ( $settings['seller_model'] ?? '' ) ? 'El Mercado de Origen' : '';
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_marketplace_seller', $marketplace, $product, $settings ), 150 );
}

function mdo_openai_seller_url_20260909( WC_Product $product, array $settings = array() ): string {
    if ( 'mercado' === (string) ( $settings['seller_model'] ?? '' ) ) { return home_url( '/' ); }
    list( , , $post ) = mdo_openai_parent_context_20260909( $product );
    if ( ! $post instanceof WP_Post ) { return ''; }
    $vendor_id = (int) $post->post_author;
    $url = '';
    if ( function_exists( 'wcfmmp_get_store_url' ) ) {
        $candidate = wcfmmp_get_store_url( $vendor_id );
        if ( is_string( $candidate ) ) { $url = $candidate; }
    }
    return esc_url_raw( (string) apply_filters( 'mdo_openai_seller_url', $url, $vendor_id, $product, $settings ) );
}

function mdo_openai_item_id_20260909( WC_Product $product ): string {
    $existing = mdo_openai_clean_text_20260909( get_post_meta( $product->get_id(), '_mdo_openai_item_id', true ), 120 );
    if ( '' !== $existing ) { return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_item_id', $existing, $product ), 120 ); }
    $sku = mdo_openai_clean_text_20260909( $product->get_sku(), 100 );
    $candidate = '';
    if ( '' !== $sku ) {
        global $wpdb;
        $count = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = '_sku' AND meta_value = %s", $sku ) );
        if ( 1 === $count ) { $candidate = $sku; }
    }
    if ( '' === $candidate ) {
        $candidate = $product instanceof WC_Product_Variation ? 'wc-' . (int) $product->get_parent_id() . '-v' . (int) $product->get_id() : 'wc-' . (int) $product->get_id();
    }
    $candidate = mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_item_id', $candidate, $product ), 120 );
    if ( '' !== $candidate ) { update_post_meta( $product->get_id(), '_mdo_openai_item_id', $candidate ); }
    return $candidate;
}

function mdo_openai_group_id_20260909( int $parent_id ): string {
    $existing = mdo_openai_clean_text_20260909( get_post_meta( $parent_id, '_mdo_openai_group_id', true ), 120 );
    if ( '' !== $existing ) { return $existing; }
    $group = 'wcg-' . $parent_id;
    update_post_meta( $parent_id, '_mdo_openai_group_id', $group );
    return $group;
}

function mdo_openai_offer_id_20260909( WC_Product $product, string $item_id, string $seller ): string {
    $seller_key = sanitize_title( $seller );
    if ( '' === $seller_key ) { $seller_key = 'seller'; }
    $offer = substr( $seller_key, 0, 48 ) . '-' . substr( hash( 'sha256', $item_id ), 0, 20 );
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_offer_id', $offer, $product, $item_id, $seller ), 120 );
}

function mdo_openai_variant_dict_20260909( WC_Product $product, ?WC_Product $parent ): array {
    if ( ! $product instanceof WC_Product_Variation ) { return array(); }
    $dict = array();
    foreach ( $product->get_attributes() as $taxonomy => $value ) {
        $raw_taxonomy = str_replace( 'attribute_', '', (string) $taxonomy );
        $value = (string) $value;
        if ( '' === $value ) { continue; }
        $label = function_exists( 'wc_attribute_label' ) ? wc_attribute_label( $raw_taxonomy, $parent ) : $raw_taxonomy;
        $label = sanitize_title( mdo_openai_clean_text_20260909( $label, 80 ) );
        if ( '' === $label ) { $label = sanitize_title( $raw_taxonomy ); }
        $display = $value;
        if ( taxonomy_exists( $raw_taxonomy ) ) {
            $term = get_term_by( 'slug', $value, $raw_taxonomy );
            if ( $term instanceof WP_Term ) { $display = $term->name; }
        }
        $display = mdo_openai_clean_text_20260909( $display, 120 );
        if ( '' !== $display ) { $dict[ $label ] = $display; }
    }
    return (array) apply_filters( 'mdo_openai_variant_dict', $dict, $product, $parent );
}

function mdo_openai_title_20260909( WC_Product $product, ?WC_Product $parent, array $variant_dict ): string {
    $base = mdo_openai_clean_text_20260909( $parent instanceof WC_Product ? $parent->get_name() : $product->get_name(), 150 );
    if ( $product instanceof WC_Product_Variation && $variant_dict ) { $base = mdo_openai_clean_text_20260909( $base . ' – ' . implode( ', ', array_values( $variant_dict ) ), 150 ); }
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_title', $base, $product, $variant_dict ), 150 );
}

function mdo_openai_relevant_facts_20260909( WC_Product $product, ?WC_Product $parent ): array {
    $source = $parent instanceof WC_Product ? $parent : $product;
    $facts = array();
    $needles = array( 'dop'=>'DOP','denominacion'=>'DOP','raza'=>'Raza','alimentacion'=>'Alimentación','precinto'=>'Precinto','origen'=>'Origen','productor'=>'Productor','formato'=>'Formato','curacion'=>'Curación' );
    foreach ( (array) $source->get_attributes() as $attribute ) {
        if ( ! $attribute instanceof WC_Product_Attribute ) { continue; }
        $label = mdo_openai_clean_text_20260909( wc_attribute_label( $attribute->get_name(), $source ), 80 );
        $norm = strtolower( remove_accents( $label ) );
        $canonical = '';
        foreach ( $needles as $needle=>$name ) { if ( false !== strpos( $norm, $needle ) ) { $canonical = $name; break; } }
        if ( '' === $canonical ) { continue; }
        $values = $attribute->is_taxonomy() ? wc_get_product_terms( $source->get_id(), $attribute->get_name(), array( 'fields'=>'names' ) ) : $attribute->get_options();
        $text = mdo_openai_clean_text_20260909( implode( ', ', array_map( 'strval', (array) $values ) ), 300 );
        if ( '' !== $text ) { $facts[ $canonical ] = $text; }
    }
    $meta = array( '_dop'=>'DOP','dop'=>'DOP','_raza'=>'Raza','raza'=>'Raza','_alimentacion'=>'Alimentación','alimentacion'=>'Alimentación','_precinto'=>'Precinto','precinto'=>'Precinto','_origen'=>'Origen','origen'=>'Origen','_productor'=>'Productor','productor'=>'Productor','_formato'=>'Formato','formato'=>'Formato','_curacion'=>'Curación','curacion'=>'Curación' );
    foreach ( $meta as $key=>$label ) {
        if ( isset( $facts[ $label ] ) ) { continue; }
        $value = mdo_openai_clean_text_20260909( get_post_meta( $source->get_id(), $key, true ), 300 );
        if ( '' !== $value ) { $facts[ $label ] = $value; }
    }
    return $facts;
}

function mdo_openai_description_20260909( WC_Product $product, ?WC_Product $parent, string $brand ): string {
    $source = $parent instanceof WC_Product ? $parent : $product;
    $description = (string) $source->get_short_description();
    if ( '' === trim( wp_strip_all_tags( $description ) ) ) { $description = (string) $source->get_description(); }
    $description = mdo_openai_clean_text_20260909( $description, 4000 );
    if ( '' === $description ) { $description = mdo_openai_clean_text_20260909( $source->get_name(), 500 ); }
    $facts = mdo_openai_relevant_facts_20260909( $product, $parent );
    if ( '' !== $brand ) {
        $contains = function_exists( 'mb_stripos' ) ? false !== mb_stripos( $description, $brand, 0, 'UTF-8' ) : false !== stripos( $description, $brand );
        if ( ! $contains ) { $facts = array( 'Productor'=>$brand ) + $facts; }
    }
    if ( $facts ) {
        $parts = array();
        foreach ( $facts as $label=>$value ) { $parts[] = $label . ': ' . $value . '.'; }
        $description = trim( $description . ' ' . implode( ' ', $parts ) );
    }
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_description', $description, $product, $facts ), 5000 );
}

function mdo_openai_product_url_20260909( WC_Product $product, ?WC_Product $parent ): string {
    $url = $product->get_permalink();
    if ( ! is_string( $url ) || '' === $url ) { $url = $parent instanceof WC_Product ? $parent->get_permalink() : ''; }
    $url = is_string( $url ) ? remove_query_arg( array( 'mdo_country','session','wc-ajax' ), $url ) : '';
    return esc_url_raw( (string) apply_filters( 'mdo_openai_url', $url, $product ) );
}

function mdo_openai_image_urls_20260909( WC_Product $product, ?WC_Product $parent ): array {
    $main_id = (int) $product->get_image_id();
    if ( $main_id <= 0 && $parent instanceof WC_Product ) { $main_id = (int) $parent->get_image_id(); }
    $main = $main_id > 0 ? wp_get_attachment_image_url( $main_id, 'full' ) : '';
    $main = is_string( $main ) ? esc_url_raw( $main ) : '';
    $ids = array_map( 'absint', (array) $product->get_gallery_image_ids() );
    if ( ! $ids && $parent instanceof WC_Product ) { $ids = array_map( 'absint', (array) $parent->get_gallery_image_ids() ); }
    $additional = array();
    foreach ( array_slice( array_values( array_unique( array_filter( $ids ) ) ), 0, 10 ) as $id ) {
        $url = wp_get_attachment_image_url( $id, 'full' );
        if ( is_string( $url ) && '' !== $url && $url !== $main ) { $additional[] = esc_url_raw( $url ); }
    }
    return array( $main, array_values( array_unique( $additional ) ) );
}

function mdo_openai_availability_20260909( WC_Product $product ): string {
    $status = (string) $product->get_stock_status();
    $map = array( 'instock'=>'in_stock', 'outofstock'=>'out_of_stock', 'onbackorder'=>'backorder' );
    $availability = $map[ $status ] ?? 'unknown';
    if ( 'yes' === (string) get_post_meta( $product->get_id(), '_mdo_openai_preorder', true ) ) { $availability = 'pre_order'; }
    return (string) apply_filters( 'mdo_openai_availability', $availability, $product, $status );
}

function mdo_openai_availability_date_20260910( WC_Product $product ): string {
    $raw = trim( (string) get_post_meta( $product->get_id(), '_mdo_openai_availability_date', true ) );
    $raw = trim( (string) apply_filters( 'mdo_openai_availability_date', $raw, $product ) );
    if ( '' === $raw ) { return ''; }
    try {
        $date = new DateTimeImmutable( $raw );
        return $date->format( DATE_RFC3339 );
    } catch ( Throwable $e ) {
        return '';
    }
}

function mdo_openai_price_fields_20260909( WC_Product $product ): array {
    $raw_current = $product->get_price();
    if ( '' === $raw_current || ! is_numeric( $raw_current ) ) { return array( '', '', 0.0 ); }
    $current = (float) wc_get_price_to_display( $product, array( 'price'=>(float) $raw_current ) );
    if ( $current <= 0 ) { return array( '', '', 0.0 ); }
    $regular_raw = $product->get_regular_price();
    $sale_raw = $product->get_sale_price();
    $regular = is_numeric( $regular_raw ) ? (float) wc_get_price_to_display( $product, array( 'price'=>(float) $regular_raw ) ) : $current;
    $sale = is_numeric( $sale_raw ) ? (float) wc_get_price_to_display( $product, array( 'price'=>(float) $sale_raw ) ) : 0.0;
    if ( $product->is_on_sale() && $regular > 0 && $sale > 0 && $sale < $regular ) { return array( mdo_openai_money_20260909( $regular ), mdo_openai_money_20260909( $sale ), $sale ); }
    return array( mdo_openai_money_20260909( $current ), '', $current );
}

function mdo_openai_product_category_20260909( int $parent_id ): string {
    $value = function_exists( 'mdo_gmf_product_type_v1' ) ? mdo_gmf_product_type_v1( $parent_id, 'es' ) : '';
    return mdo_openai_clean_text_20260909( apply_filters( 'mdo_openai_product_category', $value, $parent_id ), 750 );
}

function mdo_openai_weight_fields_20260909( WC_Product $product, ?WC_Product $parent, array $variant_dict ): array {
    foreach ( $variant_dict as $value ) {
        if ( preg_match( '/\b\d+(?:[\.,]\d+)?\s*[-–—]\s*\d+(?:[\.,]\d+)?\s*(?:kg|g|lb|oz)\b/u', strtolower( remove_accents( (string) $value ) ) ) ) { return array(); }
    }
    $weight = $product->get_weight();
    if ( ( '' === $weight || ! is_numeric( $weight ) || (float) $weight <= 0 ) && $parent instanceof WC_Product ) { $weight = $parent->get_weight(); }
    if ( '' === $weight || ! is_numeric( $weight ) || (float) $weight <= 0 ) { return array(); }
    $unit = strtolower( (string) get_option( 'woocommerce_weight_unit', 'kg' ) );
    if ( 'lbs' === $unit ) { $unit = 'lb'; }
    return in_array( $unit, array( 'g','kg','oz','lb' ), true ) ? array( 'weight'=>(float) $weight, 'item_weight_unit'=>$unit ) : array();
}

function mdo_openai_reviews_20260909( WC_Product $product, ?WC_Product $parent ): array {
    $source = $parent instanceof WC_Product ? $parent : $product;
    $count = (int) $source->get_review_count();
    if ( $count <= 0 ) { return array(); }
    $rating = (float) $source->get_average_rating();
    if ( $rating < 0 || $rating > 5 ) { return array(); }
    return array( 'review_count'=>$count, 'star_rating'=>round( $rating, 2 ) );
}

function mdo_openai_id_matches_product_20260910( WC_Product $product, int $parent_id, array $ids ): bool {
    return in_array( (int) $product->get_id(), $ids, true ) || in_array( $parent_id, $ids, true );
}

function mdo_openai_category_matches_20260910( int $parent_id, array $ids ): bool {
    if ( ! $ids ) { return false; }
    $cats = wp_get_post_terms( $parent_id, 'product_cat', array( 'fields'=>'ids' ) );
    return ! is_wp_error( $cats ) && (bool) array_intersect( $ids, array_map( 'absint', (array) $cats ) );
}

function mdo_openai_returns_20260909( WC_Product $product, array $settings, int $parent_id = 0, int $vendor_id = 0 ): array {
    if ( '1' !== (string) ( $settings['returns_enabled'] ?? '0' ) ) { return array(); }
    if ( $parent_id <= 0 ) { list( $parent_id, , $post ) = mdo_openai_parent_context_20260909( $product ); if ( $post instanceof WP_Post ) { $vendor_id = (int) $post->post_author; } }
    if ( mdo_openai_id_matches_product_20260910( $product, $parent_id, mdo_openai_parse_id_list_20260909( $settings['returns_excluded_product_ids'] ?? '' ) ) ) { return array(); }
    if ( in_array( $vendor_id, mdo_openai_parse_id_list_20260909( $settings['returns_excluded_vendor_ids'] ?? '' ), true ) ) { return array(); }
    if ( mdo_openai_category_matches_20260910( $parent_id, mdo_openai_parse_id_list_20260909( $settings['returns_excluded_category_ids'] ?? '' ) ) ) { return array(); }
    $fields = array( 'accepts_returns'=>'1' === (string) ( $settings['returns_accepts'] ?? '0' ) );
    $days = $settings['returns_deadline_days'] ?? '';
    if ( is_numeric( $days ) && (int) $days >= 0 ) { $fields['return_deadline_in_days'] = (int) $days; }
    $url = esc_url_raw( (string) ( $settings['returns_policy_url'] ?? '' ) );
    if ( '' !== $url ) { $fields['return_policy'] = $url; }
    return (array) apply_filters( 'mdo_openai_returns', $fields, $product, $settings );
}

function mdo_openai_shipping_20260909( WC_Product $product, array $settings, int $vendor_id ): array {
    $representation = (string) ( $settings['shipping_representation'] ?? 'disabled' );
    if ( '1' !== (string) ( $settings['shipping_capability_confirmed'] ?? '0' ) || 'disabled' === $representation || ! function_exists( 'mdo_gmf_vendor_shipping_v1' ) || $vendor_id <= 0 ) { return array(); }
    $country = strtoupper( (string) ( $settings['shipping_country'] ?? 'ES' ) );
    $shipping = mdo_gmf_vendor_shipping_v1( $vendor_id, $country );
    if ( empty( $shipping['can_ship'] ) || ! isset( $shipping['cost'] ) || ! is_numeric( $shipping['cost'] ) ) { return array(); }
    $cost = max( 0.0, (float) $shipping['cost'] );
    $threshold = isset( $shipping['free_threshold'] ) && is_numeric( $shipping['free_threshold'] ) ? max( 0.0, (float) $shipping['free_threshold'] ) : 0.0;
    $raw_current = $product->get_price();
    if ( $threshold > 0 && is_numeric( $raw_current ) ) {
        $effective = (float) wc_get_price_to_display( $product, array( 'price'=>(float) $raw_current ) );
        if ( $effective + 0.0001 >= $threshold ) { $cost = 0.0; }
    }
    $price = mdo_openai_money_20260909( $cost );
    $fields = array();
    if ( 'shipping_price' === $representation ) { $fields['shipping_price'] = $price; }
    elseif ( 'tuple' === $representation ) {
        $region = mdo_openai_clean_text_20260909( $settings['shipping_region'] ?? '', 80 );
        $service = mdo_openai_clean_text_20260909( $settings['shipping_service_class'] ?? 'standard', 80 );
        $fields['shipping'] = $country . ':' . $region . ':' . $service . ':' . $price;
    }
    return (array) apply_filters( 'mdo_openai_shipping', $fields, $product, $settings, $shipping );
}

function mdo_openai_is_excluded_20260909( WC_Product $product, int $parent_id, int $vendor_id, array $settings ): bool {
    if ( 'yes' === (string) get_post_meta( $product->get_id(), '_mdo_openai_exclude', true ) || 'yes' === (string) get_post_meta( $parent_id, '_mdo_openai_exclude', true ) ) { return true; }
    if ( mdo_openai_id_matches_product_20260910( $product, $parent_id, mdo_openai_parse_id_list_20260909( $settings['excluded_product_ids'] ?? '' ) ) ) { return true; }
    if ( in_array( $vendor_id, mdo_openai_parse_id_list_20260909( $settings['excluded_vendor_ids'] ?? '' ), true ) ) { return true; }
    if ( mdo_openai_category_matches_20260910( $parent_id, mdo_openai_parse_id_list_20260909( $settings['excluded_category_ids'] ?? '' ) ) ) { return true; }
    return (bool) apply_filters( 'mdo_openai_product_excluded', false, $product, $settings );
}

function mdo_openai_is_search_eligible_20260909( WC_Product $product, WP_Post $post, array $settings ): bool {
    $eligible = 'publish' === $post->post_status && '' === (string) $post->post_password && 'hidden' !== (string) $product->get_catalog_visibility() && $product->is_purchasable();
    return (bool) apply_filters( 'mdo_openai_is_eligible_search', $eligible, $product, $settings );
}

function mdo_openai_brand_consistency_warning_20260909( WC_Product $product, ?WC_Product $parent, string $vendor_name ): string {
    $explicit = mdo_openai_explicit_brand_20260909( $product );
    $parent_explicit = $parent instanceof WC_Product ? mdo_openai_explicit_brand_20260909( $parent ) : '';
    if ( $product instanceof WC_Product_Variation && '' !== $explicit && '' !== $parent_explicit && 0 !== strcasecmp( $explicit, $parent_explicit ) ) {
        return 'La marca/productor de la variación (' . $explicit . ') no coincide con la del producto padre (' . $parent_explicit . ').';
    }
    if ( '' !== $explicit && '' !== $vendor_name && 0 !== strcasecmp( $explicit, $vendor_name ) ) {
        return 'La marca/productor explícita (' . $explicit . ') no coincide con el vendor WCFM (' . $vendor_name . '). Revisar si es intencionado.';
    }
    return '';
}

function mdo_openai_native_record_20260909( WC_Product $product, array $settings, array &$report ): ?array {
    list( $parent_id, $parent, $post ) = mdo_openai_parent_context_20260909( $product );
    if ( ! $post instanceof WP_Post ) { return null; }
    $vendor_id = (int) $post->post_author;
    if ( function_exists( 'mdo_gmf_vendor_is_active_v1' ) && ! mdo_gmf_vendor_is_active_v1( $vendor_id ) ) { ++$report['excluded']; return null; }
    if ( mdo_openai_is_excluded_20260909( $product, $parent_id, $vendor_id, $settings ) ) { ++$report['excluded']; return null; }
    if ( ! mdo_openai_is_search_eligible_20260909( $product, $post, $settings ) ) { ++$report['excluded']; return null; }

    $variant_dict = mdo_openai_variant_dict_20260909( $product, $parent );
    $brand = mdo_get_openai_brand( $product );
    $seller = mdo_openai_seller_name_20260909( $product, $settings );
    $item_id = mdo_openai_item_id_20260909( $product );
    list( $image, $additional ) = mdo_openai_image_urls_20260909( $product, $parent );
    list( $price, $sale_price ) = mdo_openai_price_fields_20260909( $product );
    $availability = mdo_openai_availability_20260909( $product );

    $record = array(
        'item_id' => $item_id,
        'title' => mdo_openai_title_20260909( $product, $parent, $variant_dict ),
        'description' => mdo_openai_description_20260909( $product, $parent, $brand ),
        'url' => mdo_openai_product_url_20260909( $product, $parent ),
        'brand' => $brand,
        'seller_name' => $seller,
        'image_url' => $image,
        'availability' => $availability,
        'price' => (string) apply_filters( 'mdo_openai_price', $price, $product ),
        'offer_id' => mdo_openai_offer_id_20260909( $product, $item_id, $seller ),
        'is_eligible_search' => true,
    );
    if ( '' !== $sale_price ) { $record['sale_price'] = $sale_price; }
    if ( in_array( $availability, array( 'pre_order','backorder' ), true ) ) {
        $date = mdo_openai_availability_date_20260910( $product );
        if ( '' !== $date ) { $record['availability_date'] = $date; }
    }
    if ( $product instanceof WC_Product_Variation ) {
        $record['group_id'] = mdo_openai_group_id_20260909( $parent_id );
        $record['listing_has_variations'] = true;
        $record['variant_dict'] = $variant_dict;
    }
    $marketplace = mdo_openai_marketplace_seller_20260909( $product, $settings );
    if ( '' !== $marketplace ) { $record['marketplace_seller'] = $marketplace; }
    $seller_url = mdo_openai_seller_url_20260909( $product, $settings );
    if ( '' !== $seller_url ) { $record['seller_url'] = $seller_url; }
    if ( $additional ) { $record['additional_image_urls'] = $additional; }
    $category = mdo_openai_product_category_20260909( $parent_id );
    if ( '' !== $category ) { $record['product_category'] = $category; }
    $record += mdo_openai_weight_fields_20260909( $product, $parent, $variant_dict );
    $record += mdo_openai_reviews_20260909( $product, $parent );
    $record += mdo_openai_shipping_20260909( $product, $settings, $vendor_id );
    $record += mdo_openai_returns_20260909( $product, $settings, $parent_id, $vendor_id );
    if ( function_exists( 'mdo_gmf_gtin_v1' ) ) { $gtin = mdo_gmf_gtin_v1( $product, $parent ); if ( '' !== $gtin ) { $record['gtin'] = $gtin; } }
    if ( function_exists( 'mdo_gmf_mpn_v1' ) ) { $mpn = mdo_gmf_mpn_v1( $product, $parent ); if ( '' !== $mpn ) { $record['mpn'] = $mpn; } }

    $warning = mdo_openai_brand_consistency_warning_20260909( $product, $parent, mdo_openai_vendor_name_20260909( $vendor_id ) );
    if ( '' !== $warning ) { mdo_openai_report_issue_20260909( $report, 'warning', $item_id, $warning ); }
    return (array) apply_filters( 'mdo_openai_native_record', $record, $product, $settings );
}

function mdo_openai_google_record_20260909( WC_Product $product, array $settings, array &$report ): ?array {
    if ( '1' !== (string) ( $settings['google_compatible_confirmed'] ?? '0' ) ) {
        mdo_openai_report_issue_20260909( $report, 'error', '', 'El formato Google-compatible está bloqueado hasta que OpenAI confirme y registre esta integración para la cuenta.' );
        return null;
    }
    $native = mdo_openai_native_record_20260909( $product, $settings, $report );
    if ( ! is_array( $native ) ) { return null; }
    $availability_map = array( 'in_stock'=>'in_stock','out_of_stock'=>'out_of_stock','pre_order'=>'preorder','backorder'=>'backorder' );
    $id = (string) ( $native['item_id'] ?? '' );
    if ( ! isset( $availability_map[ $native['availability'] ] ) ) {
        mdo_openai_report_issue_20260909( $report, 'error', $id, 'Google-compatible no admite availability=' . (string) $native['availability'] . '.' );
        return null;
    }
    if ( in_array( $native['availability'], array( 'pre_order','backorder' ), true ) && empty( $native['availability_date'] ) ) {
        mdo_openai_report_issue_20260909( $report, 'error', $id, 'Google-compatible exige availability_date para preorder/backorder; no se inventa una fecha.' );
        return null;
    }
    $row = array(
        'id'=>$native['item_id'], 'title'=>$native['title'], 'description'=>$native['description'], 'link'=>$native['url'],
        'image_link'=>$native['image_url'], 'availability'=>$availability_map[ $native['availability'] ], 'price'=>$native['price'], 'brand'=>$native['brand'],
    );
    if ( ! empty( $native['availability_date'] ) ) { $row['availability_date'] = $native['availability_date']; }
    if ( ! empty( $native['sale_price'] ) ) { $row['sale_price'] = $native['sale_price']; }
    if ( ! empty( $native['gtin'] ) ) { $row['gtin'] = $native['gtin']; }
    if ( ! empty( $native['mpn'] ) ) { $row['mpn'] = $native['mpn']; }
    if ( empty( $native['gtin'] ) && empty( $native['mpn'] ) ) { $row['identifier_exists'] = 'no'; }
    if ( ! empty( $native['group_id'] ) ) { $row['item_group_id'] = $native['group_id']; }
    if ( ! empty( $native['additional_image_urls'] ) ) { $row['additional_image_link'] = implode( ',', (array) $native['additional_image_urls'] ); }
    if ( ! empty( $native['product_category'] ) ) { $row['product_type'] = $native['product_category']; }
    return (array) apply_filters( 'mdo_openai_google_compatible_record', $row, $product, $settings, $native );
}

function mdo_openai_report_template_20260909(): array {
    return array( 'generated_at'=>time(), 'duration_ms'=>0, 'parents'=>0, 'variants'=>0, 'exported'=>0, 'excluded'=>0, 'errors'=>0, 'warnings'=>0, 'retired'=>0, 'issues'=>array(), 'filename'=>'', 'format'=>'', 'bytes'=>0 );
}

function mdo_openai_report_issue_20260909( array &$report, string $severity, string $item_id, string $message ): void {
    $severity = 'warning' === $severity ? 'warning' : 'error';
    ++$report[ 'warning' === $severity ? 'warnings' : 'errors' ];
    if ( count( $report['issues'] ) < 500 ) { $report['issues'][] = array( 'severity'=>$severity, 'item_id'=>$item_id, 'message'=>mdo_openai_clean_text_20260909( $message, 500 ) ); }
}

function mdo_openai_parse_money_20260909( $value ): ?array {
    if ( ! is_string( $value ) || ! preg_match( '/^((?:0|[1-9]\d*)\.\d{2})\s([A-Z]{3})$/', trim( $value ), $m ) ) { return null; }
    return array( (float) $m[1], $m[2] );
}

function mdo_openai_validate_record_20260909( array $record, string $mode, array &$state, array &$report ): bool {
    $native = 'native' === $mode;
    $id = (string) ( $record[ $native ? 'item_id' : 'id' ] ?? '' );
    $required = $native ? array( 'item_id','title','description','url','brand','seller_name','image_url','availability','price' ) : array( 'id','title','description','link','image_link','availability','price','brand' );
    $critical = false;
    foreach ( $required as $key ) {
        if ( ! isset( $record[ $key ] ) || '' === trim( (string) $record[ $key ] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'Campo obligatorio vacío: ' . $key ); $critical = true; }
    }
    if ( '' !== $id ) {
        if ( isset( $state['item_ids'][ $id ] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'item_id/id duplicado.' ); $critical = true; }
        $state['item_ids'][ $id ] = true;
    }
    $url_key = $native ? 'url' : 'link';
    $image_key = $native ? 'image_url' : 'image_link';
    foreach ( array( $url_key, $image_key ) as $key ) {
        $url = (string) ( $record[ $key ] ?? '' );
        if ( '' !== $url && ! filter_var( $url, FILTER_VALIDATE_URL ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'URL inválida en ' . $key ); $critical = true; }
        elseif ( '' !== $url && 0 !== stripos( $url, 'https://' ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'La URL debe ser HTTPS en ' . $key ); $critical = true; }
    }
    $availability = (string) ( $record['availability'] ?? '' );
    $allowed = $native ? array( 'in_stock','out_of_stock','pre_order','backorder','unknown' ) : array( 'in_stock','out_of_stock','preorder','backorder' );
    if ( ! in_array( $availability, $allowed, true ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'availability no válida: ' . $availability ); $critical = true; }
    if ( ! $native && in_array( $availability, array( 'preorder','backorder' ), true ) && empty( $record['availability_date'] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'availability_date es obligatorio para preorder/backorder en Google-compatible.' ); $critical = true; }
    $price = mdo_openai_parse_money_20260909( $record['price'] ?? null );
    if ( null === $price || $price[0] <= 0 ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'price inválido.' ); $critical = true; }
    if ( ! empty( $record['sale_price'] ) ) {
        $sale = mdo_openai_parse_money_20260909( $record['sale_price'] );
        if ( null === $sale || null === $price || $sale[1] !== $price[1] || $sale[0] <= 0 || $sale[0] >= $price[0] ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'sale_price debe ser > 0, menor que price y usar la misma moneda.' ); $critical = true; }
    }
    if ( $native ) {
        if ( isset( $record['group_id'] ) && (string) $record['group_id'] === $id ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'group_id no puede ser igual a item_id.' ); $critical = true; }
        if ( isset( $record['group_id'], $record['variant_dict'] ) ) {
            $variant_key = (string) $record['group_id'] . '|' . wp_json_encode( $record['variant_dict'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
            if ( isset( $state['variants'][ $variant_key ] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'variant_dict duplicado dentro del mismo group_id.' ); $critical = true; }
            $state['variants'][ $variant_key ] = true;
        }
        if ( isset( $record['gtin'] ) && function_exists( 'mdo_gmf_valid_gtin_v1' ) && '' === mdo_gmf_valid_gtin_v1( (string) $record['gtin'] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'GTIN inválido.' ); $critical = true; }
        if ( isset( $record['star_rating'] ) && ( (float) $record['star_rating'] < 0 || (float) $record['star_rating'] > 5 ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'star_rating fuera de 0-5.' ); $critical = true; }
        if ( isset( $record['star_rating'] ) xor isset( $record['review_count'] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'review_count y star_rating deben corresponder al mismo agregado.' ); $critical = true; }
        if ( isset( $record['shipping_price'], $record['shipping'] ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'No enviar shipping_price y shipping simultáneamente.' ); $critical = true; }
        if ( isset( $record['shipping_price'] ) ) {
            $shipping = mdo_openai_parse_money_20260909( $record['shipping_price'] );
            if ( null === $shipping || $shipping[0] < 0 ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'shipping_price inválido.' ); $critical = true; }
        }
        if ( isset( $record['weight'] ) && ( ! is_numeric( $record['weight'] ) || (float) $record['weight'] <= 0 || ! in_array( (string) ( $record['item_weight_unit'] ?? '' ), array( 'g','kg','oz','lb' ), true ) ) ) { mdo_openai_report_issue_20260909( $report, 'error', $id, 'Peso/unidad de peso inválidos.' ); $critical = true; }
    }
    return ! $critical;
}

function mdo_openai_product_query_args_20260909( int $page, int $batch = 100 ): array {
    return array( 'post_type'=>'product', 'post_status'=>'publish', 'posts_per_page'=>max( 20, min( 500, $batch ) ), 'paged'=>max( 1, $page ), 'fields'=>'ids', 'orderby'=>'ID', 'order'=>'ASC', 'has_password'=>false, 'no_found_rows'=>false, 'ignore_sticky_posts'=>true, 'suppress_filters'=>true, 'update_post_meta_cache'=>false, 'update_post_term_cache'=>false );
}

function mdo_openai_foreach_product_20260909( callable $callback, array &$report, int $limit = 0 ): int {
    if ( ! function_exists( 'wc_get_product' ) ) { return 0; }
    $seen = 0;
    for ( $page=1; ; ++$page ) {
        $query = new WP_Query( mdo_openai_product_query_args_20260909( $page, 100 ) );
        if ( empty( $query->posts ) ) { break; }
        foreach ( array_map( 'absint', $query->posts ) as $parent_id ) {
            $parent = wc_get_product( $parent_id );
            if ( ! $parent instanceof WC_Product ) { continue; }
            ++$report['parents'];
            if ( $parent instanceof WC_Product_Variable && $parent->is_type( 'variable' ) ) {
                foreach ( array_map( 'absint', (array) $parent->get_children() ) as $variation_id ) {
                    $variation = wc_get_product( $variation_id );
                    if ( ! $variation instanceof WC_Product_Variation || ! $variation->exists() || 'publish' !== (string) $variation->get_status() ) { continue; }
                    ++$report['variants'];
                    $callback( $variation );
                    if ( $limit > 0 && ++$seen >= $limit ) { return $seen; }
                }
            } else {
                $callback( $parent );
                if ( $limit > 0 && ++$seen >= $limit ) { return $seen; }
            }
        }
        if ( $page >= (int) $query->max_num_pages ) { break; }
        if ( function_exists( 'wp_cache_flush_runtime' ) ) { wp_cache_flush_runtime(); }
    }
    return $seen;
}

function mdo_openai_native_columns_20260909(): array {
    return array( 'item_id','group_id','listing_has_variations','variant_dict','offer_id','title','description','url','brand','seller_name','marketplace_seller','seller_url','image_url','additional_image_urls','availability','availability_date','price','sale_price','product_category','weight','item_weight_unit','gtin','mpn','review_count','star_rating','shipping_price','shipping','accepts_returns','return_deadline_in_days','return_policy','is_eligible_search' );
}
function mdo_openai_google_columns_20260909(): array {
    return array( 'id','title','description','link','image_link','availability','availability_date','price','brand','sale_price','gtin','mpn','identifier_exists','item_group_id','additional_image_link','product_type' );
}
function mdo_openai_csv_value_20260909( $value ): string {
    if ( is_array( $value ) ) { return wp_json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
    if ( is_bool( $value ) ) { return $value ? 'true' : 'false'; }
    return (string) $value;
}
function mdo_openai_gz_write_csv_20260909( $gz, array $columns, array $row, string $delimiter ): void {
    $memory = fopen( 'php://temp', 'r+' );
    $values = array();
    foreach ( $columns as $column ) { $values[] = mdo_openai_csv_value_20260909( $row[ $column ] ?? '' ); }
    fputcsv( $memory, $values, $delimiter, '"', '\\', "\n" );
    rewind( $memory );
    $line = stream_get_contents( $memory );
    fclose( $memory );
    gzwrite( $gz, (string) $line );
}
function mdo_openai_manifest_path_20260909(): string {
    $storage = mdo_openai_upload_dir_20260909();
    return trailingslashit( $storage['dir'] ) . MDO_OPENAI_COMMERCE_MANIFEST;
}
function mdo_openai_each_previous_manifest_20260909( callable $callback ): void {
    $path = mdo_openai_manifest_path_20260909();
    if ( ! is_readable( $path ) ) { return; }
    $fh = fopen( $path, 'rb' );
    if ( false === $fh ) { return; }
    while ( false !== ( $line = fgets( $fh ) ) ) {
        $entry = json_decode( trim( $line ), true );
        if ( is_array( $entry ) && ! empty( $entry['item_id'] ) ) { $callback( $entry ); }
    }
    fclose( $fh );
}

abstract class MDO_OpenAI_Abstract_Provider_20260909 implements MDO_Merchant_Feed_Provider_Interface_20260909 {
    protected array $settings;
    protected string $mode = 'native';
    public function __construct( ?array $settings = null ) { $this->settings = $settings ?: mdo_openai_settings_20260909(); }
    public function filename(): string { return mdo_openai_format_meta_20260909( $this->settings['format'] )['filename']; }
    abstract protected function build_record( WC_Product $product, array &$report ): ?array;
    protected function preflight(): ?string {
        if ( 'google' === $this->mode && '1' !== (string) ( $this->settings['google_compatible_confirmed'] ?? '0' ) ) { return 'OpenAI no ha confirmado todavía un feed Google-compatible registrado para esta cuenta.'; }
        return null;
    }
    public function preview( int $limit = 20 ): array {
        $report = mdo_openai_report_template_20260909();
        if ( $error = $this->preflight() ) { mdo_openai_report_issue_20260909( $report, 'error', '', $error ); return array( 'rows'=>array(), 'report'=>$report ); }
        $rows = array();
        $state = array( 'item_ids'=>array(), 'variants'=>array() );
        mdo_openai_foreach_product_20260909( function( WC_Product $product ) use ( &$rows, &$report, &$state, $limit ): void {
            if ( count( $rows ) >= $limit ) { return; }
            $row = $this->build_record( $product, $report );
            if ( is_array( $row ) && mdo_openai_validate_record_20260909( $row, $this->mode, $state, $report ) ) { $rows[] = $row; }
        }, $report, max( 200, $limit * 20 ) );
        return array( 'rows'=>array_slice( $rows, 0, $limit ), 'report'=>$report );
    }
    public function generate(): array {
        if ( $error = $this->preflight() ) { return array( 'ok'=>false, 'error'=>$error, 'report'=>mdo_openai_report_template_20260909() ); }
        $start = microtime( true );
        $report = mdo_openai_report_template_20260909();
        $meta = mdo_openai_format_meta_20260909( $this->settings['format'] );
        $report['filename'] = $meta['filename'];
        $report['format'] = (string) $this->settings['format'];
        $final = mdo_openai_feed_path_20260909( $this->settings['format'] );
        $tmp = $final . '.tmp';
        $gz = gzopen( $tmp, 'wb9' );
        if ( false === $gz ) { return array( 'ok'=>false, 'error'=>'No se pudo abrir el archivo temporal.', 'report'=>$report ); }
        $columns = 'native' === $this->mode ? mdo_openai_native_columns_20260909() : mdo_openai_google_columns_20260909();
        if ( 'jsonl' !== $meta['kind'] ) { mdo_openai_gz_write_csv_20260909( $gz, $columns, array_combine( $columns, $columns ), (string) $meta['delimiter'] ); }
        $state = array( 'item_ids'=>array(), 'variants'=>array() );
        $current_ids = array();
        $manifest_tmp = mdo_openai_manifest_path_20260909() . '.tmp';
        $manifest_fh = 'native' === $this->mode ? fopen( $manifest_tmp, 'wb' ) : false;
        if ( 'native' === $this->mode && false === $manifest_fh ) { gzclose( $gz ); @unlink( $tmp ); return array( 'ok'=>false, 'error'=>'No se pudo abrir el manifiesto temporal.', 'report'=>$report ); }
        $write = function( array $row ) use ( $gz, $meta, $columns ): void {
            if ( 'jsonl' === $meta['kind'] ) { gzwrite( $gz, wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" ); }
            else { mdo_openai_gz_write_csv_20260909( $gz, $columns, $row, (string) $meta['delimiter'] ); }
        };
        $manifest_write = static function( $fh, array $entry ): void {
            if ( is_resource( $fh ) ) { fwrite( $fh, wp_json_encode( $entry, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" ); }
        };
        mdo_openai_foreach_product_20260909( function( WC_Product $product ) use ( &$report, &$state, &$current_ids, $manifest_fh, $manifest_write, $write ): void {
            $row = $this->build_record( $product, $report );
            if ( ! is_array( $row ) || ! mdo_openai_validate_record_20260909( $row, $this->mode, $state, $report ) ) { return; }
            $write( $row );
            ++$report['exported'];
            if ( 'native' === $this->mode ) {
                $id = (string) $row['item_id'];
                $current_ids[ $id ] = true;
                $manifest_write( $manifest_fh, array( 'item_id'=>$id, 'last_seen'=>time(), 'row'=>$row ) );
            }
        }, $report );
        if ( 'native' === $this->mode ) {
            $cutoff = time() - ( MDO_OPENAI_COMMERCE_TOMBSTONE_DAYS * DAY_IN_SECONDS );
            mdo_openai_each_previous_manifest_20260909( function( array $entry ) use ( &$current_ids, &$state, &$report, $cutoff, $write, $manifest_fh, $manifest_write ): void {
                $id = (string) ( $entry['item_id'] ?? '' );
                if ( '' === $id || isset( $current_ids[ $id ] ) ) { return; }
                $last_seen = (int) ( $entry['last_seen'] ?? 0 );
                $row = isset( $entry['row'] ) && is_array( $entry['row'] ) ? $entry['row'] : array();
                if ( $last_seen >= $cutoff && $row ) {
                    $row['is_eligible_search'] = false;
                    if ( mdo_openai_validate_record_20260909( $row, 'native', $state, $report ) ) {
                        $write( $row );
                        ++$report['retired'];
                        $manifest_write( $manifest_fh, array( 'item_id'=>$id, 'last_seen'=>$last_seen, 'row'=>$row ) );
                    }
                }
            } );
            fclose( $manifest_fh );
        }
        gzclose( $gz );
        if ( ! @rename( $tmp, $final ) ) { @unlink( $tmp ); if ( is_file( $manifest_tmp ) ) { @unlink( $manifest_tmp ); } return array( 'ok'=>false, 'error'=>'No se pudo reemplazar el snapshot.', 'report'=>$report ); }
        if ( 'native' === $this->mode ) { @rename( $manifest_tmp, mdo_openai_manifest_path_20260909() ); }
        $report['duration_ms'] = (int) round( ( microtime( true ) - $start ) * 1000 );
        $report['bytes'] = is_file( $final ) ? (int) filesize( $final ) : 0;
        update_option( MDO_OPENAI_COMMERCE_REPORT_OPTION, $report, false );
        mdo_openai_log_20260909( 'generation', 'Snapshot OpenAI generado.', array( 'format'=>$report['format'], 'exported'=>$report['exported'], 'errors'=>$report['errors'], 'warnings'=>$report['warnings'], 'bytes'=>$report['bytes'] ) );
        return array( 'ok'=>true, 'path'=>$final, 'report'=>$report );
    }
}

final class MDO_OpenAI_Commerce_Feed_Provider_20260909 extends MDO_OpenAI_Abstract_Provider_20260909 {
    protected string $mode = 'native';
    public function key(): string { return 'openai_native'; }
    public function label(): string { return 'OpenAI Native Commerce'; }
    protected function build_record( WC_Product $product, array &$report ): ?array { return mdo_openai_native_record_20260909( $product, $this->settings, $report ); }
}
final class MDO_OpenAI_Google_Compatible_Provider_20260909 extends MDO_OpenAI_Abstract_Provider_20260909 {
    protected string $mode = 'google';
    public function key(): string { return 'openai_google_compatible'; }
    public function label(): string { return 'OpenAI – Google Compatible'; }
    protected function build_record( WC_Product $product, array &$report ): ?array { return mdo_openai_google_record_20260909( $product, $this->settings, $report ); }
}

function mdo_openai_provider_20260909( ?array $settings = null ): MDO_Merchant_Feed_Provider_Interface_20260909 {
    $settings = $settings ?: mdo_openai_settings_20260909();
    $meta = mdo_openai_format_meta_20260909( $settings['format'] );
    $provider = 'google_compatible' === $meta['provider'] ? new MDO_OpenAI_Google_Compatible_Provider_20260909( $settings ) : new MDO_OpenAI_Commerce_Feed_Provider_20260909( $settings );
    return apply_filters( 'mdo_openai_feed_provider', $provider, $settings );
}

function mdo_openai_validate_catalog_20260909( ?array $settings = null ): array {
    $settings = $settings ?: mdo_openai_settings_20260909();
    $meta = mdo_openai_format_meta_20260909( $settings['format'] );
    $mode = 'google_compatible' === $meta['provider'] ? 'google' : 'native';
    $report = mdo_openai_report_template_20260909();
    $report['format'] = (string) $settings['format'];
    if ( 'google' === $mode && '1' !== (string) ( $settings['google_compatible_confirmed'] ?? '0' ) ) {
        mdo_openai_report_issue_20260909( $report, 'error', '', 'OpenAI no ha confirmado todavía un feed Google-compatible registrado para esta cuenta.' );
        update_option( MDO_OPENAI_COMMERCE_REPORT_OPTION, $report, false );
        return $report;
    }
    $state = array( 'item_ids'=>array(), 'variants'=>array() );
    mdo_openai_foreach_product_20260909( function( WC_Product $product ) use ( &$report, &$state, $settings, $mode ): void {
        $row = 'native' === $mode ? mdo_openai_native_record_20260909( $product, $settings, $report ) : mdo_openai_google_record_20260909( $product, $settings, $report );
        if ( is_array( $row ) && mdo_openai_validate_record_20260909( $row, $mode, $state, $report ) ) { ++$report['exported']; }
    }, $report );
    update_option( MDO_OPENAI_COMMERCE_REPORT_OPTION, $report, false );
    mdo_openai_log_20260909( 'validation', 'Catálogo OpenAI validado.', array( 'exported'=>$report['exported'], 'errors'=>$report['errors'], 'warnings'=>$report['warnings'] ) );
    return $report;
}

function mdo_openai_exportable_count_20260909(): int {
    $cached = get_transient( 'mdo_openai_exportable_count' );
    if ( false !== $cached ) { return (int) $cached; }
    $settings = mdo_openai_settings_20260909();
    $settings['format'] = 'native_jsonl_gz';
    $report = mdo_openai_report_template_20260909();
    $state = array( 'item_ids'=>array(), 'variants'=>array() );
    $count = 0;
    mdo_openai_foreach_product_20260909( function( WC_Product $product ) use ( &$count, &$report, &$state, $settings ): void {
        $row = mdo_openai_native_record_20260909( $product, $settings, $report );
        if ( is_array( $row ) && mdo_openai_validate_record_20260909( $row, 'native', $state, $report ) ) { ++$count; }
    }, $report );
    set_transient( 'mdo_openai_exportable_count', $count, 15 * MINUTE_IN_SECONDS );
    return $count;
}

function mdo_openai_serve_preview_file_20260909(): void {
    $uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
    $path = (string) wp_parse_url( $uri, PHP_URL_PATH );
    if ( 0 !== strpos( rtrim( $path, '/' ), '/emdo-feed/openai/' ) ) { return; }
    $requested = basename( rtrim( $path, '/' ) );
    $allowed = array();
    foreach ( array( 'native_jsonl_gz','native_csv_gz','native_tsv_gz','google_csv_gz','google_tsv_gz' ) as $format ) { $allowed[ mdo_openai_format_meta_20260909( $format )['filename'] ] = $format; }
    if ( ! isset( $allowed[ $requested ] ) ) { status_header( 404 ); exit; }
    $file = mdo_openai_feed_path_20260909( $allowed[ $requested ] );
    if ( ! is_readable( $file ) ) { status_header( 404 ); header( 'Content-Type: text/plain; charset=UTF-8' ); echo 'Feed not generated yet'; exit; }
    while ( ob_get_level() > 0 ) { @ob_end_clean(); }
    status_header( 200 );
    header( 'Content-Type: application/gzip' );
    header( 'Content-Disposition: attachment; filename="' . $requested . '"' );
    header( 'Content-Length: ' . filesize( $file ) );
    header( 'X-Robots-Tag: noindex, nofollow', true );
    header( 'Cache-Control: no-store, max-age=0', true );
    readfile( $file );
    exit;
}
add_action( 'parse_request', 'mdo_openai_serve_preview_file_20260909', -19000 );

function mdo_openai_cron_schedules_20260909( array $schedules ): array {
    $schedules['mdo_openai_6h'] = array( 'interval'=>6 * HOUR_IN_SECONDS, 'display'=>'MDO OpenAI cada 6 horas' );
    $schedules['mdo_openai_12h'] = array( 'interval'=>12 * HOUR_IN_SECONDS, 'display'=>'MDO OpenAI cada 12 horas' );
    return $schedules;
}
add_filter( 'cron_schedules', 'mdo_openai_cron_schedules_20260909' );

function mdo_openai_schedule_key_20260909( string $schedule ): string {
    $map = array( 'hourly'=>'hourly', 'six_hours'=>'mdo_openai_6h', 'twelve_hours'=>'mdo_openai_12h', 'daily'=>'daily' );
    return $map[ $schedule ] ?? '';
}
function mdo_openai_reschedule_20260909(): void {
    $hook = 'mdo_openai_commerce_cron';
    $settings = mdo_openai_settings_20260909();
    $wanted = mdo_openai_schedule_key_20260909( (string) $settings['schedule'] );
    $next = wp_next_scheduled( $hook );
    if ( '' === $wanted ) { if ( $next ) { wp_clear_scheduled_hook( $hook ); } return; }
    $current = wp_get_schedule( $hook );
    if ( ! $next || $current !== $wanted ) { wp_clear_scheduled_hook( $hook ); wp_schedule_event( time() + 300, $wanted, $hook ); }
}
add_action( 'init', 'mdo_openai_reschedule_20260909', 50 );

function mdo_openai_cron_run_20260909(): void {
    $provider = mdo_openai_provider_20260909();
    $result = $provider->generate();
    if ( ! empty( $result['ok'] ) ) {
        do_action( 'mdo_openai_snapshot_generated', $result['path'], $result['report'] );
        $settings = mdo_openai_settings_20260909();
        if ( '1' === (string) ( $settings['auto_upload'] ?? '0' ) && 'none' !== (string) ( $settings['delivery'] ?? 'none' ) ) { do_action( 'mdo_openai_request_upload', $result['path'], false ); }
    }
}
add_action( 'mdo_openai_commerce_cron', 'mdo_openai_cron_run_20260909' );
