<?php
if ( ! defined( 'WP_CLI' ) ) return;

class MERC_Import_Worker_Command {

    public function process( $args, $assoc_args ) {
        global $wpdb;

        $limit = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 1;
        $job_id = isset( $assoc_args['job-id'] ) ? (int) $assoc_args['job-id'] : 0;
        $processed = 0;

        while ( $processed < $limit ) {
            if ( $job_id ) {
                $job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM " . MERC_Import_Job::table_name() . " WHERE id=%d LIMIT 1", $job_id ) );
                if ( $job && $job->status === 'queued' ) {
                    $wpdb->update( MERC_Import_Job::table_name(), [ 'status' => 'processing', 'updated_at' => current_time( 'mysql' ) ], [ 'id' => $job->id ], [ '%s', '%s' ], [ '%d' ] );
                    $job->status = 'processing';
                }
            } else {
                $job = MERC_Import_Job::claim_next_job();
            }
            if ( ! $job ) {
                WP_CLI::log( 'No more queued jobs.' );
                return;
            }

            $job_id = (int) $job->id;
            WP_CLI::log( "Processing job {$job_id}: {$job->original_name} ({$job->file_path})" );

            try {
                MERC_Import_Job::update_status( $job_id, 'processing' );
                $this->process_file_job( $job );
                MERC_Import_Job::update_status( $job_id, 'completed' );
                WP_CLI::log( "Job {$job_id} completed." );
            } catch ( Exception $e ) {
                $msg = $e->getMessage();
                error_log( "[MERC_IMPORT][JOB_{$job_id}] Error: " . $msg );
                MERC_Import_Job::update_status( $job_id, 'failed', $msg );
                WP_CLI::error( "Job {$job_id} failed: {$msg}" );
            }

            $processed++;
        }
    }

    private function process_file_job( $job ) {
        global $wpdb;
        $path = $job->file_path;
        if ( ! file_exists( $path ) || ! is_readable( $path ) ) {
            throw new Exception( 'Archivo no encontrado o no legible: ' . $path );
        }

        $handle = fopen( $path, 'r' );
        if ( ! $handle ) {
            throw new Exception( 'No se pudo abrir el archivo: ' . $path );
        }

        // Leer cabecera
        $headers = [];
        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            // skip empty rows
            $nonEmpty = false;
            foreach ( $row as $c ) { if ( is_string( $c ) && strlen( trim( $c ) ) > 0 ) { $nonEmpty = true; break; } }
            if ( ! $nonEmpty ) continue;
            $headers = $row;
            break;
        }

        if ( empty( $headers ) ) {
            fclose( $handle );
            throw new Exception( 'CSV sin cabecera válida.' );
        }

        $chunk_size = 100;
        $chunk = [];
        $row_index = 0;
        $job_processed = 0;
        $job_failed = 0;

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $nonEmpty = false;
            foreach ( $row as $c ) { if ( is_string( $c ) && strlen( trim( $c ) ) > 0 ) { $nonEmpty = true; break; } }
            if ( ! $nonEmpty ) continue;

            $record = [];
            foreach ( $headers as $i => $h ) {
                $key = is_string( $h ) ? trim( $h ) : "col_{$i}";
                $record[ $key ] = isset( $row[ $i ] ) ? $row[ $i ] : '';
            }

            $chunk[] = $record;
            $row_index++;

            if ( count( $chunk ) >= $chunk_size ) {
                list( $p, $f ) = $this->process_chunk( $chunk, $job->id );
                $job_processed += $p;
                $job_failed += $f;
                MERC_Import_Job::update_metrics( $job->id, $p, $f );
                $chunk = [];
            }
        }

        if ( count( $chunk ) ) {
            list( $p, $f ) = $this->process_chunk( $chunk, $job->id );
            $job_processed += $p;
            $job_failed += $f;
            MERC_Import_Job::update_metrics( $job->id, $p, $f );
        }

        fclose( $handle );
    }

    private function process_chunk( array $rows, int $job_id ) {
        global $wpdb;

        // Iniciar transacción
        $wpdb->query( 'START TRANSACTION' );
        $ok = true;

        try {
            $processed = 0;
            $failed = 0;
            foreach ( $rows as $record ) {
                // Validar el registro antes de insertar. Si es inválido, guardar motivo y saltar.
                if ( class_exists( 'MERC_CSV_Preprocessor' ) ) {
                    $v = MERC_CSV_Preprocessor::validate_record_array( $record );
                    // Diagnostic: log validation output for debugging
                    error_log( '[MERC_IMPORT][VALIDATION] record validation result: ' . maybe_serialize( $v ) );
                    if ( isset( $v['valid'] ) && $v['valid'] === false ) {
                        // intentar obtener tracking para mapear el motivo
                        $tracking = '';
                        $possible = array( 'wpcargo_tracking_number', 'tracking', 'wpcargo_shipment_number', 'post_title', 'shipment_number' );
                        foreach ( $possible as $k ) { if ( ! empty( $record[ $k ] ) ) { $tracking = (string) $record[ $k ]; break; } }
                        $logs = isset( $v['logs'] ) ? $v['logs'] : ( isset( $v['errors'] ) ? $v['errors'] : array( 'invalid_record' ) );
                        if ( $tracking ) {
                            $opt_key = 'merc_import_log_rejected_' . sanitize_key( $tracking );
                            update_option( $opt_key, $logs );
                            // schedule cleanup to avoid long-lived data
                            if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
                                if ( ! wp_next_scheduled( 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) ) ) {
                                    wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) );
                                }
                            }
                            error_log( '[MERC_IMPORT][REJECTED] Tracking ' . $tracking . ' rejected: ' . maybe_serialize( $logs ) );
                        } else {
                            error_log( '[MERC_IMPORT][REJECTED] Registro sin tracking rechazado: ' . maybe_serialize( $logs ) );
                        }
                        $failed++;
                        continue;
                    }
                }
                // Crear post básico - usar título vacío/placeholder si no viene tracking
                $title = isset( $record['wpcargo_tracking_number'] ) && strlen( trim( $record['wpcargo_tracking_number'] ) ) ? $record['wpcargo_tracking_number'] : 'SIN TÍTULO';
                $post_id = wp_insert_post( [
                    'post_title'  => $title,
                    'post_type'   => 'wpcargo_shipment',
                    'post_status' => 'publish',
                ], true );

                if ( is_wp_error( $post_id ) ) {
                    $failed++;
                    throw new Exception( 'Error insertando envío: ' . $post_id->get_error_message() );
                }

                // Guardar metadatos desde el record (mapeo simple)
                foreach ( $record as $k => $v ) {
                    // Normalizar clave para meta (sin espacios)
                    $meta_key = sanitize_key( str_replace( ' ', '_', strtolower( $k ) ) );
                    update_post_meta( $post_id, $meta_key, $v );
                }

                // Ejecutar hooks de post-import existentes para validar/normalizar
                do_action( 'wpcie_after_save_csv_import', $post_id, $record );
                $processed++;
            }

            $wpdb->query( 'COMMIT' );
            return [ $processed, $failed ];
        } catch ( Exception $e ) {
            $wpdb->query( 'ROLLBACK' );
            error_log( '[MERC_IMPORT][JOB_' . $job_id . '] Chunk failed: ' . $e->getMessage() );
            // marcar job como failed y rethrow
            throw $e;
        }
    }
}

WP_CLI::add_command( 'merc-import', 'MERC_Import_Worker_Command' );
