<?php
/**
 * Plugin Name: MDO Pinterest Tag
 * Description: Consent-aware Pinterest Tag plus WooCommerce product, cart and checkout events.
 * Version: 2026.08.21.5
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * EMDO has its own 10-minute Home HTML cache. If that cache predates this tag,
 * invalidate only the stale Home copy before the theme serves it, so the next
 * render is rebuilt with Pinterest included.
 */
add_action(
    'template_redirect',
    static function (): void {
        if ( ! function_exists( 'elmercado_home_cache_key' ) ) {
            return;
        }

        $is_home = function_exists( 'elmercado_is_optimized_home' )
            ? (bool) elmercado_is_optimized_home()
            : ( is_front_page() || is_home() );
        if ( ! $is_home ) {
            return;
        }

        $key = elmercado_home_cache_key();
        $cached = get_transient( $key );
        if ( ! is_string( $cached ) || '' === $cached || false !== strpos( $cached, '2612375296577' ) ) {
            return;
        }

        delete_transient( $key );

        if ( function_exists( 'elmercado_home_static_cache_file' ) ) {
            $file = elmercado_home_static_cache_file();
            if ( is_string( $file ) && is_file( $file ) ) {
                @unlink( $file );
            }
        }
    },
    -3000
);

function mdo_pinterest_product_category( int $product_id ): string {
    $terms = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'names' ) );
    if ( is_wp_error( $terms ) || empty( $terms ) ) {
        return '';
    }
    return sanitize_text_field( (string) reset( $terms ) );
}

function mdo_pinterest_product_brand( int $product_id ): string {
    $post = get_post( $product_id );
    if ( ! $post instanceof WP_Post ) {
        return 'El Mercado de Origen';
    }

    $settings = get_user_meta( (int) $post->post_author, 'wcfmmp_profile_settings', true );
    if ( is_array( $settings ) && ! empty( $settings['store_name'] ) ) {
        $name = sanitize_text_field( (string) $settings['store_name'] );
        if ( '' !== $name ) {
            return $name;
        }
    }

    return 'El Mercado de Origen';
}

function mdo_pinterest_line_item( WC_Product $product, int $quantity = 1 ): array {
    $parent_id = $product instanceof WC_Product_Variation ? (int) $product->get_parent_id() : (int) $product->get_id();
    $price = (float) wc_get_price_to_display( $product, array( 'price' => (float) $product->get_price() ) );

    return array(
        'product_name'     => wp_strip_all_tags( $product->get_name() ),
        'product_id'       => 'emdo-' . (int) $product->get_id(),
        'product_category' => mdo_pinterest_product_category( $parent_id ),
        'product_price'    => round( $price, 2 ),
        'product_quantity' => max( 1, $quantity ),
        'product_brand'    => mdo_pinterest_product_brand( $parent_id ),
    );
}

