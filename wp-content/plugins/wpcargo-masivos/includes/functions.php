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
    global $wpdb;

    // ── Prioridad 1: usar la función nativa de WPCargo si existe ──────────────
    // WPCargo exporta distintos nombres según versión del plugin
    $funciones_wpcargo = [
        'wpcargo_generate_tracking_number',  // nombre más común
        'wpcargo_auto_generate_title',        // versión alternativa
        'wpcargo_get_auto_title',             // otra variante
    ];
    foreach ( $funciones_wpcargo as $fn ) {
        if ( function_exists($fn) ) {
            $tracking = $fn();
            if ( $tracking && is_string($tracking) && strlen($tracking) > 2 ) {
                return $tracking;
            }
        }
    }

    // ── Prioridad 2: replicar exactamente el sistema de WPCargo ──────────────
    // CONFIRMADO en BD:
    //   - Config en wpcargo_option_settings: wpcargo_title_prefix = "MERC-", action = "on"
    //   - Dígitos en wpcargo_title_numdigit = 6
    //   - Formato resultado: MERC-000061 (prefijo + número 6 dígitos con ceros)
    //   - El número es correlativo al último post_title de wpcargo_shipment
    $cfg    = wcmas_get_wpcargo_tracking_config();
    $prefix = $cfg['prefix'];   // ej: "MERC-"
    $suffix = $cfg['suffix'];   // ej: ""
    $digits = $cfg['digits'];   // ej: 6

    // Buscar el número más alto actual entre TODOS los envíos (no solo los del plugin)
    // WPCargo hace lo mismo para garantizar unicidad
    $ultimo = wcmas_get_ultimo_numero_tracking($prefix, $suffix, $digits);
    $siguiente = $ultimo + 1;
    $numero    = str_pad((string)$siguiente, $digits, '0', STR_PAD_LEFT);

    $tracking = $prefix . $numero . $suffix;

    // Verificar unicidad — si ya existe ese título, incrementar hasta encontrar uno libre
    $intentos = 0;
    while ( $intentos < 100 ) {
        $existe = $wpdb->get_var( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_title = %s AND post_type = 'wpcargo_shipment' LIMIT 1",
            $tracking
        ));
        if ( ! $existe ) break;
        $siguiente++;
        $numero   = str_pad((string)$siguiente, $digits, '0', STR_PAD_LEFT);
        $tracking = $prefix . $numero . $suffix;
        $intentos++;
    }

    return $tracking;
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
 * Lee la configuración de tracking de WPCargo.
 *
 * CONFIRMADO en BD: WPCargo guarda la config en el array serializado 'wpcargo_option_settings':
 *   wpcargo_title_prefix        -> prefijo (ej: "MERC-")
 *   wpcargo_title_prefix_action -> "on" si el prefijo está activo
 *   wpcargo_title_suffix        -> sufijo (vacío en este caso)
 * Y en opción separada:
 *   wpcargo_title_numdigit      -> número de dígitos (ej: 6)
 *
 * Formato resultante confirmado en BD: MERC-000061
 *
 * @return array { prefix, suffix, digits, type, preview }
 */
