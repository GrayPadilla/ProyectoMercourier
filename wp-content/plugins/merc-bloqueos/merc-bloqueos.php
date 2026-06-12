<?php
/**
 * Plugin Name: Mercourier Bloqueos V2
 * Description: Control avanzado de horarios y calendarios para envíos Mercourier (Emprendedor, Agencia, Full Fitment).
 * Version: 2.0.0
 * Author: Mercourier
 */

if (!defined('ABSPATH')) {
    exit;
}

// Constantes
define('MERC_BLOQUEOS_VERSION', '2.0.0');
define('MERC_BLOQUEOS_DIR', plugin_dir_path(__FILE__));
define('MERC_BLOQUEOS_URL', plugin_dir_url(__FILE__));

// Cargar módulos
require_once MERC_BLOQUEOS_DIR . 'includes/class-settings.php';
require_once MERC_BLOQUEOS_DIR . 'includes/class-block-logic.php';
require_once MERC_BLOQUEOS_DIR . 'includes/class-ajax.php';
require_once MERC_BLOQUEOS_DIR . 'includes/class-save-guard.php';
require_once MERC_BLOQUEOS_DIR . 'includes/class-user-controls.php';

// Inicializar el plugin
function merc_bloqueos_init() {
    new Merc_Bloqueos_Settings();
    new Merc_Bloqueos_Ajax();
    new Merc_Bloqueos_Save_Guard();
    new Merc_Bloqueos_User_Controls();
}
add_action('plugins_loaded', 'merc_bloqueos_init');

// Cargar scripts en el frontend
function merc_bloqueos_enqueue_scripts() {
    if (!is_user_logged_in()) {
        return;
    }

    // No cargar en la página de Envíos Masivos.
    // calendar-block.js tiene una guarda JS por pathname, pero es frágil.
    // Esta guard PHP es definitiva: usa el ID real del post de WordPress.
    $masivos_page_id = (int) get_option('wcmas_frontend_page_id');
    if ($masivos_page_id && is_page($masivos_page_id)) {
        return;
    }

    $user = wp_get_current_user();
    
    // Inyectar script inline para evitar cualquier problema de caché o de enqueue
    ?>
    <script>
        var mercBloqueos = {
            "ajax_url": "<?php echo esc_js(admin_url('admin-ajax.php')); ?>",
            "client_id": <?php echo esc_js($user->ID); ?>,
            "is_admin": <?php echo current_user_can('manage_options') ? 'true' : 'false'; ?>
        };
    </script>
    <script src="<?php echo esc_url(MERC_BLOQUEOS_URL . 'assets/js/calendar-block.js?v=' . time()); ?>"></script>
    <?php
}
add_action('wp_footer', 'merc_bloqueos_enqueue_scripts', 9999);

