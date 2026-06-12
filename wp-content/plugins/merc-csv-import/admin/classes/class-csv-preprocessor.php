<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Minimal MERC_CSV_Preprocessor fallback implementation.
 * Provides `validate_record_array()` and `preprocess_record()` so the plugin
 * won't fatal if the original file was removed. This is intentionally small
 * and conservative; extend with full validation logic as needed.
 */
class MERC_CSV_Preprocessor {
    /**
     * Validate a CSV row represented as an associative array.
     * Returns an array with keys: valid(bool), errors(array), canonical(array), raw_clean(array), logs(array)
     */
    public static function validate_record_array( array $record ) : array {
        // Limpieza de cabeceras: trim() en claves y valores
        $raw = [];
        foreach ( $record as $k => $v ) {
            $nk = is_string( $k ) ? trim( $k ) : $k;
            // Evitar colisiones: si ya existe la clave normalizada, mantener la primera
            if ( is_string( $nk ) && array_key_exists( $nk, $raw ) ) continue;
            $raw[ $nk ] = is_string( $v ) ? trim( $v ) : $v;
        }

        $errors = [];
        // Tracking is optional for CSV imports; do NOT add any error or log
        // about its absence. We still keep the list of possible tracking-like
        // keys for canonical mapping below.
        $tracking_keys = array( 'tracking', 'wpcargo_tracking_number', 'wpcargo_shipment_number', 'post_title', 'shipment_number' );

        // Build a minimal canonical mapping
        $canonical = array();
        foreach ( $tracking_keys as $k ) {
            if ( ! empty( $raw[ $k ] ) ) { $canonical['post_title'] = $raw[ $k ]; break; }
        }

        // Campos obligatorios básicos
        $required_name_keys = [ 'wpcargo_receiver_name', 'name', 'receiver_name', 'nombre' ];
        $required_addr_keys = [ 'wpcargo_receiver_address', 'address', 'direccion' ];
        $required_district_keys = [ 'wpcargo_distrito_destino', 'distrito_destino', 'destination_district', 'district', 'distrito', 'wpcargo_distrito' ];
        $required_phone_keys = [ 'wpcargo_receiver_phone', 'receiver_phone', 'phone', 'celular', 'telefono' ];

        $found_name = '';
        foreach ( $required_name_keys as $k ) { if ( ! empty( $raw[ $k ] ) ) { $found_name = $raw[ $k ]; break; } }
        if ( empty( $found_name ) ) $errors[] = 'missing_name';

        $found_addr = '';
        foreach ( $required_addr_keys as $k ) { if ( ! empty( $raw[ $k ] ) ) { $found_addr = $raw[ $k ]; break; } }
        if ( empty( $found_addr ) ) $errors[] = 'missing_address';

        $found_distr = '';
        foreach ( $required_district_keys as $k ) { if ( ! empty( $raw[ $k ] ) ) { $found_distr = $raw[ $k ]; break; } }
        if ( empty( $found_distr ) ) $errors[] = 'missing_district';

        $found_phone = '';
        foreach ( $required_phone_keys as $k ) { if ( ! empty( $raw[ $k ] ) ) { $found_phone = $raw[ $k ]; break; } }
        if ( empty( $found_phone ) ) $errors[] = 'missing_phone';

        $logs = array();

        // Validación: celular Perú (9 dígitos y empieza con 9)
        if ( ! empty( $found_phone ) ) {
            $digits = preg_replace('/\D+/', '', $found_phone);
            if ( strlen( $digits ) !== 9 || $digits[0] !== '9' ) {
                $errors[] = 'invalid_phone';
                $logs[] = 'Teléfono inválido: ' . $found_phone;
            } else {
                // guardar versión limpia en canonical
                $canonical['receiver_phone_clean'] = $digits;
            }
        }

        // Validación: distrito — usar siempre las opciones expuestas por WPCFE
        if ( ! empty( $found_distr ) ) {
            $norm_input = self::normalize_text( $found_distr );
            $allowed_norm = array();

            // Obtener opciones del filtro wpcfe_billing_address_fields
            $billing_fields = apply_filters( 'wpcfe_billing_address_fields', array() );
            if ( is_array( $billing_fields ) && isset( $billing_fields['distrito'] ) && isset( $billing_fields['distrito']['options'] ) && is_array( $billing_fields['distrito']['options'] ) ) {
                foreach ( $billing_fields['distrito']['options'] as $opt ) {
                    $dn = self::normalize_text( $opt );
                    if ( $dn ) $allowed_norm[ $dn ] = $opt;
                }
            }

            // Si no hay lista de referencia, aceptamos y guardamos tal cual (no bloquear)
            if ( empty( $allowed_norm ) ) {
                $canonical['wpcargo_distrito_destino'] = $found_distr;
            } else {
                if ( isset( $allowed_norm[ $norm_input ] ) ) {
                    $canonical['wpcargo_distrito_destino'] = $allowed_norm[ $norm_input ];
                } else {
                    // No hay coincidencia exacta: marcar error pero intentar heurísticas de "aprox"
                    $errors[] = 'invalid_district';
                    $logs[] = 'Distrito no reconocido: ' . $found_distr;

                    // Preparar tokens y candidates
                    $tokens = array_values(array_filter(explode(' ', $norm_input), function($t){ return $t !== ''; }));
                    $candidates = array();
                    foreach ( $allowed_norm as $k => $orig ) {
                        $matchCount = 0;
                        foreach ( $tokens as $t ) {
                            if ( $t !== '' && strpos( $k, $t ) !== false ) $matchCount++;
                        }
                        $tokenScore = count( $tokens ) ? ($matchCount / count( $tokens )) : 0;
                        $lev = levenshtein( $norm_input, $k );
                        $len = max(1, strlen( $norm_input ));
                        $levScore = 1 - ( $lev / $len );
                        $combined = $tokenScore * 0.6 + $levScore * 0.4; // ponderar tokens más
                        $candidates[] = array(
                            'key' => $k,
                            'orig' => $orig,
                            'tokenScore' => $tokenScore,
                            'lev' => $lev,
                            'levScore' => $levScore,
                            'combined' => $combined,
                        );
                    }

                    // Ordenar candidatos por score descendente
                    usort( $candidates, function( $a, $b ) { return $b['combined'] <=> $a['combined']; } );

                    // Si el mejor candidato pasa un umbral, aceptarlo automáticamente
                    if ( ! empty( $candidates ) && $candidates[0]['combined'] >= 0.60 ) {
                        $canonical['wpcargo_distrito_destino'] = $candidates[0]['orig'];
                        $logs[] = 'Aceptado por coincidencia aproximada: ' . $candidates[0]['orig'];
                        // remover el error invalid_district
                        $errors = array_values( array_diff( $errors, array( 'invalid_district' ) ) );
                    } else {
                        // Añadir hasta 3 sugerencias al log para facilitar diagnóstico
                        $top = array_slice( $candidates, 0, 3 );
                        foreach ( $top as $c ) {
                            $logs[] = sprintf( 'Sugerencia: %s (score=%.2f,lev=%d)', $c['orig'], $c['combined'], $c['lev'] );
                        }
                        // También registrar debug reducido para inspección (no muy verboso)
                        error_log( sprintf( "🔎 [MERC_CSV_PRE] Distrito mismatch: input='%s' norm='%s' top_suggestion='%s'", $found_distr, $norm_input, !empty($top) ? $top[0]['orig'] : '' ) );
                    }
                }
            }
        }

        // Validación: link_maps URL
        if ( ! empty( $raw['link_maps'] ) ) {
            $lm = $raw['link_maps'];
            if ( stripos( $lm, 'http://' ) !== 0 && stripos( $lm, 'https://' ) !== 0 ) {
                $errors[] = 'link_maps_invalid';
                $logs[] = 'Link Google Maps inválido: ' . $lm;
            }
        }

        // Validación: tipo de envío (solo permitir valores conocidos)
        $service_keys = [ 'wpcargo_service_id', 'tipo_envio', 'service_type', 'service' ];
        $service_val = '';
        foreach ( $service_keys as $k ) { if ( isset( $raw[ $k ] ) && $raw[ $k ] !== '' ) { $service_val = $raw[ $k ]; break; } }
        if ( $service_val !== '' ) {
            $sv = strtolower( trim( (string) $service_val ) );
            $allowed = [ 'agencia', 'emprendedor', 'normal', 'express' ];
            $matched = false;
            foreach ( $allowed as $a ) { if ( stripos( $sv, $a ) !== false ) { $matched = true; break; } }
            if ( ! $matched ) {
                $errors[] = 'invalid_service_type';
                $logs[] = 'Tipo de envío inválido: ' . $service_val;
            }
        }

        // Validación: modo de pago y monto
        $payment_keys = [ 'modo_pago','payment_mode','modo','formapago','payment','wpcargo_payment_mode' ];
        $amount_keys  = [ 'monto','amount','wpcargo_monto','total','wpcargo_total_cobrar','price' ];
        $mode = '';
        foreach ( $payment_keys as $k ) { if ( isset( $raw[ $k ] ) && $raw[ $k ] !== '' ) { $mode = $raw[ $k ]; break; } }
        $amount = null;
        foreach ( $amount_keys as $k ) { if ( isset( $raw[ $k ] ) && $raw[ $k ] !== '' ) { $amount = floatval( preg_replace('/[^0-9\.\-]/','', (string) $raw[ $k ] ) ); break; } }
        if ( $mode !== '' ) {
            $mup = strtoupper( trim( (string) $mode ) );
            if ( $mup === 'NO COBRAR' || $mup === 'NOCOBRAR' || $mup === 'NO-COBRAR' ) {
                if ( $amount !== null && floatval( $amount ) !== 0.0 ) {
                    $errors[] = 'mode_amount_mismatch';
                    $logs[] = 'Modo NO COBRAR pero monto distinto de 0: ' . $amount;
                }
            } elseif ( in_array( $mup, [ 'EFECTIVO','YAPE','PLIN','POS' ], true ) ) {
                if ( $amount === null || floatval( $amount ) <= 0 ) {
                    $errors[] = 'missing_amount_for_payment_mode';
                    $logs[] = 'Modo de pago ' . $mode . ' requiere monto > 0';
                }
            }
        }

        // Validación: Fecha pickup — aceptar variantes como d/m/Y o dd/mm/YYYY
        if ( ! empty( $raw['wpcargo_pickup_date_picker'] ) ) {
            $dstr = $raw['wpcargo_pickup_date_picker'];
            // Usar el parser flexible para soportar días/meses de 1 o 2 dígitos
            $dt = self::parse_possibly_formatted_date( $dstr );
            if ( ! $dt ) {
                $errors[] = 'invalid_pickup_date_format';
                $logs[] = 'Formato de fecha de envío inválido (esperado DD/MM/YYYY o variantes): ' . $dstr;
            } else {
                $today = new \DateTime('today');
                $dt->setTime(0,0,0);
                if ( $dt < $today ) {
                    $errors[] = 'shipping_date_before_today';
                    $logs[] = 'Fecha de envío anterior a hoy: ' . $dstr;
                } else {
                    // Guardar versión canónica normalizada
                    $canonical['wpcargo_pickup_date_picker'] = $dt->format('d/m/Y');
                }
            }
        }

        // Validación: detectar cualquier campo que parezca fecha (por nombre o por valor)
        // y marcar error si la fecha es anterior a hoy o no es parseable.
        $found_date_field = false;
        foreach ( $raw as $rk => $rv ) {
            if ( empty( $rv ) ) continue;
            $rk_l = is_string( $rk ) ? strtolower( $rk ) : '';
            // Si el nombre de la columna sugiere que es una fecha/envío
            $name_looks_like_date = preg_match('/fecha|envio|envío|pickup|pick|ship|calendar|fecha_envio|pickup_date/i', $rk_l);
            // O si el valor tiene un patrón de fecha (ej. DD/MM/YYYY o YYYY-MM-DD)
            $value_looks_like_date = is_string( $rv ) && ( preg_match('/\d{1,2}[\/\-\s]\d{1,2}[\/\-\s]\d{2,4}/', $rv) || preg_match('/\d{4}[\/\-]\d{2}[\/\-]\d{2}/', $rv) );
            if ( $name_looks_like_date || $value_looks_like_date ) {
                $found_date_field = true;
                $d = self::parse_possibly_formatted_date( $rv );
                if ( $d instanceof \DateTime ) {
                    // Normalize and store canonical date with zero-padded day/month
                    $canonical['wpcargo_pickup_date_picker'] = $d->format('d/m/Y');
                    $today = new \DateTime( 'today' );
                    if ( $d < $today ) {
                        $errors[] = 'shipping_date_before_today';
                        $logs[] = 'Fecha de envío anterior a la fecha actual: ' . $d->format('d/m/Y');
                    }
                } else {
                    $errors[] = 'invalid_shipping_date';
                    $logs[] = 'Fecha de envío inválida/no parseable: ' . $rv;
                }
                // no seguir comprobando otras claves
                break;
            }
        }

        // Recalcular validez si añadimos errores
        $valid = empty( $errors );
        if ( ! $valid && empty( $logs ) ) {
            $logs[] = 'Registro inválido: ' . implode( ', ', $errors );
        }

        return array(
            'valid' => $valid,
            'errors' => $errors,
            'canonical' => $canonical,
            'raw_clean' => $raw,
            'logs' => $logs,
        );
    }

