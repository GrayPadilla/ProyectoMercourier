<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1>Almacén — <?php echo esc_html(ucfirst($tipo)); ?>s</h1>
<a href="<?php echo esc_url(admin_url('admin.php?page=wpca-'.$tipo.'s&action=add')); ?>" class="page-title-action">+ Nueva <?php echo ucfirst($tipo); ?></a>
<hr class="wp-header-end">
<?php if($msg==='guardado'):?><div class="notice notice-success is-dismissible"><p>Guardado.</p></div>
<?php elseif($msg==='eliminado'):?><div class="notice notice-info is-dismissible"><p>Eliminado.</p></div><?php endif;?>
<table class="wp-list-table widefat striped">
<thead><tr><th>Fecha</th><th>Código</th><th>Descripción</th><th>Cantidad</th><th>Documento</th><th>Notas</th><th>Acción</th></tr></thead>
<tbody>
<?php if(empty($movs)): ?>
<tr><td colspan="7">No hay registros.</td></tr>
<?php else: foreach($movs as $m): ?>
<tr>
  <td><?php echo wpca_fecha($m->fecha); ?></td>
  <td><code><?php echo esc_html($m->codigo); ?></code></td>
  <td><?php echo esc_html($m->descripcion); ?></td>
  <td><?php echo wpca_num($m->cantidad); ?> <?php echo esc_html($m->unidad); ?></td>
  <td><?php echo esc_html($m->nro_documento); ?></td>
  <td><?php echo esc_html($m->notas); ?></td>
  <td><form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('¿Eliminar?');">
    <?php wp_nonce_field('wpca_admin_del_mov_nonce'); ?>
    <input type="hidden" name="action" value="wpca_admin_del_mov">
    <input type="hidden" name="id" value="<?php echo (int)$m->id; ?>">
    <button class="button">Eliminar</button></form>
  </td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
