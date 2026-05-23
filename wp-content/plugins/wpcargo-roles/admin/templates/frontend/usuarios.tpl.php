<?php if ( ! defined('ABSPATH') ) exit;
$fuentes_color = ['wpcargo_core'=>'#2271b1','plugin'=>'#00a32a','pagina'=>'#6c757d','manual'=>'#b45309'];
$msgs_map = [
    'guardado'     => ['success','Cambios guardados correctamente.'],
    'error_req'    => ['danger', 'Faltan campos obligatorios.'],
    'error_propio' => ['warning','No puedes modificar tu propio tipo de acceso.'],
];
?>

<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto"><i class="fa fa-shield mr-2 text-primary"></i>Roles & Accesos</h5>
    <div>
        <a href="<?php echo esc_url($page_url); ?>" class="btn btn-primary btn-sm mr-1">
            <i class="fa fa-users mr-1"></i>Usuarios
        </a>
        <a href="<?php echo esc_url(add_query_arg('wcrol_vista','modulos',$page_url)); ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fa fa-th-list mr-1"></i>Módulos
        </a>
    </div>
</div>

<?php if ($msg && isset($msgs_map[$msg])): [$mt,$mm] = $msgs_map[$msg]; ?>
<div class="alert alert-<?php echo esc_attr($mt); ?> alert-dismissible fade show">
    <?php echo esc_html($mm); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<?php if ($edit_uid && $usuario):
    $tipo_actual = WCROL_Rol_WPCargo::tipo_acceso($edit_uid);
    $sin_restric = WCROL_Permisos::es_sin_restriccion($edit_uid);
    $es_yo       = ($edit_uid === get_current_user_id());
?>

<!-- Header del usuario -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;padding:16px 20px;margin-bottom:16px;display:flex;align-items:center;gap:16px">
    <?php echo get_avatar($usuario->ID, 52, '', '', ['style'=>'border-radius:50%;flex-shrink:0']); ?>
    <div style="flex:1">
        <h5 class="mb-0"><?php echo esc_html(wcrol_nombre_usuario($usuario)); ?>
            <?php if ($es_yo): ?>
            <span style="background:#2271b1;color:#fff;font-size:10px;padding:2px 7px;border-radius:10px;margin-left:6px;vertical-align:middle">Tú</span>
            <?php endif; ?>
        </h5>
        <div class="text-muted small"><?php echo esc_html($usuario->user_email); ?></div>
    </div>
    <span style="padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;background:<?php echo $tipo_actual==='wordpress_admin'?'#d7f7c2':'#e8f4fd'; ?>;color:<?php echo $tipo_actual==='wordpress_admin'?'#135d3e':'#1a6891'; ?>">
        <i class="fa <?php echo $tipo_actual==='wordpress_admin'?'fa-wordpress':'fa-shield'; ?> mr-1"></i>
        <?php echo $tipo_actual==='wordpress_admin'?'Admin WordPress':'Admin WPCargo'; ?>
    </span>
    <a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-secondary btn-sm">
        <i class="fa fa-arrow-left mr-1"></i>Volver
    </a>
</div>