    /**
     * Intenta parsear una fecha en varios formatos y devuelve DateTime o false
     * Soporta: d/m/Y, j/n/Y, Y-m-d, Y/m/d, d-m-Y, j-n-Y
     */
    private static function parse_possibly_formatted_date( $date_str ) {
        if ( empty( $date_str ) ) {
            return false;
        }
        $date_str = trim( $date_str );
        $formats = [ 'j/n/Y', 'd/m/Y', 'Y-m-d', 'Y/m/d', 'd-m-Y', 'j-n-Y' ];
        foreach ( $formats as $fmt ) {
            $dt = \DateTime::createFromFormat( $fmt, $date_str );
            if ( $dt ) {
                // Normalizar a mitad del día (sin hora)
                $dt->setTime(0,0,0);
                return $dt;
            }
        }
        // Intentar strtotime como último recurso
        $ts = strtotime( $date_str );
        if ( $ts !== false && $ts > 0 ) {
            $dt = new \DateTime();
            $dt->setTimestamp( $ts );
            $dt->setTime(0,0,0);
            return $dt;
        }
        return false;
    }

    /**
     * Normaliza texto para comparaciones: pasar a minúsculas, quitar tildes/diacríticos,
     * eliminar caracteres no alfanuméricos salvo espacios y colapsar espacios.
     */
    private static function normalize_text( $s ) {
        if ( empty( $s ) ) return '';
        $s = (string) $s;
        // Normalizar unicode y transliterar a ASCII cuando sea posible
        if ( function_exists( 'transliterator_transliterate' ) ) {
            $s = transliterator_transliterate( 'NFD; [:Nonspacing Mark:] Remove; NFC; Latin-ASCII;', $s );
        } else {
            // fallback básico: iconv
            $t = @iconv( 'UTF-8', 'ASCII//TRANSLIT', $s );
            if ( $t !== false ) $s = $t;
        }
        $s = strtolower( $s );
        // quitar todo excepto letras, números y espacios
        $s = preg_replace('/[^a-z0-9\s]/', ' ', $s);
        // colapsar espacios
        $s = preg_replace('/\s+/', ' ', $s);
        return trim( $s );
    }

