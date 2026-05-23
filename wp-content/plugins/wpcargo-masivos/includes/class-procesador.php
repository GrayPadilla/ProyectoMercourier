<?php
if ( ! defined('ABSPATH') ) exit;

class WCMAS_Procesador {

    /**
     * Procesa una fila y crea el envío en WPCargo.
     *
     * POST TYPE CORRECTO: 'wpcargo_shipment' (confirmado del plugin oficial import/export)
     * REMITENTE:          post_author = user_id Y meta 'registered_shipper' = user_id
     * ESTADO:             meta 'wpcargo_status' = default status de WPCargo
     */
    public static function procesar_fila( array $fila, int $user_id = 0 ): array {
        $columnas = WCMAS_Columnas::obtener_activas();
        $errores  = [];
        $meta     = [];
        $author   = $user_id ?: get_current_user_id();

        foreach ( $columnas as $col ) {
            $valor = trim($fila[$col['id']] ?? '');

            // Aplicar valor por defecto si la celda está vacía
            if ( $valor === '' && ($col['default_val'] ?? '') !== '' ) {
                $valor = $col['default_val'];
            }

            if ( $col['obligatorio'] && $valor === '' ) {
                $errores[$col['id']] = "'{$col['label']}' es obligatorio.";
                continue;
            }
            if ( $valor === '' ) continue;

            $validado = self::validar_tipo($valor, $col['tipo'], $col['label']);
            if ( is_wp_error($validado) ) {
                $errores[$col['id']] = $validado->get_error_message();
                continue;
            }
            $meta[$col['meta_key']] = $validado;
        }

        if ( ! empty($errores) ) {
            return ['ok' => false, 'errores' => $errores, 'datos' => $fila];
        }

        // Generar número de tracking (= post_title en WPCargo)
        $tracking = wcmas_generar_tracking();

        // Crear el envío con el post_type correcto de WPCargo
        $post_id = wp_insert_post([
            'post_type'   => 'wpcargo_shipment',   // ← CORRECTO según plugin import/export oficial
            'post_status' => 'publish',
            'post_author' => $author,
            'post_title'  => $tracking,
            'post_name'   => sanitize_title($tracking),
        ], true);

        if ( is_wp_error($post_id) ) {
            return ['ok' => false, 'errores' => ['_' => $post_id->get_error_message()], 'datos' => $fila];
        }

        // ── Guardar meta del envío ────────────────────────────────────────────

        // Meta personalizado configurado por el admin (destinatario, dirección, etc.)
        foreach ( $meta as $key => $val ) {
            update_post_meta($post_id, $key, $val);
        }

        // registered_shipper: vincula el envío al cliente (igual que WPCargo)
        update_post_meta($post_id, 'registered_shipper', $author);

        // Estado inicial del envío (usa el default de WPCargo)
        update_post_meta($post_id, 'wpcargo_status', wcmas_default_status());

        // Tracking number como meta adicional (WPCargo lo espera aquí también)
        update_post_meta($post_id, 'wpcargo_tracking_number', $tracking);

        // Marca de creación para identificar envíos del módulo masivo
        update_post_meta($post_id, 'wpcargo_created_via', 'envios_masivos');

        // ── Disparar hooks de WPCargo ─────────────────────────────────────────
        // WPCargo usa este hook para notificaciones, etc.
        do_action('wpcargo_after_create_shipment', $post_id, $meta);
        // Hook adicional del frontend de WPCargo
        do_action('wpcfe_after_create_shipment', $post_id);

        return [
            'ok'       => true,
            'post_id'  => $post_id,
            'tracking' => $tracking,
            'errores'  => [],
            'datos'    => $fila,
        ];
    }

    public static function procesar_lote( array $filas, int $user_id = 0 ): array {
        $resultados = [];
        foreach ( $filas as $i => $fila ) {
            $r = self::procesar_fila($fila, $user_id);
            $r['fila_num'] = $i + 1;
            $resultados[]  = $r;
        }
        return $resultados;
    }

    public static function validar_fila( array $fila ): array {
        $columnas = WCMAS_Columnas::obtener_activas();
        $errores  = [];
        foreach ( $columnas as $col ) {
            $valor = trim($fila[$col['id']] ?? '');
            if ( $valor === '' && ($col['default_val'] ?? '') !== '' ) continue;
            if ( $col['obligatorio'] && $valor === '' ) {
                $errores[$col['id']] = "'{$col['label']}' es obligatorio.";
                continue;
            }
            if ( $valor === '' ) continue;
            $v = self::validar_tipo($valor, $col['tipo'], $col['label']);
            if ( is_wp_error($v) ) $errores[$col['id']] = $v->get_error_message();
        }
        return $errores;
    }

    private static function validar_tipo( string $valor, string $tipo, string $label ): string|\WP_Error {
        switch ($tipo) {
            case 'number':
                if ( ! is_numeric(str_replace(',', '.', $valor)) ) {
                    return new \WP_Error('fmt', "'{$label}' debe ser un número.");
                }
                return str_replace(',', '.', $valor);
            case 'phone':
                $limpio = preg_replace('/[\s\-\+\(\)]/', '', $valor);
                if ( ! preg_match('/^\d{7,15}$/', $limpio) ) {
                    return new \WP_Error('fmt', "'{$label}' debe ser un teléfono válido.");
                }
                return $limpio;
            case 'email':
                if ( ! is_email($valor) ) {
                    return new \WP_Error('fmt', "'{$label}' debe ser un email válido.");
                }
                return sanitize_email($valor);
            case 'date':
                // El usuario ingresa DD/MM/YYYY (via Flatpickr)
                // WPCargo guarda internamente en YYYY-MM-DD (confirmado en su documentación oficial)
                // Aceptar ambos formatos por si viene pegado desde Excel
                if ( preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $valor) ) {
                    // DD/MM/YYYY → convertir
                    [$d, $m, $y] = explode('/', $valor);
                } elseif ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ) {
                    // Ya viene en YYYY-MM-DD
                    [$y, $m, $d] = explode('-', $valor);
                } else {
                    return new \WP_Error('fmt', "'{$label}' debe tener el formato DD/MM/YYYY.");
                }
                if ( ! checkdate((int)$m, (int)$d, (int)$y) ) {
                    return new \WP_Error('fmt', "'{$label}' no es una fecha válida.");
                }
                $ts = mktime(0, 0, 0, (int)$m, (int)$d, (int)$y);
                if ( date('N', $ts) === '7' ) {
                    return new \WP_Error('fmt', "'{$label}' no puede ser domingo (día no laborable).");
                }
                // Guardar en YYYY-MM-DD — formato interno que usa WPCargo en post_meta
                return sprintf('%04d-%02d-%02d', (int)$y, (int)$m, (int)$d);
            default:
                return sanitize_text_field($valor);
        }
    }
}
