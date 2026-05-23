<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap"><h1>Reportes de Almacén</h1>
<p>Total productos activos: <strong><?php echo count(WPCA_Producto::obtener_todos()); ?></strong></p>
</div>
