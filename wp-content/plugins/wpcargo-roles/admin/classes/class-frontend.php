<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class WCROL_Frontend {

    public function __construct() {
        add_shortcode('wcrol-roles',            [$this, 'render_shortcode']);
        add_filter('wpcfe_after_sidebar_menus', [$this, 'sidebar_item'], 28, 1);

        add_action('admin_post_wcrol_fe_guardar_permisos',    [$this, 'handle_guardar_permisos']);
        add_action('admin_post_wcrol_fe_quitar_restricciones',[$this, 'handle_quitar_restricciones']);
        add_action('admin_post_wcrol_fe_cambiar_tipo',        [$this, 'handle_cambiar_tipo']);
        add_action('admin_post_wcrol_fe_sincronizar',         [$this, 'handle_sincronizar']);
        add_action('admin_post_wcrol_fe_guardar_modulo',      [$this, 'handle_guardar_modulo']);
        add_action('admin_post_wcrol_fe_eliminar_modulo',     [$this, 'handle_eliminar_modulo']);
    }

    public function sidebar_item( array $menu ): array {
        if ( ! wcrol_puede_gestionar() ) return $menu;
        $menu['wcrol-roles'] = [
            'page-id'   => wcrol_get_frontend_page_id(),
            'label'     => 'Roles & Accesos',
            'permalink' => wcrol_frontend_url(),
            'icon'      => 'fa-shield',
        ];
        return $menu;
    }

    public function render_shortcode(): string {
        if ( ! wcrol_puede_gestionar() ) {
            return '<div class="alert alert-warning"><i class="fa fa-lock mr-2"></i>Acceso restringido a administradores.</div>';
        }
        ob_start();
        $vista    = sanitize_key($_GET['wcrol_vista'] ?? 'usuarios');
        $edit_uid = intval($_GET['usuario'] ?? 0);
        match ($vista) {
            'modulos' => $this->render_modulos(),
            default   => $this->render_usuarios($edit_uid),
        };
        return ob_get_clean();
    }

    private function render_usuarios( int $edit_uid ): void {
        $usuario    = $edit_uid ? get_userdata($edit_uid) : null;
        $usuarios   = WCROL_Permisos::obtener_usuarios();
        $modulos    = WCROL_Modulos::obtener_todos();
        $permisos_u = $edit_uid ? WCROL_Permisos::obtener($edit_uid) : null;
        $page_url   = wcrol_frontend_url();
        $msg        = sanitize_key($_GET['wcrol_msg'] ?? '');
        wcrol_tpl('frontend/usuarios.tpl.php', compact('edit_uid','usuario','usuarios','modulos','permisos_u','page_url','msg'));
    }

    private function render_modulos(): void {
        $edit_slug = sanitize_key($_GET['editar'] ?? '');
        $modulo    = $edit_slug ? WCROL_Modulos::obtener($edit_slug) : null;
        $modulos   = WCROL_Modulos::obtener_todos();
        $paginas   = get_posts([
            'post_type'=>'page','post_status'=>'publish','posts_per_page'=>-1,
            'meta_query'=>[['key'=>'_wp_page_template','value'=>'dashboard.php']]
        ]);
        $capturado = get_transient(WCROL_Modulos::CAPTURE_KEY);
        $page_url  = wcrol_frontend_url();
        wcrol_tpl('frontend/modulos.tpl.php', compact('edit_slug','modulo','modulos','paginas','capturado','page_url'));
    }

    /* ── Handlers ───────────────────────────────────────────────── */

    public function handle_guardar_permisos(): void {
        check_admin_referer('wcrol_fe_permisos_nonce');
        if ( ! wcrol_puede_gestionar() ) wp_die();
        $user_id = intval($_POST['user_id'] ?? 0);
        if (!$user_id) { wp_safe_redirect(wcrol_frontend_url(['wcrol_msg'=>'error_req'])); exit; }
        WCROL_Permisos::guardar($user_id, array_map('sanitize_key', $_POST['modulos'] ?? []));
        wp_safe_redirect(wcrol_frontend_url(['usuario'=>$user_id,'wcrol_msg'=>'guardado']));
        exit;
    }

    public function handle_quitar_restricciones(): void {
        check_admin_referer('wcrol_fe_permisos_nonce');
        if ( ! wcrol_puede_gestionar() ) wp_die();
        $user_id = intval($_POST['user_id'] ?? 0);
        WCROL_Permisos::quitar_restricciones($user_id);
        wp_safe_redirect(wcrol_frontend_url(['usuario'=>$user_id,'wcrol_msg'=>'guardado']));
        exit;
    }

    public function handle_cambiar_tipo(): void {
        check_admin_referer('wcrol_fe_tipo_nonce');
        if ( ! wcrol_puede_gestionar() ) wp_die();
        $user_id = intval($_POST['user_id'] ?? 0);
        $tipo    = sanitize_key($_POST['tipo_acceso'] ?? '');
        if (!$user_id || !$tipo) { wp_safe_redirect(wcrol_frontend_url(['wcrol_msg'=>'error_req'])); exit; }
        $ok = WCROL_Rol_WPCargo::cambiar_tipo($user_id, $tipo);
        wp_safe_redirect(wcrol_frontend_url(['usuario'=>$user_id,'wcrol_msg'=> $ok ? 'guardado' : 'error_propio']));
        exit;
    }

    public function handle_sincronizar(): void {
        check_admin_referer('wcrol_fe_sync_nonce');
        if ( ! wcrol_puede_gestionar() ) wp_die();
        $nuevos = WCROL_Modulos::sincronizar();
        wp_safe_redirect(wcrol_frontend_url(['wcrol_vista'=>'modulos','wcrol_msg'=>'sincronizado','nuevos'=>$nuevos]));
        exit;
    }

    public function handle_guardar_modulo(): void {
        check_admin_referer('wcrol_fe_modulo_nonce');
        if ( ! wcrol_puede_gestionar() ) wp_die();
        $r = WCROL_Modulos::guardar([
            'slug'        => sanitize_key($_POST['slug'] ?? ''),
            'label'       => sanitize_text_field(wp_unslash($_POST['label'] ?? '')),
            'icon'        => sanitize_text_field(wp_unslash($_POST['icon']  ?? 'fa-circle-o')),
            'page_id'     => intval($_POST['page_id'] ?? 0),
            'sidebar_key' => sanitize_key($_POST['sidebar_key'] ?? ''),
        ], sanitize_key($_POST['slug_original'] ?? ''));
        $msg = is_wp_error($r) ? 'error_req' : 'guardado';
        wp_safe_redirect(wcrol_frontend_url(['wcrol_vista'=>'modulos','wcrol_msg'=>$msg]));
        exit;
    }

    public function handle_eliminar_modulo(): void {
        check_admin_referer('wcrol_fe_modulo_nonce');
        if ( ! wcrol_puede_gestionar() ) wp_die();
        WCROL_Modulos::eliminar(sanitize_key($_POST['slug'] ?? ''));
        wp_safe_redirect(wcrol_frontend_url(['wcrol_vista'=>'modulos','wcrol_msg'=>'eliminado']));
        exit;
    }
}

new WCROL_Frontend();
