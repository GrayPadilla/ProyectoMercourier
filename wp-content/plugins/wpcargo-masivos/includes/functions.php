<?php
if ( ! defined('ABSPATH') ) exit;

function wcmas_tpl( string $tpl, array $vars = [] ): void {
    $file = WCMAS_PATH . 'admin/templates/' . $tpl;
    if ( ! file_exists($file) ) { echo '<p>Template no encontrado: '.esc_html($tpl).'</p>'; return; }
    extract($vars, EXTR_SKIP);
    require $file;
}

function wcmas_url( string $page, array $extra = [] ): string {
    return add_query_arg(array_merge(['page' => $page], $extra), admin_url('admin.php'));
}

function wcmas_redirect( string $page, string $msg = '', array $extra = [] ): void {
    $params = array_merge(['page' => $page], $extra);
    if ($msg) $params['wcmas_msg'] = $msg;
    wp_redirect(add_query_arg($params, admin_url('admin.php')));
    exit;
}

function wcmas_get_frontend_page_id(): int {
    $saved = (int) get_option('wcmas_frontend_page_id');
    if ( $saved && get_post_status($saved) === 'publish' ) return $saved;
    global $wpdb;
    $id = (int) $wpdb->get_var("SELECT ID FROM {$wpdb->prefix}posts WHERE post_content LIKE '%[wcmas-masivos]%' AND post_status='publish' LIMIT 1");
    if ( ! $id ) {
        $id = (int) wp_insert_post([
            'post_title'   => 'Envíos Masivos',
            'post_content' => '[wcmas-masivos]',
            'post_status'  => 'publish',
            'post_type'    => 'page',
        ]);
    }
    if ( $id ) {
        update_post_meta($id, '_wp_page_template', 'dashboard.php');
        update_post_meta($id, 'wpcfe_menu_icon',   'fa fa-table mr-3');
        update_option('wcmas_frontend_page_id', $id, false);
    }
    return $id;
}

function wcmas_frontend_url( array $extra = [] ): string {
    $url = get_permalink(wcmas_get_frontend_page_id()) ?: home_url('/envios-masivos/');
    return $extra ? add_query_arg($extra, $url) : $url;
}

/**
 * Lee la configuración de tracking del plugin oficial de WPCargo y
 * genera un número de tracking siguiendo exactamente ese formato.
 *
 * WPCargo guarda la configuración de tracking en estas opciones:
 *   - wpcargo_tracking_prefix   → prefijo del tracking (ej: "DHV")
 *   - wpcargo_tracking_suffix   → sufijo (si existe)
 *   - wpcargo_tracking_digits   → número de dígitos numéricos
 *   - wpcargo_tracking_type     → tipo: 'alpha', 'numeric', 'alphanumeric'
 *
 * Si WPCargo tiene su propia función de generación la usa directamente.
 */
function wcmas_generar_tracking(): string {
    // 1. WPCargo puede tener su propia función de generación — prioridad máxima
    if ( function_exists('wpcargo_generate_tracking_number') ) {
        $tracking = wpcargo_generate_tracking_number();
        if ( $tracking ) return $tracking;
    }

    // 2. Leer configuración de WPCargo para replicar el formato exacto
    $wpcargo_config = wcmas_get_wpcargo_tracking_config();

    $prefix  = strtoupper($wpcargo_config['prefix']);
    $suffix  = strtoupper($wpcargo_config['suffix']);
    $digits  = intval($wpcargo_config['digits'] ?: 6);
    $type    = $wpcargo_config['type'] ?: 'numeric';

    // Generar la parte variable según el tipo configurado en WPCargo
    switch ( $type ) {
        case 'numeric':
            // Formato secuencial numérico con ceros a la izquierda (ej: MERC-002563)
            // Obtener el siguiente número correlativo basado en el conteo actual de envíos
            $last_tracking = wcmas_get_ultimo_numero_tracking($prefix, $suffix, $digits);
            $siguiente     = $last_tracking + 1;
            $variable      = str_pad((string)$siguiente, $digits, '0', STR_PAD_LEFT);
            break;
        case 'alpha':
            $chars    = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $variable = '';
            for ( $i = 0; $i < $digits; $i++ ) $variable .= $chars[random_int(0, 25)];
            break;
        case 'alphanumeric':
        default:
            // Formato por defecto WPCargo: fecha corta + random alfanumérico
            $variable = strtoupper(date('ymd') . substr(base_convert((string)random_int(0, PHP_INT_MAX), 10, 36), -4));
            break;
    }

    return $prefix . $variable . $suffix;
}

