<?php
/**
 * Plugin Name: Mercourier Fix Dates
 * Description: Corrige las fechas vacías. Ejecutar con ?merc_fix_dates=1
 */

add_action('init', function() {
    if (isset($_GET['merc_debug_logic']) && $_GET['merc_debug_logic'] === '1') {
        if (!current_user_can('manage_options')) wp_die('Admin only');
        
        global $wpdb;
        echo "<h2>Debug Pedidos de Hoy</h2>";
        $client_id = get_current_user_id();
        $today_iso = wp_date('Y-m-d');
        $today_dmy = wp_date('d/m/Y');
        
        $shipments = $wpdb->get_results($wpdb->prepare("
            SELECT p.ID, pm_type.meta_value as tipo, pm_date.meta_value as fecha, pm_status.meta_value as estado
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm_shipper ON p.ID = pm_shipper.post_id AND pm_shipper.meta_key = 'registered_shipper'
            LEFT JOIN {$wpdb->postmeta} pm_type ON p.ID = pm_type.post_id AND pm_type.meta_key = 'wpcargo_type_of_shipment'
            LEFT JOIN {$wpdb->postmeta} pm_date ON p.ID = pm_date.post_id AND pm_date.meta_key = 'wpcargo_pickup_date_picker'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = 'wpcargo_status'
            WHERE p.post_type = 'wpcargo_shipment'
            AND pm_shipper.meta_value = %d
            AND (pm_date.meta_value = %s OR pm_date.meta_value = %s)
        ", $client_id, $today_iso, $today_dmy));

        echo "Pedidos encontrados hoy (" . count($shipments) . "):<table border=1><tr><th>ID</th><th>Tipo</th><th>Fecha DB</th><th>Estado</th></tr>";
        foreach($shipments as $s) {
            echo "<tr><td>{$s->ID}</td><td>{$s->tipo}</td><td>{$s->fecha}</td><td>{$s->estado}</td></tr>";
        }
        echo "</table>";

        die();
    }

    if (isset($_GET['merc_fix_dates']) && $_GET['merc_fix_dates'] === '1') {
        // Solo administradores
        if (!current_user_can('manage_options')) {
            wp_die('Acceso denegado. Debes ser administrador.');
        }

        global $wpdb;
        $meta_key = 'wpcargo_pickup_date_picker';
        $new_date = '2026-04-27';

        $results = $wpdb->get_results("
            SELECT p.ID FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '{$meta_key}'
            WHERE p.post_type = 'wpcargo_shipment'
            AND p.post_status NOT IN ('trash', 'auto-draft')
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");

        echo "<h2>Actualizando " . count($results) . " pedidos...</h2>";
        $count = 0;
        foreach ($results as $row) {
            update_post_meta($row->ID, $meta_key, $new_date);
            echo "Pedido #{$row->ID} corregido.<br>";
            $count++;
        }
        echo "<h3>Proceso terminado. Total: $count</h3>";
        echo "<p style='color:red;'><b>RECUERDA ELIMINAR ESTE ARCHIVO:</b> wp-content/mu-plugins/merc-fix-dates.php</p>";
        die();
    }
});