<!-- SECCIÓN 1: Tipo de acceso -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden;margin-bottom:16px">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong style="font-size:.9rem"><i class="fa fa-key mr-1 text-warning"></i>Tipo de acceso</strong>
    </div>
    <div style="padding:16px">
    <?php if ($es_yo): ?>
        <div class="alert alert-info mb-0 small"><i class="fa fa-info-circle mr-1"></i>No puedes modificar tu propio tipo de acceso.</div>
    <?php else: ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wcrol-tipo-form">
            <?php wp_nonce_field('wcrol_fe_tipo_nonce'); ?>
            <input type="hidden" name="action"      value="wcrol_fe_cambiar_tipo">
            <input type="hidden" name="user_id"     value="<?php echo intval($edit_uid); ?>">
            <input type="hidden" name="tipo_acceso" id="wcrol-tipo-hidden" value="<?php echo esc_attr($tipo_actual); ?>">

            <div class="row" style="row-gap:10px;margin-bottom:14px">
                <!-- Opción WordPress Admin -->
                <div class="col-md-6">
                    <div class="wcrol-tipo-card" data-tipo="wordpress_admin"
                         style="border:2px solid <?php echo $tipo_actual==='wordpress_admin'?'#2271b1':'#dee2e6'; ?>;border-radius:6px;padding:14px 16px;cursor:pointer;background:<?php echo $tipo_actual==='wordpress_admin'?'#f0f6fc':'#fff'; ?>">
                        <div class="d-flex align-items-start">
                            <div class="wcrol-radio-dot" style="width:18px;height:18px;border-radius:50%;border:2px solid <?php echo $tipo_actual==='wordpress_admin'?'#2271b1':'#ccc'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:3px">
                                <div style="width:8px;height:8px;border-radius:50%;background:#2271b1;display:<?php echo $tipo_actual==='wordpress_admin'?'block':'none'; ?>"></div>
                            </div>
                            <div style="margin-left:10px">
                                <strong><i class="fa fa-wordpress mr-1"></i>Administrador WordPress</strong>
                                <div class="text-muted small mt-1">Acceso completo a wp-admin y al dashboard de WPCargo. Para administradores de confianza.</div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Opción WPCargo Admin -->
                <div class="col-md-6">
                    <div class="wcrol-tipo-card" data-tipo="wpcargo_admin"
                         style="border:2px solid <?php echo $tipo_actual==='wpcargo_admin'?'#2271b1':'#dee2e6'; ?>;border-radius:6px;padding:14px 16px;cursor:pointer;background:<?php echo $tipo_actual==='wpcargo_admin'?'#f0f6fc':'#fff'; ?>">
                        <div class="d-flex align-items-start">
                            <div class="wcrol-radio-dot" style="width:18px;height:18px;border-radius:50%;border:2px solid <?php echo $tipo_actual==='wpcargo_admin'?'#2271b1':'#ccc'; ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:3px">
                                <div style="width:8px;height:8px;border-radius:50%;background:#2271b1;display:<?php echo $tipo_actual==='wpcargo_admin'?'block':'none'; ?>"></div>
                            </div>
                            <div style="margin-left:10px">
                                <strong><i class="fa fa-shield mr-1"></i>Administrador WPCargo</strong>
                                <div class="text-muted small mt-1">Solo accede al dashboard de WPCargo. <strong>No puede entrar a wp-admin.</strong> Ideal para asistentes y operadores.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-sm" id="wcrol-tipo-btn" <?php echo 'disabled'; ?>>
                <i class="fa fa-save mr-1"></i>Guardar tipo de acceso
            </button>
            <span class="text-muted small ml-2" id="wcrol-tipo-hint" style="display:none">Selecciona una opción para cambiar</span>
        </form>

        <script>
        (function(){
            var actual = '<?php echo esc_js($tipo_actual); ?>';
            var cards  = document.querySelectorAll('.wcrol-tipo-card');
            var hidden = document.getElementById('wcrol-tipo-hidden');
            var btn    = document.getElementById('wcrol-tipo-btn');
            var hint   = document.getElementById('wcrol-tipo-hint');

            cards.forEach(function(card){
                card.addEventListener('click', function(){
                    var tipo = this.getAttribute('data-tipo');
                    hidden.value = tipo;

                    // Visual de todas las cards
                    cards.forEach(function(c){
                        var isSelected = c.getAttribute('data-tipo') === tipo;
                        c.style.borderColor  = isSelected ? '#2271b1' : '#dee2e6';
                        c.style.background   = isSelected ? '#f0f6fc' : '#fff';
                        var dot = c.querySelector('.wcrol-radio-dot');
                        var inner = dot.querySelector('div');
                        dot.style.borderColor = isSelected ? '#2271b1' : '#ccc';
                        inner.style.display   = isSelected ? 'block' : 'none';
                    });

                    // Botón habilitado solo si cambió
                    if (tipo !== actual) {
                        btn.removeAttribute('disabled');
                        hint.style.display = 'none';
                    } else {
                        btn.setAttribute('disabled','disabled');
                    }
                });
            });
        })();
        </script>
    <?php endif; ?>
    </div>