function wcmas_get_wpcargo_tracking_config(): array {
    // Leer el array serializado principal de configuración de WPCargo
    $opt = get_option('wpcargo_option_settings', []);
    if ( is_string($opt) ) {
        $opt = maybe_unserialize($opt);
    }
    if ( ! is_array($opt) ) $opt = [];

    // Prefijo: activo solo si wpcargo_title_prefix_action = 'on'
    $prefix_active = ( ($opt['wpcargo_title_prefix_action'] ?? '') === 'on' );
    $prefix        = $prefix_active ? ( $opt['wpcargo_title_prefix'] ?? '' ) : '';

    // Sufijo
    $suffix = $opt['wpcargo_title_suffix'] ?? '';

    // Dígitos: guardado en opción separada 'wpcargo_title_numdigit'
    $digits = intval( get_option('wpcargo_title_numdigit', 0) );
    if ( $digits <= 0 ) $digits = intval( $opt['wpcargo_title_numdigit'] ?? 0 );
    if ( $digits <= 0 ) $digits = 6; // fallback

    // WPCargo siempre usa numérico secuencial con este sistema de prefijo+dígitos
    $type = 'numeric';

    // Fallback: si por alguna razón no hay prefijo configurado
    if ( ! $prefix ) {
        $prefix = get_option('wcmas_tracking_prefix', 'SHIP-');
    }

    $preview = strtoupper($prefix) . str_pad('1', $digits, '0', STR_PAD_LEFT) . strtoupper($suffix);

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
        'customer',
    ]);

    $args_base = [
        'role__in' => $roles_cliente,
        'orderby'  => 'display_name',
        'order'    => 'ASC',
        'number'   => $limit,
        'fields'   => 'all',
    ];

    // Búsqueda por nombre/email/login
    $args = $args_base;
    if ( $search !== '' ) {
        $args['search']         = '*' . $search . '*';
        $args['search_columns'] = ['user_login', 'user_email', 'display_name', 'user_nicename'];
    }
    $users = get_users($args);

    // Búsqueda adicional por wpcargo_tiendaname y billing_company (meta)
    if ( $search !== '' ) {
        $args_meta = array_merge($args_base, [
            'meta_query' => [
                'relation' => 'OR',
                [ 'key' => 'wpcargo_tiendaname', 'value' => $search, 'compare' => 'LIKE' ],
                [ 'key' => 'billing_company',    'value' => $search, 'compare' => 'LIKE' ],
            ],
        ]);
        $users_meta = get_users($args_meta);
        // Combinar y deduplicar
        $ids_vistos = array_map(fn($u) => $u->ID, $users);
        foreach ( $users_meta as $u ) {
            if ( ! in_array($u->ID, $ids_vistos, true) ) {
                $users[]    = $u;
                $ids_vistos[] = $u->ID;
            }
        }
    }

    $result = [];
    foreach ( $users as $u ) {
        // Label: priorizar wpcargo_tiendaname → billing_company → nombre + apellido
        $tienda = get_user_meta($u->ID, 'wpcargo_tiendaname', true)
               ?: get_user_meta($u->ID, 'billing_company', true);

        $nombre_completo = trim(($u->first_name ?? '') . ' ' . ($u->last_name ?? ''))
                        ?: $u->display_name
                        ?: $u->user_login;

        if ( $tienda ) {
            // "VonnCosmetics — Harold Alvarado"
            $label = $tienda . ' — ' . $nombre_completo;
        } else {
            // Sin tienda: solo nombre y apellido
            $label = $nombre_completo;
        }

        $result[] = [
            'id'    => $u->ID,
            'label' => $label,
            'email' => $u->user_email,
            'text'  => $label, // lo que muestra Select2
        ];
    }

    // Ordenar por label y limitar
    usort($result, fn($a, $b) => strcmp($a['label'], $b['label']));
    return array_slice($result, 0, $limit);
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
    // Distrito de recojo — en el perfil del usuario el meta key es 'distrito'
    // (confirmado en form-autofill.js: get_user_meta($uid, 'distrito', true))
    // 'wpcargo_distrito_recojo' es el meta del POST del envío, NO del usuario
    $distrito = get_user_meta($user_id, 'distrito', true);
    if ( ! $distrito ) $distrito = get_user_meta($user_id, 'wpcargo_distrito_recojo', true); // fallback

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



/**
 * Retorna las tarifas configuradas para autocompletar costos en la grilla.
 * Formato guardado en wcmas_tarifas:
 * [ 'Miraflores' => ['normal'=>13.00, 'express'=>18.00, 'full_fitment'=>20.00], ... ]
 *
 * Si no hay tarifas configuradas retorna array vacío y el usuario ingresa el monto manualmente.
 */
function wcmas_get_tarifas(): array {
    $t = get_option('wcmas_tarifas', []);
    if ( ! is_array($t) || empty($t) ) {
        // Si no hay tarifas guardadas, usar las tarifas por defecto precargadas
        return wcmas_get_tarifas_default();
    }
    return $t;
}

