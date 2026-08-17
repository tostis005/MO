<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Workflow guard markers retained for the existing production runner.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

$source_id  = 9;
$vendor_id  = 4507;
$recipient  = 'jose.fraga@gmail.com';
$subject    = 'Tenemos nueva imagen. Pero eso es solo el principio 👀';
$state_key  = '_emdo_fluentcrm_tolecarnes_mail_20260817';

function emdo_campaign_abort( $message ) {
    fwrite( STDERR, 'EMDO_CRM_ABORT: ' . $message . "\n" );
    exit( 20 );
}

try {
    $campaign_class   = '\\FluentCrm\\App\\Models\\Campaign';
    $controller_class = '\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    $app_class        = '\\FluentCrm\\Framework\\Foundation\\App';

    if ( ! class_exists( $campaign_class ) || ! class_exists( $controller_class ) || ! class_exists( $app_class ) ) {
        emdo_campaign_abort( 'FluentCRM classes unavailable' );
    }

    $source = $campaign_class::find( $source_id );
    if ( ! $source ) {
        emdo_campaign_abort( 'Source campaign 9 not found' );
    }

    // Hard guard: never alter or send the source campaign.
    if ( (string) $source->status !== 'archived' || (int) $source->template_id !== 10453 || (string) $source->design_template !== 'simple' ) {
        emdo_campaign_abort( 'Source campaign no longer matches expected archived campaign' );
    }

    $app        = $app_class::getInstance();
    $request    = $app['request'];
    $controller = new $controller_class();

    $state = get_option( $state_key, array() );
    $draft = null;

    if ( ! empty( $state['campaign_id'] ) ) {
        $draft = $campaign_class::find( (int) $state['campaign_id'] );
        if ( ! $draft ) {
            emdo_campaign_abort( 'Saved draft campaign id no longer exists' );
        }
    } else {
        $duplicate = $controller->duplicateCampaign( $request, $source_id );
        $draft = isset( $duplicate['campaign'] ) ? $duplicate['campaign'] : null;
        if ( ! $draft || (int) $draft->id === $source_id ) {
            emdo_campaign_abort( 'Native campaign duplication failed' );
        }

        $state = array(
            'campaign_id' => (int) $draft->id,
            'test_sent'   => false,
            'created_at'  => current_time( 'mysql' ),
        );
        update_option( $state_key, $state, false );
    }

    // Resolve a working CTA. Prefer the native WCFM store URL; if its rewrite is
    // currently unavailable, point to a live product from the same producer.
    $cta_url = function_exists( 'wcfmmp_get_store_url' ) ? wcfmmp_get_store_url( $vendor_id ) : '';
    $cta_type = 'store';
    $store_ok = false;
    if ( $cta_url ) {
        $response = wp_remote_get( trailingslashit( $cta_url ), array(
            'timeout'     => 10,
            'redirection' => 2,
            'user-agent'  => 'EMDO FluentCRM campaign builder',
        ) );
        $store_ok = ! is_wp_error( $response ) && 200 === (int) wp_remote_retrieve_response_code( $response );
    }

    if ( ! $store_ok ) {
        $products = get_posts( array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'author'         => $vendor_id,
            'posts_per_page' => 1,
            'orderby'        => 'date',
            'order'          => 'DESC',
            'fields'         => 'ids',
        ) );
        if ( $products ) {
            $cta_url  = get_permalink( (int) $products[0] );
            $cta_type = 'product';
        } else {
            $cta_url  = home_url( '/?s=Tolecarnes' );
            $cta_type = 'search';
        }
    }

    if ( ! $cta_url ) {
        emdo_campaign_abort( 'Could not resolve CTA URL' );
    }

    $body = <<<'HTML'
<!-- wp:image {"id":4006,"width":"120px","sizeSlug":"thumbnail","linkDestination":"none","align":"center"} -->
<figure class="wp-block-image aligncenter size-thumbnail is-resized"><img src="https://www.elmercadodeorigen.com/wp-content/uploads/2021/03/Logo-sin-fondo-150x150.jpg" alt="" class="wp-image-4006" style="width:120px"/></figure>
<!-- /wp:image -->

