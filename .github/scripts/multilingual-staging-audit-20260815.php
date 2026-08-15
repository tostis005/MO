<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

require_once ABSPATH . 'wp-admin/includes/plugin.php';

$result = array();
$result['core_version'] = get_bloginfo( 'version' );
$result['siteurl'] = get_option( 'siteurl' );
$result['home'] = get_option( 'home' );
$result['locale'] = get_locale();
$result['stylesheet'] = get_option( 'stylesheet' );
$result['template'] = get_option( 'template' );
$result['active_plugins'] = (array) get_option( 'active_plugins', array() );
$result['all_plugins'] = array_keys( get_plugins() );

$pages = get_posts(
    array(
        'post_type'      => 'page',
        'post_status'    => array( 'publish', 'draft', 'private' ),
        'posts_per_page' => 250,
        'orderby'        => 'ID',
        'order'          => 'ASC',
    )
);
$result['pages'] = array_map(
    static function ( $p ) {
        return array(
            'ID'          => (int) $p->ID,
            'title'       => $p->post_title,
            'slug'        => $p->post_name,
            'status'      => $p->post_status,
            'parent'      => (int) $p->post_parent,
            'template'    => get_page_template_slug( $p->ID ),
            'elementor'   => get_post_meta( $p->ID, '_elementor_edit_mode', true ),
        );
    },
    $pages
);

$result['special_pages'] = array();
foreach ( array( 'page_on_front', 'page_for_posts', 'woocommerce_shop_page_id', 'woocommerce_cart_page_id', 'woocommerce_checkout_page_id', 'woocommerce_myaccount_page_id' ) as $option ) {
    $result['special_pages'][ $option ] = (int) get_option( $option );
}

$result['menus'] = array();
foreach ( wp_get_nav_menus() as $menu ) {
    $items = wp_get_nav_menu_items( $menu->term_id );
    $result['menus'][] = array(
        'id'    => (int) $menu->term_id,
        'name'  => $menu->name,
        'slug'  => $menu->slug,
        'count' => is_array( $items ) ? count( $items ) : 0,
        'items' => is_array( $items ) ? array_map(
            static function ( $item ) {
                return array(
                    'id'         => (int) $item->ID,
                    'title'      => $item->title,
                    'url'        => $item->url,
                    'object'     => $item->object,
                    'object_id'  => (int) $item->object_id,
                    'parent'     => (int) $item->menu_item_parent,
                );
            },
            $items
        ) : array(),
    );
}
$result['menu_locations'] = wp_get_nav_menu_locations();
$result['registered_menu_locations'] = get_registered_nav_menus();

$product_counts = wp_count_posts( 'product' );
$result['product_counts'] = $product_counts ? (array) $product_counts : array();
$result['products_sample'] = array_map(
    static function ( $p ) {
        return array( 'ID' => (int) $p->ID, 'title' => $p->post_title, 'slug' => $p->post_name );
    },
    get_posts(
        array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => 40,
            'orderby'        => 'ID',
            'order'          => 'ASC',
        )
    )
);

$cats = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
$result['product_categories'] = is_wp_error( $cats ) ? array() : array_map(
    static function ( $t ) {
        return array(
            'id'     => (int) $t->term_id,
            'name'   => $t->name,
            'slug'   => $t->slug,
            'count'  => (int) $t->count,
            'parent' => (int) $t->parent,
        );
    },
    $cats
);

$result['attributes'] = array();
if ( function_exists( 'wc_get_attribute_taxonomies' ) ) {
    foreach ( wc_get_attribute_taxonomies() as $attribute ) {
        $taxonomy = 'pa_' . $attribute->attribute_name;
        $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
        $result['attributes'][] = array(
            'id'    => (int) $attribute->attribute_id,
            'name'  => $attribute->attribute_label,
            'slug'  => $attribute->attribute_name,
            'terms' => is_wp_error( $terms ) ? array() : array_map(
                static function ( $t ) {
                    return array( 'id' => (int) $t->term_id, 'name' => $t->name, 'slug' => $t->slug, 'count' => (int) $t->count );
                },
                $terms
            ),
        );
    }
}

$result['elementor_library'] = array_map(
    static function ( $p ) {
        return array( 'ID' => (int) $p->ID, 'title' => $p->post_title, 'slug' => $p->post_name, 'status' => $p->post_status );
    },
    get_posts(
        array(
            'post_type'      => 'elementor_library',
            'post_status'    => array( 'publish', 'draft' ),
            'posts_per_page' => 100,
        )
    )
);

$result['polylang'] = array(
    'pll_languages_list_exists' => function_exists( 'pll_languages_list' ),
    'options' => array_filter(
        wp_load_alloptions(),
        static function ( $key ) {
            return false !== stripos( $key, 'polylang' );
        },
        ARRAY_FILTER_USE_KEY
    ),
);
$result['wpml'] = array(
    'icl_sitepress_settings' => get_option( 'icl_sitepress_settings', null ),
    'sitepress_exists'       => isset( $GLOBALS['sitepress'] ),
);

$result['theme_mods'] = get_theme_mods();

$json = wp_json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
echo '__MDO_MULTILINGUAL_AUDIT__=' . base64_encode( $json ) . PHP_EOL;
