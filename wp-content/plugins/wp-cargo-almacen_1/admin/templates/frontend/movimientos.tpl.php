<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<?php if ( $msg === 'guardado' ) : ?>
    <div class="alert alert-success alert-dismissible fade show">
        Movimiento registrado. <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php elseif ( $msg === 'eliminado' ) : ?>
    <div class="alert alert-info alert-dismissible fade show">
        Movimiento eliminado. <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0">
        <i class="fa fa-<?php echo $tipo === 'entrada' ? 'arrow-down text-success' : 'arrow-up text-danger'; ?> mr-2"></i>
        <?php echo $tipo === 'entrada' ? 'Entradas' : 'Salidas'; ?>
    </h5>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'nuevo-' . $tipo, $page_url ) ); ?>" class="btn btn-<?php echo $tipo === 'entrada' ? 'success' : 'danger'; ?> btn-sm">
        <i class="fa fa-plus mr-1"></i> Nueva <?php echo $tipo === 'entrada' ? 'Entrada' : 'Salida'; ?>
    </a>
</div>

<form method="get" class="form-inline mb-3 flex-wrap" style="gap:.5rem;">
    <input type="hidden" name="wpca" value="<?php echo esc_attr($tipo); ?>s">
    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Código, descripción o doc..." value="<?php echo esc_attr($buscar); ?>" style="min-width:180px;">
    <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo esc_attr($desde); ?>" title="Desde">
    <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo esc_attr($hasta); ?>" title="Hasta">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i> Filtrar</button>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', $tipo . 's', $page_url ) ); ?>" class="btn btn-secondary btn-sm">Limpiar</a>
</form>

<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead class="thead-light">
            <tr>
                <th>Fecha</th><th>Código</th><th>Descripción</th><th>Marca</th>
                <th class="text-center">Cantidad</th><th>Unid.</th><th>Lote</th><th>Documento</th><th>Notas</th>
                <th class="text-right">Acción</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty($movs) ) : ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No hay registros.</td></tr>
            <?php else : ?>
                <?php foreach ( $movs as $m ) : ?>
                    <tr>
                        <td><?php echo wpca_fecha($m->fecha); ?></td>
                        <td><code><?php echo esc_html($m->codigo); ?></code></td>
                        <td><?php echo esc_html($m->descripcion); ?></td>
                        <td><?php echo esc_html($m->marca); ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?php echo $tipo === 'entrada' ? 'success' : 'danger'; ?>">
                                <?php echo $tipo === 'entrada' ? '+' : '-'; ?><?php echo wpca_num($m->cantidad); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($m->unidad); ?></td>
                        <td><?php echo esc_html($m->lote); ?></td>
                        <td><?php echo esc_html($m->nro_documento); ?></td>
                        <td><?php echo esc_html($m->notas); ?></td>
                        <td class="text-right">
                            <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" onsubmit="return confirm('¿Eliminar este movimiento?');" style="display:inline;">
                                <?php wp_nonce_field('wpca_del_mov_nonce'); ?>
                                <input type="hidden" name="action" value="wpca_eliminar_mov">
                                <input type="hidden" name="id" value="<?php echo (int)$m->id; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
