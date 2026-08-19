<?php
/**
 * Shipping summary for a WCFM producer store.
 * Matches the native Products tab spacing and renders quantity formulas clearly.
 *
 * Available variables are supplied by WCFM:
 * @var object $store_user
 * @var array  $store_info
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$vendor_id = 0;
if ( is_object( $store_user ) ) {
    if ( is_callable( array( $store_user, 'get_id' ) ) ) {
        $vendor_id = absint( $store_user->get_id() );
    } elseif ( isset( $store_user->ID ) ) {
        $vendor_id = absint( $store_user->ID );
    } elseif ( isset( $store_user->data->ID ) ) {
        $vendor_id = absint( $store_user->data->ID );
    }
}

$minimum = $vendor_id ? mdo_sst_minimum_order( $vendor_id ) : 0;
$rows    = $vendor_id ? mdo_sst_shipping_rows( $vendor_id ) : array();

/** Detect a WooCommerce flat-rate formula such as "8 * [qty]". */
$qty_formula = static function( $value ): ?array {
    $raw = trim( wp_strip_all_tags( (string) $value ) );
    if ( '' === $raw || false === stripos( $raw, '[qty]' ) ) {
        return null;
    }

    if ( preg_match( '/^\s*([0-9]+(?:[\.,][0-9]+)?)\s*(?:\*|x|×)\s*\[qty\]\s*$/i', $raw, $matches ) ) {
        $number = (float) str_replace( ',', '.', $matches[1] );
        return array(
            'unit' => $number,
            'raw'  => $raw,
        );
    }

    // Be tolerant of harmless wrapping parentheses/spaces around the formula.
    if ( preg_match( '/([0-9]+(?:[\.,][0-9]+)?)\s*(?:\*|x|×)\s*\[qty\]/i', $raw, $matches ) ) {
        $number = (float) str_replace( ',', '.', $matches[1] );
        return array(
            'unit' => $number,
            'raw'  => $raw,
        );
    }

    return array(
        'unit' => null,
        'raw'  => $raw,
    );
};

$row_qty_formula = static function( array $row ) use ( $qty_formula ): ?array {
    if ( empty( $row['flat_costs'] ) || ! is_array( $row['flat_costs'] ) ) {
        return null;
    }

    foreach ( $row['flat_costs'] as $cost ) {
        $parsed = $qty_formula( $cost );
        if ( null !== $parsed ) {
            return $parsed;
        }
    }

    return null;
};

$row_unconditional_free = static function( array $row ): bool {
    if ( empty( $row['notes'] ) || ! is_array( $row['notes'] ) ) {
        return false;
    }

    foreach ( $row['notes'] as $note ) {
        $plain = remove_accents( strtolower( trim( wp_strip_all_tags( (string) $note ) ) ) );
        if ( false !== strpos( $plain, 'envio gratuito' ) || false !== strpos( $plain, 'free shipping' ) ) {
            return true;
        }
    }

    return false;
};

$row_minimum_guarantees_free = static function( array $row ) use ( $minimum ): bool {
    $free_from = ! empty( $row['free_from'] ) ? (float) $row['free_from'] : 0.0;
    return $minimum > 0 && $free_from > 0 && ( $minimum + 0.0001 ) >= $free_from;
};

/**
 * Sort groups exactly as requested:
 *  1) non-quantity rates, free first and then fixed price ascending;
 *  2) quantity-based rates, ordered by their unit price ascending;
 *  3) unknown/conditional rates last.
 */
$row_sort_key = static function( array $row ) use ( $row_qty_formula, $row_unconditional_free, $row_minimum_guarantees_free ): array {
    if ( $row_minimum_guarantees_free( $row ) || $row_unconditional_free( $row ) ) {
        return array( 0, 0.0 );
    }

    $qty = $row_qty_formula( $row );
    if ( null !== $qty ) {
        return array( 2, is_numeric( $qty['unit'] ) ? (float) $qty['unit'] : PHP_FLOAT_MAX );
    }

    if ( isset( $row['sort_cost'] ) && is_numeric( $row['sort_cost'] ) ) {
        return array( 1, max( 0.0, (float) $row['sort_cost'] ) );
    }

    return array( 3, PHP_FLOAT_MAX );
};

usort(
    $rows,
    static function( array $a, array $b ) use ( $row_sort_key ): int {
        $ka = $row_sort_key( $a );
        $kb = $row_sort_key( $b );

        if ( $ka[0] !== $kb[0] ) {
            return $ka[0] <=> $kb[0];
        }
        if ( $ka[1] !== $kb[1] ) {
            return $ka[1] <=> $kb[1];
        }

        return strcasecmp(
            remove_accents( mdo_sst_row_display_name( $a ) ),
            remove_accents( mdo_sst_row_display_name( $b ) )
        );
    }
);

$has_qty_rate = false;
foreach ( $rows as $row ) {
    if ( null !== $row_qty_formula( $row ) ) {
        $has_qty_rate = true;
        break;
    }
}
?>