add_action(
    'wp_head',
    static function (): void {
        ?>
<!-- Pinterest Tag - EMDO -->
<script>
(function () {
    'use strict';

    var TAG_ID = '2612375296577';
    var started = false;
    var lastConsent = null;
    var consentCallbacks = [];

    function readCookie(name) {
        var prefix = name + '=';
        var parts = document.cookie ? document.cookie.split(';') : [];
        for (var i = 0; i < parts.length; i++) {
            var value = parts[i].trim();
            if (value.indexOf(prefix) === 0) {
                return decodeURIComponent(value.substring(prefix.length));
            }
        }
        return null;
    }

    function hasMarketingConsent() {
        return readCookie('cookielawinfo-checkbox-non-necessary') === 'yes';
    }

    function bootstrapPinterest() {
        if (started) {
            return;
        }
        started = true;

        !function(e){if(!window.pintrk){window.pintrk=function(){window.pintrk.queue.push(Array.prototype.slice.call(arguments))};var n=window.pintrk;n.queue=[],n.version='3.0';var t=document.createElement('script');t.async=!0,t.src=e;var r=document.getElementsByTagName('script')[0];r.parentNode.insertBefore(t,r)}}('https://s.pinimg.com/ct/core.js');

        pintrk('load', TAG_ID);
        pintrk('setconsent', true);
        pintrk('page');
    }

    function runConsentCallbacks() {
        if (!hasMarketingConsent()) {
            return;
        }
        var callbacks = consentCallbacks.slice();
        consentCallbacks = [];
        callbacks.forEach(function (callback) {
            try { callback(); } catch (e) {}
        });
    }

    function syncConsent() {
        var granted = hasMarketingConsent();

        if (granted) {
            if (!started) {
                bootstrapPinterest();
            } else if (lastConsent !== true && window.pintrk) {
                pintrk('setconsent', true);
            }
            if (lastConsent !== true) {
                runConsentCallbacks();
            }
        } else if (started && lastConsent !== false && window.pintrk) {
            pintrk('setconsent', false);
        }

        lastConsent = granted;
    }

    window.mdoPinterestOnConsent = function (callback) {
        if (typeof callback !== 'function') {
            return;
        }
        if (hasMarketingConsent()) {
            if (!started) {
                bootstrapPinterest();
            }
            callback();
            return;
        }
        consentCallbacks.push(callback);
    };

    window.mdoPinterestTrack = function (eventName, data) {
        if (!hasMarketingConsent()) {
            return false;
        }
        if (!started) {
            bootstrapPinterest();
        }
        if (!window.pintrk) {
            return false;
        }
        pintrk('track', eventName, data || {});
        return true;
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncConsent, {once: true});
    } else {
        syncConsent();
    }

    document.addEventListener('click', function () {
        window.setTimeout(syncConsent, 300);
    }, true);

    window.addEventListener('focus', syncConsent);
    window.setInterval(syncConsent, 2000);
})();
</script>
<!-- End Pinterest Tag - EMDO -->
        <?php
    },
    5
);

/**
 * Give archive AJAX add-to-cart buttons enough product metadata for Pinterest.
 */
add_filter(
    'woocommerce_loop_add_to_cart_args',
    static function ( array $args, WC_Product $product ): array {
        $attributes = isset( $args['attributes'] ) && is_array( $args['attributes'] ) ? $args['attributes'] : array();
        $attributes['data-emdo-pinterest-id'] = 'emdo-' . (int) $product->get_id();
        $attributes['data-emdo-pinterest-name'] = wp_strip_all_tags( $product->get_name() );
        $attributes['data-emdo-pinterest-price'] = (string) round( (float) wc_get_price_to_display( $product, array( 'price' => (float) $product->get_price() ) ), 2 );
        $attributes['data-emdo-pinterest-category'] = mdo_pinterest_product_category( (int) $product->get_id() );
        $attributes['data-emdo-pinterest-brand'] = mdo_pinterest_product_brand( (int) $product->get_id() );
        $args['attributes'] = $attributes;
        return $args;
    },
    10,
    2
);

