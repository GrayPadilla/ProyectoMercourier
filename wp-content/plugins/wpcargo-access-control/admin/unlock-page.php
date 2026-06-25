<?php
/**
 * Unlock Admin Page Module
 * Render the WP-Admin page for managing client unlock status
 *
 * @package WPCargo_Access_Control
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the unlock control page
 */
function wpcac_render_unlock_page() {
    $current_user = wp_get_current_user();
    $is_merc_admin = $current_user->user_email === 'mercourier2019@gmail.com';
    
    if (!current_user_can('manage_options') && !$is_merc_admin) {
        wp_die('No tienes permisos para acceder a esta página');
    }
    
    $today = wpcac_get_today();
    $skip_enabled = wpcac_is_bypass_enabled_today();
    
    // Get count of clients
    $client_count = count(get_users(array('role' => 'wpcargo_client', 'fields' => 'ID', 'number' => -1)));
    ?>
    <div class="wrap">
        <h1>🔐 Control de Desbloqueo - Skip Blocks</h1>
        
        <div class="notice notice-info" style="margin-top: 20px;">
            <p>
                <strong>Descripción:</strong> Esta página permite desbloquear manualmente todos los clientes por hoy.
                Cuando está activado, todos los clientes pueden crear envíos sin restricciones.
            </p>
        </div>
        
        <div style="background: #ffffff; padding: 30px; border: 1px solid #ccc; border-radius: 8px; max-width: 700px; margin-top: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
            
            <!-- Estado Actual -->
            <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee;">
                <h2 style="margin-top: 0;">📊 Estado Actual</h2>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="padding: 8px; font-weight: 600;">Fecha Hoy:</td>
                        <td style="padding: 8px; color: #0073aa;"><code><?php echo esc_html($today); ?></code></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; font-weight: 600;">Estado:</td>
                        <td style="padding: 8px;">
                            <span style="display: inline-block; padding: 6px 12px; border-radius: 4px; font-weight: 600; color: white; background: <?php echo $skip_enabled ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo $skip_enabled ? '✅ DESBLOQUEADO' : '❌ BLOQUEADO'; ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; font-weight: 600;">Clientes Afectados:</td>
                        <td style="padding: 8px;"><strong><?php echo $client_count; ?></strong> clientes registrados</td>
                    </tr>
                </table>
            </div>
            
            <!-- Controles -->
            <div style="margin-bottom: 20px;">
                <h3 style="margin-top: 0;">🎮 Controles</h3>
                
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <?php wp_nonce_field('wpcac_toggle_unlock_action', 'wpcac_toggle_unlock_nonce'); ?>
                    <input type="hidden" name="action" value="wpcac_toggle_unlock_post">
                    
                    <?php if (!$skip_enabled): ?>
                        <input type="hidden" name="toggle_action" value="enable">
                        <button type="submit" class="button button-primary button-large" style="background: #28a745; border-color: #28a745; padding: 12px 30px; font-size: 16px; cursor: pointer; color: white;">
                            🔓 DESBLOQUEAR TODOS LOS CLIENTES
                        </button>
                        <p style="color: #666; font-size: 13px; margin-top: 10px;">
                            Al hacer clic, todos los clientes podrán crear envíos sin restricciones hoy.
                        </p>
                    <?php else: ?>
                        <input type="hidden" name="toggle_action" value="disable">
                        <button type="submit" class="button button-secondary button-large" style="background: #dc3545; border-color: #dc3545; padding: 12px 30px; font-size: 16px; cursor: pointer; color: white;">
                            🔒 BLOQUEAR CLIENTES
                        </button>
                        <p style="color: #666; font-size: 13px; margin-top: 10px;">
                            Al hacer clic, se aplicarán nuevamente las restricciones normales.
                        </p>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Info -->
            <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px;">
                <h4 style="margin-top: 0; color: #0073aa;">ℹ️ Información</h4>
                <ul style="margin: 0; padding-left: 20px;">
                    <li><strong>Desbloqueado:</strong> Los clientes pueden crear envíos sin límites</li>
                    <li><strong>Bloqueado:</strong> Se aplican restricciones por horario y tipo de envío</li>
                    <li><strong>Automático:</strong> El estado se resetea después de medianoche</li>
                    <li><strong>Registro:</strong> Todos los cambios se guardan en error_log</li>
                </ul>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Handle admin-post action for toggle
 */
function wpcac_handle_toggle_unlock_post() {
    $current_user = wp_get_current_user();
    $is_merc_admin = $current_user->user_email === 'mercourier2019@gmail.com';
    
    if (!current_user_can('manage_options') && !$is_merc_admin) {
        wp_die('No tienes permisos');
    }
    
    // Verify nonce
    if (!isset($_POST['wpcac_toggle_unlock_nonce']) || !wp_verify_nonce($_POST['wpcac_toggle_unlock_nonce'], 'wpcac_toggle_unlock_action')) {
        wp_die('Nonce inválido');
    }
    
    $action = sanitize_text_field($_POST['toggle_action'] ?? '');
    
    if ($action === 'enable') {
        // Enable MON-SAT bypass when using the admin form
        update_option('merc_skip_blocks_today', wpcac_get_today());
        update_option('merc_skip_blocks_mode', 'mon-sat');
        wpcac_apply_skip_to_all_clients(true);
        error_log("✅ Admin activó bypass de bloqueos (MON-SAT) (form): " . wpcac_get_today());
    } elseif ($action === 'disable') {
        update_option('merc_skip_blocks_today', '');
        update_option('merc_skip_blocks_mode', '');
        wpcac_apply_skip_to_all_clients(false);
        error_log("❌ Admin desactivó bypass de bloqueos (form)");
    }
    
    $redirect = wp_get_referer() ?: admin_url('tools.php?page=wpcac-skip-blocks');
    wp_safe_redirect($redirect);
    exit;
}

add_action('admin_post_wpcac_toggle_unlock_post', 'wpcac_handle_toggle_unlock_post');


