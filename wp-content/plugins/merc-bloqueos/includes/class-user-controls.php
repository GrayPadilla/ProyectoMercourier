<?php
/**
 * Controles de Bloqueo en WPCargo User Management
 */

if (!defined('ABSPATH')) {
    exit;
}

class Merc_Bloqueos_User_Controls {

    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
        
        // AJAX Endpoints para los botones
        add_action('wp_ajax_merc_bloqueos_temp_unlock', [$this, 'handle_temp_unlock']);
        add_action('wp_ajax_merc_bloqueos_toggle_total', [$this, 'handle_toggle_total']);
    }

    public function enqueue_admin_scripts($hook) {
        // Solo cargar en la página de WPCargo User Management
        if (strpos($hook, 'wpcargo-user-management') !== false || isset($_GET['page']) && strpos($_GET['page'], 'wpcumanage') !== false) {
            
            // Inyectar script inline para reemplazar los botones
            wp_add_inline_script('jquery', $this->get_inline_js());
        }
    }

    public function enqueue_frontend_scripts() {
        // No cargar en la página de Envíos Masivos: el JS de controles de bloqueo
        // (botones Bloqueo Total / Desbloqueo Temporal) no tiene función ahí y puede
        // interferir con los event listeners de jQuery de la grilla.
        $masivos_page_id = (int) get_option('wcmas_frontend_page_id');
        if ($masivos_page_id && is_page($masivos_page_id)) {
            return;
        }

        wp_add_inline_script('jquery', $this->get_inline_js());
    }


    private function get_inline_js() {
        ob_start();
        ?>
        jQuery(document).ready(function($) {
            var ajaxurl = typeof window.ajaxurl !== 'undefined' ? window.ajaxurl : '<?php echo admin_url('admin-ajax.php'); ?>';
            
            // Reemplazar la columna "Bloquear/Desbloquear" existente con nuestros controles
            function render_merc_controls() {
                $('.wpcumanage-blocked').each(function() {
                    var td = $(this);
                    
                    // Extraer el ID del usuario del botón original
                    var btn = td.find('button').first();
                    if (!btn.length) return;
                    
                    var userId = btn.data('id');
                    if (!userId) return;

                    // Si ya lo procesamos, saltar
                    if (td.find('.merc-controls-container').length) return;

                    // Consultar estado actual (en un caso ideal esto vendría en el HTML, 
                    // pero para no alterar el otro plugin lo inyectamos visualmente).
                    var container = $('<div class="merc-controls-container" style="display:flex; flex-direction:column; gap:8px; align-items:flex-start;"></div>');
                    
                    // Determinar estado inicial (el botón original dirá "Desbloquear" si está bloqueado, o podemos sacar el data attr, pero para simplificar usamos el estilo)
                    // Como no tenemos el estado exacto del bloqueo total a mano sin alterar la fila, asumimos que el usuario lo verá al hacer clic o podemos cargar el estado vía AJAX.
                    // Por ahora mejoramos el diseño del botón.
                    var btnTotal = $('<button class="btn btn-sm btn-danger merc-toggle-total" data-id="' + userId + '" style="width: 100%; text-align: left;"><i class="fa fa-ban" style="margin-right: 5px;"></i> Bloqueo Total</button>');
                    
                    // Botón Desbloqueo Temporal
                    var btnTemp = $('<button class="btn btn-sm btn-info merc-temp-unlock" data-id="' + userId + '" style="width: 100%; text-align: left; background-color: #17a2b8; border-color: #17a2b8; color: white;"><i class="fa fa-clock-o" style="margin-right: 5px;"></i> Desbloqueo Temp.</button>');

                    container.append(btnTotal).append(btnTemp);
                    td.html(container);
                });
            }

            // Ejecutar al inicio y si hay paginación AJAX
            render_merc_controls();
            $(document).ajaxComplete(function(event, xhr, settings) {
                if (settings.url && settings.url.indexOf('admin-ajax.php') !== -1) {
                    setTimeout(render_merc_controls, 500);
                }
            });

            // Manejar click en Desbloqueo Temporal
            $(document).on('click', '.merc-temp-unlock', function(e) {
                e.preventDefault();
                var userId = $(this).data('id');
                
                var html = `
                    <div style="text-align:left; padding: 10px;">
                        <div style="margin-bottom: 20px;">
                            <label style="display:block; margin-bottom:8px; font-weight: 600; color: #333;">Tipo de Servicio:</label>
                            <select id="merc_temp_type" style="width:100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; outline: none;">
                                <option value="all">Todos los servicios</option>
                                <option value="normal">MERC EMPRENDEDOR</option>
                                <option value="express">MERC AGENCIA</option>
                                <option value="full_fitment">MERC FULL FITMENT</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block; margin-bottom:8px; font-weight: 600; color: #333;">Tiempo de Desbloqueo:</label>
                            <select id="merc_temp_mins" style="width:100%; padding: 10px; border: 1px solid #ccc; border-radius: 5px; font-size: 15px; outline: none;">
                                <option value="5">5 minutos</option>
                                <option value="10">10 minutos</option>
                                <option value="15">15 minutos</option>
                                <option value="30">30 minutos</option>
                                <option value="60">1 hora</option>
                            </select>
                        </div>
                    </div>
                `;

                Swal.fire({
                    title: '<h3 style="margin:0; color:#17a2b8;"><i class="fa fa-unlock-alt"></i> Desbloqueo Temporal</h3>',
                    html: html,
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    confirmButtonText: 'Aplicar Desbloqueo',
                    confirmButtonColor: '#17a2b8',
                    customClass: {
                        confirmButton: 'btn btn-info',
                        cancelButton: 'btn btn-secondary'
                    },
                    preConfirm: () => {
                        return {
                            type: document.getElementById('merc_temp_type').value,
                            mins: document.getElementById('merc_temp_mins').value
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(ajaxurl, {
                            action: 'merc_bloqueos_temp_unlock',
                            user_id: userId,
                            type: result.value.type,
                            mins: result.value.mins
                        }, function(res) {
                            if (res.success) {
                                Swal.fire('Éxito', res.data.message, 'success');
                            } else {
                                Swal.fire('Error', res.data.message, 'error');
                            }
                        });
                    }
                });
            });

            // Manejar click en Bloqueo Total
            $(document).on('click', '.merc-toggle-total', function(e) {
                e.preventDefault();
                var userId = $(this).data('id');
                var btn = $(this);

                Swal.fire({
                    title: '<h3 style="margin:0; color:#dc3545;"><i class="fa fa-ban"></i> Bloqueo Total</h3>',
                    text: '¿Deseas alternar el estado de Bloqueo Total para este cliente? Esto le impedirá crear cualquier tipo de pedido.',
                    icon: 'warning',
                    showCancelButton: true,
                    cancelButtonText: 'Cancelar',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Sí, aplicar',
                    customClass: {
                        confirmButton: 'btn btn-danger',
                        cancelButton: 'btn btn-secondary'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post(ajaxurl, {
                            action: 'merc_bloqueos_toggle_total',
                            user_id: userId
                        }, function(res) {
                            if (res.success) {
                                Swal.fire('Éxito', res.data.message, 'success');
                                // Actualizar botón visualmente
                                if (res.data.is_blocked) {
                                    btn.html('<i class="fa fa-check-circle" style="margin-right: 5px;"></i> Quitar Bloqueo').removeClass('btn-danger').addClass('btn-success');
                                } else {
                                    btn.html('<i class="fa fa-ban" style="margin-right: 5px;"></i> Bloqueo Total').removeClass('btn-success').addClass('btn-danger');
                                }
                            } else {
                                Swal.fire('Error', res.data.message, 'error');
                            }
                        });
                    }
                });
            });
        });
        <?php
        return ob_get_clean();
    }

    public function handle_temp_unlock() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $user_id = intval($_POST['user_id']);
        $type = sanitize_text_field($_POST['type']);
        $mins = intval($_POST['mins']);

        if (!$user_id || !$mins) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }

        $expire = current_time('timestamp') + ($mins * 60);

        update_user_meta($user_id, 'merc_temp_unlock_type', $type);
        update_user_meta($user_id, 'merc_temp_unlock_expire', $expire);

        // Limpiar el bloqueo total si lo tuviera, para que el temporal funcione
        delete_user_meta($user_id, 'merc_bloqueo_total');

        $format = date('H:i:s', $expire);
        wp_send_json_success(['message' => "Cliente desbloqueado para {$type} por {$mins} minutos (hasta las {$format})."]);
    }

    public function handle_toggle_total() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Sin permisos']);
        }

        $user_id = intval($_POST['user_id']);
        if (!$user_id) {
            wp_send_json_error(['message' => 'Datos inválidos']);
        }

        $current = get_user_meta($user_id, 'merc_bloqueo_total', true);

        if ($current === '1') {
            delete_user_meta($user_id, 'merc_bloqueo_total');
            $msg = 'Bloqueo total eliminado. El cliente vuelve a sus horarios regulares.';
            $is_blocked = false;
        } else {
            update_user_meta($user_id, 'merc_bloqueo_total', '1');
            // Limpiar unlocks temporales
            delete_user_meta($user_id, 'merc_temp_unlock_expire');
            $msg = 'Bloqueo total activado. El cliente ya no puede crear envíos.';
            $is_blocked = true;
        }

        wp_send_json_success([
            'message' => $msg,
            'is_blocked' => $is_blocked
        ]);
    }
}

