<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function wcrol_tpl( string $tpl, array $vars = [] ): void {
    $file = WCROL_PATH . 'admin/templates/' . $tpl;
    if ( ! file_exists($file) ) {
        echo '<div class="notice notice-error"><p>Template no encontrado: ' . esc_html($tpl) . '</p></div>';
        return;
    }
    extract($vars, EXTR_SKIP);
    require $file;
}

function wcrol_url( string $page, array $extra = [] ): string {
    return add_query_arg(array_merge(['page' => $page], $extra), admin_url('admin.php'));
}

function wcrol_redirect( string $page, string $msg = '', array $extra = [] ): void {
    $params = array_merge(['page' => $page], $extra);
    if ($msg) $params['wcrol_msg'] = $msg;
    wp_redirect(add_query_arg($params, admin_url('admin.php')));
    exit;
}

function wcrol_nombre_usuario( \WP_User $user ): string {
    return trim($user->display_name) ?: trim($user->first_name . ' ' . $user->last_name) ?: $user->user_login;
}

// ── Frontend (dashboard WPCargo) ──────────────────────────────────────────────

function wcrol_get_frontend_page_id(): int {
    $saved = (int) get_option('wcrol_frontend_page_id');
    if ( $saved && get_post_status($saved) === 'publish' ) return $saved;

    global $wpdb;
    $id = (int) $wpdb->get_var(
        "SELECT ID FROM {$wpdb->prefix}posts
         WHERE post_content LIKE '%[wcrol-roles]%' AND post_status='publish' LIMIT 1"
    );
    if ( ! $id ) {
        $id = (int) wp_insert_post([
            'post_title'   => 'Roles & Accesos',
            'post_content' => '[wcrol-roles]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
    if ( $id ) {
        update_post_meta($id, '_wp_page_template', 'dashboard.php');
        update_post_meta($id, 'wpcfe_menu_icon',   'fa fa-shield mr-3');
        update_option('wcrol_frontend_page_id', $id, false);
    }
    return $id;
}

function wcrol_frontend_url( array $extra = [] ): string {
    $url = get_permalink(wcrol_get_frontend_page_id()) ?: home_url('/roles-accesos/');
    return $extra ? add_query_arg($extra, $url) : $url;
}

/** ¿Es el usuario un admin de WordPress puro (puede entrar a wp-admin)? */
function wcrol_es_wp_admin( int $user_id = 0 ): bool {
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    return $user && in_array('administrator', (array)$user->roles, true);
}

/** ¿Es el usuario un admin de WPCargo solamente (no puede entrar a wp-admin)? */
function wcrol_es_wpcargo_admin( int $user_id = 0 ): bool {
    $user = $user_id ? get_userdata($user_id) : wp_get_current_user();
    return $user && in_array('wpcargo_admin', (array)$user->roles, true);
}

/** ¿Puede el usuario actual gestionar roles? (solo wp admins) */
function wcrol_puede_gestionar(): bool {
    return current_user_can('manage_options');
}
