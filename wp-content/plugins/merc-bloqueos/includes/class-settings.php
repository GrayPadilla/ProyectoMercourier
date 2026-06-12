<?php
/**
 * Configuración del plugin (Backend y Frontend Shortcode)
 */

if (!defined('ABSPATH')) {
    exit;
}

class Merc_Bloqueos_Settings {
    
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_shortcode('merc_bloqueos_settings', [$this, 'render_shortcode']);
        
        // Manejar guardado desde el frontend shortcode
        add_action('admin_post_merc_save_frontend_settings', [$this, 'handle_frontend_save']);

        // Agregar al menú de WPCargo (Frontend)
        add_action('wpcfe_after_sidebar_custom_menu', [$this, 'add_frontend_sidebar_menu']);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            'Bloqueos Merc',
            'Bloqueos Merc',
            'manage_options',
            'merc-bloqueos',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Horarios Emprendedor
        register_setting('merc_bloqueos_options', 'merc_hora_emprendedor_sin_pedidos');
        register_setting('merc_bloqueos_options', 'merc_hora_emprendedor_con_pedidos');
        
        // Horarios Agencia
        register_setting('merc_bloqueos_options', 'merc_hora_agencia_sin_pedidos');
        register_setting('merc_bloqueos_options', 'merc_hora_agencia_con_pedidos');
        
        // Horarios Full Fitment
        register_setting('merc_bloqueos_options', 'merc_hora_full_sin_pedidos');
        register_setting('merc_bloqueos_options', 'merc_hora_full_con_pedidos');

        // Fechas y domingos
        register_setting('merc_bloqueos_options', 'merc_bloqueos_fechas_especiales');
        register_setting('merc_bloqueos_options', 'merc_bloqueos_domingos_desbloqueados');