    /**
     * Called after a shipment post is created. Store processing logs for later lookup.
     */
    public static function preprocess_record( int $shipment_id, array $record ) {
        error_log( "[MERC_CSV_PREPROCESSOR] preprocess_record called for shipment_id: {$shipment_id}" );

        // Run validation to produce canonical data / logs
        $result = self::validate_record_array( $record );

        $logs = ! empty( $result['logs'] ) ? $result['logs'] : array();

        // Always persist a transient/option log for the shipment id so UI can query by id
        if ( function_exists( 'set_transient' ) ) {
            set_transient( 'merc_import_log_' . $shipment_id, $logs, HOUR_IN_SECONDS );
        }
        update_option( 'merc_import_log_' . $shipment_id, $logs );

        // DEBUG: volcar contenido completo del resultado justo antes de persistir
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            $dbg = array(
                'shipment_id' => $shipment_id,
                'errors' => isset($result['errors']) ? $result['errors'] : array(),
                'logs' => isset($result['logs']) ? $result['logs'] : array(),
                'canonical' => isset($result['canonical']) ? $result['canonical'] : array(),
            );
            error_log( "[MERC_CSV_PRE_DEBUG] " . wp_json_encode( $dbg ) );
        }

        // If validation failed, persist a rejected-by-tracking mapping so the UI
        // can request the reason even if the post is deleted later. Do not delete
        // the post here (import guard handles deletion); only store the rejected log.
        $tracking = '';
        $possible = array( 'post_title', 'wpcargo_tracking_number', 'tracking', 'wpcargo_shipment_number', 'shipment_number' );
        foreach ( $possible as $k ) {
            if ( ! empty( $record[ $k ] ) ) { $tracking = (string) $record[ $k ]; break; }
        }

