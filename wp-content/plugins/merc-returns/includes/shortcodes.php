<?php
/**
 * Shortcodes - Merc Returns
 */

if (!defined('ABSPATH')) exit;

/**
 * Shortcode principal
 */
add_shortcode('merc_devoluciones', function () {
    if (!merc_user_can_view_devoluciones()) return '<p>No autorizado</p>';

    $estado     = isset($_GET['estado'])     ? sanitize_text_field($_GET['estado'])    : '';
    $marca      = isset($_GET['marca'])      ? sanitize_text_field($_GET['marca'])     : '';
    $motorizado = isset($_GET['motorizado']) ? absint($_GET['motorizado'])             : 0;
    $desde      = isset($_GET['desde']) && !empty($_GET['desde']) ? sanitize_text_field($_GET['desde']) : '';
    $hasta      = isset($_GET['hasta']) && !empty($_GET['hasta']) ? sanitize_text_field($_GET['hasta']) : '';
    $buscar     = isset($_GET['buscar'])     ? sanitize_text_field($_GET['buscar'])    : '';

    ob_start();
    ?>
    <div class="wrap merc-devoluciones-wrap" style="max-width:100%;margin:0;">
        <h1 style="margin-bottom:20px;">🔄 Gestión de Devoluciones</h1>

        <div id="merc-stats-container">
            <?php echo merc_render_stats_cards($estado, $marca, $motorizado, $desde, $hasta, $buscar); ?>
        </div>

        <div class="merc-card no-print" style="margin:20px 0;background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
            <h2 style="padding:20px;margin:0;border-bottom:2px solid #e8e8e8;background:linear-gradient(to bottom,#fafafa,#f5f5f5);border-radius:8px 8px 0 0;font-size:18px;">
                🔍 Filtros de Búsqueda
            </h2>
            <div style="padding:25px;">
                <?php echo merc_render_filters_form($estado, $marca, $motorizado, $desde, $hasta, $buscar); ?>
            </div>
        </div>

        <div class="merc-card" style="background:#fff;border:1px solid #ddd;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
            <h2 class="no-print" style="padding:20px;margin:0;border-bottom:2px solid #e8e8e8;background:linear-gradient(to bottom,#fafafa,#f5f5f5);border-radius:8px 8px 0 0;display:flex;justify-content:space-between;align-items:center;font-size:18px;">
                <span>📋 Listado de Devoluciones</span>
                <div><?php echo merc_render_export_buttons($estado, $marca, $motorizado, $desde, $hasta, $buscar); ?></div>
            </h2>
            <div style="padding:25px;overflow-x:auto;" id="merc-table-container">
                <?php echo merc_render_table($estado, $marca, $motorizado, $desde, $hasta, $buscar); ?>
            </div>
        </div>
    </div>

    <link rel="stylesheet" href="<?php echo esc_url(MERC_RETURNS_URL . 'assets/styles.css'); ?>">
    <script src="<?php echo esc_url(MERC_RETURNS_URL . 'assets/scripts.js'); ?>"></script>
    <?php
    return ob_get_clean();
});

/**
 * Renderizar cards de estadísticas
 */
