<?php
/**
 * Hooks - Merc Returns
 */

if (!defined('ABSPATH')) exit;

/**
 * Configurar roles permitidos para ver devoluciones
 */
add_filter('merc_devoluciones_allowed_roles', function($roles) {
    return array_merge($roles, array('wpcargo_agent'));
}, 10, 1);

/**
 * Agregar item al sidebar
 */
add_filter('wpcfe_after_sidebar_menu_items', function ($menu_items) {
    if (!merc_user_can_view_devoluciones()) return $menu_items;
    
    $page = get_page_by_path('devoluciones');
    if (!$page) return $menu_items;

    $count_args = array(
        'post_type'      => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array('key' => 'wpcargo_status', 'value' => array('Reprogramado','Anulado','No recibido'), 'compare' => 'IN'),
            array('key' => 'cambio_producto', 'value' => 'Sí', 'compare' => '=')
        )
    );
    $q     = new WP_Query($count_args);
    $count = (int) $q->found_posts;

    $label = '<i>🔄</i> Devoluciones';
    if ($count > 0) {
        $label .= ' <span style="background:#f44336;color:#fff;padding:2px 8px;border-radius:10px;font-size:11px;margin-left:5px;">' . $count . '</span>';
    }

    $menu_items['merc_devoluciones'] = array(
        'label'     => $label,
        'url'       => get_permalink($page->ID),
        'permalink' => get_permalink($page->ID)
    );
    return $menu_items;
}, 30);

/**
 * Meta Box en pedidos
 */
add_action('add_meta_boxes', 'merc_add_devolucion_meta_box');
function merc_add_devolucion_meta_box() {
    add_meta_box(
        'merc_devolucion_info',
        '🔄 Información de Devolución',
        'merc_render_devolucion_meta_box',
        'wpcargo_shipment',
        'side',
        'high'
    );
}

function merc_render_devolucion_meta_box($post) {
    $estado          = get_post_meta($post->ID, 'wpcargo_status', true);
    $cambio_producto = get_post_meta($post->ID, 'cambio_producto', true);
    $estado_entrega  = get_post_meta($post->ID, 'merc_estado_entrega', true);

    $estados_devolucion = array('Reprogramado', 'Anulado', 'No recibido');
    $es_devolucion      = in_array($estado, $estados_devolucion) || $cambio_producto === 'Sí';

    if (!$es_devolucion) {
        echo '<p style="color:#666;font-style:italic;">Este pedido no está registrado como devolución.</p>';
        echo '<p style="color:#999;font-size:12px;">Para que aparezca en el módulo debe tener estado: Reprogramado, Anulado, No recibido o cambio_producto = "Sí"</p>';
        return;
    }

    $page       = get_page_by_path('devoluciones');
    $devol_url  = $page ? get_permalink($page->ID) : '#';
    $tracking   = get_post_meta($post->ID, 'wpcargo_tracking_number', true);
    $search_url = add_query_arg('buscar', $tracking, $devol_url);

    $badge_colors = array(
        'Reprogramado' => '#ff9800',
        'Anulado'      => '#f44336',
        'No recibido'  => '#9c27b0'
    );
    $color = isset($badge_colors[$estado]) ? $badge_colors[$estado] : '#00796b';
    ?>
    <div style="padding:15px;background:<?php echo $color; ?>15;border-left:4px solid <?php echo $color; ?>;border-radius:4px;margin-bottom:15px;">
        <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;">
            <span style="font-size:24px;">🔄</span>
            <strong style="color:<?php echo $color; ?>;font-size:14px;">DEVOLUCIÓN REGISTRADA</strong>
        </div>
        <div style="padding:10px;background:white;border-radius:4px;margin-top:10px;">
            <?php if (!empty($estado)): ?>
            <p style="margin:0 0 8px 0;color:#333;font-size:13px;">
                <strong>Estado:</strong>
                <span style="display:inline-block;padding:4px 8px;border-radius:3px;background:<?php echo $color; ?>;color:#fff;font-size:11px;margin-left:5px;">
                    <?php echo esc_html($estado); ?>
                </span>
            </p>
            <?php endif; ?>
            <?php if ($cambio_producto === 'Sí'): ?>
            <p style="margin:0 0 8px 0;color:#00796b;font-size:13px;">
                <strong>🔁 Cambio de producto:</strong>
                <span style="display:inline-block;padding:4px 8px;border-radius:3px;background:#00796b;color:#fff;font-size:11px;margin-left:5px;">
                    SÍ
                </span>
            </p>
            <?php endif; ?>
            <?php if (!empty($estado_entrega)): ?>
            <p style="margin:0;color:#333;font-size:13px;">
                <strong>📦 Estado Entrega:</strong>
                <span style="display:inline-block;padding:4px 8px;border-radius:3px;background:<?php echo $estado_entrega === 'Entregado' ? '#28a745' : '#dc3545'; ?>;color:#fff;font-size:11px;margin-left:5px;">
                    <?php echo esc_html($estado_entrega); ?>
                </span>
            </p>
            <?php endif; ?>
        </div>
    </div>
    <div style="margin-top:12px;">
        <a href="<?php echo esc_url($search_url); ?>" class="button button-secondary" style="width:100%;text-align:center;font-weight:600;">
            📋 Ver en Módulo de Devoluciones
        </a>
    </div>
    <?php
}

