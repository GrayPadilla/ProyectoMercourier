<?php
if (!defined('ABSPATH')) exit;

// AJAX: cliente sube voucher para pagar una penalidad y crear una entrada de liquidación
add_action('wp_ajax_merc_cliente_pagar_penalty', 'merc_cliente_pagar_penalty_ajax');
function merc_cliente_pagar_penalty_ajax() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'merc_cliente_pagar' ) ) {
        wp_send_json_error(array('message'=>'Nonce inválido'));
    }
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    if ( $user_id <= 0 ) wp_send_json_error(array('message'=>'Usuario inválido'));
    $current = wp_get_current_user();
    if ( $current->ID !== $user_id && ! current_user_can('administrator') ) {
        wp_send_json_error(array('message'=>'Sin permisos'));
    }

    $penalty_id = isset($_POST['penalty_id']) ? intval($_POST['penalty_id']) : 0;
    if ( $penalty_id <= 0 ) wp_send_json_error(array('message'=>'Penalidad inválida'));

    $amount = floatval( get_post_meta($penalty_id, 'amount', true) );
    if ( $amount <= 0 ) wp_send_json_error(array('message'=>'Monto inválido'));

    if ( empty($_FILES) || empty($_FILES['voucher']) ) {
        wp_send_json_error(array('message'=>'Debes adjuntar un comprobante (imagen).'));
    }

    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $file = $_FILES['voucher'];
    $overrides = array( 'test_form' => false );
    $movefile = wp_handle_upload( $file, $overrides );
    if ( isset( $movefile['error'] ) ) {
        wp_send_json_error( array( 'message' => 'Error al subir comprobante: ' . $movefile['error'] ) );
    }

    $filename = $movefile['file'];
    $filetype = wp_check_filetype( basename( $filename ), null );
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_file_name( basename( $filename ) ),
        'post_content'   => '',
        'post_status'    => 'inherit'
    );
    $attachment_id = wp_insert_attachment( $attachment, $filename );
    if ( ! is_wp_error( $attachment_id ) ) {
        $attach_data = wp_generate_attachment_metadata( $attachment_id, $filename );
        wp_update_attachment_metadata( $attachment_id, $attach_data );
    }

    // Crear entrada de liquidación en user meta
    $liquidation = array(
        'id' => uniqid('liq_pen_'),
        'date' => current_time('mysql'),
        'action' => 'penalty_payment',
        'amount' => round($amount,2),
        'attachment_id' => $attachment_id,
        'penalty_id' => $penalty_id,
    );

    $history = get_user_meta( $user_id, 'merc_liquidations', true );
    if ( ! is_array( $history ) ) $history = array();
    $history[] = $liquidation;
    update_user_meta( $user_id, 'merc_liquidations', $history );

    // Marcar penalidad como pending y referenciar la liquidación
    update_post_meta( $penalty_id, 'status', 'pending' );
    update_post_meta( $penalty_id, 'payment_ref_liquidation', $liquidation['id'] );

    wp_send_json_success(array('message'=>'Pago registrado y pendiente de verificación (S/. ' . number_format($amount,2) . ')'));
}

// AJAX: Verificar liquidación (admin) — aplica la liquidación y marca envíos
add_action('wp_ajax_merc_verify_liquidation', 'merc_verify_liquidation_ajax');
function merc_verify_liquidation_ajax() {
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce( $_POST['nonce'], 'merc_verify' ) ) {
        wp_send_json_error(array('message'=>'Nonce inválido'));
    }
    if ( ! current_user_can('administrator') ) {
        wp_send_json_error(array('message'=>'Sin permisos'));
    }
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $liq_id = isset($_POST['liq_id']) ? sanitize_text_field($_POST['liq_id']) : '';
    if ( $user_id <= 0 || empty($liq_id) ) wp_send_json_error(array('message'=>'Datos inválidos'));

    $history = get_user_meta( $user_id, 'merc_liquidations', true );
    if ( ! is_array($history) ) wp_send_json_error(array('message'=>'No hay historial'));

    $found = false;
    foreach ( $history as $i => $entry ) {
        if ( isset($entry['id']) && $entry['id'] === $liq_id ) {
            $found = true;
            if ( isset($entry['verified']) && $entry['verified'] ) {
                wp_send_json_error(array('message'=>'Ya verificado'));
            }

            // Aplicar: marcar envíos incluidos y servicios cobrados
            $shipments = isset($entry['shipments']) && is_array($entry['shipments']) ? $entry['shipments'] : array();
            foreach ( $shipments as $shipment_id ) {
                update_post_meta( $shipment_id, 'wpcargo_servicio_cobrado', 'si' );
                update_post_meta( $shipment_id, 'wpcargo_included_in_liquidation', $liq_id );
                update_post_meta( $shipment_id, 'wpcargo_fecha_liquidacion_remitente', current_time('mysql') );
            }

            // Marcar entry como verificada
            $history[$i]['verified'] = true;
            $history[$i]['verified_by'] = get_current_user_id();
            $history[$i]['verified_date'] = current_time('mysql');
            
            // Si la entrada corresponde a una penalidad, marcarla como pagada
            if ( isset($entry['penalty_id']) && ! empty($entry['penalty_id']) ) {
                $pen_id = intval($entry['penalty_id']);
                if ( $pen_id > 0 ) {
                    update_post_meta($pen_id, 'status', 'paid');
                    update_post_meta($pen_id, 'paid_at', current_time('mysql'));
                    update_post_meta($pen_id, 'payment_ref_liquidation', $liq_id);
                }
            }

            update_user_meta( $user_id, 'merc_liquidations', $history );
            wp_send_json_success(array('message'=>'Liquidación verificada y aplicada.'));
        }
    }
    if ( ! $found ) wp_send_json_error(array('message'=>'Liquidación no encontrada'));
}

// AJAX handlers para finanzas adicionales
add_action('wp_ajax_merc_finance_get_summary', 'merc_finance_get_summary');
function merc_finance_get_summary() {
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'No autorizado']);
    }
    wp_send_json_success(['summary' => []]);
}
?>