        // URL del Frontend
        register_setting('merc_bloqueos_options', 'merc_bloqueos_frontend_url');
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Configuración de Bloqueos V2</h1>
            <?php $this->render_form_content(true); ?>
        </div>
        <?php
    }

    public function render_shortcode($atts) {
        if (!current_user_can('manage_options')) {
            return '<p>No tienes permiso para ver esta configuración.</p>';
        }

        ob_start();
        ?>
        <div class="merc-bloqueos-frontend-settings" style="background:#fff; padding:30px; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.05); font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;">
            <h2 style="color:#2c3e50; border-bottom:2px solid #f0f2f5; padding-bottom:15px; margin-top:0; font-weight: 600; display:flex; align-items:center; gap:10px;">
                <i class="fa fa-clock-o" style="color:#3498db;"></i> Configuración de Bloqueos V2
            </h2>
            
            <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true'): ?>
                <div style="background:#d4edda; color:#155724; padding:15px 20px; border-radius:8px; margin-bottom:25px; border-left:4px solid #28a745; display:flex; align-items:center; gap:10px;">
                    <i class="fa fa-check-circle"></i> Ajustes guardados correctamente.
                </div>
            <?php endif; ?>

            <?php $this->render_form_content(false); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_form_content($is_backend = true) {
        $action_url = $is_backend ? 'options.php' : admin_url('admin-post.php');
        
        // Valores por defecto
        $h_emp_sin = get_option('merc_hora_emprendedor_sin_pedidos', '10:30');
        $h_emp_con = get_option('merc_hora_emprendedor_con_pedidos', '10:30');
        $h_ag_sin  = get_option('merc_hora_agencia_sin_pedidos', '12:30');
        $h_ag_con  = get_option('merc_hora_agencia_con_pedidos', '12:30');
        $h_fu_sin  = get_option('merc_hora_full_sin_pedidos', '12:30');
        $h_fu_con  = get_option('merc_hora_full_con_pedidos', '12:30');

        $fechas = get_option('merc_bloqueos_fechas_especiales', []);
        if (!is_array($fechas)) $fechas = [];

        $domingos = get_option('merc_bloqueos_domingos_desbloqueados', []);
        if (!is_array($domingos)) $domingos = [];
        
        $frontend_url = get_option('merc_bloqueos_frontend_url', '');
        ?>
        <form method="post" action="<?php echo esc_url($action_url); ?>">
            <?php 
            if ($is_backend) {
                settings_fields('merc_bloqueos_options'); 
            } else {
                wp_nonce_field('merc_save_frontend_settings', 'merc_nonce');
                echo '<input type="hidden" name="action" value="merc_save_frontend_settings">';
                // Enviar la URL actual para redirigir de vuelta
                global $wp;
                $current_url = home_url(add_query_arg(array(), $wp->request));
                echo '<input type="hidden" name="redirect_url" value="'.esc_url($current_url).'">';
            }
            ?>
            
            <style>
                .merc-form-section { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 25px; border: 1px solid #e9ecef; }
                .merc-form-title { font-size: 18px; color: #34495e; margin-top: 0; margin-bottom: 15px; font-weight: 600; border-bottom: 1px solid #dee2e6; padding-bottom: 10px; }
                .merc-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; background: #fff; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.02); }
                .merc-table th, .merc-table td { padding: 12px 15px; border-bottom: 1px solid #edf2f7; text-align: left; vertical-align: middle; }
                .merc-table th { background: #f1f5f9; color: #475569; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 0.5px; }
                .merc-table tr:last-child td { border-bottom: none; }
                .merc-table td strong { color: #1e293b; }
                .merc-input-time { padding: 8px 12px; width: 130px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 15px; color: #334155; transition: border-color 0.2s; outline: none; }
                .merc-input-time:focus { border-color: #3b82f6; box-shadow: 0 0 0 2px rgba(59,130,246,0.1); }
                .merc-input-date { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 15px; color: #334155; outline: none; }
                .merc-btn-remove { background: #ef4444; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; margin-left: 10px; font-weight: bold; transition: background 0.2s; }
                .merc-btn-remove:hover { background: #dc2626; }
                .merc-btn-add { background: #f1f5f9; color: #475569; border: 1px dashed #cbd5e1; padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; width: 100%; text-align: center; transition: all 0.2s; display: block; margin-top: 10px; }
                .merc-btn-add:hover { background: #e2e8f0; color: #1e293b; border-color: #94a3b8; }
                .merc-date-row { display: flex; align-items: center; margin-bottom: 10px; background: #fff; padding: 10px; border-radius: 4px; border: 1px solid #edf2f7; }
                .merc-submit-btn { background: #2563eb; color: #fff; border: none; padding: 12px 24px; font-size: 16px; font-weight: 600; border-radius: 6px; cursor: pointer; transition: background 0.2s; box-shadow: 0 4px 6px rgba(37,99,235,0.2); }
                .merc-submit-btn:hover { background: #1d4ed8; }
            </style>

            <div class="merc-form-section">
                <h3 class="merc-form-title"><i class="fa fa-clock-o" style="color:#64748b; margin-right:8px;"></i> 1. Horarios de Cierre (Formato 24h)</h3>
                <table class="merc-table">
                    <tr>
                        <th>Tipo de Servicio</th>
                        <th>Límite SIN pedidos hoy</th>
                        <th>Límite CON pedidos hoy</th>
                    </tr>
                    <tr>
                        <td><strong>MERC EMPRENDEDOR</strong></td>
                        <td><input type="time" name="merc_hora_emprendedor_sin_pedidos" class="merc-input-time" value="<?php echo esc_attr($h_emp_sin); ?>"></td>
                        <td><input type="time" name="merc_hora_emprendedor_con_pedidos" class="merc-input-time" value="<?php echo esc_attr($h_emp_con); ?>"></td>
                    </tr>
                    <tr>
                        <td><strong>MERC AGENCIA</strong></td>
                        <td><input type="time" name="merc_hora_agencia_sin_pedidos" class="merc-input-time" value="<?php echo esc_attr($h_ag_sin); ?>"></td>
                        <td><input type="time" name="merc_hora_agencia_con_pedidos" class="merc-input-time" value="<?php echo esc_attr($h_ag_con); ?>"></td>
                    </tr>
                    <tr>
                        <td><strong>MERC FULL FITMENT</strong></td>
                        <td><input type="time" name="merc_hora_full_sin_pedidos" class="merc-input-time" value="<?php echo esc_attr($h_fu_sin); ?>"></td>
                        <td><input type="time" name="merc_hora_full_con_pedidos" class="merc-input-time" value="<?php echo esc_attr($h_fu_con); ?>"></td>
                    </tr>
                </table>
            </div>

            <div class="merc-form-section">
                <h3 class="merc-form-title"><i class="fa fa-calendar-check-o" style="color:#10b981; margin-right:8px;"></i> 2. Domingos Desbloqueados</h3>
                <p style="color:#64748b; font-size:14px; margin-bottom:15px;">Por defecto los domingos están bloqueados. Agrega fechas exactas de domingos que sí se trabajen.</p>
                <div id="merc-domingos-list">
                    <?php foreach ($domingos as $i => $d): ?>
                        <div class="merc-date-row">
                            <input type="date" name="merc_bloqueos_domingos_desbloqueados[]" class="merc-input-date" value="<?php echo esc_attr($d); ?>">
                            <button type="button" class="merc-btn-remove remove-row"><i class="fa fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="merc-btn-add" onclick="addDateRow('merc-domingos-list', 'merc_bloqueos_domingos_desbloqueados[]')"><i class="fa fa-plus"></i> Agregar Domingo</button>
            </div>

            <div class="merc-form-section">
                <h3 class="merc-form-title"><i class="fa fa-calendar-times-o" style="color:#ef4444; margin-right:8px;"></i> 3. Fechas Festivas (Bloqueo Total)</h3>
                <p style="color:#64748b; font-size:14px; margin-bottom:15px;">Agrega fechas que estarán bloqueadas para TODOS los servicios (ej. Feriados).</p>
                <div id="merc-fechas-list">
                    <?php foreach ($fechas as $i => $f): ?>
                        <div class="merc-date-row">
                            <input type="date" name="merc_bloqueos_fechas_especiales[]" class="merc-input-date" value="<?php echo esc_attr($f); ?>">
                            <button type="button" class="merc-btn-remove remove-row"><i class="fa fa-times"></i></button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="merc-btn-add" onclick="addDateRow('merc-fechas-list', 'merc_bloqueos_fechas_especiales[]')"><i class="fa fa-plus"></i> Agregar Fecha Festiva</button>
            </div>

            <?php if ($is_backend): ?>
            <div class="merc-form-section">
                <h3 class="merc-form-title"><i class="fa fa-link" style="color:#8b5cf6; margin-right:8px;"></i> 4. URL del Panel Frontend</h3>
                <p style="color:#64748b; font-size:14px; margin-bottom:15px;">Pega aquí la URL de la página donde pusiste el shortcode <code>[merc_bloqueos_settings]</code>. Esto hará que aparezca automáticamente en el menú lateral de WPCargo.</p>
                <input type="url" name="merc_bloqueos_frontend_url" value="<?php echo esc_attr($frontend_url); ?>" style="width:100%; max-width:500px; padding:10px; border:1px solid #cbd5e1; border-radius:4px; outline:none;" placeholder="https://tu-sitio.com/configuracion-bloqueos">
            </div>
            <?php endif; ?>

            <p style="margin-top: 30px; text-align: right;">
                <?php if ($is_backend): ?>
                    <?php submit_button('Guardar Cambios', 'primary', 'submit', false, ['class' => 'merc-submit-btn']); ?>
                <?php else: ?>
                    <button type="submit" class="merc-submit-btn"><i class="fa fa-save" style="margin-right:8px;"></i> Guardar Configuración</button>
                <?php endif; ?>
            </p>
        </form>

        <script>
            function addDateRow(containerId, inputName) {
                var container = document.getElementById(containerId);
                var div = document.createElement('div');
                div.className = 'merc-date-row';
                div.innerHTML = '<input type="date" name="' + inputName + '" class="merc-input-date"> <button type="button" class="merc-btn-remove remove-row"><i class="fa fa-times"></i></button>';
                container.appendChild(div);
            }
            
            document.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('remove-row')) {
                    e.target.parentElement.remove();
                }
            });
        </script>
        <?php
    }

    public function handle_frontend_save() {
        if (!current_user_can('manage_options') || !isset($_POST['merc_nonce']) || !wp_verify_nonce($_POST['merc_nonce'], 'merc_save_frontend_settings')) {
            wp_die('Acceso denegado');
        }

        // Sanitizar y guardar
        update_option('merc_hora_emprendedor_sin_pedidos', sanitize_text_field($_POST['merc_hora_emprendedor_sin_pedidos']));
        update_option('merc_hora_emprendedor_con_pedidos', sanitize_text_field($_POST['merc_hora_emprendedor_con_pedidos']));
        update_option('merc_hora_agencia_sin_pedidos', sanitize_text_field($_POST['merc_hora_agencia_sin_pedidos']));
        update_option('merc_hora_agencia_con_pedidos', sanitize_text_field($_POST['merc_hora_agencia_con_pedidos']));
        update_option('merc_hora_full_sin_pedidos', sanitize_text_field($_POST['merc_hora_full_sin_pedidos']));
        update_option('merc_hora_full_con_pedidos', sanitize_text_field($_POST['merc_hora_full_con_pedidos']));

        $domingos = isset($_POST['merc_bloqueos_domingos_desbloqueados']) ? array_map('sanitize_text_field', $_POST['merc_bloqueos_domingos_desbloqueados']) : [];
        $domingos = array_filter($domingos); // remover vacíos
        update_option('merc_bloqueos_domingos_desbloqueados', $domingos);

        $fechas = isset($_POST['merc_bloqueos_fechas_especiales']) ? array_map('sanitize_text_field', $_POST['merc_bloqueos_fechas_especiales']) : [];
        $fechas = array_filter($fechas);
        update_option('merc_bloqueos_fechas_especiales', $fechas);

        $redirect = isset($_POST['redirect_url']) ? esc_url_raw($_POST['redirect_url']) : home_url();
        $redirect = add_query_arg('settings-updated', 'true', $redirect);
        
        wp_safe_redirect($redirect);
        exit;
    }

    public function add_frontend_sidebar_menu() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        $frontend_url = get_option('merc_bloqueos_frontend_url', '');
        if (!empty($frontend_url)) {
            // Verificar si es la página actual para marcarla como activa
            $current_url = home_url(add_query_arg(array(), $GLOBALS['wp']->request));
            $is_active = (strpos($current_url, untrailingslashit($frontend_url)) !== false) ? 'active' : '';
            
            echo '<a href="' . esc_url($frontend_url) . '" class="list-group-item waves-effect ' . $is_active . '">';
            echo '<i class="fa fa-clock-o mr-3"></i>Horarios y Bloqueos';
            echo '</a>';
        }
    }
}

