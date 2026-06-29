<?php
if (!defined('ABSPATH')) exit;

// Funciones relacionadas con penalidades

/**
 * Crear cargo por no recibido automáticamente
 */
function merc_crear_cargo_no_recibido($post_id, $customer_id, $amount = 5.00) {
    $penalty = array(
        'id'               => uniqid('penalty_'),
        'user_id'          => $customer_id,
        'post_id'          => $post_id,
        'amount'           => floatval($amount),
        'type'             => 'no_recibido_charge',
        'reason'           => 'Envío no recibido',
        'timestamp'        => current_time('mysql'),
        'tipo_liquidacion' => 'no_recibido_charge',
    );

    // Guardar en user_meta
    $history = get_user_meta($customer_id, 'merc_liquidations', true);
    if (!is_array($history)) $history = array();
    
    $history[] = $penalty;
    update_user_meta($customer_id, 'merc_liquidations', $history);

    // Guardar ID en post meta
    update_post_meta($post_id, 'wpcargo_cargo_no_recibido_liquidacion_id', $penalty['id']);
    update_post_meta($post_id, 'wpcargo_included_in_liquidation', 'pending_' . $penalty['id']);

    error_log(sprintf('[MERC FINANCE] Cargo NO RECIBIDO creado - shipment=%s user=%s amount=%s',
        $post_id, $customer_id, $amount));

    return $penalty;
}

/**
 * Obtener penalidades impagas de un usuario
 */
function merc_get_user_unpaid_penalties( $user_id ) {
    $q = new WP_Query(array(
        'post_type' => 'merc_penalty',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => 'user_id', 'value' => $user_id),
            array('key' => 'status', 'value' => 'unpaid'),
        ),
    ));
    return $q->posts;
}

/**
 * Suma total de penalidades impagas de un usuario
 */
function merc_get_user_penalty_total( $user_id ) {
    $total = 0.0;
    $penalties = merc_get_user_unpaid_penalties( $user_id );
    foreach ( $penalties as $p ) {
        $amt = floatval( get_post_meta($p->ID, 'amount', true) );
        $total += $amt;
    }
    return $total;
}

/**
 * Mostrar sección de penalidades en el perfil del usuario
 */
