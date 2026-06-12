<?php
/**
 * Motor de decisión de bloqueos
 */

if (!defined('ABSPATH')) {
    exit;
}

class Merc_Bloqueos_Logic {

    /**
     * Evalúa si un cliente está bloqueado para un tipo de servicio hoy.
     * Retorna array con:
     * - bloqueo_total: bool
     * - bloquear_hoy: bool
     * - reason: string (debug)
     */
    public static function evaluate($client_id, $tipo) {
        global $wpdb;
        // Administradores nunca se bloquean
        $current_user = wp_get_current_user();
        if (in_array('administrator', (array) $current_user->roles)) {
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => false,
                'reason' => 'is_admin'
            ];
        }

        // Usar wp_date para respetar la zona horaria de WordPress (Lima)
        $today = wp_date('Y-m-d');
        $now = wp_date('H:i');
        $tipo_clean = strtolower(trim($tipo));

        // 1. Verificación de Bloqueo Total (aplica a todos los tipos, incluyendo masivos)
        $bloqueo_total = get_user_meta($client_id, 'merc_bloqueo_total', true);
        if ($bloqueo_total === '1') {
            return [
                'bloqueo_total' => true,
                'bloquear_hoy' => true,
                'reason' => 'bloqueo_total'
            ];
        }

