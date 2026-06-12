<?php
/**
 * AJAX Handlers - Merc Returns
 */

if (!defined('ABSPATH')) exit;

/**
 * AJAX: Guardar estado de entrega
 */
add_action('wp_ajax_merc_save_estado_entrega',        'merc_save_estado_entrega');
add_action('wp_ajax_nopriv_merc_save_estado_entrega', 'merc_save_estado_entrega');
function merc_save_estado_entrega() {
    if (!merc_user_can_view_devoluciones()) {
        wp_send_json_error(array('message' => 'No autorizado'));
    }

    $post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
    $valor   = isset($_POST['valor'])   ? sanitize_text_field($_POST['valor']) : '';

    if (!$post_id || !in_array($valor, array('Entregado', 'No Entregado', ''), true)) {
        wp_send_json_error(array('message' => 'Datos inválidos'));
    }

    if ($valor === '') {
        delete_post_meta($post_id, 'merc_estado_entrega');
    } else {
        update_post_meta($post_id, 'merc_estado_entrega', $valor);
    }

    wp_send_json_success(array('post_id' => $post_id, 'valor' => $valor));
}

/**
 * AJAX: Exportar CSV
 */
add_action('wp_ajax_merc_export_csv',        'merc_handle_export');
add_action('wp_ajax_nopriv_merc_export_csv', 'merc_handle_export');
function merc_handle_export() {
    if (!merc_user_can_view_devoluciones()) wp_die('No autorizado');

    $args = merc_build_query(
        isset($_GET['estado'])     ? sanitize_text_field($_GET['estado'])     : '',
        isset($_GET['marca'])      ? sanitize_text_field($_GET['marca'])      : '',
        isset($_GET['motorizado']) ? absint($_GET['motorizado'])              : 0,
        isset($_GET['desde'])      ? sanitize_text_field($_GET['desde'])      : '',
        isset($_GET['hasta'])      ? sanitize_text_field($_GET['hasta'])      : '',
        isset($_GET['buscar'])     ? sanitize_text_field($_GET['buscar'])     : ''
    );
    $args['posts_per_page'] = -1;
    $q = new WP_Query($args);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=devoluciones_' . date('Y-m-d_H-i-s') . '.csv');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, array('Fecha','Tracking','Cliente','Estado','Marca','Motorizado','Cambio de Producto','Estado Entrega','Dirección','Teléfono'));

    if ($q->have_posts()) {
        foreach ($q->posts as $post) {
            $post_id        = $post->ID;
            $fecha          = get_post_meta($post_id, 'wpcargo_pickup_date_picker', true);
            $fecha_f        = $fecha ? date('d/m/Y', strtotime($fecha)) : '';
            $tracking       = get_post_meta($post_id, 'wpcargo_tracking_number', true);
            $cliente        = get_post_meta($post_id, 'wpcargo_receiver_name', true);
            $estado_envio   = get_post_meta($post_id, 'wpcargo_status', true);
            $marca_nombre   = get_post_meta($post_id, 'wpcargo_tiendaname', true);
            $direccion      = get_post_meta($post_id, 'wpcargo_receiver_address', true);
            $telefono       = get_post_meta($post_id, 'wpcargo_receiver_phone', true);
            $driver         = get_post_meta($post_id, 'wpcargo_driver', true);
            $driver_name    = ($driver && get_userdata($driver)) ? get_userdata($driver)->display_name : 'No asignado';
            $cambio_producto = get_post_meta($post_id, 'cambio_producto', true);
            $estado_entrega  = get_post_meta($post_id, 'merc_estado_entrega', true);

            fputcsv($output, array(
                $fecha_f,
                $tracking,
                $cliente,
                $estado_envio,
                $marca_nombre,
                $driver_name,
                $cambio_producto === 'Sí' ? 'SÍ' : '',
                $estado_entrega ?: '',
                $direccion,
                $telefono
            ));
        }
    }

    fclose($output);
    exit;
}