/**
 * Widget en Dashboard
 */
add_action('wp_dashboard_setup', 'merc_add_dashboard_widget');
function merc_add_dashboard_widget() {
    if (!merc_user_can_view_devoluciones()) return;
    wp_add_dashboard_widget(
        'merc_devoluciones_widget',
        '🔄 Resumen de Devoluciones',
        'merc_render_dashboard_widget'
    );
}

function merc_render_dashboard_widget() {
    $args = array(
        'post_type'      => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'OR',
            array('key' => 'wpcargo_status', 'value' => array('Reprogramado','Anulado','No recibido'), 'compare' => 'IN'),
            array('key' => 'cambio_producto', 'value' => 'Sí', 'compare' => '=')
        )
    );

    $q     = new WP_Query($args);
    $total = (int) $q->found_posts;
    $counts = array('Reprogramado' => 0, 'Anulado' => 0, 'No recibido' => 0, 'Cambio de producto' => 0);

    if ($q->have_posts()) {
        foreach ($q->posts as $post_id) {
            $estado = get_post_meta($post_id, 'wpcargo_status', true);
            $cambio = get_post_meta($post_id, 'cambio_producto', true);
            if (isset($counts[$estado])) $counts[$estado]++;
            if ($cambio === 'Sí') $counts['Cambio de producto']++;
        }
    }
    ?>
    <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:12px;margin-bottom:15px;">
        <div style="padding:15px;background:#e8f4fd;border-left:4px solid #2271b1;border-radius:4px;">
            <div style="font-size:28px;font-weight:bold;color:#2271b1;"><?php echo $total; ?></div>
            <div style="color:#2271b1;font-size:12px;font-weight:600;">Total Devoluciones</div>
        </div>
        <div style="padding:15px;background:#fee;border-left:4px solid #f44336;border-radius:4px;">
            <div style="font-size:28px;font-weight:bold;color:#f44336;"><?php echo $counts['Anulado']; ?></div>
            <div style="color:#c62828;font-size:12px;font-weight:600;">Anulados</div>
        </div>
        <div style="padding:15px;background:#fff3e0;border-left:4px solid #ff9800;border-radius:4px;">
            <div style="font-size:28px;font-weight:bold;color:#ff9800;"><?php echo $counts['Reprogramado']; ?></div>
            <div style="color:#e65100;font-size:12px;font-weight:600;">Reprogramados</div>
        </div>
        <div style="padding:15px;background:#f3e5f5;border-left:4px solid #9c27b0;border-radius:4px;">
            <div style="font-size:28px;font-weight:bold;color:#9c27b0;"><?php echo $counts['No recibido']; ?></div>
            <div style="color:#6a1b9a;font-size:12px;font-weight:600;">No Recibidos</div>
        </div>
    </div>
    <div style="text-align:center;padding-top:15px;border-top:1px solid #ddd;">
        <a href="<?php echo esc_url(merc_get_devoluciones_page_url()); ?>" class="button button-primary" style="font-weight:600;">
            Ver todas las devoluciones →
        </a>
    </div>
    <?php
}

/**
 * Admin Notice
 */
add_action('admin_notices', 'merc_devoluciones_admin_notice');
function merc_devoluciones_admin_notice() {
    if (!merc_user_can_view_devoluciones()) return;

    $screen = get_current_screen();
    if ($screen->id !== 'dashboard') return;

    $args = array(
        'post_type'      => 'wpcargo_shipment',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'fields'         => 'ids',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array('key' => 'wpcargo_status', 'value' => array('Reprogramado','Anulado','No recibido'), 'compare' => 'IN'),
                array('key' => 'cambio_producto', 'value' => 'Sí', 'compare' => '=')
            ),
            array(
                'key'     => 'wpcargo_pickup_date_picker',
                'value'   => date('Y-m-d', strtotime('-7 days')),
                'compare' => '>=',
                'type'    => 'DATE'
            )
        )
    );

    $q     = new WP_Query($args);
    $count = (int) $q->found_posts;

    if ($count > 5):
    ?>
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong>🔄 Devoluciones:</strong>
                Tienes <strong><?php echo $count; ?></strong> devoluciones registradas en los últimos 7 días.
                <a href="<?php echo esc_url(merc_get_devoluciones_page_url()); ?>" style="margin-left:10px;">
                    Ver devoluciones →
                </a>
            </p>
        </div>
    <?php
    endif;
}

/**
 * Admin Head Styles
 */
add_action('admin_head', 'merc_devoluciones_admin_styles');
function merc_devoluciones_admin_styles() {
    ?>
    <style>
    #merc_devolucion_info .inside { padding: 0; margin: 0; }
    </style>
    <?php
}
