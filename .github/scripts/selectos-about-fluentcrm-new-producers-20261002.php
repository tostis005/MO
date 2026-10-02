<?php
/**
 * Production task: update Selectos de Castilla About copy and create/update
 * a FluentCRM draft for the October 2026 new-producers mailing.
 *
 * Safety:
 * - Finds WCFM vendors by their stored profile identity.
 * - Backs up the previous Selectos About fields once.
 * - Creates only a FluentCRM draft; never sends or schedules it.
 * - Idempotent across workflow retries.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit( 1 );
}

function emdo_new_producers_abort( $message, $code = 20 ) {
    fwrite( STDERR, 'EMDO_NEW_PRODUCERS_ABORT: ' . $message . "\n" );
    exit( $code );
}

function emdo_normalize_identity( $value ) {
    return strtolower( remove_accents( wp_strip_all_tags( (string) $value ) ) );
}

function emdo_find_wcfm_vendor( $mode ) {
    global $wpdb;

    $rows = $wpdb->get_results(
        "SELECT um.user_id, u.user_login, u.user_nicename, u.display_name, um.meta_value
         FROM {$wpdb->usermeta} um
         INNER JOIN {$wpdb->users} u ON u.ID = um.user_id
         WHERE um.meta_key = 'wcfmmp_profile_settings'",
        ARRAY_A
    );

    $matches = array();

    foreach ( (array) $rows as $row ) {
        $settings = maybe_unserialize( $row['meta_value'] ?? '' );
        if ( ! is_array( $settings ) ) {
            continue;
        }

        $haystack = emdo_normalize_identity( implode( ' ', array(
            (string) ( $settings['store_name'] ?? '' ),
            (string) ( $settings['store_slug'] ?? '' ),
            (string) $row['user_login'],
            (string) $row['user_nicename'],
            (string) $row['display_name'],
        ) ) );

        $ok = false;
        if ( 'selectos' === $mode ) {
            $ok = false !== strpos( $haystack, 'selectos de castilla' )
                || false !== strpos( $haystack, 'selectos-de-castilla' );
        } elseif ( 'montjam' === $mode ) {
            $ok = false !== strpos( $haystack, 'montjam' )
                || false !== strpos( $haystack, 'mont jam' );
        } elseif ( 'huerta' === $mode ) {
            $ok = false !== strpos( $haystack, 'huerta' )
                && false !== strpos( $haystack, 'ana' )
                && ( false !== strpos( $haystack, 'mary' ) || false !== strpos( $haystack, 'anamary' ) );
        }

        if ( $ok ) {
            $row['_settings'] = $settings;
            $matches[] = $row;
        }
    }

    if ( 1 !== count( $matches ) ) {
        emdo_new_producers_abort( 'expected exactly one WCFM vendor for ' . $mode . ', found ' . count( $matches ), 21 );
    }

    $match = $matches[0];
    $user = get_userdata( (int) $match['user_id'] );
    if ( ! $user instanceof WP_User || ! in_array( 'wcfm_vendor', (array) $user->roles, true ) ) {
        emdo_new_producers_abort( 'matched user for ' . $mode . ' is not an active wcfm_vendor identity', 22 );
    }

    return array(
        'user'     => $user,
        'settings' => $match['_settings'],
    );
}

function emdo_vendor_store_url( $vendor ) {
    $user_id = (int) $vendor['user']->ID;
    $settings = $vendor['settings'];

    $url = '';
    if ( function_exists( 'wcfmmp_get_store_url' ) ) {
        $url = (string) wcfmmp_get_store_url( $user_id );
    }

    if ( '' === trim( $url ) ) {
        $slug = sanitize_title( (string) ( $settings['store_slug'] ?? '' ) );
        if ( '' === $slug ) {
            $slug = sanitize_title( (string) $vendor['user']->user_nicename );
        }
        $url = home_url( '/tienda/' . $slug . '/' );
    }

    return trailingslashit( $url );
}

$selectos_about = <<<'HTML'
<p>Selectos de Castilla nació en 1989 en Villamartín de Campos, en la provincia de Palencia, con una especialización muy concreta: la cría de pato y la elaboración de foie gras y otros productos derivados.</p>

<p>Desde sus comienzos, el proyecto ha unido la tradición gastronómica francesa vinculada al foie gras con el entorno agrícola de Tierra de Campos. La cría se realiza en régimen de semilibertad y en amplias extensiones, con una alimentación estrechamente relacionada con los cereales propios de la zona.</p>

<p>Cuando los animales alcanzan la edad adulta comienza la fase de cebo, realizada con maíz en grano y de forma individual. Es una parte especialmente importante del proceso y una de las que posteriormente determina las características del foie gras y de las distintas piezas de carne de pato.</p>

<p>La elaboración continúa en sus propias instalaciones de Villamartín de Campos. El despiece se realiza manualmente y buena parte de los productos mantienen una elaboración artesanal basada en recetas y procesos desarrollados desde los primeros años de la empresa, buscando respetar al máximo la materia prima.</p>

<p>De este trabajo nace una gama especialmente amplia alrededor del pato: foie gras fresco y mi-cuit, magret, confit, jamón de pato, mousses, parfaits, rillettes y diferentes cortes frescos forman parte de una especialización que permite recorrer prácticamente todas las posibilidades gastronómicas de este producto.</p>

<p>Con más de tres décadas de trayectoria, Selectos de Castilla ha ido incorporando sistemas de control y nuevas tecnologías aplicadas a la producción y la seguridad alimentaria, manteniendo al mismo tiempo una elaboración estrechamente ligada al producto, al territorio y a la especialización en pato.</p>
HTML;

$subject = 'Tres nuevos productores llegan a El Mercado de Origen';
$preheader = 'Selectos de Castilla, Montjam, La Huerta de Ana Mary y una prueba de Edenred / Ticket Restaurant hasta final de año.';
$state_key = '_emdo_fluentcrm_new_producers_20261002';

try {
    $selectos = emdo_find_wcfm_vendor( 'selectos' );
    $montjam  = emdo_find_wcfm_vendor( 'montjam' );
    $huerta   = emdo_find_wcfm_vendor( 'huerta' );

    $selectos_id = (int) $selectos['user']->ID;
    $selectos_settings = $selectos['settings'];

    $backup_key = '_emdo_selectos_about_backup_20261002';
    if ( '' === (string) get_user_meta( $selectos_id, $backup_key, true ) ) {
        update_user_meta( $selectos_id, $backup_key, array(
            '_store_description' => (string) get_user_meta( $selectos_id, '_store_description', true ),
            'shop_description'   => (string) ( $selectos_settings['shop_description'] ?? '' ),
            'saved_at'           => current_time( 'mysql' ),
        ) );
    }

    update_user_meta( $selectos_id, '_store_description', $selectos_about );
    $selectos_settings['shop_description'] = $selectos_about;
    update_user_meta( $selectos_id, 'wcfmmp_profile_settings', $selectos_settings );
    clean_user_cache( $selectos_id );

    $saved_primary = (string) get_user_meta( $selectos_id, '_store_description', true );
    $saved_settings = get_user_meta( $selectos_id, 'wcfmmp_profile_settings', true );
    $saved_profile = is_array( $saved_settings ) ? (string) ( $saved_settings['shop_description'] ?? '' ) : '';

    if ( $saved_primary !== $selectos_about || $saved_profile !== $selectos_about ) {
        emdo_new_producers_abort( 'Selectos About persistence verification failed', 23 );
    }

    $selectos_url = emdo_vendor_store_url( array( 'user' => $selectos['user'], 'settings' => $saved_settings ) );
    $montjam_url  = emdo_vendor_store_url( $montjam );
    $huerta_url   = emdo_vendor_store_url( $huerta );

    $mail_body = <<<'HTML'
<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Tenemos novedades en <strong>El Mercado de Origen</strong>.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Durante las últimas semanas hemos incorporado tres nuevos productores que amplían bastante la variedad del mercado: pato y foie gras desde Palencia, ibéricos desde Huelva y producto de huerta desde León.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Tres proyectos muy distintos, pero con algo en común: detrás de cada producto sabemos quién lo hace, dónde y cómo.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;"><strong>Selectos de Castilla</strong></p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Desde Villamartín de Campos, en Palencia, Selectos de Castilla lleva desde 1989 especializado en la cría de pato y en la elaboración de foie gras y otros productos derivados.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Trabajan prácticamente todas las posibilidades gastronómicas del pato: foie gras fresco y mi-cuit, magret, confit, jamón de pato, patés, mousses, rillettes y distintos cortes frescos.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 18px;">Más de tres décadas de especialización en un producto muy concreto que ahora puedes encontrar en El Mercado de Origen.</p>

<div class="wp-block-buttons" style="margin:0 0 26px;"><div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-fill"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color wp-element-button" href="{{SELECTOS_URL}}" style="display:block;border-radius:0;background-color:#5eb041;color:#ffffff;text-align:center;padding:12px 18px;text-decoration:none;"><strong>DESCUBRIR SELECTOS DE CASTILLA</strong></a></div></div>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;"><strong>Montjam</strong></p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Nos vamos hasta El Repilado, en plena Sierra de Aracena y Picos de Aroche, en Huelva.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Montjam cuenta con cuatro generaciones vinculadas a la elaboración de productos del cerdo ibérico. Jamones, paletas y embutidos elaborados en una de las zonas con mayor tradición ibérica de España, incluyendo piezas amparadas por la DOP Jabugo.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 18px;">En su tienda puedes encontrar desde jamones y paletas de bellota hasta cebo de campo, piezas DOP Jabugo y distintos formatos loncheados.</p>

<div class="wp-block-buttons" style="margin:0 0 26px;"><div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-fill"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color wp-element-button" href="{{MONTJAM_URL}}" style="display:block;border-radius:0;background-color:#5eb041;color:#ffffff;text-align:center;padding:12px 18px;text-decoration:none;"><strong>DESCUBRIR MONTJAM</strong></a></div></div>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;"><strong>La Huerta de Ana Mary</strong></p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">De Huelva nos vamos a Fresno de la Vega, en León, una localidad estrechamente vinculada desde hace generaciones al cultivo de hortalizas.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">La Huerta de Ana Mary continúa esa tradición familiar con verduras y hortalizas cultivadas en la zona y una forma de trabajar en la que la recolección se organiza en función de los pedidos para reducir el tiempo de almacenamiento y acortar el recorrido entre la huerta y casa.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 18px;">Producto fresco de temporada, pimientos, tomates, verduras, legumbres, conservas y otras elaboraciones forman parte de su propuesta.</p>

<div class="wp-block-buttons" style="margin:0 0 28px;"><div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-fill"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color wp-element-button" href="{{HUERTA_URL}}" style="display:block;border-radius:0;background-color:#5eb041;color:#ffffff;text-align:center;padding:12px 18px;text-decoration:none;"><strong>DESCUBRIR LA HUERTA DE ANA MARY</strong></a></div></div>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;"><strong>Hasta final de año aceptamos Edenred / Ticket Restaurant en periodo de prueba.</strong></p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Desde ahora y hasta el <strong>31 de diciembre de 2026</strong>, vamos a realizar una prueba de aceptación de <strong>Edenred / Ticket Restaurant</strong> como forma de pago en El Mercado de Origen.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Durante estos meses queremos comprobar cómo funciona y qué acogida tiene entre nuestros clientes antes de decidir si lo mantenemos de forma permanente.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 22px;">Así que, si tienes una tarjeta Edenred o Ticket Restaurant, durante este periodo también puedes utilizarla para realizar tus compras en El Mercado de Origen.</p>

<p style="font-size:16px;line-height:1.6;margin:0 0 16px;">Esperamos que disfrutes descubriendo a los nuevos productores y, sobre todo, los productos que hay detrás de cada uno de ellos.</p>

<p style="font-size:16px;line-height:1.6;margin:0;"><strong>El Mercado de Origen</strong></p>
HTML;

    $mail_body = str_replace(
        array( '{{SELECTOS_URL}}', '{{MONTJAM_URL}}', '{{HUERTA_URL}}' ),
        array( esc_url( $selectos_url ), esc_url( $montjam_url ), esc_url( $huerta_url ) ),
        $mail_body
    );

    $campaign_class   = '\FluentCrm\App\Models\Campaign';
    $controller_class = '\FluentCrm\App\Http\Controllers\CampaignController';
    $app_class        = '\FluentCrm\Framework\Foundation\App';

    if ( ! class_exists( $campaign_class ) || ! class_exists( $controller_class ) || ! class_exists( $app_class ) ) {
        emdo_new_producers_abort( 'FluentCRM classes unavailable', 24 );
    }

    $state = get_option( $state_key, array() );
    $draft = null;

    if ( ! empty( $state['campaign_id'] ) ) {
        $draft = $campaign_class::find( (int) $state['campaign_id'] );
    }

    if ( ! $draft ) {
        $draft = $campaign_class::where( 'status', 'draft' )
            ->where( 'email_subject', $subject )
            ->orderBy( 'id', 'DESC' )
            ->first();
    }

    if ( ! $draft ) {
        $source = $campaign_class::where( 'status', 'archived' )
            ->where( 'design_template', 'simple' )
            ->orderBy( 'id', 'DESC' )
            ->first();

        if ( ! $source ) {
            $source = $campaign_class::where( 'status', 'archived' )
                ->orderBy( 'id', 'DESC' )
                ->first();
        }

        if ( ! $source ) {
            emdo_new_producers_abort( 'no archived FluentCRM source campaign available for safe duplication', 25 );
        }

        $app = $app_class::getInstance();
        $request = $app['request'];
        $controller = new $controller_class();
        $duplicate = $controller->duplicateCampaign( $request, (int) $source->id );
        $draft = isset( $duplicate['campaign'] ) ? $duplicate['campaign'] : null;

        if ( ! $draft || (int) $draft->id === (int) $source->id ) {
            emdo_new_producers_abort( 'FluentCRM native campaign duplication failed', 26 );
        }

        $state = array(
            'campaign_id'        => (int) $draft->id,
            'source_campaign_id' => (int) $source->id,
            'created_at'         => current_time( 'mysql' ),
            'sent'               => false,
        );
        update_option( $state_key, $state, false );
    }

    $draft->title            = $subject;
    $draft->email_subject    = $subject;
    $draft->email_pre_header = $preheader;
    $draft->email_body       = $mail_body;
    $draft->status           = 'draft';
    $draft->save();

    $draft = $campaign_class::find( (int) $draft->id );
    if ( ! $draft ) {
        emdo_new_producers_abort( 'FluentCRM draft disappeared after save', 27 );
    }

    if ( 'draft' !== (string) $draft->status ) {
        emdo_new_producers_abort( 'FluentCRM campaign is not draft after save', 28 );
    }

    $saved_body = (string) $draft->email_body;
    foreach ( array(
        'DESCUBRIR SELECTOS DE CASTILLA',
        'DESCUBRIR MONTJAM',
        'DESCUBRIR LA HUERTA DE ANA MARY',
        '31 de diciembre de 2026',
        'Edenred / Ticket Restaurant',
    ) as $marker ) {
        if ( false === strpos( $saved_body, $marker ) ) {
            emdo_new_producers_abort( 'campaign body verification marker missing: ' . $marker, 29 );
        }
    }

    if ( false === strpos( $saved_body, esc_url( $selectos_url ) )
        || false === strpos( $saved_body, esc_url( $montjam_url ) )
        || false === strpos( $saved_body, esc_url( $huerta_url ) ) ) {
        emdo_new_producers_abort( 'one or more producer CTA URLs were not saved', 30 );
    }

    // Explicit safety assertion: this task never sends or schedules the campaign.
    $state['campaign_id'] = (int) $draft->id;
    $state['updated_at'] = current_time( 'mysql' );
    $state['sent'] = false;
    update_option( $state_key, $state, false );

    echo "EMDO_SELECTOS_CRM_DRAFT_OK\n";
    echo 'SELECTOS_USER_ID=' . $selectos_id . "\n";
    echo 'SELECTOS_STORE_SLUG=' . sanitize_title( (string) ( $saved_settings['store_slug'] ?? $selectos['user']->user_nicename ) ) . "\n";
    echo 'SELECTOS_STORE_URL=' . esc_url_raw( $selectos_url ) . "\n";
    echo 'MONTJAM_STORE_URL=' . esc_url_raw( $montjam_url ) . "\n";
    echo 'HUERTA_STORE_URL=' . esc_url_raw( $huerta_url ) . "\n";
    echo 'FLUENTCRM_CAMPAIGN_ID=' . (int) $draft->id . "\n";
    echo 'FLUENTCRM_STATUS=' . (string) $draft->status . "\n";
    echo 'FLUENTCRM_SUBJECT=' . (string) $draft->email_subject . "\n";
    echo 'FLUENTCRM_BODY_SHA256=' . hash( 'sha256', $saved_body ) . "\n";
    echo "FLUENTCRM_SENT=no\n";
} catch ( Throwable $e ) {
    emdo_new_producers_abort( get_class( $e ) . ': ' . $e->getMessage(), 31 );
}