/**
 * Obtiene el último número correlativo usado en trackings numéricos,
 * buscando en los envíos existentes de WPCargo para no repetir.
 *
 * @param string $prefix  Prefijo exacto (puede incluir guión, ej: "MERC-")
 * @param string $suffix  Sufijo
 * @param int    $digits  Dígitos del número
 * @return int  Último número usado (0 si no hay ninguno)
 */
function wcmas_get_ultimo_numero_tracking( string $prefix, string $suffix, int $digits ): int {
    global $wpdb;

    // Buscar el post_title más alto que coincida con el patrón
    // post_title = prefijo + N dígitos + sufijo
    $like_pattern = $wpdb->esc_like($prefix) . str_repeat('_', $digits) . $wpdb->esc_like($suffix);

    $ultimo = $wpdb->get_var( $wpdb->prepare(
        "SELECT post_title FROM {$wpdb->posts}
         WHERE post_type = 'wpcargo_shipment'
           AND post_status = 'publish'
           AND post_title LIKE %s
         ORDER BY post_title DESC
         LIMIT 1",
        $like_pattern
    ));

    if ( ! $ultimo ) return 0;

    // Extraer la parte numérica quitando prefijo y sufijo
    $sin_prefix = substr($ultimo, strlen($prefix));
    $sin_suffix = $suffix ? substr($sin_prefix, 0, -strlen($suffix)) : $sin_prefix;
    $numero     = intval(preg_replace('/\D/', '', $sin_suffix));

    return $numero;
}

/**
 * Lee y normaliza la configuración de tracking de WPCargo desde todas
 * las posibles opciones que usa el plugin oficial en sus distintas versiones.
 *
 * @return array { prefix, suffix, digits, type, preview }
 */
function wcmas_get_wpcargo_tracking_config(): array {
    // WPCargo guarda el prefijo en distintas opciones según la versión
    $prefix = get_option('wpcargo_tracking_prefix')          // versión oficial
           ?: get_option('wpcargo_prefix')                   // versión alternativa
           ?: get_option('wcmas_tracking_prefix', 'DHV');    // fallback propio

    $suffix = get_option('wpcargo_tracking_suffix', '')
           ?: get_option('wpcargo_suffix', '');

    // WPCargo puede guardar los dígitos con distintos nombres de opción
    $digits = intval(get_option('wpcargo_tracking_digits', 0));
    if ( $digits <= 0 ) $digits = intval(get_option('wpcargo_shipment_number_digits', 0));
    if ( $digits <= 0 ) $digits = intval(get_option('wpcargo_digits', 0));

    // Determinar tipo: si no es explícito pero hay dígitos → numérico secuencial
    $type = get_option('wpcargo_tracking_type', '');
    if ( ! $type ) $type = get_option('wpcargo_number_type', '');
    if ( ! in_array($type, ['alpha', 'numeric', 'alphanumeric'], true) ) {
        $type = ( $digits > 0 ) ? 'numeric' : 'alphanumeric';
    }

    // Normalizar dígitos
    if ( $digits <= 0 ) $digits = ( $type === 'numeric' ) ? 6 : 8;

    // Generar ejemplo del formato resultante para mostrar en config
    $ejemplo_var = match($type) {
        'numeric'      => str_pad('1', $digits, '0', STR_PAD_LEFT), // ej: 000001
        'alpha'        => str_repeat('X', $digits),
        default        => date('ymd') . str_repeat('X', max(1, $digits - 6)),
    };
    $preview = strtoupper($prefix) . $ejemplo_var . strtoupper($suffix);

    return compact('prefix', 'suffix', 'digits', 'type', 'preview');
}

/**
 * Estado inicial del envío (usa el mismo default que WPCargo frontend).
 */
function wcmas_default_status(): string {
    // WPCargo guarda el status default en esta opción
    $status = get_option('wpcfe_default_status');
    if ( ! $status ) $status = 'Pending';
    return apply_filters('wcmas_default_status', $status);
}

/**
 * ¿Puede el usuario actual crear envíos masivos?
 * Incluye: admins WP, admins WPCargo, clientes WPCargo y cualquier rol
 * que WPCargo considere con permiso para añadir envíos.
 */
function wcmas_puede_crear(): bool {
    if ( ! is_user_logged_in() ) return false;

    // Usar la función nativa de WPCargo si existe
    if ( function_exists('can_wpcfe_add_shipment') ) {
        return (bool) can_wpcfe_add_shipment();
    }

    // Fallback: roles que pueden crear envíos
    $user  = wp_get_current_user();
    $roles = (array) $user->roles;
    $roles_permitidos = ['administrator', 'wpcargo_admin', 'wpcargo_client',
                         'wpcargo_employee', 'cargo_agent', 'wpcargo_branch_manager', 'editor'];
    return (bool) array_intersect($roles, $roles_permitidos);
}

