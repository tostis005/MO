<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
function mdo_gmf_admin_parent_v1(): string {
    global $menu;
    foreach ( (array) $menu as $entry ) {
        $label = isset( $entry[0] ) ? strtolower( wp_strip_all_tags( (string) $entry[0] ) ) : '';
        if ( false !== strpos( $label, 'emdo' ) || false !== strpos( $label, 'el mercado de origen' ) ) {
            return isset( $entry[2] ) ? (string) $entry[2] : 'woocommerce';
        }
    }
    return 'woocommerce';
}

function mdo_gmf_admin_page_v1(): void {
    if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to access this page.' ) );
    }

    $feeds = mdo_gmf_actual_country_map_v1();
    ?>
    <div class="wrap">
        <h1>Google Shopping · Feeds por país</h1>
        <p>Feeds XML dinámicos de EMDO. España usa contenido en español; el resto de destinos usa únicamente la versión inglesa publicada. Los importes de producto, envío, umbral de envío gratuito y pedido mínimo se publican en EUR.</p>
        <p><strong>Seguridad Merchant:</strong> solo se incluyen tiendas activas/aprobadas, productos públicos con precio e imagen, destinos realmente servidos y tarifas de envío que EMDO puede determinar de forma numérica.</p>
        <?php if ( empty( $feeds ) ) : ?>
            <div class="notice notice-warning inline"><p>No hay actualmente países con ofertas elegibles para Google Merchant.</p></div>
        <?php else : ?>
            <table class="widefat striped" style="max-width:1200px">
                <thead>
                    <tr>
                        <th>País</th>
                        <th>Idioma</th>
                        <th>Moneda</th>
                        <th>Ofertas</th>
                        <th>Estado</th>
                        <th>URL XML</th>
                        <th>Última consulta</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $feeds as $country => $name ) :
                    $url = home_url( '/emdo-feed/' . strtolower( $country ) . '.xml' );
                    $count = mdo_gmf_offer_count_v1( $country );
                    $last = (int) get_option( 'mdo_gmf_last_fetch_' . $country, 0 );
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html( $name ); ?></strong> <code><?php echo esc_html( $country ); ?></code></td>
                        <td><?php echo 'ES' === $country ? 'Español' : 'English'; ?></td>
                        <td>EUR</td>
                        <td><?php echo esc_html( number_format_i18n( $count ) ); ?></td>
                        <td><?php echo $count > 0 ? '<span style="color:#008a20;font-weight:600">Activo</span>' : '<span style="color:#b26200;font-weight:600">Sin ofertas elegibles</span>'; ?></td>
                        <td style="min-width:430px">
                            <input type="text" readonly value="<?php echo esc_attr( $url ); ?>" class="regular-text mdo-gmf-url" style="width:72%;min-width:320px" />
                            <button type="button" class="button mdo-gmf-copy">Copiar URL</button>
                            <a class="button" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">Abrir</a>
                        </td>
                        <td><?php echo $last > 0 ? esc_html( wp_date( 'd/m/Y H:i', $last ) ) : '—'; ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <p style="margin-top:18px;color:#646970">Las URLs son vivas: Google recibe el catálogo y las reglas actuales cada vez que solicita el XML. No hay archivos que regenerar manualmente. Si un país muestra 0 ofertas, el XML sigue existiendo pero conviene revisar traducciones inglesas o una tarifa de envío no numérica antes de darlo de alta en Merchant.</p>
    </div>
    <script>
    document.addEventListener('click', function (event) {
        var button = event.target.closest('.mdo-gmf-copy');
        if (!button) return;
        var input = button.parentNode.querySelector('.mdo-gmf-url');
        if (!input) return;
        var done = function () {
            var old = button.textContent;
            button.textContent = 'Copiado';
            setTimeout(function(){ button.textContent = old; }, 1200);
        };
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(input.value).then(done);
        } else {
            input.select();
            document.execCommand('copy');
            done();
        }
    });
    </script>
    <?php
}

add_action( 'admin_menu', static function(): void {
    add_submenu_page(
        mdo_gmf_admin_parent_v1(),
        'Google Shopping Feeds',
        'Google Shopping Feeds',
        'manage_woocommerce',
        'mdo-google-shopping-feeds',
        'mdo_gmf_admin_page_v1'
    );
}, 99 );