function merc_user_profile_penalties_section( $user ) {
    if ( ! current_user_can('edit_user', $user->ID) ) return;
    $penalties = merc_get_user_unpaid_penalties( $user->ID );
    echo '<h2>Penalidades del cliente</h2>';
    if ( empty($penalties) ) {
        echo '<p>No hay penalidades pendientes.</p>';
        return;
    }
    echo '<table class="widefat"><thead><tr><th>ID</th><th>Fecha</th><th>Monto</th><th>Acción</th></tr></thead><tbody>';
    foreach ( $penalties as $p ) {
        $date = esc_html( get_post_meta($p->ID, 'date', true) );
        $amt = number_format_i18n( floatval(get_post_meta($p->ID, 'amount', true)), 2 );
        $pay_nonce = wp_create_nonce('merc_pay_penalty_'.$p->ID);
        echo '<tr>';
        echo '<td>' . esc_html($p->ID) . '</td>';
        echo '<td>' . $date . '</td>';
        echo '<td>S/ ' . $amt . '</td>';
        echo '<td><button class="button merc-pay-penalty" data-penalty-id="'.esc_attr($p->ID).'" data-nonce="'.$pay_nonce.'">Pagar</button></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    ?>
    <script>
    (function($){
      $(document).on('click', '.merc-pay-penalty', function(e){
        e.preventDefault();
        var id = $(this).data('penalty-id');
        var nonce = $(this).data('nonce');
        var $btn = $(this).prop('disabled', true).text('Creando pedido...');
        $.post(ajaxurl, { action: 'merc_create_penalty_order', penalty_id: id, nonce: nonce }, function(resp){
          if (resp && resp.success && resp.data && resp.data.pay_url) {
            window.location.href = resp.data.pay_url;
          } else {
            alert('Error al crear pedido: ' + (resp && resp.data?resp.data:'Error desconocido'));
            $btn.prop('disabled', false).text('Pagar');
          }
        }, 'json').fail(function(){ alert('Error de red'); $btn.prop('disabled', false).text('Pagar'); });
      });
    })(jQuery);
    </script>
    <?php
}

/**
 * AJAX: crear pedido WooCommerce para la penalidad y devolver URL de pago
 */
function merc_ajax_create_penalty_order() {
    if ( empty($_POST['penalty_id']) ) wp_send_json_error('Missing penalty_id');
    $penalty_id = intval($_POST['penalty_id']);
    $nonce = isset($_POST['nonce'])? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'merc_pay_penalty_'.$penalty_id ) ) wp_send_json_error('Nonce inválido');

    $user_id = get_post_meta($penalty_id, 'user_id', true);
    if ( ! $user_id ) wp_send_json_error('Penalidad sin usuario');
    $amount = floatval( get_post_meta($penalty_id, 'amount', true) );
    if ( $amount <= 0 ) wp_send_json_error('Monto inválido');

    if ( ! class_exists('WC_Order') ) {
        wp_send_json_error('WooCommerce no está activo');
    }

    // Crear pedido simple con fee
    $order = wc_create_order(array('customer_id' => $user_id));
    if ( is_wp_error($order) ) wp_send_json_error('Error al crear pedido');

    // Añadir fee como item
    $item = new WC_Order_Item_Fee();
    $item->set_name('Penalidad por envíos no recogidos');
    $item->set_amount( $amount );
    $item->set_total( $amount );
    $order->add_item( $item );

    // Totales
    $order->calculate_totals();
    $order->save();

    // Vincular pedido con penalidad
    update_post_meta($penalty_id, 'payment_ref', $order->get_id());
    update_post_meta($penalty_id, 'status', 'pending');

    // URL de pago: endpoint order-pay
    $pay_url = wc_get_endpoint_url( 'order-pay', $order->get_id(), wc_get_page_permalink( 'checkout' ) );
    wp_send_json_success( array('pay_url' => $pay_url) );
}

/**
 * Cuando un pedido se marca como pagado en WooCommerce, marcar la penalidad como 'paid'
 */
function merc_woocommerce_order_paid_mark_penalty( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;
    // buscar penalidad vinculada
    $penalty_query = new WP_Query(array(
        'post_type' => 'merc_penalty',
        'posts_per_page' => -1,
        'meta_query' => array(
            array('key' => 'payment_ref', 'value' => $order_id),
        ),
    ));
    if ( $penalty_query->have_posts() ) {
        foreach ( $penalty_query->posts as $p ) {
            update_post_meta($p->ID, 'status', 'paid');
            update_post_meta($p->ID, 'paid_at', current_time('mysql'));
            // Trigger para que sistemas de liquidación recojan el pago
            do_action('merc_penalty_paid', $p->ID, $order_id);
        }
    }
}

/**
 * Verifica si un estado es "no recogido"
 */
