<?php if ( ! defined('ABSPATH') ) exit;
$fuentes_color = ['wpcargo_core'=>'#2271b1','plugin'=>'#00a32a','pagina'=>'#6c757d','manual'=>'#b45309'];
$fuentes_label = ['wpcargo_core'=>'WPCargo Core','plugin'=>'Plugin externo','pagina'=>'Página WP','manual'=>'Manual'];
$msgs_map = [
    'guardado'     => ['success','Módulo guardado correctamente.'],
    'eliminado'    => ['success','Módulo eliminado.'],
    'sincronizado' => ['success','Módulos sincronizados.'],
    'error_req'    => ['danger', 'Faltan campos obligatorios.'],
];
$msg = sanitize_key($_GET['wcrol_msg'] ?? '');
?>

<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <h5 class="mb-0 mr-auto"><i class="fa fa-th-list mr-2 text-primary"></i>Módulos del Sidebar</h5>
    <div>
        <a href="<?php echo esc_url($page_url); ?>" class="btn btn-outline-secondary btn-sm mr-1">
            <i class="fa fa-users mr-1"></i>Usuarios
        </a>
        <?php if (!($edit_slug || isset($_GET['editar']))): ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline">
            <?php wp_nonce_field('wcrol_fe_sync_nonce'); ?>
            <input type="hidden" name="action" value="wcrol_fe_sincronizar">
            <button type="submit" class="btn btn-outline-secondary btn-sm mr-1" title="Captura las páginas del dashboard y los módulos de plugins. Primero navega por el dashboard de WPCargo para que se registren los plugins.">
                <i class="fa fa-refresh mr-1"></i>Sincronizar
            </button>
        </form>
        <a href="<?php echo esc_url(add_query_arg(['wcrol_vista'=>'modulos','editar'=>'nuevo'],$page_url)); ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-plus mr-1"></i>Añadir módulo
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($msg && isset($msgs_map[$msg])): [$mt,$mm]=$msgs_map[$msg]; ?>
<div class="alert alert-<?php echo esc_attr($mt); ?> alert-dismissible fade show">
    <?php echo esc_html($mm); ?>
    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<!-- Aviso sobre cómo funciona la sincronización -->
<?php if (!($edit_slug||isset($_GET['editar']))): ?>
<div class="alert alert-info small" style="margin-bottom:16px">
    <i class="fa fa-lightbulb-o mr-1"></i>
    <strong>¿Cómo funciona?</strong> Haz clic en <strong>Sincronizar</strong> para detectar automáticamente los módulos del sidebar. 
    Para que los módulos de plugins como Viáticos o Finanzas aparezcan, primero navega a esas páginas en el dashboard y luego sincroniza.
    <?php if ($capturado): ?>
    <br><span class="text-success"><i class="fa fa-check mr-1"></i>Se han capturado <strong><?php echo count($capturado); ?> ítem(s)</strong> del sidebar en la última visita al dashboard.</span>
    <?php else: ?>
    <br><span class="text-warning"><i class="fa fa-exclamation-triangle mr-1"></i>Aún no se ha capturado el sidebar. Visita el dashboard de WPCargo y luego sincroniza.</span>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($edit_slug || isset($_GET['editar'])): ?>
