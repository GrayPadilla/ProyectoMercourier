<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class MERC_Import_Guard {

    const ROW_LIMIT = 1000;

    public function __construct() {
        if ( is_admin() ) {
            add_action( 'admin_init', array( $this, 'check_upload_limit' ), 1 );
        }
    }

    public function check_upload_limit() {
        if ( empty( $_POST ) || ! isset( $_POST['action'] ) ) {
            return;
        }

        if ( 'wpcie_import_file' !== sanitize_text_field( wp_unslash( $_POST['action'] ) ) ) {
            return;
        }

        if ( empty( $_FILES['uploadedfile']['tmp_name'] ) ) {
            return;
        }

        $tmp = $_FILES['uploadedfile']['tmp_name'];
        if ( ! is_readable( $tmp ) ) {
            return;
        }

        $handle = fopen( $tmp, 'r' );
        if ( ! $handle ) {
            return;
        }

        $rows = 0;
        while ( ( $data = fgetcsv( $handle ) ) !== false ) {
            // skip empty rows (all empty cells)
            $nonEmpty = false;
            foreach ( $data as $cell ) {
                if ( is_string( $cell ) && strlen( trim( $cell ) ) > 0 ) {
                    $nonEmpty = true;
                    break;
                }
            }
            if ( ! $nonEmpty ) {
                continue;
            }
            $rows++;
            if ( $rows > self::ROW_LIMIT ) {
                break;
            }
        }
        fclose( $handle );

        if ( $rows > self::ROW_LIMIT ) {
            wp_die( 'El archivo CSV excede el límite de 1000 filas. Divide el archivo en partes más pequeñas e inténtalo de nuevo.', 'Límite de filas excedido', array( 'response' => 400 ) );
            exit;
        }

        // Si pasa el límite, encolar el archivo para procesamiento en background
        // Mover archivo a uploads/merc-imports
        $upload = wp_upload_dir();
        $dest_dir = trailingslashit( $upload['basedir'] ) . 'merc-imports';
        if ( ! file_exists( $dest_dir ) ) {
            wp_mkdir_p( $dest_dir );
        }

        $original_name = isset( $_FILES['uploadedfile']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['uploadedfile']['name'] ) ) : 'import.csv';
        $unique = time() . '-' . wp_generate_password( 8, false, false );
        $dest_name = $unique . '-' . $original_name;
        $dest_path = trailingslashit( $dest_dir ) . $dest_name;

        if ( ! move_uploaded_file( $tmp, $dest_path ) ) {
            error_log( '[MERC_IMPORT] Error moviendo archivo subido a destino: ' . $tmp );
            wp_die( 'Error al recibir el archivo. Inténtalo de nuevo.', 'Error', array( 'response' => 500 ) );
        }

        // Procesamiento síncrono: procesar el CSV ahora (no encolamos jobs)
        $user_id = get_current_user_id() ?: 0;
        try {
            $handle = fopen( $dest_path, 'r' );
            if ( ! $handle ) {
                throw new Exception( 'No se pudo abrir el archivo para procesar: ' . $dest_path );
            }

            // Leer cabecera
            $headers = [];
            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
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

            // token para esta importación (usar $unique generado arriba)
            $import_token = sanitize_key( $unique );
            $rejected_summary = array();
            $processed = 0;
            $row_number = 0;
            while ( ( $row = fgetcsv( $handle ) ) !== false ) {
                $nonEmpty = false;
                foreach ( $row as $c ) { if ( is_string( $c ) && strlen( trim( $c ) ) > 0 ) { $nonEmpty = true; break; } }
                if ( ! $nonEmpty ) continue;

                $record = [];
                foreach ( $headers as $i => $h ) {
                    $key_raw = is_string( $h ) ? trim( $h ) : "col_{$i}";
                    // Detectar identificador canónico entre paréntesis, p.e. "Fecha (wpcargo_pickup_date_picker)"
                    $key = $key_raw;
                    if ( is_string( $key_raw ) && preg_match('/\(([^)]+)\)/', $key_raw, $m) ) {
                        // usar el identificador dentro de paréntesis como clave (más fiable)
                        $key_candidate = trim( $m[1] );
                        if ( $key_candidate !== '' ) {
                            $key = $key_candidate;
                        }
                    }
                    $record[ $key ] = isset( $row[ $i ] ) ? $row[ $i ] : '';
                }

                $row_number++;
                // Primero: delegar la validación al preprocesador si existe (usa su lógica completa)
                if ( class_exists( 'MERC_CSV_Preprocessor' ) ) {
                    $v = MERC_CSV_Preprocessor::validate_record_array( $record );
                    error_log( '[MERC_IMPORT][VALIDATION] pre-insert validate_record_array: ' . maybe_serialize( $v ) );
                    if ( isset( $v['valid'] ) && $v['valid'] === false ) {
                        $tracking = '';
                        $possible = array( 'wpcargo_tracking_number', 'tracking', 'wpcargo_shipment_number', 'post_title', 'shipment_number' );
                        foreach ( $possible as $k ) { if ( ! empty( $record[ $k ] ) ) { $tracking = (string) $record[ $k ]; break; } }
                        $logs = isset( $v['logs'] ) ? $v['logs'] : ( isset( $v['errors'] ) ? $v['errors'] : array( 'invalid_record' ) );
                        if ( $tracking ) {
                            $opt_key = 'merc_import_log_rejected_' . sanitize_key( $tracking );
                            update_option( $opt_key, $logs );
                            if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
                                if ( ! wp_next_scheduled( 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) ) ) {
                                    wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) );
                                }
                            }
                            error_log( '[MERC_IMPORT][REJECTED] Tracking ' . $tracking . ' rejected (pre-insert): ' . maybe_serialize( $logs ) );
                        } else {
                            $opt_key = 'merc_import_log_rejected_' . $import_token . '_row_' . $row_number;
                            $payload = array( 'row' => $row_number, 'logs' => $logs, 'record' => $record, 'ts' => time() );
                            update_option( $opt_key, $payload );
                            $rejected_summary[] = array( 'row' => $row_number, 'opt' => $opt_key, 'logs' => $logs );
                            error_log( '[MERC_IMPORT][REJECTED] Registro sin tracking rechazado (pre-insert) row=' . $row_number . ' opt=' . $opt_key . ': ' . maybe_serialize( $logs ) );
                        }
                        continue;
                    }
                }

                // Fallback: si no hay preprocessor o validación no detectó, aplicar comprobación de fecha rápida
                $pre_reject_logs = array();
                foreach ( $record as $rk => $rv ) {
                    if ( ! is_string( $rv ) || trim( $rv ) === '' ) continue;
                    $vtrim = trim( $rv );
                    $looks_like_date = preg_match('/\d{1,2}[\/\-\s]\d{1,2}[\/\-\s]\d{2,4}/', $vtrim) || preg_match('/\d{4}[\/\-]\d{2}[\/\-]\d{2}/', $vtrim);
                    $name_looks_like = is_string( $rk ) && preg_match('/fecha|envio|envío|pickup|pick|ship|calendar|fecha_envio|pickup_date/i', $rk);
                    if ( $looks_like_date || $name_looks_like ) {
                        $dt = false;
                        // Fallback rápido: strtotime
                        $ts = strtotime( $vtrim );
                        if ( $ts !== false && $ts > 0 ) {
                            $dt = new DateTime();
                            $dt->setTimestamp( $ts );
                            $dt->setTime(0,0,0);
                        }
                        if ( $dt instanceof DateTime ) {
                            $today = new DateTime( 'today' );
                            if ( $dt < $today ) {
                                $pre_reject_logs[] = 'shipping_date_before_today';
                                $pre_reject_logs[] = 'Fecha de envío anterior a la fecha actual: ' . $vtrim;
                            }
                        } else {
                            $pre_reject_logs[] = 'invalid_shipping_date';
                            $pre_reject_logs[] = 'Fecha de envío inválida/no parseable: ' . $vtrim;
                        }
                        break;
                    }
                }
                if ( ! empty( $pre_reject_logs ) ) {
                    $tracking = '';
                    $possible = array( 'wpcargo_tracking_number', 'tracking', 'wpcargo_shipment_number', 'post_title', 'shipment_number' );
                    foreach ( $possible as $k ) { if ( ! empty( $record[ $k ] ) ) { $tracking = (string) $record[ $k ]; break; } }
                    $logs = $pre_reject_logs;
                    if ( $tracking ) {
                        $opt_key = 'merc_import_log_rejected_' . sanitize_key( $tracking );
                        update_option( $opt_key, $logs );
                        if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
                            if ( ! wp_next_scheduled( 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) ) ) {
                                wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) );
                            }
                        }
                        error_log( '[MERC_IMPORT][REJECTED] Tracking ' . $tracking . ' rejected (pre-insert date-check): ' . maybe_serialize( $logs ) );
                    } else {
                        $opt_key = 'merc_import_log_rejected_' . $import_token . '_row_' . $row_number;
                        $payload = array( 'row' => $row_number, 'logs' => $logs, 'record' => $record, 'ts' => time() );
                        update_option( $opt_key, $payload );
                        $rejected_summary[] = array( 'row' => $row_number, 'opt' => $opt_key, 'logs' => $logs );
                        error_log( '[MERC_IMPORT][REJECTED] Registro sin tracking rechazado (pre-insert date-check) row=' . $row_number . ' opt=' . $opt_key . ': ' . maybe_serialize( $logs ) );
                    }
                    continue;
                }

                // Crear post
                $title = isset( $record['wpcargo_tracking_number'] ) && strlen( trim( $record['wpcargo_tracking_number'] ) ) ? $record['wpcargo_tracking_number'] : 'SIN TÍTULO';
                $post_id = wp_insert_post( [
                    'post_title'  => $title,
                    'post_type'   => 'wpcargo_shipment',
                    'post_status' => 'publish',
                ], true );

                if ( is_wp_error( $post_id ) ) {
                    error_log( '[MERC_IMPORT] Error insertando envío: ' . $post_id->get_error_message() );
                    continue;
                }

                // Guardar metadatos
                foreach ( $record as $k => $v ) {
                    $meta_key = sanitize_key( str_replace( ' ', '_', strtolower( $k ) ) );
                    update_post_meta( $post_id, $meta_key, $v );
                }

                // Llamar hooks de procesado post-import (normalizadores, validadores, preprocessor, etc.)
                do_action( 'wpcie_after_save_csv_import', $post_id, $record );
                $processed++;
            }

            // Persistir resumen de rechazos para esta importación
            if ( ! empty( $rejected_summary ) ) {
                if ( function_exists( 'set_transient' ) ) {
                    set_transient( 'merc_import_rejected_summary_' . $import_token, $rejected_summary, HOUR_IN_SECONDS );
                }
                update_option( 'merc_import_rejected_summary_' . $import_token, $rejected_summary );
            }

            fclose( $handle );
            // Borrar archivo temporal
            @unlink( $dest_path );

            // Redirigir indicando éxito síncrono
            $redirect = add_query_arg( 'merc_import_processed', intval( $processed ), wp_get_referer() );
            // Añadir token para consultar resumen de rechazos sin tracking
            $redirect = add_query_arg( 'merc_import_token', $import_token, $redirect );
            wp_redirect( $redirect );
            exit;

        } catch ( Exception $e ) {
            error_log( '[MERC_IMPORT] Procesamiento síncrono falló: ' . $e->getMessage() );
            @unlink( $dest_path );
            wp_die( 'Error procesando el archivo: ' . esc_html( $e->getMessage() ), 'Error', array( 'response' => 500 ) );
        }
    }
}

new MERC_Import_Guard();

