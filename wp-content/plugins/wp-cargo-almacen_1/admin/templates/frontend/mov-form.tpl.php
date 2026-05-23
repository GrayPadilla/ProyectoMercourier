<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<style>
.wpca-select { display: block !important; width: 100%; height: calc(1.5em + .75rem + 2px); padding: .375rem 1.75rem .375rem .75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 8px 10px; border: 1px solid #ced4da; border-radius: .25rem; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
.wpca-select:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); }
.wpca-select.form-control-sm { height: calc(1.5em + .5rem + 2px); padding: .25rem 1.5rem .25rem .5rem; font-size: .875rem; }
</style>


<div class="d-flex align-items-center mb-3">
    <a href="<?php echo esc_url( add_query_arg( 'wpca', $tipo . 's', $page_url ) ); ?>" class="btn btn-outline-secondary btn-sm mr-2">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0">Nueva <?php echo $tipo === 'entrada' ? 'Entrada' : 'Salida'; ?></h5>
</div>

<?php if ( $error ) : ?>
    <div class="alert alert-danger"><?php echo esc_html($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
            <?php wp_nonce_field('wpca_mov_nonce'); ?>
            <input type="hidden" name="action" value="wpca_guardar_mov">
            <input type="hidden" name="tipo" value="<?php echo esc_attr($tipo); ?>">

            <div class="form-group">
                <label>Producto <span class="text-danger">*</span></label>
                <select name="producto_id" class="wpca-select" required id="wpca-prod-sel">
                    <option value="">— Seleccionar producto —</option>
                    <?php foreach ( $productos as $p ) : ?>
                        <option value="<?php echo (int)$p->id; ?>"
                                data-stock="<?php echo (int)$p->stock_actual; ?>"
                                data-unidad="<?php echo esc_attr($p->unidad); ?>"
                                <?php selected( $prod_pre, $p->id ); ?>>
                            [<?php echo esc_html($p->codigo); ?>] <?php echo esc_html($p->descripcion); ?>
                            — Stock: <?php echo (int)$p->stock_actual; ?> <?php echo esc_html($p->unidad); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if ( $tipo === 'salida' ) : ?>
                <div id="wpca-stock-hint" class="alert alert-info py-2" style="display:none;">
                    Stock disponible: <strong id="wpca-stock-val">—</strong>
                </div>
            <?php endif; ?>

            <div class="form-row">
                <div class="form-group col-md-4">
                    <label>Cantidad <span class="text-danger">*</span></label>
                    <input type="number" name="cantidad" class="form-control" min="1" required placeholder="0">
                </div>
                <div class="form-group col-md-4">
                    <label>Fecha <span class="text-danger">*</span></label>
                    <input type="date" name="fecha" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group col-md-4">
                    <label>Lote</label>
                    <input type="text" name="lote" class="form-control" placeholder="Número de lote">
                </div>
            </div>

            <div class="form-group">
                <label>Nro. Documento / Referencia</label>
                <input type="text" name="nro_documento" class="form-control" placeholder="Factura, GRE, OC...">
            </div>

            <div class="form-group">
                <label>Notas</label>
                <textarea name="notas" class="form-control" rows="2" placeholder="Observaciones..."></textarea>
            </div>

            <button type="submit" class="btn btn-<?php echo $tipo === 'entrada' ? 'success' : 'danger'; ?>">
                <i class="fa fa-save mr-1"></i> Registrar <?php echo $tipo === 'entrada' ? 'Entrada' : 'Salida'; ?>
            </button>
            <a href="<?php echo esc_url( add_query_arg('wpca', $tipo.'s', $page_url) ); ?>" class="btn btn-secondary ml-2">Cancelar</a>
        </form>
    </div>
</div>

<?php if ( $tipo === 'salida' ) : ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var sel   = document.getElementById('wpca-prod-sel');
    var hint  = document.getElementById('wpca-stock-hint');
    var val   = document.getElementById('wpca-stock-val');
    function update() {
        var opt = sel.options[sel.selectedIndex];
        if (opt && opt.value) {
            var stock = opt.getAttribute('data-stock');
            var unidad = opt.getAttribute('data-unidad');
            val.textContent = stock + ' ' + unidad;
            hint.style.display = 'block';
        } else {
            hint.style.display = 'none';
        }
    }
    sel.addEventListener('change', update);
    update();
});
</script>
<?php endif; ?>
