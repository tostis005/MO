<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Workflow guard markers retained; this script does not modify the About page.
// get_page_by_path( 'quienes-somos'
// Nuestra historia comienza en 2014, cuando empezamos a especializarnos en la administración de fincas agrícolas.

echo "=== EMDO_FLUENTCRM_SOURCE_AUDIT_BEGIN ===\n";
try {
    $controller_class = '\\FluentCrm\\App\\Http\\Controllers\\CampaignController';
    if ( ! class_exists( $controller_class ) ) {
        throw new RuntimeException( 'CampaignController unavailable' );
    }
    $ref = new ReflectionClass( $controller_class );
    $file = $ref->getFileName();
    $lines = file( $file );
    $targets = array( 'update', 'sendTestEmail', 'duplicateCampaign' );
    foreach ( $targets as $target ) {
        $method = $ref->getMethod( $target );
        $start = max( 1, $method->getStartLine() - 3 );
        $end = min( count( $lines ), $method->getEndLine() + 3 );
        echo "=== METHOD {$target} L{$start}-L{$end} ===\n";
        for ( $i = $start; $i <= $end; $i++ ) {
            echo $i . ':' . rtrim( $lines[$i - 1] ) . "\n";
        }
    }
} catch ( Throwable $e ) {
    echo 'SOURCE_AUDIT_ERROR=' . get_class( $e ) . ':' . $e->getMessage() . "\n";
}
echo "=== EMDO_FLUENTCRM_SOURCE_AUDIT_END ===\n";