        if ( ! $result['valid'] ) {
            // Save rejection mapping keyed by tracking (if available)
            if ( $tracking ) {
                self::persist_rejected_by_tracking( $tracking, $result );
                $opt_key_info = 'tracking:' . sanitize_key( $tracking );
            } else {
                // Fallback: persist by import token + row when tracking is not available
                $token = '';
                $rownum = '';
                $token_keys = array( 'merc_import_token', 'import_token', 'merc_import_token' );
                foreach ( $token_keys as $k ) {
                    if ( isset( $record[ $k ] ) && $record[ $k ] !== '' ) { $token = (string) $record[ $k ]; break; }
                }
                $row_keys = array( 'merc_import_row', 'row', 'import_row', 'csv_row' );
                foreach ( $row_keys as $k ) {
                    if ( isset( $record[ $k ] ) && $record[ $k ] !== '' ) { $rownum = (string) $record[ $k ]; break; }
                }

                if ( $token && $rownum !== '' ) {
                    $opt_key = 'merc_import_log_rejected_' . sanitize_key( $token ) . '_row_' . intval( $rownum );
                    $payload = array(
                        'errors' => isset( $result['errors'] ) ? $result['errors'] : array(),
                        'logs' => isset( $result['logs'] ) ? $result['logs'] : array(),
                        'canonical' => isset( $result['canonical'] ) ? $result['canonical'] : array(),
                        'ts' => time(),
                    );
                    update_option( $opt_key, $payload );
                    // schedule cleanup for this option key
                    if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
                        if ( ! wp_next_scheduled( 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) ) ) {
                            wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) );
                        }
                    }
                    $opt_key_info = $opt_key;
                } elseif ( $token ) {
                    // If only token present, append to a summary option
                    $summary_key = 'merc_import_rejected_summary_' . sanitize_key( $token );
                    $summary = get_option( $summary_key, array() );
                    $summary[] = array(
                        'errors' => isset( $result['errors'] ) ? $result['errors'] : array(),
                        'logs' => isset( $result['logs'] ) ? $result['logs'] : array(),
                        'canonical' => isset( $result['canonical'] ) ? $result['canonical'] : array(),
                        'ts' => time(),
                    );
                    update_option( $summary_key, $summary );
                    $opt_key_info = $summary_key;
                }
            }

            // Also persist per-shipment id log for immediate lookup
            update_post_meta( $shipment_id, 'merc_import_skipped', implode( ',', $result['errors'] ) );

            // Keep transient/option as already set above
            $reason = ! empty( $result['logs'] ) ? implode( ' | ', $result['logs'] ) : implode( ', ', $result['errors'] );
            $log_extra = isset( $opt_key_info ) ? " — stored_as: {$opt_key_info}" : '';
            error_log( "⚠️ [MERC_CSV_PRE] Errores en fila (shipment #{$shipment_id}): " . implode( ',', $result['errors'] ) . " — Motivo: " . $reason . $log_extra );

            // Delete immediately so later hooks cannot re-publish the post. Logs/reasons
            // have been persisted by tracking or token/row above for UI retrieval.
            if ( get_post_status( $shipment_id ) ) {
                wp_delete_post( $shipment_id, true );
            }

            return;
        }

        // If valid, persist canonical headers and merge canonical values into post meta
        if ( ! empty( $result['canonical'] ) ) {
            update_post_meta( $shipment_id, 'merc_import_canonical_headers', $result['canonical'] );
            foreach ( $result['canonical'] as $meta_key => $value ) {
                // Normalize date-like canonical values to d/m/Y to ensure consistent storage
                $stored_value = $value;
                $is_date_key = preg_match('/date|pickup|fecha|envio/i', $meta_key);
                if ( $is_date_key && ! empty( $value ) ) {
                    $dt = self::parse_possibly_formatted_date( (string) $value );
                    if ( $dt instanceof \DateTime ) {
                        $stored_value = $dt->format('d/m/Y');
                    } else {
                        // fallback: try to normalize simple patterns like '6/4/2026' -> '06/04/2026'
                        if ( is_string( $value ) && preg_match('/^(\d{1,2})\s*[\/\-\s]\s*(\d{1,2})\s*[\/\-\s]\s*(\d{2,4})$/', trim( $value ), $m) ) {
                            $dd = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
                            $mm = str_pad( $m[2], 2, '0', STR_PAD_LEFT );
                            $yy = $m[3];
                            if ( strlen( $yy ) === 2 ) $yy = '20' . $yy;
                            $stored_value = sprintf('%s/%s/%s', $dd, $mm, $yy);
                        }
                    }
                }

                // Avoid overwriting registered_shipper unless admin
                if ( $meta_key === 'registered_shipper' ) {
                    if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
                        update_post_meta( $shipment_id, 'registered_shipper', sanitize_text_field( (string) $stored_value ) );
                        if ( ctype_digit( (string) $stored_value ) ) {
                            wp_update_post( [ 'ID' => $shipment_id, 'post_author' => (int) $stored_value ] );
                        }
                    } else {
                        update_post_meta( $shipment_id, 'merc_import_registered_shipper_blocked', sanitize_text_field( (string) $stored_value ) );
                        error_log( "⚠️ [MERC_CSV_PRE] registered_shipper bloqueado para shipment #{$shipment_id} (importador no admin)" );
                    }
                } else {
                    update_post_meta( $shipment_id, $meta_key, sanitize_text_field( (string) $stored_value ) );
                }
            }
        }

        // Persist mapping by tracking so UI can fetch logs by tracking even if post removed
        if ( $tracking ) {
            $opt_key = 'merc_import_log_tracking_' . sanitize_key( $tracking );
            update_option( $opt_key, $logs );
            if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
                if ( ! wp_next_scheduled( 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) ) ) {
                    wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'merc_csv_import_cleanup_tracking_log', array( $opt_key ) );
                }
            }
        }
    }

    /**
     * Encola el procesamiento para evitar ejecutar trabajo pesado durante la
     * petición HTTP (reduce riesgo de 504). Si WP-Cron no está disponible,
     * cae de vuelta a `preprocess_record` síncronamente.
     */
    public static function enqueue_preprocess_record( int $shipment_id, array $record ) {
        if ( function_exists( 'wp_schedule_single_event' ) ) {
            // Programar en unos segundos para salir rápido de la petición
            if ( ! wp_next_scheduled( 'merc_csv_import_process_queue', array( $shipment_id, $record ) ) ) {
                wp_schedule_single_event( time() + 5, 'merc_csv_import_process_queue', array( $shipment_id, $record ) );
            }
            return true;
        }
        // Fallback: procesar inmediatamente
        return self::preprocess_record( $shipment_id, $record );
    }

    /**
     * Persistar datos de rechazo indexados por tracking para que el endpoint AJAX
     * pueda devolver el motivo aun cuando el post sea eliminado.
     */
    public static function persist_rejected_by_tracking( $tracking, array $result ) {
        if ( empty( $tracking ) ) return false;
        $key = 'merc_import_log_rejected_' . sanitize_key( $tracking );
        $payload = array(
            'errors' => isset( $result['errors'] ) ? $result['errors'] : array(),
            'logs' => isset( $result['logs'] ) ? $result['logs'] : array(),
            'canonical' => isset( $result['canonical'] ) ? $result['canonical'] : array(),
            'ts' => time(),
        );
        update_option( $key, $payload );
        // schedule cleanup after a short TTL (e.g., 10 minutes)
        if ( function_exists( 'wp_next_scheduled' ) && function_exists( 'wp_schedule_single_event' ) ) {
            if ( ! wp_next_scheduled( 'merc_csv_import_cleanup_tracking_log', array( $key ) ) ) {
                wp_schedule_single_event( time() + 10 * MINUTE_IN_SECONDS, 'merc_csv_import_cleanup_tracking_log', array( $key ) );
            }
        }
        return true;
    }

    /**
     * Cleanup callback to remove tracking-based option mapping.
     */
    public static function cleanup_tracking_log( $opt_key ) {
        if ( empty( $opt_key ) ) return;
        delete_option( $opt_key );
    }

    /**
     * Schedule deletion of a shipment after a delay (seconds).
     * This allows `merc_get_discard_reason` to query logs before the post is removed.
     */
    public static function schedule_deletion( int $shipment_id, int $delay_seconds = 120 ) {
        if ( ! function_exists( 'wp_schedule_single_event' ) || ! function_exists( 'wp_next_scheduled' ) ) return false;
        $args = array( $shipment_id );
        if ( ! wp_next_scheduled( 'merc_csv_import_delayed_delete', $args ) ) {
            wp_schedule_single_event( time() + max( 10, (int) $delay_seconds ), 'merc_csv_import_delayed_delete', $args );
            return true;
        }
        return false;
    }

    /**
     * Delayed delete handler: remove the post and cleanup related logs/transients/options.
     */
    public static function delayed_delete_shipment( $shipment_id ) {
        $shipment_id = intval( $shipment_id );
        if ( $shipment_id <= 0 ) return;
        // remove post if still exists
        if ( get_post_status( $shipment_id ) ) {
            wp_delete_post( $shipment_id, true );
        }
        // cleanup transient/option
        if ( function_exists( 'delete_transient' ) ) delete_transient( 'merc_import_log_' . $shipment_id );
        delete_option( 'merc_import_log_' . $shipment_id );
    }

    /**
     * Ensure date-like post metas are normalized to d/m/Y after save.
     * This runs as a safety net in case other hooks overwrite canonical values.
     */
    public static function normalize_saved_dates( int $shipment_id, array $record = array() ) {
        $date_keys = array( 'wpcargo_pickup_date_picker', 'wpcargo_pickup_date', 'wpcargo_calendarenvio', 'wpcargo_fecha_envio' );
        foreach ( $date_keys as $dk ) {
            $val = get_post_meta( $shipment_id, $dk, true );
            if ( empty( $val ) ) continue;
            $dt = self::parse_possibly_formatted_date( (string) $val );
            if ( $dt instanceof \DateTime ) {
                $fmt = $dt->format('d/m/Y');
                if ( $fmt !== (string) $val ) update_post_meta( $shipment_id, $dk, $fmt );
                continue;
            }
            // fallback: normalize simple numeric patterns like 6/4/2026 -> 06/04/2026
            if ( is_string( $val ) && preg_match('/^(\d{1,2})\s*[\/\-\s]\s*(\d{1,2})\s*[\/\-\s]\s*(\d{2,4})$/', trim( $val ), $m ) ) {
                $dd = str_pad( $m[1], 2, '0', STR_PAD_LEFT );
                $mm = str_pad( $m[2], 2, '0', STR_PAD_LEFT );
                $yy = $m[3]; if ( strlen( $yy ) === 2 ) $yy = '20' . $yy;
                $fmt = sprintf('%s/%s/%s', $dd, $mm, $yy );
                if ( $fmt !== (string) $val ) update_post_meta( $shipment_id, $dk, $fmt );
            }
        }
    }
}

