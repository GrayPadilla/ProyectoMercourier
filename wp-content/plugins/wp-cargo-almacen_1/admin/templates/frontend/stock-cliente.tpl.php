<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h5 class="mb-3"><i class="fa fa-boxes mr-2"></i> Stock actual</h5>

<?php
$sin_stock = array_filter( $productos, fn($p) => (int)$p->stock_actual <= 0 );
$bajo      = array_filter( $productos, fn($p) => (int)$p->stock_minimo > 0 && (int)$p->stock_actual > 0 && (int)$p->stock_actual <= (int)$p->stock_minimo );
?>

<?php foreach ( $sin_stock as $p ) : ?>
    <div class="alert alert-danger py-2">
        <i class="fa fa-times-circle mr-1"></i> <strong><?php echo esc_html($p->codigo); ?></strong> — <?php echo esc_html($p->descripcion); ?>: <strong>Sin stock</strong>
    </div>
<?php endforeach; ?>

<?php foreach ( $bajo as $p ) : ?>
    <div class="alert alert-warning py-2">
        <i class="fa fa-exclamation-triangle mr-1"></i> <strong><?php echo esc_html($p->codigo); ?></strong> — <?php echo esc_html($p->descripcion); ?>: stock bajo (<?php echo wpca_num($p->stock_actual); ?> <?php echo esc_html($p->unidad); ?>)
    </div>
<?php endforeach; ?>

<form method="get" class="form-inline mb-3 flex-wrap" style="gap:.5rem;">
    <input type="hidden" name="wpca" value="">
    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Código o descripción..." value="<?php echo esc_attr($buscar); ?>" style="min-width:180px;">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i> Filtrar</button>
    <a href="<?php echo esc_url($page_url); ?>" class="btn btn-secondary btn-sm">Limpiar</a>
</form>

<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead class="thead-light">
            <tr>
                <th>Código</th><th>Descripción</th><th>Marca</th><th>Unidad</th>
                <th class="text-center">Stock</th><th class="text-center">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty($productos) ) : ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay productos.</td></tr>
            <?php else : ?>
                <?php foreach ( $productos as $p ) : ?>
                    <tr>
                        <td><code><?php echo esc_html($p->codigo); ?></code></td>
                        <td><?php echo esc_html($p->descripcion); ?></td>
                        <td><?php echo esc_html($p->marca); ?></td>
                        <td><?php echo esc_html($p->unidad); ?></td>
                        <td class="text-center"><strong><?php echo wpca_num($p->stock_actual); ?></strong></td>
                        <td class="text-center"><?php echo wpca_stock_badge($p); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
