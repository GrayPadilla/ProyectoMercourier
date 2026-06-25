<?php if ( ! defined('ABSPATH') ) exit;
// $wpcargo_tracking viene de pagina_config() — config real de WPCargo
$wpc = $wpcargo_tracking ?? [];
$wpc_detectado = ! empty($wpc['prefix']) || ! empty($wpc['preview']);
?>
<div class="wrap">
<h1>Envíos Masivos — Configuración</h1>
<hr class="wp-header-end">

<!-- ═══ BLOQUE: Config de WPCargo detectada ════════════════════════ -->
<div class="postbox" style="max-width:700px;margin-top:16px">
    <div class="postbox-header"><h2 class="hndle">📦 Configuración de Tracking de WPCargo</h2></div>
    <div class="inside">
    <?php if ($wpc_detectado): ?>
        <div style="background:#edf7ed;border:1px solid #46b450;border-radius:4px;padding:10px 14px;margin-bottom:14px;font-size:13px">
            <span class="dashicons dashicons-yes-alt" style="color:#46b450;vertical-align:middle"></span>
            Configuración de WPCargo detectada. El plugin generará los trackings con este formato automáticamente.
        </div>
        <table class="form-table" role="presentation" style="max-width:600px">
            <tr>
                <th style="width:200px">Prefijo detectado</th>
                <td>
                    <code style="font-size:14px;padding:4px 8px;background:#f0f0f1;border-radius:3px">
                        <?php echo esc_html($wpc['prefix'] ?: '(ninguno)'); ?>
                    </code>
                    <span class="description" style="margin-left:8px">
                        Opción WPCargo: <code>wpcargo_tracking_prefix</code>
                    </span>
                </td>
            </tr>
            <?php if ($wpc['suffix']): ?>
            <tr>
                <th>Sufijo detectado</th>
                <td>
                    <code style="font-size:14px;padding:4px 8px;background:#f0f0f1;border-radius:3px">
                        <?php echo esc_html($wpc['suffix']); ?>
                    </code>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <th>Tipo de código</th>
                <td>
                    <?php
                    $tipo_labels = [
                        'alphanumeric' => 'Alfanumérico (letras + números)',
                        'numeric'      => 'Solo numérico',
                        'alpha'        => 'Solo letras',
                    ];
                    echo esc_html($tipo_labels[$wpc['type']] ?? $wpc['type']);
                    ?>
                </td>
            </tr>
            <tr>
                <th>Dígitos variables</th>
                <td><?php echo intval($wpc['digits']); ?></td>
            </tr>
            <tr>
                <th>Formato resultante</th>
                <td>
                    <code style="font-size:15px;padding:5px 10px;background:#2271b1;color:#fff;border-radius:4px;letter-spacing:1px">
                        <?php echo esc_html($wpc['preview']); ?>
                    </code>
                    <p class="description" style="margin-top:4px">
                        Ejemplo del formato que se generará para cada envío.
                    </p>
                </td>
            </tr>
        </table>
        <p class="description" style="margin-top:8px;padding:8px 12px;background:#f6f7f7;border-radius:3px">
            <span class="dashicons dashicons-info" style="vertical-align:middle;color:#2271b1"></span>
            Para cambiar el formato de tracking, modifícalo directamente en
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpcargo-settings')); ?>">
                Ajustes → WPCargo
            </a>. Los cambios se reflejarán automáticamente aquí.
        </p>
    <?php else: ?>
        <div style="background:#fff8e5;border:1px solid #f0c33c;border-radius:4px;padding:10px 14px;margin-bottom:14px;font-size:13px">
            <span class="dashicons dashicons-warning" style="color:#bd8600;vertical-align:middle"></span>
            No se detectó configuración de tracking en WPCargo. Se usará el prefijo configurado abajo como fallback.
        </div>
    <?php endif; ?>
    </div>
</div>

<!-- ═══ BLOQUE: Config propia del plugin ══════════════════════════ -->
<div class="postbox" style="max-width:700px;margin-top:16px">
    <div class="postbox-header"><h2 class="hndle">⚙ Configuración del Plugin</h2></div>
    <div class="inside">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcmas_config_nonce'); ?>
        <input type="hidden" name="action" value="wcmas_guardar_config">
        <table class="form-table" role="presentation">
            <tr>
                <th><label for="tracking_prefix">Prefijo fallback del tracking</label></th>
                <td>
                    <input id="tracking_prefix" name="tracking_prefix" type="text" class="small-text"
                           value="<?php echo esc_attr($tracking_prefix); ?>" maxlength="8"
                           style="text-transform:uppercase;font-family:monospace;letter-spacing:2px;font-size:15px">
                    <p class="description">
                        Solo se usa si WPCargo <strong>no</strong> tiene prefijo configurado.<br>
                        Ej: <code>DHV</code> → <code>DHV240414XKJP2</code>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="filas_default">Filas iniciales de la grilla</label></th>
                <td>
                    <input id="filas_default" name="filas_default" type="number" class="small-text"
                           value="<?php echo esc_attr($filas_default); ?>" min="5" max="200">
                    <p class="description">Filas vacías que verá el cliente al abrir la grilla. El cliente puede añadir más.</p>
                </td>
            </tr>
            <tr>
                <th>Columnas por defecto</th>
                <td>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>"
                          onsubmit="return confirm('¿Resetear columnas? Se perderán las columnas personalizadas.')">
                        <?php wp_nonce_field('wcmas_config_nonce'); ?>
                        <input type="hidden" name="action" value="wcmas_reset_columnas">
                        <button type="submit" class="button button-secondary">
                            🔄 Resetear columnas a los nuevos defaults
                        </button>
                        <p class="description" style="margin-top:4px">
                            Aplica las columnas actualizadas: Distrito Recojo, Distrito Destino,
                            Tipo de Servicio, Modo de Pago, Costo Producto, Costo Servicio, Monto.
                        </p>
                    </form>
                </td>
            </tr>
        </table>
        <p class="submit"><button type="submit" class="button button-primary">Guardar cambios</button></p>
    </form>
    </div>
