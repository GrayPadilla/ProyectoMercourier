<?php if ( ! defined( 'ABSPATH' ) ) exit;
$fuentes_color=['wpcargo_core'=>'#2271b1','plugin'=>'#00a32a','pagina'=>'#6c757d','manual'=>'#b45309'];
?>
<div class="wrap">
<h1 class="wp-heading-inline">Roles & Accesos</h1>
<a href="<?php echo esc_url(wcrol_url('wcrol-modulos')); ?>" class="page-title-action">Módulos del sidebar</a>
<hr class="wp-header-end">

<?php if ( $edit_uid && $usuario ):
$tipo_actual = WCROL_Rol_WPCargo::tipo_acceso($edit_uid);
$sin_restric = WCROL_Permisos::es_sin_restriccion($edit_uid);
$es_yo       = ($edit_uid === get_current_user_id());
?>
<!-- ═══ EDITAR USUARIO ═══════════════════════════════════════════════ -->
<h2 style="display:flex;align-items:center;gap:10px">
    <?php echo get_avatar($usuario->ID,32); ?>
    <?php echo esc_html(wcrol_nombre_usuario($usuario)); ?>
    <span style="font-size:13px;color:#666;font-weight:400">&lt;<?php echo esc_html($usuario->user_email); ?>&gt;</span>
    <?php if ($tipo_actual==='wpcargo_admin'): ?>
    <span style="background:#e8f4fd;color:#1a6891;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700"><span class="dashicons dashicons-shield" style="font-size:13px;width:13px;height:13px;vertical-align:middle"></span> WPCargo Admin</span>
    <?php else: ?>
    <span style="background:#d7f7c2;color:#135d3e;padding:2px 10px;border-radius:10px;font-size:11px;font-weight:700"><span class="dashicons dashicons-wordpress" style="font-size:13px;width:13px;height:13px;vertical-align:middle"></span> WordPress Admin</span>
    <?php endif; ?>
</h2>

<!-- Tipo de acceso -->
<div class="postbox" style="max-width:860px">
    <div class="postbox-header"><h2 class="hndle">Tipo de acceso</h2></div>
    <div class="inside">
    <?php if ($es_yo): ?>
        <p class="description">No puedes modificar tu propio tipo de acceso.</p>
    <?php else: ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wcrol_tipo_nonce'); ?>
            <input type="hidden" name="action"  value="wcrol_cambiar_tipo">
            <input type="hidden" name="user_id" value="<?php echo intval($edit_uid); ?>">
            <table class="form-table" role="presentation">
                <tr>
                    <th>Tipo de acceso</th>
                    <td>
                        <fieldset>
                            <label style="display:block;margin-bottom:8px">
                                <input type="radio" name="tipo_acceso" value="wordpress_admin" <?php checked($tipo_actual,'wordpress_admin'); ?>>
                                <strong> Administrador WordPress</strong> — acceso completo a wp-admin y al dashboard de WPCargo
                            </label>
                            <label style="display:block">
                                <input type="radio" name="tipo_acceso" value="wpcargo_admin" <?php checked($tipo_actual,'wpcargo_admin'); ?>>
                                <strong> Administrador WPCargo</strong> — <em>solo</em> accede al dashboard de WPCargo, <strong>no puede entrar a wp-admin</strong>
                            </label>
                        </fieldset>
                    </td>
                </tr>
            </table>
            <button type="submit" class="button button-primary">Guardar tipo de acceso</button>
        </form>
    <?php endif; ?>
    </div>
</div>