</div>

<!-- SECCIÓN 2: Módulos del dashboard -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa;display:flex;align-items:center;justify-content:space-between">
        <strong style="font-size:.9rem"><i class="fa fa-th-list mr-1 text-primary"></i>Módulos del dashboard</strong>
        <?php if ($sin_restric): ?>
        <span style="background:#d7f7c2;color:#135d3e;font-size:11px;padding:2px 10px;border-radius:10px;font-weight:700">Sin restricción — ve todos</span>
        <?php else: ?>
        <span style="background:#fce9e9;color:#8a1a1a;font-size:11px;padding:2px 10px;border-radius:10px;font-weight:700"><?php echo count($permisos_u??[]); ?> módulo(s) asignado(s)</span>
        <?php endif; ?>
    </div>
    <div style="padding:16px">
        <?php if (empty($modulos)): ?>
        <div class="alert alert-warning small">
            <i class="fa fa-exclamation-triangle mr-1"></i>
            No hay módulos en el catálogo. Visita el dashboard de WPCargo y luego ve a <strong>Módulos → Sincronizar</strong>.
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3">Marca los módulos que este usuario puede ver en el sidebar del dashboard.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wcrol_fe_permisos_nonce'); ?>
            <input type="hidden" name="action"  value="wcrol_fe_guardar_permisos">
            <input type="hidden" name="user_id" value="<?php echo intval($edit_uid); ?>">

            <?php
            $grupos = [];
            foreach ($modulos as $mod) $grupos[$mod['fuente'] ?? 'manual'][] = $mod;
            $labels_fuente = ['wpcargo_core'=>'WPCargo Core','pagina'=>'Páginas WP','plugin'=>'Plugins externos','manual'=>'Manuales'];
            foreach (['wpcargo_core','pagina','plugin','manual'] as $fuente):
                if (empty($grupos[$fuente])) continue;
                $fc = $fuentes_color[$fuente] ?? '#666';
                $fl = $labels_fuente[$fuente] ?? $fuente;
            ?>
            <div style="margin-bottom:14px">
                <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:<?php echo esc_attr($fc); ?>;padding:4px 8px;border-left:3px solid <?php echo esc_attr($fc); ?>;margin-bottom:8px">
                    <?php echo esc_html($fl); ?>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:6px">
                    <?php foreach ($grupos[$fuente] as $mod):
                        $slug_mod = $mod['slug'];
                        $checked  = $sin_restric || ($permisos_u !== null && in_array($slug_mod, $permisos_u, true));
                        $bid = 'wcrol_mod_'.esc_attr($slug_mod);
                    ?>
                    <label for="<?php echo $bid; ?>"
                           style="display:flex;align-items:center;gap:8px;padding:8px 10px;border:1px solid <?php echo $checked?'#b8daff':'#dee2e6'; ?>;border-radius:4px;cursor:pointer;background:<?php echo $checked?'#e8f4fd':'#fff'; ?>;transition:border-color .15s,background .15s;user-select:none">
                        <input type="checkbox" id="<?php echo $bid; ?>" name="modulos[]"
                               value="<?php echo esc_attr($slug_mod); ?>"
                               <?php checked($checked); ?> style="flex-shrink:0;margin:0"
                               onchange="var l=this.closest('label');l.style.background=this.checked?'#e8f4fd':'#fff';l.style.borderColor=this.checked?'#b8daff':'#dee2e6'">
                        <span>
                            <i class="fa <?php echo esc_attr($mod['icon']??'fa-circle-o'); ?>" style="color:<?php echo esc_attr($fc); ?>;width:16px;text-align:center"></i>
                            <strong style="font-size:12px"> <?php echo esc_html($mod['label']); ?></strong>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>

            <div class="d-flex align-items-center" style="gap:8px;margin-top:14px;padding-top:14px;border-top:1px solid #dee2e6">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-save mr-1"></i>Guardar permisos</button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="document.querySelectorAll('input[name=\'modulos[]\']').forEach(function(i){i.checked=true;var l=i.closest('label');l.style.background='#e8f4fd';l.style.borderColor='#b8daff';})">
                    Marcar todos
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm"
                        onclick="document.querySelectorAll('input[name=\'modulos[]\']').forEach(function(i){i.checked=false;var l=i.closest('label');l.style.background='#fff';l.style.borderColor='#dee2e6';})">
                    Desmarcar todos
                </button>
            </div>
        </form>

        <?php if (!$sin_restric): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #dee2e6">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('¿Dar acceso total a este usuario?')">
                <?php wp_nonce_field('wcrol_fe_permisos_nonce'); ?>
                <input type="hidden" name="action"  value="wcrol_fe_quitar_restricciones">
                <input type="hidden" name="user_id" value="<?php echo intval($edit_uid); ?>">
                <button type="submit" class="btn btn-link btn-sm text-muted p-0">
                    <i class="fa fa-unlock mr-1"></i>Quitar restricciones (acceso total)
                </button>
            </form>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ═══ LISTA DE USUARIOS ════════════════════════════════════════════ -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
