<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>

<h5 class="mb-3">
    <i class="fa fa-<?php echo $tipo === 'entrada' ? 'arrow-down text-success' : 'arrow-up text-danger'; ?> mr-2"></i>
    <?php echo $tipo === 'entrada' ? 'Entradas' : 'Salidas'; ?>
</h5>

<form method="get" class="form-inline mb-3 flex-wrap" style="gap:.5rem;">
    <input type="hidden" name="wpca" value="<?php echo esc_attr($tipo); ?>s">
    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Buscar..." value="<?php echo esc_attr($buscar); ?>" style="min-width:180px;">
    <input type="date" name="desde" class="form-control form-control-sm" value="<?php echo esc_attr($desde); ?>">
    <input type="date" name="hasta" class="form-control form-control-sm" value="<?php echo esc_attr($hasta); ?>">
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i> Filtrar</button>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', $tipo . 's', $page_url ) ); ?>" class="btn btn-secondary btn-sm">Limpiar</a>
</form>

<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead class="thead-light">
            <tr>
                <th>Fecha</th><th>Código</th><th>Descripción</th>
                <th class="text-center">Cantidad</th><th>Unid.</th><th>Documento</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty($movs) ) : ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay registros.</td></tr>
            <?php else : ?>
                <?php foreach ( $movs as $m ) : ?>
                    <tr>
                        <td><?php echo wpca_fecha($m->fecha); ?></td>
                        <td><code><?php echo esc_html($m->codigo); ?></code></td>
                        <td><?php echo esc_html($m->descripcion); ?></td>
                        <td class="text-center">
                            <span class="badge badge-<?php echo $tipo === 'entrada' ? 'success' : 'danger'; ?>">
                                <?php echo $tipo === 'entrada' ? '+' : '-'; ?><?php echo wpca_num($m->cantidad); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($m->unidad); ?></td>
                        <td><?php echo esc_html($m->nro_documento); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
