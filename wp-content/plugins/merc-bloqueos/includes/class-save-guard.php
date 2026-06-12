<?php
/**
 * Validación Server-Side para evitar saltarse el bloqueo
 */

if (!defined('ABSPATH')) {
    exit;
}

class Merc_Bloqueos_Save_Guard {

    public function __construct() {
        // Nos enganchamos en 'wp' antes que wpcfe_add_shipment (que usa prioridad 10)
        add_action('wp', [$this, 'validate_shipment_save'], 9);
    }

    public function validate_shipment_save() {
        // Verificar si es una petición de guardado de WPCargo (Nuevo o Edición)
        $is_add = isset($_POST['wpcfe_add_form_fields']) && wp_verify_nonce($_POST['wpcfe_add_form_fields'], 'wpcfe_add_action');
        $is_edit = isset($_POST['wpcfe_form_fields']) && wp_verify_nonce($_POST['wpcfe_form_fields'], 'wpcfe_edit_action');

        if (!$is_add && !$is_edit) {
            return;
        }

        // Determinar cliente
        $client_id = get_current_user_id();

        // Si el usuario es administrador, no lo bloqueamos
        if (current_user_can('manage_options')) {
            return;
        }

        // Obtener la fecha seleccionada en el formulario
        $selected_date_raw = isset($_POST['wpcargo_pickup_date_picker']) ? sanitize_text_field($_POST['wpcargo_pickup_date_picker']) : '';
        $tipo = isset($_POST['tipo_envio']) ? sanitize_text_field($_POST['tipo_envio']) : 'normal';

        if (empty($selected_date_raw)) {
            return; // Si no hay fecha, dejamos que WPCargo maneje sus propias validaciones
        }

        // Convertir la fecha seleccionada a Y-m-d para comparar
        // Asumiendo que el picker devuelve YYYY-MM-DD o un formato parseable.
        $selected_timestamp = strtotime(str_replace('/', '-', $selected_date_raw));
        if (!$selected_timestamp) {
            return;
        }
        $selected_date = date('Y-m-d', $selected_timestamp);
        $today = current_time('Y-m-d');

        // Evaluar reglas generales (Bloqueo total)
        $result = Merc_Bloqueos_Logic::evaluate($client_id, $tipo);

        // 1. Validar Bloqueo Total
        if ($result['bloqueo_total']) {
            $this->abort_save('Tu cuenta se encuentra bloqueada para realizar envíos. Por favor, comunícate con administración.', $is_add);
            return;
        }

        // 2. Validar Fechas Especiales y Domingos (Aplica a cualquier fecha que elijan)
        $fechas_bloqueadas = get_option('merc_bloqueos_fechas_especiales', []);
        $domingos_desbloqueados = get_option('merc_bloqueos_domingos_desbloqueados', []);

        if (in_array($selected_date, (array)$fechas_bloqueadas)) {
            $this->abort_save('La fecha seleccionada es un día festivo y no está disponible para envíos.', $is_add);
            return;
        }

        $dia_semana = date('w', $selected_timestamp); // 0 (para domingo) hasta 6 (para sábado)
        if ($dia_semana == 0 && !in_array($selected_date, (array)$domingos_desbloqueados)) {
            $this->abort_save('Los domingos no hay servicio de envíos.', $is_add);
            return;
        }

        // 3. Validar si seleccionaron HOY estando bloqueados
        if ($selected_date === $today && $result['bloquear_hoy']) {
            $this->abort_save('El horario para envíos el día de hoy ya ha cerrado. Por favor selecciona una fecha posterior.', $is_add);
            return;
        }
    }

    private function abort_save($message, $is_add) {
        // Eliminar las variables POST que desencadenan el guardado en wpcargo-frontend-manager
        if ($is_add) {
            unset($_POST['wpcfe_add_form_fields']);
        } else {
            unset($_POST['wpcfe_form_fields']);
        }

        // Inyectar el error para que la interfaz lo muestre
        $_POST['wpcfe-notification'] = [
            'status'  => 'danger',
            'icon'    => 'exclamation',
            'message' => $message
        ];
    }
}
