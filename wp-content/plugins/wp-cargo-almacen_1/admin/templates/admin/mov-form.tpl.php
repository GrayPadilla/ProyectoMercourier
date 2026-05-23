<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap"><h1>Nueva <?php echo ucfirst($tipo); ?></h1>
<?php if($error):?><div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div><?php endif;?>
<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
<?php wp_nonce_field('wpca_admin_mov_nonce'); ?>
<input type="hidden" name="action" value="wpca_admin_mov">
<input type="hidden" name="tipo" value="<?php echo esc_attr($tipo); ?>">
<table class="form-table">
<tr><th>Producto</th><td><select name="producto_id" required class="regular-text">
<option value="">— Seleccionar —</option>
<?php foreach($productos as $p): ?>
<option value="<?php echo (int)$p->id; ?>" <?php selected($prod_pre,$p->id); ?>>[<?php echo esc_html($p->codigo); ?>] <?php echo esc_html($p->descripcion); ?> — <?php echo (int)$p->stock_actual; ?> <?php echo esc_html($p->unidad); ?></option>
<?php endforeach; ?>
</select></td></tr>
<tr><th>Cantidad</th><td><input type="number" name="cantidad" min="1" required class="small-text"></td></tr>
<tr><th>Fecha</th><td><input type="date" name="fecha" value="<?php echo date('Y-m-d'); ?>" required></td></tr>
<tr><th>Lote</th><td><input type="text" name="lote" class="regular-text"></td></tr>
<tr><th>Nro. Documento</th><td><input type="text" name="nro_documento" class="regular-text"></td></tr>
<tr><th>Notas</th><td><textarea name="notas" class="regular-text" rows="2"></textarea></td></tr>
</table>
<?php submit_button('Registrar '.ucfirst($tipo)); ?>
</form></div>
