<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Native OpenAI provider that writes only the current eligible catalog.
 *
 * The generic provider keeps 14-day tombstones for removed items. That is not
 * appropriate for El Mercado de Origen because a disabled vendor, hidden item,
 * or product without its required image must disappear from the physical file
 * immediately rather than remain as an ineligible historical row.
 */
final class MDO_OpenAI_Strict_Snapshot_Provider_20260910 extends MDO_OpenAI_Abstract_Provider_20260909 {
    protected string $mode = 'native';

    public function key(): string { return 'openai_native_strict_current'; }
    public function label(): string { return 'OpenAI Native Commerce — current public catalog only'; }
    protected function build_record( WC_Product $product, array &$report ): ?array {
        return mdo_openai_native_record_20260909( $product, $this->settings, $report );
    }

    public function generate(): array {
        if ( $error = $this->preflight() ) {
            return array( 'ok'=>false, 'error'=>$error, 'report'=>mdo_openai_report_template_20260909() );
        }

        $start = microtime( true );
        $report = mdo_openai_report_template_20260909();
        $meta = mdo_openai_format_meta_20260909( $this->settings['format'] );
        $report['filename'] = $meta['filename'];
        $report['format'] = (string) $this->settings['format'];
        $report['retired'] = 0;

        $final = mdo_openai_feed_path_20260909( $this->settings['format'] );
        $tmp = $final . '.tmp';
        $gz = gzopen( $tmp, 'wb9' );
        if ( false === $gz ) {
            return array( 'ok'=>false, 'error'=>'No se pudo abrir el archivo temporal.', 'report'=>$report );
        }

        $columns = mdo_openai_native_columns_20260909();
        if ( 'jsonl' !== $meta['kind'] ) {
            mdo_openai_gz_write_csv_20260909( $gz, $columns, array_combine( $columns, $columns ), (string) $meta['delimiter'] );
        }

        $manifest_tmp = mdo_openai_manifest_path_20260909() . '.tmp';
        $manifest_fh = fopen( $manifest_tmp, 'wb' );
        if ( false === $manifest_fh ) {
            gzclose( $gz );
            @unlink( $tmp );
            return array( 'ok'=>false, 'error'=>'No se pudo abrir el manifiesto temporal.', 'report'=>$report );
        }

        $state = array( 'item_ids'=>array(), 'variants'=>array() );
        $write = static function( $gz_handle, array $format_meta, array $column_names, array $row ): void {
            if ( 'jsonl' === $format_meta['kind'] ) {
                gzwrite( $gz_handle, wp_json_encode( $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . "\n" );
                return;
            }
            mdo_openai_gz_write_csv_20260909( $gz_handle, $column_names, $row, (string) $format_meta['delimiter'] );
        };

        mdo_openai_foreach_product_20260909(
            function( WC_Product $product ) use ( &$report, &$state, $gz, $meta, $columns, $manifest_fh, $write ): void {
                $row = $this->build_record( $product, $report );
                if ( ! is_array( $row ) || ! mdo_openai_validate_record_20260909( $row, 'native', $state, $report ) ) { return; }

                $write( $gz, $meta, $columns, $row );
                ++$report['exported'];
                fwrite(
                    $manifest_fh,
                    wp_json_encode(
                        array( 'item_id'=>(string) $row['item_id'], 'last_seen'=>time(), 'row'=>$row ),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ) . "\n"
                );
            },
            $report
        );

        fclose( $manifest_fh );
        gzclose( $gz );

        if ( ! @rename( $tmp, $final ) ) {
            @unlink( $tmp );
            @unlink( $manifest_tmp );
            return array( 'ok'=>false, 'error'=>'No se pudo reemplazar el snapshot.', 'report'=>$report );
        }
        @rename( $manifest_tmp, mdo_openai_manifest_path_20260909() );

        $report['duration_ms'] = (int) round( ( microtime( true ) - $start ) * 1000 );
        $report['bytes'] = is_file( $final ) ? (int) filesize( $final ) : 0;
        update_option( MDO_OPENAI_COMMERCE_REPORT_OPTION, $report, false );
        mdo_openai_log_20260909(
            'generation',
            'Snapshot OpenAI estricto generado.',
            array(
                'format'=>$report['format'],
                'exported'=>$report['exported'],
                'retired'=>0,
                'errors'=>$report['errors'],
                'warnings'=>$report['warnings'],
                'bytes'=>$report['bytes'],
            )
        );

        return array( 'ok'=>true, 'path'=>$final, 'report'=>$report );
    }
}

function mdo_openai_force_strict_current_provider_20260910( $provider, array $settings ) {
    $meta = mdo_openai_format_meta_20260909( (string) ( $settings['format'] ?? 'native_jsonl_gz' ) );
    if ( 'native' === (string) ( $meta['provider'] ?? '' ) ) {
        return new MDO_OpenAI_Strict_Snapshot_Provider_20260910( $settings );
    }
    return $provider;
}
add_filter( 'mdo_openai_feed_provider', 'mdo_openai_force_strict_current_provider_20260910', PHP_INT_MAX, 2 );