/**
 * ¿Es administrador (WP admin o WPCargo admin)?
 */
function wcmas_es_admin(): bool {
    if ( ! is_user_logged_in() ) return false;
    if ( current_user_can('manage_options') ) return true;
    // WPCargo tiene su propia función de superadmin
    if ( function_exists('wpcfe_is_super_admin') && wpcfe_is_super_admin() ) return true;
    return in_array('wpcargo_admin', (array) wp_get_current_user()->roles, true);
}

/**
 * Lista de CLIENTES disponibles para asignar envíos (solo para admins).
 * Retorna únicamente usuarios con rol de cliente WPCargo o subscriber
 * que hayan usado el sistema (no incluye admins ni empleados).
 *
 * @param string $search  Búsqueda opcional (nombre, email o login).
 * @param int    $limit   Máximo de resultados (para Select2 paginado).
 */
function wcmas_get_clientes_select( string $search = '', int $limit = 50 ): array {
    // Roles considerados "clientes" en WPCargo
    $roles_cliente = apply_filters('wcmas_roles_clientes', [
        'wpcargo_client',
        'subscriber',
        'customer', // WooCommerce si está integrado
    ]);

    $args = [
        'role__in' => $roles_cliente,
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'number'   => $limit,
        'fields'   => 'all',
    ];

    // Búsqueda por nombre, email o login
    if ( $search !== '' ) {
        $args['search']         = '*' . $search . '*';
        $args['search_columns'] = ['user_login', 'user_email', 'display_name', 'user_nicename'];
    }

    $users  = get_users($args);
    $result = [];
    foreach ( $users as $u ) {
        $nombre = trim($u->display_name ?: $u->user_login);
        $result[] = [
            'id'    => $u->ID,
            'label' => $nombre,
            'email' => $u->user_email,
            'text'  => "{$nombre} — {$u->user_email}", // formato Select2
        ];
    }
    return $result;
}

/**
 * @deprecated Usar wcmas_get_clientes_select() — mantenido por compatibilidad.
 */
function wcmas_get_usuarios_select(): array {
    return wcmas_get_clientes_select();
}

/**
 * Devuelve los datos de remitente de un usuario (cliente) guardados en
 * su perfil de WPCargo (usermeta), para autocompletar el formulario.
 *
 * WPCargo Frontend guarda los datos del shipper en usermeta con prefijo
 * "wpcfe_shipper_" o directamente en los campos del perfil de WP.
 */
function wcmas_get_datos_remitente( int $user_id ): array {
    if ( ! $user_id ) return [];
    $u = get_userdata($user_id);
    if ( ! $u ) return [];

    /**
     * Meta keys exactas de WPCargo para datos del remitente (cliente).
     * Fuente: configuración proporcionada por el administrador del sitio.
     *
     * Nombre de marca:    wpcargo_tiendaname  o  billing_company
     * Celular:            wpcargo_shipper_phone
     * Dirección:          wpcargo_shipper_address
     * Distrito de Recojo: wpcargo_distrito_recojo
     * Link Google Maps:   link_maps_remitente
     */

    // Nombre / marca: priorizar wpcargo_tiendaname, luego billing_company
    $nombre = get_user_meta($user_id, 'wpcargo_tiendaname', true);
    if ( ! $nombre ) $nombre = get_user_meta($user_id, 'billing_company', true);
    if ( ! $nombre ) $nombre = trim($u->first_name . ' ' . $u->last_name) ?: $u->display_name;

    // Celular
    $telefono = get_user_meta($user_id, 'wpcargo_shipper_phone', true);
    if ( ! $telefono ) $telefono = get_user_meta($user_id, 'billing_phone', true);

    // Dirección
    $direccion = get_user_meta($user_id, 'wpcargo_shipper_address', true);
    if ( ! $direccion ) $direccion = get_user_meta($user_id, 'billing_address_1', true);

    // Distrito de recojo
    $distrito = get_user_meta($user_id, 'wpcargo_distrito_recojo', true);

    // Link Google Maps
    $link_maps = get_user_meta($user_id, 'link_maps_remitente', true);

    $datos = [
        'nombre'    => $nombre,
        'telefono'  => $telefono    ?: '',
        'direccion' => $direccion   ?: '',
        'distrito'  => $distrito    ?: '',
        'link_maps' => $link_maps   ?: '',
        'email'     => $u->user_email,
    ];

    return apply_filters('wcmas_datos_remitente', $datos, $user_id);
}


