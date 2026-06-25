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

    if (!$query->have_posts()) {
        ob_start();
        ?>
        <div class="merc-empty-result">
            <p>No se encontraron devoluciones con esos filtros.</p>
        </div>
        <?php
        return ob_get_clean();
    }

    $grupos = array();

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

        $tracking      = get_the_title($id);
        $cliente       = trim(get_post_meta($id, 'wpcargo_receiver_name', true));
        $estado_envio  = get_post_meta($id, 'wpcargo_status', true);
        $marca_nombre  = get_post_meta($id, 'wpcargo_tiendaname', true);
        $driver_id     = get_post_meta($id, 'wpcargo_driver', true);
        $cambio_producto = get_post_meta($id, 'cambio_producto', true);
        $estado_entrega  = get_post_meta($id, 'merc_estado_entrega', true);

        if ($cliente === '') {
            $cliente = 'Sin cliente';
        }

        if ($driver_id && get_userdata($driver_id)) {
            $first = get_user_meta($driver_id, 'first_name', true);
            $last  = get_user_meta($driver_id, 'last_name', true);
            $motorizado_nombre = trim($first . ' ' . $last) ?: get_userdata($driver_id)->display_name;
        } else {
            $motorizado_nombre = 'No asignado';
        }

        $fila_bg = '';
        if ($estado_entrega === 'Entregado') {
            $fila_bg = 'background-color:#d4edda;';
        } elseif ($estado_entrega === 'No Entregado') {
            $fila_bg = 'background-color:#f8d7da;';
        }

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

        $grupos[$marca_nombre][] = array(
            'id'                => $id,
            'fecha'             => $fecha,
            'tracking'          => $tracking,
            'cliente'           => $cliente,
            'estado_envio'      => $estado_envio,
            'badge_color'       => $badge_color,
            'marca_nombre'      => $marca_nombre,
            'motorizado_nombre' => $motorizado_nombre,
            'cambio_producto'   => $cambio_producto,
            'estado_entrega'    => $estado_entrega,
            'fila_bg'           => $fila_bg,
            'detalle_url'       => $detalle_url,
        );
    endwhile;

    wp_reset_postdata();

    ob_start();
    ?>
    <style>
    .merc-return-group{
        margin-bottom:16px;
        border:1px solid #dfe3e8;
        border-radius:10px;
        overflow:hidden;
        background:#fff;
        box-shadow:0 1px 3px rgba(0,0,0,.06);
    }

    .merc-return-summary{
        width:100%;
        border:0;
        background:linear-gradient(90deg,#2f4154,#3b5169);
        color:#fff;
        padding:14px 18px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        cursor:pointer;
        text-align:left;
        font:inherit;
        font-weight:700;
        box-sizing:border-box;
    }

    .merc-return-summary::-webkit-details-marker{
        display:none;
    }

    .merc-return-summary::marker{
        content:'';
    }

    .merc-return-summary-left{
        display:flex;
        align-items:center;
        gap:10px;
        min-width:0;
    }

    .merc-return-summary-icon{
        font-size:16px;
        line-height:1;
    }

    .merc-return-summary-title{
        color:#fff;
        font-size:15px;
        font-weight:700;
        white-space:nowrap;
    }

    .merc-return-summary-count{
        color:#fff;
        font-weight:600;
        white-space:nowrap;
        opacity:.95;
    }

    .merc-return-summary-arrow{
        color:#fff;
        font-size:18px;
        transition:transform .2s ease;
        margin-left:auto;
    }

    .merc-return-group[open] .merc-return-summary-arrow{
        transform:rotate(180deg);
    }

    .merc-return-group-body{
        padding:0;
        background:#fff;
    }

    .merc-return-table{
        margin:0;
        width:100%;
        border-top:1px solid #e5e7eb;
    }

    .merc-return-table thead th{
        background:#f8fafc;
        font-weight:700;
    }

    .merc-return-empty{
        padding:18px;
        text-align:center;
        color:#6b7280;
        background:#fff;
    }
    </style>

    <?php foreach ($grupos as $grupo => $rows): ?>
        <details class="merc-return-group" <?php echo $primer_grupo ? 'open' : ''; ?>>
            <summary class="merc-return-summary">
                <span class="merc-return-summary-left">
                    <span class="merc-return-summary-icon">🔄</span>
                    <span class="merc-return-summary-title"><?php echo esc_html($grupo); ?></span>
                </span>

                <span class="merc-return-summary-count"><?php echo count($rows); ?> devolución(es)</span>
                <span class="merc-return-summary-arrow">▾</span>
            </summary>

            <div class="merc-return-group-body">
                <table class="wp-list-table widefat fixed striped merc-return-table">
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
                        <?php if (!empty($rows)): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr style="<?php echo esc_attr($row['fila_bg']); ?>">
                                    <td><?php echo esc_html($row['fecha']); ?></td>
                                    <td>
                                        <a href="<?php echo esc_url($row['detalle_url']); ?>" style="font-weight:600;color:#2271b1;text-decoration:none;">
                                            <?php echo esc_html($row['tracking']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($row['cliente']); ?></td>
                                    <td>
                                        <span style="display:inline-block;padding:6px 12px;border-radius:4px;background:<?php echo esc_attr($row['badge_color']); ?>;color:#fff;font-weight:600;font-size:11px;">
                                            <?php echo esc_html($row['estado_envio']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo esc_html($row['marca_nombre']); ?></td>
                                    <td><?php echo esc_html($row['motorizado_nombre']); ?></td>
                                    <td>
                                        <?php if ($row['cambio_producto'] === 'Sí'): ?>
                                            <span style="display:inline-block;padding:6px 12px;border-radius:4px;background:#00796b;color:#fff;font-weight:600;font-size:11px;">SÍ</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="white-space:nowrap;">
                                        <select class="merc-estado-entrega-select" data-post-id="<?php echo esc_attr($row['id']); ?>">
                                            <option value="" <?php selected($row['estado_entrega'], ''); ?>>— Sin definir —</option>
                                            <option value="Entregado" <?php selected($row['estado_entrega'], 'Entregado'); ?>>✅ Entregado</option>
                                            <option value="No Entregado" <?php selected($row['estado_entrega'], 'No Entregado'); ?>>❌ No Entregado</option>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="merc-return-empty">Sin devoluciones en este grupo.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </details>
    <?php endforeach; ?>

    <?php
    return ob_get_clean();
}
