<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Diagnostic: log admin-ajax requests to help trace 0/400 responses
add_action( 'admin_init', function() {
    if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
        $act = isset( $_REQUEST['action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ) : '(none)';
        $method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'unknown';
        error_log( sprintf( "[MERC_IMPORT_AJAX_CALL] action=%s method=%s REMOTE=%s", $act, $method, $_SERVER['REMOTE_ADDR'] ?? 'cli' ) );
        // Extra debug for specific problematic endpoint
        if ( $act === 'merc_get_discard_reason' ) {
            $ct = isset( $_SERVER['HTTP_CONTENT_TYPE'] ) ? $_SERVER['HTTP_CONTENT_TYPE'] : ( isset( $_SERVER['CONTENT_TYPE'] ) ? $_SERVER['CONTENT_TYPE'] : '' );
            error_log( "[MERC_IMPORT_AJAX_CALL_DEBUG] merc_get_discard_reason headers: Content-Type=" . $ct );
            error_log( "[MERC_IMPORT_AJAX_CALL_DEBUG] REQUEST_VARS: " . maybe_serialize( $_REQUEST ) );
            // read raw body for POSTs
            $raw = @file_get_contents( 'php://input' );
            if ( $raw ) error_log( "[MERC_IMPORT_AJAX_CALL_DEBUG] RAW_INPUT: " . substr( $raw, 0, 2000 ) );
        }
    }
} );

/**
 * Devuelve el motivo de descarte asociado a un número de tracking.
 * Busca en opciones/transients `merc_import_log_*` y devuelve el primer match.
 */
function merc_get_discard_reason_ajax() {
    $tracking = isset( $_REQUEST['tracking'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tracking'] ) ) : '';

    // Allow clients to query discard reason by tracking OR by receiver phone/name.
    // If none provided, return a generic error indicating insufficient input.
    $phone_in = isset( $_REQUEST['receiver_phone'] ) ? preg_replace('/\D+/', '', sanitize_text_field( wp_unslash( $_REQUEST['receiver_phone'] ) ) ) : '';
    $name_in  = isset( $_REQUEST['receiver_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['receiver_name'] ) ) : '';

        if ( empty( $tracking ) && empty( $phone_in ) && empty( $name_in ) ) {
            wp_send_json_error( 'missing_input' );
    }

    global $wpdb;
    $found = null;
    $found_option = null;

    // Fast path: mapping by tracking (created by preprocessor) to avoid race with deleted posts
    // 1) Rejected mapping (records rejected before creating post)
    $rej_key = 'merc_import_log_rejected_' . sanitize_key( $tracking );
    $rej_val = get_option( $rej_key );
    if ( $rej_val ) {
        $accept = true;
        if ( $phone_in && is_array( $rej_val ) ) {
            $ser = json_encode( $rej_val );
            if ( stripos( $ser, $phone_in ) === false ) $accept = false;
        }
        if ( $accept ) { $found = $rej_val; $found_option = $rej_key; }
    }

    // 2) Fast path: mapping by tracking (created by preprocessor) to avoid race with deleted posts
    if ( $found === null ) {
        $map_key = 'merc_import_log_tracking_' . sanitize_key( $tracking );
        $map_val = get_option( $map_key );
        if ( $map_val ) {
            $accept = true;
            if ( $phone_in && is_array( $map_val ) ) {
                $ser = json_encode( $map_val );
                if ( stripos( $ser, $phone_in ) === false ) $accept = false;
            }
            if ( $accept ) { $found = $map_val; $found_option = $map_key; }
        }
    }

    // Buscar opciones directas creadas por update_option
    $like = $wpdb->esc_like( 'merc_import_log_' ) . '%';
    $rows = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like ), ARRAY_A );
    if ( is_array( $rows ) ) {
        foreach ( $rows as $r ) {
            $val = maybe_unserialize( $r['option_value'] );
            $ser = is_array( $val ) ? json_encode( $val ) : (string) $val;
            $matched = false;
            if ( $tracking && stripos( $ser, $tracking ) !== false ) $matched = true;
            // prefer matches that include phone or name when provided
            if ( $phone_in && stripos( $ser, $phone_in ) !== false ) $matched = true;
            if ( $name_in && stripos( $ser, $name_in ) !== false ) $matched = true;
            if ( $matched ) {
                $found = $val;
                $found_option = $r['option_name'];
                break;
            }
        }
    }

    // Si no encontramos, revisar transients en options (prefijo _transient_merc_import_log_*)
    if ( $found === null ) {
        $like_t = $wpdb->esc_like( '_transient_merc_import_log_' ) . '%';
        $rows2 = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like_t ), ARRAY_A );
        if ( is_array( $rows2 ) ) {
            foreach ( $rows2 as $r ) {
                $val = maybe_unserialize( $r['option_value'] );
                $ser = is_array( $val ) ? json_encode( $val ) : (string) $val;
                $matched = false;
                if ( $tracking && stripos( $ser, $tracking ) !== false ) $matched = true;
                if ( $phone_in && stripos( $ser, $phone_in ) !== false ) $matched = true;
                if ( $name_in && stripos( $ser, $name_in ) !== false ) $matched = true;
                if ( $matched ) {
                    $found = $val;
                    $found_option = $r['option_name'];
                    break;
                }
            }
        }
    }

    // If still not found, try raw-key variants and log diagnostics for debugging
    if ( $found === null ) {
        // try raw (unsanitized) keys in case storage used raw tracking
        $rej_key_raw = 'merc_import_log_rejected_' . $tracking;
        $map_key_raw = 'merc_import_log_tracking_' . $tracking;
        $rej_raw_val = get_option( $rej_key_raw );
        $map_raw_val = get_option( $map_key_raw );
        if ( $rej_raw_val ) {
            $ser = is_array( $rej_raw_val ) ? json_encode( $rej_raw_val ) : (string) $rej_raw_val;
            if ( ! $phone_in || stripos( $ser, $phone_in ) !== false ) { $found = $rej_raw_val; $found_option = $rej_key_raw; }
        } elseif ( $map_raw_val ) {
            $ser = is_array( $map_raw_val ) ? json_encode( $map_raw_val ) : (string) $map_raw_val;
            if ( ! $phone_in || stripos( $ser, $phone_in ) !== false ) { $found = $map_raw_val; $found_option = $map_key_raw; }
        }

        // If still not found, write diagnostic info to error_log: how many candidate options exist
        if ( $found === null ) {
            global $wpdb;
            $like_all = $wpdb->esc_like( 'merc_import_log_' ) . '%';
            $rows_diag = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 50", $like_all ), ARRAY_A );
            $names = array();
            if ( is_array( $rows_diag ) ) {
                foreach ( $rows_diag as $r ) {
                    $names[] = $r['option_name'];
                }
            }
            error_log( sprintf( "[MERC_IMPORT_AJAX_DEBUG] tracking='%s' sanitized='%s' candidate_options=%d sample=%s", $tracking, sanitize_key( $tracking ), count( $names ), maybe_serialize( array_slice( $names, 0, 10 ) ) ) );
        }
    }

    // If still not found, try matching by provided receiver_phone or receiver_name (useful when tracking absent)
    if ( $found === null ) {
        $phone_in = isset( $_REQUEST['receiver_phone'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['receiver_phone'] ) ) : '';
        $name_in  = isset( $_REQUEST['receiver_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['receiver_name'] ) ) : '';
        if ( $phone_in || $name_in ) {
            $rows_all = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s LIMIT 500", $wpdb->esc_like( 'merc_import_log_' ) . '%' ), ARRAY_A );
            if ( is_array( $rows_all ) ) {
                foreach ( $rows_all as $r ) {
                    $val = maybe_unserialize( $r['option_value'] );
                    // If val is array with canonical sub-array, try to compare
                    if ( is_array( $val ) ) {
                        // canonical may contain receiver_phone_clean or receiver_phone
                        if ( isset( $val['canonical'] ) && is_array( $val['canonical'] ) ) {
                            $canon = $val['canonical'];
                            if ( $phone_in ) {
                                $digits = preg_replace('/\D+/', '', $phone_in);
                                if ( isset( $canon['receiver_phone_clean'] ) && $canon['receiver_phone_clean'] == $digits ) {
                                    $found = $val; $found_option = $r['option_name']; break;
                                }
                                if ( isset( $canon['receiver_phone'] ) && preg_replace('/\D+/', '', $canon['receiver_phone']) == $digits ) {
                                    $found = $val; $found_option = $r['option_name']; break;
                                }
                            }
                            if ( $name_in && isset( $canon['post_title'] ) ) {
                                if ( stripos( $canon['post_title'], $name_in ) !== false || stripos( $name_in, $canon['post_title'] ) !== false ) {
                                    $found = $val; $found_option = $r['option_name']; break;
                                }
                            }
                        }
                        // Otherwise, search logs/other fields for phone or name
                        if ( $phone_in && $found === null ) {
                            $digits = preg_replace('/\D+/', '', $phone_in);
                            $serialized = json_encode( $val );
                            if ( stripos( $serialized, $digits ) !== false ) { $found = $val; $found_option = $r['option_name']; break; }
                        }
                        if ( $name_in && $found === null ) {
                            $serialized = json_encode( $val );
                            if ( stripos( $serialized, $name_in ) !== false ) { $found = $val; $found_option = $r['option_name']; break; }
                        }
                    } else {
                        // value is string - check for phone/name substring
                        if ( $phone_in && stripos( (string) $r['option_value'], $phone_in ) !== false ) { $found = $r['option_value']; $found_option = $r['option_name']; break; }
                        if ( $name_in && stripos( (string) $r['option_value'], $name_in ) !== false ) { $found = $r['option_value']; $found_option = $r['option_name']; break; }
                    }
                }
            }
        }
    }

    // Final fallback: search option_value text for common rejection phrases and return first match
    if ( $found === null ) {
        $phrases = array(
            'Fecha de envío anterior',
            'Distrito no reconocido',
            'Fecha de envío inválida',
            'Teléfono inválido',
            'Falta número de tracking',
            'Registro inválido'
        );
        $likes = array_map( function($p) use ($wpdb) { return '%' . $wpdb->esc_like( $p ) . '%'; }, $phrases );
        // build SQL with ORs
        $where_parts = array();
        foreach ( $likes as $i => $l ) { $where_parts[] = "option_value LIKE %s"; }
        $sql = "SELECT option_name, option_value FROM {$wpdb->options} WHERE (" . implode(' OR ', $where_parts) . ") LIMIT 1";
        $params = $likes;
        $row_fallback = $wpdb->get_row( $wpdb->prepare( $sql, $params ), ARRAY_A );
        if ( $row_fallback ) {
            // Only accept fallback if it actually mentions the requested tracking, phone or name
            $candidate_ser = maybe_unserialize( $row_fallback['option_value'] );
            $candidate_text = is_array( $candidate_ser ) ? json_encode( $candidate_ser ) : (string) $candidate_ser;
            $accept_fallback = false;
            if ( $tracking && stripos( $candidate_text, $tracking ) !== false ) $accept_fallback = true;
            // allow phone match (digits) or name match
            if ( ! $accept_fallback && ! empty( $phone_in ) ) {
                $digits = preg_replace('/\D+/', '', $phone_in);
                if ( $digits && stripos( $candidate_text, $digits ) !== false ) $accept_fallback = true;
            }
            if ( ! $accept_fallback && ! empty( $name_in ) ) {
                if ( stripos( $candidate_text, $name_in ) !== false ) $accept_fallback = true;
            }
            if ( $accept_fallback ) {
                $found_option = $row_fallback['option_name'];
                $found = maybe_unserialize( $row_fallback['option_value'] );
                error_log( "[MERC_IMPORT_AJAX_DEBUG] fallback accepted option: " . $found_option );
            } else {
                error_log( "[MERC_IMPORT_AJAX_DEBUG] fallback rejected for tracking={$tracking} candidate=" . $row_fallback['option_name'] );
            }
        }
    }

    // Mapear a mensajes legibles
    $readable_map = array(
        'missing_name' => 'Falta nombre del destinatario',
        'missing_address' => 'Falta dirección',
        'missing_district' => 'Falta distrito',
        'missing_phone' => 'Falta teléfono',
        'invalid_phone' => 'Teléfono inválido (debe tener 9 dígitos y empezar por 9)',
        'invalid_district' => 'Distrito no reconocido',
        'distrito_not_allowed' => 'Distrito no permitido',
        'link_maps_invalid' => 'Link de Google Maps inválido (debe empezar por http:// o https://)',
        'invalid_service_type' => 'Tipo de envío no permitido',
        'mode_amount_mismatch' => 'Modo NO COBRAR pero monto distinto de 0',
        'missing_amount_for_payment_mode' => 'Modo de pago requiere monto mayor a 0',
        'invalid_pickup_date_format' => 'Formato de fecha inválido (esperado DD/MM/YYYY)',
        'invalid_shipping_date' => 'Fecha de envío inválida o no parseable',
        'shipping_date_before_today' => 'Fecha de envío anterior a hoy',
        'fecha_in_past' => 'Fecha de envío anterior a hoy',
    );

    $reason_text = null;
    $shipment_info = null;
    // Si encontramos el option name, intentar extraer shipment ID y obtener datos
    if ( $found_option ) {
        $opt = $found_option;
        if ( preg_match('/merc_import_log_(\d+)$/', $opt, $m) ) {
            $sid = intval( $m[1] );
        } elseif ( preg_match('/_transient_merc_import_log_(\d+)$/', $opt, $m) ) {
            $sid = intval( $m[1] );
        } else {
            $sid = 0;
        }
        if ( $sid > 0 ) {
            $post = get_post( $sid );
            if ( $post ) {
                // recoger metas útiles
                $phone_keys = [ 'wpcargo_receiver_phone', 'receiver_phone', 'phone', 'celular', 'telefono' ];
                $district_keys = [ 'wpcargo_distrito_destino', 'distrito_destino', 'destination_district', 'district', 'distrito' ];
                $phone = '';
                foreach ( $phone_keys as $k ) { $v = get_post_meta( $sid, $k, true ); if ( $v ) { $phone = $v; break; } }
                $district = '';
                foreach ( $district_keys as $k ) { $v = get_post_meta( $sid, $k, true ); if ( $v ) { $district = $v; break; } }
                $shipment_info = [
                    'shipment_id' => $sid,
                    'title' => get_the_title( $sid ),
                    'receiver_phone' => $phone,
                    'wpcargo_distrito_destino' => $district,
                    'permalink' => get_edit_post_link( $sid ),
                ];
            }
        }
    }
    if ( $found ) {
        $reasons = array();
        // Manejar payloads estructurados (persistidos por el preprocessor)
        if ( is_array( $found ) ) {
            // Caso: payload con claves 'errors' y/o 'logs'
            if ( isset( $found['errors'] ) || isset( $found['logs'] ) ) {
                if ( isset( $found['errors'] ) && is_array( $found['errors'] ) ) {
                    foreach ( $found['errors'] as $err ) {
                        $err = (string) $err;
                        $err_trim = trim( $err );
                        if ( isset( $readable_map[ $err_trim ] ) ) {
                            $reasons[] = $readable_map[ $err_trim ];
                            continue;
                        }
                        $mapped = false;
                        foreach ( $readable_map as $k => $v ) {
                            if ( stripos( $err_trim, $k ) !== false ) { $reasons[] = $v; $mapped = true; break; }
                        }
                        if ( ! $mapped ) $reasons[] = $err_trim;
                    }
                }
                if ( isset( $found['logs'] ) && is_array( $found['logs'] ) ) {
                    foreach ( $found['logs'] as $log ) {
                        if ( ! is_string( $log ) ) continue;
                        $log = trim( $log );
                        if ( $log === '' ) continue;
                        if ( ! in_array( $log, $reasons, true ) ) $reasons[] = $log;
                    }
                }
                // Añadir información canónica relevante si existe
                if ( isset( $found['canonical'] ) && is_array( $found['canonical'] ) ) {
                    if ( isset( $found['canonical']['wpcargo_distrito_destino'] ) && $found['canonical']['wpcargo_distrito_destino'] ) {
                        $d = $found['canonical']['wpcargo_distrito_destino'];
                        if ( ! in_array( 'Distrito: ' . $d, $reasons, true ) ) $reasons[] = 'Distrito: ' . $d;
                    }
                }
            } else {
                // Caso: array de mensajes simples (legacy)
                foreach ( $found as $item ) {
                    if ( ! is_string( $item ) ) continue;
                    $s = trim( $item );
                    if ( isset( $readable_map[ $s ] ) ) { $reasons[] = $readable_map[ $s ]; continue; }
                    $matched = false;
                    foreach ( $readable_map as $k => $v ) { if ( stripos( $s, $k ) !== false ) { $reasons[] = $v; $matched = true; break; } }
                    if ( ! $matched ) $reasons[] = $s;
                }
            }
        } else {
            // Valor simple (string)
            $s = trim( (string) $found );
            if ( isset( $readable_map[ $s ] ) ) {
                $reasons[] = $readable_map[ $s ];
            } else {
                $matched = false;
                foreach ( $readable_map as $k => $v ) {
                    if ( stripos( $s, $k ) !== false ) { $reasons[] = $v; $matched = true; break; }
                }
                if ( ! $matched ) $reasons[] = $s;
            }
        }

        $reasons = array_values( array_unique( array_filter( $reasons ) ) );
        if ( ! empty( $reasons ) ) $reason_text = implode( '; ', $reasons );
    }

    // By default return only the human-readable reason to keep payload small.
    $response = array( 'reason' => $reason_text );

    // If the client explicitly requests full debug details (debug=full) and
    // the current user is allowed, include raw payload, shipment info and
    // candidate option samples. This avoids large payloads being returned
    // unintentionally for normal requests.
    $debug_full = isset( $_REQUEST['debug'] ) && sanitize_text_field( wp_unslash( $_REQUEST['debug'] ) ) === 'full';
    if ( $debug_full && function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
        $response['raw'] = $found;
        $response['shipment'] = $shipment_info;

        // Include a short sample of candidate options (names + truncated sample)
        $diag = array();
        $like_all = $wpdb->esc_like( 'merc_import_log_' ) . '%';
        $like_val = '%' . $wpdb->esc_like( $tracking ) . '%';
        $rows_diag = $wpdb->get_results( $wpdb->prepare( "SELECT option_name, LEFT(option_value, 200) as sample FROM {$wpdb->options} WHERE option_name LIKE %s OR option_value LIKE %s LIMIT 100", $like_all, $like_val ), ARRAY_A );
        $diag_names = array();
        if ( is_array( $rows_diag ) ) {
            foreach ( $rows_diag as $r ) {
                $diag_names[] = $r;
            }
        }
        $response['debug_options'] = $diag_names;
    }

    wp_send_json_success( $response );
}

add_action( 'wp_ajax_merc_get_discard_reason', 'merc_get_discard_reason_ajax' );
add_action( 'wp_ajax_nopriv_merc_get_discard_reason', 'merc_get_discard_reason_ajax' );