// Register scheduled action handlers so scheduled events call class methods.
        if ( function_exists( 'add_action' ) ) {
    if ( false === has_action( 'merc_csv_import_cleanup_tracking_log', array( 'MERC_CSV_Preprocessor', 'cleanup_tracking_log' ) ) ) {
        add_action( 'merc_csv_import_cleanup_tracking_log', array( 'MERC_CSV_Preprocessor', 'cleanup_tracking_log' ) );
    }
    if ( false === has_action( 'merc_csv_import_delayed_delete', array( 'MERC_CSV_Preprocessor', 'delayed_delete_shipment' ) ) ) {
        add_action( 'merc_csv_import_delayed_delete', array( 'MERC_CSV_Preprocessor', 'delayed_delete_shipment' ) );
    }
    // Run the preprocessor late so it can make a final decision (delete/draft)
    // after other handlers have run; use a high priority so it executes last.
    if ( false === has_action( 'wpcie_after_save_csv_import', array( 'MERC_CSV_Preprocessor', 'enqueue_preprocess_record' ) ) ) {
        add_action( 'wpcie_after_save_csv_import', array( 'MERC_CSV_Preprocessor', 'enqueue_preprocess_record' ), 999, 2 );
    }
    // Hook que ejecuta el procesamiento encolado (WP-Cron)
    if ( false === has_action( 'merc_csv_import_process_queue', array( 'MERC_CSV_Preprocessor', 'preprocess_record' ) ) ) {
        add_action( 'merc_csv_import_process_queue', array( 'MERC_CSV_Preprocessor', 'preprocess_record' ), 10, 2 );
    }
    // Safety net: normalize date metas right after save (high priority)
    if ( false === has_action( 'after_wpcfe_save_shipment', array( 'MERC_CSV_Preprocessor', 'normalize_saved_dates' ) ) ) {
        add_action( 'after_wpcfe_save_shipment', array( 'MERC_CSV_Preprocessor', 'normalize_saved_dates' ), 99999, 2 );
    }
    // ALSO hook into the CSV import completion action to ensure dates are
    // normalized immediately during imports. Some normalizers (theme/plugins)
    // may set a forced pickup date via user meta; register here with a high
    // priority so we run after most normalizers and re-normalize any date
    // metas before the preprocessor scheduled job runs.
    if ( false === has_action( 'wpcie_after_save_csv_import', array( 'MERC_CSV_Preprocessor', 'normalize_saved_dates' ) ) ) {
        add_action( 'wpcie_after_save_csv_import', array( 'MERC_CSV_Preprocessor', 'normalize_saved_dates' ), 1000, 2 );
    }
}

