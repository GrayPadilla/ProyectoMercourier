<?php
/**
 * Plugin Name: WPCargo Roles & Accesos
 * Plugin URI:  https://dhvcourier.com
 * Description: Gestión de acceso a módulos del dashboard WPCargo y control de acceso a wp-admin por usuario.
 * Version:     1.1.0
 * Author:      DHV Courier
 * Text Domain: wpcargo-roles
 * Requires PHP: 8.1
 */
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'WCROL_VERSION',  '1.1.0' );
define( 'WCROL_PATH',     plugin_dir_path( __FILE__ ) );
define( 'WCROL_URL',      plugin_dir_url( __FILE__ ) );
define( 'WCROL_BASENAME', plugin_basename( __FILE__ ) );

require_once WCROL_PATH . 'includes/functions.php';
require_once WCROL_PATH . 'includes/class-modulos.php';
require_once WCROL_PATH . 'includes/class-permisos.php';
require_once WCROL_PATH . 'includes/class-rol-wpcargo.php';
require_once WCROL_PATH . 'admin/classes/class-admin.php';
require_once WCROL_PATH . 'admin/classes/class-frontend.php';
require_once WCROL_PATH . 'admin/classes/class-filtro.php';

register_activation_hook(   __FILE__, 'wcrol_activar'    );
register_deactivation_hook( __FILE__, 'wcrol_desactivar' );

function wcrol_activar(): void {
    WCROL_Modulos::registrar_defaults();
    WCROL_Rol_WPCargo::crear_rol();
    wcrol_get_frontend_page_id(); // Crear página del dashboard
}

function wcrol_desactivar(): void {
    // No eliminamos el rol para no perder usuarios asignados
}
