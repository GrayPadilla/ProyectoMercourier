<?php
if (!defined('ABSPATH')) exit;

// AJAX handlers para warehouse (logueados solamente)
add_action('wp_ajax_merc_almacen_get_productos', 'merc_almacen_get_productos');
function merc_almacen_get_productos() {
    // Validar nonce si está presente
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
    }
    
    $current_user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');
    $is_client = in_array('wpcargo_client', (array)$current_user->roles);
    
    if (!is_user_logged_in() || (!$is_admin && !$is_client)) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'No autorizado']);
    }
    
    // Preparar query de productos
    $args = array(
        'post_type' => 'merc_producto',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'title',
        'order' => 'ASC'
    );
    
// Si es cliente (no admin), filtrar solo productos asignados a él
if ($is_client && !$is_admin) {
    $args['meta_query'] = array(
        array(
            'key' => '_merc_producto_cliente_asignado',
            'value' => (string) $current_user->ID,
            'compare' => '=',
            'type' => 'CHAR'
        )
    );
}

// Si es admin y seleccionó un cliente, filtrar por ese cliente
if ($is_admin && !empty($_POST['cliente_id'])) {
    $args['meta_query'] = array(
        array(
            'key' => '_merc_producto_cliente_asignado',
            'value' => (string) intval($_POST['cliente_id']),
            'compare' => '=',
            'type' => 'CHAR'
        )
    );
}
    
    $ps = get_posts($args);
    $lista = array();
    
    foreach ($ps as $p) {
        $cliente_meta = get_post_meta($p->ID, '_merc_producto_cliente_asignado', true);
        
        // Filtro manual para clientes
        if ($is_client && !$is_admin) {
            if ((string)$cliente_meta !== (string)$current_user->ID) {
                continue;
            }
        }
        
        $c = function_exists('merc_get_product_stock') ? merc_get_product_stock($p->ID) : 0;
        $estado = get_post_meta($p->ID, '_merc_producto_estado', true) ?: 'sin_asignar';
        $motorizado = get_post_meta($p->ID, '_merc_producto_motorizado', true) ?: '-';
        
        // Obtener billing_company del cliente asignado
        $billing_company = '';
        $cliente_nombre = '';
        if ($cliente_meta) {
            $bc = get_user_meta(intval($cliente_meta), 'billing_company', true);
            if (!empty($bc)) {
                $billing_company = $bc;
            } else {
                $fn = get_user_meta(intval($cliente_meta), 'billing_first_name', true);
                $ln = get_user_meta(intval($cliente_meta), 'billing_last_name', true);
                $billing_company = trim($fn . ' ' . $ln);
                if (empty($billing_company)) {
                    $u = get_userdata(intval($cliente_meta));
                    $billing_company = $u ? $u->display_name : 'Sin nombre';
                }
            }
            // También guardar nombre completo como fallback
            $fn = get_user_meta(intval($cliente_meta), 'first_name', true);
            $ln = get_user_meta(intval($cliente_meta), 'last_name', true);
            $cliente_nombre = trim($fn . ' ' . $ln);
            if (empty($cliente_nombre)) {
                $u = get_userdata(intval($cliente_meta));
                $cliente_nombre = $u ? $u->display_name : 'Sin nombre';
            }
        }

        $lista[] = array(
            'id' => $p->ID,
            'nombre' => $p->post_title,
            'codigo_barras' => get_post_meta($p->ID, '_merc_producto_codigo_barras', true),
            'cliente_asignado' => $cliente_meta,
            'billing_company' => $billing_company,
            'cliente_nombre' => $cliente_nombre,
            'cantidad' => !empty($c) ? intval($c) : 0,
            'fecha_creacion' => get_the_date('d/m/Y H:i', $p->ID),
            'fecha_modificacion' => get_the_modified_date('d/m/Y H:i', $p->ID),
            'estado' => $estado,
            'motorizado' => $motorizado,
            'tipo_medida' => get_post_meta($p->ID, '_merc_producto_tipo_medida', true) ?: '',
            'valor_medida' => get_post_meta($p->ID, '_merc_producto_valor_medida', true) ?: '',
        );
    }
    
    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    wp_send_json_success(['productos' => $lista]);
}

