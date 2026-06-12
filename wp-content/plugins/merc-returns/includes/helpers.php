<?php
/**
 * Helpers - Merc Returns
 */

if (!defined('ABSPATH')) exit;

/**
 * Verificar si usuario puede ver devoluciones
 */
function merc_user_can_view_devoluciones() {
    if (current_user_can('manage_options')) return true;
    $allowed_roles = apply_filters('merc_devoluciones_allowed_roles', array());
    if (!empty($allowed_roles)) {
        $user = wp_get_current_user();
        if (!empty($user->roles) && array_intersect($allowed_roles, $user->roles)) return true;
    }
    return false;
}

/**
 * Normalizar estado
 */
function merc_normalize_status($status) {
    if (empty($status)) return '';
    return ucfirst(strtolower(trim($status)));
}

/**
 * Obtener URL del página de devoluciones
 */
function merc_get_devoluciones_page_url() {
    $page = get_page_by_path('devoluciones');
    return $page ? get_permalink($page->ID) : home_url();
}

/**
 * Construir query para devoluciones
 */
function merc_build_query($estado, $marca, $motorizado, $desde, $hasta, $buscar = '') {
    $args = array(
        'post_type'      => 'wpcargo_shipment',
        'posts_per_page' => 200,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC'
    );

    $meta_query = array('relation' => 'AND');

    $base = array(
        'relation' => 'OR',
        array('key' => 'wpcargo_status', 'value' => array('Reprogramado','Anulado','No recibido'), 'compare' => 'IN'),
        array('key' => 'cambio_producto', 'value' => 'Sí', 'compare' => '=')
    );

    if (!empty($estado)) {
        if ($estado === 'Cambio de producto') {
            $meta_query[] = array('key' => 'cambio_producto', 'value' => 'Sí', 'compare' => '=');
        } else {
            $meta_query[] = $base;
            $meta_query[] = array('key' => 'wpcargo_status', 'value' => $estado, 'compare' => '=');
        }
    } else {
        $meta_query[] = $base;
    }

    if (!empty($marca))      $meta_query[] = array('key' => 'wpcargo_tiendaname', 'value' => $marca, 'compare' => '=');
    if (!empty($motorizado)) $meta_query[] = array('key' => 'wpcargo_driver', 'value' => $motorizado, 'compare' => '=');
    
    if (!empty($desde) && !empty($hasta)) {
        global $wpdb;
        $ids = $wpdb->get_col($wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta}
            WHERE meta_key = 'wpcargo_pickup_date_picker'
            AND STR_TO_DATE(meta_value, '%%d/%%m/%%Y') BETWEEN %s AND %s
        ", $desde, $hasta));
        
        if (!empty($ids)) {
            $args['post__in'] = $ids;
        } else {
            $args['post__in'] = array(0);
        }
    }
    
    if (!empty($buscar)) $args['s'] = $buscar;

    $args['meta_query'] = $meta_query;
    return $args;
}