function merc_status_is_no_recogido( $status ) {
    if ( empty($status) && $status !== '0' ) return false;
    if ( is_array($status) || is_object($status) ) {
        $status = json_encode($status);
    }
    $s = strtolower( trim( (string) $status ) );
    // Normalizar: quitar guiones/underscores/espacios y acentos básicos
    $s_norm = preg_replace('/[\s_\-]+/', '', $s);
    $s_norm = strtr($s_norm, array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n'));

    if ( strpos($s_norm, 'norecogido') !== false ) return true;
    if ( strpos($s, 'no recogido') !== false ) return true;
    if ( strpos($s, 'no_recogido') !== false ) return true;
    return false;
}

/**
 * Obtener customer_id de un shipment (busca en varios campos)
 */
function merc_get_shipment_customer_id($shipment_id) {
    // Buscar primero en meta
    $customer_id = get_post_meta($shipment_id, 'wpcargo_customer_id', true);
    if ( ! empty($customer_id) ) return $customer_id;
    
    // Buscar en 'registered_shipper'
    $customer_id = get_post_meta($shipment_id, 'registered_shipper', true);
    if ( ! empty($customer_id) ) return $customer_id;
    
    // Usar post_author como último recurso
    $post = get_post($shipment_id);
    if ( $post && ! empty($post->post_author) ) {
        return $post->post_author;
    }
    
    return 0;
}

/**
 * Determina si el pickup date/meta del shipment corresponde a hoy
 */
function merc_pickup_date_is_today( $shipment_id ) {
    if ( empty($shipment_id) ) return false;
    $today = current_time('Y-m-d');

    // posibles metas donde se guarda la fecha
    $candidates = array(
        get_post_meta($shipment_id, 'wpcargo_pickup_date_picker', true),
        get_post_meta($shipment_id, 'wpcargo_calendarenvio', true),
        get_post_meta($shipment_id, 'wpcargo_pickup_date', true),
    );

    foreach ( $candidates as $val ) {
        if ( empty($val) ) continue;
        $val = trim((string) $val);

        // Si ya está en formato Y-m-d
        if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) ) {
            if ( $val === $today ) return true;
            continue;
        }

        // Intentar parsear dd/mm/YYYY o d/m/YYYY
        $parts = preg_split('/[\/\-\.]/', $val);
        if ( count($parts) === 3 ) {
            // Detectar si formato es dd/mm/yyyy o yyyy-mm-dd
            if ( strlen($parts[0]) === 4 ) {
                // yyyy-mm-dd
                $normalized = sprintf('%04d-%02d-%02d', intval($parts[0]), intval($parts[1]), intval($parts[2]));
            } else {
                // dd/mm/yyyy
                $day = intval($parts[0]);
                $mon = intval($parts[1]);
                $yr = intval($parts[2]);
                if ( $yr < 100 ) $yr += 2000;
                $normalized = sprintf('%04d-%02d-%02d', $yr, $mon, $day);
            }
            if ( $normalized === $today ) return true;
        }

        // Intentar strtotime fallback
        $ts = strtotime($val);
        if ( $ts !== false ) {
            if ( date('Y-m-d', $ts) === $today ) return true;
        }
    }

    // Además, revisar el historial de actualizaciones 'wpcargo_shipments_update'
    $updates = get_post_meta($shipment_id, 'wpcargo_shipments_update', true);
    if ( ! empty($updates) ) {
        // Puede venir como array serializado o como JSON string
        if ( is_string($updates) ) {
            $maybe = json_decode($updates, true);
            if ( $maybe !== null ) $updates = $maybe;
            else {
                // intentar deserializar (caso WP que guarda array serializado)
                $maybe = @unserialize($updates);
                if ( $maybe !== false && is_array($maybe) ) $updates = $maybe;
            }
        }

        if ( is_array($updates) ) {
            foreach ( $updates as $u ) {
                if ( is_array($u) || is_object($u) ) {
                    $dateVal = isset($u['date']) ? $u['date'] : (isset($u->date) ? $u->date : '');
                } else {
                    $dateVal = (string) $u;
                }
                $dateVal = trim((string) $dateVal);
                if ( empty($dateVal) ) continue;

                // normalizar y comparar (reusar lógica de arriba)
                if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateVal) ) {
                    if ( $dateVal === $today ) return true;
                    continue;
                }
                $parts = preg_split('/[\/\-\.]/', $dateVal);
                if ( count($parts) === 3 ) {
                    if ( strlen($parts[0]) === 4 ) {
                        $normalized = sprintf('%04d-%02d-%02d', intval($parts[0]), intval($parts[1]), intval($parts[2]));
                    } else {
                        $day = intval($parts[0]);
                        $mon = intval($parts[1]);
                        $yr = intval($parts[2]);
                        if ( $yr < 100 ) $yr += 2000;
                        $normalized = sprintf('%04d-%02d-%02d', $yr, $mon, $day);
                    }
                    if ( $normalized === $today ) return true;
                }

                $ts2 = strtotime($dateVal);
                if ( $ts2 !== false && date('Y-m-d', $ts2) === $today ) return true;
            }
        }
    }

    return false;
}