// AJAX handler para guardar nuevo producto
add_action('wp_ajax_merc_guardar_producto', 'merc_guardar_producto');
function merc_guardar_producto() {
    // Validar nonce si está presente
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
    }
    
    $current_user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');
    
    if (!is_user_logged_in() || !$is_admin) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'No tienes permisos para crear productos']);
    }
    
    // Capturar datos del formulario
    $nombre = sanitize_text_field($_POST['nombre'] ?? '');
    $codigo_barras = sanitize_text_field($_POST['codigo_barras'] ?? '');
    $cantidad = intval($_POST['cantidad'] ?? 1);
    $cliente_asignado = intval($_POST['cliente_asignado'] ?? 0);
    $peso = floatval($_POST['peso'] ?? 0);
    $tipo_medida = sanitize_text_field($_POST['tipo_medida'] ?? '');
    $valor_medida = sanitize_text_field($_POST['valor_medida'] ?? '');
    $largo = floatval($_POST['largo'] ?? 0);
    $ancho = floatval($_POST['ancho'] ?? 0);
    $alto = floatval($_POST['alto'] ?? 0);
    
    if (empty($nombre)) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'El nombre del producto es obligatorio']);
    }
    
    // Crear el post del producto
    $post_data = array(
        'post_type' => 'merc_producto',
        'post_title' => $nombre,
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => $current_user->ID
    );
    
    $product_id = wp_insert_post($post_data);
    
    if (is_wp_error($product_id)) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'Error al crear el producto']);
    }
    
    // Guardar metadatos
    if (!empty($codigo_barras)) {
        update_post_meta($product_id, '_merc_producto_codigo_barras', $codigo_barras);
    }
    
    if ($cliente_asignado > 0) {
        update_post_meta($product_id, '_merc_producto_cliente_asignado', (string)$cliente_asignado);
    }
    
    update_post_meta($product_id, '_merc_producto_estado', 'sin_asignar');
    
    if ($peso > 0) {
        update_post_meta($product_id, '_merc_producto_peso', $peso);
    }
    
    if (!empty($tipo_medida)) {
        update_post_meta($product_id, '_merc_producto_tipo_medida', $tipo_medida);
    }
    
    if (!empty($valor_medida)) {
        update_post_meta($product_id, '_merc_producto_valor_medida', $valor_medida);
    }
    
    if ($largo > 0 || $ancho > 0 || $alto > 0) {
        $dimensiones = array('largo' => $largo, 'ancho' => $ancho, 'alto' => $alto);
        update_post_meta($product_id, '_merc_producto_dimensiones', $dimensiones);
    }
    
    // Asignar cantidad en stock si la función existe
    if (function_exists('merc_set_product_stock')) {
        merc_set_product_stock($product_id, $cantidad, $codigo_barras);
    }
    
    error_log("✅ Producto creado: #{$product_id} - {$nombre}");
    
    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    wp_send_json_success([
        'message' => 'Producto creado exitosamente',
        'product_id' => $product_id,
        'nombre' => $nombre
    ]);
}

// AJAX handler para obtener lista de clientes
add_action('wp_ajax_merc_obtener_clientes_lista', 'merc_obtener_clientes_lista');
add_action('wp_ajax_nopriv_merc_obtener_clientes_lista', 'merc_obtener_clientes_lista');
function merc_obtener_clientes_lista() {
    // Validar nonce si está presente (pero no lo requerimos)
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            // Log pero no bloqueamos
            error_log("⚠️ Nonce inválido en merc_obtener_clientes_lista");
        }
    }
    
    // Permitir a usuarios logueados o admins
    if (!current_user_can('manage_options') && !is_user_logged_in()) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'Debes estar logueado']);
        return;
    }
    
    // Obtener clientes
    $clientes = get_users(array(
        'role' => 'wpcargo_client',
        'orderby' => 'display_name',
        'order' => 'ASC',
        'number' => 500
    ));
    
    error_log("📋 Obteniendo clientes - Total: " . count($clientes));
    
    $lista_clientes = array();
    foreach ($clientes as $cliente) {
        // Obtener datos del cliente
        $empresa = get_user_meta($cliente->ID, 'billing_company', true) ?: get_user_meta($cliente->ID, 'company', true) ?: 'Sin Empresa';
        $nombre = get_user_meta($cliente->ID, 'billing_first_name', true) ?: $cliente->first_name;
        $apellido = get_user_meta($cliente->ID, 'billing_last_name', true) ?: $cliente->last_name;
        
        // Si no hay nombre/apellido, usar display_name
        if (empty($nombre) && empty($apellido)) {
            $nombre_completo = $cliente->display_name;
        } else {
            $nombre_completo = trim($nombre . ' ' . $apellido);
        }
        
        $lista_clientes[] = array(
            'id' => (string)$cliente->ID, // Convertir a string para consistencia
            'nombre' => $empresa . ' (' . $nombre_completo . ')'
        );
    }
    
    error_log("📋 Clientes formateados: " . json_encode($lista_clientes));
    
    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    wp_send_json_success(['clientes' => $lista_clientes]);
}

