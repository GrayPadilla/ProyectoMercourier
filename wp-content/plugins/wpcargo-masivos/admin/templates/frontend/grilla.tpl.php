<?php if ( ! defined('ABSPATH') ) exit;
$draft_key = 'wcmas_draft_u' . get_current_user_id();
$es_admin  = $es_admin ?? false;
$historial = $historial ?? [];
$current_user_id    = get_current_user_id();
$current_user_label = wp_get_current_user()->display_name ?: wp_get_current_user()->user_login;
?>

<!-- ═══ JS config ════════════════════════════════════════════════════ -->
<script>
var WCMAS = {
    ajax_url     : '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
    nonce        : '<?php echo esc_js($nonce); ?>',
    columnas     : <?php echo WCMAS_Columnas::para_js(true); ?>,
    filas_init   : <?php echo intval($filas_init); ?>,
    draft_key    : '<?php echo esc_js($draft_key); ?>',
    es_admin     : <?php echo $es_admin ? 'true' : 'false'; ?>,
    current_uid  : <?php echo intval($current_user_id); ?>,
    current_label: '<?php echo esc_js($current_user_label); ?>',
    distritos_tarifa: <?php echo wp_json_encode(array_combine(
        WCMAS_Columnas::get_distritos(),
        array_map([WCMAS_Columnas::class, 'get_tarifa_distrito'], WCMAS_Columnas::get_distritos())
    )); ?>,
    bloqueos: { normal: null, express: null }
};

// Cargar bloqueos del servidor al iniciar
(function() {
    var tipos = ['normal', 'express'];
    tipos.forEach(function(tipo) {
        jQuery.post(WCMAS.ajax_url, {
            action: 'merc_bloqueo_info',
            tipo: tipo,
            nonce: WCMAS.nonce
        }, function(res) {
            if (res && res.success && res.data) {
                WCMAS.bloqueos[tipo] = res.data;
            }
        }).fail(function() {
            // En caso de error, usar valores por defecto sin bloqueos
            WCMAS.bloqueos[tipo] = { bloquear_hoy: false, fechas_bloqueadas: [], domingos_desbloqueados: [] };
        });
    });
})();
</script>

