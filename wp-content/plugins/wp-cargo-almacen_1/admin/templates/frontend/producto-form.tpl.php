<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<style>
.wpca-select { display: block !important; width: 100%; height: calc(1.5em + .75rem + 2px); padding: .375rem 1.75rem .375rem .75rem; font-size: 1rem; line-height: 1.5; color: #495057; background-color: #fff; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' width='4' height='5' viewBox='0 0 4 5'%3e%3cpath fill='%23343a40' d='M2 0L0 2h4zm0 5L0 3h4z'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right .75rem center; background-size: 8px 10px; border: 1px solid #ced4da; border-radius: .25rem; -webkit-appearance: none; -moz-appearance: none; appearance: none; }
.wpca-select:focus { border-color: #80bdff; outline: 0; box-shadow: 0 0 0 .2rem rgba(0,123,255,.25); }
</style>

<?php
$v = fn( $f ) => esc_attr( $prev[ $f ] ?? ( $producto->$f ?? '' ) );
$codigo_full  = $prev['codigo'] ?? ( $producto->codigo ?? '' );
$codigo_num   = preg_match( '/^DHV-(.+)$/i', trim( $codigo_full ), $m ) ? $m[1] : '';
$imagen_actual = $prev['imagen_url'] ?? ( $producto->imagen ?? '' );
$unidades_map  = [
    'UND' => 'UND — Unidad',   'KG'  => 'KG — Kilogramos',
    'LT'  => 'LT — Litros',    'MT'  => 'MT — Metros',
    'CJ'  => 'CJ — Caja',      'DOC' => 'DOC — Docena',
    'PAR' => 'PAR — Par',       'SET' => 'SET — Conjunto',
];
$sel_unidad = $v( 'unidad' ) ?: 'UND';
?>

<!-- Cabecera -->
<div class="d-flex align-items-center mb-3">
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'productos', $page_url ) ); ?>" class="btn btn-outline-secondary btn-sm mr-2">
        <i class="fa fa-arrow-left"></i>
    </a>
    <h5 class="mb-0"><?php echo $id ? 'Editar Producto' : 'Nuevo Producto'; ?></h5>
    <?php if ( $id && $producto ) : ?>
        <span class="badge badge-secondary ml-2" style="font-size:.85rem;"><?php echo esc_html( $producto->codigo ); ?></span>
    <?php endif; ?>
</div>

<?php if ( $error ) : ?>
    <div class="alert alert-danger"><?php echo esc_html( $error ); ?></div>
<?php endif; ?>