/**
 * Guarda/actualiza las tarifas desde el panel de configuración.
 * $tarifas: array [ distrito => [ tipo_servicio => monto ] ]
 */
function wcmas_save_tarifas( array $tarifas ): void {
    // Sanitizar: solo números positivos
    $limpio = [];
    foreach ( $tarifas as $distrito => $tipos ) {
        $d = sanitize_text_field($distrito);
        if ( ! $d || ! is_array($tipos) ) continue;
        foreach ( $tipos as $tipo => $monto ) {
            $limpio[$d][sanitize_key($tipo)] = max(0, floatval($monto));
        }
    }
    update_option('wcmas_tarifas', $limpio, false);
}

/**
 * Tarifas por defecto precargadas para Mercourier.
 * Se instalan automáticamente si wcmas_tarifas está vacío.
 * Fuente: tarifas reales confirmadas por el administrador.
 *
 * Formato: [ distrito => [ tipo_servicio => costo ] ]
 * Tipos: normal (EMPRENDEDOR), express (AGENCIA), full_fitment (FULLFITMENT)
 */
function wcmas_get_tarifas_default(): array {
    return [
        'Ate - Salamanca - Vitarte' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Barranco' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Bellavista' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Breña' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Callao' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Carabayllo' => [
            'normal' => 13.00,
            'express' => 11.00,
            'full_fitment' => 10.00,
        ],
        'Carmen de la Legua' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Centro de Lima' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Chorrillos' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Comas' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'El Agustino' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Huachipa (Zoológico de Huachipa)' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Huaycan' => [
            'normal' => 14.00,
            'express' => 12.00,
            'full_fitment' => 10.00,
        ],
        'Gloria Grande' => [
            'normal' => 14.00,
            'express' => 12.00,
            'full_fitment' => 10.00,
        ],
        'Pariachi' => [
            'normal' => 14.00,
            'express' => 12.00,
            'full_fitment' => 10.00,
        ],
        'Independencia' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Jesús María' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'La Molina' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'La Perla' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'La Punta - Callao' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'La Victoria' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Lima Cercado' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Lince' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Los Olivos' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Magdalena' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Molina Alta - Musa - Portada del Sol - Planicie' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Musa' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Portada del Sol' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Planicie' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Miraflores' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Pueblo Libre' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Puente Piedra' => [
            'normal' => 13.00,
            'express' => 11.00,
            'full_fitment' => 10.00,
        ],
        'Rímac' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Borja' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Isidro' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Juan de Lurigancho' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Juan de Miraflores' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Luis' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Martin de Porres' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'San Miguel' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Santa Anita' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Santa Clara' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Santiago de Surco' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Sarita Colonia (Comisaría Sarita Colonia)' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Surquillo' => [
            'normal' => 10.00,
            'express' => 8.00,
            'full_fitment' => 10.00,
        ],
        'Ventanilla' => [
            'normal' => 13.00,
            'express' => 11.00,
            'full_fitment' => 10.00,
        ],
        'Villa El Salvador' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
        'Villa María del Triunfo' => [
            'normal' => 12.00,
            'express' => 10.00,
            'full_fitment' => 10.00,
        ],
    ];
}

/**
 * Instalar tarifas por defecto si no hay ninguna configurada.
 * Se llama al activar el plugin.
 */
function wcmas_instalar_tarifas_default(): void {
    if ( ! get_option('wcmas_tarifas') ) {
        update_option('wcmas_tarifas', wcmas_get_tarifas_default(), false);
    }
}

/**
/**
 * Busca el contenedor activo para un distrito consultando el mapeo
 * guardado en wp_options ('wcmas_mapa_contenedores').
 *
 * El mapeo es editable desde Admin → Contenedores (página wcmas-contenedores).
 * Formato guardado: [ 'Miraflores' => 3725, 'Barranco' => 3725, ... ]
 */