/**
 * Detecta si un envío tiene fecha de recojo FUTURA (posterior al día actual)
 */
function merc_pickup_date_is_future( $shipment_id ) {
    if ( empty($shipment_id) ) return false;
    $today = current_time('Y-m-d');

    // posibles metas donde se guarda la fecha
    $candidates = array(
        get_post_meta($shipment_id, 'wpcargo_pickup_date_picker', true),
        get_post_meta($shipment_id, 'wpcargo_calendarenvio', true),
        get_post_meta($shipment_id, 'wpcargo_pickup_date', true),
    );

    foreach ( $candidates as $val ) {
        if ( empty($val) ) continue;
        $val = trim((string) $val);

        // Si ya está en formato Y-m-d
        if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $val) ) {
            if ( $val > $today ) return true;
            continue;
        }

        // Intentar parsear dd/mm/YYYY o d/m/YYYY
        $parts = preg_split('/[\/\-\.]/', $val);
        if ( count($parts) === 3 ) {
            // Detectar si formato es dd/mm/yyyy o yyyy-mm-dd
            if ( strlen($parts[0]) === 4 ) {
                // yyyy-mm-dd
                $normalized = sprintf('%04d-%02d-%02d', intval($parts[0]), intval($parts[1]), intval($parts[2]));
            } else {
                // dd/mm/yyyy
                $day = intval($parts[0]);
                $mon = intval($parts[1]);
                $yr = intval($parts[2]);
                if ( $yr < 100 ) $yr += 2000;
                $normalized = sprintf('%04d-%02d-%02d', $yr, $mon, $day);
            }
            if ( $normalized > $today ) return true;
        }

        // Intentar strtotime fallback
        $ts = strtotime($val);
        if ( $ts !== false ) {
            if ( date('Y-m-d', $ts) > $today ) return true;
        }
    }

    return false;
}

/**
 * Crea penalidad automática cuando todos los envíos NORMAL del cliente para hoy están en NO RECOGIDO
 */