<div class="card">
<div class="card-body">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="wpca-prod-form">
    <?php wp_nonce_field( 'wpca_prod_nonce' ); ?>
    <input type="hidden" name="action"     value="wpca_guardar_prod">
    <input type="hidden" name="id"         value="<?php echo (int) $id; ?>">
    <input type="hidden" name="imagen_url" id="wpca_imagen_url" value="<?php echo esc_attr( $imagen_actual ); ?>">
    <input type="hidden" name="codigo"     id="wpca_codigo_hidden" value="<?php echo esc_attr( $codigo_full ); ?>">

    <!-- ① Código + Descripción -->
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <label class="font-weight-bold">Código <span class="text-danger">*</span></label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text font-weight-bold" style="background:#e9ecef;">DHV-</span>
                    </div>
                    <input type="text" name="codigo_num" id="wpca_codigo_num"
                           class="form-control" required
                           value="<?php echo esc_attr( $codigo_num ); ?>"
                           placeholder="0001"
                           style="text-transform:uppercase;"
                           <?php if ( $id ) echo 'readonly style="background:#f8f9fa;text-transform:uppercase;"'; ?>>
                </div>
                <?php if ( $id ) : ?><small class="text-muted">No se puede modificar.</small><?php endif; ?>
            </div>
        </div>
        <div class="col-sm-8">
            <div class="form-group">
                <label class="font-weight-bold">Descripción <span class="text-danger">*</span></label>
                <input type="text" name="descripcion" class="form-control" required
                       value="<?php echo $v( 'descripcion' ); ?>"
                       placeholder="Nombre del producto">
            </div>
        </div>
    </div>

    <!-- ② Cliente (Marca) -->
    <div class="form-group">
        <label class="font-weight-bold">Cliente (Marca)</label>
        <?php if ( ! empty( $clientes ) ) : ?>
            <select name="marca" class="wpca-select">
                <option value="">— Sin asignar —</option>
                <?php foreach ( $clientes as $c ) : ?>
                    <option value="<?php echo esc_attr( $c->label ); ?>" <?php selected( $v( 'marca' ), $c->label ); ?>>
                        <?php echo esc_html( $c->label ); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php else : ?>
            <input type="text" name="marca" class="form-control"
                   value="<?php echo $v( 'marca' ); ?>"
                   placeholder="Nombre del cliente / marca">
            <small class="text-muted">No hay usuarios con rol <code>wpcargo_client</code>.</small>
        <?php endif; ?>
    </div>

    <!-- ③ Unidad + Stock mínimo -->
    <div class="row">
        <div class="col-sm-4">
            <div class="form-group">
                <label class="font-weight-bold">
                    Unidad de medida
                    <i class="fa fa-question-circle ml-1 text-muted"
                       data-toggle="tooltip"
                       title="UND=Unidad · KG=Kilogramos · LT=Litros · MT=Metros · CJ=Caja · DOC=Docena · PAR=Par · SET=Conjunto"
                       style="cursor:help;"></i>
                </label>
                <select name="unidad" class="wpca-select">
                    <?php foreach ( $unidades_map as $val => $lbl ) : ?>
                        <option value="<?php echo esc_attr( $val ); ?>" <?php selected( $sel_unidad, $val ); ?>>
                            <?php echo esc_html( $lbl ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="col-sm-4">
            <div class="form-group">
                <label class="font-weight-bold">Stock mínimo</label>
                <input type="number" name="stock_minimo" class="form-control"
                       min="0" value="<?php echo $v( 'stock_minimo' ) !== '' ? $v( 'stock_minimo' ) : 0; ?>">
                <small class="text-muted">Alerta cuando llegue a este nivel.</small>
            </div>
        </div>
        <?php if ( ! $id ) : ?>
        <div class="col-sm-4">
            <div class="form-group">
                <label class="font-weight-bold">Stock inicial</label>
                <input type="number" name="stock_actual" class="form-control" min="0" value="0">
                <small class="text-muted">Base permanente del producto.</small>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ④ Imagen -->
    <div class="form-group">
        <label class="font-weight-bold">Imagen del producto</label>
        <div class="d-flex align-items-start flex-wrap" style="gap:1rem;">
            <div id="wpca-img-preview"
                 style="width:100px;height:100px;border:2px dashed #dee2e6;border-radius:6px;
                        display:flex;align-items:center;justify-content:center;
                        overflow:hidden;background:#f8f9fa;flex-shrink:0;">
                <?php if ( $imagen_actual ) : ?>
                    <img src="<?php echo esc_url( $imagen_actual ); ?>" style="max-width:100%;max-height:100%;object-fit:contain;">
                <?php else : ?>
                    <span style="color:#adb5bd;font-size:.75rem;text-align:center;padding:8px;">
                        <i class="fa fa-image fa-2x d-block mb-1"></i>Sin imagen
                    </span>
                <?php endif; ?>
            </div>
            <div>
                <input type="file" id="wpca-img-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="wpca-img-btn">
                    <i class="fa fa-upload mr-1"></i><?php echo $imagen_actual ? 'Cambiar imagen' : 'Subir imagen'; ?>
                </button>
                <?php if ( $imagen_actual ) : ?>
                    <button type="button" class="btn btn-outline-danger btn-sm ml-1" id="wpca-img-remove">
                        <i class="fa fa-trash"></i> Quitar
                    </button>
                <?php endif; ?>
                <div id="wpca-img-status" class="mt-1" style="font-size:.8rem;"></div>
                <small class="text-muted d-block mt-1">JPG, PNG, GIF o WEBP · Máx. 2MB.</small>
            </div>
        </div>
    </div>

    <hr>
    <button type="submit" class="btn btn-primary">
        <i class="fa fa-save mr-1"></i><?php echo $id ? 'Actualizar' : 'Crear Producto'; ?>
    </button>
    <a href="<?php echo esc_url( add_query_arg( 'wpca', 'productos', $page_url ) ); ?>" class="btn btn-secondary ml-2">Cancelar</a>
</form>
</div>
</div>

<script>
(function(){
    // DHV- sync
    var numInp = document.getElementById('wpca_codigo_num');
    var hidden = document.getElementById('wpca_codigo_hidden');
    function sync(){ if(numInp&&hidden) hidden.value = numInp.value.trim() ? 'DHV-'+numInp.value.trim().toUpperCase() : ''; }
    if(numInp){ numInp.addEventListener('input', sync); sync(); }

    // Image upload
    var btn    = document.getElementById('wpca-img-btn');
    var file   = document.getElementById('wpca-img-file');
    var urlInp = document.getElementById('wpca_imagen_url');
    var status = document.getElementById('wpca-img-status');
    var prev   = document.getElementById('wpca-img-preview');
    var rmv    = document.getElementById('wpca-img-remove');

    function setPrev(url){
        if(!url){
            prev.innerHTML='<span style="color:#adb5bd;font-size:.75rem;text-align:center;padding:8px;"><i class="fa fa-image fa-2x d-block mb-1"></i>Sin imagen</span>';
            if(urlInp) urlInp.value='';
            if(btn) btn.innerHTML='<i class="fa fa-upload mr-1"></i>Subir imagen';
        } else {
            prev.innerHTML='<img src="'+url+'" style="max-width:100%;max-height:100%;object-fit:contain;">';
            if(urlInp) urlInp.value=url;
            if(btn) btn.innerHTML='<i class="fa fa-upload mr-1"></i>Cambiar imagen';
        }
    }
    if(btn&&file){
        btn.addEventListener('click',function(){ file.click(); });
        file.addEventListener('change',function(){
            var f=file.files[0]; if(!f) return;
            if(f.size>2*1024*1024){ status.innerHTML='<span class="text-danger">Máximo 2MB.</span>'; return; }
            status.innerHTML='<span class="text-muted"><i class="fa fa-spinner fa-spin mr-1"></i>Subiendo...</span>';
            btn.disabled=true;
            var fd=new FormData();
            fd.append('action','wpca_upload_imagen');
            fd.append('_ajax_nonce','<?php echo wp_create_nonce("wpca_upload_imagen"); ?>');
            fd.append('imagen',f);
            fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>',{method:'POST',body:fd,credentials:'same-origin'})
            .then(function(r){return r.json();})
            .then(function(d){
                btn.disabled=false;
                if(d.success){ setPrev(d.data.url); status.innerHTML='<span class="text-success"><i class="fa fa-check mr-1"></i>Subida correctamente.</span>'; }
                else { status.innerHTML='<span class="text-danger">'+(d.data||'Error al subir.')+'</span>'; }
            }).catch(function(){ btn.disabled=false; status.innerHTML='<span class="text-danger">Error de conexión.</span>'; });
        });
    }
    if(rmv){ rmv.addEventListener('click',function(){ setPrev(''); status.innerHTML=''; }); }

    // Submit validation
    var form=document.getElementById('wpca-prod-form');
    if(form){ form.addEventListener('submit',function(e){ if(numInp&&!numInp.value.trim()){ e.preventDefault(); alert('El código es obligatorio.'); numInp.focus(); } else { sync(); } }); }

    // Tooltip
    if(typeof $!=='undefined'&&$.fn&&$.fn.tooltip){ $('[data-toggle="tooltip"]').tooltip(); }
})();
</script>