<!-- ═══ ESTILOS ════════════════════════════════════════════════════ -->
<style>
/* SELECT: apariencia nativa del navegador => se ve como dropdown real */
.wcmas-sel,
#wcmas-grid .wcmas-sel {
    display:block !important;width:100% !important;height:32px !important;min-height:32px !important;
    padding:4px 28px 4px 8px !important;outline:none !important;box-sizing:border-box !important;
    color:#1d2327 !important;cursor:pointer !important;line-height:1.2 !important;
    background-color:#fff !important;background-repeat:no-repeat !important;
    background-position:right 8px center !important;background-size:14px 14px !important;
    border:1px solid #cfd7df !important;border-radius:0 !important;
    -webkit-appearance:none !important;-moz-appearance:none !important;appearance:none !important;
    background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3E%3Cpath fill='%23666' d='M4.2 6.4a.75.75 0 0 1 1.06.04L8 9.34l2.74-2.9a.75.75 0 1 1 1.1 1.02l-3.3 3.5a.75.75 0 0 1-1.08 0l-3.3-3.5a.75.75 0 0 1 .04-1.06z'/%3E%3C/svg%3E") !important;
    position:relative !important;z-index:2 !important;
}
.wcmas-sel.wcmas-empty { color:#6c757d !important; }
.wcmas-sel:focus { background-color:#fffde7 !important; }
.wcmas-inp,
#wcmas-grid .wcmas-inp {
    display:block !important;width:100% !important;height:32px !important;min-height:32px !important;
    padding:4px 8px !important;border:1px solid #cfd7df !important;outline:none !important;
    font-size:13px;font-family:inherit;box-sizing:border-box;background:#fff !important;color:#1d2327 !important;
}
.wcmas-inp[readonly] { color:#555;cursor:default;background:#f5f5f5; }
.wcmas-inp.ok,.wcmas-sel.ok  { background-color:#f0fff4!important;border-bottom:2px solid #28a745; }
.wcmas-inp.err,.wcmas-sel.err{ background-color:#fff5f5!important;border-bottom:2px solid #dc3545; }

/* celda deshabilitada (condicional) */
.wcmas-col-disabled { opacity:.25;pointer-events:none;transition:opacity .15s; }
/* monto_producto oculto si no cobrar */
.wcmas-solo-cobrar { transition:opacity .15s; }
.wcmas-solo-cobrar.hidden-col { opacity:.25;pointer-events:none; }
</style>

<!-- ═══ ENCABEZADO ════════════════════════════════════════════════════ -->
<div class="d-flex align-items-center mb-3 border-bottom pb-3">
    <div class="mr-auto">
        <h5 class="mb-0"><i class="fa fa-table mr-2 text-primary"></i>Carga Masiva de Envíos</h5>
        <small class="text-muted">Completa la tabla o <strong>copia y pega desde Excel / Google Sheets</strong> con <kbd>Ctrl+V</kbd>.</small>
    </div>
    <div id="wcmas-toolbar" class="d-flex align-items-center" style="gap:6px">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="wcmas-btn-add5">
            <i class="fa fa-plus mr-1"></i>+5 filas
        </button>
        <button type="button" class="btn btn-outline-warning btn-sm" id="wcmas-btn-borrador" title="Guardar borrador">
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

<!-- Hint -->
<div class="alert alert-info small mb-2" id="wcmas-hint">
    <i class="fa fa-lightbulb-o mr-1"></i>
    <strong>Copiar y pegar desde Excel:</strong> Selecciona tu rango (sin encabezados), copia con <kbd>Ctrl+C</kbd>, haz clic en una celda y pega con <kbd>Ctrl+V</kbd>.
    <button type="button" onclick="this.closest('.alert').style.display='none'" class="close ml-2" style="font-size:14px">&times;</button>
</div>

<!-- ═══ GRILLA ════════════════════════════════════════════════════════ -->
<div id="wcmas-grid-wrap" style="overflow-x:auto;margin-bottom:8px;border:1px solid #dee2e6;border-radius:4px">
<table id="wcmas-grid" style="border-collapse:collapse;width:100%;table-layout:fixed;min-width:600px">
    <colgroup><col style="width:36px"></colgroup>
    <thead id="wcmas-thead">
        <tr style="background:#f8f9fa;position:sticky;top:0;z-index:2">
            <th style="padding:7px 4px;text-align:center;font-size:11px;color:#888;border:1px solid #dee2e6;font-weight:600">#</th>
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
        <thead class="thead-light"><tr><th>Fecha</th><?php if($es_admin): ?><th>Asignado a</th><?php endif; ?><th>Total</th><th style="width:56px;text-align:center;font-size:12px">OK</th><th style="width:56px;text-align:center;font-size:12px">Error</th></tr></thead>
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
<div style="background:#fff;border-radius:8px;max-width:960px;margin:0 auto;overflow:hidden;box-shadow:0 20px 60px rgba(0,0,0,.3)">
    <div style="background:#2271b1;color:#fff;padding:16px 20px;display:flex;align-items:center;gap:12px">
        <i class="fa fa-eye fa-lg"></i>
        <div style="flex:1">
            <h5 class="mb-0" style="font-size:16px">Vista previa — Confirmar carga</h5>
            <small id="wcmas-modal-subtitle" style="opacity:.85"></small>
        </div>
        <button type="button" id="wcmas-modal-cerrar-x" style="background:none;border:none;color:#fff;font-size:22px;cursor:pointer;padding:0;line-height:1">&times;</button>
    </div>
    <div style="padding:20px">
        <div id="wcmas-modal-resumen" style="display:flex;gap:12px;margin-bottom:16px;flex-wrap:wrap"></div>
        <div id="wcmas-modal-errores-wrap" style="display:none;margin-bottom:14px">
            <div class="alert alert-warning small mb-2">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                <strong>Filas con errores:</strong> Se crearán solo las filas válidas.
            </div>
        </div>
        <div style="overflow-x:auto;max-height:360px;overflow-y:auto;border:1px solid #dee2e6;border-radius:4px">
        <table class="table table-sm table-bordered mb-0" id="wcmas-modal-tabla">
            <thead class="thead-light" id="wcmas-modal-thead"></thead>
            <tbody id="wcmas-modal-tbody"></tbody>
        </table>
        </div>
    </div>
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

/*
 * cols = columnas visibles (sin shipment_title)
 * WCMAS.columnas = todas (para enviar al servidor incluyendo shipment_title)
 */
var cols     = WCMAS.columnas.filter(function(c){ return c.id !== 'shipment_title'; });
var draftKey = WCMAS.draft_key;
var tbody    = document.getElementById('wcmas-tbody');
var thead    = document.getElementById('wcmas-thead').querySelector('tr');
var numFilas = WCMAS.filas_init;
var ANCHO    = { sm:'90px', md:'155px', lg:'220px' };

/* ── IDs especiales ─────────────────────────────────────────────── */
var ID_TIPO_PROGRAMADO = 'tipo_programado';
var ID_DISTRITO        = 'distrito_envio';
var ID_COBRAR          = 'listo_cobrar_producto';
var ID_MONTO_PRODUCTO  = 'listo_monto_producto';
var ID_MONTO_TOTAL     = 'listo_monto_total';
var ID_MONTO_ENVIO     = 'monto_envio';
var ID_SHIPPER         = 'registered_shipper';
var ID_MODO_PAGO       = 'modo_de_pago';
var ID_DRIVER          = 'wpcargo_driver';
var ID_CONTAINER       = 'shipment_container';

/* ── Calcular próxima fecha disponible ──────────────────────────── */
function calcularProximaFechaGrilla(bData) {
    var bloquear_hoy         = bData ? !!bData.bloquear_hoy : false;
    var fechas_bloqueadas    = (bData && Array.isArray(bData.fechas_bloqueadas))    ? bData.fechas_bloqueadas    : [];
    var domingos_desbloqueados = (bData && Array.isArray(bData.domingos_desbloqueados)) ? bData.domingos_desbloqueados : [];

    var date = new Date();
    date.setHours(0, 0, 0, 0);
    if (bloquear_hoy) date.setDate(date.getDate() + 1);

    for (var i = 0; i < 60; i++) {
        var y  = date.getFullYear();
        var m  = ('0' + (date.getMonth() + 1)).slice(-2);
        var d  = ('0' + date.getDate()).slice(-2);
        var ds = y + '-' + m + '-' + d;

        // Domingo bloqueado?
        if (date.getDay() === 0 && domingos_desbloqueados.indexOf(ds) === -1) {
            date.setDate(date.getDate() + 1); continue;
        }
        // Fecha especial bloqueada?
        if (fechas_bloqueadas.indexOf(ds) !== -1) {
            date.setDate(date.getDate() + 1); continue;
        }
        return ds;
    }
    return '';
}

/*
 * REGLAS VISIBILIDAD:
 *   Domicilio    -> direccion SI | distrito SI | telefono SI
 *   Mercado Flex -> direccion NO | distrito SI | telefono SI
 *   (sin valor)  -> direccion NO | distrito NO | telefono NO
 */

/* ── Cabecera dinámica ──────────────────────────────────────────── */
function buildThead() {
    thead.querySelectorAll('th.wcmas-th').forEach(function(th){ th.remove(); });
    var lastTh = thead.querySelector('th:last-child');

    var table    = document.getElementById('wcmas-grid');
    var colgroup = table.querySelector('colgroup');
    colgroup.innerHTML = '<col style="width:36px">';

    cols.forEach(function(col) {
        var colEl = document.createElement('col');
        colEl.style.width = ANCHO[col.ancho] || '155px';
        colgroup.appendChild(colEl);

        var th = document.createElement('th');
        th.className = 'wcmas-th';
        th.style.cssText = 'padding:7px 8px;font-size:12px;font-weight:700;color:#1d2327;border:1px solid #dee2e6;white-space:nowrap;overflow:hidden;text-overflow:ellipsis';
        th.title = col.label + (col.meta_key ? ' ('+col.meta_key+')' : '');
        th.innerHTML = esc(col.label)
            + (col.obligatorio ? '<span style="color:#dc3545;margin-left:2px">*</span>' : '')
            + (col.default_val ? '<span title="Default: '+esc(col.default_val)+'" style="color:#6c757d;margin-left:3px;cursor:help">ⓘ</span>' : '');
        thead.insertBefore(th, lastTh);
    });

    var colFin = document.createElement('col');
    colFin.style.width = '28px';
    colgroup.appendChild(colFin);
}

/* ── Crear input/select ─────────────────────────────────────────── */
function crearInput(col) {
    var el;
    function syncSelectEmptyState(sel) {
        if (!sel) return;
        if ((sel.value || '').trim() === '') sel.classList.add('wcmas-empty');
        else sel.classList.remove('wcmas-empty');
    }

    /* SELECT fijo o desde BD */
    if (col.tipo === 'select' || col.tipo === 'select_db') {
        el = document.createElement('select');
        el.className = 'wcmas-sel';
        var o0 = document.createElement('option');
        o0.value = ''; o0.textContent = 'Seleccionar...';
        el.appendChild(o0);
        (Array.isArray(col.opciones) ? col.opciones : []).forEach(function(op) {
            var o = document.createElement('option');
            o.value = op; o.textContent = op;
            el.appendChild(o);
        });
        if (col.default_val) el.value = col.default_val;
        syncSelectEmptyState(el);
        el.addEventListener('change', function(){ syncSelectEmptyState(el); });

    /* SHIPPER */
    } else if (col.tipo === 'shipper') {
        if (WCMAS.es_admin) {
            el = document.createElement('select');
            el.className = 'wcmas-sel';
            var usuarios = <?php echo wp_json_encode(array_values($usuarios ?? [])); ?>;
            var oa = document.createElement('option');
            oa.value=''; oa.textContent='Seleccionar usuario...'; el.appendChild(oa);
            usuarios.forEach(function(u) {
                var o = document.createElement('option');
                o.value = u.id; o.textContent = u.label; el.appendChild(o);
            });
            syncSelectEmptyState(el);
            el.addEventListener('change', function(){ syncSelectEmptyState(el); });
        } else {
            /* Cliente: muestra nombre visualmente, envía su ID */
            el = document.createElement('input');
            el.type = 'text'; el.className = 'wcmas-inp'; el.readOnly = true;
            el.value = WCMAS.current_uid;
            el.setAttribute('data-display', WCMAS.current_label);
            el.style.display = 'none';
            var wrap = document.createElement('div');
            wrap.style.cssText = 'padding:4px 6px;font-size:13px;color:#555;background:#f5f5f5;height:100%;display:flex;align-items:center';
            wrap.textContent = WCMAS.current_label;
            el.setAttribute('data-col', col.id);
            wrap.appendChild(el);
            return wrap;
        }

    /* NUMBER READONLY (calculado) */
    } else if (col.tipo === 'number_readonly') {
        el = document.createElement('input');
        el.type = 'text'; el.className = 'wcmas-inp'; el.readOnly = true;
        el.placeholder = col.placeholder || '0.00';
        el.style.cssText = 'background:#f5f5f5;color:#1a6e3c;font-weight:700';

    /* TEXT / NUMBER / PHONE / EMAIL */
    } else {
        el = document.createElement('input');
        el.type = 'text'; el.className = 'wcmas-inp';
        el.placeholder = col.placeholder || '';
        el.autocomplete = 'off'; el.spellcheck = false;
        if (col.default_val) el.value = col.default_val;
    }

    el.setAttribute('data-col', col.id);
    return el;
}

/* ── Crear fila ─────────────────────────────────────────────────── */
function crearFila(n) {
    var tr = document.createElement('tr');
    tr.setAttribute('data-n', n);
    tr.style.borderBottom = '1px solid #f0f0f0';

    /* # */
    var tdN = document.createElement('td');
    tdN.style.cssText = 'text-align:center;color:#bbb;font-size:11px;padding:0;border:1px solid #dee2e6;width:36px;user-select:none;background:#fafafa';
    tdN.textContent = n;
    tr.appendChild(tdN);

    cols.forEach(function(col) {
        var td = document.createElement('td');
        td.style.cssText = 'padding:0;border:1px solid #dee2e6;position:relative';
        td.setAttribute('data-td', col.id);

        var inp    = crearInput(col);
        var inputEl = (inp.tagName === 'DIV') ? inp.querySelector('[data-col]') : inp;

        /* ── Listeners ── */
        if (col.id === ID_TIPO_PROGRAMADO) {
            inputEl.addEventListener('change', function() { onTipoProgramadoChange(tr); actualizarStats(); });
        }
        if (col.id === ID_DISTRITO) {
            inputEl.addEventListener('change', function() { onDistritoChange(tr); actualizarStats(); });
        }
        if (col.id === ID_COBRAR) {
            inputEl.addEventListener('change', function() { onCobrarChange(tr); actualizarStats(); });
        }
        if (col.id === ID_MODO_PAGO) {
            inputEl.addEventListener('change', function() { recalcularTotal(tr); actualizarStats(); });
        }
        if (col.id === ID_MONTO_ENVIO || col.id === ID_MONTO_PRODUCTO) {
            inputEl.addEventListener('input', function() { recalcularTotal(tr); });
        }
        if (col.id === 'tipo_envio') {
            inputEl.addEventListener('change', function() { onTipoEnvioChange(tr); actualizarStats(); });
        }
        if (col.id === 'calendarenvio' || col.id === 'wpcargo_pickup_date_picker' || col.id === 'fecha_envio') {
            inputEl.addEventListener('change', function() { validarFechaFila(tr, inputEl, col); actualizarStats(); });
            inputEl.addEventListener('blur', function() { validarFechaFila(tr, inputEl, col); actualizarStats(); });
            // Add datepicker hint placeholder
            if(!inputEl.placeholder) inputEl.placeholder = 'YYYY-MM-DD';
        }
        if (!inputEl.readOnly) {
            inputEl.addEventListener('blur',    function() { validarInput(inputEl, col); actualizarStats(); });
            inputEl.addEventListener('focus',   function() { if(inputEl.select) inputEl.select(); });
            inputEl.addEventListener('input',   function() { guardarBorradorDebounce(); });
            inputEl.addEventListener('paste',   function(e) { manejarPaste(e, tr, col); });
            inputEl.addEventListener('keydown', function(e) { manejarTeclas(e, inputEl, tr, col); });
        }

        td.appendChild(inp);
        tr.appendChild(td);
    });

    /* Estado */
    var tdSt = document.createElement('td');
    tdSt.style.cssText = 'text-align:center;padding:2px;border:1px solid #dee2e6;width:28px;font-size:13px';
    tdSt.className = 'wcmas-est';
    tr.appendChild(tdSt);

    /* Estado inicial */
    setTimeout(function(){ onTipoProgramadoChange(tr); onCobrarChange(tr); }, 0);
    return tr;
}

/* ── Habilitar / deshabilitar celda ─────────────────────────────── */
function setColDisabled(tr, colId, disabled) {
    var td = tr.querySelector('[data-td="'+colId+'"]');
    if (!td) return;
    if (disabled) {
        td.classList.add('wcmas-col-disabled');
        /* limpiar valor al deshabilitar */
        var inp = tr.querySelector('[data-col="'+colId+'"]');
        if (inp && !inp.readOnly) { inp.value=''; inp.className='wcmas-inp'; }
    } else {
        td.classList.remove('wcmas-col-disabled');
    }
}

/* ── Lógica tipo_programado ─────────────────────────────────────── */
function onTipoEnvioChange(tr) {
    var selTipo = getInputEl(tr, 'tipo_envio');
    var val = selTipo ? (selTipo.value || '').toLowerCase().trim() : '';
    var dateEl = getInputEl(tr, 'calendarenvio') || getInputEl(tr, 'wpcargo_pickup_date_picker') || getInputEl(tr, 'fecha_envio');
    if (dateEl) {
        if (val === '') {
            dateEl.readOnly = true;
            dateEl.value = '';
            dateEl.style.backgroundColor = '#f5f5f5';
            dateEl.title = 'Seleccione primero el Tipo de Envío';
            dateEl.classList.remove('ok', 'err');
        } else {
            dateEl.readOnly = false;
            dateEl.style.backgroundColor = '';
            dateEl.title = '';

            // --- AUTOCOMPLETAR FECHA ---
            // Masivos usa su propio flujo de procesamiento: la fecha de inicio
            // es SIEMPRE hoy (bloquear_hoy no aplica a masivos, solo al formulario
            // de envío individual). Sí respetamos fechas_bloqueadas y domingos.
            var tipo = (selTipo.value || '').toLowerCase().trim();
            var bDataRaw = WCMAS.bloqueos[tipo] || WCMAS.bloqueos.normal || {};
            var bData = {
                bloquear_hoy: false,  // Masivos siempre puede usar hoy
                fechas_bloqueadas: bDataRaw.fechas_bloqueadas || [],
                domingos_desbloqueados: bDataRaw.domingos_desbloqueados || []
            };
            if (!dateEl.value) {
                dateEl.value = calcularProximaFechaGrilla(bData);
            }
            // ---------------------------

            validarFechaFila(tr, dateEl, {label: 'Fecha de Envío', obligatorio: true});
        }
    }
}


function validarFechaFila(tr, inp, col) {
    // Sin bloqueos para masivos
    if (inp) {
        inp.classList.remove('err');
        inp.classList.add('ok');
        inp.title = '';
    }
    return '';
}

/**
 * Calcula la próxima fecha disponible para la grilla
 */
function calcularProximaFechaGrilla(bData) {
    if (!bData) return '';
    var date = new Date();
    date.setHours(0, 0, 0, 0);
    if (bData.bloquear_hoy) {
        date.setDate(date.getDate() + 1);
    }
    var domingos_desbloqueados = bData.domingos_desbloqueados || [];
    var fechas_bloqueadas = bData.fechas_bloqueadas || [];

    for (var i = 0; i < 60; i++) {
        var year  = date.getFullYear();
        var month = ('0' + (date.getMonth() + 1)).slice(-2);
        var day   = ('0' + date.getDate()).slice(-2);
        var ds    = year + '-' + month + '-' + day;

        if (date.getDay() === 0) {
            if (domingos_desbloqueados.indexOf(ds) === -1) {
                date.setDate(date.getDate() + 1);
                continue;
            }
        }
        if (fechas_bloqueadas.indexOf(ds) !== -1) {
            date.setDate(date.getDate() + 1);
            continue;
        }
        return ds;
    }
    return '';
}

function onTipoProgramadoChange(tr) {
    var sel = getInputEl(tr, ID_TIPO_PROGRAMADO);
    var val = sel ? (sel.value || '').toLowerCase().trim() : '';

    var esDomicilio   = (val === 'domicilio');
    var esMercadoFlex = (val === 'mercado flex');

    /* dirección: Domicilio y Mercado Flex */
    setColDisabled(tr, 'dest_direccion', !(esDomicilio || esMercadoFlex));

    /* distrito: Domicilio y Mercado Flex */
    setColDisabled(tr, 'distrito_envio', !(esDomicilio || esMercadoFlex));

    /* teléfono: solo Domicilio */
    setColDisabled(tr, 'dest_telefono', !esDomicilio);

    /* Si no aplica, limpiar el monto de envío */
    if (!(esDomicilio || esMercadoFlex)) {
        var montoInp = getInputEl(tr, ID_MONTO_ENVIO);
        if (montoInp && !montoInp.readOnly) { montoInp.value=''; montoInp.className='wcmas-inp'; }
    }

    autoAsignarPorDistrito(tr);
    recalcularTotal(tr);
    actualizarEstFila(tr);
}

/* ── Al elegir distrito: auto-rellena monto envío ───────────────── */
function onDistritoChange(tr) {
    var distrEl = getInputEl(tr, ID_DISTRITO);
    if (!distrEl) return;

    autoAsignarPorDistrito(tr);
    if (!distrEl.value) { recalcularTotal(tr); return; }

    var distrito = distrEl.value.trim().toUpperCase();
    var tarifa   = WCMAS.distritos_tarifa[distrito];
    if (tarifa === undefined || tarifa === null) { recalcularTotal(tr); return; }

    var montoInp = getInputEl(tr, ID_MONTO_ENVIO);
    if (montoInp && !montoInp.readOnly) {
        montoInp.value = parseFloat(tarifa).toFixed(2);
        montoInp.classList.remove('err');
        montoInp.classList.add('ok');
    }

    recalcularTotal(tr);
}

function tieneColumna(colId) {
    return cols.some(function(c){ return c.id === colId; });
}

function limpiarAsignacionFila(tr) {
    tr.dataset.autoContainer = '';
    tr.dataset.autoDriver = '';

    var contEl = getInputEl(tr, ID_CONTAINER);
    var drvEl  = getInputEl(tr, ID_DRIVER);

    if (contEl && !contEl.readOnly) {
        contEl.value = '';
        if (contEl.tagName === 'SELECT') {
            contEl.classList.remove('ok');
            contEl.classList.add('wcmas-empty');
        }
    }
    if (drvEl && !drvEl.readOnly) {
        drvEl.value = '';
        if (drvEl.tagName === 'SELECT') {
            drvEl.classList.remove('ok');
            drvEl.classList.add('wcmas-empty');
        }
    }
}

function esTipoAsignable(tr) {
    var tipoEl = getInputEl(tr, ID_TIPO_PROGRAMADO);
    var tipo   = tipoEl ? (tipoEl.value || '').toLowerCase().trim() : '';
    return tipo === 'domicilio' || tipo === 'mercado flex';
}

function valorDistrito(tr) {
    var distEl = getInputEl(tr, ID_DISTRITO);
    return distEl ? (distEl.value || '').trim() : '';
}

function normalizarTexto(v) {
    return String(v || '')
        .replace(/\s*[\u2013\-].*$/, '')
        .trim()
        .toUpperCase();
}

function setSelectValueConEstado(el, value) {
    if (!el || el.readOnly) return false;
    var valor = String(value || '');
    if (el.tagName === 'SELECT') {
        var existe = Array.from(el.options || []).some(function(opt){ return String(opt.value) === valor; });
        if (existe) {
            el.value = valor;
            el.classList.remove('wcmas-empty', 'err');
            if (valor !== '') el.classList.add('ok');
            return true;
        }
        return false;
    }
    el.value = valor;
    return true;
}

function crearUrlEncoded(data) {
    var body = new URLSearchParams();
    Object.keys(data).forEach(function(k){ body.append(k, data[k]); });
    return body;
}

function fetchAjaxAction(action) {
    return fetch(WCMAS.ajax_url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
        body: crearUrlEncoded({ action: action })
    })
    .then(function(r){ return r.json(); })
    .catch(function(){ return { success:false, data:{} }; });
}

function construirConductorMapDesdeFila(tr, contNames) {
    var map = {};
    var drvEl = getInputEl(tr, ID_DRIVER);
    if (!drvEl || drvEl.tagName !== 'SELECT') return map;

    var drvOpts = Array.from(drvEl.options || []);
    Object.keys(contNames || {}).forEach(function(cid) {
        var contName = normalizarTexto(contNames[cid]);
        if (!contName) return;
        for (var i = 0; i < drvOpts.length; i++) {
            var opt = drvOpts[i];
            if (!opt || !opt.value) continue;
            if (normalizarTexto(opt.textContent || opt.text || '') === contName) {
                map[String(cid)] = String(opt.value);
                break;
            }
        }
    });
    return map;
}

function _aplicarAsignacion(tr, distrito, rutas, condMap) {
    var containerId = (rutas && rutas[distrito] && rutas[distrito][0] != null)
        ? String(rutas[distrito][0])
        : '';

    if (!containerId) {
        limpiarAsignacionFila(tr);
        return;
    }

    tr.dataset.autoContainer = containerId;
    tr.dataset.autoDriver = '';

    var contEl = getInputEl(tr, ID_CONTAINER);
    var drvEl  = getInputEl(tr, ID_DRIVER);

    if (contEl && !setSelectValueConEstado(contEl, containerId)) {
        limpiarAsignacionFila(tr);
        return;
    }

    var driverId = (condMap && condMap[containerId]) ? String(condMap[containerId]) : '';

    if (!driverId && contEl && drvEl && contEl.tagName === 'SELECT' && drvEl.tagName === 'SELECT') {
        var contOpt = Array.from(contEl.options || []).find(function(opt){ return String(opt.value) === String(containerId); }) || null;
        var contNombre = normalizarTexto(contOpt ? (contOpt.textContent || contOpt.text || '') : '');
        if (contNombre) {
            Array.from(drvEl.options || []).some(function(opt) {
                if (!opt || !opt.value) return false;
                if (normalizarTexto(opt.textContent || opt.text || '') === contNombre) {
                    driverId = String(opt.value);
                    return true;
                }
                return false;
            });
        }
    }

    if (driverId) {
        tr.dataset.autoDriver = String(driverId);
        if (drvEl) setSelectValueConEstado(drvEl, driverId);
    } else if (drvEl) {
        drvEl.value = '';
        if (drvEl.tagName === 'SELECT') {
            drvEl.classList.remove('ok');
            drvEl.classList.add('wcmas-empty');
        }
    }
}

function autoAsignarPorDistrito(tr) {
    var distrito = valorDistrito(tr);
    if (!esTipoAsignable(tr) || !distrito) {
        limpiarAsignacionFila(tr);
        return;
    }

    var rutas = window.listoRutasConfig || {};
    var condMap = window.listoConductorMap || {};

    if (Object.keys(rutas).length) {
        _aplicarAsignacion(tr, distrito, rutas, condMap);
        return;
    }

    Promise.all([
        fetchAjaxAction('listo_get_all_rutas'),
        fetchAjaxAction('listo_get_containers')
    ]).then(function(results){
        var r1 = results[0] || {};
        var r2 = results[1] || {};

        window.listoRutasConfig = r1.success ? (r1.data || {}) : {};

        var contNames = r2.success ? (r2.data || {}) : {};
        var map = construirConductorMapDesdeFila(tr, contNames);
        window.listoConductorMap = map;

        _aplicarAsignacion(tr, distrito, window.listoRutasConfig, window.listoConductorMap);
    }).catch(function(){
        /* Sin rutas disponibles no se autoasigna; no bloquea el flujo principal. */
    });
}

/* ── Lógica cobrar producto ─────────────────────────────────────── */
function onCobrarChange(tr) {
    var sel    = getInputEl(tr, ID_COBRAR);
    var cobrar = sel ? (sel.value || '').toLowerCase() : 'no';
    var td     = tr.querySelector('[data-td="'+ID_MONTO_PRODUCTO+'"]');
    if (td) {
        if (cobrar === 'si') {
            td.classList.remove('wcmas-solo-cobrar','hidden-col');
        } else {
            td.classList.add('wcmas-solo-cobrar','hidden-col');
            var inp = getInputEl(tr, ID_MONTO_PRODUCTO);
            if (inp) { inp.value=''; inp.className='wcmas-inp'; }
        }
    }
    recalcularTotal(tr);
}

/* ── Recalcular monto total ─────────────────────────────────────── */
function recalcularTotal(tr) {
    var totalEl = getInputEl(tr, ID_MONTO_TOTAL);
    if (!totalEl) return;

    /* Si modo_de_pago = "NO COBRAR" → total siempre 0 */
    var modoEl = getInputEl(tr, ID_MODO_PAGO);
    var modo   = modoEl ? (modoEl.value || '').trim().toUpperCase() : '';
    if (modo === 'NO COBRAR') {
        totalEl.value = '0.00';
        return;
    }

    var cobrarEl = getInputEl(tr, ID_COBRAR);
    var cobrar   = cobrarEl ? (cobrarEl.value||'').toLowerCase() : 'no';
    var envioEl  = getInputEl(tr, ID_MONTO_ENVIO);
    var prodEl   = getInputEl(tr, ID_MONTO_PRODUCTO);
    var envio    = parseFloat((envioEl ? envioEl.value : '0').replace(',','.')) || 0;
    var prod     = (cobrar === 'si') ? (parseFloat((prodEl ? prodEl.value : '0').replace(',','.')) || 0) : 0;
    totalEl.value = (envio + prod).toFixed(2);
}

/* ── Obtener input de columna en fila ───────────────────────────── */
function getInputEl(tr, colId) {
    return tr.querySelector('[data-col="'+colId+'"]');
}

/* ── Inicializar grilla ─────────────────────────────────────────── */
function initGrilla(datos) {
    tbody.innerHTML = '';
    buildThead();
    numFilas = Math.max(WCMAS.filas_init, datos ? datos.length + 5 : WCMAS.filas_init);
    for (var i = 1; i <= numFilas; i++) tbody.appendChild(crearFila(i));
    if (datos) rellenarDatos(datos);
    actualizarStats();
}

function rellenarDatos(datos) {
    var filas = tbody.querySelectorAll('tr');
    datos.forEach(function(fila, ri) {
        if (ri >= filas.length) return;
        var tr = filas[ri];
        cols.forEach(function(col) {
            var inp = getInputEl(tr, col.id);
            if (inp && fila[col.id] !== undefined) {
                inp.value = fila[col.id];
                if (!inp.readOnly) validarInput(inp, col);
            }
        });
        onTipoEnvioChange(tr);
        onTipoProgramadoChange(tr);
        onCobrarChange(tr);
        recalcularTotal(tr);
        actualizarEstFila(tr);
    });
}

/* ── Pegado desde Excel ─────────────────────────────────────────── */
function manejarPaste(e, trOrigen, colInicio) {
    var texto = (e.clipboardData || window.clipboardData).getData('text/plain');
    if (!texto) return;
    if (texto.indexOf('\t') === -1 && texto.indexOf('\n') === -1) return;
    e.preventDefault();

    var lineas  = texto.replace(/\r\n/g,'\n').replace(/\r/g,'\n').split('\n').filter(function(l){ return l !== ''; });
    var colIdx  = cols.findIndex(function(c){ return c.id === colInicio.id; });
    var filaIdx = Array.from(tbody.children).indexOf(trOrigen);
    var needed  = filaIdx + lineas.length;

    while (tbody.children.length < needed) { numFilas++; tbody.appendChild(crearFila(numFilas)); }

        lineas.forEach(function(linea, ri) {
        var valores  = linea.split('\t');
        var trActual = tbody.children[filaIdx + ri];
        valores.forEach(function(val, ci) {
            var ci2 = colIdx + ci;
            if (ci2 >= cols.length) return;
            var inp = getInputEl(trActual, cols[ci2].id);
            if (inp && !inp.readOnly) { inp.value = val.trim(); validarInput(inp, cols[ci2]); }
        });
        onTipoEnvioChange(trActual);
        onTipoProgramadoChange(trActual);
        onCobrarChange(trActual);
        recalcularTotal(trActual);
        actualizarEstFila(trActual);
    });
    actualizarStats();
    guardarBorradorDebounce();
    renumerarFilas();
}

/* ── Teclado tipo Excel ─────────────────────────────────────────── */
function manejarTeclas(e, inp, tr, col) {
    if (e.key === 'Tab') {
        e.preventDefault();
        var ci = cols.findIndex(function(c){ return c.id === col.id; });
        var sig;
        if (!e.shiftKey) {
            sig = ci < cols.length-1 ? getInputEl(tr, cols[ci+1].id) : null;
            if (!sig && tr.nextElementSibling) sig = getInputEl(tr.nextElementSibling, cols[0].id);
        } else {
            sig = ci > 0 ? getInputEl(tr, cols[ci-1].id) : null;
            if (!sig && tr.previousElementSibling) sig = getInputEl(tr.previousElementSibling, cols[cols.length-1].id);
        }
        if (sig && !sig.readOnly) sig.focus();
    }
    if (e.key === 'Enter') {
        e.preventDefault();
        var trSig = tr.nextElementSibling;
        if (!trSig) { numFilas++; trSig = crearFila(numFilas); tbody.appendChild(trSig); }
        var ci2 = cols.findIndex(function(c){ return c.id === col.id; });
        var inp2 = getInputEl(trSig, cols[ci2].id);
        if (inp2 && !inp2.readOnly) inp2.focus();
    }
}

/* ── Validación ─────────────────────────────────────────────────── */
function validarInput(inp, col) {
    if (!inp || inp.readOnly) return '';
    var val = (inp.value || '').trim();
    var err = '';

    var tr = inp.closest('tr');
    var obligatorio = col.obligatorio;

    if (tr) {
        var tipoProg      = (getInputEl(tr, ID_TIPO_PROGRAMADO)||{value:''}).value.toLowerCase().trim();
        var esDomicilio   = (tipoProg === 'domicilio');
        var esMercadoFlex = (tipoProg === 'mercado flex');

        /* Dirección y distrito: obligatorios en Domicilio y Mercado Flex */
        if (col.id === 'dest_direccion' || col.id === 'distrito_envio') {
            obligatorio = esDomicilio || esMercadoFlex;
        }
        /* Teléfono: obligatorio solo en Domicilio */
        if (col.id === 'dest_telefono') obligatorio = esDomicilio;
        /* Monto producto: obligatorio solo si cobrar = si */
        if (col.id === ID_MONTO_PRODUCTO) {
            var cobrar = (getInputEl(tr, ID_COBRAR)||{value:'no'}).value.toLowerCase();
            obligatorio = (cobrar === 'si');
        }
    }

    if (obligatorio && val === '' && !col.default_val) {
        err = col.label + ' es obligatorio.';
    } else if (val !== '') {
        switch(col.tipo) {
            case 'number': if(isNaN(parseFloat(val.replace(',','.')))) err=col.label+': debe ser un número.'; break;
            case 'phone':  if(!/^\d{7,15}$/.test(val.replace(/[\s\-\+\(\)]/g,''))) err=col.label+': teléfono inválido.'; break;
            case 'email':  if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(val)) err=col.label+': email inválido.'; break;
        }
    }

    if (err) {
        inp.classList.remove('ok'); inp.classList.add('err'); inp.title = err;
    } else if (val !== '' || col.tipo === 'select' || col.tipo === 'select_db') {
        inp.classList.remove('err');
        if (val !== '') inp.classList.add('ok'); else inp.classList.remove('ok');
        inp.title = '';
    } else {
        inp.classList.remove('ok','err'); inp.title = '';
    }

    if (col.id === 'calendarenvio' || col.id === 'wpcargo_pickup_date_picker' || col.id === 'fecha_envio') {
        var dErr = validarFechaFila(inp.closest('tr'), inp, col);
        if (dErr) return dErr;
    }

    return err;
}

function filaVacia(tr) {
    return cols.every(function(col){
        // Campos no editables/sistema no cuentan como contenido real de la fila.
        if (col.tipo === 'number_readonly' || col.tipo === 'shipper') return true;

        var i = getInputEl(tr, col.id);
        if (!i) return true;

        var val = (i.value || '').trim();
        var def = (col.default_val || '').trim();

        // Vacio real.
        if (val === '') return true;
        // Si solo conserva el valor por defecto, no considerarlo como fila con data.
        if (def !== '' && val === def) return true;

        return false;
    });
}

function validarFila(tr) {
    var errs = [];
    cols.forEach(function(col){
        if (col.tipo === 'number_readonly' || col.tipo === 'shipper') return;
        /* saltar celdas deshabilitadas */
        var td = tr.querySelector('[data-td="'+col.id+'"]');
        if (td && td.classList.contains('wcmas-col-disabled')) return;
        var inp = getInputEl(tr, col.id);
        if (!inp) return;
        var e = validarInput(inp, col);
        if (e) errs.push({col:col.id, msg:e});
    });
    return errs;
}

function actualizarEstFila(tr) {
    var td = tr.querySelector('.wcmas-est');
    if (!td) return;
    if (filaVacia(tr)) { td.textContent=''; td.title=''; return; }
    var errs = validarFila(tr);
    td.textContent = errs.length === 0 ? 'OK' : 'ERR';
    td.title       = errs.map(function(e){ return e.msg; }).join('\n');
}

/* ── Stats ──────────────────────────────────────────────────────── */
function actualizarStats() {
    var total=0, ok=0, err=0;
    Array.from(tbody.children).forEach(function(tr){
        if(filaVacia(tr)) return;
        total++;
        recalcularTotal(tr);
        var errs = validarFila(tr);
        actualizarEstFila(tr);
        if(errs.length===0) ok++; else err++;
    });
    document.getElementById('wcmas-stats').textContent = total + ' fila(s) con datos';
    var info = document.getElementById('wcmas-valid-info');
    if(total>0) info.innerHTML = '<span style="color:#28a745">'+ok+' válida(s)</span>'+(err?' · <span style="color:#dc3545">'+err+' con errores</span>':'');
    else info.textContent='';
    document.getElementById('wcmas-btn-preview').disabled = (ok === 0);
    document.getElementById('wcmas-badge').textContent = ok;
}

function renumerarFilas() {
    Array.from(tbody.children).forEach(function(tr,i){
        var td = tr.querySelector('td:first-child'); if(td) td.textContent=i+1;
    });
}

/* ── Recolectar datos (incluye TODAS las cols para el server) ───── */
function recolectarFilas(soloValidas) {
    var res = [];
    Array.from(tbody.children).forEach(function(tr, i){
        if(filaVacia(tr)) return;
        var errs = validarFila(tr);
        if(soloValidas && errs.length>0) return;
        var fila = {_idx: i, _errs: errs};
        /* recorrer todas las columnas del servidor (incluyendo shipment_title) */
        WCMAS.columnas.forEach(function(col){
            var inp = getInputEl(tr, col.id);
            fila[col.id] = inp ? (inp.value||'').trim() : '';
        });

        var contInput = getInputEl(tr, ID_CONTAINER);
        var drvInput  = getInputEl(tr, ID_DRIVER);
        var contVal = contInput ? (contInput.value || '').trim() : (tr.dataset.autoContainer || '').trim();
        var drvVal  = drvInput  ? (drvInput.value  || '').trim() : (tr.dataset.autoDriver || '').trim();
        if (contVal !== '') fila[ID_CONTAINER] = contVal;
        if (drvVal !== '')  fila[ID_DRIVER] = drvVal;

        if (!WCMAS.es_admin) fila[ID_SHIPPER] = String(WCMAS.current_uid);
        res.push(fila);
    });
    return res;
}

/* ── Borrador ───────────────────────────────────────────────────── */
var _draftTimer = null;
function guardarBorradorDebounce() {
    clearTimeout(_draftTimer);
    _draftTimer = setTimeout(function(){ guardarBorrador(false); }, 1500);
}
function guardarBorrador(manual) {
    try {
        var datos = recolectarFilas(false);
        var payload = datos.map(function(f){
            var d={}; cols.forEach(function(c){ d[c.id]=f[c.id]||''; }); return d;
        });
        localStorage.setItem(draftKey, JSON.stringify({ts:Date.now(), datos:payload}));
        if(manual){
            var btn=document.getElementById('wcmas-btn-borrador');
            btn.innerHTML='Guardado';
            setTimeout(function(){ btn.innerHTML='<i class="fa fa-floppy-o mr-1"></i>Borrador'; },2000);
        }
    } catch(e){}
}
function cargarBorrador() {
    try {
        var raw = localStorage.getItem(draftKey);
        if(!raw) return null;
        var obj = JSON.parse(raw);
        return (obj && obj.datos && obj.datos.length) ? obj : null;
    } catch(e){ return null; }
}
function limpiarBorrador() { try{ localStorage.removeItem(draftKey); }catch(e){} }

/* ── Modal ──────────────────────────────────────────────────────── */
var _filasParaEnviar = [];

function abrirModal() {
    var validas   = recolectarFilas(true);
    var todas     = recolectarFilas(false);
    var invalidas = todas.length - validas.length;
    _filasParaEnviar = validas;
    document.getElementById('wcmas-modal-subtitle').textContent =
        'Se crearán '+validas.length+' envío(s). El remitente se toma desde cada fila.';

    var resDiv=document.getElementById('wcmas-modal-resumen');
    resDiv.innerHTML=[
        ['<span style="font-size:1.4rem;font-weight:700;color:#28a745">'+validas.length+'</span>','filas válidas','#d7f7c2','#135d3e'],
        invalidas>0?['<span style="font-size:1.4rem;font-weight:700;color:#dc3545">'+invalidas+'</span>','con errores (no se crearán)','#fce9e9','#8a1a1a']:null,
    ].filter(Boolean).map(function(x){
        return '<div style="background:'+x[2]+';border-radius:6px;padding:10px 16px;color:'+x[3]+'">'+x[0]+'<div class="small mt-1">'+x[1]+'</div></div>';
    }).join('');

    document.getElementById('wcmas-modal-errores-wrap').style.display=invalidas>0?'':'none';

    var thead2=document.getElementById('wcmas-modal-thead');
    var tbody2=document.getElementById('wcmas-modal-tbody');
    thead2.innerHTML='<tr><th>#</th>'+cols.map(function(c){return '<th>'+esc(c.label)+'</th>';}).join('')+'<th>Estado</th></tr>';
    tbody2.innerHTML='';
    validas.slice(0,50).forEach(function(fila,i){
        var tr2=document.createElement('tr');
        tr2.innerHTML='<td class="small">'+(i+1)+'</td>'
            +cols.map(function(c){return '<td class="small">'+esc(fila[c.id]||'')+'</td>';}).join('')
            +'<td><span class="badge badge-success small">OK</span></td>';
        tbody2.appendChild(tr2);
    });
    if(validas.length>50){
        var tr3=document.createElement('tr');
        tr3.innerHTML='<td colspan="'+(cols.length+2)+'" class="text-center text-muted small">... y '+(validas.length-50)+' más</td>';
        tbody2.appendChild(tr3);
    }
    document.getElementById('wcmas-modal-overlay').style.display='';
    document.body.style.overflow='hidden';
}

function cerrarModal() {
    document.getElementById('wcmas-modal-overlay').style.display='none';
    document.body.style.overflow='';
}

/* ── Enviar ─────────────────────────────────────────────────────── */
function enviarFilas() {
    cerrarModal();
    var grid    = document.getElementById('wcmas-grid-wrap');
    var toolbar = document.getElementById('wcmas-toolbar');
    var loader  = document.getElementById('wcmas-loader');
    var result  = document.getElementById('wcmas-resultado');
    var bar     = document.getElementById('wcmas-progress-bar');
    var sub     = document.getElementById('wcmas-loader-sub');

    grid.style.display='none'; toolbar.style.display='none';
    loader.style.display=''; result.style.display='none';
    document.getElementById('wcmas-hint').style.display='none';

    var prog=0;
    var tick=setInterval(function(){
        prog=Math.min(prog+8,88); bar.style.width=prog+'%';
        sub.textContent='Procesando '+_filasParaEnviar.length+' envío(s)...';
    },180);

    var fd=new FormData();
    fd.append('action','wcmas_procesar_lote');
    fd.append('nonce',WCMAS.nonce);

    _filasParaEnviar.forEach(function(fila,i){
        WCMAS.columnas.forEach(function(c){ fd.append('filas['+i+']['+c.id+']', fila[c.id]||''); });
        fd.append('filas['+i+']['+ID_CONTAINER+']', fila[ID_CONTAINER] || '');
        fd.append('filas['+i+']['+ID_DRIVER+']', fila[ID_DRIVER] || '');
    });

    fetch(WCMAS.ajax_url,{method:'POST',body:fd,credentials:'same-origin'})
        .then(function(r){ return r.json(); })
        .then(function(resp){
            clearInterval(tick); bar.style.width='100%';
            setTimeout(function(){ loader.style.display='none'; mostrarResultado(resp); },300);
        })
        .catch(function(){
            clearInterval(tick); loader.style.display='none';
            grid.style.display=''; toolbar.style.display='flex';
            alert('Error de conexión. Intenta de nuevo.');
        });
}

/* ── Resultado ──────────────────────────────────────────────────── */
function mostrarResultado(resp) {
    var result=document.getElementById('wcmas-resultado');
    var rbody =document.getElementById('wcmas-resultado-body');
    var rres  =document.getElementById('wcmas-resultado-resumen');
    result.style.display='';
    if(!resp.success||!resp.data){
        rres.innerHTML='<div class="alert alert-danger">Error: '+(resp.data&&resp.data.msg?esc(resp.data.msg):'desconocido')+'</div>';
        return;
    }
    var d=resp.data;
    rres.innerHTML='<div class="d-flex" style="gap:10px;flex-wrap:wrap;margin-bottom:8px">'
        +'<span class="badge badge-success p-2" style="font-size:14px">'+d.ok+' creado(s)</span>'
        +(d.errores>0?'<span class="badge badge-danger p-2" style="font-size:14px">'+d.errores+' con error</span>':'')
        +'</div>';
    rbody.innerHTML='';
    d.resultados.forEach(function(r){
        var tr4=document.createElement('tr');
        tr4.style.background=r.ok?'':'#fff5f5';
        tr4.innerHTML='<td class="small">'+r.fila_num+'</td>'
            +'<td>'+(r.ok?'<code class="small">'+esc(r.tracking)+'</code>':'—')+'</td>'
            +'<td class="small">'+esc(r.label||'')+'</td>'
            +'<td>'+(r.ok?'<span class="badge badge-success">Creado</span>':'<span class="badge badge-danger">Error</span>')+'</td>'
            +'<td class="small text-muted">'+(r.ok?'':Object.values(r.errores||{}).join(', '))+'</td>';
        rbody.appendChild(tr4);
    });
    limpiarBorrador();
    if(d.errores>0){
        document.getElementById('wcmas-grid-wrap').style.display='';
        document.getElementById('wcmas-toolbar').style.display='flex';
    }
}

function esc(s){ var d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }

/* ── Botones ────────────────────────────────────────────────────── */
document.getElementById('wcmas-btn-add5').addEventListener('click', function(){
    for(var i=0;i<5;i++){ numFilas++; tbody.appendChild(crearFila(numFilas)); }
});
document.getElementById('wcmas-btn-borrador').addEventListener('click', function(){ guardarBorrador(true); });
document.getElementById('wcmas-btn-limpiar').addEventListener('click', function(){
    if(!confirm('¿Limpiar toda la tabla?')) return;
    limpiarBorrador(); initGrilla(null);
    document.getElementById('wcmas-resultado').style.display='none';
    document.getElementById('wcmas-grid-wrap').style.display='';
    document.getElementById('wcmas-toolbar').style.display='flex';
});
document.getElementById('wcmas-btn-preview').addEventListener('click', abrirModal);
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

/* ── Init: cargar bloqueos y luego inicializar grilla ─────────── */
(function(){
    var borrador = cargarBorrador();
    if(borrador){
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
    } else {
        initGrilla(null);
    }

    // Cargar bloqueos en background (no bloquea la grilla)
    var seguro = { bloquear_hoy: false, fechas_bloqueadas: [], domingos_desbloqueados: [] };
    function cargarBloqueo(tipo) {
        return fetch(WCMAS.ajax_url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=merc_bloqueo_info&tipo=' + tipo
        }).then(function(r){ return r.json(); })
          .then(function(r){ return r.success ? r.data : seguro; })
          .catch(function(){ return seguro; });
    }
    cargarBloqueo('normal').then(function(d){ WCMAS.bloqueos.normal  = d; });
    cargarBloqueo('express').then(function(d){ WCMAS.bloqueos.express = d; });
})();

})();
</script>