<style>
    /*
     * On native Products, WCFM inserts #products > .product_area > .products-wrapper
     * below the tabs. Shipping now uses the same structural shell. Keep the tab
     * container transparent so the white content panel starts below the tabs.
     */
    #wcfmmp-store #tabsWithStyle.tab_area {
        background: transparent !important;
        box-shadow: none !important;
    }
    #wcfmmp-store #mdo-shipping-products {
        clear: both;
        margin-top: 20px;
    }
    #wcfmmp-store #mdo-shipping-products .product_area,
    #wcfmmp-store #mdo-shipping-products .products-wrapper {
        width: 100%;
        margin: 0;
    }
    #wcfmmp-store .mdo-store-shipping {
        margin: 0 0 24px;
        padding: 22px;
        border: 1px solid rgba(0,0,0,.08);
        border-radius: 12px;
        background: #fff;
        box-shadow: 0 1px 3px rgba(0,0,0,.035);
    }
    #wcfmmp-store .mdo-store-shipping h2 {
        margin: 0 0 18px;
    }
    #wcfmmp-store .mdo-store-shipping__minimum {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 16px;
        margin: 0 0 20px;
        padding: 16px 18px;
        border: 1px solid rgba(0,0,0,.09);
        border-radius: 10px;
        background: #fff;
        box-shadow: 0 1px 4px rgba(0,0,0,.04);
    }
    #wcfmmp-store .mdo-store-shipping__minimum-label {
        font-weight: 650;
    }
    #wcfmmp-store .mdo-store-shipping__minimum-price {
        font-size: 1.15em;
        font-weight: 750;
        white-space: nowrap;
    }
    #wcfmmp-store .mdo-store-shipping__table-wrap {
        overflow-x: auto;
        border: 1px solid rgba(0,0,0,.09);
        border-radius: 10px;
        background: #fff;
    }
    #wcfmmp-store .mdo-store-shipping__table {
        width: 100%;
        margin: 0;
        border: 0;
        border-collapse: collapse;
        table-layout: fixed;
        background: #fff;
    }
    #wcfmmp-store .mdo-store-shipping__table th,
    #wcfmmp-store .mdo-store-shipping__table td {
        padding: 15px 17px;
        border: 0;
        border-bottom: 1px solid rgba(0,0,0,.075);
        text-align: left;
        vertical-align: top;
    }
    #wcfmmp-store .mdo-store-shipping__table th {
        background: rgba(0,0,0,.025);
        color: #555;
        font-size: .88em;
        font-weight: 700;
    }
    #wcfmmp-store .mdo-store-shipping__table tbody tr:last-child td {
        border-bottom: 0;
    }
    #wcfmmp-store .mdo-store-shipping__destination {
        width: 58%;
        color: #222;
        font-weight: 650;
        line-height: 1.45;
    }
    #wcfmmp-store .mdo-store-shipping__cost {
        width: 42%;
        color: #222;
        font-weight: 700;
        line-height: 1.45;
    }
    #wcfmmp-store .mdo-store-shipping__detail {
        display: block;
        margin-top: 4px;
        color: #737373;
        font-size: .88em;
        font-weight: 400;
        line-height: 1.4;
    }
    #wcfmmp-store .mdo-store-shipping__free {
        font-weight: 700;
    }
    #wcfmmp-store .mdo-store-shipping__empty {
        margin: 0;
        padding: 18px;
        border: 1px solid rgba(0,0,0,.09);
        border-radius: 10px;
        background: #fff;
    }
    #wcfmmp-store .mdo-store-shipping__note {
        margin: 16px 0 0;
        color: #777;
        font-size: .88em;
        line-height: 1.5;
    }
    #wcfmmp-store .mdo-store-shipping__note + .mdo-store-shipping__note {
        margin-top: 7px;
    }
    @media (max-width: 600px) {
        #wcfmmp-store #mdo-shipping-products {
            margin-top: 16px;
        }
        #wcfmmp-store .mdo-store-shipping {
            padding: 16px;
        }
        #wcfmmp-store .mdo-store-shipping__minimum {
            align-items: flex-start;
            flex-direction: column;
            gap: 4px;
        }
        #wcfmmp-store .mdo-store-shipping__table th,
        #wcfmmp-store .mdo-store-shipping__table td {
            padding: 13px 12px;
        }
        #wcfmmp-store .mdo-store-shipping__destination {
            width: 56%;
        }
        #wcfmmp-store .mdo-store-shipping__cost {
            width: 44%;
        }
    }
</style>