function wcmas_buscar_contenedor_activo( string $distrito, string $tipo = 'entrega' ): int {
    $distrito = trim($distrito);
    if ( ! $distrito ) return 0;

    $mapa = get_option('wcmas_mapa_contenedores', []);
    if ( ! is_array($mapa) ) $mapa = [];

    // 1. Búsqueda exacta
    if ( isset($mapa[$distrito]) && $mapa[$distrito] ) {
        return (int) $mapa[$distrito];
    }

    // 2. Búsqueda parcial case-insensitive (cubre variantes de tipeo)
    $d_lower = mb_strtolower($distrito, 'UTF-8');
    foreach ( $mapa as $key => $id ) {
        if ( ! $id ) continue;
        $k_lower = mb_strtolower((string)$key, 'UTF-8');
        if ( str_contains($k_lower, $d_lower) || str_contains($d_lower, $k_lower) ) {
            return (int) $id;
        }
    }

    return 0; // Sin match → no asignar contenedor (mejor que asignar uno incorrecto)
}

/**
 * Devuelve el mapa contenedor por defecto basado en los 13 contenedores conocidos.
 * Se usa solo al instalar/resetear. Los IDs de contenedor son los de producción.
 * El admin puede corregirlos desde la página de Contenedores.
 */
function wcmas_get_mapa_contenedores_default(): array {
    return [
        // RUTA NORTE 1 — 3706
        'Carabayllo'                                        => 3706,
        'Comas'                                             => 3706,
        'Independencia'                                     => 3706,
        // RUTA NORTE 2 — 3707
        'Puente Piedra'                                     => 3707,
        'Los Olivos'                                        => 3707,
        // RUTA NORTE 3 — 3721
        'Rímac'                                             => 3721,
        'San Martin de Porres'                              => 3721,
        // RUTA OESTE 5 — 3722
        'Carmen de la Legua'                                => 3722,
        'Breña'                                             => 3722,
        'Ventanilla'                                        => 3722,
        'Lima Cercado'                                      => 3722,
        // RUTA OESTE 4 — 3723
        'La Perla'                                          => 3723,
        'Bellavista'                                        => 3723,
        'La Punta - Callao'                                 => 3723,
        'San Miguel'                                        => 3723,
        'Pueblo Libre'                                      => 3723,
        'Callao'                                            => 3723,
        'Sarita Colonia (Comisaría Sarita Colonia)'         => 3723,
        // RUTA CENTRO 6 — 3724
        'Magdalena'                                         => 3724,
        'Jesús María'                                       => 3724,
        'Lince'                                             => 3724,
        'San Isidro'                                        => 3724,
        // RUTA SUR 7 — 3725
        'Chorrillos'                                        => 3725,
        'Barranco'                                          => 3725,
        'Miraflores'                                        => 3725,
        // RUTA SUR 8 — 3726
        'Villa El Salvador'                                 => 3726,
        'Villa María del Triunfo'                           => 3726,
        'San Juan de Miraflores'                            => 3726,
        // RUTA SUR 9 — 3727
        'Santiago de Surco'                                 => 3727,
        'Surquillo'                                         => 3727,
        // RUTA ESTE 10 — 3728
        'Ate - Salamanca - Vitarte'                         => 3728,
        'Santa Anita'                                       => 3728,
        'Santa Clara'                                       => 3728,
        'Huachipa (Zoológico de Huachipa)'                  => 3728,
        // RUTA ESTE 11 — 3731
        'La Molina'                                         => 3731,
        'San Borja'                                         => 3731,
        'San Luis'                                          => 3731,
        'Molina Alta - Musa - Portada del Sol - Planicie'   => 3731,
        // RUTA ESTE 12 — 3729
        'El Agustino'                                       => 3729,
        'San Juan de Lurigancho'                            => 3729,
        // RUTA CENTRO 13 — 3730
        'La Victoria'                                       => 3730,
    ];
}

/**
 * Instala el mapa de contenedores default si no existe aún.
 */
function wcmas_instalar_mapa_contenedores(): void {
    if ( ! get_option('wcmas_mapa_contenedores') ) {
        update_option('wcmas_mapa_contenedores', wcmas_get_mapa_contenedores_default(), false);
    }
}
 
