<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCROL_Admin {

    public function __construct() {
        add_action('admin_menu',    [$this, 'registrar_menu']);
        add_action('admin_notices', [$this, 'mostrar_notice']);

        add_action('admin_post_wcrol_guardar_permisos',     [$this, 'handle_guardar_permisos']);
        add_action('admin_post_wcrol_quitar_restricciones', [$this, 'handle_quitar_restricciones']);
        add_action('admin_post_wcrol_cambiar_tipo',         [$this, 'handle_cambiar_tipo']);
        add_action('admin_post_wcrol_guardar_modulo',       [$this, 'handle_guardar_modulo']);
        add_action('admin_post_wcrol_eliminar_modulo',      [$this, 'handle_eliminar_modulo']);
        add_action('admin_post_wcrol_sincronizar',          [$this, 'handle_sincronizar']);
    }

    public function registrar_menu(): void {
        add_menu_page('Roles WPCargo','Roles & Accesos','manage_options',
            'wcrol-usuarios',[$this,'pagina_usuarios'],'dashicons-shield',57);
        add_submenu_page('wcrol-usuarios','Usuarios','Usuarios',
            'manage_options','wcrol-usuarios',[$this,'pagina_usuarios']);
        add_submenu_page('wcrol-usuarios','Módulos del Sidebar','Módulos del Sidebar',
            'manage_options','wcrol-modulos',[$this,'pagina_modulos']);
    }

    public function pagina_usuarios(): void {
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $edit_uid   = intval($_GET['usuario'] ?? 0);
        $usuario    = $edit_uid ? get_userdata($edit_uid) : null;
        $usuarios   = WCROL_Permisos::obtener_usuarios();
        $modulos    = WCROL_Modulos::obtener_todos();
        $permisos_u = $edit_uid ? WCROL_Permisos::obtener($edit_uid) : null;
        wcrol_tpl('admin/usuarios.tpl.php', compact('edit_uid','usuario','usuarios','modulos','permisos_u'));
    }

    public function pagina_modulos(): void {
        if ( ! current_user_can('manage_options') ) wp_die('Sin permisos.');
        $edit_slug = sanitize_key($_GET['editar'] ?? '');
        $modulo    = $edit_slug ? WCROL_Modulos::obtener($edit_slug) : null;
        $modulos   = WCROL_Modulos::obtener_todos();
        $paginas   = get_posts([
            'post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,
            'meta_query'=>[['key'=>'_wp_page_template','value'=>'dashboard.php']]
        ]);
        $capturado = get_transient(WCROL_Modulos::CAPTURE_KEY);
        wcrol_tpl('admin/modulos.tpl.php', compact('edit_slug','modulo','modulos','paginas','capturado'));
    }

    /* ── Handlers ────────────────────────────────────────────────── */

    public function handle_guardar_permisos(): void {
        check_admin_referer('wcrol_permisos_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();
        $user_id = intval($_POST['user_id'] ?? 0);
        if ( ! $user_id ) wcrol_redirect('wcrol-usuarios','error_req');
        WCROL_Permisos::guardar($user_id, array_map('sanitize_key', $_POST['modulos'] ?? []));
        wcrol_redirect('wcrol-usuarios','guardado',['usuario'=>$user_id]);
    }

    public function handle_quitar_restricciones(): void {
        check_admin_referer('wcrol_permisos_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();
        $user_id = intval($_POST['user_id'] ?? 0);
        WCROL_Permisos::quitar_restricciones($user_id);
        wcrol_redirect('wcrol-usuarios','guardado',['usuario'=>$user_id]);
    }

    public function handle_cambiar_tipo(): void {
        check_admin_referer('wcrol_tipo_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();
        $user_id = intval($_POST['user_id'] ?? 0);
        $tipo    = sanitize_key($_POST['tipo_acceso'] ?? '');
        if ( ! $user_id || ! $tipo ) wcrol_redirect('wcrol-usuarios','error_req');
        $ok = WCROL_Rol_WPCargo::cambiar_tipo($user_id, $tipo);
        wcrol_redirect('wcrol-usuarios', $ok ? 'guardado' : 'error_propio', ['usuario'=>$user_id]);
    }

    public function handle_guardar_modulo(): void {
        check_admin_referer('wcrol_modulo_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();
        $r = WCROL_Modulos::guardar([
            'slug'        => sanitize_key($_POST['slug'] ?? ''),
            'label'       => sanitize_text_field(wp_unslash($_POST['label'] ?? '')),
            'icon'        => sanitize_text_field(wp_unslash($_POST['icon']  ?? 'fa-circle-o')),
            'page_id'     => intval($_POST['page_id'] ?? 0),
            'sidebar_key' => sanitize_key($_POST['sidebar_key'] ?? ''),
        ], sanitize_key($_POST['slug_original'] ?? ''));
        wcrol_redirect('wcrol-modulos', is_wp_error($r) ? 'error_req' : 'guardado');
    }

    public function handle_eliminar_modulo(): void {
        check_admin_referer('wcrol_modulo_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();
        WCROL_Modulos::eliminar(sanitize_key($_POST['slug'] ?? ''));
        wcrol_redirect('wcrol-modulos','eliminado');
    }

    public function handle_sincronizar(): void {
        check_admin_referer('wcrol_sync_nonce');
        if ( ! current_user_can('manage_options') ) wp_die();
        $nuevos = WCROL_Modulos::sincronizar();
        wcrol_redirect('wcrol-modulos', 'sincronizado', ['nuevos'=>$nuevos]);
    }

    public function mostrar_notice(): void {
        $key = sanitize_key($_GET['wcrol_msg'] ?? '');
        if (!$key) return;
        $nuevos = intval($_GET['nuevos'] ?? 0);
        $msgs = [
            'guardado'     => ['success','Guardado correctamente.'],
            'eliminado'    => ['success','Eliminado correctamente.'],
            'sincronizado' => ['success',"Sincronizado. {$nuevos} módulo(s) nuevo(s) encontrado(s). <strong>Nota:</strong> Para capturar módulos de plugins, navega al dashboard de WPCargo primero y luego sincroniza."],
            'error_req'    => ['error',  'Faltan campos obligatorios.'],
            'error_propio' => ['error',  'No puedes modificar tu propio tipo de acceso.'],
        ];
        if (isset($msgs[$key])) {
            [$t,$m] = $msgs[$key];
            echo "<div class='notice notice-{$t} is-dismissible'><p>{$m}</p></div>";
        }
    }
}

new WCROL_Admin();
