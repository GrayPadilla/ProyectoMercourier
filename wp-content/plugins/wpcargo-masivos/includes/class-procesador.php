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

        // ── Estado inicial según tipo de envío ─────────────────────────────────
        // EMPRENDEDOR (normal)  → PENDIENTE (default de WPCargo)
        // AGENCIA (express)     → RECEPCIONADO (mismo comportamiento que el formulario frontend)
        $tipo_envio_val = strtolower(trim($meta['tipo_envio'] ?? ''));
        $status_inicial = ( $tipo_envio_val === 'express' )
            ? 'RECEPCIONADO'
            : wcmas_default_status(); // PENDIENTE
        update_post_meta($post_id, 'wpcargo_status', $status_inicial);

        // Tracking number como meta adicional (WPCargo lo espera aquí también)
        update_post_meta($post_id, 'wpcargo_tracking_number', $tracking);

        // Marca de creación para identificar envíos del módulo masivo
        update_post_meta($post_id, 'wpcargo_created_via', 'envios_masivos');

        // ── Guardar datos del remitente (shipper) desde el perfil del cliente ─
        // CONFIRMADO en BD: estos meta keys son los que WPCargo usa y muestra
        // en el formulario de envío (wpcargo_tiendaname, wpcargo_shipper_phone, etc.)
        // Si el envío se asigna a un cliente, copiar sus datos de remitente al post
        // ── Datos del remitente desde perfil del cliente ────────────────────
        // PUNTO 1: el distrito_recojo se autocompleta desde el perfil del remitente
        // y NO aparece como columna editable — se extrae aquí directamente
        if ( $author ) {
            $remitente = wcmas_get_datos_remitente($author);

            // Mapa: meta_key → valor del perfil. Solo guardar si no vino ya en $meta
            // (la columna dist_recojo no existe en defaults, se extrae del cliente)
            $map_remitente = [
                'wpcargo_tiendaname'      => $remitente['nombre']    ?? '',
                'wpcargo_shipper_phone'   => $remitente['telefono']  ?? '',
                'wpcargo_shipper_address' => $remitente['direccion'] ?? '',
                'wpcargo_distrito_recojo' => $remitente['distrito']  ?? '',
                'link_maps_remitente'     => $remitente['link_maps'] ?? '',
            ];
            foreach ( $map_remitente as $key => $val ) {
                // Solo guardar si hay valor Y no fue ya guardado como columna de la grilla
                if ( $val !== '' && ! isset($meta[$key]) ) {
                    update_post_meta($post_id, $key, $val);
                } elseif ( $val !== '' && isset($meta[$key]) ) {
                    // La columna de la grilla tiene prioridad — ya fue guardada arriba
                    // No sobreescribir
                }
            }
            // Si la columna dist_recojo vino en la grilla, usarla (prioridad sobre perfil)
            if ( ! empty($meta['wpcargo_distrito_recojo']) ) {
                update_post_meta($post_id, 'wpcargo_distrito_recojo', $meta['wpcargo_distrito_recojo']);
            }
        }

        // ── Guardar historial inicial (wpcargo_shipments_update) ────────────
        // CONFIRMADO en BD: WPCargo usa este meta para el dashboard de historial
        // Formato exacto de la BD: a:1:{i:0;a:6:{s:4:"date";s:10:"23/04/2026";...}}
        // Sin este meta, el envío no aparece en el historial del día en el dashboard
        $fecha_hoy  = date('d/m/Y');   // DD/MM/YYYY — formato confirmado en BD
        $hora_hoy   = date('h:i a');   // ej: "01:22 am"
        $user_actual = wp_get_current_user();
        $user_label  = $user_actual->user_email ?: $user_actual->display_name;
        $historial_inicial = [
            [
                'date'         => $fecha_hoy,
                'time'         => $hora_hoy,
                'location'     => '',
                'status'       => $status_inicial,
                'updated-name' => $user_label,
                'remarks'      => '',
            ]
        ];
        update_post_meta($post_id, 'wpcargo_shipments_update', $historial_inicial);

        // ── Calcular y guardar campos financieros derivados ─────────────────
        // Confirmado en BD (Query G): campos reales que usa WPCargo
        $modo_pago      = strtoupper(trim($meta['payment_wpcargo_mode_field'] ?? ''));
        $costo_producto = floatval($meta['wpcargo_costo_producto'] ?? 0);
        $costo_servicio = floatval($meta['wpcargo_costo_envio']    ?? 0);
        $es_no_cobrar   = ( $modo_pago === 'NO COBRAR' );

        // monto: lo envía la grilla (0.00 si NO COBRAR, total si otro modo)
        $monto_grilla = floatval($meta['monto'] ?? 0);

        // Si NO COBRAR: destinatario no paga nada, remitente paga el servicio
        // Si otro modo: destinatario paga el monto total (costo_producto + costo_servicio)
        if ( $es_no_cobrar ) {
            $monto_final   = 0.00;
            $total_cobrar  = $costo_servicio; // se le cobra al remitente en finanzas
            $quien_paga    = 'remitente';
        } else {
            $monto_final   = $monto_grilla > 0 ? $monto_grilla : ($costo_producto + $costo_servicio);
            $total_cobrar  = $monto_final;
            $quien_paga    = 'destinatario';
        }

        // Actualizar el meta 'monto' con el valor final calculado
        update_post_meta($post_id, 'monto',                   number_format($monto_final,     2, '.', ''));
        update_post_meta($post_id, 'wpcargo_total_cobrar',    number_format($total_cobrar,    2, '.', ''));
        update_post_meta($post_id, 'wpcargo_costo_producto',  number_format($costo_producto,  2, '.', ''));
        update_post_meta($post_id, 'wpcargo_costo_envio',     number_format($costo_servicio,  2, '.', ''));
        update_post_meta($post_id, 'wpcargo_quien_paga',      $quien_paga);
        update_post_meta($post_id, 'wpcargo_cargo_remitente', '0');
        update_post_meta($post_id, 'wpcargo_cobrado_por_motorizado', '0');
        update_post_meta($post_id, 'wpcargo_estado_pago_motorizado', 'pendiente');
        update_post_meta($post_id, 'wpcargo_cliente_pago_a',  'pendiente');

        // ── PUNTO 5: Asignar contenedores ───────────────────────────────────────
        // Lógica confirmada del análisis de hooks.php del plugin de contenedores:
        //   shipment_container_recojo  → contenedor activo para el distrito de recojo
        //   shipment_container_entrega → contenedor activo para el distrito de destino
        // Se usa la misma función que usa el formulario de WPCargo
        $distrito_recojo  = get_post_meta($post_id, 'wpcargo_distrito_recojo',  true);
        $distrito_destino = get_post_meta($post_id, 'wpcargo_distrito_destino', true);

        if ( $distrito_recojo ) {
            $cont_recojo = wcmas_buscar_contenedor_activo($distrito_recojo, 'recojo');
            if ( $cont_recojo ) {
                update_post_meta($post_id, 'shipment_container_recojo', $cont_recojo);
            }
        }
        if ( $distrito_destino ) {
            $cont_entrega = wcmas_buscar_contenedor_activo($distrito_destino, 'entrega');
            if ( $cont_entrega ) {
                update_post_meta($post_id, 'shipment_container_entrega', $cont_entrega);
            }
        }

        // ── Disparar hooks de WPCargo ─────────────────────────────────────────
        do_action('wpcargo_after_create_shipment', $post_id, $meta);
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
                // CONFIRMADO en BD: WPCargo guarda la fecha en DD/MM/YYYY
                // Ejemplo real en post_meta: wpcargo_pickup_date_picker = "23/04/2026"
                // Aceptar también YYYY-MM-DD por si viene pegado desde Excel
                if ( preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $valor) ) {
                    // DD/MM/YYYY — formato nativo de WPCargo, guardar tal cual
                    [$d, $m, $y] = explode('/', $valor);
                } elseif ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor) ) {
                    // YYYY-MM-DD (pegado desde Excel) — convertir a DD/MM/YYYY
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
                // Guardar en DD/MM/YYYY — formato confirmado en BD de WPCargo
                return sprintf('%02d/%02d/%04d', (int)$d, (int)$m, (int)$y);
            case 'tipo_servicio':
                // EMPRENDEDOR→normal, AGENCIA→express, FULLFITMENT→full_fitment
                $map_servicio = ['EMPRENDEDOR'=>'normal','AGENCIA'=>'express','FULLFITMENT'=>'full_fitment'];
                // Aceptar tanto el label (EMPRENDEDOR) como el valor (normal)
                if ( isset($map_servicio[$valor]) ) return $map_servicio[$valor];
                if ( in_array($valor, $map_servicio, true) ) return $valor;
                return new \WP_Error('fmt', "'{$label}' debe ser EMPRENDEDOR, AGENCIA o FULLFITMENT.");
            case 'select_wpcf':
            case 'monto':
                // Guardar como texto/número limpio
                return sanitize_text_field($valor);
            default:
                return sanitize_text_field($valor);
        }
    }
}