function merc_maybe_create_penalty_for_shipment( $post_id ) {
    if ( empty($post_id) ) return false;
    $post = get_post($post_id);
    if ( ! $post || $post->post_type !== 'wpcargo_shipment' ) return false;

    error_log(sprintf('MERC_DEBUG_ENTRY - merc_maybe_create_penalty_for_shipment post_id=%s post_type=%s', $post_id, $post->post_type));

    // verificar que el estado del envío actual es 'no recogido'
    $estado = get_post_meta($post_id, 'wpcargo_status', true);
    error_log(sprintf('MERC_DEBUG_STATE - post_id=%s estado_raw=%s', $post_id, is_scalar($estado) ? $estado : json_encode($estado)));
    if ( ! merc_status_is_no_recogido( $estado ) ) {
        error_log(sprintf('MERC_DEBUG_ABORT - post_id=%s estado no es NO RECOGIDO, abortando', $post_id));
        return false;
    }

    $customer_id = merc_get_shipment_customer_id( $post_id );
    error_log(sprintf('MERC_DEBUG_CUSTOMER - post_id=%s customer_id=%s', $post_id, $customer_id));
    if ( ! $customer_id ) {
        error_log(sprintf('MERC_DEBUG_ABORT - post_id=%s sin customer_id', $post_id));
        return false;
    }

    $today = current_time('Y-m-d');

    // obtener todos los envíos del cliente
    $found_a = get_posts(array(
        'post_type' => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'meta_key' => 'wpcargo_customer_id',
        'meta_value' => $customer_id,
        'fields' => 'ids',
    ));
    $found_b = get_posts(array(
        'post_type' => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'meta_key' => 'registered_shipper',
        'meta_value' => $customer_id,
        'fields' => 'ids',
    ));
    $found_c = get_posts(array(
        'post_type' => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'author' => $customer_id,
        'fields' => 'ids',
    ));

    $all_posts = array();
    if ( is_array($found_a) ) $all_posts = array_merge($all_posts, $found_a);
    if ( is_array($found_b) ) $all_posts = array_merge($all_posts, $found_b);
    if ( is_array($found_c) ) $all_posts = array_merge($all_posts, $found_c);
    $all_posts = array_values( array_unique( array_filter( $all_posts ) ) );

    if ( empty($all_posts) ) {
        error_log(sprintf('MERC_DEBUG_ABORT - customer %s no tiene envíos', $customer_id));
        return false;
    }

    // Filtrar solo envíos tipo NORMAL
    $normal_posts = array();
    foreach ( $all_posts as $sid ) {
        $shipment_type = get_post_meta($sid, 'wpcargo_type_of_shipment', true);
        if ( $shipment_type === 'Normal' ) {
            $normal_posts[] = $sid;
        }
    }
    error_log(sprintf('MERC_DEBUG_TYPE_FILTER - customer=%s total_found=%d normal_only=%d', $customer_id, count($all_posts), count($normal_posts)));
    if ( empty($normal_posts) ) {
        error_log(sprintf('MERC_DEBUG_ABORT - customer %s no tiene envíos tipo NORMAL', $customer_id));
        return false;
    }

    // Filtrar envíos tipo NORMAL para hoy
    $shipments_today = array();
    foreach ( $normal_posts as $sid ) {
        if ( merc_pickup_date_is_today( $sid ) ) $shipments_today[] = $sid;
    }
    error_log(sprintf('MERC_DEBUG_PICKUPS - customer=%s normal_today_count=%d', $customer_id, count($shipments_today)));
    if ( empty($shipments_today) ) {
        error_log(sprintf('MERC_DEBUG_ABORT - customer %s no tiene envíos NORMAL para hoy', $customer_id));
        return false;
    }

    // Verificar que TODOS los envíos tipo NORMAL de hoy están en 'NO RECOGIDO'
    foreach ( $shipments_today as $sid ) {
        $s_estado = get_post_meta($sid, 'wpcargo_status', true);
        error_log(sprintf('MERC_DEBUG_CHECK - shipment=%s (NORMAL) estado=%s', $sid, is_scalar($s_estado) ? $s_estado : json_encode($s_estado)));
        if ( ! merc_status_is_no_recogido( $s_estado ) ) {
            error_log(sprintf('MERC_DEBUG_ABORT - shipment %s (NORMAL) del cliente %s no está NO_RECOGIDO', $sid, $customer_id));
            return false;
        }
    }

    // Evitar duplicados: verificar si ya existe sanción para este user+fecha
    $existing = new WP_Query(array(
        'post_type' => 'merc_penalty',
        'posts_per_page' => 1,
        'meta_query' => array(
            array('key' => 'user_id', 'value' => $customer_id),
            array('key' => 'date', 'value' => $today),
        ),
    ));
    if ( $existing->have_posts() ) return false;

    // Crear sanción
    $title = sprintf('Penalidad %s %s', $customer_id, $today);
    $penalty_id = wp_insert_post(array(
        'post_type' => 'merc_penalty',
        'post_title' => $title,
        'post_status' => 'publish',
        'post_content' => 'Penalidad automática: todos los envíos tipo NORMAL del día no fueron recogidos.',
    ));
    if ( $penalty_id && ! is_wp_error($penalty_id) ) {
        update_post_meta($penalty_id, 'amount', 5.00);
        update_post_meta($penalty_id, 'user_id', $customer_id);
        update_post_meta($penalty_id, 'shipment_ids', $shipments_today);
        update_post_meta($penalty_id, 'date', $today);
        update_post_meta($penalty_id, 'status', 'unpaid');
        
        // Actualizar el costo de todos los envíos NORMAL de hoy a 0
        foreach ( $shipments_today as $sid ) {
            update_post_meta($sid, 'wpcargo_costo_envio', 0);
            error_log(sprintf('MERC_PENALTY_COST - shipment=%s costo actualizado a 0', $sid));
        }
        
        error_log(sprintf('MERC_PENALTY - creada (auto) penalty_id=%s user=%s date=%s shipments_affected=%d', $penalty_id, $customer_id, $today, count($shipments_today)));
        return true;
    }
    return false;
}

