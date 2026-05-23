<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
<h1 class="wp-heading-inline">Almacén — Stock</h1>
<a href="<?php echo esc_url(admin_url('admin.php?page=wpca-entradas&action=add')); ?>" class="page-title-action">+ Nueva Entrada</a>
<a href="<?php echo esc_url(admin_url('admin.php?page=wpca-salidas&action=add')); ?>" class="page-title-action">+ Nueva Salida</a>
<hr class="wp-header-end">
<?php if($msg==='guardado'):?><div class="notice notice-success is-dismissible"><p>Movimiento registrado.</p></div><?php endif;?>
<table class="wp-list-table widefat striped">
<thead><tr><th>Código</th><th>Descripción</th><th>Marca</th><th>Unidad</th><th>Stock</th><th>Mín.</th><th>Estado</th></tr></thead>
<tbody>
<?php if(empty($productos)): ?>
<tr><td colspan="7">No hay productos.</td></tr>
<?php else: foreach($productos as $p): ?>
<tr>
  <td><code><?php echo esc_html($p->codigo); ?></code></td>
  <td><?php echo esc_html($p->descripcion); ?></td>
  <td><?php echo esc_html($p->marca); ?></td>
  <td><?php echo esc_html($p->unidad); ?></td>
  <td><strong><?php echo wpca_num($p->stock_actual); ?></strong></td>
  <td><?php echo wpca_num($p->stock_minimo); ?></td>
  <td><?php echo wpca_stock_badge($p); ?></td>
</tr>
<?php endforeach; endif; ?>
</tbody></table></div>