<!-- ═══ FORMULARIO ════════════════════════════════════════════════════ -->
<div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <div style="padding:12px 16px;border-bottom:1px solid #dee2e6;background:#f8f9fa">
        <strong><?php echo $modulo ? 'Editar: '.esc_html($modulo['label']) : 'Nuevo módulo'; ?></strong>
    </div>
    <div style="padding:20px">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcrol_fe_modulo_nonce'); ?>
        <input type="hidden" name="action"        value="wcrol_fe_guardar_modulo">
        <input type="hidden" name="slug_original" value="<?php echo esc_attr($edit_slug); ?>">
        <div class="row">
            <div class="col-md-5 form-group">
                <label class="font-weight-bold">Etiqueta visible *</label>
                <input id="wcrol-label" name="label" type="text" class="form-control" value="<?php echo esc_attr($modulo['label']??''); ?>" required>
            </div>
            <div class="col-md-3 form-group">
                <label class="font-weight-bold">Slug</label>
                <input id="wcrol-slug" name="slug" type="text" class="form-control" value="<?php echo esc_attr($modulo['slug']??''); ?>" <?php echo $modulo?'readonly':'required'; ?>>
            </div>
            <div class="col-md-4 form-group">
                <label class="font-weight-bold">Ícono (Font Awesome 4)</label>
                <div class="input-group">
                    <input id="wcrol-icon" name="icon" type="text" class="form-control" value="<?php echo esc_attr($modulo['icon']??'fa-circle-o'); ?>" placeholder="fa-truck" oninput="document.getElementById('wcrol-iprev').className='fa '+this.value+' fa-lg'">
                    <div class="input-group-append"><span class="input-group-text"><i id="wcrol-iprev" class="fa <?php echo esc_attr($modulo['icon']??'fa-circle-o'); ?> fa-lg"></i></span></div>
                </div>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Página WordPress vinculada</label>
                <select name="page_id" class="form-control browser-default">
                    <option value="0">— Ninguna —</option>
                    <?php foreach($paginas as $p): ?>
                    <option value="<?php echo intval($p->ID); ?>" <?php selected($modulo['page_id']??0,$p->ID); ?>><?php echo esc_html($p->post_title); ?> (ID: <?php echo $p->ID; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Necesario para filtrar módulos que son páginas WP.</small>
            </div>
            <div class="col-md-6 form-group">
                <label class="font-weight-bold">Clave del sidebar (sidebar_key)</label>
                <input name="sidebar_key" type="text" class="form-control" value="<?php echo esc_attr($modulo['sidebar_key']??''); ?>" placeholder="ej: wpcv-menu">
                <small class="text-muted">Necesario para filtrar módulos añadidos por plugins (sin página propia).</small>
            </div>
        </div>
        <div class="d-flex" style="gap:8px">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save mr-1"></i>Guardar</button>
            <a href="<?php echo esc_url(add_query_arg('wcrol_vista','modulos',$page_url)); ?>" class="btn btn-outline-secondary">Cancelar</a>
        </div>
    </form>
    </div>
</div>
<script>
document.getElementById('wcrol-label').addEventListener('input',function(){
    var s=document.getElementById('wcrol-slug');
    if(!s.readOnly) s.value=this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
});
</script>

<?php else: ?>
<!-- ═══ LISTA ════════════════════════════════════════════════════════ -->
<?php if (empty($modulos)): ?>
<div class="alert alert-warning">
    <i class="fa fa-exclamation-triangle mr-1"></i>
    No hay módulos en el catálogo. Usa el botón <strong>Sincronizar</strong> para detectarlos automáticamente.
</div>
<?php else:
    $por_fuente = [];
    foreach ($modulos as $mod) $por_fuente[$mod['fuente'] ?? 'manual'][] = $mod;
    foreach (['wpcargo_core','pagina','plugin','manual'] as $fuente):
        if (empty($por_fuente[$fuente])) continue;
        $fc = $fuentes_color[$fuente] ?? '#666';
        $fl = $fuentes_label[$fuente] ?? $fuente;
?>
<div style="margin-bottom:20px">
    <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:<?php echo esc_attr($fc); ?>;margin-bottom:8px;display:flex;align-items:center;gap:8px">
        <span style="width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr($fc); ?>;display:inline-block;flex-shrink:0"></span>
        <?php echo esc_html($fl); ?>
        <span style="color:#888;font-weight:400;text-transform:none;letter-spacing:0">(<?php echo count($por_fuente[$fuente]); ?>)</span>
    </div>
    <div style="background:#fff;border:1px solid #dee2e6;border-radius:6px;overflow:hidden">
    <table class="table table-hover table-sm mb-0">
        <thead class="thead-light">
            <tr><th style="width:36px">Ícono</th><th>Nombre</th><th>Slug</th><th>Página ID</th><th>Sidebar key</th><th style="width:90px">Acciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($por_fuente[$fuente] as $mod): ?>
            <tr>
                <td style="text-align:center"><i class="fa <?php echo esc_attr($mod['icon']??'fa-circle-o'); ?>" style="color:<?php echo esc_attr($fc); ?>;font-size:15px"></i></td>
                <td><strong><?php echo esc_html($mod['label']); ?></strong></td>
                <td><code class="small"><?php echo esc_html($mod['slug']); ?></code></td>
                <td class="small text-muted"><?php echo !empty($mod['page_id'])? intval($mod['page_id']):'—'; ?></td>
                <td class="small text-muted"><?php echo !empty($mod['sidebar_key'])?esc_html($mod['sidebar_key']):'—'; ?></td>
                <td>
                    <a href="<?php echo esc_url(add_query_arg(['wcrol_vista'=>'modulos','editar'=>$mod['slug']],$page_url)); ?>" class="btn btn-outline-primary btn-sm mr-1"><i class="fa fa-pencil"></i></a>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar este módulo?')">
                        <?php wp_nonce_field('wcrol_fe_modulo_nonce'); ?>
                        <input type="hidden" name="action" value="wcrol_fe_eliminar_modulo">
                        <input type="hidden" name="slug"   value="<?php echo esc_attr($mod['slug']); ?>">
                        <button type="submit" class="btn btn-outline-danger btn-sm"><i class="fa fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
</div>
<?php endforeach; endif; ?>
<?php endif; ?>