/**
 * Detecta cuando un envío se marca como "NO RECIBIDO" y registra cargo automático
 */
function merc_auto_registrar_cargo_no_recibido($post_id, $nuevo_estado) {
    if ( empty($post_id) || empty($nuevo_estado) ) return false;
    
    $post = get_post($post_id);
    if ( ! $post || $post->post_type !== 'wpcargo_shipment' ) return false;
    
    // Verificar que el estado es "NO RECIBIDO"
    $estado_normalizado = strtoupper(trim($nuevo_estado));
    $es_no_recibido = ($estado_normalizado === 'NO RECIBIDO' || $estado_normalizado === 'NORECIBIDO');
    
    if ( ! $es_no_recibido ) {
        error_log(sprintf('MERC_NO_RECIBIDO_DEBUG - post_id=%s estado=%s NO es NO RECIBIDO', $post_id, $nuevo_estado));
        return false;
    }
    
    // Evitar duplicados
    $ya_registrado = get_post_meta($post_id, 'wpcargo_cargo_no_recibido', true);
    if ( ! empty($ya_registrado) ) {
        error_log(sprintf('MERC_NO_RECIBIDO_DEBUG - post_id=%s ya registrado previamente', $post_id));
        return false;
    }
    
    // Obtener customer_id
    $customer_id = merc_get_shipment_customer_id($post_id);
    if ( ! $customer_id ) {
        error_log(sprintf('MERC_NO_RECIBIDO_DEBUG - post_id=%s sin customer_id, abortando', $post_id));
        return false;
    }
    
    // Obtener costo del envío
    $costo_envio = floatval(get_post_meta($post_id, 'wpcargo_costo_envio', true));
    if ( $costo_envio <= 0 ) {
        error_log(sprintf('MERC_NO_RECIBIDO_DEBUG - post_id=%s costo_envio=%f (inválido)', $post_id, $costo_envio));
        return false;
    }
    
    // Crear entrada de liquidación con tipo 'no_recibido_charge'
    $liquidation = array(
        'id' => 'liq_no_recibido_' . uniqid(),
        'date' => current_time('mysql'),
        'action' => 'cargo_no_recibido',
        'shipment_id' => $post_id,
        'tipo_liquidacion' => 'no_recibido_charge',
        'amount' => round($costo_envio, 2),
        'status' => 'unpaid'
    );
    
    // Guardar en user_meta (merc_liquidations)
    $history = get_user_meta($customer_id, 'merc_liquidations', true);
    if ( ! is_array($history) ) $history = array();
    foreach ( $history as $entry ) {
        if (
            isset( $entry['shipment_id'], $entry['tipo_liquidacion'] ) &&
            intval( $entry['shipment_id'] ) === intval( $post_id ) &&
            $entry['tipo_liquidacion'] === 'no_recibido_charge'
        ) {
            error_log(sprintf('MERC_NO_RECIBIDO_DEBUG - shipment=%s ya existe en historial, no duplicar', $post_id));
            update_post_meta($post_id, 'wpcargo_cargo_no_recibido', 'si');
            return false;
        }
    }
    $history[] = $liquidation;
    update_user_meta($customer_id, 'merc_liquidations', $history);
    
    // Marcar el shipment como cargo_no_recibido registrado
    update_post_meta($post_id, 'wpcargo_cargo_no_recibido', 'si');
    update_post_meta($post_id, 'wpcargo_cargo_no_recibido_fecha', current_time('mysql'));
    update_post_meta($post_id, 'wpcargo_cargo_no_recibido_liquidacion_id', $liquidation['id']);
    update_post_meta($post_id, 'wpcargo_included_in_liquidation', 'pending_' . $liquidation['id']);
    
    error_log(sprintf('✅ MERC_NO_RECIBIDO_CARGO_REGISTRADO - post_id=%s customer=%s monto=S/. %.2f liq_id=%s', 
        $post_id, $customer_id, $costo_envio, $liquidation['id']
    ));
    
    return true;
}

