<?php
/**
 * AJAX Endpoints
 */

if (!defined('ABSPATH')) {
    exit;
}

class Merc_Bloqueos_Ajax {

    public function __construct() {
        add_action('wp_ajax_merc_bloqueo_info', [$this, 'get_bloqueo_info']);
        add_action('wp_ajax_nopriv_merc_bloqueo_info', [$this, 'get_bloqueo_info']);
    }

    public function get_bloqueo_info() {
        $tipo = isset($_POST['tipo']) ? sanitize_text_field($_POST['tipo']) : 'normal';
        $client_id = get_current_user_id();

        if (!$client_id) {
            wp_send_json_error(['message' => 'Usuario no logueado']);
        }

        // Evaluar reglas de bloqueo
        $result = Merc_Bloqueos_Logic::evaluate($client_id, $tipo);

        // Obtener fechas especiales y domingos configurados
        $fechas = get_option('merc_bloqueos_fechas_especiales', []);
        $domingos = get_option('merc_bloqueos_domingos_desbloqueados', []);

        $response = [
            'bloqueo_total' => $result['bloqueo_total'],
            'bloquear_hoy' => $result['bloquear_hoy'],
            'fechas_bloqueadas' => is_array($fechas) ? $fechas : [],
            'domingos_desbloqueados' => is_array($domingos) ? $domingos : [],
            '_debug_reason' => $result['reason']
        ];

        wp_send_json_success($response);
    }
}
