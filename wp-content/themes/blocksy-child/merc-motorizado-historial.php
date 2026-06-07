<?php
/**
 * Historial de Cambios de Motorizados
 * 
 * Sistema de seguimiento para registrar cada cambio de motorizado de recojo/entrega
 * Integrado con la limpieza diaria y cambios manuales
 */

/**
 * Mostrar historial en el dashboard frontend (cuando se edita un envío)
 */
add_action('after_wpcfe_shipment_form_fields', 'merc_show_motorizado_history_frontend', 50);
function merc_show_motorizado_history_frontend($post_id) {
    // Obtener el ID del envío desde GET (si estamos editando)
    if (!isset($_GET['id'])) {
        return;
    }
    
    $shipment_id = intval($_GET['id']);
    if ($shipment_id <= 0) {
        return;
    }
    
    $historia = get_post_meta($shipment_id, 'merc_motorizado_historia', true);
    
    if (!is_array($historia) || empty($historia)) {
        return;
    }
    
    // Invertir el array para mostrar el más reciente primero
    $historia = array_reverse($historia);
    
    ?>
    <div class="col-md-12 mb-4">
        <div class="card">
            <section class="card-header">
                📊 Historial de Motorizados
            </section>
            <section class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead style="background: #f5f5f5;">
                            <tr>
                                <th>Hora</th>
                                <th>Tipo</th>
                                <th>De</th>
                                <th>A</th>
                                <th>Razón</th>
                                <th>Usuario</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($historia as $index => $entrada) : ?>
                                <tr>
                                    <td>
                                        <small><?php echo esc_html(substr($entrada['timestamp'], 11)); ?></small><br>
                                        <small style="color: #999;"><?php echo esc_html(substr($entrada['timestamp'], 0, 10)); ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $tipo_label = $entrada['tipo'] === 'recojo' ? '🚚 Recojo' : '📦 Entrega';
                                        echo esc_html($tipo_label);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($entrada['de'] === 0 || empty($entrada['de'])) {
                                            echo '<em style="color: #999;">Vacío</em>';
                                        } else {
                                            $motorizado_anterior = get_userdata($entrada['de']);
                                            echo esc_html($motorizado_anterior ? $motorizado_anterior->display_name : 'Motorizado #' . $entrada['de']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if ($entrada['a'] === 0 || empty($entrada['a'])) {
                                            echo '<em style="color: #999;">Vacío</em>';
                                        } else {
                                            $motorizado_nuevo = get_userdata($entrada['a']);
                                            echo esc_html($motorizado_nuevo ? $motorizado_nuevo->display_name : 'Motorizado #' . $entrada['a']);
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        $razones = [
                                            'manual' => '✏️ Manual',
                                            'cambio_manual' => '✏️ Manual',
                                            'limpieza_diaria_reprogramacion' => '🔄 Limpieza Diaria',
                                            'auto_asignacion' => '⚙️ Auto Asignación'
                                        ];
                                        $razon_label = isset($razones[$entrada['razon']]) ? $razones[$entrada['razon']] : $entrada['razon'];
                                        echo esc_html($razon_label);
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        if (!empty($entrada['usuario'])) {
                                            $usuario = get_userdata($entrada['usuario']);
                                            echo esc_html($usuario ? $usuario->display_name : 'Usuario #' . $entrada['usuario']);
                                        } else {
                                            echo '<em style="color: #999;">Sistema</em>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="margin-top: 15px; padding: 10px; background: #e8f4f8; border-left: 4px solid #0099cc; font-size: 12px; border-radius: 3px;">
                    <strong>📈 Resumen de cambios:</strong><br>
                    Total: <strong><?php echo count($historia); ?></strong> |
                    <?php
                    $recojo_count = count(array_filter($historia, function($h) { return $h['tipo'] === 'recojo'; }));
                    $entrega_count = count(array_filter($historia, function($h) { return $h['tipo'] === 'entrega'; }));
                    ?>
                    🚚 Recojo: <strong><?php echo $recojo_count; ?></strong> |
                    📦 Entrega: <strong><?php echo $entrega_count; ?></strong>
                </div>
            </section>
        </div>
    </div>
    <?php
}

/**
 * Meta Box: Mostrar historial de motorizados en la página de edición del envío (WordPress Admin)
 */
add_action('add_meta_boxes', 'merc_add_motorizado_history_metabox');
function merc_add_motorizado_history_metabox() {
    add_meta_box(
        'merc_motorizado_history',
        '📊 Historial de Motorizados',
        'merc_motorizado_history_callback',
        'wpcargo_shipment',
        'normal',
        'low'
    );
}

function merc_motorizado_history_callback($post) {
    $historia = get_post_meta($post->ID, 'merc_motorizado_historia', true);
    
    if (!is_array($historia) || empty($historia)) {
        echo '<p style="color: #666; font-style: italic;">No hay cambios registrados aún.</p>';
        return;
    }
    
    // Invertir el array para mostrar el más reciente primero
    $historia = array_reverse($historia);
    
    ?>
    <table style="width:100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">Hora</th>
                <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">Tipo</th>
                <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">De</th>
                <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">A</th>
                <th style="padding: 10px; text-align: left; border-right: 1px solid #ddd;">Razón</th>
                <th style="padding: 10px; text-align: left;">Usuario</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historia as $index => $entrada) : ?>
                <tr style="border-bottom: 1px solid #eee; <?php echo $index % 2 === 0 ? 'background: #fafafa;' : ''; ?>">
                    <td style="padding: 10px; border-right: 1px solid #eee;">
                        <strong><?php echo esc_html($entrada['timestamp']); ?></strong>
                    </td>
                    <td style="padding: 10px; border-right: 1px solid #eee;">
                        <?php 
                        $tipo_label = $entrada['tipo'] === 'recojo' ? '🚚 Recojo' : '📦 Entrega';
                        echo esc_html($tipo_label);
                        ?>
                    </td>
                    <td style="padding: 10px; border-right: 1px solid #eee;">
                        <?php 
                        if ($entrada['de'] === 0 || empty($entrada['de'])) {
                            echo '<em style="color: #999;">Vacío</em>';
                        } else {
                            $motorizado_anterior = get_userdata($entrada['de']);
                            echo esc_html($motorizado_anterior ? $motorizado_anterior->display_name : 'Motorizado #' . $entrada['de']);
                        }
                        ?>
                    </td>
                    <td style="padding: 10px; border-right: 1px solid #eee;">
                        <?php 
                        if ($entrada['a'] === 0 || empty($entrada['a'])) {
                            echo '<em style="color: #999;">Vacío</em>';
                        } else {
                            $motorizado_nuevo = get_userdata($entrada['a']);
                            echo esc_html($motorizado_nuevo ? $motorizado_nuevo->display_name : 'Motorizado #' . $entrada['a']);
                        }
                        ?>
                    </td>
                    <td style="padding: 10px; border-right: 1px solid #eee;">
                        <?php
                        $razones = [
                            'manual' => '✏️ Manual',
                            'cambio_manual' => '✏️ Manual',
                            'limpieza_diaria_reprogramacion' => '🔄 Limpieza Diaria',
                            'auto_asignacion' => '⚙️ Auto Asignación'
                        ];
                        $razon_label = isset($razones[$entrada['razon']]) ? $razones[$entrada['razon']] : $entrada['razon'];
                        echo esc_html($razon_label);
                        ?>
                    </td>
                    <td style="padding: 10px;">
                        <?php 
                        if (!empty($entrada['usuario'])) {
                            $usuario = get_userdata($entrada['usuario']);
                            echo esc_html($usuario ? $usuario->display_name : 'Usuario #' . $entrada['usuario']);
                        } else {
                            echo '<em style="color: #999;">Sistema</em>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div style="margin-top: 15px; padding: 10px; background: #e8f4f8; border-left: 4px solid #0099cc; font-size: 13px;">
        <strong>📈 Resumen:</strong><br>
        Total de cambios: <strong><?php echo count($historia); ?></strong><br>
        <?php
        $recojo_count = count(array_filter($historia, function($h) { return $h['tipo'] === 'recojo'; }));
        $entrega_count = count(array_filter($historia, function($h) { return $h['tipo'] === 'entrega'; }));
        ?>
        • Recojo: <strong><?php echo $recojo_count; ?></strong> cambios<br>
        • Entrega: <strong><?php echo $entrega_count; ?></strong> cambios
    </div>
    <?php
}

/**
 * Panel de Admin: Mostrar historial de motorizados de todos los envíos
 * Se agrega como una nueva página de admin
 */
add_action('admin_menu', 'merc_add_motorizado_history_admin_page');
function merc_add_motorizado_history_admin_page() {
    add_submenu_page(
        'wpcargo_shipments',
        'Historial de Motorizados',
        'Historial Motorizados',
        'manage_options',
        'merc-motorizado-history',
        'merc_motorizado_history_admin_page'
    );
}

function merc_motorizado_history_admin_page() {
    if (!current_user_can('manage_options')) {
        wp_die('Acceso denegado');
    }
    
    global $wpdb;
    
    // Obtener envíos con historial
    $shipments_with_history = $wpdb->get_results(
        "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
        WHERE meta_key = 'merc_motorizado_historia'
        ORDER BY post_id DESC
        LIMIT 100"
    );
    
    ?>
    <div class="wrap">
        <h1>📊 Historial de Cambios de Motorizados</h1>
        <p>Visualiza el historial de cambios de motorizados de recojo y entrega de todos los envíos:</p>
        
        <table class="widefat striped">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Remitente</th>
                    <th>Estado</th>
                    <th style="width: 150px;">Cambios Recojo</th>
                    <th style="width: 150px;">Cambios Entrega</th>
                    <th style="width: 100px;">Total Cambios</th>
                    <th style="width: 150px;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($shipments_with_history as $row) : 
                    $shipment_id = $row->post_id;
                    $shipment = get_post($shipment_id);
                    $historia = get_post_meta($shipment_id, 'merc_motorizado_historia', true);
                    
                    if (!is_array($historia) || empty($historia)) {
                        continue;
                    }
                    
                    $shipper_id = get_post_meta($shipment_id, 'registered_shipper', true);
                    $shipper = get_userdata($shipper_id);
                    $estado = get_post_meta($shipment_id, 'wpcargo_status', true);
                    
                    $recojo_count = count(array_filter($historia, function($h) { return $h['tipo'] === 'recojo'; }));
                    $entrega_count = count(array_filter($historia, function($h) { return $h['tipo'] === 'entrega'; }));
                    $total_count = count($historia);
                    
                    $colores_tipo = ['recojo' => '#FF9800', 'entrega' => '#2196F3'];
                    ?>
                    <tr>
                        <td>
                            <strong>#<?php echo esc_html($shipment_id); ?></strong>
                        </td>
                        <td>
                            <?php 
                            if ($shipper) {
                                $company = get_user_meta($shipper_id, 'billing_company', true);
                                if ($company) {
                                    echo esc_html($company . ' (' . trim($shipper->first_name . ' ' . $shipper->last_name) . ')');
                                } else {
                                    echo esc_html(trim($shipper->first_name . ' ' . $shipper->last_name) ?: $shipper->display_name);
                                }
                            } else {
                                echo 'N/A';
                            }
                            ?>
                        </td>
                        <td>
                            <span style="padding: 4px 8px; background: #f0f0f0; border-radius: 3px;">
                                <?php echo esc_html($estado ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($recojo_count > 0) : ?>
                                <span style="padding: 4px 8px; background: <?php echo $colores_tipo['recojo']; ?>; color: white; border-radius: 3px; display: inline-block;">
                                    🚚 <?php echo $recojo_count; ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <?php if ($entrega_count > 0) : ?>
                                <span style="padding: 4px 8px; background: <?php echo $colores_tipo['entrega']; ?>; color: white; border-radius: 3px; display: inline-block;">
                                    📦 <?php echo $entrega_count; ?>
                                </span>
                            <?php else : ?>
                                <span style="color: #999;">—</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: center;">
                            <strong><?php echo $total_count; ?></strong>
                        </td>
                        <td>
                            <a href="<?php echo esc_url(admin_url('post.php?post=' . $shipment_id . '&action=edit')); ?>" class="button button-small">
                                Ver Detalle
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <?php if (empty($shipments_with_history)) : ?>
            <div style="margin-top: 20px; padding: 20px; background: #f5f5f5; border-left: 4px solid #999;">
                <p style="margin: 0;">ℹ️ No hay registros de cambios de motorizados aún.</p>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