<!-- wp:heading {"lineHeight":30,"marginBottom":20} -->
<h2 class="wp-block-heading" style="line-height:30px;margin-bottom:20px"><strong>Tenemos nueva imagen. Pero, en realidad, eso es lo de menos.</strong></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Durante las últimas semanas hemos estado renovando <strong>El Mercado de Origen</strong>, pero este cambio es solo el principio de algo que nos hace bastante más ilusión: <strong>volver con más fuerza a la idea por la que nació todo esto.</strong></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Acercar productores y clientes. Que puedas conocer quién está detrás de cada producto, de dónde viene y comprarlo directamente a quien lo produce.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"lineHeight":30,"marginBottom":20} -->
<h2 class="wp-block-heading" style="line-height:30px;margin-bottom:20px"><strong>Y para empezar, tenemos nuevo productor: Tolecarnes.</strong></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Una empresa familiar de los <strong>Montes de Toledo</strong>, con varias generaciones dedicadas a la ganadería y una selección de carne de ternera que desde ahora puedes encontrar en El Mercado de Origen.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Cortes de ternera, carne picada, preparados, hamburguesas… y para celebrar su llegada hemos preparado junto a Tolecarnes <strong>una promoción especial de lanzamiento</strong>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"lineHeight":30,"marginBottom":20} -->
<h2 class="wp-block-heading" style="line-height:30px;margin-bottom:20px"><strong>Durante los primeros pedidos, te llevas 2 hamburguesas de ternera de regalo con tu compra</strong>, hasta agotar las unidades disponibles para la promoción.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">El <strong>pedido mínimo es de 60 €</strong> y, además, si el pedido va a <strong>Madrid o Toledo, el envío es gratuito</strong>.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Así que, si todavía no conocías Tolecarnes, <strong>esta puede ser una buena ocasión para ponerle remedio</strong>.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"lineHeight":30,"marginBottom":20} -->
<h2 class="wp-block-heading" style="line-height:30px;margin-bottom:20px"><strong>Nosotros ponemos las hamburguesas. Lo de encender la parrilla ya corre por tu cuenta.</strong></h2>
<!-- /wp:heading -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"textColor":"white","width":100,"className":"is-style-fill","style":{"elements":{"link":{"color":{"text":"var:preset|color|white"}}},"border":{"radius":"0px"},"color":{"background":"#5eb041"},"typography":{"fontSize":"20px"}}} -->
<div class="wp-block-button has-custom-width wp-block-button__width-100 is-style-fill"><a class="wp-block-button__link has-white-color has-text-color has-background has-link-color has-custom-font-size wp-element-button" href="{{CTA_URL}}" style="border-radius:0px;background-color:#5eb041;font-size:20px"><strong>DESCUBRE TOLECARNES</strong></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:heading {"lineHeight":30,"marginBottom":20} -->
<h2 class="wp-block-heading" style="line-height:30px;margin-bottom:20px"><strong>Y esto es solo el principio.</strong></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Durante las próximas semanas iremos presentando <strong>nuevos productores, nuevos productos y alguna que otra sorpresa</strong> que ya estamos preparando.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"spacing":{"margin":{"bottom":"20px"}}}} -->
<p style="margin-bottom:20px">Porque si algo tenemos claro es que todavía queda mucho origen por descubrir.</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"lineHeight":30,"marginBottom":20} -->
<h2 class="wp-block-heading" style="line-height:30px;margin-bottom:20px"><strong>El Mercado de Origen</strong></h2>
<!-- /wp:heading -->
HTML;

    $body = str_replace( '{{CTA_URL}}', esc_url( $cta_url ), $body );

    // Modify only the duplicated draft. Template/design/settings remain those copied natively.
    $draft->title            = $subject;
    $draft->email_subject    = $subject;
    $draft->email_pre_header = '';
    $draft->email_body       = $body;
    $draft->status           = 'draft';
    $draft->save();
    $draft = $campaign_class::find( (int) $draft->id );

    if ( ! $draft || (int) $draft->id === $source_id ) {
        emdo_campaign_abort( 'Draft verification failed' );
    }
    if ( (string) $draft->status !== 'draft' || (int) $draft->template_id !== 10453 || (string) $draft->design_template !== 'simple' ) {
        emdo_campaign_abort( 'Draft design/status verification failed' );
    }
    if ( (string) $draft->email_subject !== $subject || false === strpos( (string) $draft->email_body, '2 hamburguesas' ) || false === strpos( (string) $draft->email_body, 'DESCUBRE TOLECARNES' ) ) {
        emdo_campaign_abort( 'Draft content verification failed' );
    }

    // Recheck the source was never changed.
    $source_check = $campaign_class::find( $source_id );
    if ( ! $source_check || (string) $source_check->status !== 'archived' ) {
        emdo_campaign_abort( 'Source campaign changed unexpectedly' );
    }

    if ( empty( $state['test_sent'] ) ) {
        // The native FluentCRM test endpoint reads these values from its app request.
        $request->merge( array(
            'test_campaign' => false,
            'campaign_id'   => (int) $draft->id,
            'email'         => $recipient,
        ) );

        $test = $controller->sendTestEmail();
        if ( is_array( $test ) && array_key_exists( 'result', $test ) && false === $test['result'] ) {
            emdo_campaign_abort( 'FluentCRM mailer returned false' );
        }

        $state['test_sent']    = true;
        $state['test_sent_at'] = current_time( 'mysql' );
        $state['test_message'] = is_array( $test ) && isset( $test['message'] ) ? sanitize_text_field( $test['message'] ) : 'sendTestEmail completed';
        update_option( $state_key, $state, false );
    }

    echo "=== EMDO_FLUENTCRM_ACTION_OK ===\n";
    echo 'SOURCE_CAMPAIGN_ID=' . (int) $source_id . "\n";
    echo 'SOURCE_STATUS=' . (string) $source_check->status . "\n";
    echo 'DRAFT_CAMPAIGN_ID=' . (int) $draft->id . "\n";
    echo 'DRAFT_STATUS=' . (string) $draft->status . "\n";
    echo 'DRAFT_TEMPLATE_ID=' . (int) $draft->template_id . "\n";
    echo 'DRAFT_DESIGN=' . (string) $draft->design_template . "\n";
    echo 'DRAFT_SUBJECT=' . (string) $draft->email_subject . "\n";
    echo 'CTA_TYPE=' . $cta_type . "\n";
    echo 'CTA_URL=' . esc_url_raw( $cta_url ) . "\n";
    echo 'TEST_RECIPIENT=' . $recipient . "\n";
    echo 'TEST_SENT=' . ( ! empty( $state['test_sent'] ) ? 'yes' : 'no' ) . "\n";
    echo 'TEST_MESSAGE=' . ( isset( $state['test_message'] ) ? $state['test_message'] : '' ) . "\n";
} catch ( Throwable $e ) {
    emdo_campaign_abort( get_class( $e ) . ': ' . $e->getMessage() );
}