add_action(
    'wp_footer',
    static function (): void {
        if ( ! function_exists( 'is_product' ) ) {
            return;
        }

        $currency = function_exists( 'get_woocommerce_currency' ) ? get_woocommerce_currency() : 'EUR';
        $product_data = null;

        if ( is_product() ) {
            $product_id = (int) get_queried_object_id();
            $product = wc_get_product( $product_id );
            if ( $product instanceof WC_Product && ! $product->is_type( 'variable' ) ) {
                $line_item = mdo_pinterest_line_item( $product, 1 );
                $product_data = array(
                    'product_id'       => $line_item['product_id'],
                    'product_category' => $line_item['product_category'],
                    'value'            => $line_item['product_price'],
                    'currency'         => $currency,
                    'line_items'       => array( $line_item ),
                );
            }
        }
        ?>
<script id="mdo-pinterest-woocommerce-events">
(function () {
    'use strict';

    var currency = <?php echo wp_json_encode( $currency ); ?>;
    var initialProduct = <?php echo wp_json_encode( $product_data ); ?>;
    var currentVariation = null;
    var lastAddKey = '';
    var lastAddAt = 0;

    function trackOnceAdd(data) {
        if (!data || !data.product_id) {
            return;
        }
        var now = Date.now();
        var key = String(data.product_id) + ':' + String(data.order_quantity || 1);
        if (key === lastAddKey && (now - lastAddAt) < 1500) {
            return;
        }
        lastAddKey = key;
        lastAddAt = now;
        window.mdoPinterestTrack('addtocart', data);
    }

    function itemData(id, name, price, quantity, category, brand) {
        var numericPrice = parseFloat(price || 0) || 0;
        var qty = parseInt(quantity || 1, 10) || 1;
        return {
            product_id: id,
            product_category: category || '',
            value: Math.round(numericPrice * qty * 100) / 100,
            order_quantity: qty,
            currency: currency,
            line_items: [{
                product_name: name || '',
                product_id: id,
                product_category: category || '',
                product_price: numericPrice,
                product_quantity: qty,
                product_brand: brand || 'El Mercado de Origen'
            }]
        };
    }

    if (initialProduct) {
        window.mdoPinterestOnConsent(function () {
            window.mdoPinterestTrack('pagevisit', initialProduct);
        });
    }

    if (window.jQuery) {
        window.jQuery(document.body).on('found_variation', function (event, variation) {
            if (!variation || !variation.variation_id) {
                return;
            }
            var form = event && event.target ? window.jQuery(event.target).closest('form.variations_form') : window.jQuery('form.variations_form').first();
            var name = document.querySelector('h1.product_title') ? document.querySelector('h1.product_title').textContent.trim() : '';
            var category = form.attr('data-emdo-pinterest-category') || '';
            var brand = form.attr('data-emdo-pinterest-brand') || 'El Mercado de Origen';
            var price = variation.display_price || 0;
            currentVariation = itemData('emdo-' + variation.variation_id, name, price, 1, category, brand);
            window.mdoPinterestOnConsent(function () {
                window.mdoPinterestTrack('pagevisit', currentVariation);
            });
        });

        window.jQuery(document.body).on('added_to_cart', function (event, fragments, cartHash, button) {
            var $button = button ? window.jQuery(button) : window.jQuery();
            var quantity = parseInt($button.attr('data-quantity') || 1, 10) || 1;
            var variationId = parseInt(window.jQuery('form.variations_form input[name="variation_id"]').first().val() || 0, 10) || 0;
            if (variationId && currentVariation) {
                var variableData = JSON.parse(JSON.stringify(currentVariation));
                variableData.order_quantity = quantity;
                variableData.value = Math.round((variableData.line_items[0].product_price || 0) * quantity * 100) / 100;
                variableData.line_items[0].product_quantity = quantity;
                trackOnceAdd(variableData);
                return;
            }

            var id = $button.attr('data-emdo-pinterest-id') || ($button.attr('data-product_id') ? 'emdo-' + $button.attr('data-product_id') : '');
            if (!id) {
                return;
            }
            trackOnceAdd(itemData(
                id,
                $button.attr('data-emdo-pinterest-name') || $button.attr('aria-label') || '',
                $button.attr('data-emdo-pinterest-price') || 0,
                quantity,
                $button.attr('data-emdo-pinterest-category') || '',
                $button.attr('data-emdo-pinterest-brand') || ''
            ));
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target && event.target.matches ? event.target : null;
        if (!form || !form.matches('form.cart')) {
            return;
        }
        var qtyInput = form.querySelector('input.qty');
        var quantity = qtyInput ? (parseInt(qtyInput.value || 1, 10) || 1) : 1;
        var variationInput = form.querySelector('input[name="variation_id"]');
        var variationId = variationInput ? (parseInt(variationInput.value || 0, 10) || 0) : 0;
        if (variationId && currentVariation) {
            var variableData = JSON.parse(JSON.stringify(currentVariation));
            variableData.order_quantity = quantity;
            variableData.value = Math.round((variableData.line_items[0].product_price || 0) * quantity * 100) / 100;
            variableData.line_items[0].product_quantity = quantity;
            trackOnceAdd(variableData);
            return;
        }
        if (initialProduct) {
            var simpleData = JSON.parse(JSON.stringify(initialProduct));
            simpleData.order_quantity = quantity;
            simpleData.value = Math.round((simpleData.line_items[0].product_price || 0) * quantity * 100) / 100;
            simpleData.line_items[0].product_quantity = quantity;
            trackOnceAdd(simpleData);
        }
    }, true);
})();
</script>
        <?php
    },
    40
);

/**
 * Supply product category/brand to the variable product form used by the JS above.
 */
add_filter(
    'woocommerce_available_variation',
    static function ( array $data, WC_Product_Variable $product, WC_Product_Variation $variation ): array {
        $data['mdo_pinterest_product_id'] = 'emdo-' . (int) $variation->get_id();
        $data['mdo_pinterest_category'] = mdo_pinterest_product_category( (int) $product->get_id() );
        $data['mdo_pinterest_brand'] = mdo_pinterest_product_brand( (int) $product->get_id() );
        return $data;
    },
    10,
    3
);

add_action(
    'woocommerce_before_variations_form',
    static function (): void {
        global $product;
        if ( ! $product instanceof WC_Product_Variable ) {
            return;
        }
        ?>
<script>
(function(){
    var form = document.currentScript && document.currentScript.parentElement ? document.currentScript.parentElement.querySelector('form.variations_form') : null;
    if (!form) { form = document.querySelector('form.variations_form'); }
    if (form) {
        form.setAttribute('data-emdo-pinterest-category', <?php echo wp_json_encode( mdo_pinterest_product_category( (int) $product->get_id() ) ); ?>);
        form.setAttribute('data-emdo-pinterest-brand', <?php echo wp_json_encode( mdo_pinterest_product_brand( (int) $product->get_id() ) ); ?>);
    }
})();
</script>
        <?php
    },
    1
);

/**
 * A completed WooCommerce order is Pinterest's Checkout conversion event.
 * event_id + localStorage guard prevent a thank-you page refresh from counting twice.
 */
add_action(
    'woocommerce_thankyou',
    static function ( int $order_id ): void {
        $order = wc_get_order( $order_id );
        if ( ! $order instanceof WC_Order ) {
            return;
        }

        $line_items = array();
        $quantity = 0;
        foreach ( $order->get_items( 'line_item' ) as $item ) {
            if ( ! $item instanceof WC_Order_Item_Product ) {
                continue;
            }
            $product = $item->get_product();
            if ( ! $product instanceof WC_Product ) {
                continue;
            }
            $qty = max( 1, (int) $item->get_quantity() );
            $quantity += $qty;
            $line = mdo_pinterest_line_item( $product, $qty );
            $line['product_price'] = $qty > 0 ? round( (float) $item->get_total() / $qty, 2 ) : $line['product_price'];
            $line_items[] = $line;
        }

        if ( empty( $line_items ) ) {
            return;
        }

        $payload = array(
            'event_id'       => 'emdo-order-' . $order_id,
            'order_id'       => (string) $order->get_order_number(),
            'value'          => round( (float) $order->get_total(), 2 ),
            'order_quantity' => max( 1, $quantity ),
            'currency'       => $order->get_currency(),
            'product_id'     => $line_items[0]['product_id'],
            'line_items'     => $line_items,
        );
        ?>
<script id="mdo-pinterest-checkout-event">
(function () {
    'use strict';
    var storageKey = <?php echo wp_json_encode( 'mdo_pinterest_checkout_' . $order_id ); ?>;
    var payload = <?php echo wp_json_encode( $payload ); ?>;
    window.mdoPinterestOnConsent(function () {
        try {
            if (window.localStorage && localStorage.getItem(storageKey) === '1') {
                return;
            }
        } catch (e) {}

        if (window.mdoPinterestTrack('checkout', payload)) {
            try {
                if (window.localStorage) {
                    localStorage.setItem(storageKey, '1');
                }
            } catch (e) {}
        }
    });
})();
</script>
        <?php
    },
    50
);