// AJAX handler para obtener datos de un producto
add_action('wp_ajax_merc_obtener_producto', 'merc_obtener_producto');
function merc_obtener_producto() {
    // Validar nonce si está presente
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            while ( ob_get_level() > 0 ) { ob_end_clean(); }
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
    }
    
    if (!is_user_logged_in()) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'No estás logueado']);
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'ID de producto inválido']);
    }
    
    $producto = get_post($product_id);
    if (!$producto || $producto->post_type !== 'merc_producto') {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'Producto no encontrado']);
    }
    
    $cliente_meta = get_post_meta($product_id, '_merc_producto_cliente_asignado', true);
    $c = function_exists('merc_get_product_stock') ? merc_get_product_stock($product_id) : 0;
    $estado = get_post_meta($product_id, '_merc_producto_estado', true) ?: 'sin_asignar';
    $dimensiones = get_post_meta($product_id, '_merc_producto_dimensiones', true);
    
    $datos = array(
        'id' => $product_id,
        'nombre' => $producto->post_title,
        'codigo_barras' => get_post_meta($product_id, '_merc_producto_codigo_barras', true),
        'cliente_asignado' => (string)$cliente_meta, // Convertir a string para consistencia
        'cantidad' => !empty($c) ? intval($c) : 0,
        'estado' => $estado,
        'peso' => floatval(get_post_meta($product_id, '_merc_producto_peso', true) ?: 0),
        'tipo_medida' => get_post_meta($product_id, '_merc_producto_tipo_medida', true) ?: '',
        'valor_medida' => get_post_meta($product_id, '_merc_producto_valor_medida', true) ?: '',
        'largo' => $dimensiones['largo'] ?? 0,
        'ancho' => $dimensiones['ancho'] ?? 0,
        'alto' => $dimensiones['alto'] ?? 0
    );
        while ( ob_get_level() > 0 ) { ob_end_clean(); }    wp_send_json_success($datos);
}

// AJAX handler para actualizar un producto
add_action('wp_ajax_merc_actualizar_producto', 'merc_actualizar_producto');
function merc_actualizar_producto() {
    // Validar nonce si está presente
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            while ( ob_get_level() > 0 ) { ob_end_clean(); }
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
    }
    
    $current_user = wp_get_current_user();
    if (!current_user_can('manage_options')) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'No tienes permisos para editar productos']);
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'ID de producto inválido']);
    }
    
    $producto = get_post($product_id);
    if (!$producto || $producto->post_type !== 'merc_producto') {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'Producto no encontrado']);
    }
    
    // Actualizar post
    $nombre = sanitize_text_field($_POST['nombre'] ?? '');
    wp_update_post(array(
        'ID' => $product_id,
        'post_title' => $nombre
    ));
    
    // Actualizar metadatos
    $codigo_barras = sanitize_text_field($_POST['codigo_barras'] ?? '');
    if (!empty($codigo_barras)) {
        update_post_meta($product_id, '_merc_producto_codigo_barras', $codigo_barras);
    }
    
    $cliente_asignado = intval($_POST['cliente_asignado'] ?? 0);
    if ($cliente_asignado > 0) {
        update_post_meta($product_id, '_merc_producto_cliente_asignado', (string)$cliente_asignado);
    } else {
        delete_post_meta($product_id, '_merc_producto_cliente_asignado');
    }
    
    $estado = sanitize_text_field($_POST['estado'] ?? 'sin_asignar');
    update_post_meta($product_id, '_merc_producto_estado', $estado);
    
    $peso = floatval($_POST['peso'] ?? 0);
    if ($peso > 0) {
        update_post_meta($product_id, '_merc_producto_peso', $peso);
    }
    
    $tipo_medida = sanitize_text_field($_POST['tipo_medida'] ?? '');
    if (!empty($tipo_medida)) {
        update_post_meta($product_id, '_merc_producto_tipo_medida', $tipo_medida);
    } else {
        delete_post_meta($product_id, '_merc_producto_tipo_medida');
    }
    
    $valor_medida = sanitize_text_field($_POST['valor_medida'] ?? '');
    if (!empty($valor_medida)) {
        update_post_meta($product_id, '_merc_producto_valor_medida', $valor_medida);
    } else {
        delete_post_meta($product_id, '_merc_producto_valor_medida');
    }
    
    $largo = floatval($_POST['largo'] ?? 0);
    $ancho = floatval($_POST['ancho'] ?? 0);
    $alto = floatval($_POST['alto'] ?? 0);
    if ($largo > 0 || $ancho > 0 || $alto > 0) {
        $dimensiones = array('largo' => $largo, 'ancho' => $ancho, 'alto' => $alto);
        update_post_meta($product_id, '_merc_producto_dimensiones', $dimensiones);
    }
    
    $cantidad = intval($_POST['cantidad'] ?? 0);
    if (function_exists('merc_set_product_stock')) {
        merc_set_product_stock($product_id, $cantidad, $codigo_barras);
    }
    
    error_log("✏️ Producto actualizado: #{$product_id} - {$nombre}");
    
    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    wp_send_json_success(['message' => 'Producto actualizado correctamente']);
}

