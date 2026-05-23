<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<style>
.wpca-select { display: block !important; width: 100%; height: calc(1.5em + .75rem + 2px); padding: .375rem 1.75rem .375rem .75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 8px 10px; border: 1px solid #ced4da; border-radius: .25rem; appearance: none; }
.wpca-select:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); }
.wpca-select.form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem 1.5rem .25rem .5rem; font-size: .875rem; }
</style>


<?php if ( $msg === 'creado' || $msg === 'actualizado' ) : ?>
    <div class="alert alert-success alert-dismissible fade show">
        Producto <?php echo $msg === 'creado' ? 'creado' : 'actualizado'; ?> correctamente.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php elseif ( $msg === 'eliminado' ) : ?>
    <div class="alert alert-info alert-dismissible fade show">
        Producto desactivado.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0"><i class="fa fa-box mr-2"></i> Productos</h5>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'nuevo-producto', $page_url ) ); ?>" class="btn btn-primary btn-sm">
        <i class="fa fa-plus mr-1"></i> Nuevo Producto
    </a>
</div>

<form method="get" class="form-inline mb-3 flex-wrap" style="gap:.5rem;">
    <input type="hidden" name="wpca" value="productos">
    <?php $page_id = wpca_get_frontend_page_id(); echo '<input type="hidden" name="page_id" value="' . $page_id . '">'; ?>
    <input type="text" name="buscar" class="form-control form-control-sm" placeholder="Código o descripción..." value="<?php echo esc_attr( $buscar ); ?>" style="min-width:180px;">
    <?php if ( ! empty( $clientes ) ) : ?>
    <select name="marca" class="wpca-select form-control-sm">
        <option value="">— Todos los clientes —</option>
        <?php foreach ( $clientes as $cliente ) : ?>
            <option value="<?php echo esc_attr( $cliente->label ); ?>" <?php selected( $marca, $cliente->label ); ?>>
                <?php echo esc_html( $cliente->label ); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php else : ?>
    <input type="text" name="marca" class="form-control form-control-sm" placeholder="Cliente / Marca..." value="<?php echo esc_attr( $marca ); ?>" style="min-width:160px;">
    <?php endif; ?>
    <button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-filter mr-1"></i> Filtrar</button>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'productos', $page_url ) ); ?>" class="btn btn-secondary btn-sm">Limpiar</a>
</form>

<div class="table-responsive">
    <table class="table table-hover table-sm">
        <thead class="thead-light">
            <tr>
                <th style="width:50px;"></th><th>Código</th><th>Descripción</th><th>Cliente</th><th>Unidad</th>
                <th class="text-center">Stock</th><th class="text-center">Mín.</th>
                <th class="text-center">Estado</th><th class="text-right">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $productos ) ) : ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay productos.</td></tr>
            <?php else : ?>
                <?php foreach ( $productos as $p ) : ?>
                    <tr>
                        <td>
                            <?php if ( ! empty( $p->imagen ) ) : ?>
                                <img src="<?php echo esc_url( $p->imagen ); ?>" style="width:40px;height:40px;object-fit:contain;border-radius:4px;border:1px solid #dee2e6;">
                            <?php else : ?>
                                <span style="display:inline-block;width:40px;height:40px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;"></span>
                            <?php endif; ?>
                        </td>
                        <td><code><?php echo esc_html( $p->codigo ); ?></code></td>
                        <td><?php echo esc_html( $p->descripcion ); ?></td>
                        <td><?php echo esc_html( $p->marca ); ?></td>
                        <td><?php echo esc_html( $p->unidad ); ?></td>
                        <td class="text-center"><strong><?php echo wpca_num( $p->stock_actual ); ?></strong></td>
                        <td class="text-center"><?php echo wpca_num( $p->stock_minimo ); ?></td>
                        <td class="text-center"><?php echo wpca_stock_badge( $p ); ?></td>
                        <td class="text-right" style="white-space:nowrap;">
                            <a href="<?php echo esc_url( add_query_arg( [ 'wpca' => 'editar-producto', 'id' => $p->id ], $page_url ) ); ?>" class="btn btn-outline-secondary btn-sm" title="Editar">
                                <i class="fa fa-pencil"></i>
                            </a>
                            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('¿Desactivar este producto?');" style="display:inline;">
                                <?php wp_nonce_field( 'wpca_del_prod_nonce' ); ?>
                                <input type="hidden" name="action" value="wpca_eliminar_prod">
                                <input type="hidden" name="id" value="<?php echo (int) $p->id; ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm" title="Desactivar"><i class="fa fa-ban"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