        // 1b. Tipo 'masivo': exento de restricciones horarias.
        //     El módulo de Envíos Masivos tiene su propio flujo (AJAX directo vía wp_insert_post)
        //     y no debe verse afectado por los límites de horario del formulario individual.
        //     El bloqueo total de cuenta (arriba) sí aplica.
        if ($tipo_clean === 'masivo') {
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => false,
                'reason'       => 'tipo_masivo_exento'
            ];
        }

        // 2. Verificación de Desbloqueo Temporal
        $temp_unlock_type = get_user_meta($client_id, 'merc_temp_unlock_type', true);
        $temp_unlock_expire = get_user_meta($client_id, 'merc_temp_unlock_expire', true);
        
        if (!empty($temp_unlock_expire) && current_time('timestamp') < (int)$temp_unlock_expire) {
            if ($temp_unlock_type === 'all' || $temp_unlock_type === $tipo_clean) {
                return [
                    'bloqueo_total' => false,
                    'bloquear_hoy' => false,
                    'reason' => 'temp_unlock'
                ];
            }
        }

        // 3. Obtener límites de hora
        if ($tipo_clean === 'normal' || $tipo_clean === 'masivo' || stripos($tipo, 'emprendedor') !== false) {
            $limite_sin = get_option('merc_hora_emprendedor_sin_pedidos', '10:30');
            $limite_con = get_option('merc_hora_emprendedor_con_pedidos', '10:30');
            $is_emprendedor = true;
        } elseif ($tipo_clean === 'express' || stripos($tipo, 'agencia') !== false) {
            $limite_sin = get_option('merc_hora_agencia_sin_pedidos', '12:30');
            $limite_con = get_option('merc_hora_agencia_con_pedidos', '12:30');
            $is_emprendedor = false;
        } else {
            // full_fitment
            $limite_sin = get_option('merc_hora_full_sin_pedidos', '12:30');
            $limite_con = get_option('merc_hora_full_con_pedidos', '12:30');
            $is_emprendedor = false;
        }

        // 4. Conteo de pedidos:
        $today_iso = date('Y-m-d');
        $today_dmy = date('d/m/Y');
        
        $log_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/merc_logs' : ABSPATH . 'wp-content/merc_logs';
        if (!file_exists($log_dir)) @mkdir($log_dir, 0755, true);
        $debug_file = $log_dir . '/merc-debug-manual.log';

        // Consulta ultra-simplificada para no fallar
        $all_today = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, 
                   pm_shipper.meta_value as shipper_id,
                   pm_type.meta_value as tipo_envio,
                   pm_status.meta_value as estado
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = 'wpcargo_pickup_date_picker'
            LEFT JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
            LEFT JOIN {$wpdb->postmeta} pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = 'wpcargo_type_of_shipment'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status = 'publish'
            AND (pm_date.meta_value = %s OR pm_date.meta_value = %s)
        ", $today_iso, $today_dmy));

        $envios_hoy = [];
        foreach ($all_today as $ship) {
            // Comprobación flexible del ID del cliente
            if (trim($ship->shipper_id) == trim($client_id)) {
                $t = strtolower($ship->tipo_envio);
                if ($tipo_clean === 'normal' || $tipo_clean === 'masivo') {
                    if ($t === 'normal' || stripos($t, 'programado') !== false || empty($t)) {
                        $envios_hoy[] = $ship;
                    }
                } else {
                    if (stripos($t, $tipo_clean) !== false) {
                        $envios_hoy[] = $ship;
                    }
                }
            }
        }

        $tiene_pedidos = count($envios_hoy) > 0;

        // --- DEBUG LOG ---
        $log_dir = defined('WP_CONTENT_DIR') ? WP_CONTENT_DIR . '/merc_logs' : ABSPATH . 'wp-content/merc_logs';
        if (!file_exists($log_dir)) @mkdir($log_dir, 0755, true);
        $debug_file = $log_dir . '/merc-debug-manual.log';
        $log_msg = sprintf(
            "[%s] [MERC DEBUG] Usuario: %d | Tipo: %s | ISO: %s | DMY: %s | Encontrados: %d\n",
            date('Y-m-d H:i:s'), $client_id, $tipo, $today_iso, $today_dmy, count($envios_hoy)
        );
        error_log($log_msg, 3, $debug_file);
        if ($tiene_pedidos) {
            foreach ($envios_hoy as $e) {
                error_log("  - Pedido ID: {$e->ID} | Estado: {$e->estado} | Tipo: {$e->tipo_envio}\n", 3, $debug_file);
            }
        }
        // -----------------
        
        $limite_actual = $tiene_pedidos ? $limite_con : $limite_sin;

        // 5. Comparar hora
        if ($now < $limite_actual) {
            error_log(sprintf("[%s] [MERC DEBUG] Resultado: NO BLOQUEAR (antes de hora %s)\n", date('Y-m-d H:i:s'), $limite_actual), 3, $debug_file);
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => false,
                'reason' => 'antes_de_hora'
            ];
        }

        // 6. Pasada la hora, aplicar reglas por tipo
        if (!$is_emprendedor) {
            error_log(sprintf("[%s] [MERC DEBUG] Resultado: BLOQUEAR (hora pasada tipo no-emprendedor)\n", date('Y-m-d H:i:s')), 3, $debug_file);
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => true,
                'reason' => 'hora_pasada_express'
            ];
        }

        // Es EMPRENDEDOR pasada la hora
        if (!$tiene_pedidos) {
            error_log(sprintf("[%s] [MERC DEBUG] Resultado: BLOQUEAR (hora pasada sin pedidos)\n", date('Y-m-d H:i:s')), 3, $debug_file);
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => true,
                'reason' => 'hora_pasada_sin_pedidos'
            ];
        }

        // Tiene pedidos, ¿Hay alguno PENDIENTE?
        $tiene_pendientes = false;
        foreach ($envios_hoy as $envio) {
            $estado = strtoupper(trim($envio->estado));
            // Consideramos pendiente si el estado contiene 'PENDIENTE' o 'RECIBIDO' o está vacío
            if (empty($estado) || stripos($estado, 'PENDIENTE') !== false || stripos($estado, 'RECIBIDO') !== false) {
                $tiene_pendientes = true;
                break;
            }
        }

        if ($tiene_pendientes) {
            error_log(sprintf("[%s] [MERC DEBUG] Resultado: NO BLOQUEAR (tiene pendientes)\n", date('Y-m-d H:i:s')), 3, $debug_file);
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => false,
                'reason' => 'tiene_pendientes_emprendedor'
            ];
        } else {
            error_log(sprintf("[%s] [MERC DEBUG] Resultado: BLOQUEAR (todos procesados)\n", date('Y-m-d H:i:s')), 3, $debug_file);
            return [
                'bloqueo_total' => false,
                'bloquear_hoy' => true,
                'reason' => 'todos_procesados_emprendedor'
            ];
        }
    }
}