<!-- Permisos de módulos -->
<div class="postbox" style="max-width:860px">
    <div class="postbox-header"><h2 class="hndle">Módulos del dashboard <?php echo $sin_restric?'<span style="font-weight:400;font-size:12px;color:#00a32a">— Acceso total</span>':'<span style="font-weight:400;font-size:12px;color:#2271b1">— '.count($permisos_u??[]).' módulo(s) asignado(s)</span>'; ?></h2></div>
    <div class="inside">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wcrol_permisos_nonce'); ?>
            <input type="hidden" name="action"  value="wcrol_guardar_permisos">
            <input type="hidden" name="user_id" value="<?php echo intval($edit_uid); ?>">

            <?php
            $grupos=[];
            foreach($modulos as $mod) $grupos[$mod['fuente']][]=$mod;
            $fl=['wpcargo_core'=>'WPCargo Core','pagina'=>'Páginas WP','plugin'=>'Plugins externos','manual'=>'Manuales'];
            foreach(['wpcargo_core','pagina','plugin','manual'] as $fuente):
                if(empty($grupos[$fuente])) continue;
                $fc=$fuentes_color[$fuente]??'#666';
            ?>
            <h4 style="margin:16px 0 8px;padding:4px 8px;background:#f6f7f7;border-left:3px solid <?php echo esc_attr($fc); ?>;font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:<?php echo esc_attr($fc); ?>"><?php echo esc_html($fl[$fuente]??$fuente); ?></h4>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px">
            <?php foreach($grupos[$fuente] as $mod):
                $ck=$sin_restric||($permisos_u!==null&&in_array($mod['slug'],$permisos_u,true));
            ?>
                <label style="display:flex;align-items:center;gap:6px;padding:7px 10px;border:1px solid <?php echo $ck?'#c3d9f4':'#ddd'; ?>;border-radius:4px;cursor:pointer;background:<?php echo $ck?'#f0f6fc':'#fff'; ?>">
                    <input type="checkbox" name="modulos[]" value="<?php echo esc_attr($mod['slug']); ?>" <?php checked($ck,true); ?> onchange="var l=this.closest('label');l.style.background=this.checked?'#f0f6fc':'#fff';l.style.borderColor=this.checked?'#c3d9f4':'#ddd'">
                    <span class="dashicons" style="color:<?php echo esc_attr($fc); ?>;font-size:14px;width:14px;height:14px"></span>
                    <span style="font-size:13px"><strong><?php echo esc_html($mod['label']); ?></strong></span>
                </label>
            <?php endforeach; ?>
            </div>
            <?php endforeach; ?>

            <div style="display:flex;gap:8px;margin-top:16px;padding-top:12px;border-top:1px solid #ddd">
                <button type="submit" class="button button-primary">Guardar permisos</button>
                <a href="<?php echo esc_url(wcrol_url('wcrol-usuarios')); ?>" class="button">← Volver</a>
            </div>
        </form>
        <?php if(!$sin_restric): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid #ddd">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('¿Dar acceso total?')">
                <?php wp_nonce_field('wcrol_permisos_nonce'); ?>
                <input type="hidden" name="action"  value="wcrol_quitar_restricciones">
                <input type="hidden" name="user_id" value="<?php echo intval($edit_uid); ?>">
                <button type="submit" class="button button-link">Quitar restricciones (acceso total)</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>
<!-- ═══ LISTA ════════════════════════════════════════════════════════ -->
<table class="wp-list-table widefat fixed striped" style="max-width:1000px">
    <thead><tr>
        <th style="width:40px"></th><th>Usuario</th><th>Email</th>
        <th>Tipo de acceso</th><th>Módulos</th><th style="width:100px">Acciones</th>
    </tr></thead>
    <tbody>
    <?php if($usuarios): foreach($usuarios as $entry):
        $u=$entry['user']; $sin=$entry['sin_restriccion']; $num=$entry['num_modulos']; $tipo=$entry['tipo_acceso'];
    ?>
        <tr>
            <td><?php echo get_avatar($u->ID,28,'','',['style'=>'border-radius:50%;vertical-align:middle']); ?></td>
            <td>
                <strong><?php echo esc_html(wcrol_nombre_usuario($u)); ?></strong>
                <?php if($u->ID===get_current_user_id()): ?><span style="background:#d63638;color:#fff;font-size:10px;padding:1px 5px;border-radius:3px;margin-left:4px">Tú</span><?php endif; ?>
            </td>
            <td><?php echo esc_html($u->user_email); ?></td>
            <td>
                <?php if($tipo==='wordpress_admin'): ?>
                <span style="background:#d7f7c2;color:#135d3e;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:700"><span class="dashicons dashicons-wordpress" style="font-size:11px;width:11px;height:11px;vertical-align:middle"></span> WP Admin</span>
                <?php elseif($tipo==='wpcargo_admin'): ?>
                <span style="background:#e8f4fd;color:#1a6891;font-size:11px;padding:2px 8px;border-radius:10px;font-weight:700"><span class="dashicons dashicons-shield" style="font-size:11px;width:11px;height:11px;vertical-align:middle"></span> WPCargo Admin</span>
                <?php else: ?><span class="description"><?php echo esc_html($tipo); ?></span><?php endif; ?>
            </td>
            <td><?php echo $sin?'<span style="color:#00a32a">✓ Todos</span>':'<span style="color:#2271b1">'.intval($num).' módulo(s)</span>'; ?></td>
            <td>
                <a href="<?php echo esc_url(wcrol_url('wcrol-usuarios',['usuario'=>$u->ID])); ?>" class="button button-small button-primary">Editar</a>
            </td>
        </tr>
    <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:20px;color:#888">No hay usuarios.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<div style="margin-top:16px;padding:10px 14px;background:#fff8e5;border:1px solid #f0c040;border-radius:4px;max-width:1000px">
    <p style="margin:0;font-size:13px"><span class="dashicons dashicons-info-outline" style="vertical-align:middle;color:#b45309"></span>
    <strong>WPCargo Admin</strong> no puede acceder a wp-admin. Al loguearse, es redirigido directamente al dashboard de WPCargo.</p>
</div>
<?php endif; ?>
</div>