<div id="mdo-shipping-products">
    <div class="product_area">
        <div class="products-wrapper">
            <div class="mdo-store-shipping" aria-labelledby="mdo-store-shipping-title">
                <h2 id="mdo-store-shipping-title"><?php echo esc_html( mdo_sst_text( 'Envíos', 'Shipping' ) ); ?></h2>

                <?php if ( $minimum > 0 ) : ?>
                    <div class="mdo-store-shipping__minimum">
                        <span class="mdo-store-shipping__minimum-label">
                            <?php echo esc_html( mdo_sst_text( 'Pedido mínimo', 'Minimum order' ) ); ?>
                        </span>
                        <span class="mdo-store-shipping__minimum-price"><?php echo wp_kses_post( wc_price( $minimum ) ); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $rows ) ) : ?>
                    <div class="mdo-store-shipping__table-wrap">
                        <table class="mdo-store-shipping__table">
                            <thead>
                                <tr>
                                    <th scope="col"><?php echo esc_html( mdo_sst_text( 'Destino', 'Destination' ) ); ?></th>
                                    <th scope="col"><?php echo esc_html( mdo_sst_text( 'Coste de envío', 'Shipping cost' ) ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $rows as $row ) : ?>
                                    <?php
                                    $free_from              = ! empty( $row['free_from'] ) ? (float) $row['free_from'] : 0.0;
                                    $minimum_guarantees_free = $row_minimum_guarantees_free( $row );
                                    $unconditional_free      = $row_unconditional_free( $row );
                                    $qty                      = $row_qty_formula( $row );

                                    $details = array();
                                    if ( ! $minimum_guarantees_free && ! $unconditional_free && $free_from > 0 ) {
                                        $details[] = sprintf(
                                            '%s %s',
                                            mdo_sst_text( 'Envío gratuito a partir de', 'Free shipping from' ),
                                            wp_strip_all_tags( wc_price( $free_from ) )
                                        );
                                    }
                                    if ( ! empty( $row['notes'] ) && is_array( $row['notes'] ) ) {
                                        foreach ( $row['notes'] as $note ) {
                                            $plain_note = remove_accents( strtolower( trim( wp_strip_all_tags( (string) $note ) ) ) );
                                            if ( $unconditional_free && ( false !== strpos( $plain_note, 'envio gratuito' ) || false !== strpos( $plain_note, 'free shipping' ) ) ) {
                                                continue;
                                            }
                                            $details[] = trim( (string) $note );
                                        }
                                    }
                                    $details = array_values( array_unique( array_filter( $details ) ) );
                                    ?>
                                    <tr>
                                        <td class="mdo-store-shipping__destination">
                                            <?php echo esc_html( mdo_sst_row_display_name( $row ) ); ?>
                                        </td>
                                        <td class="mdo-store-shipping__cost">
                                            <?php if ( $minimum_guarantees_free || $unconditional_free ) : ?>
                                                <span class="mdo-store-shipping__free">
                                                    <?php echo esc_html( mdo_sst_text( 'Envío gratuito', 'Free shipping' ) ); ?>
                                                </span>
                                            <?php elseif ( null !== $qty && is_numeric( $qty['unit'] ) ) : ?>
                                                <?php echo wp_kses_post( wc_price( (float) $qty['unit'] ) ); ?>
                                                <?php echo esc_html( ' × ' . mdo_sst_text( '[cantidad]', '[quantity]' ) ); ?>
                                            <?php elseif ( ! empty( $row['flat_costs'] ) ) : ?>
                                                <?php echo esc_html( implode( ' / ', $row['flat_costs'] ) ); ?>
                                            <?php elseif ( $free_from > 0 ) : ?>
                                                <?php echo esc_html( mdo_sst_text( 'Según condiciones', 'According to conditions' ) ); ?>
                                            <?php else : ?>
                                                <?php echo esc_html( mdo_sst_text( 'Consultar condiciones', 'See conditions' ) ); ?>
                                            <?php endif; ?>

                                            <?php foreach ( $details as $detail ) : ?>
                                                <span class="mdo-store-shipping__detail"><?php echo esc_html( $detail ); ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else : ?>
                    <p class="mdo-store-shipping__empty">
                        <?php
                        echo esc_html(
                            mdo_sst_text(
                                'Esta tienda no tiene tarifas de envío publicadas actualmente.',
                                'This store does not currently have published shipping rates.'
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>

                <p class="mdo-store-shipping__note">
                    <?php
                    echo esc_html(
                        mdo_sst_text(
                            'El coste definitivo se confirma en el carrito según el importe del pedido, los productos y la dirección de entrega.',
                            'The final shipping cost is confirmed in the basket according to the order value, products and delivery address.'
                        )
                    );
                    ?>
                </p>

                <?php if ( $has_qty_rate ) : ?>
                    <p class="mdo-store-shipping__note mdo-store-shipping__quantity-note">
                        <?php
                        echo esc_html(
                            mdo_sst_text(
                                'En las tarifas por cantidad, el importe indicado se multiplica por el número de unidades del pedido. Por ejemplo, 4 unidades = 4 × la tarifa indicada.',
                                'For quantity-based rates, the amount shown is multiplied by the number of units in the order. For example, 4 units = 4 × the displayed rate.'
                            )
                        );
                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
