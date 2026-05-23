<?php if ( ! defined( 'ABSPATH' ) ) exit;
$v = fn($f) => esc_attr($prev[$f] ?? ($producto ? $producto->$f : ''));
?>
<div class="wrap"><h1><?php echo $id ? 'Editar' : 'Nuevo'; ?> Producto</h1>
<?php if($error):?><div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div><?php endif;?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<?php wp_nonce_field('wpca_admin_prod_nonce'); ?>
<input type="hidden" name="action" value="wpca_admin_prod">
<input type="hidden" name="id" value="<?php echo (int)$id; ?>">
<table class="form-table">
<tr><th>Código *</th><td><input type="text" name="codigo" required value="<?php echo $v('codigo'); ?>" class="regular-text"></td></tr>
<tr><th>Descripción *</th><td><input type="text" name="descripcion" required value="<?php echo $v('descripcion'); ?>" class="large-text"></td></tr>
<tr><th>Marca</th><td><input type="text" name="marca" value="<?php echo $v('marca'); ?>" class="regular-text"></td></tr>
<tr><th>Unidad</th><td><select name="unidad">
<?php foreach(['UND','KG','LT','MT','CJ','DOC','PAR','SET'] as $u): ?>
<option value="<?php echo $u; ?>" <?php selected($v('unidad') ?: 'UND', $u); ?>><?php echo $u; ?></option>
<?php endforeach; ?>
</select></td></tr>
<tr><th>Stock mínimo</th><td><input type="number" name="stock_minimo" min="0" value="<?php echo $v('stock_minimo') ?: 0; ?>" class="small-text"></td></tr>
<?php if(!$id): ?><tr><th>Stock inicial</th><td><input type="number" name="stock_actual" min="0" value="0" class="small-text"></td></tr><?php endif; ?>
</table>
<?php submit_button($id ? 'Actualizar' : 'Crear Producto'); ?>
</form></div>