<div class="table-responsive">
<table class="table table-hover table-sm mb-0">
    <thead class="thead-light">
        <tr><th style="width:42px"></th><th>Usuario</th><th>Email</th><th>Tipo de acceso</th><th>Módulos</th><th style="width:90px">Acciones</th></tr>
    </thead>
    <tbody>
    <?php if ($usuarios): foreach ($usuarios as $entry):
        $u    = $entry['user'];
        $sin  = $entry['sin_restriccion'];
        $num  = $entry['num_modulos'];
        $tipo = $entry['tipo_acceso'];
        $es_yo = ($u->ID === get_current_user_id());
    ?>
        <tr>
            <td><?php echo get_avatar($u->ID, 32, '', '', ['style'=>'border-radius:50%;vertical-align:middle']); ?></td>
            <td>
                <strong><?php echo esc_html(wcrol_nombre_usuario($u)); ?></strong>
                <?php if ($es_yo): ?><span style="background:#2271b1;color:#fff;font-size:10px;padding:1px 5px;border-radius:10px;margin-left:4px">Tú</span><?php endif; ?>
            </td>
            <td class="small text-muted"><?php echo esc_html($u->user_email); ?></td>
            <td>
                <?php if ($tipo === 'wordpress_admin'): ?>
                <span style="background:#e3f0ff;color:#1a4a7a;font-size:11px;padding:2px 9px;border-radius:10px;font-weight:700;white-space:nowrap">
                    <i class="fa fa-wordpress mr-1"></i>WordPress Admin
                </span>
                <?php else: ?>
                <span style="background:#e8f4fd;color:#1a6891;font-size:11px;padding:2px 9px;border-radius:10px;font-weight:700;white-space:nowrap">
                    <i class="fa fa-shield mr-1"></i>WPCargo Admin
                </span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($sin): ?>
                <span class="text-success small"><i class="fa fa-check-circle mr-1"></i>Todos</span>
                <?php else: ?>
                <span class="text-primary small"><i class="fa fa-shield mr-1"></i><?php echo intval($num); ?> módulo(s)</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="<?php echo esc_url(add_query_arg('usuario',$u->ID,$page_url)); ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-pencil"></i>
                </a>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="6" class="text-center text-muted py-4">No hay usuarios configurados.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
</div>
<div style="margin-top:14px;padding:10px 14px;background:#fff8e5;border:1px solid #f0c040;border-radius:6px;font-size:13px">
    <i class="fa fa-info-circle text-warning mr-1"></i>
    <strong>WordPress Admin</strong> tiene acceso a wp-admin. <strong>WPCargo Admin</strong> solo ve el dashboard — sin acceso a WordPress. Ideal para asistentes y operadores.
</div>
<?php endif; ?>
