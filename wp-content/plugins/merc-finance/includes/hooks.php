<?php
if (!defined('ABSPATH')) exit;

// Hooks y acciones para finanzas

/**
 * Al marcar un pedido como "No recibido", crear cargo automático
 */
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    if ($meta_key !== 'wpcargo_status') return;
    if ($meta_value !== 'No recibido') return;

    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'wpcargo_shipment') return;

    // Obtener customer ID
    $customer_id = get_post_meta($post_id, 'wpcargo_customer', true);
    if (!$customer_id) return;

    // Verificar si ya existe cargo
    $existing = get_post_meta($post_id, 'wpcargo_cargo_no_recibido_liquidacion_id', true);
    if ($existing) return;

    // Crear cargo
    merc_crear_cargo_no_recibido($post_id, $customer_id, 5.00);
}, 10, 4);

// Hook de activación para registrar post types
add_action('merc_finance_activate', function() {
    merc_finance_register_post_types();
    flush_rewrite_rules();
});

// Mostrar penalidades en perfil del usuario
add_action('show_user_profile', 'merc_user_profile_penalties_section');
add_action('edit_user_profile', 'merc_user_profile_penalties_section');

// AJAX handler para crear orden de pago
add_action('wp_ajax_merc_create_penalty_order', 'merc_ajax_create_penalty_order');

// Marcar penalidad como pagada cuando se completa pago en WooCommerce
add_action('woocommerce_payment_complete', 'merc_woocommerce_order_paid_mark_penalty');

// Interceptar cambios de estado antes de actualizar
add_action('update_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    // Interceptar ANTES de que se actualice, para capturar el estado anterior
    if ($meta_key !== 'wpcargo_status') {
        return;
    }
    
    $estado_actual = get_post_meta($post_id, 'wpcargo_status', true);
    
    // Si el nuevo estado es "LISTO PARA SALIR" y hay un estado anterior diferente
    if (!empty($meta_value) && stripos($meta_value, 'LISTO PARA SALIR') !== false && !empty($estado_actual) && $estado_actual !== $meta_value) {
        error_log("🔵 [BEFORE_META_UPDATE] Interceptando cambio de estado en Envío #" . $post_id);
        error_log("   Estado Actual: '" . $estado_actual . "' -> Nuevo: '" . $meta_value . "'");
        
        // Guardar en meta específico ANTES de que se actualice
        update_post_meta($post_id, 'wpcargo_status_anterior', $estado_actual);
        error_log("   ✅ Meta 'wpcargo_status_anterior' establecido a: '" . $estado_actual . "'");
        
        // También agregar al historial - PERO SOLO si no existe un registro reciente igual
        $historial = maybe_unserialize(get_post_meta($post_id, 'wpcargo_shipments_update', true));
        if (!is_array($historial)) {
            $historial = array();
        }
        
        // Verificar si el primer registro ya es identical (evitar duplicados)
        $crear_registro = true;
        if (!empty($historial) && is_array($historial[0])) {
            $first = $historial[0];
            if ($first['status'] === $estado_actual && 
                strpos($first['remarks'], 'Estado anterior') !== false) {
                error_log("   ℹ️  Registro anterior ya existe, evitando duplicado");
                $crear_registro = false;
            }
        }
        
        if ($crear_registro) {
            // Crear registro del estado anterior
            $previous_state_record = array(
                'status' => $estado_actual,
                'date' => current_time('Y-m-d'),
                'time' => current_time('H:i:s'),
                'updated-name' => wp_get_current_user()->display_name,
                'location' => get_post_meta($post_id, 'location', true),
                'remarks' => 'Estado anterior (cambio a LISTO PARA SALIR)'
            );
            
            array_unshift($historial, $previous_state_record);
            update_post_meta($post_id, 'wpcargo_shipments_update', $historial);
            error_log("   ✅ Historial actualizado (total: " . count($historial) . " registros)");
        }
    }
}, 10, 4);

// Detectar cambios de estado y crear penalidades/cargos
add_action('updated_post_meta', function($meta_id, $post_id, $meta_key, $meta_value) {
    if ( defined('MERC_DEBUG') && MERC_DEBUG ) {
        error_log(sprintf('MERC_DEBUG_META_UPDATE - meta_id=%s post_id=%s meta_key=%s meta_value=%s', $meta_id, $post_id, $meta_key, is_scalar($meta_value) ? $meta_value : json_encode($meta_value)));
    }

    if ( $meta_key !== 'wpcargo_status' ) {
        if ( defined('MERC_DEBUG') && MERC_DEBUG ) {
            error_log('MERC_DEBUG_META_UPDATE - Ignorado (no es wpcargo_status)');
        }
        return;
    }

    merc_maybe_create_penalty_for_shipment($post_id);
    merc_auto_registrar_cargo_no_recibido($post_id, $meta_value);
    merc_sync_service_cost_by_status( $post_id );
}, 10, 4);

if ( ! function_exists( 'merc_get_adjusted_service_cost' ) ) {
    function merc_get_adjusted_service_cost( $shipment_id ) {
        $status = strtoupper( trim( (string) get_post_meta( $shipment_id, 'wpcargo_status', true ) ) );

        $base_cost = floatval( get_post_meta( $shipment_id, 'wpcargo_costo_envio', true ) );

        if ( stripos( $status, 'REPROGRAMADO' ) !== false || stripos( $status, 'ANULADO' ) !== false ) {
            $base_cost = 0.0;
        }
        
        // Sumar cargos adicionales si existen
        $cargos = get_post_meta( $shipment_id, 'merc_cargos_adicionales', true );
        $adicional = 0.0;
        if ( is_array( $cargos ) ) {
            foreach ( $cargos as $cargo ) {
                $adicional += floatval( $cargo['monto'] );
            }
        }
        
        return $base_cost + $adicional;
    }
}