function merc_render_stats_cards($estado, $marca, $motorizado, $desde, $hasta, $buscar) {
    $args = merc_build_query($estado, $marca, $motorizado, $desde, $hasta, $buscar);
    $args['posts_per_page'] = -1;
    $q     = new WP_Query($args);
    $total = (int) $q->found_posts;

    $counts = array('Reprogramado' => 0, 'Anulado' => 0, 'No recibido' => 0, 'Cambio de producto' => 0);

    if ($q->have_posts()) {
        foreach ($q->posts as $post) {
            $s      = get_post_meta($post->ID, 'wpcargo_status', true);
            $cambio = get_post_meta($post->ID, 'cambio_producto', true);
            $s_normalized = merc_normalize_status($s);
            if (isset($counts[$s_normalized])) $counts[$s_normalized]++;
            if ($cambio === 'Sí') $counts['Cambio de producto']++;
        }
    }

    ob_start();
    ?>
    <div class="merc-stats-grid no-print">
        <div class="merc-stat-card" style="border-left-color:#2271b1;">
            <div class="merc-stat-label">📊 Total Devoluciones</div>
            <div class="merc-stat-value" style="color:#2271b1;"><?php echo $total; ?></div>
        </div>
        <div class="merc-stat-card" style="border-left-color:#ff9800;">
            <div class="merc-stat-label">📅 Reprogramados</div>
            <div class="merc-stat-value" style="color:#ff9800;"><?php echo $counts['Reprogramado']; ?></div>
        </div>
        <div class="merc-stat-card" style="border-left-color:#f44336;">
            <div class="merc-stat-label">❌ Anulados</div>
            <div class="merc-stat-value" style="color:#f44336;"><?php echo $counts['Anulado']; ?></div>
        </div>
        <div class="merc-stat-card" style="border-left-color:#9c27b0;">
            <div class="merc-stat-label">📭 No Recibidos</div>
            <div class="merc-stat-value" style="color:#9c27b0;"><?php echo $counts['No recibido']; ?></div>
        </div>
        <div class="merc-stat-card" style="border-left-color:#00796b;">
            <div class="merc-stat-label">🔁 Cambio de Producto</div>
            <div class="merc-stat-value" style="color:#00796b;"><?php echo $counts['Cambio de producto']; ?></div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Renderizar formulario de filtros
 */
function merc_render_filters_form($estado, $marca, $motorizado, $desde, $hasta, $buscar) {
    global $wpdb;
    
    $marcas = $wpdb->get_col("
        SELECT DISTINCT meta_value 
        FROM {$wpdb->usermeta} 
        WHERE meta_key = 'billing_company' 
        AND meta_value != '' 
        ORDER BY meta_value ASC
    ");
    
    $drivers = get_users([
        'role'    => 'wpcargo_driver',
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'fields'  => array('ID', 'display_name'),
    ]);

    $current_url = home_url(add_query_arg(null, null));
    $base_url    = strtok($current_url, '?');

    ob_start();
    ?>
    <form method="get" action="" class="merc-filtros-grid">
        <input type="hidden" name="page_id" value="<?php echo get_queried_object_id(); ?>">
        
        <div class="form-group">
            <label for="buscar">🔍 Buscar por Tracking</label>
            <input type="text" name="buscar" id="buscar" placeholder="Ej: MERC-12345" value="<?php echo esc_attr($buscar); ?>">
        </div>
        
        <div class="form-group">
            <label for="estado">📊 Estado</label>
            <select name="estado" id="estado">
                <option value="">Todos</option>
                <option value="Reprogramado" <?php selected($estado, 'Reprogramado'); ?>>Reprogramado</option>
                <option value="Anulado" <?php selected($estado, 'Anulado'); ?>>Anulado</option>
                <option value="No recibido" <?php selected($estado, 'No recibido'); ?>>No recibido</option>
                <option value="Cambio de producto" <?php selected($estado, 'Cambio de producto'); ?>>Cambio de producto</option>
            </select>
        </div>
        
        <div class="form-group">
            <label for="marca">🏪 Marca</label>
            <div class="merc-select-container">
                <select name="marca" id="marca" size="1" style="max-height:200px;overflow-y:auto;" class="merc-select-scroll">
                    <option value="">Todas (<?php echo count($marcas); ?>)</option>
                    <?php foreach ($marcas as $m): ?>
                        <option value="<?php echo esc_attr($m); ?>" <?php selected($marca, $m); ?>>
                            <?php echo esc_html($m); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="motorizado">🏍️ Motorizado</label>
            <div class="merc-select-container">
                <select name="motorizado" id="motorizado" size="1" style="max-height:200px;overflow-y:auto;" class="merc-select-scroll">
                    <option value="">Todos (<?php echo count($drivers); ?>)</option>
                    <?php foreach ($drivers as $d): ?>
                        <option value="<?php echo esc_attr($d->ID); ?>" <?php selected($motorizado, $d->ID); ?>>
                            <?php 
                            $nombre = trim(get_user_meta($d->ID, 'first_name', true) . ' ' . get_user_meta($d->ID, 'last_name', true));
                            echo esc_html($nombre ?: $d->display_name);
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group">
            <label for="desde">📅 Desde</label>
            <input type="date" name="desde" id="desde" value="<?php echo esc_attr($desde); ?>">
        </div>
        
        <div class="form-group">
            <label for="hasta">📅 Hasta</label>
            <input type="date" name="hasta" id="hasta" value="<?php echo esc_attr($hasta); ?>">
        </div>
        
        <div class="form-group">
            <button type="submit" class="button button-primary" style="height:38px;width:100%;">🔍 Filtrar</button>
        </div>
        
        <div class="form-group">
            <a href="<?php echo esc_url($base_url . '?page_id=' . get_queried_object_id()); ?>" class="button" style="height:38px;line-height:36px;text-align:center;display:block;">
               🔄 Limpiar
            </a>
        </div>
    </form>
    <?php
    return ob_get_clean();
}

/**
 * Botones de exportación
 */
function merc_render_export_buttons($estado, $marca, $motorizado, $desde, $hasta, $buscar) {
    $params = array('action' => 'merc_export_csv');
    if ($estado)    $params['estado']     = $estado;
    if ($marca)     $params['marca']      = $marca;
    if ($motorizado) $params['motorizado'] = $motorizado;
    if ($desde)     $params['desde']      = $desde;
    if ($hasta)     $params['hasta']      = $hasta;
    if ($buscar)    $params['buscar']     = $buscar;
    $export_url = admin_url('admin-ajax.php?' . http_build_query($params));

    ob_start();
    ?>
    <div class="merc-export-buttons">
        <a href="<?php echo esc_url($export_url); ?>" class="button button-csv">📥 Exportar CSV</a>
        <button onclick="window.print()" class="button button-print">🖨️ Imprimir</button>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Tabla principal de devoluciones
 */
function merc_render_table($estado, $marca, $motorizado, $desde, $hasta, $buscar) {
    $args  = merc_build_query($estado, $marca, $motorizado, $desde, $hasta, $buscar);
    $query = new WP_Query($args);

    ob_start();
    ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th style="width:10%;">Fecha</th>
                <th style="width:11%;">Tracking</th>
                <th style="width:15%;">Cliente</th>
                <th style="width:12%;">Estado</th>
                <th style="width:13%;">Marca</th>
                <th style="width:12%;">Motorizado</th>
                <th style="width:11%;">Cambio Producto</th>
                <th style="width:16%;">Estado Entrega</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($query->have_posts()):
                while ($query->have_posts()): $query->the_post();
                    $id = get_the_ID();
                    $fecha_raw = get_post_meta($id, 'wpcargo_pickup_date_picker', true);
                    
                    if ($fecha_raw) {
                        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $fecha_raw)) {
                            $fecha = $fecha_raw;
                        } else {
                            $fecha = date_i18n('d/m/Y', strtotime($fecha_raw));
                        }
                    } else {
                        $fecha = '';
                    }
                    
                    $tracking         = get_the_title($id);
                    $cliente          = get_post_meta($id, 'wpcargo_receiver_name', true);
                    $estado_envio     = get_post_meta($id, 'wpcargo_status', true);
                    $marca_nombre     = get_post_meta($id, 'wpcargo_tiendaname', true);
                    $driver_id        = get_post_meta($id, 'wpcargo_driver', true);
                    
                    if ($driver_id && get_userdata($driver_id)) {
                        $first = get_user_meta($driver_id, 'first_name', true);
                        $last  = get_user_meta($driver_id, 'last_name', true);
                        $motorizado_nombre = trim($first . ' ' . $last) ?: get_userdata($driver_id)->display_name;
                    } else {
                        $motorizado_nombre = 'No asignado';
                    }
                    
                    $cambio_producto  = get_post_meta($id, 'cambio_producto', true);
                    $estado_entrega   = get_post_meta($id, 'merc_estado_entrega', true);

                    $fila_bg = '';
                    if ($estado_entrega === 'Entregado')    $fila_bg = 'background-color:#d4edda;';
                    if ($estado_entrega === 'No Entregado') $fila_bg = 'background-color:#f8d7da;';

                    $estado_normalizado = merc_normalize_status($estado_envio);
                    $badge_colors = array(
                        'Reprogramado' => '#ff9800',
                        'Anulado'      => '#f44336',
                        'No recibido'  => '#9c27b0',
                        'Entregado'    => '#4caf50',
                        'En tránsito'  => '#2196f3',
                        'Pendiente'    => '#ff9800',
                        'Procesando'   => '#00bcd4',
                        'Cancelado'    => '#f44336'
                    );
                    $badge_color = isset($badge_colors[$estado_normalizado]) ? $badge_colors[$estado_normalizado] : '#666';

                    $dashboard_url = get_permalink(wpcfe_admin_page());
                    $detalle_url   = add_query_arg(array('wpcfe' => 'track', 'num' => $tracking), $dashboard_url);
                    ?>
                    <tr style="<?php echo esc_attr($fila_bg); ?>">
                        <td><?php echo esc_html($fecha); ?></td>
                        <td>
                            <a href="<?php echo esc_url($detalle_url); ?>" style="font-weight:600;color:#2271b1;text-decoration:none;">
                                <?php echo esc_html($tracking); ?>
                            </a>
                        </td>
                        <td><?php echo esc_html($cliente); ?></td>
                        <td>
                            <span style="display:inline-block;padding:6px 12px;border-radius:4px;background:<?php echo esc_attr($badge_color); ?>;color:#fff;font-weight:600;font-size:11px;">
                                <?php echo esc_html($estado_envio); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($marca_nombre); ?></td>
                        <td><?php echo esc_html($motorizado_nombre); ?></td>
                        <td>
                            <?php if ($cambio_producto === 'Sí'): ?>
                                <span style="display:inline-block;padding:6px 12px;border-radius:4px;background:#00796b;color:#fff;font-weight:600;font-size:11px;">SÍ</span>
                            <?php endif; ?>
                        </td>
                        <td style="white-space:nowrap;">
                            <select class="merc-estado-entrega-select" data-post-id="<?php echo esc_attr($id); ?>">
                                <option value="" <?php selected($estado_entrega, ''); ?>>— Sin definir —</option>
                                <option value="Entregado" <?php selected($estado_entrega, 'Entregado'); ?>>✅ Entregado</option>
                                <option value="No Entregado" <?php selected($estado_entrega, 'No Entregado'); ?>>❌ No Entregado</option>
                            </select>
                        </td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="8" style="text-align:center;padding:60px;">
                        <div style="font-size:64px;margin-bottom:20px;">📭</div>
                        <p style="color:#666;font-size:16px;margin:0;">No se encontraron devoluciones</p>
                    </td>
                </tr>
            <?php endif; wp_reset_postdata(); ?>
        </tbody>
    </table>

    <?php if ($query->found_posts > 0): ?>
        <div style="margin-top:20px;padding:15px;background:#e8f4fd;border-left:4px solid #2271b1;border-radius:4px;">
            <strong>📊 Total:</strong> <?php echo intval($query->found_posts); ?> devoluciones encontradas
        </div>
    <?php endif;

    return ob_get_clean();
}
