<?php
/**
 * Shipping summary for a WCFM producer store.
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
?>

<div class="mdo-store-shipping" aria-labelledby="mdo-store-shipping-title">
    <style>
        #wcfmmp-store .mdo-store-shipping {
            padding: 4px 0 24px;
        }
        #wcfmmp-store .mdo-store-shipping__intro {
            margin: 0 0 20px;
            color: #555;
            line-height: 1.6;
        }
        #wcfmmp-store .mdo-store-shipping__minimum {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin: 0 0 22px;
            padding: 15px 18px;
            border: 1px solid rgba(0,0,0,.1);
            border-radius: 10px;
            background: rgba(0,0,0,.025);
        }
        #wcfmmp-store .mdo-store-shipping__minimum-label {
            font-weight: 600;
        }
        #wcfmmp-store .mdo-store-shipping__minimum-price {
            font-size: 1.15em;
            font-weight: 700;
            white-space: nowrap;
        }
        #wcfmmp-store .mdo-store-shipping__zones {
            display: grid;
            gap: 14px;
        }
        #wcfmmp-store .mdo-store-shipping__zone {
            padding: 17px 18px;
            border: 1px solid rgba(0,0,0,.1);
            border-radius: 10px;
            background: #fff;
        }
        #wcfmmp-store .mdo-store-shipping__zone h3 {
            margin: 0 0 5px;
            font-size: 1.08em;
            line-height: 1.35;
        }
        #wcfmmp-store .mdo-store-shipping__locations {
            margin: 0 0 13px;
            color: #707070;
            font-size: .93em;
        }
        #wcfmmp-store .mdo-store-shipping__conditions {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px 18px;
            margin: 0;
        }
        #wcfmmp-store .mdo-store-shipping__condition {
            min-width: 0;
        }
        #wcfmmp-store .mdo-store-shipping__condition dt {
            margin: 0 0 2px;
            color: #777;
            font-size: .85em;
            font-weight: 500;
        }
        #wcfmmp-store .mdo-store-shipping__condition dd {
            margin: 0;
            font-weight: 600;
        }
        #wcfmmp-store .mdo-store-shipping__empty {
            margin: 0;
            padding: 18px;
            border: 1px solid rgba(0,0,0,.1);
            border-radius: 10px;
            background: rgba(0,0,0,.025);
        }
        #wcfmmp-store .mdo-store-shipping__note {
            margin: 18px 0 0;
            color: #777;
            font-size: .9em;
            line-height: 1.5;
        }
        @media (max-width: 600px) {
            #wcfmmp-store .mdo-store-shipping__minimum {
                align-items: flex-start;
                flex-direction: column;
                gap: 4px;
            }
            #wcfmmp-store .mdo-store-shipping__conditions {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <h2 id="mdo-store-shipping-title"><?php echo esc_html( mdo_sst_text( 'Envíos', 'Shipping' ) ); ?></h2>

    <p class="mdo-store-shipping__intro">
        <?php
        echo esc_html(
            mdo_sst_text(
                'Consulta las zonas a las que envía este productor y las condiciones configuradas actualmente para cada destino.',
                'See where this producer ships and the current conditions configured for each destination.'
            )
        );
        ?>
    </p>

    <?php if ( $minimum > 0 ) : ?>
        <div class="mdo-store-shipping__minimum">
            <span class="mdo-store-shipping__minimum-label">
                <?php echo esc_html( mdo_sst_text( 'Pedido mínimo de esta tienda', 'Minimum order for this store' ) ); ?>
            </span>
            <span class="mdo-store-shipping__minimum-price"><?php echo wp_kses_post( wc_price( $minimum ) ); ?></span>
        </div>
    <?php endif; ?>

    <?php if ( ! empty( $rows ) ) : ?>
        <div class="mdo-store-shipping__zones">
            <?php foreach ( $rows as $row ) : ?>
                <section class="mdo-store-shipping__zone">
                    <h3><?php echo esc_html( $row['name'] ); ?></h3>

                    <?php if ( ! empty( $row['locations'] ) ) : ?>
                        <p class="mdo-store-shipping__locations">
                            <?php echo esc_html( implode( ', ', $row['locations'] ) ); ?>
                        </p>
                    <?php endif; ?>

                    <dl class="mdo-store-shipping__conditions">
                        <?php if ( ! empty( $row['flat_costs'] ) ) : ?>
                            <div class="mdo-store-shipping__condition">
                                <dt><?php echo esc_html( mdo_sst_text( 'Coste de envío', 'Shipping cost' ) ); ?></dt>
                                <dd><?php echo esc_html( implode( ' / ', $row['flat_costs'] ) ); ?></dd>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $row['free_from'] ) ) : ?>
                            <div class="mdo-store-shipping__condition">
                                <dt><?php echo esc_html( mdo_sst_text( 'Envío gratuito', 'Free shipping' ) ); ?></dt>
                                <dd>
                                    <?php echo esc_html( mdo_sst_text( 'A partir de', 'From' ) ); ?>
                                    <?php echo wp_kses_post( wc_price( (float) $row['free_from'] ) ); ?>
                                </dd>
                            </div>
                        <?php endif; ?>

                        <?php foreach ( $row['notes'] as $note ) : ?>
                            <div class="mdo-store-shipping__condition">
                                <dt><?php echo esc_html( mdo_sst_text( 'Condición', 'Condition' ) ); ?></dt>
                                <dd><?php echo esc_html( $note ); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </section>
            <?php endforeach; ?>
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
</div>