if ( ! function_exists( 'merc_sync_service_cost_by_status' ) ) {
    function merc_sync_service_cost_by_status( $shipment_id ) {
        if ( get_post_type( $shipment_id ) !== 'wpcargo_shipment' ) {
            return;
        }

        $status = strtoupper( trim( (string) get_post_meta( $shipment_id, 'wpcargo_status', true ) ) );
        $special = ( stripos( $status, 'REPROGRAMADO' ) !== false || stripos( $status, 'ANULADO' ) !== false );

        $service_key  = 'wpcargo_costo_envio';
        $backup_key   = '_merc_original_wpcargo_costo_envio';
        $flag_key     = '_merc_service_zeroed_by_status';
        $current_cost  = get_post_meta( $shipment_id, $service_key, true );

        if ( $special ) {
            if ( get_post_meta( $shipment_id, $backup_key, true ) === '' && $current_cost !== '' ) {
                update_post_meta( $shipment_id, $backup_key, $current_cost );
            }

            update_post_meta( $shipment_id, $service_key, '0.00' );
            update_post_meta( $shipment_id, $flag_key, '1' );
            return;
        }

        if ( get_post_meta( $shipment_id, $flag_key, true ) === '1' ) {
            $original_cost = get_post_meta( $shipment_id, $backup_key, true );

            if ( $original_cost !== '' ) {
                update_post_meta( $shipment_id, $service_key, $original_cost );
            }

            delete_post_meta( $shipment_id, $flag_key );
        }
    }
}

// AJAX: generar penalidades del día
add_action('wp_ajax_merc_generate_penalties_today', function() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'merc_generate_penalties' ) ) {
        wp_send_json_error(array('message'=>'Nonce inválido'));
    }
    if ( ! current_user_can('administrator') ) {
        wp_send_json_error(array('message'=>'Sin permisos'));
    }

    $today = current_time('Y-m-d');
    error_log(sprintf('MERC_DEBUG_GENERATOR - start today=%s', $today));
    
    $args = array(
        'post_type' => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key'=>'wpcargo_pickup_date_picker','value'=>$today),
        ),
        'fields' => 'ids',
    );
    $q = new WP_Query($args);
    if ( empty($q->posts) ) {
        error_log('MERC_DEBUG_GENERATOR - no shipments for today');
        wp_send_json_success(array('message'=>'No hay envíos para la fecha de hoy.'));
    }

    error_log(sprintf('MERC_DEBUG_GENERATOR - total_shipments_found=%d', count($q->posts)));

    // Agrupar por cliente
    $clients = array();
    foreach ( $q->posts as $sid ) {
        $cid = get_post_meta($sid,'wpcargo_customer_id', true);
        if ( empty($cid) ) continue;
        if ( ! isset($clients[$cid]) ) $clients[$cid] = array();
        $clients[$cid][] = $sid;
    }

    $created = 0; $skipped = 0; $details = array();
    foreach ( $clients as $cid => $sids ) {
        error_log(sprintf('MERC_DEBUG_GENERATOR - processing client=%s shipments=%d', $cid, count($sids)));
        $all_no = true;
        foreach ( $sids as $sid ) {
            $st = get_post_meta($sid,'wpcargo_status', true);
            error_log(sprintf('MERC_DEBUG_GENERATOR - shipment=%s status=%s', $sid, is_scalar($st)?$st:json_encode($st)));
            if ( function_exists('merc_status_is_no_recogido') ) {
                if ( ! merc_status_is_no_recogido($st) ) { $all_no = false; break; }
            } else {
                if ( stripos($st,'no recogido') === false && stripos($st,'no_recogido')===false ) { $all_no = false; break; }
            }
        }
        if ( ! $all_no ) { error_log(sprintf('MERC_DEBUG_GENERATOR - client %s skipped (not all NO RECOGIDO)', $cid)); $skipped++; continue; }

        // verificar duplicado
        $existing = new WP_Query(array('post_type'=>'merc_penalty','posts_per_page'=>1,'meta_query'=>array(array('key'=>'user_id','value'=>$cid),array('key'=>'date','value'=>$today))));
        if ( $existing->have_posts() ) { $details[] = "user {$cid}: exists"; continue; }

        $title = sprintf('Penalidad %s %s', $cid, $today);
        $penalty_id = wp_insert_post(array(
            'post_type'=>'merc_penalty','post_title'=>$title,'post_status'=>'publish','post_content'=>'Penalidad automática por envíos no recogidos.'
        ));
        if ( $penalty_id && ! is_wp_error($penalty_id) ) {
            update_post_meta($penalty_id,'amount',5.00);
            update_post_meta($penalty_id,'user_id',$cid);
            update_post_meta($penalty_id,'shipment_ids',$sids);
            update_post_meta($penalty_id,'date',$today);
            update_post_meta($penalty_id,'status','unpaid');
            $created++; $details[] = "user {$cid}: created {$penalty_id}";
        }
    }

    $msg = sprintf('Penalidades creadas: %d, omitidos: %d', $created, $skipped);
    wp_send_json_success(array('message'=>$msg,'details'=>$details));
});



