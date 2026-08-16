<?php
/**
 * Second-pass cleanup for repeated Spanish blocks inside reviewed English product content.
 * Matches by source content, not by product ID, because the same copy is reused across products.
 */
if ( PHP_SAPI !== 'cli' || empty( $argv[1] ) ) { exit( 2 ); }
$root = rtrim( (string) $argv[1], '/' );
ini_set( 'memory_limit', '512M' );
require $root . '/wp-load.php';
global $wpdb;

$backup = array( 'generated_at' => gmdate( 'c' ), 'post_meta' => array() );
$changes = 0;

$pdo_replacement = "Protected Designations of Origin are used in Spain to recognise differentiated quality resulting from specific characteristics linked to the geographical environment in which the raw materials are produced and the products are made, as well as the influence of the people involved in the process. Producers that use a PDO undertake to follow defined standards to maintain consistently high quality. A public body regulates the designation and monitors compliance with those standards. Spanish PDOs are protected under European Union regulations.\r\n\r\n<u><strong>What is a PDO for?</strong></u>\r\n\r\nFor consumers, above all, it guarantees specific characteristics and a high, consistent level of quality.\r\n\r\nFor producers, it protects them against similar products made in other areas and helps them reach new markets backed by the quality reputation of the PDO.\r\n\r\n";

$oil_replacement = "Extra virgin olive oil is a 100% natural juice that preserves the aroma, flavour, vitamins and all the properties of the olive. It is the only vegetable oil that can be consumed exactly as it is obtained. It comes directly from olives by extracting their juice, although the process is a little more complex than squeezing an orange.\r\n\r\nWhen the olives arrive at the mill, they are first cleaned to remove impurities such as leaves and small twigs that may have been collected with the fruit. Once clean, the olives are ready to be processed. Milling consists essentially of four stages. Throughout the process, both the olives and the oil remain in contact only with inert materials (stainless steel) to prevent any alteration.\r\n\r\nThe first stage is <strong>milling</strong>. The olives are crushed to break the cell structures that contain the oil. In simple terms, it is like putting the olives in a large food blender. The result is an olive paste: crushed olives containing pieces of stone and skin, oil and vegetation water.\r\n\r\nThe next stage is <strong>malaxation</strong>. The olive paste is slowly mixed to make it uniform and encourage the small droplets of oil to join together into larger drops. It is important that mixing does not exceed 90 minutes and that the paste temperature remains below 27–28 °C.\r\n\r\nOnce mixed, the paste moves to <strong>centrifugation</strong>. In a decanter it is spun at high speed to separate the different components according to density. Three concentric layers form inside the cylindrical drum: pomace on the outside, vegetation water in the middle and olive oil on the inside.\r\n\r\nThe final stage is <strong>filtration. </strong>";

$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key='_en_US_post_content' AND meta_value<>''" );
foreach ( $rows as $row ) {
    $post_id = (int) $row->post_id;
    $old = (string) $row->meta_value;
    $new = $old;

    // The English heading immediately following this section is a stable boundary even where the Spanish wording varies slightly.
    if ( strpos( $new, 'Constituyen el sistema utilizado en nuestro país para el reconocimiento de una calidad diferenciada' ) !== false ) {
        $new = (string) preg_replace(
            '#Constituyen el sistema utilizado en nuestro país para el reconocimiento de una calidad diferenciada.*?(?=<strong>\s*How are Iberian pigs raised\?\s*</strong>)#su',
            $pdo_replacement,
            $new
        );
    }

    // Several olive-oil products share the same partially translated explanation. The final filtration paragraph was already English,
    // so replace the Spanish section up to its existing English filtration heading and keep the English tail intact.
    if ( strpos( $new, 'El aceite de oliva virgen extra es un zumo 100% natural' ) !== false ) {
        $new = (string) preg_replace(
            '#El aceite de oliva virgen extra es un zumo 100% natural.*?El último paso es el\s*<strong>\s*filtration\.\s*</strong>#su',
            $oil_replacement,
            $new
        );
    }

    if ( $new === $old ) { continue; }
    $backup['post_meta'][] = array( 'post_id' => $post_id, 'key' => '_en_US_post_content', 'value' => $old );
    update_post_meta( $post_id, '_en_US_post_content', $new );
    $changes++;
    echo "POST_V2_UPDATED|{$post_id}\n";
}

$dir = $root . '/wp-content/uploads/mdo-backups';
if ( ! is_dir( $dir ) ) { wp_mkdir_p( $dir ); }
$path = $dir . '/english-copy-residuals-v2-' . gmdate( 'YmdHis' ) . '.json';
file_put_contents( $path, wp_json_encode( $backup, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) );
echo "BACKUP={$path}\n";
echo "CHANGES={$changes}\n";
