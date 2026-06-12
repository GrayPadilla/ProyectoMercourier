<?php
/**
 * Módulo de Reprogramación de Envíos
 * 
 * Maneja:
 * 1. Marcar un envío como REPROGRAMADO (es_reprogramado = 1)
 * 2. Verificar automáticamente si llegó la fecha y cambiar a RECEPCIONADO
 * 3. Pintar las filas de NARANJA cuando están reprogramadas
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Hook: cuando el estado cambia a REPROGRAMADO
 * Activa la bandera es_reprogramado = 1
 */
add_action('update_post_meta_wpcargo_status', function($meta_id, $object_id, $meta_key, $meta_value) {
    if ($meta_value === 'REPROGRAMADO') {
        error_log("🟠 REPROG: Envío $object_id cambió a REPROGRAMADO - Activando bandera es_reprogramado");
        update_post_meta($object_id, 'es_reprogramado', 1);
    } elseif (in_array($meta_value, array('ENTREGADO', 'ANULADO', 'NO RECIBIDO'), true)) {
        // Limpiar franja naranja al llegar a un estado final
        error_log("🟠 REPROG: Envío $object_id llegó a estado final ($meta_value) - Limpiando bandera es_reprogramado");
        update_post_meta($object_id, 'es_reprogramado', 0);
    }
}, 10, 4);

/**
 * Limpiar cualquier cron legado de reprogramación.
 *
 * La reprogramación ya se resuelve al confirmar el cambio, por lo que no
 * debemos dejar tareas programadas que alteren el estado después.
 */
add_action('init', function() {
    if (wp_next_scheduled('merc_reprogramacion_check_cron')) {
        wp_clear_scheduled_hook('merc_reprogramacion_check_cron');
        error_log('🟠 REPROG: Cron legado merc_reprogramacion_check_cron eliminado');
    }
});

/**
 * Limpiar cron al desactivar plugin
 */
register_deactivation_hook(MERC_RETURNS_FILE, function() {
    wp_clear_scheduled_hook('merc_reprogramacion_check_cron');
});

