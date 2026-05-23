<?php if ( ! defined('ABSPATH') ) exit;
$ancho_px = ['sm' => '90px', 'md' => '150px', 'lg' => '220px'];
$draft_key = 'wcmas_draft_u' . get_current_user_id();
$es_admin  = $es_admin ?? false;
$historial = $historial ?? [];
?>

<!-- ═══ JS config ════════════════════════════════════════════════════ -->
<script>
var WCMAS = {
    ajax_url  : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
    nonce     : '<?php echo esc_js($nonce); ?>',
    columnas  : <?php echo WCMAS_Columnas::para_js(true); ?>,
    filas_init: <?php echo intval($filas_init); ?>,
    draft_key : '<?php echo esc_js($draft_key); ?>',
    es_admin  : <?php echo $es_admin ? 'true' : 'false'; ?>
};
</script>

<!-- ═══ ENCABEZADO ════════════════════════════════════════════════════ -->
<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <div class="mr-auto">
        <h5 class="mb-0"><i class="fa fa-table mr-2 text-primary"></i>Carga Masiva de Envíos</h5>
        <small class="text-muted">Completa la tabla o <strong>copia y pega desde Excel / Google Sheets</strong> con <kbd>Ctrl+V</kbd>.</small>
    </div>
    <div id="wcmas-toolbar" class="d-flex align-items-center" style="gap:6px">
        <?php if($es_admin && !empty($usuarios ?? [])): ?>
        <div class="d-flex align-items-center mr-2" style="gap:6px">
            <label class="mb-0 small font-weight-bold text-muted">Asignar a:</label>
            <select id="wcmas-admin-user" class="form-control form-control-sm" style="max-width:220px">
                <?php foreach(($usuarios??[]) as $u): ?>
                <option value="<?php echo intval($u['id']); ?>" <?php selected($u['id'],get_current_user_id()); ?>>
                    <?php echo esc_html($u['label']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php elseif($es_admin): ?>
        <!-- Select2 dinámico (admin page — sin lista precargada) -->
        <div class="d-flex align-items-center mr-2" style="gap:6px">
            <label class="mb-0 small font-weight-bold text-muted">Asignar a:</label>
            <select id="wcmas-admin-user" name="asignar_a" style="min-width:200px;max-width:260px">
                <option value="">— Seleccionar cliente —</option>
            </select>
        </div>
        <?php endif; ?>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="wcmas-btn-add5">
            <i class="fa fa-plus mr-1"></i>+5 filas
        </button>
        <button type="button" class="btn btn-outline-warning btn-sm" id="wcmas-btn-borrador" title="Guardar borrador manualmente">
            <i class="fa fa-floppy-o mr-1"></i>Borrador
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="wcmas-btn-limpiar">
            <i class="fa fa-trash mr-1"></i>Limpiar
        </button>
        <div style="width:1px;height:26px;background:#dee2e6;margin:0 2px"></div>
        <button type="button" class="btn btn-success btn-sm px-3" id="wcmas-btn-preview" disabled>
            <i class="fa fa-eye mr-1"></i>Vista previa
            <span id="wcmas-badge" class="badge badge-light ml-1" style="font-size:11px">0</span>
        </button>
    </div>
</div>

<!-- Aviso borrador -->
<div id="wcmas-draft-aviso" class="alert alert-warning small" style="display:none;margin-bottom:10px">
    <i class="fa fa-floppy-o mr-1"></i>
    <strong>Tienes un borrador guardado.</strong>
    <button type="button" class="btn btn-warning btn-sm ml-2" id="wcmas-draft-restaurar">Restaurar</button>
    <button type="button" class="btn btn-link btn-sm ml-1 text-muted" id="wcmas-draft-descartar">Descartar</button>
</div>

<!-- Hint paste -->
<div class="alert alert-info small mb-2" id="wcmas-hint">
    <i class="fa fa-lightbulb-o mr-1"></i>
    <strong>Copiar y pegar desde Excel:</strong> Selecciona tu rango de datos en Excel (sin encabezados), copia con <kbd>Ctrl+C</kbd>, haz clic en cualquier celda de la tabla y pega con <kbd>Ctrl+V</kbd>. Las columnas se detectan automáticamente.
    <button type="button" onclick="this.closest('.alert').style.display='none'" class="close ml-2" style="font-size:14px">&times;</button>
</div>

<!-- ═══ GRILLA ════════════════════════════════════════════════════════ -->
<div id="wcmas-grid-wrap" style="overflow-x:auto;margin-bottom:8px;border:1px solid #dee2e6;border-radius:4px">
<table id="wcmas-grid" style="border-collapse:collapse;width:100%;table-layout:fixed;min-width:400px">
    <colgroup>
        <col style="width:36px">
        <?php foreach($columnas as $col): ?>
        <col style="width:<?php echo esc_attr($ancho_px[$col['ancho']] ?? '150px'); ?>">
        <?php endforeach; ?>
        <col style="width:28px">
    </colgroup>
    <thead>
        <tr style="background:#f8f9fa;position:sticky;top:0;z-index:2">
            <th style="padding:7px 4px;text-align:center;font-size:11px;color:#888;border:1px solid #dee2e6;font-weight:600">#</th>
            <?php foreach($columnas as $col): ?>
            <th style="padding:7px 8px;font-size:12px;font-weight:700;color:#1d2327;border:1px solid #dee2e6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis" title="<?php echo esc_attr($col['label'].($col['meta_key']?' ('.$col['meta_key'].')':'')); ?>">
                <?php echo esc_html($col['label']); ?><?php if(!empty($col['obligatorio'])): ?><span style="color:#dc3545;margin-left:2px">*</span><?php endif; ?>
                <?php if(!empty($col['default_val'])): ?><span title="Valor por defecto: <?php echo esc_attr($col['default_val']); ?>" style="color:#6c757d;margin-left:3px;cursor:help">ⓘ</span><?php endif; ?>
            </th>
            <?php endforeach; ?>
            <th style="border:1px solid #dee2e6;width:28px"></th>
        </tr>
    </thead>
    <tbody id="wcmas-tbody"></tbody>
</table>
</div>
<div style="font-size:11px;color:#888;margin-bottom:16px">
    <span id="wcmas-stats">0 filas con datos</span>
    · <span id="wcmas-valid-info"></span>
</div>

<!-- ═══ HISTORIAL RÁPIDO ══════════════════════════════════════════════ -->
<?php if ($historial): ?>
<div style="margin-top:24px;border-top:1px solid #dee2e6;padding-top:16px">
    <h6 class="font-weight-bold mb-2" style="font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:#6c757d">
        <i class="fa fa-history mr-1"></i>Últimas importaciones
    </h6>
    <table class="table table-sm table-hover" style="font-size:12px">
        <thead class="thead-light"><tr><th>Fecha</th><?php if($es_admin): ?><th>Asignado a</th><?php endif; ?><th>Total</th><th>✅</th><th>❌</th></tr></thead>
        <tbody>
        <?php foreach($historial as $h): ?>
            <tr>
                <td><?php echo esc_html(date_i18n('d/m/Y H:i',strtotime($h->fecha))); ?></td>
                <?php if($es_admin): ?><td><?php echo esc_html($h->asignado_nombre??'—'); ?></td><?php endif; ?>
                <td><?php echo intval($h->total_filas); ?></td>
                <td class="text-success font-weight-bold"><?php echo intval($h->total_ok); ?></td>
                <td class="<?php echo $h->total_errores>0?'text-danger font-weight-bold':'text-muted'; ?>"><?php echo intval($h->total_errores); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- ═══ MODAL VISTA PREVIA ════════════════════════════════════════════ -->
<div id="wcmas-modal-overlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:9999;overflow-y:auto;padding:40px 16px">
<div style="background:#fff;border-radius:8px;max-width:900px;margin:0 auto;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <!-- Header modal -->
    <div style="background:#2271b1;color:#fff;padding:16px 20px;display:flex;align-items:center;gap:12px">
        <i class="fa fa-eye fa-lg"></i>
        <div style="flex:1">
            <h5 class="mb-0" style="font-size:16px">Vista previa — Confirmar carga</h5>
            <small id="wcmas-modal-subtitle" style="opacity:.85"></small>
        </div>
        <button type="button" id="wcmas-modal-cerrar-x" style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:0;line-height:1">&times;</button>
    </div>
    <!-- Body -->
    <div style="padding:20px">
        <!-- Resumen -->
        <div id="wcmas-modal-resumen" style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap"></div>
        <!-- Errores de validación -->
        <div id="wcmas-modal-errores-wrap" style="display:none;margin-bottom:14px">
            <div class="alert alert-warning small mb-2">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                <strong>Filas con errores:</strong> Se crearán solo las filas válidas. Las inválidas quedarán marcadas en la tabla.
            </div>
        </div>
        <!-- Tabla preview -->
        <div style="overflow-x:auto;max-height:360px;overflow-y:auto;border:1px solid #dee2e6;border-radius:4px">
        <table class="table table-sm table-bordered mb-0" id="wcmas-modal-tabla">
            <thead class="thead-light" id="wcmas-modal-thead"></thead>
            <tbody id="wcmas-modal-tbody"></tbody>
        </table>
        </div>
    </div>
    <!-- Footer -->
    <div style="padding:14px 20px;border-top:1px solid #dee2e6;background:#f8f9fa;display:flex;justify-content:flex-end;gap:8px">
        <button type="button" class="btn btn-outline-secondary" id="wcmas-modal-cerrar">Cancelar</button>
        <button type="button" class="btn btn-success px-4" id="wcmas-modal-confirmar">
            <i class="fa fa-paper-plane mr-1"></i>Crear envíos
        </button>
    </div>
</div>
</div>

<!-- ═══ LOADER ════════════════════════════════════════════════════════ -->
<div id="wcmas-loader" style="display:none;text-align:center;padding:40px 20px;color:#6c757d">
    <i class="fa fa-spinner fa-spin fa-3x d-block mb-3"></i>
    <h5 id="wcmas-loader-msg">Creando envíos...</h5>
    <div class="progress mt-3" style="max-width:320px;margin:0 auto;height:8px">
        <div id="wcmas-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-success" style="width:0%;transition:width .3s"></div>
    </div>
    <small class="text-muted mt-2 d-block" id="wcmas-loader-sub"></small>
</div>

<!-- ═══ RESULTADO FINAL ═══════════════════════════════════════════════ -->
<div id="wcmas-resultado" style="display:none">
    <hr>
    <div id="wcmas-resultado-resumen" class="mb-3"></div>
    <div class="table-responsive">
    <table class="table table-sm table-bordered" style="font-size:12px">
        <thead class="thead-light">
            <tr><th style="width:45px">Fila</th><th>Tracking</th><th>Destinatario</th><th>Estado</th><th>Detalle</th></tr>
        </thead>
        <tbody id="wcmas-resultado-body"></tbody>
    </table>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="wcmas-btn-nueva">
        <i class="fa fa-refresh mr-1"></i>Nueva carga
    </button>
</div>

<!-- ═══ JAVASCRIPT PRINCIPAL ══════════════════════════════════════════ -->
<script>
(function(){
'use strict';

/* ── Estado ──────────────────────────────────────────────────────────── */
var cols       = WCMAS.columnas;
var draftKey   = WCMAS.draft_key;
var tbody      = document.getElementById('wcmas-tbody');
var numFilas   = WCMAS.filas_init;
var ANCHO      = { sm:'90px', md:'150px', lg:'220px' };

/* ── Estilos de celda ────────────────────────────────────────────────── */
var S = {
    cell: 'width:100%;height:100%;padding:4px 6px;border:none;outline:none;font-size:13px;font-family:inherit;box-sizing:border-box;background:transparent',
    ok  : 'background:#f0fff4',
    err : 'background:#fff5f5',
    none: ''
};

/* ── Crear input/select para una columna ─────────────────────────────── */
function crearInput(col) {
    var el;
    if (col.tipo === 'select' && col.opciones && col.opciones.length) {
        el = document.createElement('select');
        el.style.cssText = S.cell + ';cursor:pointer';
        var o0 = document.createElement('option'); o0.value = ''; o0.textContent = '—'; el.appendChild(o0);
        col.opciones.forEach(function(op) {
            var o = document.createElement('option'); o.value = op; o.textContent = op; el.appendChild(o);
        });
    } else if (col.tipo === 'date') {
        el = document.createElement('input');
        el.type        = 'text';
        el.style.cssText = S.cell;
        el.placeholder = col.placeholder || 'DD/MM/YYYY';
        el.autocomplete = 'off';
        el.readOnly     = true; // forzar uso del datepicker
        el.style.cursor = 'pointer';
        el.setAttribute('data-datepicker', '1');
    } else {
        el = document.createElement('input');
        el.type        = 'text';
        el.style.cssText = S.cell;
        el.placeholder = col.placeholder || '';
        el.autocomplete = 'off';
        el.spellcheck   = false;
    }
    el.setAttribute('data-col', col.id);
    return el;
}

/* ── Crear fila ──────────────────────────────────────────────────────── */
function crearFila(n) {
    var tr = document.createElement('tr');
    tr.setAttribute('data-n', n);
    tr.style.borderBottom = '1px solid #f0f0f0';

    // Nº
    var tdN = document.createElement('td');
    tdN.style.cssText = 'text-align:center;color:#bbb;font-size:11px;padding:0;border:1px solid #dee2e6;width:36px;user-select:none;background:#fafafa';
    tdN.textContent = n;
    tr.appendChild(tdN);

    // Columnas
    cols.forEach(function(col) {
        var td = document.createElement('td');
        td.style.cssText = 'padding:0;border:1px solid #dee2e6;position:relative';
        td.setAttribute('data-td', col.id);

        var inp = crearInput(col);
        inp.addEventListener('blur',    function() { validarInput(inp, col); actualizarStats(); });
        inp.addEventListener('focus',   function() { if(inp.select) inp.select(); });
        inp.addEventListener('input',   function() { guardarBorradorDebounce(); });
        inp.addEventListener('paste',   function(e) { manejarPaste(e, tr, col); });
        inp.addEventListener('keydown', function(e) { manejarTeclas(e, inp, tr, col); });

        td.appendChild(inp);
        tr.appendChild(td);

        // Inicializar flatpickr para columnas de tipo date
        if (col.tipo === 'date' && inp.getAttribute('data-datepicker')) {
            wcmasInitDatepicker(inp);
        }
    });

    // Estado
    var tdSt = document.createElement('td');
    tdSt.style.cssText = 'text-align:center;padding:2px;border:1px solid #dee2e6;width:28px;font-size:13px';
    tdSt.className = 'wcmas-est';
    tr.appendChild(tdSt);

    return tr;
}

/* ── Flatpickr: inicializar datepicker con domingos bloqueados ───────── */
function wcmasInitDatepicker(inp) {
    if (typeof flatpickr === 'undefined') {
        setTimeout(function(){ wcmasInitDatepicker(inp); }, 300);
        return;
    }

    // Input oculto paralelo que guarda el valor en YYYY-MM-DD para enviar al servidor
    // WPCargo guarda internamente en YYYY-MM-DD (confirmado en su documentación)
    var hiddenId = inp.getAttribute('data-col') + '_raw';
    var hidden = document.createElement('input');
    hidden.type = 'hidden';
    hidden.id   = hiddenId;
    inp.parentNode.insertBefore(hidden, inp.nextSibling);

    flatpickr(inp, {
        locale       : 'es',
        dateFormat   : 'd/m/Y',      // muestra DD/MM/YYYY al usuario
        altInput     : false,
        allowInput   : false,
        disableMobile: true,
        disable      : [
            function(date) { return date.getDay() === 0; } // bloquear domingos
        ],
        onChange: function(selectedDates, dateStr, instance) {
            // Guardar YYYY-MM-DD en el input oculto que leerá recolectarFilas()
            if (selectedDates.length) {
                var d  = selectedDates[0];
                var yy = d.getFullYear();
                var mm = String(d.getMonth()+1).padStart(2,'0');
                var dd = String(d.getDate()).padStart(2,'0');
                hidden.value = yy + '-' + mm + '-' + dd;
            } else {
                hidden.value = '';
            }
            inp.setAttribute('data-raw', hidden.value);
            inp.dispatchEvent(new Event('blur'));
            guardarBorradorDebounce();
        },
    });
}

/* ── Validación JS de tipo date ─────────────────────────────────────── */
function validarDate(valor, label) {
    // Acepta tanto DD/MM/YYYY (display) como YYYY-MM-DD (raw guardado)
    var d, mo, y, fecha;
    var mDMY = valor.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    var mYMD = valor.match(/^(\d{4})-(\d{2})-(\d{2})$/);
    if (mDMY) {
        d = parseInt(mDMY[1],10); mo = parseInt(mDMY[2],10); y = parseInt(mDMY[3],10);
    } else if (mYMD) {
        y = parseInt(mYMD[1],10); mo = parseInt(mYMD[2],10); d = parseInt(mYMD[3],10);
    } else {
        return label + ': formato inválido (DD/MM/YYYY).';
    }
    fecha = new Date(y, mo-1, d);
    if (fecha.getFullYear()!==y || fecha.getMonth()!==mo-1 || fecha.getDate()!==d) {
        return label + ': fecha no existe.';
    }
    if (fecha.getDay() === 0) return label + ': no puede ser domingo.';
    return null;
}

/* ── Inicializar grilla ──────────────────────────────────────────────── */
function initGrilla(datos) {
    tbody.innerHTML = '';
    numFilas = Math.max(WCMAS.filas_init, datos ? datos.length + 5 : WCMAS.filas_init);
    for (var i = 1; i <= numFilas; i++) tbody.appendChild(crearFila(i));
    if (datos) rellenarDatos(datos);
    actualizarStats();
}

function rellenarDatos(datos) {
    var filas = tbody.querySelectorAll('tr');
    datos.forEach(function(fila, ri) {
        if (ri >= filas.length) return;
        cols.forEach(function(col) {
            var inp = filas[ri].querySelector('[data-col="' + col.id + '"]');
            if (inp && fila[col.id] !== undefined) { inp.value = fila[col.id]; validarInput(inp, col); }
        });
        actualizarEstFila(filas[ri]);
    });
}

/* ── Pegado desde Excel ──────────────────────────────────────────────── */
function manejarPaste(e, trOrigen, colInicio) {
    var texto = (e.clipboardData || window.clipboardData).getData('text/plain');
    if (!texto) return;
    // Solo manejar si tiene tabs (multi-columna) o newlines (multi-fila)
    if (texto.indexOf('\t') === -1 && texto.indexOf('\n') === -1) return;
    e.preventDefault();

    var lineas  = texto.replace(/\r\n/g,'\n').replace(/\r/g,'\n').split('\n').filter(function(l){ return l !== ''; });
    var colIdx  = cols.findIndex(function(c){ return c.id === colInicio.id; });
    var filaIdx = Array.from(tbody.children).indexOf(trOrigen);

    // Asegurar suficientes filas
    var needed = filaIdx + lineas.length;
    while (tbody.children.length < needed) {
        numFilas++;
        tbody.appendChild(crearFila(numFilas));
    }

    lineas.forEach(function(linea, ri) {
        var valores = linea.split('\t');
        var trActual = tbody.children[filaIdx + ri];
        valores.forEach(function(val, ci) {
            var ci2 = colIdx + ci;
            if (ci2 >= cols.length) return;
            var inp = trActual.querySelector('[data-col="' + cols[ci2].id + '"]');
            if (inp) { inp.value = val.trim(); validarInput(inp, cols[ci2]); }
        });
        actualizarEstFila(trActual);
    });
    actualizarStats();
    guardarBorradorDebounce();
    renumerarFilas();
}

/* ── Navegación con teclado tipo Excel ───────────────────────────────── */
function manejarTeclas(e, inp, tr, col) {
    if (e.key === 'Tab') {
        e.preventDefault();
        var ci = cols.findIndex(function(c){ return c.id === col.id; });
        var siguiente;
        if (!e.shiftKey) {
            siguiente = ci < cols.length - 1
                ? tr.querySelector('[data-col="' + cols[ci+1].id + '"]')
                : null;
            if (!siguiente && tr.nextElementSibling) {
                siguiente = tr.nextElementSibling.querySelector('[data-col="' + cols[0].id + '"]');
            }
        } else {
            siguiente = ci > 0
                ? tr.querySelector('[data-col="' + cols[ci-1].id + '"]')
                : null;
            if (!siguiente && tr.previousElementSibling) {
                siguiente = tr.previousElementSibling.querySelector('[data-col="' + cols[cols.length-1].id + '"]');
            }
        }
        if (siguiente) siguiente.focus();
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        var trSig = tr.nextElementSibling;
        if (!trSig) { numFilas++; trSig = crearFila(numFilas); tbody.appendChild(trSig); }
        var ci = cols.findIndex(function(c){ return c.id === col.id; });
        var inp2 = trSig.querySelector('[data-col="' + cols[ci].id + '"]');
        if (inp2) inp2.focus();
    }
}

/* ── Validación ──────────────────────────────────────────────────────── */
function validarInput(inp, col) {
    var val = (inp.value || '').trim();
    var err = '';
    if (col.obligatorio && val === '' && !(col.default_val)) err = col.label + ' es obligatorio.';
    else if (val !== '') {
        switch(col.tipo) {
            case 'number': if(isNaN(parseFloat(val.replace(',','.')))) err=col.label+': debe ser un número.'; break;
            case 'phone':  if(!/^\d{7,15}$/.test(val.replace(/[\s\-\+\(\)]/g,''))) err=col.label+': teléfono inválido.'; break;
            case 'email':  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) err=col.label+': email inválido.'; break;
            case 'date':   var dateErr = validarDate(val, col.label); if(dateErr) err = dateErr; break;
        }
    }
    if (err) {
        inp.style.background = '#fff5f5';
        inp.style.borderBottom = '2px solid #dc3545';
        inp.title = err;
    } else if (val !== '') {
        inp.style.background = '#f0fff4';
        inp.style.borderBottom = '2px solid #28a745';
        inp.title = '';
    } else {
        inp.style.background = '';
        inp.style.borderBottom = '';
        inp.title = '';
    }
    return err;
}

function filaVacia(tr) {
    return cols.every(function(col){
        var i = tr.querySelector('[data-col="'+col.id+'"]');
        return !i || (i.value||'').trim() === '';
    });
}

function validarFila(tr) {
    var errs = [];
    cols.forEach(function(col){
        var inp = tr.querySelector('[data-col="'+col.id+'"]');
        if(!inp) return;
        var e = validarInput(inp,col);
        if(e) errs.push({col:col.id, msg:e});
    });
    return errs;
}

function actualizarEstFila(tr) {
    var td = tr.querySelector('.wcmas-est');
    if (!td) return;
    if (filaVacia(tr)) { td.textContent=''; td.title=''; return; }
    var errs = validarFila(tr);
    td.textContent = errs.length === 0 ? '✅' : '⚠️';
    td.title       = errs.map(function(e){ return e.msg; }).join('\n');
}

/* ── Stats ───────────────────────────────────────────────────────────── */
function actualizarStats() {
    var total=0, ok=0, err=0;
    Array.from(tbody.children).forEach(function(tr){
        if(filaVacia(tr)) return;
        total++;
        var errs = validarFila(tr);
        actualizarEstFila(tr);
        if(errs.length===0) ok++; else err++;
    });
    document.getElementById('wcmas-stats').textContent = total + ' fila(s) con datos';
    var info = document.getElementById('wcmas-valid-info');
    if(total>0) info.innerHTML = '<span style="color:#28a745">'+ok+' válida(s)</span>' + (err?' · <span style="color:#dc3545">'+err+' con errores</span>':'');
    else info.textContent='';
    document.getElementById('wcmas-btn-preview').disabled = (ok === 0);
    document.getElementById('wcmas-badge').textContent = ok;
}

function renumerarFilas() {
    Array.from(tbody.children).forEach(function(tr,i){
        var td = tr.querySelector('td:first-child'); if(td) td.textContent=i+1;
    });
}

/* ── Recolectar datos de la grilla ───────────────────────────────────── */
function recolectarFilas(soloValidas) {
    var res = [];
    Array.from(tbody.children).forEach(function(tr,i){
        if(filaVacia(tr)) return;
        var errs = validarFila(tr);
        if(soloValidas && errs.length>0) return;
        var fila = {_idx: i, _errs: errs};
        cols.forEach(function(col){
            var inp = tr.querySelector('[data-col="'+col.id+'"]');
            if (!inp) { fila[col.id] = ''; return; }
            // Para columnas de tipo date: enviar YYYY-MM-DD al servidor (data-raw)
            // pero mostrar DD/MM/YYYY al usuario (inp.value)
            if (col.tipo === 'date' && inp.getAttribute('data-raw')) {
                fila[col.id] = inp.getAttribute('data-raw');
            } else {
                fila[col.id] = (inp.value||'').trim();
            }
        });
        res.push(fila);
    });
    return res;
}

/* ── Borrador (localStorage) ─────────────────────────────────────────── */
var _draftTimer = null;
function guardarBorradorDebounce() {
    clearTimeout(_draftTimer);
    _draftTimer = setTimeout(function(){ guardarBorrador(false); }, 1500);
}

function guardarBorrador(manual) {
    try {
        var datos = recolectarFilas(false);
        var payload = datos.map(function(f){
            var d={};
            cols.forEach(function(c){ d[c.id]=f[c.id]||''; });
            return d;
        });
        localStorage.setItem(draftKey, JSON.stringify({ts: Date.now(), datos: payload}));
        if(manual){
            var btn=document.getElementById('wcmas-btn-borrador');
            btn.textContent='✓ Guardado';
            setTimeout(function(){ btn.innerHTML='<i class="fa fa-floppy-o mr-1"></i>Borrador'; }, 2000);
        }
    } catch(e){}
}

function cargarBorrador() {
    try {
        var raw = localStorage.getItem(draftKey);
        if(!raw) return null;
        var obj = JSON.parse(raw);
        if(!obj || !obj.datos || !obj.datos.length) return null;
        return obj;
    } catch(e){ return null; }
}

function limpiarBorrador() { try{ localStorage.removeItem(draftKey); }catch(e){} }

/* ── Modal de vista previa ───────────────────────────────────────────── */
var _filasParaEnviar = [];

function abrirModal() {
    var validas = recolectarFilas(true);
    var todas   = recolectarFilas(false);
    var invalidas = todas.length - validas.length;
    _filasParaEnviar = validas;

    // Subtítulo
    var userLabel = '';
    var selU = document.getElementById('wcmas-admin-user');
    if(selU) { var opt=selU.options[selU.selectedIndex]; userLabel = opt ? opt.textContent : ''; }
    document.getElementById('wcmas-modal-subtitle').textContent =
        'Se crearán ' + validas.length + ' envío(s)' + (userLabel?' asignados a '+userLabel:'');

    // Resumen
    var resDiv = document.getElementById('wcmas-modal-resumen');
    resDiv.innerHTML = [
        ['<span style="font-size:1.4rem;font-weight:700;color:#28a745">'+validas.length+'</span>', 'filas válidas', '#d7f7c2', '#135d3e'],
        invalidas > 0 ? ['<span style="font-size:1.4rem;font-weight:700;color:#dc3545">'+invalidas+'</span>', 'filas con errores <small>(no se crearán)</small>', '#fce9e9', '#8a1a1a'] : null,
    ].filter(Boolean).map(function(x){
        return '<div style="background:'+x[2]+';border-radius:6px;padding:10px 16px;color:'+x[3]+'">' + x[0] + '<div class="small mt-1">' + x[1] + '</div></div>';
    }).join('');

    // Errores
    document.getElementById('wcmas-modal-errores-wrap').style.display = invalidas>0 ? '' : 'none';

    // Tabla preview
    var thead = document.getElementById('wcmas-modal-thead');
    var tbodyM= document.getElementById('wcmas-modal-tbody');
    thead.innerHTML = '<tr><th>#</th>' + cols.map(function(c){ return '<th>'+esc(c.label)+'</th>'; }).join('') + '<th>Estado</th></tr>';
    tbodyM.innerHTML = '';
    validas.slice(0,50).forEach(function(fila,i){
        var tr=document.createElement('tr');
        tr.innerHTML='<td class="small">'+(i+1)+'</td>'
            + cols.map(function(c){ return '<td class="small">'+esc(fila[c.id]||'')+'</td>'; }).join('')
            + '<td><span class="badge badge-success small">✓ OK</span></td>';
        tbodyM.appendChild(tr);
    });
    if(validas.length>50){
        var tr=document.createElement('tr');
        tr.innerHTML='<td colspan="'+(cols.length+2)+'" class="text-center text-muted small">... y '+(validas.length-50)+' más</td>';
        tbodyM.appendChild(tr);
    }

    document.getElementById('wcmas-modal-overlay').style.display='';
    document.body.style.overflow='hidden';
}

function cerrarModal() {
    document.getElementById('wcmas-modal-overlay').style.display='none';
    document.body.style.overflow='';
}

/* ── Enviar al servidor ──────────────────────────────────────────────── */
function enviarFilas() {
    cerrarModal();
    var grid    = document.getElementById('wcmas-grid-wrap');
    var toolbar = document.getElementById('wcmas-toolbar');
    var loader  = document.getElementById('wcmas-loader');
    var result  = document.getElementById('wcmas-resultado');
    var bar     = document.getElementById('wcmas-progress-bar');
    var msg     = document.getElementById('wcmas-loader-msg');
    var sub     = document.getElementById('wcmas-loader-sub');

    grid.style.display    = 'none';
    toolbar.style.display = 'none';
    loader.style.display  = '';
    result.style.display  = 'none';
    document.getElementById('wcmas-hint').style.display = 'none';

    var prog = 0;
    var tick = setInterval(function(){
        prog = Math.min(prog+8, 88);
        bar.style.width = prog+'%';
        sub.textContent = 'Procesando '+_filasParaEnviar.length+' envío(s)...';
    }, 180);

    var fd = new FormData();
    fd.append('action','wcmas_procesar_lote');
    fd.append('nonce', WCMAS.nonce);

    var selU = document.getElementById('wcmas-admin-user');
    if(selU && WCMAS.es_admin) fd.append('asignar_a', selU.value);

    _filasParaEnviar.forEach(function(fila,i){
        cols.forEach(function(c){ fd.append('filas['+i+']['+c.id+']', fila[c.id]||''); });
    });

    fetch(WCMAS.ajax_url, {method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(resp){
            clearInterval(tick);
            bar.style.width='100%';
            setTimeout(function(){
                loader.style.display='none';
                mostrarResultado(resp);
            }, 300);
        })
        .catch(function(){
            clearInterval(tick);
            loader.style.display='none';
            grid.style.display='';
            toolbar.style.display='flex';
            alert('Error de conexión. Intenta de nuevo.');
        });
}

/* ── Mostrar resultado ───────────────────────────────────────────────── */
function mostrarResultado(resp) {
    var result = document.getElementById('wcmas-resultado');
    var rbody  = document.getElementById('wcmas-resultado-body');
    var rres   = document.getElementById('wcmas-resultado-resumen');
    result.style.display = '';

    if(!resp.success || !resp.data) {
        rres.innerHTML='<div class="alert alert-danger">Error: '+(resp.data&&resp.data.msg?esc(resp.data.msg):'desconocido')+'</div>';
        return;
    }
    var d = resp.data;
    rres.innerHTML = '<div class="d-flex" style="gap:10px;flex-wrap:wrap;margin-bottom:8px">'
        +'<span class="badge badge-success p-2" style="font-size:14px">✅ '+d.ok+' creado(s)</span>'
        +(d.errores>0?'<span class="badge badge-danger p-2" style="font-size:14px">❌ '+d.errores+' con error</span>':'')
        +'</div>';

    rbody.innerHTML='';
    var firstCol = cols[0];
    d.resultados.forEach(function(r){
        var tr=document.createElement('tr');
        tr.style.background = r.ok?'':'#fff5f5';
        tr.innerHTML='<td class="small">'+r.fila_num+'</td>'
            +'<td>'+(r.ok?'<code class="small">'+esc(r.tracking)+'</code>':'—')+'</td>'
            +'<td class="small">'+esc(r.label||'')+'</td>'
            +'<td>'+(r.ok?'<span class="badge badge-success">✓ Creado</span>':'<span class="badge badge-danger">✗ Error</span>')+'</td>'
            +'<td class="small text-muted">'+(r.ok?'':Object.values(r.errores||{}).join(', '))+'</td>';
        rbody.appendChild(tr);

        // Marcar filas con error en la grilla original
        if(!r.ok && r.fila_num<=tbody.children.length){
            Array.from(tbody.children[r.fila_num-1].querySelectorAll('[data-col]')).forEach(function(inp){
                if(r.errores && r.errores[inp.getAttribute('data-col')]){
                    inp.style.background='#fff5f5';
                    inp.style.borderBottom='2px solid #dc3545';
                    inp.title=r.errores[inp.getAttribute('data-col')];
                }
            });
        }
    });

    limpiarBorrador();
    // Si hubo errores, mostrar la grilla también para que el cliente corrija
    if(d.errores > 0){
        document.getElementById('wcmas-grid-wrap').style.display='';
        document.getElementById('wcmas-toolbar').style.display='flex';
    }
}

function esc(s){ var d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }

/* ── Botones ─────────────────────────────────────────────────────────── */
document.getElementById('wcmas-btn-add5').addEventListener('click', function(){
    for(var i=0;i<5;i++){ numFilas++; tbody.appendChild(crearFila(numFilas)); }
});

document.getElementById('wcmas-btn-borrador').addEventListener('click', function(){ guardarBorrador(true); });

document.getElementById('wcmas-btn-limpiar').addEventListener('click', function(){
    if(!confirm('¿Limpiar toda la tabla? Se perderá el borrador guardado.')) return;
    limpiarBorrador();
    initGrilla(null);
    document.getElementById('wcmas-resultado').style.display='none';
    document.getElementById('wcmas-grid-wrap').style.display='';
    document.getElementById('wcmas-toolbar').style.display='flex';
});

document.getElementById('wcmas-btn-preview').addEventListener('click', function(){ abrirModal(); });
document.getElementById('wcmas-modal-cerrar').addEventListener('click', cerrarModal);
document.getElementById('wcmas-modal-cerrar-x').addEventListener('click', cerrarModal);
document.getElementById('wcmas-modal-overlay').addEventListener('click', function(e){ if(e.target===this) cerrarModal(); });
document.getElementById('wcmas-modal-confirmar').addEventListener('click', enviarFilas);
document.getElementById('wcmas-btn-nueva').addEventListener('click', function(){
    initGrilla(null);
    document.getElementById('wcmas-resultado').style.display='none';
    document.getElementById('wcmas-grid-wrap').style.display='';
    document.getElementById('wcmas-toolbar').style.display='flex';
});

/* ── Borrador: comprobar al cargar ───────────────────────────────────── */
var borrador = cargarBorrador();
if(borrador && borrador.datos && borrador.datos.length){
    document.getElementById('wcmas-draft-aviso').style.display='';
    document.getElementById('wcmas-draft-restaurar').addEventListener('click', function(){
        document.getElementById('wcmas-draft-aviso').style.display='none';
        initGrilla(borrador.datos);
    });
    document.getElementById('wcmas-draft-descartar').addEventListener('click', function(){
        limpiarBorrador();
        document.getElementById('wcmas-draft-aviso').style.display='none';
        initGrilla(null);
    });
}

/* ── Init ────────────────────────────────────────────────────────────── */
initGrilla(null);

})();
</script>

<?php if ($es_admin ?? false): ?>
<!-- Panel de datos del remitente (visible en frontend cuando admin selecciona cliente) -->
<div id="wcmas-remitente-panel" style="display:none;margin-top:10px;padding:10px 14px;background:#f8f9fa;border:1px solid #dee2e6;border-radius:4px;font-size:12px">
    <strong style="font-size:11px;text-transform:uppercase;letter-spacing:.5px;color:#6c757d">
        <i class="fa fa-user mr-1"></i>Datos del remitente (cliente seleccionado)
    </strong>
    <div style="display:flex;flex-wrap:wrap;gap:12px;margin-top:8px">
        <span><span style="color:#888">Marca:</span> <strong id="wcmas-rem-nombre">—</strong></span>
        <span><span style="color:#888">Celular:</span> <strong id="wcmas-rem-telefono">—</strong></span>
        <span><span style="color:#888">Email:</span> <strong id="wcmas-rem-email">—</strong></span>
        <span><span style="color:#888">Dirección:</span> <strong id="wcmas-rem-direccion">—</strong></span>
        <span><span style="color:#888">Dist. Recojo:</span> <strong id="wcmas-rem-distrito">—</strong></span>
        <span id="wcmas-rem-maps-wrap" style="display:none"><span style="color:#888">Maps:</span> <strong id="wcmas-rem-maps">—</strong></span>
    </div>
</div>

<script>
/* ── Select2 + Autocompletado remitente (solo admin) ──────────────────── */
(function wcmasSelect2Init(){
    var $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.select2) {
        // Select2 no está listo aún — reintentar
        setTimeout(wcmasSelect2Init, 250);
        return;
    }

    var $sel = $('#wcmas-admin-user');
    if (!$sel.length) return;

    // Detectar si hay opciones precargadas (lista pequeña) o si se usa AJAX
    var tieneOpciones = $sel.find('option[value!=""]').length > 0;

    if (!tieneOpciones) {
        // AJAX dinámico — buscar clientes al tipear
        $sel.select2({
            placeholder        : '— Buscar cliente por nombre o email —',
            allowClear         : true,
            minimumInputLength : 2,
            language: {
                inputTooShort : function(){ return 'Escribe al menos 2 caracteres para buscar...'; },
                noResults     : function(){ return 'No se encontraron clientes.'; },
                searching     : function(){ return 'Buscando...'; },
                loadingMore   : function(){ return 'Cargando más resultados...'; },
            },
            ajax: {
                url     : WCMAS.ajax_url,
                dataType: 'json',
                delay   : 300,
                data: function(params){
                    return {
                        action : 'wcmas_buscar_clientes',
                        nonce  : WCMAS.nonce,
                        q      : params.term,
                        page   : params.page || 1,
                    };
                },
                processResults: function(data){
                    return {
                        results    : data.results    || [],
                        pagination : data.pagination || { more: false },
                    };
                },
                cache: true,
            },
            width: 'auto',
        });
    } else {
        // Lista precargada pequeña — búsqueda local
        $sel.select2({
            placeholder: '— Seleccionar cliente —',
            allowClear : true,
            width      : 'auto',
        });
    }

    // Al seleccionar un cliente: cargar datos del remitente
    $sel.on('select2:select', function(e){
        var uid = parseInt(e.params.data.id, 10);
        if (!uid) return;

        var $panel = $('#wcmas-remitente-panel');
        $panel.show();
        $panel.find('[id^="wcmas-rem-"]').text('...');

        $.post(WCMAS.ajax_url, {
            action  : 'wcmas_datos_remitente',
            nonce   : WCMAS.nonce,
            user_id : uid,
        }, function(resp){
            if (resp && resp.success && resp.data) {
                var d = resp.data;
                $('#wcmas-rem-nombre')   .text(d.nombre    || '—');
                $('#wcmas-rem-telefono') .text(d.telefono  || '—');
                $('#wcmas-rem-email')    .text(d.email     || '—');
                $('#wcmas-rem-direccion').text(d.direccion || '—');
                $('#wcmas-rem-distrito') .text(d.distrito  || '—');
                if (d.link_maps) {
                    $('#wcmas-rem-maps').html('<a href="'+d.link_maps+'" target="_blank" rel="noopener">Ver mapa ↗</a>');
                    $('#wcmas-rem-maps-wrap').show();
                } else {
                    $('#wcmas-rem-maps-wrap').hide();
                }
            } else {
                $panel.hide();
            }
        }).fail(function(){ $('#wcmas-remitente-panel').hide(); });
    });

    // Al limpiar la selección: ocultar panel
    $sel.on('select2:clear select2:unselect', function(){
        $('#wcmas-remitente-panel').hide();
    });

})();
</script>
<?php endif; ?>
