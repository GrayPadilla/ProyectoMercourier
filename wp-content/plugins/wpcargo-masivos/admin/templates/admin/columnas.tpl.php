<?php if ( ! defined('ABSPATH') ) exit;
$tipos  = ['text'=>'Texto','number'=>'Número','phone'=>'Teléfono','email'=>'Email','select'=>'Lista (select)','textarea'=>'Texto largo','date'=>'Fecha (DD/MM/YYYY)'];
$anchos = ['sm'=>'Estrecho (90px)','md'=>'Normal (150px)','lg'=>'Ancho (220px)'];
?>
<div class="wrap">
<h1 class="wp-heading-inline">Envíos Masivos — Columnas</h1>
<?php if ( ! ($edit_id || isset($_GET['editar'])) ): ?>
<a href="<?php echo esc_url(wcmas_url('wcmas-columnas',['editar'=>'nuevo'])); ?>" class="page-title-action">+ Nueva columna</a>
<?php endif; ?>
<hr class="wp-header-end">

<?php if ( $edit_id || isset($_GET['editar']) ): ?>
<!-- ═══ FORMULARIO ════════════════════════════════════════════════════ -->
<div class="postbox" style="max-width:820px;margin-top:16px">
    <div class="postbox-header"><h2 class="hndle"><?php echo $columna ? 'Editar: '.esc_html($columna['label']) : 'Nueva columna'; ?></h2></div>
    <div class="inside">
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <?php wp_nonce_field('wcmas_columna_nonce'); ?>
        <input type="hidden" name="action"      value="wcmas_guardar_columna">
        <input type="hidden" name="id_original" value="<?php echo esc_attr($edit_id); ?>">

        <table class="form-table" role="presentation">
            <tr>
                <th><label for="wcmas-label">Etiqueta (encabezado) <span style="color:red">*</span></label></th>
                <td>
                    <input id="wcmas-label" name="label" type="text" class="regular-text"
                           value="<?php echo esc_attr($columna['label']??''); ?>" required>
                    <p class="description">Lo que verá el cliente en el encabezado.</p>
                </td>
            </tr>
            <tr>
                <th><label for="wcmas-id">ID interno <span style="color:red">*</span></label></th>
                <td>
                    <input id="wcmas-id" name="id" type="text" class="regular-text"
                           value="<?php echo esc_attr($columna['id']??''); ?>"
                           <?php echo $columna ? 'readonly' : 'required'; ?>>
                    <p class="description">Slug único (solo letras, números y _). <?php echo $columna?'No editable una vez creado.':''; ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="wcmas-metakey">Meta Key de WPCargo <span style="color:red">*</span></label></th>
                <td>
                    <input id="wcmas-metakey" name="meta_key" type="text" class="regular-text"
                           value="<?php echo esc_attr($columna['meta_key']??''); ?>" required
                           placeholder="ej: wpcargo_name, wpcargo_phone, wpcargo_address">
                    <p class="description">
                        Comunes: <code>wpcargo_name</code> <code>wpcargo_phone</code> <code>wpcargo_address</code>
                        <code>wpcargo_city</code> <code>wpcargo_description</code> <code>wpcargo_content</code>
                        <code>wpcargo_weight</code> <code>wpcargo_amount</code>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label for="wcmas-tipo">Tipo de dato</label></th>
                <td>
                    <select id="wcmas-tipo" name="tipo">
                        <?php foreach($tipos as $v=>$l): ?>
                        <option value="<?php echo esc_attr($v); ?>" <?php selected($columna['tipo']??'text',$v); ?>><?php echo esc_html($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr id="wcmas-opciones-row" style="<?php echo ($columna['tipo']??'')!=='select'?'display:none':''; ?>">
                <th><label>Opciones del select</label></th>
                <td>
                    <textarea name="opciones" rows="5" class="large-text" placeholder="Una opción por línea&#10;Express&#10;Normal"><?php echo esc_textarea(implode("\n",$columna['opciones']??[])); ?></textarea>
                </td>
            </tr>
            <tr>
                <th><label for="wcmas-default">Valor por defecto</label></th>
                <td>
                    <input id="wcmas-default" name="default_val" type="text" class="regular-text"
                           value="<?php echo esc_attr($columna['default_val']??''); ?>"
                           placeholder="Se aplica si el cliente deja la celda vacía">
                    <p class="description">El cliente no ve este valor — se inserta automáticamente en cada envío.</p>
                </td>
            </tr>
            <tr>
                <th><label for="wcmas-placeholder">Texto de ayuda (placeholder)</label></th>
                <td>
                    <input id="wcmas-placeholder" name="placeholder" type="text" class="regular-text"
                           value="<?php echo esc_attr($columna['placeholder']??''); ?>">
                </td>
            </tr>
            <tr>
                <th>Ancho de columna</th>
                <td>
                    <fieldset>
                        <?php foreach($anchos as $v=>$l): ?>
                        <label style="margin-right:16px">
                            <input type="radio" name="ancho" value="<?php echo esc_attr($v); ?>" <?php checked($columna['ancho']??'md',$v); ?>>
                            <?php echo esc_html($l); ?>
                        </label>
                        <?php endforeach; ?>
                    </fieldset>
                </td>
            </tr>
            <tr>
                <th>Estado y validación</th>
                <td>
                    <label style="display:block;margin-bottom:6px">
                        <input type="checkbox" name="activa" value="1" <?php checked(!empty($columna['activa'])); ?>>
                        <strong>Activa</strong> — aparece en la grilla del cliente
                    </label>
                    <label style="display:block">
                        <input type="checkbox" name="obligatorio" value="1" <?php checked(!empty($columna['obligatorio'])); ?>>
                        <strong>Obligatoria</strong> — la fila no se puede enviar sin este campo
                    </label>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" class="button button-primary">Guardar columna</button>
            <a href="<?php echo esc_url(wcmas_url('wcmas-columnas')); ?>" class="button">Cancelar</a>
        </p>
    </form>
    </div>
</div>
<script>
document.getElementById('wcmas-label').addEventListener('input',function(){
    var s=document.getElementById('wcmas-id'); if(!s.readOnly) s.value=this.value.toLowerCase().replace(/\s+/g,'_').replace(/[^a-z0-9_]/g,'');
});
document.getElementById('wcmas-tipo').addEventListener('change',function(){
    document.getElementById('wcmas-opciones-row').style.display=this.value==='select'?'':'none';
});
</script>

<?php else: ?>
<!-- ═══ LISTA DE COLUMNAS + PREVIEW ══════════════════════════════════ -->
<div style="display:flex;gap:24px;margin-top:16px;align-items:flex-start">

    <div style="flex:1">
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;flex-wrap:wrap">
            <p class="description" style="margin:0">
                <span class="dashicons dashicons-move" style="vertical-align:middle"></span>
                Arrastra las filas para reordenar. Solo las columnas <strong>Activas</strong> aparecen en la grilla del cliente.
            </p>
            <button type="submit" form="wcmas-orden-form" class="button button-primary" id="wcmas-btn-guardar-orden" style="display:none">
                <span class="dashicons dashicons-saved" style="font-size:14px;width:14px;height:14px;vertical-align:middle;margin-right:3px"></span>
                Guardar orden
            </button>
        </div>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" id="wcmas-orden-form">
            <?php wp_nonce_field('wcmas_reordenar_nonce'); ?>
            <input type="hidden" name="action" value="wcmas_reordenar">
        </form>
        <table class="wp-list-table widefat fixed striped" id="wcmas-tabla">
            <thead>
                <tr>
                    <th style="width:24px"></th>
                    <th>Etiqueta</th>
                    <th>Meta Key</th>
                    <th>Tipo</th>
                    <th style="width:55px;text-align:center">Activa</th>
                    <th style="width:60px;text-align:center">Oblig.</th>
                    <th style="width:120px">Acciones</th>
                </tr>
            </thead>
            <tbody id="wcmas-tbody">
            <?php foreach($columnas as $col): ?>
                <tr data-id="<?php echo esc_attr($col['id']); ?>" style="cursor:grab">
                    <td style="text-align:center;color:#bbb;font-size:16px">⠿</td>
                    <td>
                        <strong><?php echo esc_html($col['label']); ?></strong>
                        <?php if($col['default_val']??''): ?>
                        <br><small style="color:#888">Default: <em><?php echo esc_html($col['default_val']); ?></em></small>
                        <?php endif; ?>
                    </td>
                    <td><code style="font-size:11px"><?php echo esc_html($col['meta_key']); ?></code></td>
                    <td><?php echo esc_html($tipos[$col['tipo']]??$col['tipo']); ?></td>
                    <td style="text-align:center"><?php echo !empty($col['activa'])?'<span style="color:#00a32a;font-weight:700">✓</span>':'<span style="color:#ccc">—</span>'; ?></td>
                    <td style="text-align:center"><?php echo !empty($col['obligatorio'])?'<span style="color:#d63638;font-weight:700">✓</span>':'<span style="color:#ccc">—</span>'; ?></td>
                    <td>
                        <a href="<?php echo esc_url(wcmas_url('wcmas-columnas',['editar'=>$col['id']])); ?>" class="button button-small">Editar</a>
                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline" onsubmit="return confirm('¿Eliminar columna «<?php echo esc_js($col['label']); ?>»?')">
                            <?php wp_nonce_field('wcmas_columna_nonce'); ?>
                            <input type="hidden" name="action" value="wcmas_eliminar_columna">
                            <input type="hidden" name="id"     value="<?php echo esc_attr($col['id']); ?>">
                            <button type="submit" class="button button-small button-link-delete">Borrar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Preview -->
    <div style="width:300px;flex-shrink:0;position:sticky;top:32px">
        <h4 style="margin:0 0 8px;font-size:13px">Vista previa del cliente</h4>
        <div style="font-size:11px;border:1px solid #ddd;border-radius:4px;overflow:hidden">
            <div style="background:#2271b1;color:#fff;padding:6px 10px;font-weight:600">Carga masiva de envíos</div>
            <div style="overflow-x:auto">
            <table style="border-collapse:collapse;width:100%">
                <thead>
                    <tr style="background:#f6f7f7">
                        <th style="padding:5px 6px;border:1px solid #ddd;color:#666;min-width:28px">#</th>
                        <?php foreach(array_filter($columnas,fn($c)=>!empty($c['activa'])) as $col): ?>
                        <th style="padding:5px 6px;border:1px solid #ddd;font-weight:600;white-space:nowrap;min-width:<?php echo $col['ancho']==='sm'?'60':($col['ancho']==='lg'?'120':'90'); ?>px">
                            <?php echo esc_html($col['label']); ?><?php if(!empty($col['obligatorio'])): ?><span style="color:red">*</span><?php endif; ?>
                        </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php for($i=1;$i<=4;$i++): ?>
                    <tr>
                        <td style="padding:4px 6px;border:1px solid #eee;color:#bbb;text-align:center"><?php echo $i; ?></td>
                        <?php foreach(array_filter($columnas,fn($c)=>!empty($c['activa'])) as $col): ?>
                        <td style="padding:4px 6px;border:1px solid #eee">
                            <span style="color:#d0d0d0;font-size:10px;font-style:italic"><?php echo esc_html(substr($col['placeholder']??'',0,12)); ?>...</span>
                        </td>
                        <?php endforeach; ?>
                    </tr>
                    <?php endfor; ?>
                </tbody>
            </table>
            </div>
        </div>
        <p class="description" style="margin-top:8px;font-size:11px">
            <?php $activas = count(array_filter($columnas,fn($c)=>!empty($c['activa']))); ?>
            <strong><?php echo $activas; ?></strong> columna(s) activa(s) visible(s) para el cliente.
        </p>
        <a href="<?php echo esc_url(wcmas_url('wcmas-grilla')); ?>" class="button button-small" style="margin-top:4px">
            <span class="dashicons dashicons-visibility" style="font-size:13px;width:13px;height:13px;vertical-align:middle"></span>
            Ver grilla completa
        </a>
    </div>
</div>

<script>
(function(){
    var tbody  = document.getElementById('wcmas-tbody');
    var btnSave= document.getElementById('wcmas-btn-guardar-orden');
    if (!tbody || !btnSave) return;

    var dragging   = null;
    var ordenCambio= false;

    tbody.querySelectorAll('tr').forEach(function(row){
        row.draggable = true;

        row.addEventListener('dragstart', function(e){
            dragging = this;
            this.style.opacity = '.4';
            e.dataTransfer.effectAllowed = 'move';
        });

        row.addEventListener('dragend', function(){
            this.style.opacity = '';
            dragging = null;
            // Quitar highlight de todas las filas
            tbody.querySelectorAll('tr').forEach(function(r){ r.style.borderTop = ''; });
        });

        row.addEventListener('dragover', function(e){
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            var r = this.getBoundingClientRect();
            tbody.querySelectorAll('tr').forEach(function(r){ r.style.borderTop = ''; });
            if (e.clientY < r.top + r.height / 2) {
                this.style.borderTop = '2px solid #2271b1';
                tbody.insertBefore(dragging, this);
            } else {
                this.style.borderTop = '';
                tbody.insertBefore(dragging, this.nextSibling);
            }
            // Mostrar botón guardar al detectar cambio
            if (!ordenCambio) {
                ordenCambio = true;
                sincronizarOrdenEnForm();
                btnSave.style.display = '';
                btnSave.classList.add('button-primary');
            }
        });

        row.addEventListener('drop', function(e){
            e.preventDefault();
            tbody.querySelectorAll('tr').forEach(function(r){ r.style.borderTop=''; });
            sincronizarOrdenEnForm();
            renumerarFilas();
            btnSave.style.display = '';
        });
    });

    function sincronizarOrdenEnForm(){
        var form = document.getElementById('wcmas-orden-form');
        // Limpiar inputs anteriores
        form.querySelectorAll('input[name="orden[]"]').forEach(function(i){ i.remove(); });
        // Agregar en el orden actual
        tbody.querySelectorAll('tr').forEach(function(row){
            var inp = document.createElement('input');
            inp.type  = 'hidden';
            inp.name  = 'orden[]';
            inp.value = row.getAttribute('data-id');
            form.appendChild(inp);
        });
    }

    function renumerarFilas(){
        tbody.querySelectorAll('tr').forEach(function(tr, i){
            var td = tr.querySelector('td:first-child');
            // El primer td tiene el ícono de arrastre, no el número — saltar
        });
    }

    // Sincronizar al cargar por si se envía el form sin arrastrar
    sincronizarOrdenEnForm();
})();
</script>
<?php endif; ?>
</div>
