<?php if ( ! defined( 'ABSPATH' ) ) exit;
$fuentes = [
    'wpcargo_core' => ['WPCargo Core', '#2271b1'],
    'plugin'       => ['Plugin externo', '#00a32a'],
    'pagina'       => ['Página WP', '#6c757d'],
    'manual'       => ['Manual', '#b45309'],
];
?>
<div class="wrap">
<h1 class="wp-heading-inline">Módulos del Sidebar</h1>
<?php if (!($edit_slug || isset($_GET['editar']))): ?>
<a href="<?php echo esc_url(wcrol_url('wcrol-modulos',['editar'=>'nuevo'])); ?>" class="page-title-action">+ Añadir módulo manual</a>
<?php endif; ?>
<hr class="wp-header-end">

<p class="description" style="margin-bottom:16px">
    Los módulos se detectan automáticamente del sidebar de WPCargo. Puedes sincronizar para capturar nuevos módulos de plugins recién instalados.
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline;margin-left:8px">
        <?php wp_nonce_field('wcrol_sync_nonce'); ?>
        <input type="hidden" name="action" value="wcrol_sincronizar">
        <button type="submit" class="button button-small">
            <span class="dashicons dashicons-update" style="font-size:13px;width:13px;height:13px;vertical-align:middle"></span>
            Sincronizar módulos
        </button>
    </form>
</p>

<?php if ($edit_slug || isset($_GET['editar'])): ?>
<!-- ═══ FORMULARIO MÓDULO ══════════════════════════════════════════════ -->
<div class="postbox" style="max-width:700px">
    <div class="postbox-header">
        <h2 class="hndle"><?php echo $modulo ? 'Editar módulo: '.esc_html($modulo['label']) : 'Nuevo módulo manual'; ?></h2>
    </div>
    <div class="inside">
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('wcrol_modulo_nonce'); ?>
            <input type="hidden" name="action"         value="wcrol_guardar_modulo">
            <input type="hidden" name="slug_original"  value="<?php echo esc_attr($edit_slug); ?>">

            <table class="form-table" role="presentation">
                <tr>
                    <th><label for="wcrol-label">Etiqueta visible</label></th>
                    <td><input id="wcrol-label" name="label" type="text" class="regular-text"
                               value="<?php echo esc_attr($modulo['label']??''); ?>" required></td>
                </tr>
                <tr>
                    <th><label for="wcrol-slug">Slug (identificador único)</label></th>
                    <td>
                        <input id="wcrol-slug" name="slug" type="text" class="regular-text"
                               value="<?php echo esc_attr($modulo['slug']??''); ?>"
                               <?php echo $modulo ? 'readonly' : 'required'; ?>>
                        <?php if($modulo && in_array($modulo['fuente'],['wpcargo_core','plugin','pagina'],true)):?>
                            <p class="description">Módulo detectado automáticamente — el slug refleja el key del sidebar.</p>
                        <?php endif;?>
                    </td>
                </tr>
                <tr>
                    <th><label for="wcrol-icon">Ícono (Font Awesome 4)</label></th>
                    <td>
                        <div style="display:flex;align-items:center;gap:8px">
                            <input id="wcrol-icon" name="icon" type="text" class="regular-text"
                                   value="<?php echo esc_attr($modulo['icon']??'fa-circle-o'); ?>"
                                   placeholder="ej: fa-truck" oninput="document.getElementById('wcrol-icon-prev').className='fa '+this.value">
                            <i id="wcrol-icon-prev" class="fa <?php echo esc_attr($modulo['icon']??'fa-circle-o'); ?>" style="font-size:20px;width:24px;text-align:center;color:#2271b1"></i>
                        </div>
                        <p class="description">Íconos disponibles en <a href="https://fontawesome.com/v4/icons/" target="_blank">fontawesome.com/v4</a></p>
                    </td>
                </tr>
                <tr>
                    <th><label for="wcrol-pageid">Página WordPress (opcional)</label></th>
                    <td>
                        <select id="wcrol-pageid" name="page_id" class="regular-text">
                            <option value="0">— Ninguna (módulo de menú directo) —</option>
                            <?php foreach($paginas as $p): ?>
                            <option value="<?php echo intval($p->ID); ?>" <?php selected($modulo['page_id']??0,$p->ID); ?>>
                                <?php echo esc_html($p->post_title); ?> (ID: <?php echo $p->ID; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Asocia este módulo a una página con template dashboard.php para filtrarla.</p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="submit" class="button button-primary">Guardar módulo</button>
                <a href="<?php echo esc_url(wcrol_url('wcrol-modulos')); ?>" class="button">Cancelar</a>
            </p>
        </form>
    </div>
</div>

<?php else: ?>
<!-- ═══ LISTA DE MÓDULOS ══════════════════════════════════════════════ -->
<?php
$por_fuente = [];
foreach ($modulos as $mod) $por_fuente[$mod['fuente']][] = $mod;
$orden = ['wpcargo_core','pagina','plugin','manual'];
?>

<?php foreach ($orden as $fuente):
    if (empty($por_fuente[$fuente])) continue;
    [$flabel,$fcolor] = $fuentes[$fuente] ?? [$fuente,'#666'];
?>
<div style="margin-bottom:24px">
    <h3 style="margin:0 0 8px;font-size:13px;text-transform:uppercase;letter-spacing:.5px;color:<?php echo esc_attr($fcolor); ?>;display:flex;align-items:center;gap:8px">
        <span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:<?php echo esc_attr($fcolor); ?>"></span>
        <?php echo esc_html($flabel); ?>
        <span style="font-size:11px;color:#888;font-weight:400;text-transform:none;letter-spacing:0">(<?php echo count($por_fuente[$fuente]); ?>)</span>
    </h3>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr><th style="width:32px">Ícono</th><th>Nombre</th><th>Slug</th><th>Página vinculada</th><th style="width:130px">Acciones</th></tr>
        </thead>
        <tbody>
        <?php foreach ($por_fuente[$fuente] as $mod):
            $page = $mod['page_id'] ? get_post($mod['page_id']) : null;
        ?>
            <tr>
                <td style="text-align:center"><i class="fa <?php echo esc_attr($mod['icon']); ?>" style="font-size:16px;color:<?php echo esc_attr($fcolor); ?>"></i></td>
                <td><strong><?php echo esc_html($mod['label']); ?></strong></td>
                <td><code><?php echo esc_html($mod['slug']); ?></code></td>
                <td><?php echo $page ? esc_html($page->post_title).' <small style="color:#888">(ID:'.$page->ID.')</small>' : '<span style="color:#bbb">—</span>'; ?></td>
                <td>
                    <a href="<?php echo esc_url(wcrol_url('wcrol-modulos',['editar'=>$mod['slug']])); ?>" class="button button-small">Editar</a>
                    <?php if (in_array($fuente,['manual','plugin'],true)): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar este módulo del catálogo?')">
                        <?php wp_nonce_field('wcrol_modulo_nonce'); ?>
                        <input type="hidden" name="action" value="wcrol_eliminar_modulo">
                        <input type="hidden" name="slug"   value="<?php echo esc_attr($mod['slug']); ?>">
                        <button type="submit" class="button button-small button-link-delete">Eliminar</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>
<?php endif; ?>
</div>

<?php /* Cargar FA4 si no está ya cargado por WPCargo */ ?>
<script>
if (!document.querySelector('link[href*="font-awesome"]')) {
    var fa = document.createElement('link');
    fa.rel = 'stylesheet';
    fa.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css';
    document.head.appendChild(fa);
}
</script>