// AJAX handler para eliminar un producto
add_action('wp_ajax_merc_eliminar_producto', 'merc_eliminar_producto');
function merc_eliminar_producto() {
    // Validar nonce si está presente
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            while ( ob_get_level() > 0 ) { ob_end_clean(); }
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
    }
    
    $current_user = wp_get_current_user();
    if (!current_user_can('manage_options')) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'No tienes permisos para eliminar productos']);
    }
    
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'ID de producto inválido']);
    }
    
    $producto = get_post($product_id);
    if (!$producto || $producto->post_type !== 'merc_producto') {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'Producto no encontrado']);
    }
    
    $nombre = $producto->post_title;
    wp_delete_post($product_id, true); // true = bypass trash
    
    error_log("🗑️ Producto eliminado: #{$product_id} - {$nombre}");
    
    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    wp_send_json_success(['message' => 'Producto eliminado correctamente']);
}

// AJAX: Obtener unidades de stock por producto
add_action('wp_ajax_merc_get_product_units', 'merc_get_product_units_ajax');
function merc_get_product_units_ajax() {
    // Validar nonce si está presente
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'merc_almacen')) {
            while ( ob_get_level() > 0 ) { ob_end_clean(); }
            wp_send_json_error(['message' => 'Nonce inválido']);
        }
    }

    $current_user = wp_get_current_user();
    $is_admin = current_user_can('manage_options');
    $is_client = in_array('wpcargo_client', (array)$current_user->roles);
    
    if (!is_user_logged_in() || (!$is_admin && !$is_client)) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'No autorizado']);
    }

    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'ID de producto inválido']);
    }

    global $wpdb;
    $table = merc_get_stock_table_name();
    
    // Obtener unidades de stock
    $rows = $wpdb->get_results(
        $wpdb->prepare("SELECT id, sku, status, shipment_id, created_at FROM {$table} WHERE product_id = %d ORDER BY id ASC", $product_id),
        ARRAY_A
    );

    if (empty($rows)) {
        while ( ob_get_level() > 0 ) { ob_end_clean(); }
        wp_send_json_error(['message' => 'El producto "' . get_the_title($product_id) . '" no tiene unidades registradas.']);
    }

    // Agregar información del motorizado y tracking para cada unidad
    foreach ($rows as &$row) {
        $status = strtolower(trim((string)($row['status'] ?? 'available')));
        $effective_status = $status_raw;
        $row['motorizado'] = '-';
        $row['tracking'] = '-';
        
        if (!empty($row['shipment_id'])) {
            $shipment_id = intval($row['shipment_id']);
            
            // Obtener el número de tracking del envío
            $shipment = get_post($shipment_id);
            if ($shipment && $shipment->post_type === 'wpcargo_shipment') {
                $row['tracking'] = $shipment->post_title;
            }
            
            // Obtener el motorizado del envío
            $motorizado_recojo = get_post_meta($shipment_id, 'wpcargo_motorizo_recojo', true);
            $motorizado_entrega = get_post_meta($shipment_id, 'wpcargo_motorizo_entrega', true);
            $motorizado_general = get_post_meta($shipment_id, 'wpcargo_driver', true);
            
            // Prioridad: entrega > recojo > general
            $motorizado_id = $motorizado_entrega ?: ($motorizado_recojo ?: $motorizado_general);
            
            if (!empty($motorizado_id)) {
                $motorizado_user = get_user_by('id', intval($motorizado_id));
                if ($motorizado_user) {
                    $row['motorizado'] = $motorizado_user->display_name;
                    $row['motorizado_id'] = $motorizado_user->ID;
                }
            }

            // Si la unidad ya está dentro de un envío, no debe seguir apareciendo como disponible.
            if (in_array($status_raw, array('', 'available', 'disponible', 'sin_asignar'), true)) {
                $effective_status = 'assigned';

                $wpdb->update(
                    $table,
                    array(
                        'status' => 'assigned',
                        'updated_at' => current_time('mysql'),
                    ),
                    array(
                        'id' => intval($row['id']),
                    ),
                    array('%s', '%s'),
                    array('%d')
                );
            }
        }

        $row['status'] = $status;
        $row['status_effective'] = $effective_status;
        $row['estado'] = $status;
    }

    while ( ob_get_level() > 0 ) { ob_end_clean(); }
    wp_send_json_success($rows);
}