</div>

<!-- ═══ BLOQUE: Roles de cliente WPCargo ══════════════════════════ -->
<div class="postbox" style="max-width:700px;margin-top:16px">
    <div class="postbox-header"><h2 class="hndle">👥 Roles considerados "Clientes"</h2></div>
    <div class="inside">
        <p class="description">Estos son los roles que aparecen en el selector "Asignar a" al hacer una carga masiva. El filtro <code>wcmas_roles_clientes</code> permite personalizarlos desde functions.php.</p>
        <ul style="margin:8px 0 0 16px;list-style:disc">
            <?php
            $roles_cliente = apply_filters('wcmas_roles_clientes', ['wpcargo_client','subscriber','customer']);
            foreach ($roles_cliente as $rol):
                $wp_role = get_role($rol);
                $label   = $wp_role ? translate_user_role(ucfirst(str_replace('_', ' ', $rol))) : $rol;
                $count   = count(get_users(['role' => $rol, 'number' => 1000, 'fields' => 'ID']));
            ?>
            <li style="margin-bottom:4px">
                <code><?php echo esc_html($rol); ?></code>
                — <?php echo esc_html($label); ?>
                <span style="color:#888;font-size:12px">(<?php echo intval($count); ?> usuario<?php echo $count !== 1 ? 's' : ''; ?>)</span>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>


<!-- ═══ BLOQUE: Tabla de tarifas por distrito y tipo de servicio ══════ -->
<div class="postbox" style="max-width:900px;margin-top:16px">
    <div class="postbox-header"><h2 class="hndle">💰 Tarifas por Distrito y Tipo de Servicio</h2></div>
    <div class="inside">
        <p class="description" style="margin-bottom:12px">
            Define el costo de envío por distrito y tipo de servicio. Al crear envíos masivos,
            el <strong>Costo Servicio</strong> se autocompleta con este valor (editable).
            Si no hay tarifa definida, el usuario ingresa el monto manualmente.
        </p>

        <?php if ( empty($distritos) ): ?>
        <div class="notice notice-warning inline"><p>No se encontraron distritos en WPCargo. Asegúrate de tener el addon de Custom Fields activado.</p></div>
        <?php else: ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wcmas_config_nonce'); ?>
            <input type="hidden" name="action" value="wcmas_guardar_tarifas">

            <div style="overflow-x:auto">
            <table class="widefat fixed striped" style="font-size:12px;min-width:500px">
                <thead>
                    <tr style="background:#2271b1;color:#fff">
                        <th style="width:220px;color:#fff;padding:8px 10px">Distrito</th>
                        <?php foreach($tipos_servicio as $label => $val): ?>
                        <th style="text-align:center;color:#fff;padding:8px 10px">
                            <?php echo esc_html($label); ?>
                            <small style="display:block;opacity:.8;font-weight:normal"><?php echo esc_html($val); ?></small>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                <?php foreach($distritos as $distrito): ?>
                <tr>
                    <td style="padding:4px 10px;font-weight:600"><?php echo esc_html($distrito); ?></td>
                    <?php foreach($tipos_servicio as $label => $tipo_val): ?>
                    <td style="padding:4px 8px;text-align:center">
                        <input
                            type="number"
                            step="0.50"
                            min="0"
                            name="tarifas[<?php echo esc_attr($distrito); ?>][<?php echo esc_attr($tipo_val); ?>]"
                            value="<?php echo esc_attr($tarifas[$distrito][$tipo_val] ?? ''); ?>"
                            placeholder="0.00"
                            style="width:80px;text-align:center;padding:3px 5px;font-size:12px"
                        >
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <p class="submit" style="margin-top:12px">
                <button type="submit" class="button button-primary">💾 Guardar Tarifas</button>
                <span class="description" style="margin-left:12px">Deja en blanco los distritos sin tarifa definida.</span>
                &nbsp;
                <button type="button" class="button button-secondary" style="margin-left:8px"
                    onclick="if(confirm('¿Restaurar las tarifas por defecto de Mercourier?')) wcmasRestaurarTarifas()">
                    🔄 Restaurar tarifas por defecto
                </button>
                <script>
                function wcmasRestaurarTarifas() {
                    fetch(ajaxurl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/x-www-form-urlencoded'},
                        body: 'action=wcmas_restaurar_tarifas_default&nonce=<?php echo wp_create_nonce("wcmas_procesar_nonce"); ?>'
                    }).then(r=>r.json()).then(function(r){
                        if(r.success) { alert('Tarifas restauradas. Recarga la página.'); location.reload(); }
                        else alert('Error: ' + (r.data&&r.data.msg||'desconocido'));
                    });
                }
                </script>
            </p>
        </form>

        <?php endif; ?>
    </div>
</div>

</div>
