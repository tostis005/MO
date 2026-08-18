<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

global $wpdb;
$slugs = array( '1957', 'hidalgo-de-la-jara', 'tolecarnes', 'puente-robles', 'el-catedratico' );
$out = array();
foreach ( $slugs as $slug ) {
    $user = get_user_by( 'slug', $slug );
    $row = array( 'slug' => $slug, 'user_id' => $user instanceof WP_User ? $user->ID : 0 );
    if ( $user instanceof WP_User ) {
        $en = (string) get_user_meta( $user->ID, '_mdo_en_store_description', true );
        $es = (string) get_user_meta( $user->ID, '_store_description', true );
        if ( '' === trim( wp_strip_all_tags( $es ) ) ) {
            $settings = get_user_meta( $user->ID, 'wcfmmp_profile_settings', true );
            $es = is_array( $settings ) ? (string) ( $settings['shop_description'] ?? '' ) : '';
        }
        $row['published_products'] = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type='product' AND post_status='publish' AND post_author=%d",
            $user->ID
        ) );
        $row['spanish_chars'] = mb_strlen( trim( wp_strip_all_tags( $es ) ) );
        $row['english_chars'] = mb_strlen( trim( wp_strip_all_tags( $en ) ) );
        $row['english_start'] = mb_substr( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $en ) ) ), 0, 180 );
    }
    $out[] = $row;
}

$producer_pages = $wpdb->get_results(
    "SELECT p.ID,p.post_title,p.post_name,
      MAX(CASE WHEN pm.meta_key='_en_US_post_title' THEN pm.meta_value END) en_title,
      MAX(CASE WHEN pm.meta_key='_en_US_post_name' THEN pm.meta_value END) en_slug,
      MAX(CASE WHEN pm.meta_key='_en_US_published' THEN pm.meta_value END) en_published
     FROM {$wpdb->posts} p
     LEFT JOIN {$wpdb->postmeta} pm ON pm.post_id=p.ID AND pm.meta_key IN ('_en_US_post_title','_en_US_post_name','_en_US_published')
     WHERE p.post_type='page' AND p.post_status='publish'
     GROUP BY p.ID
     HAVING p.post_title LIKE '%Productor%' OR en_title LIKE '%Producer%'
     ORDER BY p.ID",
    ARRAY_A
);

echo wp_json_encode( array( 'vendors' => $out, 'producer_pages' => $producer_pages ), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ), "\n";
