<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ( $msg === 'guardado' ) : ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Movimiento registrado correctamente.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php elseif ( $msg === 'eliminado' ) : ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        Movimiento eliminado.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-boxes mr-2"></i> Stock actual</h5>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'nuevo-entrada', $page_url ) ); ?>" class="btn btn-success btn-sm">
        <i class="fa fa-plus mr-1"></i> Nueva Entrada
    </a>
</div>

<?php
$total_prod   = count( $productos );
$total_uds    = array_sum( array_column( $productos, 'stock_actual' ) );
$cnt_bajo     = count( array_filter( $productos, fn($p) => (int)$p->stock_minimo > 0 && (int)$p->stock_actual <= (int)$p->stock_minimo && (int)$p->stock_actual > 0 ) );
$cnt_cero     = count( array_filter( $productos, fn($p) => (int)$p->stock_actual <= 0 ) );
?>

<div class="row mb-4">
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center border-primary">
            <div class="card-body py-3">
                <div style="font-size:2rem;">📦</div>
                <div class="h4 font-weight-bold mb-0"><?php echo wpca_num( $total_prod ); ?></div>
                <small class="text-muted">Productos</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center border-success">
            <div class="card-body py-3">
                <div style="font-size:2rem;">🔢</div>
                <div class="h4 font-weight-bold mb-0"><?php echo wpca_num( $total_uds ); ?></div>
                <small class="text-muted">Unidades totales</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center border-warning">
            <div class="card-body py-3">
                <div style="font-size:2rem;">⚠️</div>
                <div class="h4 font-weight-bold mb-0"><?php echo wpca_num( $cnt_bajo ); ?></div>
                <small class="text-muted">Stock bajo</small>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-6 mb-3">
        <div class="card text-center border-danger">
            <div class="card-body py-3">
                <div style="font-size:2rem; color:#e74c3c;">✕</div>
                <div class="h4 font-weight-bold mb-0"><?php echo wpca_num( $cnt_cero ); ?></div>
                <small class="text-muted">Sin stock</small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<form method="get" class="form-inline mb-3 flex-wrap" style="gap:.5rem;">
    <input type="hidden" name="wpca" value="">
    <?php
    global $wp;
    $page_id = wpca_get_frontend_page_id();
    echo '<input type="hidden" name="page_id" value="' . $page_id . '">';
    ?>
    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Código o descripción..." value="<?php echo esc_attr( $buscar ); ?>" style="min-width:180px;">
    <select name="marca" class="form-control form-control-sm">
        <option value="">Todas las marcas</option>
        <?php foreach ( $marcas as $m ) : ?>
            <option value="<?php echo esc_attr( $m ); ?>" <?php selected( $marca, $m ); ?>><?php echo esc_html( $m ); ?></option>
        <?php endforeach; ?>
    </select>
    <label class="mb-0"><input type="checkbox" name="stock_bajo" value="1" <?php checked( $stock_bajo ); ?> class="mr-1"> Solo stock bajo</label>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i> Filtrar</button>
    <a href="<?php echo esc_url( $page_url ); ?>" class="btn btn-secondary btn-sm"><i class="fa fa-times mr-1"></i> Limpiar</a>
</form>

<!-- Tabla -->
<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead class="thead-light">
            <tr>
                <th>Código</th>
                <th>Descripción</th>
                <th>Marca</th>
                <th>Unidad</th>
                <th class="text-center">Stock</th>
                <th class="text-center">Estado</th>
                <th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $productos ) ) : ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay productos.</td></tr>
            <?php else : ?>
                <?php foreach ( $productos as $p ) : ?>
                    <tr>
                        <td><code><?php echo esc_html( $p->codigo ); ?></code></td>
                        <td><?php echo esc_html( $p->descripcion ); ?></td>
                        <td><?php echo esc_html( $p->marca ); ?></td>
                        <td><?php echo esc_html( $p->unidad ); ?></td>
                        <td class="text-center"><strong><?php echo wpca_num( $p->stock_actual ); ?></strong></td>
                        <td class="text-center"><?php echo wpca_stock_badge( $p ); ?></td>
                        <td class="text-right" style="white-space:nowrap;">
                            <a href="<?php echo esc_url( add_query_arg( [ 'wpca' => 'nuevo-entrada', 'prod' => $p->id ], $page_url ) ); ?>" class="btn btn-outline-success btn-sm" title="Entrada">
                                <i class="fa fa-plus"></i>
                            </a>
                            <a href="<?php echo esc_url( add_query_arg( [ 'wpca' => 'nuevo-salida', 'prod' => $p->id ], $page_url ) ); ?>" class="btn btn-outline-danger btn-sm" title="Salida">
                                <i class="fa fa-minus"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
